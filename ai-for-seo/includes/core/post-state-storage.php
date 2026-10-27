<?php
/**
 * Indexed, site-local status memberships, counters and token-owned work.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate the compact storage checkpoint without repairing unknown state.
 *
 * @param mixed $state Persisted state.
 * @return bool
 */
function ai4seo_validate_post_state_storage_status( $state ): bool {
	if ( array() === $state ) {
		return true;
	}
	if ( ! is_array( $state ) || array_diff( array_keys( $state ), array( 'version', 'phase', 'generation', 'source', 'offset', 'row', 'digest', 'manifest', 'analysis', 'repair', 'error', 'export', 'work_revision', 'export_work_revision', 'reader', 'building_generation', 'retired_generation', 'published_progress', 'live_invalidated' ) ) ) {
		return false;
	}
	if ( isset( $state['reader'] ) && ( ! in_array( $state['phase'] ?? '', array( 'import', 'verify' ), true ) || ! AI4SEO_Post_State_Reader::validate_cursor( $state['reader'] ) ) ) {
		return false;
	}
	if ( isset( $state['export'] ) && ! ai4seo_validate_post_state_export_checkpoint( $state['export'] ) ) {
		return false;
	}
	foreach ( array( 'work_revision', 'export_work_revision', 'building_generation', 'retired_generation' ) as $key ) {
		if ( isset( $state[ $key ] ) && false === ai4seo_normalize_option_post_id( $state[ $key ], true ) ) {
			return false;
		}
	}
	foreach ( array( 'version', 'generation', 'source', 'offset', 'row' ) as $key ) {
		if ( ! isset( $state[ $key ] ) || false === ai4seo_normalize_option_post_id( $state[ $key ], true ) ) {
			return false;
		}
	}
	if ( AI4SEO_POST_STATE_STORAGE_VERSION !== (int) $state['version'] || (int) $state['generation'] < 1 || (int) $state['source'] > 24
		|| ! in_array( $state['phase'] ?? '', array( 'draining', 'import', 'verify', 'publish', 'active', 'export_wait', 'export', 'legacy' ), true )
		|| ! is_bool( $state['repair'] ?? null ) || ! is_string( $state['error'] ?? null ) || strlen( $state['error'] ) > 128
		|| ! is_array( $state['manifest'] ?? null ) || count( $state['manifest'] ) > 24 ) {
		return false;
	}
	foreach ( array( 'building_generation', 'retired_generation' ) as $key ) {
		if ( array_key_exists( $key, $state ) && ( false === ai4seo_normalize_option_post_id( $state[ $key ] )
			|| ( 'building_generation' === $key ? (int) $state[ $key ] <= (int) $state['generation'] : (int) $state[ $key ] >= (int) $state['generation'] ) ) ) {
			return false;
		}
	}
	if ( array_key_exists( 'live_invalidated', $state ) && ! is_bool( $state['live_invalidated'] ) ) {
		return false;
	}
	if ( array_key_exists( 'published_progress', $state ) && ( ! is_array( $state['published_progress'] )
		|| 2 !== (int) ( $state['published_progress']['version'] ?? 0 )
		|| ! empty( $state['published_progress']['failure'] )
		|| ! ai4seo_validate_environmental_variable_value( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS, $state['published_progress'] ) ) ) {
		return false;
	}
	if ( isset( $state['building_generation'] ) && ! isset( $state['published_progress'] ) ) {
		return false;
	}
	foreach ( array_merge( array( $state['digest'] ?? null, $state['analysis'] ?? null ), array_values( $state['manifest'] ) ) as $digest ) {
		if ( ! is_string( $digest ) || ( '' !== $digest && 1 !== preg_match( '/^[a-f0-9]{64}$/D', $digest ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Read routing authoritatively, including inside an already acquired write fence.
 *
 * Null is a read/validation failure, never permission to return to old options.
 *
 * @return array|null Empty means an installation that still uses legacy storage.
 */
function ai4seo_read_post_state_storage_status(): ?array {
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	if ( null === $snapshot || ( $snapshot['exists'] && ! is_array( $snapshot['value'] ) ) ) {
		return null;
	}
	$state = $snapshot['value'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE ] ?? array();
	if ( ! ai4seo_validate_post_state_storage_status( $state ) ) {
		return null;
	}
	if ( $state ) {
		foreach ( array( 'version', 'generation', 'source', 'offset', 'row' ) as $key ) {
			$state[ $key ] = (int) $state[ $key ];
		}
		foreach ( array( 'work_revision', 'export_work_revision', 'building_generation', 'retired_generation' ) as $key ) {
			if ( isset( $state[ $key ] ) ) {
				$state[ $key ] = (int) $state[ $key ];
			}
		}
		if ( isset( $state['reader'] ) ) {
			$state['reader'] = AI4SEO_Post_State_Reader::normalize_cursor( $state['reader'] );
		}
		if ( ! empty( $state['export'] ) ) {
			foreach ( array( 'version', 'source', 'group', 'cursor', 'count', 'bytes', 'revision' ) as $key ) {
				$state['export'][ $key ] = (int) $state['export'][ $key ];
			}
		}
	}
	return $state;
}

/**
 * Whether table readers must be used (including a paused export).
 *
 * @return bool Whether table readers must be used (including a paused export).
 */
function ai4seo_uses_post_state_tables(): bool {
	$state = ai4seo_read_post_state_storage_status();
	return null === $state || in_array( $state['phase'] ?? '', array( 'active', 'export_wait', 'export' ), true );
}

/**
 * Whether new claims, queue actions and scans must wait.
 *
 * @return bool Whether new claims, queue actions and scans must wait.
 */
function ai4seo_is_post_state_storage_paused(): bool {
	$state = ai4seo_read_post_state_storage_status();
	return null === $state || ( $state && ! in_array( $state['phase'], array( 'active', 'legacy' ), true ) );
}

/**
 * Maximum identities per statement/transaction.
 *
 * @return int Maximum identities per statement/transaction.
 */
function ai4seo_post_state_batch_size(): int {
	return max( 1, min( 1000, ai4seo_get_database_chunk_size() ) );
}

/**
 * Compile status SQL through the closed identifier registry.
 *
 * @param string $template SQL with named tokens.
 * @param array  $values Scalar/list data, never SQL fragments.
 * @return string|false
 */
function ai4seo_post_state_sql( string $template, array $values = array() ) {
	$bindings = array();
	$tables   = array(
		'memberships'  => 'post_memberships',
		'totals'       => 'post_totals',
		'work'         => 'post_work',
		'title_rows'   => 'post_title_rows',
		'title_blocks' => 'post_title_blocks',
		'title_counts' => 'post_title_counts',
		'posts'        => 'posts',
		'options'      => 'options',
		'meta'         => 'postmeta',
	);
	$invalid  = false;
	// The shared compiler requires a distinct binding per occurrence, including identifiers.
	$template = preg_replace_callback(
		'/\{\{([a-z][a-z0-9_]*)}}/',
		static function ( array $token_match ) use ( $tables, $values, &$bindings, &$invalid ): string {
			$token = $token_match[1];
			$key   = 'binding_' . count( $bindings );
			if ( isset( $tables[ $token ] ) ) {
				$bindings[ $key ] = ai4seo_database_identifier_binding( 'table.' . $tables[ $token ] );
			} elseif ( array_key_exists( $token, $values ) ) {
				$value            = $values[ $token ];
				$sample           = is_array( $value ) ? reset( $value ) : $value;
				$format           = is_int( $sample ) ? '%d' : '%s';
				$bindings[ $key ] = is_array( $value ) ? ai4seo_database_list_binding( $format, array_values( $value ) ) : ai4seo_database_scalar_binding( $format, $value );
			} else {
				$invalid = true;
			}
			return '{{' . $key . '}}';
		},
		$template
	);
	if ( $invalid || null === $template ) {
		return false;
	}
	return ai4seo_prepare_database_query( $template, $bindings );
}

/**
 * Execute a typed statement; null denotes an error, including an invalid query.
 *
 * @param string $template Typed SQL.
 * @param array  $values Bound data.
 * @param bool   $read Whether rows are expected.
 * @return array|int|null
 */
function ai4seo_post_state_query( string $template, array $values = array(), bool $read = false ) {
	global $wpdb;
	$sql = ai4seo_post_state_sql( $template, $values );
	if ( false === $sql ) {
		return null;
	}
	$wpdb->last_error = '';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Closed typed compiler; authoritative transactional state cannot use an options cache.
	$result = $read ? $wpdb->get_results( $sql, ARRAY_A ) : $wpdb->query( $sql );
	return $wpdb->last_error || false === $result || null === $result ? null : $result;
}

/**
 * Build ordered value tuples for the shared typed SQL compiler.
 *
 * Callers retain responsibility for row limits and packet sizing. Preserve their
 * row keys in placeholder names while normalizing each row's column positions.
 *
 * @param array $rows Ordered rows containing column values.
 * @return array{tuples:string,values:array} SQL tuples and their named bindings.
 */
function ai4seo_build_post_state_insert_payload( array $rows ): array {
	$values = array();
	$tuples = array();
	foreach ( $rows as $index => $row ) {
		$tokens = array();
		foreach ( array_values( $row ) as $column => $value ) {
			$key            = 'v' . $index . '_' . $column;
			$tokens[]       = '{{' . $key . '}}';
			$values[ $key ] = $value;
		}
		$tuples[] = '(' . implode( ',', $tokens ) . ')';
	}
	return array(
		'tuples' => implode( ',', $tuples ),
		'values' => $values,
	);
}

/**
 * Insert bounded rows without changing the sequence of existing memberships.
 *
 * @param string $table Registry token.
 * @param array  $rows Homogeneous rows, in insertion order.
 * @param string $mode Closed write mode.
 * @return bool
 */
function ai4seo_post_state_insert_rows( string $table, array $rows, string $mode = 'membership' ): bool {
	if ( ! $rows ) {
		return true;
	}
	$definitions = array(
		'membership'  => array( 'memberships', 'area,generation,bucket,post_id,post_type', 'post_type=VALUES(post_type)' ),
		'work'        => array( 'work', 'kind,post_id,token,expires_at,payload', 'token=VALUES(token),expires_at=VALUES(expires_at),payload=VALUES(payload)' ),
		'import_work' => array( 'work', 'kind,post_id,token,expires_at,payload', 'post_id=VALUES(post_id)' ),
	);
	if ( ! isset( $definitions[ $mode ] ) || $table !== $definitions[ $mode ][0] || count( $rows ) > ai4seo_post_state_batch_size() ) {
		return false;
	}
	$payload = ai4seo_build_post_state_insert_payload( array_values( $rows ) );
	return null !== ai4seo_post_state_query(
		'INSERT INTO {{' . $table . '}} (' . $definitions[ $mode ][1] . ') VALUES ' . $payload['tuples'] . ' ON DUPLICATE KEY UPDATE ' . $definitions[ $mode ][2],
		$payload['values']
	);
}

/**
 * Create and verify the schema before changing storage routing.
 *
 * @return array Success and a diagnostic reason, never credentials or SQL payloads.
 */
function ai4seo_ensure_post_state_schema(): array {
	$definitions   = array(
		'memberships' => 'membership_id bigint unsigned NOT NULL AUTO_INCREMENT, area tinyint unsigned NOT NULL, generation bigint unsigned NOT NULL, bucket tinyint unsigned NOT NULL, post_id bigint unsigned NOT NULL, post_type varchar(20) NOT NULL,
			PRIMARY KEY (membership_id), UNIQUE KEY membership (area,generation,bucket,post_id),
			KEY post_lookup (post_id,area,generation,bucket), KEY queue_page (area,generation,bucket,membership_id),
			KEY type_page (area,generation,bucket,post_type,membership_id), KEY generations (generation,membership_id)',
		'totals'      => 'area tinyint unsigned NOT NULL, generation bigint unsigned NOT NULL, bucket tinyint unsigned NOT NULL, post_type varchar(20) NOT NULL, total bigint NOT NULL,
			PRIMARY KEY (area,generation,bucket,post_type)',
		'work'        => 'work_id bigint unsigned NOT NULL AUTO_INCREMENT, kind tinyint unsigned NOT NULL, post_id bigint unsigned NOT NULL, token varchar(128) NOT NULL, expires_at bigint unsigned NOT NULL, payload text NOT NULL,
			PRIMARY KEY (work_id), UNIQUE KEY identity_key (kind,post_id), KEY work_page (kind,work_id), KEY expiry (kind,expires_at)',
	);
	$expected_keys = array(
		'memberships' => array(
			'PRIMARY'     => 'membership_id',
			'membership'  => 'area,generation,bucket,post_id',
			'post_lookup' => 'post_id,area,generation,bucket',
			'queue_page'  => 'area,generation,bucket,membership_id',
			'type_page'   => 'area,generation,bucket,post_type,membership_id',
			'generations' => 'generation,membership_id',
		),
		'totals'      => array( 'PRIMARY' => 'area,generation,bucket,post_type' ),
		'work'        => array(
			'PRIMARY'      => 'work_id',
			'identity_key' => 'kind,post_id',
			'work_page'    => 'kind,work_id',
			'expiry'       => 'kind,expires_at',
		),
	);
	return ai4seo_ensure_post_state_schema_tables( $definitions, $expected_keys );
}

/**
 * Share strict schema/privilege verification with derived title storage.
 *
 * @param array $definitions Closed table definitions.
 * @param array $expected_keys Required index columns.
 * @return array
 */
function ai4seo_ensure_post_state_schema_tables( array $definitions, array $expected_keys ): array {
	global $wpdb;
	$option_engine = ai4seo_post_state_query( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME={{name}}', array( 'name' => $wpdb->options ), true );
	if ( ! $option_engine || 'InnoDB' !== $option_engine[0]['ENGINE'] ) {
		return array(
			'success' => false,
			'reason'  => 'options_require_innodb',
		);
	}
	foreach ( $definitions as $table => $definition ) {
		if ( null === ai4seo_post_state_query( 'CREATE TABLE IF NOT EXISTS {{' . $table . '}} (' . $definition . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin' ) ) {
			return array(
				'success' => false,
				'reason'  => 'schema_create_failed',
			);
		}
		$name    = $wpdb->prefix . 'ai4seo_post_' . $table;
		$engine  = ai4seo_post_state_query( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME={{name}}', array( 'name' => $name ), true );
		$keys    = ai4seo_post_state_query( 'SHOW INDEX FROM {{' . $table . '}}', array(), true );
		$columns = ai4seo_post_state_query( 'SHOW COLUMNS FROM {{' . $table . '}}', array(), true );
		if ( null === $engine || 'InnoDB' !== ( $engine[0]['ENGINE'] ?? '' ) || null === $keys || null === $columns ) {
			return array(
				'success' => false,
				'reason'  => 'schema_engine_or_read_failed',
			);
		}
		$actual_keys = array();
		foreach ( $keys as $key ) {
			$actual_keys[ $key['Key_name'] ][ (int) $key['Seq_in_index'] ] = $key['Column_name'];
			if ( in_array( $key['Key_name'], array( 'PRIMARY', 'membership', 'identity_key' ), true ) && '0' !== (string) $key['Non_unique'] ) {
				return array(
					'success' => false,
					'reason'  => 'schema_unique_key_invalid',
				);
			}
		}
		foreach ( $expected_keys[ $table ] as $key => $fields ) {
			$actual = $actual_keys[ $key ] ?? array();
			ksort( $actual );
			if ( implode( ',', $actual ) !== $fields ) {
				return array(
					'success' => false,
					'reason'  => 'schema_index_invalid',
				);
			}
		}
		$expected_columns = array();
		preg_match_all( '/(?:^|,\s*)([a-z_]+) (bigint unsigned|bigint|tinyint unsigned|varchar\([0-9]+\)|text) NOT NULL( AUTO_INCREMENT)?/', $definition, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$expected_columns[ $match[1] ] = array( $match[2], empty( $match[3] ) ? '' : 'auto_increment' );
		}
		if ( count( $columns ) !== count( $expected_columns ) ) {
			return array(
				'success' => false,
				'reason'  => 'schema_columns_invalid',
			);
		}
		foreach ( $columns as $column ) {
			$type = preg_replace( '/(bigint|tinyint)\([0-9]+\)/', '$1', strtolower( $column['Type'] ) );
			if ( 'NO' !== $column['Null'] || ( $expected_columns[ $column['Field'] ] ?? null ) !== array( $type, $column['Extra'] ) ) {
				return array(
					'success' => false,
					'reason'  => 'schema_column_invalid',
				);
			}
		}
		// Verify write privileges before pausing SOOZ, without retaining probe data.
		if ( null === ai4seo_post_state_query( 'UPDATE {{' . $table . '}} SET ' . ( in_array( $table, array( 'totals', 'title_counts' ), true ) ? 'total=total' : 'post_id=post_id' ) . ' WHERE 1=0' )
			|| null === ai4seo_post_state_query( 'INSERT INTO {{' . $table . '}} SELECT * FROM {{' . $table . '}} WHERE 1=0' )
			|| null === ai4seo_post_state_query( 'DELETE FROM {{' . $table . '}} WHERE 1=0' ) ) {
			return array(
				'success' => false,
				'reason'  => 'schema_write_privileges_missing',
			);
		}
	}
	return array(
		'success' => true,
		'reason'  => '',
	);
}

/**
 * Run a short status transaction under the existing site mutation fence.
 *
 * The semaphore row is locked until COMMIT; a stale takeover cannot interleave.
 * A reconnect or a lost fence rolls back and never reports a successful write.
 *
 * @param callable $callback Receives the current validated routing state.
 * @param string   $analysis_lock Optional analysis fence that must still be owned at commit.
 * @return bool True only after a verified commit.
 */
function ai4seo_post_state_transaction( callable $callback, string $analysis_lock = '' ): bool {
	global $wpdb;
	$scope = (string) $wpdb->options;
	if ( isset( $GLOBALS['ai4seo_post_state_transaction'] ) ) {
		return false;
	}
	$name = ai4seo_get_post_id_option_transition_semaphore_name();
	$owns = null !== ai4seo_get_held_semaphore_record( ai4seo_get_semaphore_option_key( $name ) );
	if ( ! $owns && ! ai4seo_acquire_semaphore( $name ) ) {
		return false;
	}
	$committed = false;
	$released  = true;
	try {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control cannot be cached.
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return false;
		}
		$GLOBALS['ai4seo_post_state_transaction'] = $scope;
		$lock                                     = ai4seo_post_state_query( 'SELECT option_id,CONNECTION_ID() AS connection_id FROM {{options}} WHERE option_name={{name}} FOR UPDATE', array( 'name' => ai4seo_get_semaphore_option_key( $name ) ), true );
		$state                                    = ai4seo_read_post_state_storage_status();
		if ( ! $lock || null === $state || ! ai4seo_renew_post_id_option_transition_semaphore() || true !== $callback( $state ) ) {
			return false;
		}
		do_action( 'ai4seo_post_state_checkpoint', 'before_commit' );
		$connection = ai4seo_post_state_query( 'SELECT CONNECTION_ID() AS connection_id FROM {{options}} WHERE option_name={{name}}', array( 'name' => ai4seo_get_semaphore_option_key( $name ) ), true );
		if ( $scope !== (string) $wpdb->options || ! $connection || $connection[0]['connection_id'] !== $lock[0]['connection_id'] || ! ai4seo_renew_post_id_option_transition_semaphore()
			|| ( '' !== $analysis_lock && ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $analysis_lock ) ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Publish only the atomic membership/counter/work change.
		$committed = false !== $wpdb->query( 'COMMIT' ) && ! $wpdb->last_error;
	} finally {
		if ( ! $committed ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Undo all partial status writes.
			$wpdb->query( 'ROLLBACK' );
			ai4seo_reconcile_environmental_variable_caches_from_storage();
		}
		unset( $GLOBALS['ai4seo_post_state_transaction'] );
		ai4seo_reset_generation_status_summary_request_cache();
		if ( ! $owns ) {
			$released = ai4seo_release_semaphore( $name );
		}
	}
	if ( $committed ) {
		do_action( 'ai4seo_post_state_checkpoint', 'after_commit' );
		ai4seo_bump_content_type_list_cache_version();
	}
	return $committed && $released;
}

/**
 * Resolve an immutable membership key for this generation.
 *
 * @param string $option Status name.
 * @param int    $area One for sources, two for the filtered analysis.
 * @param array  $state Current routing checkpoint.
 * @return array|null
 */
function ai4seo_post_state_key( string $option, int $area, array $state ): ?array {
	if ( ! isset( AI4SEO_POST_STATE_BUCKETS[ $option ] ) || ! in_array( $area, array( 1, 2 ), true ) ) {
		return null;
	}
	return array(
		'area'       => $area,
		'generation' => 2 === $area || in_array( $option, AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS, true ) ? (int) ( $state['generation'] ?? 1 ) : 0,
		'bucket'     => AI4SEO_POST_STATE_BUCKETS[ $option ],
	);
}

/**
 * Read memberships for only the supplied identities.
 *
 * @param array $post_ids Bounded candidate IDs (larger inputs are chunked).
 * @param array $options Requested statuses.
 * @param int   $area Source or analysis.
 * @return array|null Option => ID => post type; null is not an empty result.
 */
function ai4seo_read_post_state_for_ids( array $post_ids, array $options = AI4SEO_ALL_POST_ID_OPTIONS, int $area = 1 ): ?array {
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return null;
	}
	$result   = array_fill_keys( $options, array() );
	$post_ids = ai4seo_normalize_option_post_id_collection( $post_ids );
	if ( ! $post_ids && null === ai4seo_post_state_query( 'SELECT post_id FROM {{memberships}} LIMIT 0', array(), true ) ) {
		return null;
	}
	foreach ( $options as $option ) {
		$key = ai4seo_post_state_key( $option, $area, $state );
		if ( null === $key ) {
			return null;
		}
		foreach ( array_chunk( $post_ids, ai4seo_post_state_batch_size() ) as $ids ) {
			$rows = ai4seo_post_state_query( 'SELECT post_id,post_type FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_id IN ({{ids}})', $key + array( 'ids' => $ids ), true );
			if ( null === $rows ) {
				return null;
			}
			foreach ( $rows as $row ) {
				$result[ $option ][ (int) $row['post_id'] ] = $row['post_type'];
			}
		}
	}
	return $result;
}

/**
 * Read one insertion-ordered page; the continuation is the final membership_id.
 *
 * @param string   $option Status name.
 * @param int      $after Last row, not an OFFSET.
 * @param int      $limit Requested bound.
 * @param int      $area Source or analysis.
 * @param string   $post_type Optional exact type.
 * @param int|null $through Optional inclusive membership boundary; null leaves the page unbounded.
 * @return array|null Rows or an explicit failure.
 */
function ai4seo_read_post_state_page( string $option, int $after = 0, int $limit = 1000, int $area = 1, string $post_type = '', ?int $through = null ): ?array {
	$state = ai4seo_read_post_state_storage_status();
	$key   = null === $state ? null : ai4seo_post_state_key( $option, $area, $state );
	if ( null === $key || $after < 0 || $limit < 1 || ( null !== $through && $through < 0 ) ) {
		return null;
	}
	$values   = $key + array(
		'after' => $after,
		'limit' => min( $limit, ai4seo_post_state_batch_size() ),
	);
	$type_sql = '';
	if ( '' !== $post_type ) {
		$type_sql       = ' AND post_type={{type}}';
		$values['type'] = $post_type;
	}
	if ( null !== $through ) {
		$type_sql         .= ' AND membership_id<={{through}}';
		$values['through'] = $through;
	}
	return ai4seo_post_state_query( 'SELECT membership_id,post_id,post_type FROM {{memberships}} FORCE INDEX (' . ( '' === $post_type ? 'queue_page' : 'type_page' ) . ') WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND membership_id>{{after}}' . $type_sql . ' ORDER BY membership_id LIMIT {{limit}}', $values, true );
}

/**
 * Read compact totals without materializing or hashing any membership collection.
 *
 * @param int $area Source or analysis.
 * @return array|null Public option/type/count shape, or failure.
 */
function ai4seo_read_post_state_totals( int $area = 2 ): ?array {
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return null;
	}
	$rows = ai4seo_post_state_query(
		'SELECT bucket,post_type,total FROM {{totals}} WHERE area={{area}} AND generation IN (0,{{generation}}) AND total<>0',
		array(
			'area'       => $area,
			'generation' => (int) ( $state['generation'] ?? 1 ),
		),
		true
	);
	if ( null === $rows ) {
		return null;
	}
	$result  = array();
	$options = array_flip( AI4SEO_POST_STATE_BUCKETS );
	foreach ( $rows as $row ) {
		if ( ! isset( $options[ (int) $row['bucket'] ] ) || (int) $row['total'] < 0 ) {
			return null;
		}
		$result[ $options[ (int) $row['bucket'] ] ][ $row['post_type'] ] = (int) $row['total'];
	}
	return $result;
}

/**
 * Explicit diagnostic export. Regular consumers use pages or counters instead.
 *
 * @param string $option Requested source.
 * @param int    $area Source or analysis.
 * @return array|null IDs in stable queue order.
 */
function ai4seo_export_post_state_ids( string $option, int $area = 1 ): ?array {
	$after = 0;
	$ids   = array();
	do {
		$page = ai4seo_read_post_state_page( $option, $after, 1000, $area );
		if ( null === $page ) {
			return null;
		}
		foreach ( $page as $row ) {
			$ids[] = (int) $row['post_id'];
			$after = (int) $row['membership_id'];
		}
	} while ( $page );
	return $ids;
}

/**
 * Apply one bounded membership delta inside a status transaction.
 *
 * @param array $additions Option => ID => type.
 * @param array $removals Option => IDs.
 * @param int   $area Source or analysis.
 * @param array $state Routing state, read after acquiring the fence.
 * @param bool  $changed Receives actual membership/type changes.
 * @return bool
 */
function ai4seo_apply_post_state_delta_under_lock( array $additions, array $removals, int $area, array $state, bool &$changed ): bool {
	$changed = false;
	if ( ! isset( $GLOBALS['ai4seo_post_state_transaction'] ) ) {
		return false;
	}
	$title_ids = array();
	foreach ( array_unique( array_merge( array_keys( $additions ), array_keys( $removals ) ) ) as $option ) {
		$key  = ai4seo_post_state_key( $option, $area, $state );
		$adds = $additions[ $option ] ?? array();
		$ids  = ai4seo_normalize_option_post_id_collection( array_merge( array_keys( $adds ), $removals[ $option ] ?? array() ) );
		if ( null === $key || count( $ids ) > ai4seo_post_state_batch_size() ) {
			return false;
		}
		foreach ( $adds as $post_id => $type ) {
			if ( false === ai4seo_normalize_option_post_id( $post_id ) || ! is_string( $type ) || strlen( $type ) > 20 || ( '' !== $type && sanitize_key( $type ) !== $type ) ) {
				return false;
			}
		}
		if ( ! $ids ) {
			continue;
		}
		$before = ai4seo_post_state_query( 'SELECT post_id,post_type FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_id IN ({{ids}}) FOR UPDATE', $key + array( 'ids' => $ids ), true );
		if ( null === $before ) {
			return false;
		}
		$before        = array_column( $before, 'post_type', 'post_id' );
		$remove        = array_diff_key( array_fill_keys( $removals[ $option ] ?? array(), true ), $adds );
		$delete_ids    = array_keys( array_intersect_key( $before, $remove ) );
		$counter_delta = array();
		$rows          = array();
		foreach ( $ids as $post_id ) {
			$old = $before[ $post_id ] ?? null;
			$new = $adds[ $post_id ] ?? ( isset( $remove[ $post_id ] ) ? null : $old );
			if ( $old === $new ) {
				continue;
			}
			$changed               = true;
			$title_ids[ $post_id ] = $post_id;
			if ( null !== $old ) {
				$counter_delta[ $old ] = ( $counter_delta[ $old ] ?? 0 ) - 1;
			}
			if ( null !== $new ) {
				$counter_delta[ $new ] = ( $counter_delta[ $new ] ?? 0 ) + 1;
				$rows[]                = array( $key['area'], $key['generation'], $key['bucket'], $post_id, $new );
			}
		}
		if ( $delete_ids && null === ai4seo_post_state_query( 'DELETE FROM {{memberships}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_id IN ({{ids}})', $key + array( 'ids' => $delete_ids ) ) ) {
			return false;
		}
		if ( ! ai4seo_post_state_insert_rows( 'memberships', $rows ) ) {
			return false;
		}
		foreach ( $counter_delta as $type => $amount ) {
			if ( $amount < 0 ) {
				// A missing or inconsistent counter is a storage failure, never a negative total.
				$updated = ai4seo_post_state_query(
					'UPDATE {{totals}} SET total=total-{{amount}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}} AND post_type={{type}} AND total>={{amount}}',
					$key + array(
						'type'   => (string) $type,
						'amount' => -$amount,
					)
				);
				if ( 1 !== $updated ) {
					return false;
				}
				continue;
			}
			if ( 0 !== $amount && null === ai4seo_post_state_query(
				'INSERT INTO {{totals}} (area,generation,bucket,post_type,total) VALUES ({{area}},{{generation}},{{bucket}},{{type}},{{amount}}) ON DUPLICATE KEY UPDATE total=total+VALUES(total)',
				$key + array(
					'type'   => (string) $type,
					'amount' => $amount,
				)
			) ) {
				return false;
			}
		}
	}
	return 1 !== $area || ! $title_ids || ( isset( $state['building_generation'] ) && $state['generation'] === $state['building_generation'] ) || ai4seo_apply_title_order_delta( array_values( $title_ids ) );
}

/**
 * Atomically store memberships, counters and required repair markers.
 *
 * @param array $additions Option => ID => type.
 * @param array $removals Option => IDs.
 * @param int   $area Source or analysis.
 * @param bool  $changed Actual persisted change.
 * @return bool
 */
function ai4seo_apply_post_state_delta( array $additions, array $removals, int $area = 1, ?bool &$changed = null ): bool {
	$changed = false;
	return ai4seo_post_state_transaction(
		static function ( array $state ) use ( $additions, $removals, $area, &$changed ): bool {
			if ( ! in_array( $state['phase'] ?? '', array( 'active', 'export_wait' ), true ) || ! ai4seo_apply_post_state_delta_under_lock( $additions, $removals, $area, $state, $changed ) ) {
				return false;
			}
			if ( 1 !== $area || ! $changed || ai4seo_is_analysis_owned_summary_write() ) {
				return true;
			}
			$ids = array();
			foreach ( $additions as $members ) {
				$ids = array_merge( $ids, array_keys( $members ) );
			}
			foreach ( $removals as $members ) {
				$ids = array_merge( $ids, $members );
			}
			return ai4seo_post_state_enqueue_under_lock( array_values( array_unique( $ids ) ), true );
		}
	);
}

/**
 * Whether this source write is already part of an analysis commit.
 *
 * @return bool Whether this source write is already part of an analysis commit.
 */
function ai4seo_is_analysis_owned_summary_write(): bool {
	global $wpdb;
	return ( $GLOBALS['ai4seo_analysis_summary_write_scope'] ?? null ) === (string) $wpdb->options;
}

/**
 * Keep post edits durable across migration and scans.
 *
 * @param mixed  $meta_id WordPress metadata identity.
 * @param int    $post_id Changed post.
 * @param string $key Metadata key.
 * @param mixed  $value WordPress hook payload.
 * @return void
 */
function ai4seo_capture_post_state_metadata_change( $meta_id, int $post_id, string $key, $value ): void {
	unset( $meta_id, $value );
	$state = ai4seo_read_post_state_storage_status();
	if ( ! $state || 'legacy' === $state['phase'] ) {
		return;
	}
	if ( 'active' !== $state['phase'] || in_array( $key, array( AI4SEO_POST_META_GENERATED_DATA_META_KEY, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY, '_wp_attachment_image_alt', '_wp_attachment_metadata' ), true ) ) {
		ai4seo_capture_post_state_reconciliation( $post_id );
	}
}

/**
 * Retain deletion repair until both memberships and counters are reconciled.
 *
 * @param int $post_id Saved or deleted identity.
 * @return void
 */
function ai4seo_capture_post_state_post_change( int $post_id ): void {
	ai4seo_capture_title_order_change( $post_id );
	$state = ai4seo_read_post_state_storage_status();
	if ( $state && 'legacy' !== $state['phase'] ) {
		ai4seo_capture_post_state_reconciliation( $post_id );
	}
}

/**
 * Retain a repair request when WordPress already saved a change but its journal failed.
 *
 * @param int $post_id Changed identity.
 * @return void
 */
function ai4seo_capture_post_state_reconciliation( int $post_id ): void {
	$stored = false;
	ai4seo_mutate_analysis_reconciliation( array( $post_id ), true, array(), array(), $stored );
	if ( ! $stored ) {
		// Preserve the scan cursor and central cooldown. A missing scheduler alone
		// does not require a full repair when the per-post marker is already durable.
		ai4seo_schedule_generation_status_summary_rebuild();
	}
}

/**
 * Resolve only the candidate memberships, retaining the strict legacy validator.
 *
 * @param string $option Status option.
 * @param array  $ids Candidate IDs.
 * @return array Inspection contract.
 */
function ai4seo_inspect_post_state_candidates( string $option, array $ids ): array {
	if ( ! ai4seo_uses_post_state_tables() ) {
		$result        = ai4seo_inspect_analysis_post_id_option( $option );
		$result['ids'] = array_values( array_intersect( $ids, $result['ids'] ) );
		return $result;
	}
	$members = ai4seo_read_post_state_for_ids( $ids, array( $option ) );
	return array(
		'ids'            => null === $members ? array() : array_keys( $members[ $option ] ),
		'read_succeeded' => null !== $members,
		'complete'       => null !== $members,
		'issues'         => null === $members ? array( 'unreliable_read' ) : array(),
	);
}

/**
 * Supply a bounded snapshot to existing ownership validators.
 *
 * This is a read adapter, never a CAS predicate for writing legacy options.
 *
 * @param string $option Status name.
 * @param array  $ids Candidates.
 * @return array|null
 */
function ai4seo_read_post_state_candidate_snapshot( string $option, array $ids ): ?array {
	if ( ! ai4seo_uses_post_state_tables() || ! isset( AI4SEO_POST_STATE_BUCKETS[ $option ] ) ) {
		return ai4seo_get_raw_option_snapshot( $option );
	}
	$result = ai4seo_inspect_post_state_candidates( $option, $ids );
	if ( ! $result['read_succeeded'] ) {
		return null;
	}
	return array(
		'exists'      => true,
		'option_id'   => 1,
		'option_name' => $option,
		'raw_value'   => maybe_serialize( $result['ids'] ),
		'value'       => $result['ids'],
		'autoload'    => 'no',
	);
}

/**
 * Adapt a normalized transition while retaining atomicity across its statuses.
 *
 * @param array $additions Option => IDs.
 * @param array $removals Option => IDs.
 * @param bool  $changed Actual persisted change.
 * @return bool
 */
function ai4seo_apply_post_state_id_transition( array $additions, array $removals, bool &$changed ): bool {
	$ids     = array();
	$changed = false;
	foreach ( array_merge( array_values( $additions ), array_values( $removals ) ) as $members ) {
		$ids = array_merge( $ids, $members );
	}
	foreach ( array_chunk( array_values( array_unique( $ids ) ), ai4seo_post_state_batch_size() ) as $chunk ) {
		$read  = false;
		$types = ai4seo_read_post_types_by_post_ids( $chunk, $read );
		if ( ! $read ) {
			return false;
		}
		$adds    = array();
		$removes = array();
		foreach ( $additions as $option => $members ) {
			foreach ( array_intersect( $members, $chunk ) as $id ) {
				$adds[ $option ][ $id ] = $types[ $id ] ?? '';
			}
		}
		foreach ( $removals as $option => $members ) {
			$removes[ $option ] = array_values( array_intersect( $members, $chunk ) );
		}
		$packet_changed = false;
		if ( ! ai4seo_apply_post_state_delta( $adds, $removes, 1, $packet_changed ) ) {
			return false;
		}
		$changed = $changed || $packet_changed;
	}
	return true;
}

/**
 * Replace one processing lease using the existing token and intent validators.
 *
 * @param string      $option Processing context.
 * @param int         $post_id Identity.
 * @param array|null  $replacement Validated replacement.
 * @param string|null $expected Exact ownership predicate.
 * @param bool        $matched Predicate result.
 * @return bool
 */
function ai4seo_mutate_post_state_lease( string $option, int $post_id, ?array $replacement, ?string $expected, bool &$matched ): bool {
	$matched = false;
	return ai4seo_post_state_transaction(
		static function ( array $state ) use ( $option, $post_id, $replacement, $expected, &$matched ): bool {
			if ( ! in_array( $state['phase'] ?? '', array( 'active', 'export_wait' ), true ) ) {
				return false;
			}
			$kind = AI4SEO_POST_STATE_BUCKETS[ $option ] ?? 0;
			if ( ! in_array( $kind, array( 5, 14 ), true ) ) {
				return false;
			}
			$current = ai4seo_read_post_state_work( $kind, array( $post_id ) );
			if ( null === $current ) {
				return false;
			}
			$token = $current[ $post_id ]['token'] ?? '';
			if ( null !== $expected && ! hash_equals( $expected, $token ) ) {
				return true;
			}
			$matched = true;
			if ( null === $replacement ) {
				return null !== ai4seo_post_state_query(
					'DELETE FROM {{work}} WHERE kind={{kind}} AND post_id={{id}} AND token={{token}}',
					array(
						'kind'  => $kind,
						'id'    => $post_id,
						'token' => $token,
					)
				);
			}
			return ai4seo_post_state_insert_rows( 'work', array( array( $kind, $post_id, $replacement['token'], $replacement['expires_at'], wp_json_encode( $replacement ) ) ), 'work' );
		}
	);
}

/**
 * Serialize legacy writes against the migration freeze.
 *
 * @param string   $option Option to mutate.
 * @param callable $callback Existing writer.
 * @return mixed Writer result, or false when storage is frozen.
 */
function ai4seo_guard_legacy_post_state_write( string $option, callable $callback ) {
	if ( ! in_array( $option, array_merge( ai4seo_post_state_migration_sources(), array( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, ai4seo_get_generation_status_summary_pair_state_option_name() ) ), true ) ) {
		return $callback();
	}
	global $wpdb;
	if ( ( $GLOBALS['ai4seo_post_state_export_scope'] ?? null ) === (string) $wpdb->options ) {
		return $callback();
	}
	$name = ai4seo_get_post_id_option_transition_semaphore_name();
	$owns = null !== ai4seo_get_held_semaphore_record( ai4seo_get_semaphore_option_key( $name ) );
	if ( ! $owns && ! ai4seo_acquire_semaphore( $name ) ) {
		return false;
	}
	try {
		$state = ai4seo_read_post_state_storage_status();
		if ( null === $state || ( $state && ! in_array( $state['phase'], array( 'draining', 'legacy' ), true ) ) || ! ai4seo_renew_post_id_option_transition_semaphore() ) {
			return false;
		}
		return $callback();
	} finally {
		if ( ! $owns ) {
			ai4seo_release_semaphore( $name );
		}
	}
}

/**
 * Export the full analysis only for an explicit compatibility/diagnostic caller.
 *
 * @return array|null
 */
function ai4seo_export_post_state_summary(): ?array {
	$summary = array();
	foreach ( ai4seo_get_generation_status_summary_source_option_names() as $option ) {
		$after = 0;
		do {
			$page = ai4seo_read_post_state_page( $option, $after, 1000, 2 );
			if ( null === $page ) {
				return null;
			}
			foreach ( $page as $row ) {
				$type                                      = $row['post_type'];
				$summary[ $option ][ $type ]['post_ids'][] = (int) $row['post_id'];
				$summary[ $option ][ $type ]['total']      = count( $summary[ $option ][ $type ]['post_ids'] );
				$after                                     = (int) $row['membership_id'];
			}
		} while ( $page );
	}
	return $summary;
}

/**
 * Commit source coverage and its projection in bounded, idempotent packets.
 *
 * @param array $transition Coverage additions/removals.
 * @param array $batches Verified eligible option/type/ID groups.
 * @param array $isolated_ids Corrupt rows excluded from coverage.
 * @param array $metadata_types Eligible metadata rows.
 * @param array $media_types Eligible media rows.
 * @return bool
 */
function ai4seo_commit_indexed_analysis_memberships( array $transition, array $batches, array $isolated_ids, array $metadata_types, array $media_types = array() ): bool {
	$ids = array_fill_keys( array_merge( array_keys( $metadata_types ), array_keys( $media_types ), $isolated_ids ), true );
	foreach ( array_merge( array_values( $transition['additions'] ), array_values( $transition['removals'] ) ) as $members ) {
		$ids += array_fill_keys( $members, true );
	}
	if ( count( $ids ) <= ai4seo_post_state_batch_size() ) {
		return ai4seo_commit_indexed_analysis_membership_packet( $transition, $batches, $isolated_ids, $metadata_types, $media_types );
	}
	$ids = array_keys( $ids );
	sort( $ids, SORT_NUMERIC );
	foreach ( array_chunk( $ids, ai4seo_post_state_batch_size() ) as $packet ) {
		$lookup            = array_fill_keys( $packet, true );
		$packet_transition = array();
		foreach ( array( 'additions', 'removals' ) as $operation ) {
			$packet_transition[ $operation ] = array();
			foreach ( $transition[ $operation ] as $option => $members ) {
				$packet_transition[ $operation ][ $option ] = array_values( array_intersect( $members, $packet ) );
			}
		}
		$packet_batches = array();
		foreach ( $batches as $option => $types ) {
			foreach ( $types as $type => $members ) {
				$packet_batches[ $option ][ $type ] = array_values( array_intersect( $members, $packet ) );
			}
		}
		// Committed packets are idempotent; the caller publishes its cursor only after all succeed.
		if ( ! ai4seo_commit_indexed_analysis_membership_packet(
			$packet_transition,
			$packet_batches,
			array_values( array_intersect( $isolated_ids, $packet ) ),
			array_intersect_key( $metadata_types, $lookup ),
			array_intersect_key( $media_types, $lookup )
		) ) {
			return false;
		}
	}
	return true;
}

/**
 * Commit one bounded packet, keeping operative and filtered memberships atomic.
 *
 * @param array $transition Coverage additions/removals.
 * @param array $batches Verified eligible option/type/ID groups.
 * @param array $isolated_ids Corrupt rows excluded from coverage.
 * @param array $metadata_types Eligible metadata rows.
 * @param array $media_types Eligible media rows.
 * @return bool Whether the complete packet committed.
 */
function ai4seo_commit_indexed_analysis_membership_packet( array $transition, array $batches, array $isolated_ids, array $metadata_types, array $media_types ): bool {
	return ai4seo_post_state_transaction(
		static function ( array $state ) use ( $transition, $batches, $isolated_ids, $metadata_types, $media_types ): bool {
			$eligible_types = $metadata_types + $media_types;
			if ( 'active' !== ( $state['phase'] ?? '' ) ) {
				return false;
			}
			$summary_adds = array();
			foreach ( ai4seo_get_verified_posts_table_analysis_summary_batches( $batches, $transition['additions'] ) as $option => $types ) {
				foreach ( $types as $type => $ids ) {
					foreach ( $ids as $id ) {
						$summary_adds[ $option ][ $id ] = $type;
					}
				}
			}
			// Re-read operative state under the writer fence, while retaining the scan's exclusions.
			if ( $eligible_types ) {
				$current = ai4seo_read_post_state_for_ids( array_keys( $eligible_types ), AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS );
				if ( null === $current ) {
					return false;
				}
				foreach ( $current as $option => $members ) {
					$context_types           = AI4SEO_POST_STATE_BUCKETS[ $option ] < 10 ? $metadata_types : $media_types;
					$summary_adds[ $option ] = array_intersect_key( $context_types, $members );
				}
			}
			$coverage_adds = array();
			foreach ( $transition['additions'] as $option => $ids ) {
				foreach ( $ids as $id ) {
					$coverage_adds[ $option ][ $id ] = $summary_adds[ $option ][ $id ] ?? $eligible_types[ $id ] ?? '';
				}
			}
			$summary_removes = $transition['removals'];
			foreach ( AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS as $option ) {
				$summary_removes[ $option ] = array_keys( $eligible_types );
			}
			foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $option ) {
				$summary_removes[ $option ] = array_values( array_unique( array_merge( $summary_removes[ $option ] ?? array(), $isolated_ids ) ) );
			}
			$state   = ai4seo_get_analysis_write_storage_state( $state );
			$changed = false;
			return ai4seo_apply_post_state_delta_under_lock( $coverage_adds, $transition['removals'], 1, $state, $changed )
				&& ai4seo_apply_post_state_delta_under_lock( $summary_adds, $summary_removes, 2, $state, $changed );
		}
	);
}

/**
 * Whether a new analysis generation was committed without a bulk delete.
 *
 * @return bool Whether a new analysis generation was committed without a bulk delete.
 */
function ai4seo_reset_indexed_analysis_generation(): bool {
	return ai4seo_post_state_transaction(
		static function ( array $state ): bool {
			if ( 'active' !== ( $state['phase'] ?? '' ) || PHP_INT_MAX === $state['generation'] ) {
				return false;
			}
			$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot();
			if ( empty( $snapshot['success'] ) ) {
				return false;
			}
			$values   = $snapshot['values'];
			$progress = $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ];
			if ( ! isset( $state['published_progress'] ) && 'completed' === $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE ]
				&& 2 === (int) ( $progress['version'] ?? 0 ) && empty( $progress['failure'] ) ) {
				$remaining = ai4seo_read_posts_table_analysis_tail(
					(int) $values[ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ],
					(int) $progress['upper_post_id']
				);
				if ( null === $remaining ) {
					return false;
				}
				if ( ! $remaining ) {
					$state['published_progress'] = $progress;
				}
			}
			if ( isset( $state['published_progress'] ) ) {
				$last = $state['building_generation'] ?? $state['generation'];
				if ( PHP_INT_MAX === $last ) {
					return false;
				}
				$state['building_generation'] = $last + 1;
			} else {
				++$state['generation'];
			}
			$result = ai4seo_bulk_update_environmental_variables(
				array(
					AI4SEO_ENVIRONMENTAL_VARIABLE_POST_STATE_STORAGE => $state,
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS => array(),
					AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE => 'idle',
				),
				false
			);
			return ! empty( $result['success'] );
		}
	);
}

/**
 * Remove an obsolete generation a bounded number of rows at a time.
 *
 * @return bool
 */
function ai4seo_cleanup_post_state_generations(): bool {
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state || 'active' !== ( $state['phase'] ?? '' ) ) {
		return false;
	}
	$values = array(
		'generation' => $state['generation'],
		'latest'     => $state['building_generation'] ?? $state['generation'],
		'retired'    => $state['retired_generation'] ?? $state['generation'],
		'limit'      => ai4seo_post_state_batch_size(),
	);
	return null !== ai4seo_post_state_query( 'DELETE FROM {{memberships}} WHERE generation>0 AND generation<{{latest}} AND generation<>{{generation}} AND generation<>{{retired}} ORDER BY generation,membership_id LIMIT {{limit}}', $values )
		&& null !== ai4seo_post_state_query( 'DELETE FROM {{totals}} WHERE generation>0 AND generation<{{latest}} AND generation<>{{generation}} AND generation<>{{retired}} LIMIT {{limit}}', $values );
}

/**
 * Reconcile current metadata and status without loading another post's state.
 *
 * @param array $batch Previously claimed reconciliation tokens.
 * @param int   $kind Ordinary work or the retained background replay journal.
 * @return bool
 */
function ai4seo_reconcile_indexed_analysis_batch( array $batch, int $kind = 1 ): bool {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_posts_table_analysis_database_lock_name() ) ) {
		return false;
	}
	$taxonomy_lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( ! ai4seo_acquire_database_advisory_lock( $taxonomy_lock ) ) {
		return false;
	}
	try {
		return ai4seo_post_state_transaction(
			static function ( array $state ) use ( $batch, $kind ): bool {
				if ( ! in_array( $kind, array( 1, AI4SEO_ANALYSIS_BUILD_WORK_KIND ), true ) || 'active' !== ( $state['phase'] ?? '' ) || count( $batch ) > ai4seo_post_state_batch_size() ) {
					return false;
				}
				$work = ai4seo_read_post_state_work( $kind, array_keys( $batch ) );
				if ( null === $work ) {
					return false;
				}
				foreach ( $batch as $id => $entry ) {
					if ( ( $work[ $id ]['token'] ?? '' ) !== $entry['token'] ) {
						unset( $batch[ $id ] );
					}
				}
				if ( ! $batch ) {
					return true;
				}
				$rows = ai4seo_post_state_query( 'SELECT ID,post_type,post_author,post_status,post_mime_type,post_date,post_date_gmt,post_modified,post_modified_gmt FROM {{posts}} WHERE ID IN ({{ids}})', array( 'ids' => array_keys( $batch ) ), true );
				if ( null === $rows ) {
					return false;
				}
				$rows            = array_column( $rows, null, 'ID' );
				$source_adds     = array();
				$source_removes  = array();
				$summary_adds    = array();
				$summary_removes = array_fill_keys( ai4seo_get_generation_status_summary_source_option_names(), array_keys( $batch ) );
				$current         = ai4seo_read_post_state_for_ids( array_keys( $batch ), AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS );
				$completed       = $batch;
				if ( null === $current ) {
					return false;
				}
				foreach ( $batch as $id => $entry ) {
					$isolated = false;
					foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
						$eligible   = false;
						$issues     = array();
						$transition = ai4seo_build_analysis_reconciliation_coverage( (int) $id, $rows[ $id ] ?? null, $context, $eligible, $issues );
						if ( null === $transition ) {
							return false;
						}
						$isolated = $isolated || (bool) $issues;
						$type     = $rows[ $id ]['post_type'] ?? '';
						foreach ( $transition['additions'] as $option => $ids ) {
							$source_adds[ $option ][ $id ]  = $type;
							$summary_adds[ $option ][ $id ] = $type;
						}
						foreach ( $transition['removals'] as $option => $ids ) {
							$source_removes[ $option ][] = (int) $id;
						}
						if ( $eligible ) {
							foreach ( array_intersect( ai4seo_get_analysis_reconciliation_source_options( $context ), AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS ) as $option ) {
								if ( isset( $current[ $option ][ $id ] ) ) {
									$summary_adds[ $option ][ $id ] = $type;
								}
							}
						}
					}
					if ( $isolated ) {
						// Retain the token for repair; exclude uncertain coverage without touching live queues or metadata.
						foreach ( array_keys( $source_adds ) as $option ) {
							unset( $source_adds[ $option ][ $id ] );
						}
						foreach ( array_keys( $summary_adds ) as $option ) {
							unset( $summary_adds[ $option ][ $id ] );
						}
						foreach ( AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $option ) {
							$source_removes[ $option ][] = (int) $id;
						}
						unset( $completed[ $id ] );
						if ( AI4SEO_ANALYSIS_BUILD_WORK_KIND === $kind && ! ai4seo_retain_background_analysis_repair( (int) $id, $entry ) ) {
							return false;
						}
						if ( null === ai4seo_post_state_query(
							'UPDATE {{work}} SET expires_at={{retry}} WHERE kind=1 AND post_id={{id}} AND token={{token}}',
							array(
								'retry' => time() + AI4SEO_ANALYSIS_RECONCILIATION_RETRY_DELAY,
								'id'    => (int) $id,
								'token' => $entry['token'],
							)
						) ) {
							return false;
						}
					}
				}
				$changed = false;
				$targets = array( $state );
				if ( isset( $state['building_generation'] ) ) {
					$targets[] = ai4seo_get_analysis_write_storage_state( $state );
				}
				foreach ( $targets as $target ) {
					if ( ! ai4seo_apply_post_state_delta_under_lock( $source_adds, $source_removes, 1, $target, $changed )
						|| ! ai4seo_apply_post_state_delta_under_lock( $summary_adds, $summary_removes, 2, $target, $changed ) ) {
						return false;
					}
				}
				return ai4seo_post_state_enqueue_under_lock( array(), false, $completed )
					&& ( AI4SEO_ANALYSIS_BUILD_WORK_KIND !== $kind || ai4seo_acknowledge_background_analysis_work( $batch ) );
			},
			ai4seo_get_posts_table_analysis_database_lock_name()
		);
	} finally {
		ai4seo_release_database_advisory_lock( $taxonomy_lock );
	}
}

