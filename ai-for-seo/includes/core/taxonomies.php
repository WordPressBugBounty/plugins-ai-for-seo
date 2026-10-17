<?php
/**
 * Handles taxonomy and term discovery.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region TAXONOMIES ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Get all taxonomies that expose public term archive URLs.
 *
 * Includes core, custom, WooCommerce product taxonomies, and Woo attributes (pa_*)
 * but excludes Woo attributes that have archives disabled.
 *
 * @return array[] List of taxonomy info:
 *                 array(
 *                     'taxonomy'      => 'category',
 *                     'label'         => 'Categories',
 *                     'is_woocommerce'=> true|false,
 *                     'is_attribute'  => true|false,
 *                     'archives_on'   => true|false,
 *                     'term_count'    => 123,
 *                     'sample_url'    => 'https://example.com/category/foo' | null,
 *                 )
 */
function ai4seo_get_url_exposed_taxonomies(): array {
	$cache_key = 'ai4seo_url_exposed_taxonomies_v1';
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 543892749, 'Prevented loop', true );
		return array();
	}

	$tax_objects = ai4seo_get_public_archive_taxonomies();
	if ( is_wp_error( $tax_objects ) ) {
		return array();
	}

	$results = array();

	foreach ( $tax_objects as $tax_name => $tax_obj ) {
		// Woo and attributes flags.
		$is_woo       = in_array( $tax_name, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( $tax_name, 'pa_' );
		$is_attribute = 0 === strpos( $tax_name, 'pa_' );

		// Count terms cheaply.
		$term_count = wp_count_terms(
			array(
				'taxonomy'   => $tax_name,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $term_count ) ) {
			return array();
		}
		$term_count = (int) $term_count;

		// Sample URL: try to fetch a single term and link to it.
		$sample_url = null;
		if ( $term_count > 0 ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax_name,
					'hide_empty' => false,
					'number'     => 1,
					'fields'     => 'all',
				)
			);
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$url = get_term_link( $terms[0] );
				if ( ! is_wp_error( $url ) ) {
					$sample_url = esc_url( $url );
				}
			}
		}

		$results[] = array(
			'taxonomy'       => $tax_name,
			'label'          => isset( $tax_obj->labels->name ) ? (string) $tax_obj->labels->name : $tax_name,
			'is_woocommerce' => $is_woo,
			'is_attribute'   => $is_attribute,
			'archives_on'    => true,
			'term_count'     => $term_count,
			'sample_url'     => $sample_url,
		);
	}

	// Sort: Woo first, then by name.
	usort(
		$results,
		static function ( $a, $b ) {
			if ( $a['is_woocommerce'] !== $b['is_woocommerce'] ) {
				return $a['is_woocommerce'] ? -1 : 1;
			}
			return strcasecmp( $a['taxonomy'], $b['taxonomy'] );
		}
	);

	set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );
	return $results;
}


/**
 * Register storage-only posts; no WordPress content or SEO surface exposes them yet.
 *
 * @return void
 */
function ai4seo_register_taxonomy_proxy_post_type(): void {
	register_post_type(
		AI4SEO_TAXONOMY_PROXY_POST_TYPE,
		array(
			'label'               => 'Taxonomy inventory',
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'query_var'           => false,
			'rewrite'             => false,
			'can_export'          => false,
			'delete_with_user'    => false,
			'supports'            => array(),
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		)
	);
}


/**
 * Get registered public archive candidates, including query-string URLs and empty taxonomies.
 *
 * This is deliberately independent of noindex, supported post types, user settings and term caches.
 * WooCommerce's attribute flag supplements the registered public/queryable flags.
 *
 * @return WP_Taxonomy[]|WP_Error Taxonomies keyed by name, or an unreadable provider registry.
 */
function ai4seo_get_public_archive_taxonomies() {
	global $wpdb;

	$attributes = array();
	$has_woo    = function_exists( 'wc_get_attribute_taxonomies' );
	if ( $has_woo ) {
		$wpdb->last_error = '';
		$rows             = wc_get_attribute_taxonomies();
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			return new WP_Error( 'taxonomy_attribute_read_failed' );
		}
		foreach ( $rows as $attribute ) {
			$attributes[ 'pa_' . sanitize_key( $attribute->attribute_name ) ] = ! empty( $attribute->attribute_public );
		}
	}
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	foreach ( $taxonomies as $name => $taxonomy ) {
		if ( ! $taxonomy->publicly_queryable
			|| ( $has_woo && 0 === strpos( $name, 'pa_' ) && empty( $attributes[ $name ] ) ) ) {
			unset( $taxonomies[ $name ] );
		}
	}
	return $taxonomies;
}


/**
 * Return the site-specific native database lock, within MySQL's 64-byte limit.
 *
 * @return string
 */
function ai4seo_get_taxonomy_proxy_sync_lock_name(): string {
	global $wpdb;

	return 'ai4seo-taxonomy-' . md5( DB_NAME . '|' . $wpdb->options . '|' . get_current_blog_id() );
}


/**
 * Execute a typed, uncached inventory read, keeping failed reads distinct from empty results.
 *
 * @param string $template Named SQL template.
 * @param array  $bindings Typed compiler bindings.
 * @return array|WP_Error
 */
function ai4seo_query_taxonomy_proxy_inventory( string $template, array $bindings ) {
	global $wpdb;

	$query = ai4seo_prepare_database_query( $template, $bindings );
	if ( false === $query ) {
		return new WP_Error( 'taxonomy_query_invalid' );
	}
	$wpdb->last_error = '';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Authoritative inventory reads use the typed query compiler; cached absence cannot prove deletion.
	$rows = $wpdb->get_results( $query, ARRAY_A );
	return $wpdb->last_error || ! is_array( $rows ) ? new WP_Error( 'taxonomy_read_failed' ) : $rows;
}


/**
 * Capture an upper bound so additions cannot indefinitely extend a running cycle.
 *
 * @param string $phase Source or proxies.
 * @return int|WP_Error
 */
function ai4seo_get_taxonomy_proxy_sync_upper_bound( string $phase ) {
	$source   = 'source' === $phase;
	$bindings = array( 'inventory' => ai4seo_database_identifier_binding( $source ? 'table.term_taxonomy' : 'table.posts' ) );
	if ( ! $source ) {
		$bindings['post_type'] = ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE );
	}
	$rows = ai4seo_query_taxonomy_proxy_inventory(
		$source ? 'SELECT COALESCE(MAX(term_taxonomy_id), 0) AS id FROM {{inventory}}'
			: 'SELECT COALESCE(MAX(ID), 0) AS id FROM {{inventory}} WHERE post_type = {{post_type}}',
		$bindings
	);
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}
	return 1 === count( $rows ) && ctype_digit( (string) $rows[0]['id'] ) ? (int) $rows[0]['id'] : new WP_Error( 'taxonomy_upper_bound_invalid' );
}


/**
 * Read the next bounded keyset, without language or hide-empty filters.
 *
 * @param array $progress Validated checkpoint.
 * @param int   $limit Maximum records to inspect.
 * @return array|WP_Error
 */
function ai4seo_read_taxonomy_proxy_sync_batch( array $progress, int $limit ) {
	$source   = 'source' === $progress['phase'];
	$bindings = array(
		'inventory' => ai4seo_database_identifier_binding( $source ? 'table.term_taxonomy' : 'table.posts' ),
		'cursor'    => ai4seo_database_scalar_binding( '%d', $progress['cursor'] ),
		'upper'     => ai4seo_database_scalar_binding( '%d', $progress[ $source ? 'source_upper' : 'proxy_upper' ] ),
		'limit'     => ai4seo_database_scalar_binding( '%d', max( 1, min( AI4SEO_TAXONOMY_PROXY_SYNC_BATCH_SIZE, $limit ) ) ),
	);
	if ( ! $source ) {
		$bindings['post_type'] = ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE );
	}
	return ai4seo_query_taxonomy_proxy_inventory(
		$source ? 'SELECT term_taxonomy_id AS id FROM {{inventory}} WHERE term_taxonomy_id > {{cursor}} AND term_taxonomy_id <= {{upper}} ORDER BY term_taxonomy_id ASC LIMIT {{limit}}'
			: 'SELECT ID AS id FROM {{inventory}} WHERE post_type = {{post_type}} AND ID > {{cursor}} AND ID <= {{upper}} ORDER BY ID ASC LIMIT {{limit}}',
		$bindings
	);
}


