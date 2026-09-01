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
	$current_plan = ai4seo_normalize_plan_identifier( $current_subscription['plan'] ?? 'free' );

	if ( ! $current_plan ) {
		$current_plan = 'free';
	}

	$ai4seo_current_user_plan = $current_plan;

	return $ai4seo_current_user_plan;
}

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
 * Return the non-autoloaded option name for a hashed purchase-return token.
 *
 * @param string $token Raw purchase-return token.
 * @return string Option name.
 */
function ai4seo_get_purchase_return_token_option_name( string $token ): string {
	return AI4SEO_PURCHASE_RETURN_TOKEN_OPTION_PREFIX . hash( 'sha256', $token );
}

/**
 * Check the exact schema used for internally stored purchase-return state.
 *
 * @param mixed $token_state Stored purchase-return state.
 * @return bool Whether the state contains exact positive integer fields.
 */
function ai4seo_is_purchase_return_token_state_valid( $token_state ): bool {
	return is_array( $token_state )
		&& isset( $token_state['blog_id'], $token_state['user_id'], $token_state['expires_at'] )
		&& is_int( $token_state['blog_id'] )
		&& is_int( $token_state['user_id'] )
		&& is_int( $token_state['expires_at'] )
		&& $token_state['blog_id'] > 0
		&& $token_state['user_id'] > 0
		&& $token_state['expires_at'] > 0;
}

/**
 * Ensure that a validated token option has one future expiry event.
 *
 * @param string $option_name Hashed purchase-return option name.
 * @param int    $expires_at  Absolute Unix expiry time.
 * @return bool Whether an expiry event exists or was scheduled.
 */
function ai4seo_schedule_purchase_return_token_expiry( string $option_name, int $expires_at ): bool {
	$valid_option_name_pattern = '/\A' . preg_quote( AI4SEO_PURCHASE_RETURN_TOKEN_OPTION_PREFIX, '/' ) . '[a-f0-9]{64}\z/';
	$event_arguments           = array( $option_name );

	if ( 1 !== preg_match( $valid_option_name_pattern, $option_name ) || $expires_at <= 0 ) {
		return false;
	}

	if ( false !== wp_next_scheduled( AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK, $event_arguments ) ) {
		return true;
	}

	$scheduled = wp_schedule_single_event(
		$expires_at,
		AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK,
		$event_arguments,
		true
	);

	return true === $scheduled;
}

/**
 * Remove a purchase-return token and its scheduled expiry event.
 *
 * @param string $token Raw purchase-return token.
 * @return bool Whether the stored token was removed.
 */
function ai4seo_delete_purchase_return_token( string $token ): bool {
	if ( 1 !== preg_match( '/\A[A-Za-z0-9]{32}\z/', $token ) ) {
		return false;
	}

	$option_name = ai4seo_get_purchase_return_token_option_name( $token );
	$deleted     = delete_option( $option_name );

	// Preserve the cleanup event when a database failure leaves the durable option in place.
	if ( $deleted ) {
		wp_clear_scheduled_hook( AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK, array( $option_name ) );
	}

	return $deleted;
}

/**
 * Delete an expired purchase-return token through its validated cron argument.
 *
 * @param string $option_name Hashed purchase-return option name.
 * @return void
 */
function ai4seo_expire_purchase_return_token( string $option_name ): void {
	$valid_option_name_pattern = '/\A' . preg_quote( AI4SEO_PURCHASE_RETURN_TOKEN_OPTION_PREFIX, '/' ) . '[a-f0-9]{64}\z/';

	if ( 1 !== preg_match( $valid_option_name_pattern, $option_name ) ) {
		return;
	}

	$missing_token_state = new stdClass();
	$token_state         = get_option( $option_name, $missing_token_state );

	if ( $missing_token_state === $token_state ) {
		return;
	}

	// An early or recovered callback must preserve valid state and restore its missing expiry event.
	if ( ai4seo_is_purchase_return_token_state_valid( $token_state ) && $token_state['expires_at'] > time() ) {
		ai4seo_schedule_purchase_return_token_expiry( $option_name, $token_state['expires_at'] );
		return;
	}

	if ( delete_option( $option_name ) ) {
		wp_clear_scheduled_hook( AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK, array( $option_name ) );
		return;
	}

	// Retry a failed database deletion because the one-shot event has already been consumed.
	if ( get_option( $option_name, $missing_token_state ) !== $missing_token_state ) {
		ai4seo_schedule_purchase_return_token_expiry( $option_name, time() + HOUR_IN_SECONDS );
	}
}

