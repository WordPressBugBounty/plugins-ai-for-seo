<?php
/**
 * Called via AJAX.
 * Generates metadata through our RobHub API for a post and returns it as JSON.
 *
 * @package AI_For_SEO
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_use_plugin_content() ) {
	return;
}

// Recheck the global AJAX nonce before handling this protected admin request.
if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 34127321 );
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// set false in production.
$ai4seo_debug = false;

// set content type to json.
if ( ! $ai4seo_debug ) {
	header( 'Content-Type: application/json' );
	ob_start();
}

// === CHECK ROBHUB ACCOUNT =============================================================== \\

// Check archive permission before account refresh or paid requests.
$ai4seo_taxonomy_request_id = absint( wp_unslash( $_REQUEST['ai4seo_post_id'] ?? 0 ) );
if ( ai4seo_is_taxonomy_proxy( $ai4seo_taxonomy_request_id ) && ! ai4seo_can_edit_taxonomy_proxy( $ai4seo_taxonomy_request_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'This term archive is unavailable for generation.', 'ai-for-seo' ), 731013003 );
	return;
}

// === CHECK PARAMETER: POST ID =========================================================== \\

// get sanitized post id parameter.
$ai4seo_post_id = absint( wp_unslash( $_REQUEST['ai4seo_post_id'] ?? 0 ) );

if ( $ai4seo_post_id <= 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Post id is invalid.', 'ai-for-seo' ), 34127323 );
}

$ai4seo_missing_post_error_message = sprintf(
	/* translators: %d: WordPress post ID. */
	esc_html__( 'This entry (ID %d) could not be found. Refresh the page and reopen the entry before generating metadata again.', 'ai-for-seo' ),
	$ai4seo_post_id
);

// Reject missing entries before permission checks and the paid generation request.
if ( ! get_post( $ai4seo_post_id ) ) {
	ai4seo_send_ajax_error( $ai4seo_missing_post_error_message, 923260001 );
}

// Generation can later persist metadata, so enforce WordPress's permission for this post object.
if ( ! ai4seo_can_edit_post( $ai4seo_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this entry.', 'ai-for-seo' ), 34127324 );
}

// Refresh shared account state only after authorizing the requested object.
$ai4seo_account_readiness        = ai4seo_maybe_refresh_account_state();
$ai4seo_is_robhub_account_synced = $ai4seo_account_readiness['credits_ready'];

if ( ! $ai4seo_is_robhub_account_synced ) {
	ai4seo_send_ajax_error( $ai4seo_account_readiness['message'], 121320825 );
	return;
}


// === CHECK PARAMETER: CONTENT ========================================================== \\

// Capture the existing failure before the API request; later failures must remain visible.
$ai4seo_activity_error_token = ai4seo_get_recent_activity_error_token( $ai4seo_post_id, 'metadata-bulk-generated' );

// get sanitized content parameter.
$ai4seo_post_content = sanitize_textarea_field( wp_unslash( $_REQUEST['ai4seo_content'] ?? '' ) );


// === CHECK PARAMETER: GENERATION FIELDS ==================================================== \\

// Preserve the old parameter shape while making the direct request read WPCS-compliant.
$ai4seo_generation_fields = ai4seo_deep_sanitize(
	isset( $_REQUEST['ai4seo_generation_fields'] )
		? map_deep( wp_unslash( $_REQUEST['ai4seo_generation_fields'] ), 'sanitize_text_field' )
		: array()
);

if ( ! is_array( $ai4seo_generation_fields ) || count( $ai4seo_generation_fields ) === 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1613301025 );
}

// Canonicalize submitted identifiers before exact membership checks against the active metadata list.
$ai4seo_generation_fields = ai4seo_normalize_metadata_identifier_list( $ai4seo_generation_fields );

if ( ! $ai4seo_generation_fields ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1613301025 );
}

