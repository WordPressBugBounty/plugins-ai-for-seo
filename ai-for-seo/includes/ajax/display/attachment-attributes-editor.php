<?php
/**
 * Displays the Media Attributes editor. Called via AJAX.
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

$ai4seo_attachment_read_succeeded       = false;
$ai4seo_attachment_post_exists          = false;
$ai4seo_this_post_attachment_attributes = ai4seo_read_available_attachment_attributes(
	$ai4seo_this_attachment_post_id,
	$ai4seo_attachment_read_succeeded,
	$ai4seo_attachment_post_exists
);

if ( ! $ai4seo_attachment_read_succeeded ) {
	ai4seo_send_ajax_error( esc_html__( 'Media attributes could not be loaded. Please refresh the page and try again.', 'ai-for-seo' ), 2208262602 );
	return;
}

if ( ! $ai4seo_attachment_post_exists ) {
	ai4seo_send_ajax_error( esc_html__( 'Attachment Post not found.', 'ai-for-seo' ), 57177525 );
	return;
}

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

// Reuse the attachment placeholder facts so the modal identity shows the same file data used by generation.
$ai4seo_attachment_placeholder_replacements = ai4seo_get_attachment_placeholder_replacements( $ai4seo_this_attachment_post_id );
$ai4seo_attachment_identity_details         = array();
$ai4seo_attachment_identity_details[]       = sprintf(
	/* translators: %d: Attachment ID. */
	__( 'Media item #%d', 'ai-for-seo' ),
	$ai4seo_this_attachment_post_id
);

$ai4seo_attachment_dimensions = explode( 'x', (string) ( $ai4seo_attachment_placeholder_replacements['IMAGE_DIMENSIONS'] ?? '' ), 2 );
if ( 2 === count( $ai4seo_attachment_dimensions ) ) {
	$ai4seo_attachment_width  = absint( $ai4seo_attachment_dimensions[0] );
	$ai4seo_attachment_height = absint( $ai4seo_attachment_dimensions[1] );

	if ( 0 < $ai4seo_attachment_width && 0 < $ai4seo_attachment_height ) {
		$ai4seo_attachment_identity_details[] = sprintf(
			/* translators: 1: Image width in pixels. 2: Image height in pixels. */
			__( '%1$s × %2$s px', 'ai-for-seo' ),
			ai4seo_format_number_i18n( $ai4seo_attachment_width ),
			ai4seo_format_number_i18n( $ai4seo_attachment_height )
		);
	}
}

$ai4seo_attachment_file_size = trim( (string) ( $ai4seo_attachment_placeholder_replacements['FILE_SIZE'] ?? '' ) );
if ( '' !== $ai4seo_attachment_file_size ) {
	$ai4seo_attachment_identity_details[] = $ai4seo_attachment_file_size;
}

// Prefer the absolute GMT value while allowing legacy attachments to recover their validated local date.
$ai4seo_attachment_upload_time_display = ai4seo_get_attachment_upload_time_display(
	(string) $ai4seo_this_attachment_post->post_date_gmt,
	'auto',
	(string) $ai4seo_this_attachment_post->post_date
);
if ( '' !== $ai4seo_attachment_upload_time_display ) {
	$ai4seo_attachment_identity_details[] = sprintf(
		/* translators: %s: Attachment upload time. */
		__( 'Upload time: %s', 'ai-for-seo' ),
		$ai4seo_attachment_upload_time_display
	);
}

$ai4seo_attachment_mime_type_display = trim( (string) $ai4seo_this_attachment_mime_type );
if ( '' !== $ai4seo_attachment_mime_type_display ) {
	$ai4seo_attachment_identity_details[] = sprintf(
		/* translators: %s: Attachment MIME type. */
		__( 'MIME type: %s', 'ai-for-seo' ),
		$ai4seo_attachment_mime_type_display
	);
}

$ai4seo_attachment_identity_subtitle = implode( ' · ', $ai4seo_attachment_identity_details );

