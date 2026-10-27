<?php
/**
 * Bounded consumers of the indexed post status store.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Count taxonomy statuses in SQL; precedence matches the existing archive chart.
 *
 * @param string     $taxonomy Archive taxonomy.
 * @param bool       $active_only Whether only available proxies are counted.
 * @param array|null $option_inspections Shared legacy inspections for one render.
 * @return array|WP_Error Small counters; no identities are returned.
 */
function ai4seo_read_taxonomy_generation_counts( string $taxonomy, bool $active_only = false, ?array &$option_inspections = null ) {
	if ( ! ai4seo_uses_post_state_tables() ) {
		$summary = ai4seo_read_taxonomy_generation_status_summary( $taxonomy, $active_only, $option_inspections );
		if ( is_wp_error( $summary ) ) {
			return $summary;
		}
		if ( $active_only ) {
			$work = ai4seo_read_analysis_reconciliation_snapshot();
			if ( null === $work ) {
				return new WP_Error( 'taxonomy_status_unavailable' );
			}
			foreach ( $summary as &$ids ) {
				$ids = array_diff( $ids, array_keys( $work['value'] ) );
			}
			unset( $ids );
		}
		$complete = $summary['complete'] ?? array();
		$pending  = array_unique( array_merge( $summary['pending'], $summary['processing'] ) );
		$queued   = array_diff( $pending, $complete );
		$failed   = array_diff( $summary['failed'], $complete, $queued );
		$missing  = array_diff( $summary['missing'], $complete, $queued, $failed );
		return array(
			'complete'    => count( $complete ),
			'queued'      => count( $queued ),
			'failed'      => count( $failed ),
			'missing'     => count( $missing ),
			'raw_missing' => count( $summary['missing'] ),
			'pending'     => count( $pending ) + count( $missing ),
		);
	}
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return new WP_Error( 'taxonomy_status_unavailable' );
	}
	$values = array(
		'area'          => $active_only ? 2 : 1,
		'generation'    => $state['generation'],
		'taxonomy'      => $taxonomy,
		'type'          => AI4SEO_TAXONOMY_PROXY_POST_TYPE,
		'name_key'      => AI4SEO_TAXONOMY_PROXY_NAME_META_KEY,
		'available_key' => AI4SEO_TAXONOMY_PROXY_AVAILABILITY_META_KEY,
	);
	$flags  = array();
	foreach ( array(
		'c' => 3,
		'q' => 4,
		'r' => 5,
		'f' => 6,
		'm' => 1,
	) as $alias => $bucket ) {
		$flags[] = 'EXISTS (SELECT 1 FROM {{memberships}} s WHERE s.area={{area}} AND s.generation=' . ( $active_only || in_array( $bucket, array( 1, 3 ), true ) ? '{{generation}}' : '0' ) . ' AND s.bucket=' . $bucket . ' AND s.post_id=p.ID) AS ' . $alias;
	}
	$rows = ai4seo_post_state_query(
		'SELECT COALESCE(SUM(c),0) AS complete,COALESCE(SUM((q OR r) AND NOT c),0) AS queued,
		COALESCE(SUM(f AND NOT c AND NOT(q OR r)),0) AS failed,
		COALESCE(SUM(m AND NOT(c OR q OR r OR f)),0) AS missing,COALESCE(SUM(m),0) AS raw_missing,
		COALESCE(SUM((q OR r) OR (m AND NOT(c OR q OR r OR f))),0) AS pending
		FROM (SELECT ' . implode( ',', $flags ) . ' FROM {{posts}} p WHERE p.post_type={{type}}
		AND EXISTS (SELECT 1 FROM {{meta}} t WHERE t.post_id=p.ID AND t.meta_key={{name_key}} AND t.meta_value={{taxonomy}})'
		. ( $active_only ? " AND EXISTS (SELECT 1 FROM {{meta}} a WHERE a.post_id=p.ID AND a.meta_key={{available_key}} AND a.meta_value='available') AND NOT EXISTS (SELECT 1 FROM {{work}} w WHERE w.kind=1 AND w.post_id=p.ID)" : '' ) . ') flags',
		$values,
		true
	);
	return null === $rows ? new WP_Error( 'taxonomy_status_unavailable' ) : array_map( 'intval', $rows[0] );
}

