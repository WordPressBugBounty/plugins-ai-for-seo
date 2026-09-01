<?php
/**
 * Handles plugin AJAX routing and request processing.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region AJAX ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/*
 * Instructions for adding a new AJAX action: see .agent/rules/ajax.md
 */


/**
 * Gatekeeper for AI4SEO AJAX requests.
 *
 * @return void
 */
function ai4seo_on_ajax_action() {
	if ( wp_doing_ajax() === false ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing key precedes the nonce gate.
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

	if ( '' === $action ) {
		if ( ai4seo_request_contains_prefixed_parameters() ) {
			ai4seo_debug_message( 2512181226, 'AJAX request is missing the action parameter. The POST body may have been truncated by PHP max_input_vars.', true );
			ai4seo_send_ajax_error(
				esc_html__( 'The AJAX request was incomplete before WordPress could route it. Please increase the PHP max_input_vars limit and try again.', 'ai-for-seo' ),
				2512181226
			);
		}

		return;
	}

	if ( strpos( $action, 'ai4seo_' ) !== 0 ) {
		return;
	}

	// we have an AJAX request for our plugin, let's run the security gate.
	ai4seo_ajax_security_gate();

	if ( ! in_array( $action, AI4SEO_ALLOWED_AJAX_FUNCTIONS, true ) ) {
		ai4seo_debug_message( 2312181226, 'Blocked unknown AJAX action: ' . $action, true );
		ai4seo_send_ajax_error(
			esc_html__( 'AJAX action is not allowed. Please refresh the page and try again.', 'ai-for-seo' ),
			2312181226
		);
	}

	$ajax_hook_name = "wp_ajax_{$action}";

	if ( has_action( $ajax_hook_name ) === false ) {
		ai4seo_debug_message( 2412181226, 'AJAX action has no registered handler: ' . $action, true );
		ai4seo_send_ajax_error(
			esc_html__( 'AJAX action is not available. Please refresh the page and try again.', 'ai-for-seo' ),
			2412181226
		);
	}
}


/**
 * Checks whether the current request contains plugin-prefixed parameters.
 *
 * @return bool
 */
function ai4seo_request_contains_prefixed_parameters(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Names-only diagnostic has no side effects.
	foreach ( $_REQUEST as $parameter_name => $parameter_value ) {
		if ( ! is_string( $parameter_name ) ) {
			continue;
		}

		if ( strpos( $parameter_name, AI4SEO_POST_PARAMETER_PREFIX ) === 0 ) {
			return true;
		}
	}

	return false;
}


/**
 * Check whether an AJAX action requires site-wide administrative permission.
 *
 * @param string $ajax_action Sanitized AJAX action name.
 * @return bool
 */
function ai4seo_ajax_action_requires_administration( string $ajax_action ): bool {
	return in_array( $ajax_action, AI4SEO_ADMINISTRATIVE_AJAX_FUNCTIONS, true );
}


/**
 * Send the shared AJAX authorization failure when administrative permission is missing.
 *
 * @return bool True when the current user is authorized. Unauthorized requests terminate while sending JSON.
 */
function ai4seo_require_ajax_administration(): bool {
	if ( ai4seo_can_administer_plugin() ) {
		return true;
	}

	ai4seo_send_ajax_error(
		esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
		11420725
	);

	return false;
}


/**
 * Validates nonce and permissions for AI4SEO AJAX requests.
 *
 * Sends an AJAX error and exits on failure.
 *
 * @return void
 */
function ai4seo_ajax_security_gate() {
	$ajax_nonce  = '';
	$ajax_action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );

	if ( isset( $_REQUEST[ AI4SEO_GLOBAL_NONCE_IDENTIFIER ] ) ) {
		$ajax_nonce = sanitize_text_field( wp_unslash( $_REQUEST[ AI4SEO_GLOBAL_NONCE_IDENTIFIER ] ) );
	} elseif ( isset( $_REQUEST['security'] ) ) {
		$ajax_nonce = sanitize_text_field( wp_unslash( $_REQUEST['security'] ) );
	}

	if ( '' === $ajax_nonce ) {
		ai4seo_send_ajax_error(
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
			401271224
		);
	}

	if ( wp_verify_nonce( $ajax_nonce, AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error(
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
			411271224
		);
	}

	$has_required_permission = ai4seo_ajax_action_requires_administration( $ajax_action )
		? ai4seo_can_administer_plugin()
		: ai4seo_can_use_plugin_content();

	// The mixed save endpoint performs field-level authorization after decoding its protected payload.
	if ( 'ai4seo_save_anything' === $ajax_action ) {
		$has_required_permission = ai4seo_can_use_plugin_content()
			|| ai4seo_can_administer_plugin()
			|| ai4seo_can_recover_incognito_mode();
	}

	if ( false === $has_required_permission ) {
		ai4seo_send_ajax_error(
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
			11420725
		);
	}

	$GLOBALS['ai4seo_ajax_nonce'] = $ajax_nonce;
}


/**
 * Check whether a save-anything payload contains site-wide configuration or account fields.
 *
 * @param array $upcoming_updates Sanitized, unprefixed save-anything updates.
 * @return bool
 */
function ai4seo_save_payload_requires_administration( array $upcoming_updates ): bool {
	$administrative_names = array_merge(
		array_keys( AI4SEO_DEFAULT_SETTINGS ),
		array_keys( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES ),
		array_keys( ai4seo_robhub_api()::DEFAULT_ENVIRONMENTAL_VARIABLES )
	);

	return (bool) array_intersect( array_keys( $upcoming_updates ), $administrative_names );
}


/**
 * Check whether a save payload is the narrow operation allowed during Incognito recovery.
 *
 * @param array $upcoming_updates Sanitized, unprefixed save-anything updates.
 * @return bool
 */
function ai4seo_is_valid_incognito_recovery_save_payload( array $upcoming_updates ): bool {
	if ( 1 !== count( $upcoming_updates ) || ! array_key_exists( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE, $upcoming_updates ) ) {
		return false;
	}

	$submitted_value = ai4seo_normalize_boolean_setting_value(
		AI4SEO_SETTING_ENABLE_INCOGNITO_MODE,
		$upcoming_updates[ AI4SEO_SETTING_ENABLE_INCOGNITO_MODE ]
	);

	return is_bool( $submitted_value );
}


/**
 * Capture output noise without getting stuck on a non-removable buffer.
 *
 * @return string Captured output ordered from the innermost buffer outward.
 */
function ai4seo_drain_ajax_output_buffers(): string {
	$noise = '';

	// Traverse buffers from the innermost level while each handler can be manipulated safely.
	while ( 0 < ob_get_level() ) {
		$buffer_level  = ob_get_level();
		$buffer_status = ob_get_status();

		// Missing capability metadata makes further cleanup unsafe.
		if ( ! is_array( $buffer_status ) || ! isset( $buffer_status['flags'] ) ) {
			break;
		}

		$buffer_flags   = (int) $buffer_status['flags'];
		$buffer_content = ob_get_contents();

		// An unreadable buffer cannot be captured without risking response corruption.
		if ( ! is_string( $buffer_content ) ) {
			break;
		}

		// Ordinary removable buffers can be captured and closed before inspecting their parent.
		if ( 0 !== ( $buffer_flags & PHP_OUTPUT_HANDLER_REMOVABLE ) ) {
			if ( ! ob_end_clean() ) {
				break;
			}

			$noise .= $buffer_content;

			if ( ob_get_level() >= $buffer_level ) {
				break;
			}

			continue;
		}

		// A non-removable buffer must remain active, so clean it when supported and stop before its parent.
		if ( 0 === ( $buffer_flags & PHP_OUTPUT_HANDLER_CLEANABLE ) || ! ob_clean() ) {
			break;
		}

		$noise .= $buffer_content;
		break;
	}

	return $noise;
}


/**
 * Helper: send clean JSON and log any noise safely.
 *
 * @param mixed $response JSON data or a raw HTML string when raw-response mode is enabled.
 * @param mixed $status_code The status code value.
 * @param bool  $send_raw_html_content The send raw html content value.
 */
function ai4seo_send_ajax_success( $response = array(), $status_code = null, $send_raw_html_content = false ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Drain preceding output so only the requested AJAX response reaches the client.
	$noise = ai4seo_drain_ajax_output_buffers();

	if ( '' !== $noise ) {
		// Log the first part so we can find the culprit later.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			ai4seo_debug_message( 526144335, 'AJAX noise stripped: ' . substr( $noise, 0, 500 ), true );
		}
	}

	// clean data.
	ai4seo_normalize_ajax_response_data( $response );

	if ( $send_raw_html_content ) {
		// Raw-response mode accepts HTML strings only; JSON-mode arrays must never reach the string-only echo helper.
		$raw_html_content = is_string( $response ) ? $response : '';
		ai4seo_echo_wp_kses( $raw_html_content );
		// Stop admin-ajax from appending its default zero body after the raw response.
		wp_die();
	} else {
		// JSON header + exit.
		wp_send_json_success( $response, $status_code );
	}
}


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

	// Apply the same capability-aware cleanup as successful AJAX responses.
	if ( $clear_buffer ) {
		ai4seo_drain_ajax_output_buffers();
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


/**
 * Normalize translatable strings in an AJAX response payload.
 *
 * @param mixed $data AJAX response data, passed by reference.
 * @return void
 */
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


/**
 * Remove TranslatePress markers from one AJAX response item.
 *
 * @param mixed $item AJAX response item, passed by reference.
 * @return void
 */
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
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Strict decoding preserves the declared JSON transport and rejects malformed input.
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

	// Classify the complete decoded payload once so every processor receives the same authorization decision.
	$ai4seo_save_requires_administration = ai4seo_save_payload_requires_administration( $upcoming_save_anything_updates );
	$ai4seo_is_incognito_recovery_save   = ai4seo_can_recover_incognito_mode()
		&& ai4seo_is_valid_incognito_recovery_save_payload( $upcoming_save_anything_updates );
	$ai4seo_has_required_save_permission = $ai4seo_save_requires_administration
		? ai4seo_can_administer_plugin() || $ai4seo_is_incognito_recovery_save
		: ai4seo_can_use_plugin_content();

	// Resolve the whole payload to one boundary before any category can persist a partial update.
	if ( ! $ai4seo_has_required_save_permission ) {
		ai4seo_send_ajax_error(
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
			11420725
		);
		return;
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
	$save_anything_response = array();

	foreach ( $save_anything_processors as $save_anything_processor ) {
		$save_anything_processor_result = $save_anything_processor( $upcoming_save_anything_updates );

		// Arrays contribute response data; null and other non-errors preserve the existing success/no-op behavior.
		if ( is_array( $save_anything_processor_result ) ) {
			$save_anything_response = array_replace_recursive( $save_anything_response, $save_anything_processor_result );
			continue;
		}

		if ( ! ( $save_anything_processor_result instanceof WP_Error ) ) {
			continue;
		}

		// Keep JSON response formatting centralized in the existing AJAX error mechanism.
		ai4seo_send_ajax_error(
			$save_anything_processor_result->get_error_message(),
			(int) $save_anything_processor_result->get_error_code()
		);
		return;
	}

	// Report success only after every category has completed without returning an error.
	ai4seo_send_ajax_success( $save_anything_response );
}

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


/**
 * Returns the base and bracket parts of a PHP-style form field name.
 *
 * @param string $parameter_name Parameter name, for example ai4seo_setting[key][].
 * @return array Parameter name parts.
 */
function ai4seo_get_bracketed_parameter_name_parts( string $parameter_name ): array {
	// Accept only a base name followed by one or more bracket parts.
	if ( ! preg_match( '/^([^\[\]]+)((?:\[[^]]*])+)$/', $parameter_name, $matches ) ) {
		return array();
	}

	// Extract the individual bracket values while preserving empty [] markers.
	preg_match_all( '/\[([^]]*)]/', $matches[2], $bracket_matches );

	// Reject names that matched the outer pattern but did not produce usable bracket parts.
	if ( empty( $bracket_matches[1] ) ) {
		return array();
	}

	// Return a single ordered path containing the base field name and all nested bracket keys.
	return array_merge( array( $matches[1] ), $bracket_matches[1] );
}


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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109832 );
		return;
	}

	// stop bulk generation.
	if ( ! ai4seo_update_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, AI4SEO_DEFAULT_SETTINGS[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Bulk generation could not be stopped. Please try again.', 'ai-for-seo' ), 12109842 );
		return;
	}

	// send success.
	ai4seo_send_ajax_success();
}


/**
 * Clears snapshot pending memberships and their paired force-overwrite markers atomically.
 *
 * @return bool True only when every requested absence is verified and the shared lock releases.
 */
function ai4seo_clear_pending_bulk_generation_queue_entries(): bool {
	// Snapshot each Pending owner only after entering the shared fence, then remove exactly its paired Force IDs.
	return ai4seo_clear_primary_post_id_option_pairs(
		array(
			AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
			AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		)
	);
}


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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109833 );
		return;
	}

	// Clear Pending and the paired Force marker in one checked cross-family transition.
	if ( ! ai4seo_clear_pending_bulk_generation_queue_entries() ) {
		ai4seo_send_ajax_error( esc_html__( 'The queue could not be cleared.', 'ai-for-seo' ), 1010062601 );
		return;
	}

	// send success.
	ai4seo_send_ajax_success(
		array(
			'queue_count' => ai4seo_get_bulk_generation_queue_count(),
		)
	);
}


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

	// Validate every selected object because the AJAX payload is independent of the visible admin table.
	if ( ! ai4seo_can_edit_post_ids( $post_ids ) ) {
		ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit one or more selected entries.', 'ai-for-seo' ), 3106062606 );
		return;
	}

	$queue_transition_failed = false;
	$result                  = ai4seo_process_bulk_generation_queue_action(
		$bulk_generation_queue_action,
		$post_ids,
		$context,
		$queue_transition_failed
	);

	if ( $queue_transition_failed ) {
		ai4seo_send_ajax_error( esc_html__( 'The selected bulk action could not be applied.', 'ai-for-seo' ), 2208062601 );
		return;
	}

	// send success.
	ai4seo_send_ajax_success( $result );
}


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

	// Validate every selected object before applying its entry-level custom instructions.
	if ( ! ai4seo_can_edit_post_ids( $post_ids ) ) {
		ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit one or more selected entries.', 'ai-for-seo' ), 1407062606 );
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

	if ( ! ai4seo_can_use_plugin_content() ) {
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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109834 );
		return;
	}

	// Clear failed ownership through the same fence used by worker terminal transitions.
	if ( ! ai4seo_clear_post_id_options( array( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ) ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Failed attachment attributes could not be queued for retry. Please try again.', 'ai-for-seo' ), 12109840 );
		return;
	}

	// Refresh the generation status summary after the already-authorized retry mutation.
	if ( ! ai4seo_force_posts_table_analysis_refresh_after_admin_mutation() ) {
		if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
			ai4seo_debug_message( 984321742, 'Could not persist a generation-status rebuild request after the failed attachment retry refresh.', true );
		}

		ai4seo_send_ajax_error( esc_html__( 'Failed attachment attributes could not be queued for retry. Please try again.', 'ai-for-seo' ), 12109840 );
		return;
	}

	// send success.
	ai4seo_send_ajax_success();
}


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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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
	if ( ! ai4seo_remove_all_post_ids_by_post_type_and_generation_status( $post_type, AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Failed metadata could not be queued for retry. Please try again.', 'ai-for-seo' ), 12109841 );
		return;
	}

	// Refresh the generation status summary after the already-authorized retry mutation.
	if ( ! ai4seo_force_posts_table_analysis_refresh_after_admin_mutation() ) {
		if ( ! ai4seo_schedule_generation_status_summary_rebuild() ) {
			ai4seo_debug_message( 984321743, 'Could not persist a generation-status rebuild request after the failed metadata retry refresh.', true );
		}

		ai4seo_send_ajax_error( esc_html__( 'Failed metadata could not be queued for retry. Please try again.', 'ai-for-seo' ), 12109841 );
		return;
	}

	// send success.
	ai4seo_send_ajax_success();
}


/**
 * Called via AJAX - refresh dashboard statistics by running the performance analysis
 *
 * @return void
 */
function ai4seo_refresh_dashboard_statistics() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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


/**
 * Called via AJAX - refresh the RobHub account data manually
 *
 * @return void
 */
function ai4seo_refresh_robhub_account() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109826 );
		return;
	}

	$check_for_purchase                        = (bool) sanitize_text_field( wp_unslash( $_POST['check_for_purchase'] ?? false ) );
	$robhub_api                                = ai4seo_robhub_api();
	$had_api_password_rotation_recovery_intent = $robhub_api->has_api_password_rotation_recovery_intent();

	if ( $check_for_purchase ) {
		$robhub_api->accelerate_pending_api_password_rotation_reconciliation();
	}

	$robhub_api->set_auth_data_locked( false );
	$robhub_api->reset_last_account_sync();
	$api_response = ai4seo_sync_robhub_account( 'manual_refresh' );

	if ( ! $robhub_api->was_call_successful( $api_response ) ) {
		// Any non-missing pending state must bypass the generic authentication lock.
		// Server-backed reconciliation resolves quarantined or temporarily unreadable state.
		$recovery_transition_owns_auth_state = (
			$had_api_password_rotation_recovery_intent
				|| $robhub_api->has_api_password_rotation_recovery_intent()
		)
			&& ! $robhub_api->is_auth_data_locked()
			&& $robhub_api->check_credentials();

		if ( ! $robhub_api->has_pending_api_password_rotation() && ! $recovery_transition_owns_auth_state ) {
			$robhub_api->set_auth_data_locked( true );
		}
		$robhub_api->reset_last_account_sync();
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
		$credits_balance         = $robhub_api->get_credits_balance();

		$ajax_response['is_purchase_ready'] = $has_purchased_something
			&& $credits_balance > 400
			&& ! $robhub_api->has_pending_api_password_rotation();
	}

	ai4seo_send_ajax_success( $ajax_response );
}



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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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


/**
 * Validates one Stripe purchase URL candidate.
 *
 * @param string $purchase_url_candidate Purchase URL candidate.
 * @return string Valid HTTPS Stripe URL or an empty string.
 */
function ai4seo_validate_stripe_purchase_url_candidate( string $purchase_url_candidate ): string {
	// Apply WordPress's URL and SSRF validation before evaluating the payment-provider boundary.
	$purchase_url_candidate = esc_url_raw( $purchase_url_candidate, array( 'https' ) );

	if ( '' === $purchase_url_candidate || false === wp_http_validate_url( $purchase_url_candidate ) ) {
		return '';
	}

	// Parse only the sanitized URL so encoded or malformed authority components cannot affect the allowlist.
	$scheme = strtolower( (string) wp_parse_url( $purchase_url_candidate, PHP_URL_SCHEME ) );
	$host   = strtolower( (string) wp_parse_url( $purchase_url_candidate, PHP_URL_HOST ) );

	if ( 'https' !== $scheme || '' === $host ) {
		return '';
	}

	// Accept Stripe itself and true subdomains while rejecting lookalike suffixes such as evilstripe.com.
	$stripe_host_suffix = '.stripe.com';
	$is_stripe_host     = 'stripe.com' === $host
		|| substr( $host, -strlen( $stripe_host_suffix ) ) === $stripe_host_suffix;

	return $is_stripe_host ? $purchase_url_candidate : '';
}

/**
 * Normalize and validate the Stripe purchase URL returned by RobHub.
 *
 * @param mixed $purchase_url Raw purchase URL.
 * @return string Valid HTTPS Stripe URL or an empty string.
 */