/**
 * Reject corrupt or newer checkpoints rather than reset their cursors.
 *
 * @param mixed $progress Persisted progress.
 * @return bool
 */
function ai4seo_is_valid_taxonomy_proxy_sync_progress( $progress ): bool {
	if ( ! is_array( $progress ) || 1 !== ( $progress['version'] ?? null )
		|| ! in_array( $progress['phase'] ?? '', array( 'source', 'proxies', 'completed' ), true )
		|| ! in_array( $progress['last_status'] ?? '', array( 'pending', 'paused', 'completed', 'error' ), true )
		|| ! isset( $progress['last_error'] ) || ! is_string( $progress['last_error'] ) ) {
		return false;
	}
	foreach ( array( 'cursor', 'source_upper', 'proxy_upper', 'last_run' ) as $field ) {
		if ( ! isset( $progress[ $field ] ) || ! is_int( $progress[ $field ] ) || $progress[ $field ] < 0 ) {
			return false;
		}
	}
	if ( isset( $progress['pending_delete'] ) ) {
		$pending = $progress['pending_delete'];
		if ( 'proxies' !== $progress['phase'] || ! is_array( $pending ) || ! isset( $pending['post_id'], $pending['source']['term_taxonomy_id'] )
			|| ! is_int( $pending['post_id'] ) || $pending['post_id'] <= 0
			|| ! is_int( $pending['source']['term_taxonomy_id'] )
			|| ! ai4seo_is_valid_taxonomy_proxy_source( $pending['source'], $pending['source']['term_taxonomy_id'] ) ) {
			return false;
		}
	}
	return 'completed' === $progress['phase'] || $progress['cursor'] <= $progress[ 'source' === $progress['phase'] ? 'source_upper' : 'proxy_upper' ];
}


/**
 * Persist confirmed progress and refresh the exact CAS snapshot for subsequent writes.
 *
 * @param array $progress Current cursor and optional deletion journal.
 * @param array $snapshot Expected raw option snapshot, updated only after verification.
 * @return true|WP_Error
 */
function ai4seo_save_taxonomy_proxy_sync_progress( array &$progress, array &$snapshot ) {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
		return new WP_Error( 'taxonomy_lock_lost' );
	}
	$progress['generation'] = wp_generate_uuid4();
	if ( true !== ai4seo_compare_and_swap_option_snapshot( AI4SEO_TAXONOMY_PROXY_SYNC_OPTION_NAME, $snapshot, $progress, false ) ) {
		return new WP_Error( 'taxonomy_checkpoint_write_failed' );
	}
	$verified = ai4seo_get_raw_option_snapshot( AI4SEO_TAXONOMY_PROXY_SYNC_OPTION_NAME );
	if ( null === $verified || $verified['value'] !== $progress || 'no' !== $verified['autoload'] ) {
		return new WP_Error( 'taxonomy_checkpoint_write_unconfirmed' );
	}
	$snapshot = $verified;
	return true;
}


/**
 * Resolve current source truth without treating a cache miss or provider outage as deletion.
 *
 * @param int    $term_taxonomy_id Immutable source key.
 * @param string $expected_taxonomy Stored taxonomy, when reconciling an existing proxy.
 * @return array|WP_Error State plus managed source fields when the term exists.
 */
function ai4seo_get_taxonomy_proxy_source_state( int $term_taxonomy_id, string $expected_taxonomy = '' ) {
	global $wpdb;

	if ( $term_taxonomy_id <= 0 ) {
		return new WP_Error( 'taxonomy_source_key_invalid' );
	}
	$taxonomies = ai4seo_get_public_archive_taxonomies();
	if ( is_wp_error( $taxonomies ) ) {
		return $taxonomies;
	}
	$rows = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT tt.term_taxonomy_id, tt.term_id, tt.taxonomy, tt.description, tt.parent, tt.count, t.term_id AS source_term_id, t.name, t.slug, t.term_group FROM {{term_taxonomy}} tt LEFT JOIN {{terms}} t ON t.term_id = tt.term_id WHERE tt.term_taxonomy_id = {{id}} LIMIT 1',
		array(
			'term_taxonomy' => ai4seo_database_identifier_binding( 'table.term_taxonomy' ),
			'terms'         => ai4seo_database_identifier_binding( 'table.terms' ),
			'id'            => ai4seo_database_scalar_binding( '%d', $term_taxonomy_id ),
		)
	);
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}
	$taxonomy = $rows ? $rows[0]['taxonomy'] : $expected_taxonomy;
	if ( '' !== $expected_taxonomy && $taxonomy !== $expected_taxonomy ) {
		return new WP_Error( 'taxonomy_source_identity_changed' );
	}
	$available = isset( $taxonomies[ $taxonomy ] );
	$reason    = taxonomy_exists( $taxonomy ) ? 'archive_disabled' : 'taxonomy_unregistered';
	if ( ! $rows ) {
		// A source disappearing between the source batch and this read needs no insertion.
		return array(
			'state'  => '' === $taxonomy || $available ? 'deleted' : 'inactive',
			'reason' => $reason,
		);
	}
	$row = $rows[0];
	if ( null === $row['source_term_id'] ) {
		return new WP_Error( 'taxonomy_source_incomplete' );
	}
	$url = '';
	if ( $available ) {
		$wpdb->last_error = '';
		$url              = get_term_link( new WP_Term( (object) $row ) );
		if ( is_wp_error( $url ) || $wpdb->last_error || ! is_string( $url ) || '' === $url ) {
			return new WP_Error( 'taxonomy_archive_url_read_failed' );
		}
	}
	return array(
		'state'        => $available ? 'available' : 'inactive',
		'reason'       => $available ? '' : $reason,
		'post_title'   => $row['name'],
		'post_content' => $row['description'],
		'source'       => array(
			'version'            => 1,
			'taxonomy'           => $taxonomy,
			'term_id'            => (int) $row['term_id'],
			'term_taxonomy_id'   => $term_taxonomy_id,
			'slug'               => $row['slug'],
			'parent'             => (int) $row['parent'],
			'archive_url'        => $url,
			'count'              => (int) $row['count'],
			'availability'       => $available ? 'available' : 'inactive',
			'unavailable_reason' => $available ? '' : $reason,
		),
	);
}


/**
 * Validate the owned versioned postmeta field before changing or deleting its post.
 *
 * @param mixed $source Decoded snapshot.
 * @param int   $term_taxonomy_id Technical key from the post row.
 * @return bool
 */
function ai4seo_is_valid_taxonomy_proxy_source( $source, int $term_taxonomy_id ): bool {
	$stored_key = $source['term_taxonomy_id'] ?? null;
	if ( ! is_array( $source ) || 1 !== ( $source['version'] ?? null )
		|| $term_taxonomy_id !== $stored_key
		|| ! in_array( $source['availability'] ?? '', array( 'available', 'inactive' ), true ) ) {
		return false;
	}
	foreach ( array( 'taxonomy', 'slug', 'archive_url', 'unavailable_reason' ) as $field ) {
		if ( ! isset( $source[ $field ] ) || ! is_string( $source[ $field ] ) ) {
			return false;
		}
	}
	foreach ( array( 'term_id', 'parent', 'count' ) as $field ) {
		if ( ! isset( $source[ $field ] ) || ! is_int( $source[ $field ] ) || $source[ $field ] < 0 ) {
			return false;
		}
	}
	return '' !== $source['taxonomy'] && $source['term_id'] > 0;
}


