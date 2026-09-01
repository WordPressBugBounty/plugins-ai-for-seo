<?php
/**
 * Custom-instruction normalization, rendering, persistence, and generation payloads.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === CUSTOM INSTRUCTIONS ================================================================= \\

/**
 * Return post types that can receive metadata custom instructions.
 *
 * Attachments use the dedicated media-attribute instruction scope instead.
 *
 * @return array Supported metadata post type identifiers.
 */
function ai4seo_get_supported_metadata_custom_instructions_post_types(): array {
	$supported_post_types = ai4seo_get_supported_post_types( false );

	if ( ! is_array( $supported_post_types ) || ! $supported_post_types ) {
		return array();
	}

	$metadata_post_types = array();

	foreach ( $supported_post_types as $post_type ) {
		$post_type = sanitize_key( (string) $post_type );

		// Settings can be imported, so keep only current metadata-capable post types.
		if ( ! $post_type || 'attachment' === $post_type ) {
			continue;
		}

		$metadata_post_types[ $post_type ] = $post_type;
	}

	return array_values( $metadata_post_types );
}


/**
 * Normalize a custom instruction value and cap it to the current account limit.
 *
 * @param mixed    $value        Raw instruction value.
 * @param int|null $length_limit Optional override for the character limit.
 * @return string Normalized instruction value.
 */
function ai4seo_normalize_custom_instructions_value( $value, ?int $length_limit = null ): string {
	// Accept scalar form values only because settings import and AJAX saves can pass mixed payloads.
	if ( is_scalar( $value ) ) {
		$value = (string) $value;
	}

	if ( ! is_string( $value ) ) {
		return '';
	}

	// Keep server-side cleanup aligned across settings, editor postmeta, manual generation, and autopilot.
	$value = sanitize_textarea_field( $value );
	$value = trim( $value );

	if ( null === $length_limit ) {
		$length_limit = ai4seo_get_custom_instructions_length_limit();
	}

	if ( ai4seo_mb_strlen( $value ) > $length_limit ) {
		$value = ai4seo_trim_string_to_length( $value, $length_limit );
	}

	return $value;
}


/**
 * Normalize custom instruction setting values before validation or persistence.
 *
 * @param string $setting_name  Setting identifier.
 * @param mixed  $setting_value Raw setting value.
 * @return mixed Normalized setting value.
 */
function ai4seo_normalize_custom_instructions_setting_value( string $setting_name, $setting_value ) {
	switch ( $setting_name ) {
		case AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS:
			// Plain custom-instruction settings share the same text cleanup and subscription cap.
			return ai4seo_normalize_custom_instructions_value( $setting_value );

		case AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS:
			// Post-type instructions are stored as a sparse map so empty entries do not bloat settings.
			if ( ! is_array( $setting_value ) ) {
				return array();
			}

			$normalized_post_type_instructions = array();
			$allowed_post_types                = array_flip( ai4seo_get_supported_metadata_custom_instructions_post_types() );

			foreach ( $setting_value as $post_type => $post_type_custom_instructions ) {
				$post_type = sanitize_key( $post_type );

				// Imported maps may contain stale post types; only keep scopes the plugin can currently render/generate.
				if ( ! $post_type || ! isset( $allowed_post_types[ $post_type ] ) ) {
					continue;
				}

				$post_type_custom_instructions = ai4seo_normalize_custom_instructions_value( $post_type_custom_instructions );

				if ( '' === $post_type_custom_instructions ) {
					continue;
				}

				$normalized_post_type_instructions[ $post_type ] = $post_type_custom_instructions;
			}

			return $normalized_post_type_instructions;
	}

	return $setting_value;
}


/**
 * Validate custom instruction setting values.
 *
 * @param string $setting_name  Setting identifier.
 * @param mixed  $setting_value Setting value.
 * @return bool True when valid.
 */
