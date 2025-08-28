<?php
/**
 * Required by save-everything.php.
 * Updates given robhub environmental variables for the robhub communicator from $ai4seo_post_parameter in bulk
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


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ROBHUB ENVIRONMENTAL VARIABLES UPDATES ================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_recent_robhub_environmental_variable_changes = array();

foreach (ai4seo_robhub_api()::DEFAULT_ENVIRONMENTAL_VARIABLES as $ai4seo_this_robhub_environmental_variable_name => $ai4seo_this_default_robhub_environmental_variable_value) {
    // Check if $ai4seo_post_parameter-entry exists for this robhub environmental variable
    if (!isset($ai4seo_post_parameter[$ai4seo_this_robhub_environmental_variable_name])) {
        continue;
    }

    $ai4seo_this_old_robhub_environmental_variable_value = ai4seo_robhub_api()->read_environmental_variable($ai4seo_this_robhub_environmental_variable_name);
    $ai4seo_this_new_robhub_environmental_variable_value = $ai4seo_post_parameter[$ai4seo_this_robhub_environmental_variable_name];

    // is equal to old value -> ignore it
    if ($ai4seo_this_new_robhub_environmental_variable_value == $ai4seo_this_old_robhub_environmental_variable_value) {
        continue;
    }

    // validate the value
    if (!ai4seo_robhub_api()->validate_environmental_variable_value($ai4seo_this_robhub_environmental_variable_name, $ai4seo_this_new_robhub_environmental_variable_value)) {
        ai4seo_send_json_error(sprintf(
            esc_html__("Invalid robhub environmental variable value for %s", "ai-for-seo"),
            $ai4seo_this_robhub_environmental_variable_name
        ), 11419225);
        #ai4seo_return_error_as_json("Invalid robhub environmental variable value '$ai4seo_this_new_robhub_environmental_variable_value' for " . $ai4seo_this_robhub_environmental_variable_name, 11419225);
        wp_die();
    }

    // keep track of recent changes
    $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_this_robhub_environmental_variable_name] = array($ai4seo_this_old_robhub_environmental_variable_value, $ai4seo_this_new_robhub_environmental_variable_value);
}


// ___________________________________________________________________________________________ \\
// === UPDATE ENVIRONMENTAL VARIABLES ======================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_recent_robhub_environmental_variable_changes) {
    # todo: improve with bulk update
    foreach ($ai4seo_recent_robhub_environmental_variable_changes as $ai4seo_this_robhub_environmental_variable_name => $ai4seo_this_robhub_environmental_variable_values) {
        ai4seo_robhub_api()->update_environmental_variable($ai4seo_this_robhub_environmental_variable_name, $ai4seo_this_robhub_environmental_variable_values[1]);
    }
}


// ___________________________________________________________________________________________ \\
// === SPECIAL POST-SAVE HANDLING ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === TEST NEW AUTH DATA / RESTORE OLD ONE ================================================================================= \\

$ai4seo_robhub_api_username_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME;
$ai4seo_robhub_api_password_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD;

if (isset($ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key]) || isset($ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key])) {
    $ai4seo_old_api_username = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][0] ?? "";
    $ai4seo_old_api_password = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key][0] ?? "";
    $ai4seo_new_api_username = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][1] ?? "";
    $ai4seo_new_api_password = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key][1] ?? "";
    $ai4seo_reset_robhub_account = false;

    // if we have new username or password, we need to test the new credentials
    if ($ai4seo_new_api_username && $ai4seo_new_api_password) {
        $ai4seo_robhub_api_response = ai4seo_robhub_api()->call("client/changed-api-user",
            array("old-api-username" => $ai4seo_old_api_username,
                "new-api-username" => $ai4seo_new_api_username), "POST");

        // check if the response is valid
        if (ai4seo_robhub_api()->was_call_successful($ai4seo_robhub_api_response)) {
            $ai4seo_reset_robhub_account = true;
        } else {
            ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_username_key, $ai4seo_old_api_username);
            ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_password_key, $ai4seo_old_api_password);
            ai4seo_send_json_error(esc_html__("Could not verify new credentials.", "ai-for-seo"), 391222324);
        }

    // if we had old username or password, but now we have empty ones, we try to init a free account
    } else if ($ai4seo_old_api_username || $ai4seo_old_api_password) {
        // if the new username or password is empty, we try to init a free account
        ai4seo_robhub_api()->init_free_account();
        $ai4seo_reset_robhub_account = true;
    } else {
        // if we had no username or password before, we do nothing
        // this is the case when the user has not set any credentials before and jut saved
    }

    // reset some variables and mechanics to adapt to the new account
    if ($ai4seo_reset_robhub_account) {
        // reset last account sync, so we can sync its details again
        ai4seo_robhub_api()->reset_last_account_sync();
        ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false);

        // remove all notifications, as we may have new ones with this new account
        ai4seo_remove_all_notifications();
    }
}