/**
 * Find a unique proxy, including an interrupted insert without its source meta.
 *
 * @param int $term_taxonomy_id Immutable source key.
 * @return array|null|WP_Error Null means absent; duplicates or conflicting ownership fail closed.
 */
function ai4seo_read_taxonomy_proxy( int $term_taxonomy_id ) {
	$rows = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT ID, post_name, post_title, post_content, post_status FROM {{posts}} WHERE post_type = {{type}} AND post_name = {{name}} ORDER BY ID ASC LIMIT 2',
		array(
			'posts' => ai4seo_database_identifier_binding( 'table.posts' ),
			'type'  => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
			'name'  => ai4seo_database_scalar_binding( '%s', 'ai4seo-taxonomy-' . $term_taxonomy_id ),
		)
	);
	if ( is_wp_error( $rows ) || ! $rows ) {
		return $rows ? $rows : null;
	}
	if ( 1 !== count( $rows ) ) {
		return new WP_Error( 'taxonomy_proxy_ambiguous' );
	}
	$post = $rows[0];
	$meta = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT meta_value FROM {{postmeta}} WHERE post_id = {{id}} AND meta_key = {{key}} LIMIT 2',
		array(
			'postmeta' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'id'       => ai4seo_database_scalar_binding( '%d', (int) $post['ID'] ),
			'key'      => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_SOURCE_META_KEY ),
		)
	);
	if ( is_wp_error( $meta ) ) {
		return $meta;
	}
	$post['source'] = $meta ? ai4seo_safe_maybe_unserialize( $meta[0]['meta_value'] ) : null;
	if ( count( $meta ) > 1 || ( $meta && ! ai4seo_is_valid_taxonomy_proxy_source( $post['source'], $term_taxonomy_id ) ) ) {
		return new WP_Error( 'taxonomy_proxy_mapping_invalid' );
	}
	return $post;
}


/**
 * Write only the owned source field, verifying database truth rather than metadata API caches.
 *
 * @param array $post Verified proxy row.
 * @param array $source Desired snapshot.
 * @return true|WP_Error
 */
function ai4seo_store_taxonomy_proxy_source( array $post, array $source ) {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
		return new WP_Error( 'taxonomy_lock_lost' );
	}
	if ( $post['source'] !== $source ) {
		// Avoid a stale cached value causing update_post_meta() to skip a required repair.
		wp_cache_delete( (int) $post['ID'], 'post_meta' );
		update_post_meta( (int) $post['ID'], AI4SEO_TAXONOMY_PROXY_SOURCE_META_KEY, wp_slash( $source ) );
	}
	$verified = ai4seo_read_taxonomy_proxy( $source['term_taxonomy_id'] );
	if ( is_wp_error( $verified ) ) {
		return $verified;
	}
	if ( ! $verified || $verified['ID'] !== $post['ID'] || $verified['source'] !== $source ) {
		return new WP_Error( 'taxonomy_meta_write_failed' );
	}
	if ( ! empty( $post['output_changed'] ) || ( $post['source'] && ( $post['source']['availability'] !== $source['availability']
		|| $post['source']['archive_url'] !== $source['archive_url'] ) ) ) {
		ai4seo_purge_taxonomy_archive_cache( array( $post['source']['archive_url'] ?? '', $source['archive_url'] ) );
	}
	$result = ai4seo_sync_taxonomy_proxy_list_fields( $verified );
	if ( ! is_wp_error( $result ) && $post['source'] !== $source ) {
		ai4seo_schedule_generation_status_summary_rebuild();
	}
	return $result;
}


/**
 * Maintain queryable projections only after the authoritative snapshot is confirmed.
 *
 * @param array $post Verified inventory row including its source.
 * @return true|WP_Error
 */
function ai4seo_sync_taxonomy_proxy_list_fields( array $post ) {
	$fields = array(
		AI4SEO_TAXONOMY_PROXY_NAME_META_KEY         => $post['source']['taxonomy'],
		AI4SEO_TAXONOMY_PROXY_AVAILABILITY_META_KEY => $post['source']['availability'],
	);
	foreach ( $fields as $key => $value ) {
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
			return new WP_Error( 'taxonomy_lock_lost' );
		}
		$query_template = 'SELECT meta_value FROM {{meta}} WHERE post_id = {{id}} AND meta_key = {{key}}';
		$query_bindings = array(
			'meta' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'id'   => ai4seo_database_scalar_binding( '%d', (int) $post['ID'] ),
			'key'  => ai4seo_database_scalar_binding( '%s', $key ),
		);
		$rows           = ai4seo_query_taxonomy_proxy_inventory( $query_template, $query_bindings );
		if ( is_wp_error( $rows ) || count( $rows ) > 1 ) {
			return new WP_Error( 'taxonomy_list_fields_unreadable' );
		}
		if ( ! $rows || $rows[0]['meta_value'] !== $value ) {
			wp_cache_delete( (int) $post['ID'], 'post_meta' );
			update_post_meta( (int) $post['ID'], $key, wp_slash( $value ) );
		}
		$verified = ai4seo_query_taxonomy_proxy_inventory( $query_template, $query_bindings );
		if ( is_wp_error( $verified ) || 1 !== count( $verified ) || $verified[0]['meta_value'] !== $value ) {
			return new WP_Error( 'taxonomy_list_fields_write_failed' );
		}
	}
	return ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() )
		? true : new WP_Error( 'taxonomy_lock_lost' );
}


/**
 * Identify internal proxies without widening supported generation post types.
 *
 * @param int $post_id Entry ID.
 * @return bool
 */
function ai4seo_is_taxonomy_proxy( int $post_id ): bool {
	return AI4SEO_TAXONOMY_PROXY_POST_TYPE === get_post_type( $post_id );
}


/**
 * Resolve current source truth and presentation without repairing stored data.
 *
 * @param int $post_id Proxy ID.
 * @return array|WP_Error
 */
function ai4seo_get_taxonomy_proxy_editor_context( int $post_id ) {
	$post = ai4seo_get_taxonomy_proxy_by_id( $post_id );
	if ( is_wp_error( $post ) || ! $post || empty( $post['source'] ) ) {
		return new WP_Error( 'taxonomy_context_unavailable', __( 'This taxonomy entry could not be read safely. Refresh the inventory and try again.', 'ai-for-seo' ) );
	}
	$source = $post['source'];
	$state  = ai4seo_get_taxonomy_proxy_source_state( $source['term_taxonomy_id'], $source['taxonomy'] );
	if ( is_wp_error( $state ) || 'deleted' === $state['state']
		|| ( isset( $state['source'] ) && $state['source']['term_id'] !== $source['term_id'] ) ) {
		return new WP_Error( 'taxonomy_context_unavailable', __( 'This taxonomy entry is unavailable or has changed. Reopen it after the inventory has synchronized.', 'ai-for-seo' ) );
	}
	$taxonomy  = get_taxonomy( $source['taxonomy'] );
	$available = 'available' === $state['state'] && 'available' === $source['availability'];
	$source    = $available ? $state['source'] : $source;
	$edit_url  = $available ? get_edit_term_link( $source['term_id'], $source['taxonomy'] ) : '';
	return array(
		'post_id'     => $post_id,
		'source'      => $source,
		'taxonomy'    => $taxonomy,
		'available'   => $available,
		'name'        => $state['post_title'] ?? $post['post_title'],
		'description' => $state['post_content'] ?? $post['post_content'],
		'label'       => $taxonomy ? $taxonomy->labels->singular_name : $source['taxonomy'],
		'archive_url' => $available ? $state['source']['archive_url'] : '',
		'edit_url'    => is_string( $edit_url ) ? $edit_url : '',
	);
}


