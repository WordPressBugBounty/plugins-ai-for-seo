<?php
/**
 * Processes setting updates from save-anything requests.
 *
 * @since 2.0.0
 *
 * @package AI_For_SEO
 */

// Prevent direct execution because this processor depends on the loaded WordPress and plugin runtime.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes setting updates from sanitized save-anything data.
 *
 * @param array $upcoming_save_anything_updates Sanitized updates shared by the ordered save-anything processors.
 * @return array|WP_Error|null Confirmed save with notices, an error, or a non-settings no-op.
 */
function ai4seo_process_save_anything_settings( array &$upcoming_save_anything_updates ) {
	// Leave content-only payloads untouched so their owning processors can handle them independently.
	if ( ! array_intersect( array_keys( $upcoming_save_anything_updates ), array_keys( AI4SEO_DEFAULT_SETTINGS ) ) ) {
		return null;
	}

	// Incognito recovery is the sole settings write available before valid administrative ownership exists.
	$ai4seo_is_incognito_recovery_save = ai4seo_can_recover_incognito_mode()
		&& ai4seo_is_valid_incognito_recovery_save_payload( $upcoming_save_anything_updates );

	// Keep direct processor callers behind the same boundary as the save-anything preflight.
	if ( ! ai4seo_can_administer_plugin() && ! $ai4seo_is_incognito_recovery_save ) {
		return new WP_Error(
			11420725,
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' )
		);
	}

	global $ai4seo_settings;
	$state = ai4seo_inspect_settings();
	if ( ! $state['readable'] ) {
		return new WP_Error( 471013126, __( 'The stored settings cannot be read safely. Nothing was saved; the original data was retained.', 'ai-for-seo' ) );
	}
	$edit = $upcoming_save_anything_updates['settings_edit'] ?? null;
	if ( null !== $edit ) {
		if ( ! is_array( $edit ) ) {
			return new WP_Error( 461013126, __( 'The settings form could not be verified. Keep your changes and reload it.', 'ai-for-seo' ) );
		}
		$conflict = ai4seo_validate_settings_edit( $upcoming_save_anything_updates, $edit, $state['raw'] );
		if ( is_wp_error( $conflict ) ) {
			return $conflict;
		}
	}
	$selection_changes = ai4seo_prepare_setting_selection_changes( $upcoming_save_anything_updates, $state['raw'], is_array( $edit['domains'] ?? null ) ? $edit['domains'] : array() );
	if ( is_wp_error( $selection_changes ) ) {
		return $selection_changes;
	}
	$upcoming_save_anything_updates = $selection_changes;

	// Ownership is derived exclusively from the authorized transition, never from a submitted owner field.
	unset( $upcoming_save_anything_updates[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] );

	// Persist an incognito transition or explicit recovery and its owner in the same settings snapshot.
	if ( array_key_exists( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, $upcoming_save_anything_updates ) ) {
		$ai4seo_current_incognito_mode_value   = ai4seo_normalize_boolean_setting_value(
			AI4SEO_SETTING_ENABLE_INCOGNITO_MODE,
			$ai4seo_settings[ AI4SEO_SETTING_ENABLE_INCOGNITO_MODE ]
		);
		$ai4seo_submitted_incognito_mode_value = ai4seo_normalize_boolean_setting_value(
			AI4SEO_SETTING_ENABLE_INCOGNITO_MODE,
			$upcoming_save_anything_updates[ AI4SEO_SETTING_ENABLE_INCOGNITO_MODE ]
		);

		// Invalid submitted values remain untouched so the normal validation path below returns its established error.
		if ( is_bool( $ai4seo_current_incognito_mode_value ) && is_bool( $ai4seo_submitted_incognito_mode_value )
			&& ( $ai4seo_current_incognito_mode_value !== $ai4seo_submitted_incognito_mode_value || $ai4seo_is_incognito_recovery_save ) ) {
			if ( $ai4seo_submitted_incognito_mode_value ) {
				$ai4seo_current_user_id = get_current_user_id();

				if ( $ai4seo_current_user_id <= 0 ) {
					return new WP_Error(
						281219225,
						esc_html__( 'Could not determine the Incognito Mode owner. Please try again.', 'ai-for-seo' )
					);
				}

				$upcoming_save_anything_updates[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] = $ai4seo_current_user_id;
			} else {
				// Disabling removes the access restriction and clears ownership in the same write.
				$upcoming_save_anything_updates[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] = 0;
			}
		}
	}

	$ai4seo_recent_setting_changes = array();
	$setting_changes               = array();
	foreach ( array_intersect_key( $upcoming_save_anything_updates, AI4SEO_DEFAULT_SETTINGS ) as $name => $value ) {
		try {
			$value = ai4seo_normalize_setting_input( $name, $value );
			$valid = ai4seo_validate_setting_value( $name, $value );
		} catch ( Throwable $exception ) {
			$valid = false;
		}
		if ( ! $valid ) {
			return new WP_Error(
				261219225,
				sprintf(
					/* translators: %s: Setting name. */
					__( 'Invalid setting value for %s', 'ai-for-seo' ),
					$name
				)
			);
		}
		$old_value                = $state['settings'][ $name ];
		$setting_changes[ $name ] = $value;
		if ( isset( $state['issues'][ $name ] ) || ! ai4seo_are_persisted_state_values_equivalent( $old_value, $value ) ) {
			$ai4seo_recent_setting_changes[ $name ] = array( $old_value, $value );
		}
	}

	// Reset history before committing a new transport preference so a failed reset remains retryable.
	$ai4seo_recovery_history_is_ready = ! isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_IMAGE_UPLOAD_METHOD ] )
		|| ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_BASE64_RECOVERY_STREAK, 0 );

	if ( ! $ai4seo_recovery_history_is_ready ) {
		ai4seo_debug_message( 709071005, 'Could not clear the attachment recovery streak before saving image upload settings.', true );

		return new WP_Error( 271219225, __( 'The image upload preference could not be changed safely. Your settings were not saved.', 'ai-for-seo' ) );
	}
	$result = ai4seo_patch_settings( $setting_changes, $state['raw'] );
	if ( 'saved' !== $result['status'] ) {
		return new WP_Error(
			271219225,
			'conflict' === $result['status']
				? __( 'These settings changed while saving. Nothing was saved. Keep your changes and reload the form.', 'ai-for-seo' )
				: __( 'Failed to update settings. Your changes were not saved. Please try again.', 'ai-for-seo' )
		);
	}
	$saved_fields = array_keys( $setting_changes );
	$warnings     = array();

	// Persist notification synchronization before later post-save work can fail independently.
	if ( isset( $GLOBALS['ai4seo_notification_preference_save'] ) ) {
		ai4seo_finish_notification_preference_save( $upcoming_save_anything_updates );
	}

	// Reconcile the date boundary before queue side effects run, including unchanged imported/resaved filters.
	$ai4seo_bulk_generation_date_filter_state_was_repaired = false;

	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ] ) ) {
		// Only transitions from the inactive mode establish a deliberately fresh boundary.
		$ai4seo_should_start_bulk_generation_date_filter_with_fresh_reference = isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ] )
			&& ! ai4seo_is_bulk_generation_date_filter_active( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ][0] )
			&& ai4seo_is_bulk_generation_date_filter_active( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ][1] );

		// Capture validity so repaired unchanged/imported state can trigger one analysis refresh below.
		$ai4seo_bulk_generation_date_filter_state_before_reconciliation = ai4seo_get_current_bulk_generation_date_filter_state();

		// A zero override requests a fresh boundary while the no-argument path preserves valid state.
		$ai4seo_bulk_generation_date_filter_state_was_reconciled = $ai4seo_should_start_bulk_generation_date_filter_with_fresh_reference
			? ai4seo_reconcile_bulk_generation_date_filter_reference_timestamp( 0 )
			: ai4seo_reconcile_bulk_generation_date_filter_reference_timestamp();

		if ( ! $ai4seo_bulk_generation_date_filter_state_was_reconciled ) {
			ai4seo_debug_message( 718217659, 'Could not reconcile the SEO Autopilot date-filter reference timestamp after saving settings.', true );

			$warnings[] = __( 'The Autopilot date reference could not be updated. Automatic selection remains restricted until the reference is valid.', 'ai-for-seo' );
		} elseif ( empty( $ai4seo_bulk_generation_date_filter_state_before_reconciliation['is_valid'] ) || $ai4seo_should_start_bulk_generation_date_filter_with_fresh_reference ) {
			// Confirm that the persisted replacement is valid before treating analysis as stale.
			$ai4seo_bulk_generation_date_filter_state_after_reconciliation = ai4seo_get_current_bulk_generation_date_filter_state();
			$ai4seo_bulk_generation_date_filter_state_was_repaired         = ! empty( $ai4seo_bulk_generation_date_filter_state_after_reconciliation['is_valid'] );
		}
	}

	// Keep analysis-sensitive settings declarative so all status-affecting changes share one refresh path.
	$ai4seo_analysis_trigger_settings = array(
		AI4SEO_SETTING_ACTIVE_META_TAGS,
		AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES,
		AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES,
		AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES,
		AI4SEO_SETTING_DISABLED_POST_TYPES,
		AI4SEO_SETTING_DISABLED_POST_AUTHORS,
		AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS,
		AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM,
		AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS,
		AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES,
		AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES,
		AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA,
		AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES,
		AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER,
	);

	// Refresh posts-table analysis once when a setting change or repaired date boundary affects derived status.
	$ai4seo_should_refresh_posts_table_analysis = $ai4seo_bulk_generation_date_filter_state_was_repaired || isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ] );

	foreach ( $ai4seo_analysis_trigger_settings as $ai4seo_this_setting_key ) {
		// The recent-change map prevents refreshes for submitted values that were equal to stored state.
		if ( isset( $ai4seo_recent_setting_changes[ $ai4seo_this_setting_key ] ) ) {
			$ai4seo_should_refresh_posts_table_analysis = true;
			break;
		}
	}

	if ( $ai4seo_should_refresh_posts_table_analysis ) {
		$ai4seo_posts_table_analysis_was_refreshed = ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();

		if ( ! $ai4seo_posts_table_analysis_was_refreshed ) {
			$ai4seo_rebuild_was_scheduled = ai4seo_schedule_generation_status_summary_rebuild();

			if ( ! $ai4seo_rebuild_was_scheduled ) {
				ai4seo_debug_message( 88120826, 'Could not durably schedule generation-status reconciliation after saving analysis-sensitive settings.', true );
			}

			$warnings[] = $ai4seo_rebuild_was_scheduled
				? __( 'The statistics could not be updated yet. A background refresh was requested.', 'ai-for-seo' )
				: __( 'The statistics could not be updated and a background refresh could not be scheduled. Please retry the analysis from Help.', 'ai-for-seo' );
		}
	}

	// Track whether this request already scheduled the bulk-generation cron to avoid a duplicate injection below.
	$ai4seo_bulk_generation_cron_was_injected            = false;
	$ai4seo_persisted_enabled_bulk_generation_post_types = array();

	// A submitted enabled-type setting must repair stale queue state even when its value did not change.
	if ( ( array_key_exists( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $upcoming_save_anything_updates ) || array_key_exists( AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES, $upcoming_save_anything_updates ) )
		&& ! ai4seo_reconcile_disabled_bulk_generation_queue_entries(
			$ai4seo_persisted_enabled_bulk_generation_post_types
		) ) {
		ai4seo_debug_message( 90120826, 'Could not safely reconcile disabled bulk-generation queue entries after saving settings.', true );

		$warnings[] = __( 'The Autopilot queue could not be reconciled. Existing jobs were retained; automatic selection may remain restricted.', 'ai-for-seo' );
	}

	// Reconcile queue and scheduling state when the enabled SEO Autopilot post types change.
	if ( isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ] ) || isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ] ) ) {
		// Enabling any type should fill its queue, schedule processing, and record the current setup time.
		if ( $ai4seo_persisted_enabled_bulk_generation_post_types ) {
			ai4seo_try_excavate_bulk_generation_entries_for_enabled_post_types();

			ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			$ai4seo_bulk_generation_cron_was_injected = true;

			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME, time() );
		}
	}

	// Re-enabling automatic queueing while SEO Autopilot is active should immediately resume excavation and processing.
	if ( isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES ] )
		&& true === $ai4seo_recent_setting_changes[ AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES ][1]
		&& ai4seo_is_any_bulk_generation_enabled() ) {
		ai4seo_try_excavate_bulk_generation_entries_for_enabled_post_types();

		// Schedule processing only when the enabled-types branch did not already inject the same cron call.
		if ( ! $ai4seo_bulk_generation_cron_was_injected ) {
			ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
		}
	}

	// Synchronize enabled pay-as-you-go state with RobHub after the local setting has been persisted.
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_PAYG_ENABLED ] ) && $upcoming_save_anything_updates[ AI4SEO_SETTING_PAYG_ENABLED ] ) {
		$ai4seo_pay_as_you_go_settings_sent = ai4seo_send_pay_as_you_go_settings();

		// Local persistence succeeded even when the remote billing state remains unconfirmed.
		if ( false === $ai4seo_pay_as_you_go_settings_sent ) {
			$warnings[] = __( 'The settings were saved locally, but pay-as-you-go synchronization is still pending. The remote billing settings are not confirmed.', 'ai-for-seo' );
		}
	}

	return ai4seo_settings_save_response( $saved_fields, $warnings, $result['tokens'] ?? array() );
}
