<?php
/**
 * Indexed list predicates and bounded compatibility filtering.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build closed SQL predicates shared by list counts and pages.
 *
 * @param array $args Existing list arguments.
 * @return array|null Base predicate, status predicates and typed values.
 */
function ai4seo_post_state_list_predicates( array $args ): ?array {
	global $wpdb;
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return null;
	}
	$context  = ai4seo_normalize_content_type_list_context( $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA );
	$options  = ai4seo_get_content_type_status_option_names( $context );
	$types    = array_values( array_filter( (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) ) ) );
	$statuses = (array) ( $args['post_status'] ?? array( 'publish', 'future' ) );
	$values   = array(
		'types'    => $types,
		'statuses' => $statuses,
	);
	$where    = $types && $statuses ? 'p.post_type IN ({{types}}) AND p.post_status IN ({{statuses}})' : '1=0';
	foreach ( array(
		'author_not_in'   => 'p.post_author NOT IN',
		'post_mime_types' => 'p.post_mime_type IN',
		'scoped_post_ids' => 'p.ID IN',
	) as $name => $clause ) {
		if ( ! empty( $args[ $name ] ) ) {
			$where          .= ' AND ' . $clause . ' ({{' . $name . '}})';
			$values[ $name ] = array_values( $args[ $name ] );
		} elseif ( 'scoped_post_ids' === $name && isset( $args[ $name ] ) ) {
			$where .= ' AND 1=0';
		}
	}
	$text = sanitize_text_field( $args['filter_text'] ?? '' );
	if ( '' !== $text ) {
		$values['text'] = '%' . $wpdb->esc_like( $text ) . '%';
		$where         .= ' AND (p.post_title LIKE {{text}} OR p.post_name LIKE {{text}} OR p.guid LIKE {{text}}';
		if ( ctype_digit( $text ) ) {
			$where              .= ' OR p.ID={{search_id}}';
			$values['search_id'] = (int) $text;
		}
		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ) {
			$where             .= ' OR EXISTS (SELECT 1 FROM {{meta}} f WHERE f.post_id=p.ID AND f.meta_key={{file_key}} AND f.meta_value LIKE {{text}})';
			$values['file_key'] = '_wp_attached_file';
		}
		$where .= ')';
	}
	$predicates = array();
	foreach ( $options as $status => $option ) {
		$key                      = ai4seo_post_state_key( $option, 1, $state );
		$values[ 'g_' . $status ] = $key['generation'];
		$values[ 'b_' . $status ] = $key['bucket'];
		$predicates[ $status ]    = 'EXISTS (SELECT 1 FROM {{memberships}} s WHERE s.area=1 AND s.generation={{g_' . $status . '}} AND s.bucket={{b_' . $status . '}} AND s.post_id=p.ID)';
	}
	$hidden = $predicates['hidden'];
	foreach ( $predicates as $status => $predicate ) {
		if ( 'hidden' !== $status ) {
			$predicates[ $status ] = '(' . $predicate . ' AND NOT ' . $hidden . ')';
		}
	}
	$predicates['all'] = 'NOT ' . $hidden;
	// A successful indexed read verifies exclusions; failed reads remain unavailable.
	$predicates['unverified']       = '1=0';
	$predicates['waiting_to_queue'] = $predicates['missing'];
	foreach ( array( 'queued', 'processing', 'failed', 'auto_queue_disallowed' ) as $status ) {
		$predicates['waiting_to_queue'] .= ' AND NOT ' . $predicates[ $status ];
	}
	$date = ai4seo_get_bulk_generation_date_filter_state( $args['new_or_existing_filter'] ?? 'both', $args['new_or_existing_filter_reference_timestamp'] ?? 0 );
	if ( empty( $args['is_bulk_generation_activated'] ) || empty( $args['should_auto_queue_bulk_generation_entries'] ) || empty( $args['has_enough_credits'] ) || ! $date['is_valid'] ) {
		$predicates['waiting_to_queue'] = '1=0';
	} elseif ( 'both' !== $date['filter'] ) {
		$values['date']                  = $date['post_date_gmt'];
		$predicates['waiting_to_queue'] .= ' AND p.post_date_gmt' . ( 'new' === $date['filter'] ? '>' : '<=' ) . '{{date}}';
	}
	return array(
		'where'    => $where,
		'statuses' => $predicates,
		'values'   => $values,
	);
}

