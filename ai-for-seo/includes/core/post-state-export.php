<?php
/**
 * Bounded, resumable export of current table state to public legacy formats.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate only compact progress, never a caller-provided filesystem path.
 *
 * @param mixed $value Checkpoint.
 * @return bool
 */
function ai4seo_validate_post_state_export_checkpoint( $value ): bool {
	if ( array() === $value ) {
		return true;
	}
	if ( ! is_array( $value ) || array_keys( $value ) !== array( 'version', 'run', 'source', 'group', 'cursor', 'count', 'bytes', 'hash', 'complete', 'revision' )
		|| 1 !== ai4seo_normalize_option_post_id( $value['version'], true ) || ! is_string( $value['run'] ) || ! preg_match( '/^[a-f0-9]{32}$/D', $value['run'] )
		|| ! is_string( $value['hash'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $value['hash'] ) || ! is_bool( $value['complete'] ) ) {
		return false;
	}
	foreach ( array( 'source', 'group', 'cursor', 'count', 'bytes', 'revision' ) as $key ) {
		if ( false === ai4seo_normalize_option_post_id( $value[ $key ], true ) ) {
			return false;
		}
	}
	return $value['source'] < count( ai4seo_post_state_migration_sources() );
}

/**
 * Derive a private site-specific spool outside the web roots.
 *
 * No arbitrary paths are persisted. Files survive worker termination and are
 * accessible only to the same OS account. A different web node fails closed.
 *
 * @return string|null Directory, or null if private storage is unavailable.
 */
function ai4seo_post_state_export_directory(): ?string {
	global $wpdb;
	$base = realpath( sys_get_temp_dir() );
	if ( false === $base ) {
		return null;
	}
	$base = wp_normalize_path( $base );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- DOCUMENT_ROOT is a server filesystem path, validated with realpath below; text sanitization would change legitimate path bytes.
	foreach ( array_filter( array( ABSPATH, WP_CONTENT_DIR, $_SERVER['DOCUMENT_ROOT'] ?? '' ) ) as $root ) {
		$root = realpath( $root );
		if ( false !== $root && 0 === stripos( trailingslashit( $base ), trailingslashit( wp_normalize_path( $root ) ) ) ) {
			return null;
		}
	}
	$directory = $base . '/ai4seo-export-' . hash( 'sha256', ABSPATH . ':' . DB_NAME . ':' . $wpdb->options );
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Private native streams must not use public uploads or FTP credentials.
	if ( ! is_dir( $directory ) && ! mkdir( $directory, 0700 ) && ! is_dir( $directory ) ) {
		return null;
	}
	if ( is_link( $directory ) || ! wp_is_writable( $directory ) ) {
		return null;
	}
	if ( '/' === DIRECTORY_SEPARATOR ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Existing private spools must retain owner-only directory access too.
		if ( ! chmod( $directory, 0700 ) ) {
			return null;
		}
		clearstatcache( true, $directory );
		$permissions = fileperms( $directory );
		if ( false === $permissions || 0 !== ( $permissions & 0077 ) ) {
			return null;
		}
	}
	return $directory;
}

/**
 * Compute the bounded groups and exact legacy headers for one frozen source.
 *
 * @param string $name Legacy option.
 * @param array  $state Routing checkpoint.
 * @return array|null Stream groups, or a failed read.
 */
function ai4seo_post_state_export_groups( string $name, array $state ): ?array {
	if ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ) {
		$rows = ai4seo_post_state_query( 'SELECT bucket,post_type,total FROM {{totals}} WHERE area=2 AND generation={{generation}} AND total>0 ORDER BY bucket,post_type', array( 'generation' => $state['generation'] ), true );
		if ( null === $rows ) {
			return null;
		}
		$by_option = array();
		$options   = array_flip( AI4SEO_POST_STATE_BUCKETS );
		foreach ( $rows as $row ) {
			if ( ! isset( $options[ (int) $row['bucket'] ] ) ) {
				return null;
			}
			$by_option[ $options[ (int) $row['bucket'] ] ][] = $row;
		}
		$groups = array();
		foreach ( ai4seo_get_generation_status_summary_source_option_names() as $option ) {
			foreach ( $by_option[ $option ] ?? array() as $index => $row ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Public legacy wire format requires exact serialization of validated scalar and array values; objects are excluded.
				$prefix = 0 === $index ? serialize( $option ) . 'a:' . count( $by_option[ $option ] ) . ':{' : '';
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Public legacy wire format requires exact serialization of validated scalar and array values; objects are excluded.
				$prefix  .= serialize( $row['post_type'] ) . 'a:2:{s:8:"post_ids";a:' . (int) $row['total'] . ':{';
				$suffix   = '}s:5:"total";i:' . (int) $row['total'] . ';}' . ( count( $by_option[ $option ] ) === $index + 1 ? '}' : '' );
				$groups[] = array(
					'area'       => 2,
					'generation' => $state['generation'],
					'bucket'     => (int) $row['bucket'],
					'type'       => $row['post_type'],
					'total'      => (int) $row['total'],
					'kind'       => 0,
					'prefix'     => $prefix,
					'suffix'     => $suffix,
				);
			}
		}
		if ( $groups ) {
			$groups[0]['prefix']                       = 'a:' . count( $by_option ) . ':{' . $groups[0]['prefix'];
			$groups[ count( $groups ) - 1 ]['suffix'] .= '}';
			return $groups;
		}
	} elseif ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) ) {
		$key  = ai4seo_post_state_key( $name, 1, $state );
		$rows = ai4seo_post_state_query( 'SELECT COALESCE(SUM(total),0) AS n FROM {{totals}} WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}}', $key, true );
		if ( null === $rows ) {
			return null;
		}
		$total = (int) $rows[0]['n'];
		return array(
			$key + array(
				'type'   => '',
				'total'  => $total,
				'kind'   => 0,
				'prefix' => 'a:' . $total . ':{',
				'suffix' => '}',
			),
		);
	} elseif ( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME !== $name ) {
		$kind = AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME === $name ? 1 : ( ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) === $name ? 5 : 14 );
		$rows = ai4seo_post_state_query( 'SELECT COUNT(*) AS n FROM {{work}} WHERE kind={{kind}}', array( 'kind' => $kind ), true );
		if ( null === $rows ) {
			return null;
		}
		$total = (int) $rows[0]['n'];
		return array(
			array(
				'kind'   => $kind,
				'total'  => $total,
				'prefix' => 'a:' . $total . ':{',
				'suffix' => '}',
			),
		);
	}
	return array(
		array(
			'kind'   => -1,
			'total'  => 0,
			'prefix' => 'a:0:{',
			'suffix' => '}',
		),
	);
}

