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
 * @return WP_Error|null Error on failure, null on success or no-op.
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

	// Mutate the loaded settings cache first so the existing bulk database writer persists one coherent snapshot.
	global $ai4seo_settings;

	// Normalize active post type selection (UI shows active types, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_TYPES ] ) ) {
		$ai4seo_submitted_active_post_types = $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_TYPES ];

		// Normalize a single submitted selection to the array shape used by multi-select requests.
		if ( ! is_array( $ai4seo_submitted_active_post_types ) ) {
			$ai4seo_submitted_active_post_types = $ai4seo_submitted_active_post_types ? array( $ai4seo_submitted_active_post_types ) : array();
		}

		// Persist the complement because the setting stores exclusions while the UI submits enabled post types.
		$ai4seo_available_post_types = ai4seo_get_supported_post_types( false );
		$ai4seo_disabled_post_types  = array_values( array_diff( $ai4seo_available_post_types, $ai4seo_submitted_active_post_types ) );

		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_TYPES ] = $ai4seo_disabled_post_types;
	}

	// Normalize active post author selection (UI shows active authors, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_AUTHORS ] ) ) {
		$ai4seo_submitted_active_post_author_ids = $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_AUTHORS ];

		// Normalize a single submitted author to the array shape used by checkbox groups.
		if ( ! is_array( $ai4seo_submitted_active_post_author_ids ) ) {
			$ai4seo_submitted_active_post_author_ids = $ai4seo_submitted_active_post_author_ids ? array( $ai4seo_submitted_active_post_author_ids ) : array();
		}

		// Canonicalize IDs before comparing the submitted active set with currently available authors.
		$ai4seo_available_post_authors           = ai4seo_get_available_post_authors();
		$ai4seo_available_post_author_ids        = array_map( 'intval', array_keys( $ai4seo_available_post_authors ) );
		$ai4seo_submitted_active_post_author_ids = array_map( 'intval', $ai4seo_submitted_active_post_author_ids );
		$ai4seo_submitted_active_post_author_ids = array_values( array_unique( $ai4seo_submitted_active_post_author_ids ) );

		// Remove nonpositive IDs because they cannot identify an available WordPress author.
		foreach ( $ai4seo_submitted_active_post_author_ids as $ai4seo_this_post_author_index => $ai4seo_this_post_author_id ) {
			// Remove by index here and reindex once after the loop to avoid mutating iteration order.
			if ( $ai4seo_this_post_author_id <= 0 ) {
				unset( $ai4seo_submitted_active_post_author_ids[ $ai4seo_this_post_author_index ] );
			}
		}

		$ai4seo_submitted_active_post_author_ids = array_values( $ai4seo_submitted_active_post_author_ids );
		$ai4seo_disabled_post_author_ids         = array_values( array_diff( $ai4seo_available_post_author_ids, $ai4seo_submitted_active_post_author_ids ) );

		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_POST_AUTHORS ] = $ai4seo_disabled_post_author_ids;
	}

	// Normalize active metadata WPML language selection (UI shows active languages, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES ] ) ) {
		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES ] = ai4seo_get_disabled_wpml_language_codes_from_active_selection(
			$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES ]
		);
	}

	// Normalize active taxonomy term selection (UI shows active terms, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ] ) ) {
		$ai4seo_submitted_active_taxonomy_terms = $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ];

		// A malformed top-level value represents no active taxonomy selections, matching the prior form parser.
		if ( ! is_array( $ai4seo_submitted_active_taxonomy_terms ) ) {
			$ai4seo_submitted_active_taxonomy_terms = array();
		}

		$ai4seo_supported_taxonomy_terms = ai4seo_get_supported_taxonomy_terms();
		$ai4seo_disabled_taxonomy_terms  = array();

		// Calculate exclusions independently per taxonomy so term IDs from different taxonomies never mix.
		foreach ( $ai4seo_supported_taxonomy_terms as $ai4seo_this_taxonomy_name => $ai4seo_this_supported_taxonomy ) {
			$ai4seo_supported_term_ids        = array_map( 'intval', array_keys( $ai4seo_this_supported_taxonomy['terms'] ?? array() ) );
			$ai4seo_submitted_active_term_ids = $ai4seo_submitted_active_taxonomy_terms[ $ai4seo_this_taxonomy_name ] ?? array();

			// Normalize a single submitted term to the array shape used by taxonomy checkbox groups.
			if ( ! is_array( $ai4seo_submitted_active_term_ids ) ) {
				$ai4seo_submitted_active_term_ids = $ai4seo_submitted_active_term_ids ? array( $ai4seo_submitted_active_term_ids ) : array();
			}

			$ai4seo_submitted_active_term_ids = array_map( 'intval', $ai4seo_submitted_active_term_ids );
			$ai4seo_submitted_active_term_ids = array_values( array_unique( $ai4seo_submitted_active_term_ids ) );

			// Remove nonpositive IDs before deriving disabled terms from the supported set.
			foreach ( $ai4seo_submitted_active_term_ids as $ai4seo_this_term_index => $ai4seo_this_term_id ) {
				// Remove by index here and reindex once after the loop to avoid mutating iteration order.
				if ( $ai4seo_this_term_id <= 0 ) {
					unset( $ai4seo_submitted_active_term_ids[ $ai4seo_this_term_index ] );
				}
			}

			$ai4seo_submitted_active_term_ids = array_values( $ai4seo_submitted_active_term_ids );
			$ai4seo_this_disabled_term_ids    = array_values( array_diff( $ai4seo_supported_term_ids, $ai4seo_submitted_active_term_ids ) );

			// Omit taxonomies without exclusions so the persisted setting remains compact and compatible with readers.
			if ( $ai4seo_this_disabled_term_ids ) {
				$ai4seo_disabled_taxonomy_terms[ $ai4seo_this_taxonomy_name ] = $ai4seo_this_disabled_term_ids;
			}
		}

		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS ] = $ai4seo_disabled_taxonomy_terms;
	}

	// Normalize active attachment author selection (UI shows active authors, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS ] ) ) {
		$ai4seo_submitted_active_attachment_post_author_ids = $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS ];

		// Normalize a single submitted author to the array shape used by media-author checkbox groups.
		if ( ! is_array( $ai4seo_submitted_active_attachment_post_author_ids ) ) {
			$ai4seo_submitted_active_attachment_post_author_ids = $ai4seo_submitted_active_attachment_post_author_ids ? array( $ai4seo_submitted_active_attachment_post_author_ids ) : array();
		}

		// Canonicalize IDs before comparing the submitted active set with available attachment authors.
		$ai4seo_available_attachment_post_authors           = ai4seo_get_available_attachment_post_authors();
		$ai4seo_available_attachment_post_author_ids        = array_map( 'intval', array_keys( $ai4seo_available_attachment_post_authors ) );
		$ai4seo_submitted_active_attachment_post_author_ids = array_map( 'intval', $ai4seo_submitted_active_attachment_post_author_ids );
		$ai4seo_submitted_active_attachment_post_author_ids = array_values( array_unique( $ai4seo_submitted_active_attachment_post_author_ids ) );

		// Remove nonpositive IDs because they cannot identify an available WordPress media author.
		foreach ( $ai4seo_submitted_active_attachment_post_author_ids as $ai4seo_this_post_author_index => $ai4seo_this_post_author_id ) {
			// Remove by index here and reindex once after the loop to avoid mutating iteration order.
			if ( $ai4seo_this_post_author_id <= 0 ) {
				unset( $ai4seo_submitted_active_attachment_post_author_ids[ $ai4seo_this_post_author_index ] );
			}
		}

		$ai4seo_submitted_active_attachment_post_author_ids = array_values( $ai4seo_submitted_active_attachment_post_author_ids );
		$ai4seo_disabled_attachment_post_author_ids         = array_values( array_diff( $ai4seo_available_attachment_post_author_ids, $ai4seo_submitted_active_attachment_post_author_ids ) );

		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS ] = $ai4seo_disabled_attachment_post_author_ids;
	}

	// Normalize active attachment attributes WPML language selection (UI shows active languages, setting stores disabled ones).
	if ( isset( $upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES ] ) ) {
		$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES ] = ai4seo_get_disabled_wpml_language_codes_from_active_selection(
			$upcoming_save_anything_updates[ AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES ]
		);
	}

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

	// Retain old/new pairs because several post-save workflows depend on the direction of a setting transition.
	$ai4seo_recent_setting_changes = array();

	// Use the defaults registry as the allowlist for setting names accepted by save-anything.
	foreach ( array_keys( AI4SEO_DEFAULT_SETTINGS ) as $ai4seo_this_setting_name ) {
		// The shared save endpoint can contain unrelated categories, so process only present settings.
		if ( ! isset( $upcoming_save_anything_updates[ $ai4seo_this_setting_name ] ) ) {
			continue;
		}

		$ai4seo_this_old_setting_value = $ai4seo_settings[ $ai4seo_this_setting_name ];
		$ai4seo_this_new_setting_value = $upcoming_save_anything_updates[ $ai4seo_this_setting_name ];

		// Apply the shared custom-instruction cleanup and plan limit before equality and validation checks.
		$ai4seo_this_new_setting_value = ai4seo_normalize_custom_instructions_setting_value( $ai4seo_this_setting_name, $ai4seo_this_new_setting_value );

		// Normalize supported checkbox and compatibility representations before comparison and validation.
		$ai4seo_this_new_setting_value = ai4seo_normalize_boolean_setting_value(
			$ai4seo_this_setting_name,
			$ai4seo_this_new_setting_value
		);

		// Retain loose comparison because saved option scalars can differ only by PHP representation.
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Preserve existing save-anything equality semantics across scalar option values.
		if ( $ai4seo_this_new_setting_value == $ai4seo_this_old_setting_value ) {
			continue;
		}

		// Reject the entire category before database persistence when any submitted setting is invalid.
		if ( ! ai4seo_validate_setting_value( $ai4seo_this_setting_name, $ai4seo_this_new_setting_value ) ) {
			return new WP_Error(
				261219225,
				sprintf(
					/* translators: %s: Setting name. */
					esc_html__( 'Invalid setting value for %s', 'ai-for-seo' ),
					$ai4seo_this_setting_name
				)
			);
		}

		// Update the shared cache and retain old/new values for post-save side effects after the bulk write.
		$ai4seo_settings[ $ai4seo_this_setting_name ]               = $ai4seo_this_new_setting_value;
		$ai4seo_recent_setting_changes[ $ai4seo_this_setting_name ] = array( $ai4seo_this_old_setting_value, $ai4seo_this_new_setting_value );
	}

	// Persist the shared settings snapshot once after every submitted value has passed validation.
	if ( $ai4seo_recent_setting_changes && ! ai4seo_push_local_setting_changes_to_database() ) {
		// Restore the request-local cache so later code cannot observe values that the database rejected.
		foreach ( $ai4seo_recent_setting_changes as $ai4seo_setting_name => $ai4seo_setting_values ) {
			$ai4seo_settings[ $ai4seo_setting_name ] = $ai4seo_setting_values[0];
		}

		ai4seo_store_settings_request_cache_for_current_site();

		return new WP_Error(
			271219225,
			esc_html__( 'Failed to update settings. Please try again.', 'ai-for-seo' )
		);
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

			return new WP_Error(
				718217659,
				esc_html__( 'Could not update the SEO Autopilot date-filter reference. Some settings may already have been saved. Please try again.', 'ai-for-seo' )
			);
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
	$ai4seo_should_refresh_posts_table_analysis = $ai4seo_bulk_generation_date_filter_state_was_repaired;

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

			return new WP_Error(
				88120826,
				esc_html__( 'The settings were saved, but their generation-status statistics could not be reconciled. A background repair was requested. Please refresh the page and try again.', 'ai-for-seo' )
			);
		}
	}

	// Track whether this request already scheduled the bulk-generation cron to avoid a duplicate injection below.
	$ai4seo_bulk_generation_cron_was_injected            = false;
	$ai4seo_persisted_enabled_bulk_generation_post_types = array();

	// A submitted enabled-type setting must repair stale queue state even when its value did not change.
	if ( array_key_exists( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $upcoming_save_anything_updates )
		&& ! ai4seo_reconcile_disabled_bulk_generation_queue_entries(
			$ai4seo_persisted_enabled_bulk_generation_post_types
		) ) {
		ai4seo_debug_message( 90120826, 'Could not safely reconcile disabled bulk-generation queue entries after saving settings.', true );

		return new WP_Error(
			88120826,
			esc_html__( 'The settings were saved, but their generation-status statistics could not be reconciled. A background repair was requested. Please refresh the page and try again.', 'ai-for-seo' )
		);
	}

	// Reconcile queue and scheduling state when the enabled SEO Autopilot post types change.
	if ( isset( $ai4seo_recent_setting_changes[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ] ) ) {
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

		// Keep the established AJAX error when RobHub does not accept the synchronized billing state.
		if ( false === $ai4seo_pay_as_you_go_settings_sent ) {
			return new WP_Error(
				401217325,
				esc_html__( 'Could not send pay-as-you-go settings to RobHub', 'ai-for-seo' )
			);
		}
	}

	return null;
}
