<?php
/**
 * Site-wide notification presentation and account-bound preference synchronization.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the translated names shared by Account and import previews. */
function ai4seo_get_notification_preference_labels(): array {
	return array(
		AI4SEO_SETTING_SHOW_ADMIN_OFFERS        => __( 'Show offers and discounts on other WordPress admin pages', 'ai-for-seo' ),
		/* translators: %s: Plugin name. */
		AI4SEO_SETTING_SHOW_PLUGIN_OFFERS       => sprintf( __( 'Show offers and discounts within %s', 'ai-for-seo' ), AI4SEO_PLUGIN_NAME ),
		/* translators: %s: Plugin name. */
		AI4SEO_SETTING_SHOW_OTHER_NOTIFICATIONS => sprintf( __( 'Show optional %s notifications', 'ai-for-seo' ), AI4SEO_PLUGIN_NAME ),
		AI4SEO_SETTING_SHOW_MENU_COUNTERS       => __( 'Show notification counters in menus', 'ai-for-seo' ),
	);
}

/** Return effective settings, including defaults that the settings writer omits. */
function ai4seo_get_notification_preferences(): array {
	$result = array();
	foreach ( AI4SEO_NOTIFICATION_SETTINGS as $key ) {
		$result[ $key ] = (bool) ai4seo_get_setting( $key );
	}
	return $result;
}

/** Bind durable work to the account without storing its credentials. */
function ai4seo_get_notification_preferences_account(): string {
	return wp_hash( wp_json_encode( ai4seo_robhub_api()->read_auth_data() ) );
}

/**
 * Preserve recorded rejection; absence of historical consent is not rejection.
 *
 * @param array $raw Persisted settings without defaults.
 * @param array $permission Account-bound historical choices.
 */
function ai4seo_get_notification_preferences_migration_values( array $raw, array $permission ): array {
	$rejected = ! empty( $permission['opted_out'] )
		|| ( array_key_exists( 'plugin_consented_at', $permission ) && empty( $permission['plugin_consented_at'] ) );
	$result   = array();
	foreach ( array( AI4SEO_SETTING_SHOW_ADMIN_OFFERS, AI4SEO_SETTING_SHOW_PLUGIN_OFFERS ) as $key ) {
		if ( ! array_key_exists( $key, $raw ) && ( $rejected || ( isset( $permission[ $key ] ) && ! $permission[ $key ] ) ) ) {
			$result[ $key ] = false;
		}
	}
	return $result;
}

/** Run the additive migration once, independently of the public release version. */
function ai4seo_initialize_notification_preferences(): bool {
	$state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES );
	if ( 1 === ( $state['version'] ?? null ) ) {
		return true;
	}
	$data = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVATION );
	if ( ! ai4seo_apply_required_setting_migration_values( ai4seo_get_notification_preferences_migration_values( ai4seo_read_settings(), $data['preferences'] ?? array() ) ) ) {
		return false;
	}
	return ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES,
		array(
			'version' => 1,
			'account' => ai4seo_get_notification_preferences_account(),
			'pending' => array(),
		)
	);
}

/**
 * Classify explicit offer identifiers and local operational problems.
 *
 * @param string $index Stable notification identifier.
 * @param array  $notification Validated notification data.
 */
function ai4seo_get_notification_category( string $index, array $notification = array() ): string {
	if ( 'discount' === $index || 0 === strpos( $index, 'activation-' ) || isset( $notification['activation_offer_id'] )
		|| in_array( $index, array( 'black-friday-sale-2025', 'new-year-2026', 'limited-time-offer', 'first-purchase-credits-bonus' ), true )
		|| ! empty( $notification['grab_deal_button'] ) || ! empty( $notification['claim_bonus_button'] ) ) {
		return 'offer';
	}
	return in_array( $index, array( 'robhub-account-error', 'payg-status-error', 'inefficient-cron-jobs' ), true ) ? 'required' : 'optional';
}

/**
 * Apply the same preference to rendering, unread counts and mark-as-read selection.
 *
 * @param string $index Stable notification identifier.
 * @param array  $notification Validated notification data.
 * @param string $surface Plugin or other admin pages.
 */
function ai4seo_should_show_notification( string $index, array $notification = array(), string $surface = 'plugin' ): bool {
	$category = ai4seo_get_notification_category( $index, $notification );
	if ( 'offer' === $category ) {
		// A recorded personal-offer dismissal also suppresses fallback discounts on either surface.
		$data = ai4seo_get_activation_data( true );
		if ( 'discount' === $index && ! empty( $data['dismissed_until'] ) && $data['dismissed_until'] > time() ) {
			return false;
		}
		return ai4seo_can_administer_plugin() && (bool) ai4seo_get_setting( 'admin' === $surface ? AI4SEO_SETTING_SHOW_ADMIN_OFFERS : AI4SEO_SETTING_SHOW_PLUGIN_OFFERS );
	}
	return 'required' === $category || (bool) ai4seo_get_setting( AI4SEO_SETTING_SHOW_OTHER_NOTIFICATIONS );
}

