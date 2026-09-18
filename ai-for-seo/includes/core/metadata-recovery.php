<?php
/**
 * Bounded active-metadata interpretation and conditional recovery.
 *
 * A resolved view is not a physical single-row snapshot. Conflicting fields are
 * absent from active_metadata and retain every distinct alternative in conflicts.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classify exact rows without choosing among different values or changing storage.
 *
 * @param int   $post_id Owning post.
 * @param array $rows Ordered exact rows, including one overflow sentinel if present.
 * @return array Resolved view, alternatives, fingerprint and physical rows.
 */
function ai4seo_classify_active_metadata_rows( int $post_id, array $rows ): array {
	$view = array(
		'classification'    => 'unreadable',
		'reason'            => 'invalid_metadata_shape',
		'active_metadata'   => array(),
		'conflicts'         => array(),
		'rows'              => array(),
		'fingerprint'       => '',
		'repair_incomplete' => false,
	);
	if ( $post_id <= 0 || count( $rows ) > AI4SEO_ACTIVE_METADATA_MAX_ROWS ) {
		$view['reason'] = 'row_limit_exceeded';
		return $view;
	}
	$alternatives = array();
	$identities   = array( (string) get_current_blog_id(), (string) $post_id );
	$previous_id  = 0;
	foreach ( $rows as $row ) {
		$meta_id = ai4seo_normalize_database_id( $row['meta_id'] ?? null );
		$values  = array();
		if ( false === $meta_id || $meta_id <= $previous_id
			|| AI4SEO_POST_META_ACTIVE_METADATA_META_KEY !== ( $row['meta_key'] ?? '' )
			|| ! is_string( $row['meta_value'] ?? null )
			|| strlen( $row['meta_value'] ) > AI4SEO_ACTIVE_METADATA_MAX_ROW_BYTES
			|| ! ai4seo_decode_active_metadata_postmeta_value_authoritatively( $row['meta_value'], $values ) ) {
			return $view;
		}
		$previous_id            = $meta_id;
		$identities[]           = $meta_id . ':' . hash( 'sha256', $row['meta_value'] );
		$row['active_metadata'] = $values;
		$view['rows'][]         = $row;
		foreach ( $values as $field => $value ) {
			if ( ! isset( $alternatives[ $field ] ) ) {
				$alternatives[ $field ] = array();
			}
			if ( ! in_array( $value, $alternatives[ $field ], true ) ) {
				$alternatives[ $field ][] = $value;
			}
		}
	}
	foreach ( $alternatives as $field => $values ) {
		if ( count( $values ) > 1 ) {
			$view['conflicts'][ $field ] = $values;
		} else {
			$view['active_metadata'][ $field ] = $values[0];
		}
	}
	$view['reason']      = '';
	$view['fingerprint'] = hash( 'sha256', implode( '|', $identities ) );
	if ( ! $rows ) {
		$view['classification'] = 'missing';
	} elseif ( 1 === count( $rows ) ) {
		$view['classification'] = 'single';
	} else {
		$view['classification'] = $view['conflicts'] ? 'conflicting_duplicates' : 'compatible_duplicates';
	}
	return $view;
}

/**
 * Read bounded, uncached storage. Truncation always fails classification.
 *
 * @param int $post_id Post ID.
 * @return array Resolved view; never implies one physical row exists.
 */
function ai4seo_read_active_metadata_resolved_view( int $post_id ): array {
	global $wpdb;
	$view           = ai4seo_classify_active_metadata_rows( 0, array() );
	$view['reason'] = 'storage_read_failed';
	if ( $post_id <= 0 ) {
		return $view;
	}
	$previous_suppress_errors = $wpdb->suppress_errors( true );
	try {
		// Read one overflow sentinel so oversized rows and row sets remain distinguishable from valid data.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded authoritative storage read, intentionally bypassing postmeta caches.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_key, LEFT(BINARY meta_value, %d) AS meta_value FROM $wpdb->postmeta WHERE post_id = %d AND BINARY meta_key = BINARY %s ORDER BY meta_id ASC LIMIT %d",
				AI4SEO_ACTIVE_METADATA_MAX_ROW_BYTES + 1,
				$post_id,
				AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
				AI4SEO_ACTIVE_METADATA_MAX_ROWS + 1
			),
			ARRAY_A
		);
		if ( ! $wpdb->last_error && is_array( $rows ) ) {
			$view = ai4seo_classify_active_metadata_rows( $post_id, $rows );
		}
	} catch ( Throwable $throwable ) {
		$view['reason'] = 'storage_read_exception';
	} finally {
		$wpdb->suppress_errors( $previous_suppress_errors );
	}
	return $view;
}