/**
 * Reconcile a bounded set of durable return tokens after missed cron callbacks.
 *
 * @param int $limit Maximum number of oldest token options to inspect.
 * @return void
 */
function ai4seo_reconcile_purchase_return_tokens( int $limit = 100 ): void {
	global $wpdb;

	$limit       = max( 1, min( 100, $limit ) );
	$option_like = $wpdb->esc_like( AI4SEO_PURCHASE_RETURN_TOKEN_OPTION_PREFIX ) . '%';
	$query       = $wpdb->prepare(
		'SELECT option_name FROM ' . esc_sql( $wpdb->options ) . ' WHERE option_name LIKE %s ORDER BY option_id ASC LIMIT %d',
		$option_like,
		$limit
	);

	// A prefix query is required because WordPress has no API for discovering non-autoloaded option names.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- The table is core-owned, the values are prepared, and each result is re-read through get_option().
	$option_names = $wpdb->get_col( $query );

	if ( ! is_array( $option_names ) ) {
		return;
	}

	// Oldest-first ordering lets repeated bounded runs advance naturally as fixed-lifetime tokens expire.
	foreach ( $option_names as $option_name ) {
		ai4seo_expire_purchase_return_token( (string) $option_name );
	}
}

/**
 * Create a site- and user-bound purchase-return token.
 *
 * @return string Raw token, or an empty string when it could not be stored.
 */
function ai4seo_create_purchase_return_token(): string {
	$current_user_id = get_current_user_id();

	// Checkout can only start for a site administrator within the active Incognito boundary.
	if ( $current_user_id <= 0 || ! ai4seo_can_administer_plugin() ) {
		return '';
	}

	// Recover expired rows and missing events left by disabled cron or a temporarily inactive plugin.
	ai4seo_reconcile_purchase_return_tokens();

	// Keep only a hash-derived option name in storage while the raw token travels through the checkout redirect.
	$token       = wp_generate_password( 32, false, false );
	$expires_at  = time() + AI4SEO_PURCHASE_RETURN_TOKEN_TTL_SECONDS;
	$option_name = ai4seo_get_purchase_return_token_option_name( $token );
	$stored      = add_option(
		$option_name,
		array(
			'blog_id'    => get_current_blog_id(),
			'user_id'    => $current_user_id,
			'expires_at' => $expires_at,
		),
		'',
		false
	);

	if ( ! $stored ) {
		return '';
	}

	// Pair durable state with explicit expiry so external object-cache eviction cannot invalidate checkout returns.
	if ( ! ai4seo_schedule_purchase_return_token_expiry( $option_name, $expires_at ) ) {
		delete_option( $option_name );
		return '';
	}

	return $token;
}

/**
 * Consume a valid purchase-return token exactly once.
 *
 * @param string $token Raw purchase-return token.
 * @return bool Whether the token was valid and this request consumed it.
 */
function ai4seo_consume_purchase_return_token( string $token ): bool {
	// Reject malformed input before deriving an option name or touching persistent state.
	if ( 1 !== preg_match( '/\A[A-Za-z0-9]{32}\z/', $token ) ) {
		return false;
	}

	$option_name = ai4seo_get_purchase_return_token_option_name( $token );
	$token_state = get_option( $option_name, null );

	if ( ! is_array( $token_state ) ) {
		if ( null !== $token_state ) {
			ai4seo_delete_purchase_return_token( $token );
		}

		return false;
	}

	// Stored state is internal-only, so reject coercible or partial records instead of normalizing them.
	if ( ! ai4seo_is_purchase_return_token_state_valid( $token_state ) ) {
		ai4seo_delete_purchase_return_token( $token );
		return false;
	}

	// Leave another user's valid state intact while rejecting expired or structurally invalid records.
	if ( $token_state['expires_at'] <= time() ) {
		ai4seo_delete_purchase_return_token( $token );
		return false;
	}

	if ( get_current_blog_id() !== $token_state['blog_id'] || get_current_user_id() !== $token_state['user_id'] ) {
		return false;
	}

	// Core option deletion is database-authoritative, so concurrent requests have one storage-level winner.
	$consumed = delete_option( $option_name );

	if ( $consumed ) {
		wp_clear_scheduled_hook( AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK, array( $option_name ) );
	}

	return $consumed;
}

/**
 * Build the dashboard URL used after a completed purchase.
 *
 * @param string $purchase_return_token Optional token created by the checkout caller.
 * @return string Single-use purchase-return URL, or an empty string when token storage failed.
 */