/**
 * Read the front of a queue without materializing other waiting jobs.
 *
 * @param string $option Pending source.
 * @return array|null FIFO identities, or an explicit read failure.
 */
function ai4seo_read_post_state_queue_ids( string $option ): ?array {
	if ( ! ai4seo_uses_post_state_tables() ) {
		$inspection = ai4seo_inspect_analysis_post_id_option( $option );
		return $inspection['read_succeeded'] && $inspection['complete'] ? $inspection['ids'] : null;
	}
	$state = ai4seo_read_post_state_storage_status();
	$key   = null === $state ? null : ai4seo_post_state_key( $option, 1, $state );
	if ( null === $key || ! in_array( $key['bucket'], array( 4, 13 ), true ) ) {
		return null;
	}
	$rows = ai4seo_post_state_query(
		'SELECT m.post_id FROM {{memberships}} m FORCE INDEX (queue_page) WHERE m.area={{area}} AND m.generation={{generation}} AND m.bucket={{bucket}}
		AND NOT EXISTS (SELECT 1 FROM {{work}} w WHERE w.kind={{kind}} AND w.post_id=m.post_id)
		ORDER BY m.membership_id LIMIT {{limit}}',
		$key + array(
			'kind'  => $key['bucket'] + 1,
			'limit' => ai4seo_post_state_batch_size(),
		),
		true
	);
	if ( array() === $rows ) {
		// Preserve owned Pending rows: an empty runnable page must not trigger queue cleanup.
		$rows = ai4seo_post_state_query( 'SELECT post_id FROM {{memberships}} FORCE INDEX (queue_page) WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} ORDER BY membership_id LIMIT 1', $key, true );
	}
	return null === $rows ? null : array_map( 'intval', array_column( $rows, 'post_id' ) );
}

/**
 * Select only expired, unfinished or orphaned claims for bounded recovery.
 *
 * @param string $processing Processing status.
 * @param int    $limit Maximum repair candidates.
 * @return array|null
 */
function ai4seo_read_post_state_recovery_ids( string $processing, int $limit ): ?array {
	$kind = AI4SEO_POST_STATE_BUCKETS[ $processing ] ?? 0;
	if ( ! in_array( $kind, array( 5, 14 ), true ) ) {
		return null;
	}
	$values  = array(
		'kind'     => $kind,
		'now'      => time(),
		'limit'    => min( $limit, ai4seo_post_state_batch_size() ),
		'rollback' => '%"rollback_requested":true%',
		'terminal' => '%"terminal_force_overwrite":%',
	);
	$leases  = ai4seo_post_state_query( 'SELECT w.post_id FROM {{work}} w WHERE w.kind={{kind}} AND (w.expires_at<={{now}} OR w.payload LIKE {{rollback}} OR w.payload LIKE {{terminal}} OR NOT EXISTS (SELECT 1 FROM {{memberships}} m WHERE m.area=1 AND m.generation=0 AND m.bucket={{kind}} AND m.post_id=w.post_id)) ORDER BY w.work_id LIMIT {{limit}}', $values, true );
	$missing = ai4seo_post_state_query(
		'SELECT m.post_id FROM {{memberships}} m FORCE INDEX (queue_page) WHERE m.area=1 AND m.generation=0 AND m.bucket={{kind}} AND NOT EXISTS (SELECT 1 FROM {{work}} w WHERE w.kind={{kind}} AND w.post_id=m.post_id) ORDER BY m.membership_id LIMIT {{limit}}',
		array(
			'kind'  => $kind,
			'limit' => $values['limit'],
		),
		true
	);
	return null === $leases || null === $missing ? null : array_slice( array_values( array_unique( array_map( 'intval', array_merge( array_column( $leases, 'post_id' ), array_column( $missing, 'post_id' ) ) ) ) ), 0, $values['limit'] );
}

/**
 * Inspect a bounded queue window under the same policy and mutation fence.
 *
 * @param array|null $enabled Verified enabled post types.
 * @param array|null $contexts Requested queue contexts.
 * @return bool
 */
