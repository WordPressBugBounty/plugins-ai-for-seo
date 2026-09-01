<?php
/**
 * Handles post discovery, types, and table analysis.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region POSTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Return the shared per-request post-context override stack.
 *
 * @return array Post IDs in push order.
 */
function &ai4seo_get_post_context_stack(): array {
	static $ai4seo_post_context_stack = array();

	return $ai4seo_post_context_stack;
}


/**
 * Return a robust Post/Page ID for the current request.
 *
 * Default: prefer the main queried object (stable even with secondary loops).
 * Falls back to the loop's global $post when requested.
 *
 * Usage: replace get_the_ID() with ai4seo_get_post_id().
 *
 * @since 2.1.4
 *
 * @param array $args {
 *     Optional behavior flags.
 *
 *     @type string $prefer   'primary' or 'loop'. Default 'primary'.
 *                            - 'primary': use main query / queried object.
 *                            - 'loop'   : use global $post first, then primary.
 *     @type string $fallback 'loop' or '0'. Default 'loop'.
 *                            What to return if no primary ID is found.
 * }
 * @return int Post ID or 0.
 */
function ai4seo_get_current_post_id( array $args = array() ): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 528983587, 'Prevented loop', true );
		return 0;
	}

	$args = wp_parse_args(
		$args,
		array(
			'prefer'   => 'primary',
			'fallback' => 'loop',
		)
	);

	// Read manual overrides from the posts-owned stack shared with the push and pop helpers.
	$ai4seo_post_context_stack = &ai4seo_get_post_context_stack();
	// If an override is active, honor it.
	if ( ! empty( $ai4seo_post_context_stack ) ) {
		$override_id = (int) end( $ai4seo_post_context_stack );

		if ( $override_id > 0 ) {
			/**
			 * Filter: allow last-chance override of the manually pushed ID.
			 *
			 * @param int   $override_id
			 * @param array $args
			 */
			return (int) apply_filters( 'ai4seo_post_id_overridden', $override_id, $args );
		}
	}

	// Cache the computed "primary" ID to avoid repeated work.
	static $ai4seo_cached_primary_id = null;

	// Helper to compute the primary (main-queried) ID once.
	$compute_primary = static function () {
		// Not for wp-admin screens (except AJAX). Keep predictable.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return 0;
		}

		$post_id = 0;

		$queried = get_queried_object();
		if ( $queried instanceof WP_Post ) {
			$post_id = (int) $queried->ID;
		} else {
			// Static "Posts page" when set and we are on the blog index.
			if ( is_home() && ! is_front_page() ) {
				$page_for_posts = (int) ai4seo_get_option( 'page_for_posts' );
				if ( $page_for_posts > 0 ) {
					$post_id = $page_for_posts;
				}
			}

			// Static "Front page" when set.
			if ( 0 === $post_id && is_front_page() ) {
				$page_on_front = (int) ai4seo_get_option( 'page_on_front' );
				if ( $page_on_front > 0 ) {
					$post_id = $page_on_front;
				}
			}

			// WooCommerce shop archive maps to a Page ID.
			if ( 0 === $post_id && function_exists( 'is_shop' ) && is_shop() ) {
				$shop_id = (int) ai4seo_get_option( 'woocommerce_shop_page_id' );
				if ( $shop_id > 0 ) {
					$post_id = $shop_id;
				}
			}
		}

		// Resolve previews that point to a revision.
		if ( $post_id > 0 ) {
			$maybe_parent = wp_is_post_revision( $post_id );
			if ( $maybe_parent ) {
				$post_id = (int) $maybe_parent;
			}
		}

		/**
		 * Filter the detected primary post ID for the current request.
		 *
		 * @param int $post_id Detected primary post ID.
		 */
		return (int) apply_filters( 'ai4seo_primary_post_id', $post_id );
	};

	// Compute or read the cached primary ID.
	if ( null === $ai4seo_cached_primary_id ) {
		$ai4seo_cached_primary_id = $compute_primary();
	}

	// Optionally use the loop's current post first.
	if ( 'loop' === $args['prefer'] ) {
		$loop_id = 0;
		/**
		 * Current global loop post.
		 *
		 * @var WP_Post|null $post Current loop post object.
		 */
		global $post;
		if ( $post instanceof WP_Post ) {
			$loop_id = (int) $post->ID;
		}
		if ( $loop_id > 0 ) {
			return (int) apply_filters( 'ai4seo_post_id_loop_preferred', $loop_id, $args );
		}
		// Fall through to primary if loop ID not available.
	}

	// Prefer primary.
	if ( $ai4seo_cached_primary_id > 0 ) {
		return (int) $ai4seo_cached_primary_id;
	}

	// Fallback strategy.
	if ( 'loop' === $args['fallback'] ) {
		$loop_id = 0;
		/**
		 * Current global loop post.
		 *
		 * @var WP_Post|null $post Current loop post object.
		 */
		global $post;
		if ( $post instanceof WP_Post ) {
			$loop_id = (int) $post->ID;
		}
		if ( $loop_id > 0 ) {
			return (int) $loop_id;
		}
	}

	return 0;
}


/**
 * Push a temporary post context ID.
 * Call before entering a custom loop; pair with ai4seo_pop_post_context().
 *
 * @param int $post_id Post ID to push onto the context stack.
 * @return void
 * @since 2.1.4
 */
function ai4seo_push_post_context( int $post_id ) {
	$ai4seo_post_context_stack   = &ai4seo_get_post_context_stack();
	$ai4seo_post_context_stack[] = (int) $post_id;
}

/**
 * Pop the last pushed post context ID.
 *
 * @since 2.1.4
 * @return void
 */
function ai4seo_pop_post_context() {
	$ai4seo_post_context_stack = &ai4seo_get_post_context_stack();

	if ( ! empty( $ai4seo_post_context_stack ) ) {
		array_pop( $ai4seo_post_context_stack );
	}
}


/**
 * Atomically stores one bounded available-author result in the environmental cache map.
 *
 * @param string $cache_key Stable query fingerprint.
 * @param array  $available_post_authors Author labels indexed by user ID.
 * @return bool True when the latest map was retained with this entry.
 */
function ai4seo_cache_available_post_authors_result( string $cache_key, array $available_post_authors ): bool {
	return ai4seo_mutate_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE,
		static function ( array $available_post_authors_cache ) use ( $cache_key, $available_post_authors ): array {
			$available_post_authors_cache[ $cache_key ] = $available_post_authors;

			if ( count( $available_post_authors_cache ) > 10 ) {
				$available_post_authors_cache = array_slice( $available_post_authors_cache, -10, null, true );
			}

			return $available_post_authors_cache;
		},
		true,
		HOUR_IN_SECONDS
	);
}


/**
 * Returns all authors that currently own entries for the given post types.
 *
 * @param array $supported_post_types Post types whose authors should be returned.
 * @param array $post_statuses Post statuses to include.
 * @return array Array of post_author IDs mapped to display labels.
 */