function ai4seo_get_purchase_return_url( string $purchase_return_token = '' ): string {
	if ( '' === $purchase_return_token ) {
		$purchase_return_token = ai4seo_create_purchase_return_token();
	}

	if ( 1 !== preg_match( '/\A[A-Za-z0-9]{32}\z/', $purchase_return_token ) ) {
		return '';
	}

	$purchase_return_url = ai4seo_get_subpage_url(
		'dashboard',
		array(
			AI4SEO_PURCHASE_RETURN_QUERY_PARAMETER       => 'true',
			AI4SEO_PURCHASE_RETURN_TOKEN_QUERY_PARAMETER => $purchase_return_token,
		)
	);

	if ( '' === $purchase_return_url ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
	}

	return $purchase_return_url;
}

/**
 * Read a purchase-return value from canonical or legacy entity-prefixed query keys.
 *
 * @param string $query_parameter Canonical query parameter name.
 * @return string Sanitized query value, or an empty string when unavailable.
 */
function ai4seo_read_purchase_return_query_value( string $query_parameter ): string {
	$query_parameters = array(
		$query_parameter,
		'amp;' . $query_parameter,
	);

	// Preserve canonical-key precedence while accepting Stripe's legacy entity-prefixed redirect keys.
	foreach ( $query_parameters as $candidate_query_parameter ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This read-only helper feeds the caller's one-time-token validation.
		if ( ! array_key_exists( $candidate_query_parameter, $_GET ) ) {
			continue;
		}

		// An invalid canonical value must not fall through to a second, attacker-controlled representation.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This read-only helper feeds the caller's one-time-token validation.
		if ( ! is_string( $_GET[ $candidate_query_parameter ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This read-only helper feeds the caller's one-time-token validation.
		return sanitize_text_field( wp_unslash( $_GET[ $candidate_query_parameter ] ) );
	}

	return '';
}

/**
 * Check whether the current URL carries any canonical or legacy purchase-return fields.
 *
 * @return bool Whether purchase-return query cleanup should run.
 */
function ai4seo_has_purchase_return_query_parameters(): bool {
	$query_parameters = array(
		AI4SEO_PURCHASE_RETURN_QUERY_PARAMETER,
		'amp;' . AI4SEO_PURCHASE_RETURN_QUERY_PARAMETER,
		AI4SEO_PURCHASE_RETURN_TOKEN_QUERY_PARAMETER,
		'amp;' . AI4SEO_PURCHASE_RETURN_TOKEN_QUERY_PARAMETER,
	);

	foreach ( $query_parameters as $query_parameter ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence-only read determines whether the browser should remove return data.
		if ( array_key_exists( $query_parameter, $_GET ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Record purchase activity and make any pending credential transition immediately retryable.
 *
 * Checkout initialization, pricing visits, and verified returns share this signal so account
 * polling and password-rotation recovery always move onto the same fast reconciliation cadence.
 *
 * @return void
 */
function ai4seo_record_purchase_activity(): void {
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time() );
	ai4seo_robhub_api()->accelerate_pending_api_password_rotation_reconciliation();
}

/**
 * Validate a purchase return before updating state and displaying its success modal.
 *
 * @return bool Whether the purchase-return modal should be rendered.
 */
function ai4seo_process_purchase_return_request(): bool {
	// Purchase state and success UI expose account-level state and require site administration.
	if ( ! ai4seo_can_administer_plugin() ) {
		return false;
	}

	// Require the exact checkout-return marker before looking up its one-time state.
	$purchase_return_flag = ai4seo_read_purchase_return_query_value( AI4SEO_PURCHASE_RETURN_QUERY_PARAMETER );

	if ( 'true' !== $purchase_return_flag ) {
		return false;
	}

	// Consume the site- and user-bound state before recording recent purchase activity or rendering success UI.
	$purchase_return_token = ai4seo_read_purchase_return_query_value( AI4SEO_PURCHASE_RETURN_TOKEN_QUERY_PARAMETER );

	if ( ! ai4seo_consume_purchase_return_token( $purchase_return_token ) ) {
		return false;
	}

	// Put both account polling and credential reconciliation onto their shared purchase cadence.
	ai4seo_record_purchase_activity();

	return true;
}

/**
 * Prepare the signed claim that binds a first purchase to this site's password rotation.
 *
 * Paid accounts do not need another automatic rotation. Returning an empty string for them lets
 * callers distinguish the existing-account path from a failed first-purchase preparation.
 *
 * @return string Exact signed claim for a first purchase, or an empty string when unnecessary or unavailable.
 */
function ai4seo_prepare_first_purchase_api_password_rotation_claim(): string {
	$has_purchased_something = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );

	if ( $has_purchased_something ) {
		return '';
	}

	return ai4seo_robhub_api()->prepare_api_password_rotation_claim();
}

/**
 * Build the RobHub parameters used to initialize one credit-pack checkout.
 *
 * Keeping the signed claim assignment in one pure helper makes its byte-for-byte transport
 * contract independently verifiable without invoking the terminating AJAX response boundary.
 *
 * @param string $stripe_price_id Stripe price identifier selected by the administrator.
 * @param string $redirect_url Secure site-local return URL.
 * @param string $rotation_claim_token Optional signed first-purchase rotation claim.
 * @return array RobHub init-purchase parameters.
 */
function ai4seo_build_credit_pack_purchase_parameters(
	string $stripe_price_id,
	string $redirect_url,
	string $rotation_claim_token = ''
): array {
	$endpoint_parameters = array(
		'stripe_price_id' => $stripe_price_id,
		'redirect_url'    => $redirect_url,
	);

	if ( '' !== $rotation_claim_token ) {
		$endpoint_parameters['rotation_claim_token'] = $rotation_claim_token;
	}

	return $endpoint_parameters;
}

/**
 * Return the pricing URL associated with an already-prepared purchase reference.
 *
 * This builder is deliberately side-effect free: rendering a button must never prepare a password
 * rotation or make a RobHub request. Callers that need a first-purchase claim must prepare it only
 * after an explicit administrator CTA and then pass the exact signed value here.
 *
 * @param string $pricing_reference Legacy client identifier or signed first-purchase claim.
 * @return string Purchase plan URL.
 */
function ai4seo_get_purchase_plan_url( string $pricing_reference ): string {
	if ( ! ai4seo_can_administer_plugin() ) {
		return '';
	}

	// Preserve either reference byte-for-byte through add_query_arg() under the shared Stripe contract.
	if ( '' !== $pricing_reference
		&& ! Ai4Seo_RobHubApiCommunicator::is_purchase_client_reference_valid( $pricing_reference ) ) {
		return '';
	}

	$ai4seo_pricing_url = trailingslashit( AI4SEO_OFFICIAL_PRICING_URL );

	// Keep the pricing link usable without attribution when no RobHub client id is available yet.
	if ( '' === $pricing_reference ) {
		return $ai4seo_pricing_url;
	}

	return add_query_arg( 'client-id', $pricing_reference, $ai4seo_pricing_url );
}


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
 * Reset persisted low-credit PAYG tracking after its condition becomes inactive.
 *
 * One raw presence check keeps the steady state out of the mutation path without
 * hiding explicitly stored defaults or invalid target values that still need repair.
 * Every required reset delegates to the authoritative environmental CAS writer, so
 * this preflight is never reused as mutation input. Unreadable storage falls back to
 * the existing setters and their cache-reconciliation behavior.
 *
 * @return bool True when cleanup was unnecessary or every required reset succeeded.
 */
function ai4seo_reset_inactive_payg_low_credit_tracking(): bool {
	// Inspect one raw snapshot so absent default-valued overrides never enter the mutation path.
	$tracking_variable_names = array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME,
		AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME,
	);
	$environmental_snapshot  = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
	$persisted_overrides     = null;

	// Classify missing and valid storage while preserving null as the failed-read signal.
	if ( null !== $environmental_snapshot ) {
		if ( ! $environmental_snapshot['exists'] ) {
			$persisted_overrides = array();
		} elseif ( is_array( $environmental_snapshot['value'] ) ) {
			$persisted_overrides = $environmental_snapshot['value'];
		}
	}

	$did_reset_all_tracking = true;

	// Attempt every required reset so one failed field cannot leave another stale field untouched.
	foreach ( $tracking_variable_names as $tracking_variable_name ) {
		// A valid raw map distinguishes a missing override from a validated default.
		if ( is_array( $persisted_overrides ) && ! array_key_exists( $tracking_variable_name, $persisted_overrides ) ) {
			continue;
		}

		$did_reset_tracking = ai4seo_update_environmental_variable( $tracking_variable_name, 0 );

		if ( ! $did_reset_tracking ) {
			$did_reset_all_tracking = false;
		}
	}

	return $did_reset_all_tracking;
}


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
		ai4seo_reset_inactive_payg_low_credit_tracking();
	}

	// Recent purchases keep polling while credits remain low so completed one-time payments appear promptly.
	$just_purchased_something_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME );

	if ( $just_purchased_something_time && $just_purchased_something_time > time() - 7200 && $credits_balance < 100 ) {
		ai4seo_sync_robhub_account( 'waiting_for_payment' );
		return;
	}
}


/**
 * Generate one opaque generation token for a durable automatic-retry request.
 *
 * @return string Canonical UUID token.
 */
function ai4seo_generate_auto_retry_failed_request_token(): string {
	return strtolower( wp_generate_uuid4() );
}


/**
 * Replace or delete an exact fallback-marker generation through bounded option CAS retries.
 *
 * A mismatched current generation is a successful no-op because it belongs to another request.
 *
 * @param string|bool $expected_value Expected token, true for legacy migration, or false for absence.
 * @param string|bool $replacement_value Replacement token or false for deletion.
 * @param bool        $did_replace Receives whether the expected generation was replaced.
 * @return bool True after a checked replacement/mismatch, false on malformed storage or database failure.
 */
function ai4seo_compare_and_swap_auto_retry_failed_fallback_marker(
	$expected_value,
	$replacement_value,
	bool &$did_replace
): bool {
	$did_replace   = false;
	$attempt_limit = ai4seo_get_environmental_variable_mutation_attempt_limit();

	if (
		false === ai4seo_normalize_auto_retry_failed_request_token( $expected_value )
		&& false !== $expected_value
	) {
		return false;
	}

	if (
		false === ai4seo_normalize_auto_retry_failed_request_token( $replacement_value )
		&& false !== $replacement_value
	) {
		return false;
	}

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME );

		if ( null === $option_snapshot ) {
			return false;
		}

		$current_value = $option_snapshot['exists']
			? ai4seo_normalize_auto_retry_failed_request_token( $option_snapshot['value'] )
			: false;

		if ( $option_snapshot['exists'] && false === $current_value && false !== $option_snapshot['value'] ) {
			return false;
		}

		if ( $current_value !== $expected_value ) {
			return true;
		}

		if ( false === $replacement_value ) {
			$compare_and_swap_result = ai4seo_compare_and_delete_option_snapshot(
				AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME,
				$option_snapshot
			);
		} else {
			$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
				AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME,
				$option_snapshot,
				$replacement_value,
				false
			);
		}

		if ( true === $compare_and_swap_result ) {
			$did_replace = true;
			return true;
		}

		if ( null === $compare_and_swap_result ) {
			return false;
		}
	}

	return false;
}


