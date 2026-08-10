<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region POSTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

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

	// Per-request manual override stack. Use push/pop helpers below.
	static $ai4seo_post_context_stack = array();

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
		 * @param int $post_id
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
		/** @var WP_Post|null $post */
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
		/** @var WP_Post|null $post */
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

// =========================================================================================== \\

/**
 * Push a temporary post context ID.
 * Call before entering a custom loop; pair with ai4seo_pop_post_context().
 *
 * @param int $post_id
 * @return void
 * @since 2.1.4
 */
function ai4seo_push_post_context( int $post_id ) {
	static $ai4seo_post_context_stack = array(); // same static as above by function scope.
	$ai4seo_post_context_stack[]      = (int) $post_id;
}

// =========================================================================================== \\

/**
 * Pop the last pushed post context ID.
 *
 * @since 2.1.4
 * @return void
 */
function ai4seo_pop_post_context() {
	static $ai4seo_post_context_stack = array(); // same static as above by function scope.
	if ( ! empty( $ai4seo_post_context_stack ) ) {
		array_pop( $ai4seo_post_context_stack );
	}
}

// =========================================================================================== \\

/**
 * Returns all authors that currently own entries for the given post types.
 *
 * @param array $supported_post_types
 * @param array $post_statuses
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

	$post_type_placeholders   = implode( ', ', array_fill( 0, count( $supported_post_types ), '%s' ) );
	$post_status_placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );

	$sql = $wpdb->prepare(
		"SELECT DISTINCT post_author
        FROM {$wpdb->posts}
        WHERE post_author > 0
        AND post_type IN ($post_type_placeholders)
        AND post_status IN ($post_status_placeholders)",
		...array_merge( $supported_post_types, $post_statuses )
	);

	// Safe: $sql is prepared immediately above; dynamic IN lists contain only generated placeholders.
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
		$available_post_authors       = array();
		$available_post_authors_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE );

		if ( ! is_array( $available_post_authors_cache ) ) {
			$available_post_authors_cache = array();
		}

		$available_post_authors_cache[ $available_post_authors_cache_key ] = $available_post_authors;

		if ( count( $available_post_authors_cache ) > 10 ) {
			$available_post_authors_cache = array_slice( $available_post_authors_cache, -10, null, true );
		}

		ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE,
			$available_post_authors_cache,
			true,
			HOUR_IN_SECONDS
		);

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

	$available_post_authors_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE );

	if ( ! is_array( $available_post_authors_cache ) ) {
		$available_post_authors_cache = array();
	}

	$available_post_authors_cache[ $available_post_authors_cache_key ] = $available_post_authors;

	if ( count( $available_post_authors_cache ) > 10 ) {
		$available_post_authors_cache = array_slice( $available_post_authors_cache, -10, null, true );
	}

	ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE,
		$available_post_authors_cache,
		true,
		HOUR_IN_SECONDS
	);

	return $available_post_authors;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Returns all disabled post author IDs from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_post_author_ids(): array {
	return ai4seo_get_disabled_author_ids_by_setting_name( AI4SEO_SETTING_DISABLED_POST_AUTHORS );
}

// =========================================================================================== \\

/**
 * Returns all disabled attachment post author IDs from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_attachment_post_author_ids(): array {
	return ai4seo_get_disabled_author_ids_by_setting_name( AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS );
}

// =========================================================================================== \\

/**
 * Returns supported taxonomy terms that are connected to supported content types.
 *
 * @return array Array keyed by taxonomy name with labels and term IDs mapped to names.
 */
