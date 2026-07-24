<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region OUTPUT FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Returns the next post ID from an ordered list of post IDs.
 *
 * @param int   $current_post_id The current post ID.
 * @param array $ordered_post_ids All post IDs in the current list.
 * @return int The next post ID or 0 if no next post ID is available.
 */
function ai4seo_get_next_post_id_from_ordered_post_ids( int $current_post_id, array $ordered_post_ids ): int {
	// Normalize the modal navigation inputs before comparing them with strict array_search().
	$current_post_id  = absint( $current_post_id );
	$ordered_post_ids = array_map( 'absint', $ordered_post_ids );

	// Empty lists and invalid current IDs mean there is no sequential editor target.
	if ( ! $current_post_id || ! $ordered_post_ids ) {
		return 0;
	}

	// Preserve the original list order so list filters and sorting keep controlling editor navigation.
	$current_post_index = array_search( $current_post_id, $ordered_post_ids, true );

	if ( false === $current_post_index || ! isset( $ordered_post_ids[ $current_post_index + 1 ] ) ) {
		return 0;
	}

	return $ordered_post_ids[ $current_post_index + 1 ];
}

// =========================================================================================== \\

/**
 * Returns the shared editor button that saves the current entry and opens the next entry.
 *
 * @param int    $next_post_id         Next post ID.
 * @param string $validation_function  JavaScript validation function name.
 * @param string $open_modal_function  JavaScript modal-opening function name.
 * @param array  $open_modal_arguments Additional JavaScript arguments after the next post ID.
 * @return string HTML.
 */
function ai4seo_get_editor_save_next_button_tag(
	int $next_post_id,
	string $validation_function,
	string $open_modal_function,
	array $open_modal_arguments = array()
): string {
	// Normalize the optional next entry ID so callers can pass raw list-navigation values safely.
	$next_post_id = absint( $next_post_id );

	// Return an empty footer item when the current editor has no following entry in the active list.
	if ( ! $next_post_id ) {
		return '';
	}

	// Limit callback names to simple global JavaScript functions before embedding them into onclick markup.
	$ai4seo_javascript_function_name_pattern = '/^[A-Za-z_][A-Za-z0-9_]*$/';

	if (
		! preg_match( $ai4seo_javascript_function_name_pattern, $validation_function )
		|| ! preg_match( $ai4seo_javascript_function_name_pattern, $open_modal_function )
	) {
		return '';
	}

	// Keep editor modal signatures consistent by always passing the next entry ID as the first argument.
	$open_modal_arguments     = array_merge( array( $next_post_id ), $open_modal_arguments );
	$ai4seo_encoded_arguments = array();

	// Encode each argument as a JavaScript literal so booleans, IDs, and ID arrays preserve their runtime types.
	foreach ( $open_modal_arguments as $ai4seo_open_modal_argument ) {
		$ai4seo_encoded_argument = wp_json_encode( $ai4seo_open_modal_argument );

		if ( false === $ai4seo_encoded_argument ) {
			return '';
		}

		$ai4seo_encoded_arguments[] = $ai4seo_encoded_argument;
	}

	// Reuse the existing save-anything flow while swapping only the success callback target per editor.
	$ai4seo_onclick = 'ai4seo_save_anything(jQuery(this), ' . $validation_function . ', function() { '
		. $open_modal_function . '(' . implode( ', ', $ai4seo_encoded_arguments ) . '); });';

	// Render through the shared button helper so the footer action keeps the existing classes and escaping path.
	return ai4seo_get_button_tag(
		esc_html__( 'Save & edit next', 'ai-for-seo' ),
		'ai4seo-big-button ai4seo-lockable ai4seo-save-button ai4seo-start-inactive',
		$ai4seo_onclick
	);
}

// =========================================================================================== \\

/**
 * Returns the shared editor notice shown when no fields are active.
 *
 * @param string $message      Notice message.
 * @param string $settings_url Plugin settings URL.
 * @return string HTML.
 */
function ai4seo_get_editor_no_active_fields_notice_tag( string $message, string $settings_url ): string {
	$message = trim( $message );

	if ( '' === $message ) {
		return '';
	}

	// Keep the empty-editor notice identical between metadata and media-attribute modals.
	$output  = esc_html( $message );
	$output .= '<br><br>';
	$output .= ai4seo_get_a_tag_icon_button_tag(
		$settings_url,
		'',
		'',
		'gear',
		esc_html__( 'Settings', 'ai-for-seo' ),
		'ai4seo-primary-button'
	);

	return $output;
}

// =========================================================================================== \\

/**
 * Returns display names for editor field identifiers.
 *
 * @param array $field_identifiers Field identifiers.
 * @param array $field_details     Field details keyed by identifier.
 * @return array Field display names.
 */
function ai4seo_get_editor_field_display_names( array $field_identifiers, array $field_details ): array {
	$field_display_names = array();

	// Resolve stored field identifiers through the same detail maps that define editor labels.
	foreach ( $field_identifiers as $field_identifier ) {
		$field_identifier = sanitize_key( (string) $field_identifier );

		if ( '' === $field_identifier ) {
			continue;
		}

		$field_name            = $field_details[ $field_identifier ]['name'] ?? $field_identifier;
		$field_display_names[] = is_scalar( $field_name ) ? (string) $field_name : $field_identifier;
	}

	return $field_display_names;
}

// =========================================================================================== \\

/**
 * Returns the shared editor notice shown when configured fields are inactive.
 *
 * @param array  $field_identifiers Field identifiers.
 * @param array  $field_details     Field details keyed by identifier.
 * @param string $notice_template   Translated notice template with field-list and settings URL placeholders.
 * @param string $settings_url      Plugin settings URL.
 * @return string HTML.
 */
function ai4seo_get_editor_inactive_fields_notice_tag(
	array $field_identifiers,
	array $field_details,
	string $notice_template,
	string $settings_url
): string {
	$field_display_names = ai4seo_get_editor_field_display_names( $field_identifiers, $field_details );

	if ( ! $field_display_names || '' === trim( $notice_template ) ) {
		return '';
	}

	// The wrapper classes match the editor form spacing while the template owns the visible text.
	$output          = "<div class='ai4seo-form-item ai4seo-form-item-flush'>";
		$output     .= "<div class='ai4seo-yellow-message ai4seo-yellow-message-inline-offset'>";
			$output .= sprintf(
				$notice_template,
				'<strong>' . esc_html( implode( ', ', $field_display_names ) ) . '</strong>',
				esc_url( $settings_url )
			);
		$output     .= '</div>';
	$output         .= '</div>';

	return $output;
}

// =========================================================================================== \\

/**
 * Returns the HTML for the edit metadata button
 *
 * @param int   $post_id The post id to get the button for.
 * @param array $all_post_ids all post ids in this current list.
 * @return string The HTML for the button
 */
function ai4seo_get_edit_metadata_button( int $post_id, array $all_post_ids = array() ): string {
	$all_post_ids = ai4seo_deep_sanitize( $all_post_ids, 'absint' );

	return ai4seo_get_icon_button_tag( 'pen-to-square', '', '', 'ai4seo_open_metadata_editor_modal(' . esc_js( $post_id ) . ', false' . ( $all_post_ids ? ', ' . json_encode( $all_post_ids ) : '' ) . ');', AI4SEO_PLUGIN_NAME . ': ' . esc_html__( 'Metadata Editor', 'ai-for-seo' ) );
}

// =========================================================================================== \\

/**
 * Returns the HTML for the edit attachment attributes button
 *
 * @param int   $attachment_post_id The post id to get the button for.
 * @param array $all_attachment_post_ids all post ids in this current list.
 * @return string The HTML for the button
 */
function ai4seo_get_edit_attachment_attributes_button( int $attachment_post_id, array $all_attachment_post_ids = array() ): string {
	$all_attachment_post_ids = ai4seo_deep_sanitize( $all_attachment_post_ids, 'absint' );

	return ai4seo_get_icon_button_tag( 'pen-to-square', '', '', 'ai4seo_open_attachment_attributes_editor_modal(' . esc_js( $attachment_post_id ) . ( $all_attachment_post_ids ? ', ' . json_encode( $all_attachment_post_ids ) : '' ) . ');', AI4SEO_PLUGIN_NAME . ': ' . esc_html__( 'Media Attributes Editor', 'ai-for-seo' ) );
}

// =========================================================================================== \\

/**
 * Returns the HTML for the related media button.
 *
 * @param int    $post_id The post id to get the related media button for.
 * @param string $button_text Optional visible button text.
 * @return string The HTML for the button.
 */
function ai4seo_get_related_attachments_button( int $post_id, string $button_text = '' ): string {
	// Reuse the shared icon-button renderer so post-list and editor buttons stay visually aligned.
	return ai4seo_get_icon_button_tag(
		'image',
		$button_text,
		'',
		'ai4seo_open_related_attachments_modal(' . esc_js( $post_id ) . ');',
		AI4SEO_PLUGIN_NAME . ': ' . esc_html__( 'Related Media', 'ai-for-seo' )
	);
}

// =========================================================================================== \\

/*
function ai4seo_get_current_language() {
	// Read current language with weglot-plugin if it is installed and active
	if (function_exists("weglot_get_current_language")) {
		return weglot_get_current_language();
	}

	// Read current language with WPML-plugin if it is installed and active
	elseif (has_filter("wpml_current_language")) {
		return apply_filters("wpml_current_language", null);
	}

	// Read regular WordPress-language
	else {
		// Get language
		$language = get_locale();

		// Set default language if no language has been found
		if (empty($language)) {
			$language = "en_US";
		}

		// Convert language into simple language-code and return it
		return substr($language, 0, 2);
	}
}*/

// =========================================================================================== \\

/**
 * Generates the content for one accordion-element
 *
 * @param string $headline
 * @param string $content
 * @return string
 */
function ai4seo_get_accordion_element( string $headline, string $content ): string {
	// Generate output.
	$output = "<div class='ai4seo-accordion-holder'>";
		// Add headline to output.
		$output     .= "<div class='card ai4seo-card ai4seo-accordion-headline' onclick='jQuery(\".ai4seo-accordion-content\").hide();jQuery(this).next().show();'>";
			$output .= $headline;
		$output     .= '</div>';

		// Add content to output.
		$output     .= "<div class='card ai4seo-card ai4seo-accordion-content'>";
			$output .= $content;
		$output     .= '</div>';
	$output         .= '</div>';

	return $output;
}

// =========================================================================================== \\

function ai4seo_echo_half_donut_chart_with_headline_and_percentage( $headline, $chart_values, $num_done, $num_total, $posts_table_analysis_state, $post_type ) {
	$ai4seo_percentage_done = round( $num_done / $num_total * 100 );

	// Use a status class so the percentage color stays centralized in the stylesheet.
	if ( $ai4seo_percentage_done < 99 ) {
		$ai4seo_percentage_color_class = 'ai4seo-half-donut-chart-incomplete';
	} else {
		$ai4seo_percentage_color_class = 'ai4seo-half-donut-chart-complete';
	}

	echo "<div class='ai4seo-chart-container'>";
		echo '<h4>';
			ai4seo_echo_wp_kses( $headline );
		echo '</h4>';

		echo "<div class='ai4seo-half-donut-chart-container'>";
			ai4seo_echo_half_donut_chart( $chart_values );

			echo "<div class='ai4seo-half-donut-chart-percentage " . esc_attr( $ai4seo_percentage_color_class ) . "'>";
				echo esc_html( ai4seo_format_number_i18n( $ai4seo_percentage_done ) ) . '%';
			echo '</div>';

			echo "<div class='ai4seo-half-donut-chart-done " . esc_attr( $ai4seo_percentage_color_class ) . "'>";
				ai4seo_echo_wp_kses(
					sprintf(
					/* translators: 1: Number of completed items. 2: Total number of items. */
						esc_html__( '%1$s/%2$s done', 'ai-for-seo' ),
						esc_html( ai4seo_format_number_i18n( $num_done ) ),
						'completed' !== $posts_table_analysis_state ? ai4seo_get_svg_tag( 'gear', '', 'ai4seo-spinning-icon ai4seo-gray-icon' ) : esc_html( ai4seo_format_number_i18n( $num_total ) )
					)
				);
			echo '</div>';

	// Explain WPML's language-expanded media total through the same keyboard-accessible tooltip used elsewhere.
	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) && in_array( $post_type, array( 'attachment', 'media' ), true ) ) {
		$ai4seo_chart_tooltip_trigger_html = sprintf(
			/* translators: %s: Total number of media items. */
			esc_html__( 'Why %1$s?', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $num_total ) ),
		);

		ai4seo_echo_wp_kses(
			ai4seo_get_tooltip_tag(
				$ai4seo_chart_tooltip_trigger_html,
				esc_html__( 'Your images appear on different language versions of your website. Therefore, each image needs to be analyzed for each language separately to ensure optimal SEO performance across all languages.', 'ai-for-seo' ),
				array(
					'holder_tag'        => 'div',
					'holder_css_class'  => 'ai4seo-half-donut-chart-sub-info',
					'trigger_css_class' => 'ai4seo-half-donut-chart-sub-info-trigger',
				)
			)
		);
	}
		echo '</div>';
	echo '</div>';
}

// =========================================================================================== \\