if ( ! ai4seo_validate_editor_read_admission( $ai4seo_post_id, 'metadata', $ai4seo_generation_fields, false, array(), true ) ) {
	ai4seo_send_ajax_error( esc_html__( 'Some requested fields could not be read safely. Reopen this entry after correcting the stored data. No generation was started.', 'ai-for-seo' ), 728451921 );
	return;
}


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// Preserve the old-values shape because field lookups below expect the submitted metadata keys.
$ai4seo_old_input_values = ai4seo_deep_sanitize(
	isset( $_REQUEST['ai4seo_old_input_values'] )
		? map_deep( wp_unslash( $_REQUEST['ai4seo_old_input_values'] ), 'sanitize_text_field' )
		: array()
);


// === CHECK PARAMETER: ENTRY CUSTOM INSTRUCTIONS =========================================== \\

// Keep manual Generate side-effect free while preserving the distinction between absent and empty textarea values.
$ai4seo_entry_custom_instructions = ai4seo_get_generation_entry_custom_instructions_request_value();


// === PREPARE ADDITIONAL DETAILS ========================================================= \\

// Normalize the configured identifiers at this request boundary before building field instructions.
$ai4seo_active_meta_tags = ai4seo_normalize_metadata_identifier_list( ai4seo_get_active_meta_tags() );

if ( ! $ai4seo_active_meta_tags ) {
	ai4seo_send_ajax_error( esc_html__( 'No active meta tags found.', 'ai-for-seo' ), 3711221025 );
}

// Retain requested fields only when they are currently active, preserving their submitted order.
$ai4seo_requested_active_meta_tags = array();

foreach ( $ai4seo_generation_fields as $ai4seo_this_generation_field ) {
	if ( in_array( $ai4seo_this_generation_field, $ai4seo_active_meta_tags, true ) ) {
		$ai4seo_requested_active_meta_tags[] = $ai4seo_this_generation_field;
	}
}

$ai4seo_generation_fields = $ai4seo_requested_active_meta_tags;

if ( ! $ai4seo_generation_fields ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1613301025 );
}


// ___________________________________________________________________________________________ \\
// === PREPARE POST CONTENT ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Reuse the same preparation contract as cron so manual and automated requests classify identical content equally.
$ai4seo_prepared_content = ai4seo_get_metadata_generation_entry_context(
	$ai4seo_post_id,
	$ai4seo_post_content
);

if ( is_wp_error( $ai4seo_prepared_content ) || ! $ai4seo_prepared_content['content_extraction_succeeded'] ) {
	ai4seo_send_ajax_error( esc_html__( 'The page content could not be read for generation.', 'ai-for-seo' ), 917202601 );
}

$ai4seo_post_content     = $ai4seo_prepared_content['content'];
$ai4seo_post_context     = $ai4seo_prepared_content['post_context'];
$ai4seo_content_analysis = $ai4seo_prepared_content['content_analysis'];

// check if content is too large (should never happen as we already condensed the content).
if ( ai4seo_mb_strlen( $ai4seo_post_content ) > AI4SEO_MAX_TOTAL_CONTENT_SIZE ) {
	ai4seo_send_ajax_error( esc_html__( 'Content is too large.', 'ai-for-seo' ), 361229323 );
}


$ai4seo_post_content = sanitize_text_field( $ai4seo_post_content );
$ai4seo_post_context = sanitize_text_field( $ai4seo_post_context );

// check for a key phrase.
$ai4seo_keyphrase = sanitize_text_field( ai4seo_get_any_third_party_seo_plugin_keyphrase( $ai4seo_post_id ) );

// ___________________________________________________________________________________________ \\
// === EXECUTE API CALL ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_metadata_generation_language = ai4seo_get_posts_language( $ai4seo_post_id );

$ai4seo_robhub_api_call_parameters = array(
	'content'  => $ai4seo_post_content,
	'language' => $ai4seo_metadata_generation_language,
);

if ( '' !== $ai4seo_keyphrase ) {
	$ai4seo_robhub_api_call_parameters['keyphrase'] = $ai4seo_keyphrase;
}

$ai4seo_robhub_api_call_parameters['trigger']          = 'manual';
$ai4seo_robhub_api_call_parameters['website_context']  = ai4seo_get_website_context();
$ai4seo_robhub_api_call_parameters['post_context']     = $ai4seo_post_context;
$ai4seo_robhub_api_call_parameters['content_analysis'] = $ai4seo_content_analysis;

