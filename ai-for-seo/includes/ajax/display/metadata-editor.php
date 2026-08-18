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

// The plugin-level role gate does not replace WordPress's object-level post permission check.
if ( ! ai4seo_can_edit_post( $ai4seo_post_id ) ) {
	ai4seo_send_ajax_error( esc_html__( 'You are not allowed to edit this entry.', 'ai-for-seo' ), 2306230643 );
}

// Normalize the navigation list before using it for authorization or next-entry selection.
$ai4seo_all_post_ids = isset( $_REQUEST['all_post_ids'] ) && is_array( $_REQUEST['all_post_ids'] )
	? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_REQUEST['all_post_ids'] ) ) ) ) )
	: array();

// Keep only editable navigation targets so one unrelated entry cannot block the authorized editor.
$ai4seo_all_post_ids = ai4seo_filter_editable_post_ids( $ai4seo_all_post_ids );

// Reuse the ordered-list helper so metadata and media editors calculate their next-entry targets identically.
$ai4seo_next_post_id = ai4seo_get_next_post_id_from_ordered_post_ids( $ai4seo_post_id, $ai4seo_all_post_ids );


// === GET ADDITIONAL DETAILS ===================================================================== \\

// Read post- or page-title and post custom fields.
$ai4seo_this_post_title = get_the_title( $ai4seo_post_id );

// read all metadata values for this post.
$ai4seo_this_metadata_values = ai4seo_read_available_metadata_by_post_ids( array( $ai4seo_post_id ) );

if ( is_array( $ai4seo_this_metadata_values ) ) {
	$ai4seo_this_metadata_values = $ai4seo_this_metadata_values[ $ai4seo_post_id ] ?? array();
} else {
	$ai4seo_this_metadata_values = array();
}

// Start with no client snapshot so persisted metadata remains authoritative outside the current page editor.
$ai4seo_live_yoast_metadata_identifiers = array();
$ai4seo_live_yoast_metadata             = array();

// Unslash the nested snapshot as one unit; recognized fields are sanitized individually below.
if ( isset( $_REQUEST['live_yoast_metadata'] ) && is_array( $_REQUEST['live_yoast_metadata'] ) ) {
	$ai4seo_live_yoast_metadata = wp_unslash( $_REQUEST['live_yoast_metadata'] );
}

// Prefer Yoast's current editor values only for fields actively configured to synchronize with Yoast.
$ai4seo_yoast_sync_metadata_identifiers = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO );

foreach ( $ai4seo_yoast_sync_metadata_identifiers as $ai4seo_live_yoast_metadata_identifier ) {
	if ( ! array_key_exists( $ai4seo_live_yoast_metadata_identifier, $ai4seo_live_yoast_metadata )
		|| ! is_scalar( $ai4seo_live_yoast_metadata[ $ai4seo_live_yoast_metadata_identifier ] ) ) {
		continue;
	}

	// Use the same field sanitizer as persisted editor values before allowing the snapshot into rendered form controls.
	$ai4seo_live_yoast_metadata_value = ai4seo_sanitize_editor_field_value(
		$ai4seo_live_yoast_metadata[ $ai4seo_live_yoast_metadata_identifier ]
	);
	$ai4seo_this_metadata_values[ $ai4seo_live_yoast_metadata_identifier ] = ai4seo_normalize_editor_input_value( $ai4seo_live_yoast_metadata_value );
	$ai4seo_live_yoast_metadata_identifiers[] = $ai4seo_live_yoast_metadata_identifier;
}

// Source hints compare the active editor values with SOOZ and third-party snapshots before rendering labels.
$ai4seo_metadata_source_details = ai4seo_read_metadata_editor_source_details(
	$ai4seo_post_id,
	$ai4seo_this_metadata_values
);

// Distinguish live unsaved Yoast state from values read from Yoast postmeta.
foreach ( $ai4seo_live_yoast_metadata_identifiers as $ai4seo_live_yoast_metadata_identifier ) {
	$ai4seo_metadata_source_details[ $ai4seo_live_yoast_metadata_identifier ] = ai4seo_get_editor_field_third_party_source_details(
		'Yoast SEO',
		false,
		true
	);
}

