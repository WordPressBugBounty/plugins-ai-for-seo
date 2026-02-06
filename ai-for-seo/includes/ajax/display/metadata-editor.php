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

// === CHECK PARAMETER ============================================== \\

// Make sure that input-fields exist
if (!defined('AI4SEO_METADATA_DETAILS')) {
    ai4seo_send_json_error(esc_html__("An error occurred! Please check your settings or contact the plugin developer.", "ai-for-seo"), 2306230642);
}

$ai4seo_read_page_content_via_js = isset($_REQUEST["read_page_content_via_js"]) && $_REQUEST["read_page_content_via_js"] == "true" ? "true" : "false";

// Get sanitized post id parameter
$ai4seo_post_id = absint($_REQUEST["post_id"] ?? 0);

// validate post id
if ($ai4seo_post_id <= 0) {
    ai4seo_send_json_error(esc_html__("Post id is invalid.", "ai-for-seo"), 2306230638);
}

// get sanitized all_post_ids parameter
$ai4seo_all_post_ids = isset($_REQUEST["all_post_ids"]) && is_array($_REQUEST["all_post_ids"]) ? array_map('absint', $_REQUEST["all_post_ids"]) : array();

// $ai4seo_all_post_ids is a list of all post ids of the current list. check the position of the current post id in this list and fetch the next post id if available
$ai4seo_next_post_id = 0;

if ($ai4seo_all_post_ids) {
    $ai4seo_current_post_index = array_search($ai4seo_post_id, $ai4seo_all_post_ids);

    if ($ai4seo_current_post_index !== false && isset($ai4seo_all_post_ids[$ai4seo_current_post_index + 1])) {
        $ai4seo_next_post_id = $ai4seo_all_post_ids[$ai4seo_current_post_index + 1];
    }
}


// === GET ADDITIONAL DETAILS ===================================================================== \\

// Read post- or page-title and post custom fields
$ai4seo_this_post_title = get_the_title($ai4seo_post_id);

// read all metadata values for this post
$ai4seo_this_metadata_values = ai4seo_read_available_metadata_by_post_ids(array($ai4seo_post_id));

if ($ai4seo_this_metadata_values) {
    $ai4seo_this_metadata_values = $ai4seo_this_metadata_values[$ai4seo_post_id] ?? array();
}

// Prepare variables for prefixes and suffixes
$ai4seo_metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
$ai4seo_metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);

// Prepare variables for active meta tags
$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

$ai4seo_settings_url = ai4seo_get_subpage_url("settings");


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// HEADLINE
echo "<div class='ai4seo-modal-headline'>";
    echo "<div class='ai4seo-modal-headline-icon'>";
        echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("64x64")) . "' />";
    echo "</div>";

    echo esc_html(AI4SEO_PLUGIN_NAME) . " - " . esc_html__("Metadata Editor", "ai-for-seo");
echo "</div>";

echo "<div class='ai4seo-modal-sub-headline'>";

ai4seo_echo_wp_kses(
    sprintf(
        __("Manage metadata for <b>%s</b> (#%d)", "ai-for-seo"),
        $ai4seo_this_post_title,
        $ai4seo_post_id
    )
);

echo "</div>";

if (!$ai4seo_active_meta_tags) {
    echo esc_html(__("No meta tags are active. Please activate at least one meta tag in the plugin settings to manage metadata.", "ai-for-seo"));
    echo "<br><br>";
    ai4seo_echo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_settings_url, "gear", __("Settings", "ai-for-seo"), "ai4seo-primary-button"));
    return;
}

// GENERATE ALL BUTTON
echo "<div id='ai4seo-generate-all-metadata-button-hook'></div>";

