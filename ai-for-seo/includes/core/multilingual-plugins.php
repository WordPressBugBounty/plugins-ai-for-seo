<?php
/**
 * Provides integrations with multilingual plugins.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region MULTI-LANGUAGE THIRD-PARTY PLUGINS ==================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

// region TRANSLATEPRESS ========================================================================.

/**
 * Remove TranslatePress tags and wrappers from a string.
 *
 * Example input:
 * "#!trpst#trp-gettext#Metadata Editor#!trpen#Manage metadata for Stuffed peppers (#35432)#!trpst#"
 * Output:
 * "Metadata Editor Manage metadata for Stuffed peppers (#35432)"
 *
 * @param string $input Text that may contain TranslatePress tags.
 * @return string
 */
function ai4seo_remove_translatepress_tags( string $input ): string {
	// Replace TranslatePress wrapped text with its inner content.
	$cleaned_text = preg_replace_callback(
		'/#!trpst#trp-gettext#(.*?)#!trpen#/us',
		function ( $matches ) {
			return ' ' . $matches[1] . ' ';
		},
		$input
	);

	// Handle inline variant like #trp-gettext data-trpgettextoriginal=157#!trpen#.
	$cleaned_text = preg_replace( '/#trp-gettext[^#]*#!trpen#/us', ' ', $cleaned_text );

	// Remove any remaining TranslatePress markers.
	$cleaned_text = preg_replace( '/#!?trp[a-zA-Z0-9_\-\s="]+#?/', ' ', $cleaned_text );

	// Collapse whitespace left by removed wrappers so AJAX text remains readable.
	$cleaned_text = trim( preg_replace( '/\s+/', ' ', $cleaned_text ) );

	return $cleaned_text;
}


// endregion
// ___________________________________________________________________________________________.

/**
 * Function that tries to determine the language of a post by checking various multi-language plugins
 *
 * @param int $post_id The post id.
 * @return string The language of the post
 */
function ai4seo_try_get_post_language_by_checking_multilanguage_plugins( int $post_id ): string {
	$post_language_code = ai4seo_get_post_language_code_by_multilanguage_plugins( $post_id );

	if ( '' !== $post_language_code ) {
		return ai4seo_get_language_long_version( $post_language_code, '' );
	}

	return '';
}


/**
 * Returns the language code for a post if a supported multi-language plugin is active.
 *
 * @param int $post_id The post id.
 * @return string The language code (e.g. "en", "de") or an empty string if not available.
 */
function ai4seo_get_post_language_code_by_multilanguage_plugins( int $post_id ): string {
	// WPML.
	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML defines this public integration hook.
		$attachment_language = apply_filters( 'wpml_post_language_details', null, $post_id );
		$attachment_language = ai4seo_deep_sanitize( $attachment_language );

		if ( $attachment_language && isset( $attachment_language['language_code'] ) ) {
			return sanitize_key( $attachment_language['language_code'] );
		}
	}

	return '';
}


/**
 * Returns active WPML languages as labels keyed by language code.
 *
 * Uses WPML's active-language filter so settings and list filters share the same language scope.
 *
 * @return array
 */
