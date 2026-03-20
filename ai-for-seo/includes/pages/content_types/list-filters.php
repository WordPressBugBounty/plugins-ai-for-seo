<?php
/**
 * Shared helpers for rendering and applying list filters across AI for SEO content type tables.
 *
 * @package AI_For_SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ai4seo_setup_content_type_filters')) {
    /**
     * Prepares list filters (search + status), returns context data and rendered HTML.
     *
     * @param array $args {
     *     Arguments to control behaviour.
     *
     *     @type string $form_action          Target URL for the filter form.
     *     @type array  $post_types           Post types to limit search queries.
     *     @type array  $post_status          Post statuses to limit search queries.
     *     @type array  $post_mime_types      Optional. MIME types to limit attachment search queries.
     *     @type bool   $search_file_meta     Optional. Whether to search attachment filenames via post meta.
     *     @type int    $per_page             Optional. Items per page. Default 20.
     *     @type array  $hidden_fields        Optional. Additional hidden fields for the filter form.
     * }
     *
     * @return array {
     *     @type string $filter_text      The active text filter.
     *     @type string $filter_status    The active status filter.
     *     @type string $filter_language  The active language filter (WPML only).
     *     @type array  $status_options   Available status filter options.
     *     @type array  $search_ids       Post IDs matched by the text filter. `null` when no text filter is active.
     *     @type int    $per_page         Items per page.
     *     @type string $html             Rendered filter form HTML.
     *     @type array  $query_args       Query arguments to append to generated links (active filters only).
     * }
     */
    function ai4seo_setup_content_type_filters(array $args): array
    {
        global $wpdb;

        $defaults = array(
            'form_action' => '',
            'post_types' => array(),
            'post_status' => array(),
            'post_mime_types' => array(),
            'search_file_meta' => false,
            'per_page' => 20,
            'hidden_fields' => array(),
            'nonce_action' => 'ai4seo_content_type_filter_form',
            'nonce_name' => 'ai4seo_content_type_filter_nonce',
        );

        $args = array_merge($defaults, $args);

        $allowed_statuses = array(
            'all' => __('All', 'ai-for-seo'),
            'complete' => __('Complete', 'ai-for-seo'),
            'missing' => __('Missing Fields', 'ai-for-seo'),
            'failed' => __('Failed', 'ai-for-seo'),
            'processing' => __('Processing', 'ai-for-seo'),
        );

        $language_options = array();
        $filter_language = '';

        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
            $wpml_active_languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));

            if (is_array($wpml_active_languages)) {
                foreach ($wpml_active_languages as $wpml_language_code => $wpml_language_data) {
                    $this_language_code = sanitize_key($wpml_language_code);
                    $this_language_label = isset($wpml_language_data['translated_name']) ? sanitize_text_field($wpml_language_data['translated_name']) : $this_language_code;

                    if ($this_language_code === '') {
                        continue;
                    }

                    $language_options[$this_language_code] = $this_language_label;
                }
            }
        }

        $nonce_action = sanitize_key((string) $args['nonce_action']);
        if ($nonce_action === '') {
            $nonce_action = 'ai4seo_content_type_filter_form';
        }

        $nonce_name = sanitize_key((string) $args['nonce_name']);
        if ($nonce_name === '') {
            $nonce_name = 'ai4seo_content_type_filter_nonce';
        }

        $is_filter_request = isset($_GET['ai4seo_filter_text'])
            || isset($_GET['ai4seo_filter_status'])
            || isset($_GET['ai4seo_filter_language']);

        $raw_nonce = isset($_GET[$nonce_name]) ? wp_unslash($_GET[$nonce_name]) : '';
        $filter_nonce = sanitize_text_field($raw_nonce);
        $is_filter_nonce_valid = $is_filter_request && wp_verify_nonce($filter_nonce, $nonce_action);

        $raw_text = isset($_GET['ai4seo_filter_text']) ? wp_unslash($_GET['ai4seo_filter_text']) : '';
        $filter_text = sanitize_text_field($raw_text);

        $raw_status = isset($_GET['ai4seo_filter_status']) ? wp_unslash($_GET['ai4seo_filter_status']) : 'all';
        $filter_status = sanitize_key($raw_status);

        if (!array_key_exists($filter_status, $allowed_statuses)) {
            $filter_status = 'all';
        }

        $raw_language = isset($_GET['ai4seo_filter_language']) ? wp_unslash($_GET['ai4seo_filter_language']) : '';
        $filter_language = sanitize_text_field($raw_language);

        if (!isset($language_options[$filter_language])) {
            $filter_language = '';
        }

        if ($is_filter_request && !$is_filter_nonce_valid) {
            $filter_text = '';
            $filter_status = 'all';
            $filter_language = '';
        }

        $per_page = (int) $args['per_page'];
        if ($per_page < 1) {
            $per_page = 20;
        }

        $search_ids = null;

        static $request_sql_cache = array();
        static $request_meta_sql_cache = array();

        if ($filter_text !== '') {
            $post_types = array_map('sanitize_key', (array) $args['post_types']);
            $post_status = array_map('sanitize_key', (array) $args['post_status']);
            $post_mime_types = array_map('sanitize_text_field', (array) $args['post_mime_types']);

            // Hard cap dynamic filter arrays to avoid oversized IN(...) clauses.
            $post_types = array_slice( $post_types, 0, 256 );
            $post_status = array_slice( $post_status, 0, 256 );
            $post_mime_types = array_slice( $post_mime_types, 0, 256 );

            $sql_parts = array();
            $sql_values = array();

            if ($post_types) {
                $type_placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
                $sql_parts[] = "post_type IN ($type_placeholders)";
                $sql_values = array_merge($sql_values, $post_types);
            }

            if ($post_status) {
                $status_placeholders = implode(', ', array_fill(0, count($post_status), '%s'));
                $sql_parts[] = "post_status IN ($status_placeholders)";
                $sql_values = array_merge($sql_values, $post_status);
            }

            if ($post_mime_types) {
                $mime_placeholders = implode(', ', array_fill(0, count($post_mime_types), '%s'));
                $sql_parts[] = "post_mime_type IN ($mime_placeholders)";
                $sql_values = array_merge($sql_values, $post_mime_types);
            }

            $like_term = '%' . $wpdb->esc_like($filter_text) . '%';
            $search_clauses = array(
                'post_title LIKE %s',
                'post_name LIKE %s',
                'guid LIKE %s',
            );
            $sql_values[] = $like_term;
            $sql_values[] = $like_term;
            $sql_values[] = $like_term;

            if (ctype_digit($filter_text)) {
                $search_clauses[] = 'ID = %d';
                $sql_values[] = (int) $filter_text;
            }

            $sql_parts[] = '(' . implode(' OR ', $search_clauses) . ')';

            $sql = "SELECT ID FROM {$wpdb->posts}";

            if ($sql_parts) {
                $sql .= ' WHERE ' . implode(' AND ', $sql_parts);
            }

            $sql .= ' ORDER BY ID DESC';

            // Dynamic query with placeholders is prepared immediately below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $prepared_sql = $wpdb->prepare( $sql, ...$sql_values );

            if ( isset( $request_sql_cache[ $prepared_sql ] ) ) {
                $search_ids = $request_sql_cache[ $prepared_sql ];
            } else {
                // Prepared above.
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $search_ids = $wpdb->get_col( $prepared_sql );

                if ( $wpdb->last_error ) {
                    ai4seo_debug_message(984321701, 'Database error: ' . $wpdb->last_error);
                    $search_ids = array();
                }

                $request_sql_cache[$prepared_sql] = $search_ids;
            }

            if (!is_array($search_ids)) {
                $search_ids = array();
            }

            $search_ids = array_map('intval', $search_ids);

            if ($filter_language !== '') {
                $search_ids = ai4seo_filter_post_ids_by_language($search_ids, $filter_language);
            }

            if (!empty($args['search_file_meta'])) {
                $meta_sql_parts = array();
                $meta_values = array();

                if ($post_types) {
                    $type_placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
                    $meta_sql_parts[] = 'p.post_type IN (' . $type_placeholders . ')';
                    $meta_values = array_merge($meta_values, $post_types);
                }

                if ($post_status) {
                    $status_placeholders = implode(', ', array_fill(0, count($post_status), '%s'));
                    $meta_sql_parts[] = 'p.post_status IN (' . $status_placeholders . ')';
                    $meta_values = array_merge($meta_values, $post_status);
                }

                if ($post_mime_types) {
                    $mime_placeholders = implode(', ', array_fill(0, count($post_mime_types), '%s'));
                    $meta_sql_parts[] = 'p.post_mime_type IN (' . $mime_placeholders . ')';
                    $meta_values = array_merge($meta_values, $post_mime_types);
                }

                $meta_sql_parts[] = 'pm.meta_key = %s';
                $meta_values[] = '_wp_attached_file';
                $meta_sql_parts[] = 'pm.meta_value LIKE %s';
                $meta_values[] = $like_term;

                $meta_sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id";

                if ($meta_sql_parts) {
                    $meta_sql .= ' WHERE ' . implode(' AND ', $meta_sql_parts);
                }

                $meta_sql .= ' ORDER BY p.ID DESC';

                // Dynamic query with placeholders is prepared immediately below.
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $prepared_meta_sql = $wpdb->prepare( $meta_sql, ...$meta_values );

                if ( isset( $request_meta_sql_cache[ $prepared_meta_sql ] ) ) {
                    $meta_ids = $request_meta_sql_cache[ $prepared_meta_sql ];
                } else {
                    // Prepared above.
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $meta_ids = $wpdb->get_col( $prepared_meta_sql );

                    if ( $wpdb->last_error ) {
                        ai4seo_debug_message(984321702, 'Database error: ' . $wpdb->last_error);
                        $meta_ids = array();
                    }

                    $request_meta_sql_cache[$prepared_meta_sql] = $meta_ids;
                }

                if ($meta_ids) {
                    $meta_ids = array_map('intval', $meta_ids);
                    $search_ids = array_values(array_unique(array_merge($search_ids, $meta_ids)));

                    if ($filter_language !== '') {
                        $search_ids = ai4seo_filter_post_ids_by_language($search_ids, $filter_language);
                    }
                }
            }
        }

        $active_query_args = array();

        if ($filter_text !== '') {
            $active_query_args['ai4seo_filter_text'] = $filter_text;
        }

        if ($filter_status !== 'all') {
            $active_query_args['ai4seo_filter_status'] = $filter_status;
        }

        if ($filter_language !== '') {
            $active_query_args['ai4seo_filter_language'] = $filter_language;
        }

        if (!empty($active_query_args) && $is_filter_nonce_valid) {
            $active_query_args[$nonce_name] = $filter_nonce;
        }

        $form_action_url = trim((string) $args['form_action']);
        $action_hidden_fields = array();

        if ($form_action_url !== '' && strpos($form_action_url, '?') !== false) {
            list($form_action_path, $form_action_query) = explode('?', $form_action_url, 2);
            $form_action_url = $form_action_path;

            if ($form_action_query !== '') {
                $parsed_query = array();
                wp_parse_str($form_action_query, $parsed_query);

                if (!empty($parsed_query) && is_array($parsed_query)) {
                    foreach ($parsed_query as $query_key => $query_value) {
                        if (!is_scalar($query_value)) {
                            continue;
                        }

                        $action_hidden_fields[$query_key] = $query_value;
                    }
                }
            }
        }

        if ($form_action_url === '') {
            $form_action_url = function_exists('admin_url') ? admin_url('admin.php') : 'admin.php';
        }

        $hidden_fields = $action_hidden_fields;

        $current_wpml_lang = isset($_GET['lang']) ? sanitize_text_field(wp_unslash($_GET['lang'])) : '';
        if ($current_wpml_lang !== '') {
            $hidden_fields['lang'] = $current_wpml_lang;
        }

        if (!empty($args['hidden_fields']) && is_array($args['hidden_fields'])) {
            foreach ($args['hidden_fields'] as $field_name => $field_value) {
                if (!is_scalar($field_value)) {
                    continue;
                }

                $hidden_fields[$field_name] = $field_value;
            }
        }

        $hidden_fields_html = '';

        if (!empty($hidden_fields)) {
            foreach ($hidden_fields as $field_name => $field_value) {
                $field_key = sanitize_key($field_name);

                if ($field_key === '' || in_array($field_key, array('ai4seo_filter_text', 'ai4seo_filter_status'), true)) {
                    continue;
                }

                $field_value = sanitize_text_field((string) $field_value);

                if ($field_value === '') {
                    continue;
                }

                $hidden_fields_html .= '<input type="hidden" name="' . esc_attr($field_key) . '" value="' . esc_attr($field_value) . '" />';
            }
        }

        $form_classes = 'ai4seo-filter-bar';
        $status_options_html = '';
        $language_options_html = '';

        foreach ($allowed_statuses as $status_key => $status_label) {
            $status_options_html .= '<option value="' . esc_attr($status_key) . '"' . selected($filter_status, $status_key, false) . '>' . esc_html($status_label) . '</option>';
        }

        if ($language_options) {
            $language_options_html .= '<option value="">' . esc_html__('All languages', 'ai-for-seo') . '</option>';

            foreach ($language_options as $language_code => $language_label) {
                $language_options_html .= '<option value="' . esc_attr($language_code) . '"' . selected($filter_language, $language_code, false) . '>' . esc_html($language_label) . '</option>';
            }
        }

        $reset_url = $args['form_action'] !== '' ? $args['form_action'] : $form_action_url;

        $nonce_field_html = wp_nonce_field($nonce_action, $nonce_name, true, false);

        $filter_form_html = '<form method="get" action="' . esc_url($form_action_url) . '" class="' . esc_attr($form_classes) . '">'
            . $hidden_fields_html
            . $nonce_field_html
            . '<div class="ai4seo-filter-bar__fields">'
            . '<label class="ai4seo-filter-bar__label">'
            . esc_html__('Search', 'ai-for-seo')
            . '<input class="ai4seo-textfield" autocomplete="off" type="text" name="ai4seo_filter_text" value="' . esc_attr($filter_text) . '" placeholder="' . esc_attr__('Search by title, ID, or URL/filename', 'ai-for-seo') . '" />'
            . '</label>'
            . '<label class="ai4seo-filter-bar__label">'
            . esc_html__('Status', 'ai-for-seo')
            . '<select class="ai4seo-textfield" autocomplete="off" name="ai4seo_filter_status">' . $status_options_html . '</select>'
            . '</label>';

        if ($language_options_html !== '') {
            $filter_form_html .= '<label class="ai4seo-filter-bar__label">'
                . esc_html__('Language', 'ai-for-seo')
                . '<select class="ai4seo-textfield" autocomplete="off" name="ai4seo_filter_language">' . $language_options_html . '</select>'
                . '</label>';
        }

        $filter_form_html .= ''
            . '<div class="ai4seo-filter-bar__actions">'
            . ai4seo_get_button_tag(esc_html__('Apply Filters', 'ai-for-seo'), "", "ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen(); jQuery(this).closest(\"form\").submit();");

        if ($filter_text !== '' || $filter_status !== 'all' || $filter_language !== '') {
            $filter_form_html .= ai4seo_get_a_tag_icon_button_tag($reset_url, "", "", "", esc_html__('Reset', 'ai-for-seo'), "ai4seo-abort-button", "ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen();");
        }

        $filter_form_html .= '</div>'
            . '</div>'
            . '</form>';

        return array(
            'filter_text' => $filter_text,
            'filter_status' => $filter_status,
            'filter_language' => $filter_language,
            'status_options' => $allowed_statuses,
            'search_ids' => $search_ids,
            'per_page' => $per_page,
            'html' => $filter_form_html,
            'query_args' => $active_query_args,
        );
    }
}

