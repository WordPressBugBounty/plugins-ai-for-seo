<?php
/**
 * Renders the content of the submenu media for the "AI for SEO" page.
 *
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_manage_this_plugin() ) {
	return;
}

require_once __DIR__ . '/list-filters.php';


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

global $wpdb;
$ai4seo_allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();

// Treat an explicitly provided ID list as an additional media-table filter for modal reuse.
if ( isset( $ai4seo_attachment_post_ids_filter ) ) {
	$ai4seo_attachment_post_ids_filter_is_active = true;
	$ai4seo_attachment_post_ids_filter           = array_values( array_filter( ai4seo_normalize_related_attachment_post_ids( (array) $ai4seo_attachment_post_ids_filter ) ) );
} else {
	$ai4seo_attachment_post_ids_filter_is_active = false;
	$ai4seo_attachment_post_ids_filter           = array();
}

$ai4seo_is_related_attachments_modal = ( ! empty( $ai4seo_is_related_attachments_modal ) && $ai4seo_attachment_post_ids_filter_is_active );

$ai4seo_main_attachment_post_type = 'attachment';
$ai4seo_nice_post_type            = 'media';
$ai4seo_post_types                = ai4seo_get_supported_attachment_post_types( ! $ai4seo_attachment_post_ids_filter_is_active );
$ai4seo_additional_post_types     = array();
$ai4seo_current_credits_balance   = ai4seo_robhub_api()->get_credits_balance();

$ai4seo_media_label_singular = _n( 'media', 'media', 1, 'ai-for-seo' );
$ai4seo_media_label_plural   = _n( 'media', 'media', 2, 'ai-for-seo' );
$ai4seo_total_pages          = 1;

// Keep modal pagination isolated from the full media page by reading the shared filter page parameter.
$ai4seo_current_page = absint( wp_unslash( $_REQUEST['ai4seo_page'] ?? 1 ) );

if ( $ai4seo_current_page < 1 ) {
	$ai4seo_current_page = 1;
}

// check if the cron job should be executed sooner.
if ( isset( $_GET['ai4seo-execute-cron-job-sooner'] ) && sanitize_text_field( wp_unslash( $_GET['ai4seo-execute-cron-job-sooner'] ) ) ) {
	ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
}

// Start with empty row state so optimized media lists do not parse global status options before page IDs are known.
$ai4seo_pending_attributes_attachment_post_ids               = array();
$ai4seo_processing_attributes_attachment_post_ids            = array();
$ai4seo_missing_attachment_attributes_post_ids               = array();
$ai4seo_failed_attributes_attachment_post_ids                = array();
$ai4seo_hidden_attachment_attributes_post_ids                = array();
$ai4seo_auto_queue_disallowed_attachment_attributes_post_ids = array();
$ai4seo_fully_covered_attachment_post_ids                    = array();
$ai4seo_generated_attachment_post_ids                        = array();
$ai4seo_waiting_to_get_queued_attachment_post_ids            = array();
$ai4seo_status_filter_counts                                 = array();
$ai4seo_total_items                                        = 0;
$ai4seo_current_page_attachment_post_ids                   = array();
$ai4seo_sort_value_map                                     = array();
$ai4seo_attachment_attributes_coverage_for_sorting         = array();
$ai4seo_attachment_attributes_coverage_summary_for_sorting = array();
$ai4seo_num_total_attachment_attributes_for_sorting        = 0;
$ai4seo_should_derive_current_page_attachment_status_ids   = false;
$ai4seo_should_defer_status_filters                        = false;
$ai4seo_disabled_attachment_post_author_ids                = ai4seo_get_disabled_attachment_post_author_ids();

// Read media WPML exclusions once so filters, status counts, and queue previews use the same scope.
$ai4seo_disabled_attachment_attributes_wpml_language_codes          = ai4seo_get_disabled_attachment_attributes_wpml_language_codes();
$ai4seo_do_generate_attachment_attributes_for_fully_covered_entries = ai4seo_do_generate_attachment_attributes_for_fully_covered_entries();


// ___________________________________________________________________________________________ \\
// === READ ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// handle the import of NextGen Gallery images.
if ( ! $ai4seo_is_related_attachments_modal && ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY ) ) {
	$ai4seo_num_not_imported_nextgen_gallery_images = 0;

	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE ) ) {
		$ai4seo_nextgen_gallery_image_pids = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE );

		if ( ! is_array( $ai4seo_nextgen_gallery_image_pids ) ) {
			$ai4seo_nextgen_gallery_image_pids = array();
		}
	} else {
		$ai4seo_nextgen_gallery_pictures_table_name = esc_sql( $wpdb->prefix . 'ngg_pictures' );
		$ai4seo_nextgen_gallery_image_pids_sql      = $wpdb->prepare(
			"SELECT `pid` FROM {$ai4seo_nextgen_gallery_pictures_table_name} WHERE `pid` > %d",
			0
		);

		// Prepared above; table name is built from WordPress' trusted table prefix.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ai4seo_nextgen_gallery_image_pids = $wpdb->get_results( $ai4seo_nextgen_gallery_image_pids_sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321699, 'Database error: ' . $wpdb->last_error );
			$ai4seo_nextgen_gallery_image_pids = array();
		}

		if ( $ai4seo_nextgen_gallery_image_pids ) {
			$ai4seo_nextgen_gallery_image_pids = array_map(
				function ( $ai4seo_this_nextgen_gallery_image ) {
					return (int) $ai4seo_this_nextgen_gallery_image['pid'];
				},
				$ai4seo_nextgen_gallery_image_pids
			);
		}

		ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE,
			$ai4seo_nextgen_gallery_image_pids,
			true,
			MINUTE_IN_SECONDS * 5
		);
	}

	if ( $ai4seo_nextgen_gallery_image_pids ) {
		if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE ) ) {
			$ai4seo_num_imported_nextgen_gallery_images = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE );
		} else {
			// get the number of entries from wp_posts where type is AI4SEO_NEXTGEN_GALLERY_POST_TYPE and pid is not in wp_posts.post_parent.
			$ai4seo_num_imported_nextgen_gallery_images = 0;
			$ai4seo_database_chunk_size                 = ai4seo_get_database_chunk_size();
			$ai4seo_nextgen_gallery_image_pid_chunks    = array_chunk( $ai4seo_nextgen_gallery_image_pids, $ai4seo_database_chunk_size );

			foreach ( $ai4seo_nextgen_gallery_image_pid_chunks as $this_nextgen_gallery_image_pid_chunk ) {
				$this_nextgen_gallery_image_pid_chunk = array_values( array_filter( array_map( 'absint', $this_nextgen_gallery_image_pid_chunk ) ) );

				if ( ! $this_nextgen_gallery_image_pid_chunk ) {
					continue;
				}

				$this_nextgen_gallery_image_pid_placeholders = implode( ', ', array_fill( 0, count( $this_nextgen_gallery_image_pid_chunk ), '%d' ) );
				$this_imported_nextgen_gallery_images_sql    = $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_type` = %s AND `post_parent` IN ($this_nextgen_gallery_image_pid_placeholders)",
					AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
					...$this_nextgen_gallery_image_pid_chunk
				);

				// Prepared above with dynamic placeholders for the current NextGEN PID chunk.
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$this_imported_nextgen_gallery_images = (int) $wpdb->get_var( $this_imported_nextgen_gallery_images_sql );

				if ( $wpdb->last_error ) {
					ai4seo_debug_message( 984321700, 'Database error: ' . $wpdb->last_error );
					$this_imported_nextgen_gallery_images = 0;
					break;
				}

				$ai4seo_num_imported_nextgen_gallery_images += $this_imported_nextgen_gallery_images;
			}

			ai4seo_update_environmental_variable(
				AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE,
				$ai4seo_num_imported_nextgen_gallery_images,
				true,
				MINUTE_IN_SECONDS * 5
			);
		}
		$ai4seo_num_not_imported_nextgen_gallery_images = count( $ai4seo_nextgen_gallery_image_pids ) - $ai4seo_num_imported_nextgen_gallery_images;
	}

	$ai4seo_import_nextgen_gallery_button = ai4seo_get_icon_button_tag(
		'file-export',
		sprintf(
			/* translators: 1: Number of images that can be imported from NextGen Gallery */
			esc_html__( 'Import NextGen Gallery images (%s)', 'ai-for-seo' ),
			$ai4seo_num_not_imported_nextgen_gallery_images
		),
		( $ai4seo_num_not_imported_nextgen_gallery_images ? 'ai4seo-primary-button' : 'ai4seo-inactive-button' ),
		( $ai4seo_num_not_imported_nextgen_gallery_images ? 'ai4seo_import_nextgen_gallery_images(this);' : '' )
	);
}

