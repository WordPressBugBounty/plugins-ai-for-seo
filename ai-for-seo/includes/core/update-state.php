<?php
/**
 * Checked, resumable plugin update state and local diagnostics.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the site-owned migration lock, independent of account and queue leases. */
function ai4seo_get_plugin_update_lock_name(): string {
	return 'ai4seo_update_' . substr( hash( 'sha256', ai4seo_get_site_options_request_cache_scope() ), 0, 48 );
}

/**
 * Inspect persisted installation history without converting unknown data into fresh-install defaults.
 *
 * @return array Classification, source version and exact environmental snapshot.
 */
function ai4seo_inspect_plugin_update_origin(): array {
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	$result   = array(
		'status'   => 'unknown',
		'source'   => '',
		'snapshot' => $snapshot,
	);
	if ( null === $snapshot || ( $snapshot['exists'] && ! is_array( $snapshot['value'] ) ) ) {
		return $result;
	}
	$raw     = $snapshot['value'] ?? array();
	$version = $raw[ AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION ] ?? null;
	if ( is_string( $version ) && preg_match( '/^[0-9]+(?:\.[0-9]+){1,3}$/D', $version ) && '0.0.0' !== $version ) {
		$result['status'] = version_compare( $version, AI4SEO_PLUGIN_VERSION_NUMBER, '>' ) ? 'newer' : 'known';
		$result['source'] = $version;
		return $result;
	}
	// Missing modern history may still belong to a public legacy installation.
	$legacy = ai4seo_get_raw_option_snapshot( '_ai4seo_version' );
	if ( null === $legacy ) {
		return $result;
	}
	if ( $legacy['exists'] && is_string( $legacy['value'] ) && preg_match( '/^[0-9]+(?:\.[0-9]+){1,3}$/D', $legacy['value'] ) ) {
		$result['source'] = $legacy['value'];
		$result['status'] = version_compare( $result['source'], AI4SEO_PLUGIN_VERSION_NUMBER, '>' ) ? 'newer' : 'known';
		return $result;
	}
	if ( null !== $version && '0.0.0' !== $version ) {
		return $result;
	}
	if ( 'pending' === ( $raw[ AI4SEO_ENVIRONMENTAL_VARIABLE_WELCOME_NOTIFICATION_STATE ] ?? null ) ) {
		$result['status'] = 'fresh';
		return $result;
	}
	$names   = array_unique(
		array_merge(
			array( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, AI4SEO_SETTINGS_OPTION_NAME, AI4SEO_NOTIFICATIONS_OPTION_NAME, AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME ),
			AI4SEO_WELCOME_LEGACY_HISTORY_OPTIONS,
			AI4SEO_ALL_POST_ID_OPTIONS
		)
	);
	$history = ai4seo_get_raw_option_snapshots( array_values( $names ) );
	if ( null !== $history && 'pending' === ai4seo_classify_welcome_install_context( $history ) ) {
		$result['status'] = 'fresh';
	}
	return $result;
}

/**
 * Validate the same persisted step contract before reading and publishing it.
 *
 * @param mixed $step Worker result or stored step.
 * @return bool Whether every required field can be read without normalization.
 */
function ai4seo_is_valid_plugin_update_step( $step ): bool {
	if ( ! is_array( $step )
		|| ! in_array( $step['status'] ?? null, array( 'completed', 'pending', 'deferred', 'blocked', 'failed' ), true )
		|| ! is_string( $step['reason'] ?? null ) || ! is_int( $step['next_retry'] ?? null )
		|| ! is_int( $step['cursor'] ?? null ) || $step['cursor'] < 0
		|| ! is_int( $step['issues'] ?? null ) || $step['issues'] < 0
		|| ! is_array( $step['examples'] ?? null ) || count( $step['examples'] ) > 20 ) {
		return false;
	}
	foreach ( $step['examples'] as $id ) {
		if ( ! is_int( $id ) || $id < 0 ) {
			return false;
		}
	}
	return true;
}

