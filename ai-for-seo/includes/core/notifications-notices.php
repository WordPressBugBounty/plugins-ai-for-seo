<?php
/**
 * Manages plugin notifications and admin notices.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region NOTIFICATIONS / NOTICES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Register early notice suppression for a plugin admin page hook.
 *
 * @param string $hook_suffix The WordPress admin page hook suffix.
 * @return void
 */
function ai4seo_register_plugin_admin_notice_suppression( string $hook_suffix ): void {
	// Remember suffixes within this request so duplicate menu hooks do not add duplicate callbacks.
	static $registered_hook_suffixes = array();

	// Normalize WordPress' returned hook suffix before building page-specific action names.
	$hook_suffix = trim( $hook_suffix );

	// Ignore failed or duplicate registrations because top-level and submenu pages may overlap.
	if ( '' === $hook_suffix || isset( $registered_hook_suffixes[ $hook_suffix ] ) ) {
		return;
	}

	// Use page-specific hooks so notice suppression runs only on SOOZ admin screens.
	add_action( 'load-' . $hook_suffix, 'ai4seo_suppress_external_admin_notices_on_plugin_page', 0 );
	add_action( 'admin_print_styles-' . $hook_suffix, 'ai4seo_output_admin_notice_suppression_styles', 0 );

	// Track registered suffixes locally to avoid duplicate hook callbacks on the dashboard page.
	$registered_hook_suffixes[ $hook_suffix ] = true;
}


/**
 * Suppress external WordPress admin notices on AI4SEO plugin pages.
 *
 * @return void
 */
function ai4seo_suppress_external_admin_notices_on_plugin_page(): void {
	// Keep the callback harmless if WordPress or another plugin reuses a registered hook suffix unexpectedly.
	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return;
	}

	// Remove each WordPress admin notice channel before it can output third-party notices on SOOZ pages.
	$notice_hook_names = array(
		'admin_notices',
		'all_admin_notices',
		'network_admin_notices',
		'user_admin_notices',
	);

	// Apply the same suppression to all notice channels while keeping the hook list easy to audit.
	foreach ( $notice_hook_names as $this_notice_hook_name ) {
		remove_all_actions( $this_notice_hook_name );
	}
}


/**
 * Hide direct WordPress notice output before first paint on AI4SEO plugin pages.
 *
 * @return void
 */
function ai4seo_output_admin_notice_suppression_styles(): void {
	// Keep this fallback scoped to SOOZ pages in case WordPress calls the style hook unexpectedly.
	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return;
	}

	// Preserve SOOZ notices while hiding direct WordPress notice children early enough to prevent a first-paint flash.
	$preserved_notice_selector_suffix = ':not(.ai4seo-notice):not(.ai4seo-debug-notice)';
	$notice_suppression_selectors     = array(
		'#wpbody-content > .notice' . $preserved_notice_selector_suffix,
		'#wpbody-content > .updated' . $preserved_notice_selector_suffix,
		'#wpbody-content > .error' . $preserved_notice_selector_suffix,
	);

	// Inline output is intentional here because the normal plugin stylesheet is too late for notice flash prevention.
	echo '<style id="ai4seo-admin-notice-suppression">';
		echo wp_kses( implode( ',', $notice_suppression_selectors ), array() ) . '{display:none!important;}';
	echo '</style>';
}


/**
 * Sanitize an API-provided notification message with the remote-content policy.
 *
 * @param string $message Remote notification message HTML.
 * @return string Sanitized message HTML.
 */
function ai4seo_sanitize_remote_notification_message( string $message ): string {
	// Explicit protocols keep relative and fragment links while excluding every non-web scheme.
	return trim(
		wp_kses(
			$message,
			ai4seo_get_remote_notification_allowed_html_tags_and_attributes(),
			array( 'http', 'https' )
		)
	);
}


/**
 * Validate one remote notification image URL independently from remote message HTML.
 *
 * @param mixed $image_url Remote image URL candidate.
 * @return string Canonical HTTP(S) image URL, or an empty string when invalid.
 */
function ai4seo_sanitize_remote_notification_image_url( $image_url ): string {
	if ( ! is_string( $image_url ) || '' === trim( $image_url ) ) {
		return '';
	}

	$image_url = esc_url_raw( trim( $image_url ), array( 'http', 'https' ) );
	$url_parts = wp_parse_url( $image_url );

	if ( '' === $image_url
		|| ! is_array( $url_parts )
		|| empty( $url_parts['scheme'] )
		|| empty( $url_parts['host'] )
		|| ! in_array( strtolower( $url_parts['scheme'] ), array( 'http', 'https' ), true )
		|| isset( $url_parts['user'] )
		|| isset( $url_parts['pass'] ) ) {
		return '';
	}

	return $image_url;
}


/**
 * Build accessible plain text for a locally generated remote discount banner.
 *
 * @param string $message Sanitized discount fallback message.
 * @param string $voucher_code Optional voucher code.
 * @param int    $expire_in Remaining validity in seconds.
 * @return string Banner alternative text.
 */
function ai4seo_get_remote_discount_notification_image_alt_text( string $message, string $voucher_code = '', int $expire_in = 0 ): string {
	$expire_countdown = $expire_in > 0 ? ai4seo_format_seconds_to_hhmmss_or_days_hhmmss( $expire_in ) : '';
	$alt_text         = str_replace( '{{EXPIRE_COUNTDOWN}}', $expire_countdown, ai4seo_sanitize_remote_notification_message( $message ) );
	$alt_text         = preg_replace( '/<br\s*\/?>/i', ' ', $alt_text ) ?? '';
	$alt_text         = html_entity_decode( wp_strip_all_tags( $alt_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$alt_text         = preg_replace( '/\s+/u', ' ', trim( $alt_text ) ) ?? '';
	$voucher_code     = sanitize_text_field( $voucher_code );

	if ( '' !== $voucher_code ) {
		$alt_text .= ' ' . sprintf(
			/* translators: %s: Voucher code. */
			__( 'Enter this voucher code during checkout to apply the discount: %s.', 'ai-for-seo' ),
			$voucher_code
		);
	}

	if ( '' === trim( $alt_text ) ) {
		$alt_text = __( 'Discount offer', 'ai-for-seo' );
	}

	return sanitize_text_field( $alt_text );
}


/**
 * Build a remote discount banner from validated metadata using fixed local attributes.
 *
 * @param array  $notification Remote discount notification data.
 * @param string $message Sanitized fallback message used for accessible text.
 * @return string Locally generated image element, or an empty string when unavailable.
 */
function ai4seo_get_remote_discount_notification_image_tag( array $notification, string $message ): string {
	$image_url = ai4seo_sanitize_remote_notification_image_url( $notification['image'] ?? null );

	if ( '' === $image_url ) {
		return '';
	}

	$voucher_code = isset( $notification['voucher_code'] ) && is_string( $notification['voucher_code'] )
		? $notification['voucher_code']
		: '';
	$expire_in    = isset( $notification['expire_at'] ) && is_numeric( $notification['expire_at'] )
		? max( 0, (int) $notification['expire_at'] - time() )
		: 0;
	$alt_text     = ai4seo_get_remote_discount_notification_image_alt_text( $message, $voucher_code, $expire_in );

	return '<img class="ai4seo-notification-image" src="' . esc_url( $image_url, array( 'http', 'https' ) ) . '" alt="' . esc_attr( $alt_text ) . '">';
}


/**
 * Determine whether sanitized notification markup contains visible text.
 *
 * @param string $message Sanitized notification message HTML.
 * @return bool Whether the message contains visible text.
 */
function ai4seo_notification_message_has_visible_content( string $message ): bool {
	// Decode entities before the text check so non-breaking and zero-width placeholders cannot preserve empty notices.
	$visible_text = html_entity_decode( wp_strip_all_tags( $message ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	return 1 === preg_match( '/[^\s\x{00A0}\x{200B}\x{FEFF}]/u', $visible_text );
}


/**
 * Determine whether one reserved notification identity always represents remote content.
 *
 * @param string $notification_index Notification identifier.
 * @return bool Whether local provenance is forbidden for this identity.
 */
function ai4seo_is_remote_only_notification_index( string $notification_index ): bool {
	return 'discount' === sanitize_key( $notification_index );
}


/**
 * Return request-local notification state for every site visited by this request.
 *
 * @return array Site-scoped notification request state.
 */
function &ai4seo_get_notification_request_state_by_site(): array {
	// The by-reference registry shares each site's verified view and pending derived work across readers and option hooks.
	static $notification_request_state_by_site = array();

	return $notification_request_state_by_site;
}


/**
 * Read the current site's verified notification view from request memory.
 *
 * @param array|null $notifications Receives the cached notification map.
 * @param bool|null  $membership_refresh_pending Receives whether repair changed stored membership.
 * @return bool Whether a verified cached value was available.
 */
function ai4seo_get_notification_request_cache_for_current_site(
	?array &$notifications = null,
	?bool &$membership_refresh_pending = null
): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();
	$current_state                      = $notification_request_state_by_site[ $current_scope ] ?? array();

	if ( empty( $current_state['has_safe_value'] ) || ! isset( $current_state['notifications'] ) || ! is_array( $current_state['notifications'] ) ) {
		return false;
	}

	$notifications              = $current_state['notifications'];
	$membership_refresh_pending = ! empty( $current_state['membership_refresh_pending'] );

	return true;
}


/**
 * Publish one verified notification view for the current site.
 *
 * @param array $notifications Verified notification map.
 * @param bool  $mark_unread_refresh_pending Whether derived unread state may have changed.
 * @param bool  $mark_membership_refresh_pending Whether notification membership changed during repair.
 * @return bool Whether the current site identity was available.
 */
function ai4seo_store_notification_request_cache_for_current_site(
	array $notifications,
	bool $mark_unread_refresh_pending = false,
	bool $mark_membership_refresh_pending = false
): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();
	$current_state                      = $notification_request_state_by_site[ $current_scope ] ?? array();

	$notification_request_state_by_site[ $current_scope ] = array(
		'has_safe_value'             => true,
		'notifications'              => $notifications,
		'unread_refresh_pending'     => $mark_unread_refresh_pending || ! empty( $current_state['unread_refresh_pending'] ),
		'membership_refresh_pending' => $mark_membership_refresh_pending || ! empty( $current_state['membership_refresh_pending'] ),
	);

	return true;
}


/**
 * Clear the current site's cached notification data while preserving pending derived work.
 *
 * @return void
 */
function ai4seo_reset_notification_request_cache_for_current_site(): void {
	$current_scope                      = ai4seo_get_site_options_request_cache_scope();
	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();

	if ( '' === $current_scope ) {
		$notification_request_state_by_site = array();
		return;
	}

	$current_state = $notification_request_state_by_site[ $current_scope ] ?? array();

	$notification_request_state_by_site[ $current_scope ] = array(
		'has_safe_value'             => false,
		'notifications'              => array(),
		'unread_refresh_pending'     => ! empty( $current_state['unread_refresh_pending'] ),
		'membership_refresh_pending' => ! empty( $current_state['membership_refresh_pending'] ),
	);
}


/**
 * Reset notification request memory when the canonical option changes.
 *
 * @param string $option_name Changed option name.
 * @return void
 */
function ai4seo_maybe_reset_notification_request_cache( string $option_name ): void {
	if ( AI4SEO_NOTIFICATIONS_OPTION_NAME === $option_name ) {
		ai4seo_reset_notification_request_cache_for_current_site();
	}
}


/**
 * Mark the current site's unread counter as requiring authoritative reconciliation.
 *
 * @return bool Whether the current site identity was available.
 */
function ai4seo_mark_notification_unread_refresh_pending_for_current_site(): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();

	if ( ! isset( $notification_request_state_by_site[ $current_scope ] ) ) {
		$notification_request_state_by_site[ $current_scope ] = array(
			'has_safe_value'             => false,
			'notifications'              => array(),
			'unread_refresh_pending'     => true,
			'membership_refresh_pending' => false,
		);
	} else {
		$notification_request_state_by_site[ $current_scope ]['unread_refresh_pending'] = true;
	}

	return true;
}


/**
 * Invalidate notification request state after an ordinary or mirrored WordPress option write.
 *
 * @param string $option_name Changed option name.
 * @return void
 */
function ai4seo_handle_notification_option_change( string $option_name ): void {
	if ( AI4SEO_NOTIFICATIONS_OPTION_NAME !== $option_name ) {
		return;
	}

	ai4seo_reset_notification_request_cache_for_current_site();
	ai4seo_mark_notification_unread_refresh_pending_for_current_site();
}


/**
 * Determine whether the current site's unread counter needs to be recomputed.
 *
 * @return bool Whether unread refresh work is pending.
 */
function ai4seo_is_notification_unread_refresh_pending(): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();

	return ! empty( $notification_request_state_by_site[ $current_scope ]['unread_refresh_pending'] );
}


/**
 * Mark the current site's derived unread state as synchronized.
 *
 * @return void
 */
function ai4seo_complete_notification_unread_refresh_for_current_site(): void {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return;
	}

	$notification_request_state_by_site =& ai4seo_get_notification_request_state_by_site();

	if ( isset( $notification_request_state_by_site[ $current_scope ] ) ) {
		$notification_request_state_by_site[ $current_scope ]['unread_refresh_pending']     = false;
		$notification_request_state_by_site[ $current_scope ]['membership_refresh_pending'] = false;
	}
}


/**
 * Track whether unread reconciliation is already deriving a fresh displayable view.
 *
 * @param bool|null $new_state Optional replacement state.
 * @return bool Current synchronization state.
 */
function ai4seo_notification_unread_synchronization_is_active( ?bool $new_state = null ): bool {
	static $is_active = false;

	if ( null !== $new_state ) {
		$is_active = $new_state;
	}

	return $is_active;
}


/**
 * Return request-local routine notification mutation batches for every visited site.
 *
 * @return array Site-scoped mutation batches.
 */
function &ai4seo_get_notification_mutation_batches_by_site(): array {
	static $notification_mutation_batches_by_site = array();

	return $notification_mutation_batches_by_site;
}


/**
 * Read the active routine notification mutation batch for the current site.
 *
 * @param array|null $batch_state Receives the active batch state.
 * @return bool Whether a batch is active.
 */
function ai4seo_get_active_notification_mutation_batch_for_current_site( ?array &$batch_state = null ): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_mutation_batches_by_site =& ai4seo_get_notification_mutation_batches_by_site();

	if ( empty( $notification_mutation_batches_by_site[ $current_scope ]['active'] ) ) {
		return false;
	}

	$batch_state = $notification_mutation_batches_by_site[ $current_scope ];
	return true;
}


/**
 * Begin one routine-check notification mutation batch from an authoritative raw snapshot.
 *
 * @return bool Whether a safe batch could be started.
 */
function ai4seo_begin_notification_mutation_batch(): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$existing_batch = array();

	if ( ai4seo_get_active_notification_mutation_batch_for_current_site( $existing_batch ) ) {
		return false;
	}

	$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

	if ( null === $option_snapshot ) {
		return false;
	}

	$snapshot_made_changes       = false;
	$snapshot_membership_changed = false;
	$raw_notifications           = $option_snapshot['exists'] && is_array( $option_snapshot['value'] )
		? $option_snapshot['value']
		: array();
	$notifications               = $option_snapshot['exists']
		? ai4seo_normalize_stored_notifications(
			$option_snapshot['value'],
			$snapshot_made_changes,
			$snapshot_membership_changed
		)
		: array();

	$notification_mutation_batches_by_site                   =& ai4seo_get_notification_mutation_batches_by_site();
	$notification_mutation_batches_by_site[ $current_scope ] = array(
		'active'                    => true,
		'initial_snapshot'          => $option_snapshot,
		'notifications'             => $notifications,
		'raw_notification_indexes'  => array_map( 'strval', array_keys( $raw_notifications ) ),
		'mutation_callbacks'        => array(),
		'repair_refresh_required'   => $snapshot_made_changes,
		'repair_membership_changed' => $snapshot_membership_changed,
	);

	return true;
}


/**
 * Stage one ordered mutation inside the current site's routine-check batch.
 *
 * @param callable $mutation_callback Mutation callback to stage and replay.
 * @param mixed    $mutation_result Receives the callback's semantic result.
 * @return bool Whether the mutation was staged successfully.
 */
function ai4seo_stage_notification_mutation( callable $mutation_callback, &$mutation_result = null ): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	$notification_mutation_batches_by_site =& ai4seo_get_notification_mutation_batches_by_site();

	if ( empty( $notification_mutation_batches_by_site[ $current_scope ]['active'] ) ) {
		return false;
	}

	$current_notifications = $notification_mutation_batches_by_site[ $current_scope ]['notifications'];
	$callback_result       = $mutation_callback( $current_notifications );

	if ( ! ai4seo_is_valid_notification_mutation_result( $callback_result, true )
		|| ( $callback_result['delete_option'] && array() !== $callback_result['notifications'] ) ) {
		ai4seo_debug_message( 654982720, 'A staged notification mutation returned an invalid result shape.', true );
		return false;
	}

	$notification_mutation_batches_by_site[ $current_scope ]['notifications']        = $callback_result['notifications'];
	$notification_mutation_batches_by_site[ $current_scope ]['mutation_callbacks'][] = $mutation_callback;
	$mutation_result = $callback_result['result'];

	return true;
}


/**
 * Determine whether an active batch's raw starting value contained one notification index.
 *
 * @param string $notification_index Notification identifier.
 * @return bool|null Presence result, or null when no batch is active.
 */
