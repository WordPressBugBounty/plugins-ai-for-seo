<?php
/**
 * Called via AJAX.
 * Generates metadata through our RobHub API for a post and returns it as JSON.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_manage_this_plugin() ) {
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

$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

if ( ! $ai4seo_is_robhub_account_synced ) {
	ai4seo_send_ajax_error( esc_html__( 'Failed to verify your license data. Please check your account settings.', 'ai-for-seo' ), 121320825 );
}


// === CHECK PARAMETER: POST ID =========================================================== \\

// get sanitized post id parameter.
$ai4seo_post_id = absint( wp_unslash( $_REQUEST['ai4seo_post_id'] ?? 0 ) );

if ( $ai4seo_post_id <= 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Post id is invalid.', 'ai-for-seo' ), 34127323 );
}

// Generation can later persist metadata, so enforce WordPress's permission for this post object.
if ( ! ai4seo_can_edit_post( $ai4seo_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this entry.', 'ai-for-seo' ), 34127324 );
}


// === CHECK PARAMETER: CONTENT ========================================================== \\

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

$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

if ( ! $ai4seo_active_meta_tags ) {
	ai4seo_send_ajax_error( esc_html__( 'No active meta tags found.', 'ai-for-seo' ), 3711221025 );
}


// ___________________________________________________________________________________________ \\
// === PREPARE POST CONTENT ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Reuse the same preparation contract as cron so manual and automated requests classify identical content equally.
$ai4seo_prepared_content = ai4seo_prepare_metadata_generation_content_data(
	$ai4seo_post_id,
	$ai4seo_post_content
);
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
$ai4seo_post_permalink = get_permalink( $ai4seo_post_id );

if ( $ai4seo_post_permalink ) {
	$ai4seo_robhub_api_call_parameters['content_url'] = $ai4seo_post_permalink;
}


// collect and build field instructions.
$ai4seo_field_instructions       = array();
$ai4seo_metadata_prefixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
$ai4seo_metadata_suffixes        = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );
$ai4seo_placeholder_replacements = ai4seo_get_metadata_placeholder_replacements( $ai4seo_post_id );

// Resolve the title placeholder after common placeholders so RobHub receives final affixes for length budgeting.
$ai4seo_post_title_for_placeholders = sanitize_text_field( get_the_title( $ai4seo_post_id ) );

foreach ( $ai4seo_active_meta_tags as $ai4seo_this_active_meta_tag ) {
	$ai4seo_this_to_generate = in_array( $ai4seo_this_active_meta_tag, $ai4seo_generation_fields );
	$ai4seo_this_old_value   = $ai4seo_old_input_values[ $ai4seo_this_active_meta_tag ] ?? '';

	// Normalize client context and reject unresolved third-party templates before building RobHub instructions.
	$ai4seo_this_old_value   = is_scalar( $ai4seo_this_old_value ) ? (string) $ai4seo_this_old_value : '';
	$ai4seo_this_old_value   = ai4seo_prepare_third_party_seo_metadata_value_for_generation_context(
		$ai4seo_this_old_value,
		$ai4seo_post_id,
		$ai4seo_this_active_meta_tag
	);
	$ai4seo_this_prefix      = $ai4seo_metadata_prefixes[ $ai4seo_this_active_meta_tag ] ?? '';
	$ai4seo_this_suffix      = $ai4seo_metadata_suffixes[ $ai4seo_this_active_meta_tag ] ?? '';

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
$ai4seo_generated_at = time();
$ai4seo_this_success = ai4seo_save_generated_data_to_postmeta(
	$ai4seo_post_id,
	$ai4seo_new_metadata,
	true,
	$ai4seo_generated_at,
	$ai4seo_unresolved_generation_fields
);

if ( ! $ai4seo_this_success ) {
	ai4seo_debug_message( 141829626, 'Could not save generated metadata for post ID ' . $ai4seo_post_id . ': ' . ai4seo_stringify( $ai4seo_new_metadata ) );
	ai4seo_send_ajax_error( esc_html__( 'Could not save generated metadata.', 'ai-for-seo' ), 151829626 );
}


// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

// Normalize unavailable usage once so activity history and the AJAX response report the same credit value.
$ai4seo_credits_consumed = (int) ( $ai4seo_results['credits-consumed'] ?? 0 );

ai4seo_add_latest_activity_entry(
	$ai4seo_post_id,
	'success',
	'metadata-manually-generated',
	$ai4seo_credits_consumed
);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_ajax_response = array(
	'generated_data'      => $ai4seo_new_metadata,
	// The formatted timestamp is only for immediate label rendering; generated_at remains the stored source of truth.
	'generated_at'        => $ai4seo_generated_at,
	'generated_at_output' => ai4seo_format_unix_timestamp( $ai4seo_generated_at ),
	'credits_consumed'    => $ai4seo_credits_consumed,
	'new_credits_balance' => (int) ( $ai4seo_results['new-credits-balance'] ?? 0 ),
);

ai4seo_send_ajax_success( $ai4seo_ajax_response );
