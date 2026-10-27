<?php
/**
 * Durable natural title order, bounded blocks and filtered block counters.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compare raw titles exactly like the existing list, including descending ID ties.
 *
 * @param array  $a First entry (id/value).
 * @param array  $b Second entry.
 * @param string $order Direction.
 * @return int
 */
function ai4seo_compare_content_type_title_entries( array $a, array $b, string $order = 'asc' ): int {
	$comparison = strnatcasecmp( (string) $a['value'], (string) $b['value'] );
	return $comparison ? ( 'asc' === $order ? 1 : -1 ) * $comparison : ( (int) $b['id'] <=> (int) $a['id'] );
}

/**
 * Comparator for persisted narrow rows.
 *
 * @param array $a First row.
 * @param array $b Second row.
 * @param int   $direction One ascending, two descending.
 * @return int
 */
function ai4seo_compare_title_order_rows( array $a, array $b, int $direction ): int {
	return ai4seo_compare_content_type_title_entries(
		array(
			'id'    => $a['post_id'],
			'value' => $a['post_title'],
		),
		array(
			'id'    => $b['post_id'],
			'value' => $b['post_title'],
		),
		1 === $direction ? 'asc' : 'desc'
	);
}

/**
 * Validate compact resumable state; an unreadable checkpoint never means empty.
 *
 * @param mixed $state Checkpoint.
 * @return bool
 */