function ai4seo_get_supported_taxonomy_terms(): array {
	global $wpdb;

	static $ai4seo_supported_taxonomy_terms = null;

	if ( is_array( $ai4seo_supported_taxonomy_terms ) ) {
		return $ai4seo_supported_taxonomy_terms;
	}

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

	$taxonomy_names         = array_keys( $supported_taxonomies );
	$post_type_placeholders = implode( ', ', array_fill( 0, count( $supported_post_types ), '%s' ) );
	$database_chunk_size    = function_exists( 'ai4seo_get_database_chunk_size' ) ? (int) ai4seo_get_database_chunk_size() : 1000;

	if ( $database_chunk_size < 1 ) {
		$database_chunk_size = 1000;
	}

	$sql = $wpdb->prepare(
		"SELECT DISTINCT tr.term_taxonomy_id
        FROM {$wpdb->term_relationships} AS tr
        INNER JOIN {$wpdb->posts} AS p
            ON tr.object_id = p.ID
        WHERE p.post_type IN ($post_type_placeholders)
        AND p.post_status IN ('publish', 'future')",
		...$supported_post_types
	);

	// Safe: $sql is prepared immediately above; the post type IN list is generated placeholders only.
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

	$supported_taxonomy_term_rows      = array();
	$taxonomy_placeholders             = implode( ', ', array_fill( 0, count( $taxonomy_names ), '%s' ) );
	$supported_term_taxonomy_id_chunks = array_chunk( $supported_term_taxonomy_ids, $database_chunk_size );

	foreach ( $supported_term_taxonomy_id_chunks as $this_supported_term_taxonomy_id_chunk ) {
		$term_taxonomy_id_placeholders = implode( ', ', array_fill( 0, count( $this_supported_term_taxonomy_id_chunk ), '%d' ) );

		$sql = $wpdb->prepare(
			"SELECT tt.taxonomy, t.term_id, t.name
            FROM {$wpdb->term_taxonomy} AS tt
            INNER JOIN {$wpdb->terms} AS t
                ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($taxonomy_placeholders)
            AND tt.term_taxonomy_id IN ($term_taxonomy_id_placeholders)",
			...array_merge( $taxonomy_names, $this_supported_term_taxonomy_id_chunk )
		);

		// Safe: $sql is prepared immediately above; taxonomy and term ID IN lists are placeholders only.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this_supported_taxonomy_term_rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321705, 'Database error: ' . $wpdb->last_error, true );
			$ai4seo_supported_taxonomy_terms = array();
			return $ai4seo_supported_taxonomy_terms;
		}

		if ( $this_supported_taxonomy_term_rows ) {
			$supported_taxonomy_term_rows = array_merge( $supported_taxonomy_term_rows, $this_supported_taxonomy_term_rows );
		}
	}

	foreach ( (array) $supported_taxonomy_term_rows as $this_supported_taxonomy_term_row ) {
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

// =========================================================================================== \\

/**
 * Sanitizes a disabled-taxonomy-terms setting value.
 *
 * @param mixed $disabled_taxonomy_terms
 * @param bool  $restrict_to_supported_taxonomies
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

// =========================================================================================== \\

/**
 * Returns all disabled taxonomy terms from the user setting.
 *
 * @return array
 */
function ai4seo_get_disabled_taxonomy_terms(): array {
	return ai4seo_sanitize_disabled_taxonomy_terms_value( ai4seo_get_setting( AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ) );
}

// =========================================================================================== \\

/**
 * Returns whether posts should be excluded as soon as they match any disabled taxonomy term.
 *
 * @return bool
 */
function ai4seo_should_exclude_posts_if_any_disabled_taxonomy_term_matches(): bool {
	return (bool) ai4seo_get_setting( AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM );
}

// =========================================================================================== \\

/**
 * Builds SQL fragments for matching disabled taxonomy terms.
 *
 * @param array $disabled_taxonomy_terms
 * @return array
 */
function ai4seo_get_disabled_taxonomy_term_sql_parts( array $disabled_taxonomy_terms ): array {
	$disabled_taxonomy_terms = ai4seo_sanitize_disabled_taxonomy_terms_value( $disabled_taxonomy_terms );

	if ( ! $disabled_taxonomy_terms ) {
		return array(
			'condition'      => '',
			'values'         => array(),
			'taxonomy_names' => array(),
		);
	}

	$matching_conditions = array();
	$matching_values     = array();

	foreach ( $disabled_taxonomy_terms as $taxonomy_name => $term_ids ) {
		if ( ! $term_ids ) {
			continue;
		}

		$term_placeholders     = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
		$matching_conditions[] = "(tt.taxonomy = %s AND tt.term_id IN ($term_placeholders))";
		$matching_values[]     = $taxonomy_name;
		$matching_values       = array_merge( $matching_values, $term_ids );
	}

	if ( ! $matching_conditions ) {
		return array(
			'condition'      => '',
			'values'         => array(),
			'taxonomy_names' => array(),
		);
	}

	return array(
		'condition'      => implode( ' OR ', $matching_conditions ),
		'values'         => $matching_values,
		'taxonomy_names' => array_keys( $disabled_taxonomy_terms ),
	);
}

// =========================================================================================== \\

/**
 * Returns post IDs whose assigned taxonomy terms include at least one disabled term.
 *
 * @param array $post_ids
 * @param array $disabled_taxonomy_terms
 * @return array
 */
function ai4seo_get_post_ids_with_any_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms ): array {
	global $wpdb;

	$post_ids = array_map( 'intval', $post_ids );
	$post_ids = array_values( array_unique( $post_ids ) );
	$post_ids = array_filter(
		$post_ids,
		static function ( $post_id ) {
			return $post_id > 0;
		}
	);

	$disabled_taxonomy_term_sql_parts = ai4seo_get_disabled_taxonomy_term_sql_parts( $disabled_taxonomy_terms );

	if ( ! $post_ids || '' === $disabled_taxonomy_term_sql_parts['condition'] ) {
		return array();
	}

	$excluded_post_ids   = array();
	$database_chunk_size = function_exists( 'ai4seo_get_database_chunk_size' ) ? (int) ai4seo_get_database_chunk_size() : 1000;

	if ( $database_chunk_size < 1 ) {
		$database_chunk_size = 1000;
	}

	$post_id_chunks = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_id_chunks as $this_post_id_chunk ) {
		$post_id_placeholders = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );

		// Safe: disabled taxonomy SQL parts contain only generated %s/%d placeholders and prepared values.
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT DISTINCT tr.object_id
            FROM {$wpdb->term_relationships} AS tr
            INNER JOIN {$wpdb->term_taxonomy} AS tt
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id IN ($post_id_placeholders)
            AND (" . $disabled_taxonomy_term_sql_parts['condition'] . ')',
			...array_merge( $this_post_id_chunk, $disabled_taxonomy_term_sql_parts['values'] )
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Safe: $sql is prepared immediately above with sanitized post IDs and generated taxonomy-term placeholders/values.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$this_excluded_post_ids = $wpdb->get_col( $sql );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321707, 'Database error: ' . $wpdb->last_error, true );
			continue;
		}

		if ( $this_excluded_post_ids ) {
			$excluded_post_ids = array_merge( $excluded_post_ids, array_map( 'intval', $this_excluded_post_ids ) );
		}
	}

	$excluded_post_ids = array_values( array_unique( $excluded_post_ids ) );
	sort( $excluded_post_ids, SORT_NUMERIC );

	return $excluded_post_ids;
}

