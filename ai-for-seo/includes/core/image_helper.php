<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- Preserve the explicitly requested image_helper.php module name.
/**
 * Provides image inspection, temporary-file, conversion, and encoding helpers.
 *
 * Helpers operate on bytes, dimensions, MIME types, URLs, and caller-supplied limits.
 * Attachment identity, attribute generation, and API response policy belong to callers.
 *
 * @package AI_For_SEO
 */

// Keep this module inaccessible before WordPress has loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether given binary content is probably an image.
 *
 * This is a "best effort" check. It does not require GD/Imagick support for the format.
 *
 * @param string $binary_content Raw response body (binary).
 * @param string $content_type   Optional HTTP Content-Type header value.
 * @return array {
 * @type bool $is_probably_image True if it looks like an image.
 * @type string $reason Short explanation.
 * @type string|null $detected_format Detected format (jpeg/png/gif/webp/avif/bmp/tiff/ico/svg) or null.
 * }
 */
function ai4seo_is_probably_image_content( string $binary_content, string $content_type = '' ): array {
	if ( '' === $binary_content ) {
		return array(
			'is_probably_image' => false,
			'reason'            => 'empty_body',
			'detected_format'   => null,
		);
	}

	// 1) Verify with getimagesizefromstring (if available).
	if ( function_exists( 'getimagesizefromstring' ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Arbitrary response bodies are expected to fail this best-effort image probe.
		$image_size = @getimagesizefromstring( $binary_content );

		if ( $image_size ) {
			$mime_type = $image_size['mime'] ?? '';
			return array(
				'is_probably_image' => true,
				'reason'            => 'getimagesizefromstring',
				'detected_format'   => $mime_type,
			);
		}
	}

	// 2) Magic bytes signature checks (most stable).
	$head = substr( $binary_content, 0, 64 );

	// JPEG: FF D8 FF.
	if ( substr( $head, 0, 3 ) === "\xFF\xD8\xFF" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'jpeg',
		);
	}

	// PNG: 89 50 4E 47 0D 0A 1A 0A.
	if ( substr( $head, 0, 8 ) === "\x89PNG\r\n\x1A\n" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'png',
		);
	}

	// GIF: GIF87a / GIF89a.
	if ( substr( $head, 0, 6 ) === 'GIF87a' || substr( $head, 0, 6 ) === 'GIF89a' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'gif',
		);
	}

	// WebP: RIFF....WEBP.
	if ( substr( $head, 0, 4 ) === 'RIFF' && substr( $head, 8, 4 ) === 'WEBP' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'webp',
		);
	}

	// BMP: BM.
	if ( substr( $head, 0, 2 ) === 'BM' ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'bmp',
		);
	}

	// TIFF: II*\x00 or MM\x00*.
	if ( substr( $head, 0, 4 ) === "II*\x00" || substr( $head, 0, 4 ) === "MM\x00*" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'tiff',
		);
	}

	// ICO: 00 00 01 00.
	if ( substr( $head, 0, 4 ) === "\x00\x00\x01\x00" ) {
		return array(
			'is_probably_image' => true,
			'reason'            => 'magic_bytes',
			'detected_format'   => 'ico',
		);
	}

	// AVIF/HEIF family: look for 'ftyp' box + known brands.
	// Note: ISO BMFF has 'ftyp' typically within first bytes, but not always at offset 4 exactly.
	if ( strpos( $head, 'ftyp' ) !== false ) {
		$brands = array( 'avif', 'avis', 'heic', 'heix', 'mif1', 'msf1' );
		foreach ( $brands as $brand ) {
			if ( strpos( $head, $brand ) !== false ) {
				$detected = ( 'avif' === $brand || 'avis' === $brand ) ? 'avif' : 'heif';
				return array(
					'is_probably_image' => true,
					'reason'            => 'magic_bytes',
					'detected_format'   => $detected,
				);
			}
		}
	}

	// 3) Optional: finfo MIME sniff (fallback).
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );

		if ( $finfo ) {
			$mime = finfo_buffer( $finfo, $binary_content );

			if ( is_string( $mime ) && strpos( strtolower( $mime ), 'image/' ) === 0 ) {
				return array(
					'is_probably_image' => true,
					'reason'            => 'finfo_mime',
					'detected_format'   => strtolower( substr( $mime, 6 ) ),
				);
			}
		}
	}

	// 4) As a last fallback: trust header if it says image/*.
	if ( '' !== $content_type ) {
		$content_type_normalized = strtolower( trim( explode( ';', $content_type )[0] ) );
		if ( strpos( $content_type_normalized, 'image/' ) === 0 ) {
			return array(
				'is_probably_image' => true,
				'reason'            => 'content_type_only',
				'detected_format'   => strtolower( substr( $content_type_normalized, 6 ) ),
			);
		}
	}

	return array(
		'is_probably_image' => false,
		'reason'            => 'no_match',
		'detected_format'   => null,
	);
}


/**
 * Convert an image signature detector format to its normalized MIME type.
 *
 * @param string $detected_image_format Format or MIME type returned by the image signature detector.
 * @return string Normalized MIME type, or an empty string when the format is unknown.
 */
function ai4seo_get_mime_type_from_detected_image_format( string $detected_image_format ): string {
	// Normalize once so both MIME values and short signature names remain case-insensitive.
	$detected_image_format = strtolower( $detected_image_format );

	// Preserve MIME values returned by getimagesizefromstring() while normalizing optional parameters.
	if ( 0 === strpos( $detected_image_format, 'image/' ) ) {
		return ai4seo_normalize_mime_type_string( $detected_image_format ) ?? '';
	}

	// Map the stable short names returned by the plugin's magic-byte checks.
	$image_mime_types = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'heif' => 'image/heif',
		'bmp'  => 'image/bmp',
		'tiff' => 'image/tiff',
		'ico'  => 'image/x-icon',
	);

	return $image_mime_types[ $detected_image_format ] ?? '';
}