function ai4seo_get_available_post_authors_by_post_types( array $supported_post_types, array $post_statuses = array( 'publish', 'future' ) ): array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 748322611, 'Prevented loop', true );
		return array();
	}

	if ( ! $supported_post_types ) {
		return array();
	}

	$supported_post_types = ai4seo_deep_sanitize( $supported_post_types, 'sanitize_key' );
	$supported_post_types = array_values( array_unique( $supported_post_types ) );
	$supported_post_types = array_slice( $supported_post_types, 0, 256 );

	if ( ! $supported_post_types ) {
		return array();
	}

	$post_statuses = ai4seo_deep_sanitize( $post_statuses, 'sanitize_key' );
	$post_statuses = array_values( array_unique( $post_statuses ) );
	$post_statuses = array_slice( $post_statuses, 0, 256 );

	if ( ! $post_statuses ) {
		$post_statuses = array( 'publish', 'future' );
	}

	$available_post_authors_cache_key = md5( wp_json_encode( array( $supported_post_types, $post_statuses ) ) );

	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE ) ) {
		$available_post_authors_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE );

		if (
			is_array( $available_post_authors_cache )
			&& isset( $available_post_authors_cache[ $available_post_authors_cache_key ] )
			&& is_array( $available_post_authors_cache[ $available_post_authors_cache_key ] )
		) {
			return $available_post_authors_cache[ $available_post_authors_cache_key ];
		}
	}

	// Compile the complete author filter as typed bindings before the cacheable discovery read.
	$sql = ai4seo_prepare_database_query(
		'SELECT DISTINCT post_author
		FROM {{posts_table}}
		WHERE post_author > 0
		AND post_type IN ({{post_types}})
		AND post_status IN ({{post_statuses}})',
		array(
			'posts_table'   => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_types'    => ai4seo_database_list_binding( '%s', $supported_post_types ),
			'post_statuses' => ai4seo_database_list_binding( '%s', $post_statuses ),
		)
	);

	if ( false === $sql ) {
		ai4seo_debug_message( 984321703, 'Could not prepare the available post authors query.', true );
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; results use the one-hour author cache invalidated by post, attachment, user, plugin, and theme lifecycle hooks.
	$available_post_author_ids = $wpdb->get_col( $sql );
	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321703, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	$sanitized_post_author_ids = array();

	foreach ( (array) $available_post_author_ids as $available_post_author_id ) {
		$available_post_author_id = (int) $available_post_author_id;

		if ( $available_post_author_id <= 0 ) {
			continue;
		}

		$sanitized_post_author_ids[] = $available_post_author_id;
	}

	$sanitized_post_author_ids = array_values( array_unique( $sanitized_post_author_ids ) );

	if ( ! $sanitized_post_author_ids ) {
		$available_post_authors = array();
		ai4seo_cache_available_post_authors_result( $available_post_authors_cache_key, $available_post_authors );

		return array();
	}

	$wordpress_users = get_users(
		array(
			'include' => $sanitized_post_author_ids,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);

	$available_post_authors  = array();
	$handled_post_author_ids = array();

	foreach ( $wordpress_users as $this_wordpress_user ) {
		$this_post_author_id = (int) ( $this_wordpress_user->ID ?? 0 );

		if ( $this_post_author_id <= 0 ) {
			continue;
		}

		$handled_post_author_ids[] = $this_post_author_id;

		$this_display_name = sanitize_text_field( $this_wordpress_user->display_name ?? '' );
		$this_user_login   = sanitize_text_field( $this_wordpress_user->user_login ?? '' );
		$this_author_label = ( '' !== $this_display_name ) ? $this_display_name : $this_user_login;

		if ( '' !== $this_display_name && '' !== $this_user_login && $this_display_name !== $this_user_login ) {
			$this_author_label .= ' (' . $this_user_login . ')';
		}

		if ( '' === $this_author_label ) {
			/* translators: %d: WordPress user ID. */
			$this_author_label = sprintf( __( 'User #%d', 'ai-for-seo' ), $this_post_author_id );
		}

		$available_post_authors[ $this_post_author_id ] = $this_author_label;
	}

	$missing_post_author_ids = array_diff( $sanitized_post_author_ids, $handled_post_author_ids );

	if ( $missing_post_author_ids ) {
		sort( $missing_post_author_ids, SORT_NUMERIC );

		foreach ( $missing_post_author_ids as $missing_post_author_id ) {
			/* translators: %d: WordPress user ID. */
			$available_post_authors[ $missing_post_author_id ] = sprintf( __( 'User #%d', 'ai-for-seo' ), $missing_post_author_id );
		}
	}

	if ( $available_post_authors ) {
		asort( $available_post_authors, SORT_NATURAL | SORT_FLAG_CASE );
	}

	ai4seo_cache_available_post_authors_result( $available_post_authors_cache_key, $available_post_authors );

	return $available_post_authors;
}


/**
 * Returns all authors that currently own supported posts.
 *
 * @return array Array of post_author IDs mapped to display labels.
 */
function ai4seo_get_available_post_authors(): array {
	static $ai4seo_available_post_authors = null;

	if ( is_array( $ai4seo_available_post_authors ) ) {
		return $ai4seo_available_post_authors;
	}

	$ai4seo_available_post_authors = ai4seo_get_available_post_authors_by_post_types(
		ai4seo_get_supported_post_types( false )
	);

	return $ai4seo_available_post_authors;
}


/**
 * Returns all authors that currently own supported attachments.
 *
 * @return array Array of post_author IDs mapped to display labels.
 */
function ai4seo_get_available_attachment_post_authors(): array {
	static $ai4seo_available_attachment_post_authors = null;

	if ( is_array( $ai4seo_available_attachment_post_authors ) ) {
		return $ai4seo_available_attachment_post_authors;
	}

	$ai4seo_available_attachment_post_authors = ai4seo_get_available_post_authors_by_post_types(
		ai4seo_get_supported_attachment_post_types( false ),
		array( 'publish', 'future', 'inherit' )
	);

	return $ai4seo_available_attachment_post_authors;
}


/**
 * Returns all disabled post author IDs from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_post_author_ids(): array {
	return ai4seo_get_disabled_author_ids_by_setting_name( AI4SEO_SETTING_DISABLED_POST_AUTHORS );
}


/**
 * Returns all disabled attachment post author IDs from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_attachment_post_author_ids(): array {
	return ai4seo_get_disabled_author_ids_by_setting_name( AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS );
}


/**
 * Returns supported taxonomy terms that are connected to supported content types.
 *
 * @return array Array keyed by taxonomy name with labels and term IDs mapped to names.
 */
function ai4seo_get_supported_taxonomy_terms(): array {
	global $wpdb;

	static $ai4seo_supported_taxonomy_terms_by_site = array();

	$options_table  = isset( $wpdb->options ) ? (string) $wpdb->options : '';
	$blog_id        = absint( get_current_blog_id() );
	$site_cache_key = $options_table . '|' . $blog_id;

	if ( isset( $ai4seo_supported_taxonomy_terms_by_site[ $site_cache_key ] )
		&& is_array( $ai4seo_supported_taxonomy_terms_by_site[ $site_cache_key ] ) ) {
		return $ai4seo_supported_taxonomy_terms_by_site[ $site_cache_key ];
	}

	$ai4seo_supported_taxonomy_terms =& $ai4seo_supported_taxonomy_terms_by_site[ $site_cache_key ];

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 517322611, 'Prevented loop', true );
		return array();
	}

	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE ) ) {
		$ai4seo_supported_taxonomy_terms = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE );

		if ( is_array( $ai4seo_supported_taxonomy_terms ) ) {
			return $ai4seo_supported_taxonomy_terms;
		}
	}

	$supported_post_types = ai4seo_get_supported_post_types( false );

	if ( ! $supported_post_types ) {
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	$supported_post_types = ai4seo_deep_sanitize( $supported_post_types, 'sanitize_key' );
	$supported_post_types = array_values( array_unique( $supported_post_types ) );
	$supported_post_types = array_slice( $supported_post_types, 0, 256 );

	if ( ! $supported_post_types ) {
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	$supported_taxonomies = array();

	foreach ( $supported_post_types as $this_supported_post_type ) {
		$this_taxonomy_objects = get_object_taxonomies( $this_supported_post_type, 'objects' );

		foreach ( (array) $this_taxonomy_objects as $this_taxonomy_name => $this_taxonomy_object ) {
			$this_taxonomy_name = sanitize_key( $this_taxonomy_name );

			if ( '' === $this_taxonomy_name ) {
				continue;
			}

			if ( empty( $this_taxonomy_object->public ) || empty( $this_taxonomy_object->show_ui ) ) {
				continue;
			}

			if ( 'post_format' === $this_taxonomy_name ) {
				continue;
			}

			$this_taxonomy_label = sanitize_text_field( $this_taxonomy_object->labels->name ?? $this_taxonomy_object->label ?? $this_taxonomy_name );

			if ( '' === $this_taxonomy_label ) {
				$this_taxonomy_label = $this_taxonomy_name;
			}

			if ( ! isset( $supported_taxonomies[ $this_taxonomy_name ] ) ) {
				$supported_taxonomies[ $this_taxonomy_name ] = array(
					'label' => $this_taxonomy_label,
					'terms' => array(),
				);
			}
		}
	}

	if ( ! $supported_taxonomies ) {
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	$taxonomy_names      = array_keys( $supported_taxonomies );
	$database_chunk_size = function_exists( 'ai4seo_get_database_chunk_size' ) ? (int) ai4seo_get_database_chunk_size() : 1000;

	if ( $database_chunk_size < 1 ) {
		$database_chunk_size = 1000;
	}

	// Resolve only relationships owned by eligible content before loading their taxonomy labels.
	$sql = ai4seo_prepare_database_query(
		'SELECT DISTINCT tr.term_taxonomy_id
		FROM {{term_relationships_table}} AS tr
		INNER JOIN {{posts_table}} AS p
			ON tr.object_id = p.ID
		INNER JOIN {{term_taxonomy_table}} AS tt
			ON tr.term_taxonomy_id = tt.term_taxonomy_id
		WHERE p.post_type IN ({{post_types}})
		AND p.post_status IN ({{post_statuses}})
		AND tt.taxonomy IN ({{taxonomy_names}})',
		array(
			'term_relationships_table' => ai4seo_database_identifier_binding( 'table.term_relationships' ),
			'posts_table'              => ai4seo_database_identifier_binding( 'table.posts' ),
			'term_taxonomy_table'      => ai4seo_database_identifier_binding( 'table.term_taxonomy' ),
			'post_types'               => ai4seo_database_list_binding( '%s', $supported_post_types ),
			'post_statuses'            => ai4seo_database_list_binding( '%s', array( 'publish', 'future' ) ),
			'taxonomy_names'           => ai4seo_database_list_binding( '%s', $taxonomy_names ),
		)
	);

	if ( false === $sql ) {
		ai4seo_debug_message( 984321704, 'Could not prepare the supported taxonomy relationship query.', true );
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; results use the one-hour taxonomy cache invalidated by post, relationship, term, plugin, and theme lifecycle hooks.
	$supported_term_taxonomy_ids = $wpdb->get_col( $sql );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321704, 'Database error: ' . $wpdb->last_error, true );
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	$supported_term_taxonomy_ids = array_map( 'intval', (array) $supported_term_taxonomy_ids );
	$supported_term_taxonomy_ids = array_values( array_unique( $supported_term_taxonomy_ids ) );
	$supported_term_taxonomy_ids = array_filter(
		$supported_term_taxonomy_ids,
		static function ( $term_taxonomy_id ) {
			return $term_taxonomy_id > 0;
		}
	);

	if ( ! $supported_term_taxonomy_ids ) {
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	// Reserve the fixed taxonomy-name and identifier bindings before sizing each term-ID chunk.
	$available_term_taxonomy_id_bindings = ai4seo_get_database_placeholder_budget() - count( $taxonomy_names ) - 2;

	if ( $available_term_taxonomy_id_bindings < 1 ) {
		ai4seo_debug_message( 984321705, 'Could not fit the supported taxonomy query within the database placeholder budget.', true );
		$ai4seo_supported_taxonomy_terms = array();
		return $ai4seo_supported_taxonomy_terms;
	}

	$database_chunk_size               = min( $database_chunk_size, $available_term_taxonomy_id_bindings );
	$supported_term_taxonomy_id_chunks = array_chunk( $supported_term_taxonomy_ids, $database_chunk_size );

	foreach ( $supported_term_taxonomy_id_chunks as $this_supported_term_taxonomy_id_chunk ) {
		$sql = ai4seo_prepare_database_query(
			'SELECT tt.taxonomy, t.term_id, t.name
			FROM {{term_taxonomy_table}} AS tt
			INNER JOIN {{terms_table}} AS t
				ON t.term_id = tt.term_id
			WHERE tt.taxonomy IN ({{taxonomy_names}})
			AND tt.term_taxonomy_id IN ({{term_taxonomy_ids}})',
			array(
				'term_taxonomy_table' => ai4seo_database_identifier_binding( 'table.term_taxonomy' ),
				'terms_table'         => ai4seo_database_identifier_binding( 'table.terms' ),
				'taxonomy_names'      => ai4seo_database_list_binding( '%s', $taxonomy_names ),
				'term_taxonomy_ids'   => ai4seo_database_list_binding( '%d', $this_supported_term_taxonomy_id_chunk ),
			)
		);

		if ( false === $sql ) {
			ai4seo_debug_message( 984321705, 'Could not prepare the supported taxonomy term query.', true );
			$ai4seo_supported_taxonomy_terms = array();
			return $ai4seo_supported_taxonomy_terms;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; rows feed the one-hour taxonomy cache invalidated by post, relationship, term, plugin, and theme lifecycle hooks.
		$this_supported_taxonomy_term_rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321705, 'Database error: ' . $wpdb->last_error, true );
			$ai4seo_supported_taxonomy_terms = array();
			return $ai4seo_supported_taxonomy_terms;
		}

		// Reduce this bounded page immediately so high-cardinality term rows are released before the next query.
		foreach ( (array) $this_supported_taxonomy_term_rows as $this_supported_taxonomy_term_row ) {
			$this_taxonomy_name = sanitize_key( $this_supported_taxonomy_term_row['taxonomy'] ?? '' );
			$this_term_id       = (int) ( $this_supported_taxonomy_term_row['term_id'] ?? 0 );
			$this_term_name     = sanitize_text_field( $this_supported_taxonomy_term_row['name'] ?? '' );

			if ( '' === $this_taxonomy_name || $this_term_id <= 0 || '' === $this_term_name ) {
				continue;
			}

			if ( ! isset( $supported_taxonomies[ $this_taxonomy_name ] ) ) {
				continue;
			}

			$supported_taxonomies[ $this_taxonomy_name ]['terms'][ $this_term_id ] = $this_term_name;
		}
	}

	foreach ( $supported_taxonomies as $this_taxonomy_name => $this_supported_taxonomy ) {
		if ( empty( $this_supported_taxonomy['terms'] ) || ! is_array( $this_supported_taxonomy['terms'] ) ) {
			unset( $supported_taxonomies[ $this_taxonomy_name ] );
			continue;
		}

		asort( $supported_taxonomies[ $this_taxonomy_name ]['terms'], SORT_NATURAL | SORT_FLAG_CASE );
	}

	if ( $supported_taxonomies ) {
		uasort(
			$supported_taxonomies,
			static function ( $left, $right ) {
				$left_label  = sanitize_text_field( $left['label'] ?? '' );
				$right_label = sanitize_text_field( $right['label'] ?? '' );

				return strnatcasecmp( $left_label, $right_label );
			}
		);
	}

	$ai4seo_supported_taxonomy_terms = $supported_taxonomies;

	ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE,
		$ai4seo_supported_taxonomy_terms,
		true,
		HOUR_IN_SECONDS
	);

	return $ai4seo_supported_taxonomy_terms;
}


/**
 * Sanitizes a disabled-taxonomy-terms setting value.
 *
 * @param mixed $disabled_taxonomy_terms Raw disabled-term setting value.
 * @param bool  $restrict_to_supported_taxonomies Whether to discard unsupported taxonomies.
 * @return array
 */
function ai4seo_sanitize_disabled_taxonomy_terms_value( $disabled_taxonomy_terms, bool $restrict_to_supported_taxonomies = true ): array {
	if ( ! is_array( $disabled_taxonomy_terms ) ) {
		return array();
	}

	$supported_taxonomy_terms          = $restrict_to_supported_taxonomies ? ai4seo_get_supported_taxonomy_terms() : array();
	$sanitized_disabled_taxonomy_terms = array();

	foreach ( $disabled_taxonomy_terms as $taxonomy_name => $term_ids ) {
		$taxonomy_name = sanitize_key( $taxonomy_name );

		if ( '' === $taxonomy_name ) {
			continue;
		}

		if ( $restrict_to_supported_taxonomies && ! isset( $supported_taxonomy_terms[ $taxonomy_name ] ) ) {
			continue;
		}

		if ( ! is_array( $term_ids ) ) {
			$term_ids = $term_ids ? array( $term_ids ) : array();
		}

		$sanitized_term_ids = array();

		foreach ( $term_ids as $term_id ) {
			$term_id = (int) $term_id;

			if ( $term_id <= 0 ) {
				continue;
			}

			$sanitized_term_ids[] = $term_id;
		}

		$sanitized_term_ids = array_values( array_unique( $sanitized_term_ids ) );
		sort( $sanitized_term_ids, SORT_NUMERIC );

		if ( ! $sanitized_term_ids ) {
			continue;
		}

		$sanitized_disabled_taxonomy_terms[ $taxonomy_name ] = $sanitized_term_ids;
	}

	return $sanitized_disabled_taxonomy_terms;
}


/**
 * Returns all disabled taxonomy terms from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_taxonomy_terms(): array {
	return ai4seo_sanitize_disabled_taxonomy_terms_value( ai4seo_get_setting( AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ) );
}


/**
 * Returns the persisted disabled taxonomy policy for generation enforcement.
 *
 * Live taxonomy discovery must not erase the configured policy when its database read fails.
 *
 * @return array
 */
function ai4seo_get_enforced_disabled_taxonomy_terms(): array {
	return ai4seo_sanitize_disabled_taxonomy_terms_value( ai4seo_get_setting( AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ), false );
}


/**
 * Builds constant-time term membership lookups for a normalized disabled-term policy.
 *
 * @param array $disabled_taxonomy_terms Disabled term IDs grouped by taxonomy.
 * @return array Disabled term membership lookups grouped by taxonomy.
 */
function ai4seo_get_disabled_taxonomy_term_id_lookups( array $disabled_taxonomy_terms ): array {
	$disabled_term_id_lookups = array();

	foreach ( $disabled_taxonomy_terms as $taxonomy_name => $term_ids ) {
		$disabled_term_id_lookups[ $taxonomy_name ] = array_fill_keys( $term_ids, true );
	}

	return $disabled_term_id_lookups;
}


/**
 * Returns whether posts should be excluded as soon as they match any disabled taxonomy term.
 *
 * @return bool
 */
function ai4seo_should_exclude_posts_if_any_disabled_taxonomy_term_matches(): bool {
	return (bool) ai4seo_get_setting( AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM );
}


/**
 * Processes relevant taxonomy assignments one bounded relationship-row page at a time.
 *
 * @param array    $post_ids Candidate post IDs normalized by the caller.
 * @param array    $taxonomy_names Relevant taxonomy names.
 * @param callable $assignment_chunk_processor Receives one bounded assignment-row result set.
 * @return bool True after every chunk is processed, or false on preparation, execution, or row-validation failure.
 */
function ai4seo_process_taxonomy_assignment_chunks_for_post_ids( array $post_ids, array $taxonomy_names, callable $assignment_chunk_processor ): bool {
	global $wpdb;

	if ( ! $post_ids || ! $taxonomy_names ) {
		return true;
	}

	$post_ids = ai4seo_normalize_database_ids( $post_ids );

	if ( false === $post_ids || ! $post_ids ) {
		return false;
	}

	// Normalize taxonomy names once so every candidate chunk uses the same closed filter set.
	$taxonomy_names = ai4seo_deep_sanitize( $taxonomy_names, 'sanitize_key' );
	$taxonomy_names = array_values( array_unique( array_filter( $taxonomy_names ) ) );

	if ( ! $taxonomy_names ) {
		return true;
	}

	$taxonomy_name_lookup = array_fill_keys( $taxonomy_names, true );

	// Reserve taxonomy, table, keyset-cursor, and row-limit bindings before candidate IDs consume the budget.
	$available_post_id_bindings = ai4seo_get_database_placeholder_budget() - count( $taxonomy_names ) - 6;

	if ( $available_post_id_bindings < 1 ) {
		return false;
	}

	$post_id_chunk_size = function_exists( 'ai4seo_get_database_chunk_size' ) ? (int) ai4seo_get_database_chunk_size() : 1000;

	if ( $post_id_chunk_size < 1 ) {
		$post_id_chunk_size = 1000;
	}

	// Bound both candidate lists and returned relationship pages even when the general setting is larger.
	$post_id_chunk_size   = min( $post_id_chunk_size, $available_post_id_bindings, 1000 );
	$assignment_row_limit = min( $post_id_chunk_size, 1000 );

	foreach ( array_chunk( $post_ids, $post_id_chunk_size ) as $this_post_id_chunk ) {
		$this_post_id_lookup     = array_fill_keys( $this_post_id_chunk, true );
		$object_id_cursor        = 0;
		$term_taxonomy_id_cursor = 0;

		do {
			$sql = ai4seo_prepare_database_query(
				'SELECT tr.object_id, tt.taxonomy, tt.term_id, tr.term_taxonomy_id
				FROM {{term_relationships_table}} AS tr
				INNER JOIN {{term_taxonomy_table}} AS tt
					ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tr.object_id IN ({{post_ids}})
				AND tt.taxonomy IN ({{taxonomy_names}})
				AND (
					tr.object_id > {{object_id_cursor_after}}
					OR (
						tr.object_id = {{object_id_cursor_equal}}
						AND tr.term_taxonomy_id > {{term_taxonomy_id_cursor}}
					)
				)
				ORDER BY tr.object_id ASC, tr.term_taxonomy_id ASC
				LIMIT {{query_limit}}',
				array(
					'term_relationships_table' => ai4seo_database_identifier_binding( 'table.term_relationships' ),
					'term_taxonomy_table'      => ai4seo_database_identifier_binding( 'table.term_taxonomy' ),
					'post_ids'                 => ai4seo_database_list_binding( '%d', $this_post_id_chunk ),
					'taxonomy_names'           => ai4seo_database_list_binding( '%s', $taxonomy_names ),
					'object_id_cursor_after'   => ai4seo_database_scalar_binding( '%d', $object_id_cursor ),
					'object_id_cursor_equal'   => ai4seo_database_scalar_binding( '%d', $object_id_cursor ),
					'term_taxonomy_id_cursor'  => ai4seo_database_scalar_binding( '%d', $term_taxonomy_id_cursor ),
					'query_limit'              => ai4seo_database_scalar_binding( '%d', $assignment_row_limit ),
				)
			);

			if ( false === $sql ) {
				return false;
			}

			$wpdb->last_error = '';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; the composite primary-key cursor and LIMIT bound each current relationship page.
			$this_assignment_rows = $wpdb->get_results( $sql, ARRAY_A );

			if ( $wpdb->last_error || ! is_array( $this_assignment_rows ) ) {
				return false;
			}

			if ( ! $this_assignment_rows ) {
				break;
			}

			$this_assignment_row_count = count( $this_assignment_rows );

			if ( $this_assignment_row_count > $assignment_row_limit ) {
				return false;
			}

			$this_public_assignment_rows = array();
			$next_object_id_cursor       = $object_id_cursor;
			$next_term_taxonomy_cursor   = $term_taxonomy_id_cursor;

			foreach ( $this_assignment_rows as $this_assignment_row ) {
				if ( ! is_array( $this_assignment_row )
					|| ! array_key_exists( 'object_id', $this_assignment_row )
					|| ! array_key_exists( 'taxonomy', $this_assignment_row )
					|| ! array_key_exists( 'term_id', $this_assignment_row )
					|| ! array_key_exists( 'term_taxonomy_id', $this_assignment_row )
					|| ! is_string( $this_assignment_row['taxonomy'] ) ) {
					return false;
				}

				$this_object_id        = ai4seo_normalize_database_id( $this_assignment_row['object_id'] ?? null );
				$this_term_id          = ai4seo_normalize_database_id( $this_assignment_row['term_id'] ?? null );
				$this_term_taxonomy_id = ai4seo_normalize_database_id( $this_assignment_row['term_taxonomy_id'] ?? null );
				$this_taxonomy_name    = $this_assignment_row['taxonomy'];

				if ( false === $this_object_id
					|| false === $this_term_id
					|| false === $this_term_taxonomy_id
					|| ! isset( $this_post_id_lookup[ $this_object_id ] )
					|| sanitize_key( $this_taxonomy_name ) !== $this_taxonomy_name
					|| ! isset( $taxonomy_name_lookup[ $this_taxonomy_name ] ) ) {
					return false;
				}

				if ( $this_object_id < $next_object_id_cursor
					|| ( $this_object_id === $next_object_id_cursor && $this_term_taxonomy_id <= $next_term_taxonomy_cursor ) ) {
					return false;
				}

				$next_object_id_cursor     = $this_object_id;
				$next_term_taxonomy_cursor = $this_term_taxonomy_id;

				unset( $this_assignment_row['term_taxonomy_id'] );
				$this_public_assignment_rows[] = $this_assignment_row;
			}

			$assignment_chunk_processor( $this_public_assignment_rows );

			$object_id_cursor        = $next_object_id_cursor;
			$term_taxonomy_id_cursor = $next_term_taxonomy_cursor;

			// Release high-cardinality relationship rows before the next keyset page is queried.
			unset( $this_assignment_rows, $this_public_assignment_rows );

			if ( $this_assignment_row_count < $assignment_row_limit ) {
				break;
			}
		} while ( true );
	}

	return true;
}


/**
 * Reads relevant taxonomy assignments for a bounded set of candidate posts.
 *
 * This compatibility reader retains the raw-row return contract. Production ANY/ONLY matching uses
 * the chunk processor directly so relationship rows are reduced and released after every query.
 *
 * @param array $post_ids Candidate post IDs normalized by the caller.
 * @param array $taxonomy_names Relevant taxonomy names.
 * @return array|false Assignment rows, or false when preparation or execution fails.
 */
function ai4seo_read_taxonomy_assignments_for_post_ids( array $post_ids, array $taxonomy_names ) {
	$assignment_rows = array();
	$read_succeeded  = ai4seo_process_taxonomy_assignment_chunks_for_post_ids(
		$post_ids,
		$taxonomy_names,
		static function ( array $this_assignment_rows ) use ( &$assignment_rows ): void {
			foreach ( $this_assignment_rows as $this_assignment_row ) {
				$assignment_rows[] = $this_assignment_row;
			}
		}
	);

	return $read_succeeded ? $assignment_rows : false;
}

/**
 * Returns post IDs whose assigned taxonomy terms include at least one disabled term.
 *
 * @param array     $post_ids Candidate post IDs.
 * @param array     $disabled_taxonomy_terms Disabled terms grouped by taxonomy.
 * @param bool|null $read_succeeded Receives whether every taxonomy-assignment read succeeded.
 * @return array
 */
function ai4seo_get_post_ids_with_any_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;
	$post_ids       = array_map( 'intval', $post_ids );
	$post_ids       = array_values( array_unique( $post_ids ) );
	$post_ids       = array_values(
		array_filter(
			$post_ids,
			static function ( $post_id ) {
				return $post_id > 0;
			}
		)
	);

	// Enforcement must retain the persisted policy when live taxonomy discovery is unavailable.
	$disabled_taxonomy_terms = ai4seo_sanitize_disabled_taxonomy_terms_value( $disabled_taxonomy_terms, false );

	if ( ! $post_ids || ! $disabled_taxonomy_terms ) {
		$read_succeeded = true;
		return array();
	}

	// Reuse the normalized policy lookup while reducing each streamed assignment in constant time.
	$disabled_term_id_lookups = ai4seo_get_disabled_taxonomy_term_id_lookups( $disabled_taxonomy_terms );

	$excluded_post_id_lookup = array();
	$read_succeeded          = ai4seo_process_taxonomy_assignment_chunks_for_post_ids(
		$post_ids,
		array_keys( $disabled_taxonomy_terms ),
		static function ( array $this_assignment_rows ) use ( $disabled_term_id_lookups, &$excluded_post_id_lookup ): void {
			foreach ( $this_assignment_rows as $assignment_row ) {
				$object_id     = (int) ( $assignment_row['object_id'] ?? 0 );
				$taxonomy_name = sanitize_key( $assignment_row['taxonomy'] ?? '' );
				$term_id       = (int) ( $assignment_row['term_id'] ?? 0 );

				if ( $object_id <= 0 || $term_id <= 0 || ! isset( $disabled_term_id_lookups[ $taxonomy_name ][ $term_id ] ) ) {
					continue;
				}

				$excluded_post_id_lookup[ $object_id ] = true;
			}
		}
	);

	if ( ! $read_succeeded ) {
		ai4seo_debug_message( 984321707, 'Database error: ' . $wpdb->last_error, true );

		// Fail closed so a database problem cannot generate content excluded by the user's taxonomy policy.
		sort( $post_ids, SORT_NUMERIC );
		return $post_ids;
	}

	$excluded_post_ids = array_map( 'intval', array_keys( $excluded_post_id_lookup ) );
	sort( $excluded_post_ids, SORT_NUMERIC );

	return $excluded_post_ids;
}


