<?php
/**
 * Current RobHub account state, entitlements, credits, billing, and synchronization.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region ACCOUNT STATE AND ENTITLEMENTS ====================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Return the normalized plan key for the current account.
 *
 * @return string Normalized plan key.
 */
function ai4seo_get_current_user_plan(): string {
	// Cache the normalized plan because entitlement checks fan out across settings and UI rendering.
	global $ai4seo_current_user_plan;

	// Reuse the request-level value once the synchronized subscription has been resolved.
	if ( isset( $ai4seo_current_user_plan ) ) {
		return $ai4seo_current_user_plan;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 508736227, 'Prevented loop', true );
		return 'free';
	}

	// The server-side subscription sync is authoritative; inactive accounts are expected to be stored as free.
	$robhub_api           = ai4seo_robhub_api();
	$current_subscription = $robhub_api->read_environmental_variable( $robhub_api::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );

	if ( ! is_array( $current_subscription ) ) {
		$ai4seo_current_user_plan = 'free';
		return $ai4seo_current_user_plan;
	}

	// Unknown local plan values should not unlock any paid-tier behavior.
	$current_plan             = ai4seo_normalize_plan_identifier( $current_subscription['plan'] ?? 'free' );
	$ai4seo_current_user_plan = $current_plan ?: 'free';

	return $ai4seo_current_user_plan;
}

// =========================================================================================== \\

/**
 * Determine whether the current account has at least the required plan level.
 *
 * Accepts plan identifiers (free, s, m, l) or their textual equivalents (basic, pro, premium).
 *
 * @since 2.3.0
 *
 * @param string $required_plan Plan identifier or name to compare against.
 *
 * @return bool True when the user's subscription meets or exceeds the requirement.
 */
function ai4seo_user_has_at_least_plan( string $required_plan ): bool {
	// Cache comparisons per plan because one settings page can query every paid tier repeatedly.
	global $ai4seo_user_has_at_least_plan;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 988736227, 'Prevented loop', true );
		return false;
	}

	// Normalize required plans so settings-page gates can use identifiers or readable plan names interchangeably.
	$required_plan = ai4seo_normalize_plan_identifier( $required_plan );

	if ( ! $required_plan ) {
		return false;
	}

	if ( isset( $ai4seo_user_has_at_least_plan[ $required_plan ] ) ) {
		return (bool) $ai4seo_user_has_at_least_plan[ $required_plan ];
	}

	// Compare known plan positions only; malformed local subscription data is treated as free.
	$current_plan               = ai4seo_get_current_user_plan();
	$available_plans            = ai4seo_get_available_plans();
	$available_plan_identifiers = array_keys( $available_plans );
	$current_plan_index         = array_search( $current_plan, $available_plan_identifiers, true );

	if ( false === $current_plan_index ) {
		$current_plan_index = 0;
	}

	// Build the comparison cache in one pass because settings pages often query several tiers at once.
	foreach ( $available_plan_identifiers as $this_plan_index => $this_plan_identifier ) {
		$ai4seo_user_has_at_least_plan[ $this_plan_identifier ] = $current_plan_index >= $this_plan_index;
	}

	return (bool) ( $ai4seo_user_has_at_least_plan[ $required_plan ] ?? false );
}

// =========================================================================================== \\

/**
 * Determine whether the current account exactly matches the required plan.
 *
 * @param string $required_plan Plan identifier or name to compare against.
 * @return bool True when the current plan exactly matches the requirement.
 */
function ai4seo_user_has_exact_plan( string $required_plan ): bool {
	// Exact checks share the same normalization source as tier-gated settings checks.
	$required_plan = ai4seo_normalize_plan_identifier( $required_plan );

	if ( ! $required_plan ) {
		return false;
	}

	return ai4seo_get_current_user_plan() === $required_plan;
}

// =========================================================================================== \\

