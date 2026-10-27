<?php
/**
 * Resumable import, verification and explicit export of post status storage.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixed import sources; existing release migrations prepare these before import.
 *
 * @return array
 */
function ai4seo_post_state_migration_sources(): array {
	return array_merge(
		AI4SEO_ALL_POST_ID_OPTIONS,
		array(
			AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
			AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME,
			ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ),
			ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
			AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME,
		)
	);
}

/**
 * Fingerprint bytes without allocating another copy of a large serialized value.
 *
 * @param array $snapshot Authoritative legacy source.
 * @return string
 */
function ai4seo_post_state_source_digest( array $snapshot ): string {
	$hash = hash_init( 'sha256' );
	hash_update( $hash, $snapshot['exists'] ? '1' : '0' );
	hash_update( $hash, $snapshot['raw_value'] );
	return hash_final( $hash );
}

/**
 * Inspect source identity/size without transferring or decoding its value.
 *
 * @param string $name Frozen option.
 * @return array|null Null is a transport error, not an absent source.
 */
function ai4seo_read_post_state_source_header( string $name ): ?array {
	$rows = ai4seo_post_state_query(
		"SELECT option_id,option_name,autoload,OCTET_LENGTH(option_value) AS bytes,SHA2(CONCAT('1',option_value),256) AS digest FROM {{options}} WHERE option_name={{name}}",
		array( 'name' => $name ),
		true
	);
	if ( null === $rows ) {
		return null;
	}
	if ( $rows && ( ! isset( $rows[0]['digest'], $rows[0]['bytes'] ) || ! is_string( $rows[0]['digest'] ) || 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $rows[0]['digest'] ) || false === ai4seo_normalize_option_post_id( $rows[0]['bytes'], true ) ) ) {
		return null;
	}
	return $rows ? array( 'exists' => true ) + $rows[0] : array(
		'exists' => false,
		'bytes'  => 0,
		'digest' => hash( 'sha256', '0' ),
	);
}

/**
 * Preserve legacy decoders for small sources with a hard transport bound.
 *
 * @param string $name Source name.
 * @param array  $header Source identity.
 * @return array|null
 */
function ai4seo_read_small_post_state_source( string $name, array $header ): ?array {
	$snapshot = array(
		'exists'      => $header['exists'],
		'option_id'   => (int) ( $header['option_id'] ?? 0 ),
		'option_name' => $name,
		'raw_value'   => '',
		'value'       => null,
		'autoload'    => $header['autoload'] ?? '',
	);
	if ( ! $header['exists'] ) {
		return $snapshot;
	}
	$rows = ai4seo_post_state_query( 'SELECT SUBSTRING(BINARY option_value,1,262145) AS chunk FROM {{options}} WHERE option_name={{name}}', array( 'name' => $name ), true );
	if ( null === $rows || ! isset( $rows[0]['chunk'] ) || strlen( $rows[0]['chunk'] ) > 262144 ) {
		return null;
	}
	$snapshot['raw_value'] = $rows[0]['chunk'];
	if ( ai4seo_post_state_source_digest( $snapshot ) !== $header['digest'] ) {
		return null;
	}
	$snapshot['value'] = ai4seo_safe_maybe_unserialize( $snapshot['raw_value'] );
	return $snapshot;
}

/**
 * Fingerprint of the scan checkpoint protected by the analysis lock.
 *
 * @return string|null Fingerprint of the scan checkpoint protected by the analysis lock.
 */
function ai4seo_post_state_analysis_digest(): ?string {
	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot();
	if ( empty( $snapshot['success'] ) ) {
		return null;
	}
	$values = array();
	foreach ( array( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ) as $name ) {
		$values[ $name ] = $snapshot['values'][ $name ];
	}
	return hash( 'sha256', maybe_serialize( $values ) );
}

/**
 * Prove the old pause bug without accepting changed progress as a new baseline.
 *
 * The old worker changed an idle/processing scan to completed and requested a rebuild.
 * Keep that possibly genuine request, but finish the proven scan before it runs.
 *
 * @param array $state Frozen migration checkpoint.
 * @return array|null Empty for an exact match, bounded repairs for the proven bug, null otherwise.
 */
