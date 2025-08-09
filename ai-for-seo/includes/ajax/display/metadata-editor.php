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


// === GET ADDITIONAL DETAILS ===================================================================== \\

// Read post- or page-title and post custom fields
$ai4seo_this_post_title = get_the_title($ai4seo_post_id);

// read all metadata values for this post
$ai4seo_this_metadata = ai4seo_read_active_metadata_values_by_post_ids(array($ai4seo_post_id));

if ($ai4seo_this_metadata) {
    $ai4seo_this_metadata = $ai4seo_this_metadata[$ai4seo_post_id] ?? array();
}

// Prepare variables for prefixes and suffixes
$ai4seo_metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
$ai4seo_metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);

// Prepare variables for visible meta tags
$ai4seo_visible_meta_tags = ai4seo_get_setting(AI4SEO_SETTING_VISIBLE_META_TAGS);


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

echo "<div class='ai4seo-modal-sub-headline'>" . ai4seo_wp_kses(sprintf(__("Manage metadata for <b>%s</b> (#%d)", "ai-for-seo"), $ai4seo_this_post_title, $ai4seo_post_id)) . "</div>";

// GENERATE ALL BUTTON
echo "<div id='ai4seo-generate-all-metadata-button-hook'></div>";

// Form
echo "<div class='ai4seo-form ai4seo-editor-form'>";

    // === GO THROUGH EACH FIELD ================================================================================= \\

    foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
        // Make sure that required value-entries exist
        if (!isset($ai4seo_this_metadata_details["name"]) || !isset($ai4seo_this_metadata_details["input"]) || !isset($ai4seo_this_metadata_details["hint"])) {
            continue;
        }

        // get the value of the post meta entry for the input-field
        $ai4seo_this_metadata_input_value = $ai4seo_this_metadata[$ai4seo_this_metadata_identifier] ?? "";
        $ai4seo_this_metadata_input_name = ai4seo_get_prefixed_input_name("metadata_" . $ai4seo_this_metadata_identifier);
        $ai4seo_this_metadata_prefix = sanitize_text_field($ai4seo_metadata_prefixes[$ai4seo_this_metadata_identifier] ?? "");
        $ai4seo_this_metadata_suffix = sanitize_text_field($ai4seo_metadata_suffixes[$ai4seo_this_metadata_identifier] ?? "");

        // form item
        echo "<div class='ai4seo-form-item' style='margin-top: 0; padding-top: 0;'>";

            // Name
            echo "<label for='" . esc_attr($ai4seo_this_metadata_input_name) . "'>";
                if (isset($ai4seo_this_metadata_details["icon"])) {
                    echo ai4seo_wp_kses(ai4seo_get_svg_tag($ai4seo_this_metadata_details["icon"], "", "ai4seo-24x24-icon ai4seo-gray-icon")) . " ";
                }

                echo esc_html($ai4seo_this_metadata_details["name"]);

                // Tooltip
                echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_this_metadata_details["hint"]));

                if (!in_array($ai4seo_this_metadata_identifier, $ai4seo_visible_meta_tags)) {
                    echo "<div class='ai4seo-error-message ai4seo-sub-info' style='margin-top: 5px;'>";
                    echo sprintf(
                        esc_html__("The %s is not visible on the front-end as defined in the plugin settings 'Meta Tag Inclusion'.", "ai-for-seo"),
                        esc_html($ai4seo_this_metadata_details["name"])
                    );
                    echo "</div>";
                }
            echo "</label>";

            // Input
            echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";
                // Prefix
                if ($ai4seo_this_metadata_prefix) {
                    echo "<span class='ai4seo-editor-prefix ai4seo-gray-text' style='float: left'>";
                        echo esc_html__("Prefix", "ai-for-seo") . ": " . esc_html($ai4seo_this_metadata_prefix) . " ";

                        // tooltip
                        echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag(__("Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.", "ai-for-seo")));
                    echo "</span> ";
                }

                // Text field
                if ($ai4seo_this_metadata_details["input"] == "textfield") {
                    echo "<input type='text' class='ai4seo-editor-textfield' name='" . esc_attr($ai4seo_this_metadata_input_name) . "' id='" . esc_attr($ai4seo_this_metadata_input_name) . "' value='" . esc_attr($ai4seo_this_metadata_input_value) . "'/>";
                }

                // Textarea
                else if ($ai4seo_this_metadata_details["input"] == "textarea") {
                    echo "<textarea class='ai4seo-editor-textarea' name='" . esc_attr($ai4seo_this_metadata_input_name) . "' id='" . esc_attr($ai4seo_this_metadata_input_name) . "'>" . esc_textarea($ai4seo_this_metadata_input_value) . "</textarea>";
                }

                // Suffix
                if ($ai4seo_this_metadata_suffix) {
                    echo "<span class='ai4seo-editor-suffix ai4seo-gray-text' style='position: absolute; left: 0;'>";
                        echo esc_html__("Suffix", "ai-for-seo") . ": " .  esc_html($ai4seo_this_metadata_suffix) . " ";

                        // tooltip
                        echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag(__("Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.", "ai-for-seo")));
                    echo "</span>";
                }
            echo "</div>";

        echo "</div>";
    }

    // put the post id into a hidden field, so we have access to it after the form is submitted
    echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr(ai4seo_get_prefixed_input_name("metadata_editor_post_id")) . "' value='" . esc_attr($ai4seo_post_id) . "' />";
    echo "<input type='hidden' id='ai4seo-read-page-content-via-js' value='" . esc_attr($ai4seo_read_page_content_via_js) . "' />";


    // === BUTTONS ROW ================================================================================= \\

    echo "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_close_modal_by_child(this)' class='button ai4seo-button ai4seo-abort-button ai4seo-big-button'>" . esc_html__("Abort", "ai-for-seo") . "</button>";
        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_metadata_editor_inputs, ai4seo_safe_reload);' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";

echo "</div>";