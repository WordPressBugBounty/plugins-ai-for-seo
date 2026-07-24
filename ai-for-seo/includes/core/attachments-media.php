<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region ATTACHMENTS / MEDIA =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to read and analyze the attachment attributes coverage of the given attachment ids (post ids)
 *
 * @param int|array $attachment_post_ids The post ids of the attachments we want to analyze
 * @return array
 */
function ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_post_ids ): array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 898193802, 'Prevented loop', true );
		return array();
	}

	// allow single ID.
	if ( ! is_array( $attachment_post_ids ) ) {
		$attachment_post_ids = array( $attachment_post_ids );
	}

	// initial coverage structure.
	$attachment_attributes_coverage = ai4seo_create_empty_attachment_attributes_coverage_array( $attachment_post_ids );

	// bail on empty or invalid IDs.
	if ( empty( $attachment_post_ids ) ) {
		return $attachment_attributes_coverage;
	}

	foreach ( $attachment_post_ids as $attachment_post_id ) {
		if ( ! is_numeric( $attachment_post_id ) ) {
			return $attachment_attributes_coverage;
		}
	}

	// normalize IDs.
	$attachment_post_ids = array_map( 'absint', $attachment_post_ids );

	// active attributes.
	$active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	if ( ! $active_attachment_attributes ) {
		return $attachment_attributes_coverage;
	}

	// chunk IDs to avoid huge IN-lists.
	$database_chunk_size        = ai4seo_get_database_chunk_size();
	$attachment_post_ids_chunks = array_chunk( $attachment_post_ids, $database_chunk_size );

	// --- TITLE / CAPTION / DESCRIPTION / GUID ----------------------------------------- \\

	if ( array_intersect( array( 'title', 'caption', 'description' ), $active_attachment_attributes ) ) {
		foreach ( $attachment_post_ids_chunks as $this_attachment_post_ids_chunk ) {
			if ( empty( $this_attachment_post_ids_chunk ) ) {
				continue;
			}

			$this_attachment_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_attachment_post_ids_chunk ), '%d' ) );

			$attachment_posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_excerpt, post_content, guid
                     FROM {$wpdb->posts}
                     WHERE ID IN ({$this_attachment_post_ids_placeholders})",
					$this_attachment_post_ids_chunk
				),
				ARRAY_A
			);

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321663, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! $attachment_posts ) {
				continue;
			}

			foreach ( $attachment_posts as $this_attachment_post ) {
				$this_attachment_post_id = absint( $this_attachment_post['ID'] );

				if ( in_array( 'title', $active_attachment_attributes, true ) ) {
					$attachment_attributes_coverage[ $this_attachment_post_id ]['title'] = $this_attachment_post['post_title'];
				}

				if ( in_array( 'caption', $active_attachment_attributes, true ) ) {
					$attachment_attributes_coverage[ $this_attachment_post_id ]['caption'] = $this_attachment_post['post_excerpt'];
				}

				if ( in_array( 'description', $active_attachment_attributes, true ) ) {
					$attachment_attributes_coverage[ $this_attachment_post_id ]['description'] = $this_attachment_post['post_content'];
				}

				// file-name if needed in the future:
				// $file_name = substr( $attachment_post['guid'], strrpos( $attachment_post['guid'], '/' ) + 1 );
				// $attachment_attributes_coverage[ $this_id ]['file-name'] = $file_name;.
			}
		}
	}

	// --- ALT TEXT --------------------------------------------------------------------- \\

	if ( in_array( 'alt-text', $active_attachment_attributes, true ) ) {
		foreach ( $attachment_post_ids_chunks as $this_post_ids_chunk ) {
			if ( empty( $this_post_ids_chunk ) ) {
				continue;
			}

			$this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

			$this_attachment_postmetas = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders})",
					...array_merge( array( '_wp_attachment_image_alt' ), $this_post_ids_chunk )
				),
				ARRAY_A
			);

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321664, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( empty( $this_attachment_postmetas ) ) {
				continue;
			}

			foreach ( $this_attachment_postmetas as $this_attachment_postmeta ) {
				$this_attachment_post_id = absint( $this_attachment_postmeta['post_id'] );
				$attachment_attributes_coverage[ $this_attachment_post_id ]['alt-text'] = strval( $this_attachment_postmeta['meta_value'] );
			}
		}
	}

	return $attachment_attributes_coverage;
}

// =========================================================================================== \\

/**
 * Function to return the summary of the attachment attributes coverage array
 *
 * @param array $attachment_attributes_coverage The attachment attributes coverage array generated by ai4seo_read_and_analyze_attachment_attributes_coverage().
 * @return array The summary of the attachment attributes coverage array, basically the amount of filled attachment attributes per attachment
 */
function ai4seo_get_attachment_attributes_coverage_summary( array $attachment_attributes_coverage ): array {
	// generate a summary of the attachment attributes coverage array.
	$attachment_attributes_coverage_summary = array();

	if ( ! $attachment_attributes_coverage ) {
		return $attachment_attributes_coverage_summary;
	}

	foreach ( $attachment_attributes_coverage as $attachment_post_id => $attachment_attributes ) {
		$attachment_attributes_coverage_summary[ $attachment_post_id ] = 0;

		foreach ( $attachment_attributes as $this_attachment_attribute ) {
			if ( $this_attachment_attribute ) {
				++$attachment_attributes_coverage_summary[ $attachment_post_id ];
			}
		}
	}

	return $attachment_attributes_coverage_summary;
}

// =========================================================================================== \\

/**
 * Function to create an empty attachment attributes coverage array
 *
 * @param array $attachment_post_ids The post ids of the attachments we want to analyze.
 * @return array The empty attachment attributes coverage array
 */
function ai4seo_create_empty_attachment_attributes_coverage_array( array $attachment_post_ids ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 155352411, 'Prevented loop', true );
		return array();
	}

	// make sure all entries of post_ids are numeric.
	foreach ( $attachment_post_ids as $attachment_post_id ) {
		if ( ! is_numeric( $attachment_post_id ) ) {
			return array();
		}
	}

	// Make sure that all parameters are not empty.
	if ( empty( $attachment_post_ids ) ) {
		return array();
	}

	if ( ! defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) ) {
		return array();
	}

	$active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	if ( ! $active_attachment_attributes ) {
		return array();
	}

	// build an array that holds track of which attachment_attributes are covered by the given posts.
	$attachment_attributes_coverage = array();

	foreach ( $attachment_post_ids as $post_id ) {
		$attachment_attributes_coverage[ $post_id ] = array();

		foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details ) {
			if ( ! in_array( $this_attachment_attribute_identifier, $active_attachment_attributes ) ) {
				continue;
			}

			$attachment_attributes_coverage[ $post_id ][ $this_attachment_attribute_identifier ] = '';
		}
	}

	return $attachment_attributes_coverage;
}

// =========================================================================================== \\

/**
 * Checks if the metadata for a given post is fully covered
 *
 * @param int $attachment_post_id The post id to check the metadata coverage for.
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_are_attachment_attributes_fully_covered( int $attachment_post_id ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 346802262, 'Prevented loop', true );
		return true;
	}

	// get the total amount of attachment attributes.
	$active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	if ( ! $active_attachment_attributes ) {
		return true;
	}

	$num_active_and_covered_attachment_attributes = 0;

	// get existing attributes coverage.
	$attachment_attributes_coverage      = ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_post_id );
	$this_attachment_attributes_coverage = $attachment_attributes_coverage[ $attachment_post_id ] ?? array();

	foreach ( $active_attachment_attributes as $this_attachment_attribute ) {
		if ( $this_attachment_attributes_coverage[ $this_attachment_attribute ] ) {
			++$num_active_and_covered_attachment_attributes;
		}
	}

	$attachment_attributes_coverage_percentage = ( $num_active_and_covered_attachment_attributes / count( $active_attachment_attributes ) ) * 100;

	return ( 100 == $attachment_attributes_coverage_percentage );
}

// =========================================================================================== \\

/**
 * Returns the number of active attachment attributes
 *
 * @return int the number of active attachment attributes
 */
function ai4seo_get_active_num_attachment_attributes(): int {
	return count( ai4seo_get_active_attachment_attributes() );
}

// =========================================================================================== \\

/**
 * Returns the attachment attributes for a specific attachment post id
 *
 * @param int $attachment_post_id The post id of the attachment.
 * @return array The attachment attributes
 */
function ai4seo_read_available_attachment_attributes( int $attachment_post_id ): array {
	// Read attachment title, caption, description, alt-text and file-path.
	$ai4seo_this_attachment_post                                  = get_post( $attachment_post_id );
	$ai4seo_this_post_attachment_attributes_values['title']       = $ai4seo_this_attachment_post->post_title ?? '';
	$ai4seo_this_post_attachment_attributes_values['caption']     = $ai4seo_this_attachment_post->post_excerpt ?? '';
	$ai4seo_this_post_attachment_attributes_values['description'] = $ai4seo_this_attachment_post->post_content ?? '';
	$ai4seo_this_post_attachment_attributes_values['alt-text']    = get_post_meta( $attachment_post_id, '_wp_attachment_image_alt', true ) ?? '';
	// $ai4seo_this_attachment_post_details["file-name"] = basename(get_attached_file($attachment_post_id)) ?? "";

	return $ai4seo_this_post_attachment_attributes_values;
}

// =========================================================================================== \\

/**
 * Refreshes the attachment attributes coverage for the given post by putting the post id into the corresponding option
 *
 * @param int          $attachment_post_id The post id to refresh the attachment attributes coverage for.
 * @param WP_Post|null $post The post object to refresh the attachment attributes coverage for.
 * @return void
 */
function ai4seo_refresh_one_posts_attachment_attributes_coverage( int $attachment_post_id, $post = null ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 393113591, 'Prevented loop', true );
		return;
	}

	if ( ! is_numeric( $attachment_post_id ) ) {
		return;
	}

	if ( ! ai4seo_is_post_a_valid_attachment( $attachment_post_id, $post ) ) {
		ai4seo_remove_post_ids_from_all_options( $attachment_post_id );
		return;
	}

	// consider which option to put the post id into.
	if ( ai4seo_are_attachment_attributes_fully_covered( $attachment_post_id ) ) {
		ai4seo_add_post_ids_to_option( AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );

		// check if the post has generated data.
		if ( ai4seo_post_has_generated_data( $attachment_post_id ) ) {
			ai4seo_add_post_ids_to_option( AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
		}
	} else {
		ai4seo_add_post_ids_to_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id );
	}
}

// =========================================================================================== \\

/**
 * This function checks if an attachment is valid for our plugin to be considered
 *
 * @param int          $attachment_post_id The post id to check.
 * @param WP_Post|null $attachment_post The post object to check.
 * @return bool Whether the attachment is valid
 */
function ai4seo_is_post_a_valid_attachment( int $attachment_post_id, ?WP_Post $attachment_post = null ): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 304333735, 'Prevented loop', true );
		return false;
	}

	if ( ! is_numeric( $attachment_post_id ) ) {
		return false;
	}

	$supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

	// read post.
	if ( null === $attachment_post ) {
		$attachment_post = get_post( $attachment_post_id );
	}

	// check if the post could be read.
	if ( ! $attachment_post || is_wp_error( $attachment_post ) || ! isset( $attachment_post->post_type ) ) {
		return false;
	}

	// check if the post type is an attachment.
	if ( ! in_array( $attachment_post->post_type, $supported_attachment_post_types ) ) {
		return false;
	}

	$attachment_post_mime_type     = ai4seo_get_attachment_post_mime_type( $attachment_post_id );
	$allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();

	// check mime type.
	if ( ! in_array( $attachment_post_mime_type, $allowed_attachment_mime_types, true ) ) {
		return false;
	}

	// check post status.
	if ( ! in_array( $attachment_post->post_status, array( 'publish', 'future', 'private', 'pending', 'inherit' ) ) ) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Resolves the image source used for attachment-attribute generation.
 *
 * Existing WordPress sub-sizes are considered only when the full image dimensions are known
 * and exceed the generation limit. Missing dimensions or sub-sizes retain the full source.
 *
 * @param int         $attachment_post_id      Attachment post ID.
 * @param string|null $original_attachment_url Optional pre-resolved full attachment URL.
 * @param string|null $full_mime_type          Optional pre-resolved full attachment MIME type.
 * @return array|null {
 *     Attachment generation image source, or null when the attachment URL is unavailable.
 *
 *     @type string $original_url Full attachment URL retained for reference and context.
 *     @type string $delivery_url URL used for image delivery.
 *     @type int    $width        Delivery image width when known.
 *     @type int    $height       Delivery image height when known.
 *     @type string $mime_type    Delivery image MIME type.
 *     @type string $size_name    WordPress image size name.
 * }
 */