function ai4seo_reconcile_indexed_disabled_queue( ?array &$enabled, ?array $contexts ): bool {
	$contexts = $contexts ?? ai4seo_get_bulk_generation_queue_contexts();
	$enabled  = array();
	if ( ! $contexts || array_diff( $contexts, ai4seo_get_bulk_generation_queue_contexts() ) ) {
		return false;
	}
	$more   = false;
	$result = ai4seo_post_state_transaction(
		static function ( array $state ) use ( &$enabled, $contexts, &$more ): bool {
			if ( 'active' !== ( $state['phase'] ?? '' ) ) {
				return false;
			}
			$read              = false;
			$settings_snapshot = null;
			$enabled           = ai4seo_read_persisted_enabled_bulk_generation_post_types( $read, $settings_snapshot );
			$settings          = $read ? ai4seo_decode_settings_snapshot( $settings_snapshot ) : null;
			$snapshot          = array();
			$cursor            = null;
			if ( ! is_array( $settings ) || ! ai4seo_read_disabled_queue_inspection_state_under_lock( $snapshot, $cursor ) ) {
				return false;
			}
			$fingerprint = hash( 'sha256', $settings_snapshot['raw_value'] . implode( ',', $contexts ) . ':indexed' );
			$after       = ( $cursor['settings_fingerprint'] ?? '' ) === $fingerprint ? $cursor['last_inspected_metadata_post_id'] : 0;
			if ( ( $cursor['settings_fingerprint'] ?? '' ) === $fingerprint && $cursor['complete'] ) {
				return true;
			}
			$buckets = array();
			foreach ( $contexts as $context ) {
				$options   = ai4seo_get_bulk_generation_queue_options_by_context( $context );
				$buckets[] = AI4SEO_POST_STATE_BUCKETS[ $options['pending'] ];
				$buckets[] = AI4SEO_POST_STATE_BUCKETS[ $options['force_overwrite'] ];
			}
			$limit = min( 50, ai4seo_post_state_batch_size() );
			$rows  = ai4seo_post_state_query(
				'SELECT membership_id,post_id,bucket FROM {{memberships}} WHERE area=1 AND generation=0 AND bucket IN ({{buckets}}) AND membership_id>{{after}} ORDER BY membership_id LIMIT {{limit}}',
				array(
					'buckets' => $buckets,
					'after'   => $after,
					'limit'   => $limit,
				),
				true
			);
			if ( null === $rows ) {
				return false;
			}
			$ids   = array_map( 'intval', array_column( $rows, 'post_id' ) );
			$types = ai4seo_read_post_types_by_post_ids( $ids, $read );
			if ( ! $read ) {
				return false;
			}
			$removes = array();
			foreach ( $rows as $row ) {
				$id       = (int) $row['post_id'];
				$context  = (int) $row['bucket'] < 10 ? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA : AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES;
				$options  = ai4seo_get_bulk_generation_queue_options_by_context( $context );
				$type     = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ? 'attachment' : ( $types[ $id ] ?? '' );
				$disabled = ! in_array( $type, $enabled, true );
				if ( AI4SEO_TAXONOMY_PROXY_POST_TYPE === $type ) {
					$archive = ai4seo_get_taxonomy_proxy_editor_context( $id );
					if ( is_wp_error( $archive ) ) {
						return false;
					}
					$disabled = ! $archive['available'] || ! in_array( $archive['source']['taxonomy'], ai4seo_get_enabled_bulk_generation_taxonomies( $settings ), true );
				}
				if ( $disabled ) {
					$processing = ai4seo_read_post_state_for_ids( array( $id ), array( $options['processing'] ) );
					$leases     = ai4seo_read_post_state_work( AI4SEO_POST_STATE_BUCKETS[ $options['processing'] ], array( $id ) );
					if ( null === $processing || null === $leases || $processing[ $options['processing'] ] || $leases ) {
						return false;
					}
					$removes[ $options['pending'] ][]         = $id;
					$removes[ $options['force_overwrite'] ][] = $id;
				}
				$after = (int) $row['membership_id'];
			}
			$changed = false;
			if ( $removes && ( ! ai4seo_apply_post_state_delta_under_lock( array(), $removes, 1, $state, $changed ) || ! ai4seo_post_state_enqueue_under_lock( array_values( array_unique( $ids ) ), true ) ) ) {
				return false;
			}
			$more = count( $rows ) === $limit;
			return ai4seo_replace_disabled_queue_inspection_state_under_lock(
				$snapshot,
				array(
					'version'                         => 1,
					'settings_fingerprint'            => $fingerprint,
					'metadata_pending_fingerprint'    => hash( 'sha256', 'indexed' ),
					'metadata_force_fingerprint'      => hash( 'sha256', 'indexed' ),
					'last_inspected_metadata_post_id' => $more ? $after : 0,
					'complete'                        => ! $more,
				)
			);
		}
	);
	if ( $more || ! $result ) {
		ai4seo_schedule_disabled_bulk_generation_queue_reconciliation_recovery();
	}
	return $result;
}