/**
 * Read one export packet, preserving FIFO and the existing work validators.
 *
 * @param array $group Frozen group.
 * @param int   $after Last row.
 * @return array|null Rows with row ID, legacy key and value.
 */
function ai4seo_read_post_state_export_packet( array $group, int $after ): ?array {
	if ( -1 === $group['kind'] ) {
		return array();
	}
	if ( 0 === $group['kind'] ) {
		$values = array_intersect_key( $group, array_flip( array( 'area', 'generation', 'bucket', 'type' ) ) ) + array(
			'after' => $after,
			'limit' => ai4seo_post_state_batch_size(),
		);
		$rows   = ai4seo_post_state_query(
			'SELECT membership_id AS row_id,post_id FROM {{memberships}} FORCE INDEX (' . ( '' === $group['type'] ? 'queue_page' : 'type_page' ) . ') WHERE area={{area}} AND generation={{generation}} AND bucket={{bucket}}'
				. ( '' !== $group['type'] ? ' AND post_type={{type}}' : '' ) . ' AND membership_id>{{after}} ORDER BY membership_id LIMIT {{limit}}',
			$values,
			true
		);
		if ( null === $rows ) {
			return null;
		}
		foreach ( $rows as &$row ) {
			$row['value'] = (int) $row['post_id'];
			if ( $row['value'] <= 0 ) {
				return null;
			}
		}
		unset( $row );
		return $rows;
	}
	$rows = ai4seo_post_state_query(
		'SELECT work_id AS row_id,post_id FROM {{work}} FORCE INDEX (work_page) WHERE kind={{kind}} AND work_id>{{after}} ORDER BY work_id LIMIT {{limit}}',
		array(
			'kind'  => $group['kind'],
			'after' => $after,
			'limit' => ai4seo_post_state_batch_size(),
		),
		true
	);
	if ( null === $rows ) {
		return null;
	}
	$values = ai4seo_read_post_state_work( $group['kind'], array_map( 'intval', array_column( $rows, 'post_id' ) ) );
	if ( null === $values ) {
		return null;
	}
	foreach ( $rows as &$row ) {
		if ( ! isset( $values[ (int) $row['post_id'] ] ) ) {
			return null;
		}
		$row['value'] = $values[ (int) $row['post_id'] ];
	}
	unset( $row );
	return $rows;
}