/**
 * Determine whether the current account has an active paid subscription.
 *
 * Credits packs and Pay-As-You-Go do not unlock subscription-only limits.
 *
 * @param string $required_plan Minimum paid plan identifier or name.
 * @return bool True when the current account has at least the required paid plan.
 */
function ai4seo_user_has_active_subscription( string $required_plan = 's' ): bool {
	// Active subscriptions are paid plans only; server-side sync downgrades inactive subscriptions to free.
	$required_plan = ai4seo_normalize_plan_identifier( $required_plan );

	if ( ! $required_plan || 'free' === $required_plan ) {
		return false;
	}

	return ai4seo_user_has_at_least_plan( $required_plan );
}


// endregion
// ___________________________________________________________________________________________.


// region CREDITS AND BILLING ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Return the pricing URL associated with the current RobHub client.
 *
 * @param string $ai4seo_client_id RobHub client identifier used for pricing attribution.
 * @return string Purchase plan URL.
 */
function ai4seo_get_purchase_plan_url( string $ai4seo_client_id ): string {
	// Normalize the attribution value while keeping the canonical pricing URL available as a fallback.
	$ai4seo_client_id   = sanitize_key( $ai4seo_client_id );
	$ai4seo_pricing_url = trailingslashit( AI4SEO_OFFICIAL_PRICING_URL );

	// Keep the pricing link usable without attribution when no RobHub client id is available yet.
	if ( '' === $ai4seo_client_id ) {
		return $ai4seo_pricing_url;
	}

	return add_query_arg( 'client-id', $ai4seo_client_id, $ai4seo_pricing_url );
}

// =========================================================================================== \\

/**
 * Return the recommended one-time credit pack size for the current missing entries.
 *
 * @return int Recommended credit amount.
 */
function ai4seo_get_recommended_credits_pack_size_by_num_missing_entries(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 978225511, 'Prevented loop', true );
		return 0;
	}

	// Compare estimated demand with the ordered pack registry used by the purchase modal.
	$approximate_credits_needed = ai4seo_get_approximate_credits_needed();
	$credits_packs              = ai4seo_get_credits_packs();

	// Recommend the first sufficient pack, capped at the third displayed option.
	$credits_pack_position = 0;
	foreach ( $credits_packs as $this_credits_pack ) {
		$this_credits_amount = (int) $this_credits_pack['credits_amount'];
		++$credits_pack_position;

		if ( $this_credits_amount >= $approximate_credits_needed ) {
			return $this_credits_amount;
		}

		// The purchase modal intentionally limits automatic recommendations to its first three packs.
		if ( $credits_pack_position >= 3 ) {
			return $this_credits_amount;
		}
	}

	// Empty or malformed comparisons fall back to the first configured pack when one exists.
	$first_credits_pack = reset( $credits_packs );
	return (int) ( $first_credits_pack['credits_amount'] ?? 0 );
}

// =========================================================================================== \\

/**
 * Estimate the credits needed to generate all currently missing entries.
 *
 * @return int Approximate number of credits needed.
 */
function ai4seo_get_approximate_credits_needed(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 736466930, 'Prevented loop', true );
		return 0;
	}

	// Missing-entry counts are already grouped by post type by the shared generation analysis.
	$approximate_credits_needed     = 0;
	$num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

	if ( ! $num_missing_posts_by_post_type ) {
		return 0;
	}

	// Resolve each generation context's unit cost once before aggregating all missing entries.
	$metadata_credits_cost_per_post                 = ai4seo_calculate_metadata_credits_cost_per_post();
	$attachment_attributes_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

	// Attachments use media-attribute pricing; every other supported post type uses metadata pricing.
	foreach ( $num_missing_posts_by_post_type as $post_type => $num_missing_posts ) {
		if ( 'attachment' === $post_type ) {
			$approximate_credits_needed += $num_missing_posts * $attachment_attributes_cost_per_attachment_post;
		} else {
			$approximate_credits_needed += $num_missing_posts * $metadata_credits_cost_per_post;
		}
	}

	return $approximate_credits_needed;
}