function ai4seo_normalize_purchase_url( $purchase_url ): string {
	// Preserve the empty-string failure contract for missing or malformed API fields.
	if ( ! is_string( $purchase_url ) || '' === $purchase_url ) {
		return '';
	}

	// Decode HTML transport entities without changing opaque URL path or query data.
	$purchase_url = html_entity_decode(
		$purchase_url,
		ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
		'UTF-8'
	);

	// Preserve valid modern responses exactly rather than decoding their opaque path or query data.
	$validated_purchase_url = ai4seo_validate_stripe_purchase_url_candidate( $purchase_url );

	if ( '' !== $validated_purchase_url ) {
		return $validated_purchase_url;
	}

	// Historical RobHub responses encoded the complete URL, so retry that format only after raw validation fails.
	$legacy_purchase_url = rawurldecode( $purchase_url );

	if ( $legacy_purchase_url === $purchase_url ) {
		return '';
	}

	return ai4seo_validate_stripe_purchase_url_candidate( $legacy_purchase_url );
}

/**
 * Resolve the subscription CTA destination after an explicit administrator click.
 *
 * Existing subscribers go to the configured billing portal. A paid credit-only account obtains
 * authenticated attribution, while a free account obtains its first-purchase rotation claim.
 *
 * @return string Valid configured destination URL, or an empty string on failure.
 */
function ai4seo_prepare_subscription_pricing_destination_url(): string {
	if ( ! ai4seo_can_administer_plugin() ) {
		return '';
	}

	$robhub_api              = ai4seo_robhub_api();
	$subscription            = $robhub_api->read_environmental_variable( $robhub_api::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );
	$has_purchased_something = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );

	if ( is_array( $subscription ) && $subscription ) {
		$billing_url = esc_url_raw( AI4SEO_STRIPE_BILLING_URL, array( 'https' ) );

		// The subscribed path is a closed redirect to the exact configured billing portal.
		return AI4SEO_STRIPE_BILLING_URL === $billing_url ? $billing_url : '';
	}

	$purchase_client_reference = $has_purchased_something
		? $robhub_api->prepare_subscription_pricing_client_reference()
		: ai4seo_prepare_first_purchase_api_password_rotation_claim();

	if ( '' === $purchase_client_reference ) {
		return '';
	}

	$pricing_url = ai4seo_get_purchase_plan_url( $purchase_client_reference );

	// The pricing path is constructed locally and must remain on the exact configured origin/path.
	$expected_pricing_url = trailingslashit( AI4SEO_OFFICIAL_PRICING_URL );
	$expected_parts       = wp_parse_url( $expected_pricing_url );
	$actual_parts         = wp_parse_url( $pricing_url );

	if ( ! is_array( $expected_parts ) || ! is_array( $actual_parts )
		|| 'https' !== strtolower( (string) ( $actual_parts['scheme'] ?? '' ) )
		|| strtolower( (string) ( $expected_parts['host'] ?? '' ) ) !== strtolower( (string) ( $actual_parts['host'] ?? '' ) )
		|| (string) ( $expected_parts['path'] ?? '' ) !== (string) ( $actual_parts['path'] ?? '' )
		|| isset( $actual_parts['user'] )
		|| isset( $actual_parts['pass'] )
		|| isset( $actual_parts['fragment'] ) ) {
		return '';
	}

	parse_str( (string) ( $actual_parts['query'] ?? '' ), $pricing_query );

	return array( 'client-id' => $purchase_client_reference ) === $pricing_query ? $pricing_url : '';
}

/**
 * Initialize subscription pricing or account billing after an explicit administrator CTA.
 *
 * @return void
 */
function ai4seo_init_subscription_pricing() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109835 );
		return;
	}

	$purchase_url = ai4seo_prepare_subscription_pricing_destination_url();

	if ( '' === $purchase_url ) {
		ai4seo_debug_message( 601818326, 'Could not prepare the subscription-pricing destination.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not initialize secure account protection for this purchase. Please try again.', 'ai-for-seo' ), 601818326 );
		return;
	}

	// Start the shared purchase/reconciliation cadence only after the destination is ready to open.
	ai4seo_record_purchase_activity();
	ai4seo_send_ajax_success( array( 'purchase_url' => $purchase_url ) );
}

/**
 * Initialize a secure purchase and return its validated checkout URL by AJAX.
 *
 * @return void
 */
function ai4seo_init_purchase() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109827 );
		return;
	}

	// Accept only a scalar Stripe price selected from the server-rendered pack registry.
	if ( ! isset( $_POST['stripe_price_id'] ) || ! is_string( $_POST['stripe_price_id'] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid stripe_price_id', 'ai-for-seo' ), 551818325 );
		wp_die();
	}

	$stripe_price_id = sanitize_text_field( wp_unslash( $_POST['stripe_price_id'] ) );

	if ( ! array_key_exists( $stripe_price_id, ai4seo_get_credits_packs() ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid stripe_price_id', 'ai-for-seo' ), 551818326 );
		return;
	}

	// Build a user-bound return URL so only the initiated checkout can confirm the purchase return.
	$purchase_return_token = ai4seo_create_purchase_return_token();

	if ( '' === $purchase_return_token ) {
		ai4seo_debug_message( 571818325, 'Could not store purchase-return state.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not initialize the secure purchase return. Please try again.', 'ai-for-seo' ), 571818325 );
		return;
	}

	$redirect_url = ai4seo_get_purchase_return_url( $purchase_return_token );

	if ( '' === $redirect_url ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
		ai4seo_debug_message( 571818325, 'Could not store purchase-return state.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not initialize the secure purchase return. Please try again.', 'ai-for-seo' ), 571818325 );
		return;
	}

	$has_purchased_something = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );
	$rotation_claim_token    = ai4seo_prepare_first_purchase_api_password_rotation_claim();

	// Never open an unattributed first checkout: possession of the free password alone must not
	// be enough to claim the replacement paid-account credential.
	if ( ! $has_purchased_something && '' === $rotation_claim_token ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
		ai4seo_debug_message( 601818325, 'Could not prepare the first-purchase API-password rotation claim.', true );
		ai4seo_send_ajax_error( esc_html__( 'Could not initialize secure account protection for this purchase. Please try again.', 'ai-for-seo' ), 601818325 );
		return;
	}

	// Keep return state and the optional rotation claim together in one checkout request.
	$endpoint_parameters = ai4seo_build_credit_pack_purchase_parameters(
		$stripe_price_id,
		$redirect_url,
		$rotation_claim_token
	);

	$response = ai4seo_robhub_api()->call( 'client/init-purchase', $endpoint_parameters );

	// Reject the complete initialization when RobHub did not confirm checkout creation.
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
		ai4seo_debug_message( 561818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 561818325 );
		return;
	}

	if ( ! isset( $response['data']['purchase_url'] ) || ! $response['data']['purchase_url'] ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
		ai4seo_debug_message( 581818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 581818325 );
		return;
	}

	// Treat the API-provided redirect as untrusted until it passes the Stripe HTTPS boundary.
	$purchase_url = ai4seo_normalize_purchase_url( $response['data']['purchase_url'] );

	if ( '' === $purchase_url ) {
		ai4seo_delete_purchase_return_token( $purchase_return_token );
		ai4seo_debug_message( 591818325, 'Invalid response from RobHub API.', true );
		ai4seo_send_ajax_error( esc_html__( 'Invalid response from RobHub API', 'ai-for-seo' ), 591818325 );
		return;
	}

	// Start account polling and credential reconciliation as soon as checkout can be opened.
	ai4seo_record_purchase_activity();

	ai4seo_send_ajax_success( array( 'purchase_url' => $purchase_url ) );
}


/**
 * Called via AJAX - submit plugin deactivation feedback.
 *
 * @return void
 */
function ai4seo_submit_feedback() {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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


/**
 * Capture the authoritative upper post-ID boundary for a selected generated-data reset.
 *
 * @param array         $post_types Selected post types.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return int|WP_Error High-water post ID, zero for no matching posts, or a storage error.
 */
function ai4seo_read_generated_data_reset_high_water_post_id( array $post_types, ?callable $ownership_checkpoint = null ) {
	global $wpdb;

	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return 0;
	}

	$high_water_query = ai4seo_prepare_database_query(
		'SELECT MAX(ID) FROM {{posts_table}} WHERE post_type IN ({{post_types}})',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_types'  => ai4seo_database_list_binding( '%s', $post_types ),
		)
	);

	if ( false === $high_water_query ) {
		return new WP_Error(
			784322923,
			esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
		);
	}

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
		);
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this finite site/post-type boundary; reset targeting requires current storage.
	$high_water_post_id = $wpdb->get_var( $high_water_query );
	$database_error     = (string) $wpdb->last_error;

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
		);
	}

	if ( '' !== $database_error ) {
		return new WP_Error(
			784322923,
			esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
		);
	}

	if ( null === $high_water_post_id ) {
		return 0;
	}

	$high_water_post_id = ai4seo_normalize_database_id( $high_water_post_id );

	if ( false === $high_water_post_id ) {
		return new WP_Error(
			784322923,
			esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
		);
	}

	return $high_water_post_id;
}


/**
 * Visit authoritative generated-data owners in bounded, strictly increasing primary-key pages.
 *
 * The caller-provided visitor receives only one database-sized page at a time. Ownership is
 * reverified before and after every query and before and after every visited page, so a destructive
 * visitor cannot continue after either reset fence is lost.
 *
 * @param array         $post_types Selected post types.
 * @param int           $high_water_post_id Fixed upper post-ID boundary for this visit.
 * @param callable      $post_id_page_visitor Receives one canonical post-ID page and returns true or WP_Error.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @param int|null      $visited_post_id_count Receives the count from fully verified pages only.
 * @return true|WP_Error True on success, or an ownership/storage/visitor error.
 */
function ai4seo_visit_generated_data_post_id_pages_by_post_types(
	array $post_types,
	int $high_water_post_id,
	callable $post_id_page_visitor,
	?callable $ownership_checkpoint = null,
	?int &$visited_post_id_count = null
) {
	global $wpdb;

	$post_types            = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
	$visited_post_id_count = 0;

	if ( ! $post_types || $high_water_post_id < 0 ) {
		return new WP_Error(
			784322923,
			esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
		);
	}

	if ( 0 === $high_water_post_id ) {
		return ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint )
			? true
			: new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
	}

	$database_chunk_size = ai4seo_get_database_chunk_size();
	$legacy_bindings     = ai4seo_get_legacy_active_metadata_database_query_bindings();

	if ( $database_chunk_size <= 0 || ! $legacy_bindings ) {
		return new WP_Error(
			784322923,
			esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
		);
	}

	$post_id_cursor = 0;
	$metadata_keys  = array(
		AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY,
		AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY,
	);

	while ( $post_id_cursor < $high_water_post_id ) {
		$query_bindings = array_merge(
			$legacy_bindings,
			array(
				'posts_table'        => ai4seo_database_identifier_binding( 'table.posts' ),
				'post_types'         => ai4seo_database_list_binding( '%s', $post_types ),
				'post_id_cursor'     => ai4seo_database_scalar_binding( '%d', $post_id_cursor ),
				'high_water_post_id' => ai4seo_database_scalar_binding( '%d', $high_water_post_id ),
				'metadata_keys'      => ai4seo_database_list_binding( '%s', $metadata_keys ),
				'query_limit'        => ai4seo_database_scalar_binding( '%d', $database_chunk_size ),
			)
		);
		$post_ids_query = ai4seo_prepare_database_query(
			'SELECT DISTINCT p.ID
			FROM {{posts_table}} AS p
			INNER JOIN {{postmeta_table}} AS pm ON pm.post_id = p.ID
			WHERE p.post_type IN ({{post_types}})
			AND p.ID > {{post_id_cursor}}
			AND p.ID <= {{high_water_post_id}}
			AND (
				pm.meta_key IN ({{metadata_keys}})
				OR (
					(
						pm.meta_key LIKE {{legacy_pattern_0}} OR
						pm.meta_key LIKE {{legacy_pattern_1}} OR
						pm.meta_key LIKE {{legacy_pattern_2}} OR
						pm.meta_key LIKE {{legacy_pattern_3}} OR
						pm.meta_key LIKE {{legacy_pattern_4}} OR
						pm.meta_key LIKE {{legacy_pattern_5}} OR
						pm.meta_key LIKE {{legacy_pattern_6}} OR
						pm.meta_key LIKE {{legacy_pattern_7}} OR
						pm.meta_key LIKE {{legacy_pattern_8}} OR
						pm.meta_key LIKE {{legacy_pattern_9}}
					)
					AND BINARY pm.meta_key REGEXP BINARY {{legacy_key_regexp}}
				)
			)
			ORDER BY p.ID ASC
			LIMIT {{query_limit}}',
			$query_bindings
		);

		if ( false === $post_ids_query ) {
			return new WP_Error(
				784322923,
				esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
			);
		}

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		$wpdb->last_error = '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the bounded authoritative reset-target page.
		$this_post_id_page = $wpdb->get_col( $post_ids_query );
		$database_error    = (string) $wpdb->last_error;

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		if ( '' !== $database_error || ! is_array( $this_post_id_page ) ) {
			return new WP_Error(
				784322923,
				esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
			);
		}

		if ( ! $this_post_id_page ) {
			break;
		}

		$normalized_post_id_page = ai4seo_normalize_database_ids( $this_post_id_page );

		if ( false === $normalized_post_id_page || count( $normalized_post_id_page ) !== count( $this_post_id_page ) ) {
			return new WP_Error(
				784322923,
				esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
			);
		}

		$previous_post_id = $post_id_cursor;

		foreach ( $normalized_post_id_page as $this_post_id ) {
			if ( $this_post_id <= $previous_post_id || $this_post_id > $high_water_post_id ) {
				return new WP_Error(
					784322923,
					esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
				);
			}

			$previous_post_id = $this_post_id;
		}

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		try {
			$page_visit_result = call_user_func( $post_id_page_visitor, $normalized_post_id_page );
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 984321744, 'Unexpected generated-data reset page visitor failure: ' . $throwable->getMessage(), true );
			$page_visit_result = new WP_Error(
				784322923,
				esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
			);
		}

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		if ( is_wp_error( $page_visit_result ) ) {
			return $page_visit_result;
		}

		if ( true !== $page_visit_result ) {
			return new WP_Error(
				784322923,
				esc_html__( 'Could not verify generated metadata before resetting it. Please try again.', 'ai-for-seo' )
			);
		}

		$visited_post_id_count += count( $normalized_post_id_page );
		$post_id_cursor         = $previous_post_id;
	}

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
		);
	}

	return true;
}


/**
 * Read authoritative generated-data owners for selected post types in bounded primary-key pages.
 *
 * This compatibility reader intentionally returns the complete ID collection. Reset code should
 * use the bounded page visitor directly so site-wide target sets are never retained in memory.
 *
 * @param array         $post_types Selected post types.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return array|WP_Error Canonical post IDs or an authoritative-storage error.
 */
function ai4seo_read_generated_data_post_ids_by_post_types( array $post_types, ?callable $ownership_checkpoint = null ) {
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return array();
	}

	$high_water_post_id = ai4seo_read_generated_data_reset_high_water_post_id( $post_types, $ownership_checkpoint );

	if ( is_wp_error( $high_water_post_id ) ) {
		return $high_water_post_id;
	}

	$post_ids     = array();
	$visit_result = ai4seo_visit_generated_data_post_id_pages_by_post_types(
		$post_types,
		$high_water_post_id,
		static function ( array $post_id_page ) use ( &$post_ids ): bool {
			foreach ( $post_id_page as $post_id ) {
				$post_ids[] = $post_id;
			}

			return true;
		},
		$ownership_checkpoint
	);

	return is_wp_error( $visit_result ) ? $visit_result : $post_ids;
}


/**
 * Reads selected post IDs that currently have a specific postmeta key.
 *
 * @param array     $post_ids Post IDs.
 * @param string    $meta_key Postmeta key.
 * @param bool|null $database_read_failed Receives whether query compilation or execution failed.
 * @return array Post IDs that have the postmeta key.
 */