/** Render the always-available notification section in the Account form. */
function ai4seo_render_notification_preferences(): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		return;
	}
	$values = ai4seo_get_notification_preferences();
	echo '<hr class="ai4seo-form-item-divider"><div class="ai4seo-form-item ai4seo-notification-preferences">';
	echo '<span class="ai4seo-form-item-label">' . esc_html__( 'Notifications', 'ai-for-seo' ) . ':</span><div class="ai4seo-form-item-input-wrapper">';
	echo '<input type="hidden" name="ai4seo_notification_original" value="' . esc_attr( wp_json_encode( $values ) ) . '">';
	echo '<input type="hidden" name="ai4seo_notification_signature" value="' . esc_attr( ai4seo_get_activation_preferences_signature( $values ) ) . '">';
	foreach ( ai4seo_get_notification_preference_labels() as $key => $label ) {
		echo '<div class="ai4seo-notification-preference"><span class="ai4seo-label-with-tooltip">';
		echo '<label for="ai4seo-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		if ( AI4SEO_SETTING_SHOW_OTHER_NOTIFICATIONS === $key ) {
			ai4seo_echo_wp_kses(
				ai4seo_get_icon_with_tooltip_tag(
					__( 'Includes update news, rating requests, low-credit reminders and SEO Autopilot completion notices. Offers and discounts, menu counters and optional emails have separate settings. Important account, payment and background-processing problems remain visible. Notifications from WordPress and other plugins are not affected.', 'ai-for-seo' ),
					'',
					'circle-question',
					__( 'Optional notifications help', 'ai-for-seo' )
				)
			);
		}
		echo '</span><input type="checkbox" id="ai4seo-' . esc_attr( $key ) . '" name="' . esc_attr( ai4seo_get_prefixed_input_name( $key ) ) . '" class="ai4seo-single-checkbox ai4seo-lockable" ' . checked( $values[ $key ], true, false ) . '></div>';
	}
	$state = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES );
	if ( ! empty( $state['pending'] ) ) {
		$message = ! empty( $state['conflict'] )
			? __( 'Your display settings are saved locally. Personal offer preferences changed elsewhere. Review your choices below before retrying synchronization.', 'ai-for-seo' )
			: __( 'Your display settings are saved locally. Synchronization of your personal offer preferences is still pending.', 'ai-for-seo' );
		echo '<p class="ai4seo-form-item-description ai4seo-warning-message">' . esc_html( $message ) . '</p>';
		if ( ! empty( $state['conflict'] ) ) {
			echo '<label><input type="checkbox" name="ai4seo_notification_retry" class="ai4seo-single-checkbox ai4seo-lockable"> ' . esc_html__( 'I have reviewed my choices and want to save them again.', 'ai-for-seo' ) . '</label>';
		}
	}
	echo '</div></div>';
}

/**
 * Render a surface-specific permanent action even for image-only offers.
 *
 * @param string $surface Plugin or other admin pages.
 */
function ai4seo_get_offer_preference_button( string $surface ): string {
	if ( ! ai4seo_can_administer_plugin() ) {
		return '';
	}
	$key    = 'admin' === $surface ? AI4SEO_SETTING_SHOW_ADMIN_OFFERS : AI4SEO_SETTING_SHOW_PLUGIN_OFFERS;
	$values = ai4seo_get_notification_preferences();
	$label  = __( 'Don’t show offers here again', 'ai-for-seo' );
	return '<button type="button" class="ai4seo-button ai4seo-secondary-button ai4seo-disable-offers" data-offer-setting="' . esc_attr( $key ) . '" data-preferences="' . esc_attr( wp_json_encode( $values ) ) . '" data-signature="' . esc_attr( ai4seo_get_activation_preferences_signature( $values ) ) . '">' . esc_html( $label ) . '</button>';
}