function ai4seo_batched_raw_notification_index_exists( string $notification_index ): ?bool {
	$batch_state = array();

	if ( ! ai4seo_get_active_notification_mutation_batch_for_current_site( $batch_state ) ) {
		return null;
	}

	return in_array( $notification_index, $batch_state['raw_notification_indexes'], true );
}


/**
 * Discard a routine notification mutation batch without persisting its provisional state.
 *
 * @param string $batch_scope Exact site/options-table scope captured when batching began.
 * @return void
 */
function ai4seo_abort_notification_mutation_batch( string $batch_scope ): void {
	if ( '' === $batch_scope ) {
		return;
	}

	$notification_mutation_batches_by_site =& ai4seo_get_notification_mutation_batches_by_site();
	unset( $notification_mutation_batches_by_site[ $batch_scope ] );
}


/**
 * Flush the current site's staged routine mutations through one retrying compare-and-swap operation.
 *
 * @return bool Whether the complete batch reached an authoritative desired state.
 */
function ai4seo_flush_notification_mutation_batch(): bool {
	$current_scope = ai4seo_get_site_options_request_cache_scope();
	$batch_state   = array();

	if ( '' === $current_scope || ! ai4seo_get_active_notification_mutation_batch_for_current_site( $batch_state ) ) {
		return false;
	}

	$notification_mutation_batches_by_site =& ai4seo_get_notification_mutation_batches_by_site();
	unset( $notification_mutation_batches_by_site[ $current_scope ] );

	$mutation_callbacks = $batch_state['mutation_callbacks'];
	$batch_callback     = static function ( array $notifications ) use ( $mutation_callbacks ): array {
		$original_notifications = $notifications;
		$delete_option          = false;
		$refresh_unread_count   = false;

		foreach ( $mutation_callbacks as $mutation_callback ) {
			$callback_input  = $notifications;
			$callback_result = $mutation_callback( $notifications );

			if ( ! ai4seo_is_valid_notification_mutation_result( $callback_result, true ) ) {
				return array();
			}

			$notifications        = $callback_result['notifications'];
			$delete_option        = $callback_result['delete_option'];
			$refresh_unread_count = $refresh_unread_count
				|| ( $callback_result['refresh_unread_count'] && $notifications !== $callback_input );
		}

		return array(
			'notifications'        => $notifications,
			'delete_option'        => $delete_option,
			'result'               => true,
			'refresh_unread_count' => $refresh_unread_count || $notifications !== $original_notifications,
		);
	};
	$mutation_result    = false;
	$unread_refresh     = false;
	$resulting_state    = $batch_state['notifications'];
	$mutation_succeeded = ai4seo_mutate_notifications(
		$batch_callback,
		$mutation_result,
		$unread_refresh,
		$resulting_state,
		$batch_state['initial_snapshot']
	);

	if ( ! $mutation_succeeded ) {
		ai4seo_reset_notification_request_cache_for_current_site();
		return false;
	}

	// The routine boundary also self-heals a count write that failed during an earlier request.
	ai4seo_refresh_unread_notifications_count();
	return true;
}


/**
 * Create a site-authenticated signature for one trusted local notification message.
 *
 * @param string $notification_index Notification identifier.
 * @param string $message Sanitized local notification message.
 * @return string Message-source signature.
 */
function ai4seo_get_local_notification_message_source_signature( string $notification_index, string $message ): string {
	// Bind the marker to both the notification identity and exact sanitized bytes so it cannot be replayed onto another message.
	return hash_hmac( 'sha256', $notification_index . "\0" . $message, wp_salt( 'auth' ) );
}


/**
 * Determine whether a stored notification message was created by the trusted local path.
 *
 * @param string $notification_index Notification identifier.
 * @param array  $notification Stored notification data.
 * @return bool Whether the local source marker has a valid site-authenticated signature.
 */
function ai4seo_is_trusted_local_notification_message( string $notification_index, array $notification ): bool {
	// A plain local marker is untrusted because historical API metadata could supply the same field and value.
	if ( ! isset( $notification['message'] )
		|| ! is_string( $notification['message'] )
		|| AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL !== ( $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD ] ?? '' )
		|| ! isset( $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] )
		|| ! is_string( $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] ) ) {
		return false;
	}

	$expected_signature = ai4seo_get_local_notification_message_source_signature( $notification_index, $notification['message'] );

	return hash_equals( $expected_signature, $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] );
}


/**
 * Remove metadata shapes that downstream notification rendering does not support.
 *
 * @param array $notification Notification record to normalize.
 * @return bool Whether unsupported metadata was removed.
 */
function ai4seo_remove_non_scalar_notification_metadata( array &$notification ): bool {
	$made_changes = false;

	// Preserve the separately validated message while limiting every metadata field to renderer-compatible scalars.
	foreach ( $notification as $field_name => $field_value ) {
		if ( 'message' === $field_name || is_scalar( $field_value ) ) {
			continue;
		}

		unset( $notification[ $field_name ] );
		$made_changes = true;
	}

	return $made_changes;
}


/**
 * Normalize one complete stored notification map before it can reach display logic.
 *
 * Records created before source tracking are conservatively treated as remote. Dismissed records
 * retain their empty message and history, while active records that no longer contain visible text
 * are removed.
 *
 * @param mixed     $notifications Stored notification option value.
 * @param bool      $made_changes Receives whether the returned map differs from storage.
 * @param bool|null $membership_changed Receives whether records were added or removed.
 * @return array Safe notification map.
 */
function ai4seo_normalize_stored_notifications( $notifications, bool &$made_changes, ?bool &$membership_changed = null ): array {
	$made_changes       = false;
	$membership_changed = false;

	// An invalid option shape offers no trustworthy record boundary, so repair it to the safe empty map.
	if ( ! is_array( $notifications ) ) {
		$made_changes       = true;
		$membership_changed = true;
		return array();
	}

	// Compare keys after normalization so unread state refreshes only when stored membership actually changes.
	$original_notification_indexes = array_keys( $notifications );

	// Normalize every record independently so valid notification state survives unrelated malformed entries.
	foreach ( $notifications as $notification_index => $notification ) {
		// The reserved zero identifier is rejected by every public consumer and cannot remain countable storage.
		if ( '0' === (string) $notification_index ) {
			unset( $notifications[ $notification_index ] );
			$made_changes = true;
			continue;
		}

		// A record without a string message cannot enter either source-specific sanitization contract.
		if ( ! is_array( $notification )
			|| ! array_key_exists( 'message', $notification )
			|| ! is_string( $notification['message'] ) ) {
			unset( $notifications[ $notification_index ] );
			$made_changes = true;
			continue;
		}

		// Only a site-authenticated local record can retain the richer local markup policy unchanged.
		if ( ! ai4seo_is_remote_only_notification_index( (string) $notification_index )
			&& ai4seo_is_trusted_local_notification_message( (string) $notification_index, $notification ) ) {
			continue;
		}

		// Historical remote metadata must satisfy the same scalar shape enforced during current ingestion.
		if ( ai4seo_remove_non_scalar_notification_metadata( $notifications[ $notification_index ] ) ) {
			$made_changes = true;
		}

		$notification = $notifications[ $notification_index ];

		// Missing, unknown, remote, and forged-local sources all converge on the narrow remote policy.
		$sanitized_message = ai4seo_sanitize_remote_notification_message( $notification['message'] );
		$is_dismissed      = ! empty( $notification['dismissed'] );

		// The reserved discount may retain only a separately validated image URL; its message remains image-free.
		if ( ai4seo_is_remote_only_notification_index( (string) $notification_index ) ) {
			$sanitized_image_url = ai4seo_sanitize_remote_notification_image_url( $notification['image'] ?? null );

			if ( '' === $sanitized_image_url ) {
				if ( array_key_exists( 'image', $notifications[ $notification_index ] ) ) {
					unset( $notifications[ $notification_index ]['image'] );
					$made_changes = true;
				}
			} elseif ( ! isset( $notification['image'] ) || $sanitized_image_url !== $notification['image'] ) {
				$notifications[ $notification_index ]['image'] = $sanitized_image_url;
				$made_changes                                  = true;
			}

			// Preserve historical image-only discounts by replacing their stripped legacy <img> message with safe fallback text.
			if ( ! $is_dismissed
				&& '' !== $sanitized_image_url
				&& ! ai4seo_notification_message_has_visible_content( $sanitized_message ) ) {
				$sanitized_message = __( 'Discount offer', 'ai-for-seo' );
			}
		} elseif ( array_key_exists( 'image', $notifications[ $notification_index ] ) ) {
			// Typed remote images are reserved for discounts and cannot survive on generic remote records.
			unset( $notifications[ $notification_index ]['image'] );
			$made_changes = true;
		}

		if ( $sanitized_message !== $notification['message'] ) {
			$notifications[ $notification_index ]['message'] = $sanitized_message;
			$made_changes                                    = true;
		}

		// Canonicalize provenance and remove any forged signature through the shared source-field owner.
		$made_changes = ai4seo_apply_notification_message_source(
			$notifications[ $notification_index ],
			(string) $notification_index,
			$sanitized_message,
			AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE
		) || $made_changes;

		// Preserve blank dismissal history, but purge active records that can no longer render visible content.
		if ( ! $is_dismissed && ! ai4seo_notification_message_has_visible_content( $sanitized_message ) ) {
			unset( $notifications[ $notification_index ] );
			$made_changes = true;
		}
	}

	$membership_changed = array_keys( $notifications ) !== $original_notification_indexes;

	return $notifications;
}


/**
 * Read and repair the stored notification map without overwriting a concurrent writer.
 *
 * @param bool|null $storage_ready Receives whether the authoritative option is safe for subsequent mutation.
 * @param bool|null $membership_changed Receives whether unread derived state needs refreshing.
 * @return array Safe notification map from the latest usable snapshot.
 */
function ai4seo_get_repaired_notifications( ?bool &$storage_ready = null, ?bool &$membership_changed = null ): array {
	$storage_ready              = false;
	$membership_changed         = false;
	$last_safe_notifications    = array();
	$cached_notifications       = array();
	$membership_refresh_pending = false;
	$repair_refresh_pending     = false;

	// Reuse only a previously verified value; mutations deliberately bypass this request memo.
	if ( ai4seo_get_notification_request_cache_for_current_site( $cached_notifications, $membership_refresh_pending ) ) {
		$storage_ready      = true;
		$membership_changed = $membership_refresh_pending;
		return $cached_notifications;
	}

	// Re-read authoritative bytes after each conflict so repair never overwrites a concurrent notification change.
	for ( $attempt = 0; $attempt < AI4SEO_NOTIFICATION_CAS_MAX_ATTEMPTS; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

		// A failed authoritative read provides no safe source snapshot and therefore fails closed.
		if ( null === $option_snapshot ) {
			ai4seo_debug_message( 654982713, 'Could not read the notification option while repairing stored messages.', true );
			return array();
		}

		// A genuinely missing option is already safe and can be created by a later notification writer.
		if ( ! $option_snapshot['exists'] ) {
			$storage_ready      = true;
			$membership_changed = $repair_refresh_pending;
			ai4seo_store_notification_request_cache_for_current_site(
				array(),
				$repair_refresh_pending,
				$repair_refresh_pending
			);
			return array();
		}

		// Normalize only the current attempt's snapshot before deciding whether persistence is required.
		$snapshot_made_changes       = false;
		$snapshot_membership_changed = false;
		$last_safe_notifications     = ai4seo_normalize_stored_notifications(
			$option_snapshot['value'],
			$snapshot_made_changes,
			$snapshot_membership_changed
		);
		$repair_refresh_pending      = $repair_refresh_pending || $snapshot_made_changes;

		// An unchanged normalized snapshot is authoritative and safe for subsequent write operations.
		if ( ! $snapshot_made_changes ) {
			$storage_ready      = true;
			$membership_changed = $repair_refresh_pending;
			ai4seo_store_notification_request_cache_for_current_site(
				$last_safe_notifications,
				$repair_refresh_pending,
				$repair_refresh_pending
			);
			return $last_safe_notifications;
		}

		// Replace only the exact bytes that produced this sanitized candidate.
		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_NOTIFICATIONS_OPTION_NAME,
			$option_snapshot,
			$last_safe_notifications,
			false,
			true
		);

		if ( true === $compare_and_swap_result ) {
			// Re-read after mirrored hooks because a listener may have changed the option reentrantly.
			continue;
		}

		if ( null === $compare_and_swap_result ) {
			ai4seo_debug_message( 654982714, 'Could not persist repaired notification messages.', true );
			return $last_safe_notifications;
		}
	}

	ai4seo_debug_message( 654982715, 'Could not persist repaired notification messages after concurrent updates.', true );

	// After exhausting writes, return a freshly sanitized final view without claiming it is safe to mutate.
	$final_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

	if ( null === $final_snapshot ) {
		return array();
	}

	if ( ! $final_snapshot['exists'] ) {
		$storage_ready      = true;
		$membership_changed = $repair_refresh_pending;
		ai4seo_store_notification_request_cache_for_current_site(
			array(),
			$repair_refresh_pending,
			$repair_refresh_pending
		);
		return array();
	}

	$final_snapshot_made_changes = false;
	$final_membership_changed    = false;
	$last_safe_notifications     = ai4seo_normalize_stored_notifications(
		$final_snapshot['value'],
		$final_snapshot_made_changes,
		$final_membership_changed
	);
	$repair_refresh_pending      = $repair_refresh_pending || $final_snapshot_made_changes;

	if ( ! $final_snapshot_made_changes ) {
		$storage_ready      = true;
		$membership_changed = $repair_refresh_pending;
		ai4seo_store_notification_request_cache_for_current_site(
			$last_safe_notifications,
			$repair_refresh_pending,
			$repair_refresh_pending
		);
	}

	return $last_safe_notifications;
}


/**
 * Return the staged routine notification view, falling back to authoritative repaired storage.
 *
 * @return array Safe notification map.
 */
function ai4seo_get_routine_notification_view(): array {
	$batch_state = array();

	if ( ai4seo_get_active_notification_mutation_batch_for_current_site( $batch_state ) ) {
		return $batch_state['notifications'];
	}

	return ai4seo_get_repaired_notifications();
}


/**
 * Determine whether a notification is dismissed in the current routine-check view.
 *
 * @param string $notification_index Notification identifier.
 * @return bool Whether the notification is dismissed.
 */
function ai4seo_is_routine_notification_dismissed( string $notification_index ): bool {
	if ( '' === $notification_index ) {
		return false;
	}

	$notifications = ai4seo_get_routine_notification_view();

	return ! empty( $notifications[ $notification_index ]['dismissed'] );
}


/**
 * Validate the shared callback contract used by notification mutation retries.
 *
 * @param mixed $callback_result Mutation callback result.
 * @param bool  $require_unread_refresh_field Whether this phase consumes the unread-refresh field.
 * @return bool Whether the callback result contains every required field with the expected shape.
 */
function ai4seo_is_valid_notification_mutation_result( $callback_result, bool $require_unread_refresh_field ): bool {
	if ( ! is_array( $callback_result )
		|| ! isset( $callback_result['notifications'] )
		|| ! is_array( $callback_result['notifications'] )
		|| ! isset( $callback_result['delete_option'] )
		|| ! is_bool( $callback_result['delete_option'] )
		|| ! array_key_exists( 'result', $callback_result ) ) {
		return false;
	}

	// The final proof read needs only desired state, while active retries also propagate derived unread work.
	return ! $require_unread_refresh_field
		|| ( isset( $callback_result['refresh_unread_count'] ) && is_bool( $callback_result['refresh_unread_count'] ) );
}


/**
 * Apply one notification mutation to fresh authoritative snapshots with bounded CAS retries.
 *
 * The callback receives a normalized map and returns notifications, delete_option, result, and
 * refresh_unread_count fields. It is re-run after every collision so concurrent records survive.
 *
 * @param callable   $mutation_callback Builds the desired mutation from one current safe map.
 * @param mixed      $mutation_result Receives the callback's semantic operation result.
 * @param bool|null  $unread_refresh_required Receives whether this operation changed derived unread state.
 * @param array|null $resulting_notifications Receives the latest safe candidate, including on failure.
 * @param array|null $initial_option_snapshot Optional authoritative snapshot for the first attempt.
 * @return bool Whether the desired state was authoritatively committed or already present.
 */
