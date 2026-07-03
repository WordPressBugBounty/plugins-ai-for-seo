<?php
/**
 * Required by ai-for-seo-php > save-everything() function. This file is required INSIDE that function.
 * Updates given attachment attributes editor values for this plugin from $upcoming_save_anything_updates in bulk
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

# note: $upcoming_save_anything_updates is already sanitized in ai4seo_save_anything()
if (!isset($upcoming_save_anything_updates)) {
    return;
}

if (!defined('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS') || !isset($upcoming_save_anything_updates['attachment_attributes_editor_post_id'])) {
    return;
}

$ai4seo_this_attachment_post_id = intval($upcoming_save_anything_updates['attachment_attributes_editor_post_id']);

// Track field presence separately because an empty instruction value intentionally clears postmeta.
$ai4seo_custom_instructions_were_submitted = array_key_exists('attachment_attributes_editor_custom_instructions', $upcoming_save_anything_updates);

// Confirm the editor is targeting a native media attachment before saving attachment-specific postmeta.
if (!ai4seo_is_wordpress_attachment_post($ai4seo_this_attachment_post_id)) {
    ai4seo_send_ajax_error(esc_html__('Media post not found.', 'ai-for-seo'), 6311221025);
    wp_die();
}

// ___________________________________________________________________________________________ \\
// === VALIDATES AND COLLECTS UPCOMING ENVIRONMENTAL VARIABLES UPDATES ======================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_attachment_attributes = array();

foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_attachment_attribute_identifier => $ai4seo_attachment_attribute_details) {
    $ai4seo_attachment_attribute_input_name = 'attachment_attribute_' . $ai4seo_attachment_attribute_identifier;

    if (!isset($upcoming_save_anything_updates[$ai4seo_attachment_attribute_input_name])) {
        continue;
    }

    $ai4seo_attachment_attribute_value = $upcoming_save_anything_updates[$ai4seo_attachment_attribute_input_name];
    $ai4seo_attachment_attribute_field_label = '';

    if (isset($ai4seo_attachment_attribute_details['name']) && is_string($ai4seo_attachment_attribute_details['name']) && $ai4seo_attachment_attribute_details['name'] !== '') {
        $ai4seo_attachment_attribute_field_label = $ai4seo_attachment_attribute_details['name'];
    } else {
        $ai4seo_attachment_attribute_field_label = ucwords(str_replace('-', ' ', $ai4seo_attachment_attribute_identifier));
    }

    // check length
    $ai4seo_length_limit = ai4seo_get_max_editor_input_length($ai4seo_attachment_attribute_identifier);

    if (ai4seo_mb_strlen($ai4seo_attachment_attribute_value) > $ai4seo_length_limit) {
        ai4seo_send_ajax_error(
            sprintf(
            /* translators: 1: Field label, 2: Length limit */
                esc_html__('The value for "%1$s" exceeds the maximum allowed length of %2$s characters. Please shorten your input and try again.', 'ai-for-seo'),
                esc_html($ai4seo_attachment_attribute_field_label),
                esc_html(ai4seo_format_number_i18n($ai4seo_length_limit))
            ),
            5511221025
        );
        wp_die();
    }

    $ai4seo_new_attachment_attributes[$ai4seo_attachment_attribute_identifier] = $ai4seo_attachment_attribute_value;
}


// ___________________________________________________________________________________________ \\
// === PROCESS =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!$ai4seo_new_attachment_attributes && !$ai4seo_custom_instructions_were_submitted) {
    ai4seo_send_ajax_error(esc_html__('No attachment attributes were provided to update.', 'ai-for-seo'), 5711221025);
    wp_die();
}

// Save attachment-level instructions only after attribute validation has passed to avoid partial form updates.
if ($ai4seo_custom_instructions_were_submitted) {
    $ai4seo_custom_instructions_saved = ai4seo_save_custom_instructions_postmeta(
        $ai4seo_this_attachment_post_id,
        AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY,
        $upcoming_save_anything_updates['attachment_attributes_editor_custom_instructions']
    );

    if (!$ai4seo_custom_instructions_saved) {
        ai4seo_send_ajax_error(esc_html__('Failed to update custom instructions. Please try again.', 'ai-for-seo'), 6211221025);
        wp_die();
    }
}

// Stop after an instruction-only save so attachment coverage remains unchanged.
if (!$ai4seo_new_attachment_attributes) {
    return;
}

ai4seo_update_attachment_attributes($ai4seo_this_attachment_post_id, $ai4seo_new_attachment_attributes, true);

// Refresh the attachment attributes coverage
ai4seo_refresh_one_posts_attachment_attributes_coverage($ai4seo_this_attachment_post_id);
ai4seo_remove_post_ids_from_all_generation_status_options($ai4seo_this_attachment_post_id);