/**
 * Resolve every regular list through bounded status readers.
 *
 * Normal ID pages and counts stay in SQL. Third-party language/capability rules
 * and natural title/coverage sorting use bounded runs, retaining their exact PHP
 * semantics without retaining a site-wide status collection in PHP.
 *
 * @param array $args Existing public list arguments.
 * @return array Existing resolver result.
 */
function ai4seo_resolve_indexed_content_type_list( array $args ): array {
	$cache_key = ai4seo_post_state_list_cache_key( $args );
	$slot      = empty( $args['_counts_only'] ) ? 'page' : 'counts';
	$cache     = ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_LIST_CACHE )
		? ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_LIST_CACHE ) : array();
	if ( null !== $cache_key && isset( $cache[ $slot ] ) && $cache[ $slot ]['key'] === $cache_key && $cache[ $slot ]['expires'] > time() ) {
		return $cache[ $slot ]['result'];
	}
	$failures = ai4seo_content_type_list_read_failures();
	$result   = ai4seo_resolve_uncached_indexed_content_type_list( $args );
	// Never publish a mixed-version scan, unavailable result, or a large cache value.
	if ( null !== $cache_key && ai4seo_post_state_list_cache_key( $args ) === $cache_key
		&& ai4seo_content_type_list_read_failures() === $failures && $result['view_available']
		&& strlen( (string) wp_json_encode( $result ) ) <= 65536 ) {
		$cache[ $slot ] = array(
			'key'     => $cache_key,
			'expires' => time() + 30,
			'result'  => $result,
		);
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_LIST_CACHE, $cache, true, 30 );
	}
	return $result;
}

/**
 * Count the same filtered inventory without sorting or selecting a page.
 *
 * @param array $args List arguments.
 * @return array Status counters, retaining unavailable values.
 */
function ai4seo_read_post_state_list_counts( array $args ): array {
	unset( $args['orderby'], $args['order'], $args['current_page'], $args['per_page'] );
	$args['_counts_only'] = true;
	return ai4seo_resolve_indexed_content_type_list( $args )['status_counts'];
}

/**
 * Recognize callbacks whose grants cannot affect the requested content types.
 *
 * WooCommerce's standard callbacks concern users and orders. They are safe for
 * ordinary product editing, but not for a custom type that aliases those caps.
 * Unknown callbacks always require the exact per-post compatibility reader.
 *
 * @param array $args List arguments.
 * @return bool
 */