function ai4seo_get_available_wpml_languages(): array {
	static $ai4seo_available_wpml_languages = null;

	if ( is_array( $ai4seo_available_wpml_languages ) ) {
		return $ai4seo_available_wpml_languages;
	}

	$ai4seo_available_wpml_languages = array();

	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) || ! has_filter( 'wpml_active_languages' ) ) {
		return $ai4seo_available_wpml_languages;
	}

	// Ask WPML for every active language, even when the current entry has no translation.
	$wpml_active_languages = apply_filters(
		'wpml_active_languages', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML defines this public integration hook.
		null,
		array(
			'skip_missing' => 0,
		)
	);

	if ( ! is_array( $wpml_active_languages ) ) {
		return $ai4seo_available_wpml_languages;
	}

	foreach ( $wpml_active_languages as $this_wpml_language_code => $this_wpml_language_details ) {
		$this_wpml_language_code = sanitize_key( (string) $this_wpml_language_code );

		if ( '' === $this_wpml_language_code ) {
			continue;
		}

		// Prefer human-readable WPML labels, then fall back to the normalized code.
		$this_wpml_language_label = '';

		if ( is_array( $this_wpml_language_details ) ) {
			$this_wpml_language_label = sanitize_text_field( (string) ( $this_wpml_language_details['native_name'] ?? '' ) );

			if ( '' === $this_wpml_language_label ) {
				$this_wpml_language_label = sanitize_text_field( (string) ( $this_wpml_language_details['translated_name'] ?? '' ) );
			}

			if ( '' === $this_wpml_language_label ) {
				$this_wpml_language_label = sanitize_text_field( (string) ( $this_wpml_language_details['display_name'] ?? '' ) );
			}
		}

		if ( '' === $this_wpml_language_label ) {
			$this_wpml_language_label = strtoupper( $this_wpml_language_code );
		}

		$ai4seo_available_wpml_languages[ $this_wpml_language_code ] = $this_wpml_language_label;
	}

	return $ai4seo_available_wpml_languages;
}


/**
 * Sanitizes WPML language-code lists for settings, filters, and queue cleanup.
 *
 * @param mixed $wpml_language_codes Raw language code list.
 * @param bool  $restrict_to_available_languages Whether to drop languages WPML does not currently expose.
 * @return array
 */
function ai4seo_sanitize_wpml_language_codes( $wpml_language_codes, bool $restrict_to_available_languages = false ): array {
	if ( ! is_array( $wpml_language_codes ) ) {
		$wpml_language_codes = ( '' === $wpml_language_codes || null === $wpml_language_codes ) ? array() : array( $wpml_language_codes );
	}

	$available_wpml_language_codes = $restrict_to_available_languages
		? array_keys( ai4seo_get_available_wpml_languages() )
		: array();
	$sanitized_wpml_language_codes = array();

	foreach ( $wpml_language_codes as $this_wpml_language_code ) {
		if ( ! is_scalar( $this_wpml_language_code ) ) {
			continue;
		}

		$this_wpml_language_code = sanitize_key( (string) $this_wpml_language_code );

		if ( '' === $this_wpml_language_code ) {
			continue;
		}

		if ( $restrict_to_available_languages && ! in_array( $this_wpml_language_code, $available_wpml_language_codes, true ) ) {
			continue;
		}

		$sanitized_wpml_language_codes[] = $this_wpml_language_code;
	}

	return array_values( array_unique( $sanitized_wpml_language_codes ) );
}


/**
 * Returns disabled WPML languages for metadata lists and automation.
 *
 * @return array
 */
function ai4seo_get_disabled_metadata_wpml_language_codes(): array {
	$disabled_wpml_language_codes = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES );

	// Restrict at read time so stale stored languages remain harmless until WPML exposes them again.
	return ai4seo_sanitize_wpml_language_codes(
		$disabled_wpml_language_codes,
		true
	);
}


/**
 * Returns disabled WPML languages for media-attribute lists and automation.
 *
 * @return array
 */
function ai4seo_get_disabled_attachment_attributes_wpml_language_codes(): array {
	$disabled_wpml_language_codes = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES );

	// Restrict at read time so stale stored languages remain harmless until WPML exposes them again.
	return ai4seo_sanitize_wpml_language_codes(
		$disabled_wpml_language_codes,
		true
	);
}


/**
 * Converts submitted active WPML languages into the disabled-language storage format.
 *
 * @param mixed $active_wpml_language_codes Submitted active language codes.
 * @return array
 */
function ai4seo_get_disabled_wpml_language_codes_from_active_selection( $active_wpml_language_codes ): array {
	$available_wpml_language_codes = array_keys( ai4seo_get_available_wpml_languages() );
	$active_wpml_language_codes    = ai4seo_sanitize_wpml_language_codes( $active_wpml_language_codes, true );

	// Newly available WPML languages are active by default because only unchecked languages are stored.
	return array_values( array_diff( $available_wpml_language_codes, $active_wpml_language_codes ) );
}


