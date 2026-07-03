<?php
/**
 * Displays related media attachments for a post. Called via AJAX.
 *
 * @since 2.3.8
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

// Recheck the global AJAX nonce before handling this protected admin request.
if (wp_verify_nonce($GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
    ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 11032599);
    return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// =========================================================================================== \\

// The modal is scoped to one source post; all other filter parameters are optional table state.
$ai4seo_post_id = absint(wp_unslash($_REQUEST['post_id'] ?? 0));

if ($ai4seo_post_id <= 0) {
    ai4seo_send_ajax_error(esc_html__('Post id is invalid.', 'ai-for-seo'), 11032601);
}

$ai4seo_post = get_post($ai4seo_post_id);

if (!$ai4seo_post || is_wp_error($ai4seo_post) || !isset($ai4seo_post->post_type)) {
    ai4seo_send_ajax_error(esc_html__('Post not found.', 'ai-for-seo'), 11032602);
}

if (in_array($ai4seo_post->post_type, array('attachment', 'revision', 'nav_menu_item'), true)) {
    ai4seo_send_ajax_error(esc_html__('This post type cannot have related media listed here.', 'ai-for-seo'), 11032603);
}

// Use the source post title only for context; attachment.php remains responsible for the table itself.
$ai4seo_this_post_title = get_the_title($ai4seo_post_id);

if (!$ai4seo_this_post_title) {
    $ai4seo_this_post_title = __('Untitled', 'ai-for-seo');
}

// Use the detailed scanner result so the modal can warn when bounded metadata scanning is partial.
$ai4seo_related_attachments_scan_result = ai4seo_get_related_attachment_scan_result($ai4seo_post_id);
$ai4seo_attachment_post_ids_filter = (array) ($ai4seo_related_attachments_scan_result['attachment_post_ids'] ?? array());
$ai4seo_related_attachments_scan_is_partial = !empty($ai4seo_related_attachments_scan_result['is_partial']);
$ai4seo_is_related_attachments_modal = true;
$ai4seo_related_attachments_modal_post_id = $ai4seo_post_id;

// Persist the full scanner result before table filtering so generation can use it as a context fallback later.
if (!ai4seo_update_attachment_related_post_id_for_attachment_post_ids(
    $ai4seo_attachment_post_ids_filter,
    $ai4seo_post_id
)) {
    ai4seo_debug_message(
        4527052602,
        'Could not update related post fallback values for related media modal post ID ' . $ai4seo_post_id,
        true
    );
}

// Forward only the media-table filter parameters that attachment.php already knows how to consume.
$ai4seo_related_attachments_filter_request_fields = array(
    'ai4seo_filter_text' => 'text',
    'ai4seo_filter_status' => 'key',
    'ai4seo_filter_language' => 'text',
    'ai4seo_content_type_filter_nonce' => 'text',
    'orderby' => 'key',
    'order' => 'key',
    'ai4seo_page' => 'int',
    'lang' => 'text',
);

// Mirror modal AJAX parameters into request globals because the reused media table reads shared filter state there.
foreach ($ai4seo_related_attachments_filter_request_fields as $ai4seo_filter_request_field => $ai4seo_filter_request_type) {
    if (!isset($_REQUEST[$ai4seo_filter_request_field]) || is_array($_REQUEST[$ai4seo_filter_request_field])) {
        continue;
    }

    if ($ai4seo_filter_request_type === 'int') {
        $ai4seo_filter_request_value = absint(wp_unslash($_REQUEST[$ai4seo_filter_request_field]));
    } else if ($ai4seo_filter_request_type === 'key') {
        $ai4seo_filter_request_value = sanitize_key(wp_unslash($_REQUEST[$ai4seo_filter_request_field]));
    } else {
        $ai4seo_filter_request_value = sanitize_text_field(wp_unslash($_REQUEST[$ai4seo_filter_request_field]));
    }

    $_GET[$ai4seo_filter_request_field] = $ai4seo_filter_request_value;
    $_REQUEST[$ai4seo_filter_request_field] = $ai4seo_filter_request_value;
}

// Default the modal to the first page so opening it from a post row never inherits unrelated page state.
if (!isset($_REQUEST['ai4seo_page'])) {
    $_GET['ai4seo_page'] = 1;
    $_REQUEST['ai4seo_page'] = 1;
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// =========================================================================================== \\

// Use the same modal header structure as the existing AJAX editors for consistent framing.
ai4seo_echo_wp_kses(ai4seo_get_modal_headline_tag(__('Related Media', 'ai-for-seo')));

echo "<div class='ai4seo-modal-sub-headline'>";
    ai4seo_echo_wp_kses(
        sprintf(
            /* translators: 1: Post title. 2: Post ID. */
            __('Media related to <b>%1$s</b> (#%2$d)', 'ai-for-seo'),
            $ai4seo_this_post_title,
            $ai4seo_post_id
        )
    );
echo '</div>';

// attachment.php reads these scoped variables and applies the related-ID filter in addition to its normal filters.
echo "<div class='ai4seo-modal-content ai4seo-related-attachments-modal-content' data-post-id='" . esc_attr($ai4seo_post_id) . "'>";
    if ($ai4seo_related_attachments_scan_is_partial) {
        // Warn only when scanner caps skipped data; normal targeted key filtering remains silent.
        echo "<div class='notice notice-warning inline ai4seo-related-attachments-partial-notice'>";
            echo '<p>' . esc_html__('Some post metadata was skipped to keep this request fast. The list may be incomplete.', 'ai-for-seo') . '</p>';
        echo '</div>';
    }

    require ai4seo_get_includes_pages_content_types_path('attachment.php');
echo '</div>';
