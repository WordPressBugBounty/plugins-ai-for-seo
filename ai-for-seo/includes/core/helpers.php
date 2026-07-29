<?php
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
 * Function to return the robhub api communicator
 *
 * @param bool $init_only The init only value.
 * @return Ai4Seo_RobHubApiCommunicator|null The robhub api communicator
 */
function ai4seo_robhub_api( $init_only = false ): ?Ai4Seo_RobHubApiCommunicator {
	global $ai4seo_robhub_api;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 232093142, 'Prevented loop', true );
		return null;
	}

	// init the robhub api communicator if not already done.
	if ( ! $ai4seo_robhub_api instanceof Ai4Seo_RobHubApiCommunicator ) {
		$ai4seo_robhub_api_path = ai4seo_get_includes_api_path( 'class-robhub-api-communicator.php' );

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Function to prevent recursive loops
 *
 * @param string $function_name The name of the function to check
 * @param int    $max_depth The maximum depth of recursion allowed (default 1, min 1)
 * @param int    $max_calls The maximum number of calls allowed globally (default 99999, min 1)
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

// =========================================================================================== \\

/**
 * Function to simulate a singleton (only one call per function per id)
 *
 * @param mixed $id The id value.
 * @return bool
 */
function ai4seo_singleton( $id ): bool {
	return ! ai4seo_prevent_loops( $id, 1, 1 );
}

// =========================================================================================== \\

/**
 * Check whether plugin translations can be loaded without triggering WordPress early-load notices.
 *
 * @return bool True when translation functions are safe to call for this text domain.
 */
function ai4seo_are_translations_ready(): bool {
	return function_exists( 'did_action' ) && did_action( 'init' ) > 0;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Return weather the given string is a valid json
 *
 * @param mixed $string The string value.
 * @return bool
 */
function ai4seo_is_json( $string ): bool {
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

// =========================================================================================== \\

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

		if ( ! in_array( $trimmed_sentence, $unique_sentences ) ) {
			$unique_sentences[] = $trimmed_sentence;
		}
	}

	// Join the unique sentences back into a single string.
	return implode( ' ', $unique_sentences );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Returns the public plugin name used in plugin UI copy.
 *
 * @return string The plugin name.
 */
function ai4seo_get_plugin_name(): string {
	return AI4SEO_PLUGIN_NAME;
}

// =========================================================================================== \\

/**
 * Returns the plugin basename
 *
 * @return string The plugin basename
 */
function ai4seo_get_plugin_basename(): string {
	return sanitize_text_field( plugin_basename( AI4SEO_PLUGIN_FILE ) );
}

// =========================================================================================== \\

/**
 * Returns a url leading to a point within the plugin
 *
 * @param string $sub_page The page to navigate to
 * @param array  $additional_parameter Additional parameters to add to the url
 * @param bool   $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo)
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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Returns the url to a specific post type within the AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME array
 *
 * @param string $post_type The post type to navigate to
 * @param int    $current_page The current page to navigate to
 * @param array  $additional_parameter Additional parameters to add to the url
 * @param bool   $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo&ai4seo_subpage=post&ai4seo_post_type=post)
 * @return string The url to the post type
 */
function ai4seo_get_post_type_page_url( string $post_type, int $current_page = 1, array $additional_parameter = array(), bool $return_full_path = true ): string {
	$additional_parameter['ai4seo_page'] = $current_page ?: '%#%'; // %#% = pagination workaround

	return ai4seo_get_subpage_url(
		AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME,
		array( 'ai4seo_post_type' => $post_type ) + $additional_parameter,
		$return_full_path
	);
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Returns whether the user is inside our plugin's admin pages
 *
 * @return bool Whether the user is inside our plugin's admin pages
 */
function ai4seo_is_user_inside_our_plugin_admin_pages(): bool {
	// check if the "page" parameter is set and if it is our plugin.
	return is_admin() && isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === AI4SEO_PLUGIN_IDENTIFIER;
}

// =========================================================================================== \\

/**
 * Checks if the active page is the given page
 *
 * @param string $plugin_page The page to check
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

// =========================================================================================== \\

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

	if ( isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ) {
		$ajax_action = sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) );
	}

	if ( ! in_array( $ajax_action, array( 'ai4seo_get_dashboard_html', 'ai4seo_refresh_dashboard_statistics' ), true ) ) {
		return false;
	}

	// ai4seo_ajax_security_gate() sets this only after nonce and permission checks passed.
	return ! empty( $GLOBALS['ai4seo_ajax_nonce'] );
}

// =========================================================================================== \\

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

	if ( isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ) {
		$ajax_action = sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) );
	}

	if ( 'ai4seo_get_dashboard_html' !== $ajax_action ) {
		return false;
	}

	// ai4seo_ajax_security_gate() sets this only after nonce and permission checks passed.
	return ! empty( $GLOBALS['ai4seo_ajax_nonce'] );
}

// =========================================================================================== \\

/**
 * Returns whether dashboard/cron-only background tasks may run in the current request.
 *
 * @return bool
 */
function ai4seo_can_run_dashboard_or_cron_tasks(): bool {
	if ( wp_doing_cron() ) {
		return true;
	}

	if ( ! ai4seo_can_manage_this_plugin() ) {
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

// =========================================================================================== \\

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
	if ( ! ai4seo_can_manage_this_plugin() ) {
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

// =========================================================================================== \\

/**
 * Checks, if the current post type is the given post type
 *
 * @param string $post_type The post type to check
 * @return bool Whether the current post type is the given post type
 */
function ai4seo_is_post_type_open( string $post_type ): bool {
	$current_post_type = ai4seo_get_active_post_type_subpage();
	return $current_post_type === $post_type;
}

// =========================================================================================== \\

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
	$potential_subpage = sanitize_key( $_GET['ai4seo_subpage'] ?? $_GET['amp;ai4seo_subpage'] ?? $_GET['ai4seo-tab'] ?? $_GET['amp;ai4seo-tab'] ?? '' );

	if ( ! $potential_subpage ) {
		$potential_subpage = ai4seo_get_default_subpage();
	}

	return $potential_subpage;
}

// =========================================================================================== \\

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

	return sanitize_key( $_GET['ai4seo_post_type'] ?? ai4seo_get_default_post_type() );
}

// =========================================================================================== \\

/**
 * Returns the default page (dashboard)
 *
 * @return string The default page
 */
function ai4seo_get_default_subpage(): string {
	return 'dashboard';
}

// =========================================================================================== \\

/**
 * Returns the default post type
 *
 * @return string The default post type
 */
function ai4seo_get_default_post_type(): string {
	return 'page';
}

// =========================================================================================== \\

/**
 * Returns the plugin directory path
 *
 * @param string $sub_path The sub path to append to the plugin directory path (optional)
 * @return string The plugin directory path
 */
function ai4seo_get_plugin_dir_path( string $sub_path = '' ): string {
	return plugin_dir_path( AI4SEO_PLUGIN_FILE ) . $sub_path;
}

// =========================================================================================== \\

/**
 * Returns the plugins base urls
 *
 * @param string $sub_path The sub path to append to the plugins base url (optional)
 * @return string The url to the file
 */
function ai4seo_get_plugins_url( string $sub_path = '' ): string {
	return plugins_url( $sub_path, AI4SEO_PLUGIN_FILE );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/modals
 *
 * @param string $sub_path The sub path to append to the includes/modals path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_modal_schemas_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/modal_schemas/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/pages
 *
 * @param string $sub_path The sub path to append to the includes/pages path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/pages/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/pages/content_types
 *
 * @param string $sub_path The sub path to append to the includes/pages/content_types path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_content_types_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/pages/content_types/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/ajax/display
 *
 * @param string $sub_path The sub path to append to the includes/ajax/display path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_display_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/ajax/display/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/ajax/process
 *
 * @param string $sub_path The sub path to append to the includes/ajax/process path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_process_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/ajax/process/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/elements
 *
 * @param string $sub_path The sub path to append to the includes/elements path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_elements_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/elements/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the path to includes/api
 *
 * @param string $sub_path The sub path to append to the includes/api path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_api_path( string $sub_path = '' ): string {
	return ai4seo_get_plugin_dir_path( "includes/api/{$sub_path}" );
}

// =========================================================================================== \\

/**
 * Returns the url to assets/images
 *
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_images_url( $file_name = '' ): string {
	return ai4seo_get_plugins_url( "assets/images/{$file_name}" );
}

// =========================================================================================== \\

/**
 * Returns the url to assets/css
 *
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_css_path( string $file_name = '' ): string {
	return ai4seo_get_plugins_url( "assets/css/{$file_name}" );
}

// =========================================================================================== \\

/**
 * Returns the url to assets/js
 *
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_js_path( string $file_name ): string {
	return ai4seo_get_plugins_url( "assets/js/{$file_name}" );
}

// =========================================================================================== \\

/**
 * Returns the url to the SOOZ logo
 *
 * @param string $variant The variant of the logo to get the url for
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

// =========================================================================================== \\

/**
 * Returns the purchase plan url
 *
 * @param string $ai4seo_client_id
 * @return string The purchase plan url
 */
function ai4seo_get_purchase_plan_url( string $ai4seo_client_id ): string {
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
 * This function uses wp_kses with our collection of allowed html tags and attributes
 *
 * @param string $content The content to sanitize.
 * @return string The sanitized content
 */
function ai4seo_wp_kses( string $content ): string {
	$allowed_html_tags_and_attributes = ai4seo_get_allowed_html_tags_and_attributes();

	return wp_kses( $content, $allowed_html_tags_and_attributes );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * This function retrieves the language code of the WordPress installation as defined in the settings
 *
 * @return string The language code of the WordPress installation
 */
function ai4seo_get_wordpress_language_code(): string {
	return get_bloginfo( 'language' );
}

// =========================================================================================== \\

/**
 * This function retrieves the language of the WordPress installation as defined in the settings
 *
 * @return string The language of the WordPress installation
 */
function ai4seo_get_wordpress_language(): string {
	$wordpress_language_code = ai4seo_get_wordpress_language_code();
	return ai4seo_get_language_long_version( $wordpress_language_code );
}

// =========================================================================================== \\

/**
 * This functions returns the long version of a given language short version (de_DE -> german)
 *
 * @param string $language_short_version The short version of the language
 * @param string $value_on_undefined The value to return if the language is not found
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

// =========================================================================================== \\

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

	return ! in_array( $function_name, explode( ',', $disabled_functions ) );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Calculate the difference in seconds between the current user timestamp and a given UTC timestamp.
 *
 * @param int $utc_timestamp The UTC timestamp to compare.
 * @return int The difference in seconds. Positive if the UTC timestamp is in the future, negative if in the past.
 */
function ai4seo_get_time_difference_in_seconds( int $utc_timestamp ): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 206072924, 'Prevented loop', true );
		return 0;
	}

	// Get the current timestamp in WordPress timezone.
	$timezone     = get_option( 'timezone_string' );
	$current_time = current_time( 'timestamp' ); // Current time in WordPress timezone.

	// If a valid timezone is set, convert UTC timestamp to WordPress timezone.
	if ( $timezone ) {
		$datetime_utc = new DateTime( "@$utc_timestamp" );
		try {
			$datetime_utc->setTimezone( new DateTimeZone( $timezone ) ); // Convert to WordPress timezone.
		} catch ( Exception $e ) {
			return $utc_timestamp - $current_time; // return the difference in seconds if timezone is invalid.
		}
		$utc_timestamp_local = strtotime( $datetime_utc->format( 'Y-m-d H:i:s' ) ); // Convert to timestamp.
	} else {
		$utc_timestamp_local = $utc_timestamp; // Default to UTC if no timezone is set.
	}

	// Calculate and return the difference in seconds.
	return $utc_timestamp_local - $current_time;
}

// =========================================================================================== \\

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
			} catch ( Exception $e ) {
				// silently ignore and fall back to normal formatting.
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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Function to convert datetime-local format to unix timestamp
 *
 * @param string $datetime_local The datetime-local string (YYYY-MM-DDTHH:MM)
 * @param string $timezone The timezone to use (auto, default: timezone_string)
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

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Function to return the clients ip
 *
 * @return string The clients ip
 */
function ai4seo_get_client_ip(): string {
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$client_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );

		if ( ai4seo_is_valid_ip( $client_ip ) ) {
			return $client_ip;
		}
	}

	if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$client_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );

		if ( ai4seo_is_valid_ip( $client_ip ) ) {
			return $client_ip;
		}
	}

	if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		$client_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

		if ( ai4seo_is_valid_ip( $client_ip ) ) {
			return $client_ip;
		}
	}

	return '';
}

