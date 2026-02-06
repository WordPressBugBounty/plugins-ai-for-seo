<?php
/**
 * Displays the metadata editor. Called via AJAX.
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

global $ai4seo_allowed_image_mime_types;


// === CHECK PARAMETER ============================================== \\

// Make sure that input-fields exist
if (!defined('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS')) {
    ai4seo_send_json_error(esc_html__("An error occurred! Please check your settings or contact the plugin developer.", "ai-for-seo"), 221920824);
}

// Get sanitized post id parameter
$ai4seo_this_attachment_post_id = absint($_REQUEST["attachment_post_id"] ?? 0);

// validate post id
if ($ai4seo_this_attachment_post_id <= 0) {
    ai4seo_send_json_error(esc_html__("Post id is invalid.", "ai-for-seo"), 291920824);
}

// get sanitized all_post_ids parameter
$ai4seo_all_attachment_post_ids = isset($_REQUEST["all_attachment_post_ids"]) && is_array($_REQUEST["all_attachment_post_ids"]) ? array_map('absint', $_REQUEST["all_attachment_post_ids"]) : array();

// $ai4seo_all_post_ids is a list of all post ids of the current list. check the position of the current post id in this list and fetch the next post id if available
$ai4seo_next_attachment_post_id = 0;

if ($ai4seo_all_attachment_post_ids) {
    $ai4seo_current_attachment_post_index = array_search($ai4seo_this_attachment_post_id, $ai4seo_all_attachment_post_ids);

    if ($ai4seo_current_attachment_post_index !== false && isset($ai4seo_all_attachment_post_ids[$ai4seo_current_attachment_post_index + 1])) {
        $ai4seo_next_attachment_post_id = $ai4seo_all_attachment_post_ids[$ai4seo_current_attachment_post_index + 1];
    }
}

// get post object
$ai4seo_this_attachment_post = get_post($ai4seo_this_attachment_post_id);

if (!$ai4seo_this_attachment_post) {
    ai4seo_send_json_error(esc_html__("Attachment Post not found.", "ai-for-seo"), 57177525);
}


// === GET ADDITIONAL DETAILS ===================================================================== \\

$ai4seo_this_post_attachment_attributes = ai4seo_read_available_attachment_attributes($ai4seo_this_attachment_post_id);

// Check if we have an image, by using $ai4seo_allowed_image_mime_types
$ai4seo_this_attachment_mime_type = ai4seo_get_attachment_post_mime_type($ai4seo_this_attachment_post_id);

$ai4seo_this_attachment_is_an_image = false;

foreach ($ai4seo_allowed_image_mime_types as $ai4seo_this_allowed_image_mime_type) {
    if (strpos($ai4seo_this_attachment_mime_type, $ai4seo_this_allowed_image_mime_type) !== false) {
        $ai4seo_this_attachment_is_an_image = true;
        break;
    }
}

$ai4seo_this_attachment_url = ai4seo_get_attachment_url($ai4seo_this_attachment_post_id);

// fallback -> get guid
if (!$ai4seo_this_attachment_url) {
    $ai4seo_this_attachment_url = ai4seo_get_assets_images_url("icons/document-question-48x48.png");
}

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

$ai4seo_settings_url = ai4seo_get_subpage_url("settings");


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// HEADLINE
echo "<div class='ai4seo-modal-headline'>";
    echo "<div class='ai4seo-modal-headline-icon'>";
        echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("64x64")) . "' />";
    echo "</div>";

    echo esc_html(AI4SEO_PLUGIN_NAME) . " - " . esc_html__("Media Attributes Editor", "ai-for-seo");
echo "</div>";

echo "<div class='ai4seo-modal-sub-headline'>";

ai4seo_echo_wp_kses(
    sprintf(
        __("Manage media attributes for <b>%s</b> (#%d)", "ai-for-seo"),
        $ai4seo_this_post_attachment_attributes["title"],
        $ai4seo_this_attachment_post_id
    )
);

echo "</div>";

if (!$ai4seo_active_attachment_attributes) {
    echo esc_html(__("No media attributes are active. Please activate at least one media attribute in the plugin settings to manage media attributes.", "ai-for-seo"));
    echo "<br><br>";
    ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_settings_url, "gear", __("Settings", "ai-for-seo"), "ai4seo-primary-button"));
    return;
}

// add an left floating image of the attachment
echo "<div class='ai4seo-attachment-editor-image-preview'>";
    echo "<img src='" . esc_url($ai4seo_this_attachment_url) . "' />";
echo "</div>";

// GENERATE ALL BUTTON
echo "<div id='ai4seo-generate-all-attachment-attributes-button-hook'></div>";

// small gap
echo "<div class='ai4seo-clear-both'></div>";

// Form
echo "<div class='ai4seo-form ai4seo-editor-form'>";

    // === GO THROUGH EACH FIELD ================================================================================= \\

    $ai4seo_skipped_attachment_attributes = [];

    foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
        if (!in_array($ai4seo_this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes)) {
            $ai4seo_skipped_attachment_attributes[] = $ai4seo_this_attachment_attribute_identifier;
            continue;
        }

        // Make sure that required value-entries exist
        if (!isset($ai4seo_this_attachment_attribute_details["name"]) || !isset($ai4seo_this_attachment_attribute_details["input-type"]) || !isset($ai4seo_this_attachment_attribute_details["hint"])) {
            error_log("AI for SEO: Missing required details for media attribute: " . $ai4seo_this_attachment_attribute_identifier . " - post id: " . $ai4seo_this_attachment_post_id);
            continue;
        }

        if (!isset($ai4seo_this_post_attachment_attributes[$ai4seo_this_attachment_attribute_identifier])) {
            error_log("AI for SEO: Media Attributes: Missing value for attribute: " . $ai4seo_this_attachment_attribute_identifier . " - post id: " . $ai4seo_this_attachment_post_id);
            continue;
        }

        $ai4seo_this_attachment_attribute_value = $ai4seo_this_post_attachment_attributes[$ai4seo_this_attachment_attribute_identifier];
        $ai4seo_this_attachment_attribute_input_name = ai4seo_get_prefixed_input_name("attachment_attribute_" . $ai4seo_this_attachment_attribute_identifier);

        // form item
        echo "<div class='ai4seo-form-item' style='margin-top: 0; padding-top: 0;'>";

            // Headline
            echo "<label for='" . esc_attr($ai4seo_this_attachment_attribute_input_name) . "'>";
                // Icon
                if (isset($ai4seo_this_attachment_attribute_details["icon"])) {
                    ai4seo_echo_wp_kses(ai4seo_get_svg_tag($ai4seo_this_attachment_attribute_details["icon"], "", "ai4seo-24x24-icon ai4seo-gray-icon"));
                    echo " ";
                }

                // Name
                echo esc_html($ai4seo_this_attachment_attribute_details["name"]);

                // Tooltip
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_this_attachment_attribute_details["hint"]));
            echo "</label>";

            // Input
            echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";

                // Text field
                if ($ai4seo_this_attachment_attribute_details["input-type"] == "textfield") {
                    echo "<input type='text' class='ai4seo-textfield ai4seo-editor-textfield' id='" . esc_attr($ai4seo_this_attachment_attribute_input_name) . "' name='" . esc_attr($ai4seo_this_attachment_attribute_input_name) . "' value='" . esc_attr($ai4seo_this_attachment_attribute_value) . "'/>";
                }

                // Textarea
                else if ($ai4seo_this_attachment_attribute_details["input-type"] == "textarea") {
                    echo "<textarea class='ai4seo-textarea ai4seo-editor-textarea ai4seo-auto-resize-textarea' id='" . esc_attr($ai4seo_this_attachment_attribute_input_name) . "' name='" . esc_attr($ai4seo_this_attachment_attribute_input_name) . "'>" . esc_textarea($ai4seo_this_attachment_attribute_value) . "</textarea>";
                }

            echo "</div>";
        echo "</div>";
    }

    // put the post id into a hidden field, so we have access to it after the form is submitted
    echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr(ai4seo_get_prefixed_input_name("attachment_attributes_editor_post_id")) . "' value='" . esc_attr($ai4seo_this_attachment_post_id) . "' />";

    // friendly reminder: $ai4seo_skipped_attachment_attributes
    if ($ai4seo_skipped_attachment_attributes) {
        foreach ($ai4seo_skipped_attachment_attributes as &$ai4seo_skipped_attachment_attribute_identifier) {
            $ai4seo_skipped_attachment_attribute_identifier = AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS[$ai4seo_skipped_attachment_attribute_identifier]["name"] ?? $ai4seo_skipped_attachment_attribute_identifier;
        }

        echo "<div class='ai4seo-form-item' style='margin-top: 0; padding-top: 0;'>";
            echo "<div class='ai4seo-yellow-message' style='margin-top: 10px;'>";
                ai4seo_echo_wp_kses(
                    sprintf(
                        __("<strong>Note:</strong> The following media attributes are currently inactive and not shown in this editor: %s. You can activate them in the <a href='%s' target='_blank'>plugin settings</a>.", "ai-for-seo"),
                        "<strong>" . esc_html(implode(", ", $ai4seo_skipped_attachment_attributes)) . "</strong>",
                        esc_url($ai4seo_settings_url)
                    )
                );
            echo "</div>";
        echo "</div>";
    }
    
    // === BUTTONS ROW ================================================================================= \\

    echo "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_close_modal_by_child(this);' class='button ai4seo-button ai4seo-abort-button ai4seo-big-button'>" . esc_html__("Abort", "ai-for-seo") . "</button>";

        if ($ai4seo_next_attachment_post_id) {
            echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_attachment_attributes_editor_inputs, function() { ai4seo_open_attachment_attributes_editor_modal(" . esc_js($ai4seo_next_attachment_post_id) . ", " . esc_js(json_encode($ai4seo_all_attachment_post_ids)) . "); });' class='button ai4seo-button ai4seo-big-button ai4seo-lockable'>" . esc_html__("Save & edit next", "ai-for-seo") . "</button>";
        }

        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_attachment_attributes_editor_inputs, function() { ai4seo_safe_page_load(); });' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button ai4seo-lockable'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";
echo "</div>";