function ai4seo_has_stable_content_type_capabilities( array $args ): bool {
	global $wp_filter;
	$known    = array(
		'map_meta_cap' => array(
			'wc_modify_map_meta_cap' => array( 'edit_user', 'remove_user', 'promote_user', 'delete_user' ),
		),
		'user_has_cap' => array(
			'wp_maybe_grant_install_languages_cap'  => array( 'install_languages' ),
			'wp_maybe_grant_resume_extensions_caps' => array( 'resume_plugins', 'resume_themes' ),
			'wp_maybe_grant_site_health_caps'       => array( 'view_site_health_checks' ),
			'wc_customer_has_capability'            => array( 'view_order', 'pay_for_order', 'order_again', 'cancel_order', 'download_file' ),
			'wc_shop_manager_has_capability'        => array( 'edit_users' ),
		),
	);
	$affected = array();
	foreach ( $known as $hook => $callbacks_by_name ) {
		foreach ( $wp_filter[ $hook ]->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$name = $callback['function'];
				if ( is_string( $name ) && isset( $callbacks_by_name[ $name ] ) ) {
					$affected = array_merge( $affected, $callbacks_by_name[ $name ] );
					continue;
				}
				$effects = ai4seo_get_verified_content_capability_effects( $hook, $name, $args );
				if ( null === $effects ) {
					return false;
				}
				$affected = array_merge( $affected, $effects );
			}
		}
	}
	foreach ( (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) ) as $type ) {
		$object = get_post_type_object( $type );
		if ( ! $object || array_intersect( (array) $object->cap, $affected ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Describe verified plugin callbacks only in the versions and routes inspected.
 *
 * Exact classes exclude overrides in subclasses. Future plugin versions and
 * unknown callbacks retain the per-post reader until their contracts are verified.
 *
 * @param string $hook Capability hook.
 * @param mixed  $callback Registered callable.
 * @param array  $args Requested content types.
 * @return array|null Capabilities affected, or null when the profile is not safe.
 */
function ai4seo_get_verified_content_capability_effects( string $hook, $callback, array $args ): ?array {
	if ( 'map_meta_cap' === $hook && 'wpcode_map_meta_cap' === $callback && defined( 'WPCODE_VERSION' ) && '2.4.0' === WPCODE_VERSION ) {
		return array( 'wpcode_edit_php_snippets', 'wpcode_edit_html_snippets', 'wpcode_manage_conversion_pixels', 'wpcode_file_editor', 'wpcode_manage_settings' );
	}
	if ( ! is_array( $callback ) || 2 !== count( $callback ) || ! is_object( $callback[0] ) || ! is_string( $callback[1] ) ) {
		return null;
	}
	$name = get_class( $callback[0] ) . '::' . $callback[1];
	if ( 'map_meta_cap' === $hook && 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController::maybe_translate_order_caps' === $name
		&& defined( 'WC_VERSION' ) && '11.2.1' === WC_VERSION ) {
		// The HPOS helper returns unchanged caps for every existing real post.
		$types = (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) );
		return in_array( 'shop_order_placehold', $types, true ) ? null : array();
	}
	if ( 'map_meta_cap' === $hook && 'Automattic\\Jetpack\\Connection\\Manager::jetpack_connection_custom_caps' === $name
		&& defined( 'Automattic\\Jetpack\\Connection\\Package_Version::PACKAGE_VERSION' )
		&& '6.19.2' === constant( 'Automattic\\Jetpack\\Connection\\Package_Version::PACKAGE_VERSION' ) ) {
		return array( 'jetpack_connect', 'jetpack_reconnect', 'jetpack_disconnect', 'jetpack_connect_user', 'jetpack_unlink_user' );
	}
	if ( 'user_has_cap' === $hook && 'Elementor\\Core\\Documents_Manager::remove_user_edit_cap' === $name
		&& defined( 'ELEMENTOR_VERSION' ) && '3.35.9' === ELEMENTOR_VERSION
		&& ! in_array( $GLOBALS['pagenow'] ?? '', array( 'post.php', 'edit.php' ), true ) ) {
		return array();
	}
	return null;
}

/**
 * Scope a bounded cache to authoritative data and the current permission owner.
 *
 * Dynamic capability filters cannot be fingerprinted, so their results are not cached.
 *
 * @param array $args List arguments.
 * @return string|null Digest, or null when caching is unsafe.
 */
function ai4seo_post_state_list_cache_key( array $args ): ?string {
	if ( ! ai4seo_has_stable_content_type_capabilities( $args ) || ai4seo_is_content_type_list_cache_namespace_dirty() ) {
		return null;
	}
	$version = ai4seo_get_raw_option_snapshot( AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME );
	$state   = ai4seo_read_post_state_storage_status();
	if ( null === $version || null === $state || 'active' !== ( $state['phase'] ?? '' ) ) {
		return null;
	}
	$title_state = ai4seo_read_title_order_state();
	if ( null === $title_state || ( $title_state && array() !== ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true ) ) ) {
		return null;
	}
	$user         = wp_get_current_user();
	$capabilities = array();
	foreach ( (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) ) as $type ) {
		$object                = get_post_type_object( $type );
		$capabilities[ $type ] = $object ? array( (array) $object->cap, $object->map_meta_cap ) : null;
	}
	$external_versions = wp_using_ext_object_cache() ? array( wp_cache_get_last_changed( 'posts' ), wp_cache_get_last_changed( 'terms' ) ) : array();
	return hash( 'sha256', (string) wp_json_encode( array( 'verified-exclusion-counts', $args, ai4seo_get_site_options_request_cache_scope(), $user->ID, $user->allcaps, is_super_admin( $user->ID ), $capabilities, $version['raw_value'], $state, $external_versions, $title_state ) ) );
}

