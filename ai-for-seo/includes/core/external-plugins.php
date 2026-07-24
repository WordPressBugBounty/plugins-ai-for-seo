<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region EXTERNAL PLUGINS ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

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

		case AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL:
			$check_this_file_path  = 'blog2social/blog2social.php';
			$check_this_class_name = 'B2S_System';
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

	return $is_active;
}


// endregion
// ___________________________________________________________________________________________.