// =========================================================================================== \\

/**
 * Returns post IDs whose assigned taxonomy terms are all disabled.
 *
 * Posts without any assigned relevant taxonomy term are not returned.
 *
 * @param array $post_ids
 * @param array $disabled_taxonomy_terms
 * @return array
 */
function ai4seo_get_post_ids_with_only_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms ): array {
	global $wpdb;

	$post_ids = array_map( 'intval', $post_ids );
	$post_ids = array_values( array_unique( $post_ids ) );
	$post_ids = array_filter(
		$post_ids,
		static function ( $post_id ) {
			return $post_id > 0;
		}
	);

	$disabled_taxonomy_term_sql_parts = ai4seo_get_disabled_taxonomy_term_sql_parts( $disabled_taxonomy_terms );

	if ( ! $post_ids || '' === $disabled_taxonomy_term_sql_parts['condition'] || ! $disabled_taxonomy_term_sql_parts['taxonomy_names'] ) {
		return array();
	}

	$excluded_post_ids   = array();
	$database_chunk_size = function_exists( 'ai4seo_get_database_chunk_size' ) ? (int) ai4seo_get_database_chunk_size() : 1000;

	if ( $database_chunk_size < 1 ) {
		$database_chunk_size = 1000;
	}

	$post_id_chunks        = array_chunk( $post_ids, $database_chunk_size );
	$taxonomy_placeholders = implode( ', ', array_fill( 0, count( $disabled_taxonomy_term_sql_parts['taxonomy_names'] ), '%s' ) );

	foreach ( $post_id_chunks as $this_post_id_chunk ) {
		$post_id_placeholders = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );

		// Safe: disabled taxonomy SQL parts contain only generated %s/%d placeholders and prepared values.
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT tr.object_id
            FROM {$wpdb->term_relationships} AS tr
            INNER JOIN {$wpdb->term_taxonomy} AS tt
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy IN ($taxonomy_placeholders)
            AND tr.object_id IN ($post_id_placeholders)
            GROUP BY tr.object_id
            HAVING COUNT(DISTINCT tt.term_taxonomy_id) > 0
            AND COUNT(DISTINCT tt.term_taxonomy_id) = SUM(CASE WHEN (" . $disabled_taxonomy_term_sql_parts['condition'] . ') THEN 1 ELSE 0 END)',
			...array_merge(
				$disabled_taxonomy_term_sql_parts['taxonomy_names'],
				$this_post_id_chunk,
				$disabled_taxonomy_term_sql_parts['values']
			)
		);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Safe: $sql is prepared immediately above with sanitized taxonomy names, post IDs, and generated taxonomy-term placeholders/values.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$this_excluded_post_ids = $wpdb->get_col( $sql );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321706, 'Database error: ' . $wpdb->last_error, true );
			continue;
		}

		if ( $this_excluded_post_ids ) {
			$excluded_post_ids = array_merge( $excluded_post_ids, array_map( 'intval', $this_excluded_post_ids ) );
		}
	}

	$excluded_post_ids = array_values( array_unique( $excluded_post_ids ) );
	sort( $excluded_post_ids, SORT_NUMERIC );

	return $excluded_post_ids;
}

