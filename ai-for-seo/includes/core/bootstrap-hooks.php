<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region INITIALIZATION ======================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

// Register the plugin language directory before other initialization callbacks.
add_action( 'init', 'ai4seo_load_language_files', 0 );

// init settings.
add_action( 'init', 'ai4seo_init_settings', 8 );

// Invalidate content type list caches when the posts table changes.
ai4seo_add_content_type_list_cache_invalidation_hooks();

// Keep local summary recovery available even when account-dependent cron initialization is unavailable.
add_action( AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME, AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME );

// CRON CALL ONLY.
if ( wp_doing_cron() ) {
	// init cron jobs.
	add_action( 'init', 'ai4seo_init_cron_jobs', 10 );

	// exit here.
	return;
}

// FOR FRONTEND, LOGGED-OUT USERS. ALSO FOR: LOGGED-IN USERS, ADMIN AREA
// init plugin injections for all users for the frontend.
add_action( 'init', 'ai4seo_enqueue_frontend_scripts' );
add_action( 'init', 'ai4seo_init_frontend_injections' );

// Register whitelisted AJAX functions before permission-gated user essentials.
add_action( 'init', 'ai4seo_register_ajax_actions', 8 );

// FOR LOGGED-IN USERS. ALSO FOR: ADMIN AREA
// init (logged-in) user essentials after all plugins have been loaded, used for admin area and frontend.
add_action( 'init', 'ai4seo_init_user_essentials' );

// perform ajax nonce check.
add_action( 'admin_init', 'ai4seo_on_ajax_action', 9999 );

// not admin area -> exit here.
if ( ! ai4seo_is_function_usable( 'is_admin' ) || ! is_admin() ) {
	return;
}

// init admin essentials for the backend after all plugins have been loaded.
add_action( 'init', 'ai4seo_init_admin_area_essentials', 12 );

// on plugin deactivation.
register_deactivation_hook( AI4SEO_PLUGIN_FILE, 'ai4seo_on_deactivation' );

if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
	return;
}

// init cron jobs.
add_action( 'init', 'ai4seo_init_cron_jobs' );

// do some checks after all plugins have been loaded.
add_action( 'init', 'ai4seo_check_and_handle_plugin_update' );

// check for unfinished post table analysis.
add_action( 'init', 'ai4seo_try_start_posts_table_analysis', 9 );

// check for new notifications.
add_action( 'init', 'ai4seo_check_for_new_notifications', 13 );

// init admin essentials for the backend after all plugins have been loaded.
add_action( 'init', 'ai4seo_send_additional_tos_accept_details' );

// on saving a post, check if the all ceo meta tags are filled.
add_action( 'save_post', 'ai4seo_mark_post_to_be_analyzed', 20, 3 );

// add unified cache invalidation hooks for environmental-variable based caches.
ai4seo_add_invalidate_caches_hooks();

// analyze the post after it has been saved, call ai4seo_handle_posts_to_be_analyzed() at the end of the request.
add_action( 'shutdown', 'ai4seo_handle_posts_to_be_analyzed' );

// on plugin activation.
register_activation_hook( AI4SEO_PLUGIN_FILE, 'ai4seo_on_activation' );

// Register native WordPress list bulk action integrations through the opt-in gate below.
add_action( 'admin_init', 'ai4seo_register_bulk_generation_queue_bulk_actions' );
