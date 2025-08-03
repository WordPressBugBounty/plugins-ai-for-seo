<?php
/**
 * Renders the content of the submenu media for the "AI for SEO" page.
 *
 * @since 1.2.0
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

global $wpdb;
global $ai4seo_allowed_attachment_mime_types;

$ai4seo_main_attachment_post_type = "attachment";
$ai4seo_nice_post_type = "media";
$ai4seo_post_types = ai4seo_get_supported_attachment_post_types();
$ai4seo_additional_post_types = array();
$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

$ai4seo_media_label_singular = _n("media", "media", 1, "ai-for-seo");
$ai4seo_media_label_plural = _n("media", "media", 2, "ai-for-seo");
$ai4seo_total_pages = 1;
$ai4seo_current_page = absint($_REQUEST["ai4seo-page"] ?? 1);

if ($ai4seo_current_page < 1) {
    $ai4seo_current_page = 1;
}

// check if the cron job should be executed sooner
if (isset($_GET["ai4seo-execute-cron-job-sooner"]) && $_GET["ai4seo-execute-cron-job-sooner"]) {
    ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
}


// ___________________________________________________________________________________________ \\
// === READ ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// handle the import of NextGen Gallery images
if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY)) {
    $ai4seo_num_not_imported_nextgen_gallery_images = 0;

    // read all pid's of wp_ngg_pictures
    $ai4seo_nextgen_gallery_image_pids = $wpdb->get_results("SELECT `pid` FROM " . esc_sql($wpdb->prefix) . "ngg_pictures WHERE `pid` > 0", ARRAY_A);

    if ($ai4seo_nextgen_gallery_image_pids) {
        $ai4seo_nextgen_gallery_image_pids = array_map(function ($ai4seo_this_nextgen_gallery_image) {
            return (int) $ai4seo_this_nextgen_gallery_image["pid"];
        }, $ai4seo_nextgen_gallery_image_pids);

        // get the number of entries from wp_posts where type is AI4SEO_NEXTGEN_GALLERY_POST_TYPE and pid is not in wp_posts.post_parent
        $ai4seo_num_imported_nextgen_gallery_images = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($wpdb->posts) . " WHERE `post_type` = '" . esc_sql(AI4SEO_NEXTGEN_GALLERY_POST_TYPE) . "' AND `post_parent` IN (" . esc_sql(implode(",", $ai4seo_nextgen_gallery_image_pids)) . ")");
        $ai4seo_num_not_imported_nextgen_gallery_images = count($ai4seo_nextgen_gallery_image_pids) - $ai4seo_num_imported_nextgen_gallery_images;
    }

    $ai4seo_import_nextgen_gallery_button = ai4seo_wp_kses(
        ai4seo_get_button_text_link_tag("#", "file-export",  esc_html__("Import NextGen Gallery images ($ai4seo_num_not_imported_nextgen_gallery_images)", "ai-for-seo"),
            ($ai4seo_num_not_imported_nextgen_gallery_images ? "ai4seo-success-button" : "ai4seo-inactive-button"),
            ($ai4seo_num_not_imported_nextgen_gallery_images ? "ai4seo_import_nextgen_gallery_images(this);" : ""))
    );
}

// Prepare arguments for the wp-query
$ai4seo_posts_query_arguments = array(
    "post_status" => array('publish', 'future', 'inherit'),
    "post_type" => count($ai4seo_post_types) > 1 ? $ai4seo_post_types : reset($ai4seo_post_types),
    "post_mime_type" => $ai4seo_allowed_attachment_mime_types,
    "posts_per_page" => 20,
    "orderby" => "ID",
    "order" => "DESC",
    "paged" => $ai4seo_current_page,
);

// Fire query
$ai4seo_post_data = new WP_Query($ai4seo_posts_query_arguments);

// Define variable for the posts that were found
$ai4seo_all_attachment_posts = $ai4seo_post_data->posts ?? array();
$ai4seo_total_pages = (int) ($ai4seo_post_data->max_num_pages ?? 1);

unset($ai4seo_post_data);

// fetch all post ids from this page
$ai4seo_current_attachment_post_ids = array_map(function ($ai4seo_attachment_post) {
    return (int) $ai4seo_attachment_post->ID;
}, $ai4seo_all_attachment_posts);


// read attributes coverage
$ai4seo_attachment_attributes_coverage = ai4seo_read_and_analyse_attachment_attributes_coverage($ai4seo_current_attachment_post_ids);
$ai4seo_attachment_attributes_coverage_summary = ai4seo_get_attachment_attributes_coverage_summary($ai4seo_attachment_attributes_coverage);
$ai4seo_num_total_attachment_attributes = ai4seo_get_active_num_attachment_attributes();

// get value for bulk generation toggle checkbox
$ai4seo_is_bulk_generation_activated = ai4seo_is_bulk_generation_enabled($ai4seo_main_attachment_post_type);
$ai4seo_is_bulk_generation_checked_phrase = ($ai4seo_is_bulk_generation_activated ? "checked" : "");

// cronjob
$ai4seo_bulk_generation_cron_job_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_bulk_generation_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);

$ai4seo_last_cron_job_call_timestamp = ai4seo_get_last_cron_job_call_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call = wp_next_scheduled(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
$ai4seo_next_cron_job_call_diff = ($ai4seo_next_cron_job_call ? $ai4seo_next_cron_job_call - time() : 9999999);


// look for attachment post ids that are scheduled by the cron jobs to process attributes
$ai4seo_pending_attributes_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
$ai4seo_processing_attributes_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

// look for failed to fill post ids
$ai4seo_failed_attributes_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

// remove entries from $ai4seo_all_failed_to_fill_attributes_attachment_post_ids that are not on this page
$ai4seo_current_page_failed_to_fill_attachment_post_ids = array();

if ($ai4seo_all_attachment_posts) {
    foreach ($ai4seo_all_attachment_posts AS $ai4seo_this_attachment_post) {
        if (in_array($ai4seo_this_attachment_post->ID, $ai4seo_failed_attributes_attachment_post_ids)) {
            $ai4seo_current_page_failed_to_fill_attachment_post_ids[] = $ai4seo_this_attachment_post->ID;
        }
    }
}

// collect some admin links and buttons
$ai4seo_this_admin_tab_url = ai4seo_get_admin_url($ai4seo_nice_post_type, array("ai4seo-page" => $ai4seo_current_page));
$ai4seo_refresh_button = ai4seo_get_small_button_tag($ai4seo_this_admin_tab_url, "rotate", __("Refresh page", "ai-for-seo"));

// execute cron job sooner link
$ai4seo_execute_sooner_text_link_url = ai4seo_get_admin_url($ai4seo_nice_post_type, array("ai4seo-execute-cron-job-sooner" => true, "ai4seo-page" => $ai4seo_current_page));
$ai4seo_execute_sooner_button = ai4seo_get_small_button_tag($ai4seo_execute_sooner_text_link_url, "bolt", __("Execute sooner!", "ai-for-seo"));

// Define variable for the label of the failed-attributes-generations-link
$ai4seo_retry_all_failed_attachment_attributes_generations_link_label = "<span class='ai4seo-retry-failed'>" . __("Retry all failed media attributes generations", "ai-for-seo") . "</span><span class='ai4seo-retry-failed-mobile'>" . __("Retry all failed", "ai-for-seo") . "</span>";

// retry all failed attachment attributes generations link
$ai4seo_retry_all_failed_attachment_attributes_generations_link_tag = ai4seo_get_small_button_tag("#", "rotate", $ai4seo_retry_all_failed_attachment_attributes_generations_link_label, "", "ai4seo_retry_all_failed_attachment_attributes(this); return false;");

$ai4seo_consider_purchasing_more_credits_link_url = ai4seo_get_admin_url();
$ai4seo_consider_purchasing_more_credits_link_tag = ai4seo_get_small_button_tag($ai4seo_consider_purchasing_more_credits_link_url, "circle-plus", __("Get more Credits", "ai-for-seo"), "ai4seo-success-button");

$ai4seo_bulk_generation_order = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_ORDER);
$ai4seo_bulk_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
$ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);
$ai4seo_generate_media_attributes_for_fully_covered_entries = ai4seo_get_setting(AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES);

if ($ai4seo_generate_media_attributes_for_fully_covered_entries) {
    $ai4seo_generated_media_attributes_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
} else {
    $ai4seo_generated_media_attributes_post_ids = array();
}

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();
$ai4seo_active_attachment_attribute_names = array();

foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS AS $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
    if (in_array($ai4seo_this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes)) {
        $ai4seo_active_attachment_attribute_names[] = $ai4seo_this_attachment_attribute_details["name"];
    }
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (isset($ai4seo_import_nextgen_gallery_button) && $ai4seo_import_nextgen_gallery_button) {
    echo "<div class='ai4seo-buttons-wrapper'>";
        echo ai4seo_wp_kses($ai4seo_import_nextgen_gallery_button);
    echo "</div>";
}

// Stop script if no posts have been found -> show message and stop page rendering
if (!$ai4seo_all_attachment_posts) {
    echo '<p>' . esc_html__('No relevant media found.', "ai-for-seo") . '</p>';
    return;
}


// Display table with entries
echo "<div class='ai4seo-posts-table-container'>";
echo "<table class='widefat striped table-view-list attachments ai4seo-posts-table'>";
    echo "<tr>";
        echo "<th>" . esc_html__("ID", "ai-for-seo") . "</th>";
        echo "<th></th>";
        echo "<th class='ai4seo-hidden-on-mobile'>" . esc_html__("Title", "ai-for-seo") . "</th>";
        echo "<th>";
            echo esc_html__("SEO Coverage", "ai-for-seo");

            echo "<span class='ai4seo-visible-on-mobile'> / ";
                echo esc_html__("Title", "ai-for-seo");
            echo "</span>";

            echo " <span style='font-size: smaller'>(" . esc_html(implode(", ", $ai4seo_active_attachment_attribute_names)) . ")</span>";

            // RESET ALL FAILED ATTACHMENT ATTRIBUTES GENERATION
            if (count($ai4seo_failed_attributes_attachment_post_ids)) {
                echo "<div class='ai4seo-table-title-button'>";
                echo ai4seo_wp_kses($ai4seo_retry_all_failed_attachment_attributes_generations_link_tag);
                echo "</div>";
            }
        echo "</th>";
        echo "<th></th>";
    echo "</tr>";

    // Loop through entries and display table-row for each entry
    foreach ($ai4seo_all_attachment_posts as $ai4seo_this_attachment) {
        // Prepare variables
        $ai4seo_this_post_attachment_id = (int) $ai4seo_this_attachment->ID ?? "";
        $ai4seo_this_attachment_title = $ai4seo_this_attachment->post_title ?? "";
        $ai4seo_this_mime_type = $ai4seo_this_attachment->post_mime_type ?? "";
        $ai4seo_this_post_link = get_edit_post_link($ai4seo_this_attachment) ?: $ai4seo_this_attachment->guid ?? "";

        // get timestamp of post date
        $ai4seo_this_attachment_date_timestamp = strtotime($ai4seo_this_attachment->post_date_gmt);

        // check if the new-or-existing filter complies with this post ("both" -> yes, "new" -> only posts with post_date_timestamp > reference_timestamp, "existing" -> only posts with post_date_timestamp <= reference_timestamp)
        $ai4seo_is_excluded_by_new_or_existing_filter = false;
        if ($ai4seo_bulk_generation_new_or_existing_filter == "new" && $ai4seo_this_attachment_date_timestamp <= $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp) {
            $ai4seo_is_excluded_by_new_or_existing_filter = true;
        } else if ($ai4seo_bulk_generation_new_or_existing_filter == "existing" && $ai4seo_this_attachment_date_timestamp > $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp) {
            $ai4seo_is_excluded_by_new_or_existing_filter = true;
        }

        // this attachment attributes coverage
        $ai4seo_this_attachment_attribute_coverage_summary = $ai4seo_attachment_attributes_coverage_summary[$ai4seo_this_post_attachment_id] ?? 0;

        if ($ai4seo_active_attachment_attributes) {
            $ai4seo_this_attachment_attribute_coverage_percentage = $ai4seo_num_total_attachment_attributes ? round(($ai4seo_this_attachment_attribute_coverage_summary / $ai4seo_num_total_attachment_attributes) * 100, 2) : 100;
            $ai4seo_this_attachment_attribute_coverage_percentage = min(100, $ai4seo_this_attachment_attribute_coverage_percentage);
            $ai4seo_this_attachment_attributes_is_not_covered = ($ai4seo_this_attachment_attribute_coverage_percentage < 100);
        } else {
            $ai4seo_this_attachment_attribute_coverage_percentage = 100;
            $ai4seo_this_attachment_attributes_is_not_covered = false;
        }

        if ($ai4seo_generate_media_attributes_for_fully_covered_entries) {
            $ai4seo_this_attachment_attributes_is_not_finished = !in_array($ai4seo_this_post_attachment_id, $ai4seo_generated_media_attributes_post_ids);
        } else {
            $ai4seo_this_attachment_attributes_is_not_finished = ($ai4seo_this_attachment_attribute_coverage_percentage < 100);
        }

        $ai4seo_is_attachment_post_failed = in_array($ai4seo_this_post_attachment_id, $ai4seo_current_page_failed_to_fill_attachment_post_ids);
        // check if filling is in progress
        $ai4seo_is_attachment_post_pending = false;
        $ai4seo_is_attachment_post_waiting_to_get_scheduled = false;
        $ai4seo_is_insufficient_credits = false;
        $ai4seo_is_attachment_post_waiting_for_cron_job = false;

        // if the post is not covered, but pending
        if ($ai4seo_this_attachment_attributes_is_not_finished && !$ai4seo_is_excluded_by_new_or_existing_filter) {
            if (in_array($ai4seo_this_post_attachment_id, $ai4seo_pending_attributes_attachment_post_ids) || in_array($ai4seo_this_post_attachment_id, $ai4seo_processing_attributes_attachment_post_ids)) {
                $ai4seo_is_attachment_post_pending = true;
            } else if ($ai4seo_is_bulk_generation_activated) {
                $ai4seo_is_attachment_post_waiting_to_get_scheduled = true;
            }
        }

        // if the post is not covered and the filling process is not in progress
        if ($ai4seo_is_attachment_post_failed) {
            $ai4seo_is_attachment_post_pending = false;
            $ai4seo_is_attachment_post_waiting_to_get_scheduled = false;
        }

        // if the user has not enough credits to fill the attachment attributes
        if ($ai4seo_is_attachment_post_waiting_to_get_scheduled && $ai4seo_current_credits_balance < AI4SEO_MIN_CREDITS_BALANCE) {
            $ai4seo_is_attachment_post_pending = false;
            $ai4seo_is_attachment_post_waiting_to_get_scheduled = false;
            $ai4seo_is_insufficient_credits = true;
        }

        // if the last cron job call was 10 or more seconds ago, than the filling in progress is a false alert
        if ($ai4seo_is_attachment_post_pending & $ai4seo_bulk_generation_cron_job_status !== "processing") {
            $ai4seo_is_attachment_post_pending = false;

            if ($ai4seo_is_bulk_generation_activated) {
                $ai4seo_is_attachment_post_waiting_to_get_scheduled = true;
            }
        }

        // if we are waiting for the cron job to start the next filling process
        if ($ai4seo_is_bulk_generation_activated && !$ai4seo_is_attachment_post_pending && $ai4seo_is_attachment_post_waiting_to_get_scheduled && $ai4seo_bulk_generation_cron_job_status !== "processing") {
            $ai4seo_is_attachment_post_waiting_for_cron_job = true;
        }

        $ai4seo_preview_image_url = "";

        if (in_array($ai4seo_this_mime_type, array("image/jpeg", "image/png", "image/gif", "image/webp"))) {
            $ai4seo_preview_image_url = wp_get_attachment_image_url($ai4seo_this_post_attachment_id, array(48, 48));

            if (!$ai4seo_preview_image_url) {
                $ai4seo_preview_image_url = $ai4seo_this_attachment->guid ?? "";
            }
        }

        if (!$ai4seo_preview_image_url) {
            $ai4seo_preview_image_url = ai4seo_get_assets_images_url("icons/document-question-48x48.png");
        }

        echo "<tr>";
            // Post-ID
            echo "<td>";
                echo esc_html($ai4seo_this_post_attachment_id);
            echo "</td>";

            // Image or File Preview
            echo "<td class='ai4seo-attachment-list-image-preview'>";
                echo "<a href='" . esc_url($ai4seo_this_post_link) . "' target='_blank'>";
                    echo "<img src='" . esc_url($ai4seo_preview_image_url) . "' alt='" . esc_html__("No image preview available", "ai-for-seo") . "'/>";
                echo "</a>";
            echo "</td>";

            // Title
            echo "<td class='title column-title has-row-actions column-primary post-title ai4seo-hidden-on-mobile'>";
                echo "<strong>";
                    echo "<a href='" . esc_url($ai4seo_this_post_link) . "' target='_blank'>";
                        echo esc_html($ai4seo_this_attachment_title);
                    echo "</a>";
                echo "</strong>";
            echo "</td>";

            // Generation Coverage
            echo "<td class='ai4seo-generation-coverage'>";
                # todo: make adjustments from main branch
                if ($ai4seo_active_attachment_attributes) {
                    // output progress bar
                    echo "<div id='ai4seo-seo-coverage-progress-bar-" . esc_attr($ai4seo_this_post_attachment_id) . "' class='ai4seo-seo-coverage-progress-bar" . ($ai4seo_is_attachment_post_pending ? " ai4seo-green-animated-progress-bar" : ($ai4seo_is_attachment_post_waiting_to_get_scheduled ? " ai4seo-gray-animated-progress-bar" : "")) . ($ai4seo_this_attachment_attributes_is_not_finished ? " ai4seo-progress-bar-not-finished" : " ai4seo-progress-bar-finished") . "'>";
                        echo "<div class='ai4seo-seo-coverage-inner-progress-bar' style='width: " . esc_attr($ai4seo_this_attachment_attribute_coverage_percentage) . "%'></div>";
                    echo "</div>";

                    if ($ai4seo_is_attachment_post_waiting_to_get_scheduled || $ai4seo_is_attachment_post_waiting_for_cron_job) {
                        if ($ai4seo_is_attachment_post_waiting_for_cron_job) {
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
                                echo "(" . esc_html__("I'm probably optimizing a different entry.", "ai-for-seo") . ") ";
                                echo ai4seo_wp_kses($ai4seo_refresh_button);
                            echo "</div>";
                        }
                    } else if ($ai4seo_is_attachment_post_pending) {
                        echo "<div class='ai4seo-sub-info'>";
                            echo esc_html__("Processing", "ai-for-seo") . "... ";
                            echo ai4seo_wp_kses($ai4seo_refresh_button);
                        echo "</div>";
                    } else if ($ai4seo_is_insufficient_credits) {
                        echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
                            echo esc_html__("Insufficient Credits", "ai-for-seo") . ". ";
                            echo ai4seo_wp_kses($ai4seo_consider_purchasing_more_credits_link_tag);
                        echo "</div>";
                    } else if ($ai4seo_is_attachment_post_failed && $ai4seo_this_attachment_attributes_is_not_finished) {
                        echo "<div class='ai4seo-seo-data-not-covered-message'>";
                            echo "<span>" . esc_html__("Failed to automatically fill media attributes.", "ai-for-seo") . "</span> ";
                            echo ai4seo_wp_kses(ai4seo_get_small_button_tag("#", "arrow-up-right-from-square", __("Try it manually", "ai-for-seo"), "", "ai4seo_open_attachment_attributes_editor_modal(\"" . esc_html($ai4seo_this_post_attachment_id) . "\");"));
                        echo "</div>";
                    } else if ($ai4seo_is_excluded_by_new_or_existing_filter && $ai4seo_this_attachment_attributes_is_not_finished) {
                        echo "<div class='ai4seo-sub-info'>";
                            echo esc_html__("Excluded by 'New or existing entries' filter.", "ai-for-seo");
                        echo "</div>";
                    }
                } else {
                    echo "<div class='ai4seo-sub-info'>";
                        echo esc_html__("No media attributes are active.", "ai-for-seo");
                    echo "</div>";
                }

                // Title display for mobile version
                echo "<div class='ai4seo-visible-on-mobile'>";
                    echo "<strong>";
                        echo "<a href='" . esc_url($ai4seo_this_post_link) . "' target='_blank'>";
                            echo esc_html($ai4seo_this_attachment_title);
                        echo "</a>";
                    echo "</strong>";
                echo "</div>";
            echo "</td>";

            // Actions
            echo "<td>";
                echo ai4seo_wp_kses(ai4seo_get_edit_attachment_attributes_button($ai4seo_this_attachment->ID));
            echo "</td>";
        echo "</tr>";
    }
echo "</table>";
echo "</div>";

// Pagination
$ai4seo_pagination_base_argument = ai4seo_get_admin_url($ai4seo_nice_post_type, array("ai4seo-page" => "%#%"));
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