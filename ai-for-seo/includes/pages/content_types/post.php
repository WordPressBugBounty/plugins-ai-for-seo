<?php
/**
 * Renders the content of the submenu page for the "AI for SEO" posts-page.
 *
 * @since 1.0
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

$ai4seo_supported_post_types = ai4seo_get_supported_post_types();

$ai4seo_post_type = ai4seo_get_active_post_type_subpage();

if ( ! in_array( $ai4seo_post_type, $ai4seo_supported_post_types ) ) {
	echo 'Unknown post type: ' . esc_html( $ai4seo_post_type );
	return;
}

$ai4seo_translated_post_type        = ai4seo_get_post_type_translation( $ai4seo_post_type );
$ai4seo_translated_post_type_plural = ai4seo_get_post_type_translation( $ai4seo_post_type, true );

// sanitize and get current page (pagination).
$ai4seo_current_page = absint( wp_unslash( $_REQUEST['ai4seo_page'] ?? 1 ) );

if ( $ai4seo_current_page < 1 ) {
	$ai4seo_current_page = 1;
}


$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

// check if the cron job should be executed sooner.
if ( isset( $_GET['ai4seo-execute-cron-job-sooner'] ) && sanitize_text_field( wp_unslash( $_GET['ai4seo-execute-cron-job-sooner'] ) ) ) {
	ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
}

// Define variable for the label of the failed-metadata-generations-link.
$ai4seo_retry_all_failed_metadata_generations_link_label = __( 'Retry all failed', 'ai-for-seo' );

// retry all failed metadata generations link.
$ai4seo_retry_all_failed_metadata_generations_link_tag = ai4seo_get_small_icon_button_tag( 'rotate', $ai4seo_retry_all_failed_metadata_generations_link_label, '', "ai4seo_retry_all_failed_metadata(this, '" . esc_js( $ai4seo_post_type ) . "'); return false;" );

// Give AJAX hydration a stable target that is scoped to this post-type table.
$ai4seo_retry_all_failed_metadata_generations_container_id = 'ai4seo-retry-all-failed-metadata-' . sanitize_html_class( $ai4seo_post_type );

$ai4seo_consider_purchasing_more_credits_link_tag = ai4seo_get_small_icon_button_tag( 'circle-plus', __( 'Get more Credits', 'ai-for-seo' ), 'ai4seo-primary-button', 'ai4seo_close_all_modals(); ai4seo_open_get_more_credits_modal();' );

// get value for bulk toggle checkbox.
$ai4seo_is_bulk_generation_activated              = ai4seo_is_bulk_generation_enabled( $ai4seo_post_type );
$ai4seo_is_bulk_generation_checked_phrase         = ( $ai4seo_is_bulk_generation_activated ? 'checked' : '' );
$ai4seo_should_auto_queue_bulk_generation_entries = ai4seo_should_auto_queue_bulk_generation_entries();
$ai4seo_disabled_post_author_ids                  = ai4seo_get_disabled_post_author_ids();
$ai4seo_disabled_taxonomy_terms                   = ai4seo_get_disabled_taxonomy_terms();

// Read metadata WPML exclusions once so filters, status counts, and queue previews use the same scope.
$ai4seo_disabled_metadata_wpml_language_codes          = ai4seo_get_disabled_metadata_wpml_language_codes();
$ai4seo_do_generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();


// ___________________________________________________________________________________________ \\
// === READ POSTS ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Prepare arguments for the wp-query.
$ai4seo_posts_query_arguments = array(
	'post_status'      => array( 'publish', 'future' ),
	'post_type'        => $ai4seo_post_type,
	'posts_per_page'   => 20,
	'orderby'          => 'ID',
	'order'            => 'DESC',
	'suppress_filters' => true,
	'lang'             => 'all',
);

if ( $ai4seo_disabled_post_author_ids ) {
	$ai4seo_posts_query_arguments['author__not_in'] = $ai4seo_disabled_post_author_ids;
}

// Build the shared filter context first; post lists defer search IDs to the optimized resolver.
$ai4seo_filter_setup_arguments = array(
	'form_action'                  => ai4seo_get_post_type_page_url( $ai4seo_post_type, 1 ),
	'nonce_action'                 => 'ai4seo_content_type_filter_form',
	'nonce_name'                   => 'ai4seo_content_type_filter_nonce',
	'post_types'                   => array( $ai4seo_post_type ),
	'post_status'                  => array( 'publish', 'future' ),
	'author_not_in'                => $ai4seo_disabled_post_author_ids,
	'disabled_taxonomy_terms'      => $ai4seo_disabled_taxonomy_terms,
	'disabled_wpml_language_codes' => $ai4seo_disabled_metadata_wpml_language_codes,
	'defer_search_ids'             => true,
	'per_page'                     => 20,
);

$ai4seo_filter_context = ai4seo_setup_content_type_filters( $ai4seo_filter_setup_arguments );

$ai4seo_filter_status   = $ai4seo_filter_context['filter_status'];
$ai4seo_filter_language = $ai4seo_filter_context['filter_language'];
// Reuse the shared sort normalizer so rows, links, and AJAX-hydrated filters stay in sync.
$ai4seo_sort_args      = ai4seo_normalize_content_type_sort_args( $ai4seo_filter_context['orderby'] ?? 'id', $ai4seo_filter_context['order'] ?? 'desc' );
$ai4seo_orderby        = $ai4seo_sort_args['orderby'];
$ai4seo_order          = $ai4seo_sort_args['order'];
$ai4seo_search_ids     = $ai4seo_filter_context['search_ids'];
$ai4seo_items_per_page = (int) $ai4seo_filter_context['per_page'];

if ( $ai4seo_items_per_page < 1 ) {
	$ai4seo_items_per_page = 20;
}

// Pagination reuses the exact active filter args produced by the shared filter helper.
$ai4seo_filter_query_args = ai4seo_get_content_type_filter_query_args( $ai4seo_filter_context );

$ai4seo_bulk_generation_new_or_existing_filter                     = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
$ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );
$ai4seo_metadata_credits_costs_per_post                            = ai4seo_calculate_metadata_credits_cost_per_post();

// Start with empty per-page state; the optimized resolver fills only what row rendering needs.
$ai4seo_pending_metadata_post_ids                      = array();
$ai4seo_processing_metadata_post_ids                   = array();
$ai4seo_all_failed_metadata_post_ids                   = array();
$ai4seo_hidden_metadata_post_ids                       = array();
$ai4seo_auto_queue_disallowed_metadata_post_ids        = array();
$ai4seo_missing_metadata_post_ids                      = array();
$ai4seo_fully_covered_metadata_post_ids                = array();
$ai4seo_generated_metadata_post_ids                    = array();
$ai4seo_waiting_to_get_queued_metadata_post_ids        = array();
$ai4seo_status_filter_counts                           = array();
$ai4seo_total_items                                    = 0;
$ai4seo_total_pages                                    = 1;
$ai4seo_current_page_post_ids                          = array();
$ai4seo_sort_value_map                                 = array();
$ai4seo_should_derive_current_page_metadata_status_ids = false;
$ai4seo_should_defer_status_filters                    = false;

// Resolve common post-type lists without loading every post ID from the current post type.
$ai4seo_content_type_list_result = ai4seo_resolve_optimized_post_content_type_list(
	array(
		'post_type'                                  => $ai4seo_post_type,
		'post_status'                                => array( 'publish', 'future' ),
		'author_not_in'                              => $ai4seo_disabled_post_author_ids,
		'disabled_taxonomy_terms'                    => $ai4seo_disabled_taxonomy_terms,
		'disabled_wpml_language_codes'               => $ai4seo_disabled_metadata_wpml_language_codes,
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
		'has_enough_credits'                         => ( $ai4seo_current_credits_balance >= $ai4seo_metadata_credits_costs_per_post ),
		'new_or_existing_filter'                     => $ai4seo_bulk_generation_new_or_existing_filter,
		'new_or_existing_filter_reference_timestamp' => $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp,
	)
);

if ( ! empty( $ai4seo_content_type_list_result['is_optimized'] ) ) {
	// The optimized resolver returns row IDs, counts, and current-page status membership as one coherent snapshot.
	$ai4seo_current_page                                   = (int) ( $ai4seo_content_type_list_result['current_page'] ?? $ai4seo_current_page );
	$ai4seo_current_page_post_ids                          = array_values( array_map( 'intval', (array) ( $ai4seo_content_type_list_result['post_ids'] ?? array() ) ) );
	$ai4seo_total_items                                    = (int) ( $ai4seo_content_type_list_result['total_items'] ?? 0 );
	$ai4seo_total_pages                                    = (int) ( $ai4seo_content_type_list_result['total_pages'] ?? 1 );
	$ai4seo_status_filter_counts                           = (array) ( $ai4seo_content_type_list_result['status_counts'] ?? array() );
	$ai4seo_sort_value_map                                 = (array) ( $ai4seo_content_type_list_result['sort_value_map'] ?? array() );
	$ai4seo_should_derive_current_page_metadata_status_ids = (bool) ( $ai4seo_content_type_list_result['should_derive_current_page_metadata_status_ids'] ?? false );
	$ai4seo_should_defer_status_filters                    = (bool) ( $ai4seo_content_type_list_result['should_defer_status_counts'] ?? false );
	$ai4seo_current_page_status_ids                        = (array) ( $ai4seo_content_type_list_result['current_page_status_ids'] ?? array() );
	$ai4seo_pending_metadata_post_ids                      = (array) ( $ai4seo_current_page_status_ids['queued'] ?? array() );
	$ai4seo_processing_metadata_post_ids                   = (array) ( $ai4seo_current_page_status_ids['processing'] ?? array() );
	$ai4seo_all_failed_metadata_post_ids                   = (array) ( $ai4seo_current_page_status_ids['failed'] ?? array() );
	$ai4seo_hidden_metadata_post_ids                       = (array) ( $ai4seo_current_page_status_ids['hidden'] ?? array() );
	$ai4seo_auto_queue_disallowed_metadata_post_ids        = (array) ( $ai4seo_current_page_status_ids['auto_queue_disallowed'] ?? array() );
	$ai4seo_missing_metadata_post_ids                      = (array) ( $ai4seo_current_page_status_ids['missing'] ?? array() );
	$ai4seo_fully_covered_metadata_post_ids                = (array) ( $ai4seo_current_page_status_ids['complete'] ?? array() );
	$ai4seo_generated_metadata_post_ids                    = (array) ( $ai4seo_current_page_status_ids['generated'] ?? array() );
} else {
	// Complex filters keep the legacy exact path so WPML/taxonomy/SEO-progress behavior remains unchanged.
	if ( '' !== $ai4seo_filter_context['filter_text'] ) {
		$ai4seo_filter_setup_arguments['defer_search_ids'] = false;
		$ai4seo_filter_context                             = ai4seo_setup_content_type_filters( $ai4seo_filter_setup_arguments );
		$ai4seo_filter_status                              = $ai4seo_filter_context['filter_status'];
		$ai4seo_filter_language                            = $ai4seo_filter_context['filter_language'];
		// Re-normalize after the legacy search setup rebuilds the filter context.
		$ai4seo_sort_args         = ai4seo_normalize_content_type_sort_args( $ai4seo_filter_context['orderby'] ?? 'id', $ai4seo_filter_context['order'] ?? 'desc' );
		$ai4seo_orderby           = $ai4seo_sort_args['orderby'];
		$ai4seo_order             = $ai4seo_sort_args['order'];
		$ai4seo_search_ids        = $ai4seo_filter_context['search_ids'];
		$ai4seo_filter_query_args = ai4seo_get_content_type_filter_query_args( $ai4seo_filter_context );
	}

	// The legacy path reads global status options because it must match the old all-candidate behavior exactly.
	$ai4seo_pending_metadata_post_ids               = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_processing_metadata_post_ids            = ai4seo_get_post_ids_from_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_all_failed_metadata_post_ids            = ai4seo_get_post_ids_from_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_hidden_metadata_post_ids                = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_auto_queue_disallowed_metadata_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_missing_metadata_post_ids               = ai4seo_get_post_ids_from_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_fully_covered_metadata_post_ids         = ai4seo_get_post_ids_from_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME );
	$ai4seo_generated_metadata_post_ids             = ai4seo_get_post_ids_from_option( AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME );

	// Search fallbacks can reuse precomputed search IDs; otherwise they intentionally use the old full candidate scan.
	if ( is_array( $ai4seo_search_ids ) ) {
		$ai4seo_candidate_post_ids = $ai4seo_search_ids;
	} else {
		$ai4seo_candidate_arguments                   = $ai4seo_posts_query_arguments;
		$ai4seo_candidate_arguments['fields']         = 'ids';
		$ai4seo_candidate_arguments['posts_per_page'] = -1;
		$ai4seo_candidate_arguments['no_found_rows']  = true;
		$ai4seo_candidate_arguments['lang']           = 'all';
		$ai4seo_candidate_post_ids                    = ai4seo_with_wpml_all_languages(
			function () use ( $ai4seo_candidate_arguments ) {
				return get_posts( $ai4seo_candidate_arguments );
			}
		);
	}

	$ai4seo_candidate_post_ids = array_values( array_unique( array_map( 'intval', (array) $ai4seo_candidate_post_ids ) ) );
	rsort( $ai4seo_candidate_post_ids, SORT_NUMERIC );
	$ai4seo_candidate_post_ids = ai4seo_filter_post_ids_by_disabled_taxonomy_terms( $ai4seo_candidate_post_ids, $ai4seo_disabled_taxonomy_terms );
	$ai4seo_candidate_post_ids = ai4seo_filter_post_ids_by_language( $ai4seo_candidate_post_ids, $ai4seo_filter_language );

	// Apply WPML active-language scope after the selected-language filter because both rely on per-entry WPML details.
	$ai4seo_candidate_post_ids         = ai4seo_filter_post_ids_by_disabled_wpml_languages( $ai4seo_candidate_post_ids, $ai4seo_disabled_metadata_wpml_language_codes );
	$ai4seo_visible_candidate_post_ids = array_values( array_diff( $ai4seo_candidate_post_ids, $ai4seo_hidden_metadata_post_ids ) );

	// Waiting-to-queue is derived after hidden/language/taxonomy filters because it depends on visible missing entries.
	$ai4seo_waiting_to_get_queued_metadata_post_ids = ai4seo_get_content_type_waiting_to_get_queued_post_ids(
		$ai4seo_visible_candidate_post_ids,
		$ai4seo_missing_metadata_post_ids,
		$ai4seo_pending_metadata_post_ids,
		$ai4seo_processing_metadata_post_ids,
		$ai4seo_all_failed_metadata_post_ids,
		array(
			'is_bulk_generation_activated'               => $ai4seo_is_bulk_generation_activated,
			'should_auto_queue_bulk_generation_entries'  => $ai4seo_should_auto_queue_bulk_generation_entries,
			'has_enough_credits'                         => ( $ai4seo_current_credits_balance >= $ai4seo_metadata_credits_costs_per_post ),
			'new_or_existing_filter'                     => $ai4seo_bulk_generation_new_or_existing_filter,
			'new_or_existing_filter_reference_timestamp' => $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp,
			'hidden_ids'                                 => $ai4seo_hidden_metadata_post_ids,
			'auto_queue_disallowed_ids'                  => $ai4seo_auto_queue_disallowed_metadata_post_ids,
		)
	);

	$ai4seo_search_status_map = array(
		'complete'         => $ai4seo_fully_covered_metadata_post_ids,
		'missing'          => $ai4seo_missing_metadata_post_ids,
		'waiting_to_queue' => $ai4seo_waiting_to_get_queued_metadata_post_ids,
		'queued'           => $ai4seo_pending_metadata_post_ids,
		'processing'       => $ai4seo_processing_metadata_post_ids,
		'failed'           => $ai4seo_all_failed_metadata_post_ids,
		'hidden'           => $ai4seo_hidden_metadata_post_ids,
	);

	// Legacy counters and row filtering share the same status map to preserve old tab behavior.
	$ai4seo_status_filter_counts = ai4seo_get_content_type_filter_status_counts(
		$ai4seo_candidate_post_ids,
		$ai4seo_search_status_map,
		$ai4seo_filter_context['status_options'],
		$ai4seo_hidden_metadata_post_ids
	);

	$ai4seo_filtered_candidate_post_ids = ai4seo_filter_post_ids_by_status( $ai4seo_candidate_post_ids, $ai4seo_filter_status, $ai4seo_search_status_map, $ai4seo_hidden_metadata_post_ids );
	$ai4seo_filtered_candidate_post_ids = array_values( array_unique( array_map( 'intval', $ai4seo_filtered_candidate_post_ids ) ) );
	$ai4seo_sort_value_map              = array();

	if ( 'title' === $ai4seo_orderby ) {
		$ai4seo_sort_value_map = ai4seo_get_content_type_post_title_map( $ai4seo_filtered_candidate_post_ids );
	} elseif ( 'seo_progress' === $ai4seo_orderby ) {
		$ai4seo_sort_value_map = ai4seo_read_percentage_of_available_metadata_by_post_ids( $ai4seo_filtered_candidate_post_ids );
	}

	$ai4seo_filtered_candidate_post_ids = ai4seo_sort_content_type_ids(
		$ai4seo_filtered_candidate_post_ids,
		$ai4seo_orderby,
		$ai4seo_order,
		$ai4seo_sort_value_map
	);

	$ai4seo_total_items = count( $ai4seo_filtered_candidate_post_ids );
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

	$ai4seo_offset                = ( $ai4seo_current_page - 1 ) * $ai4seo_items_per_page;
	$ai4seo_current_page_post_ids = array_slice( $ai4seo_filtered_candidate_post_ids, $ai4seo_offset, $ai4seo_items_per_page );
}

$ai4seo_all_posts = array();

// Fetch only the resolved current-page posts; both optimized and fallback paths provide IDs in display order.
if ( $ai4seo_current_page_post_ids ) {
	$ai4seo_posts_query_arguments['post__in']       = $ai4seo_current_page_post_ids;
	$ai4seo_posts_query_arguments['orderby']        = 'post__in';
	$ai4seo_posts_query_arguments['order']          = 'DESC';
	$ai4seo_posts_query_arguments['posts_per_page'] = count( $ai4seo_current_page_post_ids );

	$ai4seo_post_data = ai4seo_with_wpml_all_languages(
		function () use ( $ai4seo_posts_query_arguments ) {
			return new WP_Query( $ai4seo_posts_query_arguments );
		}
	);
	$ai4seo_all_posts = ( $ai4seo_post_data instanceof WP_Query ) ? ( $ai4seo_post_data->posts ?? array() ) : array();
	unset( $ai4seo_post_data );
}

// fetch all post ids from this page.
$ai4seo_current_post_ids = array_map(
	function ( $post ) {
		return (int) $post->ID;
	},
	$ai4seo_all_posts
);

$ai4seo_recent_metadata_activity_entries_by_post_id = ai4seo_get_latest_activity_entries_by_post_id(
	array(
		'metadata-bulk-generated',
	)
);

// get percentage of active metadata for all posts.
$ai4seo_percentage_of_active_metadata_by_post_ids = ( 'seo_progress' === $ai4seo_orderby )
	&& $ai4seo_sort_value_map
	? array_intersect_key( $ai4seo_sort_value_map, array_flip( $ai4seo_current_post_ids ) )
	: ai4seo_read_percentage_of_available_metadata_by_post_ids( $ai4seo_current_post_ids );

// Large all-lists avoid global coverage options and derive complete/missing state from the rendered page only.
if ( $ai4seo_should_derive_current_page_metadata_status_ids ) {
	$ai4seo_generated_metadata_post_ids       = ai4seo_read_post_ids_with_postmeta_key( $ai4seo_current_post_ids, AI4SEO_POST_META_GENERATED_DATA_META_KEY );
	$ai4seo_generated_metadata_post_id_lookup = array_flip( array_map( 'intval', $ai4seo_generated_metadata_post_ids ) );
	$ai4seo_fully_covered_metadata_post_ids   = array();
	$ai4seo_missing_metadata_post_ids         = array();

	foreach ( $ai4seo_current_post_ids as $ai4seo_this_current_post_id ) {
		$ai4seo_this_current_post_id           = (int) $ai4seo_this_current_post_id;
		$ai4seo_this_current_post_coverage     = (int) ( $ai4seo_percentage_of_active_metadata_by_post_ids[ $ai4seo_this_current_post_id ] ?? 0 );
		$ai4seo_this_current_post_is_generated = isset( $ai4seo_generated_metadata_post_id_lookup[ $ai4seo_this_current_post_id ] );

		if ( $ai4seo_this_current_post_coverage >= 100
			&& ( ! $ai4seo_do_generate_metadata_for_fully_covered_entries || $ai4seo_this_current_post_is_generated ) ) {
			$ai4seo_fully_covered_metadata_post_ids[] = $ai4seo_this_current_post_id;
			continue;
		}

		$ai4seo_missing_metadata_post_ids[] = $ai4seo_this_current_post_id;
	}
}

// read all key phrases for all posts in $ai4seo_this_page_post_ids.
$ai4seo_third_party_seo_plugin_key_phrases = ai4seo_read_third_party_seo_plugin_key_phrases( $ai4seo_current_post_ids );

// remove entries from $ai4seo_failed_to_fill_post_ids that are not on this page.
$ai4seo_current_page_failed_to_fill_post_ids = array();

if ( $ai4seo_all_posts ) {
	foreach ( $ai4seo_all_posts as $ai4seo_this_post ) {
		if ( in_array( $ai4seo_this_post->ID, $ai4seo_all_failed_metadata_post_ids ) ) {
			$ai4seo_current_page_failed_to_fill_post_ids[] = $ai4seo_this_post->ID;
		}
	}
}
$ai4seo_all_failed_metadata_post_ids = $ai4seo_current_page_failed_to_fill_post_ids;

// Retry-all visibility follows the same count-backed rule that makes the Failed status filter visible.
$ai4seo_should_show_retry_all_failed_metadata_generations_link = ai4seo_should_show_content_type_retry_all_failed_button( $ai4seo_filter_context['status_options'], $ai4seo_status_filter_counts );
$ai4seo_retry_all_failed_metadata_generations_container_class  = $ai4seo_should_show_retry_all_failed_metadata_generations_link ? '' : ' ai4seo-display-none';

$ai4seo_active_meta_tags                      = ai4seo_get_active_meta_tags();
$ai4seo_active_meta_tags_names                = ai4seo_get_active_meta_tags_names( $ai4seo_active_meta_tags );
$ai4seo_bulk_generation_queue_checkbox_name   = 'ai4seo_bulk_generation_queue_post_ids';
$ai4seo_bulk_generation_queue_action_controls = ai4seo_get_bulk_generation_queue_action_controls(
	AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
	$ai4seo_bulk_generation_queue_checkbox_name,
	$ai4seo_filter_status
);
$ai4seo_content_type_filter_controls_html     = ai4seo_get_content_type_filter_controls_html(
	$ai4seo_filter_context,
	$ai4seo_status_filter_counts,
	$ai4seo_total_items,
	$ai4seo_all_posts ? $ai4seo_bulk_generation_queue_action_controls : '',
	array(
		'defer_status_filters'           => $ai4seo_should_defer_status_filters,
		'content_context'                => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		'post_type'                      => $ai4seo_post_type,
		'retry_all_failed_button_target' => $ai4seo_retry_all_failed_metadata_generations_container_id,
	)
);


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === TABLE WITH ALL POSTS ================================================================== \\

ai4seo_echo_wp_kses( $ai4seo_content_type_filter_controls_html );

// Stop script if no posts have been found -> show message and stop page rendering.
if ( ! $ai4seo_all_posts ) {
	$ai4seo_remove_filters_button_html = ai4seo_get_content_type_remove_filters_button_html( $ai4seo_filter_context );

	echo '<p>';
		printf(
			/* translators: %s: post type plural name */
			esc_html__( 'No %s found.', 'ai-for-seo' ),
			esc_html( $ai4seo_translated_post_type_plural ),
		);

	if ( $ai4seo_remove_filters_button_html ) {
		echo ' ';
		ai4seo_echo_wp_kses( $ai4seo_remove_filters_button_html );
	}
	echo '</p>';

	return;
}