function ai4seo_validate_title_order_state( $state ): bool {
	if ( array() === $state ) {
		return true;
	}
	$keys = array( 'version', 'generation', 'source_generation', 'phase', 'after', 'runs', 'level', 'direction', 'pair', 'left', 'right', 'out', 'block', 'revision', 'signature' );
	if ( ! is_array( $state ) || array_diff( array_keys( $state ), $keys ) || array_diff( $keys, array_keys( $state ) )
		|| AI4SEO_TITLE_ORDER_VERSION !== ( $state['version'] ?? null )
		|| ! in_array( $state['phase'] ?? '', array( 'scan', 'merge', 'blocks', 'reconcile', 'verify', 'ready', 'refresh' ), true )
		|| ! is_string( $state['signature'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/D', $state['signature'] ) ) {
		return false;
	}
	foreach ( array_diff( $keys, array( 'phase', 'signature' ) ) as $key ) {
		if ( ! is_int( $state[ $key ] ) || $state[ $key ] < 0 ) {
			return false;
		}
	}
	return $state['generation'] > 0 && $state['source_generation'] > 0 && in_array( $state['direction'], array( 1, 2 ), true ) && $state['level'] < 64;
}

/**
 * Read an authoritative checkpoint.
 *
 * @return array|null
 */
function ai4seo_read_title_order_state(): ?array {
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	if ( null === $snapshot || ( $snapshot['exists'] && ! is_array( $snapshot['value'] ) ) ) {
		return null;
	}
	$state = $snapshot['value'][ AI4SEO_ENVIRONMENTAL_VARIABLE_TITLE_ORDER ] ?? array();
	return ai4seo_validate_title_order_state( $state ) ? $state : null;
}

/**
 * Persist progress inside the same transaction as its rows.
 *
 * @param array $previous Expected state.
 * @param array $next New state.
 * @return bool
 */
function ai4seo_save_title_order_state( array $previous, array $next ): bool {
	$replaced = false;
	return ai4seo_compare_and_swap_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_TITLE_ORDER, $previous, $next, $replaced ) && $replaced;
}

/**
 * Identify the comparator across PHP/locale changes.
 *
 * @return string
 */
function ai4seo_title_order_signature(): string {
	return hash( 'sha256', AI4SEO_TITLE_ORDER_VERSION . ':' . PHP_VERSION . ':' . setlocale( LC_CTYPE, 0 ) );
}

/**
 * Verify InnoDB schema and write privileges.
 *
 * @return array
 */
function ai4seo_ensure_title_order_schema(): array {
	if ( PHP_INT_SIZE < 8 ) {
		return array(
			'success' => false,
			'reason'  => 'title_order_requires_64_bit_integers',
		);
	}
	return ai4seo_ensure_post_state_schema_tables(
		array(
			'title_rows'   => 'generation bigint unsigned NOT NULL, direction tinyint unsigned NOT NULL, level bigint unsigned NOT NULL, run_id bigint unsigned NOT NULL, position bigint unsigned NOT NULL, post_id bigint unsigned NOT NULL, post_title text NOT NULL, post_type varchar(20) NOT NULL, post_status varchar(20) NOT NULL, metadata_mask bigint unsigned NOT NULL, media_mask bigint unsigned NOT NULL,
				PRIMARY KEY (generation,direction,level,post_id), KEY page (generation,direction,level,run_id,position), KEY cleanup (generation,level,direction,post_id)',
			'title_blocks' => 'generation bigint unsigned NOT NULL, direction tinyint unsigned NOT NULL, block_id bigint unsigned NOT NULL, post_id bigint unsigned NOT NULL, post_title text NOT NULL, total bigint NOT NULL,
				PRIMARY KEY (generation,direction,block_id)',
			'title_counts' => 'generation bigint unsigned NOT NULL, direction tinyint unsigned NOT NULL, block_id bigint unsigned NOT NULL, post_type varchar(20) NOT NULL, post_status varchar(20) NOT NULL, context tinyint unsigned NOT NULL, bucket varchar(24) NOT NULL, total bigint NOT NULL,
				PRIMARY KEY (generation,direction,block_id,post_type,post_status,context,bucket), KEY counts (generation,direction,context,post_type,post_status,bucket,block_id)',
		),
		array(
			'title_rows'   => array(
				'PRIMARY' => 'generation,direction,level,post_id',
				'page'    => 'generation,direction,level,run_id,position',
				'cleanup' => 'generation,level,direction,post_id',
			),
			'title_blocks' => array( 'PRIMARY' => 'generation,direction,block_id' ),
			'title_counts' => array(
				'PRIMARY' => 'generation,direction,block_id,post_type,post_status,context,bucket',
				'counts'  => 'generation,direction,context,post_type,post_status,bucket,block_id',
			),
		)
	);
}

/**
 * Execute a bounded insertion through the existing closed SQL compiler.
 *
 * @param string $table Closed table token.
 * @param array  $rows Ordered column values.
 * @return bool
 */
function ai4seo_insert_title_order_rows( string $table, array $rows ): bool {
	$definitions = array(
		'title_rows'   => array( 'generation,direction,level,run_id,position,post_id,post_title,post_type,post_status,metadata_mask,media_mask', 'run_id=VALUES(run_id),position=VALUES(position),post_title=VALUES(post_title),post_type=VALUES(post_type),post_status=VALUES(post_status),metadata_mask=VALUES(metadata_mask),media_mask=VALUES(media_mask)' ),
		'title_blocks' => array( 'generation,direction,block_id,post_id,post_title,total', 'post_id=VALUES(post_id),post_title=VALUES(post_title),total=VALUES(total)' ),
		'title_counts' => array( 'generation,direction,block_id,post_type,post_status,context,bucket,total', 'total=total+VALUES(total)' ),
	);
	if ( ! isset( $definitions[ $table ] ) ) {
		return false;
	}
	if ( ! $rows ) {
		return true;
	}
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read the connection's current numeric transport limit before bounded writes.
	$maximum = $wpdb->get_var( 'SELECT @@max_allowed_packet' );
	if ( $wpdb->last_error || (int) $maximum <= 8192 ) {
		return false;
	}
	$limit      = min( AI4SEO_TITLE_ORDER_PACKET_BYTES, (int) $maximum - 8192 );
	$packets    = array();
	$packet     = array();
	$bytes      = 4096;
	$batch_size = ai4seo_post_state_batch_size();
	foreach ( $rows as $row ) {
		$row_bytes = 128;
		foreach ( $row as $value ) {
			// SQL escaping can double string bytes; reserve framing and numeric literals too.
			$row_bytes += is_string( $value ) ? 2 * strlen( $value ) + 4 : 24;
		}
		if ( $row_bytes + 4096 > $limit ) {
			return false;
		}
		if ( $packet && ( $bytes + $row_bytes > $limit || count( $packet ) >= $batch_size ) ) {
			$packets[] = $packet;
			$packet    = array();
			$bytes     = 4096;
		}
		$packet[] = $row;
		$bytes   += $row_bytes;
	}
	if ( $packet ) {
		$packets[] = $packet;
	}
	foreach ( $packets as $packet ) {
		$payload = ai4seo_build_post_state_insert_payload( $packet );
		if ( null === ai4seo_post_state_query(
			'INSERT INTO {{' . $table . '}} (' . $definitions[ $table ][0] . ') VALUES ' . $payload['tuples'] . ' ON DUPLICATE KEY UPDATE ' . $definitions[ $table ][1],
			$payload['values']
		) ) {
			return false;
		}
	}
	return true;
}

/**
 * Read current narrow source rows and the two canonical status families.
 *
 * @param array $ids Bounded IDs.
 * @param bool  $titles Include title bytes only for sorting/renames.
 * @return array|null
 */
function ai4seo_read_title_order_sources( array $ids, bool $titles = true ): ?array {
	if ( ! $ids ) {
		return array();
	}
	require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/pages/content_types/list-filters.php';
	$rows    = ai4seo_post_state_query( 'SELECT ID AS post_id,post_type,post_status' . ( $titles ? ',post_title' : '' ) . ' FROM {{posts}} WHERE ID IN ({{ids}})', array( 'ids' => $ids ), true );
	$members = ai4seo_read_post_state_for_ids( $ids, AI4SEO_ALL_POST_ID_OPTIONS );
	if ( null === $rows || null === $members ) {
		return null;
	}
	foreach ( $rows as &$row ) {
		$row['post_id'] = (int) $row['post_id'];
		foreach ( array(
			'metadata_mask' => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			'media_mask'    => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
		) as $column => $context ) {
			$mask = 0;
			foreach ( ai4seo_get_content_type_status_option_names( $context ) as $status => $option ) {
				if ( isset( $members[ $option ][ $row['post_id'] ] ) ) {
					$mask |= AI4SEO_TITLE_ORDER_BITS[ $status ];
				}
			}
			if ( $mask & AI4SEO_TITLE_ORDER_BITS['hidden'] ) {
				$mask = AI4SEO_TITLE_ORDER_BITS['hidden'];
			} else {
				$mask |= AI4SEO_TITLE_ORDER_BITS['all'] | AI4SEO_TITLE_ORDER_BITS['unverified'];
				if ( ( $mask & AI4SEO_TITLE_ORDER_BITS['missing'] ) && ! ( $mask & ( AI4SEO_TITLE_ORDER_BITS['queued'] | AI4SEO_TITLE_ORDER_BITS['processing'] | AI4SEO_TITLE_ORDER_BITS['failed'] | AI4SEO_TITLE_ORDER_BITS['auto_queue_disallowed'] ) ) ) {
					$mask |= AI4SEO_TITLE_ORDER_BITS['waiting_to_queue'];
				}
			}
			$row[ $column ] = $mask;
		}
	}
	unset( $row );
	return array_column( $rows, null, 'post_id' );
}

/**
 * Read a run using a narrow byte probe before allocating title strings.
 *
 * @param array $key Generation/direction/level/run_id.
 * @param int   $after Last position.
 * @param int   $limit Row limit.
 * @return array|null
 */
function ai4seo_read_title_order_run( array $key, int $after, int $limit = 1000 ): ?array {
	$values = array_replace(
		$key,
		array(
			'after' => $after,
			'limit' => min( $limit, ai4seo_post_state_batch_size() ),
		)
	);
	$probe  = ai4seo_post_state_query( 'SELECT post_id,OCTET_LENGTH(post_title) AS bytes FROM {{title_rows}} FORCE INDEX (page) WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND run_id={{run_id}} AND position>{{after}} ORDER BY position LIMIT {{limit}}', $values, true );
	if ( null === $probe ) {
		return null;
	}
	$ids   = array();
	$bytes = 0;
	foreach ( $probe as $row ) {
		if ( $ids && $bytes + (int) $row['bytes'] > AI4SEO_TITLE_ORDER_PACKET_BYTES ) {
			break;
		}
		$ids[]  = (int) $row['post_id'];
		$bytes += (int) $row['bytes'];
	}
	return $ids ? ai4seo_post_state_query( 'SELECT * FROM {{title_rows}} WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND post_id IN ({{ids}}) ORDER BY position', $key + array( 'ids' => $ids ), true ) : array();
}

/**
 * Read an entire bounded block even after the configured SQL packet size shrinks.
 *
 * @param array $key Index key including run_id.
 * @return array|null
 */
function ai4seo_read_title_order_block( array $key ): ?array {
	$rows  = array();
	$after = 0;
	do {
		$packet = ai4seo_read_title_order_run( $key, $after, AI4SEO_TITLE_ORDER_BLOCK_SIZE );
		if ( null === $packet ) {
			return null;
		}
		if ( $packet ) {
			$after = (int) end( $packet )['position'];
			$rows  = array_merge( $rows, $packet );
		}
		if ( count( $rows ) > AI4SEO_TITLE_ORDER_BLOCK_SIZE ) {
			return null;
		}
	} while ( $packet );
	return $rows;
}

/**
 * Replace one bounded block and its counters in the caller's transaction.
 *
 * @param array $state Index state.
 * @param int   $direction Direction.
 * @param int   $block Block label.
 * @param array $rows Ordered rows.
 * @return bool
 */
function ai4seo_write_title_order_block( array $state, int $direction, int $block, array $rows ): bool {
	$key = array(
		'generation' => $state['generation'],
		'direction'  => $direction,
		'level'      => $state['level'],
		'block'      => $block,
	);
	foreach ( array(
		'title_rows'   => 'run_id',
		'title_counts' => 'block_id',
		'title_blocks' => 'block_id',
	) as $table => $column ) {
		if ( null === ai4seo_post_state_query( 'DELETE FROM {{' . $table . '}} WHERE generation={{generation}} AND direction={{direction}} AND ' . $column . '={{block}}' . ( 'title_rows' === $table ? ' AND level={{level}}' : '' ), $key ) ) {
			return false;
		}
	}
	if ( ! $rows ) {
		return true;
	}
	$insert   = array();
	$counters = array();
	foreach ( array_values( $rows ) as $position => $row ) {
		$insert[] = array( $state['generation'], $direction, $state['level'], $block, $position + 1, (int) $row['post_id'], $row['post_title'], $row['post_type'], $row['post_status'], (int) $row['metadata_mask'], (int) $row['media_mask'] );
		ai4seo_add_title_order_counter_delta( $counters, $state, $direction, $block, $row, 1 );
	}
	$first = reset( $rows );
	return ai4seo_insert_title_order_rows( 'title_rows', $insert )
		&& ai4seo_insert_title_order_rows( 'title_blocks', array( array( $state['generation'], $direction, $block, (int) $first['post_id'], $first['post_title'], count( $rows ) ) ) )
		&& ai4seo_insert_title_order_rows( 'title_counts', array_values( $counters ) );
}

/**
 * Accumulate small counter deltas without loading any complete membership list.
 *
 * @param array $counts Accumulator.
 * @param array $state Index state.
 * @param int   $direction Direction.
 * @param int   $block Block label.
 * @param array $row Narrow row.
 * @param int   $amount Signed change.
 */
function ai4seo_add_title_order_counter_delta( array &$counts, array $state, int $direction, int $block, array $row, int $amount ): void {
	foreach ( array(
		1 => 'metadata_mask',
		2 => 'media_mask',
	) as $context => $column ) {
		foreach ( AI4SEO_TITLE_ORDER_BITS as $bucket => $bit ) {
			if ( ! ( (int) $row[ $column ] & $bit ) ) {
				continue;
			}
			$key = implode( ':', array( $direction, $block, $row['post_type'], $row['post_status'], $context, $bucket ) );
			if ( ! isset( $counts[ $key ] ) ) {
				$counts[ $key ] = array( $state['generation'], $direction, $block, $row['post_type'], $row['post_status'], $context, $bucket, 0 );
			}
			$counts[ $key ][7] += $amount;
		}
	}
}

/**
 * Store a distinct title token; normal WordPress writes need not acquire SOOZ's fence.
 *
 * @param int  $post_id Changed post.
 * @param bool $pending Whether the WordPress write is about to start.
 * @return bool
 */
function ai4seo_capture_title_order_change( int $post_id, bool $pending = false ): bool {
	$routing = ai4seo_read_post_state_storage_status();
	if ( array() === $routing || 'legacy' === ( $routing['phase'] ?? '' ) ) {
		return true;
	}
	if ( null === $routing ) {
		return false;
	}
	$state = ai4seo_read_title_order_state();
	if ( array() === $state || $post_id <= 0 ) {
		return true;
	}
	if ( null === $state ) {
		return false;
	}
	$token   = wp_generate_uuid4();
	$expires = $pending ? time() + 30 : 0;
	$ok      = ai4seo_post_state_insert_rows(
		'work',
		array(
			array(
				AI4SEO_TITLE_ORDER_WORK_KIND,
				$post_id,
				$token,
				$expires,
				wp_json_encode(
					array(
						'token'      => $token,
						'expires_at' => $expires,
					)
				),
			),
		),
		'work'
	);
	ai4seo_force_bump_content_type_list_cache_version();
	if ( $ok && ! $pending && ! isset( $GLOBALS['ai4seo_post_state_transaction'] ) ) {
		do_action( 'ai4seo_post_state_checkpoint', 'title_after_change_capture' );
	}
	// Wake the bounded repair worker after a WordPress save, without scheduling
	// inside canonical transactions or replacing the recurring fallback first.
	static $scheduled = array();
	$scope            = ai4seo_get_site_options_request_cache_scope();
	if ( $ok && ! $pending && ! isset( $GLOBALS['ai4seo_post_state_transaction'] ) && ! isset( $scheduled[ $scope ] ) ) {
		$schedule = ai4seo_schedule_background_job_checked( AI4SEO_TITLE_ORDER_CRON_HOOK, 20 );
		if ( in_array( $schedule['status'], array( 'scheduled', 'already-scheduled', 'scheduled-with-fallback' ), true ) ) {
			$scheduled[ $scope ] = true;
		}
	}
	return $ok;
}

/**
 * Mark existing-post updates before WordPress changes their bytes.
 *
 * @param int $post_id Identity.
 */
function ai4seo_capture_title_order_before_change( int $post_id ): void {
	ai4seo_capture_title_order_change( $post_id, true );
}

/**
 * Incrementally update masks under the canonical status writer's fence.
 *
 * @param array $ids Changed identities.
 * @return bool
 */
function ai4seo_apply_title_order_delta( array $ids ): bool {
	$state = ai4seo_read_title_order_state();
	if ( array() === $state || ! $ids ) {
		return true;
	}
	if ( null === $state ) {
		return false;
	}
	// A retired comparator cannot serve reads and will be rebuilt from current
	// memberships. Updating its old rows during import adds no usable state.
	if ( ai4seo_title_order_signature() !== $state['signature'] ) {
		return true;
	}
	$storage = ai4seo_read_post_state_storage_status();
	if ( null === $storage ) {
		return false;
	}
	if ( ( $storage['generation'] ?? 0 ) !== $state['source_generation'] ) {
		return true;
	}
	if ( 'ready' !== $state['phase'] ) {
		foreach ( $ids as $id ) {
			if ( ! ai4seo_capture_title_order_change( (int) $id ) ) {
				return false;
			}
		}
		return true;
	}
	$source = ai4seo_read_title_order_sources( $ids, false );
	$rows   = ai4seo_post_state_query( 'SELECT direction,run_id,post_id,post_type,post_status,metadata_mask,media_mask FROM {{title_rows}} WHERE generation={{generation}} AND direction IN (1,2) AND level={{level}} AND post_id IN ({{ids}})', $state + array( 'ids' => $ids ), true );
	if ( null === $source || null === $rows ) {
		return false;
	}
	$counts = array();
	$blocks = array();
	foreach ( $rows as $row ) {
		$id  = (int) $row['post_id'];
		$new = $source[ $id ] ?? null;
		if ( null === $new || $row['post_type'] !== $new['post_type'] || $row['post_status'] !== $new['post_status'] ) {
			if ( ! ai4seo_capture_title_order_change( $id ) ) {
				return false;
			}
			continue;
		}
		if ( (int) $row['metadata_mask'] === $new['metadata_mask'] && (int) $row['media_mask'] === $new['media_mask'] ) {
			continue;
		}
		$blocks[] = (int) $row['run_id'];
		ai4seo_add_title_order_counter_delta( $counts, $state, (int) $row['direction'], (int) $row['run_id'], $row, -1 );
		ai4seo_add_title_order_counter_delta( $counts, $state, (int) $row['direction'], (int) $row['run_id'], $new, 1 );
		if ( null === ai4seo_post_state_query( 'UPDATE {{title_rows}} SET metadata_mask={{metadata_mask}},media_mask={{media_mask}} WHERE generation={{generation}} AND level={{level}} AND direction={{direction}} AND post_id={{post_id}}', array_replace( $state, $new, array( 'direction' => (int) $row['direction'] ) ) ) ) {
			return false;
		}
	}
	$counts = array_filter(
		$counts,
		static function ( array $row ): bool {
			return 0 !== $row[7];
		}
	);
	if ( ! ai4seo_insert_title_order_rows( 'title_counts', array_values( $counts ) ) ) {
		return false;
	}
	if ( $blocks ) {
		$invalid = ai4seo_post_state_query( 'SELECT total FROM {{title_counts}} WHERE generation={{generation}} AND block_id IN ({{blocks}}) AND total<0 LIMIT 1', $state + array( 'blocks' => array_values( array_unique( $blocks ) ) ), true );
		if ( null === $invalid || $invalid ) {
			return false;
		}
	}
	$next = $state;
	++$next['revision'];
	return ai4seo_save_title_order_state( $state, $next );
}

/**
 * Move one title by reading a directory of labels and only logarithmic boundaries.
 *
 * @param array      $state Current index.
 * @param int        $direction Direction.
 * @param int        $id Identity.
 * @param array|null $source Current post, null after deletion.
 * @return bool
 */
function ai4seo_move_title_order_row( array $state, int $direction, int $id, ?array $source ): bool {
	$key = array(
		'generation' => $state['generation'],
		'level'      => $state['level'],
		'direction'  => $direction,
	);
	$old = ai4seo_post_state_query( 'SELECT run_id,post_id,post_title,post_type,post_status,metadata_mask,media_mask FROM {{title_rows}} WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND post_id={{id}}', $key + array( 'id' => $id ), true );
	if ( null === $old ) {
		return false;
	}
	if ( $old ) {
		if ( null !== $source && $source['post_title'] === $old[0]['post_title'] && $source['post_type'] === $old[0]['post_type'] && $source['post_status'] === $old[0]['post_status']
			&& $source['metadata_mask'] === (int) $old[0]['metadata_mask'] && $source['media_mask'] === (int) $old[0]['media_mask'] ) {
			return true;
		}
		$block = (int) $old[0]['run_id'];
		$rows  = ai4seo_read_title_order_block( $key + array( 'run_id' => $block ) );
		if ( null === $rows ) {
			return false;
		}
		if ( null !== $source && 0 === ai4seo_compare_title_order_rows( $old[0], $source, $direction ) ) {
			foreach ( $rows as &$row ) {
				if ( (int) $row['post_id'] === $id ) {
					$row = $source;
				}
			}
			unset( $row );
			return ai4seo_write_title_order_block( $state, $direction, $block, $rows );
		}
		$rows = array_filter(
			$rows,
			static function ( array $row ) use ( $id ): bool {
				return (int) $row['post_id'] !== $id;
			}
		);
		if ( ! ai4seo_write_title_order_block( $state, $direction, $block, $rows ) ) {
			return false;
		}
	}
	if ( null === $source ) {
		return true;
	}
	$directory = ai4seo_post_state_query( 'SELECT block_id FROM {{title_blocks}} WHERE generation={{generation}} AND direction={{direction}} ORDER BY block_id', $key, true );
	if ( null === $directory ) {
		return false;
	}
	$low    = 0;
	$high   = count( $directory ) - 1;
	$chosen = 0;
	while ( $low <= $high ) {
		$middle   = intdiv( $low + $high, 2 );
		$boundary = ai4seo_post_state_query( 'SELECT post_id,post_title FROM {{title_blocks}} WHERE generation={{generation}} AND direction={{direction}} AND block_id={{block}}', $key + array( 'block' => (int) $directory[ $middle ]['block_id'] ), true );
		if ( ! $boundary ) {
			return false;
		}
		if ( ai4seo_compare_title_order_rows( $boundary[0], $source, $direction ) <= 0 ) {
			$chosen = $middle;
			$low    = $middle + 1;
		} else {
			$high = $middle - 1;
		}
	}
	$block = (int) ( $directory[ $chosen ]['block_id'] ?? AI4SEO_TITLE_ORDER_BLOCK_GAP );
	$rows  = $directory ? ai4seo_read_title_order_block( $key + array( 'run_id' => $block ) ) : array();
	if ( null === $rows ) {
		return false;
	}
	$rows[] = $source;
	usort(
		$rows,
		static function ( array $a, array $b ) use ( $direction ): int {
			return ai4seo_compare_title_order_rows( $a, $b, $direction );
		}
	);
	$bytes = array_sum(
		array_map(
			static function ( array $row ): int {
				return strlen( $row['post_title'] );
			},
			$rows
		)
	);
	if ( count( $rows ) <= AI4SEO_TITLE_ORDER_BLOCK_SIZE && $bytes <= AI4SEO_TITLE_ORDER_PACKET_BYTES ) {
		return ai4seo_write_title_order_block( $state, $direction, $block, $rows );
	}
	$next = (int) ( $directory[ $chosen + 1 ]['block_id'] ?? ( $block + AI4SEO_TITLE_ORDER_BLOCK_GAP * 2 ) );
	if ( $next - $block < 2 ) {
		$GLOBALS['ai4seo_title_order_rebuild_scope'] = ai4seo_get_site_options_request_cache_scope();
		return false; // Roll back this move before starting a resumable shadow generation.
	}
	$split      = 0;
	$left_bytes = 0;
	$row_count  = count( $rows );
	$distance   = PHP_INT_MAX;
	foreach ( $rows as $index => $row ) {
		$left_bytes += strlen( $row['post_title'] );
		$cut         = $index + 1;
		if ( $cut < $row_count && $cut <= AI4SEO_TITLE_ORDER_BLOCK_SIZE && $row_count - $cut <= AI4SEO_TITLE_ORDER_BLOCK_SIZE
			&& $left_bytes <= AI4SEO_TITLE_ORDER_PACKET_BYTES && $bytes - $left_bytes <= AI4SEO_TITLE_ORDER_PACKET_BYTES
			&& abs( $cut - intdiv( $row_count, 2 ) ) < $distance ) {
			$split    = $cut;
			$distance = abs( $cut - intdiv( $row_count, 2 ) );
		}
	}
	if ( 0 === $split ) {
		return false;
	}
	do_action( 'ai4seo_post_state_checkpoint', 'title_before_split' );
	return ai4seo_write_title_order_block( $state, $direction, $block, array_slice( $rows, 0, $split ) )
		&& ai4seo_write_title_order_block( $state, $direction, $block + intdiv( $next - $block, 2 ), array_slice( $rows, $split ) );
}

/**
 * Process one token atomically; a concurrent replacement survives its acknowledgement.
 *
 * @param array $state Current index state, updated on success.
 * @return bool
 */
function ai4seo_reconcile_title_order_batch( array &$state ): bool {
	$work = ai4seo_read_post_state_work( AI4SEO_TITLE_ORDER_WORK_KIND, null, 1 );
	if ( null === $work ) {
		return false;
	}
	$entry = $work ? reset( $work ) : null;
	if ( ! $work || $entry['expires_at'] > time() ) {
		return true;
	}
	$id     = (int) key( $work );
	$source = ai4seo_read_title_order_sources( array( $id ) );
	if ( null === $source ) {
		return false;
	}
	foreach ( array( 1, 2 ) as $direction ) {
		if ( ! ai4seo_move_title_order_row( $state, $direction, $id, $source[ $id ] ?? null ) ) {
			return false;
		}
	}
	do_action( 'ai4seo_post_state_checkpoint', 'title_before_ack' );
	if ( null === ai4seo_post_state_query(
		'DELETE FROM {{work}} WHERE kind={{kind}} AND post_id={{id}} AND token={{token}}',
		array(
			'kind'  => AI4SEO_TITLE_ORDER_WORK_KIND,
			'id'    => $id,
			'token' => $entry['token'],
		)
	) ) {
		return false;
	}
	++$state['revision'];
	return true;
}

/**
 * Advance one atomic packet of external merge construction in database runs.
 * No file handles, large options, or offset scans are needed for recovery.
 *
 * @param array $next Mutable checkpoint.
 * @return bool
 */
function ai4seo_build_title_order_packet( array &$next ): bool {
	$limit = ai4seo_post_state_batch_size();
	if ( 'scan' === $next['phase'] ) {
		$probe = ai4seo_post_state_query( 'SELECT ID,OCTET_LENGTH(post_title) AS bytes FROM {{posts}} WHERE ID>{{after}} ORDER BY ID LIMIT {{limit}}', $next + array( 'limit' => $limit ), true );
		if ( null === $probe ) {
			return false;
		}
		$ids   = array();
		$bytes = 0;
		foreach ( $probe as $row ) {
			if ( $ids && $bytes + (int) $row['bytes'] > AI4SEO_TITLE_ORDER_PACKET_BYTES ) {
				break;
			}
			$ids[]  = (int) $row['ID'];
			$bytes += (int) $row['bytes'];
		}
		if ( ! $ids ) {
			$next['phase'] = $next['runs'] > 1 ? 'merge' : 'blocks';
			$next['after'] = 0;
			return true;
		}
		$rows = ai4seo_read_title_order_sources( $ids );
		if ( null === $rows ) {
			return false;
		}
		++$next['runs'];
		foreach ( array( 1, 2 ) as $direction ) {
			usort(
				$rows,
				static function ( array $a, array $b ) use ( $direction ): int {
					return ai4seo_compare_title_order_rows( $a, $b, $direction );
				}
			);
			$insert = array();
			foreach ( $rows as $position => $row ) {
				$insert[] = array( $next['generation'], $direction, 0, $next['runs'], $position + 1, $row['post_id'], $row['post_title'], $row['post_type'], $row['post_status'], $row['metadata_mask'], $row['media_mask'] );
			}
			if ( ! ai4seo_insert_title_order_rows( 'title_rows', $insert ) ) {
				return false;
			}
		}
		$next['after'] = max( $ids );
		return true;
	}
	if ( 'merge' === $next['phase'] ) {
		$left  = ai4seo_read_title_order_run( $next + array( 'run_id' => $next['pair'] * 2 + 1 ), $next['left'] );
		$right = ai4seo_read_title_order_run( $next + array( 'run_id' => $next['pair'] * 2 + 2 ), $next['right'] );
		if ( null === $left || null === $right ) {
			return false;
		}
		if ( ! $left && ! $right ) {
			++$next['pair'];
			$next['left']  = 0;
			$next['right'] = 0;
			$next['out']   = 0;
			if ( $next['pair'] * 2 >= $next['runs'] ) {
				$next['pair'] = 0;
				if ( 1 === $next['direction'] ) {
					$next['direction'] = 2;
				} else {
					$next['direction'] = 1;
					++$next['level'];
					$next['runs'] = (int) ceil( $next['runs'] / 2 );
					if ( 1 === $next['runs'] ) {
						$next['phase'] = 'blocks';
					}
				}
			}
			return true;
		}
		$insert = array();
		$l      = 0;
		$r      = 0;
		while ( $l + $r < $limit && ( isset( $left[ $l ] ) || isset( $right[ $r ] ) ) ) {
			// Stop at a consumed nonempty packet: its next unseen row may precede the other run.
			if ( ( $left && ! isset( $left[ $l ] ) ) || ( $right && ! isset( $right[ $r ] ) ) ) {
				break;
			}
			$take_left                             = ! isset( $right[ $r ] ) || ( isset( $left[ $l ] ) && ai4seo_compare_title_order_rows( $left[ $l ], $right[ $r ], $next['direction'] ) <= 0 );
			$row                                   = $take_left ? $left[ $l++ ] : $right[ $r++ ];
			$next[ $take_left ? 'left' : 'right' ] = (int) $row['position'];
			$insert[]                              = array( $next['generation'], $next['direction'], $next['level'] + 1, $next['pair'] + 1, ++$next['out'], (int) $row['post_id'], $row['post_title'], $row['post_type'], $row['post_status'], (int) $row['metadata_mask'], (int) $row['media_mask'] );
		}
		return ai4seo_insert_title_order_rows( 'title_rows', $insert );
	}
	if ( 'blocks' === $next['phase'] ) {
		$rows = ai4seo_read_title_order_run( $next + array( 'run_id' => 1 ), $next['after'], AI4SEO_TITLE_ORDER_BLOCK_SIZE );
		if ( null === $rows ) {
			return false;
		}
		if ( ! $rows ) {
			$next['after'] = 0;
			$next['block'] = 0;
			if ( 1 === $next['direction'] ) {
				$next['direction'] = 2;
			} else {
				$next['direction'] = 1;
				++$next['level'];
				$next['phase'] = 'refresh';
			}
			return true;
		}
		$next['after']  = (int) end( $rows )['position'];
		$next['block'] += AI4SEO_TITLE_ORDER_BLOCK_GAP;
		$destination    = $next;
		++$destination['level'];
		return ai4seo_write_title_order_block( $destination, $next['direction'], $next['block'], $rows );
	}
	if ( 'reconcile' === $next['phase'] || 'ready' === $next['phase'] ) {
		if ( ! ai4seo_reconcile_title_order_batch( $next ) ) {
			return false;
		}
		$pending = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1 FOR UPDATE', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true );
		if ( null === $pending ) {
			return false;
		}
		if ( ! $pending && 'reconcile' === $next['phase'] ) {
			$next['phase'] = 'verify';
		}
		return true;
	}
	if ( 'verify' === $next['phase'] || 'refresh' === $next['phase'] ) {
		$blocks = ai4seo_post_state_query( 'SELECT block_id,total FROM {{title_blocks}} WHERE generation={{generation}} AND direction={{direction}} AND block_id>{{after}} ORDER BY block_id LIMIT 1', $next, true );
		if ( null === $blocks ) {
			return false;
		}
		if ( $blocks ) {
			$block = (int) $blocks[0]['block_id'];
			$rows  = ai4seo_read_title_order_block( $next + array( 'run_id' => $block ) );
			if ( null === $rows || count( $rows ) !== (int) $blocks[0]['total'] ) {
				return false;
			}
			if ( 'refresh' === $next['phase'] ) {
				$source = ai4seo_read_title_order_sources( array_map( 'intval', array_column( $rows, 'post_id' ) ), false );
				if ( null === $source ) {
					return false;
				}
				$changed = false;
				foreach ( $rows as &$row ) {
					if ( isset( $source[ $row['post_id'] ] ) ) {
						$current = $source[ $row['post_id'] ];
						foreach ( array( 'post_type', 'post_status', 'metadata_mask', 'media_mask' ) as $field ) {
							$changed = $changed || (string) $row[ $field ] !== (string) $current[ $field ];
						}
						$row = array_replace( $row, $current );
					}
				}
				unset( $row );
				if ( $changed && ! ai4seo_write_title_order_block( $next, $next['direction'], $block, $rows ) ) {
					return false;
				}
			} else {
				// Check both the local order and the boundary to the preceding block.
				$previous = ai4seo_post_state_query( 'SELECT block_id FROM {{title_blocks}} WHERE generation={{generation}} AND direction={{direction}} AND block_id<{{block}} ORDER BY block_id DESC LIMIT 1', array_replace( $next, array( 'block' => $block ) ), true );
				if ( null === $previous ) {
					return false;
				}
				$last = null;
				if ( $previous ) {
					$boundary = ai4seo_post_state_query( 'SELECT post_id,post_title FROM {{title_rows}} FORCE INDEX (page) WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND run_id={{block}} ORDER BY position DESC LIMIT 1', array_replace( $next, array( 'block' => (int) $previous[0]['block_id'] ) ), true );
					if ( ! $boundary ) {
						return false;
					}
					$last = $boundary[0];
				}
				$expected = array();
				foreach ( $rows as $row ) {
					if ( null !== $last && ai4seo_compare_title_order_rows( $last, $row, $next['direction'] ) >= 0 ) {
						return false;
					}
					$last = $row;
					ai4seo_add_title_order_counter_delta( $expected, $next, $next['direction'], $block, $row, 1 );
				}
				$actual = ai4seo_post_state_query( 'SELECT generation,direction,block_id,post_type,post_status,context,bucket,total FROM {{title_counts}} WHERE generation={{generation}} AND direction={{direction}} AND block_id={{block}} AND total<>0', array_replace( $next, array( 'block' => $block ) ), true );
				if ( null === $actual ) {
					return false;
				}
				$normalize = static function ( array $entries ): array {
					$result = array_map(
						static function ( array $entry ): string {
							return implode( '|', $entry );
						},
						$entries
					);
					sort( $result );
					return $result;
				};
				if ( $normalize( array_values( $expected ) ) !== $normalize( $actual ) ) {
					return false;
				}
			}
			$next['after'] = $block;
			return true;
		}
		$next['after'] = 0;
		if ( 1 === $next['direction'] ) {
			$next['direction'] = 2;
		} else {
			$next['direction'] = 1;
			$pending           = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1 FOR UPDATE', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true );
			if ( null === $pending ) {
				return false;
			}
			$next['phase'] = $pending || 'refresh' === $next['phase'] ? 'reconcile' : 'ready';
			if ( 'ready' === $next['phase'] ) {
				do_action( 'ai4seo_post_state_checkpoint', 'title_before_cutover' );
			}
		}
		return true;
	}
	return false;
}

/**
 * Resume the derived index with short fenced packets and a bounded time budget.
 *
 * @param float $seconds Worker budget.
 * @return array Update-step result.
 */
function ai4seo_build_title_order_batch( float $seconds = 2 ): array {
	$lock = 'ai4seo_title_' . substr( hash( 'sha256', ai4seo_get_site_options_request_cache_scope() ), 0, 40 );
	if ( ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return ai4seo_plugin_update_step_result( 'pending', 'title_worker_busy' );
	}
	try {
		$result = ai4seo_build_title_order_batch_under_lock( $seconds, $lock );
		return ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ? $result : ai4seo_plugin_update_step_result( 'pending', 'title_worker_lock_lost' );
	} finally {
		ai4seo_release_database_advisory_lock( $lock );
	}
}

/**
 * Build while owning the site-local worker lock; packets retain the mutation fence.
 *
 * @param float  $seconds Worker budget.
 * @param string $lock Owned advisory lock.
 * @return array
 */
function ai4seo_build_title_order_batch_under_lock( float $seconds, string $lock ): array {
	$storage = ai4seo_read_post_state_storage_status();
	if ( 'legacy' === ( $storage['phase'] ?? '' ) ) {
		return ai4seo_plugin_update_step_result( 'completed' );
	}
	if ( 'active' !== ( $storage['phase'] ?? '' ) ) {
		return ai4seo_plugin_update_step_result( 'pending', 'title_storage_not_ready' );
	}
	if ( ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
		return ai4seo_plugin_update_step_result( 'pending', 'heavy_operations_disabled' );
	}
	$state = ai4seo_read_title_order_state();
	if ( null === $state ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'title_checkpoint_invalid' );
	}
	if ( ! $state && ! ai4seo_ensure_title_order_schema()['success'] ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'title_schema_unavailable' );
	}
	$deadline = microtime( true ) + max( 0.01, min( 10, $seconds ) );
	if ( in_array( $state['phase'] ?? '', array( 'ready', 'reconcile' ), true ) ) {
		$work = ai4seo_read_post_state_work( AI4SEO_TITLE_ORDER_WORK_KIND, null, 1 );
		if ( null === $work ) {
			return ai4seo_plugin_update_step_result( 'pending', 'title_packet_not_committed' );
		}
		if ( $work && reset( $work )['expires_at'] > time() ) {
			return ai4seo_plugin_update_step_result( 'pending', 'title_post_write_in_progress' );
		}
	}
	if ( 'ready' === ( $state['phase'] ?? '' ) && $storage['generation'] === $state['source_generation'] && ai4seo_title_order_signature() === $state['signature']
		&& array() === ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true ) ) {
		return ai4seo_cleanup_title_order_batch( $state, $deadline );
	}
	do {
		$ok = ai4seo_post_state_transaction(
			static function ( array $routing ) use ( $lock ): bool {
				if ( 'active' !== ( $routing['phase'] ?? '' ) || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
					return false;
				}
				$previous = ai4seo_read_title_order_state();
				if ( null === $previous ) {
					return false;
				}
				$next = $previous;
				if ( ! $next || ai4seo_title_order_signature() !== $next['signature'] ) {
					$next = ai4seo_new_title_order_state( $routing, $previous );
					if ( null === $next ) {
						return false;
					}
				} elseif ( $next['source_generation'] !== $routing['generation'] ) {
					if ( ! in_array( $next['phase'], array( 'ready', 'verify', 'reconcile', 'refresh' ), true ) ) {
						$next['source_generation'] = $routing['generation'];
					} else {
						$next['phase']             = 'refresh';
						$next['source_generation'] = $routing['generation'];
						$next['after']             = 0;
						$next['direction']         = 1;
					}
				}
				if ( ! ai4seo_build_title_order_packet( $next ) ) {
					return false;
				}
				// Finished merge levels cannot be read again after this checkpoint. Reclaim
				// them gradually so the build never retains every historical sort pass.
				if ( $next['level'] > 0 && null === ai4seo_delete_obsolete_title_order_rows( $next, false ) ) {
					return false;
				}
				++$next['revision'];
				do_action( 'ai4seo_post_state_checkpoint', 'title_before_checkpoint' );
				return ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) && ai4seo_save_title_order_state( $previous, $next );
			}
		);
		if ( ! $ok ) {
			if ( ( $GLOBALS['ai4seo_title_order_rebuild_scope'] ?? null ) === ai4seo_get_site_options_request_cache_scope() ) {
				unset( $GLOBALS['ai4seo_title_order_rebuild_scope'] );
				$restarted = ai4seo_post_state_transaction(
					static function ( array $routing ): bool {
						$previous = ai4seo_read_title_order_state();
						$next     = null === $previous ? null : ai4seo_new_title_order_state( $routing, $previous );
						return null !== $next && ai4seo_save_title_order_state( $previous, $next );
					}
				);
				if ( $restarted ) {
					return ai4seo_plugin_update_step_result( 'pending', 'title_rebalance_started' );
				}
			}
			return ai4seo_plugin_update_step_result( 'pending', 'title_packet_not_committed' );
		}
		do_action( 'ai4seo_post_state_checkpoint', 'title_after_checkpoint' );
		$state = ai4seo_read_title_order_state();
		if ( 'ready' === ( $state['phase'] ?? '' ) ) {
			do_action( 'ai4seo_post_state_checkpoint', 'title_after_cutover' );
			$pending = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true );
			if ( array() === $pending ) {
				return ai4seo_cleanup_title_order_batch( $state, $deadline );
			}
		}
	} while ( microtime( true ) < $deadline );
	return ai4seo_plugin_update_step_result( 'pending', 'title_build_in_progress' );
}

