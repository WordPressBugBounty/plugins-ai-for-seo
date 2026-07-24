<?php
/**
 * Shared helpers for rendering and applying list filters across AI for SEO content type tables.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ai4seo_get_content_type_status_options' ) ) {
	/**
	 * Returns the shared SOOZ content-list status options.
	 *
	 * @return array Status labels keyed by status.
	 */
	function ai4seo_get_content_type_status_options(): array {
		// Keep every content-list surface on the same status set so page loads and AJAX hydration match.
		$allowed_statuses = array(
			'all'              => __( 'All', 'ai-for-seo' ),
			'complete'         => __( 'Complete', 'ai-for-seo' ),
			'missing'          => __( 'Missing Fields', 'ai-for-seo' ),
			'waiting_to_queue' => __( 'Waiting to get queued', 'ai-for-seo' ),
			'queued'           => __( 'In queue', 'ai-for-seo' ),
			'processing'       => __( 'Processing', 'ai-for-seo' ),
			'failed'           => __( 'Failed', 'ai-for-seo' ),
			'hidden'           => __( 'Hidden entries', 'ai-for-seo' ),
		);

		// "Waiting to get queued" is only actionable when SEO Autopilot can auto-queue entries.
		if ( ! ai4seo_should_auto_queue_bulk_generation_entries() ) {
			unset( $allowed_statuses['waiting_to_queue'] );
		}

		return $allowed_statuses;
	}
}

if ( ! function_exists( 'ai4seo_get_visible_content_type_status_filter_keys' ) ) {
	/**
	 * Returns the status keys that are visible in the content-list status filter row.
	 *
	 * @param array $status_options Available status options keyed by status.
	 * @param array $status_counts  Status counts keyed by status.
	 * @return array Visible status keys.
	 */
	function ai4seo_get_visible_content_type_status_filter_keys( array $status_options, array $status_counts ): array {
		// Collect the same non-empty status keys that the subsubsub filter renderer will output.
		$visible_status_filter_keys = array();

		// Keep retry-button visibility and status-link rendering on the exact same count rule.
		foreach ( $status_options as $this_status_key => $this_status_label ) {
			$this_status_key = sanitize_key( $this_status_key );

			if ( '' === $this_status_key ) {
				continue;
			}

			if ( absint( $status_counts[ $this_status_key ] ?? 0 ) <= 0 ) {
				continue;
			}

			$visible_status_filter_keys[] = $this_status_key;
		}

		return $visible_status_filter_keys;
	}
}