/**
 * Store a fresh generation token in the independent fallback marker.
 *
 * @param string $request_token Canonical request token.
 * @return bool True when the exact token was persisted.
 */
function ai4seo_persist_auto_retry_failed_fallback_token( string $request_token ): bool {
	$attempt_limit = ai4seo_get_environmental_variable_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$option_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME );

		if ( null === $option_snapshot ) {
			return false;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME,
			$option_snapshot,
			$request_token,
			false
		);

		if ( true === $compare_and_swap_result ) {
			return true;
		}

		if ( null === $compare_and_swap_result ) {
			return false;
		}
	}

	return false;
}


/**
 * Persist one fresh remote automatic-retry request generation.
 *
 * The environmental value is canonical. The standalone option is a checked fallback for a failed
 * environmental-map CAS, while generation tokens prevent an older pass from consuming a newer request.
 *
 * @return string|bool Canonical request token, or false when neither durable marker was verified.
 */
function ai4seo_persist_auto_retry_failed_required() {
	$request_token = ai4seo_generate_auto_retry_failed_request_token();

	if (
		ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED,
			$request_token,
			false
		)
	) {
		return $request_token;
	}

	if ( ai4seo_persist_auto_retry_failed_fallback_token( $request_token ) ) {
		return $request_token;
	}

	ai4seo_debug_message( 81120826, 'Could not persist either durable automatic retry marker.', true );
	return false;
}


