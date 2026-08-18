<?php
/**
 * Plugin settings storage and validation.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep shared settings behavior outside AJAX routing so every caller uses the same storage and validation rules.

/**
 * Retrieve all settings
 *
 * @return array
 */
function ai4seo_get_all_settings(): array {
	global $ai4seo_settings;
	return $ai4seo_settings;
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Retrieve all settings that may be exported/imported via JSON.
 *
 * @return array
 */
function ai4seo_get_all_exportable_settings(): array {
	return array_values(
		array_unique(
			array_merge(
				AI4SEO_EXPORTABLE_SETTING_PAGE_SETTINGS,
				AI4SEO_EXPORTABLE_SEO_AUTOPILOT_SETTINGS,
				AI4SEO_EXPORTABLE_ACCOUNT_PAGE_SETTINGS,
				AI4SEO_EXPORTABLE_GET_MORE_CREDITS_MODAL_SETTINGS
			)
		)
	);
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Retrieve all currently stored settings that are allowed to be exported.
 *
 * @return array
 */
function ai4seo_get_exportable_settings(): array {
	$ai4seo_all_settings        = ai4seo_get_all_settings();
	$ai4seo_exportable_settings = array();

	foreach ( ai4seo_get_all_exportable_settings() as $ai4seo_setting_name ) {
		if ( array_key_exists( $ai4seo_setting_name, $ai4seo_all_settings ) ) {
			$ai4seo_exportable_settings[ $ai4seo_setting_name ] = $ai4seo_all_settings[ $ai4seo_setting_name ];
		}
	}

	return $ai4seo_exportable_settings;
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Retrieve value of a setting
 *
 * @param string $setting_name The name of the setting.
 */
function ai4seo_get_setting( string $setting_name ) {
	global $ai4seo_settings;
	global $ai4seo_are_settings_initialized;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5, 99999 ) ) {
		ai4seo_debug_message( 739593453, 'Prevented loop', true );
		return '';
	}

	if ( ! $ai4seo_are_settings_initialized ) {
		ai4seo_init_settings();
	}

	if ( ! $ai4seo_are_settings_initialized ) {
		ai4seo_debug_message( 7122824, 'Settings are not initialized.', true );
		return '';
	}

	// Make sure that $setting_name-parameter has content.
	if ( ! $setting_name ) {
		ai4seo_debug_message( 8122824, 'Setting name is empty.', true );
		return '';
	}

	// Check if the $setting_name-parameter exists in settings-array.
	if ( ! isset( $ai4seo_settings[ $setting_name ] ) ) {
		return AI4SEO_DEFAULT_SETTINGS[ $setting_name ] ?? '';
	}

	return $ai4seo_settings[ $setting_name ];
}

// =========================================================================================== \\

/**
 * Return whether incognito mode is enabled.
 *
 * @return bool Whether incognito mode is enabled.
 */
function ai4seo_is_incognito_mode_enabled(): bool {
	// Keep this shared query loop-safe because it is called during settings initialization and access checks.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 176167926, 'Prevented loop', true );
		return false;
	}

	return (bool) ai4seo_get_setting( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE );
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Retrieve the configured default view mode for both entry editors.
 *
 * @return string Preview or editor.
 */
function ai4seo_get_editor_default_view_mode(): string {
	$view_mode = ai4seo_get_setting( AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE );

	// Invalid or legacy values fall back to the declared fresh-install default.
	if ( ! is_string( $view_mode ) || ! in_array( $view_mode, AI4SEO_EDITOR_VIEW_MODES, true ) ) {
		return AI4SEO_EDITOR_VIEW_MODE_PREVIEW;
	}

	return $view_mode;
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Retrieve translated editor view-mode options for settings controls.
 *
 * @return array<string, string> Mode labels keyed by stored value.
 */
function ai4seo_get_editor_view_mode_options(): array {
	// Keep this registry as the shared source for the setting and modal switch order.
	return array(
		AI4SEO_EDITOR_VIEW_MODE_PREVIEW => esc_html__( 'Preview', 'ai-for-seo' ),
		AI4SEO_EDITOR_VIEW_MODE_EDITOR  => esc_html__( 'Editor', 'ai-for-seo' ),
	);
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Determine whether a setting with a pre-2.4.4 default needs a compatibility value.
 *
 * @param string $last_known_plugin_version Previous installed plugin version.
 * @param array  $raw_settings              Settings stored before the update.
 * @param string $setting_name              Setting whose former default may need migration.
 * @return bool True when the former default must be stored explicitly.
 */
function ai4seo_should_migrate_pre_244_setting_default(
	string $last_known_plugin_version,
	array $raw_settings,
	string $setting_name
): bool {
	// Fresh installs and explicit stored choices must use the current declared defaults unchanged.
	return '' !== $last_known_plugin_version
		&& version_compare( $last_known_plugin_version, '2.4.3', '<=' )
		&& ! array_key_exists( $setting_name, $raw_settings );
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Determine whether an upgraded site needs the compatibility editor-mode default.
 *
 * @param string $last_known_plugin_version Previous installed plugin version.
 * @param array  $raw_settings              Settings stored before the update.
 * @return bool True when the compatibility value must be stored.
 */
function ai4seo_should_migrate_default_editor_view_mode(
	string $last_known_plugin_version,
	array $raw_settings
): bool {
	// Reuse the shared version and explicit-choice gate for the former editor-first default.
	return ai4seo_should_migrate_pre_244_setting_default(
		$last_known_plugin_version,
		$raw_settings,
		AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE
	);
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Determine whether an upgraded site needs the compatibility third-party SEO sync selection.
 *
 * @param string $last_known_plugin_version Previous installed plugin version.
 * @param array  $raw_settings              Settings stored before the update.
 * @return bool True when the legacy selection must be stored.
 */
function ai4seo_should_migrate_default_third_party_seo_plugin_sync(
	string $last_known_plugin_version,
	array $raw_settings
): bool {
	// Reuse the shared version and explicit-choice gate for the former synchronization selection.
	return ai4seo_should_migrate_pre_244_setting_default(
		$last_known_plugin_version,
		$raw_settings,
		AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS
	);
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Normalize supported representations for settings whose declared default is boolean.
 *
 * Invalid values remain unchanged so the setting validator can reject them instead of silently
 * converting arbitrary input to false.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Setting value.
 * @return mixed Normalized boolean or the unchanged value.
 */
function ai4seo_normalize_boolean_setting_value( string $setting_name, $setting_value ) {
	if (
		! array_key_exists( $setting_name, AI4SEO_DEFAULT_SETTINGS )
		|| ! is_bool( AI4SEO_DEFAULT_SETTINGS[ $setting_name ] )
	) {
		return $setting_value;
	}

	if ( is_bool( $setting_value ) ) {
		return $setting_value;
	}

	if ( is_int( $setting_value ) ) {
		if ( 1 === $setting_value ) {
			return true;
		}

		if ( 0 === $setting_value ) {
			return false;
		}

		return $setting_value;
	}

	if ( is_string( $setting_value ) ) {
		$normalized_setting_value = strtolower( trim( $setting_value ) );

		if ( 'true' === $normalized_setting_value || '1' === $normalized_setting_value ) {
			return true;
		}

		if ( 'false' === $normalized_setting_value || '0' === $normalized_setting_value ) {
			return false;
		}
	}

	return $setting_value;
}

// =========================================================================================== \\

/**
 * Update value a setting
 *
 * @param string $setting_name The setting name value.
 * @param mixed  $new_setting_value The new setting value value.
 * @return bool True if the setting was updated successfully, false if not
 */
function ai4seo_update_setting( string $setting_name, $new_setting_value ): bool {
	global $ai4seo_settings;
	global $ai4seo_are_settings_initialized;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 341531855, 'Prevented loop', true );
		return false;
	}

	if ( ! $ai4seo_are_settings_initialized ) {
		ai4seo_init_settings();
	}

	if ( ! $ai4seo_are_settings_initialized ) {
		ai4seo_debug_message( 5122824, 'Settings are not initialized.', true );
		return false;
	}

	// Normalize instruction settings before validation so direct updates and settings-page saves behave the same.
	$new_setting_value = ai4seo_normalize_custom_instructions_setting_value( $setting_name, $new_setting_value );

	// Normalize checkbox, API, and compatibility representations before strict boolean validation.
	$new_setting_value = ai4seo_normalize_boolean_setting_value( $setting_name, $new_setting_value );

	// Keep slider values as strings across form saves and imported integer values.
	$new_setting_value = ai4seo_normalize_prompt_slider_setting_value( $setting_name, $new_setting_value );

	// Make sure that the new value of the setting is valid.
	if ( ! ai4seo_validate_setting_value( $setting_name, $new_setting_value ) ) {
		ai4seo_debug_message( 9122824, 'Invalid setting value for setting "' . $setting_name . '"', true );
		return false;
	}

	// Compare against the effective default when a setting has not been stored explicitly yet.
	$current_setting_value = $ai4seo_settings[ $setting_name ] ?? AI4SEO_DEFAULT_SETTINGS[ $setting_name ] ?? '';

	// no change at all?
	if ( $current_setting_value == $new_setting_value ) {
		return true;
	}

	// Overwrite entry in $ai4seo_settings-array.
	$ai4seo_settings[ $setting_name ] = $new_setting_value;

	return ai4seo_push_local_setting_changes_to_database();
}

// =========================================================================================== \\

/**
 * Update values of given settings
 *
 * @param array $setting_changes An array of settings to update.
 * @return bool True if the setting was updated successfully, false if not
 */
function ai4seo_bulk_update_settings( array $setting_changes ): bool {
	global $ai4seo_settings;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 796348328, 'Prevented loop', true );
		return false;
	}

	$ai4seo_new_settings = $ai4seo_settings;

	foreach ( $setting_changes as $this_setting_name => $this_setting_value ) {
		// Normalize each instruction value before the batch is committed to the global settings array.
		$this_setting_value = ai4seo_normalize_custom_instructions_setting_value( $this_setting_name, $this_setting_value );

		// Keep bulk updates aligned with single-setting and form-save boolean normalization.
		$this_setting_value = ai4seo_normalize_boolean_setting_value( $this_setting_name, $this_setting_value );

		// Apply the same string normalization used by single-setting updates before validating the batch.
		$this_setting_value = ai4seo_normalize_prompt_slider_setting_value( $this_setting_name, $this_setting_value );

		// Make sure that the new value of the setting is valid.
		if ( ! ai4seo_validate_setting_value( $this_setting_name, $this_setting_value ) ) {
			ai4seo_debug_message( 40146824, 'Invalid setting value for setting "' . $this_setting_name . '"', true );
			return false;
		}

		// Overwrite entry in $ai4seo_settings-array.
		$ai4seo_new_settings[ $this_setting_name ] = $this_setting_value;
	}

	$ai4seo_settings = $ai4seo_new_settings;

	return ai4seo_push_local_setting_changes_to_database();
}

// =========================================================================================== \\

/**
 * Function to update the wp_options table with the current settings, by removing default values
 *
 * @return bool
 */
function ai4seo_push_local_setting_changes_to_database(): bool {
	global $ai4seo_settings;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 648276361, 'Prevented loop', true );
		return false;
	}

	$ai4seo_settings_copy = $ai4seo_settings;

	foreach ( $ai4seo_settings_copy as $ai4seo_setting_name => $ai4seo_setting_value ) {
		// if the setting is equal to the default setting, set it to null to prevent overhead.
		if ( isset( AI4SEO_DEFAULT_SETTINGS[ $ai4seo_setting_name ] ) && AI4SEO_DEFAULT_SETTINGS[ $ai4seo_setting_name ] == $ai4seo_setting_value ) {
			unset( $ai4seo_settings_copy[ $ai4seo_setting_name ] );
		}
	}

	// Save updated settings to database.
	return ai4seo_update_option( AI4SEO_SETTINGS_OPTION_NAME, $ai4seo_settings_copy, true );
}

