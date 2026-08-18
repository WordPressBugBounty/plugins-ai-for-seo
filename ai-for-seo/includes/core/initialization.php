<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === INIT FUNCTIONS ======================================================================== \\

// Register the bundled translation path only after WordPress can coordinate just-in-time text-domain loading.
/**
 * Registers the plugin language directory with WordPress.
 *
 * @return void
 */
function ai4seo_load_language_files() {
	load_plugin_textdomain(
		'ai-for-seo',
		false,
		dirname( plugin_basename( AI4SEO_PLUGIN_FILE ) ) . '/languages'
	);
}

// =========================================================================================== \\

/**
 * Register all whitelisted logged-in AJAX actions.
 *
 * Permission checks happen in ai4seo_ajax_security_gate().
 *
 * @return void
 */
function ai4seo_register_ajax_actions() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	foreach ( AI4SEO_ALLOWED_AJAX_FUNCTIONS as $this_ajax_function ) {
		if ( ! function_exists( $this_ajax_function ) ) {
			ai4seo_debug_message( 1212181226, 'Allowed AJAX function does not exist: ' . $this_ajax_function, true );
			continue;
		}

		add_action( "wp_ajax_{$this_ajax_function}", $this_ajax_function );
	}
}

// =========================================================================================== \\

/**
 * Function to init plugin essentials for admins in the front and backend
 *
 * @return void
 */
function ai4seo_init_user_essentials() {
	// Make sure that the user is allowed to use this plugin.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// enqueue scripts and styles.
	add_action( 'wp_enqueue_scripts', 'ai4seo_enqueue_primary_assets' );
	add_action( 'admin_enqueue_scripts', 'ai4seo_enqueue_primary_assets' );

	// add modal schemas to the footer.
	add_action( 'wp_footer', 'ai4seo_include_modal_schemas_file' );
	add_action( 'get_footer', 'ai4seo_include_modal_schemas_file' );
	add_action( 'admin_footer', 'ai4seo_include_modal_schemas_file' );

	// register compatibility with other plugins.
	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY ) ) {
		register_post_type(
			AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
			array(
				'label'               => AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title', 'editor' ),
				'can_export'          => false,
				'show_in_rest'        => false,
			)
		);
	}

	// user needs to accept tos? stop here, to prevent further plugin actions.
	if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return;
	}

	// admin bar menu item.
	add_action( 'admin_bar_menu', 'ai4seo_add_admin_menu_item', 999 );
}

// =========================================================================================== \\

/**
 * Function to init plugin essentials for admins in the backend
 *
 * @return void
 */
function ai4seo_init_admin_area_essentials() {
	// ADMIN AREA ONLY (REST OF INIT CODE FROM HERE) ->
	// make sure the robhub api communicator is initialized before anything else.
	try {
		if ( ! ai4seo_robhub_api( true ) || ! ai4seo_robhub_api()->is_initialized ) {
			// if the robhub api communicator could not be initialized, we cannot continue
			// show notice.
			if ( ai4seo_can_manage_this_plugin() ) {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-error"><p>';
						sprintf(
							/* translators: %s: plugin name */
							esc_html__( "The %s plugin could not be initialized. Class 'Ai4Seo_RobHubApiCommunicator' could not be initialized. Please check your server configuration and try again.", 'ai-for-seo' ),
							esc_html( AI4SEO_PLUGIN_NAME )
						);
						echo '</p></div>';
					}
				);
			}

			// exit here.
			return;
		}
	} catch ( Throwable $e ) {
		// could not initialize the robhub api communicator -> abort here, echoing a notice.
		if ( ai4seo_can_manage_this_plugin() ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					sprintf(
						/* translators: %s: plugin name */
						esc_html__( "The %s plugin could not be initialized. Class 'Ai4Seo_RobHubApiCommunicator' could not be initialized. Please check your server configuration and try again.", 'ai-for-seo' ),
						esc_html( AI4SEO_PLUGIN_NAME )
					);
					echo '</p></div>';
				}
			);
		}

		return;
	}

	// Overwrite the plugin-details for white-label-settings.
	add_filter( 'all_plugins', 'ai4seo_modify_plugin_details_for_white_label', 10, 1 );

	// Make sure that the user is allowed to use this plugin.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$is_user_inside_our_plugin_admin_pages = ai4seo_is_user_inside_our_plugin_admin_pages();

	// Add menu-item to main menu, sub menu-items and page titles.
	add_filter( 'admin_title', 'ai4seo_filter_admin_title', 10, 2 );
	add_filter( 'admin_body_class', 'ai4seo_add_admin_body_class' );
	add_action( 'admin_menu', 'ai4seo_add_menu_entries' );
	add_filter( 'parent_file', 'ai4seo_mark_parent_menu_active' );
	add_filter( 'submenu_file', 'ai4seo_mark_submenu_active' );

	// plugin action link use filter "plugin_action_links_ + plugin_basename".
	$this_plugin_basename = sanitize_text_field( ai4seo_get_plugin_basename() );
	add_filter( "plugin_action_links_{$this_plugin_basename}", 'ai4seo_add_links_to_the_plugin_directory', 999 );

	$does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();

	if ( $does_user_need_to_accept_tos_toc_and_pp ) {
		// Register the footer output only when the shared request-level TOS decision also enables its assets and schema.
		if ( ai4seo_should_show_terms_of_service_modal_on_current_request() ) {
			add_action( 'admin_footer', 'ai4seo_show_terms_of_service_modal' );
		}

		// stop here, to prevent further plugin actions.
		return;
	}

	// put our code into the post and page table.
	add_filter( 'manage_post_posts_columns', 'ai4seo_add_metadata_editor_column_to_posts_table' );
	add_filter( 'manage_page_posts_columns', 'ai4seo_add_metadata_editor_column_to_posts_table' );
	// add_filter("manage_edit-product_columns", "ai4seo_add_metadata_editor_column_to_posts_table");.
	add_action( 'manage_post_posts_custom_column', 'ai4seo_add_metadata_editor_button_to_posts_table', 10, 2 );
	add_action( 'manage_page_posts_custom_column', 'ai4seo_add_metadata_editor_button_to_posts_table', 10, 2 );
	// add_action("manage_product_posts_custom_column", "ai4seo_add_metadata_editor_button_to_posts_table", 10, 2);.

	// add ajax nonce field to the footer.
	add_action( 'admin_print_footer_scripts', 'ai4seo_print_ajax_nonce_field' );

	// if user is inside our plugin admin pages, check for account sync.
	if ( $is_user_inside_our_plugin_admin_pages ) {
		ai4seo_check_for_robhub_account_sync();
	}
}

// =========================================================================================== \\

/**
 * Function to init the plugin-settings
 *
 * @return void
 */
function ai4seo_init_settings() {
	global $ai4seo_settings;
	global $ai4seo_are_settings_initialized;

	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 10 ) ) {
		ai4seo_debug_message( 223772145, 'Prevented loop', true );
		return;
	}

	// Read settings from database.
	$from_database_settings = ai4seo_read_settings();

	// Loop through settings and add the new values to $ai4seo_settings.
	foreach ( $from_database_settings as $setting_name => $setting_value ) {
		// Normalize legacy or imported instruction values before generic setting validation runs.
		$setting_value = ai4seo_normalize_custom_instructions_setting_value( $setting_name, $setting_value );

		// Convert supported stored boolean representations before strict type validation.
		$setting_value = ai4seo_normalize_boolean_setting_value( $setting_name, $setting_value );

		// Make sure that this setting is valid.
		if ( ! ai4seo_validate_setting_value( $setting_name, $setting_value ) ) {
			continue;
		}

		// Save the new values to $ai4seo_settings.
		$ai4seo_settings[ $setting_name ] = $setting_value;
	}

	$ai4seo_are_settings_initialized = true;
}

// =========================================================================================== \\

function ai4seo_read_settings(): array {
	// prevent infinite loops (1 depth, max 10 calls).
	if ( ai4seo_prevent_loops( __FUNCTION__, 1, 10 ) ) {
		ai4seo_debug_message( 134874085, 'Prevented loop', true );
		return array();
	}

	// Read settings from database.
	$settings = ai4seo_get_option( AI4SEO_SETTINGS_OPTION_NAME );

	// Make sure that settings could be read from database.
	if ( ! $settings ) {
		$settings = array();
	}

	$settings = maybe_unserialize( $settings );

	// Make sure that $settings is array.
	if ( ! is_array( $settings ) ) {
		if ( is_string( $settings ) && ai4seo_is_json( $settings ) ) {
			$settings = json_decode( $settings, true );
		}
	}

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings = ai4seo_deep_sanitize( $settings );

	return $settings;
}

// =========================================================================================== \\

/**
 * Things to do on plugin activation
 *
 * @return void
 */
function ai4seo_on_activation() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// set AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME.
	if ( ! ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME ) ) {
		ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME, time() );
	}

	// init cron jobs.
	ai4seo_init_cron_jobs();
}

// =========================================================================================== \\

/**
 * Things to do on plugin deactivation
 *
 * @return void
 */
function ai4seo_on_deactivation() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// un schedule all cron jobs.
	ai4seo_un_schedule_cron_jobs();

	ai4seo_robhub_api()->perform_product_deactivated_call();

	// Check for function get_current_user_id().
	if ( ! function_exists( 'get_current_user_id' ) ) {
		return;
	}

	// Define variables for the incognito-setting.
	$ai4seo_setting_enable_incognito_mode  = ai4seo_is_incognito_mode_enabled();
	$ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID );
	$current_user_id                       = get_current_user_id();

	// Delete plugin if it was deactivated by non-incognito mode user.
	if ( $ai4seo_setting_enable_incognito_mode && AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] != $ai4seo_setting_incognito_mode_user_id && $ai4seo_setting_incognito_mode_user_id != $current_user_id ) {
		// Make sure we can call delete_plugins().
		if ( ! function_exists( 'delete_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Attempt to delete this plugin's files.
		if ( function_exists( 'delete_plugins' ) ) {
			delete_plugins( array( plugin_basename( AI4SEO_PLUGIN_FILE ) ) );
		}
	}
}

// =========================================================================================== \\

/**
 * Function to check if we have updated recently and do some actions accordingly
 *
 * @return void
 */
function ai4seo_check_and_handle_plugin_update() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$last_known_plugin_version = strval( ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION ) );

	// same plugin version as last known version? -> skip.
	if ( AI4SEO_PLUGIN_VERSION_NUMBER === $last_known_plugin_version ) {
		return;
	}

	// save new version to database.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION, AI4SEO_PLUGIN_VERSION_NUMBER );

	// workaround for version 0.0.0 -> remove $last_known_plugin_version.
	if ( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION ] === $last_known_plugin_version ) {
		$last_known_plugin_version = '';
	}

	// tidy up some old version parameters, tables and options.
	ai4seo_tidy_up( $last_known_plugin_version );

	// call "product-updated" endpoint to RobHub if we are not on a fresh install.
	if ( $last_known_plugin_version ) {
		// call robhub api endpoint "client/product-updated" with the old and new plugin version.
		$robhub_api_parameters = array(
			'old_version' => $last_known_plugin_version,
			'new_version' => AI4SEO_PLUGIN_VERSION_NUMBER,
		);

		ai4seo_robhub_api()->call( 'client/product-updated', $robhub_api_parameters );
		ai4seo_sync_robhub_account( 'product_updated' );

		// maybe push a new plugin update notification.
		ai4seo_check_for_plugin_update_notification( $last_known_plugin_version, true );
	}
}