function ai4seo_post_state_analysis_recovery( array $state ): ?array {
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	if ( null === $snapshot || ( $snapshot['exists'] && ! is_array( $snapshot['value'] ) ) ) {
		return null;
	}
	$values = array();
	foreach ( array( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ) as $name ) {
		$value = array_key_exists( $name, $snapshot['value'] ?? array() ) ? $snapshot['value'][ $name ] : AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $name ];
		if ( ! ai4seo_validate_environmental_variable_value( $name, $value ) ) {
			return null;
		}
		$values[ $name ] = $value;
	}
	$rebuild = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ];
	unset( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE ] );
	if ( hash( 'sha256', maybe_serialize( $values ) ) === $state['analysis'] ) {
		return array();
	}
	if ( 'completed' !== $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] || 'required' !== $rebuild
		|| 2 !== (int) ( $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ]['version'] ?? 0 ) ) {
		return null;
	}
	foreach ( array( 'processing', 'idle' ) as $previous_state ) {
		$values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ] = $previous_state;
		if ( hash( 'sha256', maybe_serialize( $values ) ) === $state['analysis'] ) {
			return array(
				AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE => $previous_state,
				AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE => 'resume_then_rebuild',
			);
		}
	}
	return null;
}

/**
 * Publish a compact migration checkpoint without overwriting another phase.
 *
 * @param array $previous Expected state.
 * @param array $next Replacement.
 * @return bool
 */
function ai4seo_save_post_state_storage_status( array $previous, array $next ): bool {
	if ( 'legacy' === ( $next['phase'] ?? '' ) && 'legacy' !== ( $previous['phase'] ?? '' ) ) {
		$title = ai4seo_read_title_order_state();
		if ( null === $title ) {
			return false;
		}
		if ( $title ) {
			$invalidated              = $title;
			$invalidated['signature'] = str_repeat( '0', 64 );
			if ( ! ai4seo_save_title_order_state( $title, $invalidated ) ) {
				return false;
			}
		}
	}
	$replaced = false;
	return ai4seo_compare_and_swap_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE, $previous, $next, $replaced ) && $replaced;
}

/**
 * Validate one legacy source once per worker, retaining its canonical source order.
 *
 * @param string $name Source.
 * @param array  $snapshot Exact read.
 * @param bool   $repair Whether derived data needs a controlled rebuild.
 * @return array|null Canonical collection; null blocks release.
 */
function ai4seo_decode_post_state_migration_source( string $name, array $snapshot, bool &$repair ): ?array {
	if ( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME === $name ) {
		$value = $snapshot['exists'] ? $snapshot['value'] : array();
		if ( is_string( $value ) ) {
			$value = '' === $value ? array() : json_decode( $value, true );
		}
		if ( ! is_array( $value ) ) {
			return null;
		}
		foreach ( $value as $id ) {
			if ( false === ai4seo_normalize_option_post_id( $id ) ) {
				return null;
			}
		}
		return ai4seo_normalize_option_post_id_collection( $value );
	}
	if ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) ) {
		$inspection = ai4seo_inspect_analysis_post_id_option( $name, $snapshot );
		if ( ! $inspection['read_succeeded'] || ! $inspection['complete'] ) {
			if ( in_array( $name, AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, true ) ) {
				$repair = true;
				return array();
			}
			return null;
		}
		return $inspection['ids'];
	}
	if ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
		$value = $snapshot['exists'] ? ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $snapshot['value'] ) : array();
		if ( null === $value || array_diff( array_keys( $value ), ai4seo_get_generation_status_summary_source_option_names() ) ) {
			$repair = true;
			return array();
		}
		return $value;
	}
	if ( AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME === $name ) {
		$value = $snapshot['exists'] ? $snapshot['value'] : array();
		if ( ! is_array( $value ) ) {
			return null;
		}
		foreach ( $value as $post_id => $entry ) {
			if ( false === ai4seo_normalize_option_post_id( $post_id ) || ! is_array( $entry ) || ! is_string( $entry['token'] ?? null )
				|| '' === $entry['token'] || strlen( $entry['token'] ) > 128 || ! is_bool( $entry['coverage'] ?? null )
				|| ( isset( $entry['options'] ) && ( ! is_array( $entry['options'] ) || array_diff( $entry['options'], ai4seo_get_generation_status_summary_source_option_names() ) ) ) ) {
				return null;
			}
		}
		return $value;
	}
	foreach ( array( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ) as $processing ) {
		if ( ai4seo_get_processing_claim_lease_option_name( $processing ) === $name ) {
			$leases = array();
			if ( ! $snapshot['exists'] ) {
				return $leases;
			}
			return ai4seo_validate_processing_claim_leases( $snapshot['value'], $leases )
				&& ai4seo_validate_processing_claim_lease_context( $processing, $leases )
				&& ai4seo_are_processing_claim_deadlines_plausible( $leases ) ? $leases : null;
		}
	}
	return null;
}