function ai4seo_mutate_notifications(
	callable $mutation_callback,
	&$mutation_result = null,
	?bool &$unread_refresh_required = null,
	?array &$resulting_notifications = null,
	?array $initial_option_snapshot = null
): bool {
	// Initialize semantic and derived-state outputs without discarding a caller-provided fail-closed view.
	$mutation_result = false;

	if ( null === $resulting_notifications ) {
		$resulting_notifications = array();
	}

	$unread_refresh_required    = false;
	$refresh_was_requested      = false;
	$write_or_conflict_observed = false;

	if ( null !== $initial_option_snapshot
		&& ! ai4seo_is_valid_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME, $initial_option_snapshot ) ) {
		return false;
	}

	// A mutation never trusts a previously memoized view as its compare-and-swap input.
	ai4seo_reset_notification_request_cache_for_current_site();

	for ( $attempt = 0; $attempt < AI4SEO_NOTIFICATION_CAS_MAX_ATTEMPTS; ++$attempt ) {
		$option_snapshot = 0 === $attempt && null !== $initial_option_snapshot
			? $initial_option_snapshot
			: ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

		if ( null === $option_snapshot ) {
			ai4seo_debug_message( 654982716, 'Could not read the notification option while applying a mutation.', true );
			return false;
		}

		// Normalize the authoritative snapshot before the callback derives one retry-specific desired state.
		$snapshot_made_changes       = false;
		$snapshot_membership_changed = false;
		$current_notifications       = $option_snapshot['exists']
			? ai4seo_normalize_stored_notifications(
				$option_snapshot['value'],
				$snapshot_made_changes,
				$snapshot_membership_changed
			)
			: array();
		$callback_result             = $mutation_callback( $current_notifications );

		// Reject malformed callback contracts before their data can participate in canonicalization or persistence.
		if ( ! ai4seo_is_valid_notification_mutation_result( $callback_result, true ) ) {
			ai4seo_debug_message( 654982717, 'A notification mutation returned an invalid result shape.', true );
			return false;
		}

		// Normalize and validate callback output so CAS never persists another repair obligation.
		$candidate_made_changes  = false;
		$candidate_notifications = ai4seo_normalize_stored_notifications(
			$callback_result['notifications'],
			$candidate_made_changes
		);

		if ( $candidate_made_changes
			|| ( $callback_result['delete_option'] && array() !== $candidate_notifications ) ) {
			ai4seo_debug_message( 654982718, 'A notification mutation attempted to persist non-canonical state.', true );
			return false;
		}

		$mutation_result         = $callback_result['result'];
		$resulting_notifications = $candidate_notifications;
		$candidate_changed       = $candidate_notifications !== $current_notifications;
		$refresh_was_requested   = $refresh_was_requested
			|| ( $callback_result['refresh_unread_count'] && $candidate_changed )
			|| $snapshot_made_changes;

		// Distinguish a missing option from an empty stored map before choosing whether an authoritative write is needed.
		if ( $callback_result['delete_option'] ) {
			$requires_write = $option_snapshot['exists'];
		} else {
			$requires_write = $snapshot_made_changes
				|| ( $option_snapshot['exists'] && $candidate_notifications !== $option_snapshot['value'] )
				|| ( ! $option_snapshot['exists'] && array() !== $candidate_notifications );
		}

		// An idempotent desired state is a successful mutation, including after another writer won the CAS.
		if ( ! $requires_write ) {
			$unread_refresh_required = $refresh_was_requested && $write_or_conflict_observed;
			ai4seo_store_notification_request_cache_for_current_site(
				$candidate_notifications,
				$unread_refresh_required,
				$refresh_was_requested
			);
			return true;
		}

		// Deletion must match the same snapshot as mutation CAS so a newer writer cannot be removed.
		$compare_and_swap_result = $callback_result['delete_option']
			? ai4seo_compare_and_delete_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME, $option_snapshot, true )
			: ai4seo_compare_and_swap_option_snapshot(
				AI4SEO_NOTIFICATIONS_OPTION_NAME,
				$option_snapshot,
				$candidate_notifications,
				false,
				true
			);

		if ( true === $compare_and_swap_result ) {
			// Mirrored WordPress hooks may write reentrantly, so prove the desired state with another raw read.
			$write_or_conflict_observed = true;
			continue;
		}

		if ( null === $compare_and_swap_result ) {
			ai4seo_debug_message( 654982719, 'Could not persist a notification mutation.', true );
			return false;
		}

		$write_or_conflict_observed = true;
	}

	// One final read can prove that a competing writer already installed the exact desired state.
	$final_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

	if ( null === $final_snapshot ) {
		return false;
	}

	$final_snapshot_made_changes = false;
	$final_notifications         = $final_snapshot['exists']
		? ai4seo_normalize_stored_notifications(
			$final_snapshot['value'],
			$final_snapshot_made_changes
		)
		: array();
	$final_callback_result       = $mutation_callback( $final_notifications );

	// The final read proves only exact desired state and intentionally does not initiate another write attempt.
	if ( ! ai4seo_is_valid_notification_mutation_result( $final_callback_result, false )
		|| $final_snapshot_made_changes ) {
		return false;
	}

	$desired_state_is_present = $final_callback_result['delete_option']
		? ! $final_snapshot['exists']
		: $final_callback_result['notifications'] === $final_notifications
			&& ( $final_snapshot['exists'] || array() === $final_notifications );

	if ( ! $desired_state_is_present ) {
		$resulting_notifications = $final_callback_result['notifications'];
		return false;
	}

	$mutation_result         = $final_callback_result['result'];
	$resulting_notifications = $final_notifications;
	$unread_refresh_required = $refresh_was_requested;
	ai4seo_store_notification_request_cache_for_current_site(
		$final_notifications,
		$unread_refresh_required,
		$refresh_was_requested
	);

	return true;
}


/**
 * Commit one semantic notification mutation and synchronize derived unread state.
 *
 * @param callable $mutation_callback Builds the desired mutation from one current safe map.
 * @return bool The callback's semantic result, or false when authoritative persistence fails.
 */
function ai4seo_commit_notification_mutation( callable $mutation_callback ): bool {
	$mutation_result         = false;
	$unread_refresh_required = false;

	// Keep routine writers on one failure and unread-refresh contract while display logic retains lower-level state access.
	if ( ! ai4seo_mutate_notifications(
		$mutation_callback,
		$mutation_result,
		$unread_refresh_required
	) ) {
		return false;
	}

	// Recompute the derived badge only after the mutation helper proves an authoritative desired state.
	if ( $unread_refresh_required ) {
		ai4seo_refresh_unread_notifications_count();
	}

	return (bool) $mutation_result;
}


/**
 * Commit a routine-check mutation provisionally when a batch is active, or immediately otherwise.
 *
 * @param callable $mutation_callback Builds the desired mutation from one current safe map.
 * @return bool The callback's semantic result, or false when staging/persistence fails.
 */
function ai4seo_commit_routine_notification_mutation( callable $mutation_callback ): bool {
	$batch_state     = array();
	$mutation_result = false;

	if ( ! ai4seo_get_active_notification_mutation_batch_for_current_site( $batch_state ) ) {
		return ai4seo_commit_notification_mutation( $mutation_callback );
	}

	if ( ! ai4seo_stage_notification_mutation( $mutation_callback, $mutation_result ) ) {
		return false;
	}

	return (bool) $mutation_result;
}


/**
 * Apply the canonical source marker and local-message signature to one notification.
 *
 * @param array  $notification Notification data to update.
 * @param string $notification_index Notification identifier.
 * @param string $message Sanitized notification message.
 * @param string $message_source Trusted message source.
 * @return bool Whether any provenance field changed.
 */
function ai4seo_apply_notification_message_source( array &$notification, string $notification_index, string $message, string $message_source ): bool {
	$made_changes          = false;
	$stored_message_source = $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD ] ?? null;

	// Keep the persisted marker synchronized even when a source replaces an existing notification in place.
	if ( $stored_message_source !== $message_source ) {
		$notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD ] = $message_source;
		$made_changes = true;
	}

	// Only locally generated messages receive a site-authenticated signature; remote records must never retain one.
	if ( AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL === $message_source ) {
		$message_source_signature        = ai4seo_get_local_notification_message_source_signature( $notification_index, $message );
		$stored_message_source_signature = $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] ?? null;

		if ( $stored_message_source_signature !== $message_source_signature ) {
			$notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] = $message_source_signature;
			$made_changes = true;
		}
	} elseif ( array_key_exists( AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD, $notification ) ) {
		unset( $notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD ] );
		$made_changes = true;
	}

	return $made_changes;
}


/**
 * Store one notification with an explicitly trusted message source.
 *
 * @param string $notification_index Notification identifier.
 * @param string $message Notification message.
 * @param bool   $force Whether to force replace the existing notification.
 * @param array  $additional_fields Additional notification fields, such as notice type and permanence.
 * @param string $message_source Trusted message source.
 * @param bool   $routine_mutation Whether to stage through the active routine-check batch.
 * @return bool True if the notification is durably stored, false otherwise.
 */
function ai4seo_store_notification(
	string $notification_index,
	string $message,
	bool $force,
	array $additional_fields,
	string $message_source,
	bool $routine_mutation = false
): bool {
	// Retain the established recursion budget while both public source-specific entry points share this implementation.
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 10 ) ) {
		ai4seo_debug_message( 982407188, 'Prevented loop', true );
		return false;
	}

	// Canonicalize the option key once so storage, signatures, and rendered identifiers use the same identity.
	$notification_index = sanitize_key( $notification_index );

	if ( '' === $notification_index || '0' === $notification_index ) {
		return false;
	}

	// Select the source policy once; every non-remote internal call deliberately converges on trusted local behavior.
	$is_remote_notification = AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE === $message_source
		|| ai4seo_is_remote_only_notification_index( $notification_index );

	if ( $is_remote_notification ) {
		$message_source = AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE;
		$message        = ai4seo_sanitize_remote_notification_message( $message );
	} else {
		$message_source = AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL;
		$message        = trim( ai4seo_wp_kses( $message ) );
	}

	// Use one timestamp for expiry conversion and any newly initialized notification state.
	$current_time               = time();
	$prepared_additional_fields = array();

	// Prepare compatible metadata once so every retry applies identical bytes to its fresh snapshot.
	if ( ! empty( $additional_fields ) ) {
		foreach ( $additional_fields as $field_name => $field_value ) {
			// Canonical keys prevent alternate spellings from bypassing reserved-field checks.
			$field_name = sanitize_key( $field_name );

			// API or caller metadata cannot select or authenticate its own message source.
			if ( '' === $field_name || in_array(
				$field_name,
				array(
					AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD,
					AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD,
				),
				true
			) ) {
				continue;
			}

			// Compound remote metadata is rejected because downstream notice fields expect scalar values.
			if ( $is_remote_notification
				&& ! is_scalar( $field_value ) ) {
				continue;
			}

			// Remote images are typed discount metadata, never arbitrary message markup or generic-notification metadata.
			if ( $is_remote_notification && 'image' === $field_name ) {
				if ( ! ai4seo_is_remote_only_notification_index( $notification_index ) ) {
					continue;
				}

				$field_value = ai4seo_sanitize_remote_notification_image_url( $field_value );

				if ( '' === $field_value ) {
					continue;
				}
			}

			// Preserve the established rich-field compatibility after shape validation.
			$field_value = ai4seo_wp_kses( $field_value );

			// Convert relative expiry once at ingestion so later filtering compares an absolute timestamp.
			if ( 'expire_in' === $field_name && is_numeric( $field_value ) && $field_value > 0 ) {
				$field_name  = 'expire_at';
				$field_value = $current_time + (int) $field_value;
			}

			$prepared_additional_fields[ $field_name ] = $field_value;
		}
	}

	$commit_notification_mutation = $routine_mutation
		? 'ai4seo_commit_routine_notification_mutation'
		: 'ai4seo_commit_notification_mutation';

	return $commit_notification_mutation(
		static function ( array $notifications ) use (
			$notification_index,
			$message,
			$force,
			$prepared_additional_fields,
			$message_source,
			$is_remote_notification,
			$current_time
		): array {
			$original_notifications = $notifications;

			// Empty local copy retains removal compatibility; remote copy must also contain visible allowlisted text.
			if ( '' === $message
				|| ( $is_remote_notification && ! ai4seo_notification_message_has_visible_content( $message ) ) ) {
				unset( $notifications[ $notification_index ] );

				return array(
					'notifications'        => $notifications,
					'delete_option'        => false,
					'result'               => false,
					'refresh_unread_count' => $notifications !== $original_notifications,
				);
			}

			// Forced, absent, empty, or malformed records start a fresh unread lifecycle on every retry.
			$start_fresh_lifecycle = $force
				|| ! isset( $notifications[ $notification_index ] )
				|| ! $notifications[ $notification_index ]
				|| ! is_array( $notifications[ $notification_index ] );

			if ( $start_fresh_lifecycle ) {
				$notifications[ $notification_index ] = array();
			}

			// A remote source replacing trusted local state cannot inherit compound local-only metadata.
			if ( $is_remote_notification ) {
				ai4seo_remove_non_scalar_notification_metadata( $notifications[ $notification_index ] );
				unset( $notifications[ $notification_index ]['image'] );
			}

			foreach ( $prepared_additional_fields as $field_name => $field_value ) {
				$notifications[ $notification_index ][ $field_name ] = $field_value;
			}

			ai4seo_apply_notification_message_source(
				$notifications[ $notification_index ],
				$notification_index,
				$message,
				$message_source
			);
			$notifications[ $notification_index ]['message'] = $message;

			// Initialize transient state only for a fresh lifecycle, preserving compatible existing state otherwise.
			if ( $start_fresh_lifecycle ) {
				$notifications[ $notification_index ]['time_created'] = $current_time;
				$notifications[ $notification_index ]['read']         = false;

				if ( empty( $notifications[ $notification_index ]['is_permanent'] ) ) {
					$notifications[ $notification_index ]['dismissed']         = false;
					$notifications[ $notification_index ]['time_dismissed']    = 0;
					$notifications[ $notification_index ]['time_auto_dismiss'] = 0;
				} else {
					unset(
						$notifications[ $notification_index ]['dismissed'],
						$notifications[ $notification_index ]['time_dismissed'],
						$notifications[ $notification_index ]['time_auto_dismiss']
					);
				}
			}

			return array(
				'notifications'        => $notifications,
				'delete_option'        => false,
				'result'               => true,
				'refresh_unread_count' => $notifications !== $original_notifications,
			);
		}
	);
}


/**
 * Push a trusted local notification.
 *
 * @param string $notification_index The notification identifier.
 * @param string $message The notification message.
 * @param bool   $force Whether to force replace existing notification.
 * @param array  $additional_fields Additional notification fields, such as notice type and permanence.
 * @return bool True if notification was added, false otherwise.
 */
function ai4seo_push_notification( string $notification_index, string $message, bool $force = false, array $additional_fields = array() ): bool {
	// Local callers retain the rich policy and receive authenticated provenance through the shared store.
	return ai4seo_store_notification(
		$notification_index,
		$message,
		$force,
		$additional_fields,
		AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL
	);
}


/**
 * Push an API-provided notification through the remote-content policy.
 *
 * @param string $notification_index The notification identifier.
 * @param string $message The remote notification message.
 * @param bool   $force Whether to force replace existing notification.
 * @param array  $additional_fields Additional notification fields, such as notice type and permanence.
 * @return bool True if notification was added, false otherwise.
 */
function ai4seo_push_remote_notification( string $notification_index, string $message, bool $force = false, array $additional_fields = array() ): bool {
	// API-provided messages enter through the narrow policy regardless of any supplied metadata.
	return ai4seo_store_notification(
		$notification_index,
		$message,
		$force,
		$additional_fields,
		AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE
	);
}


/**
 * Apply auto-dismissal and expiry rules to one safe notification map.
 *
 * @param array $notifications Notification map to update.
 * @param int   $current_time One stable lifecycle evaluation timestamp.
 * @return bool Whether lifecycle state changed.
 */
function ai4seo_apply_notification_lifecycle_state( array &$notifications, int $current_time ): bool {
	$made_changes = false;

	// Auto-dismissal retains history and takes precedence when the same notification also reaches hard expiry.
	foreach ( $notifications as $notification_index => $notification ) {
		if ( ! is_array( $notification ) || ! empty( $notification['dismissed'] ) ) {
			continue;
		}

		if ( isset( $notification['time_auto_dismiss'] )
			&& $notification['time_auto_dismiss'] > 0
			&& $current_time >= $notification['time_auto_dismiss']
			&& empty( $notification['is_permanent'] ) ) {
			$notifications[ $notification_index ]['dismissed']      = true;
			$notifications[ $notification_index ]['time_dismissed'] = $current_time;
			$made_changes = true;
			continue;
		}

		if ( isset( $notification['expire_at'] )
			&& is_numeric( $notification['expire_at'] )
			&& $notification['expire_at'] > 0
			&& $current_time > $notification['expire_at'] ) {
			unset( $notifications[ $notification_index ] );
			$made_changes = true;
		}
	}

	return $made_changes;
}


/**
 * Function to auto-dismiss expired notifications and get displayable notifications
 *
 * @param bool      $skip_num_displayable_notification_condition Whether to skip the condition that checks the number of displayable notifications to prevent loops.
 * @param bool      $refresh_unread_count Whether to refresh the unread notifications counter after auto-dismissing or deleting notifications.
 * @param bool|null $storage_ready Receives whether the returned display state is authoritative and persisted.
 * @return array Array of notifications that should be displayed (not dismissed and not expired)
 */
