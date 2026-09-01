<?php
/**
 * AJAX handler for validating and importing settings file.
 *
 * @package AI_For_SEO
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! ai4seo_can_administer_plugin() ) {
	return;
}

// Recheck the global AJAX nonce before handling this protected admin request.
if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 10291824 );
	return;
}

global $ai4seo_settings;

$ai4seo_import_settings_request = $_REQUEST;

// Import payloads are transported as JSON so empty arrays from exported settings are not dropped by form encoding.
if ( isset( $_REQUEST['ai4seo_import_settings_payload'] ) ) {
	// The import JSON is decoded first so exported text values are not altered before the settings validator runs.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The decoded settings array is sanitized before use.
	$ai4seo_raw_import_settings_payload      = wp_unslash( $_REQUEST['ai4seo_import_settings_payload'] );
	$ai4seo_import_settings_payload_encoding = sanitize_key( wp_unslash( (string) ( $_REQUEST['ai4seo_import_settings_payload_encoding'] ?? '' ) ) );

	if ( ! is_string( $ai4seo_raw_import_settings_payload ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The submitted import settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ), 10291825 );
	}

	if ( 'base64_json' === $ai4seo_import_settings_payload_encoding ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Strict decoding preserves the declared JSON transport and rejects malformed input.
		$ai4seo_raw_import_settings_payload = base64_decode( $ai4seo_raw_import_settings_payload, true );

		if ( ! is_string( $ai4seo_raw_import_settings_payload ) ) {
			ai4seo_send_ajax_error( esc_html__( 'The submitted import settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ), 11291825 );
		}
	}

	$ai4seo_import_settings_payload = json_decode( $ai4seo_raw_import_settings_payload, true );

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $ai4seo_import_settings_payload ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The submitted import settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ), 12291825 );
	}

	$ai4seo_import_settings_request = array_merge( $ai4seo_import_settings_request, $ai4seo_import_settings_payload );
}

$ai4seo_all_categories = array(
	'settings',
	'seo_autopilot',
	'account',
	'get_more_credits',
);

// get mode (preview or execute).
if ( ! isset( $ai4seo_import_mode ) ) {
	$ai4seo_import_mode = isset( $ai4seo_import_settings_request['ai4seo_import_mode'] ) && 'execute' === $ai4seo_import_settings_request['ai4seo_import_mode'] ? 'execute' : 'preview';
}

// get categories to import.
$ai4seo_import_categories = isset( $ai4seo_import_settings_request['ai4seo_import_categories'] ) ? ai4seo_deep_sanitize( wp_unslash( (array) $ai4seo_import_settings_request['ai4seo_import_categories'] ) ) : array();

if ( ! $ai4seo_import_categories ) {
	ai4seo_send_ajax_error( esc_html__( 'No categories selected for import.', 'ai-for-seo' ), 4196725 );
}

// get source plugin version to apply compatibility migrations during cross-version imports.
$ai4seo_import_plugin_version = isset( $ai4seo_import_settings_request['ai4seo_import_plugin_version'] ) ? sanitize_text_field( wp_unslash( (string) $ai4seo_import_settings_request['ai4seo_import_plugin_version'] ) ) : '';

// get new settings to import.
$ai4seo_new_settings_raw = isset( $ai4seo_import_settings_request['ai4seo_new_settings'] ) ? ai4seo_deep_sanitize( wp_unslash( (array) $ai4seo_import_settings_request['ai4seo_new_settings'] ) ) : array();

if ( ! $ai4seo_new_settings_raw ) {
	ai4seo_send_ajax_error( esc_html__( 'No settings data provided for import.', 'ai-for-seo' ), 4296725 );
}


// ___________________________________________________________________________________________ \\
// === FUNCTIONS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Return the import category for a setting name.
 *
 * @param mixed $setting_name Setting identifier.
 * @return string Import category identifier.
 */