/**
 * Clear Pending and its paired Force markers with short atomic packets.
 *
 * @param array         $pairs Validated Pending => Force pairs.
 * @param array         $removed Optional explicit diagnostic identity export.
 * @param callable|null $guard Policy check repeated inside each transaction.
 * @param bool          $collect Whether the caller needs a full identity export.
 * @return bool
 */
function ai4seo_clear_indexed_pending_pairs( array $pairs, array &$removed, ?callable $guard, bool $collect = true ): bool {
	$removed = array();
	$through = 0;
	// Freeze one insertion boundary for every context before releasing the shared fence.
	// Later admissions and requeued memberships receive higher identities, even in empty queues.
	$captured = ai4seo_post_state_transaction(
		static function ( array $state ) use ( $guard, &$through ): bool {
			if ( 'active' !== ( $state['phase'] ?? '' ) || ( null !== $guard && ! $guard() ) ) {
				return false;
			}
			$rows = ai4seo_post_state_query( 'SELECT COALESCE(MAX(membership_id),0) AS last_id FROM {{memberships}}', array(), true );
			if ( null === $rows || ! isset( $rows[0]['last_id'] ) ) {
				return false;
			}
			$through = (int) $rows[0]['last_id'];
			return true;
		}
	);
	if ( ! $captured ) {
		return false;
	}
	foreach ( $pairs as $pending => $force ) {
		$after = 0;
		do {
			$page   = array();
			$stored = ai4seo_post_state_transaction(
				static function ( array $state ) use ( $pending, $force, $guard, $after, $through, &$page ): bool {
					if ( 'active' !== ( $state['phase'] ?? '' ) || ( null !== $guard && ! $guard() ) ) {
						return false;
					}
					$page = ai4seo_read_post_state_page( $pending, $after, 1000, 1, '', $through );
					if ( null === $page ) {
						return false;
					}
					$ids = array_map( 'intval', array_column( $page, 'post_id' ) );
					if ( ! $ids ) {
						return true;
					}
					$context    = AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME === $pending ? AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA : AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES;
					$processing = ai4seo_get_bulk_generation_queue_options_by_context( $context )['processing'];
					$members    = ai4seo_read_post_state_for_ids( $ids, array( $processing ) );
					$leases     = array();
					if ( null === $members || ! ai4seo_read_processing_claim_leases_under_lock( $processing, $leases, $ids ) ) {
						return false;
					}
					foreach ( $leases as $id => $lease ) {
						if ( ! isset( $members[ $processing ][ $id ] ) && ! empty( $lease['rollback_requested'] )
						&& array_key_exists( 'pending_was_present', $lease ) && array_key_exists( 'force_was_present', $lease )
						&& null === ai4seo_post_state_query(
							'DELETE FROM {{work}} WHERE kind={{kind}} AND post_id={{id}} AND token={{token}}',
							array(
								'kind'  => AI4SEO_POST_STATE_BUCKETS[ $processing ],
								'id'    => (int) $id,
								'token' => $lease['token'],
							)
						) ) {
							return false;
						}
					}
					$changed = false;
					return ai4seo_apply_post_state_delta_under_lock(
						array(),
						array(
							$pending => $ids,
							$force   => $ids,
						),
						1,
						$state,
						$changed
					)
					&& ai4seo_post_state_enqueue_under_lock( $ids, true ) && ( null === $guard || $guard() );
				}
			);
			if ( ! $stored ) {
				return false;
			}
			foreach ( $page as $row ) {
				if ( $collect ) {
					$removed[ $pending ][] = (int) $row['post_id'];
				}
				$after = (int) $row['membership_id'];
			}
		} while ( $page );
	}
	return true;
}

