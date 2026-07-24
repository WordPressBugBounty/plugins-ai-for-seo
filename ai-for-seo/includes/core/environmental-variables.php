<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region ENVIRONMENTAL VARIABLES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to retrieve all environmental variables from database
 *
 * @param bool $use_cache Should we use the cache
 * @return array All environmental variables
 */
function ai4seo_read_all_environmental_variables( bool $use_cache = true ): array {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 690812093, 'Prevented loop', true );
		return array();
	}

	if ( ! isset( $ai4seo_environmental_variables ) || ! is_array( $ai4seo_environmental_variables ) ) {
		$ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
	}

	// get cached version.
	if ( $use_cache && $ai4seo_environmental_variables_are_loaded ) {
		return $ai4seo_environmental_variables;
	}

	$current_environmental_variables = ai4seo_get_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, false, ! $use_cache );

	// nothing in our database? fallback to known/default environmental variables.
	if ( ! is_array( $current_environmental_variables ) || ! $current_environmental_variables ) {
		$ai4seo_environmental_variables            = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
		$ai4seo_environmental_variables_are_loaded = true;
		return $ai4seo_environmental_variables;
	}

	$loaded_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;

	// go through each base environmental variable and check if it is valid.
	foreach ( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES as $environmental_variable_name => $environmental_variable_value ) {
		// set default if not set.
		if ( ! isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
			$current_environmental_variables[ $environmental_variable_name ] = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];
		}

		// validate.
		if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $current_environmental_variables[ $environmental_variable_name ] ) ) {
			ai4seo_debug_message( 2317181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true );
			$current_environmental_variables[ $environmental_variable_name ] = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];
		}

		$loaded_environmental_variables[ $environmental_variable_name ] = $current_environmental_variables[ $environmental_variable_name ];
	}

	// include ttl companion entries as runtime-accepted environmental variables.
	foreach ( $current_environmental_variables as $environmental_variable_name => $environmental_variable_value ) {
		if ( ! ai4seo_is_environmental_variable_ttl_name( (string) $environmental_variable_name ) ) {
			continue;
		}

		if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $environmental_variable_value ) ) {
			ai4seo_debug_message( 2317181025, 'Invalid TTL value for environmental variable "' . $environmental_variable_name . '"', true );
			continue;
		}

		$loaded_environmental_variables[ $environmental_variable_name ] = (int) $environmental_variable_value;
	}

	$ai4seo_environmental_variables            = $loaded_environmental_variables;
	$ai4seo_environmental_variables_are_loaded = true;

	return $ai4seo_environmental_variables;
}

// =========================================================================================== \\

/**
 * Function to retrieve a specific environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable
 * @param bool   $use_cache Should we use the cache
 * @return mixed The value of the environmental variable
 */
function ai4seo_read_environmental_variable( string $environmental_variable_name, bool $use_cache = true ) {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 232735921, 'Prevented loop', true );
		return null;
	}

	// Make sure that $environmental_variable_name-parameter has content.
	if ( ! $environmental_variable_name ) {
		ai4seo_debug_message( 515181024, 'Environmental variable name is empty.', true );
		return null;
	}

	// check for the default value.
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		ai4seo_debug_message( 56187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true );
		return null;
	}

	$current_environmental_variables = ai4seo_read_all_environmental_variables( $use_cache );

	// Check if the $environmental_variable_name-parameter exists in environmental variables-array.
	if ( isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
		return $current_environmental_variables[ $environmental_variable_name ];
	} else {
		return AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];
	}
}

// =========================================================================================== \\

/**
 * Function to update a specific environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable
 * @param mixed  $new_environmental_variable_value The new value of the environmental variable
 * @param bool   $use_cache Should we use the cache
 * @param int    $cache_ttl Cache TTL in seconds. 0 disables TTL companion updates.
 * @return bool True if the environmental variable was updated successfully, false if not
 */