// Prepare arguments for the wp-query.
$ai4seo_posts_query_arguments = array(
	'post_status'      => array( 'publish', 'future', 'inherit' ),
	'post_type'        => count( $ai4seo_post_types ) > 1 ? $ai4seo_post_types : reset( $ai4seo_post_types ),
	'post_mime_type'   => $ai4seo_allowed_attachment_mime_types,
	'posts_per_page'   => 20,
	'orderby'          => 'ID',
	'order'            => 'DESC',
	'suppress_filters' => true,
	'lang'             => 'all',
);

if ( $ai4seo_disabled_attachment_post_author_ids ) {
	$ai4seo_posts_query_arguments['author__not_in'] = $ai4seo_disabled_attachment_post_author_ids;
}

// Carry the source post ID through filter/search/pagination links when attachment.php is rendered in the modal.
$ai4seo_attachment_filter_hidden_fields   = array();
$ai4seo_related_attachments_modal_post_id = isset( $ai4seo_related_attachments_modal_post_id )
	? absint( $ai4seo_related_attachments_modal_post_id )
	: 0;

if ( $ai4seo_is_related_attachments_modal && $ai4seo_related_attachments_modal_post_id > 0 ) {
	$ai4seo_attachment_filter_hidden_fields['post_id'] = $ai4seo_related_attachments_modal_post_id;
}

// Related-media modal filters must stay constrained to the scanner result before search SQL is built.
$ai4seo_scoped_attachment_post_ids = $ai4seo_attachment_post_ids_filter_is_active
	? $ai4seo_attachment_post_ids_filter
	: null;

// Build shared controls after resolving modal scope so search and exact fallback paths use the same candidate boundary.
$ai4seo_filter_context = ai4seo_setup_content_type_filters(
	array(
		'form_action'                  => ai4seo_get_subpage_url( $ai4seo_nice_post_type, array( 'ai4seo_page' => 1 ) ),
		'nonce_action'                 => 'ai4seo_content_type_filter_form',
		'nonce_name'                   => 'ai4seo_content_type_filter_nonce',
		'post_types'                   => (array) $ai4seo_post_types,
		'post_status'                  => array( 'publish', 'future', 'inherit' ),
		'post_mime_types'              => (array) $ai4seo_allowed_attachment_mime_types,
		'author_not_in'                => $ai4seo_disabled_attachment_post_author_ids,
		'disabled_wpml_language_codes' => $ai4seo_disabled_attachment_attributes_wpml_language_codes,
		'search_file_meta'             => true,
		'scoped_post_ids'              => $ai4seo_scoped_attachment_post_ids,
		'per_page'                     => 20,
		'hidden_fields'                => $ai4seo_attachment_filter_hidden_fields,
	)
);

$ai4seo_filter_status   = $ai4seo_filter_context['filter_status'];
$ai4seo_filter_language = $ai4seo_filter_context['filter_language'];
// Reuse the shared sort normalizer so media rows, links, and AJAX-hydrated filters stay in sync.
$ai4seo_sort_args      = ai4seo_normalize_content_type_sort_args( $ai4seo_filter_context['orderby'] ?? 'id', $ai4seo_filter_context['order'] ?? 'desc' );
$ai4seo_orderby        = $ai4seo_sort_args['orderby'];
$ai4seo_order          = $ai4seo_sort_args['order'];
$ai4seo_search_ids     = $ai4seo_filter_context['search_ids'];
$ai4seo_items_per_page = (int) $ai4seo_filter_context['per_page'];

if ( $ai4seo_items_per_page < 1 ) {
	$ai4seo_items_per_page = 20;
}

// Bulk-generation settings are needed by both the optimized resolver and the exact legacy branch.
$ai4seo_is_bulk_generation_activated                               = ai4seo_is_bulk_generation_enabled( $ai4seo_main_attachment_post_type );
$ai4seo_is_bulk_generation_checked_phrase                          = ( $ai4seo_is_bulk_generation_activated ? 'checked' : '' );
$ai4seo_should_auto_queue_bulk_generation_entries                  = ai4seo_should_auto_queue_bulk_generation_entries();
$ai4seo_bulk_generation_new_or_existing_filter                     = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
$ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );
$ai4seo_approximate_cost_per_attachment_post                       = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

// Pagination reuses the shared filter helper so media and post-type pages sanitize query args consistently.
$ai4seo_filter_query_args = ai4seo_get_content_type_filter_query_args( $ai4seo_filter_context );

// Try the bounded resolver for the full media page; related-media scopes stay exact and self-contained.
$ai4seo_content_type_list_result = array( 'is_optimized' => false );

if ( ! $ai4seo_attachment_post_ids_filter_is_active ) {
	$ai4seo_content_type_list_result = ai4seo_resolve_optimized_content_type_list(
		array(
			'content_context'                            => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
			'post_types'                                 => (array) $ai4seo_post_types,
			'post_status'                                => array( 'publish', 'future', 'inherit' ),
			'post_mime_types'                            => (array) $ai4seo_allowed_attachment_mime_types,
			'author_not_in'                              => $ai4seo_disabled_attachment_post_author_ids,
			'disabled_wpml_language_codes'               => $ai4seo_disabled_attachment_attributes_wpml_language_codes,
			'filter_status'                              => $ai4seo_filter_status,
			'filter_text'                                => (string) ( $ai4seo_filter_context['filter_text'] ?? '' ),
			'filter_language'                            => $ai4seo_filter_language,
			'orderby'                                    => $ai4seo_orderby,
			'order'                                      => $ai4seo_order,
			'current_page'                               => $ai4seo_current_page,
			'per_page'                                   => $ai4seo_items_per_page,
			'status_options'                             => $ai4seo_filter_context['status_options'],
			'is_bulk_generation_activated'               => $ai4seo_is_bulk_generation_activated,
			'should_auto_queue_bulk_generation_entries'  => $ai4seo_should_auto_queue_bulk_generation_entries,
			'has_enough_credits'                         => ( $ai4seo_current_credits_balance >= $ai4seo_approximate_cost_per_attachment_post ),
			'new_or_existing_filter'                     => $ai4seo_bulk_generation_new_or_existing_filter,
			'new_or_existing_filter_reference_timestamp' => $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp,
		)
	);
}

