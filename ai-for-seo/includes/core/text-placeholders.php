<?php
/**
 * Handles plugin identity, metadata, and attachment text-placeholder resolution.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === TEXT PLACEHOLDERS =================================================================== \\

/**
 * Replace the fixed plugin-identity placeholders used by white-label output.
 *
 * @param string $text Text containing white-label placeholders.
 * @return string Text with supported identity placeholders replaced.
 */
function ai4seo_replace_white_label_placeholders( string $text ): string {
	// Keep white-label tokens separate from runtime content replacements because their values are constants.
	return str_replace(
		array( '{NAME}', '{VERSION}', '{WEBSITE}' ),
		array( AI4SEO_PLUGIN_NAME, AI4SEO_PLUGIN_VERSION_NUMBER, AI4SEO_OFFICIAL_WEBSITE ),
		$text
	);
}


/**
 * Returns common placeholder replacements shared across metadata and attachments.
 *
 * @return array
 */
function ai4seo_get_common_placeholder_replacements(): array {
	// Normalize site identity values once so metadata and attachments render the same replacements.
	$website_url = untrailingslashit( home_url() );
	$website_url = $website_url ? trim( esc_url_raw( $website_url ) ) : '';

	$website_name = get_bloginfo( 'name' );
	$website_name = is_string( $website_name ) ? trim( wp_strip_all_tags( $website_name ) ) : '';

	return array(
		'WEBSITE_URL'  => $website_url,
		'WEBSITE_NAME' => $website_name,
	);
}


/**
 * Returns placeholder replacements for metadata prefixes and suffixes.
 *
 * @param int    $post_id              The current post ID.
 * @param string $product_price        The WooCommerce product price if available.
 * @param string $product_name         The WooCommerce product name if available.
 *
 * @return array
 */
function ai4seo_get_metadata_placeholder_replacements( int $post_id, string $product_price = '', string $product_name = '' ): array {
	// Extend the shared site replacements with post-specific values expected by metadata settings.
	$replacements = ai4seo_get_common_placeholder_replacements();

	$replacements['POST_ID'] = (string) absint( $post_id );

	$post_type = get_post_type( $post_id );

	// Keep product-only tokens present but empty for every other supported post type.
	$product_name_value  = '';
	$product_price_value = '';

	if ( 'product' === $post_type ) {
		if ( '' === $product_name ) {
			$product_name = get_the_title( $post_id );
		}

		$product_name_value = is_string( $product_name ) ? trim( wp_strip_all_tags( $product_name ) ) : '';

		if ( '' !== $product_price ) {
			$product_price_value = trim( wp_strip_all_tags( $product_price ) );
		}
	}

	$replacements['PRODUCT_NAME']  = $product_name_value;
	$replacements['PRODUCT_PRICE'] = $product_price_value;

	return $replacements;
}


/**
 * Return the runtime placeholder replacements used by rendered metadata and editor previews.
 *
 * @param int $post_id Current post ID.
 *
 * @return array<string, string> Resolved placeholder values.
 */
function ai4seo_get_metadata_output_placeholder_replacements( int $post_id ): array {
	$post_type     = get_post_type( $post_id );
	$product_name  = '';
	$product_price = '';

	// Product placeholders mirror frontend output only when WooCommerce can provide the product.
	if ( 'product' === $post_type
		&& ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE )
		&& ai4seo_is_function_usable( 'wc_get_product' )
		&& ai4seo_is_function_usable( 'wc_price' )
		&& class_exists( 'WC_Product' )
	) {
		$product = wc_get_product( $post_id );

		if ( $product instanceof WC_Product ) {
			$product_name      = wp_strip_all_tags( $product->get_name() );
			$product_price_raw = $product->get_price();

			if ( '' !== $product_price_raw && null !== $product_price_raw ) {
				$product_price = wc_price( $product_price_raw );
				$product_price = wp_strip_all_tags( $product_price );
				$product_price = html_entity_decode( $product_price, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$product_price = str_replace( ' ', ' ', $product_price );
				$product_price = trim( $product_price );
			}
		}
	}

	// Merge runtime commerce values with the common metadata replacement contract.
	$replacements = ai4seo_get_metadata_placeholder_replacements( $post_id, $product_price, $product_name );
	$post_title   = get_the_title( $post_id );
	$post_title   = is_string( $post_title ) ? trim( wp_strip_all_tags( $post_title ) ) : '';

	if ( '' !== $post_title ) {
		$replacements['TITLE'] = $post_title;
	}

	return $replacements;
}


/**
 * Returns placeholder replacements for attachment prefixes and suffixes.
 *
 * @param int $attachment_post_id The attachment post ID.
 *
 * @return array
 */