/**
 * Allocate a generation above any retained rows, including after a cleared checkpoint.
 *
 * @param array $routing Canonical storage state.
 * @param array $previous Previous derived checkpoint.
 * @return array|null
 */
function ai4seo_new_title_order_state( array $routing, array $previous ): ?array {
	$generation = $previous['generation'] ?? 0;
	foreach ( array( 'title_rows', 'title_blocks', 'title_counts' ) as $table ) {
		$maximum = ai4seo_post_state_query( 'SELECT MAX(generation) AS generation FROM {{' . $table . '}}', array(), true );
		if ( null === $maximum ) {
			return null;
		}
		$generation = max( $generation, (int) $maximum[0]['generation'] );
	}
	return array(
		'version'           => AI4SEO_TITLE_ORDER_VERSION,
		'generation'        => $generation + 1,
		'source_generation' => $routing['generation'],
		'phase'             => 'scan',
		'after'             => 0,
		'runs'              => 0,
		'level'             => 0,
		'direction'         => 1,
		'pair'              => 0,
		'left'              => 0,
		'right'             => 0,
		'out'               => 0,
		'block'             => 0,
		'revision'          => 0,
		'signature'         => ai4seo_title_order_signature(),
	);
}

/**
 * Reclaim old generations/merge runs using indexed ranges and bounded deletes.
 *
 * @param array $state Published generation.
 * @param float $deadline Worker deadline.
 * @return array
 */