/**
 * Read the additive progress format; never replace an unreadable or future format.
 *
 * @param array|null $snapshot Exact raw snapshot for the following checked publication.
 * @return array|null Valid progress or null for unknown storage.
 */
function ai4seo_read_plugin_update_progress( ?array &$snapshot = null ): ?array {
	$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_PLUGIN_UPDATE_PROGRESS_OPTION_NAME );
	if ( null === $snapshot ) {
		return null;
	}
	if ( ! $snapshot['exists'] ) {
		return array(
			'version' => 1,
			'target'  => AI4SEO_PLUGIN_VERSION_NUMBER,
			'steps'   => array(),
		);
	}
	$value = $snapshot['value'];
	if ( ! is_array( $value ) || 1 !== ( $value['version'] ?? null ) || ! is_string( $value['target'] ?? null ) || ! preg_match( '/^[0-9]+(?:\.[0-9]+){1,3}$/D', $value['target'] ) || ! is_array( $value['steps'] ?? null ) ) {
		return null;
	}
	foreach ( $value['steps'] as $key => $step ) {
		if ( ! is_string( $key ) || ! ai4seo_is_valid_plugin_update_step( $step ) ) {
			return null;
		}
	}
	return $value;
}

/**
 * Build a bounded step result. Reasons contain identifiers, never customer content.
 *
 * @param string $status Completion state.
 * @param string $reason Diagnostic identifier.
 * @param int    $cursor Last safely classified source ID.
 * @param int    $issues Unresolved rows in the current pass.
 * @param array  $examples Bounded source IDs.
 * @return array Step state.
 */
function ai4seo_plugin_update_step_result( string $status, string $reason = '', int $cursor = 0, int $issues = 0, array $examples = array() ): array {
	return array(
		'status'     => $status,
		'reason'     => $reason,
		'cursor'     => $cursor,
		'issues'     => $issues,
		'examples'   => array_slice( array_values( array_unique( $examples ) ), 0, 20 ),
		'next_retry' => 'completed' === $status ? 0 : time() + ( 'blocked' === $status ? HOUR_IN_SECONDS : MINUTE_IN_SECONDS ),
	);
}

/**
 * Run one independent step and publish only while the current connection still owns the lock.
 *
 * @param string   $name Stable step name.
 * @param callable $callback Receives the last committed step state.
 * @param bool     $force Retry an unresolved step now; completed steps remain completed.
 * @param bool     $recheck Revalidate a completed prerequisite against current evidence.
 * @return bool Whether the step is durably completed.
 */
function ai4seo_run_plugin_update_step( string $name, callable $callback, bool $force = false, bool $recheck = false ): bool {
	$lock_name = ai4seo_get_plugin_update_lock_name();
	$progress  = ai4seo_read_plugin_update_progress( $snapshot );
	if ( null === $progress || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name ) ) {
		return false;
	}
	if ( AI4SEO_PLUGIN_VERSION_NUMBER !== $progress['target'] ) {
		// A public version transition starts a new set of steps without interpreting future versions.
		if ( version_compare( $progress['target'], AI4SEO_PLUGIN_VERSION_NUMBER, '>' ) ) {
			return false;
		}
		$progress = array(
			'version' => 1,
			'target'  => AI4SEO_PLUGIN_VERSION_NUMBER,
			'steps'   => array(),
		);
	}
	$previous = $progress['steps'][ $name ] ?? ai4seo_plugin_update_step_result( 'pending' );
	if ( ! $recheck && 'completed' === $previous['status'] ) {
		return true;
	}
	if ( ! $force && ! $recheck && isset( $progress['steps'][ $name ] ) && $previous['next_retry'] > time() && $previous['next_retry'] <= time() + HOUR_IN_SECONDS ) {
		return false;
	}
	try {
		$result = $callback( $previous );
	} catch ( Throwable $throwable ) {
		ai4seo_debug_message( 418620980, 'Plugin update step failed: ' . sanitize_key( $name ), true );
		$result = ai4seo_plugin_update_step_result( 'failed', 'exception', $previous['cursor'], $previous['issues'], $previous['examples'] );
	}
	if ( is_bool( $result ) ) {
		$result = ai4seo_plugin_update_step_result( $result ? 'completed' : 'failed', $result ? '' : 'step_failed' );
	}
	if ( ! ai4seo_is_valid_plugin_update_step( $result ) || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name ) ) {
		return false;
	}
	$progress['steps'][ $name ] = $result;
	if ( $snapshot['exists'] && $snapshot['value'] === $progress ) {
		// A successful recheck may leave identical bytes; require the same row and lock instead of a changed-row count.
		return ai4seo_get_raw_option_snapshot( AI4SEO_PLUGIN_UPDATE_PROGRESS_OPTION_NAME ) === $snapshot
			&& ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name )
			&& 'completed' === $result['status'];
	}
	if ( true !== ai4seo_compare_and_swap_option_snapshot( AI4SEO_PLUGIN_UPDATE_PROGRESS_OPTION_NAME, $snapshot, $progress, false ) ) {
		return false;
	}
	return 'completed' === $result['status'];
}