function ai4seo_update_environmental_variable( string $environmental_variable_name, $new_environmental_variable_value, bool $use_cache = true, int $cache_ttl = 0 ): bool {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 726736127, 'Prevented loop', true );
		return false;
	}

	if ( ai4seo_is_environmental_variable_ttl_name( $environmental_variable_name ) ) {
		ai4seo_debug_message( 141224226, 'Attempted to update TTL companion variable via ai4seo_update_environmental_variable(): "' . $environmental_variable_name . '"', true );
		return false;
	}

	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		ai4seo_debug_message( 51187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true );
		return false;
	}

	// Make sure that the new value of the environmental variable is valid.
	if ( ! ai4seo_validate_environmental_variable_value( $environmental_variable_name, $new_environmental_variable_value ) ) {
		ai4seo_debug_message( 535181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true );
		return false;
	}

	// sanitize.
	$new_environmental_variable_value = ai4seo_deep_sanitize( $new_environmental_variable_value );

	// use semaphore to make sure this critical section is thread-safe.
	if ( ! $use_cache ) {
		/*
		if (!ai4seo_acquire_semaphore(__FUNCTION__)) {
			// could not acquire semaphore -> another process is in the critical section -> return
			return false;
		}*/
	}

	// overwrite entry in $current_environmental_variables-array.
	$current_environmental_variables = ai4seo_read_all_environmental_variables( $use_cache );

	// is same as default value? delete it.
	if ( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] == $new_environmental_variable_value ) {
		unset( $current_environmental_variables[ $environmental_variable_name ] );
	} else {
		$value_is_unchanged = isset( $current_environmental_variables[ $environmental_variable_name ] )
			&& $current_environmental_variables[ $environmental_variable_name ] === $new_environmental_variable_value;

		// An unchanged cache value still needs its TTL renewed.
		if ( $value_is_unchanged && $cache_ttl <= 0 ) {
			return true;
		}

		if ( ! $value_is_unchanged ) {
			$current_environmental_variables[ $environmental_variable_name ] = $new_environmental_variable_value;
		}
	}

	// if we have a cache TTL, we also update the TTL companion variable to current time + TTL, so that the cache can be properly invalidated after the TTL has expired.
	if ( $cache_ttl > 0 ) {
		$ttl_environmental_variable_name                                     = ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name );
		$current_environmental_variables[ $ttl_environmental_variable_name ] = time() + $cache_ttl;
	}

	// no changes made.
	if ( $ai4seo_environmental_variables == $current_environmental_variables ) {
		return true;
	}

	// update the global parameter as well.
	$ai4seo_environmental_variables            = $current_environmental_variables;
	$ai4seo_environmental_variables_are_loaded = true;

	// Save updated environmental variables to database.
	$success = ai4seo_update_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables, false, ! $use_cache );

	if ( ! $use_cache ) {
		// ai4seo_release_semaphore(__FUNCTION__);.
	}

	return $success;
}

// =========================================================================================== \\

/**
 * Function to delete an environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable
 * @return bool True if the environmental variable was deleted successfully, false if not
 */
function ai4seo_delete_environmental_variable( string $environmental_variable_name ): bool {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 912986381, 'Prevented loop', true );
		return false;
	}

	// Make sure that $environmental_variable_name-parameter has content.
	if ( ! $environmental_variable_name ) {
		ai4seo_debug_message( 491226225, 'Environmental variable name is empty.', true );
		return false;
	}

	// overwrite entry in $current_environmental_variables-array.
	$current_environmental_variables = ai4seo_read_all_environmental_variables();

	if ( ! isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
		return true;
	}

	// delete the entry.
	unset( $current_environmental_variables[ $environmental_variable_name ] );

	// update the class parameter as well.
	$ai4seo_environmental_variables            = $current_environmental_variables;
	$ai4seo_environmental_variables_are_loaded = true;

	// Save updated environmental variables to database.
	return ai4seo_update_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables, false );
}

// =========================================================================================== \\

/**
 * Deletes all environmental variables
 *
 * @return bool
 */
function ai4seo_delete_all_environmental_variables(): bool {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	$ai4seo_environmental_variables            = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
	$ai4seo_environmental_variables_are_loaded = true;

	return ai4seo_delete_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );
}

// =========================================================================================== \\

/**
 * Bulk update environmental variables.
 *
 * Accepts an associative array of updates like array( 'variable_name' => 'new_value', ... ).
 * Each entry is validated against AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES and
 * ai4seo_validate_environmental_variable_value() before being applied.
 * Values equal to their defaults are removed from the stored overrides.
 *
 * @param array $environmental_variable_updates Associative array: name => value.
 * @return array {
 *     @type bool  $success        True if persisted successfully (or nothing to persist), false on DB write failure.
 *     @type int   $updated_count  Number of variables that changed (added/updated/removed).
 *     @type array $invalid_names  List of names skipped because they are unknown.
 *     @type array $invalid_values List of names skipped because the value was invalid.
 * }
 */