function ai4seo_cleanup_title_order_batch( array $state, float $deadline ): array {
	foreach ( array( true, false ) as $old_generation ) {
		do {
			$deleted = ai4seo_delete_obsolete_title_order_rows( $state, $old_generation );
			if ( null === $deleted ) {
				return ai4seo_plugin_update_step_result( 'pending', 'title_cleanup_failed' );
			}
			if ( $deleted && microtime( true ) >= $deadline ) {
				return ai4seo_plugin_update_step_result( 'pending', 'title_cleanup_in_progress' );
			}
		} while ( $deleted );
	}
	$queries = array(
		'DELETE FROM {{title_blocks}} WHERE generation<{{generation}} LIMIT {{limit}}',
		'DELETE FROM {{title_counts}} WHERE generation<{{generation}} LIMIT {{limit}}',
	);
	foreach ( $queries as $query ) {
		do {
			$deleted = ai4seo_post_state_query( $query, $state + array( 'limit' => ai4seo_post_state_batch_size() ) );
			if ( null === $deleted ) {
				return ai4seo_plugin_update_step_result( 'pending', 'title_cleanup_failed' );
			}
			if ( $deleted && microtime( true ) >= $deadline ) {
				return ai4seo_plugin_update_step_result( 'pending', 'title_cleanup_in_progress' );
			}
		} while ( $deleted );
	}
	return ai4seo_plugin_update_step_result( 'completed' );
}