function ai4seo_validate_custom_instructions_setting_value( string $setting_name, $setting_value ): bool {
	$length_limit = ai4seo_get_custom_instructions_length_limit();

	switch ( $setting_name ) {
		case AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS:
			// Validate already-normalized text against the same cap used by the renderer and save helpers.
			return is_string( $setting_value ) && ai4seo_mb_strlen( $setting_value ) <= $length_limit;

		case AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS:
			// Validate the sparse post-type map produced by the normalizer before it is saved or imported.
			if ( ! is_array( $setting_value ) ) {
				return false;
			}

			$allowed_post_types = array_flip( ai4seo_get_supported_metadata_custom_instructions_post_types() );

			foreach ( $setting_value as $post_type => $post_type_custom_instructions ) {
				if ( ! is_string( $post_type ) || ! isset( $allowed_post_types[ $post_type ] ) ) {
					return false;
				}

				if ( ! is_string( $post_type_custom_instructions ) || ai4seo_mb_strlen( $post_type_custom_instructions ) > $length_limit ) {
					return false;
				}
			}

			return true;
	}

	return false;
}


/**
 * Return textarea HTML for a custom instruction input.
 *
 * @param string $input_id               Input id.
 * @param string $input_name             Input name.
 * @param string $input_value            Current value.
 * @param string $additional_css_classes Additional textarea classes.
 * @param string $field_label            Label used by client-side validation.
 * @param string $placeholder            Optional placeholder text.
 * @return string Textarea HTML.
 */
function ai4seo_get_custom_instructions_textarea_tag(
	string $input_id,
	string $input_name,
	string $input_value = '',
	string $additional_css_classes = '',
	string $field_label = '',
	string $placeholder = ''
): string {
	$length_limit = ai4seo_get_custom_instructions_length_limit();
	$input_value  = ai4seo_normalize_custom_instructions_value( $input_value, $length_limit );
	$classes      = trim( 'ai4seo-textarea ai4seo-auto-resize-textarea ai4seo-custom-instructions-input ' . $additional_css_classes );

	// Expose the PHP-enforced limit to the browser so counters and client-side validation stay in sync.
	return '<textarea'
		. ' class="' . esc_attr( $classes ) . '"'
		. ' id="' . esc_attr( $input_id ) . '"'
		. ' name="' . esc_attr( $input_name ) . '"'
		. ' rows="1"'
		. ( '' !== $placeholder ? " placeholder='" . esc_attr( $placeholder ) . "'" : '' )
		. ' data-ai4seo-custom-instructions-limit="' . esc_attr( $length_limit ) . '"'
		. ' data-ai4seo-custom-instructions-label="' . esc_attr( $field_label ) . '"'
		. '>' . esc_textarea( $input_value ) . '</textarea>';
}


/**
 * Return character counter HTML for a custom instruction textarea.
 *
 * @param string $input_id Input id this counter belongs to.
 * @return string Counter HTML.
 */
