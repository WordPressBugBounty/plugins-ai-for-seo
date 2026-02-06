<?php
/**
 * Required by ai-for-seo-php > save-everything() function. This file is required INSIDE that function.
 * Updates given metadata editor values for this plugin from $upcoming_save_anything_updates in bulk
 *
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

# note: $upcoming_save_anything_updates is already sanitized in ai4seo_save_anything()
if (!isset($upcoming_save_anything_updates)) {
    return;
}

if (!defined('AI4SEO_METADATA_DETAILS') || !isset($upcoming_save_anything_updates["metadata_editor_post_id"])) {
    return;
}

$ai4seo_this_post_id = intval($upcoming_save_anything_updates["metadata_editor_post_id"]);


// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_metadata = array();

foreach (AI4SEO_METADATA_DETAILS as $ai4seo_metadata_identifier => $ai4seo_metadata_details) {
    $ai4seo_metadata_input_name = "metadata_" . $ai4seo_metadata_identifier;

    if (!isset($upcoming_save_anything_updates[$ai4seo_metadata_input_name])) {
        continue;
    }

    $ai4seo_metadata_raw_value = $upcoming_save_anything_updates[$ai4seo_metadata_input_name];

    $ai4seo_metadata_field_label = '';

    if (isset($ai4seo_metadata_details['name']) && is_string($ai4seo_metadata_details['name']) && $ai4seo_metadata_details['name'] !== '') {
        $ai4seo_metadata_field_label = $ai4seo_metadata_details['name'];
    } else {
        $ai4seo_metadata_field_label = ucwords(str_replace('-', ' ', $ai4seo_metadata_identifier));
    }

    // check length
    $ai4seo_length_limit = ai4seo_get_max_editor_input_length($ai4seo_metadata_identifier);

    if (ai4seo_mb_strlen($ai4seo_metadata_raw_value) > $ai4seo_length_limit) {
        ai4seo_send_json_error(
            sprintf(
            /* translators: 1: Field label, 2: Length limit */
                esc_html__('The value for "%1$s" exceeds the maximum allowed length of %2$d characters. Please shorten your input and try again.', 'ai-for-seo'),
                esc_html($ai4seo_metadata_field_label),
                esc_html($ai4seo_length_limit)
            ),
            5311221025
        );
        wp_die();
    }

    // special treatment for keywords to make sure the format is correct and values are sanitized
    if ($ai4seo_metadata_identifier === 'keywords') {
        if (!is_string($ai4seo_metadata_raw_value)) {
            $ai4seo_new_metadata[$ai4seo_metadata_identifier] = '';
            continue;
        }

        $ai4seo_metadata_keywords = array_map('trim', explode(',', ai4seo_wp_unslash($ai4seo_metadata_raw_value)));
        $ai4seo_metadata_keywords = array_filter($ai4seo_metadata_keywords, static function ($ai4seo_keyword) {
            return $ai4seo_keyword !== '';
        });
        
        if (!$ai4seo_metadata_keywords) {
            $ai4seo_new_metadata[$ai4seo_metadata_identifier] = '';
            continue;
        }

        $ai4seo_metadata_keywords = array_map('sanitize_text_field', $ai4seo_metadata_keywords);
        $ai4seo_metadata_keywords = array_unique($ai4seo_metadata_keywords);
        $ai4seo_keywords_string = implode(', ', $ai4seo_metadata_keywords);

        $ai4seo_metadata_raw_value = $ai4seo_keywords_string;
    }

    $ai4seo_metadata_raw_value = ai4seo_normalize_editor_input_value($ai4seo_metadata_raw_value);

    $ai4seo_new_metadata[$ai4seo_metadata_identifier] = $ai4seo_metadata_raw_value;
}


// ___________________________________________________________________________________________ \\
// === PROCESS =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!$ai4seo_new_metadata) {
    ai4seo_send_json_error(esc_html__('No metadata values to update.', 'ai-for-seo'), 5611221025);
    wp_die();
}

$ai4seo_this_success = ai4seo_update_active_metadata($ai4seo_this_post_id, $ai4seo_new_metadata, true);

if (!$ai4seo_this_success) {
    ai4seo_send_json_error(esc_html__('Failed to update metadata. Please try again.', 'ai-for-seo'), 3518161025);
    wp_die();
}

// Refresh the metadata coverage
ai4seo_refresh_one_posts_metadata_coverage_status($ai4seo_this_post_id);
ai4seo_remove_post_ids_from_all_generation_status_options($ai4seo_this_post_id);