function ai4seo_get_setting_category( $setting_name ): string {
	// Non-string import keys cannot match the canonical setting registries.
	if ( ! is_string( $setting_name ) ) {
		return 'unknown';
	}

	if ( in_array( $setting_name, AI4SEO_EXPORTABLE_SETTING_PAGE_SETTINGS, true ) ) {
		return 'settings';
	}

	if ( in_array( $setting_name, AI4SEO_EXPORTABLE_SEO_AUTOPILOT_SETTINGS, true ) ) {
		return 'seo_autopilot';
	}

	if ( in_array( $setting_name, AI4SEO_EXPORTABLE_ACCOUNT_PAGE_SETTINGS, true ) ) {
		return 'account';
	}

	if ( in_array( $setting_name, AI4SEO_EXPORTABLE_GET_MORE_CREDITS_MODAL_SETTINGS, true ) ) {
		return 'get_more_credits';
	}

	return 'unknown';
}


/**
 * Convert a setting identifier to a readable label.
 *
 * @param string $setting_name Setting identifier.
 * @return string Readable setting label.
 */
function ai4seo_make_nicer_setting_name( $setting_name ): string {
	// Convert setting name to a more readable format.
	$setting_name = str_replace( '_', ' ', $setting_name );
	$setting_name = ucwords( $setting_name );
	return $setting_name;
}


/**
 * Format a setting value for the import preview.
 *
 * @param mixed $setting_value Setting value to format.
 * @return string Readable setting value.
 */
function ai4seo_make_nicer_setting_value( $setting_value ): string {
	// Import previews can contain nested setting arrays, so format each level before output escaping.
	if ( is_array( $setting_value ) ) {
		if ( ! $setting_value ) {
			return esc_html__( 'None', 'ai-for-seo' );
		}

		// Keep associative keys visible so nested settings like disabled taxonomy terms remain understandable.
		$formatted_values             = array();
		$setting_value_has_named_keys = ( array_keys( $setting_value ) !== range( 0, count( $setting_value ) - 1 ) );

		foreach ( $setting_value as $key => $value ) {
			$formatted_value = ai4seo_make_nicer_setting_value( $value );

			if ( $setting_value_has_named_keys ) {
				$formatted_values[] = htmlspecialchars( (string) $key, ENT_QUOTES, 'UTF-8' ) . ': ' . $formatted_value;
			} else {
				$formatted_values[] = $formatted_value;
			}
		}

		return implode( ', ', $formatted_values );
	} elseif ( is_bool( $setting_value ) || is_null( $setting_value ) ) {
		return $setting_value ? esc_html__( 'Yes', 'ai-for-seo' ) : esc_html__( 'No', 'ai-for-seo' );
	} else {
		return htmlspecialchars( (string) $setting_value, ENT_QUOTES, 'UTF-8' );
	}
}


// ___________________________________________________________________________________________ \\
// === IMPORT VALUE HELPERS =================================================================== \\

/**
 * Converts disabled stored values into the active values submitted by UI checkboxes.
 *
 * @param array $available_values Available values on this website.
 * @param mixed $disabled_values Stored disabled values from the import file.
 * @param bool  $use_integer_values Whether values should be compared as integers.
 * @return array Active values for save-anything.
 */
function ai4seo_get_active_selection_from_disabled_values( array $available_values, $disabled_values, bool $use_integer_values = false ): array {
	// Import files store disabled selections, while save-settings expects the active checkbox values.
	if ( ! is_array( $disabled_values ) ) {
		$disabled_values = $disabled_values ? array( $disabled_values ) : array();
	}

	// Match the comparison type used by the target setting so array_diff mirrors save-settings normalization.
	if ( $use_integer_values ) {
		$available_values = array_map( 'intval', $available_values );
		$disabled_values  = array_map( 'intval', $disabled_values );
	} else {
		$available_values = array_map( 'strval', $available_values );
		$disabled_values  = array_map( 'strval', $disabled_values );
	}

	// Only current-site available values are kept, so stale imported entities stay dropped by the normal save path.
	$available_values = array_values( array_unique( $available_values ) );
	$disabled_values  = array_values( array_unique( $disabled_values ) );

	return array_values( array_diff( $available_values, $disabled_values ) );
}


/**
 * Converts disabled taxonomy terms from import storage into active UI checkbox selections.
 *
 * @param mixed $disabled_taxonomy_terms Stored disabled taxonomy terms from the import file.
 * @return array Active taxonomy terms for save-anything.
 */
