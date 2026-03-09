<?php
/**
 * Modal Schema: Export/Import Settings
 *
 * @since 2.1.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo esc_html__("Export/Import Settings", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";

    // Export Section
    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("file-arrow-down"));
            echo esc_html__("Export Settings", "ai-for-seo");
        echo "</h2>";

        echo "<div class='ai4seo-form-item'>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                ai4seo_echo_wp_kses(ai4seo_get_button_tag(esc_html__("Save Changes & Export Settings", "ai-for-seo"), "ai4seo-export-settings-button", "ai4seo_init_export_settings();"));
                echo "<div class='ai4seo-medium-gap'></div>";
                echo "<p class='ai4seo-form-item-description'>" . esc_html__("Download your current plugin settings as a JSON file for backup or transfer to another site.", "ai-for-seo") . "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";

    // Import Section
    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
            ai4seo_echo_wp_kses(ai4seo_get_svg_tag("file-arrow-up"));
            echo esc_html__("Import Settings", "ai-for-seo");
        echo "</h2>";

        // File
        echo "<div class='ai4seo-form-item'>";
            echo "<div style='margin-bottom: 10px; font-weight: bold;'>";
                echo esc_html__("Select Settings File (e.g. sooz-settings-XXX.json)", "ai-for-seo");
            echo "</div>";

            // File input
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<input type='file' id='ai4seo-import-file' accept='.json' class='ai4seo-file-input' />";
            echo "</div>";
        echo "</div>";

        // Categories to import
        echo "<div class='ai4seo-form-item'>";
            echo "<label>" . esc_html__("Select Categories to Import", "ai-for-seo") . "</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<label for='ai4seo-import-settings-page-checkbox' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='ai4seo-import-settings-page-checkbox' value='settings' checked /> ";
                    echo esc_html__("Settings (This Page)", "ai-for-seo");
                echo "</label>";
                echo "<label for='ai4seo-import-account-page-checkbox' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='ai4seo-import-account-page-checkbox' value='account' checked /> ";
                    echo esc_html__("Account Settings (Without Credentials)", "ai-for-seo");
                echo "</label>";
                echo "<label for='ai4seo-import-seo-autopilot-checkbox' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='ai4seo-import-seo-autopilot-checkbox' value='seo_autopilot' /> ";
                    echo esc_html__("SEO Autopilot Settings", "ai-for-seo");
                echo "</label>";
                echo "<label for='ai4seo-import-get-more-credits-checkbox' class='ai4seo-form-multiple-inputs'>";
                    echo "<input type='checkbox' id='ai4seo-import-get-more-credits-checkbox' value='get_more_credits' /> ";
                    echo esc_html__("Get More Credits Settings", "ai-for-seo");
                echo "</label>";
            echo "</div>";
        echo "</div>";

        // button to import settings
        echo "<div class='ai4seo-form-item'>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                ai4seo_echo_wp_kses(ai4seo_get_submit_button_tag(esc_html__("Show Preview", "ai-for-seo"), "ai4seo-import-settings-button ai4seo-start-inactive", "ai4seo_init_import_settings();"));
                echo "<p class='ai4seo-form-item-description'>" . esc_html__("Upload a previously exported settings file and choose which settings to import.", "ai-for-seo") . "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    ai4seo_echo_wp_kses(ai4seo_get_modal_close_button_tag());
echo "</div>";