if ( ! function_exists( 'ai4seo_should_show_content_type_retry_all_failed_button' ) ) {
	/**
	 * Returns whether the retry-all-failed button should be visible for the current status filters.
	 *
	 * @param array $status_options Available status options keyed by status.
	 * @param array $status_counts  Status counts keyed by status.
	 * @return bool True when the Failed status filter is visible.
	 */
	function ai4seo_should_show_content_type_retry_all_failed_button( array $status_options, array $status_counts ): bool {
		// Resolve visibility through the shared status-filter helper so the button cannot drift from the Failed tab.
		$visible_status_filter_keys = ai4seo_get_visible_content_type_status_filter_keys( $status_options, $status_counts );

		// The retry action is available exactly when users can see and select the Failed status filter.
		return in_array( 'failed', $visible_status_filter_keys, true );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_filter_hydration_hidden_fields' ) ) {
	/**
	 * Returns routing fields that deferred status-filter hydration may preserve in generated links.
	 *
	 * @param array $hidden_fields Hidden fields from the initial filter context or AJAX request.
	 * @return array Sanitized hidden fields for hydrated status links.
	 */
	function ai4seo_get_content_type_status_filter_hydration_hidden_fields( array $hidden_fields ): array {
		$hydration_hidden_fields    = array();
		$allowed_hidden_field_names = array(
			'lang',
		);

		// Preserve only known routing fields so AJAX cannot inject arbitrary status-link parameters.
		foreach ( $allowed_hidden_field_names as $this_hidden_field_name ) {
			if ( ! isset( $hidden_fields[ $this_hidden_field_name ] ) || ! is_scalar( $hidden_fields[ $this_hidden_field_name ] ) ) {
				continue;
			}

			$this_hidden_field_value = sanitize_text_field( (string) $hidden_fields[ $this_hidden_field_name ] );

			if ( '' === $this_hidden_field_value ) {
				continue;
			}

			$hydration_hidden_fields[ $this_hidden_field_name ] = $this_hidden_field_value;
		}

		return $hydration_hidden_fields;
	}
}

if ( ! function_exists( 'ai4seo_normalize_content_type_list_context' ) ) {
	/**
	 * Normalizes the list context used to select metadata or attachment-attribute status options.
	 *
	 * @param string $content_context Raw context.
	 * @return string Normalized context.
	 */
	function ai4seo_normalize_content_type_list_context( string $content_context ): string {
		$content_context = sanitize_key( $content_context );

		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $content_context ) {
			return AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES;
		}

		return AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_allowed_sort_keys' ) ) {
	/**
	 * Returns the sort keys supported by the shared content-list controls.
	 *
	 * @return array Sort keys.
	 */
	function ai4seo_get_content_type_allowed_sort_keys(): array {
		// Keep PHP sorting, SQL paging, links, and AJAX hydration on the same small sort contract.
		return array(
			'id',
			'title',
			'seo_progress',
		);
	}
}

if ( ! function_exists( 'ai4seo_normalize_content_type_sort_args' ) ) {
	/**
	 * Normalizes content-list sort arguments.
	 *
	 * @param mixed $orderby Raw sort key.
	 * @param mixed $order Raw sort direction.
	 * @return array Normalized orderby and order values.
	 */
	function ai4seo_normalize_content_type_sort_args( $orderby, $order ): array {
		$orderby = sanitize_key( (string) $orderby );
		$order   = strtolower( sanitize_key( (string) $order ) );

		// Default back to the legacy newest-first ID sort for unknown request values.
		if ( ! in_array( $orderby, ai4seo_get_content_type_allowed_sort_keys(), true ) ) {
			$orderby = 'id';
		}

		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = 'desc';
		}

		return array(
			'orderby' => $orderby,
			'order'   => $order,
		);
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_option_names' ) ) {
	/**
	 * Returns status option names for a content-list context.
	 *
	 * @param string $content_context List context.
	 * @return array Option names keyed by row/status identifier.
	 */
	function ai4seo_get_content_type_status_option_names( string $content_context = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ): array {
		$content_context = ai4seo_normalize_content_type_list_context( $content_context );

		// Media rows use attachment-attribute status options, while post/page/product rows use metadata options.
		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $content_context ) {
			return array(
				'complete'              => AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'missing'               => AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'generated'             => AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'queued'                => AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'processing'            => AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'failed'                => AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'hidden'                => AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
		}

		return array(
			'complete'              => AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
			'missing'               => AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
			'generated'             => AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
			'queued'                => AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
			'processing'            => AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
			'failed'                => AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
			'hidden'                => AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
			'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
		);
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_scoped_post_id_chunks' ) ) {
	/**
	 * Normalizes an optional explicit post-ID scope for bounded content-list queries.
	 *
	 * @param mixed $scoped_post_ids Raw scoped post IDs or null when no explicit scope is active.
	 * @return array Scope state with active flag, normalized IDs, and database-safe chunks.
	 */
	function ai4seo_get_content_type_scoped_post_id_chunks( $scoped_post_ids ): array {
		$is_scoped_post_ids_active  = is_array( $scoped_post_ids );
		$normalized_scoped_post_ids = $is_scoped_post_ids_active
			? array_values( array_unique( array_filter( array_map( 'absint', $scoped_post_ids ) ) ) )
			: array();

		// Unscoped searches use one null marker so existing broad search SQL stays unchanged.
		if ( ! $is_scoped_post_ids_active ) {
			return array(
				'is_active' => false,
				'post_ids'  => array(),
				'chunks'    => array( null ),
			);
		}

		// Empty modal scopes must stay empty and avoid executing broad fallback queries.
		if ( ! $normalized_scoped_post_ids ) {
			return array(
				'is_active' => true,
				'post_ids'  => array(),
				'chunks'    => array(),
			);
		}

		return array(
			'is_active' => true,
			'post_ids'  => $normalized_scoped_post_ids,
			'chunks'    => array_chunk( $normalized_scoped_post_ids, ai4seo_get_database_chunk_size() ),
		);
	}
}

if ( ! function_exists( 'ai4seo_setup_content_type_filters' ) ) {
	/**
	 * Prepares list filters (search + status) and returns reusable filter context data.
	 *
	 * @param array $args {
	 *     Arguments to control behaviour.
	 *
	 *     @type string $form_action          Target URL for the filter form.
	 *     @type array  $post_types           Post types to limit search queries.
	 *     @type array  $post_status          Post statuses to limit search queries.
	 *     @type array  $post_mime_types      Optional. MIME types to limit attachment search queries.
	 *     @type array  $author_not_in        Optional. Post author IDs to exclude from search queries.
	 *     @type array  $disabled_taxonomy_terms Optional. Disabled taxonomy terms to exclude from search queries.
	 *     @type array  $disabled_wpml_language_codes Optional. Disabled WPML language codes to exclude from search queries.
	 *     @type bool   $search_file_meta     Optional. Whether to search attachment filenames via post meta.
	 *     @type bool   $defer_search_ids     Optional. Whether the caller resolves search IDs later.
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
	 *     @type string $html             Reserved compatibility value. Empty for native-style renderers.
	 *     @type array  $query_args       Query arguments to append to generated links (active filters only).
	 *     @type string $orderby          Active sort key.
	 *     @type string $order            Active sort direction.
	 *     @type string $form_action_url  Normalized form action URL.
	 *     @type array  $hidden_fields    Hidden fields parsed from the form action and filter args.
	 *     @type string $nonce_action     Nonce action used by the filter UI.
	 *     @type string $nonce_name       Nonce field name used by the filter UI.
	 *     @type array  $language_options Available language options, if WPML is active.
	 *     @type string $reset_url        URL that clears active filter state.
	 * }
	 */
	function ai4seo_setup_content_type_filters( array $args ): array {
		global $wpdb;

		$defaults = array(
			'form_action'                  => '',
			'post_types'                   => array(),
			'post_status'                  => array(),
			'post_mime_types'              => array(),
			'author_not_in'                => array(),
			'disabled_taxonomy_terms'      => array(),
			'disabled_wpml_language_codes' => array(),
			'search_file_meta'             => false,
			'defer_search_ids'             => false,
			'scoped_post_ids'              => null,
			'per_page'                     => 20,
			'hidden_fields'                => array(),
			'nonce_action'                 => 'ai4seo_content_type_filter_form',
			'nonce_name'                   => 'ai4seo_content_type_filter_nonce',
		);

		$args = array_merge( $defaults, $args );

		// Disabled WPML languages affect both the visible language filter choices and the ID-based result sets.
		$disabled_wpml_language_codes = ai4seo_sanitize_wpml_language_codes( $args['disabled_wpml_language_codes'] ?? array(), true );

		// Use the shared status list so normal pages and AJAX-hydrated filters expose the same views.
		$allowed_statuses = ai4seo_get_content_type_status_options();

		$language_options = ai4seo_get_available_wpml_languages();
		$filter_language  = '';

		// Hide disabled WPML languages from the filter dropdown so users cannot select an excluded scope.
		if ( $language_options && $disabled_wpml_language_codes ) {
			foreach ( $disabled_wpml_language_codes as $this_disabled_wpml_language_code ) {
				unset( $language_options[ $this_disabled_wpml_language_code ] );
			}
		}

		$nonce_action = sanitize_key( (string) $args['nonce_action'] );
		if ( '' === $nonce_action ) {
			$nonce_action = 'ai4seo_content_type_filter_form';
		}

		$nonce_name = sanitize_key( (string) $args['nonce_name'] );
		if ( '' === $nonce_name ) {
			$nonce_name = 'ai4seo_content_type_filter_nonce';
		}

		$is_filter_request = isset( $_GET['ai4seo_filter_text'] )
			|| isset( $_GET['ai4seo_filter_status'] )
			|| isset( $_GET['ai4seo_filter_language'] );

		$filter_nonce          = isset( $_GET[ $nonce_name ] ) ? sanitize_text_field( wp_unslash( $_GET[ $nonce_name ] ) ) : '';
		$is_filter_nonce_valid = $is_filter_request && wp_verify_nonce( $filter_nonce, $nonce_action );

		$filter_text = isset( $_GET['ai4seo_filter_text'] ) ? sanitize_text_field( wp_unslash( $_GET['ai4seo_filter_text'] ) ) : '';

		$filter_status = isset( $_GET['ai4seo_filter_status'] ) ? sanitize_key( wp_unslash( $_GET['ai4seo_filter_status'] ) ) : 'all';

		if ( ! array_key_exists( $filter_status, $allowed_statuses ) ) {
			$filter_status = 'all';
		}

		$filter_language = isset( $_GET['ai4seo_filter_language'] ) ? sanitize_text_field( wp_unslash( $_GET['ai4seo_filter_language'] ) ) : '';

		if ( ! isset( $language_options[ $filter_language ] ) ) {
			$filter_language = '';
		}

		if ( $is_filter_request && ! $is_filter_nonce_valid ) {
			$filter_text     = '';
			$filter_status   = 'all';
			$filter_language = '';
		}

		$sort_args = ai4seo_normalize_content_type_sort_args(
			isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id',
			isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc'
		);
		$orderby   = $sort_args['orderby'];
		$order     = $sort_args['order'];

		$per_page = (int) $args['per_page'];
		if ( $per_page < 1 ) {
			$per_page = 20;
		}

		$search_ids = null;

		static $request_sql_cache      = array();
		static $request_meta_sql_cache = array();

		// Content lists can defer search resolution so they can paginate matching IDs instead of materializing every match.
		if ( '' !== $filter_text && empty( $args['defer_search_ids'] ) ) {
			$scoped_post_id_scope      = ai4seo_get_content_type_scoped_post_id_chunks( $args['scoped_post_ids'] );
			$is_scoped_post_ids_active = (bool) $scoped_post_id_scope['is_active'];
			$scoped_post_ids           = (array) $scoped_post_id_scope['post_ids'];

			// Scoped modal searches with no candidates should return immediately instead of broadening to all media.
			if ( $is_scoped_post_ids_active && ! $scoped_post_ids ) {
				$search_ids = array();
			}

			$post_types              = array_map( 'sanitize_key', (array) $args['post_types'] );
			$post_status             = array_map( 'sanitize_key', (array) $args['post_status'] );
			$post_mime_types         = array_map( 'sanitize_text_field', (array) $args['post_mime_types'] );
			$author_not_in           = array_map( 'intval', (array) $args['author_not_in'] );
			$disabled_taxonomy_terms = ai4seo_sanitize_disabled_taxonomy_terms_value( $args['disabled_taxonomy_terms'] ?? array() );
			$author_not_in           = array_values( array_unique( $author_not_in ) );

			foreach ( $author_not_in as $author_not_in_index => $author_not_in_id ) {
				if ( $author_not_in_id <= 0 ) {
					unset( $author_not_in[ $author_not_in_index ] );
				}
			}

			$author_not_in = array_values( $author_not_in );

			// Hard cap dynamic filter arrays to avoid oversized IN(...) clauses.
			$post_types      = array_slice( $post_types, 0, 256 );
			$post_status     = array_slice( $post_status, 0, 256 );
			$post_mime_types = array_slice( $post_mime_types, 0, 256 );
			$author_not_in   = array_slice( $author_not_in, 0, 256 );

			$sql_parts  = array();
			$sql_values = array();

			$like_term                 = '%' . $wpdb->esc_like( $filter_text ) . '%';
			$should_run_search_queries = ! isset( $search_ids );

			// Run title/URL search only when the scope did not already force an empty result.
			if ( $should_run_search_queries ) {
				if ( $post_types ) {
					$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
					$sql_parts[]       = "post_type IN ($type_placeholders)";
					$sql_values        = array_merge( $sql_values, $post_types );
				}

				if ( $post_status ) {
					$status_placeholders = implode( ', ', array_fill( 0, count( $post_status ), '%s' ) );
					$sql_parts[]         = "post_status IN ($status_placeholders)";
					$sql_values          = array_merge( $sql_values, $post_status );
				}

				if ( $post_mime_types ) {
					$mime_placeholders = implode( ', ', array_fill( 0, count( $post_mime_types ), '%s' ) );
					$sql_parts[]       = "post_mime_type IN ($mime_placeholders)";
					$sql_values        = array_merge( $sql_values, $post_mime_types );
				}

				if ( $author_not_in ) {
					$author_placeholders = implode( ', ', array_fill( 0, count( $author_not_in ), '%d' ) );
					$sql_parts[]         = "post_author NOT IN ($author_placeholders)";
					$sql_values          = array_merge( $sql_values, $author_not_in );
				}

				$search_clauses = array(
					'post_title LIKE %s',
					'post_name LIKE %s',
					'guid LIKE %s',
				);
				$sql_values[]   = $like_term;
				$sql_values[]   = $like_term;
				$sql_values[]   = $like_term;

				if ( ctype_digit( $filter_text ) ) {
					$search_clauses[] = 'ID = %d';
					$sql_values[]     = (int) $filter_text;
				}

				$sql_parts[] = '(' . implode( ' OR ', $search_clauses ) . ')';

				foreach ( (array) $scoped_post_id_scope['chunks'] as $this_scoped_post_id_chunk ) {
					$this_sql_parts  = $sql_parts;
					$this_sql_values = $sql_values;

					// Related media modal searches must stay limited to their explicit attachment ID set.
					if ( is_array( $this_scoped_post_id_chunk ) ) {
						$scoped_post_id_placeholders = implode( ', ', array_fill( 0, count( $this_scoped_post_id_chunk ), '%d' ) );
						$this_sql_parts[]            = "ID IN ($scoped_post_id_placeholders)";
						$this_sql_values             = array_merge( $this_sql_values, $this_scoped_post_id_chunk );
					}

					$sql = "SELECT ID FROM {$wpdb->posts}";

					if ( $this_sql_parts ) {
						$sql .= ' WHERE ' . implode( ' AND ', $this_sql_parts );
					}

					$sql .= ' ORDER BY ID DESC';

					// Dynamic query with placeholders is prepared immediately below.
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$prepared_sql = $wpdb->prepare( $sql, ...$this_sql_values );

					if ( isset( $request_sql_cache[ $prepared_sql ] ) ) {
						$this_search_ids = $request_sql_cache[ $prepared_sql ];
					} else {
						// Safe: $prepared_sql is built immediately above from generated placeholders and prepared values.
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
						$this_search_ids = $wpdb->get_col( $prepared_sql );

						if ( $wpdb->last_error ) {
							ai4seo_debug_message( 984321701, 'Database error: ' . $wpdb->last_error );
							$this_search_ids = array();
						}

						$request_sql_cache[ $prepared_sql ] = $this_search_ids;
					}

					$search_ids = array_merge( (array) $search_ids, (array) $this_search_ids );
				}
			}

			if ( ! is_array( $search_ids ) ) {
				$search_ids = array();
			}

			$search_ids = array_map( 'intval', $search_ids );

			if ( '' !== $filter_language ) {
				$search_ids = ai4seo_filter_post_ids_by_language( $search_ids, $filter_language );
			}

			// Apply WPML exclusions after selected-language filtering because both use per-entry language details.
			if ( $disabled_wpml_language_codes ) {
				$search_ids = ai4seo_filter_post_ids_by_disabled_wpml_languages( $search_ids, $disabled_wpml_language_codes );
			}

			if ( ! empty( $args['search_file_meta'] ) && ( ! $is_scoped_post_ids_active || $scoped_post_ids ) ) {
				$meta_sql_parts = array();
				$meta_values    = array();

				if ( $post_types ) {
					$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
					$meta_sql_parts[]  = 'p.post_type IN (' . $type_placeholders . ')';
					$meta_values       = array_merge( $meta_values, $post_types );
				}

				if ( $post_status ) {
					$status_placeholders = implode( ', ', array_fill( 0, count( $post_status ), '%s' ) );
					$meta_sql_parts[]    = 'p.post_status IN (' . $status_placeholders . ')';
					$meta_values         = array_merge( $meta_values, $post_status );
				}

				if ( $post_mime_types ) {
					$mime_placeholders = implode( ', ', array_fill( 0, count( $post_mime_types ), '%s' ) );
					$meta_sql_parts[]  = 'p.post_mime_type IN (' . $mime_placeholders . ')';
					$meta_values       = array_merge( $meta_values, $post_mime_types );
				}

				if ( $author_not_in ) {
					$author_placeholders = implode( ', ', array_fill( 0, count( $author_not_in ), '%d' ) );
					$meta_sql_parts[]    = 'p.post_author NOT IN (' . $author_placeholders . ')';
					$meta_values         = array_merge( $meta_values, $author_not_in );
				}

				$meta_sql_parts[] = 'pm.meta_key = %s';
				$meta_values[]    = '_wp_attached_file';
				$meta_sql_parts[] = 'pm.meta_value LIKE %s';
				$meta_values[]    = $like_term;

				foreach ( (array) $scoped_post_id_scope['chunks'] as $this_scoped_post_id_chunk ) {
					$this_meta_sql_parts = $meta_sql_parts;
					$this_meta_values    = $meta_values;

					// Filename searches in related media stay inside the modal's explicit attachment scope.
					if ( is_array( $this_scoped_post_id_chunk ) ) {
						$scoped_post_id_placeholders = implode( ', ', array_fill( 0, count( $this_scoped_post_id_chunk ), '%d' ) );
						$this_meta_sql_parts[]       = "p.ID IN ($scoped_post_id_placeholders)";
						$this_meta_values            = array_merge( $this_meta_values, $this_scoped_post_id_chunk );
					}

					$meta_sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id";

					if ( $this_meta_sql_parts ) {
						$meta_sql .= ' WHERE ' . implode( ' AND ', $this_meta_sql_parts );
					}

					$meta_sql .= ' ORDER BY p.ID DESC';

					// Dynamic query with placeholders is prepared immediately below.
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$prepared_meta_sql = $wpdb->prepare( $meta_sql, ...$this_meta_values );

					if ( isset( $request_meta_sql_cache[ $prepared_meta_sql ] ) ) {
						$meta_ids = $request_meta_sql_cache[ $prepared_meta_sql ];
					} else {
						// Safe: $prepared_meta_sql is built immediately above from generated placeholders and prepared values.
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
						$meta_ids = $wpdb->get_col( $prepared_meta_sql );

						if ( $wpdb->last_error ) {
							ai4seo_debug_message( 984321702, 'Database error: ' . $wpdb->last_error );
							$meta_ids = array();
						}

						$request_meta_sql_cache[ $prepared_meta_sql ] = $meta_ids;
					}

					if ( $meta_ids ) {
						$meta_ids   = array_map( 'intval', $meta_ids );
						$search_ids = array_values( array_unique( array_merge( $search_ids, $meta_ids ) ) );

						if ( '' !== $filter_language ) {
							$search_ids = ai4seo_filter_post_ids_by_language( $search_ids, $filter_language );
						}
					}
				}
			}

			// Filename metadata matches are merged later, so reapply WPML exclusions after all search IDs are known.
			if ( $disabled_wpml_language_codes ) {
				$search_ids = ai4seo_filter_post_ids_by_disabled_wpml_languages( $search_ids, $disabled_wpml_language_codes );
			}

			if ( $disabled_taxonomy_terms ) {
				$search_ids = ai4seo_filter_post_ids_by_disabled_taxonomy_terms( $search_ids, $disabled_taxonomy_terms );
			}
		}

		$active_query_args = array();

		if ( '' !== $filter_text ) {
			$active_query_args['ai4seo_filter_text'] = $filter_text;
		}

		if ( 'all' !== $filter_status ) {
			$active_query_args['ai4seo_filter_status'] = $filter_status;
		}

		if ( '' !== $filter_language ) {
			$active_query_args['ai4seo_filter_language'] = $filter_language;
		}

		if ( ! empty( $active_query_args ) && $is_filter_nonce_valid ) {
			$active_query_args[ $nonce_name ] = $filter_nonce;
		}

		if ( 'id' !== $orderby || 'desc' !== $order ) {
			$active_query_args['orderby'] = $orderby;
			$active_query_args['order']   = $order;
		}

		$form_action_url      = trim( (string) $args['form_action'] );
		$action_hidden_fields = array();

		if ( '' !== $form_action_url && strpos( $form_action_url, '?' ) !== false ) {
			list($form_action_path, $form_action_query) = explode( '?', $form_action_url, 2 );
			$form_action_url                            = $form_action_path;

			if ( '' !== $form_action_query ) {
				$parsed_query = array();
				wp_parse_str( $form_action_query, $parsed_query );

				if ( ! empty( $parsed_query ) && is_array( $parsed_query ) ) {
					foreach ( $parsed_query as $query_key => $query_value ) {
						if ( ! is_scalar( $query_value ) ) {
							continue;
						}

						$action_hidden_fields[ $query_key ] = $query_value;
					}
				}
			}
		}

		if ( '' === $form_action_url ) {
			$form_action_url = function_exists( 'admin_url' ) ? admin_url( 'admin.php' ) : 'admin.php';
		}

		$hidden_fields = $action_hidden_fields;

		$current_wpml_lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
		if ( '' !== $current_wpml_lang ) {
			$hidden_fields['lang'] = $current_wpml_lang;
		}

		if ( ! empty( $args['hidden_fields'] ) && is_array( $args['hidden_fields'] ) ) {
			foreach ( $args['hidden_fields'] as $field_name => $field_value ) {
				if ( ! is_scalar( $field_value ) ) {
					continue;
				}

				$hidden_fields[ $field_name ] = $field_value;
			}
		}

		$reset_url = '' !== $args['form_action'] ? $args['form_action'] : $form_action_url;

		return array(
			'filter_text'                  => $filter_text,
			'filter_status'                => $filter_status,
			'filter_language'              => $filter_language,
			'status_options'               => $allowed_statuses,
			'search_ids'                   => $search_ids,
			'per_page'                     => $per_page,
			'html'                         => '',
			'query_args'                   => $active_query_args,
			'orderby'                      => $orderby,
			'order'                        => $order,
			'form_action_url'              => $form_action_url,
			'hidden_fields'                => $hidden_fields,
			'nonce_action'                 => $nonce_action,
			'nonce_name'                   => $nonce_name,
			'language_options'             => $language_options,
			'disabled_wpml_language_codes' => $disabled_wpml_language_codes,
			'reset_url'                    => $reset_url,
		);
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_waiting_to_get_queued_post_ids' ) ) {
	/**
	 * Returns missing entry IDs that SEO Autopilot can still excavate into the Pending queue.
	 *
	 * @param array $candidate_ids Candidate IDs respecting the current base filters.
	 * @param array $missing_ids Missing metadata or attachment attribute IDs.
	 * @param array $pending_ids Pending queue IDs.
	 * @param array $processing_ids Processing queue IDs.
	 * @param array $failed_ids Failed queue IDs.
	 * @param array $args Additional state for auto queue eligibility.
	 *
	 * @return array Waiting-to-queue IDs preserving candidate order.
	 */
	function ai4seo_get_content_type_waiting_to_get_queued_post_ids(
		array $candidate_ids,
		array $missing_ids,
		array $pending_ids,
		array $processing_ids,
		array $failed_ids,
		array $args
	): array {
		$is_bulk_generation_activated              = (bool) ( $args['is_bulk_generation_activated'] ?? false );
		$should_auto_queue_bulk_generation_entries = (bool) ( $args['should_auto_queue_bulk_generation_entries'] ?? false );
		$has_enough_credits                        = (bool) ( $args['has_enough_credits'] ?? false );
		$hidden_ids                                = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $args['hidden_ids'] ?? array() ) ) ) ) );
		$auto_queue_disallowed_ids                 = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $args['auto_queue_disallowed_ids'] ?? array() ) ) ) ) );

		if ( ! $is_bulk_generation_activated || ! $should_auto_queue_bulk_generation_entries || ! $has_enough_credits ) {
			return array();
		}

		$candidate_ids = array_values( array_unique( array_filter( array_map( 'intval', $candidate_ids ) ) ) );
		$missing_ids   = array_values( array_unique( array_filter( array_map( 'intval', $missing_ids ) ) ) );
		$excluded_ids  = array_merge(
			array_values( array_unique( array_filter( array_map( 'intval', $pending_ids ) ) ) ),
			array_values( array_unique( array_filter( array_map( 'intval', $processing_ids ) ) ) ),
			array_values( array_unique( array_filter( array_map( 'intval', $failed_ids ) ) ) ),
			$hidden_ids,
			$auto_queue_disallowed_ids
		);

		if ( ! $candidate_ids || ! $missing_ids ) {
			return array();
		}

		$waiting_to_get_queued_post_ids = array_values(
			array_diff(
				array_intersect( $candidate_ids, $missing_ids ),
				$excluded_ids
			)
		);

		if ( ! $waiting_to_get_queued_post_ids ) {
			return array();
		}

		$new_or_existing_filter = sanitize_key( (string) ( $args['new_or_existing_filter'] ?? 'both' ) );
		$reference_timestamp    = (int) ( $args['new_or_existing_filter_reference_timestamp'] ?? 0 );

		if ( ! in_array( $new_or_existing_filter, array( 'new', 'existing' ), true ) ) {
			return $waiting_to_get_queued_post_ids;
		}

		$filtered_waiting_to_get_queued_post_ids = array();

		foreach ( $waiting_to_get_queued_post_ids as $this_post_id ) {
			$this_post = get_post( $this_post_id );

			if ( ! $this_post || is_wp_error( $this_post ) ) {
				continue;
			}

			$this_post_date_timestamp = (int) get_post_time( 'U', true, $this_post );

			if ( 'new' === $new_or_existing_filter && $this_post_date_timestamp > $reference_timestamp ) {
				$filtered_waiting_to_get_queued_post_ids[] = $this_post_id;
			} elseif ( 'existing' === $new_or_existing_filter && $this_post_date_timestamp <= $reference_timestamp ) {
				$filtered_waiting_to_get_queued_post_ids[] = $this_post_id;
			}
		}

		return $filtered_waiting_to_get_queued_post_ids;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_filter_status_counts' ) ) {
	/**
	 * Counts available entries for each status while respecting the current base candidate set.
	 *
	 * @param array $candidate_ids  Candidate IDs before applying the selected status filter.
	 * @param array $status_map     Map of status => array of IDs.
	 * @param array $status_options Available status options.
	 * @param array $hidden_ids     Hidden IDs to exclude from normal statuses.
	 *
	 * @return array Status counts keyed by status.
	 */
	function ai4seo_get_content_type_filter_status_counts( array $candidate_ids, array $status_map, array $status_options, array $hidden_ids = array() ): array {
		$candidate_ids         = array_values( array_unique( array_map( 'intval', $candidate_ids ) ) );
		$hidden_ids            = array_values( array_unique( array_filter( array_map( 'intval', $hidden_ids ) ) ) );
		$visible_candidate_ids = array_values( array_diff( $candidate_ids, $hidden_ids ) );
		$status_counts         = array(
			'all' => count( $visible_candidate_ids ),
		);

		foreach ( $status_options as $this_status_key => $this_status_label ) {
			$this_status_key = sanitize_key( $this_status_key );

			if ( '' === $this_status_key || 'all' === $this_status_key ) {
				continue;
			}

			if ( 'hidden' === $this_status_key ) {
				$status_counts[ $this_status_key ] = count( ai4seo_filter_post_ids_by_status( $candidate_ids, $this_status_key, $status_map ) );
				continue;
			}

			$status_counts[ $this_status_key ] = count( ai4seo_filter_post_ids_by_status( $visible_candidate_ids, $this_status_key, $status_map ) );
		}

		return $status_counts;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_filter_query_args' ) ) {
	/**
	 * Returns sanitized query arguments from a shared content-type filter context.
	 *
	 * @param array $filter_context Filter context from ai4seo_setup_content_type_filters().
	 * @return array Sanitized query args for pagination and list links.
	 */
	function ai4seo_get_content_type_filter_query_args( array $filter_context ): array {
		$filter_query_args = array();

		if ( empty( $filter_context['query_args'] ) || ! is_array( $filter_context['query_args'] ) ) {
			return $filter_query_args;
		}

		// Keep pagination links aligned with the filter form while preserving the existing sanitation behavior.
		foreach ( $filter_context['query_args'] as $this_query_key => $this_query_value ) {
			$filter_query_args[ sanitize_key( $this_query_key ) ] = sanitize_text_field( $this_query_value );
		}

		return $filter_query_args;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_metadata_status_option_name' ) ) {
	/**
	 * Returns the metadata status option name for a content list status.
	 *
	 * @param string $status Status key.
	 * @return string Option name or empty string.
	 */
	function ai4seo_get_content_type_metadata_status_option_name( string $status ): string {
		return ai4seo_get_content_type_status_option_name( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $status );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_option_name' ) ) {
	/**
	 * Returns a status option name for metadata or attachment-attribute lists.
	 *
	 * @param string $content_context List context.
	 * @param string $status Status key.
	 * @return string Option name or empty string.
	 */
	function ai4seo_get_content_type_status_option_name( string $content_context, string $status ): string {
		$status              = sanitize_key( $status );
		$status_option_names = ai4seo_get_content_type_status_option_names( $content_context );

		return (string) ( $status_option_names[ $status ] ?? '' );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_metadata_status_ids' ) ) {
	/**
	 * Reads post IDs for a metadata status option.
	 *
	 * @param string $status Status key.
	 * @return array Post IDs.
	 */
	function ai4seo_get_content_type_metadata_status_ids( string $status ): array {
		return ai4seo_get_content_type_status_ids( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $status );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_ids' ) ) {
	/**
	 * Reads post IDs for one content-list status with a request-local option cache.
	 *
	 * @param string $content_context List context.
	 * @param string $status Status key.
	 * @return array Post IDs.
	 */
	function ai4seo_get_content_type_status_ids( string $content_context, string $status ): array {
		$option_name = ai4seo_get_content_type_status_option_name( $content_context, $status );

		if ( '' === $option_name ) {
			return array();
		}

		return ai4seo_get_content_type_option_post_ids( $option_name );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_option_post_ids' ) ) {
	/**
	 * Reads post IDs from one status option and caches normalized IDs for this request.
	 *
	 * @param string $option_name Option name.
	 * @return array Post IDs.
	 */
	function ai4seo_get_content_type_option_post_ids( string $option_name ): array {
		static $option_post_ids_cache = array();

		$option_name = sanitize_key( $option_name );

		if ( '' === $option_name ) {
			return array();
		}

		// Large list rendering may check several row states against the same option.
		if ( ! isset( $option_post_ids_cache[ $option_name ] ) ) {
			$option_post_ids_cache[ $option_name ] = array_values(
				array_unique(
					array_filter(
						array_map(
							'absint',
							ai4seo_get_post_ids_from_option( $option_name )
						)
					)
				)
			);
		}

		return $option_post_ids_cache[ $option_name ];
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_post_list_sql_parts' ) ) {
	/**
	 * Builds shared SQL parts for optimized content type post-list queries.
	 *
	 * @param string|array $post_type Post type or post types.
	 * @param array        $post_statuses Post statuses.
	 * @param array        $author_not_in Author IDs to exclude.
	 * @param string       $filter_text Search text.
	 * @param array        $post_mime_types Optional post mime types.
	 * @return array SQL parts and values.
	 */
	function ai4seo_get_content_type_post_list_sql_parts( $post_type, array $post_statuses, array $author_not_in = array(), string $filter_text = '', array $post_mime_types = array() ): array {
		global $wpdb;

		$post_types      = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $post_type ) ) ) );
		$post_types      = array_slice( $post_types, 0, 256 );
		$post_statuses   = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_statuses ) ) ) );
		$post_statuses   = array_slice( $post_statuses, 0, 256 );
		$post_mime_types = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $post_mime_types ) ) ) );
		$post_mime_types = array_slice( $post_mime_types, 0, 256 );
		$author_not_in   = array_values( array_unique( array_filter( array_map( 'absint', $author_not_in ) ) ) );
		$author_not_in   = array_slice( $author_not_in, 0, 256 );
		$where_parts     = array();
		$values          = array();

		// Build the same base wp_posts constraints for count and page reads so totals and rows stay aligned.
		if ( $post_types ) {
			$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$where_parts[]     = "post_type IN ($type_placeholders)";
			$values            = array_merge( $values, $post_types );
		}

		if ( $post_statuses ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
			$where_parts[]       = "post_status IN ($status_placeholders)";
			$values              = array_merge( $values, $post_statuses );
		}

		if ( $post_mime_types ) {
			$mime_placeholders = implode( ', ', array_fill( 0, count( $post_mime_types ), '%s' ) );
			$where_parts[]     = "post_mime_type IN ($mime_placeholders)";
			$values            = array_merge( $values, $post_mime_types );
		}

		if ( $author_not_in ) {
			$author_placeholders = implode( ', ', array_fill( 0, count( $author_not_in ), '%d' ) );
			$where_parts[]       = "post_author NOT IN ($author_placeholders)";
			$values              = array_merge( $values, $author_not_in );
		}

		$filter_text = sanitize_text_field( $filter_text );

		// Search stays inside SQL for optimized post lists, avoiding the old all-ID search materialization path.
		if ( '' !== $filter_text ) {
			$like_term      = '%' . $wpdb->esc_like( $filter_text ) . '%';
			$search_clauses = array(
				'post_title LIKE %s',
				'post_name LIKE %s',
				'guid LIKE %s',
			);
			$values[]       = $like_term;
			$values[]       = $like_term;
			$values[]       = $like_term;

			if ( ctype_digit( $filter_text ) ) {
				$search_clauses[] = 'ID = %d';
				$values[]         = (int) $filter_text;
			}

			$where_parts[] = '(' . implode( ' OR ', $search_clauses ) . ')';
		}

		return array(
			'where_sql' => $where_parts ? ' WHERE ' . implode( ' AND ', $where_parts ) : '',
			'values'    => $values,
		);
	}
}