// url.
$ai4seo_post_permalink = $ai4seo_prepared_content['content_url'];

if ( $ai4seo_post_permalink ) {
	$ai4seo_robhub_api_call_parameters['content_url'] = $ai4seo_post_permalink;
}


// collect and build field instructions.
$ai4seo_field_instructions       = array();
$ai4seo_metadata_prefixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
$ai4seo_metadata_suffixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );
$ai4seo_placeholder_replacements = ai4seo_get_metadata_output_placeholder_replacements( $ai4seo_post_id );

// Resolve the title placeholder after common placeholders so RobHub receives final affixes for length budgeting.
$ai4seo_post_title_for_placeholders = sanitize_text_field( $ai4seo_prepared_content['content_analysis']['post_title'] ?? get_the_title( $ai4seo_post_id ) );

foreach ( $ai4seo_active_meta_tags as $ai4seo_this_active_meta_tag ) {
	$ai4seo_this_to_generate = in_array( $ai4seo_this_active_meta_tag, $ai4seo_generation_fields, true );
	$ai4seo_this_old_value   = $ai4seo_old_input_values[ $ai4seo_this_active_meta_tag ] ?? '';

	// Normalize client context and reject unresolved third-party templates before building RobHub instructions.
	$ai4seo_this_old_value = is_scalar( $ai4seo_this_old_value ) ? (string) $ai4seo_this_old_value : '';
	$ai4seo_this_old_value = ai4seo_prepare_third_party_seo_metadata_value_for_generation_context(
		$ai4seo_this_old_value,
		$ai4seo_post_id,
		$ai4seo_this_active_meta_tag
	);
	$ai4seo_this_prefix    = $ai4seo_metadata_prefixes[ $ai4seo_this_active_meta_tag ] ?? '';
	$ai4seo_this_suffix    = $ai4seo_metadata_suffixes[ $ai4seo_this_active_meta_tag ] ?? '';

	if ( ! $ai4seo_this_to_generate && ! $ai4seo_this_old_value ) {
		continue;
	}

	$ai4seo_this_prefix = ai4seo_replace_text_placeholders( $ai4seo_this_prefix, $ai4seo_placeholder_replacements );
	$ai4seo_this_suffix = ai4seo_replace_text_placeholders( $ai4seo_this_suffix, $ai4seo_placeholder_replacements );
	$ai4seo_this_prefix = ai4seo_replace_metadata_title_placeholder(
		$ai4seo_this_prefix,
		$ai4seo_post_title_for_placeholders
	);
	$ai4seo_this_suffix = ai4seo_replace_metadata_title_placeholder(
		$ai4seo_this_suffix,
		$ai4seo_post_title_for_placeholders
	);

	$ai4seo_field_instructions[ $ai4seo_this_active_meta_tag ] = array(
		'generate'  => $ai4seo_this_to_generate,
		'old_value' => $ai4seo_this_old_value,
		'prefix'    => $ai4seo_this_prefix,
		'suffix'    => $ai4seo_this_suffix,
	);
}

$ai4seo_robhub_api_call_parameters['approximate_cost']   = ai4seo_calculate_metadata_credits_cost_per_post( $ai4seo_generation_fields );
$ai4seo_robhub_api_call_parameters['field_instructions'] = $ai4seo_field_instructions;

// Collect the settings-level instructions with the optional unsaved editor value for this request.
$ai4seo_custom_instructions = ai4seo_get_generation_custom_instructions( 'metadata', $ai4seo_post_id, $ai4seo_entry_custom_instructions );

if ( $ai4seo_custom_instructions ) {
	// Pass only non-empty custom instruction scopes to keep old API payloads unchanged when no instructions exist.
	$ai4seo_robhub_api_call_parameters['custom_instructions'] = $ai4seo_custom_instructions;
}

// Inspect history before spending credits. Damaged history is never rewritten by this request.
$ai4seo_history_failure_reason = ai4seo_get_manual_generation_history_readiness( $ai4seo_post_id );
$ai4seo_history_read_succeeded = 'ready' === $ai4seo_history_failure_reason;