function ai4seo_get_attachment_generation_image_source(
	int $attachment_post_id,
	?string $original_attachment_url = null,
	?string $full_mime_type = null
): ?array {
	// Preserve the canonical full URL as request context even when a smaller delivery source is selected later.
	$attachment_post_id = absint( $attachment_post_id );

	if ( null === $original_attachment_url ) {
		$original_attachment_url = ai4seo_get_attachment_url( $attachment_post_id );
	}

	if ( ! $original_attachment_url ) {
		return null;
	}

	// Prefer WordPress metadata because it identifies the full image without opening the image binary.
	$attachment_metadata = wp_get_attachment_metadata( $attachment_post_id );
	$full_width           = 0;
	$full_height          = 0;

	if ( is_array( $attachment_metadata ) ) {
		$full_width  = absint( $attachment_metadata['width'] ?? 0 );
		$full_height = absint( $attachment_metadata['height'] ?? 0 );
	}

	// Read local image headers only when attachment metadata cannot identify the full dimensions.
	if ( $full_width <= 0 || $full_height <= 0 ) {
		$attached_file = get_attached_file( $attachment_post_id );

		if ( is_string( $attached_file ) && is_file( $attached_file ) && is_readable( $attached_file ) ) {
			$full_image_size = wp_getimagesize( $attached_file );

			if ( is_array( $full_image_size ) ) {
				$full_width  = absint( $full_image_size[0] ?? 0 );
				$full_height = absint( $full_image_size[1] ?? 0 );
			}
		}
	}

	// Build the unchanged fallback descriptor once so every soft-selection failure returns the same full source.
	if ( null === $full_mime_type ) {
		$full_mime_type = ai4seo_get_attachment_post_mime_type( $attachment_post_id ) ?? '';
	}

	$full_image_source = array(
		'original_url' => $original_attachment_url,
		'delivery_url' => $original_attachment_url,
		'width'        => $full_width,
		'height'       => $full_height,
		'mime_type'    => $full_mime_type,
		'size_name'    => 'full',
	);

	// Soft selection requires known oversized full dimensions; every other case keeps today's full source.
	if ( $full_width <= 0 || $full_height <= 0
		|| ( $full_width <= AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION
			&& $full_height <= AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION )
	) {
		return $full_image_source;
	}

	// Try only existing core image sizes in descending preference; WordPress must not create variants during generation.
	$allowed_mime_types = ai4seo_get_allowed_attachment_mime_types();

	foreach ( array( '2048x2048', '1536x1536', 'large' ) as $image_size_name ) {
		$candidate_image_source = wp_get_attachment_image_src( $attachment_post_id, $image_size_name, false );

		// Reject malformed filter results and the full-image fallback before coercing tuple values.
		if ( ! is_array( $candidate_image_source )
			|| ! isset(
				$candidate_image_source[0],
				$candidate_image_source[1],
				$candidate_image_source[2],
				$candidate_image_source[3]
			)
			|| ! is_string( $candidate_image_source[0] )
			|| '' === trim( $candidate_image_source[0] )
			|| ! is_numeric( $candidate_image_source[1] )
			|| ! is_numeric( $candidate_image_source[2] )
			|| true !== $candidate_image_source[3]
		) {
			continue;
		}

		// Trust the filtered WordPress source tuple only when its URL and actual reported dimensions fit the delivery limit.
		$candidate_url    = (string) $candidate_image_source[0];
		$candidate_width  = absint( $candidate_image_source[1] ?? 0 );
		$candidate_height = absint( $candidate_image_source[2] ?? 0 );

		if ( false === filter_var( $candidate_url, FILTER_VALIDATE_URL )
			|| $candidate_width <= 0
			|| $candidate_height <= 0
			|| $candidate_width > AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION
			|| $candidate_height > AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION
		) {
			continue;
		}

		// Prefer the sub-size MIME metadata, then infer from its filtered URL, while retaining the
		// full MIME as a safe fallback.
		$candidate_mime_type = '';

		if ( is_array( $attachment_metadata ) && isset( $attachment_metadata['sizes'][ $image_size_name ]['mime-type'] ) ) {
			$candidate_mime_type = ai4seo_normalize_mime_type_string(
				(string) $attachment_metadata['sizes'][ $image_size_name ]['mime-type']
			);
		}

		if ( ! $candidate_mime_type ) {
			$candidate_mime_type = ai4seo_get_mime_type_from_url( $candidate_url );
		}

		if ( ! in_array( $candidate_mime_type, $allowed_mime_types, true ) ) {
			$candidate_mime_type = $full_mime_type;
		}

		// Return the first eligible candidate while keeping the full URL available as the stable reference.
		return array(
			'original_url' => $original_attachment_url,
			'delivery_url' => $candidate_url,
			'width'        => $candidate_width,
			'height'       => $candidate_height,
			'mime_type'    => $candidate_mime_type,
			'size_name'    => $image_size_name,
		);
	}

	// Keep the optimization soft when none of the existing core sizes satisfies every candidate requirement.
	return $full_image_source;
}

// =========================================================================================== \\

/**
 * Calls the attachment-attribute generation endpoint with one preselected image source.
 *
 * @param array $attachment_image_source Attachment image source descriptor.
 * @param array $robhub_api_call_parameters RobHub API parameters.
 * @return array RobHub API response.
 */
function ai4seo_call_attachment_attributes_generation_api(
	array $attachment_image_source,
	array $robhub_api_call_parameters
): array {
	// Validate the complete descriptor before either transport can allocate or fetch image data.
	$original_url = (string) ( $attachment_image_source['original_url'] ?? '' );
	$delivery_url = (string) ( $attachment_image_source['delivery_url'] ?? '' );
	$mime_type    = (string) ( $attachment_image_source['mime_type'] ?? '' );

	if ( ! $original_url || ! $delivery_url || ! $mime_type ) {
		return array(
			'success' => false,
			'message' => 'Attachment image source is incomplete',
			'code'    => 361324726,
		);
	}

	// Apply the existing user/automatic transport policy to the selected delivery URL rather than the full reference URL.
	if ( ai4seo_should_use_base64_image( $delivery_url ) ) {
		return ai4seo_generate_attachment_attributes_using_base64(
			$attachment_image_source,
			$robhub_api_call_parameters
		);
	}

	// Send both identities so RobHub can analyze the delivery variant while retaining full-image context.
	$robhub_api_call_parameters['attachment_url']           = $delivery_url;
	$robhub_api_call_parameters['reference_attachment_url'] = $original_url;

	return ai4seo_robhub_api()->call( 'ai4seo/generate-all-attachment-attributes', $robhub_api_call_parameters );
}

// =========================================================================================== \\

/**
 * Generates attachment attributes from a base64 representation of the selected image source.
 *
 * @param array $attachment_image_source Attachment image source descriptor.
 * @param array $robhub_api_call_parameters RobHub API parameters.
 * @return array RobHub API response.
 */
function ai4seo_generate_attachment_attributes_using_base64(
	array $attachment_image_source,
	array $robhub_api_call_parameters
): array {
	// Retain the existing recursion safeguard now that manual and cron generation share this transport helper.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 868051563, 'Prevented loop', true );
		return array(
			'success' => false,
			'message' => 'Prevented infinite loop',
			'code'    => 361324724,
		);
	}

	// Keep the full reference separate while fetching and encoding only the selected delivery source.
	$original_url = (string) ( $attachment_image_source['original_url'] ?? '' );
	$delivery_url = (string) ( $attachment_image_source['delivery_url'] ?? '' );
	$mime_type    = (string) ( $attachment_image_source['mime_type'] ?? '' );

	// Reuse the bounded image fetcher so the established 25 MB source limit remains authoritative for base64.
	$base64_from_image_file_response = ai4seo_get_base64_from_image_file( $delivery_url );

	// Normalize all fetch failures before building the data URI so callers receive the established error contract.
	if ( ! isset( $base64_from_image_file_response['success'] ) || ! $base64_from_image_file_response['success']
		|| ! isset( $base64_from_image_file_response['data'] ) || ! $base64_from_image_file_response['data'] ) {
		$base64_error_code    = (int) ( $base64_from_image_file_response['code'] ?? 361324725 );
		$base64_error_message = $base64_from_image_file_response['message'] ?? 'Unknown error';

		$results = array(
			'success' => false,
			'message' => $base64_error_message,
			'code'    => $base64_error_code,
		);

		// Preserve the user-facing interpretation error used for empty, oversized, or invalid image bodies.
		if ( in_array( $base64_error_code, array( 111324725, 131324725, 141324725, 581927126 ), true ) ) {
			$results['message'] = "Attachment '{$original_url}' could not be interpreted. "
				. 'The fetched image content is empty or invalid.';
			$results['code']    = 391014824;
		}

		return $results;
	}

	// Build the data URI with the actual encoded MIME while retaining the canonical full URL
	// for context and diagnostics.
	$attachment_base64 = $base64_from_image_file_response['data'];
	$encoded_mime_type = ai4seo_normalize_mime_type_string(
		$base64_from_image_file_response['mime_type'] ?? ''
	) ?: $mime_type;

	$robhub_api_call_parameters['reference_attachment_url'] = $original_url;
	$robhub_api_call_parameters['content']                  = "data:{$encoded_mime_type};base64,{$attachment_base64}";

	return ai4seo_robhub_api()->call( 'ai4seo/generate-all-attachment-attributes', $robhub_api_call_parameters );
}

// =========================================================================================== \\

/**
 * Creates a base64-encoded string of an image, downsizing it if necessary to fit within 3 MB.
 *
 * @param string      $image_data        The image data to encode.
 * @param string      $source_mime_type  MIME type detected from the source bytes.
 * @param string|null $encoded_mime_type Actual MIME type of the encoded output.
 * @return string The base64-encoded image data, or false if there was an error.
 */