// Display table with entries.
// Resolve active ARIA states once so table headers and their direction-toggle links stay synchronized.
$ai4seo_id_column_aria_sort           = ai4seo_get_content_type_sortable_column_aria_sort_value( 'id', $ai4seo_filter_context );
$ai4seo_title_column_aria_sort        = ai4seo_get_content_type_sortable_column_aria_sort_value( 'title', $ai4seo_filter_context );
$ai4seo_seo_progress_column_aria_sort = ai4seo_get_content_type_sortable_column_aria_sort_value( 'seo_progress', $ai4seo_filter_context );

echo "<div class='ai4seo-posts-table-container'>";
echo "<table class='widefat striped table-view-list pages ai4seo-posts-table'>";
	echo '<tr>';
		echo "<th class='ai4seo-bulk-generation-queue-checkbox-column'>";
			ai4seo_echo_wp_kses( ai4seo_get_select_all_checkbox( $ai4seo_bulk_generation_queue_checkbox_name, '' ) );
		echo '</th>';
		echo "<th class='manage-column sortable ai4seo-content-list-sortable-column'" . ( $ai4seo_id_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_id_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'ID', 'ai-for-seo' ), 'id', $ai4seo_filter_context ) );
		echo '</th>';
		echo "<th class='manage-column sortable ai4seo-hidden-on-mobile ai4seo-content-list-sortable-column'" . ( $ai4seo_title_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_title_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'Title and key phrase', 'ai-for-seo' ), 'title', $ai4seo_filter_context ) );
		echo '</th>';
		echo "<th class='manage-column sortable ai4seo-content-list-sortable-column'" . ( $ai4seo_seo_progress_column_aria_sort ? " aria-sort='" . esc_attr( $ai4seo_seo_progress_column_aria_sort ) . "'" : '' ) . '>';
			ai4seo_echo_wp_kses( ai4seo_get_content_type_sortable_column_label_html( __( 'Metadata coverage', 'ai-for-seo' ), 'seo_progress', $ai4seo_filter_context ) );