function ai4seo_bulk_update_environmental_variables( array $environmental_variable_updates ): array {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	$result = array(
		'success'        => true,
		'updated_count'  => 0,
		'invalid_names'  => array(),
		'invalid_values' => array(),
	);

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 210791927, 'Prevented loop', true );
		return $result;
	}

	// Nothing to do.
	if ( empty( $environmental_variable_updates ) ) {
		return $result;
	}

	// Read current overrides once.
	$current_environmental_variables = ai4seo_read_all_environmental_variables();

	// Iterate all requested updates.
	foreach ( $environmental_variable_updates as $this_name => $this_value ) {
		// Validate variable name against whitelist.
		if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) ) {
			// Unknown name. Skip and record.
			$result['invalid_names'][] = $this_name;
			ai4seo_debug_message( 2017171025, 'Unknown environmental variable name in bulk update: "' . $this_name . '"', true );
			continue;
		}

		// Validate value using existing validator.
		if ( ! ai4seo_validate_environmental_variable_value( $this_name, $this_value ) ) {
			// Invalid value. Skip and record.
			$result['invalid_values'][] = $this_name;
			ai4seo_debug_message( 2117171025, 'Invalid value for environmental variable "' . $this_name . '" in bulk update.', true );
			continue;
		}

		// Sanitize value deeply.
		$this_value = ai4seo_deep_sanitize( $this_value );

		// If equals default, ensure override is removed.
		if ( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] == $this_value ) {
			if ( isset( $current_environmental_variables[ $this_name ] ) ) {
				unset( $current_environmental_variables[ $this_name ] );
				++$result['updated_count'];
			}
			continue;
		}

		// If no change vs current override, skip.
		if ( isset( $current_environmental_variables[ $this_name ] )
			&& $current_environmental_variables[ $this_name ] == $this_value ) {
			continue;
		}

		// Apply/overwrite override.
		$current_environmental_variables[ $this_name ] = $this_value;
		++$result['updated_count'];
	}

	// If nothing changed, keep success=true and return.
	if ( $ai4seo_environmental_variables == $current_environmental_variables ) {
		return $result;
	}

	// Update the global cache.
	$ai4seo_environmental_variables            = $current_environmental_variables;
	$ai4seo_environmental_variables_are_loaded = true;

	// Persist once.
	$did_update = ai4seo_update_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables, false );

	if ( ! $did_update ) {
		// DB write failed. Keep in-memory state but surface failure.
		$result['success'] = false;
		ai4seo_debug_message( 2217171025, 'Failed to persist environmental variables in bulk update.', true );
	}

	return $result;
}

// =========================================================================================== \\

/**
 * Validate value of an environmental variable
 *
 * @param string $environmental_variable_name The name of the environmental variable
 * @param mixed  $environmental_variable_value The value of the environmental variable
 */