/**
 * Returns post IDs whose assigned taxonomy terms are all disabled.
 *
 * Posts without any assigned relevant taxonomy term are not returned.
 *
 * @param array     $post_ids Candidate post IDs.
 * @param array     $disabled_taxonomy_terms Disabled terms grouped by taxonomy.
 * @param bool|null $read_succeeded Receives whether every taxonomy-assignment read succeeded.
 * @return array
 */
function ai4seo_get_post_ids_with_only_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;
	$post_ids       = array_map( 'intval', $post_ids );
	$post_ids       = array_values( array_unique( $post_ids ) );
	$post_ids       = array_values(
		array_filter(
			$post_ids,
			static function ( $post_id ) {
				return $post_id > 0;
			}
		)
	);

	// Enforcement must retain the persisted policy when live taxonomy discovery is unavailable.
	$disabled_taxonomy_terms = ai4seo_sanitize_disabled_taxonomy_terms_value( $disabled_taxonomy_terms, false );

	if ( ! $post_ids || ! $disabled_taxonomy_terms ) {
		$read_succeeded = true;
		return array();
	}

	// Reuse the normalized policy lookup while classifying each streamed assignment in constant time.
	$disabled_term_id_lookups = ai4seo_get_disabled_taxonomy_term_id_lookups( $disabled_taxonomy_terms );

	$post_ids_with_relevant_terms = array();
	$post_ids_with_enabled_terms  = array();
	$read_succeeded               = ai4seo_process_taxonomy_assignment_chunks_for_post_ids(
		$post_ids,
		array_keys( $disabled_taxonomy_terms ),
		static function ( array $this_assignment_rows ) use ( $disabled_term_id_lookups, &$post_ids_with_relevant_terms, &$post_ids_with_enabled_terms ): void {
			foreach ( $this_assignment_rows as $assignment_row ) {
				$object_id     = (int) ( $assignment_row['object_id'] ?? 0 );
				$taxonomy_name = sanitize_key( $assignment_row['taxonomy'] ?? '' );
				$term_id       = (int) ( $assignment_row['term_id'] ?? 0 );

				if ( $object_id <= 0 || $term_id <= 0 || ! isset( $disabled_term_id_lookups[ $taxonomy_name ] ) ) {
					continue;
				}

				$post_ids_with_relevant_terms[ $object_id ] = true;

				if ( ! isset( $disabled_term_id_lookups[ $taxonomy_name ][ $term_id ] ) ) {
					$post_ids_with_enabled_terms[ $object_id ] = true;
				}
			}
		}
	);

	if ( ! $read_succeeded ) {
		ai4seo_debug_message( 984321706, 'Database error: ' . $wpdb->last_error, true );

		// Fail closed so a database problem cannot generate content excluded by the user's taxonomy policy.
		sort( $post_ids, SORT_NUMERIC );
		return $post_ids;
	}

	// Finalize only after every keyset page so one post split across pages retains enabled-term state.
	$excluded_post_ids = array_map(
		'intval',
		array_keys( array_diff_key( $post_ids_with_relevant_terms, $post_ids_with_enabled_terms ) )
	);
	sort( $excluded_post_ids, SORT_NUMERIC );
	return $excluded_post_ids;
}