/**
 * Persist only a migration's validated setting keys against their current raw snapshot.
 *
 * @param array      $changes Required compatibility changes.
 * @param array|null $expected Optional planning snapshot; requires the update lock.
 * @return bool Whether the exact patch is durable.
 */
function ai4seo_patch_plugin_update_settings( array $changes, ?array $expected = null ): bool {
	$result = ai4seo_patch_settings( $changes, $expected, true, null !== $expected ? ai4seo_get_plugin_update_lock_name() : '' );
	return 'saved' === $result['status'];
}

/** Return raw supported settings without discarding unrecognized or invalid members. */
function ai4seo_read_plugin_update_settings(): ?array {
	return ai4seo_decode_settings_snapshot( ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME ) );
}

/**
 * Adapt the existing legacy-cache outcome to a resumable step without changing strict callers.
 *
 * @param array      $result Existing batch outcome.
 * @param array|null $previous Opt-in tolerant scan state.
 * @return array Existing outcome with an additional step result.
 */
function ai4seo_finish_legacy_cache_update_batch( array $result, ?array $previous ): array {
	if ( null === $previous ) {
		unset( $result['cursor'], $result['issues'], $result['examples'] );
		return $result;
	}
	if ( $result['complete'] ) {
		$result['step'] = ai4seo_plugin_update_step_result( 'completed' );
	} elseif ( $result['success'] ) {
		$result['step'] = ai4seo_plugin_update_step_result( 'blocked' === $result['status'] ? 'blocked' : 'pending', 'legacy_data_retained', $result['cursor'], $result['issues'], $result['examples'] );
	} else {
		$result['step'] = ai4seo_plugin_update_step_result( 'failed', $result['status'], $previous['cursor'], $previous['issues'], $previous['examples'] );
	}
	return $result;
}

/**
 * Preserve a destination already chosen by the user; only valid legacy conversions may replace old shapes.
 *
 * @param array $changes Planned compatibility values.
 * @param array $source Settings observed while planning the group.
 * @return bool Whether this independent group is durably applied.
 */
function ai4seo_apply_plugin_update_setting_group( array $changes, array $source ): bool {
	foreach ( $changes as $key => $value ) {
		if ( array_key_exists( $key, $source ) ) {
			$observed = ai4seo_normalize_boolean_setting_value( $key, $source[ $key ] );
			if ( ! ai4seo_validate_setting_value( $key, $observed ) ) {
				return false;
			}
			unset( $changes[ $key ] );
		}
	}
	$current = ai4seo_read_plugin_update_settings();
	if ( null === $current ) {
		return false;
	}
	foreach ( $changes as $key => $value ) {
		if ( array_key_exists( $key, $current ) !== array_key_exists( $key, $source )
			|| ( array_key_exists( $key, $current ) && $current[ $key ] !== $source[ $key ] ) ) {
			return false;
		}
	}
	return ai4seo_patch_plugin_update_settings( $changes, $current );
}