/**
 * Commit export progress while retaining concurrently recorded repair revisions.
 *
 * @param array $previous Expected progress.
 * @param array $next Replacement progress, updated with the latest revision.
 * @return bool
 */
function ai4seo_save_post_state_export_progress( array $previous, array &$next ): bool {
	return ai4seo_post_state_transaction(
		static function ( array $current ) use ( $previous, &$next ): bool {
			$expected                  = $previous;
			$expected['work_revision'] = $current['work_revision'] ?? 0;
			if ( $current !== $expected ) {
				return false;
			}
			$next['work_revision'] = $current['work_revision'] ?? 0;
			return ai4seo_save_post_state_storage_status( $current, $next );
		}
	);
}

/**
 * Build bounded serialized packets with durable byte/cursor checkpoints.
 *
 * @param array  $state State updated after each confirmed checkpoint.
 * @param array  $groups Source groups.
 * @param float  $deadline Worker deadline.
 * @param string $path Derived private file path.
 * @return string ready, deferred, or a diagnostic reason.
 */
function ai4seo_stream_post_state_export_batch( array &$state, array $groups, float $deadline, string $path ): string {
	$checkpoint = $state['export'];
	if ( is_link( $path ) ) {
		return 'spool_not_private';
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- The private resumable spool is intentionally a native stream.
	$file = fopen( $path, 'c+b' );
	if ( false === $file ) {
		return 'spool_open_failed';
	}
	try {
		if ( is_link( $path ) || ! flock( $file, LOCK_EX | LOCK_NB ) ) {
			return 'spool_not_private';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Restrict the private stream on POSIX; Windows inherits the account's temp ACL.
		chmod( $path, 0600 );
		$hash = hash_init( 'sha256' );
		if ( hash_update_stream( $hash, $file, $checkpoint['bytes'] ) !== $checkpoint['bytes'] || ! hash_equals( $checkpoint['hash'], hash_final( hash_copy( $hash ) ) ) ) {
			return 'spool_checkpoint_mismatch';
		}
		if ( ! ftruncate( $file, $checkpoint['bytes'] ) || 0 !== fseek( $file, $checkpoint['bytes'] ) ) {
			return 'spool_truncate_failed';
		}
		while ( ! $checkpoint['complete'] && microtime( true ) < $deadline ) {
			$group = $groups[ $checkpoint['group'] ] ?? null;
			if ( null === $group ) {
				return 'export_group_invalid';
			}
			$rows = ai4seo_read_post_state_export_packet( $group, $checkpoint['cursor'] );
			if ( null === $rows || ( ! $rows && $checkpoint['count'] !== $group['total'] ) ) {
				return 'export_packet_unreadable';
			}
			$chunk = 0 === $checkpoint['count'] ? $group['prefix'] : '';
			foreach ( $rows as $row ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Public legacy wire format requires exact serialization of validated scalar and array values; objects are excluded.
				$chunk .= serialize( 0 === $group['kind'] ? $checkpoint['count'] : (int) $row['post_id'] ) . serialize( $row['value'] );
				++$checkpoint['count'];
				$checkpoint['cursor'] = (int) $row['row_id'];
			}
			if ( $checkpoint['count'] > $group['total'] ) {
				return 'export_count_changed';
			}
			if ( $checkpoint['count'] === $group['total'] ) {
				// Check the terminal cursor as well: counters must not conceal extra memberships.
				$extra = ai4seo_read_post_state_export_packet( $group, $checkpoint['cursor'] );
				if ( null === $extra || $extra ) {
					return 'export_count_mismatch';
				}
				$chunk .= $group['suffix'];
				++$checkpoint['group'];
				$checkpoint['cursor']   = 0;
				$checkpoint['count']    = 0;
				$checkpoint['complete'] = count( $groups ) === $checkpoint['group'];
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write only this bounded serialization packet.
			if ( strlen( $chunk ) !== fwrite( $file, $chunk ) || ! fflush( $file ) ) {
				return 'spool_write_failed';
			}
			// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.fsyncFound -- Optional durable flush, guarded for PHP 7.4 and 8.0.
			if ( function_exists( 'fsync' ) && ! fsync( $file ) ) {
				return 'spool_sync_failed';
			}
			hash_update( $hash, $chunk );
			$checkpoint['bytes'] += strlen( $chunk );
			$checkpoint['hash']   = hash_final( hash_copy( $hash ) );
			$next                 = $state;
			$next['export']       = $checkpoint;
			do_action( 'ai4seo_post_state_checkpoint', 'before_export_batch_checkpoint' );
			if ( ! ai4seo_save_post_state_export_progress( $state, $next ) ) {
				return 'deferred';
			}
			$state = $next;
			do_action( 'ai4seo_post_state_checkpoint', 'after_export_batch_checkpoint' );
			if ( $checkpoint['revision'] !== $state['work_revision'] && 1 === $group['kind'] ) {
				return 'deferred';
			}
		}
		return $checkpoint['complete'] ? 'ready' : 'deferred';
	} finally {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Release the private spool in every failure path.
		fclose( $file );
	}
}

/**
 * Verify exact server bytes without loading or unserializing the option.
 *
 * @param string $name Option name.
 * @param array  $checkpoint Completed stream.
 * @return string|null Source manifest digest, or null on mismatch/error.
 */
function ai4seo_verify_post_state_export_stream( string $name, array $checkpoint ): ?string {
	$rows = ai4seo_post_state_query( "SELECT OCTET_LENGTH(option_value) AS bytes,SHA2(option_value,256) AS checksum,SHA2(CONCAT('1',option_value),256) AS digest FROM {{options}} WHERE option_name={{name}} AND autoload='no'", array( 'name' => $name ), true );
	return $rows && (int) $rows[0]['bytes'] === $checkpoint['bytes'] && hash_equals( $checkpoint['hash'], $rows[0]['checksum'] ) ? $rows[0]['digest'] : null;
}

/**
 * Publish through the existing wpdb connection with bounded native parameter data.
 *
 * The wpdb string-escaping path materializes multiple copies of the entire value.
 * Only this explicit export uses mysqli's prepared long-data transport, while
 * identifiers and the option name are prepared by wpdb. No new connection, hooks,
 * legacy comparison arrays, or alloptions values are introduced.
 *
 * @param string $name Destination.
 * @param string $path Private spool.
 * @param array  $checkpoint Completed source.
 * @return bool
 */
function ai4seo_publish_post_state_export_stream( string $name, string $path, array $checkpoint ): bool {
	global $wpdb;
	if ( ! $checkpoint['complete'] || ! ( $wpdb->dbh instanceof mysqli ) || ! hash_equals( $checkpoint['hash'], (string) hash_file( 'sha256', $path ) ) ) {
		return false;
	}
	if ( null !== ai4seo_verify_post_state_export_stream( $name, $checkpoint ) ) {
		return true;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Explicit export preflight; only one small numeric result is loaded.
	$maximum = $wpdb->get_var( 'SELECT @@max_allowed_packet' );
	if ( $wpdb->last_error || $checkpoint['bytes'] >= (int) $maximum ) {
		return false;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- The completed private spool is streamed, never read into a PHP string.
	$file = fopen( $path, 'rb' );
	if ( false === $file ) {
		return false;
	}
	$statement = null;
	try {
		$query = ai4seo_post_state_sql( "INSERT INTO {{options}} (option_name,option_value,autoload) VALUES ({{name}},?,'no') ON DUPLICATE KEY UPDATE option_value=VALUES(option_value),autoload='no'", array( 'name' => $name ) );
		if ( false === $query ) {
			return false;
		}
		$statement = $wpdb->dbh->prepare( $query );
		if ( false === $statement ) {
			return false;
		}
		$value = null;
		if ( ! $statement->bind_param( 'b', $value ) ) {
			return false;
		}
		while ( ! feof( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Stream bounded chunks from the private export spool.
			$chunk = fread( $file, 65536 );
			if ( false === $chunk || ! $statement->send_long_data( 0, $chunk ) ) {
				return false;
			}
		}
		do_action( 'ai4seo_post_state_checkpoint', 'before_export_option_commit' );
		if ( ! $statement->execute() ) {
			return false;
		}
		ai4seo_invalidate_option_cache( $name, false );
		ai4seo_reset_generation_status_summary_request_cache();
		do_action( 'ai4seo_post_state_checkpoint', 'after_export_option_commit' );
		return null !== ai4seo_verify_post_state_export_stream( $name, $checkpoint );
	} catch ( mysqli_sql_exception $exception ) {
		return false;
	} finally {
		if ( $statement instanceof mysqli_stmt ) {
			$statement->close();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the private export stream.
		fclose( $file );
	}
}

/**
 * Build the small summary companions from group counts and the streamed digest.
 *
 * @param array $groups Summary groups.
 * @param array $checkpoint Complete summary stream.
 * @return array Option names mapped to small values.
 */
function ai4seo_post_state_export_summary_companions( array $groups, array $checkpoint ): array {
	$totals  = array();
	$options = array_flip( AI4SEO_POST_STATE_BUCKETS );
	foreach ( $groups as $group ) {
		if ( 0 === $group['kind'] ) {
			$totals[ $options[ $group['bucket'] ] ][ $group['type'] ] = $group['total'];
		}
	}
	$totals      = ai4seo_get_comparable_generation_status_summary_totals( $totals );
	$totals_hash = hash( 'sha256', maybe_serialize( $totals ) );
	$pair        = array(
		'version'         => 1,
		'generation'      => hash( 'sha256', $checkpoint['hash'] . ':' . $totals_hash ),
		'full_checksum'   => $checkpoint['hash'],
		'totals_checksum' => $totals_hash,
	);
	return array(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME => $totals,
		ai4seo_get_generation_status_summary_pair_state_option_name() => $pair,
	);
}

/**
 * Publish only the small counter and pair records through the existing writer.
 *
 * @param array $groups Summary groups.
 * @param array $checkpoint Completed stream.
 * @return bool
 */
function ai4seo_publish_post_state_export_summary_companions( array $groups, array $checkpoint ): bool {
	global $wpdb;
	$values = ai4seo_post_state_export_summary_companions( $groups, $checkpoint );
	$rows   = ai4seo_post_state_query( 'SELECT MAX(OCTET_LENGTH(option_value)) AS bytes FROM {{options}} WHERE option_name IN ({{names}})', array( 'names' => array_keys( $values ) ), true );
	if ( null === $rows || (int) $rows[0]['bytes'] > 1024 * 1024 ) {
		return false;
	}
	$GLOBALS['ai4seo_post_state_export_scope'] = (string) $wpdb->options;
	try {
		foreach ( $values as $name => $value ) {
			if ( ! ai4seo_update_option( $name, $value ) ) {
				return false;
			}
		}
		return true;
	} finally {
		unset( $GLOBALS['ai4seo_post_state_export_scope'] );
	}
}

/**
 * Check both summary companions against the frozen counters and server digest.
 *
 * @param array $state Frozen routing state.
 * @return bool
 */
function ai4seo_verify_post_state_export_summary_companions( array $state ): bool {
	$groups = ai4seo_post_state_export_groups( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $state );
	$rows   = ai4seo_post_state_query( 'SELECT SHA2(option_value,256) AS checksum FROM {{options}} WHERE option_name={{name}}', array( 'name' => AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME ), true );
	if ( null === $groups || ! $rows ) {
		return false;
	}
	foreach ( ai4seo_post_state_export_summary_companions( $groups, array( 'hash' => $rows[0]['checksum'] ) ) as $name => $value ) {
		$rows = ai4seo_post_state_query( "SELECT SHA2(option_value,256) AS checksum FROM {{options}} WHERE option_name={{name}} AND autoload='no'", array( 'name' => $name ), true );
		if ( ! $rows || ! hash_equals( hash( 'sha256', maybe_serialize( $value ) ), $rows[0]['checksum'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Export current state without rebuilding whole legacy arrays in PHP.
 *
 * @param float $budget_seconds Worker budget, checked after every packet.
 * @return array Existing update-step response.
 */
function ai4seo_export_post_state_to_legacy_options( float $budget_seconds = 2.0 ): array {
	global $wpdb;
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state || ! in_array( $state['phase'] ?? '', array( 'active', 'export_wait', 'export', 'legacy' ), true ) ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'export_storage_unavailable' );
	}
	if ( 'legacy' === $state['phase'] ) {
		return ai4seo_plugin_update_step_result( 'completed' );
	}
	if ( isset( $state['building_generation'] ) ) {
		ai4seo_schedule_generation_status_summary_rebuild( false );
		return ai4seo_plugin_update_step_result( 'deferred', 'background_analysis_in_progress' );
	}
	$directory = ai4seo_post_state_export_directory();
	if ( null === $directory || ! ( $wpdb->dbh instanceof mysqli ) ) {
		return ai4seo_plugin_update_step_result( 'blocked', 'export_stream_unavailable' );
	}
	$lock = ai4seo_get_posts_table_analysis_database_lock_name();
	if ( ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return ai4seo_plugin_update_step_result( 'deferred', 'analysis_busy' );
	}
	try {
		if ( in_array( $state['phase'], array( 'active', 'export_wait' ), true ) ) {
			$next = $state;
			if ( 'active' === $state['phase'] ) {
				$next['phase']         = 'export_wait';
				$next['source']        = 0;
				$next['manifest']      = array();
				$next['export']        = array();
				$next['work_revision'] = 0;
				unset( $next['export_work_revision'] );
				if ( ! ai4seo_post_state_transaction(
					static function ( array $current ) use ( $state, $next ): bool {
						return $current === $state && ai4seo_save_post_state_storage_status( $state, $next );
					}
				) ) {
					return ai4seo_plugin_update_step_result( 'deferred', 'export_pause_failed' );
				}
				$state = $next;
			}
			$next['phase'] = 'export';
			$frozen        = ai4seo_post_state_transaction(
				static function ( array $current ) use ( $state, $next ): bool {
					$active = ai4seo_post_state_query( 'SELECT 1 FROM {{work}} WHERE kind IN (5,14) AND expires_at>{{now}} LIMIT 1', array( 'now' => time() ), true );
					return $current === $state && array() === $active && ai4seo_save_post_state_storage_status( $state, $next );
				}
			);
			if ( ! $frozen ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'active_generations_or_export_freeze_failed' );
			}
			$state = $next;
		}
		$sources      = ai4seo_post_state_migration_sources();
		$source_count = count( $sources );
		$work_source  = array_search( AI4SEO_ANALYSIS_RECONCILIATION_OPTION_NAME, $sources, true );
		$deadline     = microtime( true ) + max( 0.01, min( 10.0, $budget_seconds ) );
		while ( $state['source'] === $source_count || microtime( true ) < $deadline ) {
			// An earlier source checkpoint may be valid while the work snapshot became stale.
			$state = ai4seo_read_post_state_storage_status();
			if ( null === $state || 'export' !== $state['phase'] ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'export_state_changed' );
			}
			if ( $state['source'] === $source_count ) {
				// The final source is already in the verified manifest. Deleting its
				// spool before clearing the checkpoint also handles an interrupted cleanup.
				if ( ! empty( $state['export'] ) ) {
					wp_delete_file( $directory . '/' . $state['export']['run'] . '.bin' );
				}
				if ( ( $state['export_work_revision'] ?? -1 ) !== ( $state['work_revision'] ?? 0 ) ) {
					$next           = $state;
					$next['source'] = $work_source;
					$next['export'] = array();
					if ( ! ai4seo_save_post_state_export_progress( $state, $next ) ) {
						return ai4seo_plugin_update_step_result( 'deferred', 'export_checkpoint_failed' );
					}
					continue;
				}
				do_action( 'ai4seo_post_state_checkpoint', 'before_export_cutover' );
				$reason    = 'export_cutover_failed';
				$published = ai4seo_post_state_transaction(
					static function ( array $current ) use ( $state, &$reason ): bool {
						if ( $state !== $current ) {
							return false;
						}
						if ( ! ai4seo_verify_post_state_source_manifest( $state ) || ! ai4seo_verify_post_state_export_summary_companions( $state ) ) {
							$reason = 'export_verification_failed';
							return false;
						}
						$next           = $current;
						$next['phase']  = 'legacy';
						$next['export'] = array();
						return ai4seo_save_post_state_storage_status( $current, $next );
					}
				);
				if ( $published ) {
					do_action( 'ai4seo_post_state_checkpoint', 'after_export_cutover' );
					return ai4seo_plugin_update_step_result( 'completed' );
				}
				return ai4seo_plugin_update_step_result( 'export_verification_failed' === $reason ? 'blocked' : 'deferred', $reason );
			}
			$name       = $sources[ $state['source'] ];
			$checkpoint = $state['export'] ?? array();
			if ( ! $checkpoint || $checkpoint['source'] !== $state['source'] || ( $state['source'] === $work_source && ( $state['work_revision'] ?? 0 ) !== $checkpoint['revision'] ) ) {
				// A stale work revision or an advanced source never resumes this file.
				// Remove it before publishing its replacement so a crash cannot orphan it.
				if ( $checkpoint ) {
					wp_delete_file( $directory . '/' . $checkpoint['run'] . '.bin' );
				}
				$next                  = $state;
				$next['work_revision'] = $state['work_revision'] ?? 0;
				$next['export']        = array(
					'version'  => 1,
					'run'      => bin2hex( random_bytes( 16 ) ),
					'source'   => $state['source'],
					'group'    => 0,
					'cursor'   => 0,
					'count'    => 0,
					'bytes'    => 0,
					'hash'     => hash( 'sha256', '' ),
					'complete' => false,
					'revision' => $state['work_revision'] ?? 0,
				);
				// Initial unreleased export checkpoints have no revision; upgrade under the same fence.
				if ( ! isset( $state['work_revision'] ) ) {
					if ( ! ai4seo_post_state_transaction(
						static function ( array $current ) use ( $state, $next ): bool {
							return $current === $state && ai4seo_save_post_state_storage_status( $state, $next );
						}
					) ) {
						return ai4seo_plugin_update_step_result( 'deferred', 'export_checkpoint_failed' );
					}
				} elseif ( ! ai4seo_save_post_state_export_progress( $state, $next ) ) {
					return ai4seo_plugin_update_step_result( 'deferred', 'export_checkpoint_failed' );
				}
				$state = $next;
			}
			$path   = $directory . '/' . $state['export']['run'] . '.bin';
			$groups = ai4seo_post_state_export_groups( $name, $state );
			if ( null === $groups ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'export_groups_unreadable' );
			}
			$result = ai4seo_stream_post_state_export_batch( $state, $groups, $deadline, $path );
			if ( 'ready' !== $result ) {
				$current = ai4seo_read_post_state_storage_status();
				if ( $state['source'] === $work_source && null !== $current && ( $current['work_revision'] ?? 0 ) !== $state['export']['revision'] ) {
					$result = 'deferred';
				}
				return ai4seo_plugin_update_step_result( 'deferred' === $result ? 'deferred' : 'blocked', 'deferred' === $result ? 'export_in_progress' : $result, $state['source'] );
			}
			$checkpoint = $state['export'];
			if ( $state['source'] === $work_source && $checkpoint['revision'] !== $state['work_revision'] ) {
				continue;
			}
			if ( ! ai4seo_publish_post_state_export_stream( $name, $path, $checkpoint )
				|| ( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name && ! ai4seo_publish_post_state_export_summary_companions( $groups, $checkpoint ) ) ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'export_publish_failed', $state['source'] );
			}
			$digest = ai4seo_verify_post_state_export_stream( $name, $checkpoint );
			if ( null === $digest ) {
				return ai4seo_plugin_update_step_result( 'blocked', 'export_digest_failed', $state['source'] );
			}
			// Retain the original hook for the first pass, not for repeated final work refreshes.
			if ( ! isset( $state['manifest'][ $state['source'] ] ) ) {
				do_action( 'ai4seo_post_state_checkpoint', 'before_export_checkpoint' );
			}
			$next                                 = $state;
			$next['manifest'][ $state['source'] ] = $digest;
			if ( $state['source'] === $work_source ) {
				$next['export_work_revision'] = $checkpoint['revision'];
			}
			++$next['source'];
			if ( $state['source'] === $work_source && count( $next['manifest'] ) === count( $sources ) ) {
				$next['source'] = count( $sources );
			}
			if ( ! ai4seo_save_post_state_export_progress( $state, $next ) ) {
				return ai4seo_plugin_update_step_result( 'deferred', 'export_checkpoint_failed' );
			}
			wp_delete_file( $path );
			$state = $next;
		}
		return ai4seo_plugin_update_step_result( 'deferred', 'export_in_progress', $state['source'] );
	} finally {
		ai4seo_release_database_advisory_lock( $lock );
	}
}
