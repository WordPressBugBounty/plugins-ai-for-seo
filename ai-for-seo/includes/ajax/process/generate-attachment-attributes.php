<?php
/**
 * Called via AJAX.
 * Generates attachment attributes through our RobHub API for a post and returns it as JSON.
 *
 * @package AI_For_SEO
 * @since 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_use_plugin_content() ) {
	return;
}

// Recheck the global AJAX nonce before handling this protected admin request.
if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 211823822 );
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();

// set false in production.
$ai4seo_debug = false;

// set content type to json.
if ( ! $ai4seo_debug ) {
	header( 'Content-Type: application/json' );
	ob_start();
}


// ___________________________________________________________________________________________ \\
// === CHECK PARAMETER ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === CHECK ROBHUB ACCOUNT =============================================================== \\

$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

if ( ! $ai4seo_is_robhub_account_synced ) {
	ai4seo_send_ajax_error( esc_html__( 'Failed to verify your license data. Please check your account settings.', 'ai-for-seo' ), 131320825 );
}


// === CHECK PARAMETER: ATTACHMENT POST ID =========================================================== \\

// get sanitized post id parameter.
$ai4seo_this_attachment_post_id = absint( wp_unslash( $_REQUEST['ai4seo_post_id'] ?? 0 ) );

if ( $ai4seo_this_attachment_post_id <= 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Media post id is invalid.', 'ai-for-seo' ), 211823824 );
}

// Generation can later persist attributes, so enforce WordPress's permission for this media object.
if ( ! ai4seo_can_edit_post( $ai4seo_this_attachment_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this media entry.', 'ai-for-seo' ), 211823825 );
}


// === CHECK PARAMETER: GENERATION FIELDS ==================================================== \\

// Preserve the old parameter shape while making the direct request read WPCS-compliant.
$ai4seo_generation_fields = ai4seo_deep_sanitize(
	isset( $_REQUEST['ai4seo_generation_fields'] )
		? map_deep( wp_unslash( $_REQUEST['ai4seo_generation_fields'] ), 'sanitize_text_field' )
		: array()
);

if ( ! is_array( $ai4seo_generation_fields ) || count( $ai4seo_generation_fields ) === 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1713301026 );
}

// Canonicalize submitted identifiers before exact membership checks against the active attribute list.
$ai4seo_generation_fields = ai4seo_normalize_attachment_attribute_identifier_list( $ai4seo_generation_fields );

if ( ! $ai4seo_generation_fields ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1713301026 );
}


// === CHECK PARAMETER: OLD VALUES =========================================================== \\

// Preserve the old-values shape because field lookups below expect the submitted attribute keys.
$ai4seo_old_input_values = ai4seo_deep_sanitize(
	isset( $_REQUEST['ai4seo_old_input_values'] )
		? map_deep( wp_unslash( $_REQUEST['ai4seo_old_input_values'] ), 'sanitize_text_field' )
		: array()
);

// Prepare variables for prefixes and suffixes.
$ai4seo_attachment_attributes_prefixes      = ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES );
$ai4seo_attachment_attributes_suffixes      = ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES );
$ai4seo_attachment_placeholder_replacements = ai4seo_get_attachment_placeholder_replacements( $ai4seo_this_attachment_post_id );


// === GET ACTIVATE ATTACHMENT ATTRIBUTES ==================================================== \\

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

if ( ! $ai4seo_active_attachment_attributes ) {
	ai4seo_send_ajax_error( esc_html__( 'No active attachment attributes found.', 'ai-for-seo' ), 36124125 );
}

// Retain requested fields only when they are currently active, preserving their submitted order.
$ai4seo_requested_active_attachment_attributes = array();

foreach ( $ai4seo_generation_fields as $ai4seo_this_generation_field ) {
	if ( in_array( $ai4seo_this_generation_field, $ai4seo_active_attachment_attributes, true ) ) {
		$ai4seo_requested_active_attachment_attributes[] = $ai4seo_this_generation_field;
	}
}

$ai4seo_generation_fields = $ai4seo_requested_active_attachment_attributes;

if ( ! $ai4seo_generation_fields ) {
	ai4seo_send_ajax_error( esc_html__( 'Generation fields are invalid.', 'ai-for-seo' ), 1713301026 );
}


// === CHECK ATTACHMENT ======================================================================= \\

// Confirm the editor is targeting a native media attachment before saving attachment-specific postmeta.
if ( ! ai4seo_is_wordpress_attachment_post( $ai4seo_this_attachment_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'Media post not found.', 'ai-for-seo' ), 501013325 );
}

// === CHECK PARAMETER: ENTRY CUSTOM INSTRUCTIONS =========================================== \\

// Keep manual Generate side-effect free while preserving the distinction between absent and empty textarea values.
$ai4seo_entry_custom_instructions = ai4seo_get_generation_entry_custom_instructions_request_value();

// Resolve the media URL only after the attachment identity check above has passed.
$ai4seo_attachment_url = ai4seo_get_attachment_url( $ai4seo_this_attachment_post_id );

if ( ! $ai4seo_attachment_url ) {
	ai4seo_send_ajax_error( esc_html__( 'Media url not found.', 'ai-for-seo' ), 241823824 );
}

$ai4seo_attachment_mime_type = ai4seo_get_attachment_post_mime_type( $ai4seo_this_attachment_post_id );

// Check whether the media type can be generated by the current attachment-attribute flow.
if ( ! $ai4seo_attachment_mime_type || ! in_array( $ai4seo_attachment_mime_type, $ai4seo_allowed_attachment_mime_types, true ) ) {
	ai4seo_send_ajax_error(
		sprintf(
		/* translators: 1: Attachment mime type. 2: Attachment URL. */
			esc_html__( 'Media mime type is not allowed: %1$s for %2$s', 'ai-for-seo' ),
			$ai4seo_attachment_mime_type,
			$ai4seo_attachment_url
		),
		251823824
	);
}

