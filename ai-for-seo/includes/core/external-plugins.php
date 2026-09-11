<?php
/**
 * Provides compatibility with external plugins.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region EXTERNAL PLUGINS ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Determine whether post content contains an ACF block marker.
 *
 * @param string $post_content Post content to inspect.
 * @return bool Whether an ACF block marker is present.
 */
function ai4seo_is_acf_content( $post_content ): bool {
	return strpos( $post_content, '<!-- wp:acf/' ) !== false;
}


/**
 * Extract user-facing field values from serialized ACF block comments.
 *
 * @param string $post_content Post content containing ACF blocks.
 * @return string Extracted ACF field content.
 */
function ai4seo_extract_acf_content( $post_content ): string {
	// Initialize an array to hold the extracted content.
	$extracted_content = array();

	// Match all ACF blocks in the post_content.
	preg_match_all( '/<!-- wp:acf\/(.*?) (.*?)\/-->/s', $post_content, $matches, PREG_SET_ORDER );

	// Loop through each ACF block match.
	foreach ( $matches as $match ) {
		// Decode the JSON data for the ACF block.
		$acf_data = json_decode( $match[2], true );

		if ( isset( $acf_data['data'] ) ) {
			// Loop through the 'data' array and extract field content.
			foreach ( $acf_data['data'] as $key => $value ) {
				// Skip metadata fields (fields starting with an underscore).
				if ( strpos( $key, '_' ) === 0 ) {
					continue;
				}

				// Add the content to the extracted content array.
				if ( ! empty( $value ) ) {
					$extracted_content[] = $value;
				}
			}
		}
	}

	// Return the extracted content as a plain text string.
	return implode( ' ', $extracted_content );
}


/**
 * Contains the cache-addition suspension leaked by Fix Alt Text 1.9.1 save callbacks.
 *
 * Run immediately before the vendor's priority 999 callbacks. Only replace the
 * known registration in place, retaining its identity, position and argument count
 * so has_action() and remove_action() continue to recognize the original callback.
 * The vendor's background scanner and previously active suspensions remain untouched.
 *
 * @return void
 */