function ai4seo_smart_image_base64_encode(
	string $image_data,
	string $source_mime_type = '',
	?string &$encoded_mime_type = null
): string {
	// Default to the detected source MIME; the GD conversion branch replaces it with JPEG below.
	$encoded_mime_type = ai4seo_normalize_mime_type_string( $source_mime_type ) ?? '';

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 241293986, 'Prevented loop', true );
		return '';
	}

	// Set the file size limit to 1 MB.
	$max_file_size = 100000; // 1 MB in bytes.

	try {
		// Get the size of the decoded image data in bytes.
		$image_size = strlen( $image_data );

		// If the image size is less than or equal to the limit, return the original image as base64.
		if ( $image_size <= $max_file_size ) {
			return base64_encode( $image_data );
		}

		// check if we can use the image functions.
		if ( ! function_exists( 'imagecreatefromstring' )
			|| ! function_exists( 'imagejpeg' )
			|| ! function_exists( 'imagecopyresampled' )
			|| ! function_exists( 'imagecreatetruecolor' )
			|| ! function_exists( 'imagedestroy' )
		) {
			throw new Exception( 'Required image functions are not available.' );
		}

		// Try to create an image from the string.
		$image = @imagecreatefromstring( $image_data );

		if ( false === $image ) {
			throw new Exception( 'Failed to create image from string.' );
		}

		// Get the original image dimensions.
		$width  = imagesx( $image );
		$height = imagesy( $image );

		// Calculate the scaling factor to downsize the image to fit within 1 MB.
		$scale      = sqrt( $max_file_size / $image_size );
		$new_width  = intval( $width * $scale );
		$new_height = intval( $height * $scale );

		// Create a new image with the new dimensions.
		$new_image = imagecreatetruecolor( $new_width, $new_height );

		if ( ! imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height ) ) {
			throw new Exception( 'Failed to resample the image.' );
		}

		// Start output buffering to capture the downsized image data.
		ob_start();

		if ( ! imagejpeg( $new_image, null, 75 ) ) { // 75 is the quality for the JPEG.
			ob_end_clean();
			throw new Exception( 'Failed to output the resized image.' );
		}

		$downsized_image_data = ob_get_contents();
		ob_end_clean();

		// imagejpeg() always changes the encoded output format regardless of the source format.
		$encoded_mime_type = 'image/jpeg';

		// Free memory.
		imagedestroy( $image );
		imagedestroy( $new_image );

		// Return the new base64-encoded image.
		return base64_encode( $downsized_image_data );

	} catch ( Exception $e ) {
		// Log the error message for debugging (WordPress style).
		ai4seo_debug_message( 578877568, $e->getMessage(), true );

		if ( function_exists( 'imagedestroy' ) && function_exists( 'is_resource' ) ) {
			// Free any allocated resources in case of an error.
			if ( isset( $image ) && is_resource( $image ) ) {
				imagedestroy( $image );
			}

			if ( isset( $new_image ) && is_resource( $new_image ) ) {
				imagedestroy( $new_image );
			}
		}

		// Return "" to indicate failure.
		return '';
	}
}

// =========================================================================================== \\

/**
 * Updates the currently active attachment attributes for an attachment
 *
 * @param int   $attachment_post_id the attachment post id
 * @param array $attachment_attribute_updates the updates to apply with the keys title, caption, description, alt-text
 * @param bool  $force_overwrite_all_existing_data if true, existing data will be overwritten, if false, we check the settings to identify the attachment attributes that should be overwritten
 * @return bool true on success, false on failure
 */
function ai4seo_update_attachment_attributes( int $attachment_post_id, array $attachment_attribute_updates = array(), bool $force_overwrite_all_existing_data = false ): bool {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 540510370, 'Prevented loop', true );
		return false;
	}

	// sanitize.
	$attachment_attribute_updates = ai4seo_deep_sanitize( $attachment_attribute_updates, 'ai4seo_sanitize_editor_field_value' );

	// handle specific overwrite existing data instruction.
	$overwrite_existing_data_attachment_attributes_names = array();

	if ( ! $force_overwrite_all_existing_data ) {
		$overwrite_existing_data_attachment_attributes_names = ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES );
	}

	$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	// read the attachment post.
	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post ) {
		return false;
	}

	// keep track if we made changes to the post.
	$we_made_changes_to_the_post = false;

	// collect post column updates for wp_posts (title/caption/description).
	$post_updates        = array();
	$post_update_formats = array();

	// third party plugins.
	$is_nextgen_gallery_active = ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY );

	if ( $is_nextgen_gallery_active ) {
		$nextgen_gallery_updates = array();
	}

	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details ) {
		if ( ! in_array( $this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes ) ) {
			continue;
		}

		$this_api_identifier = $this_attachment_attribute_details['api-identifier'] ?? '';

		if ( isset( $attachment_attribute_updates[ $this_attachment_attribute_identifier ] ) ) {
			$this_attachment_attribute_value = $attachment_attribute_updates[ $this_attachment_attribute_identifier ];
		} elseif ( $this_api_identifier && isset( $attachment_attribute_updates[ $this_api_identifier ] ) ) {
			$this_attachment_attribute_value = $attachment_attribute_updates[ $this_api_identifier ];
		} else {
			continue;
		}

		// do we overwrite this particular attachment attribute?
		if ( true === $force_overwrite_all_existing_data ) {
			$overwrite_this_attachment_attribute = true;
		} else {
			$overwrite_this_attachment_attribute = in_array( $this_attachment_attribute_identifier, $overwrite_existing_data_attachment_attributes_names );
		}

		// make sure the max length is respected.
		$this_attachment_attribute_value = ai4seo_normalize_editor_input_value( $this_attachment_attribute_value );
		$this_max_length                 = ai4seo_get_max_editor_input_length( $this_attachment_attribute_identifier );
		$this_attachment_attribute_value = ai4seo_trim_string_to_length( $this_attachment_attribute_value, $this_max_length );

		// which table do we need to update? (title, caption, description => wp_posts, alt-text => wp_postmeta).
		if ( in_array( $this_attachment_attribute_identifier, array( 'title', 'caption', 'description' ) ) ) {
			// which column do we need to update? (title => post_title, caption => post_excerpt, description => post_content).
			switch ( $this_attachment_attribute_identifier ) {
				case 'title':
					$this_post_column = 'post_title';
					break;
				case 'caption':
					$this_post_column = 'post_excerpt';
					break;
				case 'description':
					$this_post_column = 'post_content';
					break;
				default:
					continue 2;
			}

			// skip, if $overwrite_existing_data is false AND the previous value is not empty.
			if ( ! $overwrite_this_attachment_attribute && ! empty( $attachment_post->$this_post_column ) ) {
				continue;
			}

			// collect updates for direct DB update (avoid wp_update_post slashing behavior).
			$post_updates[ $this_post_column ] = $this_attachment_attribute_value;
			$post_update_formats[]             = '%s';

			// handle nextgen gallery description.
			if ( $is_nextgen_gallery_active && 'description' === $this_attachment_attribute_identifier ) {
				$nextgen_gallery_updates['description'] = $this_attachment_attribute_value;
			}

			$we_made_changes_to_the_post = true;
		} elseif ( 'alt-text' === $this_attachment_attribute_identifier ) {
			// update the postmeta table (mata_key = _wp_attachment_image_alt).
			if ( ! $overwrite_this_attachment_attribute ) {
				// if not empty -> skip.
				$existing_attachment_attribute_value = get_post_meta( $attachment_post_id, '_wp_attachment_image_alt', true );

				if ( ! empty( $existing_attachment_attribute_value ) ) {
					continue;
				}
			}

			// Propagate alt-text persistence failures so cron cannot mark a generation successful after a failed write.
			$this_success = ai4seo_update_post_meta( $attachment_post_id, '_wp_attachment_image_alt', $this_attachment_attribute_value );

			if ( ! $this_success ) {
				return false;
			}

			// handle nextgen gallery description.
			if ( $is_nextgen_gallery_active ) {
				$nextgen_gallery_updates['alttext'] = $this_attachment_attribute_value;
			}
		}
	}

	// only update the post if we made changes.
	if ( $we_made_changes_to_the_post && ! empty( $post_updates ) ) {
		$wpdb->update(
			$wpdb->posts,
			$post_updates,
			array(
				'ID' => $attachment_post_id,
			),
			$post_update_formats,
			array(
				'%d',
			)
		);

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321688, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}

		clean_post_cache( $attachment_post_id );
	}

	// handle nextgen gallery update.
	if ( $is_nextgen_gallery_active && isset( $nextgen_gallery_updates ) && $nextgen_gallery_updates ) {
		$nextgen_gallery_pid     = (int) $attachment_post->post_parent;
		$nextgen_gallery_updates = ai4seo_deep_sanitize( $nextgen_gallery_updates );
		$wpdb->update( esc_sql( $wpdb->prefix ) . 'ngg_pictures', $nextgen_gallery_updates, array( 'pid' => $nextgen_gallery_pid ) );

		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321689, 'Database error: ' . $wpdb->last_error, true );
			return false;
		}
	}

	return true;
}

// =========================================================================================== \

/**
 * Checks whether a post ID points to a WordPress attachment post.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return bool True when the post exists and is a WordPress attachment.
 */
function ai4seo_is_wordpress_attachment_post( int $attachment_post_id ): bool {
	// Keep this fallback scoped to native WordPress attachments; custom media post types use their own flows.
	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return false;
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || is_wp_error( $attachment_post ) ) {
		return false;
	}

	return ( 'attachment' === $attachment_post->post_type );
}

// =========================================================================================== \

/**
 * Checks whether a post ID can be stored as an attachment related-post fallback.
 *
 * @param int $related_post_id Related post ID.
 * @return bool True when the related post can provide image usage context.
 */
function ai4seo_is_attachment_related_post_id_eligible( int $related_post_id ): bool {
	// Store only posts that the existing usage-context system can actually use during generation.
	return ai4seo_is_attachment_context_post_eligible( absint( $related_post_id ) );
}

// =========================================================================================== \

/**
 * Normalizes a stored attachment related-post value.
 *
 * @param mixed $related_post_id_value Stored postmeta value.
 * @return int Valid related post ID, or 0 when invalid.
 */
function ai4seo_get_valid_attachment_related_post_id_from_value( $related_post_id_value ): int {
	// Accept only scalar integer-like postmeta values because this key is an int fallback cache.
	if ( is_array( $related_post_id_value ) || is_object( $related_post_id_value ) ) {
		return 0;
	}

	$related_post_id_string = trim( (string) $related_post_id_value );

	if ( '' === $related_post_id_string || ! ctype_digit( $related_post_id_string ) ) {
		return 0;
	}

	$related_post_id = absint( $related_post_id_string );

	if ( $related_post_id <= 0 || ! ai4seo_is_attachment_related_post_id_eligible( $related_post_id ) ) {
		return 0;
	}

	return $related_post_id;
}

// =========================================================================================== \

/**
 * Reads the attachment related-post fallback ID.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return int Related post ID, or 0 when missing or invalid.
 */
function ai4seo_get_attachment_related_post_id( int $attachment_post_id ): int {
	// Do not expose fallback values for non-attachment posts, even if the meta key exists there.
	$attachment_post_id = absint( $attachment_post_id );

	if ( ! ai4seo_is_wordpress_attachment_post( $attachment_post_id ) ) {
		return 0;
	}

	// Read all rows because older writes or manual edits may leave duplicate entries behind.
	$related_post_id_values = get_post_meta( $attachment_post_id, AI4SEO_POST_META_RELATED_POST_ID_META_KEY, false );

	if ( ! $related_post_id_values || ! is_array( $related_post_id_values ) ) {
		return 0;
	}

	foreach ( $related_post_id_values as $this_related_post_id_value ) {
		$this_related_post_id = ai4seo_get_valid_attachment_related_post_id_from_value( $this_related_post_id_value );

		if ( $this_related_post_id > 0 ) {
			return $this_related_post_id;
		}
	}

	return 0;
}

// =========================================================================================== \

/**
 * Checks whether all existing attachment related-post fallback rows already match.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @param int $related_post_id Related post ID.
 * @return bool True when no postmeta write is needed.
 */
function ai4seo_is_attachment_related_post_id_already_stored( int $attachment_post_id, int $related_post_id ): bool {
	// Require every row to match exactly so mismatched duplicates still get overwritten by the writer.
	$attachment_post_id     = absint( $attachment_post_id );
	$related_post_id_string = (string) absint( $related_post_id );

	if ( $attachment_post_id <= 0 || '0' === $related_post_id_string ) {
		return false;
	}

	$related_post_id_values = get_post_meta( $attachment_post_id, AI4SEO_POST_META_RELATED_POST_ID_META_KEY, false );

	if ( ! $related_post_id_values || ! is_array( $related_post_id_values ) ) {
		return false;
	}

	foreach ( $related_post_id_values as $this_related_post_id_value ) {
		if ( is_array( $this_related_post_id_value ) || is_object( $this_related_post_id_value ) ) {
			return false;
		}

		if ( trim( (string) $this_related_post_id_value ) !== $related_post_id_string ) {
			return false;
		}
	}

	return true;
}