function ai4seo_get_custom_instructions_character_counter_tag( string $input_id ): string {
	$length_limit                = ai4seo_get_custom_instructions_length_limit();
	$is_active_subscription_user = ai4seo_user_has_active_subscription();

	// Pair the counter with the textarea id so AJAX-rendered forms can initialize counters after insertion.
	$html  = '<p class="ai4seo-form-item-description ai4seo-custom-instructions-counter" data-input-id="' . esc_attr( $input_id ) . '" data-max-length="' . esc_attr( $length_limit ) . '">';
	$html .= '<span class="ai4seo-custom-instructions-characters-left">';
	$html .= sprintf(
		/* translators: %1$s: Number of used characters. %2$s: Character limit. */
		esc_html__( '%1$s / %2$s characters', 'ai-for-seo' ),
		ai4seo_format_number_i18n( 0 ),
		ai4seo_format_number_i18n( $length_limit )
	);
	$html .= '</span>';

	// Free accounts keep the upgrade CTA, while subscribers see why the higher limit is available.
	if ( ! $is_active_subscription_user ) {
		$html .= ' &middot; ';
		$html .= ai4seo_get_subscription_upgrade_prompt_tag( 's', 'custom_instructions_1000_chars' );
	} else {
		$current_plan           = ai4seo_get_current_user_plan();
		$current_plan_name      = ai4seo_get_plan_name( $current_plan );
		$current_plan_name_html = '<strong>' . esc_html( $current_plan_name ) . '</strong>';

		$html .= ' &middot; ';
		$html .= '<span class="ai4seo-custom-instructions-plan-notice">';
		$html .= sprintf(
			/* translators: %1$s: Character limit. %2$s: Current subscription plan name wrapped in bold markup. */
			esc_html__( 'You can enter up to %1$s chars because your %2$s plan is active.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $length_limit ) ),
			$current_plan_name_html
		);
		$html .= '</span>';
	}
	$html .= '</p>';

	return $html;
}


/**
 * Return examples and limit support text for a custom instruction context.
 *
 * @param string $context The custom instruction context.
 * @return array{intro: string, examples: string[]} Context details.
 */
function ai4seo_get_custom_instructions_examples_context_data( string $context ): array {
	$context  = sanitize_key( $context );
	$intro    = '';
	$examples = array();

	// Map each UI surface to guidance that matches the scope of instructions collected there.
	switch ( $context ) {
		case 'global':
			$intro    = __( 'Use global instructions for site-wide guidance that should affect both metadata and media attributes.', 'ai-for-seo' );
			$examples = array(
				__( 'We sell premium marble kitchen tables for modern homes and interior design projects. Focus on marble patterns, table shapes, room fit, delivery, and long-term maintenance.', 'ai-for-seo' ),
				__( 'This site is an artist gallery for coastal abstract paintings. Focus on original artworks, collection themes, artist stories, exhibition context, and collectors looking for statement pieces.', 'ai-for-seo' ),
				__( 'This is a tech blog about practical AI tools, automation, and machine learning trends. Focus on real use cases for founders, developers, and marketing teams.', 'ai-for-seo' ),
			);
			break;

		case 'metadata':
			$intro    = __( 'Use metadata instructions for titles, descriptions, social previews, and similar metadata fields.', 'ai-for-seo' );
			$examples = array(
				__( 'Always include the city this post is about in the focus keyphrase and meta title.', 'ai-for-seo' ),
				__( 'Include the article number in the meta description.', 'ai-for-seo' ),
				__( 'End social media descriptions with this CTA: "Join us now!"', 'ai-for-seo' ),
			);
			break;

		case 'metadata-post-type':
			$intro    = __( 'Use post type instructions when one content type needs different metadata guidance than the rest of the site.', 'ai-for-seo' );
			$examples = array(
				__( 'Product pages: prioritize use cases, differentiators, and purchase intent.', 'ai-for-seo' ),
				__( 'Blog posts: write educational metadata and avoid hard-sell language.', 'ai-for-seo' ),
				__( 'Case studies: mention the industry, result, or project type when available.', 'ai-for-seo' ),
			);
			break;

		case 'metadata-editor':
			$intro    = __( 'Use entry-specific instructions for guidance that should only affect this entry going forward.', 'ai-for-seo' );
			$examples = array(
				__( 'When the generation language is Auto, output French metadata for this post.', 'ai-for-seo' ),
				__( 'Mention the product dimensions in the meta description.', 'ai-for-seo' ),
				__( 'Include "New York" in the focus keyphrase.', 'ai-for-seo' ),
				__( 'WRITE EVERYTHING IN ALL CAPS!', 'ai-for-seo' ),
			);
			break;

		case 'media-attributes':
			$intro    = __( 'Use media attribute instructions for image titles, alt text, captions, and media descriptions.', 'ai-for-seo' );
			$examples = array(
				__( 'We sell marble kitchen tables. If not stated otherwise, assume the table material is marble.', 'ai-for-seo' ),
				__( 'For people\'s names, abbreviate the last name, e.g. "Peter K."', 'ai-for-seo' ),
				__( 'Product pictures only: format alt text like "product name - dimensions - price" when data is available.', 'ai-for-seo' ),
			);
			break;

		case 'attachment-attributes-editor':
			$intro    = __( 'Use attachment-specific instructions for guidance that should only affect this media item going forward.', 'ai-for-seo' );
			$examples = array(
				__( 'The table is made of oak timber.', 'ai-for-seo' ),
				__( 'Do not mention the background color.', 'ai-for-seo' ),
				__( 'The painting artist is "Peter Francesco". Mention him.', 'ai-for-seo' ),
			);
			break;
	}

	if ( ! $examples ) {
		return array(
			'intro'    => '',
			'examples' => array(),
		);
	}

	return array(
		'intro'    => $intro,
		'examples' => $examples,
	);
}

/**
 * Build compact placeholder snippets from 3-4 word examples.
 *
 * @param string $context            Examples context.
 * @param int    $snippet_word_count Number of words per snippet.
 * @param int    $max_snippets       Maximum number of snippets to include.
 * @return string Placeholder text.
 */
function ai4seo_get_custom_instructions_placeholder( string $context, int $snippet_word_count = 4, int $max_snippets = 3 ): string {
	$context = sanitize_key( $context );

	// Entry editors use concise starters tailored to the output generated in each modal.
	if ( 'metadata-editor' === $context ) {
		return __( 'Include keyword..., Use concise tone..., Mention location..., Avoid clickbait...', 'ai-for-seo' );
	}

	if ( 'attachment-attributes-editor' === $context ) {
		return __( 'Focus on..., Material is..., Do not mention..., Her name is...', 'ai-for-seo' );
	}

	$context_data = ai4seo_get_custom_instructions_examples_context_data( $context );
	if ( empty( $context_data['examples'] ) || ! is_array( $context_data['examples'] ) ) {
		return '';
	}

	$max_snippets = max( 1, min( 5, $max_snippets ) );
	$max_words    = max( 3, min( 4, $snippet_word_count ) );
	$snippets     = array();

	foreach ( array_slice( $context_data['examples'], 0, $max_snippets ) as $example ) {
		$example = trim( str_replace( array( "\r", "\n", "\t" ), ' ', wp_strip_all_tags( (string) $example ) ) );
		if ( '' === $example ) {
			continue;
		}

		$example = preg_replace( '/\s+/', ' ', $example );
		$words   = preg_split( '/\s+/', preg_replace( '/["“”‘’]/u', '', $example ), -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $words ) || ! $words ) {
			continue;
		}

		$snippets[] = implode( ' ', array_slice( $words, 0, $max_words ) ) . '...';
	}

	return implode( ', ', $snippets );
}

