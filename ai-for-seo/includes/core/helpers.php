<?php
/**
 * Provides shared sanitization, normalization, and utility helpers.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region UTILITY / HELPER FUNCTIONS ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Ensure WordPress number-format locale values are available before number formatting.
 *
 * WPML can temporarily replace the global WP_Locale object with a lazy locale instance whose
 * number_format property is still empty. WordPress core reads these array keys directly in
 * number_format_i18n(), so initialize the locale or add core defaults before formatting.
 *
 * @return void
 */
function ai4seo_ensure_wp_locale_number_format(): void {
	global $wp_locale;

	// Only WP_Locale-like objects can be initialized; CLI/tests without a locale should not fail here.
	if ( ! isset( $wp_locale ) || ! is_object( $wp_locale ) ) {
		return;
	}

	// Preserve fully initialized locale data, including locales that intentionally use an empty separator.
	if ( isset( $wp_locale->number_format )
		&& is_array( $wp_locale->number_format )
		&& array_key_exists( 'decimal_point', $wp_locale->number_format )
		&& array_key_exists( 'thousands_sep', $wp_locale->number_format ) ) {
		return;
	}

	// Trigger WPML/Core lazy locale initialization before applying this plugin's fallback defaults.
	if ( is_callable( array( $wp_locale, 'get_list_item_separator' ) ) ) {
        /** @noinspection PhpExpressionResultUnusedInspection */
		$wp_locale->get_list_item_separator();
	}

	// Normalize the field after lazy initialization so missing keys can be filled predictably.
	if ( ! isset( $wp_locale->number_format ) || ! is_array( $wp_locale->number_format ) ) {
		$wp_locale->number_format = array();
	}

	// Add only the core defaults that number_format_i18n() reads directly and only when absent.
	if ( ! array_key_exists( 'decimal_point', $wp_locale->number_format ) ) {
		$wp_locale->number_format['decimal_point'] = '.';
	}

	if ( ! array_key_exists( 'thousands_sep', $wp_locale->number_format ) ) {
		$wp_locale->number_format['thousands_sep'] = ',';
	}
}

/**
 * Format a number with WordPress locale handling protected against lazy locale state.
 *
 * @param float|int $number   The number to format.
 * @param int       $decimals Optional number of decimal places.
 * @return string Locale-formatted number.
 */
function ai4seo_format_number_i18n( $number, int $decimals = 0 ): string {
	// Protect every plugin-owned formatted number from WPML's temporarily incomplete locale state.
	ai4seo_ensure_wp_locale_number_format();

	return (string) number_format_i18n( $number, $decimals );
}


/**
 * Return whether a coverage value represents exactly one hundred percent.
 *
 * @param mixed $coverage_percentage Coverage value from calculations or persisted state.
 * @return bool Whether the value is a numeric representation of 100.
 */
function ai4seo_is_full_coverage_percentage( $coverage_percentage ): bool {
	// Accept calculated numbers and persisted numeric strings without general scalar coercion.
	return is_numeric( $coverage_percentage ) && 100.0 === (float) $coverage_percentage;
}


/**
 * Compare persisted values after explicitly selecting their legacy comparison domain.
 *
 * WordPress options can retain numeric strings, integers, and boolean-compatible values from
 * older form and API writes. This helper preserves those established equivalences while letting
 * storage callers use a strict final comparison and retain array key-order independence.
 *
 * @param mixed $first_value First persisted value.
 * @param mixed $second_value Second persisted value.
 * @return bool True when both values represent the same persisted state.
 */
function ai4seo_are_persisted_state_values_equivalent( $first_value, $second_value ): bool {
	// Unchanged values are overwhelmingly common and need no recursive normalization.
	if ( $first_value === $second_value ) {
		return true;
	}

	// PHP's boolean comparison domain converts both operands whenever either value is boolean.
	if ( is_bool( $first_value ) || is_bool( $second_value ) ) {
		return (bool) $first_value === (bool) $second_value;
	}

	// Preserve the narrower null domain instead of treating every falsy string as an empty value.
	if ( null === $first_value || null === $second_value ) {
		$non_null_value = null === $first_value ? $second_value : $first_value;

		if ( null === $non_null_value ) {
			return true;
		}

		if ( is_array( $non_null_value ) ) {
			return array() === $non_null_value;
		}

		if ( is_string( $non_null_value ) ) {
			return '' === $non_null_value;
		}

		if ( is_int( $non_null_value ) || is_float( $non_null_value ) ) {
			return 0.0 === (float) $non_null_value;
		}

		return false;
	}

	// Array persistence ignores insertion order but compares each matching key recursively.
	if ( is_array( $first_value ) || is_array( $second_value ) ) {
		if ( ! is_array( $first_value ) || ! is_array( $second_value ) || count( $first_value ) !== count( $second_value ) ) {
			return false;
		}

		foreach ( $first_value as $this_key => $this_value ) {
			if ( ! array_key_exists( $this_key, $second_value )
				|| ! ai4seo_are_persisted_state_values_equivalent( $this_value, $second_value[ $this_key ] ) ) {
				return false;
			}
		}

		return true;
	}

	// Compare integer representations without a float conversion that could collapse large IDs.
	$first_integer  = ai4seo_normalize_persisted_integer_for_comparison( $first_value );
	$second_integer = ai4seo_normalize_persisted_integer_for_comparison( $second_value );

	if ( null !== $first_integer && null !== $second_integer ) {
		return $first_integer === $second_integer;
	}

	// Decimal or exponent representations share a numeric domain; other scalars compare as strings.
	if ( is_numeric( $first_value ) && is_numeric( $second_value ) ) {
		return (float) $first_value === (float) $second_value;
	}

	if ( is_scalar( $first_value ) && is_scalar( $second_value ) ) {
		return (string) $first_value === (string) $second_value;
	}

	// Persisted objects and resources are unsupported state and only match by identity.
	return $first_value === $second_value;
}


/**
 * Normalize an integer or integer string for an exact storage comparison.
 *
 * @param mixed $value Candidate integer representation.
 * @return string|null Canonical signed integer, or null for another value domain.
 */
function ai4seo_normalize_persisted_integer_for_comparison( $value ): ?string {
	// Preserve native integers without passing large IDs through a lossy float domain.
	if ( is_int( $value ) ) {
		return (string) $value;
	}

	// Only legacy string representations participate in integer normalization.
	if ( ! is_string( $value ) ) {
		return null;
	}

	$value = trim( $value );

	// Reject decimal and exponent syntax before canonicalizing the sign and zero padding.
	if ( ! preg_match( '/^[+-]?[0-9]+$/', $value ) ) {
		return null;
	}

	$is_negative = '-' === $value[0];
	$digits      = ltrim( $value, '+-' );
	$digits      = ltrim( $digits, '0' );

	if ( '' === $digits ) {
		return '0';
	}

	return $is_negative ? '-' . $digits : $digits;
}


/**
 * Function to return the robhub api communicator
 *
 * @param bool $init_only The init only value.
 * @return Ai4Seo_RobHubApiCommunicator|null The robhub api communicator
 * @throws RuntimeException When the communicator file or class is unavailable.
 */
function ai4seo_robhub_api( $init_only = false ): ?Ai4Seo_RobHubApiCommunicator {
	global $ai4seo_robhub_api;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 232093142, 'Prevented loop', true );
		return null;
	}

	// init the robhub api communicator if not already done.
	if ( ! $ai4seo_robhub_api instanceof Ai4Seo_RobHubApiCommunicator ) {
		$ai4seo_robhub_api_path = ai4seo_get_includes_api_path( 'class-ai4seo-robhubapicommunicator.php' );

		if ( ! file_exists( $ai4seo_robhub_api_path ) ) {
			if ( $init_only ) {
				return null;
			}

			ai4seo_debug_message( 174300405, 'RobHub API communicator file missing at ' . $ai4seo_robhub_api_path, true );
			throw new RuntimeException( 'RobHub API communicator file missing.' );
		}

		require_once $ai4seo_robhub_api_path;

		if ( ! class_exists( 'Ai4Seo_RobHubApiCommunicator' ) ) {
			if ( $init_only ) {
				return null;
			}

			ai4seo_debug_message( 214019245, 'Failed to load Ai4Seo_RobHubApiCommunicator from ' . $ai4seo_robhub_api_path, true );
			throw new RuntimeException( 'Ai4Seo_RobHubApiCommunicator class not found after include.' );
		}

		$ai4seo_robhub_api = new Ai4Seo_RobHubApiCommunicator();
		$ai4seo_robhub_api->set_environmental_variables_option_name( AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
		$product_activation_time = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME );
		$ai4seo_robhub_api->set_product_parameters( 'ai4seo', AI4SEO_PLUGIN_VERSION_NUMBER, $product_activation_time );
		$does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();
		$ai4seo_robhub_api->set_does_user_need_to_accept_tos_toc_and_pp( $does_user_need_to_accept_tos_toc_and_pp );
		$ai4seo_robhub_api->is_initialized = true;
	}

	return $ai4seo_robhub_api;
}


/**
 * Return a fully sanitized array, using custom sanitize functions for both keys and values.
 *
 * @param array|string $data The array or value to be sanitized.
 * @param string       $sanitize_value_function_name The custom sanitize function for the values (default: sanitize_text_field).
 * @param string       $sanitize_key_function_name The custom sanitize function for the keys (default: sanitize_key).
 * @return array|string The sanitized array or value.
 */
function ai4seo_deep_sanitize( $data, string $sanitize_value_function_name = 'sanitize_text_field', string $sanitize_key_function_name = 'sanitize_key' ) {
	if ( ai4seo_prevent_loops( __FUNCTION__, 100, 99999 ) ) {
		ai4seo_debug_message( 549978452, 'Prevented loop', true );
		return is_array( $data ) ? array() : '';
	}

	if ( ! ai4seo_is_function_usable( $sanitize_value_function_name ) ) {
		ai4seo_debug_message( 54103126, 'Value sanitize function ' . $sanitize_value_function_name . ' is not usable', true );
		return is_array( $data ) ? array() : '';
	}

	if ( is_array( $data ) ) {
		$sanitized_data = array();
		foreach ( $data as $key => $value ) {
			// Sanitize the key using the key sanitize function.
			$sanitized_key = $sanitize_key_function_name( $key );

			// Recursively sanitize the value if it's an array, or sanitize the value using the value sanitize function.
			if ( is_array( $value ) ) {
				$sanitized_data[ $sanitized_key ] = ai4seo_deep_sanitize( $value, $sanitize_value_function_name, $sanitize_key_function_name );
			} elseif ( is_bool( $value ) ) {
					$sanitized_data[ $sanitized_key ] = $value;
			} else {
				$sanitized_data[ $sanitized_key ] = $sanitize_value_function_name( $value );
			}
		}
		return $sanitized_data;
	} else {
		if ( is_bool( $data ) ) {
			return $data;
		}

		// If it's not an array, sanitize the value directly.
		return $sanitize_value_function_name( $data );
	}
}


/**
 * Runs a callback while ignore_user_abort() is forced to true (unless WP-Cron already handles it).
 *
 * @param callable $callback             Callback to execute.
 * @param array    $callback_arguments   Arguments passed to the callback.
 * @param bool     $skip_for_cron_calls  Skip toggling ignore_user_abort() when running in WP-Cron.
 *
 * @return mixed The callback result.
 */
function ai4seo_run_with_ignore_user_abort( callable $callback, array $callback_arguments = array(), bool $skip_for_cron_calls = true ) {
	$previous_ignore_user_abort_state = null;
	$should_restore_ignore_user_abort = false;

	if ( ! $skip_for_cron_calls || ! wp_doing_cron() ) {
		$previous_ignore_user_abort_state = ignore_user_abort( true );
		$should_restore_ignore_user_abort = true;
	}

	try {
		return call_user_func_array( $callback, $callback_arguments );
	} finally {
		if ( $should_restore_ignore_user_abort ) {
			ignore_user_abort( $previous_ignore_user_abort_state );
		}
	}
}


/**
 * Function to prevent recursive loops
 *
 * @param string $function_name The name of the function to check.
 * @param int    $max_depth The maximum depth of recursion allowed (default 1, min 1).
 * @param int    $max_calls The maximum number of calls allowed globally (default 99999, min 1).
 * @return bool True if the loop should be prevented, false otherwise
 */