if ( $ai4seo_active_meta_tags_names ) {
	echo " <span class='ai4seo-content-list-active-fields-note'>(" . esc_html( implode( ', ', $ai4seo_active_meta_tags_names ) ) . ')</span>';
}

			echo "<span class='ai4seo-visible-on-mobile'> / ";
				echo esc_html__( 'Title and key phrase', 'ai-for-seo' );
			echo '</span>';

			// Keep the global retry action aligned with the Failed status filter, including deferred AJAX hydration.
if ( $ai4seo_should_show_retry_all_failed_metadata_generations_link || $ai4seo_should_defer_status_filters ) {
	echo '<div'
		. " id='" . esc_attr( $ai4seo_retry_all_failed_metadata_generations_container_id ) . "'"
		. " class='ai4seo-table-title-button ai4seo-content-list-retry-all-failed-button" . esc_attr( $ai4seo_retry_all_failed_metadata_generations_container_class ) . "'"
		. " data-ai4seo-content-context='" . esc_attr( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ) . "'"
		. " data-ai4seo-post-type='" . esc_attr( $ai4seo_post_type ) . "'"
		. '>';
	ai4seo_echo_wp_kses( $ai4seo_retry_all_failed_metadata_generations_link_tag );
	echo '</div>';
}
		echo '</th>';
		echo '<th></th>';
	echo '</tr>';

	// Loop through all posts.
