<?php
/**
 * Inspect settings without repairing storage and persist explicit changes only.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decode the supported option formats without sanitizing away evidence.
 *
 * @param array|null $snapshot Exact option snapshot.
 * @return array|null Stored overrides, or null when their structure is unknown.
 */
function ai4seo_decode_settings_snapshot( ?array $snapshot ): ?array {
	if ( null === $snapshot ) {
		return null;
	}
	$value = $snapshot['exists'] ? ai4seo_safe_maybe_unserialize( $snapshot['value'] ) : array();
	if ( is_string( $value ) ) {
		$value = json_decode( $value, true );
	}
	return is_array( $value ) ? $value : null;
}

/**
 * Normalize explicit input consistently before strict validation.
 *
 * @param string $name Setting name.
 * @param mixed  $value Submitted value.
 * @return mixed Canonical input.
 */
function ai4seo_normalize_setting_input( string $name, $value ) {
	$value = ai4seo_normalize_custom_instructions_setting_value( $name, $value );
	$value = ai4seo_normalize_boolean_setting_value( $name, $value );
	$value = ai4seo_normalize_incognito_mode_user_id_setting_value( $name, $value );
	return ai4seo_normalize_prompt_slider_setting_value( $name, $value );
}

/**
 * Inspect each setting independently; an unknown container never means a new installation.
 *
 * @param array|null $snapshot Optional fresh snapshot.
 * @return array Raw overrides, effective settings and per-key quality reasons.
 */
function ai4seo_inspect_settings( ?array $snapshot = null ): array {
	$snapshot = $snapshot ?? ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
	$raw      = ai4seo_decode_settings_snapshot( $snapshot );
	$settings = AI4SEO_DEFAULT_SETTINGS;
	$issues   = array();
	$lists    = array(
		AI4SEO_SETTING_ACTIVE_META_TAGS              => AI4SEO_AVAILABLE_METADATA_IDENTIFIERS,
		AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA   => AI4SEO_AVAILABLE_METADATA_IDENTIFIERS,
		AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA      => AI4SEO_AVAILABLE_METADATA_IDENTIFIERS,
		AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES  => AI4SEO_AVAILABLE_ATTACHMENT_ATTRIBUTE_IDENTIFIERS,
		AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES => AI4SEO_AVAILABLE_ATTACHMENT_ATTRIBUTE_IDENTIFIERS,
		AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES => array_keys( AI4SEO_ATTACHMENT_FILE_TYPE_MIME_TYPES ),
		AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS => array_keys( ai4seo_get_third_party_seo_plugin_details() ),
	);
	foreach ( $settings as $name => $default ) {
		if ( is_array( $raw ) && ! array_key_exists( $name, $raw ) ) {
			continue;
		}
		$value = is_array( $raw ) ? $raw[ $name ] : null;
		try {
			$value = ai4seo_normalize_setting_input( $name, $value );
			$valid = null !== $raw && ai4seo_validate_setting_value( $name, $value );
		} catch ( Throwable $exception ) {
			$valid = false;
		}
		if ( $valid ) {
			$settings[ $name ] = $value;
			continue;
		}
		$issues[ $name ] = null === $raw ? 'container_unreadable' : 'invalid';
		if ( isset( $lists[ $name ] ) ) {
			$settings[ $name ] = array();
			foreach ( is_array( $value ) ? $value : array() as $member ) {
				if ( is_string( $member ) && in_array( $member, $lists[ $name ], true ) ) {
					$settings[ $name ][] = $member;
				}
			}
			$settings[ $name ] = array_values( array_unique( $settings[ $name ] ) );
			if ( $settings[ $name ] ) {
				$issues[ $name ] = 'partial';
			}
		} elseif ( is_bool( $default ) ) {
			// Unknown restrictions remain restrictive; other switches do not grant new activity.
			$settings[ $name ] = in_array( $name, array( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ), true );
		} elseif ( in_array( $name, array( AI4SEO_SETTING_ALLOWED_USER_ROLES, AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ), true ) ) {
			$settings[ $name ] = array();
		} elseif ( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID === $name ) {
			$settings[ $name ] = false;
		} elseif ( AI4SEO_SETTING_META_TAG_OUTPUT_MODE === $name ) {
			$settings[ $name ] = 'disable';
		} elseif ( AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE === $name ) {
			$settings[ $name ] = 'disabled';
		}
	}
	if ( is_array( $raw ) && ! array_key_exists( AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES, $raw ) ) {
		$version = (string) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION );
		if ( '' !== $version && '0.0.0' !== $version ) {
			$settings[ AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES ] = AI4SEO_LEGACY_ATTACHMENT_FILE_TYPES;
		}
	}
	if ( $settings[ AI4SEO_SETTING_ENABLE_INCOGNITO_MODE ] && ( null === $raw || ! array_key_exists( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID, $raw ) ) ) {
		$settings[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] = false;
		$issues[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ]   = 'invalid';
	}
	return array(
		'readable' => null !== $raw,
		'raw'      => $raw,
		'settings' => $settings,
		'issues'   => $issues,
	);
}

