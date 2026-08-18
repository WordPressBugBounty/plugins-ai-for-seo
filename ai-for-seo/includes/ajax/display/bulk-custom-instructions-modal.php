<?php
/**
 * Displays the bulk custom-instructions modal. Called via AJAX.
 *
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_manage_this_plugin() ) {
	return;
}

// Recheck the global AJAX nonce before handling this protected admin request.
if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1507062599 );
	return;
}

// === CHECK PARAMETER ======================================================================= \\

$ai4seo_bulk_generation_queue_action = sanitize_key( wp_unslash( $_REQUEST['bulk_generation_queue_action'] ?? '' ) );
$ai4seo_context                      = sanitize_key( wp_unslash( $_REQUEST['context'] ?? '' ) );

// Accept native and custom table payloads in the same shape the modal submit endpoint will later process.
$ai4seo_post_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_REQUEST['post_ids'] ?? array() ) ) ) ) );

// The display endpoint renders only the custom-instructions modal, not generic bulk-action UI.
if ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS !== $ai4seo_bulk_generation_queue_action ) {
	ai4seo_send_ajax_error( esc_html__( 'The selected bulk action is invalid.', 'ai-for-seo' ), 1507062601 );
}

if ( ! ai4seo_is_bulk_generation_queue_context( $ai4seo_context ) ) {
	ai4seo_send_ajax_error( esc_html__( 'The selected bulk action context is invalid.', 'ai-for-seo' ), 1507062602 );
}

if ( ! $ai4seo_post_ids ) {
	ai4seo_send_ajax_error( esc_html__( 'Please select at least one entry.', 'ai-for-seo' ), 1507062603 );
}

// Validate every modal target because the submitted selection can differ from the visible admin table.
if ( ! ai4seo_can_edit_post_ids( $ai4seo_post_ids ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit one or more selected entries.', 'ai-for-seo' ), 1507062604 );
}

// === PREPARE =============================================================================== \\

$ai4seo_selected_entries_count         = count( $ai4seo_post_ids );
$ai4seo_custom_instructions_input_name = ai4seo_get_prefixed_input_name( 'bulk_custom_instructions' );

// Map the validated bulk context to both modal copy and its equivalent editor tooltip; submit revalidates it.
$ai4seo_is_attachment_attributes_context     = AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $ai4seo_context;
$ai4seo_generation_scope_label               = $ai4seo_is_attachment_attributes_context
	? esc_html__( 'media attribute', 'ai-for-seo' )
	: esc_html__( 'metadata', 'ai-for-seo' );
$ai4seo_custom_instructions_examples_context = $ai4seo_is_attachment_attributes_context
	? 'attachment-attributes-editor'
	: 'metadata-editor';
$ai4seo_custom_instructions_description      = sprintf(
	/* translators: 1: Plugin name, 2: Generation scope such as metadata or media attribute. */
	esc_html__( 'These entry-specific instructions are used only for future %1$s %2$s generations for the selected entries.', 'ai-for-seo' ),
	ai4seo_get_plugin_name(),
	$ai4seo_generation_scope_label
);

// === OUTPUT ================================================================================ \\

// Keep the headline consistent with AJAX editor modals while making this modal clearly bulk scoped.
ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Set custom instructions', 'ai-for-seo' ) ) );

echo "<div class='ai4seo-modal-content'>";
	echo "<div class='ai4seo-form ai4seo-bulk-custom-instructions-form'>";
		// Show the selected-entry scope before warnings so the overwrite risk is concrete.
		echo '<p>';
			echo esc_html(
				sprintf(
					/* translators: %s: Number of selected entries. */
					_n(
						'The custom instructions entered here will be saved for %s selected entry.',
						'The custom instructions entered here will be saved for %s selected entries.',
						$ai4seo_selected_entries_count,
						'ai-for-seo'
					),
					ai4seo_format_number_i18n( $ai4seo_selected_entries_count )
				)
			);
			echo '</p>';

			// Keep overwrite and empty-clear warnings visible without using WordPress notice styling inside the modal.
			echo "<div class='ai4seo-bulk-custom-instructions-warning'>";
			echo '<p>';
				echo '<strong>' . esc_html__( 'Warning:', 'ai-for-seo' ) . '</strong> ';
				echo esc_html__( 'Existing entry-specific custom instructions for the selected entries will be overwritten.', 'ai-for-seo' );
			echo '</p>';
			echo '<p>';
				echo esc_html__( 'Submitting an empty value clears existing entry-specific custom instructions for the selected entries.', 'ai-for-seo' );
			echo '</p>';
			echo '</div>';

			// Hidden fields keep the selected table state attached to the modal submit button that finally saves the data.
			echo "<input type='hidden' class='ai4seo-bulk-custom-instructions-action' value='" . esc_attr( $ai4seo_bulk_generation_queue_action ) . "'>";
			echo "<input type='hidden' class='ai4seo-bulk-custom-instructions-context' value='" . esc_attr( $ai4seo_context ) . "'>";

			foreach ( $ai4seo_post_ids as $ai4seo_this_post_id ) {
				echo "<input type='hidden' class='ai4seo-bulk-custom-instructions-post-id' value='" . esc_attr( $ai4seo_this_post_id ) . "'>";
			}

			// Reuse the shared form item so character limits and context-matched examples remain centralized.
			ai4seo_echo_wp_kses(
				ai4seo_get_custom_instructions_form_item_tag(
					$ai4seo_custom_instructions_input_name,
					$ai4seo_custom_instructions_input_name,
					'',
					esc_html__( 'Custom Instructions:', 'ai-for-seo' ),
					$ai4seo_custom_instructions_description,
					'ai4seo-editor-textarea ai4seo-bulk-custom-instructions-input',
					esc_html__( 'Custom Instructions', 'ai-for-seo' ),
					'ai4seo-bulk-custom-instructions-form-item',
					'',
					$ai4seo_custom_instructions_examples_context
				)
			);
			echo '</div>';
			echo '</div>';

			// Use standard modal footer buttons so keyboard shortcuts and lockable states keep working.
			// Build the action row with existing button helpers and let the shared footer helper provide only the wrapper.
			$ai4seo_modal_footer_button_tags = array(
				ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ),
				ai4seo_get_submit_button_tag( esc_html__( 'Submit', 'ai-for-seo' ), 'ai4seo-big-button ai4seo-lockable', 'ai4seo_apply_bulk_custom_instructions_action(jQuery(this));' ),
			);

			// Render the action row through the shared footer helper used by AJAX modals.
			ai4seo_echo_wp_kses( ai4seo_get_modal_footer_tag( $ai4seo_modal_footer_button_tags ) );