if (!function_exists('ai4seo_filter_post_ids_by_status')) {
    /**
     * Filters a list of candidate IDs by the selected status.
     *
     * @param array  $candidate_ids Candidate IDs respecting the current query.
     * @param string $status        Selected status.
     * @param array  $status_map    Map of status => array of IDs.
     *
     * @return array Filtered IDs preserving the original order.
     */
    function ai4seo_filter_post_ids_by_status(array $candidate_ids, string $status, array $status_map): array
    {
        if ($status === 'all') {
            return array_values($candidate_ids);
        }

        if (!isset($status_map[$status]) || !is_array($status_map[$status])) {
            return array();
        }

        $status_ids = array_map('intval', $status_map[$status]);

        $candidate_ids = array_map('intval', $candidate_ids);

        $filtered_ids = array();

        foreach ($candidate_ids as $candidate_id) {
            if (in_array($candidate_id, $status_ids, true)) {
                $filtered_ids[] = $candidate_id;
            }
        }

        return $filtered_ids;
    }
}

if (!function_exists('ai4seo_filter_post_ids_by_language')) {
    /**
     * Filters a list of candidate IDs by the selected language (currently WPML-aware).
     *
     * @param array  $candidate_ids Candidate IDs respecting the current query.
     * @param string $language_code Language code to keep. Empty string keeps all IDs.
     * @return array Filtered IDs preserving the original order.
     */
    function ai4seo_filter_post_ids_by_language(array $candidate_ids, string $language_code): array
    {
        if ($language_code === '') {
            return $candidate_ids;
        }

        $filtered_ids = array();

        foreach ($candidate_ids as $candidate_id) {
            $candidate_id = (int) $candidate_id;

            if ($candidate_id <= 0) {
                continue;
            }

            $candidate_language_code = ai4seo_get_post_language_code_by_multilanguage_plugins($candidate_id);

            if ($candidate_language_code === $language_code) {
                $filtered_ids[] = $candidate_id;
            }
        }

        return $filtered_ids;
    }
}
