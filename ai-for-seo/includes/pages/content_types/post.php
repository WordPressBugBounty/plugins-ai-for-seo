<?php
/**
 * Renders the content of the submenu page for the "AI for SEO" posts-page.
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

$ai4seo_supported_post_types = ai4seo_get_supported_post_types();

$ai4seo_post_type = ai4seo_get_current_post_type();

if (!in_array($ai4seo_post_type, $ai4seo_supported_post_types)) {
    echo "Unknown post type: " . esc_html($ai4seo_post_type);
    $ai4seo_post_type = "post";
}

$ai4seo_translated_post_type = ai4seo_get_post_type_translation($ai4seo_post_type);
$ai4seo_translated_post_type_plural = ai4seo_get_post_type_translation($ai4seo_post_type, true);

// sanitize and get current page (pagination)
$ai4seo_current_page = absint($_REQUEST["ai4seo-page"] ?? 1);

if ($ai4seo_current_page < 1) {
    $ai4seo_current_page = 1;
}


$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

// check if the cron job should be executed sooner
if (isset($_GET["ai4seo-execute-cron-job-sooner"]) && $_GET["ai4seo-execute-cron-job-sooner"]) {
    ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
}

// collect some admin links and buttons
$ai4seo_this_admin_tab_url = ai4seo_get_post_type_url($ai4seo_post_type, $ai4seo_current_page);
$ai4seo_refresh_button = ai4seo_get_small_button_tag($ai4seo_this_admin_tab_url, "rotate", __("Refresh page", "ai-for-seo"));

// execute cron job sooner link
$ai4seo_execute_sooner_button_url = ai4seo_get_post_type_url($ai4seo_post_type, $ai4seo_current_page, array("ai4seo-execute-cron-job-sooner" => true));
$ai4seo_execute_sooner_button = ai4seo_get_small_button_tag($ai4seo_execute_sooner_button_url, "bolt", __("Execute sooner!", "ai-for-seo"));

// Define variable for the label of the failed-metadata-generations-link
$ai4seo_retry_all_failed_metadata_generations_link_label = "<span class='ai4seo-retry-failed'>" . __("Retry all failed metadata generations", "ai-for-seo") . "</span><span class='ai4seo-retry-failed-mobile'>" . __("Retry all failed", "ai-for-seo") . "</span>";

// retry all failed metadata generations link
$ai4seo_retry_all_failed_metadata_generations_link_tag = ai4seo_get_small_button_tag("#", "rotate", $ai4seo_retry_all_failed_metadata_generations_link_label, "", "ai4seo_retry_all_failed_metadata(this, '" . esc_js($ai4seo_post_type) . "'); return false;");

$ai4seo_consider_purchasing_more_credits_link_url = ai4seo_get_admin_url();
$ai4seo_consider_purchasing_more_credits_link_tag = ai4seo_get_small_button_tag($ai4seo_consider_purchasing_more_credits_link_url, "circle-plus", __("Get more Credits", "ai-for-seo"), "ai4seo-success-button");

// get value for bulk toggle checkbox
$ai4seo_is_bulk_generation_activated = ai4seo_is_bulk_generation_enabled($ai4seo_post_type);
$ai4seo_is_bulk_generation_checked_phrase = ($ai4seo_is_bulk_generation_activated ? "checked" : "");


// ___________________________________________________________________________________________ \\
// === READ POSTS ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Prepare arguments for the wp-query
$ai4seo_posts_query_arguments = array(
    "post_status" => array('publish', 'future'),
    "post_type" => $ai4seo_post_type,
    "posts_per_page" => 20,
    "orderby" => "ID",
    "order" => "DESC",
    "paged" => $ai4seo_current_page,
);

// Fire query
$ai4seo_post_data = new WP_Query($ai4seo_posts_query_arguments);

// Define variable for the posts that were found
$ai4seo_all_posts = $ai4seo_post_data->posts ?? array();
$ai4seo_total_pages = (int) ($ai4seo_post_data->max_num_pages ?? 1);

unset($ai4seo_post_data);

// fetch all post ids from this page
$ai4seo_current_post_ids = array_map(function ($post) {
    return (int) $post->ID;
}, $ai4seo_all_posts);

// get percentage of active metadata for all posts
$ai4seo_percentage_of_active_metadata_by_post_ids = ai4seo_read_percentage_of_active_metadata_by_post_ids($ai4seo_current_post_ids);

// look for post ids that are scheduled by the cron jobs to process metadata
$ai4seo_pending_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);
$ai4seo_processing_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME);

// look for failed to fill post ids
$ai4seo_all_failed_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);

// read all key phrases for all posts in $ai4seo_this_page_post_ids
$ai4seo_third_party_seo_plugin_key_phrases = ai4seo_read_third_party_seo_plugin_key_phrases($ai4seo_current_post_ids);

// read all seo scores for all posts in $ai4seo_this_page_post_ids
$ai4seo_yoast_seo_scores = ai4seo_read_yoast_seo_scores($ai4seo_current_post_ids);

$ai4seo_bulk_generation_cron_job_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_bulk_generation_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);

$ai4seo_last_cron_job_call_timestamp = ai4seo_get_last_cron_job_call_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call = wp_next_scheduled(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call_diff = ($ai4seo_next_cron_job_call ? $ai4seo_next_cron_job_call - time() : 9999999);

// get the amount of failed posts for this post type
$ai4seo_num_failed_to_fill_this_post_type = ai4seo_get_num_generation_status_and_post_types_posts(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME, $ai4seo_post_type);

// remove entries from $ai4seo_failed_to_fill_post_ids that are not on this page
$ai4seo_current_page_failed_to_fill_post_ids = array();
if ($ai4seo_all_posts) {
    foreach ($ai4seo_all_posts AS $ai4seo_this_post) {
        if (in_array($ai4seo_this_post->ID, $ai4seo_all_failed_metadata_post_ids)) {
            $ai4seo_current_page_failed_to_fill_post_ids[] = $ai4seo_this_post->ID;
        }
    }
}
$ai4seo_all_failed_metadata_post_ids = $ai4seo_current_page_failed_to_fill_post_ids;

$ai4seo_bulk_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
$ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);
$ai4seo_generate_metadata_for_fully_covered_entries = ai4seo_get_setting(AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES);

if ($ai4seo_generate_metadata_for_fully_covered_entries) {
    $ai4seo_generated_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME);
} else {
    $ai4seo_generated_metadata_post_ids = array();
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === TABLE WITH ALL POSTS ================================================================== \\

// Stop script if no posts have been found -> show message and stop page rendering
if (!$ai4seo_all_posts) {
    echo sprintf(
        '<p>' . esc_html__('No %s found.', "ai-for-seo") . '</p>',
        esc_html($ai4seo_translated_post_type_plural),
    );
    return;
}

// Display table with entries
echo "<div class='ai4seo-posts-table-container'>";
echo "<table class='widefat striped table-view-list pages ai4seo-posts-table'>";
    echo "<tr>";
        echo "<th>" . esc_html__("ID", "ai-for-seo") . "</th>";
        echo "<th class='ai4seo-hidden-on-mobile'>" . esc_html__("Title and key phrase", "ai-for-seo") . "</th>";
        echo "<th>";
            echo esc_html__("Metadata coverage", "ai-for-seo");

            echo "<span class='ai4seo-visible-on-mobile'> / ";
                echo esc_html__("Title and key phrase", "ai-for-seo");
            echo "</span>";

            // RESET ALL FAILED METADATA GENERATION
            if (($ai4seo_num_failed_to_fill_this_post_type || $ai4seo_current_page_failed_to_fill_post_ids)) {
                echo "<div class='ai4seo-table-title-button'>";
                echo ai4seo_wp_kses($ai4seo_retry_all_failed_metadata_generations_link_tag);
                echo "</div>";
            }
        echo "</th>";
        echo "<th></th>";
    echo "</tr>";

    // Loop through all posts
    foreach ($ai4seo_all_posts as $ai4seo_this_post) {
        // Get post-ID
        $ai4seo_this_post_id = $ai4seo_this_post->ID;

        // Get post-title
        $ai4seo_single_post_title = $ai4seo_this_post->post_title;

        // Get post-link
        $ai4seo_single_post_link = get_permalink($ai4seo_this_post_id);

        // get timestamp of post date
        $ai4seo_this_post_date_timestamp = strtotime($ai4seo_this_post->post_date_gmt);

        // check if the new-or-existing filter complies with this post ("both" -> yes, "new" -> only posts with post_date_timestamp > reference_timestamp, "existing" -> only posts with post_date_timestamp <= reference_timestamp)
        $ai4seo_is_excluded_by_new_or_existing_filter = false;
        if ($ai4seo_bulk_generation_new_or_existing_filter == "new" && $ai4seo_this_post_date_timestamp <= $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp) {
            $ai4seo_is_excluded_by_new_or_existing_filter = true;
        } else if ($ai4seo_bulk_generation_new_or_existing_filter == "existing" && $ai4seo_this_post_date_timestamp > $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp) {
            $ai4seo_is_excluded_by_new_or_existing_filter = true;
        }

        // this post meta coverage summary
        $ai4seo_this_active_metadata_coverage_percentage = $ai4seo_percentage_of_active_metadata_by_post_ids[$ai4seo_this_post_id] ?? 0;
        if ($ai4seo_generate_metadata_for_fully_covered_entries) {
            $ai4seo_this_metadata_generation_is_not_finished = !in_array($ai4seo_this_post_id, $ai4seo_generated_metadata_post_ids);
        } else {
            $ai4seo_this_metadata_generation_is_not_finished = ($ai4seo_this_active_metadata_coverage_percentage < 100);
        }
        $ai4seo_this_post_is_failed_to_fill = in_array($ai4seo_this_post_id, $ai4seo_current_page_failed_to_fill_post_ids);

        // check if filling is in progress
        $ai4seo_is_post_pending = false;
        $ai4seo_is_post_waiting_to_get_scheduled = false;
        $ai4seo_is_insufficient_credits = false;
        $ai4seo_is_waiting_for_cron_job = false;

        // if the post is not covered, but pending
        if ($ai4seo_this_metadata_generation_is_not_finished && !$ai4seo_is_excluded_by_new_or_existing_filter) {
            if (in_array($ai4seo_this_post_id, $ai4seo_pending_metadata_post_ids) || in_array($ai4seo_this_post_id, $ai4seo_processing_metadata_post_ids)) {
                $ai4seo_is_post_pending = true;
            } else if ($ai4seo_is_bulk_generation_activated) {
                $ai4seo_is_post_waiting_to_get_scheduled = true;
            }
        }

        // if the post is not covered and the filling process is not in progress
        if ($ai4seo_this_post_is_failed_to_fill) {
            $ai4seo_is_post_pending = false;
            $ai4seo_is_post_waiting_to_get_scheduled = false;
        }

        // if the user has not enough credits to fill the metadata
        if ($ai4seo_is_post_waiting_to_get_scheduled && $ai4seo_current_credits_balance < AI4SEO_MIN_CREDITS_BALANCE) {
            $ai4seo_is_post_pending = false;
            $ai4seo_is_post_waiting_to_get_scheduled = false;
            $ai4seo_is_insufficient_credits = true;
        }

        // if the current status is not processing, then the cron job is not running
        if ($ai4seo_is_post_pending && $ai4seo_bulk_generation_cron_job_status !== "processing") {
            $ai4seo_is_post_pending = false;

            if ($ai4seo_is_bulk_generation_activated) {
                $ai4seo_is_post_waiting_to_get_scheduled = true;
            }
        }

        // if we are waiting for the cron job to start the next filling process
        if ($ai4seo_is_bulk_generation_activated && !$ai4seo_is_post_pending && $ai4seo_is_post_waiting_to_get_scheduled && $ai4seo_bulk_generation_cron_job_status !== "processing") {
            $ai4seo_is_waiting_for_cron_job = true;
        }

        // Display table-row for this post
        echo "<tr>";
            // Post-ID
            echo "<td>";
                echo esc_html($ai4seo_this_post_id);
            echo "</td>";

            // Post-Title
            echo "<td class='title column-title has-row-actions column-primary post-title ai4seo-hidden-on-mobile'>";
                echo "<strong>";
                    echo "<a href='" . esc_attr($ai4seo_single_post_link) . "' target='_blank' class='ai4seo-table-content-link'>";
                        echo esc_html($ai4seo_single_post_title);
                    echo "</a>";

                    if (isset($ai4seo_third_party_seo_plugin_key_phrases[$ai4seo_this_post_id])) {
                        echo " <span class='ai4seo-key-phrase'>(" . esc_html($ai4seo_third_party_seo_plugin_key_phrases[$ai4seo_this_post_id]) . ")</span>";
                    }
                echo "</strong>";
            echo "</td>";

            // Generation Coverage
            echo "<td class='ai4seo-generation-coverage'>";
                // output progress bar
                echo "<div id='ai4seo-seo-coverage-progress-bar-" . esc_attr($ai4seo_this_post_id) . "' class='ai4seo-seo-coverage-progress-bar" . ($ai4seo_is_post_pending ? " ai4seo-green-animated-progress-bar" : ($ai4seo_is_post_waiting_to_get_scheduled ? " ai4seo-gray-animated-progress-bar" : "")) . ($ai4seo_this_metadata_generation_is_not_finished ? " ai4seo-progress-bar-not-finished" : " ai4seo-progress-bar-finished") . "'>";
                    echo "<div class='ai4seo-seo-coverage-inner-progress-bar' style='width: " . esc_attr($ai4seo_this_active_metadata_coverage_percentage) . "%'></div>";
                echo "</div>";

                if ($ai4seo_is_post_waiting_to_get_scheduled || $ai4seo_is_waiting_for_cron_job) {
                    if ($ai4seo_is_waiting_for_cron_job) {
                        echo "<div class='ai4seo-sub-info'>" . esc_html__("Pending", "ai-for-seo") . "... ";
                            if ($ai4seo_next_cron_job_call_diff >= 60) {
                                echo sprintf(esc_html__("The task is set to run in less than %u minutes.", "ai-for-seo"), esc_html($ai4seo_next_cron_job_call_diff / 60));
                            } else {
                                echo esc_html__("Task is scheduled to execute any moment.", "ai-for-seo");
                            }

                            // execute sooner link
                            if ($ai4seo_next_cron_job_call_diff >= 70) {
                                echo " " . ai4seo_wp_kses($ai4seo_execute_sooner_button);
                            } else {
                                echo " " . ai4seo_wp_kses($ai4seo_refresh_button);
                            }
                        echo "</div>";
                    } else {
                        echo "<div class='ai4seo-sub-info'>";
                            echo esc_html__("Pending", "ai-for-seo") . "... ";
                            echo "(" . sprintf(esc_html__("I'm probably optimizing a different entry.", "ai-for-seo"), esc_html($ai4seo_post_type)). ")";
                            echo " " . ai4seo_wp_kses($ai4seo_refresh_button);
                        echo "</div>";
                    }
                } else if ($ai4seo_is_post_pending) {
                    echo "<div class='ai4seo-sub-info'>";
                        echo esc_html__("Processing", "ai-for-seo") . "...";
                        echo " " . ai4seo_wp_kses($ai4seo_refresh_button);
                    echo "</div>";
                } else if ($ai4seo_is_insufficient_credits) {
                    echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
                        echo esc_html__("Insufficient Credits", "ai-for-seo") . ".";
                        echo " " . ai4seo_wp_kses($ai4seo_consider_purchasing_more_credits_link_tag);
                    echo "</div>";
                } else if ($ai4seo_this_post_is_failed_to_fill && $ai4seo_this_metadata_generation_is_not_finished) {
                    echo "<div class='ai4seo-seo-data-not-covered-message'>";
                        echo "<span>" . esc_html__("Failed to automatically fill metadata.", "ai-for-seo") . "</span>";
                        echo " " . ai4seo_wp_kses(ai4seo_get_small_button_tag("#", "arrow-up-right-from-square", __("Try it manually", "ai-for-seo"), "", "ai4seo_open_metadata_editor_modal(\"" . esc_js($ai4seo_this_post_id) . "\");"));
                    echo "</div>";
                } else if ($ai4seo_is_excluded_by_new_or_existing_filter && $ai4seo_this_metadata_generation_is_not_finished) {
                    echo "<div class='ai4seo-sub-info'>";
                        echo esc_html__("Excluded by 'New or existing entries' filter.", "ai-for-seo");
                    echo "</div>";
                }

                // Title display for mobile version
                echo "<div class='ai4seo-visible-on-mobile'>";
                    echo "<strong>";
                        echo "<a href='" . esc_attr($ai4seo_single_post_link) . "' target='_blank' class='ai4seo-table-content-link'>";
                            echo esc_html($ai4seo_single_post_title);
                        echo "</a>";

                        if (isset($ai4seo_third_party_seo_plugin_key_phrases[$ai4seo_this_post_id])) {
                            echo " <span class='ai4seo-key-phrase'>(" . esc_html($ai4seo_third_party_seo_plugin_key_phrases[$ai4seo_this_post_id]) . ")</span>";
                        }
                    echo "</strong>";
                echo "</div>";
            echo "</td>";

            // Post-Edit-Link
            echo "<td>";
                // Edit-Link
                echo ai4seo_wp_kses(ai4seo_get_edit_metadata_button($ai4seo_this_post_id));
            echo "</td>";
        echo "</tr>";
    }
echo "</table>";
echo "</div>";

// Pagination
$ai4seo_pagination_base_argument = ai4seo_get_post_type_url($ai4seo_post_type, 0);
$ai4seo_total_pages = max(1, $ai4seo_total_pages);
$ai4seo_current_page = max(1, $ai4seo_current_page);
$ai4seo_pagination_base_argument = $ai4seo_pagination_base_argument ?: '%_%'; // Default base if not defined
$ai4seo_pagination_arguments = array(
    "base" => $ai4seo_pagination_base_argument,
    "total" => $ai4seo_total_pages,
    "current" => $ai4seo_current_page,
    "show_all" => false,
    "end_size" => 2,
    "mid_size" => 0,
    "prev_text" => "&larr; " . __("Previous", "ai-for-seo"),
    "next_text" => __("Next", "ai-for-seo") . " &rarr;",
    "add_args" => false,
);

$ai4seo_pagination_links = paginate_links($ai4seo_pagination_arguments);

if (!empty($ai4seo_pagination_links)) {
    echo "<div class='ai4seo-pagination'>";
    echo wp_kses_post($ai4seo_pagination_links);
    echo "</div>";
}