/**
 * Returns all post IDs that should be excluded by the disabled-taxonomy-terms setting mode.
 *
 * @param array     $post_ids Candidate post IDs.
 * @param array     $disabled_taxonomy_terms Disabled terms grouped by taxonomy.
 * @param bool|null $exclude_on_any_disabled_taxonomy_term Optional. Defaults to the user setting.
 * @param bool|null $read_succeeded Receives whether every taxonomy-assignment read succeeded.
 * @return array
 */
function ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool $exclude_on_any_disabled_taxonomy_term = null, ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;

	if ( null === $exclude_on_any_disabled_taxonomy_term ) {
		$exclude_on_any_disabled_taxonomy_term = ai4seo_should_exclude_posts_if_any_disabled_taxonomy_term_matches();
	}

	if ( $exclude_on_any_disabled_taxonomy_term ) {
		return ai4seo_get_post_ids_with_any_disabled_taxonomy_terms( $post_ids, $disabled_taxonomy_terms, $read_succeeded );
	}

	return ai4seo_get_post_ids_with_only_disabled_taxonomy_terms( $post_ids, $disabled_taxonomy_terms, $read_succeeded );
}


/**
 * Filters out posts based on the disabled-taxonomy-terms setting mode.
 *
 * @param array     $post_ids Candidate post IDs.
 * @param array     $disabled_taxonomy_terms Disabled terms grouped by taxonomy.
 * @param bool|null $exclude_on_any_disabled_taxonomy_term Optional. Defaults to the user setting.
 * @param bool|null $read_succeeded Receives whether every taxonomy-assignment read succeeded.
 * @return array
 */
function ai4seo_filter_post_ids_by_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool $exclude_on_any_disabled_taxonomy_term = null, ?bool &$read_succeeded = null ): array {
	$excluded_post_ids = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms(
		$post_ids,
		$disabled_taxonomy_terms,
		$exclude_on_any_disabled_taxonomy_term,
		$read_succeeded
	);

	if ( ! $excluded_post_ids ) {
		return array_values( $post_ids );
	}

	$excluded_post_ids = array_flip( array_map( 'intval', $excluded_post_ids ) );
	$filtered_post_ids = array();

	foreach ( $post_ids as $post_id ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 || isset( $excluded_post_ids[ $post_id ] ) ) {
			continue;
		}

		$filtered_post_ids[] = $post_id;
	}

	return $filtered_post_ids;
}


/**
 * Returns all disabled author IDs from a setting.
 *
 * @param string $setting_name Disabled-author setting name.
 * @return array
 */
function ai4seo_get_disabled_author_ids_by_setting_name( string $setting_name ): array {
	$disabled_post_author_ids = ai4seo_get_setting( $setting_name );

	if ( ! is_array( $disabled_post_author_ids ) ) {
		return array();
	}

	$sanitized_post_author_ids = array();

	foreach ( $disabled_post_author_ids as $disabled_post_author_id ) {
		$disabled_post_author_id = (int) $disabled_post_author_id;

		if ( $disabled_post_author_id <= 0 ) {
			continue;
		}

		$sanitized_post_author_ids[] = $disabled_post_author_id;
	}

	$sanitized_post_author_ids = array_values( array_unique( $sanitized_post_author_ids ) );
	sort( $sanitized_post_author_ids, SORT_NUMERIC );

	return $sanitized_post_author_ids;
}


/**
 * Returns public post types that WordPress can expose through normal front-end requests.
 *
 * @return array Post type labels keyed by post type name.
 */
function ai4seo_get_publicly_accessible_post_types(): array {
	// Exclude WordPress internals and plugin-managed pseudo-types that should never enter SEO analysis.
	$excluded_post_types = array(
		'attachment',
		'ai4seo_ngg', // NextGEN Gallery.
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'template',
		'wp_block',
	);

	// Start with WordPress's public registry so custom post type visibility remains owned by core.
	$args = array(
		'public' => true,
	);

	$post_types                     = get_post_types( $args, 'objects' );
	$publicly_accessible_post_types = array();

	foreach ( $post_types as $post_type ) {
		// Custom post types also need both a public query route and rewrite configuration.
		if ( ! $post_type->_builtin && ! $post_type->publicly_queryable ) {
			continue;
		}

		if ( ! $post_type->_builtin && ! $post_type->rewrite ) {
			continue;
		}

		// Keep the explicit exclusions separate from registry flags because several are public in some setups.
		if ( in_array( (string) $post_type->name, $excluded_post_types, true ) ) {
			continue;
		}

		// Retain types that WordPress can surface through archives, normal posts, or search.
		if ( $post_type->has_archive || 'post' === $post_type->capability_type || ! $post_type->exclude_from_search ) {
			$publicly_accessible_post_types[ $post_type->name ] = $post_type->label;
		}
	}

	return $publicly_accessible_post_types;
}


/**
 * Determines whether a registered post type can use the metadata editor.
 *
 * Editor availability follows registry and settings state without requiring an existing published
 * or future post, which keeps the first draft of a public post type inside the same save contract.
 *
 * @param string $post_type Post type identifier.
 * @return bool Whether the metadata editor is enabled for the post type.
 */
function ai4seo_is_metadata_editor_enabled_for_post_type( string $post_type ): bool {
	if ( '' === $post_type || sanitize_key( $post_type ) !== $post_type ) {
		return false;
	}

	$publicly_accessible_post_types = ai4seo_get_publicly_accessible_post_types();

	if ( ! isset( $publicly_accessible_post_types[ $post_type ] ) || ! ai4seo_get_active_meta_tags() ) {
		return false;
	}

	$disabled_post_types = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_POST_TYPES );

	if ( ! is_array( $disabled_post_types ) ) {
		$disabled_post_types = array();
	} else {
		$disabled_post_types = ai4seo_deep_sanitize( $disabled_post_types, 'sanitize_key' );
	}

	return ! in_array( $post_type, $disabled_post_types, true );
}


/**
 * Returns the exact active-site scope for supported-post-type request caches.
 *
 * @return string Options-table and blog identity.
 */
function ai4seo_get_supported_post_types_cache_scope(): string {
	global $wpdb;

	$options_table = is_object( $wpdb ) && isset( $wpdb->options ) && is_string( $wpdb->options )
		? $wpdb->options
		: '';
	$blog_id       = function_exists( 'get_current_blog_id' ) ? absint( get_current_blog_id() ) : 0;

	return $options_table . '|' . $blog_id;
}


/**
 * Returns all supported post types for this WordPress setup
 *
 * @param bool $apply_user_setting Whether to filter out user-disabled post types.
 * @return array The supported post types
 */
function ai4seo_get_supported_post_types( bool $apply_user_setting = true ): array {
	global $ai4seo_cached_supported_post_types;
	global $ai4seo_checked_supported_post_types;
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 327975848, 'Prevented loop', true );
		return array();
	}

	$site_cache_key = ai4seo_get_supported_post_types_cache_scope();

	// Upgrade request state that was initialized with the former flat-list shape.
	foreach ( array( 'ai4seo_cached_supported_post_types', 'ai4seo_checked_supported_post_types' ) as $cache_global_name ) {
		$cache_by_site =& $GLOBALS[ $cache_global_name ];
		$is_scoped_map = is_array( $cache_by_site );

		if ( $is_scoped_map ) {
			foreach ( $cache_by_site as $scope_key => $site_values ) {
				if ( ! is_string( $scope_key ) || false === strpos( $scope_key, '|' ) || ! is_array( $site_values ) ) {
					$is_scoped_map = false;
					break;
				}
			}
		}

		if ( ! $is_scoped_map ) {
			$legacy_values = is_array( $cache_by_site ) ? $cache_by_site : array();
			$cache_by_site = array(
				$site_cache_key => $legacy_values,
			);
		}

		if ( ! isset( $cache_by_site[ $site_cache_key ] ) || ! is_array( $cache_by_site[ $site_cache_key ] ) ) {
			$cache_by_site[ $site_cache_key ] = array();
		}

		unset( $cache_by_site );
	}

	$cached_supported_post_types  =& $ai4seo_cached_supported_post_types[ $site_cache_key ];
	$checked_supported_post_types =& $ai4seo_checked_supported_post_types[ $site_cache_key ];

	$publicly_accessible_post_types = ai4seo_get_publicly_accessible_post_types();

	$check_this_post_types   = array_keys( $publicly_accessible_post_types );
	$check_this_post_types   = ai4seo_deep_sanitize( $check_this_post_types, 'sanitize_key' );
	$check_this_post_types   = array_values( array_unique( $check_this_post_types ) );
	$public_post_type_lookup = array_fill_keys( $check_this_post_types, true );

	// Exclude only post types already checked against this site's authoritative posts table.
	if ( $checked_supported_post_types ) {
		$check_this_post_types = array_diff( $check_this_post_types, $checked_supported_post_types );
	}

	if ( ! $check_this_post_types ) {
		$supported_post_types = $cached_supported_post_types;
	} else {
		// Keep existing behavior (require at least one post). If you want empty CPTs too, replace the DB query with $check_this_post_types.
		// Bound the pending batch before advancing checked state so later calls can discover the remainder.
		$check_this_post_types       = array_slice( array_values( $check_this_post_types ), 0, 256 );
		$check_this_post_type_lookup = array_fill_keys( $check_this_post_types, true );
		$validated_supported_types   = array();
		$discovery_read_succeeded    = false;
		$used_environmental_cache    = false;
		$validate_discovery_rows     = static function ( $rows, array $allowed_post_type_lookup, array &$validated_rows ): bool {
			$validated_rows = array();
			$seen_rows      = array();

			if ( ! is_array( $rows ) ) {
				return false;
			}

			foreach ( $rows as $post_type ) {
				if (
					! is_string( $post_type )
					|| '' === $post_type
					|| sanitize_key( $post_type ) !== $post_type
					|| ! isset( $allowed_post_type_lookup[ $post_type ] )
					|| isset( $seen_rows[ $post_type ] )
				) {
					$validated_rows = array();
					return false;
				}

				$seen_rows[ $post_type ] = true;
				$validated_rows[]        = $post_type;
			}

			return true;
		};

		// A persistent cache represents a complete earlier discovery snapshot. Once this request has
		// checked a batch, query every newly observed batch so cached bytes cannot suppress incremental work.
		if (
			! $checked_supported_post_types
			&& ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE )
		) {
			$environmental_supported_types = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE );
			$discovery_read_succeeded      = $validate_discovery_rows(
				$environmental_supported_types,
				$public_post_type_lookup,
				$validated_supported_types
			);
			$used_environmental_cache      = $discovery_read_succeeded;
		}

		if ( ! $discovery_read_succeeded ) {
			$sql = ai4seo_prepare_database_query(
				'SELECT DISTINCT p.post_type
				FROM {{posts_table}} AS p
				WHERE p.post_type IN ({{post_types}})
				AND p.post_status IN ({{post_statuses}})
				LIMIT {{result_limit}}',
				array(
					'posts_table'   => ai4seo_database_identifier_binding( 'table.posts' ),
					'post_types'    => ai4seo_database_list_binding( '%s', $check_this_post_types ),
					'post_statuses' => ai4seo_database_list_binding( '%s', array( 'publish', 'future' ) ),
					'result_limit'  => ai4seo_database_scalar_binding( '%d', count( $check_this_post_types ) ),
				)
			);

			if ( false === $sql ) {
				ai4seo_debug_message( 984321677, 'Could not prepare the supported post types query.', true );
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; results use the one-hour supported-type cache invalidated by plugin, theme, post-deletion, and status-transition hooks.
				$supported_post_type_rows = $wpdb->get_col( $sql );

				if ( $wpdb->last_error ) {
					ai4seo_debug_message( 984321677, 'Database error: ' . $wpdb->last_error, true );
				} elseif ( ! $validate_discovery_rows( $supported_post_type_rows, $check_this_post_type_lookup, $validated_supported_types ) ) {
					ai4seo_debug_message( 984321677, 'The supported post types query returned malformed or out-of-scope rows.', true );
				} else {
					$discovery_read_succeeded = true;
				}
			}
		}

		if ( ! $discovery_read_succeeded ) {
			$supported_post_types = $cached_supported_post_types;
		} else {
			// Advance only the exact batch backed by a validated cache or authoritative database read.
			$checked_supported_post_types = array_values( array_unique( array_merge( $checked_supported_post_types, $check_this_post_types ) ) );

			// Merge this incremental result only into the active site's request cache.
			$cached_supported_post_types = array_merge( $cached_supported_post_types, $validated_supported_types );
			$cached_supported_post_types = array_values( array_unique( $cached_supported_post_types ) );

			// order the post types.
			sort( $cached_supported_post_types );

			// Persist the cumulative request result after authoritative incremental reads so a later
			// request never replaces an earlier batch with only the final batch's positive rows.
			if ( ! $used_environmental_cache ) {
				ai4seo_update_environmental_variable(
					AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE,
					$cached_supported_post_types,
					true,
					HOUR_IN_SECONDS
				);
			}

			$supported_post_types = $cached_supported_post_types;
		}
	}

	if ( ! isset( $supported_post_types ) ) {
		$supported_post_types = $cached_supported_post_types;
	}

	if ( ! $apply_user_setting ) {
		return $supported_post_types;
	}

	// check active meta tags.
	$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

	if ( ! $ai4seo_active_meta_tags ) {
		return array();
	}

	// check disabled post types.
	$disabled_post_types = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_POST_TYPES );

	if ( ! is_array( $disabled_post_types ) ) {
		$disabled_post_types = array();
	} else {
		$disabled_post_types = ai4seo_deep_sanitize( $disabled_post_types, 'sanitize_key' );
	}

	if ( empty( $disabled_post_types ) ) {
		return $supported_post_types;
	}

	$supported_post_types = array_values( array_diff( $supported_post_types, $disabled_post_types ) );

	return $supported_post_types;
}