// =========================================================================================== \\

/**
 * Send the current Pay-As-You-Go settings to the RobHub account.
 *
 * @return bool Whether the settings were sent successfully.
 */
function ai4seo_send_pay_as_you_go_settings(): bool {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	// Keep the endpoint beside its payload because this operation is the account-domain API boundary.
	$payg_settings_endpoint = 'client/payg-settings';

	// Build the API payload from the canonical setting accessors before applying shared deep sanitization.
	$payg_settings                                        = array();
	$payg_settings[ AI4SEO_SETTING_PAYG_ENABLED ]         = (bool) ai4seo_get_setting( AI4SEO_SETTING_PAYG_ENABLED );
	$payg_settings[ AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID ] = ai4seo_get_setting( AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID );
	$payg_settings[ AI4SEO_SETTING_PAYG_DAILY_BUDGET ]    = (int) ai4seo_get_setting( AI4SEO_SETTING_PAYG_DAILY_BUDGET );
	$payg_settings[ AI4SEO_SETTING_PAYG_MONTHLY_BUDGET ]  = (int) ai4seo_get_setting( AI4SEO_SETTING_PAYG_MONTHLY_BUDGET );
	$payg_settings                                        = ai4seo_deep_sanitize( $payg_settings );

	$api_response = ai4seo_robhub_api()->call( $payg_settings_endpoint, $payg_settings );

	// Preserve the current error path when RobHub rejects or cannot process the synchronized settings.
	if ( ! ai4seo_robhub_api()->was_call_successful( $api_response ) ) {
		ai4seo_debug_message( 361217325, 'Invalid response from RobHub API.', true );
		return false;
	}

	// A successful synchronization clears stale PAYG errors and restores the local idle state.
	ai4seo_remove_notification( 'payg-status-error' );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS, 'idle' );
	ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );

	return true;
}


// endregion
// ___________________________________________________________________________________________.


// region ACCOUNT SYNCHRONIZATION ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Synchronize the RobHub account when locally observed account state is stale.
 *
 * @return void
 */
function ai4seo_check_for_robhub_account_sync(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Unsynchronized installations need an immediate baseline before other account checks are meaningful.
	$is_account_synced = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED );

	if ( ! $is_account_synced ) {
		ai4seo_sync_robhub_account( 'not_yet_synced' );
		return;
	}

	// Regular interval synchronization keeps subscription, credits, and remote notices current.
	$last_account_sync = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC );

	if ( $last_account_sync < time() - ai4seo_robhub_api()::ACCOUNT_SYNC_INTERVAL ) {
		ai4seo_sync_robhub_account( 'regular_interval' );
		return;
	}

	// Refresh after the scheduled free-credit timestamp so the local balance reflects the remote grant.
	$next_free_credits_timestamp = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP );

	if ( $next_free_credits_timestamp && $next_free_credits_timestamp < time() ) {
		ai4seo_sync_robhub_account( 'next_free_credits_passed' );
		return;
	}

	// Low-credit PAYG accounts poll briefly for a pending payment while dashboard activity can display the result.
	$is_payg_enabled = (bool) ai4seo_get_setting( AI4SEO_SETTING_PAYG_ENABLED );
	$credits_balance = ai4seo_robhub_api()->get_credits_balance();

	if ( $is_payg_enabled && $credits_balance < 100 ) {
		$current_timestamp                         = time();
		$did_trigger_payg_waiting_for_payment_sync = false;

		// Track the first timestamp when this low-credits + PAYG state started.
		$payg_low_credits_first_occurrence_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME );

		if ( ! $payg_low_credits_first_occurrence_time ) {
			$payg_low_credits_first_occurrence_time = $current_timestamp;
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME, $payg_low_credits_first_occurrence_time );
		}

		// Hard-stop this sync reason after 60 minutes from first occurrence.
		if ( $current_timestamp - $payg_low_credits_first_occurrence_time <= HOUR_IN_SECONDS ) {
			$payg_low_credits_last_sync_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME );

			// Rate-limit to at most once every 5 minutes while inside the 60-minute window.
			if ( ! $payg_low_credits_last_sync_time || $payg_low_credits_last_sync_time <= $current_timestamp - ( 5 * MINUTE_IN_SECONDS ) ) {
				ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME, $current_timestamp );
				ai4seo_sync_robhub_account( 'payg_waiting_for_payment' );
				$did_trigger_payg_waiting_for_payment_sync = true;
			}
		}

		// Return only when this branch actually triggered the PAYG waiting-for-payment sync.
		if ( $did_trigger_payg_waiting_for_payment_sync ) {
			return;
		}
	} else {
		// Reset tracking once the low-credits + PAYG condition is no longer active.
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME, 0 );
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME, 0 );
	}

	// Recent purchases keep polling while credits remain low so completed one-time payments appear promptly.
	$just_purchased_something_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME );

	if ( $just_purchased_something_time && $just_purchased_something_time > time() - 7200 && $credits_balance < 100 ) {
		ai4seo_sync_robhub_account( 'waiting_for_payment' );
		return;
	}
}

