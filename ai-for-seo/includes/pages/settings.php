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
$ai4seo_focus_keyphrase_behavior_options = ai4seo_get_focus_keyphrase_behavior_options();

$ai4seo_wordpress_language = ai4seo_get_wordpress_language();
$ai4seo_language_options = ai4seo_get_translated_generation_language_options();

if (isset($ai4seo_language_options[$ai4seo_wordpress_language])) {
    $ai4seo_wordpress_language = $ai4seo_language_options[$ai4seo_wordpress_language];
}

$ai4seo_user_has_basic_plan_or_higher = ai4seo_user_has_at_least_plan('s');
$ai4seo_user_has_pro_plan_or_higher = ai4seo_user_has_at_least_plan('m');
$ai4seo_user_has_premium_plan_or_higher = ai4seo_user_has_at_least_plan('l');


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// HEADLINE
echo "<div class='ai4seo-form'>";

    // ___________________________________________________________________________________________ \\
    // === TOP BUTTON ROW ======================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='ai4seo-settings-top-buttons'>";
        echo "<button type='button' onclick='ai4seo_open_modal_from_schema(\"export-import-settings\", {modal_size: \"small\"});' class='button ai4seo-button ai4seo-small-button'>";
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("download"));
            echo " " . esc_html__("Export/Import", "ai-for-seo");
        echo "</button>";
        echo "<button type='button' onclick='ai4seo_restore_default_settings(this);' class='button ai4seo-button ai4seo-small-button'>";
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("rotate"));
            echo " " . esc_html__("Restore Default", "ai-for-seo");
        echo "</button>";


        // === SHOW ADVANCED SETTINGS =============================================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        echo "<input type='hidden' value='" . esc_attr($ai4seo_this_setting_input_value) . "' id='ai4seo-advanced-setting-state' name='" . esc_attr($ai4seo_this_setting_input_name) . "' />";
        echo "<div style='display: " . ($ai4seo_this_setting_input_value === "show" ? "none" : "block") . "' id='ai4seo-show-advanced-settings-container'>";
            echo "<button type='button' onclick='ai4seo_show_advanced_settings(true);' id='ai4seo-toggle-advanced-button' class='button ai4seo-button ai4seo-small-button'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("eye"));
                echo " " . esc_html__("Show Advanced Settings", "ai-for-seo");
            echo "</button>";
        echo "</div>";
        echo "<div style='display: " . ($ai4seo_this_setting_input_value === "show" ? "block" : "none") . "' id='ai4seo-hide-advanced-settings-container'>";
            echo "<button type='button' onclick='ai4seo_hide_advanced_settings(true);' id='ai4seo-toggle-advanced-button' class='button ai4seo-button ai4seo-small-button ai4seo-advanced-settings-highlight'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("eye-slash"));
                echo " " . esc_html__("Hide Advanced Settings", "ai-for-seo");
            echo "</button>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === METADATA ============================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
            echo '<i class="dashicons dashicons-admin-site ai4seo-menu-item-icon"></i>';
            echo esc_html__("Metadata", "ai-for-seo") . " <span style='font-size: small'>(" . esc_html__("for pages/posts/products etc.", "ai-for-seo") . ")</span>";
        echo "</h2>";


        // === AI4SEO_SETTING_ACTIVE_META_TAGS ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ACTIVE_META_TAGS;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Select which meta tags to include or exclude from plugin generation. Does not affect meta tags from other plugins.", "ai-for-seo");

        // Divider
        #echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Active Meta Tags:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Define variable for the selected user-roles based on plugin-settings
                $ai4seo_this_checked_values = ($ai4seo_this_setting_input_value && is_array($ai4seo_this_setting_input_value) ? $ai4seo_this_setting_input_value : array());

                // add a select / un select all checkbox
                ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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

                        if (in_array($ai4seo_this_metadata_identifier, array('focus-keyphrase', 'keywords'))) {
                            // new feature bubble # todo: remove bubble after some time
                            echo " <span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                        }

                        echo "<br>";
                    echo "</label>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_DISABLED_POST_TYPES ===================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_DISABLED_POST_TYPES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_disabled_post_types = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_disabled_post_types = is_array($ai4seo_disabled_post_types) ? $ai4seo_disabled_post_types : array();
        $ai4seo_all_supported_post_types = ai4seo_get_supported_post_types(false);
        $ai4seo_active_post_types = array_values(array_diff($ai4seo_all_supported_post_types, $ai4seo_disabled_post_types));

        $ai4seo_this_setting_description = esc_html__("Uncheck any post type you want to hide from AI for SEO dashboards, widgets, and automations. Newly detected post types stay active by default.", "ai-for-seo");

        #
        echo "<hr class='ai4seo-form-item-divider'>";

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Active Post Types:", "ai-for-seo");
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                if ($ai4seo_all_supported_post_types) {
                    ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                    echo "<div class='ai4seo-medium-gap'></div>";

                    foreach ($ai4seo_all_supported_post_types as $ai4seo_this_post_type) {
                        $ai4seo_this_checkbox_id = "{$ai4seo_this_setting_input_name}-{$ai4seo_this_post_type}";
                        $ai4seo_is_checkbox_checked = in_array($ai4seo_this_post_type, $ai4seo_active_post_types, true);
                        $ai4seo_post_type_label = ai4seo_get_post_type_translation($ai4seo_this_post_type, true);

                        echo "<label for='" . esc_attr($ai4seo_this_checkbox_id) . "' class='ai4seo-form-multiple-inputs'>";
                            echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_checkbox_id) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "[]' value='" . esc_attr($ai4seo_this_post_type) . "'" . ($ai4seo_is_checkbox_checked ? " checked='checked'" : "") . " /> ";
                            echo esc_html($ai4seo_post_type_label);
                            echo "<br>";
                        echo "</label>";
                    }
                } else {
                    echo "<p class='ai4seo-form-item-description'>";
                        echo esc_html__("No supported post types detected.", "ai-for-seo");
                    echo "</p>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE =========================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        $ai4seo_this_setting_description = "";

        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
            $ai4seo_this_setting_description .= sprintf(esc_html__("%s detected. Use “Automatic” to determine which language to use based on %s.", "ai-for-seo"), "<strong>WPML</strong>", "<strong>WPML</strong>");
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
                    ai4seo_echo_wp_kses(ai4seo_get_generation_language_select_options_html($ai4seo_this_setting_input_value));
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_METADATA_FALLBACKS ========================================================= \\

        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Meta Tag Fallbacks:", "ai-for-seo");
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                foreach (AI4SEO_METADATA_FALLBACK_MAPPING as $ai4seo_this_metadata_identifier => $ai4seo_this_fallback_setting_name) {
                    if (!isset(AI4SEO_METADATA_DETAILS[$ai4seo_this_metadata_identifier])) {
                        continue;
                    }

                    $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_fallback_setting_name);
                    $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_fallback_setting_name);
                    $ai4seo_this_allowed_options = ai4seo_get_metadata_fallback_allowed_values($ai4seo_this_metadata_identifier);
                    $ai4seo_this_label = AI4SEO_METADATA_DETAILS[$ai4seo_this_metadata_identifier]['name'] ?? $ai4seo_this_metadata_identifier;

                    echo "<div class='ai4seo-prefix-suffix-setting-holder'>";
                        echo "<div class='ai4seo-prefix-suffix-setting-headline'>";
                            echo esc_html($ai4seo_this_label) . ":";
                        echo "</div>";

                        echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                            foreach ($ai4seo_this_allowed_options as $ai4seo_this_option_value => $ai4seo_this_option_label) {
                                echo "<option value='" . esc_attr($ai4seo_this_option_value) . "'" . ($ai4seo_this_setting_input_value === $ai4seo_this_option_value ? " selected='selected'" : "") . ">" . esc_html($ai4seo_this_option_label) . "</option>";
                            }
                        echo "</select>";
                    echo "</div>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    echo esc_html__("Choose how each meta tag should behave when no value is available or has not been generated.", "ai-for-seo");
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

        $ai4seo_metadata_placeholders_tooltip = __(
            "<strong>Available placeholders</strong> (case-insensitive; supports {PLACEHOLDER}, [PLACEHOLDER], or %%placeholder%% formats):<br>WEBSITE_URL - Site URL.<br>WEBSITE_NAME - Site name.<br>POST_ID - Current entry ID.<br>TITLE - Current entry title.<br>PRODUCT_NAME - WooCommerce product title (products only).<br>PRODUCT_PRICE - WooCommerce product price (products only).<br><br>Placeholders are replaced automatically when meta tags are injected on the frontend.",
            "ai-for-seo"
        );

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Prefix / Suffix:", "ai-for-seo") ;
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_metadata_placeholders_tooltip));
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Loop through all available metadata-details and display input-fields for each of them
                foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                    // skip "focus-keyphrase" and "keywords"
                    if (in_array($ai4seo_this_metadata_identifier, array('focus-keyphrase', 'keywords'))) {
                        continue;
                    }

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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                        ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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

                                ai4seo_echo_wp_kses(ai4seo_get_svg_tag($ai4seo_this_third_party_seo_plugin_details["icon"], $ai4seo_this_third_party_seo_plugin_details["mame"] ?? "", $ai4seo_this_icon_css_class));
                                echo " ";
                            }

                            // Display the name
                            echo esc_html($ai4seo_this_third_party_seo_plugin_details["name"] ?? $ai4seo_this_third_party_seo_plugin_identifier);
                            echo "<br>";
                        echo "</label>";
                    }

                    echo "<p class='ai4seo-form-item-description'>";
                        ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                    ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
                    echo "<div class='ai4seo-medium-gap'></div>";

                    // Loop through all available user-roles and display checkboxes for each of them
                    foreach (AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
                        // skip "keywords"
                        if (in_array($ai4seo_this_metadata_identifier, array('keywords'))) {
                            continue;
                        }

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
                        ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                echo "</div>";
        echo "</div>";
        echo "</div>";

    // === AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA ===================================== \\

        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE)) {
            $ai4seo_this_setting_name = AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA;
            $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
            $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
            $ai4seo_this_setting_options = ai4seo_get_setting_include_product_price_in_metadata_allowed_values();

            if (!is_string($ai4seo_this_setting_input_value)
                || !array_key_exists($ai4seo_this_setting_input_value, $ai4seo_this_setting_options)) {
                $ai4seo_this_setting_input_value = AI4SEO_DEFAULT_SETTINGS[$ai4seo_this_setting_name] ?? 'never';
            }

            // Divider
            echo "<hr class='ai4seo-form-item-divider'>";

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    // new feature bubble # todo: remove bubble after some time
                    echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                    ai4seo_echo_wp_kses(ai4seo_get_svg_tag('woocommerce', "WooCommerce", 'ai4seo-medium-icon'));
                    echo " ";
                    echo esc_html__("Include product price in metadata", "ai-for-seo") . ":";
                echo "</label>";

                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' class='ai4seo-select'>";
                        foreach ($ai4seo_this_setting_options as $ai4seo_option_value => $ai4seo_option_label) {
                            echo '<option value="' . esc_attr( $ai4seo_option_value ) . '" ' .
                                selected( $ai4seo_option_value, $ai4seo_this_setting_input_value, false ) . '>' .
                                esc_html( $ai4seo_option_label ) .
                                '</option>';
                        }
                    echo "</select>";

                    echo "<p class='ai4seo-form-item-description'>";
                        echo esc_html__("Choose how WooCommerce product prices should be handled in generated metadata: never include them, store a fixed price, or use a dynamic placeholder that updates at render time.", "ai-for-seo");
                    echo "</p>";
                echo "</div>";
            echo "</div>";
        }

        // === AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE ====================================== \\

        echo "<hr class='ai4seo-form-item-divider'>";

        $ai4seo_this_setting_name = AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = (bool) ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Let AI for SEO include your existing metadata and focus keyphrases as additional context for generation.", "ai-for-seo");

        if (!$ai4seo_user_has_basic_plan_or_higher) {
            $ai4seo_this_setting_description .= ' ' . esc_html__("Requires Basic Plan or higher.", "ai-for-seo");
        }

        $ai4seo_plan_badge_html = ai4seo_get_plan_badge('s');

        echo "<div class='ai4seo-form-item'>"; 
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Use existing metadata as reference", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . ($ai4seo_user_has_basic_plan_or_higher ? "" : " disabled='disabled'") . " /> ";
                    echo esc_html__("Use existing metadata as reference", "ai-for-seo");
                    echo " ";
                    ai4seo_echo_wp_kses($ai4seo_plan_badge_html);
                    echo "<br>";
                echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
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
                ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Generate metadata for entries that already have complete metadata sets. Disable to only generate for entries missing at least one field. Note: Make sure to enable at least one field in 'Overwrite Existing Metadata' to see any effect.", "ai-for-seo");

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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA ============================ \\

        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        $ai4seo_this_setting_name = AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);

        if (!is_string($ai4seo_this_setting_input_value)
            || !array_key_exists($ai4seo_this_setting_input_value, $ai4seo_focus_keyphrase_behavior_options)) {
            $ai4seo_this_setting_input_value = AI4SEO_DEFAULT_SETTINGS[$ai4seo_this_setting_name];
        }

        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Focus Keyphrase behavior", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' class='ai4seo-select'>";
                    foreach ($ai4seo_focus_keyphrase_behavior_options as $ai4seo_option_value => $ai4seo_option_label) {
                        $ai4seo_is_selected = ($ai4seo_option_value === $ai4seo_this_setting_input_value) ? " selected='selected'" : "";
                        echo "<option value='" . esc_attr($ai4seo_option_value) . "'" . $ai4seo_is_selected . ">" . esc_html($ai4seo_option_label) . "</option>";
                    }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses(__("Control how focus keyphrases are generated <strong>when a meta title and meta description already exist</strong> for an entry. This only affects SEO Autopilot (bulk generation).", "ai-for-seo"));
                    echo "<br><br>";
                    ai4seo_echo_wp_kses(__("<strong>Attention:</strong> For \"Regenerate metadata\", make sure that Meta Title and Meta Description are checked in the \"Overwrite Existing Metadata\" setting.", "ai-for-seo"));
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
                    ai4seo_echo_wp_kses(__("<strong>Disable:</strong> Disables all plugin meta tags. Only useful when completely syncing to other SEO plugins.", "ai-for-seo"));
                    echo "<br><br>";

                    // Force 'AI for SEO' Meta Tags
                    ai4seo_echo_wp_kses(__("<strong>Force:</strong> Outputs plugin meta tags regardless of other plugins. May create duplicates.", "ai-for-seo"));
                    echo "<br><br>";

                    // Replace Existing Meta Tags
                    ai4seo_echo_wp_kses(__("<strong>Replace (Recommended):</strong> Replaces existing meta tags, preventing duplicates and cleaning HTML header.", "ai-for-seo"));
                    echo "<br><br>";

                    // Complement Existing Meta Tags
                    ai4seo_echo_wp_kses(__("<strong>Complement:</strong> Adds missing meta tags without overwriting existing ones.", "ai-for-seo"));
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
        echo esc_html__("Media attributes", "ai-for-seo") . " <span style='font-size: small'>(" . esc_html__("for images", "ai-for-seo") . ")</span>";
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
                ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                    ai4seo_echo_wp_kses(ai4seo_get_generation_language_select_options_html($ai4seo_this_setting_input_value));
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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

        $ai4seo_attachment_placeholders_tooltip = __(
            "<strong>Available placeholders</strong> (case-insensitive):<br>{WEBSITE_URL} - Site URL.<br>{WEBSITE_NAME} - Site name.<br>{FILE_NAME} - File name without extension.<br>{FILE_TYPE} - File extension.<br>{FILE_SIZE} - File size in kilobytes.<br>{IMAGE_DIMENSIONS} - Image width x height.<br><br>Placeholders are replaced when attributes are saved or injected into the frontend.",
            "ai-for-seo"
        );

        // Divider
        echo "<hr class='ai4seo-form-item-divider'>";

        // Display form elements
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Prefix / Suffix:", "ai-for-seo") ;
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_attachment_placeholders_tooltip));
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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE ================================================ \\

        echo "<hr class='ai4seo-form-item-divider'>";

        $ai4seo_this_setting_name = AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = (bool) ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Let AI for SEO include your existing media attributes (existing alt text, title, caption or description) as additional context for generation.", "ai-for-seo");

        if (!$ai4seo_user_has_basic_plan_or_higher) {
            $ai4seo_this_setting_description .= ' ' . esc_html__("Requires Basic Plan or higher.", "ai-for-seo");
        }

        $ai4seo_plan_badge_html = ai4seo_get_plan_badge('s');

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Use existing media attributes as reference", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . ($ai4seo_user_has_basic_plan_or_higher ? "" : " disabled='disabled'") . " /> ";
                    echo esc_html__("Use existing media attributes as reference", "ai-for-seo");
                    echo " ";
                    ai4seo_echo_wp_kses($ai4seo_plan_badge_html);
                    echo "<br>";
                echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION ============================================================ \\

        echo "<hr class='ai4seo-form-item-divider'>";

        $ai4seo_this_setting_name = AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = (bool) ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Using a more complex prompt, our AI is able to find most brands, places, landmarks, companies, products, movie titles and more in your images.", "ai-for-seo");

        if (!$ai4seo_user_has_pro_plan_or_higher) {
            $ai4seo_this_setting_description .= ' ' . esc_html__("Requires Pro Plan or higher.", "ai-for-seo");
        }

        $ai4seo_plan_badge_html = ai4seo_get_plan_badge('m');

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Enhanced Entity Recognition", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . ($ai4seo_user_has_pro_plan_or_higher ? "" : " disabled='disabled'") . " /> ";
                    echo esc_html__("Enhanced Entity Recognition", "ai-for-seo");
                    echo " ";
                    ai4seo_echo_wp_kses($ai4seo_plan_badge_html);
                    echo "<br>";
                echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION ======================================================== \\

        echo "<hr class='ai4seo-form-item-divider'>";

        $ai4seo_this_setting_name = AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = (bool) ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = esc_html__("Using advanced face detection tools we are able to recognize 99% of all publicly known people in images.", "ai-for-seo");

        if (!$ai4seo_user_has_premium_plan_or_higher) {
            $ai4seo_this_setting_description .= ' ' . esc_html__("Requires Premium Plan.", "ai-for-seo");
        }

        $ai4seo_plan_badge_html = ai4seo_get_plan_badge('l');

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
                echo esc_html__("Enhanced Celebrity Recognition", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . ($ai4seo_user_has_premium_plan_or_higher ? "" : " disabled='disabled'") . " /> ";
                    echo esc_html__("Enhanced Celebrity Recognition", "ai-for-seo");
                    echo " ";
                    ai4seo_echo_wp_kses($ai4seo_plan_badge_html);
                    echo "<br>";
                echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES =========================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Generate media attributes for entries that already have complete attribute sets. Disable to only generate for entries missing attributes. Note: Make sure to enable at least one attribute in 'Overwrite Existing Media Attributes' to see any effect.", "ai-for-seo");

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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                ai4seo_echo_wp_kses(ai4seo_get_select_all_checkbox($ai4seo_this_setting_input_name));
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
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                        echo "<option value='" . esc_attr($ai4seo_this_duration) . "' " . selected($ai4seo_this_setting_input_value, $ai4seo_this_duration, false) . ">" . sprintf(esc_html(_n("%s minute", "%s minutes", ($ai4seo_this_duration / 60), "ai-for-seo")), esc_html(number_format_i18n($ai4seo_this_duration / 60))) . "</option>";
                    }
                echo "</select>";
                echo "<div class='ai4seo-medium-gap'></div>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // === AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION ========================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Inject alt text at render time and dynamically via JavaScript so images always include the correct alt text, even when themes or page builders omit it.", "ai-for-seo");
        $ai4seo_this_setting_description .= "<br><br>" . __("Enable this setting if your theme or page builder does not display alt text generated by the plugin (or omits alt text entirely).", "ai-for-seo");

        // Divider
        echo "<hr class='ai4seo-form-item-divider ai4seo-is-advanced-setting'>";

        // Display form elements
        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
            echo esc_html__("Alt Text Injection:", "ai-for-seo") ;

            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox ai4seo-alt-text-injection-toggle'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . "/> ";
                    echo esc_html__("Inject alt text", "ai-for-seo");
                    echo "<br>";
                    echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";

        $ai4seo_alt_injection_enabled = (bool) $ai4seo_this_setting_input_value;


        // === AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION ===================================================== \\

        $ai4seo_this_setting_name = AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION;
        $ai4seo_this_setting_input_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $ai4seo_this_setting_input_value = ai4seo_get_setting($ai4seo_this_setting_name);
        $ai4seo_this_setting_description = __("Load a JavaScript fallback that injects alt text on the frontend after the page loads. Disable if another script handles this or if only server-side injection should be used. Attention: Can cause slower page loads on some setups.", "ai-for-seo");

        $ai4seo_potential_js_alt_text_setting_hidden_class = $ai4seo_alt_injection_enabled ? '' : ' ai4seo-js-alt-text-setting-hidden';

        echo "<div class='ai4seo-form-item ai4seo-is-advanced-setting" . esc_attr($ai4seo_potential_js_alt_text_setting_hidden_class) . "' id='ai4seo-js-alt-text-injection-setting'>";
            echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                // new feature bubble # todo: remove bubble after some time
                echo "<span class='ai4seo-green-bubble'>" . esc_html__("NEW", "ai-for-seo") . "</span> ";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "' value='1' class='ai4seo-single-checkbox'" . ($ai4seo_this_setting_input_value ? " checked='checked'" : "") . "/> ";
                echo esc_html__("Inject alt text with JavaScript", "ai-for-seo");
                echo "<br>";
                echo "</label>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
            echo esc_html__("Image Title Injection:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                $ai4seo_title_injection_options = ai4seo_get_setting_render_level_title_injection_allowed_values();

                echo "<select id='" . esc_attr($ai4seo_this_setting_input_name) . "' name='" . esc_attr($ai4seo_this_setting_input_name) . "'>";
                foreach ($ai4seo_title_injection_options as $ai4seo_option_value => $ai4seo_option_label) {
                    echo '<option value="' . esc_attr( $ai4seo_option_value ) . '" ' .
                        selected( $ai4seo_option_value, $ai4seo_this_setting_input_value, false ) . '>' .
                        esc_html( $ai4seo_option_label ) .
                        '</option>';
                }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
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
                    echo '<option value="' . esc_attr( $ai4seo_option_value ) . '" ' .
                        selected( $ai4seo_option_value, $ai4seo_this_setting_input_value, false ) . '>' .
                        esc_html( $ai4seo_option_label ) .
                        '</option>';
                }
                echo "</select>";

                echo "<p class='ai4seo-form-item-description'>";
                    ai4seo_echo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
echo "</div>";

// Submit button
echo "<div class='ai4seo-sticky-buttons-bar'>";
    echo "<div class='ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_settings_inputs);' id='ai4seo-save-settings' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";
echo "</div>";