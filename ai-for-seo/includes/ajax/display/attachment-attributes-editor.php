<?php
/**
 * Displays the metadata editor. Called via AJAX.
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
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 291920822 );
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

global $ai4seo_allowed_image_mime_types;


// === CHECK PARAMETER ============================================== \\

// Make sure that input-fields exist.
if ( ! defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) ) {
	ai4seo_send_ajax_error( esc_html__( 'An error occurred! Please check your settings or contact the plugin developer.', 'ai-for-seo' ), 221920824 );
}

// Get sanitized post id parameter.
$ai4seo_this_attachment_post_id = absint( $_REQUEST['attachment_post_id'] ?? 0 );

// validate post id.
if ( $ai4seo_this_attachment_post_id <= 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Post id is invalid.', 'ai-for-seo' ), 291920824 );
}

// The plugin-level role gate does not replace WordPress's object-level media permission check.
if ( ! ai4seo_can_edit_post( $ai4seo_this_attachment_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this media entry.', 'ai-for-seo' ), 291920825 );
}

// Normalize the navigation list before using it for authorization or next-entry selection.
$ai4seo_all_attachment_post_ids = isset( $_REQUEST['all_attachment_post_ids'] ) && is_array( $_REQUEST['all_attachment_post_ids'] )
	? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_REQUEST['all_attachment_post_ids'] ) ) ) ) )
	: array();

// Keep only editable navigation targets so one unrelated media entry cannot block the authorized editor.
$ai4seo_all_attachment_post_ids = ai4seo_filter_editable_post_ids( $ai4seo_all_attachment_post_ids );

// Reuse the ordered-list helper so media and metadata editors calculate their next-entry targets identically.
$ai4seo_next_attachment_post_id = ai4seo_get_next_post_id_from_ordered_post_ids( $ai4seo_this_attachment_post_id, $ai4seo_all_attachment_post_ids );

// get post object.
$ai4seo_this_attachment_post = get_post( $ai4seo_this_attachment_post_id );

if ( ! $ai4seo_this_attachment_post ) {
	ai4seo_send_ajax_error( esc_html__( 'Attachment Post not found.', 'ai-for-seo' ), 57177525 );
}


// === GET ADDITIONAL DETAILS ===================================================================== \\

$ai4seo_this_post_attachment_attributes = ai4seo_read_available_attachment_attributes( $ai4seo_this_attachment_post_id );

// Media source hints only compare active attachment attributes with SOOZ generated-data snapshots.
$ai4seo_attachment_attribute_source_details = ai4seo_read_attachment_attributes_editor_source_details(
	$ai4seo_this_attachment_post_id,
	$ai4seo_this_post_attachment_attributes
);

// Check if we have an image, by using $ai4seo_allowed_image_mime_types.
$ai4seo_this_attachment_mime_type = ai4seo_get_attachment_post_mime_type( $ai4seo_this_attachment_post_id );

$ai4seo_this_attachment_is_an_image = false;

foreach ( $ai4seo_allowed_image_mime_types as $ai4seo_this_allowed_image_mime_type ) {
	if ( strpos( $ai4seo_this_attachment_mime_type, $ai4seo_this_allowed_image_mime_type ) !== false ) {
		$ai4seo_this_attachment_is_an_image = true;
		break;
	}
}

$ai4seo_this_attachment_url = ai4seo_get_attachment_url( $ai4seo_this_attachment_post_id );

// fallback -> get guid.
if ( ! $ai4seo_this_attachment_url ) {
	$ai4seo_this_attachment_url = ai4seo_get_assets_images_url( 'icons/document-question-48x48.png' );
}

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

$ai4seo_settings_url                              = ai4seo_get_subpage_url( 'settings' );
$ai4seo_attachment_attributes_custom_instructions = ai4seo_read_custom_instructions_postmeta( $ai4seo_this_attachment_post_id, AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Use the shared AJAX modal headline shell so editor modals keep the same logo/title structure.
ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Media Attributes Editor', 'ai-for-seo' ) ) );

echo "<div class='ai4seo-modal-sub-headline'>";

ai4seo_echo_wp_kses(
	sprintf(
		/* translators: 1: Attachment title. 2: Attachment ID. */
		__( 'Manage media attributes for <b>%1$s</b> (#%2$d)', 'ai-for-seo' ),
		$ai4seo_this_post_attachment_attributes['title'],
		$ai4seo_this_attachment_post_id
	)
);

echo '</div>';

if ( ! $ai4seo_active_attachment_attributes ) {
	ai4seo_echo_wp_kses(
		ai4seo_get_editor_no_active_fields_notice_tag(
			__( 'No media attributes are active. Please activate at least one media attribute in the plugin settings to manage media attributes.', 'ai-for-seo' ),
			$ai4seo_settings_url
		)
	);
	return;
}

