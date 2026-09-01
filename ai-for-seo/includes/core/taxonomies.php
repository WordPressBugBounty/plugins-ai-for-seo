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


// endregion
// ___________________________________________________________________________________________.
