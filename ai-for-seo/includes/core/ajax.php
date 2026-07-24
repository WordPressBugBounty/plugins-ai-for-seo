<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region AJAX ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/*
 * Instructions for adding a new AJAX action: see .agent/rules/ajax.md
 */

// =========================================================================================== \\

/**
 * Helper: send clean JSON and log any noise safely.
 *
 * @param array $response The response value.
 * @param mixed $status_code The status code value.
 * @param bool  $send_raw_html_content The send raw html content value.
 */
function ai4seo_send_ajax_success( $response = array(), $status_code = null, $send_raw_html_content = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$noise = '';

	while ( ob_get_level() ) {
		$noise .= @ob_get_clean();
	}

	if ( '' !== $noise ) {
		// Log the first part so we can find the culprit later.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			ai4seo_debug_message( 526144335, 'AJAX noise stripped: ' . substr( $noise, 0, 500 ), true );
		}
	}

	// clean data.
	ai4seo_normalize_ajax_response_data( $response );

	if ( $send_raw_html_content ) {
		ai4seo_echo_wp_kses( $response );
	} else {
		// JSON header + exit.
		wp_send_json_success( $response, $status_code );
	}
}

// =========================================================================================== \\

/**
 * Returns an error as JSON and quit the php execution.
 *
 * @param string $error_message The error message to return.
 * @param int    $error_code The error code to return.
 * @param string $error_headline The error headline value.
 * @param bool   $add_contact_us_link The add contact us link value.
 * @return void
 */
function ai4seo_send_ajax_error( string $error_message = 'Unknown Error', int $error_code = 999, $error_headline = '', $add_contact_us_link = true ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	$clear_buffer = apply_filters( 'ai4seo_clear_buffer_on_error', true );

	// Clean output buffer if active.
	if ( $clear_buffer && ob_get_level() ) {
		// error_log(ob_get_contents()); # for debugging.
		ob_end_clean();
	}

	wp_send_json_error(
		array(
			'success'             => false,
			'error'               => ai4seo_wp_kses( $error_message ),
			'code'                => $error_code,
			'headline'            => ai4seo_wp_kses( $error_headline ),
			'add_contact_us_link' => $add_contact_us_link,
		)
	);
}

// =========================================================================================== \\

function ai4seo_normalize_ajax_response_data( &$data ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	if ( is_array( $data ) ) {
		array_walk_recursive(
			$data,
			function ( &$item ) {
				ai4seo_normalize_ajax_response_item( $item );
			}
		);
	} elseif ( is_string( $data ) ) {
		ai4seo_normalize_ajax_response_item( $data );
	}

	// check if we already have a success and data structure.
	if ( is_array( $data ) && isset( $data['success'] ) && isset( $data['data'] ) ) {
		$data = $data['data'];
	}
}

// =========================================================================================== \\

function ai4seo_normalize_ajax_response_item( &$item ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 967428361, 'Prevented loop', true );
		return;
	}

	if ( ! is_string( $item ) ) {
		return;
	}

	$item = ai4seo_remove_translatepress_tags( $item );
}

// =========================================================================================== \\

/**
 * Print a hidden nonce field into admin pages we render.
 */
function ai4seo_print_ajax_nonce_field(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Only when our menu/page is active.
	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Hidden input. Not part of a form, just a DOM source for JS. No referrer field.
	printf(
		'<input type="hidden" id="' . esc_attr( AI4SEO_GLOBAL_NONCE_IDENTIFIER ) . '" value="%s" />',
		esc_attr( wp_create_nonce( AI4SEO_GLOBAL_NONCE_IDENTIFIER ) )
	);
}

// =========================================================================================== \\

/**
 * Called via AJAX - saves various kind of data
 *
 * @param mixed $additional_upcoming_updates Additional updates to consider (in addition to $_POST).
 * @return void
 */
function ai4seo_save_anything( $additional_upcoming_updates = array() ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// add $_POST to the updates.
	if ( ! is_array( $additional_upcoming_updates ) ) {
		$additional_upcoming_updates = array();
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109824 );
		return;
	}

	if (
		wp_doing_ajax()
		&& isset( $_POST['action'] )
		&& sanitize_key( wp_unslash( $_POST['action'] ) ) === __FUNCTION__
		&& (
			! isset( $_POST['ai4seo_ajax_payload_complete'] )
			|| sanitize_text_field( wp_unslash( $_POST['ai4seo_ajax_payload_complete'] ) ) !== '1'
		)
	) {
		ai4seo_debug_message( 2612181226, 'Save request was incomplete. The POST body may have been truncated by PHP max_input_vars.', true );
		ai4seo_send_ajax_error(
			esc_html__( 'The submitted settings data was incomplete. Please increase the PHP max_input_vars limit and try again.', 'ai-for-seo' ),
			2612181226
		);
		return;
	}

	if ( isset( $_POST['ai4seo_save_anything_payload'] ) ) {
		// The JSON string is decoded before the resulting settings array is normalized and deep-sanitized.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizing before json_decode() can alter valid JSON values.
		$raw_save_anything_payload      = wp_unslash( $_POST['ai4seo_save_anything_payload'] );
		$save_anything_payload_encoding = sanitize_key( wp_unslash( $_POST['ai4seo_save_anything_payload_encoding'] ?? '' ) );

		if ( ! is_string( $raw_save_anything_payload ) ) {
			ai4seo_debug_message( 2712181226, 'Save request payload was not a JSON string.', true );
			ai4seo_send_ajax_error(
				esc_html__( 'The submitted settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ),
				2712181226
			);
			return;
		}

		if ( 'base64_json' === $save_anything_payload_encoding ) {
			$raw_save_anything_payload = base64_decode( $raw_save_anything_payload, true );

			if ( ! is_string( $raw_save_anything_payload ) ) {
				ai4seo_debug_message( 2912181226, 'Save request base64 JSON payload could not be decoded.', true );
				ai4seo_send_ajax_error(
					esc_html__( 'The submitted settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ),
					2912181226
				);
				return;
			}
		}

		$post_data = json_decode( $raw_save_anything_payload, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $post_data ) ) {
			ai4seo_debug_message( 2812181226, 'Save request JSON payload could not be decoded: ' . json_last_error_msg(), true );
			ai4seo_send_ajax_error(
				esc_html__( 'The submitted settings data could not be decoded. Please refresh the page and try again.', 'ai-for-seo' ),
				2812181226
			);
			return;
		}

		$post_data                     = ai4seo_normalize_save_anything_payload_data( $post_data );
		$post_data                     = ai4seo_deep_sanitize( $post_data );
		$raw_all_save_anything_updates = $additional_upcoming_updates + $post_data;
	} elseif ( is_array( $_POST ) === false ) {
		$raw_all_save_anything_updates = $additional_upcoming_updates;
	} else {
		$post_data                     = wp_unslash( $_POST );
		$post_data                     = ai4seo_deep_sanitize( $post_data );
		$raw_all_save_anything_updates = $additional_upcoming_updates + $post_data;
	}

	// check for and sanitize every $raw_all_save_anything_updates variable with AI4SEO_POST_PARAMETER_PREFIX prefix.
	$upcoming_save_anything_updates = array();

	foreach ( $raw_all_save_anything_updates as $ai4seo_this_prefixed_input_id => $ai4seo_this_post_value ) {
		// only consider prefixed input ids from our plugin.
		if ( strpos( $ai4seo_this_prefixed_input_id, AI4SEO_POST_PARAMETER_PREFIX ) === 0 ) {
			// remove prefix and sanitize.
			$ai4seo_this_input_id = ai4seo_get_unprefixed_input_name( $ai4seo_this_prefixed_input_id );

			// handle checkboxes
			// todo: use better indicator like "checkbox-true".
			if ( 'true' === $ai4seo_this_post_value ) {
				$ai4seo_this_post_value = true;
			} elseif ( 'false' === $ai4seo_this_post_value ) {
				$ai4seo_this_post_value = false;
			}

			// handle empty arrays (#ai4seo-empty-array# as string).
			if ( '#ai4seo-empty-array#' === $ai4seo_this_post_value
				|| ( is_array( $ai4seo_this_post_value ) && count( $ai4seo_this_post_value ) === 1 && reset( $ai4seo_this_post_value ) === '#ai4seo-empty-array#' ) ) {
				$ai4seo_this_post_value = array();
			}

			$upcoming_save_anything_updates[ $ai4seo_this_input_id ] = ai4seo_deep_sanitize( $ai4seo_this_post_value );
		}
	}

	// Keep each processor declaration paired with its callback so category additions have one registration point.
	$save_anything_processors = array(
		'save-anything-categories/save-settings.php' => 'ai4seo_process_save_anything_settings',
		'save-anything-categories/save-environmental-variables.php' => 'ai4seo_process_save_anything_environmental_variables',
		'save-anything-categories/save-robhub-environmental-variables.php' => 'ai4seo_process_save_anything_robhub_environmental_variables',
		'save-anything-categories/save-metadata-editor-values.php' => 'ai4seo_process_save_anything_metadata_editor_values',
		'save-anything-categories/save-attachment-attributes-editor-values.php' => 'ai4seo_process_save_anything_attachment_attributes_editor_values',
	);

	// Load every declaration before processing to preserve the previous include sequence for the request.
	foreach ( array_keys( $save_anything_processors ) as $save_anything_processor_file ) {
		require_once ai4seo_get_includes_ajax_process_path( $save_anything_processor_file );
	}

	// Run categories against one shared update bag so normalization remains visible in the established processor order.
	foreach ( $save_anything_processors as $save_anything_processor ) {
		$save_anything_processor_error = $save_anything_processor( $upcoming_save_anything_updates );

		// A null result means that the category either succeeded or had no applicable values.
		if ( ! ( $save_anything_processor_error instanceof WP_Error ) ) {
			continue;
		}

		// Keep JSON response formatting centralized in the existing AJAX error mechanism.
		ai4seo_send_ajax_error(
			$save_anything_processor_error->get_error_message(),
			(int) $save_anything_processor_error->get_error_code()
		);
		return;
	}

	// Report success only after every category has completed without returning an error.
	ai4seo_send_ajax_success();
}
// =========================================================================================== \\