// =========================================================================================== \

/**
 * Stores the source post ID to use as an attachment usage-context fallback.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @param int $related_post_id Related post ID.
 * @return bool True when the postmeta write completed without a database error.
 */
function ai4seo_update_attachment_related_post_id( int $attachment_post_id, int $related_post_id ): bool {
	// Validate both sides before writing so the fallback cannot point generation at unusable context.
	$attachment_post_id = absint( $attachment_post_id );
	$related_post_id    = absint( $related_post_id );

	if ( ! ai4seo_is_wordpress_attachment_post( $attachment_post_id ) ) {
		return false;
	}

	if ( ! ai4seo_is_attachment_related_post_id_eligible( $related_post_id ) ) {
		return false;
	}

	if ( ai4seo_is_attachment_related_post_id_already_stored( $attachment_post_id, $related_post_id ) ) {
		return true;
	}

	// update_post_meta without prev_value overwrites all existing rows for this key, keeping duplicates aligned.
	return ai4seo_update_post_meta(
		$attachment_post_id,
		AI4SEO_POST_META_RELATED_POST_ID_META_KEY,
		$related_post_id
	);
}

// =========================================================================================== \

/**
 * Stores one source post ID for multiple related attachment posts.
 *
 * @param array $attachment_post_ids Attachment post IDs.
 * @param int   $related_post_id Related post ID.
 * @return bool True when all valid attachment writes completed without database errors.
 */
function ai4seo_update_attachment_related_post_id_for_attachment_post_ids(
	array $attachment_post_ids,
	int $related_post_id
): bool {
	// Normalize the full scanner result before saving so table filters and pagination cannot narrow this cache.
	$attachment_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $attachment_post_ids ) ) ) );
	$related_post_id     = absint( $related_post_id );

	if ( ! $attachment_post_ids ) {
		return true;
	}

	if ( ! ai4seo_is_attachment_related_post_id_eligible( $related_post_id ) ) {
		return false;
	}

	$overall_success = true;

	// Save each attachment independently so one failed row does not block valid fallback cache entries.
	foreach ( $attachment_post_ids as $this_attachment_post_id ) {
		if ( ai4seo_update_attachment_related_post_id( $this_attachment_post_id, $related_post_id ) ) {
			continue;
		}

		$overall_success = false;
		ai4seo_debug_message(
			4527052601,
			'Could not update related post fallback for attachment post ID ' . $this_attachment_post_id . ' and related post ID ' . $related_post_id,
			true
		);
	}

	return $overall_success;
}

// =========================================================================================== \

/**
 * Returns post-related context for an attachment.
 *
 * @param int $attachment_post_id the attachment post id
 * @return string the post-related context for the attachment, including condensed content and surrounding content
 * of the first occurrence of the attachment in the content, separated by " | ".
 * Returns an empty string if no related post or content is found.
 */
function ai4seo_get_attachment_post_related_context( int $attachment_post_id ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 630085028, 'Prevented loop', true );
		return '';
	}

	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		ai4seo_debug_message( 630085031, 'Invalid attachment post ID', true );
		return '';
	}

	$attachment_url = ai4seo_get_attachment_url( $attachment_post_id );

	// try to find a post where the attachment is used (e.g. as featured image or in the content).
	$post_id = ai4seo_get_first_attachment_using_post_id( $attachment_post_id );

	if ( $post_id <= 0 ) {
		// ai4seo_debug_message(630085030, 'No post found using the attachment', true);.
		return '';
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		ai4seo_debug_message( 630085029, 'Could not read post for attachment context' );
		return '';
	}

	// POST CONTENT.
	$post_related_context = ai4seo_get_condensed_post_content_from_database( $post_id );

	// ADD POST CONTEXT.
	ai4seo_add_post_context( $post_id, $post_related_context, false );

	// FIND SURROUNDING CONTENT.
	$content_markers = array(
		'wp-image-' . $attachment_post_id,
		'"id":' . $attachment_post_id,
		'attachment_' . $attachment_post_id,
		'ids="' . $attachment_post_id,
		"ids='" . $attachment_post_id,
		"#$attachment_post_id",
	);

	if ( $attachment_url ) {
		$content_markers[] = $attachment_url;

		// basename only.
		$content_markers[] = basename( $attachment_url );

		// without file type.
		$content_markers[] = basename( $attachment_url, pathinfo( $attachment_url, PATHINFO_EXTENSION ) );
	}

	$combined_post_content     = ai4seo_get_combined_post_content( $post_id, '', true );
	$first_occurrence_position = false;

	foreach ( $content_markers as $this_marker ) {
		$this_position = ai4seo_mb_strpos( $combined_post_content, $this_marker );

		if ( false === $this_position ) {
			continue;
		}

		if ( false === $first_occurrence_position || $this_position < $first_occurrence_position ) {
			$first_occurrence_position = $this_position;
		}
	}

	$post_content_around_first_image_occurrence = '';

	if ( false !== $first_occurrence_position ) {
		$length            = 1000;
		$start             = max( 0, ( (int) $first_occurrence_position ) - $length );
		$pre_image_content = ai4seo_mb_substr( $combined_post_content, $start, $length );
		ai4seo_condense_raw_post_content( $pre_image_content, $length - 100, $length );

		$start              = ( (int) $first_occurrence_position - 16 );
		$post_image_content = ai4seo_mb_substr( $combined_post_content, $start, $length + 100 );
		ai4seo_condense_raw_post_content( $post_image_content, $length, $length + 100 );

		$post_content_around_first_image_occurrence = $pre_image_content . ' #IMAGE IS USED HERE# ' . $post_image_content;
	}

	if ( $post_content_around_first_image_occurrence ) {
		$post_related_context .= " Image surrounding content: '[...] " . $post_content_around_first_image_occurrence . " [...]'";
	}

	return $post_related_context;
}

// =========================================================================================== \

/**
 * Returns image attachment post IDs related to a specific post.
 *
 * Checks direct WordPress relations, post content, page-builder/custom postmeta values,
 * and local image URLs. Attachments may also be used by other posts.
 *
 * @param int $post_id The post id to inspect.
 * @return array Related attachment post IDs.
 */
function ai4seo_get_related_attachment_post_ids( int $post_id ): array {
	// Keep the public helper compatible with existing callers that expect a plain attachment ID list.
	$related_attachment_scan_result = ai4seo_get_related_attachment_scan_result( $post_id );

	return (array) ( $related_attachment_scan_result['attachment_post_ids'] ?? array() );
}

// =========================================================================================== \

/**
 * Returns detailed related attachment scan data for a specific post.
 *
 * @param int $post_id The post id to inspect.
 * @return array Related attachment scan result.
 */
function ai4seo_get_related_attachment_scan_result( int $post_id ): array {
	// Start with the full result shape so early returns stay compatible with the modal scanner.
	$related_attachment_scan_result = ai4seo_get_empty_related_attachment_scan_result();

	// Keep the scanner bounded to one source post per request; callers can retry later.
	if ( ai4seo_prevent_loops( __FUNCTION__, 5 ) ) {
		return $related_attachment_scan_result;
	}

	$post_id = absint( $post_id );

	// Validate the source post before scanning content and postmeta values.
	if ( $post_id <= 0 ) {
		return $related_attachment_scan_result;
	}

	$post = get_post( $post_id );

	if ( ! $post || is_wp_error( $post ) || ! isset( $post->post_type ) ) {
		return $related_attachment_scan_result;
	}

	if ( in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
		return $related_attachment_scan_result;
	}

	if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
		return $related_attachment_scan_result;
	}

	$related_attachment_post_ids = array();

	// Share lookup state across content and postmeta scans so URL resolution caps apply per modal request.
	$related_attachment_scan_state = ai4seo_get_related_attachment_scan_state();

	// Seed the result with direct WordPress relationships that do not require parsing content.
	$featured_image_post_id = get_post_thumbnail_id( $post_id );
	ai4seo_add_related_attachment_post_id( $related_attachment_post_ids, absint( $featured_image_post_id ) );

	// Include WooCommerce variation thumbnails because products can store purchasable image choices on child posts.
	ai4seo_add_related_attachment_post_ids(
		$related_attachment_post_ids,
		ai4seo_get_related_woocommerce_variation_attachment_post_ids( $post_id, (string) $post->post_type )
	);

	// Include uploaded children because WordPress stores some attachment relations through post_parent.
	$child_attachment_post_ids = ai4seo_with_wpml_all_languages(
		function () use ( $post_id ) {
			return get_posts(
				array(
					'post_parent'      => $post_id,
					'post_type'        => 'attachment',
					'post_status'      => array( 'publish', 'future', 'inherit' ),
					'post_mime_type'   => ai4seo_get_allowed_attachment_mime_types(),
					'fields'           => 'ids',
					'posts_per_page'   => -1,
					'no_found_rows'    => true,
					'orderby'          => 'ID',
					'order'            => 'DESC',
					'suppress_filters' => true,
					'lang'             => 'all',
				)
			);
		}
	);

	ai4seo_add_related_attachment_post_ids( $related_attachment_post_ids, (array) $child_attachment_post_ids );

	// Scan classic and block-editor text fields before falling back to broader postmeta inspection.
	$content_values = array(
		(string) ( $post->post_content ?? '' ),
		(string) ( $post->post_excerpt ?? '' ),
		(string) ( $post->post_content_filtered ?? '' ),
	);

	foreach ( $content_values as $this_content_value ) {
		ai4seo_add_related_attachment_post_ids(
			$related_attachment_post_ids,
			ai4seo_extract_related_attachment_post_ids_from_value( $this_content_value, 'post_content', 0, $related_attachment_scan_state )
		);
	}

	// Inspect targeted postmeta because Elementor, WooCommerce, BeTheme/Muffin, and other builders store media there.
	$scannable_post_meta_rows = ai4seo_get_related_attachment_scannable_post_meta_rows( $post_id, $related_attachment_scan_result );

	foreach ( $scannable_post_meta_rows as $this_scannable_post_meta_row ) {
		ai4seo_add_related_attachment_post_ids(
			$related_attachment_post_ids,
			ai4seo_extract_related_attachment_post_ids_from_value(
				$this_scannable_post_meta_row['meta_value'] ?? '',
				(string) ( $this_scannable_post_meta_row['meta_key'] ?? '' ),
				0,
				$related_attachment_scan_state
			)
		);
	}

	// Bubble partial state from nested URL extraction into the result consumed by the AJAX modal.
	if ( ! empty( $related_attachment_scan_state['is_partial'] ) ) {
		$related_attachment_scan_result['is_partial'] = true;
	}

	// Attach validated IDs last so detailed scan metadata and the public wrapper share one source.
	$related_attachment_scan_result['attachment_post_ids'] = array_values( $related_attachment_post_ids );

	return $related_attachment_scan_result;
}

// =========================================================================================== \

/**
 * Returns an empty related attachment scan result.
 *
 * @return array Empty scan result.
 */
function ai4seo_get_empty_related_attachment_scan_result(): array {
	return array(
		'attachment_post_ids' => array(),
		'is_partial'          => false,
		'skipped_meta_count'  => 0,
		'scanned_meta_count'  => 0,
	);
}

// =========================================================================================== \

/**
 * Returns related attachment scanner caps.
 *
 * @return array Scan limits.
 */
function ai4seo_get_related_attachment_scan_limits(): array {
	// Keep the limits conservative because this scanner runs synchronously inside an admin modal request.
	return array(
		'max_selected_meta_rows'           => 40,
		'max_total_selected_meta_bytes'    => 1048576,
		'max_single_meta_value_bytes'      => 262144,
		'max_image_url_attachment_lookups' => 50,
	);
}

// =========================================================================================== \

/**
 * Returns mutable state for one related attachment scan.
 *
 * @return array Mutable scan state.
 */