foreach ( $ai4seo_all_posts as $ai4seo_this_post ) {
	// Get post-ID.
	$ai4seo_this_post_id = $ai4seo_this_post->ID;

	// Get post-title.
	$ai4seo_single_post_title               = $ai4seo_this_post->post_title;
	$ai4seo_this_post_language              = ai4seo_try_get_post_language_by_checking_multilanguage_plugins( $ai4seo_this_post_id );
	$ai4seo_single_post_title_with_language = $ai4seo_single_post_title;

	if ( '' !== $ai4seo_this_post_language ) {
		$ai4seo_single_post_title_with_language .= ' (' . $ai4seo_this_post_language . ')';
	}

	// Get post-link.
	$ai4seo_single_post_link = get_permalink( $ai4seo_this_post_id );

	$ai4seo_this_post_date_gmt     = (string) ( $ai4seo_this_post->post_date_gmt ?? '' );
	$ai4seo_this_post_date         = (string) ( $ai4seo_this_post->post_date ?? '' );
	$ai4seo_this_post_modified_gmt = (string) ( $ai4seo_this_post->post_modified_gmt ?? '' );
	$ai4seo_this_post_modified     = (string) ( $ai4seo_this_post->post_modified ?? '' );

	// get timestamp of post date.
	$ai4seo_this_post_date_timestamp = strtotime( $ai4seo_this_post_date_gmt . ' UTC' );
	$ai4seo_this_post_date_display   = $ai4seo_this_post_date;

	$ai4seo_this_post_date_local_timestamp = strtotime( $ai4seo_this_post_date );
	if ( $ai4seo_this_post_date_local_timestamp ) {
		$ai4seo_this_post_date_display = ai4seo_format_unix_timestamp( $ai4seo_this_post_date_local_timestamp );
	}

	// check if the new-or-existing filter complies with this post ("both" -> yes, "new" -> only posts with post_date_timestamp > reference_timestamp, "existing" -> only posts with post_date_timestamp <= reference_timestamp).
	$ai4seo_is_excluded_by_new_or_existing_filter = false;
	if ( 'new' === $ai4seo_bulk_generation_new_or_existing_filter && $ai4seo_this_post_date_timestamp <= $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp ) {
		$ai4seo_is_excluded_by_new_or_existing_filter = true;
	} elseif ( 'existing' === $ai4seo_bulk_generation_new_or_existing_filter && $ai4seo_this_post_date_timestamp > $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp ) {
		$ai4seo_is_excluded_by_new_or_existing_filter = true;
	}

	// this post meta coverage summary.
	if ( $ai4seo_active_meta_tags ) {
		$ai4seo_this_active_metadata_coverage_percentage = $ai4seo_percentage_of_active_metadata_by_post_ids[ $ai4seo_this_post_id ] ?? 0;
	} else {
		$ai4seo_this_active_metadata_coverage_percentage = 100;
	}

	$ai4seo_this_post_is_fully_covered               = in_array( $ai4seo_this_post_id, $ai4seo_fully_covered_metadata_post_ids );
	$ai4seo_this_metadata_generation_is_not_finished = ! $ai4seo_this_post_is_fully_covered;
	$ai4seo_this_post_is_generated                   = in_array( $ai4seo_this_post_id, $ai4seo_generated_metadata_post_ids );
	$ai4seo_this_post_sub_info_rows                  = array();
	$ai4seo_this_post_is_failed_to_fill              = in_array( $ai4seo_this_post_id, $ai4seo_current_page_failed_to_fill_post_ids );
	$ai4seo_this_post_has_recent_activity            = isset( $ai4seo_recent_metadata_activity_entries_by_post_id[ $ai4seo_this_post_id ] );
	$ai4seo_this_recent_activity_details_subtext_tag = '';

	// Recent activity entries should link directly to the editor where details can be inspected.
	if ( $ai4seo_this_post_has_recent_activity ) {
		$ai4seo_this_recent_activity_details_onclick = 'ai4seo_open_metadata_editor_modal(' . absint( $ai4seo_this_post_id ) . ', false';

		if ( $ai4seo_current_post_ids ) {
			$ai4seo_this_recent_activity_details_onclick .= ', ' . wp_json_encode( array_values( array_map( 'intval', $ai4seo_current_post_ids ) ) );
		}

		$ai4seo_this_recent_activity_details_onclick    .= ');';
		$ai4seo_this_recent_activity_details_subtext_tag = ai4seo_get_recent_activity_details_subtext_tag(
			$ai4seo_this_recent_activity_details_onclick,
			$ai4seo_recent_metadata_activity_entries_by_post_id[ $ai4seo_this_post_id ]
		);
	}

	$ai4seo_this_post_timestamp_tooltip = sprintf(
		/* translators: 1: post_date_gmt, 2: post_date, 3: post_modified_gmt, 4: post_modified */
		__( "post_date_gmt: %1\$s\npost_date: %2\$s\npost_modified_gmt: %3\$s\npost_modified: %4\$s", 'ai-for-seo' ),
		$ai4seo_this_post_date_gmt ?: '-',
		$ai4seo_this_post_date ?: '-',
		$ai4seo_this_post_modified_gmt ?: '-',
		$ai4seo_this_post_modified ?: '-'
	);

	$ai4seo_this_post_sub_info_rows[] = "<span class='ai4seo-attachment-upload-timestamp' title='" . esc_attr( $ai4seo_this_post_timestamp_tooltip ) . "'>" .
		sprintf(
			/* translators: %s: publish timestamp */
			esc_html__( 'Publish time: %s', 'ai-for-seo' ),
			esc_html( $ai4seo_this_post_date_display )
		) .
	'</span>';

	$ai4seo_this_post_coverage_suffix = $ai4seo_this_post_is_fully_covered ? ' ' . esc_html__( '(completed)', 'ai-for-seo' ) : '';
	$ai4seo_this_post_sub_info_rows[] =
		sprintf(
			/* translators: 1: coverage percentage, 2: optional completed note */
			esc_html__( 'Coverage: %1$s%%%2$s', 'ai-for-seo' ),
			esc_html( ai4seo_stringify( $ai4seo_this_active_metadata_coverage_percentage ) ),
			$ai4seo_this_post_coverage_suffix
		);

	$ai4seo_this_post_sub_info_rows[] =
		(
			$ai4seo_this_post_is_generated
				? sprintf(
					/* translators: %s: plugin name */
					esc_html__( '%s has generated data for this entry.', 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME )
				)
				: sprintf(
					/* translators: %s: plugin name */
					__( "%s has <span class='ai4seo-text-underlined'>not</span> generated data for this entry yet.", 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME )
				)
		);

	$ai4seo_this_post_sub_info_html = "<div class='ai4seo-sub-info'>" . implode( ' &bull; ', $ai4seo_this_post_sub_info_rows ) . '</div>';

	// Check queue state separately, so Pending and Processing can have different visuals.
	$ai4seo_is_post_missing                        = in_array( $ai4seo_this_post_id, $ai4seo_missing_metadata_post_ids, true );
	$ai4seo_is_post_pending                        = in_array( $ai4seo_this_post_id, $ai4seo_pending_metadata_post_ids, true );
	$ai4seo_is_post_processing                     = in_array( $ai4seo_this_post_id, $ai4seo_processing_metadata_post_ids, true );
	$ai4seo_is_post_auto_queue_disallowed          = in_array( $ai4seo_this_post_id, $ai4seo_auto_queue_disallowed_metadata_post_ids, true );
	$ai4seo_is_post_waiting_to_get_queued          = false;
	$ai4seo_is_insufficient_credits                = false;
	$ai4seo_should_show_auto_queue_disallowed_note = false;

	// Failed entries should keep their failed state even if stale queue data exists.
	if ( $ai4seo_this_post_is_failed_to_fill ) {
		$ai4seo_is_post_pending    = false;
		$ai4seo_is_post_processing = false;
	}

	// Missing entries are waiting for SEO Autopilot excavation only when auto queueing can actually pick them up.
	$ai4seo_can_post_get_auto_queued = $ai4seo_is_bulk_generation_activated
		&& $ai4seo_should_auto_queue_bulk_generation_entries
		&& $ai4seo_is_post_missing
		&& ! $ai4seo_is_post_pending
		&& ! $ai4seo_is_post_processing
		&& ! $ai4seo_this_post_is_failed_to_fill
		&& ! $ai4seo_is_post_auto_queue_disallowed
		&& ! $ai4seo_is_excluded_by_new_or_existing_filter
		&& ( $ai4seo_this_metadata_generation_is_not_finished || ( ! $ai4seo_this_post_is_generated && $ai4seo_do_generate_metadata_for_fully_covered_entries ) );

	if ( $ai4seo_can_post_get_auto_queued ) {
		if ( $ai4seo_current_credits_balance < $ai4seo_metadata_credits_costs_per_post ) {
			$ai4seo_is_insufficient_credits = true;
		} else {
			$ai4seo_is_post_waiting_to_get_queued = true;
		}
	}

	// Display table-row for this post.
	echo '<tr>';
		// Use the shared renderer so Posts and Pages receive identical labeled bulk-selection markup.
		echo "<td class='ai4seo-bulk-generation-queue-checkbox-column'>";
			ai4seo_echo_wp_kses(
				ai4seo_get_bulk_generation_queue_entry_checkbox(
					$ai4seo_bulk_generation_queue_checkbox_name,
					$ai4seo_this_post_id,
					$ai4seo_single_post_title_with_language
				)
			);
		echo '</td>';

		// Post-ID.
		echo '<td>';
			echo esc_html( $ai4seo_this_post_id );
		echo '</td>';

		// Post-Title.
		echo "<td class='title column-title has-row-actions column-primary post-title ai4seo-hidden-on-mobile'>";
			echo '<strong>';
				echo "<a href='" . esc_attr( $ai4seo_single_post_link ) . "' target='_blank' class='ai4seo-table-content-link'>";
					echo esc_html( $ai4seo_single_post_title_with_language );
				echo '</a>';

	if ( isset( $ai4seo_third_party_seo_plugin_key_phrases[ $ai4seo_this_post_id ] ) ) {
		echo " <span class='ai4seo-key-phrase'>(" . esc_html( $ai4seo_third_party_seo_plugin_key_phrases[ $ai4seo_this_post_id ] ) . ')</span>';
	}
			echo '</strong>';
			ai4seo_echo_wp_kses( $ai4seo_this_post_sub_info_html );
		echo '</td>';

		// Generation Coverage.
		echo "<td class='ai4seo-generation-coverage'>";
	if ( $ai4seo_active_meta_tags ) {
		$ai4seo_progress_bar_animation_class           = '';
		$ai4seo_should_show_auto_queue_disallowed_note = $ai4seo_is_bulk_generation_activated
			&& $ai4seo_should_auto_queue_bulk_generation_entries
			&& $ai4seo_is_post_auto_queue_disallowed
			&& $ai4seo_this_metadata_generation_is_not_finished
			&& ! $ai4seo_is_post_pending
			&& ! $ai4seo_is_post_processing;
		$ai4seo_should_show_autopilot_pending_note     = $ai4seo_is_post_pending
			&& ! $ai4seo_is_post_processing;

		if ( $ai4seo_is_post_processing ) {
			$ai4seo_progress_bar_animation_class = ' ai4seo-green-animated-progress-bar';
		}

		// Reuse the list-wide progress helper so post and attachment rows expose identical accessibility metadata.
		echo ai4seo_get_seo_coverage_progress_bar_tag(
			$ai4seo_this_post_id,
			$ai4seo_this_active_metadata_coverage_percentage,
			$ai4seo_progress_bar_animation_class,
			$ai4seo_this_metadata_generation_is_not_finished
		);

		if ( $ai4seo_is_post_waiting_to_get_queued ) {
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
		} elseif ( $ai4seo_is_post_processing ) {
			echo "<div class='ai4seo-sub-info'>";
				echo esc_html__( 'This entry is currently being processed.', 'ai-for-seo' );
			echo '</div>';
		} elseif ( $ai4seo_is_insufficient_credits ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
				echo esc_html__( 'Insufficient Credits', 'ai-for-seo' ) . '.';
				echo ' ';
				ai4seo_echo_wp_kses( $ai4seo_consider_purchasing_more_credits_link_tag );
			echo '</div>';
		} elseif ( $ai4seo_this_post_is_failed_to_fill && $ai4seo_this_metadata_generation_is_not_finished ) {
			echo "<div class='ai4seo-seo-data-not-covered-message'>";
				echo '<span>' . esc_html__( 'Failed to automatically fill metadata.', 'ai-for-seo' ) . '</span>';
				echo ' ';
				ai4seo_echo_wp_kses( ai4seo_get_small_icon_button_tag( 'arrow-up-right-from-square', __( 'Try it manually', 'ai-for-seo' ), '', 'ai4seo_open_metadata_editor_modal("' . esc_js( $ai4seo_this_post_id ) . '");' ) );
			echo '</div>';
		} elseif ( $ai4seo_is_excluded_by_new_or_existing_filter && $ai4seo_this_metadata_generation_is_not_finished ) {
			$ai4seo_new_or_existing_filter_reference_timestamp_formatted = ai4seo_format_unix_timestamp( $ai4seo_bulk_generation_new_or_existing_filter_reference_timestamp );
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			if ( 'new' === $ai4seo_bulk_generation_new_or_existing_filter ) {
					printf(
						/* translators: %s: reference timestamp */
						esc_html__( "Excluded by 'New or existing entries' filter: Published before %s.", 'ai-for-seo' ),
						esc_html( $ai4seo_new_or_existing_filter_reference_timestamp_formatted )
					);
			} elseif ( 'existing' === $ai4seo_bulk_generation_new_or_existing_filter ) {
						printf(
							/* translators: %s: reference timestamp */
							esc_html__( "Excluded by 'New or existing entries' filter: Published after %s.", 'ai-for-seo' ),
							esc_html( $ai4seo_new_or_existing_filter_reference_timestamp_formatted )
						);
			} else {
				echo esc_html__( "Excluded by 'New or existing entries' filter.", 'ai-for-seo' );
			}
							echo '</div>';
		} elseif ( $ai4seo_this_post_is_fully_covered && ! $ai4seo_this_post_is_generated && ! $ai4seo_do_generate_metadata_for_fully_covered_entries ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
				echo esc_html__( 'Excluded because all metadata is filled, and the setting "SEO Autopilot: Include Complete Entries When Overwriting" is disabled, or no fields are allowed to be overwritten.', 'ai-for-seo' );
			echo '</div>';
		}

		if ( $ai4seo_should_show_auto_queue_disallowed_note ) {
			echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
				echo esc_html__( 'Auto Queue will ignore this entry because it was excluded with a bulk action.', 'ai-for-seo' );
			echo '</div>';
		}

		// Title display for mobile version.
		echo "<div class='ai4seo-visible-on-mobile'>";
			echo '<strong>';
				echo "<a href='" . esc_attr( $ai4seo_single_post_link ) . "' target='_blank' class='ai4seo-table-content-link'>";
					echo esc_html( $ai4seo_single_post_title_with_language );
				echo '</a>';

		if ( isset( $ai4seo_third_party_seo_plugin_key_phrases[ $ai4seo_this_post_id ] ) ) {
			echo " <span class='ai4seo-key-phrase'>(" . esc_html( $ai4seo_third_party_seo_plugin_key_phrases[ $ai4seo_this_post_id ] ) . ')</span>';
		}
			echo '</strong>';
			ai4seo_echo_wp_kses( $ai4seo_this_post_sub_info_html );
			echo '</div>';
	} else {
		echo "<div class='ai4seo-sub-info ai4seo-red-message'>";
			echo esc_html__( 'No active meta tags.', 'ai-for-seo' );
		echo '</div>';
	}

	if ( $ai4seo_this_recent_activity_details_subtext_tag ) {
		ai4seo_echo_wp_kses( $ai4seo_this_recent_activity_details_subtext_tag );
	}
			echo '</td>';

			// Post-Edit-Link.
			echo '<td>';
			// Keep metadata and related-media row actions in the shared button wrapper so options stay on one line.
			echo "<div class='ai4seo-buttons-wrapper ai4seo-row-action-buttons'>";
				// Edit-Link.
	if ( $ai4seo_active_meta_tags ) {
		ai4seo_echo_wp_kses( ai4seo_get_edit_metadata_button( $ai4seo_this_post_id, $ai4seo_current_post_ids ) );
	}

				ai4seo_echo_wp_kses( ai4seo_get_related_attachments_button( $ai4seo_this_post_id ) );
				echo '</div>';
				echo '</td>';
				echo '</tr>';
}
echo '</table>';
echo '</div>';

// Pagination.
$ai4seo_pagination_base_argument = add_query_arg(
	array(
		'page'             => AI4SEO_PLUGIN_IDENTIFIER,
		'ai4seo_subpage'   => AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME, // e.g. 'post'.
		'ai4seo_post_type' => $ai4seo_post_type,
		'ai4seo_page'      => '%#%', // placeholder for paginate_links().
	),
	admin_url( 'admin.php' )
);

$ai4seo_total_pages              = max( 1, $ai4seo_total_pages );
$ai4seo_current_page             = max( 1, $ai4seo_current_page );
$ai4seo_pagination_base_argument = $ai4seo_pagination_base_argument ?: '%_%'; // Default base if not defined.
$ai4seo_pagination_arguments     = array(
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