// =========================================================================================== \\

/**
 * Function to return the clients user agent
 *
 * @return string The clients user agent
 */
function ai4seo_get_client_user_agent(): string {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
}

// =========================================================================================== \\

/**
 * Function to return the webservers ip
 *
 * @return string The webservers ip
 */
function ai4seo_get_server_ip(): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 863637103, 'Prevented loop', true );
		return '';
	}

	try {
		$server_ip_response = ai4seo_file_get_contents( 'https://api.ipify.org' );

		if ( false !== $server_ip_response ) {
			$server_ip = sanitize_text_field( $server_ip_response );

			if ( ai4seo_is_valid_ip( $server_ip ) ) {
				return $server_ip;
			}
		}

		if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
			$server_ip = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );

			if ( ai4seo_is_valid_ip( $server_ip ) ) {
				return $server_ip;
			}
		}
	} catch ( Exception $e ) {
		return '';
	}

	return '';
}

// =========================================================================================== \\

/**
 * Function to check if the given string is a valid ip address
 *
 * @param string $ip The ip to check
 * @return bool Whether the given string is a valid ip address
 */
function ai4seo_is_valid_ip( string $ip ): bool {
	return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
}

// =========================================================================================== \\

/**
 * Function to get the checksum of an array
 *
 * @param mixed $array The array value.
 * @return int The crc32 checksum of the array
 */