function ai4seo_get_related_attachment_scan_state(): array {
	// Keep mutable counters separate from configured limits so extractors can update one request's state.
	$related_attachment_scan_limits = ai4seo_get_related_attachment_scan_limits();

	return array(
		'image_url_attachment_lookups'     => 0,
		'max_image_url_attachment_lookups' => absint( $related_attachment_scan_limits['max_image_url_attachment_lookups'] ?? 0 ),
		'is_partial'                       => false,
	);
}

// =========================================================================================== \

/**
 * Returns selected postmeta rows that are worth scanning for related media.
 *
 * @param int   $post_id Source post ID.
 * @param array $related_attachment_scan_result Mutable scan result.
 * @return array Scannable postmeta rows.
 */
function ai4seo_get_related_attachment_scannable_post_meta_rows( int $post_id, array &$related_attachment_scan_result ): array {
	global $wpdb;

	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return array();
	}

	// Read limits once so row selection, SQL prefix loading, and PHP byte caps use the same thresholds.
	$related_attachment_scan_limits = ai4seo_get_related_attachment_scan_limits();
	$max_selected_meta_rows         = absint( $related_attachment_scan_limits['max_selected_meta_rows'] ?? 40 );
	$max_total_selected_meta_bytes  = absint( $related_attachment_scan_limits['max_total_selected_meta_bytes'] ?? 1048576 );
	$max_single_meta_value_bytes    = absint( $related_attachment_scan_limits['max_single_meta_value_bytes'] ?? 262144 );

	// Load only meta IDs and keys first; full values are fetched later only for selected media-like rows.
	$post_meta_key_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id ASC",
			$post_id
		),
		ARRAY_A
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 19747201, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	if ( ! $post_meta_key_rows || ! is_array( $post_meta_key_rows ) ) {
		return array();
	}

	// Split exact known builder/media keys from broad media-like keys so exact keys keep priority under caps.
	$exact_post_meta_keys  = ai4seo_get_related_attachment_exact_post_meta_keys();
	$exact_post_meta_ids   = array();
	$generic_post_meta_ids = array();

	foreach ( $post_meta_key_rows as $this_post_meta_key_row ) {
		$this_post_meta_id  = absint( $this_post_meta_key_row['meta_id'] ?? 0 );
		$this_post_meta_key = (string) ( $this_post_meta_key_row['meta_key'] ?? '' );

		if ( $this_post_meta_id <= 0 ) {
			continue;
		}

		if ( in_array( strtolower( trim( $this_post_meta_key ) ), $exact_post_meta_keys, true ) ) {
			$exact_post_meta_ids[] = $this_post_meta_id;
			continue;
		}

		if ( ! ai4seo_is_related_attachment_scannable_post_meta_key( $this_post_meta_key ) ) {
			continue;
		}

		$generic_post_meta_ids[] = $this_post_meta_id;
	}

	// Apply the row cap after prioritization so known builder keys are not crowded out by generic custom fields.
	$selected_post_meta_ids  = array();
	$candidate_post_meta_ids = array_merge( $exact_post_meta_ids, $generic_post_meta_ids );

	foreach ( $candidate_post_meta_ids as $this_candidate_post_meta_id ) {
		if ( count( $selected_post_meta_ids ) >= $max_selected_meta_rows ) {
			$related_attachment_scan_result['is_partial'] = true;
			++$related_attachment_scan_result['skipped_meta_count'];
			continue;
		}

		$selected_post_meta_ids[] = $this_candidate_post_meta_id;
	}

	if ( ! $selected_post_meta_ids ) {
		return array();
	}

	// Fetch only selected values, and only up to the single-value cap, to avoid loading huge builder payloads.
	$selected_post_meta_ids_placeholders = implode( ',', array_fill( 0, count( $selected_post_meta_ids ), '%d' ) );

	$post_meta_value_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_id, meta_key, LEFT(meta_value, %d) AS meta_value, LENGTH(meta_value) AS meta_value_bytes FROM {$wpdb->postmeta} WHERE meta_id IN ({$selected_post_meta_ids_placeholders}) ORDER BY meta_id ASC",
			...array_merge( array( $max_single_meta_value_bytes ), $selected_post_meta_ids )
		),
		ARRAY_A
	);

	if ( $wpdb->last_error ) {
		ai4seo_debug_message( 19747202, 'Database error: ' . $wpdb->last_error, true );
		return array();
	}

	if ( ! $post_meta_value_rows || ! is_array( $post_meta_value_rows ) ) {
		return array();
	}

	$scannable_post_meta_rows = array();
	$scanned_post_meta_bytes  = 0;

	// Enforce remaining byte caps in PHP because the aggregate cap depends on rows already accepted.
	foreach ( $post_meta_value_rows as $this_post_meta_value_row ) {
		$this_post_meta_value            = (string) ( $this_post_meta_value_row['meta_value'] ?? '' );
		$this_post_meta_value_bytes      = strlen( $this_post_meta_value );
		$this_full_post_meta_value_bytes = absint( $this_post_meta_value_row['meta_value_bytes'] ?? $this_post_meta_value_bytes );

		if ( $max_single_meta_value_bytes > 0 && $this_full_post_meta_value_bytes > $max_single_meta_value_bytes ) {
			if ( $this_post_meta_value_bytes > $max_single_meta_value_bytes ) {
				$this_post_meta_value       = substr( $this_post_meta_value, 0, $max_single_meta_value_bytes );
				$this_post_meta_value_bytes = strlen( $this_post_meta_value );
			}

			$related_attachment_scan_result['is_partial'] = true;
			++$related_attachment_scan_result['skipped_meta_count'];
		}

		if ( $max_total_selected_meta_bytes > 0 && ( $scanned_post_meta_bytes + $this_post_meta_value_bytes ) > $max_total_selected_meta_bytes ) {
			$remaining_post_meta_bytes = $max_total_selected_meta_bytes - $scanned_post_meta_bytes;

			if ( $remaining_post_meta_bytes <= 0 ) {
				$related_attachment_scan_result['is_partial'] = true;
				++$related_attachment_scan_result['skipped_meta_count'];
				continue;
			}

			$this_post_meta_value                         = substr( $this_post_meta_value, 0, $remaining_post_meta_bytes );
			$this_post_meta_value_bytes                   = strlen( $this_post_meta_value );
			$related_attachment_scan_result['is_partial'] = true;
			++$related_attachment_scan_result['skipped_meta_count'];
		}

		$scanned_post_meta_bytes += $this_post_meta_value_bytes;
		++$related_attachment_scan_result['scanned_meta_count'];

		$scannable_post_meta_rows[] = array(
			'meta_id'    => absint( $this_post_meta_value_row['meta_id'] ?? 0 ),
			'meta_key'   => (string) ( $this_post_meta_value_row['meta_key'] ?? '' ),
			'meta_value' => $this_post_meta_value,
		);
	}

	// Return only the bounded values that downstream extractors should parse.
	return $scannable_post_meta_rows;
}

// =========================================================================================== \

/**
 * Returns exact postmeta keys that can store related media.
 *
 * @return array Exact postmeta keys.
 */
function ai4seo_get_related_attachment_exact_post_meta_keys(): array {
	// Include builder keys that are known to store media even when their key names lack media terms.
	return array(
		'_thumbnail_id',
		'_product_image_gallery',
		'_elementor_data',
		'_elementor_page_settings',
		'_fl_builder_data',
		'ct_builder_shortcodes',
		'mfn-page-items-seo',
		'mfn-page-items',
	);
}

// =========================================================================================== \

/**
 * Checks whether a BeTheme/Muffin postmeta key should be scanned for related media.
 *
 * @param string $meta_key Postmeta key.
 * @return bool True if the key should be scanned.
 */
function ai4seo_is_related_attachment_betheme_post_meta_key( string $meta_key ): bool {
	$meta_key = strtolower( trim( $meta_key ) );

	// Muffin Builder stores page content in exact keys that do not include media-specific wording.
	if ( in_array( $meta_key, array( 'mfn-page-items-seo', 'mfn-page-items' ), true ) ) {
		return true;
	}

	// Broaden BeTheme/Muffin coverage only for prefixed keys whose names indicate media usage.
	if ( strpos( $meta_key, 'mfn-' ) !== 0 && strpos( $meta_key, 'mfn_' ) !== 0 ) {
		return false;
	}

	$betheme_media_terms = array(
		'image',
		'background',
		'gallery',
		'photo',
		'media',
		'thumbnail',
		'poster',
	);

	foreach ( $betheme_media_terms as $this_betheme_media_term ) {
		if ( strpos( $meta_key, $this_betheme_media_term ) !== false ) {
			return true;
		}
	}

	return false;
}

// =========================================================================================== \

/**
 * Checks whether a postmeta key should be scanned for related media.
 *
 * @param string $meta_key Postmeta key.
 * @return bool True if the key should be scanned.
 */
function ai4seo_is_related_attachment_scannable_post_meta_key( string $meta_key ): bool {
	$meta_key = strtolower( trim( $meta_key ) );

	if ( '' === $meta_key ) {
		return false;
	}

	// Exact builder/media keys are trusted even when the key name itself is not media-like.
	if ( in_array( $meta_key, ai4seo_get_related_attachment_exact_post_meta_keys(), true ) ) {
		return true;
	}

	// Keep BeTheme/Muffin Builder matching explicit so future mfn key changes are easy to audit.
	if ( ai4seo_is_related_attachment_betheme_post_meta_key( $meta_key ) ) {
		return true;
	}

	// Fall back to the generic media-term matcher used by nested value extraction.
	return ai4seo_is_attachment_like_reference_key( $meta_key );
}

// =========================================================================================== \

/**
 * Returns WooCommerce product variation thumbnail attachment IDs for a product.
 *
 * @param int    $post_id Source post ID.
 * @param string $post_type Source post type, if already known.
 * @return array Variation thumbnail attachment IDs.
 */
function ai4seo_get_related_woocommerce_variation_attachment_post_ids( int $post_id, string $post_type = '' ): array {
	// Normalize caller input before checking whether this source can own variation thumbnails.
	$post_id   = absint( $post_id );
	$post_type = '' !== $post_type ? sanitize_key( $post_type ) : (string) get_post_type( $post_id );

	// Skip non-product sources and installations where WooCommerce has not registered variations.
	if ( $post_id <= 0 || 'product' !== $post_type || ! post_type_exists( 'product_variation' ) ) {
		return array();
	}

	// Read variation child posts without language filtering, matching the attachment scan around this helper.
	$variation_post_ids = ai4seo_with_wpml_all_languages(
		function () use ( $post_id ) {
			return get_posts(
				array(
					'post_parent'      => $post_id,
					'post_type'        => 'product_variation',
					'post_status'      => array( 'publish', 'private' ),
					'fields'           => 'ids',
					'posts_per_page'   => -1,
					'no_found_rows'    => true,
					'orderby'          => 'ID',
					'order'            => 'DESC',
					'suppress_filters' => true,
					'lang'             => 'all',
				)
			);
		}
	);

	// Stop early when the product has no variation children with their own media relation.
	if ( ! $variation_post_ids ) {
		return array();
	}

	// Collect the normal WordPress featured-image relation from each variation.
	$variation_thumbnail_attachment_post_ids = array();

	foreach ( (array) $variation_post_ids as $this_variation_post_id ) {
		$this_variation_thumbnail_post_id = get_post_thumbnail_id( absint( $this_variation_post_id ) );

		if ( $this_variation_thumbnail_post_id ) {
			$variation_thumbnail_attachment_post_ids[] = absint( $this_variation_thumbnail_post_id );
		}
	}

	return ai4seo_normalize_related_attachment_post_ids( $variation_thumbnail_attachment_post_ids );
}

// =========================================================================================== \

/**
 * Adds a valid related attachment post ID to a deduped ID map.
 *
 * @param array $related_attachment_post_ids Related attachment ID map.
 * @param int   $attachment_post_id Attachment post ID candidate.
 * @return void
 */
