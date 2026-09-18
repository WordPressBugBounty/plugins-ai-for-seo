<?php
/**
 * Read-only, administrator-requested inspection of plugin-owned post state.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Identify credential-bearing keys before including diagnostic excerpts.
 *
 * @param string $key Storage key or record field.
 * @return bool Whether values must be redacted.
 */
function ai4seo_is_sensitive_post_inspection_key( string $key ): bool {
	return 1 === preg_match( '/password|secret|token|credential|authorization|cookie|nonce|api[_-]?key|license[_-]?key/i', $key );
}

/**
 * Bound and redact a record belonging to the requested post.
 *
 * @param mixed $value Record or field.
 * @param int   $depth Current nesting depth.
 * @param int   $remaining Remaining nodes shared across this excerpt.
 * @return mixed Diagnostic-safe excerpt.
 */
function ai4seo_get_post_inspection_excerpt( $value, int $depth = 0, int &$remaining = 100 ) {
	if ( --$remaining < 0 || $depth > 8 ) {
		return '[capture limit]';
	}
	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $key => $item ) {
			if ( $remaining <= 0 ) {
				$result['__capture_limit'] = true;
				break;
			}
			$result[ substr( (string) $key, 0, 160 ) ] = ai4seo_is_sensitive_post_inspection_key( (string) $key )
				? '[redacted]' : ai4seo_get_post_inspection_excerpt( $item, $depth + 1, $remaining );
		}
		return $result;
	}
	if ( is_string( $value ) ) {
		return strlen( $value ) > 2048 ? substr( $value, 0, 2048 ) . '[capture limit]' : $value;
	}
	return is_scalar( $value ) || null === $value ? $value : '[unsupported value]';
}

/**
 * Find exact IDs, keyed post records, and clearly labeled text mentions in decoded options.
 *
 * @param mixed $value Decoded option subtree.
 * @param int   $post_id Requested post ID.
 * @param array $matches Collected bounded matches.
 * @param int   $remaining Remaining traversal nodes.
 * @param array $path Current path within the option.
 * @return bool Whether traversal completed without reaching a capture limit.
 */
function ai4seo_find_post_inspection_references( $value, int $post_id, array &$matches, int &$remaining, array $path = array() ): bool {
	if ( --$remaining < 0 || count( $path ) > 20 || count( $matches ) >= 50 ) {
		return false;
	}
	if ( is_array( $value ) ) {
		if ( isset( $value['post_id'] ) && ai4seo_normalize_option_post_id( $value['post_id'] ) === $post_id ) {
			$matches[] = array(
				'path'   => $path,
				'kind'   => 'post_record',
				'record' => ai4seo_get_post_inspection_excerpt( $value ),
			);
			return true;
		}
		$is_list = ! $value || array_keys( $value ) === range( 0, count( $value ) - 1 );
		foreach ( $value as $key => $item ) {
			if ( ai4seo_is_sensitive_post_inspection_key( (string) $key ) ) {
				continue;
			}
			$child_path = array_merge( $path, array( substr( (string) $key, 0, 160 ) ) );
			if ( ! $is_list && ai4seo_normalize_option_post_id( $key ) === $post_id ) {
				$matches[] = array(
					'path'   => $child_path,
					'kind'   => 'post_id_key',
					'record' => ai4seo_get_post_inspection_excerpt( $item ),
				);
				if ( count( $matches ) >= 50 ) {
					return false;
				}
				continue;
			}
			if ( ! ai4seo_find_post_inspection_references( $item, $post_id, $matches, $remaining, $child_path ) ) {
				return false;
			}
		}
		return true;
	}
	if ( ai4seo_normalize_option_post_id( $value ) === $post_id ) {
		$matches[] = array(
			'path' => $path,
			'kind' => 'exact_id',
		);
	} elseif ( is_string( $value ) && preg_match( '/(?<![0-9])' . $post_id . '(?![0-9])/', $value ) ) {
		// A textual mention is evidence of a reference, not proof of queue membership.
		$matches[] = array(
			'path' => $path,
			'kind' => 'text_mention',
		);
	}
	return true;
}

/**
 * Inspect plugin options without invoking readers that repair legacy queues or caches.
 *
 * Shared option contents are never dumped wholesale. Only matching paths and post-specific
 * records are logged; raw candidate matches remain explicit when decoding/capture is incomplete.
 *
 * @param int $post_id Requested post ID.
 * @return bool Whether the bounded inspection and its log writes succeeded.
 */
