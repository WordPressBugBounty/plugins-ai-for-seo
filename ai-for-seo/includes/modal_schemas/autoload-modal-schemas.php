<?php
/**
 * Includes / autoload the modal schemas.
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

$ai4seo_current_content_tab = ai4seo_get_current_tab();
$ai4seo_modal_schemas = array();


// === FIND SUITABLE MODAL SCHEMAS =========================================================== \\

$is_user_inside_plugin_admin_pages = ai4seo_is_user_inside_plugin_admin_pages();

if ($is_user_inside_plugin_admin_pages) {
    // TOS
    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        $ai4seo_modal_schemas[] = "tos";
    } else {
        if ($ai4seo_current_content_tab == "dashboard" || $ai4seo_current_content_tab == "account") {
            $ai4seo_modal_schemas[] = "get-more-credits";
            $ai4seo_modal_schemas[] = "select-credits-pack";
            $ai4seo_modal_schemas[] = "customize-pay-as-you-go";
        }

        if ($ai4seo_current_content_tab == "dashboard") {
            $ai4seo_modal_schemas[] = "seo-autopilot";
        }
    }
}

if (!$ai4seo_modal_schemas) {
    return;
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schemas-container' style='display: none !important;'>";

    foreach ($ai4seo_modal_schemas AS $ai4seo_this_modal_identifier) {
        echo "<div class='ai4seo-modal-schema' id='ai4seo-modal-schema-" . esc_attr($ai4seo_this_modal_identifier) . "'>";
            include ai4seo_get_includes_modal_schemas_path($ai4seo_this_modal_identifier . ".php");
        echo "</div>";
    }

echo "</div>";