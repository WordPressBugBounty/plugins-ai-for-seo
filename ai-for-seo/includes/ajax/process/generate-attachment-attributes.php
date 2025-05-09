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
// === INIT API COMMUNICATOR ================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!ai4seo_robhub_api() instanceof Ai4Seo_RobHubApiCommunicator) {
    ai4seo_return_error_as_json("Could not initialize API communicator. Please contact the plugin developer.", 221823824);
}

// check if credentials are set
if (!ai4seo_robhub_api()->init_credentials()) {
    ai4seo_return_error_as_json("Could not initialize API credentials. Please check your settings or contact the plugin developer.", 231823824);
}

$ai4seo_credits_balance = ai4seo_robhub_api()->get_credits_balance();


// ___________________________________________________________________________________________ \\
// === CHECK PARAMETER ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === CHECK PARAMETER: ATTACHMENT POST ID =========================================================== \\

// get sanitized post id parameter
$ai4seo_this_attachment_post_id = absint($_REQUEST["ai4seo_post_id"] ?? 0);

if ($ai4seo_this_attachment_post_id <= 0) {
    ai4seo_return_error_as_json("Media post id is invalid.", 211823824);
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
        ai4seo_return_error_as_json("No active attachment attributes found.", 36124125);
    }
}


// === CHECK ATTACHMENT ======================================================================= \\

$ai4seo_use_base64_image = false;

// first, let's get the wp_post entry for more checks
$ai4seo_this_attachment_post = get_post($ai4seo_this_attachment_post_id);

if (!$ai4seo_this_attachment_post) {
    ai4seo_return_error_as_json("Media post not found.", 501013325);
}

// check if it's an attachment
if ($ai4seo_this_attachment_post->post_type === "attachment") {
    // check url of the attachment
    $ai4seo_this_attachment_url = wp_get_attachment_url($ai4seo_this_attachment_post_id);
} else {
    $ai4seo_this_attachment_url = get_the_guid($ai4seo_this_attachment_post);
}

if (!$ai4seo_this_attachment_url) {
    ai4seo_return_error_as_json("Media url not found.", 241823824);
}

$ai4seo_this_mime_type = $ai4seo_this_attachment_post->post_mime_type ?? "";

# try a different way to get the mime type
if (!$ai4seo_this_mime_type || !in_array($ai4seo_this_mime_type, $ai4seo_allowed_attachment_mime_types)) {
    $ai4seo_this_mime_type = ai4seo_get_mime_type_from_url($ai4seo_this_attachment_url);
}

// check if it's one of the allowed mime types
if (!$ai4seo_this_mime_type || !in_array($ai4seo_this_mime_type, $ai4seo_allowed_attachment_mime_types)) {
    ai4seo_return_error_as_json("Media mime type is not allowed: " . $ai4seo_this_mime_type, 251823824);
}

// check if the url is valid -> if not we will try to use the image as base64
if (!filter_var($ai4seo_this_attachment_url, FILTER_VALIDATE_URL)) {
    $ai4seo_use_base64_image = true;
}

if (ai4seo_robhub_api()->are_we_on_a_localhost_system()) {
    $ai4seo_use_base64_image = true;
}

if (!$ai4seo_use_base64_image) {
    // check if the attachment url is accessible
    $ai4seo_this_attachment_url_headers = get_headers($ai4seo_this_attachment_url);

    if (!$ai4seo_this_attachment_url_headers || !is_array($ai4seo_this_attachment_url_headers) || !isset($ai4seo_this_attachment_url_headers[0])) {
        $ai4seo_use_base64_image = true;
    }

    if (strpos($ai4seo_this_attachment_url_headers[0], "200") === false) {
        $ai4seo_use_base64_image = true;
    }
}

if ($ai4seo_use_base64_image) {
    // Use wp_safe_remote_get instead of file_get_contents for fetching remote files
    $ai4seo_this_attachment_contents = ai4seo_get_remote_body($ai4seo_this_attachment_url);

    if (is_wp_error($ai4seo_this_attachment_contents)) {
        $ai4seo_remote_get_response_error = $ai4seo_this_attachment_contents->get_error_message();
        ai4seo_return_error_as_json("Could not fetch media contents: " . $ai4seo_remote_get_response_error, 391024824);
    }

    // Verify that the content is a valid image
    if (function_exists('getimagesizefromstring') && !getimagesizefromstring($ai4seo_this_attachment_contents)) {
        ai4seo_return_error_as_json("The fetched content is not a valid image.", 441024824);
    }

    $ai4seo_this_attachment_base64 = ai4seo_smart_image_base64_encode($ai4seo_this_attachment_contents);
    unset($ai4seo_this_attachment_contents);

    if (!$ai4seo_this_attachment_base64) {
        ai4seo_return_error_as_json("Could not encode media contents.", 421024824);
    }
}