/**
 * Converts JSON-envelope field names into the same shape PHP creates for normal form posts.
 *
 * @param array $post_data Decoded save-anything payload data.
 * @return array Normalized payload data.
 */
function ai4seo_normalize_save_anything_payload_data( array $post_data ): array {
	// Create a fresh output array so decoded JSON keys can be rebuilt without mutating the original payload.
	$normalized_post_data = array();

	// Walk through every submitted field and rebuild PHP-style bracket notation where needed.
	foreach ( $post_data as $parameter_name => $parameter_value ) {
		// Keep normal scalar field names unchanged because they already match the old POST shape.
		if ( ! is_string( $parameter_name ) || strpos( $parameter_name, '[' ) === false ) {
			$normalized_post_data[ $parameter_name ] = $parameter_value;
			continue;
		}

		// Split bracketed field names into path parts so they can be assigned into nested arrays.
		$parameter_name_parts = ai4seo_get_bracketed_parameter_name_parts( $parameter_name );

		// Preserve malformed bracket names as-is so unexpected input does not disappear silently.
		if ( ! $parameter_name_parts ) {
			$normalized_post_data[ $parameter_name ] = $parameter_value;
			continue;
		}

		// Assign the decoded value into the normalized output using the parsed field path.
		ai4seo_assign_bracketed_payload_value( $normalized_post_data, $parameter_name_parts, $parameter_value );
	}

	// Return payload data in the same shape PHP would create from a regular form POST.
	return $normalized_post_data;
}

// =========================================================================================== \\

/**
 * Returns the base and bracket parts of a PHP-style form field name.
 *
 * @param string $parameter_name Parameter name, for example ai4seo_setting[key][].
 * @return array Parameter name parts.
 */
function ai4seo_get_bracketed_parameter_name_parts( string $parameter_name ): array {
	// Accept only a base name followed by one or more bracket parts.
	if ( ! preg_match( '/^([^\[\]]+)((?:\[[^\]]*\])+)$/', $parameter_name, $matches ) ) {
		return array();
	}

	// Extract the individual bracket values while preserving empty [] markers.
	preg_match_all( '/\[([^\]]*)\]/', $matches[2], $bracket_matches );

	// Reject names that matched the outer pattern but did not produce usable bracket parts.
	if ( empty( $bracket_matches[1] ) ) {
		return array();
	}

	// Return a single ordered path containing the base field name and all nested bracket keys.
	return array_merge( array( $matches[1] ), $bracket_matches[1] );
}

// =========================================================================================== \\

/**
 * Assigns a value into a nested array using PHP-style form field name parts.
 *
 * @param array $normalized_post_data Normalized payload data.
 * @param array $parameter_name_parts Parameter name parts.
 * @param mixed $parameter_value Submitted parameter value.
 * @return void
 */
function ai4seo_assign_bracketed_payload_value( array &$normalized_post_data, array $parameter_name_parts, $parameter_value ): void {
	// Determine which path segment is the final assignment target.
	$last_part_index = count( $parameter_name_parts ) - 1;

	// Keep a reference to the current nesting level so assignments modify the output array directly.
	$current_value = &$normalized_post_data;

	// Walk the parsed path and create missing nested arrays as needed.
	foreach ( $parameter_name_parts as $part_index => $parameter_name_part ) {
		// Track whether the current segment is the leaf where the submitted value belongs.
		$is_last_part = ( $part_index === $last_part_index );

		// Handle empty [] path segments as append operations, matching PHP form parsing.
		if ( '' === $parameter_name_part ) {
			// Assign list-style values at the leaf without adding an extra nested level.
			if ( $is_last_part ) {
				if ( is_array( $parameter_value ) ) {
					$current_value = $parameter_value;
				} else {
					$current_value[] = $parameter_value;
				}
				return;
			}

			// Create a new nested array item and descend into it for deeper [] paths.
			$current_value[] = array();
			end( $current_value );
			$last_key      = key( $current_value );
			$current_value = &$current_value[ $last_key ];
			continue;
		}

		// Assign named leaf values directly to their final key.
		if ( $is_last_part ) {
			$current_value[ $parameter_name_part ] = $parameter_value;
			return;
		}

		// Ensure an intermediate named key exists as an array before descending into it.
		if ( ! isset( $current_value[ $parameter_name_part ] ) || ! is_array( $current_value[ $parameter_name_part ] ) ) {
			$current_value[ $parameter_name_part ] = array();
		}

		// Move the reference down one level for the next path segment.
		$current_value = &$current_value[ $parameter_name_part ];
	}
}

// =========================================================================================== \\

/**
 * Called via AJAX - stop bulk generation
 *
 * @return void
 */
function ai4seo_stop_bulk_generation() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109832 );
		return;
	}

	// stop bulk generation.
	ai4seo_update_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ] );

	// send success.
	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - clear all pending bulk generation queue entries
 *
 * @return void
 */
function ai4seo_clear_bulk_generation_queue() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109833 );
		return;
	}

	$pending_metadata_post_ids             = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME );
	$pending_attachment_attribute_post_ids = ai4seo_get_post_ids_from_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	// Clear pending queue entries only. Processing, failed, generated and fully covered entries stay untouched.
	$cleared_pending_metadata              = ai4seo_update_option( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, array() );
	$cleared_pending_attachment_attributes = ai4seo_update_option( AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, array() );

	if ( ! $cleared_pending_metadata || ! $cleared_pending_attachment_attributes ) {
		ai4seo_send_ajax_error( esc_html__( 'The queue could not be cleared.', 'ai-for-seo' ), 1010062601 );
		return;
	}

	// Force-overwrite markers are only removed for entries that were still Pending.
	ai4seo_remove_post_ids_from_option( AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME, $pending_metadata_post_ids );
	ai4seo_remove_post_ids_from_option( AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $pending_attachment_attribute_post_ids );

	// send success.
	ai4seo_send_ajax_success(
		array(
			'queue_count' => ai4seo_get_bulk_generation_queue_count(),
		)
	);
}

// =========================================================================================== \\

/**
 * Called via AJAX - apply a manual bulk generation queue action.
 *
 * @return void
 */