function ai4seo_validate_environmental_variable_value( string $environmental_variable_name, $environmental_variable_value ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		ai4seo_debug_message( 705807482, 'Prevented loop', true );
		return false;
	}

	if ( ai4seo_is_environmental_variable_ttl_name( $environmental_variable_name ) ) {
		return is_numeric( $environmental_variable_value ) && (int) $environmental_variable_value >= 0;
	}

	switch ( $environmental_variable_name ) {
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION:
			// contains only of numbers and dots.
			return is_string( $environmental_variable_value ) && preg_match( '/^[0-9.]+$/', $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME:
			// contains only of numbers.
			return is_numeric( $environmental_variable_value ) && $environmental_variable_value >= 0;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE:
			// string with specific allowed values.
			return in_array( $environmental_variable_value, array( 'idle', 'processing', 'completed' ) );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT:
			// empty or array with at least name and percentage.
			if ( empty( $environmental_variable_value ) ) {
				return true;
			}

			if ( ! is_array( $environmental_variable_value ) || ! isset( $environmental_variable_value['name'] ) || ! isset( $environmental_variable_value['percentage'] ) ) {
				ai4seo_debug_message( 531729725, 'Invalid current discount environmental variable.', true );
				return false;
			}

			// name contains only small letters and "-".
			if ( ! is_string( $environmental_variable_value['name'] ) || ! preg_match( '/^[a-z0-9-]+$/', $environmental_variable_value['name'] ) ) {
				ai4seo_debug_message( 541729725, 'Invalid discount name in the current discount environmental variable.', true );
				return false;
			}

			// percentage must be int.
			if ( ! is_numeric( $environmental_variable_value['percentage'] ) || $environmental_variable_value['percentage'] < 0 || $environmental_variable_value['percentage'] > 100 ) {
				ai4seo_debug_message( 551729725, 'Invalid percentage in the current discount environmental variable.', true );
				return false;
			}

			// if expire_in is provided, check its integer and between 0 and 99.999.999.
			if ( isset( $environmental_variable_value['expire_in'] ) && ( ! is_numeric( $environmental_variable_value['expire_in'] ) || $environmental_variable_value['expire_in'] < 0 || $environmental_variable_value['expire_in'] > 99999999 ) ) {
				ai4seo_debug_message( 561729725, 'Invalid expire_in in the current discount environmental variable.', true );
				return false;
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED:
			// boolean.
			return is_bool( $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST:
			// array of strings (containing a-z and -).
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $key => $value ) {
				if ( ! is_string( $value ) || ! preg_match( '/^[a-z0-9-]+$/', $value ) ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME:
			if ( ! is_numeric( $environmental_variable_value ) || $environmental_variable_value < 0 ) {
				ai4seo_debug_message( 5713171224, 'Invalid value in the automated generations new or existing filter reference times setting.', true );
				return false;
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER:
			return is_bool( $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS:
		case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES:
			// array of integers >= 0.
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $key => $value ) {
				if ( ! is_numeric( $value ) || $value < 0 ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS:
			return in_array( $environmental_variable_value, AI4SEO_ALLOWED_PAYG_STATUS );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON:
			if ( ! is_string( $environmental_variable_value ) ) {
				return false;
			}

			return ( '' === $environmental_variable_value || sanitize_key( $environmental_variable_value ) === $environmental_variable_value );

		case AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_value ) {
				if ( ! is_string( $this_value ) || sanitize_key( $this_value ) !== $this_value ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_key => $this_value ) {
				if ( ! is_string( $this_key ) || '' === $this_key ) {
					return false;
				}

				if ( ! is_array( $this_value ) ) {
					return false;
				}

				foreach ( $this_value as $this_author_id => $this_author_label ) {
					if ( ! is_numeric( $this_author_id ) || (int) $this_author_id <= 0 ) {
						return false;
					}

					if ( ! is_string( $this_author_label ) ) {
						return false;
					}
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_taxonomy_name => $this_taxonomy_data ) {
				if ( ! is_string( $this_taxonomy_name ) || sanitize_key( $this_taxonomy_name ) !== $this_taxonomy_name || '' === $this_taxonomy_name ) {
					return false;
				}

				if (
					! is_array( $this_taxonomy_data )
					|| ! isset( $this_taxonomy_data['label'] )
					|| ! isset( $this_taxonomy_data['terms'] )
					|| ! is_string( $this_taxonomy_data['label'] )
					|| ! is_array( $this_taxonomy_data['terms'] )
				) {
					return false;
				}

				foreach ( $this_taxonomy_data['terms'] as $this_term_id => $this_term_name ) {
					if ( ! is_numeric( $this_term_id ) || (int) $this_term_id <= 0 ) {
						return false;
					}

					if ( ! is_string( $this_term_name ) ) {
						return false;
					}
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_value ) {
				if ( ! is_numeric( $this_value ) || (int) $this_value < 0 ) {
					return false;
				}
			}

			return true;

		case AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE:
			if ( ! is_array( $environmental_variable_value ) ) {
				return false;
			}

			foreach ( $environmental_variable_value as $this_key => $this_value ) {
				if ( ! is_string( $this_key ) || '' === $this_key ) {
					return false;
				}

				if ( ! is_numeric( $this_value ) || (int) $this_value < 0 ) {
					return false;
				}
			}

			return true;

		default:
			return false;
	}
}

// =========================================================================================== \\

/**
 * Check if an environmental variable name is a TTL companion variable.
 *
 * @param string $environmental_variable_name
 * @return bool True if the name ends with the TTL suffix, false otherwise.
 */
function ai4seo_is_environmental_variable_ttl_name( string $environmental_variable_name ): bool {
	return substr( $environmental_variable_name, -strlen( AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX ) ) === AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}

// =========================================================================================== \\

/**
 * Build ttl companion environmental variable name.
 *
 * @param string $environmental_variable_name
 * @return string
 */
function ai4seo_get_environmental_variable_ttl_name( string $environmental_variable_name ): string {
	return $environmental_variable_name . AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}

// =========================================================================================== \
/**
 * Returns true if an environmental variable cache TTL exists and is not expired.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return bool
 */
function ai4seo_is_environmental_variable_cache_available( string $environmental_variable_name ): bool {
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		return false;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 301224226, 'Prevented loop', true );
		return false;
	}

	$ttl_name                    = ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name );
	$all_environmental_variables = ai4seo_read_all_environmental_variables();

	if ( ! isset( $all_environmental_variables[ $ttl_name ] ) || ! is_numeric( $all_environmental_variables[ $ttl_name ] ) ) {
		return false;
	}

	return (int) $all_environmental_variables[ $ttl_name ] > time();
}

// =========================================================================================== \\

/**
 * Invalidate one environmental variable cache by removing its ttl companion value.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return void
 */
function ai4seo_invalidate_environmental_variable_cache( string $environmental_variable_name ): void {
	if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
		return;
	}

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 311224226, 'Prevented loop', true );
		return;
	}

	ai4seo_delete_environmental_variable( ai4seo_get_environmental_variable_ttl_name( $environmental_variable_name ) );
}

// =========================================================================================== \\

/**
 * Invalidate all environmental variable caches by removing every __ttl_time entry.
 *
 * @return void
 */
function ai4seo_invalidate_all_environmental_variable_caches(): void {
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 321224226, 'Prevented loop', true );
		return;
	}

	$all_environmental_variables = ai4seo_read_all_environmental_variables( false );
	$did_change                  = false;

	foreach ( $all_environmental_variables as $this_name => $unused_value ) {
		if ( ! ai4seo_is_environmental_variable_ttl_name( (string) $this_name ) ) {
			continue;
		}

		unset( $all_environmental_variables[ $this_name ] );
		$did_change = true;
	}

	if ( ! $did_change ) {
		return;
	}

	$ai4seo_environmental_variables            = $all_environmental_variables;
	$ai4seo_environmental_variables_are_loaded = true;
	ai4seo_update_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $all_environmental_variables, false );
}