function ai4seo_add_related_attachment_post_id( array &$related_attachment_post_ids, int $attachment_post_id ): void {
	// Validate each candidate once so broad content/meta scans cannot add non-image posts.
	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return;
	}

	if ( isset( $related_attachment_post_ids[ $attachment_post_id ] ) ) {
		return;
	}

	if ( ! ai4seo_is_related_attachment_post_id_valid( $attachment_post_id ) ) {
		return;
	}

	$related_attachment_post_ids[ $attachment_post_id ] = $attachment_post_id;
}

// =========================================================================================== \

/**
 * Adds multiple valid related attachment post IDs to a deduped ID map.
 *
 * @param array $related_attachment_post_ids Related attachment ID map.
 * @param array $attachment_post_ids Attachment post ID candidates.
 * @return void
 */
function ai4seo_add_related_attachment_post_ids( array &$related_attachment_post_ids, array $attachment_post_ids ): void {
	// Funnel every batch through the single-ID validator to keep all sources consistent.
	foreach ( $attachment_post_ids as $this_attachment_post_id ) {
		ai4seo_add_related_attachment_post_id( $related_attachment_post_ids, absint( $this_attachment_post_id ) );
	}
}

// =========================================================================================== \

/**
 * Checks whether a candidate is a usable image attachment.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return bool True if the attachment can be listed.
 */
function ai4seo_is_related_attachment_post_id_valid( int $attachment_post_id ): bool {
	static $validated_attachment_post_ids = array();

	// Cache validation by attachment ID because the same candidate can appear in content and meta.
	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return false;
	}

	if ( isset( $validated_attachment_post_ids[ $attachment_post_id ] ) ) {
		return $validated_attachment_post_ids[ $attachment_post_id ];
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || is_wp_error( $attachment_post ) || ! isset( $attachment_post->post_type ) ) {
		$validated_attachment_post_ids[ $attachment_post_id ] = false;
		return false;
	}

	if ( 'attachment' !== $attachment_post->post_type ) {
		$validated_attachment_post_ids[ $attachment_post_id ] = false;
		return false;
	}

	if ( ! in_array( $attachment_post->post_status, array( 'publish', 'future', 'inherit' ), true ) ) {
		$validated_attachment_post_ids[ $attachment_post_id ] = false;
		return false;
	}

	$attachment_post_mime_type                            = ai4seo_get_attachment_post_mime_type( $attachment_post_id );
	$allowed_attachment_mime_types                        = ai4seo_get_allowed_attachment_mime_types();
	$validated_attachment_post_ids[ $attachment_post_id ] = in_array( $attachment_post_mime_type, $allowed_attachment_mime_types, true );

	return $validated_attachment_post_ids[ $attachment_post_id ];
}

// =========================================================================================== \

/**
 * Normalizes related attachment post ID candidates while preserving first-seen order.
 *
 * @param array $attachment_post_ids Attachment post ID candidates.
 * @return array Normalized attachment post IDs.
 */
function ai4seo_normalize_related_attachment_post_ids( array $attachment_post_ids ): array {
	// Keep zero values here because the later validator is responsible for rejecting invalid IDs.
	return array_values( array_unique( array_map( 'absint', $attachment_post_ids ) ) );
}

// =========================================================================================== \

/**
 * Extracts attachment ID candidates from a mixed content or metadata value.
 *
 * @param mixed  $value The value to scan.
 * @param string $context_key The source key or field name.
 * @param int    $depth Recursion depth guard.
 * @param mixed  $related_attachment_scan_state Optional mutable scan state.
 * @return array Attachment ID candidates.
 */
function ai4seo_extract_related_attachment_post_ids_from_value( $value, string $context_key = '', int $depth = 0, &$related_attachment_scan_state = null ): array {
	// Builder JSON can nest deeply; cap recursion so malformed values cannot consume the request.
	if ( $depth > 8 ) {
		return array();
	}

	$related_attachment_post_ids = array();

	// Preserve nested array keys as context because keys often tell us whether numeric values are media references.
	if ( is_array( $value ) ) {
		foreach ( $value as $this_key => $this_value ) {
			$this_context_key            = ai4seo_get_related_attachment_nested_context_key( $context_key, $this_key );
			$related_attachment_post_ids = array_merge(
				$related_attachment_post_ids,
				ai4seo_extract_related_attachment_post_ids_from_value( $this_value, $this_context_key, $depth + 1, $related_attachment_scan_state )
			);
		}

		return ai4seo_normalize_related_attachment_post_ids( $related_attachment_post_ids );
	}

	// Convert decoded objects into arrays so JSON-like builder data follows the same traversal path.
	if ( is_object( $value ) ) {
		return ai4seo_extract_related_attachment_post_ids_from_value( get_object_vars( $value ), $context_key, $depth + 1, $related_attachment_scan_state );
	}

	// Numeric values are only treated as IDs when the surrounding key is known to describe media.
	if ( is_numeric( $value ) && ai4seo_is_attachment_like_reference_key( $context_key ) ) {
		return array( absint( $value ) );
	}

	if ( ! is_string( $value ) || trim( $value ) === '' ) {
		return array();
	}

	// WordPress and page builders may store serialized arrays in single postmeta rows.
	$maybe_unserialized_value = maybe_unserialize( $value );

	if ( $maybe_unserialized_value !== $value ) {
		$related_attachment_post_ids = array_merge(
			$related_attachment_post_ids,
			ai4seo_extract_related_attachment_post_ids_from_value( $maybe_unserialized_value, $context_key, $depth + 1, $related_attachment_scan_state )
		);
	}

	$trimmed_value = trim( $value );

	// Elementor and block builders commonly store media references in JSON blobs.
	if ( '' !== $trimmed_value && in_array( substr( $trimmed_value, 0, 1 ), array( '{', '[' ), true ) ) {
		$json_value = json_decode( $trimmed_value, true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $json_value ) ) {
			$related_attachment_post_ids = array_merge(
				$related_attachment_post_ids,
				ai4seo_extract_related_attachment_post_ids_from_value( $json_value, $context_key, $depth + 1, $related_attachment_scan_state )
			);
		}
	}

	// Fall back to pattern scanning for HTML, shortcodes, class names, URLs, and scalar meta values.
	$related_attachment_post_ids = array_merge(
		$related_attachment_post_ids,
		ai4seo_extract_related_attachment_post_ids_from_string( $value, $context_key, $related_attachment_scan_state )
	);

	return ai4seo_normalize_related_attachment_post_ids( $related_attachment_post_ids );
}

// =========================================================================================== \

/**
 * Builds a nested context key for related attachment scanning.
 *
 * @param string     $context_key Parent context key.
 * @param int|string $key         Current array key.
 * @return string Nested context key.
 */
function ai4seo_get_related_attachment_nested_context_key( string $context_key, $key ): string {
	if ( ! is_string( $key ) ) {
		return $context_key;
	}

	$key = trim( $key );

	if ( '' === $key || ctype_digit( $key ) ) {
		return $context_key;
	}

	if ( '' === $context_key ) {
		return $key;
	}

	return $context_key . '.' . $key;
}

// =========================================================================================== \

/**
 * Extracts attachment ID candidates from a string.
 *
 * @param string $content The string to scan.
 * @param string $context_key The source key or field name.
 * @param mixed  $related_attachment_scan_state Optional mutable scan state.
 * @return array Attachment ID candidates.
 */
function ai4seo_extract_related_attachment_post_ids_from_string( string $content, string $context_key = '', &$related_attachment_scan_state = null ): array {
	// Unescape JSON-style slashes so URL and shortcode patterns can match the stored value.
	$content = trim( str_replace( '\/', '/', $content ) );

	if ( '' === $content ) {
		return array();
	}

	$related_attachment_post_ids = array();

	// Match common WordPress and builder patterns while intentionally ignoring ambiguous bare "id" keys.
	$id_patterns = array(
		'/\b(?:wp-image-|attachment[_-]|attachment-id-)([0-9]+)\b/i',
		'/["\'](?:image_id|attachment_id|media_id|thumbnail_id|background_image)["\']\s*[:=]\s*["\']?([0-9]+)/i',
	);

	foreach ( $id_patterns as $this_id_pattern ) {
		if ( preg_match_all( $this_id_pattern, $content, $matches ) ) {
			foreach ( $matches[1] as $this_attachment_post_id ) {
				$related_attachment_post_ids[] = absint( $this_attachment_post_id );
			}
		}
	}

	// Match galleries and other comma-separated media lists without assuming exclusive usage.
	$id_list_patterns = array(
		'/\b(?:ids|data-ids)=["\']([^"\']+)["\']/i',
		'/\[(?:gallery|playlist)[^\]]*\bids=["\']?([^"\'\]\s]+(?:\s*,\s*[0-9]+)*)/i',
		'/["\'](?:ids|gallery_ids|image_ids|attachment_ids)["\']\s*:\s*\[([0-9,\s"\']+)\]/i',
		'/["\'](?:ids|gallery_ids|image_ids|attachment_ids)["\']\s*:\s*["\']([^"\']+)["\']/i',
	);

	foreach ( $id_list_patterns as $this_id_list_pattern ) {
		if ( preg_match_all( $this_id_list_pattern, $content, $matches ) ) {
			foreach ( $matches[1] as $this_attachment_post_id_list ) {
				// JSON arrays can quote numeric IDs, while wp_parse_id_list expects plain separators.
				$this_normalized_attachment_post_id_list = str_replace( array( '"', "'" ), '', $this_attachment_post_id_list );
				$related_attachment_post_ids             = array_merge( $related_attachment_post_ids, wp_parse_id_list( $this_normalized_attachment_post_id_list ) );
			}
		}
	}

	// Some custom fields store only a scalar ID or comma list, so require a media-like key first.
	if ( ai4seo_is_attachment_like_reference_key( $context_key ) && preg_match( '/^[0-9,\s]+$/', $content ) ) {
		$related_attachment_post_ids = array_merge( $related_attachment_post_ids, wp_parse_id_list( $content ) );
	}

	// Resolve local image URLs last because that can require attachment lookup work.
	$related_attachment_post_ids = array_merge(
		$related_attachment_post_ids,
		ai4seo_extract_related_attachment_post_ids_from_image_urls( $content, $related_attachment_scan_state )
	);

	return ai4seo_normalize_related_attachment_post_ids( $related_attachment_post_ids );
}

// =========================================================================================== \

/**
 * Extracts attachment IDs by resolving local image URLs from content.
 *
 * @param string $content The string to scan.
 * @param mixed  $related_attachment_scan_state Optional mutable scan state.
 * @return array Attachment ID candidates.
 */