/**
 * Iterate the loaded source without making a second flattened ID collection.
 *
 * @param string $name Source name.
 * @param array  $source Decoded source.
 * @return Generator Canonical logical records.
 */
function ai4seo_post_state_source_records( string $name, array $source ): Generator {
	if ( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME === $name ) {
		foreach ( $source as $id ) {
			yield array(
				'kind'    => 1,
				'post_id' => $id,
				'entry'   => array(
					'token'    => 'import-post-' . $id,
					'coverage' => true,
					'options'  => ai4seo_get_generation_status_summary_source_option_names(),
				),
			);
		}
	} elseif ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) ) {
		foreach ( $source as $id ) {
			yield array(
				'option'    => $name,
				'post_id'   => $id,
				'post_type' => null,
				'area'      => 1,
			);
		}
	} elseif ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
		foreach ( $source as $option => $types ) {
			foreach ( $types as $type => $entry ) {
				foreach ( $entry['post_ids'] as $id ) {
					yield array(
						'option'    => $option,
						'post_id'   => $id,
						'post_type' => $type,
						'area'      => 2,
					);
				}
			}
		}
	} else {
		$kind = AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME === $name ? 1 : ( ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) === $name ? 5 : 14 );
		foreach ( $source as $id => $entry ) {
			yield array(
				'kind'    => $kind,
				'post_id' => (int) $id,
				'entry'   => $entry,
			);
		}
	}
}

/**
 * Import a bounded logical packet. Replaying after a lost checkpoint is harmless.
 *
 * @param array $records Logical records.
 * @param array $expected Expected routing phase.
 * @return bool
 */
function ai4seo_import_post_state_packet( array $records, array $expected ): bool {
	return ai4seo_post_state_transaction(
		static function ( array $state ) use ( $records, $expected ): bool {
			if ( $state !== $expected || 'import' !== $state['phase'] ) {
				return false;
			}
			$types = array();
			if ( $records && isset( $records[0]['area'] ) && 1 === $records[0]['area'] ) {
				$read  = false;
				$types = ai4seo_read_post_types_by_post_ids( array_column( $records, 'post_id' ), $read );
				if ( ! $read ) {
					return false;
				}
			}
			$adds = array();
			$work = array();
			foreach ( $records as $record ) {
				if ( isset( $record['kind'] ) ) {
					$entry  = $record['entry'];
					$work[] = array( $record['kind'], $record['post_id'], $entry['token'], (int) ( $entry['expires_at'] ?? 0 ), wp_json_encode( $entry ) );
				} else {
					$adds[ $record['option'] ][ $record['post_id'] ] = $record['post_type'] ?? ( $types[ $record['post_id'] ] ?? '' );
				}
			}
			$changed = false;
			return ( ! $adds || ai4seo_apply_post_state_delta_under_lock( $adds, array(), $records[0]['area'], $state, $changed ) )
				&& ai4seo_post_state_insert_rows( 'work', $work, 'import_work' );
		}
	);
}

/**
 * Verify membership identity and queue order against one source packet.
 *
 * @param string $name Source.
 * @param array  $records Expected logical records.
 * @param array  $state Current checkpoint.
 * @param int    $after Receives the last verified table row.
 * @return bool
 */