function ai4seo_prevent_loops( string $function_name, int $max_depth = 1, int $max_calls = 22222 ): bool {
	static $call_counts = array();

	if ( $max_depth < 1 ) {
		$max_depth = 1;
	}

	if ( $max_calls < 1 ) {
		$max_calls = 1;
	}

	// Initialize call count if not exists.
	if ( ! isset( $call_counts[ $function_name ] ) ) {
		$call_counts[ $function_name ] = 0;
	}

	// Increment global call count.
	++$call_counts[ $function_name ];

	// Check max calls.
	if ( $call_counts[ $function_name ] > $max_calls ) {
		return true;
	}

	// if $call_counts[$function_name] is less than $max_depth, we cannot have reached max depth yet.
	if ( $call_counts[ $function_name ] <= $max_depth ) {
		return false;
	}

	// Check recursion depth.
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- The runtime recursion guard requires the active call stack.
	$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	$depth     = 0;

	// Iterate through backtrace to count occurrences of the function.
	foreach ( $backtrace as $trace ) {
		if ( isset( $trace['function'] ) && $trace['function'] === $function_name ) {
			++$depth;
		}
	}

	// The current call is included in the backtrace, so depth is at least 1.
	// If max_depth is 1, we want to prevent ANY recursion (i.e., if depth > 1).
	// If max_depth is 2, we allow 2 recursive call (depth 2).
	// So we return true if depth > $max_depth.

	if ( $depth > $max_depth ) {
		return true;
	}

	return false;
}


/**
 * Function to simulate a singleton (only one call per function per id)
 *
 * @param mixed $id The id value.
 * @return bool
 */
function ai4seo_singleton( $id ): bool {
	return ! ai4seo_prevent_loops( $id, 1, 1 );
}


/**
 * Check whether plugin translations can be loaded without triggering WordPress early-load notices.
 *
 * @return bool True when translation functions are safe to call for this text domain.
 */
function ai4seo_are_translations_ready(): bool {
	return function_exists( 'did_action' ) && did_action( 'init' ) > 0;
}


/**
 * Given any text phrase that may not be suitable as a button or page label, this function will return a nice label
 *
 * @param string $text The text to be converted.
 * @param string $separator The separator value.
 * @return string The nice label
 */
function ai4seo_get_nice_label( string $text, $separator = ' ' ): string {
	// convert every _ to $separator.
	$text = str_replace( '_', $separator, $text );

	// explode by the separator.
	$text_array = explode( $separator, $text );

	// make every word start with a capital letter.
	$text_array = array_map( 'ucfirst', $text_array );

	// put the words back together.
	$text = implode( $separator, $text_array );

	// make some manual adjustments.
	$text = str_replace( array( 'Rss' ), array( 'RSS' ), $text );

	return $text;
}


// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Retain the PHP 8 named-argument contract.
/**
 * Return weather the given string is a valid json
 *
 * @param mixed $string The string value.
 * @return bool
 */
function ai4seo_is_json( $string ): bool {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
	if ( ! is_string( $string ) ) {
		return false;
	}

	// check if string starts with { or [.
	if ( '{' !== $string[0] && '[' !== $string[0] ) {
		return false;
	}

	json_decode( $string );

	return ( json_last_error() === JSON_ERROR_NONE );
}


/**
 * Removes double sentences from the given string
 *
 * @param mixed $input_string The input string value.
 * @return string
 */
function ai4seo_remove_double_sentences( $input_string ): string {
	// Split the input string into sentences using a regular expression.
	$sentences = preg_split( '/(?<=[.?!])\s+(?=[a-z])/i', $input_string );

	// Create an empty array to store unique sentences.
	$unique_sentences = array();

	// Loop through the sentences array and add unique sentences to the uniqueSentences array.
	foreach ( $sentences as $sentence ) {
		$trimmed_sentence = trim( $sentence );

		if ( ! in_array( $trimmed_sentence, $unique_sentences, true ) ) {
			$unique_sentences[] = $trimmed_sentence;
		}
	}

	// Join the unique sentences back into a single string.
	return implode( ' ', $unique_sentences );
}


/**
 * Truncate a string after a specified soft cap length, considering the first end of sentence
 * as the end of the input, with a hard cap on the length.
 *
 * @param string $input   The input string to be truncated.
 * @param int    $soft_cap The soft cap length after which to look for the end of a sentence.
 * @param int    $hard_cap The hard cap length to truncate the string if no sentence end is found.
 * @return string         The truncated string.
 */
function ai4seo_truncate_sentence( string $input, int $soft_cap, int $hard_cap = 0 ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 574839326, 'Prevented loop', true );
		return $input;
	}

	// Ensure the input length is within the limits.
	if ( ai4seo_mb_strlen( $input ) <= $soft_cap ) {
		return $input;
	}

	// if hard cap is less than soft cap, set hard cap to soft cap.
	if ( $hard_cap < $soft_cap ) {
		$hard_cap = $soft_cap;
	}

	// Start truncation from soft cap onwards.
	$truncated_at_hard_cap    = ai4seo_mb_substr( $input, 0, $hard_cap );
	$truncated_after_soft_cap = ai4seo_mb_substr( $truncated_at_hard_cap, $soft_cap );

	// Define sentence-ending punctuation marks.
	$punctuation_marks = array( '.', '!', '?', '…', '؟', '·', '。', '！', '？' );

	// Find the first sentence-ending punctuation after the soft cap.
	$first_sentence_after_soft_cap_end = PHP_INT_MAX;

	foreach ( $punctuation_marks as $mark ) {
		$position = ai4seo_mb_strpos( $truncated_after_soft_cap, $mark );

		if ( false !== $position ) {
			$first_sentence_after_soft_cap_end = min( $first_sentence_after_soft_cap_end, $position );
		}
	}

	// If an end of sentence is found, adjust the truncation to include it.
	if ( PHP_INT_MAX !== $first_sentence_after_soft_cap_end ) {
		$truncated_sentence = ai4seo_mb_substr( $truncated_at_hard_cap, 0, $soft_cap + $first_sentence_after_soft_cap_end + 1 );
	} else {
		// If no sentence end is found, ensure the truncation is at hard cap.
		$truncated_sentence = $truncated_at_hard_cap;
	}

	return $truncated_sentence;
}


/**
 * Returns the public plugin name used in plugin UI copy.
 *
 * @return string The plugin name.
 */
function ai4seo_get_plugin_name(): string {
	return AI4SEO_PLUGIN_NAME;
}


/**
 * Returns the plugin basename
 *
 * @return string The plugin basename
 */
function ai4seo_get_plugin_basename(): string {
	return sanitize_text_field( plugin_basename( AI4SEO_PLUGIN_FILE ) );
}


/**
 * Returns a url leading to a point within the plugin
 *
 * @param string $sub_page The page to navigate to.
 * @param array  $additional_parameter Additional parameters to add to the url.
 * @param bool   $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo).
 * @return string The plugins admin sub page url
 */
function ai4seo_get_subpage_url( string $sub_page = '', array $additional_parameter = array(), bool $return_full_path = true ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 688861817, 'Prevented loop', true );
		return '';
	}

	$sub_page = sanitize_key( $sub_page );

	if ( $return_full_path ) {
		$page_url = admin_url( 'admin.php' );
	} else {
		$page_url = '';
	}

	$page_url = ai4seo_add_query_arg( 'page', AI4SEO_PLUGIN_IDENTIFIER, $page_url );

	// workaround: if page is dashboard, remove it from the url.
	if ( 'dashboard' === $sub_page ) {
		$sub_page = '';
	}

	// add subpage if set.
	if ( $sub_page ) {
		$page_url = ai4seo_add_query_arg( 'ai4seo_subpage', $sub_page, $page_url );
	}

	// add additional parameters if set.
	if ( $additional_parameter ) {
		foreach ( $additional_parameter as $this_key => $this_value ) {
			$page_url = ai4seo_add_query_arg( $this_key, $this_value, $page_url );
		}
	}

	// sanitize the url if we want the full path.
	if ( $return_full_path ) {
		$page_url = esc_url_raw( $page_url );
	}

	$page_url = str_replace( '&#038;', '&', $page_url );
	$page_url = str_replace( '#038;', '&', $page_url );
	$page_url = html_entity_decode( $page_url, ENT_QUOTES );

	return $page_url;
}


/**
 * Add a sanitized query argument while preserving pagination placeholders.
 *
 * @param string|int $key Query argument key.
 * @param mixed      $value Query argument value.
 * @param string     $url Base URL.
 * @return string URL with the sanitized query argument.
 */
function ai4seo_add_query_arg( $key, $value, $url ): string {
	$key   = sanitize_key( $key );
	$value = sanitize_text_field( $value );

	// preserve %#% placeholder during add_query_arg.
	if ( strpos( $url, '%#%' ) !== false ) {
		$url = str_replace( '%#%', 'AI4SEO_PAGE_PLACEHOLDER', $url );
	}

	$url = add_query_arg( $key, $value, $url );

	// restore %#%.
	$url = str_replace( 'AI4SEO_PAGE_PLACEHOLDER', '%#%', $url );

	return $url;
}


/**
 * Returns the url to a specific post type within the AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME array
 *
 * @param string $post_type The post type to navigate to.
 * @param int    $current_page The current page to navigate to.
 * @param array  $additional_parameter Additional parameters to add to the url.
 * @param bool   $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo&ai4seo_subpage=post&ai4seo_post_type=post).
 * @return string The url to the post type
 */
function ai4seo_get_post_type_page_url( string $post_type, int $current_page = 1, array $additional_parameter = array(), bool $return_full_path = true ): string {
	// Preserve the pagination placeholder when callers do not provide a concrete page.
	$additional_parameter['ai4seo_page'] = $current_page ? $current_page : '%#%';

	return ai4seo_get_subpage_url(
		AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME,
		array( 'ai4seo_post_type' => $post_type ) + $additional_parameter,
		$return_full_path
	);
}


/**
 * Normalize malformed ampersand entities in pagination links.
 *
 * @param string $pagination_links Pagination link markup.
 * @return string|null Normalized pagination link markup, or null if replacement fails.
 */
function ai4seo_normalize_pagination_links( $pagination_links ) {
	// Normalize broken ampersand entities in query strings.
	// This:
	// href="...page=ai-for-seo#038;ai4seo_subpage=media&#038;ai4seo_page=2"
	// becomes:
	// href="...page=ai-for-seo&ai4seo_subpage=media&ai4seo_page=2".
	$pagination_links = preg_replace(
		'/&(?:amp;)?#038;|#038;/',
		'&',
		$pagination_links
	);

	return $pagination_links;
}


/**
 * Returns whether the user is inside our plugin's admin pages
 *
 * @return bool Whether the user is inside our plugin's admin pages
 */
function ai4seo_is_user_inside_our_plugin_admin_pages(): bool {
	// check if the "page" parameter is set and if it is our plugin.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state is changed.
	return is_admin() && isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === AI4SEO_PLUGIN_IDENTIFIER;
}


/**
 * Checks if the active page is the given page
 *
 * @param string $plugin_page The page to check.
 * @return bool Whether the active page is the given page
 */
function ai4seo_is_plugin_page_active( string $plugin_page = '' ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 585904324, 'Prevented loop', true );
		return false;
	}

	$plugin_page        = sanitize_key( $plugin_page );
	$active_plugin_page = ai4seo_get_active_subpage();

	// check if we are inside the plugins admin pages (page should be ai-for-seo).
	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return false;
	}

	// Dashboard: both "dashboard" and empty are considered dashboard.
	if ( ! $plugin_page ) {
		$plugin_page = 'dashboard';
	}

	if ( ! $active_plugin_page ) {
		$active_plugin_page = 'dashboard';
	}

	return $active_plugin_page === $plugin_page;
}


/**
 * Returns whether the current AJAX action may run dashboard background tasks.
 *
 * @return bool
 */
function ai4seo_is_dashboard_background_task_ajax_request(): bool {
	if ( wp_doing_ajax() === false ) {
		return false;
	}

	$ajax_action = '';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing metadata only; this helper returns true only after the global AJAX nonce gate succeeds.
	if ( isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing metadata only; this helper returns true only after the global AJAX nonce gate succeeds.
		$ajax_action = sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) );
	}

	if ( ! in_array( $ajax_action, array( 'ai4seo_get_dashboard_html', 'ai4seo_refresh_dashboard_statistics' ), true ) ) {
		return false;
	}

	// ai4seo_ajax_security_gate() sets this only after nonce and permission checks passed.
	return ! empty( $GLOBALS['ai4seo_ajax_nonce'] );
}


/**
 * Returns whether the current AJAX action is the dashboard auto-refresh request.
 *
 * @return bool
 */