function ai4seo_get_active_taxonomy_terms_from_disabled_taxonomy_terms( $disabled_taxonomy_terms ): array {
	// Invalid shapes have already failed validation; this fallback keeps helper calls defensive.
	if ( ! is_array( $disabled_taxonomy_terms ) ) {
		$disabled_taxonomy_terms = array();
	}

	// Use the current site's supported terms so imports cannot resurrect deleted or unsupported terms.
	$ai4seo_supported_taxonomy_terms = ai4seo_get_supported_taxonomy_terms();
	$ai4seo_active_taxonomy_terms    = array();

	foreach ( $ai4seo_supported_taxonomy_terms as $ai4seo_this_taxonomy_name => $ai4seo_this_supported_taxonomy ) {
		$ai4seo_supported_term_ids   = array_map( 'intval', array_keys( $ai4seo_this_supported_taxonomy['terms'] ?? array() ) );
		$ai4seo_disabled_term_ids    = $disabled_taxonomy_terms[ $ai4seo_this_taxonomy_name ] ?? array();
		$ai4seo_this_active_term_ids = ai4seo_get_active_selection_from_disabled_values(
			$ai4seo_supported_term_ids,
			$ai4seo_disabled_term_ids,
			true
		);

		// Omitted taxonomy keys mean "no active terms" to the existing save-settings normalizer.
		if ( $ai4seo_this_active_term_ids ) {
			$ai4seo_active_taxonomy_terms[ $ai4seo_this_taxonomy_name ] = $ai4seo_this_active_term_ids;
		}
	}

	return $ai4seo_active_taxonomy_terms;
}


/**
 * Converts import values from stored setting format into save-anything's UI input format.
 *
 * @param array $setting_changes Setting changes keyed by setting name.
 * @return array Save-anything-compatible setting changes.
 */
function ai4seo_prepare_import_settings_for_save_anything( array $setting_changes ): array {
	foreach ( $setting_changes as $ai4seo_this_setting_name => $ai4seo_this_setting_value ) {
		// Only settings whose UI submits active selections need import-time conversion.
		switch ( $ai4seo_this_setting_name ) {
			case AI4SEO_SETTING_DISABLED_POST_TYPES:
				// Post type settings store disabled types but the settings page submits active types.
				$setting_changes[ $ai4seo_this_setting_name ] = ai4seo_get_active_selection_from_disabled_values(
					ai4seo_get_supported_post_types( false ),
					$ai4seo_this_setting_value
				);
				break;

			case AI4SEO_SETTING_DISABLED_POST_AUTHORS:
				// Author settings use integer IDs, so convert by current available author IDs before save-anything.
				$setting_changes[ $ai4seo_this_setting_name ] = ai4seo_get_active_selection_from_disabled_values(
					array_keys( ai4seo_get_available_post_authors() ),
					$ai4seo_this_setting_value,
					true
				);
				break;

			case AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS:
				// Media author settings follow the same active-checkbox contract as post authors.
				$setting_changes[ $ai4seo_this_setting_name ] = ai4seo_get_active_selection_from_disabled_values(
					array_keys( ai4seo_get_available_attachment_post_authors() ),
					$ai4seo_this_setting_value,
					true
				);
				break;

			case AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES:
			case AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES:
				// WPML language settings are saved through the shared active-language normalizer.
				$setting_changes[ $ai4seo_this_setting_name ] = ai4seo_get_active_selection_from_disabled_values(
					array_keys( ai4seo_get_available_wpml_languages() ),
					$ai4seo_this_setting_value
				);
				break;

			case AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS:
				// Taxonomy terms are nested by taxonomy name and need the same disabled-to-active conversion per group.
				$setting_changes[ $ai4seo_this_setting_name ] = ai4seo_get_active_taxonomy_terms_from_disabled_taxonomy_terms( $ai4seo_this_setting_value );
				break;
		}
	}

	return $setting_changes;
}


/**
 * Returns the preview label for settings whose stored name is inverted from the UI label.
 *
 * @param string $setting_name Setting name.
 * @return string Preview label.
 */
