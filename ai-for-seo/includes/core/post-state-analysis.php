<?php
/**
 * Published analysis generations and bounded background change replay.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route scan writes to the unpublished generation without changing ordinary readers.
 *
 * @param array $state Validated storage state.
 * @return array Write routing only; never persist this derived state.
 */
function ai4seo_get_analysis_write_storage_state( array $state ): array {
	if ( isset( $state['building_generation'] ) ) {
		$state['generation'] = $state['building_generation'];
	}
	return $state;
}

/**
 * Whether a complete published result is retained during a separate scan.
 *
 * @return bool
 */
function ai4seo_has_background_analysis(): bool {
	$state = ai4seo_read_post_state_storage_status();
	return null !== $state && ! empty( $state['building_generation'] );
}

/**
 * Probe the saved scan bounds without treating a failed read as exhaustion.
 *
 * @param int $cursor Last committed physical post ID.
 * @param int $upper_post_id Fixed upper bound captured when the scan started.
 * @return array|null At most one remaining row, an empty array at exhaustion, or null on failure.
 */
function ai4seo_read_posts_table_analysis_tail( int $cursor, int $upper_post_id ): ?array {
	return ai4seo_post_state_query(
		'SELECT ID FROM {{posts}} WHERE ID>{{cursor}} AND ID<={{upper}} LIMIT 1',
		array(
			'cursor' => $cursor,
			'upper'  => $upper_post_id,
		),
		true
	);
}

/**
 * Preserve a completed pre-upgrade result before the coordinator clears its markers.
 *
 * @param array $progress Completed progress observed under the analysis fence.
 * @param int   $cursor Last committed physical ID.
 * @return bool Whether preservation or the absence of a completed result was verified.
 */
function ai4seo_adopt_published_analysis( array $progress, int $cursor ): bool {
	$remaining = ai4seo_read_posts_table_analysis_tail( $cursor, (int) $progress['upper_post_id'] );
	if ( null === $remaining ) {
		return false;
	}
	if ( $remaining ) {
		return true;
	}
	return ai4seo_post_state_transaction(
		static function ( array $state ) use ( $progress ): bool {
			if ( 'active' !== ( $state['phase'] ?? '' ) ) {
				return false;
			}
			$next                       = $state;
			$next['published_progress'] = $state['published_progress'] ?? $progress;
			return ai4seo_save_post_state_storage_status( $state, $next );
		},
		ai4seo_get_posts_table_analysis_database_lock_name()
	);
}

/**
 * Keep unreadable records on the ordinary repair queue after background isolation.
 *
 * @param int   $post_id Isolated identity.
 * @param array $entry Verified replay entry.
 * @return bool
 */
function ai4seo_retain_background_analysis_repair( int $post_id, array $entry ): bool {
	$current = ai4seo_read_post_state_work( 1, array( $post_id ) );
	if ( null === $current ) {
		return false;
	}
	return $current || ai4seo_post_state_insert_rows(
		'work',
		array( array( 1, $post_id, $entry['token'], time() + AI4SEO_ANALYSIS_RECONCILIATION_RETRY_DELAY, wp_json_encode( $entry ) ) ),
		'work'
	);
}

/**
 * Acknowledge only replay tokens that were actually checked under the writer fence.
 *
 * @param array $batch Verified token batch, including individually isolated rows.
 * @return bool
 */
function ai4seo_acknowledge_background_analysis_work( array $batch ): bool {
	if ( ! isset( $GLOBALS['ai4seo_post_state_transaction'] ) ) {
		return false;
	}
	foreach ( $batch as $id => $entry ) {
		if ( null === ai4seo_post_state_query(
			'DELETE FROM {{work}} WHERE kind={{kind}} AND post_id={{id}} AND token={{token}}',
			array(
				'kind'  => AI4SEO_ANALYSIS_BUILD_WORK_KIND,
				'id'    => (int) $id,
				'token' => $entry['token'],
			)
		) ) {
			return false;
		}
	}
	return true;
}

/**
 * Replay a bounded tail and publish routing, progress and cooldown in one transaction.
 *
 * The final empty-journal check shares the writer fence with all status hooks. A
 * newer marker either delays publication or belongs to the newly published live
 * result. A stopped request never exposes a partly built generation.
 *
 * @param bool $pending Receives whether more replay work is still outstanding.
 * @return bool Whether the batch/publication committed successfully.
 */
function ai4seo_publish_indexed_analysis( bool &$pending ): bool {
	$pending = false;
	$lock    = ai4seo_get_posts_table_analysis_database_lock_name();
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
		return false;
	}
	$state = ai4seo_read_post_state_storage_status();
	if ( null === $state || 'active' !== ( $state['phase'] ?? '' ) ) {
		return false;
	}
	$snapshot = ai4seo_read_authoritative_environmental_variables_snapshot();
	if ( empty( $snapshot['success'] ) ) {
		return false;
	}
	$progress = $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_PROGRESS ];
	if ( 2 !== (int) ( $progress['version'] ?? 0 ) || ! empty( $progress['failure'] ) ) {
		return false;
	}
	$remaining = ai4seo_read_posts_table_analysis_tail(
		(int) $snapshot['values'][ AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID ],
		(int) $progress['upper_post_id']
	);
	if ( null === $remaining || $remaining ) {
		return false;
	}
	if ( isset( $state['building_generation'] ) ) {
		$batch = ai4seo_read_post_state_work( AI4SEO_ANALYSIS_BUILD_WORK_KIND );
		if ( null === $batch || ( $batch && ! ai4seo_reconcile_indexed_analysis_batch( $batch, AI4SEO_ANALYSIS_BUILD_WORK_KIND ) ) ) {
			return false;
		}
	}
	$published = false;
	$result    = ai4seo_post_state_transaction(
		static function ( array $current ) use ( &$pending, &$published ): bool {
			if ( 'active' !== ( $current['phase'] ?? '' ) ) {
				return false;
			}
			if ( isset( $current['building_generation'] ) ) {
				$work = ai4seo_post_state_query( 'SELECT post_id FROM {{work}} WHERE kind={{kind}} LIMIT 1', array( 'kind' => AI4SEO_ANALYSIS_BUILD_WORK_KIND ), true );
				if ( null === $work ) {
					return false;
				}
				if ( $work ) {
					$pending = true;
					return ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, 'idle', false );
				}
				$current['retired_generation'] = $current['generation'];
				$current['generation']         = $current['building_generation'];
				unset( $current['building_generation'] );
			}
			do_action( 'ai4seo_post_state_checkpoint', 'before_analysis_publish' );
			$published = ai4seo_commit_analysis_refresh_completion( $current );
			return $published;
		},
		$lock
	);
	if ( $result && $published ) {
		do_action( 'ai4seo_post_state_checkpoint', 'after_analysis_publish' );
	}
	return $result;
}
