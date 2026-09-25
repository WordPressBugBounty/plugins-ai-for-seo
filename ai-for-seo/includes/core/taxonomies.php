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

	// Map Woo attribute archive settings if WooCommerce is present.
	$woo_attr_archive_on = array(); // taxonomy => bool.
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		$attrs = wc_get_attribute_taxonomies();
		if ( is_array( $attrs ) ) {
			foreach ( $attrs as $attr ) {
				if ( ! empty( $attr->attribute_name ) ) {
					$tax_name                         = 'pa_' . sanitize_key( $attr->attribute_name );
					$woo_attr_archive_on[ $tax_name ] = ! empty( $attr->attribute_public );
				}
			}
		}
	}

	// Get public taxonomies.
	$tax_objects = get_taxonomies(
		array(
			'public' => true,
		),
		'objects'
	);

	$results = array();

	foreach ( $tax_objects as $tax_name => $tax_obj ) {
		// Must be queryable and have rewrite rules to expose pretty URLs.
		$has_rewrite  = ! empty( $tax_obj->rewrite );
		$is_queryable = ! empty( $tax_obj->publicly_queryable );
		if ( ! $has_rewrite || ! $is_queryable ) {
			continue;
		}

		// Woo and attributes flags.
		$is_woo       = in_array( $tax_name, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( $tax_name, 'pa_' );
		$is_attribute = 0 === strpos( $tax_name, 'pa_' );

		// For Woo attributes: respect "Enable archives".
		if ( $is_attribute ) {
			$archives_on = isset( $woo_attr_archive_on[ $tax_name ] ) ? (bool) $woo_attr_archive_on[ $tax_name ] : true;
			if ( ! $archives_on ) {
				continue; // Skip attributes without archives.
			}
		}

		// Count terms cheaply.
		$term_count = (int) wp_count_terms(
			array(
				'taxonomy'   => $tax_name,
				'hide_empty' => false,
			)
		);

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
			'archives_on'    => true, // reached only if queryable + rewrite (+ attr archives on).
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
