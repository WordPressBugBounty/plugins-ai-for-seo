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


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// get sanitized old values parameter
$ai4seo_generation_input_values = ai4seo_deep_sanitize($_REQUEST["ai4seo_generation_input_values"] ?? array());

// Prepare variables for prefixes and suffixes
$ai4seo_attachment_attributes_prefixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES);
$ai4seo_attachment_attributes_suffixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES);


// === GET ACTIVATE ATTACHMENT ATTRIBUTES ==================================================== \\

# workaround if we only generate one attribute -> we make sure it's active
if (count($ai4seo_generation_input_values) == 1) {
    $ai4seo_active_attachment_attributes = array_keys(AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS);
} else {
    $ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if (!$ai4seo_active_attachment_attributes) {
        ai4seo_send_json_error(esc_html__("No active attachment attributes found.", "ai-for-seo"), 36124125);
    }
}


// === CHECK ATTACHMENT ======================================================================= \\

// first, let's get the wp_post entry for more checks
$ai4seo_attachment_post = get_post($ai4seo_this_attachment_post_id);

if (!$ai4seo_attachment_post) {
    ai4seo_send_json_error(esc_html__("Media post not found.", "ai-for-seo"), 501013325);
}

// check if it's an attachment
if ($ai4seo_attachment_post->post_type === "attachment") {
    // check url of the attachment
    $ai4seo_attachment_url = wp_get_attachment_url($ai4seo_this_attachment_post_id);
} else {
    $ai4seo_attachment_url = get_the_guid($ai4seo_attachment_post);
}

if (!$ai4seo_attachment_url) {
    ai4seo_send_json_error(esc_html__("Media url not found.", "ai-for-seo"), 241823824);
}

$ai4seo_mime_type = $ai4seo_attachment_post->post_mime_type ?? "";

# try a different way to get the mime type
if (!$ai4seo_mime_type || !in_array($ai4seo_mime_type, $ai4seo_allowed_attachment_mime_types)) {
    $ai4seo_mime_type = ai4seo_get_mime_type_from_url($ai4seo_attachment_url);
}

// check if it's one of the allowed mime types
if (!$ai4seo_mime_type || !in_array($ai4seo_mime_type, $ai4seo_allowed_attachment_mime_types)) {
    ai4seo_send_json_error(sprintf(
        esc_html__("Media mime type is not allowed: %s for %s", "ai-for-seo"),
        $ai4seo_mime_type,
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

$ai4seo_robhub_endpoint = "ai4seo/generate-all-attachment-attributes";


// === CALL ROBHUB API  WITH ATTACHMENT URL ================================================================== \\

if (!$ai4seo_use_base64_image) {
    $ai4seo_robhub_api_call_parameters["attachment_url"] = $ai4seo_attachment_url;

    $ai4seo_results = ai4seo_robhub_api()->call($ai4seo_robhub_endpoint, $ai4seo_robhub_api_call_parameters, "POST");

    if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results)) {
        unset($ai4seo_robhub_api_call_parameters["attachment_url"]);
        $ai4seo_use_base64_image = true;
    }
}


// === CALL ROBHUB API WITH BASE64 ========================================================================== \\

if ($ai4seo_use_base64_image) {
    $ai4seo_results = ai4seo_generate_attachment_attributes_using_base64($ai4seo_attachment_url, $ai4seo_attachment_post->post_mime_type, $ai4seo_robhub_api_call_parameters);
}

if (!ai4seo_robhub_api()->was_call_successful($ai4seo_results ?? false)) {
    #error_log("AI for SEO: Could not generate attachment attributes: " . print_r($ai4seo_results, true));

    ai4seo_send_json_error(sprintf(
        esc_html__("Could not generate media attributes: %s", "ai-for-seo"),
        ($ai4seo_results["message"] ?? "Unknown error!")
    ), 421024824);
}

$ai4seo_generated_data = $ai4seo_results["data"] ?? array();

if (!$ai4seo_generated_data || !is_array($ai4seo_generated_data)) {
    #error_log("AI for SEO: Could not generate attachment attributes: " . print_r($ai4seo_results, true));
    ai4seo_send_json_error(esc_html__("API call did not return valid data.", "ai-for-seo"), 431024824);
}

if (!isset($ai4seo_results["credits-consumed"])) {
    $ai4seo_results["credits-consumed"] = 0;
}

if (!isset($ai4seo_results["new-credits-balance"])) {
    $ai4seo_results["new-credits-balance"] = ai4seo_robhub_api()->get_credits_balance();
}


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === PREPARE RESPONSE ====================================================================== \\

// Remove everything that is not in the active attachment attributes
$ai4seo_generated_data = array_intersect_key($ai4seo_generated_data, array_flip($ai4seo_active_attachment_attributes));

$ai4seo_new_attachment_attributes = array();

// go through each final data entry and use html_entity_decode
foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
    $ai4seo_this_api_identifier = $ai4seo_this_attachment_attribute_details["api-identifier"];
    $ai4seo_this_generated_data_value = $ai4seo_generated_data[$ai4seo_this_api_identifier] ?? "";

    if (!$ai4seo_this_generated_data_value) {
        continue;
    }

    // Add prefix and suffix
    $ai4seo_this_attachment_attribute_prefix = trim(sanitize_text_field($ai4seo_attachment_attributes_prefixes[$ai4seo_this_attachment_attribute_identifier] ?? ""));
    $ai4seo_this_attachment_attribute_suffix = trim(sanitize_text_field($ai4seo_attachment_attributes_suffixes[$ai4seo_this_attachment_attribute_identifier] ?? ""));
    $ai4seo_this_attachment_attribute_value = trim($ai4seo_this_attachment_attribute_prefix . " " . $ai4seo_this_generated_data_value . " " . $ai4seo_this_attachment_attribute_suffix);

    // Overwrite generated data entry
    $ai4seo_new_attachment_attributes[$ai4seo_this_attachment_attribute_identifier] = html_entity_decode($ai4seo_this_attachment_attribute_value);
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

ai4seo_save_generated_data_to_postmeta($ai4seo_this_attachment_post_id, $ai4seo_new_attachment_attributes);

// workaround for alt text: save it as post meta directly
if (isset($ai4seo_new_attachment_attributes["alt-text"])) {
    $ai4seo_this_attachment_alt_text = sanitize_text_field($ai4seo_new_attachment_attributes["alt-text"]);
    update_post_meta($ai4seo_this_attachment_post_id, "_wp_attachment_image_alt", $ai4seo_this_attachment_alt_text);
}


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_this_attachment_post_id, "success", "attachment-attributes-manually-generated", (int) $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_response = array(
    "generated_data" => $ai4seo_new_attachment_attributes,
    "credits_consumed" => (int) $ai4seo_results["credits-consumed"],
    "new_credits_balance" => (int) $ai4seo_results["new-credits-balance"],
);

ai4seo_send_json_success($ai4seo_response);