function ai4seo_read_post_ids_with_postmeta_key( array $post_ids, string $meta_key, ?bool &$database_read_failed = null ): array {
	global $wpdb;

	$post_ids             = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$meta_key             = sanitize_key( $meta_key );
	$database_read_failed = false;

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

		$sql = ai4seo_prepare_database_query(
			'SELECT DISTINCT post_id
			FROM {{postmeta_table}}
			WHERE meta_key = {{requested_meta_key}}
			AND post_id IN ({{post_ids}})',
			array(
				'postmeta_table'     => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'requested_meta_key' => ai4seo_database_scalar_binding( '%s', $meta_key ),
				'post_ids'           => ai4seo_database_list_binding( '%d', $this_post_ids_chunk ),
			)
		);

		if ( false === $sql ) {
			$database_read_failed = true;
			ai4seo_debug_message( 984321709, 'Could not prepare the postmeta-key lookup query.', true );
			return array();
		}

		// Ignore an unrelated earlier wpdb error when classifying this lookup's execution result.
		$wpdb->last_error = '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; this one-shot reset lookup must observe current postmeta before the same request mutates it.
		$this_post_ids_with_meta_key = $wpdb->get_col( $sql );

		if ( $wpdb->last_error || ! is_array( $this_post_ids_with_meta_key ) ) {
			$database_read_failed = true;
			$database_error       = $wpdb->last_error ? 'Database error: ' . $wpdb->last_error : 'The postmeta-key lookup returned an invalid result.';
			ai4seo_debug_message( 984321709, $database_error, true );
			return array();
		}

		if ( ! $this_post_ids_with_meta_key ) {
			continue;
		}

		$post_ids_with_meta_key = array_merge( $post_ids_with_meta_key, $this_post_ids_with_meta_key );
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $post_ids_with_meta_key ) ) ) );
}


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

	$full_summary_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );

	if ( null === $full_summary_snapshot ) {
		return false;
	}

	$normalized_full_summary = $full_summary_snapshot['exists']
		? ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $full_summary_snapshot['value'] )
		: null;

	// ID-only cleanup cannot be reconstructed from legacy totals. Preserve that storage without synthesizing a full pair.
	if ( ! is_array( $normalized_full_summary )
		|| ! ai4seo_get_comparable_generation_status_summary( $normalized_full_summary ) ) {
		return true;
	}

	$post_id_lookup = array_flip( $post_ids );
	return ai4seo_mutate_generation_status_summary(
		static function ( array $generation_status_summary ) use ( $option_name, $post_id_lookup ): array {
			$original_generation_status_summary = $generation_status_summary;

			if ( ! isset( $generation_status_summary[ $option_name ] )
				|| ! is_array( $generation_status_summary[ $option_name ] ) ) {
				return array(
					'summary' => $generation_status_summary,
					'changed' => false,
				);
			}

			foreach ( $generation_status_summary[ $option_name ] as $this_post_type => $this_summary_entry ) {
				if ( ! is_array( $this_summary_entry )
					|| ! isset( $this_summary_entry['post_ids'] )
					|| ! is_array( $this_summary_entry['post_ids'] ) ) {
					continue;
				}

				$this_entry_post_ids = array_values(
					array_filter(
						$this_summary_entry['post_ids'],
						static function ( int $post_id ) use ( $post_id_lookup ): bool {
							return ! isset( $post_id_lookup[ $post_id ] );
						}
					)
				);

				if ( ! $this_entry_post_ids ) {
					unset( $generation_status_summary[ $option_name ][ $this_post_type ] );
					continue;
				}

				$generation_status_summary[ $option_name ][ $this_post_type ] = array(
					'total'    => count( $this_entry_post_ids ),
					'post_ids' => $this_entry_post_ids,
				);
			}

			return array(
				'summary' => $generation_status_summary,
				'changed' => ai4seo_get_comparable_generation_status_summary( $original_generation_status_summary )
					!== ai4seo_get_comparable_generation_status_summary( $generation_status_summary ),
			);
		},
		true
	);
}

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
	if ( ! $post_ids && ! $post_types ) {
		return true;
	}

	$post_id_lookup        = array_flip( $post_ids );
	$full_summary_mutation = static function ( array $generation_status_summary ) use ( $post_id_lookup, $post_types ): array {
		$original_generation_status_summary = $generation_status_summary;

		foreach ( $generation_status_summary as $this_option_name => $this_post_type_entries ) {
			if ( ! is_array( $this_post_type_entries ) ) {
				continue;
			}

			foreach ( $this_post_type_entries as $this_post_type => $this_summary_entry ) {
				if ( ! $post_id_lookup && in_array( $this_post_type, $post_types, true ) ) {
					unset( $generation_status_summary[ $this_option_name ][ $this_post_type ] );
					continue;
				}

				if ( ! $post_id_lookup
					|| ! is_array( $this_summary_entry )
					|| ! isset( $this_summary_entry['post_ids'] )
					|| ! is_array( $this_summary_entry['post_ids'] ) ) {
					continue;
				}

				$this_entry_post_ids = array_values(
					array_filter(
						$this_summary_entry['post_ids'],
						static function ( int $post_id ) use ( $post_id_lookup ): bool {
							return ! isset( $post_id_lookup[ $post_id ] );
						}
					)
				);

				if ( ! $this_entry_post_ids ) {
					unset( $generation_status_summary[ $this_option_name ][ $this_post_type ] );
					continue;
				}

				$generation_status_summary[ $this_option_name ][ $this_post_type ] = array(
					'total'    => count( $this_entry_post_ids ),
					'post_ids' => $this_entry_post_ids,
				);
			}
		}

		return array(
			'summary' => $generation_status_summary,
			'changed' => ai4seo_get_comparable_generation_status_summary( $original_generation_status_summary )
				!== ai4seo_get_comparable_generation_status_summary( $generation_status_summary ),
		);
	};

	$full_summary_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );
	if ( null === $full_summary_snapshot ) {
		return false;
	}

	$normalized_full_summary = $full_summary_snapshot['exists']
		? ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $full_summary_snapshot['value'] )
		: null;

	// A nonempty full membership is authoritative and can be safely re-mutated after every CAS conflict.
	if ( is_array( $normalized_full_summary ) && ai4seo_get_comparable_generation_status_summary( $normalized_full_summary ) ) {
		return ai4seo_mutate_generation_status_summary(
			$full_summary_mutation,
			true
		);
	}

	if ( ! $post_types ) {
		return true;
	}

	// Legacy totals have no memberships to rebuild, so remove only requested post-type counters via their own CAS path.
	$legacy_totals_were_mutated = ai4seo_mutate_legacy_generation_status_summary_totals(
		static function ( array $generation_status_summary_totals ) use ( $post_types ): array {
			$original_generation_status_summary_totals = $generation_status_summary_totals;

			foreach ( $generation_status_summary_totals as $this_option_name => $this_post_type_entries ) {
				if ( ! is_array( $this_post_type_entries ) ) {
					continue;
				}

				foreach ( $post_types as $this_post_type ) {
					unset( $generation_status_summary_totals[ $this_option_name ][ $this_post_type ] );
				}
			}

			return array(
				'totals'  => $generation_status_summary_totals,
				'changed' => $original_generation_status_summary_totals !== $generation_status_summary_totals,
			);
		}
	);

	if ( ! $legacy_totals_were_mutated ) {
		return false;
	}

	// A full writer may have committed while the legacy totals CAS retried. Recheck before reporting cleanup complete.
	$full_summary_snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );

	if ( null === $full_summary_snapshot ) {
		return false;
	}

	$normalized_full_summary = $full_summary_snapshot['exists']
		? ai4seo_normalize_generation_status_summary_for_incremental_sync_value( $full_summary_snapshot['value'] )
		: null;

	if ( ! is_array( $normalized_full_summary )
		|| ! ai4seo_get_comparable_generation_status_summary( $normalized_full_summary ) ) {
		return true;
	}

	return ai4seo_mutate_generation_status_summary( $full_summary_mutation, true );
}

/**
 * Persist an authoritative rebuild request after a generated-data reset fails partway.
 *
 * @return bool True when durable rebuild state and any required event were verified.
 */
function ai4seo_schedule_generated_data_reset_rebuild(): bool {
	$rebuild_was_scheduled = ai4seo_schedule_generation_status_summary_rebuild();

	if ( ! $rebuild_was_scheduled ) {
		ai4seo_debug_message( 984321720, 'Could not persist a generation-status rebuild request after a partial generated-data reset failure.', true );
	}

	return $rebuild_was_scheduled;
}


/**
 * Run one optional reset-ownership checkpoint with a strict, exception-safe contract.
 *
 * @param callable|null $ownership_checkpoint Optional ownership callback.
 * @return bool True when no callback is required or it returned strict true.
 */
function ai4seo_run_generated_data_reset_ownership_checkpoint( ?callable $ownership_checkpoint = null ): bool {
	if ( null === $ownership_checkpoint ) {
		return true;
	}

	try {
		return true === call_user_func( $ownership_checkpoint );
	} catch ( Throwable $throwable ) {
		ai4seo_debug_message( 984321724, 'Generated-data reset ownership checkpoint failed: ' . $throwable->getMessage(), true );
		return false;
	}
}


/**
 * Renew and verify both exclusive reset fences on their existing owner connection/request.
 *
 * @param string $analysis_lock_name Site-scoped analysis advisory-lock name.
 * @param string $post_id_critical_section Shared post-ID semaphore name.
 * @return bool True only while both current ownership claims remain valid.
 */
function ai4seo_renew_generated_data_reset_exclusive_ownership( string $analysis_lock_name, string $post_id_critical_section ): bool {
	return '' !== $analysis_lock_name
		&& '' !== $post_id_critical_section
		&& ai4seo_is_database_advisory_lock_owned_by_current_connection( $analysis_lock_name )
		&& ai4seo_renew_semaphore( $post_id_critical_section );
}


/**
 * Run an existing multi-query database helper with a lease checkpoint before every emitted query.
 *
 * The query filter is request-local and recursion-guarded so checkpoint SQL is never re-entered. If
 * ownership is lost, the pending helper query is replaced with a harmless empty read and the wrapper
 * fails closed; no later helper query can mutate storage.
 *
 * @param callable      $operation Existing database operation.
 * @param callable|null $ownership_checkpoint Optional ownership callback.
 * @return bool True only when the operation and every ownership checkpoint succeeded.
 */
function ai4seo_run_generated_data_reset_database_operation(
	callable $operation,
	?callable $ownership_checkpoint = null
): bool {
	if ( null === $ownership_checkpoint ) {
		try {
			return true === call_user_func( $operation );
		} catch ( Throwable $throwable ) {
			return false;
		}
	}

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return false;
	}

	$is_checkpoint_query         = false;
	$ownership_checkpoint_failed = false;
	$query_checkpoint            = static function ( string $query ) use ( $ownership_checkpoint, &$is_checkpoint_query, &$ownership_checkpoint_failed ): string {
		if ( $is_checkpoint_query ) {
			return $query;
		}

		if ( $ownership_checkpoint_failed ) {
			return 'SELECT 1 WHERE 1 = 0';
		}

		$is_checkpoint_query = true;

		try {
			$checkpoint_succeeded = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );
		} finally {
			$is_checkpoint_query = false;
		}

		if ( ! $checkpoint_succeeded ) {
			$ownership_checkpoint_failed = true;
			return 'SELECT 1 WHERE 1 = 0';
		}

		return $query;
	};
	$operation_succeeded         = false;

	add_filter( 'query', $query_checkpoint, PHP_INT_MAX );

	try {
		$operation_succeeded = true === call_user_func( $operation );
	} catch ( Throwable $throwable ) {
		$operation_succeeded = false;
	} finally {
		remove_filter( 'query', $query_checkpoint, PHP_INT_MAX );
	}

	$ownership_is_held = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );

	return $operation_succeeded && ! $ownership_checkpoint_failed && $ownership_is_held;
}


/**
 * Remove selected IDs from every generation-status option while its shared fence is already held.
 *
 * @param array $post_ids Canonical post IDs to remove.
 * @return bool True only when every requested absence was verified.
 */
function ai4seo_remove_post_ids_from_generation_status_options_under_lock( array $post_ids ): bool {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	if ( ! $post_ids ) {
		return false;
	}

	$removals = array();

	foreach ( AI4SEO_ALL_POST_ID_OPTIONS as $option_name ) {
		$removals[ $option_name ] = $post_ids;
	}

	$did_change = false;

	return ai4seo_apply_normalized_post_id_option_transition_under_lock(
		array(),
		$removals,
		$did_change
	);
}


/**
 * Resets generated data for specific post IDs and selected post types.
 *
 * @param array         $post_ids Generated-data post IDs.
 * @param array         $post_types Selected post types.
 * @param bool          $post_id_transition_lock_is_held Whether the caller already owns the shared post-ID fence.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return bool True on success.
 */
function ai4seo_reset_generated_data_for_post_ids(
	array $post_ids,
	array $post_types,
	bool $post_id_transition_lock_is_held = false,
	?callable $ownership_checkpoint = null
): bool {
	global $wpdb;

	$post_ids   = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! $post_types ) {
		return false;
	}

	if ( ! $post_ids ) {
		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return false;
		}

		$summary_was_reset = ai4seo_remove_post_ids_from_generation_status_summary( array(), $post_types );
		$ownership_is_held = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );

		if ( ( ! $summary_was_reset || ! $ownership_is_held ) && ! $post_id_transition_lock_is_held ) {
			ai4seo_schedule_generated_data_reset_rebuild();
		}

		return $summary_was_reset && $ownership_is_held;
	}

	$database_chunk_size     = ai4seo_get_database_chunk_size();
	$post_id_chunks          = 0 < $database_chunk_size ? array_chunk( $post_ids, $database_chunk_size ) : array();
	$postmeta_keys_to_delete = array(
		AI4SEO_POST_META_ACTIVE_METADATA_META_KEY,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY,
		AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY,
	);

	if ( ! $post_id_chunks ) {
		return false;
	}

	foreach ( $post_id_chunks as $this_post_id_chunk ) {
		$legacy_delete_succeeded = ai4seo_run_generated_data_reset_database_operation(
			static function () use ( $this_post_id_chunk ): bool {
				return ai4seo_delete_legacy_active_metadata_for_post_ids( $this_post_id_chunk );
			},
			$ownership_checkpoint
		);

		if ( ! $legacy_delete_succeeded ) {
			if ( ! $post_id_transition_lock_is_held ) {
				ai4seo_schedule_generated_data_reset_rebuild();
			}

			return false;
		}

		foreach ( $postmeta_keys_to_delete as $this_postmeta_key_to_delete ) {
			$possibly_deleted_post_ids = array();

			if ( ! ai4seo_delete_postmeta_for_post_ids_and_meta_key(
				$this_post_id_chunk,
				$this_postmeta_key_to_delete,
				$possibly_deleted_post_ids,
				$ownership_checkpoint
			) ) {
				if ( ! $post_id_transition_lock_is_held ) {
					ai4seo_schedule_generated_data_reset_rebuild();
				}

				return false;
			}
		}
	}

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return false;
	}

	// The AJAX reset already owns the shared fence; direct callers retain the public fenced transition.
	$post_id_options_were_reset = $post_id_transition_lock_is_held
		? ai4seo_remove_post_ids_from_generation_status_options_under_lock( $post_ids )
		: ai4seo_remove_post_ids_from_options( AI4SEO_ALL_POST_ID_OPTIONS, $post_ids );
	$post_id_options_error      = ! $post_id_options_were_reset && is_object( $wpdb ) ? (string) $wpdb->last_error : '';

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		if ( ! $post_id_transition_lock_is_held ) {
			ai4seo_schedule_generated_data_reset_rebuild();
		}

		return false;
	}

	$summary_was_reset = ai4seo_remove_post_ids_from_generation_status_summary( $post_ids, $post_types );
	$ownership_is_held = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );

	if ( ! $post_id_options_were_reset && '' !== $post_id_options_error && is_object( $wpdb ) ) {
		$wpdb->last_error = $post_id_options_error;
	}

	if ( ( ! $post_id_options_were_reset || ! $summary_was_reset || ! $ownership_is_held ) && ! $post_id_transition_lock_is_held ) {
		ai4seo_schedule_generated_data_reset_rebuild();
	}

	return $post_id_options_were_reset && $summary_was_reset && $ownership_is_held;
}


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

	return true === ai4seo_reset_plugin_storage_with_exclusive_ownership( 'metadata', $post_types, false );
}


/**
 * Deletes every option in a reset-owned family without short-circuiting later cleanup.
 *
 * @param array         $option_names Exact option names owned by the reset operation.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return bool True only when every option was deleted or already absent.
 */
function ai4seo_delete_plugin_reset_option_family( array $option_names, ?callable $ownership_checkpoint = null ): bool {
	global $wpdb;

	$all_options_were_deleted = true;
	$first_database_error     = '';
	$seen_option_names        = array();

	foreach ( $option_names as $option_name ) {
		if ( ! is_string( $option_name ) ) {
			$all_options_were_deleted = false;
			continue;
		}

		$option_name = trim( $option_name );

		if ( '' === $option_name ) {
			$all_options_were_deleted = false;
			continue;
		}

		if ( isset( $seen_option_names[ $option_name ] ) ) {
			continue;
		}

		$seen_option_names[ $option_name ] = true;

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			$all_options_were_deleted = false;
			continue;
		}

		$this_option_was_deleted = ai4seo_delete_option( $option_name );

		if ( ! $this_option_was_deleted && '' === $first_database_error && is_object( $wpdb ) ) {
			$first_database_error = (string) $wpdb->last_error;
		}

		$all_options_were_deleted = $this_option_was_deleted && $all_options_were_deleted;
	}

	if ( ! $all_options_were_deleted && '' !== $first_database_error && is_object( $wpdb ) ) {
		$wpdb->last_error = $first_database_error;
	}

	return $all_options_were_deleted;
}


/**
 * Deletes the generation-summary pair and commit marker with checked snapshot retries.
 *
 * Totals are removed before the full membership map so a partial database failure can never
 * leave a compact totals-only value looking newer than the authoritative full summary.
 *
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return bool Whether both public rows and their commit marker are confirmed absent.
 */
function ai4seo_delete_generation_status_summary_cache_pair( ?callable $ownership_checkpoint = null ): bool {
	$option_names  = array(
		AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME,
		AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME,
	);
	$attempt_limit = ai4seo_get_generation_status_summary_persistence_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			break;
		}

		$marker_invalidation = ai4seo_invalidate_generation_status_summary_pair_state();

		if ( null === $marker_invalidation ) {
			break;
		}

		if ( ! $marker_invalidation ) {
			continue;
		}

		$lost_snapshot_race = false;

		foreach ( $option_names as $option_name ) {
			if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
				$lost_snapshot_race = null;
				break;
			}

			$option_snapshot = ai4seo_get_raw_option_snapshot( $option_name );

			if ( null === $option_snapshot ) {
				$lost_snapshot_race = null;
				break;
			}

			$delete_result = ai4seo_compare_and_delete_option_snapshot( $option_name, $option_snapshot );

			if ( null === $delete_result ) {
				$lost_snapshot_race = null;
				break;
			}

			if ( ! $delete_result ) {
				$lost_snapshot_race = true;
				break;
			}
		}

		if ( null === $lost_snapshot_race ) {
			break;
		}

		if ( $lost_snapshot_race ) {
			continue;
		}

		// A concurrent writer may have published a marker while the two public rows were removed.
		if (
			! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint )
			|| true !== ai4seo_invalidate_generation_status_summary_pair_state()
		) {
			continue;
		}

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			break;
		}

		$full_readback   = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME );
		$totals_readback = ai4seo_get_raw_option_snapshot( AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME );
		$marker_readback = ai4seo_get_raw_option_snapshot( ai4seo_get_generation_status_summary_pair_state_option_name() );

		if ( null === $full_readback || null === $totals_readback || null === $marker_readback ) {
			break;
		}

		if ( ! $full_readback['exists'] && ! $totals_readback['exists'] && ! $marker_readback['exists'] ) {
			ai4seo_reset_generation_status_summary_request_cache();
			return true;
		}
	}

	foreach ( array_merge( $option_names, array( ai4seo_get_generation_status_summary_pair_state_option_name() ) ) as $option_name ) {
		ai4seo_invalidate_option_cache( $option_name );
	}

	ai4seo_reset_generation_status_summary_request_cache();
	return false;
}


/**
 * Read and classify both worker-owned Processing families and their durable claim leases.
 *
 * Existing malformed rows fail closed. A Processing membership blocks reset only while its matching
 * lease is valid and unexpired; expired/missing leases are abandoned ownership that an explicit reset
 * may consume under the same post-ID fence. Lease-only entries are cleaned only when in reset scope.
 *
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return array|WP_Error Classified ownership state, or an authoritative-read error.
 */
function ai4seo_read_generated_data_reset_processing_post_ids_under_lock( ?callable $ownership_checkpoint = null ) {
	$processing_option_names = array(
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$processing_state        = array(
		'active_post_ids'                     => array(),
		'recoverable_post_ids'                => array(),
		'recoverable_post_ids_by_option'      => array(),
		'processing_post_ids_by_option'       => array(),
		'processing_claim_leases_by_option'   => array(),
		'stale_lease_post_ids_by_option'      => array(),
		'processing_claim_lease_option_names' => array(),
	);

	foreach ( $processing_option_names as $processing_option_name ) {
		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		$processing_snapshot = ai4seo_get_raw_option_snapshot( $processing_option_name );

		if ( null === $processing_snapshot ) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting metadata. Please try again.', 'ai-for-seo' )
			);
		}

		$processing_option_value = ai4seo_safe_maybe_unserialize( $processing_snapshot['value'] );

		if ( is_string( $processing_option_value ) && '' !== $processing_option_value && ai4seo_is_json( $processing_option_value ) ) {
			$processing_option_value = json_decode( $processing_option_value, true );
		}

		// An existing malformed Processing row cannot be interpreted as authoritative emptiness.
		if ( $processing_snapshot['exists'] && ! is_array( $processing_option_value ) ) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
			);
		}

		$processing_option_value = is_array( $processing_option_value ) ? $processing_option_value : array();

		foreach ( $processing_option_value as $raw_processing_post_id ) {
			if ( false === ai4seo_normalize_option_post_id( $raw_processing_post_id ) ) {
				return new WP_Error(
					784322920,
					esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
				);
			}
		}

		$processing_post_ids = ai4seo_normalize_option_post_id_collection( $processing_option_value );

		$lease_option_name = ai4seo_get_processing_claim_lease_option_name( $processing_option_name );

		if ( '' === $lease_option_name ) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
			);
		}

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		}

		$lease_snapshot = ai4seo_get_raw_option_snapshot( $lease_option_name );

		if ( null === $lease_snapshot ) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
			);
		}

		$leases = array();

		if ( $lease_snapshot['exists']
			&& (
				! ai4seo_validate_processing_claim_leases( $lease_snapshot['value'], $leases )
				|| ! ai4seo_validate_processing_claim_lease_context( $processing_option_name, $leases )
			)
		) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
			);
		}

		$processing_lookup = array_fill_keys( $processing_post_ids, true );
		$recoverable_ids   = array();
		$stale_lease_ids   = array();

		foreach ( $processing_post_ids as $post_id ) {
			if ( ! empty( $leases[ $post_id ]['preserve_new_queue_memberships'] ) ) {
				// Disabled-queue scrub rollback owners are orphan-only and cannot classify live Processing work.
				return new WP_Error(
					784322920,
					esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
				);
			}
		}

		foreach ( $processing_post_ids as $post_id ) {
			if ( isset( $leases[ $post_id ] ) && ai4seo_is_processing_claim_lease_active( $leases[ $post_id ] ) ) {
				$processing_state['active_post_ids'][] = $post_id;
			} else {
				$recoverable_ids[] = $post_id;
			}
		}

		foreach ( $leases as $post_id => $lease ) {
			if ( ! isset( $processing_lookup[ $post_id ] ) || ! ai4seo_is_processing_claim_lease_active( $lease ) ) {
				$stale_lease_ids[] = $post_id;
			}
		}

		$processing_state['recoverable_post_ids']                                      = array_merge(
			$processing_state['recoverable_post_ids'],
			$recoverable_ids
		);
		$processing_state['recoverable_post_ids_by_option'][ $processing_option_name ] = $recoverable_ids;
		$processing_state['processing_post_ids_by_option'][ $processing_option_name ]  = $processing_post_ids;
		$processing_state['processing_claim_leases_by_option'][ $processing_option_name ] = $leases;
		$processing_state['stale_lease_post_ids_by_option'][ $processing_option_name ]    = array_values( array_unique( $stale_lease_ids ) );
		$processing_state['processing_claim_lease_option_names'][]                        = $lease_option_name;
	}

	$processing_state['active_post_ids']                     = array_values( array_unique( $processing_state['active_post_ids'] ) );
	$processing_state['recoverable_post_ids']                = array_values( array_unique( $processing_state['recoverable_post_ids'] ) );
	$processing_state['processing_claim_lease_option_names'] = array_values(
		array_unique( $processing_state['processing_claim_lease_option_names'] )
	);

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
		);
	}

	return $processing_state;
}