/**
 * Function to output a half donut chart
 *
 * @param array $values Example: [ "done" => ["value" => 10], "missing" => ["value" => 20] ].
 * @return void
 */
function ai4seo_echo_half_donut_chart( array $values ) {
	$total = array_sum( array_column( $values, 'value' ) );

	echo '<svg width="250" height="120" xmlns="http://www.w3.org/2000/svg">';
	$startOffset = -235; // Adjust start position so that it begins to the left.
	foreach ( $values as $type => $info ) {
		$percentage = ( $info['value'] / $total ) * 235;
		$chart_segment_class = 'ai4seo-chart-segment-' . sanitize_html_class( (string) $type );

		// Segment colors live in CSS so dashboard data only needs semantic status keys.
		echo "<circle class='ai4seo-circle ai4seo-chart-segment " . esc_attr( $chart_segment_class ) . "' r='75' cx='125' cy='100' fill='transparent' ";
		echo "stroke-width='20' stroke-dasharray='" . esc_attr( $percentage ) . " 99999' stroke-dashoffset='" . esc_attr( $startOffset ) . "' />";
		$startOffset -= $percentage;
	}
	echo '</svg>';
}

// =========================================================================================== \\

/**
 * Function to output the legend for the half donut chart
 *
 * @param array $values Example: [ "done" => ["value" => 10], "missing" => ["value" => 20] ].
 * @return void
 */
function ai4seo_echo_chart_legend( array $values ) {
	echo '<div class="ai4seo-chart-legend">';

	foreach ( array_keys( $values ) as $type ) {
		$chart_segment_class = 'ai4seo-chart-segment-' . sanitize_html_class( (string) $type );

		// Legend swatches reuse the same semantic segment class as the SVG chart segments.
		echo '<div class="ai4seo-chart-legend-item">';
			echo '<div class="ai4seo-chart-legend-color ai4seo-chart-segment ' . esc_attr( $chart_segment_class ) . '"></div>';
			echo '<div class="ai4seo-chart-legend-text">' . esc_html( ai4seo_get_chart_legend_translation( $type ) ) . '</div>';
		echo '</div>';
	}

	echo '</div>';
}

// =========================================================================================== \\

/**
 * Function to output a money-back-guarantee notice
 *
 * @return void
 */
function ai4seo_output_money_back_guarantee_notice() {
	echo "<div class='ai4seo-money-back-guarantee-notice'>";

		// Portrait.
		/*
		echo "<div class='ai4seo-andre-erbis-portrait'>";
			echo "<img src='" . esc_url(ai4seo_get_assets_images_url("andre-erbis-at-space-codes.webp")) . "' alt='André Erbis @ Space Codes - " . esc_attr__("SEO Expert and Full Stack Developer", "ai-for-seo") . "' />";
		echo "</div>";*/

			// Headline.
		echo "<div class='ai4seo-money-back-guarantee-headline'>";
			echo esc_html__( "Found a better price elsewhere? We'll match it!", 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-money-back-guarantee-quote'>";
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %1$s plugin name, %2$s is a clickable email address */
					__( 'We’re excited for you to experience *%1$s*. If you find a better price elsewhere, simply <a href="%2$s" target="_blank">reach out</a>! We’ll match it.', 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME ),
					esc_attr( AI4SEO_OFFICIAL_CONTACT_URL )
				)
			);
		echo '</div>';

		echo '<br>';

		// Headline.
		echo "<div class='ai4seo-money-back-guarantee-headline'>";
			echo esc_html__( 'We provide a 100% Risk-Free Money-Back Guarantee!', 'ai-for-seo' );
		echo '</div>';

		echo "<div class='ai4seo-money-back-guarantee-quote'>";
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: 1: Number of money-back guarantee days. 2: Support contact link. 3: Percentage to refund. 4: Plugin name */
					__( 'During the first %1$s days after purchasing a subscription (Basic, Pro or Premium) or your first Credits Pack, if *%4$s* isn’t the best fit, simply <a href="%2$s" target="blank">reach out</a>! We’ll happily refund %3$s of your money. No questions asked.', 'ai-for-seo' ),
					ai4seo_format_number_i18n( AI4SEO_MONEY_BACK_GUARANTEE_DAYS ),
					esc_attr( AI4SEO_OFFICIAL_CONTACT_URL ),
					'100%',
					esc_html( AI4SEO_PLUGIN_NAME ),
				)
			);
		echo '</div>';

		echo "<div class='ai4seo-money-back-guarantee-signature'>";
			echo "<img src='" . esc_url( ai4seo_get_assets_images_url( 'andre-erbis-signature.png' ) ) . "' alt='André Erbis @ Space Codes - " . esc_attr__( 'SEO Expert and Full Stack Developer', 'ai-for-seo' ) . "' /><br>";
			echo 'André Erbis @ Space Codes - ' . esc_html__( 'SEO Expert and Full Stack Developer', 'ai-for-seo' );
		echo '</div>';

	echo '</div>';
}

// =========================================================================================== \\

/**
 * Function to output the loading-icon including holder-element
 *
 * @return void
 */
function ai4seo_echo_loading_icon_output() {
	echo "<span class='ai4seo-hidden-loading-icon-holder'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'rotate', __( 'Loading', 'ai-for-seo' ), 'ai4seo-spinning-icon' ) );
	echo '</span>';
}

// =========================================================================================== \\

/**
 * Function to output a link styled as an icon button.
 *
 * @param string $a_href The URL.
 * @param string $a_css_class The CSS class for the a-tag.
 * @param string $a_target The target attribute.
 * @param string $button_icon The Font Awesome icon name.
 * @param string $button_text The text to display.
 * @param string $button_css_class The CSS class for the button.
 * @param string $button_onclick The onclick event for the button.
 * @param string $button_title The title and accessible label for icon-only buttons.
 * @param string $a_rel The rel attribute for the a-tag.
 * @return string HTML
 */
function ai4seo_get_a_tag_icon_button_tag( string $a_href, string $a_css_class = '', string $a_target = '_self', string $button_icon = '', string $button_text = '', string $button_css_class = '', string $button_onclick = '', string $button_title = '', string $a_rel = '' ): string {
	if ( ! $a_href ) {
		// If no href is given, we set the href to "#".
		$a_href = '#';
	}

	$button_css_class = 'ai4seo-button ai4seo-lockable' . ( '' !== $button_css_class ? ' ' . $button_css_class : '' );

	if ( '' === $button_text ) {
		$button_css_class .= ' ai4seo-icon-only-button';
	}

	$a_css_class                  = trim( $a_css_class . ' ' . $button_css_class );
	$ai4seo_link_label_attributes = '';

	if ( '' !== $button_title ) {
		$ai4seo_link_label_attributes = " title='" . esc_attr( $button_title ) . "' aria-label='" . esc_attr( $button_title ) . "'";
	} elseif ( '' !== $button_text ) {
		$ai4seo_link_label_attributes = " title='" . esc_attr( $button_text ) . "'";
	}

	if ( '' !== $button_onclick ) {
		$ai4seo_link_label_attributes .= " onclick='" . esc_attr( $button_onclick ) . "'";
	}

	// External tabs must not keep opener access to the WordPress admin window.
	if ( '_blank' === $a_target && '' === $a_rel ) {
		$a_rel = 'noopener noreferrer';
	}

	if ( '' !== $a_rel ) {
		$ai4seo_link_label_attributes .= " rel='" . esc_attr( $a_rel ) . "'";
	}

	$output = "<a href='" . esc_url( $a_href ) . "' class='" . esc_attr( $a_css_class ) . "' target='" . esc_attr( $a_target ) . "'" . $ai4seo_link_label_attributes . '>';

	if ( '' !== $button_icon ) {
		$output .= ai4seo_get_svg_tag( $button_icon, '', 'ai4seo-button-icon-left' );
	}

	if ( '' !== $button_text ) {
		$output .= '<span>' . $button_text . '</span>';
	}

	$output .= '</a>';

	return $output;
}

// =========================================================================================== \\

function ai4seo_get_contact_us_button( string $a_css_class = '', $button_css_class = '' ): string {
	return ai4seo_get_a_tag_icon_button_tag( AI4SEO_OFFICIAL_CONTACT_URL, $a_css_class, '_blank', 'comments', __( 'Contact us', 'ai-for-seo' ), $button_css_class );
}

// =========================================================================================== \\

function ai4seo_get_button_tag( string $text, string $css_class = '', string $onclick = '' ): string {
	return ai4seo_get_icon_button_tag( '', $text, $css_class, $onclick );
}

// =========================================================================================== \\

/**
 * Returns a native collapsible details/summary block.
 *
 * @param string $summary Label shown as the clickable summary.
 * @param string $content Collapsible content HTML.
 * @param string $css_class Additional root CSS classes.
 * @param string $content_css_class Additional content wrapper CSS classes.
 * @param bool   $is_open Whether the block should render open.
 * @return string HTML.
 */
function ai4seo_get_collapsible_tag( string $summary, string $content, string $css_class = '', string $content_css_class = '', bool $is_open = false ): string {
	// Normalize helper input so notices can pass short labels and prebuilt safe HTML consistently.
	$summary = sanitize_text_field( $summary );
	$content = ai4seo_wp_kses( $content );

	// Keep module classes stable while allowing each caller to append context-specific styling hooks.
	$css_class         = trim( 'ai4seo-collapsible' . ( '' !== $css_class ? ' ' . $css_class : '' ) );
	$content_css_class = trim( 'ai4seo-collapsible-content' . ( '' !== $content_css_class ? ' ' . $content_css_class : '' ) );
	$open_attribute    = $is_open ? ' open="open"' : '';

	// Native details/summary keeps short support sections expandable without adding JavaScript.
	$output          = '<details class="' . esc_attr( $css_class ) . '"' . $open_attribute . '>';
		$output     .= '<summary class="ai4seo-collapsible-summary">';
			$output .= '<span class="ai4seo-collapsible-summary-text">' . esc_html( $summary ) . '</span>';
		$output     .= '</summary>';
		$output     .= '<div class="' . esc_attr( $content_css_class ) . '">';
			$output .= $content;
		$output     .= '</div>';
	$output         .= '</details>';

	return $output;
}

// =========================================================================================== \\

/**
 * Function to output a button tag with icon and text.
 *
 * @param string $icon      The icon name.
 * @param string $text      The text to display.
 * @param string $css_class Additional CSS classes.
 * @param string $onclick   The onclick event.
 * @param string $title     The title attribute.
 *
 * @return string HTML.
 */
function ai4seo_get_icon_button_tag( string $icon, string $text, string $css_class = '', string $onclick = '', string $title = '' ): string {
	// Base classes.
	$css_class = 'ai4seo-button ai4seo-lockable' . ( '' !== $css_class ? ' ' . $css_class : '' );

	// Icon-only handling.
	if ( '' === $text ) {
		$css_class .= ' ai4seo-icon-only-button';
	}

	$css_class = trim( $css_class );

	// Auto title fallback.
	if ( '' === $title && '' !== $text ) {
		$title = $text;
	}

	// Build attributes dynamically.
	$attributes = array(
		'type'  => 'button',
		'class' => $css_class,
	);

	if ( '' !== $onclick ) {
		$attributes['onclick'] = $onclick;
	}

	if ( '' !== $title ) {
		$attributes['title'] = $title;

		if ( '' === $text ) {
			$attributes['aria-label'] = $title;
		}
	}

	// Convert attributes to string.
	$attribute_string = '';
	foreach ( $attributes as $key => $value ) {
		$attribute_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}

	// Build output.
	$output = '<button' . $attribute_string . '>';

	if ( '' !== $icon ) {
		$output .= ai4seo_get_svg_tag( $icon, '', 'ai4seo-button-icon-left' );
	}

	if ( '' !== $text ) {
		$output .= '<span>' . $text . '</span>';
	}

	$output .= '</button>';

	return $output;
}

// =========================================================================================== \\

function ai4seo_get_small_icon_button_tag( string $icon = '', string $text = '', string $css_class = '', string $onclick = '' ): string {
	// default values.
	if ( empty( $css_class ) ) {
		$css_class = 'ai4seo-small-button';
	} else {
		$css_class .= ' ai4seo-small-button';
	}

	return ai4seo_get_icon_button_tag( $icon, $text, $css_class, $onclick );
}

// =========================================================================================== \\

function ai4seo_get_abort_button_tag( string $icon = '', string $text = '', string $css_class = '', string $onclick = '' ): string {
	// default values.
	if ( empty( $text ) ) {
		$text = __( 'Abort', 'ai-for-seo' );
	}

	if ( empty( $css_class ) ) {
		$css_class = 'ai4seo-abort-button';
	} else {
		$css_class .= ' ai4seo-abort-button';
	}

	return ai4seo_get_icon_button_tag( $icon, $text, $css_class, $onclick );
}

// =========================================================================================== \\

function ai4seo_get_modal_close_button_tag( string $text = '', string $css_class = '', string $onclick = '' ): string {
	// default values.
	if ( empty( $text ) ) {
		$text = __( 'Close', 'ai-for-seo' );
	}

	if ( empty( $css_class ) ) {
		$css_class = 'ai4seo-abort-button';
	} else {
		$css_class .= ' ai4seo-abort-button';
	}

	if ( empty( $onclick ) ) {
		$onclick = 'ai4seo_close_modal_by_child(this);';
	} else {
		$onclick .= ' ai4seo_close_modal_by_child(this);';
	}

	return ai4seo_get_button_tag( $text, $css_class, $onclick );
}