/**
 * Read and, when necessary, migrate the oldest available durable retry generation.
 *
 * @return string|null Canonical token, an empty string when idle, or null on malformed/database storage.
 */
function ai4seo_read_auto_retry_failed_required_token(): ?string {
	$attempt_limit = ai4seo_get_environmental_variable_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$environmental_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );

		if ( null === $environmental_snapshot ) {
			return null;
		}

		$environmental_overrides = $environmental_snapshot['exists'] ? $environmental_snapshot['value'] : array();

		if ( ! is_array( $environmental_overrides ) ) {
			return null;
		}

		$environmental_value = $environmental_overrides[ AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED ] ?? false;
		$environmental_token = ai4seo_normalize_auto_retry_failed_request_token( $environmental_value );

		if ( false !== $environmental_value && false === $environmental_token ) {
			return null;
		}

		if ( true === $environmental_token ) {
			$replacement_token = ai4seo_generate_auto_retry_failed_request_token();
			$did_replace       = false;

			if ( ! ai4seo_compare_and_swap_environmental_variable_value(
				AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED,
				true,
				$replacement_token,
				$did_replace
			) ) {
				return null;
			}

			if ( $did_replace ) {
				return $replacement_token;
			}

			continue;
		}

		if ( is_string( $environmental_token ) ) {
			return $environmental_token;
		}

		$fallback_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME );

		if ( null === $fallback_snapshot ) {
			return null;
		}

		if ( ! $fallback_snapshot['exists'] ) {
			return '';
		}

		$fallback_token = ai4seo_normalize_auto_retry_failed_request_token( $fallback_snapshot['value'] );

		if ( false === $fallback_token ) {
			return null;
		}

		if ( true === $fallback_token ) {
			$replacement_token = ai4seo_generate_auto_retry_failed_request_token();
			$did_replace       = false;

			if ( ! ai4seo_compare_and_swap_auto_retry_failed_fallback_marker(
				true,
				$replacement_token,
				$did_replace
			) ) {
				return null;
			}

			if ( $did_replace ) {
				return $replacement_token;
			}

			continue;
		}

		return $fallback_token;
	}

	return null;
}