/**
 * Select obsolete primary keys through the covering cleanup index before deletion.
 * MariaDB may otherwise scan the current generation even for a zero-row DELETE.
 *
 * @param array $state Published/current merge generation.
 * @param bool  $old_generation Select older generations instead of finished levels.
 * @return int|null
 */
function ai4seo_delete_obsolete_title_order_rows( array $state, bool $old_generation ): ?int {
	$where = $old_generation ? 'generation<{{generation}}' : 'generation={{generation}} AND level<{{level}}';
	$rows  = ai4seo_post_state_query( 'SELECT generation,direction,level,post_id FROM {{title_rows}} FORCE INDEX (cleanup) WHERE ' . $where . ' LIMIT {{limit}}', $state + array( 'limit' => ai4seo_post_state_batch_size() ), true );
	if ( null === $rows ) {
		return null;
	}
	$groups = array();
	foreach ( $rows as $row ) {
		$key = $row['generation'] . ':' . $row['direction'] . ':' . $row['level'];
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array(
				'generation' => (int) $row['generation'],
				'direction'  => (int) $row['direction'],
				'level'      => (int) $row['level'],
				'ids'        => array(),
			);
		}
		$groups[ $key ]['ids'][] = (int) $row['post_id'];
	}
	$total = 0;
	foreach ( $groups as $group ) {
		$deleted = ai4seo_post_state_query( 'DELETE FROM {{title_rows}} WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND post_id IN ({{ids}})', $group );
		if ( null === $deleted ) {
			return null;
		}
		$total += $deleted;
	}
	return $total;
}