// =========================================================================================== \\

function ai4seo_get_submit_button_tag( string $text = '', string $css_class = '', string $onclick = '' ): string {
	// default values.
	if ( empty( $text ) ) {
		$text = __( 'Submit', 'ai-for-seo' );
	}

	if ( empty( $css_class ) ) {
		$css_class = 'ai4seo-submit-button';
	} else {
		$css_class .= ' ai4seo-submit-button';
	}

	return ai4seo_get_button_tag( $text, $css_class, $onclick );
}

// =========================================================================================== \\

/**
 * Returns the shared modal headline tag with the SOOZ logo.
 *
 * @param string $headline     Modal headline text.
 * @param string $logo_variant SOOZ logo variant.
 * @return string HTML.
 */
function ai4seo_get_modal_headline_tag( string $headline, string $logo_variant = 'sooz' ): string {
	// Match the AJAX modal headline shell used by editor and related-media modals.
	$output          = "<div class='ai4seo-modal-headline'>";
		$output     .= "<div class='ai4seo-modal-headline-icon'>";
			$output .= ai4seo_get_sooz_logo_image_tag( $logo_variant );
		$output     .= '</div>';

		$output .= esc_html( $headline );
	$output     .= '</div>';

	return $output;
}

// =========================================================================================== \\

/**
 * Returns the shared modal footer tag for modal action buttons.
 *
 * @param array  $button_tags Button HTML strings.
 * @param string $css_class   Additional footer CSS classes.
 * @return string HTML.
 */
function ai4seo_get_modal_footer_tag( array $button_tags, string $css_class = '' ): string {
	// Normalize optional modal buttons before wrapping them in the shared footer shell.
	$valid_button_tags = array();

	foreach ( $button_tags as $this_button_tag ) {
		if ( ! is_string( $this_button_tag ) || trim( $this_button_tag ) === '' ) {
			continue;
		}

		$valid_button_tags[] = $this_button_tag;
	}

	if ( ! $valid_button_tags ) {
		return '';
	}

	// Keep the footer classes centralized so AJAX modal action rows stay visually aligned.
	$footer_css_class = trim( 'ai4seo-modal-footer ai4seo-buttons-wrapper ' . $css_class );

	// Preserve caller-provided button HTML because existing button helpers already handle escaping.
	$output      = "<div class='" . esc_attr( $footer_css_class ) . "'>";
		$output .= implode( '', $valid_button_tags );
	$output     .= '</div>';

	return $output;
}

// =========================================================================================== \\

/**
 * Function to output a small button text link tag, wrapped in an a-tag
 *
 * @param string $a_href The a href value.
 * @param string $a_css_class The a css class value.
 * @param string $a_target The a target value.
 * @param string $button_icon The button icon value.
 * @param string $button_text The button text value.
 * @param string $button_css_class The button css class value.
 * @param string $button_onclick The button onclick value.
 * @return string HTML
 */
function ai4seo_get_small_a_tag_icon_button_tag( string $a_href, string $a_css_class = '', string $a_target = '_self', string $button_icon = '', string $button_text = '', string $button_css_class = '', string $button_onclick = '' ): string {
	// default values.
	if ( empty( $button_css_class ) ) {
		$button_css_class = 'ai4seo-small-button';
	} else {
		$button_css_class .= ' ai4seo-small-button';
	}

	return ai4seo_get_a_tag_icon_button_tag( $a_href, $a_css_class, $a_target, $button_icon, $button_text, $button_css_class, $button_onclick );
}

// =========================================================================================== \\

/**
 * Retrieve the translation for the different content types
 *
 * @param mixed $post_type The post type value.
 * @param bool  $count_or_plural The count or plural value.
 * @return string The translation
 */