/**
 * Preserve retry ownership after a partial reconciliation without overwriting a newer generation.
 *
 * @param string $request_token Generation currently being reconciled.
 * @return bool True when this or a newer durable generation exists.
 */
function ai4seo_retain_auto_retry_failed_required( string $request_token ): bool {
	$did_replace = false;

	if ( ai4seo_compare_and_swap_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED,
		false,
		$request_token,
		$did_replace
	) ) {
		return true;
	}

	$fallback_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME );

	if ( null === $fallback_snapshot ) {
		return false;
	}

	if ( $fallback_snapshot['exists'] ) {
		$fallback_token = ai4seo_normalize_auto_retry_failed_request_token( $fallback_snapshot['value'] );

		if ( false !== $fallback_token ) {
			return true;
		}
	}

	return ai4seo_persist_auto_retry_failed_fallback_token( $request_token );
}


/**
 * Consume only the exact retry generation completed by this reconciliation pass.
 *
 * @param string $request_token Completed generation token.
 * @return bool True when both marker stores were checked without deleting a newer generation.
 */
function ai4seo_consume_auto_retry_failed_required( string $request_token ): bool {
	$environmental_did_clear = false;

	$environmental_store_succeeded = ai4seo_compare_and_swap_environmental_variable_value(
		AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED,
		$request_token,
		false,
		$environmental_did_clear
	);
	$fallback_did_clear            = false;
	$fallback_store_succeeded      = ai4seo_compare_and_swap_auto_retry_failed_fallback_marker(
		$request_token,
		false,
		$fallback_did_clear
	);

	return $environmental_store_succeeded && $fallback_store_succeeded;
}


/**
 * Reconcile a durable RobHub request to retry every failed generation entry.
 *
 * The local token outlives a one-shot API response. It is consumed only after both Failed families
 * were verified empty and their derived summary was refreshed or durably queued for a rebuild.
 *
 * @param string $request_token Optional freshly persisted remote-request generation.
 * @return bool True when no retry is required or the complete local request was reconciled.
 */