if ( ! function_exists( 'ai4seo_read_content_type_post_list_all_ids' ) ) {
	/**
	 * Reads all post IDs matching optimized content type list constraints.
	 *
	 * @param string|array $post_type Post type or post types.
	 * @param array        $post_statuses Post statuses.
	 * @param array        $author_not_in Author IDs to exclude.
	 * @param string       $filter_text Search text.
	 * @param array        $post_mime_types Optional post mime types.
	 * @return array Post IDs.
	 */
	function ai4seo_read_content_type_post_list_all_ids( $post_type, array $post_statuses, array $author_not_in = array(), string $filter_text = '', array $post_mime_types = array() ): array {
		global $wpdb;

		$sql_parts = ai4seo_get_content_type_post_list_sql_parts( $post_type, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
		$sql       = "SELECT ID FROM {$wpdb->posts}" . $sql_parts['where_sql'] . ' ORDER BY ID DESC';
		$values    = $sql_parts['values'];

		if ( $values ) {
			// Dynamic query with placeholders is prepared immediately below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $sql, ...$values );
		}

		// Safe: SQL fragments come from ai4seo_get_content_type_post_list_sql_parts() and values are prepared when placeholders are present.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$post_ids = $wpdb->get_col( $sql );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321714, 'Database error: ' . $wpdb->last_error );
			return array();
		}

		return array_values( array_map( 'intval', (array) $post_ids ) );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_post_list_orderby_sql' ) ) {
	/**
	 * Builds the ORDER BY clause for optimized content type post-list queries.
	 *
	 * @param string $orderby Sort key.
	 * @param string $order Sort direction.
	 * @return string ORDER BY clause.
	 */
	function ai4seo_get_content_type_post_list_orderby_sql( string $orderby, string $order ): string {
		$sort_args = ai4seo_normalize_content_type_sort_args( $orderby, $order );
		$order_sql = ( 'asc' === $sort_args['order'] ) ? 'ASC' : 'DESC';

		// Optimized SQL paging only handles ID sorting; title and progress sorting keep the exact PHP sorter.
		return " ORDER BY ID {$order_sql}";
	}
}

if ( ! function_exists( 'ai4seo_count_content_type_post_list_entries' ) ) {
	/**
	 * Counts posts for an optimized content type list query.
	 *
	 * @param string|array $post_type Post type or post types.
	 * @param array        $post_statuses Post statuses.
	 * @param array        $author_not_in Author IDs to exclude.
	 * @param string       $filter_text Search text.
	 * @param array        $post_mime_types Optional post mime types.
	 * @param array        $disabled_wpml_language_codes Optional disabled WPML language codes.
	 * @return int Count, or 0 on error.
	 */
	function ai4seo_count_content_type_post_list_entries( $post_type, array $post_statuses, array $author_not_in = array(), string $filter_text = '', array $post_mime_types = array(), array $disabled_wpml_language_codes = array() ): int {
		global $wpdb;

		// WPML exclusions need per-entry language checks, so count through the exact ID path instead of SQL COUNT.
		if ( $disabled_wpml_language_codes ) {
			$post_ids = ai4seo_read_content_type_post_list_all_ids( $post_type, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
			$post_ids = ai4seo_filter_post_ids_by_disabled_wpml_languages( $post_ids, $disabled_wpml_language_codes );

			return count( $post_ids );
		}

		$sql_parts = ai4seo_get_content_type_post_list_sql_parts( $post_type, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
		$sql       = "SELECT COUNT(ID) FROM {$wpdb->posts}" . $sql_parts['where_sql'];
		$values    = $sql_parts['values'];

		// Prepare only after all dynamic WHERE pieces are assembled.
		if ( $values ) {
			// Dynamic query with placeholders is prepared immediately below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $sql, ...$values );
		}

		// Safe: SQL fragments come from ai4seo_get_content_type_post_list_sql_parts() and values are prepared when placeholders are present.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = (int) $wpdb->get_var( $sql );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321710, 'Database error: ' . $wpdb->last_error );
			return 0;
		}

		return $count;
	}
}

if ( ! function_exists( 'ai4seo_read_content_type_post_list_page_ids' ) ) {
	/**
	 * Reads one page of post IDs using bounded SQL.
	 *
	 * @param string|array $post_type Post type or post types.
	 * @param array        $post_statuses Post statuses.
	 * @param array        $author_not_in Author IDs to exclude.
	 * @param string       $filter_text Search text.
	 * @param string       $orderby Sort key.
	 * @param string       $order Sort direction.
	 * @param int          $offset Visible offset.
	 * @param int          $limit Page size.
	 * @param array        $excluded_post_ids Post IDs to skip.
	 * @param array        $post_mime_types Optional post mime types.
	 * @return array Post IDs.
	 */
	function ai4seo_read_content_type_post_list_page_ids(
		$post_type,
		array $post_statuses,
		array $author_not_in,
		string $filter_text,
		string $orderby,
		string $order,
		int $offset,
		int $limit,
		array $excluded_post_ids = array(),
		array $post_mime_types = array()
	): array {
		global $wpdb;

		if ( $limit < 1 ) {
			return array();
		}

		$offset              = max( 0, $offset );
		$excluded_post_ids   = array_values( array_unique( array_filter( array_map( 'absint', $excluded_post_ids ) ) ) );
		$sql_parts           = ai4seo_get_content_type_post_list_sql_parts( $post_type, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
		$orderby_sql         = ai4seo_get_content_type_post_list_orderby_sql( $orderby, $order );
		$database_chunk_size = ai4seo_get_database_chunk_size();

		// Use a direct NOT IN query when the exclusion list fits the configured database chunk size.
		if ( ! $excluded_post_ids || count( $excluded_post_ids ) <= $database_chunk_size ) {
			$values    = $sql_parts['values'];
			$where_sql = $sql_parts['where_sql'];

			if ( $excluded_post_ids ) {
				$excluded_placeholders = implode( ', ', array_fill( 0, count( $excluded_post_ids ), '%d' ) );
				$where_sql            .= ( '' === $where_sql ? ' WHERE ' : ' AND ' ) . "ID NOT IN ($excluded_placeholders)";
				$values                = array_merge( $values, $excluded_post_ids );
			}

			$sql      = "SELECT ID FROM {$wpdb->posts}{$where_sql}{$orderby_sql} LIMIT %d OFFSET %d";
			$values[] = $limit;
			$values[] = $offset;

			// Safe: WHERE/ORDER fragments are generated by local helpers and all variable values are prepared below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$post_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$values ) );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321711, 'Database error: ' . $wpdb->last_error );
				return array();
			}

			return array_values( array_map( 'intval', (array) $post_ids ) );
		}

		// Very large hidden lists are handled in small page batches to avoid oversized NOT IN clauses.
		$excluded_post_id_lookup = array_flip( $excluded_post_ids );
		$visible_post_ids        = array();
		$visible_index           = 0;
		$raw_offset              = 0;
		$batch_size              = max( $limit * 5, 100 );

		while ( count( $visible_post_ids ) < $limit ) {
			$values   = $sql_parts['values'];
			$sql      = "SELECT ID FROM {$wpdb->posts}{$sql_parts['where_sql']}{$orderby_sql} LIMIT %d OFFSET %d";
			$values[] = $batch_size;
			$values[] = $raw_offset;

			// Safe: WHERE/ORDER fragments are generated by local helpers and all variable values are prepared below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$batch_post_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$values ) );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321712, 'Database error: ' . $wpdb->last_error );
				return array();
			}

			if ( ! $batch_post_ids ) {
				break;
			}

			$raw_offset += count( $batch_post_ids );

			foreach ( $batch_post_ids as $this_post_id ) {
				$this_post_id = (int) $this_post_id;

				if ( isset( $excluded_post_id_lookup[ $this_post_id ] ) ) {
					continue;
				}

				if ( $visible_index < $offset ) {
					++$visible_index;
					continue;
				}

				$visible_post_ids[] = $this_post_id;
				++$visible_index;

				if ( count( $visible_post_ids ) >= $limit ) {
					break;
				}
			}
		}

		return $visible_post_ids;
	}
}

if ( ! function_exists( 'ai4seo_validate_content_type_post_ids_for_list' ) ) {
	/**
	 * Validates candidate IDs against post type, status, author and search filters.
	 *
	 * @param array        $post_ids Candidate post IDs.
	 * @param string|array $post_type Post type or post types.
	 * @param array        $post_statuses Post statuses.
	 * @param array        $author_not_in Author IDs to exclude.
	 * @param string       $filter_text Search text.
	 * @param array        $post_mime_types Optional post mime types.
	 * @param array        $disabled_wpml_language_codes Optional disabled WPML language codes.
	 * @return array Valid post IDs preserving candidate order.
	 */
	function ai4seo_validate_content_type_post_ids_for_list( array $post_ids, $post_type, array $post_statuses, array $author_not_in = array(), string $filter_text = '', array $post_mime_types = array(), array $disabled_wpml_language_codes = array() ): array {
		global $wpdb;

		$post_ids        = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
		$post_types      = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $post_type ) ) ) );
		$post_types      = array_slice( $post_types, 0, 256 );
		$post_statuses   = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_statuses ) ) ) );
		$author_not_in   = array_values( array_unique( array_filter( array_map( 'absint', $author_not_in ) ) ) );
		$post_mime_types = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $post_mime_types ) ) ) );
		$post_mime_types = array_slice( $post_mime_types, 0, 256 );
		$filter_text     = sanitize_text_field( $filter_text );

		if ( ! $post_ids || ! $post_types || ! $post_statuses ) {
			return array();
		}

		$valid_post_id_lookup = array();
		$database_chunk_size  = ai4seo_get_database_chunk_size();
		$post_id_chunks       = array_chunk( $post_ids, $database_chunk_size );

		// Validate option-stored IDs in chunks so filtered status views avoid scanning the entire post type.
		foreach ( $post_id_chunks as $this_post_id_chunk ) {
			if ( ! $this_post_id_chunk ) {
				continue;
			}

			$id_placeholders     = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );
			$type_placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$status_placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
			$sql_parts           = array(
				"ID IN ($id_placeholders)",
				"post_type IN ($type_placeholders)",
				"post_status IN ($status_placeholders)",
			);
			$values              = array_merge( $this_post_id_chunk, $post_types, $post_statuses );

			if ( $post_mime_types ) {
				$mime_placeholders = implode( ', ', array_fill( 0, count( $post_mime_types ), '%s' ) );
				$sql_parts[]       = "post_mime_type IN ($mime_placeholders)";
				$values            = array_merge( $values, $post_mime_types );
			}

			if ( $author_not_in ) {
				$author_placeholders = implode( ', ', array_fill( 0, count( $author_not_in ), '%d' ) );
				$sql_parts[]         = "post_author NOT IN ($author_placeholders)";
				$values              = array_merge( $values, $author_not_in );
			}

			// Apply the same SQL search clauses used by the paged resolver when a text filter is active.
			if ( '' !== $filter_text ) {
				$like_term      = '%' . $wpdb->esc_like( $filter_text ) . '%';
				$search_clauses = array(
					'post_title LIKE %s',
					'post_name LIKE %s',
					'guid LIKE %s',
				);
				$values[]       = $like_term;
				$values[]       = $like_term;
				$values[]       = $like_term;

				if ( ctype_digit( $filter_text ) ) {
					$search_clauses[] = 'ID = %d';
					$values[]         = (int) $filter_text;
				}

				$sql_parts[] = '(' . implode( ' OR ', $search_clauses ) . ')';
			}

			$sql = "SELECT ID FROM {$wpdb->posts} WHERE " . implode( ' AND ', $sql_parts );

			// Safe: SQL parts use generated placeholders and sanitized post filters; all variable values are prepared below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$valid_post_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$values ) );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321713, 'Database error: ' . $wpdb->last_error );
				return array();
			}

			foreach ( (array) $valid_post_ids as $this_valid_post_id ) {
				$valid_post_id_lookup[ (int) $this_valid_post_id ] = true;
			}
		}

		$filtered_post_ids = array();

		// Preserve the option order after validation so status-filtered pagination remains stable.
		foreach ( $post_ids as $this_post_id ) {
			if ( isset( $valid_post_id_lookup[ (int) $this_post_id ] ) ) {
				$filtered_post_ids[] = (int) $this_post_id;
			}
		}

		// Apply WPML exclusions after base SQL validation so disabled languages cannot re-enter status counts.
		if ( $disabled_wpml_language_codes ) {
			$filtered_post_ids = ai4seo_filter_post_ids_by_disabled_wpml_languages( $filtered_post_ids, $disabled_wpml_language_codes );
		}

		return $filtered_post_ids;
	}
}