function ai4seo_prepare_fix_alt_text_cache_scope(): void {
	global $wp_filter;

	// Limit ownership of the leaked suspension to the verified vendor implementation without autoloading it.
	if ( ! defined( 'FIXALTTEXT_VERSION' ) || '1.9.1' !== FIXALTTEXT_VERSION || ! class_exists( 'FixAltText\\Scan', false ) ) {
		return;
	}

	// Match each supported WordPress action to the vendor's original method and argument contract.
	$hook_name = current_filter();
	switch ( $hook_name ) {
		case 'save_post':
		case 'attachment_updated':
		case 'add_attachment':
			$method_name   = 'save_post_scan';
			$accepted_args = 1;
			break;
		case 'saved_term':
			$method_name   = 'save_term_scan';
			$accepted_args = 3;
			break;
		case 'delete_term':
			$method_name   = 'delete_term_scan';
			$accepted_args = 3;
			break;
		default:
			return;
	}

	// Only native hook containers expose the registration structure that can be replaced in place.
	if ( ! isset( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
		return;
	}

	// An existing wrapper or any third-party registration change falls outside the verified contract.
	$original_callback = array( 'FixAltText\\Scan', $method_name );
	$callback_id       = 'FixAltText\\Scan::' . $method_name;
	$registration      = $wp_filter[ $hook_name ]->callbacks[999][ $callback_id ] ?? null;

	if (
		! is_array( $registration )
		|| ( $registration['function'] ?? null ) !== $original_callback
		|| ( $registration['accepted_args'] ?? null ) !== $accepted_args
		|| ! is_callable( $original_callback )
	) {
		return;
	}

	// Replacing only the callable also avoids reordering other callbacks at priority 999.
	$wp_filter[ $hook_name ]->callbacks[999][ $callback_id ]['function'] = static function ( ...$args ) use ( $original_callback ): void {
		// Capture ownership before the scan so an outer caller's suspension survives nested callbacks.
		$was_suspended = wp_suspend_cache_addition();
		$guard_existed = defined( 'FIXALTTEXT_HELPERSLIBRARY_DONOTCACHE_WP' );

		// Always restore an owned leak while allowing the original exception to reach its caller.
		try {
			call_user_func_array( $original_callback, $args );
		} finally {
			// The vendor sets this request-wide guard exactly when it first suspends additions.
			// Nested scans and later independent suspensions must retain their existing state.
			if ( ! $was_suspended && ! $guard_existed && defined( 'FIXALTTEXT_HELPERSLIBRARY_DONOTCACHE_WP' ) && wp_suspend_cache_addition() ) {
				wp_suspend_cache_addition( false );
			}
		}
	};
}


/**
 * Activates the plugin/theme detection cache for the current site identity.
 *
 * @return bool Whether the current site identity was available.
 */
function ai4seo_prepare_active_plugins_and_themes_request_cache_for_current_site(): bool {
	global $ai4seo_active_plugins_and_themes_request_cache_by_site;
	global $ai4seo_active_plugins_and_themes_request_cache_scope;
	global $ai4seo_cached_active_plugins_and_themes;

	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	if ( ! is_array( $ai4seo_active_plugins_and_themes_request_cache_by_site ) ) {
		$ai4seo_active_plugins_and_themes_request_cache_by_site = array();
	}

	if ( ! is_string( $ai4seo_active_plugins_and_themes_request_cache_scope ) ) {
		$ai4seo_active_plugins_and_themes_request_cache_scope = '';
	}

	if ( ! is_array( $ai4seo_cached_active_plugins_and_themes ) ) {
		$ai4seo_cached_active_plugins_and_themes = array();
	}

	if ( $current_scope === $ai4seo_active_plugins_and_themes_request_cache_scope ) {
		return true;
	}

	if ( '' !== $ai4seo_active_plugins_and_themes_request_cache_scope ) {
		$ai4seo_active_plugins_and_themes_request_cache_by_site[ $ai4seo_active_plugins_and_themes_request_cache_scope ] = $ai4seo_cached_active_plugins_and_themes;
	} elseif ( $ai4seo_cached_active_plugins_and_themes ) {
		// Adopt a legacy/test flat cache on its first scoped access.
		$ai4seo_active_plugins_and_themes_request_cache_scope                     = $current_scope;
		$ai4seo_active_plugins_and_themes_request_cache_by_site[ $current_scope ] = $ai4seo_cached_active_plugins_and_themes;
		return true;
	}

	$ai4seo_active_plugins_and_themes_request_cache_scope = $current_scope;
	$current_cache                                        = $ai4seo_active_plugins_and_themes_request_cache_by_site[ $current_scope ] ?? array();
	$ai4seo_cached_active_plugins_and_themes              = is_array( $current_cache ) ? $current_cache : array();

	return true;
}


/**
 * Stores the current flat plugin/theme detection view in its exact site record.
 *
 * @return bool Whether the current scoped record was stored.
 */
function ai4seo_store_active_plugins_and_themes_request_cache_for_current_site(): bool {
	global $ai4seo_active_plugins_and_themes_request_cache_by_site;
	global $ai4seo_cached_active_plugins_and_themes;

	if ( ! ai4seo_prepare_active_plugins_and_themes_request_cache_for_current_site() ) {
		return false;
	}

	$current_scope = ai4seo_get_site_options_request_cache_scope();
	$ai4seo_active_plugins_and_themes_request_cache_by_site[ $current_scope ] = $ai4seo_cached_active_plugins_and_themes;

	return true;
}


/**
 * Clears only the current site's plugin/theme detection cache.
 *
 * @return bool Whether the current site identity was available and reset.
 */
function ai4seo_reset_active_plugins_and_themes_request_cache_for_current_site(): bool {
	global $ai4seo_active_plugins_and_themes_request_cache_by_site;
	global $ai4seo_active_plugins_and_themes_request_cache_scope;
	global $ai4seo_cached_active_plugins_and_themes;

	$current_scope = ai4seo_get_site_options_request_cache_scope();

	if ( '' === $current_scope ) {
		return false;
	}

	if ( is_array( $ai4seo_active_plugins_and_themes_request_cache_by_site ) ) {
		unset( $ai4seo_active_plugins_and_themes_request_cache_by_site[ $current_scope ] );
	} else {
		$ai4seo_active_plugins_and_themes_request_cache_by_site = array();
	}

	if ( $current_scope === $ai4seo_active_plugins_and_themes_request_cache_scope ) {
		$ai4seo_cached_active_plugins_and_themes = array();
	}

	return true;
}

/**
 * Returns weather a plugin or theme is active
 *
 * @param mixed $identifier The identifier value.
 * @return bool
 */
function ai4seo_is_plugin_or_theme_active( $identifier ): bool {
	global $ai4seo_cached_active_plugins_and_themes;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 406909321, 'Prevented loop', true );
		return false;
	}

	if ( ! ai4seo_prepare_active_plugins_and_themes_request_cache_for_current_site() ) {
		return false;
	}

	// try use cache first.
	if ( isset( $ai4seo_cached_active_plugins_and_themes[ $identifier ] ) ) {
		return $ai4seo_cached_active_plugins_and_themes[ $identifier ];
	}

	// Make sure that plugin-file has been loaded.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		return false;
	}

	$is_active             = false;
	$check_this_theme_name = '';
	$check_this_file_path  = '';
	$check_this_class_name = '';

	switch ( $identifier ) {
		// editors.
		case AI4SEO_THIRD_PARTY_PLUGIN_BETHEME:
			$check_this_theme_name = 'Betheme';
			break;
		case AI4SEO_THIRD_PARTY_PLUGIN_ELEMENTOR:
			$check_this_file_path  = 'elementor/elementor.php';
			$check_this_class_name = 'Elementor\Plugin';
			break;

		// shops.
		case AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE:
			$check_this_file_path  = 'woocommerce/woocommerce.php';
			$check_this_class_name = 'WooCommerce';
			break;

		// multi-language.
		case AI4SEO_THIRD_PARTY_PLUGIN_WPML:
			$check_this_file_path  = 'sitepress-multilingual-cms/sitepress.php';
			$check_this_class_name = 'SitePress';
			break;

		// seo plugins.
		case AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO:
			$check_this_file_path  = 'wordpress-seo/wp-seo.php';
			$check_this_class_name = 'WPSEO_Meta';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO:
			$check_this_file_path  = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
			$check_this_class_name = 'AIOSEO\Plugin\AIOSEO';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH:
			$check_this_file_path  = 'seo-by-rank-math/rank-math.php';
			$check_this_class_name = 'RankMath';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK:
			$check_this_file_path  = 'seo-simple-pack/seo-simple-pack.php';
			$check_this_class_name = 'SEO_SIMPLE_PACK';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS:
			$check_this_file_path  = 'wp-seopress/seopress.php';
			$check_this_class_name = 'SEOPress\Core\Kernel';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO:
			$check_this_file_path  = 'slim-seo/slim-seo.php';
			$check_this_class_name = 'SlimSEO\\Core';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO:
			$check_this_file_path  = 'squirrly-seo/squirrly.php';
			$check_this_class_name = 'SQ_Classes_ObjController';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK:
			$check_this_file_path = 'autodescription/autodescription.php';
			// do not check for class, as it is not unique, as the plugin uses a load system.
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY:
			$check_this_file_path  = 'nextgen-gallery/nggallery.php';
			$check_this_class_name = 'C_NextGEN_Bootstrap';
			break;

		case AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY:
			$check_this_file_path  = 'seo-key/seo-key.php';
			$check_this_class_name = 'SEOKEY_Free';
			break;
	}

	do {
		// check for a specific theme.
		if ( $check_this_theme_name ) {
			$current_theme = wp_get_theme();
			$parent_theme  = $current_theme->parent();

			// Check if betheme is active.
			$is_active = $current_theme->get( 'Name' ) === $check_this_theme_name || ( $parent_theme && $parent_theme->get( 'Name' ) === $check_this_theme_name );

			if ( ! $is_active ) {
				break;
			}
		}

		// check for a specific plugin -> check path.
		if ( $check_this_file_path ) {
			try {
				$is_active = is_plugin_active( $check_this_file_path );
			} catch ( Exception $e ) {
				$is_active = false;
			}

			if ( ! $is_active ) {
				break;
			}
		}

		// check for a specific plugin -> check class.
		if ( $check_this_class_name ) {
			try {
				$is_active = class_exists( $check_this_class_name );
			} catch ( Exception $e ) {
				$is_active = false;
			}

			if ( ! $is_active ) {
				break;
			}
		}
	} while ( false );

	// update cache.
	$ai4seo_cached_active_plugins_and_themes[ $identifier ] = $is_active;
	ai4seo_store_active_plugins_and_themes_request_cache_for_current_site();

	return $is_active;
}