// Prepare variables for prefixes and suffixes.
$ai4seo_metadata_prefixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
$ai4seo_metadata_suffixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );

// Prepare variables for active meta tags.
$ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

$ai4seo_settings_url                 = ai4seo_get_subpage_url( 'settings' );
$ai4seo_metadata_custom_instructions = ai4seo_read_custom_instructions_postmeta( $ai4seo_post_id, AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY );
$ai4seo_this_post                    = get_post( $ai4seo_post_id );
$ai4seo_this_post_type_object        = $ai4seo_this_post ? get_post_type_object( $ai4seo_this_post->post_type ) : null;
$ai4seo_this_post_status_object      = $ai4seo_this_post ? get_post_status_object( $ai4seo_this_post->post_status ) : null;
$ai4seo_this_post_type_label         = $ai4seo_this_post_type_object && isset( $ai4seo_this_post_type_object->labels->singular_name )
	? $ai4seo_this_post_type_object->labels->singular_name
	: __( 'Content', 'ai-for-seo' );
$ai4seo_this_post_status_label       = $ai4seo_this_post_status_object && isset( $ai4seo_this_post_status_object->label )
	? $ai4seo_this_post_status_object->label
	: __( 'Unknown status', 'ai-for-seo' );
$ai4seo_this_post_modified_time      = $ai4seo_this_post
	? get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $ai4seo_this_post, true )
	: '';


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Preserve the existing empty-state behavior before constructing the full editor workspace.
if ( ! $ai4seo_active_meta_tags ) {
	ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Metadata Editor', 'ai-for-seo' ) ) );
	ai4seo_echo_wp_kses(
		ai4seo_get_editor_no_active_fields_notice_tag(
			__( 'No meta tags are active. Please activate at least one meta tag in the plugin settings to manage metadata.', 'ai-for-seo' ),
			$ai4seo_settings_url
		)
	);
	return;
}

$ai4seo_editor_default_view_mode    = ai4seo_get_editor_default_view_mode();
$ai4seo_metadata_preview_context    = ai4seo_get_metadata_editor_preview_context(
	$ai4seo_post_id,
	$ai4seo_this_metadata_values,
	$ai4seo_active_meta_tags
);
$ai4seo_metadata_evaluation_details = array();