/**
 * Build taxonomy links independently of the generation post-type registry.
 *
 * @return array Entries keyed by taxonomy name; errors never imply an empty inventory.
 */
function ai4seo_get_taxonomy_content_navigation_entries(): array {
	if ( ! ai4seo_can_use_plugin_content() ) {
		return array();
	}
	$eligible = ai4seo_get_public_archive_taxonomies();
	if ( is_wp_error( $eligible ) ) {
		ai4seo_debug_message( 731013001, 'Taxonomy navigation registry could not be read.', true );
		return array();
	}
	$rows  = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT DISTINCT m.meta_value AS taxonomy FROM {{posts}} p INNER JOIN {{meta}} m ON m.post_id = p.ID WHERE p.post_type = {{type}} AND m.meta_key = {{key}}',
		array(
			'posts' => ai4seo_database_identifier_binding( 'table.posts' ),
			'meta'  => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'type'  => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
			'key'   => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_NAME_META_KEY ),
		)
	);
	$names = array_keys( $eligible );
	// Count taxonomy presence, not assigned posts: a term with no content still has an archive.
	$term_rows = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT DISTINCT taxonomy FROM {{terms}}',
		array( 'terms' => ai4seo_database_identifier_binding( 'table.term_taxonomy' ) )
	);
	if ( is_wp_error( $term_rows ) ) {
		ai4seo_debug_message( 731013003, 'Taxonomy navigation term presence could not be read.', true );
	}
	$populated_names = is_wp_error( $rows ) || is_wp_error( $term_rows ) ? null : array_fill_keys(
		array_merge( array_column( $rows, 'taxonomy' ), array_column( $term_rows, 'taxonomy' ) ),
		true
	);
	if ( is_wp_error( $rows ) ) {
		ai4seo_debug_message( 731013002, 'Retained taxonomy navigation could not be read.', true );
	} else {
		$names = array_unique( array_merge( $names, array_column( $rows, 'taxonomy' ) ) );
	}
	$entries = array();
	foreach ( $names as $name ) {
		if ( ! is_string( $name ) || '' === $name || sanitize_key( $name ) !== $name ) {
			continue;
		}
		$taxonomy = get_taxonomy( $name );
		if ( $taxonomy ? ! current_user_can( $taxonomy->cap->edit_terms ) : ! ai4seo_can_administer_plugin() ) {
			continue;
		}
		$label = $taxonomy ? $taxonomy->labels->name : $name;
		if ( ! isset( $eligible[ $name ] ) ) {
			/* translators: %s: Taxonomy label. */
			$label = sprintf( __( '%s (inactive)', 'ai-for-seo' ), $label );
		}
		$url              = ai4seo_get_post_type_page_url( AI4SEO_TAXONOMY_PROXY_POST_TYPE, 1, array( 'ai4seo_taxonomy' => $name ) );
		$entries[ $name ] = array(
			// Keep empty routes accessible; only the visible navigation hides confirmed empty taxonomies.
			'has_entries' => null === $populated_names || isset( $populated_names[ $name ] ),
			'label'       => $label,
			'slug'        => AI4SEO_PLUGIN_IDENTIFIER . '&ai4seo_subpage=post&ai4seo_post_type=' . AI4SEO_TAXONOMY_PROXY_POST_TYPE . '&ai4seo_taxonomy=' . rawurlencode( $name ),
			'url'         => $url,
			'taxonomy'    => $name,
		);
	}
	uksort(
		$entries,
		static function ( $first, $second ) use ( $entries ): int {
			$order = strnatcasecmp( $entries[ $first ]['label'], $entries[ $second ]['label'] );
			return 0 !== $order ? $order : strcmp( $first, $second );
		}
	);
	return $entries;
}


/**
 * Name the taxonomy groups behind an existing notice counter without changing its status scope.
 *
 * @param int    $count Existing proxy total.
 * @param string $option_name Metadata status represented by that total.
 * @return string[] Localized counted taxonomy labels, with a generic fallback for unresolved entries.
 */
function ai4seo_get_taxonomy_proxy_count_labels( int $count, string $option_name ): array {
	if ( $count <= 0 ) {
		return array();
	}
	$fallback = static function ( int $amount ): string {
		return sprintf(
			/* translators: %s: Number of taxonomy archive entries whose taxonomy label is unavailable. */
			_n( '%s taxonomy archive', '%s taxonomy archives', $amount, 'ai-for-seo' ),
			ai4seo_format_number_i18n( $amount )
		);
	};
	$summary  = ai4seo_read_generation_status_summary( false, true );
	$ids      = $summary[ $option_name ][ AI4SEO_TAXONOMY_PROXY_POST_TYPE ]['post_ids'] ?? array();
	if ( count( $ids ) !== $count ) {
		return array( $fallback( $count ) );
	}
	$counts = array();
	foreach ( array_chunk( $ids, ai4seo_get_database_chunk_size() ) as $chunk ) {
		$rows = ai4seo_query_taxonomy_proxy_inventory(
			'SELECT p.ID, MIN(m.meta_value) AS taxonomy FROM {{posts}} p INNER JOIN {{meta}} m ON m.post_id = p.ID
			WHERE p.post_type = {{type}} AND m.meta_key = {{name_key}} AND p.ID IN ({{ids}})
			GROUP BY p.ID HAVING COUNT(*) = 1',
			array(
				'posts'    => ai4seo_database_identifier_binding( 'table.posts' ),
				'meta'     => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'type'     => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
				'name_key' => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_NAME_META_KEY ),
				'ids'      => ai4seo_database_list_binding( '%d', $chunk ),
			)
		);
		if ( is_wp_error( $rows ) ) {
			return array( $fallback( $count ) );
		}
		foreach ( $rows as $row ) {
			$counts[ $row['taxonomy'] ] = ( $counts[ $row['taxonomy'] ] ?? 0 ) + 1;
		}
	}
	$labels = array();
	ksort( $counts );
	foreach ( $counts as $name => $amount ) {
		$taxonomy = get_taxonomy( $name );
		if ( ! $taxonomy ) {
			continue;
		}
		$label = 1 === $amount ? $taxonomy->labels->singular_name : $taxonomy->labels->name;
		if ( '' !== trim( $label ) ) {
			$labels[] = sprintf(
				/* translators: 1: Number of taxonomy entries. 2: Registered singular or plural taxonomy label. */
				__( '%1$s %2$s', 'ai-for-seo' ),
				ai4seo_format_number_i18n( $amount ),
				$label
			);
			$count -= $amount;
		}
	}
	if ( $count > 0 ) {
		$labels[] = $fallback( $count );
	}
	return $labels;
}


/**
 * Read a bounded list from scalar projections, then validate each returned identity.
 *
 * @param string $taxonomy Selected taxonomy.
 * @param string $availability Active, inactive or all.
 * @param string $search Name/description search.
 * @param int    $page Requested page.
 * @param bool   $missing_only Restrict to confirmed incomplete metadata, including queued/failed entries.
 * @return array|WP_Error Rows, count and incomplete-index status.
 */