function ai4seo_get_displayable_notifications(
	bool $skip_num_displayable_notification_condition = false,
	bool $refresh_unread_count = true,
	?bool &$storage_ready = null
): array {
	$storage_ready = false;

	if ( ai4seo_prevent_loops( __FUNCTION__, 3 ) ) {
		ai4seo_debug_message( 377627317, 'Prevented loop', true );
		return array();
	}

	// Repair provenance and markup before dismissal, expiry, or display conditions inspect stored state.
	$notification_storage_ready   = false;
	$repair_membership_changed    = false;
	$notifications                = ai4seo_get_repaired_notifications( $notification_storage_ready, $repair_membership_changed );
	$current_time                 = time();
	$lifecycle_candidate          = $notifications;
	$lifecycle_made_changes       = ai4seo_apply_notification_lifecycle_state( $lifecycle_candidate, $current_time );
	$lifecycle_mutation_succeeded = false;

	// Persist lifecycle changes only from a fresh authoritative snapshot; failed repairs remain in-memory only.
	if ( $lifecycle_made_changes && $notification_storage_ready ) {
		$mutation_result              = false;
		$resulting_notifications      = $lifecycle_candidate;
		$unread_refresh_required      = false;
		$lifecycle_mutation_succeeded = ai4seo_mutate_notifications(
			static function ( array $current_notifications ) use ( $current_time ): array {
				$original_notifications = $current_notifications;
				ai4seo_apply_notification_lifecycle_state( $current_notifications, $current_time );

				return array(
					'notifications'        => $current_notifications,
					'delete_option'        => false,
					'result'               => true,
					'refresh_unread_count' => $current_notifications !== $original_notifications,
				);
			},
			$mutation_result,
			$unread_refresh_required,
			$resulting_notifications
		);
		$notifications                = $resulting_notifications;
	} elseif ( $lifecycle_made_changes ) {
		// Rendering remains fail-closed even when unsafe legacy storage could not be repaired.
		$notifications = $lifecycle_candidate;
	}

	// Preserve unread-first ordering while applying the historical display cap only to already-read notices.
	$read_displayable_notifications             = array();
	$unread_displayable_notifications           = array();
	$max_displayable_already_read_notifications = AI4SEO_MAX_DISPLAYABLE_ALREADY_READ_NOTIFICATIONS;

	foreach ( $notifications as $this_notification_index => $this_notification ) {
		if ( ! is_array( $this_notification ) ) {
			continue;
		}

		// Skip already dismissed notifications.
		if ( isset( $this_notification['dismissed'] ) && $this_notification['dismissed'] ) {
			continue;
		}

		// skip if we don't pass conditions.
		if ( ! ai4seo_check_notification_conditions( $this_notification_index, $this_notification, $skip_num_displayable_notification_condition ) ) {
			continue;
		}

		// This notification should be displayed.
		if ( ! empty( $this_notification['read'] ) ) {
			// If the notification is read, we limit the number of already read notifications.
			if ( count( $read_displayable_notifications ) < $max_displayable_already_read_notifications ) {
				$read_displayable_notifications[ $this_notification_index ] = $this_notification;
			}
		} else {
			// If the notification is unread, we add it to the unread notifications.
			$unread_displayable_notifications[ $this_notification_index ] = $this_notification;
		}
	}

	$displayable_notifications = array_merge( $unread_displayable_notifications, $read_displayable_notifications );
	$can_refresh_unread_count  = $notification_storage_ready
		&& ( ! $lifecycle_made_changes || $lifecycle_mutation_succeeded );
	$storage_ready             = $can_refresh_unread_count;

	// Synchronize only a fully persisted view, including one-time repair work requested by an internal read.
	if ( $can_refresh_unread_count
		&& ! ai4seo_notification_unread_synchronization_is_active()
		&& ( $repair_membership_changed
			|| $refresh_unread_count ) ) {
		ai4seo_store_unread_count_from_displayable_notifications( $displayable_notifications );
	}

	return $displayable_notifications;
}


/**
 * Echo a notice from the notification system
 *
 * @param string $notification_index The notification index.
 * @param array  $notification The notification data.
 * @return void
 */
function ai4seo_echo_notice_from_notification( string $notification_index, array $notification ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 601523355, 'Prevented loop', true );
		return;
	}

	if ( empty( $notification_index )
		|| empty( $notification )
		|| ! isset( $notification['message'] )
		|| ! is_string( $notification['message'] ) ) {
		return;
	}

	// Revalidate provenance at the final output boundary in case a storage repair could not converge earlier.
	$is_remote_discount_notification = ai4seo_is_remote_only_notification_index( $notification_index );
	$is_trusted_local_message        = ! $is_remote_discount_notification
		&& ai4seo_is_trusted_local_notification_message( $notification_index, $notification );
	$message                         = $notification['message'];
	$discount_image_tag              = '';

	// Every unsigned, forged, legacy, or remote message receives the narrow policy before either render branch.
	if ( ! $is_trusted_local_message ) {
		$message = ai4seo_sanitize_remote_notification_message( $message );

		if ( $is_remote_discount_notification ) {
			$discount_image_tag = ai4seo_get_remote_discount_notification_image_tag( $notification, $message );
		}

		if ( ! ai4seo_notification_message_has_visible_content( $message ) && '' === $discount_image_tag ) {
			return;
		}

		if ( ! ai4seo_notification_message_has_visible_content( $message ) ) {
			$message = __( 'Discount offer', 'ai-for-seo' );
		}

		$notification['message']                                  = $message;
		$notification[ AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD ] = AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE;
	}

	// Discount presentation flags also determine whether the generic notice chrome remains useful.
	$is_dismissable           = ai4seo_can_administer_plugin() && ! ( isset( $notification['is_permanent'] ) && $notification['is_permanent'] );
	$show_generic_coupon_text = (bool) ( $notification['show_generic_coupon_text'] ?? true );
	$show_buttons_row         = (bool) ( $notification['show_buttons_row'] ?? true );

	// Invalid or missing typed discount images always fall back to the safe generic message.
	if ( $is_remote_discount_notification && '' === $discount_image_tag ) {
		$show_generic_coupon_text = true;
	}

	$is_image_only_notification      = ! $show_generic_coupon_text && ! $show_buttons_row;
	$show_notice_dismiss_button      = $is_dismissable && ! $is_image_only_notification;
	$show_contact_us_info            = (bool) ( $notification['contact_us_info'] ?? ( $notification['contact_us'] ?? false ) );
	$notice_class                    = $notification['notice_type'] ?? 'notice-info';
	$is_unread                       = ! isset( $notification['read'] ) || ! $notification['read'];
	$ignore_during_dashboard_refresh = (bool) ( $notification['ignore_during_dashboard_refresh'] ?? true );

	if ( $show_contact_us_info && $show_generic_coupon_text ) {
		$message .= '<br /><br />' . __( 'If you have any questions, just click the button below to <strong>contact us</strong>. We’re happy to help. In any language you prefer.', 'ai-for-seo' );
	}

	// Add CSS classes for unread notifications (blinking).
	$additional_classes = '';

	// unread?
	$additional_classes .= $is_unread ? ' ai4seo-unread-notice' : '';

	// ignore during dashboard refresh?
	if ( $ignore_during_dashboard_refresh ) {
		$additional_classes .= ' ai4seo-ignore-during-dashboard-refresh';
	}

	if ( $is_image_only_notification ) {
		$additional_classes .= ' ai4seo-image-only-notification';
	}

	echo '<div class="notice ai4seo-notice ai4seo-notification' . ( $show_notice_dismiss_button ? ' is-dismissible' : '' ) . ' ' . esc_attr( $notice_class ) . esc_attr( $additional_classes ) . '" data-notification-index="' . esc_attr( $notification_index ) . '">';

	if ( ! $is_image_only_notification ) {
		ai4seo_echo_wp_kses( ai4seo_get_sooz_logo_image_tag( 'sooz-oo' ) );
	}

	// Typed discount images are generated locally; the remote fallback message remains under the narrow HTML policy.
	if ( '' !== $discount_image_tag ) {
		if ( $show_generic_coupon_text ) {
			ai4seo_echo_wp_kses( ai4seo_filter_notification_message( $message, $notification_index, $notification ) );
			echo '<br><br>';
		}

		ai4seo_echo_wp_kses( $discount_image_tag );
	} elseif ( $is_image_only_notification ) {
		ai4seo_echo_wp_kses( $message );
	} else {
		ai4seo_echo_wp_kses( ai4seo_filter_notification_message( $message, $notification_index, $notification ) );
	}

	// Suppressed button rows must not leave an empty footer wrapper in image-only notices.
	$notification_buttons = $show_buttons_row ? ai4seo_get_notification_buttons( $notification_index, $notification ) : '';

	if ( $notification_buttons ) {
		echo '<div class="ai4seo-buttons-wrapper">';
			ai4seo_echo_wp_kses( $notification_buttons );
		echo '</div>';
	}

	// Keep the existing spacer workaround for WordPress notice dismiss button layout bugs.
	if ( ! $is_image_only_notification ) {
		echo '<span></span><span></span>';
	}

	// Render the dismiss button server-side because JavaScript now only binds the dismiss action.
	if ( $show_notice_dismiss_button ) {
		echo '<button type="button" class="notice-dismiss ai4seo-ignore-during-dashboard-refresh"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'ai-for-seo' ) . '</span></button>';
	}

	echo '</div>';
}


/**
 * Expand dynamic placeholders and optional content in a notification message.
 *
 * @param string $message Notification message HTML.
 * @param string $notification_index Notification identifier.
 * @param array  $notification Notification configuration.
 * @return string Filtered notification message HTML.
 */
function ai4seo_filter_notification_message( string $message, string $notification_index, array $notification ): string {
	// replace placeholders in the message.
	if ( strstr( $message, '{{EXPIRE_COUNTDOWN}}' ) && isset( $notification['expire_at'] ) && is_numeric( $notification['expire_at'] ) && $notification['expire_at'] > time() ) {
		$message_expires_in          = $notification['expire_at'] - time();
		$countdown_trigger_attribute = ai4seo_can_administer_plugin() ? " data-trigger='ai4seo_refresh_robhub_account'" : '';
		$expire_in_countdown         = "<span class='ai4seo-countdown' data-time-left='" . esc_attr( $message_expires_in ) . "'" . $countdown_trigger_attribute . '>' . esc_html( ai4seo_format_seconds_to_hhmmss_or_days_hhmmss( $message_expires_in ) ) . '</span>';
		$message                     = str_replace( '{{EXPIRE_COUNTDOWN}}', $expire_in_countdown, $message );
	}

	// add avatar and greetings if the notification has 'show_avatar' set to true.
	if ( ! empty( $notification['show_avatar'] ) ) {
		$avatar           = "<div class='ai4seo-developer-avatar-wrapper'><img src='" . esc_attr( ai4seo_get_assets_images_url( 'andre-erbis-at-space-codes.webp' ) ) . "'></div>";
		$users_first_name = ai4seo_is_function_usable( 'get_current_user_id' ) && get_current_user_id() ? get_user_meta( get_current_user_id(), 'first_name', true ) : '';

		if ( $users_first_name ) {
			/* translators: %s: User first name. */
			$greetings = '<strong>' . sprintf( esc_html__( 'Hi %s', 'ai-for-seo' ), esc_html( $users_first_name ) ) . ',</strong>';
		} else {
			$greetings = '<strong>' . esc_html__( 'Hi', 'ai-for-seo' ) . ',</strong>';
		}

		$greetings .= '<br><br>';
		$greetings .= sprintf(
			/* translators: %s: plugin name */
			esc_html__( 'This is Andre from the %s team. Thanks for joining our SEO community of 2,400+ happy users – we appreciate having you on board!', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		$greetings .= '<br><br>';

		$message = $avatar . $greetings . $message;
	}

	// add voucher_code if the notification has 'voucher_code' set.
	if ( ! empty( $notification['voucher_code'] ) ) {
		$message .= '<br><br>';
		$message .= esc_html__( 'Enter this voucher code during checkout to apply the discount:', 'ai-for-seo' ) . '<br>';
		$message .= ai4seo_get_voucher_code_output( $notification['voucher_code'] );
		$message .= '';
	}

	// Filter the message through the 'ai4seo_notification_message' filter.
	return apply_filters( 'ai4seo_notification_message', $message, $notification_index, $notification );
}


/**
 * Filter and customize the footer for notifications
 *
 * @param string $notification_index The notification index.
 * @param array  $notification The notification data.
 * @return string The filtered footer HTML
 */
function ai4seo_get_notification_buttons( string $notification_index, array $notification ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 470991053, 'Prevented loop', true );
		return '';
	}

	$can_administer_plugin              = ai4seo_can_administer_plugin();
	$show_dismiss_button                = $can_administer_plugin && ! ( isset( $notification['is_permanent'] ) && $notification['is_permanent'] );
	$show_not_now_button                = $can_administer_plugin && (bool) ( $notification['not_now_button'] ?? false ); // replaces dismiss button if set.
	$show_contact_us_button             = (bool) ( $notification['contact_us'] ?? false );
	$show_set_up_seo_autopilot_button   = $can_administer_plugin && (bool) ( $notification['set_up_seo_autopilot_button'] ?? false );
	$show_get_a_get_more_credits_button = $can_administer_plugin && (bool) ( $notification['get_more_credits_button'] ?? false );
	$show_customize_payg_button         = $can_administer_plugin && (bool) ( $notification['customize_payg_button'] ?? false );
	$show_increase_payg_budget_button   = $can_administer_plugin && (bool) ( $notification['increase_payg_budget_button'] ?? false ); // same as customize payg.

	// Button override options let specific notices reuse shared rendering while changing label, class, or order.
	$show_sync_account_button                = $can_administer_plugin && (bool) ( $notification['sync_account_button'] ?? false );
	$show_sync_account_button_first          = (bool) ( $notification['sync_account_button_first'] ?? false );
	$sync_account_button_label               = sanitize_text_field( (string) ( $notification['sync_account_button_label'] ?? __( 'Refresh', 'ai-for-seo' ) ) );
	$sync_account_button_css_class           = sanitize_text_field( (string) ( $notification['sync_account_button_css_class'] ?? '' ) );
	$show_get_a_custom_quote_button          = $can_administer_plugin && (bool) ( $notification['get_a_custom_quote_button'] ?? false );
	$show_grab_deal_button                   = $can_administer_plugin && (bool) ( $notification['grab_deal_button'] ?? false );
	$show_claim_bonus_button                 = $can_administer_plugin && (bool) ( $notification['claim_bonus_button'] ?? false );
	$show_rate_us_button                     = $can_administer_plugin && (bool) ( $notification['rate_us_button'] ?? false );
	$show_go_to_account_settings_button      = $can_administer_plugin && (bool) ( $notification['go_to_account_settings_button'] ?? false );
	$go_to_account_settings_button_label     = sanitize_text_field( (string) ( $notification['go_to_account_settings_button_label'] ?? __( 'Account Settings', 'ai-for-seo' ) ) );
	$go_to_account_settings_button_css_class = sanitize_text_field( (string) ( $notification['go_to_account_settings_button_css_class'] ?? 'ai4seo-primary-button' ) );
	$show_lost_licence_key_button            = $can_administer_plugin && (bool) ( $notification['lost_licence_key_button'] ?? false );
	$lost_licence_key_button_label           = sanitize_text_field( (string) ( $notification['lost_licence_key_button_label'] ?? __( 'Lost your license data?', 'ai-for-seo' ) ) );
	$show_update_plugin_button               = $can_administer_plugin && (bool) ( $notification['update_plugin_button'] ?? false );
	$show_go_to_settings_button              = $can_administer_plugin && (bool) ( $notification['go_to_settings_button'] ?? false );
	$show_go_to_help_button                  = (bool) ( $notification['go_to_help_button'] ?? false );
	$show_see_whats_new_button               = $can_administer_plugin && (bool) ( $notification['see_whats_new_button'] ?? false );
	$account_url                             = ai4seo_get_subpage_url( 'account' );
	$settings_url                            = ai4seo_get_subpage_url( 'settings' );
	$help_url                                = ai4seo_get_subpage_url( 'help' );
	$wp_admin_plugins_list_url               = admin_url( 'plugins.php' );

	$notification_buttons = '';
	$sync_account_button  = '';

	// Build the refresh button once so account notices can place it before or after the common button set.
	if ( $show_sync_account_button ) {
		$sync_account_button = ai4seo_get_icon_button_tag(
			'rotate',
			esc_html( $sync_account_button_label ),
			$sync_account_button_css_class,
			'ai4seo_refresh_robhub_account(this); return false;',
			$sync_account_button_label
		);
	}

	// SPECIFIC NOTIFICATION BUTTONS ============================================================================.

	// Some account recovery notices need the refresh action to be the primary first step.
	if ( $show_sync_account_button_first && $sync_account_button ) {
		$notification_buttons .= $sync_account_button;
	}

	// plugin-update notification -> add "See what's new" button.
	if ( $show_see_whats_new_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( '#ai4seo_recent_plugin_updates', '', '', 'arrow-up-right-from-square', __( "See what's new", 'ai-for-seo' ), 'ai4seo-notification-dismiss-button', 'jQuery(".ai4seo-recent-plugin-updates-content").removeClass("ai4seo-display-none")' );
	}

	// ADDITIONAL GENERIC BUTTONS ===============================================================================.

	// Show a "Rate us" Button.
	if ( $show_rate_us_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( AI4SEO_OFFICIAL_RATE_US_URL, '', '', 'star', __( 'Rate us', 'ai-for-seo' ), 'ai4seo-unicorn-button', '', '_blank' );
	}

	// Show a "Grab Deal" Button.
	if ( $show_grab_deal_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'gift', __( 'Grab Deal', 'ai-for-seo' ), 'ai4seo-unicorn-button', 'ai4seo_open_get_more_credits_modal();' );
	}

	// Show a "Claim Bonus" Button.
	if ( $show_claim_bonus_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'arrow-up-right-from-square', __( 'Claim Bonus', 'ai-for-seo' ), 'ai4seo-unicorn-button', 'ai4seo_open_get_more_credits_modal();' );
	}

	// Show a "Get more Credits" Button.
	if ( $show_get_a_get_more_credits_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'circle-plus', __( 'Get more Credits', 'ai-for-seo' ), 'ai4seo-primary-button', 'ai4seo_open_get_more_credits_modal();' );
	}

	// Show a "Customize PAYG" Button.
	if ( $show_customize_payg_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'sliders', esc_html__( 'Customize Pay-As-You-Go', 'ai-for-seo' ), '', 'ai4seo_handle_open_customize_payg_modal();' );
	}

	// Show a "Increase Budget" Button (same as customize payg).
	if ( $show_increase_payg_budget_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'sliders', esc_html__( 'Increase Budget', 'ai-for-seo' ), '', 'ai4seo_handle_open_customize_payg_modal();' );
	}

	// Show a "Get an exclusive quote" Button.
	if ( $show_get_a_custom_quote_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( AI4SEO_OFFICIAL_CONTACT_URL, '', '_blank', 'handshake', __( 'Get an exclusive quote', 'ai-for-seo' ) );
	}

	// show a Set up SEO Autopilot button.
	if ( $show_set_up_seo_autopilot_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag( 'paper-plane', esc_html__( 'Set up SEO Autopilot', 'ai-for-seo' ), '', 'ai4seo_open_modal_from_schema("seo-autopilot", {modal_size: "small", unsaved_changes_warnings: true});' );
	}

	// Show a "Go to Settings" button.
	if ( $show_go_to_settings_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( $settings_url, '', '', 'gear', __( 'Go to Settings', 'ai-for-seo' ), 'ai4seo-primary-button' );
	}

	// Show an "Account Settings" button.
	if ( $show_go_to_account_settings_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag(
			$account_url,
			'',
			'',
			'key',
			esc_html( $go_to_account_settings_button_label ),
			$go_to_account_settings_button_css_class,
			'',
			$go_to_account_settings_button_label
		);
	}

	// Show an "Update Plugin" button.
	if ( $show_update_plugin_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( $wp_admin_plugins_list_url, '', '', 'circle-up', __( 'Update Plugin', 'ai-for-seo' ), 'ai4seo-primary-button' );
	}

	// Show a lost license key button.
	if ( $show_lost_licence_key_button ) {
		$notification_buttons .= ai4seo_get_icon_button_tag(
			'key-slash',
			esc_html( $lost_licence_key_button_label ),
			'',
			'ai4seo_open_lost_key_modal();',
			$lost_licence_key_button_label
		);
	}

	// Show a "Go to Help section" button.
	if ( $show_go_to_help_button ) {
		$notification_buttons .= ai4seo_get_a_tag_icon_button_tag( $help_url, '', '', 'circle-question', __( 'Go to Help', 'ai-for-seo' ), 'ai4seo-primary-button' );
	}

	// Append the refresh action in the default position for notices that do not request first placement.
	if ( ! $show_sync_account_button_first && $sync_account_button ) {
		$notification_buttons .= $sync_account_button;
	}

	// contact us button.
	if ( $show_contact_us_button ) {
		$notification_buttons .= ai4seo_get_contact_us_button();
	}

	// dismiss / not now button.
	if ( $show_dismiss_button || $show_not_now_button ) {
		// dismiss button.
		$notification_buttons .= '<button type="button" class="ai4seo-button ai4seo-abort-button ai4seo-notification-dismiss-button" data-notification-index="' . esc_attr( $notification_index ) . '" title="' . esc_attr__( 'Dismiss this notification', 'ai-for-seo' ) . '">';
		if ( $show_not_now_button ) {
			$notification_buttons .= esc_html__( 'Not now', 'ai-for-seo' );
		} else {
			$notification_buttons .= esc_html__( 'Dismiss', 'ai-for-seo' );
		}
		$notification_buttons .= '</button>';
	}

	return $notification_buttons;
}