/**
 * Fingerprint a field's exact presence and value without exposing stored content.
 *
 * @param array  $raw Stored overrides.
 * @param string $name Setting name.
 * @return string Opaque conflict token, scoped to the current website.
 */
function ai4seo_get_setting_token( array $raw, string $name ): string {
	return hash_hmac( 'sha256', serialize( array( ai4seo_get_site_options_request_cache_scope(), $name, array_key_exists( $name, $raw ), $raw[ $name ] ?? null ) ), wp_salt( 'nonce' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Exact types and key presence are part of the conflict contract.
}

/**
 * Describe settings to an authorized form without exposing raw values.
 *
 * @param array|null $state Inspection used to render the controls, or a fresh inspection.
 * @return array Conflict tokens and quality reasons.
 */
function ai4seo_get_settings_form_state( ?array $state = null ): array {
	$state  = $state ?? ai4seo_inspect_settings();
	$tokens = array();
	foreach ( array_keys( AI4SEO_DEFAULT_SETTINGS ) as $name ) {
		$tokens[ $name ] = $state['readable'] ? ai4seo_get_setting_token( $state['raw'], $name ) : '';
	}
	return array(
		'readable' => $state['readable'],
		'tokens'   => $tokens,
		'issues'   => $state['issues'],
	);
}

/**
 * Render the shared form metadata; JavaScript places notices beside affected controls.
 *
 * @param array|null $state Explicit inspection for previews; ordinary forms use their loaded view.
 * @return string Escaped markup.
 */
function ai4seo_get_settings_form_state_tag( ?array $state = null ): string {
	ai4seo_get_all_settings();
	$state = $state ?? $GLOBALS['ai4seo_settings_loaded_states'][ ai4seo_get_site_options_request_cache_scope() ] ?? ai4seo_inspect_settings();
	return '<div class="ai4seo-settings-state" data-ai4seo-settings-state="' . esc_attr( wp_json_encode( ai4seo_get_settings_form_state( $state ) ) ) . '"></div>';
}

/**
 * Commit only explicit validated keys, preserving unrelated bytes and concurrent changes.
 *
 * @param array      $changes Explicit new values.
 * @param array|null $expected Raw planning state; null captures the state at the first attempt.
 * @param bool       $preserve_defaults Keep explicit defaults for compatibility migrations.
 * @param string     $lock_name Optional advisory lock that must still be owned.
 * @return array Status and names of changed settings; no partial persistence on failure.
 */
function ai4seo_patch_settings( array $changes, ?array $expected = null, bool $preserve_defaults = false, string $lock_name = '' ): array {
	foreach ( $changes as $name => $value ) {
		try {
			$valid = is_string( $name ) && array_key_exists( $name, AI4SEO_DEFAULT_SETTINGS );
			if ( $valid ) {
				$value = ai4seo_normalize_setting_input( $name, $value );
				$valid = ai4seo_validate_setting_value( $name, $value );
			}
		} catch ( Throwable $exception ) {
			$valid = false;
		}
		if ( ! $valid ) {
			return array(
				'status' => 'invalid',
				'fields' => array( $name ),
			);
		}
		$changes[ $name ] = $value;
	}
	if ( ! $changes ) {
		return array(
			'status' => 'saved',
			'fields' => array(),
		);
	}
	for ( $attempt = 0; $attempt < 3; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
		$raw      = ai4seo_decode_settings_snapshot( $snapshot );
		if ( null === $raw ) {
			return array(
				'status' => 'unreadable',
				'fields' => array(),
			);
		}
		$expected = $expected ?? $raw;
		// Ownership changes cannot race another administrative settings mutation.
		$guard_keys = array_unique( array_merge( array_keys( $changes ), array( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ) ) );
		foreach ( $guard_keys as $name ) {
			if ( ai4seo_get_setting_token( $raw, $name ) !== ai4seo_get_setting_token( $expected, $name ) ) {
				return array(
					'status' => 'conflict',
					'fields' => array( $name ),
				);
			}
		}
		$replacement = $raw;
		foreach ( $changes as $name => $value ) {
			// Type-equivalent valid legacy representations need no rewrite.
			$old_valid = false;
			try {
				$old_value = array_key_exists( $name, $raw ) ? ai4seo_normalize_setting_input( $name, $raw[ $name ] ) : null;
				$old_valid = ai4seo_validate_setting_value( $name, $old_value );
			} catch ( Throwable $exception ) {
				$old_valid = false;
			}
			if ( $old_valid && ai4seo_are_persisted_state_values_equivalent( $old_value, $value ) ) {
				continue;
			}
			$replacement[ $name ] = $value;
			if ( ! $preserve_defaults && AI4SEO_SETTING_ENABLED_ATTACHMENT_FILE_TYPES !== $name && AI4SEO_SETTING_INCOGNITO_MODE_USER_ID !== $name
				&& ai4seo_are_persisted_state_values_equivalent( AI4SEO_DEFAULT_SETTINGS[ $name ], $value ) ) {
				unset( $replacement[ $name ] );
			}
		}
		if ( array_key_exists( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID, $changes )
			&& false === ai4seo_normalize_boolean_setting_value( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, $replacement[ AI4SEO_SETTING_ENABLE_INCOGNITO_MODE ] ?? false ) ) {
			unset( $replacement[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] );
		}
		if ( '' !== $lock_name && ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock_name ) ) {
			return array(
				'status' => 'lock_lost',
				'fields' => array(),
			);
		}
		$result = $replacement === $raw ? true : ai4seo_compare_and_swap_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME, $snapshot, $replacement, true );
		if ( true === $result ) {
			$tokens = array();
			foreach ( $guard_keys as $name ) {
				$tokens[ $name ] = ai4seo_get_setting_token( $replacement, $name );
			}
			ai4seo_reset_settings_request_cache_for_current_site();
			ai4seo_init_settings();
			return array(
				'status' => 'saved',
				'fields' => array_keys( $changes ),
				'tokens' => $tokens,
			);
		}
		if ( null === $result ) {
			return array(
				'status' => 'storage_error',
				'fields' => array(),
			);
		}
	}
	return array(
		'status' => 'conflict',
		'fields' => array_keys( $changes ),
	);
}