function ai4seo_read_taxonomy_proxy_list_page( string $taxonomy, string $availability = 'active', string $search = '', int $page = 1, bool $missing_only = false ) {
	global $wpdb;
	if ( ! isset( ai4seo_get_taxonomy_content_navigation_entries()[ $taxonomy ] )
		|| ! in_array( $availability, array( 'active', 'inactive', 'all' ), true ) ) {
		return new WP_Error( 'taxonomy_list_forbidden', __( 'You are not allowed to access this taxonomy.', 'ai-for-seo' ) );
	}
	$checkpoint = ai4seo_get_raw_option_snapshot( AI4SEO_TAXONOMY_PROXY_SYNC_OPTION_NAME );
	if ( null === $checkpoint || ( $checkpoint['exists'] && ! ai4seo_is_valid_taxonomy_proxy_sync_progress( $checkpoint['value'] ) ) ) {
		return new WP_Error( 'taxonomy_progress_unreadable', __( 'The taxonomy inventory status could not be read. Try again after synchronization.', 'ai-for-seo' ) );
	}
	$progress = $checkpoint['exists'] ? $checkpoint['value'] : null;
	$bindings = array(
		'posts'      => ai4seo_database_identifier_binding( 'table.posts' ),
		'meta'       => ai4seo_database_identifier_binding( 'table.postmeta' ),
		'state_meta' => ai4seo_database_identifier_binding( 'table.postmeta' ),
		'type'       => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
		'name_key'   => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_NAME_META_KEY ),
		'state_key'  => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_AVAILABILITY_META_KEY ),
	);
	$pending  = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT COUNT(*) AS amount FROM {{posts}} p WHERE p.post_type = {{type}} AND (NOT EXISTS (SELECT 1 FROM {{meta}} m WHERE m.post_id = p.ID AND m.meta_key = {{name_key}}) OR NOT EXISTS (SELECT 1 FROM {{state_meta}} m WHERE m.post_id = p.ID AND m.meta_key = {{state_key}}))',
		$bindings
	);
	if ( is_wp_error( $pending ) ) {
		return $pending;
	}
	$bindings['taxonomy']           = ai4seo_database_scalar_binding( '%s', $taxonomy );
	$bindings['search']             = ai4seo_database_scalar_binding( '%s', '%' . $wpdb->esc_like( $search ) . '%' );
	$bindings['description_search'] = $bindings['search'];
	$where                          = ' FROM {{posts}} p WHERE p.post_type = {{type}} AND EXISTS (SELECT 1 FROM {{meta}} m WHERE m.post_id = p.ID AND m.meta_key = {{name_key}} AND m.meta_value = {{taxonomy}}) AND (p.post_title LIKE {{search}} OR p.post_content LIKE {{description_search}})';
	// An absent or disabled registration cannot appear active while its next cron run is pending.
	$eligible = ai4seo_get_public_archive_taxonomies();
	if ( is_wp_error( $eligible ) ) {
		return $eligible;
	}
	// A completed cycle predates newly added terms or newly exposed archives. Do not report those lists as empty and ready.
	$unindexed = array();
	if ( isset( $eligible[ $taxonomy ] ) ) {
		$unindexed = ai4seo_query_taxonomy_proxy_inventory(
			'SELECT tt.term_taxonomy_id FROM {{terms}} tt WHERE tt.taxonomy = {{taxonomy}}
			AND NOT EXISTS (SELECT 1 FROM {{posts}} p WHERE p.post_type = {{type}} AND p.post_name = CONCAT({{prefix}}, tt.term_taxonomy_id)) LIMIT 1',
			array(
				'terms'    => ai4seo_database_identifier_binding( 'table.term_taxonomy' ),
				'posts'    => ai4seo_database_identifier_binding( 'table.posts' ),
				'taxonomy' => ai4seo_database_scalar_binding( '%s', $taxonomy ),
				'type'     => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
				'prefix'   => ai4seo_database_scalar_binding( '%s', 'ai4seo-taxonomy-' ),
			)
		);
		if ( is_wp_error( $unindexed ) ) {
			return $unindexed;
		}
	}
	if ( 'all' !== $availability ) {
		if ( ! isset( $eligible[ $taxonomy ] ) ) {
			$where .= 'active' === $availability ? ' AND 1 = 0' : '';
		} else {
			$bindings['availability'] = ai4seo_database_scalar_binding( '%s', 'active' === $availability ? 'available' : 'inactive' );
			$where                   .= ' AND EXISTS (SELECT 1 FROM {{state_meta}} m WHERE m.post_id = p.ID AND m.meta_key = {{state_key}} AND m.meta_value = {{availability}})';
		}
	}
	// Only supply compiler bindings used by the chosen query.
	if ( false === strpos( $where, '{{state_key}}' ) ) {
		unset( $bindings['state_key'], $bindings['state_meta'] );
	}
	if ( $missing_only ) {
		$readiness = ai4seo_get_posts_table_analysis_readiness();
		$summary   = ai4seo_read_taxonomy_generation_status_summary( $taxonomy );
		$coverage  = ai4seo_inspect_analysis_post_id_option( AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME );
		if ( empty( $readiness['contexts'][ AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA ] ) || is_wp_error( $summary ) || ! $coverage['complete'] || ! $coverage['read_succeeded'] ) {
			return new WP_Error( 'taxonomy_status_unavailable', __( 'Metadata status is not available yet. Try again after analysis.', 'ai-for-seo' ) );
		}
		// Filter the ordered ID projection before pagination; no unbounded SQL IN list or per-entry reads.
		$rows = ai4seo_query_taxonomy_proxy_inventory( 'SELECT p.ID' . $where . ' ORDER BY p.post_title ASC, p.ID ASC', $bindings );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		$missing_ids = array_diff(
			array_unique( array_merge( $summary['missing'], $summary['pending'], $summary['processing'], $summary['failed'] ) ),
			$coverage['ids']
		);
		$ids         = array_values( array_intersect( array_map( 'intval', array_column( $rows, 'ID' ) ), $missing_ids ) );
		$total       = count( $ids );
		$page        = min( max( 1, $page ), max( 1, (int) ceil( $total / 20 ) ) );
		$rows        = array_map(
			static function ( $id ): array {
				return array( 'ID' => $id );
			},
			array_slice( $ids, ( $page - 1 ) * 20, 20 )
		);
	} else {
		$counts = ai4seo_query_taxonomy_proxy_inventory( 'SELECT COUNT(*) AS amount' . $where, $bindings );
		if ( is_wp_error( $counts ) ) {
			return $counts;
		}
		$total              = (int) $counts[0]['amount'];
		$page               = min( max( 1, $page ), max( 1, (int) ceil( $total / 20 ) ) );
		$bindings['offset'] = ai4seo_database_scalar_binding( '%d', ( $page - 1 ) * 20 );
		$rows               = ai4seo_query_taxonomy_proxy_inventory( 'SELECT p.ID' . $where . ' ORDER BY p.post_title ASC, p.ID ASC LIMIT 20 OFFSET {{offset}}', $bindings );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
	}
	$result = array(
		'rows'          => array(),
		'total'         => $total,
		'page'          => $page,
		'index_pending' => null === $progress || 'completed' !== $progress['phase'] || (int) $pending[0]['amount'] > 0 || ! empty( $unindexed ),
		'has_errors'    => null !== $progress && 'error' === $progress['last_status'],
	);
	foreach ( $rows as $row ) {
		$context = ai4seo_get_taxonomy_proxy_editor_context( (int) $row['ID'] );
		if ( is_wp_error( $context ) || $context['source']['taxonomy'] !== $taxonomy ) {
			$result['has_errors'] = true;
			continue;
		}
		if ( ai4seo_can_view_taxonomy_proxy( (int) $row['ID'], $context ) ) {
			$result['rows'][] = $context;
		}
	}
	return $result;
}


/**
 * Own the inventory across all editor writes, including instruction-only saves.
 *
 * @param int   $post_id Proxy ID.
 * @param array $updates Validated field updates.
 * @param array $request Validated editor request.
 * @param array $recovery Storage conflict choices.
 * @return array|WP_Error Existing field-save transport contract.
 */