/**
 * Log only identities, field names and hashes; diagnostic failures are nonfatal.
 *
 * @param int    $post_id Post ID.
 * @param string $outcome Outcome identifier.
 * @param array  $view Observed view.
 * @param array  $fields Affected fields.
 * @return void
 */
function ai4seo_debug_metadata_recovery( int $post_id, string $outcome, array $view, array $fields = array() ): void {
	try {
		ai4seo_debug_message(
			728451916,
			'Metadata recovery: ' . wp_json_encode(
				array(
					'post_id'        => $post_id,
					'outcome'        => $outcome,
					'classification' => $view['classification'],
					'row_ids'        => array_column( $view['rows'], 'meta_id' ),
					'fields'         => $fields,
					'fingerprint'    => $view['fingerprint'],
				)
			)
		);
	} catch ( Throwable $throwable ) {
		// Logging is observational and must never affect metadata availability.
		return;
	}
}

/**
 * Delete one redundant row only while its exact merged survivor exists.
 *
 * @param int    $post_id Post ID.
 * @param array  $row Redundant exact row.
 * @param int    $survivor_id Retained row ID.
 * @param string $merged_raw Verified merged bytes.
 * @return bool Whether the guarded deletion took place.
 */
function ai4seo_delete_redundant_active_metadata_row( int $post_id, array $row, int $survivor_id, string $merged_raw ): bool {
	global $wpdb;
	$key = AI4SEO_POST_META_ACTIVE_METADATA_META_KEY;
	$ids = array( (int) $row['meta_id'] );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve WordPress metadata interception.
	$check = apply_filters( 'delete_post_metadata', null, $post_id, $key, $row['meta_value'], false );
	if ( null !== $check ) {
		return false;
	}
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror core's before-delete hooks.
	do_action( 'delete_post_meta', $ids, $post_id, $key, $row['meta_value'] );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror core's legacy before-delete hook.
	do_action( 'delete_postmeta', $ids );
	// The self-join protects against a survivor changed or removed by a hook/foreign writer between statements.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact row and survivor bytes guard deletion; caches are invalidated below.
	$result = $wpdb->query(
		$wpdb->prepare(
			"DELETE redundant FROM $wpdb->postmeta AS redundant INNER JOIN $wpdb->postmeta AS survivor ON survivor.meta_id = %d AND survivor.post_id = %d AND BINARY survivor.meta_key = BINARY %s AND BINARY survivor.meta_value = BINARY %s WHERE redundant.meta_id = %d AND redundant.post_id = %d AND BINARY redundant.meta_key = BINARY %s AND BINARY redundant.meta_value = BINARY %s AND redundant.meta_id <> survivor.meta_id",
			$survivor_id,
			$post_id,
			$key,
			$merged_raw,
			$ids[0],
			$post_id,
			$key,
			$row['meta_value']
		)
	);
	if ( 1 !== $result ) {
		return false;
	}
	wp_cache_delete( $post_id, 'post_meta' );
	ai4seo_invalidate_postmeta_table_count_cache_after_direct_delete();
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror core's after-delete hooks.
	do_action( 'deleted_post_meta', $ids, $post_id, $key, $row['meta_value'] );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Mirror core's legacy after-delete hook.
	do_action( 'deleted_postmeta', $ids );
	return true;
}

/**
 * Consolidate compatible rows under an already-owned metadata lock.
 *
 * @param int   $post_id Post ID.
 * @param array $view Exact observed storage.
 * @return array Fresh resolved view, including incomplete cleanup indication.
 */