/**
 * Determine whether all configured notification conditions are satisfied.
 *
 * @param string $notification_index Notification identifier.
 * @param array  $additional_fields Additional notification fields and conditions.
 * @param bool   $skip_num_displayable_notification_condition Whether to skip the visible-notification-count condition.
 * @return bool Whether the notification conditions are satisfied.
 */
function ai4seo_check_notification_conditions( string $notification_index, array $additional_fields = array(), bool $skip_num_displayable_notification_condition = false ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 3 ) ) {
		ai4seo_debug_message( 283333336, 'Prevented loop', true );
		return false;
	}

	$conditions = array();
	$debug      = false; // set to true to enable debug logging.

	// go through each $additional_fields and check if one is suffixed with "_condition", filter them out.
	foreach ( $additional_fields as $field_name => $field_value ) {
		if ( substr( $field_name, -10 ) === '_condition' ) {
			$conditions[ $field_name ] = $field_value;
		}
	}

	if ( ! $conditions ) {
		if ( $debug ) {
			ai4seo_debug_message( 472507849, $notification_index . '>' . ai4seo_stringify( 'No conditions to check, passing by default.' ) );
		}

		return true;
	}

	// go through each condition and check if it is met.
	foreach ( $conditions as $condition_name => $condition_value ) {
		if ( 'true' === $condition_value ) {
			$condition_value = true; // convert "true" string to boolean true.
		} elseif ( 'false' === $condition_value ) {
			$condition_value = false; // convert "false" string to boolean false.
		} else {
			$condition_value = ai4seo_deep_sanitize( $condition_value ); // sanitize string values.
		}

		switch ( $condition_name ) {
			case 'min_num_missing_entries_condition':
				// check if the number of missing entries is less than the condition value.
				$min_num_missing_entries_condition = (int) $condition_value;
				$num_missing_posts                 = 0;
				$num_missing_posts_by_post_type    = ai4seo_get_num_missing_posts_by_post_type();

				if ( $num_missing_posts_by_post_type ) {
					$num_missing_posts = array_sum( $num_missing_posts_by_post_type );
				}

				if ( $num_missing_posts < $condition_value ) {
					if ( $debug ) {
						ai4seo_debug_message( 225223351, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $num_missing_posts . ' < ' . $condition_value ) );
					}

					return false; // condition not met.
				}
				break;

			case 'max_credits_balance_condition':
				// check if the credits balance is less than the condition value.
				$max_credits_balance_condition = (int) $condition_value;
				$credits_balance               = ai4seo_robhub_api()->get_credits_balance();

				if ( $credits_balance > $max_credits_balance_condition ) {
					if ( $debug ) {
						ai4seo_debug_message( 104350543, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $credits_balance . ' > ' . $max_credits_balance_condition ) );
					}

					return false; // condition not met.
				}
				break;

			case 'min_credits_balance_condition':
				// check if the credits balance is greater than the condition value.
				$min_credits_balance_condition = (int) $condition_value;
				$credits_balance               = ai4seo_robhub_api()->get_credits_balance();

				if ( $credits_balance < $min_credits_balance_condition ) {
					if ( $debug ) {
						ai4seo_debug_message( 574266499, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $credits_balance . ' < ' . $min_credits_balance_condition ) );
					}

					return false; // condition not met.
				}
				break;

			case 'do_credits_cover_all_missing_entries_condition':
				// check if the credits cover all missing entries.
				$do_credits_cover_all_missing_entries_condition = (bool) $condition_value;
				$credits_balance                                = ai4seo_robhub_api()->get_credits_balance();

				$needed_amount_of_credits_to_cover_all_missing_entries = ai4seo_get_approximate_credits_needed();
				$do_credits_cover_all_missing_entries                  = $credits_balance >= $needed_amount_of_credits_to_cover_all_missing_entries;

				if ( $do_credits_cover_all_missing_entries_condition !== $do_credits_cover_all_missing_entries ) {
					if ( $debug ) {
						ai4seo_debug_message( 665345926, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': credits_balance = ' . $credits_balance . ', needed = ' . $needed_amount_of_credits_to_cover_all_missing_entries . ', condition = ' . ( $do_credits_cover_all_missing_entries_condition ? 'true' : 'false' ) . ', actual = ' . ( $do_credits_cover_all_missing_entries ? 'true' : 'false' ) ) );
					}

					return false; // condition not met.
				}
				break;

			case 'has_purchased_something_condition':
				// check if the user has purchased something.
				$has_purchased_something_condition = (bool) $condition_value;
				$has_purchased_something           = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );

				if ( $has_purchased_something_condition !== $has_purchased_something ) {
					if ( $debug ) {
						ai4seo_debug_message( 530710183, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . ( $has_purchased_something ? 'true' : 'false' ) . ' != ' . ( $has_purchased_something_condition ? 'true' : 'false' ) ) );
					}

					return false; // condition not met.
				}
				break;

			case 'max_num_unread_notifications_condition':
				// check if the number of unread notifications is less than the condition value.
				$max_num_unread_notifications_condition = (int) $condition_value;
				$num_unread_notifications               = ai4seo_get_num_unread_notification();

				if ( $num_unread_notifications > $max_num_unread_notifications_condition ) {
					if ( $debug ) {
						ai4seo_debug_message( 386181290, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $num_unread_notifications . ' > ' . $max_num_unread_notifications_condition ) );
					}

					return false; // condition not met.
				}
				break;

			case 'max_num_visible_notifications_condition':
				if ( $skip_num_displayable_notification_condition ) {
					// skip this condition if we are not checking the number of displayable notifications to prevent a loop.
					break;
				}

				// check if the number of undismissed notifications is less than the condition value.
				$max_num_visible_notifications_condition = (int) $condition_value;
				$num_visible_notifications               = count( ai4seo_get_displayable_notifications( true, false ) ) - 1; // account for this notification itself.

				if ( $num_visible_notifications > $max_num_visible_notifications_condition ) {
					if ( $debug ) {
						ai4seo_debug_message( 637430938, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $num_visible_notifications . ' > ' . $max_num_visible_notifications_condition ) );
					}

					return false; // condition not met.
				}
				break;

			case 'is_robhub_account_synced_condition':
				// check if the RobHub account is synced.
				$is_robhub_account_synced_condition = (bool) $condition_value;
				$is_robhub_account_synced           = (bool) ai4seo_robhub_api()->is_account_synced();

				if ( $is_robhub_account_synced_condition !== $is_robhub_account_synced ) {
					if ( $debug ) {
						ai4seo_debug_message( 689873061, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . ( $is_robhub_account_synced ? 'true' : 'false' ) . ' != ' . ( $is_robhub_account_synced_condition ? 'true' : 'false' ) ) );
					}

					return false; // condition not met.
				}

				break;

			case 'min_product_version_condition':
				// check if the product version is at least the condition value.
				$min_product_version_condition = ai4seo_deep_sanitize( $condition_value );
				$current_product_version       = AI4SEO_PLUGIN_VERSION_NUMBER;

				if ( version_compare( $current_product_version, $min_product_version_condition, '<' ) ) {
					if ( $debug ) {
						ai4seo_debug_message( 200593735, $notification_index . ' >' . ai4seo_stringify( $condition_name . ': ' . $current_product_version . ' < ' . $min_product_version_condition ) );
					}

					return false; // condition not met.
				}
				break;

			// unknown condition -> always opt out.
			default:
				if ( $debug ) {
					ai4seo_debug_message( 498983924, $notification_index . ' >' . ai4seo_stringify( 'Unknown condition: ' . $condition_name . ', opting out.' ) );
				}
				return false;
		}
	}

	if ( $debug ) {
		ai4seo_debug_message( 136156803, $notification_index . ' > All conditions met' );
	}

	return true;
}


/**
 * Persist the unread count represented by one final displayable notification map.
 *
 * @param array $displayable_notifications Displayable notification map.
 * @return bool Whether the derived environmental value was synchronized.
 */
function ai4seo_store_unread_count_from_displayable_notifications( array $displayable_notifications ): bool {
	if ( ai4seo_notification_unread_synchronization_is_active() ) {
		return false;
	}

	$expected_notifications = array();

	if ( ! ai4seo_get_notification_request_cache_for_current_site( $expected_notifications ) ) {
		return false;
	}

	ai4seo_notification_unread_synchronization_is_active( true );

	try {
		for ( $attempt = 0; $attempt < AI4SEO_NOTIFICATION_CAS_MAX_ATTEMPTS; ++$attempt ) {
			$unread_count = 0;

			foreach ( $displayable_notifications as $notification ) {
				if ( is_array( $notification ) && empty( $notification['read'] ) ) {
					++$unread_count;
				}
			}

			$stored_unread_count = (int) ai4seo_read_environmental_variable(
				AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT,
				false
			);

			if ( $stored_unread_count !== $unread_count
				&& ! ai4seo_update_environmental_variable(
					AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT,
					$unread_count
				) ) {
				ai4seo_mark_notification_unread_refresh_pending_for_current_site();
				return false;
			}

			// Verify that the canonical notification map did not change while its derived count was written.
			$verification_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );

			if ( null === $verification_snapshot ) {
				ai4seo_mark_notification_unread_refresh_pending_for_current_site();
				return false;
			}

			$verification_made_changes = false;
			$verified_notifications    = $verification_snapshot['exists']
				? ai4seo_normalize_stored_notifications(
					$verification_snapshot['value'],
					$verification_made_changes
				)
				: array();
			$verified_state_matches    = ! $verification_made_changes
				&& $verified_notifications === $expected_notifications
				&& ( $verification_snapshot['exists'] || array() === $expected_notifications );

			if ( $verified_state_matches ) {
				ai4seo_complete_notification_unread_refresh_for_current_site();
				return true;
			}

			// Rebuild displayability from the newer authoritative map before retrying the derived count.
			ai4seo_mark_notification_unread_refresh_pending_for_current_site();
			ai4seo_reset_notification_request_cache_for_current_site();
			$storage_ready             = false;
			$displayable_notifications = ai4seo_get_displayable_notifications( false, false, $storage_ready );

			if ( ! $storage_ready
				|| ! ai4seo_get_notification_request_cache_for_current_site( $expected_notifications ) ) {
				return false;
			}
		}

		ai4seo_mark_notification_unread_refresh_pending_for_current_site();
		return false;
	} finally {
		ai4seo_notification_unread_synchronization_is_active( false );
	}
}


/**
 * Push a trusted local notification through the current routine-check batch when available.
 *
 * @param string $notification_index The notification identifier.
 * @param string $message The notification message.
 * @param bool   $force Whether to force replace existing notification.
 * @param array  $additional_fields Additional notification fields, such as notice type and permanence.
 * @return bool True if the notification was staged or durably stored, false otherwise.
 */
function ai4seo_push_routine_notification( string $notification_index, string $message, bool $force = false, array $additional_fields = array() ): bool {
	return ai4seo_store_notification(
		$notification_index,
		$message,
		$force,
		$additional_fields,
		AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL,
		true
	);
}


/**
 * Function to refresh the unread notification counter from AI4SEO_NOTIFICATIONS_OPTION_NAME
 *
 * @return void
 */
function ai4seo_refresh_unread_notifications_count() {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 847616570, 'Prevented loop', true );
		return;
	}

	if ( ai4seo_notification_unread_synchronization_is_active() ) {
		return;
	}

	$storage_ready = false;
	ai4seo_notification_unread_synchronization_is_active( true );

	try {
		$displayable_notifications = ai4seo_get_displayable_notifications( false, false, $storage_ready );
	} finally {
		ai4seo_notification_unread_synchronization_is_active( false );
	}

	if ( $storage_ready ) {
		ai4seo_store_unread_count_from_displayable_notifications( $displayable_notifications );
	}
}


/**
 * Function to check if an notification is defined in the $notifications array
 *
 * @param string $notification_index The notification identifier.
 * @return bool True if the notification is defined, false otherwise
 */
function ai4seo_is_notification_defined( string $notification_index ): bool {
	if ( empty( $notification_index ) ) {
		return false;
	}

	// Resolve repaired state so legacy payloads cannot influence notification identity checks.
	$notifications = ai4seo_get_repaired_notifications();

	return isset( $notifications[ $notification_index ] ) && is_array( $notifications[ $notification_index ] );
}


/**
 * Function to get the amount of unread notifications
 *
 * @return int The number of unread notifications
 */
function ai4seo_get_num_unread_notification(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 769273087, 'Prevented loop', true );
		return 0;
	}

	return (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT );
}


/**
 * Function to mark all notifications as read
 *
 * @return bool True if all notifications were marked as read, false otherwise
 */
function ai4seo_mark_all_displayable_notifications_as_read(): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 527064110, 'Prevented loop', true );
		return false;
	}

	// Capture only the rows visible to this user; fresh CAS retries leave concurrently added rows untouched.
	$displayable_notifications = ai4seo_get_displayable_notifications( false, true );

	if ( empty( $displayable_notifications ) ) {
		return false; // no notifications to mark as read.
	}

	$displayable_notification_indexes = array_keys( $displayable_notifications );
	$auto_dismiss_time                = time() + ( AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS * DAY_IN_SECONDS );

	// Apply read state only to the captured visible identities from each fresh authoritative retry snapshot.
	return ai4seo_commit_notification_mutation(
		static function ( array $notifications ) use ( $displayable_notification_indexes, $auto_dismiss_time ): array {
			$original_notifications = $notifications;

			foreach ( $displayable_notification_indexes as $notification_index ) {
				if ( ! isset( $notifications[ $notification_index ] ) || ! is_array( $notifications[ $notification_index ] ) ) {
					continue;
				}

				if ( ! empty( $notifications[ $notification_index ]['read'] ) ) {
					continue;
				}

				$notifications[ $notification_index ]['read']              = true;
				$notifications[ $notification_index ]['time_auto_dismiss'] = $auto_dismiss_time;
			}

			return array(
				'notifications'        => $notifications,
				'delete_option'        => false,
				'result'               => true,
				'refresh_unread_count' => $notifications !== $original_notifications,
			);
		}
	);
}


