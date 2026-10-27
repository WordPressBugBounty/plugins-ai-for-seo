<?php
/**
 * Taxonomy inventory, metadata editing and generation queue controls.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ai4seo_metadata_field_descriptors = ai4seo_get_generation_field_descriptors( 'metadata' );

/**
 * Selected route inherited from includes/menu-frame.php.
 *
 * @var string $ai4seo_current_taxonomy
 */
if ( ! ai4seo_can_use_plugin_content() || ! isset( $ai4seo_menu_registry['taxonomies'][ $ai4seo_current_taxonomy ] ) ) {
	echo '<p>' . esc_html__( 'You are not allowed to access this taxonomy.', 'ai-for-seo' ) . '</p>';
	return;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters under the content-role and taxonomy-capability gates.
$ai4seo_taxonomy_availability = isset( $_GET['ai4seo_taxonomy_availability'] ) && is_string( $_GET['ai4seo_taxonomy_availability'] ) ? sanitize_key( wp_unslash( $_GET['ai4seo_taxonomy_availability'] ) ) : 'active';
$ai4seo_taxonomy_search       = isset( $_GET['ai4seo_search'] ) && is_string( $_GET['ai4seo_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ai4seo_search'] ) ) : '';
$ai4seo_taxonomy_page         = isset( $_GET['ai4seo_page'] ) && is_scalar( $_GET['ai4seo_page'] ) ? max( 1, absint( $_GET['ai4seo_page'] ) ) : 1;
$ai4seo_taxonomy_missing_only = isset( $_GET['ai4seo_filter_status'] ) && 'missing' === $_GET['ai4seo_filter_status'];
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$ai4seo_taxonomy_list = ai4seo_read_taxonomy_proxy_list_page( $ai4seo_current_taxonomy, $ai4seo_taxonomy_availability, $ai4seo_taxonomy_search, $ai4seo_taxonomy_page, $ai4seo_taxonomy_missing_only );
if ( is_wp_error( $ai4seo_taxonomy_list ) ) {
	echo '<p class="ai4seo-error-message" role="alert">' . esc_html__( 'The taxonomy inventory could not be loaded. Refresh the page and try again.', 'ai-for-seo' ) . '</p>';
	return;
}