/** Select one global offer consistently for assets and rendering. */
function ai4seo_get_admin_offer(): array {
	if ( ! ai4seo_can_administer_plugin() || ai4seo_is_plugin_page_active() || ! ai4seo_get_setting( AI4SEO_SETTING_SHOW_ADMIN_OFFERS )
		|| ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return array();
	}
	$offer = ai4seo_get_activation_offer();
	if ( $offer ) {
		return array( 'offer' => $offer );
	}
	// A dismissed personal offer suppresses immediate fallback marketing for the same active window.
	$data = ai4seo_get_activation_data( true );
	if ( ! empty( $data['dismissed_until'] ) && $data['dismissed_until'] > time() ) {
		return array();
	}
	$notifications = ai4seo_get_repaired_notifications();
	$notice        = $notifications['discount'] ?? array();
	if ( $notice && empty( $notice['dismissed'] ) && (int) ( $notice['expire_at'] ?? 0 ) > time()
		&& ai4seo_check_notification_conditions( 'discount', $notice ) ) {
		return array( 'notification' => $notice );
	}
	return array();
}

/** Show at most one global offer, with a personal quote taking precedence. */
function ai4seo_render_offer_admin_notice(): void {
	$selection = ai4seo_get_admin_offer();
	if ( isset( $selection['offer'] ) ) {
		ai4seo_render_activation_offer( $selection['offer'], true );
	} elseif ( isset( $selection['notification'] ) ) {
		ai4seo_echo_notice_from_notification( 'discount', $selection['notification'], 'admin' );
	}
}

/**
 * Validate account-bound snapshots before any save category can change state.
 *
 * The save-anything caller releases the semaphore after processing, including on errors.
 *
 * @param array $updates Sanitized updates, with unchanged notification fields removed.
 * @return WP_Error|null Error on failure, null when the remaining save categories may proceed.
 */
function ai4seo_prepare_notification_preference_save( array &$updates ) {
	unset( $GLOBALS['ai4seo_notification_preference_response'], $GLOBALS['ai4seo_notification_preference_save'] );
	$keys       = array_intersect( array_keys( $updates ), AI4SEO_NOTIFICATION_SETTINGS );
	$email_form = array_key_exists( 'activation_original', $updates ) && ! array_key_exists( 'activation_plugin', $updates );
	if ( ! $keys && ! $email_form && ! array_intersect( array_keys( $updates ), array_keys( AI4SEO_DEFAULT_SETTINGS ) ) ) {
		return null;
	}
	if ( ( ( $keys || $email_form ) && ! ai4seo_can_administer_plugin() ) || ! ai4seo_acquire_semaphore( 'notification-preferences' ) ) {
		return new WP_Error( 5092710, __( 'Preferences are being updated. Please try again.', 'ai-for-seo' ) );
	}
	ai4seo_reset_settings_request_cache_for_current_site();
	ai4seo_init_settings();
	if ( ! $keys && ! $email_form ) {
		// Other settings forms also reload inside the lock so an old cache cannot undo a footer choice.
		return null;
	}
	$original = isset( $updates['notification_original'] ) && is_string( $updates['notification_original'] ) ? json_decode( $updates['notification_original'], true ) : null;
	if ( isset( $updates['notification_original'] ) && ( ! is_array( $original ) || ! is_string( $updates['notification_signature'] ?? null )
		|| ! hash_equals( ai4seo_get_activation_preferences_signature( $original ), $updates['notification_signature'] ) ) ) {
		return new WP_Error( 5092705, __( 'Your account changed. Reload this page before saving offer preferences.', 'ai-for-seo' ) );
	}
	foreach ( $keys as $key ) {
		$value = ai4seo_normalize_boolean_setting_value( $key, $updates[ $key ] );
		if ( ! is_bool( $value ) ) {
			return new WP_Error( 5092702, __( 'Invalid notification preference.', 'ai-for-seo' ) );
		}
		if ( is_array( $original ) ) {
			if ( ! is_bool( $original[ $key ] ?? null ) ) {
				return new WP_Error( 5092702, __( 'Invalid notification preference.', 'ai-for-seo' ) );
			}
			// Settings forms submit only explicit edits, including repairs that equal the displayed fallback.
			if ( $original[ $key ] === $value && ! is_array( $updates['settings_edit'] ?? null ) ) {
				unset( $updates[ $key ] );
				continue;
			}
			if ( (bool) ai4seo_get_setting( $key ) !== $original[ $key ] && (bool) ai4seo_get_setting( $key ) !== $value ) {
				return new WP_Error( 5092711, __( 'Notification preferences changed in another window. Reload this page before saving.', 'ai-for-seo' ) );
			}
		}
		$updates[ $key ] = $value;
	}
	$input = array(
		'intent'     => is_array( $original ) ? 'save' : 'import',
		'reactivate' => false,
	);
	if ( $email_form ) {
		$email = ai4seo_validate_activation_preferences_save( $updates );
		if ( is_wp_error( $email ) ) {
			return $email;
		}
		$input = array_merge( $input, $email );
	}
	if ( true === ( $updates['notification_reactivate'] ?? false ) && is_array( $original ) ) {
		$input['reactivate'] = true;
	}
	if ( ! ai4seo_is_account_license_entry_placeholder( $updates ) ) {
		foreach ( array( 'api_username', 'api_password' ) as $key ) {
			if ( isset( $updates[ $key ] ) && ai4seo_robhub_api()->read_environmental_variable( $key ) !== $updates[ $key ]
				&& ( array_intersect( array_keys( $updates ), AI4SEO_NOTIFICATION_SETTINGS ) || isset( $input['email'] ) || $input['reactivate'] ) ) {
				return new WP_Error( 5092713, __( 'Save your account connection first, then reload before changing notification or email preferences.', 'ai-for-seo' ) );
			}
		}
	}
	$GLOBALS['ai4seo_notification_preference_retry']   = true === ( $updates['notification_retry'] ?? false ) && is_array( $original );
	$GLOBALS['ai4seo_notification_preference_save']    = $input;
	$GLOBALS['ai4seo_notification_preference_account'] = ai4seo_get_notification_preferences_account();
	return null;
}