if ( ! empty( $ai4seo_content_type_list_result['is_optimized'] ) ) {
	// The optimized media path returns only current-page row state plus cheap counts for the initial render.
	$ai4seo_current_page                                     = (int) ( $ai4seo_content_type_list_result['current_page'] ?? $ai4seo_current_page );
	$ai4seo_current_page_attachment_post_ids                 = array_values( array_map( 'intval', (array) ( $ai4seo_content_type_list_result['post_ids'] ?? array() ) ) );
	$ai4seo_total_items                                      = (int) ( $ai4seo_content_type_list_result['total_items'] ?? 0 );
	$ai4seo_total_pages                                      = (int) ( $ai4seo_content_type_list_result['total_pages'] ?? 1 );
	$ai4seo_status_filter_counts                             = (array) ( $ai4seo_content_type_list_result['status_counts'] ?? array() );
	$ai4seo_should_derive_current_page_attachment_status_ids = (bool) ( $ai4seo_content_type_list_result['should_derive_current_page_status_ids'] ?? false );
	$ai4seo_should_defer_status_filters                      = (bool) ( $ai4seo_content_type_list_result['should_defer_status_counts'] ?? false );
	$ai4seo_current_page_status_ids                          = (array) ( $ai4seo_content_type_list_result['current_page_status_ids'] ?? array() );
	$ai4seo_pending_attributes_attachment_post_ids           = (array) ( $ai4seo_current_page_status_ids['queued'] ?? array() );
	$ai4seo_processing_attributes_attachment_post_ids        = (array) ( $ai4seo_current_page_status_ids['processing'] ?? array() );
	$ai4seo_failed_attributes_attachment_post_ids            = (array) ( $ai4seo_current_page_status_ids['failed'] ?? array() );
	$ai4seo_hidden_attachment_attributes_post_ids            = (array) ( $ai4seo_current_page_status_ids['hidden'] ?? array() );
	$ai4seo_auto_queue_disallowed_attachment_attributes_post_ids = (array) ( $ai4seo_current_page_status_ids['auto_queue_disallowed'] ?? array() );
	$ai4seo_missing_attachment_attributes_post_ids               = (array) ( $ai4seo_current_page_status_ids['missing'] ?? array() );
	$ai4seo_fully_covered_attachment_post_ids                    = (array) ( $ai4seo_current_page_status_ids['complete'] ?? array() );
	$ai4seo_generated_attachment_post_ids                        = (array) ( $ai4seo_current_page_status_ids['generated'] ?? array() );
} else {
	if ( $ai4seo_attachment_post_ids_filter_is_active && ! $ai4seo_attachment_post_ids_filter ) {
		// Empty related-media scopes must stay empty and avoid global media status option reads.
		$ai4seo_candidate_attachment_post_ids = array();
	} else {
		// The legacy path reads global status options only after scoped modal emptiness is ruled out.
		$ai4seo_pending_attributes_attachment_post_ids               = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_processing_attributes_attachment_post_ids            = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_missing_attachment_attributes_post_ids               = ai4seo_get_post_ids_from_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_failed_attributes_attachment_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_hidden_attachment_attributes_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_auto_queue_disallowed_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_fully_covered_attachment_post_ids                    = ai4seo_get_post_ids_from_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
		$ai4seo_generated_attachment_post_ids                        = ai4seo_get_post_ids_from_option( AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
	}

	// Apply the related-ID filter before the normal status/language/sort flow so modal filters behave like the full page.
	if ( is_array( $ai4seo_search_ids ) ) {
		$ai4seo_candidate_attachment_post_ids = $ai4seo_attachment_post_ids_filter_is_active
			? array_values( array_intersect( $ai4seo_search_ids, $ai4seo_attachment_post_ids_filter ) )
			: $ai4seo_search_ids;
	} elseif ( ! $ai4seo_attachment_post_ids_filter_is_active || $ai4seo_attachment_post_ids_filter ) {
		$ai4seo_candidate_arguments                   = $ai4seo_posts_query_arguments;
		$ai4seo_candidate_arguments['fields']         = 'ids';
		$ai4seo_candidate_arguments['posts_per_page'] = -1;
		$ai4seo_candidate_arguments['no_found_rows']  = true;
		$ai4seo_candidate_arguments['lang']           = 'all';

		if ( $ai4seo_attachment_post_ids_filter_is_active ) {
			$ai4seo_candidate_arguments['post__in'] = $ai4seo_attachment_post_ids_filter;
		}

		$ai4seo_candidate_attachment_post_ids = ai4seo_with_wpml_all_languages(
			function () use ( $ai4seo_candidate_arguments ) {
				return get_posts( $ai4seo_candidate_arguments );
			}
		);
	}

	$ai4seo_candidate_attachment_post_ids = array_values( array_unique( array_map( 'intval', (array) $ai4seo_candidate_attachment_post_ids ) ) );

	// Intersect again after WP_Query/search handling so all candidate paths honor the modal's related-ID scope.
	if ( $ai4seo_attachment_post_ids_filter_is_active ) {
		$ai4seo_candidate_attachment_post_ids = array_values( array_intersect( $ai4seo_candidate_attachment_post_ids, $ai4seo_attachment_post_ids_filter ) );
	}

	rsort( $ai4seo_candidate_attachment_post_ids, SORT_NUMERIC );
	$ai4seo_candidate_attachment_post_ids = ai4seo_filter_post_ids_by_language( $ai4seo_candidate_attachment_post_ids, $ai4seo_filter_language );

	// Apply WPML active-language scope after the selected-language filter because both rely on per-entry WPML details.
	$ai4seo_candidate_attachment_post_ids             = ai4seo_filter_post_ids_by_disabled_wpml_languages( $ai4seo_candidate_attachment_post_ids, $ai4seo_disabled_attachment_attributes_wpml_language_codes );
	$ai4seo_visible_candidate_attachment_post_ids     = array_values( array_diff( $ai4seo_candidate_attachment_post_ids, $ai4seo_hidden_attachment_attributes_post_ids ) );
	$ai4seo_waiting_to_get_queued_attachment_post_ids = ai4seo_get_content_type_waiting_to_get_queued_post_ids(
		$ai4seo_visible_candidate_attachment_post_ids,
		$ai4seo_missing_attachment_attributes_post_ids,
		$ai4seo_pending_attributes_attachment_post_ids,
		$ai4seo_processing_attributes_attachment_post_ids,
		$ai4seo_failed_attributes_attachment_post_ids,
		array(
			'is_bulk_generation_activated'               => $ai4seo_is_bulk_generation_activated,
			'should_auto_queue_bulk_generation_entries'  => $ai4seo_should_auto_queue_bulk_generation_entries,
			'has_enough_credits'                         => ( $ai4seo_current_credits_balance >= $ai4seo_approximate_cost_per_attachment_post ),
			'new_or_existing_filter'                     => $ai4seo_bulk_generation_new_or_existing_filter,
			'new_or_existing_filter_reference_timestamp' => $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp,
			'hidden_ids'                                 => $ai4seo_hidden_attachment_attributes_post_ids,
			'auto_queue_disallowed_ids'                  => $ai4seo_auto_queue_disallowed_attachment_attributes_post_ids,
		)
	);

	$ai4seo_status_map = array(
		'complete'         => $ai4seo_fully_covered_attachment_post_ids,
		'missing'          => $ai4seo_missing_attachment_attributes_post_ids,
		'waiting_to_queue' => $ai4seo_waiting_to_get_queued_attachment_post_ids,
		'queued'           => $ai4seo_pending_attributes_attachment_post_ids,
		'processing'       => $ai4seo_processing_attributes_attachment_post_ids,
		'failed'           => $ai4seo_failed_attributes_attachment_post_ids,
		'hidden'           => $ai4seo_hidden_attachment_attributes_post_ids,
	);

	$ai4seo_status_filter_counts = ai4seo_get_content_type_filter_status_counts(
		$ai4seo_candidate_attachment_post_ids,
		$ai4seo_status_map,
		$ai4seo_filter_context['status_options'],
		$ai4seo_hidden_attachment_attributes_post_ids
	);

	$ai4seo_filtered_candidate_attachment_post_ids = ai4seo_filter_post_ids_by_status(
		$ai4seo_candidate_attachment_post_ids,
		$ai4seo_filter_status,
		$ai4seo_status_map,
		$ai4seo_hidden_attachment_attributes_post_ids
	);
	$ai4seo_filtered_candidate_attachment_post_ids = array_values( array_unique( array_map( 'intval', $ai4seo_filtered_candidate_attachment_post_ids ) ) );

	if ( 'title' === $ai4seo_orderby ) {
		$ai4seo_sort_value_map = ai4seo_get_content_type_post_title_map( $ai4seo_filtered_candidate_attachment_post_ids );
	} elseif ( 'seo_progress' === $ai4seo_orderby ) {
		$ai4seo_attachment_attributes_coverage_for_sorting         = ai4seo_read_and_analyse_attachment_attributes_coverage( $ai4seo_filtered_candidate_attachment_post_ids );
		$ai4seo_attachment_attributes_coverage_summary_for_sorting = ai4seo_get_attachment_attributes_coverage_summary( $ai4seo_attachment_attributes_coverage_for_sorting );
		$ai4seo_num_total_attachment_attributes_for_sorting        = ai4seo_get_active_num_attachment_attributes();

		foreach ( $ai4seo_filtered_candidate_attachment_post_ids as $ai4seo_this_attachment_post_id_for_sorting ) {
			$ai4seo_sort_value_map[ $ai4seo_this_attachment_post_id_for_sorting ] = $ai4seo_num_total_attachment_attributes_for_sorting
				? min( 100, round( ( ( $ai4seo_attachment_attributes_coverage_summary_for_sorting[ $ai4seo_this_attachment_post_id_for_sorting ] ?? 0 ) / $ai4seo_num_total_attachment_attributes_for_sorting ) * 100, 2 ) )
				: 100;
		}
	}

	$ai4seo_filtered_candidate_attachment_post_ids = ai4seo_sort_content_type_ids(
		$ai4seo_filtered_candidate_attachment_post_ids,
		$ai4seo_orderby,
		$ai4seo_order,
		$ai4seo_sort_value_map
	);

	$ai4seo_total_items = count( $ai4seo_filtered_candidate_attachment_post_ids );
	$ai4seo_total_pages = $ai4seo_total_items ? (int) ceil( $ai4seo_total_items / $ai4seo_items_per_page ) : 1;

	if ( $ai4seo_total_pages < 1 ) {
		$ai4seo_total_pages = 1;
	}

	if ( $ai4seo_current_page > $ai4seo_total_pages ) {
		$ai4seo_current_page = $ai4seo_total_pages;
	}

	if ( $ai4seo_current_page < 1 ) {
		$ai4seo_current_page = 1;
	}

	$ai4seo_offset                           = ( $ai4seo_current_page - 1 ) * $ai4seo_items_per_page;
	$ai4seo_current_page_attachment_post_ids = array_slice( $ai4seo_filtered_candidate_attachment_post_ids, $ai4seo_offset, $ai4seo_items_per_page );
}

$ai4seo_all_attachment_posts = array();

if ( $ai4seo_current_page_attachment_post_ids ) {
	$ai4seo_posts_query_arguments['post__in']       = $ai4seo_current_page_attachment_post_ids;
	$ai4seo_posts_query_arguments['orderby']        = 'post__in';
	$ai4seo_posts_query_arguments['order']          = 'DESC';
	$ai4seo_posts_query_arguments['posts_per_page'] = count( $ai4seo_current_page_attachment_post_ids );

	$ai4seo_post_data            = ai4seo_with_wpml_all_languages(
		function () use ( $ai4seo_posts_query_arguments ) {
			return new WP_Query( $ai4seo_posts_query_arguments );
		}
	);
	$ai4seo_all_attachment_posts = ( $ai4seo_post_data instanceof WP_Query ) ? ( $ai4seo_post_data->posts ?? array() ) : array();
	unset( $ai4seo_post_data );
}

// fetch all post ids from this page.
$ai4seo_current_attachment_post_ids = array_map(
	function ( $ai4seo_attachment_post ) {
		return (int) $ai4seo_attachment_post->ID;
	},
	$ai4seo_all_attachment_posts
);

$ai4seo_recent_attachment_activity_entries_by_post_id = ai4seo_get_latest_activity_entries_by_post_id(
	array(
		'attachment-attributes-bulk-generated',
	)
);


// read attributes coverage.
if ( 'seo_progress' === $ai4seo_orderby ) {
	$ai4seo_current_attachment_post_ids_map        = array_flip( $ai4seo_current_attachment_post_ids );
	$ai4seo_attachment_attributes_coverage         = array_intersect_key( $ai4seo_attachment_attributes_coverage_for_sorting, $ai4seo_current_attachment_post_ids_map );
	$ai4seo_attachment_attributes_coverage_summary = array_intersect_key( $ai4seo_attachment_attributes_coverage_summary_for_sorting, $ai4seo_current_attachment_post_ids_map );
	$ai4seo_num_total_attachment_attributes        = $ai4seo_num_total_attachment_attributes_for_sorting;
} else {
	$ai4seo_attachment_attributes_coverage         = ai4seo_read_and_analyse_attachment_attributes_coverage( $ai4seo_current_attachment_post_ids );
	$ai4seo_attachment_attributes_coverage_summary = ai4seo_get_attachment_attributes_coverage_summary( $ai4seo_attachment_attributes_coverage );
	$ai4seo_num_total_attachment_attributes        = ai4seo_get_active_num_attachment_attributes();
}

// Large all-lists derive complete/missing media state from the rendered page instead of global coverage options.
if ( $ai4seo_should_derive_current_page_attachment_status_ids ) {
	$ai4seo_generated_attachment_post_ids          = ai4seo_read_post_ids_with_postmeta_key( $ai4seo_current_attachment_post_ids, AI4SEO_POST_META_GENERATED_DATA_META_KEY );
	$ai4seo_generated_attachment_post_id_lookup    = array_flip( array_map( 'intval', $ai4seo_generated_attachment_post_ids ) );
	$ai4seo_fully_covered_attachment_post_ids      = array();
	$ai4seo_missing_attachment_attributes_post_ids = array();

	foreach ( $ai4seo_current_attachment_post_ids as $ai4seo_this_current_attachment_post_id ) {
		$ai4seo_this_current_attachment_post_id             = (int) $ai4seo_this_current_attachment_post_id;
		$ai4seo_this_current_attachment_coverage_summary    = (int) ( $ai4seo_attachment_attributes_coverage_summary[ $ai4seo_this_current_attachment_post_id ] ?? 0 );
		$ai4seo_this_current_attachment_coverage_percentage = $ai4seo_num_total_attachment_attributes
			? min( 100, round( ( $ai4seo_this_current_attachment_coverage_summary / $ai4seo_num_total_attachment_attributes ) * 100, 2 ) )
			: 100;
		$ai4seo_this_current_attachment_is_generated        = isset( $ai4seo_generated_attachment_post_id_lookup[ $ai4seo_this_current_attachment_post_id ] );

		if ( $ai4seo_this_current_attachment_coverage_percentage >= 100
			&& ( ! $ai4seo_do_generate_attachment_attributes_for_fully_covered_entries || $ai4seo_this_current_attachment_is_generated ) ) {
			$ai4seo_fully_covered_attachment_post_ids[] = $ai4seo_this_current_attachment_post_id;
			continue;
		}

		$ai4seo_missing_attachment_attributes_post_ids[] = $ai4seo_this_current_attachment_post_id;
	}
}

// remove entries from $ai4seo_all_failed_to_fill_attributes_attachment_post_ids that are not on this page.
$ai4seo_current_page_failed_to_fill_attachment_post_ids = array();

if ( $ai4seo_all_attachment_posts ) {
	foreach ( $ai4seo_all_attachment_posts as $ai4seo_this_attachment_post ) {
		if ( in_array( $ai4seo_this_attachment_post->ID, $ai4seo_failed_attributes_attachment_post_ids ) ) {
			$ai4seo_current_page_failed_to_fill_attachment_post_ids[] = $ai4seo_this_attachment_post->ID;
		}
	}
}

// Define variable for the label of the failed-attributes-generations-link.
$ai4seo_retry_all_failed_attachment_attributes_generations_link_label = __( 'Retry all failed', 'ai-for-seo' );

// retry all failed attachment attributes generations link.
$ai4seo_retry_all_failed_attachment_attributes_generations_link_tag = ai4seo_get_small_icon_button_tag( 'rotate', $ai4seo_retry_all_failed_attachment_attributes_generations_link_label, '', 'ai4seo_retry_all_failed_attachment_attributes(this); return false;' );

// Give AJAX hydration a stable target for the full media table retry action.
$ai4seo_retry_all_failed_attachment_attributes_container_id = 'ai4seo-retry-all-failed-attachment-attributes';

// The related-media modal is scoped, while retry-all-failed is still a global media action.
$ai4seo_should_show_retry_all_failed_attachment_attributes_generations_link = ! $ai4seo_is_related_attachments_modal
	&& ai4seo_should_show_content_type_retry_all_failed_button( $ai4seo_filter_context['status_options'], $ai4seo_status_filter_counts );
$ai4seo_retry_all_failed_attachment_attributes_container_class              = $ai4seo_should_show_retry_all_failed_attachment_attributes_generations_link ? '' : ' ai4seo-display-none';

$ai4seo_consider_purchasing_more_credits_link_tag = ai4seo_get_small_icon_button_tag( 'circle-plus', __( 'Get more Credits', 'ai-for-seo' ), 'ai4seo-primary-button', 'ai4seo_close_all_modals();ai4seo_open_get_more_credits_modal();' );

$ai4seo_active_attachment_attributes      = ai4seo_get_active_attachment_attributes();
$ai4seo_active_attachment_attribute_names = ai4seo_get_active_attachment_attributes_names( $ai4seo_active_attachment_attributes );

// Use a modal-specific checkbox name so bulk actions only read selections inside the related-media modal.
$ai4seo_bulk_generation_queue_checkbox_name   = $ai4seo_is_related_attachments_modal
	? 'ai4seo_bulk_generation_queue_related_attachment_post_ids'
	: 'ai4seo_bulk_generation_queue_attachment_post_ids';
$ai4seo_bulk_generation_queue_action_controls = ai4seo_get_bulk_generation_queue_action_controls(
	AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
	$ai4seo_bulk_generation_queue_checkbox_name,
	$ai4seo_filter_status,
	array(
		'list_location' => $ai4seo_is_related_attachments_modal ? 'related_attachments_modal' : 'main',
	)
);
$ai4seo_content_type_filter_controls_html     = ai4seo_get_content_type_filter_controls_html(
	$ai4seo_filter_context,
	$ai4seo_status_filter_counts,
	$ai4seo_total_items,
	$ai4seo_all_attachment_posts ? $ai4seo_bulk_generation_queue_action_controls : '',
	array(
		'defer_status_filters'           => $ai4seo_should_defer_status_filters,
		'content_context'                => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		'post_type'                      => $ai4seo_main_attachment_post_type,
		'retry_all_failed_button_target' => $ai4seo_is_related_attachments_modal ? '' : $ai4seo_retry_all_failed_attachment_attributes_container_id,
	)
);


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Hide media-page-only import actions when this table is embedded in the related-media modal.
if ( ! $ai4seo_is_related_attachments_modal && isset( $ai4seo_import_nextgen_gallery_button ) && $ai4seo_import_nextgen_gallery_button ) {
	echo "<div class='ai4seo-buttons-wrapper'>";
		ai4seo_echo_wp_kses( $ai4seo_import_nextgen_gallery_button );
	echo '</div>';
}

ai4seo_echo_wp_kses( $ai4seo_content_type_filter_controls_html );

// Stop script if no posts have been found -> show message and stop page rendering.
if ( ! $ai4seo_all_attachment_posts ) {
	$ai4seo_remove_filters_button_html = ai4seo_get_content_type_remove_filters_button_html( $ai4seo_filter_context );

	echo '<p>';
		echo $ai4seo_is_related_attachments_modal
			? esc_html__( 'No related media found.', 'ai-for-seo' )
			: esc_html__( 'No relevant media found.', 'ai-for-seo' );

	if ( $ai4seo_remove_filters_button_html ) {
		echo ' ';
		ai4seo_echo_wp_kses( $ai4seo_remove_filters_button_html );
	}
	echo '</p>';

	return;
}

// Keep the full-page WPML explanation out of the modal because related media already inherits normal filters.
if ( ! $ai4seo_is_related_attachments_modal && ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
	echo '<p>';
		echo '<strong>' . esc_html__( 'Heads up:', 'ai-for-seo' ) . '</strong> ';
		echo esc_html__( 'Your images appear on different language versions of your website. Therefore, each image needs to be analyzed for each language separately to ensure optimal SEO performance across all languages.', 'ai-for-seo' );
	echo '</p>';
}

// Display table with entries.
// Resolve active ARIA states once so table headers and their direction-toggle links stay synchronized.
$ai4seo_id_column_aria_sort           = ai4seo_get_content_type_sortable_column_aria_sort_value( 'id', $ai4seo_filter_context );
$ai4seo_title_column_aria_sort        = ai4seo_get_content_type_sortable_column_aria_sort_value( 'title', $ai4seo_filter_context );
$ai4seo_seo_progress_column_aria_sort = ai4seo_get_content_type_sortable_column_aria_sort_value( 'seo_progress', $ai4seo_filter_context );

echo "<div class='ai4seo-posts-table-container'>";
echo "<table class='widefat striped table-view-list attachments ai4seo-posts-table'>";
	echo '<tr>';
		echo "<th class='ai4seo-bulk-generation-queue-checkbox-column'>";
			ai4seo_echo_wp_kses( ai4seo_get_select_all_checkbox( $ai4seo_bulk_generation_queue_checkbox_name, '' ) );
		echo '</th>';

		echo "<th class='manage-column sortable ai4seo-content-list-sortable-column'" . ( $ai4seo_id_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_id_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'ID', 'ai-for-seo' ), 'id', $ai4seo_filter_context ) );
		echo '</th>';
		echo '<th></th>';
		echo "<th class='manage-column sortable ai4seo-hidden-on-mobile ai4seo-content-list-sortable-column'" . ( $ai4seo_title_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_title_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'Title', 'ai-for-seo' ), 'title', $ai4seo_filter_context ) );
		echo '</th>';
		echo "<th class='manage-column sortable ai4seo-content-list-sortable-column'" . ( $ai4seo_seo_progress_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_seo_progress_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'SEO Coverage', 'ai-for-seo' ), 'seo_progress', $ai4seo_filter_context ) );

			echo "<span class='ai4seo-visible-on-mobile'> / ";
				echo esc_html__( 'Title', 'ai-for-seo' );
			echo '</span>';

