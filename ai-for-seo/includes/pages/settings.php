<?php
/**
 * Renders the content of the submenu page for the AI for SEO settings page.
 *
 * @since 1.2.0
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

global $ai4seo_settings;
global $ai4seo_fallback_allowed_user_roles;

// Prepare variable for the user-roles
$ai4seo_allowed_user_roles = ai4seo_get_all_possible_user_roles();

// fallback for user-roles
if (!$ai4seo_allowed_user_roles) {
    $ai4seo_allowed_user_roles = $ai4seo_fallback_allowed_user_roles;
}

$ai4seo_setting_meta_tag_output_mode_allowed_values = ai4seo_get_setting_meta_tag_output_mode_allowed_values();

$ai4seo_wordpress_language = ai4seo_get_wordpress_language();
$ai4seo_language_options = ai4seo_get_translated_generation_language_options();

if (isset($ai4seo_language_options[$ai4seo_wordpress_language])) {
    $ai4seo_wordpress_language = $ai4seo_language_options[$ai4seo_wordpress_language];
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// HEADLINE
echo "<div class='ai4seo-form'>";

    // ___________________________________________________________________________________________ \\
    // === TOP BUTTON ROW ======================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='ai4seo-settings-top-buttons'>";
        echo "<button type='button' onclick='ai4seo_open_modal_from_schema(\"export-import-settings\", {modal_size: \"small\"});' class='button ai4seo-button ai4seo-small-button'>" . ai4seo_wp_kses(ai4seo_get_svg_tag("download")) . " " . esc_html__("Export/Import", "ai-for-seo") . "</button>";
        echo "<button type='button' onclick='ai4seo_restore_default_settings(this);' class='button ai4seo-button ai4seo-small-button'>" . ai4seo_wp_kses(ai4seo_get_svg_tag("rotate")) . " " . esc_html__("Restore Default", "ai-for-seo") . "</button>";


        // === SHOW ADVANCED SETTINGS =============================================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        echo "<input type='hidden' value='" . esc_attr($ai4seo_this_setting_input_value) . "' id='ai4seo-advanced-setting-state' name='" . esc_attr($ai4seo_this_setting_input_name) . "' />";
        echo "<div style='display: " . ($ai4seo_this_setting_input_value === "show" ? "none" : "block") . "' id='ai4seo-show-advanced-settings-container'>";
            echo "<button type='button' onclick='ai4seo_show_advanced_settings(true);' id='ai4seo-toggle-advanced-button' class='button ai4seo-button ai4seo-small-button'>" . ai4seo_wp_kses(ai4seo_get_svg_tag("eye")) . " " . esc_html__("Show Advanced Settings", "ai-for-seo") . "</button>";
        echo "</div>";
        echo "<div style='display: " . ($ai4seo_this_setting_input_value === "show" ? "block" : "none") . "' id='ai4seo-hide-advanced-settings-container'>";
            echo "<button type='button' onclick='ai4seo_hide_advanced_settings(true);' id='ai4seo-toggle-advanced-button' class='button ai4seo-button ai4seo-small-button ai4seo-advanced-settings-highlight'>" . ai4seo_wp_kses(ai4seo_get_svg_tag("eye-slash")) . " " . esc_html__("Hide Advanced Settings", "ai-for-seo") . "</button>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === METADATA ============================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
            echo '<i class="dashicons dashicons-admin-site ai4seo-menu-item-icon"></i>';
            echo esc_html__("Metadata", "ai-for-seo");
        echo "</h2>";


        // === AI4SEO_SETTING_VISIBLE_META_TAGS ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_VISIBLE_META_TAGS;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Select which meta tags to include or exclude from plugin output. Does not affect meta tags from other plugins.", "ai-for-seo");

        // Divider
        #echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("Meta Tag Inclusion:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                echo "<div class='ai4seo-medium-gap'></div>";

                // Loop through all available user-roles and display checkboxes for each of them
                foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                    $ai4seo_this_translated_checkbox_label = $ai4seo_this_metadata_details["name"] ?? $ai4seo_this_metadata_identifier;
                    $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_metadata_identifier}";

                    // Determine whether this role is supported
                    $ai4seo_is_this_checkbox_checked = in_array($ai4seo_this_metadata_identifier, $ai4seo_this_checked_values);

                    echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                        echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_metadata_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . "/> ";
                        echo esc_html($ai4seo_this_translated_checkbox_label);
                        echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE =========================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        $ai4seo_this_setting_description = "";

        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
            $ai4seo_this_setting_description .= sprintf(esc_html__("WPML detected. Use \"Automatic\" for accurate language detection per content entry.", "ai-for-seo"), "<strong>WPML</strong>", "<strong>WPML</strong>");
        } else {
            $ai4seo_this_setting_description .= esc_html__("Select a specific language or choose 'Automatic' to let AI determine the best language for each content entry.", "ai-for-seo");
        }

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Language for Metadata Generation", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo ai4seo_wp_kses(ai4seo_get_generation_language_select_options_html($ai4seo_this_setting_input_value));
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_METADATA_PREFIXES & AI4SEO_SETTING_METADATA_SUFFIXES =================== \\

        $ai4seo_metadata_prefix_setting_name = AI4SEO_SETTING_METADATA_PREFIXES;
        $ai4seo_metadata_suffix_setting_name = AI4SEO_SETTING_METADATA_SUFFIXES;

        // Prefix
        $ai4seo_metadata_prefix_setting_input_name = "ai4seo_{$ai4seo_metadata_prefix_setting_name}";
        $ai4seo_metadata_prefix_setting_input_value = ai4seo_get_setting($ai4seo_metadata_prefix_setting_name);

        // Suffix
        $ai4seo_metadata_suffix_setting_input_name = "ai4seo_{$ai4seo_metadata_suffix_setting_name}";
        $ai4seo_metadata_suffix_setting_input_value = ai4seo_get_setting($ai4seo_metadata_suffix_setting_name);

        // Description for both prefix and suffix
        $ai4seo_this_setting_description = esc_html__("Add prefix and suffix text to metadata fields. Applied only when 'Meta Tag Output Mode' is set to 'Replace' or 'Force'. Useful for including website name or branding elements consistently across all metadata.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Prefix / Suffix:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Loop through all available metadata-details and display input-fields for each of them
                foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                    $ai4seo_this_translated_setting_label = $ai4seo_this_metadata_details["name"] ?? $ai4seo_this_metadata_identifier;
                    $ai4seo_this_metadata_prefix_setting_input_name = "{$ai4seo_metadata_prefix_setting_input_name}[{$ai4seo_this_metadata_identifier}]";
                    $ai4seo_this_metadata_suffix_setting_input_name = "{$ai4seo_metadata_suffix_setting_input_name}[{$ai4seo_this_metadata_identifier}]";
                    $ai4seo_this_metadata_prefix_setting_input_value = $ai4seo_metadata_prefix_setting_input_value[$ai4seo_this_metadata_identifier] ?? "";
                    $ai4seo_this_metadata_suffix_setting_input_value = $ai4seo_metadata_suffix_setting_input_value[$ai4seo_this_metadata_identifier] ?? "";

                    // Display translated headline for this setting
                    echo "<div class='ai4seo-prefix-suffix-setting-holder'>";
                        echo "<div class='ai4seo-prefix-suffix-setting-headline'>";
                            echo esc_html($ai4seo_this_translated_setting_label) . ":";
                        echo "</div>";

                        // Display input for prefix
                        echo "<input type='text' class='ai4seo-prefix-suffix-setting-textfield' name='" . esc_attr($ai4seo_this_metadata_prefix_setting_input_name) . "' value='" . esc_attr($ai4seo_this_metadata_prefix_setting_input_value) . "' placeholder='" . esc_attr("Prefix") . "' maxlength='48' />";

                        echo " {" . esc_html__("TEXT", "ai-for-seo") . "} ";

                        // Display input for suffix
                        echo "<input type='text' class='ai4seo-prefix-suffix-setting-textfield' name='" . esc_attr($ai4seo_this_metadata_suffix_setting_input_name) . "' value='" . esc_attr($ai4seo_this_metadata_suffix_setting_input_value) . "' placeholder='" . esc_attr("Suffix") . "' maxlength='48' />";
                    echo "</div>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGIN ============================================= \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS;
        $ai4seo_sync_activated_third_party_seo_plugins = array();
        $ai4seo_active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();
        $ai4seo_uses_workarounds_for_third_party_seo_plugins = false;

        foreach ($ai4seo_active_third_party_seo_plugin_details AS $ai4seo_active_third_party_seo_plugin_identifier => $ai4seo_active_third_party_seo_plugin_detail) {
            if (in_array($ai4seo_active_third_party_seo_plugin_identifier, array(AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO, AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO, AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO, AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL))) {
                $ai4seo_uses_workarounds_for_third_party_seo_plugins = true;
                break;
            }
        }

        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Sync AI for SEO changes to selected third-party SEO plugins for further analysis.", "ai-for-seo");

        if ($ai4seo_uses_workarounds_for_third_party_seo_plugins) {
            $ai4seo_this_setting_description .= "<br><br>";
            $ai4seo_this_setting_description .= __("<strong>WARNING:</strong> May permanently change third-party plugin data. Backup recommended.", "ai-for-seo");
        }

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Form element
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Sync 'AI for SEO' Changes:", "ai-for-seo");
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";

                if ($ai4seo_active_third_party_seo_plugin_details) {
                    // Define variable for the selected user-roles based on plugin-settings
                    $ai4seo_sync_activated_third_party_seo_plugins = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                    // add a select / un select all checkbox
                    if (count($ai4seo_active_third_party_seo_plugin_details) > 1) {
                        echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                        echo "<div class='ai4seo-medium-gap'></div>";
                    }

                    // Loop through all available user-roles and display checkboxes for each of them
                    foreach ($ai4seo_active_third_party_seo_plugin_details as $ai4seo_this_third_party_seo_plugin_identifier => $ai4seo_this_third_party_seo_plugin_details) {
                        // Determine whether this role is supported
                        $ai4seo_is_this_checkbox_checked = in_array($ai4seo_this_third_party_seo_plugin_identifier, $ai4seo_sync_activated_third_party_seo_plugins);
                        $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_third_party_seo_plugin_identifier}";

                        echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                            echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_third_party_seo_plugin_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . " onchange='ai4seo_toggle_sync_only_these_metadata_container();' class='ai4seo_third_party_sync_checkbox'/> ";

                            // Display the icon
                            if (!empty($ai4seo_this_third_party_seo_plugin_details["icon"])) {
                                $ai4seo_this_icon_css_class = "ai4seo-large-icon";

                                if (!empty($ai4seo_this_third_party_seo_plugin_details["icon-css-class"])) {
                                    $ai4seo_this_icon_css_class .= " " . $ai4seo_this_third_party_seo_plugin_details["icon-css-class"];
                                }

                                echo ai4seo_wp_kses(ai4seo_get_svg_tag($ai4seo_this_third_party_seo_plugin_details["icon"], $ai4seo_this_third_party_seo_plugin_details["mame"] ?? "", $ai4seo_this_icon_css_class)) . " ";
                            }

                            // Display the name
                            echo esc_html($ai4seo_this_third_party_seo_plugin_details["name"] ?? $ai4seo_this_third_party_seo_plugin_identifier);
                            echo "<br>";
                        echo "</label>";
                    }

                    echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                } else {
                    echo "<i>" . esc_html__("No supported and active third-party SEO plugins found.", "ai-for-seo") . "</i>";
                }
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA;

        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Choose which metadata to sync with selected third-party SEO plugins.", "ai-for-seo");

        echo "<div style='display: " . ($ai4seo_sync_activated_third_party_seo_plugins ? "block" : "none") . "' id='ai4seo-sync-only-these-metadata-container'>";

            // Divider
            echo "<hr class='ai4seo-form-item-divider'>";

            // Display form elements
            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Metadata to Sync with Third-Party Plugins:", "ai-for-seo") ;
                echo "</label>";

                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    // Define variable for the selected user-roles based on plugin-settings
                    $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                    // add a select / un select all checkbox
                    echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                    echo "<div class='ai4seo-medium-gap'></div>";

                    // Loop through all available user-roles and display checkboxes for each of them
                    foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                        $ai4seo_this_translated_checkbox_label = $ai4seo_this_metadata_details["name"] ?? $ai4seo_this_metadata_identifier;
                        $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_metadata_identifier}";

                        // Determine whether this role is supported
                        $ai4seo_is_this_checkbox_checked = in_array($ai4seo_this_metadata_identifier, $ai4seo_this_checked_values);

                        echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                        echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_metadata_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . "/> ";
                        echo esc_html($ai4seo_this_translated_checkbox_label);

                        echo "<br>";
                        echo "</label>";
                    }

                    echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                echo "</div>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_OVERWRITE_EXISTING_META_TAGS ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Overwrite existing metadata when using SEO Autopilot. If disabled, only missing metadata will be generated.", "ai-for-seo");
        $ai4seo_this_setting_description .= "<br><br>";
        $ai4seo_this_setting_description .= __("<strong>WARNING:</strong> Permanently overwrites existing data. Backup recommended before activating SEO Autopilot.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("SEO Autopilot: Overwrite Existing Metadata:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                echo "<div class='ai4seo-medium-gap'></div>";

                // Loop through all available user-roles and display checkboxes for each of them
                foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                    $ai4seo_this_translated_checkbox_label = $ai4seo_this_metadata_details["name"] ?? $ai4seo_this_metadata_identifier;
                    $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_metadata_identifier}";

                    // Determine whether this role is supported
                    $ai4seo_is_this_checkbox_checked = in_array($ai4seo_this_metadata_identifier, $ai4seo_this_checked_values);

                    echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_metadata_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . "/> ";
                    echo esc_html($ai4seo_this_translated_checkbox_label);

                    echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Generate metadata for entries that already have complete metadata sets. Disable to only generate for entries missing at least one field.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("SEO Autopilot: Include Complete Entries When Overwriting:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . "/> ";
                    echo esc_html__("Include Complete Entries", "ai-for-seo");

                    echo "<br>";
                    echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_META_TAG_OUTPUT_MODE ================================================================================= \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_META_TAG_OUTPUT_MODE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Meta Tag Output Mode", "ai-for-seo") . ":";
            echo "</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    foreach ($ai4seo_setting_meta_tag_output_mode_allowed_values AS $ai4seo_this_option_value => $ai4seo_this_option_label) {
                        echo "<option value='" . esc_attr($ai4seo_this_option_value) . "'" . ($ai4seo_this_setting_input_value == $ai4seo_this_option_value ? " selected='selected'" : "") . ">" . esc_html($ai4seo_this_option_label) . "</option>";
                    }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    // Disable 'AI for SEO' Meta Tags
                    echo ai4seo_wp_kses(__("<strong>Disable:</strong> Disables all plugin meta tags. Useful when syncing to other SEO plugins.", "ai-for-seo")) . "<br><br>";

                    // Force 'AI for SEO' Meta Tags
                    echo ai4seo_wp_kses(__("<strong>Force:</strong> Outputs plugin meta tags regardless of other plugins. May create duplicates.", "ai-for-seo")) . "<br><br>";

                    // Replace Existing Meta Tags
                    echo ai4seo_wp_kses(__("<strong>Replace (Recommended):</strong> Replaces existing meta tags, preventing duplicates and cleaning HTML header.", "ai-for-seo")) . "<br><br>";

                    // Complement Existing Meta Tags
                    echo ai4seo_wp_kses(__("<strong>Complement:</strong> Adds missing meta tags without overwriting existing ones.", "ai-for-seo"));
                echo "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === MEDIA ATTRIBUTES ====================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
        echo '<i class="dashicons dashicons-admin-media ai4seo-menu-item-icon"></i>';
        echo esc_html__("Media attributes", "ai-for-seo");
        echo "</h2>";


        // === AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Select which media attributes to include or exclude from plugin generation. Does not affect existing attributes.", "ai-for-seo");

        // Divider
        #echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("Active Media Attributes:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                echo "<div class='ai4seo-medium-gap'></div>";

                // Loop through all available user-roles and display checkboxes for each of them
                foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_media_attribute_identifier => $ai4seo_this_media_attribute_details) {
                    $ai4seo_this_translated_checkbox_label = $ai4seo_this_media_attribute_details["name"] ?? $ai4seo_this_media_attribute_identifier;
                    $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_media_attribute_identifier}";

                    // Determine whether this role is supported
                    $ai4seo_is_this_checkbox_checked = in_array($ai4seo_this_media_attribute_identifier, $ai4seo_this_checked_values);

                    echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_media_attribute_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . "/> ";
                    echo esc_html($ai4seo_this_translated_checkbox_label);

                    echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE ============================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
            $ai4seo_this_setting_description = sprintf(
                    esc_html__(
                        "WPML detected. Use \"Automatic\" for accurate language detection. Falls back to WordPress language (%s) if detection fails.",
                        "ai-for-seo"
                    ),
                    "<strong>" . $ai4seo_wordpress_language . "</strong>",
                );
        } else {
            $ai4seo_this_setting_description = sprintf(
                esc_html__("Select a specific language or choose 'Automatic' to use your WordPress language (%s). Full automatic detection will be available in a future release.", "ai-for-seo"),
                "<strong>" . $ai4seo_wordpress_language . "</strong>",
            );
        }

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Form element
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Language for Media Attributes Generation", "ai-for-seo") . ":";
            echo "</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo ai4seo_wp_kses(ai4seo_get_generation_language_select_options_html($ai4seo_this_setting_input_value));
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES & AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES =================== \\

        $ai4seo_attachment_attributes_prefix_setting_name = AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES;
        $ai4seo_attachment_attributes_suffix_setting_name = AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES;
        // Prefix
        $ai4seo_attachment_attributes_prefix_setting_input_name = "ai4seo_{$ai4seo_attachment_attributes_prefix_setting_name}";
        $ai4seo_attachment_attributes_prefix_setting_input_value = ai4seo_get_setting($ai4seo_attachment_attributes_prefix_setting_name);

        // Suffix
        $ai4seo_attachment_attributes_suffix_setting_input_name = "ai4seo_{$ai4seo_attachment_attributes_suffix_setting_name}";
        $ai4seo_attachment_attributes_suffix_setting_input_value = ai4seo_get_setting($ai4seo_attachment_attributes_suffix_setting_name);

        // Description for both prefix and suffix
        $ai4seo_this_setting_description = esc_html__("Add prefix and suffix text to media attributes for consistent branding. Note: Only applied to newly generated attributes, not existing ones.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Prefix / Suffix:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Loop through all available attachment-attributes-details and display input-fields for each of them
                foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attributes_identifier => $ai4seo_this_attachment_attributes_details) {
                    $ai4seo_this_translated_setting_label = $ai4seo_this_attachment_attributes_details["name"] ?? $ai4seo_this_attachment_attributes_identifier;
                    $ai4seo_this_attachment_attributes_prefix_setting_input_name = "{$ai4seo_attachment_attributes_prefix_setting_input_name}[{$ai4seo_this_attachment_attributes_identifier}]";
                    $ai4seo_this_attachment_attributes_suffix_setting_input_name = "{$ai4seo_attachment_attributes_suffix_setting_input_name}[{$ai4seo_this_attachment_attributes_identifier}]";
                    $ai4seo_this_attachment_attributes_prefix_setting_input_value = $ai4seo_attachment_attributes_prefix_setting_input_value[$ai4seo_this_attachment_attributes_identifier] ?? "";
                    $ai4seo_this_attachment_attributes_suffix_setting_input_value = $ai4seo_attachment_attributes_suffix_setting_input_value[$ai4seo_this_attachment_attributes_identifier] ?? "";

                    // Display translated headline for this setting
                    echo "<div class='ai4seo-prefix-suffix-setting-holder'>";
                        echo "<div class='ai4seo-prefix-suffix-setting-headline'>";
                            echo esc_html($ai4seo_this_translated_setting_label) . ":";
                        echo "</div>";

                        // Display input for prefix
                        echo "<input type='text' class='ai4seo-prefix-suffix-setting-textfield' name='" . esc_attr($ai4seo_this_attachment_attributes_prefix_setting_input_name) . "' value='" . esc_attr($ai4seo_this_attachment_attributes_prefix_setting_input_value) . "' placeholder='" . esc_attr("Prefix") . "' maxlength='48' />";

                        echo " {" . esc_html__("TEXT", "ai-for-seo") . "} ";

                        // Display input for suffix
                        echo "<input type='text' class='ai4seo-prefix-suffix-setting-textfield' name='" . esc_attr($ai4seo_this_attachment_attributes_suffix_setting_input_name) . "' value='" . esc_attr($ai4seo_this_attachment_attributes_suffix_setting_input_value) . "' placeholder='" . esc_attr("Suffix") . "' maxlength='48' />";
                    echo "</div>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Overwrite existing media attributes when using SEO Autopilot. If disabled, only missing attributes will be generated.", "ai-for-seo");
        $ai4seo_this_setting_description .= "<br><br>";
        $ai4seo_this_setting_description .= __("<strong>WARNING:</strong> Permanently overwrites existing data. Backup recommended.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("SEO Autopilot: Overwrite Existing Media Attributes:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                echo "<div class='ai4seo-medium-gap'></div>";

                // Loop through all available user-roles and display checkboxes for each of them
                foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_attachment_attribute_name => $ai4seo_attachment_attribute_details) {
                    $ai4seo_this_translated_checkbox_label = $ai4seo_attachment_attribute_details["name"] ?? $ai4seo_attachment_attribute_name;
                    $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_attachment_attribute_name}";

                    // Determine whether this role is supported
                    $ai4seo_is_this_checkbox_checked = in_array($ai4seo_attachment_attribute_name, $ai4seo_this_checked_values);

                    echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_attachment_attribute_name) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . "/> ";
                    echo esc_html($ai4seo_this_translated_checkbox_label);

                    echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Generate media attributes for entries that already have complete attribute sets. Disable to only generate for entries missing attributes.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("SEO Autopilot: Include Complete Entries When Overwriting:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . "/> ";
                    echo esc_html__("Include Complete Entries When Overwriting", "ai-for-seo");

                    echo "<br>";
                    echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";

    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === USER MANAGEMENT ======================================================================= \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section ai4seo-is-advanced-setting'>";
        // Headline
        echo "<h2>";
        echo '<i class="dashicons dashicons-admin-users ai4seo-menu-item-icon"></i>';
        echo esc_html__("User Management", "ai-for-seo");
        echo "</h2>";


        // === ALLOWED USER ROLES =========================================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ALLOWED_USER_ROLES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Select user roles that should have access to this plugin. Only roles with 'edit_posts' capability are listed.", "ai-for-seo");

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo esc_html__("Allowed User Roles:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                echo ai4seo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                echo "<div class='ai4seo-medium-gap'></div>";

                // Loop through all available user-roles and display checkboxes for each of them
                foreach ($ai4seo_allowed_user_roles as $ai4seo_this_user_role_identifier => $ai4seo_this_user_role) {
                    $ai4seo_this_translated_checkbox_label = translate_user_role($ai4seo_this_user_role);

                    if ($ai4seo_this_translated_checkbox_label) {
                        $ai4seo_this_user_role = $ai4seo_this_translated_checkbox_label;
                    }

                    $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_user_role_identifier}";

                    // Determine whether this role is supported
                    $ai4seo_is_this_checkbox_checked = (in_array($ai4seo_this_user_role_identifier, $ai4seo_this_checked_values) || $ai4seo_this_user_role_identifier == "administrator");

                    echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                        echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_user_role_identifier) . "'" . ($ai4seo_is_this_checkbox_checked ? " checked='checked'" : "") . ($ai4seo_this_user_role_identifier == "administrator" ? " class='ai4seo-disabled-form-input' disabled='disabled'" : "") . " /> ";
                        echo esc_html($ai4seo_this_user_role);
                        echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === TROUBLESHOOTING ========================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section ai4seo-is-advanced-setting'>";
        // Headline
        echo "<h2>";
            echo '<i class="dashicons dashicons-sos ai4seo-menu-item-icon"></i>';
            echo esc_html__("Troubleshooting & Experimental", "ai-for-seo");
        echo "</h2>";


        // === BULK GENERATION DURATION ================================================================================= \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_BULK_GENERATION_DURATION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = "";

        if (ai4seo_is_wordpress_cron_disabled()) {
            $ai4seo_this_setting_description = __("Set duration to match your cron job frequency. Best performance: server cron every minute with 1-minute duration.", "ai-for-seo");
        } else {
            $ai4seo_this_setting_description = "<strong>" . __("Attention:", "ai-for-seo") . "</strong> ";
            $ai4seo_this_setting_description .= __("WordPress cron is enabled, which may limit SEO Autopilot efficiency. Recommend setting up server cron (every minute) or gradually increase duration.", "ai-for-seo");
        }

        $ai4seo_this_setting_description .= "<br /><br />";
        $ai4seo_this_setting_description .= __("Reduce duration if server experiences performance issues during bulk generation.", "ai-for-seo");

        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>" . esc_html__("SEO Autopilot (Bulk Generation) Duration", "ai-for-seo") . ":</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Bulk generation duration  select field, containing 10, 20, 30, 40, 50, 60, 120, 180, 240 and 300
                echo "<select class='ai4seo-editor-select' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='" . esc_attr($ai4seo_this_setting_input_value) . "'>";
                    for ($ai4seo_this_duration = 10; $ai4seo_this_duration <= 50; $ai4seo_this_duration += 10) {
                        echo "<option value='" . esc_attr($ai4seo_this_duration) . "' " . selected($ai4seo_this_setting_input_value, $ai4seo_this_duration, false) . ">" . sprintf(esc_html__("%s seconds", "ai-for-seo"), $ai4seo_this_duration) . "</option>";
                    }
                    for ($ai4seo_this_duration = 60; $ai4seo_this_duration <= 300; $ai4seo_this_duration += 60) {
                        echo "<option value='" . esc_attr($ai4seo_this_duration) . "' " . selected($ai4seo_this_setting_input_value, $ai4seo_this_duration, false) . ">" . sprintf(esc_html(_n("%s minute", "%s minutes", ($ai4seo_this_duration / 60), "ai-for-seo")), number_format_i18n($ai4seo_this_duration / 60)) . "</option>";
                    }
                echo "</select>";
                echo "<div class='ai4seo-medium-gap'></div>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Inject alt text at render level to ensure images always include the correct alt text, even if themes or page builders fail to output it.", "ai-for-seo");
        $ai4seo_this_setting_description .= "<br><br>" . __("Enable this setting if your theme or page builder does not display alt text generated by the plugin (or omits alt text entirely).", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            // new feature bubble # todo: remove bubble after some time
            echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
            echo esc_html__("Alt Text Injection:", "ai-for-seo") ;

            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . "/> ";
                    echo esc_html__("Inject alt text", "ai-for-seo");
                    echo "<br>";
                    echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ENABLE_RENDER_LEVEL_IMAGE_TITLE_INJECTION ============================================= \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Choose what to inject as the title attribute of image elements. Provides hover information for images.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            // new feature bubble # todo: remove bubble after some time
            echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
            echo esc_html__("Image Title Injection:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                $ai4seo_title_injection_options = ai4seo_get_setting_render_level_title_injection_allowed_values();

                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                foreach ($ai4seo_title_injection_options as $ai4seo_option_value => $ai4seo_option_label) {
                    $ai4seo_is_selected = ($ai4seo_this_setting_input_value === $ai4seo_option_value) ? ' selected="selected"' : '';
                    echo "<option value='" . esc_attr($ai4seo_option_value) . "'" . $ai4seo_is_selected . ">";
                    echo esc_html($ai4seo_option_label);
                    echo "</option>";
                }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";

        
        // === AI4SEO_SETTING_IMAGE_UPLOAD_METHOD ============================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_IMAGE_UPLOAD_METHOD;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Choose how images are sent to our server: <strong>Auto (recommended)</strong>: Selects method based on accessibility. <strong>URL</strong>: Always sends image URL. <strong>Data</strong>: Always sends full image data.", "ai-for-seo");
        $ai4seo_this_setting_description .= "<br><br>";
        $ai4seo_this_setting_description .= __("Try 'Data' if you experience generation issues. Slower but more reliable in some situations.", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            // new feature bubble # todo: remove bubble after some time
            echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
            echo esc_html__("Image Upload Method:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                $ai4seo_image_upload_method_options = array(
                    "auto" => __("Auto (default & recommended)", "ai-for-seo"),
                    "url" => __("URL", "ai-for-seo"),
                    "base64" => __("Data", "ai-for-seo")
                );

                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                foreach ($ai4seo_image_upload_method_options as $ai4seo_option_value => $ai4seo_option_label) {
                    $ai4seo_is_selected = ($ai4seo_this_setting_input_value === $ai4seo_option_value) ? ' selected="selected"' : '';
                    echo "<option value='" . esc_attr($ai4seo_option_value) . "'" . $ai4seo_is_selected . ">";
                    echo esc_html($ai4seo_option_label);
                    echo "</option>";
                }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";

    echo "</div>";

    // Submit button
    echo "<div class='ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_settings_inputs);' id='ai4seo-save-settings' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";
echo "</div>";