/**
 * Find Processing IDs owned by the selected reset scope from current primary storage.
 *
 * Processing entries already present in the reset-target snapshot are known matches. Every other
 * entry is resolved from a bounded exact posts-table snapshot; only a successful missing row is
 * treated as absent, while query compilation, execution, duplicate, and malformed-row failures
 * abort the reset.
 *
 * @param array         $processing_post_ids Current Processing IDs.
 * @param array         $reset_post_ids Generated-data IDs selected for removal.
 * @param array         $post_types Selected post types.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return array|WP_Error Processing IDs in scope, or an ownership error.
 */
function ai4seo_get_processing_post_ids_for_generated_data_reset_targets(
	array $processing_post_ids,
	array $reset_post_ids,
	array $post_types,
	?callable $ownership_checkpoint = null
) {
	global $wpdb;

	$processing_post_ids  = array_values( array_unique( array_filter( array_map( 'absint', $processing_post_ids ) ) ) );
	$reset_post_id_lookup = array_fill_keys(
		array_values( array_unique( array_filter( array_map( 'absint', $reset_post_ids ) ) ) ),
		true
	);
	$post_type_lookup     = array_fill_keys(
		array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) ),
		true
	);
	$active_post_ids      = array_values( array_intersect( $processing_post_ids, array_keys( $reset_post_id_lookup ) ) );
	$unmatched_post_ids   = array_values( array_diff( $processing_post_ids, $active_post_ids ) );

	if ( $unmatched_post_ids ) {
		$database_chunk_size = ai4seo_get_database_chunk_size();

		if ( $database_chunk_size <= 0 ) {
			return new WP_Error(
				784322920,
				esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
			);
		}

		$post_types_by_post_id = array();

		foreach ( array_chunk( $unmatched_post_ids, $database_chunk_size ) as $this_post_id_chunk ) {
			$post_type_query = ai4seo_prepare_database_query(
				'SELECT ID, post_type
				FROM {{posts_table}}
				WHERE ID IN ({{post_ids}})
				ORDER BY ID ASC',
				array(
					'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
					'post_ids'    => ai4seo_database_list_binding( '%d', $this_post_id_chunk ),
				)
			);

			if ( false === $post_type_query ) {
				return new WP_Error(
					784322920,
					esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
				);
			}

			if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
				return new WP_Error(
					784322919,
					esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
				);
			}

			$wpdb->last_error = '';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this bounded current-post snapshot under both reset fences.
			$this_post_type_rows = $wpdb->get_results( $post_type_query, ARRAY_A );
			$database_error      = (string) $wpdb->last_error;

			if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
				return new WP_Error(
					784322919,
					esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
				);
			}

			if ( '' !== $database_error || ! is_array( $this_post_type_rows ) ) {
				return new WP_Error(
					784322920,
					esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
				);
			}

			$this_post_id_lookup = array_fill_keys( $this_post_id_chunk, true );

			foreach ( $this_post_type_rows as $post_type_row ) {
				if (
					! is_array( $post_type_row )
					|| 2 !== count( $post_type_row )
					|| ! array_key_exists( 'ID', $post_type_row )
					|| ! array_key_exists( 'post_type', $post_type_row )
				) {
					return new WP_Error(
						784322920,
						esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
					);
				}

				$post_id   = ai4seo_normalize_database_id( $post_type_row['ID'] );
				$post_type = $post_type_row['post_type'];

				if (
					false === $post_id
					|| ! isset( $this_post_id_lookup[ $post_id ] )
					|| isset( $post_types_by_post_id[ $post_id ] )
					|| ! is_string( $post_type )
					|| '' === $post_type
					|| sanitize_key( $post_type ) !== $post_type
				) {
					return new WP_Error(
						784322920,
						esc_html__( 'Could not verify active generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
					);
				}

				$post_types_by_post_id[ $post_id ] = $post_type;
			}
		}

		foreach ( $post_types_by_post_id as $post_id => $post_type ) {
			if ( isset( $post_type_lookup[ $post_type ] ) ) {
				$active_post_ids[] = $post_id;
			}
		}
	}

	if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
		);
	}

	return array_values( array_unique( $active_post_ids ) );
}


/**
 * Remove abandoned Processing memberships and stale leases while the shared post-ID fence is held.
 *
 * @param array         $processing_state Classified Processing and claim-lease state.
 * @param array         $target_post_ids Processing/lease IDs that belong to this reset scope.
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @param bool          $reconcile_orphan_rollback_intents Whether selected reset must replay rollback snapshots.
 * @return bool True only when every requested absence was verified under both fences.
 */
function ai4seo_cleanup_generated_data_reset_processing_state_under_lock(
	array $processing_state,
	array $target_post_ids,
	?callable $ownership_checkpoint = null,
	bool $reconcile_orphan_rollback_intents = false
): bool {
	$target_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $target_post_ids ) ) ) );

	if ( ! $target_post_ids ) {
		return ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );
	}

	$target_lookup               = array_fill_keys( $target_post_ids, true );
	$recoverable_ids_by_option   = isset( $processing_state['recoverable_post_ids_by_option'] ) && is_array( $processing_state['recoverable_post_ids_by_option'] )
		? $processing_state['recoverable_post_ids_by_option']
		: array();
	$stale_lease_ids_by_option   = isset( $processing_state['stale_lease_post_ids_by_option'] ) && is_array( $processing_state['stale_lease_post_ids_by_option'] )
		? $processing_state['stale_lease_post_ids_by_option']
		: array();
	$processing_option_names     = array(
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$processing_removals         = array();
	$protected_rollback_ids      = array();
	$queue_options_by_processing = array(
		AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME => array(
			'pending'         => AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
			'force_overwrite' => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
		),
		AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME => array(
			'pending'         => AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			'force_overwrite' => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		),
	);

	foreach ( $processing_option_names as $processing_option_name ) {
		$recoverable_ids                                = isset( $recoverable_ids_by_option[ $processing_option_name ] )
			? ai4seo_normalize_option_post_id_collection( $recoverable_ids_by_option[ $processing_option_name ] )
			: array();
		$processing_removals[ $processing_option_name ] = array_values(
			array_filter(
				$recoverable_ids,
				static function ( int $post_id ) use ( $target_lookup ): bool {
					return isset( $target_lookup[ $post_id ] );
				}
			)
		);
	}

	if ( $reconcile_orphan_rollback_intents ) {
		$processing_post_ids_by_option     = $processing_state['processing_post_ids_by_option'] ?? null;
		$processing_claim_leases_by_option = $processing_state['processing_claim_leases_by_option'] ?? null;

		if ( ! is_array( $processing_post_ids_by_option ) || ! is_array( $processing_claim_leases_by_option ) ) {
			return false;
		}

		foreach ( $processing_option_names as $processing_option_name ) {
			$processing_post_ids = $processing_post_ids_by_option[ $processing_option_name ] ?? null;
			$lease_snapshot      = $processing_claim_leases_by_option[ $processing_option_name ] ?? null;
			$stale_lease_ids     = $stale_lease_ids_by_option[ $processing_option_name ] ?? null;

			if ( ! is_array( $processing_post_ids ) || ! is_array( $lease_snapshot ) || ! is_array( $stale_lease_ids ) ) {
				return false;
			}

			$processing_lookup          = array_fill_keys( ai4seo_normalize_option_post_id_collection( $processing_post_ids ), true );
			$orphan_rollback_post_ids   = array();
			$expected_rollback_tokens   = array();
			$normalized_stale_lease_ids = ai4seo_normalize_option_post_id_collection( $stale_lease_ids );

			foreach ( $normalized_stale_lease_ids as $stale_lease_post_id ) {
				$current_lease = $lease_snapshot[ $stale_lease_post_id ] ?? null;

				if (
					! isset( $target_lookup[ $stale_lease_post_id ] )
					|| isset( $processing_lookup[ $stale_lease_post_id ] )
					|| ! is_array( $current_lease )
					|| empty( $current_lease['rollback_requested'] )
					|| ! array_key_exists( 'pending_was_present', $current_lease )
					|| ! array_key_exists( 'force_was_present', $current_lease )
				) {
					continue;
				}

				$orphan_rollback_post_ids[]                       = $stale_lease_post_id;
				$expected_rollback_tokens[ $stale_lease_post_id ] = $current_lease['token'];
			}

			$protected_rollback_ids[ $processing_option_name ] = $orphan_rollback_post_ids;

			if ( ! $orphan_rollback_post_ids ) {
				continue;
			}

			$reconciled_post_ids = array();

			if ( ! ai4seo_reconcile_orphan_processing_rollback_leases_under_lock(
				$processing_option_name,
				$queue_options_by_processing[ $processing_option_name ]['pending'],
				$queue_options_by_processing[ $processing_option_name ]['force_overwrite'],
				$orphan_rollback_post_ids,
				false,
				$expected_rollback_tokens,
				$ownership_checkpoint,
				$reconciled_post_ids
			) ) {
				return false;
			}
		}
	}

	if ( array_filter( $processing_removals ) ) {
		$processing_membership_changed = false;

		if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			return false;
		}

		try {
			$processing_transition_succeeded = ai4seo_apply_normalized_post_id_option_transition_under_lock(
				array(),
				$processing_removals,
				$processing_membership_changed
			);
		} catch ( Throwable $throwable ) {
			$processing_transition_succeeded = false;
		}

		$ownership_is_held = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );

		if ( ! $processing_transition_succeeded || ! $ownership_is_held ) {
			return false;
		}
	}

	foreach ( $processing_option_names as $processing_option_name ) {
		$lease_ids = array_merge(
			$processing_removals[ $processing_option_name ],
			isset( $stale_lease_ids_by_option[ $processing_option_name ] )
				? ai4seo_normalize_option_post_id_collection( $stale_lease_ids_by_option[ $processing_option_name ] )
				: array()
		);
		$lease_ids = array_values(
			array_unique(
				array_filter(
					$lease_ids,
					static function ( int $post_id ) use ( $target_lookup ): bool {
						return isset( $target_lookup[ $post_id ] );
					}
				)
			)
		);
		$lease_ids = array_values(
			array_diff(
				$lease_ids,
				$protected_rollback_ids[ $processing_option_name ] ?? array()
			)
		);

		foreach ( $lease_ids as $post_id ) {
			$predicate_matched = false;

			if ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
				return false;
			}

			try {
				$lease_mutation_succeeded = ai4seo_mutate_processing_claim_lease_under_lock(
					$processing_option_name,
					$post_id,
					null,
					null,
					$predicate_matched
				);
			} catch ( Throwable $throwable ) {
				$lease_mutation_succeeded = false;
			}

			$ownership_is_held = ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );

			if ( ! $lease_mutation_succeeded || ! $predicate_matched || ! $ownership_is_held ) {
				return false;
			}
		}
	}

	return ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint );
}


/**
 * Return both durable Processing claim-lease option names owned by reset cleanup.
 *
 * @return array Exact site-local option names.
 */
function ai4seo_get_generated_data_reset_processing_claim_lease_option_names(): array {
	$option_names = array(
		ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ),
		ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
	);

	return array_values(
		array_unique(
			array_filter(
				$option_names,
				static function ( $option_name ): bool {
					return is_string( $option_name ) && '' !== $option_name;
				}
			)
		)
	);
}


/**
 * Reset all generated postmeta, status options, and summary storage while both reset fences are held.
 *
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return true|WP_Error True on success, or the checked storage error.
 */