// ___________________________________________________________________________________________ \\
// === CHECK/COMPARE OLD VALUES ============================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// if we have fresh input values, lets compare them with the old data. If we have old data, and if we see a difference
// to the current data, then just return the old data. If we do not have old data or if the data is the same, then we
// can continue with the generation process.
if ($ai4seo_generation_input_values) {
    $ai4seo_old_generated_values = ai4seo_read_generated_data_from_post_meta($ai4seo_this_attachment_post_id);

    if ($ai4seo_old_generated_values) {
        $ai4seo_old_generated_values = ai4seo_deep_sanitize($ai4seo_old_generated_values);

        // decode all html special chars
        array_walk($ai4seo_old_generated_values, function(&$value) {
            $value = ai4seo_normalize_text($value);
        });

        // Remove everything that is not in the active attachment attributes
        $ai4seo_old_generated_values = array_intersect_key($ai4seo_old_generated_values, array_flip($ai4seo_active_attachment_attributes));

        foreach ($ai4seo_generation_input_values AS $ai4seo_generation_input_key => $ai4seo_generation_input_value) {
            if (!isset($ai4seo_old_generated_values[$ai4seo_generation_input_key])) {
                continue;
            }

            if (!in_array($ai4seo_generation_input_key, $ai4seo_active_attachment_attributes)) {
                continue;
            }

            $ai4seo_generation_input_value = ai4seo_normalize_text($ai4seo_generation_input_value);

            if ($ai4seo_generation_input_value !== $ai4seo_old_generated_values[$ai4seo_generation_input_key]) {
                $ai4seo_response = array(
                    "generated_data" => $ai4seo_old_generated_values,
                    "credits_consumed" => 0,
                    "new_credits_balance" => $ai4seo_credits_balance,
                );

                wp_send_json_success($ai4seo_response);
            }
        }
    }
}


// ___________________________________________________________________________________________ \\
// === CHECK EXISTING ATTACHMENT ATTRIBUTES ================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/*$ai4seo_this_post_attachment_attributes_fully_covered = ai4seo_are_attachment_attributes_fully_covered($ai4seo_this_attachment_post_id);

if ($ai4seo_this_post_attachment_attributes_fully_covered) {
    $ai4seo_this_post_attachment_attributes = ai4seo_read_attachment_attributes($ai4seo_this_attachment_post_id);

    if ($ai4seo_this_post_attachment_attributes) {
        $ai4seo_response = array(
            "success" => true,
            "data" => $ai4seo_this_post_attachment_attributes,
            "credits-consumed" => 0,
            "new-credits-balance" => $ai4seo_credits_balance,
        );

        ai4seo_return_success_as_json($ai4seo_response);
    }
}*/


// ___________________________________________________________________________________________ \\
// === EXECUTE API CALL ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_attachment_attributes_generation_language = ai4seo_get_attachments_language($ai4seo_this_attachment_post_id);

$ai4seo_api_call_parameters = array(
    "language" => $ai4seo_attachment_attributes_generation_language
);

// localhost workaround -> send image as base64
if ($ai4seo_use_base64_image) {
    $base64_image_encoded = sanitize_text_field("data:{$ai4seo_this_attachment_post->post_mime_type};base64,{$ai4seo_this_attachment_base64}");
    $ai4seo_api_call_parameters["input"] = $base64_image_encoded;
} else {
    $ai4seo_api_call_parameters["attachment_url"] = $ai4seo_this_attachment_url;
}

$ai4seo_robhub_endpoint = "ai4seo/generate-all-attachment-attributes";

try {
    $ai4seo_results = ai4seo_robhub_api()->call($ai4seo_robhub_endpoint, $ai4seo_api_call_parameters, "POST");
} catch (Exception $e) {
    ai4seo_return_error_as_json("Could not execute API call: " . $e->getMessage(), 261823824);
}


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_results === false) {
    ai4seo_return_error_as_json("Could not execute API call.", 271823824);
}

if (!is_array($ai4seo_results)) {
    ai4seo_return_error_as_json("API call did not return an array.", 281823824);
}

if (empty($ai4seo_results)) {
    ai4seo_return_error_as_json("API call returned an empty array.", 291823824);
}

if (!isset($ai4seo_results["success"])) {
    ai4seo_return_error_as_json("API call did not return a success value", 301823824);
}

if ($ai4seo_results["success"] === false) {
    ai4seo_return_error_as_json($ai4seo_results["message"] . " (Error-code: #" . $ai4seo_results["code"] . ")", 311823824);
}

if ($ai4seo_results["success"] !== true && $ai4seo_results["success"] !== "true") {
    ai4seo_return_error_as_json("API call returned an invalid success value.", 321823824);
}

// check if data is set
if (!isset($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call did not return data.", 331823824);
}

// sanitize data
$ai4seo_results["data"] = wp_kses_post($ai4seo_results["data"]);

if (empty($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call returned an empty data array.", 341823824);
}

if (!ai4seo_is_json($ai4seo_results["data"])) {
    ai4seo_return_error_as_json("API call returned an invalid data array: " . print_r($ai4seo_results["data"], true), 351823824);
}

$ai4seo_generated_data = json_decode($ai4seo_results["data"], true);

if (!$ai4seo_generated_data) {
    ai4seo_return_error_as_json("API call returned an invalid data array: " . print_r($ai4seo_results["data"], true), 361823824);
}

// check if credits are set
if (!isset($ai4seo_results["credits-consumed"])) {
    ai4seo_return_error_as_json("API call did not return consumed Credits.", 371823824);
}

// sanitize credits
$ai4seo_results["credits-consumed"] = (int) $ai4seo_results["credits-consumed"];

// check if new credits balance is set
if (!isset($ai4seo_results["new-credits-balance"])) {
    ai4seo_return_error_as_json("API call did not return new Credits balance.", 381823824);
}

// sanitize new credits balance
$ai4seo_results["new-credits-balance"] = (int) $ai4seo_results["new-credits-balance"];


// === PREPARE RESPONSE ================================================================================= \\

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


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

ai4seo_add_latest_activity_entry($ai4seo_this_attachment_post_id, "success", "attachment-attributes-manually-generated", $ai4seo_results["credits-consumed"]);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_response = array(
    "generated_data" => $ai4seo_new_attachment_attributes,
    "credits_consumed" => $ai4seo_results["credits-consumed"],
    "new_credits_balance" => $ai4seo_results["new-credits-balance"],
);

wp_send_json_success($ai4seo_response);