function ai4seo_verify_post_state_packet( string $name, array $records, array $state, int &$after ): bool {
	if ( ! $records ) {
		return true;
	}
	if ( isset( $records[0]['kind'] ) ) {
		$kind   = $records[0]['kind'];
		$actual = ai4seo_read_post_state_work( $kind, array_column( $records, 'post_id' ) );
		if ( null === $actual ) {
			return false;
		}
		foreach ( $records as $record ) {
			$entry = $actual[ $record['post_id'] ] ?? null;
			// A post edit admitted during the pause supersedes an imported repair token.
			if ( $entry !== $record['entry'] && ( 1 !== $kind || ! is_array( $entry ) || empty( $entry['coverage'] ) ) ) {
				return false;
			}
		}
		return true;
	}
	$values = array(
		'generation' => $state['generation'],
		'after'      => $after,
		'limit'      => count( $records ),
	);
	if ( 1 === $records[0]['area'] ) {
		$actual = ai4seo_read_post_state_page( $name, $after, count( $records ) );
	} else {
		$actual = ai4seo_post_state_query( 'SELECT membership_id,post_id,post_type,bucket FROM {{memberships}} WHERE area=2 AND generation={{generation}} AND membership_id>{{after}} ORDER BY membership_id LIMIT {{limit}}', $values, true );
	}
	if ( null === $actual || count( $actual ) !== count( $records ) ) {
		return false;
	}
	foreach ( $records as $index => $record ) {
		if ( (int) $actual[ $index ]['post_id'] !== $record['post_id']
			|| ( 2 === $record['area'] && ( $actual[ $index ]['post_type'] !== $record['post_type'] || AI4SEO_POST_STATE_BUCKETS[ $record['option'] ] !== (int) $actual[ $index ]['bucket'] ) ) ) {
			return false;
		}
		$after = (int) $actual[ $index ]['membership_id'];
	}
	return true;
}

/**
 * True means a protected generation is still finishing; null blocks migration.
 *
 * @return bool|null True means a protected generation is still finishing; null blocks migration.
 */
