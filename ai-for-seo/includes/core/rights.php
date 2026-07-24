<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region RIGHTS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

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
