<?php
/**
 * Frontend metadata and image-attribute injection.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === FRONTEND INJECTIONS ================================================================== \\

/**
 * Initialize frontend metadata and image-attribute injection for all visitors.
 *
 * @return void
 */
function ai4seo_init_frontend_injections() {
	// Repeated bootstrap paths must not register duplicate output-buffer callbacks.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Limit full-document transformation to frontend HTML responses where a closing head and image markup can exist.
	if (
		is_admin()
		|| is_feed()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	) {
		return;
	}

	// Squirrly owns the response buffer, so its hook must run both transforms instead of this module starting another buffer.
	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO ) ) {
		add_action( 'sq_buffer', 'ai4seo_inject_our_meta_tags_into_the_html_head', 20 );
		add_action( 'sq_buffer', 'ai4seo_inject_image_attributes_into_html', 20 );
		return;
	}

	// Start one late response buffer so both transforms see the complete frontend document in a fixed order.
	add_action(
		'template_redirect',
		function () {
			ob_start(
				function ( $html ) {
					// Metadata must be injected before image attributes because both callbacks share the same response buffer.
					$html = ai4seo_inject_our_meta_tags_into_the_html_head( $html );
					return ai4seo_inject_image_attributes_into_html( $html );
				}
			);
		},
		PHP_INT_MAX
	);

	// Flush only this non-empty buffer during shutdown, preserving WordPress' existing empty-buffer behavior.
	add_action(
		'shutdown',
		function () {
			// Avoid emitting or closing a buffer when no transformed response content was captured.
			if ( ob_get_length() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ob_get_clean();
			}
		}
	);
}


/**
 * Extracts supported metadata tags from buffered head HTML.
 *
 * @param string $head_html The HTML content of the document head.
 * @return array The supported metadata tags grouped by metadata identifier.
 */
function ai4seo_get_meta_tags_from_html( string $head_html ): array {
	// Prevent nested output-buffer parsing from recursively analyzing the same document head.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 764731280, 'Prevented loop', true );
		return array();
	}

	// The metadata registry supplies the supported tag types and identifiers.
	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return array();
	}

	$found_meta_tags = array();
	foreach ( ai4seo_get_metadata_html_tokens( $head_html ) as $token ) {
		if ( $token['closing'] || ! in_array( $token['tag'], array( 'meta', 'title' ), true ) ) {
			continue;
		}
		$attributes = 'meta' === $token['tag'] ? array_change_key_case( wp_kses_hair( substr( $token['raw-html'], 5, -1 ), wp_allowed_protocols() ), CASE_LOWER ) : array();
		$entry      = array(
			'raw-html' => $token['raw-html'],
			'offset'   => $token['offset'],
			'content'  => '',
		);
		foreach ( array( 'charset', 'viewport' ) as $structural ) {
			if ( ( 'charset' === $structural && isset( $attributes['charset'] ) )
				|| ( 'viewport' === $structural && 'viewport' === strtolower( $attributes['name']['value'] ?? '' ) ) ) {
				$found_meta_tags[ $structural ] = $entry;
			}
		}
		// Match actual tags through the output registry, independent of attribute order or quoting.
		foreach ( AI4SEO_METADATA_DETAILS as $identifier => $details ) {
			$type = $details['output-tag-type'] ?? '';
			if ( 'title' === $type && 'title' === $token['tag']
				&& preg_match( '/^<title\b[^>]*>(.*)<\/title\s*>$/is', $token['raw-html'], $title ) ) {
				$entry['content'] = trim( $title[1] );
			} elseif ( 'meta' === $token['tag'] && in_array( $type, array( 'meta name', 'meta property' ), true )
				&& ( $details['output-tag-identifier'] ?? '' ) === strtolower( $attributes[ substr( $type, 5 ) ]['value'] ?? '' ) ) {
				$entry['content'] = trim( $attributes['content']['value'] ?? '' );
			} else {
				continue;
			}
			$found_meta_tags[ $identifier ][] = $entry;
		}
	}

	return $found_meta_tags;
}


/**
 * Locate editable tags without changing bytes or interpreting raw-text resources as markup.
 *
 * @param string $html Buffered HTML.
 * @param bool   $head_only Stop at the body when inspecting document-head boundaries.
 * @return array Tokens with original byte offsets; malformed raw-text tails remain opaque.
 */