// region FRONTEND CACHE INTEGRATIONS =========================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Best-effort purge for a single post/page URL across common caching layers.
 *
 * @param int        $post_id Post ID.
 * @param array|null $diagnostics Receives optional cache failure identities.
 * @return void
 */
function ai4seo_purge_frontend_cache_for_post( int $post_id, ?array &$diagnostics = null ): void {
	$diagnostics                     = array();
	$is_frontend_cache_purge_enabled = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE );

	if ( ! $is_frontend_cache_purge_enabled ) {
		return;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 371792553, 'Prevented loop', true );
		return;
	}

	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return;
	}

	// Post-cache hooks are optional follow-up work and must not prevent the remaining frontend purges.
	try {
		clean_post_cache( $post_id );
	} catch ( Throwable $throwable ) {
		$diagnostics[] = ai4seo_record_metadata_save_diagnostic( 1908261200, 'cache', 'post_cache_exception', array( 'post_id' => $post_id ), $throwable );
	}

	// URL-based integrations require a permalink, so retain its failure and stop only those follow-up purges.
	try {
		$permalink = get_permalink( $post_id );
	} catch ( Throwable $throwable ) {
		$diagnostics[] = ai4seo_record_metadata_save_diagnostic( 1908261200, 'cache', 'permalink_exception', array( 'post_id' => $post_id ), $throwable );
		return;
	}

	if ( empty( $permalink ) ) {
		return;
	}

	// Preserve both the post-cache failure and any individual URL integration failures for the save response.
	$url_diagnostics = array();
	ai4seo_purge_frontend_cache_for_url( $permalink, $url_diagnostics );
	$diagnostics = array_merge( $diagnostics, $url_diagnostics );
}


