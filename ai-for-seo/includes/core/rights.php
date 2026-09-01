<?php
/**
 * Manages plugin roles, capabilities, and permissions.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region RIGHTS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Normalize an incognito-mode owner to a canonical identifier.
 *
 * Legacy options may contain integer-equivalent floats, decimals, exponents, or leading plus
 * signs. Exact decimal normalization keeps those owners usable without applying loose permission
 * comparisons or passing large identifiers through a lossy float conversion.
 *
 * @param mixed $user_id User-ID representation.
 * @return int|null Non-negative owner identifier, where zero denotes a missing owner, or null when malformed.
 */
function ai4seo_normalize_incognito_mode_user_id( $user_id ): ?int {
	// Native WordPress IDs stay exact without passing through string or float coercion.
	if ( is_int( $user_id ) ) {
		return 0 <= $user_id ? $user_id : null;
	}

	// Historical imports could retain an integer-valued float before settings sanitization.
	if ( is_float( $user_id ) ) {
		if ( ! is_finite( $user_id ) || 0.0 > $user_id || floor( $user_id ) !== $user_id ) {
			return null;
		}

		if ( 0.0 === $user_id ) {
			return 0;
		}

		// Format the float as decimal text so overflow checks happen before any integer cast.
		return ai4seo_normalize_incognito_mode_user_id( sprintf( '%.0F', $user_id ) );
	}

	if ( ! is_string( $user_id ) ) {
		return null;
	}

	$user_id = trim( $user_id );

	if ( 1 !== preg_match( '/^\+?(\d+)(?:\.(\d*))?(?:[eE]([+-]?\d+))?$/D', $user_id, $matches ) ) {
		return null;
	}

	$whole_digits    = $matches[1];
	$fraction_digits = $matches[2] ?? '';
	$all_digits      = $whole_digits . $fraction_digits;

	if ( '' === trim( $all_digits, '0' ) ) {
		return 0;
	}

	$exponent_text      = $matches[3] ?? '0';
	$negative_exponent  = '-' === substr( $exponent_text, 0, 1 );
	$exponent_digits    = ltrim( $exponent_text, '+-' );
	$exponent_digits    = ltrim( $exponent_digits, '0' );
	$maximum_exponent   = strlen( $all_digits ) + strlen( (string) PHP_INT_MAX );
	$maximum_as_string  = (string) $maximum_exponent;
	$exponent_magnitude = 0;

	if ( '' !== $exponent_digits ) {
		if ( strlen( $maximum_as_string ) < strlen( $exponent_digits )
			|| ( strlen( $maximum_as_string ) === strlen( $exponent_digits ) && 0 > strcmp( $maximum_as_string, $exponent_digits ) )
		) {
			return null;
		}

		$exponent_magnitude = (int) $exponent_digits;
	}

	$exponent         = $negative_exponent ? -$exponent_magnitude : $exponent_magnitude;
	$decimal_position = strlen( $whole_digits ) + $exponent;

	// A non-zero value whose decimal point stays left of every digit cannot represent an integer.
	if ( 0 >= $decimal_position ) {
		return null;
	}

	if ( strlen( $all_digits ) > $decimal_position ) {
		$fractional_remainder = substr( $all_digits, $decimal_position );

		if ( '' !== trim( $fractional_remainder, '0' ) ) {
			return null;
		}

		$integer_digits = substr( $all_digits, 0, $decimal_position );
	} else {
		$integer_digits = $all_digits . str_repeat( '0', $decimal_position - strlen( $all_digits ) );
	}

	$integer_digits = ltrim( $integer_digits, '0' );

	if ( '' === $integer_digits ) {
		return 0;
	}

	// Confirm the exact normalized identifier fits the platform integer range before casting it.
	$maximum_integer_user_id = (string) PHP_INT_MAX;

	if ( strlen( $maximum_integer_user_id ) < strlen( $integer_digits )
		|| ( strlen( $maximum_integer_user_id ) === strlen( $integer_digits ) && 0 > strcmp( $maximum_integer_user_id, $integer_digits ) )
	) {
		return null;
	}

	return (int) $integer_digits;
}


/**
 * Return the configured incognito-mode owner as a canonical identifier.
 *
 * @return int|null Non-negative owner identifier, where zero denotes a missing owner, or null when malformed.
 */
function ai4seo_get_incognito_mode_user_id(): ?int {
	// Centralize stored-owner interpretation so every permission surface shares the same boundary.
	return ai4seo_normalize_incognito_mode_user_id( ai4seo_get_setting( AI4SEO_SETTING_INCOGNITO_MODE_USER_ID ) );
}


/**
 * Return the valid administrative owner of Incognito Mode.
 *
 * @return int|null Administrative owner ID, or null when ownership is missing or invalid.
 */