function ai4seo_apply_bulk_generation_queue_action() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 3106062600 );
		return;
	}

	$bulk_generation_queue_action = sanitize_key( wp_unslash( $_POST['bulk_generation_queue_action'] ?? '' ) );
	$context                      = sanitize_key( wp_unslash( $_POST['context'] ?? '' ) );
	$active_status_filter         = sanitize_key( wp_unslash( $_POST['active_status_filter'] ?? 'all' ) );
	// Normalize selected IDs before validation so repeated native/custom checkboxes cannot multiply writes.
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['post_ids'] ?? array() ) ) ) ) );

	if ( ! ai4seo_is_bulk_generation_queue_action( $bulk_generation_queue_action ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action is invalid.', 'ai-for-seo' ), 3106062601 );
		return;
	}

	if ( ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action context is invalid.', 'ai-for-seo' ), 3106062602 );
		return;
	}

	// Mirror the custom list UI rules server-side, especially the Hide/Show action swap for the hidden status filter.
	if ( ! ai4seo_is_bulk_generation_queue_action_available_for_surface( $bulk_generation_queue_action, 'custom', $active_status_filter, $context ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action is not available for the current list filter.', 'ai-for-seo' ), 3106062604 );
		return;
	}

	if ( ai4seo_is_bulk_generation_queue_action_modal_required( $bulk_generation_queue_action ) ) {
		ai4seo_send_ajax_error( esc_html__( 'This bulk action needs the modal before it can be submitted.', 'ai-for-seo' ), 3106062605 );
		return;
	}

	if ( ! $post_ids ) {
		ai4seo_send_ajax_error( esc_html__( 'Please select at least one entry.', 'ai-for-seo' ), 3106062603 );
		return;
	}

	$result = ai4seo_process_bulk_generation_queue_action(
		$bulk_generation_queue_action,
		$post_ids,
		$context
	);

	// send success.
	ai4seo_send_ajax_success( $result );
}

// =========================================================================================== \\

/**
 * Called via AJAX - apply bulk custom instructions after the modal was submitted.
 *
 * @return void
 */
function ai4seo_apply_bulk_custom_instructions_action() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1407062600 );
		return;
	}

	// Read the modal payload directly because this action saves postmeta, not settings via save-anything.
	$bulk_generation_queue_action = sanitize_key( wp_unslash( $_POST['bulk_generation_queue_action'] ?? '' ) );
	$context                      = sanitize_key( wp_unslash( $_POST['context'] ?? '' ) );
	// Normalize selected IDs before validation so repeated native/custom checkboxes cannot multiply writes.
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['post_ids'] ?? array() ) ) ) ) );

	// The modal endpoint only accepts the modal-required action, while other bulk actions stay on the queue endpoint.
	if ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS !== $bulk_generation_queue_action ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action is invalid.', 'ai-for-seo' ), 1407062601 );
		return;
	}

	if ( ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action context is invalid.', 'ai-for-seo' ), 1407062602 );
		return;
	}

	if ( ! $post_ids ) {
		ai4seo_send_ajax_error( esc_html__( 'Please select at least one entry.', 'ai-for-seo' ), 1407062603 );
		return;
	}

	if ( ! array_key_exists( 'custom_instructions', $_POST ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Custom instructions were not submitted.', 'ai-for-seo' ), 1407062604 );
		return;
	}

	if ( is_array( $_POST['custom_instructions'] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Custom instructions must be submitted as text.', 'ai-for-seo' ), 1407062605 );
		return;
	}

	// Keep empty strings valid because they intentionally clear existing entry-level instructions.
	$custom_instructions = sanitize_textarea_field( wp_unslash( $_POST['custom_instructions'] ) );

	// Delegate all postmeta writes to the shared processor so AJAX and future callers receive the same result shape.
	$result = ai4seo_process_bulk_custom_instructions_action(
		$post_ids,
		$context,
		$custom_instructions
	);

	// send success.
	ai4seo_send_ajax_success( $result );
}

// =========================================================================================== \\

/**
 * Called via AJAX - returns exact content type status filters after the list rows have loaded.
 *
 * @return void
 */
function ai4seo_get_content_type_status_filters() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1806062600 );
		return;
	}

	if ( ! ai4seo_can_manage_this_plugin() ) {
		ai4seo_send_ajax_error( esc_html__( 'You are not allowed to manage this plugin.', 'ai-for-seo' ), 1806062601 );
		return;
	}

	require_once AI4SEO_PLUGIN_DIR_PATH . 'includes/pages/content_types/list-filters.php';

	$content_context = ai4seo_normalize_content_type_list_context( sanitize_key( wp_unslash( $_REQUEST['content_context'] ?? '' ) ) );
	$filter_text     = sanitize_text_field( wp_unslash( $_REQUEST['filter_text'] ?? '' ) );
	$filter_status   = sanitize_key( wp_unslash( $_REQUEST['filter_status'] ?? 'all' ) );

	// Match the initial list renderer's sort defaults before building hydrated status links.
	$sort_args      = ai4seo_normalize_content_type_sort_args(
		sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? 'id' ) ),
		sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? 'desc' ) )
	);
	$orderby        = $sort_args['orderby'];
	$order          = $sort_args['order'];
	$status_options = ai4seo_get_content_type_status_options();
	// Status-filter hydration only preserves known routing fields, but sanitize the submitted map before filtering it.
	$raw_hidden_fields                      = ( isset( $_REQUEST['hidden_fields'] ) && is_array( $_REQUEST['hidden_fields'] ) )
		? array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['hidden_fields'] ) )
		: array();
	$bulk_generation_new_or_existing_filter = ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER );
	$bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );

	if ( ! is_array( $raw_hidden_fields ) ) {
		$raw_hidden_fields = array();
	}

	// Hydrated status links may preserve only explicit routing fields, such as WPML's admin language parameter.
	$hidden_fields = ai4seo_get_content_type_status_filter_hydration_hidden_fields( $raw_hidden_fields );

	// Media lists derive all scope from attachment settings so client data cannot broaden the count query.
	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $content_context ) {
		if ( '' !== $filter_text ) {
			ai4seo_send_ajax_error( esc_html__( 'Deferred media status filters are unavailable for filename searches.', 'ai-for-seo' ), 1806062602, '', false );
			return;
		}

		$approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
		$hydration_result                     = ai4seo_get_content_type_status_filter_hydration_result(
			array(
				'content_context'              => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
				'post_types'                   => ai4seo_get_supported_attachment_post_types( true ),
				'post_status'                  => array( 'publish', 'future', 'inherit' ),
				'post_mime_types'              => ai4seo_get_allowed_attachment_mime_types(),
				'author_not_in'                => ai4seo_get_disabled_attachment_post_author_ids(),
				'disabled_wpml_language_codes' => ai4seo_get_disabled_attachment_attributes_wpml_language_codes(),
				'filter_text'                  => '',
				'filter_status'                => $filter_status,
				'orderby'                      => $orderby,
				'order'                        => $order,
				'status_options'               => $status_options,
				'form_action_url'              => ai4seo_get_subpage_url( 'media', array( 'ai4seo_page' => 1 ) ),
				'hidden_fields'                => $hidden_fields,
				'nonce_action'                 => 'ai4seo_content_type_filter_form',
				'nonce_name'                   => 'ai4seo_content_type_filter_nonce',
				'is_bulk_generation_activated' => ai4seo_is_bulk_generation_enabled( 'attachment' ),
				'should_auto_queue_bulk_generation_entries' => ai4seo_should_auto_queue_bulk_generation_entries(),
				'has_enough_credits'           => ( ai4seo_robhub_api()->get_credits_balance() >= $approximate_cost_per_attachment_post ),
				'new_or_existing_filter'       => $bulk_generation_new_or_existing_filter,
				'new_or_existing_filter_reference_timestamp' => $bulk_generation_new_or_existing_filter_reference_timestamp,
			)
		);

		ai4seo_send_ajax_success( $hydration_result );
		return;
	}

	$post_type            = sanitize_key( wp_unslash( $_REQUEST['post_type'] ?? '' ) );
	$supported_post_types = ai4seo_get_supported_post_types();

	if ( '' === $post_type || ! in_array( $post_type, $supported_post_types, true ) ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected post type is invalid.', 'ai-for-seo' ), 1806062603, '', false );
		return;
	}

	// Metadata lists count the requested supported post type only, with disabled authors applied server-side.
	$metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
	$hydration_result                = ai4seo_get_content_type_status_filter_hydration_result(
		array(
			'content_context'                            => AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
			'post_type'                                  => $post_type,
			'post_status'                                => array( 'publish', 'future' ),
			'author_not_in'                              => ai4seo_get_disabled_post_author_ids(),
			'disabled_wpml_language_codes'               => ai4seo_get_disabled_metadata_wpml_language_codes(),
			'filter_text'                                => $filter_text,
			'filter_status'                              => $filter_status,
			'orderby'                                    => $orderby,
			'order'                                      => $order,
			'status_options'                             => $status_options,
			'form_action_url'                            => ai4seo_get_post_type_page_url( $post_type, 1 ),
			'hidden_fields'                              => $hidden_fields,
			'nonce_action'                               => 'ai4seo_content_type_filter_form',
			'nonce_name'                                 => 'ai4seo_content_type_filter_nonce',
			'is_bulk_generation_activated'               => ai4seo_is_bulk_generation_enabled( $post_type ),
			'should_auto_queue_bulk_generation_entries'  => ai4seo_should_auto_queue_bulk_generation_entries(),
			'has_enough_credits'                         => ( ai4seo_robhub_api()->get_credits_balance() >= $metadata_credits_costs_per_post ),
			'new_or_existing_filter'                     => $bulk_generation_new_or_existing_filter,
			'new_or_existing_filter_reference_timestamp' => $bulk_generation_new_or_existing_filter_reference_timestamp,
		)
	);

	ai4seo_send_ajax_success( $hydration_result );
}

// =========================================================================================== \\

/**
 * Called via AJAX - retry all failed attachment attributes
 *
 * @return void
 */
function ai4seo_retry_all_failed_attachment_attributes() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109834 );
		return;
	}

	// Reset all failed attachment attributes by clearing the option.
	ai4seo_delete_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );

	// Refresh the generation status summary after the already-authorized retry mutation.
	ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();

	// send success.
	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - retry all failed metadata for a specific post type
 *
 * @return void
 */
function ai4seo_retry_all_failed_metadata() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109825 );
		return;
	}

	// Get the post type from the request.
	$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ?? '' ) );

	if ( empty( $post_type ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Post type is required', 'ai-for-seo' ), 12109825 );
		return;
	}

	// Remove all failed post IDs for this post type.
	ai4seo_remove_all_post_ids_by_post_type_and_generation_status( $post_type, AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME );

	// Refresh the generation status summary after the already-authorized retry mutation.
	ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();

	// send success.
	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - refresh dashboard statistics by running the performance analysis
 *
 * @return void
 */
function ai4seo_refresh_dashboard_statistics() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 44129000 );
		return;
	}

	if ( ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
		ai4seo_send_ajax_error(
			esc_html__( 'Heavy database operations are currently disabled. Enable them in Settings to refresh statistics.', 'ai-for-seo' ),
			44129001
		);
		return;
	}

	ai4seo_analyze_plugin_performance( false, true );

	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - refresh the RobHub account data manually
 *
 * @return void
 */
