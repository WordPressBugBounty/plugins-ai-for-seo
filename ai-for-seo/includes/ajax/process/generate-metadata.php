<?php
/**
 * Called via AJAX.
 * Generates metadata through our RobHub API for a post and returns it as JSON.
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

// set false in production
$ai4seo_debug = false;

// set content type to json
if (!$ai4seo_debug) {
    header("Content-Type: application/json");
    ob_start();
}


// === CHECK PARAMETER: POST ID =========================================================== \\

// get sanitized post id parameter
$ai4seo_post_id = absint($_REQUEST["ai4seo_post_id"] ?? 0);

if ($ai4seo_post_id <= 0) {
    ai4seo_return_error_as_json("Post id is invalid.", 34127323);
}


// === CHECK PARAMETER: CONTENT ========================================================== \\

// get sanitized content parameter
$ai4seo_post_content = sanitize_textarea_field($_REQUEST["ai4seo_content"] ?? "");


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// get sanitized old values parameter
$ai4seo_generation_input_values = ai4seo_deep_sanitize($_REQUEST["ai4seo_generation_input_values"] ?? array());

// Prepare variables for prefixes and suffixes
$ai4seo_metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
$ai4seo_metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);


// ___________________________________________________________________________________________ \\
// === INIT API COMMUNICATOR ================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!ai4seo_robhub_api() instanceof Ai4Seo_RobHubApiCommunicator) {
    ai4seo_return_error_as_json("Could not initialize API communicator. Please contact the plugin developer.", 12127323);
}

// check if credentials are set
if (!ai4seo_robhub_api()->init_credentials()) {
    ai4seo_return_error_as_json("Could not initialize API credentials. Please check your settings or contact the plugin developer.", 13127323);
}

$ai4seo_credits_balance = ai4seo_robhub_api()->get_credits_balance();


// ___________________________________________________________________________________________ \\
// === CHECK/COMPARE OLD VALUES ============================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// if we have fresh input values, lets compare them with the old data. If we have old data, and if we see a difference
// to the current data, then just return the old data. If we do not have old data or if the data is the same, then we
// can continue with the generation process.
if ($ai4seo_generation_input_values) {
    $ai4seo_old_generated_values = ai4seo_read_generated_data_from_post_meta($ai4seo_post_id);

    if ($ai4seo_old_generated_values) {
        $ai4seo_old_generated_values = ai4seo_deep_sanitize($ai4seo_old_generated_values);

        // decode all html special chars
        array_walk($ai4seo_old_generated_values, function(&$value) {
            $value = ai4seo_normalize_text($value);
        });

        foreach ($ai4seo_generation_input_values AS $ai4seo_generation_input_key => $ai4seo_generation_input_value) {
            if (!isset($ai4seo_old_generated_values[$ai4seo_generation_input_key])) {
                continue;
            }

            $ai4seo_generation_input_value = ai4seo_normalize_text($ai4seo_generation_input_value);

            if ($ai4seo_generation_input_value !== $ai4seo_old_generated_values[$ai4seo_generation_input_key]) {
                $ai4seo_ajax_response = array(
                    "generated_data" => $ai4seo_old_generated_values,
                    "credits_consumed" => 0,
                    "new_credits_balance" => $ai4seo_credits_balance,
                );

                wp_send_json_success($ai4seo_ajax_response);
            }
        }
    }
}


// ___________________________________________________________________________________________ \\
// === CHECK POST CONTENT LENGTH - TRY GET POST CONTENT FROM DATABASE AS AN ALTERNATIVE ====== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_post_content_length = (int) mb_strlen($ai4seo_post_content);
$ai4seo_post_content_is_too_short = ($ai4seo_post_content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH);

if ($ai4seo_post_content_is_too_short) {
    // check if we get a larger sample from the database
    $ai4seo_condensed_post_content_from_database = ai4seo_get_condensed_post_content_from_database($ai4seo_post_id);
    $ai4seo_post_content_from_database_length = (int) mb_strlen($ai4seo_condensed_post_content_from_database);

    // use the content from the database if it is larger
    if ($ai4seo_post_content_from_database_length > $ai4seo_post_content_length) {
        $ai4seo_post_content = $ai4seo_condensed_post_content_from_database;
        $ai4seo_post_content_length = $ai4seo_post_content_from_database_length;
        $ai4seo_post_content_is_too_short = ($ai4seo_post_content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH);
    }

    // still too short -> return error
    if ($ai4seo_post_content_is_too_short) {
        if ($ai4seo_post_content_length == 0) {
            ai4seo_return_error_as_json(esc_html__("This entry contains no content.", "ai-for-seo"), 22127323);
        } else  {
            ai4seo_return_error_as_json(
                esc_html__("Your content is too short for the AI to generate optimized metadata.", "ai-for-seo") . "<br>" .
                "<ul><li><strong>" . sprintf(esc_html__("Current length: %s.", "ai-for-seo"), esc_html($ai4seo_post_content_length)) . "</strong>" . "</li>" .
                "<li><strong>" . sprintf(esc_html__("At least %s characters are required.", "ai-for-seo"), AI4SEO_TOO_SHORT_CONTENT_LENGTH) . "</strong></li></ul><br>" .
                esc_html__("Please increase the content length of this entry to enable this feature.", "ai-for-seo") . " " .
                sprintf(__("If you think this is an error, please <a href='%s' target='_blank'>contact us</a>.", "ai-for-seo"), AI4SEO_OFFICIAL_CONTACT_URL),
                54155424,
                __("Insufficient Content Length for AI Optimization", "ai-for-seo"),
                false
            );
        }
    }
} else {
    // condense the post content
    ai4seo_condense_raw_post_content($ai4seo_post_content);
}

$ai4seo_post_content_summary = $ai4seo_post_content;
unset($ai4seo_post_content);

// check if content is too large (should never happen as we already condensed the content)
if (strlen($ai4seo_post_content_summary) > AI4SEO_MAX_TOTAL_CONTENT_SIZE) {
    ai4seo_return_error_as_json("Content is too large.", 361229323);
}


// ___________________________________________________________________________________________ \\
// === CHECK EXISTING CONTENT SUMMARY (COMPARE SIMILARITY) =================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// dont compare if debug is enabled
/*if (!$ai4seo_debug) {
    $ai4seo_existing_post_content_summary = ai4seo_read_post_content_summary_from_post_meta($ai4seo_post_id);

    if ($ai4seo_existing_post_content_summary && ai4seo_are_post_content_summaries_similar($ai4seo_post_content_summary, $ai4seo_existing_post_content_summary)) {
        $ai4seo_existing_generated_data = ai4seo_read_generated_data_from_post_meta($ai4seo_post_id);

        if ($ai4seo_existing_generated_data) {
            $ai4seo_response = array(
                "success" => true,
                "data" => $ai4seo_existing_generated_data,
                "credits-consumed" => 0,
                "new-credits-balance" => $ai4seo_credits_balance,
            );

            ai4seo_return_success_as_json($ai4seo_response);
        }
    }
}*/


