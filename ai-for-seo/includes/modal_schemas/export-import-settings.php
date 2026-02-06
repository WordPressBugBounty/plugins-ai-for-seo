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
                echo "<button type='button' onclick='ai4seo_init_export_settings();' id='ai4seo-export-settings-button' class='button ai4seo-button ai4seo-submit-button'>";
                    echo esc_html__("Save Changes & Export Settings", "ai-for-seo");
                echo "</button>";
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
            echo "<label for='ai4seo-import-file'>";
                echo esc_html__("Select Settings File (e.g. ai4seo-settings-XXX.json)", "ai-for-seo");
            echo "</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";

                // File input
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
                echo "<button type='button' onclick='ai4seo_init_import_settings();' id='ai4seo-import-settings-button' class='button ai4seo-button ai4seo-submit-button'>";
                    echo esc_html__("Show Preview", "ai-for-seo");
                echo "</button>";
                echo "<p class='ai4seo-form-item-description'>" . esc_html__("Upload a previously exported settings file and choose which settings to import.", "ai-for-seo") . "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    echo "<button type='button' onclick='ai4seo_close_modal_from_schema(\"export-import-settings\");' class='button ai4seo-button ai4seo-abort-button'>";
        echo esc_html__("Close", "ai-for-seo");
    echo "</button>";
echo "</div>";