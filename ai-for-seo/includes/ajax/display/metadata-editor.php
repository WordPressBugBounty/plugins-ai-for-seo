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
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 2306230636 );
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// === CHECK PARAMETER ============================================== \\

// Make sure that input-fields exist.
if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
	ai4seo_send_ajax_error( esc_html__( 'An error occurred! Please check your settings or contact the plugin developer.', 'ai-for-seo' ), 2306230642 );
}

$ai4seo_read_page_content_via_js = isset( $_REQUEST['read_page_content_via_js'] ) && 'true' === $_REQUEST['read_page_content_via_js'] ? 'true' : 'false';

// Get sanitized post id parameter.
$ai4seo_post_id = absint( $_REQUEST['post_id'] ?? 0 );

// validate post id.
if ( $ai4seo_post_id <= 0 ) {
	ai4seo_send_ajax_error( esc_html__( 'Post id is invalid.', 'ai-for-seo' ), 2306230638 );
}

// get sanitized all_post_ids parameter.
$ai4seo_all_post_ids = isset( $_REQUEST['all_post_ids'] ) && is_array( $_REQUEST['all_post_ids'] ) ? array_map( 'absint', $_REQUEST['all_post_ids'] ) : array();

// Reuse the ordered-list helper so metadata and media editors calculate their next-entry targets identically.
$ai4seo_next_post_id = ai4seo_get_next_post_id_from_ordered_post_ids( $ai4seo_post_id, $ai4seo_all_post_ids );


// === GET ADDITIONAL DETAILS ===================================================================== \\

// Read post- or page-title and post custom fields.
$ai4seo_this_post_title = get_the_title( $ai4seo_post_id );

// read all metadata values for this post.
$ai4seo_this_metadata_values = ai4seo_read_available_metadata_by_post_ids( array( $ai4seo_post_id ) );

if ( $ai4seo_this_metadata_values ) {
	$ai4seo_this_metadata_values = $ai4seo_this_metadata_values[ $ai4seo_post_id ] ?? array();
}

// Source hints compare the active editor values with SOOZ and third-party snapshots before rendering labels.
$ai4seo_metadata_source_details = ai4seo_read_metadata_editor_source_details(
	$ai4seo_post_id,
	$ai4seo_this_metadata_values
);

// Prepare variables for prefixes and suffixes.
$ai4seo_metadata_prefixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
$ai4seo_metadata_suffixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );

// Prepare variables for active meta tags.
$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