function ai4seo_refresh_robhub_account() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109826 );
		return;
	}

	$check_for_purchase = (bool) sanitize_text_field( wp_unslash( $_POST['check_for_purchase'] ?? false ) );

	ai4seo_robhub_api()->set_auth_data_locked( false );
	ai4seo_robhub_api()->reset_last_account_sync();
	$api_response = ai4seo_sync_robhub_account( 'manual_refresh' );

	if ( ! ai4seo_robhub_api()->was_call_successful( $api_response ) ) {
		ai4seo_robhub_api()->set_auth_data_locked( true );
		ai4seo_robhub_api()->reset_last_account_sync();
		$api_response_error_message = $api_response['message'] ?? esc_html__( 'Please try to reconnect account', 'ai-for-seo' );

		ai4seo_send_ajax_error(
			sprintf(
				/* translators: %1$s - error message */
				esc_html__( 'Could not refresh account data: %s', 'ai-for-seo' ),
				$api_response_error_message
			),
			44129002
		);
	}

	$ajax_response = array();

	if ( $check_for_purchase ) {
		$has_purchased_something = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );
		$credits_balance         = ai4seo_robhub_api()->get_credits_balance();

		$ajax_response['is_purchase_ready'] = ( $has_purchased_something && $credits_balance > 400 );
	}

	ai4seo_send_ajax_success( $ajax_response );
}


// =========================================================================================== \\

/**
 * Called via AJAX - disable payg
 *
 * @return void
 */
function ai4seo_disable_payg() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 421217324 );
		return;
	}

	// disable payg setting.
	ai4seo_update_setting( AI4SEO_SETTING_PAYG_ENABLED, false );

	// send new settings to robhub.
	$sent_pay_as_you_go_settings_response = ai4seo_send_pay_as_you_go_settings();

	if ( false === $sent_pay_as_you_go_settings_response ) {
		ai4seo_send_ajax_error( esc_html__( 'Could not send pay-as-you-go settings to RobHub', 'ai-for-seo' ), 421217325 );
		wp_die();
	} elseif ( is_string( $sent_pay_as_you_go_settings_response ) ) {
		ai4seo_send_ajax_error( $sent_pay_as_you_go_settings_response, 431217325 );
		wp_die();
	}

	// send success.
	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

function ai4seo_init_purchase() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109827 );
		return;
	}

	// check stripe_price_id.
	if ( ! isset( $_POST['stripe_price_id'] ) || ! is_string( $_POST['stripe_price_id'] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid stripe_price_id', 'ai-for-seo' ), 551818325 );
		wp_die();
	}

	$stripe_price_id = sanitize_text_field( wp_unslash( $_POST['stripe_price_id'] ) );

	// build redirect url.
	$redirect_url = ai4seo_get_subpage_url( 'dashboard' ) . '&ai4seo-just-purchased=true';

	// call robhub api endpoint "payg-settings" with current payg settings.
	$robhub_endpoint = 'client/init-purchase';

	$endpoint_parameter                    = array();
	$endpoint_parameter['stripe_price_id'] = $stripe_price_id;
	$endpoint_parameter['redirect_url']    = $redirect_url;

	$response = ai4seo_robhub_api()->call( $robhub_endpoint, $endpoint_parameter );

	// check response.
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_debug_message( 561818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 561818325 );
	}

	if ( ! isset( $response['data']['purchase_url'] ) || ! $response['data']['purchase_url'] ) {
		ai4seo_debug_message( 581818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 581818325 );
	}

	// url decode.
	$purchase_url = urldecode( $response['data']['purchase_url'] );

	// html_entity_decode.
	$purchase_url = html_entity_decode( $purchase_url );

	// validate.
	if ( ! filter_var( $purchase_url, FILTER_VALIDATE_URL ) ) {
		ai4seo_debug_message( 591818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 591818325 );
	}

	// we assume the purchase process is started now, so we better start syncing the account in case a payment is made.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time() );

	ai4seo_send_ajax_success( array( 'purchase_url' => $purchase_url ) );
}

// =========================================================================================== \\

/**
 * Called via AJAX - track subscription pricing CTA clicks
 *
 * Updates the JUST_PURCHASED environmental variable so the account sync can run immediately
 * while the user reviews pricing options.
 *
 * @return void
 */
function ai4seo_track_subscription_pricing_visit() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109835 );
		return;
	}

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time() );

	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - submit plugin deactivation feedback.
 *
 * @return void
 */
function ai4seo_submit_feedback() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// CHECK PARAMETER
	// message.
	$feedback_message = '';

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109828 );
		return;
	}

	if ( isset( $_POST['feedback_message'] ) && is_string( $_POST['feedback_message'] ) ) {
		$feedback_message = sanitize_textarea_field( wp_unslash( $_POST['feedback_message'] ) );
	}

	// cap at 2000 characters.
	if ( strlen( $feedback_message ) > 2000 ) {
		$feedback_message = ai4seo_mb_substr( $feedback_message, 0, 2000 );
	}

	// flow.
	$feedback_flow = '';

	if ( isset( $_POST['feedback_flow'] ) && is_string( $_POST['feedback_flow'] ) ) {
		$feedback_flow = sanitize_key( wp_unslash( $_POST['feedback_flow'] ) );
	}

	if ( ! in_array( $feedback_flow, array( 'deactivate', 'claim_offer' ), true ) ) {
		$feedback_flow = 'deactivate';
	}

	// reason.
	$feedback_reason = '';

	if ( isset( $_POST['feedback_reason'] ) && is_string( $_POST['feedback_reason'] ) ) {
		$feedback_reason = sanitize_key( wp_unslash( $_POST['feedback_reason'] ) );
	}

	// if flow is deactivate, perform deactivate pre robhub call.
	$was_plugin_deactivated = false;

	if ( 'deactivate' === $feedback_flow ) {
		$was_plugin_deactivated = ai4seo_deactivate_plugin();
	}

	// SEND FEEDBACK TO ROBHUB.
	$response = ai4seo_robhub_api()->perform_client_feedback_call( $feedback_reason, $feedback_message, $feedback_flow );

	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_debug_message( 421426226, 'Failed to submit feedback to RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not submit feedback. Please try again.', 'ai-for-seo' ), 55120626 );
	}

	// HANDLE RARE CASE WHERE PLUGIN DEACTIVATION FAILED.
	if ( 'deactivate' === $feedback_flow && ! $was_plugin_deactivated ) {
		ai4seo_debug_message( 171426226, 'Plugin deactivation failed after feedback submission.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not deactivate the plugin. Please refresh the page and try again.', 'ai-for-seo' ), 56120626 );
	}

	// ON CLAIM OFFER -> sync account.
	if ( 'claim_offer' === $feedback_flow ) {
		ai4seo_sync_robhub_account( 'claimed_feedback_offer' );
	}

	ai4seo_send_ajax_success( array( 'was_deactivated' => $was_plugin_deactivated ) );
}

// =========================================================================================== \\

/**
 * Called via AJAX - requests lost licence data to be sent via email
 *
 * @return void
 */
function ai4seo_request_lost_licence_data() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109829 );
		return;
	}

	// check stripe_email.
	if ( ! isset( $_POST['stripe_email'] ) || ! is_string( $_POST['stripe_email'] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid email address', 'ai-for-seo' ), 551819325 );
		wp_die();
	}

	$stripe_email = sanitize_email( wp_unslash( $_POST['stripe_email'] ) );

	// Validate email format.
	if ( ! filter_var( $stripe_email, FILTER_VALIDATE_EMAIL ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid email address', 'ai-for-seo' ), 561819325 );
		wp_die();
	}

	$response = ai4seo_robhub_api()->perform_lost_licence_call( $stripe_email );

	// RobHub returns this code while its lost-license endpoint is throttled.
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) && isset( $response['code'] ) && 521561224 === $response['code'] ) {
		// Keep the API throttle code unchanged while localizing the visible retry interval.
		ai4seo_send_ajax_error(
			sprintf(
				/* translators: %s: Number of seconds. */
				esc_html__( 'You can only request your licence data once every %s seconds. Please wait a moment and try again.', 'ai-for-seo' ),
				esc_html( ai4seo_format_number_i18n( 60 ) )
			),
			521561224
		);
		wp_die();
	}

	// Always treat as success regardless of API response (as per requirements)
	// Even if the API responds with an error (e.g. email not found), treat it as a success.
	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Reads generated-data reset post types from the AJAX request.
 *
 * @return array Selected post types.
 */
function ai4seo_read_generated_data_reset_post_types_from_request(): array {
	// Recheck the global AJAX nonce before reading reset targets from a protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 784322900 );
		return array();
	}

	if ( ! isset( $_POST['ai4seo_reset_metadata_post_types'] ) ) {
		return array();
	}

	// Reset post types can arrive as JSON, so keep the transport intact until each decoded value is sanitized below.
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The decoded post type values are sanitized before use.
	$raw_post_types = wp_unslash( $_POST['ai4seo_reset_metadata_post_types'] );

	if ( is_string( $raw_post_types ) ) {
		$decoded_post_types = json_decode( $raw_post_types, true );

		if ( is_array( $decoded_post_types ) ) {
			$raw_post_types = $decoded_post_types;
		} else {
			$raw_post_types = array( $raw_post_types );
		}
	}

	if ( ! is_array( $raw_post_types ) ) {
		return array();
	}

	$post_types = array();

	foreach ( $raw_post_types as $this_raw_post_type ) {
		if ( ! is_scalar( $this_raw_post_type ) ) {
			continue;
		}

		$this_post_type = sanitize_key( (string) $this_raw_post_type );

		if ( ! $this_post_type ) {
			continue;
		}

		$post_types[] = $this_post_type;
	}

	return array_values( array_unique( $post_types ) );
}