function ai4seo_post_state_has_active_legacy_claims(): ?bool {
	foreach ( array( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ) as $processing ) {
		$name   = ai4seo_get_processing_claim_lease_option_name( $processing );
		$header = ai4seo_read_post_state_source_header( $name );
		if ( null === $header ) {
			return null;
		}
		if ( (int) $header['bytes'] > 262144 ) {
			try {
				$reader = new AI4SEO_Post_State_Reader( $name, (int) $header['bytes'] );
				$record = $reader->next_record();
				while ( null !== $record ) {
					if ( $record['entry']['expires_at'] > time() ) {
						return true;
					}
					$record = $reader->next_record();
				}
			} catch ( RuntimeException $exception ) {
				return null;
			}
			continue;
		}
		$snapshot = ai4seo_read_small_post_state_source( $name, $header );
		$repair   = false;
		$leases   = null === $snapshot ? null : ai4seo_decode_post_state_migration_source( $name, $snapshot, $repair );
		if ( null === $leases ) {
			return null;
		}
		foreach ( $leases as $lease ) {
			if ( (int) $lease['expires_at'] > time() ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Advance one resumable migration worker, decoding each source only once.
 *
 * @param float $budget_seconds Worker budget; individual statements remain bounded.
 * @return array Existing updater result format.
 */
function ai4seo_migrate_post_state_storage_batch( float $budget_seconds = 2.0 ): array {
	$deadline = microtime( true ) + max( 0.01, min( 10.0, $budget_seconds ) );
	$state    = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'storage_checkpoint_unreadable' );
	}
	if ( in_array( $state['phase'] ?? '', array( 'active', 'legacy' ), true ) ) {
		if ( 'active' === $state['phase'] ) {
			// Recover cache invalidation if a process stopped just after the cutover commit.
			foreach ( ai4seo_post_state_migration_sources() as $name ) {
				ai4seo_invalidate_option_cache( $name );
			}
		}
		return ai4seo_plugin_update_step_result( 'completed' );
	}
	$analysis_lock = ai4seo_get_posts_table_analysis_database_lock_name();
	if ( ! ai4seo_acquire_database_advisory_lock( $analysis_lock ) ) {
		return ai4seo_plugin_update_step_result( 'deferred', 'analysis_busy' );
	}
	try {
		if ( ! $state ) {
			$schema = ai4seo_ensure_post_state_schema();
			if ( ! $schema['success'] ) {
				return ai4seo_plugin_update_step_result( 'blocked', $schema['reason'] );
			}
			foreach ( array( 'memberships', 'totals', 'work' ) as $table ) {
				$existing = ai4seo_post_state_query( 'SELECT 1 FROM {{' . $table . '}} LIMIT 1', array(), true );
				if ( null === $existing || $existing ) {
					return ai4seo_plugin_update_step_result( 'blocked', 'unowned_target_rows' );
				}
			}
			$next = array(
				'version'    => AI4SEO_POST_STATE_STORAGE_VERSION,
				'phase'      => 'draining',
				'generation' => 1,
				'source'     => 0,
				'offset'     => 0,
				'row'        => 0,
				'digest'     => '',
				'manifest'   => array(),
				'analysis'   => '',
				'repair'     => false,
				'error'      => '',
			);
			if ( ! ai4seo_post_state_transaction(
				static function ( array $current ) use ( $next ): bool {
					return ! $current && ai4seo_save_post_state_storage_status( $current, $next );
				}
			) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'pause_checkpoint_failed' );
			}
			$state = $next;
		}
		if ( 'draining' === $state['phase'] ) {
			$active = ai4seo_post_state_has_active_legacy_claims();
			if ( false !== $active ) {
				return ai4seo_plugin_update_step_result( null === $active ? 'blocked' : 'deferred', null === $active ? 'leases_unreadable' : 'active_generations' );
			}
			$next             = $state;
			$next['analysis'] = ai4seo_post_state_analysis_digest();
			$next['phase']    = 'import';
			if ( null === $next['analysis'] || ! ai4seo_post_state_transaction(
				static function ( array $current ) use ( $state, $next ): bool {
					return $current === $state && false === ai4seo_post_state_has_active_legacy_claims() && ai4seo_save_post_state_storage_status( $current, $next );
				}
			) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'freeze_checkpoint_failed' );
			}
			$state = $next;
		}
		$sources      = ai4seo_post_state_migration_sources();
		$source_count = count( $sources );
		while ( $state['source'] < $source_count && microtime( true ) < $deadline ) {
			$name   = $sources[ $state['source'] ];
			$header = ai4seo_read_post_state_source_header( $name );
			if ( null === $header ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'source_read_failed', $state['source'] );
			}
			$digest = $header['digest'];
			if ( '' !== $state['digest'] && $digest !== $state['digest'] ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'source_changed', $state['source'] );
			}
			if ( (int) $header['bytes'] > 262144 || isset( $state['reader'] ) ) {
				$result = ai4seo_migrate_post_state_stream( $name, $header, $state, $deadline );
				if ( null !== $result ) {
					return $result;
				}
				continue;
			}
			$snapshot = ai4seo_read_small_post_state_source( $name, $header );
			if ( null === $snapshot ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'source_read_failed', $state['source'] );
			}
			$repair = $state['repair'];
			$source = ai4seo_decode_post_state_migration_source( $name, $snapshot, $repair );
			unset( $snapshot );
			if ( null === $source ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'source_invalid', $state['source'] );
			}
			if ( '' === $state['digest'] ) {
				$next           = $state;
				$next['digest'] = $digest;
				$next['repair'] = $repair;
				if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
					return ai4seo_plugin_update_step_result( 'deferred', 'source_checkpoint_failed' );
				}
				$state = $next;
			}
			$index  = 0;
			$packet = array();
			foreach ( ai4seo_post_state_source_records( $name, $source ) as $record ) {
				if ( $index++ < $state['offset'] ) {
					continue;
				}
				$packet[] = $record;
				if ( count( $packet ) < ai4seo_post_state_batch_size() ) {
					continue;
				}
				if ( ! ai4seo_post_state_migration_packet_checkpoint( $name, $packet, $state ) ) {
					return ai4seo_plugin_update_step_result( 'deferred', 'packet_failed', $state['offset'] );
				}
				$packet = array();
				if ( microtime( true ) >= $deadline ) {
					return ai4seo_plugin_update_step_result( 'deferred', 'migration_in_progress', $state['offset'] );
				}
			}
			if ( $packet && ! ai4seo_post_state_migration_packet_checkpoint( $name, $packet, $state ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'packet_failed', $state['offset'] );
			}
			unset( $source );
			if ( 'verify' === $state['phase'] && ! ai4seo_verify_post_state_source_count( $name, $state ) ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'source_count_mismatch', $state['source'] );
			}
			if ( ! ai4seo_finish_post_state_migration_source( $state, $digest ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'source_completion_failed' );
			}
		}
		if ( $state['source'] < count( $sources ) ) {
			return ai4seo_plugin_update_step_result( 'deferred', 'migration_in_progress', $state['offset'] );
		}
		return ai4seo_publish_post_state_storage( $state );
	} finally {
		ai4seo_release_database_advisory_lock( $analysis_lock );
	}
}

/**
 * Publish only a completed import/verification packet checkpoint.
 *
 * @param string     $name Source.
 * @param array      $packet Bounded records.
 * @param array      $state Updated checkpoint.
 * @param array|null $reader Bounded parser position after this packet.
 * @return bool
 */