function ai4seo_is_dashboard_refresh_ajax_request(): bool {
	if ( wp_doing_ajax() === false ) {
		return false;
	}

	$ajax_action = '';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing metadata only; this helper returns true only after the global AJAX nonce gate succeeds.
	if ( isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing metadata only; this helper returns true only after the global AJAX nonce gate succeeds.
		$ajax_action = sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) );
	}

	if ( 'ai4seo_get_dashboard_html' !== $ajax_action ) {
		return false;
	}

	// ai4seo_ajax_security_gate() sets this only after nonce and permission checks passed.
	return ! empty( $GLOBALS['ai4seo_ajax_nonce'] );
}


/**
 * Returns whether dashboard/cron-only background tasks may run in the current request.
 *
 * @return bool
 */
function ai4seo_can_run_dashboard_or_cron_tasks(): bool {
	if ( wp_doing_cron() ) {
		return true;
	}

	if ( ! ai4seo_can_administer_plugin() ) {
		return false;
	}

	if ( ! ai4seo_is_function_usable( 'is_admin' ) || ! is_admin() ) {
		return false;
	}

	if ( ! ai4seo_is_plugin_page_active( 'dashboard' ) && ! ai4seo_is_dashboard_background_task_ajax_request() ) {
		return false;
	}

	return true;
}


/**
 * Returns whether the current request may start a posts table analysis.
 *
 * @param bool $allow_trusted_admin_mutation Whether trusted admin mutation requests may start analysis.
 * @return bool
 */
function ai4seo_can_start_posts_table_analysis( bool $allow_trusted_admin_mutation = false ): bool {
	// Keep the original dashboard/cron gate as the normal automatic analysis start path.
	if ( ai4seo_can_run_dashboard_or_cron_tasks() ) {
		return true;
	}

	// Admin mutation starts are opt-in so background work stays dashboard/cron-scoped by default.
	if ( ! $allow_trusted_admin_mutation ) {
		return false;
	}

	// Reuse the plugin's existing management capability check before trusting an admin mutation.
	if ( ! ai4seo_can_administer_plugin() ) {
		return false;
	}

	// Mutation-triggered starts should only happen from wp-admin, including admin AJAX.
	if ( ! ai4seo_is_function_usable( 'is_admin' ) || ! is_admin() ) {
		return false;
	}

	// AJAX mutation callers pass through the global AI4SEO nonce gate before reaching this helper.
	if ( wp_doing_ajax() && wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		return false;
	}

	return true;
}


/**
 * Checks, if the current post type is the given post type
 *
 * @param string $post_type The post type to check.
 * @return bool Whether the current post type is the given post type
 */
function ai4seo_is_post_type_open( string $post_type ): bool {
	$current_post_type = ai4seo_get_active_post_type_subpage();
	return $current_post_type === $post_type;
}


/**
 * Returns the active page (admin url page)
 *
 * @return string The active page
 */
function ai4seo_get_active_subpage(): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 332066427, 'Prevented loop', true );
		return '';
	}

	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return '';
	}

	// workaround: amp; is added to the url when the user is redirected from stripe.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state is changed.
	$potential_subpage = sanitize_key( $_GET['ai4seo_subpage'] ?? $_GET['amp;ai4seo_subpage'] ?? $_GET['ai4seo-tab'] ?? $_GET['amp;ai4seo-tab'] ?? '' );

	if ( ! $potential_subpage ) {
		$potential_subpage = ai4seo_get_default_subpage();
	}

	return $potential_subpage;
}


/**
 * Returns the active post type page
 *
 * @return string The active post type page
 */
function ai4seo_get_active_post_type_subpage(): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 605553019, 'Prevented loop', true );
		return '';
	}

	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return '';
	}

	if ( ai4seo_get_active_subpage() !== 'post' ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation parameter; no state is changed.
	return sanitize_key( $_GET['ai4seo_post_type'] ?? ai4seo_get_default_post_type() );
}


/**
 * Returns the default page (dashboard)
 *
 * @return string The default page
 */
function ai4seo_get_default_subpage(): string {
	return 'dashboard';
}


/**
 * Returns the default post type
 *
 * @return string The default post type
 */
function ai4seo_get_default_post_type(): string {
	return 'page';
}


/**
 * Returns the plugin directory path
 *
 * @param string $sub_path The sub path to append to the plugin directory path (optional).
 * @return string The plugin directory path
 */
function ai4seo_get_plugin_dir_path( string $sub_path = '' ): string {
	return plugin_dir_path( AI4SEO_PLUGIN_FILE ) . $sub_path;
}


/**
 * Returns the plugins base urls
 *
 * @param string $sub_path The sub path to append to the plugins base url (optional).
 * @return string The url to the file
 */
function ai4seo_get_plugins_url( string $sub_path = '' ): string {
	return plugins_url( $sub_path, AI4SEO_PLUGIN_FILE );
}


/**
 * Returns the path to includes/modals
 *
 * @param string $sub_path The sub path to append to the includes/modals path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_modal_schemas_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/modal_schemas/{$sub_path}" );
}


/**
 * Returns the path to includes/pages
 *
 * @param string $sub_path The sub path to append to the includes/pages path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/pages/{$sub_path}" );
}


/**
 * Returns the path to includes/pages/content_types
 *
 * @param string $sub_path The sub path to append to the includes/pages/content_types path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_content_types_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/pages/content_types/{$sub_path}" );
}


/**
 * Returns the path to includes/ajax/display
 *
 * @param string $sub_path The sub path to append to the includes/ajax/display path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_display_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/ajax/display/{$sub_path}" );
}


/**
 * Returns the path to includes/ajax/process
 *
 * @param string $sub_path The sub path to append to the includes/ajax/process path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_process_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/ajax/process/{$sub_path}" );
}


/**
 * Returns the path to includes/elements
 *
 * @param string $sub_path The sub path to append to the includes/elements path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_elements_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/elements/{$sub_path}" );
}


/**
 * Returns the path to includes/api
 *
 * @param string $sub_path The sub path to append to the includes/api path (optional).
 * @return string The path to the file
 */
function ai4seo_get_includes_api_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/api/{$sub_path}" );
}


/**
 * Returns the url to assets/images
 *
 * @param string $file_name The name of the file to get the path for.
 * @return string The url to the file
 */
function ai4seo_get_assets_images_url( $file_name = '' ): string {
	return ai4seo_get_plugins_url( "assets/images/{$file_name}" );
}


/**
 * Returns the url to assets/css
 *
 * @param string $file_name The name of the file to get the path for.
 * @return string The url to the file
 */
function ai4seo_get_assets_css_path( string $file_name = '' ): string {
	return ai4seo_get_plugins_url( "assets/css/{$file_name}" );
}


/**
 * Returns the url to assets/js
 *
 * @param string $file_name The name of the file to get the path for.
 * @return string The url to the file
 */
function ai4seo_get_assets_js_path( string $file_name ): string {
	return ai4seo_get_plugins_url( "assets/js/{$file_name}" );
}


/**
 * Returns the url to the SOOZ logo
 *
 * @param string $variant The variant of the logo to get the url for.
 * @return string The url to the file
 */
function ai4seo_get_sooz_logo_url( string $variant = '32x32' ): string {
	switch ( $variant ) {
		case 'sooz':
			return ai4seo_get_assets_images_url( 'logos/sooz.svg' );
		case 'sooz-oo':
			return ai4seo_get_assets_images_url( 'logos/sooz-oo.svg' );
		case 'sooz-with-ai-for-seo':
			return ai4seo_get_assets_images_url( 'logos/sooz-with-ai-for-seo.svg' );
		case 'svg':
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo.svg' );
		case 'full':
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo-full-logo.png' );
		case '64x64':
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo-logo-64x64.png' );
		case '256x256':
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo-logo-256x256.png' );
		case '512x512-animated':
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo-logo-animated-512x512.gif' );
		case 'sooz-32x32':
			return ai4seo_get_assets_images_url( 'logos/sooz-ai-for-seo-logo-32x32.jpg' );
		case 'sooz-64x64':
			return ai4seo_get_assets_images_url( 'logos/sooz-ai-for-seo-logo-64x64.jpg' );
		case 'sooz-256x256':
			return ai4seo_get_assets_images_url( 'logos/sooz-ai-for-seo-logo-256x256.jpg' );
		case '32x32':
		default:
			return ai4seo_get_assets_images_url( 'logos/ai-for-seo-logo-32x32.png' );
	}
}


/**
 * This function uses wp_kses with our collection of allowed html tags and attributes
 *
 * @param string $content The content to sanitize.
 * @return string The sanitized content
 */
function ai4seo_wp_kses( string $content ): string {
	$allowed_html_tags_and_attributes = ai4seo_get_allowed_html_tags_and_attributes();

	return wp_kses( $content, $allowed_html_tags_and_attributes );
}


/**
 * Echoes the sanitized content using ai4seo_wp_kses.
 *
 * @param string $content The content to sanitize and echo.
 * @return void
 */
function ai4seo_echo_wp_kses( string $content ): void {
	$allowed_html_tags_and_attributes = ai4seo_get_allowed_html_tags_and_attributes();

	echo wp_kses( $content, $allowed_html_tags_and_attributes );
}


/**
 * This function retrieves the language code of the WordPress installation as defined in the settings
 *
 * @return string The language code of the WordPress installation
 */
function ai4seo_get_wordpress_language_code(): string {
	return get_bloginfo( 'language' );
}


/**
 * This function retrieves the language of the WordPress installation as defined in the settings
 *
 * @return string The language of the WordPress installation
 */
function ai4seo_get_wordpress_language(): string {
	$wordpress_language_code = ai4seo_get_wordpress_language_code();
	return ai4seo_get_language_long_version( $wordpress_language_code );
}


/**
 * This functions returns the long version of a given language short version (de_DE -> german)
 *
 * @param string $language_short_version The short version of the language.
 * @param string $value_on_undefined The value to return if the language is not found.
 * @return string The long version of the language
 */
function ai4seo_get_language_long_version( string $language_short_version, string $value_on_undefined = AI4SEO_DEFAULT_FALLBACK_LANGUAGE ): string {
	// Normalize the short code by converting it to lowercase.
	$language_short_version = strtolower( $language_short_version );

	// Check for a full language code match first.
	if ( isset( AI4SEO_FULL_LANGUAGE_CODE_MAPPING[ $language_short_version ] ) ) {
		return AI4SEO_FULL_LANGUAGE_CODE_MAPPING[ $language_short_version ];
	}

	// Fall back to checking the base language code (first two letters).
	$language_base = substr( $language_short_version, 0, 2 );
	return AI4SEO_BASE_LANGUAGE_CODE_MAPPING[ $language_base ] ?? $value_on_undefined;
}


/**
 * Check if a PHP function is usable (defined and not disabled).
 *
 * @param string $function_name The name of the function to check.
 * @return bool Returns true if the function is usable, false otherwise.
 */
function ai4seo_is_function_usable( string $function_name ): bool {
	if ( ! function_exists( $function_name ) ) {
		return false;
	}

	$disabled_functions = ini_get( 'disable_functions' );

	if ( ! $disabled_functions ) {
		return true;
	}

	// Normalize php.ini's comma-separated entries before the exact function-name comparison.
	$disabled_functions = array_map( 'trim', explode( ',', $disabled_functions ) );

	return ! in_array( $function_name, $disabled_functions, true );
}


/**
 * Convert seconds into HH:MM:SS format.
 *
 * @param int $seconds The total number of seconds to convert.
 * @return string The formatted time in HH:MM:SS or "D days and HH:MM:SS" format.
 */
function ai4seo_format_seconds_to_hhmmss_or_days_hhmmss( int $seconds ): string {
	// Ensure the seconds are non-negative.
	$seconds = max( 0, $seconds );

	// Calculate hours, minutes, and seconds.
	$hours             = floor( $seconds / 3600 );
	$minutes           = floor( ( $seconds % 3600 ) / 60 );
	$remaining_seconds = $seconds % 60;

	if ( $hours >= 24 ) {
		$formatted_duration = sprintf(
			/* translators: 1: Number of days. 2: Hours. 3: Minutes. 4: Seconds. */
			esc_html__( '%1$s days %2$02d:%3$02d:%4$02d', 'ai-for-seo' ),
			ai4seo_format_number_i18n( (int) floor( $hours / 24 ) ),
			$hours % 24,
			$minutes,
			$remaining_seconds
		);
	} else {
		// Format the result as HH:MM:SS.
		$formatted_duration = sprintf( '%02d:%02d:%02d', $hours, $minutes, $remaining_seconds );
	}

	return $formatted_duration;
}