// =========================================================================================== \\

/**
 * Validate prefix and suffix setting arrays.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Setting value.
 * @return bool True if the setting value is valid.
 */
function ai4seo_validate_prefix_suffix_setting_values( string $setting_name, $setting_value ): bool {
	if ( ! is_array( $setting_value ) ) {
		ai4seo_debug_message( 421728825, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
		return false;
	}

	foreach ( $setting_value as $key => $value ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $key ) ) {
			ai4seo_debug_message( 274714041, 'Invalid key in the prefix / suffix setting "' . $setting_name . '".', true );
			return false;
		}

		if ( ! is_string( $value ) ) {
			ai4seo_debug_message( 274714042, 'Invalid value in the prefix / suffix setting "' . $setting_name . '".', true );
			return false;
		}

		if ( ai4seo_mb_strlen( $value ) > 48 ) {
			ai4seo_debug_message( 274714045, 'Prefix / suffix value is too long for setting "' . $setting_name . '".', true );
			return false;
		}
	}

	return true;
}

// =========================================================================================== \\

/**
 * Returns whether the setting is backed by the staged prompt slider component.
 *
 * @param string $setting_name Setting name.
 * @return bool True when the setting is a prompt slider setting.
 */
function ai4seo_is_prompt_slider_setting( string $setting_name ): bool {
	return isset( AI4SEO_PROMPT_SLIDER_SETTING_STAGE_COUNTS[ $setting_name ] );
}