function ai4seo_post_state_migration_packet_checkpoint( string $name, array $packet, array &$state, ?array $reader = null ): bool {
	$next   = $state;
	$stored = 'import' === $state['phase'] ? ai4seo_import_post_state_packet( $packet, $state ) : ai4seo_verify_post_state_packet( $name, $packet, $state, $next['row'] );
	if ( ! $stored ) {
		return false;
	}
	do_action( 'ai4seo_post_state_checkpoint', 'before_import_checkpoint' );
	$next['offset'] += count( $packet );
	if ( null !== $reader ) {
		$next['reader'] = $reader;
	}
	if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
		return false;
	}
	$state = $next;
	do_action( 'ai4seo_post_state_checkpoint', 'after_import_checkpoint' );
	return true;
}

/**
 * Complete one source pass and discard its byte cursor.
 *
 * @param array  $state Current checkpoint, replaced only after persistence.
 * @param string $digest Verified source hash.
 * @return bool
 */
function ai4seo_finish_post_state_migration_source( array &$state, string $digest ): bool {
	$next           = $state;
	$next['offset'] = 0;
	$next['row']    = 0;
	unset( $next['reader'] );
	if ( 'import' === $state['phase'] ) {
		$next['phase'] = 'verify';
	} else {
		$next['manifest'][ $state['source'] ] = $digest;
		++$next['source'];
		$next['phase']  = 'import';
		$next['digest'] = '';
	}
	if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
		return false;
	}
	$state = $next;
	return true;
}

/**
 * Retain first-occurrence queue semantics when a legacy list repeats IDs.
 *
 * @param string $name Operative source.
 * @param array  $packet Bounded parsed records.
 * @param array  $state Verification position.
 * @return array|null
 */
function ai4seo_normalize_post_state_verification_packet( string $name, array $packet, array $state ): ?array {
	if ( 'verify' !== $state['phase'] || ! isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) || ! $packet ) {
		return $packet;
	}
	$key  = ai4seo_post_state_key( $name, 1, $state );
	$rows = ai4seo_post_state_query(
		'SELECT post_id,membership_id FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_id IN ({{ids}})',
		$key + array( 'ids' => array_column( $packet, 'post_id' ) ),
		true
	);
	if ( null === $rows ) {
		return null;
	}
	$positions = array_column( $rows, 'membership_id', 'post_id' );
	$unique    = array();
	foreach ( $packet as $record ) {
		if ( isset( $positions[ $record['post_id'] ] ) && (int) $positions[ $record['post_id'] ] <= $state['row'] ) {
			continue;
		}
		$unique[ $record['post_id'] ] = $record;
	}
	return array_values( $unique );
}

/**
 * Import/verify bounded records and resume directly at a persisted byte cursor.
 *
 * @param string $name Frozen source.
 * @param array  $header Authoritative source identity.
 * @param array  $state Current checkpoint.
 * @param float  $deadline Worker deadline.
 * @return array|null Updater result, or null after completing this source pass.
 */
function ai4seo_migrate_post_state_stream( string $name, array $header, array &$state, float $deadline ): ?array {
	try {
		$reader = new AI4SEO_Post_State_Reader( $name, (int) $header['bytes'], $state['reader'] ?? array() );
		if ( ! isset( $state['reader'] ) ) {
			$next           = $state;
			$next['digest'] = $header['digest'];
			$next['offset'] = 0;
			$next['row']    = 0;
			$next['reader'] = $reader->checkpoint();
			// Old in-progress imports can replay this source safely; they need no
			// conversion of an unbounded row offset into a guessed byte position.
			if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'source_checkpoint_failed' );
			}
			$state = $next;
		}
		do {
			$packet = array();
			$limit  = ai4seo_post_state_batch_size();
			for ( $index = 0; $index < $limit; ++$index ) {
				$record = $reader->next_record();
				if ( null === $record ) {
					break;
				}
				$packet[] = $record;
			}
			$packet = ai4seo_normalize_post_state_verification_packet( $name, $packet, $state );
			if ( null === $packet || ! ai4seo_post_state_migration_packet_checkpoint( $name, $packet, $state, $reader->checkpoint() ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'packet_failed', $state['offset'] );
			}
			if ( $reader->checkpoint()['complete'] ) {
				if ( 'verify' === $state['phase'] && ! ai4seo_verify_post_state_source_count( $name, $state ) ) {
					return ai4seo_plugin_update_step_result( 'blocked', 'source_count_mismatch', $state['source'] );
				}
				return ai4seo_finish_post_state_migration_source( $state, $header['digest'] ) ? null : ai4seo_plugin_update_step_result( 'deferred', 'source_completion_failed' );
			}
		} while ( microtime( true ) < $deadline );
		return ai4seo_plugin_update_step_result( 'deferred', 'migration_in_progress', $state['offset'] );
	} catch ( UnexpectedValueException $exception ) {
		if ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name || in_array( $name, AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, true ) ) {
			// Any partial derived rows belong to the retired generation. Publish
			// only with the existing controlled analysis rebuild, never as empty.
			$next                                 = $state;
			$next['repair']                       = true;
			$next['phase']                        = 'import';
			$next['offset']                       = 0;
			$next['row']                          = 0;
			$next['digest']                       = '';
			$next['manifest'][ $state['source'] ] = $header['digest'];
			++$next['source'];
			unset( $next['reader'] );
			if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'repair_checkpoint_failed' );
			}
			$state = $next;
			return null;
		}
		return ai4seo_plugin_update_step_result( 'blocked', 'source_invalid', $state['source'] );
	} catch ( RuntimeException $exception ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'source_read_failed', $state['source'] );
	}
}