/**
 * Preserve exclusions outside the displayed, currently available selection domain.
 *
 * @param array $previous Previously stored exclusions.
 * @param array $replacement New exclusions calculated by the existing form adapter.
 * @param array $available Currently available values.
 * @param array $displayed Values actually shown when the form was opened.
 * @return array Exclusions with unavailable and unseen entries retained.
 */
function ai4seo_merge_setting_exclusions( array $previous, array $replacement, array $available, array $displayed ): array {
	$domain = array_intersect( $available, $displayed );
	return array_values( array_unique( array_merge( array_diff( $previous, $domain ), array_intersect( $replacement, $domain ) ) ) );
}

/**
 * Convert active form selections while retaining exclusions not represented by the form.
 *
 * @param array $changes Submitted settings.
 * @param array $raw Current stored overrides.
 * @param array $domains Displayed checkbox domains keyed by setting and optional taxonomy.
 * @return array|WP_Error Valid stored-format changes or a field-specific rejection.
 */
function ai4seo_prepare_setting_selection_changes( array $changes, array $raw, array $domains = array() ) {
	$readers = array(
		AI4SEO_SETTING_DISABLED_POST_TYPES              => static function () {
			return ai4seo_get_supported_post_types( false );
		},
		AI4SEO_SETTING_DISABLED_POST_AUTHORS            => static function () {
			return array_keys( ai4seo_get_available_post_authors() );
		},
		AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS => static function () {
			return array_keys( ai4seo_get_available_attachment_post_authors() );
		},
		AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES => static function () {
			return array_keys( ai4seo_get_available_wpml_languages() );
		},
		AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES => static function () {
			return array_keys( ai4seo_get_available_wpml_languages() );
		},
		AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS          => static function () {
			$result = array();
			foreach ( ai4seo_get_supported_taxonomy_terms() as $taxonomy => $details ) {
				$result[ $taxonomy ] = array_keys( $details['terms'] ?? array() );
			}
			return $result;
		},
	);
	foreach ( array_intersect_key( $changes, $readers ) as $name => $selected ) {
		if ( ! is_array( $selected ) ) {
			$selected = $selected ? array( $selected ) : array();
		}
		if ( ! ai4seo_validate_setting_value( $name, $selected ) ) {
			return new WP_Error( 261219225, __( 'Please check the selected filters before saving.', 'ai-for-seo' ) );
		}
		$previous = $raw[ $name ] ?? array();
		// A deliberate edit may replace a broken field, but never silently cleans other fields.
		$previous  = is_array( $previous ) && ai4seo_validate_setting_value( $name, $previous ) ? $previous : array();
		$available = $readers[ $name ]();
		$displayed = $domains[ $name ] ?? $available;
		if ( ! is_array( $displayed ) || ! ai4seo_validate_setting_value( $name, $displayed ) ) {
			return new WP_Error( 261219225, __( 'The displayed filter choices could not be verified. Keep your changes and reload the form.', 'ai-for-seo' ) );
		}
		if ( AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS === $name ) {
			$replacement = $previous;
			foreach ( $available as $taxonomy => $ids ) {
				$replacement[ $taxonomy ] = ai4seo_merge_setting_exclusions(
					$previous[ $taxonomy ] ?? array(),
					array_diff( $ids, $selected[ $taxonomy ] ?? array() ),
					$ids,
					$displayed[ $taxonomy ] ?? array()
				);
				if ( ! $replacement[ $taxonomy ] ) {
					unset( $replacement[ $taxonomy ] );
				}
			}
			$changes[ $name ] = $replacement;
		} else {
			$changes[ $name ] = ai4seo_merge_setting_exclusions( $previous, array_diff( $available, $selected ), $available, $displayed );
		}
	}
	return $changes;
}