function ai4seo_reset_all_generated_data_under_ownership( ?callable $ownership_checkpoint = null ) {
	global $wpdb;

	$legacy_delete_succeeded = ai4seo_run_generated_data_reset_database_operation(
		static function (): bool {
			return ai4seo_delete_all_legacy_active_metadata();
		},
		$ownership_checkpoint
	);

	if ( ! $legacy_delete_succeeded ) {
		return new WP_Error( 784322903, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
	}

	$postmeta_keys_to_delete = array(
		AI4SEO_POST_META_ACTIVE_METADATA_META_KEY      => 784322906,
		AI4SEO_POST_META_GENERATED_DATA_META_KEY       => 784322904,
		AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY => 784322905,
	);

	foreach ( $postmeta_keys_to_delete as $meta_key => $error_code ) {
		if ( ! ai4seo_delete_all_postmeta_for_meta_key( $meta_key, $ownership_checkpoint ) ) {
			return new WP_Error( $error_code, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
		}
	}

	// The caller owns the post-ID fence, so these direct checked deletes cannot interleave with a worker transition.
	$status_options_were_reset = ai4seo_delete_plugin_reset_option_family(
		array_merge(
			AI4SEO_ALL_POST_ID_OPTIONS,
			ai4seo_get_generated_data_reset_processing_claim_lease_option_names(),
			array(
				AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME,
				ai4seo_get_third_party_seo_metadata_finalization_retry_option_name(),
			)
		),
		$ownership_checkpoint
	);
	$status_options_error      = ! $status_options_were_reset && is_object( $wpdb ) ? (string) $wpdb->last_error : '';
	$summary_was_reset         = ai4seo_delete_generation_status_summary_cache_pair( $ownership_checkpoint );

	if ( ! $status_options_were_reset ) {
		if ( is_object( $wpdb ) ) {
			$wpdb->last_error = $status_options_error;
		}

		return new WP_Error( 784322914, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
	}

	if ( ! $summary_was_reset ) {
		return new WP_Error( 784322912, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
	}

	return true;
}


/**
 * Reset cache-owned status, summary, environmental, and postmeta storage while both fences are held.
 *
 * @param callable|null $ownership_checkpoint Optional reset-ownership renewal callback.
 * @return true|WP_Error True on success, or the checked cache-storage error.
 */
function ai4seo_reset_cache_data_under_ownership( ?callable $ownership_checkpoint = null ) {
	global $wpdb;

	if (
		! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint )
		|| ! ai4seo_delete_legacy_robhub_api_lock_options()
	) {
		return new WP_Error( 784322901, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	delete_transient( 'ai4seo_last_contact_form_submit_timestamp' );

	// Processing was verified empty before this direct checked status-family deletion started.
	$status_options_were_reset = ai4seo_delete_plugin_reset_option_family(
		array_merge(
			AI4SEO_ALL_POST_ID_OPTIONS,
			ai4seo_get_generated_data_reset_processing_claim_lease_option_names(),
			array(
				AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME,
				ai4seo_get_third_party_seo_metadata_finalization_retry_option_name(),
			)
		),
		$ownership_checkpoint
	);
	$status_options_error      = ! $status_options_were_reset && is_object( $wpdb ) ? (string) $wpdb->last_error : '';
	$summary_was_reset         = ai4seo_delete_generation_status_summary_cache_pair( $ownership_checkpoint );

	if ( ! $status_options_were_reset ) {
		if ( is_object( $wpdb ) ) {
			$wpdb->last_error = $status_options_error;
		}

		return new WP_Error( 784322913, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	if ( ! $summary_was_reset ) {
		return new WP_Error( 784322911, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	if (
		! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint )
		|| ! ai4seo_invalidate_all_environmental_variable_caches()
	) {
		return new WP_Error( 784322916, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	if ( ! ai4seo_delete_all_postmeta_for_meta_key( AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY, $ownership_checkpoint ) ) {
		return new WP_Error( 784322902, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	if ( ! ai4seo_delete_all_postmeta_for_meta_key( AI4SEO_POST_META_RELATED_POST_ID_META_KEY, $ownership_checkpoint ) ) {
		return new WP_Error( 784322909, esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ) );
	}

	return true;
}


/**
 * Reset cache data or selected/all generated data under analysis and worker ownership fences.
 *
 * Lock order is the site-scoped posts-analysis advisory lock followed by the shared post-ID
 * transition semaphore. Processing state is read authoritatively only after both are held.
 *
 * @param string $reset_scope Either cache or metadata.
 * @param array  $post_types Selected post types for a partial reset.
 * @param bool   $is_full_reset Whether every generated-data family is reset.
 * @return true|WP_Error True on success, or a stable contention/storage/release error.
 */
function ai4seo_reset_plugin_storage_with_exclusive_ownership( string $reset_scope, array $post_types = array(), bool $is_full_reset = false ) {
	$reset_scope = sanitize_key( $reset_scope );
	$post_types  = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

	if ( ! in_array( $reset_scope, array( 'cache', 'metadata' ), true ) ) {
		return new WP_Error( 784322922, esc_html__( 'Unknown plugin data reset scope.', 'ai-for-seo' ) );
	}

	if ( 'metadata' === $reset_scope && ! $is_full_reset && ! $post_types ) {
		return new WP_Error( 784322907, esc_html__( 'Please select at least one entry type.', 'ai-for-seo' ) );
	}

	$analysis_lock_name         = ai4seo_get_posts_table_analysis_database_lock_name();
	$analysis_lock_was_acquired = false;
	$post_id_lock_was_acquired  = false;
	$analysis_release_succeeded = true;
	$post_id_release_succeeded  = true;
	$operation_result           = new WP_Error(
		784322917,
		esc_html__( 'Plugin data cannot be reset while generation-status analysis is active. Please try again shortly.', 'ai-for-seo' )
	);
	$post_id_critical_section   = ai4seo_get_post_id_option_transition_semaphore_name();
	$summary_suppression_scope  = array();
	$summary_tracking_restored  = true;
	$ownership_checkpoint       = static function () use ( $analysis_lock_name, $post_id_critical_section ): bool {
		return ai4seo_renew_generated_data_reset_exclusive_ownership(
			$analysis_lock_name,
			$post_id_critical_section
		);
	};

	if ( '' === $analysis_lock_name ) {
		return $operation_result;
	}

	try {
		$analysis_lock_was_acquired = ai4seo_acquire_database_advisory_lock( $analysis_lock_name );

		if ( ! $analysis_lock_was_acquired ) {
			return $operation_result;
		}

		$post_id_lock_was_acquired = ai4seo_acquire_semaphore( $post_id_critical_section );

		if ( ! $post_id_lock_was_acquired ) {
			$operation_result = new WP_Error(
				784322917,
				esc_html__( 'Plugin data cannot be reset while generation work is changing state. Please try again shortly.', 'ai-for-seo' )
			);
		} elseif ( ! ai4seo_run_generated_data_reset_ownership_checkpoint( $ownership_checkpoint ) ) {
			$operation_result = new WP_Error(
				784322919,
				esc_html__( 'Plugin data cannot be reset because exclusive reset ownership was lost. Please try again.', 'ai-for-seo' )
			);
		} else {
			$processing_state = ai4seo_read_generated_data_reset_processing_post_ids_under_lock( $ownership_checkpoint );

			if ( is_wp_error( $processing_state ) ) {
				$operation_result = $processing_state;
			} elseif ( 'cache' === $reset_scope || $is_full_reset ) {
				$operation_result           = true;
				$active_processing_post_ids = isset( $processing_state['active_post_ids'] ) && is_array( $processing_state['active_post_ids'] )
					? $processing_state['active_post_ids']
					: array();

				if ( $active_processing_post_ids ) {
					$operation_result = new WP_Error(
						784322918,
						esc_html__( 'Plugin data cannot be reset while entries are being generated. Please wait for generation to finish and try again.', 'ai-for-seo' )
					);
				} elseif ( ! ai4seo_clear_disabled_queue_inspection_state_under_lock( $ownership_checkpoint ) ) {
					$operation_result = new WP_Error(
						784322925,
						esc_html__( 'Plugin data cannot be reset because pending generation state could not be cleared safely. Please try again.', 'ai-for-seo' )
					);
				} else {
					$summary_suppression_scope = ai4seo_begin_generation_status_summary_bulk_rebuild_suppression();

					if ( ! $summary_suppression_scope ) {
						$operation_result = new WP_Error(
							784322924,
							esc_html__( 'Plugin data cannot be reset because authoritative status reconciliation could not be scheduled. Please try again.', 'ai-for-seo' )
						);
					} else {
						$cleanup_post_ids = isset( $processing_state['recoverable_post_ids'] ) && is_array( $processing_state['recoverable_post_ids'] )
							? $processing_state['recoverable_post_ids']
							: array();

						foreach ( $processing_state['stale_lease_post_ids_by_option'] as $stale_lease_post_ids ) {
							if ( is_array( $stale_lease_post_ids ) ) {
								$cleanup_post_ids = array_merge( $cleanup_post_ids, $stale_lease_post_ids );
							}
						}

						$cleanup_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $cleanup_post_ids ) ) ) );

						if ( $cleanup_post_ids ) {
							if ( ! ai4seo_cleanup_generated_data_reset_processing_state_under_lock( $processing_state, $cleanup_post_ids, $ownership_checkpoint ) ) {
								$operation_result = new WP_Error(
									784322920,
									esc_html__( 'Could not repair abandoned generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
								);
							}
						}

						if ( ! is_wp_error( $operation_result ) ) {
							$operation_result = 'cache' === $reset_scope
								? ai4seo_reset_cache_data_under_ownership( $ownership_checkpoint )
								: ai4seo_reset_all_generated_data_under_ownership( $ownership_checkpoint );
						}
					}
				}
			} else {
				$operation_result   = true;
				$cleanup_candidates = $processing_state['recoverable_post_ids'];

				foreach ( $processing_state['stale_lease_post_ids_by_option'] as $stale_lease_post_ids ) {
					if ( is_array( $stale_lease_post_ids ) ) {
						$cleanup_candidates = array_merge( $cleanup_candidates, $stale_lease_post_ids );
					}
				}

				$cleanup_candidates        = array_values( array_unique( array_filter( array_map( 'absint', $cleanup_candidates ) ) ) );
				$processing_post_ids       = array_values(
					array_unique(
						array_filter(
							array_map( 'absint', array_merge( $processing_state['active_post_ids'], $cleanup_candidates ) )
						)
					)
				);
				$processing_post_id_lookup = array_fill_keys( $processing_post_ids, true );
				$seen_processing_post_ids  = array();
				$classified_post_id_count  = 0;
				$high_water_post_id        = ai4seo_read_generated_data_reset_high_water_post_id( $post_types, $ownership_checkpoint );

				if ( is_wp_error( $high_water_post_id ) ) {
					$operation_result = $high_water_post_id;
				} else {
					$classification_result = ai4seo_visit_generated_data_post_id_pages_by_post_types(
						$post_types,
						$high_water_post_id,
						static function ( array $post_id_page ) use ( $processing_post_id_lookup, &$seen_processing_post_ids ): bool {
							foreach ( $post_id_page as $post_id ) {
								if ( isset( $processing_post_id_lookup[ $post_id ] ) ) {
									$seen_processing_post_ids[ $post_id ] = true;
								}
							}

							return true;
						},
						$ownership_checkpoint,
						$classified_post_id_count
					);

					if ( is_wp_error( $classification_result ) ) {
						$operation_result = $classification_result;
					} else {
						$target_processing_post_ids = ai4seo_get_processing_post_ids_for_generated_data_reset_targets(
							$processing_post_ids,
							array_keys( $seen_processing_post_ids ),
							$post_types,
							$ownership_checkpoint
						);

						$target_active_processing_post_ids = array();
						$target_cleanup_post_ids           = array();

						if ( is_wp_error( $target_processing_post_ids ) ) {
							$operation_result = $target_processing_post_ids;
						} else {
							$target_processing_post_id_lookup = array_fill_keys( $target_processing_post_ids, true );

							$target_active_processing_post_ids = array_values(
								array_filter(
									$processing_state['active_post_ids'],
									static function ( int $post_id ) use ( $target_processing_post_id_lookup ): bool {
										return isset( $target_processing_post_id_lookup[ $post_id ] );
									}
								)
							);

							$target_cleanup_post_ids = array_values(
								array_filter(
									$cleanup_candidates,
									static function ( int $post_id ) use ( $target_processing_post_id_lookup ): bool {
										return isset( $target_processing_post_id_lookup[ $post_id ] );
									}
								)
							);
						}

						if ( ! is_wp_error( $operation_result ) && $target_active_processing_post_ids ) {
							$operation_result = new WP_Error(
								784322918,
								esc_html__( 'Selected metadata cannot be reset while matching entries are being generated. Please wait for generation to finish and try again.', 'ai-for-seo' )
							);
						} elseif ( ! is_wp_error( $operation_result ) && ! ai4seo_clear_disabled_queue_inspection_state_under_lock( $ownership_checkpoint ) ) {
							$operation_result = new WP_Error(
								784322925,
								esc_html__( 'Plugin data cannot be reset because pending generation state could not be cleared safely. Please try again.', 'ai-for-seo' )
							);
						} elseif ( ! is_wp_error( $operation_result ) ) {
							$summary_suppression_scope = ai4seo_begin_generation_status_summary_bulk_rebuild_suppression();

							if ( ! $summary_suppression_scope ) {
								$operation_result = new WP_Error(
									784322924,
									esc_html__( 'Plugin data cannot be reset because authoritative status reconciliation could not be scheduled. Please try again.', 'ai-for-seo' )
								);
							} elseif ( $target_cleanup_post_ids ) {
								if ( ! ai4seo_cleanup_generated_data_reset_processing_state_under_lock(
									$processing_state,
									$target_cleanup_post_ids,
									$ownership_checkpoint,
									true
								) ) {
									$operation_result = new WP_Error(
										784322920,
										esc_html__( 'Could not repair abandoned generation work before resetting plugin data. Please try again.', 'ai-for-seo' )
									);
								}
							}

							if ( ! is_wp_error( $operation_result ) ) {
								$reset_post_id_count = 0;
								$reset_result        = ai4seo_visit_generated_data_post_id_pages_by_post_types(
									$post_types,
									$high_water_post_id,
									static function ( array $post_id_page ) use ( $post_types, $ownership_checkpoint ) {
										if ( ! ai4seo_reset_generated_data_for_post_ids( $post_id_page, $post_types, true, $ownership_checkpoint ) ) {
											return new WP_Error( 784322908, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
										}

										return true;
									},
									$ownership_checkpoint,
									$reset_post_id_count
								);

								if ( is_wp_error( $reset_result ) ) {
									$operation_result = $reset_result;
								} elseif ( $reset_post_id_count !== $classified_post_id_count ) {
									$operation_result = new WP_Error( 784322908, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
								} elseif ( 0 === $reset_post_id_count ) {
									if ( ! ai4seo_reset_generated_data_for_post_ids( array(), $post_types, true, $ownership_checkpoint ) ) {
										$operation_result = new WP_Error( 784322908, esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ) );
									}
								}
							}
						}
					}
				}
			}
		}
	} catch ( Throwable $throwable ) {
		ai4seo_debug_message( 984321718, 'Unexpected generated-data reset failure: ' . $throwable->getMessage(), true );
		$operation_result = new WP_Error( 784322921, esc_html__( 'Unexpected error while resetting metadata. Please try again.', 'ai-for-seo' ) );
	} finally {
		if ( $summary_suppression_scope ) {
			try {
				$summary_tracking_restored = ai4seo_end_generation_status_summary_bulk_rebuild_suppression( $summary_suppression_scope );
			} catch ( Throwable $throwable ) {
				$summary_tracking_restored = false;
			}
		}

		if ( $post_id_lock_was_acquired ) {
			try {
				$post_id_release_succeeded = ai4seo_release_semaphore( $post_id_critical_section );
			} catch ( Throwable $throwable ) {
				$post_id_release_succeeded = false;
			}
		}

		if ( $analysis_lock_was_acquired ) {
			try {
				$analysis_release_succeeded = ai4seo_release_database_advisory_lock( $analysis_lock_name );
			} catch ( Throwable $throwable ) {
				$analysis_release_succeeded = false;
			}
		}
	}

	if ( ! $summary_tracking_restored || ! $post_id_release_succeeded || ! $analysis_release_succeeded ) {
		return new WP_Error(
			784322919,
			esc_html__( 'Plugin data may have been reset, but exclusive reset ownership could not be released safely. Please refresh the page before trying again.', 'ai-for-seo' )
		);
	}

	return $operation_result;
}


/**
 * Called via AJAX - resets selected plugin data
 *
 * @return void
 */
function ai4seo_reset_plugin_data() {
	global $wpdb;
	global $ai4seo_settings;
	global $ai4seo_are_settings_initialized;

	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109830 );
		return;
	}

	$do_reset_environmental_variables        = ( isset( $_POST['ai4seo_reset_environmental_variables'] ) && 'true' === $_POST['ai4seo_reset_environmental_variables'] );
	$do_reset_settings                       = ( isset( $_POST['ai4seo_reset_settings'] ) && 'true' === $_POST['ai4seo_reset_settings'] );
	$do_reset_metadata                       = ( isset( $_POST['ai4seo_reset_metadata'] ) && 'true' === $_POST['ai4seo_reset_metadata'] );
	$has_reset_metadata_post_types_parameter = array_key_exists( 'ai4seo_reset_metadata_post_types', $_POST );
	$reset_metadata_is_full_reset            = false;
	$reset_metadata_post_types               = array();
	$bulk_generation_reference_timestamp     = null;

	// Preserve the date boundary when internal state is reset without resetting its owning setting.
	if ( $do_reset_environmental_variables && ! $do_reset_settings ) {
		$bulk_generation_reference_timestamp = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME );
	}

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

		if ( $reset_metadata_is_full_reset ) {
			$reset_metadata_post_types = array();
		}
	}

	// remove caches.
	if ( isset( $_POST['ai4seo_reset_cache'] ) && 'true' === $_POST['ai4seo_reset_cache'] ) {
		$cache_reset_result = ai4seo_reset_plugin_storage_with_exclusive_ownership( 'cache' );

		if ( is_wp_error( $cache_reset_result ) ) {
			ai4seo_debug_message( 984321721, 'Could not complete the exclusively fenced cache reset: ' . $cache_reset_result->get_error_message(), true );
			ai4seo_send_ajax_error(
				$cache_reset_result->get_error_message(),
				(int) $cache_reset_result->get_error_code()
			);
			wp_die();
		}
	}

	// ai4seo_reset_notifications.
	if ( isset( $_POST['ai4seo_reset_notifications'] ) && 'true' === $_POST['ai4seo_reset_notifications'] ) {
		// remove all notifications.
		ai4seo_remove_all_notifications();
	}

	// remove environmental variables.
	if ( $do_reset_environmental_variables ) {
		if ( ! ai4seo_delete_all_environmental_variables() ) {
			ai4seo_debug_message( 984321711, 'Could not reset environmental variables because persistent state changed or database storage failed.', true );
			ai4seo_send_ajax_error(
				esc_html__( 'Could not reset internal data. Please try again.', 'ai-for-seo' ),
				784322910
			);
			return;
		}

		ai4seo_robhub_api()->delete_all_environmental_variables();

		if ( ! $do_reset_settings && ! ai4seo_reconcile_bulk_generation_date_filter_reference_timestamp( $bulk_generation_reference_timestamp ) ) {
			ai4seo_debug_message( 728217659, 'Could not reconcile the SEO Autopilot date-filter reference timestamp after resetting environmental variables.', true );
			ai4seo_send_ajax_error(
				esc_html__( 'Could not restore the SEO Autopilot date-filter reference after resetting internal data. Please save the SEO Autopilot settings again.', 'ai-for-seo' ),
				728217659
			);
			return;
		}
	}

	// remove/reset settings.
	if ( $do_reset_settings ) {
		if ( ! ai4seo_delete_option( AI4SEO_SETTINGS_OPTION_NAME ) ) {
			$settings_reset_database_error = (string) $wpdb->last_error;

			// Discard any optimistic request state and reload the still-authoritative stored settings.
			ai4seo_reset_settings_request_cache_for_current_site();
			$ai4seo_settings                 = AI4SEO_DEFAULT_SETTINGS;
			$ai4seo_are_settings_initialized = false;
			ai4seo_init_settings();

			$wpdb->last_error = $settings_reset_database_error;
			ai4seo_debug_message( 984321716, 'Could not reset plugin settings. Database error: ' . $settings_reset_database_error, true );
			ai4seo_send_ajax_error( esc_html__( 'Database error while resetting settings.', 'ai-for-seo' ), 784322915 );
			wp_die();
		}

		ai4seo_reset_settings_request_cache_for_current_site();
		$ai4seo_settings                 = AI4SEO_DEFAULT_SETTINGS;
		$ai4seo_are_settings_initialized = true;
		ai4seo_store_settings_request_cache_for_current_site();
	}

	// remove existing generated metadata.
	if ( $do_reset_metadata ) {
		$metadata_reset_result = ai4seo_reset_plugin_storage_with_exclusive_ownership(
			'metadata',
			$reset_metadata_post_types,
			$reset_metadata_is_full_reset
		);

		if ( is_wp_error( $metadata_reset_result ) ) {
			ai4seo_debug_message( 984321719, 'Could not complete the exclusively fenced generated-data reset: ' . $metadata_reset_result->get_error_message(), true );
			ai4seo_send_ajax_error(
				$metadata_reset_result->get_error_message(),
				(int) $metadata_reset_result->get_error_code()
			);
			wp_die();
		}
	}

	// tidy up.
	ai4seo_tidy_up();

	ai4seo_send_ajax_success();
}


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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109836 );
		return;
	}

	require_once ai4seo_get_includes_ajax_process_path( 'export-settings.php' );
}


/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_show_import_settings_preview() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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


/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_import_settings() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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



/**
 * Called via AJAX - Requires the Get More Credits modal to be displayed.
 *
 * @return void
 */
function ai4seo_show_get_more_credits_modal() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1108261201 );
		return;
	}

	ob_start();
	require_once ai4seo_get_includes_ajax_display_path( 'get-more-credits-modal.php' );
	$content = ob_get_clean();

	if ( ! is_string( $content ) || trim( $content ) === '' ) {
		ai4seo_send_ajax_error( esc_html__( 'The Credits options could not be loaded. Please refresh the page and try again.', 'ai-for-seo' ), 1108261202 );
		return;
	}

	ai4seo_send_ajax_success( $content );
}



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

	// Usage lookup exposes surrounding post context, so require access to the requested media object first.
	if ( ! ai4seo_can_edit_post( $attachment_post_id ) ) {
		ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this media entry.', 'ai-for-seo' ), 16032603 );
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

	// Resolve only an editable usage post so the context link cannot expose an unauthorized entry.
	$usage_post_id    = ai4seo_get_first_attachment_using_post_id( $attachment_post_id, true );
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
			'settings_url'                  => ai4seo_can_administer_plugin() ? ai4seo_get_subpage_url( 'settings' ) : '',
		)
	);
}



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

	// Configured content roles may hydrate cached dashboard state but cannot trigger site-wide analysis.
	if ( ai4seo_can_administer_plugin() ) {
		ai4seo_check_for_performance_analysis();
		ai4seo_check_for_unfinished_posts_table_analysis_notification( true );
	}

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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
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


/**
 * Snapshots the highest current NextGEN picture ID for finite keyset traversal.
 *
 * @return int|false Highest positive picture ID, zero for an empty table, or false on failure.
 */