function ai4seo_get_post_type_translation( $post_type, $count_or_plural = false ): string {
	$post_type_original = $post_type;
	$post_type          = strtolower( $post_type );
	$translation        = $post_type_original;

	switch ( $post_type ) {
		case 'post':
		case 'posts':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'posts', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'post', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of posts. */
				$translation = sprintf(
					/* translators: %s: Number of posts. */
					_nx( '%1$s post', '%1$s posts', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'page':
		case 'pages':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'pages', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'page', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of pages. */
				$translation = sprintf(
					/* translators: %s: Number of pages. */
					_nx( '%1$s page', '%1$s pages', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'product':
		case 'products':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'products', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'product', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of products. */
				$translation = sprintf(
					/* translators: %s: Number of products. */
					_nx( '%1$s product', '%1$s products', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'portfolio':
		case 'portfolios':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'portfolios', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'portfolio', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of portfolios. */
				$translation = sprintf(
					/* translators: %s: Number of portfolios. */
					_nx( '%1$s portfolio', '%1$s portfolios', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'attachment':
		case 'attachments':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'attachments', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'attachment', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of attachments. */
				$translation = sprintf(
					/* translators: %s: Number of attachments. */
					_nx( '%1$s attachment', '%1$s attachments', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'media': // not a post type, but useful to have in some situations, as we describe attachments as media for the user.
		case 'medias':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = _n( 'medium', 'media', 2, 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = _n( 'medium', 'media', 1, 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of media items. */
				$translation = sprintf(
					/* translators: %s: Number of media items. */
					_nx( '%1$s medium', '%1$s media', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		case 'media file': // not a post type, but useful to have in some situations, as we describe attachments as media for the user.
		case 'media files':
			// Plural.
			if ( true === $count_or_plural ) {
				$translation = __( 'media files', 'ai-for-seo' );
			}
			// Singular.
			elseif ( false === $count_or_plural ) {
				$translation = __( 'media file', 'ai-for-seo' );
			}
			// Singular or plural with count.
			else {
				/* translators: %s: Number of media files. */
				$translation = sprintf(
					/* translators: %s: Number of media files. */
					_nx( '%1$s media file', '%1$s media files', $count_or_plural, 'noun', 'ai-for-seo' ),
					$count_or_plural
				);
			}
			break;
		default:
			// plural.
			if ( true === $count_or_plural ) {
				// we do not add an "s" to the end of the translation, as it does not work with every language reliably
				// $translation .= "s";.

				// singular.
			} elseif ( false === $count_or_plural ) {
				// nothing to do.

				// singular / plural with a counter.
			} elseif ( is_numeric( $count_or_plural ) ) {
				$translation = $count_or_plural . ' ' . $post_type_original;

				if ( 1 !== $count_or_plural ) {
					// we do not add an "s" to the end of the translation, as it does not work with every language reliably
					// $translation .= "s";.
				}
			}
	}

	return $translation;
}

// =========================================================================================== \\

/**
 * Function that outputs the options for a language selection select field
 *
 * @param string $selected The selected value.
 * @return string The html of the options for the select field
 */
function ai4seo_get_generation_language_select_options_html( $selected = 'auto' ): string {
	$languages    = ai4seo_get_translated_generation_language_options();
	$languages    = array( 'auto' => '- ' . __( 'Automatic', 'ai-for-seo' ) . ' -' ) + $languages;
	$options_html = '';

	foreach ( $languages as $value => $text ) {
		$selected_attribute = ( $selected == $value ) ? ' selected' : '';
		$options_html      .= "<option value='" . esc_attr( $value ) . "'" . esc_attr( $selected_attribute ) . '>' . esc_html( $text ) . '</option>';
	}

	return $options_html;
}

// =========================================================================================== \\

/**
 * Get all available language options for AI generation
 *
 * @return array An array of all available language options this plugin supports for AI generation
 */
function ai4seo_get_translated_generation_language_options(): array {
	// Array of language codes and their corresponding names.
	$languages = array(
		'albanian'             => esc_html__( 'Albanian', 'ai-for-seo' ),
		'arabic'               => esc_html__( 'Arabic', 'ai-for-seo' ),
		'bulgarian'            => esc_html__( 'Bulgarian', 'ai-for-seo' ),
		'chinese'              => esc_html__( 'Chinese (General)', 'ai-for-seo' ),
		'simplified chinese'   => esc_html__( 'Chinese (Simplified)', 'ai-for-seo' ),
		'traditional chinese'  => esc_html__( 'Chinese (Traditional)', 'ai-for-seo' ),
		'croatian'             => esc_html__( 'Croatian', 'ai-for-seo' ),
		'czech'                => esc_html__( 'Czech', 'ai-for-seo' ),
		'danish'               => esc_html__( 'Danish', 'ai-for-seo' ),
		'dutch'                => esc_html__( 'Dutch', 'ai-for-seo' ),
		'american english'     => esc_html__( 'English (America)', 'ai-for-seo' ),
		'british english'      => esc_html__( 'English (Britain)', 'ai-for-seo' ),
		'estonian'             => esc_html__( 'Estonian', 'ai-for-seo' ),
		'finnish'              => esc_html__( 'Finnish', 'ai-for-seo' ),
		'european french'      => esc_html__( 'French (Europe)', 'ai-for-seo' ),
		'canadian french'      => esc_html__( 'French (Canada)', 'ai-for-seo' ),
		'german'               => esc_html__( 'German', 'ai-for-seo' ),
		'greek'                => esc_html__( 'Greek', 'ai-for-seo' ),
		'hebrew'               => esc_html__( 'Hebrew', 'ai-for-seo' ),
		'hindi'                => esc_html__( 'Hindi', 'ai-for-seo' ),
		'hungarian'            => esc_html__( 'Hungarian', 'ai-for-seo' ),
		'icelandic'            => esc_html__( 'Icelandic', 'ai-for-seo' ),
		'indonesian'           => esc_html__( 'Indonesian', 'ai-for-seo' ),
		'italian'              => esc_html__( 'Italian', 'ai-for-seo' ),
		'japanese'             => esc_html__( 'Japanese', 'ai-for-seo' ),
		'korean'               => esc_html__( 'Korean', 'ai-for-seo' ),
		'latvian'              => esc_html__( 'Latvian', 'ai-for-seo' ),
		'lithuanian'           => esc_html__( 'Lithuanian', 'ai-for-seo' ),
		'macedonian'           => esc_html__( 'Macedonian', 'ai-for-seo' ),
		'maltese'              => esc_html__( 'Maltese', 'ai-for-seo' ),
		'norwegian'            => esc_html__( 'Norwegian', 'ai-for-seo' ),
		'polish'               => esc_html__( 'Polish', 'ai-for-seo' ),
		'european portuguese'  => esc_html__( 'Portuguese (Europe)', 'ai-for-seo' ),
		'brazilian portuguese' => esc_html__( 'Portuguese (Brazil)', 'ai-for-seo' ),
		'romanian'             => esc_html__( 'Romanian', 'ai-for-seo' ),
		'russian'              => esc_html__( 'Russian', 'ai-for-seo' ),
		'serbian'              => esc_html__( 'Serbian', 'ai-for-seo' ),
		'slovak'               => esc_html__( 'Slovak', 'ai-for-seo' ),
		'slovenian'            => esc_html__( 'Slovenian', 'ai-for-seo' ),
		'spanish'              => esc_html__( 'Spanish', 'ai-for-seo' ),
		'swedish'              => esc_html__( 'Swedish', 'ai-for-seo' ),
		'thai'                 => esc_html__( 'Thai', 'ai-for-seo' ),
		'turkish'              => esc_html__( 'Turkish', 'ai-for-seo' ),
		'ukrainian'            => esc_html__( 'Ukrainian', 'ai-for-seo' ),
		'vietnamese'           => esc_html__( 'Vietnamese', 'ai-for-seo' ),
	);

	return $languages;
}

// =========================================================================================== \\

/**
 * Retrieve the translation for the different chart-legend-types
 *
 * @param string $legend_identifier
 * @return string
 */
function ai4seo_get_chart_legend_translation( string $legend_identifier ): string {
	$legend_identifier_original = $legend_identifier;
	$legend_identifier          = strtolower( $legend_identifier );

	switch ( $legend_identifier ) {
		case 'done':
			return esc_html__( 'Done', 'ai-for-seo' );
		case 'processing':
			return esc_html__( 'Processing', 'ai-for-seo' );
		case 'missing':
			return esc_html__( 'Missing SEO / Pending', 'ai-for-seo' );
		case 'failed':
			return esc_html__( 'Failed (please check details)', 'ai-for-seo' );
		default:
			return $legend_identifier_original;
	}
}

// =========================================================================================== \\

/**
 * Returns a select-all checkbox with a visible or screen-reader-only label.
 *
 * @param string $target_checkbox_name Name of the entry checkboxes controlled by this checkbox.
 * @param string $label                Visible label, `auto` for the default, or empty for screen-reader-only text.
 * @return string
 */
function ai4seo_get_select_all_checkbox( $target_checkbox_name, $label = 'auto' ): string {
	// Resolve the helper's visible default before distinguishing compact table headers from labeled setting groups.
	if ( 'auto' === $label ) {
		$label = esc_html__( 'Select All / Unselect All', 'ai-for-seo' );
	}

	// Derive both the DOM relationship and the JavaScript target from the same group name so repeated checkbox sets stay scoped.
	$select_all_checkbox_id      = "ai4seo-select-all-{$target_checkbox_name}";
	$input_html                  = "<input type='checkbox' class='ai4seo-select-all-checkbox' data-target='" . esc_attr( $target_checkbox_name ) . "' id='" . esc_attr( $select_all_checkbox_id ) . "'>";
	$is_label_screen_reader_only = empty( $label );

	// Content tables pass an empty label to preserve their narrow header column while retaining an accessible control name.
	if ( $is_label_screen_reader_only ) {
		$label = esc_html__( 'Select All / Unselect All', 'ai-for-seo' );

		return "<label class='screen-reader-text' for='" . esc_attr( $select_all_checkbox_id ) . "'>" . esc_html( $label ) . '</label>' . $input_html;
	}

	// Settings-style callers keep the existing wrapped label so the visible text and checkbox remain one pointer target.
	$output  = "<label class='ai4seo-select-all-checkbox-label ai4seo-form-multiple-inputs' for='" . esc_attr( $select_all_checkbox_id ) . "'>";
	$output .= $input_html;
	$output .= esc_html( $label );
	$output .= '</label>';

	return $output;
}

// =========================================================================================== \\

/**
 * Returns an entry checkbox with a screen-reader label for bulk generation actions.
 *
 * @param string $target_checkbox_name Bulk-selection checkbox name without array brackets.
 * @param int    $entry_id             Post or attachment ID.
 * @param string $entry_title          Entry title shown in the content list.
 * @return string
 */
function ai4seo_get_bulk_generation_queue_entry_checkbox( string $target_checkbox_name, int $entry_id, string $entry_title ): string {
	// Namespace the entry ID by target name so the main Media table and Related Media modal can coexist without duplicate IDs.
	$entry_checkbox_id = "ai4seo-select-{$target_checkbox_name}-{$entry_id}";
	$entry_title       = trim( $entry_title );

	// Keep entries without a stored title distinguishable from otherwise unnamed checkboxes.
	if ( '' === $entry_title ) {
		$entry_title = esc_html__( 'Untitled entry', 'ai-for-seo' );
	}

	// Include the immutable ID because titles can be duplicated across content entries.
	$entry_checkbox_label = sprintf(
		/* translators: %1$s: Entry title. %2$d: Entry ID. */
		esc_html__( 'Select %1$s (ID: %2$d)', 'ai-for-seo' ),
		$entry_title,
		$entry_id
	);

	// Keep the hidden label as a sibling so WordPress screen-reader styles do not also clip the visible checkbox.
	$output  = "<label class='screen-reader-text' for='" . esc_attr( $entry_checkbox_id ) . "'>" . esc_html( $entry_checkbox_label ) . '</label>';
	$output .= "<input type='checkbox' class='ai4seo-bulk-generation-queue-entry-checkbox ai4seo-lockable' name='" . esc_attr( $target_checkbox_name ) . "[]' value='" . esc_attr( $entry_id ) . "' id='" . esc_attr( $entry_checkbox_id ) . "'>";

	return $output;
}

// =========================================================================================== \\

/**
 * Returns stage labels and descriptions for a saved prompt slider setting.
 *
 * The descriptions intentionally combine the user-facing behavior summary with the prompt
 * fragment so the settings page previews the generation guidance sent to RobHub.
 *
 * @param string $setting_name Prompt slider setting name.
 * @return array Slider stages.
 */
function ai4seo_get_prompt_slider_setting_stages( string $setting_name ): array {
	// Length controls are data-driven so the settings UI and save-time quality contract share one range registry.
	if ( ai4seo_is_generation_length_slider_setting( $setting_name ) ) {
		$setting_details = ai4seo_get_generation_length_setting_details( $setting_name );
		$length_stages   = is_array( $setting_details['stages'] ?? null ) ? $setting_details['stages'] : array();
		$stages          = array();

		foreach ( $length_stages as $stage_value => $length_stage ) {
			// Ignore malformed registry entries so one bad field cannot break the complete settings page.
			if ( ! is_array( $length_stage ) ) {
				continue;
			}

			$minimum_length = absint( $length_stage['min-length'] ?? 0 );
			$maximum_length = absint( $length_stage['max-length'] ?? 0 );

			// Only continuous, positive ranges can become meaningful slider labels and prompt targets.
			if ( $minimum_length <= 0 || $maximum_length <= $minimum_length ) {
				continue;
			}

			// Resolve each paid stage independently because Option 4 requires Basic while Option 5 requires Pro.
			$stage_value  = (string) $stage_value;
			$minimum_plan = ai4seo_get_generation_length_stage_minimum_plan( $stage_value );
			$stages[]     = array(
				'value'        => $stage_value,
				'label'        => sprintf(
					/* translators: %1$d: Minimum character count. %2$d: Maximum character count. */
					esc_html__( '%1$d–%2$d', 'ai-for-seo' ),
					$minimum_length,
					$maximum_length
				),
				'description'  => sprintf(
					/* translators: %1$s: Option number. %2$d: Minimum character count. %3$d: Maximum character count. */
					esc_html__( 'Option %1$s targets %2$d to %3$d Unicode characters. SOOZ still prioritizes natural, factual wording and does not add padding merely to reach the minimum.', 'ai-for-seo' ),
					$stage_value,
					$minimum_length,
					$maximum_length
				),
				'minimum_plan' => $minimum_plan,
				'is_locked'    => '' !== $minimum_plan && ! ai4seo_user_can_use_generation_length_stage( $setting_name, $stage_value ),
			);
		}

		return $stages;
	}

	// Keep stage copy beside the setting constants so later prompt wiring can reuse the same intent map.
	switch ( $setting_name ) {
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Image only', 'ai-for-seo' ),
					'description' => esc_html__( 'Avoid contextual framing. Treat usage/page context as language and disambiguation support only. Do not add contextual topics, purposes, names, or page-title details unless directly visible.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could ignore useful page context, names, or topic clues that help identify the image correctly.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use context only with very strong visual support. Add surrounding/page terms only when the image clearly confirms them.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use context when good indicators support it. Blend relevant surrounding/page terms with visible image facts, but let image evidence decide.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Page-framed', 'ai-for-seo' ),
					'description' => esc_html__( 'Use page context as the normal frame. Assume the image is directly related to surrounding content when present; otherwise use page title, excerpt, and first section as primary reference while respecting image evidence and field rules.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Context led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use contextual framing proactively. Let surrounding/page purpose lead the topic frame from simple compatible clues. Visible image evidence still overrides contradictions.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could let page context override weak visual evidence and may connect the image to topics, purposes, or entities too aggressively.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Ignore', 'ai-for-seo' ),
					'description' => esc_html__( 'Avoid filename facts. Ignore the source filename except for technical disambiguation. Never use names, brands, quantities, or product terms from it.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could miss useful product names, people, places, or quantities that are only available in a descriptive file name.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use filename details only with very strong support. Add names, brands, quantities, or product terms only when image/context evidence clearly confirms them.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Supported', 'ai-for-seo' ),
					'description' => esc_html__( 'Use filename details when good indicators support them. Add clear filename entities or quantities only when image evidence or usage context makes them likely.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Likely facts', 'ai-for-seo' ),
					'description' => esc_html__( 'Treat compatible filename facts as likely confirmed. If the filename contains a full name and a person is visible, use it as the confirmed identity; if it contains a product name or brand and aligns with image/context evidence, include it.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Filename led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use filename details proactively. Preserve plausible filename names, product identifiers, brands, and quantities from simple compatible clues unless stronger evidence contradicts them.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could overtrust filenames and preserve upload artifacts, stock-photo labels, or misleading names when they look plausible.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Ignore', 'ai-for-seo' ),
					'description' => esc_html__( 'Ignore old values. Ignore supplied old values for generation. Do not use them as factual source material.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could discard useful existing names, quantities, or context that were manually added before.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Avoid similar only', 'ai-for-seo' ),
					'description' => esc_html__( 'Use old values only to avoid near-duplicate wording. Produce substantially different phrasing without using old values as factual source material.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Supported hints', 'ai-for-seo' ),
					'description' => esc_html__( 'Use old values as supported hints. Consider names, quantities, and recognizable details only when good evidence from content, image, or context supports them.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Conditional mandatory', 'ai-for-seo' ),
					'description' => esc_html__( 'Use old values as reference points for recognizable names or additional information. Ignore non-descriptive old values. Quantitative details such as sizes, dimensions, weights, tall, wide, or high must be included exactly once when they logically describe the subject.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Proactive preserve', 'ai-for-seo' ),
					'description' => esc_html__( 'Preserve old-value details proactively. Keep credible names, quantities, dimensions, and recognizable details when compatible, even from simple supporting clues, while avoiding poor old wording.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could preserve outdated, low-quality, or misleading old details if they appear compatible with the new generation request.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Ignore', 'ai-for-seo' ),
					'description' => esc_html__( 'Do not use the focus keyphrase as a generation requirement. Generate metadata from page content and field rules without forcing keyphrase placement.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could miss important SEO alignment when a clear focus keyphrase has already been defined.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use the focus keyphrase only with strong natural fit. Include it in one primary metadata field only when it reads cleanly and matches the page.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Titles only', 'ai-for-seo' ),
					'description' => esc_html__( 'Use the focus keyphrase in title-oriented metadata fields where natural. Do not force it into descriptions or keywords unless other active rules require it.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Key fields', 'ai-for-seo' ),
					'description' => esc_html__( 'Use the focus keyphrase in key SEO fields. The title should start with it, the description should contain it, and keywords should include it when it fits naturally.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Dominant', 'ai-for-seo' ),
					'description' => esc_html__( 'Let the exact focus keyphrase lead the metadata strategy across applicable SEO fields. Keep wording readable and avoid awkward stuffing.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could force repetitive or awkward phrasing if the exact keyphrase does not fit naturally across fields.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Natural', 'ai-for-seo' ),
					'description' => esc_html__( 'Accessibility/readability first. Prioritize clarity, accessibility, and factual usefulness over SEO terms.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could underuse relevant search terms, especially on pages that need stronger topical SEO signals.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Light', 'ai-for-seo' ),
					'description' => esc_html__( 'Light SEO phrasing. Use light SEO phrasing with one or two relevant search terms when natural.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Balanced SEO. Balance user-friendly language with relevant SEO terms.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Keyword-rich', 'ai-for-seo' ),
					'description' => esc_html__( 'Keyword-rich but readable. Use keyword-rich wording while keeping complete, readable sentences.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Maximum', 'ai-for-seo' ),
					'description' => esc_html__( 'Maximum SEO signal. Maximize SEO signal with dense but natural relevant terms. Do not keyword-stuff.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could make wording feel keyword-heavy or less natural if too many search terms compete for short fields.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_COMMERCIAL_TONE:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'No CTA', 'ai-for-seo' ),
					'description' => esc_html__( 'Avoid calls to action. Keep metadata informational and neutral, with no direct conversion wording.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could reduce conversion intent on sales, service, product, or lead-generation pages.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful fit', 'ai-for-seo' ),
					'description' => esc_html__( 'Use benefit or CTA wording only with very strong page intent. Avoid direct commands unless conversion intent is unmistakable.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use moderate benefit wording where it fits the page. Add a clear CTA only when supported by page intent and field purpose.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Description CTA', 'ai-for-seo' ),
					'description' => esc_html__( 'Use CTA wording in description fields. Generate the browser meta description and Open Graph/Twitter descriptions with a call to action, while keeping titles more informational and respecting product or client-specific exceptions.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'CTA led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use CTA and benefit wording proactively. Apply action-oriented wording across applicable description fields when the page intent supports conversion.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could make metadata sound too sales-focused or add action-oriented language where the page intent is mostly informational.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_SOCIAL_VARIATION:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Close', 'ai-for-seo' ),
					'description' => esc_html__( 'Keep close to browser metadata. Keep Open Graph and Twitter metadata close to browser metadata, with minor wording differences.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could make social previews feel repetitive because Open Graph, Twitter, and browser metadata may stay very similar.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Mild difference', 'ai-for-seo' ),
					'description' => esc_html__( 'Use mild social variation. Make small wording differences while keeping Open Graph, Twitter, and browser metadata closely aligned.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use balanced social variation. Make social values useful on their own and different enough to avoid simple duplication.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Distinct', 'ai-for-seo' ),
					'description' => esc_html__( 'Make social metadata distinct. Twitter title and description must differ from Facebook values, and social values should not simply duplicate browser metadata.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Platform led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use platform-specific angles proactively. Make Open Graph broader or benefit-led and Twitter sharper, concise, and more differentiated.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could drift too far from the browser metadata or overemphasize platform-specific angles that the page does not strongly support.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Ignore', 'ai-for-seo' ),
					'description' => esc_html__( 'Ignore recognizable entities. Do not include names of brands, places, landmarks, companies, products, titles, or people unless custom instructions or the separate celebrity recognition setting explicitly supply them.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could omit useful names for brands, places, products, or people even when context strongly supports them.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use recognizable entities only with very strong evidence. Include names only when explicitly supplied or clearly confirmed by image/context evidence.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Balanced supported entity use. Include supported recognizable names/entities when image, filename, or context makes them likely and relevant.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Enhanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use enhanced entity recognition. Extract supported names from filename, surrounding content, and page content, and include relevant recognizable names and characteristics when applicable and supported.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Entity led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use entity-led wording proactively. Include plausible supported names, entities, and distinctive characteristics from simple compatible clues within field length limits.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could overidentify entities or characteristics from weak clues, especially when filenames or surrounding text are noisy.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE:
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Ignore', 'ai-for-seo' ),
					'description' => esc_html__( 'Avoid website and brand context. Do not use site identity, brand terminology, audience, or positioning unless another instruction explicitly requires it.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could miss useful brand terminology, audience context, or positioning that would make generated text fit the website better.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful fit', 'ai-for-seo' ),
					'description' => esc_html__( 'Use website/brand context only with very strong fit. Apply brand terminology or audience cues only when directly relevant to the subject.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use website/brand context when good indicators support it. Align wording with site terminology and audience without overriding the subject evidence.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Brand aware', 'ai-for-seo' ),
					'description' => esc_html__( 'Use website/brand context as normal supporting context. Let website identity, terminology, and audience shape wording when compatible with content or image evidence.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Brand led', 'ai-for-seo' ),
					'description' => esc_html__( 'Use brand context proactively. Let website identity, audience, terminology, and positioning lead the wording when compatible with facts and field rules.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could overuse brand framing or audience assumptions, especially when the page or image subject should stay more factual.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Visual facts', 'ai-for-seo' ),
					'description' => esc_html__( 'Use neutral visual facts only. Describe what is visibly present and avoid inferred action, purpose, mood, benefit, or intent.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could produce overly literal media attributes and miss useful context, meaning, or user-relevant interpretation.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use a careful media tone. Describe visible details and relationships only when strongly supported, avoiding unsupported action, purpose, or mood.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use a balanced media tone. Describe visible details, composition, and supported actions while keeping image evidence authoritative.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Descriptive', 'ai-for-seo' ),
					'description' => esc_html__(
						'Use a descriptive media tone. Describe what matters to understanding the image: subject, action, setting, readable text, and supported relationships. Do not prioritize generic visual inventory such as colors, background color, shape, size, or layout unless it carries specific meaning.',
						'ai-for-seo'
					),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Contextual', 'ai-for-seo' ),
					'description' => esc_html__(
						'Use a strongly contextual media tone. Frame the subject by its supported role in the surrounding content and what the visible elements are used for. Avoid generic image-description wording and de-emphasize colors, shapes, backgrounds, and composition unless they support the page context or user benefit.',
						'ai-for-seo'
					),
					'note'        => esc_html__( 'This could overinterpret intent, meaning, subject relationships, or character motives when the surrounding context is weak or ambiguous.', 'ai-for-seo' ),
				),
			);

		case AI4SEO_SETTING_METADATA_TONE_VARIANT:
			return array(
				array(
					'value'       => '1',
					'label'       => esc_html__( 'Neutral', 'ai-for-seo' ),
					'description' => esc_html__( 'Plain factual metadata. Use a neutral factual tone. Prefer clear, direct descriptions without persuasion, emotional language, or sales framing.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could make metadata feel plain or less distinctive, especially on pages that need a stronger editorial or benefit-led angle.', 'ai-for-seo' ),
				),
				array(
					'value'       => '2',
					'label'       => esc_html__( 'Careful', 'ai-for-seo' ),
					'description' => esc_html__( 'Use a careful factual tone. Keep metadata clear and readable, with minimal persuasion and only strongly supported claims.', 'ai-for-seo' ),
				),
				array(
					'value'       => '3',
					'label'       => esc_html__( 'Balanced', 'ai-for-seo' ),
					'description' => esc_html__( 'Use a balanced editorial tone. Make metadata polished and useful while staying informational, fact-led, and natural.', 'ai-for-seo' ),
				),
				array(
					'value'       => '4',
					'label'       => esc_html__( 'Intent focused', 'ai-for-seo' ),
					'description' => esc_html__( 'Use intent-focused SEO copywriting. Write natural metadata that helps searchers understand the page, gives search engines a clear topic signal, and avoids keyword stuffing, generic filler, and unsupported claims.', 'ai-for-seo' ),
				),
				array(
					'value'       => '5',
					'label'       => esc_html__( 'Vibrant', 'ai-for-seo' ),
					'description' => esc_html__( 'Use a vibrant metadata tone. Make wording more proactive, expressive, and benefit-forward when supported, while preserving factual accuracy.', 'ai-for-seo' ),
					'note'        => esc_html__( 'This could overstate benefits or make wording feel too promotional if the page content does not strongly support that tone.', 'ai-for-seo' ),
				),
			);
	}

	return array();
}

// =========================================================================================== \\

/**
 * Returns the settings-page description for a saved prompt slider setting.
 *
 * These descriptions explain the purpose of the control, while the selected stage description
 * explains the exact prompt instruction represented by the current value.
 *
 * @param string $setting_name Prompt slider setting name.
 * @return string Setting description HTML.
 */
function ai4seo_get_prompt_slider_setting_description( string $setting_name ): string {
	// Length settings share the required experimental prefix while retaining field-specific guidance.
	if ( ai4seo_is_generation_length_slider_setting( $setting_name ) ) {
		$description_prefix = esc_html__( 'Experimental. Option 2 follows most search engines’ best practices and ensures titles and descriptions fit the designated output areas in search results or link previews.', 'ai-for-seo' );

		switch ( $setting_name ) {
			case AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the page title shown in search results and browser tabs.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the page summary shown beneath the title in search results.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the Open Graph title used in Facebook and compatible link previews.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the Open Graph summary used in Facebook and compatible link previews.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the title used in Twitter/X card previews.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH:
				$field_guidance = esc_html__( 'Choose a shorter or longer target for the summary used in Twitter/X card previews.', 'ai-for-seo' );
				break;
			case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH:
				// Alt text serves an accessibility purpose, so its guidance must not inherit search-preview terminology.
				$description_prefix = esc_html__( 'Experimental. Option 2 reflects widely published accessibility recommendations.', 'ai-for-seo' );
				$field_guidance     = '';
				break;
			default:
				$field_guidance = '';
		}

		// Show one shared pricing CTA only while at least one paid stage is unavailable.
		$upgrade_prompt = '';

		if ( ! ai4seo_user_can_use_generation_length_stage( $setting_name, '4' ) ) {
			$upgrade_prompt = ai4seo_get_subscription_upgrade_prompt_tag( 's', 'generation_length_options' );
		} elseif ( ! ai4seo_user_can_use_generation_length_stage( $setting_name, '5' ) ) {
			$upgrade_prompt = ai4seo_get_subscription_upgrade_prompt_tag( 'm', 'generation_length_options' );
		}

		return trim( $description_prefix . ' ' . $field_guidance . ' ' . $upgrade_prompt );
	}

	// Keep general setting help separate from the selected-stage text shown by the slider component.
	switch ( $setting_name ) {
		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE:
			return esc_html__( 'Controls how strongly surrounding content and page context should shape generated media titles, alt text, captions, and descriptions.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE:
			return esc_html__( 'Controls how strongly the source file name can influence generated media attributes, especially names, brands, products, quantities, and other filename hints.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH:
			return esc_html__( 'Controls how strongly existing metadata should be used as a reference when generating new metadata.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH:
			return esc_html__( 'Controls how strongly existing media attributes should be used as a reference when generating new media attributes.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE:
			return esc_html__( 'Controls how strongly a focus keyphrase should influence generated metadata fields.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY:
			return esc_html__( 'Controls how dense and keyword-oriented generated metadata should be while staying readable.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY:
			return esc_html__( 'Controls how dense and keyword-oriented generated media attributes should be while staying useful and accessible.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_COMMERCIAL_TONE:
			return esc_html__( 'Controls whether generated metadata should stay informational or use stronger benefit and call-to-action wording.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_SOCIAL_VARIATION:
			return esc_html__( 'Controls how different Open Graph and Twitter/X metadata should be from browser metadata and from each other.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION:
			return esc_html__( 'Controls whether recognizable brands, places, landmarks, companies, products, titles, people, and other supported entities should be included in generated media attributes.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE:
			return esc_html__( 'Controls how strongly website and brand context should shape generated metadata wording.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE:
			return esc_html__( 'Controls how strongly website and brand context should shape generated media attribute wording.', 'ai-for-seo' );

		case AI4SEO_SETTING_METADATA_TONE_VARIANT:
			return esc_html__( 'Controls the overall writing tone used for generated metadata.', 'ai-for-seo' );

		case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT:
			return esc_html__( 'Controls whether generated media attributes focus on visual facts, visible actions, practical purpose, or the image role in the page.', 'ai-for-seo' );
	}

	return '';
}

// =========================================================================================== \\

/**
 * Returns a settings-page form item for a saved prompt slider setting.
 *
 * This keeps the settings page compact while preserving the normal save-anything radio workflow.
 *
 * @param string $setting_name Setting name.
 * @param string $setting_label Visible setting label.
 * @param bool   $is_advanced_setting Whether the setting is hidden behind the advanced toggle.
 * @return string Setting form item HTML.
 */
function ai4seo_get_prompt_slider_setting_form_item_tag( string $setting_name, string $setting_label, bool $is_advanced_setting = false ): string {
	// Resolve the setting type once because it controls entitlement fallback and label layout.
	$is_generation_length_setting = ai4seo_is_generation_length_slider_setting( $setting_name );
	$stages                       = ai4seo_get_prompt_slider_setting_stages( $setting_name );

	if ( ! $stages ) {
		return '';
	}

	// Keep saved slider values aligned with the prefixed settings-page input names.
	$setting_input_name      = ai4seo_get_prefixed_input_name( $setting_name );
	$setting_input_value     = ai4seo_get_setting( $setting_name );
	$preserved_setting_value = '';

	// Render the effective fallback while retaining an unavailable paid stage for unrelated form saves.
	if ( $is_generation_length_setting ) {
		$effective_setting_input_value = ai4seo_get_effective_generation_length_stage( $setting_name, $setting_input_value );

		if ( ai4seo_validate_prompt_slider_setting_value( $setting_name, $setting_input_value )
			&& (string) $setting_input_value !== $effective_setting_input_value ) {
			$preserved_setting_value = (string) $setting_input_value;
		}

		$setting_input_value = $effective_setting_input_value;
	}

	// Normalize the source ID once so the visible label and radiogroup reference share the exact token.
	$setting_label_id = sanitize_key( $setting_input_name . '-label' );

	// Fall back to the conservative default if stored/imported data falls outside the slider range.
	if ( ! ai4seo_validate_prompt_slider_setting_value( $setting_name, $setting_input_value ) ) {
		$setting_input_value = AI4SEO_DEFAULT_SETTINGS[ $setting_name ] ?? '1';
	}

	// Apply the existing advanced-settings toggle class to the divider and form row together.
	$advanced_setting_css_class = $is_advanced_setting ? ' ai4seo-is-advanced-setting' : '';
	$output                     = "<hr class='ai4seo-form-item-divider" . esc_attr( $advanced_setting_css_class ) . "'>";
	$output                    .= "<div class='ai4seo-form-item" . esc_attr( $advanced_setting_css_class ) . "'>";
		$output                .= "<span class='ai4seo-form-item-label'>";
			// Prompt slider settings are newly introduced controls, so the badge lives with their shared renderer.
			$output .= "<span class='ai4seo-green-bubble'>" . esc_html__( 'NEW', 'ai-for-seo' ) . '</span> ';
			// Expose the visible setting label as the accessible name source for the nested radiogroup.
			$output .= '<span id="' . esc_attr( $setting_label_id ) . '">' . esc_html( $setting_label ) . '</span>';
		$output     .= '</span>';

		$output .= "<div class='ai4seo-form-item-input-wrapper'>";
			// Render the setting purpose after the selected stage so the stage-specific prompt text stays primary.
			$output .= ai4seo_get_slider_input_tag(
				array(
					'id'                 => $setting_input_name,
					'name'               => $setting_input_name,
					'aria_labelledby'    => $setting_label_id,
					'value'              => (string) $setting_input_value,
					'preserved_value'    => $preserved_setting_value,
					'stages'             => $stages,
					'show_help_tooltip'  => ! $is_generation_length_setting,
					'rotate_long_labels' => ! $is_generation_length_setting,
					'track_background'   => 'linear-gradient(to right, var(--ai4seo-gray), #d63638)',
					'track_thickness'    => '8px',
					'track_opacity'      => '0.5',
				)
			);

			$setting_description = ai4seo_get_prompt_slider_setting_description( $setting_name );

	if ( '' !== $setting_description ) {
		$output     .= "<p class='ai4seo-form-item-description ai4seo-slider-input-setting-description'>";
			$output .= ai4seo_wp_kses( $setting_description );
		$output     .= '</p>';
	}
		$output .= '</div>';
	$output     .= '</div>';

	return $output;
}

// =========================================================================================== \\

/**
 * Sanitizes slider CSS values that are later exposed as inline CSS variables.
 *
 * Slider tracks need caller-defined colors, gradients, and dimensions, but those values should
 * stay limited to simple CSS tokens and functions without URL or declaration-breaking syntax.
 *
 * @param mixed  $css_value Raw CSS value.
 * @param string $fallback Fallback CSS value.
 * @return string Safe CSS value.
 */
function ai4seo_sanitize_slider_css_value( $css_value, string $fallback ): string {
	// Normalize caller input before validating the small CSS value subset used by sliders.
	$css_value = trim( (string) $css_value );

	if ( '' === $css_value ) {
		return $fallback;
	}

	// Reject syntax that could escape the CSS variable context or load external resources.
	if ( preg_match( '/[;{}<>]/', $css_value ) || stripos( $css_value, 'url' ) !== false ) {
		return $fallback;
	}

	// Keep CSS functions such as var() and linear-gradient(), while blocking unknown tokens.
	if ( ! preg_match( '/^[a-zA-Z0-9#(),.%\s_\-+]+$/', $css_value ) ) {
		return $fallback;
	}

	return $css_value;
}

// =========================================================================================== \\

/**
 * Sanitizes slider track opacity values for the track-only visual layer.
 *
 * The helper accepts 0-1 decimals and percentages so callers can pass either 0.5 or 50%.
 *
 * @param mixed  $opacity_value Raw opacity value.
 * @param string $fallback Fallback opacity value.
 * @return string Safe opacity value.
 */
function ai4seo_sanitize_slider_opacity_value( $opacity_value, string $fallback = '1' ): string {
	// Normalize caller input before checking the numeric opacity range.
	$opacity_value = trim( (string) $opacity_value );

	if ( '' === $opacity_value ) {
		return $fallback;
	}

	// Percentages are convenient for UI callers but CSS opacity needs a decimal value.
	$is_percentage = ( substr( $opacity_value, -1 ) === '%' );

	if ( $is_percentage ) {
		$opacity_value = trim( substr( $opacity_value, 0, -1 ) );
	}

	if ( ! is_numeric( $opacity_value ) ) {
		return $fallback;
	}

	$opacity_number = (float) $opacity_value;

	if ( $is_percentage ) {
		$opacity_number = $opacity_number / 100;
	}

	if ( $opacity_number < 0 || $opacity_number > 1 ) {
		return $fallback;
	}

	return rtrim( rtrim( sprintf( '%.3F', $opacity_number ), '0' ), '.' );
}

// =========================================================================================== \\

/**
 * Returns tooltip HTML that lists every slider stage label and description.
 *
 * @param array $stages Sanitized slider stages.
 * @return string Tooltip HTML.
 */
function ai4seo_get_slider_input_stage_help_tooltip_html( array $stages ): string {
	// Collect stage copy into one tooltip so long slider descriptions do not crowd the settings form.
	$tooltip_items = array();

	foreach ( $stages as $this_stage ) {
		if ( ! is_array( $this_stage ) ) {
			continue;
		}

		$this_stage_label       = sanitize_text_field( (string) ( $this_stage['label'] ?? '' ) );
		$this_stage_description = sanitize_text_field( (string) ( $this_stage['description'] ?? '' ) );

		if ( '' === $this_stage_label && '' === $this_stage_description ) {
			continue;
		}

		$this_tooltip_item = '<li>';

		if ( '' !== $this_stage_label ) {
			$this_tooltip_item .= '<strong>' . esc_html( $this_stage_label ) . '</strong>';
		}

		if ( '' !== $this_stage_description ) {
			$this_tooltip_item .= ( '' !== $this_stage_label ? ': ' : '' ) . esc_html( $this_stage_description );
		}

		$this_tooltip_item .= '</li>';
		$tooltip_items[]    = $this_tooltip_item;
	}

	// Empty stage sets should not render an empty tooltip shell.
	if ( ! $tooltip_items ) {
		return '';
	}

	// Return list markup for the existing icon-with-tooltip helper, which owns tooltip positioning.
	return '<ul class="ai4seo-slider-input-help-list">' . implode( '', $tooltip_items ) . '</ul>';
}

// =========================================================================================== \\

/**
 * Returns a staged slider input built from radio controls.
 *
 * The save-anything JavaScript already knows how to collect radio groups. This helper keeps that
 * mechanism intact while adding staged labels, configurable track styling, and descriptions.
 *
 * @param array $args Slider input configuration.
 * @return string Slider HTML.
 */
function ai4seo_get_slider_input_tag( array $args ): string {
	// Require a stable wrapper ID because JavaScript uses it for description binding.
	$input_id = sanitize_key( $args['id'] ?? '' );

	if ( ! $input_id ) {
		return '';
	}

	// Preserve the caller's DOM ID reference while removing markup and control whitespace before escaping.
	$aria_labelledby_value = trim( sanitize_text_field( (string) ( $args['aria_labelledby'] ?? '' ) ) );

	// Normalize layout options so the markup only emits supported CSS modifier classes.
	$input_name  = isset( $args['name'] ) ? sanitize_text_field( (string) $args['name'] ) : '';
	$orientation = sanitize_key( $args['orientation'] ?? 'horizontal' );
	$alignment   = sanitize_key( $args['alignment'] ?? 'left' );

	if ( ! in_array( $orientation, array( 'horizontal', 'vertical' ), true ) ) {
		$orientation = 'horizontal';
	}

	if ( ! in_array( $alignment, array( 'left', 'center', 'right' ), true ) ) {
		$alignment = 'left';
	}

	// Expose track customization through CSS variables, not selector-specific inline styles.
	$track_background   = ai4seo_sanitize_slider_css_value( $args['track_background'] ?? 'var(--ai4seo-gray)', 'var(--ai4seo-gray)' );
	$track_thickness    = ai4seo_sanitize_slider_css_value( $args['track_thickness'] ?? '5px', '5px' );
	$track_length       = ai4seo_sanitize_slider_css_value( $args['track_length'] ?? '100%', '100%' );
	$track_opacity      = ai4seo_sanitize_slider_opacity_value( $args['track_opacity'] ?? '1' );
	$is_persistent      = isset( $args['is_persistent'] ) ? (bool) $args['is_persistent'] : true;
	$rotate_long_labels = isset( $args['rotate_long_labels'] ) ? (bool) $args['rotate_long_labels'] : true;

	// Unnamed sliders cannot be saved reliably, so they are automatically treated as previews.
	if ( '' === $input_name ) {
		$is_persistent = false;
	}

	// Normalize stages up front so rendering and JavaScript can rely on unique radio values.
	$raw_stages = $args['stages'] ?? array();

	if ( ! is_array( $raw_stages ) ) {
		return '';
	}

	$stages            = array();
	$used_stage_values = array();
	$has_long_label    = false;

	foreach ( $raw_stages as $this_stage_index => $this_stage ) {
		if ( ! is_array( $this_stage ) || count( $stages ) >= 10 ) {
			continue;
		}

		$this_stage_value = sanitize_key( (string) ( $this_stage['value'] ?? ( $this_stage_index + 1 ) ) );

		if ( '' === $this_stage_value || isset( $used_stage_values[ $this_stage_value ] ) ) {
			continue;
		}

		$this_stage_label       = sanitize_text_field( (string) ( $this_stage['label'] ?? $this_stage_value ) );
		$this_stage_description = sanitize_text_field( (string) ( $this_stage['description'] ?? '' ) );
		$this_stage_note        = sanitize_text_field( (string) ( $this_stage['note'] ?? '' ) );

		// Prefer a caller-resolved lock state when field-specific entitlement is stricter than the generic plan gate.
		$this_stage_minimum_plan = ai4seo_normalize_plan_identifier( $this_stage['minimum_plan'] ?? '' );
		$this_stage_is_locked    = array_key_exists( 'is_locked', $this_stage )
			? (bool) $this_stage['is_locked']
			: '' !== $this_stage_minimum_plan && ! ai4seo_user_has_active_subscription( $this_stage_minimum_plan );

		if ( '' === $this_stage_label ) {
			$this_stage_label = $this_stage_value;
		}

		if ( $rotate_long_labels && ai4seo_mb_strlen( $this_stage_label ) > 3 ) {
			$has_long_label = true;
		}

		$used_stage_values[ $this_stage_value ] = true;
		$stages[]                               = array(
			'value'        => $this_stage_value,
			'label'        => $this_stage_label,
			'description'  => $this_stage_description,
			'note'         => $this_stage_note,
			'minimum_plan' => $this_stage_minimum_plan,
			'is_locked'    => $this_stage_is_locked,
		);
	}

	// A slider needs at least two valid stages to represent a choice.
	if ( count( $stages ) < 2 ) {
		return '';
	}

	// An unavailable saved stage remains persistent until the user explicitly selects another option.
	$preserved_value = sanitize_key( (string) ( $args['preserved_value'] ?? '' ) );

	if ( ! isset( $used_stage_values[ $preserved_value ] ) ) {
		$preserved_value = '';
	}

	// Fall back to the first valid stage when the provided value is no longer available.
	$selected_value = sanitize_key( (string) ( $args['value'] ?? $stages[0]['value'] ) );

	if ( ! isset( $used_stage_values[ $selected_value ] ) ) {
		$selected_value = $stages[0]['value'];
	}

	// Keep the selected description server-rendered so the control is readable before JS runs.
	$selected_description = '';
	$selected_note        = '';

	foreach ( $stages as $this_stage ) {
		if ( $this_stage['value'] === $selected_value ) {
			$selected_description = $this_stage['description'];
			$selected_note        = $this_stage['note'];
			break;
		}
	}

	$help_tooltip_html = '';

	// Prompt sliders can opt into the full stage list while simpler sliders stay visually unchanged.
	if ( ! empty( $args['show_help_tooltip'] ) ) {
		$help_tooltip_html = ai4seo_get_slider_input_stage_help_tooltip_html( $stages );
	}

	// CSS modifiers keep the helper reusable for horizontal, vertical, persistent, and demo use.
	$slider_css_classes = array(
		'ai4seo-slider-input',
		'ai4seo-slider-input-' . $orientation,
		'ai4seo-slider-input-align-' . $alignment,
	);

	if ( '' !== $help_tooltip_html ) {
		$slider_css_classes[] = 'ai4seo-slider-input-has-help';
	}

	if ( $has_long_label && 'horizontal' === $orientation ) {
		$slider_css_classes[] = 'ai4seo-slider-input-rotate-labels';
	}

	// Inline label layouts need their own modifier because fixed marker-width stages must not wrap numeric ranges.
	if ( ! $rotate_long_labels && 'horizontal' === $orientation ) {
		$slider_css_classes[] = 'ai4seo-slider-input-inline-labels';
	}

	if ( ! $is_persistent ) {
		$slider_css_classes[] = 'ai4seo-slider-input-non-persistent';
	}

	// The fallback radio name preserves native grouping for preview sliders without persistence.
	$radio_group_name          = ( $input_name ) ? $input_name : $input_id . '_slider';
	$wrapper_style             = '--ai4seo-slider-track-background: ' . $track_background . ';'
		. '--ai4seo-slider-track-thickness: ' . $track_thickness . ';'
		. '--ai4seo-slider-track-length: ' . $track_length . ';'
		. '--ai4seo-slider-track-opacity: ' . $track_opacity . ';';
	$non_persistent_attribute  = ( ! $is_persistent ) ? ' data-ai4seo-non-persistent="1"' : '';
	$preserved_value_attribute = ( '' !== $preserved_value ) ? ' data-ai4seo-slider-preserved-value="' . esc_attr( $preserved_value ) . '"' : '';

	// Associate the radiogroup with its external setting label only when the caller supplies a valid ID.
	$aria_labelledby_attribute = '';

	if ( '' !== $aria_labelledby_value ) {
		$aria_labelledby_attribute = ' aria-labelledby="' . esc_attr( $aria_labelledby_value ) . '"';
	}

	// Hide the optional caution note until a stage with note text is selected or previewed.
	$selected_note_hidden_attribute = ( '' === $selected_note ) ? ' hidden="hidden"' : '';

	// Plan badge classes keep stage requirements consistent with the plugin-wide subscription color system.
	$plan_badge_css_classes = array(
		's' => 'ai4seo-plan-badge-basic',
		'm' => 'ai4seo-plan-badge-pro',
		'l' => 'ai4seo-plan-badge-premium',
	);

	// Render labels around real radio inputs so keyboard behavior and future saving stay native.
	$output              = '<div id="' . esc_attr( $input_id ) . '" class="' . esc_attr( implode( ' ', $slider_css_classes ) ) . '" data-ai4seo-slider-input="1"' . $non_persistent_attribute . $preserved_value_attribute . ' style="' . esc_attr( $wrapper_style ) . '">';
		$output         .= '<div class="ai4seo-slider-input-control-wrapper">';
			$output     .= '<div class="ai4seo-slider-input-control">';
				$output .= '<div class="ai4seo-slider-input-track" aria-hidden="true"></div>';
				$output .= '<div class="ai4seo-slider-input-stages" role="radiogroup"' . $aria_labelledby_attribute . ' aria-describedby="' . esc_attr( $input_id . '-description' ) . '">';

	foreach ( $stages as $this_stage_index => $this_stage ) {
		$this_stage_id          = $input_id . '-stage-' . ( $this_stage_index + 1 );
		$is_checked             = ( $this_stage['value'] === $selected_value );
		$this_stage_css_classes = 'ai4seo-slider-input-stage' . ( $is_checked ? ' ai4seo-slider-input-stage-selected' : '' );
		$this_stage_attributes  = '';
		$this_input_attributes  = '';
		$this_stage_tag_name    = 'label';
		$this_stage_for         = ' for="' . esc_attr( $this_stage_id ) . '"';

		// Locked stages stay in the comparison layout but become explicit upgrade actions instead of labels.
		if ( $this_stage['is_locked'] ) {
			// Use the configured plan name in the action label so Pro-only stages are announced accurately.
			$minimum_plan_name = ai4seo_get_plan_name( $this_stage['minimum_plan'] );

			// Present locked stages as upgrade buttons because activating them opens a modal instead of selecting a radio.
			$locked_stage_aria_label = sprintf(
				/* translators: %1$s: Generation length option label. %2$s: Required plan name. */
				__( '%1$s. %2$s plan required. Open upgrade options.', 'ai-for-seo' ),
				$this_stage['label'],
				$minimum_plan_name
			);

			$this_stage_css_classes .= ' ai4seo-slider-input-stage-locked';
			$this_stage_attributes  .= ' tabindex="0" role="button" aria-label="' . esc_attr( $locked_stage_aria_label ) . '" data-ai4seo-slider-locked="1" data-ai4seo-required-plan="' . esc_attr( $this_stage['minimum_plan'] ) . '"';
			$this_input_attributes  .= ' disabled="disabled" hidden="hidden" aria-hidden="true"';
			$this_stage_tag_name     = 'span';
			$this_stage_for          = '';
		}

		$output     .= '<' . $this_stage_tag_name . ' class="' . esc_attr( $this_stage_css_classes ) . '"' . $this_stage_for . ' title="' . esc_attr( $this_stage['description'] ) . '"' . $this_stage_attributes . '>';
			$output .= '<input type="radio" id="' . esc_attr( $this_stage_id ) . '" name="' . esc_attr( $radio_group_name ) . '" value="' . esc_attr( $this_stage['value'] ) . '" class="ai4seo-slider-input-radio"' . ( $is_checked ? ' checked="checked"' : '' ) . $this_input_attributes . $non_persistent_attribute . ' data-ai4seo-slider-description="' . esc_attr( $this_stage['description'] ) . '" data-ai4seo-slider-note="' . esc_attr( $this_stage['note'] ) . '" aria-describedby="' . esc_attr( $input_id . '-description' ) . '">';
			$output .= '<span class="ai4seo-slider-input-marker" aria-hidden="true"></span>';
			$output .= '<span class="ai4seo-slider-input-label">' . esc_html( $this_stage['label'] ) . '</span>';

		// Resolve the badge class independently from the stage markup assignment group.
		$plan_badge_css_class = $plan_badge_css_classes[ $this_stage['minimum_plan'] ] ?? '';

		if ( '' !== $plan_badge_css_class ) {
			// Keep the minimum plan visible for both locked and currently entitled users.
			$output .= '<span class="ai4seo-plan-badge ' . esc_attr( $plan_badge_css_class ) . ' ai4seo-slider-input-stage-plan-badge">' . esc_html( ai4seo_get_plan_name( $this_stage['minimum_plan'] ) ) . '</span>';
		}
		$output .= '</' . $this_stage_tag_name . '>';
	}

				$output .= '</div>';
			$output     .= '</div>';

	if ( '' !== $help_tooltip_html ) {
		$output     .= '<span class="ai4seo-slider-input-help">';
			$output .= ai4seo_get_icon_with_tooltip_tag( $help_tooltip_html, 'ai4seo-slider-input-help-icon' );
		$output     .= '</span>';
	}
		$output     .= '</div>';
		$output     .= '<div id="' . esc_attr( $input_id . '-description' ) . '" class="ai4seo-slider-input-selected-description" aria-live="polite">';
			$output .= '<strong>' . esc_html__( 'Selected:', 'ai-for-seo' ) . '</strong> <span class="ai4seo-slider-input-selected-description-text">' . esc_html( $selected_description ) . '</span>';
			$output .= '<p class="ai4seo-slider-input-selected-note"' . $selected_note_hidden_attribute . '><strong>' . esc_html__( 'Note', 'ai-for-seo' ) . '</strong>: <span class="ai4seo-slider-input-selected-note-text">' . esc_html( $selected_note ) . '</span></p>';
		$output     .= '</div>';
	$output         .= '</div>';

	return $output;
}

// =========================================================================================== \\

/**
 * Returns the custom SOOZ list bulk queue action controls.
 *
 * @param string $context Queue context.
 * @param string $target_checkbox_name Checkbox field name without [].
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param array  $args Optional control arguments.
 * @return string HTML.
 */
function ai4seo_get_bulk_generation_queue_action_controls( string $context, string $target_checkbox_name, string $active_status_filter = 'all', array $args = array() ): string {
	$context              = sanitize_key( $context );
	$target_checkbox_name = sanitize_key( $target_checkbox_name );
	$active_status_filter = sanitize_key( $active_status_filter );
	// List location lets shared attachment.php controls omit modal-only actions inside embedded media pickers.
	$list_location = sanitize_key( (string) ( $args['list_location'] ?? 'main' ) );

	if ( ! ai4seo_is_bulk_generation_queue_context( $context ) || ! $target_checkbox_name ) {
		return '';
	}

	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'custom', $active_status_filter, $context, $list_location );
	$bulk_action_select_id         = "ai4seo-{$context}-bulk-generation-queue-action";

	// Reuse the same action metadata as the select options so SOOZ table help text stays aligned.
	$bulk_action_help_icon_html = ai4seo_get_bulk_generation_queue_action_help_icon_html( 'custom', $active_status_filter, $context, $list_location );

	$output      = "<form class='ai4seo-bulk-generation-queue-action-form' method='post' action=''>";
		$output .= "<input type='hidden' class='ai4seo-bulk-generation-queue-context' value='" . esc_attr( $context ) . "'>";
		$output .= "<input type='hidden' class='ai4seo-bulk-generation-queue-checkbox-name' value='" . esc_attr( $target_checkbox_name ) . "'>";
		// Keep the server-side custom action validation aligned with the dropdown that was rendered for this filter.
		$output     .= "<input type='hidden' class='ai4seo-bulk-generation-queue-active-status-filter' value='" . esc_attr( $active_status_filter ) . "'>";
		$output     .= "<label class='screen-reader-text' for='" . esc_attr( $bulk_action_select_id ) . "'>" . esc_html__( 'Bulk action', 'ai-for-seo' ) . '</label>';
		$output     .= "<select id='" . esc_attr( $bulk_action_select_id ) . "' name='ai4seo_bulk_generation_queue_action' class='ai4seo-bulk-generation-queue-action-select ai4seo-textfield ai4seo-lockable'>";
			$output .= "<option value=''>" . esc_html__( 'Bulk actions', 'ai-for-seo' ) . '</option>';

	foreach ( array_keys( $bulk_generation_queue_actions ) as $this_action_identifier ) {
		$this_action_label = ai4seo_get_bulk_generation_queue_action_label( $this_action_identifier, false );
		$output           .= "<option value='" . esc_attr( $this_action_identifier ) . "'>" . esc_html( $this_action_label ) . '</option>';
	}
		$output .= '</select>';
		$output .= $bulk_action_help_icon_html;
		$output .= ai4seo_get_button_tag(
			esc_html__( 'Apply', 'ai-for-seo' ),
			'ai4seo-bulk-generation-queue-action-submit',
			''
		);
	$output     .= '</form>';

	return $output;
}

// =========================================================================================== \\

/**
 * Returns generated-data counts by post type for reset controls.
 *
 * @return array Generated-data counts by post type.
 */
function ai4seo_get_generated_data_reset_post_type_counts(): array {
	$generation_status_summary       = ai4seo_read_generation_status_summary( true, true );
	$generated_data_post_type_counts = array();
	$generated_data_option_names     = array(
		AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
		AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);

	foreach ( $generated_data_option_names as $this_generated_data_option_name ) {
		if ( ! isset( $generation_status_summary[ $this_generated_data_option_name ] )
			|| ! is_array( $generation_status_summary[ $this_generated_data_option_name ] ) ) {
			continue;
		}

		foreach ( $generation_status_summary[ $this_generated_data_option_name ] as $this_post_type => $this_num_generated_entries ) {
			$this_post_type             = sanitize_key( $this_post_type );
			$this_num_generated_entries = absint( $this_num_generated_entries );

			if ( ! $this_post_type || ! $this_num_generated_entries ) {
				continue;
			}

			if ( ! isset( $generated_data_post_type_counts[ $this_post_type ] ) ) {
				$generated_data_post_type_counts[ $this_post_type ] = 0;
			}

			$generated_data_post_type_counts[ $this_post_type ] += $this_num_generated_entries;
		}
	}

	return $generated_data_post_type_counts;
}

// =========================================================================================== \\

/**
 * Returns the reset UI label for a generated-data post type.
 *
 * @param string $post_type The post type.
 * @return string Post type label.
 */
function ai4seo_get_generated_data_reset_post_type_label( string $post_type ): string {
	$post_type_label_identifier = ( 'attachment' === $post_type ) ? 'media files' : $post_type;
	$post_type_label            = ai4seo_get_post_type_translation( $post_type_label_identifier, true );

	return ucfirst( $post_type_label );
}

// =========================================================================================== \\

/**
 * Returns generated-data reset checkboxes by post type.
 *
 * @param string $input_name Input name without array brackets.
 * @param string $input_class Additional input CSS classes.
 * @param string $id_prefix Input ID prefix.
 * @param array  $generated_data_post_type_counts Optional preloaded counts by post type.
 * @param bool   $checked Whether the checkboxes should be checked initially.
 * @return string Checkbox HTML.
 */
function ai4seo_get_generated_data_reset_post_type_checkboxes_html( string $input_name, string $input_class, string $id_prefix, array $generated_data_post_type_counts = array(), bool $checked = false ): string {
	if ( ! $generated_data_post_type_counts ) {
		$generated_data_post_type_counts = ai4seo_get_generated_data_reset_post_type_counts();
	}

	if ( ! $generated_data_post_type_counts ) {
		return '';
	}

	$input_name      = sanitize_key( $input_name );
	$input_class     = trim( $input_class );
	$id_prefix       = sanitize_html_class( $id_prefix );
	$checkboxes_html = '';

	foreach ( $generated_data_post_type_counts as $this_post_type => $this_num_generated_entries ) {
		$this_post_type             = sanitize_key( $this_post_type );
		$this_num_generated_entries = absint( $this_num_generated_entries );

		if ( ! $this_post_type || ! $this_num_generated_entries ) {
			continue;
		}

		$this_input_id          = sanitize_html_class( $id_prefix . '-' . $this_post_type );
		$this_input_classes     = trim( 'ai4seo-generated-data-reset-post-type-checkbox ' . $input_class );
		$this_post_type_label   = ai4seo_get_generated_data_reset_post_type_label( $this_post_type );
		$this_checked_attribute = $checked ? ' checked' : '';

		$checkboxes_html     .= "<div class='ai4seo-form-multiple-inputs'>";
			$checkboxes_html .= "<input type='checkbox' id='" . esc_attr( $this_input_id ) . "' name='" . esc_attr( $input_name ) . "[]' value='" . esc_attr( $this_post_type ) . "' class='" . esc_attr( $this_input_classes ) . "'" . $this_checked_attribute . ' />';
			$checkboxes_html .= "<label for='" . esc_attr( $this_input_id ) . "'>" . esc_html( $this_post_type_label ) . ' (' . esc_html( ai4seo_format_number_i18n( $this_num_generated_entries ) ) . ')</label>';
		$checkboxes_html     .= '</div>';
	}

	if ( $checkboxes_html ) {
		$note_context_class = sanitize_html_class( $id_prefix . '-full-reset-note' );
		$note_css_class     = $checked
			? 'ai4seo-generated-data-reset-full-reset-note ' . $note_context_class
			: 'ai4seo-generated-data-reset-full-reset-note ' . $note_context_class . ' ai4seo-display-none';

		$checkboxes_html     .= "<div class='" . esc_attr( $note_css_class ) . "' role='status'>";
			$checkboxes_html .= esc_html__( 'All generated data will be removed because every entry type is selected.', 'ai-for-seo' );
		$checkboxes_html     .= '</div>';
	}

	return $checkboxes_html;
}

// =========================================================================================== \\

/**
 * Returns generated-data reset post type options for JavaScript-rendered controls.
 *
 * @param array $generated_data_post_type_counts Optional preloaded counts by post type.
 * @return string Hidden options HTML.
 */
function ai4seo_get_generated_data_reset_post_type_options_html( array $generated_data_post_type_counts = array() ): string {
	if ( ! $generated_data_post_type_counts ) {
		$generated_data_post_type_counts = ai4seo_get_generated_data_reset_post_type_counts();
	}

	if ( ! $generated_data_post_type_counts ) {
		return '';
	}

	$options_html = '';

	foreach ( $generated_data_post_type_counts as $this_post_type => $this_num_generated_entries ) {
		$this_post_type             = sanitize_key( $this_post_type );
		$this_num_generated_entries = absint( $this_num_generated_entries );

		if ( ! $this_post_type || ! $this_num_generated_entries ) {
			continue;
		}

		$this_post_type_label            = ai4seo_get_generated_data_reset_post_type_label( $this_post_type );
		$this_post_type_label_with_count = $this_post_type_label . ' (' . ai4seo_format_number_i18n( $this_num_generated_entries ) . ')';

		$options_html .= "<span class='ai4seo-generated-data-reset-post-type-option' data-post-type='" . esc_attr( $this_post_type ) . "' data-label='" . esc_attr( $this_post_type_label_with_count ) . "'></span>";
	}

	return $options_html;
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of the terms of service in a readable format
 *
 * @return string A readable format of the accepted timestamp
 */
function ai4seo_get_tos_toc_and_pp_accepted_time_output(): string {
	return ai4seo_get_environmental_variable_accepted_time_output( AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME );
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of the enhanced reporting agreement
 *
 * @return string A readable format of the accepted timestamp
 */
function ai4seo_get_enhanced_reporting_accepted_time_output(): string {
	return ai4seo_get_environmental_variable_accepted_time_output( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME );
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of a specific environmental variable in a readable format
 *
 * @param mixed $environmental_variable_name The environmental variable name value.
 * @return string A readable format of the accepted timestamp of the terms of service
 */
function ai4seo_get_environmental_variable_accepted_time_output( $environmental_variable_name ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 212334759, 'Prevented loop', true );
		return '';
	}

	$accepted_time = ai4seo_read_environmental_variable( $environmental_variable_name );

	$content = '';

	if ( $accepted_time ) {
		$readable_accepted_time = ai4seo_format_unix_timestamp( $accepted_time );
		$content               .= ai4seo_get_svg_tag( 'square-check', '', 'ai4seo-16x16-icon ai4seo-dark-green-icon' ) . ' ';
		/* translators: %s: Accepted time in a human readable format. */
		$content .= sprintf( esc_html__( 'Accepted on %s.', 'ai-for-seo' ), $readable_accepted_time );
	} else {
		// $content .= ai4seo_get_svg_tag("square-xmark", "", "ai4seo-16x16-icon ai4seo-red-icon") . " ";
		// $content .= esc_html__("Not accepted yet.", "ai-for-seo");
	}
	return $content;
}

// =========================================================================================== \\

/**
 * Function to check if the SEO Autopilot is running at least X amount of seconds
 *
 * @param int $duration The duration in seconds
 * @return bool True if the SEO Autopilot is running at least X amount of seconds
 */
function ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago( int $duration = 300 ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 530953976, 'Prevented loop', true );
		return false;
	}

	$seo_autopilot_start_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME );

	if ( ! $seo_autopilot_start_time ) {
		return false;
	}

	return ( time() - $seo_autopilot_start_time ) >= $duration;
}

// =========================================================================================== \\

function ai4seo_echo_cost_breakdown_section( $credits_percentage, bool $can_afford_at_least_one_generation = true ) {
	$active_meta_tags_names                                 = ai4seo_get_active_meta_tags_names();
	$active_attachment_attribute_names                      = ai4seo_get_active_attachment_attributes_names();
	$metadata_credits_cost_per_post                         = ai4seo_calculate_metadata_credits_cost_per_post();
	$attachment_attributes_credits_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
	$is_payg_enabled                                        = (bool) ai4seo_get_setting( AI4SEO_SETTING_PAYG_ENABLED );
	$payg_stripe_price_id                                   = ai4seo_deep_sanitize( ai4seo_get_setting( AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID ) );
	$credits_packs                         = ai4seo_get_credits_packs();
	$payg_credits_pack_amount              = (int) ( $credits_packs[ $payg_stripe_price_id ]['credits_amount'] ?? 0 );
	$robhub_subscription                   = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );
	$robhub_subscription_plan              = $robhub_subscription['plan'] ?? 'free';
	$robhub_subscription_plan_name         = ai4seo_get_plan_name( $robhub_subscription_plan );
	$robhub_subscription_end_date_and_time = $robhub_subscription['subscription_end'] ?? false;
	$robhub_subscription_end_timestamp     = $robhub_subscription_end_date_and_time
		? strtotime( $robhub_subscription_end_date_and_time ) : 0;
	$has_active_subscription               = ( 'free' !== $robhub_subscription_plan ) && $robhub_subscription_end_timestamp > time();
	$green_check_icon                      = ai4seo_get_svg_tag( 'check', '', 'ai4seo-dark-green-icon ai4seo-upscaled-inline-icon' ) . ' ';
	$red_x_icon                            = ai4seo_get_svg_tag( 'xmark', '', 'ai4seo-red-icon ai4seo-upscaled-inline-icon' ) . ' ';

	echo "<div class='ai4seo-centered-inline-content'>";
		// echo '<h4>';
		// echo esc_html__("Cost Breakdown", "ai-for-seo");
		// echo "</h4>";.

		echo '<ul>';
			echo '<li>';
	if ( $metadata_credits_cost_per_post ) {
		ai4seo_echo_wp_kses(
			sprintf(
			/* translators: %s: Number of credits per piece of metadata. */
				$green_check_icon . __( 'Metadata per page/post/etc.: %s', 'ai-for-seo' ),
				"<span class='ai4seo-credits-usage-badge'><strong>" . esc_html( ai4seo_format_number_i18n( $metadata_credits_cost_per_post ) ) . '</strong> '
								. esc_html( _n( 'Credit', 'Credits', $metadata_credits_cost_per_post, 'ai-for-seo' ) ) . '</span>'
			)
		);

		// Keep identical tooltip content while giving this shared icon a metadata-specific accessible name.
		ai4seo_echo_wp_kses(
			ai4seo_get_icon_with_tooltip_tag(
				sprintf(
					/* translators: %s: List of active metadata tags. */
					__( 'Your current generation setup: %s', 'ai-for-seo' ),
					esc_html( implode( ', ', $active_meta_tags_names ) )
				),
				'',
				'circle-question',
				__( 'Metadata', 'ai-for-seo' ) . ': ' . __( 'Help', 'ai-for-seo' )
			)
		);
	} else {
		ai4seo_echo_wp_kses(
			sprintf(
				__( 'No meta tags are currently active.', 'ai-for-seo' )
			)
		);
	}
			echo '</li>';
				echo '<li>';
	if ( $attachment_attributes_credits_cost_per_attachment_post ) {
		ai4seo_echo_wp_kses(
			sprintf(
			/* translators: %s: Number of credits per image attribute set. */
				$green_check_icon . __( 'Media attributes per image: %s', 'ai-for-seo' ),
				"<span class='ai4seo-credits-usage-badge'><strong>" . esc_html( ai4seo_format_number_i18n( $attachment_attributes_credits_cost_per_attachment_post ) ) . '</strong> '
								. esc_html( _n( 'Credit', 'Credits', $attachment_attributes_credits_cost_per_attachment_post, 'ai-for-seo' ) ) . '</span>'
			)
		);

		// Keep identical tooltip content while giving this shared icon a media-specific accessible name.
		ai4seo_echo_wp_kses(
			ai4seo_get_icon_with_tooltip_tag(
				sprintf(
					/* translators: %s: List of active media attributes. */
					__( 'Your current generation setup: %s', 'ai-for-seo' ),
					esc_html( implode( ', ', $active_attachment_attribute_names ) )
				),
				'',
				'circle-question',
				__( 'Media attributes', 'ai-for-seo' ) . ': ' . __( 'Help', 'ai-for-seo' )
			)
		);
	} else {
		ai4seo_echo_wp_kses(
			sprintf(
				__( 'No media attributes are currently active.', 'ai-for-seo' )
			)
		);
	}
			echo '</li>';

	if ( ! $can_afford_at_least_one_generation ) {
		echo "<li class='ai4seo-red-message'>";
			ai4seo_echo_wp_kses(
				sprintf(
					$red_x_icon . __( 'Your Credits balance is insufficient to cover any additional AI generations.', 'ai-for-seo' )
				)
			);
		echo '</li>';
	} elseif ( $credits_percentage < 100 ) {
		echo "<li class='ai4seo-red-message'>";
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %s: Percentage of remaining coverage. */
					$red_x_icon . __( 'Your Credits balance only covers approximately <strong>%1$s%%</strong> of the remaining pages / media files.', 'ai-for-seo' ),
					esc_html( ai4seo_format_number_i18n( $credits_percentage ) )
				)
			);
		echo '</li>';
	}

	if ( $is_payg_enabled && $payg_credits_pack_amount > 0 ) {
		echo "<li class='ai4seo-green-message'>";
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %s: PAYG refill credits amount. */
					$green_check_icon . __( 'Pay-As-You-Go is active with a refill size of <strong>%s Credits</strong>.', 'ai-for-seo' ),
					esc_html( ai4seo_format_number_i18n( $payg_credits_pack_amount, 0 ) )
				)
			);
		echo '</li>';
	}

	if ( $has_active_subscription ) {
		echo "<li class='ai4seo-green-message'>";
			ai4seo_echo_wp_kses(
				sprintf(
				/* translators: %s: Subscription plan name. */
					$green_check_icon . __( 'Your active subscription plan: <strong>%s</strong>.', 'ai-for-seo' ),
					esc_html( $robhub_subscription_plan_name )
				)
			);
		echo '</li>';
	}

	if ( ! $is_payg_enabled && ! $has_active_subscription && $credits_percentage < 100 ) {
		echo "<li class='ai4seo-red-message'>";
			ai4seo_echo_wp_kses( $red_x_icon . __( 'No active subscription or Pay-As-You-Go refill is currently enabled.', 'ai-for-seo' ) );
		echo '</li>';
	}
		echo '</ul>';
	echo '</div>';
}

// =========================================================================================== \\

function ai4seo_echo_current_discount() {
	$ai4seo_current_discount = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT );

	if ( ! $ai4seo_current_discount ) {
		return '';
	}

	// create green bubble with gift icon and discount percentage.
	echo "<div class='ai4seo-green-bubble ai4seo-discount-available-message'>";

		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'gift', esc_attr__( 'Discount available!', 'ai-for-seo' ) ) );
		echo ' ';

		// with countdown.
	if ( isset( $ai4seo_current_discount['expire_in'] ) && $ai4seo_current_discount['expire_in'] > 0 ) {
		printf(
			/* translators: 1: Discount percentage. 2: Remaining time. */
			esc_html__( '%1$s%% discount available (time left: %2$s)', 'ai-for-seo' ),
			(int) $ai4seo_current_discount['percentage'],
			"<span class='ai4seo-countdown ai4seo-ignore-during-dashboard-refresh' data-time-left='" . esc_attr( $ai4seo_current_discount['expire_in'] ) . "' data-trigger='ai4seo_refresh_robhub_account'>" . esc_html( ai4seo_format_seconds_to_hhmmss_or_days_hhmmss( $ai4seo_current_discount['expire_in'] ) ) . '</span>'
		);
		// without countdown.
	} else {
		printf(
			/* translators: %s: Discount percentage. */
			esc_html__( '%1$s%% discount available', 'ai-for-seo' ),
			(int) $ai4seo_current_discount['percentage']
		);
	}
	echo '</div>';
}

// =========================================================================================== \\

function ai4seo_get_voucher_code_output( $voucher_code ): string {
	$voucher_code_output              = "<div class='ai4seo-voucher-code-wrapper'>";
		$voucher_code_output         .= "<div class='ai4seo-voucher-code'>" . esc_html( $voucher_code );
				$voucher_code_output .= "<button class='ai4seo-button ai4seo-secondary-button ai4seo-icon-only-button ai4seo-copy-voucher-code-button ai4seo-copy-to-clipboard' data-clipboard-text='" . esc_attr( $voucher_code ) . "' title='" . esc_attr__( 'Copy voucher code', 'ai-for-seo' ) . "'>";
				$voucher_code_output .= ai4seo_get_svg_tag( 'copy' );
			$voucher_code_output     .= '</button>';
			$voucher_code_output     .= "<div class='ai4seo-copy-voucher-code-tooltip ai4seo-copied-to-clipboard'>👍 " . esc_html__( 'Copied!', 'ai-for-seo' ) . '</div>';
		$voucher_code_output         .= '</div>';
	$voucher_code_output             .= '</div>';

	return $voucher_code_output;
}

// =========================================================================================== \\

/**
 * Function to return the HTML for a dashicon tag
 *
 * @param string $icon_name The name of the dashicon.
 * @param string $css_class The CSS class to add to the icon (optional).
 * @return string The HTML for the dashicon tag
 */
function ai4seo_get_dashicon_tag( string $icon_name, string $css_class = '' ): string {
	return '<i class="dashicons dashicons-' . esc_attr( $icon_name ) . ' ' . esc_attr( $css_class ) . '"></i>';
}

// =========================================================================================== \\

/**
 * Function to return the HTML for a dashicon tag for the menu items
 *
 * @param string $plugin_page The name of the plugin page (e.g., "page", "post", "category", etc.).
 * @return string The HTML for the dashicon tag for the menu items
 */
function ai4seo_get_dashicon_tag_for_navigation( $plugin_page ): string {
	$icon_name_mapping = array(
		'default'          => 'text-page',
		'page'             => 'admin-page',
		'post'             => 'admin-post',
		'category'         => 'admin-category',
		'product'          => 'products',
		'product-category' => 'products',
		'portfolio'        => 'portfolio',
		'attachment'       => 'admin-media',
		'media files'      => 'admin-media',
		'media'            => 'admin-media',
		'rss'              => 'rss',
		'rss-feed'         => 'rss',
		'rss_feed'         => 'rss',
	);

	$icon_name = $icon_name_mapping[ $plugin_page ] ?? $icon_name_mapping['default'];

	return ai4seo_get_dashicon_tag( $icon_name, 'ai4seo-menu-item-icon' );
}


// endregion
// ___________________________________________________________________________________________.
