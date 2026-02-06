<?php
/**
 * Modal Schema: Represents the Terms of Service modal.
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

// get the latest update to the terms of service, terms of conditions or privacy policy
$ai4seo_updated_tos_toc_or_pp_timestamp = ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp();
$ai4seo_latest_tos_and_toc_and_pp_version = ai4seo_get_latest_tos_and_toc_and_pp_version();

$ai4seo_datetime_of_change = ai4seo_format_unix_timestamp($ai4seo_updated_tos_toc_or_pp_timestamp, 'auto', '');

// last time we accepted this tos, toc and pp
$ai4seo_tos_toc_and_pp_accepted_time = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME);

// did we accept enhanced reporting before?
$ai4seo_enhanced_reporting_accepted = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED);

// we are inside the AI for SEO plugin?
$ai4seo_is_user_inside_plugin_admin_pages = ai4seo_is_user_inside_our_plugin_admin_pages();
$ai4seo_is_user_inside_installed_plugins_page = ai4seo_is_user_inside_installed_plugins_page();

$ai4seo_extended_data_collection_tooltip_text = esc_html__("This data includes feature usage, performance metrics, and error logs. It will be stored for up to 30 days to assist with improving the plugin. You can opt out of data collection at any time through the plugin settings.", "ai-for-seo");


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo "<center>";

    echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("full")) . "' class='ai4seo-tos-plugin-logo ai4seo-modal-headline-icon' /><br>";

    echo esc_html__("Terms of Service", "ai-for-seo");

    if ($ai4seo_tos_toc_and_pp_accepted_time) {
        echo "<p>" . sprintf(esc_html__("Please review and accept the updated Terms of Service, effective from %s.", "ai-for-seo"), esc_html($ai4seo_datetime_of_change)) . "</p>";
    } else {
        echo "<p>" . sprintf(esc_html__("Please accept the Terms of Service to start using the %s plugin.", "ai-for-seo"), "<span class='ai4seo-plugin-name'>" . esc_html(AI4SEO_PLUGIN_NAME) . "</span>") . "</p>";
    }

    echo "</center>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";
    echo "<div class='ai4seo-tos-box'>";

        echo "<div class='ai4seo-tos-version-number'>" . esc_html($ai4seo_latest_tos_and_toc_and_pp_version) . "</div>";

        // get the terms of service
        $tos_content = get_tos_content();

        if ($tos_content) {
            ai4seo_echo_wp_kses($tos_content);
        } else {
            echo "<p>" . esc_html__("The Terms of Service could not be loaded. Please try again later.", "ai-for-seo") . "</p>";
        }

    echo "</div>";

    // CHECKBOXES
    echo "<div class='ai4seo-tos-checkboxes-wrapper'>";
        // Checkbox "I have read and agree to the Terms and Conditions."
        echo "<div class='ai4seo-tos-checkbox ai4seo-accept-tos-checkbox-wrapper'>";
            echo "<input type='checkbox' class='ai4seo-accept-tos-checkbox' id='ai4seo-accept-tos-checkbox' name='ai4seo-accept-tos-checkbox' value='1' onchange='ai4seo_refresh_tos_accept_button_state();'>";
            echo "<label for='ai4seo-accept-tos-checkbox'><strong>" . esc_html__("I have read and agree to the Terms and Service.", "ai-for-seo") . "</strong></label>";
        echo "</div>";

        // only show enhanced reporting checkbox if the user has not accepted it before
        if (!$ai4seo_tos_toc_and_pp_accepted_time || !$ai4seo_enhanced_reporting_accepted) {
            // Checkbox "I agree to share extended data, stored for up to 30 days, to support the ongoing development of the plugin. I may opt out at any time."

            echo "<div class='ai4seo-tos-checkbox'>";
                echo "<input type='checkbox' class='ai4seo-accept-enhanced-reporting-checkbox' id='ai4seo-accept-enhanced-reporting-checkbox' name='ai4seo-accept-enhanced-reporting-checkbox' value='1'>";
                echo "<label for='ai4seo-accept-enhanced-reporting-checkbox'><strong>" . esc_html__("I agree to share extended data to support the ongoing development of the plugin. I may opt out at any time.", "ai-for-seo") . " (" . esc_html__("optional", "ai-for-seo") . ")" . "</strong></label>";
                ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag($ai4seo_extended_data_collection_tooltip_text));
            echo "</div>";
        }
    echo "</div>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    // reject button
    echo "<button type='button' onclick='ai4seo_confirm_to_reject_tos();' class='button ai4seo-button ai4seo-abort-button'>" . esc_html__("Reject & Uninstall", "ai-for-seo") . "</button>";

    // hide modal if we see this modal outside the plugin pages
    $additional_accept_tos_javascript = "";
    $additional_accept_tos_parameter = "";

    if (!$ai4seo_is_user_inside_plugin_admin_pages && !$ai4seo_is_user_inside_installed_plugins_page) {
        $additional_accept_tos_javascript = "ai4seo_hide_modal(this);";
        $additional_accept_tos_parameter = "false"; # do not reload page parameter
    }

    // accept button
    echo "<div onclick='ai4seo_check_if_user_accepted_tos();'>";
        echo "<button type='button' onclick='ai4seo_accept_tos(" . esc_js($additional_accept_tos_parameter) . ");" . esc_js($additional_accept_tos_javascript) . "' class='button ai4seo-button ai4seo-disabled-button ai4seo-modal-submit-button ai4seo-accept-tos-button'>" . esc_html__("Accept & Continue", "ai-for-seo") . "</button>";
    echo "</div>";
echo "</div>";