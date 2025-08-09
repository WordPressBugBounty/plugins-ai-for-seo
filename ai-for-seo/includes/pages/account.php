<?php
/**
 * Renders the content of the submenu page for the AI for SEO license page.
 *
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

$ai4seo_robhub_subscription = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION);

// Define boolean to determine whether to read license-data
$ai4seo_show_license_details = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);

// Define license variables
$ai4seo_license_username = ($ai4seo_show_license_details ? ai4seo_robhub_api()->get_api_username() : "");
$ai4seo_license_key = ($ai4seo_show_license_details ? ai4seo_robhub_api()->get_api_password() : "");

// Prepare enhanced reporting
$ai4seo_did_user_accept_enhanced_reporting = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED);
$ai4seo_enhanced_reporting_revoke_timestamp = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME);

// Define variables for the current username and email
$ai4seo_current_user = wp_get_current_user();
$ai4seo_current_user_username = ($ai4seo_current_user->user_login ?? "unknown");
$ai4seo_current_user_email = ($ai4seo_current_user->user_email ?? "unknown");

// Define variables for the settings
$ai4seo_setting_enable_incognito_mode = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_INCOGNITO_MODE);
$ai4seo_setting_enable_white_label = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_WHITE_LABEL);
$ai4seo_setting_plugin_name = ai4seo_get_setting(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME);
$ai4seo_setting_plugin_description = ai4seo_get_setting(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION);
$ai4seo_setting_display_source_code_notes = ai4seo_get_setting(AI4SEO_SETTING_ADD_GENERATOR_HINTS);
$ai4seo_setting_source_code_notes_content_start = ai4seo_get_setting(AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT);
$ai4seo_setting_source_code_notes_content_end = ai4seo_get_setting(AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT);

// Remove slashes from white-label text-fields
$ai4seo_setting_plugin_name = stripslashes($ai4seo_setting_plugin_name);
$ai4seo_setting_plugin_description = stripslashes($ai4seo_setting_plugin_description);
$ai4seo_setting_source_code_notes_content_start = stripslashes($ai4seo_setting_source_code_notes_content_start);
$ai4seo_setting_source_code_notes_content_end = stripslashes($ai4seo_setting_source_code_notes_content_end);

$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

$ai4seo_robhub_subscription_plan = $ai4seo_robhub_subscription["plan"] ?? "free";
$ai4seo_robhub_subscription_end_date_and_time = $ai4seo_robhub_subscription["subscription_end"] ?? false;
$ai4seo_robhub_subscription_end_timestamp = $ai4seo_robhub_subscription_end_date_and_time
    ? strtotime($ai4seo_robhub_subscription_end_date_and_time) : 0;
$ai4seo_user_is_on_free_plan = ($ai4seo_robhub_subscription_plan == "free") || $ai4seo_robhub_subscription_end_timestamp < time();
$ai4seo_has_purchased_something = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);


// ___________________________________________________________________________________________ \\
// === JAVASCRIPT ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

?><script type="text/javascript">
    // Function to display lost-key-modal
    function ai4seo_open_lost_key_modal() {
        // Define variables for the modal
        let modal_headline = wp.i18n.__("Lost your license data?", "ai-for-seo");
        let modal_content = wp.i18n.__("Please contact us for assistance. To help us resolve your issue quickly, kindly provide details such as your <strong>website domain</strong> and the <strong>email address</strong> used during the purchase.", "ai-for-seo");
        let modal_footer = "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__("Close", "ai-for-seo") + "</button> ";
        modal_footer += wp.i18n.sprintf(wp.i18n.__("<a href='%s' target='_blank' class='button ai4seo-button ai4seo-success-button'>Contact us</a>", "ai-for-seo"), ai4seo_official_contact_url);
        let modal_settings = {
            close_on_outside_click: true,
            add_close_button: true,
        }

        // Open notification modal
        ai4seo_open_notification_modal(modal_headline, modal_content, modal_footer, modal_settings);
    }
</script><?php


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-form'>";

    // ___________________________________________________________________________________________ \\
    // === LICENSE =============================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";

        // === HEADLINE ============================================================================== \\

        echo "<h2>";
            echo "<i class='dashicons dashicons-id-alt ai4seo-nav-tab-icon'></i>";
            echo esc_html__("License", "ai-for-seo");
        echo "</h2>";


        // === DESCRIPTION =========================================================================== \\

            echo "<div class='ai4seo-form-item-description' style='margin: 1rem; padding-bottom: 1rem;'>";
                // Show description in case of existing license
                if ($ai4seo_show_license_details) {
                    echo "<ol>";
                        echo "<li>" . esc_html__("Please make sure to save the username and license key somewhere safe in case your need to reconnect your website to your existing account.", "ai-for-seo") . "</li>";
                        echo "<li><strong>" . esc_html__("You can use these credentials on as many websites as you like which is especially convenient for SEO- and web agencies.", "ai-for-seo") . "</strong></li>";
                    echo "</ol>";
                }

                // Show description in case of missing license
                else {
                    echo "<ol>";
                        echo "<li>" . esc_html__("Here you can connect your website to an existing account in order to use the Credits from your main account.", "ai-for-seo") . "</li>";
                        echo "<li>" . esc_html__("Your credentials will be generated automatically when you purchase a plan or Credits and you will be able to find them here.", "ai-for-seo") . "</li>";
                        echo "<li>" . esc_html__("If you have lost your credentials, please contact us via the link below.", "ai-for-seo") . "</li>";
                    echo "<ol>";
                }
            echo "</div>";


        // === API USERNAME / LICENSE OWNER ========================================================== \\

        $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME);

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("License owner", "ai-for-seo") . ":</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) ."' autocomplete='off' value='" . esc_attr($ai4seo_license_username) . "' />";
            echo "</div>";
        echo "</div>";

        echo "<hr class='ai4seo-form-item-divider'>";


        // === API PASSWORD / LICENSE KEY =========================================================================== \\

        $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD);

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("License key", "ai-for-seo") . ":</label>";
            echo "<div class='ai4seo-form-item-input-wrapper' style='position:relative;'>";
                // Display only actual input-field for when there is no existing api-key
                if (!$ai4seo_license_key) {
                    echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_license_key) . "' />";
                }

                // Display both input-fields and toggle buttons if there is an existing api-key
                else {
                    // Input for the license key
                    echo "<div id='ai4seo-actual-license-key-holder' class='ai4seo-display-none'>";
                        echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_license_key) . "' />";
                    echo "</div>";

                    // Input for the "hidden" license key
                    echo "<div id='ai4seo-visual-license-key-holder'>";
                        echo "<input type='text' class='ai4seo-textfield ai4seo-element-inactive' autocomplete='off' value='************************************************' readonly='readonly' style='background-color:#eee!important;' />";
                    echo "</div>";

                    // Button to reveal license-key
                    echo "<div class='ai4seo-form-floating-textfield-icon-holder' onclick='jQuery(\"#ai4seo-visual-license-key-holder\").hide();jQuery(\"#ai4seo-actual-license-key-holder\").show();jQuery(this).hide();jQuery(this).next().show();'>";
                        echo ai4seo_wp_kses(ai4seo_get_svg_tag("eye", __("Reveal License Key", "ai-for-seo")));
                    echo "</div>";

                    // Button to hide license-key
                    echo "<div class='ai4seo-form-floating-textfield-icon-holder ai4seo-display-none' onclick='jQuery(\"#ai4seo-visual-license-key-holder\").show();jQuery(\"#ai4seo-actual-license-key-holder\").hide();jQuery(this).hide();jQuery(this).prev().show();'>";
                        echo ai4seo_wp_kses(ai4seo_get_svg_tag("eye-slash", __("Hide License Key", "ai-for-seo")));
                    echo "</div>";
                }
            echo "</div>";
        echo "</div>";

        echo "<hr class='ai4seo-form-item-divider'>";


        // === MANAGE CREDITS BUTTON ================================================================= \\

        echo "<div class='ai4seo-form-item' style='padding-top:0;'>";
            echo "<div class='ai4seo-buttons-wrapper' style='margin-top: 0; margin-bottom: 5px;'>";
                // Button to show lost-license-instructions
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "key", esc_html__("Lost your license data?", "ai-for-seo"), "", "ai4seo_open_lost_key_modal();"));

                // Button to manage subscription if user has an active subscription
                if (!$ai4seo_user_is_on_free_plan) {
                    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag(AI4SEO_STRIPE_BILLING_URL, "stripe", esc_html__("Manage Subscription", "ai-for-seo"), "", "", "_blank"));
                }

                // Customize pay-as-you-go
                if ($ai4seo_has_purchased_something) {
                    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "list", esc_html__("Customize Pay-As-You-Go", "ai-for-seo"), "", "ai4seo_handle_open_customize_payg_modal();"));
                }

                // Button to manage credits
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "arrow-up-right-from-square", esc_html__("Get more Credits", "ai-for-seo"), "", "ai4seo_open_get_more_credits_modal();"));
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === AGENCY MODE =========================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";


        // === HEADLINE ============================================================================== \\

        echo "<h2>";
            echo "<i class='dashicons dashicons-megaphone ai4seo-nav-tab-icon'></i>";
            echo esc_html__("For SEO and Web Agencies", "ai-for-seo");
        echo "</h2>";


        // === ENABLE INCOGNITO MODE ================================================================= \\

        $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_ENABLE_INCOGNITO_MODE);

        $ai4seo_this_setting_description = sprintf(
            __("By enabling this checkbox you can hide the plugin from all other users. This means that <strong><u>only you</u> (%s, %s)</strong> will be able to generate data, access and edit plugin settings and see the menu item in the header and the main menu of your website. Please note that the plugin will still be visible in the plugin list to other users. However, you may white-label the appearance using the settings below.", "ai-for-seo"),
            $ai4seo_current_user_username,
            $ai4seo_current_user_email
        );

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Incognito Mode", "ai-for-seo") . ":</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Input
                echo "<input type='checkbox' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' class='ai4seo-single-checkbox' " . ($ai4seo_setting_enable_incognito_mode ? " checked='checked'" : "") . " /> ";
                echo esc_html__("Enable Incognito Mode", "ai-for-seo");

                // Description
                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";

        echo "<hr class='ai4seo-form-item-divider'>";


        // === ENABLE WHITE-LABEL ==================================================================== \\

        $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_ENABLE_WHITE_LABEL);

        $ai4seo_this_setting_description = __("Enabling this option will give you access to change the display of certain plugin related information (i.e. the plugin name).", "ai-for-seo");

        echo "<div class='ai4seo-form-item'>";
            echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("White-Label Mode", "ai-for-seo") . ":</label>";
            echo "<div class='ai4seo-form-item-input-wrapper'>";
                // Input
                echo "<input type='checkbox' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' class='ai4seo-single-checkbox' onchange='ai4seo_toggle_visibility_on_checkbox(jQuery(this), jQuery(\".ai4seo-white-label-only-container\"))'" . ($ai4seo_setting_enable_white_label ? " checked='checked'" : "") . " /> ";
                echo esc_html__("Enable White-Label Mode", "ai-for-seo");

                // Description
                echo "<p class='ai4seo-form-item-description'>";
                    echo ai4seo_wp_kses($ai4seo_this_setting_description);
                echo "</p>";
            echo "</div>";
        echo "</div>";


        // ___________________________________________________________________________________________ \\
        // === HIDDEN WHITE LABEL ELEMENTS =========================================================== \\
        // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

        echo "<div class='ai4seo-white-label-only-container" . ($ai4seo_setting_enable_white_label ? "" : " ai4seo-display-none") . "'>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === PLUGIN NAME =========================================================================== \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME);

            $ai4seo_this_setting_description = ai4seo_wp_kses(sprintf(
                __("Here you can define the plugin name that will be shown on the <a href='%s'>installed plugins page</a> of your website.", "ai-for-seo"),
                esc_url(admin_url("plugins.php"))
            ));

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Overwrite 'Installed Plugins' Page Plugin Name", "ai-for-seo") . ":</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    // Input
                    echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_setting_plugin_name) . "' minlength='3' maxlength='100' />";

                    // Description
                    echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                echo "</div>";
            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === PLUGIN DESCRIPTION ==================================================================== \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION);

            $ai4seo_this_setting_description = ai4seo_wp_kses(sprintf(
                __("Here you can define the plugin description that will be shown on the <a href='%s'>installed plugins page</a> of your website.", "ai-for-seo"),
                esc_url(admin_url("plugins.php"))
            ));

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Overwrite 'Installed Plugins' Page Plugin Description", "ai-for-seo") . ":</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    // Input
                    echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_setting_plugin_description) . "' minlength='3' maxlength='140' />";

                    // Description
                    echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                echo "</div>";
            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === ADD GENERATOR HINTS ============================================================= \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_ADD_GENERATOR_HINTS);

            $ai4seo_this_setting_description = __("With this setting you can decide whether to display a comment block before and after the meta tags block generated by the plugin in the <u>source code</u> of your website.", "ai-for-seo");

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Generator Hints (Source Code)", "ai-for-seo") . ":</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    // Input
                    echo "<input type='checkbox' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' class='ai4seo-single-checkbox' onchange='ai4seo_toggle_visibility_on_checkbox(jQuery(this), jQuery(\".ai4seo-source-code-adjustments-only-container\"))' " . ($ai4seo_setting_display_source_code_notes ? " checked='checked'" : "") . " /> ";
                    echo esc_html__("Add Generator Hints", "ai-for-seo");

                    // Description
                    echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";
                echo "</div>";
            echo "</div>";


            // ___________________________________________________________________________________________ \\
            // === HIDDEN SOURCE CODE ADJUSTMENTS ======================================================== \\
            // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

            echo "<div class='ai4seo-source-code-adjustments-only-container" . ($ai4seo_setting_display_source_code_notes ? "" : " ai4seo-display-none") . "'>";

                echo "<hr class='ai4seo-form-item-divider'>";

                // === Meta Tags Block Starting Hint ======================================================= \\

                $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT);

                $ai4seo_this_setting_description = __("Here you can define the content of the comment block that will be displayed before the meta tags generated by the plugin in the source code of your website.", "ai-for-seo") . "<br /><br />";
                $ai4seo_this_setting_description .= esc_html__("Possible placeholders:", "ai-for-seo") . " {NAME} = " . esc_html(AI4SEO_PLUGIN_NAME) . ", {VERSION} = " . esc_html(AI4SEO_PLUGIN_VERSION_NUMBER) . ", {WEBSITE} = " . esc_html(AI4SEO_OFFICIAL_WEBSITE);

                echo "<div class='ai4seo-form-item'>";
                    echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Meta Tags Block Starting Hint", "ai-for-seo") . ":</label>";
                    echo "<div class='ai4seo-form-item-input-wrapper'>";
                        // Input
                        echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_setting_source_code_notes_content_start) . "' minlength='3' maxlength='250' />";

                        // Description
                        echo "<p class='ai4seo-form-item-description'>";
                            echo ai4seo_wp_kses($ai4seo_this_setting_description);
                        echo "</p>";
                    echo "</div>";
                echo "</div>";

                echo "<hr class='ai4seo-form-item-divider'>";
                

                // === Meta Tags Block Ending Hint ========================================================= \\

                $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT);

                $ai4seo_this_setting_description = __("Here you can define the content of the comment block that will be displayed after the meta tags generated by the plugin in the source code of your website.", "ai-for-seo") . "<br /><br>";
                $ai4seo_this_setting_description .= esc_html__("Possible placeholders: ", "ai-for-seo") . "{NAME} = " . esc_html(AI4SEO_PLUGIN_NAME);

                echo "<div class='ai4seo-form-item'>";
                    echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Meta Tags Block Ending Hint", "ai-for-seo") . ":</label>";
                    echo "<div class='ai4seo-form-item-input-wrapper'>";
                        // Input
                        echo "<input type='text' class='ai4seo-textfield' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' autocomplete='off' value='" . esc_attr($ai4seo_setting_source_code_notes_content_end) . "' minlength='3' maxlength='250' />";

                        // Description
                        echo "<p class='ai4seo-form-item-description'>";
                            echo ai4seo_wp_kses($ai4seo_this_setting_description);
                        echo "</p>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === PRIVACY AND AGREEMENTS ================================================================ \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='card ai4seo-form-section'>";
        // Headline
        echo "<h2>";
            echo '<i class="dashicons dashicons-shield ai4seo-nav-tab-icon"></i>';
            echo esc_html__("Privacy & Agreements", "ai-for-seo");
        echo "</h2>";

        // TERMS OF SERVICE BUTTON
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Terms of Service", "ai-for-seo") . ":";
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";
                if (ai4seo_does_user_need_to_accept_tos_toc_and_pp(false)) {
                    echo "<button type='button' class='button ai4seo-button' onclick='ai4seo_open_modal_from_schema(\"tos\", {modal_css_class: \"ai4seo-tos-modal\", modal_size: \"auto\"})'>";
                        echo ai4seo_wp_kses(ai4seo_get_svg_tag("arrow-up-right-from-square")) . " ";
                        echo esc_html__("Show Terms of Service", "ai-for-seo");
                    echo "</button>";
                } else {
                    echo "<button type='button' class='button ai4seo-button' onclick='ai4seo_open_ajax_modal(\"ai4seo_show_terms_of_service\", {}, {modal_size: \"small\"})'>";
                        echo ai4seo_wp_kses(ai4seo_get_svg_tag("arrow-up-right-from-square")) . " ";
                        echo esc_html__("Show Terms of Service", "ai-for-seo");
                    echo "</button>";
                }

                echo "<p class='ai4seo-form-item-description'>";
                    $latest_tos_and_toc_and_pp_version = ai4seo_get_latest_tos_and_toc_and_pp_version();
                    echo esc_html(sprintf(__("Current version: %s", "ai-for-seo"), $latest_tos_and_toc_and_pp_version)) . ".<br><br>";
                    echo ai4seo_wp_kses(ai4seo_get_tos_toc_and_pp_accepted_time_output());
                echo "</p>";
            echo "</div>";
        echo "</div>";

        echo "<hr class='ai4seo-form-item-divider'>";

        // ENHANCED REPORTING
        echo "<div class='ai4seo-form-item'>";
            echo "<label>";
                echo esc_html__("Enhanced Reporting:", "ai-for-seo") ;
            echo "</label>";

            echo "<div class='ai4seo-form-item-input-wrapper'>";

                $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED);

                // Checkbox "I agree to share extended data, stored for up to 30 days, to support the ongoing development of the plugin. I may opt out at any time."
                $extended_data_collection_tooltip_text = __("This data includes feature usage, performance metrics, and error logs. It will be stored for up to 30 days to assist with improving the plugin. You can opt out of data collection at any time through the plugin settings.", "ai-for-seo");

                echo "<div style='max-width: 400px;'>";
                    echo "<input type='checkbox' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' class='ai4seo-single-checkbox' " . ($ai4seo_did_user_accept_enhanced_reporting ? " checked='checked'" : "") . ">";
                    echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("I agree to share extended data to support the ongoing development of the plugin. I may opt out at any time.", "ai-for-seo") . ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag($extended_data_collection_tooltip_text)) . "</label>";
                echo "</div>";

                echo "<p class='ai4seo-form-item-description'>";
                    // revoked?
                    if (!$ai4seo_did_user_accept_enhanced_reporting && $ai4seo_enhanced_reporting_revoke_timestamp) {
                        $ai4seo_readable_revoked_time = ai4seo_format_unix_timestamp($ai4seo_enhanced_reporting_revoke_timestamp);
                        echo ai4seo_wp_kses(ai4seo_get_svg_tag("square-xmark", "", "ai4seo-16x16-icon ai4seo-red-icon")) . " ";
                        echo sprintf(esc_html__("Revoked on %s.", "ai-for-seo"), esc_html($ai4seo_readable_revoked_time));
                    } else {
                        echo ai4seo_wp_kses(ai4seo_get_enhanced_reporting_accepted_time_output());
                    }
                echo "</p>";
            echo "</div>";
        echo "</div>";
    echo "</div>";

    // Submit button
    echo "<div class='ai4seo-buttons-wrapper'>";
        echo "<button type='button' onclick='ai4seo_save_anything(jQuery(this), ai4seo_validate_account_inputs);' class='button ai4seo-button ai4seo-submit-button ai4seo-big-button'>" . esc_html__("Save changes", "ai-for-seo") . "</button>";
    echo "</div>";
echo "</div>";