// Form
echo "<div class='ai4seo-form ai4seo-editor-form'>";

    // === GO THROUGH EACH FIELD ================================================================================= \\

    $ai4seo_skipped_meta_tags = array();

    foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
        // Make sure that required value-entries exist
        if (!isset($ai4seo_this_metadata_details["name"]) || !isset($ai4seo_this_metadata_details["input"]) || !isset($ai4seo_this_metadata_details["hint"])) {
            continue;
        }

        if (!in_array($ai4seo_this_metadata_identifier, $ai4seo_active_meta_tags)) {
            $ai4seo_skipped_meta_tags[] = $ai4seo_this_metadata_identifier;
            continue;
        }

        // get the value of the post meta entry for the input-field
        $ai4seo_this_metadata_input_value = $ai4seo_this_metadata_values[$ai4seo_this_metadata_identifier] ?? "";
        $ai4seo_this_metadata_input_name = ai4seo_get_prefixed_input_name("metadata_" . $ai4seo_this_metadata_identifier);
        $ai4seo_this_metadata_prefix = sanitize_text_field($ai4seo_metadata_prefixes[$ai4seo_this_metadata_identifier] ?? "");
        $ai4seo_this_metadata_suffix = sanitize_text_field($ai4seo_metadata_suffixes[$ai4seo_this_metadata_identifier] ?? "");

        // form item
        echo "<div class='ai4seo-form-item' style='margin-top: 0; padding-top: 0;'>";

            // Name
            echo "<label for='" . esc_attr($ai4seo_this_metadata_input_name) . "'>";
                if (isset($ai4seo_this_metadata_details["icon"])) {
                    ai4seo_echo_wp_kses(ai4seo_get_svg_tag($ai4seo_this_metadata_details["icon"], "", "ai4seo-24x24-icon ai4seo-gray-icon"));
                    echo " ";
                }

                echo esc_html($ai4seo_this_metadata_details["name"]);

                // Tooltip
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_this_metadata_details["hint"]));

                // Heads up for the focus-keyphrase: If meta-title or meta-description already got a value,
                // guide the user to make sure to overwrite the meta-title and meta-description as well
                if ($ai4seo_this_metadata_identifier == 'focus-keyphrase' && !$ai4seo_this_metadata_input_value
                    && ((isset($ai4seo_this_metadata_values['meta-title']) && $ai4seo_this_metadata_values['meta-title'])
                    || (isset($ai4seo_this_metadata_values['meta-description']) && $ai4seo_this_metadata_values['meta-description']))) {
                    echo "<br><br><span class='ai4seo-red-message'>";
                        echo esc_html__("<strong>Heads up:</strong> This entry currently has no focus keyphrase. We recommend using the <strong>Generate & Overwrite</strong> button to ensure the keyphrase is applied and reflected across all related metadata fields.", "ai-for-seo");
                    echo "</span>";
                }

            echo "</label>";

            // Input
            echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";
                // Prefix
                if ($ai4seo_this_metadata_prefix) {
                    echo "<span class='ai4seo-editor-prefix ai4seo-gray-text' style='float: left'>";
                        echo esc_html__("Prefix", "ai-for-seo") . ": " . esc_html($ai4seo_this_metadata_prefix) . " ";

                        // tooltip
                        ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag(__("Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.", "ai-for-seo")));
                    echo "</span><br>";
                }

                // Text field
                if ($ai4seo_this_metadata_details["input"] == "textfield") {
                    echo "<input type='text' class='ai4seo-textfield ai4seo-editor-textfield' name='" . esc_attr($ai4seo_this_metadata_input_name) . "' id='" . esc_attr($ai4seo_this_metadata_input_name) . "' value='" . esc_attr($ai4seo_this_metadata_input_value) . "'/>";
                }

                // Textarea
                else if ($ai4seo_this_metadata_details["input"] == "textarea") {
                    echo "<textarea class='ai4seo-textarea ai4seo-editor-textarea ai4seo-auto-resize-textarea' name='" . esc_attr($ai4seo_this_metadata_input_name) . "' id='" . esc_attr($ai4seo_this_metadata_input_name) . "'>" . esc_textarea($ai4seo_this_metadata_input_value) . "</textarea>";
                }

                // Suffix
                if ($ai4seo_this_metadata_suffix) {
                    echo "<br><span class='ai4seo-editor-suffix ai4seo-gray-text' style='position: absolute; left: 0; margin-top: 5px;'>";
                        echo esc_html__("Suffix", "ai-for-seo") . ": " . esc_html($ai4seo_this_metadata_suffix) . " ";

                        // tooltip
                        ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag(__("Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.", "ai-for-seo")));
                    echo "</span><br>";
                }
            echo "</div>";

        echo "</div>";
    }

    // friendly reminder: $ai4seo_skipped_meta_tags
    if ($ai4seo_skipped_meta_tags) {
        foreach ($ai4seo_skipped_meta_tags AS &$ai4seo_skipped_metadata_identifier) {
            $ai4seo_skipped_metadata_identifier = AI4SEO_METADATA_DETAILS[$ai4seo_skipped_metadata_identifier]["name"] ?? $ai4seo_skipped_metadata_identifier;
        }

        echo "<div class='ai4seo-form-item' style='margin-top: 0; padding-top: 0;'>";
            echo "<div class='ai4seo-yellow-message' style='margin-top: 10px;'>";
                ai4seo_echo_wp_kses(
                    sprintf(
                        __("<strong>Note:</strong> The following meta tags are currently inactive and not shown in this editor: %s. You can activate them in the <a href='%s' target='_blank'>plugin settings</a>.", "ai-for-seo"),
                        "<strong>" . esc_html(implode(", ", $ai4seo_skipped_meta_tags)) . "</strong>",
                        esc_url($ai4seo_settings_url)
                    )
                );
            echo "</div>";
        echo "</div>";
    }

    // put the post id into a hidden field, so we have access to it after the form is submitted
    echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr(ai4seo_get_prefixed_input_name("metadata_editor_post_id")) . "' value='" . esc_attr($ai4seo_post_id) . "' />";
    echo "<input type='hidden' id='ai4seo-read-page-content-via-js' value='" . esc_attr($ai4seo_read_page_content_via_js) . "' />";


    // === BUTTONS ROW ================================================================================= \\

    echo "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_close_modal_by_child(this)' class='button ai4seo-button ai4seo-abort-button ai4seo-big-button'>" . esc_html__("Abort", "ai-for-seo") . "</button>";

        if ($ai4seo_next_post_id) {
            echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_metadata_editor_inputs, function() { ai4seo_open_metadata_editor_modal(" . esc_js($ai4seo_next_post_id) . ", " . esc_js($ai4seo_read_page_content_via_js) . ", " . esc_js(json_encode($ai4seo_all_post_ids)) . "); });' class='button ai4seo-button ai4seo-big-button ai4seo-lockable'>" . esc_html__("Save & edit next", "ai-for-seo") . "</button>";
        }

        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_metadata_editor_inputs, function() { ai4seo_safe_page_load(); });' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button ai4seo-lockable'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";

echo "</div>";