if ( ! function_exists( 'ai4seo_intersect_content_type_option_ids_with_page' ) ) {
	/**
	 * Reads one status option and intersects it with current page post IDs.
	 *
	 * @param string $option_name Option name.
	 * @param array  $current_page_post_ids Current page post IDs.
	 * @return array Current page IDs found in the option.
	 */
	function ai4seo_intersect_content_type_option_ids_with_page( string $option_name, array $current_page_post_ids ): array {
		$current_page_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $current_page_post_ids ) ) ) );

		if ( ! $current_page_post_ids ) {
			return array();
		}

		// Status membership is needed only for rendered rows, so intersect cached option IDs with the current page.
		$current_page_post_id_lookup = array_flip( $current_page_post_ids );
		$option_post_ids             = ai4seo_get_content_type_option_post_ids( $option_name );

		return array_values(
			array_filter(
				$option_post_ids,
				function ( $post_id ) use ( $current_page_post_id_lookup ) {
					return isset( $current_page_post_id_lookup[ $post_id ] );
				}
			)
		);
	}
}

// =========================================================================================== \\

if ( ! function_exists( 'ai4seo_get_content_type_current_page_status_ids' ) ) {
	/**
	 * Returns status IDs limited to the current page.
	 *
	 * @param array  $current_page_post_ids Current page post IDs.
	 * @param string $content_context List context.
	 * @return array Status IDs keyed by list status.
	 */
	function ai4seo_get_content_type_current_page_status_ids( array $current_page_post_ids, string $content_context = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ): array {
		$current_page_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $current_page_post_ids ) ) ) );
		$status_options        = ai4seo_get_content_type_status_option_names( $content_context );

		if ( ! $current_page_post_ids ) {
			return array(
				'complete'              => array(),
				'missing'               => array(),
				'generated'             => array(),
				'queued'                => array(),
				'processing'            => array(),
				'failed'                => array(),
				'hidden'                => array(),
				'auto_queue_disallowed' => array(),
			);
		}

		$status_ids = array();

		// Mirror the row-rendering status arrays while limiting every list to the current page.
		foreach ( $status_options as $this_status => $this_option_name ) {
			$status_ids[ $this_status ] = ai4seo_intersect_content_type_option_ids_with_page( $this_option_name, $current_page_post_ids );
		}

		return $status_ids;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_exact_status_counts_from_options' ) ) {
	/**
	 * Builds exact status counts from status ID options and bounded validation queries.
	 *
	 * @param array $args Count arguments.
	 * @return array Status counts.
	 */
	function ai4seo_get_content_type_exact_status_counts_from_options( array $args ): array {
		$content_context              = ai4seo_normalize_content_type_list_context( (string) ( $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) );
		$post_types                   = ! empty( $args['post_types'] )
			? (array) $args['post_types']
			: array( (string) ( $args['post_type'] ?? '' ) );
		$post_types                   = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
		$post_statuses                = (array) ( $args['post_status'] ?? array( 'publish', 'future' ) );
		$author_not_in                = (array) ( $args['author_not_in'] ?? array() );
		$post_mime_types              = (array) ( $args['post_mime_types'] ?? array() );
		$filter_text                  = sanitize_text_field( (string) ( $args['filter_text'] ?? '' ) );
		$status_options               = isset( $args['status_options'] ) && is_array( $args['status_options'] ) ? $args['status_options'] : array();
		$all_count                    = max( 0, (int) ( $args['all_count'] ?? 0 ) );
		$hidden_ids                   = isset( $args['hidden_ids'] ) && is_array( $args['hidden_ids'] ) ? $args['hidden_ids'] : array();
		$disabled_wpml_language_codes = ai4seo_sanitize_wpml_language_codes( $args['disabled_wpml_language_codes'] ?? array(), true );

		// Reuse already validated hidden IDs from the resolver when available to avoid a duplicate chunk pass.
		if ( empty( $args['hidden_ids_are_validated'] ) ) {
			$hidden_ids = ai4seo_validate_content_type_post_ids_for_list( $hidden_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types, $disabled_wpml_language_codes );
		}

		$hidden_id_lookup               = array_flip( $hidden_ids );
		$status_counts                  = array(
			'all' => max( 0, $all_count - count( $hidden_ids ) ),
		);
		$validated_status_ids_by_status = array(
			'hidden' => $hidden_ids,
		);

		// Exact counts validate each status option against the active post-type filters.
		foreach ( $status_options as $this_status => $this_status_label ) {
			$this_status = sanitize_key( $this_status );

			if ( '' === $this_status || 'all' === $this_status ) {
				continue;
			}

			if ( 'waiting_to_queue' === $this_status ) {
				continue;
			}

			if ( 'hidden' === $this_status ) {
				$status_counts[ $this_status ] = count( $hidden_ids );
				continue;
			}

			$this_status_post_ids = ai4seo_get_content_type_status_ids( $content_context, $this_status );
			$this_status_post_ids = ai4seo_validate_content_type_post_ids_for_list( $this_status_post_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types, $disabled_wpml_language_codes );

			if ( $hidden_id_lookup ) {
				$this_status_post_ids = array_values(
					array_filter(
						$this_status_post_ids,
						function ( $post_id ) use ( $hidden_id_lookup ) {
							return ! isset( $hidden_id_lookup[ (int) $post_id ] );
						}
					)
				);
			}

			$validated_status_ids_by_status[ $this_status ] = $this_status_post_ids;
			$status_counts[ $this_status ]                  = count( $this_status_post_ids );
		}

		// Waiting-to-queue depends on queue settings and must be derived from validated missing IDs.
		if ( isset( $status_options['waiting_to_queue'] ) ) {
			$missing_ids = $validated_status_ids_by_status['missing'] ?? array();

			if ( $missing_ids ) {
				$status_counts['waiting_to_queue'] = count(
					ai4seo_get_content_type_waiting_to_get_queued_post_ids(
						$missing_ids,
						$missing_ids,
						ai4seo_get_content_type_status_ids( $content_context, 'queued' ),
						ai4seo_get_content_type_status_ids( $content_context, 'processing' ),
						ai4seo_get_content_type_status_ids( $content_context, 'failed' ),
						array(
							'is_bulk_generation_activated' => (bool) ( $args['is_bulk_generation_activated'] ?? false ),
							'should_auto_queue_bulk_generation_entries' => (bool) ( $args['should_auto_queue_bulk_generation_entries'] ?? false ),
							'has_enough_credits'           => (bool) ( $args['has_enough_credits'] ?? false ),
							'new_or_existing_filter'       => sanitize_key( (string) ( $args['new_or_existing_filter'] ?? 'both' ) ),
							'new_or_existing_filter_reference_timestamp' => (int) ( $args['new_or_existing_filter_reference_timestamp'] ?? 0 ),
							'hidden_ids'                   => $hidden_ids,
							'auto_queue_disallowed_ids'    => ai4seo_get_content_type_status_ids( $content_context, 'auto_queue_disallowed' ),
						)
					)
				);
			} else {
				$status_counts['waiting_to_queue'] = 0;
			}
		}

		return $status_counts;
	}
}