function ai4seo_get_array_checksum( $array ): int {
	return crc32( serialize( $array ) );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

function ai4seo_is_wordpress_cron_disabled(): bool {
	return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
}

// =========================================================================================== \\

function ai4seo_get_prefixed_input_name( $input_id ): string {
	return AI4SEO_POST_PARAMETER_PREFIX . $input_id;
}

// =========================================================================================== \\

function ai4seo_get_unprefixed_input_name( $input_id ): string {
	return str_replace( AI4SEO_POST_PARAMETER_PREFIX, '', $input_id );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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
		$mime_type = @mime_content_type( $normalized_path );

		if ( ! empty( $mime_type ) ) {
			return ai4seo_normalize_mime_type_string( $mime_type );
		}
	}

	if ( ai4seo_is_function_usable( 'finfo_open' ) && ai4seo_is_function_usable( 'finfo_file' ) ) {
		$file_info = finfo_open( FILEINFO_MIME_TYPE );

		if ( $file_info ) {
			$mime_type = finfo_file( $file_info, $normalized_path );
			if ( ai4seo_is_function_usable( 'finfo_close' ) ) {
				finfo_close( $file_info );
			}

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

// =========================================================================================== \\

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

	$request_arguments = array(
		'timeout'     => 5,
		'redirection' => 3,
		'user-agent'  => 'AI4SEO/' . AI4SEO_PLUGIN_VERSION_NUMBER,
		'sslverify'   => false,
	);

	if ( function_exists( 'wp_remote_head' ) ) {
		$response = wp_remote_head( $url, $request_arguments );

		if ( ! is_wp_error( $response ) ) {
			$mime_type = wp_remote_retrieve_header( $response, 'content-type' );

			$mime_type = ai4seo_normalize_mime_type_string( $mime_type );

			if ( ! empty( $mime_type ) ) {
				return $mime_type;
			}
		}
	}

	if ( function_exists( 'wp_remote_get' ) ) {
		$request_arguments['method']  = 'GET';
		$request_arguments['headers'] = array( 'Range' => 'bytes=0-1023' );

		$response = wp_remote_get( $url, $request_arguments );

		if ( ! is_wp_error( $response ) ) {
			$mime_type = wp_remote_retrieve_header( $response, 'content-type' );

			$mime_type = ai4seo_normalize_mime_type_string( $mime_type );

			if ( ! empty( $mime_type ) ) {
				return $mime_type;
			}
		}
	}

	return null;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

function ai4seo_get_attachment_post_mime_type( $attachment_post_id ): ?string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 373146404, 'Prevented loop', true );
		return null;
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || empty( $attachment_post->post_type ) ) {
		return null;
	}

	// we found it already in the post_mime_type field.
	if ( ! empty( $attachment_post->post_mime_type ) ) {
		return ai4seo_normalize_mime_type_string( $attachment_post->post_mime_type );
	}

	// fallback: try to get it from the url.
	$attachment_url = ai4seo_get_attachment_url( $attachment_post_id );

	if ( ! $attachment_url ) {
		return '';
	}

	return ai4seo_get_mime_type_from_url( $attachment_url );
}

// =========================================================================================== \\

function ai4seo_get_attachment_url( $attachment_post_id ): ?string {
	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || empty( $attachment_post->post_type ) ) {
		return null;
	}

	// check if it's an attachment.
	if ( 'attachment' === $attachment_post->post_type ) {
		// check url of the attachment.
		$ai4seo_attachment_url = wp_get_attachment_url( $attachment_post_id );
	} else {
		$ai4seo_attachment_url = get_the_guid( $attachment_post );
	}

	return $ai4seo_attachment_url;
}

// =========================================================================================== \\

/**
 * Get the best available attachment source.
 * Attention: Only use this function on a small number of attachments at once.
 *
 * Returns either a local file path or a reachable URL.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return array|null
 */