// ___________________________________________________________________________________________ \\
// === PREPARE POST CONTENT ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// check for a key phrase
$ai4seo_keyphrase = sanitize_text_field(ai4seo_get_any_third_party_seo_plugin_keyphrase($ai4seo_post_id));


// ___________________________________________________________________________________________ \\
// === EXECUTE API CALL ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_metadata_generation_language = ai4seo_get_posts_language($ai4seo_post_id);

$ai4seo_api_call_parameters = array(
    "input" => $ai4seo_post_content_summary,
    "language" => $ai4seo_metadata_generation_language
);

if ($ai4seo_keyphrase) {
    $ai4seo_api_call_parameters["keyphrase"] = $ai4seo_keyphrase;
}


$ai4seo_results = ai4seo_robhub_api()->call("ai4seo/generate-all-metadata", $ai4seo_api_call_parameters, "POST");


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_results === false) {
    ai4seo_return_error_as_json("Could not execute API call.", 28127323);
}

if (!is_array($ai4seo_results)) {
    ai4seo_return_error_as_json("API call did not return an array.", 29127323);
}

if (empty($ai4seo_results)) {
    ai4seo_return_error_as_json("API call returned an empty array.", 30127323);
}

if (!isset($ai4seo_results["success"])) {
    ai4seo_return_error_as_json("API call did not return a success value", 31127323);
}

if ($ai4seo_results["success"] === false) {
    ai4seo_return_error_as_json($ai4seo_results["message"] . " (API error #{$ai4seo_results["code"]})", 32127323);
}

if ($ai4seo_results["success"] !== true && $ai4seo_results["success"] !== "true") {
    ai4seo_return_error_as_json("API call returned an invalid success value.", 47127323);
}

// check if data is set
if (!isset($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call did not return data.", 48127323);
}

// sanitize data
$ai4seo_results["data"] = wp_kses_post($ai4seo_results["data"]);

if (empty($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call returned an empty data array.", 49127323);
}

if (!ai4seo_is_json($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call returned an invalid data array: " . print_r($ai4seo_results["data"], true), 52127323);
}

$ai4seo_generated_data = json_decode($ai4seo_results["data"], true);

if (!$ai4seo_generated_data) {
    ai4seo_return_error_as_json("API call returned an invalid data array: " . print_r($ai4seo_results["data"], true), 331711823);
}

// check if credits are set
if (!isset($ai4seo_results["credits-consumed"])) {
    ai4seo_return_error_as_json("API call did not return consumed Credits.", 50127323);
}

// sanitize credits
$ai4seo_results["credits-consumed"] = (int) $ai4seo_results["credits-consumed"];

// check if new credits balance is set
if (!isset($ai4seo_results["new-credits-balance"])) {
    ai4seo_return_error_as_json("API call did not return new Credits balance.", 51127323);
}

// sanitize new credits balance
$ai4seo_results["new-credits-balance"] = (int) $ai4seo_results["new-credits-balance"];


// === PREPARE RESPONSE =============================================================================== \\

$ai4seo_new_metadata = array();

// go through each final data entry and use html_entity_decode
foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
    $ai4seo_this_api_identifier = $ai4seo_this_metadata_details["api-identifier"];
    $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_api_identifier] ?? "";

    if (!$ai4seo_this_generated_data_value) {
        continue;
    }

    // Add prefix and suffix
    $ai4seo_this_metadata_prefix = trim(sanitize_text_field($ai4seo_metadata_prefixes[$ai4seo_this_metadata_identifier] ?? ""));
    $ai4seo_this_metadata_suffix = trim(sanitize_text_field($ai4seo_metadata_suffixes[$ai4seo_this_metadata_identifier] ?? ""));
    $ai4seo_this_metadata_value = trim($ai4seo_this_metadata_prefix . " " . $ai4seo_this_generated_data_value . " " . $ai4seo_this_metadata_suffix);

    // Overwrite generated data entry
    $ai4seo_new_metadata[$ai4seo_this_metadata_identifier] = html_entity_decode($ai4seo_this_metadata_value);
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

ai4seo_save_generated_data_to_postmeta($ai4seo_post_id, $ai4seo_new_metadata);


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_post_id, "success", "metadata-manually-generated", $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_ajax_response = array(
    "generated_data" => $ai4seo_new_metadata,
    "credits_consumed" => $ai4seo_results["credits-consumed"],
    "new_credits_balance" => $ai4seo_results["new-credits-balance"],
);

wp_send_json_success($ai4seo_ajax_response);