// Resolve the shared full-versus-intermediate source before transport selection so manual and
// automated generation stay aligned.
$ai4seo_attachment_image_source = ai4seo_get_attachment_generation_image_source(
	$ai4seo_this_attachment_post_id,
	$ai4seo_attachment_url,
	$ai4seo_attachment_mime_type
);

if ( ! $ai4seo_attachment_image_source ) {
	ai4seo_send_ajax_error( esc_html__( 'Media source not found.', 'ai-for-seo' ), 241823825 );
}


// ___________________________________________________________________________________________ \\
// === EXECUTE CALL ========================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_attachment_attributes_generation_language = ai4seo_get_attachments_language( $ai4seo_this_attachment_post_id );
$ai4seo_wpml_language                             = sanitize_text_field( ai4seo_try_get_post_language_by_checking_multilanguage_plugins( $ai4seo_this_attachment_post_id ) );

$ai4seo_robhub_api_call_parameters = array(
	'language'        => $ai4seo_attachment_attributes_generation_language,
	'system_language' => sanitize_text_field( ai4seo_get_wordpress_language() ),
);

if ( $ai4seo_wpml_language ) {
	$ai4seo_robhub_api_call_parameters['wpml_language'] = $ai4seo_wpml_language;
}

$ai4seo_robhub_api_call_parameters['trigger']         = 'manual';
$ai4seo_robhub_api_call_parameters['website_context'] = ai4seo_get_website_context();
$ai4seo_attachment_usage_context                      = ai4seo_get_attachment_post_related_context( $ai4seo_this_attachment_post_id, true );

if ( ! empty( $ai4seo_attachment_usage_context ) ) {
	$ai4seo_robhub_api_call_parameters['attachment_usage_context'] = $ai4seo_attachment_usage_context;
}

// collect and build field instructions.
$ai4seo_field_instructions = array();

foreach ( $ai4seo_active_attachment_attributes as $ai4seo_this_active_attachment_attribute ) {
	$ai4seo_this_to_generate = in_array( $ai4seo_this_active_attachment_attribute, $ai4seo_generation_fields, true );
	$ai4seo_this_old_value   = $ai4seo_old_input_values[ $ai4seo_this_active_attachment_attribute ] ?? '';
	$ai4seo_this_prefix      = $ai4seo_attachment_attributes_prefixes[ $ai4seo_this_active_attachment_attribute ] ?? '';
	$ai4seo_this_suffix      = $ai4seo_attachment_attributes_suffixes[ $ai4seo_this_active_attachment_attribute ] ?? '';

	if ( ! $ai4seo_this_to_generate && ! $ai4seo_this_old_value ) {
		continue;
	}

	$ai4seo_this_prefix = ai4seo_replace_text_placeholders( $ai4seo_this_prefix, $ai4seo_attachment_placeholder_replacements );
	$ai4seo_this_suffix = ai4seo_replace_text_placeholders( $ai4seo_this_suffix, $ai4seo_attachment_placeholder_replacements );

	$ai4seo_field_instructions[ $ai4seo_this_active_attachment_attribute ] = array(
		'generate'  => $ai4seo_this_to_generate,
		'old_value' => $ai4seo_this_old_value,
		'prefix'    => $ai4seo_this_prefix,
		'suffix'    => $ai4seo_this_suffix,
	);
}

$ai4seo_robhub_api_call_parameters['approximate_cost']   = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post( $ai4seo_generation_fields );
$ai4seo_robhub_api_call_parameters['field_instructions'] = $ai4seo_field_instructions;

// Collect the settings-level instructions with the optional unsaved editor value for this request.
$ai4seo_custom_instructions = ai4seo_get_generation_custom_instructions( 'attachment_attributes', $ai4seo_this_attachment_post_id, $ai4seo_entry_custom_instructions );

if ( $ai4seo_custom_instructions ) {
	// Pass only non-empty custom instruction scopes to keep old API payloads unchanged when no instructions exist.
	$ai4seo_robhub_api_call_parameters['custom_instructions'] = $ai4seo_custom_instructions;
}