// =========================================================================================== \\

/**
 * Returns all post IDs that should be excluded by the disabled-taxonomy-terms setting mode.
 *
 * @param array     $post_ids
 * @param array     $disabled_taxonomy_terms
 * @param bool|null $exclude_on_any_disabled_taxonomy_term Optional. Defaults to the user setting.
 * @return array
 */
function ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool $exclude_on_any_disabled_taxonomy_term = null ): array {
	if ( null === $exclude_on_any_disabled_taxonomy_term ) {
		$exclude_on_any_disabled_taxonomy_term = ai4seo_should_exclude_posts_if_any_disabled_taxonomy_term_matches();
	}

	if ( $exclude_on_any_disabled_taxonomy_term ) {
		return ai4seo_get_post_ids_with_any_disabled_taxonomy_terms( $post_ids, $disabled_taxonomy_terms );
	}

	return ai4seo_get_post_ids_with_only_disabled_taxonomy_terms( $post_ids, $disabled_taxonomy_terms );
}

// =========================================================================================== \\

/**
 * Filters out posts based on the disabled-taxonomy-terms setting mode.
 *
 * @param array     $post_ids
 * @param array     $disabled_taxonomy_terms
 * @param bool|null $exclude_on_any_disabled_taxonomy_term Optional. Defaults to the user setting.
 * @return array
 */