/**
 * Return an examples-and-limits tooltip for a custom instruction field.
 *
 * @param string $context         The custom instruction field context.
 * @param string $description     Optional visible description to include in tooltip.
 * @param bool   $as_icon_trigger If true, render tooltip trigger as a help icon.
 * @return string Tooltip HTML.
 */
function ai4seo_get_custom_instructions_examples_tooltip_tag( string $context, string $description = '', bool $as_icon_trigger = false ): string {
	$context_data = ai4seo_get_custom_instructions_examples_context_data( $context );
	$intro        = $context_data['intro'];
	$examples     = $context_data['examples'];

	// Unsupported contexts intentionally render no trigger instead of showing generic or misleading examples.
	if ( ! $examples ) {
		return '';
	}

	$tooltip_html = '';

	// Keep the contextual introduction optional because every supported context still has useful examples.
	if ( '' !== $description ) {
		$tooltip_html .= '<span class="ai4seo-custom-instructions-description">' . wpautop( wp_kses_post( $description ) ) . '</span>';
	}
	if ( '' !== $intro ) {
		$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-description">' . esc_html( $intro ) . '</span>';
	}

	$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-caption"><strong>' . esc_html__( 'Examples & limits', 'ai-for-seo' ) . '</strong></span>';
	$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-list">';

	// Render each example separately so wrapping and spacing remain predictable inside the constrained tooltip.
	foreach ( $examples as $this_example ) {
		$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-item">&quot;' . esc_html( $this_example ) . '&quot;</span>';
	}

	// Close the example list before adding constraints so the two guidance groups remain distinct.
	$tooltip_html .= '</span>';

	// Keep immutable usage constraints beside examples so every instruction entry point explains the allowed influence.
	$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-caption ai4seo-custom-instructions-limits-caption"><strong>' . esc_html__( 'Usage limits', 'ai-for-seo' ) . '</strong></span>';
	$tooltip_html .= '<span class="ai4seo-custom-instructions-examples-description ai4seo-custom-instructions-limits-description">';
	$tooltip_html .= esc_html__( 'Custom instructions can guide wording, tone, terminology, emphasis, calls to action, and additional owner-supplied details. They cannot change which fields are generated, JSON or technical field formats, a fixed generation language, configured prefix/suffix behavior, official character, word, sentence, or item limits, storage caps, or safety rules. Requests for extra, repeated, padded, or unlimited output are ignored; compatible parts still apply.', 'ai-for-seo' );
	$tooltip_html .= ' ';

	// Resolve the active cap at render time because subscription status controls the usable instruction length.
	$tooltip_html .= sprintf(
		/* translators: %1$s: Free-plan character limit. %2$s: Subscription character limit. %3$s: Current account character limit. */
		esc_html__( 'Instruction text is limited to %1$s characters on Free or %2$s characters with an active subscription. Your current limit is %3$s characters.', 'ai-for-seo' ),
		esc_html( ai4seo_format_number_i18n( AI4SEO_CUSTOM_INSTRUCTIONS_FREE_LENGTH_LIMIT ) ),
		esc_html( ai4seo_format_number_i18n( AI4SEO_CUSTOM_INSTRUCTIONS_SUBSCRIPTION_LENGTH_LIMIT ) ),
		esc_html( ai4seo_format_number_i18n( ai4seo_get_custom_instructions_length_limit() ) )
	);
	$tooltip_html .= '</span>';

	// Use the shared trigger so the expanded guidance supports mouse and keyboard interaction consistently.
	$tooltip_trigger = $as_icon_trigger
		? ai4seo_get_svg_tag( 'circle-question', '', 'ai4seo-gray-icon ai4seo-custom-instructions-examples-icon' )
		: esc_html__( 'Examples & limits', 'ai-for-seo' );

	return ai4seo_get_tooltip_tag(
		$tooltip_trigger,
		$tooltip_html,
		array(
			'holder_css_class'   => 'ai4seo-custom-instructions-examples',
			'trigger_css_class'  => $as_icon_trigger ? 'ai4seo-icon-tooltip-trigger ai4seo-custom-instructions-examples-trigger' : 'ai4seo-custom-instructions-examples-trigger',
			'trigger_aria_label' => $as_icon_trigger ? __( 'Custom Instructions: Help', 'ai-for-seo' ) : '',
		)
	);
}