if ( $ai4seo_active_attachment_attribute_names ) {
	echo " <span class='ai4seo-content-list-active-fields-note'>(" . esc_html( implode( ', ', $ai4seo_active_attachment_attribute_names ) ) . ')</span>';
}

			// Keep the global retry action aligned with the Failed status filter, including deferred AJAX hydration.
if ( ! $ai4seo_is_related_attachments_modal && ( $ai4seo_should_show_retry_all_failed_attachment_attributes_generations_link || $ai4seo_should_defer_status_filters ) ) {
	echo '<div'
		. " id='" . esc_attr( $ai4seo_retry_all_failed_attachment_attributes_container_id ) . "'"
		. " class='ai4seo-table-title-button ai4seo-content-list-retry-all-failed-button" . esc_attr( $ai4seo_retry_all_failed_attachment_attributes_container_class ) . "'"
		. " data-ai4seo-content-context='" . esc_attr( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES ) . "'"
		. " data-ai4seo-post-type='" . esc_attr( $ai4seo_main_attachment_post_type ) . "'"
		. '>';
	ai4seo_echo_wp_kses( $ai4seo_retry_all_failed_attachment_attributes_generations_link_tag );
	echo '</div>';
}
		echo '</th>';
		echo '<th></th>';
	echo '</tr>';

	// Loop through entries and display table-row for each entry.