/**
 * Function to mark a notification as read by index
 *
 * @param string $notification_index The notification identifier.
 * @return bool True if notification was marked as read, false otherwise
 */
function ai4seo_mark_notification_as_read( string $notification_index ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 715015911, 'Prevented loop', true );
		return false;
	}

	if ( empty( $notification_index ) ) {
		return false;
	}

	$auto_dismiss_time = time() + ( AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS * DAY_IN_SECONDS );

	// Derive read state from each fresh snapshot so concurrent notification fields remain intact.
	return ai4seo_commit_notification_mutation(
		static function ( array $notifications ) use ( $notification_index, $auto_dismiss_time ): array {
			$original_notifications = $notifications;
			$result                 = isset( $notifications[ $notification_index ] );

			if ( $result && empty( $notifications[ $notification_index ]['read'] ) ) {
				$notifications[ $notification_index ]['read'] = true;

				if ( empty( $notifications[ $notification_index ]['is_permanent'] ) ) {
					$notifications[ $notification_index ]['time_auto_dismiss'] = $auto_dismiss_time;
				}
			}

			return array(
				'notifications'        => $notifications,
				'delete_option'        => false,
				'result'               => $result,
				'refresh_unread_count' => $notifications !== $original_notifications,
			);
		}
	);
}


/**
 * Function to mark a notification as dismissed by index
 *
 * @param string $index The notification identifier.
 * @return bool True if notification was marked as dismissed, false otherwise
 */
function ai4seo_mark_notification_as_dismissed( string $index ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 398944775, 'Prevented loop', true );
		return false;
	}

	if ( empty( $index ) ) {
		return false;
	}

	$dismissed_at = time();

	// Preserve authenticated provenance while blanking dismissed content on each fresh retry snapshot.
	return ai4seo_commit_notification_mutation(
		static function ( array $notifications ) use ( $index, $dismissed_at ): array {
			$original_notifications = $notifications;
			$result                 = isset( $notifications[ $index ] );

			if ( $result && empty( $notifications[ $index ]['dismissed'] ) ) {
				$was_trusted_local = ai4seo_is_trusted_local_notification_message( $index, $notifications[ $index ] );

				// Blank dismissed payloads preserve history without retaining unnecessary remote markup.
				$notifications[ $index ]['dismissed']      = true;
				$notifications[ $index ]['time_dismissed'] = $dismissed_at;
				$notifications[ $index ]['message']        = '';
				ai4seo_apply_notification_message_source(
					$notifications[ $index ],
					$index,
					'',
					$was_trusted_local ? AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL : AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE
				);
			}

			return array(
				'notifications'        => $notifications,
				'delete_option'        => false,
				'result'               => $result,
				'refresh_unread_count' => $notifications !== $original_notifications,
			);
		}
	);
}


/**
 * Determine whether a notification has been dismissed.
 *
 * @param string $notification_index Notification identifier.
 * @return bool Whether the notification is dismissed.
 */
function ai4seo_is_notification_dismissed( string $notification_index ): bool {
	// Make sure that $notification_index-parameter has content.
	if ( ! $notification_index ) {
		ai4seo_debug_message( 511301024, 'Notification index is empty.', true );
		return false;
	}

	// Read repaired state so forged provenance cannot survive ancillary notification checks.
	$notifications = ai4seo_get_repaired_notifications();

	if ( ! isset( $notifications[ $notification_index ] ) ) {
		return false;
	}

	return isset( $notifications[ $notification_index ]['dismissed'] ) && $notifications[ $notification_index ]['dismissed'];
}


/**
 * Remove one notification through either the public immediate or routine batched writer.
 *
 * @param string $notification_index Notification identifier.
 * @param bool   $routine_mutation Whether to use the active routine-check batch.
 * @return bool Whether the notification was removed.
 */
function ai4seo_remove_notification_with_commit( string $notification_index, bool $routine_mutation ): bool {
	if ( empty( $notification_index ) ) {
		return false;
	}

	$batched_target_exists = ai4seo_batched_raw_notification_index_exists( $notification_index );
	$target_seen           = true === $batched_target_exists;

	// The selected minimal compatibility fix uses one raw pre-read outside routine batches.
	if ( null === $batched_target_exists ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_NOTIFICATIONS_OPTION_NAME );
		$target_seen     = null !== $option_snapshot
			&& $option_snapshot['exists']
			&& is_array( $option_snapshot['value'] )
			&& array_key_exists( $notification_index, $option_snapshot['value'] );
	}

	$commit_notification_mutation = $routine_mutation
		? 'ai4seo_commit_routine_notification_mutation'
		: 'ai4seo_commit_notification_mutation';

	// Track target presence across retries so a lost CAS cannot turn a completed removal into a false negative.
	return $commit_notification_mutation(
		static function ( array $notifications ) use ( $notification_index, &$target_seen ): array {
			$original_notifications = $notifications;

			if ( isset( $notifications[ $notification_index ] ) ) {
				$target_seen = true;
				unset( $notifications[ $notification_index ] );
			}

			return array(
				'notifications'        => $notifications,
				'delete_option'        => false,
				'result'               => $target_seen,
				'refresh_unread_count' => $notifications !== $original_notifications,
			);
		}
	);
}


/**
 * Function to remove a notification entry by index.
 *
 * @param string $notification_index The notification identifier.
 * @return bool True if notification was removed, false otherwise.
 */
function ai4seo_remove_notification( string $notification_index ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 831312893, 'Prevented loop', true );
		return false;
	}

	return ai4seo_remove_notification_with_commit( $notification_index, false );
}


/**
 * Remove a notification through the current routine-check batch when available.
 *
 * @param string $notification_index The notification identifier.
 * @return bool True if notification was staged or removed, false otherwise.
 */
function ai4seo_remove_routine_notification( string $notification_index ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 831312894, 'Prevented routine notification removal loop.', true );
		return false;
	}

	return ai4seo_remove_notification_with_commit( $notification_index, true );
}


/**
 * Function to remove all notifications
 */
function ai4seo_remove_all_notifications() {
	// Delete only the exact authoritative snapshot supplied to the callback so concurrent additions survive a retry.
	ai4seo_commit_notification_mutation(
		static function ( array $notifications ): array {
			return array(
				'notifications'        => array(),
				'delete_option'        => true,
				'result'               => true,
				'refresh_unread_count' => array() !== $notifications,
			);
		}
	);
}


// NOTIFICATION CHECKS ===================================================================.

/**
 * Evaluate and enqueue notifications relevant to the current request.
 *
 * @return void
 */
function ai4seo_check_for_new_notifications() {
	if ( ! ai4seo_can_run_dashboard_or_cron_tasks() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Stage every routine intent against one authoritative safe snapshot and replay the batch on conflict.
	if ( ! ai4seo_begin_notification_mutation_batch() ) {
		return;
	}

	$notification_batch_scope = ai4seo_get_site_options_request_cache_scope();
	$notification_checks_ran  = false;

	try {
		$is_user_on_our_dashboard = ai4seo_is_plugin_page_active( 'dashboard' );

		// push these notifications, only on our dashboard to save resources.

		if ( $is_user_on_our_dashboard ) {
			// present a fresh missing entries notification on the dashboard page.
			ai4seo_check_for_missing_entries_notification();

			// check for wpml plugin heads up notification.
			ai4seo_check_for_wpml_heads_up_notification();

			// check for rate us notification.
			ai4seo_check_for_rate_us_notification();
		}

		// push these notifications, even when the user is not inside our plugin admin pages ->.
		ai4seo_check_for_low_credits_balance_notification();
		ai4seo_check_for_inefficient_cron_jobs_notification();
		ai4seo_check_for_finished_seo_autopilot_notification();
		ai4seo_check_for_unfinished_posts_table_analysis_notification( true );
		ai4seo_check_for_heavy_db_operations_disabled_notification();
		$notification_checks_ran = true;
	} finally {
		if ( $notification_checks_ran
			&& ai4seo_get_site_options_request_cache_scope() === $notification_batch_scope ) {
			ai4seo_flush_notification_mutation_batch();
		} else {
			ai4seo_abort_notification_mutation_batch( $notification_batch_scope );
		}
	}
}


/**
 * Update the notification for an unfinished posts-table analysis.
 *
 * @param bool $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_unfinished_posts_table_analysis_notification( $force = false ) {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 5 ) ) {
		return;
	}

	$notification_index = 'unfinished-posts-table-analysis';

	$posts_table_analysis_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE );

	if ( 'completed' === $posts_table_analysis_state ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// if we have dismissed this notification before, we don't show it again.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	$posts_table_analysis_last_post_id = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID );

	// read last post id in posts table.
	if ( ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE ) ) {
		$max_post_id_in_wp_posts_table = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE );
	} else {
		$max_post_id_query = ai4seo_prepare_database_query(
			'SELECT MAX(ID) FROM {{posts_table}}',
			array(
				'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			)
		);

		if ( false === $max_post_id_query ) {
			ai4seo_debug_message( 984321698, 'Could not prepare the maximum post-ID query.', true );
			return;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler resolved the core table; this aggregate is retained in the one-hour environmental cache below.
		$max_post_id_in_wp_posts_table = (int) $wpdb->get_var( $max_post_id_query );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321698, 'Database error: ' . $wpdb->last_error, true );
			return;
		}

		ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE,
			$max_post_id_in_wp_posts_table,
			true,
			HOUR_IN_SECONDS
		);
	}

	// calculate percentage done.
	$percentage_done = 0;

	if ( $max_post_id_in_wp_posts_table > 0 ) {
		$percentage_done = round( ( $posts_table_analysis_last_post_id / $max_post_id_in_wp_posts_table ) * 100 );
	}

	$message = sprintf(
		/* translators: %s: plugin name */
		esc_html__( 'Your pages and media files are being analyzed to improve SEO coverage statistics. This process helps %s identify which content needs AI optimization. Please wait until the analysis is complete.', 'ai-for-seo' ),
		esc_html( AI4SEO_PLUGIN_NAME )
	);

	$message .= '<br><br>';

	$message .= "<progress class='ai4seo-seo-coverage-progress-bar ai4seo-green-animated-progress-bar ai4seo-progress-bar-not-finished' value='" . esc_attr( $percentage_done ) . "' max='100'></progress>";

	/* translators: %s: Percentage of posts table analysis completed. */
	$message .= sprintf( esc_html__( 'Progress: %s%% completed', 'ai-for-seo' ), esc_html( ai4seo_format_number_i18n( $percentage_done ) ) );

	// in smaller font the number of posts analyzed so far and max entries,
	// also the estimated time remaining considering AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE, AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME and AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS.
	$num_posts_analyzed_so_far = $posts_table_analysis_last_post_id;
	$num_posts_remaining       = $max_post_id_in_wp_posts_table - $num_posts_analyzed_so_far;

	$num_batches_remaining            = ceil( $num_posts_remaining / AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE );
	$num_batches_per_seconds          = round( ( AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME / ( AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS / 100000 ) ) ); // how many batches can be processed in 10 seconds (considering auto dashboard reloads triggering a batch-stack).
	$estimated_time_remaining_seconds = ( $num_batches_remaining / max( $num_batches_per_seconds, 1 ) ) * 10; // in seconds.

	$message     .= " <span class='ai4seo-sub-info'>";
		$message .= sprintf(
			/* translators: 1: Number of processed entries. 2: Total number of entries. 3: Estimated time remaining. */
			esc_html__( '(%1$s / %2$s entries. Estimated time remaining: %3$s. This page refreshes automatically until the analysis is complete.)', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $num_posts_analyzed_so_far ) ),
			esc_html( ai4seo_format_number_i18n( $max_post_id_in_wp_posts_table ) ),
			sprintf(
				/* translators: %s: Estimated time remaining in seconds. */
				_n( '%s second', '%s seconds', $estimated_time_remaining_seconds, 'ai-for-seo' ),
				esc_html( ai4seo_format_number_i18n( $estimated_time_remaining_seconds ) )
			),
		);
	$message .= '</span>';

	// push the notification.
	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                     => 'notice-info',
			'is_permanent'                    => true,
			'ignore_during_dashboard_refresh' => false,
		)
	);
}


/**
 * Update the notification describing changes since the last known plugin version.
 *
 * @param string $last_known_plugin_version Last plugin version known to the user.
 * @param bool   $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_plugin_update_notification( $last_known_plugin_version, $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'plugin-update';

	// go through change log and collect useful data.
	$full_change_log        = ai4seo_get_change_log();
	$total_num_changes      = 0;
	$change_log_examples    = array();
	$missed_plugin_versions = array();

	foreach ( $full_change_log as $change_log_entry ) {
		if ( ! isset( $change_log_entry['important'] ) || ! $change_log_entry['important'] ) {
			continue;
		}

		// we can break here since there are no versions lower than the last known plugin version left.
		if ( ! isset( $change_log_entry['version'] ) || version_compare( $change_log_entry['version'], $last_known_plugin_version, '<=' ) ) {
			break;
		}

		if ( ! isset( $change_log_entry['updates'] ) || ! $change_log_entry['updates'] ) {
			continue;
		}

		$missed_plugin_versions[] = $change_log_entry['version'];
		$total_num_changes       += count( $change_log_entry['updates'] );

		foreach ( $change_log_entry['updates'] as $this_update ) {
			if ( count( $change_log_examples ) < 3 ) {
				$change_log_examples[] = $this_update;
			}
		}
	}

	// if we only have one entry and this contains "maintenance" updates, we skip showing this notification.
	if ( count( $change_log_examples ) === 1 ) {
		$first_change_log_example = reset( $change_log_examples );

		if ( stripos( $first_change_log_example, 'Maintenance' ) !== false ) {
			ai4seo_remove_notification( $notification_index );
			return;
		}
	}

	if ( ! $missed_plugin_versions ) {
		ai4seo_remove_notification( $notification_index );
		return;
	}

	// if we have dismissed this notification before, we don't show it again.
	if ( ! $force && ai4seo_is_notification_dismissed( $notification_index ) ) {
		return;
	}

	$remaining_changes = ( $total_num_changes - count( $change_log_examples ) );

	// build message.
	$message = sprintf(
	/* translators: 1: Plugin name, 2: Plugin version, 3: Plugin versions */
		esc_html__( 'Heads up! %1$s has been updated from version %2$s to version %3$s, and it includes %4$s important improvements:', 'ai-for-seo' ),
		'<strong>' . AI4SEO_PLUGIN_NAME . '</strong>',
		$last_known_plugin_version,
		'<strong>' . AI4SEO_PLUGIN_VERSION_NUMBER . '</strong>',
		'<strong>' . $total_num_changes . '</strong>'
	);

	$message .= '<ul>';
	foreach ( $change_log_examples as $this_example ) {
		$message .= '<li>' . esc_html( $this_example ) . '</li>';
	}

	if ( $remaining_changes > 0 ) {
		$message .= '<li>';
		$message .= sprintf(
			/* translators: %s: Number of additional changelog improvements. */
			esc_html__( 'And %1$s more improvements!', 'ai-for-seo' ),
			"<strong>{$remaining_changes}</strong>",
		);
		$message .= '</li>';
	}
	$message .= '</ul>';

	if ( $remaining_changes > 0 ) {
		$message .= esc_html__( '👉 Check out the full changelog by clicking the button below.', 'ai-for-seo' );
	}

	// push the notification.
	ai4seo_push_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                             => 'notice-info',
			'max_num_visible_notifications_condition' => 1, // prevent spam.
			'see_whats_new_button'                    => $remaining_changes > 0,
		)
	);
}


// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Preserve the public force parameter.
/**
 * Update the warning shown while heavy database operations are disabled.
 *
 * @param bool $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_heavy_db_operations_disabled_notification( bool $force = false ) {
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'heavy-db-operations-disabled';

	if ( ! ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	$help_troubleshooting_url = ai4seo_get_subpage_url( 'help' ) . '#ai4seo-troubleshooting-section';

	$message = sprintf(
		/* translators: %s: Help & Troubleshooting URL */
		__( 'Heavy database refresh operations are currently <strong>disabled for debugging</strong>. Coverage statistics and generation summaries may be outdated until you re-enable this option under <a href="%s" target="_blank" rel="noopener noreferrer">Help &gt; Troubleshooting</a>.', 'ai-for-seo' ),
		esc_url( $help_troubleshooting_url )
	);

	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		true,
		array(
			'notice_type'       => 'notice-warning',
			'is_permanent'      => true,
			'go_to_help_button' => true,
		)
	);
}


/**
 * Update the informational notification shown when WPML is active.
 *
 * @param bool $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_wpml_heads_up_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'wpml-heads-up';

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	// check if WPML plugin is active.
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	$message = sprintf(
		/* translators: 1: Plugin name “WPML”, 2: Your plugin name */
		esc_html__( 'Just a heads-up — this isn’t a warning. %1$s is currently active on your website, and %2$s is fully compatible with it. Here are a few useful tips tailored to your setup:', 'ai-for-seo' ),
		'<strong>WPML</strong>',
		'<span class="ai4seo-plugin-name">' . AI4SEO_PLUGIN_NAME . '</span>'
	);
	$message     .= '<ul>';
		$message .= '<li>1. ' . esc_html__( 'Metadata and media attributes should be generated for each entry in every language. For this reason, the total number displayed on the dashboard appears higher, as each entry is processed separately for each language.', 'ai-for-seo' ) . '</li>';
		$message .= '<li>2. ' . esc_html__( "For best results, we recommend keeping the language settings at \"automatic\", as this ensures the metadata is generated correctly for each language using WPML's language detection.", 'ai-for-seo' ) . '</li>';
	$message     .= '</ul>';

	$message .= esc_html__( 'You may safely dismiss this notification, once you are aware of the above.', 'ai-for-seo' );

	// push the notification.
	ai4seo_push_routine_notification( $notification_index, $message, $force );
}