/**
 * Return a complete form item for a custom instruction textarea.
 *
 * @param string $input_id                         Input id.
 * @param string $input_name                       Input name.
 * @param string $input_value                      Current value.
 * @param string $label                            Visible label text.
 * @param string $description                      Description below the counter.
 * @param string $additional_textarea_css_classes  Additional textarea classes.
 * @param string $field_label                      Label used by client-side validation.
 * @param string $additional_form_item_css_classes Additional wrapper classes.
 * @param string $label_prefix_html                Optional HTML rendered before the visible label.
 * @param string $examples_context                 Optional examples tooltip context.
 * @param string $placeholder                      Optional placeholder text for textarea.
 * @param bool   $show_description_in_tooltip      Show description and examples inside a tooltip next to the label.
 * @return string Form item HTML.
 */
function ai4seo_get_custom_instructions_form_item_tag(
	string $input_id,
	string $input_name,
	string $input_value,
	string $label,
	string $description = '',
	string $additional_textarea_css_classes = '',
	string $field_label = '',
	string $additional_form_item_css_classes = '',
	string $label_prefix_html = '',
	string $examples_context = '',
	string $placeholder = '',
	bool $show_description_in_tooltip = false
): string {
	$form_item_css_classes = trim( 'ai4seo-form-item ' . $additional_form_item_css_classes );

	// Keep validation labels independent from visible labels so UI punctuation does not leak into error text.
	if ( '' === $field_label ) {
		$field_label = $label;
	}

	$active_description = trim( $description );
	$active_placeholder = trim( $placeholder );

	// Entry-editor tooltips provide their own concise placeholder when callers do not override it.
	if ( '' === $active_placeholder && $show_description_in_tooltip ) {
		$active_placeholder = ai4seo_get_custom_instructions_placeholder( $examples_context );
	}

	$description_tooltip_tag = '';
	if ( '' !== $active_description || '' !== $examples_context ) {
		$description_tooltip_tag = ai4seo_get_custom_instructions_examples_tooltip_tag( $examples_context, $active_description, $show_description_in_tooltip );
	}

	// Reuse the shared textarea and counter helpers so settings pages and AJAX editors stay in sync.
	$html = '<div class="' . esc_attr( $form_item_css_classes ) . '">';

	if ( $show_description_in_tooltip ) {
		$html .= '<span class="ai4seo-label-with-tooltip">';
	}

	// Allow shared label prefixes, such as setting badges, while keeping the main label escaped.
	$html .= '<label for="' . esc_attr( $input_id ) . '">';
	$html .= ai4seo_wp_kses( $label_prefix_html );
	$html .= esc_html( $label );
	$html .= '</label>';

	// Tooltip mode groups the label and help trigger without duplicating the label-rendering path.
	if ( $show_description_in_tooltip ) {
		if ( '' !== $description_tooltip_tag ) {
			$html .= ai4seo_wp_kses( $description_tooltip_tag );
		}
		$html .= '</span>';
	}

	$html .= '<div class="ai4seo-form-item-input-wrapper">';
	$html .= ai4seo_get_custom_instructions_textarea_tag(
		$input_id,
		$input_name,
		$input_value,
		$additional_textarea_css_classes,
		$field_label,
		$active_placeholder
	);
	$html .= ai4seo_wp_kses( ai4seo_get_custom_instructions_character_counter_tag( $input_id ) );

	// Append optional guidance inside the input wrapper so it follows the counter in every form surface.
	if ( ! $show_description_in_tooltip && '' !== $active_description ) {
		$examples_tooltip_html = ai4seo_get_custom_instructions_examples_tooltip_tag( $examples_context );

		$html .= '<p class="ai4seo-form-item-description">';
		$html .= ai4seo_wp_kses( $active_description );

		// Only add the separator when a supported examples context returned a tooltip.
		if ( '' !== $examples_tooltip_html ) {
			$html .= ' ';
			$html .= ai4seo_wp_kses( $examples_tooltip_html );
		}

		$html .= '</p>';
	}

	$html .= '</div>';
	$html .= '</div>';

	return $html;
}