/**
 * Rename a legacy option only when its destination is missing or already identical.
 *
 * @param string $old_name Retained source option.
 * @param string $new_name Canonical destination option.
 * @return bool Whether the source was safely transferred.
 */
function ai4seo_migrate_plugin_update_option( string $old_name, string $new_name ): bool {
	$queue_lock = ai4seo_get_posts_table_analysis_database_lock_name();
	$owned      = ai4seo_is_database_advisory_lock_owned_by_current_connection( $queue_lock );
	if ( ! $owned && ! ai4seo_acquire_database_advisory_lock( $queue_lock ) ) {
		return false;
	}
	try {
		$snapshots = ai4seo_get_raw_option_snapshots( array( $old_name, $new_name ) );
		if ( null === $snapshots || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_plugin_update_lock_name() ) ) {
			return false;
		}
		$source = $snapshots[ $old_name ];
		$target = $snapshots[ $new_name ];
		if ( ! $source['exists'] ) {
			return true;
		}
		// Do not copy malformed operational collections into a modern queue.
		if ( in_array( $new_name, AI4SEO_ALL_POST_ID_OPTIONS, true ) ) {
			$inspection_snapshot                = $source;
			$inspection_snapshot['option_name'] = $new_name;
			$inspection                         = ai4seo_inspect_analysis_post_id_option( $new_name, $inspection_snapshot );
			if ( ! $inspection['complete'] || ! $inspection['read_succeeded'] ) {
				return false;
			}
		} elseif ( ! is_array( $source['value'] ) ) {
			return false;
		}
		if ( $target['exists'] && $source['value'] !== $target['value'] ) {
			return false;
		}
		if ( ! $target['exists'] && true !== ai4seo_compare_and_swap_option_snapshot( $new_name, $target, $source['value'], false ) ) {
			return false;
		}
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_plugin_update_lock_name() ) ) {
			return false;
		}
		return true === ai4seo_compare_and_delete_option_snapshot( $old_name, $source );
	} finally {
		if ( ! $owned ) {
			ai4seo_release_database_advisory_lock( $queue_lock );
		}
	}
}

/**
 * Transfer old account credentials only when both the source and destination are unambiguous.
 *
 * @return bool Whether the compatibility transfer completed without replacing newer account data.
 */