function ai4seo_debug_post_options( int $post_id ): bool {
	global $wpdb;

	$names            = array_merge( AI4SEO_ALL_POST_ID_OPTIONS, array( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME ) );
	$query            = ai4seo_prepare_database_query(
		'SELECT option_id, option_name, OCTET_LENGTH(option_value) AS raw_bytes
		FROM {{options_table}}
		WHERE (option_name LIKE {{prefix_1}} OR option_name LIKE {{prefix_2}} OR option_name LIKE {{prefix_3}}
			OR option_name LIKE {{prefix_4}} OR option_name LIKE {{prefix_5}} OR option_name LIKE {{prefix_6}})
		AND (option_name LIKE {{name_id_pattern}} OR option_value LIKE {{value_id_pattern}} OR option_name IN ({{status_names}}))
		ORDER BY option_id ASC LIMIT 501',
		array(
			'options_table'    => ai4seo_database_identifier_binding( 'table.options' ),
			'prefix_1'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( 'ai4seo_' ) . '%' ),
			'prefix_2'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( '_ai4seo_' ) . '%' ),
			'prefix_3'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( '_transient_ai4seo_' ) . '%' ),
			'prefix_4'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( '_transient__ai4seo_' ) . '%' ),
			'prefix_5'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( '_transient_timeout_ai4seo_' ) . '%' ),
			'prefix_6'         => ai4seo_database_scalar_binding( '%s', $wpdb->esc_like( '_transient_timeout__ai4seo_' ) . '%' ),
			'name_id_pattern'  => ai4seo_database_scalar_binding( '%s', '%' . $wpdb->esc_like( (string) $post_id ) . '%' ),
			'value_id_pattern' => ai4seo_database_scalar_binding( '%s', '%' . $wpdb->esc_like( (string) $post_id ) . '%' ),
			'status_names'     => ai4seo_database_list_binding( '%s', $names ),
		)
	);
	$wpdb->last_error = '';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- The typed compiler owns all bindings for this bounded raw diagnostic read.
	$rows = false === $query ? false : $wpdb->get_results( $query, ARRAY_A );
	if ( $wpdb->last_error || ! is_array( $rows ) ) {
		ai4seo_debug_message( 728451914, 'Post option inspection failed: ' . $post_id );
		return false;
	}
	$has_more    = count( $rows ) > 500;
	$rows        = array_slice( $rows, 0, 500 );
	$states      = array_fill_keys( $names, $has_more ? 'not_observed_within_limit' : 'missing' );
	$byte_budget = 8 * 1024 * 1024;
	$logged      = true;
	foreach ( $rows as $row ) {
		$name      = $row['option_name'];
		$is_status = in_array( $name, $names, true );
		$report    = array(
			'post_id'     => $post_id,
			'option_name' => $name,
			'raw_bytes'   => (int) $row['raw_bytes'],
		);
		if ( $is_status ) {
			$states[ $name ] = 'unverified';
		}
		if ( ai4seo_is_sensitive_post_inspection_key( $name ) ) {
			$report['option_name'] = '[sensitive option #' . (int) $row['option_id'] . ']';
			$report['status']      = 'sensitive_option_not_exported';
		} elseif ( AI4SEO_DEBUG_MESSAGES_OPTION_NAME === $name ) {
			$report['status'] = 'diagnostic_log_not_recursively_exported';
		} elseif ( (int) $row['raw_bytes'] > 2 * 1024 * 1024 || (int) $row['raw_bytes'] > $byte_budget ) {
			$report['status'] = 'byte_limit_candidate_unverified';
		} else {
			$wpdb->last_error = '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact option ID read is bounded even if the value grows between inspection queries.
			$value_row = $wpdb->get_row( $wpdb->prepare( "SELECT LEFT(CAST(option_value AS BINARY), %d) AS raw_value FROM {$wpdb->options} WHERE option_id = %d AND option_name = %s", (int) $row['raw_bytes'] + 1, (int) $row['option_id'], $name ), ARRAY_A );
			$raw       = is_array( $value_row ) ? ( $value_row['raw_value'] ?? null ) : null;
			if ( $wpdb->last_error || ! is_string( $raw ) || strlen( $raw ) !== (int) $row['raw_bytes'] ) {
				$report['status'] = 'read_failed_or_changed';
				$logged           = false;
			} else {
				$byte_budget -= strlen( $raw );
				$value        = ai4seo_safe_maybe_unserialize( $raw );
				if ( is_string( $value ) ) {
					$json_value = json_decode( $value, true );
					if ( JSON_ERROR_NONE === json_last_error() ) {
						$value = $json_value;
					}
				}
				$matches                    = array();
				$remaining                  = 100000;
				$complete                   = ai4seo_find_post_inspection_references( $value, $post_id, $matches, $remaining );
				$name_matches               = 1 === preg_match( '/(?<![0-9])' . $post_id . '(?![0-9])/', $name );
				$report['status']           = $complete ? 'inspected' : 'traversal_limit';
				$report['matches']          = $matches;
				$report['name_contains_id'] = $name_matches;
				if ( $name_matches ) {
					$report['post_option_value'] = ai4seo_get_post_inspection_excerpt( $value );
				}
				if ( $is_status ) {
					$ids                            = ai4seo_normalize_option_post_id_collection( $value );
					$states[ $name ]                = is_array( $value ) ? ( in_array( $post_id, $ids, true ) ? 'member' : 'not_member' ) : 'invalid_collection';
					$report['membership']           = $states[ $name ];
					$report['unique_post_id_count'] = count( $ids );
					$report['stored_entry_count']   = is_array( $value ) ? count( $value ) : null;
				} elseif ( ! $matches && ! $name_matches && $complete ) {
					// Preserve uncertain candidates from corrupt storage instead of treating them as a clean absence.
					if ( false !== $value || 'b:0;' === $raw ) {
						continue;
					}
					$report['status'] = 'undecodable_candidate';
				}
			}
		}
		$row_logged = ai4seo_debug_message( 728451913, 'Post option inspection: ' . wp_json_encode( $report ) );
		$logged     = $row_logged && $logged;
	}
	$summary_logged = ai4seo_debug_message(
		728451912,
		'Post status inspection: ' . wp_json_encode(
			array(
				'post_id'             => $post_id,
				'option_memberships'  => $states,
				'candidate_count'     => count( $rows ),
				'has_more_candidates' => $has_more,
				'option_row_limit'    => 500,
				'option_byte_limit'   => 2 * 1024 * 1024,
				'total_byte_limit'    => 8 * 1024 * 1024,
				'bytes_read'          => 8 * 1024 * 1024 - $byte_budget,
				'scope'               => 'current_site_plugin_options_and_plugin_transients',
			)
		)
	);
	return $summary_logged && $logged;
}