function ai4seo_repair_active_metadata_under_lock( int $post_id, array $view ): array {
	if ( 'compatible_duplicates' !== $view['classification'] ) {
		return $view;
	}
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_active_metadata_postmeta_lock_name( $post_id ) ) ) {
		$view['repair_incomplete'] = true;
		return $view;
	}
	$merged_raw = wp_json_encode( $view['active_metadata'], JSON_UNESCAPED_UNICODE );
	if ( ! is_string( $merged_raw ) || strlen( $merged_raw ) > AI4SEO_ACTIVE_METADATA_MAX_ROW_BYTES ) {
		$view['repair_incomplete'] = true;
		return $view;
	}
	$survivor = $view['rows'][0];
	try {
		ai4seo_compare_and_swap_postmeta_row(
			(int) $survivor['meta_id'],
			$post_id,
			AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
			$survivor['meta_value'],
			$merged_raw,
			$survivor['meta_value']
		);
		if ( ai4seo_active_metadata_postmeta_row_matches_exact_value( $post_id, (int) $survivor['meta_id'], $merged_raw ) ) {
			foreach ( array_slice( $view['rows'], 1 ) as $row ) {
				if ( ! ai4seo_delete_redundant_active_metadata_row( $post_id, $row, (int) $survivor['meta_id'], $merged_raw ) ) {
					break;
				}
			}
		}
	} catch ( Throwable $throwable ) {
		// Reread decides what survived; never roll back by deleting or restoring old bytes.
		$view['repair_incomplete'] = true;
	}
	$result                      = ai4seo_read_active_metadata_resolved_view( $post_id );
	$result['repair_incomplete'] = 'single' !== $result['classification']
		|| $survivor['meta_id'] !== $result['rows'][0]['meta_id']
		|| ! hash_equals( $merged_raw, $result['rows'][0]['meta_value'] );
	ai4seo_debug_metadata_recovery( $post_id, $result['repair_incomplete'] ? 'repair_incomplete' : 'repaired', $result, array_keys( $view['active_metadata'] ) );
	return $result;
}

/**
 * Repair for an authorized editor or generation preflight; reads elsewhere stay read-only.
 *
 * @param int $post_id Post ID.
 * @return array Current resolved view.
 */
function ai4seo_recover_active_metadata( int $post_id ): array {
	$view = ai4seo_read_active_metadata_resolved_view( $post_id );
	if ( 'compatible_duplicates' !== $view['classification'] ) {
		return $view;
	}
	$lock_name = ai4seo_get_active_metadata_postmeta_lock_name( $post_id );
	$owned     = false;
	try {
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name ) ) {
			$owned = ai4seo_acquire_database_advisory_lock( $lock_name );
		}
		if ( $owned ) {
			$view = ai4seo_repair_active_metadata_under_lock( $post_id, ai4seo_read_active_metadata_resolved_view( $post_id ) );
		} else {
			$view['repair_incomplete'] = true;
		}
	} catch ( Throwable $throwable ) {
		$view['repair_incomplete'] = true;
	} finally {
		if ( $owned ) {
			try {
				ai4seo_release_database_advisory_lock( $lock_name );
			} catch ( Throwable $throwable ) {
				$view['repair_incomplete'] = true;
			}
		}
	}
	if ( $owned ) {
		try {
			wp_cache_delete( $post_id, 'post_meta' );
			ai4seo_purge_frontend_cache_for_post( $post_id );
			if ( ! ai4seo_refresh_one_posts_metadata_coverage_status( $post_id ) ) {
				ai4seo_schedule_generation_status_summary_rebuild();
			}
		} catch ( Throwable $throwable ) {
			try {
				ai4seo_schedule_generation_status_summary_rebuild();
			} catch ( Throwable $scheduling_error ) {
				ai4seo_debug_metadata_recovery( $post_id, 'coverage_retry_failed', $view );
			}
		}
	}
	return $view;
}