function ai4seo_get_metadata_html_tokens( string $html, bool $head_only = false ): array {
	// Consume disjoint attribute chunks without a backtracking frame for every character.
	$attributes     = '(?:[^>"\']++|"[^"]*"|\'[^\']*\')*+';
	$pattern        = '~<!--|<!\[CDATA\[|</?[a-z][a-z0-9:-]*(?=[\s/>])' . $attributes . '>~i';
	$tokens         = array();
	$template_depth = 0;
	$offset         = 0;
	$length         = strlen( $html );
	while ( preg_match( $pattern, $html, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
		$raw    = $match[0][0];
		$start  = $match[0][1];
		$offset = $start + strlen( $raw );
		if ( '<!' === substr( $raw, 0, 2 ) ) {
			$terminator = '<!--' === $raw ? '-->' : ']]>';
			$end        = strpos( $html, $terminator, $offset );
			$offset     = false === $end ? $length : $end + strlen( $terminator );
			continue;
		}
		preg_match( '~^<(/?)([a-z][a-z0-9:-]*)~i', $raw, $tag );
		$name    = strtolower( $tag[2] );
		$closing = '/' === $tag[1];
		if ( ! $closing && in_array( $name, array( 'script', 'style', 'textarea', 'noscript', 'title' ), true ) ) {
			// Search only for the closing delimiter; large opaque payloads never enter a wildcard match.
			$offset = preg_match( '~</' . $name . '\s*>~i', $html, $end, PREG_OFFSET_CAPTURE, $offset )
				? $end[0][1] + strlen( $end[0][0] ) : $length;
		}
		if ( 'template' === $name ) {
			$template_depth = max( 0, $template_depth + ( $closing ? -1 : 1 ) );
			continue;
		}
		if ( $template_depth || in_array( $name, array( 'script', 'style', 'textarea', 'noscript' ), true ) ) {
			continue;
		}
		$tokens[] = array(
			'tag'      => $name,
			'closing'  => $closing,
			'raw-html' => substr( $html, $start, $offset - $start ),
			'offset'   => $start,
		);
		if ( $head_only && 'body' === $name ) {
			break;
		}
	}
	return $tokens;
}


/**
 * Resolve the main document to a supported entry without invoking editor permissions or repairs.
 *
 * @return array|null|WP_Error Null means outside the output scope; errors preserve existing HTML.
 */
function ai4seo_get_frontend_metadata_entry_context() {
	global $wp_the_query;
	if ( ! $wp_the_query instanceof WP_Query || is_admin() || $wp_the_query->is_feed() || $wp_the_query->is_404()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
		return null;
	}
	if ( $wp_the_query->is_singular() ) {
		$id = ai4seo_get_current_post_id();
		return $id ? array(
			'kind'    => 'post',
			'post_id' => $id,
		) : null;
	}
	if ( ! ( $wp_the_query->is_category() || $wp_the_query->is_tag() || $wp_the_query->is_tax() )
		|| $wp_the_query->is_paged() || (int) $wp_the_query->get( 'paged' ) > 1 || (int) $wp_the_query->get( 'page' ) > 1 ) {
		return null;
	}
	$term = $wp_the_query->get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$proxy = ai4seo_read_taxonomy_proxy( (int) $term->term_taxonomy_id );
	if ( is_wp_error( $proxy ) || ! $proxy ) {
		return $proxy;
	}
	$source = $proxy['source'];
	if ( ! $source || $source['term_id'] !== (int) $term->term_id || $source['taxonomy'] !== $term->taxonomy ) {
		return new WP_Error( 'taxonomy_output_identity_invalid' );
	}
	$state = ai4seo_get_taxonomy_proxy_source_state( (int) $term->term_taxonomy_id, $term->taxonomy );
	if ( is_wp_error( $state ) ) {
		return $state;
	}
	if ( 'available' !== $state['state'] || 'available' !== $source['availability'] || 'publish' !== $proxy['post_status'] ) {
		return null;
	}
	if ( $state['source']['term_id'] !== $source['term_id'] ) {
		return new WP_Error( 'taxonomy_output_identity_changed' );
	}
	return array(
		'kind'    => 'taxonomy',
		'post_id' => (int) $proxy['ID'],
		'source'  => $state['source'],
	);
}


// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Preserve PHP 8 named callers.
/**
 * Restores line breaks protected while buffered head HTML is split into tags.
 *
 * @param string $string The string containing protected line-break markers.
 * @return string The string with its original line breaks restored.
 */
function ai4seo_remove_header_line_break_placeholders( string $string ): string {
	// phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
	// Restore Windows and Unix markers separately so the original newline form is preserved.
	return str_replace( array( '#AI4SEO#LBRN#', '#AI4SEO#LBN#' ), array( "\r\n", "\n" ), $string );
}


/**
 * Modify and add plugin metadata tags to the HTML head.
 *
 * @param string $full_html_buffer Full HTML response buffer.
 * @return string Modified HTML response buffer.
 */
function ai4seo_inject_our_meta_tags_into_the_html_head( string $full_html_buffer ): string {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return $full_html_buffer;
	}

	if ( ! defined( 'AI4SEO_METADATA_DETAILS' ) ) {
		return $full_html_buffer;
	}

	// read setting AI4SEO_SETTING_META_TAG_OUTPUT_MODE.
	$policy               = ai4seo_read_metadata_sync_policy_snapshot();
	$meta_tag_output_mode = $policy['settings'][ AI4SEO_SETTING_META_TAG_OUTPUT_MODE ];

	if ( 'disable' === $meta_tag_output_mode ) {
		return $full_html_buffer; // stop function if meta tag output is disabled.
	}

	// Normalize active identifiers before exact frontend-output comparisons.
	$active_meta_tags = $policy['settings'][ AI4SEO_SETTING_ACTIVE_META_TAGS ];

	if ( ! $active_meta_tags ) {
		return $full_html_buffer;
	}

	$context = ai4seo_get_frontend_metadata_entry_context();
	if ( is_wp_error( $context ) || ! $context ) {
		return $full_html_buffer;
	}
	$post_id     = $context['post_id'];
	$is_taxonomy = 'taxonomy' === $context['kind'];

	// Require one explicit head before the body; protected resources never supply delimiters.
	$head_start_position = null;
	$head_end_position   = null;
	foreach ( ai4seo_get_metadata_html_tokens( $full_html_buffer, true ) as $token ) {
		if ( 'body' === $token['tag'] && null === $head_end_position ) {
			return $full_html_buffer;
		}
		if ( 'head' !== $token['tag'] ) {
			continue;
		}
		if ( ! $token['closing'] && null === $head_start_position ) {
			$head_start_position = $token['offset'] + strlen( $token['raw-html'] );
		} elseif ( $token['closing'] && null !== $head_start_position && null === $head_end_position ) {
			$head_end_position = $token['offset'];
		} else {
			return $full_html_buffer;
		}
	}
	if ( null === $head_start_position || null === $head_end_position ) {
		return $full_html_buffer;
	}

	$head_html = substr( $full_html_buffer, $head_start_position, $head_end_position - $head_start_position );

	// analyze head html.
	$found_third_party_meta_tags = ai4seo_get_meta_tags_from_html( $head_html );

	// read OUR metadata values for this post.
	$output_state = ai4seo_read_metadata_output_state( $post_id );
	if ( ! $output_state['read_succeeded'] ) {
		return $full_html_buffer;
	}
	$our_metadata = $output_state['values'];

	// check post type.
	$current_post_type = get_post_type( $post_id );
	if ( ! $is_taxonomy && ! ai4seo_is_metadata_sync_post_supported( $post_id, $policy ) ) {
		return $full_html_buffer;
	}

	// go through each meta tag and decide what to do with it.
	$add_this_metadata                 = array();
	$remove_this_third_party_meta_tags = array();

	$metadata_placeholder_replacements      = ai4seo_get_metadata_output_placeholder_replacements( $post_id );
	$current_post_title_for_placeholders    = $metadata_placeholder_replacements['TITLE'] ?? '';
	$current_product_price_for_placeholders = $metadata_placeholder_replacements['PRODUCT_PRICE'] ?? '';

	foreach ( AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_field_details ) {
		if ( ! in_array( $this_metadata_identifier, $active_meta_tags, true )
			|| in_array( $this_metadata_identifier, $output_state['unknown'], true ) ) {
			continue;
		}
		$this_found_third_party_meta_tags = $found_third_party_meta_tags[ $this_metadata_identifier ] ?? array();
		$this_our_metadata                = $our_metadata[ $this_metadata_identifier ] ?? '';

		// Resolve supported third-party templates before deciding whether existing frontend tags should be replaced.
		if ( ! $is_taxonomy && $this_our_metadata ) {
			$this_our_metadata                         = ai4seo_resolve_third_party_seo_metadata_variables( $this_our_metadata, $post_id, $this_metadata_identifier );
			$our_metadata[ $this_metadata_identifier ] = $this_our_metadata;
		}

		// find a fallback if neither we nor a third party have a value for this meta tag.
		if ( ! $is_taxonomy && empty( $this_our_metadata ) && empty( $this_found_third_party_meta_tags ) ) {
			ai4seo_apply_possible_fallbacks( $post_id, $this_metadata_identifier, $our_metadata );
			$this_our_metadata = $our_metadata[ $this_metadata_identifier ] ?? '';

			// Apply the same resolution rules to fallback values before they enter output-mode decisions.
			if ( $this_our_metadata ) {
				$this_our_metadata                         = ai4seo_resolve_third_party_seo_metadata_variables( $this_our_metadata, $post_id, $this_metadata_identifier );
				$our_metadata[ $this_metadata_identifier ] = $this_our_metadata;
			}
		}

		// leave this meta tag alone if we do not have a value for it or we exclude this meta tag.
		if ( $is_taxonomy ? '' === trim( $this_our_metadata ) : ! $this_our_metadata ) {
			continue;
		}

		switch ( $meta_tag_output_mode ) {
			case 'force':
				$add_this_metadata[ $this_metadata_identifier ] = $this_our_metadata;
				break;
			case 'replace':
				$add_this_metadata[ $this_metadata_identifier ] = $this_our_metadata;

				// remove found third party meta tags.
				if ( $this_found_third_party_meta_tags ) {
					foreach ( $this_found_third_party_meta_tags as $this_found_third_party_meta_tag ) {
						if ( $this_found_third_party_meta_tag ) {
							$remove_this_third_party_meta_tags[ $this_found_third_party_meta_tag['offset'] ] = $this_found_third_party_meta_tag['raw-html'];
						}
					}
				}
				break;
			case 'complement':
				if ( ! $this_found_third_party_meta_tags ) {
					$add_this_metadata[ $this_metadata_identifier ] = $this_our_metadata;
				} else {
					// workaround: if all the found meta tags are empty -> add ours anyway and remove their empty ones.
					$this_found_third_party_meta_tag_got_content         = false;
					$this_found_third_party_meta_tag_no_content_raw_html = array();
					foreach ( $this_found_third_party_meta_tags as $this_found_third_party_meta_tag ) {
						if ( $is_taxonomy ? '' !== $this_found_third_party_meta_tag['content'] : (bool) $this_found_third_party_meta_tag['content'] ) {
							$this_found_third_party_meta_tag_got_content = true;
							break;
						} else {
							$this_found_third_party_meta_tag_no_content_raw_html[ $this_found_third_party_meta_tag['offset'] ] = $this_found_third_party_meta_tag['raw-html'];
						}
					}

					if ( ! $this_found_third_party_meta_tag_got_content ) {
						$add_this_metadata[ $this_metadata_identifier ] = $this_our_metadata;
						$remove_this_third_party_meta_tags             += $this_found_third_party_meta_tag_no_content_raw_html;
					}
				}
				break;
		}
	}

	// Remove only identified tag spans, from the end so earlier byte offsets remain valid.
	if ( $remove_this_third_party_meta_tags ) {
		krsort( $remove_this_third_party_meta_tags, SORT_NUMERIC );
		foreach ( $remove_this_third_party_meta_tags as $offset => $raw_html ) {
			$head_html = substr_replace( $head_html, '', $offset, strlen( $raw_html ) );
		}
	}
	$found_third_party_meta_tags = ai4seo_get_meta_tags_from_html( $head_html );

	// add our tags to the head, finding position first.
	if ( $add_this_metadata ) {
		$add_this_meta_tags = array();

		// Read prefix- and suffix-settings.
		$ai4seo_metadata_prefixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_PREFIXES );
		$ai4seo_metadata_suffixes = ai4seo_get_setting( AI4SEO_SETTING_METADATA_SUFFIXES );

		// Build metadata tags from the selected values while preserving the registry's output shapes.
		foreach ( $add_this_metadata as $this_metadata_identifier => $this_metadata_content ) {
			// Read field details and affixes after output-mode selection has accepted the resolved metadata value.
			$this_metadata_field_details = AI4SEO_METADATA_DETAILS[ $this_metadata_identifier ] ?? array();
			$this_metadata_prefix_raw    = $ai4seo_metadata_prefixes[ $this_metadata_identifier ] ?? '';
			$this_metadata_suffix_raw    = $ai4seo_metadata_suffixes[ $this_metadata_identifier ] ?? '';

			$this_metadata_prefix = trim( sanitize_text_field( $this_metadata_prefix_raw ) );
			$this_metadata_suffix = trim( sanitize_text_field( $this_metadata_suffix_raw ) );

			if ( 'product' !== $current_post_type
				&& ai4seo_text_contains_product_placeholder( $this_metadata_prefix_raw )
			) {
				$this_metadata_prefix = '';
			} else {
				$this_metadata_prefix = ai4seo_replace_text_placeholders(
					$this_metadata_prefix,
					$metadata_placeholder_replacements
				);
			}

			if ( 'product' !== $current_post_type
				&& ai4seo_text_contains_product_placeholder( $this_metadata_suffix_raw )
			) {
				$this_metadata_suffix = '';
			} else {
				$this_metadata_suffix = ai4seo_replace_text_placeholders(
					$this_metadata_suffix,
					$metadata_placeholder_replacements
				);
			}

			$this_metadata_prefix = ai4seo_replace_metadata_title_placeholder(
				$this_metadata_prefix,
				$current_post_title_for_placeholders
			);

			$this_metadata_suffix = ai4seo_replace_metadata_title_placeholder(
				$this_metadata_suffix,
				$current_post_title_for_placeholders
			);

			if ( ! $this_metadata_field_details ) {
				continue;
			}

			if ( false !== strpos( $this_metadata_content, '{WC_PRICE=' ) ) {
				$this_metadata_content = preg_replace_callback(
					'/\{WC_PRICE=([^}]+)}/',
					function ( $matches ) use ( $current_product_price_for_placeholders ) {
						$fallback_price = html_entity_decode( wp_strip_all_tags( $matches[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						$fallback_price = str_replace( ' ', ' ', $fallback_price );
						$fallback_price = trim( $fallback_price );

						if ( '' !== $current_product_price_for_placeholders ) {
							return $current_product_price_for_placeholders;
						}

						return $fallback_price;
					},
					$this_metadata_content
				);
			}

			// Add prefix and suffix.
			$this_metadata_content = trim( $this_metadata_prefix . ' ' . $this_metadata_content . ' ' . $this_metadata_suffix );

			// Prepare variables.
			$this_output_tag_type       = $this_metadata_field_details['output-tag-type'] ?? '';
			$this_output_tag_identifier = $this_metadata_field_details['output-tag-identifier'] ?? '';

			// Render each configured field with the tag shape declared by its metadata registry entry.
			if ( 'title' === $this_output_tag_type ) {
				$add_this_meta_tags[] = '<title>' . esc_attr( $this_metadata_content ) . '</title>';
			} elseif ( 'meta name' === $this_output_tag_type ) {
				$add_this_meta_tags[] = '<meta name="' . esc_attr( $this_output_tag_identifier ) . '" content="' . esc_attr( $this_metadata_content ) . '" />';
			} elseif ( 'meta property' === $this_output_tag_type ) {
				$add_this_meta_tags[] = '<meta property="' . esc_attr( $this_output_tag_identifier ) . '" content="' . esc_attr( $this_metadata_content ) . '" />';
			}
		}

		// output our meta tags.
		if ( $add_this_meta_tags ) {
			// find a suitable position for our meta tags.
			$our_meta_tags_position = 0;

			// consider the charset meta tag position, if it's near the head start.
			if ( isset( $found_third_party_meta_tags['charset'] ) ) {
				$charset_meta_tags_position = $found_third_party_meta_tags['charset']['offset'] + strlen( $found_third_party_meta_tags['charset']['raw-html'] );

				// set $charset_meta_tags_position as our meta tags position if it's not further away than 100 characters.
				if ( $charset_meta_tags_position < 100 ) {
					$our_meta_tags_position = $charset_meta_tags_position;
				}
			}

			// consider the viewport meta tag position, if it's near the head start.
			if ( isset( $found_third_party_meta_tags['viewport'] ) ) {
				$viewport_meta_tags_position = $found_third_party_meta_tags['viewport']['offset'] + strlen( $found_third_party_meta_tags['viewport']['raw-html'] );

				// set $viewport_meta_tags_position as our meta tags position if it's not further away than 200 characters.
				if ( $viewport_meta_tags_position < 200 ) {
					$our_meta_tags_position = $viewport_meta_tags_position;
				}
			}

			// Read start- and end-settings for generator hints.
			$add_generator_hints = ai4seo_get_setting( AI4SEO_SETTING_ADD_GENERATOR_HINTS );

			if ( $add_generator_hints ) {
				$source_code_notes_start = ai4seo_get_setting( AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT );
				$source_code_notes_end   = ai4seo_get_setting( AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT );

				// Replace placeholders in source-code-notes.
				$source_code_notes_start = ai4seo_replace_white_label_placeholders( $source_code_notes_start );
				$source_code_notes_end   = ai4seo_replace_white_label_placeholders( $source_code_notes_end );

				// Make sure that $source_code_notes_start and $source_code_notes_end don't exceed the max. length
				// decode \&quot; to ".
				$source_code_notes_start = str_replace( '\&quot;', '"', $source_code_notes_start );
				$source_code_notes_end   = str_replace( '\&quot;', '"', $source_code_notes_end );
				$source_code_notes_start = ai4seo_mb_substr( $source_code_notes_start, 0, 250 );
				$source_code_notes_end   = ai4seo_mb_substr( $source_code_notes_end, 0, 250 );

				// add plugin information to the meta tags block.
				array_unshift( $add_this_meta_tags, '<!-- ' . esc_html( $source_code_notes_start ) . ' -->' );
				$add_this_meta_tags[] = '<!-- ' . esc_html( $source_code_notes_end ) . ' -->';
			}

			$add_this_meta_tags = ai4seo_deep_sanitize( $add_this_meta_tags, 'ai4seo_wp_kses' );

			// add our meta tags to the head.
			$head_html = substr_replace( $head_html, "\n\n\t" . implode( "\n\t", $add_this_meta_tags ) . "\n", $our_meta_tags_position, 0 );
		}
	}

	return substr_replace( $full_html_buffer, $head_html, $head_start_position, $head_end_position - $head_start_position );
}


// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve the render_block callback contract.
/**
 * Inject image attributes into an individual Gutenberg block render.
 *
 * @param string $content Rendered block HTML.
 * @param array  $block   Parsed block data retained for the WordPress render_block filter signature.
 * @return string Rendered block HTML with eligible image attributes injected.
 */
function ai4seo_inject_image_attributes_for_gutenberg( $content, $block ) {
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	// Prevent duplicate processing when more than one render path invokes this callback.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return $content;
	}

	// Skip admin, feeds, REST API and AJAX requests.
	if (
		is_admin()
		|| is_feed()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	) {
		return $content;
	}

	return ai4seo_inject_image_attributes_into_html( $content );
}


/**
 * Inject stored image attributes into rendered image tags when the matching settings are enabled.
 *
 * @param mixed $content The content value.
 * @return mixed Content with eligible image attributes injected.
 */
function ai4seo_inject_image_attributes_into_html( $content ) {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return $content;
	}

	$policy               = ai4seo_read_metadata_sync_policy_snapshot();
	$alt_enabled          = $policy['settings'][ AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION ];
	$title_injection_mode = $policy['settings'][ AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE ];

	if ( ! $alt_enabled && 'disabled' === $title_injection_mode ) {
		return $content;
	}

	$cache = array();

	// Keep URL identities and attribute values within this render pass and the current site scope.
	$attachment_ids               = array();
	$attachment_ids_scope         = '';
	$attachment_ids_cache_enabled = true;

	return preg_replace_callback(
		'/<img\b([^>]*?)>/i',
		function ( $matches ) use ( &$cache, &$attachment_ids, &$attachment_ids_scope, &$attachment_ids_cache_enabled, $alt_enabled, $title_injection_mode ) {
			global $wpdb;

			$this_full_tag = $matches[0];
			$this_attr_str = $matches[1];

			// Resolve the rendered source back to an attachment before consulting plugin-generated postmeta.
			if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/', $this_attr_str, $this_src_matches ) ) {
				return $this_full_tag;
			}

			$this_src   = $this_src_matches[1];
			$this_scope = ai4seo_get_site_options_request_cache_scope();

			// Drop the old site's identities, including when the current site cannot be determined.
			if ( $attachment_ids_scope !== $this_scope ) {
				$attachment_ids       = array();
				$attachment_ids_scope = $this_scope;
				$cache                = array();
			}
			if ( '' === $this_scope ) {
				$cache = array();
			}

			// Resolver filters may be stateful or appear mid-render; once observed, preserve every later call.
			if ( $attachment_ids_cache_enabled && ( has_filter( 'pre_attachment_url_to_postid' ) || has_filter( 'attachment_url_to_postid' ) ) ) {
				$attachment_ids               = array();
				$attachment_ids_cache_enabled = false;
			}

			if ( $attachment_ids_cache_enabled && '' !== $this_scope && array_key_exists( $this_src, $attachment_ids ) ) {
				$this_post_id = $attachment_ids[ $this_src ];
			} else {
				$this_post_id = ai4seo_get_attachment_id_from_src( $this_src );

				// A lookup can itself install a filter or switch sites; never publish that result into the old scope.
				if ( $attachment_ids_cache_enabled && ( has_filter( 'pre_attachment_url_to_postid' ) || has_filter( 'attachment_url_to_postid' ) ) ) {
					$attachment_ids               = array();
					$attachment_ids_cache_enabled = false;
				}

				$resolved_scope = ai4seo_get_site_options_request_cache_scope();
				if ( $resolved_scope !== $this_scope ) {
					$attachment_ids       = array();
					$attachment_ids_scope = $resolved_scope;
				}

				// Retain regular misses too, but never an observable database failure or more than 256 exact URLs.
				if ( $attachment_ids_cache_enabled && '' !== $this_scope && $resolved_scope === $this_scope
					&& empty( $wpdb->last_error ) && count( $attachment_ids ) < 256 ) {
					$attachment_ids[ $this_src ] = $this_post_id;
				}
			}

			if ( ! $this_post_id ) {
				return $this_full_tag;
			}

			// Restrict render-level changes to attachments for which the plugin has generated and stored data.
			$attribute_cache_key = $this_scope . ':' . $this_post_id;
			if ( ! isset( $cache[ $attribute_cache_key ]['generated'] ) ) {
				try {
					$cache[ $attribute_cache_key ]['generated'] = ai4seo_read_generated_data_presence_for_post( $this_post_id );
				} catch ( Throwable $throwable ) {
					$cache[ $attribute_cache_key ]['generated'] = false;
				}
			}
			if ( ! $cache[ $attribute_cache_key ]['generated'] ) {
				return $this_full_tag;
			}

			$this_to_add = array();

			// Queue an alt replacement only when render-level injection is enabled and the stored value differs.
			if ( $alt_enabled ) {
				// Preserve an explicitly matching value while still replacing missing or stale rendered attributes.
				preg_match( '/\balt\s*=\s*["\']([^"\']*)["\']/', $this_attr_str, $this_alt_matches );
				$this_existing_alt = $this_alt_matches[1] ?? null;

				// Cache postmeta within this render pass because the same attachment can appear more than once.
				if ( ! isset( $cache[ $attribute_cache_key ]['alt'] ) ) {
					$cache[ $attribute_cache_key ]['alt'] = ai4seo_read_frontend_attachment_alt( $this_post_id );
				}
				$this_db_alt = $cache[ $attribute_cache_key ]['alt'];

				if ( $this_db_alt && $this_existing_alt !== $this_db_alt ) {
					$this_to_add['alt'] = $this_db_alt;
				}
			}

			// Queue the configured title source independently so alt-only and title-only modes keep working.
			if ( 'disabled' !== $title_injection_mode ) {
				preg_match( '/\btitle\s*=\s*["\']([^"\']*)["\']/', $this_attr_str, $this_title_matches );
				$this_existing_title = $this_title_matches[1] ?? null;

				if ( ! isset( $cache[ $attribute_cache_key ][ $title_injection_mode ] ) ) {
					$cache[ $attribute_cache_key ][ $title_injection_mode ] = ai4seo_get_title_attribute_value( $this_post_id, $title_injection_mode, $cache );
				}
				$this_db_title = $cache[ $attribute_cache_key ][ $title_injection_mode ];

				if ( $this_db_title && $this_existing_title !== $this_db_title ) {
					$this_to_add['title'] = $this_db_title;
				}
			}

			if ( ! $this_to_add ) {
				return $this_full_tag;
			}

			// Remove only the attributes being replaced so unrelated image markup remains byte-for-byte intact.
			if ( isset( $this_to_add['alt'] ) ) {
				$this_attr_str = preg_replace( '/\s*alt\s*=\s*["\'][^"\']*["\']/', '', $this_attr_str );
			}

			if ( isset( $this_to_add['title'] ) ) {
				$this_attr_str = preg_replace( '/\s*title\s*=\s*["\'][^"\']*["\']/', '', $this_attr_str );
			}

			// Normalize the captured attribute suffix before rebuilding the original self-closing style below.
			$this_attr_str = preg_replace( '/\s*\/$/', '', rtrim( $this_attr_str ) );

			// Rebuild the image once after both optional attributes have been collected.
			$this_self_closed = substr( rtrim( $this_full_tag ), -2 ) === '/>';
			$this_tag_ending  = $this_self_closed ? ' />' : '>';
			$this_new_tag     = '<img' . $this_attr_str;

			// Escape each stored value at the final HTML boundary before appending it to the tag.
			foreach ( $this_to_add as $name => $val ) {
				$this_new_tag .= ' ' . $name . '="' . esc_attr( $val ) . '"';
			}

			$this_full_tag = $this_new_tag . $this_tag_ending;

			return $this_full_tag;
		},
		$content
	);
}