function ai4seo_get_best_attachment_source( int $attachment_post_id ): ?array {
	try {
		$attachment_post = get_post( $attachment_post_id );

		if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
			return null;
		}

		$attachment_path = get_attached_file( $attachment_post_id );

		if ( $attachment_path && file_exists( $attachment_path ) && is_readable( $attachment_path ) ) {
			return array(
				'type'   => 'path',
				'source' => $attachment_path,
			);
		}

		$attachment_url = wp_get_attachment_url( $attachment_post_id );

		if ( ! $attachment_url || wp_http_validate_url( $attachment_url ) === false ) {
			return null;
		}

		$response = wp_remote_head(
			$attachment_url,
			array(
				'timeout'     => 10,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) ) {
			$response = wp_remote_get(
				$attachment_url,
				array(
					'timeout'     => 10,
					'redirection' => 3,
					'stream'      => false,
					'headers'     => array(
						'Range' => 'bytes=0-0',
					),
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code >= 200 && $response_code < 400 ) {
			return array(
				'type'   => 'url',
				'source' => $attachment_url,
			);
		}
	} catch ( Exception $e ) {
		return null;
	}

	return null;
}

// =========================================================================================== \\

/**
 * Retrieves a formatted backtrace debug message.
 *
 * @param string $separator The separator for each backtrace entry.
 * @return string The formatted backtrace message.
 */
function ai4seo_get_backtrace_debug_message( string $separator = '<br />' ): string {
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

// =========================================================================================== \\

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

	return ai4seo_can_manage_this_plugin();
}

// =========================================================================================== \\

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

	$result = file_put_contents( $log_file_path, $log_entry, FILE_APPEND | LOCK_EX );

	if ( false === $result ) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

		if ( $backtrace = ai4seo_get_backtrace_debug_message( $backtrace_separator ) ) {
			$combined_message .= ', Backtrace:' . $backtrace_separator . $backtrace;
		}
	}

	$combined_message .= ')';

	switch ( $debug_output_mode ) {
		case 'error_log':
			error_log( $combined_message );
			return true;
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

// =========================================================================================== \\

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
				$entries[] = '[' . $property_name . '] => ' . var_export( $this_value, true );
			}
		}

		$output .= implode( ', ', $entries ) . ' )';

		return $output;
	}

	return var_export( $value, true );
}

// =========================================================================================== \\

function ai4seo_get_recommended_credits_pack_size_by_num_missing_entries(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 978225511, 'Prevented loop', true );
		return 0;
	}

	$approximate_credits_needed = ai4seo_get_approximate_credits_needed();
	$credits_packs              = ai4seo_get_credits_packs();

	// find the smallest credit pack size that is larger than the approximate credits needed
	// only consider first three entries.
	$n = 0;
	foreach ( $credits_packs as $this_credits_pack ) {
		$this_credits_amount = (int) $this_credits_pack['credits_amount'];
		++$n;

		if ( $this_credits_amount >= $approximate_credits_needed ) {
			return $this_credits_amount;
		}

		// we reached the third entry, return the current entry.
		if ( $n >= 3 ) {
			return $this_credits_amount;
		}
	}

	// fallback: return the smallest pack size.
	$first_credits_pack = reset( $credits_packs );
	return (int) ( $first_credits_pack['credits_amount'] ?? 0 );
}

// =========================================================================================== \\

function ai4seo_get_approximate_credits_needed(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 736466930, 'Prevented loop', true );
		return 0;
	}

	$approximate_credits_needed = 0;

	$num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

	if ( ! $num_missing_posts_by_post_type ) {
		return 0;
	}

	$metadata_credits_cost_per_post                 = ai4seo_calculate_metadata_credits_cost_per_post();
	$attachment_attributes_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

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