/**
 * Calculate the difference in seconds between the current Unix timestamp and a given UTC timestamp.
 *
 * @param int $utc_timestamp The UTC timestamp to compare.
 * @return int The difference in seconds. Positive if the UTC timestamp is in the future, negative if in the past.
 */
function ai4seo_get_time_difference_in_seconds( int $utc_timestamp ): int {
	// Unix timestamps represent absolute instants, so direct subtraction remains timezone-invariant.
	return $utc_timestamp - time();
}


/**
 * Function returns the users formatted time, based on a unix timestamp
 *
 * @param int    $unix_timestamp The unix timestamp to format.
 * @param string $date_format    The date format to use (auto, default: date_format).
 * @param string $time_format    The time format to use (auto, default: time_format).
 * @param string $separator      The separator to use (default: ' ').
 * @param string $timezone       The timezone to use (auto, default: timezone_string).
 *
 * @return string
 */
function ai4seo_format_unix_timestamp( int $unix_timestamp, string $date_format = 'auto', string $time_format = 'auto', string $separator = ' ', string $timezone = 'auto' ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 942137684, 'Prevented loop', true );
		return strval( $unix_timestamp );
	}

	$final_format   = '';
	$date_auto_miss = false;

	if ( 'auto-miss' === $date_format ) {
		$date_auto_miss = true;
		$date_format    = 'auto';
	}

	// add date format.
	if ( $date_format ) {
		if ( 'auto' === $date_format ) {
			// use plugin option with fallback.
			$date_format = get_option( 'date_format', 'Y-m-d' );

			if ( ! $date_format ) {
				$date_format = 'Y-m-d';
			}
		}

		$final_format .= sanitize_text_field( $date_format );
	}

	// add time format.
	if ( $time_format ) {
		if ( 'auto' === $time_format ) {
			// use plugin option with fallback.
			$time_format = get_option( 'time_format', 'H:i' );

			if ( ! $time_format ) {
				$time_format = 'H:i';
			}
		}

		// separator if we already have a date format.
		if ( $date_format ) {
			$final_format .= $separator;
		}

		$final_format .= sanitize_text_field( $time_format );
	}

	if ( ! $final_format ) {
		// nothing to format, return empty string.
		return '';
	}

	// Get the WordPress timezone.
	if ( 'auto' === $timezone ) {
		// use plugin option with fallback to UTC.
		$timezone = get_option( 'timezone_string', 'UTC' );
	}

	// If no valid timezone is set, default to UTC.
	if ( ! $timezone ) {
		// Use safe UTC format as fallback.
		return ai4seo_gmdate( $final_format, $unix_timestamp );
	}

	try {
		// auto-miss: omit date if timestamp is today (use timezone-aware comparison).
		if ( $date_auto_miss ) {
			try {
				$now_datetime_object  = new DateTime( 'now', new DateTimeZone( $timezone ) );
				$this_datetime_object = new DateTime( '@' . $unix_timestamp );
				$this_datetime_object->setTimezone( new DateTimeZone( $timezone ) );

				if ( $now_datetime_object->format( 'Y-m-d' ) === $this_datetime_object->format( 'Y-m-d' ) ) {
					$final_format = sanitize_text_field( $time_format );
				}
			} catch ( Exception $exception ) {
				// Invalid timezone/date combinations fall through to the full date-formatting path below.
				unset( $exception );
			}
		}

		// Create a DateTime object with the UTC timestamp.
		$datetime_object = new DateTime( '@' . $unix_timestamp ); // The @ symbol treats the timestamp as UNIX time.
		$datetime_object->setTimezone( new DateTimeZone( $timezone ) ); // Set to WordPress timezone.
	} catch ( Exception $e ) {
		// Use safe UTC format as fallback.
		return ai4seo_gmdate( $final_format, $unix_timestamp );
	}

	// Format and return the time in the desired format.
	return $datetime_object->format( $final_format );
}


/**
 * Formats a recent execution as elapsed time and older executions as an absolute timestamp.
 *
 * @param int $execution_timestamp Unix timestamp of the execution.
 * @return string Localized execution-time text.
 */
function ai4seo_get_last_execution_time_text( int $execution_timestamp ): string {
	// Missing timestamps do not provide enough information for a useful status message.
	if ( $execution_timestamp <= 0 ) {
		return '';
	}

	// Recent executions are easier to scan as elapsed time than as an absolute timestamp.
	$execution_age = time() - $execution_timestamp;

	// Keep sub-minute execution messages precise because these typically follow a manual action.
	if ( $execution_age >= 0 && $execution_age < MINUTE_IN_SECONDS ) {
		return sprintf(
			esc_html(
				/* translators: %s: Number of seconds since the last execution. */
				_n(
					'Last execution was %s second ago.',
					'Last execution was %s seconds ago.',
					$execution_age,
					'ai-for-seo'
				)
			),
			ai4seo_format_number_i18n( $execution_age )
		);
	}

	// Collapse recent minute-level durations to whole units to keep the dashboard message compact.
	if ( $execution_age >= MINUTE_IN_SECONDS && $execution_age < HOUR_IN_SECONDS ) {
		$elapsed_minutes = (int) floor( $execution_age / MINUTE_IN_SECONDS );

		return sprintf(
			esc_html(
				/* translators: %s: Number of minutes since the last execution. */
				_n(
					'Last execution was %s minute ago.',
					'Last execution was %s minutes ago.',
					$elapsed_minutes,
					'ai-for-seo'
				)
			),
			ai4seo_format_number_i18n( $elapsed_minutes )
		);
	}

	// Collapse same-day hour-level durations to whole units for consistency with minute-level output.
	if ( $execution_age >= HOUR_IN_SECONDS && $execution_age < DAY_IN_SECONDS ) {
		$elapsed_hours = (int) floor( $execution_age / HOUR_IN_SECONDS );

		return sprintf(
			esc_html(
				/* translators: %s: Number of hours since the last execution. */
				_n(
					'Last execution was %s hour ago.',
					'Last execution was %s hours ago.',
					$elapsed_hours,
					'ai-for-seo'
				)
			),
			ai4seo_format_number_i18n( $elapsed_hours )
		);
	}

	// Older or future-dated executions use the established WordPress-aware timestamp formatter.
	return sprintf(
		/* translators: %s: Last execution time. */
		esc_html__( 'Last execution was on %s.', 'ai-for-seo' ),
		esc_html( ai4seo_format_unix_timestamp( $execution_timestamp, 'auto-miss' ) )
	);
}


/**
 * Validate a database DATETIME value against the MySQL-supported range and exact format.
 *
 * @param string $datetime Database DATETIME value.
 * @return bool True when the value is safe for a MySQL DATETIME comparison.
 */
function ai4seo_is_valid_mysql_datetime( string $datetime ): bool {
	// Reserve captures for the exact-format check and the subsequent typed component validation.
	$datetime_parts = array();

	// Require the exact SQL literal shape before interpreting any calendar or time component.
	if ( 1 !== preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/D', $datetime, $datetime_parts ) ) {
		return false;
	}

	// Convert the captured components once so the calendar and range checks remain explicit.
	$year   = (int) $datetime_parts[1];
	$month  = (int) $datetime_parts[2];
	$day    = (int) $datetime_parts[3];
	$hour   = (int) $datetime_parts[4];
	$minute = (int) $datetime_parts[5];
	$second = (int) $datetime_parts[6];

	// Enforce MySQL's year boundary together with real calendar and clock component ranges.
	return $year >= 1000
		&& $year <= 9999
		&& checkdate( $month, $day, $year )
		&& $hour <= 23
		&& $minute <= 59
		&& $second <= 59;
}

/**
 * Safely wrap gmdate() and provide fallbacks if gmdate is unavailable.
 *
 * @param string $format         The date/time format.
 * @param int    $unix_timestamp The UNIX timestamp.
 *
 * @return string
 */
function ai4seo_gmdate( string $format, int $unix_timestamp = 0 ): string {
	$unix_timestamp = (int) $unix_timestamp;

	if ( $unix_timestamp <= 0 ) {
		$unix_timestamp = time();
	}

	if ( '' === $format ) {
		$format = 'Y-m-d H:i:s';
	}

	if ( function_exists( 'gmdate' ) ) {
		return gmdate( $format, $unix_timestamp );
	}

	try {
		$datetime_object = new DateTimeImmutable( '@' . $unix_timestamp );
		$datetime_object = $datetime_object->setTimezone( new DateTimeZone( 'UTC' ) );

		return $datetime_object->format( $format );
	} catch ( Exception $e ) {
		// Fallback to date() in UTC if anything goes wrong.
		return date( $format, $unix_timestamp );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}
}


/**
 * Resolve a DateTimeZone based on plugin/WordPress settings or a given timezone string.
 *
 * Note: Currently not used by ai4seo_format_unix_timestamp(), but kept as a helper.
 *
 * @param string $timezone Timezone identifier or 'auto'.
 *
 * @return DateTimeZone
 */
function ai4seo_get_timezone( string $timezone = 'auto' ): DateTimeZone {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 381267591, 'Prevented loop', true );
		return new DateTimeZone( 'UTC' );
	}

	$timezone_string = '';

	if ( 'auto' === $timezone || '' === $timezone ) {
		// 1) Try plugin option.
		$timezone_string = get_option( 'timezone_string', '' );

		// 2) Fallback: wp_timezone_string() if available (WP 5.3+).
		if ( ! is_string( $timezone_string ) || '' === $timezone_string ) {
			if ( function_exists( 'wp_timezone_string' ) ) {
				$timezone_string = wp_timezone_string();
			}
		}

		// 4) Fallback: build from gmt_offset if still empty.
		if ( ! is_string( $timezone_string ) || '' === $timezone_string ) {
			$gmt_offset = ai4seo_get_option( 'gmt_offset' );

			if ( is_numeric( $gmt_offset ) && 0.0 !== (float) $gmt_offset ) {
				$timezone_string = timezone_name_from_abbr( '', (float) $gmt_offset * HOUR_IN_SECONDS, 0 );

				if ( false === $timezone_string ) {
					// Last-resort mapping for fixed offsets (Etc/GMT has reversed sign).
					$timezone_string = sprintf( 'Etc/GMT%+d', (int) - $gmt_offset );
				}
			}
		}
	} else {
		$timezone_string = sanitize_text_field( $timezone );
	}

	if ( ! is_string( $timezone_string ) || '' === $timezone_string ) {
		$timezone_string = 'UTC';
	}

	try {
		return new DateTimeZone( $timezone_string );
	} catch ( Exception $e ) {
		return new DateTimeZone( 'UTC' );
	}
}


/**
 * Function to convert datetime-local format to unix timestamp
 *
 * @param string $datetime_local The datetime-local string (YYYY-MM-DDTHH:MM).
 * @param string $timezone The timezone to use (auto, default: timezone_string).
 * @return int The unix timestamp
 */
function ai4seo_convert_datetime_local_to_timestamp( string $datetime_local, string $timezone = 'auto' ): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 858347572, 'Prevented loop', true );
		return 0;
	}

	// Get the WordPress timezone.
	if ( 'auto' === $timezone ) {
		$timezone = get_option( 'timezone_string' );
	}

	// If no valid timezone is set, default to UTC.
	if ( ! $timezone ) {
		return strtotime( $datetime_local . ' UTC' ); // Treat as UTC if no timezone.
	}

	try {
		// Create DateTime object from the local datetime string in the specified timezone.
		$datetime_object = new DateTime( $datetime_local, new DateTimeZone( $timezone ) );
		return $datetime_object->getTimestamp();
	} catch ( Exception $e ) {
		// Fallback: treat as UTC.
		return strtotime( $datetime_local . ' UTC' );
	}
}


/**
 * Function to deactivate AI for SEO
 *
 * @return bool Whether the plugin was deactivated
 */
function ai4seo_deactivate_plugin(): bool {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	// Check if the user has the required permissions.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return false;
	}

	// Deactivate the plugin.
	try {
		deactivate_plugins( ai4seo_get_plugin_basename() );
	} catch ( Exception $e ) {
		return false;
	}

	return true;
}


/**
 * Function to return the clients ip
 *
 * @return string The clients ip
 */