/**
 * Get the attachment title attribute value selected by the render-level setting.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $setting_value Title injection setting value.
 * @param array  &$cache        Reference to the request-local cache array.
 * @return string|false Title attribute value, or false when none is available.
 */
function ai4seo_get_title_attribute_value( int $attachment_id, string $setting_value, array &$cache ) {
	$cache_key = ai4seo_get_site_options_request_cache_scope() . ':' . $attachment_id . ':' . $setting_value;

	if ( isset( $cache[ $attachment_id ][ $cache_key ] ) ) {
		return $cache[ $attachment_id ][ $cache_key ];
	}

	$value = false;

	switch ( $setting_value ) {
		case 'inject_title':
			$value = get_the_title( $attachment_id );
			break;
		case 'inject_alt_text':
			$value = ai4seo_read_frontend_attachment_alt( $attachment_id );
			break;
		case 'inject_caption':
			$attachment = get_post( $attachment_id );

			if ( $attachment ) {
				$value = $attachment->post_excerpt;
			}
			break;
		case 'inject_description':
			$attachment = get_post( $attachment_id );

			if ( $attachment ) {
				$value = $attachment->post_content;
			}
			break;
	}

	$value                                 = is_string( $value ) ? $value : false;
	$cache[ $attachment_id ][ $cache_key ] = $value;
	return $value;
}


