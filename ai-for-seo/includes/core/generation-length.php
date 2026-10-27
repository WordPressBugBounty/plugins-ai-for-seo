<?php
/**
 * Generation-length stages, subscription entitlement, and output-quality windows.
 *
 * @package AI_For_SEO
 */

// Keep domain policy inaccessible outside the WordPress runtime.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return whether a staged setting controls one generated field's target length.
 *
 * @param string $setting_name Setting name.
 * @return bool True when generation-length details exist.
 */
function ai4seo_is_generation_length_slider_setting( string $setting_name ): bool {
	return isset( AI4SEO_GENERATION_LENGTH_SETTING_DETAILS[ $setting_name ] );
}


/**
 * Return the declarative generation-length details for one setting.
 *
 * @param string $setting_name Setting name.
 * @return array<string, mixed>
 */
function ai4seo_get_generation_length_setting_details( string $setting_name ): array {
	// Centralize registry access so callers share the same safe fallback for unknown settings.
	$setting_details = AI4SEO_GENERATION_LENGTH_SETTING_DETAILS[ $setting_name ] ?? array();

	return is_array( $setting_details ) ? $setting_details : array();
}


/**
 * Return the minimum subscription plan for one generation-length stage.
 *
 * @param mixed $setting_value Stage value.
 * @return string Normalized plan identifier, or an empty string for a free stage.
 */
function ai4seo_get_generation_length_stage_minimum_plan( $setting_value ): string {
	// Canonical string keys keep this lookup aligned with saved radio values and API stages.
	$setting_value = is_string( $setting_value ) || is_int( $setting_value )
		? (string) $setting_value
		: '';

	return AI4SEO_GENERATION_LENGTH_STAGE_MINIMUM_PLANS[ $setting_value ] ?? '';
}


/**
 * Determine whether the synchronized subscription can use a paid length stage.
 *
 * @param string $required_plan Minimum required plan.
 * @return bool True for a non-expired subscription at or above the required plan.
 */
function ai4seo_user_has_active_generation_length_subscription( string $required_plan = 's' ): bool {
	global $ai4seo_user_has_active_generation_length_subscription;

	// Unknown and free requirements cannot unlock subscription-only generation stages.
	$required_plan = ai4seo_normalize_plan_identifier( $required_plan );

	if ( '' === $required_plan || 'free' === $required_plan ) {
		return false;
	}

	// Unverified facts cannot reuse an earlier paid entitlement from this request.
	$account = ai4seo_get_account_readiness();
	if ( ! $account['plan_ready'] ) {
		return false;
	}

	// Read the synchronized subscription directly because general plan helpers intentionally do not inspect expiry.
	$subscription        = $account['subscription'];
	$subscription_plan   = is_array( $subscription ) ? ai4seo_normalize_plan_identifier( $subscription['plan'] ?? 'free' ) : 'free';
	$subscription_end    = is_array( $subscription ) ? ( $subscription['subscription_end'] ?? '' ) : '';
	$subscription_expiry = is_numeric( $subscription_end ) ? (int) $subscription_end : strtotime( (string) $subscription_end );

	// Resolve every plan at once so later Basic and Pro checks do not repeat environmental reads or date parsing.
	$available_plan_identifiers = array_keys( ai4seo_get_available_plans() );
	$subscription_plan_index    = array_search( $subscription_plan, $available_plan_identifiers, true );
	$is_subscription_active     = false !== $subscription_expiry && $subscription_expiry > time();

	foreach ( $available_plan_identifiers as $plan_index => $plan_identifier ) {
		$ai4seo_user_has_active_generation_length_subscription[ $plan_identifier ] = 'free' !== $plan_identifier
			&& $is_subscription_active
			&& false !== $subscription_plan_index
			&& $subscription_plan_index >= $plan_index;
	}

	return (bool) ( $ai4seo_user_has_active_generation_length_subscription[ $required_plan ] ?? false );
}


/**
 * Determine whether the current account may use a generation-length stage.
 *
 * Options one through three are available to every account. Option four requires Basic and
 * Option five requires Pro.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Stage value.
 * @return bool True when the stage may be used.
 */