// Build the field footer contract once so Preview and Editor use identical labels, targets, sources, and IDs.
foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_metadata_identifier => $ai4seo_metadata_details ) {
	if ( ! in_array( $ai4seo_metadata_identifier, $ai4seo_active_meta_tags, true ) ) {
		continue;
	}

	$ai4seo_metadata_quality_window      = $ai4seo_metadata_preview_context['qualityWindows'][ $ai4seo_metadata_identifier ] ?? array();
	$ai4seo_metadata_minimum_length      = absint( $ai4seo_metadata_quality_window['min'] ?? 0 );
	$ai4seo_metadata_maximum_length      = absint( $ai4seo_metadata_quality_window['max'] ?? 0 );
	$ai4seo_metadata_has_quality_window  = 0 < $ai4seo_metadata_minimum_length && $ai4seo_metadata_minimum_length <= $ai4seo_metadata_maximum_length;
	$ai4seo_metadata_is_focus_keyphrase  = 'focus-keyphrase' === $ai4seo_metadata_identifier;
	$ai4seo_metadata_is_keywords         = 'keywords' === $ai4seo_metadata_identifier;
	$ai4seo_metadata_feedback_maximum    = $ai4seo_metadata_is_focus_keyphrase ? AI4SEO_FOCUS_KEYPHRASE_RECOMMENDED_MAX_LENGTH : $ai4seo_metadata_maximum_length;
	$ai4seo_metadata_input_name          = ai4seo_get_prefixed_input_name( 'metadata_' . $ai4seo_metadata_identifier );
	$ai4seo_metadata_length_feedback_id  = $ai4seo_metadata_input_name . '-length-feedback';
	$ai4seo_metadata_target_description  = '';
	$ai4seo_metadata_source_message_text = '';

	if ( $ai4seo_metadata_has_quality_window ) {
		$ai4seo_metadata_target_description = sprintf(
			/* translators: 1: Minimum character target, 2: Maximum character target. */
			__( 'Target: %1$d–%2$d characters', 'ai-for-seo' ),
			$ai4seo_metadata_minimum_length,
			$ai4seo_metadata_maximum_length
		);
	} elseif ( $ai4seo_metadata_is_keywords ) {
		$ai4seo_metadata_target_description = sprintf(
			/* translators: 1: Minimum keyword count, 2: Maximum keyword count. */
			__( 'Recommended: %1$d–%2$d keywords', 'ai-for-seo' ),
			AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MIN_ITEMS,
			AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MAX_ITEMS
		);
	}

	if ( isset( $ai4seo_metadata_source_details[ $ai4seo_metadata_identifier ] ) ) {
		$ai4seo_metadata_source_message_details = ai4seo_get_editor_field_source_message_details( $ai4seo_metadata_source_details[ $ai4seo_metadata_identifier ] );
		$ai4seo_metadata_source_message_text    = $ai4seo_metadata_source_message_details['message'] ?? '';
	}

	$ai4seo_metadata_evaluation_details[ $ai4seo_metadata_identifier ] = array(
		'count_label'        => $ai4seo_metadata_is_focus_keyphrase || $ai4seo_metadata_is_keywords ? '' : wp_strip_all_tags( $ai4seo_metadata_details['name'] ?? '' ),
		'evaluation_id'      => $ai4seo_metadata_length_feedback_id,
		'feedback_maximum'   => $ai4seo_metadata_feedback_maximum,
		'has_feedback'       => $ai4seo_metadata_has_quality_window || $ai4seo_metadata_is_focus_keyphrase || $ai4seo_metadata_is_keywords,
		'input_attributes'   => ai4seo_get_editor_length_input_attributes(
			$ai4seo_metadata_length_feedback_id,
			$ai4seo_metadata_minimum_length,
			$ai4seo_metadata_feedback_maximum
		),
		'maximum_length'     => $ai4seo_metadata_maximum_length,
		'minimum_length'     => $ai4seo_metadata_minimum_length,
		'source_message'     => $ai4seo_metadata_source_message_text,
		'target_description' => $ai4seo_metadata_target_description,
	);
}

// The AJAX modal parser places this shared headline in the modal chrome above the workspace.
ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Metadata Editor', 'ai-for-seo' ) ) );