if ( ! $ai4seo_history_read_succeeded ) {
	if ( 'damaged_history' !== $ai4seo_history_failure_reason ) {
		ai4seo_send_ajax_error( esc_html__( 'Generation history could not be checked. No generation was started. Please try again after resolving the database error.', 'ai-for-seo' ), 728451922 );
		return;
	}

	$ai4seo_history_warning_acknowledged = '1' === sanitize_text_field( wp_unslash( $_REQUEST['ai4seo_allow_generation_without_history'] ?? '' ) );
	if ( ! $ai4seo_history_warning_acknowledged ) {
		ai4seo_send_ajax_success( array( 'generation_preflight' => 'history_warning' ) );
		return;
	}
}

$ai4seo_taxonomy_generation    = null;
$ai4seo_taxonomy_result_locked = false;
if ( ai4seo_is_taxonomy_proxy( $ai4seo_post_id ) ) {
	$ai4seo_taxonomy_generation = ai4seo_begin_taxonomy_manual_generation( $ai4seo_post_id, $ai4seo_prepared_content );
	if ( is_wp_error( $ai4seo_taxonomy_generation ) ) {
		ai4seo_send_ajax_error( esc_html__( 'This archive is busy or changed. Keep your drafts and reopen it before generating.', 'ai-for-seo' ), 731014001 );
		return;
	}
}