function ai4seo_get_import_setting_preview_name( string $setting_name ): string {
	switch ( $setting_name ) {
		case AI4SEO_SETTING_DISABLED_POST_TYPES:
			return esc_html__( 'Active Post Types', 'ai-for-seo' );

		case AI4SEO_SETTING_DISABLED_POST_AUTHORS:
			return esc_html__( 'Active Authors', 'ai-for-seo' );

		case AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS:
			return esc_html__( 'Active Media Authors', 'ai-for-seo' );

		case AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES:
			return esc_html__( 'Active Metadata WPML Languages', 'ai-for-seo' );

		case AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES:
			return esc_html__( 'Active Media WPML Languages', 'ai-for-seo' );

		case AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS:
			return esc_html__( 'Active Categories (taxonomy terms)', 'ai-for-seo' );
	}

	return ai4seo_make_nicer_setting_name( $setting_name );
}


/**
 * Converts stored disabled values into the active values admins expect in the import preview.
 *
 * @param string $setting_name Setting name.
 * @param mixed  $setting_value Stored setting value.
 * @return mixed Preview setting value.
 */
function ai4seo_get_import_setting_preview_value( string $setting_name, $setting_value ) {
	switch ( $setting_name ) {
		case AI4SEO_SETTING_DISABLED_POST_TYPES:
			return ai4seo_get_active_selection_from_disabled_values(
				ai4seo_get_supported_post_types( false ),
				$setting_value
			);

		case AI4SEO_SETTING_DISABLED_POST_AUTHORS:
			return ai4seo_get_active_selection_from_disabled_values(
				array_keys( ai4seo_get_available_post_authors() ),
				$setting_value,
				true
			);

		case AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS:
			return ai4seo_get_active_selection_from_disabled_values(
				array_keys( ai4seo_get_available_attachment_post_authors() ),
				$setting_value,
				true
			);

		case AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES:
		case AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES:
			return ai4seo_get_active_selection_from_disabled_values(
				array_keys( ai4seo_get_available_wpml_languages() ),
				$setting_value
			);

		case AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS:
			return ai4seo_get_active_taxonomy_terms_from_disabled_taxonomy_terms( $setting_value );
	}

	return $setting_value;
}


// ___________________________________________________________________________________________ \\
// === COMPATIBILITY MIGRATIONS =============================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Add missing prompt-slider settings when importing settings exported before those controls existed.
$ai4seo_has_imported_prompt_slider_settings         = ! empty( array_intersect_key( $ai4seo_new_settings_raw, AI4SEO_PROMPT_SLIDER_SETTING_STAGE_COUNTS ) );
$ai4seo_is_pre_240_settings_import                  = ( '' !== $ai4seo_import_plugin_version && version_compare( $ai4seo_import_plugin_version, '2.4.0', '<' ) );
$ai4seo_should_apply_prompt_slider_import_migration = in_array( 'settings', $ai4seo_import_categories, true )
	&& ( $ai4seo_is_pre_240_settings_import || ( '' === $ai4seo_import_plugin_version && ! $ai4seo_has_imported_prompt_slider_settings ) );

if ( $ai4seo_should_apply_prompt_slider_import_migration ) {
	$ai4seo_prompt_slider_setting_migration_values = ai4seo_get_prompt_slider_setting_pre_240_migration_values( $ai4seo_new_settings_raw );

	foreach ( $ai4seo_prompt_slider_setting_migration_values as $ai4seo_this_setting_name => $ai4seo_this_setting_value ) {
		// Preserve explicit slider values from newer exports or manually edited import files.
		if ( array_key_exists( $ai4seo_this_setting_name, $ai4seo_new_settings_raw ) ) {
			continue;
		}

		$ai4seo_new_settings_raw[ $ai4seo_this_setting_name ] = $ai4seo_this_setting_value;
	}
}


// ___________________________________________________________________________________________ \\
// === FILTER BY CATEGORIES ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_categorized_new_settings     = array();
$ai4seo_got_unknown_category_entries = false;