/**
 * Best-effort purge for a single URL across common caching plugins.
 *
 * Note: This cannot purge CDN/browser caches unless your setup integrates them.
 *
 * @param string     $url Absolute URL.
 * @param array|null $diagnostics Receives optional cache failure identities.
 * @return void
 */
function ai4seo_purge_frontend_cache_for_url( string $url, ?array &$diagnostics = null ): void {
	$diagnostics = array();
	$url         = esc_url_raw( $url );

	if ( empty( $url ) ) {
		return;
	}

	// One optional integration must not abort the remaining cache purges.
	try {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed defines this public integration hook.
		do_action( 'litespeed_purge_url', $url );
	} catch ( Throwable $throwable ) {
		$diagnostics[] = ai4seo_record_metadata_save_diagnostic( 1908261200, 'cache', 'cache_exception', array( 'provider' => 'litespeed' ), $throwable );
	}

	// Preserve each plugin's API shape; empty argument lists retain its existing whole-cache purge behavior.
	$purges = array(
		'rocket_clean_files'                    => array( array( $url ) ),
		'w3tc_flush_url'                        => array( $url ),
		'wp_cache_clear_cache'                  => array(),
		'sg_cachepress_purge_cache'             => array(),
		'cache_enabler_clear_page_cache_by_url' => array( $url ),
		'wp_optimize_cache_purge_url'           => array( $url ),
		'wpfc_clear_url_cache'                  => array( $url ),
	);

	// Skip unavailable integrations and isolate each installed callback so later purges can still run.
	foreach ( $purges as $callback => $arguments ) {
		if ( ! function_exists( $callback ) ) {
			continue;
		}

		try {
			call_user_func_array( $callback, $arguments );
		} catch ( Throwable $throwable ) {
			$diagnostics[] = ai4seo_record_metadata_save_diagnostic( 1908261200, 'cache', 'cache_exception', array( 'provider' => $callback ), $throwable );
		}
	}
}


// endregion
// ___________________________________________________________________________________________.

// endregion
// ___________________________________________________________________________________________.