function ai4seo_filter_post_ids_by_disabled_taxonomy_terms( array $post_ids, array $disabled_taxonomy_terms, ?bool $exclude_on_any_disabled_taxonomy_term = null ): array {
	$excluded_post_ids = ai4seo_get_post_ids_excluded_by_disabled_taxonomy_terms(
		$post_ids,
		$disabled_taxonomy_terms,
		$exclude_on_any_disabled_taxonomy_term
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

// =========================================================================================== \\

/**
 * Returns all disabled author IDs from a setting.
 *
 * @param string $setting_name
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

// =========================================================================================== \\

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
		if ( in_array( $post_type->name, $excluded_post_types ) ) {
			continue;
		}

		// Retain types that WordPress can surface through archives, normal posts, or search.
		if ( $post_type->has_archive || 'post' === $post_type->capability_type || ! $post_type->exclude_from_search ) {
			$publicly_accessible_post_types[ $post_type->name ] = $post_type->label;
		}
	}

	return $publicly_accessible_post_types;
}

// =========================================================================================== \\

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

	$publicly_accessible_post_types = ai4seo_get_publicly_accessible_post_types();

	$check_this_post_types = array_keys( $publicly_accessible_post_types );
	$check_this_post_types = ai4seo_deep_sanitize( $check_this_post_types, 'sanitize_key' );

	// go through supported_post_types and remove those we already found in $ai4seo_checked_supported_post_types.
	if ( is_array( $ai4seo_checked_supported_post_types ) && ! empty( $ai4seo_checked_supported_post_types ) ) {
		$check_this_post_types = array_diff( $check_this_post_types, $ai4seo_checked_supported_post_types );
	}

	if ( ! $check_this_post_types ) {
		$supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
	} else {
		// add entries to checked supported post types.
		$ai4seo_checked_supported_post_types = array_merge( (array) $ai4seo_checked_supported_post_types, $check_this_post_types );

		// Keep existing behavior (require at least one post). If you want empty CPTs too, replace the DB query with $check_this_post_types.
		// Hard cap post types to 256 entries to avoid oversized IN(...) clauses.
		$check_this_post_types = array_slice( $check_this_post_types, 0, 256 );

		if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE ) ) {
			$supported_post_types_from_database = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE );
		} else {
			$candidate_post_type_sql_parts = array();

			foreach ( $check_this_post_types as $this_index => $unused_post_type ) {
				$candidate_post_type_sql_parts[] = ( 0 === $this_index )
					? 'SELECT %s AS post_type'
					: 'UNION ALL SELECT %s AS post_type';
			}

			$candidate_post_type_sql = implode( "\n", $candidate_post_type_sql_parts );

			$sql = $wpdb->prepare(
				"SELECT candidate.post_type
                 FROM (
                    $candidate_post_type_sql
                 ) candidate
                 WHERE EXISTS (
                    SELECT 1
                    FROM {$wpdb->posts} p
                    WHERE p.post_type = candidate.post_type
                    AND p.post_status IN ('publish', 'future')
                    LIMIT 1
                 )
                 LIMIT 100",
				...$check_this_post_types
			);

			// Safe: $sql is prepared immediately above; candidate post type rows use generated %s placeholders and sanitized values.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$supported_post_types_from_database = $wpdb->get_col( $sql );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321677, 'Database error: ' . $wpdb->last_error, true );
				$supported_post_types_from_database = array();
			} else {
				ai4seo_update_environmental_variable(
					AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE,
					$supported_post_types_from_database,
					true,
					HOUR_IN_SECONDS
				);
			}
		}

		if ( ! $supported_post_types_from_database ) {
			$supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
		} else {
			// sanitize the supported post types from database.
			$supported_post_types_from_database = ai4seo_deep_sanitize( $supported_post_types_from_database, 'sanitize_key' );

			// add $ai4seo_cached_supported_post_types to supported post types.
			$ai4seo_cached_supported_post_types = array_merge( (array) $ai4seo_cached_supported_post_types, $supported_post_types_from_database );
			$ai4seo_cached_supported_post_types = array_values( array_unique( $ai4seo_cached_supported_post_types ) );

			// order the post types.
			sort( $ai4seo_cached_supported_post_types );

			$supported_post_types = $ai4seo_cached_supported_post_types;
		}
	}

	if ( ! isset( $supported_post_types ) ) {
		$supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
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

// =========================================================================================== \\

/**
 * @param int         $post_id The ID of the post to get the pure text content for.
 * @param bool        $debug Whether to enable debug mode (default: false).
 * @param string|null $strict_visible_text Optional structure-free visible text output.
 * @param string|null $first_h1 Optional first locally available H1 output.
 * @return string The pure text content of the post.
 */
function ai4seo_get_condensed_post_content_from_database(
	int $post_id,
	bool $debug = false,
	?string &$strict_visible_text = null,
	?string &$first_h1 = null
): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 561711889, 'Prevented loop', true );
		return '';
	}

	// Retrieve the post object.
	$post = get_post( $post_id );

	if ( ! $post ) {
		return ''; // Return empty if post is not found.
	}

	// Keep local editor and builder content available for structured analysis even when the legacy
	// transport path replaces a short body with the fully rendered permalink response.
	$local_combined_content = '';
	$post_content           = ai4seo_get_combined_post_content( $post_id, '', false, $debug, $local_combined_content );
	$analysis_content       = '' !== trim( $local_combined_content ) ? $local_combined_content : $post_content;
	$first_h1              = ai4seo_extract_first_local_h1( $analysis_content );

	// Preserve the existing transport condenser while deriving language evidence from the local source.
	ai4seo_condense_raw_post_content( $post_content );
	ai4seo_condense_raw_post_content( $analysis_content, 2000, 2250, $strict_visible_text );

	if ( $debug ) {
		ai4seo_debug_message( 614595339, 'FINAL POST CONTENT (condensed) >' . ai4seo_stringify( htmlspecialchars( $post_content ) ) );
	}

	return $post_content;
}