// === CALL ROBHUB API ONCE WITH THE PRESELECTED IMAGE INPUT METHOD ========================================== \\

// RobHub owns image recovery and model repairs, so issue one request through the shared image transport.
$ai4seo_results = ai4seo_call_attachment_attributes_generation_api(
	$ai4seo_attachment_image_source,
	$ai4seo_robhub_api_call_parameters
);

if ( ! ai4seo_robhub_api()->was_call_successful( $ai4seo_results ?? false ) ) {
	if ( isset( $ai4seo_results['message'] ) && $ai4seo_results['message'] && isset( $ai4seo_results['code'] ) && $ai4seo_results['code'] ) {
		ai4seo_send_ajax_error( esc_html( $ai4seo_results['message'] ), $ai4seo_results['code'] );
	} else {
		ai4seo_send_ajax_error( esc_html__( 'Could not execute API call.', 'ai-for-seo' ), 28127323 );
	}
}

$ai4seo_generated_data = $ai4seo_results['data'] ?? array();

if ( ! $ai4seo_generated_data || ! is_array( $ai4seo_generated_data ) ) {
	ai4seo_debug_message( 28173126, 'Attachment attributes generation API call did not return valid data: ' . ai4seo_stringify( $ai4seo_results ) );
	ai4seo_send_ajax_error( esc_html__( 'API call did not return valid data.', 'ai-for-seo' ), 431024824 );
}


// ___________________________________________________________________________________________ \\
// === CHECK RESULTS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === PREPARE RESPONSE ====================================================================== \\

// Prepare requested attributes through the shared save contract, including final affixes and immutable hard caps.
$ai4seo_prepared_generated_output    = ai4seo_prepare_generated_output_fields_for_save(
	'attachment_attributes',
	$ai4seo_generated_data,
	array_values( array_intersect( $ai4seo_generation_fields, $ai4seo_active_attachment_attributes ) ),
	$ai4seo_field_instructions,
	true
);
$ai4seo_new_attachment_attributes    = $ai4seo_prepared_generated_output['values'];
$ai4seo_unresolved_generation_fields = $ai4seo_prepared_generated_output['unresolved_fields'];

// Log omissions for support while allowing every usable partial attribute to continue.
if ( $ai4seo_unresolved_generation_fields ) {
	ai4seo_debug_message(
		762318905,
		'Media attribute generation returned a partial response for attachment post ID ' . $ai4seo_this_attachment_post_id
			. '. Unresolved fields: ' . implode( ', ', $ai4seo_unresolved_generation_fields )
	);
}

// A soft-failed response still needs at least one usable requested attribute before anything is saved.
if ( ! $ai4seo_new_attachment_attributes ) {
	ai4seo_send_ajax_error( esc_html__( 'No media attributes were generated.', 'ai-for-seo' ), 4111221026 );
}


// === SAVE GENERATED DATA TO DATABASE ================================================================= \\

// Save one timestamp and clear stale provenance for omissions so live values survive and remain eligible later.
$ai4seo_generated_at = time();
$ai4seo_this_success = ai4seo_save_generated_data_to_postmeta(
	$ai4seo_this_attachment_post_id,
	$ai4seo_new_attachment_attributes,
	true,
	$ai4seo_generated_at,
	$ai4seo_unresolved_generation_fields
);

if ( ! $ai4seo_this_success ) {
	ai4seo_debug_message( 30173126, 'Could not save media attributes for attachment post ID ' . $ai4seo_this_attachment_post_id . ': ' . ai4seo_stringify( $ai4seo_new_attachment_attributes ) );
	ai4seo_send_ajax_error( esc_html__( 'Could not save media attributes.', 'ai-for-seo' ), 3218161025 );
}

// === ADD LATEST ACTIVITY ENTRY ======================================================================= \\

// Normalize unavailable usage once so activity history and the AJAX response report the same credit value.
$ai4seo_credits_consumed = (int) ( $ai4seo_results['credits-consumed'] ?? 0 );

ai4seo_add_latest_activity_entry(
	$ai4seo_this_attachment_post_id,
	'success',
	'attachment-attributes-manually-generated',
	$ai4seo_credits_consumed
);


// === BUILD SUCCESS RESPONSE ========================================================================== \\

$ai4seo_response = array(
	'generated_data'      => $ai4seo_new_attachment_attributes,
	// The formatted timestamp is only for immediate label rendering; generated_at remains the stored source of truth.
	'generated_at'        => $ai4seo_generated_at,
	'generated_at_output' => ai4seo_format_unix_timestamp( $ai4seo_generated_at ),
	'credits_consumed'    => $ai4seo_credits_consumed,
	'new_credits_balance' => (int) ( $ai4seo_results['new-credits-balance'] ?? 0 ),
);

ai4seo_send_ajax_success( $ai4seo_response );