/**
 * Returns a cached language code for repeated WPML scope checks in the same request.
 *
 * @param int $post_id The post ID.
 * @return string Language code or empty string.
 */
function ai4seo_get_cached_post_language_code_by_multilanguage_plugins( int $post_id ): string {
	static $ai4seo_post_language_code_request_cache = array();

	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return '';
	}

	// Summary and analysis passes can inspect the same IDs repeatedly, so avoid duplicate WPML filter calls.
	if ( ! array_key_exists( $post_id, $ai4seo_post_language_code_request_cache ) ) {
		$ai4seo_post_language_code_request_cache[ $post_id ] = ai4seo_get_post_language_code_by_multilanguage_plugins( $post_id );
	}

	return $ai4seo_post_language_code_request_cache[ $post_id ];
}


/**
 * Removes entries whose WPML language is disabled from a post-ID list.
 *
 * @param array $post_ids Post IDs to filter.
 * @param array $disabled_wpml_language_codes Disabled WPML language codes.
 * @return array Filtered post IDs preserving the original order.
 */
function ai4seo_filter_post_ids_by_disabled_wpml_languages( array $post_ids, array $disabled_wpml_language_codes ): array {
	$disabled_wpml_language_codes = ai4seo_sanitize_wpml_language_codes( $disabled_wpml_language_codes, true );

	if ( ! $disabled_wpml_language_codes ) {
		return $post_ids;
	}

	$disabled_wpml_language_lookup = array_flip( $disabled_wpml_language_codes );
	$filtered_post_ids             = array();

	foreach ( $post_ids as $this_post_id ) {
		$this_post_id = absint( $this_post_id );

		if ( $this_post_id <= 0 ) {
			continue;
		}

		$this_wpml_language_code = ai4seo_get_cached_post_language_code_by_multilanguage_plugins( $this_post_id );

		if ( '' !== $this_wpml_language_code && isset( $disabled_wpml_language_lookup[ $this_wpml_language_code ] ) ) {
			continue;
		}

		$filtered_post_ids[] = $this_post_id;
	}

	return $filtered_post_ids;
}


/**
 * Executes a callback while temporarily forcing WPML to return all languages.
 *
 * @param callable $callback The callback to execute.
 * @return mixed The callback result.
 */
function ai4seo_with_wpml_all_languages( callable $callback ) {
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
		return $callback();
	}

	$filter_function   = 'ai4seo_wpml_return_all_languages';
	$previous_language = null;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized request-local language snapshot used only for restoration; no persistent state is changed.
	if ( isset( $_GET['lang'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized request-local language snapshot used only for restoration; no persistent state is changed.
		$previous_lang_get = sanitize_key( wp_unslash( $_GET['lang'] ) );
	} else {
		$previous_lang_get = null;
	}

	$sitepress = $GLOBALS['sitepress'] ?? null;

	if ( $sitepress && is_object( $sitepress ) && method_exists( $sitepress, 'get_current_language' ) && method_exists( $sitepress, 'switch_lang' ) ) {
		$previous_language = $sitepress->get_current_language();
		$sitepress->switch_lang( 'all', true );
	}

	$_GET['lang'] = 'all';
	add_filter( 'wpml_current_language', $filter_function, 99 );

	try {
		return $callback();
	} finally {
		remove_filter( 'wpml_current_language', $filter_function, 99 );

		if ( $sitepress && is_object( $sitepress ) && method_exists( $sitepress, 'switch_lang' ) && null !== $previous_language ) {
			$sitepress->switch_lang( $previous_language, true );
		}

		if ( null === $previous_lang_get ) {
			unset( $_GET['lang'] );
		} else {
			$_GET['lang'] = $previous_lang_get;
		}

		// WPML language restoration can leave a lazy locale object behind, so repair it before later UI formatting.
		ai4seo_ensure_wp_locale_number_format();
	}
}


/**
 * Helper to return the WPML value for "all languages".
 *
 * @return string
 */
function ai4seo_wpml_return_all_languages(): string {
	return 'all';
}


// endregion
// ___________________________________________________________________________________________.