/**
 * Verify immutable sources and counters before publishing the storage version.
 *
 * @param array $state Completed import checkpoint.
 * @return array Updater result.
 */
function ai4seo_publish_post_state_storage( array $state ): array {
	$analysis_lock = ai4seo_get_posts_table_analysis_database_lock_name();
	if ( '' === $analysis_lock || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $analysis_lock ) ) {
		return ai4seo_plugin_update_step_result( 'deferred', 'analysis_busy' );
	}
	if ( ! ai4seo_verify_post_state_source_manifest( $state ) ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'source_changed_before_publish' );
	}
	$actual = ai4seo_post_state_query( 'SELECT area,generation,bucket,post_type,COUNT(*) AS total FROM {{memberships}} GROUP BY area,generation,bucket,post_type ORDER BY area,generation,bucket,post_type', array(), true );
	$totals = ai4seo_post_state_query( 'SELECT area,generation,bucket,post_type,total FROM {{totals}} WHERE total<>0 ORDER BY area,generation,bucket,post_type', array(), true );
	if ( null === $actual || $actual !== $totals || null === ai4seo_post_state_analysis_recovery( $state ) ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'counter_or_analysis_verification_failed' );
	}
	do_action( 'ai4seo_post_state_checkpoint', 'before_cutover' );
	$published = ai4seo_post_state_transaction(
		static function ( array $current ) use ( $state ): bool {
			$recovery = ai4seo_post_state_analysis_recovery( $state );
			if ( $state !== $current || null === $recovery || ! ai4seo_verify_post_state_source_manifest( $state ) ) {
				return false;
			}
			// Freeze source bytes while preventing old autoload flags from pulling whole arrays into every request.
			if ( null === ai4seo_post_state_query( "UPDATE {{options}} SET autoload='no' WHERE option_name IN ({{names}})", array( 'names' => ai4seo_post_state_migration_sources() ) ) ) {
				return false;
			}
			$next           = $state;
			$next['phase']  = 'active';
			$next['digest'] = '';
			if ( $state['repair'] ) {
				++$next['generation'];
				$result = ai4seo_bulk_update_environmental_variables(
					array(
						AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE => $next,
						AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
						AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS => array(),
						AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE => 'idle',
					),
					false
				);
				return ! empty( $result['success'] );
			}
			if ( $recovery ) {
				// The locked environmental row publishes routing and recovery in the same commit.
				$result = ai4seo_bulk_update_environmental_variables(
					$recovery + array( AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE => $next ),
					false
				);
				return ! empty( $result['success'] ) && empty( $result['invalid_names'] ) && empty( $result['invalid_values'] )
					&& ai4seo_read_post_state_storage_status() === $next && array() === ai4seo_post_state_analysis_recovery( $next );
			}
			return ai4seo_save_post_state_storage_status( $state, $next );
		},
		$analysis_lock
	);
	if ( ! $published ) {
		return ai4seo_plugin_update_step_result( 'deferred', 'cutover_checkpoint_failed' );
	}
	foreach ( ai4seo_post_state_migration_sources() as $name ) {
		ai4seo_invalidate_option_cache( $name );
	}
	do_action( 'ai4seo_post_state_checkpoint', 'after_cutover' );
	ai4seo_schedule_generation_status_summary_rebuild( false );
	return ai4seo_plugin_update_step_result( 'completed' );
}