function ai4seo_migrate_plugin_update_account_data(): bool {
	$api = ai4seo_robhub_api( true );
	if ( ! $api || ! $api->is_initialized ) {
		return false;
	}
	foreach ( array( 'ai4seo_robhub_auth_data', AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME ) as $source_name ) {
		$snapshots = ai4seo_get_raw_option_snapshots( array_values( array_unique( array( $source_name, AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME ) ) ) );
		if ( null === $snapshots ) {
			return false;
		}
		$target = $snapshots[ AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME ];
		$source = $snapshots[ $source_name ];
		if ( $target['exists'] && ! is_array( $target['value'] ) ) {
			return false;
		}
		$raw      = $target['exists'] ? $target['value'] : array();
		$combined = AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME === $source_name;
		if ( $combined && ! array_key_exists( $api::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA, $raw ) ) {
			continue;
		}
		if ( ! $source['exists'] ) {
			continue;
		}
		$credentials = $combined ? $raw[ $api::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA ] : $source['value'];
		if ( $combined && array() === $credentials ) {
			continue;
		}
		if ( ! is_array( $credentials ) || array( 0, 1 ) !== array_keys( $credentials ) ) {
			return false;
		}
		$changes = array();
		foreach ( array( $api::ENVIRONMENTAL_VARIABLE_API_USERNAME, $api::ENVIRONMENTAL_VARIABLE_API_PASSWORD ) as $index => $key ) {
			$value = $credentials[ $index ];
			if ( ! is_string( $value ) || '' === $value || sanitize_text_field( $value ) !== $value
				|| ( array_key_exists( $key, $raw ) && '' !== $raw[ $key ] && $value !== $raw[ $key ] ) ) {
				return false;
			}
			$changes[ $key ] = $value;
		}
		if ( ! $api->apply_plugin_update_environmental_changes( $target, $changes, $combined ? array( $api::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA ) : array() ) ) {
			return false;
		}
		if ( ! $combined && ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_plugin_update_lock_name() )
			|| true !== ai4seo_compare_and_delete_option_snapshot( $source_name, $source ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Read context-specific update prerequisites without treating retained historical rows as a global blocker.
 *
 * @param string $context Metadata or attachment attributes.
 * @param bool   $automatic Whether old automatic-generation preferences are required.
 * @return bool Whether the required settings migrations are known to be usable.
 */
function ai4seo_is_plugin_update_context_ready( string $context, bool $automatic = false ): bool {
	$progress = ai4seo_read_plugin_update_progress();
	if ( null === $progress || AI4SEO_PLUGIN_VERSION_NUMBER !== $progress['target'] ) {
		return false;
	}
	if ( ! $automatic && isset( $progress['steps']['history'] ) && 'completed' !== $progress['steps']['history']['status'] ) {
		$settings = ai4seo_read_plugin_update_settings();
		$key      = 'metadata' === $context ? AI4SEO_SETTING_ACTIVE_META_TAGS : AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES;
		if ( null === $settings || ! array_key_exists( $key, $settings ) || ! ai4seo_validate_setting_value( $key, $settings[ $key ] ) ) {
			return false;
		}
	}
	$required = 'metadata' === $context ? array( 'settings_220' ) : array( 'attachment_formats' );
	if ( $automatic ) {
		$required       = array_merge( $required, array( 'history', 'settings_200' ) );
		$legacy_options = 'metadata' === $context
			? array( 'ai4seo_missing_seo_data_post_ids', 'ai4seo_failed_to_fill_post_ids', 'ai4seo_failed_to_fill_metadata_post_ids' )
			: array( 'ai4seo_failed_to_fill_attributes_attachment_post_ids' );
		foreach ( $legacy_options as $option_name ) {
			$legacy = ai4seo_get_raw_option_snapshot( $option_name );
			if ( null === $legacy || ( $legacy['exists'] && array() !== $legacy['value'] ) ) {
				return false;
			}
		}
	}
	foreach ( $required as $key ) {
		if ( isset( $progress['steps'][ $key ] ) && 'completed' !== $progress['steps'][ $key ]['status'] ) {
			return false;
		}
	}
	return true;
}

/**
 * Recheck a migration source immediately before its destination writer commits.
 *
 * @param array $context Migration-only source preconditions.
 * @return bool Whether source ownership and bytes are unchanged.
 */
function ai4seo_validate_update_migration_source( array $context ): bool {
	global $wpdb;
	if ( ( isset( $context['legacy_cache_source'] ) || isset( $context['legacy_metadata_source'] ) )
		&& ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_plugin_update_lock_name() ) ) {
		return false;
	}
	if ( isset( $context['legacy_cache_source'] ) ) {
		$row = $context['legacy_cache_source'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact source identity and bytes are checked under the destination lock.
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT post_id, data FROM {$wpdb->prefix}ai4seo_cache WHERE id = %d", $row['source_row_id'] ), ARRAY_A );
		return ! $wpdb->last_error && is_array( $current ) && (string) $row['source_post_id'] === (string) $current['post_id'] && $row['source_data'] === $current['data'];
	}
	if ( isset( $context['legacy_metadata_source'] ) ) {
		$source = $context['legacy_metadata_source'];
		$issues = array();
		$rows   = array();
		ai4seo_read_legacy_active_metadata_by_post_ids( array( $source['post_id'] ), $read, $issues, $rows );
		$current_rows = $rows[ $source['post_id'] ] ?? array();
		return $read && ! $issues && $source['rows'] === $current_rows;
	}
	return true;
}

/**
 * Remove only unchanged legacy snapshots after the destination write was verified.
 *
 * @param array $rows Exact legacy metadata source rows.
 * @return bool Whether every source snapshot was removed.
 */
function ai4seo_delete_migrated_metadata_source_rows( array $rows ): bool {
	global $wpdb;
	foreach ( $rows as $row ) {
		if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_plugin_update_lock_name() ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Exact, validated source bytes prevent cleanup from deleting a concurrent edit.
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_id = %d AND post_id = %d AND BINARY meta_key = BINARY %s AND BINARY meta_value = BINARY %s", $row['meta_id'], $row['post_id'], $row['meta_key'], $row['meta_value'] ) );
		if ( 1 !== $deleted || $wpdb->last_error ) {
			return false;
		}
		ai4seo_invalidate_postmeta_caches( array( (int) $row['post_id'] ) );
	}
	return true;
}

/**
 * Publish a set of derived migration markers without replacing an unreadable container.
 *
 * @param array $changes Derived state changes.
 * @return bool Whether publication succeeded.
 */
function ai4seo_publish_plugin_update_markers( array $changes ): bool {
	return ai4seo_mutate_environmental_variable_overrides(
		static function ( array $raw ) use ( $changes ): array {
			$updated = array_replace( $raw, $changes );
			return array(
				'overrides' => $updated,
				'changed'   => $updated !== $raw,
				'result'    => null,
			);
		},
		false,
		$unused,
		true
	);
}

/**
 * Recognize routine waits without hiding failed checkpoints or unknown update results.
 *
 * @param string $name Update step name.
 * @param array  $step Persisted step result.
 * @return bool Whether this step needs only an informational presentation.
 */
function ai4seo_is_expected_plugin_update_step( string $name, array $step ): bool {
	return 'post_state_storage_v1' === $name && ai4seo_is_valid_plugin_update_step( $step )
		&& 'deferred' === $step['status'] && 0 === $step['issues'] && ! $step['examples']
		&& in_array( $step['reason'], array( 'migration_in_progress', 'active_generations', 'analysis_busy' ), true );
}

/**
 * Read-only diagnostics shared by normal plugin pages and the API-independent recovery screen.
 *
 * @return string Escaped HTML with no refresh loop or automatic mutation.
 */
function ai4seo_get_plugin_update_notice_html(): string {
	$progress = ai4seo_read_plugin_update_progress();
	if ( null === $progress ) {
		return '<div class="notice notice-warning ai4seo-update-status"><p>' . esc_html__( 'Update progress could not be read. Existing data has been retained. Please use Help to inspect the problem.', 'ai-for-seo' ) . '</p></div>';
	}
	if ( version_compare( $progress['target'], AI4SEO_PLUGIN_VERSION_NUMBER, '>' ) ) {
		return '<div class="notice notice-warning ai4seo-update-status"><p>' . esc_html__( 'Stored update progress belongs to a newer plugin version. Affected operations remain restricted and original data has been retained. Please check the installed plugin version.', 'ai-for-seo' ) . '</p></div>';
	}
	$unresolved = array_filter(
		$progress['steps'],
		static function ( array $step ): bool {
			return 'completed' !== $step['status'];
		}
	);
	if ( ! $unresolved ) {
		return '';
	}
	$groups = array(
		'info'    => array(),
		'warning' => array(),
	);
	foreach ( $unresolved as $name => $step ) {
		$groups[ ai4seo_is_expected_plugin_update_step( $name, $step ) ? 'info' : 'warning' ][ $name ] = $step;
	}
	$html    = '';
	$labels  = array(
		'history'               => __( 'Installation history', 'ai-for-seo' ),
		'legacy_cache'          => __( 'Previous generation data', 'ai-for-seo' ),
		'active_metadata'       => __( 'Previous metadata', 'ai-for-seo' ),
		'notifications'         => __( 'Notification preferences', 'ai-for-seo' ),
		'account_maintenance'   => __( 'Account synchronization', 'ai-for-seo' ),
		'account_compatibility' => __( 'Previous account configuration', 'ai-for-seo' ),
		'cache_invalidation'    => __( 'Cached statistics', 'ai-for-seo' ),
		'post_state_storage_v1' => __( 'Post status storage', 'ai-for-seo' ),
	);
	$reasons = array(
		'history_unknown'           => __( 'The previous installation version could not be verified.', 'ai-for-seo' ),
		'settings_unreadable'       => __( 'Stored settings could not be read.', 'ai-for-seo' ),
		'settings_not_committed'    => __( 'Some compatibility settings could not be safely applied.', 'ai-for-seo' ),
		'heavy_operations_disabled' => __( 'Intensive database operations are disabled in your settings.', 'ai-for-seo' ),
		'legacy_data_retained'      => __( 'Original data is retained while its migration is incomplete.', 'ai-for-seo' ),
		'migration_in_progress'     => __( 'Post status storage is being migrated in batches. Saved progress will be resumed.', 'ai-for-seo' ),
		'active_generations'        => __( 'Post status migration is waiting for running generations to finish.', 'ai-for-seo' ),
		'analysis_busy'             => __( 'Another background task is finishing. Update preparation will continue automatically.', 'ai-for-seo' ),
	);
	foreach ( $groups as $type => $steps ) {
		if ( ! $steps ) {
			continue;
		}
		$is_expected = 'info' === $type;
		$title       = $is_expected ? __( 'Update in progress', 'ai-for-seo' ) : __( 'Some update steps are still pending', 'ai-for-seo' );
		$intro       = $is_expected
			? __( 'SOOZ is preparing your existing SEO status data. Analysis will resume after preparation is complete. Your saved progress is retained.', 'ai-for-seo' )
			: __( 'Available functions can still be used. Affected operations remain restricted until their data can be verified. Unreadable original data has been retained.', 'ai-for-seo' );
		$html       .= '<div class="notice notice-' . esc_attr( $type ) . ' ai4seo-update-status" role="status"><p><strong>' . esc_html( $title ) . '</strong></p><p>' . esc_html( $intro ) . '</p><ul>';
		foreach ( $steps as $name => $step ) {
			$label  = $labels[ $name ] ?? __( 'Compatibility settings', 'ai-for-seo' );
			$reason = $reasons[ $step['reason'] ] ?? __( 'This step could not be completed. It will be checked again.', 'ai-for-seo' );
			$html  .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $reason );
			if ( $step['issues'] > 0 ) {
				/* translators: %d: Number of original records requiring attention. */
				$html .= ' ' . esc_html( sprintf( __( 'Affected records: %d.', 'ai-for-seo' ), $step['issues'] ) );
			}
			$html .= '<details><summary>' . esc_html__( 'Diagnostic details', 'ai-for-seo' ) . '</summary><code>' . esc_html( $name . ': ' . $step['reason'] . ( $step['examples'] ? ' (IDs: ' . implode( ', ', $step['examples'] ) . ')' : '' ) ) . '</code></details></li>';
		}
		$guidance = $is_expected
			? __( 'This is a normal update step. No action is needed.', 'ai-for-seo' )
			: __( 'After correcting the data, use “Retry pending update steps” in Help. Automatic retries are limited.', 'ai-for-seo' );
		$html    .= '</ul><p>' . esc_html( $guidance ) . '</p></div>';
	}
	return $html;
}

/** Render local diagnostics when the remote-service component cannot initialize. */
function ai4seo_render_local_update_diagnostics(): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		wp_die( esc_html__( 'You are not allowed to access this page.', 'ai-for-seo' ), '', array( 'response' => 403 ) );
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'SOOZ diagnostics', 'ai-for-seo' ) . '</h1><p>'
		. esc_html__( 'The service component could not be initialized. Local diagnostics remain available. Please check the plugin installation and debug log.', 'ai-for-seo' ) . '</p>';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The shared renderer escapes every value.
	echo ai4seo_get_plugin_update_notice_html();
	echo '</div>';
}

/** Register only the local recovery screen when ordinary API-dependent navigation is unavailable. */
function ai4seo_register_local_update_diagnostics(): void {
	if ( ai4seo_can_administer_plugin() ) {
		add_management_page( __( 'SOOZ diagnostics', 'ai-for-seo' ), __( 'SOOZ diagnostics', 'ai-for-seo' ), 'manage_options', 'ai4seo-local-diagnostics', 'ai4seo_render_local_update_diagnostics' );
	}
}