/**
 * Certify only filter and capability profiles represented by the shared counters.
 *
 * @param array $args Existing list arguments.
 * @return array|null Null uses the exact existing compatibility reader.
 */
function ai4seo_resolve_title_order_profile( array $args ): ?array {
	foreach ( array( 'filter_text', 'filter_language', 'disabled_taxonomy_terms', 'disabled_wpml_language_codes', 'author_not_in', 'post_mime_types' ) as $key ) {
		if ( ! empty( $args[ $key ] ) ) {
			return null;
		}
	}
	if ( isset( $args['scoped_post_ids'] ) || null === ai4seo_post_state_list_cache_key( $args ) || ! ai4seo_can_use_plugin_content() ) {
		return null;
	}
	$types    = array_values( (array) ( $args['post_types'] ?? array( $args['post_type'] ?? '' ) ) );
	$statuses = array_values( (array) ( $args['post_status'] ?? array( 'publish', 'future' ) ) );
	if ( ! $types || ! $statuses || array_diff( $statuses, array( 'publish', 'future', 'draft', 'pending', 'private' ) ) ) {
		return null;
	}
	foreach ( $types as $type ) {
		$object = get_post_type_object( $type );
		if ( ! $object || ! $object->map_meta_cap || in_array( $type, array( 'attachment', AI4SEO_TAXONOMY_PROXY_POST_TYPE ), true ) ) {
			return null;
		}
		foreach ( array( 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts' ) as $cap ) {
			if ( empty( $object->cap->$cap ) || ! current_user_can( $object->cap->$cap ) ) {
				return null;
			}
		}
	}
	$date    = ai4seo_get_bulk_generation_date_filter_state( $args['new_or_existing_filter'] ?? 'both', $args['new_or_existing_filter_reference_timestamp'] ?? 0 );
	$waiting = ! empty( $args['is_bulk_generation_activated'] ) && ! empty( $args['should_auto_queue_bulk_generation_entries'] ) && ! empty( $args['has_enough_credits'] ) && $date['is_valid'];
	if ( $waiting && 'both' !== $date['filter'] ) {
		return null;
	}
	return array(
		'types'    => $types,
		'statuses' => $statuses,
		'waiting'  => $waiting,
		'context'  => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === ( $args['content_context'] ?? '' ) ? 2 : 1,
	);
}

/**
 * Read small block aggregates, never post identities.
 *
 * @param array $state Ready generation.
 * @param array $profile Certified view.
 * @param int   $direction Direction.
 * @return array|null
 */
function ai4seo_read_title_order_counts( array $state, array $profile, int $direction ): ?array {
	$rows = ai4seo_post_state_query(
		'SELECT bucket,SUM(total) AS total FROM {{title_counts}} FORCE INDEX (counts) WHERE generation={{generation}} AND direction={{direction}} AND context={{context}} AND post_type IN ({{types}}) AND post_status IN ({{statuses}}) GROUP BY bucket',
		array(
			'generation' => $state['generation'],
			'direction'  => $direction,
		) + $profile,
		true
	);
	if ( null === $rows ) {
		return null;
	}
	$counts = array_fill_keys( array_keys( AI4SEO_TITLE_ORDER_BITS ), 0 );
	foreach ( $rows as $row ) {
		if ( ! isset( $counts[ $row['bucket'] ] ) || (int) $row['total'] < 0 ) {
			return null;
		}
		$counts[ $row['bucket'] ] = (int) $row['total'];
	}
	if ( ! $profile['waiting'] ) {
		$counts['waiting_to_queue'] = 0;
	}
	// Older projections also record the review candidate pool. It is not an
	// unverified count: successful authoritative reads have verified exclusions.
	// Normalize at read time so existing indexes need no rebuild.
	$counts['unverified'] = 0;
	return $counts;
}

/**
 * Resolve arbitrary numeric pages through block counts and bounded row reads.
 *
 * @param array $args List arguments.
 * @param array $sort Normalized sort.
 * @param array $result Existing result envelope.
 * @param bool  $retry Allow one retry after an atomic counter change.
 * @return array|null Null requests the exact existing fallback.
 */
function ai4seo_read_title_order_page( array $args, array $sort, array $result, bool $retry = true ): ?array {
	$original = $result;
	if ( 'title' !== $sort['orderby'] && empty( $args['_counts_only'] ) ) {
		return null;
	}
	$profile = ai4seo_resolve_title_order_profile( $args );
	$state   = ai4seo_read_title_order_state();
	$storage = ai4seo_read_post_state_storage_status();
	if ( null === $profile || 'ready' !== ( $state['phase'] ?? '' ) || ( $storage['generation'] ?? 0 ) !== $state['source_generation'] || ai4seo_title_order_signature() !== $state['signature'] ) {
		return null;
	}
	$pending = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true );
	if ( array() !== $pending ) {
		return null;
	}
	$direction = 'asc' === $sort['order'] ? 1 : 2;
	$counts    = ai4seo_read_title_order_counts( $state, $profile, $direction );
	$status    = sanitize_key( $args['filter_status'] ?? 'all' );
	if ( null === $counts || ! isset( $counts[ $status ] ) ) {
		return null;
	}
	$result['status_counts'] = array_intersect_key( $counts, $result['status_counts'] );
	$result['total_items']   = $counts[ $status ];
	$per_page                = max( 1, min( ai4seo_post_state_batch_size(), (int) ( $args['per_page'] ?? 20 ) ) );
	$result['total_pages']   = max( 1, (int) ceil( $result['total_items'] / $per_page ) );
	$result['current_page']  = min( $result['current_page'], $result['total_pages'] );
	$offset                  = ( $result['current_page'] - 1 ) * $per_page;
	if ( empty( $args['_counts_only'] ) && $result['total_items'] > 0 ) {
		$key       = array(
			'generation' => $state['generation'],
			'direction'  => $direction,
			'level'      => $state['level'],
			'bucket'     => $status,
		) + $profile;
		$directory = ai4seo_post_state_query( 'SELECT block_id,SUM(total) AS total FROM {{title_counts}} FORCE INDEX (counts) WHERE generation={{generation}} AND direction={{direction}} AND context={{context}} AND post_type IN ({{types}}) AND post_status IN ({{statuses}}) AND bucket={{bucket}} GROUP BY block_id HAVING SUM(total)>0 ORDER BY block_id', $key, true );
		if ( null === $directory ) {
			return null;
		}
		foreach ( $directory as $block ) {
			if ( $offset >= (int) $block['total'] ) {
				$offset -= (int) $block['total'];
				continue;
			}
			$column = 1 === $profile['context'] ? 'metadata_mask' : 'media_mask';
			$rows   = ai4seo_post_state_query(
				'SELECT post_id,post_title FROM {{title_rows}} FORCE INDEX (page) WHERE generation={{generation}} AND direction={{direction}} AND level={{level}} AND run_id={{block}} AND post_type IN ({{types}}) AND post_status IN ({{statuses}}) AND (' . $column . ' & {{bit}})<>0 ORDER BY position LIMIT {{offset}},{{limit}}',
				$key + array(
					'block'  => (int) $block['block_id'],
					'bit'    => AI4SEO_TITLE_ORDER_BITS[ $status ],
					'offset' => $offset,
					'limit'  => $per_page - count( $result['post_ids'] ),
				),
				true
			);
			if ( null === $rows ) {
				return null;
			}
			foreach ( $rows as $row ) {
				$id                              = (int) $row['post_id'];
				$result['post_ids'][]            = $id;
				$result['sort_value_map'][ $id ] = $row['post_title'];
			}
			$offset = 0;
			if ( count( $result['post_ids'] ) === $per_page ) {
				break;
			}
		}
		if ( count( $result['post_ids'] ) !== min( $per_page, $result['total_items'] - ( $result['current_page'] - 1 ) * $per_page ) ) {
			return null;
		}
		if ( ! $retry ) {
			// Another connection may have changed an already-primed post before repair.
			foreach ( $result['post_ids'] as $id ) {
				wp_cache_delete( $id, 'posts' );
			}
		}
		_prime_post_caches( $result['post_ids'], false, false );
		if ( ai4seo_filter_editable_post_ids( $result['post_ids'] ) !== $result['post_ids'] ) {
			return null;
		}
	}
	do_action( 'ai4seo_post_state_checkpoint', 'title_after_page_read' );
	$current = ai4seo_read_title_order_state();
	$pending = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_TITLE_ORDER_WORK_KIND ), true );
	if ( $current !== $state || array() !== $pending ) {
		// A completed status transaction needs a fresh bounded read, not a full inventory scan.
		if ( $retry && array() === $pending && 'ready' === ( $current['phase'] ?? '' )
			&& $current['generation'] === $state['generation'] && $current['source_generation'] === $state['source_generation'] && $current['signature'] === $state['signature'] ) {
			return ai4seo_read_title_order_page( $args, $sort, $original, false );
		}
		return null;
	}
	return $result;
}

/** Resume background indexing without attaching full work to a list request. */
function ai4seo_title_order_cron_job(): void {
	$result = ai4seo_build_title_order_batch();
	if ( 'completed' !== $result['status'] && 'heavy_operations_disabled' !== $result['reason'] ) {
		ai4seo_schedule_background_job_checked( AI4SEO_TITLE_ORDER_CRON_HOOK, 20 );
	}
}