function ai4seo_get_base64_from_image_file( $image_url ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 697474987, 'Prevented loop', true );
		return array(
			'success' => false,
			'message' => 'Infinite loop detected',
			'code'    => 91234725,
		);
	}

	// Keep the local/base64 path within the same media source-size envelope as the URL-based API path.
	$max_source_size      = AI4SEO_MAX_BASE64_ATTACHMENT_SOURCE_SIZE_BYTES;
	$same_site_local_path = ai4seo_get_same_site_local_file_path_from_url( $image_url );

	// Same-site media can be measured before loading the binary into PHP memory.
	if ( $same_site_local_path && ai4seo_is_file_larger_than( $same_site_local_path, $max_source_size ) ) {
		return ai4seo_get_attachment_source_too_large_response( ai4seo_get_file_size( $same_site_local_path ) );
	}

	// Remote media with Content-Length can be rejected before the body request.
	if ( ! $same_site_local_path ) {
		$remote_content_length = ai4seo_get_remote_content_length( $image_url );

		if ( $remote_content_length > $max_source_size ) {
			return ai4seo_get_attachment_source_too_large_response( $remote_content_length );
		}
	}

	// Use bounded WP HTTP requests and local reads for fetching media contents.
	try {
		foreach ( array( 'local_only', 'remote_only', 'safe_remote_only' ) as $fetch_mode ) {
			$image_body = ai4seo_get_remote_body( $image_url, $fetch_mode, $max_source_size );

			if ( is_wp_error( $image_body ) ) {
				// Convert the internal capped-fetch marker into the same structured API-style failure used below.
				if ( $image_body->get_error_code() === 'ai4seo_fetch_too_large' ) {
					return ai4seo_get_attachment_source_too_large_response();
				}

				continue;
			}

			if ( ! $image_body ) {
				continue;
			}

			// Keep a final size guard in case the active WP HTTP transport does not honor limit_response_size.
			if ( strlen( $image_body ) > $max_source_size ) {
				return ai4seo_get_attachment_source_too_large_response( strlen( $image_body ) );
			}

			// Verify that the content is a valid image.
			$is_probably_image = ai4seo_is_probably_image_content( $image_body );

			if ( ! empty( $is_probably_image['is_probably_image'] ) ) {
				break;
			}
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => 'Media URL not accessible: ' . $e->getMessage(),
			'code'    => 91324725,
		);
	}

	if ( is_wp_error( $image_body ) ) {
		$remote_get_response_error = $image_body->get_error_message();

		return array(
			'success' => false,
			'message' => 'Media URL not accessible: ' . $remote_get_response_error,
			'code'    => 101324725,
		);
	}

	if ( ! $image_body ) {
		return array(
			'success' => false,
			'message' => 'Media content not accessible',
			'code'    => 111324725,
		);
	}

	if ( ! isset( $is_probably_image['is_probably_image'] ) || ! $is_probably_image['is_probably_image'] ) {
		return array(
			'success' => false,
			'message' => 'The fetched content is not a valid image',
			'code'    => 581927126,
		);
	}

	// Normalize the signature detector output so the encoder can report whether GD changed the format.
	$detected_image_format = strtolower( (string) ( $is_probably_image['detected_format'] ?? '' ) );
	$image_mime_types       = array(
		'jpg'   => 'image/jpeg',
		'jpeg'  => 'image/jpeg',
		'png'   => 'image/png',
		'gif'   => 'image/gif',
		'webp'  => 'image/webp',
		'avif'  => 'image/avif',
		'heif'  => 'image/heif',
		'bmp'   => 'image/bmp',
		'tiff'  => 'image/tiff',
		'ico'   => 'image/x-icon',
	);

	if ( strpos( $detected_image_format, 'image/' ) === 0 ) {
		$source_mime_type = ai4seo_normalize_mime_type_string( $detected_image_format ) ?? '';
	} else {
		$source_mime_type = $image_mime_types[ $detected_image_format ] ?? '';
	}

	// Encode the attachment while collecting the actual post-conversion MIME for the data URI.
	$encoded_mime_type = $source_mime_type;

	try {
		$attachment_base64 = ai4seo_smart_image_base64_encode(
			$image_body,
			$source_mime_type,
			$encoded_mime_type
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => 'Media content could not be base64 encoded: ' . $e->getMessage(),
			'code'    => 131324725,
		);
	}

	if ( ! $attachment_base64 ) {
		return array(
			'success' => false,
			'message' => 'Media content could not be base64 encoded',
			'code'    => 141324725,
		);
	}

	return array(
		'success'   => true,
		'data'      => $attachment_base64,
		'mime_type' => $encoded_mime_type,
	);
}

// =========================================================================================== \\

/**
 * Build a structured oversized media response for local/base64 attachment processing.
 *
 * @param int $content_length The known source size in bytes.
 * @return array
 */
function ai4seo_get_attachment_source_too_large_response( int $content_length = 0 ): array {
	// Mirror the RobHub oversized-fetch error so the existing failed-attachment handling can persist this state.
	$message = 'Content too large to fetch';

	if ( $content_length > 0 ) {
		$message .= ' (Content-Length: ' . $content_length . ' bytes)';
	}

	return array(
		'success' => false,
		'message' => $message,
		'code'    => 71214326,
	);
}

// =========================================================================================== \\

/**
 * Return the internal capped-fetch error used before an oversized source becomes a structured generation failure.
 *
 * @return WP_Error
 */
function ai4seo_get_attachment_source_too_large_wp_error(): WP_Error {
	// Use one internal marker so all capped fetch paths map to the same structured response later.
	return new WP_Error( 'ai4seo_fetch_too_large', 'Content too large to fetch' );
}

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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

// =========================================================================================== \\

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
			'sslverify'   => false,
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

// =========================================================================================== \\

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

	// Quick header hint (if you have it).
	if ( '' !== $content_type ) {
		$content_type_normalized = strtolower( trim( explode( ';', $content_type )[0] ) );

		if ( strpos( $content_type_normalized, 'image/' ) === 0 ) {
			// Still validate signature below. Some CDNs return image/* for error placeholders.
		}
	}

	// 1) Verify with getimagesizefromstring (if available).
	if ( function_exists( 'getimagesizefromstring' ) ) {
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
			finfo_close( $finfo );

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


// =========================================================================================== \\

/**
 * Fetch remote file contents with fallback strategies:
 * 1. wp_safe_remote_get (default)
 * 2. wp_safe_remote_get with 'sslverify' => false
 * 3. Local file access (if URL is local)
 * 4. download_url() fallback
 *
 * @param string $url The full URL of the media to fetch
 * @param string $attempt_type (Optional) Type of attempts to make ('all' by default)
 * @param int    $max_response_size (Optional) Maximum bytes to retrieve before returning an error.
 * @return string|WP_Error The file contents on success, or WP_Error on failure
 */
function ai4seo_get_remote_body( string $url, string $attempt_type = 'all', int $max_response_size = 0 ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 416990059, 'Prevented loop', true );
		return '';
	}

	// Attempt 1: Standard remote fetch.
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
		}
	}

	// Attempt 2: Retry with sslverify disabled (less secure).
	if ( 'safe_remote_only' === $attempt_type || 'all' === $attempt_type ) {
		$remote_get_arguments = array(
			'timeout'     => 15,
			'redirection' => 5,
			'decompress'  => true,
			'sslverify'   => false,
		);

		// Keep the SSL-disabled fallback bounded the same way as the standard remote fetch.
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
		}
	}

	// Attempt 3: Try to resolve as a local file if URL is local.
	if ( 'local_only' === $attempt_type || 'all' === $attempt_type ) {
		$local_path = ai4seo_get_same_site_local_file_path_from_url( $url );

		if ( $local_path ) {
			// Local files can fail fast on filesize() before the shared file reader loads the contents.
			if ( $max_response_size > 0 && ai4seo_is_file_larger_than( $local_path, $max_response_size ) ) {
				return ai4seo_get_attachment_source_too_large_wp_error();
			}

			$contents = ai4seo_file_get_contents( $local_path );

			if ( false !== $contents ) {
				// Keep the same final guard here as the remote fetches in case the file changes after filesize().
				if ( $max_response_size > 0 && strlen( $contents ) > $max_response_size ) {
					return ai4seo_get_attachment_source_too_large_wp_error();
				}

				return $contents;
			}
		}
	}

	// Attempt 4: Use download_url.
	if ( $max_response_size <= 0 && ( 'download_only' === $attempt_type || 'all' === $attempt_type ) ) {
		$temp_file = download_url( $url );

		if ( ! is_wp_error( $temp_file ) ) {
			$contents = ai4seo_file_get_contents( $temp_file );

			wp_delete_file( $temp_file ); // Always clean up temp file.

			if ( false !== $contents ) {
				return $contents;
			}
		}
	}

	// All attempts failed.
	return new WP_Error( 'ai4seo_fetch_failed', 'Could not fetch media contents.' );
}