/**
 * Get an attachment ID from an image source URL.
 *
 * @param string $img_src Image source URL.
 * @return int|false Attachment ID, or false when no match is found.
 */
function ai4seo_get_attachment_id_from_src( string $img_src ) {
	global $wpdb;

	// Remove query parameters and fragments from URL.
	$img_src = strtok( $img_src, '?' );
	$img_src = strtok( $img_src, '#' );

	// First try WordPress built-in function.
	$attachment_id = attachment_url_to_postid( $img_src );

	if ( $attachment_id ) {
		return $attachment_id;
	}

	// If that fails, try to match by filename in case of different sizes.
	$filename = basename( $img_src );

	// Remove size suffixes like -150x150, -300x200, etc.
	$filename_without_size = preg_replace( '/-\d+x\d+(?=\.[^.]*$)/', '', $filename );

	if ( $filename_without_size !== $filename ) {
		// Try to find by the original filename.
		$original_url  = str_replace( $filename, $filename_without_size, $img_src );
		$attachment_id = attachment_url_to_postid( $original_url );

		if ( $attachment_id ) {
			return $attachment_id;
		}
	}

	// As last resort, search in postmeta for the URL.
	$cached_attachment_id = ai4seo_get_cached_attachment_id_from_filename( $filename );

	if ( false !== $cached_attachment_id ) {
		return $cached_attachment_id ? $cached_attachment_id : false;
	}

	// The WordPress URL lookup cannot match every resized filename, so use the plugin cache before this final prepared query.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The plugin cache immediately above covers this filename lookup.
	$attachment_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			esc_sql( $filename )
		)
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 984321670, 'Database error: ' . $wpdb->last_error, true );
		return false;
	}

	$attachment_id = $attachment_id ? (int) $attachment_id : 0;
	ai4seo_set_cached_attachment_id_from_filename( $filename, $attachment_id );

	return $attachment_id ? $attachment_id : false;
}


// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve the WordPress image-filter contract.
/**
 * Fill a missing alt attribute for WordPress-generated attachment image markup.
 *
 * @param array   $attr       Image attributes supplied by WordPress.
 * @param WP_Post $attachment Attachment post.
 * @param mixed   $size       Requested image size retained for the WordPress filter signature.
 * @return array Filtered image attributes.
 */
function ai4seo_filter_wp_image_attrs( $attr, $attachment, $size ) {
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	// Prevent duplicate processing when several image rendering paths invoke this callback.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return $attr;
	}

	// Preserve author-supplied alt text and only fill an empty attribute from attachment postmeta.
	if ( empty( $attr['alt'] ) ) {
		$alt = ai4seo_read_frontend_attachment_alt( $attachment->ID );

		// Sanitize the stored value before returning it to WordPress' image renderer.
		if ( $alt ) {
			$attr['alt'] = sanitize_text_field( $alt );
		}
	}
	return $attr;
}

/**
 * Read an unambiguous native alt value without coercing damaged storage to text.
 *
 * @param int $attachment_id Attachment ID.
 * @return string|false Valid text or an unknown value.
 */
function ai4seo_read_frontend_attachment_alt( int $attachment_id ) {
	$succeeded = false;
	try {
		$rows = ai4seo_read_exact_third_party_postmeta_rows( $attachment_id, '_wp_attachment_image_alt', $succeeded );
	} catch ( Throwable $throwable ) {
		return false;
	}
	if ( ! $succeeded || count( $rows ) > 1 ) {
		return false;
	}
	$value = $rows[0]['meta_value'] ?? '';
	return is_serialized( $value ) ? false : $value;
}