function ai4seo_get_client_ip(): string {
	$remote_address = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';

	if ( ! ai4seo_is_valid_ip( $remote_address ) ) {
		return '';
	}

	/**
	 * Filters the exact proxy IP addresses whose forwarding headers may be trusted.
	 *
	 * @param array $trusted_proxy_ips Trusted IPv4 or IPv6 addresses.
	 */
	$trusted_proxy_ips = apply_filters( 'ai4seo_trusted_proxy_ips', array() );
	$trusted_proxy_ips = is_array( $trusted_proxy_ips )
		? array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $trusted_proxy_ip ): string {
							return is_string( $trusted_proxy_ip ) ? trim( $trusted_proxy_ip ) : '';
						},
						$trusted_proxy_ips
					),
					'ai4seo_is_valid_ip'
				)
			)
		)
		: array();

	// Forwarding headers are client-controlled unless the immediate peer is explicitly trusted.
	if ( ! in_array( $remote_address, $trusted_proxy_ips, true )
		|| ! isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
	) {
		return $remote_address;
	}

	$forwarded_addresses = explode(
		',',
		sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	);

	// Walk from the trusted side and stop at the first address outside the configured proxy chain.
	for ( $address_index = count( $forwarded_addresses ) - 1; $address_index >= 0; --$address_index ) {
		$forwarded_address = trim( $forwarded_addresses[ $address_index ] );

		if ( ! ai4seo_is_valid_ip( $forwarded_address ) ) {
			return $remote_address;
		}

		if ( ! in_array( $forwarded_address, $trusted_proxy_ips, true ) ) {
			return $forwarded_address;
		}
	}

	return $remote_address;
}


/**
 * Function to return the clients user agent
 *
 * @return string The clients user agent
 */
function ai4seo_get_client_user_agent(): string {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
}


/**
 * Function to return the webservers ip
 *
 * @return string The webservers ip.
 */
function ai4seo_get_server_ip(): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 863637103, 'Prevented loop', true );
		return '';
	}

	if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
		$server_ip = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );

		if ( ai4seo_is_valid_ip( $server_ip ) ) {
			return $server_ip;
		}
	}

	return '';
}


/**
 * Function to check if the given string is a valid ip address
 *
 * @param string $ip The ip to check.
 * @return bool Whether the given string is a valid ip address
 */
function ai4seo_is_valid_ip( string $ip ): bool {
	return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
}


/**
 * Validate a decoded serialized value without traversing an unbounded reference graph.
 *
 * @param mixed $value           Decoded value to inspect.
 * @param int   $depth           Current traversal depth.
 * @param int   $remaining_nodes Remaining value nodes permitted during traversal.
 * @return bool Whether the decoded value contains only safe scalar and array values.
 */
function ai4seo_is_safe_unserialized_value( $value, int $depth, int &$remaining_nodes ): bool {
	// Cyclic references and oversized structures must fail closed before exhausting the request.
	if ( 32 < $depth || 0 >= $remaining_nodes ) {
		return false;
	}

	--$remaining_nodes;

	// Classes remain incomplete when decoding is restricted, but they are still object values.
	if ( is_object( $value ) || is_resource( $value ) ) {
		return false;
	}

	if ( ! is_array( $value ) ) {
		return true;
	}

	foreach ( $value as $nested_value ) {
		if ( ! ai4seo_is_safe_unserialized_value( $nested_value, $depth + 1, $remaining_nodes ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Detect serialized enum tokens without matching enum-like text inside scalar strings.
 *
 * PHP can invoke an autoloader for enum tokens even when unserialize() disallows classes.
 * Serialized strings therefore need to be skipped according to their declared byte length.
 *
 * @param string $serialized_value Serialized value to inspect.
 * @return bool Whether the serialized grammar contains an enum token.
 */
function ai4seo_serialized_value_contains_enum_token( string $serialized_value ): bool {
	$serialized_length = strlen( $serialized_value );
	$offset            = 0;

	while ( $offset < $serialized_length ) {
		// Value tokens begin at the root or after another serialized value or container boundary.
		$is_token_boundary = 0 === $offset
			|| in_array( $serialized_value[ $offset - 1 ], array( ';', '{', '}' ), true );

		if ( ! $is_token_boundary ) {
			++$offset;
			continue;
		}

		// Skip standard scalar strings so their bytes cannot resemble executable grammar.
		if ( 's' === $serialized_value[ $offset ]
			&& preg_match( '/\Gs:([0-9]+):"/', $serialized_value, $string_header, 0, $offset ) ) {
			$string_byte_count = (int) $string_header[1];
			$string_start      = $offset + strlen( $string_header[0] );
			$remaining_bytes   = $serialized_length - $string_start;

			if ( 2 <= $remaining_bytes && $string_byte_count <= $remaining_bytes - 2 ) {
				$string_end = $string_start + $string_byte_count;

				if ( '";' !== substr( $serialized_value, $string_end, 2 ) ) {
					++$offset;
					continue;
				}

				$offset = $string_end + 2;
				continue;
			}
		}

		// Require a complete length-delimited enum token before rejecting the value.
		if ( 'E' === $serialized_value[ $offset ]
			&& preg_match( '/\GE:([0-9]+):"/', $serialized_value, $enum_header, 0, $offset ) ) {
			$enum_byte_count = (int) $enum_header[1];
			$enum_start      = $offset + strlen( $enum_header[0] );
			$remaining_bytes = $serialized_length - $enum_start;

			if ( 2 <= $remaining_bytes && $enum_byte_count <= $remaining_bytes - 2 ) {
				$enum_end = $enum_start + $enum_byte_count;

				if ( '";' === substr( $serialized_value, $enum_end, 2 ) ) {
					return true;
				}
			}
		}

		++$offset;
	}

	return false;
}

/**
 * Identify an exact legacy Serializable custom-object envelope.
 *
 * WordPress does not recognize the C token in is_serialized(), so it must be rejected separately.
 *
 * @param string $serialized_value Potential legacy serialized custom object.
 * @return bool Whether the full value matches the legacy custom-object grammar.
 */
function ai4seo_is_legacy_serialized_custom_object( string $serialized_value ): bool {
	$serialized_length = strlen( $serialized_value );

	if ( 11 > $serialized_length || 'C:' !== substr( $serialized_value, 0, 2 ) ) {
		return false;
	}

	// Read the declared class-name byte count and require its opening delimiter.
	$offset                   = 2;
	$class_length_digit_count = strspn( $serialized_value, '0123456789', $offset );

	if ( 0 === $class_length_digit_count ) {
		return false;
	}

	$class_byte_count = (int) substr( $serialized_value, $offset, $class_length_digit_count );
	$offset          += $class_length_digit_count;

	if ( ':"' !== substr( $serialized_value, $offset, 2 ) ) {
		return false;
	}

	$class_start = $offset + 2;

	if ( $class_byte_count > $serialized_length - $class_start ) {
		return false;
	}

	$class_end = $class_start + $class_byte_count;

	if ( '":' !== substr( $serialized_value, $class_end, 2 ) ) {
		return false;
	}

	// Read the declared opaque-payload byte count and require its opening delimiter.
	$offset                     = $class_end + 2;
	$payload_length_digit_count = strspn( $serialized_value, '0123456789', $offset );

	if ( 0 === $payload_length_digit_count ) {
		return false;
	}

	$payload_byte_count = (int) substr( $serialized_value, $offset, $payload_length_digit_count );
	$offset            += $payload_length_digit_count;

	if ( ':{' !== substr( $serialized_value, $offset, 2 ) ) {
		return false;
	}

	$payload_start = $offset + 2;

	if ( $payload_byte_count > $serialized_length - $payload_start ) {
		return false;
	}

	$payload_end = $payload_start + $payload_byte_count;

	return $payload_end + 1 === $serialized_length
		&& '}' === $serialized_value[ $payload_end ];
}

/**
 * Safely decode one serialization layer without instantiating PHP objects.
 *
 * @param mixed $value Potentially serialized value.
 * @return mixed Decoded value or the original value when it is not recognized as serialized.
 *               False for serialized false, object-bearing data, or decoder failure.
 */
function ai4seo_safe_maybe_unserialize( $value ) {
	// Values already decoded by WordPress should retain their original type and identity.
	if ( ! is_string( $value ) ) {
		return $value;
	}

	// Match WordPress's serialized-value handling without changing ordinary string output.
	$serialized_value = trim( $value );

	// WordPress omits legacy custom-object tokens from its serialized-value recognition.
	if ( ai4seo_is_legacy_serialized_custom_object( $serialized_value ) ) {
		return false;
	}

	if ( ! is_serialized( $serialized_value ) ) {
		return $value;
	}

	// Enum tokens must be blocked before PHP has an opportunity to invoke their autoloader.
	if ( ai4seo_serialized_value_contains_enum_token( $serialized_value ) ) {
		return false;
	}

	try {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize,WordPress.PHP.NoSilencedErrors.Discouraged -- Class instantiation is disabled, and malformed external data must fail closed without a warning.
		$decoded_value = @unserialize(
			$serialized_value,
			array(
				'allowed_classes' => false,
				'max_depth'       => 32,
			)
		);
	} catch ( Throwable $exception ) {
		// Decoder exceptions follow the same false contract as object-bearing serialized data.
		return false;
	}

	// Validate every decoded node while bounding recursive or adversarial reference structures.
	$remaining_nodes = 10000;

	if ( ! ai4seo_is_safe_unserialized_value( $decoded_value, 0, $remaining_nodes ) ) {
		return false;
	}

	return $decoded_value;
}

// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound -- Retain the PHP 8 named-argument contract.
/**
 * Function to get the checksum of an array
 *
 * @param mixed $array The array value.
 * @return int The crc32 checksum of the array
 */
function ai4seo_get_array_checksum( $array ): int {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Preserve the established checksum bytes; the value is never unserialized here.
	return crc32( serialize( $array ) );
}


/**
 * Returns whether the user is inside the 'installed plugins' (plugins.php) admin page
 *
 * @return bool Whether the user is inside the 'installed plugins' admin page
 */
function ai4seo_is_user_inside_installed_plugins_page(): bool {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	if ( '' === $request_uri ) {
		return false;
	}

	return strpos( $request_uri, 'plugins.php' ) !== false;
}


/**
 * Add the plugin parameter prefix to an input identifier.
 *
 * @param string $input_id Input identifier.
 * @return string Prefixed input name.
 */
function ai4seo_get_prefixed_input_name( $input_id ): string {
	return AI4SEO_POST_PARAMETER_PREFIX . $input_id;
}


/**
 * Remove the plugin parameter prefix from an input identifier.
 *
 * @param string $input_id Input identifier.
 * @return string Unprefixed input name.
 */
function ai4seo_get_unprefixed_input_name( $input_id ): string {
	return str_replace( AI4SEO_POST_PARAMETER_PREFIX, '', $input_id );
}


/**
 * Determine if a URL references a locally hosted file.
 *
 * @param string $url URL to inspect.
 * @return bool True when the URL refers to a file on the current site.
 */
function ai4seo_is_local_file( string $url ): bool {
	if ( empty( $url ) ) {
		return false;
	}

	$uploads_directory = wp_get_upload_dir();

	if ( ! empty( $uploads_directory['baseurl'] ) && strpos( $url, $uploads_directory['baseurl'] ) === 0 ) {
		return true;
	}

	$parsed_url = wp_parse_url( $url );

	if ( empty( $parsed_url ) ) {
		return false;
	}

	if ( empty( $parsed_url['host'] ) ) {
		return true;
	}

	$site_url  = wp_parse_url( home_url() );
	$site_host = isset( $site_url['host'] ) ? strtolower( $site_url['host'] ) : '';
	$url_host  = strtolower( $parsed_url['host'] );

	if ( $site_host === $url_host ) {
		return true;
	}

	$normalized_site_host = preg_replace( '/^www\./', '', $site_host );
	$normalized_url_host  = preg_replace( '/^www\./', '', $url_host );

	return ! empty( $normalized_site_host ) && $normalized_site_host === $normalized_url_host;
}


/**
 * Convert a local URL into an absolute filesystem path when possible.
 *
 * @param string $url Local URL to convert.
 * @return string|null Absolute path or null when it cannot be resolved.
 */
function ai4seo_get_local_path_from_url( string $url ): ?string {
	$decoded_url = rawurldecode( $url );

	$uploads_directory = wp_get_upload_dir();

	if ( ! empty( $uploads_directory['baseurl'] ) && strpos( $decoded_url, $uploads_directory['baseurl'] ) === 0 ) {
		$relative_path = ltrim( substr( $decoded_url, strlen( $uploads_directory['baseurl'] ) ), '/' );
		$local_path    = trailingslashit( $uploads_directory['basedir'] ) . $relative_path;

		if ( file_exists( $local_path ) ) {
			return wp_normalize_path( $local_path );
		}
	}

	$parsed_url = wp_parse_url( $decoded_url );
	$path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

	if ( empty( $path ) ) {
		return null;
	}

	$absolute_path = trailingslashit( ABSPATH ) . ltrim( $path, '/' );

	if ( file_exists( $absolute_path ) ) {
		return wp_normalize_path( $absolute_path );
	}

	return null;
}


/**
 * Retrieve the MIME type of a locally stored file.
 *
 * @param string $path Absolute path to the local file.
 * @return string|null MIME type string when detected, otherwise null.
 */
function ai4seo_get_local_mime_type( string $path ): ?string {
	if ( empty( $path ) || ! file_exists( $path ) || ! is_readable( $path ) ) {
		return null;
	}

	$normalized_path = wp_normalize_path( $path );

	if ( ai4seo_is_function_usable( 'mime_content_type' ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed or unsupported local files are an expected probe failure handled by the fallback detector.
		$mime_type = @mime_content_type( $normalized_path );

		if ( ! empty( $mime_type ) ) {
			return ai4seo_normalize_mime_type_string( $mime_type );
		}
	}

	if ( ai4seo_is_function_usable( 'finfo_open' ) && ai4seo_is_function_usable( 'finfo_file' ) ) {
		$file_info = finfo_open( FILEINFO_MIME_TYPE );

		if ( $file_info ) {
			$mime_type = finfo_file( $file_info, $normalized_path );

			if ( ! empty( $mime_type ) ) {
				return ai4seo_normalize_mime_type_string( $mime_type );
			}
		}
	}

	if ( function_exists( 'wp_check_filetype' ) ) {
		$file_type = wp_check_filetype( $normalized_path );

		if ( ! empty( $file_type['type'] ) ) {
			return ai4seo_normalize_mime_type_string( $file_type['type'] );
		}
	}

	return null;
}


/**
 * Attempt to retrieve a MIME type from remote headers using WordPress HTTP helpers.
 *
 * @param string $url Remote URL to probe.
 * @return string|null MIME type string or null if unavailable.
 */
function ai4seo_get_remote_mime_type( string $url ): ?string {
	if ( empty( $url ) ) {
		return null;
	}

	// Keep both header probes inside WordPress's TLS and SSRF validation boundary.
	$tls_verification_error = null;
	$request_arguments      = array(
		'timeout'     => 5,
		'redirection' => 3,
		'user-agent'  => 'AI4SEO/' . AI4SEO_PLUGIN_VERSION_NUMBER,
	);

	if ( function_exists( 'wp_safe_remote_head' ) ) {
		$response = wp_safe_remote_head( $url, $request_arguments );

		if ( ! is_wp_error( $response ) ) {
			$mime_type = wp_remote_retrieve_header( $response, 'content-type' );

			$mime_type = ai4seo_normalize_mime_type_string( $mime_type );

			if ( ! empty( $mime_type ) ) {
				return $mime_type;
			}
		} elseif ( ai4seo_is_tls_verification_wp_error( $response ) ) {
			$tls_verification_error = ai4seo_get_remote_tls_verification_wp_error( $url, $response );
		}
	}

	// Fall back to a small ranged GET because some remote providers do not support HEAD requests.
	if ( function_exists( 'wp_safe_remote_get' ) ) {
		$request_arguments['method']              = 'GET';
		$request_arguments['headers']             = array( 'Range' => 'bytes=0-1023' );
		$request_arguments['limit_response_size'] = 1024;

		$response = wp_safe_remote_get( $url, $request_arguments );
		// Report certificate remediation only when the final transport probe also failed TLS verification.
		$tls_verification_error = null;

		if ( ! is_wp_error( $response ) ) {
			$mime_type = wp_remote_retrieve_header( $response, 'content-type' );

			$mime_type = ai4seo_normalize_mime_type_string( $mime_type );

			if ( ! empty( $mime_type ) ) {
				return $mime_type;
			}
		} elseif ( ai4seo_is_tls_verification_wp_error( $response ) ) {
			$tls_verification_error = ai4seo_get_remote_tls_verification_wp_error( $url, $response );
		}
	}

	if ( $tls_verification_error ) {
		$error_data = $tls_verification_error->get_error_data();
		$host       = is_array( $error_data ) ? ( $error_data['host'] ?? '' ) : '';
		$host_label = '' !== $host ? $host : 'unknown';

		ai4seo_debug_message(
			592866137,
			$tls_verification_error->get_error_message() . ' Host: ' . $host_label
		);
	}

	return null;
}


/**
 * Clean and normalize a MIME type string extracted from headers or file metadata.
 *
 * @param string|null $mime_type Raw MIME type string.
 * @return string|null Normalized MIME type or null when empty.
 */
function ai4seo_normalize_mime_type_string( ?string $mime_type ): ?string {
	if ( empty( $mime_type ) ) {
		return null;
	}

	if ( strpos( $mime_type, ';' ) !== false ) {
		$mime_type = explode( ';', $mime_type )[0];
	}

	$mime_type = strtolower( trim( $mime_type ) );

	return '' !== $mime_type ? $mime_type : null;
}


/**
 * Convert an image signature detector format to its normalized MIME type.
 *
 * @param string $detected_image_format Format or MIME type returned by the image signature detector.
 * @return string Normalized MIME type, or an empty string when the format is unknown.
 */
function ai4seo_get_mime_type_from_detected_image_format( string $detected_image_format ): string {
	// Normalize once so both MIME values and short signature names remain case-insensitive.
	$detected_image_format = strtolower( $detected_image_format );

	// Preserve MIME values returned by getimagesizefromstring() while normalizing optional parameters.
	if ( 0 === strpos( $detected_image_format, 'image/' ) ) {
		return ai4seo_normalize_mime_type_string( $detected_image_format ) ?? '';
	}

	// Map the stable short names returned by the plugin's magic-byte checks.
	$image_mime_types = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'heif' => 'image/heif',
		'bmp'  => 'image/bmp',
		'tiff' => 'image/tiff',
		'ico'  => 'image/x-icon',
	);

	return $image_mime_types[ $detected_image_format ] ?? '';
}


/**
 * Get the MIME type of file from a given URL.
 *
 * @param string $url The URL of the file.
 * @return string|null The MIME type (e.g., "image/jpeg") or null if not found.
 */
function ai4seo_get_mime_type_from_url( string $url ): ?string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 739526157, 'Prevented loop', true );
		return null;
	}

	if ( empty( $url ) ) {
		return null;
	}

	if ( ai4seo_is_local_file( $url ) ) {
		$local_path = ai4seo_get_local_path_from_url( $url );

		if ( ! empty( $local_path ) ) {
			$mime_type = ai4seo_get_local_mime_type( $local_path );

			if ( ! empty( $mime_type ) ) {
				return $mime_type;
			}
		}
	}

	return ai4seo_get_remote_mime_type( $url );
}


/**
 * Retrieves a formatted backtrace debug message.
 *
 * @param string $separator The separator for each backtrace entry.
 * @return string The formatted backtrace message.
 */
function ai4seo_get_backtrace_debug_message( string $separator = '<br />' ): string {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- This opt-in diagnostic helper explicitly formats the active call stack.
	$backtrace_array     = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	$formatted_backtrace = array();

	foreach ( $backtrace_array as $index => $item ) {
		// Ensure necessary keys exist.
		if ( ! isset( $item['function'], $item['line'], $item['file'] ) ) {
			continue;
		}

		// Ignore specific functions.
		$ignored_functions = array( 'ai4seo_get_backtrace_debug_message' );

		if ( in_array( $item['function'], $ignored_functions, true ) ) {
			continue;
		}

		$formatted_backtrace[] = sprintf(
			'%s @ Line %d: <b>%s()</b>',
			basename( $item['file'] ),
			intval( $item['line'] ),
			esc_html( $item['function'] )
		);
	}

	if ( empty( $formatted_backtrace ) ) {
		return '';
	}

	$formatted_backtrace = array_reverse( $formatted_backtrace );

	// Add index numbers.
	foreach ( $formatted_backtrace as $i => &$entry ) {
		$entry = ( $i + 1 ) . '. ' . sanitize_text_field( $entry );
	}

	unset( $entry );

	return implode( $separator, $formatted_backtrace );
}


/**
 * Checks if debug output is visible to the current request context.
 *
 * @return bool
 */
function ai4seo_can_display_debug_output_messages(): bool {
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return false;
	}

	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return false;
	}

	if ( ! is_admin() ) {
		return false;
	}

	return ai4seo_can_administer_plugin();
}


