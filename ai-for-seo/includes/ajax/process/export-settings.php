<?php
/**
 * AJAX handler for exporting settings
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

// Gather all settings data
$ai4seo_export_data = array();

// Add plugin version for backwards compatibility
$ai4seo_export_data['ai4seo_plugin_version'] = AI4SEO_PLUGIN_VERSION_NUMBER;
$ai4seo_export_data['ai4seo_export_timestamp'] = time();

// Get all plugin settings
$ai4seo_export_data['settings'] = ai4seo_get_all_settings();

// Generate filename with version
$ai4seo_filename = "ai4seo-settings-" . AI4SEO_PLUGIN_VERSION_NUMBER . ".json";

// Return data for download
ai4seo_send_json_success(array(
    'settings_data' => $ai4seo_export_data,
    'filename' => $ai4seo_filename
));