// =========================================================================================== \\

/**
 * Restore the previous version marker when a compatibility-setting migration fails.
 *
 * This localized retry keeps the next request eligible for the failed migration without changing
 * the sequencing of established update routines.
 *
 * @param bool   $migration_succeeded      Whether the compatibility setting was persisted.
 * @param string $last_known_plugin_version Previous installed plugin version.
 * @return bool True when no retry is needed or the retry marker was stored successfully.
 */
function ai4seo_restore_plugin_update_retry_after_failed_setting_migration(
	bool $migration_succeeded,
	string $last_known_plugin_version
): bool {
	// Successful migrations and fresh installs do not need the update marker rewound.
	if ( $migration_succeeded || '' === $last_known_plugin_version ) {
		return true;
	}

	// Rewind only the version marker so the next request re-enters the normal update migration path.
	$retry_marker_stored = ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION,
		$last_known_plugin_version
	);

	// Retain operational visibility if the retry marker itself cannot be persisted.
	if ( ! $retry_marker_stored ) {
		ai4seo_debug_message( 928451620, 'Could not retain the plugin update retry marker.', true );
	}

	return $retry_marker_stored;
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Function to clean up old version's options, variables etc. "Cleanup". "Clean_up", "Tidy-up"
 *
 * @param string $last_known_plugin_version The last known plugin version, used to determine which cleanup actions to perform.
 * @return void
 */
