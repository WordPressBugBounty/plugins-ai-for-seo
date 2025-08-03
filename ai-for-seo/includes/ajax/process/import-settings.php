<?php
/**
 * AJAX handler for validating and importing settings file
 *
 * @since 2.1.0
 */

if (!defined("ABSPATH")) {
    exit;
}

// Check user permissions
if (!ai4seo_can_manage_this_plugin()) {
    return;
}

global $ai4seo_settings;

$ai4seo_all_categories = array(
    'settings',
    'seo_autopilot',
    'account',
    'get_more_credits'
);

// get mode (preview or execute)
$ai4seo_import_mode = isset($_REQUEST['ai4seo_import_mode']) && $_REQUEST['ai4seo_import_mode'] === 'execute' ? 'execute' : 'preview';

// get categories to import
$ai4seo_import_categories = isset($_REQUEST['ai4seo_import_categories']) ? ai4seo_deep_sanitize((array) $_REQUEST['ai4seo_import_categories']) : array();

if (!$ai4seo_import_categories) {
    ai4seo_return_error_as_json("No categories selected for import.", 4196725);
}

// get new settings to import
$ai4seo_new_settings_raw = isset($_REQUEST['ai4seo_new_settings']) ? ai4seo_deep_sanitize((array) $_REQUEST['ai4seo_new_settings']) : array();

if (!$ai4seo_new_settings_raw) {
    ai4seo_return_error_as_json("No settings data provided for import.", 4296725);
}


// ___________________________________________________________________________________________ \\
// === FUNCTIONS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_setting_category($setting_name): string {
    if (in_array($setting_name, AI4SEO_ALL_SETTING_PAGE_SETTINGS)) {
        return 'settings';
    } else if (in_array($setting_name, AI4SEO_ALL_SEO_AUTOPILOT_SETTINGS)) {
        return 'seo_autopilot';
    } else if (in_array($setting_name, AI4SEO_ALL_ACCOUNT_PAGE_SETTINGS)) {
        return 'account';
    } else if (in_array($setting_name, AI4SEO_ALL_GET_MORE_CREDITS_MODAL_SETTINGS)) {
        return 'get_more_credits';
    } else {
        return 'unknown';
    }
}

// =========================================================================================== \\

function ai4seo_make_nicer_setting_name($setting_name): string {
    // Convert setting name to a more readable format
    $setting_name = str_replace('_', ' ', $setting_name);
    $setting_name = ucwords($setting_name);
    return $setting_name;
}

// =========================================================================================== \\