/**
 * Return a settings-page form item for a custom instruction setting.
 *
 * @param string $setting_name                     Setting identifier.
 * @param string $label                            Visible label text.
 * @param string $description                      Description below the counter.
 * @param string $field_label                      Label used by client-side validation.
 * @param string $additional_textarea_css_classes  Additional textarea classes.
 * @param string $additional_form_item_css_classes Additional wrapper classes.
 * @param string $examples_context                 Optional examples tooltip context.
 * @return string Form item HTML.
 */
function ai4seo_get_custom_instructions_setting_form_item_tag(
	string $setting_name,
	string $label,
	string $description = '',
	string $field_label = '',
	string $additional_textarea_css_classes = '',
	string $additional_form_item_css_classes = '',
	string $examples_context = ''
): string {
	// Settings forms use the prefixed setting name for both id and name so save-anything mapping remains unchanged.
	$input_name  = ai4seo_get_prefixed_input_name( $setting_name );
	$input_value = ai4seo_get_setting( $setting_name );

	// Delegate the remaining markup to the same renderer used by AJAX editor fields.
	return ai4seo_get_custom_instructions_form_item_tag(
		$input_name,
		$input_name,
		$input_value,
		$label,
		$description,
		$additional_textarea_css_classes,
		$field_label,
		$additional_form_item_css_classes,
		'',
		$examples_context
	);
}


/**
 * Save custom instructions to a postmeta entry.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Postmeta key.
 * @param mixed  $value    Raw custom instruction value.
 * @return bool True when the entry was saved or cleared.
 */