// Reuse the post/media control layout while retaining the taxonomy-specific route and filters.
echo '<div class="ai4seo-filter-bar ai4seo-content-list-controls"><div class="ai4seo-content-list-controls__top">';
echo '<div class="ai4seo-content-list-controls__views"><ul class="subsubsub ai4seo-content-list-status-filters">';
foreach ( array(
	'active'   => __( 'Active', 'ai-for-seo' ),
	'inactive' => __( 'Inactive', 'ai-for-seo' ),
	'all'      => __( 'All', 'ai-for-seo' ),
) as $ai4seo_filter_value => $ai4seo_filter_label ) {
	$ai4seo_filter_url = ai4seo_get_post_type_page_url(
		AI4SEO_TAXONOMY_PROXY_POST_TYPE,
		1,
		array(
			'ai4seo_taxonomy'              => $ai4seo_current_taxonomy,
			'ai4seo_taxonomy_availability' => $ai4seo_filter_value,
			'ai4seo_search'                => $ai4seo_taxonomy_search,
			'ai4seo_filter_status'         => $ai4seo_taxonomy_missing_only ? 'missing' : 'all',
		)
	);
	echo '<li>' . ( 'active' !== $ai4seo_filter_value ? ' | ' : '' ) . '<a href="' . esc_url( $ai4seo_filter_url ) . '"'
		. ( $ai4seo_taxonomy_availability === $ai4seo_filter_value ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $ai4seo_filter_label ) . '</a></li>';
}
if ( $ai4seo_taxonomy_missing_only ) {
	echo '<li> | <span>' . esc_html__( 'Missing fields', 'ai-for-seo' ) . '</span></li>';
}
echo '</ul></div><div class="ai4seo-content-list-controls__search">';
echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" class="ai4seo-content-list-search-form search-box">';
foreach ( array(
	'page'                         => AI4SEO_PLUGIN_IDENTIFIER,
	'ai4seo_subpage'               => 'post',
	'ai4seo_post_type'             => AI4SEO_TAXONOMY_PROXY_POST_TYPE,
	'ai4seo_taxonomy'              => $ai4seo_current_taxonomy,
	'ai4seo_taxonomy_availability' => $ai4seo_taxonomy_availability,
	'ai4seo_filter_status'         => $ai4seo_taxonomy_missing_only ? 'missing' : 'all',
) as $ai4seo_filter_name => $ai4seo_filter_value ) {
	echo '<input type="hidden" name="' . esc_attr( $ai4seo_filter_name ) . '" value="' . esc_attr( $ai4seo_filter_value ) . '" />';
}
echo '<label class="screen-reader-text" for="ai4seo-taxonomy-search">' . esc_html__( 'Search name or description', 'ai-for-seo' ) . '</label>';
echo '<input class="ai4seo-textfield" type="search" id="ai4seo-taxonomy-search" name="ai4seo_search" value="' . esc_attr( $ai4seo_taxonomy_search ) . '" placeholder="' . esc_attr__( 'Search name or description', 'ai-for-seo' ) . '" />';
echo '<button type="submit" class="ai4seo-button ai4seo-content-list-search-submit">' . esc_html__( 'Search', 'ai-for-seo' ) . '</button>';
if ( '' !== $ai4seo_taxonomy_search || $ai4seo_taxonomy_missing_only ) {
	$ai4seo_reset_url = ai4seo_get_post_type_page_url(
		AI4SEO_TAXONOMY_PROXY_POST_TYPE,
		1,
		array(
			'ai4seo_taxonomy'              => $ai4seo_current_taxonomy,
			'ai4seo_taxonomy_availability' => $ai4seo_taxonomy_availability,
		)
	);
	ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( $ai4seo_reset_url, '', '', '', __( 'Reset', 'ai-for-seo' ), 'ai4seo-abort-button ai4seo-content-list-search-reset-button' ) );
}
echo '</form></div></div><div class="tablenav top ai4seo-content-list-controls__bulk">';
if ( $ai4seo_taxonomy_list['rows'] ) {
	echo '<div class="alignleft actions bulkactions">';
	ai4seo_echo_wp_kses( ai4seo_get_bulk_generation_queue_action_controls( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, 'ai4seo_bulk_generation_queue_post_ids', 'all', array( 'list_location' => 'taxonomy' ) ) );
	echo '</div>';
}
echo '<div class="tablenav-pages one-page ai4seo-content-list-controls__count"><span class="displaying-num ai4seo-content-list-total-count">';
echo esc_html(
	$ai4seo_taxonomy_list['has_errors'] ? __( 'Entry count unavailable', 'ai-for-seo' ) : sprintf(
		/* translators: %s: Number of entries matching the current filters. */
		_n( '%s entry', '%s entries', $ai4seo_taxonomy_list['total'], 'ai-for-seo' ),
		ai4seo_format_number_i18n( $ai4seo_taxonomy_list['total'] )
	)
);
echo '</span></div></div></div>';

if ( $ai4seo_taxonomy_list['index_pending'] ) {
	echo '<p role="status">' . esc_html__( 'Some taxonomy entries are still waiting for synchronization. They will appear after a scheduled update; reload this page afterwards. If this message persists, check that WordPress scheduled tasks are running.', 'ai-for-seo' ) . '</p>';
}
if ( $ai4seo_taxonomy_list['has_errors'] ) {
	echo '<p class="ai4seo-error-message" role="alert">' . esc_html__( 'The taxonomy inventory could not be fully verified. Displayed entries may be incomplete; reopen the list after synchronization.', 'ai-for-seo' ) . '</p>';
}