function ai4seo_make_nicer_setting_value($setting_value): string {
    // Convert setting value to a more readable format
    if (is_array($setting_value)) {
        // key-value pairs if keys are not numeric, otherwise just implode
        if (array_keys($setting_value) !== range(0, count($setting_value) - 1)) {
            $formatted_values = array();
            foreach ($setting_value as $key => $value) {
                $formatted_values[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
            return implode(', ', $formatted_values);
        } else {
            // if numeric keys, just implode the values
            return htmlspecialchars(implode(', ', $setting_value), ENT_QUOTES, 'UTF-8');
        }
    } else if (is_bool($setting_value) || is_null($setting_value)) {
        return $setting_value ? esc_html__('Yes', 'ai-for-seo') : esc_html__('No', 'ai-for-seo');
    } else {
        return htmlspecialchars((string) $setting_value, ENT_QUOTES, 'UTF-8');
    }
}


// ___________________________________________________________________________________________ \\
// === FILTER BY CATEGORIES ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_categorized_new_settings = array();
$ai4seo_got_unknown_category_entries = false;

foreach ($ai4seo_new_settings_raw as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value) {
    $ai4seo_this_setting_category = ai4seo_get_setting_category($ai4seo_this_setting_name);

    if (in_array($ai4seo_this_setting_category, $ai4seo_import_categories)) {
        $ai4seo_categorized_new_settings[$ai4seo_this_setting_category][$ai4seo_this_setting_name] = $ai4seo_this_setting_new_value;
    }

    if ($ai4seo_this_setting_category === 'unknown') {
        $ai4seo_got_unknown_category_entries = true;
    }
}


// ___________________________________________________________________________________________ \\
// === VALIDATE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_invalid_settings = array();
$ai4seo_not_selected_category_settings = array();
$ai4seo_validated_new_settings = array();

foreach ($ai4seo_categorized_new_settings AS $ai4seo_this_category => $ai4seo_this_settings) {
    foreach ($ai4seo_this_settings AS $ai4seo_this_setting_name => $ai4seo_this_setting_new_value) {
        // Get the current value from the settings
        $ai4seo_current_value = $ai4seo_settings[$ai4seo_this_setting_name] ?? null;

        // convert the new value to the same type as the current value
        if (is_int($ai4seo_current_value)) {
            $ai4seo_this_setting_new_value = (int) $ai4seo_this_setting_new_value;
        } else if (is_float($ai4seo_current_value)) {
            $ai4seo_this_setting_new_value = (float) $ai4seo_this_setting_new_value;
        } else if (is_bool($ai4seo_current_value)) {
            // handle boolean values
            if ($ai4seo_this_setting_new_value === "true") {
                $ai4seo_this_setting_new_value = true;
            } else if ($ai4seo_this_setting_new_value === "false") {
                $ai4seo_this_setting_new_value = false;
            }

            $ai4seo_this_setting_new_value = filter_var($ai4seo_this_setting_new_value, FILTER_VALIDATE_BOOLEAN);
        } else if (is_string($ai4seo_current_value)) {
            $ai4seo_this_setting_new_value = stripslashes($ai4seo_this_setting_new_value);
        }

        if(ai4seo_validate_setting_value($ai4seo_this_setting_name, $ai4seo_this_setting_new_value)) {
            // these settings are valid and can be imported
            if (in_array($ai4seo_this_category, $ai4seo_import_categories)) {
                $ai4seo_validated_new_settings[$ai4seo_this_setting_name] = $ai4seo_this_setting_new_value;
            } else {
                // If the setting is valid but not in the selected categories, store it for later
                $ai4seo_not_selected_category_settings[$ai4seo_this_setting_name] = $ai4seo_this_setting_new_value;
            }
        } else {
            $ai4seo_invalid_settings[$ai4seo_this_setting_name] = $ai4seo_this_setting_name;
        }
    }
}

$ai4seo_we_got_valid_settings = !empty($ai4seo_validated_new_settings);


// ___________________________________________________________________________________________ \\
// === COMPARE WITH OLD VALUE ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_and_validated_changes = array();

foreach ($ai4seo_validated_new_settings as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value) {
    // Get the current value from the settings
    $ai4seo_current_value = $ai4seo_settings[$ai4seo_this_setting_name] ?? null;

    if ($ai4seo_current_value !== $ai4seo_this_setting_new_value) {
        // If the current value is different from the new value, prepare for comparison
        $ai4seo_new_and_validated_changes[$ai4seo_this_setting_name] = array(
            'current' => $ai4seo_current_value,
            'new' => $ai4seo_this_setting_new_value,
            'category' => ai4seo_get_setting_category($ai4seo_this_setting_name)
        );
    }
}

$ai4seo_we_got_valid_changes = !empty($ai4seo_new_and_validated_changes);


// ___________________________________________________________________________________________ \\
// === PREVIEW ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_import_mode === 'preview') {

    // HEADLINE
    echo "<div class='ai4seo-modal-headline'>";
        echo "<div class='ai4seo-modal-headline-icon'>";
            echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("64x64")) . "' />";
        echo "</div>";

        echo esc_html__("Preview Settings Import", "ai-for-seo");
    echo "</div>";

    // show extra hint if we have entries in $ai4seo_invalid_settings or $ai4seo_got_unknown_category_entries
    if ($ai4seo_we_got_valid_settings) {
        if ($ai4seo_we_got_valid_changes) {
            if (!empty($ai4seo_invalid_settings) || $ai4seo_got_unknown_category_entries) {
                echo "<div class='ai4seo-medium-gap'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation")) . " ";
                echo esc_html__("ATTENTION: Some settings are invalid or belong to unknown categories and cannot be imported. However, the valid settings can be imported.", "ai-for-seo");
                echo "</div>";
            }
        } else {
            echo "<div class='ai4seo-medium-gap'>";
                echo ai4seo_wp_kses(ai4seo_get_svg_tag("circle-check")) . " ";
                echo esc_html__("Your settings are up-to-date and no changes were detected.", "ai-for-seo");
            echo "</div>";
        }
    } else {
        echo "<div class='ai4seo-medium-gap'>";
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation")) . " ";
            echo esc_html__("No valid settings found for import.", "ai-for-seo");
        echo "</div>";
    }

    // go through each category and its settings
    foreach ($ai4seo_categorized_new_settings AS $ai4seo_this_category => $ai4seo_this_settings) {
        if (empty($ai4seo_this_settings)) {
            continue; // Skip empty categories
        }

        if ($ai4seo_this_category && !in_array($ai4seo_this_category, $ai4seo_import_categories)) {
            continue; // Skip categories not selected for import
        }

        $ai4seo_is_unknown_category = false;

        switch ($ai4seo_this_category) {
            case 'settings':
                $this_label = esc_html__("Settings (This Page)", "ai-for-seo");
                break;
            case 'account':
                $this_label = esc_html__("Account Settings (Without Credentials)", "ai-for-seo");
                break;
            case 'seo_autopilot':
                $this_label = esc_html__("SEO Autopilot Settings", "ai-for-seo");
                break;
            case 'get_more_credits':
                $this_label = esc_html__("Get More Credits Settings", "ai-for-seo");
                break;
            default:
                $ai4seo_is_unknown_category = true;
                $this_label = ai4seo_get_svg_tag("triangle-exclamation") . " " . esc_html__("Unknown Settings (Cannot be imported)", "ai-for-seo");
                break;
        }

        echo "<h3 style='margin-top: 3rem;'>" . ai4seo_wp_kses($this_label) . "</h3>";
        echo "<ul>";

        $ai4seo_this_got_any_valid_changes = false;

        foreach ($ai4seo_this_settings AS $ai4seo_this_setting_name => $ai4seo_this_setting_new_value) {
            if (!isset($ai4seo_new_and_validated_changes[$ai4seo_this_setting_name])) {
                continue;
            }

            $ai4seo_this_new_and_validated_change = $ai4seo_new_and_validated_changes[$ai4seo_this_setting_name];

            // invalid settings are shown with a strikethrough
            if ($ai4seo_is_unknown_category || in_array($ai4seo_this_setting_name, $ai4seo_invalid_settings)) {
                echo "<li style='text-decoration: line-through; color: red;'>- " . ai4seo_wp_kses(ai4seo_get_svg_tag("triangle-exclamation")) . " ";
            } else {
                $ai4seo_this_got_any_valid_changes = true;
                echo "<li>- ";
            }

            // make nicer setting name
            $ai4seo_this_setting_name = ai4seo_make_nicer_setting_name($ai4seo_this_setting_name);

            // make nicer current setting value
            $ai4seo_this_current_setting_value = ai4seo_make_nicer_setting_value($ai4seo_this_new_and_validated_change['current']);

            // make nicer new setting value
            $ai4seo_this_setting_new_value = ai4seo_make_nicer_setting_value($ai4seo_this_new_and_validated_change['new']);

            echo "<strong>" . esc_html($ai4seo_this_setting_name) . ":</strong> ";
            echo "<span style='color: #888; text-decoration: line-through;'>";
                echo ai4seo_wp_kses($ai4seo_this_current_setting_value);
            echo "</span> → ";

            echo ai4seo_wp_kses($ai4seo_this_setting_new_value);;

            echo "</li>";
        }

        echo "</ul>";

        if (!$ai4seo_this_got_any_valid_changes) {
            echo "<div class='ai4seo-medium-gap'>";
            echo ai4seo_wp_kses(ai4seo_get_svg_tag("circle-check")) . " ";
            echo esc_html__("No changes were detected in this settings category.", "ai-for-seo");
            echo "</div>";
        }
    }

    // Buttons
    echo "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>";
        // abort button
        echo "<button type='button' onclick='ai4seo_close_modal_by_child(this)' class='button ai4seo-button ai4seo-abort-button ai4seo-big-button'>" . esc_html__("Abort", "ai-for-seo") . "</button>";
    if ($ai4seo_we_got_valid_changes) {
        echo "<button type='button' onclick='ai4seo_execute_import_settings(this);' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button'>";
            echo esc_html__("Import Settings", "ai-for-seo");
        echo "</button>";
    }
    echo "</div>";

    return;
}


// ___________________________________________________________________________________________ \\
// === EXECUTE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_import_mode === 'execute') {
    if (!$ai4seo_we_got_valid_settings) {
        ai4seo_return_error_as_json("No valid settings found for import.", 8137725);
    }

    if (!$ai4seo_we_got_valid_changes || !$ai4seo_new_and_validated_changes || !is_array($ai4seo_new_and_validated_changes)) {
        ai4seo_return_error_as_json("No changes detected in the selected settings.", 9137725);
    }

    // prepare $_POST to be used by save-anything function
    foreach ($ai4seo_new_and_validated_changes as $ai4seo_this_setting_name => $ai4seo_this_new_and_validated_change) {
        // Update the setting with the new value
        $ai4seo_this_setting_name = ai4seo_get_prefixed_input_name($ai4seo_this_setting_name);
        $_POST[$ai4seo_this_setting_name] = $ai4seo_this_new_and_validated_change['new'];
    }

    ai4seo_save_anything();

    return;
}