function ai4seo_tidy_up( string $last_known_plugin_version = AI4SEO_PLUGIN_VERSION_NUMBER ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Derived cache values must not survive a plugin update because their source logic or data shape may have changed.
	ai4seo_invalidate_all_environmental_variable_caches();

	// reestablish cron jobs.
	ai4seo_un_schedule_cron_jobs();
	ai4seo_init_cron_jobs();

	// start cron jobs in 10 seconds.
	ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 10 );
	ai4seo_inject_additional_cronjob_call( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 10 );

	// unset temporary environmental variables.
	ai4seo_robhub_api()->reset_last_account_sync();
	ai4seo_robhub_api()->tidy_up_api_locks();
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL, time() - 300 );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS, array() );

	// we need the raw settings to check for old variations of the settings.
	$raw_settings = ai4seo_read_settings();

	// we need the raw environmental variables to check for old variations.
	$raw_environmental_variables = ai4seo_get_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );

	if ( $raw_environmental_variables ) {
		$raw_environmental_variables = maybe_unserialize( $raw_environmental_variables );
		$raw_environmental_variables = ai4seo_deep_sanitize( $raw_environmental_variables );
	}

	// region V1.1.X ==============================================================================.

	// remove old options (from older versions)
	// required after V1.1.1.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.1.1', '<' ) ) {
		ai4seo_delete_option( '_ai4seo_current_credits_balance' );
	}

	// if old option ai4seo_missing_seo_data_post_ids is set, rename it to ai4seo_processing_metadata_post_ids
	// required after V1.1.2.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.1.2', '<' ) ) {
		if ( ai4seo_get_option( 'ai4seo_missing_seo_data_post_ids' ) ) {
			$missing_seo_data_post_ids = ai4seo_get_option( 'ai4seo_missing_seo_data_post_ids' );
			ai4seo_update_option( 'ai4seo_processing_metadata_post_ids', $missing_seo_data_post_ids );
			ai4seo_delete_option( 'ai4seo_missing_seo_data_post_ids' );
		}

		// if old option _ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type is set, rename it to _ai4seo_num_processing_metadata_post_ids_by_post_type
		// required after V1.1.2.
		if ( ai4seo_get_option( '_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type' ) ) {
			$num_existing_going_to_fill_this_post_ids_by_post_type = ai4seo_get_option( '_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type' );
			ai4seo_update_option( '_ai4seo_num_processing_metadata_post_ids_by_post_type', $num_existing_going_to_fill_this_post_ids_by_post_type );
			ai4seo_delete_option( '_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type' );
		}

		// clear schedule of old cronjobs, as of V1.1.2 we use new cronjobs.
		wp_clear_scheduled_hook( 'ai4seo_search_missing_seo_data_posts' );
		wp_clear_scheduled_hook( 'ai4seo_search_missing_metadata_posts' );
		wp_clear_scheduled_hook( 'ai4seo_automated_seo_data_generation' );
	}

	// V1.1.8: clear schedule of old cronjob "ai4seo_automated_metadata_generation", it's now called "ai4seo_automated_generation_cron_job".
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.1.8', '<' ) ) {
		wp_clear_scheduled_hook( 'ai4seo_automated_metadata_generation' );

		ai4seo_delete_option( 'ai4seo_is_automation_activated_for_posts' );
		ai4seo_delete_option( 'ai4seo_is_automation_activated_for_pages' );
		ai4seo_delete_option( 'ai4seo_is_automation_activated_for_products' );
	}

	// region V1.2.X ==============================================================================.

	// if old option ai4seo_already_filled_post_ids is set, rename it to ai4seo_already_filled_metadata_post_ids
	// required after V1.2.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.2', '<' ) ) {
		if ( ai4seo_get_option( 'ai4seo_already_filled_post_ids' ) ) {
			$already_filled_metadata_post_ids = ai4seo_get_option( 'ai4seo_already_filled_post_ids' );
			ai4seo_update_option( 'ai4seo_already_filled_metadata_post_ids', $already_filled_metadata_post_ids );
			ai4seo_delete_option( 'ai4seo_already_filled_post_ids' );
		}

		// if old option ai4seo_failed_to_fill_post_ids is set, rename it to ai4seo_failed_to_fill_metadata_post_ids
		// required after V1.2.
		if ( ai4seo_get_option( 'ai4seo_failed_to_fill_post_ids' ) ) {
			$failed_to_fill_metadata_post_ids = ai4seo_get_option( 'ai4seo_failed_to_fill_post_ids' );
			ai4seo_update_option( 'ai4seo_failed_to_fill_metadata_post_ids', $failed_to_fill_metadata_post_ids );
			ai4seo_delete_option( 'ai4seo_failed_to_fill_post_ids' );
		}

		// V1.2: check for table "wp_ai4seo_cache" (id, post_id, data), if available, save all it's "data" to the post_meta of the corresponding post_id, using ai4seo_save_generated_data().
		ai4seo_tidy_up_old_ai4seo_cache_table();
	}

	// V1.2.1: Delete old summary options.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.2.1', '<' ) ) {
		if ( ai4seo_get_option( '_ai4seo_num_processing_metadata_post_ids_by_post_type' ) ) {
			ai4seo_delete_option( '_ai4seo_num_processing_metadata_post_ids_by_post_type' );
		}

		if ( ai4seo_get_option( '_ai4seo_num_failed_to_fill_post_ids_by_post_type' ) ) {
			ai4seo_delete_option( '_ai4seo_num_failed_to_fill_post_ids_by_post_type' );
		}

		if ( ai4seo_get_option( '_ai4seo_num_already_filled_post_ids_by_post_type' ) ) {
			ai4seo_delete_option( '_ai4seo_num_already_filled_post_ids_by_post_type' );
		}

		if ( ai4seo_get_option( '_ai4seo_num_posts_not_filled_by_post_type' ) ) {
			ai4seo_delete_option( '_ai4seo_num_posts_not_filled_by_post_type' );
		}

		if ( ai4seo_get_option( 'ai4seo_already_filled_metadata_post_ids' ) ) {
			ai4seo_delete_option( 'ai4seo_already_filled_metadata_post_ids' );
		}

		if ( ai4seo_get_option( 'ai4seo_already_filled_attributes_attachment_post_ids' ) ) {
			ai4seo_delete_option( 'ai4seo_already_filled_attributes_attachment_post_ids' );
		}

		// V1.2.1: Rename some post ids options
		// (ai4seo_failed_to_fill_metadata_post_ids -> ai4seo_failed_metadata_post_ids)
		// (ai4seo_failed_to_fill_attributes_attachment_post_ids -> ai4seo_failed_attributes_attachment_post_ids).
		if ( ai4seo_get_option( 'ai4seo_failed_to_fill_metadata_post_ids' ) ) {
			$failed_metadata_post_ids = ai4seo_get_option( 'ai4seo_failed_to_fill_metadata_post_ids' );
			ai4seo_update_option( 'ai4seo_failed_metadata_post_ids', $failed_metadata_post_ids );
			ai4seo_delete_option( 'ai4seo_failed_to_fill_metadata_post_ids' );
		}

		if ( ai4seo_get_option( 'ai4seo_failed_to_fill_attributes_attachment_post_ids' ) ) {
			$failed_attributes_attachment_post_ids = ai4seo_get_option( 'ai4seo_failed_to_fill_attributes_attachment_post_ids' );
			ai4seo_update_option( 'ai4seo_failed_attributes_attachment_post_ids', $failed_attributes_attachment_post_ids );
			ai4seo_delete_option( 'ai4seo_failed_to_fill_attributes_attachment_post_ids' );
		}
	}

	// V1.2.6: Save various options into the new environmental variables option.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '1.2.6', '<' ) ) {
		if ( ai4seo_get_option( '_ai4seo_robhub_last_credit_balance_check' ) !== false ) {
			ai4seo_delete_option( '_ai4seo_robhub_last_credit_balance_check' );
		}

		if ( ai4seo_get_option( 'ai4seo_robhub_auth_data' ) !== false ) {
			$old_robhub_auth_data = ai4seo_get_option( 'ai4seo_robhub_auth_data' );
			$old_api_username     = sanitize_text_field( $old_robhub_auth_data[0] ?? '' );
			$old_api_password     = sanitize_text_field( $old_robhub_auth_data[1] ?? '' );
			ai4seo_robhub_api()->update_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME, $old_api_username );
			ai4seo_robhub_api()->update_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $old_api_password );
			ai4seo_delete_option( 'ai4seo_robhub_auth_data' );
		}

		if ( ai4seo_get_option( '_ai4seo_robhub_credits_balance' ) !== false ) {
			ai4seo_robhub_api()->update_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE, (int) ai4seo_get_option( '_ai4seo_robhub_credits_balance' ) );
			ai4seo_delete_option( '_ai4seo_robhub_credits_balance' );
		}

		if ( ai4seo_get_option( '_ai4seo_version' ) !== false ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION, ai4seo_get_option( '_ai4seo_version' ) );
			ai4seo_delete_option( '_ai4seo_version' );
		}

		if ( ai4seo_get_option( '_ai4seo_licence_key_shown' ) !== false ) {
			// ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LICENSE_KEY_SHOWN, (bool) ai4seo_get_option("_ai4seo_licence_key_shown"));.
			ai4seo_delete_option( '_ai4seo_licence_key_shown' );
		}

		if ( ai4seo_get_option( '_ai4seo_last_cronjob_call' ) !== false ) {
			// we defined the new variable earlier in this function, therefore we can safely delete the old one.
			ai4seo_delete_option( '_ai4seo_last_cronjob_call' );
		}

		if ( ai4seo_get_option( '_ai4seo_last_cronjob_call_for_ai4seo_automated_generation_cron_job' ) !== false ) {
			// we defined the new variable earlier in this function, therefore we can safely delete the old one.
			ai4seo_delete_option( '_ai4seo_last_cronjob_call_for_ai4seo_automated_generation_cron_job' );
		}

		if ( ai4seo_get_option( '_ai4seo_last_cronjob_call_for_ai4seo_automated_metadata_generation' ) !== false ) {
			// we defined the new variable earlier in this function, therefore we can safely delete the old one.
			ai4seo_delete_option( '_ai4seo_last_cronjob_call_for_ai4seo_automated_metadata_generation' );
		}

		if ( ai4seo_get_option( '_ai4seo_performance_notice_dismissed_timestamp' ) !== false ) {
			// we defined the new variable earlier in this function, therefore we can safely delete the old one.
			ai4seo_delete_option( '_ai4seo_performance_notice_dismissed_timestamp' );
		}
	}

	// region V2.0.X ==============================================================================.

	// V2.0.0:
	// robhub auth data changed from environmental variable "auth_data" to "api_username" and "api_password".
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.0.0', '<' ) ) {
		ai4seo_robhub_api()->tidy_up_deprecated_auth_data();

		// V2.0.0: Settings migration
		// enabled_automated_generations -> enabled_bulk_generation_post_types.
		if ( isset( $raw_settings['enabled_automated_generations'] ) && is_array( $raw_settings['enabled_automated_generations'] ) ) {
			$new_enabled_bulk_generation_post_types = array();

			foreach ( $raw_settings['enabled_automated_generations'] as $this_post_type => $this_is_enabled ) {
				if ( $this_is_enabled ) {
					$new_enabled_bulk_generation_post_types[] = $this_post_type;
				}
			}

			if ( $new_enabled_bulk_generation_post_types ) {
				ai4seo_update_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $new_enabled_bulk_generation_post_types );
			}
		}

		// automated_generation_order (array) -> bulk_generation_order (string).
		if ( isset( $raw_settings['automated_generation_order'] ) && is_array( $raw_settings['automated_generation_order'] ) ) {
			// get first element of the array, set it as the new value.
			if ( count( $raw_settings['automated_generation_order'] ) >= 1 ) {
				$new_bulk_generation_order = reset( $raw_settings['automated_generation_order'] );

				if ( $new_bulk_generation_order ) {
					ai4seo_update_setting( AI4SEO_SETTING_BULK_GENERATION_ORDER, $new_bulk_generation_order );
				}
			}
		}

		// automated_generation_new_or_existing_filter (array) -> bulk_generation_new_or_existing_filter (string).
		if ( isset( $raw_settings['automated_generation_new_or_existing_filter'] ) && is_array( $raw_settings['automated_generation_new_or_existing_filter'] ) ) {
			// get first element of the array, set it as the new value.
			if ( count( $raw_settings['automated_generation_new_or_existing_filter'] ) >= 1 ) {
				$new_bulk_generation_new_or_existing_filter = reset( $raw_settings['automated_generation_new_or_existing_filter'] );

				if ( $new_bulk_generation_new_or_existing_filter ) {
					ai4seo_update_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER, $new_bulk_generation_new_or_existing_filter );
				}
			}
		}

		// automated_generation_new_or_existing_filter_reference_times (array) -> bulk_generation_new_or_existing_filter_reference_time (string)
		// environmental variable.
		if ( isset( $raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times'] ) && is_array( $raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times'] ) ) {
			// get first element of the array, set it as the new value.
			if ( count( $raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times'] ) >= 1 ) {
				$new_bulk_generation_new_or_existing_filter_reference_time = reset( $raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times'] );

				if ( $new_bulk_generation_new_or_existing_filter_reference_time ) {
					ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME, $new_bulk_generation_new_or_existing_filter_reference_time );
				}
			}
		}
	}

	// region 2.1.X ==============================================================================.

	// V2.1.0:.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.1.0', '<' ) ) {
		// Remove old environmental variable "performance_notice_dismissed_time" and dismissed_one_time_notices, feature was removed in V2.1.0.
		ai4seo_delete_environmental_variable( 'performance_notice_dismissed_time' );
		ai4seo_delete_environmental_variable( 'dismissed_one_time_notices' );
		ai4seo_delete_environmental_variable( 'is_first_purchase_discount_available' );
		ai4seo_delete_environmental_variable( 'early_bird_discount_time_left' );

		// delete option "_ai4seo_plugin_activation_time" -> deprecated in V2.1.0.
		if ( ai4seo_get_option( '_ai4seo_plugin_activation_time' ) !== false ) {
			ai4seo_delete_option( '_ai4seo_plugin_activation_time' );
		}
	}

	// V2.1.1:.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.1.1', '<' ) ) {
		// delete old robhub environmental variable last_credit_balance_check.
		ai4seo_robhub_api()->delete_environmental_variable( 'last_credit_balance_check' );
	}

	// region 2.2.X ==============================================================================.

	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.2.0', '<' ) ) {
		// handle default visible meta tags setting change to new active meta tags setting.
		if ( ! isset( $raw_settings[ AI4SEO_SETTING_VISIBLE_META_TAGS ] ) || ! $raw_settings[ AI4SEO_SETTING_VISIBLE_META_TAGS ] ) {
			// old default value for visible meta tags.
			ai4seo_update_setting( AI4SEO_SETTING_ACTIVE_META_TAGS, array( 'meta-title', 'meta-description', 'facebook-title', 'facebook-description' ) );
		}

		// if visible meta tags were set, apply the same to active meta tags.
		if ( isset( $raw_settings[ AI4SEO_SETTING_VISIBLE_META_TAGS ] ) && $raw_settings[ AI4SEO_SETTING_VISIBLE_META_TAGS ] ) {
			ai4seo_update_setting( AI4SEO_SETTING_ACTIVE_META_TAGS, $raw_settings[ AI4SEO_SETTING_VISIBLE_META_TAGS ] );
		}

		// set AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE to 'facebook-title' and set AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION to 'facebook-description'.
		ai4seo_update_setting( AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE, 'facebook-title' );
		ai4seo_update_setting( AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION, 'facebook-description' );
	}

	// region 2.3.X ==============================================================================.

	// V2.3.5: migrate legacy active metadata postmeta keys to one JSON postmeta entry per post.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.3.5', '<' ) ) {
		// Preserve pre-2.3.5 external button behavior for existing clients.
		ai4seo_update_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS, true );
		ai4seo_update_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS, true );

		if ( ! empty( $raw_settings[ AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ] )
			&& ! ai4seo_is_deep_context_search_supported_for_current_site() ) {
			ai4seo_disable_deep_context_search_for_images();
		}

		ai4seo_start_active_metadata_migration_v235();
	}

	// region 2.4.X ==============================================================================.

	// V2.4.4: Existing users keep their former third-party SEO sync behavior unless they chose a selection explicitly.
	if ( ai4seo_should_migrate_default_third_party_seo_plugin_sync( $last_known_plugin_version, $raw_settings ) ) {
		$third_party_seo_sync_migration_succeeded = ai4seo_update_setting(
			AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS,
			AI4SEO_LEGACY_DEFAULT_THIRD_PARTY_SEO_PLUGIN_SYNC_IDENTIFIERS
		);

		// A failed compatibility write must remain eligible for the next update check.
		ai4seo_restore_plugin_update_retry_after_failed_setting_migration(
			$third_party_seo_sync_migration_succeeded,
			$last_known_plugin_version
		);
	}

	// V2.4.4: Existing users keep the field-first editor experience unless they already chose a mode explicitly.
	if ( ai4seo_should_migrate_default_editor_view_mode( $last_known_plugin_version, $raw_settings ) ) {
		ai4seo_update_setting( AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE, AI4SEO_EDITOR_VIEW_MODE_EDITOR );
	}

	// V2.4.0: Initialize prompt slider settings for upgraded sites with current-behavior defaults.
	if ( $last_known_plugin_version && version_compare( $last_known_plugin_version, '2.4.0', '<' ) ) {
		$prompt_slider_setting_migration_values = ai4seo_get_prompt_slider_setting_pre_240_migration_values( $raw_settings );
		ai4seo_bulk_update_settings( $prompt_slider_setting_migration_values );
	}

	// to finish the tidy up, we re-analyze the plugin performance and by adding notifications.
	ai4seo_analyze_plugin_performance();

	// force push various notifications, if applicable.
	ai4seo_check_for_missing_entries_notification( true );
	ai4seo_check_for_low_credits_balance_notification( true );

	// refresh unread notifications count.
	ai4seo_refresh_unread_notifications_count();

	// Reload the current SOOZ page after all update work has completed so its assets receive a fresh request-only version.
	if (
		$last_known_plugin_version
		&& AI4SEO_PLUGIN_VERSION_NUMBER !== $last_known_plugin_version
		&& ai4seo_is_user_inside_our_plugin_admin_pages()
	) {
		add_action( 'admin_init', 'ai4seo_refresh_plugin_page_assets_after_update', 0 );
	}
}

// =========================================================================================== \\

/**
 * Redirects an updated SOOZ admin page once so its assets bypass browser and optimizer caches.
 *
 * @return void
 */
function ai4seo_refresh_plugin_page_assets_after_update() {
	// Tidy-up can run through more than one bootstrap path, but the page must redirect only once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Reuse the current-request URL helper so the active SOOZ subpage and its state are preserved.
	$current_url = ai4seo_get_current_prohibit_plugin_request_url();

	if ( '' === $current_url ) {
		return;
	}

	// A timestamp changes only this request's asset version while the normal plugin version remains stable.
	$refresh_url = ai4seo_add_query_arg( AI4SEO_ASSET_REFRESH_QUERY_PARAMETER, time(), $current_url );

	// Prevent intermediate caches from retaining the redirect response itself.
	nocache_headers();
	wp_safe_redirect( $refresh_url );
	exit;
}

// =========================================================================================== \\