/**
 * Writes a debug message to the uploads log file.
 *
 * @param string $message The final log message.
 * @return bool
 */
function ai4seo_append_debug_message_to_file( string $message ): bool {
	if ( ! ai4seo_is_function_usable( 'wp_upload_dir' )
		|| ! ai4seo_is_function_usable( 'trailingslashit' )
		|| ! ai4seo_is_function_usable( 'wp_mkdir_p' )
		|| ! ai4seo_is_function_usable( 'wp_is_writable' )
		|| ! ai4seo_is_function_usable( 'is_wp_error' )
	) {
		return false;
	}

	$message = ai4seo_deep_sanitize( $message );
	$message = ai4seo_deep_sanitize( $message, 'wp_strip_all_tags' );

	$upload_dir = wp_upload_dir();

	if ( empty( $upload_dir ) || is_wp_error( $upload_dir ) || empty( $upload_dir['basedir'] ) ) {
		return false;
	}

	$log_directory = trailingslashit( $upload_dir['basedir'] );

	if ( ! wp_mkdir_p( $log_directory ) ) {
		return false;
	}

	if ( ! wp_is_writable( $log_directory ) ) {
		return false;
	}

	$log_file_path = $log_directory . 'ai-for-seo-debug.log';
	$timestamp     = ai4seo_gmdate( 'Y-m-d H:i:s' );
	$log_entry     = '[' . $timestamp . '] ' . $message . PHP_EOL;

	if ( ! ai4seo_is_function_usable( 'file_put_contents' ) ) {
		return false;
	}

	// A partial append must follow the same false contract as a failed write.
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Atomic append with LOCK_EX has no WP_Filesystem equivalent; expected write failures follow this helper's false contract.
	$written_bytes = @file_put_contents( $log_file_path, $log_entry, FILE_APPEND | LOCK_EX );

	return strlen( $log_entry ) === $written_bytes;
}


/**
 * Stores debug messages in the WordPress options table.
 *
 * @param int    $code      The error code.
 * @param string $message   The error message.
 * @param string $backtrace The backtrace content.
 * @return bool
 */
function ai4seo_store_debug_message_in_database( int $code, string $message, string $backtrace ): bool {
	$existing_log_entries = get_option( AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array() );
	$sanitized_message    = ai4seo_deep_sanitize( $message );
	$sanitized_message    = ai4seo_deep_sanitize( $sanitized_message, 'wp_strip_all_tags' );
	$sanitized_backtrace  = ai4seo_deep_sanitize( $backtrace, 'wp_unslash' );
	$sanitized_backtrace  = ai4seo_deep_sanitize( $sanitized_backtrace, 'wp_kses_post' );

	if ( ! is_array( $existing_log_entries ) ) {
		$existing_log_entries = array();
	}

	$existing_log_entries[] = array(
		'time'      => time(),
		'code'      => $code,
		'message'   => $sanitized_message,
		'backtrace' => $sanitized_backtrace,
	);

	if ( count( $existing_log_entries ) > 1000 ) {
		$existing_log_entries = array_slice( $existing_log_entries, -1000 );
	}

	return ai4seo_update_option( AI4SEO_DEBUG_MESSAGES_OPTION_NAME, $existing_log_entries, false, false );
}


/**
 * Collects debug messages for admin notice output.
 *
 * @param string $message The message to display.
 * @return bool
 */
function ai4seo_collect_debug_notice_message( string $message ): bool {
	if ( ! ai4seo_can_display_debug_output_messages() ) {
		return false;
	}

	global $ai4seo_debug_notice_messages;

	if ( ! is_array( $ai4seo_debug_notice_messages ) ) {
		$ai4seo_debug_notice_messages = array();
	}

	$ai4seo_debug_notice_messages[] = $message;

	if ( ! has_action( 'admin_footer', 'ai4seo_render_debug_notice_messages' ) ) {
		add_action( 'admin_footer', 'ai4seo_render_debug_notice_messages' );
	}

	return true;
}


/**
 * Renders collected debug notice messages once in the admin footer.
 *
 * @return void
 */