// =========================================================================================== \\

/**
 * Reads generated-data post IDs for the selected post types.
 *
 * @param array $post_types Selected post types.
 * @return array Generated-data post IDs.
 */
function ai4seo_read_generated_data_post_ids_by_post_types( array $post_types ): array {
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return array();
	}

	$post_ids                    = array();
	$generation_status_summary   = ai4seo_read_generation_status_summary( false, true );
	$generated_data_option_names = array(
		AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	foreach ( $generated_data_option_names as $this_generated_data_option_name ) {
		if ( ! isset( $generation_status_summary[ $this_generated_data_option_name ] )
			|| ! is_array( $generation_status_summary[ $this_generated_data_option_name ] ) ) {
			continue;
		}

		foreach ( $post_types as $this_post_type ) {
			$this_summary_entry = $generation_status_summary[ $this_generated_data_option_name ][ $this_post_type ] ?? array();

			if ( ! is_array( $this_summary_entry )
				|| ! isset( $this_summary_entry['post_ids'] )
				|| ! is_array( $this_summary_entry['post_ids'] ) ) {
				continue;
			}

			$post_ids = array_merge( $post_ids, $this_summary_entry['post_ids'] );
		}
	}

	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	return $post_ids;
}

// =========================================================================================== \\

/**
 * Reads selected post IDs that currently have a specific postmeta key.
 *
 * @param array  $post_ids Post IDs.
 * @param string $meta_key Postmeta key.
 * @return array Post IDs that have the postmeta key.
 */
function ai4seo_read_post_ids_with_postmeta_key( array $post_ids, string $meta_key ): array {
	global $wpdb;

	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$meta_key = sanitize_key( $meta_key );

	if ( ! $post_ids || ! $meta_key ) {
		return array();
	}

	$post_ids_with_meta_key = array();
	$database_chunk_size    = ai4seo_get_database_chunk_size();
	$post_ids_chunks        = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_post_ids_placeholders  = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );
		$this_post_ids_with_meta_key = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                AND post_id IN ({$this_post_ids_placeholders})",
				...array_merge( array( $meta_key ), $this_post_ids_chunk )
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321709, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_post_ids_with_meta_key ) {
			continue;
		}

		$post_ids_with_meta_key = array_merge( $post_ids_with_meta_key, $this_post_ids_with_meta_key );
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $post_ids_with_meta_key ) ) ) );
}

// =========================================================================================== \\

/**
 * Deletes a postmeta key for specific post IDs.
 *
 * @param array  $post_ids Post IDs.
 * @param string $meta_key Postmeta key.
 * @return bool True on success.
 */
function ai4seo_delete_postmeta_for_post_ids_and_meta_key( array $post_ids, string $meta_key ): bool {
	global $wpdb;

	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$meta_key = sanitize_key( $meta_key );

	if ( ! $post_ids || ! $meta_key ) {
		return true;
	}

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$post_ids_chunks     = array_chunk( $post_ids, $database_chunk_size );

	foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
		if ( ! $this_post_ids_chunk ) {
			continue;
		}

		$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                AND post_id IN ({$this_post_ids_placeholders})",
				...array_merge( array( $meta_key ), $this_post_ids_chunk )
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321707, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		foreach ( $this_post_ids_chunk as $this_post_id ) {
			wp_cache_delete( $this_post_id, 'post_meta' );
		}
	}

	return true;
}

// =========================================================================================== \\

/**
 * Removes post IDs from one generation status summary option and keeps totals in sync.
 *
 * @param array  $post_ids Post IDs to remove.
 * @param string $option_name Generation status option name.
 * @return bool True on success.
 */
function ai4seo_remove_post_ids_from_generation_status_summary_option( array $post_ids, string $option_name ): bool {
	$post_ids    = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$option_name = sanitize_key( $option_name );

	if ( ! $post_ids || ! $option_name ) {
		return true;
	}

	$generation_status_summary = ai4seo_read_generation_status_summary( false, true );

	if ( ! $generation_status_summary
		|| ! isset( $generation_status_summary[ $option_name ] )
		|| ! is_array( $generation_status_summary[ $option_name ] ) ) {
		return true;
	}

	$post_id_lookup = array_flip( $post_ids );

	foreach ( $generation_status_summary[ $option_name ] as $this_post_type => $this_summary_entry ) {
		if ( ! is_array( $this_summary_entry )
			|| ! isset( $this_summary_entry['post_ids'] )
			|| ! is_array( $this_summary_entry['post_ids'] ) ) {
			continue;
		}

		$this_entry_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $this_summary_entry['post_ids'] ) ) ) );
		$this_entry_post_ids = array_values(
			array_filter(
				$this_entry_post_ids,
				function ( $post_id ) use ( $post_id_lookup ) {
					return ! isset( $post_id_lookup[ $post_id ] );
				}
			)
		);

		if ( ! $this_entry_post_ids ) {
			unset( $generation_status_summary[ $option_name ][ $this_post_type ] );
			continue;
		}

		$generation_status_summary[ $option_name ][ $this_post_type ]['post_ids'] = $this_entry_post_ids;
		$generation_status_summary[ $option_name ][ $this_post_type ]['total']    = count( $this_entry_post_ids );
	}

	if ( ! $generation_status_summary[ $option_name ] ) {
		$generation_status_summary[ $option_name ] = array();
	}

	ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );
	ai4seo_update_option(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		ai4seo_get_generation_status_summary_totals( $generation_status_summary )
	);

	return true;
}

// =========================================================================================== \\

/**
 * Removes post IDs from the full generation status summary and keeps totals in sync.
 *
 * @param array $post_ids Post IDs to remove.
 * @param array $post_types Optional post types to remove when only totals are available.
 * @return bool True on success.
 */
function ai4seo_remove_post_ids_from_generation_status_summary( array $post_ids, array $post_types = array() ): bool {
	$post_ids   = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_ids ) {
		if ( ! $post_types ) {
			return true;
		}

		$generation_status_summary = ai4seo_read_generation_status_summary( false, true );

		if ( $generation_status_summary ) {
			foreach ( $generation_status_summary as $this_option_name => $this_post_type_entries ) {
				if ( ! is_array( $this_post_type_entries ) ) {
					continue;
				}

				foreach ( $post_types as $this_post_type ) {
					if ( isset( $generation_status_summary[ $this_option_name ][ $this_post_type ] ) ) {
						unset( $generation_status_summary[ $this_option_name ][ $this_post_type ] );
					}
				}
			}

			ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );
			ai4seo_update_option(
				AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
				ai4seo_get_generation_status_summary_totals( $generation_status_summary )
			);

			return true;
		}

		$generation_status_summary_totals = ai4seo_read_generation_status_summary( true, true );

		foreach ( $generation_status_summary_totals as $this_option_name => $this_post_type_entries ) {
			if ( ! is_array( $this_post_type_entries ) ) {
				continue;
			}

			foreach ( $post_types as $this_post_type ) {
				if ( isset( $generation_status_summary_totals[ $this_option_name ][ $this_post_type ] ) ) {
					unset( $generation_status_summary_totals[ $this_option_name ][ $this_post_type ] );
				}
			}
		}

		ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, $generation_status_summary_totals );

		return true;
	}

	$post_id_lookup            = array_flip( $post_ids );
	$generation_status_summary = ai4seo_read_generation_status_summary( false, true );

	if ( ! $generation_status_summary ) {
		if ( $post_types ) {
			$generation_status_summary_totals = ai4seo_read_generation_status_summary( true, true );

			foreach ( $generation_status_summary_totals as $this_option_name => $this_post_type_entries ) {
				if ( ! is_array( $this_post_type_entries ) ) {
					continue;
				}

				foreach ( $post_types as $this_post_type ) {
					if ( isset( $generation_status_summary_totals[ $this_option_name ][ $this_post_type ] ) ) {
						unset( $generation_status_summary_totals[ $this_option_name ][ $this_post_type ] );
					}
				}
			}

			ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME, $generation_status_summary_totals );
		}

		return true;
	}

	foreach ( $generation_status_summary as $this_option_name => $this_post_type_entries ) {
		if ( ! is_array( $this_post_type_entries ) ) {
			continue;
		}

		foreach ( $this_post_type_entries as $this_post_type => $this_summary_entry ) {
			if ( ! is_array( $this_summary_entry )
				|| ! isset( $this_summary_entry['post_ids'] )
				|| ! is_array( $this_summary_entry['post_ids'] ) ) {
				continue;
			}

			$this_entry_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $this_summary_entry['post_ids'] ) ) ) );
			$this_entry_post_ids = array_values(
				array_filter(
					$this_entry_post_ids,
					function ( $post_id ) use ( $post_id_lookup ) {
						return ! isset( $post_id_lookup[ $post_id ] );
					}
				)
			);

			if ( ! $this_entry_post_ids ) {
				unset( $generation_status_summary[ $this_option_name ][ $this_post_type ] );
				continue;
			}

			$generation_status_summary[ $this_option_name ][ $this_post_type ]['post_ids'] = $this_entry_post_ids;
			$generation_status_summary[ $this_option_name ][ $this_post_type ]['total']    = count( $this_entry_post_ids );
		}

		if ( ! $generation_status_summary[ $this_option_name ] ) {
			$generation_status_summary[ $this_option_name ] = array();
		}
	}

	ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );
	ai4seo_update_option(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		ai4seo_get_generation_status_summary_totals( $generation_status_summary )
	);

	return true;
}