// =========================================================================================== \\

/**
 * Returns the post content to a given post_id by also reading the content of the most common page builders and
 * combining them into one content
 *
 * @param int         $post_id The post or page id to read the content from.
 * @param string      $editor_identifier The identifier of the editor to read the content from.
 * @param bool        $output_raw The output raw value.
 * @param bool        $debug Whether to enable debug mode (default: false).
 * @param string|null $local_combined_content Optional pre-remote-fallback editor and builder content output.
 * @return false|string The post or page content or false if the post_id is empty
 */
function ai4seo_get_combined_post_content(
	int $post_id = 0,
	string $editor_identifier = '',
	bool $output_raw = false,
	bool $debug = false,
	?string &$local_combined_content = null
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

	// Get post-object.
	$post = get_post( $post_id );

	// Define variable for the combined post- or page-content.
	$combined_content = array();

	// Get post-content.
	$post_content = $post->post_content;

	// apply short codes.
	$post_content = do_shortcode( $post_content );

	// Return post-content if not empty and not the same as the post-title or post-excerpt.
	if ( ! empty( $post_content ) ) {
		if ( $debug ) {
			ai4seo_debug_message( 369296244, 'POST CONTENT >' . ai4seo_stringify( htmlspecialchars( $post_content ) ) );
		}

		$combined_content[] = trim( $post_content );
	}

	// check if is_plugin_active() is available.
	$plugins_are_loaded = function_exists( 'is_plugin_active' );

	// Elementor: only if the post_content got less than 100 characters, as the post_content should contain even a clearer version of the content.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'elementor' === $editor_identifier ) && is_plugin_active( 'elementor/elementor.php' ) ) {
		// Get elementor-content.
		$elementor_content = get_post_meta( $post_id, '_elementor_data', true );

		// Return elementor-content if not empty.
		if ( ! empty( $elementor_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 264856340, 'ELEMENTOR CONTENT>' . ai4seo_stringify( htmlspecialchars( $elementor_content ) ) );
			}

			$combined_content[] = trim( $elementor_content );
		}
	}

	// Check if muffin-builder-plugin is active. If yes, only consider it's content as it's the content that is shown on the page.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'mfn-builder' === $editor_identifier ) && ( $current_theme->get( 'Name' ) === 'Betheme'
			|| ( $parent_theme && $parent_theme->get( 'Name' ) === 'Betheme' ) ) ) {
		// Get muffin-builder-content.
		$muffin_builder_content = get_post_meta( $post_id, 'mfn-page-items-seo', true );

		// Return muffin-builder-content if not empty.
		if ( ! empty( $muffin_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 288886929, 'MUFFIN BUILDER CONTENT>' . ai4seo_stringify( htmlspecialchars( $muffin_builder_content ) ) );
			}

			$combined_content[] = trim( $muffin_builder_content );
		}
	}

	// Check if beaver-builder-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'fl-builder' === $editor_identifier ) && is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) ) {
		// Get beaver-builder-content.
		$beaver_builder_content = get_post_meta( $post_id, '_fl_builder_data', true );

		// Return beaver-builder-content if not empty.
		if ( ! empty( $beaver_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 899351813, 'BEAVER BUILDER CONTENT>' . ai4seo_stringify( htmlspecialchars( $beaver_builder_content ) ) );
			}

			$combined_content[] = trim( $beaver_builder_content );
		}
	}

	// Check if divi-builder-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'divi-builder' === $editor_identifier ) && is_plugin_active( 'divi-builder/divi-builder.php' ) ) {
		// Get divi-builder-content.
		$divi_builder_content = get_post_meta( $post_id, '_et_pb_use_builder', true );

		// Return divi-builder-content if not empty.
		if ( ! empty( $divi_builder_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 412179553, 'DIVI BUILDER CONTENT>' . ai4seo_stringify( htmlspecialchars( $divi_builder_content ) ) );
			}

			$combined_content[] = trim( $divi_builder_content );
		}
	}

	// Check if oxygen-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'oxygen' === $editor_identifier ) && is_plugin_active( 'oxygen/functions.php' ) ) {
		// Get oxygen-content.
		$oxygen_content = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

		// Return oxygen-content if not empty.
		if ( ! empty( $oxygen_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 528624552, 'OXYGEN CONTENT>' . ai4seo_stringify( htmlspecialchars( $oxygen_content ) ) );
			}

			$combined_content[] = trim( $oxygen_content );
		}
	}

	// Check if brizy-plugin is active.
	if ( $plugins_are_loaded && ( ! $editor_identifier || 'brizy' === $editor_identifier ) && is_plugin_active( 'brizy/brizy.php' ) ) {
		// Get brizy-content.
		$brizy_content = get_post_meta( $post_id, 'brizy_post_uid', true );

		// Return brizy-content if not empty.
		if ( ! empty( $brizy_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 344263270, 'BRIZY CONTENT>' . ai4seo_stringify( htmlspecialchars( $brizy_content ) ) );
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
				ai4seo_debug_message( 823132530, 'REMOTE CONTENT>' . ai4seo_stringify( htmlspecialchars( $remote_content ) ) );
			}

			$combined_content = array( $remote_content );
		}
	}

	$combined_content = implode( ' ', $combined_content );

	// Apply the 'the_content' filter to the post content.
	if ( ! $output_raw ) {
		$filtered_combined_content = apply_filters( 'the_content', $combined_content );

		if ( $filtered_combined_content && strlen( $filtered_combined_content ) > strlen( $combined_content ) ) {
			if ( $debug ) {
				ai4seo_debug_message( 859196742, 'FILTERED COMBINED CONTENT>' . ai4seo_stringify( htmlspecialchars( $filtered_combined_content ) ) );
			}

			$combined_content = $filtered_combined_content;
		}
	}

	return $combined_content;
}