function ai4seo_render_debug_notice_messages(): void {
	global $ai4seo_debug_notice_messages;

	if ( ! ai4seo_can_display_debug_output_messages() ) {
		return;
	}

	if ( empty( $ai4seo_debug_notice_messages ) || ! is_array( $ai4seo_debug_notice_messages ) ) {
		return;
	}

	$combined_message = implode( '<br /><br />', $ai4seo_debug_notice_messages );

	// Keep footer-rendered debug notices visible when the early fallback hides external notices.
	echo "<div class='notice notice-error ai4seo-debug-notice'>";
		echo '<p>';
			ai4seo_echo_wp_kses( $combined_message );
		echo '</p>';
	echo '</div>';

	$ai4seo_debug_notice_messages = array();
}


/**
 * Outputs a formatted debug message using the selected debug output mode.
 *
 * @param int    $code          The error code identifier.
 * @param string $message       The error message.
 * @param bool   $add_traceback Whether to append a backtrace.
 * @return bool
 */
function ai4seo_debug_message( int $code, string $message, bool $add_traceback = false ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 99999 ) ) {
		return false;
	}

	// sanitize message.
	$message           = wp_specialchars_decode( $message, ENT_QUOTES );
	$sanitized_message = sanitize_text_field( $message );
	$sanitized_message = wp_strip_all_tags( $sanitized_message );
	$sanitized_message = trim( $sanitized_message );

	$debug_output_mode = ai4seo_get_setting( AI4SEO_SETTING_DEBUG_OUTPUT_MODE );

	if ( ! in_array( $debug_output_mode, AI4SEO_AVAILABLE_DEBUG_OUTPUT_MODE_OPTIONS, true ) ) {
		$debug_output_mode = 'none';
	}

	if ( 'none' === $debug_output_mode ) {
		return false;
	}

	// check if we can display notices or print_r outputs (not in cron/ajax).
	if ( ! ai4seo_can_display_debug_output_messages() && in_array( $debug_output_mode, array( 'notice', 'print_r' ), true ) ) {
		return false;
	}

	// build final message.
	$sanitized_db_message = $sanitized_message;
	$combined_message     = AI4SEO_PLUGIN_NAME . ': ' . $sanitized_message . ' (Code #' . $code;

	// add backtrace if requested.
	$backtrace = '';

	if ( $add_traceback ) {
		$backtrace_separator = ( in_array( $debug_output_mode, array( 'print_r', 'notice', 'database' ), true ) ) ? '<br />' : ' > ';
		$backtrace           = ai4seo_get_backtrace_debug_message( $backtrace_separator );

		if ( $backtrace ) {
			$combined_message .= ', Backtrace:' . $backtrace_separator . $backtrace;
		}
	}

	$combined_message .= ')';

	switch ( $debug_output_mode ) {
		case 'error_log':
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- The administrator explicitly selected PHP's error-log debug destination.
			return error_log( $combined_message );
		case 'file':
			return ai4seo_append_debug_message_to_file( $combined_message );
		case 'database':
			return ai4seo_store_debug_message_in_database( $code, $sanitized_db_message, $backtrace );
		case 'notice':
			return ai4seo_collect_debug_notice_message( $combined_message );
		case 'print_r':
			ai4seo_echo_wp_kses( '<pre>' . $combined_message . '</pre>' );
			return true;
		default:
			return false;
	}
}


/**
 * Convert any variable into a readable single-line string.
 * Similar to print_r( $var, true ), but compact and consistent.
 *
 * @param mixed $value Variable to stringify.
 *
 * @return string
 */
function ai4seo_stringify( $value ): string {
	if ( is_array( $value ) ) {
		$output = 'Array( ';

		$entries = array();

		foreach ( $value as $key => $this_value ) {
			if ( is_array( $this_value ) || is_object( $this_value ) ) {
				$entries[] = '[' . $key . '] => ' . ai4seo_stringify( $this_value );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Preserve the established diagnostic representation of scalar values.
				$entries[] = '[' . $key . '] => ' . var_export( $this_value, true );
			}
		}

		$output .= implode( ', ', $entries ) . ' )';

		return $output;
	}

	if ( is_object( $value ) ) {
		$output = get_class( $value ) . ' Object( ';

		$entries = array();

		foreach ( get_object_vars( $value ) as $property_name => $this_value ) {
			if ( is_array( $this_value ) || is_object( $this_value ) ) {
				$entries[] = '[' . $property_name . '] => ' . ai4seo_stringify( $this_value );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Preserve the established diagnostic representation of scalar properties.
				$entries[] = '[' . $property_name . '] => ' . var_export( $this_value, true );
			}
		}

		$output .= implode( ', ', $entries ) . ' )';

		return $output;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Preserve the established diagnostic representation of scalar values.
	return var_export( $value, true );
}


/**
 * Return only sanitized array keys for diagnostics that must not expose values.
 *
 * @param array $debug_array Array whose shape should be summarized.
 * @return string Comma-separated key list or "none" when empty.
 */
function ai4seo_get_debug_array_key_summary( array $debug_array ): string {
	$sanitized_keys = array();

	// Preserve numeric positions while normalizing named keys to their diagnostic-safe representation.
	foreach ( array_keys( $debug_array ) as $key ) {
		$sanitized_keys[] = is_int( $key ) ? '#' . $key : sanitize_key( (string) $key );
	}

	// Remove names that become empty after sanitization so the summary never emits blank entries.
	$sanitized_keys = array_values( array_filter( $sanitized_keys ) );

	return $sanitized_keys ? implode( ', ', $sanitized_keys ) : 'none';
}


/**
 * Return the internal capped-fetch error used before an oversized source becomes a structured generation failure.
 *
 * @return WP_Error
 */
function ai4seo_get_attachment_source_too_large_wp_error(): WP_Error {
	// Use one internal marker so all capped fetch paths map to the same structured response later.
	return new WP_Error( 'ai4seo_fetch_too_large', 'Content too large to fetch' );
}


/**
 * Determine whether an HTTP error represents failed TLS certificate verification.
 *
 * @param WP_Error $error The WordPress HTTP error.
 * @return bool
 */
function ai4seo_is_tls_verification_wp_error( WP_Error $error ): bool {
	$error_parts = array_merge( $error->get_error_codes(), $error->get_error_messages() );
	$error_text  = strtolower( implode( ' ', array_map( 'strval', $error_parts ) ) );
	$indicators  = array(
		'curl error 51',
		'curl error 60',
		'certificate has expired',
		'certificate is not yet valid',
		'certificate revoked',
		'certificate verify failed',
		'certificate verification failed',
		'did not match expected cn',
		'does not match target host name',
		'hostname mismatch',
		'no alternative certificate subject name',
		'ssl certificate problem',
		'unable to get local issuer certificate',
		'unable to verify the first certificate',
		'unable to verify leaf signature',
		'peer certificate cannot be authenticated',
		'self signed certificate',
		'tls certificate',
	);

	foreach ( $indicators as $indicator ) {
		if ( false !== strpos( $error_text, $indicator ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Build an actionable error without exposing the remote path or transport details.
 *
 * @param string   $url          The remote URL that could not be verified.
 * @param WP_Error $source_error The original WordPress HTTP error.
 * @return WP_Error
 */
function ai4seo_get_remote_tls_verification_wp_error( string $url, WP_Error $source_error ): WP_Error {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$host = is_string( $host ) ? strtolower( sanitize_text_field( $host ) ) : '';

	return new WP_Error(
		'ai4seo_tls_verification_failed',
		'Remote media TLS certificate verification failed. Fix the remote certificate chain or configure a trusted CA certificate; do not disable TLS verification.',
		array(
			'host'              => $host,
			'source_error_code' => sanitize_key( (string) $source_error->get_error_code() ),
		)
	);
}


/**
 * Resolve a same-site media URL to a local filesystem path when possible.
 *
 * @param string $url The media URL.
 * @return string The local path, or an empty string when it cannot be resolved safely.
 */
function ai4seo_get_same_site_local_file_path_from_url( string $url ): string {
	// Only same-site URLs are resolved to local paths; other URLs stay on the bounded remote-fetch path.
	$parsed_url = wp_parse_url( $url );
	$site_url   = wp_parse_url( site_url() );

	if ( ! isset( $parsed_url['host'], $site_url['host'] ) || strtolower( $parsed_url['host'] ) !== strtolower( $site_url['host'] ) ) {
		return '';
	}

	$relative_path = $parsed_url['path'] ?? '';
	$site_path     = rtrim( $site_url['path'] ?? '', '/' );

	// WordPress can live in a subdirectory, so strip the site path before appending to ABSPATH.
	if ( ! $relative_path ) {
		return '';
	}

	if ( $site_path && ( $relative_path === $site_path || strpos( $relative_path, $site_path . '/' ) === 0 ) ) {
		$relative_path = substr( $relative_path, strlen( $site_path ) );
	}

	$local_path      = ABSPATH . ltrim( rawurldecode( $relative_path ), '/\\' );
	$real_local_path = realpath( $local_path );
	$real_abspath    = realpath( ABSPATH );

	// realpath() proves the file exists before the containment check below.
	if ( ! $real_local_path || ! $real_abspath || ! is_file( $real_local_path ) ) {
		return '';
	}

	$normalized_local_path = wp_normalize_path( $real_local_path );
	$normalized_abspath    = trailingslashit( wp_normalize_path( $real_abspath ) );

	// Reject path traversal or symlink escapes outside the WordPress installation.
	if ( strpos( $normalized_local_path, $normalized_abspath ) !== 0 ) {
		return '';
	}

	return $real_local_path;
}


/**
 * Read a local file size as an integer.
 *
 * @param string $path The local filesystem path.
 * @return int The file size in bytes, or 0 when unavailable.
 */
function ai4seo_get_file_size( string $path ): int {
	// Use filesize() only after confirming the path is a local file.
	if ( ! $path || ! is_file( $path ) ) {
		return 0;
	}

	$file_size = filesize( $path );

	if ( false === $file_size ) {
		return 0;
	}

	return (int) $file_size;
}


/**
 * Check whether a local file is larger than a byte limit.
 *
 * @param string $path The local filesystem path.
 * @param int    $max_size The maximum allowed size in bytes.
 * @return bool
 */
function ai4seo_is_file_larger_than( string $path, int $max_size ): bool {
	// Treat unknown file sizes as not over-limit so normal fetch error handling can continue.
	$file_size = ai4seo_get_file_size( $path );

	return ( $file_size > 0 && $file_size > $max_size );
}


/**
 * Read a remote Content-Length header without downloading the body.
 *
 * @param string $url The remote URL.
 * @return int The content length in bytes, or 0 when unavailable.
 */
function ai4seo_get_remote_content_length( string $url ): int {
	// HEAD lets the base64 path reject known-oversized remote files without downloading their body.
	$response = wp_safe_remote_head(
		$url,
		array(
			'timeout'     => 10,
			'redirection' => 5,
		)
	);

	if ( is_wp_error( $response ) ) {
		return 0;
	}

	// Some HTTP implementations expose duplicate headers as arrays.
	$content_length = wp_remote_retrieve_header( $response, 'content-length' );

	if ( is_array( $content_length ) ) {
		$content_length = reset( $content_length );
	}

	if ( ! is_numeric( $content_length ) ) {
		return 0;
	}

	return (int) $content_length;
}


/**
 * Determine whether given binary content is probably an image.
 *
 * This is a "best effort" check. It does not require GD/Imagick support for the format.
 *
 * @param string $binary_content Raw response body (binary).
 * @param string $content_type Optional HTTP Content-Type header value.
 * @return array {
 * @type bool $is_probably_image True if it looks like an image.
 * @type string $reason Short explanation.
 * @type string|null $detected_format Detected format (jpeg/png/gif/webp/avif/bmp/tiff/ico/svg) or null.
 * }
 */
function ai4seo_is_probably_image_content( string $binary_content, string $content_type = '' ): array {
	if ( '' === $binary_content ) {
		return array(
			'is_probably_image' => false,
			'reason'            => 'empty_body',
			'detected_format'   => null,
		);
	}

	// 1) Verify with getimagesizefromstring (if available).
	if ( function_exists( 'getimagesizefromstring' ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Arbitrary response bodies are expected to fail this best-effort image probe.
		$image_size = @getimagesizefromstring( $binary_content );

		if ( $image_size ) {
			$mime_type = $image_size['mime'] ?? '';
			return array(
				'is_probably_image' => true,
				'reason'            => 'getimagesizefromstring',
				'detected_format'   => $mime_type,
			);
		}
	}

	// 2) Magic bytes signature checks (most stable).
	$head = substr( $binary_content, 0, 64 );

	// JPEG: FF D8 FF.
	if ( substr( $head, 0, 3 ) === "\xFF\xD8\xFF" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'jpeg',
		);
	}

	// PNG: 89 50 4E 47 0D 0A 1A 0A.
	if ( substr( $head, 0, 8 ) === "\x89PNG\r\n\x1A\n" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'png',
		);
	}

	// GIF: GIF87a / GIF89a.
	if ( substr( $head, 0, 6 ) === 'GIF87a' || substr( $head, 0, 6 ) === 'GIF89a' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'gif',
		);
	}

	// WebP: RIFF....WEBP.
	if ( substr( $head, 0, 4 ) === 'RIFF' && substr( $head, 8, 4 ) === 'WEBP' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'webp',
		);
	}

	// BMP: BM.
	if ( substr( $head, 0, 2 ) === 'BM' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'bmp',
		);
	}

	// TIFF: II*\x00 or MM\x00*.
	if ( substr( $head, 0, 4 ) === "II*\x00" || substr( $head, 0, 4 ) === "MM\x00*" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'tiff',
		);
	}

	// ICO: 00 00 01 00.
	if ( substr( $head, 0, 4 ) === "\x00\x00\x01\x00" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'ico',
		);
	}

	// AVIF/HEIF family: look for 'ftyp' box + known brands.
	// Note: ISO BMFF has 'ftyp' typically within first bytes, but not always at offset 4 exactly.
	if ( strpos( $head, 'ftyp' ) !== false ) {
		$brands = array( 'avif', 'avis', 'heic', 'heix', 'mif1', 'msf1' );
		foreach ( $brands as $brand ) {
			if ( strpos( $head, $brand ) !== false ) {
				$detected = ( 'avif' === $brand || 'avis' === $brand ) ? 'avif' : 'heif';
				return array(
					'is_probably_image' => true,
					'reason'            => 'magic_bytes',
					'detected_format'   => $detected,
				);
			}
		}
	}

	// 3) Optional: finfo MIME sniff (fallback).
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );

		if ( $finfo ) {
			$mime = finfo_buffer( $finfo, $binary_content );

			if ( is_string( $mime ) && strpos( strtolower( $mime ), 'image/' ) === 0 ) {
				return array(
					'is_probably_image' => true,
					'reason'            => 'finfo_mime',
					'detected_format'   => strtolower( substr( $mime, 6 ) ),
				);
			}
		}
	}

	// 4) As a last fallback: trust header if it says image/*.
	if ( '' !== $content_type ) {
		$content_type_normalized = strtolower( trim( explode( ';', $content_type )[0] ) );
		if ( strpos( $content_type_normalized, 'image/' ) === 0 ) {
			return array(
				'is_probably_image' => true,
				'reason'            => 'content_type_only',
				'detected_format'   => strtolower( substr( $content_type_normalized, 6 ) ),
			);
		}
	}

	return array(
		'is_probably_image' => false,
		'reason'            => 'no_match',
		'detected_format'   => null,
	);
}


