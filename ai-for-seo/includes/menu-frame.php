<?php
/**
 * Renders the content of the submenu page for the AI for SEO overview page.
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

$ai4seo_dashboard_url = ai4seo_get_subpage_url("dashboard");
$ai4seo_is_dashboard_open = ai4seo_is_plugin_page_active("dashboard");
$ai4seo_settings_page_url = ai4seo_get_subpage_url("settings");
$ai4seo_media_page_url = ai4seo_get_subpage_url("media");
$ai4seo_account_page_url = ai4seo_get_subpage_url("account");
$ai4seo_help_page_url = ai4seo_get_subpage_url("help");

$ai4seo_active_plugin_page = ai4seo_get_active_subpage();
$ai4seo_current_post_type = ai4seo_get_active_post_type_subpage();

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();
$ai4seo_supported_post_types = ai4seo_get_supported_post_types();

if ($ai4seo_is_dashboard_open) {
    $ai4seo_unread_notifications_count = 0;
} else {
    $ai4seo_unread_notifications_count = ai4seo_get_num_unread_notification();
}

// maybe perform a performance analysis, do it here as we rely on a fully loaded WP environment
if ($ai4seo_is_dashboard_open) {
    ai4seo_check_for_performance_analysis();
}


// ___________________________________________________________________________________________ \\
// === OUTPUT: STYLES ======================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

?><style>
    /* hide wordpress footer */
    #wpfooter {
        display: none;
    }

    #wpbody-content {
        padding-bottom: 0;
    }
</style><?php


// ___________________________________________________________________________________________ \\
// === OUTPUT: JUST PURCHASED MODAL ========================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

# workaround: amp; is added to the url when the user is redirected from stripe
if (isset($_GET["ai4seo-just-purchased"]) || isset($_GET["amp;ai4seo-just-purchased"])) {
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time());

    // --- JAVASCRIPT --------------------------------------------------------- \\
    ?><script type="text/javascript">
        jQuery(function() {
            // open modal
            ai4seo_open_generic_success_notification_modal(
                "<?=esc_js(esc_html__("Your Credits will appear on your dashboard shortly.", "ai-for-seo"))?>"
                + "<br><br><?=esc_js(esc_html__("Please wait a moment and then click the button below to refresh your Credits balance.", "ai-for-seo"))?>",
                "<button type='button' class='ai4seo-button ai4seo-inactive-button ai4seo-inactive-countdown-button' data-time-left='5' onclick='ai4seo_refresh_robhub_account(this, { check_for_purchase: true }); return false;'>"
                + "<?=esc_js(esc_html__("Refresh Credits Balance", "ai-for-seo"))?>"
                + "</button>",
                {
                    headline: "<?=esc_js(esc_html__("Thank you for your purchase!", "ai-for-seo"))?>",
                    close_on_outside_click: false,
                    add_close_button: false,
                }
            );
        });
    </script><?php
    // ------------------------------------------------------------------------ \\
}


// ___________________________________________________________________________________________ \\
// === OUTPUT: TOP BAR / NAVIGATION (MOBILE) ================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-mobile-top-bar'>";
    // toggle button
    echo "<button class='ai4seo-mobile-top-bar-toggle-button' onclick='ai4seo_toggle_sidebar();'>";
        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("bars-sort"));
    echo "</button>";

        // Main logo
        echo "<div class='ai4seo-top-bar-headline'>";
            echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("64x64")) . "' alt='" . esc_attr(AI4SEO_PLUGIN_NAME) . "' class='ai4seo-logo' />";
            echo esc_html(AI4SEO_PLUGIN_NAME);
            //echo " <span class='ai4seo-sidebar-version-number'>v" . esc_html(AI4SEO_PLUGIN_VERSION_NUMBER) . "</span>";

            if (ai4seo_robhub_api()->are_we_using_local_api()) {
                echo "<div class='ai4seo-local-mode-hint ai4seo-blink-animation'>[LOCAL MODE]</div>";
            }
        echo "</div>";
echo "</div>";