if ( ! function_exists( 'ai4seo_sort_content_type_post_ids_for_list' ) ) {
	/**
	 * Sorts post IDs for optimized list results.
	 *
	 * @param array  $post_ids Post IDs.
	 * @param string $orderby Sort key.
	 * @param string $order Sort direction.
	 * @return array Sorted post IDs.
	 */
	function ai4seo_sort_content_type_post_ids_for_list( array $post_ids, string $orderby, string $order ): array {
		$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
		$orderby  = sanitize_key( $orderby );

		// Title-filtered status lists reuse the shared PHP sorter so they match the legacy list ordering.
		if ( 'title' === $orderby ) {
			return ai4seo_sort_content_type_ids(
				$post_ids,
				'title',
				$order,
				ai4seo_get_content_type_post_title_map( $post_ids )
			);
		}

		return ai4seo_sort_content_type_ids( $post_ids, 'id', $order );
	}
}

if ( ! function_exists( 'ai4seo_resolve_optimized_content_type_list' ) ) {
	/**
	 * Resolves content type list rows without loading every candidate ID for common paths.
	 *
	 * @param array $args Resolver arguments.
	 * @return array Resolver result. `is_optimized` false means caller should use legacy exact path.
	 */
	function ai4seo_resolve_optimized_content_type_list( array $args ): array {
		$content_context              = ai4seo_normalize_content_type_list_context( (string) ( $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) );
		$post_types                   = ! empty( $args['post_types'] )
			? (array) $args['post_types']
			: array( (string) ( $args['post_type'] ?? '' ) );
		$post_types                   = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
		$post_statuses                = (array) ( $args['post_status'] ?? array( 'publish', 'future' ) );
		$post_mime_types              = (array) ( $args['post_mime_types'] ?? array() );
		$author_not_in                = (array) ( $args['author_not_in'] ?? array() );
		$disabled_taxonomy_terms      = isset( $args['disabled_taxonomy_terms'] ) && is_array( $args['disabled_taxonomy_terms'] )
			? $args['disabled_taxonomy_terms']
			: array();
		$disabled_wpml_language_codes = ai4seo_sanitize_wpml_language_codes( $args['disabled_wpml_language_codes'] ?? array(), true );
		$filter_status                = sanitize_key( (string) ( $args['filter_status'] ?? 'all' ) );
		$filter_text                  = sanitize_text_field( (string) ( $args['filter_text'] ?? '' ) );
		$filter_language              = sanitize_text_field( (string) ( $args['filter_language'] ?? '' ) );
		$sort_args                    = ai4seo_normalize_content_type_sort_args( $args['orderby'] ?? 'id', $args['order'] ?? 'desc' );
		$orderby                      = $sort_args['orderby'];
		$order                        = $sort_args['order'];
		$current_page                 = max( 1, (int) ( $args['current_page'] ?? 1 ) );
		$per_page                     = max( 1, (int) ( $args['per_page'] ?? 20 ) );
		$status_options               = isset( $args['status_options'] ) && is_array( $args['status_options'] )
			? $args['status_options']
			: ai4seo_get_content_type_status_options();

		// Normalize bulk-generation state once so counts, cache keys, and waiting-to-queue checks cannot drift.
		$is_bulk_generation_activated               = (bool) ( $args['is_bulk_generation_activated'] ?? false );
		$should_auto_queue_bulk_generation_entries  = (bool) ( $args['should_auto_queue_bulk_generation_entries'] ?? false );
		$has_enough_credits                         = (bool) ( $args['has_enough_credits'] ?? false );
		$new_or_existing_filter                     = sanitize_key( (string) ( $args['new_or_existing_filter'] ?? 'both' ) );
		$new_or_existing_filter_reference_timestamp = (int) ( $args['new_or_existing_filter_reference_timestamp'] ?? 0 );

		// Complex filters keep exact behavior; disabled WPML languages require per-entry checks like language/taxonomy filters.
		if ( ! $post_types
			|| '' !== $filter_language
			|| ! empty( $disabled_taxonomy_terms )
			|| ! empty( $disabled_wpml_language_codes )
			|| 'id' !== $orderby
		) {
			return array( 'is_optimized' => false );
		}

		// Attachment filename search currently depends on the legacy meta-search path.
		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $content_context && '' !== $filter_text ) {
			return array( 'is_optimized' => false );
		}

		if ( 'all' !== $filter_status
			&& 'waiting_to_queue' !== $filter_status
			&& ai4seo_get_content_type_status_option_name( $content_context, $filter_status ) === ''
		) {
			return array( 'is_optimized' => false );
		}

		// Large-site mode is intentionally based on the whole wp_posts table, matching the analysis cache.
		$current_posts_table_entries_count = ai4seo_get_current_posts_table_entries_count();

		if ( $current_posts_table_entries_count < 0 ) {
			return array( 'is_optimized' => false );
		}

		$is_large_site              = ( $current_posts_table_entries_count >= AI4SEO_LARGE_SITE_POSTS_THRESHOLD );
		$should_defer_status_counts = ( $is_large_site && 'all' === $filter_status );
		$cache_version              = function_exists( 'ai4seo_get_content_type_list_cache_version' ) ? ai4seo_get_content_type_list_cache_version() : 1;
		$cache_key                  = 'content_list_' . md5(
			wp_json_encode(
				array(
					'content_context'              => $content_context,
					'post_types'                   => $post_types,
					'post_statuses'                => $post_statuses,
					'post_mime_types'              => $post_mime_types,
					'author_not_in'                => $author_not_in,
					'filter_language'              => $filter_language,
					'disabled_taxonomy_terms'      => $disabled_taxonomy_terms,
					'disabled_wpml_language_codes' => $disabled_wpml_language_codes,
					'filter_status'                => $filter_status,
					'filter_text'                  => $filter_text,
					'orderby'                      => $orderby,
					'order'                        => $order,
					'current_page'                 => $current_page,
					'per_page'                     => $per_page,
					'is_large_site'                => $is_large_site,
					'should_defer_status_counts'   => $should_defer_status_counts,
					'status_options'               => array_keys( $status_options ),
					'is_bulk_generation_activated' => $is_bulk_generation_activated,
					'should_auto_queue_bulk_generation_entries' => $should_auto_queue_bulk_generation_entries,
					'has_enough_credits'           => $has_enough_credits,
					'new_or_existing_filter'       => $new_or_existing_filter,
					'new_or_existing_filter_reference_timestamp' => $new_or_existing_filter_reference_timestamp,
					'cache_version'                => $cache_version,
				)
			)
		);

		static $request_cache = array();

		// Prefer the request cache first so repeated page fragments do not duplicate SQL work.
		if ( isset( $request_cache[ $cache_key ] ) ) {
			return $request_cache[ $cache_key ];
		}

		$cached_result = wp_cache_get( $cache_key, 'ai4seo_content_type_lists' );

		if ( is_array( $cached_result ) ) {
			$request_cache[ $cache_key ] = $cached_result;
			return $cached_result;
		}

		// Hidden entries are excluded from normal views and validated against the same SQL filters as visible rows.
		$status_option_names                   = ai4seo_get_content_type_status_option_names( $content_context );
		$hidden_ids                            = ai4seo_get_content_type_status_ids( $content_context, 'hidden' );
		$validated_hidden_ids                  = ai4seo_validate_content_type_post_ids_for_list( $hidden_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
		$hidden_id_lookup                      = array_flip( $validated_hidden_ids );
		$base_all_count                        = ai4seo_count_content_type_post_list_entries( $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
		$visible_all_count                     = max( 0, $base_all_count - count( $validated_hidden_ids ) );
		$total_items                           = 0;
		$current_page_post_ids                 = array();
		$should_derive_current_page_status_ids = false;

		// The default unfiltered list is the mandatory fast path: bounded page IDs plus a separate count.
		if ( 'all' === $filter_status ) {
			$total_items           = $visible_all_count;
			$total_pages           = $total_items ? (int) ceil( $total_items / $per_page ) : 1;
			$current_page          = min( max( 1, $current_page ), max( 1, $total_pages ) );
			$offset                = ( $current_page - 1 ) * $per_page;
			$current_page_post_ids = ai4seo_read_content_type_post_list_page_ids(
				$post_types,
				$post_statuses,
				$author_not_in,
				$filter_text,
				$orderby,
				$order,
				$offset,
				$per_page,
				$validated_hidden_ids,
				$post_mime_types
			);

			$should_derive_current_page_status_ids = $is_large_site;
		} else {
			// Status filters start from their option arrays, then validate and page the smaller candidate set.
			if ( 'waiting_to_queue' === $filter_status ) {
				$candidate_post_ids = ai4seo_get_content_type_status_ids( $content_context, 'missing' );
				$candidate_post_ids = ai4seo_validate_content_type_post_ids_for_list( $candidate_post_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types );
				$candidate_post_ids = ai4seo_get_content_type_waiting_to_get_queued_post_ids(
					$candidate_post_ids,
					$candidate_post_ids,
					ai4seo_get_content_type_status_ids( $content_context, 'queued' ),
					ai4seo_get_content_type_status_ids( $content_context, 'processing' ),
					ai4seo_get_content_type_status_ids( $content_context, 'failed' ),
					array(
						'is_bulk_generation_activated' => $is_bulk_generation_activated,
						'should_auto_queue_bulk_generation_entries' => $should_auto_queue_bulk_generation_entries,
						'has_enough_credits'           => $has_enough_credits,
						'new_or_existing_filter'       => $new_or_existing_filter,
						'new_or_existing_filter_reference_timestamp' => $new_or_existing_filter_reference_timestamp,
						'hidden_ids'                   => $validated_hidden_ids,
						'auto_queue_disallowed_ids'    => ai4seo_get_content_type_status_ids( $content_context, 'auto_queue_disallowed' ),
					)
				);
			} else {
				$candidate_post_ids = ai4seo_get_content_type_status_ids( $content_context, $filter_status );
				$candidate_post_ids = ai4seo_validate_content_type_post_ids_for_list( $candidate_post_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types );

				if ( 'hidden' !== $filter_status && $hidden_id_lookup ) {
					$candidate_post_ids = array_values(
						array_filter(
							$candidate_post_ids,
							function ( $post_id ) use ( $hidden_id_lookup ) {
								return ! isset( $hidden_id_lookup[ (int) $post_id ] );
							}
						)
					);
				}
			}

			$candidate_post_ids    = ai4seo_sort_content_type_post_ids_for_list( $candidate_post_ids, $orderby, $order );
			$total_items           = count( $candidate_post_ids );
			$total_pages           = $total_items ? (int) ceil( $total_items / $per_page ) : 1;
			$current_page          = min( max( 1, $current_page ), max( 1, $total_pages ) );
			$offset                = ( $current_page - 1 ) * $per_page;
			$current_page_post_ids = array_slice( $candidate_post_ids, $offset, $per_page );
		}

		// Large fast paths render rows first; exact status counts are hydrated separately via AJAX.
		if ( $should_defer_status_counts ) {
			$status_counts = array(
				'all' => $total_items,
			);
		} else {
			$status_counts = ai4seo_get_content_type_exact_status_counts_from_options(
				array(
					'content_context'              => $content_context,
					'post_types'                   => $post_types,
					'post_status'                  => $post_statuses,
					'post_mime_types'              => $post_mime_types,
					'author_not_in'                => $author_not_in,
					'filter_text'                  => $filter_text,
					'status_options'               => $status_options,
					'all_count'                    => $base_all_count,
					'hidden_ids'                   => $validated_hidden_ids,
					'hidden_ids_are_validated'     => true,
					'is_bulk_generation_activated' => $is_bulk_generation_activated,
					'should_auto_queue_bulk_generation_entries' => $should_auto_queue_bulk_generation_entries,
					'has_enough_credits'           => $has_enough_credits,
					'new_or_existing_filter'       => $new_or_existing_filter,
					'new_or_existing_filter_reference_timestamp' => $new_or_existing_filter_reference_timestamp,
				)
			);
		}

		// Large all-lists derive expensive coverage membership later from rendered-page data.
		if ( $should_derive_current_page_status_ids ) {
			$current_page_status_ids = array(
				'complete'              => array(),
				'missing'               => array(),
				'generated'             => array(),
				'queued'                => ai4seo_intersect_content_type_option_ids_with_page( $status_option_names['queued'] ?? '', $current_page_post_ids ),
				'processing'            => ai4seo_intersect_content_type_option_ids_with_page( $status_option_names['processing'] ?? '', $current_page_post_ids ),
				'failed'                => ai4seo_intersect_content_type_option_ids_with_page( $status_option_names['failed'] ?? '', $current_page_post_ids ),
				'hidden'                => array_values( array_intersect( $current_page_post_ids, $validated_hidden_ids ) ),
				'auto_queue_disallowed' => ai4seo_intersect_content_type_option_ids_with_page( $status_option_names['auto_queue_disallowed'] ?? '', $current_page_post_ids ),
			);
		} else {
			$current_page_status_ids = ai4seo_get_content_type_current_page_status_ids( $current_page_post_ids, $content_context );
		}

		// Cache the complete resolver result so row IDs, totals, and status counts stay coherent.
		$result = array(
			'is_optimized'                          => true,
			'content_context'                       => $content_context,
			'is_large_site'                         => $is_large_site,
			'current_page'                          => $current_page,
			'post_ids'                              => $current_page_post_ids,
			'total_items'                           => $total_items,
			'total_pages'                           => $total_items ? (int) ceil( $total_items / $per_page ) : 1,
			'status_counts'                         => $status_counts,
			'current_page_status_ids'               => $current_page_status_ids,
			'should_defer_status_counts'            => $should_defer_status_counts,
			'should_derive_current_page_status_ids' => $should_derive_current_page_status_ids,
			'should_derive_current_page_metadata_status_ids' => $should_derive_current_page_status_ids,
			'sort_value_map'                        => array(),
		);

		$request_cache[ $cache_key ] = $result;
		wp_cache_set( $cache_key, $result, 'ai4seo_content_type_lists', MINUTE_IN_SECONDS * 5 );

		return $result;
	}
}

if ( ! function_exists( 'ai4seo_resolve_optimized_post_content_type_list' ) ) {
	/**
	 * Backwards-compatible metadata resolver wrapper.
	 *
	 * @param array $args Resolver arguments.
	 * @return array Resolver result.
	 */
	function ai4seo_resolve_optimized_post_content_type_list( array $args ): array {
		$args['content_context'] = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA;
		return ai4seo_resolve_optimized_content_type_list( $args );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_filter_hydration_result' ) ) {
	/**
	 * Builds exact deferred status-filter counts and HTML for content type lists.
	 *
	 * @param array $args Hydration arguments.
	 * @return array AJAX response data.
	 */
	function ai4seo_get_content_type_status_filter_hydration_result( array $args ): array {
		// Normalize the same list scope that the initial page resolver used, then validate it before counting.
		$content_context                            = ai4seo_normalize_content_type_list_context( (string) ( $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) );
		$post_types                                 = ! empty( $args['post_types'] )
			? (array) $args['post_types']
			: array( (string) ( $args['post_type'] ?? '' ) );
		$post_types                                 = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
		$post_statuses                              = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $args['post_status'] ?? array( 'publish', 'future' ) ) ) ) ) );
		$post_mime_types                            = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) ( $args['post_mime_types'] ?? array() ) ) ) ) );
		$author_not_in                              = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $args['author_not_in'] ?? array() ) ) ) ) );
		$disabled_wpml_language_codes               = ai4seo_sanitize_wpml_language_codes( $args['disabled_wpml_language_codes'] ?? array(), true );
		$filter_text                                = sanitize_text_field( (string) ( $args['filter_text'] ?? '' ) );
		$filter_status                              = sanitize_key( (string) ( $args['filter_status'] ?? 'all' ) );
		$sort_args                                  = ai4seo_normalize_content_type_sort_args( $args['orderby'] ?? 'id', $args['order'] ?? 'desc' );
		$orderby                                    = $sort_args['orderby'];
		$order                                      = $sort_args['order'];
		$hidden_fields                              = isset( $args['hidden_fields'] ) && is_array( $args['hidden_fields'] ) ? $args['hidden_fields'] : array();
		$status_options                             = isset( $args['status_options'] ) && is_array( $args['status_options'] )
			? $args['status_options']
			: ai4seo_get_content_type_status_options();
		$is_bulk_generation_activated               = (bool) ( $args['is_bulk_generation_activated'] ?? false );
		$should_auto_queue_bulk_generation_entries  = (bool) ( $args['should_auto_queue_bulk_generation_entries'] ?? false );
		$has_enough_credits                         = (bool) ( $args['has_enough_credits'] ?? false );
		$new_or_existing_filter                     = sanitize_key( (string) ( $args['new_or_existing_filter'] ?? 'both' ) );
		$new_or_existing_filter_reference_timestamp = (int) ( $args['new_or_existing_filter_reference_timestamp'] ?? 0 );

		// Without a valid post scope, return a harmless exact empty filter list instead of broadening the query.
		if ( ! $post_types || ! $post_statuses ) {
			return array(
				'status_filter_html'           => '',
				'status_counts'                => array(),
				'visible_statuses'             => array(),
				'show_retry_all_failed_button' => false,
				'total_items'                  => 0,
				'cache_version'                => function_exists( 'ai4seo_get_content_type_list_cache_version' ) ? ai4seo_get_content_type_list_cache_version() : 1,
				'is_exact'                     => true,
			);
		}

		// Keep invalid request values aligned with normal page-filter fallback behavior.
		if ( ! isset( $status_options[ $filter_status ] ) ) {
			$filter_status = 'all';
		}

		// Cache only within the request because the rendered HTML contains fresh WordPress nonces.
		$cache_version = function_exists( 'ai4seo_get_content_type_list_cache_version' ) ? ai4seo_get_content_type_list_cache_version() : 1;
		$cache_key     = 'content_status_filters_' . md5(
			wp_json_encode(
				array(
					'content_context'              => $content_context,
					'post_types'                   => $post_types,
					'post_statuses'                => $post_statuses,
					'post_mime_types'              => $post_mime_types,
					'author_not_in'                => $author_not_in,
					'disabled_wpml_language_codes' => $disabled_wpml_language_codes,
					'filter_text'                  => $filter_text,
					'filter_status'                => $filter_status,
					'orderby'                      => $orderby,
					'order'                        => $order,
					'status_options'               => array_keys( $status_options ),
					'form_action_url'              => (string) ( $args['form_action_url'] ?? '' ),
					'hidden_fields'                => $hidden_fields,
					'is_bulk_generation_activated' => $is_bulk_generation_activated,
					'should_auto_queue_bulk_generation_entries' => $should_auto_queue_bulk_generation_entries,
					'has_enough_credits'           => $has_enough_credits,
					'new_or_existing_filter'       => $new_or_existing_filter,
					'new_or_existing_filter_reference_timestamp' => $new_or_existing_filter_reference_timestamp,
					'cache_version'                => $cache_version,
				)
			)
		);

		static $request_cache = array();

		// The AJAX endpoint may be retried on the same request; keep exact counts coherent and cheap.
		if ( isset( $request_cache[ $cache_key ] ) ) {
			return $request_cache[ $cache_key ];
		}

		// Exact deferred filters validate option IDs against the active SQL constraints instead of using summary totals.
		$hidden_ids           = ai4seo_get_content_type_status_ids( $content_context, 'hidden' );
		$validated_hidden_ids = ai4seo_validate_content_type_post_ids_for_list( $hidden_ids, $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types, $disabled_wpml_language_codes );
		$base_all_count       = ai4seo_count_content_type_post_list_entries( $post_types, $post_statuses, $author_not_in, $filter_text, $post_mime_types, $disabled_wpml_language_codes );

		$status_counts = ai4seo_get_content_type_exact_status_counts_from_options(
			array(
				'content_context'              => $content_context,
				'post_types'                   => $post_types,
				'post_status'                  => $post_statuses,
				'post_mime_types'              => $post_mime_types,
				'author_not_in'                => $author_not_in,
				'disabled_wpml_language_codes' => $disabled_wpml_language_codes,
				'filter_text'                  => $filter_text,
				'status_options'               => $status_options,
				'all_count'                    => $base_all_count,
				'hidden_ids'                   => $validated_hidden_ids,
				'hidden_ids_are_validated'     => true,
				'is_bulk_generation_activated' => $is_bulk_generation_activated,
				'should_auto_queue_bulk_generation_entries' => $should_auto_queue_bulk_generation_entries,
				'has_enough_credits'           => $has_enough_credits,
				'new_or_existing_filter'       => $new_or_existing_filter,
				'new_or_existing_filter_reference_timestamp' => $new_or_existing_filter_reference_timestamp,
			)
		);

		// Rebuild the same status-link context used by the initial controls, but with exact hydrated counts.
		$filter_context   = array(
			'filter_text'     => $filter_text,
			'filter_status'   => $filter_status,
			'filter_language' => '',
			'status_options'  => $status_options,
			'orderby'         => $orderby,
			'order'           => $order,
			'form_action_url' => esc_url_raw( (string) ( $args['form_action_url'] ?? '' ) ),
			'hidden_fields'   => $hidden_fields,
			'nonce_action'    => sanitize_key( (string) ( $args['nonce_action'] ?? 'ai4seo_content_type_filter_form' ) ),
			'nonce_name'      => sanitize_key( (string) ( $args['nonce_name'] ?? 'ai4seo_content_type_filter_nonce' ) ),
		);
		$total_items      = (int) ( $status_counts[ $filter_status ] ?? ( $status_counts['all'] ?? 0 ) );
		$visible_statuses = ai4seo_get_visible_content_type_status_filter_keys( $status_options, $status_counts );
		$result           = array(
			'status_filter_html'           => ai4seo_get_content_type_status_filters_html( $filter_context, $status_counts ),
			'status_counts'                => $status_counts,
			'visible_statuses'             => $visible_statuses,
			'show_retry_all_failed_button' => ai4seo_should_show_content_type_retry_all_failed_button( $status_options, $status_counts ),
			'total_items'                  => max( 0, $total_items ),
			'cache_version'                => $cache_version,
			'is_exact'                     => true,
		);

		$request_cache[ $cache_key ] = $result;

		return $result;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_sort_url' ) ) {
	/**
	 * Builds a sort URL while preserving active filters and base page arguments.
	 *
	 * @param array  $filter_context Filter context from ai4seo_setup_content_type_filters().
	 * @param string $orderby        Sort key.
	 * @param string $order          Sort direction.
	 *
	 * @return string Sort URL.
	 */
	function ai4seo_get_content_type_sort_url( array $filter_context, string $orderby, string $order ): string {
		$sort_args = ai4seo_normalize_content_type_sort_args( $orderby, $order );
		$orderby   = $sort_args['orderby'];
		$order     = $sort_args['order'];

		$form_action_url = esc_url_raw( (string) ( $filter_context['form_action_url'] ?? '' ) );
		if ( '' === $form_action_url ) {
			$form_action_url = function_exists( 'admin_url' ) ? admin_url( 'admin.php' ) : 'admin.php';
		}

		$nonce_action = sanitize_key( (string) ( $filter_context['nonce_action'] ?? 'ai4seo_content_type_filter_form' ) );
		if ( '' === $nonce_action ) {
			$nonce_action = 'ai4seo_content_type_filter_form';
		}

		$nonce_name = sanitize_key( (string) ( $filter_context['nonce_name'] ?? 'ai4seo_content_type_filter_nonce' ) );
		if ( '' === $nonce_name ) {
			$nonce_name = 'ai4seo_content_type_filter_nonce';
		}

		$hidden_fields   = isset( $filter_context['hidden_fields'] ) && is_array( $filter_context['hidden_fields'] )
			? $filter_context['hidden_fields']
			: array();
		$reserved_fields = array(
			'ai4seo_filter_text',
			'ai4seo_filter_status',
			'ai4seo_filter_language',
			'ai4seo_page',
			'orderby',
			'order',
			$nonce_name,
		);
		$query_args      = array();

		foreach ( $hidden_fields as $this_hidden_field_name => $this_hidden_field_value ) {
			if ( ! is_scalar( $this_hidden_field_value ) ) {
				continue;
			}

			$this_hidden_field_name = sanitize_key( $this_hidden_field_name );

			if ( '' === $this_hidden_field_name || in_array( $this_hidden_field_name, $reserved_fields, true ) ) {
				continue;
			}

			$this_hidden_field_value = sanitize_text_field( (string) $this_hidden_field_value );

			if ( '' === $this_hidden_field_value ) {
				continue;
			}

			$query_args[ $this_hidden_field_name ] = $this_hidden_field_value;
		}

		$filter_text     = sanitize_text_field( (string) ( $filter_context['filter_text'] ?? '' ) );
		$filter_status   = sanitize_key( (string) ( $filter_context['filter_status'] ?? 'all' ) );
		$filter_language = sanitize_text_field( (string) ( $filter_context['filter_language'] ?? '' ) );

		if ( '' !== $filter_text ) {
			$query_args['ai4seo_filter_text'] = $filter_text;
		}

		if ( '' !== $filter_status && 'all' !== $filter_status ) {
			$query_args['ai4seo_filter_status'] = $filter_status;
		}

		if ( '' !== $filter_language ) {
			$query_args['ai4seo_filter_language'] = $filter_language;
		}

		if ( '' !== $filter_text || ( '' !== $filter_status && 'all' !== $filter_status ) || '' !== $filter_language ) {
			$query_args[ $nonce_name ] = wp_create_nonce( $nonce_action );
		}

		$query_args['ai4seo_page'] = 1;
		$query_args['orderby']     = $orderby;
		$query_args['order']       = $order;

		return add_query_arg( $query_args, $form_action_url );
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_sortable_column_aria_sort_value' ) ) {
	/**
	 * Returns the ARIA sort state when a sortable column is active.
	 *
	 * @param string $orderby        Sort key.
	 * @param array  $filter_context Filter context from ai4seo_setup_content_type_filters().
	 *
	 * @return string ARIA sort value, or an empty string for inactive columns.
	 */
	function ai4seo_get_content_type_sortable_column_aria_sort_value( string $orderby, array $filter_context ): string {
		// Normalize both sides through the existing sort helper so aliases and invalid directions resolve identically.
		$sort_args         = ai4seo_normalize_content_type_sort_args( $orderby, 'desc' );
		$current_sort_args = ai4seo_normalize_content_type_sort_args( $filter_context['orderby'] ?? 'id', $filter_context['order'] ?? 'desc' );

		// Inactive headers omit aria-sort because the attribute describes only the table's active ordering.
		if ( $current_sort_args['orderby'] !== $sort_args['orderby'] ) {
			return '';
		}

		return 'asc' === $current_sort_args['order'] ? 'ascending' : 'descending';
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_sortable_column_label_html' ) ) {
	/**
	 * Renders a sortable table column title with one direction-toggle link.
	 *
	 * @param string $label          Column label.
	 * @param string $orderby        Sort key.
	 * @param array  $filter_context Filter context from ai4seo_setup_content_type_filters().
	 *
	 * @return string Sortable label HTML.
	 */
	function ai4seo_get_content_type_sortable_column_label_html( string $label, string $orderby, array $filter_context ): string {
		// Normalize the requested and active sort state through the same helper used to build filter URLs.
		$label             = sanitize_text_field( $label );
		$sort_args         = ai4seo_normalize_content_type_sort_args( $orderby, 'desc' );
		$orderby           = $sort_args['orderby'];
		$current_sort_args = ai4seo_normalize_content_type_sort_args( $filter_context['orderby'] ?? 'id', $filter_context['order'] ?? 'desc' );
		$current_orderby   = $current_sort_args['orderby'];
		$current_order     = $current_sort_args['order'];

		// A click reverses the active column but starts every inactive column in ascending order.
		$is_current_column = ( $current_orderby === $orderby );
		$next_order        = $is_current_column && 'asc' === $current_order ? 'desc' : 'asc';
		$sort_url          = ai4seo_get_content_type_sort_url( $filter_context, $orderby, $next_order );

		// Both indicators remain visible while only the current direction receives the active modifier.
		$ascending_classes  = 'sorting-indicator ai4seo-content-list-sort-indicator ai4seo-content-list-sort-indicator--asc';
		$descending_classes = 'sorting-indicator ai4seo-content-list-sort-indicator ai4seo-content-list-sort-indicator--desc';

		if ( $is_current_column && 'asc' === $current_order ) {
			$ascending_classes .= ' ai4seo-content-list-sort-indicator--active';
		}

		if ( $is_current_column && 'desc' === $current_order ) {
			$descending_classes .= ' ai4seo-content-list-sort-indicator--active';
		}

		// Describe the action the single link will perform, rather than repeating the current sort state.
		if ( 'asc' === $next_order ) {
			/* translators: %s: column label */
			$sort_link_aria_label = sprintf( __( 'Sort %s ascending', 'ai-for-seo' ), $label );
			$sort_link_title      = __( 'Sort ascending', 'ai-for-seo' );
		} else {
			/* translators: %s: column label */
			$sort_link_aria_label = sprintf( __( 'Sort %s descending', 'ai-for-seo' ), $label );
			$sort_link_title      = __( 'Sort descending', 'ai-for-seo' );
		}

		// Keep label and decorative carets inside one focus target to mirror native WordPress table headers.
		$output  = '<span class="ai4seo-content-list-sortable-column-title">';
		$output .= '<a href="' . esc_url( $sort_url ) . '" class="ai4seo-content-list-sort-link" aria-label="' . esc_attr( $sort_link_aria_label ) . '" title="' . esc_attr( $sort_link_title ) . '">';
		$output .= '<span class="ai4seo-content-list-sortable-column-label">' . esc_html( $label ) . '</span>';
		$output .= '<span class="sorting-indicators ai4seo-content-list-sort-indicators" aria-hidden="true">';
		$output .= '<span class="' . esc_attr( $ascending_classes ) . '"></span>';
		$output .= '<span class="' . esc_attr( $descending_classes ) . '"></span>';
		$output .= '</span>';
		$output .= '</a>';
		$output .= '</span>';

		return $output;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_post_title_map' ) ) {
	/**
	 * Reads post titles for sorting a filtered ID list.
	 *
	 * @param array $post_ids Post IDs.
	 *
	 * @return array Map of post ID => title.
	 */
	function ai4seo_get_content_type_post_title_map( array $post_ids ): array {
		global $wpdb;

		$post_ids    = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );
		$post_titles = array();

		foreach ( $post_ids as $post_id ) {
			$post_titles[ $post_id ] = '';
		}

		if ( ! $post_ids ) {
			return $post_titles;
		}

		$database_chunk_size = ai4seo_get_database_chunk_size();
		$post_id_chunks      = array_chunk( $post_ids, $database_chunk_size );

		foreach ( $post_id_chunks as $this_post_id_chunk ) {
			$this_post_id_chunk = array_values( array_filter( array_map( 'intval', $this_post_id_chunk ) ) );

			if ( ! $this_post_id_chunk ) {
				continue;
			}

			$post_id_placeholders = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );

			// Dynamic query with placeholders is prepared immediately below.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$prepared_sql = $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($post_id_placeholders)", ...$this_post_id_chunk );

			// Prepared above.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_title_rows = $wpdb->get_results( $prepared_sql, ARRAY_A );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321703, 'Database error: ' . $wpdb->last_error );
				continue;
			}

			foreach ( (array) $post_title_rows as $this_post_title_row ) {
				$this_post_id = (int) ( $this_post_title_row['ID'] ?? 0 );

				if ( $this_post_id <= 0 ) {
					continue;
				}

				$post_titles[ $this_post_id ] = (string) ( $this_post_title_row['post_title'] ?? '' );
			}
		}

		return $post_titles;
	}
}