function ai4seo_get_valid_incognito_mode_owner_id(): ?int {
	$owner_id = ai4seo_get_incognito_mode_user_id();

	if ( null === $owner_id || $owner_id <= 0 || ! function_exists( 'user_can' ) ) {
		return null;
	}

	// Ownership is an administrative boundary, so historical lower-role owners are no longer valid.
	return user_can( $owner_id, 'manage_options' ) ? $owner_id : null;
}


/**
 * Check whether enabled Incognito Mode requires an administrator to repair its ownership.
 *
 * @return bool
 */
function ai4seo_is_incognito_mode_recovery_required(): bool {
	return ai4seo_is_incognito_mode_enabled() && null === ai4seo_get_valid_incognito_mode_owner_id();
}


/**
 * Check whether the current administrator may repair invalid Incognito Mode ownership.
 *
 * @return bool
 */
function ai4seo_can_recover_incognito_mode(): bool {
	return function_exists( 'current_user_can' )
		&& current_user_can( 'manage_options' )
		&& ai4seo_is_incognito_mode_recovery_required();
}


/**
 * Check whether the current user may administer plugin-wide settings and account data.
 *
 * @return bool
 */
function ai4seo_can_administer_plugin(): bool {
	if ( ! function_exists( 'current_user_can' ) || ! function_exists( 'get_current_user_id' ) ) {
		return false;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( ! ai4seo_is_incognito_mode_enabled() ) {
		return true;
	}

	$owner_id = ai4seo_get_valid_incognito_mode_owner_id();

	if ( null === $owner_id ) {
		return false;
	}

	return in_array( get_current_user_id(), array( $owner_id ), true );
}


/**
 * Check whether the current user may generate or edit content-level SEO data.
 *
 * @return bool
 */
function ai4seo_can_use_plugin_content(): bool {
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

	// Enabled Incognito Mode grants access only to a valid administrative owner.
	if ( ai4seo_is_incognito_mode_enabled() ) {
		$owner_id = ai4seo_get_valid_incognito_mode_owner_id();

		if ( null === $owner_id || ! in_array( get_current_user_id(), array( $owner_id ), true ) ) {
			return false;
		}

		// A valid administrative owner has exclusive content access while Incognito Mode is enabled.
		$ai4seo_can_manage_this_plugin = true;
		return true;
	}

	// if we are here, we can assume the outcome of this function can be cached
	// (before this point, WordPress might not be fully loaded).
	$ai4seo_can_manage_this_plugin = false;

	// Treat malformed or stale stored access rules as administrator-only instead of risking a type-based bypass.
	$allowed_user_roles = ai4seo_get_setting( AI4SEO_SETTING_ALLOWED_USER_ROLES );

	if ( ! ai4seo_validate_allowed_user_roles( $allowed_user_roles ) ) {
		$allowed_user_roles = array( 'administrator' );
	}

	// Get the details of the current user.
	$user = wp_get_current_user();

	// Stop script if the current user or the roles of the current user could not be read.
	if ( ! $user || ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
		return false;
	}

	// Canonicalize WordPress role values so numeric-only identifiers retain their string setting representation.
	$current_user_roles = array();

	foreach ( $user->roles as $current_user_role ) {
		$current_user_role = ai4seo_canonicalize_user_role_identifier( $current_user_role );

		if ( '' !== $current_user_role ) {
			$current_user_roles[] = $current_user_role;
		}
	}

	// Grant access only when a validated setting role strictly matches a canonical current-user role.
	foreach ( $allowed_user_roles as $allowed_user_role ) {
		if ( in_array( $allowed_user_role, $current_user_roles, true ) ) {
			$ai4seo_can_manage_this_plugin = true;
			return true;
		}
	}

	return false;
}


/**
 * Backward-compatible alias for the plugin's configured-role content permission.
 *
 * @return bool
 */
function ai4seo_can_manage_this_plugin(): bool {
	return ai4seo_can_use_plugin_content();
}


/**
 * Canonicalize a WordPress role identifier for strict access comparisons.
 *
 * WordPress role registry keys can be integers when an otherwise valid role identifier contains
 * only digits. Setting values remain strings because they originate from form and JSON data.
 *
 * @param mixed $user_role_identifier WordPress role identifier.
 * @return string Canonical role identifier, or an empty string for an unsupported value.
 */
function ai4seo_canonicalize_user_role_identifier( $user_role_identifier ): string {
	if ( ! is_string( $user_role_identifier ) && ! is_int( $user_role_identifier ) ) {
		return '';
	}

	return sanitize_key( (string) $user_role_identifier );
}


/**
 * Retrieve every available role identifier in its canonical string representation.
 *
 * @return array<string> Canonical role identifiers.
 */
function ai4seo_get_canonical_possible_user_role_identifiers(): array {
	$canonical_user_role_identifiers = array();

	foreach ( array_keys( ai4seo_get_all_possible_user_roles() ) as $user_role_identifier ) {
		$user_role_identifier = ai4seo_canonicalize_user_role_identifier( $user_role_identifier );

		if ( '' !== $user_role_identifier && ! in_array( $user_role_identifier, $canonical_user_role_identifiers, true ) ) {
			$canonical_user_role_identifiers[] = $user_role_identifier;
		}
	}

	return $canonical_user_role_identifiers;
}


/**
 * Validate the complete allowed-user-role setting at its shared storage and runtime boundary.
 *
 * @param mixed $allowed_user_roles Allowed-user-role setting value.
 * @return bool True when every role is a unique, canonical, currently available string and administrators remain allowed.
 */
function ai4seo_validate_allowed_user_roles( $allowed_user_roles ): bool {
	if ( ! is_array( $allowed_user_roles ) ) {
		return false;
	}

	$possible_user_role_identifiers  = ai4seo_get_canonical_possible_user_role_identifiers();
	$validated_user_role_identifiers = array();

	foreach ( $allowed_user_roles as $user_role_identifier ) {
		if ( ! is_string( $user_role_identifier )
			|| ! in_array( $user_role_identifier, $possible_user_role_identifiers, true )
			|| in_array( $user_role_identifier, $validated_user_role_identifiers, true ) ) {
			return false;
		}

		$validated_user_role_identifiers[] = $user_role_identifier;
	}

	return in_array( 'administrator', $validated_user_role_identifiers, true );
}


/**
 * Checks whether the current plugin user may edit a specific post object.
 *
 * @param int $post_id Post ID to check.
 * @return bool
 */
function ai4seo_can_edit_post( int $post_id ): bool {
	// Object checks apply only after the current user has passed the plugin-wide role gate.
	if ( $post_id <= 0 || ! ai4seo_can_use_plugin_content() || ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	// Delegate per-object ownership and post-type capability mapping to WordPress.
	return current_user_can( 'edit_post', $post_id );
}


/**
 * Checks whether the current plugin user may edit every supplied post object.
 *
 * @param array $post_ids Post IDs to check.
 * @return bool
 */
function ai4seo_can_edit_post_ids( array $post_ids ): bool {
	// Bulk operations are all-or-nothing so one forbidden object rejects the complete request.
	foreach ( $post_ids as $post_id ) {
		if ( ! ai4seo_can_edit_post( absint( $post_id ) ) ) {
			return false;
		}
	}

	// An empty list is valid here because callers decide separately whether their action requires entries.
	return true;
}


/**
 * Returns only post IDs the current plugin user may edit.
 *
 * @param array $post_ids Post IDs to filter.
 * @return array Editable post IDs in their original order.
 */
function ai4seo_filter_editable_post_ids( array $post_ids ): array {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	if ( ! $post_ids || ! ai4seo_can_use_plugin_content() || ! function_exists( 'current_user_can' ) ) {
		return array();
	}

	// Check the plugin-wide gate once before applying WordPress's per-object capability mapping.
	return array_values(
		array_filter(
			$post_ids,
			function ( int $post_id ): bool {
				return current_user_can( 'edit_post', $post_id );
			}
		)
	);
}


/**
 * Checks whether optimized list queries may include every author's entries for the given post types.
 *
 * @param array $post_types Post type identifiers.
 * @return bool True when the current plugin user may edit other users' entries for every post type.
 */
function ai4seo_can_edit_others_posts_for_post_types( array $post_types ): bool {
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types || ! ai4seo_can_use_plugin_content() || ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	foreach ( $post_types as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		$edit_others_cap  = is_object( $post_type_object ) && isset( $post_type_object->cap->edit_others_posts )
			? (string) $post_type_object->cap->edit_others_posts
			: '';

		if ( '' === $edit_others_cap || ! current_user_can( $edit_others_cap ) ) {
			return false;
		}
	}

	return true;
}


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
		// Canonicalize identifiers before strict setting validation and permission checks consume them.
		$user_role_identifier = ai4seo_canonicalize_user_role_identifier( $user_role_identifier );
		$user_role            = sanitize_text_field( $user_role );

		if ( '' === $user_role_identifier ) {
			continue;
		}

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

	if ( ! is_array( $ai4seo_forbidden_allowed_user_roles ) ) {
		return;
	}

	foreach ( $ai4seo_forbidden_allowed_user_roles as $user_role ) {
		unset( $user_roles[ $user_role ] );
	}
}


// endregion
// ___________________________________________________________________________________________.
