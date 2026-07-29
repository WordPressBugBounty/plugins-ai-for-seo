<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region RIGHTS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to check whether the current user is allowed to use this plugin
 *
 * @return bool
 */
function ai4seo_can_manage_this_plugin(): bool {
	global $ai4seo_can_manage_this_plugin;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 472226712, 'Prevented loop', true );
		return false;
	}

	// use cache if available.
	if ( null !== $ai4seo_can_manage_this_plugin ) {
		return $ai4seo_can_manage_this_plugin;
	}

	// check if is_user_logged_in() is defined.
	if ( ! function_exists( 'is_user_logged_in' ) ) {
		return false;
	}

	if ( ! function_exists( 'get_current_user_id' ) ) {
		return false;
	}

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		return false;
	}

	// Check if the current user is logged in.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Define variables for the incognito-setting.
	$ai4seo_setting_enable_incognito_mode  = ai4seo_is_incognito_mode_enabled();
	$ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID );
	$current_user_id                       = get_current_user_id();

	// Check incognito-setting and incognito user-id.
	if ( $ai4seo_setting_enable_incognito_mode && AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ] != $ai4seo_setting_incognito_mode_user_id
		&& $ai4seo_setting_incognito_mode_user_id != $current_user_id ) {
		return false;
	}

	// if we are here, we can assume the outcome of this function can be cached
	// (before this point, WordPress might not be fully loaded).
	$ai4seo_can_manage_this_plugin = false;

	// Define variable for the allowed user-roles based on plugin-settings.
	$allowed_user_roles = ai4seo_get_setting( AI4SEO_SETTING_ALLOWED_USER_ROLES );

	if ( ! $allowed_user_roles || ! is_array( $allowed_user_roles ) ) {
		return false;
	}

	// Get the details of the current user.
	$user = wp_get_current_user();

	// Stop script if the current user or the roles of the current user could not be read.
	if ( ! $user || ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
		return false;
	}

	// Loop through allowed roles and check if roles apply to current user.
	foreach ( $allowed_user_roles as $allowed_user_role ) {
		// Check if the user has this allowed role.
		if ( in_array( $allowed_user_role, (array) $user->roles ) ) {
			$ai4seo_can_manage_this_plugin = true;
			return true;
		}
	}

	return false;
}

// =========================================================================================== \\

/**
 * Retrieve an array of all user-roles that are currently available
 *
 * @return array An array of all user-roles
 */
function ai4seo_get_all_possible_user_roles(): array {
	global $ai4seo_fallback_allowed_user_roles;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 874528039, 'Prevented loop', true );
		return $ai4seo_fallback_allowed_user_roles;
	}

	if ( ! function_exists( 'wp_roles' ) ) {
		ai4seo_debug_message( 49176824, 'wp_roles() does not exist', true );
		return $ai4seo_fallback_allowed_user_roles;
	}

	// Attempt to get WordPress roles.
	$wp_roles = wp_roles();

	// Check if wp_roles() returned a valid object.
	if ( ! is_object( $wp_roles ) || ! method_exists( $wp_roles, 'get_names' ) ) {
		ai4seo_debug_message( 50176824, 'wp_roles() did not return a valid object', true );
		return $ai4seo_fallback_allowed_user_roles;
	}

	// Get the array of role names.
	$not_sanitized_user_roles = $wp_roles->get_names();

	// Check if roles array is not empty.
	if ( empty( $not_sanitized_user_roles ) ) {
		ai4seo_debug_message( 51176824, 'wp_roles() did not return any roles', true );
		return $ai4seo_fallback_allowed_user_roles;
	}

	// sanitize and filter based on 'edit_post' capability.
	$sanitized_user_roles = array();

	foreach ( $not_sanitized_user_roles as $user_role_identifier => $user_role ) {
		// Sanitize identifiers.
		$user_role_identifier = sanitize_key( $user_role_identifier );
		$user_role            = sanitize_text_field( $user_role );

		// Check if the role has the 'edit_post' capability.
		$role_object = get_role( $user_role_identifier );

		if ( $role_object && $role_object->has_cap( 'edit_posts' ) ) {
			$sanitized_user_roles[ $user_role_identifier ] = $user_role;
		}
	}

	ai4seo_remove_forbidden_allowed_user_roles( $sanitized_user_roles );

	// add administrator role if it's not already in the array.
	if ( ! isset( $sanitized_user_roles['administrator'] ) ) {
		$sanitized_user_roles['administrator'] = 'Administrator';
	}

	return $sanitized_user_roles;
}

// =========================================================================================== \\

/**
 * Removes forbidden user roles from the given user roles array
 *
 * @param mixed $user_roles The user roles value.
 * @return void
 */
function ai4seo_remove_forbidden_allowed_user_roles( &$user_roles ) {
	global $ai4seo_forbidden_allowed_user_roles;

	if ( ! is_array( $user_roles ) ) {
		return;
	}

	foreach ( $ai4seo_forbidden_allowed_user_roles as $user_role ) {
		unset( $user_roles[ $user_role ] );
	}
}


// endregion
// ___________________________________________________________________________________________.