// =========================================================================================== \\

/**
 * Safely measure the length of a string regardless of mbstring availability.
 *
 * @param string $string   String to measure.
 * @param string $encoding Optional encoding, defaults to UTF-8.
 * @return int             Length of the string.
 */
function ai4seo_mb_strlen( string $string, string $encoding = 'UTF-8' ): int {
	if ( function_exists( 'mb_strlen' ) ) {
		try {
			return $encoding ? mb_strlen( $string, $encoding ) : mb_strlen( $string );
		} catch ( Throwable $e ) {
			// fall back when mbstring throws (e.g. invalid encoding).
		}
	}

	if ( function_exists( 'iconv_strlen' ) ) {
		try {
			return $encoding ? iconv_strlen( $string, $encoding ) : iconv_strlen( $string );
		} catch ( Throwable $e ) {
			// continue to basic strlen fallback.
		}
	}

	return strlen( $string );
}

// =========================================================================================== \\

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
	if ( function_exists( 'mb_substr' ) ) {
		try {
			return $encoding ? mb_substr( $string, $start, $length, $encoding ) : mb_substr( $string, $start, $length );
		} catch ( Throwable $e ) {
			// fall back when mbstring throws (e.g. invalid encoding).
		}
	}

	if ( function_exists( 'iconv_substr' ) ) {
		try {
			return $encoding ? iconv_substr( $string, $start, $length, $encoding ) : iconv_substr( $string, $start, $length );
		} catch ( Throwable $e ) {
			// continue to basic substr fallback.
		}
	}

	if ( null === $length ) {
		return substr( $string, $start );
	}

	return substr( $string, $start, $length );
}

// =========================================================================================== \\

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
		} catch ( Throwable $e ) {
			// fall back when mbstring throws (e.g. invalid encoding).
		}
	}

	return strpos( $haystack, $needle, $offset );
}

// =========================================================================================== \\

/**
 * Wrapper for file_get_contents() that gracefully falls back to the WP HTTP API or stream access.
 *
 * @param string   $path    Remote URL or local path.
 * @param resource $context Optional stream context (only used when native function available).
 * @return string|false     File contents on success, false on failure.
 */
function ai4seo_file_get_contents( string $path, $context = null ) {
	$parsed_url = wp_parse_url( $path );
	$scheme     = $parsed_url['scheme'] ?? '';

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

	if ( $wp_filesystem ) {
		$contents = $wp_filesystem->get_contents( $local_path );
		if ( false !== $contents ) {
			return $contents;
		}
	}

	// Fallback: direct file_get_contents (only if you want to keep it).
	if ( ai4seo_is_function_usable( 'file_get_contents' ) ) {
		try {
			// Fallback for environments where WP_Filesystem is unavailable or fails.
			// WP_Filesystem is used as the primary method above.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
			$contents = $context
				? @file_get_contents( $local_path, false, $context )
				: @file_get_contents( $local_path );

			if ( false !== $contents ) {
				return $contents;
			}
		} catch ( Throwable $e ) {
			// continue.
		}
	}

	return false;
}

// =========================================================================================== \\

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

// =========================================================================================== \\

/**
 * Lightweight alternative to get_option() using direct $wpdb access.
 *
 * This function:
 * - Reads the option directly from the options table.
 * - Returns the provided default if the option does not exist or a DB error occurs.
 * - Unserializes the stored value using maybe_unserialize().
 *
 * @param string $option_name Name of the option to retrieve.
 * @param mixed  $default     Optional. Default value to return if the option does not exist.
 *                            Default false.
 * @param bool   $use_direct_database_call Optional. Whether to bypass get_option() and query the database directly.
 *
 * @return mixed The option value if found, otherwise the default.
 */
function ai4seo_get_option( string $option_name, $default = false, bool $use_direct_database_call = true ) {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 663145060, 'Prevented loop', true );
		return '';
	}

	// If the caller explicitly wants to use get_option() instead of direct DB access, delegate to it.
	if ( ! $use_direct_database_call ) {
		return get_option( $option_name, $default );
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return $default;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return $default;
	}

	try {
		// Directly query the options table for this specific option.
		$option_value_serialized = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value
             FROM {$wpdb->options}
             WHERE option_name = %s
             LIMIT 1",
				$option_name
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321672, 'Database error: ' . $wpdb->last_error, true );
			return $default;
		}
	} catch ( Exception $exception ) {
		// In case of DB error, fall back to the default.
		return $default;
	}

	// If no row was found, return default.
	if ( null === $option_value_serialized ) {
		return $default;
	}

	// Unserialize if needed and return.
	return maybe_unserialize( $option_value_serialized );
}

// =========================================================================================== \\

/**
 * Refreshes WordPress' targeted option cache entries after direct option table writes.
 *
 * @param string           $option_name Option name.
 * @param mixed            $option_value Option value.
 * @param bool             $option_exists Whether the option exists after the write.
 * @param string|bool|null $autoload Autoload value for the option.
 * @return void
 */
