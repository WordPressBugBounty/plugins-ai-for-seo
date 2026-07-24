<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region THIRD PARTY SEO PLUGINS ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Collects all the currently supported and active third party SEO plugins
 *
 * @return array The supported and currently active third party SEO plugins
 */
function ai4seo_get_active_third_party_seo_plugin_details(): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 500078657, 'Prevented loop', true );
		return array();
	}

	$active_supported_third_party_seo_plugin_details = array();

	$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

	foreach ( $third_party_seo_plugin_details as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( ai4seo_is_plugin_or_theme_active( $this_third_party_seo_plugin_identifier ) ) {
			$active_supported_third_party_seo_plugin_details[ $this_third_party_seo_plugin_identifier ] = $this_third_party_seo_plugin_details;
		}
	}

	return $active_supported_third_party_seo_plugin_details;
}

// =========================================================================================== \\

/**
 * Returns the keyphrase of the currently active third party SEO plugin, if it exists
 *
 * @param int $post_id The post id.
 * @return string The keyphrase or an empty string
 */
function ai4seo_get_any_third_party_seo_plugin_keyphrase( int $post_id ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 608212638, 'Prevented loop', true );
		return '';
	}

	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'] )
			|| empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] ) ) {
			continue;
		}

		$keyphrase_postmeta_key = sanitize_text_field( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] );

		$this_keyphrase = get_post_meta( $post_id, $keyphrase_postmeta_key, true );

		if ( ! empty( $this_keyphrase ) && is_string( $this_keyphrase ) ) {
			return $this_keyphrase;
		}
	}

	return '';
}

// =========================================================================================== \\

/**
 * Returns the key phrases for the given post ids (based on the currently active third party seo plugin)
 *
 * @param array $post_ids post ids.
 * @return array key phrases by post id or null on error
 */
function ai4seo_read_third_party_seo_plugin_key_phrases( array $post_ids ): ?array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 807307700, 'Prevented loop', true );
		return array();
	}

	if ( ! $post_ids ) {
		return array();
	}

	$sanitized_post_ids = array_map( 'intval', $post_ids );
	$sanitized_post_ids = array_filter( $sanitized_post_ids );

	if ( empty( $sanitized_post_ids ) ) {
		return array();
	}

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size       = ai4seo_get_database_chunk_size();
	$sanitized_post_ids_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	// only consider the currently active third party seo plugins.
	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! $active_supported_third_party_seo_plugins ) {
		return array();
	}

	// go through all active third party seo plugins and get the key phrases.
	$key_phrases = array();

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'] )
			|| empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] ) ) {
			continue;
		}

		// if we found all key phrases, we can stop the loop.
		if ( count( $key_phrases ) === count( $post_ids ) ) {
			break;
		}

		$this_keyphrase_postmeta_key = sanitize_text_field( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] );

		foreach ( $sanitized_post_ids_chunks as $this_post_ids_chunk ) {
			if ( empty( $this_post_ids_chunk ) ) {
				continue;
			}

			$this_post_id_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

			$query_args = array_merge( array( $this_keyphrase_postmeta_key ), $this_post_ids_chunk );

			$this_postmeta_entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = %s
                    AND post_id IN ({$this_post_id_placeholders})",
					...$query_args
				),
				ARRAY_A
			);

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321654, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! $this_postmeta_entries ) {
				continue;
			}

			// loop through all key phrases and add them to the $ai4seo_this_page_post_ids array.
			foreach ( $this_postmeta_entries as $this_postmeta_entry ) {
				$this_post_id          = isset( $this_postmeta_entry['post_id'] ) ? intval( $this_postmeta_entry['post_id'] ) : 0;
				$this_key_phrase_value = isset( $this_postmeta_entry['meta_value'] ) ? sanitize_text_field( $this_postmeta_entry['meta_value'] ) : '';

				// Make sure that post id is numeric.
				if ( ! $this_post_id ) {
					continue;
				}

				// skip if we already have a key phrase for this post id.
				if ( isset( $key_phrases[ $this_post_id ] ) ) {
					continue;
				}

				// Add key phrase to the $ai4seo_this_page_post_ids array.
				$key_phrases[ $this_post_id ] = $this_key_phrase_value;
			}
		}
	}

	return $key_phrases;
}

// =========================================================================================== \\

/**
 * Returns the yoast seo scores for the given post ids
 *
 * @param array $post_ids post ids.
 * @return array yoast seo scores by post id or null on error
 */
function ai4seo_read_yoast_seo_scores( array $post_ids ): ?array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 647946549, 'Prevented loop', true );
		return array();
	}

	// todo: make this whole function dynamic.

	// Make sure that yoast seo plugin is active.
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ) ) {
		return array();
	}

	if ( ! $post_ids ) {
		return array();
	}

	// Normalize post IDs before binding them to the generated placeholders.
	$sanitized_post_ids = array_map(
		function ( $id ) {
			return intval( $id );
		},
		$post_ids
	);

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size      = ai4seo_get_database_chunk_size();
	$sanitized_post_id_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	$yoast_seo_scores = array();

	foreach ( $sanitized_post_id_chunks as $this_post_id_chunk ) {
		$this_post_ids_placeholders = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );

		// Read Yoast scores for the current chunk with post IDs bound as placeholders.
		$this_chunk_yoast_seo_scores = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND post_id IN ( {$this_post_ids_placeholders} )",
				...array_merge(
					array( '_yoast_wpseo_linkdex' ),
					$this_post_id_chunk
				)
			)
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321655, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_chunk_yoast_seo_scores ) {
			continue;
		}

		$yoast_seo_scores = array_merge( $yoast_seo_scores, $this_chunk_yoast_seo_scores );
	}

	if ( ! $yoast_seo_scores ) {
		return array();
	}

	// loop through all yoast seo scores and add them to the $ai4seo_this_page_post_ids array.
	$seo_scores = array();

	foreach ( $yoast_seo_scores as $yoast_seo_score ) {
		$post_id   = $yoast_seo_score->post_id;
		$seo_score = $yoast_seo_score->meta_value;

		// Make sure that post id is numeric.
		if ( ! is_numeric( $post_id ) || ! $post_id ) {
			continue;
		}

		// Add seo score to the $ai4seo_this_page_post_ids array.
		$seo_scores[ $post_id ] = $seo_score;
	}

	return $seo_scores;
}


// endregion
// ___________________________________________________________________________________________.