foreach ( $ai4seo_new_settings_raw as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value ) {
	// can't import these settings ->.
	if ( in_array( $ai4seo_this_setting_name, AI4SEO_NOT_IMPORTABLE_SETTINGS, true ) ) {
		continue;
	}

	$ai4seo_this_setting_category = ai4seo_get_setting_category( $ai4seo_this_setting_name );

	if ( in_array( $ai4seo_this_setting_category, $ai4seo_import_categories, true ) ) {
		$ai4seo_categorized_new_settings[ $ai4seo_this_setting_category ][ $ai4seo_this_setting_name ] = $ai4seo_this_setting_new_value;
	}

	if ( 'unknown' === $ai4seo_this_setting_category ) {
		$ai4seo_got_unknown_category_entries = true;
	}
}


// ___________________________________________________________________________________________ \\
// === VALIDATE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_invalid_settings               = array();
$ai4seo_not_selected_category_settings = array();
$ai4seo_validated_new_settings         = array();

foreach ( $ai4seo_categorized_new_settings as $ai4seo_this_category => $ai4seo_this_settings ) {
	foreach ( $ai4seo_this_settings as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value ) {
		// Get the current value from the settings.
		$ai4seo_current_value = $ai4seo_settings[ $ai4seo_this_setting_name ] ?? null;

		// Normalize imported boolean representations from current and older export formats.
		$ai4seo_this_setting_new_value = ai4seo_normalize_boolean_setting_value(
			$ai4seo_this_setting_name,
			$ai4seo_this_setting_new_value
		);

		// convert the new value to the same type as the current value.
		if ( is_int( $ai4seo_current_value ) ) {
			$ai4seo_this_setting_new_value = (int) $ai4seo_this_setting_new_value;
		} elseif ( is_float( $ai4seo_current_value ) ) {
			$ai4seo_this_setting_new_value = (float) $ai4seo_this_setting_new_value;
		}

		// Custom-instruction imports use the same capping/cleanup path as settings saves before validation.
		$ai4seo_this_setting_new_value = ai4seo_normalize_custom_instructions_setting_value( $ai4seo_this_setting_name, $ai4seo_this_setting_new_value );

		// Validate the normalized value so preview and execute mode agree on what can be imported.
		if ( ai4seo_validate_setting_value( $ai4seo_this_setting_name, $ai4seo_this_setting_new_value ) ) {
			// these settings are valid and can be imported.
			if ( in_array( $ai4seo_this_category, $ai4seo_import_categories, true ) ) {
				$ai4seo_validated_new_settings[ $ai4seo_this_setting_name ] = $ai4seo_this_setting_new_value;
			} else {
				// If the setting is valid but not in the selected categories, store it for later.
				$ai4seo_not_selected_category_settings[ $ai4seo_this_setting_name ] = $ai4seo_this_setting_new_value;
			}
		} else {
			$ai4seo_invalid_settings[ $ai4seo_this_setting_name ] = $ai4seo_this_setting_name;
		}
	}
}

$ai4seo_we_got_valid_settings = ! empty( $ai4seo_validated_new_settings );


// ___________________________________________________________________________________________ \\
// === COMPARE WITH OLD VALUE ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_new_and_validated_changes = array();

foreach ( $ai4seo_validated_new_settings as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value ) {
	// Get the current value from the settings.
	$ai4seo_current_value = $ai4seo_settings[ $ai4seo_this_setting_name ] ?? null;

	if ( $ai4seo_current_value !== $ai4seo_this_setting_new_value ) {
		// If the current value is different from the new value, prepare for comparison.
		$ai4seo_new_and_validated_changes[ $ai4seo_this_setting_name ] = array(
			'current'  => $ai4seo_current_value,
			'new'      => $ai4seo_this_setting_new_value,
			'category' => ai4seo_get_setting_category( $ai4seo_this_setting_name ),
		);
	}
}

$ai4seo_we_got_valid_changes = ! empty( $ai4seo_new_and_validated_changes );