/**
 * Queue a confirmed local save before later, independent save categories can fail.
 *
 * @param array $updates Sanitized fields whose settings have been persisted.
 */
function ai4seo_finish_notification_preference_save( array $updates ): array {
	if ( isset( $GLOBALS['ai4seo_notification_preference_response'] ) ) {
		return $GLOBALS['ai4seo_notification_preference_response'];
	}
	if ( ! isset( $GLOBALS['ai4seo_notification_preference_save'] ) ) {
		return array();
	}
	$input = $GLOBALS['ai4seo_notification_preference_save'];
	unset( $GLOBALS['ai4seo_notification_preference_save'] );
	if ( ( $GLOBALS['ai4seo_notification_preference_account'] ?? '' ) !== ai4seo_get_notification_preferences_account() ) {
		// Account changes in another save category cannot transfer an email choice to the new license.
		return array( 'notification_preferences' => array( 'sync_pending' => true ) );
	}
	$offer_changed = array_key_exists( AI4SEO_SETTING_SHOW_ADMIN_OFFERS, $updates ) || array_key_exists( AI4SEO_SETTING_SHOW_PLUGIN_OFFERS, $updates );
	$result        = ( $offer_changed || isset( $input['email'] ) || $input['reactivate'] || ! empty( $GLOBALS['ai4seo_notification_preference_retry'] ) ) ? ai4seo_sync_notification_preferences( $input ) : true;
	ai4seo_get_displayable_notifications();
	$values = ai4seo_get_notification_preferences();
	$GLOBALS['ai4seo_notification_preference_response'] = array(
		'notification_preferences' => array(
			'values'       => $values,
			'original'     => wp_json_encode( $values ),
			'signature'    => ai4seo_get_activation_preferences_signature( $values ),
			'sync_pending' => ! $result,
		),
	);
	return $GLOBALS['ai4seo_notification_preference_response'];
}

/**
 * Persist retries before transport; unchanged retries reuse the identical request ID and body.
 *
 * @param array $changes Explicit, validated choices; empty for an account-sync retry.
 */