function ai4seo_refresh_option_cache( string $option_name, $option_value = null, bool $option_exists = true, $autoload = null ): void {
	// Track repaired cache states for this request so repeated writes to the same option do not repeatedly touch large cache buckets.
	static $alloptions_cache_states = array();
	static $individual_cache_states = array();
	static $notoptions_cache_states = array();

	$option_name = trim( $option_name );

	if ( '' === $option_name || ! function_exists( 'wp_cache_get' ) ) {
		return;
	}

	// Use WordPress' current autoload values when available so our cache layout matches core behavior.
	$autoload_values = array( 'yes', 'on', 'auto-on', 'auto' );
	if ( function_exists( 'wp_autoload_values_to_autoload' ) ) {
		$autoload_values = wp_autoload_values_to_autoload();
	}

	// Hash only for request-local deduplication; the actual cache value remains the serialized WordPress option value.
	$is_autoloaded                   = ( true === $autoload || in_array( (string) $autoload, $autoload_values, true ) );
	$cached_option_value             = maybe_serialize( $option_value );
	$cached_option_value_hash_source = is_string( $cached_option_value ) ? $cached_option_value : serialize( $cached_option_value );
	$cached_option_value_hash        = strlen( $cached_option_value_hash_source ) . ':' . md5( $cached_option_value_hash_source );

	// Repair alloptions only when the option belongs there or when a stale entry must be removed.
	// This avoids rewriting the full alloptions payload for frequent non-autoload status updates on large sites.
	$desired_alloptions_cache_state = ( $option_exists && $is_autoloaded ) ? 'autoloaded:' . $cached_option_value_hash : 'not-autoloaded';

	if ( ( $alloptions_cache_states[ $option_name ] ?? null ) !== $desired_alloptions_cache_state ) {
		$alloptions = wp_cache_get( 'alloptions', 'options' );

		if ( is_array( $alloptions ) ) {
			$did_update_alloptions = false;

			if ( $option_exists && $is_autoloaded ) {
				if ( ! isset( $alloptions[ $option_name ] ) || $alloptions[ $option_name ] !== $cached_option_value ) {
					$alloptions[ $option_name ] = $cached_option_value;
					$did_update_alloptions      = true;
				}
			} elseif ( isset( $alloptions[ $option_name ] ) ) {
				unset( $alloptions[ $option_name ] );
				$did_update_alloptions = true;
			}

			if ( $did_update_alloptions ) {
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}
		}

		$alloptions_cache_states[ $option_name ] = $desired_alloptions_cache_state;
	}

	// Keep the individual option cache in the same shape WordPress core expects.
	// Autoloaded options live in alloptions, non-autoloaded options live under their own option key.
	if ( $option_exists && $is_autoloaded ) {
		if ( ( $individual_cache_states[ $option_name ] ?? null ) !== 'deleted' ) {
			wp_cache_delete( $option_name, 'options' );
			$individual_cache_states[ $option_name ] = 'deleted';
		}
	} elseif ( $option_exists ) {
		$desired_individual_cache_state = 'value:' . $cached_option_value_hash;

		if ( ( $individual_cache_states[ $option_name ] ?? null ) !== $desired_individual_cache_state ) {
			wp_cache_set( $option_name, $cached_option_value, 'options' );
			$individual_cache_states[ $option_name ] = $desired_individual_cache_state;
		}
	} elseif ( ( $individual_cache_states[ $option_name ] ?? null ) !== 'deleted' ) {
			wp_cache_delete( $option_name, 'options' );
			$individual_cache_states[ $option_name ] = 'deleted';
	}

	// Keep notoptions aligned so a previously missing option starts resolving immediately after insert/update,
	// and a deleted option does not trigger repeated database lookups.
	$desired_notoptions_cache_state = $option_exists ? 'exists' : 'missing';

	if ( ( $notoptions_cache_states[ $option_name ] ?? null ) === $desired_notoptions_cache_state ) {
		return;
	}

	$notoptions            = wp_cache_get( 'notoptions', 'options' );
	$did_update_notoptions = false;

	if ( $option_exists ) {
		if ( is_array( $notoptions ) && isset( $notoptions[ $option_name ] ) ) {
			unset( $notoptions[ $option_name ] );
			$did_update_notoptions = true;
		}
	} else {
		if ( ! is_array( $notoptions ) ) {
			$notoptions            = array();
			$did_update_notoptions = true;
		}

		if ( ! isset( $notoptions[ $option_name ] ) ) {
			$notoptions[ $option_name ] = true;
			$did_update_notoptions      = true;
		}
	}

	if ( $did_update_notoptions ) {
		wp_cache_set( 'notoptions', $notoptions, 'options' );
	}

	$notoptions_cache_states[ $option_name ] = $desired_notoptions_cache_state;
}

// =========================================================================================== \\

/**
 * Update or insert an option using direct $wpdb access.
 *
 * This function behaves similar to update_option(), but bypasses the core
 * update_option() internals and writes directly to the options table.
 *
 * - Inserts the option if it does not exist.
 * - Updates the option if it exists and the value has changed.
 * - Returns false if the value is unchanged or on failure.
 * - Synchronizes the options cache so get_option() sees the new value.
 *
 * @param string           $option_name   Name of the option to update.
 * @param mixed            $option_value  Value to store. Will be maybe_serialize()'d.
 * @param string|bool|null $autoload Optional. Whether to load the option when WordPress starts up.
 *                                   Accepts 'yes', 'no', true, false, or null.
 *                                   Null keeps existing autoload or defaults to 'yes' on insert.
 * @param bool             $use_direct_database_call Optional. Whether to bypass update_option() and query the database directly.
 *
 * @return bool True if the option value was changed or added, false otherwise.
 */