/**
 * Retrieve the translation for the different content types
 *
 * @param mixed $post_type The post type value.
 * @param bool  $count_or_plural The count or plural value.
 * @return string The translation
 */
function ai4seo_get_post_type_translation( $post_type, $count_or_plural = false ): string {
	$post_type_original = $post_type;
	$post_type          = strtolower( $post_type );
	$translation        = $post_type_original;

	switch ( $post_type ) {
		case 'post':
		case 'posts':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'posts', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'post', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of posts. */
				$translation = sprintf(
					/* translators: %s: Number of posts. */
					_nx( '%1$s post', '%1$s posts', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'page':
		case 'pages':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'pages', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'page', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of pages. */
				$translation = sprintf(
					/* translators: %s: Number of pages. */
					_nx( '%1$s page', '%1$s pages', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'product':
		case 'products':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'products', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'product', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of products. */
				$translation = sprintf(
					/* translators: %s: Number of products. */
					_nx( '%1$s product', '%1$s products', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'portfolio':
		case 'portfolios':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'portfolios', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'portfolio', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of portfolios. */
				$translation = sprintf(
					/* translators: %s: Number of portfolios. */
					_nx( '%1$s portfolio', '%1$s portfolios', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'attachment':
		case 'attachments':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'attachments', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'attachment', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of attachments. */
				$translation = sprintf(
					/* translators: %s: Number of attachments. */
					_nx( '%1$s attachment', '%1$s attachments', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		// Media is not a post type, but names attachments consistently in user-facing text.
		case 'media':
		case 'medias':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = _n( 'medium', 'media', 2, 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = _n( 'medium', 'media', 1, 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of media items. */
				$translation = sprintf(
					/* translators: %s: Number of media items. */
					_nx( '%1$s medium', '%1$s media', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		// Media file is not a post type, but provides a more specific attachment label where needed.
		case 'media file':
		case 'media files':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'media files', 'ai-for-seo' );
			} elseif ( false === $count_or_plural ) {
				// Singular.
				$translation = __( 'media file', 'ai-for-seo' );
			} else {
				// Singular or plural with count.
				/* translators: %s: Number of media files. */
				$translation = sprintf(
					/* translators: %s: Number of media files. */
					_nx( '%1$s media file', '%1$s media files', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		default:
			// Unknown content types remain unchanged unless a numeric count needs to be prefixed.
			if ( is_numeric( $count_or_plural ) ) {
				$translation = $count_or_plural . ' ' . $post_type_original;
			}
	}

	return $translation;
}


/**
 * Return builder postmeta keys that can contribute required local generation content.
 *
 * @param string $editor_identifier Optional single builder identifier.
 * @return array<int,string> Active exact postmeta keys.
 */
function ai4seo_get_active_metadata_generation_builder_meta_keys( string $editor_identifier = '' ): array {
	$plugins_are_loaded = function_exists( 'is_plugin_active' );
	$current_theme      = wp_get_theme();
	$parent_theme       = $current_theme->parent();
	$builder_meta_keys  = array();

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'elementor' === $editor_identifier ) && is_plugin_active( 'elementor/elementor.php' ) ) {
		$builder_meta_keys[] = '_elementor_data';
	}

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'mfn-builder' === $editor_identifier ) && ( 'Betheme' === $current_theme->get( 'Name' )
		|| ( $parent_theme && 'Betheme' === $parent_theme->get( 'Name' ) ) ) ) {
		$builder_meta_keys[] = 'mfn-page-items-seo';
	}

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'fl-builder' === $editor_identifier ) && is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) ) {
		$builder_meta_keys[] = '_fl_builder_data';
	}

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'divi-builder' === $editor_identifier ) && is_plugin_active( 'divi-builder/divi-builder.php' ) ) {
		$builder_meta_keys[] = '_et_pb_use_builder';
	}

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'oxygen' === $editor_identifier ) && is_plugin_active( 'oxygen/functions.php' ) ) {
		$builder_meta_keys[] = 'ct_builder_shortcodes';
	}

	if ( $plugins_are_loaded && ( ! $editor_identifier || 'brizy' === $editor_identifier ) && is_plugin_active( 'brizy/brizy.php' ) ) {
		$builder_meta_keys[] = 'brizy_post_uid';
	}

	return array_values( array_unique( $builder_meta_keys ) );
}


/**
 * Read the required post row and active builder values from authoritative storage.
 *
 * A missing post is a verified result. Database errors, duplicate builder owners, or malformed
 * required fields fail closed so a claimed worker cannot turn an unknown source into Failed.
 *
 * @param int    $post_id Post ID.
 * @param array  $source_snapshot Receives the validated post and active builder values.
 * @param bool   $post_exists Receives whether the exact posts-table owner exists.
 * @param string $editor_identifier Optional single builder identifier.
 * @return bool Whether every required read and row validation succeeded.
 */
function ai4seo_read_metadata_generation_source_snapshot(
	int $post_id,
	array &$source_snapshot,
	bool &$post_exists,
	string $editor_identifier = ''
): bool {
	global $wpdb;

	$post_id         = absint( $post_id );
	$source_snapshot = array();
	$post_exists     = false;

	if ( $post_id <= 0 ) {
		return false;
	}

	$post_query = ai4seo_prepare_database_query(
		'SELECT `ID`, `post_content`, `post_title`, `post_excerpt`, `post_type`
		FROM {{posts_table}}
		WHERE `ID` = {{post_id}}
		LIMIT 1',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_id'     => ai4seo_database_scalar_binding( '%d', $post_id ),
		)
	);

	if ( false === $post_query ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query owns one exact primary-key source snapshot; generation must bypass possibly stale post caches.
	$post_row = $wpdb->get_row( $post_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return false;
	}

	if ( null === $post_row ) {
		return true;
	}

	$required_string_columns = array( 'post_content', 'post_title', 'post_excerpt', 'post_type' );

	if ( ! is_array( $post_row ) || ai4seo_normalize_database_id( $post_row['ID'] ?? null ) !== $post_id ) {
		return false;
	}

	foreach ( $required_string_columns as $required_string_column ) {
		if ( ! array_key_exists( $required_string_column, $post_row ) || ! is_string( $post_row[ $required_string_column ] ) ) {
			return false;
		}
	}

	$post_exists       = true;
	$builder_meta_keys = ai4seo_get_active_metadata_generation_builder_meta_keys( $editor_identifier );
	$builder_meta      = array_fill_keys( $builder_meta_keys, '' );

	if ( $builder_meta_keys ) {
		$builder_meta_query = ai4seo_prepare_database_query(
			'SELECT `meta_id`, `meta_key`, `meta_value`
			FROM {{postmeta_table}}
			WHERE `post_id` = {{post_id}}
			AND `meta_key` IN ({{meta_keys}})
			ORDER BY `meta_id` ASC
			LIMIT {{result_limit}}',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
				'meta_keys'      => ai4seo_database_list_binding( '%s', $builder_meta_keys ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exact post ID and a bounded active-key allowlist own this generation snapshot.
				'result_limit'   => ai4seo_database_scalar_binding( '%d', count( $builder_meta_keys ) + 1 ),
			)
		);

		if ( false === $builder_meta_query ) {
			return false;
		}

		$wpdb->last_error = '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query owns the bounded active-builder snapshot and detects duplicate key owners.
		$builder_meta_rows = $wpdb->get_results( $builder_meta_query, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $builder_meta_rows ) || count( $builder_meta_rows ) > count( $builder_meta_keys ) ) {
			return false;
		}

		$seen_builder_meta_keys = array();

		foreach ( $builder_meta_rows as $builder_meta_row ) {
			$meta_id  = is_array( $builder_meta_row ) ? ai4seo_normalize_database_id( $builder_meta_row['meta_id'] ?? null ) : false;
			$meta_key = is_array( $builder_meta_row ) && isset( $builder_meta_row['meta_key'] ) && is_string( $builder_meta_row['meta_key'] )
				? $builder_meta_row['meta_key']
				: '';

			if ( false === $meta_id
				|| ! array_key_exists( $meta_key, $builder_meta )
				|| isset( $seen_builder_meta_keys[ $meta_key ] )
				|| ! array_key_exists( 'meta_value', $builder_meta_row )
				|| ! is_string( $builder_meta_row['meta_value'] )
			) {
				return false;
			}

			$seen_builder_meta_keys[ $meta_key ] = true;
			$builder_meta[ $meta_key ]           = $builder_meta_row['meta_value'];
		}
	}

	$source_snapshot = array(
		'post'         => $post_row,
		'builder_meta' => $builder_meta,
	);
	return true;
}


/**
 * Return condensed, structure-free post content and optional visible-text details.
 *
 * @param int         $post_id The ID of the post to get the pure text content for.
 * @param bool        $debug Whether to enable debug mode (default: false).
 * @param string|null $strict_visible_text Optional structure-free visible text output.
 * @param string|null $first_h1 Optional first locally available H1 output.
 * @param array|null  $authoritative_source_snapshot Optional validated post/builder source snapshot.
 * @return string The pure text content of the post.
 */
function ai4seo_get_condensed_post_content_from_database(
	int $post_id,
	bool $debug = false,
	?string &$strict_visible_text = null,
	?string &$first_h1 = null,
	?array $authoritative_source_snapshot = null
): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 561711889, 'Prevented loop', true );
		return '';
	}

	if ( null === $authoritative_source_snapshot ) {
		// Legacy callers retain WordPress's cached post read; claimed workers pass an exact snapshot.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return ''; // Return empty if post is not found.
		}
	} elseif ( ! isset( $authoritative_source_snapshot['post'], $authoritative_source_snapshot['builder_meta'] ) ) {
		return '';
	}

	// Keep local editor and builder content available for structured analysis even when the legacy
	// transport path replaces a short body with the fully rendered permalink response.
	$local_combined_content = '';
	$post_content           = ai4seo_get_combined_post_content(
		$post_id,
		'',
		false,
		$debug,
		$local_combined_content,
		$authoritative_source_snapshot
	);
	$analysis_content       = '' !== trim( $local_combined_content ) ? $local_combined_content : $post_content;
	$first_h1               = ai4seo_extract_first_local_h1( $analysis_content );

	// Preserve the existing transport condenser while deriving language evidence from the local source.
	ai4seo_condense_raw_post_content( $post_content );
	ai4seo_condense_raw_post_content( $analysis_content, 2000, 2250, $strict_visible_text );

	if ( $debug ) {
		ai4seo_debug_message(
			614595339,
			'FINAL POST CONTENT (condensed) >' . ai4seo_stringify(
				htmlspecialchars( $post_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
			)
		);
	}

	return $post_content;
}


/**
 * Returns the post content to a given post_id by also reading the content of the most common page builders and
 * combining them into one content
 *
 * @param int         $post_id The post or page id to read the content from.
 * @param string      $editor_identifier The identifier of the editor to read the content from.
 * @param bool        $output_raw The output raw value.
 * @param bool        $debug Whether to enable debug mode (default: false).
 * @param string|null $local_combined_content Optional pre-remote-fallback editor and builder content output.
 * @param array|null  $authoritative_source_snapshot Optional validated post/builder source snapshot.
 * @return false|string The post or page content or false if the post_id is empty
 */