/**
 * Exclude unresolved or stale resolutions before any provider or primary writes.
 *
 * @param array $updates Proposed canonical field values.
 * @param array $view Observed storage.
 * @param array $context Optional fingerprint and explicit field resolutions.
 * @return array Safe subset of updates.
 */
function ai4seo_filter_metadata_recovery_updates( array $updates, array $view, array $context ): array {
	if ( 'unreadable' === $view['classification'] ) {
		return array();
	}
	foreach ( $updates as $field => $value ) {
		$resolution = $context['resolutions'][ $field ] ?? null;
		if ( ! isset( $view['conflicts'][ $field ] ) && null === $resolution ) {
			continue;
		}
		// Editor requests retain the selected alternative before applying field-specific normalization.
		$stored_value = $resolution['stored_value'] ?? $value;
		$fresh        = is_string( $context['fingerprint'] ?? null ) && hash_equals( $view['fingerprint'], $context['fingerprint'] );
		$valid        = is_array( $resolution ) && (
			'custom' === ( $resolution['kind'] ?? '' )
			|| ( 'stored' === ( $resolution['kind'] ?? '' ) && in_array( $stored_value, $view['conflicts'][ $field ] ?? array(), true ) )
		);
		if ( ! $fresh || ! $valid ) {
			unset( $updates[ $field ] );
		}
	}
	return $updates;
}

/**
 * Write safe fields across duplicates, leaving all other alternatives intact.
 *
 * @param int   $post_id Post ID.
 * @param array $updates Already filtered canonical values.
 * @param array $view Current exact view under the metadata lock.
 * @param array $details Receives verified fields and current storage.
 * @return bool Whether every requested field was verified.
 */
function ai4seo_write_duplicate_metadata_under_lock( int $post_id, array $updates, array $view, array &$details ): bool {
	$attempted = false;
	try {
		foreach ( $view['rows'] as $row ) {
			$raw = wp_json_encode( array_replace( $row['active_metadata'], $updates ), JSON_UNESCAPED_UNICODE );
			if ( ! is_string( $raw ) || strlen( $raw ) > AI4SEO_ACTIVE_METADATA_MAX_ROW_BYTES ) {
				break;
			}
			if ( ! hash_equals( $row['meta_value'], $raw ) ) {
				$attempted = true;
				if ( ! ai4seo_compare_and_swap_postmeta_row( (int) $row['meta_id'], $post_id, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, $row['meta_value'], $raw, $row['meta_value'] ) ) {
					break;
				}
			}
		}
	} catch ( Throwable $throwable ) {
		// The fresh view below identifies confirmed fields even after a later hook fails.
		$details['failure_reason'] = 'storage_exception';
	}
	$after     = ai4seo_read_active_metadata_resolved_view( $post_id );
	$after     = ai4seo_repair_active_metadata_under_lock( $post_id, $after );
	$confirmed = ai4seo_get_confirmed_active_metadata_updates( $updates, $after );

	$details['confirmed_fields']        = $confirmed;
	$details['storage_view']            = $after;
	$details['active_metadata_changed'] = $attempted;
	$details['commit_state']            = $confirmed ? 'committed' : ( $attempted ? 'possibly_committed' : 'not_committed' );
	$details['failure_reason']          = count( $confirmed ) === count( $updates ) ? '' : 'partial_write';
	ai4seo_debug_metadata_recovery( $post_id, '' !== $details['failure_reason'] ? $details['failure_reason'] : 'fields_saved', $after, array_keys( $confirmed ) );
	return count( $confirmed ) === count( $updates );
}

/**
 * Confirm submitted values against every physical row in a resolved storage view.
 *
 * A merged view can contain a field that is missing from another row. Writers,
 * provider synchronization and editor responses must all verify the physical rows.
 *
 * @param array $updates Submitted canonical field values, in response order.
 * @param array $view Fresh resolved storage view.
 * @return array Submitted fields present with the exact value in every surviving row.
 */
