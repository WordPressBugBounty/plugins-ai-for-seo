<?php
/**
 * Required by save-everything.php.
 * Updates given environmental variables for this plugin from $ai4seo_post_parameter in bulk
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
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_recent_environmental_variable_changes = array();

foreach (AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES as $ai4seo_this_environmental_variable_name => $ai4seo_this_default_environmental_variable_value) {
    // Check if $ai4seo_post_parameter-entry exists for this environmental variable
    if (!isset($ai4seo_post_parameter[$ai4seo_this_environmental_variable_name])) {
        continue;
    }

    $ai4seo_this_old_environmental_variable_value = ai4seo_read_environmental_variable($ai4seo_this_environmental_variable_name);
    $ai4seo_this_new_environmental_variable_value = $ai4seo_post_parameter[$ai4seo_this_environmental_variable_name];

    // is equal to old value -> ignore it
    if ($ai4seo_this_new_environmental_variable_value == $ai4seo_this_old_environmental_variable_value) {
        continue;
    }

    // validate the value value
    if (!ai4seo_validate_environmental_variable_value($ai4seo_this_environmental_variable_name, $ai4seo_this_new_environmental_variable_value)) {
        ai4seo_return_error_as_json("Invalid environmental variable value for " . $ai4seo_this_environmental_variable_name, 461219225);
        wp_die();
    }

    // keep track of recent changes
    $ai4seo_recent_environmental_variable_changes[$ai4seo_this_environmental_variable_name] = array($ai4seo_this_old_environmental_variable_value, $ai4seo_this_new_environmental_variable_value);
}


// ___________________________________________________________________________________________ \\
// === UPDATE ENVIRONMENTAL VARIABLES ======================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_recent_environmental_variable_changes) {
    # todo: improve with bulk update
    foreach ($ai4seo_recent_environmental_variable_changes as $ai4seo_this_environmental_variable_name => $ai4seo_this_environmental_variable_values) {
        ai4seo_update_environmental_variable($ai4seo_this_environmental_variable_name, $ai4seo_this_environmental_variable_values[1]);
    }
}


// ___________________________________________________________________________________________ \\
// === SPECIAL POST-SAVE HANDLING ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === ENHANCED REPORTING: SEND NEWEST INFO TO ROBHUB ======================================== \\

if (isset($ai4seo_recent_environmental_variable_changes[AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED])) {
    // Accepted
    if ($ai4seo_recent_environmental_variable_changes[AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED][1]) {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME, time());

        // send newest info to robhub
        ai4seo_set_tos_accept_details(true, "accepted enhanced reporting");

        // Revoked
    } else {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME, time());

        // send newest info to robhub
        ai4seo_set_tos_accept_details(false, "revoked enhanced reporting");
    }
}