// =========================================================================================== \\

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
	$content = preg_replace( '/\[[a-zA-Z0-9_]+(\]|$)/', '', $content );

	// Remove opening vc_ shortcodes.
	$content = preg_replace( '/\[vc_[^\]]+(\]|$)/', '', $content );

	// Remove closing vc_ shortcodes.
	$content = preg_replace( '/\[\/vc_[^\]]+(\]|$)/', '', $content );

	// handle $shortcode_tags.
	$shortcodes = array_keys( $shortcode_tags );

	if ( $shortcodes ) {
		foreach ( $shortcodes as $shortcode ) {
			$content = preg_replace( '/\[' . $shortcode . '[^\]]*\]/', '', $content );
			$content = preg_replace( '/\[\/' . $shortcode . '[^\]]*\]/', '', $content );
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
	$temp_content = preg_replace( '/\[.*?\]/', '', $content );

	// Bound only the new analysis pass; transport processing above remains unchanged for legacy callers.
	$strict_analysis_source = ai4seo_get_bounded_metadata_analysis_source(
		is_string( $temp_content ) ? $temp_content : ''
	);
	$strict_visible_text = ai4seo_clean_metadata_visible_text( $strict_analysis_source );
	$strict_visible_text = ai4seo_remove_double_sentences( $strict_visible_text );
	$strict_visible_text = ai4seo_truncate_sentence( $strict_visible_text, $soft_cap, $hard_cap );

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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
	$content = preg_replace( '/\[.*?\]/s', '', $content );
	$content = is_string( $content ) ? wp_strip_all_tags( $content ) : '';
	$content = ai4seo_remove_urls_from_string( $content );

	// Remove still-encoded or unknown entities rather than counting their names as visible words.
	$content = preg_replace( '/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]+);/i', ' ', $content );
	$content = preg_replace( '/\s+/u', ' ', is_string( $content ) ? $content : '' );

	return is_string( $content ) ? trim( $content ) : '';
}

// =========================================================================================== \\

/**
 * Extract the first H1 already present in local/in-memory content.
 *
 * This helper never performs a remote request. The scan is byte-bounded to keep analysis cheap
 * even when an existing content source contains a very large rendered document.
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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Prepare the shared content and structured analysis used by manual and automated metadata generation.
 *
 * @param int    $post_id WordPress post ID.
 * @param string $submitted_content Content already supplied by manual generation, when available.
 * @return array{content:string,post_context:string,content_analysis:array}
 */
function ai4seo_prepare_metadata_generation_content_data(
	int $post_id,
	string $submitted_content = ''
): array {
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
		$post_content = ai4seo_get_condensed_post_content_from_database( $post_id, false, $body_text, $database_first_h1 );

		if ( ! $first_h1 ) {
			$first_h1 = $database_first_h1;
		}
	}

	// Build the legacy post-context string and structured title/excerpt evidence in the same data-access pass.
	$post_context = $post_content;
	ai4seo_add_post_context( $post_id, $post_context, false, false, $structured_context );

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

// =========================================================================================== \\

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
 */
function ai4seo_add_post_context(
	$post_id,
	&$content,
	bool $include_website_context = true,
	bool $include_first_section = true,
	?array &$structured_context = null
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

	// url.
	$post_url = get_permalink( $post_id );

	if ( $post_url ) {
		$context .= "URL: '" . $post_url . "'. ";
	}

	// post type.
	$post_type = get_post_type( $post_id );

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

	// woocommerce context.
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
	$post_title = get_the_title( $post_id );

	if ( $post_title ) {
		$structured_context['post_title'] = $post_title;
		$context .= "Sub Page Title: '" . $post_title . "'. ";
	}

	// excerpt.
	$post_excerpt = get_the_excerpt( $post_id );

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

// =========================================================================================== \\

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

// =========================================================================================== \\

function ai4seo_is_acf_content( $post_content ): bool {
	return strpos( $post_content, '<!-- wp:acf/' ) !== false;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

function ai4seo_calculate_metadata_credits_cost_per_post( $only_this_meta_tags = null ): int {
	// check all active meta tags.
	$metadata_price_table = ai4seo_get_metadata_price_table( $only_this_meta_tags );

	if ( empty( $metadata_price_table ) ) {
		return 1;
	}

	// calculate total costs.
	return array_sum( $metadata_price_table );
}

// =========================================================================================== \\

function ai4seo_get_metadata_price_table( $only_this_meta_tags = null ): array {
	$active_meta_tags = ai4seo_get_active_meta_tags();

	if ( empty( $active_meta_tags ) ) {
		return array();
	}

	$price_table = array();

	foreach ( $active_meta_tags as $this_active_meta_tag ) {
		if ( $only_this_meta_tags && is_array( $only_this_meta_tags ) && ! in_array( $this_active_meta_tag, $only_this_meta_tags ) ) {
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

// =========================================================================================== \\

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

// =========================================================================================== \\

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


// =========================================================================================== \\


/**
 * Is called when a post is updated or created, using the action hook "save_post". The function will add the post
 * id to the option "AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME" to be analyzed by the plugin.
 *
 * @param int          $post_id the post id.
 * @param WP_Post|null $post the post object.
 * @param bool         $update if the post is updated.
 * @return void
 */
function ai4seo_mark_post_to_be_analyzed( int $post_id, ?WP_Post $post = null, bool $update = false ) {
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

	// Make sure that the user is allowed to use this plugin.
	if ( ! ai4seo_can_manage_this_plugin() ) {
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
	ai4seo_add_post_ids_to_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME, $post_id );
}

// =========================================================================================== \\

/**
 * Analyzes the post, currently updating the metadata coverage
 *
 * @param int $post_id the post id.
 * @return void
 */
function ai4seo_analyze_post( int $post_id ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 516343145, 'Prevented loop', true );
		return;
	}

	if ( ! is_numeric( $post_id ) ) {
		return;
	}

	// read post.
	$post = get_post( $post_id );

	// check if the post could be read.
	if ( ! $post || is_wp_error( $post ) || ! isset( $post->post_type ) ) {
		return;
	}

	// ignore attachments.
	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	if ( in_array( $post->post_type, $supported_attachment_post_types ) ) {
		return;
	}

	ai4seo_refresh_one_posts_metadata_coverage_status( $post_id, $post );
}

// =========================================================================================== \\

function ai4seo_handle_posts_to_be_analyzed() {
	// Make sure that the user is allowed to use this plugin.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// get all posts that need to be analyzed.
	$posts_to_be_analyzed = ai4seo_get_post_ids_from_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME );

	// if there are no posts to be analyzed, return.
	if ( ! $posts_to_be_analyzed ) {
		return;
	}

	// get the first post to be analyzed.
	$post_id = array_shift( $posts_to_be_analyzed );

	// check if the post id is numeric.
	if ( is_numeric( $post_id ) ) {
		// analyze the post.
		ai4seo_analyze_post( $post_id );
	}

	// update the option.
	ai4seo_remove_post_ids_from_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME, $post_id );
}

// endregion
// ___________________________________________________________________________________________.