// =========================================================================================== \\

/**
 * Returns environmental variable => action map for cache invalidation.
 *
 * @return array<string, array<int, string>>
 */
function ai4seo_get_environmental_variable_to_action_cache_invalidation_map(): array {
	return array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES => array(
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'delete_post',
			'deleted_post',
			'transition_post_status',
			'switch_theme',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
			'add_attachment',
			'edit_attachment',
			'delete_attachment',
			'switch_theme',
			'profile_update',
			'user_register',
			'deleted_user',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE => array(
			'activated_plugin',
			'deactivated_plugin',
			'save_post',
			'delete_post',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'transition_post_status',
			'set_object_terms',
			'created_term',
			'edited_term',
			'delete_term',
			'switch_theme',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE => array(
			'save_post',
			'delete_post',
			'deleted_post',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE => array(
			'add_attachment',
			'edit_attachment',
			'delete_attachment',
			'updated_post_meta',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE => array(
			'add_attachment',
			'delete_attachment',
		),
		AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE => array(
			'add_attachment',
			'delete_attachment',
			'save_post',
			'deleted_post',
		),
	);
}

// =========================================================================================== \\
/**
 * Register all cache invalidation hooks based on environmental variable map.
 *
 * @return void
 */
function ai4seo_add_invalidate_caches_hooks(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$environmental_variable_to_action_map = ai4seo_get_environmental_variable_to_action_cache_invalidation_map();

	foreach ( $environmental_variable_to_action_map as $this_environmental_variable_name => $this_actions ) {
		foreach ( $this_actions as $this_action ) {
			add_action(
				$this_action,
				function () use ( $this_environmental_variable_name ) {
					ai4seo_invalidate_environmental_variable_cache( $this_environmental_variable_name );
				},
				5,
				20
			);
		}
	}
}


// =========================================================================================== \\

/**
 * Read attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename
 * @return int|false
 */
function ai4seo_get_cached_attachment_id_from_filename( string $normalized_filename ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 331224226, 'Prevented loop', true );
		return false;
	}

	if ( ! ai4seo_is_environmental_variable_cache_available( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE ) ) {
		return false;
	}

	$lookup_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE );

	if ( ! is_array( $lookup_cache ) || ! isset( $lookup_cache[ $normalized_filename ] ) ) {
		return false;
	}

	return (int) $lookup_cache[ $normalized_filename ];
}

// =========================================================================================== \\

/**
 * Store attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename
 * @param int    $attachment_id
 * @return void
 */
function ai4seo_set_cached_attachment_id_from_filename( string $normalized_filename, int $attachment_id ): void {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 341224226, 'Prevented loop', true );
		return;
	}

	$lookup_cache = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE );

	if ( ! is_array( $lookup_cache ) ) {
		$lookup_cache = array();
	}

	$lookup_cache[ $normalized_filename ] = (int) $attachment_id;

	if ( count( $lookup_cache ) > 200 ) {
		$lookup_cache = array_slice( $lookup_cache, -200, null, true );
	}

	ai4seo_update_environmental_variable(
		AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE,
		$lookup_cache,
		true,
		HOUR_IN_SECONDS
	);
}


// endregion
// ___________________________________________________________________________________________.