if ( ! function_exists( 'ai4seo_sort_content_type_ids' ) ) {
	/**
	 * Sorts content type IDs by ID, title, or SEO progress.
	 *
	 * @param array  $post_ids        Post IDs.
	 * @param string $orderby         Sort key.
	 * @param string $order           Sort direction.
	 * @param array  $sort_value_map  Optional map used for title/progress sorting.
	 *
	 * @return array Sorted post IDs.
	 */
	function ai4seo_sort_content_type_ids( array $post_ids, string $orderby, string $order, array $sort_value_map = array() ): array {
		$post_ids  = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );
		$sort_args = ai4seo_normalize_content_type_sort_args( $orderby, $order );
		$orderby   = $sort_args['orderby'];
		$order     = $sort_args['order'];

		$sort_direction = ( 'asc' === $order ) ? 1 : -1;

		usort(
			$post_ids,
			function ( int $first_post_id, int $second_post_id ) use ( $orderby, $sort_direction, $sort_value_map ): int {
				if ( 'id' === $orderby ) {
					return $sort_direction * ( $first_post_id <=> $second_post_id );
				}

				if ( 'seo_progress' === $orderby ) {
					$first_sort_value  = (float) ( $sort_value_map[ $first_post_id ] ?? 0 );
					$second_sort_value = (float) ( $sort_value_map[ $second_post_id ] ?? 0 );
					$sort_result       = $first_sort_value <=> $second_sort_value;
				} else {
					$first_sort_value  = (string) ( $sort_value_map[ $first_post_id ] ?? '' );
					$second_sort_value = (string) ( $sort_value_map[ $second_post_id ] ?? '' );
					$sort_result       = strnatcasecmp( $first_sort_value, $second_sort_value );
				}

				if ( 0 === $sort_result ) {
					return $second_post_id <=> $first_post_id;
				}

				return $sort_direction * $sort_result;
			}
		);

		return $post_ids;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_status_filters_html' ) ) {
	/**
	 * Renders the status filter links for SOOZ content type lists.
	 *
	 * @param array $filter_context Filter context from ai4seo_setup_content_type_filters().
	 * @param array $status_counts  Status counts keyed by status.
	 * @return string Status filter item HTML.
	 */
	function ai4seo_get_content_type_status_filters_html( array $filter_context, array $status_counts ): string {
		$filter_text     = sanitize_text_field( (string) ( $filter_context['filter_text'] ?? '' ) );
		$filter_status   = sanitize_key( (string) ( $filter_context['filter_status'] ?? 'all' ) );
		$filter_language = sanitize_text_field( (string) ( $filter_context['filter_language'] ?? '' ) );
		$status_options  = isset( $filter_context['status_options'] ) && is_array( $filter_context['status_options'] )
			? $filter_context['status_options']
			: array();
		$hidden_fields   = isset( $filter_context['hidden_fields'] ) && is_array( $filter_context['hidden_fields'] )
			? $filter_context['hidden_fields']
			: array();
		$sort_args       = ai4seo_normalize_content_type_sort_args( $filter_context['orderby'] ?? 'id', $filter_context['order'] ?? 'desc' );
		$orderby         = $sort_args['orderby'];
		$order           = $sort_args['order'];

		if ( ! isset( $status_options[ $filter_status ] ) ) {
			$filter_status = 'all';
		}

		$nonce_action = sanitize_key( (string) ( $filter_context['nonce_action'] ?? 'ai4seo_content_type_filter_form' ) );
		if ( '' === $nonce_action ) {
			$nonce_action = 'ai4seo_content_type_filter_form';
		}

		$nonce_name = sanitize_key( (string) ( $filter_context['nonce_name'] ?? 'ai4seo_content_type_filter_nonce' ) );
		if ( '' === $nonce_name ) {
			$nonce_name = 'ai4seo_content_type_filter_nonce';
		}

		$form_action_url = esc_url_raw( (string) ( $filter_context['form_action_url'] ?? '' ) );
		if ( '' === $form_action_url ) {
			$form_action_url = function_exists( 'admin_url' ) ? admin_url( 'admin.php' ) : 'admin.php';
		}

		$reserved_filter_fields = array(
			'ai4seo_filter_text',
			'ai4seo_filter_status',
			'ai4seo_filter_language',
			'orderby',
			'order',
			$nonce_name,
		);
		$base_query_args        = array(
			'ai4seo_page' => 1,
		);

		// Preserve non-filter routing fields such as post type or modal source IDs.
		foreach ( $hidden_fields as $this_hidden_field_name => $this_hidden_field_value ) {
			if ( ! is_scalar( $this_hidden_field_value ) ) {
				continue;
			}

			$this_hidden_field_name = sanitize_key( $this_hidden_field_name );

			if ( '' === $this_hidden_field_name || in_array( $this_hidden_field_name, $reserved_filter_fields, true ) ) {
				continue;
			}

			$this_hidden_field_value = sanitize_text_field( (string) $this_hidden_field_value );

			if ( '' === $this_hidden_field_value ) {
				continue;
			}

			$base_query_args[ $this_hidden_field_name ] = $this_hidden_field_value;
		}

		$visible_status_filter_keys = ai4seo_get_visible_content_type_status_filter_keys( $status_options, $status_counts );
		$visible_status_options     = array();

		// Convert visible keys back to labels so status-link rendering and external visibility checks cannot drift.
		foreach ( $visible_status_filter_keys as $this_status_key ) {
			if ( ! isset( $status_options[ $this_status_key ] ) ) {
				continue;
			}

			$visible_status_options[ $this_status_key ] = $status_options[ $this_status_key ];
		}

		$status_filter_items_html = '';
		$status_filter_index      = 0;
		$num_status_options       = count( $visible_status_options );

		// Build links from counts only after hidden/disabled entries have been accounted for by the caller.
		foreach ( $visible_status_options as $this_status_key => $this_status_label ) {
			$this_status_key = sanitize_key( $this_status_key );

			if ( '' === $this_status_key ) {
				continue;
			}

			$this_status_count                     = absint( $status_counts[ $this_status_key ] ?? 0 );
			$this_status_query_args                = $base_query_args;
			$this_status_query_args[ $nonce_name ] = wp_create_nonce( $nonce_action );

			if ( '' !== $filter_text ) {
				$this_status_query_args['ai4seo_filter_text'] = $filter_text;
			}

			if ( '' !== $filter_language ) {
				$this_status_query_args['ai4seo_filter_language'] = $filter_language;
			}

			if ( 'id' !== $orderby || 'desc' !== $order ) {
				$this_status_query_args['orderby'] = $orderby;
				$this_status_query_args['order']   = $order;
			}

			if ( 'all' !== $this_status_key ) {
				$this_status_query_args['ai4seo_filter_status'] = $this_status_key;
			}

			$this_status_url               = add_query_arg( $this_status_query_args, $form_action_url );
			$this_status_link_classes      = ( $filter_status === $this_status_key ) ? 'current' : '';
			$this_status_link_aria_current = ( $filter_status === $this_status_key ) ? ' aria-current="page"' : '';

			$status_filter_items_html         .= '<li class="' . esc_attr( $this_status_key ) . '">';
				$status_filter_items_html     .= '<a href="' . esc_url( $this_status_url ) . '" class="' . esc_attr( $this_status_link_classes ) . '"' . $this_status_link_aria_current . '>';
					$status_filter_items_html .= esc_html( $this_status_label );
					$status_filter_items_html .= ' <span class="count">(' . esc_html( ai4seo_format_number_i18n( $this_status_count ) ) . ')</span>';
				$status_filter_items_html     .= '</a>';

			if ( $status_filter_index < ( $num_status_options - 1 ) ) {
				$status_filter_items_html .= ' |';
			}
			$status_filter_items_html .= '</li>';

			++$status_filter_index;
		}

		return $status_filter_items_html;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_filter_controls_html' ) ) {
	/**
	 * Renders native WordPress-style list controls for SOOZ content type lists.
	 *
	 * @param array  $filter_context                               Filter context from ai4seo_setup_content_type_filters().
	 * @param array  $status_counts                                Status counts keyed by status.
	 * @param int    $total_entries                                Total entries after all active filters.
	 * @param string $bulk_generation_queue_action_controls         Optional bulk queue controls HTML.
	 * @param array  $render_args                                  Optional render arguments.
	 *
	 * @return string Rendered controls HTML.
	 */
	function ai4seo_get_content_type_filter_controls_html(
		array $filter_context,
		array $status_counts,
		int $total_entries,
		string $bulk_generation_queue_action_controls = '',
		array $render_args = array()
	): string {
		$filter_text      = sanitize_text_field( (string) ( $filter_context['filter_text'] ?? '' ) );
		$filter_status    = sanitize_key( (string) ( $filter_context['filter_status'] ?? 'all' ) );
		$filter_language  = sanitize_text_field( (string) ( $filter_context['filter_language'] ?? '' ) );
		$status_options   = isset( $filter_context['status_options'] ) && is_array( $filter_context['status_options'] )
			? $filter_context['status_options']
			: array();
		$language_options = isset( $filter_context['language_options'] ) && is_array( $filter_context['language_options'] )
			? $filter_context['language_options']
			: array();
		$hidden_fields    = isset( $filter_context['hidden_fields'] ) && is_array( $filter_context['hidden_fields'] )
			? $filter_context['hidden_fields']
			: array();
		$sort_args        = ai4seo_normalize_content_type_sort_args( $filter_context['orderby'] ?? 'id', $filter_context['order'] ?? 'desc' );
		$orderby          = $sort_args['orderby'];
		$order            = $sort_args['order'];

		if ( ! isset( $status_options[ $filter_status ] ) ) {
			$filter_status = 'all';
		}

		$nonce_action = sanitize_key( (string) ( $filter_context['nonce_action'] ?? 'ai4seo_content_type_filter_form' ) );
		if ( '' === $nonce_action ) {
			$nonce_action = 'ai4seo_content_type_filter_form';
		}

		$nonce_name = sanitize_key( (string) ( $filter_context['nonce_name'] ?? 'ai4seo_content_type_filter_nonce' ) );
		if ( '' === $nonce_name ) {
			$nonce_name = 'ai4seo_content_type_filter_nonce';
		}

		$form_action_url = esc_url_raw( (string) ( $filter_context['form_action_url'] ?? '' ) );
		if ( '' === $form_action_url ) {
			$form_action_url = function_exists( 'admin_url' ) ? admin_url( 'admin.php' ) : 'admin.php';
		}

		$filter_nonce           = wp_create_nonce( $nonce_action );
		$reserved_filter_fields = array(
			'ai4seo_filter_text',
			'ai4seo_filter_status',
			'ai4seo_filter_language',
			'orderby',
			'order',
			$nonce_name,
		);
		$base_query_args        = array();
		$hidden_fields_html     = '';

		foreach ( $hidden_fields as $this_hidden_field_name => $this_hidden_field_value ) {
			if ( ! is_scalar( $this_hidden_field_value ) ) {
				continue;
			}

			$this_hidden_field_name = sanitize_key( $this_hidden_field_name );

			if ( '' === $this_hidden_field_name || in_array( $this_hidden_field_name, $reserved_filter_fields, true ) ) {
				continue;
			}

			$this_hidden_field_value = sanitize_text_field( (string) $this_hidden_field_value );

			if ( '' === $this_hidden_field_value ) {
				continue;
			}

			$base_query_args[ $this_hidden_field_name ] = $this_hidden_field_value;
			$hidden_fields_html                        .= '<input type="hidden" name="' . esc_attr( $this_hidden_field_name ) . '" value="' . esc_attr( $this_hidden_field_value ) . '" />';
		}

		$base_query_args['ai4seo_page'] = 1;

		$text_filter_reset_query_args = $base_query_args;

		// The reset button belongs to the text search only, so keep the other active list controls intact.
		if ( 'all' !== $filter_status ) {
			$text_filter_reset_query_args['ai4seo_filter_status'] = $filter_status;
		}

		if ( '' !== $filter_language ) {
			$text_filter_reset_query_args['ai4seo_filter_language'] = $filter_language;
		}

		if ( 'id' !== $orderby || 'desc' !== $order ) {
			$text_filter_reset_query_args['orderby'] = $orderby;
			$text_filter_reset_query_args['order']   = $order;
		}

		if ( 'all' !== $filter_status || '' !== $filter_language ) {
			$text_filter_reset_query_args[ $nonce_name ] = $filter_nonce;
		}

		$text_filter_reset_url = add_query_arg( $text_filter_reset_query_args, $form_action_url );

		$status_filter_items_html = ai4seo_get_content_type_status_filters_html( $filter_context, $status_counts );

		$search_hidden_fields_html = $hidden_fields_html
			. '<input type="hidden" name="' . esc_attr( $nonce_name ) . '" value="' . esc_attr( $filter_nonce ) . '" />';

		if ( 'all' !== $filter_status ) {
			$search_hidden_fields_html .= '<input type="hidden" name="ai4seo_filter_status" value="' . esc_attr( $filter_status ) . '" />';
		}

		if ( 'id' !== $orderby || 'desc' !== $order ) {
			$search_hidden_fields_html .= '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
			$search_hidden_fields_html .= '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
		}

		$language_select_html = '';

		if ( $language_options ) {
			$language_select_html     .= '<label class="screen-reader-text" for="ai4seo-content-list-language-filter">' . esc_html__( 'Language', 'ai-for-seo' ) . '</label>';
			$language_select_html     .= '<select id="ai4seo-content-list-language-filter" class="ai4seo-textfield" autocomplete="off" name="ai4seo_filter_language">';
				$language_select_html .= '<option value="">' . esc_html__( 'All languages', 'ai-for-seo' ) . '</option>';

			foreach ( $language_options as $this_language_code => $this_language_label ) {
				$this_language_code = sanitize_key( $this_language_code );

				if ( '' === $this_language_code ) {
					continue;
				}

				$language_select_html .= '<option value="' . esc_attr( $this_language_code ) . '"' . selected( $filter_language, $this_language_code, false ) . '>' . esc_html( $this_language_label ) . '</option>';
			}

			$language_select_html .= '</select>';
		} elseif ( '' !== $filter_language ) {
			$search_hidden_fields_html .= '<input type="hidden" name="ai4seo_filter_language" value="' . esc_attr( $filter_language ) . '" />';
		}

		$total_entries       = max( 0, $total_entries );
		$total_entries_label = sprintf(
			/* translators: %s: number of entries */
			_n( '%s entry', '%s entries', $total_entries, 'ai-for-seo' ),
			ai4seo_format_number_i18n( $total_entries )
		);
		$has_active_text_filter         = ( '' !== $filter_text );
		$should_defer_status_filters    = ! empty( $render_args['defer_status_filters'] );
		$content_context                = ai4seo_normalize_content_type_list_context( (string) ( $render_args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) );
		$retry_all_failed_button_target = sanitize_html_class( (string) ( $render_args['retry_all_failed_button_target'] ?? '' ) );
		$status_filter_attributes       = 'class="subsubsub ai4seo-content-list-status-filters"';

		if ( $should_defer_status_filters ) {
			$hydration_hidden_fields         = ai4seo_get_content_type_status_filter_hydration_hidden_fields( $hidden_fields );
			$loading_status_filter_separator = '' !== $status_filter_items_html ? ' | ' : '';

			// Show operators that exact counts are still loading; AJAX replaces the whole list on success.
			$status_filter_items_html .= '<li class="ai4seo-content-list-status-filter-loading" aria-live="polite">'
				. esc_html( $loading_status_filter_separator )
				. '<span>' . esc_html__( 'Loading status filters...', 'ai-for-seo' ) . '</span>'
				. '</li>';

			// The initial page renders only cheap counts; exact status filters are hydrated after first paint.
			$status_filter_attributes = 'class="subsubsub ai4seo-content-list-status-filters"'
				. ' data-ai4seo-defer-status-filters="1"'
				. ' data-ai4seo-content-context="' . esc_attr( $content_context ) . '"'
				. ' data-ai4seo-post-type="' . esc_attr( sanitize_key( (string) ( $render_args['post_type'] ?? '' ) ) ) . '"'
				. ' data-ai4seo-filter-text="' . esc_attr( $filter_text ) . '"'
				. ' data-ai4seo-filter-status="' . esc_attr( $filter_status ) . '"'
				. ' data-ai4seo-orderby="' . esc_attr( $orderby ) . '"'
				. ' data-ai4seo-order="' . esc_attr( $order ) . '"';

			if ( '' !== $retry_all_failed_button_target ) {
				$status_filter_attributes .= ' data-ai4seo-retry-all-failed-button-target="' . esc_attr( $retry_all_failed_button_target ) . '"';
			}

			if ( $hydration_hidden_fields ) {
				$status_filter_attributes .= ' data-ai4seo-hydration-hidden-fields="' . esc_attr( wp_json_encode( $hydration_hidden_fields ) ) . '"';
			}
		}

		$output                  = '<div class="ai4seo-filter-bar ai4seo-content-list-controls">';
			$output             .= '<div class="ai4seo-content-list-controls__top">';
				$output         .= '<div class="ai4seo-content-list-controls__views">';
					$output     .= '<ul ' . $status_filter_attributes . '>' . $status_filter_items_html . '</ul>';
				$output         .= '</div>';
				$output         .= '<div class="ai4seo-content-list-controls__search">';
					$output     .= '<form method="get" action="' . esc_url( $form_action_url ) . '" class="ai4seo-content-list-search-form search-box">';
						$output .= $search_hidden_fields_html;
						$output .= '<label class="screen-reader-text" for="ai4seo-content-list-search-input">' . esc_html__( 'Search', 'ai-for-seo' ) . '</label>';
						$output .= '<input class="ai4seo-textfield" autocomplete="off" id="ai4seo-content-list-search-input" type="search" name="ai4seo_filter_text" value="' . esc_attr( $filter_text ) . '" placeholder="' . esc_attr__( 'Search by title, ID, or URL/filename', 'ai-for-seo' ) . '" />';
						$output .= $language_select_html;

						// Add stable classes so shared JS can submit on Enter and reset on Escape across media/post lists and modals.
						$output .= ai4seo_get_button_tag( esc_html__( 'Search', 'ai-for-seo' ), 'ai4seo-content-list-search-submit', 'ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen(); jQuery(this).closest("form").submit();' );

		if ( $has_active_text_filter ) {
			$output .= ai4seo_get_a_tag_icon_button_tag( $text_filter_reset_url, '', '', '', esc_html__( 'Reset', 'ai-for-seo' ), 'ai4seo-abort-button ai4seo-content-list-search-reset-button', 'ai4seo_add_loading_html_to_element(this); ai4seo_show_full_page_loading_screen();' );
		}

					$output .= '</form>';
				$output     .= '</div>';
			$output         .= '</div>';

			$output .= '<div class="tablenav top ai4seo-content-list-controls__bulk">';
		if ( '' !== $bulk_generation_queue_action_controls ) {
			$output     .= '<div class="alignleft actions bulkactions">';
				$output .= $bulk_generation_queue_action_controls;
			$output     .= '</div>';
		}

				$output     .= '<div class="tablenav-pages one-page ai4seo-content-list-controls__count">';
					$output .= '<span class="displaying-num ai4seo-content-list-total-count">' . esc_html( $total_entries_label ) . '</span>';
				$output     .= '</div>';
			$output         .= '</div>';

		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'ai4seo_get_content_type_remove_filters_button_html' ) ) {
	/**
	 * Returns a remove-filters button for empty SOOZ content type list states.
	 *
	 * @param array $filter_context Filter context from ai4seo_setup_content_type_filters().
	 * @return string Button HTML or empty string.
	 */
	function ai4seo_get_content_type_remove_filters_button_html( array $filter_context ): string {
		$filter_text     = sanitize_text_field( (string) ( $filter_context['filter_text'] ?? '' ) );
		$filter_status   = sanitize_key( (string) ( $filter_context['filter_status'] ?? 'all' ) );
		$filter_language = sanitize_text_field( (string) ( $filter_context['filter_language'] ?? '' ) );

		if ( '' === $filter_text && 'all' === $filter_status && '' === $filter_language ) {
			return '';
		}

		$reset_url = esc_url_raw( (string) ( $filter_context['reset_url'] ?? '' ) );

		if ( '' === $reset_url ) {
			return '';
		}

		return ai4seo_get_a_tag_icon_button_tag(
			$reset_url,
			'',
			'_self',
			'xmark',
			esc_html__( 'Remove filters', 'ai-for-seo' ),
			'ai4seo-abort-button',
			'ai4seo_show_full_page_loading_screen();'
		);
	}
}

// =========================================================================================== \\

if ( ! function_exists( 'ai4seo_filter_post_ids_by_status' ) ) {
	/**
	 * Filters a list of candidate IDs by the selected status.
	 *
	 * @param array  $candidate_ids Candidate IDs respecting the current query.
	 * @param string $status        Selected status.
	 * @param array  $status_map    Map of status => array of IDs.
	 * @param array  $hidden_ids    Hidden IDs to exclude from normal statuses.
	 *
	 * @return array Filtered IDs preserving the original order.
	 */
	function ai4seo_filter_post_ids_by_status( array $candidate_ids, string $status, array $status_map, array $hidden_ids = array() ): array {
		$hidden_ids = array_values( array_unique( array_filter( array_map( 'intval', $hidden_ids ) ) ) );

		if ( 'all' === $status ) {
			if ( $hidden_ids ) {
				return array_values( array_diff( array_map( 'intval', $candidate_ids ), $hidden_ids ) );
			}

			return array_values( $candidate_ids );
		}

		if ( ! isset( $status_map[ $status ] ) || ! is_array( $status_map[ $status ] ) ) {
			return array();
		}

		$status_ids    = array_map( 'intval', $status_map[ $status ] );
		$candidate_ids = array_map( 'intval', $candidate_ids );

		if ( 'hidden' !== $status && $hidden_ids ) {
			$candidate_ids = array_values( array_diff( $candidate_ids, $hidden_ids ) );
		}

		$filtered_ids = array();

		foreach ( $candidate_ids as $candidate_id ) {
			if ( in_array( $candidate_id, $status_ids, true ) ) {
				$filtered_ids[] = $candidate_id;
			}
		}

		return $filtered_ids;
	}
}

if ( ! function_exists( 'ai4seo_filter_post_ids_by_language' ) ) {
	/**
	 * Filters a list of candidate IDs by the selected language (currently WPML-aware).
	 *
	 * @param array  $candidate_ids Candidate IDs respecting the current query.
	 * @param string $language_code Language code to keep. Empty string keeps all IDs.
	 * @return array Filtered IDs preserving the original order.
	 */
	function ai4seo_filter_post_ids_by_language( array $candidate_ids, string $language_code ): array {
		if ( '' === $language_code ) {
			return $candidate_ids;
		}

		$filtered_ids = array();

		foreach ( $candidate_ids as $this_candidate_id ) {
			$this_candidate_id = (int) $this_candidate_id;

			if ( $this_candidate_id <= 0 ) {
				continue;
			}

			// Reuse the shared request cache because selected-language and disabled-language filters inspect the same IDs.
			$this_candidate_language_code = ai4seo_get_cached_post_language_code_by_multilanguage_plugins( $this_candidate_id );

			if ( $this_candidate_language_code === $language_code ) {
				$filtered_ids[] = $this_candidate_id;
			}
		}

		return $filtered_ids;
	}
}
