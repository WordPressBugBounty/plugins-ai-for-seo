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
    ai4seo_send_json_error(esc_html__("Post id is invalid.", "ai-for-seo"), 34127323);
}


// === CHECK PARAMETER: CONTENT ========================================================== \\

// get sanitized content parameter
$ai4seo_post_content = sanitize_textarea_field($_REQUEST["ai4seo_content"] ?? "");


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// get sanitized old values parameter
$ai4seo_generation_input_values = ai4seo_deep_sanitize($_REQUEST["ai4seo_generation_input_values"] ?? array());


// ___________________________________________________________________________________________ \\
// === CHECK POST CONTENT LENGTH - TRY GET POST CONTENT FROM DATABASE AS AN ALTERNATIVE ====== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_post_content) {
    ai4seo_condense_raw_post_content($ai4seo_post_content);
}

// we do not have post content yet, so we try to get it from the database
if (!$ai4seo_post_content) {
    $ai4seo_post_content = ai4seo_get_condensed_post_content_from_database($ai4seo_post_id);
}

ai4seo_add_post_context($ai4seo_post_id, $ai4seo_post_content);

// check if content is too large (should never happen as we already condensed the content)
if (strlen($ai4seo_post_content) > AI4SEO_MAX_TOTAL_CONTENT_SIZE) {
    ai4seo_send_json_error(esc_html__("Content is too large.", "ai-for-seo"), 361229323);
}


// ___________________________________________________________________________________________ \\
// === PREPARE POST CONTENT ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// check for a key phrase
$ai4seo_keyphrase = sanitize_text_field(ai4seo_get_any_third_party_seo_plugin_keyphrase($ai4seo_post_id));

$ai4seo_post_content = sanitize_text_field($ai4seo_post_content);


// ___________________________________________________________________________________________ \\
// === EXECUTE API CALL ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_metadata_generation_language = ai4seo_get_posts_language($ai4seo_post_id);

$ai4seo_api_call_parameters = array(
    "input" => $ai4seo_post_content,
    "language" => $ai4seo_metadata_generation_language
);

if ($ai4seo_keyphrase) {
    $ai4seo_api_call_parameters["keyphrase"] = $ai4seo_keyphrase;
}


$ai4seo_results = ai4seo_robhub_api()->call("ai4seo/generate-all-metadata", $ai4seo_api_call_parameters, "POST");


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results)) {
    ai4seo_send_json_error(esc_html__("Could not execute API call.", "ai-for-seo"), 28127323);
}

// check if data is set
if (!isset($ai4seo_results["data"]) || !is_array($ai4seo_results["data"]) || !$ai4seo_results["data"]) {
    ai4seo_send_json_error(esc_html__("API call did not return data.", "ai-for-seo"), 48127323);
}

$ai4seo_generated_data = $ai4seo_results["data"];

if (!isset($ai4seo_results["credits-consumed"])) {
    $ai4seo_results["credits-consumed"] = 0;
}

if (!isset($ai4seo_results["new-credits-balance"])) {
    $ai4seo_results["new-credits-balance"] = ai4seo_robhub_api()->get_credits_balance();
}


// === PREPARE RESPONSE =============================================================================== \\

$ai4seo_new_metadata = array();

// go through each final data entry and use html_entity_decode
foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
    $ai4seo_this_api_identifier = $ai4seo_this_metadata_details["api-identifier"];
    $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_api_identifier] ?? "";

    if (!$ai4seo_this_generated_data_value) {
        continue;
    }

    // Overwrite generated data entry
    $ai4seo_new_metadata[$ai4seo_this_metadata_identifier] = html_entity_decode($ai4seo_this_generated_data_value);
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

ai4seo_save_generated_data_to_postmeta($ai4seo_post_id, $ai4seo_new_metadata);


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_post_id, "success", "metadata-manually-generated", (int) $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_ajax_response = array(
    "generated_data" => $ai4seo_new_metadata,
    "credits_consumed" => (int) $ai4seo_results["credits-consumed"],
    "new_credits_balance" => (int) $ai4seo_results["new-credits-balance"],
);

ai4seo_send_json_success($ai4seo_ajax_response);