function ai4seo_get_confirmed_active_metadata_updates( array $updates, array $view ): array {
	if ( 'unreadable' === $view['classification'] || ! $view['rows'] ) {
		return array();
	}

	$confirmed = array();
	foreach ( $updates as $field => $value ) {
		foreach ( $view['rows'] as $row ) {
			if ( ! array_key_exists( $field, $row['active_metadata'] ) || $value !== $row['active_metadata'][ $field ] ) {
				continue 2;
			}
		}
		$confirmed[ $field ] = $value;
	}
	return $confirmed;
}

/**
 * Persist recovery edits first and synchronize only fields confirmed in fresh storage.
 *
 * @param int   $post_id Post ID.
 * @param array $updates Proposed values, including historical API aliases.
 * @param bool  $overwrite Whether to overwrite populated fields.
 * @param array $details Existing update result contract.
 * @param array $context Explicit resolutions and fingerprint.
 * @return bool Whether all submitted primary and provider writes succeeded.
 */
function ai4seo_update_active_metadata_with_recovery( int $post_id, array $updates, bool $overwrite, array &$details, array $context ): bool {
	$canonical = array();
	foreach ( AI4SEO_METADATA_DETAILS as $field => $definition ) {
		if ( isset( $updates[ $field ] ) || isset( $updates[ $definition['api-identifier'] ] ) ) {
			$canonical[ $field ] = $updates[ $field ] ?? $updates[ $definition['api-identifier'] ];
		}
	}
	$canonical        = ai4seo_prepare_active_metadata_values( $canonical );
	$overwrite_fields = $overwrite ? array_keys( $canonical ) : (array) ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA );
	$storage_details  = array();
	$saved            = ai4seo_save_active_metadata_to_postmeta(
		$post_id,
		$canonical,
		false,
		$storage_details,
		array_diff( array_keys( $canonical ), $overwrite_fields ),
		array(),
		$context
	);
	$view             = ai4seo_read_active_metadata_resolved_view( $post_id );
	$confirmed        = ai4seo_get_confirmed_active_metadata_updates( $canonical, $view );
	foreach ( $confirmed as $field => $value ) {
		if ( in_array( $field, $storage_details['skipped_fields'] ?? array(), true ) ) {
			unset( $confirmed[ $field ] );
		}
	}
	$details['confirmed_fields']          = $confirmed;
	$details['storage_view']              = $view;
	$details['commit_state']              = $confirmed ? 'committed' : $storage_details['commit_state'];
	$details['active_metadata_succeeded'] = $saved;
	$details['active_metadata_changed']   = $storage_details['active_metadata_changed'];
	$details['recovery_save']             = true;
	foreach ( $confirmed as $field => $value ) {
		try {
			$sync = ai4seo_update_third_party_seo_plugins_metadata( $post_id, $field, $value, in_array( $field, $overwrite_fields, true ) );
			if ( ! $sync['sync_succeeded'] ) {
				$details['third_party_sync_succeeded'] = false;
				foreach ( $sync['failed_plugin_identifiers'] as $provider ) {
					$details['failed_third_party_syncs'][ $provider ][] = $field;
				}
			}
		} catch ( Throwable $throwable ) {
			$details['third_party_sync_succeeded'] = false;
		}
	}
	$details['overall_succeeded'] = $saved && $details['third_party_sync_succeeded'];
	try {
		wp_cache_delete( $post_id, 'post_meta' );
		ai4seo_purge_frontend_cache_for_post( $post_id );
	} catch ( Throwable $throwable ) {
		ai4seo_debug_metadata_recovery( $post_id, 'cache_refresh_failed', $view );
	}
	return $details['overall_succeeded'];
}

/**
 * Client-safe storage state. Physical rows and raw bytes stay on the server.
 *
 * @param array $view Resolved storage.
 * @return array State used by editor initialization and partial responses.
 */
function ai4seo_metadata_recovery_editor_state( array $view ): array {
	return array(
		'classification'    => $view['classification'],
		'fingerprint'       => $view['fingerprint'],
		'conflicts'         => $view['conflicts'],
		'repair_incomplete' => $view['repair_incomplete'],
		'active_fields'     => ai4seo_get_active_meta_tags(),
	);
}