function ai4seo_extract_related_attachment_post_ids_from_image_urls( string $content, &$related_attachment_scan_state = null ): array {
	$attachment_post_ids = array();

	// Cover HTML attributes, CSS background values, and raw absolute URLs found in builder data.
	$url_patterns = array(
		'/\b(?:src|href)=["\']([^"\']+\.(?:jpe?g|png|gif|webp|avif)(?:\?[^"\']*)?)["\']/i',
		'/url\(["\']?([^"\')]+\.(?:jpe?g|png|gif|webp|avif)(?:\?[^"\')]+)?)["\']?\)/i',
		'/https?:\/\/[^\s"\'<>\\\\]+\.(?:jpe?g|png|gif|webp|avif)(?:\?[^\s"\'<>\\\\]*)?/i',
	);

	foreach ( $url_patterns as $this_url_pattern ) {
		if ( ! preg_match_all( $this_url_pattern, $content, $matches ) ) {
			continue;
		}

		$this_urls = isset( $matches[1] ) && is_array( $matches[1] ) && $matches[1]
			? $matches[1]
			: $matches[0];

		foreach ( $this_urls as $this_url ) {
			$this_url = trim( html_entity_decode( (string) $this_url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			if ( '' === $this_url ) {
				continue;
			}

			// Count resolver attempts because ai4seo_get_attachment_id_from_src() can touch attachment lookup paths.
			if ( is_array( $related_attachment_scan_state ) ) {
				$max_image_url_attachment_lookups = absint( $related_attachment_scan_state['max_image_url_attachment_lookups'] ?? 0 );
				$image_url_attachment_lookups     = absint( $related_attachment_scan_state['image_url_attachment_lookups'] ?? 0 );

				if ( $max_image_url_attachment_lookups > 0 && $image_url_attachment_lookups >= $max_image_url_attachment_lookups ) {
					$related_attachment_scan_state['is_partial'] = true;
					break 2;
				}

				$related_attachment_scan_state['image_url_attachment_lookups'] = $image_url_attachment_lookups + 1;
			}

			// Normalize root-relative and protocol-relative URLs before using WordPress attachment lookup.
			if ( strpos( $this_url, '//' ) === 0 ) {
				$this_url = set_url_scheme( $this_url );
			} elseif ( strpos( $this_url, '/' ) === 0 ) {
				$this_url = home_url( $this_url );
			}

			$attachment_post_id = ai4seo_get_attachment_id_from_src( $this_url );

			if ( $attachment_post_id ) {
				$attachment_post_ids[] = absint( $attachment_post_id );
			}
		}
	}

	return ai4seo_normalize_related_attachment_post_ids( $attachment_post_ids );
}

// =========================================================================================== \

/**
 * Checks whether a key usually stores attachment references.
 *
 * @param string $context_key The source key or field name.
 * @return bool True if the key likely points to media.
 */
function ai4seo_is_attachment_like_reference_key( string $context_key ): bool {
	// Match broad media terms because builders use many custom key names for image references.
	$context_key = strtolower( trim( $context_key ) );

	if ( '' === $context_key ) {
		return false;
	}

	$attachment_like_terms = array(
		'attachment',
		'background',
		'featured',
		'gallery',
		'image',
		'media',
		'photo',
		'picture',
		'poster',
		'thumbnail',
		'_thumbnail_id',
		'_product_image_gallery',
	);

	foreach ( $attachment_like_terms as $this_attachment_like_term ) {
		if ( strpos( $context_key, $this_attachment_like_term ) !== false ) {
			return true;
		}
	}

	// Ambiguous keys such as id, ids, url, and src are ignored unless nested under a media-like context.
	return false;
}

// =========================================================================================== \

/**
 * Runs an optional deep-context SELECT query with a database-level timeout.
 *
 * Returns an empty result when the current database cannot enforce a statement timeout,
 * because these deep-context queries are optional and must not block cron execution.
 *
 * @param string $select_sql A fully prepared SELECT query.
 * @param int    $timeout_seconds The maximum database execution time in seconds.
 * @param string $debug_context Short context for debug logs.
 * @return array
 */
function ai4seo_get_col_with_optional_statement_timeout( string $select_sql, int $timeout_seconds = AI4SEO_DEEP_CONTEXT_SEARCH_QUERY_TIMEOUT_SECONDS, string $debug_context = '' ): array {
	global $wpdb;

	$select_sql = trim( $select_sql );

	if ( ! $select_sql || ! preg_match( '/^SELECT\s/i', $select_sql ) ) {
		ai4seo_debug_message( 630085035, 'Optional timed query skipped because it is not a SELECT query.', true );
		return array();
	}

	$timeout_seconds = max( 1, absint( $timeout_seconds ) );
	$timeout_support = ai4seo_get_database_statement_timeout_support();

	if ( ! $timeout_support['supported'] ) {
		ai4seo_debug_message( 630085036, 'Optional timed query skipped because statement timeouts are not supported by this database. ' . $debug_context );
		return array();
	}

	if ( 'mariadb' === $timeout_support['engine'] ) {
		$timed_select_sql = 'SET STATEMENT max_statement_time=' . $timeout_seconds . ' FOR ' . $select_sql;
	} else {
		$timeout_milliseconds = $timeout_seconds * 1000;
		$timed_select_sql     = preg_replace(
			'/^SELECT\s/i',
			'SELECT /*+ MAX_EXECUTION_TIME(' . $timeout_milliseconds . ') */ ',
			$select_sql,
			1
		);
	}

	if ( ! $timed_select_sql ) {
		ai4seo_debug_message( 630085037, 'Optional timed query skipped because the timeout wrapper could not be built. ' . $debug_context, true );
		return array();
	}

	$previous_suppress_errors = $wpdb->suppress_errors( true );
	$previous_last_error      = $wpdb->last_error;
	$query_results            = array();
	$query_error              = '';
	$query_error_code         = 0;

	try {
		// Safe: timed SQL wraps a SELECT that callers pass in fully prepared; the wrapper only adds a numeric timeout.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$query_results = $wpdb->get_col( $timed_select_sql );
		$query_error   = $wpdb->last_error;
	} catch ( Throwable $e ) {
		$query_error      = $e->getMessage();
		$query_error_code = (int) $e->getCode();
	} finally {
		$wpdb->suppress_errors( $previous_suppress_errors );
	}

	if ( $query_error ) {
		$wpdb->last_error = $previous_last_error;
		ai4seo_debug_message( 630085038, 'Optional timed query failed or timed out. ' . $debug_context . ' Error: ' . $query_error, true );

		if ( ai4seo_is_database_statement_timeout_error( $query_error, $query_error_code ) ) {
			ai4seo_handle_deep_context_search_statement_timeout();
		}

		return array();
	}

	$wpdb->last_error = $previous_last_error;

	if ( ! is_array( $query_results ) ) {
		return array();
	}

	return $query_results;
}

// =========================================================================================== \

/**
 * Checks if a database error indicates a statement timeout.
 *
 * @param string $query_error Database error message.
 * @param int    $query_error_code Database error code.
 * @return bool
 */
function ai4seo_is_database_statement_timeout_error( string $query_error, int $query_error_code = 0 ): bool {
	if ( in_array( $query_error_code, array( 1969, 3024 ), true ) ) {
		return true;
	}

	$query_error = strtolower( $query_error );

	return (
		strpos( $query_error, 'max_statement_time' ) !== false
		|| strpos( $query_error, 'maximum statement execution time' ) !== false
		|| strpos( $query_error, 'execution time exceeded' ) !== false
		|| strpos( $query_error, 'query execution was interrupted' ) !== false
	);
}

// =========================================================================================== \

/**
 * Counts deep context search statement timeouts during one automated generation cron run.
 *
 * @return void
 */
function ai4seo_handle_deep_context_search_statement_timeout(): void {
	if ( empty( $GLOBALS['ai4seo_is_running_automated_generation_cron_job'] ) ) {
		return;
	}

	$GLOBALS['ai4seo_deep_context_search_statement_timeout_count'] = (int) ( $GLOBALS['ai4seo_deep_context_search_statement_timeout_count'] ?? 0 );
	++$GLOBALS['ai4seo_deep_context_search_statement_timeout_count'];

	if ( $GLOBALS['ai4seo_deep_context_search_statement_timeout_count'] < AI4SEO_DEEP_CONTEXT_SEARCH_MAX_TIMEOUTS_PER_CRON_RUN ) {
		return;
	}

	ai4seo_disable_deep_context_search_for_images();
}

// =========================================================================================== \

/**
 * Detects whether the current database can enforce per-statement SELECT timeouts.
 *
 * @return array
 */
function ai4seo_get_database_statement_timeout_support(): array {
	global $wpdb;

	static $timeout_support = null;

	if ( null !== $timeout_support ) {
		return $timeout_support;
	}

	$db_version_string = (string) $wpdb->get_var( 'SELECT VERSION()' );
	$db_version_number = preg_replace( '/[^0-9.].*$/', '', $db_version_string );
	$is_mariadb        = ( stripos( $db_version_string, 'mariadb' ) !== false );

	$timeout_support = array(
		'supported' => false,
		'engine'    => '',
		'version'   => $db_version_string,
	);

	if ( $is_mariadb ) {
		$mariadb_version_matches = array();

		if ( preg_match( '/([0-9]+(?:\.[0-9]+){1,2})-MariaDB/i', $db_version_string, $mariadb_version_matches ) ) {
			$db_version_number = $mariadb_version_matches[1];
		}
	}

	if ( ! $db_version_number ) {
		return $timeout_support;
	}

	if ( $is_mariadb ) {
		$timeout_support['engine']    = 'mariadb';
		$timeout_support['supported'] = version_compare( $db_version_number, '10.1.1', '>=' );
		return $timeout_support;
	}

	$timeout_support['engine']    = 'mysql';
	$timeout_support['supported'] = version_compare( $db_version_number, '5.7.4', '>=' );

	return $timeout_support;
}

// =========================================================================================== \

/**
 * Returns the first matching post ID where an attachment is used.
 *
 * Preferred lookup order:
 * 1) parent_id relation
 * 2) Featured image relation (_thumbnail_id)
 * 3) Deep search (optional): post_content + postmeta references
 * 4) Related Media modal fallback (ai4seo_related_post_id)
 *
 * @param int $attachment_post_id the attachment post id
 * @return int first matching post id or 0 if not found
 */
function ai4seo_get_first_attachment_using_post_id( int $attachment_post_id ): int {
	$attachment_post_id = absint( $attachment_post_id );

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 605028311, 'Prevented loop', true );
		// If recursion protection blocks fresh discovery, the stored modal result is still safe to read.
		return ai4seo_get_attachment_related_post_id( $attachment_post_id );
	}

	if ( $attachment_post_id <= 0 ) {
		return 0;
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post ) {
		return 0;
	}

	if ( 'attachment' !== $attachment_post->post_type ) {
		return 0;
	}

	// try parent post relation first.
	$parent_post_id = absint( $attachment_post->post_parent ?? 0 );

	if ( ai4seo_is_attachment_context_post_eligible( $parent_post_id ) ) {
		return $parent_post_id;
	}

	// look for thumbnail relations next.
	global $wpdb;

	$thumbnail_post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id
         FROM {$wpdb->postmeta}
         WHERE meta_key = '_thumbnail_id'
           AND meta_value = %d
         ORDER BY post_id ASC
         LIMIT 25",
			$attachment_post_id
		)
	);

	if ( ! empty( $thumbnail_post_ids ) && is_array( $thumbnail_post_ids ) ) {
		$thumbnail_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $thumbnail_post_ids );

		if ( $thumbnail_post_id > 0 ) {
			return $thumbnail_post_id;
		}
	}

	// From here we only continue if deep context search for images is enabled, otherwise use the modal fallback.
	if ( ! ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ) ) {
		return ai4seo_get_attachment_related_post_id( $attachment_post_id );
	}

	if ( ! ai4seo_is_deep_context_search_supported_for_current_site() ) {
		ai4seo_disable_deep_context_search_for_images();
		return ai4seo_get_attachment_related_post_id( $attachment_post_id );
	}

	// deep search: look for the attachment post id or url in the content of posts and postmeta
	// (e.g. for page builders that store the content in postmeta or for attachments.
	$attachment_post_id_string = (string) $attachment_post_id;

	$attachment_like_parts = array(
		'%' . $wpdb->esc_like( 'wp-image-' . $attachment_post_id ) . '%',
		'%' . $wpdb->esc_like( 'attachment_' . $attachment_post_id ) . '%',

		'%' . $wpdb->esc_like( 'id:' . $attachment_post_id ) . '%',
		'%' . $wpdb->esc_like( '"id":' . $attachment_post_id ) . '%',
		'%' . $wpdb->esc_like( '"id":"' . $attachment_post_id . '"' ) . '%',

		'%' . $wpdb->esc_like( 'id=' . $attachment_post_id ) . '%',
		'%' . $wpdb->esc_like( '"id"=' . $attachment_post_id ) . '%',
		'%' . $wpdb->esc_like( '"id"="' . $attachment_post_id . '"' ) . '%',

		// ids="123, 234, 345" (and variants): match as a distinct list item, not inside another number.
		'%' . $wpdb->esc_like( 'ids="' . $attachment_post_id_string . ',' ) . '%',   // start: ids="234,.
		'%' . $wpdb->esc_like( 'ids="' . $attachment_post_id_string . ' ,' ) . '%',  // start (space): ids="234 ,.

		'%' . $wpdb->esc_like( 'ids="' ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . ',' ) . '%',    // middle: ids="...,234,...".
		'%' . $wpdb->esc_like( 'ids="' ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . ',' ) . '%',   // middle with space after comma.

		'%' . $wpdb->esc_like( 'ids="' ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . '"' ) . '%',     // end: ids="...,234".
		'%' . $wpdb->esc_like( 'ids="' ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . '"' ) . '%',    // end with space.

		// Same for single quotes: ids='123, 234, 345'.
		'%' . $wpdb->esc_like( "ids='" . $attachment_post_id_string . ',' ) . '%',
		'%' . $wpdb->esc_like( "ids='" . $attachment_post_id_string . ' ,' ) . '%',

		'%' . $wpdb->esc_like( "ids='" ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . ',' ) . '%',
		'%' . $wpdb->esc_like( "ids='" ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . ',' ) . '%',

		'%' . $wpdb->esc_like( "ids='" ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . "'" ) . '%',
		'%' . $wpdb->esc_like( "ids='" ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . "'" ) . '%',

		// "ids":"123, 234, 345"
		'%' . $wpdb->esc_like( '"ids":"' . $attachment_post_id_string . ',' ) . '%',

		'%' . $wpdb->esc_like( '"ids":"' ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . ',' ) . '%',
		'%' . $wpdb->esc_like( '"ids":"' ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . ',' ) . '%',

		'%' . $wpdb->esc_like( '"ids":"' . $attachment_post_id_string . '"' ) . '%',
		'%' . $wpdb->esc_like( '"ids":"' ) . '%' . $wpdb->esc_like( ',' . $attachment_post_id_string . '"' ) . '%',
		'%' . $wpdb->esc_like( '"ids":"' ) . '%' . $wpdb->esc_like( ', ' . $attachment_post_id_string . '"' ) . '%',

		// Common builder attributes.
		'%' . $wpdb->esc_like( 'data-id="' . $attachment_post_id_string . '"' ) . '%',
		'%' . $wpdb->esc_like( "data-id='" . $attachment_post_id_string . "'" ) . '%',

		'%' . $wpdb->esc_like( 'data-ids="' . $attachment_post_id_string . '"' ) . '%',
		'%' . $wpdb->esc_like( "data-ids='" . $attachment_post_id_string . "'" ) . '%',
	);

	$attachment_url = ai4seo_get_attachment_url( $attachment_post_id );

	if ( $attachment_url ) {
		$attachment_like_parts[] = '%' . $wpdb->esc_like( $attachment_url ) . '%';

		// Try to match common WordPress variants like "-300x300" and "-scaled".
		$path_parts = wp_parse_url( $attachment_url );

		$attachment_path = $path_parts['path'] ?? '';
		$attachment_base = wp_basename( $attachment_path );

		$dot_position = strrpos( $attachment_base, '.' );

		if ( false !== $dot_position ) {
			$filename  = substr( $attachment_base, 0, $dot_position );
			$extension = substr( $attachment_base, $dot_position + 1 );

			// Matches: my-image-300x300.jpg.
			$attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-' ) . '%x%' . $wpdb->esc_like( '.' . $extension ) . '%';

			// Matches: my-image-scaled.jpg.
			$attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-scaled.' . $extension ) . '%';

			// Matches: my-image-rotated.jpg (sometimes created by WP when editing).
			$attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-rotated.' . $extension ) . '%';

			// Matches: my-image.
			if ( ai4seo_mb_strlen( $filename ) >= 8 ) {
				$attachment_like_parts[] = '%' . $wpdb->esc_like( $filename ) . '%';
			}
		}
	}

	$content_like_clause_parts = array_fill( 0, count( $attachment_like_parts ), 'post_content LIKE %s' );

	// Safe: content LIKE fragments are generated placeholders only; attachment patterns are prepared below.
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$content_post_ids = ai4seo_get_col_with_optional_statement_timeout(
		$wpdb->prepare(
			"SELECT ID
         FROM {$wpdb->posts}
         WHERE (" . implode( ' OR ', $content_like_clause_parts ) . ")
            AND post_type != 'revision'
            AND post_status NOT IN ('auto-draft')
            AND ID != %d
         ORDER BY ID DESC
         LIMIT 20",
			...array_merge( $attachment_like_parts, array( $attachment_post_id ) )
		),
		AI4SEO_DEEP_CONTEXT_SEARCH_QUERY_TIMEOUT_SECONDS,
		'Attachment content deep context search for media post ID: ' . $attachment_post_id
	);
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! empty( $content_post_ids ) && is_array( $content_post_ids ) ) {
		$content_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $content_post_ids );

		if ( $content_post_id > 0 ) {
			return $content_post_id;
		}
	}

	if ( ! ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ) ) {
		// The content search may disable deep search after a timeout, so fall back before scanning postmeta.
		return ai4seo_get_attachment_related_post_id( $attachment_post_id );
	}

	// if not found in content, look in postmeta.
	$postmeta_like_clause_parts = array_fill( 0, count( $attachment_like_parts ), 'meta_value LIKE %s' );

	// Safe: postmeta LIKE fragments are generated placeholders only; attachment patterns are prepared below.
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$postmeta_post_ids = ai4seo_get_col_with_optional_statement_timeout(
		$wpdb->prepare(
			"SELECT post_id
         FROM {$wpdb->postmeta}
         WHERE (" . implode( ' OR ', $postmeta_like_clause_parts ) . ')
            AND post_id != %d
         ORDER BY post_id DESC
         LIMIT 100',
			...array_merge( $attachment_like_parts, array( $attachment_post_id ) )
		),
		AI4SEO_DEEP_CONTEXT_SEARCH_QUERY_TIMEOUT_SECONDS,
		'Attachment postmeta deep context search for media post ID: ' . $attachment_post_id
	);
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! empty( $postmeta_post_ids ) && is_array( $postmeta_post_ids ) ) {
		$postmeta_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $postmeta_post_ids );

		if ( $postmeta_post_id > 0 ) {
			return $postmeta_post_id;
		}
	}

	return ai4seo_get_attachment_related_post_id( $attachment_post_id );
}