echo "<div class='wrap ai4seo-wrap'>";


    // ___________________________________________________________________________________________ \\
    // === OUTPUT: SIDE BAR / NAVIGATION (DESKTOP) ======================================================= \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='ai4seo-sidebar'>";

        // Main logo
        echo "<div class='ai4seo-sidebar-headline'>";
            echo "<img src='" . esc_url(ai4seo_get_ai_for_seo_logo_url("64x64")) . "' alt='" . esc_attr(AI4SEO_PLUGIN_NAME) . "' class='ai4seo-logo' />";
            echo esc_html(AI4SEO_PLUGIN_NAME);

            if (ai4seo_robhub_api()->are_we_using_local_api()) {
                echo "<div class='ai4seo-local-mode-hint ai4seo-blink-animation'>[LOCAL MODE]</div>";
            }
        echo "</div>";

        echo "<nav class='nav-tab-wrapper ai4seo-menu-items-wrapper'>";
            // Dashboard page
            echo "<a href='" . esc_url($ai4seo_dashboard_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_is_dashboard_open ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                echo '<i class="dashicons dashicons-dashboard ai4seo-menu-item-icon"></i>';
                echo "<span>";
                    echo esc_html__("Dashboard", "ai-for-seo");

                    // unread notifications count
                    if ($ai4seo_unread_notifications_count > 0) {
                        echo "<span class='ai4seo-menu-counter'>" . esc_html($ai4seo_unread_notifications_count) . "</span>";
                    }

                echo "</span>";
            echo "</a>";

            // Pages for supported post-types
            foreach ($ai4seo_supported_post_types AS $ai4seo_this_post_type) {
                $ai4seo_this_menu_item_label = ai4seo_get_post_type_translation($ai4seo_this_post_type, true);
                $ai4seo_this_menu_item_label = ai4seo_get_nice_label($ai4seo_this_menu_item_label);
                $ai4seo_this_menu_item_icon = ai4seo_get_dashicon_tag_for_navigation($ai4seo_this_post_type);
                $ai4seo_this_page_is_active = ($ai4seo_current_post_type == $ai4seo_this_post_type);
                $ai4seo_this_page_url = ai4seo_get_post_type_page_url($ai4seo_this_post_type);

                echo "<a href='" . esc_url($ai4seo_this_page_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_this_page_is_active ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                    ai4seo_echo_wp_kses($ai4seo_this_menu_item_icon);
                    echo "<div>";
                        echo esc_html($ai4seo_this_menu_item_label);
                    echo "</div>";
                echo "</a>";
            }

            if ($ai4seo_active_attachment_attributes) {
                // Media page
                echo "<a href='" . esc_url($ai4seo_media_page_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_active_plugin_page == "media" ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                    ai4seo_echo_wp_kses(ai4seo_get_dashicon_tag_for_navigation("attachment"));
                    echo "<span>";
                        echo esc_html(_n("Media", "Media", 2, "ai-for-seo"));
                    echo "</span>";
                echo "</a>";
            }

            // Account page
            echo "<a href='" . esc_url($ai4seo_account_page_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_active_plugin_page == "account" ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                ai4seo_echo_wp_kses(ai4seo_get_svg_tag("key", "", "ai4seo-menu-item-icon"));
                echo "<span>";
                    echo esc_html__("Account", "ai-for-seo");
                echo "</span>";
            echo "</a>";

            // Settings page
            echo "<a href='" . esc_url($ai4seo_settings_page_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_active_plugin_page == "settings" ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                echo '<i class="dashicons dashicons-admin-generic ai4seo-menu-item-icon"></i>';
                echo "<span>";
                    echo esc_html__("Settings", "ai-for-seo");
                echo "</span>";
            echo "</a>";

            // Help page
            echo "<a href='" . esc_url($ai4seo_help_page_url) . "' class='nav-tab ai4seo-menu-item" . ($ai4seo_active_plugin_page == "help" ? " nav-tab-active ai4seo-active-menu-item" : "") . "'>";
                echo '<i class="dashicons dashicons-editor-help ai4seo-menu-item-icon"></i>';
                echo "<span>";
                    echo esc_html__("Help", "ai-for-seo");
                echo "</span>";
            echo "</a>";

        echo "</nav>";

        // STATUS BOX
        /*echo "<div class='ai4seo-status-box'>";
            echo "<h5>" . esc_html__("Credits", "ai-for-seo") . "</h5>";
            echo "<div class='ai4seo-status-box-credits'>";
                echo esc_html(123);
            echo "</div>";

            echo "<h5>" . esc_html__("Bulk Generation", "ai-for-seo") . "</h5>";
            echo "<div class='ai4seo-status-box-bulk-generation'>";
                echo "Working hard...";
            echo "</div>";
        echo "</div>";*/

        echo "<div class='ai4seo-sidebar-version-number'>" . esc_html(AI4SEO_PLUGIN_VERSION_NUMBER) . "</div>";

    echo "</div>";


    // ___________________________________________________________________________________________ \\
    // === OUTPUT: CONTENT AREA ================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    echo "<div class='ai4seo-content-wrapper'>";

        // hide all other notices
        echo "<div class='ai4seo-hidden-notices-area'>";
            echo "<h1 style='display: none;'>" . esc_html(AI4SEO_PLUGIN_NAME) . "</h1>";
        echo "</div>";


        // === CHECK FOR NEW TOS ===================================================================== \\

        // set parameter to false, so we definitely don't output anything, if tos was not accepted
        if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
            // show message to accept tos and offer a reload button
            echo "<center>";
                echo "<div class='ai4seo-tos-notice'>";
                    echo "<p>" . esc_html__("Please accept our Terms of Service to proceed with using this plugin.", "ai-for-seo") . "</p>";
                    echo "<a href='" . esc_url(ai4seo_get_subpage_url()) . "' class='button ai4seo-button ai4seo-success-button'>" . esc_html__("Show terms of service", "ai-for-seo") . "</a>";
                echo "</div>";
            echo "</div>";
            return;
        }


        // === DEBUG OPERATIONS ================================================================================= \\

        // GENERATE
        if (isset($_GET["ai4seo_debug_generate_cronjob"]) && $_GET["ai4seo_debug_generate_cronjob"]) {
            $ai4seo_cron_job_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
            $ai4seo_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
            echo "<pre>>" . print_r($ai4seo_cron_job_status, true) . "<</pre>";
            echo "<pre>>" . print_r(ai4seo_format_unix_timestamp($ai4seo_cron_job_status_update_time), true) . "<</pre>";
            ai4seo_automated_generation_cron_job(true);
        }

        // ANALYZE
        if (isset($_GET["ai4seo_debug_analyze_cronjob"]) && $_GET["ai4seo_debug_analyze_cronjob"]) {
            ai4seo_analyze_plugin_performance(true);
        }

        // TIDY UP
        if (isset($_GET["ai4seo_force_tidyup"]) && $_GET["ai4seo_force_tidyup"]) {
            ai4seo_tidy_up();

            $ai4seo_dashboard_url = ai4seo_get_subpage_url("dashboard");

            // reload page
            echo "<script>";
                echo "jQuery(document).ready(function() {";
                    echo "location.href = '" . esc_url($ai4seo_dashboard_url) . "';";
                echo "});";
            echo "</script>";

            return;
        }

        // DEBUG POST CONTENT
        if (isset($_GET["ai4seo_debug_post_content"]) && $_GET["ai4seo_debug_post_content"] && is_numeric($_GET["ai4seo_debug_post_content"])) {
            $ai4seo_debug_post_id = absint($_GET["ai4seo_debug_post_content"]);
            $ai4seo_condensed_post_content_from_database = ai4seo_get_condensed_post_content_from_database($ai4seo_debug_post_id, true);

            ai4seo_add_post_context($ai4seo_debug_post_id, $ai4seo_condensed_post_content_from_database);

            echo "<pre>FINAL WITH CONTEXT >" . print_r(htmlspecialchars($ai4seo_condensed_post_content_from_database), true) . "<</pre>";
            unset($ai4seo_condensed_post_content_from_database, $ai4seo_debug_post_id);
        }

        // DEBUG ai4seo_debug_posts_table_analysis
        if (isset($_GET["ai4seo_debug_posts_table_analysis"]) && $_GET["ai4seo_debug_posts_table_analysis"]) {
            ai4seo_try_start_posts_table_analysis(true, true);
        }


        // === CONTENT PAGES =========================================================================== \\

        switch($ai4seo_active_plugin_page) {
            case "":
            case "dashboard":
                require_once(ai4seo_get_includes_pages_path("dashboard.php"));
                break;
            case "settings":
                require_once(ai4seo_get_includes_pages_path("settings.php"));
                break;
            case "post":
                require_once(ai4seo_get_includes_pages_content_types_path("post.php"));
                break;
            case "media":
                require_once(ai4seo_get_includes_pages_content_types_path("attachment.php"));
                break;
            case "help":
                require_once(ai4seo_get_includes_pages_path("help.php"));
                break;
            case "account":
                require_once(ai4seo_get_includes_pages_path("account.php"));
                break;
            default:
                echo "Unknown AI for SEO page. Please contact the plugin developer. #2406232005";
        }

        // gap
        echo "<div>&nbsp;</div>";

    echo "</div>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === OUTPUT: MODAL SCHEMAS ================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// if wp_footer is not called, we need to include the modal schemas file here
ai4seo_include_modal_schemas_file();