// =========================================================================================== \\

/**
 * Resets generated data for specific post IDs and selected post types.
 *
 * @param array $post_ids Generated-data post IDs.
 * @param array $post_types Selected post types.
 * @return bool True on success.
 */
function ai4seo_reset_generated_data_for_post_ids( array $post_ids, array $post_types ): bool {
	$post_ids   = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return false;
	}

	if ( ! $post_ids ) {
		ai4seo_remove_post_ids_from_generation_status_summary( array(), $post_types );
		return true;
	}

	if ( ! ai4seo_delete_legacy_active_metadata_for_post_ids( $post_ids ) ) {
		return false;
	}

	$postmeta_keys_to_delete = array(
		AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY,
		AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY,
	);

	foreach ( $postmeta_keys_to_delete as $this_postmeta_key_to_delete ) {
		if ( ! ai4seo_delete_postmeta_for_post_ids_and_meta_key( $post_ids, $this_postmeta_key_to_delete ) ) {
			return false;
		}
	}

	foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option ) {
		ai4seo_remove_post_ids_from_option( $ai4seo_option, $post_ids );
	}

	ai4seo_remove_post_ids_from_generation_status_summary( $post_ids, $post_types );

	return true;
}

// =========================================================================================== \\

/**
 * Resets generated data for the selected post types.
 *
 * @param array $post_types Selected post types.
 * @return bool True on success.
 */
function ai4seo_reset_generated_data_for_post_types( array $post_types ): bool {
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return false;
	}

	$post_ids = ai4seo_read_generated_data_post_ids_by_post_types( $post_types );

	return ai4seo_reset_generated_data_for_post_ids( $post_ids, $post_types );
}

// =========================================================================================== \\

/**
 * Called via AJAX - resets selected plugin data
 *
 * @return void
 */
function ai4seo_reset_plugin_data() {
	global $wpdb;
	global $ai4seo_settings;
	global $ai4seo_environmental_variables;
	global $ai4seo_environmental_variables_are_loaded;

	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109830 );
		return;
	}

	$do_reset_metadata                       = ( isset( $_POST['ai4seo_reset_metadata'] ) && 'true' === $_POST['ai4seo_reset_metadata'] );
	$has_reset_metadata_post_types_parameter = array_key_exists( 'ai4seo_reset_metadata_post_types', $_POST );
	$reset_metadata_is_full_reset            = false;
	$reset_metadata_post_types               = array();
	$reset_metadata_post_ids                 = array();

	if ( $do_reset_metadata ) {
		if ( isset( $_POST['ai4seo_reset_metadata_is_full_reset'] ) ) {
			$raw_reset_metadata_is_full_reset = sanitize_text_field( wp_unslash( $_POST['ai4seo_reset_metadata_is_full_reset'] ) );

			if ( is_scalar( $raw_reset_metadata_is_full_reset ) ) {
				$reset_metadata_is_full_reset = filter_var( (string) $raw_reset_metadata_is_full_reset, FILTER_VALIDATE_BOOLEAN );
			}
		}

		$reset_metadata_post_types = ai4seo_read_generated_data_reset_post_types_from_request();

		if ( ! $reset_metadata_is_full_reset && $has_reset_metadata_post_types_parameter && ! $reset_metadata_post_types ) {
			ai4seo_send_ajax_error( esc_html__( 'Please select at least one entry type.', 'ai-for-seo' ), 784322907 );
			wp_die();
		}

		if ( ! $reset_metadata_is_full_reset && $reset_metadata_post_types ) {
			$reset_metadata_post_ids = ai4seo_read_generated_data_post_ids_by_post_types( $reset_metadata_post_types );
		} else {
			$reset_metadata_post_types = array();
		}
	}

	// remove caches.
	if ( isset( $_POST['ai4seo_reset_cache'] ) && 'true' === $_POST['ai4seo_reset_cache'] ) {
		// remove wp_options named robhub_api_lock_*.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_robhub_api_lock_%'" );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321690, 'Database error: ' . $wpdb->last_error, true );
			ai4seo_send_ajax_error( esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ), 784322901 );
			wp_die();
		}

		// remove transient ai4seo_last_contact_form_submit_timestamp.
		delete_transient( 'ai4seo_last_contact_form_submit_timestamp' );

		// remove very wp_options named inside AI4SEO_ALL_POST_ID_OPTIONS-Array.
		foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option ) {
			ai4seo_delete_option( $ai4seo_option );
		}

		// Delete both summary options so reset leaves neither the full post-id data nor its totals companion behind.
		ai4seo_delete_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );
		ai4seo_delete_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME );

		// delete wp_option AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME.
		ai4seo_delete_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME );

		// invalidate all environmental variable caches.
		ai4seo_invalidate_all_environmental_variable_caches();

		// remove all postmeta entries with meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
				AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY,
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321691, 'Database error: ' . $wpdb->last_error, true );
			ai4seo_send_ajax_error( esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ), 784322902 );
			wp_die();
		}

		// The related-post ID is context cache, so cache reset owns its cleanup instead of metadata reset.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
				AI4SEO_POST_META_RELATED_POST_ID_META_KEY,
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321710, 'Database error: ' . $wpdb->last_error, true );
			ai4seo_send_ajax_error( esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ), 784322909 );
			wp_die();
		}
	}

	// ai4seo_reset_notifications.
	if ( isset( $_POST['ai4seo_reset_notifications'] ) && 'true' === $_POST['ai4seo_reset_notifications'] ) {
		// remove all notifications.
		ai4seo_remove_all_notifications();
	}

	// remove environmental variables.
	if ( isset( $_POST['ai4seo_reset_environmental_variables'] ) && 'true' === $_POST['ai4seo_reset_environmental_variables'] ) {
		$ai4seo_environmental_variables            = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
		$ai4seo_environmental_variables_are_loaded = true;
		ai4seo_delete_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME );

		ai4seo_robhub_api()->delete_all_environmental_variables();
	}

	// remove/reset settings.
	if ( isset( $_POST['ai4seo_reset_settings'] ) && 'true' === $_POST['ai4seo_reset_settings'] ) {
		$ai4seo_settings = AI4SEO_DEFAULT_SETTINGS;
		ai4seo_delete_option( AI4SEO_SETTINGS_OPTION_NAME );
	}

	// remove existing generated metadata.
	if ( $do_reset_metadata ) {
		if ( ! $reset_metadata_is_full_reset && $reset_metadata_post_types ) {
			if ( ! ai4seo_reset_generated_data_for_post_ids( $reset_metadata_post_ids, $reset_metadata_post_types ) ) {
				ai4seo_debug_message( 984321708, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error( esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322908 );
				wp_die();
			}
		} else {
			// remove all legacy active metadata postmeta entries.
			if ( ! ai4seo_delete_all_legacy_active_metadata() ) {
				ai4seo_debug_message( 984321692, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error( esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322903 );
				wp_die();
			}

			// remove all postmeta entries with meta_key AI4SEO_POST_META_ACTIVE_METADATA_META_KEY.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
					AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
				)
			);

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321701, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error( esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322906 );
				wp_die();
			}

			// remove all postmeta entries with meta_key AI4SEO_POST_META_GENERATED_DATA_META_KEY.
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '" . esc_sql( AI4SEO_POST_META_GENERATED_DATA_META_KEY ) . "'" );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321693, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error( esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322904 );
				wp_die();
			}

			// remove all postmeta entries with meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY.
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '" . esc_sql( AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY ) . "'" );

			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321694, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error( esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322905 );
				wp_die();
			}

			// remove very wp_options named inside AI4SEO_ALL_POST_ID_OPTIONS-Array.
			foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option ) {
				ai4seo_delete_option( $ai4seo_option );
			}

			// Delete both summary options so metadata reset clears dashboard counters and the large full summary together.
			ai4seo_delete_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );
			ai4seo_delete_option( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME );

			// delete wp_option AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME.
			ai4seo_delete_option( AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME );
		}
	}

	// tidy up.
	ai4seo_tidy_up();

	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - clears the debug message log
 *
 * @return void
 */
function ai4seo_clear_debug_message_log() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 712981224 );
		return;
	}

	$existing_log_entries = get_option( AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array() );

	if ( ! is_array( $existing_log_entries ) ) {
		$existing_log_entries = array();
	}

	$update_result = ai4seo_update_option( AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array(), false, false );

	if ( false === $update_result && ! empty( $existing_log_entries ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Could not clear the debug log. Please try again.', 'ai-for-seo' ), 712981225 );
		return;
	}

	ai4seo_send_ajax_success();
}

/**
 * AJAX handler for exporting settings
 */