/**
 * Read bounded durable work, optionally restricted to identities.
 *
 * @param int        $kind One: reconciliation; five/fourteen: processing context.
 * @param array|null $ids Null selects an ordered page.
 * @param int        $limit Page size.
 * @param int        $after Last work row.
 * @return array|null ID => decoded payload.
 */
function ai4seo_read_post_state_work( int $kind, ?array $ids = null, int $limit = 1000, int $after = 0 ): ?array {
	if ( array() === $ids ) {
		return array();
	}
	$result = array();
	foreach ( null === $ids ? array( null ) : array_chunk( $ids, ai4seo_post_state_batch_size() ) as $chunk ) {
		$values = array( 'kind' => $kind );
		if ( null === $chunk ) {
			$values += array(
				'after' => $after,
				'limit' => min( $limit, ai4seo_post_state_batch_size() ),
			);
			$where   = 'work_id>{{after}} ORDER BY work_id LIMIT {{limit}}';
		} else {
			$values['ids'] = $chunk;
			$where         = 'post_id IN ({{ids}}) ORDER BY work_id';
		}
		$rows = ai4seo_post_state_query( 'SELECT work_id,post_id,token,expires_at,payload FROM {{work}} WHERE kind={{kind}} AND ' . $where, $values, true );
		if ( null === $rows ) {
			return null;
		}
		foreach ( $rows as $row ) {
			$entry = json_decode( $row['payload'], true );
			if ( ! is_array( $entry ) || ! is_string( $entry['token'] ?? null ) || $entry['token'] !== $row['token'] ) {
				return null;
			}
			$valid_options = ! isset( $entry['options'] ) || ( is_array( $entry['options'] ) && ! array_diff( $entry['options'], ai4seo_get_generation_status_summary_source_option_names() ) );
			if ( '' === $entry['token'] || strlen( $entry['token'] ) > 128
				|| ( in_array( $kind, array( 1, AI4SEO_ANALYSIS_BUILD_WORK_KIND ), true ) && ( ! is_bool( $entry['coverage'] ?? null ) || ! $valid_options ) ) ) {
				return null;
			}
			if ( AI4SEO_TITLE_ORDER_WORK_KIND === $kind ) {
				if ( ! is_int( $entry['expires_at'] ?? null ) || $entry['expires_at'] !== (int) $row['expires_at'] || $entry['expires_at'] < 0 ) {
					return null;
				}
			} elseif ( ! in_array( $kind, array( 1, AI4SEO_ANALYSIS_BUILD_WORK_KIND ), true ) ) {
				$normalized = array();
				$option     = 5 === $kind ? AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME : AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
				if ( ! in_array( $kind, array( 5, 14 ), true ) || ( $entry['expires_at'] ?? null ) !== (int) $row['expires_at']
					|| ! ai4seo_validate_processing_claim_leases( array( (int) $row['post_id'] => $entry ), $normalized )
					|| ! ai4seo_validate_processing_claim_lease_context( $option, $normalized ) || ! ai4seo_are_processing_claim_deadlines_plausible( $normalized ) ) {
					return null;
				}
			}
			$result[ (int) $row['post_id'] ] = $entry;
		}
	}
	return $result;
}