/**
 * Check browser conflict tokens before any setting or dependent state can change.
 *
 * @param array $changes Submitted settings.
 * @param array $edit Browser intent envelope.
 * @param array $raw Current raw overrides.
 * @return WP_Error|null Conflict, invalid envelope, or success.
 */
function ai4seo_validate_settings_edit( array $changes, array $edit, array $raw ) {
	$names = array_unique( array_merge( array_keys( array_intersect_key( $changes, AI4SEO_DEFAULT_SETTINGS ) ), array( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ) ) );
	foreach ( $names as $name ) {
		$token = $edit['tokens'][ $name ] ?? null;
		if ( ! is_string( $token ) || ! hash_equals( ai4seo_get_setting_token( $raw, $name ), $token ) ) {
			return new WP_Error( 461013126, __( 'These settings changed after this form was opened. Nothing was saved. Keep your changes and reload the form before trying again.', 'ai-for-seo' ), array( 'settings_conflict' => array( $name ) ) );
		}
	}
	return null;
}

/**
 * Publish confirmed persistence separately from incomplete follow-up work.
 *
 * @param array $fields Saved field names.
 * @param array $warnings Follow-up notices.
 * @param array $tokens Tokens from the exact successful write.
 * @return array Shared save response.
 */
function ai4seo_settings_save_response( array $fields, array $warnings = array(), array $tokens = array() ): array {
	$state           = ai4seo_get_settings_form_state();
	$state['tokens'] = $tokens;
	return array(
		'settings_save' => array(
			'saved'    => true,
			'fields'   => $fields,
			'warnings' => $warnings,
			'state'    => $state,
		),
	);
}