function ai4seo_export_settings() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109836 );
		return;
	}

	require_once ai4seo_get_includes_ajax_process_path( 'export-settings.php' );
}

// =========================================================================================== \\

/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_show_import_settings_preview() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109837 );
		return;
	}

	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'import-settings-preview.php' );
	$content = ob_get_clean();

	ai4seo_send_ajax_success( $content );
}

// =========================================================================================== \\

/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_import_settings() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109838 );
		return;
	}

	// file used for display and processing.
	require_once ai4seo_get_includes_ajax_process_path( 'import-settings.php' );
}

// =========================================================================================== \\

/**
 * Called via AJAX - Restores default settings for settings page
 *
 * @return void
 */
function ai4seo_restore_default_settings() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 14109824 );
		return;
	}

	try {
		// Prepare array of settings to restore.
		$ai4seo_settings_to_restore = array();

		// Get default values for only the settings page settings.
		foreach ( AI4SEO_ALL_SETTING_PAGE_SETTINGS as $ai4seo_setting_name ) {
			if ( isset( AI4SEO_DEFAULT_SETTINGS[ $ai4seo_setting_name ] ) ) {
				$ai4seo_settings_to_restore[ $ai4seo_setting_name ] = AI4SEO_DEFAULT_SETTINGS[ $ai4seo_setting_name ];
			}
		}

		// Update settings using the bulk update function.
		if ( ! ai4seo_bulk_update_settings( $ai4seo_settings_to_restore ) ) {
			ai4seo_send_ajax_error( esc_html__( 'Failed to restore default settings.', 'ai-for-seo' ), 14109825 );
			return;
		}

		// Success response.
		ai4seo_send_ajax_success(
			array(
				'message'        => __( 'Default settings restored successfully.', 'ai-for-seo' ),
				'restored_count' => count( $ai4seo_settings_to_restore ),
			)
		);

	} catch ( Exception $e ) {
		ai4seo_debug_message( 581818325, 'Error restoring default settings: ' . $e->getMessage(), true );
		ai4seo_send_ajax_error( esc_html__( 'An error occurred while restoring default settings. Please check your PHP error log for more details.', 'ai-for-seo' ), 15109825 );
	}
}

// =========================================================================================== \\

/**
 * Called via AJAX - Requires the bulk custom-instructions modal to be displayed.
 *
 * @return void
 */
function ai4seo_show_bulk_custom_instructions_modal() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1507062600 );
		return;
	}

	// Render the AJAX display template through the standard modal response wrapper.
	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'bulk-custom-instructions-modal.php' );
	$content = ob_get_clean();
	ai4seo_send_ajax_success( $content );
}

// =========================================================================================== \\

/**
 * Called via AJAX - Requires the metadata editor to be displayed
 *
 * @return void
 */
function ai4seo_show_metadata_editor() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 2306230637 );
		return;
	}

	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'metadata-editor.php' );
	$content = ob_get_clean(); // only your output.
	ai4seo_send_ajax_success( $content );
}


// =========================================================================================== \\

/**
 * Called via AJAX - Requires the attachment attributes editor to be displayed
 *
 * @return void
 */
function ai4seo_show_attachment_attributes_editor() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 291920823 );
		return;
	}

	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'attachment-attributes-editor.php' );
	$content = ob_get_clean(); // only your output.
	ai4seo_send_ajax_success( $content );
}


// =========================================================================================== \\

/**
 * Called via AJAX - Requires related attachments to be displayed.
 *
 * @return void
 */
function ai4seo_show_related_attachments() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 11032600 );
		return;
	}

	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'related-attachments.php' );
	$content = ob_get_clean();
	ai4seo_send_ajax_success( $content );
}


// =========================================================================================== \\

/**
 * Called via AJAX - Checks whether an attachment has usable post usage context.
 *
 * @return void
 */
function ai4seo_check_attachment_usage_context() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 16032600 );
		return;
	}

	$attachment_post_id = absint( $_REQUEST['attachment_post_id'] ?? 0 );

	if ( $attachment_post_id <= 0 ) {
		ai4seo_send_ajax_error( esc_html__( 'Media post id is invalid.', 'ai-for-seo' ), 16032601 );
		return;
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
		ai4seo_send_ajax_error( esc_html__( 'Media post not found.', 'ai-for-seo' ), 16032602 );
		return;
	}

	$deep_context_search_site_support_status = ai4seo_get_deep_context_search_site_support_status();
	$is_deep_context_search_supported        = (bool) ( $deep_context_search_site_support_status['is_supported'] ?? false );
	$is_deep_context_search_enabled          = (bool) ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES );

	// Resolve the first eligible usage post once and expose its permalink for the editor's context link.
	$usage_post_id    = ai4seo_get_first_attachment_using_post_id( $attachment_post_id );
	$usage_post_title = '';
	$usage_post_url   = '';

	if ( $usage_post_id > 0 && ai4seo_is_attachment_context_post_eligible( $usage_post_id ) ) {
		$usage_post_title = get_the_title( $usage_post_id );
		$usage_post_url   = ai4seo_get_attachment_context_frontend_post_url( $usage_post_id );

		if ( ! $usage_post_title ) {
			$usage_post_title = __( 'Untitled', 'ai-for-seo' );
		}
	} else {
		$usage_post_id = 0;
	}

	// Return the same context payload as before, with an optional frontend URL for linkable post references.
	ai4seo_send_ajax_success(
		array(
			'usage_context_available'       => ( $usage_post_id > 0 ),
			'post_id'                       => $usage_post_id,
			'post_title'                    => trim( sanitize_text_field( $usage_post_title ) ),
			'post_url'                      => esc_url_raw( $usage_post_url ),
			'deep_context_search_enabled'   => $is_deep_context_search_enabled,
			'deep_context_search_supported' => $is_deep_context_search_supported,
			'settings_url'                  => ai4seo_get_subpage_url( 'settings' ),
		)
	);
}


// =========================================================================================== \\

/**
 * Called via AJAX - Returns the dashboard HTML for auto-refresh functionality
 *
 * @return void
 */
function ai4seo_get_dashboard_html() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 61628825 );
		return;
	}

	ai4seo_check_for_performance_analysis();
	ai4seo_check_for_unfinished_posts_table_analysis_notification( true );

	ob_start();
	require_once ai4seo_get_includes_pages_path( 'dashboard.php' );
	$dashboard_content = ob_get_clean();

	// Keep the complete refresh root as markup so notifications and dashboard cards share one response boundary.
	$start_pattern = "<div class='ai4seo-dashboard-refresh-root'>";
	$start_pos     = strpos( $dashboard_content, $start_pattern );

	if ( false !== $start_pos ) {
		$dashboard_refresh_root_html = substr( $dashboard_content, $start_pos );

		// The dashboard page outputs one refresh root, so its final closing div ends the refresh response.
		$last_div_pos = strrpos( $dashboard_refresh_root_html, '</div>' );

		if ( false !== $last_div_pos ) {
			$dashboard_refresh_root_html = substr( $dashboard_refresh_root_html, 0, $last_div_pos + strlen( '</div>' ) );
			ai4seo_send_ajax_success( $dashboard_refresh_root_html );
		} else {
			ai4seo_send_ajax_error( esc_html__( 'Dashboard refresh root closing tag not found', 'ai-for-seo' ), 71628825 );
		}
	} else {
		ai4seo_send_ajax_error( esc_html__( 'Dashboard refresh root not found', 'ai-for-seo' ), 81628825 );
	}
}


// =========================================================================================== \\

/**
 * Called via AJAX - Generates metadata after clicking on a generate metadata button
 *
 * @return void
 */
function ai4seo_generate_metadata() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 34127322 );
		return;
	}

	require_once ai4seo_get_includes_ajax_process_path( 'generate-metadata.php' );
	wp_die();
}


// =========================================================================================== \\

/**
 * Called via AJAX - Generates attachment-attributes after clicking on a generate attachment-attributes button
 *
 * @return void
 */
function ai4seo_generate_attachment_attributes() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 211823823 );
		return;
	}

	require_once ai4seo_get_includes_ajax_process_path( 'generate-attachment-attributes.php' );
	wp_die();
}

// =========================================================================================== \\

/**
 * Called via AJAX - Dismisses a notification by index
 *
 * @return void
 */
function ai4seo_dismiss_notification() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109831 );
		return;
	}

	// get the notification index.
	$notification_index = sanitize_key( $_POST['ai4seo_notification_index'] ?? '' );

	if ( empty( $notification_index ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid notification index.', 'ai-for-seo' ), 16109825 );
		return;
	}

	// mark the notification as dismissed.
	$result = ai4seo_mark_notification_as_dismissed( $notification_index );

	if ( $result ) {
		ai4seo_send_ajax_success();
	} else {
		ai4seo_send_ajax_error( esc_html__( 'Failed to dismiss notification.', 'ai-for-seo' ), 17109825 );
	}
}

// =========================================================================================== \\

/**
 * Called via AJAX - Shows the terms of service
 *
 * @return void
 */