/**
 * Coalesce reconciliation without discarding an older wider repair scope.
 *
 * @param array $ids IDs to enqueue.
 * @param bool  $coverage Whether coverage must be recomputed.
 * @param array $completed Exact previous tokens to acknowledge.
 * @param array $options_by_id Optional source restrictions.
 * @return bool
 */
function ai4seo_post_state_enqueue_under_lock( array $ids, bool $coverage, array $completed = array(), array $options_by_id = array() ): bool {
	if ( ! isset( $GLOBALS['ai4seo_post_state_transaction'] ) ) {
		return false;
	}
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state ) {
		return false;
	}
	if ( ( $ids || $completed ) && 'export' === ( $state['phase'] ?? '' ) ) {
		$next                  = $state;
		$next['work_revision'] = ( $state['work_revision'] ?? 0 ) + 1;
		if ( ! ai4seo_save_post_state_storage_status( $state, $next ) ) {
			return false;
		}
	}
	foreach ( $completed as $post_id => $entry ) {
		if ( null === ai4seo_post_state_query(
			'DELETE FROM {{work}} WHERE kind=1 AND post_id={{id}} AND token={{token}}',
			array(
				'id'    => (int) $post_id,
				'token' => $entry['token'],
			)
		) ) {
			return false;
		}
	}
	foreach ( array_chunk( $ids, ai4seo_post_state_batch_size() ) as $chunk ) {
		$current = ai4seo_read_post_state_work( 1, $chunk );
		if ( null === $current ) {
			return false;
		}
		$rows = array();
		foreach ( $chunk as $post_id ) {
			$entry  = array(
				'token'    => wp_generate_uuid4(),
				'coverage' => $coverage || ! empty( $current[ $post_id ]['coverage'] ),
				'options'  => array_values( array_unique( array_merge( $options_by_id[ $post_id ] ?? ai4seo_get_generation_status_summary_source_option_names(), $current[ $post_id ]['options'] ?? array() ) ) ),
			);
			$rows[] = array( 1, $post_id, $entry['token'], 0, wp_json_encode( $entry ) );
			if ( isset( $state['building_generation'] ) ) {
				$rows[] = array( AI4SEO_ANALYSIS_BUILD_WORK_KIND, $post_id, $entry['token'], 0, wp_json_encode( $entry ) );
			}
		}
		// A background journal adds a second row per ID; keep each SQL statement bounded.
		foreach ( array_chunk( $rows, ai4seo_post_state_batch_size() ) as $packet ) {
			if ( ! ai4seo_post_state_insert_rows( 'work', $packet, 'work' ) ) {
				return false;
			}
		}
	}
	return true;
}