function ai4seo_save_taxonomy_proxy_editor_values( int $post_id, array $updates, array $request, array $recovery = array() ) {
	$lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( '2' !== (string) ( $request['metadata_editor_read_version'] ?? '' ) ) {
		return new WP_Error( 'taxonomy_editor_reopen', __( 'Reopen this taxonomy entry before saving. Your current drafts have been retained.', 'ai-for-seo' ) );
	}
	if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) || ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return new WP_Error( 'taxonomy_editor_busy', __( 'The taxonomy inventory is busy. Your drafts have been retained; try saving again shortly.', 'ai-for-seo' ) );
	}
	$result = null;
	try {
		if ( ! ai4seo_can_edit_taxonomy_proxy( $post_id ) ) {
			$result = new WP_Error( 'taxonomy_editor_unavailable', __( 'This taxonomy entry can no longer be edited. Your drafts have been retained.', 'ai-for-seo' ) );
		} else {
			$result = ai4seo_save_editor_field_changes( $post_id, 'metadata', $updates, $request, $recovery );
		}
	} finally {
		$owned    = ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock );
		$released = ai4seo_release_database_advisory_lock( $lock );
	}
	if ( is_array( $result ) && ( ! $owned || ! $released ) ) {
		$result['editor_save']['warnings'][]      = __( 'The taxonomy reservation could not be verified. Confirmed values are retained; keep remaining drafts and reopen this entry.', 'ai-for-seo' );
		$result['editor_save']['reload_required'] = true;
	}
	return $result;
}


/**
 * Insert or update source-owned fields without touching generated or active SOOZ metadata.
 *
 * @param array $state Available source state.
 * @return int|WP_Error Verified proxy ID.
 */
function ai4seo_upsert_taxonomy_proxy( array $state ) {
	if ( 'available' !== ( $state['state'] ?? '' ) || ! isset( $state['source']['term_taxonomy_id'] )
		|| ! ai4seo_is_valid_taxonomy_proxy_source( $state['source'], $state['source']['term_taxonomy_id'] ) ) {
		return new WP_Error( 'taxonomy_source_invalid' );
	}
	$source = $state['source'];
	$post   = ai4seo_read_taxonomy_proxy( $source['term_taxonomy_id'] );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	if ( $post && null !== $post['source'] && ( $post['source']['taxonomy'] !== $source['taxonomy'] || $post['source']['term_id'] !== $source['term_id'] ) ) {
		return new WP_Error( 'taxonomy_proxy_identity_changed' );
	}
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
		return new WP_Error( 'taxonomy_lock_lost' );
	}
	if ( ! $post || $post['post_title'] !== $state['post_title'] || $post['post_content'] !== $state['post_content'] || 'publish' !== $post['post_status'] ) {
		$output_changed = $post && ( $post['post_title'] !== $state['post_title'] || 'publish' !== $post['post_status'] );
		$data           = array(
			'post_type'      => AI4SEO_TAXONOMY_PROXY_POST_TYPE,
			'post_name'      => 'ai4seo-taxonomy-' . $source['term_taxonomy_id'],
			'post_title'     => $state['post_title'],
			'post_content'   => $state['post_content'],
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		if ( $post ) {
			$data['ID'] = (int) $post['ID'];
			clean_post_cache( $data['ID'] );
		}
		$id = $post ? wp_update_post( wp_slash( $data ), true ) : wp_insert_post( wp_slash( $data ), true );
		if ( is_wp_error( $id ) || ! $id ) {
			return new WP_Error( 'taxonomy_post_write_failed' );
		}
		$post = ai4seo_read_taxonomy_proxy( $source['term_taxonomy_id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		if ( ! $post || (int) $post['ID'] !== $id || $post['post_title'] !== $state['post_title']
			|| $post['post_content'] !== $state['post_content'] || 'publish' !== $post['post_status'] ) {
			return new WP_Error( 'taxonomy_post_write_unconfirmed' );
		}
		// Carry the confirmed TITLE change into the source write's single old/new URL purge.
		$post['output_changed'] = $output_changed;
	}
	$result = ai4seo_store_taxonomy_proxy_source( $post, $source );
	return is_wp_error( $result ) ? $result : (int) $post['ID'];
}


/**
 * Resolve a scanned post ID back to its unique immutable key before any mutation.
 *
 * @param int $post_id Scanned post ID.
 * @return array|null|WP_Error Null means a concurrently removed post.
 */
function ai4seo_get_taxonomy_proxy_by_id( int $post_id ) {
	$rows = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT post_name, post_type FROM {{posts}} WHERE ID = {{id}} LIMIT 1',
		array(
			'posts' => ai4seo_database_identifier_binding( 'table.posts' ),
			'id'    => ai4seo_database_scalar_binding( '%d', $post_id ),
		)
	);
	if ( is_wp_error( $rows ) || ! $rows ) {
		return $rows ? $rows : null;
	}
	if ( AI4SEO_TAXONOMY_PROXY_POST_TYPE !== $rows[0]['post_type']
		|| ! preg_match( '/^ai4seo-taxonomy-([1-9][0-9]*)$/D', $rows[0]['post_name'], $matches ) ) {
		return new WP_Error( 'taxonomy_proxy_key_invalid' );
	}
	$post = ai4seo_read_taxonomy_proxy( (int) $matches[1] );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	return $post && (int) $post['ID'] === $post_id ? $post : new WP_Error( 'taxonomy_proxy_identity_changed' );
}


/**
 * Reconcile one retained proxy, including disabled providers and interrupted inserts.
 *
 * @param int   $post_id Scanned ID.
 * @param array $progress Confirmed work and optional deletion journal.
 * @param array $snapshot Expected raw checkpoint.
 * @return true|int|WP_Error
 */
function ai4seo_reconcile_taxonomy_proxy( int $post_id, array &$progress, array &$snapshot ) {
	$post = ai4seo_get_taxonomy_proxy_by_id( $post_id );
	if ( is_wp_error( $post ) || null === $post ) {
		return null === $post ? true : $post;
	}
	$id    = (int) substr( $post['post_name'], strlen( 'ai4seo-taxonomy-' ) );
	$state = ai4seo_get_taxonomy_proxy_source_state( $id, $post['source']['taxonomy'] ?? '' );
	if ( is_wp_error( $state ) ) {
		return $state;
	}
	if ( isset( $state['source'] ) && null !== $post['source'] && $state['source']['term_id'] !== $post['source']['term_id'] ) {
		return new WP_Error( 'taxonomy_proxy_identity_changed' );
	}
	if ( 'available' === $state['state'] ) {
		return ai4seo_upsert_taxonomy_proxy( $state );
	}
	if ( 'deleted' === $state['state'] ) {
		return ai4seo_delete_taxonomy_proxy( $post_id, $progress, $snapshot );
	}
	$source = $post['source'] ?? ( $state['source'] ?? null );
	if ( null === $source ) {
		return new WP_Error( 'taxonomy_proxy_source_unknown' );
	}
	$source['availability']       = 'inactive';
	$source['unavailable_reason'] = $state['reason'];
	$retired                      = ai4seo_retire_taxonomy_proxy_queue_state( $post_id );
	if ( is_wp_error( $retired ) ) {
		return $retired;
	}
	return ai4seo_store_taxonomy_proxy_source( $post, $source );
}


/**
 * Retire queue memberships under the inventory lock and the shared status fence.
 *
 * Inactive entries retain exclusions and in-flight ownership. Deletion waits for
 * both Processing membership and durable leases before removing all status data.
 *
 * @param int  $post_id Managed proxy ID.
 * @param bool $deleted Whether deletion was confirmed against the source.
 * @return true|WP_Error
 */