function ai4seo_get_combined_post_content(
	int $post_id = 0,
	string $editor_identifier = '',
	bool $output_raw = false,
	bool $debug = false,
	?string &$local_combined_content = null,
	?array $authoritative_source_snapshot = null
) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 482499702, 'Prevented loop', true );
		return false;
	}

	// Define variables for the current theme and the parent theme.
	$current_theme = wp_get_theme();
	$parent_theme  = $current_theme->parent();

	// Read post-id if it is not numeric.
	if ( empty( $post_id ) ) {
		// Get post- or page-id.
		$post_id = ai4seo_get_current_post_id();
	}

	if ( empty( $post_id ) ) {
		return false;
	}

	if ( null === $authoritative_source_snapshot ) {
		// Get the legacy cached post object only when no exact source snapshot was supplied.
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$post_content = $post->post_content;
	} elseif (
		! isset( $authoritative_source_snapshot['post'], $authoritative_source_snapshot['builder_meta'] )
		|| ! is_array( $authoritative_source_snapshot['post'] )
		|| ! is_array( $authoritative_source_snapshot['builder_meta'] )
		|| ! isset( $authoritative_source_snapshot['post']['post_content'] )
		|| ! is_string( $authoritative_source_snapshot['post']['post_content'] )
	) {
		return false;
	} else {
		$post_content = $authoritative_source_snapshot['post']['post_content'];
	}

	$read_builder_content = static function ( string $meta_key ) use ( $authoritative_source_snapshot, $post_id ) {
		if ( null !== $authoritative_source_snapshot ) {
			return $authoritative_source_snapshot['builder_meta'][ $meta_key ] ?? '';
		}

		return get_post_meta( $post_id, $meta_key, true );
	};

	// Define variable for the combined post- or page-content.
	$combined_content = array();

	// apply short codes.
	$post_content = do_shortcode( $post_content );

	// Return post-content if not empty and not the same as the post-title or post-excerpt.
	if ( ! empty( $post_content ) ) {
		if ( $debug ) {
			ai4seo_debug_message(
				369296244,
				'POST CONTENT >' . ai4seo_stringify(
					htmlspecialchars( $post_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
				)
			);
		}

		$combined_content[] = trim( $post_content );
	}

	// check if is_plugin_active() is available.
	$plugins_are_loaded = function_exists( 'is_plugin_active' );

	// Elementor: only if the post_content got less than 100 characters, as the post_content should contain even a clearer version of the content.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'elementor' === $editor_identifier ) && is_plugin_active( 'elementor/elementor.php' ) ) {
		// Get elementor-content.
		$elementor_content = $read_builder_content( '_elementor_data' );

		// Return elementor-content if not empty.
		if ( ! empty( $elementor_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					264856340,
					'ELEMENTOR CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $elementor_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $elementor_content );
		}
	}

	// Check if muffin-builder-plugin is active. If yes, only consider it's content as it's the content that is shown on the page.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'mfn-builder' === $editor_identifier ) && ( $current_theme->get( 'Name' ) === 'Betheme'
			|| ( $parent_theme && $parent_theme->get( 'Name' ) === 'Betheme' ) ) ) {
		// Get muffin-builder-content.
		$muffin_builder_content = $read_builder_content( 'mfn-page-items-seo' );

		// Return muffin-builder-content if not empty.
		if ( ! empty( $muffin_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					288886929,
					'MUFFIN BUILDER CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $muffin_builder_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $muffin_builder_content );
		}
	}

	// Check if beaver-builder-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'fl-builder' === $editor_identifier ) && is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) ) {
		// Get beaver-builder-content.
		$beaver_builder_content = $read_builder_content( '_fl_builder_data' );

		// Return beaver-builder-content if not empty.
		if ( ! empty( $beaver_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					899351813,
					'BEAVER BUILDER CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $beaver_builder_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $beaver_builder_content );
		}
	}

	// Check if divi-builder-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'divi-builder' === $editor_identifier ) && is_plugin_active( 'divi-builder/divi-builder.php' ) ) {
		// Get divi-builder-content.
		$divi_builder_content = $read_builder_content( '_et_pb_use_builder' );

		// Return divi-builder-content if not empty.
		if ( ! empty( $divi_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					412179553,
					'DIVI BUILDER CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $divi_builder_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $divi_builder_content );
		}
	}

	// Check if oxygen-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'oxygen' === $editor_identifier ) && is_plugin_active( 'oxygen/functions.php' ) ) {
		// Get oxygen-content.
		$oxygen_content = $read_builder_content( 'ct_builder_shortcodes' );

		// Return oxygen-content if not empty.
		if ( ! empty( $oxygen_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					528624552,
					'OXYGEN CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $oxygen_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $oxygen_content );
		}
	}

	// Check if brizy-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'brizy' === $editor_identifier ) && is_plugin_active( 'brizy/brizy.php' ) ) {
		// Get brizy-content.
		$brizy_content = $read_builder_content( 'brizy_post_uid' );

		// Return brizy-content if not empty.
		if ( ! empty( $brizy_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					344263270,
					'BRIZY CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $brizy_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content[] = trim( $brizy_content );
		}
	}

	// Expose the locally available source before the legacy remote fallback can replace short content.
	$local_combined_content = implode( ' ', $combined_content );

	// Fallback -> wp_remote_get the post content.
	if ( empty( $combined_content ) || strlen( implode( '', $combined_content ) ) < AI4SEO_TOO_SHORT_CONTENT_LENGTH ) {
		// Get the post content from the remote URL.
		$post_permalink = get_permalink( $post_id );

		try {
			$remote_content = ai4seo_get_remote_body( $post_permalink );
		} catch ( Exception $e ) {
			$remote_content = new WP_Error( 'remote_content_error', 'Error fetching remote content: ' . $e->getMessage() );
		}

		// If remote content is not an error, add it to the combined content.
		if ( ! is_wp_error( $remote_content ) && ! empty( $remote_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					823132530,
					'REMOTE CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $remote_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content = array( $remote_content );
		}
	}

	$combined_content = implode( ' ', $combined_content );

	// Apply the 'the_content' filter to the post content.
	if ( ! $output_raw ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core defines this public hook.
		$filtered_combined_content = apply_filters( 'the_content', $combined_content );

		if ( $filtered_combined_content && strlen( $filtered_combined_content ) > strlen( $combined_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message(
					859196742,
					'FILTERED COMBINED CONTENT>' . ai4seo_stringify(
						htmlspecialchars( $filtered_combined_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
					)
				);
			}

			$combined_content = $filtered_combined_content;
		}
	}

	return $combined_content;
}


/**
 * Condenses the raw content to a more readable and useful format for the api
 *
 * @param string      $content The raw content to condense.
 * @param int         $soft_cap Consider at least this many characters before truncating.
 * @param int         $hard_cap Truncate the content to this length if no sentence end is found.
 * @param string|null $strict_visible_text Optional structure-free visible text output.
 */
function ai4seo_condense_raw_post_content(
	string &$content,
	int $soft_cap = 2000,
	int $hard_cap = 2250,
	?string &$strict_visible_text = null
) {
	global $shortcode_tags;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 528878491, 'Prevented loop', true );
		return;
	}

	// workaround for ACF blocks, as content for ACF blocks are defined inside <!-- wp:acf/... --> tags.
	if ( ai4seo_is_acf_content( $content ) ) {
		$content .= ai4seo_extract_acf_content( $content );
	}

	// Remove complete and malformed style/script blocks so an absent closing tag cannot expose code as page text.
	$content = preg_replace( '/<style\b[^>]*>.*?(?:<\/style>|$)/is', '', $content );
	$content = preg_replace( '/<script\b[^>]*>.*?(?:<\/script>|$)/is', '', $content );

	// Remove HTML comments.
	$content = preg_replace( '/<!--(.*?)-->/', '', $content );

	// Remove CSS/JS comments.
	$content = preg_replace( '/\/\*(.*?)\*\//', '', $content );

	// replace \/ with /.
	$content = str_replace( '\/', '/', $content );
	$content = str_replace( "'", "'", $content );

	// remove icons ("icon-lamp").
	$content = preg_replace( '/icon-[a-z0-9-]+/', '', $content );

	// remove shortcodes like [vc_row1].
	$content = preg_replace( '/\[[a-zA-Z0-9_]+(]|$)/', '', $content );

	// Remove opening vc_ shortcodes.
	$content = preg_replace( '/\[vc_[^]]+(]|$)/', '', $content );

	// Remove closing vc_ shortcodes.
	$content = preg_replace( '/\[\/vc_[^]]+(]|$)/', '', $content );

	// handle $shortcode_tags.
	$shortcodes = array_keys( $shortcode_tags );

	if ( $shortcodes ) {
		foreach ( $shortcodes as $shortcode ) {
			$content = preg_replace( '/\[' . $shortcode . '[^]]*]/', '', $content );
			$content = preg_replace( '/\[\/' . $shortcode . '[^]]*]/', '', $content );
		}
	}

	// Remove all HTML tags.
	$content = wp_strip_all_tags( $content );

	// remove all URLs.
	$content = ai4seo_remove_urls_from_string( $content );

	// Replace multiple spaces with a single space and trim whitespace.
	$content = preg_replace( '/\s+/', ' ', $content );
	$content = trim( $content );

	// remove be-builder progress bar infos (50 10 #72a5d8).
	$content = preg_replace( '/[0-9]+ [0-9]+ #[a-f0-9]+/', '', $content );
	$content = preg_replace( '/[0-9]+ [0-9]+ (grey|gray|red|green|blue|yellow|orange|purple|pink|black|white)/', '', $content );

	// Decode HTML entities and handle common entities separately.
	$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$content = str_replace( ' ', ' ', $content );

	// Handle common entities that might not be converted.
	$content = str_replace( array( '&nbsp;', '&amp;', '&quot;', '&#39;', '&lt;', '&gt;', '&;', '\u2019', 'â€™', 'â€', 'â€³', '€™t', '\u201d', '\u003cli>', '\u2013' ), array( ' ', '&', '"', "'", '<', '>', "'", "'", "'", '"', '"', "'", '"', '- ', '–' ), $content );

	// Replace multiple spaces with a single space and trim whitespace.
	$content = preg_replace( '/\s+/', ' ', $content );
	$content = trim( $content );

	// remove remaining short tags with all kinds of [ - ] combinations,
	// but only apply the changes if we have at least AI4SEO_TOO_SHORT_CONTENT_LENGTH chars left.
	$temp_content = preg_replace( '/\[.*?]/', '', $content );

	// Bound only the new analysis pass; transport processing above remains unchanged for legacy callers.
	$strict_analysis_source = ai4seo_get_bounded_metadata_analysis_source(
		is_string( $temp_content ) ? $temp_content : ''
	);
	$strict_visible_text    = ai4seo_clean_metadata_visible_text( $strict_analysis_source );
	$strict_visible_text    = ai4seo_remove_double_sentences( $strict_visible_text );
	$strict_visible_text    = ai4seo_truncate_sentence( $strict_visible_text, $soft_cap, $hard_cap );

	if ( $content !== $temp_content && ai4seo_mb_strlen( $temp_content ) >= AI4SEO_TOO_SHORT_CONTENT_LENGTH ) {
		$content = $temp_content;

		// Replace multiple spaces with a single space and trim whitespace.
		$content = preg_replace( '/\s+/', ' ', $content );
		$content = trim( $content );
	}

	// remove double sentences.
	$content = ai4seo_remove_double_sentences( $content );

	// truncate sentence.
	$content = ai4seo_truncate_sentence( $content, $soft_cap, $hard_cap );
}


/**
 * Bound the strict-analysis source while retaining a small token-boundary overlap.
 *
 * The extra overlap lets tags or shortcode tokens that start near the 256 KiB boundary reach
 * their closing delimiter without allowing the cleanup pass to copy an arbitrarily large page.
 *
 * @param string $content Source already prepared by the legacy condenser.
 * @return string Bounded strict-analysis source.
 */
function ai4seo_get_bounded_metadata_analysis_source( string $content ): string {
	$max_source_bytes = 262144;
	$overlap_bytes    = 4096;

	// Normal pages avoid an unnecessary substring allocation.
	if ( strlen( $content ) <= $max_source_bytes ) {
		return $content;
	}

	// Include one bounded overlap so cleanup can consume structural tokens crossing the main boundary.
	return substr( $content, 0, $max_source_bytes + $overlap_bytes );
}


/**
 * Remove non-visible structure from bounded metadata language evidence.
 *
 * @param string $content Locally available page, heading, or excerpt content.
 * @return string Visible text only.
 */
function ai4seo_clean_metadata_visible_text( string $content ): string {
	// Empty sources need no normalization and should remain empty evidence.
	if ( '' === $content ) {
		return '';
	}

	// Remove malformed byte sequences before Unicode regexes so one bad remote byte cannot erase all evidence.
	$content = wp_check_invalid_utf8( $content, true );

	if ( '' === $content ) {
		return '';
	}

	// Decode before stripping so encoded tags, scripts, and shortcode brackets cannot become evidence later.
	for ( $decode_pass = 0; $decode_pass < 2; $decode_pass++ ) {
		$decoded_content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Stop once another pass cannot expose additional encoded structure.
		if ( $decoded_content === $content ) {
			break;
		}

		$content = $decoded_content;
	}

	// Remove non-visible structural content before reducing the remaining source to plain text.
	$content = preg_replace( '/<(style|script)\b[^>]*>.*?(?:<\/\1>|$)/is', '', $content );
	$content = preg_replace( '/<!--.*?-->/s', '', $content );
	$content = preg_replace( '/\/\*.*?\*\//s', '', $content );
	$content = preg_replace( '/\[.*?]/s', '', $content );
	$content = is_string( $content ) ? wp_strip_all_tags( $content ) : '';
	$content = ai4seo_remove_urls_from_string( $content );

	// Remove still-encoded or unknown entities rather than counting their names as visible words.
	$content = preg_replace( '/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]+);/i', ' ', $content );
	$content = preg_replace( '/\s+/u', ' ', is_string( $content ) ? $content : '' );

	return is_string( $content ) ? trim( $content ) : '';
}


/**
 * Extract the first H1 already present in local/in-memory content.
 *
 * This helper never performs a remote request. The scan is byte-bounded to keep analysis cheap
 * even when an existing content source contains a very large rendered document.
 *
 * @param mixed $content Locally available content to inspect.
 * @return string First visible H1 text, or an empty string when none is available.
 */
function ai4seo_extract_first_local_h1( $content ): string {
	// Ignore unavailable local sources without triggering any fallback retrieval.
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return '';
	}

	// Limit parsing to locally available content so metadata preparation never adds network or unbounded parsing work.
	$h1_scan_content = substr( $content, 0, 65536 );
	$h1_matches      = array();

	// Collect local candidates once so structurally empty headings can be skipped without rescanning.
	if ( ! preg_match_all( '/<h1\b[^>]*>(.*?)<\/h1>/is', $h1_scan_content, $h1_matches ) ) {
		return '';
	}

	// Return the first heading that still contains language evidence after structural cleanup.
	foreach ( $h1_matches[1] ?? array() as $h1_content ) {
		$clean_h1 = ai4seo_clean_metadata_visible_text( (string) $h1_content );

		if ( '' !== $clean_h1 ) {
			return ai4seo_mb_substr( $clean_h1, 0, 512 );
		}
	}

	return '';
}


