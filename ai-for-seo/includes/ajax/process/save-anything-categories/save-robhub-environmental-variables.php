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

    // for api_username and api_password -> if it's empty, ignore it
    if ($ai4seo_this_robhub_environmental_variable_name === ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME
        || $ai4seo_this_robhub_environmental_variable_name === ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD) {
        if (empty($ai4seo_this_new_robhub_environmental_variable_value)) {
            continue;
        }
    }

    // is equal to old value -> ignore it
    if ($ai4seo_this_new_robhub_environmental_variable_value == $ai4seo_this_old_robhub_environmental_variable_value) {
        continue;
    }

    // validate the value
    if (!ai4seo_robhub_api()->validate_environmental_variable_value($ai4seo_this_robhub_environmental_variable_name, $ai4seo_this_new_robhub_environmental_variable_value)) {
        ai4seo_return_error_as_json("Invalid robhub environmental variable value for " . $ai4seo_this_robhub_environmental_variable_name, 11419225);
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
    $ai4seo_robhub_api_response = ai4seo_robhub_api()->call("client/credits-balance");

    // Unable to connect to RobHub API anymore -> restore old license key and return error
    if (!isset($ai4seo_robhub_api_response["success"]) || $ai4seo_robhub_api_response["success"] !== true) {
        ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_username_key, $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][0]);
        ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_password_key, $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key][0]);
        ai4seo_return_error_as_json("Could not verify new credentials.", 391222324);
    } else {
        ai4seo_robhub_api()->call("client/changed-api-user",
            array("old-api-username" => $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][0],
                "new-api-username" => $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][1]), "POST");

        // reset last credit balance check, so we can check the balance again
        ai4seo_robhub_api()->reset_last_credit_balance_check();
    }
}