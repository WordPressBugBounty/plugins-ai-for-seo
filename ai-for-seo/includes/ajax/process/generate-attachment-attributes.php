<?php
/**
 * Called via AJAX.
 * Generates attachment attributes through our RobHub API for a post and returns it as JSON.
 *
 * @since 1.2
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

global $ai4seo_allowed_attachment_mime_types;

// set false in production
$ai4seo_debug = false;

// set content type to json
if (!$ai4seo_debug) {
    header("Content-Type: application/json");
    ob_start();
}


// ___________________________________________________________________________________________ \\
// === CHECK PARAMETER ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === CHECK ROBHUB ACCOUNT =============================================================== \\

$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

if (!$ai4seo_is_robhub_account_synced) {
    ai4seo_send_json_error(esc_html__("Failed to verify your license data. Please check your account settings.", "ai-for-seo"), 131320825);
}


// === CHECK PARAMETER: ATTACHMENT POST ID =========================================================== \\

// get sanitized post id parameter
$ai4seo_this_attachment_post_id = absint($_REQUEST["ai4seo_post_id"] ?? 0);

if ($ai4seo_this_attachment_post_id <= 0) {
    ai4seo_send_json_error(esc_html__("Media post id is invalid.", "ai-for-seo"), 211823824);
}


// === CHECK PARAMETER: GENERATION FIELDS ==================================================== \\

$ai4seo_generation_fields = ai4seo_deep_sanitize($_REQUEST["ai4seo_generation_fields"] ?? array());

if (!is_array($ai4seo_generation_fields) || count($ai4seo_generation_fields) === 0) {
    ai4seo_send_json_error(esc_html__("Generation fields are invalid.", "ai-for-seo"), 1713301026);
}


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// get sanitized old values parameter
$ai4seo_old_input_values = ai4seo_deep_sanitize($_REQUEST["ai4seo_old_input_values"] ?? array());

// Prepare variables for prefixes and suffixes
$ai4seo_attachment_attributes_prefixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES);
$ai4seo_attachment_attributes_suffixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES);
$ai4seo_attachment_placeholder_replacements = ai4seo_get_attachment_placeholder_replacements($ai4seo_this_attachment_post_id);


// === GET ACTIVATE ATTACHMENT ATTRIBUTES ==================================================== \\

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

if (!$ai4seo_active_attachment_attributes) {
    ai4seo_send_json_error(esc_html__("No active attachment attributes found.", "ai-for-seo"), 36124125);
}


// === CHECK ATTACHMENT ======================================================================= \\

// first, let's get the wp_post entry for more checks
$ai4seo_attachment_post = get_post($ai4seo_this_attachment_post_id);

if (!$ai4seo_attachment_post) {
    ai4seo_send_json_error(esc_html__("Media post not found.", "ai-for-seo"), 501013325);
}

// check if it's an attachment
$ai4seo_attachment_url = ai4seo_get_attachment_url($ai4seo_this_attachment_post_id);

if (!$ai4seo_attachment_url) {
    ai4seo_send_json_error(esc_html__("Media url not found.", "ai-for-seo"), 241823824);
}

$ai4seo_attachment_mime_type = ai4seo_get_attachment_post_mime_type($ai4seo_this_attachment_post_id);

// check if it's one of the allowed mime types
if (!$ai4seo_attachment_mime_type || !in_array($ai4seo_attachment_mime_type, $ai4seo_allowed_attachment_mime_types)) {
    ai4seo_send_json_error(sprintf(
        esc_html__("Media mime type is not allowed: %s for %s", "ai-for-seo"),
        $ai4seo_attachment_mime_type,
        $ai4seo_attachment_url
    ), 251823824);
}

// Determine whether to use base64 or URL based on user setting
$ai4seo_use_base64_image = ai4seo_should_use_base64_image($ai4seo_attachment_url);


// ___________________________________________________________________________________________ \\
// === EXECUTE CALL ========================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_attachment_attributes_generation_language = ai4seo_get_attachments_language($ai4seo_this_attachment_post_id);

$ai4seo_robhub_api_call_parameters = array(
    "language" => $ai4seo_attachment_attributes_generation_language
);

$ai4seo_robhub_api_call_parameters["trigger"] = "manual";
$ai4seo_robhub_api_call_parameters["context"] = ai4seo_get_website_context();

// collect and build field instructions
$ai4seo_field_instructions = array();

foreach ($ai4seo_active_attachment_attributes AS $ai4seo_this_active_attachment_attribute) {
    $ai4seo_this_to_generate = in_array($ai4seo_this_active_attachment_attribute, $ai4seo_generation_fields);
    $ai4seo_this_old_value = $ai4seo_old_input_values[$ai4seo_this_active_attachment_attribute] ?? "";
    $ai4seo_this_prefix = $ai4seo_attachment_attributes_prefixes[$ai4seo_this_active_attachment_attribute] ?? "";
    $ai4seo_this_suffix = $ai4seo_attachment_attributes_suffixes[$ai4seo_this_active_attachment_attribute] ?? "";

    if (!$ai4seo_this_to_generate && !$ai4seo_this_old_value) {
        continue;
    }

    $ai4seo_this_prefix = ai4seo_replace_text_placeholders($ai4seo_this_prefix, $ai4seo_attachment_placeholder_replacements);
    $ai4seo_this_suffix = ai4seo_replace_text_placeholders($ai4seo_this_suffix, $ai4seo_attachment_placeholder_replacements);

    $ai4seo_field_instructions[$ai4seo_this_active_attachment_attribute] = array(
        "generate" => $ai4seo_this_to_generate,
        "old_value" => $ai4seo_this_old_value,
        "prefix" => $ai4seo_this_prefix,
        "suffix" => $ai4seo_this_suffix,
    );
}

$ai4seo_robhub_api_call_parameters["approximate_cost"] = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post($ai4seo_generation_fields);
$ai4seo_robhub_api_call_parameters["field_instructions"] = $ai4seo_field_instructions;


// === CALL ROBHUB API  WITH ATTACHMENT URL ================================================================== \\

if (!$ai4seo_use_base64_image) {
    $ai4seo_robhub_api_call_parameters["attachment_url"] = $ai4seo_attachment_url;

    $ai4seo_results = ai4seo_robhub_api()->call("ai4seo/generate-all-attachment-attributes", $ai4seo_robhub_api_call_parameters);

    if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results) && ai4seo_robhub_api()->is_error_post_related($ai4seo_results)) {
        unset($ai4seo_robhub_api_call_parameters["attachment_url"]);
        $ai4seo_use_base64_image = true;
    }
}


// === CALL ROBHUB API WITH BASE64 ========================================================================== \\

if ($ai4seo_use_base64_image) {
    $ai4seo_results = ai4seo_generate_attachment_attributes_using_base64($ai4seo_attachment_url, $ai4seo_attachment_mime_type, $ai4seo_robhub_api_call_parameters);
}

if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results ?? false)) {
    if (isset($ai4seo_results["message"]) && $ai4seo_results["message"] && isset($ai4seo_results["code"]) && $ai4seo_results["code"]) {
        ai4seo_send_json_error(esc_html($ai4seo_results["message"]), $ai4seo_results["code"]);
    } else {
        ai4seo_send_json_error(esc_html__("Could not execute API call.", "ai-for-seo"), 28127323);
    }
}

$ai4seo_generated_data = $ai4seo_results["data"] ?? array();

if (!$ai4seo_generated_data || !is_array($ai4seo_generated_data)) {
    #error_log("AI for SEO: Could not generate attachment attributes: " . print_r($ai4seo_results, true));
    ai4seo_send_json_error(esc_html__("API call did not return valid data.", "ai-for-seo"), 431024824);
}


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === PREPARE RESPONSE ====================================================================== \\

$ai4seo_new_attachment_attributes = array();

// go through each final data entry and use html_entity_decode
foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
    $ai4seo_this_api_identifier = $ai4seo_this_attachment_attribute_details["api-identifier"];
    $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_api_identifier] ?? "";

    if (!$ai4seo_this_generated_data_value) {
        continue;
    }

    if (!in_array($ai4seo_this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes)) {
        continue;
    }

    // Add prefix and suffix
    $ai4seo_this_attachment_attribute_prefix = trim(sanitize_text_field($ai4seo_attachment_attributes_prefixes[$ai4seo_this_attachment_attribute_identifier] ?? ""));
    $ai4seo_this_attachment_attribute_suffix = trim(sanitize_text_field($ai4seo_attachment_attributes_suffixes[$ai4seo_this_attachment_attribute_identifier] ?? ""));

    $ai4seo_this_attachment_attribute_prefix = ai4seo_replace_text_placeholders($ai4seo_this_attachment_attribute_prefix, $ai4seo_attachment_placeholder_replacements);
    $ai4seo_this_attachment_attribute_suffix = ai4seo_replace_text_placeholders($ai4seo_this_attachment_attribute_suffix, $ai4seo_attachment_placeholder_replacements);

    $ai4seo_this_attachment_attribute_value = trim($ai4seo_this_attachment_attribute_prefix . " " . $ai4seo_this_generated_data_value . " " . $ai4seo_this_attachment_attribute_suffix);

    // Overwrite generated data entry
    $ai4seo_new_attachment_attributes[$ai4seo_this_attachment_attribute_identifier] = html_entity_decode($ai4seo_this_attachment_attribute_value);
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

$ai4seo_this_success = ai4seo_save_generated_data_to_postmeta($ai4seo_this_attachment_post_id, $ai4seo_new_attachment_attributes);

if (!$ai4seo_this_success) {
    #error_log("AI for SEO: Could not save media attributes: " . print_r($ai4seo_new_attachment_attributes, true));
    ai4seo_send_json_error(esc_html__("Could not save media attributes.", "ai-for-seo"), 3218161025);
}

// workaround for alt text: save it as post meta directly
if (isset($ai4seo_new_attachment_attributes["alt-text"])) {
    $ai4seo_this_attachment_alt_text = sanitize_text_field($ai4seo_new_attachment_attributes["alt-text"]);
    $ai4seo_this_success = ai4seo_update_post_meta($ai4seo_this_attachment_post_id, "_wp_attachment_image_alt", $ai4seo_this_attachment_alt_text);

    if (!$ai4seo_this_success) {
        #error_log("AI for SEO: Could not save media alt text: " . print_r($ai4seo_new_attachment_attributes, true));
        ai4seo_send_json_error(esc_html__("Could not save media alt text.", "ai-for-seo"), 3318161025);
    }
}


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_this_attachment_post_id, "success", "attachment-attributes-manually-generated", (int) $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_response = array(
    "generated_data" => $ai4seo_new_attachment_attributes,
    "credits_consumed" => (int) ($ai4seo_results["credits-consumed"] ?? 0),
    "new_credits_balance" => (int) ($ai4seo_results["new-credits-balance"] ?? 0),
);

ai4seo_send_json_success($ai4seo_response);