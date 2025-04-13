<?php
/**
 * Required by save-everything.php.
 * Updates given attachment attributes editor values for this plugin from $ai4seo_post_parameter in bulk
 *
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

if (!isset($ai4seo_post_parameter)) {
    return;
}

if (!defined('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS') || !isset($ai4seo_post_parameter["attachment_attributes_editor_post_id"])) {
    return;
}

$ai4seo_this_attachment_post_id = intval($ai4seo_post_parameter["attachment_attributes_editor_post_id"]);


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_attachment_attributes = array();

foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
    $this_attachment_attribute_input_name = "attachment_attribute_" . $ai4seo_this_attachment_attribute_identifier;

    if (isset($ai4seo_post_parameter[$this_attachment_attribute_input_name])) {
        $ai4seo_new_attachment_attributes[$ai4seo_this_attachment_attribute_identifier] = $ai4seo_post_parameter[$this_attachment_attribute_input_name];
    }
}


// ___________________________________________________________________________________________ \\
// === PROCESS =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_new_attachment_attributes) {
    ai4seo_update_active_attachment_attributes($ai4seo_this_attachment_post_id, $ai4seo_new_attachment_attributes, true, true);

    // Refresh the attachment attributes coverage
    ai4seo_refresh_one_posts_attachment_attributes_coverage($ai4seo_this_attachment_post_id);
    ai4seo_remove_post_ids_from_all_generation_status_options($ai4seo_this_attachment_post_id);
}