/**
 * Update the rating-request notification for eligible users.
 *
 * @param bool $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_rate_us_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'rate-us';

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	$ai4seo_has_purchased_something = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );

	// user has not purchased anything, no need to show the rate us notification yet.
	if ( ! $ai4seo_has_purchased_something ) {
		return;
	}

	// todo: user already rated us, no need to show the rate us notification again.

	$message  = esc_html__( "We hope you're enjoying the plugin and we'd love to hear your feedback and thoughts on your experience. Leaving a comment and rating our plugin using the button below truly helps us with further development and allows us to maintain high support standards. Your input is greatly appreciated!", 'ai-for-seo' );
	$message .= '<br><br>' . sprintf(
			/* translators: %s: plugin name */
		esc_html__( 'On behalf of the entire %s team, thank you for your support!', 'ai-for-seo' ),
		esc_html( AI4SEO_PLUGIN_NAME )
	);
	$message .= ' ❤️';

	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                             => 'notice-success',
			'show_avatar'                             => true,
			'rate_us_button'                          => true,
			'max_num_visible_notifications_condition' => 0, // prevent spam, catch a focused moment.
			'is_robhub_account_synced_condition'      => true, // only show this notification if the RobHub account is synced.
			'has_purchased_something_condition'       => true, // only show this notification if the user has purchased something.
		)
	);
}


/**
 * Update the notification for a low RobHub credit balance.
 *
 * @param bool $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_low_credits_balance_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'low-credits-balance';

	// check credits balance.
	$current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

	// everything is fine, no need to show a notice.
	if ( $current_credits_balance >= AI4SEO_LOW_CREDITS_THRESHOLD ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	// very low credits balance, show notice-error.
	if ( $current_credits_balance < AI4SEO_VERY_LOW_CREDITS_THRESHOLD ) {
		$notice_type = 'notice-error';
		$message     = sprintf(
			/* translators: %s: Remaining credits balance. */
			__( "<span class='ai4seo-text-critical'>Remaining Credits: %s</span>. Your Credits are running very low.", 'ai-for-seo' ),
			ai4seo_format_number_i18n( $current_credits_balance )
		);
	} else {
		$notice_type = 'notice-warning';
		$message     = sprintf(
			/* translators: %s: Remaining credits balance. */
			__( '<strong>Remaining Credits: %s</strong>. Your Credits are running low.', 'ai-for-seo' ),
			ai4seo_format_number_i18n( $current_credits_balance )
		);
	}

	$message .= '<br><br>' . __( 'To continue improving your remaining content, please consider purchasing more Credits using the <strong>Get more Credits</strong> button below. You can also activate <strong>Pay-As-You-Go</strong> to ensure you never run out of Credits.', 'ai-for-seo' );
	$message .= '<br><br>' . __( "Have questions or need a custom quote? Just click the <strong>Get an exclusive quote</strong> button to contact us. We're happy to find a solution that fits your needs.", 'ai-for-seo' );

	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                        => $notice_type,
			'is_permanent'                       => true,
			'get_more_credits_button'            => true,
			'get_a_custom_quote_button'          => true,
			'is_robhub_account_synced_condition' => true, // only show this notification if the RobHub account is synced.
			'ignore_during_dashboard_refresh'    => false, // refersh this notification even during dashboard refreshes.
		)
	);
}


/**
 * Function to add the performance notice. ATTENTION: Make sure to add the admin notices if the user got the rights to see them
 *
 * @param bool $force The force value.
 * @return void
 */
function ai4seo_check_for_missing_entries_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'missing-entries';

	$posts_table_analysis_state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE );

	// don't show missing entries notification while analysis is still ongoing.
	if ( 'completed' !== $posts_table_analysis_state ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	$messages = array();

	// check current credits balance.
	$current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

	// MISSING POSTS
	// do we even have missing posts? If not, we can skip the notice.
	$num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();
	$num_failed_posts_by_post_type  = ai4seo_get_num_failed_posts_by_post_type();

	// remove all empty post types.
	foreach ( $num_missing_posts_by_post_type as $post_type => $num_posts ) {
		// check for failed entries (to subtract them).
		if ( isset( $num_failed_posts_by_post_type[ $post_type ] ) && $num_failed_posts_by_post_type[ $post_type ] ) {
			$num_posts -= $num_failed_posts_by_post_type[ $post_type ];
		}

		if ( $num_posts <= 0 ) {
			unset( $num_missing_posts_by_post_type[ $post_type ] );
		}

		// also remove if this post type is auto generated, and we have enough credits left, as it will be fully generated soon.
		if ( ai4seo_is_bulk_generation_enabled( $post_type ) && $current_credits_balance >= AI4SEO_VERY_LOW_CREDITS_THRESHOLD ) {
			unset( $num_missing_posts_by_post_type[ $post_type ] );
		}
	}

	// if there are no missing posts, return.
	if ( empty( $num_missing_posts_by_post_type ) ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// GENERATED POSTS
	// check ai4seo_get_generation_status_summary_entry for generated posts.
	$num_generated_by_post_type = ai4seo_get_num_generated_posts_by_post_type();

	// remove empty post types.
	foreach ( $num_generated_by_post_type as $post_type => $num_posts ) {
		if ( 0 === $num_posts ) {
			unset( $num_generated_by_post_type[ $post_type ] );
		}
	}

	// YOU'RE DOING GREAT SO FAR! NOTICE.
	if ( $num_generated_by_post_type ) {
		$generated_post_types_strings_parts = array();

		foreach ( $num_generated_by_post_type as $post_type => $num_posts ) {
			// attachment -> media workaround.
			if ( 'attachment' === $post_type ) {
				$post_type = 'media file';
			}

			$generated_post_types_strings_parts[] = ai4seo_get_post_type_translation( $post_type, $num_posts );
		}

		// build $post_types_to_mention_string by separating with commas and the last one with "and".
		if ( count( $generated_post_types_strings_parts ) > 1 ) {
			$generated_post_types_complete_string = implode( ', ', array_slice( $generated_post_types_strings_parts, 0, -1 ) ) . ' ' . __( 'and', 'ai-for-seo' ) . ' ' . end( $generated_post_types_strings_parts );
		} else {
			$generated_post_types_complete_string = $generated_post_types_strings_parts[0];
		}

		$messages[] = sprintf(
			/* Translators: %1$s is replaced with bold text. */
			__( '<strong>You\'re doing great so far!</strong> You already generated SEO-relevant data for %1$s.', 'ai-for-seo' ),
			'<strong>' . esc_html( $generated_post_types_complete_string ) . '</strong>'
		);
	}

	// ROOM FOR IMPROVEMENT! NOTICE.
	$missing_post_types_strings_parts = array();

	foreach ( $num_missing_posts_by_post_type as $post_type => $num_posts ) {
		// attachment -> media workaround.
		if ( 'attachment' === $post_type ) {
			$post_type = 'media file';
		}

		$missing_post_types_strings_parts[] = ai4seo_get_post_type_translation( $post_type, $num_posts );
	}

	// build $post_types_to_mention_string by separating with commas and the last one with "and"
	// only, when we already have generated posts.
	if ( $missing_post_types_strings_parts && $num_generated_by_post_type ) {
		if ( count( $missing_post_types_strings_parts ) > 1 ) {
			$missing_post_types_complete_string = implode( ', ', array_slice( $missing_post_types_strings_parts, 0, -1 ) ) . ' ' . __( 'and', 'ai-for-seo' ) . ' ' . end( $missing_post_types_strings_parts );
		} else {
			$missing_post_types_complete_string = $missing_post_types_strings_parts[0];
		}

		$messages[] = sprintf(
			/* Translators: %1$s plugin name, %2$s is replaced with bold text of missing post types. */
			__( 'However, there is still room for improvement. <strong>%1$s</strong> has found missing or problematic data in %2$s. Please check the statistics below and consider generating the missing data to enhance your SEO performance.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			'<strong>' . esc_html( $missing_post_types_complete_string ) . '</strong>',
		);
	}

	// NO NOTICES COLLECTED SO FAR? RETURN.
	if ( ! $messages ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// PUSH NOTIFICATION.
	ai4seo_push_routine_notification(
		$notification_index,
		implode( '<br>', $messages ),
		$force,
		array(
			'set_up_seo_autopilot_button'             => true,
			'max_num_visible_notifications_condition' => 1, // prevent spam.
			'is_robhub_account_synced_condition'      => true, // only show this notification if the RobHub account is synced.
		)
	);
}


/**
 * Update the account-recovery notification for a failed RobHub response.
 *
 * @param array $api_response RobHub API response data.
 * @param bool  $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_robhub_account_error_notification( $api_response, $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'robhub-account-error';

	// if we have a successful response, potentially remove the notification.
	if ( isset( $api_response['success'] ) && $api_response['success'] ) {
		ai4seo_remove_notification( $notification_index );
		return;
	}

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_notification_dismissed( $notification_index ) ) {
		return;
	}

	// Collect API diagnostics separately so the visible notice can stay action-oriented.
	$technical_details = array();

	if ( isset( $api_response['message'] ) && $api_response['message'] ) {
		$technical_details[] = sprintf(
			/* translators: %s: Technical API error message. */
			__( 'API response: %s', 'ai-for-seo' ),
			(string) $api_response['message']
		);
	}

	if ( isset( $api_response['code'] ) && $api_response['code'] ) {
		$technical_details[] = sprintf(
			/* translators: %s: Internal support reference code. */
			__( 'Support reference: #%s', 'ai-for-seo' ),
			(string) $api_response['code']
		);
	}

	// Lead with the recovery path; technical API data is available below only when RobHub sent it.
	$message  = '<strong>' . esc_html__( 'License verification needs attention.', 'ai-for-seo' ) . '</strong> ';
	$message .= sprintf(
		/* translators: %s: Refresh license status button label. */
		esc_html__( 'First try %s to reconnect this website with the saved license data. If the issue remains, open Account settings to check the saved license or review the secure recovery guidance.', 'ai-for-seo' ),
		'<strong>' . esc_html__( 'Refresh license status', 'ai-for-seo' ) . '</strong>'
	);

	if ( $technical_details ) {
		// Keep raw support data collapsed by default so the admin notice does not become visually noisy.
		$technical_details_content = '<ul class="ai4seo-notice-technical-details-list">';

		foreach ( $technical_details as $this_technical_detail ) {
			$technical_details_content .= '<li><code>' . esc_html( $this_technical_detail ) . '</code></li>';
		}

		$technical_details_content .= '</ul>';
		$message                   .= ai4seo_get_collapsible_tag(
			esc_html__( 'Technical details', 'ai-for-seo' ),
			$technical_details_content,
			'ai4seo-notice-technical-details',
			'ai4seo-notice-technical-details-content'
		);
	}

	// Configure account recovery actions with the refresh button first, then account and recovery links.
	ai4seo_push_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                             => 'notice-error',
			'is_permanent'                            => true,
			'go_to_account_settings_button'           => true,
			'go_to_account_settings_button_label'     => __( 'Open Account settings', 'ai-for-seo' ),
			'go_to_account_settings_button_css_class' => '',
			'lost_licence_key_button'                 => true,
			'lost_licence_key_button_label'           => __( 'License recovery help', 'ai-for-seo' ),
			'sync_account_button'                     => true,
			'sync_account_button_first'               => true,
			'sync_account_button_label'               => __( 'Refresh license status', 'ai-for-seo' ),
			'sync_account_button_css_class'           => 'ai4seo-primary-button',
			'contact_us'                              => true,
			'contact_us_info'                         => false,
		)
	);
}


/**
 * Update the notification for an available plugin release.
 *
 * @param string $latest_plugin_version Latest available plugin version.
 * @param bool   $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_plugin_update_available( $latest_plugin_version, $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'plugin-update-available';

	if ( ! $latest_plugin_version ) {
		ai4seo_remove_notification( $notification_index );
		return;
	}

	// if we have the latest version, potentially remove the notification.
	if ( version_compare( AI4SEO_PLUGIN_VERSION_NUMBER, $latest_plugin_version, '>=' ) ) {
		ai4seo_remove_notification( $notification_index );
		return;
	}

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_notification_dismissed( $notification_index ) ) {
		return;
	}

	// build message.
	$message = sprintf(
		/* translators: 1: Plugin name, 2: Plugin version */
		esc_html__( 'A new version of %1$s is available: %2$s. Your current version is %3$s. Please update to the latest version to enjoy new features and improvements.', 'ai-for-seo' ),
		'<strong>' . esc_html( AI4SEO_PLUGIN_NAME ) . '</strong>',
		'<strong>' . esc_html( $latest_plugin_version ) . '</strong>',
		esc_html( AI4SEO_PLUGIN_VERSION_NUMBER )
	);

	// push the notification.
	ai4seo_push_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                             => 'notice-info',
			'update_plugin_button'                    => true,
			'max_num_visible_notifications_condition' => 1, // prevent spam.
		)
	);
}


/**
 * Normalize a Pay-As-You-Go status before notification routing.
 *
 * @param mixed $payg_status Raw Pay-As-You-Go status.
 * @return string Canonical status, or an empty string for non-scalar input.
 */
function ai4seo_normalize_payg_notification_status( $payg_status ): string {
	// Reject compound external values before canonicalization can obscure their original type.
	if ( ! is_scalar( $payg_status ) ) {
		return '';
	}

	// Accept only values already in WordPress key form so sanitation cannot create a recognized status.
	$payg_status            = (string) $payg_status;
	$normalized_payg_status = sanitize_key( $payg_status );

	return $payg_status === $normalized_payg_status ? $normalized_payg_status : '';
}


/**
 * Update the notification for a Pay-As-You-Go error status.
 *
 * @param mixed $payg_status Current Pay-As-You-Go status.
 * @param bool  $force Whether to force the notification to be refreshed.
 * @return void
 */
function ai4seo_check_for_payg_status_errors( $payg_status, $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Normalize once so removal, routing, and message selection share one exact status representation.
	$payg_status         = ai4seo_normalize_payg_notification_status( $payg_status );
	$notification_index  = 'payg-status-error';
	$payg_failure_reason = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );

	// if we have a successful response, potentially remove the notification.
	if ( ! in_array( $payg_status, array( 'budget-limit-reached', 'payment-pending', 'payment-failed', 'payment-method-failed', 'error' ), true ) ) {
		ai4seo_remove_notification( $notification_index );
		return;
	}

	$show_increase_payg_budget_button = false;
	$show_contact_us_button           = false;
	$show_get_more_credits_button     = false;
	$show_sync_account_button         = false;

	switch ( $payg_status ) {
		case 'budget-limit-reached':
			$notice_type                      = 'notice-warning';
			$message                          = __( '<strong>Budget limit reached.</strong> New usage is paused. Increase your limit to resume immediately, or wait for the next cycle.', 'ai-for-seo' );
			$show_increase_payg_budget_button = true;
			break;
		case 'payment-pending':
			$notice_type = 'notice-warning';
			$message     = sprintf(
				/* translators: %s: Refresh link label. */
				__( "<strong>Your Pay-As-You-Go payment is still pending.</strong> New usage is paused until the payment is completed. Click '%s' to check if the payment has arrived. If it takes longer than expected, please contact us for assistance.", 'ai-for-seo' ),
				'<strong>' . esc_html__( 'Refresh', 'ai-for-seo' ) . '</strong>'
			);
			$show_contact_us_button   = true;
			$show_sync_account_button = true;
			$force                    = true;
			break;
		case 'payment-method-failed':
			$notice_type = 'notice-error';
			$message     = __( '<strong>Your saved payment method could not be used for your Pay-As-You-Go refill.</strong> New usage is paused until the issue is resolved.', 'ai-for-seo' );

			if ( 'payment-method-expired' === $payg_failure_reason ) {
				$message .= ' ' . __( 'The saved payment method appears to be expired.', 'ai-for-seo' );
			} elseif ( 'payment-method-currency-mismatch' === $payg_failure_reason ) {
				$message .= ' ' . __( 'The saved payment method is not available for the required billing currency.', 'ai-for-seo' );
			} elseif ( 'payment-method-not-off-session-capable' === $payg_failure_reason ) {
				$message .= ' ' . __( 'The saved payment method does not support automatic off-session charges.', 'ai-for-seo' );
			} elseif ( 'no-payment-method' === $payg_failure_reason ) {
				$message .= ' ' . __( 'No saved payment method is currently available for automatic refills.', 'ai-for-seo' );
			}

			$message                     .= ' ' . __( 'The fastest way to continue immediately is to manually purchase a Credits Pack using the button below. This will fix most payment-method-related errors.', 'ai-for-seo' );
			$show_contact_us_button       = true;
			$show_get_more_credits_button = true;
			$show_sync_account_button     = true;
			$force                        = true;
			break;
		case 'payment-failed':
			$notice_type = 'notice-error';
			$message     = __( '<strong>Your Pay-As-You-Go payment has failed.</strong> New usage is paused until the payment issue is resolved.', 'ai-for-seo' );
			if ( 'payment-timeout' === $payg_failure_reason ) {
				$message .= ' ' . __( 'The payment confirmation took too long to arrive.', 'ai-for-seo' );
			}

			$message                     .= ' ' . __( 'The fastest way to continue immediately is to manually purchase a Credits Pack using the button below. This will fix most payment-method-related errors.', 'ai-for-seo' );
			$show_contact_us_button       = true;
			$show_get_more_credits_button = true;
			$show_sync_account_button     = true;
			$force                        = true;
			break;
		case 'error':
			$notice_type              = 'notice-error';
			$message                  = __( '<strong>There was an error with your Pay-As-You-Go refill.</strong> New usage is paused until the issue is resolved.', 'ai-for-seo' );
			$show_contact_us_button   = true;
			$show_sync_account_button = true;
			$force                    = true;
			break;
		default:
			ai4seo_remove_notification( $notification_index );
			return;
	}

	// check if this notification is already dismissed.
	if ( ! $force && ai4seo_is_notification_dismissed( $notification_index ) ) {
		return;
	}

	// push the notification.
	ai4seo_push_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                        => $notice_type,
			'is_permanent'                       => true,
			'contact_us'                         => $show_contact_us_button,
			'get_more_credits_button'            => $show_get_more_credits_button,
			'increase_payg_budget_button'        => $show_increase_payg_budget_button,
			'sync_account_button'                => $show_sync_account_button,
			'is_robhub_account_synced_condition' => true, // only show this notification if the RobHub account is synced.
		)
	);
}