function ai4seo_show_terms_of_service() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109839 );
		return;
	}

	ob_start();
	$latest_tos_and_toc_and_pp_version = ai4seo_get_latest_tos_and_toc_and_pp_version();

	// headline.
	echo '<center>';
			ai4seo_echo_wp_kses( ai4seo_get_sooz_logo_image_tag() );
			echo '<h1>' . esc_html( __( 'Terms of Service', 'ai-for-seo' ) ) . '</h1>';
		ai4seo_echo_wp_kses( ai4seo_get_tos_toc_and_pp_accepted_time_output() );
		echo ' ';
	echo '</center><br>';

	echo "<div class='ai4seo-tos-version-number'>" . esc_html( $latest_tos_and_toc_and_pp_version ) . '</div>';
	ai4seo_echo_wp_kses( ai4seo_get_tos_content() );
	$content = ob_get_clean();

	ai4seo_send_ajax_success( $content, null, true );
}

// =========================================================================================== \\

/**
 * Called via AJAX - Imports possible nextgen gallery images to the posts table using our own post_type
 *
 * @return void
 */
/**
 * Import NextGen Gallery images into AI4SEO custom post type attachments.
 *
 * @return void
 */
function ai4seo_import_nextgen_gallery_images() {
	global $wpdb;

	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 18147524 );
		return;
	}

	// Read all pid's of wp_ngg_pictures.
	$nextgen_gallery_images = $wpdb->get_results(
		'SELECT `pid`, `image_slug`, `galleryid`, `filename`, `description`, `alttext`, `imagedate`, `updated_at`
        FROM ' . esc_sql( $wpdb->prefix ) . 'ngg_pictures
        WHERE `pid` > 0',
		ARRAY_A
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321695, 'Database error: ' . $wpdb->last_error, true );
		ai4seo_send_ajax_error( esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ), 784322906 );
	}

	if ( ! $nextgen_gallery_images ) {
		ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery Images found', 'ai-for-seo' ), 18147525 );
	}

	// Find all distinct galleryid's.
	$nextgen_gallery_image_gallery_ids = array_column( $nextgen_gallery_images, 'galleryid' );

	if ( ! $nextgen_gallery_image_gallery_ids ) {
		ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery galleries found', 'ai-for-seo' ), 19147525 );
	}

	// Normalize gallery IDs to integers, unique, no zeros.
	$nextgen_gallery_image_gallery_ids = array_map( 'absint', $nextgen_gallery_image_gallery_ids );
	$nextgen_gallery_image_gallery_ids = array_filter( array_unique( $nextgen_gallery_image_gallery_ids ) );

	$nextgen_gallery_gallery_paths = array();

	$gallery_ids_chunk_size = ai4seo_get_database_chunk_size();
	$gallery_ids_chunks     = array_chunk( $nextgen_gallery_image_gallery_ids, $gallery_ids_chunk_size );

	foreach ( $gallery_ids_chunks as $this_gallery_ids_chunk ) {
		if ( ! $this_gallery_ids_chunk ) {
			continue;
		}

		$placeholders = implode( ',', array_fill( 0, count( $this_gallery_ids_chunk ), '%d' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $placeholders is built from %d only.
		$nextgen_gallery_galleries_temp = $wpdb->get_results( $wpdb->prepare( 'SELECT `gid`, `path` FROM ' . esc_sql( $wpdb->prefix ) . "ngg_gallery WHERE `gid` IN ($placeholders)", $this_gallery_ids_chunk ), ARRAY_A );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321696, 'Database error: ' . $wpdb->last_error, true );
			ai4seo_send_ajax_error( esc_html__( 'Database error while reading NextGen Gallery galleries.', 'ai-for-seo' ), 784322907 );
		}

		if ( $nextgen_gallery_galleries_temp ) {
			foreach ( $nextgen_gallery_galleries_temp as $this_nextgen_gallery_image_gallery_paths_temp_entry ) {
				$this_nextgen_gallery_gallery_id   = absint( $this_nextgen_gallery_image_gallery_paths_temp_entry['gid'] );
				$this_nextgen_gallery_gallery_path = sanitize_text_field( $this_nextgen_gallery_image_gallery_paths_temp_entry['path'] );

				if ( $this_nextgen_gallery_gallery_id > 0 && '' !== $this_nextgen_gallery_gallery_path ) {
					$nextgen_gallery_gallery_paths[ $this_nextgen_gallery_gallery_id ] = $this_nextgen_gallery_gallery_path;
				}
			}
		}
	}

	if ( ! $nextgen_gallery_gallery_paths ) {
		ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery gallery paths found', 'ai-for-seo' ), 20147525 );
	}

	// Reformat to array(pid => array(entry), ...).
	$nextgen_gallery_images = array_column( $nextgen_gallery_images, null, 'pid' );

	// Get the already imported pids from wp_posts where type is AI4SEO_NEXTGEN_GALLERY_POST_TYPE.
	$already_imported_nextgen_gallery_image_pids = $wpdb->get_results(
		'SELECT post_parent
        FROM ' . esc_sql( $wpdb->posts ) . "
        WHERE `post_type` = '" . esc_sql( AI4SEO_NEXTGEN_GALLERY_POST_TYPE ) . "'",
		ARRAY_A
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321697, 'Database error: ' . $wpdb->last_error, true );
		ai4seo_send_ajax_error( esc_html__( 'Database error while reading imported NextGen Gallery images.', 'ai-for-seo' ), 784322908 );
	}

	if ( $already_imported_nextgen_gallery_image_pids ) {
		$already_imported_nextgen_gallery_image_pids = array_map(
			'absint',
			array_column( $already_imported_nextgen_gallery_image_pids, 'post_parent' )
		);
	} else {
		$already_imported_nextgen_gallery_image_pids = array();
	}

	// Go through $nextgen_gallery_images, build guid and insert into wp_posts.
	foreach ( $nextgen_gallery_images as $this_nextgen_gallery_image ) {
		$this_nextgen_gallery_image_pid        = absint( $this_nextgen_gallery_image['pid'] );
		$this_nextgen_gallery_image_gallery_id = absint( $this_nextgen_gallery_image['galleryid'] );

		// Check if pid is already imported.
		if ( in_array( $this_nextgen_gallery_image_pid, $already_imported_nextgen_gallery_image_pids, true ) ) {
			continue;
		}

		// Check if gallery id is valid.
		if ( ! isset( $nextgen_gallery_gallery_paths[ $this_nextgen_gallery_image_gallery_id ] ) ) {
			continue;
		}

		$this_nextgen_gallery_gallery_path = $nextgen_gallery_gallery_paths[ $this_nextgen_gallery_image_gallery_id ];

		// Build guid.
		$this_website_url                = get_site_url();
		$this_nextgen_gallery_image_guid = untrailingslashit( $this_website_url ) . trailingslashit( $this_nextgen_gallery_gallery_path ) . $this_nextgen_gallery_image['filename'];
		$this_image_mime_type            = ai4seo_get_mime_type_from_url( $this_nextgen_gallery_image_guid );

		// Fallback to jpeg, as this information is not technically required.
		if ( ! $this_image_mime_type ) {
			$this_image_mime_type = 'image/jpeg';
		}

		// Insert into wp_posts.
		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_title'        => sanitize_text_field( $this_nextgen_gallery_image['image_slug'] ),
				'post_name'         => sanitize_text_field( $this_nextgen_gallery_image['image_slug'] ),
				'post_content'      => sanitize_text_field( $this_nextgen_gallery_image['description'] ),
				'post_excerpt'      => sanitize_text_field( $this_nextgen_gallery_image['alttext'] ),
				'post_type'         => AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
				'post_status'       => 'publish',
				'post_mime_type'    => sanitize_text_field( $this_image_mime_type ),
				'post_parent'       => $this_nextgen_gallery_image_pid,
				'guid'              => esc_url( $this_nextgen_gallery_image_guid ),
				'post_date'         => date( 'Y-m-d H:i:s', strtotime( $this_nextgen_gallery_image['imagedate'] ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'post_date_gmt'     => ai4seo_gmdate( 'Y-m-d H:i:s', strtotime( $this_nextgen_gallery_image['imagedate'] ) ),
				'post_modified'     => date( 'Y-m-d H:i:s', absint( $this_nextgen_gallery_image['updated_at'] ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'post_modified_gmt' => ai4seo_gmdate( 'Y-m-d H:i:s', absint( $this_nextgen_gallery_image['updated_at'] ) ),
			)
		);

		// Check for errors.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321665, 'Database error: ' . $wpdb->last_error, true );
			ai4seo_send_ajax_error(
				sprintf(
					/* translators: NextGen Gallery picture ID */
					esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
					$this_nextgen_gallery_image_pid
				),
				21147525
			);
		}

		// Get added post id.
		$this_new_post_id = absint( $wpdb->insert_id );

		// Add _wp_attachment_image_alt post meta for the alt text too.
		if ( ! empty( $this_nextgen_gallery_image['alttext'] ) ) {
			$wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => $this_new_post_id,
					'meta_key'   => '_wp_attachment_image_alt',
					'meta_value' => sanitize_text_field( $this_nextgen_gallery_image['alttext'] ),
				)
			);

			// Check for errors.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321666, 'Database error: ' . $wpdb->last_error, true );
				ai4seo_send_ajax_error(
					sprintf(
						/* translators: NextGen Gallery picture ID */
						esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
						$this_nextgen_gallery_image_pid
					),
					22147525
				);
			}
		}
	}

	ai4seo_send_ajax_success();
}