function ai4seo_reconcile_required_auto_retry_failed( string $request_token = '' ): bool {
	if ( '' === $request_token ) {
		$request_token = ai4seo_read_auto_retry_failed_required_token();

		if ( null === $request_token ) {
			return false;
		}

		if ( '' === $request_token ) {
			return true;
		}
	} elseif ( ! is_string( ai4seo_normalize_auto_retry_failed_request_token( $request_token ) ) ) {
		return false;
	}

	$failed_option_names = array(
		AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	// Clear both generation families inside one shared post-ID transition fence.
	if ( ! ai4seo_clear_post_id_options( $failed_option_names ) ) {
		// Re-verify durable retry ownership after a failed destructive phase, including an
		// explicitly authorized pass whose first marker write could not be persisted.
		if ( ! ai4seo_retain_auto_retry_failed_required( $request_token ) ) {
			ai4seo_debug_message( 80120826, 'Could not retain automatic retry ownership after the Failed-family clear did not complete.', true );
		}

		// A partial clear makes the summary stale, while the retained retry marker requests another idempotent pass.
		if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
			ai4seo_debug_message( 82120826, 'Could not durably schedule generation-status reconciliation after a failed automatic retry clear.', true );
		}

		return false;
	}

	$summary_reconciled = ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();

	// Lock contention or a paused analysis remains safe when a verified durable replacement rebuild exists.
	if ( ! $summary_reconciled ) {
		$summary_reconciled = ai4seo_schedule_generation_status_summary_rebuild();
	}

	if ( ! $summary_reconciled ) {
		if ( ! ai4seo_retain_auto_retry_failed_required( $request_token ) ) {
			ai4seo_debug_message( 79120826, 'Could not retain automatic retry ownership after generation-status reconciliation failed.', true );
		}

		ai4seo_debug_message( 83120826, 'Could not reconcile or durably schedule generation-status analysis after an automatic retry clear.', true );
		return false;
	}

	if ( ! ai4seo_consume_auto_retry_failed_required( $request_token ) ) {
		ai4seo_debug_message( 84120826, 'Could not clear the durable automatic retry request after successful local reconciliation.', true );
		return false;
	}

	return true;
}


/**
 * Normalize one RobHub discount payload to the fields consumed by local storage and notices.
 *
 * @param array $discount Raw RobHub discount payload.
 * @return array|null Canonical discount data, or null when required fields are invalid.
 */
