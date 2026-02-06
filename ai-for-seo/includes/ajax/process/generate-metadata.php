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

// === CHECK ROBHUB ACCOUNT =============================================================== \\

$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

if (!$ai4seo_is_robhub_account_synced) {
    ai4seo_send_json_error(esc_html__("Failed to verify your license data. Please check your account settings.", "ai-for-seo"), 121320825);
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


// === CHECK PARAMETER: GENERATION FIELDS ==================================================== \\

$ai4seo_generation_fields = ai4seo_deep_sanitize($_REQUEST["ai4seo_generation_fields"] ?? array());

if (!is_array($ai4seo_generation_fields) || count($ai4seo_generation_fields) === 0) {
    ai4seo_send_json_error(esc_html__("Generation fields are invalid.", "ai-for-seo"), 1613301025);
}


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// get sanitized old values parameter
$ai4seo_old_input_values = ai4seo_deep_sanitize($_REQUEST["ai4seo_old_input_values"] ?? array());


// === PREPARE ADDITIONAL DETAILS ========================================================= \\

$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

if (!$ai4seo_active_meta_tags) {
    ai4seo_send_json_error(esc_html__("No active meta tags found.", "ai-for-seo"), 3711221025);
}


// ___________________________________________________________________________________________ \\
// === PREPARE POST CONTENT ================================================================== \\
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
if (ai4seo_mb_strlen($ai4seo_post_content) > AI4SEO_MAX_TOTAL_CONTENT_SIZE) {
    ai4seo_send_json_error(esc_html__("Content is too large.", "ai-for-seo"), 361229323);
}


$ai4seo_post_content = sanitize_text_field($ai4seo_post_content);

// check for a key phrase
$ai4seo_keyphrase = sanitize_text_field(ai4seo_get_any_third_party_seo_plugin_keyphrase($ai4seo_post_id));


// ___________________________________________________________________________________________ \\
// === EXECUTE API CALL ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_metadata_generation_language = ai4seo_get_posts_language($ai4seo_post_id);

$ai4seo_robhub_api_call_parameters = array(
    "content" => $ai4seo_post_content,
    "language" => $ai4seo_metadata_generation_language
);

if ($ai4seo_keyphrase) {
    $ai4seo_robhub_api_call_parameters["keyphrase"] = $ai4seo_keyphrase;
}

$ai4seo_robhub_api_call_parameters["trigger"] = "manual";
$ai4seo_robhub_api_call_parameters["context"] = ai4seo_get_website_context();

// collect and build field instructions
$ai4seo_field_instructions = array();
$ai4seo_metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
$ai4seo_metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);
$ai4seo_placeholder_replacements = ai4seo_get_metadata_placeholder_replacements($ai4seo_post_id);

foreach ($ai4seo_active_meta_tags AS $ai4seo_this_active_meta_tag) {
    $ai4seo_this_to_generate = in_array($ai4seo_this_active_meta_tag, $ai4seo_generation_fields);
    $ai4seo_this_old_value = $ai4seo_old_input_values[$ai4seo_this_active_meta_tag] ?? "";
    $ai4seo_this_prefix = $ai4seo_metadata_prefixes[$ai4seo_this_active_meta_tag] ?? "";
    $ai4seo_this_suffix = $ai4seo_metadata_suffixes[$ai4seo_this_active_meta_tag] ?? "";

    if (!$ai4seo_this_to_generate && !$ai4seo_this_old_value) {
        continue;
    }

    $ai4seo_this_prefix = ai4seo_replace_text_placeholders($ai4seo_this_prefix, $ai4seo_placeholder_replacements);
    $ai4seo_this_suffix = ai4seo_replace_text_placeholders($ai4seo_this_suffix, $ai4seo_placeholder_replacements);

    $ai4seo_field_instructions[$ai4seo_this_active_meta_tag] = array(
        "generate" => $ai4seo_this_to_generate,
        "old_value" => $ai4seo_this_old_value,
        "prefix" => $ai4seo_this_prefix,
        "suffix" => $ai4seo_this_suffix,
    );
}

$ai4seo_robhub_api_call_parameters["approximate_cost"] = ai4seo_calculate_metadata_credits_cost_per_post($ai4seo_generation_fields);
$ai4seo_robhub_api_call_parameters["field_instructions"] = $ai4seo_field_instructions;

$ai4seo_results = ai4seo_robhub_api()->call("ai4seo/generate-all-metadata", $ai4seo_robhub_api_call_parameters);


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results)) {
    if (isset($ai4seo_results["message"]) && $ai4seo_results["message"] && isset($ai4seo_results["code"]) && $ai4seo_results["code"]) {
        ai4seo_send_json_error(esc_html($ai4seo_results["message"]), $ai4seo_results["code"]);
    } else {
        ai4seo_send_json_error(esc_html__("Could not execute API call.", "ai-for-seo"), 28127323);
    }
}

$ai4seo_generated_data = $ai4seo_results["data"] ?? "";

// check if data is set
if (!$ai4seo_generated_data || !is_array($ai4seo_generated_data)) {
    ai4seo_send_json_error(esc_html__("API call did not return valid data.", "ai-for-seo"), 48127323);
}


// === PREPARE RESPONSE =============================================================================== \\

$ai4seo_new_metadata = array();

// go through each final data entry and use html_entity_decode
foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
    $ai4seo_this_api_identifier = $ai4seo_this_metadata_details["api-identifier"];

    if (isset($ai4seo_generated_data[$ai4seo_this_metadata_identifier]) && $ai4seo_generated_data[$ai4seo_this_metadata_identifier]) {
        $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_metadata_identifier];
    } else if ($ai4seo_this_api_identifier && isset($ai4seo_generated_data[$ai4seo_this_api_identifier]) && $ai4seo_generated_data[$ai4seo_this_api_identifier]) {
        $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_api_identifier];
    } else {
        continue;
    }

    if (!in_array($ai4seo_this_metadata_identifier, $ai4seo_active_meta_tags)) {
        continue;
    }

    // Overwrite generated data entry
    $ai4seo_new_metadata[$ai4seo_this_metadata_identifier] = html_entity_decode($ai4seo_this_generated_data_value);
}

if (!$ai4seo_new_metadata) {
    ai4seo_send_json_error(esc_html__("No metadata was generated.", "ai-for-seo"), 4111221025);
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

ai4seo_save_generated_data_to_postmeta($ai4seo_post_id, $ai4seo_new_metadata);


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_post_id, "success", "metadata-manually-generated", (int) $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_ajax_response = array(
    "generated_data" => $ai4seo_new_metadata,
    "credits_consumed" => (int) ($ai4seo_results["credits-consumed"] ?? 0),
    "new_credits_balance" => (int) ($ai4seo_results["new-credits-balance"] ?? 0),
);

ai4seo_send_json_success($ai4seo_ajax_response);