/**
 * Run the Help inspector without generating content, repairing metadata, or changing queues.
 *
 * @param int $post_id Exact selected post ID, including an orphan whose state needs inspection.
 * @return array{success: bool, message: string} Completion message.
 */
function ai4seo_debug_post( int $post_id ): array {
	global $wpdb;

	if ( $post_id <= 0 || ! ai4seo_can_administer_plugin() ) {
		return array(
			'success' => false,
			'message' => __( 'You must be a plugin administrator and enter a valid post ID.', 'ai-for-seo' ),
		);
	}
	if ( 'database' !== ai4seo_get_setting( AI4SEO_SETTING_DEBUG_OUTPUT_MODE ) ) {
		return array(
			'success' => false,
			'message' => __( 'Set Preferred debug output to Store in the database, save the debug settings, and try again.', 'ai-for-seo' ),
		);
	}
	$previous_error       = $wpdb->last_error;
	$previous_suppression = $wpdb->suppress_errors( true );
	$success              = false;
	try {
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read only the selected post's identity/status; do not invoke analysis or cache-repair helpers.
		$post                = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_type, post_status, post_parent, post_mime_type FROM {$wpdb->posts} WHERE ID = %d", $post_id ), ARRAY_A );
		$post_read_succeeded = ! $wpdb->last_error;
		$header_logged       = ai4seo_debug_message(
			728451915,
			'Post inspection: ' . wp_json_encode(
				array(
					'post_id'             => $post_id,
					'build'               => 'post-inspection-20260917-1',
					'plugin_version'      => AI4SEO_PLUGIN_VERSION_NUMBER,
					'post_read_succeeded' => $post_read_succeeded,
					'post'                => $post,
					'coverage_settings'   => array(
						'active_meta_tags'             => ai4seo_get_setting( AI4SEO_SETTING_ACTIVE_META_TAGS ),
						'active_attachment_attributes' => ai4seo_get_setting( AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES ),
						'sync_to_seo_plugins'          => ai4seo_get_setting( AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS ),
					),
					'snapshot'            => 'read_only_observations_not_transactional',
				)
			)
		);
		$metadata            = ai4seo_debug_generated_data_postmeta( $post_id, true );
		$options_succeeded   = ai4seo_debug_post_options( $post_id );
		$success             = $post_read_succeeded && $header_logged && $metadata['success'] && $options_succeeded;
	} catch ( Throwable $exception ) {
		ai4seo_record_metadata_save_diagnostic( 728451914, 'post_inspection', 'inspection_exception', array( 'post_id' => $post_id ), $exception );
	} finally {
		$wpdb->last_error = $previous_error;
		$wpdb->suppress_errors( $previous_suppression );
	}
	return array(
		'success' => $success,
		'message' => $success
			? __( 'Post inspection collected. Copy the Debug Message Log for support. Capture limits and omitted sensitive values are identified in the report. No post data or queue state was changed.', 'ai-for-seo' )
			: __( 'Post inspection could not collect or log every section. Check the Debug Message Log for details. No post data or queue state was changed.', 'ai-for-seo' ),
	);
}