function ai4seo_update_option( string $option_name, $option_value, $autoload = false, bool $use_direct_database_call = true ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 635985897, 'Prevented loop', true );
		return false;
	}

	// If the caller explicitly wants to use update_option() instead of direct DB access, delegate to it.
	if ( ! $use_direct_database_call ) {
		// Capture the previous membership before WordPress mutates status options so reconciliation can use a delta.
		$old_value = get_option( $option_name, null );
		$result = update_option( $option_name, $option_value, $autoload );

		if ( $result ) {
			ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

			// The tracker loads after this helper but is available whenever runtime option mutations occur.
			if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
				ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, $option_value );
			}
		}

		return $result;
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return false;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return false;
	}

	// Use ai4seo_get_option() with a distinct default so we can detect non-existent options.
	$old_value = ai4seo_get_option( $option_name, null, $use_direct_database_call );

	// Normalize new vs old for comparison using serialization, matching core semantics.
	$serialized_new = maybe_serialize( $option_value );
	$serialized_old = ( null === $old_value ) ? null : maybe_serialize( $old_value );

	// If option exists and the value is identical, do nothing (same as update_option()).
	if ( null !== $old_value && $serialized_new === $serialized_old ) {
		$existing_autoload = null;

		// The database already contains the right value, but the object cache may still be stale.
		// Read autoload so the cache repair writes the value to the same cache bucket WordPress would use.
		try {
			$existing_autoload = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT autoload
                     FROM {$wpdb->options}
                     WHERE option_name = %s
                     LIMIT 1",
					$option_name
				)
			);
		} catch ( Throwable $e ) {
			$existing_autoload = null;
		}

		// Refresh cache even for no-op updates; this is the self-healing path for outdated persistent object-cache drop-ins.
		ai4seo_refresh_option_cache( $option_name, $option_value, true, $existing_autoload );
		ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
		return true;
	}

	// Read the current row so we can preserve or inspect autoload and existence.
	try {
		// ai4seo_debug_message(984321671, "Existing value: " . ai4seo_stringify($old_value) . ", New value: " . ai4seo_stringify($option_value), true);.
		$existing_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_id, option_value, autoload
                 FROM {$wpdb->options}
                 WHERE option_name = %s
                 LIMIT 1",
				$option_name
			),
			ARRAY_A
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321673, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	} catch ( Throwable $e ) {
		return false;
	}

	$is_insert = ( null === $existing_row );

	// Resolve autoload value.
	if ( null === $autoload ) {
		if ( false === $is_insert && isset( $existing_row['autoload'] ) && '' !== $existing_row['autoload'] ) {
			$autoload = $existing_row['autoload'];
		} else {
			// Default autoload behavior in WordPress is 'yes' for new options.
			$autoload = 'yes';
		}
	} else {
		// Normalize autoload to 'yes' / 'no'.
		if ( 'no' === $autoload || false === $autoload || ( is_string( $autoload ) && strtolower( $autoload ) === 'no' ) ) {
			$autoload = 'no';
		} else {
			$autoload = 'yes';
		}
	}

	// Perform insert or update via $wpdb.
	try {
		if ( true === $is_insert ) {
			$result = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $serialized_new,
					'autoload'     => $autoload,
				),
				array(
					'%s',
					'%s',
					'%s',
				)
			);

			if ( false === $result ) {
				return false;
			}

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321674, 'Database error: ' . $wpdb->last_error, true );
				return false;
			}
		} else {
			$result = $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => $serialized_new,
					'autoload'     => $autoload,
				),
				array(
					'option_name' => $option_name,
				),
				array(
					'%s',
					'%s',
				),
				array(
					'%s',
				)
			);

			// $result can be 0 if nothing changed on DB-level, but we already filtered that above.
			if ( false === $result ) {
				return true;
			}

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321675, 'Database error: ' . $wpdb->last_error, true );
				return false;
			}
		}

		// Synchronize targeted option caches after the direct SQL write so get_option() sees the new value immediately.
		ai4seo_refresh_option_cache( $option_name, $option_value, true, $autoload );
		ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
		ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

		// Record only successful source-option mutations; no-op cache repairs returned before this point.
		if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
			ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, $option_value );
		}
	} catch ( Throwable $e ) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Delete an option using direct $wpdb access.
 *
 * This function:
 * - Deletes the option row directly from the options table.
 * - Returns true when at least one row was removed, false otherwise.
 * - Wraps all $wpdb operations in a try/catch block.
 * - Synchronizes the options cache so get_option() and friends stay in sync.
 *
 * No hooks or actions are triggered.
 *
 * @param string $option_name Name of the option to delete.
 * @param bool   $use_direct_database_call Optional. Whether to bypass delete_option() and query the database directly.
 *
 * @return bool True if the option was deleted, false on failure or if it did not exist.
 */
function ai4seo_delete_option( string $option_name, bool $use_direct_database_call = true ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__, 2 ) ) {
		ai4seo_debug_message( 980160314, 'Prevented loop', true );
		return false;
	}

	if ( ! $use_direct_database_call ) {
		// Capture source-option membership before deletion so the shared tracker can reconcile removed IDs.
		$old_value = get_option( $option_name, null );
		$result = delete_option( $option_name );

		if ( $result ) {
			ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

			// Deletions use the same request-level reconciliation path as ordinary option updates.
			if ( function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
				ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, array() );
			}
		}

		return $result;
	}

	if ( ! isset( $wpdb ) || ! $wpdb ) {
		return false;
	}

	$option_name = trim( $option_name );

	if ( '' === $option_name ) {
		return false;
	}

	// Read before direct SQL deletion because this writer intentionally bypasses WordPress option hooks.
	$old_value = ai4seo_get_option( $option_name, null, true );

	try {
		// Delete the option row directly from the options table.
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321676, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	} catch ( Exception $exception ) {
		// On DB error, indicate failure.
		return false;
	}

	// Remove targeted cache entries even if the DB row was already gone, because stale cache can outlive direct deletes.
	ai4seo_refresh_option_cache( $option_name, null, false, false );
	ai4seo_maybe_reset_generation_status_summary_request_cache( $option_name );
	ai4seo_maybe_bump_content_type_list_cache_version( $option_name );

	// Track only an actual row removal; cache-only cleanup does not change authoritative membership.
	if ( 0 < (int) $result && function_exists( 'ai4seo_track_generation_status_summary_option_change' ) ) {
		ai4seo_track_generation_status_summary_option_change( $option_name, $old_value, array() );
	}

	// If query failed or no rows were affected, return true.
	if ( false === $result || 0 === (int) $result ) {
		return true;
	}

	return true;
}


// endregion
// ___________________________________________________________________________________________.