function ai4seo_user_can_use_generation_length_stage( string $setting_name, $setting_value ): bool {
	// Entitlement applies only to fields registered in the generation-length registry.
	if ( ! ai4seo_is_generation_length_slider_setting( $setting_name ) ) {
		return false;
	}

	// Reuse structural slider validation so entitlement cannot make malformed stages valid.
	if ( ! ai4seo_validate_prompt_slider_setting_value( $setting_name, $setting_value ) ) {
		return false;
	}

	$setting_value = (string) $setting_value;

	// Stages without a registry requirement remain available regardless of subscription state.
	$minimum_plan = ai4seo_get_generation_length_stage_minimum_plan( $setting_value );

	if ( '' === $minimum_plan ) {
		return true;
	}

	// Paid stages require both a non-expired subscription and their configured minimum tier.
	return ai4seo_user_has_active_generation_length_subscription( $minimum_plan );
}


/**
 * Resolve the generation-length stage currently allowed to affect generation.
 *
 * The stored value is intentionally left unchanged across subscription expiry. Invalid values and
 * unavailable paid stages use Option 2 so missing settings and older clients preserve prior behavior.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Optional raw value. The saved setting is read when omitted.
 * @return string Effective stage value.
 */
function ai4seo_get_effective_generation_length_stage( string $setting_name, $setting_value = null ): string {
	// Unknown settings cannot participate in generation contracts and use the legacy-equivalent fallback.
	if ( ! ai4seo_is_generation_length_slider_setting( $setting_name ) ) {
		return '2';
	}

	// Omitted values come from persistent settings; explicit values support rendering and request tests.
	if ( func_num_args() < 2 ) {
		$setting_value = ai4seo_get_setting( $setting_name );
	}

	// Missing or malformed stored/imported values preserve older-client behavior through Option 2.
	if ( ! ai4seo_validate_prompt_slider_setting_value( $setting_name, $setting_value ) ) {
		return '2';
	}

	$setting_value = (string) $setting_value;

	// Preserve the saved value separately while applying Option 2 to stages the current account cannot use.
	if ( ! ai4seo_user_can_use_generation_length_stage( $setting_name, $setting_value ) ) {
		return '2';
	}

	return $setting_value;
}


/**
 * Resolve the effective quality window for a generated field.
 *
 * @param string $context Generation context.
 * @param string $field_identifier Plugin field identifier.
 * @return array<string, int>
 */
function ai4seo_get_generation_length_quality_window( string $context, string $field_identifier ): array {
	// Normalize both lookup dimensions because editor and API identifiers use different separators.
	$context          = sanitize_key( $context );
	$field_identifier = sanitize_key( $field_identifier );

	// Resolve the setting that owns this field without maintaining a second reverse-lookup registry.
	foreach ( AI4SEO_GENERATION_LENGTH_SETTING_DETAILS as $setting_name => $setting_details ) {
		if ( ! is_array( $setting_details )
			|| ( $setting_details['context'] ?? '' ) !== $context
			|| ( $setting_details['field-identifier'] ?? '' ) !== $field_identifier ) {
			continue;
		}

		// Couple diagnostics to the same entitlement-aware stage transmitted to RobHub.
		$effective_stage = ai4seo_get_effective_generation_length_stage( $setting_name );
		$stages          = is_array( $setting_details['stages'] ?? null ) ? $setting_details['stages'] : array();
		$quality_window  = $stages[ $effective_stage ] ?? $stages['2'] ?? array();

		return is_array( $quality_window ) ? $quality_window : array();
	}

	$fixed_quality_window = AI4SEO_GENERATED_OUTPUT_QUALITY_WINDOWS[ $context ][ $field_identifier ] ?? array();

	// Fields without a configurable stage still use their declared natural-quality window.
	if ( ! is_array( $fixed_quality_window )
		|| ! isset( $fixed_quality_window['min-length'], $fixed_quality_window['max-length'] ) ) {
		return array();
	}

	return $fixed_quality_window;
}