/**
 * Adapt existing bounded readers to output without inventing values for unknown fields.
 *
 * Individually validated display values remain usable when another member is damaged;
 * malformed JSON, invalid row identity and conflicts never become missing-value fallbacks.
 *
 * @param int $post_id Entry ID.
 * @return array Read success, known values and unknown identifiers.
 */
function ai4seo_read_metadata_output_state( int $post_id ): array {
	$succeeded = false;
	$views     = array();
	$issues    = array();
	try {
		$rows = ai4seo_read_available_metadata_by_post_ids( array( $post_id ), false, $succeeded, $views, true, $issues );
	} catch ( Throwable $throwable ) {
		return array(
			'read_succeeded' => false,
			'values'         => array(),
			'unknown'        => AI4SEO_AVAILABLE_METADATA_IDENTIFIERS,
		);
	}
	$view    = $views[ $post_id ] ?? array();
	$values  = $rows[ $post_id ] ?? array();
	$unknown = array();
	if ( 'unreadable' === ( $view['classification'] ?? '' ) ) {
		$values  = $view['display_metadata'] ?? array();
		$unknown = array_values( array_diff( AI4SEO_AVAILABLE_METADATA_IDENTIFIERS, array_keys( $values ) ) );
	} else {
		foreach ( AI4SEO_AVAILABLE_METADATA_IDENTIFIERS as $field ) {
			if ( isset( $view['conflicts'][ $field ] ) || ai4seo_is_posts_table_analysis_field_unknown( $issues, $post_id, $field ) ) {
				$unknown[] = $field;
				unset( $values[ $field ] );
			}
		}
	}
	return array(
		'read_succeeded' => $succeeded,
		'values'         => $values,
		'unknown'        => $unknown,
	);
}