// ___________________________________________________________________________________________ \\
// === PREVIEW ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ( 'preview' === $ai4seo_import_mode ) {

	// HEADLINE.
	echo "<div class='ai4seo-modal-headline'>";
		echo "<div class='ai4seo-modal-headline-icon'>";
			ai4seo_echo_wp_kses( ai4seo_get_sooz_logo_image_tag() );
		echo '</div>';

		echo ' - ' . esc_html__( 'Preview Settings Import', 'ai-for-seo' );
	echo '</div>';

	$ai4seo_got_invalid_or_unknown_settings = ( ! empty( $ai4seo_invalid_settings ) || $ai4seo_got_unknown_category_entries );

	// Warn when preview contains rows that execution will skip, even if other selected settings can be imported.
	if ( $ai4seo_we_got_valid_settings ) {
		if ( $ai4seo_got_invalid_or_unknown_settings ) {
			echo "<div class='ai4seo-medium-gap'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
			echo ' ';
			echo esc_html__( 'ATTENTION: Some settings are invalid or belong to unknown categories and cannot be imported. However, the valid settings can be imported.', 'ai-for-seo' );
			echo '</div>';
		} elseif ( ! $ai4seo_we_got_valid_changes ) {
			echo "<div class='ai4seo-medium-gap'>";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-check' ) );
				echo ' ';
				echo esc_html__( 'Your settings are up-to-date and no changes were detected.', 'ai-for-seo' );
			echo '</div>';
		}
	} else {
		echo "<div class='ai4seo-medium-gap'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
			echo ' ';
			echo esc_html__( 'No valid settings found for import.', 'ai-for-seo' );
		echo '</div>';
	}

	// go through each category and its settings.
	foreach ( $ai4seo_categorized_new_settings as $ai4seo_this_category => $ai4seo_this_settings ) {
		if ( empty( $ai4seo_this_settings ) ) {
			continue; // Skip empty categories.
		}

		if ( $ai4seo_this_category && ! in_array( $ai4seo_this_category, $ai4seo_import_categories, true ) ) {
			continue; // Skip categories not selected for import.
		}

		$ai4seo_is_unknown_category = false;

		switch ( $ai4seo_this_category ) {
			case 'settings':
				$ai4seo_import_category_label = esc_html__( 'Settings (This Page)', 'ai-for-seo' );
				break;
			case 'account':
				$ai4seo_import_category_label = esc_html__( 'Account Settings (Without Credentials)', 'ai-for-seo' );
				break;
			case 'seo_autopilot':
				$ai4seo_import_category_label = esc_html__( 'SEO Autopilot Settings', 'ai-for-seo' );
				break;
			case 'get_more_credits':
				$ai4seo_import_category_label = esc_html__( 'Get More Credits Settings', 'ai-for-seo' );
				break;
			default:
				$ai4seo_is_unknown_category   = true;
				$ai4seo_import_category_label = ai4seo_get_svg_tag( 'triangle-exclamation' ) . ' ' . esc_html__( 'Unknown Settings (Cannot be imported)', 'ai-for-seo' );
				break;
		}

		echo "<h3 class='ai4seo-import-settings-preview-heading'>";
			ai4seo_echo_wp_kses( $ai4seo_import_category_label );
		echo '</h3>';
		echo '<ul>';

		$ai4seo_this_got_any_preview_rows = false;

		foreach ( $ai4seo_this_settings as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value ) {
			$ai4seo_this_setting_is_invalid = ( $ai4seo_is_unknown_category || in_array( $ai4seo_this_setting_name, $ai4seo_invalid_settings, true ) );

			// Invalid rows have no validated change record, but the preview should still show what execute mode will skip.
			if ( isset( $ai4seo_new_and_validated_changes[ $ai4seo_this_setting_name ] ) ) {
				$ai4seo_this_new_and_validated_change = $ai4seo_new_and_validated_changes[ $ai4seo_this_setting_name ];
			} elseif ( $ai4seo_this_setting_is_invalid ) {
				$ai4seo_this_new_and_validated_change = array(
					'current' => $ai4seo_settings[ $ai4seo_this_setting_name ] ?? null,
					'new'     => $ai4seo_this_setting_new_value,
				);
			} else {
				continue;
			}

			$ai4seo_this_got_any_preview_rows = true;

			// Skipped rows stay visible so users can see why only the valid subset will be imported.
			if ( $ai4seo_this_setting_is_invalid ) {
				echo "<li class='ai4seo-import-settings-invalid-item'>- ";
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'triangle-exclamation' ) );
				echo ' ';
			} else {
				echo '<li>- ';
			}

			// Inverted storage settings should be previewed like the admin UI, while invalid rows keep their raw value.
			$ai4seo_this_setting_preview_name          = ai4seo_get_import_setting_preview_name( $ai4seo_this_setting_name );
			$ai4seo_this_current_setting_preview_value = $ai4seo_this_setting_is_invalid
				? $ai4seo_this_new_and_validated_change['current']
				: ai4seo_get_import_setting_preview_value( $ai4seo_this_setting_name, $ai4seo_this_new_and_validated_change['current'] );
			$ai4seo_this_new_setting_preview_value     = $ai4seo_this_setting_is_invalid
				? $ai4seo_this_new_and_validated_change['new']
				: ai4seo_get_import_setting_preview_value( $ai4seo_this_setting_name, $ai4seo_this_new_and_validated_change['new'] );

			// make nicer current setting value.
			$ai4seo_this_current_setting_value = ai4seo_make_nicer_setting_value( $ai4seo_this_current_setting_preview_value );

			// make nicer new setting value.
			$ai4seo_this_setting_new_value = ai4seo_make_nicer_setting_value( $ai4seo_this_new_setting_preview_value );

			echo '<strong>' . esc_html( $ai4seo_this_setting_preview_name ) . ':</strong> ';
			echo "<span class='ai4seo-import-settings-current-value'>";
				ai4seo_echo_wp_kses( $ai4seo_this_current_setting_value );
			echo '</span> → ';

			ai4seo_echo_wp_kses( $ai4seo_this_setting_new_value );

			echo '</li>';
		}

		echo '</ul>';

		if ( ! $ai4seo_this_got_any_preview_rows ) {
			echo "<div class='ai4seo-medium-gap'>";
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-check' ) );
			echo ' ';
			echo esc_html__( 'No changes were detected in this settings category.', 'ai-for-seo' );
			echo '</div>';
		}
	}

	// Buttons.
	echo "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>";
		// abort button.
		ai4seo_echo_wp_kses( ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ) );

	if ( $ai4seo_we_got_valid_changes ) {
		ai4seo_echo_wp_kses( ai4seo_get_submit_button_tag( esc_html__( 'Import Settings', 'ai-for-seo' ), 'ai4seo-big-button', 'ai4seo_execute_import_settings(this);' ) );
	}
	echo '</div>';

	return;
}