/**
 * Function to tidy up old ai4seo_cache table
 *
 * @return void
 */
function ai4seo_tidy_up_old_ai4seo_cache_table() {
	global $wpdb;

	// Check if the table exists.
	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			"{$wpdb->prefix}ai4seo_cache"
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321667, 'Database error: ' . $wpdb->last_error, true );
		return;
	}

	if ( ! $table_exists ) {
		return;
	}

	// Table identifiers can't be prepared; table name is internal.
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( "SELECT post_id, data FROM `{$wpdb->prefix}ai4seo_cache`", ARRAY_A );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321668, 'Database error: ' . $wpdb->last_error, true );
		return;
	}

	foreach ( (array) $results as $result ) {
		$post_id = absint( $result['post_id'] );
		if ( ! $post_id ) {
			continue;
		}

		$decoded = json_decode( (string) $result['data'], true );
		$data    = ai4seo_deep_sanitize( is_array( $decoded ) ? $decoded : array() );

		ai4seo_save_generated_data_to_postmeta( $post_id, $data, false );
	}

	// Table identifiers can't be prepared; table name is internal.
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}ai4seo_cache`" );

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321669, 'Database error: ' . $wpdb->last_error, true );
		return;
	}
}

// =========================================================================================== \\

/**
 * Function to init cron jobs
 *
 * @return void
 */
function ai4seo_init_cron_jobs() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	try {
		if ( ! ai4seo_robhub_api( true ) || ! ai4seo_robhub_api()->is_initialized ) {
			return;
		}
	} catch ( Throwable $e ) {
		return;
	}

	if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return;
	}

	// Add custom cron schedule.
	add_filter( 'cron_schedules', 'ai4seo_add_cron_job_intervals' );

	// add cron jobs to automate content generation.
	add_action( AI4SEO_BULK_GENERATION_CRON_JOB_NAME, AI4SEO_BULK_GENERATION_CRON_JOB_NAME );

	// add cron jobs to analyze current state of the plugins performance.
	add_action( AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME );

	// add temporary cron job for the v235 active metadata migration.
	add_action( AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME, AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME );

	// schedule cron jobs if not already scheduled.
	ai4seo_schedule_cron_jobs();
}

// =========================================================================================== \\

/**
 * Adjusts the browser title for all AI for SEO plugin admin pages.
 *
 * Uses ai4seo_is_user_inside_our_plugin_admin_pages(), ai4seo_get_active_subpage(),
 * ai4seo_get_active_post_type(), and ai4seo_get_plugins_menu_registry()
 * to determine the current page label.
 *
 * @param string $admin_title Default admin title.
 * @param string $title       Original page title.
 * @return string Filtered admin title.
 */
function ai4seo_filter_admin_title( string $admin_title, string $title ): string {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return $admin_title;
	}

	$registry                 = ai4seo_get_plugins_menu_registry();
	$active_subpage           = ai4seo_get_active_subpage();
	$active_post_type_subpage = ai4seo_get_active_post_type_subpage();

	// default label.
	$active_page_label = __( 'Dashboard', 'ai-for-seo' );

	if ( ! empty( $active_subpage ) && isset( $registry[ $active_subpage ] ) ) {
		// Static subpages: settings, media, account, help.
		$active_page_label = $registry[ $active_subpage ]['label'];
	} elseif ( 'post' === $active_subpage && ! empty( $active_post_type_subpage ) && isset( $registry['post_types'][ $active_post_type_subpage ] ) ) {
		// Dynamic post-type subpages.
		$active_page_label = $registry['post_types'][ $active_post_type_subpage ]['label'];
	}

	$website_name = get_bloginfo( 'name' );

	// build everything together and sanitize.
	$browser_title = $active_page_label . ' ‹ ' . AI4SEO_PLUGIN_NAME . ' ‹ ' . $website_name;
	$browser_title = wp_strip_all_tags( $browser_title );
	$browser_title = str_replace( array( '&amp;', '&#038;' ), '&', $browser_title );
	$browser_title = str_replace( array( '&lt;', '&#060;' ), '<', $browser_title );
	$browser_title = str_replace( array( '&gt;', '&#062;' ), '>', $browser_title );
	$browser_title = str_replace( array( '&quot;', '&#034;' ), '"', $browser_title );
	$browser_title = str_replace( array( '&#039;', '&#039;' ), "'", $browser_title );

	return $browser_title;
}

// =========================================================================================== \\

/**
 * Add a scoped admin body class on AI for SEO admin screens.
 *
 * @param string $classes Admin body classes.
 * @return string Filtered admin body classes.
 */
function ai4seo_add_admin_body_class( string $classes ): string {
	// Scope moved WordPress-admin layout rules to plugin screens only.
	if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		$classes .= ' ai4seo-admin-page';
	}

	return $classes;
}

// =========================================================================================== \\

/**
 * Get the localized label for the media navigation item.
 *
 * @return string
 */
function ai4seo_get_media_menu_label(): string {
	// Use WordPress' registered attachment label so customized admin wording is reflected in AI4SEO navigation.
	$attachment_post_type = get_post_type_object( 'attachment' );

	if ( $attachment_post_type && ! empty( $attachment_post_type->labels->name ) ) {
		return $attachment_post_type->labels->name;
	}

	return _x( 'Media', 'plugin navigation menu label', 'ai-for-seo' );
}

// =========================================================================================== \\

/**
 * Build a registry of AI4SEO menu entries: labels and slugs.
 *
 * @return array{
 *   dashboard: array{label:string, slug:string},
 *   media: array{label:string, slug:string},
 *   account: array{label:string, slug:string},
 *   settings: array{label:string, slug:string},
 *   help: array{label:string, slug:string},
 *   post_types: array<string, array{label:string, slug:string}>
 * }
 */
function ai4seo_get_plugins_menu_registry(): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 925974828, 'Prevented loop', true );
		return array();
	}

	// Static pages.
	$dashboard_slug = str_replace( '?page=', '', ai4seo_get_subpage_url( 'dashboard', array(), false ) ); // typically 'ai-for-seo'.
	$settings_slug  = str_replace( '?page=', '', ai4seo_get_subpage_url( 'settings', array(), false ) );  // e.g. 'ai-for-seo&ai4seo_subpage=settings'.
	$media_slug     = str_replace( '?page=', '', ai4seo_get_subpage_url( 'media', array(), false ) );     // e.g. 'ai-for-seo&ai4seo_subpage=media'.
	$account_slug   = str_replace( '?page=', '', ai4seo_get_subpage_url( 'account', array(), false ) );   // e.g. 'ai-for-seo&ai4seo_subpage=account'.
	$help_slug      = str_replace( '?page=', '', ai4seo_get_subpage_url( 'help', array(), false ) );      // e.g. 'ai-for-seo&ai4seo_subpage=help'.

	// Dynamic post-type pages: use a stable slug (no pagination or other volatile args).
	$post_types    = ai4seo_get_supported_post_types();
	$post_type_map = array();

	foreach ( $post_types as $this_post_type ) {
		$this_post_type = sanitize_key( $this_post_type );
		$label          = ai4seo_get_nice_label( ai4seo_get_post_type_translation( $this_post_type, true ) );
		$slug           = AI4SEO_PLUGIN_IDENTIFIER . '&ai4seo_subpage=post&ai4seo_post_type=' . $this_post_type;

		$post_type_map[ $this_post_type ] = array(
			'label' => $label,
			'slug'  => $slug,
		);
	}

	return array(
		'dashboard'  => array(
			'label' => __( 'Dashboard', 'ai-for-seo' ),
			'slug'  => $dashboard_slug,
		),
		'media'      => array(
			'label' => ai4seo_get_media_menu_label(),
			'slug'  => $media_slug,
		),
		'account'    => array(
			'label' => __( 'Account', 'ai-for-seo' ),
			'slug'  => $account_slug,
		),
		'settings'   => array(
			'label' => __( 'Settings', 'ai-for-seo' ),
			'slug'  => $settings_slug,
		),
		'help'       => array(
			'label' => __( 'Help', 'ai-for-seo' ),
			'slug'  => $help_slug,
		),
		'post_types' => $post_type_map,
	);
}

// =========================================================================================== \\

/**
 * Register AI4SEO menu and submenus.
 *
 * @return void
 */
function ai4seo_add_menu_entries() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$svg_tags = ai4seo_get_svg_tags();

	if ( ! isset( $svg_tags['ai-for-seo-main-menu-icon'] ) ) {
		return;
	}

	$encoded_svg = 'data:image/svg+xml;base64,' . base64_encode( $svg_tags['ai-for-seo-main-menu-icon'] );

	// Top-level title with notification bubble.
	$menu_title         = AI4SEO_PLUGIN_NAME;
	$notification_count = ai4seo_get_num_unread_notification();

	if ( $notification_count > 0 ) {
		// The badge class keeps the WordPress menu count aligned while the plugin menu label remains long.
		$menu_title .= " <span class='update-plugins ai4seo-menu-notification-count count-{$notification_count}'><span class='plugin-count'>{$notification_count}</span></span>";
	}

	// Central registry for labels and slugs.
	$plugins_menu_registries = ai4seo_get_plugins_menu_registry();

	// Collect the hook suffixes returned by WordPress so page-specific notice suppression covers every SOOZ screen.
	$ai4seo_menu_hook_suffixes = array();

	// Top-level.
	$ai4seo_menu_hook_suffixes[] = add_menu_page(
		AI4SEO_PLUGIN_NAME,
		$menu_title,                 // Contains markup for bubble. Keep as-is.
		'edit_posts',
		AI4SEO_PLUGIN_IDENTIFIER,
		'ai4seo_include_menu_frame_file',
		$encoded_svg,
		99
	);

	// Dashboard (main page uses parent slug as submenu slug).
	$ai4seo_menu_hook_suffixes[] = add_submenu_page(
		AI4SEO_PLUGIN_IDENTIFIER,
		$plugins_menu_registries['dashboard']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
		$plugins_menu_registries['dashboard']['label'],
		'edit_posts',
		AI4SEO_PLUGIN_IDENTIFIER,
		'ai4seo_include_menu_frame_file'
	);

	// Dynamic post-type submenus.
	foreach ( $plugins_menu_registries['post_types'] as $this_post_type ) {
		$ai4seo_menu_hook_suffixes[] = add_submenu_page(
			AI4SEO_PLUGIN_IDENTIFIER,
			$this_post_type['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
			$this_post_type['label'],
			'edit_posts',
			$this_post_type['slug'],
			'ai4seo_include_menu_frame_file'
		);
	}

	// Media.
	$ai4seo_menu_hook_suffixes[] = add_submenu_page(
		AI4SEO_PLUGIN_IDENTIFIER,
		$plugins_menu_registries['media']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
		$plugins_menu_registries['media']['label'],
		'edit_posts',
		$plugins_menu_registries['media']['slug'],
		'ai4seo_include_menu_frame_file'
	);

	// Account.
	$ai4seo_menu_hook_suffixes[] = add_submenu_page(
		AI4SEO_PLUGIN_IDENTIFIER,
		$plugins_menu_registries['account']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
		$plugins_menu_registries['account']['label'],
		'edit_posts',
		$plugins_menu_registries['account']['slug'],
		'ai4seo_include_menu_frame_file'
	);

	// Settings.
	$ai4seo_menu_hook_suffixes[] = add_submenu_page(
		AI4SEO_PLUGIN_IDENTIFIER,
		$plugins_menu_registries['settings']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
		$plugins_menu_registries['settings']['label'],
		'edit_posts',
		$plugins_menu_registries['settings']['slug'],
		'ai4seo_include_menu_frame_file'
	);

	// Help.
	$ai4seo_menu_hook_suffixes[] = add_submenu_page(
		AI4SEO_PLUGIN_IDENTIFIER,
		$plugins_menu_registries['help']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
		$plugins_menu_registries['help']['label'],
		'edit_posts',
		$plugins_menu_registries['help']['slug'],
		'ai4seo_include_menu_frame_file'
	);

	// Register suppression after all menu entries are known so dynamic post-type pages follow the same path.
	foreach ( $ai4seo_menu_hook_suffixes as $this_hook_suffix ) {
		ai4seo_register_plugin_admin_notice_suppression( (string) $this_hook_suffix );
	}
}

// =========================================================================================== \\

/**
 * Mark our top-level menu as current when any AI for SEO page is open.
 *
 * @param string|null $parent_file
 * @return string|null The slug of the current top-level menu (if any).
 */
function ai4seo_mark_parent_menu_active( ?string $parent_file ): ?string {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return '';
	}

	if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		$parent_file = AI4SEO_PLUGIN_IDENTIFIER;
	}

	return $parent_file;
}

// =========================================================================================== \\

/**
 * Mark the correct submenu entry as current.
 *
 * @param string|null $submenu_file
 * @return string|null The slug of the current submenu entry (if any).
 */
function ai4seo_mark_submenu_active( ?string $submenu_file ): ?string {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return '';
	}

	if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		// Central registry for labels and slugs.
		$plugins_menu_registries = ai4seo_get_plugins_menu_registry();

		if ( $active_post_type = ai4seo_get_active_post_type_subpage() ) {
			$submenu_file = $plugins_menu_registries['post_types'][ $active_post_type ]['slug'] ?? $submenu_file;
		} elseif ( $active_subpage = ai4seo_get_active_subpage() ) {
			$submenu_file = $plugins_menu_registries[ $active_subpage ]['slug'] ?? $submenu_file;
		} else {
			$submenu_file = AI4SEO_PLUGIN_IDENTIFIER;
		}
	}

	return $submenu_file;
}

// =========================================================================================== \\

/**
 * Function to display the menu frame
 *
 * @return void
 */
function ai4seo_include_menu_frame_file() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	include_once ai4seo_get_plugin_dir_path( 'includes/menu-frame.php' );
}

// =========================================================================================== \\

/**
 * Function to add modal schemas to the footer
 *
 * @return void
 */
function ai4seo_include_modal_schemas_file() {
	// Modal schemas accompany the primary bundle, so unrelated requests skip even the schema autoloader.
	if ( ! ai4seo_get_primary_asset_contexts() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	include_once ai4seo_get_includes_modal_schemas_path( 'autoload-modal-schemas.php' );
}

// =========================================================================================== \\

function ai4seo_enqueue_frontend_scripts() {
	global $ai4seo_scripts_version_number;

	// prevent multiple calls of this function.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// check if we are outside the admin area.
	if ( is_admin() ) {
		return;
	}

	// Enqueue ai-for-seo-alt-text-injection.
	$is_render_level_alt_text_enabled = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION );
	$is_js_alt_text_enabled           = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION );

	if ( $is_render_level_alt_text_enabled && $is_js_alt_text_enabled ) {
		wp_enqueue_script( AI4SEO_INJECTION_SCRIPTS_HANDLE, ai4seo_get_assets_js_path( AI4SEO_INJECTION_SCRIPTS_FILE_NAME ), array( 'jquery' ), $ai4seo_scripts_version_number, true );
	}
}

// =========================================================================================== \\

/**
 * Keeps JavaScript translation JSON lookup stable while the physical script filename changes per release.
 *
 * @param string|false $relative_path Script path relative to the plugin directory.
 * @return string|false Translation source path.
 */
function ai4seo_use_stable_script_translation_source_path( $relative_path ) {
	if ( 'assets/js/' . AI4SEO_SCRIPTS_FILE_NAME === $relative_path ) {
		return 'assets/js/ai-for-seo-scripts.js';
	}

	return $relative_path;
}

// =========================================================================================== \\

/**
 * Returns the one-time asset version only for authorized SOOZ admin page requests.
 *
 * Keeping this query parameter out of public requests prevents arbitrary frontend cache variants
 * while preserving the post-update refresh for the plugin page that issued it.
 *
 * @return int
 */
function ai4seo_get_plugin_admin_asset_refresh_version(): int {
	// The refresh value is meaningful only where the plugin's primary admin assets are enqueued.
	if ( ! is_admin()
		|| ! ai4seo_can_manage_this_plugin()
		|| ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return 0;
	}

	// Update redirects generate a positive timestamp; all other values retain normal plugin versioning.
	return isset( $_GET[ AI4SEO_ASSET_REFRESH_QUERY_PARAMETER ] )
		? absint( wp_unslash( $_GET[ AI4SEO_ASSET_REFRESH_QUERY_PARAMETER ] ) )
		: 0;
}

// =========================================================================================== \\

/**
 * Adds the optimizer opt-out attribute shared by the main admin script and stylesheet.
 *
 * The main script contains a localized nonce, and the stylesheet must be refreshed alongside it
 * after plugin updates. LiteSpeed and Autoptimize both honor the same data attribute.
 *
 * @param string $tag Asset tag generated by WordPress.
 * @param string $handle Registered script or style handle.
 * @return string Filtered asset tag.
 */
function ai4seo_add_no_optimize_attribute_to_admin_asset_tag( $tag, $handle ) {
	// Ignore unrelated assets so their optimizer behavior remains under the owning plugin's control.
	if ( ! in_array( $handle, array( AI4SEO_SCRIPTS_HANDLE, AI4SEO_STYLES_HANDLE ), true ) ) {
		return $tag;
	}

	// Select the opening tag from the registered handle so scripts and styles share one insertion path.
	$tag_opening = AI4SEO_SCRIPTS_HANDLE === $handle ? '<script ' : '<link ';

	return str_replace( $tag_opening, $tag_opening . 'data-no-optimize="1" ', $tag );
}

// =========================================================================================== \\

/**
 * Returns the primary asset contexts needed by the current request.
 *
 * @param string $hook_suffix Current WordPress admin page hook suffix.
 * @return array Valid primary asset context identifiers.
 */
function ai4seo_get_primary_asset_contexts( string $hook_suffix = '' ): array {
	// The resolver repeats the plugin-wide role gate because the public filter must never opt unauthorized users in.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return array();
	}

	// Reuse one authoritative result across enqueue and footer callbacks in the same request.
	static $cached_asset_contexts = array();

	// Capture the request surface once because it participates in cache and classification decisions.
	$is_admin_request = is_admin();

	// Footer callers receive no argument, so recover the admin hook suffix recorded by WordPress.
	if ( $is_admin_request && '' === $hook_suffix && isset( $GLOBALS['hook_suffix'] ) && is_string( $GLOBALS['hook_suffix'] ) ) {
		$hook_suffix = $GLOBALS['hook_suffix'];
	}

	// Separate otherwise identical frontend and admin hook suffixes in the request cache.
	$hook_suffix             = sanitize_text_field( $hook_suffix );
	$asset_context_cache_key = ( $is_admin_request ? 'admin:' : 'frontend:' ) . $hook_suffix;

	// Return the first resolved result so later footer work cannot drift from the enqueued bundle.
	if ( isset( $cached_asset_contexts[ $asset_context_cache_key ] ) ) {
		return $cached_asset_contexts[ $asset_context_cache_key ];
	}

	$asset_contexts = array();

	// Classify WordPress admin screens separately from the intentionally narrow frontend integration.
	if ( $is_admin_request ) {
		// Read the current screen once so all admin classifications use the same request state.
		$screen      = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_base = ( $screen && isset( $screen->base ) ) ? sanitize_key( $screen->base ) : '';
		$screen_id   = ( $screen && isset( $screen->id ) ) ? sanitize_key( $screen->id ) : '';
		$post_type   = ( $screen && isset( $screen->post_type ) ) ? sanitize_key( $screen->post_type ) : '';

		// Every internal SOOZ page uses the complete plugin interface group.
		if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
			$asset_contexts[] = 'plugin-ui';
		}

		// TOS assets must follow the exact same weekly request decision as the footer output.
		if ( ai4seo_should_show_terms_of_service_modal_on_current_request() ) {
			$asset_contexts[] = 'tos-gate';
		}

		$does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();

		// Mandatory TOS acceptance suppresses every normal integration until the legal gate is cleared.
		if ( ! $does_user_need_to_accept_tos_toc_and_pp ) {
			// The Installed Plugins screen alone needs the deactivation feedback integration.
			if ( 'plugins.php' === $hook_suffix || 'plugins' === $screen_base ) {
				$asset_contexts[] = 'plugin-deactivation';
			}

			// Native content lists opt in through either the built-in metadata column or a registered bulk action.
			if ( 'edit' === $screen_base && $post_type ) {
				$has_metadata_editor_column = in_array( $post_type, array( 'post', 'page' ), true );
				$has_native_bulk_actions    = has_filter( "bulk_actions-edit-{$post_type}", 'ai4seo_add_native_bulk_generation_queue_bulk_actions' );

				if ( $has_metadata_editor_column || $has_native_bulk_actions ) {
					$asset_contexts[] = 'content-list';
				}
			}

			// Classify metadata editors from the registered post type and settings without populating content caches.
			$is_post_edit_screen       = ai4seo_is_post_edit_screen();
			$is_attachment_edit_screen = $is_post_edit_screen && 'attachment' === $post_type;

			if ( $is_post_edit_screen && ! $is_attachment_edit_screen && $post_type ) {
				$publicly_accessible_post_types = ai4seo_get_publicly_accessible_post_types();
				$disabled_post_types             = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_POST_TYPES );

				if ( ! is_array( $disabled_post_types ) ) {
					$disabled_post_types = array();
				} else {
					$disabled_post_types = ai4seo_deep_sanitize( $disabled_post_types, 'sanitize_key' );
				}

				if (
					isset( $publicly_accessible_post_types[ $post_type ] )
					&& ai4seo_get_active_meta_tags()
					&& ! in_array( $post_type, $disabled_post_types, true )
				) {
					$asset_contexts[] = 'post-editor';
				}
			}

			// External media controls are independent from metadata support and include the core Site Editor.
			$are_external_media_generate_buttons_enabled = true === ai4seo_get_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS );
			$is_media_library_screen                       = (
				in_array( $hook_suffix, array( 'upload.php', 'media-new.php' ), true )
				|| in_array( $screen_base, array( 'upload', 'media' ), true )
				|| in_array( $screen_id, array( 'upload', 'media' ), true )
			);
			$is_site_editor_screen                         = (
				'site-editor.php' === $hook_suffix
				|| 'site-editor' === $screen_base
				|| 'site-editor' === $screen_id
			);
			$is_post_editor_media_screen                   = $is_post_edit_screen && ! $is_attachment_edit_screen;
			$should_enqueue_external_media_assets          = (
				$are_external_media_generate_buttons_enabled
				&& (
					$is_media_library_screen
					|| $is_attachment_edit_screen
					|| $is_post_editor_media_screen
					|| $is_site_editor_screen
				)
			);

			if ( $should_enqueue_external_media_assets ) {
				$asset_contexts[] = 'external-media';
			}

			// Media list bulk actions need the common content-list confirmation handlers as a second context.
			if ( 'upload' === $screen_base && has_filter( 'bulk_actions-upload', 'ai4seo_add_native_bulk_generation_queue_bulk_actions' ) ) {
				$asset_contexts[] = 'content-list';
			}
		}
	} else {
		// Frontend assets serve only singular admin-bar editors and enabled page-builder integrations.
		if (
			! ai4seo_does_user_need_to_accept_tos_toc_and_pp()
			&& is_singular()
			&& (
				is_admin_bar_showing()
				|| true === ai4seo_get_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS )
			)
		) {
			$asset_contexts[] = 'frontend-metadata-editor';
		}
	}

	// Deduplicate core results before integrations receive their opt-in extension point.
	$core_asset_contexts = array_values( array_unique( $asset_contexts ) );

	/**
	 * Filters the primary asset contexts needed by the current request.
	 *
	 * Core contexts are mandatory; integrations may return them with additional known contexts.
	 *
	 * @param array  $core_asset_contexts Core context identifiers detected for the request.
	 * @param string $hook_suffix         Current WordPress admin page hook suffix.
	 */
	$filtered_asset_contexts = apply_filters( 'ai4seo_primary_asset_contexts', $core_asset_contexts, $hook_suffix );

	// Invalid filter return types fall back to core behavior instead of disabling required assets.
	if ( ! is_array( $filtered_asset_contexts ) ) {
		$filtered_asset_contexts = $core_asset_contexts;
	}

	// Keep only unique, known context identifiers before combining them with mandatory core contexts.
	$filtered_asset_contexts = array_filter( $filtered_asset_contexts, 'is_string' );
	$filtered_asset_contexts = array_map( 'sanitize_key', $filtered_asset_contexts );
	$filtered_asset_contexts = array_values(
		array_unique(
			array_intersect(
				AI4SEO_PRIMARY_ASSET_CONTEXTS,
				array_merge( $core_asset_contexts, $filtered_asset_contexts )
			)
		)
	);

	// Persist the validated list for every remaining consumer in this request.
	$cached_asset_contexts[ $asset_context_cache_key ] = $filtered_asset_contexts;

	return $cached_asset_contexts[ $asset_context_cache_key ];
}

// =========================================================================================== \\

/**
 * Enqueues the shared primary JavaScript and CSS bundle when the current request needs it.
 *
 * @param string $hook_suffix Current WordPress admin page hook suffix.
 * @return void
 */
function ai4seo_enqueue_primary_assets( string $hook_suffix = '' ) {
	global $ai4seo_scripts_version_number;

	// Resolve contexts before singleton or asset work so unrelated requests remain effectively no-op.
	$asset_contexts = ai4seo_get_primary_asset_contexts( $hook_suffix );

	if ( ! $asset_contexts ) {
		return;
	}

	// Prevent multiple calls of this function.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Apply the update refresh only after the current request has passed the plugin-admin capability and page checks.
	$ai4seo_asset_refresh_version = ai4seo_get_plugin_admin_asset_refresh_version();

	if ( $ai4seo_asset_refresh_version ) {
		$ai4seo_scripts_version_number = $ai4seo_asset_refresh_version;
	}

	wp_enqueue_script( 'wp-i18n' );

	// Register and enqueue stylesheet.
	wp_register_style( AI4SEO_STYLES_HANDLE, ai4seo_get_assets_css_path( AI4SEO_STYLES_FILE_NAME ), '', $ai4seo_scripts_version_number );
	wp_enqueue_style( AI4SEO_STYLES_HANDLE );

	// Enqueue javascript-file.
	wp_enqueue_script( AI4SEO_SCRIPTS_HANDLE, ai4seo_get_assets_js_path( AI4SEO_SCRIPTS_FILE_NAME ), array( 'jquery', 'wp-i18n' ), $ai4seo_scripts_version_number, true );

	// Translation JSON files retain the stable logical source path across versioned script filenames.
	add_filter(
		'load_script_textdomain_relative_path',
		'ai4seo_use_stable_script_translation_source_path',
		10,
		1
	);

	// Set localization parameters.
	ai4seo_set_localization_parameters( $asset_contexts );

	// Route both primary admin assets through the shared optimizer opt-out mechanism.
	add_filter(
		'script_loader_tag',
		'ai4seo_add_no_optimize_attribute_to_admin_asset_tag',
		10,
		2
	);

	add_filter(
		'style_loader_tag',
		'ai4seo_add_no_optimize_attribute_to_admin_asset_tag',
		10,
		2
	);
}

// =========================================================================================== \\

/**
 * Function to set localization parameters
 *
 * @param array $asset_contexts Primary asset contexts active for the current request.
 * @return void
 */
function ai4seo_set_localization_parameters( array $asset_contexts ) {
	global $ai4seo_scripts_version_number;
	global $wp_locale;

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// region INITIALISATIONS ======================================================.

	$current_post_id                           = ai4seo_get_current_post_id();
	$ajax_nonce                                = wp_create_nonce( AI4SEO_GLOBAL_NONCE_IDENTIFIER );
	$site_url                                  = site_url();
	$admin_url                                 = admin_url();
	$admin_ajax_url                            = admin_url( 'admin-ajax.php' );
	$includes_url                              = includes_url();
	$content_url                               = content_url();
	$plugin_url                                = plugins_url();
	$plugin_directory_url                      = plugins_url( '', AI4SEO_PLUGIN_FILE );
	$uploads_directory_url                     = wp_upload_dir();
	$assets_directory_url                      = ai4seo_get_plugins_url( 'assets' );
	$does_user_need_to_accept_tos_toc_and_pp   = ai4seo_does_user_need_to_accept_tos_toc_and_pp();
	$active_subpage                            = ai4seo_get_active_subpage();
	$active_post_type_subpage                  = ai4seo_get_active_post_type_subpage();
	$active_meta_tags                          = ai4seo_get_active_meta_tags();
	$active_attachment_attributes              = ai4seo_get_active_attachment_attributes();
	$enable_external_metadata_generate_buttons = (bool) ai4seo_get_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS );
	$enable_external_media_generate_buttons    = (bool) ai4seo_get_setting( AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS );
	$metadata_price_table              = ai4seo_get_metadata_price_table();
	$attachment_attributes_price_table = ai4seo_get_attachment_attributes_price_table();
	// Provide the server-selected instruction cap to dynamically rendered settings and editor forms.
	$custom_instructions_length_limit = ai4seo_get_custom_instructions_length_limit();
	$seopress_generation_metadata                     = array();
	$active_generation_editor_integration_identifiers = array();
	$is_post_editor_asset_context                      = in_array( 'post-editor', $asset_contexts, true );

	// Reactive third-party editor discovery is relevant only on WordPress post editors.
	if ( $enable_external_metadata_generate_buttons && $is_post_editor_asset_context ) {
		$active_generation_editor_integration_identifiers = ai4seo_get_active_generation_editor_integration_identifiers();
	}

	// Seed fields only when the active SEOPress editor can render SOOZ generation controls.
	if ( $current_post_id
		&& $enable_external_metadata_generate_buttons
		&& ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS ) ) {
		$seopress_generation_metadata = ai4seo_get_seopress_generation_metadata_for_editor( $current_post_id );
	}

	// Share WordPress number separators with JavaScript summaries that update after the PHP render pass.
	ai4seo_ensure_wp_locale_number_format();
	$number_format = array();

	// Read the guarded WP_Locale data only after the plugin fallback has repaired missing keys.
	if ( isset( $wp_locale ) && is_object( $wp_locale ) && isset( $wp_locale->number_format ) && is_array( $wp_locale->number_format ) ) {
		$number_format = $wp_locale->number_format;
	}

	$number_format_decimal_point = $number_format['decimal_point'] ?? '.';
	$number_format_thousands_sep = $number_format['thousands_sep'] ?? ',';

	// region LOCALIZATION PARAMETERS ==============================================.

	$localization_parameters = array(
		'ai4seo_asset_contexts'                            => array_values( $asset_contexts ),
		'ai4seo_plugin_identifier'                         => AI4SEO_PLUGIN_IDENTIFIER,
		'ai4seo_plugin_name'                               => AI4SEO_PLUGIN_NAME,
		'ai4seo_short_plugin_name'                         => AI4SEO_SHORT_PLUGIN_NAME,
		'ai4seo_site_url'                                  => $site_url,
		'ai4seo_admin_url'                                 => $admin_url,
		'ai4seo_admin_ajax_url'                            => $admin_ajax_url,
		'ai4seo_allowed_ajax_actions'                      => array_values( AI4SEO_ALLOWED_AJAX_FUNCTIONS ),
		'ai4seo_includes_url'                              => $includes_url,
		'ai4seo_content_url'                               => $content_url,
		'ai4seo_plugin_url'                                => $plugin_url,
		'ai4seo_plugin_directory_url'                      => $plugin_directory_url,
		'ai4seo_uploads_directory_url'                     => $uploads_directory_url,
		'ai4seo_assets_directory_url'                      => $assets_directory_url,
		'ai4seo_admin_script_url'                          => ai4seo_get_assets_js_path( AI4SEO_SCRIPTS_FILE_NAME ),
		'ai4seo_admin_style_url'                           => ai4seo_get_assets_css_path( AI4SEO_STYLES_FILE_NAME ),
		'ai4seo_does_user_need_to_accepted_tos_toc_and_pp' => $does_user_need_to_accept_tos_toc_and_pp,
		'ai4seo_plugin_version_number'                     => AI4SEO_PLUGIN_VERSION_NUMBER,
		'ai4seo_admin_scripts_version_number'              => $ai4seo_scripts_version_number,
		// Let the loaded admin script remove only the update-specific cache-busting key from the browser URL.
		'ai4seo_asset_refresh_query_parameter'              => AI4SEO_ASSET_REFRESH_QUERY_PARAMETER,
		'ai4seo_current_post_id'                           => $current_post_id,
		AI4SEO_GLOBAL_NONCE_IDENTIFIER                     => $ajax_nonce,
		'ai4seo_active_subpage'                            => $active_subpage,
		'ai4seo_active_post_type_subpage'                  => $active_post_type_subpage,
		'ai4seo_active_meta_tags'                          => $active_meta_tags,
		'ai4seo_active_attachment_attributes'              => $active_attachment_attributes,
		'ai4seo_enable_external_metadata_generate_buttons' => $enable_external_metadata_generate_buttons,
		'ai4seo_enable_external_media_generate_buttons'    => $enable_external_media_generate_buttons,
		'ai4seo_active_generation_editor_integrations'     => $active_generation_editor_integration_identifiers,
		'ai4seo_max_editor_input_lengths'                  => AI4SEO_MAX_EDITOR_INPUT_LENGTHS,
		'ai4seo_custom_instructions_length_limit'          => $custom_instructions_length_limit,
		'ai4seo_metadata_price_table'                      => $metadata_price_table,
		'ai4seo_attachment_attributes_price_table'         => $attachment_attributes_price_table,
		'ai4seo_seopress_generation_metadata'              => $seopress_generation_metadata,
		'ai4seo_number_format_decimal_point'               => $number_format_decimal_point,
		'ai4seo_number_format_thousands_sep'               => $number_format_thousands_sep,
	);

	// region REGISTER SCRIPT LOCALIZATION =========================================.

	wp_localize_script( AI4SEO_SCRIPTS_HANDLE, 'ai4seo_localization', $localization_parameters );
	wp_set_script_translations( AI4SEO_SCRIPTS_HANDLE, 'ai-for-seo' );
}


// =========================================================================================== \\

/**
 * Function to add new column to page- and post-table
 *
 * @param array $columns
 * @return array
 */
function ai4seo_add_metadata_editor_column_to_posts_table( array $columns ): array {
	// Make sure that this function is only called once.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 929531028, 'Prevented loop', true );
		return array();
	}

	$sooz_oo_logo = ai4seo_get_sooz_logo_image_tag( 'sooz-oo' );

	return array_merge( $columns, array( AI4SEO_PLUGIN_IDENTIFIER => $sooz_oo_logo ) );
}

// =========================================================================================== \\

/**
 * Function to add content to new page- and post-table column
 *
 * @param string $column_name
 * @param int    $post_id
 * @return void
 */
function ai4seo_add_metadata_editor_button_to_posts_table( string $column_name, int $post_id ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 907296736, 'Prevented loop', true );
		return;
	}

	if ( AI4SEO_PLUGIN_IDENTIFIER === $column_name ) {
		ai4seo_echo_wp_kses( ai4seo_get_edit_metadata_button( $post_id ) );
	}
}

// =========================================================================================== \\

/**
 * Function to add plugin links (in the plugin directory)
 *
 * @param mixed $links The links value.
 * @return array $links - array with links that will be displayed in the plugin directory near the plugin name
 */
function ai4seo_add_links_to_the_plugin_directory( $links ): array {
	// Check for function get_current_user_id().
	if ( ! function_exists( 'get_current_user_id' ) ) {
		return array();
	}

	// check if we loaded plugins already.
	if ( ! did_action( 'load-plugins.php' ) ) {
		return $links; // avoid running in unexpected contexts.
	}

	// double check if we are in the plugin directory.
	$this_plugin_basename = sanitize_text_field( ai4seo_get_plugin_basename() );

	if ( current_filter() !== "plugin_action_links_{$this_plugin_basename}" ) {
		return $links;
	}

	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return $links;
	}

	// remove everything from $links, except the deactivate link.
	$links = array_filter(
		$links,
		function ( $link ) {
			return strpos( $link, 'deactivate' ) !== false;
		}
	);

	// Define variables for the incognito-setting.
	$ai4seo_setting_enable_incognito_mode  = ai4seo_is_incognito_mode_enabled();
	$ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID );
	$current_user_id                       = get_current_user_id();

	// Check incognito-setting and incognito user-id.
	if ( $ai4seo_setting_enable_incognito_mode && $ai4seo_setting_incognito_mode_user_id != $current_user_id ) {
		return array();
	}

	$dashboard_link_url = ai4seo_get_subpage_url( 'dashboard' );

	// only show help and upgrade links if the user has not accepted the TOS yet.
	if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		// add accept terms of service link (by going to the plugin's dashboard).
		$tos_link_tag = "<a href='" . esc_url( $dashboard_link_url ) . "'>> " . esc_html__( 'Accept Terms of Service', 'ai-for-seo' ) . ' <</a>';
		array_unshift( $links, $tos_link_tag );
	} else {
		// Add Settings Link.
		$settings_link_url = ai4seo_get_subpage_url( 'settings' );

		if ( $settings_link_url ) {
			$settings_link_tag = "<a href='" . esc_url( $settings_link_url ) . "'>" . esc_html__( 'Settings', 'ai-for-seo' ) . '</a>';
			array_unshift( $links, $settings_link_tag );
		}

		// add Help link.
		$help_link_url = ai4seo_get_subpage_url( 'help' );

		if ( $help_link_url ) {
			$help_link_tag = "<a href='" . esc_url( $help_link_url ) . "'>" . esc_html__( 'Help', 'ai-for-seo' ) . '</a>';
			array_unshift( $links, $help_link_tag );
		}

		// todo: add get more credits link with ajax modal.

		// add dashboard link at the front of the links.
		$dashboard_link_tag = "<a href='" . esc_url( $dashboard_link_url ) . "'>" . esc_html__( 'Dashboard', 'ai-for-seo' ) . '</a>';
		array_unshift( $links, $dashboard_link_tag );
	}

	return $links;
}

// =========================================================================================== \\

/**
 * Function to add menu-item to admin-bar
 *
 * @param mixed $wp_admin_bar The wp admin bar value.
 * @return void
 */
function ai4seo_add_admin_menu_item( $wp_admin_bar ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Stop function if called outside of page or post etc.
	if ( ! is_singular() ) {
		return;
	}

	// Prepare arguments for admin-bar menu-item.
	$args = array(
		'id'    => 'ai4seo-edit',
		'title' => "<div class='ai4seo-main-menu-icon'></div> " . esc_html__( 'Metadata Editor', 'ai-for-seo' ),
		'meta'  => array(
			'onclick' => 'ai4seo_open_metadata_editor_modal();return false;',
		),
	);

	// Add node.
	$wp_admin_bar->add_node( $args );

	// Add node for mobile version.
	$wp_admin_bar->add_menu(
		array(
			'parent' => 'appearance',
			'id'     => 'ai4seo-edit-mobile',
			'title'  => sprintf(
			/* translators: %s: plugin name */
				esc_html__( '%s - Metadata Editor', 'ai-for-seo' ),
				esc_html( AI4SEO_PLUGIN_NAME )
			),
			'meta'   => array(
				'onclick' => 'ai4seo_open_metadata_editor_modal();return false;',
			),
		)
	);
}

// =========================================================================================== \\

/**
 * Function to replace white-label-placeholders
 *
 * @param string $text string with text containing placeholders
 * @return string $text with placeholders replaced
 */
function ai4seo_replace_white_label_placeholders( string $text ): string {
	$text = str_replace(
		array( '{NAME}', '{VERSION}', '{WEBSITE}' ),
		array( AI4SEO_PLUGIN_NAME, AI4SEO_PLUGIN_VERSION_NUMBER, AI4SEO_OFFICIAL_WEBSITE ),
		$text
	);

	return $text;
}

// =========================================================================================== \\

/**
 * Returns common placeholder replacements shared across metadata and attachments.
 *
 * @return array
 */
function ai4seo_get_common_placeholder_replacements(): array {
	$website_url = untrailingslashit( home_url() );
	$website_url = $website_url ? trim( esc_url_raw( $website_url ) ) : '';

	$website_name = get_bloginfo( 'name' );
	$website_name = is_string( $website_name ) ? trim( wp_strip_all_tags( $website_name ) ) : '';

	return array(
		'WEBSITE_URL'  => $website_url,
		'WEBSITE_NAME' => $website_name,
	);
}

// =========================================================================================== \\

/**
 * Returns placeholder replacements for metadata prefixes and suffixes.
 *
 * @param int    $post_id              The current post ID.
 * @param string $product_price        The WooCommerce product price if available.
 * @param string $product_name         The WooCommerce product name if available.
 *
 * @return array
 */
function ai4seo_get_metadata_placeholder_replacements( int $post_id, string $product_price = '', string $product_name = '' ): array {
	$replacements = ai4seo_get_common_placeholder_replacements();

	$replacements['POST_ID'] = (string) absint( $post_id );

	$post_type = get_post_type( $post_id );

	$product_name_value  = '';
	$product_price_value = '';

	if ( 'product' === $post_type ) {
		if ( '' === $product_name ) {
			$product_name = get_the_title( $post_id );
		}

		$product_name_value = is_string( $product_name ) ? trim( wp_strip_all_tags( $product_name ) ) : '';

		if ( '' !== $product_price ) {
			$product_price_value = trim( wp_strip_all_tags( $product_price ) );
		}
	}

	$replacements['PRODUCT_NAME']  = $product_name_value;
	$replacements['PRODUCT_PRICE'] = $product_price_value;

	return $replacements;
}

// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- Project section separator.
// =========================================================================================== \\

/**
 * Return the runtime placeholder replacements used by rendered metadata and editor previews.
 *
 * @param int $post_id Current post ID.
 *
 * @return array<string, string> Resolved placeholder values.
 */
function ai4seo_get_metadata_output_placeholder_replacements( int $post_id ): array {
	$post_type     = get_post_type( $post_id );
	$product_name  = '';
	$product_price = '';

	// Product placeholders mirror frontend output only when WooCommerce can provide the product.
	if ( 'product' === $post_type
		&& ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE )
		&& function_exists( 'wc_get_product' ) && ai4seo_is_function_usable( 'wc_get_product' )
		&& function_exists( 'wc_price' ) && ai4seo_is_function_usable( 'wc_price' )
		&& class_exists( 'WC_Product' )
	) {
		$product = wc_get_product( $post_id );

		if ( $product instanceof WC_Product ) {
			$product_name      = wp_strip_all_tags( $product->get_name() );
			$product_price_raw = $product->get_price();

			if ( '' !== $product_price_raw && null !== $product_price_raw ) {
				$product_price = wc_price( $product_price_raw );
				$product_price = wp_strip_all_tags( $product_price );
				$product_price = html_entity_decode( $product_price, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$product_price = str_replace( ' ', ' ', $product_price );
				$product_price = trim( $product_price );
			}
		}
	}

	$replacements = ai4seo_get_metadata_placeholder_replacements( $post_id, $product_price, $product_name );
	$post_title   = get_the_title( $post_id );
	$post_title   = is_string( $post_title ) ? trim( wp_strip_all_tags( $post_title ) ) : '';

	if ( '' !== $post_title ) {
		$replacements['TITLE'] = $post_title;
	}

	return $replacements;
}

// =========================================================================================== \\

/**
 * Returns placeholder replacements for attachment prefixes and suffixes.
 *
 * @param int $attachment_post_id The attachment post ID.
 *
 * @return array
 */
function ai4seo_get_attachment_placeholder_replacements( int $attachment_post_id ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 278475537, 'Prevented loop', true );
		return array();
	}

	$replacements = ai4seo_get_common_placeholder_replacements();

	$replacements['POST_ID']          = (string) absint( $attachment_post_id );
	$replacements['FILE_NAME']        = '';
	$replacements['FILE_TYPE']        = '';
	$replacements['FILE_SIZE']        = '';
	$replacements['IMAGE_DIMENSIONS'] = '';

	$attached_file_path = get_attached_file( $attachment_post_id );
	$pathinfo           = array();

	if ( $attached_file_path ) {
		$pathinfo = pathinfo( $attached_file_path );
	} else {
		$attachment_url = wp_get_attachment_url( $attachment_post_id );

		if ( $attachment_url ) {
			$url_path = wp_parse_url( $attachment_url, PHP_URL_PATH );

			if ( $url_path ) {
				$pathinfo = pathinfo( $url_path );
			}
		}
	}

	if ( ! empty( $pathinfo['filename'] ) ) {
		$replacements['FILE_NAME'] = trim( sanitize_text_field( $pathinfo['filename'] ) );
	}

	if ( ! empty( $pathinfo['extension'] ) ) {
		$replacements['FILE_TYPE'] = strtolower( trim( sanitize_text_field( $pathinfo['extension'] ) ) );
	}

	if ( $attached_file_path && file_exists( $attached_file_path ) ) {
		$file_size_bytes = @filesize( $attached_file_path );

		if ( is_int( $file_size_bytes ) || is_float( $file_size_bytes ) ) {
			$file_size_kb = $file_size_bytes / 1024;

			if ( $file_size_kb > 0 ) {
				if ( $file_size_kb < 10 ) {
					$formatted_file_size = ai4seo_format_number_i18n( round( $file_size_kb, 2 ), 2 );
				} else {
					$formatted_file_size = ai4seo_format_number_i18n( round( $file_size_kb ) );
				}
			} else {
				$formatted_file_size = '0';
			}

			$replacements['FILE_SIZE'] = trim( $formatted_file_size . ' KB' );
		}
	}

	$attachment_metadata = wp_get_attachment_metadata( $attachment_post_id );

	if ( is_array( $attachment_metadata )
		&& ! empty( $attachment_metadata['width'] )
		&& ! empty( $attachment_metadata['height'] )
	) {
		$width  = (int) $attachment_metadata['width'];
		$height = (int) $attachment_metadata['height'];

		if ( $width > 0 && $height > 0 ) {
			$replacements['IMAGE_DIMENSIONS'] = $width . 'x' . $height;
		}
	} elseif ( $attached_file_path && file_exists( $attached_file_path ) ) {
		$image_size = @getimagesize( $attached_file_path );

		if ( is_array( $image_size ) && isset( $image_size[0], $image_size[1] ) ) {
			$width  = (int) $image_size[0];
			$height = (int) $image_size[1];

			if ( $width > 0 && $height > 0 ) {
				$replacements['IMAGE_DIMENSIONS'] = $width . 'x' . $height;
			}
		}
	}

	return $replacements;
}

// =========================================================================================== \\

/**
 * Replaces supported placeholders in the provided text.
 *
 * @param string $text          The text that may contain placeholders.
 * @param array  $replacements  Map of placeholder => replacement value.
 *
 * @return string
 */
function ai4seo_replace_text_placeholders( string $text, array $replacements ): string {
	if ( '' === $text
		|| ( strpos( $text, '{' ) === false
			&& strpos( $text, '[' ) === false
			&& strpos( $text, '%%' ) === false )
	) {
		return $text;
	}

	return (string) preg_replace_callback(
		'/\{([A-Z0-9_]+)\}|\[([A-Z0-9_]+)\]|%%([A-Z0-9_]+)%%/i',
		static function ( $matches ) use ( $replacements ) {
			$placeholder = '';

			if ( ! empty( $matches[1] ) ) {
				$placeholder = $matches[1];
			} elseif ( ! empty( $matches[2] ) ) {
				$placeholder = $matches[2];
			} elseif ( ! empty( $matches[3] ) ) {
				$placeholder = $matches[3];
			}

			if ( '' !== $placeholder ) {
				$key = strtoupper( $placeholder );

				if ( array_key_exists( $key, $replacements ) ) {
					return (string) $replacements[ $key ];
				}
			}

			return $matches[0];
		},
		$text
	);
}

// =========================================================================================== \\

/**
 * Replaces the [TITLE] placeholder in metadata prefixes or suffixes.
 *
 * @param string $text       Text that may contain the [TITLE] placeholder.
 * @param string $post_title The current post title used as replacement.
 *
 * @return string
 */
function ai4seo_replace_metadata_title_placeholder( string $text, string $post_title ): string {
	if ( '' === $text || '' === $post_title ) {
		return $text;
	}

	$contains_title_placeholder = (
		stripos( $text, '{title}' ) !== false
		|| stripos( $text, '[title]' ) !== false
		|| stripos( $text, '%%title%%' ) !== false
	);

	if ( ! $contains_title_placeholder ) {
		return $text;
	}

	return str_ireplace(
		array( '{TITLE}', '[TITLE]', '%%TITLE%%' ),
		$post_title,
		$text
	);
}

// =========================================================================================== \\

/**
 * Checks whether the provided text contains WooCommerce product placeholders.
 *
 * @param string $text The text to inspect.
 *
 * @return bool
 */
function ai4seo_text_contains_product_placeholder( string $text ): bool {
	if ( '' === $text ) {
		return false;
	}

	return (bool) preg_match(
		'/\{PRODUCT_(?:NAME|PRICE)\}|\[PRODUCT_(?:NAME|PRICE)\]|%%PRODUCT_(?:NAME|PRICE)%%/i',
		$text
	);
}

// =========================================================================================== \\

/**
 * Function to modify plugin-details for white-label-settings
 *
 * @param array $all_plugins array with all plugins
 * @return array $all_plugins edited array with all plugins
 */
function ai4seo_modify_plugin_details_for_white_label( array $all_plugins ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 651264325, 'Prevented loop', true );
		return array();
	}

	// Define variable for the plugin-file.
	$plugin_file = ai4seo_get_plugin_basename();

	if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
		// If the plugin-file is not found in $all_plugins, return the original array.
		return $all_plugins;
	}

	// APPLYING WHITE-LABEL SETTINGS.
	$setting_enable_white_label = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_WHITE_LABEL );

	if ( $setting_enable_white_label ) {
		// Define variables for plugin-name and plugin-description based on settings.
		$new_plugin_name        = ai4seo_get_setting( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME );
		$new_plugin_description = ai4seo_get_setting( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION );

		// Make sure that plugin-name and plugin-description could be found and have content.
		if ( $new_plugin_name && $new_plugin_description ) {
			// Replace plugin-name and plugin-description based on settings.
			$all_plugins[ $plugin_file ]['Name']        = wp_unslash( ai4seo_mb_substr( $new_plugin_name, 0, 100 ) );
			$all_plugins[ $plugin_file ]['Description'] = stripslashes( ai4seo_mb_substr( $new_plugin_description, 0, 140 ) );
		}
	}

	// APPLYING INCOGNITO SETTINGS
	// check for function get_current_user_id().
	if ( ! function_exists( 'get_current_user_id' ) ) {
		return $all_plugins;
	}

	$current_user_id = get_current_user_id();

	// Define variables for the incognito-setting.
	$setting_enable_incognito_mode  = ai4seo_is_incognito_mode_enabled();
	$setting_incognito_mode_user_id = ai4seo_get_setting( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID );
	$visible_to_anyone              = ! $setting_enable_incognito_mode || ( AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] == $setting_incognito_mode_user_id );
	$only_visible_to_current_user   = $setting_enable_incognito_mode && ! $visible_to_anyone && $setting_incognito_mode_user_id == $current_user_id;

	// Check incognito-setting and incognito user-id.
	if ( $only_visible_to_current_user ) {
		// Add a note about the incognito mode plugin meta.
		add_filter( 'plugin_row_meta', 'ai4seo_add_incognito_note_to_plugin_meta', 10, 4 );
	} elseif ( ! $visible_to_anyone || $setting_enable_white_label ) {
		// Remove plugin-meta from plugin details.
		add_filter( 'plugin_row_meta', 'ai4seo_remove_plugin_meta', 10, 4 );
	}

	// Return array with all plugins.
	return $all_plugins;
}

// =========================================================================================== \\

/**
 * Function to remove plugin meta from plugins-list
 *
 * @param array  $plugin_meta An array of the plugin’s metadata, including the version, author, author URI, and plugin URI.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array  $plugin_data An array of plugin data.
 * @param string $status Status filter currently applied to the plugin list.
 * @return array $plugin_meta - an array with the found meta tags
 */
function ai4seo_remove_plugin_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {
	// Check if slug could be found and if it matches the plugin.
	if ( isset( $plugin_data['slug'] ) && AI4SEO_PLUGIN_IDENTIFIER === $plugin_data['slug'] ) {
		$plugin_meta[] = esc_html__( 'Version', 'ai-for-seo' ) . ': ' . AI4SEO_PLUGIN_VERSION_NUMBER;
	}

	return $plugin_meta;
}

// =========================================================================================== \\

/**
 * Function to add a note about the incognito mode plugin meta
 *
 * @param array  $plugin_meta An array of the plugin’s metadata, including the version, author, author URI, and plugin URI.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array  $plugin_data An array of plugin data.
 * @param string $status Status filter currently applied to the plugin list.
 * @return array $plugin_meta - an array with the found meta tags
 */
function ai4seo_add_incognito_note_to_plugin_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {
	// Check if slug could be found and if it matches the plugin.
	if ( isset( $plugin_data['slug'] ) && AI4SEO_PLUGIN_IDENTIFIER === $plugin_data['slug'] ) {
		$plugin_meta[] = esc_html__( '(Incognito Mode: This info is only visible to you)', 'ai-for-seo' );
	}

	return $plugin_meta;
}

// =========================================================================================== \\

// endregion
// ___________________________________________________________________________________________.