// The existing form remains the single source of save and generation values in both display modes.
// phpcs:disable Generic.WhiteSpace.ScopeIndent -- Visual echo indentation mirrors the generated workspace hierarchy.
echo '<div'
	. " class='ai4seo-form ai4seo-editor-form ai4seo-editor-workspace ai4seo-metadata-editor-workspace'"
	. " data-ai4seo-editor-context='metadata'"
	. " data-ai4seo-default-view-mode='" . esc_attr( $ai4seo_editor_default_view_mode ) . "'"
	. " data-ai4seo-preview-context='" . esc_attr( wp_json_encode( $ai4seo_metadata_preview_context ) ) . "'"
	. '>';

	echo "<div class='ai4seo-editor-workspace-header'>";
		echo "<div class='ai4seo-editor-workspace-identity'>";
			echo "<h2 class='ai4seo-editor-workspace-title'>" . esc_html( $ai4seo_this_post_title ) . '</h2>';
			echo "<p class='ai4seo-editor-workspace-subtitle'>";
				ai4seo_echo_wp_kses(
					sprintf(
						/* translators: 1: Post type label. 2: Post ID. 3: Post status. 4: Last-modified date and time. */
						__( '%1$s · ID %2$d · %3$s · Updated %4$s', 'ai-for-seo' ),
						$ai4seo_this_post_type_label,
						$ai4seo_post_id,
						$ai4seo_this_post_status_label,
						$ai4seo_this_post_modified_time
					)
				);
			echo '</p>';
		echo '</div>';

		echo "<div class='ai4seo-editor-workspace-header-actions'>";
			// Related media uses a dedicated AJAX modal id so it can stack above the metadata editor.
			ai4seo_echo_wp_kses( ai4seo_get_related_attachments_button( $ai4seo_post_id, esc_html__( 'Related Media', 'ai-for-seo' ) ) );
			ai4seo_echo_wp_kses( ai4seo_get_editor_mode_switch_tag( $ai4seo_editor_default_view_mode ) );
		echo '</div>';
	echo '</div>';

	$ai4seo_skipped_meta_tags = array();

	echo "<div class='ai4seo-editor-workspace-scroll'>";
		echo "<div class='ai4seo-editor-shared-context-grid ai4seo-metadata-editor-shared-context-grid'>";
		// Place the shared generation instructions before the focus keyphrase in both modes.

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
				'ai4seo-editor-custom-instructions-form-item ai4seo-metadata-custom-instructions-form-item',
				'',
				'metadata-editor',
				'',
				true
			)
		);

		if ( ai4seo_has_active_editor_fields( array( 'focus-keyphrase' ), $ai4seo_active_meta_tags ) ) {
			$ai4seo_focus_keyphrase_edit_actions = ai4seo_get_editor_preview_edit_actions(
				array( 'focus-keyphrase' ),
				$ai4seo_active_meta_tags,
				AI4SEO_METADATA_DETAILS
			);
			if ( isset( $ai4seo_focus_keyphrase_edit_actions['focus-keyphrase'] ) ) {
				$ai4seo_focus_keyphrase_edit_actions['focus-keyphrase'] = __( 'Edit Keyphrase', 'ai-for-seo' );
			}
			$ai4seo_focus_keyphrase_details        = $ai4seo_metadata_evaluation_details['focus-keyphrase'] ?? array();
			$ai4seo_focus_keyphrase_preview_hidden = 'editor' === $ai4seo_editor_default_view_mode ? ' hidden' : '';
			echo "<section class='ai4seo-editor-context-card ai4seo-editor-focus-keyphrase-preview-card ai4seo-metadata-focus-keyphrase-context ai4seo-editor-preview-only'" . esc_attr( $ai4seo_focus_keyphrase_preview_hidden ) . '>';
			ai4seo_echo_wp_kses(
				ai4seo_get_editor_preview_card_heading_tag(
					__( 'Focus Keyphrase', 'ai-for-seo' ),
					'',
					'',
					__( 'The focus keyphrase describes the primary search intent for this entry. SOOZ can use it to align generated titles, descriptions, social metadata, and keywords according to your Focus Keyphrase Influence setting. Keep it concise; more than 30 characters can make the topic unnecessarily broad.', 'ai-for-seo' ),
					__( 'Focus Keyphrase: Help', 'ai-for-seo' ),
					$ai4seo_focus_keyphrase_edit_actions
				)
			);
			echo "<div class='ai4seo-editor-focus-keyphrase-context-content'>";
				echo "<p class='ai4seo-editor-focus-keyphrase-value'></p>";
			echo '</div>';

		if ( $ai4seo_focus_keyphrase_details ) {
			ai4seo_echo_wp_kses(
				ai4seo_get_editor_preview_evaluation_tag(
					'focus-keyphrase',
					'',
					'',
					$ai4seo_focus_keyphrase_details['source_message'],
					'focus-keyphrase',
					'',
					$ai4seo_focus_keyphrase_details['count_label']
				)
			);
		}
			echo '</section>';

			if ( isset( AI4SEO_METADATA_DETAILS['focus-keyphrase'], $ai4seo_metadata_evaluation_details['focus-keyphrase'] ) ) {
				ai4seo_echo_wp_kses(
					ai4seo_get_metadata_editor_field_tag(
						'focus-keyphrase',
						AI4SEO_METADATA_DETAILS['focus-keyphrase'],
						sanitize_text_field( $ai4seo_this_metadata_values['focus-keyphrase'] ?? '' ),
						sanitize_text_field( $ai4seo_metadata_prefixes['focus-keyphrase'] ?? '' ),
						sanitize_text_field( $ai4seo_metadata_suffixes['focus-keyphrase'] ?? '' ),
						$ai4seo_metadata_evaluation_details['focus-keyphrase'],
						'ai4seo-editor-editor-only ai4seo-metadata-focus-keyphrase-context',
						'editor' !== $ai4seo_editor_default_view_mode
					)
				);
			}
		}
		echo '</div>';

		// Preview cards approximate external appearances while using the same per-field footer as Editor mode.
		echo "<div class='ai4seo-editor-preview-panel ai4seo-editor-mode-panel' data-ai4seo-editor-mode-panel='preview'>";
			echo "<div class='ai4seo-editor-preview-grid ai4seo-metadata-preview-grid'>";
				$ai4seo_google_preview_fields = array( 'meta-title', 'meta-description' );
			if ( ai4seo_has_active_editor_fields( $ai4seo_google_preview_fields, $ai4seo_active_meta_tags ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-google-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Google Search', 'ai-for-seo' ),
							'',
							'',
							__( 'An approximate search result using your effective meta title, description, URL, and configured fallbacks. Google may rewrite or truncate the final result.', 'ai-for-seo' ),
							__( 'Google Search preview: Help', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_actions( $ai4seo_google_preview_fields, $ai4seo_active_meta_tags, AI4SEO_METADATA_DETAILS )
						)
					);
					echo "<div class='ai4seo-google-preview'>";
						echo "<div class='ai4seo-google-preview-site-row'><span class='ai4seo-google-preview-site-icon'></span><span><span class='ai4seo-google-preview-site-name'></span><span class='ai4seo-google-preview-url'></span></span></div>";
						echo "<div class='ai4seo-google-preview-title ai4seo-preview-measured-text' data-ai4seo-preview-field='meta-title'></div>";
						echo "<div class='ai4seo-google-preview-description ai4seo-preview-measured-text' data-ai4seo-preview-field='meta-description'></div>";
					echo '</div>';
					ai4seo_echo_wp_kses( ai4seo_get_editor_preview_evaluations_tag( $ai4seo_google_preview_fields, $ai4seo_metadata_evaluation_details ) );
				echo '</article>';
			}

				$ai4seo_keywords_preview_fields = array( 'keywords' );
			if ( ai4seo_has_active_editor_fields( $ai4seo_keywords_preview_fields, $ai4seo_active_meta_tags ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-keywords-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Keywords', 'ai-for-seo' ),
							'',
							'',
							__( 'A code representation of the stored meta keywords tag. Five to ten distinct, comma-separated keywords provide a useful amount of context without creating an unfocused list.', 'ai-for-seo' ),
							__( 'Keywords preview: Help', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_actions( $ai4seo_keywords_preview_fields, $ai4seo_active_meta_tags, AI4SEO_METADATA_DETAILS )
						)
					);
					echo "<pre class='ai4seo-keywords-code-preview' aria-label='" . esc_attr__( 'Meta keywords HTML representation', 'ai-for-seo' ) . "'><code><span class='ai4seo-code-punctuation'>&lt;</span><span class='ai4seo-code-tag'>meta</span> <span class='ai4seo-code-attribute'>name</span><span class='ai4seo-code-punctuation'>=</span><span class='ai4seo-code-string'>&quot;keywords&quot;</span> <span class='ai4seo-code-attribute'>content</span><span class='ai4seo-code-punctuation'>=</span><span class='ai4seo-code-string'>&quot;</span><span class='ai4seo-keywords-code-value'></span><span class='ai4seo-code-string'>&quot;</span><span class='ai4seo-code-punctuation'>&gt;</span></code></pre>";
					ai4seo_echo_wp_kses( ai4seo_get_editor_preview_evaluations_tag( $ai4seo_keywords_preview_fields, $ai4seo_metadata_evaluation_details ) );
				echo '</article>';
			}

				foreach (
					array(
						'facebook' => array(
							'label'       => __( 'Facebook', 'ai-for-seo' ),
							'title'       => 'facebook-title',
							'description' => 'facebook-description',
							'tooltip'     => __( 'An approximate Open Graph link card using the configured Facebook title, description, and WordPress featured image. Facebook controls the final layout and may alter or omit supplied content.', 'ai-for-seo' ),
						),
						'twitter'  => array(
							'label'       => __( 'X', 'ai-for-seo' ),
							'title'       => 'twitter-title',
							'description' => 'twitter-description',
							'tooltip'     => __( 'An approximate X link card. Card type and image behavior are controlled elsewhere, and X determines the final rendering.', 'ai-for-seo' ),
						),
					) as $ai4seo_social_platform => $ai4seo_social_preview
				) {
					$ai4seo_social_preview_fields = array( $ai4seo_social_preview['title'], $ai4seo_social_preview['description'] );

					if ( ! ai4seo_has_active_editor_fields( $ai4seo_social_preview_fields, $ai4seo_active_meta_tags ) ) {
						continue;
					}

					echo "<article class='ai4seo-editor-preview-card ai4seo-social-preview-card ai4seo-" . esc_attr( $ai4seo_social_platform ) . "-preview-card'>";
						ai4seo_echo_wp_kses(
							ai4seo_get_editor_preview_card_heading_tag(
								$ai4seo_social_preview['label'],
								'',
								'',
								$ai4seo_social_preview['tooltip'],
								$ai4seo_social_preview['label'] . ': ' . __( 'Help', 'ai-for-seo' ),
								ai4seo_get_editor_preview_edit_actions( $ai4seo_social_preview_fields, $ai4seo_active_meta_tags, AI4SEO_METADATA_DETAILS )
							)
						);
						echo "<div class='ai4seo-social-preview'>";
							echo "<div class='ai4seo-social-preview-image ai4seo-social-preview-image-placeholder'></div>";
							echo "<div class='ai4seo-social-preview-image-label'>" . esc_html__( 'WordPress featured image — not managed by SOOZ', 'ai-for-seo' ) . '</div>';
							echo "<div class='ai4seo-social-preview-copy'>";
								echo "<span class='ai4seo-social-preview-domain'></span>";
								echo "<div class='ai4seo-social-preview-title ai4seo-preview-measured-text' data-ai4seo-preview-field='" . esc_attr( $ai4seo_social_preview['title'] ) . "'></div>";
								echo "<div class='ai4seo-social-preview-description ai4seo-preview-measured-text' data-ai4seo-preview-field='" . esc_attr( $ai4seo_social_preview['description'] ) . "'></div>";
							echo '</div>';
						echo '</div>';
						ai4seo_echo_wp_kses( ai4seo_get_editor_preview_evaluations_tag( $ai4seo_social_preview_fields, $ai4seo_metadata_evaluation_details ) );
					echo '</article>';

					if ( 'facebook' === $ai4seo_social_platform ) {
						echo "<article class='ai4seo-editor-preview-card ai4seo-whatsapp-preview-card'>";
							ai4seo_echo_wp_kses(
								ai4seo_get_editor_preview_card_heading_tag(
									__( 'WhatsApp', 'ai-for-seo' ),
									'',
									'',
									__( 'An approximate chat-style link preview using the effective Open Graph title, description, and WordPress featured image. WhatsApp determines the final content and layout.', 'ai-for-seo' ),
									__( 'WhatsApp preview: Help', 'ai-for-seo' ),
									ai4seo_get_editor_preview_edit_actions( $ai4seo_social_preview_fields, $ai4seo_active_meta_tags, AI4SEO_METADATA_DETAILS )
								)
							);
							echo "<div class='ai4seo-whatsapp-preview-shell'>";
								echo "<div class='ai4seo-whatsapp-preview-image ai4seo-social-preview-image-placeholder'></div>";
								echo "<div class='ai4seo-social-preview-image-label'>" . esc_html__( 'WordPress featured image — not managed by SOOZ', 'ai-for-seo' ) . '</div>';
								echo "<div class='ai4seo-whatsapp-preview-copy'><div class='ai4seo-whatsapp-preview-title ai4seo-preview-measured-text' data-ai4seo-preview-field='facebook-title'></div><div class='ai4seo-whatsapp-preview-description ai4seo-preview-measured-text' data-ai4seo-preview-field='facebook-description'></div><span class='ai4seo-whatsapp-preview-domain'></span></div>";
							echo '</div>';
							ai4seo_echo_wp_kses( ai4seo_get_editor_preview_evaluations_tag( $ai4seo_social_preview_fields, $ai4seo_metadata_evaluation_details ) );
						echo '</article>';
					}
				}
			echo '</div>';
		echo '</div>';

		echo "<div class='ai4seo-editor-fields-panel ai4seo-editor-mode-panel' data-ai4seo-editor-mode-panel='editor'>";
			echo "<div class='ai4seo-editor-fields-grid'>";


		// Render the remaining editable controls after the preview cards so both modes share one form.

		foreach ( AI4SEO_METADATA_DETAILS as $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details ) {
			// Make sure that required value-entries exist.
			if ( ! isset( $ai4seo_this_metadata_details['name'] ) || ! isset( $ai4seo_this_metadata_details['input'] ) || ! isset( $ai4seo_this_metadata_details['hint'] ) ) {
				continue;
			}

			if ( ! in_array( $ai4seo_this_metadata_identifier, $ai4seo_active_meta_tags, true ) ) {
				$ai4seo_skipped_meta_tags[] = $ai4seo_this_metadata_identifier;
				continue;
			}

			// The real Focus Keyphrase control lives beside Custom Instructions in Editor mode.
			if ( 'focus-keyphrase' === $ai4seo_this_metadata_identifier ) {
				continue;
			}

			ai4seo_echo_wp_kses(
				ai4seo_get_metadata_editor_field_tag(
					$ai4seo_this_metadata_identifier,
					$ai4seo_this_metadata_details,
					sanitize_text_field( $ai4seo_this_metadata_values[ $ai4seo_this_metadata_identifier ] ?? '' ),
					sanitize_text_field( $ai4seo_metadata_prefixes[ $ai4seo_this_metadata_identifier ] ?? '' ),
					sanitize_text_field( $ai4seo_metadata_suffixes[ $ai4seo_this_metadata_identifier ] ?? '' ),
					$ai4seo_metadata_evaluation_details[ $ai4seo_this_metadata_identifier ]
				)
			);
		}
			echo '</div>';
		echo '</div>';

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
	echo '</div>';


		// === BUTTONS ROW ================================================================================= \\

		// Collect footer buttons before rendering the shared wrapper so the optional next-entry action keeps its current order.
		$ai4seo_modal_footer_button_tags = array(
			ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ),
			"<div id='ai4seo-generate-all-metadata-button-hook' class='ai4seo-editor-generate-all-hook'></div>",
		);

		// Let the shared editor helper add the next-entry action only when this list has another post to edit.
		$ai4seo_modal_footer_button_tags[] = ai4seo_get_editor_save_next_button_tag(
			$ai4seo_next_post_id,
			'ai4seo_validate_metadata_editor_inputs',
			'ai4seo_open_metadata_editor_modal',
			array(
				'true' === $ai4seo_read_page_content_via_js,
				$ai4seo_all_post_ids,
			),
			'ai4seo_handle_metadata_editor_save_success'
		);

		// Keep the normal save action last, matching the previous explicit footer markup.
		$ai4seo_modal_footer_button_tags[] = ai4seo_get_submit_button_tag(
			esc_html__( 'Save changes', 'ai-for-seo' ),
			'ai4seo-big-button ai4seo-lockable ai4seo-start-inactive',
			'ai4seo_save_anything(jQuery(this), ai4seo_validate_metadata_editor_inputs, ai4seo_handle_metadata_editor_save_success);'
		);

		// Render the action row through the shared footer helper used by AJAX modals.
		ai4seo_echo_wp_kses( ai4seo_get_modal_footer_tag( $ai4seo_modal_footer_button_tags, 'ai4seo-editor-workspace-footer' ) );

		echo '</div>';
// phpcs:enable Generic.WhiteSpace.ScopeIndent