/**
 * Fetch file contents through a verified remote request or contained local fallback.
 *
 * @param string $url The full URL of the media to fetch.
 * @param string $attempt_type (Optional) Fetch mode: all, remote_only, local_only, or download_only.
 * @param int    $max_response_size (Optional) Maximum bytes to retrieve before returning an error.
 * @return string|WP_Error The file contents on success, or WP_Error on failure
 */
function ai4seo_get_remote_body( string $url, string $attempt_type = 'all', int $max_response_size = 0 ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 416990059, 'Prevented loop', true );
		return '';
	}

	$tls_verification_error = null;

	// Use WordPress's SSRF-safe client so remote media retains normal TLS verification.
	if ( 'remote_only' === $attempt_type || 'all' === $attempt_type ) {
		$remote_get_arguments = array(
			'timeout'     => 15,
			'redirection' => 5,
			'decompress'  => true,
		);

		// Fetch one byte beyond the allowed size so the caller can detect an exceeded cap.
		if ( $max_response_size > 0 ) {
			$remote_get_arguments['limit_response_size'] = $max_response_size + 1;
		}

		$response = wp_safe_remote_get( $url, $remote_get_arguments );

		if ( ! is_wp_error( $response ) ) {
			$response_body = wp_remote_retrieve_body( $response );

			if ( $max_response_size > 0 && strlen( $response_body ) > $max_response_size ) {
				return ai4seo_get_attachment_source_too_large_wp_error();
			}

			return $response_body;
		} elseif ( ai4seo_is_tls_verification_wp_error( $response ) ) {
			$tls_verification_error = ai4seo_get_remote_tls_verification_wp_error( $url, $response );
		}
	}

	// Resolve same-site URLs only through paths contained by the local media roots.
	if ( 'local_only' === $attempt_type || 'all' === $attempt_type ) {
		$local_path = ai4seo_get_same_site_local_file_path_from_url( $url );

		if ( $local_path ) {
			// Local files can fail fast on filesize() before the shared file reader loads the contents.
			if ( $max_response_size > 0 && ai4seo_is_file_larger_than( $local_path, $max_response_size ) ) {
				return ai4seo_get_attachment_source_too_large_wp_error();
			}

			$contents = ai4seo_file_get_contents( $local_path, null, $max_response_size );

			if ( false !== $contents ) {
				// Keep the same final guard here as the remote fetches in case the file changes after filesize().
				if ( $max_response_size > 0 && strlen( $contents ) > $max_response_size ) {
					return ai4seo_get_attachment_source_too_large_wp_error();
				}

				return $contents;
			}
		}
	}

	// Use a verified temporary download only when the caller did not request an in-memory response cap.
	if ( $max_response_size <= 0 && ( 'download_only' === $attempt_type || 'all' === $attempt_type ) ) {
		$temp_file = download_url( $url );

		if ( ! is_wp_error( $temp_file ) ) {
			$contents = ai4seo_file_get_contents( $temp_file );

			wp_delete_file( $temp_file ); // Always clean up temp file.

			if ( false !== $contents ) {
				return $contents;
			}
		} elseif ( ai4seo_is_tls_verification_wp_error( $temp_file ) ) {
			$tls_verification_error = ai4seo_get_remote_tls_verification_wp_error( $url, $temp_file );
		}
	}

	if ( $tls_verification_error ) {
		return $tls_verification_error;
	}

	// All attempts failed.
	return new WP_Error( 'ai4seo_fetch_failed', 'Could not fetch media contents.' );
}


// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Mirror the native mb_strlen() named-argument contract.
/**
 * Safely measure the length of a string regardless of mbstring availability.
 *
 * @param string $string   String to measure.
 * @param string $encoding Optional encoding, defaults to UTF-8.
 * @return int             Length of the string.
 */
function ai4seo_mb_strlen( string $string, string $encoding = 'UTF-8' ): int {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
	if ( function_exists( 'mb_strlen' ) ) {
		try {
			return $encoding ? mb_strlen( $string, $encoding ) : mb_strlen( $string );
		} catch ( Throwable $exception ) {
			// Invalid mbstring encodings fall through to the iconv-compatible path below.
			unset( $exception );
		}
	}

	if ( function_exists( 'iconv_strlen' ) ) {
		try {
			return $encoding ? iconv_strlen( $string, $encoding ) : iconv_strlen( $string );
		} catch ( Throwable $exception ) {
			// Invalid iconv encodings fall through to the established byte-length fallback.
			unset( $exception );
		}
	}

	return strlen( $string );
}


// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Mirror the native mb_substr() named-argument contract.
/**
 * Safely extract a substring regardless of mbstring availability.
 *
 * @param string      $string   Input string.
 * @param int         $start    Start position.
 * @param int|null    $length   Optional length.
 * @param string|null $encoding Optional encoding.
 * @return string               Extracted substring.
 */
function ai4seo_mb_substr( string $string, int $start, ?int $length = null, ?string $encoding = 'UTF-8' ): string {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
	if ( function_exists( 'mb_substr' ) ) {
		try {
			return $encoding ? mb_substr( $string, $start, $length, $encoding ) : mb_substr( $string, $start, $length );
		} catch ( Throwable $exception ) {
			// Invalid mbstring encodings fall through to the iconv-compatible path below.
			unset( $exception );
		}
	}

	if ( function_exists( 'iconv_substr' ) ) {
		try {
			return $encoding ? iconv_substr( $string, $start, $length, $encoding ) : iconv_substr( $string, $start, $length );
		} catch ( Throwable $exception ) {
			// Invalid iconv encodings fall through to the established byte-oriented fallback.
			unset( $exception );
		}
	}

	if ( null === $length ) {
		return substr( $string, $start );
	}

	return substr( $string, $start, $length );
}


/**
 * Safely locate substring position without requiring mbstring.
 *
 * @param string      $haystack Haystack string.
 * @param string      $needle   Needle to find.
 * @param int         $offset   Optional offset.
 * @param string|null $encoding Optional encoding.
 * @return int|false            Position or false when not found.
 */
function ai4seo_mb_strpos( string $haystack, string $needle, int $offset = 0, ?string $encoding = 'UTF-8' ) {
	if ( function_exists( 'mb_strpos' ) ) {
		try {
			return $encoding ? mb_strpos( $haystack, $needle, $offset, $encoding ) : mb_strpos( $haystack, $needle, $offset );
		} catch ( Throwable $exception ) {
			// Invalid mbstring encodings fall through to the established byte-oriented search.
			unset( $exception );
		}
	}

	return strpos( $haystack, $needle, $offset );
}


/**
 * Wrapper for file_get_contents() that gracefully falls back to the WP HTTP API or stream access.
 *
 * @param string   $path           Remote URL or local path.
 * @param resource $context        Optional stream context (only used when native function available).
 * @param int      $max_read_bytes Optional local read cap; one extra byte is returned for overflow detection.
 * @return string|false     File contents on success, false on failure.
 */
function ai4seo_file_get_contents( string $path, $context = null, int $max_read_bytes = 0 ) {
	// Treat Windows drive paths as local because URL parsing otherwise interprets the drive letter as a scheme.
	$is_windows_absolute_path = strlen( $path ) >= 3
		&& ctype_alpha( $path[0] )
		&& ':' === $path[1]
		&& in_array( $path[2], array( '\\', '/' ), true );
	$parsed_url               = wp_parse_url( $path );
	$scheme                   = $is_windows_absolute_path ? '' : ( $parsed_url['scheme'] ?? '' );

	// Remote URLs: use WP HTTP API.
	if ( in_array( $scheme, array( 'http', 'https' ), true ) ) {
		$args = array(
			'timeout'     => 15,
			'redirection' => 5,
		);

		// Optional: if you used $context to set headers/options, you could map them to $args here.
		$response = wp_safe_remote_get( $path, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	// Reject unknown schemes except file://.
	if ( $scheme && 'file' !== $scheme ) {
		return false;
	}

	// Local path (file:// or plain path).
	$local_path = ( 'file' === $scheme ) ? ( $parsed_url['path'] ?? '' ) : $path;

	if ( ! $local_path ) {
		return false;
	}

	// Use WP_Filesystem for local files.
	global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	if ( $wp_filesystem && $max_read_bytes <= 0 ) {
		$contents = $wp_filesystem->get_contents( $local_path );
		if ( false !== $contents ) {
			return $contents;
		}
	}

	// Retain native local stream access only as a fallback after WP_Filesystem fails.
	if ( ai4seo_is_function_usable( 'file_get_contents' ) ) {
		try {
			// Fallback for environments where WP_Filesystem is unavailable or fails.
			// WP_Filesystem is used as the primary method above.
			if ( $max_read_bytes > 0 ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A bounded native read prevents WP_Filesystem from allocating an oversized local file.
				$contents = @file_get_contents( $local_path, false, $context, 0, $max_read_bytes + 1 );
			} elseif ( $context ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- This is a local-only fallback after WP_Filesystem; expected stream failures retain the false contract.
				$contents = @file_get_contents( $local_path, false, $context );
			} else {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- This is a local-only fallback after WP_Filesystem; expected stream failures retain the false contract.
				$contents = @file_get_contents( $local_path );
			}

			if ( is_string( $contents ) ) {
				return $contents;
			}
		} catch ( Throwable $exception ) {
			// Stream failures retain this wrapper's false return contract after other readers have failed.
			unset( $exception );
		}
	}

	return false;
}


/**
 * Safely call set_time_limit() when available.
 *
 * @param int $seconds Requested timeout.
 * @return bool True when the limit was adjusted, false otherwise.
 */
function ai4seo_safe_set_time_limit( int $seconds ): bool {
	if ( ! ai4seo_is_function_usable( 'set_time_limit' ) ) {
		return false;
	}

	try {
		set_time_limit( $seconds );
		return true;
	} catch ( Throwable $e ) {
		return false;
	}
}


// endregion
// ___________________________________________________________________________________________.