foreach ( $ai4seo_all_attachment_posts as $ai4seo_this_attachment ) {
	// Prepare variables.
	$ai4seo_this_post_attachment_id             = (int) $ai4seo_this_attachment->ID ?? '';
	$ai4seo_this_attachment_title               = $ai4seo_this_attachment->post_title ?? '';
	$ai4seo_this_mime_type                      = $ai4seo_this_attachment->post_mime_type ?? '';
	$ai4seo_this_post_link                      = get_edit_post_link( $ai4seo_this_attachment ) ?: $ai4seo_this_attachment->guid ?? '';
	$ai4seo_this_attachment_language            = ai4seo_try_get_post_language_by_checking_multilanguage_plugins( $ai4seo_this_post_attachment_id );
	$ai4seo_this_attachment_title_with_language = $ai4seo_this_attachment_title;

	if ( '' !== $ai4seo_this_attachment_language ) {
		$ai4seo_this_attachment_title_with_language .= ' (' . $ai4seo_this_attachment_language . ')';
	}

	$ai4seo_this_attachment_post_date_gmt     = (string) ( $ai4seo_this_attachment->post_date_gmt ?? '' );
	$ai4seo_this_attachment_post_date         = (string) ( $ai4seo_this_attachment->post_date ?? '' );
	$ai4seo_this_attachment_post_modified_gmt = (string) ( $ai4seo_this_attachment->post_modified_gmt ?? '' );
	$ai4seo_this_attachment_post_modified     = (string) ( $ai4seo_this_attachment->post_modified ?? '' );

	// get timestamp of post date.
	$ai4seo_this_attachment_date_timestamp        = strtotime( $ai4seo_this_attachment_post_date_gmt . ' UTC' );
	$ai4seo_this_attachment_post_date_display     = $ai4seo_this_attachment_post_date;
	$ai4seo_this_attachment_post_date_gmt_display = $ai4seo_this_attachment_post_date_gmt;

	if ( $ai4seo_this_attachment_date_timestamp ) {
		$ai4seo_this_attachment_post_date_gmt_display = ai4seo_format_unix_timestamp( $ai4seo_this_attachment_date_timestamp, 'auto', 'auto', ' ', 'UTC' );
	}

	$ai4seo_this_attachment_post_date_local_timestamp = strtotime( $ai4seo_this_attachment_post_date );
	if ( $ai4seo_this_attachment_post_date_local_timestamp ) {
		$ai4seo_this_attachment_post_date_display = ai4seo_format_unix_timestamp( $ai4seo_this_attachment_post_date_local_timestamp );
	}

	// check if the new-or-existing filter complies with this post ("both" -> yes, "new" -> only posts with post_date_timestamp > reference_timestamp, "existing" -> only posts with post_date_timestamp <= reference_timestamp).
	$ai4seo_is_excluded_by_new_or_existing_filter = false;

	if ( 'new' === $ai4seo_bulk_generation_new_or_existing_filter && $ai4seo_this_attachment_date_timestamp <= $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp ) {
		$ai4seo_is_excluded_by_new_or_existing_filter = true;
	} elseif ( 'existing' === $ai4seo_bulk_generation_new_or_existing_filter && $ai4seo_this_attachment_date_timestamp > $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp ) {
		$ai4seo_is_excluded_by_new_or_existing_filter = true;
	}

	// this attachment attributes coverage.
	$ai4seo_this_attachment_attribute_coverage_summary = $ai4seo_attachment_attributes_coverage_summary[ $ai4seo_this_post_attachment_id ] ?? 0;

	if ( $ai4seo_active_attachment_attributes ) {
		$ai4seo_this_attachment_attribute_coverage_percentage = $ai4seo_num_total_attachment_attributes ? round( ( $ai4seo_this_attachment_attribute_coverage_summary / $ai4seo_num_total_attachment_attributes ) * 100, 2 ) : 100;
		$ai4seo_this_attachment_attribute_coverage_percentage = min( 100, $ai4seo_this_attachment_attribute_coverage_percentage );
		$ai4seo_this_attachment_attributes_is_not_covered     = ( $ai4seo_this_attachment_attribute_coverage_percentage < 100 );
	} else {
		$ai4seo_this_attachment_attribute_coverage_percentage = 100;
		$ai4seo_this_attachment_attributes_is_not_covered     = false;
	}

	$ai4seo_this_attachment_post_is_fully_covered      = in_array( $ai4seo_this_post_attachment_id, $ai4seo_fully_covered_attachment_post_ids );
	$ai4seo_this_attachment_attributes_is_not_finished = ( $ai4seo_this_attachment_attribute_coverage_percentage < 100 );
	$ai4seo_this_attachment_post_is_generated          = in_array( $ai4seo_this_post_attachment_id, $ai4seo_generated_attachment_post_ids );
	$ai4seo_this_attachment_sub_info_rows              = array();
	$ai4seo_this_attachment_has_recent_activity        = isset( $ai4seo_recent_attachment_activity_entries_by_post_id[ $ai4seo_this_post_attachment_id ] );
	$ai4seo_this_recent_activity_details_subtext_tag   = '';

	// Recent activity entries should link directly to the editor where details can be inspected.
	if ( $ai4seo_this_attachment_has_recent_activity ) {
		$ai4seo_this_recent_activity_details_onclick = 'ai4seo_open_attachment_attributes_editor_modal(' . absint( $ai4seo_this_post_attachment_id );

		if ( $ai4seo_current_attachment_post_ids ) {
			$ai4seo_this_recent_activity_details_onclick .= ', ' . wp_json_encode( array_values( array_map( 'intval', $ai4seo_current_attachment_post_ids ) ) );
		}

		$ai4seo_this_recent_activity_details_onclick    .= ');';
		$ai4seo_this_recent_activity_details_subtext_tag = ai4seo_get_recent_activity_details_subtext_tag(
			$ai4seo_this_recent_activity_details_onclick,
			$ai4seo_recent_attachment_activity_entries_by_post_id[ $ai4seo_this_post_attachment_id ]
		);
	}

	$ai4seo_this_attachment_upload_timestamp_tooltip = sprintf(
		/* translators: 1: post_date_gmt, 2: post_date, 3: post_modified_gmt, 4: post_modified */
		__( "post_date_gmt: %1\$s\npost_date: %2\$s\npost_modified_gmt: %3\$s\npost_modified: %4\$s", 'ai-for-seo' ),
		$ai4seo_this_attachment_post_date_gmt ?: '-',
		$ai4seo_this_attachment_post_date ?: '-',
		$ai4seo_this_attachment_post_modified_gmt ?: '-',
		$ai4seo_this_attachment_post_modified ?: '-'
	);

	$ai4seo_this_attachment_sub_info_rows[] = "<span class='ai4seo-attachment-upload-timestamp' title='" . esc_attr( $ai4seo_this_attachment_upload_timestamp_tooltip ) . "'>" .
		sprintf(
			/* translators: %s: upload timestamp */
			esc_html__( 'Upload time: %s', 'ai-for-seo' ),
			esc_html( $ai4seo_this_attachment_post_date_display )
		) .
	'</span>';

	$ai4seo_this_attachment_sub_info_rows[] =
		sprintf(
			/* translators: %s: MIME type */
			esc_html__( 'MIME type: %s', 'ai-for-seo' ),
			esc_html( $ai4seo_this_mime_type ?: '-' )
		);

	$ai4seo_this_attachment_coverage_suffix = $ai4seo_this_attachment_post_is_fully_covered ? ' ' . esc_html__( '(completed)', 'ai-for-seo' ) : '';
	$ai4seo_this_attachment_sub_info_rows[] =
		sprintf(
			/* translators: 1: coverage percentage, 2: optional fully covered note */
			esc_html__( 'Coverage: %1$s%%%2$s', 'ai-for-seo' ),
			esc_html( ai4seo_stringify( $ai4seo_this_attachment_attribute_coverage_percentage ) ),
			$ai4seo_this_attachment_coverage_suffix
		);

	$ai4seo_this_attachment_sub_info_rows[] =
		(
			$ai4seo_this_attachment_post_is_generated
				? sprintf(
					/* translators: %s: plugin name */
					esc_html__( '%s has generated data for this media file.', 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME )
				)
				: sprintf(
					/* translators: %s: plugin name */
					__( "%s has <span class='ai4seo-text-underlined'>not</span> generated data for this media file yet.", 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME )
				)
		);

	$ai4seo_this_attachment_sub_info_html = "<div class='ai4seo-sub-info'>" . implode( ' &bull; ', $ai4seo_this_attachment_sub_info_rows ) . '</div>';

	$ai4seo_is_attachment_post_failed = in_array( $ai4seo_this_post_attachment_id, $ai4seo_current_page_failed_to_fill_attachment_post_ids );
	// Check queue state separately, so Pending and Processing can have different visuals.
	$ai4seo_is_attachment_post_missing               = in_array( $ai4seo_this_post_attachment_id, $ai4seo_missing_attachment_attributes_post_ids, true );
	$ai4seo_is_attachment_post_pending               = in_array( $ai4seo_this_post_attachment_id, $ai4seo_pending_attributes_attachment_post_ids, true );
	$ai4seo_is_attachment_post_processing            = in_array( $ai4seo_this_post_attachment_id, $ai4seo_processing_attributes_attachment_post_ids, true );
	$ai4seo_is_attachment_post_auto_queue_disallowed = in_array( $ai4seo_this_post_attachment_id, $ai4seo_auto_queue_disallowed_attachment_attributes_post_ids, true );
	$ai4seo_is_attachment_post_waiting_to_get_queued = false;
	$ai4seo_is_insufficient_credits                  = false;
	$ai4seo_should_show_auto_queue_disallowed_note   = false;

	// Failed entries should keep their failed state even if stale queue data exists.
	if ( $ai4seo_is_attachment_post_failed ) {
		$ai4seo_is_attachment_post_pending    = false;
		$ai4seo_is_attachment_post_processing = false;
	}

	// Missing entries are waiting for SEO Autopilot excavation only when auto queueing can actually pick them up.
	$ai4seo_can_attachment_post_get_auto_queued = $ai4seo_is_bulk_generation_activated
		&& $ai4seo_should_auto_queue_bulk_generation_entries
		&& $ai4seo_is_attachment_post_missing
		&& ! $ai4seo_is_attachment_post_pending
		&& ! $ai4seo_is_attachment_post_processing
		&& ! $ai4seo_is_attachment_post_failed
		&& ! $ai4seo_is_attachment_post_auto_queue_disallowed
		&& ! $ai4seo_is_excluded_by_new_or_existing_filter
		&& ( $ai4seo_this_attachment_attributes_is_not_finished || ( ! $ai4seo_this_attachment_post_is_generated && $ai4seo_do_generate_attachment_attributes_for_fully_covered_entries ) );

	if ( $ai4seo_can_attachment_post_get_auto_queued ) {
		if ( $ai4seo_current_credits_balance < $ai4seo_approximate_cost_per_attachment_post ) {
			$ai4seo_is_insufficient_credits = true;
		} else {
			$ai4seo_is_attachment_post_waiting_to_get_queued = true;
		}
	}

	$ai4seo_preview_image_url      = '';
	$ai4seo_preview_image_alt_text = __( 'No image preview available', 'ai-for-seo' );

	if ( in_array( $ai4seo_this_mime_type, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ) ) ) {
		$ai4seo_preview_image_url = wp_get_attachment_image_url( $ai4seo_this_post_attachment_id, array( 48, 48 ) );

		if ( $ai4seo_preview_image_url ) {
			$ai4seo_this_attachment_alt_text = trim( (string) get_post_meta( $ai4seo_this_post_attachment_id, '_wp_attachment_image_alt', true ) );
			$ai4seo_preview_image_alt_text   = $ai4seo_this_attachment_alt_text ?: $ai4seo_this_attachment_title_with_language;
		}

		if ( ! $ai4seo_preview_image_url ) {
			$ai4seo_preview_image_url = $ai4seo_this_post_link;
		}
	}

	if ( ! $ai4seo_preview_image_url ) {
		$ai4seo_preview_image_url = ai4seo_get_assets_images_url( 'icons/document-question-48x48.png' );
	}

	echo '<tr>';
		// Use the shared renderer so the main Media table and Related Media modal retain separate labeled controls.
		echo "<td class='ai4seo-bulk-generation-queue-checkbox-column'>";
			ai4seo_echo_wp_kses(
				ai4seo_get_bulk_generation_queue_entry_checkbox(
					$ai4seo_bulk_generation_queue_checkbox_name,
					$ai4seo_this_post_attachment_id,
					$ai4seo_this_attachment_title_with_language
				)
			);
		echo '</td>';

		// Post-ID.
		echo '<td>';
			echo esc_html( $ai4seo_this_post_attachment_id );
		echo '</td>';

		// Image or File Preview.
		echo "<td class='ai4seo-attachment-list-image-preview'>";
			echo "<a href='" . esc_url( $ai4seo_this_post_link ) . "' target='_blank'>";
				echo "<img src='" . esc_url( $ai4seo_preview_image_url ) . "' alt='" . esc_attr( $ai4seo_preview_image_alt_text ) . "'/>";
			echo '</a>';
		echo '</td>';

		// Title.
		echo "<td class='title column-title has-row-actions column-primary post-title ai4seo-hidden-on-mobile'>";
			echo '<strong>';
				echo "<a href='" . esc_url( $ai4seo_this_post_link ) . "' target='_blank'>";
					echo esc_html( $ai4seo_this_attachment_title_with_language );
				echo '</a>';
			echo '</strong>';

			ai4seo_echo_wp_kses( $ai4seo_this_attachment_sub_info_html );
		echo '</td>';

		// Generation Coverage.
		echo "<td class='ai4seo-generation-coverage'>";
	if ( $ai4seo_active_attachment_attributes ) {
		$ai4seo_progress_bar_animation_class           = '';
		$ai4seo_should_show_auto_queue_disallowed_note = $ai4seo_is_bulk_generation_activated
		&& $ai4seo_should_auto_queue_bulk_generation_entries
		&& $ai4seo_is_attachment_post_auto_queue_disallowed
		&& $ai4seo_this_attachment_attributes_is_not_finished
		&& ! $ai4seo_is_attachment_post_pending
		&& ! $ai4seo_is_attachment_post_processing;
		$ai4seo_should_show_autopilot_pending_note     = $ai4seo_is_attachment_post_pending
		&& ! $ai4seo_is_attachment_post_processing;

		if ( $ai4seo_is_attachment_post_processing ) {
					$ai4seo_progress_bar_animation_class = ' ai4seo-green-animated-progress-bar';
		}

				// output progress bar.
				echo "<progress id='ai4seo-seo-coverage-progress-bar-" . esc_attr( $ai4seo_this_post_attachment_id ) . "' class='ai4seo-seo-coverage-progress-bar" . esc_attr( $ai4seo_progress_bar_animation_class ) . ( $ai4seo_this_attachment_attributes_is_not_finished ? ' ai4seo-progress-bar-not-finished' : ' ai4seo-progress-bar-finished' ) . "' value='" . esc_attr( $ai4seo_this_attachment_attribute_coverage_percentage ) . "' max='100'></progress>";

		if ( $ai4seo_is_attachment_post_waiting_to_get_queued ) {
				echo "<div class='ai4seo-sub-info'>";
					echo esc_html__( 'Waiting to get queued by SEO Autopilot...', 'ai-for-seo' );
				echo '</div>';
		} elseif ( $ai4seo_should_show_autopilot_pending_note ) {
			if ( $ai4seo_is_bulk_generation_activated ) {
				echo "<div class='ai4seo-sub-info'>";
				echo esc_html__( 'Queued for SEO Autopilot. Processing starts with a future SEO Autopilot run.', 'ai-for-seo' );
				echo '</div>';
			} else {
				echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
				echo esc_html__( 'Queued, but SEO Autopilot is deactivated for this content type.', 'ai-for-seo' );
				echo '</div>';
			}
		} elseif ( $ai4seo_is_attachment_post_processing ) {
			echo "<div class='ai4seo-sub-info'>";
			echo esc_html__( 'This entry is currently being processed.', 'ai-for-seo' );
			echo '</div>';
		} elseif ( $ai4seo_is_insufficient_credits ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			echo esc_html__( 'Insufficient Credits', 'ai-for-seo' ) . '. ';
			ai4seo_echo_wp_kses( $ai4seo_consider_purchasing_more_credits_link_tag );
			echo '</div>';
		} elseif ( $ai4seo_is_attachment_post_failed && $ai4seo_this_attachment_attributes_is_not_finished ) {
			echo "<div class='ai4seo-seo-data-not-covered-message'>";
			echo '<span>' . esc_html__( 'Failed to automatically fill media attributes.', 'ai-for-seo' ) . '</span> ';
			ai4seo_echo_wp_kses( ai4seo_get_small_icon_button_tag( 'arrow-up-right-from-square', __( 'Try it manually', 'ai-for-seo' ), '', 'ai4seo_open_attachment_attributes_editor_modal("' . esc_js( $ai4seo_this_post_attachment_id ) . '");' ) );
			echo '</div>';
		} elseif ( $ai4seo_is_excluded_by_new_or_existing_filter && $ai4seo_this_attachment_attributes_is_not_finished ) {
			$ai4seo_new_or_existing_filter_reference_timestamp_formatted = ai4seo_format_unix_timestamp( $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp );

			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			if ( 'new' === $ai4seo_bulk_generation_new_or_existing_filter ) {
				printf(
					/* translators: %s: reference timestamp */
					esc_html__( "Excluded by 'New or existing entries' filter: Uploaded before %s.", 'ai-for-seo' ),
					esc_html( $ai4seo_new_or_existing_filter_reference_timestamp_formatted )
				);
			} elseif ( 'existing' === $ai4seo_bulk_generation_new_or_existing_filter ) {
					printf(
					/* translators: %s: reference timestamp */
						esc_html__( "Excluded by 'New or existing entries' filter: Uploaded after %s.", 'ai-for-seo' ),
						esc_html( $ai4seo_new_or_existing_filter_reference_timestamp_formatted )
					);
			} else {
				echo esc_html__( "Excluded by 'New or existing entries' filter.", 'ai-for-seo' );
			}
									echo '</div>';
		} elseif ( $ai4seo_this_attachment_post_is_fully_covered && ! $ai4seo_this_attachment_post_is_generated && ! $ai4seo_do_generate_attachment_attributes_for_fully_covered_entries ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			echo esc_html__( 'Excluded because all attributes are already filled, and the setting "SEO Autopilot: Include Complete Entries When Overwriting" is disabled, or no fields are allowed to be overwritten.', 'ai-for-seo' );
			echo '</div>';
		}

		if ( $ai4seo_should_show_auto_queue_disallowed_note ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			echo esc_html__( 'Auto Queue will ignore this entry because it was excluded with a bulk action.', 'ai-for-seo' );
			echo '</div>';
		}
	} else {
		echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			echo esc_html__( 'No media attributes are active.', 'ai-for-seo' );
		echo '</div>';
	}

	if ( $ai4seo_this_recent_activity_details_subtext_tag ) {
		ai4seo_echo_wp_kses( $ai4seo_this_recent_activity_details_subtext_tag );
	}

			// Title display for mobile version.
			echo "<div class='ai4seo-visible-on-mobile'>";
				echo '<strong>';
					echo "<a href='" . esc_url( $ai4seo_this_post_link ) . "' target='_blank'>";
						echo esc_html( $ai4seo_this_attachment_title_with_language );
					echo '</a>';
				echo '</strong>';
				ai4seo_echo_wp_kses( $ai4seo_this_attachment_sub_info_html );
			echo '</div>';
			echo '</td>';

			// Actions.
			echo '<td>';
	if ( $ai4seo_active_attachment_attributes ) {
		ai4seo_echo_wp_kses( ai4seo_get_edit_attachment_attributes_button( $ai4seo_this_post_attachment_id, $ai4seo_current_attachment_post_ids ) );
	}
			echo '</td>';
			echo '</tr>';
}
echo '</table>';
echo '</div>';