try {
	if ( $ai4seo_taxonomy_generation && ! ai4seo_persist_bulk_generation_processing_claim_ambiguous_spend( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $ai4seo_post_id, $ai4seo_taxonomy_generation['token'] ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Generation could not be reserved safely. Keep your drafts and try again.', 'ai-for-seo' ), 731014002 );
		return;
	}
	$ai4seo_results = ai4seo_robhub_api()->call( 'ai4seo/generate-all-metadata', $ai4seo_robhub_api_call_parameters );


	// ___________________________________________________________________________________________ \\
	// === CHECK RESULTS ========================================================================= \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	if ( ! ai4seo_robhub_api()->was_call_successful( $ai4seo_results ) ) {
		if ( isset( $ai4seo_results['message'] ) && $ai4seo_results['message'] && isset( $ai4seo_results['code'] ) && $ai4seo_results['code'] ) {
			ai4seo_send_ajax_error( esc_html( $ai4seo_results['message'] ), $ai4seo_results['code'] );
		} else {
			ai4seo_send_ajax_error( esc_html__( 'Could not execute API call.', 'ai-for-seo' ), 28127323 );
		}
	}

	$ai4seo_generated_data = $ai4seo_results['data'] ?? '';

	// check if data is set.
	if ( ! $ai4seo_generated_data || ! is_array( $ai4seo_generated_data ) ) {
		ai4seo_send_ajax_error( esc_html__( 'API call did not return valid data.', 'ai-for-seo' ), 48127323 );
	}


	// === PREPARE RESPONSE =============================================================================== \\

	// Prepare requested active fields through the shared save contract while retaining unresolved provenance identifiers.
	$ai4seo_prepared_generated_output    = ai4seo_prepare_generated_output_fields_for_save(
		'metadata',
		$ai4seo_generated_data,
		array_values( array_intersect( $ai4seo_generation_fields, $ai4seo_active_meta_tags ) ),
		$ai4seo_field_instructions
	);
	$ai4seo_new_metadata                 = $ai4seo_prepared_generated_output['values'];
	$ai4seo_unresolved_generation_fields = $ai4seo_prepared_generated_output['unresolved_fields'];

	// Log omissions for support while allowing every usable partial field to continue.
	if ( $ai4seo_unresolved_generation_fields ) {
		ai4seo_debug_message(
			672318905,
			'Metadata generation returned a partial response for post ID ' . $ai4seo_post_id
				. '. Unresolved fields: ' . implode( ', ', $ai4seo_unresolved_generation_fields )
		);
	}

	// A soft-failed response still needs at least one usable requested field before anything is saved.
	if ( ! $ai4seo_new_metadata ) {
		ai4seo_send_ajax_error( esc_html__( 'No metadata was generated.', 'ai-for-seo' ), 4111221025 );
	}


	// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

	// Save one timestamp and clear stale provenance for omissions so live values survive and remain eligible later.
	$ai4seo_generated_at           = time();
	$ai4seo_save_operation_details = array();
	if ( $ai4seo_taxonomy_generation ) {
		$ai4seo_taxonomy_result_locked = ai4seo_lock_taxonomy_generation_result( $ai4seo_post_id, $ai4seo_taxonomy_generation );
	}
	$ai4seo_this_success = $ai4seo_history_read_succeeded && ( ! $ai4seo_taxonomy_generation || $ai4seo_taxonomy_result_locked ) && ai4seo_save_generated_data_to_postmeta(
		$ai4seo_post_id,
		$ai4seo_new_metadata,
		true,
		$ai4seo_generated_at,
		$ai4seo_unresolved_generation_fields,
		$ai4seo_save_operation_details,
		$ai4seo_taxonomy_generation ? array( 'taxonomy_generation_source' => $ai4seo_prepared_content['source_fingerprint'] ) : array()
	);

	if ( ! $ai4seo_this_success ) {
		ai4seo_debug_message( 141829626, 'Could not save generated metadata for post ID ' . $ai4seo_post_id . ': ' . ai4seo_stringify( $ai4seo_new_metadata ) );
	}

	// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

	// Normalize unavailable usage and record only persisted fields so activity history matches the AJAX response.
	$ai4seo_credits_consumed = (int) ( $ai4seo_results['credits-consumed'] ?? 0 );

	if ( $ai4seo_this_success ) {
		ai4seo_add_latest_activity_entry(
			$ai4seo_post_id,
			'success',
			'metadata-manually-generated',
			$ai4seo_credits_consumed,
			'',
			array_keys( $ai4seo_new_metadata )
		);
	}


	// === BUILD SUCCESS RESPONSE ========================================================================== \\

	$ai4seo_ajax_response = ai4seo_build_manual_generation_response( $ai4seo_new_metadata, $ai4seo_results, $ai4seo_this_success, $ai4seo_generated_at, $ai4seo_save_operation_details );

	if ( $ai4seo_this_success && ! $ai4seo_unresolved_generation_fields && '' !== $ai4seo_activity_error_token
		&& ai4seo_resolve_recent_activity_error( $ai4seo_post_id, 'metadata-bulk-generated', $ai4seo_activity_error_token ) ) {
		$ai4seo_ajax_response['resolved_activity_tokens']['metadata-bulk-generated'] = $ai4seo_activity_error_token;
	}

	if ( $ai4seo_taxonomy_generation ) {
		if ( $ai4seo_this_success ) {
			// Editor results are drafts: finish ownership without claiming active-field coverage.
			ai4seo_apply_bulk_generation_result_transition( $ai4seo_post_id, array(), $ai4seo_taxonomy_generation['token'], array( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) );
		} else {
			ai4seo_release_bulk_generation_processing_claim( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $ai4seo_post_id, $ai4seo_taxonomy_generation['token'], $ai4seo_taxonomy_generation['pending'], $ai4seo_taxonomy_generation['force'], true );
		}
		$ai4seo_taxonomy_generation = null;
	}
	if ( $ai4seo_taxonomy_result_locked ) {
		ai4seo_release_database_advisory_lock( ai4seo_get_taxonomy_proxy_sync_lock_name() );
		$ai4seo_taxonomy_result_locked = false;
	}
	ai4seo_send_ajax_success( $ai4seo_ajax_response );
} finally {
	if ( $ai4seo_taxonomy_generation ) {
		ai4seo_release_bulk_generation_processing_claim( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $ai4seo_post_id, $ai4seo_taxonomy_generation['token'], $ai4seo_taxonomy_generation['pending'], $ai4seo_taxonomy_generation['force'], true );
	}
	if ( $ai4seo_taxonomy_result_locked ) {
		ai4seo_release_database_advisory_lock( ai4seo_get_taxonomy_proxy_sync_lock_name() );
	}
}