/**
 * Read only the indexed repair identities for one bounded automatic-selection packet.
 *
 * Pending work excludes that post regardless of payload validity or retry deadline.
 * Decoding unrelated repair payloads must not block healthy candidate identities.
 *
 * @param array $ids Candidate IDs.
 * @return array|null Pending IDs, or null when the exclusion cannot be verified.
 */
function ai4seo_read_indexed_analysis_pending_ids( array $ids ): ?array {
	if ( ! $ids ) {
		return array();
	}
	if ( count( $ids ) > ai4seo_post_state_batch_size() ) {
		return null;
	}
	$rows = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind=1 AND post_id IN ({{ids}})', array( 'ids' => $ids ), true );
	if ( null === $rows ) {
		return null;
	}
	return array_map( 'intval', array_column( $rows, 'post_id' ) );
}

/**
 * Admit automatic candidates atomically after rechecking current policy and owners.
 *
 * @param array      $ids Selected candidates.
 * @param string     $context Generation context.
 * @param int        $maximum Queue capacity.
 * @param array|null $policy Expected policy.
 * @return array|null Committed identities, or failure.
 */
function ai4seo_admit_indexed_auto_queue_candidates( array $ids, string $context, int $maximum, ?array $policy ): ?array {
	$admitted = array();
	$policy   = $policy ?? ai4seo_read_auto_queue_policy_snapshot( $context );
	$stored   = ai4seo_post_state_transaction(
		static function ( array $state ) use ( $ids, $context, $maximum, $policy, &$admitted ): bool {
			if ( 'active' !== ( $state['phase'] ?? '' ) || count( $ids ) > ai4seo_post_state_batch_size()
				|| ! ai4seo_validate_analysis_auto_queue_admission_under_lock( $context ) ) {
				return false;
			}
			$current_policy = ai4seo_read_auto_queue_policy_snapshot( $context );
			$account        = ai4seo_get_account_readiness( true );
			$cost           = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context ? ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post() : ai4seo_calculate_metadata_credits_cost_per_post();
			if ( 'ready' !== $current_policy['status'] || $current_policy['fingerprint'] !== $policy['fingerprint'] || ! $account['credits_ready'] || $account['credits'] < $cost ) {
				return false;
			}
			// A post may have changed after selection, before this transaction acquired the fence.
			$pending_analysis = ai4seo_read_indexed_analysis_pending_ids( $ids );
			if ( null === $pending_analysis ) {
				return false;
			}
			$ids     = array_values( array_diff( $ids, $pending_analysis ) );
			$classed = ai4seo_classify_auto_queue_candidates( $ids, $context, $current_policy );
			$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
			$members = ai4seo_read_post_state_for_ids( $ids, array_values( $options ) );
			$totals  = ai4seo_read_post_state_totals( 1 );
			$leases  = ai4seo_read_post_state_work( AI4SEO_POST_STATE_BUCKETS[ $options['processing'] ], $ids );
			if ( 'ready' !== $classed['status'] || null === $members || null === $totals || null === $leases ) {
				return false;
			}
			$capacity = $maximum - array_sum( $totals[ $options['pending'] ] ?? array() );
			$adds     = array();
			foreach ( $classed['eligible_ids'] as $id ) {
				if ( count( $admitted ) >= $capacity ) {
					break;
				}
				if ( ! isset( $members[ $options['missing'] ][ $id ] ) || isset( $leases[ $id ] ) ) {
					continue;
				}
				$blocked = false;
				foreach ( array( 'pending', 'processing', 'failed', 'hidden', 'auto_queue_disallowed' ) as $key ) {
					$blocked = $blocked || isset( $members[ $options[ $key ] ][ $id ] );
				}
				if ( ! $blocked ) {
					$admitted[]                         = $id;
					$adds[ $options['pending'] ][ $id ] = $members[ $options['missing'] ][ $id ];
				}
			}
			$changed = false;
			return ! $adds || ( ai4seo_clear_disabled_queue_inspection_state_under_lock()
				&& ai4seo_apply_post_state_delta_under_lock( $adds, array(), 1, $state, $changed )
				&& ai4seo_post_state_enqueue_under_lock( $admitted, true ) );
		}
	);
	return $stored ? $admitted : null;
}