// =========================================================================================== \\

/**
 * Normalize a staged slider value while leaving unrelated setting types untouched.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Setting value.
 * @return mixed Normalized slider value or the original unrelated value.
 */
function ai4seo_normalize_prompt_slider_setting_value( string $setting_name, $setting_value ) {
	// Integer values can enter through imports, while settings-page radios already provide strings.
	if ( ai4seo_is_prompt_slider_setting( $setting_name ) && ( is_string( $setting_value ) || is_int( $setting_value ) ) ) {
		return (string) $setting_value;
	}

	return $setting_value;
}

// =========================================================================================== \\

/**
 * Return whether a staged setting controls one generated field's target length.
 *
 * @param string $setting_name Setting name.
 * @return bool True when generation-length details exist.
 */
function ai4seo_is_generation_length_slider_setting( string $setting_name ): bool {
	return isset( AI4SEO_GENERATION_LENGTH_SETTING_DETAILS[ $setting_name ] );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

	// Reuse the expiry-aware comparison for every slider rendered during this request.
	if ( isset( $ai4seo_user_has_active_generation_length_subscription[ $required_plan ] ) ) {
		return (bool) $ai4seo_user_has_active_generation_length_subscription[ $required_plan ];
	}

	// Read the synchronized subscription directly because general plan helpers intentionally do not inspect expiry.
	$robhub_api          = ai4seo_robhub_api();
	$subscription        = $robhub_api->read_environmental_variable( $robhub_api::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );
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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Validate a prompt slider stage value against the configured number of stages.
 *
 * Slider inputs save radio values as strings, but imported settings may supply integers.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Setting value.
 * @return bool True when the value is valid.
 */
function ai4seo_validate_prompt_slider_setting_value( string $setting_name, $setting_value ): bool {
	// Only settings registered in the shared stage-count map can use this validation path.
	if ( ! ai4seo_is_prompt_slider_setting( $setting_name ) ) {
		return false;
	}

	// Reuse storage normalization so validation treats imported integers exactly like saved radio strings.
	$setting_value = ai4seo_normalize_prompt_slider_setting_value( $setting_name, $setting_value );

	if ( ! is_string( $setting_value ) ) {
		return false;
	}

	// Require the exact registry key format so values such as "01" cannot resolve differently across the UI and API.
	if ( ! ctype_digit( $setting_value ) || (string) (int) $setting_value !== $setting_value ) {
		return false;
	}

	// The stage-count map defines the upper bound for each slider without duplicating switch cases.
	$stage_count = (int) AI4SEO_PROMPT_SLIDER_SETTING_STAGE_COUNTS[ $setting_name ];

	return (int) $setting_value >= 1
		&& (int) $setting_value <= $stage_count;
}

// =========================================================================================== \\

/**
 * Reads a raw boolean setting value from stored or imported data.
 *
 * Import payloads may contain string booleans while database settings usually contain real booleans.
 *
 * @param array  $raw_settings Raw settings.
 * @param string $setting_name Setting name.
 * @param bool   $fallback Fallback when the setting is missing or not scalar.
 * @return bool Normalized boolean value.
 */
function ai4seo_get_raw_boolean_setting_value( array $raw_settings, string $setting_name, bool $fallback = false ): bool {
	if ( ! array_key_exists( $setting_name, $raw_settings ) ) {
		return $fallback;
	}

	$raw_setting_value = $raw_settings[ $setting_name ];

	if ( is_bool( $raw_setting_value ) ) {
		return $raw_setting_value;
	}

	if ( ! is_scalar( $raw_setting_value ) ) {
		return $fallback;
	}

	// Invalid imported boolean strings should keep the migration fallback instead of acting like a disabled setting.
	$normalized_setting_value = filter_var( $raw_setting_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

	if ( null === $normalized_setting_value ) {
		return $fallback;
	}

	return $normalized_setting_value;
}

// =========================================================================================== \\

/**
 * Returns the pre-2.4.0 migration values for prompt slider settings.
 *
 * Older sites get stronger defaults to mirror the previous generation prompt behavior. Existing
 * reference strength is derived from the old boolean settings because those already represented
 * explicit user intent.
 *
 * @param array $raw_settings Raw settings as stored before current-version defaults are merged.
 * @return array Setting values keyed by setting name.
 */
function ai4seo_get_prompt_slider_setting_pre_240_migration_values( array $raw_settings ): array {
	// Preserve explicit legacy checkbox intent for the two sliders that replace visible booleans.
	$is_existing_metadata_reference_enabled              = ai4seo_get_raw_boolean_setting_value( $raw_settings, AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE );
	$is_existing_attachment_attributes_reference_enabled = ai4seo_get_raw_boolean_setting_value( $raw_settings, AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE );

	// Map the old enhanced entity checkbox into the new staged entity slider while keeping the legacy option stored.
	$is_enhanced_entity_recognition_enabled = ai4seo_get_raw_boolean_setting_value(
		$raw_settings,
		AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION,
		AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION ]
	);

	// Existing installations receive stage-four defaults so the pre-2.4.0 generation style is preserved after API wiring.
	return array(
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE => '4',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE => '4',
		AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH => $is_existing_metadata_reference_enabled ? '4' : '2',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH => $is_existing_attachment_attributes_reference_enabled ? '4' : '2',
		AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE => '4',
		AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY     => '4',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY => '4',
		AI4SEO_SETTING_METADATA_COMMERCIAL_TONE           => '4',
		AI4SEO_SETTING_METADATA_SOCIAL_VARIATION          => '4',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION => $is_enhanced_entity_recognition_enabled ? '4' : '1',
		AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE => '4',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE => '4',
		AI4SEO_SETTING_METADATA_TONE_VARIANT              => '4',
		AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT => '4',
	);
}

// =========================================================================================== \\

/**
 * Validate value of a setting
 *
 * @param string $setting_name The setting name value.
 * @param mixed  $setting_value The setting value value.
 * @return bool True if the value is valid, false if not
 */
function ai4seo_validate_setting_value( string $setting_name, $setting_value ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 596454676, 'Prevented loop', true );
		return false;
	}

	// Prompt slider settings share the same numeric radio-value validation rules.
	if ( ai4seo_is_prompt_slider_setting( $setting_name ) ) {
		return ai4seo_validate_prompt_slider_setting_value( $setting_name, $setting_value );
	}

	switch ( $setting_name ) {
		case AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE:
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_EDITOR_VIEW_MODES, true );

		case AI4SEO_SETTING_BULK_GENERATION_DURATION:
			// cast to int.
			$setting_value = (int) $setting_value;

			// integer between 10 and 300.
			return $setting_value >= 10 && $setting_value <= 300;

		case AI4SEO_SETTING_META_TAG_OUTPUT_MODE:
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_AVAILABLE_META_TAG_OUTPUT_MODE_OPTIONS, true );

		case AI4SEO_SETTING_DEBUG_OUTPUT_MODE:
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_AVAILABLE_DEBUG_OUTPUT_MODE_OPTIONS, true );

		case AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE:
		case AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION:
		case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE:
		case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION:
		case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE:
		case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION:
			$fallback_to_this_metadata_identifier = ai4seo_get_fallback_metadata_identifier_by_setting_name( $setting_name );

			if ( ! $fallback_to_this_metadata_identifier ) {
				return false;
			}

			$allowed_fallback_values = ai4seo_get_metadata_fallback_allowed_value_identifiers( $fallback_to_this_metadata_identifier );

			return is_string( $setting_value ) && in_array( $setting_value, $allowed_fallback_values, true );

		case AI4SEO_SETTING_ALLOWED_USER_ROLES:
			// Make sure that the new setting-value is an array.
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 45146824, 'Invalid setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			$allowed_user_roles            = ai4seo_get_all_possible_user_roles();
			$allowed_user_role_identifiers = array_keys( $allowed_user_roles );

			// check if all values are proper user roles.
			foreach ( $setting_value as $user_role_identifier ) {
				if ( ! in_array( $user_role_identifier, $allowed_user_role_identifiers ) ) {
					ai4seo_debug_message( 44146824, 'Invalid user role in the allowed user roles.', true );
					return false;
				}
			}

			// Make sure that the administrator-role exists in the array.
			if ( ! in_array( 'administrator', $setting_value ) ) {
				ai4seo_debug_message( 43146824, 'Administrator role is missing in the allowed user roles', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_DISABLED_POST_TYPES:
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 311815240, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			foreach ( $setting_value as $post_type ) {
				if ( ! is_string( $post_type ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $post_type ) ) {
					ai4seo_debug_message( 321815240, 'Invalid post type in the disabled post types setting.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_DISABLED_POST_AUTHORS:
		case AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS:
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 331815240, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			foreach ( $setting_value as $post_author_id ) {
				$post_author_id = (string) $post_author_id;

				if ( ! ctype_digit( $post_author_id ) || (int) $post_author_id <= 0 ) {
					ai4seo_debug_message( 341815240, 'Invalid post author in the disabled post authors setting.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES:
		case AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES:
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 391815240, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			foreach ( $setting_value as $wpml_language_code ) {
				if ( ! is_string( $wpml_language_code ) || '' === $wpml_language_code || sanitize_key( $wpml_language_code ) !== $wpml_language_code ) {
					ai4seo_debug_message( 391815241, 'Invalid WPML language in disabled WPML languages setting.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS:
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 351815240, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			foreach ( $setting_value as $taxonomy_name => $taxonomy_term_ids ) {
				$taxonomy_name = sanitize_key( $taxonomy_name );

				if ( '' === $taxonomy_name ) {
					ai4seo_debug_message( 361815240, 'Invalid taxonomy in the disabled taxonomy terms setting.', true );
					return false;
				}

				if ( ! is_array( $taxonomy_term_ids ) ) {
					ai4seo_debug_message( 371815240, 'Invalid taxonomy term list in the disabled taxonomy terms setting.', true );
					return false;
				}

				foreach ( $taxonomy_term_ids as $taxonomy_term_id ) {
					$taxonomy_term_id = (string) $taxonomy_term_id;

					if ( ! ctype_digit( $taxonomy_term_id ) || (int) $taxonomy_term_id <= 0 ) {
						ai4seo_debug_message( 381815240, 'Invalid taxonomy term in the disabled taxonomy terms setting.', true );
						return false;
					}
				}
			}

			return true;

		case AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES:
			// Make sure that the new setting-value is an array.
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 1188824, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			// Make sure the keys consist of alphanumeric strings, with - and _ allowed and the values should be "1" or "0" only.
			foreach ( $setting_value as $value ) {
				if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $value ) ) {
					ai4seo_debug_message( 2188824, 'Invalid value in the enabled bulk generations post types setting.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_BULK_GENERATION_ORDER:
			if ( ! defined( 'AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS' ) || ! in_array( $setting_value, AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS ) ) {
				ai4seo_debug_message( 2911171224, 'Invalid value in the bulk generations order setting "' . $setting_value . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER:
			if ( ! defined( 'AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS' ) || ! in_array( $setting_value, AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS ) ) {
				ai4seo_debug_message( 3211171224, 'Invalid value in the automated generations new or existing filter setting.', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS:
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 161523924, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

			$allowed_third_party_seo_plugin_identifier = array_keys( $third_party_seo_plugin_details );

			foreach ( $setting_value as $key => $value ) {
				if ( ! is_string( $value ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $value ) ) {
					ai4seo_debug_message( 171523924, 'Invalid value in the apply changes to third party seo plugin setting.', true );
					return false;
				}

				if ( ! in_array( $value, $allowed_third_party_seo_plugin_identifier ) ) {
					ai4seo_debug_message( 181523924, 'Invalid third party seo plugin name in the apply changes to third party seo plugin setting.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE:
			// Make sure that the new setting-value is a string.
			if ( ! is_string( $setting_value ) ) {
				ai4seo_debug_message( 261016824, 'Setting value for setting "' . $setting_name . '" is not a string.', true );
				return false;
			}

			// Make sure that the new setting-value is a valid language.
			if ( 'auto' !== $setting_value && ! in_array( $setting_value, AI4SEO_AVAILABLE_GENERATION_LANGUAGE_OPTIONS, true ) ) {
				ai4seo_debug_message( 271016824, 'Invalid language in the generation language setting: "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_ACTIVE_META_TAGS:
		case AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA:
		case AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA:
			// Make sure that the new setting-value is an array.
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 421728824, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			// Make sure that the new setting-value is a valid meta tag.
			foreach ( $setting_value as $meta_tag ) {
				if ( ! in_array( $meta_tag, AI4SEO_AVAILABLE_METADATA_IDENTIFIERS, true ) ) {
					ai4seo_debug_message( 431728824, 'Invalid meta tag in the visible meta tags setting: "' . $setting_name . '"', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES:
		case AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES:
			// Make sure that the new setting-value is an array.
			if ( ! is_array( $setting_value ) ) {
				ai4seo_debug_message( 101424924, 'Setting value for setting "' . $setting_name . '" is not an array.', true );
				return false;
			}

			// Make sure that the new setting-value is a valid attachment attribute.
			foreach ( $setting_value as $attachment_attribute ) {
				if ( ! in_array( $attachment_attribute, AI4SEO_AVAILABLE_ATTACHMENT_ATTRIBUTE_IDENTIFIERS, true ) ) {
					ai4seo_debug_message( 111424924, 'Invalid attachment attribute in the overwrite existing attachment attributes setting: "' . $setting_name . '" is not an array.', true );
					return false;
				}
			}

			return true;

		case AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS:
			return in_array( $setting_value, array( 'show', 'hide' ) );

		case AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES:
		case AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES:
		case AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES:
		case AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION:
		case AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION:
		case AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS:
		case AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE:
		case AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM:
		case AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE:
		case AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE:
		case AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION:
		case AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION:
		case AI4SEO_SETTING_ENABLE_NATIVE_BULK_ACTIONS:
		case AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS:
		case AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS:
		case AI4SEO_SETTING_ENABLE_INCOGNITO_MODE:
		case AI4SEO_SETTING_ENABLE_WHITE_LABEL:
		case AI4SEO_SETTING_ADD_GENERATOR_HINTS:
			// Boolean settings reach validation after their callers have normalized supported input representations.
			return is_bool( $setting_value );

		case AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES:
			if ( ! is_bool( $setting_value ) ) {
				return false;
			}

			if ( $setting_value && ! ai4seo_is_deep_context_search_supported_for_current_site() ) {
				ai4seo_debug_message( 385825161, 'Deep context search for images cannot be enabled on unsupported websites.', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA:
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_AVAILABLE_INCLUDE_PRODUCT_PRICE_IN_METADATA_OPTIONS, true );

		case AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA:
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_AVAILABLE_FOCUS_KEYPHRASE_BEHAVIOR_OPTIONS, true );

		case AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE:
			// check for valid allowed value.
			return is_string( $setting_value )
				&& in_array( $setting_value, AI4SEO_AVAILABLE_IMAGE_TITLE_INJECTION_MODE_OPTIONS, true );

		case AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS:
			// Delegate instruction settings to the shared validator so imports and saves share the same rules.
			return ai4seo_validate_custom_instructions_setting_value( $setting_name, $setting_value );

		case AI4SEO_SETTING_METADATA_PREFIXES:
		case AI4SEO_SETTING_METADATA_SUFFIXES:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES:
			return ai4seo_validate_prefix_suffix_setting_values( $setting_name, $setting_value );

		case AI4SEO_SETTING_INCOGNITO_MODE_USER_ID:
			// Make sure that setting-value is 0 or numeric.
			if ( '0' !== $setting_value && ! is_numeric( $setting_value ) ) {
				ai4seo_debug_message( 385825155, 'Invalid value for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME:
			// default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_PLUGIN_NAME] if not set.
			if ( ! $setting_value ) {
				$setting_value = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME ];
			}

			if ( ! is_string( $setting_value ) || ai4seo_mb_strlen( $setting_value ) < 3 || ai4seo_mb_strlen( $setting_value ) > 100 ) {
				ai4seo_debug_message( 385825156, 'Invalid value in the plugin-name for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION:
			// default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_PLUGIN_DESCRIPTION] if not set.
			if ( ! $setting_value ) {
				$setting_value = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION ];
			}

			if ( ! is_string( $setting_value ) || ai4seo_mb_strlen( $setting_value ) < 3 || ai4seo_mb_strlen( $setting_value ) > 140 ) {
				ai4seo_debug_message( 385825157, 'Invalid value in the plugin-description for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT:
			// default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_SOURCE_CODE_NOTES_CONTENT_START] if not set.
			if ( ! $setting_value ) {
				$setting_value = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT ];
			}

			if ( ! is_string( $setting_value ) || ai4seo_mb_strlen( $setting_value ) < 3 || ai4seo_mb_strlen( $setting_value ) > 250 ) {
				ai4seo_debug_message( 385825159, 'Invalid value in the source-code-notes-content-start for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT:
			// default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_SOURCE_CODE_NOTES_CONTENT_END] if not set.
			if ( ! $setting_value ) {
				$setting_value = AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT ];
			}

			if ( ! is_string( $setting_value ) || ai4seo_mb_strlen( $setting_value ) < 3 || ai4seo_mb_strlen( $setting_value ) > 250 ) {
				ai4seo_debug_message( 385825160, 'Invalid value in the source-code-notes-content-end for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_PREFERRED_CURRENCY:
			$allowed_currencies = ai4seo_get_allowed_currencies();

			if ( ! in_array( strtoupper( $setting_value ), $allowed_currencies ) ) {
				ai4seo_debug_message( 341016325, 'Invalid currency for setting "' . $setting_name . '"', true );
				return false;
			}

			return true;

		case AI4SEO_SETTING_PAYG_ENABLED:
			return is_bool( $setting_value );

		case AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID:
			// allow empty value for the setting, which means that the user is not using a credits pack.
			if ( '' === $setting_value ) {
				return true;
			}

			// any string starting with "price_" is allowed here, but we double-check with the available credits packs.
			return preg_match( '/^price_[a-zA-Z0-9]+$/', $setting_value );

		case AI4SEO_SETTING_PAYG_DAILY_BUDGET:
		case AI4SEO_SETTING_PAYG_MONTHLY_BUDGET:
			return is_numeric( $setting_value ) && $setting_value >= 0;

		case AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE:
			$setting_value = (int) $setting_value;

			return in_array( $setting_value, AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS, true );

		case AI4SEO_SETTING_IMAGE_UPLOAD_METHOD:
			$allowed_values = array( 'auto', 'url', 'base64' );
			return in_array( $setting_value, $allowed_values );

		default:
			return false;
	}
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the debug output mode setting.
 *
 * @return array
 */
function ai4seo_get_debug_output_mode_options(): array {
	return array(
		'none'      => esc_html__( 'Disable debug output', 'ai-for-seo' ),
		'error_log' => esc_html__( 'Send to PHP/WP debug log (respects WP_DEBUG_LOG)', 'ai-for-seo' ),
		'file'      => esc_html__( 'Write to uploads/ai-for-seo-debug.log', 'ai-for-seo' ),
		'database'  => esc_html__( "Store in the database (see 'Debug message log' below)", 'ai-for-seo' ),
		'notice'    => esc_html__( 'Show as an admin notice', 'ai-for-seo' ),
		'print_r'   => esc_html__( 'Display inline', 'ai-for-seo' ),
	);
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the setting for the meta tag output modes
 *
 * @return array return the allowed values for the setting for the meta tag output modes
 */
function ai4seo_get_setting_meta_tag_output_mode_allowed_values(): array {
	return array(
		'disable'    => sprintf(
			/* translators: %s: plugin name */
			esc_html__( "Disable '%s' Meta Tags", 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME )
		),
		'force'      => sprintf(
			/* translators: %s: plugin name */
			esc_html__( "Force '%s' Meta Tags", 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME )
		),
		'replace'    => esc_html__( 'Replace Existing Meta Tags', 'ai-for-seo' ),
		'complement' => esc_html__( 'Complement Existing Meta Tags', 'ai-for-seo' ),
	);
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the render level image title injection setting
 *
 * @return array return the allowed values for the render level image title injection setting
 */
function ai4seo_get_setting_render_level_title_injection_allowed_values(): array {
	return array(
		'disabled'           => esc_html__( 'Disabled', 'ai-for-seo' ),
		'inject_title'       => esc_html__( 'Inject image title', 'ai-for-seo' ),
		'inject_alt_text'    => esc_html__( 'Inject alt text', 'ai-for-seo' ),
		'inject_caption'     => esc_html__( 'Inject caption', 'ai-for-seo' ),
		'inject_description' => esc_html__( 'Inject image description', 'ai-for-seo' ),
	);
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the WooCommerce price inclusion setting.
 *
 * @return array
 */
function ai4seo_get_setting_include_product_price_in_metadata_allowed_values(): array {
	return array(
		'never'   => esc_html__( 'Never include WooCommerce price', 'ai-for-seo' ),
		'fixed'   => esc_html__( 'Fixed price (store current amount)', 'ai-for-seo' ),
		'dynamic' => esc_html__( 'Dynamic placeholder (updates at render time)', 'ai-for-seo' ),
	);
}

// =========================================================================================== \\

/**
 * Returns the options for the Focus Keyphrase behavior when metadata already exists.
 *
 * @return array
 */
function ai4seo_get_focus_keyphrase_behavior_options(): array {
	return array(
		AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP               => esc_html__( 'Skip focus keyphrase generation', 'ai-for-seo' ),
		AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_GENERATE_KEYPHRASE => esc_html__( 'Generate focus keyphrase only', 'ai-for-seo' ),
		AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE         => esc_html__( 'Regenerate metadata (recommended)', 'ai-for-seo' ),
	);
}


// =========================================================================================== \

/**
 * Returns the options for query ID chunk sizes used for batched database lookups.
 *
 * @return array
 */
function ai4seo_get_query_ids_chunk_size_options(): array {
	$options = array();

	foreach ( AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS as $chunk_size ) {
		$chunk_size = (int) $chunk_size;

		if ( $chunk_size <= 0 ) {
			continue;
		}

		$options[ $chunk_size ] = ai4seo_format_number_i18n( $chunk_size );
	}

	return $options;
}

// =========================================================================================== \

/**
 * Returns the validated query ID chunk size setting.
 *
 * @return int
 */
function ai4seo_get_database_chunk_size(): int {
	$chunk_size = (int) ai4seo_get_setting( AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE );

	if ( ! in_array( $chunk_size, AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS, true ) ) {
		$chunk_size = (int) AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE ];
	}

	return $chunk_size;
}

// endregion
// ___________________________________________________________________________________________.