$ai4seo_taxonomy_ids               = array_column( $ai4seo_taxonomy_list['rows'], 'post_id' );
$ai4seo_taxonomy_page_inspections  = array();
$ai4seo_taxonomy_generation_status = ai4seo_read_taxonomy_generation_status_summary( $ai4seo_current_taxonomy, false, $ai4seo_taxonomy_page_inspections, $ai4seo_taxonomy_ids );
$ai4seo_taxonomy_field_states      = array();
$ai4seo_taxonomy_issues            = array();
$ai4seo_taxonomy_coverage          = ai4seo_read_percentage_of_available_metadata_by_post_ids( $ai4seo_taxonomy_ids, 0, $ai4seo_taxonomy_metadata_known, $ai4seo_taxonomy_field_states, $ai4seo_taxonomy_issues );
$ai4seo_taxonomy_fields            = ai4seo_get_active_meta_tags();
$ai4seo_taxonomy_field_names       = array();
foreach ( $ai4seo_metadata_field_descriptors as $ai4seo_field_identifier => $ai4seo_field_details ) {
	if ( in_array( $ai4seo_field_identifier, $ai4seo_taxonomy_fields, true ) && isset( $ai4seo_field_details['name'] ) ) {
		$ai4seo_taxonomy_field_names[ $ai4seo_field_identifier ] = $ai4seo_field_details['name'];
	}
}
echo '<div class="ai4seo-posts-table-container ai4seo-taxonomy-table-wrapper"><table class="widefat striped table-view-list ai4seo-posts-table ai4seo-taxonomy-table"><thead><tr>';
echo '<th scope="col" class="ai4seo-bulk-generation-queue-checkbox-column">';
ai4seo_echo_wp_kses( ai4seo_get_select_all_checkbox( 'ai4seo_bulk_generation_queue_post_ids', '' ) );
echo '</th>';
foreach ( array( __( 'Name', 'ai-for-seo' ), __( 'Parent', 'ai-for-seo' ), __( 'Content count', 'ai-for-seo' ), __( 'Metadata coverage', 'ai-for-seo' ), __( 'Actions', 'ai-for-seo' ) ) as $ai4seo_column_label ) {
	echo '<th scope="col">' . esc_html( $ai4seo_column_label ) . '</th>';
}
echo '</tr></thead><tbody>';
foreach ( $ai4seo_taxonomy_list['rows'] as $ai4seo_taxonomy_row ) {
	$ai4seo_id         = $ai4seo_taxonomy_row['post_id'];
	$ai4seo_parent     = $ai4seo_taxonomy_row['source']['parent'] && $ai4seo_taxonomy_row['taxonomy'] ? get_term( $ai4seo_taxonomy_row['source']['parent'], $ai4seo_current_taxonomy ) : null;
	$ai4seo_percentage = $ai4seo_taxonomy_coverage[ $ai4seo_id ] ?? null;
	$ai4seo_known      = $ai4seo_taxonomy_metadata_known && null !== $ai4seo_percentage;
	echo '<tr><td class="ai4seo-bulk-generation-queue-checkbox-column">';
	if ( ai4seo_can_edit_taxonomy_proxy( $ai4seo_id ) ) {
		ai4seo_echo_wp_kses( ai4seo_get_bulk_generation_queue_entry_checkbox( 'ai4seo_bulk_generation_queue_post_ids', $ai4seo_id, $ai4seo_taxonomy_row['name'], $ai4seo_known ? '' : __( 'Stored metadata is unavailable.', 'ai-for-seo' ) ) );
	}
	echo '</td><td><strong>';
	if ( $ai4seo_taxonomy_row['archive_url'] ) {
		echo '<a href="' . esc_url( $ai4seo_taxonomy_row['archive_url'] ) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Open archive (opens in a new tab)', 'ai-for-seo' ) . '">' . esc_html( $ai4seo_taxonomy_row['name'] ) . '</a>';
	} else {
		echo esc_html( $ai4seo_taxonomy_row['name'] );
	}
	echo '</strong>';
	if ( ! $ai4seo_taxonomy_row['available'] ) {
		echo ' <span class="ai4seo-bubble" title="' . esc_attr__( 'Inactive — read only', 'ai-for-seo' ) . '">' . esc_html__( 'Inactive', 'ai-for-seo' ) . '</span>';
	}
	echo '<br><span class="ai4seo-sub-info">' . esc_html( $ai4seo_taxonomy_row['source']['slug'] ) . '</span></td>';
	echo '<td>' . esc_html( $ai4seo_parent && ! is_wp_error( $ai4seo_parent ) ? $ai4seo_parent->name : '—' ) . '</td>';
	echo '<td>' . esc_html( number_format_i18n( $ai4seo_taxonomy_row['source']['count'] ) ) . '</td>';
	echo '<td class="ai4seo-generation-coverage">';
	if ( $ai4seo_taxonomy_fields ) {
		// The shared reader excludes taxonomy proxies from third-party providers and preserves unknown coverage.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared coverage renderer escapes dynamic output, including field details and meter attributes.
		echo ai4seo_get_seo_coverage_progress_bar_tag(
			$ai4seo_id,
			$ai4seo_percentage,
			'',
			null === $ai4seo_percentage || $ai4seo_percentage < 100,
			$ai4seo_taxonomy_row['label'],
			$ai4seo_taxonomy_field_names,
			$ai4seo_taxonomy_field_states[ $ai4seo_id ] ?? array()
		);
	} else {
		echo '<div class="ai4seo-sub-info ai4seo-coverage-status">' . esc_html__( 'No active meta tags.', 'ai-for-seo' ) . '</div>';
	}
	if ( is_wp_error( $ai4seo_taxonomy_generation_status ) ) {
		echo '<div class="ai4seo-sub-info ai4seo-coverage-status">' . esc_html__( 'Status unavailable', 'ai-for-seo' ) . '</div>';
	} else {
		foreach ( array(
			'processing'            => __( 'Processing', 'ai-for-seo' ),
			'pending'               => __( 'Queued', 'ai-for-seo' ),
			'failed'                => __( 'Needs review', 'ai-for-seo' ),
			'auto_queue_disallowed' => __( 'Automatic queueing excluded', 'ai-for-seo' ),
		) as $ai4seo_generation_status_key => $ai4seo_generation_status_label ) {
			if ( in_array( $ai4seo_id, $ai4seo_taxonomy_generation_status[ $ai4seo_generation_status_key ], true ) ) {
				echo '<div class="ai4seo-sub-info ai4seo-coverage-status">' . esc_html( $ai4seo_generation_status_label ) . '</div>';
				break;
			}
		}
	}
	if ( $ai4seo_taxonomy_row['available'] ) {
		$ai4seo_generation_context = ai4seo_get_taxonomy_generation_eligibility( $ai4seo_id );
		if ( is_wp_error( $ai4seo_generation_context ) ) {
			echo '<div class="ai4seo-sub-info ai4seo-coverage-status">' . esc_html__( 'Content could not be read', 'ai-for-seo' ) . '</div>';
		} elseif ( ! $ai4seo_generation_context['has_sufficient_content'] ) {
			echo '<div class="ai4seo-sub-info ai4seo-coverage-status">' . esc_html__( 'Too little content for automatic generation', 'ai-for-seo' ) . '</div>';
		}
	}
	echo '</td><td><div class="ai4seo-row-action-buttons">';
	ai4seo_echo_wp_kses( ai4seo_get_edit_metadata_button( $ai4seo_id, $ai4seo_taxonomy_ids, $ai4seo_taxonomy_row['label'], $ai4seo_taxonomy_row['available'] && $ai4seo_known && $ai4seo_percentage < 100 ) );
	ai4seo_echo_wp_kses( ai4seo_get_wordpress_post_edit_link_button( $ai4seo_id ) );
	echo '</div></td></tr>';
}
if ( ! $ai4seo_taxonomy_list['rows'] && ! $ai4seo_taxonomy_list['has_errors'] ) {
	echo '<tr><td colspan="6">' . esc_html__( 'No entries match these filters.', 'ai-for-seo' ) . '</td></tr>';
}
echo '</tbody></table></div>';
$ai4seo_taxonomy_pagination = paginate_links(
	array(
		'base'    => ai4seo_get_post_type_page_url(
			AI4SEO_TAXONOMY_PROXY_POST_TYPE,
			0,
			array(
				'ai4seo_taxonomy'              => $ai4seo_current_taxonomy,
				'ai4seo_taxonomy_availability' => $ai4seo_taxonomy_availability,
				'ai4seo_search'                => $ai4seo_taxonomy_search,
				'ai4seo_filter_status'         => $ai4seo_taxonomy_missing_only ? 'missing' : 'all',
			)
		),
		'total'   => max( 1, (int) ceil( $ai4seo_taxonomy_list['total'] / 20 ) ),
		'current' => $ai4seo_taxonomy_list['page'],
	)
);
if ( $ai4seo_taxonomy_pagination ) {
	echo '<div class="ai4seo-pagination">';
	ai4seo_echo_wp_kses( ai4seo_normalize_pagination_links( $ai4seo_taxonomy_pagination ) );
	echo '</div>';
}
