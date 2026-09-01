<?php
/**
 * Provides the latest generated-metadata activity data.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region LATEST ACTIVITY ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to add an entry to the latest activity log
 *
 * @param int    $post_id The post id this entry refers to.
 * @param string $status The status of the action (success, error).
 * @param string $action The action that was performed ("metadata-manually-generated", "metadata-bulk-generated", "attachment-attributes-manually-generated", "attachment-attributes-bulk-generated").
 * @param int    $cost The cost of the action in credits.
 * @param string $details The details of the action.
 * @return bool
 */
function ai4seo_add_latest_activity_entry( int $post_id, string $status, string $action, int $cost = 0, string $details = '' ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 581297488, 'Prevented loop', true );
		return false;
	}

	$new_entry['timestamp'] = time();

	// check if the post_id is a valid post id.
	if ( $post_id < 0 ) {
		ai4seo_debug_message( 231410125, 'Invalid post id in latest activity log.', true );
		return false;
	}

	// check if the status is one of the allowed statuses (success, error).
	$status = sanitize_text_field( $status );

	if ( ! in_array( $status, array( 'success', 'error' ), true ) ) {
		return false;
	}

	$action = sanitize_text_field( $action );

	if ( ! in_array( $action, array( 'metadata-manually-generated', 'metadata-bulk-generated', 'attachment-attributes-manually-generated', 'attachment-attributes-bulk-generated' ), true ) ) {
		ai4seo_debug_message( 241410125, 'Invalid action in latest activity log.', true );
		return false;
	}

	// check if the cost is valid.
	if ( $cost < 0 ) {
		ai4seo_debug_message( 251410125, 'Invalid cost in latest activity log.', true );
		return false;
	}

	// read additional post data.
	$post_type = get_post_type( $post_id );

	// check post type (alphanumeric, - and _ allowed).
	$post_type = sanitize_text_field( $post_type );

	if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $post_type ) ) {
		return false;
	}

	// check title.
	$title = sanitize_text_field( get_the_title( $post_id ) );
	$title = ai4seo_mb_substr( $title, 0, 50 );
	$title = sanitize_text_field( $title );

	if ( 'attachment' === $post_type ) {
		$url = get_edit_post_link( $post_id );
	} else {
		$url = get_permalink( $post_id );
	}

	if ( ! $url ) {
		$url = '';
	}

	// check url.
	$url = sanitize_url( $url );

	if ( $url ) {
		$url = esc_url( $url );
	}

	// check details.
	$details = sanitize_text_field( $details );

	// build the new entry.
	$new_entry = array(
		'timestamp' => time(),
		'post_id'   => $post_id,
		'post_type' => $post_type,
		'status'    => $status,
		'action'    => $action,
		'cost'      => $cost,
		'title'     => $title,
		'url'       => $url,
		'details'   => $details,
	);

	$maximum_attempts = 5;

	// Rebuild the bounded log from the latest authoritative row after every concurrent-write conflict.
	for ( $attempt = 1; $attempt <= $maximum_attempts; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_LATEST_ACTIVITY_OPTION_NAME );

		if ( null === $option_snapshot ) {
			return false;
		}

		$latest_activity = $option_snapshot['exists'] && is_array( $option_snapshot['value'] )
			? $option_snapshot['value']
			: array();

		array_unshift( $latest_activity, $new_entry );

		// Preserve the established maximum-log behavior while applying it to the latest snapshot.
		if ( count( $latest_activity ) >= AI4SEO_MAX_LATEST_ACTIVITY_LOGS ) {
			array_pop( $latest_activity );
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_LATEST_ACTIVITY_OPTION_NAME,
			$option_snapshot,
			$latest_activity,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( $compare_and_swap_result ) {
			return true;
		}
	}

	return false;
}


/**
 * Filter latest activity entries to objects visible to the current plugin user.
 *
 * Site administrators retain the site-wide operational view. Content users only receive entries
 * for objects they may edit through WordPress's object-level capability mapping.
 *
 * @param array $latest_activity Latest activity entries.
 * @return array Visible latest activity entries in their original order.
 */
function ai4seo_filter_latest_activity_entries_for_current_user( array $latest_activity ): array {
	if ( ai4seo_can_administer_plugin() ) {
		return $latest_activity;
	}

	return array_values(
		array_filter(
			$latest_activity,
			static function ( $latest_activity_entry ): bool {
				return is_array( $latest_activity_entry )
					&& ai4seo_can_edit_post( absint( $latest_activity_entry['post_id'] ?? 0 ) );
			}
		)
	);
}