/**
 * Verify frozen source bytes without transferring or decoding the collections again.
 *
 * @param array $state Verified migration checkpoint.
 * @return bool
 */
function ai4seo_verify_post_state_source_manifest( array $state ): bool {
	$sources = ai4seo_post_state_migration_sources();
	$rows    = ai4seo_post_state_query( "SELECT option_name,SHA2(CONCAT('1',option_value),256) AS digest FROM {{options}} WHERE option_name IN ({{names}})", array( 'names' => $sources ), true );
	if ( null === $rows ) {
		return false;
	}
	$actual = array_column( $rows, 'digest', 'option_name' );
	foreach ( $sources as $index => $name ) {
		if ( ( $actual[ $name ] ?? hash( 'sha256', '0' ) ) !== ( $state['manifest'][ $index ] ?? '' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Detect extra memberships as well as missing or reordered imported records.
 *
 * @param string $name Source name.
 * @param array  $state Completed source checkpoint.
 * @return bool
 */
function ai4seo_verify_post_state_source_count( string $name, array $state ): bool {
	if ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) ) {
		$rows = ai4seo_post_state_query( 'SELECT COUNT(*) AS n FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}}', ai4seo_post_state_key( $name, 1, $state ), true );
	} elseif ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
		$rows = ai4seo_post_state_query( 'SELECT COUNT(*) AS n FROM {{memberships}} WHERE area=2 AND generation={{generation}}', array( 'generation' => $state['generation'] ), true );
	} elseif ( in_array( $name, array( AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME, AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME ), true ) ) {
		return true; // Concurrent post edits may legitimately add or replace repair work.
	} else {
		$kind = ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) === $name ? 5 : 14;
		$rows = ai4seo_post_state_query( 'SELECT COUNT(*) AS n FROM {{work}} WHERE kind={{kind}}', array( 'kind' => $kind ), true );
	}
	return null !== $rows && (int) $rows[0]['n'] === $state['offset'];
}


/**
 * Materialize one explicit export value using bounded table reads.
 *
 * @param string $name Legacy destination.
 * @return array|null
 */
function ai4seo_read_post_state_export_value( string $name ): ?array {
	if ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) ) {
		return ai4seo_export_post_state_ids( $name );
	}
	if ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
		return ai4seo_export_post_state_summary();
	}
	if ( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME === $name ) {
		return array(); // Its current work is exported through the token-owned repair queue.
	}
	$kind   = AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME === $name ? 1 : ( ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) === $name ? 5 : 14 );
	$result = array();
	$after  = 0;
	do {
		$rows = ai4seo_post_state_query(
			'SELECT work_id,post_id,payload FROM {{work}} WHERE kind={{kind}} AND work_id>{{after}} ORDER BY work_id LIMIT {{limit}}',
			array(
				'kind'  => $kind,
				'after' => $after,
				'limit' => ai4seo_post_state_batch_size(),
			),
			true
		);
		if ( null === $rows ) {
			return null;
		}
		foreach ( $rows as $row ) {
			$entry = json_decode( $row['payload'], true );
			if ( ! is_array( $entry ) ) {
				return null;
			}
			$result[ (int) $row['post_id'] ] = $entry;
			$after                           = (int) $row['work_id'];
		}
	} while ( $rows );
	return $result;
}

/**
 * Write and verify an explicitly requested compatibility snapshot.
 *
 * @param string $name Destination.
 * @param array  $value Current table value.
 * @return bool
 */
function ai4seo_write_post_state_export_value( string $name, array $value ): bool {
	global $wpdb;
	$GLOBALS['ai4seo_post_state_export_scope'] = (string) $wpdb->options;
	try {
		if ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
			if ( ! ai4seo_persist_generation_status_summary( $value ) ) {
				return false;
			}
		} else {
			ai4seo_update_option( $name, $value );
		}
		$read = ai4seo_get_raw_option_snapshot( $name );
		return null !== $read && $read['exists'] && $read['value'] === $value;
	} finally {
		unset( $GLOBALS['ai4seo_post_state_export_scope'] );
	}
}