// =========================================================================================== \\

/**
 * Synchronize remote RobHub account data into local settings and environmental state.
 *
 * @param string $sync_reason              Reason for the sync (for logging purposes).
 * @param bool   $allow_notification_force Whether to force a notification to be sent in case of an error.
 * @return array API response.
 */
function ai4seo_sync_robhub_account( string $sync_reason = 'unknown', bool $allow_notification_force = false ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 10 ) ) {
		ai4seo_debug_message( 461426226, 'Prevented loop', true );
		return array();
	}

	$api_response = ai4seo_robhub_api()->sync_account( $sync_reason );

	// Let the existing account-error notification mechanism interpret every API response.
	ai4seo_check_for_robhub_account_error_notification( $api_response, true );

	// Stop before local writes unless RobHub supplied a successful, non-empty account payload.
	if ( ! ai4seo_robhub_api()->was_call_successful( $api_response ) || ! isset( $api_response['data'] ) || ! is_array( $api_response['data'] ) || ! $api_response['data'] ) {
		ai4seo_debug_message( 451426226, 'Account sync failed or returned invalid data', true );
		return $api_response;
	}

	// Apply the validated response through the existing setting, state, and notification mechanisms.
	$synced_account_data                  = $api_response['data'];
	$last_website_toc_and_pp_update_time = (int) ( $synced_account_data['last_terms_update_time'] ?? false );

	// Keep legal acceptance checks aligned with the latest terms timestamp supplied by RobHub.
	if ( $last_website_toc_and_pp_update_time && ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME ) != $last_website_toc_and_pp_update_time ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME, $last_website_toc_and_pp_update_time );
	}

	// Persist the account purchase flag independently from plan or credit state.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING, (bool) ( $synced_account_data['has_purchased_something'] ?? false ) );

	// Update only PAYG values present in the response so omitted remote fields retain their local state.
	if ( isset( $synced_account_data['is_payg_enabled'] ) ) {
		ai4seo_update_setting( AI4SEO_SETTING_PAYG_ENABLED, (bool) $synced_account_data['is_payg_enabled'] );
	}

	if ( isset( $synced_account_data['stripe_price_id'] ) && $synced_account_data['stripe_price_id'] ) {
		ai4seo_update_setting( AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID, sanitize_text_field( $synced_account_data['stripe_price_id'] ) );
	}

	if ( isset( $synced_account_data['payg_daily_budget'] ) && is_numeric( $synced_account_data['payg_daily_budget'] ) ) {
		ai4seo_update_setting( AI4SEO_SETTING_PAYG_DAILY_BUDGET, (int) $synced_account_data['payg_daily_budget'] );
	}

	if ( isset( $synced_account_data['payg_monthly_budget'] ) && is_numeric( $synced_account_data['payg_monthly_budget'] ) ) {
		ai4seo_update_setting( AI4SEO_SETTING_PAYG_MONTHLY_BUDGET, (int) $synced_account_data['payg_monthly_budget'] );
	}

	if ( isset( $synced_account_data['payg_status'] ) && in_array( $synced_account_data['payg_status'], AI4SEO_ALLOWED_PAYG_STATUS ) ) {
		if ( isset( $synced_account_data['payg_failure_reason'] ) && is_string( $synced_account_data['payg_failure_reason'] ) ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON, sanitize_key( $synced_account_data['payg_failure_reason'] ) );
		} else {
			ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );
		}

		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS, sanitize_key( $synced_account_data['payg_status'] ) );

		ai4seo_check_for_payg_status_errors( $synced_account_data['payg_status'] );
	} else {
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS );
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );
	}

	// Mirror the one-time feedback-offer claim so the deactivation modal does not offer it again.
	if ( isset( $synced_account_data['claimed_feedback_offer'] ) && $synced_account_data['claimed_feedback_offer'] ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER, true );
	} else {
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER );
	}

	// Keep locally displayed pricing aligned with the account's preferred currency when supplied.
	if ( isset( $synced_account_data['preferred_currency'] ) && $synced_account_data['preferred_currency'] ) {
		ai4seo_update_setting( AI4SEO_SETTING_PREFERRED_CURRENCY, $synced_account_data['preferred_currency'] );
	}

	// Reuse the plugin update mechanism for the latest version reported during account synchronization.
	ai4seo_check_for_plugin_update_available( $synced_account_data['latest_product_version'] ?? '', true );

	// Store valid active discounts and clear both state and notices when no discount is returned.
	if ( isset( $synced_account_data['discount'] ) && is_array( $synced_account_data['discount'] ) ) {
		$discount = $synced_account_data['discount'];

		if ( isset( $discount['name'] ) && $discount['name'] && isset( $discount['percentage'] ) && is_numeric( $discount['percentage'] ) ) {
			// Normalize percentage and optional expiry values before storing the discount payload.
			$discount['percentage'] = (int) $discount['percentage'];

			if ( isset( $discount['expire_in'] ) ) {
				$discount['expire_in'] = (int) $discount['expire_in'];
			}

			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT, $discount );
			ai4seo_check_discount_notification( $discount, $allow_notification_force );
		}
	} else {
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT );
		ai4seo_remove_notification( 'discount' );
	}

	// Import remote account notifications through the canonical local notification store.
	if ( isset( $synced_account_data['notifications'] ) && is_array( $synced_account_data['notifications'] ) ) {
		$notifications = $synced_account_data['notifications'];

		foreach ( $notifications as $notification_index => $notification ) {
			if ( ! isset( $notification['message'] ) || ! $notification['message'] ) {
				continue;
			}

			// Pass the visible message separately because remaining fields are notification metadata.
			$message = $notification['message'];
			unset( $notification['message'] );

			// Honor remote force flags only when this synchronization call explicitly permits them.
			if ( $allow_notification_force ) {
				$force = isset( $notification['force'] ) && (bool) $notification['force'];
			} else {
				$force = false;
			}

			unset( $notification['force'] );

			ai4seo_push_notification( $notification_index, $message, $force, $notification );
		}
	}

	// Remote auto-retry authorization clears both failed queues and refreshes their shared status summary.
	if ( isset( $synced_account_data['auto_retry_failed'] ) && $synced_account_data['auto_retry_failed'] ) {
		// Clear both generation contexts so their cron workers can retry failed entries.
		ai4seo_delete_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );
		ai4seo_delete_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

		// Refresh the generation status summary after the already-authorized account sync mutation.
		ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();
	}

	return $api_response;
}


// endregion
// ___________________________________________________________________________________________.