function ai4seo_retire_taxonomy_proxy_queue_state( int $post_id, bool $deleted = false ) {
	$lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
		return new WP_Error( 'taxonomy_lock_lost' );
	}
	$fence = ai4seo_get_post_id_option_transition_semaphore_name();
	if ( ! ai4seo_acquire_semaphore( $fence ) ) {
		return new WP_Error( 'taxonomy_queue_busy' );
	}
	try {
		if ( $deleted ) {
			$processing = ai4seo_inspect_analysis_post_id_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
			$leases     = array();
			if ( ! $processing['read_succeeded'] || ! $processing['complete']
				|| ! ai4seo_read_processing_claim_leases_under_lock( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $leases ) ) {
				return new WP_Error( 'taxonomy_queue_unreadable' );
			}
			if ( in_array( $post_id, $processing['ids'], true ) || isset( $leases[ $post_id ] ) ) {
				return new WP_Error( 'taxonomy_generation_busy' );
			}
		}
		$options  = $deleted ? AI4SEO_ALL_POST_ID_OPTIONS : array( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME );
		$removals = array_fill_keys( $options, array( $post_id ) );
		foreach ( $options as $option ) {
			$inspection = ai4seo_inspect_analysis_post_id_option( $option );
			if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
				return new WP_Error( 'taxonomy_queue_unreadable' );
			}
		}
		$changed   = false;
		$succeeded = ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock )
			&& ai4seo_apply_normalized_post_id_option_transition_under_lock( array(), $removals, $changed )
			&& ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock );
	} catch ( Throwable $exception ) {
		return new WP_Error( 'taxonomy_queue_write_unconfirmed' );
	} finally {
		$released = ai4seo_release_semaphore( $fence );
	}
	return $succeeded && $released ? true : new WP_Error( 'taxonomy_queue_write_unconfirmed' );
}


/**
 * Delete only an unambiguous proxy whose registered, eligible source is freshly confirmed absent.
 *
 * @param int   $post_id Managed proxy ID.
 * @param array $progress Confirmed work and durable deletion identity.
 * @param array $snapshot Expected raw checkpoint.
 * @return true|WP_Error
 */
function ai4seo_delete_taxonomy_proxy( int $post_id, array &$progress, array &$snapshot ) {
	$post = ai4seo_get_taxonomy_proxy_by_id( $post_id );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	if ( $post ) {
		$processing = ai4seo_inspect_analysis_post_id_option( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME );
		if ( ! $processing['read_succeeded'] || ! $processing['complete'] || in_array( $post_id, $processing['ids'], true ) ) {
			return new WP_Error( 'taxonomy_generation_busy' );
		}
	}
	$pending = $progress['pending_delete'] ?? null;
	if ( null !== $pending && $post_id !== $pending['post_id'] ) {
		return new WP_Error( 'taxonomy_pending_deletion_conflict' );
	}
	$source = $pending['source'] ?? ( $post['source'] ?? null );
	// An interrupted insert with no remaining source cannot prove its original taxonomy's eligibility.
	if ( null === $source ) {
		return new WP_Error( 'taxonomy_proxy_source_unknown' );
	}
	$expected_name = 'ai4seo-taxonomy-' . $source['term_taxonomy_id'];
	if ( $post && ( $post['post_name'] !== $expected_name
		|| ( null !== $post['source'] && $post['source'] !== $source ) ) ) {
		return new WP_Error( 'taxonomy_pending_deletion_conflict' );
	}
	$state = ai4seo_get_taxonomy_proxy_source_state( $source['term_taxonomy_id'], $source['taxonomy'] );
	if ( is_wp_error( $state ) ) {
		return $state;
	}
	if ( 'deleted' !== $state['state'] ) {
		// A provider may disappear or a source may return while a deletion is interrupted.
		// Restore the mapping if core already removed it, then let ordinary reconciliation decide.
		if ( $post ) {
			$restored = ai4seo_store_taxonomy_proxy_source( $post, $source );
			if ( is_wp_error( $restored ) ) {
				return $restored;
			}
			$progress['pending_delete'] = null;
			return ai4seo_reconcile_taxonomy_proxy( $post_id, $progress, $snapshot );
		}
		return new WP_Error( 'taxonomy_deletion_not_confirmed' );
	}
	if ( null === $pending ) {
		// WordPress deletes meta before the post row. Retain identity across failure at either boundary.
		$progress['pending_delete'] = array(
			'post_id' => $post_id,
			'source'  => $source,
		);
		$saved                      = ai4seo_save_taxonomy_proxy_sync_progress( $progress, $snapshot );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
	}
	$retired = ai4seo_retire_taxonomy_proxy_queue_state( $post_id, true );
	if ( is_wp_error( $retired ) ) {
		return $retired;
	}
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
		return new WP_Error( 'taxonomy_lock_lost' );
	}
	if ( $post ) {
		clean_post_cache( $post_id );
		if ( ! wp_delete_post( $post_id, true ) ) {
			return new WP_Error( 'taxonomy_delete_failed' );
		}
	}
	$verified = ai4seo_get_taxonomy_proxy_by_id( $post_id );
	if ( null !== $verified ) {
		return is_wp_error( $verified ) ? $verified : new WP_Error( 'taxonomy_delete_unconfirmed' );
	}
	$bindings  = array(
		'postmeta' => ai4seo_database_identifier_binding( 'table.postmeta' ),
		'id'       => ai4seo_database_scalar_binding( '%d', $post_id ),
	);
	$remaining = ai4seo_query_taxonomy_proxy_inventory( 'SELECT meta_id FROM {{postmeta}} WHERE post_id = {{id}}', $bindings );
	if ( is_wp_error( $remaining ) ) {
		return $remaining;
	}
	// Core may have ignored a failed metadata deletion; the journal proves ownership of these leftovers.
	foreach ( $remaining as $meta ) {
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
			return new WP_Error( 'taxonomy_lock_lost' );
		}
		if ( ! delete_metadata_by_mid( 'post', (int) $meta['meta_id'] ) ) {
			return new WP_Error( 'taxonomy_meta_delete_failed' );
		}
	}
	$remaining = ai4seo_query_taxonomy_proxy_inventory( 'SELECT meta_id FROM {{postmeta}} WHERE post_id = {{id}} LIMIT 1', $bindings );
	if ( is_wp_error( $remaining ) || $remaining ) {
		return is_wp_error( $remaining ) ? $remaining : new WP_Error( 'taxonomy_meta_delete_unconfirmed' );
	}
	// Repeat after core deletion so callbacks cannot leave stale queue references.
	$retired = ai4seo_retire_taxonomy_proxy_queue_state( $post_id, true );
	if ( is_wp_error( $retired ) ) {
		return $retired;
	}
	$progress['pending_delete'] = null;
	ai4seo_purge_taxonomy_archive_cache( array( $source['archive_url'] ) );
	return true;
}


/**
 * Return site-owned cache snapshots and request-local invalidation failures.
 *
 * @return array
 */
function &ai4seo_get_supported_taxonomy_terms_request_cache(): array {
	static $request_cache = array();

	return $request_cache;
}


/**
 * Validate the complete isolated cache envelope, including expired values.
 *
 * @param mixed $record Stored envelope.
 * @return bool
 */
function ai4seo_is_valid_supported_taxonomy_terms_cache_record( $record ): bool {
	return is_array( $record )
		&& array_keys( $record ) === array( 'generation', 'expires_at', 'terms' )
		&& is_string( $record['generation'] )
		&& 1 === preg_match( '/^[a-f0-9-]{36}$/D', $record['generation'] )
		&& is_int( $record['expires_at'] )
		&& 0 <= $record['expires_at']
		&& ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE, $record['terms'] );
}


/**
 * Read only the requested site's taxonomy cache and capture its publication fence.
 *
 * @param array|null $context Receives the exact scoped snapshot, including cache misses.
 * @param bool       $use_cache Whether to reuse the current request's snapshot.
 * @param bool       $require_fresh Whether expired values must be treated as misses.
 * @return array|null Terms, including an empty hit, or null on a miss/failure.
 */
