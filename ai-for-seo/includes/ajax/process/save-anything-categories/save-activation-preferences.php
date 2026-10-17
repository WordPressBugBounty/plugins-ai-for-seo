<?php
/**
 * Persists voluntary offer preferences through the Account save action.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save changed channel choices without renewing consent on unrelated Account saves.
 *
 * @param array $updates Sanitized save-anything values without the plugin prefix.
 * @return WP_Error|null Error on failure, null on success or when absent/unchanged.
 */
function ai4seo_process_save_anything_activation_preferences( array &$updates ) {
	// The v2 form is preflighted and committed with its display settings by save-anything.
	if ( ! array_key_exists( 'activation_plugin', $updates ) ) {
		return null;
	}
	if ( ! array_key_exists( 'activation_original', $updates ) ) {
		return null;
	}
	if ( ! ai4seo_can_administer_plugin() ) {
		return new WP_Error( 11420725, esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ) );
	}
	$invalid = new WP_Error( 5092702, esc_html__( 'Invalid offer preferences. Please refresh the page and try again.', 'ai-for-seo' ) );
	$input   = array();
	foreach ( array( 'plugin', 'email', 'country', 'address', 'consent_version' ) as $key ) {
		$value = $updates[ 'activation_' . $key ] ?? null;
		if ( in_array( $key, array( 'plugin', 'email' ), true ) ? ! is_bool( $value ) : ! is_string( $value ) ) {
			return $invalid;
		}
		$input[ $key ] = $value;
	}
	$original = is_string( $updates['activation_original'] ) ? json_decode( $updates['activation_original'], true ) : null;
	if ( ! is_array( $original ) || ! is_string( $updates['activation_signature'] ?? null ) ) {
		return $invalid;
	}
	// The original values only detect a no-op; changed choices must match their rendered account.
	if ( $input === $original ) {
		return null;
	}
	if ( ! hash_equals( ai4seo_get_activation_preferences_signature( $original ), $updates['activation_signature'] ) ) {
		return new WP_Error( 5092705, esc_html__( 'Your account changed. Reload this page before saving offer preferences.', 'ai-for-seo' ) );
	}
	// Withdrawing both permissions needs neither a country nor an email address.
	if ( ! $input['plugin'] && ! $input['email'] ) {
		return ai4seo_save_activation_preferences( array( 'action' => 'optout' ) );
	}
	$input['country'] = strtoupper( trim( $input['country'] ) );
	$input['address'] = trim( $input['address'] );
	if ( ! isset( ai4seo_get_country_names()[ $input['country'] ] ) || strlen( $input['address'] ) > 254
		|| ( $input['email'] && ! is_email( $input['address'] ) ) ) {
		return $invalid;
	}
	$input['action'] = 'save';
	return ai4seo_save_activation_preferences( $input );
}

/**
 * Validate only deliberately changed email fields; unrelated saves never renew consent.
 *
 * @param array $updates Sanitized Account form fields.
 */
function ai4seo_validate_activation_preferences_save( array $updates ) {
	$original = is_string( $updates['activation_original'] ?? null ) ? json_decode( $updates['activation_original'], true ) : null;
	if ( ! is_array( $original ) || ! is_string( $updates['activation_signature'] ?? null )
		|| ! hash_equals( ai4seo_get_activation_preferences_signature( $original ), $updates['activation_signature'] ) ) {
		return new WP_Error( 5092705, __( 'Your account changed. Reload this page before saving offer preferences.', 'ai-for-seo' ) );
	}
	if ( ! is_int( $original['expected_revision'] ?? null ) || $original['expected_revision'] < 0 ) {
		return new WP_Error( 5092702, __( 'Invalid email preferences. Reload this page before saving.', 'ai-for-seo' ) );
	}
	$input = array();
	foreach ( array( 'email', 'country', 'address', 'consent_version' ) as $key ) {
		$value = $updates[ 'activation_' . $key ] ?? null;
		if ( 'email' === $key ? ! is_bool( $value ) : ! is_string( $value ) ) {
			return new WP_Error( 5092702, __( 'Invalid email preferences.', 'ai-for-seo' ) );
		}
		$input[ $key ] = $value;
	}
	// Keep the rendered revision through validation, durable queuing and transport.
	$input['expected_revision'] = $original['expected_revision'];
	if ( $input === $original && true !== ( $updates['notification_reactivate'] ?? false ) ) {
		return array();
	}
	$data = ai4seo_get_activation_data( true );
	if ( ( $input['email'] || true === ( $updates['notification_reactivate'] ?? false ) )
		&& (int) ( $data['preferences']['preferences_revision'] ?? 0 ) !== $input['expected_revision'] ) {
		return new WP_Error( 5092711, __( 'Email preferences changed. Reload this page before saving.', 'ai-for-seo' ) );
	}
	$input['country'] = strtoupper( trim( $input['country'] ) );
	$input['address'] = trim( $input['address'] );
	if ( $input['email'] && ( ! isset( ai4seo_get_country_names()[ $input['country'] ] ) || ! is_email( $input['address'] ) || strlen( $input['address'] ) > 254 ) ) {
		return new WP_Error( 5092702, __( 'Select your country and enter a valid email address.', 'ai-for-seo' ) );
	}
	return $input;
}
