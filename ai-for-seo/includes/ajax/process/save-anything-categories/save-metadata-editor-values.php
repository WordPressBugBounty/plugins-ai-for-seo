<?php
/**
 * Required by save-everything.php.
 * Updates given metadata editor values for this plugin from $ai4seo_post_parameter in bulk
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

if (!defined('AI4SEO_METADATA_DETAILS') || !isset($ai4seo_post_parameter["metadata_editor_post_id"])) {
    return;
}

$ai4seo_this_post_id = intval($ai4seo_post_parameter["metadata_editor_post_id"]);


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_metadata = array();

foreach (AI4SEO_METADATA_DETAILS as $ai4seo_metadata_identifier => $ai4seo_metadata_details) {
    $this_metadata_input_name = "metadata_" . $ai4seo_metadata_identifier;

    if (isset($ai4seo_post_parameter[$this_metadata_input_name])) {
        $ai4seo_new_metadata[$ai4seo_metadata_identifier] = $ai4seo_post_parameter[$this_metadata_input_name];
    }
}


// ___________________________________________________________________________________________ \\
// === PROCESS =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ($ai4seo_new_metadata) {
    ai4seo_update_active_metadata($ai4seo_this_post_id, $ai4seo_new_metadata, true);

    // Refresh the metadata coverage
    ai4seo_refresh_one_posts_metadata_coverage_status($ai4seo_this_post_id);
    ai4seo_remove_post_ids_from_all_generation_status_options($ai4seo_this_post_id);
}