function ai4seo_save_custom_instructions_postmeta( int $post_id, string $meta_key, $value ): bool {
	$post_id = absint( $post_id );

	if ( $post_id <= 0 || '' === $meta_key ) {
		return false;
	}

	// Route single-entry editor saves through the batch helper so bulk and editor saves cannot drift.
	$result = ai4seo_save_custom_instructions_postmeta_for_post_ids( array( $post_id ), $meta_key, $value );

	return in_array( $post_id, $result['saved_post_ids'], true );
}


/**
 * Save one custom-instruction value to several postmeta entries.
 *
 * @param array  $post_ids Post IDs.
 * @param string $meta_key Postmeta key.
 * @param mixed  $value    Raw custom instruction value.
 * @return array Saved, skipped, and normalized value details.
 */
function ai4seo_save_custom_instructions_postmeta_for_post_ids( array $post_ids, string $meta_key, $value ): array {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$meta_key = sanitize_key( $meta_key );

	// Return a stable shape so editor and bulk callers can count results without special casing validation failures.
	$result = array(
		'saved_post_ids'              => array(),
		'skipped_post_ids'            => $post_ids,
		'custom_instructions'         => '',
		'custom_instructions_cleared' => true,
	);

	if ( ! $post_ids || '' === $meta_key ) {
		return $result;
	}

	// Normalize once for the whole batch so every selected entry receives exactly the same stored value.
	$value                                 = ai4seo_normalize_custom_instructions_value( $value );
	$result['custom_instructions']         = $value;
	$result['custom_instructions_cleared'] = ( '' === $value );

	foreach ( $post_ids as $this_post_id ) {
		if ( '' === $value ) {
			// Match the non-empty write path by rejecting posts that disappeared after caller validation.
			if ( ! get_post( $this_post_id ) ) {
				continue;
			}

			// Treat an already-absent entry as cleared, but only report an existing entry after deletion succeeds.
			if (
				! metadata_exists( 'post', $this_post_id, $meta_key )
				|| delete_post_meta( $this_post_id, $meta_key )
			) {
				$result['saved_post_ids'][] = $this_post_id;
			}

			continue;
		}

		if ( ai4seo_update_post_meta( $this_post_id, $meta_key, $value ) ) {
			$result['saved_post_ids'][] = $this_post_id;
		}
	}

	$result['saved_post_ids']   = array_values( array_unique( array_map( 'absint', $result['saved_post_ids'] ) ) );
	$result['skipped_post_ids'] = array_values( array_diff( $post_ids, $result['saved_post_ids'] ) );

	return $result;
}


/**
 * Read normalized custom instructions from postmeta.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Postmeta key.
 * @return string Normalized custom instructions.
 */
function ai4seo_read_custom_instructions_postmeta( int $post_id, string $meta_key ): string {
	$post_id = absint( $post_id );

	if ( $post_id <= 0 || '' === $meta_key ) {
		return '';
	}

	// Postmeta is normalized again on read to apply the current subscription cap to older saved values.
	return ai4seo_normalize_custom_instructions_value( get_post_meta( $post_id, $meta_key, true ) );
}


/**
 * Read the optional entry-level custom instructions submitted with a manual generation request.
 *
 * @return mixed|null Raw request value, or null when the field was not submitted.
 */
function ai4seo_get_generation_entry_custom_instructions_request_value() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX callers verify the nonce first.
	if ( ! array_key_exists( 'ai4seo_entry_custom_instructions', $_REQUEST ) ) {
		return null;
	}

	// Unexpected arrays are treated like empty text so manual generation does not persist or submit mixed data.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX callers verify the nonce first.
	if ( is_array( $_REQUEST['ai4seo_entry_custom_instructions'] ) ) {
		return '';
	}

	// Keep null distinct from an intentionally empty textarea so Generate can omit saved entry-level instructions.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX callers verify the nonce first.
	return sanitize_textarea_field( wp_unslash( $_REQUEST['ai4seo_entry_custom_instructions'] ) );
}


