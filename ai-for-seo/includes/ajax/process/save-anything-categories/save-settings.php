<?php
/**
 * Required by ai-for-seo-php > save-everything() function. This file is required INSIDE that function.
 * Updates given settings from $upcoming_save_anything_updates in bulk
 *
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

# note: $upcoming_save_anything_updates is already sanitized in ai4seo_save_anything()
if (!isset($upcoming_save_anything_updates)) {
    return;
}

global $ai4seo_settings;


// ___________________________________________________________________________________________ \\
// === PRE CHANGE SETTINGS =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Normalize active post type selection (UI shows active types, setting stores disabled ones).
if (isset($upcoming_save_anything_updates[AI4SEO_SETTING_DISABLED_POST_TYPES])) {
    $ai4seo_submitted_active_post_types = $upcoming_save_anything_updates[AI4SEO_SETTING_DISABLED_POST_TYPES];

    if (!is_array($ai4seo_submitted_active_post_types)) {
        $ai4seo_submitted_active_post_types = $ai4seo_submitted_active_post_types ? array($ai4seo_submitted_active_post_types) : array();
    }

    $ai4seo_available_post_types = ai4seo_get_supported_post_types(false);
    $ai4seo_disabled_post_types = array_values(array_diff($ai4seo_available_post_types, $ai4seo_submitted_active_post_types));

    $upcoming_save_anything_updates[AI4SEO_SETTING_DISABLED_POST_TYPES] = $ai4seo_disabled_post_types;
}


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING SETTING UPDATES ======================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_settings = array();
$ai4seo_recent_setting_changes = array();

foreach (AI4SEO_DEFAULT_SETTINGS as $ai4seo_this_setting_name => $ai4seo_this_default_setting_value) {
    // Check if $upcoming_save_anything_updates-entry exists for this setting
    if (!isset($upcoming_save_anything_updates[$ai4seo_this_setting_name])) {
        continue;
    }

    $ai4seo_this_old_setting_value = $ai4seo_settings[$ai4seo_this_setting_name];
    $ai4seo_this_new_setting_value = $upcoming_save_anything_updates[$ai4seo_this_setting_name];

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

// for some settings we need to trigger a posts table analysis after saving the settings
$ai4seo_analysis_trigger_settings = [
    AI4SEO_SETTING_ACTIVE_META_TAGS,
    AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES,
    AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES,
    AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES,
    AI4SEO_SETTING_DISABLED_POST_TYPES,
    AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA,
    AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES,
];

foreach ( $ai4seo_analysis_trigger_settings as $ai4seo_this_setting_key ) {
    if ( isset( $ai4seo_recent_setting_changes[ $ai4seo_this_setting_key ] ) ) {
        ai4seo_try_start_posts_table_analysis( true );
        break;
    }
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

    // for all just disabled post types, remove all pending post ids
    if ($ai4seo_just_disabled_post_types) {
        foreach($ai4seo_just_disabled_post_types AS $ai4seo_just_disabled_post_type) {
            ai4seo_remove_all_post_ids_by_post_type_and_generation_status($ai4seo_just_disabled_post_type, AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);
        }
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

if (isset($upcoming_save_anything_updates[AI4SEO_SETTING_PAYG_ENABLED]) && $upcoming_save_anything_updates[AI4SEO_SETTING_PAYG_ENABLED]) {
    $ai4seo_sent_pay_as_you_go_settings_response = ai4seo_send_pay_as_you_go_settings();

    if ($ai4seo_sent_pay_as_you_go_settings_response === false) {
        ai4seo_send_json_error(esc_html__("Could not send pay-as-you-go settings to RobHub", "ai-for-seo"), 401217325);
        wp_die();
    }
}