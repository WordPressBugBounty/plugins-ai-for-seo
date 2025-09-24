<?php
/**
 * Required by save-everything.php.
 * Updates given settings from $ai4seo_post_parameter in bulk
 *
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

if (!isset($ai4seo_post_parameter)) {
    return;
}

global $ai4seo_settings;


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING SETTING UPDATES ======================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_settings = array();
$ai4seo_recent_setting_changes = array();

foreach (AI4SEO_DEFAULT_SETTINGS as $ai4seo_this_setting_name => $ai4seo_this_default_setting_value) {
    // Check if $ai4seo_post_parameter-entry exists for this setting
    if (!isset($ai4seo_post_parameter[$ai4seo_this_setting_name])) {
        continue;
    }

    $ai4seo_this_old_setting_value = $ai4seo_settings[$ai4seo_this_setting_name];
    $ai4seo_this_new_setting_value = $ai4seo_post_parameter[$ai4seo_this_setting_name];

    // is equal to old setting -> ignore it
    if ($ai4seo_this_new_setting_value == $ai4seo_this_old_setting_value) {
        continue;
    }

    // validate the setting value
    if (!ai4seo_validate_setting_value($ai4seo_this_setting_name, $ai4seo_this_new_setting_value)) {
        ai4seo_send_json_error(sprintf(
            esc_html__("Invalid setting value for %s", "ai-for-seo"),
            $ai4seo_this_setting_name
        ), 261219225);
        #ai4seo_return_error_as_json("Invalid setting value '" . print_r($ai4seo_this_new_setting_value, true) . "' for " . $ai4seo_this_setting_name, 261219225);
        wp_die();
    }

    // update local settings
    $ai4seo_settings[$ai4seo_this_setting_name] = $ai4seo_this_new_setting_value;

    // keep track of recent changes
    $ai4seo_recent_setting_changes[$ai4seo_this_setting_name] = array($ai4seo_this_old_setting_value, $ai4seo_this_new_setting_value);
}


// ___________________________________________________________________________________________ \\
// === UPDATE SETTINGS ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_recent_setting_changes) {
    ai4seo_push_local_setting_changes_to_database();
}


// ___________________________________________________________________________________________ \\
// === SPECIAL POST-SAVE HANDLING ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// are there changes to the AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES setting? perform a full refresh of all posts' SEO coverage
if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES])) {
    ai4seo_refresh_all_posts_seo_coverage();
}

// if AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES or AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES
// is different from the new value, we need to run ai4seo_analyze_plugin_performance()
if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES])
    || isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES])) {
    ai4seo_analyze_plugin_performance();
}


// === INCOGNITO MODE -> SAVE USER ID AS SETTING TOO ================================================================ \\

if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_ENABLE_INCOGNITO_MODE])) {
    if ($ai4seo_recent_setting_changes[AI4SEO_SETTING_ENABLE_INCOGNITO_MODE][1] && function_exists("get_current_user_id")) {
        // Get current user-id
        $ai4seo_current_user_id = get_current_user_id();

        // Save current user-id as setting
        ai4seo_update_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID, $ai4seo_current_user_id);
    } else {
        ai4seo_update_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID, "0");
    }
}


// === ACTIVE ATTACHMENT ATTRIBUTES CHANGED -> REFRESH ALL POSTS SEO COVERAGE ======================================== \\

if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES])) {
    ai4seo_refresh_all_posts_seo_coverage();
}


// === GENERATE METADATA FOR FULLY COVERED ENTRIES OR GENERATE ATTACHMENT ATTRIBUTES FOR FULLY COVERED ENTRIES CHANGED -> ANALYZE PLUGIN PERFORMANCE \\

if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES]) || isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES])) {
    ai4seo_analyze_plugin_performance();
}


// === ENABLED BULK GENERATION POST TYPES  ================================================================================== \\

if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES])) {
    $ai4seo_old_enabled_bulk_generation_post_types = $ai4seo_recent_setting_changes[AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES][0];
    $ai4seo_new_enabled_bulk_generation_post_types = $ai4seo_recent_setting_changes[AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES][1];

    // collect newly enabled post types by comparing the old and new setting
    $ai4seo_just_enabled_post_types = array_diff($ai4seo_new_enabled_bulk_generation_post_types, $ai4seo_old_enabled_bulk_generation_post_types);
    $ai4seo_just_disabled_post_types = array_diff($ai4seo_old_enabled_bulk_generation_post_types, $ai4seo_new_enabled_bulk_generation_post_types);

    if ($ai4seo_new_enabled_bulk_generation_post_types) {
        // excavate new post types
        if (in_array("attachment", $ai4seo_new_enabled_bulk_generation_post_types)) {
            ai4seo_excavate_attachments_with_missing_attributes();
        }

        foreach ($ai4seo_new_enabled_bulk_generation_post_types AS $ai4seo_new_enabled_bulk_generation_post_type) {
            if ($ai4seo_new_enabled_bulk_generation_post_type != "attachment") {
                ai4seo_excavate_post_entries_with_missing_metadata();
                break;
            }
        }

        // try to start the generation of data asap
        ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);

        // set the SEO Autopilot starting time
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME, time());
    }

    // for all just disabled post types, remove all pending post ids and refresh the generation status summary
    if ($ai4seo_just_disabled_post_types) {
        foreach($ai4seo_just_disabled_post_types AS $ai4seo_just_disabled_post_type) {
            ai4seo_remove_all_post_ids_by_post_type_and_generation_status($ai4seo_just_disabled_post_type, AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);
        }

        ai4seo_refresh_all_posts_generation_status_summary();
    }
}


// === BULK GENERATION NEW OR EXISTING TIMESTAMP ============================================================================== \\

if (isset($ai4seo_recent_setting_changes[AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER])) {
    // only if the new and the old one are NOT "new" and "existing", as we allow those to swap without resetting the timestamp
    if ($ai4seo_recent_setting_changes[AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER][0] != "new" && $ai4seo_recent_setting_changes[AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER][0] != "existing") {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME, time());
    }
}


// === SEND PAY-AS-YOU-GO SETTINGS TO ROBHUB ======================================================================== \\

if (isset($ai4seo_post_parameter[AI4SEO_SETTING_PAYG_ENABLED]) && $ai4seo_post_parameter[AI4SEO_SETTING_PAYG_ENABLED]) {
    $ai4seo_sent_pay_as_you_go_settings_response = ai4seo_send_pay_as_you_go_settings();

    if ($ai4seo_sent_pay_as_you_go_settings_response === false) {
        ai4seo_send_json_error(esc_html__("Could not send pay-as-you-go settings to RobHub", "ai-for-seo"), 401217325);
        wp_die();
    }
}