/**
 * Function to eventually output a notice about inefficient cron jobs
 *
 * @param bool $force The force value.
 * @return void
 */
function ai4seo_check_for_inefficient_cron_jobs_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'inefficient-cron-jobs';

	// no need to check cron job efficiency if seo autopilot is not enabled.
	$active_bulk_generation_post_types = ai4seo_get_enabled_bulk_generation_post_types();

	if ( ! $active_bulk_generation_post_types ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// check if the SEO Autopilot was set up at least X seconds ago.
	if ( ! ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago() ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// notification dismissed and no force -> return.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	// check if the last bulk generation cron job status update time is older than XX minutes.
	$bulk_generation_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );

	if ( ! $bulk_generation_cron_job_status_update_time ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	if ( $bulk_generation_cron_job_status_update_time >= ( time() - MINUTE_IN_SECONDS * 10 ) ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// no need to check cron job efficiency if we don't have any missing posts.
	$we_got_any_missing_posts       = false;
	$num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

	foreach ( $active_bulk_generation_post_types as $this_post_type ) {
		// check if we have any missing posts for the current post type.
		if ( ! empty( $num_missing_posts_by_post_type[ $this_post_type ] )
			&& is_numeric( $num_missing_posts_by_post_type[ $this_post_type ] )
			&& $num_missing_posts_by_post_type[ $this_post_type ] > 0 ) {
			$we_got_any_missing_posts = true;
			break;
		}
	}

	if ( ! $we_got_any_missing_posts ) {
		// no missing posts for the active post types, remove the notification.
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// cron job to slow notification.
	if ( ai4seo_is_wordpress_cron_disabled() ) {
		$message = sprintf(
			/* translators: %s: plugin name */
			esc_html__( "Your server cron jobs do not appear to be functioning properly, limiting %s automation. Please ensure that server cron jobs run at least every 5 minutes (1 minute for best results) or (not recommended) enable WordPress' internal cron system.", 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
	} else {
		$message = esc_html__( 'Heads up: You’re currently using WordPress’ built-in cron system. While it works reliably, automation may run more slowly, especially on websites with low traffic. If you’re satisfied with the current automation speed, you can safely dismiss this notice. If you’d like to speed things up, search for “cron” in Help → F.A.Q. for guidance.', 'ai-for-seo' );
	}

	// push the notification.
	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                             => 'notice-warning',
			'go_to_help_button'                       => true,
			'max_num_visible_notifications_condition' => 2, // prevent spam.
			'is_robhub_account_synced_condition'      => true, // only show this notification if the RobHub account is synced.
			'ignore_during_dashboard_refresh'         => false,
		)
	);
}



/**
 * Function to eventually push a notification about SEO Autopilot being finished
 *
 * @param bool $force The force value.
 * @return void
 */
function ai4seo_check_for_finished_seo_autopilot_notification( $force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$notification_index = 'seo-autopilot-finished';

	// check if the SEO Autopilot was set up at least X seconds ago.
	if ( ! ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago( 10 ) ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// no need to check if seo autopilot is not enabled.
	$active_bulk_generation_post_types = ai4seo_get_enabled_bulk_generation_post_types();

	if ( ! $active_bulk_generation_post_types ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// notification dismissed and no force -> return.
	if ( ! $force && ai4seo_is_routine_notification_dismissed( $notification_index ) ) {
		return;
	}

	$finished_seo_autopilot_post_types_message = '';
	$translated_post_types                     = array();

	foreach ( $active_bulk_generation_post_types as $this_post_type ) {
		// attachment -> media workaround.
		if ( 'attachment' === $this_post_type ) {
			$this_post_type = 'media file';
		}

		$translated_post_types[] = ai4seo_get_post_type_translation( $this_post_type, true );
	}

	// build $post_types_to_mention_string by separating with commas and the last one with "and"
	// only, when we already have generated posts.
	if ( $translated_post_types ) {
		if ( count( $translated_post_types ) > 1 ) {
			$finished_seo_autopilot_post_types_message = implode( ', ', array_slice( $translated_post_types, 0, -1 ) ) . ' ' . __( 'and', 'ai-for-seo' ) . ' ' . end( $translated_post_types );
		} else {
			$finished_seo_autopilot_post_types_message = $translated_post_types[0];
		}
	}

	// no need to check if we still have any missing posts left.
	$we_got_any_missing_posts       = false;
	$num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

	foreach ( $active_bulk_generation_post_types as $this_post_type ) {
		// check if we have any missing posts for the current post type.
		if ( ! empty( $num_missing_posts_by_post_type[ $this_post_type ] )
			&& is_numeric( $num_missing_posts_by_post_type[ $this_post_type ] )
			&& $num_missing_posts_by_post_type[ $this_post_type ] > 0 ) {
			$we_got_any_missing_posts = true;
			break;
		}
	}

	if ( $we_got_any_missing_posts ) {
		// we have missing posts for the active post types, remove the notification.
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// check for failed generation in active post types.
	$num_failed_posts = 0;

	$num_failed_posts_by_post_type = ai4seo_get_num_failed_posts_by_post_type();

	foreach ( $active_bulk_generation_post_types as $this_post_type ) {
		// check if we have any failed posts for the current post type.
		if ( ! empty( $num_failed_posts_by_post_type[ $this_post_type ] )
			&& is_numeric( $num_failed_posts_by_post_type[ $this_post_type ] )
			&& $num_failed_posts_by_post_type[ $this_post_type ] > 0 ) {
			$num_failed_posts += $num_failed_posts_by_post_type[ $this_post_type ];
			break;
		}
	}

	// check AI4SEO_LATEST_ACTIVITY_OPTION_NAME for a successful bulk-generated entry
	// not older than the SEO Autopilot setup time, so we only show the notification
	// when at least one metadata or attachment-attributes generation actually succeeded.
	$ai4seo_seo_autopilot_start_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME );
	$latest_activity                 = ai4seo_get_option( AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array() );

	$found_a_recent_successful_bulk_generated_entry = false;

	foreach ( $latest_activity as $ai4seo_this_latest_activity_entry ) {
		if ( isset( $ai4seo_this_latest_activity_entry['action'] )
			&& strstr( $ai4seo_this_latest_activity_entry['action'], 'bulk-generated' )
			&& ( $ai4seo_this_latest_activity_entry['status'] ?? 'error' ) === 'success'
			&& isset( $ai4seo_this_latest_activity_entry['timestamp'] )
			&& $ai4seo_this_latest_activity_entry['timestamp'] >= $ai4seo_seo_autopilot_start_time ) {
			$found_a_recent_successful_bulk_generated_entry = true;
			break;
		}
	}

	// no recent successful generation found, remove the notification.
	if ( ! $found_a_recent_successful_bulk_generated_entry ) {
		ai4seo_remove_routine_notification( $notification_index );
		return;
	}

	// finished with failed generations.
	if ( $num_failed_posts ) {
		$notice_type = 'notice-warning';
		// build message.
		$message = sprintf(
			/* translators: 1: Finished post types, 2: Num failed entries */
			esc_html__( 'The SEO Autopilot has finished processing all %1$s. However, generation failed for %2$s entries. Check the “Recent Activity” section or the relevant content pages (e.g. Posts, Media) for details.', 'ai-for-seo' ),
			'<strong>' . esc_html( $finished_seo_autopilot_post_types_message ) . '</strong>',
			'<strong>' . esc_html( ai4seo_format_number_i18n( $num_failed_posts ) ) . '</strong>'
		);
	} else {
		$notice_type = 'notice-success';
		// build message.
		$message = sprintf(
			/* translators: 1: Finished post types */
			esc_html__( 'Congratulations! The SEO Autopilot has successfully finished processing all %1$s.', 'ai-for-seo' ),
			'<strong>' . esc_html( $finished_seo_autopilot_post_types_message ) . '</strong>'
		);
	}

	// push the notification.
	ai4seo_push_routine_notification(
		$notification_index,
		$message,
		$force,
		array(
			'notice_type'                        => $notice_type,
			'is_robhub_account_synced_condition' => true, // only show this notification if the RobHub account is synced.
			'ignore_during_dashboard_refresh'    => false,
		)
	);
}


/**
 * Update the active discount notification from RobHub discount data.
 *
 * @param array $discount Discount configuration received from RobHub.
 * @param bool  $allow_notification_force Whether an expiring discount may force a notification refresh.
 * @return void
 */
function ai4seo_check_discount_notification( $discount, $allow_notification_force = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	if ( ! is_array( $discount ) ) {
		return;
	}

	// Normalize API-provided content and display flags before composing the stored notification.
	$notification_index           = 'discount';
	$discount_name                = isset( $discount['name'] ) && is_string( $discount['name'] ) ? $discount['name'] : '';
	$discount_description         = isset( $discount['description'] ) && is_string( $discount['description'] )
		? ai4seo_sanitize_remote_notification_message( $discount['description'] )
		: '';
	$discount_percentage          = isset( $discount['percentage'] ) && is_numeric( $discount['percentage'] )
		? (int) $discount['percentage']
		: 0;
	$discount_expire_in           = isset( $discount['expire_in'] ) && is_numeric( $discount['expire_in'] )
		? max( 0, (int) $discount['expire_in'] )
		: 0;
	$expire_within_next_24h       = $discount_expire_in > 0 && $discount_expire_in <= 24 * HOUR_IN_SECONDS;
	$expire_at                    = $discount_expire_in > 0 ? time() + $discount_expire_in : 0;
	$discount_first_purchase_only = isset( $discount['first_purchase_only'] ) && is_scalar( $discount['first_purchase_only'] )
		? (bool) filter_var( $discount['first_purchase_only'], FILTER_VALIDATE_BOOLEAN )
		: false;
	$discount_voucher_code        = isset( $discount['voucher_code'] ) && is_string( $discount['voucher_code'] )
		? sanitize_text_field( $discount['voucher_code'] )
		: '';
	$discount_image_url           = ai4seo_sanitize_remote_notification_image_url( $discount['image'] ?? null );

	// Preserve the historical presentation flags only when a separately validated banner can satisfy them.
	$show_generic_coupon_text = ! isset( $discount['show_generic_coupon_text'] )
		|| ! is_scalar( $discount['show_generic_coupon_text'] )
		|| (bool) filter_var( $discount['show_generic_coupon_text'], FILTER_VALIDATE_BOOLEAN );

	if ( '' === $discount_image_url ) {
		$show_generic_coupon_text = true;
	}

	$show_buttons_row = ! isset( $discount['show_buttons_row'] )
		|| ( is_scalar( $discount['show_buttons_row'] )
			&& (bool) filter_var( $discount['show_buttons_row'], FILTER_VALIDATE_BOOLEAN ) );
	$is_permanent     = isset( $discount['is_permanent'] )
		&& is_scalar( $discount['is_permanent'] )
		&& (bool) filter_var( $discount['is_permanent'], FILTER_VALIDATE_BOOLEAN );
	$force            = false;

	// if we enter the last 24h, force push this notification again.
	if ( $allow_notification_force && $expire_within_next_24h ) {
		$force = true;
	}

	// Pre-defined discount descriptions.
	if ( 'early-bird' === $discount_name ) {
		$discount_description = sprintf(
			/* translators: 1: Discount percentage. 2: Remaining discount time. */
			esc_html__( 'We hope you\'re enjoying your first steps with our plugin! We understand that getting started can be challenging, so to support you, we\'re offering a special Early Bird discount of %1$s%% off all your upcoming purchases within the next %2$s.', 'ai-for-seo' ),
			'<strong>' . esc_html( ai4seo_format_number_i18n( $discount_percentage ) ) . '</strong>',
			'<strong>{{EXPIRE_COUNTDOWN}}</strong>'
		);
	}

	// exclusive offer description.
	if ( 'exclusive-offer' === $discount_name ) {
		$discount_description = sprintf(
			/* translators: 1: Discount percentage. 2: Remaining discount time. */
			esc_html__( 'As a token of our appreciation, we\'re excited to offer you an exclusive discount of %1$s%% on all your Credits Pack purchases for the next %2$s.', 'ai-for-seo' ),
			'<strong>' . esc_html( ai4seo_format_number_i18n( $discount_percentage ) ) . '</strong>',
			'<strong>{{EXPIRE_COUNTDOWN}}</strong>'
		);
	}

	// build generic description.
	if ( ! $discount_description ) {
		if ( $discount_first_purchase_only ) {
			$discount_description = sprintf(
				/* translators: %s: Discount percentage. */
				esc_html__( "As a welcome gift, we're offering you a %s discount on your first purchase.", 'ai-for-seo' ),
				'<strong>' . esc_html( ai4seo_format_number_i18n( $discount_percentage ) ) . '%</strong>'
			);
			$discount_description .= '<br>👉 ';

			$discount_description .= sprintf(
				/* translators: %s: Discount countdown placeholder. */
				esc_html__( 'This offer is only valid for the next %1$s, so make sure to claim it before it expires.', 'ai-for-seo' ),
				'<strong>{{EXPIRE_COUNTDOWN}}</strong>'
			);
		} else {
			$discount_description = sprintf(
				/* translators: %s: Discount percentage. */
				esc_html__( 'We\'re happily offering you a %1$s discount.', 'ai-for-seo' ),
				'<strong>' . esc_html( ai4seo_format_number_i18n( $discount_percentage ) ) . '%</strong>',
			);
			$discount_description .= '<br>👉 ';
			$discount_description .= ' ' . sprintf(
				/* translators: %s: Discount countdown placeholder. */
				esc_html__( 'You can use this discount for ALL your purchases within the next %1$s.', 'ai-for-seo' ),
				'<strong>{{EXPIRE_COUNTDOWN}}</strong>'
			);
		}
	}

	// Mention the action only when the corresponding button row will actually be rendered.
	$discount_call_to_action = '';

	if ( $show_buttons_row ) {
		$discount_call_to_action = sprintf(
			/* translators: %s: Call-to-action button label. */
			esc_html__( 'Click %1$s below to apply the discount now.', 'ai-for-seo' ),
			'<strong>"' . esc_html__( 'Grab Deal', 'ai-for-seo' ) . '"</strong>'
		);
	}

	$generic_coupon_text = $discount_description;

	if ( $discount_call_to_action ) {
		$generic_coupon_text .= '<br><br>' . $discount_call_to_action;
	}

	// Store an image-free text fallback; rendering creates the typed banner locally from validated metadata.
	$message = $generic_coupon_text;

	$additional_fields = array(
		'notice_type'                        => 'notice-success',
		'image'                              => $discount_image_url,
		'show_generic_coupon_text'           => $show_generic_coupon_text,
		'show_buttons_row'                   => $show_buttons_row,
		'is_permanent'                       => $is_permanent,
		'show_avatar'                        => $show_generic_coupon_text,
		'grab_deal_button'                   => true,
		'not_now_button'                     => true,
		'expire_at'                          => $expire_at,
		'voucher_code'                       => $discount_voucher_code,
		'is_robhub_account_synced_condition' => true, // only show this notification if the RobHub account is synced
		// "min_num_missing_entries_condition" => 50, # todo: use this, if we can distinguish between agency and non-agency users
		// "do_credits_cover_all_missing_entries_condition" => false, # todo: use this, if we can distinguish between agency and non-agency users.
	);

	// add has_purchased_something_condition dynamically based on if this discount is for first purchase only.
	if ( $discount_first_purchase_only ) {
		$additional_fields['has_purchased_something_condition'] = false;
	}

	// add missing entries condition if the discount depends on remaining generation work.
	if ( isset( $discount['min_num_missing_entries_condition'] )
		&& is_numeric( $discount['min_num_missing_entries_condition'] )
		&& (int) $discount['min_num_missing_entries_condition'] > 0 ) {
		$additional_fields['min_num_missing_entries_condition'] = (int) $discount['min_num_missing_entries_condition'];
	}

	// RobHub discount descriptions share the same remote provenance and narrow HTML policy as account notices.
	ai4seo_push_remote_notification( $notification_index, $message, $force, $additional_fields );
}


// endregion
// ___________________________________________________________________________________________.