/**
 * Return deterministic Unicode metrics and the metadata content-quality classification.
 *
 * @param string $visible_text Structure-free page text.
 * @return array{quality:string,quality_reason:string,visible_word_count:int,visible_letter_count:int}
 */
function ai4seo_classify_metadata_visible_content( string $visible_text ): array {
	// Keep the token definition in sync with RobHub's bounded server-side contract verification.
	$word_result = preg_match_all( "/\p{L}[\p{L}\p{M}\p{N}'’\-]*/u", $visible_text );
	$word_count  = false === $word_result ? 0 : (int) $word_result;

	// Count letters independently because substantive evidence must meet both public thresholds.
	$letter_result = preg_match_all( '/\p{L}/u', $visible_text );
	$letter_count  = false === $letter_result ? 0 : (int) $letter_result;

	// Require both thresholds because either too few words or too few letters is weak language evidence.
	if ( 0 === $word_count ) {
		$quality        = 'markup_only';
		$quality_reason = 'no_letter_words';
	} elseif ( $word_count < 12 || $letter_count < 80 ) {
		$quality        = 'sparse';
		$quality_reason = 'below_substantive_threshold';
	} else {
		$quality        = 'substantive';
		$quality_reason = 'substantive_text';
	}

	return array(
		'quality'              => $quality,
		'quality_reason'       => $quality_reason,
		'visible_word_count'   => $word_count,
		'visible_letter_count' => $letter_count,
	);
}


/**
 * Prepare the shared content and structured analysis used by manual and automated metadata generation.
 *
 * @param int       $post_id WordPress post ID.
 * @param string    $submitted_content Content already supplied by manual generation, when available.
 * @param bool|null $required_source_read_succeeded Receives whether required post/builder reads succeeded.
 * @param bool|null $post_exists Receives whether the authoritative posts-table owner exists.
 * @return array{content:string,post_context:string,content_analysis:array}
 */
function ai4seo_prepare_metadata_generation_content_data(
	int $post_id,
	string $submitted_content = '',
	?bool &$required_source_read_succeeded = null,
	?bool &$post_exists = null
): array {
	$authoritative_source_requested = 3 <= func_num_args();
	$required_source_read_succeeded = null;
	$post_exists                    = null;
	$authoritative_source_snapshot  = null;

	if ( $authoritative_source_requested ) {
		$authoritative_source_snapshot  = array();
		$post_exists                    = false;
		$required_source_read_succeeded = ai4seo_read_metadata_generation_source_snapshot(
			$post_id,
			$authoritative_source_snapshot,
			$post_exists
		);

		if ( ! $required_source_read_succeeded ) {
			return array(
				'content'          => '',
				'post_context'     => '',
				'content_analysis' => array(
					'schema_version'       => '1',
					'quality'              => 'markup_only',
					'quality_reason'       => 'required_source_read_failed',
					'visible_word_count'   => 0,
					'visible_letter_count' => 0,
					'body_text'            => '',
					'excerpt_text'         => '',
					'h1'                   => '',
					'post_title'           => '',
					'site_language'        => '',
				),
			);
		}
	}

	$post_content         = $submitted_content;
	$has_submitted_source = '' !== trim( $submitted_content );
	$body_text            = '';
	$first_h1             = ai4seo_extract_first_local_h1( $submitted_content );
	$database_first_h1    = '';
	$structured_context   = array();

	// Reuse submitted content for manual generation before falling back to the existing database preparation path.
	if ( $has_submitted_source ) {
		ai4seo_condense_raw_post_content( $post_content, 2000, 2250, $body_text );
	}

	// Preserve the database fallback only when no editor source was submitted. Structurally empty editor
	// content must remain empty analysis evidence instead of being replaced by unrelated rendered chrome.
	if ( ! $has_submitted_source ) {
		if ( ! $authoritative_source_requested || $post_exists ) {
			$post_content = ai4seo_get_condensed_post_content_from_database(
				$post_id,
				false,
				$body_text,
				$database_first_h1,
				$authoritative_source_snapshot
			);
		}

		if ( ! $first_h1 ) {
			$first_h1 = $database_first_h1;
		}
	}

	// Build the legacy post-context string and structured title/excerpt evidence in the same data-access pass.
	$post_context           = $post_content;
	$authoritative_post_row = $authoritative_source_requested
		? ( $authoritative_source_snapshot['post'] ?? array() )
		: null;
	ai4seo_add_post_context( $post_id, $post_context, false, false, $structured_context, $authoritative_post_row );

	// Keep the transport-content fallback separate from body analysis so interface labels never become body evidence.
	if ( ! $post_content && $post_context ) {
		$post_content = $post_context;
	}

	// Classify the exact bounded value sent on the wire so RobHub's verification cannot disagree after sanitization.
	$body_text              = ai4seo_mb_substr( sanitize_text_field( $body_text ), 0, 2350 );
	$content_classification = ai4seo_classify_metadata_visible_content( $body_text );
	$site_language          = ai4seo_get_language_long_version( ai4seo_get_wordpress_language_code(), '' );

	// Assemble the versioned wire contract explicitly so its complete field set remains easy to audit.
	$content_analysis = array(
		'schema_version'       => '1',
		'quality'              => $content_classification['quality'],
		'quality_reason'       => $content_classification['quality_reason'],
		'visible_word_count'   => $content_classification['visible_word_count'],
		'visible_letter_count' => $content_classification['visible_letter_count'],
		'body_text'            => $body_text,
		'excerpt_text'         => ai4seo_mb_substr( sanitize_text_field( $structured_context['excerpt_text'] ?? '' ), 0, 512 ),
		'h1'                   => ai4seo_mb_substr( sanitize_text_field( $first_h1 ), 0, 512 ),
		'post_title'           => ai4seo_mb_substr( sanitize_text_field( $structured_context['post_title'] ?? '' ), 0, 512 ),
		'site_language'        => ai4seo_mb_substr( sanitize_text_field( $site_language ), 0, 64 ),
	);

	return array(
		'content'          => $post_content,
		'post_context'     => $post_context,
		'content_analysis' => $content_analysis,
	);
}


/**
 * Replace content with the existing post-context string and optionally expose clean language evidence.
 *
 * The optional structured output lets metadata generation reuse the title and excerpt lookups that
 * this function already performs, avoiding a second database/context pass.
 *
 * @param int        $post_id Post ID used for local context lookups.
 * @param mixed      $content Content replaced by the generated context string.
 * @param bool       $include_website_context Whether to prepend website context.
 * @param bool       $include_first_section Whether to include the supplied content as the first section.
 * @param array|null $structured_context Optional title and cleaned excerpt output.
 * @param array|null $authoritative_post_row Optional validated required post fields.
 */
function ai4seo_add_post_context(
	$post_id,
	&$content,
	bool $include_website_context = true,
	bool $include_first_section = true,
	?array &$structured_context = null,
	?array $authoritative_post_row = null
) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 275721265, 'Prevented loop', true );
		return;
	}

	// Treat an explicitly supplied output argument as structured negotiation even when its variable starts as null.
	$use_structured_context = func_num_args() >= 5;
	$context                = '';
	$structured_context     = array(
		'post_title'   => '',
		'excerpt_text' => '',
	);

	// ADD WEBSITE CONTEXT.
	if ( $include_website_context ) {
		$about_this_website = ai4seo_get_website_context();
		$context           .= "WEBSITE: {$about_this_website} | ";

		// ADD POST ENTRY CONTEXT.
		$context .= 'SUB PAGE: ';
	}

	// URL, taxonomy, and integration context are optional enrichment. Required post fields below use
	// the caller's exact row when supplied, so an enrichment miss cannot masquerade as empty content.
	$post_url = get_permalink( $post_id );

	if ( $post_url ) {
		$context .= "URL: '" . $post_url . "'. ";
	}

	// post type.
	$post_type = null === $authoritative_post_row
		? get_post_type( $post_id )
		: ( $authoritative_post_row['post_type'] ?? '' );

	if ( $post_type ) {
		$context .= "WordPress Post Type: '" . $post_type . "'. ";
	}

	// categories.
	$post_categories = get_the_category( $post_id );

	if ( $post_categories ) {
		$category_names = array_map(
			function ( $category ) {
				return $category->name;
			},
			$post_categories
		);
		$context       .= "Category: '" . implode( ', ', $category_names ) . "'. ";
	}

	// WooCommerce context.
	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE ) && function_exists( 'wc_get_page_id' ) && ai4seo_is_function_usable( 'wc_get_page_id' ) ) {
		// genric pages.
		if ( wc_get_page_id( 'shop' ) === $post_id ) {
			$context .= 'WooCommerce: This page displays all products and serves as the main store landing page. Please keep metadata generic. ';
		} elseif ( wc_get_page_id( 'cart' ) === $post_id ) {
			$context .= 'WooCommerce: This page is the shopping cart where customers can view and manage their selected products. Please keep metadata generic. ';
		} elseif ( wc_get_page_id( 'checkout' ) === $post_id ) {
			$context .= 'WooCommerce: This page is the checkout page where customers complete their purchases. Please keep metadata generic. ';
		} elseif ( wc_get_page_id( 'myaccount' ) === $post_id ) {
			$context .= 'WooCommerce: This page is the customer account page where users can manage their account details. Please keep metadata generic. ';
		} elseif ( ai4seo_get_option( 'woocommerce_terms_page_id' ) === $post_id ) {
			$context .= 'WooCommerce: This page is the Terms and Conditions page for this WooCommerce store. Please keep metadata generic. ';
		}

		// product pages -> product details.
		if ( 'product' === $post_type
			&& function_exists( 'wc_get_product' ) && ai4seo_is_function_usable( 'wc_get_product' )
			&& function_exists( 'wc_price' ) && ai4seo_is_function_usable( 'wc_price' )
			&& class_exists( 'WC_Product' ) ) {
			$product = wc_get_product( $post_id );

			if ( $product instanceof WC_Product ) {
				$context .= "WooCommerce: This is a product page for the product '" . ai4seo_deep_sanitize( $product->get_name() ) . "'. ";

				$include_product_price_mode = ai4seo_get_setting( AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA );

				if ( ! is_string( $include_product_price_mode )
					|| ! in_array( $include_product_price_mode, AI4SEO_AVAILABLE_INCLUDE_PRODUCT_PRICE_IN_METADATA_OPTIONS, true ) ) {
					$include_product_price_mode = 'never';
				}

				$product_price     = '';
				$product_price_raw = $product->get_price();

				if ( '' !== $product_price_raw && null !== $product_price_raw ) {
					$product_price = ai4seo_deep_sanitize( wc_price( $product_price_raw ) );
					$product_price = wp_strip_all_tags( $product_price );
					$product_price = html_entity_decode( $product_price, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$product_price = str_replace( ' ', ' ', $product_price );
					$product_price = trim( $product_price );
				}

				$product_price_instruction_added = false;

				if ( 'fixed' === $include_product_price_mode && '' !== $product_price ) {
					$context                        .= "The product has a price of '" . $product_price . "'. You must include this price in the metadata (meta-title, meta-description and social-media-description). ";
					$product_price_instruction_added = true;
				} elseif ( 'dynamic' === $include_product_price_mode && '' !== $product_price ) {
					$product_price_placeholder       = '{WC_PRICE=' . $product_price . '}';
					$context                        .= "The product has a price of '" . $product_price . "'. Include the placeholder " . $product_price_placeholder . ' in the metadata (meta-title, meta-description and social-media-description); it will be replaced with the live price during rendering. ';
					$product_price_instruction_added = true;
				}

				if ( ! $product_price_instruction_added ) {
					$context .= 'Important: Do not include the product price in the metadata. ';
				}

				$category_ids = ai4seo_deep_sanitize( $product->get_category_ids() );

				$terms = array_map(
					function ( $term_id ) {
						$term = get_term( $term_id, 'product_cat' );
						return $term ? $term->name : null;
					},
					$category_ids
				);

				$terms = ai4seo_deep_sanitize( array_filter( $terms ) );

				if ( ! empty( $terms ) ) {
					$context .= "The product is in the category '" . implode( ', ', $terms ) . "'. ";
				}
			}
		}
	}

	// privacy policy context.
	if ( ai4seo_get_option( 'wp_page_for_privacy_policy' ) === $post_id ) {
		$context .= 'Attention: This page is the Privacy Policy page for this website. Please keep metadata generic. ';
	}

	// post title.
	$post_title = null === $authoritative_post_row
		? get_the_title( $post_id )
		: ( $authoritative_post_row['post_title'] ?? '' );

	if ( $post_title ) {
		$structured_context['post_title'] = $post_title;
		$context                         .= "Sub Page Title: '" . $post_title . "'. ";
	}

	// excerpt.
	$post_excerpt = null === $authoritative_post_row
		? get_the_excerpt( $post_id )
		: ( $authoritative_post_row['post_excerpt'] ?? '' );

	if ( $post_excerpt ) {
		$strict_post_excerpt = '';
		ai4seo_condense_raw_post_content( $post_excerpt, 150, 250, $strict_post_excerpt ); // Condense the excerpt.
		$structured_context['excerpt_text'] = $strict_post_excerpt;

		// Structured requests must not reintroduce builder markup through their legacy post-context field.
		if ( ! $use_structured_context || $strict_post_excerpt ) {
			$context_excerpt = $use_structured_context ? $strict_post_excerpt : $post_excerpt;
			$context        .= "Excerpt: '" . $context_excerpt . "'. ";
		}
	}

	// first section.
	if ( $include_first_section && $content ) {
		$context .= "First section: '" . $content . "'. ";
	}

	$context = trim( $context );

	// enrich the content with the context.
	$content = $context;
}


