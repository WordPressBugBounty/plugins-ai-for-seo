<?php
/**
 * Required by ai-for-seo-php > save-everything() function. This file is required INSIDE that function.
 * Updates given environmental variables for this plugin from $upcoming_save_anything_updates in bulk
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


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_recent_environmental_variable_changes = array();

foreach (AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES as $ai4seo_this_environmental_variable_name => $ai4seo_this_default_environmental_variable_value) {
    // Check if $upcoming_save_anything_updates-entry exists for this environmental variable
    if (!isset($upcoming_save_anything_updates[$ai4seo_this_environmental_variable_name])) {
        continue;
    }

    $ai4seo_this_old_environmental_variable_value = ai4seo_read_environmental_variable($ai4seo_this_environmental_variable_name);
    $ai4seo_this_new_environmental_variable_value = $upcoming_save_anything_updates[$ai4seo_this_environmental_variable_name];

    // Special handling for datetime-local to timestamp conversion
    if ($ai4seo_this_environmental_variable_name === AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME) {
        // Check if the value is in datetime-local format (contains 'T' and dashes)
        if (is_string($ai4seo_this_new_environmental_variable_value) && 
            strpos($ai4seo_this_new_environmental_variable_value, 'T') !== false && 
            strpos($ai4seo_this_new_environmental_variable_value, '-') !== false) {

            // Convert datetime-local to timestamp
            $ai4seo_this_new_environmental_variable_value = ai4seo_convert_datetime_local_to_timestamp($ai4seo_this_new_environmental_variable_value);
        }
    }

    // is equal to old value -> ignore it
    if ($ai4seo_this_new_environmental_variable_value == $ai4seo_this_old_environmental_variable_value) {
        continue;
    }

    // validate the value value
    if (!ai4seo_validate_environmental_variable_value($ai4seo_this_environmental_variable_name, $ai4seo_this_new_environmental_variable_value)) {
        ai4seo_send_json_error(sprintf(
            esc_html__("Invalid environmental variable value for %s", "ai-for-seo"),
            $ai4seo_this_environmental_variable_name
        ), 461219225);
        wp_die();
    }

    // keep track of recent changes
    $ai4seo_recent_environmental_variable_changes[$ai4seo_this_environmental_variable_name] = array($ai4seo_this_old_environmental_variable_value, $ai4seo_this_new_environmental_variable_value);
}


// ___________________________________________________________________________________________ \\
// === UPDATE ENVIRONMENTAL VARIABLES ======================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ( ! empty( $ai4seo_recent_environmental_variable_changes ) && is_array( $ai4seo_recent_environmental_variable_changes ) ) {
    // Build bulk updates: expected input shape is [ 'name' => [ old_value, new_value ] ].
    $ai4seo_bulk_updates = array();

    foreach ( $ai4seo_recent_environmental_variable_changes as $ai4seo_this_environmental_variable_name => $ai4seo_this_environmental_variable_values ) {
        if ( is_array( $ai4seo_this_environmental_variable_values ) && array_key_exists( 1, $ai4seo_this_environmental_variable_values ) ) {
            $ai4seo_bulk_updates[ $ai4seo_this_environmental_variable_name ] = $ai4seo_this_environmental_variable_values[1];
        }
    }

    if ( ! empty( $ai4seo_bulk_updates ) ) {
        $ai4seo_bulk_result = ai4seo_bulk_update_environmental_variables( $ai4seo_bulk_updates );

        // Optional debug logging for diagnostics.
        if ( ! empty( $ai4seo_bulk_result['invalid_names'] ) ) {
            error_log( 'AI4SEO: Bulk update skipped unknown names: ' . implode( ', ', $ai4seo_bulk_result['invalid_names'] ) . ' #3017171025' );
        }

        if ( ! empty( $ai4seo_bulk_result['invalid_values'] ) ) {
            error_log( 'AI4SEO: Bulk update skipped invalid values for: ' . implode( ', ', $ai4seo_bulk_result['invalid_values'] ) . ' #3117171025' );
        }

        if ( $ai4seo_bulk_result['success'] !== true ) {
            error_log( 'AI4SEO: Bulk update failed to persist changes. #3217171025' );
            ai4seo_send_json_error(esc_html__( "Failed to update environmental variables.", "ai-for-seo" ), 3217171);
            wp_die();
        }
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