/**
 * Select from the missing index in bounded SQL pages, using existing classifiers.
 *
 * @param string $context Generation context.
 * @param array  $policy Current selection policy.
 * @param array  $blocked Known unspent pending blockers.
 * @return array Existing auto-selection result format.
 */
function ai4seo_find_indexed_auto_queue_candidates( string $context, array $policy, array $blocked ): array {
	$result = array(
		'status'           => $policy['status'],
		'blocked_post_ids' => array(),
		'offset'           => 0,
		'fingerprint'      => '',
	);
	if ( 'ready' !== $policy['status'] ) {
		return $result;
	}
	$result['status'] = 'system_error';
	$state            = ai4seo_read_post_state_storage_status();
	$analysis         = ai4seo_get_posts_table_analysis_readiness();
	if ( null === $state || 'active' !== $state['phase'] || empty( $analysis['read_succeeded'] ) ) {
		return $result;
	}
	if ( empty( $analysis['contexts'][ $context ] ) ) {
		$result['status'] = $analysis['complete'] ? 'context_blocked' : 'analysis_pending';
		return $result;
	}
	$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	$pending = ai4seo_read_post_state_for_ids( $blocked, array( $options['pending'] ) );
	$totals  = ai4seo_read_post_state_totals( 1 );
	if ( null === $pending || null === $totals ) {
		return $result;
	}
	$result['blocked_post_ids'] = array_slice( array_keys( $pending[ $options['pending'] ] ), 0, 20 );
	$maximum                    = 2 + min( AI4SEO_AUTO_QUEUE_RETAINED_BLOCKER_LIMIT, count( $result['blocked_post_ids'] ) );
	if ( array_sum( $totals[ $options['pending'] ] ?? array() ) >= $maximum ) {
		$result['status'] = 'queue_full';
		return $result;
	}
	$is_media = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context;
	$cost     = $is_media ? ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post() : ai4seo_calculate_metadata_credits_cost_per_post();
	$account  = ai4seo_get_account_readiness( true );
	if ( ! $account['credits_ready'] || $account['credits'] < $cost ) {
		$result['status'] = $account['credits_ready'] ? 'insufficient_credits' : 'account_unavailable';
		return $result;
	}
	if ( ! $policy['post_types'] || ( $is_media && ! ai4seo_get_enabled_attachment_mime_types() ) ) {
		$result['status'] = 'empty';
		return $result;
	}
	$key                   = ai4seo_post_state_key( $options['missing'], 1, $state );
	$regular_types         = array_values( array_diff( $policy['post_types'], array( AI4SEO_TAXONOMY_PROXY_POST_TYPE ) ) );
	$has_archives          = in_array( AI4SEO_TAXONOMY_PROXY_POST_TYPE, $policy['post_types'], true );
	$fingerprints          = array(
		hash( 'sha256', $policy['fingerprint'] . ':' . $state['generation'] . ':regular' ),
		hash( 'sha256', $policy['fingerprint'] . ':' . $state['generation'] . ':archives' ),
		hash( 'sha256', $policy['fingerprint'] . ':' . $state['generation'] . ':regular_wrapped' ),
	);
	$progress              = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_QUEUE_SELECTION_PROGRESS, false );
	$previous              = $progress[ $context ] ?? array();
	$archive_pass          = $has_archives && ( ! $regular_types || ( $previous['fingerprint'] ?? '' ) === $fingerprints[1] );
	$wrapped               = ( $previous['fingerprint'] ?? '' ) === $fingerprints[2];
	$result['fingerprint'] = $fingerprints[ $archive_pass ? 1 : ( $wrapped ? 2 : 0 ) ];
	$cursor                = ( $previous['fingerprint'] ?? '' ) === $result['fingerprint'] ? (int) $previous['offset'] : 0;
	$order                 = $policy['settings'][ AI4SEO_SETTING_BULK_GENERATION_ORDER ];
	$started               = microtime( true );
	$pending_analysis_seen = false;
	for ( $run = 0; $run < AI4SEO_AUTO_QUEUE_SELECTION_CHUNKS_PER_RUN; ++$run ) {
		if ( microtime( true ) - $started >= 5 ) {
			break;
		}
		$descending = ! $archive_pass && 'newest' === $order;
		$values     = $key + array(
			'limit' => min( AI4SEO_AUTO_QUEUE_SELECTION_CHUNK_SIZE, ai4seo_post_state_batch_size() ),
			'types' => $archive_pass ? array( AI4SEO_TAXONOMY_PROXY_POST_TYPE ) : $regular_types,
		);
		$cursor_sql = '';
		if ( $cursor ) {
			$cursor_sql       = $descending ? ' AND post_id<{{cursor}}' : ' AND post_id>{{cursor}}';
			$values['cursor'] = $cursor;
		}
		// Random selection uses a random starting identity and then an indexed bounded window.
		// Membership FIFO is unchanged; no global array shuffle or database filesort is required.
		if ( ! $archive_pass && ! $wrapped && 'random' === $order && ! $cursor ) {
			$bounds = ai4seo_post_state_query( 'SELECT MIN(post_id) AS lo,MAX(post_id) AS hi FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}}', $key, true );
			if ( null === $bounds ) {
				return $result;
			}
			if ( ! empty( $bounds[0]['hi'] ) ) {
				$cursor_sql       = ' AND post_id>={{cursor}}';
				$values['cursor'] = wp_rand( (int) $bounds[0]['lo'], (int) $bounds[0]['hi'] );
			}
		}
		$rows = ai4seo_post_state_query( 'SELECT post_id FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_type IN ({{types}})' . $cursor_sql . ' ORDER BY post_id ' . ( $descending ? 'DESC' : 'ASC' ) . ' LIMIT {{limit}}', $values, true );
		if ( null === $rows ) {
			return $result;
		}
		if ( ! $rows ) {
			// A random start must also examine lower IDs before reporting empty or admitting archives.
			if ( ! $archive_pass && ! $wrapped && 'random' === $order ) {
				$wrapped               = true;
				$cursor                = 0;
				$result['fingerprint'] = $fingerprints[2];
				continue;
			}
			if ( ! $archive_pass && $has_archives ) {
				$archive_pass          = true;
				$cursor                = 0;
				$result['fingerprint'] = $fingerprints[1];
				continue;
			}
			$result['status'] = $pending_analysis_seen ? 'deferred' : ( $result['blocked_post_ids'] ? 'data_blocked' : 'empty' );
			return $result;
		}
		$ids              = array_map( 'intval', array_column( $rows, 'post_id' ) );
		$cursor           = end( $ids );
		$members          = ai4seo_read_post_state_for_ids( $ids, array( $options['pending'], $options['processing'], $options['failed'], $options['hidden'], $options['auto_queue_disallowed'] ) );
		$pending_analysis = ai4seo_read_indexed_analysis_pending_ids( $ids );
		if ( null === $members || null === $pending_analysis ) {
			return $result;
		}
		$pending_analysis_seen = $pending_analysis_seen || (bool) $pending_analysis;
		$blocked_ids           = array_fill_keys( $pending_analysis, true );
		foreach ( $members as $found ) {
			$blocked_ids += $found;
		}
		$ids                        = array_values( array_diff( $ids, array_keys( $blocked_ids ) ) );
		$classed                    = ai4seo_classify_auto_queue_candidates( $ids, $context, $policy );
		$result['blocked_post_ids'] = array_slice( array_values( array_unique( array_merge( $result['blocked_post_ids'], $classed['blocked_post_ids'] ) ) ), 0, 20 );
		if ( 'ready' !== $classed['status'] ) {
			$result['status'] = $classed['status'];
			return $result;
		}
		if ( $classed['eligible_ids'] ) {
			$admitted = ai4seo_admit_indexed_auto_queue_candidates( array_slice( $classed['eligible_ids'], 0, 2 ), $context, $maximum, $policy );
			if ( null === $admitted ) {
				return $result;
			}
			if ( $admitted ) {
				$result['status']           = 'queued';
				$result['candidates_found'] = true;
				return $result;
			}
		}
	}
	$result['offset'] = $cursor;
	$result['status'] = 'deferred';
	return $result;
}