function ai4seo_sync_notification_preferences( array $changes = array() ): bool {
	ai4seo_reset_settings_request_cache_for_current_site();
	ai4seo_init_settings();
	$state   = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES );
	$account = ai4seo_get_notification_preferences_account();
	$data    = ai4seo_get_activation_data( true );
	if ( ( $state['account'] ?? '' ) !== $account ) {
		$state = array(
			'version' => 1,
			'account' => $account,
			'pending' => array(),
		);
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
			return false;
		}
	}
	$preferences = $data['preferences'] ?? array();
	$values      = array_intersect_key( ai4seo_get_notification_preferences(), array_flip( array( AI4SEO_SETTING_SHOW_ADMIN_OFFERS, AI4SEO_SETTING_SHOW_PLUGIN_OFFERS ) ) );
	if ( ! $changes && empty( $state['pending'] ) && empty( $state['next'] ) && 'v2' === ( $data['capability'] ?? '' ) ) {
		// Sync and migration may retain or narrow a choice, never undo a recorded remote rejection.
		foreach ( $values as $key => $value ) {
			if ( $value && isset( $preferences[ $key ] ) && ! $preferences[ $key ] ) {
				if ( ! ai4seo_update_setting( $key, false ) ) {
					return false;
				}
				$values[ $key ] = false;
			}
		}
	}
	if ( $changes && ! empty( $state['conflict'] ) ) {
		// A new deliberate decision replaces a rejected stale decision, never an uncertain accepted one.
		$state['pending'] = array();
		unset( $state['next'], $state['conflict'] );
	}
	if ( ! empty( $state['pending'] ) && $changes ) {
		// Coalesce later choices separately; an uncertain in-flight request retains its original identity.
		$state['next'] = array_merge( $state['next'] ?? array(), $changes );
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
			return false;
		}
		$changes = array();
	}
	if ( empty( $state['pending'] ) && ! empty( $state['next'] ) ) {
		$changes = array_merge( $state['next'], $changes );
		unset( $state['next'] );
	}
	if ( $changes || ( empty( $state['pending'] ) && 'v2' === ( $data['capability'] ?? '' )
		&& ( 2 !== ( $preferences['preferences_version'] ?? 1 ) || (bool) ( $preferences['show_admin_offers'] ?? false ) !== $values['show_admin_offers']
			|| (bool) ( $preferences['show_plugin_offers'] ?? false ) !== $values['show_plugin_offers'] ) ) ) {
		$state['pending']     = array_merge(
			array(
				'action'            => 'save-v2',
				'intent'            => 'sync',
				'reactivate'        => false,
				'request_id'        => str_replace( '-', '', wp_generate_uuid4() ),
				'expected_revision' => (int) ( $preferences['preferences_revision'] ?? 0 ),
				'actor'             => (string) get_current_user_id(),
			),
			$values,
			$changes
		);
		$state['retry_after'] = 0;
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
			return false;
		}
	}
	if ( empty( $state['pending'] ) ) {
		return true;
	}
	if ( 'v2' !== ( $data['capability'] ?? '' ) ) {
		return false;
	}
	if ( ( $data['last_request_id'] ?? '' ) === ( $state['pending']['request_id'] ?? '' ) ) {
		// A later account sync can acknowledge a response lost after the server committed.
		ai4seo_advance_queued_notification_preference_revision( $state, $data );
		$state['pending']     = array();
		$state['retry_after'] = 0;
		if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
			return false;
		}
		return empty( $state['next'] ) ? true : ai4seo_sync_notification_preferences();
	}
	if ( ! $changes && ( $state['retry_after'] ?? 0 ) > time() ) {
		return false;
	}
	if ( (int) ( $preferences['preferences_revision'] ?? 0 ) !== $state['pending']['expected_revision'] ) {
		if ( empty( $state['pending']['reactivate'] ) && empty( $state['pending']['email'] ) ) {
			// A restrictive retry can only narrow current display permissions or withdraw email.
			foreach ( array( 'show_admin_offers', 'show_plugin_offers' ) as $key ) {
				$state['pending'][ $key ] = $state['pending'][ $key ] && ! empty( $preferences[ $key ] );
			}
			$state['pending']['request_id']        = str_replace( '-', '', wp_generate_uuid4() );
			$state['pending']['expected_revision'] = (int) ( $preferences['preferences_revision'] ?? 0 );
			if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
				return false;
			}
		} else {
			// Never overwrite an intervening unsubscribe or confirmation with an older browser decision.
			$state['conflict'] = true;
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state );
			return false;
		}
	}
	$response = ai4seo_robhub_api()->call( 'client/activation-preferences', array( 'preferences' => wp_json_encode( $state['pending'] ) ) );
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) || 'v2' !== ( $response['data']['capability'] ?? '' ) ) {
		$state['retry_after'] = time() + 300;
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state );
		return false;
	}
	ai4seo_store_activation_data( $response['data'] );
	ai4seo_advance_queued_notification_preference_revision( $state, $response['data'] );
	$state['pending']     = array();
	$state['retry_after'] = 0;
	unset( $state['conflict'] );
	if ( ! ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES, $state ) ) {
		return false;
	}
	return empty( $state['next'] ) ? true : ai4seo_sync_notification_preferences();
}

/**
 * Carry a later choice past only its own acknowledged predecessor.
 *
 * An intervening remote change must still require explicit review.
 *
 * @param array $state Account-bound durable queue, updated before its next write.
 * @param array $data Validated acknowledgement or synchronized account snapshot.
 */
function ai4seo_advance_queued_notification_preference_revision( array &$state, array $data ): void {
	$revision = $state['pending']['expected_revision'] ?? null;
	if ( is_int( $revision ) && ( $state['next']['expected_revision'] ?? null ) === $revision
		&& ! empty( $state['pending']['request_id'] )
		&& ( $data['last_request_id'] ?? null ) === $state['pending']['request_id']
		&& ( $data['preferences']['preferences_revision'] ?? null ) === $revision + 1 ) {
		$state['next']['expected_revision'] = $revision + 1;
	}
}