/**
 * Return a compact description of the current WordPress website.
 *
 * @return string Website name, tagline, and URL context.
 */
function ai4seo_get_website_context(): string {
	$website_context = '';

	// Get the WordPress site name, tagline, and URL.
	$wp_name = get_bloginfo( 'name' );

	if ( $wp_name ) {
		$website_context .= "Name: '" . $wp_name . "'. ";
	}

	$wp_tagline = get_bloginfo( 'description' );

	if ( $wp_tagline ) {
		$website_context .= "Tagline: '" . $wp_tagline . "'. ";
	}

	$wp_url = get_bloginfo( 'url' );

	if ( $wp_url ) {
		$website_context .= "URL: '" . $wp_url . "'";
	}

	return $website_context;
}


/**
 * Determine whether post content contains an ACF block marker.
 *
 * @param string $post_content Post content to inspect.
 * @return bool Whether an ACF block marker is present.
 */
function ai4seo_is_acf_content( $post_content ): bool {
	return strpos( $post_content, '<!-- wp:acf/' ) !== false;
}


/**
 * Extract user-facing field values from serialized ACF block comments.
 *
 * @param string $post_content Post content containing ACF blocks.
 * @return string Extracted ACF field content.
 */
function ai4seo_extract_acf_content( $post_content ): string {
	// Initialize an array to hold the extracted content.
	$extracted_content = array();

	// Match all ACF blocks in the post_content.
	preg_match_all( '/<!-- wp:acf\/(.*?) (.*?)\/-->/s', $post_content, $matches, PREG_SET_ORDER );

	// Loop through each ACF block match.
	foreach ( $matches as $match ) {
		// Decode the JSON data for the ACF block.
		$acf_data = json_decode( $match[2], true );

		if ( isset( $acf_data['data'] ) ) {
			// Loop through the 'data' array and extract field content.
			foreach ( $acf_data['data'] as $key => $value ) {
				// Skip metadata fields (fields starting with an underscore).
				if ( strpos( $key, '_' ) === 0 ) {
					continue;
				}

				// Add the content to the extracted content array.
				if ( ! empty( $value ) ) {
					$extracted_content[] = $value;
				}
			}
		}
	}

	// Return the extracted content as a plain text string.
	return implode( ' ', $extracted_content );
}


/**
 * Normalize metadata identifiers to the canonical string domain.
 *
 * @param array $metadata_identifiers Raw metadata identifiers.
 * @return array Canonical string identifiers.
 */
function ai4seo_normalize_metadata_identifier_list( array $metadata_identifiers ): array {
	// Retain only exact identifier strings so later strict comparisons cannot coerce scalar lookalikes.
	$normalized_metadata_identifiers = array();

	foreach ( $metadata_identifiers as $metadata_identifier ) {
		if ( ! is_string( $metadata_identifier ) || '' === $metadata_identifier ) {
			continue;
		}

		$normalized_metadata_identifiers[] = $metadata_identifier;
	}

	return array_values( array_unique( $normalized_metadata_identifiers ) );
}


/**
 * Calculate the metadata generation credit cost for one post.
 *
 * @param array|null $only_this_meta_tags Optional metadata identifiers to include.
 * @return int Credit cost per post.
 */
function ai4seo_calculate_metadata_credits_cost_per_post( $only_this_meta_tags = null ): int {
	// check all active meta tags.
	$metadata_price_table = ai4seo_get_metadata_price_table( $only_this_meta_tags );

	if ( empty( $metadata_price_table ) ) {
		return 1;
	}

	// calculate total costs.
	return array_sum( $metadata_price_table );
}


/**
 * Return credit prices for active metadata fields.
 *
 * @param array|null $only_this_meta_tags Optional metadata identifiers to include.
 * @return array Metadata identifiers mapped to credit costs.
 */
function ai4seo_get_metadata_price_table( $only_this_meta_tags = null ): array {
	// Keep a non-empty caller filter restrictive even when all submitted identifiers normalize away.
	$active_meta_tags                = ai4seo_normalize_metadata_identifier_list( ai4seo_get_active_meta_tags() );
	$restrict_to_requested_meta_tags = is_array( $only_this_meta_tags ) && ! empty( $only_this_meta_tags );

	if ( is_array( $only_this_meta_tags ) ) {
		$only_this_meta_tags = ai4seo_normalize_metadata_identifier_list( $only_this_meta_tags );
	}

	if ( empty( $active_meta_tags ) ) {
		return array();
	}

	$price_table = array();

	foreach ( $active_meta_tags as $this_active_meta_tag ) {
		if ( $restrict_to_requested_meta_tags && ! in_array( $this_active_meta_tag, $only_this_meta_tags, true ) ) {
			continue;
		}

		if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) || ! is_array( AI4SEO_METADATA_DETAILS ) ) {
			$price_table[ $this_active_meta_tag ] = 1; // fallback to 1 credit per meta tag.
			continue;
		}

		$price_table[ $this_active_meta_tag ] = AI4SEO_METADATA_DETAILS[ $this_active_meta_tag ]['flat-credits-cost'] ?? 1;
	}

	return $price_table;
}


/**
 * Removes all URLs from a given string.
 *
 * @param string $content The input string from which URLs will be removed.
 * @return string The string with all URLs removed.
 */
function ai4seo_remove_urls_from_string( string $content ): string {
	// Define the regex pattern to match URLs.
	$pattern = '/\b(?:https?|ftp):\/\/\S+/i';

	// Use preg_replace to remove URLs.
	$cleaned_content = preg_replace( $pattern, '', $content );

	// Return the cleaned content.
	return $cleaned_content;
}


/**
 * Check if current admin screen is post edit (classic or Gutenberg).
 *
 * @return bool
 */
function ai4seo_is_post_edit_screen(): bool {
	if ( ! is_admin() ) {
		return false;
	}

	if ( ! function_exists( 'get_current_screen' ) ) {
		return false;
	}

	$screen = get_current_screen();

	if ( ! $screen ) {
		return false;
	}

	return in_array( $screen->base, array( 'post', 'post-new' ), true );
}




/**
 * Is called when a post is updated or created, using the action hook "save_post". The function will add the post
 * id to the option "AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME" to be analyzed by the plugin.
 *
 * @param int          $post_id the post id.
 * @param WP_Post|null $post the post object.
 * @param bool         $update if the post is updated.
 * @return void
 */
function ai4seo_mark_post_to_be_analyzed( int $post_id, ?WP_Post $post = null, bool $update = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve the save_post callback contract.
	// check if the post is a revision.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Check if this is an autosave routine. If it is, the edit form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// check if we are currently inside an edit form.
	if ( ! ai4seo_is_post_edit_screen() ) {
		return;
	}

	// Layer post-save analysis behind the configured content-role boundary.
	if ( ! ai4seo_can_use_plugin_content() ) {
		return;
	}

	// Verify this came from our screen and with proper authorization.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Insert post id into option to be analyzed AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME.
	if ( ! ai4seo_add_post_ids_to_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME, $post_id ) ) {
		ai4seo_debug_message( 984321731, 'Could not enqueue the saved post for metadata coverage analysis.', true );

		// Preserve an authoritative reconciliation request when the narrow queue admission fails.
		if ( ! ai4seo_schedule_post_analysis_reconciliation() ) {
			ai4seo_debug_message( 984321732, 'Could not persist the fallback metadata coverage reconciliation request.', true );
		}
	}
}


/**
 * Analyzes the post, currently updating the metadata coverage
 *
 * @param int $post_id the post id.
 * @return bool True when analysis completed or no analysis is required for this post.
 */
function ai4seo_analyze_post( int $post_id ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 516343145, 'Prevented loop', true );
		return false;
	}

	if ( $post_id <= 0 ) {
		return false;
	}

	// read post.
	$post = get_post( $post_id );

	// check if the post could be read.
	if ( ! $post || is_wp_error( $post ) || ! isset( $post->post_type ) ) {
		return true;
	}

	// ignore attachments.
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	if ( in_array( (string) $post->post_type, $supported_attachment_post_types, true ) ) {
		return true;
	}

	return ai4seo_refresh_one_posts_metadata_coverage_status( $post_id, $post );
}


/**
 * Exclusively dequeue the next metadata-coverage analysis entry.
 *
 * A successful destructive dequeue is the worker's ownership claim. Removing the entry before
 * analysis lets a save that occurs during analysis enqueue the same numeric ID as a new generation,
 * which the current worker must never remove after finishing its older generation.
 *
 * @param int|null $claimed_post_id Receives the claimed post ID, or null when no work remains.
 * @return bool True when authoritative storage was checked and any claim was committed.
 */
function ai4seo_claim_next_post_to_be_analyzed( ?int &$claimed_post_id = null ): bool {
	$claimed_post_id       = null;
	$critical_section_name = ai4seo_get_post_id_option_transition_semaphore_name();
	$operation_succeeded   = false;
	$release_succeeded     = false;

	if ( ! ai4seo_acquire_semaphore( $critical_section_name ) ) {
		return false;
	}

	try {
		$queue_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME );

		if ( null !== $queue_snapshot ) {
			$queued_post_ids = ai4seo_normalize_option_post_id_collection( $queue_snapshot['value'] );

			if ( ! $queued_post_ids ) {
				$operation_succeeded = true;
			} else {
				$candidate_post_id = reset( $queued_post_ids );
				$claim_did_change  = false;
				$changed_options   = array();

				$operation_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
					array(),
					array( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME => array( $candidate_post_id ) ),
					$claim_did_change,
					$changed_options
				);

				if ( $operation_succeeded && isset( $changed_options[ AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME ] ) ) {
					$claimed_post_id = $candidate_post_id;
				}
			}
		}
	} finally {
		$release_succeeded = ai4seo_release_semaphore( $critical_section_name );
	}

	if ( ! $operation_succeeded || ! $release_succeeded ) {
		$claimed_post_id = null;
		return false;
	}

	return true;
}


/**
 * Restore a claimed analysis entry without removing any concurrently enqueued generation.
 *
 * @param int $post_id Claimed post ID.
 * @return bool True when queue membership was verified.
 */
function ai4seo_restore_post_analysis_claim( int $post_id ): bool {
	if ( $post_id <= 0 ) {
		return false;
	}

	return ai4seo_apply_post_id_option_transition(
		array( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME => array( $post_id ) ),
		array()
	);
}


/**
 * Persist an authoritative fallback when a narrow post-analysis queue operation fails.
 *
 * @return bool True when the durable reconciliation contract was satisfied.
 */
function ai4seo_schedule_post_analysis_reconciliation(): bool {
	return function_exists( 'ai4seo_schedule_generation_status_summary_rebuild' )
		&& ai4seo_schedule_generation_status_summary_rebuild();
}


/**
 * Analyze the next exclusively claimed queued post.
 *
 * @return void
 */
function ai4seo_handle_posts_to_be_analyzed() {
	// Queue analysis runs only for users inside the configured content-role boundary.
	if ( ! ai4seo_can_use_plugin_content() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$post_id = null;

	if ( ! ai4seo_claim_next_post_to_be_analyzed( $post_id ) ) {
		ai4seo_debug_message( 984321733, 'Could not exclusively claim the next metadata coverage analysis entry.', true );

		if ( ! ai4seo_schedule_post_analysis_reconciliation() ) {
			ai4seo_debug_message( 984321734, 'Could not persist fallback reconciliation after the metadata coverage queue claim failed.', true );
		}

		return;
	}

	if ( null === $post_id ) {
		return;
	}

	$analysis_committed = false;

	// A later shutdown callback retries restoration after an uncatchable failure in this shutdown task.
	register_shutdown_function(
		static function () use ( $post_id, &$analysis_committed ): void {
			if ( $analysis_committed ) {
				return;
			}

			if ( ai4seo_restore_post_analysis_claim( $post_id ) ) {
				$analysis_committed = true;
				return;
			}

			ai4seo_debug_message( 984321735, 'Could not restore an uncommitted metadata coverage analysis claim.', true );
			ai4seo_schedule_post_analysis_reconciliation();
		}
	);

	try {
		$analysis_succeeded = ai4seo_analyze_post( $post_id );
	} catch ( Throwable $throwable ) {
		$analysis_succeeded = false;
		ai4seo_debug_message( 984321736, 'Metadata coverage analysis failed unexpectedly with ' . get_class( $throwable ) . ' and will be retried.', true );
	}

	if ( $analysis_succeeded ) {
		$analysis_committed = true;
		return;
	}

	if ( ai4seo_restore_post_analysis_claim( $post_id ) ) {
		$analysis_committed = true;
		return;
	}

	ai4seo_debug_message( 984321735, 'Could not restore the failed metadata coverage analysis claim.', true );

	if ( ! ai4seo_schedule_post_analysis_reconciliation() ) {
		ai4seo_debug_message( 984321734, 'Could not persist fallback reconciliation after metadata coverage analysis failed.', true );
	}
}

// endregion
// ___________________________________________________________________________________________.