// =========================================================================================== \\

/**
 * Returns the first eligible post ID from a list of candidates.
 *
 * @param array $candidate_post_ids list of post ids
 * @return int first eligible post id or 0
 */
function ai4seo_get_first_eligible_attachment_context_post_id( array $candidate_post_ids ): int {
	foreach ( $candidate_post_ids as $candidate_post_id ) {
		$candidate_post_id = absint( $candidate_post_id );

		if ( ai4seo_is_attachment_context_post_eligible( $candidate_post_id ) ) {
			return $candidate_post_id;
		}
	}

	return 0;
}

// =========================================================================================== \

/**
 * Checks whether a post can be used for attachment context.
 *
 * @param int $post_id the post id
 * @return bool true if eligible
 */
function ai4seo_is_attachment_context_post_eligible( int $post_id ): bool {
	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return false;
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
		return false;
	}

	if ( in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
		return false;
	}

	return true;
}

// =========================================================================================== \

/**
 * Returns the frontend URL for a public attachment context post.
 *
 * @param int $post_id the post id
 * @return string public frontend url or empty string
 */
function ai4seo_get_attachment_context_frontend_post_url( int $post_id ): string {
	$post_id = absint( $post_id );

	if ( ! ai4seo_is_attachment_context_post_eligible( $post_id ) ) {
		return '';
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	// Only expose links that should resolve as normal frontend pages for the current site.
	if ( function_exists( 'is_post_publicly_viewable' ) ) {
		$is_post_publicly_viewable = (bool) is_post_publicly_viewable( $post );
	} else {
		$post_status               = get_post_status_object( $post->post_status );
		$post_type                 = get_post_type_object( $post->post_type );
		$is_post_publicly_viewable = (bool) (
			$post_status
			&& $post_status->public
			&& $post_type
			&& $post_type->publicly_queryable
		);
	}

	if ( ! $is_post_publicly_viewable ) {
		return '';
	}

	$post_url = get_permalink( $post );

	return $post_url ? (string) $post_url : '';
}

// =========================================================================================== \

/**
 * Returns the language of the attachment
 *
 * @param int $attachment_post_id the attachment post id
 * @return string the language of the attachment
 */
function ai4seo_get_attachments_language( int $attachment_post_id ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 925515687, 'Prevented loop', true );
		return '';
	}

	return sanitize_text_field( ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE ) );
}

// =========================================================================================== \\

/**
 * Retrieves the active attachment attributes.
 *
 * @return array The active attachment attributes.
 */
function ai4seo_get_active_attachment_attributes(): array {
	$active_attachment_attributes = ai4seo_get_setting( AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES );

	if ( ! is_array( $active_attachment_attributes ) ) {
		return array();
	}

	return $active_attachment_attributes;
}

// =========================================================================================== \\

/**
 * Retrieves the supported attachment post types
 *
 * @param bool $require_active_attachment_attributes The require active attachment attributes value.
 * @return array the supported attachment post types
 */
function ai4seo_get_supported_attachment_post_types( bool $require_active_attachment_attributes = true ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 867135627, 'Prevented loop', true );
		return array();
	}

	if ( $require_active_attachment_attributes ) {
		$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

		if ( ! $ai4seo_active_attachment_attributes ) {
			return array();
		}
	}

	$supported_attachment_post_types = array( 'attachment' );

	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY ) ) {
		$supported_attachment_post_types[] = AI4SEO_NEXTGEN_GALLERY_POST_TYPE;
	}

	return $supported_attachment_post_types;
}

// =========================================================================================== \\

function ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post( $only_this_attachment_attributes = null ): int {
	$attachment_attributes_price_table = ai4seo_get_attachment_attributes_price_table( $only_this_attachment_attributes );

	if ( empty( $attachment_attributes_price_table ) ) {
		return 1;
	}

	// calculate total costs.
	return array_sum( $attachment_attributes_price_table );
}

// =========================================================================================== \\

function ai4seo_get_attachment_attributes_price_table( $only_this_attachment_attributes = null ): array {
	$active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	if ( empty( $active_attachment_attributes ) ) {
		return array();
	}

	$price_table = array();

	foreach ( $active_attachment_attributes as $this_active_attachment_attribute_identifier ) {
		if ( $only_this_attachment_attributes && is_array( $only_this_attachment_attributes ) && ! in_array( $this_active_attachment_attribute_identifier, $only_this_attachment_attributes ) ) {
			continue;
		}

		if ( ! defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) || ! is_array( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS ) ) {
			$price_table[ $this_active_attachment_attribute_identifier ] = 1; // fallback to 1 credit per attribute.
			continue;
		}

		$price_table[ $this_active_attachment_attribute_identifier ] = AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS[ $this_active_attachment_attribute_identifier ]['flat-credits-cost'] ?? 1;
	}

	return $price_table;
}

// =========================================================================================== \\

function ai4seo_get_active_attachment_attributes_names( $active_attachment_attributes = null ): array {
	if ( null === $active_attachment_attributes ) {
		$active_attachment_attributes = ai4seo_get_active_attachment_attributes();
	}

	$active_attachment_attributes_names = array();

	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details ) {
		if ( in_array( $ai4seo_this_attachment_attribute_identifier, $active_attachment_attributes ) && isset( $ai4seo_this_attachment_attribute_details['name'] ) ) {
			$active_attachment_attributes_names[] = $ai4seo_this_attachment_attribute_details['name'];
		}
	}

	return $active_attachment_attributes_names;
}


// endregion
// ___________________________________________________________________________________________.