/**
 * Create a WordPress image editor for raw image bytes.
 *
 * WordPress selects the available backend, so conversion works with Imagick or GD without
 * requiring either extension directly.
 *
 * @param string $image_data                 Raw source bytes.
 * @param string $source_mime_type           MIME type detected from the source bytes.
 * @param string $derivative_mime_type       MIME type required for the derivative.
 * @param array  $temporary_image_file_paths Temporary paths that must be deleted by the caller.
 * @return WP_Image_Editor|WP_Error
 */
function ai4seo_get_image_editor_from_data(
	string $image_data,
	string $source_mime_type,
	string $derivative_mime_type,
	array &$temporary_image_file_paths
) {
	// Load the WordPress temporary-file helper only when the host has not loaded it already.
	if ( ! function_exists( 'wp_tempnam' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	// Return a normalized editor error when WordPress cannot expose the required abstraction.
	if ( ! function_exists( 'wp_tempnam' ) || ! function_exists( 'wp_get_image_editor' ) ) {
		return new WP_Error( 'ai4seo_image_editor_unavailable', 'WordPress image editing is unavailable.' );
	}

	// Give the selected editor a local source path while tracking it for unconditional cleanup.
	$source_file_path = wp_tempnam( 'ai4seo-image-source' );

	// Stop before writing when WordPress could not reserve the source path.
	if ( ! $source_file_path ) {
		return new WP_Error( 'ai4seo_image_temp_file_failed', 'Could not create a temporary image file.' );
	}

	$temporary_image_file_paths[] = $source_file_path;
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WordPress image editors require a local temporary source path.
	$written_bytes = file_put_contents( $source_file_path, $image_data );

	// Partial writes cannot produce a trustworthy image editor input.
	if ( strlen( $image_data ) !== $written_bytes ) {
		return new WP_Error( 'ai4seo_image_temp_file_write_failed', 'Could not write the complete temporary image file.' );
	}

	// Let WordPress select whichever installed backend supports both the source and derivative formats.
	return wp_get_image_editor(
		$source_file_path,
		array(
			'mime_type'        => $source_mime_type,
			'output_mime_type' => $derivative_mime_type,
		)
	);
}


/**
 * Canonicalize a backend-selected derivative path within its reserved temporary namespace.
 *
 * WordPress image editors may keep the reserved placeholder filename, append the requested
 * extension, or replace the placeholder extension. No other sibling or external path is owned
 * by this conversion and therefore no other path may be read, tracked, or deleted.
 *
 * @param string $reserved_path        Temporary placeholder reserved by this conversion.
 * @param string $candidate_path       Derivative path returned by the image editor.
 * @param string $derivative_mime_type Requested derivative MIME type.
 * @return string|WP_Error Canonical owned path, or an error for an unowned path.
 */
function ai4seo_get_owned_temporary_derivative_path(
	string $reserved_path,
	string $candidate_path,
	string $derivative_mime_type
) {
	$reserved_directory = realpath( dirname( $reserved_path ) );
	$canonical_path     = realpath( $candidate_path );

	// Both the reserved namespace and the returned derivative must resolve before ownership is asserted.
	if ( false === $reserved_directory || false === $canonical_path || ! is_file( $canonical_path ) ) {
		return new WP_Error( 'ai4seo_image_derivative_path_unowned', 'The generated image derivative path is not owned by this conversion.' );
	}

	$normalized_reserved_directory  = wp_normalize_path( $reserved_directory );
	$normalized_candidate_directory = wp_normalize_path( dirname( $canonical_path ) );
	$reserved_basename              = basename( wp_normalize_path( $reserved_path ) );
	$candidate_basename             = basename( wp_normalize_path( $canonical_path ) );

	// Windows paths are case-insensitive, while canonical paths on other supported hosts retain case significance.
	if ( '\\' === DIRECTORY_SEPARATOR ) {
		$normalized_reserved_directory  = strtolower( $normalized_reserved_directory );
		$normalized_candidate_directory = strtolower( $normalized_candidate_directory );
		$reserved_basename              = strtolower( $reserved_basename );
		$candidate_basename             = strtolower( $candidate_basename );
	}

	// A returned path outside the exact reserved temporary directory is never safe to consume or delete.
	if ( $normalized_reserved_directory !== $normalized_candidate_directory ) {
		return new WP_Error( 'ai4seo_image_derivative_path_unowned', 'The generated image derivative path is not owned by this conversion.' );
	}

	$allowed_extensions  = array(
		'image/jpeg' => array( 'jpeg', 'jpg' ),
		'image/png'  => array( 'png' ),
	);
	$candidate_extension = strtolower( (string) pathinfo( $candidate_basename, PATHINFO_EXTENSION ) );
	$candidate_stem      = (string) pathinfo( $candidate_basename, PATHINFO_FILENAME );
	$reserved_stem       = (string) pathinfo( $reserved_basename, PATHINFO_FILENAME );
	$is_reserved_name    = $candidate_basename === $reserved_basename;
	$is_expected_variant = in_array( $candidate_extension, $allowed_extensions[ $derivative_mime_type ] ?? array(), true )
		&& in_array( $candidate_stem, array( $reserved_basename, $reserved_stem ), true );

	// Permit only the placeholder itself or the two extension variants used by core image backends.
	if ( ! $is_reserved_name && ! $is_expected_variant ) {
		return new WP_Error( 'ai4seo_image_derivative_path_unowned', 'The generated image derivative path is not owned by this conversion.' );
	}

	return $canonical_path;
}


/**
 * Save the current image-editor state to a tracked temporary derivative.
 *
 * @param WP_Image_Editor $image_editor               Loaded WordPress image editor.
 * @param string          $derivative_mime_type       Requested output MIME type.
 * @param array           $temporary_image_file_paths Temporary paths that must be deleted by the caller.
 * @return array|WP_Error
 */
function ai4seo_save_temporary_image_derivative(
	$image_editor,
	string $derivative_mime_type,
	array &$temporary_image_file_paths
) {
	// Reserve a predictable local output path and return a normalized error if that is unavailable.
	$derivative_placeholder_path = wp_tempnam( 'ai4seo-image-derivative' );

	// Stop before saving when WordPress could not reserve the derivative path.
	if ( ! $derivative_placeholder_path ) {
		return new WP_Error( 'ai4seo_image_derivative_temp_file_failed', 'Could not create a temporary derivative file.' );
	}

	// The placeholder itself was reserved by this conversion and is always safe to clean up.
	$canonical_placeholder_path   = realpath( $derivative_placeholder_path );
	$derivative_placeholder_path  = false === $canonical_placeholder_path
		? $derivative_placeholder_path
		: $canonical_placeholder_path;
	$temporary_image_file_paths[] = $derivative_placeholder_path;
	$saved_derivative             = $image_editor->save( $derivative_placeholder_path, $derivative_mime_type );

	// Canonicalize and track only backend paths derived from this conversion's reserved filename.
	if ( ! is_wp_error( $saved_derivative )
		&& isset( $saved_derivative['path'] )
		&& is_string( $saved_derivative['path'] )
		&& '' !== $saved_derivative['path']
		&& is_file( $saved_derivative['path'] ) ) {
		$owned_derivative_path = ai4seo_get_owned_temporary_derivative_path(
			$derivative_placeholder_path,
			$saved_derivative['path'],
			$derivative_mime_type
		);

		if ( is_wp_error( $owned_derivative_path ) ) {
			return $owned_derivative_path;
		}

		$saved_derivative['path']     = $owned_derivative_path;
		$temporary_image_file_paths[] = $owned_derivative_path;
	}

	return $saved_derivative;
}


/**
 * Determine whether source pixels can be decoded within absolute and current-memory safeguards.
 *
 * @param int         $width          Image width in pixels.
 * @param int         $height         Image height in pixels.
 * @param string|null $failure_reason Optional reason when the budget is exceeded.
 * @return bool Whether a decoder can be opened within the shared budget.
 */
function ai4seo_image_dimensions_fit_decode_budget( int $width, int $height, ?string &$failure_reason = null ): bool {
	$failure_reason = '';
	if ( $width < 1 || $height < 1 ) {
		$failure_reason = 'dimensions_unavailable';
		return false;
	}

	// Mirror RobHub's conservative true-colour estimate while retaining PHP memory headroom.
	$decode_bytes_per_pixel        = 8;
	$memory_reserve_bytes          = 32 * 1024 * 1024;
	$absolute_decode_budget        = 256 * 1024 * 1024;
	$memory_limit                  = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
	$available_decode_budget       = $absolute_decode_budget;
	$maximum_pixels_from_budget    = intdiv( $available_decode_budget, $decode_bytes_per_pixel );
	$maximum_width_for_this_height = intdiv( $maximum_pixels_from_budget, $height );

	// Classify the fixed ceiling first, even when the current runtime has less available memory.
	if ( $width > $maximum_width_for_this_height ) {
		$failure_reason = 'pixel_limit';
		return false;
	}

	// Even an unlimited PHP runtime retains a bounded source-canvas allocation ceiling.
	if ( $memory_limit > 0 ) {
		$available_memory              = $memory_limit
			- memory_get_usage( true )
			- $memory_reserve_bytes;
		$available_decode_budget       = min( $available_decode_budget, max( 0, $available_memory ) );
		$maximum_pixels_from_budget    = intdiv( $available_decode_budget, $decode_bytes_per_pixel );
		$maximum_width_for_this_height = intdiv( $maximum_pixels_from_budget, $height );
	}

	if ( $width > $maximum_width_for_this_height ) {
		$failure_reason = 'memory_limit';
		return false;
	}

	return true;
}


/**
 * Delete temporary image sources and derivatives.
 *
 * @param array $temporary_image_file_paths Temporary paths created during image conversion.
 * @return void
 */
function ai4seo_delete_temporary_image_files( array $temporary_image_file_paths ): void {
	// De-duplicate backend paths before removing every source, placeholder, and derivative still present.
	foreach ( array_unique( $temporary_image_file_paths ) as $temporary_image_file_path ) {
		// Ignore invalid or already-removed entries while cleaning every remaining local file.
		if ( is_string( $temporary_image_file_path ) && '' !== $temporary_image_file_path && file_exists( $temporary_image_file_path ) ) {
			wp_delete_file( $temporary_image_file_path );
		}
	}
}


/**
 * Scale image dimensions while keeping each side valid for WordPress image editors.
 *
 * @param array $current_dimensions Current width and height.
 * @param float $scale              Proportional scale to apply.
 * @return array{width: int, height: int} Scaled dimensions.
 */
function ai4seo_get_scaled_image_dimensions( array $current_dimensions, float $scale ): array {
	// Preserve the aspect ratio and prevent rounding from producing an invalid zero-sized side.
	return array(
		'width'  => max( 1, (int) floor( $current_dimensions['width'] * $scale ) ),
		'height' => max( 1, (int) floor( $current_dimensions['height'] * $scale ) ),
	);
}


/**
 * Resolve source dimensions before WordPress allocates an image-editor canvas.
 *
 * The fetched bytes are the only safe authority. Attachment metadata can describe a stale or
 * filtered source and therefore must never authorize a decoded image allocation.
 *
 * @param string $image_data       Raw source image bytes.
 * @param string $source_mime_type MIME type detected from the source bytes.
 * @return array{width: int, height: int}|false Safe dimensions, or false when unavailable.
 */
function ai4seo_get_source_image_dimensions_before_decode( string $image_data, string $source_mime_type = '' ) {
	if ( function_exists( 'getimagesizefromstring' ) ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed or unsupported image bytes are an expected probe failure; callers fail closed when dimensions are unavailable.
		$image_info = @getimagesizefromstring( $image_data );

		if ( is_array( $image_info ) ) {
			$parsed_width  = absint( $image_info[0] ?? 0 );
			$parsed_height = absint( $image_info[1] ?? 0 );

			if ( $parsed_width > 0 && $parsed_height > 0 ) {
				return array(
					'width'  => $parsed_width,
					'height' => $parsed_height,
				);
			}
		}
	}

	// PHP versions before 8.2 can recognize AVIF while reporting zero dimensions. WordPress'
	// bounded AVIF parser reads an owned local file without allocating a decoded pixel canvas.
	if ( 'image/avif' === ai4seo_normalize_mime_type_string( $source_mime_type ) ) {
		return ai4seo_get_avif_source_image_dimensions_from_owned_temporary_file( $image_data );
	}

	return false;
}


/**
 * Read AVIF dimensions through WordPress' container parser using an operation-owned temp file.
 *
 * @param string $image_data Raw AVIF bytes.
 * @return array{width: int, height: int}|false Parsed dimensions, or false on any failure.
 */
function ai4seo_get_avif_source_image_dimensions_from_owned_temporary_file( string $image_data ) {
	if ( ! function_exists( 'wp_tempnam' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( ! function_exists( 'wp_tempnam' ) || ! function_exists( 'wp_get_avif_info' ) ) {
		return false;
	}

	$source_file_path = wp_tempnam( 'ai4seo-avif-source-inspection' );

	if ( ! is_string( $source_file_path ) || '' === $source_file_path ) {
		return false;
	}

	try {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The core AVIF parser requires an operation-owned local source path.
		$written_bytes = file_put_contents( $source_file_path, $image_data );

		if ( strlen( $image_data ) !== $written_bytes ) {
			return false;
		}

		$avif_info = wp_get_avif_info( $source_file_path );
		$width     = absint( $avif_info['width'] ?? 0 );
		$height    = absint( $avif_info['height'] ?? 0 );

		if ( $width < 1 || $height < 1 ) {
			return false;
		}

		return array(
			'width'  => $width,
			'height' => $height,
		);
	} finally {
		ai4seo_delete_temporary_image_files( array( $source_file_path ) );
	}
}


/**
 * Read URL tokens from srcset without treating descriptors as image URLs.
 *
 * @param string $srcset Complete srcset attribute.
 * @return array Source URLs in document order.
 */
function ai4seo_get_generation_srcset_urls( string $srcset ): array {
	$urls   = array();
	$offset = 0;
	$length = strlen( $srcset );

	while ( $offset < $length ) {
		$offset += strspn( $srcset, " \t\r\n\f,", $offset );
		$size    = strcspn( $srcset, " \t\r\n\f", $offset );
		$url     = substr( $srcset, $offset, $size );
		$offset += $size;

		if ( '' === $url ) {
			break;
		}

		$urls[] = rtrim( $url, ',' );

		if ( ',' !== substr( $url, -1 ) ) {
			// Skip the density/width descriptor up to the next candidate separator.
			$offset += strcspn( $srcset, ',', $offset );
		}
	}

	return $urls;
}


/**
 * Determine whether image dimensions satisfy a caller-supplied canvas limit.
 *
 * @param int         $width             Image width in pixels.
 * @param int         $height            Image height in pixels.
 * @param int         $maximum_dimension Maximum permitted width or height.
 * @param string|null $failure_reason    Optional reason when the budget is exceeded.
 * @return bool Whether the image fits the requested output canvas.
 */
function ai4seo_image_dimensions_fit_output_budget( int $width, int $height, int $maximum_dimension, ?string &$failure_reason = null ): bool {
	if ( ! ai4seo_image_dimensions_fit_decode_budget( $width, $height, $failure_reason ) ) {
		return false;
	}

	if ( $width > $maximum_dimension || $height > $maximum_dimension ) {
		$failure_reason = 'pixel_limit';
		return false;
	}

	return true;
}


/**
 * Read and independently validate a generated image derivative.
 *
 * The image editor's save result is advisory. The bytes on disk remain authoritative for size,
 * MIME type, dimensions, and whether WordPress can decode the derivative again.
 *
 * @param string      $derivative_path    Local derivative path returned by the image editor.
 * @param string      $required_mime_type MIME type required for the derivative.
 * @param string      $reported_mime_type MIME type reported by the image editor.
 * @param int         $maximum_size_bytes Maximum permitted derivative size.
 * @param int         $maximum_dimension  Maximum permitted width or height.
 * @param string|null $failure_stage      Safe failure-stage identifier.
 * @param string|null $failure_reason     Optional decode-budget reason.
 * @return array|WP_Error Validated derivative data, or a normalized validation error.
 */
function ai4seo_read_image_derivative(
	string $derivative_path,
	string $required_mime_type,
	string $reported_mime_type,
	int $maximum_size_bytes,
	int $maximum_dimension,
	?string &$failure_stage = null,
	?string &$failure_reason = null
) {
	$failure_stage  = '';
	$failure_reason = '';

	// A save result is not usable unless it identifies a readable local file.
	if ( '' === $derivative_path || ! is_file( $derivative_path ) || ! is_readable( $derivative_path ) ) {
		$failure_stage = 'derivative_path';
		return new WP_Error( 'ai4seo_image_derivative_path_invalid', 'Could not read the generated image derivative.' );
	}

	$derivative_size_bytes = ai4seo_get_file_size( $derivative_path );

	// Empty files and sizes that exceed the requested byte bound must never be loaded into memory.
	if ( $derivative_size_bytes <= 0 ) {
		$failure_stage = 'derivative_read';
		return new WP_Error( 'ai4seo_image_derivative_empty', 'The generated image derivative is empty.' );
	}

	if ( $maximum_size_bytes <= 0 || $derivative_size_bytes > $maximum_size_bytes ) {
		$failure_stage = 'derivative_size';
		return new WP_Error( 'ai4seo_image_derivative_too_large', 'The generated image derivative exceeds the target file size.' );
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- The derivative is a size-bounded local temporary file.
	$derivative_image_data = file_get_contents( $derivative_path );

	// Require a complete read so a partial body cannot be submitted with otherwise plausible metadata.
	if ( ! is_string( $derivative_image_data ) || strlen( $derivative_image_data ) !== $derivative_size_bytes ) {
		$failure_stage = 'derivative_read';
		return new WP_Error( 'ai4seo_image_derivative_read_failed', 'Could not read the complete generated image derivative.' );
	}

	// Compare both the requested and backend-reported types against the independently detected container.
	$required_mime_type  = ai4seo_normalize_mime_type_string( $required_mime_type ) ?? '';
	$reported_mime_type  = ai4seo_normalize_mime_type_string( $reported_mime_type ) ?? '';
	$signature_result    = ai4seo_is_probably_image_content( $derivative_image_data );
	$signature_mime_type = ai4seo_get_mime_type_from_detected_image_format(
		(string) ( $signature_result['detected_format'] ?? '' )
	);

	// Reject a recognizable incompatible container even when PHP cannot parse its dimensions.
	if ( ! empty( $signature_result['is_probably_image'] )
		&& ( $signature_mime_type !== $required_mime_type
			|| ( '' !== $reported_mime_type && $signature_mime_type !== $reported_mime_type ) ) ) {
		$failure_stage = 'derivative_mime';
		return new WP_Error( 'ai4seo_image_derivative_mime_mismatch', 'The generated image derivative has an unexpected MIME type.' );
	}

	// Dimension and MIME metadata must be available before the derivative can be trusted.
	if ( ! function_exists( 'getimagesizefromstring' ) ) {
		$failure_stage = 'derivative_metadata';
		return new WP_Error( 'ai4seo_image_derivative_metadata_unavailable', 'Image metadata inspection is unavailable.' );
	}

	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid image bytes are an expected validation result.
	$image_info = @getimagesizefromstring( $derivative_image_data );

	if ( ! is_array( $image_info )
		|| empty( $image_info[0] )
		|| empty( $image_info[1] )
		|| empty( $image_info['mime'] ) ) {
		$failure_stage = 'derivative_metadata';
		return new WP_Error( 'ai4seo_image_derivative_metadata_invalid', 'The generated image derivative has invalid metadata.' );
	}

	$actual_mime_type = ai4seo_normalize_mime_type_string( (string) $image_info['mime'] ) ?? '';

	// The sniffed type must satisfy the requested conversion and agree with any backend claim.
	if ( '' === $actual_mime_type
		|| $actual_mime_type !== $required_mime_type
		|| ( '' !== $reported_mime_type && $actual_mime_type !== $reported_mime_type ) ) {
		$failure_stage = 'derivative_mime';
		return new WP_Error( 'ai4seo_image_derivative_mime_mismatch', 'The generated image derivative has an unexpected MIME type.' );
	}

	$metadata_width  = (int) $image_info[0];
	$metadata_height = (int) $image_info[1];

	// Reject oversized pixel canvases before a backend allocates memory to reopen the compressed derivative.
	if ( ! ai4seo_image_dimensions_fit_output_budget( $metadata_width, $metadata_height, $maximum_dimension, $failure_reason ) ) {
		$failure_stage = 'decode_budget';
		return new WP_Error( 'ai4seo_image_derivative_decode_budget_exceeded', 'The generated image derivative exceeds the image decode budget.' );
	}

	// Reopen the bytes from disk through WordPress so header-only or truncated images fail locally.
	$validation_editor = wp_get_image_editor(
		$derivative_path,
		array(
			'mime_type'        => $actual_mime_type,
			'output_mime_type' => $actual_mime_type,
		)
	);

	if ( is_wp_error( $validation_editor ) ) {
		$failure_stage = 'derivative_reopen';
		return new WP_Error( 'ai4seo_image_derivative_reopen_failed', 'WordPress could not reopen the generated image derivative.' );
	}

	// Capture the reopened dimensions before releasing the backend-specific editor resource.
	$validation_dimensions = $validation_editor->get_size();
	unset( $validation_editor );

	// A decoder without dimensions did not successfully reopen the complete derivative.
	if ( ! is_array( $validation_dimensions ) ) {
		$failure_stage = 'derivative_reopen';
		return new WP_Error( 'ai4seo_image_derivative_dimensions_mismatch', 'The generated image derivative dimensions could not be verified.' );
	}

	// Compare the decoder result with the independently sniffed dimensions before returning the bytes.
	$validation_width  = (int) ( $validation_dimensions['width'] ?? 0 );
	$validation_height = (int) ( $validation_dimensions['height'] ?? 0 );

	if ( $metadata_width !== $validation_width
		|| $metadata_height !== $validation_height ) {
		$failure_stage = 'derivative_reopen';
		return new WP_Error( 'ai4seo_image_derivative_dimensions_mismatch', 'The generated image derivative dimensions could not be verified.' );
	}

	return array(
		'data'      => $derivative_image_data,
		'mime_type' => $actual_mime_type,
		'width'     => $metadata_width,
		'height'    => $metadata_height,
		'size'      => $derivative_size_bytes,
	);
}


/**
 * Encode image bytes using explicit size, canvas, quality, retry, and conversion policy.
 *
 * @param string      $image_data         The image data to encode.
 * @param string      $source_mime_type   MIME type detected from the source bytes.
 * @param array       $conversion_options Required target_size_bytes, maximum_dimension, quality, maximum_attempts,
 *                                        derivative_mime_type (image/jpeg or image/png), and force_derivative values.
 * @param string|null $encoded_mime_type  Actual MIME type of the encoded output.
 * @param string|null $failure_stage      Safe failure-stage identifier.
 * @param string|null $failure_reason     Optional decode-budget reason.
 * @return string The base64-encoded image data, or an empty string on error.
 * @throws Exception Internally caught and converted to an empty string with failure diagnostics.
 */
function ai4seo_encode_image_data_to_base64(
	string $image_data,
	string $source_mime_type,
	array $conversion_options,
	?string &$encoded_mime_type = null,
	?string &$failure_stage = null,
	?string &$failure_reason = null
): string {
	// Default to the detected source MIME; a derivative branch replaces it with a compatible format below.
	$encoded_mime_type = ai4seo_normalize_mime_type_string( $source_mime_type ) ?? '';
	$failure_stage     = '';
	$failure_reason    = '';

	// Image-editor callbacks may re-enter this helper independently of any generation adapter.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		$failure_stage = 'loop_prevented';
		ai4seo_debug_message( 241293986, 'Prevented loop', true );
		return '';
	}

	// Keep conversion bounds explicit so no generation settings enter this module.
	$target_image_size_bytes    = $conversion_options['target_size_bytes'] ?? 0;
	$maximum_dimension          = $conversion_options['maximum_dimension'] ?? 0;
	$derivative_quality         = $conversion_options['quality'] ?? 0;
	$maximum_attempts           = $conversion_options['maximum_attempts'] ?? 0;
	$temporary_image_file_paths = array();

	// Isolate backend and filesystem failures so every conversion error retains the established return contract.
	try {
		// Reject incomplete policy before opening editors or creating temporary files.
		if ( ! is_int( $target_image_size_bytes ) || $target_image_size_bytes < 1
			|| ! is_int( $maximum_dimension ) || $maximum_dimension < 1
			|| ! is_int( $derivative_quality ) || $derivative_quality < 1 || $derivative_quality > 100
			|| ! is_int( $maximum_attempts ) || $maximum_attempts < 1
			|| ! in_array( $conversion_options['derivative_mime_type'] ?? '', array( 'image/jpeg', 'image/png' ), true )
			|| ! isset( $conversion_options['force_derivative'] ) || ! is_bool( $conversion_options['force_derivative'] ) ) {
			$failure_stage = 'derivative_encode';
			throw new Exception( 'Invalid image conversion policy.' );
		}

		// Inspect every source before pass-through so compressed bytes cannot bypass canvas limits.
		$source_image_size_bytes = strlen( $image_data );
		$force_derivative        = $conversion_options['force_derivative'];
		$source_dimensions       = ai4seo_get_source_image_dimensions_before_decode( $image_data, $encoded_mime_type );

		// Reject unsafe or unknown canvases before GD or Imagick can allocate decoded pixel memory.
		if ( false === $source_dimensions ) {
			$failure_stage  = 'decode_budget';
			$failure_reason = 'dimensions_unavailable';
			throw new Exception( 'The source image dimensions could not be read.' );
		}
		if ( ! ai4seo_image_dimensions_fit_decode_budget( $source_dimensions['width'], $source_dimensions['height'], $failure_reason ) ) {
			$failure_stage = 'decode_budget';
			throw new Exception( 'The source image exceeds the safe decode budget.' );
		}

		$source_fits_output_canvas = ai4seo_image_dimensions_fit_output_budget(
			$source_dimensions['width'],
			$source_dimensions['height'],
			$maximum_dimension
		);
		$requires_derivative       = $force_derivative
			|| $source_image_size_bytes > $target_image_size_bytes
			|| ! $source_fits_output_canvas;

		if ( ! $requires_derivative ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode the validated bounded source payload for the caller.
			return base64_encode( $image_data );
		}

		// The caller selects the conversion format, including any transparency requirements.
		$derivative_mime_type = $conversion_options['derivative_mime_type'];

		$image_editor = ai4seo_get_image_editor_from_data(
			$image_data,
			$encoded_mime_type,
			$derivative_mime_type,
			$temporary_image_file_paths
		);

		// Backend-selection failures are normalized through the common conversion error path.
		if ( is_wp_error( $image_editor ) ) {
			$failure_stage = 'image_editor';
			throw new Exception( $image_editor->get_error_message() );
		}

		// Use the declared derivative quality consistently across whichever backend WordPress selected.
		$quality_result = $image_editor->set_quality( $derivative_quality );

		// Quality configuration failures make all following derivative measurements unreliable.
		if ( is_wp_error( $quality_result ) ) {
			$failure_stage = 'derivative_encode';
			throw new Exception( $quality_result->get_error_message() );
		}

		// Capture dimensions after loading because the correction loop always scales the current editor state.
		$current_dimensions = $image_editor->get_size();

		// Reject editor states that cannot support proportional resizing.
		if ( empty( $current_dimensions['width'] ) || empty( $current_dimensions['height'] ) ) {
			$failure_stage = 'image_metadata';
			throw new Exception( 'Could not determine image dimensions.' );
		}

		// Enforce the output canvas and byte estimate together, then correct against measured bytes below.
		$initial_scale = min(
			1,
			$maximum_dimension / $current_dimensions['width'],
			$maximum_dimension / $current_dimensions['height']
		);

		if ( $source_image_size_bytes > $target_image_size_bytes ) {
			$initial_scale = min( $initial_scale, sqrt( $target_image_size_bytes / $source_image_size_bytes ) );
		}

		if ( $initial_scale < 1 ) {
			$initial_dimensions = ai4seo_get_scaled_image_dimensions( $current_dimensions, $initial_scale );

			// Avoid a no-op resize when integer rounding retains the loaded dimensions.
			if ( $initial_dimensions['width'] < $current_dimensions['width'] || $initial_dimensions['height'] < $current_dimensions['height'] ) {
				$resize_result = $image_editor->resize(
					$initial_dimensions['width'],
					$initial_dimensions['height'],
					false
				);

				// Surface backend-specific resizing failures through the shared conversion error contract.
				if ( is_wp_error( $resize_result ) ) {
					$failure_stage = 'derivative_resize';
					throw new Exception( $resize_result->get_error_message() );
				}
			}
		}

		// Re-encode only a bounded number of times while using measured derivative bytes for correction.
		for ( $encoding_attempt = 1; $encoding_attempt <= $maximum_attempts; ++$encoding_attempt ) {
			$saved_derivative = ai4seo_save_temporary_image_derivative(
				$image_editor,
				$derivative_mime_type,
				$temporary_image_file_paths
			);

			// Normalize image-editor save errors before attempting to read an output path.
			if ( is_wp_error( $saved_derivative ) ) {
				$failure_stage = 'derivative_save';
				throw new Exception( $saved_derivative->get_error_message() );
			}

			// Use the actual path returned by the selected backend rather than assuming the placeholder extension.
			$derivative_path = $saved_derivative['path'] ?? '';

			// A successful save result still needs a readable local derivative path.
			if ( ! is_string( $derivative_path )
				|| '' === $derivative_path
				|| ! is_file( $derivative_path )
				|| ! is_readable( $derivative_path ) ) {
				$failure_stage = 'derivative_path';
				throw new Exception( 'Could not read the generated image derivative.' );
			}

			// Measure the file before loading it so oversized attempts can be corrected without another allocation.
			$derivative_image_size_bytes = ai4seo_get_file_size( $derivative_path );

			if ( $derivative_image_size_bytes <= 0 ) {
				$failure_stage = 'derivative_read';
				throw new Exception( 'The generated image derivative is empty.' );
			}

			// The first size-compliant derivative must also pass independent MIME and decoder validation.
			if ( $derivative_image_size_bytes <= $target_image_size_bytes ) {
				// Release the source decoder before validation opens a second potentially large pixel canvas.
				unset( $image_editor );

				$validated_derivative = ai4seo_read_image_derivative(
					$derivative_path,
					$derivative_mime_type,
					(string) ( $saved_derivative['mime-type'] ?? '' ),
					$target_image_size_bytes,
					$maximum_dimension,
					$failure_stage,
					$failure_reason
				);

				if ( is_wp_error( $validated_derivative ) ) {
					throw new Exception( $validated_derivative->get_error_message() );
				}

				$encoded_mime_type     = $validated_derivative['mime_type'];
				$derivative_image_data = $validated_derivative['data'];
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode the bounded derivative for the caller.
				return base64_encode( $derivative_image_data );
			}

			// Stop at the configured bound instead of performing an unbounded size-correction cycle.
			if ( $maximum_attempts === $encoding_attempt ) {
				$failure_stage = 'derivative_size';
				throw new Exception( 'Could not reduce the image derivative to the target file size.' );
			}

			// Add a small reduction margin so the next derivative converges below the byte target.
			$current_dimensions   = $image_editor->get_size();
			$corrective_scale     = min( 0.95, sqrt( $target_image_size_bytes / $derivative_image_size_bytes ) * 0.95 );
			$corrected_dimensions = ai4seo_get_scaled_image_dimensions( $current_dimensions, $corrective_scale );

			// Stop when integer rounding leaves both dimensions unchanged.
			if ( $current_dimensions['width'] === $corrected_dimensions['width']
				&& $current_dimensions['height'] === $corrected_dimensions['height'] ) {
				$failure_stage = 'derivative_resize';
				throw new Exception( 'Could not reduce the image derivative dimensions further.' );
			}

			$resize_result = $image_editor->resize(
				$corrected_dimensions['width'],
				$corrected_dimensions['height'],
				false
			);

			// Any corrective resize failure makes the conversion attempt unusable.
			if ( is_wp_error( $resize_result ) ) {
				$failure_stage = 'derivative_resize';
				throw new Exception( $resize_result->get_error_message() );
			}
		}
	} catch ( Throwable $e ) {
		// Keep backend failures observable while preserving the established empty-string error contract.
		if ( '' === $failure_stage ) {
			$failure_stage = 'derivative_encode';
		}

		ai4seo_debug_message( 578877568, $e->getMessage(), true );
		return '';
	} finally {
		// Always clean backend-generated files, including early returns and failed conversions.
		ai4seo_delete_temporary_image_files( $temporary_image_file_paths );
	}

	// Every bounded attempt returns or throws above; retain the declared string contract defensively.
	return '';
}


/**
 * Read bounded image bytes from a local path whose authority has been checked by the caller.
 *
 * @param string $local_path      Validated local file path.
 * @param int    $max_source_size Maximum source size in bytes.
 * @return array|WP_Error Image data and detected MIME type, or a source error.
 */
function ai4seo_get_image_data_from_local_file( string $local_path, int $max_source_size ) {
	if ( $max_source_size < 1 || $max_source_size >= PHP_INT_MAX ) {
		return new WP_Error( 'ai4seo_image_size_limit_invalid', 'Image source size limit must be positive and bounded.' );
	}
	if ( ! is_file( $local_path ) || ! is_readable( $local_path ) ) {
		return new WP_Error( 'ai4seo_image_source_empty', 'Local image source is unavailable.' );
	}
	if ( ai4seo_is_file_larger_than( $local_path, $max_source_size ) ) {
		return new WP_Error( 'ai4seo_fetch_too_large', 'Image source exceeds the requested size limit.', ai4seo_get_file_size( $local_path ) );
	}

	// Cap the actual read as well as the size preflight in case the file grows between them.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.PHP.NoSilencedErrors.Discouraged -- Read a caller-validated local file with a byte cap; disappearance is handled below without exposing its path.
	$image_body = @file_get_contents( $local_path, false, null, 0, $max_source_size + 1 );
	if ( false === $image_body || '' === $image_body ) {
		return new WP_Error( 'ai4seo_image_source_empty', 'Local image source is unavailable.' );
	}
	if ( strlen( $image_body ) > $max_source_size ) {
		return new WP_Error( 'ai4seo_fetch_too_large', 'Image source exceeds the requested size limit.', strlen( $image_body ) );
	}
	$image_probe = ai4seo_is_probably_image_content( $image_body );
	if ( empty( $image_probe['is_probably_image'] ) ) {
		return new WP_Error( 'ai4seo_image_source_invalid', 'The local content is not a valid image.' );
	}

	return array(
		'data'      => $image_body,
		'mime_type' => ai4seo_get_mime_type_from_detected_image_format( (string) ( $image_probe['detected_format'] ?? '' ) ),
	);
}


/**
 * Fetch bounded image bytes from a URL without attachment or generation policy.
 *
 * @param string $image_url       Image source URL.
 * @param int    $max_source_size Maximum source size in bytes.
 * @return array|WP_Error Image data and MIME type, or a transport/inspection error.
 */
function ai4seo_get_image_data_from_url( string $image_url, int $max_source_size ) {
	if ( $max_source_size < 1 ) {
		return new WP_Error( 'ai4seo_image_size_limit_invalid', 'Image source size limit must be positive.' );
	}

	$same_site_local_path = ai4seo_get_same_site_local_file_path_from_url( $image_url );

	// Same-site media can be measured before loading the binary into PHP memory.
	if ( $same_site_local_path && ai4seo_is_file_larger_than( $same_site_local_path, $max_source_size ) ) {
		return new WP_Error( 'ai4seo_fetch_too_large', 'Image source exceeds the requested size limit.', ai4seo_get_file_size( $same_site_local_path ) );
	}

	// Remote media with Content-Length can be rejected before the body request.
	if ( ! $same_site_local_path ) {
		$remote_content_length = ai4seo_get_remote_content_length( $image_url );

		if ( $remote_content_length > $max_source_size ) {
			return new WP_Error( 'ai4seo_fetch_too_large', 'Image source exceeds the requested size limit.', $remote_content_length );
		}
	}

	// Prefer contained local reads before one bounded SSRF-safe remote attempt; insecure retries are intentionally excluded.
	try {
		foreach ( array( 'local_only', 'remote_only' ) as $fetch_mode ) {
			$image_body = ai4seo_get_remote_body( $image_url, $fetch_mode, $max_source_size );

			if ( is_wp_error( $image_body ) ) {
				// Preserve capped-fetch and TLS errors without retrying around their safety boundaries.
				if ( 'ai4seo_fetch_too_large' === $image_body->get_error_code()
					|| 'ai4seo_tls_verification_failed' === $image_body->get_error_code() ) {
					return $image_body;
				}

				continue;
			}

			if ( ! $image_body ) {
				continue;
			}

			// Keep a final size guard in case the active WP HTTP transport does not honor limit_response_size.
			if ( strlen( $image_body ) > $max_source_size ) {
				return new WP_Error( 'ai4seo_fetch_too_large', 'Image source exceeds the requested size limit.', strlen( $image_body ) );
			}

			// Verify that the content is a valid image.
			$is_probably_image = ai4seo_is_probably_image_content( $image_body );

			if ( ! empty( $is_probably_image['is_probably_image'] ) ) {
				break;
			}
		}
	} catch ( Exception $e ) {
		return new WP_Error( 'ai4seo_image_fetch_exception', $e->getMessage() );
	}

	if ( is_wp_error( $image_body ) ) {
		return $image_body;
	}

	if ( ! $image_body ) {
		return new WP_Error( 'ai4seo_image_source_empty', 'Media content not accessible' );
	}

	if ( ! isset( $is_probably_image['is_probably_image'] ) || ! $is_probably_image['is_probably_image'] ) {
		return new WP_Error( 'ai4seo_image_source_invalid', 'The fetched content is not a valid image' );
	}

	// Normalize the signature detector output so the encoder can report whether conversion changed the format.
	$source_mime_type = ai4seo_get_mime_type_from_detected_image_format(
		(string) ( $is_probably_image['detected_format'] ?? '' )
	);

	return array(
		'data'      => $image_body,
		'mime_type' => $source_mime_type,
	);
}