/**
 * Resolve entry-level generation instructions from the request override or saved postmeta.
 *
 * @param int    $post_id                   Post or attachment post ID.
 * @param string $meta_key                  Postmeta key used by the editor.
 * @param mixed  $entry_custom_instructions Optional request-only editor value.
 * @return string Normalized custom instructions.
 */
function ai4seo_get_generation_entry_custom_instructions( int $post_id, string $meta_key, $entry_custom_instructions = null ): string {
	if ( null !== $entry_custom_instructions ) {
		// Manual Generate may send an unsaved editor value that should affect only the current API request.
		return ai4seo_normalize_custom_instructions_value( $entry_custom_instructions );
	}

	// Absent request values keep the saved postmeta behavior used by autopilot and older generation paths.
	return ai4seo_read_custom_instructions_postmeta( $post_id, $meta_key );
}


/**
 * Collect custom instructions for a generation request.
 *
 * @param string $generation_type             metadata or attachment_attributes.
 * @param int    $post_id                     Post or attachment post ID.
 * @param mixed  $entry_custom_instructions   Optional request-only editor value.
 * @return array Custom instruction payload for RobHub.
 */
function ai4seo_get_generation_custom_instructions( string $generation_type, int $post_id, $entry_custom_instructions = null ): array {
	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return array();
	}

	// Build a sparse payload so generation requests without instructions remain byte-for-byte compatible.
	$custom_instructions         = array();
	$general_custom_instructions = ai4seo_normalize_custom_instructions_value( ai4seo_get_setting( AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS ) );

	if ( '' !== $general_custom_instructions ) {
		$custom_instructions['general'] = $general_custom_instructions;
	}

	if ( 'metadata' === $generation_type ) {
		// Metadata generations combine global, metadata, post-type, and entry-level instruction scopes.
		$metadata_custom_instructions = ai4seo_normalize_custom_instructions_value( ai4seo_get_setting( AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS ) );

		if ( '' !== $metadata_custom_instructions ) {
			$custom_instructions['metadata'] = $metadata_custom_instructions;
		}

		$post_type                     = get_post_type( $post_id );
		$post_type_custom_instructions = ai4seo_get_setting( AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS );

		if ( $post_type && is_array( $post_type_custom_instructions ) && ! empty( $post_type_custom_instructions[ $post_type ] ) ) {
			$this_post_type_custom_instructions = ai4seo_normalize_custom_instructions_value( $post_type_custom_instructions[ $post_type ] );

			if ( '' !== $this_post_type_custom_instructions ) {
				$custom_instructions['post_type'] = array(
					'identifier'   => sanitize_key( $post_type ),
					'label'        => ai4seo_get_post_type_translation( $post_type, true ),
					'instructions' => $this_post_type_custom_instructions,
				);
			}
		}

		// Resolve post-level instructions through the shared override helper used by manual and autopilot generation.
		$post_custom_instructions = ai4seo_get_generation_entry_custom_instructions(
			$post_id,
			AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY,
			$entry_custom_instructions
		);

		if ( '' !== $post_custom_instructions ) {
			$custom_instructions['post'] = array(
				'id'           => $post_id,
				'instructions' => $post_custom_instructions,
			);
		}

		return $custom_instructions;
	}

	if ( 'attachment_attributes' === $generation_type ) {
		// Attachment attribute generations use the media-attribute setting plus attachment-level postmeta.
		$attachment_attributes_custom_instructions = ai4seo_normalize_custom_instructions_value( ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS ) );

		if ( '' !== $attachment_attributes_custom_instructions ) {
			$custom_instructions['attachment_attributes'] = $attachment_attributes_custom_instructions;
		}

		// Resolve attachment-level instructions through the shared override helper used by manual and autopilot generation.
		$attachment_custom_instructions = ai4seo_get_generation_entry_custom_instructions(
			$post_id,
			AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY,
			$entry_custom_instructions
		);

		if ( '' !== $attachment_custom_instructions ) {
			$custom_instructions['attachment'] = array(
				'id'           => $post_id,
				'instructions' => $attachment_custom_instructions,
			);
		}
	}

	return $custom_instructions;
}
