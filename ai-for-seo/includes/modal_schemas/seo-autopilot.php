<?php
/**
 * Modal Schema: Represents the SEO Autopilot modal.
 *
 * @since 2.0
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

$ai4seo_active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);
$ai4seo_is_any_bulk_generation_enabled = !empty($ai4seo_active_bulk_generation_post_types);

$ai4seo_supported_post_types = ai4seo_get_supported_post_types();

$ai4seo_num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

$ai4seo_all_supported_post_types = array_merge($ai4seo_supported_post_types, array("attachment"));


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
echo esc_html__("Set Up SEO Autopilot", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";
    if (!$ai4seo_is_any_bulk_generation_enabled) {
        # hint to check settings first
        echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation", "", "ai4seo-red-icon")) . " ";
        echo ai4seo_wp_kses(sprintf(
            __("Before starting the SEO Autopilot for the first time, ensure that the <a href='%s'>plugin settings</a> are configured correctly.", "ai-for-seo"),
            esc_url(ai4seo_get_subpage_url("settings"))
        ));
        echo "<br><br>";
    }

    echo esc_html__("Select the entry types for which you want to generate SEO-relevant data.", "ai-for-seo");
    echo "<br><br>";

    echo "<div class='ai4seo-bulk-generation-modal-checkboxes-container'>";
        // checkbox for each post type
        $ai4seo_found_any_all_done_post_types = false;
        $ai4seo_this_bulk_generation_setting_name = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);

        foreach ($ai4seo_all_supported_post_types AS $ai4seo_this_supported_post_type) {
            // attachment -> media workaround
            if ($ai4seo_this_supported_post_type == "attachment") {
                $ai4seo_this_supported_post_type_label = "media files";
            } else {
                $ai4seo_this_supported_post_type_label = $ai4seo_this_supported_post_type;
            }

            $ai4seo_this_supported_post_type_label = ai4seo_get_post_type_translation($ai4seo_this_supported_post_type_label, true);
            $ai4seo_this_supported_post_type_label = ucfirst($ai4seo_this_supported_post_type_label);
            $ai4seo_this_num_missing_posts = $ai4seo_num_missing_posts_by_post_type[$ai4seo_this_supported_post_type] ?? 0;
            $ai4seo_this_post_type_icon = ai4seo_get_dashicon_tag_for_navigation($ai4seo_this_supported_post_type);
            $ai4seo_this_post_type_is_checked = !$ai4seo_is_any_bulk_generation_enabled || in_array($ai4seo_this_supported_post_type, $ai4seo_active_bulk_generation_post_types);

            echo "<div class='ai4seo-bulk-generation-modal-checkbox-container ai4seo-checkbox-container'>";
                echo ai4seo_wp_kses($ai4seo_this_post_type_icon);
                echo "<input type='checkbox' id='ai4seo-bulk-generation-checkbox-" . esc_attr($ai4seo_this_supported_post_type) . "' name='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "[]' value='" . esc_attr($ai4seo_this_supported_post_type) . "'" . ($ai4seo_this_post_type_is_checked ? " checked" : "") . ">";
                echo "<label for='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "'>";
                    echo esc_html($ai4seo_this_supported_post_type_label) . " (";
                        if ($ai4seo_this_num_missing_posts) {
                            printf(
                                esc_html(_n('%s entry left', '%s entries left', $ai4seo_this_num_missing_posts, 'ai-for-seo')),
                                esc_html($ai4seo_this_num_missing_posts)
                            );
                        } else {
                            echo esc_html__("All done", "ai-for-seo") . "*";
                            $ai4seo_found_any_all_done_post_types = true;
                        }
                    echo ")";
                echo "</label>";
            echo "</div>";
        }
    echo "</div>";

    if ($ai4seo_found_any_all_done_post_types) {
        echo "<div style='font-size: smaller; margin-top: .5rem;'>* " . esc_html__("SEO Autopilot will automatically activate when new entries are created.", "ai-for-seo") . "</div>";
    }


    // === SEO Autopilot SETTINGS ================================================================================= \\

    echo "<div class='ai4seo-form ai4seo-small-form ai4seo-no-borders' style='margin-top: 2rem;'>";

        echo "<div class='ai4seo-form-section'>";

            // === AI4SEO_SETTING_BULK_GENERATION_ORDER ================================================================================= \\

            $ai4seo_current_automated_generation_order = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_ORDER);
            $ai4seo_current_automated_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
            $ai4seo_automated_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);

            $ai4seo_this_bulk_generation_setting_name = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);

            echo "<div class='ai4seo-form-item'>";

                // output selection for automated generation new or existing filter
                echo "<label for='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "'>";
                    echo "<span>";
                        echo esc_html__("Generate metadata for:", "ai-for-seo");
                        echo " ";
                        echo ai4seo_get_icon_with_tooltip_tag(esc_html__("Choose whether to generate metadata for existing entries, newly created entries, or both. Changing this setting marks all current entries as 'existing', while any entries created afterward are considered 'new'. This distinction is refreshed each time the setting is changed, except for direct swaps between 'New entries only' and 'Existing entries only'.", "ai-for-seo"));
                    echo "</span> ";
                echo "</label>";

                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<select id='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "' name='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "'>";
                    foreach (AI4SEO_BULK_GENERATION_NEW_OR_EXISTING_FILTER_TRANSLATED_OPTIONS as $ai4seo_this_new_or_existing_filter_option => $ai4seo_this_new_or_existing_filter_option_label) {
                        echo "<option value='" . esc_attr($ai4seo_this_new_or_existing_filter_option) . "' " . ($ai4seo_this_new_or_existing_filter_option == $ai4seo_current_automated_generation_new_or_existing_filter ? "selected" : "") . ">" . esc_html($ai4seo_this_new_or_existing_filter_option_label) . "</option>";
                    }
                    echo "</select>";

                    // Add datetime picker for reference time
                    $ai4seo_this_datetime_input_name = ai4seo_get_prefixed_input_name(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);
                    $ai4seo_datetime_value = "";
                    
                    // Use current timestamp if no stored timestamp exists
                    $ai4seo_reference_timestamp = $ai4seo_automated_generation_new_or_existing_filter_reference_timestamp;

                    if (!$ai4seo_reference_timestamp) {
                        $ai4seo_reference_timestamp = time();
                    }

                    $ai4seo_datetime_value = ai4seo_format_unix_timestamp($ai4seo_reference_timestamp, 'Y-m-d', 'H:i', '\T');

                    $ai4seo_is_datetime_visible = ($ai4seo_current_automated_generation_new_or_existing_filter == "new" || $ai4seo_current_automated_generation_new_or_existing_filter == "existing");
                    
                    echo "<div class='ai4seo-datetime-picker-container' style='margin-top: 1rem;" . (!$ai4seo_is_datetime_visible ? " display: none;" : "") . "'>";
                        echo "<label for='" . esc_attr($ai4seo_this_datetime_input_name) . "' class='ai4seo-datetime-picker-label'>";
                            if ($ai4seo_current_automated_generation_new_or_existing_filter == "new") {
                                echo esc_html__("New entries since:", "ai-for-seo");
                            } else if ($ai4seo_current_automated_generation_new_or_existing_filter == "existing") {
                                echo esc_html__("Old entries before:", "ai-for-seo");
                            } else {
                                echo esc_html__("Reference time:", "ai-for-seo"); // fallback
                            }
                        echo "</label>";
                        echo "<input type='datetime-local' id='" . esc_attr($ai4seo_this_datetime_input_name) . "' name='" . esc_attr($ai4seo_this_datetime_input_name) . "' value='" . esc_attr($ai4seo_datetime_value) . "' class='ai4seo-datetime-picker-input' data-stored-timestamp='" . esc_attr($ai4seo_reference_timestamp) . "'>";
                    echo "</div>";

                echo "</div>";

            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";

            // === AI4SEO_SETTING_BULK_GENERATION_ORDER ================================================================================= \\

            $ai4seo_this_bulk_generation_setting_name = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_BULK_GENERATION_ORDER);

            echo "<div class='ai4seo-form-item'>";

                echo "<label for='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "'>";
                    echo "<span>" . esc_html__("Order of bulk generation:", "ai-for-seo") . "</span> ";
                echo "</label>";

                echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<select id='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "' name='" . esc_attr($ai4seo_this_bulk_generation_setting_name) . "'>";
                        foreach (AI4SEO_BULK_GENERATION_ORDER_TRANSLATED_OPTIONS as $ai4seo_this_order_option => $ai4seo_this_order_option_label) {
                            echo "<option value='" . esc_attr($ai4seo_this_order_option) . "' " . ($ai4seo_this_order_option == $ai4seo_current_automated_generation_order ? "selected" : "") . ">" . esc_html($ai4seo_this_order_option_label) . "</option>";
                        }
                    echo "</select>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // === COST BREAKDOWN ================================================================================= \\

    ai4seo_echo_cost_breakdown_section(100);

    echo "<br>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Close", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_close_modal_by_child(this);"));
    if ($ai4seo_is_any_bulk_generation_enabled) {
        echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Update SEO Autopilot", "ai-for-seo"), "ai4seo-success-button", "ai4seo_start_bulk_generation(this);"));
    } else {
        echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Start SEO Autopilot", "ai-for-seo"), "ai4seo-success-button", "ai4seo_start_bulk_generation(this);"));
    }
echo "</div>";