/**
 * Keep the shared environmental cache smaller than two ordinary page payloads.
 *
 * @param mixed $value Stored cache.
 * @return bool
 */
function ai4seo_validate_post_state_list_cache( $value ): bool {
	if ( ! is_array( $value ) || array_diff( array_keys( $value ), array( 'page', 'counts' ) ) || strlen( (string) wp_json_encode( $value ) ) > 132000 ) {
		return false;
	}
	foreach ( $value as $entry ) {
		if ( ! is_array( $entry ) || array_keys( $entry ) !== array( 'key', 'expires', 'result' ) || ! is_string( $entry['key'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/D', $entry['key'] ) || ! is_int( $entry['expires'] ) || ! is_array( $entry['result'] )
			|| ! isset( $entry['result']['post_ids'], $entry['result']['status_counts'], $entry['result']['view_available'] )
			|| ! is_array( $entry['result']['post_ids'] ) || count( $entry['result']['post_ids'] ) > 1000 || strlen( (string) wp_json_encode( $entry['result'] ) ) > 65536 ) {
			return false;
		}
		if ( ! is_bool( $entry['result']['view_available'] ) || ! is_array( $entry['result']['status_counts'] ) ) {
			return false;
		}
		foreach ( $entry['result']['post_ids'] as $id ) {
			if ( ! is_int( $id ) || $id <= 0 ) {
				return false;
			}
		}
		foreach ( $entry['result']['status_counts'] as $count ) {
			if ( null !== $count && ( ! is_int( $count ) || $count < 0 ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Resolve one uncached list, sharing the same predicates with count-only callers.
 *
 * @param array $args List arguments.
 * @return array Existing resolver result.
 */
function ai4seo_resolve_uncached_indexed_content_type_list( array $args ): array {
	$context  = ai4seo_normalize_content_type_list_context( $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA );
	$snapshot = ai4seo_read_content_type_status_snapshot( $context );
	$sort     = ai4seo_normalize_content_type_sort_args( $args['orderby'] ?? 'id', $args['order'] ?? 'desc' );
	$status   = sanitize_key( $args['filter_status'] ?? 'all' );
	$per_page = max( 1, min( ai4seo_post_state_batch_size(), (int) ( $args['per_page'] ?? 20 ) ) );
	$page     = max( 1, (int) ( $args['current_page'] ?? 1 ) );
	$options  = $args['status_options'] ?? ai4seo_get_content_type_status_options();
	$result   = array(
		'is_optimized'                                   => true,
		'quality_version'                                => 2,
		'status_snapshot'                                => $snapshot,
		'view_available'                                 => false,
		'content_context'                                => $context,
		'is_large_site'                                  => true,
		'current_page'                                   => $page,
		'post_ids'                                       => array(),
		'total_items'                                    => null,
		'total_pages'                                    => 1,
		'status_counts'                                  => array_fill_keys( array_keys( $options ), null ),
		'current_page_status_ids'                        => array(),
		'should_defer_status_counts'                     => false,
		'should_derive_current_page_status_ids'          => false,
		'should_derive_current_page_metadata_status_ids' => false,
		'sort_value_map'                                 => array(),
	);
	$parts    = ai4seo_post_state_list_predicates( $args );
	if ( null === $parts || ! isset( $parts['statuses'][ $status ] ) ) {
		ai4seo_content_type_list_read_failures( true );
		return $result;
	}
	$types      = (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) );
	$complex    = ! empty( $args['filter_language'] ) || ! empty( $args['disabled_taxonomy_terms'] ) || ! empty( $args['disabled_wpml_language_codes'] ) || ! ai4seo_can_edit_others_posts_for_post_types( $types ) || ! ai4seo_has_stable_content_type_capabilities( $args );
	$title_page = ai4seo_read_title_order_page( $args, $sort, $result );
	if ( null !== $title_page ) {
		$result = $title_page;
	} elseif ( $complex || 'id' !== $sort['orderby'] ) {
		$resolved = ai4seo_read_post_state_list_runs( $args, $parts, $sort, $status, $per_page, $page );
		if ( null === $resolved ) {
			ai4seo_content_type_list_read_failures( true );
			return $result;
		}
		$result = array_replace( $result, $resolved );
	} else {
		$fields = array();
		foreach ( $options as $key => $label ) {
			if ( isset( $parts['statuses'][ $key ] ) ) {
				$fields[] = 'COALESCE(SUM(' . $parts['statuses'][ $key ] . '),0) AS n_' . $key;
			}
		}
		$counts = ai4seo_post_state_query( 'SELECT ' . implode( ',', $fields ) . ' FROM {{posts}} p WHERE ' . $parts['where'], $parts['values'], true );
		if ( null === $counts ) {
			ai4seo_content_type_list_read_failures( true );
			return $result;
		}
		foreach ( $counts[0] as $key => $value ) {
			$result['status_counts'][ substr( $key, 2 ) ] = (int) $value;
		}
		$result['total_items']  = $result['status_counts'][ $status ] ?? 0;
		$result['total_pages']  = max( 1, (int) ceil( $result['total_items'] / $per_page ) );
		$result['current_page'] = min( $page, $result['total_pages'] );
		$values                 = $parts['values'] + array(
			'offset' => ( $result['current_page'] - 1 ) * $per_page,
			'limit'  => $per_page,
		);
		$rows                   = ! empty( $args['_counts_only'] ) ? array() : ai4seo_post_state_query( 'SELECT p.ID FROM {{posts}} p WHERE ' . $parts['where'] . ' AND (' . $parts['statuses'][ $status ] . ') ORDER BY p.ID ' . ( 'asc' === $sort['order'] ? 'ASC' : 'DESC' ) . ' LIMIT {{offset}},{{limit}}', $values, true );
		if ( null === $rows ) {
			ai4seo_content_type_list_read_failures( true );
			return $result;
		}
		$result['post_ids'] = array_map( 'intval', array_column( $rows, 'ID' ) );
	}
	$result['status_counts']  = ai4seo_apply_content_type_status_quality( $result['status_counts'], $snapshot );
	$result['view_available'] = ! empty( $snapshot['quality'][ $status ] );
	if ( ! $result['view_available'] ) {
		$result['post_ids']    = array();
		$result['total_items'] = null;
	}
	$result['current_page_status_ids'] = ai4seo_get_content_type_current_page_status_ids( $result['post_ids'], $context );
	return $result;
}

/**
 * Stream exact exceptional filters and natural sorting in bounded runs.
 *
 * @param array  $args List arguments.
 * @param array  $parts SQL predicates.
 * @param array  $sort Existing normalized sort.
 * @param string $status Selected filter.
 * @param int    $per_page Visible page size.
 * @param int    $page Requested page.
 * @return array|null
 */
function ai4seo_read_post_state_list_runs( array $args, array $parts, array $sort, string $status, int $per_page, int $page ): ?array {
	$context = $args['content_context'] ?? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA;
	$fields  = array();
	foreach ( $parts['statuses'] as $key => $predicate ) {
		$fields[] = '(' . $predicate . ') AS n_' . $key;
	}
	$counts      = array_fill_keys( array_keys( $parts['statuses'] ), 0 );
	$after       = 0;
	$runs        = array();
	$compare     = static function ( array $a, array $b ) use ( $sort ): int {
		if ( 'id' === $sort['orderby'] ) {
			return ( 'asc' === $sort['order'] ? 1 : -1 ) * ( $a['id'] <=> $b['id'] );
		}
		if ( 'seo_progress' === $sort['orderby'] && is_numeric( $a['value'] ) !== is_numeric( $b['value'] ) ) {
			return is_numeric( $a['value'] ) ? -1 : 1;
		}
		if ( 'title' === $sort['orderby'] ) {
			return ai4seo_compare_content_type_title_entries( $a, $b, $sort['order'] );
		}
		$order = (float) $a['value'] <=> (float) $b['value'];
		return $order ? ( 'asc' === $sort['order'] ? 1 : -1 ) * $order : ( $b['id'] <=> $a['id'] );
	};
	$counts_only = ! empty( $args['_counts_only'] );
	$top_limit   = ! $counts_only && 'title' === $sort['orderby'] && $page <= intdiv( 1000, $per_page ) ? $page * $per_page : 0;
	$top_bytes   = 0;
	$top         = $top_limit ? new class( $compare ) extends SplHeap {
		/**
		 * Exact existing ordering; the worst retained entry is the root.
		 *
		 * @var callable
		 */
		private $order;
		/**
		 * Retain the original comparator.
		 *
		 * @param callable $order Comparator.
		 */
		public function __construct( callable $order ) {
			$this->order = $order;
		}
		/**
		 * Order the worst candidate first.
		 *
		 * @param mixed $a Entry.
		 * @param mixed $b Entry.
		 * @return int
		 */
		protected function compare( $a, $b ): int {
			return call_user_func( $this->order, $a, $b );
		}
	} : null;
	try {
		do {
			// Keep keyset traversal on the primary key; type/status indexes can re-sort the remaining inventory per packet.
			$rows = ai4seo_post_state_query(
				'SELECT p.ID,OCTET_LENGTH(p.post_content)+OCTET_LENGTH(p.post_excerpt)+OCTET_LENGTH(p.post_content_filtered)+OCTET_LENGTH(p.post_title)+OCTET_LENGTH(p.to_ping)+OCTET_LENGTH(p.pinged)+4096 AS row_bytes,' . implode( ',', $fields ) . ' FROM {{posts}} p FORCE INDEX (PRIMARY) WHERE ' . $parts['where'] . ' AND p.ID>{{after}} ORDER BY p.ID LIMIT {{limit}}',
				$parts['values'] + array(
					'after' => $after,
					'limit' => ai4seo_post_state_batch_size(),
				),
				true
			);
			if ( null === $rows ) {
				return null;
			}
			$titles = ai4seo_prime_post_state_list_packet( $rows );
			if ( null === $titles ) {
				return null;
			}
			$ids = array_map( 'intval', array_column( $rows, 'ID' ) );
			if ( ! $ids ) {
				break;
			}
			$after    = end( $ids );
			$eligible = ai4seo_filter_editable_post_ids( $ids );
			$eligible = ai4seo_filter_post_ids_by_disabled_taxonomy_terms( $eligible, $args['disabled_taxonomy_terms'] ?? array() );
			$eligible = ai4seo_filter_post_ids_by_language( $eligible, $args['filter_language'] ?? '' );
			$eligible = ai4seo_filter_post_ids_by_disabled_wpml_languages( $eligible, $args['disabled_wpml_language_codes'] ?? array() );
			$lookup   = array_fill_keys( $eligible, true );
			$selected = array();
			foreach ( $rows as $row ) {
				$id = (int) $row['ID'];
				if ( ! isset( $lookup[ $id ] ) ) {
					continue;
				}
				foreach ( $counts as $key => $count ) {
					$counts[ $key ] += (int) $row[ 'n_' . $key ];
				}
				if ( ! $counts_only && $row[ 'n_' . $status ] ) {
					$selected[] = $id;
				}
			}
			$values = array();
			if ( 'title' === $sort['orderby'] ) {
				$values = $titles;
			} elseif ( 'seo_progress' === $sort['orderby'] ) {
				$read   = false;
				$issues = array();
				if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ) {
					$coverage = ai4seo_read_and_analyse_attachment_attributes_coverage( $selected, $read, $issues );
					$summary  = ai4seo_get_attachment_attributes_coverage_summary( $coverage );
					$total    = ai4seo_get_active_num_attachment_attributes();
					foreach ( $selected as $id ) {
						$values[ $id ] = $read && isset( $summary[ $id ] ) ? ( $total ? min( 100, round( $summary[ $id ] / $total * 100, 2 ) ) : 100 ) : null;
					}
				} else {
					$field_states = array();
					$values       = ai4seo_read_percentage_of_available_metadata_by_post_ids( $selected, 0, $read, $field_states, $issues );
				}
			}
			$run = array();
			foreach ( $selected as $id ) {
				$run[] = array(
					'id'    => $id,
					'value' => $values[ $id ] ?? null,
				);
			}
			if ( $run && null !== $top ) {
				$run_bytes = array_sum(
					array_map(
						static function ( array $entry ): int {
							return strlen( (string) $entry['value'] ) + 512;
						},
						$run
					)
				);
				if ( $top_bytes + $run_bytes > 8 * 1024 * 1024 ) {
					// The earlier retained prefix contains every prior candidate that can
					// still reach this page. Spill it and all later packets when titles are long.
					$run = array_merge( iterator_to_array( $top, false ), $run );
					$top = null;
				} else {
					ai4seo_select_post_state_top_k( $top, $run, $top_limit, $compare, $top_bytes );
					$run = array();
				}
			}
			if ( $run ) {
				usort( $run, $compare );
				// Auto-deleted private files avoid retaining all sort keys or identities in PHP.
				$stream = tmpfile();
				if ( false === $stream ) {
					return null;
				}
				$runs[] = $stream;
				foreach ( $run as $entry ) {
					$line = wp_json_encode( $entry ) . "\n";
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Private auto-deleted merge runs need stream access, not FTP filesystem credentials.
					if ( strlen( $line ) !== fwrite( $stream, $line ) ) {
						return null;
					}
				}
				rewind( $stream );
				array_pop( $runs );
				// Binary merge levels bound open files to O(log packets), even on very large sites.
				$level = 0;
				while ( isset( $runs[ $level ] ) ) {
					$merged = ai4seo_merge_post_state_sort_runs( $runs[ $level ], $stream, $compare );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the consumed private run.
					fclose( $stream );
					if ( false === $merged ) {
						return null;
					}
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the consumed private run.
					fclose( $runs[ $level ] );
					unset( $runs[ $level ] );
					$stream = $merged;
					++$level;
				}
				$runs[ $level ] = $stream;
			}
			foreach ( $ids as $id ) {
				wp_cache_delete( $id, 'posts' );
				wp_cache_delete( $id, 'post_meta' );
			}
			do_action( 'ai4seo_post_state_checkpoint', 'after_list_packet' );
		} while ( $rows );
		$pages = max( 1, (int) ceil( $counts[ $status ] / $per_page ) );
		$page  = min( $page, $pages );
		if ( $counts_only || null !== $top ) {
			$selected = null === $top ? array() : iterator_to_array( $top, false );
			usort( $selected, $compare );
			$selected = array_slice( $selected, ( $page - 1 ) * $per_page, $per_page );
			return array(
				'post_ids'       => array_column( $selected, 'id' ),
				'status_counts'  => $counts,
				'total_items'    => $counts[ $status ],
				'total_pages'    => $pages,
				'current_page'   => $page,
				'sort_value_map' => array_column( $selected, 'value', 'id' ),
			);
		}
		$heap = new class( $compare ) extends SplHeap {
			/**
			 * Original list ordering, reversed below so the best run head is extracted first.
			 *
			 * @var callable Ordering shared with the existing natural-sort contract.
			 */
			private $order;
			/**
			 * Retain the comparator shared by all sorted runs.
			 *
			 * @param callable $order Comparator.
			 */
			public function __construct( callable $order ) {
				$this->order = $order;
			}
			/**
			 * Reverse the max-heap order to merge the earliest entry from each run.
			 *
			 * @param mixed $a Head.
			 * @param mixed $b Head.
			 * @return int.
			 */
			protected function compare( $a, $b ): int {
				return -call_user_func( $this->order, $a, $b );
			}
		};
		foreach ( $runs as $index => $stream ) {
			$entry        = json_decode( fgets( $stream ), true );
			$entry['run'] = $index;
			$heap->insert( $entry );
		}
		$ids    = array();
		$values = array();
		$skip   = ( $page - 1 ) * $per_page;
		for ( $position = 0; $position < $skip + $per_page; ++$position ) {
			if ( $heap->isEmpty() ) {
				break;
			}
			$entry = $heap->extract();
			if ( $position >= $skip ) {
				$ids[]                  = $entry['id'];
				$values[ $entry['id'] ] = $entry['value'];
			}
			$line = fgets( $runs[ $entry['run'] ] );
			if ( false !== $line ) {
				$next        = json_decode( $line, true );
				$next['run'] = $entry['run'];
				$heap->insert( $next );
			}
		}
		return array(
			'post_ids'       => $ids,
			'status_counts'  => $counts,
			'total_items'    => $counts[ $status ],
			'total_pages'    => $pages,
			'current_page'   => $page,
			'sort_value_map' => $values,
		);
	} finally {
		foreach ( $runs as $stream ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the private temporary stream in every exit path.
			fclose( $stream );
		}
	}
}


/**
 * Merge two sorted private streams while retaining only their heads in memory.
 *
 * @param resource $left First run.
 * @param resource $right Second run.
 * @param callable $compare Existing list comparator.
 * @return resource|false Merged auto-deleted stream or a read/write failure.
 */
function ai4seo_merge_post_state_sort_runs( $left, $right, callable $compare ) {
	$output = tmpfile();
	if ( false === $output ) {
		return false;
	}
	$lines = array( fgets( $left ), fgets( $right ) );
	while ( false !== $lines[0] || false !== $lines[1] ) {
		$heads = array_map(
			static function ( $line ) {
				return false === $line ? null : json_decode( $line, true );
			},
			$lines
		);
		if ( ( false !== $lines[0] && ! is_array( $heads[0] ) ) || ( false !== $lines[1] && ! is_array( $heads[1] ) ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Failed private merge must be removed.
			fclose( $output );
			return false;
		}
		$pick = null === $heads[0] ? 1 : ( null === $heads[1] || $compare( $heads[0], $heads[1] ) <= 0 ? 0 : 1 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Private sorted runs require native stream access.
		if ( strlen( $lines[ $pick ] ) !== fwrite( $output, $lines[ $pick ] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Failed private merge must be removed.
			fclose( $output );
			return false;
		}
		$lines[ $pick ] = fgets( 0 === $pick ? $left : $right );
	}
	if ( ! feof( $left ) || ! feof( $right ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Failed private merge must be removed.
		fclose( $output );
		return false;
	}
	rewind( $output );
	return $output;
}

/**
 * Retain only the requested prefix in the exact existing natural order.
 *
 * @param SplHeap  $heap Worst retained entry first.
 * @param array    $entries Bounded candidate packet.
 * @param int      $limit Maximum retained entries.
 * @param callable $compare Existing comparator.
 * @param int      $bytes Retained title and entry budget, updated by reference.
 * @return void
 */
function ai4seo_select_post_state_top_k( SplHeap $heap, array $entries, int $limit, callable $compare, int &$bytes ): void {
	foreach ( $entries as $entry ) {
		if ( $heap->count() < $limit ) {
			$heap->insert( $entry );
			$bytes += strlen( (string) $entry['value'] ) + 512;
		} elseif ( $compare( $entry, $heap->top() ) < 0 ) {
			$removed = $heap->extract();
			$bytes  += strlen( (string) $entry['value'] ) - strlen( (string) $removed['value'] );
			$heap->insert( $entry );
		}
	}
}

/**
 * Prime complete WordPress objects for unchanged per-object capability mapping.
 *
 * Trim the cursor packet to an 8 MiB raw-data budget before fetching long fields.
 * A single larger object is processed alone, subject to the request memory limit.
 *
 * @param array $rows Candidate rows, trimmed by reference.
 * @return array|null Raw titles, or null after a read failure.
 */
function ai4seo_prime_post_state_list_packet( array &$rows ): ?array {
	$bytes = 0;
	$count = 0;
	foreach ( $rows as $row ) {
		if ( $count && $bytes + (int) $row['row_bytes'] > 8 * 1024 * 1024 ) {
			break;
		}
		$bytes += (int) $row['row_bytes'];
		++$count;
	}
	$rows = array_slice( $rows, 0, $count );
	if ( ! $rows ) {
		return array();
	}
	$limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
	if ( $limit > 0 && memory_get_usage( true ) + 3 * $bytes + 8 * 1024 * 1024 > $limit ) {
		return null;
	}
	$posts = ai4seo_post_state_query( 'SELECT * FROM {{posts}} WHERE ID IN ({{ids}})', array( 'ids' => array_map( 'intval', array_column( $rows, 'ID' ) ) ), true );
	if ( null === $posts ) {
		return null;
	}
	$titles = array();
	foreach ( $posts as $post ) {
		$titles[ (int) $post['ID'] ] = (string) $post['post_title'];
		wp_cache_add( (int) $post['ID'], sanitize_post( (object) $post, 'raw' ), 'posts' );
	}
	return $titles;
}