// Pagination.
$ai4seo_pagination_base_argument = add_query_arg(
	array(
		'page'           => AI4SEO_PLUGIN_IDENTIFIER,
		'ai4seo_subpage' => $ai4seo_nice_post_type, // e.g. 'media'.
		'ai4seo_page'    => '%#%', // placeholder for paginate_links().
	),
	admin_url( 'admin.php' )
);
$ai4seo_total_pages              = max( 1, $ai4seo_total_pages );
$ai4seo_current_page             = max( 1, $ai4seo_current_page );
$ai4seo_pagination_base_argument = $ai4seo_pagination_base_argument ?: '%_%'; // Default base if not defined.

// Preserve the modal source post while reusing the media page pagination helper.
if ( $ai4seo_is_related_attachments_modal && $ai4seo_related_attachments_modal_post_id > 0 ) {
	$ai4seo_filter_query_args['post_id'] = $ai4seo_related_attachments_modal_post_id;
}

$ai4seo_pagination_arguments = array(
	'base'      => $ai4seo_pagination_base_argument,
	'total'     => $ai4seo_total_pages,
	'current'   => $ai4seo_current_page,
	'show_all'  => false,
	'end_size'  => 2,
	'mid_size'  => 0,
	'prev_text' => '&larr; ' . __( 'Previous', 'ai-for-seo' ),
	'next_text' => __( 'Next', 'ai-for-seo' ) . ' &rarr;',
	'add_args'  => ( $ai4seo_filter_query_args ?: false ),
);

$ai4seo_pagination_links = paginate_links( $ai4seo_pagination_arguments );

if ( ! empty( $ai4seo_pagination_links ) ) {
	$ai4seo_pagination_links = ai4seo_normalize_pagination_links( $ai4seo_pagination_links );

	echo "<div class='ai4seo-pagination'>";
		ai4seo_echo_wp_kses( $ai4seo_pagination_links );
	echo '</div>';
}