function ai4seo_read_nextgen_gallery_image_max_picture_id() {
	global $wpdb;

	$sql = ai4seo_prepare_database_query(
		'SELECT MAX(`pid`)
		FROM {{pictures_table}}
		WHERE `pid` > {{minimum_pid}}',
		array(
			'pictures_table' => ai4seo_database_identifier_binding( 'table.nextgen_pictures' ),
			'minimum_pid'    => ai4seo_database_scalar_binding( '%d', 0 ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares the optional provider identifier; import needs a current finite high-water snapshot.
	$maximum_picture_id = $wpdb->get_var( $sql );

	if ( $wpdb->last_error || ( null !== $maximum_picture_id && ! is_numeric( $maximum_picture_id ) ) ) {
		return false;
	}

	return null === $maximum_picture_id ? 0 : absint( $maximum_picture_id );
}


/**
 * Reads one bounded, keyset-paginated page of current NextGEN picture rows.
 *
 * @param int $after_picture_id Read picture IDs strictly above this cursor.
 * @param int $limit Maximum rows to return.
 * @param int $maximum_picture_id Operation-start high-water picture ID.
 * @return array|false Picture rows, or false on preparation/database failure.
 */
function ai4seo_read_nextgen_gallery_image_row_page( int $after_picture_id, int $limit, int $maximum_picture_id = PHP_INT_MAX ) {
	global $wpdb;

	$after_picture_id   = absint( $after_picture_id );
	$limit              = min( absint( $limit ), ai4seo_get_database_chunk_size() );
	$maximum_picture_id = absint( $maximum_picture_id );

	if ( $limit <= 0 || $maximum_picture_id <= $after_picture_id ) {
		return array();
	}

	if ( $maximum_picture_id <= 0 ) {
		return false;
	}

	$sql = ai4seo_prepare_database_query(
		'SELECT `pid`, `image_slug`, `galleryid`, `filename`, `description`, `alttext`, `imagedate`, `updated_at`
		FROM {{pictures_table}}
		WHERE `pid` > {{minimum_pid}}
		AND `pid` <= {{maximum_pid}}
		ORDER BY `pid` ASC
		LIMIT {{row_limit}}',
		array(
			'pictures_table' => ai4seo_database_identifier_binding( 'table.nextgen_pictures' ),
			'minimum_pid'    => ai4seo_database_scalar_binding( '%d', $after_picture_id ),
			'maximum_pid'    => ai4seo_database_scalar_binding( '%d', $maximum_picture_id ),
			'row_limit'      => ai4seo_database_scalar_binding( '%d', $limit ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares the optional provider identifier, monotonic cursor, and bounded page size; import must read current source rows.
	$rows = $wpdb->get_results( $sql, ARRAY_A );

	return $wpdb->last_error || ! is_array( $rows ) ? false : $rows;
}


/**
 * Reads all current NextGEN picture rows for compatibility callers.
 *
 * Production import processing consumes the bounded page API directly so it never retains this
 * complete collection. This wrapper preserves the established read contract for other callers.
 *
 * @return array|false Picture rows, or false on preparation/database failure.
 */
function ai4seo_read_nextgen_gallery_image_rows() {
	$maximum_picture_id = ai4seo_read_nextgen_gallery_image_max_picture_id();

	if ( false === $maximum_picture_id ) {
		return false;
	}

	if ( 0 === $maximum_picture_id ) {
		return array();
	}

	$page_size        = ai4seo_get_database_chunk_size();
	$after_picture_id = 0;
	$all_rows         = array();

	while ( true ) {
		$this_page = ai4seo_read_nextgen_gallery_image_row_page( $after_picture_id, $page_size, $maximum_picture_id );

		if ( false === $this_page ) {
			return false;
		}

		if ( ! $this_page ) {
			break;
		}

		$previous_picture_id = $after_picture_id;

		foreach ( $this_page as $this_row ) {
			$this_picture_id = absint( $this_row['pid'] ?? 0 );

			if ( $this_picture_id <= $previous_picture_id ) {
				return false;
			}

			$previous_picture_id = $this_picture_id;
			$all_rows[]          = $this_row;
		}

		$after_picture_id = $previous_picture_id;

		if ( count( $this_page ) < $page_size ) {
			break;
		}
	}

	return $all_rows;
}


/**
 * Reads current NextGEN gallery paths for normalized gallery IDs.
 *
 * @param array $gallery_ids NextGEN gallery IDs.
 * @return array|false Gallery rows, or false on preparation/database failure.
 */
function ai4seo_read_nextgen_gallery_paths_by_ids( array $gallery_ids ) {
	global $wpdb;

	$gallery_ids = array_values( array_unique( array_filter( array_map( 'absint', $gallery_ids ) ) ) );

	if ( ! $gallery_ids ) {
		return array();
	}

	$maximum_id_bindings = ai4seo_get_database_placeholder_budget() - 1;
	$database_chunk_size = min( ai4seo_get_database_chunk_size(), $maximum_id_bindings );
	$gallery_rows        = array();

	foreach ( array_chunk( $gallery_ids, $database_chunk_size ) as $this_gallery_ids_chunk ) {
		$sql = ai4seo_prepare_database_query(
			'SELECT `gid`, `path`
			FROM {{gallery_table}}
			WHERE `gid` IN ({{gallery_ids}})',
			array(
				'gallery_table' => ai4seo_database_identifier_binding( 'table.nextgen_gallery' ),
				'gallery_ids'   => ai4seo_database_list_binding( '%d', $this_gallery_ids_chunk ),
			)
		);

		if ( false === $sql ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares the optional provider-table identifier and ID list; import needs current gallery paths and performs this read once per bounded chunk.
		$this_gallery_rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $this_gallery_rows ) ) {
			return false;
		}

		if ( $this_gallery_rows ) {
			$gallery_rows = array_merge( $gallery_rows, $this_gallery_rows );
		}
	}

	return $gallery_rows;
}


/**
 * Resolves a bounded picture page to its available sanitized gallery paths.
 *
 * @param array $picture_rows One keyset page of NextGEN picture rows.
 * @return array|false Gallery paths keyed by gallery ID, or false on database failure.
 */
function ai4seo_read_nextgen_gallery_path_map_for_picture_rows( array $picture_rows ) {
	$gallery_ids = array();

	foreach ( $picture_rows as $picture_row ) {
		$gallery_id = absint( $picture_row['galleryid'] ?? 0 );

		if ( $gallery_id > 0 ) {
			$gallery_ids[ $gallery_id ] = $gallery_id;
		}
	}

	if ( ! $gallery_ids ) {
		return array();
	}

	$gallery_rows = ai4seo_read_nextgen_gallery_paths_by_ids( array_values( $gallery_ids ) );

	if ( false === $gallery_rows ) {
		return false;
	}

	$gallery_paths = array();

	foreach ( $gallery_rows as $gallery_row ) {
		$gallery_id   = absint( $gallery_row['gid'] ?? 0 );
		$gallery_path = sanitize_text_field( $gallery_row['path'] ?? '' );

		if ( $gallery_id > 0 && '' !== $gallery_path ) {
			$gallery_paths[ $gallery_id ] = $gallery_path;
		}
	}

	return $gallery_paths;
}


/**
 * Reads a deterministic NextGEN picture-ID to imported-post-ID map.
 *
 * When legacy data contains duplicate imported posts, the lowest post ID is retained.
 *
 * @return array|false Imported posts keyed by picture ID, or false on preparation/database failure.
 */
function ai4seo_read_imported_nextgen_gallery_image_map() {
	global $wpdb;

	$sql = ai4seo_prepare_database_query(
		'SELECT post_parent, ID
		FROM {{posts_table}}
		WHERE post_type = {{post_type}}
		ORDER BY post_parent ASC, ID ASC',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_type'   => ai4seo_database_scalar_binding( '%s', AI4SEO_NEXTGEN_GALLERY_POST_TYPE ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The named-query compiler prepares every binding; this import deduplication read must see posts inserted earlier in the current request.
	$imported_post_rows = $wpdb->get_results( $sql, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $imported_post_rows ) ) {
		return false;
	}

	$imported_posts_by_picture_id = array();

	foreach ( $imported_post_rows as $imported_post_row ) {
		$picture_id       = absint( $imported_post_row['post_parent'] ?? 0 );
		$imported_post_id = absint( $imported_post_row['ID'] ?? 0 );

		if ( $picture_id <= 0 || $imported_post_id <= 0 || isset( $imported_posts_by_picture_id[ $picture_id ] ) ) {
			continue;
		}

		$imported_posts_by_picture_id[ $picture_id ] = $imported_post_id;
	}

	return $imported_posts_by_picture_id;
}


/**
 * Reads imported NextGEN posts only for one bounded page of provider picture IDs.
 *
 * @param array $picture_ids Provider picture IDs from the current keyset page.
 * @return array|false Imported posts keyed by picture ID, or false on failure.
 */
function ai4seo_read_imported_nextgen_gallery_image_map_for_picture_ids( array $picture_ids ) {
	global $wpdb;

	$picture_ids = ai4seo_normalize_database_ids( $picture_ids );

	if ( false === $picture_ids ) {
		return false;
	}

	if ( ! $picture_ids ) {
		return array();
	}

	$maximum_id_bindings          = ai4seo_get_database_placeholder_budget() - 2;
	$database_chunk_size          = min( ai4seo_get_database_chunk_size(), $maximum_id_bindings );
	$imported_posts_by_picture_id = array();

	foreach ( array_chunk( $picture_ids, $database_chunk_size ) as $this_picture_id_chunk ) {
		$sql = ai4seo_prepare_database_query(
			'SELECT post_parent, ID
			FROM {{posts_table}}
			WHERE post_type = {{post_type}}
			AND post_parent IN ({{picture_ids}})
			ORDER BY post_parent ASC, ID ASC',
			array(
				'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
				'post_type'   => ai4seo_database_scalar_binding( '%s', AI4SEO_NEXTGEN_GALLERY_POST_TYPE ),
				'picture_ids' => ai4seo_database_list_binding( '%d', $this_picture_id_chunk ),
			)
		);

		if ( false === $sql ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares the post type and bounded provider-ID page; current import ownership must bypass caches.
		$this_imported_post_rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error || ! is_array( $this_imported_post_rows ) ) {
			return false;
		}

		foreach ( $this_imported_post_rows as $this_imported_post_row ) {
			$picture_id       = absint( $this_imported_post_row['post_parent'] ?? 0 );
			$imported_post_id = absint( $this_imported_post_row['ID'] ?? 0 );

			if ( $picture_id <= 0 || $imported_post_id <= 0 || isset( $imported_posts_by_picture_id[ $picture_id ] ) ) {
				continue;
			}

			$imported_posts_by_picture_id[ $picture_id ] = $imported_post_id;
		}
	}

	return $imported_posts_by_picture_id;
}


/**
 * Reads NextGEN picture IDs already represented by imported posts.
 *
 * @return array|false Imported picture IDs, or false on preparation/database failure.
 */
function ai4seo_read_imported_nextgen_gallery_image_pids() {
	$imported_posts_by_picture_id = ai4seo_read_imported_nextgen_gallery_image_map();

	return false === $imported_posts_by_picture_id ? false : array_keys( $imported_posts_by_picture_id );
}


/**
 * Normalizes a non-negative database aggregate without accepting loose numeric forms.
 *
 * @param mixed $value Aggregate value returned by the database driver.
 * @return int|false Canonical count, or false when the value is malformed or overflows PHP integers.
 */
function ai4seo_normalize_nextgen_gallery_image_count( $value ) {
	if ( 0 === $value || '0' === $value ) {
		return 0;
	}

	return ai4seo_normalize_database_id( $value );
}


/**
 * Reads coherent scalar NextGEN provider/imported counts in one bounded-memory statement.
 *
 * @return array|false Provider/imported counts, or false on preparation, database, or shape failure.
 */
function ai4seo_read_nextgen_gallery_image_import_counts() {
	global $wpdb;

	$sql = ai4seo_prepare_database_query(
		'SELECT COUNT(DISTINCT provider.pid) AS provider_count,
			COUNT(DISTINCT imported.post_parent) AS imported_count
		FROM {{pictures_table}} AS provider
		LEFT JOIN {{posts_table}} AS imported
			ON imported.post_parent = provider.pid
			AND imported.post_type = {{post_type}}
		WHERE provider.pid > {{minimum_pid}}',
		array(
			'pictures_table' => ai4seo_database_identifier_binding( 'table.nextgen_pictures' ),
			'posts_table'    => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_type'      => ai4seo_database_scalar_binding( '%s', AI4SEO_NEXTGEN_GALLERY_POST_TYPE ),
			'minimum_pid'    => ai4seo_database_scalar_binding( '%d', 0 ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares both allowlisted tables and all values; one uncached aggregate keeps the UI snapshot coherent without materializing provider IDs.
	$count_row = $wpdb->get_row( $sql, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $count_row ) ) {
		return false;
	}

	$provider_count = ai4seo_normalize_nextgen_gallery_image_count( $count_row['provider_count'] ?? null );
	$imported_count = ai4seo_normalize_nextgen_gallery_image_count( $count_row['imported_count'] ?? null );

	if ( false === $provider_count || false === $imported_count || $imported_count > $provider_count ) {
		return false;
	}

	return array(
		'provider_count' => $provider_count,
		'imported_count' => $imported_count,
	);
}


/**
 * Invalidates every environmental cache affected by a NextGEN import or repair.
 *
 * @param bool $postmeta_inserted Whether the optional attachment-alt row was inserted.
 * @return bool True when every cache TTL was removed or already absent, false if any mutation failed.
 */
function ai4seo_invalidate_nextgen_environmental_caches( bool $postmeta_inserted ): bool {
	global $wpdb;

	$environmental_caches = array(
		AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
		AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE,
		AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE,
		AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE,
	);
	if ( $postmeta_inserted ) {
		$environmental_caches[] = AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES;
	}

	$all_caches_invalidated = true;
	$first_database_error   = '';

	foreach ( $environmental_caches as $environmental_cache ) {
		$this_cache_invalidated = ai4seo_invalidate_environmental_variable_cache( $environmental_cache );

		if ( ! $this_cache_invalidated && '' === $first_database_error && is_object( $wpdb ) ) {
			$first_database_error = (string) $wpdb->last_error;
		}

		$all_caches_invalidated = $this_cache_invalidated && $all_caches_invalidated;
	}

	if ( ! $all_caches_invalidated && '' !== $first_database_error && is_object( $wpdb ) ) {
		$wpdb->last_error = $first_database_error;
	}

	return $all_caches_invalidated;
}


/**
 * Invalidates caches bypassed by a successful direct NextGEN post insert.
 *
 * @param int  $post_id Inserted post ID, or zero when the database did not expose it.
 * @param bool $postmeta_inserted Whether the optional attachment-alt row was inserted.
 * @param bool $defer_persistent_cache_invalidation Whether the owning batch will publish persistent invalidations once.
 * @return bool Whether every persistent invalidation was confirmed.
 */
function ai4seo_invalidate_nextgen_import_caches( int $post_id, bool $postmeta_inserted, bool $defer_persistent_cache_invalidation = false ): bool {
	global $ai4seo_nextgen_repair_content_type_list_cache_version_bumped;

	if ( $post_id > 0 ) {
		clean_post_cache( $post_id );
		wp_cache_delete( $post_id, 'post_meta' );
	}

	$environmental_caches_invalidated = $defer_persistent_cache_invalidation
		|| ai4seo_invalidate_nextgen_environmental_caches( $postmeta_inserted );
	$cache_version_bumped             = $defer_persistent_cache_invalidation
		|| ai4seo_force_bump_content_type_list_cache_version();

	if ( ! $defer_persistent_cache_invalidation ) {
		$ai4seo_nextgen_repair_content_type_list_cache_version_bumped = $cache_version_bumped
			? ai4seo_get_content_type_list_cache_version()
			: false;
	}

	return $environmental_caches_invalidated && $cache_version_bumped;
}


/**
 * Guarantees that a direct NextGEN alt repair changes the content-list cache namespace.
 *
 * The shared bump helper deliberately coalesces ordinary invalidations within one request.
 * A repair can run after that coalesced bump, so fall back to a distinct version when needed.
 *
 * @return bool Whether this request has persisted a distinct cache version.
 */
function ai4seo_bump_nextgen_repair_content_type_list_cache_version(): bool {
	global $ai4seo_nextgen_repair_content_type_list_cache_version_bumped;

	$current_cache_version = ai4seo_get_content_type_list_cache_version();

	if (
		is_int( $ai4seo_nextgen_repair_content_type_list_cache_version_bumped ?? null )
		&& $current_cache_version === $ai4seo_nextgen_repair_content_type_list_cache_version_bumped
	) {
		return true;
	}

	$cache_version_bumped = ai4seo_force_bump_content_type_list_cache_version();
	$ai4seo_nextgen_repair_content_type_list_cache_version_bumped = $cache_version_bumped
		? ai4seo_get_content_type_list_cache_version()
		: false;

	return $cache_version_bumped;
}


/**
 * Invalidates caches after processing an existing NextGEN import.
 *
 * @param int  $post_id Existing imported post ID.
 * @param bool $postmeta_changed Whether the repair path may have changed postmeta rows.
 * @param bool $defer_persistent_cache_invalidation Whether the owning batch will publish persistent invalidations once.
 * @return bool Whether every persistent invalidation was confirmed.
 */
function ai4seo_invalidate_nextgen_repair_caches( int $post_id, bool $postmeta_changed, bool $defer_persistent_cache_invalidation = false ): bool {
	if ( $post_id > 0 ) {
		clean_post_cache( $post_id );
		wp_cache_delete( $post_id, 'post_meta' );
	}

	$environmental_caches_invalidated = $defer_persistent_cache_invalidation || ! $postmeta_changed
		|| ai4seo_invalidate_environmental_variable_cache( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES );
	$cache_version_bumped             = $defer_persistent_cache_invalidation
		|| ai4seo_bump_nextgen_repair_content_type_list_cache_version();

	return $environmental_caches_invalidated && $cache_version_bumped;
}


/**
 * Builds a stable NextGEN import error with its AJAX/debug response policy.
 *
 * @param string $error_code Stable internal error code.
 * @param string $message User-facing error message.
 * @param int    $ajax_code AJAX response code.
 * @param int    $debug_code Debug message identifier, or zero when no database error occurred.
 * @param int    $picture_id Optional NextGEN picture ID.
 * @return WP_Error
 */
function ai4seo_create_nextgen_import_error( string $error_code, string $message, int $ajax_code, int $debug_code = 0, int $picture_id = 0 ): WP_Error {
	global $wpdb;

	return new WP_Error(
		$error_code,
		$message,
		array(
			'ajax_code'     => $ajax_code,
			'debug_code'    => $debug_code,
			'debug_message' => $debug_code > 0 ? 'Database error: ' . $wpdb->last_error : '',
			'picture_id'    => $picture_id,
		)
	);
}


/**
 * Builds the stable error for a picture whose referenced gallery has no usable path.
 *
 * @param int $picture_id NextGEN picture ID.
 * @return WP_Error
 */
function ai4seo_create_nextgen_gallery_path_missing_error( int $picture_id ): WP_Error {
	return ai4seo_create_nextgen_import_error(
		'nextgen_gallery_path_missing',
		sprintf(
			/* translators: NextGen Gallery picture ID */
			esc_html__( 'Could not find a gallery path for the NextGen Gallery image with pid %s', 'ai-for-seo' ),
			$picture_id
		),
		20147525,
		0,
		$picture_id
	);
}


/**
 * Returns the shared critical-section name for a NextGEN import.
 *
 * @return string
 */
function ai4seo_get_nextgen_gallery_image_import_semaphore_name(): string {
	return 'nextgen-gallery-image-import';
}


/**
 * Returns the site-scoped database advisory-lock name for a NextGEN import.
 *
 * @return string
 */
function ai4seo_get_nextgen_gallery_image_import_database_lock_name(): string {
	global $wpdb;

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$table_prefix  = isset( $wpdb->prefix ) && is_string( $wpdb->prefix ) ? $wpdb->prefix : '';
	$scope_hash    = hash( 'sha256', $database_name . '|' . $table_prefix . '|' . get_current_blog_id() );

	return 'ai4seo_nextgen_import_' . substr( $scope_hash, 0, 40 );
}


/**
 * Resets the request-local NextGEN lease-renewal controller.
 *
 * @param bool $lease_is_fresh Whether this request just acquired a fresh semaphore lease.
 * @return void
 */
function ai4seo_reset_nextgen_gallery_image_import_lease_controller( bool $lease_is_fresh = false ): void {
	global $ai4seo_nextgen_gallery_image_import_last_lease_renewal_at;

	$ai4seo_nextgen_gallery_image_import_last_lease_renewal_at = $lease_is_fresh ? microtime( true ) : 0.0;
}


/**
 * Verifies the locally held NextGEN lease token without rewriting its stored timestamp.
 *
 * @return bool Whether the exact non-stale token still owns the semaphore row.
 */
function ai4seo_verify_nextgen_gallery_image_import_lease_ownership(): bool {
	$option_key       = ai4seo_get_semaphore_option_key( ai4seo_get_nextgen_gallery_image_import_semaphore_name() );
	$semaphore_record = ai4seo_get_held_semaphore_record( $option_key );
	$local_token      = $semaphore_record['token'] ?? '';

	if ( '' === $local_token ) {
		return false;
	}

	$lease_snapshot = ai4seo_get_semaphore_option_snapshot( $option_key );

	return is_array( $lease_snapshot )
		&& $lease_snapshot['exists']
		&& is_array( $lease_snapshot['value'] )
		&& isset( $lease_snapshot['value']['token'] )
		&& is_string( $lease_snapshot['value']['token'] )
		&& hash_equals( $local_token, $lease_snapshot['value']['token'] )
		&& ! ai4seo_is_lock_stale( $lease_snapshot['value'] );
}


/**
 * Renew or verify the active NextGEN import lease using a bounded request-local cadence.
 *
 * Ordinary loop checkpoints avoid database work until one third of the lease TTL has elapsed.
 * Forced mutation boundaries still verify the exact token, while timestamp rewrites remain
 * coalesced to the same safe cadence.
 *
 * @param bool $force_ownership_verification Whether a pending mutation requires a fresh token read.
 * @return true|WP_Error True while the current request still owns the import lease.
 */
function ai4seo_renew_nextgen_gallery_image_import_lease( bool $force_ownership_verification = false ) {
	global $ai4seo_nextgen_gallery_image_import_last_lease_renewal_at;

	$last_renewal_at  = is_numeric( $ai4seo_nextgen_gallery_image_import_last_lease_renewal_at ?? null )
		? (float) $ai4seo_nextgen_gallery_image_import_last_lease_renewal_at
		: 0.0;
	$current_time     = microtime( true );
	$renewal_interval = max( 1.0, floor( AI4SEO_SEMAPHORE_TTL_SECONDS / 3 ) );
	$renewal_is_due   = $last_renewal_at <= 0.0 || ( $current_time - $last_renewal_at ) >= $renewal_interval;

	if ( $renewal_is_due ) {
		if ( ai4seo_renew_semaphore( ai4seo_get_nextgen_gallery_image_import_semaphore_name() ) ) {
			$ai4seo_nextgen_gallery_image_import_last_lease_renewal_at = $current_time;
			return true;
		}
	} elseif ( ! $force_ownership_verification || ai4seo_verify_nextgen_gallery_image_import_lease_ownership() ) {
		return true;
	}

	return ai4seo_create_nextgen_import_error(
		'nextgen_import_lease_lost',
		esc_html__( 'The NextGen Gallery image import lock was lost. Please try again.', 'ai-for-seo' ),
		24147525
	);
}


/**
 * Builds the stable missing-alt repair error contract.
 *
 * @param int  $picture_id NextGEN picture ID.
 * @param bool $cache_failure Whether persistent cache-version invalidation failed.
 * @return WP_Error
 */
function ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( int $picture_id, bool $cache_failure = false ): WP_Error {
	if ( $cache_failure ) {
		/* translators: NextGen Gallery picture ID */
		$message = esc_html__( 'Could not finish repairing the imported NextGen Gallery image with pid %s', 'ai-for-seo' );
	} else {
		/* translators: NextGen Gallery picture ID */
		$message = esc_html__( 'Could not repair the imported NextGen Gallery image with pid %s', 'ai-for-seo' );
	}

	return ai4seo_create_nextgen_import_error(
		$cache_failure ? 'nextgen_postmeta_repair_cache_invalidation_failed' : 'nextgen_postmeta_repair_failed',
		sprintf( $message, $picture_id ),
		22147525,
		984321666,
		$picture_id
	);
}


/**
 * Builds the stable companion-alt insertion error for a newly imported NextGEN post.
 *
 * @param int $picture_id NextGEN picture ID.
 * @return WP_Error
 */
function ai4seo_create_nextgen_gallery_image_alt_postmeta_insert_error( int $picture_id ): WP_Error {
	return ai4seo_create_nextgen_import_error(
		'nextgen_postmeta_insert_failed',
		sprintf(
			/* translators: NextGen Gallery picture ID */
			esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
			$picture_id
		),
		22147525,
		984321666,
		$picture_id
	);
}


/**
 * Builds a cache-finalization error only after the checked write has failed.
 *
 * @param bool $is_repair Whether the last mutation repaired an existing import.
 * @param int  $picture_id Last affected NextGEN picture ID.
 * @return WP_Error Stable cache-finalization error with current database context.
 */
function ai4seo_create_nextgen_batch_cache_failure_error( bool $is_repair, int $picture_id ): WP_Error {
	if ( $is_repair ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id, true );
	}

	return ai4seo_create_nextgen_import_error(
		'nextgen_import_cache_invalidation_failed',
		sprintf(
			/* translators: NextGen Gallery picture ID */
			esc_html__( 'Could not finish importing the NextGen Gallery image with pid %s', 'ai-for-seo' ),
			$picture_id
		),
		21147525,
		984321665,
		$picture_id
	);
}


/**
 * Reads one unambiguous NextGEN attachment-alt postmeta state without using metadata caches.
 *
 * @param int $post_id Imported AI4SEO post ID.
 * @return array|false Exact state, or false for invalid, ambiguous, or failed reads.
 */
function ai4seo_read_nextgen_gallery_image_alt_postmeta_state( int $post_id ) {
	global $wpdb;

	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return false;
	}

	$sql = ai4seo_prepare_database_query(
		'SELECT meta_id, meta_value
		FROM {{postmeta_table}}
		WHERE post_id = {{post_id}}
		AND meta_key = {{meta_key}}
		ORDER BY meta_id ASC
		LIMIT 2',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact post/key pair is bounded to two rows to prove an unambiguous metadata owner.
		)
	);

	if ( false === $sql ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares the exact post/key values; ownership verification must bypass possibly stale metadata caches.
	$rows = $wpdb->get_results( $sql, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) {
		return false;
	}

	if ( ! $rows ) {
		return array(
			'exists'  => false,
			'meta_id' => 0,
			'value'   => '',
		);
	}

	$meta_id = absint( $rows[0]['meta_id'] ?? 0 );

	if ( $meta_id <= 0 || ! array_key_exists( 'meta_value', $rows[0] ) ) {
		return false;
	}

	return array(
		'exists'  => true,
		'meta_id' => $meta_id,
		'value'   => (string) $rows[0]['meta_value'],
	);
}


/**
 * Confirms that one post still owns the expected NextGEN provider picture ID.
 *
 * @param int $post_id Imported AI4SEO post ID.
 * @param int $picture_id NextGEN picture ID.
 * @return bool True only for the current expected post type and parent.
 */
function ai4seo_is_current_nextgen_gallery_image_post_owner( int $post_id, int $picture_id ): bool {
	global $wpdb;

	$post_id    = absint( $post_id );
	$picture_id = absint( $picture_id );

	if ( $post_id <= 0 || $picture_id <= 0 ) {
		return false;
	}

	$sql = ai4seo_prepare_database_query(
		'SELECT ID
		FROM {{posts_table}}
		WHERE ID = {{post_id}}
		AND post_type = {{post_type}}
		AND post_parent = {{picture_id}}
		LIMIT 1',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_id'     => ai4seo_database_scalar_binding( '%d', $post_id ),
			'post_type'   => ai4seo_database_scalar_binding( '%s', AI4SEO_NEXTGEN_GALLERY_POST_TYPE ),
			'picture_id'  => ai4seo_database_scalar_binding( '%d', $picture_id ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares every owner predicate; this one-shot write verification must observe the current posts row.
	$current_post_id = $wpdb->get_var( $sql );

	return ! $wpdb->last_error && absint( $current_post_id ) === $post_id;
}


/**
 * Deletes only the exact postmeta row and bytes inserted by this repair operation.
 *
 * @param int    $post_id Imported AI4SEO post ID.
 * @param int    $meta_id Operation-owned postmeta ID.
 * @param string $written_value Exact unslashed bytes written by this operation.
 * @return bool True when the operation-owned row is absent after the attempt.
 */
function ai4seo_delete_owned_nextgen_gallery_image_alt_postmeta( int $post_id, int $meta_id, string $written_value ): bool {
	global $wpdb;

	$post_id = absint( $post_id );
	$meta_id = absint( $meta_id );

	if ( $post_id <= 0 || $meta_id <= 0 ) {
		return false;
	}

	$sql = ai4seo_prepare_database_query(
		'DELETE FROM {{postmeta_table}}
		WHERE meta_id = {{meta_id}}
		AND post_id = {{post_id}}
		AND meta_key = {{meta_key}}
		AND BINARY meta_value = BINARY {{written_value}}
		LIMIT 1',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary key, post ID, and exact bytes constrain deletion to the operation-owned insert.
			'written_value'  => ai4seo_database_scalar_binding( '%s', $written_value ),
		)
	);

	if ( false === $sql ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares the exact primary/post/key/value ownership predicate; exact caches are invalidated by the caller.
	$deleted_rows = $wpdb->query( $sql );

	if ( false === $deleted_rows || $wpdb->last_error || (int) $deleted_rows > 1 ) {
		return false;
	}

	if ( 1 === (int) $deleted_rows ) {
		return true;
	}

	$current_state = ai4seo_read_nextgen_gallery_image_alt_postmeta_state( $post_id );

	return is_array( $current_state ) && ( ! $current_state['exists'] || $meta_id !== $current_state['meta_id'] );
}


/**
 * Repairs one missing NextGEN alt row while the shared per-post alt lock is held.
 *
 * @param int    $post_id Imported AI4SEO post ID.
 * @param int    $picture_id NextGEN picture ID.
 * @param string $provider_alt_text Sanitized provider alt text.
 * @param bool   $defer_persistent_cache_invalidation Whether the owning batch will publish persistent invalidations once.
 * @return true|WP_Error True when no repair is needed or the repair succeeds.
 */
function ai4seo_repair_nextgen_gallery_image_alt_postmeta_while_locked(
	int $post_id,
	int $picture_id,
	string $provider_alt_text,
	bool $defer_persistent_cache_invalidation = false
) {
	global $wpdb;

	$preflight_state = ai4seo_read_nextgen_gallery_image_alt_postmeta_state( $post_id );

	if ( false === $preflight_state ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	if ( $preflight_state['exists'] ) {
		return ai4seo_invalidate_nextgen_repair_caches( $post_id, false, $defer_persistent_cache_invalidation )
			? true
			: ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id, true );
	}

	$postmeta_insert_query = ai4seo_prepare_database_query(
		'INSERT INTO {{postmeta_insert_table}} (post_id, meta_key, meta_value)
		SELECT {{insert_post_id}}, {{insert_meta_key}}, {{insert_meta_value}}
		WHERE EXISTS (
			SELECT 1
			FROM {{posts_table}}
			WHERE ID = {{owner_post_id}}
			AND post_type = {{owner_post_type}}
			AND post_parent = {{owner_picture_id}}
		)
		AND NOT EXISTS (
			SELECT 1
			FROM {{postmeta_exists_table}}
			WHERE post_id = {{existing_post_id}}
			AND meta_key = {{existing_meta_key}}
		)',
		array(
			'postmeta_insert_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'insert_post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
			'insert_meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ),
			'insert_meta_value'     => ai4seo_database_scalar_binding( '%s', $provider_alt_text ),
			'posts_table'           => ai4seo_database_identifier_binding( 'table.posts' ),
			'owner_post_id'         => ai4seo_database_scalar_binding( '%d', $post_id ),
			'owner_post_type'       => ai4seo_database_scalar_binding( '%s', AI4SEO_NEXTGEN_GALLERY_POST_TYPE ),
			'owner_picture_id'      => ai4seo_database_scalar_binding( '%d', $picture_id ),
			'postmeta_exists_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'existing_post_id'      => ai4seo_database_scalar_binding( '%d', $post_id ),
			'existing_meta_key'     => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact post/key pair guards the insert while the shared advisory lock serializes cooperating writers.
		)
	);

	if ( false === $postmeta_insert_query ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler prepares the exact owner and metadata predicates; post-write verification and operation-owned compensation follow before success.
	$postmeta_insert_result = $wpdb->query( $postmeta_insert_query );

	if ( false === $postmeta_insert_result || $wpdb->last_error || ( 0 !== (int) $postmeta_insert_result && 1 !== (int) $postmeta_insert_result ) ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	$inserted_meta_id = 1 === (int) $postmeta_insert_result ? absint( $wpdb->insert_id ) : 0;

	ai4seo_invalidate_postmeta_caches( array( $post_id ) );

	$current_state = ai4seo_read_nextgen_gallery_image_alt_postmeta_state( $post_id );
	$current_owner = ai4seo_is_current_nextgen_gallery_image_post_owner( $post_id, $picture_id );

	if ( 0 === (int) $postmeta_insert_result ) {
		if ( ! is_array( $current_state ) || ! $current_state['exists'] || ! $current_owner ) {
			return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
		}

		return ai4seo_invalidate_nextgen_repair_caches( $post_id, true, $defer_persistent_cache_invalidation )
			? true
			: ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id, true );
	}

	$insert_is_verified = $inserted_meta_id > 0
		&& is_array( $current_state )
		&& $current_state['exists']
		&& $inserted_meta_id === $current_state['meta_id']
		&& $provider_alt_text === $current_state['value']
		&& $current_owner;

	if ( ! $insert_is_verified ) {
		$rollback_succeeded = $inserted_meta_id > 0
			&& ai4seo_delete_owned_nextgen_gallery_image_alt_postmeta( $post_id, $inserted_meta_id, $provider_alt_text );

		ai4seo_invalidate_postmeta_caches( array( $post_id ) );
		ai4seo_invalidate_environmental_variable_cache( AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES );

		if ( ! $rollback_succeeded ) {
			ai4seo_debug_message( 984321728, 'Could not remove an unverifiable operation-owned NextGEN alt-text repair row without overwriting a concurrent writer.', true );
		}

		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	return ai4seo_invalidate_nextgen_repair_caches( $post_id, true, $defer_persistent_cache_invalidation )
		? true
		: ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id, true );
}


/**
 * Repairs a missing provider-alt row for an existing imported NextGEN post.
 *
 * Existing rows, including intentionally empty values, are never overwritten.
 *
 * @param int    $post_id Imported AI4SEO post ID.
 * @param int    $picture_id NextGEN picture ID.
 * @param string $provider_alt_text Sanitized provider alt text.
 * @param bool   $defer_persistent_cache_invalidation Whether the owning batch will publish persistent invalidations once.
 * @return true|WP_Error True when no repair is needed or the repair succeeds.
 */
function ai4seo_repair_nextgen_gallery_image_alt_postmeta(
	int $post_id,
	int $picture_id,
	string $provider_alt_text,
	bool $defer_persistent_cache_invalidation = false
) {
	$post_id           = absint( $post_id );
	$picture_id        = absint( $picture_id );
	$provider_alt_text = sanitize_text_field( $provider_alt_text );

	if ( $post_id <= 0 || $picture_id <= 0 ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	if ( '' === $provider_alt_text ) {
		return ai4seo_invalidate_nextgen_repair_caches( $post_id, false, $defer_persistent_cache_invalidation )
			? true
			: ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id, true );
	}

	$lock_name = ai4seo_get_attachment_alt_text_postmeta_lock_name( $post_id );

	if ( '' === $lock_name || ! ai4seo_acquire_database_advisory_lock( $lock_name ) ) {
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	$repair_result = ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	$lock_released = false;

	try {
		$repair_result = ai4seo_repair_nextgen_gallery_image_alt_postmeta_while_locked(
			$post_id,
			$picture_id,
			$provider_alt_text,
			$defer_persistent_cache_invalidation
		);
	} finally {
		$lock_released = ai4seo_release_database_advisory_lock( $lock_name );
	}

	if ( ! $lock_released ) {
		ai4seo_debug_message( 984321729, 'Could not release the NextGEN alt-text repair database advisory lock.', true );
		return ai4seo_create_nextgen_gallery_image_alt_postmeta_repair_error( $picture_id );
	}

	return $repair_result;
}


/**
 * Returns the site/picture-scoped advisory-lock name for one NextGEN import owner.
 *
 * @param int $picture_id NextGEN picture ID.
 * @return string Lock name within MySQL's 64-byte limit.
 */
function ai4seo_get_nextgen_gallery_image_picture_lock_name( int $picture_id ): string {
	global $wpdb;

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$table_prefix  = isset( $wpdb->prefix ) && is_string( $wpdb->prefix ) ? $wpdb->prefix : '';
	$scope_hash    = hash( 'sha256', $database_name . '|' . $table_prefix . '|' . get_current_blog_id() . '|' . absint( $picture_id ) );

	return 'ai4seo_ngg_picture_' . substr( $scope_hash, 0, 40 );
}


/**
 * Inserts one NextGEN picture under a database-fenced ownership check.
 *
 * The import-wide option lease can expire while MIME or filesystem work is in progress. A
 * per-picture database lock remains authoritative on legacy MySQL hosts where the outer named
 * lock is unavailable, and the fresh ownership read prevents a later importer from duplicating
 * a post committed by the previous lock holder.
 *
 * @param array  $image NextGEN picture row.
 * @param string $gallery_path NextGEN gallery path.
 * @param bool   $defer_persistent_cache_invalidation Whether the owning batch will publish caches once.
 * @return int|WP_Error Inserted/existing post ID, or an insertion error.
 */
function ai4seo_insert_nextgen_gallery_image_post( array $image, string $gallery_path, bool $defer_persistent_cache_invalidation = false ) {
	global $wpdb;

	$picture_id = absint( $image['pid'] ?? 0 );
	$lock_name  = ai4seo_get_nextgen_gallery_image_picture_lock_name( $picture_id );

	if ( $picture_id <= 0 || '' === $lock_name || ! ai4seo_acquire_database_advisory_lock( $lock_name ) ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_import_busy',
			esc_html__( 'A NextGen Gallery image import is already processing this picture. Please try again shortly.', 'ai-for-seo' ),
			23147525,
			0,
			$picture_id
		);
	}

	$insert_result = ai4seo_create_nextgen_import_error(
		'nextgen_post_insert_failed',
		sprintf(
			/* translators: NextGen Gallery picture ID */
			esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
			$picture_id
		),
		21147525,
		984321665,
		$picture_id
	);
	$lock_released    = false;
	$resolved_post_id = 0;
	$post_was_created = false;

	try {
		$imported_map = ai4seo_read_imported_nextgen_gallery_image_map_for_picture_ids( array( $picture_id ) );

		if ( false === $imported_map ) {
			$insert_result = ai4seo_create_nextgen_import_error(
				'nextgen_imported_picture_read_failed',
				esc_html__( 'Database error while reading imported NextGen Gallery images.', 'ai-for-seo' ),
				784322908,
				984321697,
				$picture_id
			);
		} elseif ( isset( $imported_map[ $picture_id ] ) ) {
			$resolved_post_id = absint( $imported_map[ $picture_id ] );
			$insert_result    = $resolved_post_id;
		} else {
			$insert_result = ai4seo_insert_nextgen_gallery_image_post_while_locked(
				$image,
				$gallery_path,
				$defer_persistent_cache_invalidation
			);

			if ( ! is_wp_error( $insert_result ) ) {
				$resolved_post_id = absint( $insert_result );
				$post_was_created = true;
			}
		}
	} finally {
		$lock_released = ai4seo_release_database_advisory_lock( $lock_name );
	}

	if ( ! $lock_released ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_picture_lock_release_failed',
			esc_html__( 'Could not finish the NextGen Gallery image import lock. Please try again.', 'ai-for-seo' ),
			23147525,
			984321665,
			$picture_id
		);
	}

	if ( is_wp_error( $insert_result ) || $resolved_post_id <= 0 ) {
		return $insert_result;
	}

	$alt_text      = sanitize_text_field( $image['alttext'] ?? '' );
	$repair_result = ai4seo_repair_nextgen_gallery_image_alt_postmeta(
		$resolved_post_id,
		$picture_id,
		$alt_text,
		$post_was_created || $defer_persistent_cache_invalidation
	);

	if ( is_wp_error( $repair_result ) ) {
		if ( ! $post_was_created ) {
			return $repair_result;
		}

		$repair_database_error     = is_object( $wpdb ) ? (string) $wpdb->last_error : '';
		$postmeta_state            = '' !== $alt_text
			? ai4seo_read_nextgen_gallery_image_alt_postmeta_state( $resolved_post_id )
			: array( 'exists' => false );
		$postmeta_may_have_changed = '' !== $alt_text
			&& ( false === $postmeta_state || $postmeta_state['exists'] );

		if ( ! $defer_persistent_cache_invalidation ) {
			ai4seo_invalidate_nextgen_import_caches( $resolved_post_id, $postmeta_may_have_changed, false );
		}

		if ( is_object( $wpdb ) ) {
			$wpdb->last_error = $repair_database_error;
		}

		return ai4seo_create_nextgen_gallery_image_alt_postmeta_insert_error( $picture_id );
	}

	if ( ! $post_was_created ) {
		return $resolved_post_id;
	}

	if (
		! $defer_persistent_cache_invalidation
		&& ! ai4seo_invalidate_nextgen_import_caches( $resolved_post_id, '' !== $alt_text, false )
	) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_import_cache_invalidation_failed',
			sprintf(
				/* translators: NextGen Gallery picture ID */
				esc_html__( 'Could not finish importing the NextGen Gallery image with pid %s', 'ai-for-seo' ),
				$picture_id
			),
			21147525,
			984321665,
			$picture_id
		);
	}

	return $resolved_post_id;
}


/**
 * Performs one direct NextGEN post-row insert while its picture lock is held.
 *
 * Companion alt metadata is deliberately created only after this lock is released, through the
 * canonical per-post repair lock. Legacy MySQL can own only one named lock per connection.
 *
 * @param array  $image NextGEN picture row.
 * @param string $gallery_path NextGEN gallery path.
 * @param bool   $defer_persistent_cache_invalidation Whether the owning batch will publish caches once.
 * @return int|WP_Error Inserted post ID, or an insertion error.
 */
function ai4seo_insert_nextgen_gallery_image_post_while_locked( array $image, string $gallery_path, bool $defer_persistent_cache_invalidation = false ) {
	global $wpdb;

	$picture_id   = absint( $image['pid'] ?? 0 );
	$gallery_path = sanitize_text_field( $gallery_path );

	if ( $picture_id <= 0 || '' === $gallery_path ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_invalid_picture',
			sprintf(
				/* translators: NextGen Gallery picture ID */
				esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
				$picture_id
			),
			21147525,
			984321665,
			$picture_id
		);
	}

	$database_identifiers = ai4seo_get_database_identifier_registry();
	$posts_table          = $database_identifiers['table.posts'] ?? '';
	$postmeta_table       = $database_identifiers['table.postmeta'] ?? '';

	if ( '' === $posts_table || '' === $postmeta_table ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_post_insert_failed',
			sprintf(
				/* translators: NextGen Gallery picture ID */
				esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
				$picture_id
			),
			21147525,
			984321665,
			$picture_id
		);
	}

	$website_url = get_site_url();
	$image_guid  = untrailingslashit( $website_url ) . trailingslashit( $gallery_path ) . ( $image['filename'] ?? '' );
	$mime_type   = ai4seo_get_mime_type_from_url( $image_guid );

	if ( ! $mime_type ) {
		$mime_type = 'image/jpeg';
	}

	$image_date_timestamp = strtotime( $image['imagedate'] ?? '' );
	$modified_timestamp   = absint( $image['updated_at'] ?? 0 );

	// MIME discovery can perform remote work, so re-read the exact lease token immediately before mutation.
	$lease_result = ai4seo_renew_nextgen_gallery_image_import_lease( true );

	if ( is_wp_error( $lease_result ) ) {
		return $lease_result;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- A direct insert preserves the legacy unslashed storage and explicit date/modified fields; success performs all bypassed cache invalidation below.
	$post_insert_result = $wpdb->insert(
		$posts_table,
		array(
			'post_title'        => sanitize_text_field( $image['image_slug'] ?? '' ),
			'post_name'         => sanitize_text_field( $image['image_slug'] ?? '' ),
			'post_content'      => sanitize_text_field( $image['description'] ?? '' ),
			'post_excerpt'      => sanitize_text_field( $image['alttext'] ?? '' ),
			'post_type'         => AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
			'post_status'       => 'publish',
			'post_mime_type'    => sanitize_text_field( $mime_type ),
			'post_parent'       => $picture_id,
			'guid'              => esc_url( $image_guid ),
			'post_date'         => date( 'Y-m-d H:i:s', $image_date_timestamp ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'post_date_gmt'     => ai4seo_gmdate( 'Y-m-d H:i:s', $image_date_timestamp ),
			'post_modified'     => date( 'Y-m-d H:i:s', $modified_timestamp ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'post_modified_gmt' => ai4seo_gmdate( 'Y-m-d H:i:s', $modified_timestamp ),
		)
	);

	if ( false === $post_insert_result || 1 !== (int) $post_insert_result || $wpdb->last_error ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_post_insert_failed',
			sprintf(
				/* translators: NextGen Gallery picture ID */
				esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
				$picture_id
			),
			21147525,
			984321665,
			$picture_id
		);
	}

	// Never consume insert_id until the post insert itself has been proved successful.
	$new_post_id = absint( $wpdb->insert_id );

	if ( $new_post_id <= 0 ) {
		$insert_error = ai4seo_create_nextgen_import_error(
			'nextgen_post_insert_failed',
			sprintf(
				/* translators: NextGen Gallery picture ID */
				esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
				$picture_id
			),
			21147525,
			984321665,
			$picture_id
		);

		ai4seo_invalidate_nextgen_import_caches( 0, false, $defer_persistent_cache_invalidation );

		return $insert_error;
	}

	// The post is now visible to a stale-lease successor. Clear exact caches before yielding the
	// picture fence; persistent invalidation is published only after canonical alt finalization.
	ai4seo_invalidate_nextgen_import_caches( $new_post_id, false, true );

	return $new_post_id;
}


/**
 * Builds a retryable cleanup error after one import lock could not be released.
 *
 * The import itself may already be committed. A retry remains safe because picture ownership and
 * missing-alt repair are idempotent and are re-read before any new write.
 *
 * @return WP_Error
 */
function ai4seo_create_nextgen_import_lock_release_error(): WP_Error {
	return ai4seo_create_nextgen_import_error(
		'nextgen_import_lock_release_failed',
		esc_html__( 'Could not finish releasing the NextGen Gallery image import lock. Please try again.', 'ai-for-seo' ),
		23147525
	);
}


/**
 * Imports all currently eligible NextGEN pictures.
 *
 * @return true|WP_Error True on success, or the first import error.
 */
function ai4seo_process_nextgen_gallery_image_import() {
	$database_lock_name               = ai4seo_get_nextgen_gallery_image_import_database_lock_name();
	$supports_multiple_database_locks = ai4seo_database_supports_multiple_advisory_locks();
	$database_lock_acquired           = false;

	// Legacy MySQL permits only one named lock per connection. On those hosts the exact-token option
	// lease remains the import singleton so per-post repair can own its one required database lock.
	if ( $supports_multiple_database_locks ) {
		$database_lock_acquired = ai4seo_acquire_database_advisory_lock( $database_lock_name );
	}

	if ( $supports_multiple_database_locks && ! $database_lock_acquired ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_import_busy',
			esc_html__( 'A NextGen Gallery image import is already running. Please try again shortly.', 'ai-for-seo' ),
			23147525
		);
	}

	$semaphore_name         = ai4seo_get_nextgen_gallery_image_import_semaphore_name();
	$semaphore_acquired     = false;
	$semaphore_released     = true;
	$database_lock_released = true;
	$process_result         = ai4seo_create_nextgen_import_error(
		'nextgen_import_busy',
		esc_html__( 'A NextGen Gallery image import is already running. Please try again shortly.', 'ai-for-seo' ),
		23147525
	);

	try {
		if ( ! ai4seo_acquire_semaphore( $semaphore_name ) ) {
			$process_result = ai4seo_create_nextgen_import_error(
				'nextgen_import_busy',
				esc_html__( 'A NextGen Gallery image import is already running. Please try again shortly.', 'ai-for-seo' ),
				23147525
			);
		} else {
			$semaphore_acquired = true;
			ai4seo_reset_nextgen_gallery_image_import_lease_controller( true );
			$process_result = ai4seo_process_nextgen_gallery_image_import_while_locked();
		}
	} finally {
		if ( $semaphore_acquired ) {
			$semaphore_released = ai4seo_release_semaphore( $semaphore_name );
		}

		ai4seo_reset_nextgen_gallery_image_import_lease_controller();

		if ( $database_lock_acquired ) {
			$database_lock_released = ai4seo_release_database_advisory_lock( $database_lock_name );
		}
	}

	$lease_loss_was_already_reported = is_wp_error( $process_result )
		&& 'nextgen_import_lease_lost' === $process_result->get_error_code();

	// A detected takeover necessarily makes our token-matching semaphore release fail. Preserve that
	// more precise result, while every unexplained semaphore failure or advisory-lock failure remains fatal.
	if ( ! $database_lock_released || ( ! $semaphore_released && ! $lease_loss_was_already_reported ) ) {
		ai4seo_debug_message( 984321730, 'Could not release one or more NextGEN import locks; retrying the idempotent import remains safe.', true );
		return ai4seo_create_nextgen_import_lock_release_error();
	}

	return $process_result;
}


/**
 * Imports all eligible NextGEN pictures while the cross-request semaphore is held.
 *
 * @return true|WP_Error True on success, or the first import error.
 */
function ai4seo_process_nextgen_gallery_image_import_while_locked() {
	$maximum_picture_id = ai4seo_read_nextgen_gallery_image_max_picture_id();

	if ( false === $maximum_picture_id ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_picture_read_failed',
			esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ),
			784322906,
			984321695
		);
	}

	if ( 0 === $maximum_picture_id ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_no_pictures',
			esc_html__( 'No NextGen Gallery Images found', 'ai-for-seo' ),
			18147525
		);
	}

	$page_size                       = ai4seo_get_database_chunk_size();
	$after_picture_id                = 0;
	$picture_rows_found              = false;
	$gallery_ids_found               = false;
	$gallery_path_found              = false;
	$missing_gallery_path_picture_id = 0;

	// First traverse only bounded source pages to preflight every referenced gallery before any write.
	while ( $after_picture_id < $maximum_picture_id ) {
		$this_picture_page = ai4seo_read_nextgen_gallery_image_row_page(
			$after_picture_id,
			$page_size,
			$maximum_picture_id
		);

		if ( false === $this_picture_page ) {
			return ai4seo_create_nextgen_import_error(
				'nextgen_picture_read_failed',
				esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ),
				784322906,
				984321695
			);
		}

		if ( ! $this_picture_page ) {
			break;
		}

		$picture_rows_found = true;
		$this_gallery_paths = ai4seo_read_nextgen_gallery_path_map_for_picture_rows( $this_picture_page );

		if ( false === $this_gallery_paths ) {
			return ai4seo_create_nextgen_import_error(
				'nextgen_gallery_read_failed',
				esc_html__( 'Database error while reading NextGen Gallery galleries.', 'ai-for-seo' ),
				784322907,
				984321696
			);
		}

		if ( $this_gallery_paths ) {
			$gallery_path_found = true;
		}

		$lease_result = ai4seo_renew_nextgen_gallery_image_import_lease();

		if ( is_wp_error( $lease_result ) ) {
			return $lease_result;
		}

		$previous_picture_id = $after_picture_id;

		foreach ( $this_picture_page as $this_picture_row ) {
			$this_picture_id = absint( $this_picture_row['pid'] ?? 0 );
			$this_gallery_id = absint( $this_picture_row['galleryid'] ?? 0 );

			if ( $this_picture_id <= $previous_picture_id || $this_picture_id > $maximum_picture_id ) {
				return ai4seo_create_nextgen_import_error(
					'nextgen_picture_read_failed',
					esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ),
					784322906,
					984321695
				);
			}

			$previous_picture_id = $this_picture_id;

			if ( $this_gallery_id > 0 ) {
				$gallery_ids_found = true;
			}

			if ( 0 === $missing_gallery_path_picture_id
				&& ( $this_gallery_id <= 0 || ! isset( $this_gallery_paths[ $this_gallery_id ] ) ) ) {
				$missing_gallery_path_picture_id = $this_picture_id;
			}
		}

		$after_picture_id = $previous_picture_id;
	}

	if ( ! $picture_rows_found ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_no_pictures',
			esc_html__( 'No NextGen Gallery Images found', 'ai-for-seo' ),
			18147525
		);
	}

	if ( ! $gallery_ids_found ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_no_galleries',
			esc_html__( 'No NextGen Gallery galleries found', 'ai-for-seo' ),
			19147525
		);
	}

	if ( ! $gallery_path_found ) {
		return ai4seo_create_nextgen_import_error(
			'nextgen_no_gallery_paths',
			esc_html__( 'No NextGen Gallery gallery paths found', 'ai-for-seo' ),
			20147525
		);
	}

	if ( $missing_gallery_path_picture_id > 0 ) {
		return ai4seo_create_nextgen_gallery_path_missing_error( $missing_gallery_path_picture_id );
	}

	$after_picture_id         = 0;
	$batch_result             = true;
	$content_list_cache_dirty = false;
	$cache_failure_is_repair  = false;
	$cache_failure_picture_id = 0;

	// Process the same finite source snapshot in bounded pages. Imported-post ownership is read only
	// for each page, so neither provider rows nor the complete imported map coexist in memory.
	while ( $after_picture_id < $maximum_picture_id ) {
		$this_picture_page = ai4seo_read_nextgen_gallery_image_row_page(
			$after_picture_id,
			$page_size,
			$maximum_picture_id
		);

		if ( false === $this_picture_page ) {
			$batch_result = ai4seo_create_nextgen_import_error(
				'nextgen_picture_read_failed',
				esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ),
				784322906,
				984321695
			);
			break;
		}

		if ( ! $this_picture_page ) {
			break;
		}

		$this_gallery_paths = ai4seo_read_nextgen_gallery_path_map_for_picture_rows( $this_picture_page );

		if ( false === $this_gallery_paths ) {
			$batch_result = ai4seo_create_nextgen_import_error(
				'nextgen_gallery_read_failed',
				esc_html__( 'Database error while reading NextGen Gallery galleries.', 'ai-for-seo' ),
				784322907,
				984321696
			);
			break;
		}

		$this_picture_ids  = array_column( $this_picture_page, 'pid' );
		$this_imported_map = ai4seo_read_imported_nextgen_gallery_image_map_for_picture_ids( $this_picture_ids );

		if ( false === $this_imported_map ) {
			$batch_result = ai4seo_create_nextgen_import_error(
				'nextgen_imported_picture_read_failed',
				esc_html__( 'Database error while reading imported NextGen Gallery images.', 'ai-for-seo' ),
				784322908,
				984321697
			);
			break;
		}

		// Enter each page's mutation phase only after a fresh ownership read.
		$lease_result = ai4seo_renew_nextgen_gallery_image_import_lease( true );

		if ( is_wp_error( $lease_result ) ) {
			$batch_result = $lease_result;
			break;
		}

		$previous_picture_id = $after_picture_id;

		foreach ( $this_picture_page as $nextgen_gallery_image ) {
			$lease_result = ai4seo_renew_nextgen_gallery_image_import_lease();

			if ( is_wp_error( $lease_result ) ) {
				$batch_result = $lease_result;
				break 2;
			}

			$picture_id = absint( $nextgen_gallery_image['pid'] ?? 0 );
			$gallery_id = absint( $nextgen_gallery_image['galleryid'] ?? 0 );

			if ( $picture_id <= $previous_picture_id || $picture_id > $maximum_picture_id ) {
				$batch_result = ai4seo_create_nextgen_import_error(
					'nextgen_picture_read_failed',
					esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ),
					784322906,
					984321695
				);
				break 2;
			}

			$previous_picture_id = $picture_id;

			// Recheck the exact page mapping because the provider can change after the write-free preflight.
			if ( $gallery_id <= 0 || ! isset( $this_gallery_paths[ $gallery_id ] ) ) {
				$batch_result = ai4seo_create_nextgen_gallery_path_missing_error( $picture_id );
				break 2;
			}

			if ( isset( $this_imported_map[ $picture_id ] ) ) {
				$content_list_cache_dirty = true;
				$cache_failure_is_repair  = true;
				$cache_failure_picture_id = $picture_id;
				$repair_result            = ai4seo_repair_nextgen_gallery_image_alt_postmeta(
					$this_imported_map[ $picture_id ],
					$picture_id,
					sanitize_text_field( $nextgen_gallery_image['alttext'] ?? '' ),
					true
				);

				if ( is_wp_error( $repair_result ) ) {
					$batch_result = $repair_result;
					break 2;
				}

				continue;
			}

			$content_list_cache_dirty = true;
			$cache_failure_is_repair  = false;
			$cache_failure_picture_id = $picture_id;
			$insert_result            = ai4seo_insert_nextgen_gallery_image_post(
				$nextgen_gallery_image,
				$this_gallery_paths[ $gallery_id ],
				true
			);

			if ( is_wp_error( $insert_result ) ) {
				$batch_result = $insert_result;
				break 2;
			}
		}

		$after_picture_id = $previous_picture_id;
	}

	// Publish persistent invalidations exactly once after the complete batch state (including
	// partial-error mutations) is visible. This avoids row-count loop guards and prevents readers
	// from caching an intermediate row-by-row namespace.
	if ( $content_list_cache_dirty ) {
		global $wpdb;

		// Persistent cache publication is a mutation boundary even after a partially committed batch.
		$lease_result = ai4seo_renew_nextgen_gallery_image_import_lease( true );

		if ( is_wp_error( $lease_result ) ) {
			return $lease_result;
		}

		$environmental_caches_invalidated = ai4seo_invalidate_nextgen_environmental_caches( true );
		$environmental_database_error     = $environmental_caches_invalidated || ! is_object( $wpdb )
			? ''
			: (string) $wpdb->last_error;
		$cache_version_bumped             = ai4seo_force_bump_content_type_list_cache_version();
		$cache_version_database_error     = $cache_version_bumped || ! is_object( $wpdb )
			? ''
			: (string) $wpdb->last_error;

		if ( ! $environmental_caches_invalidated || ! $cache_version_bumped ) {
			if ( is_object( $wpdb ) ) {
				$wpdb->last_error = '' !== $cache_version_database_error
					? $cache_version_database_error
					: $environmental_database_error;
			}

			return ai4seo_create_nextgen_batch_cache_failure_error(
				$cache_failure_is_repair,
				$cache_failure_picture_id
			);
		}
	}

	return $batch_result;
}


/**
 * Import NextGen Gallery images into AI4SEO custom post type attachments.
 *
 * @return void
 */
function ai4seo_import_nextgen_gallery_images() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Preserve defense in depth for direct callbacks that bypass the central dispatcher.
	if ( ! ai4seo_require_ajax_administration() ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 18147524 );
		return;
	}

	$import_result = ai4seo_process_nextgen_gallery_image_import();

	if ( is_wp_error( $import_result ) ) {
		$error_data    = $import_result->get_error_data();
		$error_data    = is_array( $error_data ) ? $error_data : array();
		$debug_code    = absint( $error_data['debug_code'] ?? 0 );
		$debug_message = sanitize_text_field( $error_data['debug_message'] ?? '' );
		$ajax_code     = absint( $error_data['ajax_code'] ?? 999 );

		if ( $debug_code > 0 && '' !== $debug_message ) {
			ai4seo_debug_message( $debug_code, $debug_message, true );
		}

		ai4seo_send_ajax_error( $import_result->get_error_message(), $ajax_code );
		return;
	}

	ai4seo_send_ajax_success();
}