$ai4seo_settings_url                 = ai4seo_get_subpage_url( 'settings' );
$ai4seo_metadata_custom_instructions = ai4seo_read_custom_instructions_postmeta( $ai4seo_post_id, AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Use the shared AJAX modal headline shell so editor modals keep the same logo/title structure.
ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Metadata Editor', 'ai-for-seo' ) ) );

echo "<div class='ai4seo-metadata-editor-entry-context'>";
	echo "<div class='ai4seo-modal-sub-headline'>";
		ai4seo_echo_wp_kses(
			sprintf(
				/* translators: 1: Post title. 2: Post ID. */
				__( 'Manage metadata for <b>%1$s</b> (#%2$d)', 'ai-for-seo' ),
				$ai4seo_this_post_title,
				$ai4seo_post_id
			)
		);
		echo '</div>';

		// Related media uses a dedicated AJAX modal id so it can stack above the metadata editor.
		echo "<div class='ai4seo-metadata-editor-header-actions'>";
		ai4seo_echo_wp_kses( ai4seo_get_related_attachments_button( $ai4seo_post_id, esc_html__( 'Related Media', 'ai-for-seo' ) ) );
		echo '</div>';
		echo '</div>';

		if ( ! $ai4seo_active_meta_tags ) {
			ai4seo_echo_wp_kses(
				ai4seo_get_editor_no_active_fields_notice_tag(
					__( 'No meta tags are active. Please activate at least one meta tag in the plugin settings to manage metadata.', 'ai-for-seo' ),
					$ai4seo_settings_url
				)
			);
			return;
		}

		// GENERATE ALL BUTTON.
		echo "<div id='ai4seo-generate-all-metadata-button-hook'></div>";

		// Form.
		echo "<div class='ai4seo-form ai4seo-editor-form'>";

		// === CUSTOM INSTRUCTIONS ================================================================================= \\

		$ai4seo_metadata_custom_instructions_input_name  = ai4seo_get_prefixed_input_name( 'metadata_editor_custom_instructions' );
		$ai4seo_metadata_custom_instructions_description = esc_html__( 'These instructions are used only for future metadata generations for this entry.', 'ai-for-seo' );

		// Reuse the shared custom-instruction form item so editor-specific fields match settings fields.
		ai4seo_echo_wp_kses(
			ai4seo_get_custom_instructions_form_item_tag(
				$ai4seo_metadata_custom_instructions_input_name,
				$ai4seo_metadata_custom_instructions_input_name,
				$ai4seo_metadata_custom_instructions,
				esc_html__( 'Custom Instructions:', 'ai-for-seo' ),
				$ai4seo_metadata_custom_instructions_description,
				'ai4seo-editor-textarea ai4seo-entry-custom-instructions-input',
				esc_html__( 'Metadata Custom Instructions', 'ai-for-seo' ),
				'ai4seo-editor-custom-instructions-form-item',
				'',
				'metadata-editor'
			)
		);


		// === GO THROUGH EACH FIELD ================================================================================= \\

		$ai4seo_skipped_meta_tags = array();

		// Reuse the translated suffix while field names keep each help trigger distinct for assistive technology.
		$ai4seo_help_aria_label = __( 'Help', 'ai-for-seo' );

		foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details ) {
			// Make sure that required value-entries exist.
			if ( ! isset( $ai4seo_this_metadata_details['name'] ) || ! isset( $ai4seo_this_metadata_details['input'] ) || ! isset( $ai4seo_this_metadata_details['hint'] ) ) {
				continue;
			}

			if ( ! in_array( $ai4seo_this_metadata_identifier, $ai4seo_active_meta_tags ) ) {
				$ai4seo_skipped_meta_tags[] = $ai4seo_this_metadata_identifier;
				continue;
			}

			// get the value of the post meta entry for the input-field.
			$ai4seo_this_metadata_input_value = sanitize_text_field( $ai4seo_this_metadata_values[ $ai4seo_this_metadata_identifier ] ?? '' );
			$ai4seo_this_metadata_input_name  = sanitize_text_field( ai4seo_get_prefixed_input_name( 'metadata_' . $ai4seo_this_metadata_identifier ) );
			$ai4seo_this_metadata_prefix      = sanitize_text_field( $ai4seo_metadata_prefixes[ $ai4seo_this_metadata_identifier ] ?? '' );
			$ai4seo_this_metadata_suffix      = sanitize_text_field( $ai4seo_metadata_suffixes[ $ai4seo_this_metadata_identifier ] ?? '' );

			// Keep each metadata field in the shared editor form-item structure used by attachment fields.
			echo "<div class='ai4seo-form-item ai4seo-form-item-flush'>";

			// Separate the tooltip button from the native field label while preserving their visual grouping.
			echo "<span class='ai4seo-form-item-label'>";
				echo "<span class='ai4seo-label-with-tooltip'>";
					echo "<label for='" . esc_attr( $ai4seo_this_metadata_input_name ) . "'>";
			if ( isset( $ai4seo_this_metadata_details['icon'] ) ) {
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( $ai4seo_this_metadata_details['icon'], '', 'ai4seo-24x24-icon ai4seo-gray-icon' ) );
				echo ' ';
			}

						echo esc_html( $ai4seo_this_metadata_details['name'] );
					echo '</label>';

					// Keep the button out of the field label to avoid nested interactive controls.
					ai4seo_echo_wp_kses(
						ai4seo_get_icon_with_tooltip_tag(
							$ai4seo_this_metadata_details['hint'],
							'',
							'circle-question',
							$ai4seo_this_metadata_details['name'] . ': ' . $ai4seo_help_aria_label
						)
					);
				echo '</span>';

				// Render the source hint directly under the label so it stays visually tied to this input.
			if ( isset( $ai4seo_metadata_source_details[ $ai4seo_this_metadata_identifier ] ) ) {
				ai4seo_echo_wp_kses(
					ai4seo_get_editor_field_source_message_tag( $ai4seo_metadata_source_details[ $ai4seo_this_metadata_identifier ] )
				);
			}

			// Keep the focus-keyphrase warning in the DOM so the editor can refresh it after AI generation and manual edits.
			if ( 'focus-keyphrase' === $ai4seo_this_metadata_identifier ) {
				// Match the initial state to the same condition used by the client-side refresh handler.
				$ai4seo_should_show_focus_keyphrase_warning = ! $ai4seo_this_metadata_input_value
					&& ( ( isset( $ai4seo_this_metadata_values['meta-title'] ) && $ai4seo_this_metadata_values['meta-title'] )
					|| ( isset( $ai4seo_this_metadata_values['meta-description'] ) && $ai4seo_this_metadata_values['meta-description'] ) );
				$ai4seo_focus_keyphrase_warning_classes = 'ai4seo-editor-field-warning-message ai4seo-sub-info ai4seo-red-message';

				// Preserve the warning element for later updates while hiding it when no action is currently needed.
				if ( ! $ai4seo_should_show_focus_keyphrase_warning ) {
					$ai4seo_focus_keyphrase_warning_classes .= ' ai4seo-display-none';
				}

				// Escape the assembled state classes while retaining the existing translated warning markup.
				echo "<span class='" . esc_attr( $ai4seo_focus_keyphrase_warning_classes ) . "'>";
					ai4seo_echo_wp_kses( __( '<strong>Heads up:</strong> This entry currently has no focus keyphrase. We recommend using the <strong>Generate & Overwrite</strong> button to ensure the keyphrase is applied and reflected across all related metadata fields.', 'ai-for-seo' ) );
				echo '</span>';
			}

			echo '</span>';

			// Render the registry-defined control inside the shared generate-button wrapper.
			echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";
				// Prefix.
			if ( $ai4seo_this_metadata_prefix ) {
				echo "<span class='ai4seo-editor-prefix ai4seo-gray-text'>";
					echo esc_html__( 'Prefix', 'ai-for-seo' ) . ': ' . esc_html( $ai4seo_this_metadata_prefix ) . ' ';

					// Give repeated prefix guidance a field-specific accessible trigger name.
					ai4seo_echo_wp_kses(
						ai4seo_get_icon_with_tooltip_tag(
							__( 'Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.', 'ai-for-seo' ),
							'',
							'circle-question',
							$ai4seo_this_metadata_details['name'] . ': ' . __( 'Prefix', 'ai-for-seo' ) . ': ' . $ai4seo_help_aria_label
						)
					);
				echo '</span><br>';
			}

				// Text field.
			if ( 'textfield' === $ai4seo_this_metadata_details['input'] ) {
				echo '<input type="text" class="ai4seo-textfield ai4seo-editor-textfield" name="' . esc_attr( $ai4seo_this_metadata_input_name ) . '" id="' . esc_attr( $ai4seo_this_metadata_input_name ) . '" value="' . esc_attr( $ai4seo_this_metadata_input_value ) . '" />';
			} elseif ( 'textarea' === $ai4seo_this_metadata_details['input'] ) {
				// Preserve multiline editing for metadata fields declared as textareas in the shared registry.
				echo '<textarea class="ai4seo-textarea ai4seo-editor-textarea ai4seo-auto-resize-textarea" name="' . esc_attr( $ai4seo_this_metadata_input_name ) . '" id="' . esc_attr( $ai4seo_this_metadata_input_name ) . '">' . esc_textarea( $ai4seo_this_metadata_input_value ) . '</textarea>';
			}

				// Suffix.
			if ( $ai4seo_this_metadata_suffix ) {
				echo "<br><span class='ai4seo-editor-suffix ai4seo-gray-text'>";
					echo esc_html__( 'Suffix', 'ai-for-seo' ) . ': ' . esc_html( $ai4seo_this_metadata_suffix ) . ' ';

					// Give repeated suffix guidance a field-specific accessible trigger name.
					ai4seo_echo_wp_kses(
						ai4seo_get_icon_with_tooltip_tag(
							__( 'Prefix and suffix are added automatically when the page is rendered. Please do not include them in this input field.', 'ai-for-seo' ),
							'',
							'circle-question',
							$ai4seo_this_metadata_details['name'] . ': ' . __( 'Suffix', 'ai-for-seo' ) . ': ' . $ai4seo_help_aria_label
						)
					);
				echo '</span><br>';
			}
			echo '</div>';

			echo '</div>';
		}

		// friendly reminder: $ai4seo_skipped_meta_tags.
		if ( $ai4seo_skipped_meta_tags ) {
			ai4seo_echo_wp_kses(
				ai4seo_get_editor_inactive_fields_notice_tag(
					$ai4seo_skipped_meta_tags,
					AI4SEO_METADATA_DETAILS,
					/* translators: 1: Comma-separated list of meta tags. 2: URL to plugin settings. */
					__( '<strong>Note:</strong> The following meta tags are currently inactive and not shown in this editor: %1$s. You can activate them in the <a href="%2$s" target="_blank">plugin settings</a>.', 'ai-for-seo' ),
					$ai4seo_settings_url
				)
			);
		}

		// put the post id into a hidden field, so we have access to it after the form is submitted.
		echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr( ai4seo_get_prefixed_input_name( 'metadata_editor_post_id' ) ) . "' value='" . esc_attr( $ai4seo_post_id ) . "' />";
		echo "<input type='hidden' id='ai4seo-read-page-content-via-js' value='" . esc_attr( $ai4seo_read_page_content_via_js ) . "' />";


		// === BUTTONS ROW ================================================================================= \\

		// Collect footer buttons before rendering the shared wrapper so the optional next-entry action keeps its current order.
		$ai4seo_modal_footer_button_tags = array(
			ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ),
		);

		// Let the shared editor helper add the next-entry action only when this list has another post to edit.
		$ai4seo_modal_footer_button_tags[] = ai4seo_get_editor_save_next_button_tag(
			$ai4seo_next_post_id,
			'ai4seo_validate_metadata_editor_inputs',
			'ai4seo_open_metadata_editor_modal',
			array(
				'true' === $ai4seo_read_page_content_via_js,
				$ai4seo_all_post_ids,
			)
		);

		// Keep the normal save action last, matching the previous explicit footer markup.
		$ai4seo_modal_footer_button_tags[] = ai4seo_get_submit_button_tag( esc_html__( 'Save changes', 'ai-for-seo' ), 'ai4seo-big-button ai4seo-lockable ai4seo-start-inactive', 'ai4seo_save_anything(jQuery(this), ai4seo_validate_metadata_editor_inputs, function() { ai4seo_safe_page_load(); });' );

		// Render the action row through the shared footer helper used by AJAX modals.
		ai4seo_echo_wp_kses( ai4seo_get_modal_footer_tag( $ai4seo_modal_footer_button_tags ) );

		echo '</div>';