/**
 * Returns latest activity entries keyed by post ID.
 *
 * @param array $actions Optional activity actions to include.
 * @param array $statuses Optional activity statuses to include.
 * @return array Latest activity entries keyed by post ID.
 */
function ai4seo_get_latest_activity_entries_by_post_id( array $actions = array(), array $statuses = array() ): array {
	$latest_activity = ai4seo_get_option( AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array() );

	if ( ! is_array( $latest_activity ) || ! $latest_activity ) {
		return array();
	}

	$allowed_actions = array();

	foreach ( $actions as $this_action ) {
		$this_action = sanitize_text_field( (string) $this_action );

		if ( '' !== $this_action ) {
			$allowed_actions[ $this_action ] = true;
		}
	}

	$allowed_statuses = array();

	foreach ( $statuses as $this_status ) {
		$this_status = sanitize_text_field( (string) $this_status );

		if ( '' !== $this_status ) {
			$allowed_statuses[ $this_status ] = true;
		}
	}

	$latest_activity_entries_by_post_id = array();

	foreach ( $latest_activity as $this_latest_activity_entry ) {
		if ( ! is_array( $this_latest_activity_entry ) ) {
			continue;
		}

		$this_post_id = absint( $this_latest_activity_entry['post_id'] ?? 0 );

		if ( ! $this_post_id || isset( $latest_activity_entries_by_post_id[ $this_post_id ] ) ) {
			continue;
		}

		$this_action = sanitize_text_field( (string) ( $this_latest_activity_entry['action'] ?? '' ) );
		$this_status = sanitize_text_field( (string) ( $this_latest_activity_entry['status'] ?? '' ) );

		if ( $allowed_actions && ! isset( $allowed_actions[ $this_action ] ) ) {
			continue;
		}

		if ( $allowed_statuses && ! isset( $allowed_statuses[ $this_status ] ) ) {
			continue;
		}

		$latest_activity_entries_by_post_id[ $this_post_id ] = $this_latest_activity_entry;
	}

	return $latest_activity_entries_by_post_id;
}


/**
 * Returns the subtext shown for entries that are present in the latest activity list.
 *
 * @param string $details_onclick JavaScript onclick action that opens the matching editor.
 * @param array  $latest_activity_entry Optional latest activity entry data.
 * @return string HTML.
 */
function ai4seo_get_recent_activity_details_subtext_tag( string $details_onclick, array $latest_activity_entry = array() ): string {
	$details_onclick = trim( $details_onclick );

	if ( '' === $details_onclick ) {
		return '';
	}

	$activity_status   = sanitize_key( (string) ( $latest_activity_entry['status'] ?? '' ) );
	$activity_details  = sanitize_text_field( (string) ( $latest_activity_entry['details'] ?? '' ) );
	$message_css_class = 'ai4seo-gray-message';
	$icon_name         = 'circle';
	$icon_css_class    = 'ai4seo-dark-gray-icon';
	$icon_alt_text     = __( 'Recent activity', 'ai-for-seo' );
	$message_text      = __( 'This entry has recent activity.', 'ai-for-seo' );

	if ( 'success' === $activity_status ) {
		$message_css_class = 'ai4seo-green-message';
		$icon_name         = 'circle-check';
		$icon_css_class    = 'ai4seo-dark-green-icon';
		$icon_alt_text     = __( 'Recently processed by SEO Autopilot', 'ai-for-seo' );
		$message_text      = __( 'SEO Autopilot has recently processed this entry.', 'ai-for-seo' );
	} elseif ( 'error' === $activity_status ) {
		$message_css_class = 'ai4seo-red-message';
		$icon_name         = 'circle-xmark';
		$icon_css_class    = 'ai4seo-red-icon';
		$icon_alt_text     = __( 'Recent SEO Autopilot processing failed', 'ai-for-seo' );
		$message_text      = __( 'Recent SEO Autopilot processing failed.', 'ai-for-seo' );
	}

	$output      = "<div class='ai4seo-sub-info " . esc_attr( $message_css_class ) . "'>";
		$output .= ai4seo_get_svg_tag( $icon_name, esc_html( $icon_alt_text ), $icon_css_class );
		$output .= ' ';
		$output .= esc_html( $message_text );

	if ( 'error' === $activity_status && '' !== $activity_details ) {
		$output .= ' ';
		$output .= esc_html( $activity_details );
	}

		$output .= ' ';
		$output .= ai4seo_get_small_icon_button_tag( '', esc_html__( 'Check details', 'ai-for-seo' ), '', $details_onclick );
	$output     .= '</div>';

	return $output;
}


// endregion
// ___________________________________________________________________________________________.
