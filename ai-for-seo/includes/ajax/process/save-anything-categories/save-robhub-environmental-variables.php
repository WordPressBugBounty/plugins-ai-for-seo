<?php
/**
 * Required by ai-for-seo-php > save-everything() function. This file is required INSIDE that function.
 * Updates given robhub environmental variables for the robhub communicator from $upcoming_save_anything_updates in bulk
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

$ai4seo_old_api_username = ai4seo_robhub_api()->get_api_username();
$ai4seo_old_api_password = ai4seo_robhub_api()->get_api_password();


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ROBHUB ENVIRONMENTAL VARIABLES UPDATES ================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_recent_robhub_environmental_variable_changes = array();

foreach (ai4seo_robhub_api()::DEFAULT_ENVIRONMENTAL_VARIABLES as $ai4seo_this_robhub_environmental_variable_name => $ai4seo_this_default_robhub_environmental_variable_value) {
    // Check if $upcoming_save_anything_updates-entry exists for this robhub environmental variable
    if (!isset($upcoming_save_anything_updates[$ai4seo_this_robhub_environmental_variable_name])) {
        continue;
    }

    $ai4seo_this_old_robhub_environmental_variable_value = ai4seo_robhub_api()->read_environmental_variable($ai4seo_this_robhub_environmental_variable_name);
    $ai4seo_this_new_robhub_environmental_variable_value = $upcoming_save_anything_updates[$ai4seo_this_robhub_environmental_variable_name];

    // is equal to old value -> ignore it
    if ($ai4seo_this_new_robhub_environmental_variable_value == $ai4seo_this_old_robhub_environmental_variable_value) {
        continue;
    }

    // validate the value
    if (!ai4seo_robhub_api()->validate_environmental_variable_value($ai4seo_this_robhub_environmental_variable_name, $ai4seo_this_new_robhub_environmental_variable_value)) {
        ai4seo_send_ajax_error(sprintf(
            /* translators: %s: Environmental variable name. */
            esc_html__('Invalid robhub environmental variable value for %s', 'ai-for-seo'),
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

if ( ! empty( $ai4seo_recent_robhub_environmental_variable_changes ) && is_array( $ai4seo_recent_robhub_environmental_variable_changes ) ) {
    // Input shape: [ 'name' => [ old_value, new_value ] ] → build [ 'name' => new_value ].
    $ai4seo_robhub_environmental_variables_bulk_updates = array();

    foreach ( $ai4seo_recent_robhub_environmental_variable_changes as $ai4seo_this_robhub_environmental_variable_name => $ai4seo_this_robhub_environmental_variable_values ) {
        if ( is_array( $ai4seo_this_robhub_environmental_variable_values ) && array_key_exists( 1, $ai4seo_this_robhub_environmental_variable_values ) ) {
            $ai4seo_robhub_environmental_variables_bulk_updates[ $ai4seo_this_robhub_environmental_variable_name ] = $ai4seo_this_robhub_environmental_variable_values[1];
        }
    }

    if ( ! empty( $ai4seo_robhub_environmental_variables_bulk_updates ) ) {
        $ai4seo_robhub_bulk_result = ai4seo_robhub_api()->bulk_update_environmental_variables( $ai4seo_robhub_environmental_variables_bulk_updates );

        if ( ! empty( $ai4seo_robhub_bulk_result['invalid_names'] ) ) {
            ai4seo_debug_message(3317171025, 'RobHub: Bulk update skipped unknown names:' . implode( ', ', $ai4seo_robhub_bulk_result['invalid_names'] ));
        }

        if ( ! empty( $ai4seo_robhub_bulk_result['invalid_values'] ) ) {
            ai4seo_debug_message(3417171025, 'RobHub: Bulk update skipped invalid values for:' . implode( ', ', $ai4seo_robhub_bulk_result['invalid_values'] ));
        }

        if ( $ai4seo_robhub_bulk_result['success'] !== true ) {
            ai4seo_debug_message(3517171025, 'RobHub: Bulk update failed to persist changes.');
            ai4seo_send_ajax_error(esc_html__( "Failed to update RobHub environmental variables.", "ai-for-seo" ), 3517171);
            wp_die();
        }
    }
}



// ___________________________________________________________________________________________ \\
// === SPECIAL POST-SAVE HANDLING ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === TEST NEW AUTH DATA / RESTORE OLD ONE ================================================================================= \\

$ai4seo_robhub_api_username_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME;
$ai4seo_robhub_api_password_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD;

if (isset($ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key]) || isset($ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key])) {
    $ai4seo_new_api_username = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_username_key][1] ?? $ai4seo_old_api_username;
    $ai4seo_new_api_password = $ai4seo_recent_robhub_environmental_variable_changes[$ai4seo_robhub_api_password_key][1] ?? $ai4seo_old_api_password;
    $ai4seo_reset_robhub_account = false;

    // inkonsistent data -> maybe one of the pair is empty, we copy the old value to the new one
    if ($ai4seo_new_api_username && !$ai4seo_new_api_password) {
        $ai4seo_new_api_password = $ai4seo_old_api_password;
    } else if (!$ai4seo_new_api_username && $ai4seo_new_api_password) {
        $ai4seo_new_api_username = $ai4seo_old_api_username;
    }
    
    // if we have new username or password, we need to test the new credentials
    if ($ai4seo_new_api_username && $ai4seo_new_api_password) {
        ai4seo_robhub_api()->use_this_credentials($ai4seo_new_api_username, $ai4seo_new_api_password);

        $ai4seo_robhub_api_response = ai4seo_robhub_api()->call("client/changed-api-user",
            array("old-api-username" => $ai4seo_old_api_username,
                "new-api-username" => $ai4seo_new_api_username));
        
        // check if the response is valid
        if (ai4seo_robhub_api()->was_call_successful($ai4seo_robhub_api_response)) {
            ai4seo_robhub_api()->reset_last_account_sync();
            ai4seo_remove_all_notifications();
        } else {
            // we had good auth data before? -> revert changes
            if ($ai4seo_old_api_username && $ai4seo_old_api_password) {
                ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_username_key, $ai4seo_old_api_username);
                ai4seo_robhub_api()->update_environmental_variable($ai4seo_robhub_api_password_key, $ai4seo_old_api_password);
                ai4seo_robhub_api()->use_this_credentials($ai4seo_old_api_username, $ai4seo_old_api_password);
                ai4seo_robhub_api()->reset_last_account_sync();
                ai4seo_remove_all_notifications();
            } else {
                // otherwise we start over with empty credentials
                ai4seo_robhub_api()->invalidate_auth_data(true);
                ai4seo_remove_all_notifications();
            }

            ai4seo_send_ajax_error(esc_html__("Could not verify new credentials.", "ai-for-seo"), 391222324);
        }

    // if we had old username or password, but now we have empty ones, we try to init a free account
    } else if ($ai4seo_old_api_username || $ai4seo_old_api_password) {
        // if the new username or password is empty, we try to init a free account
        ai4seo_robhub_api()->invalidate_auth_data(true);
        ai4seo_remove_all_notifications();
    } else {
        // if we had no username or password before, we do nothing
        // this is the case when the user has not set any credentials before and just saved
    }
}