function ai4seo_get_attachment_placeholder_replacements( int $attachment_post_id ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 278475537, 'Prevented loop', true );
		return array();
	}

	// Preserve a stable replacement shape even when WordPress cannot resolve the attachment.
	$replacements = ai4seo_get_common_placeholder_replacements();

	$replacements['POST_ID']          = (string) absint( $attachment_post_id );
	$replacements['FILE_NAME']        = '';
	$replacements['FILE_TYPE']        = '';
	$replacements['FILE_SIZE']        = '';
	$replacements['IMAGE_DIMENSIONS'] = '';

	$attached_file_path = get_attached_file( $attachment_post_id );
	$pathinfo           = array();

	// Prefer the local path and fall back to the URL path for remotely stored attachment records.
	if ( $attached_file_path ) {
		$pathinfo = pathinfo( $attached_file_path );
	} else {
		$attachment_url = wp_get_attachment_url( $attachment_post_id );

		if ( $attachment_url ) {
			$url_path = wp_parse_url( $attachment_url, PHP_URL_PATH );

			if ( $url_path ) {
				$pathinfo = pathinfo( $url_path );
			}
		}
	}

	// File-name and extension replacements remain available without filesystem access.
	if ( ! empty( $pathinfo['filename'] ) ) {
		$replacements['FILE_NAME'] = trim( sanitize_text_field( $pathinfo['filename'] ) );
	}

	if ( ! empty( $pathinfo['extension'] ) ) {
		$replacements['FILE_TYPE'] = strtolower( trim( sanitize_text_field( $pathinfo['extension'] ) ) );
	}

	// File size depends on a readable local file and keeps the established KB formatting boundary.
	if ( $attached_file_path && file_exists( $attached_file_path ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Handle read races through the established fallback.
		$file_size_bytes = @filesize( $attached_file_path );

		if ( is_int( $file_size_bytes ) || is_float( $file_size_bytes ) ) {
			$file_size_kb = $file_size_bytes / 1024;

			if ( $file_size_kb > 0 ) {
				if ( $file_size_kb < 10 ) {
					$formatted_file_size = ai4seo_format_number_i18n( round( $file_size_kb, 2 ), 2 );
				} else {
					$formatted_file_size = ai4seo_format_number_i18n( round( $file_size_kb ) );
				}
			} else {
				$formatted_file_size = '0';
			}

			$replacements['FILE_SIZE'] = trim( $formatted_file_size . ' KB' );
		}
	}

	// Prefer WordPress metadata and consult the image file only when dimensions are unavailable there.
	$attachment_metadata = wp_get_attachment_metadata( $attachment_post_id );
	$image_dimensions    = array();

	if ( is_array( $attachment_metadata )
		&& ! empty( $attachment_metadata['width'] )
		&& ! empty( $attachment_metadata['height'] )
	) {
		$image_dimensions = array( $attachment_metadata['width'], $attachment_metadata['height'] );
	} elseif ( $attached_file_path && file_exists( $attached_file_path ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Handle read races through the established fallback.
		$image_size = @getimagesize( $attached_file_path );

		if ( is_array( $image_size ) && isset( $image_size[0], $image_size[1] ) ) {
			$image_dimensions = array( $image_size[0], $image_size[1] );
		}
	}

	// Apply one validation and formatting path regardless of the selected dimension source.
	if ( isset( $image_dimensions[0], $image_dimensions[1] ) ) {
		$width  = (int) $image_dimensions[0];
		$height = (int) $image_dimensions[1];

		if ( $width > 0 && $height > 0 ) {
			$replacements['IMAGE_DIMENSIONS'] = $width . 'x' . $height;
		}
	}

	return $replacements;
}


/**
 * Replaces supported placeholders in the provided text.
 *
 * @param string $text          The text that may contain placeholders.
 * @param array  $replacements  Map of placeholder => replacement value.
 *
 * @return string
 */
function ai4seo_replace_text_placeholders( string $text, array $replacements ): string {
	// Avoid regular-expression work when none of the supported delimiter families can occur.
	if ( '' === $text
		|| ( strpos( $text, '{' ) === false
			&& strpos( $text, '[' ) === false
			&& strpos( $text, '%%' ) === false )
	) {
		return $text;
	}

	// Resolve all delimiter styles through one case-insensitive replacement map.
	return (string) preg_replace_callback(
		'/\{([A-Z0-9_]+)\}|\[([A-Z0-9_]+)\]|%%([A-Z0-9_]+)%%/i',
		static function ( $matches ) use ( $replacements ) {
			$placeholder = '';

			if ( ! empty( $matches[1] ) ) {
				$placeholder = $matches[1];
			} elseif ( ! empty( $matches[2] ) ) {
				$placeholder = $matches[2];
			} elseif ( ! empty( $matches[3] ) ) {
				$placeholder = $matches[3];
			}

			if ( '' !== $placeholder ) {
				$key = strtoupper( $placeholder );

				if ( array_key_exists( $key, $replacements ) ) {
					return (string) $replacements[ $key ];
				}
			}

			return $matches[0];
		},
		$text
	);
}


/**
 * Replaces the [TITLE] placeholder in metadata prefixes or suffixes.
 *
 * @param string $text       Text that may contain the [TITLE] placeholder.
 * @param string $post_title The current post title used as replacement.
 *
 * @return string
 */
function ai4seo_replace_metadata_title_placeholder( string $text, string $post_title ): string {
	if ( '' === $text ) {
		return $text;
	}

	// Delegate syntax and case handling to the shared placeholder resolver.
	return ai4seo_replace_text_placeholders(
		$text,
		array(
			'TITLE' => $post_title,
		)
	);
}


/**
 * Checks whether the provided text contains WooCommerce product placeholders.
 *
 * @param string $text The text to inspect.
 *
 * @return bool
 */
function ai4seo_text_contains_product_placeholder( string $text ): bool {
	if ( '' === $text ) {
		return false;
	}

	// Keep product detection aligned with the delimiter grammar used by the shared resolver.
	return (bool) preg_match(
		'/\{PRODUCT_(?:NAME|PRICE)\}|\[PRODUCT_(?:NAME|PRICE)\]|%%PRODUCT_(?:NAME|PRICE)%%/i',
		$text
	);
}