function ai4seo_read_supported_taxonomy_terms_cache( &$context = null, bool $use_cache = true, bool $require_fresh = true ): ?array {
	$scope         = ai4seo_get_site_options_request_cache_scope();
	$request_cache =& ai4seo_get_supported_taxonomy_terms_request_cache();
	$context       = null;

	if ( '' === $scope || ! empty( $request_cache[ $scope ]['blocked'] ) ) {
		return null;
	}

	if ( ! $use_cache || ! isset( $request_cache[ $scope ] ) ) {
		unset( $request_cache[ $scope ] );
		$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SUPPORTED_TAXONOMY_TERMS_CACHE_OPTION_NAME );

		if ( null === $snapshot || ai4seo_get_site_options_request_cache_scope() !== $scope ) {
			return null;
		}

		$request_cache[ $scope ] = array(
			'context' => array(
				'scope'    => $scope,
				'snapshot' => $snapshot,
			),
			'valid'   => ai4seo_is_valid_supported_taxonomy_terms_cache_record( $snapshot['value'] ),
		);
	}

	$context = $request_cache[ $scope ]['context'];
	$record  = $context['snapshot']['value'];

	if ( ! $request_cache[ $scope ]['valid'] || ( $require_fresh && $record['expires_at'] <= time() ) ) {
		return null;
	}

	return $record['terms'];
}


/**
 * Publish a result only against the snapshot captured before its computation.
 *
 * @param array      $terms Validated term map.
 * @param array|null $context Original site and raw option snapshot.
 * @param int        $expires_at Absolute expiry; zero represents invalidation.
 * @return bool Whether the exact generation was replaced.
 */
function ai4seo_store_supported_taxonomy_terms_cache( array $terms, ?array $context, int $expires_at ): bool {
	$scope         = ai4seo_get_site_options_request_cache_scope();
	$request_cache =& ai4seo_get_supported_taxonomy_terms_request_cache();

	if ( ! empty( $request_cache[ $scope ]['blocked'] )
		|| '' === $scope || null === $context
		|| ( $context['scope'] ?? '' ) !== $scope || ! isset( $context['snapshot'] ) ) {
		return false;
	}

	$record = array(
		'generation' => wp_generate_uuid4(),
		'expires_at' => $expires_at,
		'terms'      => $terms,
	);

	if ( ! ai4seo_is_valid_supported_taxonomy_terms_cache_record( $record ) ) {
		return false;
	}

	$result = ai4seo_compare_and_swap_option_snapshot(
		AI4SEO_SUPPORTED_TAXONOMY_TERMS_CACHE_OPTION_NAME,
		$context['snapshot'],
		$record,
		false
	);

	// Never publish our observed value after another writer may already have replaced it.
	unset( $request_cache[ $scope ] );

	return true === $result && ai4seo_get_site_options_request_cache_scope() === $scope;
}


/**
 * Fence in-flight rebuilds with a distinct empty generation, even on repeated invalidation.
 *
 * @return bool
 */
function ai4seo_invalidate_supported_taxonomy_terms_cache(): bool {
	$scope         = ai4seo_get_site_options_request_cache_scope();
	$request_cache =& ai4seo_get_supported_taxonomy_terms_request_cache();

	if ( '' === $scope ) {
		return false;
	}

	unset( $request_cache[ $scope ] );
	$attempt_limit = ai4seo_get_environmental_variable_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		ai4seo_read_supported_taxonomy_terms_cache( $context, false );

		if ( null === $context || ai4seo_get_site_options_request_cache_scope() !== $scope ) {
			break;
		}

		if ( ai4seo_store_supported_taxonomy_terms_cache( array(), $context, 0 ) ) {
			return true;
		}
	}

	// A failed durable invalidation must not allow this request to reuse old terms.
	$request_cache[ $scope ] = array( 'blocked' => true );
	return false;
}


/**
 * Preserve named environmental mutations without putting terms back into the shared option.
 *
 * @param callable $mutation_callback Recomputed from each authoritative value.
 * @param int      $cache_ttl Relative TTL; zero preserves the existing expiry.
 * @return bool
 */
function ai4seo_mutate_supported_taxonomy_terms_cache( callable $mutation_callback, int $cache_ttl = 0 ): bool {
	$scope         = ai4seo_get_site_options_request_cache_scope();
	$attempt_limit = ai4seo_get_environmental_variable_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$terms = ai4seo_read_supported_taxonomy_terms_cache( $context, false, false );

		if ( null === $context || ai4seo_get_site_options_request_cache_scope() !== $scope ) {
			return false;
		}

		$replacement = $mutation_callback( $terms ?? array() );
		$expires_at  = 0 < $cache_ttl ? time() + $cache_ttl : ( $context['snapshot']['value']['expires_at'] ?? 0 );

		if ( ! is_array( $replacement ) || ! is_int( $expires_at ) ) {
			return false;
		}

		if ( ai4seo_store_supported_taxonomy_terms_cache( $replacement, $context, $expires_at ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Remove only obsolete derived fields, once per site/request, with ordinary CAS recovery.
 *
 * @param array $overrides Already decoded shared storage.
 * @return bool|null Cleanup result, or null when no attempt was needed/allowed.
 */
function ai4seo_remove_legacy_supported_taxonomy_terms_cache( array $overrides ): ?bool {
	static $attempted_scopes = array();
	$scope                   = ai4seo_get_site_options_request_cache_scope();
	$name                    = AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE;
	$ttl_name                = ai4seo_get_environmental_variable_ttl_name( $name );

	if ( '' === $scope || isset( $attempted_scopes[ $scope ] )
		|| ( ! array_key_exists( $name, $overrides ) && ! array_key_exists( $ttl_name, $overrides ) ) ) {
		return null;
	}

	// Recursive reconciliation may reload the option while this mutation still owns its stack.
	$attempted_scopes[ $scope ] = true;

	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $current_overrides ) use ( $name, $ttl_name, $scope ): array {
			if ( ai4seo_get_site_options_request_cache_scope() !== $scope ) {
				return array();
			}

			$changed = array_key_exists( $name, $current_overrides ) || array_key_exists( $ttl_name, $current_overrides );
			unset( $current_overrides[ $name ], $current_overrides[ $ttl_name ] );

			return array(
				'overrides' => $current_overrides,
				'changed'   => $changed,
				'result'    => null,
			);
		}
	);
}


/**
 * Clear local snapshots when ordinary WordPress writers change the isolated option.
 *
 * @param string $option_name Changed option name.
 * @return void
 */
function ai4seo_handle_supported_taxonomy_terms_option_change( string $option_name ): void {
	if ( AI4SEO_SUPPORTED_TAXONOMY_TERMS_CACHE_OPTION_NAME === $option_name ) {
		$request_cache =& ai4seo_get_supported_taxonomy_terms_request_cache();
		unset( $request_cache[ ai4seo_get_site_options_request_cache_scope() ] );
	}
}


/**
 * Register taxonomy invalidation before any admin/cron/frontend bootstrap return.
 *
 * @return void
 */
function ai4seo_add_supported_taxonomy_terms_cache_invalidation_hooks(): void {
	$environmental_variable_to_action_map = ai4seo_get_environmental_variable_to_action_cache_invalidation_map();

	foreach ( $environmental_variable_to_action_map[ AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE ] as $this_action ) {
		add_action( $this_action, 'ai4seo_invalidate_supported_taxonomy_terms_cache', 5, 0 );
	}

	// Relationship removals and final term writes can happen independently of set_object_terms.
	add_action( 'deleted_term_relationships', 'ai4seo_invalidate_supported_taxonomy_terms_cache', 5, 0 );
	add_action( 'deleted_term', 'ai4seo_invalidate_supported_taxonomy_terms_cache', 5, 0 );
	add_action( 'added_option', 'ai4seo_handle_supported_taxonomy_terms_option_change', PHP_INT_MIN, 1 );
	add_action( 'updated_option', 'ai4seo_handle_supported_taxonomy_terms_option_change', PHP_INT_MIN, 1 );
	add_action( 'deleted_option', 'ai4seo_handle_supported_taxonomy_terms_option_change', PHP_INT_MIN, 1 );
}

// endregion
// ___________________________________________________________________________________________.