// add an left floating image of the attachment.
echo "<div class='ai4seo-attachment-editor-image-preview'>";
	echo "<img src='" . esc_url( $ai4seo_this_attachment_url ) . "' />";
echo '</div>';

// GENERATE ALL BUTTON.
echo "<div id='ai4seo-generate-all-attachment-attributes-button-hook'></div>";

// small gap.
echo "<div class='ai4seo-clear-both'></div>";

// Form.
echo "<div class='ai4seo-form ai4seo-editor-form'>";

	// Usage context status.
	echo "<div class='ai4seo-form-item ai4seo-attachment-usage-context-form-item'>";
		echo "<div class='ai4seo-attachment-usage-context-label-spacer' aria-hidden='true'></div>";

		echo "<div class='ai4seo-form-item-input-wrapper'>";
			echo "<div class='ai4seo-attachment-usage-context-status' data-attachment-post-id='" . esc_attr( $ai4seo_this_attachment_post_id ) . "'>";
				echo "<div class='ai4seo-attachment-usage-context-loading'>";
					ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'rotate', __( 'Loading', 'ai-for-seo' ), 'ai4seo-spinning-icon ai4seo-gray-icon ai4seo-16x16-icon' ) );
					echo '<span>' . esc_html__( 'Checking image usage context...', 'ai-for-seo' ) . '</span>';
				echo '</div>';

				echo "<div class='ai4seo-attachment-usage-context-result'></div>";
			echo '</div>';
		echo '</div>';
	echo '</div>';

	// === CUSTOM INSTRUCTIONS ================================================================================= \\

	$ai4seo_attachment_attributes_custom_instructions_input_name  = ai4seo_get_prefixed_input_name( 'attachment_attributes_editor_custom_instructions' );
	$ai4seo_attachment_attributes_custom_instructions_description = esc_html__( 'These instructions are used only for future media attribute generations for this attachment.', 'ai-for-seo' );

	// Reuse the shared custom-instruction form item so attachment editor markup follows settings fields.
	ai4seo_echo_wp_kses(
		ai4seo_get_custom_instructions_form_item_tag(
			$ai4seo_attachment_attributes_custom_instructions_input_name,
			$ai4seo_attachment_attributes_custom_instructions_input_name,
			$ai4seo_attachment_attributes_custom_instructions,
			esc_html__( 'Custom Instructions:', 'ai-for-seo' ),
			$ai4seo_attachment_attributes_custom_instructions_description,
			'ai4seo-editor-textarea ai4seo-entry-custom-instructions-input',
			esc_html__( 'Media Attribute Custom Instructions', 'ai-for-seo' ),
			'ai4seo-editor-custom-instructions-form-item ai4seo-attachment-custom-instructions-form-item',
			'',
			'attachment-attributes-editor'
		)
	);


	// === GO THROUGH EACH FIELD ================================================================================= \\

	$ai4seo_skipped_attachment_attributes = array();

	// Reuse the translated suffix while field names keep each help trigger distinct for assistive technology.
	$ai4seo_help_aria_label = __( 'Help', 'ai-for-seo' );

	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details ) {
		if ( ! in_array( $ai4seo_this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes ) ) {
			$ai4seo_skipped_attachment_attributes[] = $ai4seo_this_attachment_attribute_identifier;
			continue;
		}

		// Make sure that required value-entries exist.
		if ( ! isset( $ai4seo_this_attachment_attribute_details['name'] ) || ! isset( $ai4seo_this_attachment_attribute_details['input-type'] ) || ! isset( $ai4seo_this_attachment_attribute_details['hint'] ) ) {
			ai4seo_debug_message( 371217325, 'Missing required details for media attribute: ' . $ai4seo_this_attachment_attribute_identifier . ' - post id: ' . $ai4seo_this_attachment_post_id );
			continue;
		}

		if ( ! isset( $ai4seo_this_post_attachment_attributes[ $ai4seo_this_attachment_attribute_identifier ] ) ) {
			ai4seo_debug_message( 381217325, 'Media Attributes: Missing value for attribute: ' . $ai4seo_this_attachment_attribute_identifier . ' - post id: ' . $ai4seo_this_attachment_post_id );
			continue;
		}

		$ai4seo_this_attachment_attribute_value      = $ai4seo_this_post_attachment_attributes[ $ai4seo_this_attachment_attribute_identifier ];
		$ai4seo_this_attachment_attribute_input_name = ai4seo_get_prefixed_input_name( 'attachment_attribute_' . $ai4seo_this_attachment_attribute_identifier );

		// Keep each generated field in the shared editor form-item structure used by metadata fields.
		echo "<div class='ai4seo-form-item ai4seo-form-item-flush'>";

			// Separate the tooltip button from the native field label while preserving their visual grouping.
			echo "<span class='ai4seo-form-item-label'>";
				echo "<span class='ai4seo-label-with-tooltip'>";
					echo "<label for='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "'>";
						// Icon.
		if ( isset( $ai4seo_this_attachment_attribute_details['icon'] ) ) {
			ai4seo_echo_wp_kses( ai4seo_get_svg_tag( $ai4seo_this_attachment_attribute_details['icon'], '', 'ai4seo-24x24-icon ai4seo-gray-icon' ) );
			echo ' ';
		}

						// Name.
						echo esc_html( $ai4seo_this_attachment_attribute_details['name'] );
					echo '</label>';

					// Keep the button out of the field label to avoid nested interactive controls.
					ai4seo_echo_wp_kses(
						ai4seo_get_icon_with_tooltip_tag(
							$ai4seo_this_attachment_attribute_details['hint'],
							'',
							'circle-question',
							$ai4seo_this_attachment_attribute_details['name'] . ': ' . $ai4seo_help_aria_label
						)
					);
				echo '</span>';

				// Render the source hint directly under the label so it stays visually tied to this input.
		if ( isset( $ai4seo_attachment_attribute_source_details[ $ai4seo_this_attachment_attribute_identifier ] ) ) {
			ai4seo_echo_wp_kses(
				ai4seo_get_editor_field_source_message_tag( $ai4seo_attachment_attribute_source_details[ $ai4seo_this_attachment_attribute_identifier ] )
			);
		}
			echo '</span>';

			// Render the registry-defined control inside the shared generate-button wrapper.
			echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";

				// Text field.
		if ( 'textfield' === $ai4seo_this_attachment_attribute_details['input-type'] ) {
			echo "<input type='text' class='ai4seo-textfield ai4seo-editor-textfield' id='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "' name='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "' value='" . esc_attr( $ai4seo_this_attachment_attribute_value ) . "'/>";
		} elseif ( 'textarea' === $ai4seo_this_attachment_attribute_details['input-type'] ) {
			// Preserve multiline editing for attachment fields declared as textareas in the shared registry.
			echo "<textarea class='ai4seo-textarea ai4seo-editor-textarea ai4seo-auto-resize-textarea' id='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "' name='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "'>" . esc_textarea( $ai4seo_this_attachment_attribute_value ) . '</textarea>';
		}

			echo '</div>';
		echo '</div>';
	}

	// put the post id into a hidden field, so we have access to it after the form is submitted.
	echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr( ai4seo_get_prefixed_input_name( 'attachment_attributes_editor_post_id' ) ) . "' value='" . esc_attr( $ai4seo_this_attachment_post_id ) . "' />";

	// friendly reminder: $ai4seo_skipped_attachment_attributes.
	if ( $ai4seo_skipped_attachment_attributes ) {
		ai4seo_echo_wp_kses(
			ai4seo_get_editor_inactive_fields_notice_tag(
				$ai4seo_skipped_attachment_attributes,
				AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS,
				/* translators: 1: Comma-separated list of media attributes. 2: URL to plugin settings. */
				__( '<strong>Note:</strong> The following media attributes are currently inactive and not shown in this editor: %1$s. You can activate them in the <a href="%2$s" target="_blank">plugin settings</a>.', 'ai-for-seo' ),
				$ai4seo_settings_url
			)
		);
	}

	// === BUTTONS ROW ================================================================================= \\

	// Collect footer buttons before rendering the shared wrapper so the optional next-entry action keeps its current order.
	$ai4seo_modal_footer_button_tags = array(
		ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ),
	);

	// Let the shared editor helper add the next-entry action only when this media list has another attachment to edit.
	$ai4seo_modal_footer_button_tags[] = ai4seo_get_editor_save_next_button_tag(
		$ai4seo_next_attachment_post_id,
		'ai4seo_validate_attachment_attributes_editor_inputs',
		'ai4seo_open_attachment_attributes_editor_modal',
		array(
			$ai4seo_all_attachment_post_ids,
		)
	);

	// Keep the normal save action last, matching the previous explicit footer markup.
	$ai4seo_modal_footer_button_tags[] = ai4seo_get_submit_button_tag( esc_html__( 'Save changes', 'ai-for-seo' ), 'ai4seo-big-button ai4seo-lockable ai4seo-start-inactive', 'ai4seo_save_anything(jQuery(this), ai4seo_validate_attachment_attributes_editor_inputs, ai4seo_handle_attachment_attributes_editor_save_success);' );

	// Render the action row through the shared footer helper used by AJAX modals.
	ai4seo_echo_wp_kses( ai4seo_get_modal_footer_tag( $ai4seo_modal_footer_button_tags ) );
	echo '</div>';