// Merge fixed core fields with the shared registry so editor rendering and attribute ordering cannot drift.
$ai4seo_active_attachment_attributes  = ai4seo_get_active_attachment_attributes();
$ai4seo_attachment_editor_field_order = array_values(
	array_unique(
		array_merge(
			array( 'title', 'description', 'alt-text', 'caption' ),
			array_keys( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS ),
		),
	),
);

$ai4seo_can_administer_plugin                     = ai4seo_can_administer_plugin();
$ai4seo_settings_url                              = $ai4seo_can_administer_plugin ? ai4seo_get_subpage_url( 'settings' ) : '';
$ai4seo_attachment_attributes_custom_instructions = ai4seo_read_custom_instructions_postmeta( $ai4seo_this_attachment_post_id, AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY );
$ai4seo_attachment_field_evaluations              = array();
$ai4seo_attachment_preview_evaluation_identifiers = array(
	'title'       => 'attachment-title',
	'alt-text'    => 'attachment-alt',
	'caption'     => 'attachment-caption',
	'description' => 'attachment-description',
);

// Build each field footer once so Preview and Editor render identical metrics and source information.
foreach ( array_keys( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS ) as $ai4seo_attachment_field_identifier ) {
	if ( ! ai4seo_has_active_editor_fields( array( $ai4seo_attachment_field_identifier ), $ai4seo_active_attachment_attributes ) ) {
		continue;
	}

	$ai4seo_attachment_field_quality_window = ai4seo_get_generation_length_quality_window(
		'attachment_attributes',
		$ai4seo_attachment_field_identifier
	);
	$ai4seo_attachment_field_minimum_length = absint( $ai4seo_attachment_field_quality_window['min-length'] ?? 0 );
	$ai4seo_attachment_field_maximum_length = absint( $ai4seo_attachment_field_quality_window['max-length'] ?? 0 );
	$ai4seo_attachment_field_target         = '';
	$ai4seo_attachment_field_source_message = '';
	$ai4seo_attachment_preview_identifier   = $ai4seo_attachment_preview_evaluation_identifiers[ $ai4seo_attachment_field_identifier ] ?? $ai4seo_attachment_field_identifier;
	$ai4seo_attachment_field_input_name     = ai4seo_get_prefixed_input_name( 'attachment_attribute_' . $ai4seo_attachment_field_identifier );

	if ( 0 < $ai4seo_attachment_field_minimum_length && $ai4seo_attachment_field_minimum_length <= $ai4seo_attachment_field_maximum_length ) {
		$ai4seo_attachment_field_target = sprintf(
			/* translators: 1: Minimum character target, 2: Maximum character target. */
			__( 'Target: %1$d–%2$d characters', 'ai-for-seo' ),
			$ai4seo_attachment_field_minimum_length,
			$ai4seo_attachment_field_maximum_length
		);
	}

	if ( isset( $ai4seo_attachment_attribute_source_details[ $ai4seo_attachment_field_identifier ] ) ) {
		$ai4seo_attachment_field_source_details = ai4seo_get_editor_field_source_message_details(
			$ai4seo_attachment_attribute_source_details[ $ai4seo_attachment_field_identifier ]
		);
		$ai4seo_attachment_field_source_message = $ai4seo_attachment_field_source_details['message'] ?? '';
	}

	$ai4seo_attachment_field_evaluations[ $ai4seo_attachment_field_identifier ] = array(
		'minimum_length' => $ai4seo_attachment_field_minimum_length,
		'maximum_length' => $ai4seo_attachment_field_maximum_length,
		'preview_tag'    => ai4seo_get_editor_preview_evaluation_tag(
			$ai4seo_attachment_preview_identifier,
			'',
			$ai4seo_attachment_field_target,
			$ai4seo_attachment_field_source_message,
			$ai4seo_attachment_field_identifier
		),
		'editor_tag'     => ai4seo_get_editor_preview_evaluation_tag(
			$ai4seo_attachment_field_identifier,
			'',
			$ai4seo_attachment_field_target,
			$ai4seo_attachment_field_source_message,
			$ai4seo_attachment_field_identifier,
			$ai4seo_attachment_field_input_name . '-length-feedback'
		),
	);
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Preserve the existing empty-state behavior before constructing the full editor workspace.
if ( ! $ai4seo_active_attachment_attributes ) {
	ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Media Attributes Editor', 'ai-for-seo' ) ) );
	ai4seo_echo_wp_kses(
		ai4seo_get_editor_no_active_fields_notice_tag(
			$ai4seo_can_administer_plugin
				? __( 'No media attributes are active. Please activate at least one media attribute in the plugin settings to manage media attributes.', 'ai-for-seo' )
				: __( 'No media attributes are active. A site administrator must activate at least one media attribute before media attributes can be managed.', 'ai-for-seo' ),
			$ai4seo_settings_url
		)
	);
	return;
}

$ai4seo_editor_default_view_mode       = ai4seo_get_editor_default_view_mode();
$ai4seo_attachment_preview_context     = ai4seo_get_attachment_editor_preview_context(
	$ai4seo_this_post_attachment_attributes,
	$ai4seo_active_attachment_attributes,
	$ai4seo_this_attachment_is_an_image,
	$ai4seo_this_attachment_url
);
$ai4seo_editable_attachment_attributes = $ai4seo_active_attachment_attributes;

// Alt Text has no editable control when the attachment is not a supported image.
if ( ! $ai4seo_this_attachment_is_an_image ) {
	$ai4seo_editable_attachment_attributes = array_values(
		array_diff( $ai4seo_editable_attachment_attributes, array( 'alt-text' ) )
	);
}

// The AJAX modal parser places this shared headline in the modal chrome above the workspace.
ai4seo_echo_wp_kses( ai4seo_get_modal_headline_tag( __( 'Media Attributes Editor', 'ai-for-seo' ) ) );

// The existing form remains the single source of save and generation values in both display modes.
// phpcs:disable Generic.WhiteSpace.ScopeIndent -- Visual echo indentation mirrors the generated workspace hierarchy.
echo '<div'
	. " class='ai4seo-form ai4seo-editor-form ai4seo-editor-workspace ai4seo-attachment-editor-workspace'"
	. " data-ai4seo-editor-context='attachment'"
	. " data-ai4seo-default-view-mode='" . esc_attr( $ai4seo_editor_default_view_mode ) . "'"
	. " data-ai4seo-preview-context='" . esc_attr( wp_json_encode( $ai4seo_attachment_preview_context ) ) . "'"
	. '>';

	echo "<div class='ai4seo-editor-workspace-header'>";
		echo "<div class='ai4seo-editor-workspace-identity ai4seo-attachment-editor-workspace-identity'>";
			echo "<div class='ai4seo-attachment-editor-header-media'>";

	// Keep the media preview beside the attachment identity while preserving its larger-preview interaction.
	if ( $ai4seo_this_attachment_is_an_image ) {
		/* translators: Opens a larger visual preview of the current attachment. */
		$ai4seo_attachment_context_image_label = __( 'Open larger image preview', 'ai-for-seo' );
		echo "<button type='button' class='ai4seo-attachment-context-image-trigger' data-ai4seo-attachment-context-image-url='" . esc_url( $ai4seo_this_attachment_url ) . "' aria-label='" . esc_attr( $ai4seo_attachment_context_image_label ) . "'>";
			echo "<img src='" . esc_url( $ai4seo_this_attachment_url ) . "' alt='' />";
			echo "<span class='ai4seo-attachment-context-image-hover-preview' aria-hidden='true'><img src='" . esc_url( $ai4seo_this_attachment_url ) . "' alt='' /></span>";
		echo '</button>';
	} else {
		echo "<div class='ai4seo-attachment-document-placeholder'>" . esc_html__( 'Non-image attachment', 'ai-for-seo' ) . '</div>';
	}

			echo '</div>';
			echo "<div class='ai4seo-attachment-editor-identity-copy'>";
				echo "<h2 class='ai4seo-editor-workspace-title'>" . esc_html( $ai4seo_this_post_attachment_attributes['title'] ) . '</h2>';
				echo "<p class='ai4seo-editor-workspace-subtitle'>";
					echo esc_html( $ai4seo_attachment_identity_subtitle );
				echo '</p>';
			echo '</div>';
		echo '</div>';
		echo "<div class='ai4seo-editor-workspace-header-actions'>";
			ai4seo_echo_wp_kses( ai4seo_get_editor_mode_switch_tag( $ai4seo_editor_default_view_mode ) );
		echo '</div>';
	echo '</div>';

	echo "<div class='ai4seo-editor-workspace-scroll'>";
		echo "<div class='ai4seo-editor-shared-context-grid ai4seo-attachment-editor-shared-context-grid'>";
			echo "<section class='ai4seo-editor-context-card ai4seo-attachment-editor-context'>";
				ai4seo_echo_wp_kses(
					ai4seo_get_editor_preview_card_heading_tag(
						__( 'Context detection', 'ai-for-seo' ),
						'',
						'',
						__( 'Review the usage context that SOOZ can use for future media attribute generation.', 'ai-for-seo' ),
						__( 'Context detection: Help', 'ai-for-seo' )
					)
				);
				echo "<div class='ai4seo-attachment-editor-context-content'>";

				// Usage context shares the media image row so a separate context block is unnecessary.
				echo "<div class='ai4seo-attachment-usage-context-status' data-attachment-post-id='" . esc_attr( $ai4seo_this_attachment_post_id ) . "'>";
					echo "<div class='ai4seo-attachment-usage-context-loading'>";
						ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'rotate', __( 'Loading', 'ai-for-seo' ), 'ai4seo-spinning-icon ai4seo-gray-icon ai4seo-16x16-icon' ) );
						echo '<span>' . esc_html__( 'Checking image usage context...', 'ai-for-seo' ) . '</span>';
					echo '</div>';

					echo "<div class='ai4seo-attachment-usage-context-result'></div>";
				echo '</div>';
				echo '</div>';
			echo '</section>';

	// Place the shared generation instructions beside attachment context in both modes.

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
			'attachment-attributes-editor',
			'',
			true
		)
	);
		echo '</div>';

		// Preview cards explain where WordPress stores each attribute and approximate likely appearances.
		$ai4seo_attachment_preview_descriptions = array(
			'caption'     => __( 'Preview: Appearance depends on your theme, blocks, page builder, and other settings. SOOZ stores the caption but does not add captions to your pages.', 'ai-for-seo' ),
			'alt-text'    => __( 'Preview: An escaped image alt attribute. Decorative images may intentionally use empty alt text.', 'ai-for-seo' ),
			'title'       => __( 'Preview: An approximation of the title in WordPress Media Library details. WordPress does not automatically output this as an HTML title attribute.', 'ai-for-seo' ),
			'description' => __( 'Preview: An approximation of a WordPress media-details or attachment-page content panel. Public visibility depends on your theme or another plugin.', 'ai-for-seo' ),
		);

		echo "<div class='ai4seo-editor-preview-panel ai4seo-editor-mode-panel' data-ai4seo-editor-mode-panel='preview'>";
			echo "<div class='ai4seo-editor-preview-grid ai4seo-attachment-preview-grid'>";
			if ( ai4seo_has_active_editor_fields( array( 'title' ), $ai4seo_active_attachment_attributes ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-attachment-title-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Title', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_target( array( 'title' ), $ai4seo_editable_attachment_attributes ),
							'',
							( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS['title']['hint'] ?? '' ) . '<br><br>' . $ai4seo_attachment_preview_descriptions['title'],
							__( 'Title: Help', 'ai-for-seo' )
						)
					);
					echo "<div class='ai4seo-wordpress-media-details-preview'>";
						echo "<div class='ai4seo-wordpress-media-details-toolbar'><span>" . esc_html__( 'Media Library details', 'ai-for-seo' ) . '</span></div>';
						echo "<div class='ai4seo-attachment-title-preview-value ai4seo-preview-measured-text' data-ai4seo-preview-field='title'></div>";
						echo "<span class='ai4seo-attachment-preview-filename'></span>";
					echo '</div>';
					ai4seo_echo_wp_kses( $ai4seo_attachment_field_evaluations['title']['preview_tag'] ?? '' );
				echo '</article>';
			}

			if ( ai4seo_has_active_editor_fields( array( 'description' ), $ai4seo_active_attachment_attributes ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-attachment-description-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Description', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_target( array( 'description' ), $ai4seo_editable_attachment_attributes ),
							'',
							( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS['description']['hint'] ?? '' ) . '<br><br>' . $ai4seo_attachment_preview_descriptions['description'],
							__( 'Description: Help', 'ai-for-seo' )
						)
					);
					echo "<div class='ai4seo-wordpress-media-details-preview'>";
						echo "<div class='ai4seo-wordpress-media-details-toolbar'><span>" . esc_html__( 'Media Library details', 'ai-for-seo' ) . '</span></div>';
						echo "<div class='ai4seo-attachment-description-preview-value ai4seo-preview-measured-text' data-ai4seo-preview-field='description'></div>";
					echo '</div>';
					ai4seo_echo_wp_kses( $ai4seo_attachment_field_evaluations['description']['preview_tag'] ?? '' );
				echo '</article>';
			}

			if ( ai4seo_has_active_editor_fields( array( 'alt-text' ), $ai4seo_active_attachment_attributes ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-attachment-alt-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Alt Text', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_target( array( 'alt-text' ), $ai4seo_editable_attachment_attributes ),
							'',
							( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS['alt-text']['hint'] ?? '' ) . '<br><br>' . $ai4seo_attachment_preview_descriptions['alt-text'],
							__( 'Alt Text: Help', 'ai-for-seo' )
						)
					);
					echo "<div class='ai4seo-alt-code-preview'>";
						echo "<code><span class='ai4seo-code-punctuation'>&lt;</span><span class='ai4seo-code-tag'>img</span> <span class='ai4seo-alt-code-attribute-preview'><span class='ai4seo-code-attribute'>alt</span><span class='ai4seo-code-punctuation'>=</span><span class='ai4seo-code-string'>&quot;</span><span class='ai4seo-alt-code-value ai4seo-preview-measured-text' data-ai4seo-preview-field='alt-text'></span><span class='ai4seo-code-string'>&quot;</span></span><span class='ai4seo-code-punctuation'>&gt;</span></code>";
					echo '</div>';
				echo "<div class='ai4seo-attachment-not-applicable ai4seo-display-none'>" . esc_html__( 'Not applicable: this attachment is not a supported image, so an image alt-text simulation is unavailable.', 'ai-for-seo' ) . '</div>';
					ai4seo_echo_wp_kses( $ai4seo_attachment_field_evaluations['alt-text']['preview_tag'] ?? '' );
				echo '</article>';
			}

			if ( ai4seo_has_active_editor_fields( array( 'caption' ), $ai4seo_active_attachment_attributes ) ) {
				echo "<article class='ai4seo-editor-preview-card ai4seo-attachment-caption-preview-card'>";
					ai4seo_echo_wp_kses(
						ai4seo_get_editor_preview_card_heading_tag(
							__( 'Caption', 'ai-for-seo' ),
							ai4seo_get_editor_preview_edit_target( array( 'caption' ), $ai4seo_editable_attachment_attributes ),
							'',
							( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS['caption']['hint'] ?? '' ) . '<br><br>' . $ai4seo_attachment_preview_descriptions['caption'],
							__( 'Caption: Help', 'ai-for-seo' )
						)
					);
					echo "<figure class='ai4seo-caption-preview'>";
						echo "<div class='ai4seo-attachment-preview-image-shell'>";

			// The caption simulation uses the same image/applicability decision as the context card.
			if ( $ai4seo_this_attachment_is_an_image ) {
				echo "<img src='" . esc_url( $ai4seo_this_attachment_url ) . "' alt='' />";
			} else {
				echo "<div class='ai4seo-attachment-document-placeholder'>" . esc_html__( 'Non-image attachment', 'ai-for-seo' ) . '</div>';
			}

						echo '</div>';
						echo "<figcaption class='ai4seo-caption-preview-value ai4seo-preview-measured-text' data-ai4seo-preview-field='caption'></figcaption>";
					echo '</figure>';
					ai4seo_echo_wp_kses( $ai4seo_attachment_field_evaluations['caption']['preview_tag'] ?? '' );
				echo '</article>';
			}
			echo '</div>';
		echo '</div>';

		echo "<div class='ai4seo-editor-fields-panel ai4seo-editor-mode-panel' data-ai4seo-editor-mode-panel='editor'>";
			echo "<div class='ai4seo-editor-fields-grid'>";


	// Render the existing editable controls after the preview cards so both modes share one form.

	$ai4seo_skipped_attachment_attributes = array();

	// Reuse the translated suffix while field names keep each help trigger distinct for assistive technology.
	$ai4seo_help_aria_label = __( 'Help', 'ai-for-seo' );

	foreach ( $ai4seo_attachment_editor_field_order as $ai4seo_this_attachment_attribute_identifier ) {
		$ai4seo_this_attachment_attribute_details = AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS[ $ai4seo_this_attachment_attribute_identifier ] ?? array();

		if ( ! in_array( $ai4seo_this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes, true ) ) {
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
		$ai4seo_attachment_field_evaluation          = $ai4seo_attachment_field_evaluations[ $ai4seo_this_attachment_attribute_identifier ] ?? array();
		$ai4seo_attachment_attribute_minimum_length  = absint( $ai4seo_attachment_field_evaluation['minimum_length'] ?? 0 );
		$ai4seo_attachment_attribute_maximum_length  = absint( $ai4seo_attachment_field_evaluation['maximum_length'] ?? 0 );
		$ai4seo_attachment_length_input_class        = '';
		$ai4seo_attachment_length_input_attributes   = '';

		if ( $ai4seo_attachment_attribute_minimum_length > 0 && $ai4seo_attachment_attribute_maximum_length >= $ai4seo_attachment_attribute_minimum_length ) {
			$ai4seo_attachment_length_input_class      = ' ai4seo-editor-length-tracked';
			$ai4seo_attachment_length_input_attributes = ai4seo_get_editor_length_input_attributes(
				$ai4seo_this_attachment_attribute_input_name . '-length-feedback',
				$ai4seo_attachment_attribute_minimum_length,
				$ai4seo_attachment_attribute_maximum_length
			);
		}

		// Keep each generated field in the shared editor form-item structure used by metadata fields.
		$ai4seo_attachment_field_css_class = 'ai4seo-form-item ai4seo-form-item-flush ai4seo-editor-field-card ai4seo-editor-field-' . sanitize_html_class( $ai4seo_this_attachment_attribute_identifier );
		echo "<div class='" . esc_attr( $ai4seo_attachment_field_css_class ) . "'>";

			// Separate the tooltip button from the native field label while preserving their visual grouping.
			echo "<span class='ai4seo-form-item-label'>";
				echo "<span class='ai4seo-label-with-tooltip'>";
					echo "<label for='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "'>";
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

			echo '</span>';

			// Render the registry-defined control inside the shared generate-button wrapper.
			echo "<div class='ai4seo-form-item-input-wrapper ai4seo-form-input-wrapper-with-generate-button'>";

				// Text field.
		if ( 'textfield' === $ai4seo_this_attachment_attribute_details['input-type'] ) {
			echo "<input type='text'"
				. ' class="ai4seo-textfield ai4seo-editor-textfield' . esc_attr( $ai4seo_attachment_length_input_class ) . '"'
				. ' id="' . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . '"'
				. ' name="' . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . '"'
				. ' value="' . esc_attr( $ai4seo_this_attachment_attribute_value ) . '"'
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each conditional attribute value is escaped when the string is assembled above.
				. $ai4seo_attachment_length_input_attributes
				. ' />';
		} elseif ( 'textarea' === $ai4seo_this_attachment_attribute_details['input-type'] ) {
			// Preserve multiline editing for attachment fields declared as textareas in the shared registry.
			echo '<textarea'
				. " class='ai4seo-textarea ai4seo-editor-textarea ai4seo-auto-resize-textarea" . esc_attr( $ai4seo_attachment_length_input_class ) . "'"
				. " id='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "'"
				. " name='" . esc_attr( $ai4seo_this_attachment_attribute_input_name ) . "'"
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each conditional attribute value is escaped when the string is assembled above.
				. $ai4seo_attachment_length_input_attributes . '>'
				. esc_textarea( $ai4seo_this_attachment_attribute_value )
				. '</textarea>';
		}

			echo '</div>';

			// Preview and Editor reuse the same complete footer markup for this field.
			ai4seo_echo_wp_kses( $ai4seo_attachment_field_evaluation['editor_tag'] ?? '' );
		echo '</div>';
	}
			echo '</div>';
		echo '</div>';

	// put the post id into a hidden field, so we have access to it after the form is submitted.
	echo "<input type='hidden' id='ai4seo-editor-modal-post-id' name='" . esc_attr( ai4seo_get_prefixed_input_name( 'attachment_attributes_editor_post_id' ) ) . "' value='" . esc_attr( $ai4seo_this_attachment_post_id ) . "' />";

	// friendly reminder: $ai4seo_skipped_attachment_attributes.
	if ( $ai4seo_skipped_attachment_attributes ) {
		if ( $ai4seo_can_administer_plugin ) {
			/* translators: 1: Comma-separated list of media attributes. 2: URL to plugin settings. */
			$ai4seo_inactive_fields_notice = __( '<strong>Note:</strong> The following media attributes are currently inactive and not shown in this editor: %1$s. You can activate them in the <a href="%2$s" target="_blank">plugin settings</a>.', 'ai-for-seo' );
		} else {
			/* translators: %1$s: Comma-separated list of media attributes. */
			$ai4seo_inactive_fields_notice = __( '<strong>Note:</strong> The following media attributes are currently inactive and not shown in this editor: %1$s. A site administrator can activate them in the plugin settings.', 'ai-for-seo' );
		}

		ai4seo_echo_wp_kses(
			ai4seo_get_editor_inactive_fields_notice_tag(
				$ai4seo_skipped_attachment_attributes,
				AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS,
				$ai4seo_inactive_fields_notice,
				$ai4seo_settings_url
			)
		);
	}
	echo '</div>';

	// === BUTTONS ROW ================================================================================= \\

	// Collect footer buttons before rendering the shared wrapper so the optional next-entry action keeps its current order.
	$ai4seo_modal_footer_button_tags = array(
		ai4seo_get_modal_close_button_tag( esc_html__( 'Abort', 'ai-for-seo' ), 'ai4seo-big-button' ),
		"<div id='ai4seo-generate-all-attachment-attributes-button-hook' class='ai4seo-editor-generate-all-hook'></div>",
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
	ai4seo_echo_wp_kses( ai4seo_get_modal_footer_tag( $ai4seo_modal_footer_button_tags, 'ai4seo-editor-workspace-footer' ) );
	echo '</div>';
// phpcs:enable Generic.WhiteSpace.ScopeIndent