function ai4seo_normalize_synced_account_discount( array $discount ): ?array {
	$discount_name       = isset( $discount['name'] ) && is_string( $discount['name'] )
		? sanitize_key( $discount['name'] )
		: '';
	$discount_percentage = $discount['percentage'] ?? null;

	if ( '' === $discount_name
		|| ! is_numeric( $discount_percentage )
		|| 0 > $discount_percentage
		|| 100 < $discount_percentage ) {
		return null;
	}

	$normalized_discount = array(
		'name'       => $discount_name,
		'percentage' => (int) $discount_percentage,
	);

	foreach ( array( 'description', 'voucher_code' ) as $string_field ) {
		if ( isset( $discount[ $string_field ] ) && is_string( $discount[ $string_field ] ) ) {
			$normalized_discount[ $string_field ] = $discount[ $string_field ];
		}
	}

	$image_url = ai4seo_sanitize_remote_notification_image_url( $discount['image'] ?? null );

	if ( '' !== $image_url ) {
		$normalized_discount['image'] = $image_url;
	}

	foreach ( array( 'expire_in', 'min_num_missing_entries_condition' ) as $numeric_field ) {
		if ( isset( $discount[ $numeric_field ] ) && is_numeric( $discount[ $numeric_field ] ) ) {
			$numeric_value = max( 0, (int) $discount[ $numeric_field ] );

			// Match the persisted discount contract instead of creating a notice whose account state cannot be stored.
			if ( 'expire_in' === $numeric_field && 99999999 < $numeric_value ) {
				continue;
			}

			$normalized_discount[ $numeric_field ] = $numeric_value;
		}
	}

	foreach ( array( 'first_purchase_only', 'show_generic_coupon_text', 'show_buttons_row', 'is_permanent' ) as $boolean_field ) {
		if ( ! isset( $discount[ $boolean_field ] ) || ! is_scalar( $discount[ $boolean_field ] ) ) {
			continue;
		}

		$boolean_value = filter_var( $discount[ $boolean_field ], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

		if ( null !== $boolean_value ) {
			$normalized_discount[ $boolean_field ] = $boolean_value;
		}
	}

	// A hidden text fallback is valid only when a separately validated banner remains available.
	if ( ! isset( $normalized_discount['image'] )
		&& isset( $normalized_discount['show_generic_coupon_text'] )
		&& false === $normalized_discount['show_generic_coupon_text'] ) {
		$normalized_discount['show_generic_coupon_text'] = true;
	}

	return $normalized_discount;
}


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
		// A prior one-shot auto-retry request is local durable work and does not depend on this API call succeeding.
		if ( ! ai4seo_reconcile_required_auto_retry_failed() ) {
			ai4seo_debug_message( 85120826, 'Could not reconcile the pending automatic retry request during an unsuccessful account sync.', true );
		}

		ai4seo_debug_message( 451426226, 'Account sync failed or returned invalid data', true );
		return $api_response;
	}

	// Apply the validated response through the existing setting, state, and notification mechanisms.
	$synced_account_data = $api_response['data'];

	// Persist one-shot remote retry authorization before any destructive local queue mutation.
	$auto_retry_failed_was_requested = isset( $synced_account_data['auto_retry_failed'] ) && $synced_account_data['auto_retry_failed'];
	$auto_retry_failed_request_token = '';

	if ( $auto_retry_failed_was_requested ) {
		$persisted_request_token = ai4seo_persist_auto_retry_failed_required();

		if ( is_string( $persisted_request_token ) ) {
			$auto_retry_failed_request_token = $persisted_request_token;
		} else {
			ai4seo_debug_message( 86120826, 'Could not persist the remote automatic retry request before local reconciliation.', true );
		}
	}

	// Retry a newly requested or previously interrupted local reconciliation on every account-sync attempt.
	if ( ! ai4seo_reconcile_required_auto_retry_failed( $auto_retry_failed_request_token ) ) {
		ai4seo_debug_message( 87120826, 'Could not completely apply the durable automatic retry request during account sync.', true );
	}

	// Route every nonzero remote timestamp through the shared idempotent environmental writer.
	$remote_last_website_toc_and_pp_update_time = (int) ( $synced_account_data['last_terms_update_time'] ?? false );

	// Keep legal acceptance checks aligned with the latest terms timestamp supplied by RobHub.
	if ( 0 !== $remote_last_website_toc_and_pp_update_time ) {
		ai4seo_update_environmental_variable(
			AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME,
			$remote_last_website_toc_and_pp_update_time
		);
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

	// Distinguish an omitted status from a present malformed value before mutating local PAYG state.
	$ai4seo_has_payg_status       = array_key_exists( 'payg_status', $synced_account_data );
	$ai4seo_has_valid_payg_status = $ai4seo_has_payg_status
		&& is_string( $synced_account_data['payg_status'] )
		&& in_array( $synced_account_data['payg_status'], AI4SEO_ALLOWED_PAYG_STATUS, true );

	if ( $ai4seo_has_valid_payg_status ) {
		if ( isset( $synced_account_data['payg_failure_reason'] ) && is_string( $synced_account_data['payg_failure_reason'] ) ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON, sanitize_key( $synced_account_data['payg_failure_reason'] ) );
		} else {
			ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );
		}

		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS, sanitize_key( $synced_account_data['payg_status'] ) );

		ai4seo_check_for_payg_status_errors( $synced_account_data['payg_status'] );
	} elseif ( $ai4seo_has_payg_status ) {
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS );
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );
		ai4seo_remove_notification( 'payg-status-error' );
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
		$discount = ai4seo_normalize_synced_account_discount( $synced_account_data['discount'] );

		if ( null !== $discount ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT, $discount );
			ai4seo_check_discount_notification( $discount, $allow_notification_force );
		} else {
			ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT );
			ai4seo_remove_notification( 'discount' );
		}
	} else {
		ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT );
		ai4seo_remove_notification( 'discount' );
	}

	// Import account notifications through the dedicated remote ingestion policy.
	if ( isset( $synced_account_data['notifications'] ) && is_array( $synced_account_data['notifications'] ) ) {
		$notifications = $synced_account_data['notifications'];

		foreach ( $notifications as $notification_index => $notification ) {
			// Reject malformed API rows before their fields can reach the typed notification store.
			if ( ! is_array( $notification )
				|| ! isset( $notification['message'] )
				|| ! is_string( $notification['message'] )
				|| '' === trim( $notification['message'] ) ) {
				continue;
			}

			// Pass the visible message separately because remaining fields are notification metadata.
			$message = $notification['message'];
			unset( $notification['message'] );

			// Honor remote force flags only when this synchronization call explicitly permits them.
			$force = $allow_notification_force
				&& isset( $notification['force'] )
				&& (bool) $notification['force'];

			// Force controls replacement rather than stored metadata; provenance fields remain reserved by the shared store.
			unset( $notification['force'] );

			// The dedicated entry point applies the remote policy and prevents metadata from selecting local trust.
			ai4seo_push_remote_notification( $notification_index, $message, $force, $notification );
		}
	}

	return $api_response;
}


// endregion
// ___________________________________________________________________________________________.