// ___________________________________________________________________________________________ \\
// === EXECUTE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if ( 'execute' === $ai4seo_import_mode ) {
	$ai4seo_upcoming_updates = array();

	if ( ! $ai4seo_we_got_valid_settings ) {
		ai4seo_send_ajax_error( esc_html__( 'No valid settings found for import.', 'ai-for-seo' ), 8137725 );
	}

	if ( ! $ai4seo_we_got_valid_changes || ! $ai4seo_new_and_validated_changes || ! is_array( $ai4seo_new_and_validated_changes ) ) {
		ai4seo_send_ajax_error( esc_html__( 'No changes detected in the selected settings.', 'ai-for-seo' ), 9137725 );
	}

	$ai4seo_import_setting_changes_for_save_anything = array();

	// Keep validated preview values in stored format until the final save-anything boundary.
	foreach ( $ai4seo_new_and_validated_changes as $ai4seo_this_setting_name => $ai4seo_this_new_and_validated_change ) {
		$ai4seo_import_setting_changes_for_save_anything[ $ai4seo_this_setting_name ] = $ai4seo_this_new_and_validated_change['new'];
	}

	// Convert only the settings whose stored import values differ from their admin-form submit shape.
	$ai4seo_import_setting_changes_for_save_anything = ai4seo_prepare_import_settings_for_save_anything( $ai4seo_import_setting_changes_for_save_anything );

	// Save-anything still owns persistence and post-save side effects, so import values use its prefixed input map.
	foreach ( $ai4seo_import_setting_changes_for_save_anything as $ai4seo_this_setting_name => $ai4seo_this_setting_new_value ) {
		// Prefix names exactly like regular form submissions before handing control to save-settings.php.
		$ai4seo_this_setting_name                             = ai4seo_get_prefixed_input_name( $ai4seo_this_setting_name );
		$ai4seo_upcoming_updates[ $ai4seo_this_setting_name ] = $ai4seo_this_setting_new_value;
	}

	ai4seo_save_anything( $ai4seo_upcoming_updates );

	return;
}
