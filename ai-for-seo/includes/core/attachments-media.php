<?php
/**
 * Handles attachment and media discovery, validation, and generation.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region ATTACHMENTS / MEDIA =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This CAS deliberately mirrors WordPress core's public metadata filter/action lifecycle.
/**
 * Build constant attachment data used by the client-side Media Attributes previews.
 *
 * @param array  $attribute_values   Current attribute values.
 * @param array  $active_fields      Active attachment attribute identifiers.
 * @param bool   $is_image           Whether the attachment is a supported image.
 * @param string $attachment_url     Attachment URL.
 * @return array<string, mixed> Preview context.
 */
function ai4seo_get_attachment_editor_preview_context(
	array $attribute_values,
	array $active_fields,
	bool $is_image,
	string $attachment_url
): array {
	$preview_values      = array();
	$attachment_url_path = wp_parse_url( $attachment_url, PHP_URL_PATH );

	// Normalize every supported value once so all preview cards consume the same safe snapshot.
	foreach ( AI4SEO_AVAILABLE_ATTACHMENT_ATTRIBUTE_IDENTIFIERS as $attribute_identifier ) {
		$attribute_identifier = sanitize_key( $attribute_identifier );

		$value = $attribute_values[ $attribute_identifier ] ?? '';

		$preview_values[ $attribute_identifier ] = is_scalar( $value ) ? ai4seo_normalize_editor_input_value( (string) $value ) : '';
	}

	$attachment_quality_windows       = array();
	$fixed_attachment_quality_windows = AI4SEO_GENERATED_OUTPUT_QUALITY_WINDOWS['attachment_attributes'] ?? array();
	foreach ( array( 'title', 'alt-text', 'caption', 'description' ) as $attachment_attribute_identifier ) {
		$window = ai4seo_get_generation_length_quality_window( 'attachment_attributes', $attachment_attribute_identifier );

		// Only Alt Text currently has a configurable stage, so retain declared targets for the remaining attributes.
		if ( ! $window ) {
			$window = $fixed_attachment_quality_windows[ $attachment_attribute_identifier ] ?? array();
		}

		$attachment_quality_windows[ sanitize_key( $attachment_attribute_identifier ) ] = array(
			'min' => absint( $window['min-length'] ?? 0 ),
			'max' => absint( $window['max-length'] ?? 0 ),
		);
	}

	// Keep the client context presentation-only; form controls remain authoritative for saving.
	return array(
		'context'        => 'attachment',
		'values'         => $preview_values,
		'activeFields'   => array_values( array_map( 'sanitize_key', $active_fields ) ),
		'isImage'        => $is_image,
		'fileName'       => sanitize_file_name( wp_basename( is_string( $attachment_url_path ) ? $attachment_url_path : '' ) ),
		'qualityWindows' => $attachment_quality_windows,
	);
}

// End attachment preview-context construction before the existing media utilities begin.

/**
 * Format an attachment upload time from its WordPress date values.
 *
 * @param string $post_date_gmt   Attachment post_date_gmt value.
 * @param string $timezone        Timezone identifier or 'auto'.
 * @param string $post_date_local Optional attachment post_date fallback.
 * @return string Formatted upload time, or an empty string when unavailable.
 */
function ai4seo_get_attachment_upload_time_display( string $post_date_gmt, string $timezone = 'auto', string $post_date_local = '' ): string {
	$post_date_gmt    = trim( $post_date_gmt );
	$display_timezone = ai4seo_get_timezone( $timezone );

	if ( ai4seo_is_valid_mysql_datetime( $post_date_gmt ) ) {
		// Interpret the database field explicitly as UTC so display timezones are applied exactly once.
		$upload_timestamp = strtotime( $post_date_gmt . ' UTC' );
	} elseif ( '' === $post_date_gmt || '0000-00-00 00:00:00' === $post_date_gmt ) {
		$post_date_local = trim( $post_date_local );

		// Recover legacy rows only from an exact local WordPress date in the resolved site timezone.
		if ( ! ai4seo_is_valid_mysql_datetime( $post_date_local ) ) {
			return '';
		}

		$local_datetime = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $post_date_local, $display_timezone );

		if ( false === $local_datetime || $post_date_local !== $local_datetime->format( 'Y-m-d H:i:s' ) ) {
			return '';
		}

		$upload_timestamp = $local_datetime->getTimestamp();
	} else {
		// A malformed non-zero GMT value is corrupt data, not a missing value eligible for fallback.
		return '';
	}

	if ( false === $upload_timestamp ) {
		return '';
	}

	return ai4seo_format_unix_timestamp( $upload_timestamp, 'auto', 'auto', ' ', $display_timezone->getName() );
}

/**
 * Return the normalized MIME type for an attachment post.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return string|null Normalized MIME type, an empty string when unavailable, or null for an invalid post.
 */
function ai4seo_get_attachment_post_mime_type( $attachment_post_id ): ?string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 373146404, 'Prevented loop', true );
		return null;
	}

	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || empty( $attachment_post->post_type ) ) {
		return null;
	}

	// we found it already in the post_mime_type field.
	if ( ! empty( $attachment_post->post_mime_type ) ) {
		return ai4seo_normalize_mime_type_string( $attachment_post->post_mime_type );
	}

	// fallback: try to get it from the url.
	$attachment_url = ai4seo_get_attachment_url( $attachment_post_id );

	if ( ! $attachment_url ) {
		return '';
	}

	return ai4seo_get_mime_type_from_url( $attachment_url );
}


/**
 * Return the URL or GUID associated with an attachment-like post.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return string|null Attachment URL, or null when the post is unavailable.
 */
function ai4seo_get_attachment_url( $attachment_post_id ): ?string {
	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post || empty( $attachment_post->post_type ) ) {
		return null;
	}

	// check if it's an attachment.
	if ( 'attachment' === $attachment_post->post_type ) {
		// check url of the attachment.
		$ai4seo_attachment_url = wp_get_attachment_url( $attachment_post_id );
	} else {
		$ai4seo_attachment_url = get_the_guid( $attachment_post );
	}

	return $ai4seo_attachment_url;
}


/**
 * Get the best available attachment source.
 * Attention: Only use this function on a small number of attachments at once.
 *
 * Returns either a local file path or a reachable URL.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return array|null
 */
function ai4seo_get_best_attachment_source( int $attachment_post_id ): ?array {
	try {
		$attachment_post = get_post( $attachment_post_id );

		if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
			return null;
		}

		$attachment_path = get_attached_file( $attachment_post_id );

		if ( $attachment_path && file_exists( $attachment_path ) && is_readable( $attachment_path ) ) {
			return array(
				'type'   => 'path',
				'source' => $attachment_path,
			);
		}

		$attachment_url = wp_get_attachment_url( $attachment_post_id );

		if ( ! $attachment_url || wp_http_validate_url( $attachment_url ) === false ) {
			return null;
		}

		// Offloaded attachment URLs may be external, so probe them through WordPress's SSRF-safe transport.
		$response = wp_safe_remote_head(
			$attachment_url,
			array(
				'timeout'     => 10,
				'redirection' => 3,
			)
		);

		$should_use_range_get = is_wp_error( $response );

		if ( ! $should_use_range_get ) {
			$head_response_code   = (int) wp_remote_retrieve_response_code( $response );
			$should_use_range_get = in_array( $head_response_code, array( 403, 405, 501 ), true );
		}

		if ( $should_use_range_get ) {
			// Some storage providers reject HEAD; a one-byte safe GET checks reachability without fetching the file.
			$response = wp_safe_remote_get(
				$attachment_url,
				array(
					'timeout'             => 10,
					'redirection'         => 3,
					'stream'              => false,
					'limit_response_size' => 1,
					'headers'             => array(
						'Range' => 'bytes=0-0',
					),
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code >= 200 && $response_code < 400 ) {
			return array(
				'type'   => 'url',
				'source' => $attachment_url,
			);
		}
	} catch ( Exception $e ) {
		return null;
	}

	return null;
}


/**
 * Function to read and analyze the attachment attributes coverage of the given attachment ids (post ids)
 *
 * @param int|array $attachment_post_ids The post ids of the attachments we want to analyze.
 * @param bool|null $read_succeeded Receives whether every authoritative read succeeded.
 * @return array
 */
function ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_post_ids, ?bool &$read_succeeded = null ): array {
	global $wpdb;

	$read_succeeded = false;

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
		$read_succeeded = true;
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
		$read_succeeded = true;
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

			$attachment_posts_query = ai4seo_prepare_database_query(
				'SELECT ID, post_title, post_excerpt, post_content, guid
				FROM {{posts_table}}
				WHERE ID IN ({{post_ids}})',
				array(
					'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
					'post_ids'    => ai4seo_database_list_binding( '%d', array_values( $this_attachment_post_ids_chunk ) ),
				)
			);

			if ( false === $attachment_posts_query ) {
				ai4seo_debug_message( 984321663, 'Could not prepare the attachment coverage query.', true );
				return array();
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the bounded attachment-ID batch; coverage must reflect current post fields before generation decisions.
			$attachment_posts = $wpdb->get_results( $attachment_posts_query, ARRAY_A );

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321663, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! is_array( $attachment_posts ) ) {
				return array();
			}

			if ( ! $attachment_posts ) {
				continue;
			}

			foreach ( $attachment_posts as $this_attachment_post ) {
				if ( ! is_array( $this_attachment_post ) || ! array_key_exists( 'ID', $this_attachment_post ) ) {
					return array();
				}

				$this_attachment_post_id = ai4seo_normalize_database_id( $this_attachment_post['ID'] );

				if ( false === $this_attachment_post_id || ! isset( $attachment_attributes_coverage[ $this_attachment_post_id ] ) ) {
					return array();
				}

				if ( in_array( 'title', $active_attachment_attributes, true ) ) {
					if ( ! array_key_exists( 'post_title', $this_attachment_post ) ) {
						return array();
					}

					$attachment_attributes_coverage[ $this_attachment_post_id ]['title'] = $this_attachment_post['post_title'];
				}

				if ( in_array( 'caption', $active_attachment_attributes, true ) ) {
					if ( ! array_key_exists( 'post_excerpt', $this_attachment_post ) ) {
						return array();
					}

					$attachment_attributes_coverage[ $this_attachment_post_id ]['caption'] = $this_attachment_post['post_excerpt'];
				}

				if ( in_array( 'description', $active_attachment_attributes, true ) ) {
					if ( ! array_key_exists( 'post_content', $this_attachment_post ) ) {
						return array();
					}

					$attachment_attributes_coverage[ $this_attachment_post_id ]['description'] = $this_attachment_post['post_content'];
				}
			}
		}
	}

	// --- ALT TEXT --------------------------------------------------------------------- \\

	if ( in_array( 'alt-text', $active_attachment_attributes, true ) ) {
		foreach ( $attachment_post_ids_chunks as $this_post_ids_chunk ) {
			if ( empty( $this_post_ids_chunk ) ) {
				continue;
			}

			$attachment_postmeta_query = ai4seo_prepare_database_query(
				'SELECT post_id, meta_value FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}})',
				array(
					'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
					'meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The indexed exact key is paired with a bounded attachment-ID batch.
					'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $this_post_ids_chunk ) ),
				)
			);

			if ( false === $attachment_postmeta_query ) {
				ai4seo_debug_message( 984321664, 'Could not prepare the attachment alt-text coverage query.', true );
				return array();
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the bounded key/attachment-ID batch; coverage must reflect current alt text before generation decisions.
			$this_attachment_postmetas = $wpdb->get_results( $attachment_postmeta_query, ARRAY_A );

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321664, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! is_array( $this_attachment_postmetas ) ) {
				return array();
			}

			if ( empty( $this_attachment_postmetas ) ) {
				continue;
			}

			foreach ( $this_attachment_postmetas as $this_attachment_postmeta ) {
				if ( ! is_array( $this_attachment_postmeta )
					|| ! array_key_exists( 'post_id', $this_attachment_postmeta )
					|| ! array_key_exists( 'meta_value', $this_attachment_postmeta )
					|| ! is_string( $this_attachment_postmeta['meta_value'] ) ) {
					return array();
				}

				$this_attachment_post_id = ai4seo_normalize_database_id( $this_attachment_postmeta['post_id'] );

				if ( false === $this_attachment_post_id || ! isset( $attachment_attributes_coverage[ $this_attachment_post_id ] ) ) {
					return array();
				}

				$attachment_attributes_coverage[ $this_attachment_post_id ]['alt-text'] = $this_attachment_postmeta['meta_value'];
			}
		}
	}

	$read_succeeded = true;
	return $attachment_attributes_coverage;
}


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
			if ( ! in_array( $this_attachment_attribute_identifier, $active_attachment_attributes, true ) ) {
				continue;
			}

			$attachment_attributes_coverage[ $post_id ][ $this_attachment_attribute_identifier ] = '';
		}
	}

	return $attachment_attributes_coverage;
}


/**
 * Checks if the metadata for a given post is fully covered
 *
 * @param int       $attachment_post_id The post id to check the metadata coverage for.
 * @param bool|null $read_succeeded Receives whether the one-ID source snapshot was authoritative.
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_are_attachment_attributes_fully_covered( int $attachment_post_id, ?bool &$read_succeeded = null ): bool {
	$read_succeeded = false;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 346802262, 'Prevented loop', true );
		return true;
	}

	// get the total amount of attachment attributes.
	$active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	if ( ! $active_attachment_attributes ) {
		$read_succeeded = true;
		return true;
	}

	$num_active_and_covered_attachment_attributes = 0;

	// Read every one-ID owner without accepting cache misses, missing posts, or duplicate alt rows.
	$source_read_succeeded               = false;
	$attachment_post_exists              = false;
	$this_attachment_attributes_coverage = ai4seo_read_available_attachment_attributes(
		$attachment_post_id,
		$source_read_succeeded,
		$attachment_post_exists
	);

	if ( ! $source_read_succeeded || ! $attachment_post_exists ) {
		return false;
	}

	foreach ( $active_attachment_attributes as $this_attachment_attribute ) {
		if ( ! empty( $this_attachment_attributes_coverage[ $this_attachment_attribute ] ) ) {
			++$num_active_and_covered_attachment_attributes;
		}
	}

	$attachment_attributes_coverage_percentage = ( $num_active_and_covered_attachment_attributes / count( $active_attachment_attributes ) ) * 100;
	$read_succeeded                            = true;

	return ai4seo_is_full_coverage_percentage( $attachment_attributes_coverage_percentage );
}


/**
 * Returns the number of active attachment attributes
 *
 * @return int the number of active attachment attributes
 */
function ai4seo_get_active_num_attachment_attributes(): int {
	return count( ai4seo_get_active_attachment_attributes() );
}


/**
 * Reads one attachment post and its authoritative attachment-attribute values.
 *
 * @param int       $attachment_post_id Attachment post ID.
 * @param bool|null $read_succeeded Receives whether both exact reads and row validation succeeded.
 * @param bool|null $post_exists Receives whether the posts-table owner exists after a successful post read.
 * @return array{post?: array, alt_text?: array} Validated source snapshot, or an empty array.
 */
function ai4seo_read_attachment_attribute_source_snapshot(
	int $attachment_post_id,
	?bool &$read_succeeded = null,
	?bool &$post_exists = null
): array {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );
	$read_succeeded     = false;
	$post_exists        = false;

	if ( $attachment_post_id <= 0 ) {
		return array();
	}

	$post_query = ai4seo_prepare_database_query(
		'SELECT `ID`, `post_type`, `post_mime_type`, `guid`, `post_title`, `post_excerpt`, `post_content`
		FROM {{posts_table}}
		WHERE `ID` = {{post_id}}
		LIMIT 1',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_id'     => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
		)
	);

	if ( false === $post_query ) {
		return array();
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the exact primary-key read; queue and coverage decisions require current source bytes.
	$post_row = $wpdb->get_row( $post_query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return array();
	}

	if ( null === $post_row ) {
		$read_succeeded = true;
		return array();
	}

	$required_post_string_columns = array(
		'post_type',
		'post_mime_type',
		'guid',
		'post_title',
		'post_excerpt',
		'post_content',
	);

	if ( ! is_array( $post_row ) || ai4seo_normalize_database_id( $post_row['ID'] ?? null ) !== $attachment_post_id ) {
		return array();
	}

	foreach ( $required_post_string_columns as $required_post_string_column ) {
		if ( ! array_key_exists( $required_post_string_column, $post_row ) || ! is_string( $post_row[ $required_post_string_column ] ) ) {
			return array();
		}
	}

	$post_exists           = true;
	$attachment_meta_query = ai4seo_prepare_database_query(
		'SELECT `meta_id`, `meta_key`, `meta_value`
		FROM {{postmeta_table}}
		WHERE `post_id` = {{post_id}}
		AND `meta_key` IN ({{meta_keys}})
		ORDER BY `meta_id` ASC
		LIMIT 4',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
			'meta_keys'      => ai4seo_database_list_binding( '%s', array( '_wp_attached_file', '_wp_attachment_image_alt' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exact post ID plus LIMIT 4 bounds both duplicate-owner checks.
		)
	);

	if ( false === $attachment_meta_query ) {
		return array();
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the exact post/key query; duplicate alt owners must fail closed outside object caches.
	$attachment_meta_rows = $wpdb->get_results( $attachment_meta_query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $attachment_meta_rows ) ) {
		return array();
	}

	$meta_states = array(
		'_wp_attached_file'        => array(
			'exists'  => false,
			'meta_id' => 0,
			'value'   => '',
		),
		'_wp_attachment_image_alt' => array(
			'exists'  => false,
			'meta_id' => 0,
			'value'   => '',
		),
	);

	foreach ( $attachment_meta_rows as $attachment_meta_row ) {
		$meta_id        = is_array( $attachment_meta_row ) ? ai4seo_normalize_database_id( $attachment_meta_row['meta_id'] ?? null ) : false;
		$meta_key       = is_array( $attachment_meta_row ) && isset( $attachment_meta_row['meta_key'] ) && is_string( $attachment_meta_row['meta_key'] )
			? $attachment_meta_row['meta_key']
			: '';
		$raw_meta_value = is_array( $attachment_meta_row ) && array_key_exists( 'meta_value', $attachment_meta_row )
			? $attachment_meta_row['meta_value']
			: null;
		$meta_value     = $raw_meta_value;

		// WordPress wraps serialized-looking scalar metadata in one safe serialized-string layer.
		if ( is_string( $meta_value ) && is_serialized( $meta_value ) ) {
			$meta_value = ai4seo_safe_maybe_unserialize( $meta_value );
		}

		if ( false === $meta_id
			|| ! isset( $meta_states[ $meta_key ] )
			|| $meta_states[ $meta_key ]['exists']
			|| ! is_string( $meta_value )
			|| wp_check_invalid_utf8( $meta_value ) !== $meta_value
			|| false !== strpos( $meta_value, "\0" )
		) {
			return array();
		}

		$meta_states[ $meta_key ] = array(
			'exists'  => true,
			'meta_id' => $meta_id,
			'value'   => $meta_value,
		);
	}

	$read_succeeded = true;
	return array(
		'post'          => $post_row,
		'attached_file' => $meta_states['_wp_attached_file'],
		'alt_text'      => $meta_states['_wp_attachment_image_alt'],
	);
}


/**
 * Resolves an attachment URL without rereading post or postmeta storage.
 *
 * The exact snapshot remains authoritative. Native attachments prefer their verified attached-file
 * path under the current uploads base URL and fall back to the verified GUID. Other supported media
 * post types use their verified GUID directly.
 *
 * @param array $snapshot Snapshot returned by ai4seo_read_attachment_attribute_source_snapshot().
 * @return string|null Resolved URL, or null when the verified snapshot has no usable source.
 */
function ai4seo_resolve_attachment_url_from_source_snapshot( array $snapshot ): ?string {
	$post_state          = $snapshot['post'] ?? null;
	$attached_file_state = $snapshot['attached_file'] ?? null;
	$validate_url        = static function ( string $url ): ?string {
		$url        = trim( $url );
		$url_scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		if ( false === filter_var( $url, FILTER_VALIDATE_URL )
			|| ! is_string( $url_scheme )
			|| ! in_array( strtolower( $url_scheme ), array( 'http', 'https' ), true )
		) {
			return null;
		}

		return $url;
	};

	if ( ! is_array( $post_state )
		|| ! isset( $post_state['post_type'], $post_state['guid'] )
		|| ! is_string( $post_state['post_type'] )
		|| ! is_string( $post_state['guid'] )
		|| ! is_array( $attached_file_state )
		|| ! isset( $attached_file_state['exists'], $attached_file_state['value'] )
		|| ! is_bool( $attached_file_state['exists'] )
		|| ! is_string( $attached_file_state['value'] )
	) {
		return null;
	}

	$verified_guid = trim( $post_state['guid'] );

	if ( 'attachment' !== $post_state['post_type'] ) {
		return $validate_url( $verified_guid );
	}

	if ( $attached_file_state['exists'] && '' !== trim( $attached_file_state['value'] ) ) {
		$uploads = wp_get_upload_dir();

		if ( is_array( $uploads )
			&& empty( $uploads['error'] )
			&& isset( $uploads['basedir'], $uploads['baseurl'] )
			&& is_string( $uploads['basedir'] )
			&& is_string( $uploads['baseurl'] )
			&& '' !== $uploads['baseurl']
		) {
			$verified_file   = wp_normalize_path( trim( $attached_file_state['value'] ) );
			$uploads_basedir = rtrim( wp_normalize_path( $uploads['basedir'] ), '/' );
			$relative_file   = '';

			if ( '' !== $uploads_basedir && 0 === strpos( $verified_file, $uploads_basedir . '/' ) ) {
				$relative_file = substr( $verified_file, strlen( $uploads_basedir ) + 1 );
			} elseif ( 1 !== preg_match( '#^(?:[A-Za-z]:/|/)#', $verified_file ) ) {
				$relative_file = $verified_file;
			}

			$relative_file = ltrim( $relative_file, '/' );

			if ( '' !== $relative_file
				&& 0 !== strpos( $relative_file, '../' )
				&& false === strpos( $relative_file, '/../' )
				&& false === strpos( $relative_file, "\0" )
			) {
				$resolved_upload_url = $validate_url( rtrim( $uploads['baseurl'], '/' ) . '/' . $relative_file );

				if ( null !== $resolved_upload_url ) {
					return $resolved_upload_url;
				}
			}
		}
	}

	return $validate_url( $verified_guid );
}


/**
 * Returns the attachment attributes for a specific attachment post id.
 *
 * @param int       $attachment_post_id The post id of the attachment.
 * @param bool|null $read_succeeded Receives whether the exact one-ID snapshot was authoritative.
 * @param bool|null $post_exists Receives whether the attachment post exists.
 * @return array The attachment attributes.
 */
function ai4seo_read_available_attachment_attributes(
	int $attachment_post_id,
	?bool &$read_succeeded = null,
	?bool &$post_exists = null
): array {
	$attachment_attributes = array(
		'title'       => '',
		'caption'     => '',
		'description' => '',
		'alt-text'    => '',
	);
	$snapshot              = ai4seo_read_attachment_attribute_source_snapshot(
		$attachment_post_id,
		$read_succeeded,
		$post_exists
	);

	if ( ! $read_succeeded || ! $post_exists ) {
		return $attachment_attributes;
	}

	$attachment_attributes['title']       = $snapshot['post']['post_title'];
	$attachment_attributes['caption']     = $snapshot['post']['post_excerpt'];
	$attachment_attributes['description'] = $snapshot['post']['post_content'];
	$attachment_attributes['alt-text']    = $snapshot['alt_text']['value'];

	return $attachment_attributes;
}


/**
 * Builds the exact attachment-attribute coverage transition for one valid media post.
 *
 * @param int       $attachment_post_id Attachment post ID whose persisted attributes are authoritative.
 * @param bool      $clear_failed_generation_status Whether Failed must be absent after success.
 * @param bool      $clear_claimable_generation_status Whether Pending and Force must also be absent.
 * @param bool|null $read_succeeded Receives whether coverage and generated-data reads were authoritative.
 * @return array{additions: array, removals: array}|array{} Normalized transition maps, or empty on read failure.
 */
function ai4seo_build_attachment_attributes_coverage_post_id_option_transition(
	int $attachment_post_id,
	bool $clear_failed_generation_status = false,
	bool $clear_claimable_generation_status = false,
	?bool &$read_succeeded = null
): array {
	$read_succeeded = false;

	$coverage_option_names         = array(
		AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
		AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	);
	$coverage_read_succeeded       = false;
	$generated_data_read_succeeded = false;
	$is_fully_covered              = ai4seo_are_attachment_attributes_fully_covered( $attachment_post_id, $coverage_read_succeeded );
	$has_generated_data            = ai4seo_post_has_generated_data( $attachment_post_id, $generated_data_read_succeeded );

	if ( ! $coverage_read_succeeded || ! $generated_data_read_succeeded ) {
		return array();
	}

	$has_generated_data   = $is_fully_covered && $has_generated_data;
	$destination_option   = $is_fully_covered
		? AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME
		: AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;
	$transition_additions = array( $destination_option => array( $attachment_post_id ) );
	$transition_removals  = array();

	if ( $has_generated_data ) {
		$transition_additions[ AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ] = array( $attachment_post_id );
	}

	foreach ( $coverage_option_names as $coverage_option_name ) {
		if ( ! isset( $transition_additions[ $coverage_option_name ] ) ) {
			$transition_removals[ $coverage_option_name ] = array( $attachment_post_id );
		}
	}

	if ( $clear_failed_generation_status ) {
		$transition_removals[ AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ] = array( $attachment_post_id );
	}

	if ( $clear_claimable_generation_status ) {
		$transition_removals[ AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ]         = array( $attachment_post_id );
		$transition_removals[ AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ] = array( $attachment_post_id );
	}

	$read_succeeded = true;
	return array(
		'additions' => $transition_additions,
		'removals'  => $transition_removals,
	);
}


/**
 * Refreshes the attachment attributes coverage for the given post by putting the post id into the corresponding option
 *
 * @param int          $attachment_post_id The post id to refresh the attachment attributes coverage for.
 * @param WP_Post|null $post The post object to refresh the attachment attributes coverage for.
 * @param bool         $clear_failed_generation_status Whether a successful manual save also clears Failed.
 * @return bool True only when the complete requested coverage state was verified.
 */
function ai4seo_refresh_one_posts_attachment_attributes_coverage(
	int $attachment_post_id,
	$post = null,
	bool $clear_failed_generation_status = false
): bool {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 393113591, 'Prevented loop', true );
		return false;
	}

	if ( $attachment_post_id <= 0 ) {
		return false;
	}

	if ( ! ai4seo_is_post_a_valid_attachment( $attachment_post_id, $post ) ) {
		return ai4seo_remove_post_ids_from_all_options( $attachment_post_id );
	}

	$coverage_read_succeeded = false;
	$coverage_transition     = ai4seo_build_attachment_attributes_coverage_post_id_option_transition(
		$attachment_post_id,
		$clear_failed_generation_status,
		false,
		$coverage_read_succeeded
	);

	if ( ! $coverage_read_succeeded ) {
		return false;
	}

	return ai4seo_apply_post_id_option_transition(
		$coverage_transition['additions'],
		$coverage_transition['removals']
	);
}


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
	if ( ! in_array( (string) $attachment_post->post_type, $supported_attachment_post_types, true ) ) {
		return false;
	}

	$attachment_post_mime_type     = ai4seo_get_attachment_post_mime_type( $attachment_post_id );
	$allowed_attachment_mime_types = ai4seo_get_allowed_attachment_mime_types();

	// check mime type.
	if ( ! in_array( $attachment_post_mime_type, $allowed_attachment_mime_types, true ) ) {
		return false;
	}

	// check post status.
	if ( ! in_array( (string) $attachment_post->post_status, array( 'publish', 'future', 'private', 'pending', 'inherit' ), true ) ) {
		return false;
	}

	return true;
}


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
	$full_width          = 0;
	$full_height         = 0;

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


/**
 * Calls the attachment-attribute generation endpoint with one preselected image source.
 *
 * RobHub owns model repair attempts. A second request is permitted only when RobHub explicitly
 * confirms that its URL-source acquisition failed before model work and provides a one-time token
 * for client-assisted base64 recovery.
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

	// Keep the existing URL-first request as the normal path; only RobHub can authorize a distinct
	// local-source continuation after it proves no model attempt began.
	$url_response       = ai4seo_robhub_api()->call( 'ai4seo/generate-all-attachment-attributes', $robhub_api_call_parameters );
	$continuation_token = ai4seo_get_attachment_base64_recovery_token( $url_response );

	// Return every ordinary failure unchanged, including direct-base64, auth, credit, and model errors.
	if ( ! $continuation_token ) {
		return $url_response;
	}

	// This is the sole client-side continuation. Never feed attachment_url back into the
	// continuation, and never recurse after a base64 request fails.
	unset( $robhub_api_call_parameters['attachment_url'] );
	$robhub_api_call_parameters['attachment_recovery_token'] = $continuation_token;

	return ai4seo_generate_attachment_attributes_using_base64(
		$attachment_image_source,
		$robhub_api_call_parameters
	);
}


/**
 * Read the narrowly-scoped base64 continuation contract from a failed API response.
 *
 * @param mixed $response Normalized RobHub API response.
 * @return string Opaque continuation token, or an empty string when no continuation is allowed.
 */
function ai4seo_get_attachment_base64_recovery_token( $response ): string {
	// Recovery metadata is meaningful only on a failed URL-mode request; a successful response
	// must never prompt another generation request.
	if ( ! is_array( $response ) || ( $response['success'] ?? false ) ) {
		return '';
	}

	// Billing failures stop the account-scoped workflow even if malformed upstream metadata requests recovery.
	if ( isset( $response['code'] )
		&& ai4seo_robhub_api()->is_terminal_billing_error_code( $response['code'] )
	) {
		return '';
	}

	// Require the API's deliberately narrow contract rather than treating arbitrary error metadata
	// as permission to retry, which preserves RobHub's coordinated model-attempt budget.
	$recovery = $response['recovery'] ?? null;

	if ( ! is_array( $recovery ) || 'base64' !== ( $recovery['method'] ?? '' ) ) {
		return '';
	}

	// Validate the opaque token locally so an expired or malformed response fails without a second
	// HTTP request; server-side binding remains the authoritative security check.
	$token      = $recovery['continuation_token'] ?? '';
	$expires_at = $recovery['expires_at'] ?? 0;

	if ( ! is_string( $token )
		|| ! preg_match( '/^[A-Za-z0-9_-]{32,128}$/', $token )
		|| ! is_numeric( $expires_at )
		|| (int) $expires_at <= time() ) {
		return '';
	}

	return $token;
}


/**
 * Normalize an attachment image preparation stage for safe diagnostics.
 *
 * @param mixed $failure_stage Untrusted or internally supplied stage value.
 * @return string Allowlisted stage, or an empty string when unknown.
 */
function ai4seo_normalize_attachment_image_preparation_failure_stage( $failure_stage ): string {
	// Reject unexpected types before sanitization can coerce them into misleading diagnostics.
	if ( ! is_string( $failure_stage ) ) {
		return '';
	}

	// Keep only stages emitted by the bounded image-preparation pipeline.
	$failure_stage  = sanitize_key( $failure_stage );
	$allowed_stages = array(
		'decode_budget',
		'image_editor',
		'image_metadata',
		'derivative_encode',
		'derivative_metadata',
		'derivative_mime',
		'derivative_path',
		'derivative_read',
		'derivative_reopen',
		'derivative_resize',
		'derivative_save',
		'derivative_size',
		'loop_prevented',
	);

	if ( ! in_array( $failure_stage, $allowed_stages, true ) ) {
		return '';
	}

	return $failure_stage;
}


/**
 * Build a structured oversized media response for local/base64 attachment processing.
 *
 * @param int $content_length The known source size in bytes.
 * @return array
 */
function ai4seo_get_attachment_source_too_large_response( int $content_length = 0 ): array {
	// Mirror the RobHub oversized-fetch error so the existing failed-attachment handling can persist this state.
	$message = 'Content too large to fetch';

	if ( $content_length > 0 ) {
		$message .= ' (Content-Length: ' . $content_length . ' bytes)';
	}

	return array(
		'success' => false,
		'message' => $message,
		'code'    => 71214326,
	);
}


/**
 * Convert a TLS certificate failure into the structured attachment response contract.
 *
 * @param WP_Error $error The actionable TLS verification error.
 * @return array
 */
function ai4seo_get_attachment_source_tls_error_response( WP_Error $error ): array {
	// Preserve the transport error context because failed-attachment diagnostics consume each field.
	return array(
		'success'    => false,
		'message'    => $error->get_error_message(),
		'code'       => 101324725,
		'error_code' => $error->get_error_code(),
		'error_data' => $error->get_error_data(),
	);
}


/**
 * Load an image and return its base64 conversion result.
 *
 * @param string $image_url Image URL.
 * @return array Image conversion result.
 */
function ai4seo_get_base64_from_image_file( $image_url ): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 697474987, 'Prevented loop', true );
		return array(
			'success' => false,
			'message' => 'Infinite loop detected',
			'code'    => 91234725,
		);
	}

	// Keep the local/base64 path within the same media source-size envelope as the URL-based API path.
	$max_source_size      = AI4SEO_MAX_BASE64_ATTACHMENT_SOURCE_SIZE_BYTES;
	$same_site_local_path = ai4seo_get_same_site_local_file_path_from_url( $image_url );

	// Same-site media can be measured before loading the binary into PHP memory.
	if ( $same_site_local_path && ai4seo_is_file_larger_than( $same_site_local_path, $max_source_size ) ) {
		return ai4seo_get_attachment_source_too_large_response( ai4seo_get_file_size( $same_site_local_path ) );
	}

	// Remote media with Content-Length can be rejected before the body request.
	if ( ! $same_site_local_path ) {
		$remote_content_length = ai4seo_get_remote_content_length( $image_url );

		if ( $remote_content_length > $max_source_size ) {
			return ai4seo_get_attachment_source_too_large_response( $remote_content_length );
		}
	}

	// Prefer contained local reads before one bounded SSRF-safe remote attempt; insecure retries are intentionally excluded.
	try {
		foreach ( array( 'local_only', 'remote_only' ) as $fetch_mode ) {
			$image_body = ai4seo_get_remote_body( $image_url, $fetch_mode, $max_source_size );

			if ( is_wp_error( $image_body ) ) {
				// Convert the internal capped-fetch marker into the same structured API-style failure used below.
				if ( $image_body->get_error_code() === 'ai4seo_fetch_too_large' ) {
					return ai4seo_get_attachment_source_too_large_response();
				}

				if ( $image_body->get_error_code() === 'ai4seo_tls_verification_failed' ) {
					return ai4seo_get_attachment_source_tls_error_response( $image_body );
				}

				continue;
			}

			if ( ! $image_body ) {
				continue;
			}

			// Keep a final size guard in case the active WP HTTP transport does not honor limit_response_size.
			if ( strlen( $image_body ) > $max_source_size ) {
				return ai4seo_get_attachment_source_too_large_response( strlen( $image_body ) );
			}

			// Verify that the content is a valid image.
			$is_probably_image = ai4seo_is_probably_image_content( $image_body );

			if ( ! empty( $is_probably_image['is_probably_image'] ) ) {
				break;
			}
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => 'Media URL not accessible: ' . $e->getMessage(),
			'code'    => 91324725,
		);
	}

	if ( is_wp_error( $image_body ) ) {
		$remote_get_response_error = $image_body->get_error_message();

		return array(
			'success' => false,
			'message' => 'Media URL not accessible: ' . $remote_get_response_error,
			'code'    => 101324725,
		);
	}

	if ( ! $image_body ) {
		return array(
			'success' => false,
			'message' => 'Media content not accessible',
			'code'    => 111324725,
		);
	}

	if ( ! isset( $is_probably_image['is_probably_image'] ) || ! $is_probably_image['is_probably_image'] ) {
		return array(
			'success' => false,
			'message' => 'The fetched content is not a valid image',
			'code'    => 581927126,
		);
	}

	// Normalize the signature detector output so the encoder can report whether conversion changed the format.
	$source_mime_type = ai4seo_get_mime_type_from_detected_image_format(
		(string) ( $is_probably_image['detected_format'] ?? '' )
	);

	// Encode the attachment while collecting the actual post-conversion MIME for the data URI.
	$encoded_mime_type = $source_mime_type;
	$failure_stage     = '';

	try {
		$attachment_base64 = ai4seo_smart_image_base64_encode(
			$image_body,
			$source_mime_type,
			$encoded_mime_type,
			$failure_stage
		);
	} catch ( Exception $e ) {
		return array(
			'success'       => false,
			'message'       => 'Media content could not be base64 encoded: ' . $e->getMessage(),
			'code'          => 131324725,
			'failure_stage' => 'derivative_encode',
		);
	}

	if ( ! $attachment_base64 ) {
		// Normalize the by-reference stage and retain the established generic fallback for unknown failures.
		$failure_stage = ai4seo_normalize_attachment_image_preparation_failure_stage( $failure_stage );
		$failure_stage = '' !== $failure_stage ? $failure_stage : 'derivative_encode';

		return array(
			'success'       => false,
			'message'       => 'Media content could not be base64 encoded',
			'code'          => 141324725,
			'failure_stage' => $failure_stage,
		);
	}

	return array(
		'success'   => true,
		'data'      => $attachment_base64,
		'mime_type' => $encoded_mime_type,
	);
}


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
	$original_url             = (string) ( $attachment_image_source['original_url'] ?? '' );
	$delivery_url             = (string) ( $attachment_image_source['delivery_url'] ?? '' );
	$mime_type                = (string) ( $attachment_image_source['mime_type'] ?? '' );
	$is_recovery_continuation = ! empty( $robhub_api_call_parameters['attachment_recovery_token'] );

	// Reuse the bounded image fetcher so the established 25 MB source limit remains authoritative for base64.
	$base64_from_image_file_response = ai4seo_get_base64_from_image_file( $delivery_url );

	// Normalize all fetch failures before building the data URI so callers receive the established error contract.
	if ( ! isset( $base64_from_image_file_response['success'] ) || ! $base64_from_image_file_response['success']
		|| ! isset( $base64_from_image_file_response['data'] ) || ! $base64_from_image_file_response['data'] ) {
		if ( $is_recovery_continuation ) {
			ai4seo_log_attachment_base64_recovery_preparation_failure( $base64_from_image_file_response );
		}

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
	);

	// Fall back to the selected source MIME only when the encoded response does not identify one.
	if ( ! $encoded_mime_type ) {
		$encoded_mime_type = $mime_type;
	}

	// A base64 request is final. In particular, a server-guided continuation must not retain the
	// failed delivery URL, which also makes a token replay incapable of triggering URL retrieval.
	unset( $robhub_api_call_parameters['attachment_url'] );
	$robhub_api_call_parameters['reference_attachment_url'] = $original_url;
	$robhub_api_call_parameters['content']                  = "data:{$encoded_mime_type};base64,{$attachment_base64}";

	return ai4seo_robhub_api()->call( 'ai4seo/generate-all-attachment-attributes', $robhub_api_call_parameters );
}

/**
 * Create the safe diagnostic fields for a failed client-side Base64 recovery preparation.
 *
 * @param array $base64_response Image preparation response.
 * @return array Safe diagnostic fields containing the stage and source error code only.
 */
function ai4seo_get_attachment_base64_recovery_preparation_failure_diagnostic( array $base64_response ): array {
	$source_error_code = ( isset( $base64_response['code'] ) && is_numeric( $base64_response['code'] ) )
		? (int) $base64_response['code']
		: 0;
	$stage             = ai4seo_normalize_attachment_image_preparation_failure_stage(
		$base64_response['failure_stage'] ?? ''
	);

	// Fall back to the legacy error-code classification only when no precise safe stage was supplied.
	if ( '' === $stage ) {
		$stage = 'unknown';

		if ( in_array( $source_error_code, array( 91324725, 101324725, 111324725 ), true ) ) {
			$stage = 'media_fetch_failed';
		} elseif ( 71214326 === $source_error_code ) {
			$stage = 'media_source_too_large';
		} elseif ( 581927126 === $source_error_code ) {
			$stage = 'media_validation_failed';
		} elseif ( in_array( $source_error_code, array( 131324725, 141324725 ), true ) ) {
			$stage = 'base64_encoding_failed';
		} elseif ( 91234725 === $source_error_code ) {
			$stage = 'loop_prevented';
		}
	}

	return array(
		'stage'             => $stage,
		'source_error_code' => $source_error_code,
	);
}

/**
 * Log a sanitized diagnostic when a server-authorized Base64 recovery cannot be submitted.
 *
 * @param array $base64_response Image preparation response.
 * @return void
 */
function ai4seo_log_attachment_base64_recovery_preparation_failure( array $base64_response ): void {
	$diagnostic = ai4seo_get_attachment_base64_recovery_preparation_failure_diagnostic( $base64_response );

	// Never log the original URL, continuation token, server response, image bytes, or raw failure message.
	ai4seo_debug_message(
		870014824,
		'Attachment Base64 recovery preparation failed before continuation submission (stage: '
		. $diagnostic['stage']
		. '; source error code: '
		. $diagnostic['source_error_code']
		. ').'
	);
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
 * @param string          $derivative_mime_type      Requested output MIME type.
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
 * @param int $width  Image width in pixels.
 * @param int $height Image height in pixels.
 * @return bool Whether a decoder can be opened within the shared budget.
 */
function ai4seo_image_dimensions_fit_decode_budget( int $width, int $height ): bool {
	if ( $width < 1 || $height < 1 ) {
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

	// Even an unlimited PHP runtime retains a bounded source-canvas allocation ceiling.
	if ( $memory_limit > 0 ) {
		$available_memory              = $memory_limit
			- memory_get_usage( true )
			- $memory_reserve_bytes;
		$available_decode_budget       = min( $available_decode_budget, max( 0, $available_memory ) );
		$maximum_pixels_from_budget    = intdiv( $available_decode_budget, $decode_bytes_per_pixel );
		$maximum_width_for_this_height = intdiv( $maximum_pixels_from_budget, $height );
	}

	return $width <= $maximum_width_for_this_height;
}


/**
 * Determine whether prepared output dimensions satisfy the model-input canvas limit.
 *
 * @param int $width  Image width in pixels.
 * @param int $height Image height in pixels.
 * @return bool Whether the image can be submitted without further resizing.
 */
function ai4seo_image_dimensions_fit_model_output_budget( int $width, int $height ): bool {
	return $width > 0
		&& $height > 0
		&& $width <= AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION
		&& $height <= AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION
		&& ai4seo_image_dimensions_fit_decode_budget( $width, $height );
}


/**
 * Read and independently validate a generated image derivative.
 *
 * The image editor's save result is advisory. The bytes on disk remain authoritative for size,
 * MIME type, dimensions, and whether WordPress can decode the derivative again.
 *
 * @param string      $derivative_path       Local derivative path returned by the image editor.
 * @param string      $required_mime_type    MIME type required for the derivative.
 * @param string      $reported_mime_type    MIME type reported by the image editor.
 * @param int         $maximum_size_bytes    Maximum permitted derivative size.
 * @param string|null $failure_stage         Safe failure-stage identifier.
 * @return array|WP_Error Validated derivative data, or a normalized validation error.
 */
function ai4seo_read_validated_image_derivative(
	string $derivative_path,
	string $required_mime_type,
	string $reported_mime_type,
	int $maximum_size_bytes,
	?string &$failure_stage = null
) {
	$failure_stage = '';

	// A save result is not usable unless it identifies a readable local file.
	if ( '' === $derivative_path || ! is_file( $derivative_path ) || ! is_readable( $derivative_path ) ) {
		$failure_stage = 'derivative_path';
		return new WP_Error( 'ai4seo_image_derivative_path_invalid', 'Could not read the generated image derivative.' );
	}

	$derivative_size_bytes = ai4seo_get_file_size( $derivative_path );

	// Empty files and sizes that exceed the model-input bound must never be loaded into memory.
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
	if ( ! ai4seo_image_dimensions_fit_model_output_budget( $metadata_width, $metadata_height ) ) {
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
		if ( file_exists( $source_file_path ) ) {
			wp_delete_file( $source_file_path );
		}
	}
}


/**
 * Create a model-compatible base64 image, converting or downsizing when necessary.
 *
 * @param string      $image_data        The image data to encode.
 * @param string      $source_mime_type  MIME type detected from the source bytes.
 * @param string|null $encoded_mime_type Actual MIME type of the encoded output.
 * @param string|null $failure_stage     Safe failure-stage identifier.
 * @return string The base64-encoded image data, or an empty string on error.
 * @throws Exception When a derivative cannot be generated or validated.
 */
function ai4seo_smart_image_base64_encode(
	string $image_data,
	string $source_mime_type = '',
	?string &$encoded_mime_type = null,
	?string &$failure_stage = null
): string {
	// Default to the detected source MIME; a derivative branch replaces it with a compatible format below.
	$encoded_mime_type = ai4seo_normalize_mime_type_string( $source_mime_type ) ?? '';
	$failure_stage     = '';

	// Preserve the existing recursion guard before allocating temporary files or image-editor resources.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		$failure_stage = 'loop_prevented';
		ai4seo_debug_message( 241293986, 'Prevented loop', true );
		return '';
	}

	// Keep encoded images near the shared model-input target using a bounded conversion loop.
	$target_image_size_bytes    = AI4SEO_ATTACHMENT_GENERATION_TARGET_IMAGE_SIZE_BYTES;
	$temporary_image_file_paths = array();

	// Isolate backend and filesystem failures so every conversion error retains the established return contract.
	try {
		// Inspect every source before pass-through so compressed bytes cannot bypass canvas limits.
		$source_image_size_bytes        = strlen( $image_data );
		$requires_compatible_derivative = 'image/avif' === $encoded_mime_type;
		$source_dimensions              = ai4seo_get_source_image_dimensions_before_decode( $image_data, $encoded_mime_type );

		// Reject unsafe or unknown canvases before GD or Imagick can allocate decoded pixel memory.
		if ( false === $source_dimensions
			|| ! ai4seo_image_dimensions_fit_decode_budget( $source_dimensions['width'], $source_dimensions['height'] ) ) {
			$failure_stage = 'decode_budget';
			throw new Exception( 'The source image exceeds the safe decode budget.' );
		}

		$source_fits_output_canvas = ai4seo_image_dimensions_fit_model_output_budget(
			$source_dimensions['width'],
			$source_dimensions['height']
		);
		$requires_derivative       = $requires_compatible_derivative
			|| $source_image_size_bytes > $target_image_size_bytes
			|| ! $source_fits_output_canvas;

		if ( ! $requires_derivative ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode the validated bounded source payload for the model request.
			return base64_encode( $image_data );
		}

		// PNG preserves AVIF transparency across WordPress image backends; other derivatives use JPEG.
		$derivative_mime_type = $requires_compatible_derivative ? 'image/png' : 'image/jpeg';

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
		$quality_result = $image_editor->set_quality( AI4SEO_ATTACHMENT_GENERATION_DERIVATIVE_QUALITY );

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
			AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION / $current_dimensions['width'],
			AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION / $current_dimensions['height']
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
		for ( $encoding_attempt = 1; $encoding_attempt <= AI4SEO_ATTACHMENT_GENERATION_MAX_ENCODING_ATTEMPTS; ++$encoding_attempt ) {
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

				$validated_derivative = ai4seo_read_validated_image_derivative(
					$derivative_path,
					$derivative_mime_type,
					(string) ( $saved_derivative['mime-type'] ?? '' ),
					$target_image_size_bytes,
					$failure_stage
				);

				if ( is_wp_error( $validated_derivative ) ) {
					throw new Exception( $validated_derivative->get_error_message() );
				}

				$encoded_mime_type     = $validated_derivative['mime_type'];
				$derivative_image_data = $validated_derivative['data'];
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode the bounded derivative for the model request.
				return base64_encode( $derivative_image_data );
			}

			// Stop at the configured bound instead of performing an unbounded size-correction cycle.
			if ( AI4SEO_ATTACHMENT_GENERATION_MAX_ENCODING_ATTEMPTS === $encoding_attempt ) {
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
 * Reads the single provider row owned by an imported NextGEN entry.
 *
 * @param int $picture_id NextGEN picture ID.
 * @return array|false Provider row, or false when it cannot be read unambiguously.
 */
function ai4seo_read_nextgen_attachment_attribute_sync_row( int $picture_id ) {
	global $wpdb;

	$picture_id = absint( $picture_id );

	if ( $picture_id <= 0 ) {
		return false;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `pid`, `description`, `alttext`
		FROM {{pictures_table}}
		WHERE `pid` = {{picture_id}}
		LIMIT 2',
		array(
			'pictures_table' => ai4seo_database_identifier_binding( 'table.nextgen_pictures' ),
			'picture_id'     => ai4seo_database_scalar_binding( '%d', $picture_id ),
		)
	);

	if ( false === $query ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the optional provider identifier and pid; synchronization must preflight and verify the current provider row around its one-shot write.
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $rows ) || 1 !== count( $rows ) ) {
		return false;
	}

	return $rows[0];
}


/**
 * Checks whether a database row contains the exact requested field values.
 *
 * @param array $row Database row.
 * @param array $expected_values Expected field values.
 * @return bool True when every expected field has the same stored string value.
 */
function ai4seo_attachment_attribute_row_matches_values( array $row, array $expected_values ): bool {
	foreach ( $expected_values as $field => $expected_value ) {
		if ( ! array_key_exists( $field, $row ) || (string) $expected_value !== (string) $row[ $field ] ) {
			return false;
		}
	}

	return true;
}


/**
 * Compares and sets allowed provider fields for one imported NextGEN entry.
 *
 * @param int   $picture_id NextGEN picture ID.
 * @param array $desired_values Values to store.
 * @param array $expected_values Values that must still be current for this write to own the change.
 * @return array Result with succeeded, changed, and conflicted boolean keys.
 */
function ai4seo_compare_and_set_nextgen_attachment_attribute_sync_row( int $picture_id, array $desired_values, array $expected_values ): array {
	global $wpdb;

	$picture_id     = absint( $picture_id );
	$allowed_fields = array(
		'description' => true,
		'alttext'     => true,
	);

	$result = array(
		'succeeded'  => false,
		'changed'    => false,
		'conflicted' => false,
	);

	if (
		$picture_id <= 0
		|| ! $desired_values
		|| array_diff_key( $desired_values, $allowed_fields )
		|| array_diff_key( $expected_values, $allowed_fields )
		|| array_keys( $desired_values ) !== array_keys( $expected_values )
	) {
		return $result;
	}

	// Canonical field order selects one of the closed query templates below.
	$normalized_desired_values  = array();
	$normalized_expected_values = array();

	foreach ( array_keys( $allowed_fields ) as $field ) {
		if ( array_key_exists( $field, $desired_values ) ) {
			$normalized_desired_values[ $field ]  = $desired_values[ $field ];
			$normalized_expected_values[ $field ] = $expected_values[ $field ];
		}
	}

	$query_templates = array(
		'description'         => 'UPDATE {{pictures_table}} SET `description` = {{desired_description}} WHERE `pid` = {{picture_id}} AND BINARY `description` = BINARY {{expected_description}} LIMIT 1',
		'alttext'             => 'UPDATE {{pictures_table}} SET `alttext` = {{desired_alttext}} WHERE `pid` = {{picture_id}} AND BINARY `alttext` = BINARY {{expected_alttext}} LIMIT 1',
		'description,alttext' => 'UPDATE {{pictures_table}} SET `description` = {{desired_description}}, `alttext` = {{desired_alttext}} WHERE `pid` = {{picture_id}} AND BINARY `description` = BINARY {{expected_description}} AND BINARY `alttext` = BINARY {{expected_alttext}} LIMIT 1',
	);
	$query_key       = implode( ',', array_keys( $normalized_desired_values ) );
	$query_template  = $query_templates[ $query_key ] ?? '';

	if ( '' === $query_template ) {
		return $result;
	}

	$bindings = array(
		'pictures_table' => ai4seo_database_identifier_binding( 'table.nextgen_pictures' ),
		'picture_id'     => ai4seo_database_scalar_binding( '%d', $picture_id ),
	);

	foreach ( $normalized_desired_values as $field => $value ) {
		$bindings[ 'desired_' . $field ]  = ai4seo_database_scalar_binding( '%s', $value );
		$bindings[ 'expected_' . $field ] = ai4seo_database_scalar_binding( '%s', $normalized_expected_values[ $field ] );
	}

	$query = ai4seo_prepare_database_query( $query_template, $bindings );

	if ( false === $query ) {
		return $result;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A closed typed template uses BINARY comparisons for byte-exact provider CAS; uncached readback distinguishes an equal no-op from a raced writer.
	$update_result = $wpdb->query( $query );

	if ( false === $update_result || $wpdb->last_error ) {
		return $result;
	}

	$result['changed'] = (int) $update_result > 0;

	$current_row = ai4seo_read_nextgen_attachment_attribute_sync_row( $picture_id );

	if ( false === $current_row ) {
		return $result;
	}

	if ( ai4seo_attachment_attribute_row_matches_values( $current_row, $normalized_desired_values ) ) {
		$result['succeeded'] = true;
		return $result;
	}

	$result['conflicted'] = true;
	return $result;
}


/**
 * Reads current WordPress-owned post columns without consulting the object cache.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return array|false Current post row, or false on absence/database failure.
 */
function ai4seo_read_attachment_attribute_post_row( int $attachment_post_id ) {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return false;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `ID`, `post_title`, `post_excerpt`, `post_content`
		FROM {{posts_table}}
		WHERE `ID` = {{post_id}}
		LIMIT 1',
		array(
			'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
			'post_id'     => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
		)
	);

	if ( false === $query ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the post identifier and ID; compare-and-set verification must bypass the possibly stale post object cache.
	$row = $wpdb->get_row( $query, ARRAY_A );

	return $wpdb->last_error || ! is_array( $row ) ? false : $row;
}


/**
 * Compares and sets WordPress-owned post columns used for attachment attributes.
 *
 * @param int   $attachment_post_id Attachment post ID.
 * @param array $desired_values Values to store.
 * @param array $expected_values Values that must still be current for this write to own the change.
 * @return array Result with succeeded, changed, and conflicted boolean keys.
 */
function ai4seo_compare_and_set_attachment_attribute_post_columns( int $attachment_post_id, array $desired_values, array $expected_values ): array {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );
	$allowed_columns    = array(
		'post_title'   => true,
		'post_excerpt' => true,
		'post_content' => true,
	);

	$result = array(
		'succeeded'  => false,
		'changed'    => false,
		'conflicted' => false,
	);

	if (
		$attachment_post_id <= 0
		|| ! $desired_values
		|| array_diff_key( $desired_values, $allowed_columns )
		|| array_diff_key( $expected_values, $allowed_columns )
		|| array_keys( $desired_values ) !== array_keys( $expected_values )
	) {
		return $result;
	}

	// Canonical column order selects one of the closed query templates below.
	$normalized_desired_values  = array();
	$normalized_expected_values = array();

	foreach ( array_keys( $allowed_columns ) as $column ) {
		if ( array_key_exists( $column, $desired_values ) ) {
			$normalized_desired_values[ $column ]  = $desired_values[ $column ];
			$normalized_expected_values[ $column ] = $expected_values[ $column ];
		}
	}

	$query_templates = array(
		'post_title'                           => 'UPDATE {{posts_table}} SET `post_title` = {{desired_post_title}} WHERE `ID` = {{post_id}} AND BINARY `post_title` = BINARY {{expected_post_title}} LIMIT 1',
		'post_excerpt'                         => 'UPDATE {{posts_table}} SET `post_excerpt` = {{desired_post_excerpt}} WHERE `ID` = {{post_id}} AND BINARY `post_excerpt` = BINARY {{expected_post_excerpt}} LIMIT 1',
		'post_content'                         => 'UPDATE {{posts_table}} SET `post_content` = {{desired_post_content}} WHERE `ID` = {{post_id}} AND BINARY `post_content` = BINARY {{expected_post_content}} LIMIT 1',
		'post_title,post_excerpt'              => 'UPDATE {{posts_table}} SET `post_title` = {{desired_post_title}}, `post_excerpt` = {{desired_post_excerpt}} WHERE `ID` = {{post_id}} AND BINARY `post_title` = BINARY {{expected_post_title}} AND BINARY `post_excerpt` = BINARY {{expected_post_excerpt}} LIMIT 1',
		'post_title,post_content'              => 'UPDATE {{posts_table}} SET `post_title` = {{desired_post_title}}, `post_content` = {{desired_post_content}} WHERE `ID` = {{post_id}} AND BINARY `post_title` = BINARY {{expected_post_title}} AND BINARY `post_content` = BINARY {{expected_post_content}} LIMIT 1',
		'post_excerpt,post_content'            => 'UPDATE {{posts_table}} SET `post_excerpt` = {{desired_post_excerpt}}, `post_content` = {{desired_post_content}} WHERE `ID` = {{post_id}} AND BINARY `post_excerpt` = BINARY {{expected_post_excerpt}} AND BINARY `post_content` = BINARY {{expected_post_content}} LIMIT 1',
		'post_title,post_excerpt,post_content' => 'UPDATE {{posts_table}} SET `post_title` = {{desired_post_title}}, `post_excerpt` = {{desired_post_excerpt}}, `post_content` = {{desired_post_content}} WHERE `ID` = {{post_id}} AND BINARY `post_title` = BINARY {{expected_post_title}} AND BINARY `post_excerpt` = BINARY {{expected_post_excerpt}} AND BINARY `post_content` = BINARY {{expected_post_content}} LIMIT 1',
	);
	$query_key       = implode( ',', array_keys( $normalized_desired_values ) );
	$query_template  = $query_templates[ $query_key ] ?? '';

	if ( '' === $query_template ) {
		return $result;
	}

	$bindings = array(
		'posts_table' => ai4seo_database_identifier_binding( 'table.posts' ),
		'post_id'     => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
	);

	foreach ( $normalized_desired_values as $column => $value ) {
		$bindings[ 'desired_' . $column ]  = ai4seo_database_scalar_binding( '%s', $value );
		$bindings[ 'expected_' . $column ] = ai4seo_database_scalar_binding( '%s', $normalized_expected_values[ $column ] );
	}

	$query = ai4seo_prepare_database_query( $query_template, $bindings );

	if ( false === $query ) {
		return $result;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A closed typed template uses BINARY comparisons for byte-exact post CAS while preserving unslashed storage; uncached readback distinguishes an equal no-op from a raced writer.
	$update_result = $wpdb->query( $query );

	if ( false === $update_result || $wpdb->last_error ) {
		return $result;
	}

	$result['changed'] = (int) $update_result > 0;
	$current_row       = ai4seo_read_attachment_attribute_post_row( $attachment_post_id );

	if ( false === $current_row ) {
		return $result;
	}

	if ( ai4seo_attachment_attribute_row_matches_values( $current_row, $normalized_desired_values ) ) {
		$result['succeeded'] = true;
		return $result;
	}

	$result['conflicted'] = true;
	return $result;
}


/**
 * Reads the exact WordPress alt-text metadata state without consulting the object cache.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return array|false State with exists, meta_id, and value keys, or false on ambiguity/failure.
 */
function ai4seo_read_attachment_alt_text_postmeta_state( int $attachment_post_id ) {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return false;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `meta_id`, `meta_value`
		FROM {{postmeta_table}}
		WHERE `post_id` = {{post_id}}
		AND `meta_key` = {{meta_key}}
		ORDER BY `meta_id` ASC
		LIMIT 2',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact key is bounded by one post ID and two rows to establish an unambiguous CAS owner.
		)
	);

	if ( false === $query ) {
		return false;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the core identifier and exact key/ID values; this one-shot preflight must bypass a possibly stale metadata cache.
	$rows = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) {
		return false;
	}

	if ( ! $rows ) {
		return array(
			'exists'  => false,
			'meta_id' => 0,
			'value'   => '',
		);
	}

	$meta_id = absint( $rows[0]['meta_id'] ?? 0 );

	if ( $meta_id <= 0 || ! array_key_exists( 'meta_value', $rows[0] ) ) {
		return false;
	}

	return array(
		'exists'  => true,
		'meta_id' => $meta_id,
		'value'   => (string) $rows[0]['meta_value'],
	);
}


/**
 * Reads one exact attachment alt-text metadata owner by its primary key.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @param int $meta_id Postmeta primary key.
 * @return array Result with succeeded, exists, and value keys.
 */
function ai4seo_read_attachment_alt_text_postmeta_row_by_id( int $attachment_post_id, int $meta_id ): array {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );
	$meta_id            = absint( $meta_id );
	$result             = array(
		'succeeded' => false,
		'exists'    => false,
		'value'     => '',
	);

	if ( $attachment_post_id <= 0 || $meta_id <= 0 ) {
		return $result;
	}

	$query = ai4seo_prepare_database_query(
		'SELECT `meta_id`, `meta_value`
		FROM {{postmeta_table}}
		WHERE `meta_id` = {{meta_id}}
		AND `post_id` = {{post_id}}
		AND `meta_key` = {{meta_key}}
		LIMIT 1',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
			'meta_key'       => ai4seo_database_scalar_binding( '%s', '_wp_attachment_image_alt' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary meta ID and post ID bound this ownership verification to one row.
		)
	);

	if ( false === $query ) {
		return $result;
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed compiler owns the core identifier and exact primary/key/post bindings; rollback verification must bypass stale metadata caches.
	$row = $wpdb->get_row( $query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return $result;
	}

	$result['succeeded'] = true;

	if ( is_array( $row ) && absint( $row['meta_id'] ?? 0 ) === $meta_id && array_key_exists( 'meta_value', $row ) ) {
		$result['exists'] = true;
		$result['value']  = (string) $row['meta_value'];
	}

	return $result;
}


/**
 * Compares and sets one attachment alt-text row against an exact preflight state.
 *
 * Missing rows use a guarded insert. Existing rows use their captured ID and a byte-exact value
 * predicate. A zero-row result is accepted only when one unambiguous row already has the desired
 * bytes; every other zero-row result is a concurrent-write conflict.
 *
 * @param int    $attachment_post_id Attachment post ID.
 * @param string $desired_value Desired unslashed alt text.
 * @param array  $expected_state Exact state returned by ai4seo_read_attachment_alt_text_postmeta_state().
 * @return array Result with succeeded, changed, conflicted, and ownership keys.
 */
function ai4seo_compare_and_set_attachment_alt_text_postmeta( int $attachment_post_id, string $desired_value, array $expected_state ): array {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );
	$meta_key           = '_wp_attachment_image_alt';
	$result             = array(
		'succeeded'  => false,
		'changed'    => false,
		'conflicted' => false,
		'ownership'  => array(),
	);

	if (
		$attachment_post_id <= 0
		|| ! array_key_exists( 'exists', $expected_state )
		|| ! is_bool( $expected_state['exists'] )
		|| ! array_key_exists( 'meta_id', $expected_state )
		|| ! array_key_exists( 'value', $expected_state )
	) {
		return $result;
	}

	// Match update_post_meta(): sanitize the unslashed request before its authoritative update filter.
	$meta_subtype      = get_object_subtype( 'post', $attachment_post_id );
	$update_hook_value = sanitize_meta( $meta_key, $desired_value, 'post', $meta_subtype );
	$update_check      = apply_filters(
		'update_post_metadata',
		null,
		$attachment_post_id,
		$meta_key,
		$update_hook_value,
		''
	);

	// A non-null metadata filter owns the operation exactly as it does in WordPress core.
	if ( null !== $update_check ) {
		$result['succeeded'] = (bool) $update_check;
		return $result;
	}

	if ( $expected_state['exists'] ) {
		$expected_meta_id = absint( $expected_state['meta_id'] );

		if ( $expected_meta_id <= 0 ) {
			return $result;
		}

		$hook_value       = $update_hook_value;
		$serialized_value = maybe_serialize( $hook_value );
		$stored_value     = (string) $serialized_value;

		// WordPress returns before update actions when the one existing value already matches.
		if ( $stored_value === (string) $expected_state['value'] ) {
			$result['succeeded'] = true;
			return $result;
		}

		$query = ai4seo_prepare_database_query(
			'UPDATE {{postmeta_table}}
			SET `meta_value` = {{desired_value}}
			WHERE `meta_id` = {{meta_id}}
			AND `post_id` = {{post_id}}
			AND `meta_key` = {{meta_key}}
			AND BINARY `meta_value` = BINARY {{expected_value}}
			LIMIT 1',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'desired_value'  => ai4seo_database_scalar_binding( '%s', $stored_value ),
				'meta_id'        => ai4seo_database_scalar_binding( '%d', $expected_meta_id ),
				'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary meta ID and post ID bound this byte-exact ownership check to one row.
				'expected_value' => ai4seo_database_scalar_binding( '%s', (string) $expected_state['value'] ),
			)
		);
	} else {
		// update_metadata() delegates a missing owner to add_metadata(), which sanitizes and filters again.
		$hook_value = sanitize_meta( $meta_key, $desired_value, 'post', $meta_subtype );
		$add_check  = apply_filters(
			'add_post_metadata',
			null,
			$attachment_post_id,
			$meta_key,
			$hook_value,
			false
		);

		if ( null !== $add_check ) {
			$result['succeeded'] = (bool) $add_check;
			return $result;
		}

		$serialized_value = maybe_serialize( $hook_value );
		$stored_value     = (string) $serialized_value;

		$query = ai4seo_prepare_database_query(
			'INSERT INTO {{postmeta_insert_table}} (`post_id`, `meta_key`, `meta_value`)
			SELECT {{insert_post_id}}, {{insert_meta_key}}, {{insert_meta_value}}
			WHERE NOT EXISTS (
				SELECT 1
				FROM {{postmeta_exists_table}}
				WHERE `post_id` = {{existing_post_id}}
				AND `meta_key` = {{existing_meta_key}}
			)',
			array(
				'postmeta_insert_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'insert_post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
				'insert_meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
				'insert_meta_value'     => ai4seo_database_scalar_binding( '%s', $stored_value ),
				'postmeta_exists_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'existing_post_id'      => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
				'existing_meta_key'     => ai4seo_database_scalar_binding( '%s', $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The exact key and post ID guard the single-row insert against a concurrent metadata owner.
			)
		);
	}

	if ( false === $query ) {
		return $result;
	}

	// Reproduce core's pre-write actions only after every filter has allowed the direct mutation.
	if ( $expected_state['exists'] ) {
		do_action( 'update_post_meta', $expected_meta_id, $attachment_post_id, $meta_key, $hook_value );
		do_action( 'update_postmeta', $expected_meta_id, $attachment_post_id, $meta_key, $serialized_value );
	} else {
		do_action( 'add_post_meta', $attachment_post_id, $meta_key, $hook_value );
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Closed typed templates perform the missing/exact-value CAS; caller-owned cache invalidation follows every mutation or observed conflict.
	$write_result = $wpdb->query( $query );

	if ( false === $write_result || $wpdb->last_error ) {
		return $result;
	}

	if ( (int) $write_result > 1 ) {
		return $result;
	}

	$result['changed'] = 1 === (int) $write_result;

	if ( $result['changed'] ) {
		if ( $expected_state['exists'] ) {
			$result['ownership'] = array(
				'type'                => 'update',
				'meta_id'             => $expected_meta_id,
				'previous_value'      => (string) $expected_state['value'],
				'previous_hook_value' => (string) $expected_state['value'],
				'written_value'       => $stored_value,
				'written_hook_value'  => $hook_value,
			);
		} else {
			$inserted_meta_id = absint( $wpdb->insert_id );

			if ( $inserted_meta_id <= 0 ) {
				return $result;
			}

			$result['ownership'] = array(
				'type'                => 'insert',
				'meta_id'             => $inserted_meta_id,
				'previous_value'      => '',
				'previous_hook_value' => '',
				'written_value'       => $stored_value,
				'written_hook_value'  => $hook_value,
			);
		}

		// Match WordPress cache/action order so post-write observers read the committed value.
		wp_cache_delete( $attachment_post_id, 'post_meta' );

		if ( $expected_state['exists'] ) {
			do_action( 'updated_post_meta', $expected_meta_id, $attachment_post_id, $meta_key, $hook_value );
			do_action( 'updated_postmeta', $expected_meta_id, $attachment_post_id, $meta_key, $serialized_value );
		} else {
			do_action( 'added_post_meta', $inserted_meta_id, $attachment_post_id, $meta_key, $hook_value );
		}
	}

	$current_state = ai4seo_read_attachment_alt_text_postmeta_state( $attachment_post_id );

	if ( false === $current_state ) {
		if ( 0 === (int) $write_result && ! $wpdb->last_error ) {
			$result['conflicted'] = true;
		}

		return $result;
	}

	if ( $current_state['exists'] && $stored_value === $current_state['value'] ) {
		$result['succeeded'] = true;
		return $result;
	}

	$result['conflicted'] = true;
	return $result;
}


/**
 * Reverses only the exact alt-text postmeta mutation owned by one failed operation.
 *
 * Updated rows are restored only while their current bytes still match the operation's write.
 * Inserted rows are deleted only by their captured primary key and exact written bytes. Absence is
 * accepted because a concurrent delete also removes this operation's partial state.
 *
 * @param int   $attachment_post_id Attachment post ID.
 * @param array $ownership Exact ownership returned by ai4seo_compare_and_set_attachment_alt_text_postmeta().
 * @return bool True when this operation's alt-text mutation is absent or restored.
 */
function ai4seo_reverse_attachment_alt_text_postmeta_write( int $attachment_post_id, array $ownership ): bool {
	global $wpdb;

	$attachment_post_id  = absint( $attachment_post_id );
	$ownership_type      = (string) ( $ownership['type'] ?? '' );
	$meta_id             = absint( $ownership['meta_id'] ?? 0 );
	$meta_key            = '_wp_attachment_image_alt';
	$previous_value      = (string) ( $ownership['previous_value'] ?? '' );
	$written_value       = (string) ( $ownership['written_value'] ?? '' );
	$previous_hook_value = array_key_exists( 'previous_hook_value', $ownership )
		? $ownership['previous_hook_value']
		: $previous_value;
	$written_hook_value  = array_key_exists( 'written_hook_value', $ownership )
		? $ownership['written_hook_value']
		: $written_value;

	if ( $attachment_post_id <= 0 || $meta_id <= 0 || ! in_array( $ownership_type, array( 'insert', 'update' ), true ) ) {
		return false;
	}

	if ( 'update' === $ownership_type ) {
		$meta_subtype        = get_object_subtype( 'post', $attachment_post_id );
		$rollback_hook_value = sanitize_meta( $meta_key, $previous_hook_value, 'post', $meta_subtype );
		$rollback_check      = apply_filters(
			'update_post_metadata',
			null,
			$attachment_post_id,
			$meta_key,
			$rollback_hook_value,
			$written_hook_value
		);

		if ( null !== $rollback_check ) {
			return (bool) $rollback_check;
		}

		// Restore the exact captured bytes; maybe_serialize() would double-serialize scalar text that resembles serialized data.
		$serialized_rollback_value = $previous_value;
		$restored_value            = $previous_value;

		$query = ai4seo_prepare_database_query(
			'UPDATE {{postmeta_table}}
			SET `meta_value` = {{previous_value}}
			WHERE `meta_id` = {{meta_id}}
			AND `post_id` = {{post_id}}
			AND `meta_key` = {{meta_key}}
			AND BINARY `meta_value` = BINARY {{written_value}}
			LIMIT 1',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'previous_value' => ai4seo_database_scalar_binding( '%s', $restored_value ),
				'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
				'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary meta ID and post ID bound this reverse CAS to the operation-owned row.
				'written_value'  => ai4seo_database_scalar_binding( '%s', $written_value ),
			)
		);
	} else {
		$rollback_hook_value = $written_hook_value;
		$rollback_check      = apply_filters(
			'delete_post_metadata',
			null,
			$attachment_post_id,
			$meta_key,
			$rollback_hook_value,
			false
		);

		if ( null !== $rollback_check ) {
			return (bool) $rollback_check;
		}

		$query = ai4seo_prepare_database_query(
			'DELETE FROM {{postmeta_table}}
			WHERE `meta_id` = {{meta_id}}
			AND `post_id` = {{post_id}}
			AND `meta_key` = {{meta_key}}
			AND BINARY `meta_value` = BINARY {{written_value}}
			LIMIT 1',
			array(
				'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
				'meta_id'        => ai4seo_database_scalar_binding( '%d', $meta_id ),
				'post_id'        => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
				'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The primary meta ID and post ID restrict deletion to the operation-owned insert.
				'written_value'  => ai4seo_database_scalar_binding( '%s', $written_value ),
			)
		);
	}

	if ( false === $query ) {
		return false;
	}

	// Do not announce a reverse mutation when a concurrent owner has already removed or replaced it.
	$current_owner = ai4seo_read_attachment_alt_text_postmeta_row_by_id( $attachment_post_id, $meta_id );

	if ( ! $current_owner['succeeded'] ) {
		return false;
	}

	if ( ! $current_owner['exists'] ) {
		return true;
	}

	if ( $written_value !== $current_owner['value'] ) {
		return 'update' === $ownership_type && $restored_value === $current_owner['value'];
	}

	if ( 'update' === $ownership_type ) {
		do_action( 'update_post_meta', $meta_id, $attachment_post_id, $meta_key, $rollback_hook_value );
		do_action( 'update_postmeta', $meta_id, $attachment_post_id, $meta_key, $serialized_rollback_value );
	} else {
		$meta_ids = array( (string) $meta_id );
		do_action( 'delete_post_meta', $meta_ids, $attachment_post_id, $meta_key, $rollback_hook_value );
		do_action( 'delete_postmeta', $meta_ids );
	}

	$wpdb->last_error = '';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Closed typed reverse-CAS/delete templates target only the captured primary key and exact bytes; caller repairs caches after the attempt.
	$reverse_result = $wpdb->query( $query );

	if ( false === $reverse_result || $wpdb->last_error || (int) $reverse_result > 1 ) {
		return false;
	}

	if ( 1 === (int) $reverse_result ) {
		wp_cache_delete( $attachment_post_id, 'post_meta' );

		if ( 'update' === $ownership_type ) {
			do_action( 'updated_post_meta', $meta_id, $attachment_post_id, $meta_key, $rollback_hook_value );
			do_action( 'updated_postmeta', $meta_id, $attachment_post_id, $meta_key, $serialized_rollback_value );
		} else {
			do_action( 'deleted_post_meta', $meta_ids, $attachment_post_id, $meta_key, $rollback_hook_value );
			do_action( 'deleted_postmeta', $meta_ids );
		}
	}

	$current_owner = ai4seo_read_attachment_alt_text_postmeta_row_by_id( $attachment_post_id, $meta_id );

	if ( ! $current_owner['succeeded'] ) {
		return false;
	}

	if ( ! $current_owner['exists'] ) {
		return true;
	}

	return 'update' === $ownership_type && $restored_value === $current_owner['value'];
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound


/**
 * Returns the bounded advisory-lock name for one site's attachment alt-text owner.
 *
 * @param int $attachment_post_id Attachment post ID.
 * @return string Site/post/meta-scoped lock name, or an empty string for invalid input.
 */
function ai4seo_get_attachment_alt_text_postmeta_lock_name( int $attachment_post_id ): string {
	global $wpdb;

	$attachment_post_id = absint( $attachment_post_id );

	if ( $attachment_post_id <= 0 ) {
		return '';
	}

	$database_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
	$scope_hash    = substr( hash( 'sha256', $database_name . '|' . (string) $wpdb->prefix . '|' . absint( get_current_blog_id() ) ), 0, 32 );

	// The database/prefix/site hash prevents server-wide GET_LOCK collisions; the longest signed ID keeps this at 63 bytes.
	return 'ai4seo_alt_' . $scope_hash . '_' . $attachment_post_id;
}


/**
 * Serializes one alt-text CAS and reverses any unverifiable owned write before releasing the lock.
 *
 * @param int    $attachment_post_id Attachment post ID.
 * @param string $desired_value Desired unslashed alt text.
 * @param array  $expected_state Exact preflight state.
 * @return array CAS result plus rollback_attempted and rollback_succeeded keys.
 */
function ai4seo_write_attachment_alt_text_postmeta_with_lock( int $attachment_post_id, string $desired_value, array $expected_state ): array {
	$lock_name = ai4seo_get_attachment_alt_text_postmeta_lock_name( $attachment_post_id );
	$result    = array(
		'succeeded'          => false,
		'changed'            => false,
		'conflicted'         => false,
		'ownership'          => array(),
		'rollback_attempted' => false,
		'rollback_succeeded' => true,
	);

	if ( '' === $lock_name || ! ai4seo_acquire_database_advisory_lock( $lock_name ) ) {
		return $result;
	}

	try {
		$result                       = ai4seo_compare_and_set_attachment_alt_text_postmeta( $attachment_post_id, $desired_value, $expected_state );
		$result['rollback_attempted'] = false;
		$result['rollback_succeeded'] = true;

		if ( ! $result['succeeded'] && $result['ownership'] ) {
			$result['rollback_attempted'] = true;
			$result['rollback_succeeded'] = ai4seo_reverse_attachment_alt_text_postmeta_write( $attachment_post_id, $result['ownership'] );
		}

		return $result;
	} finally {
		if ( ! ai4seo_release_database_advisory_lock( $lock_name ) ) {
			ai4seo_debug_message( 984321724, 'Could not release the attachment alt-text database advisory lock.', true );
		}
	}
}

/**
 * Updates the currently active attachment attributes for an attachment.
 *
 * Multi-owner writes use provider, posts, then postmeta order. Every later failure attempts to
 * restore earlier owners from captured values. If compensation itself fails, false remains an
 * idempotent retry signal and caches are repaired for any WordPress post state that may remain.
 *
 * @param int        $attachment_post_id the attachment post id.
 * @param array      $attachment_attribute_updates the updates to apply with the keys title, caption, description, alt-text.
 * @param bool       $force_overwrite_all_existing_data if true, existing data will be overwritten, if false, we check the settings to identify the attachment attributes that should be overwritten.
 * @param array|null $operation_details Receives commit_state: not_committed, committed, or possibly_committed.
 * @return bool true on success, false on failure
 */
function ai4seo_update_attachment_attributes(
	int $attachment_post_id,
	array $attachment_attribute_updates = array(),
	bool $force_overwrite_all_existing_data = false,
	?array &$operation_details = null
): bool {
	global $wpdb;

	$operation_details = array(
		'commit_state' => 'not_committed',
	);

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 540510370, 'Prevented loop', true );
		return false;
	}

	// Normalize API/editor values through the shared field sanitizer before selecting persistence owners.
	$attachment_attribute_updates = ai4seo_deep_sanitize( $attachment_attribute_updates, 'ai4seo_sanitize_editor_field_value' );

	// Apply field-specific overwrite settings unless the caller explicitly forces every owner.
	$overwrite_existing_data_attachment_attributes_names = array();

	if ( ! $force_overwrite_all_existing_data ) {
		$overwrite_existing_data_attachment_attributes_names = ai4seo_normalize_attachment_attribute_identifier_list(
			(array) ai4seo_get_setting( AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES )
		);
	}

	$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	// Load the attachment once so owner selection and every CAS preflight share the same starting state.
	$attachment_post = get_post( $attachment_post_id );

	if ( ! $attachment_post ) {
		return false;
	}

	// Collect every owner write before mutating any table.
	$wordpress_owner_reached_desired_state = false;

	$post_updates              = array();
	$original_post_values      = array();
	$postmeta_update_requested = false;
	$postmeta_update_value     = '';
	$postmeta_expected_state   = array();
	$nextgen_gallery_updates   = array();
	$is_imported_nextgen_entry = ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY )
		&& AI4SEO_NEXTGEN_GALLERY_POST_TYPE === $attachment_post->post_type
		&& absint( $attachment_post->post_parent ) > 0;

	// Resolve requested fields into their WordPress and optional provider write sets before any mutation.
	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details ) {
		if ( ! in_array( $this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes, true ) ) {
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

		// Resolve the caller-wide and field-specific overwrite policies before inspecting current values.
		if ( true === $force_overwrite_all_existing_data ) {
			$overwrite_this_attachment_attribute = true;
		} else {
			$overwrite_this_attachment_attribute = in_array( $this_attachment_attribute_identifier, $overwrite_existing_data_attachment_attributes_names, true );
		}

		// Normalize and bound every value with the same editor-field contract used by interactive saves.
		$this_attachment_attribute_value = ai4seo_normalize_editor_input_value( $this_attachment_attribute_value );
		$this_max_length                 = ai4seo_get_max_editor_input_length( $this_attachment_attribute_identifier );
		$this_attachment_attribute_value = ai4seo_trim_string_to_length( $this_attachment_attribute_value, $this_max_length );

		// Route post fields and alt text to their distinct persistence and CAS owners.
		if ( in_array( $this_attachment_attribute_identifier, array( 'title', 'caption', 'description' ), true ) ) {
			// Map public field identifiers to the exact wp_posts columns used by the closed CAS templates.
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

			// Preserve nonempty existing values unless this field is explicitly overwrite-enabled.
			if ( ! $overwrite_this_attachment_attribute && ! empty( $attachment_post->$this_post_column ) ) {
				if ( $this_attachment_attribute_value === (string) $attachment_post->$this_post_column ) {
					$wordpress_owner_reached_desired_state = true;
				}

				continue;
			}

			// Collect direct post updates together so unslashed bytes and exact rollback ownership stay aligned.
			$post_updates[ $this_post_column ]         = $this_attachment_attribute_value;
			$original_post_values[ $this_post_column ] = (string) $attachment_post->$this_post_column;

			// Mirror imported NextGEN descriptions so provider and WordPress owners remain synchronized.
			if ( $is_imported_nextgen_entry && 'description' === $this_attachment_attribute_identifier ) {
				$nextgen_gallery_updates['description'] = $this_attachment_attribute_value;
			}
		} elseif ( 'alt-text' === $this_attachment_attribute_identifier ) {
			// Capture an exact missing/value owner before any provider or WordPress mutation.
			$postmeta_expected_state = ai4seo_read_attachment_alt_text_postmeta_state( $attachment_post_id );

			if ( false === $postmeta_expected_state ) {
				ai4seo_debug_message( 984321723, 'Could not preflight the exact attachment alt-text metadata owner: ' . $wpdb->last_error, true );
				return false;
			}

			// Preserve the existing non-overwrite rule, including PHP's established empty-value semantics.
			if ( ! $overwrite_this_attachment_attribute && $postmeta_expected_state['exists'] && ! empty( $postmeta_expected_state['value'] ) ) {
				if ( $this_attachment_attribute_value === $postmeta_expected_state['value'] ) {
					$wordpress_owner_reached_desired_state = true;
				}

				continue;
			}

			$postmeta_update_requested = true;
			$postmeta_update_value     = $this_attachment_attribute_value;

			// Mirror imported NextGEN alt text under the same provider/WordPress synchronization contract.
			if ( $is_imported_nextgen_entry ) {
				$nextgen_gallery_updates['alttext'] = $this_attachment_attribute_value;
			}
		}
	}

	$nextgen_gallery_pid             = absint( $attachment_post->post_parent );
	$original_nextgen_gallery_values = array();
	$nextgen_write_owned             = false;
	$post_write_owned                = false;
	$post_cache_repair_required      = false;
	$postmeta_cache_repair_required  = false;

	// A genuine imported entry must have exactly one current provider owner before any mutation begins.
	if ( $nextgen_gallery_updates ) {
		$nextgen_gallery_updates = ai4seo_deep_sanitize( $nextgen_gallery_updates );
		$nextgen_gallery_row     = ai4seo_read_nextgen_attachment_attribute_sync_row( $nextgen_gallery_pid );

		if ( false === $nextgen_gallery_row ) {
			ai4seo_debug_message( 984321689, 'Could not preflight the exact NextGEN picture row: ' . $wpdb->last_error, true );
			return false;
		}

		foreach ( $nextgen_gallery_updates as $field => $value ) {
			if ( ! array_key_exists( $field, $nextgen_gallery_row ) ) {
				ai4seo_debug_message( 984321689, 'Could not preflight all requested NextGEN picture fields.', true );
				return false;
			}

			$original_nextgen_gallery_values[ $field ] = $nextgen_gallery_row[ $field ];
		}
	}

	// Provider first: a raced or failed optional-provider write must never leave WordPress half-updated.
	if ( $nextgen_gallery_updates ) {
		// Mark the call boundary conservatively in case a provider hook throws after mutation.
		$operation_details['commit_state'] = 'possibly_committed';
		$nextgen_write_result              = ai4seo_compare_and_set_nextgen_attachment_attribute_sync_row(
			$nextgen_gallery_pid,
			$nextgen_gallery_updates,
			$original_nextgen_gallery_values
		);

		if ( ! $nextgen_write_result['succeeded'] ) {
			$nextgen_write_error        = $wpdb->last_error;
			$nextgen_rollback_succeeded = true;

			// Roll back only a row this exact CAS changed, and only while its desired values remain current.
			if ( $nextgen_write_result['changed'] ) {
				$nextgen_rollback_result    = ai4seo_compare_and_set_nextgen_attachment_attribute_sync_row(
					$nextgen_gallery_pid,
					$original_nextgen_gallery_values,
					$nextgen_gallery_updates
				);
				$nextgen_rollback_succeeded = $nextgen_rollback_result['succeeded'];

				if ( ! $nextgen_rollback_succeeded ) {
					ai4seo_debug_message( 984321720, 'NextGEN synchronization failed and its provider state could not be restored without overwriting a concurrent writer; retrying the same attachment update is safe.', true );
				}
			}

			$operation_details['commit_state'] = $nextgen_rollback_succeeded ? 'not_committed' : 'possibly_committed';

			ai4seo_debug_message( 984321689, 'Database error while synchronizing NextGEN attachment attributes: ' . $nextgen_write_error, true );
			return false;
		}

		$nextgen_write_owned = $nextgen_write_result['changed'];
	}

	if ( $post_updates ) {
		// A thrown query filter or readback can make a post CAS result unknowable to the caller.
		$operation_details['commit_state'] = 'possibly_committed';
		$post_write_result                 = ai4seo_compare_and_set_attachment_attribute_post_columns(
			$attachment_post_id,
			$post_updates,
			$original_post_values
		);
		$post_cache_repair_required        = $post_write_result['changed']
			|| $post_write_result['conflicted']
			|| (
				$post_write_result['succeeded']
				&& ! $post_write_result['changed']
				&& ! ai4seo_attachment_attribute_row_matches_values( $original_post_values, $post_updates )
			);

		if ( ! $post_write_result['succeeded'] ) {
			$post_write_error        = $wpdb->last_error;
			$post_rollback_succeeded = true;

			if ( $post_write_result['changed'] ) {
				$post_rollback_result       = ai4seo_compare_and_set_attachment_attribute_post_columns(
					$attachment_post_id,
					$original_post_values,
					$post_updates
				);
				$post_cache_repair_required = $post_cache_repair_required || $post_rollback_result['changed'] || $post_rollback_result['conflicted'];
				$post_rollback_succeeded    = $post_rollback_result['succeeded'];
			}

			$nextgen_rollback_succeeded = true;

			if ( $nextgen_write_owned ) {
				$nextgen_rollback_result    = ai4seo_compare_and_set_nextgen_attachment_attribute_sync_row(
					$nextgen_gallery_pid,
					$original_nextgen_gallery_values,
					$nextgen_gallery_updates
				);
				$nextgen_rollback_succeeded = $nextgen_rollback_result['succeeded'];
			}

			$operation_details['commit_state'] = $post_rollback_succeeded && $nextgen_rollback_succeeded
				? 'not_committed'
				: 'possibly_committed';

			if ( $post_cache_repair_required ) {
				clean_post_cache( $attachment_post_id );

				if ( ! ai4seo_force_bump_content_type_list_cache_version() ) {
					ai4seo_debug_message( 984321725, 'Could not persist the attachment content-list cache repair after a failed post write; retrying the attachment update remains safe.', true );
				}
			}

			if ( ! $post_rollback_succeeded || ! $nextgen_rollback_succeeded ) {
				ai4seo_debug_message( 984321721, 'The WordPress attachment write failed and an owned earlier value could not be restored without overwriting a concurrent writer; retrying the same attachment update is safe.', true );
			}

			ai4seo_debug_message( 984321688, 'Database error: ' . $post_write_error, true );
			return false;
		}

		$post_write_owned                      = $post_write_result['changed'];
		$wordpress_owner_reached_desired_state = true;
	}

	// Postmeta is last because an exact conflict can compensate only earlier writes this operation owns.
	if ( $postmeta_update_requested ) {
		// The lock-scoped metadata writer can fail or throw after an owned write and compensation attempt.
		$operation_details['commit_state'] = 'possibly_committed';
		$postmeta_write_result             = ai4seo_write_attachment_alt_text_postmeta_with_lock(
			$attachment_post_id,
			$postmeta_update_value,
			$postmeta_expected_state
		);
		$postmeta_expected_matches_desired = $postmeta_expected_state['exists']
			&& $postmeta_update_value === $postmeta_expected_state['value'];
		$postmeta_cache_repair_required    = $postmeta_write_result['changed']
			|| $postmeta_write_result['conflicted']
			|| (
				$postmeta_write_result['succeeded']
				&& ! $postmeta_write_result['changed']
				&& ! $postmeta_expected_matches_desired
			);

		if ( $postmeta_write_result['succeeded'] ) {
			$wordpress_owner_reached_desired_state = true;
		}
	}

	if ( $postmeta_update_requested && ! $postmeta_write_result['succeeded'] ) {
		// The lock-scoped writer reverses its exact alt owner before this branch compensates earlier owners.
		$postmeta_rollback_succeeded = $postmeta_write_result['rollback_succeeded'];
		$post_rollback_succeeded     = true;

		if ( $post_write_owned ) {
			$post_rollback_result       = ai4seo_compare_and_set_attachment_attribute_post_columns(
				$attachment_post_id,
				$original_post_values,
				$post_updates
			);
			$post_cache_repair_required = true;
			$post_rollback_succeeded    = $post_rollback_result['succeeded'];
		}

		$nextgen_rollback_succeeded = true;

		if ( $nextgen_write_owned ) {
			$nextgen_rollback_result    = ai4seo_compare_and_set_nextgen_attachment_attribute_sync_row(
				$nextgen_gallery_pid,
				$original_nextgen_gallery_values,
				$nextgen_gallery_updates
			);
			$nextgen_rollback_succeeded = $nextgen_rollback_result['succeeded'];
		}

		$operation_details['commit_state'] = $postmeta_rollback_succeeded
			&& $post_rollback_succeeded
			&& $nextgen_rollback_succeeded
			? 'not_committed'
			: 'possibly_committed';

		if ( $postmeta_cache_repair_required ) {
			// Both a retained concurrent value and an owned direct mutation must bypass preflight-era metadata caches.
			ai4seo_invalidate_postmeta_caches( array( $attachment_post_id ) );
		}

		if ( $post_cache_repair_required ) {
			clean_post_cache( $attachment_post_id );
		}

		if ( $post_cache_repair_required || $postmeta_cache_repair_required ) {
			if ( ! ai4seo_force_bump_content_type_list_cache_version() ) {
				ai4seo_debug_message( 984321726, 'Could not persist the attachment content-list cache repair after a failed postmeta write; retrying the attachment update remains safe.', true );
			}
		}

		if ( ! $postmeta_rollback_succeeded || ! $post_rollback_succeeded || ! $nextgen_rollback_succeeded ) {
			ai4seo_debug_message( 984321722, 'The attachment postmeta write failed and one or more operation-owned writes could not be restored; retrying the same attachment update is safe.', true );
		}

		return false;
	}

	if ( $postmeta_cache_repair_required ) {
		ai4seo_invalidate_postmeta_caches( array( $attachment_post_id ) );
	}

	if ( $post_cache_repair_required ) {
		clean_post_cache( $attachment_post_id );
	}

	// Primary owners now expose the requested state, even if the following cache-version write fails.
	$operation_details['commit_state'] = 'committed';

	if ( $wordpress_owner_reached_desired_state && ! ai4seo_force_bump_content_type_list_cache_version() ) {
		ai4seo_debug_message( 984321727, 'The attachment attributes reached their requested WordPress state, but the content-list cache version could not be persisted; retrying the same update will repair it.', true );
		return false;
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
 * @param int  $attachment_post_id    Attachment post ID.
 * @param bool $require_editable_post Whether the current plugin user must be able to edit the related post.
 * @return int Related post ID, or 0 when missing or invalid.
 */
function ai4seo_get_attachment_related_post_id( int $attachment_post_id, bool $require_editable_post = false ): int {
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

		if ( $this_related_post_id > 0
			&& ( ! $require_editable_post || ai4seo_can_edit_post( $this_related_post_id ) ) ) {
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
 * @param int  $attachment_post_id    The attachment post ID.
 * @param bool $require_editable_post Whether the current plugin user must be able to edit the context post.
 * @return string the post-related context for the attachment, including condensed content and surrounding content
 * of the first occurrence of the attachment in the content, separated by " | ".
 * Returns an empty string if no related post or content is found.
 */
function ai4seo_get_attachment_post_related_context( int $attachment_post_id, bool $require_editable_post = false ): string {
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
	$post_id = ai4seo_get_first_attachment_using_post_id( $attachment_post_id, $require_editable_post );

	if ( $post_id <= 0 ) {
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
		'max_serialized_value_depth'       => 8,
		'max_serialized_value_nodes'       => 2048,
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
	$post_meta_key_query = ai4seo_prepare_database_query(
		'SELECT meta_id, meta_key FROM {{postmeta_table}} WHERE post_id = {{post_id}} ORDER BY meta_id ASC',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'post_id'        => ai4seo_database_scalar_binding( '%d', $post_id ),
		)
	);

	if ( false === $post_meta_key_query ) {
		ai4seo_debug_message( 19747201, 'Could not prepare the related-attachment postmeta-key query.', true );
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared this source-post lookup; the scan needs current keys before applying its strict row cap.
	$post_meta_key_rows = $wpdb->get_results( $post_meta_key_query, ARRAY_A );

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
	$post_meta_value_query = ai4seo_prepare_database_query(
		'SELECT meta_id, meta_key, LEFT(meta_value, {{value_byte_limit}}) AS meta_value, LENGTH(meta_value) AS meta_value_bytes FROM {{postmeta_table}} WHERE meta_id IN ({{meta_ids}}) ORDER BY meta_id ASC',
		array(
			'value_byte_limit' => ai4seo_database_scalar_binding( '%d', $max_single_meta_value_bytes ),
			'postmeta_table'   => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'meta_ids'         => ai4seo_database_list_binding( '%d', array_values( $selected_post_meta_ids ) ),
		)
	);

	if ( false === $post_meta_value_query ) {
		ai4seo_debug_message( 19747202, 'Could not prepare the related-attachment postmeta-value query.', true );
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared exact selected meta IDs and the byte cap; current bounded prefixes are consumed only by this scan.
	$post_meta_value_rows = $wpdb->get_results( $post_meta_value_query, ARRAY_A );

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
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Result-shape key, not a meta-query predicate.
			'meta_key'   => (string) ( $this_post_meta_value_row['meta_key'] ?? '' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Result-shape key, not a meta-query predicate.
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
 * Removes object nodes from safely decoded related-media data while enforcing traversal caps.
 *
 * @param mixed $value The decoded node.
 * @param int   $depth Current traversal depth.
 * @param int   $max_depth Maximum allowed traversal depth.
 * @param int   $max_nodes Maximum allowed node count.
 * @param int   $node_count Mutable traversed node count.
 * @param bool  $limit_exceeded Mutable limit state.
 * @param bool  $discard_node Mutable object/resource discard state.
 * @return mixed A scalar or sanitized array node.
 */
function ai4seo_sanitize_related_attachment_serialized_node( $value, int $depth, int $max_depth, int $max_nodes, int &$node_count, bool &$limit_exceeded, bool &$discard_node ) {
	$discard_node = false;

	if ( $limit_exceeded || $depth > $max_depth ) {
		$limit_exceeded = true;
		return null;
	}

	++$node_count;

	if ( $node_count > $max_nodes ) {
		$limit_exceeded = true;
		return null;
	}

	// Never inspect decoded objects; incomplete classes may contain attacker-controlled properties.
	if ( is_object( $value ) || is_resource( $value ) ) {
		$discard_node = true;
		return null;
	}

	if ( ! is_array( $value ) ) {
		return $value;
	}

	$sanitized_value = array();

	foreach ( $value as $this_key => $this_value ) {
		$discard_child_node = false;
		$sanitized_child    = ai4seo_sanitize_related_attachment_serialized_node(
			$this_value,
			$depth + 1,
			$max_depth,
			$max_nodes,
			$node_count,
			$limit_exceeded,
			$discard_child_node
		);

		if ( $limit_exceeded ) {
			return null;
		}

		if ( ! $discard_child_node ) {
			$sanitized_value[ $this_key ] = $sanitized_child;
		}
	}

	return $sanitized_value;
}

// =========================================================================================== \

/**
 * Safely decodes serialized related-media data without instantiating stored objects.
 *
 * This decoder is intentionally scan-only: object nodes are discarded while safe sibling
 * arrays and scalars remain available to the attachment-ID extractor.
 *
 * @param mixed $value Potentially serialized related-media value.
 * @return array Decode state containing `was_serialized`, `is_decoded`, and `value` entries.
 */
function ai4seo_decode_related_attachment_serialized_value( $value ): array {
	$decode_result = array(
		'was_serialized' => false,
		'is_decoded'     => false,
		'value'          => null,
	);

	if ( ! is_string( $value ) ) {
		return $decode_result;
	}

	$serialized_value               = trim( $value );
	$related_attachment_scan_limits = ai4seo_get_related_attachment_scan_limits();
	$max_value_bytes                = absint( $related_attachment_scan_limits['max_single_meta_value_bytes'] ?? 262144 );
	$max_depth                      = absint( $related_attachment_scan_limits['max_serialized_value_depth'] ?? 8 );
	$max_nodes                      = absint( $related_attachment_scan_limits['max_serialized_value_nodes'] ?? 2048 );
	$is_legacy_custom_object        = ai4seo_is_legacy_serialized_custom_object( $serialized_value );
	$is_standard_serialized         = is_serialized( $serialized_value );

	if ( '' === $serialized_value || ( ! $is_legacy_custom_object && ! $is_standard_serialized ) ) {
		return $decode_result;
	}

	$decode_result['was_serialized'] = true;

	if ( $is_legacy_custom_object || strlen( $serialized_value ) > $max_value_bytes ) {
		return $decode_result;
	}

	// Reject enum tokens before unserialize can autoload their declaring class on PHP 8.1+.
	if ( ai4seo_serialized_value_contains_enum_token( $serialized_value ) ) {
		return $decode_result;
	}

	try {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize,WordPress.PHP.NoSilencedErrors.Discouraged -- Class instantiation is disabled, depth is bounded, and malformed third-party metadata must fail closed without a warning.
		$decoded_value = @unserialize(
			$serialized_value,
			array(
				'allowed_classes' => false,
				'max_depth'       => $max_depth,
			)
		);
	} catch ( Throwable $exception ) {
		return $decode_result;
	}

	// Distinguish the valid serialized false scalar from a bounded decoder failure.
	if ( false === $decoded_value && 'b:0;' !== $serialized_value ) {
		return $decode_result;
	}

	$node_count     = 0;
	$limit_exceeded = false;
	$discard_node   = false;
	$decoded_value  = ai4seo_sanitize_related_attachment_serialized_node(
		$decoded_value,
		0,
		$max_depth,
		$max_nodes,
		$node_count,
		$limit_exceeded,
		$discard_node
	);

	if ( $limit_exceeded || $discard_node ) {
		return $decode_result;
	}

	$decode_result['is_decoded'] = true;
	$decode_result['value']      = $decoded_value;

	return $decode_result;
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

	// WordPress and page builders may store arrays alongside object-bearing data in postmeta rows.
	$serialized_decode_result = ai4seo_decode_related_attachment_serialized_value( $value );

	if ( $serialized_decode_result['was_serialized'] ) {
		if ( ! $serialized_decode_result['is_decoded'] ) {
			return array();
		}

		// Scan only the sanitized tree so discarded object or enum bytes cannot become regex matches.
		return ai4seo_extract_related_attachment_post_ids_from_value(
			$serialized_decode_result['value'],
			$context_key,
			$depth + 1,
			$related_attachment_scan_state
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
 * Returns whether deep image usage search is supported for the current database size.
 * Failed count lookups are treated as unsupported because the database size is unknown.
 *
 * @return array
 */
function ai4seo_get_deep_context_search_site_support_status(): array {
	$current_num_posts_table_entries    = ai4seo_get_current_posts_table_entries_count();
	$current_num_postmeta_table_entries = ai4seo_get_current_postmeta_table_entries_count();
	$blocking_reasons                   = array();

	if ( $current_num_posts_table_entries < 0 ) {
		$blocking_reasons[] = 'posts_count_unavailable';
	} elseif ( $current_num_posts_table_entries >= AI4SEO_LARGE_SITE_POSTS_THRESHOLD ) {
		$blocking_reasons[] = 'posts';
	}

	if ( $current_num_postmeta_table_entries < 0 ) {
		$blocking_reasons[] = 'postmeta_count_unavailable';
	} elseif ( $current_num_postmeta_table_entries >= AI4SEO_DEEP_CONTEXT_SEARCH_POSTMETA_THRESHOLD ) {
		$blocking_reasons[] = 'postmeta';
	}

	return array(
		'is_supported'           => empty( $blocking_reasons ),
		'blocking_reasons'       => $blocking_reasons,
		'posts_table_entries'    => $current_num_posts_table_entries,
		'postmeta_table_entries' => $current_num_postmeta_table_entries,
	);
}

// =========================================================================================== \

/**
 * Returns whether deep image usage search can be activated on the current site.
 *
 * @return bool
 */
function ai4seo_is_deep_context_search_supported_for_current_site(): bool {
	$site_support_status = ai4seo_get_deep_context_search_site_support_status();

	return (bool) $site_support_status['is_supported'];
}

// =========================================================================================== \

/**
 * Deactivates deep image usage search and persists the default/off state.
 *
 * @return bool
 */
function ai4seo_disable_deep_context_search_for_images(): bool {
	return ai4seo_update_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES, false );
}

// =========================================================================================== \

/**
 * Disables deep image usage search if it is active on an unsupported site.
 *
 * @return bool True when the setting was disabled.
 */
function ai4seo_maybe_disable_deep_context_search_for_large_site(): bool {
	$raw_settings                   = ai4seo_read_settings();
	$is_deep_context_search_enabled = (bool) ( $raw_settings[ AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ] ?? ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ) );

	if ( ! $is_deep_context_search_enabled ) {
		return false;
	}

	if ( ai4seo_is_deep_context_search_supported_for_current_site() ) {
		return false;
	}

	return ai4seo_disable_deep_context_search_for_images();
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
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Optional deep-context discovery requires a current uncached result and enforces a database-native statement timeout around caller-prepared SELECT SQL.
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

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Database capability detection is immutable for the request and retained in the function-local static cache.
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
 * @param int  $attachment_post_id    The attachment post ID.
 * @param bool $require_editable_post Whether the current plugin user must be able to edit the context post.
 * @return int first matching post id or 0 if not found
 */
function ai4seo_get_first_attachment_using_post_id( int $attachment_post_id, bool $require_editable_post = false ): int {
	$attachment_post_id = absint( $attachment_post_id );

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 605028311, 'Prevented loop', true );
		// If recursion protection blocks fresh discovery, the stored modal result is still safe to read.
		return ai4seo_get_attachment_related_post_id( $attachment_post_id, $require_editable_post );
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

	if ( ai4seo_is_attachment_context_post_eligible( $parent_post_id )
		&& ( ! $require_editable_post || ai4seo_can_edit_post( $parent_post_id ) ) ) {
		return $parent_post_id;
	}

	// look for thumbnail relations next.
	global $wpdb;

	$thumbnail_post_ids_query = ai4seo_prepare_database_query(
		"SELECT post_id
		FROM {{postmeta_table}}
		WHERE meta_key = '_thumbnail_id'
		AND meta_value = {{attachment_post_id}}
		ORDER BY post_id ASC
		LIMIT 25",
		array(
			'postmeta_table'     => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'attachment_post_id' => ai4seo_database_scalar_binding( '%d', $attachment_post_id ),
		)
	);

	$thumbnail_post_ids = array();

	if ( false === $thumbnail_post_ids_query ) {
		ai4seo_debug_message( 630085040, 'Could not prepare the featured-image relationship query.', true );
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the bounded current relationship lookup; external thumbnail mutations have no plugin-owned cache boundary.
		$thumbnail_post_ids = $wpdb->get_col( $thumbnail_post_ids_query );
	}

	if ( ! empty( $thumbnail_post_ids ) && is_array( $thumbnail_post_ids ) ) {
		$thumbnail_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $thumbnail_post_ids, $require_editable_post );

		if ( $thumbnail_post_id > 0 ) {
			return $thumbnail_post_id;
		}
	}

	// From here we only continue if deep context search for images is enabled, otherwise use the modal fallback.
	if ( ! ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ) ) {
		return ai4seo_get_attachment_related_post_id( $attachment_post_id, $require_editable_post );
	}

	if ( ! ai4seo_is_deep_context_search_supported_for_current_site() ) {
		ai4seo_disable_deep_context_search_for_images();
		return ai4seo_get_attachment_related_post_id( $attachment_post_id, $require_editable_post );
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

		// JSON-encoded gallery ID lists.
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
		$content_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $content_post_ids, $require_editable_post );

		if ( $content_post_id > 0 ) {
			return $content_post_id;
		}
	}

	if ( ! ai4seo_get_setting( AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES ) ) {
		// The content search may disable deep search after a timeout, so fall back before scanning postmeta.
		return ai4seo_get_attachment_related_post_id( $attachment_post_id, $require_editable_post );
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
		$postmeta_post_id = ai4seo_get_first_eligible_attachment_context_post_id( $postmeta_post_ids, $require_editable_post );

		if ( $postmeta_post_id > 0 ) {
			return $postmeta_post_id;
		}
	}

	return ai4seo_get_attachment_related_post_id( $attachment_post_id, $require_editable_post );
}


/**
 * Returns the first eligible post ID from a list of candidates.
 *
 * @param array $candidate_post_ids     List of post IDs.
 * @param bool  $require_editable_post Whether the current plugin user must be able to edit the candidate.
 * @return int first eligible post id or 0
 */
function ai4seo_get_first_eligible_attachment_context_post_id( array $candidate_post_ids, bool $require_editable_post = false ): int {
	foreach ( $candidate_post_ids as $candidate_post_id ) {
		$candidate_post_id = absint( $candidate_post_id );

		if ( ai4seo_is_attachment_context_post_eligible( $candidate_post_id )
			&& ( ! $require_editable_post || ai4seo_can_edit_post( $candidate_post_id ) ) ) {
			return $candidate_post_id;
		}
	}

	return 0;
}

// =========================================================================================== \

/**
 * Checks whether a post can be used for attachment context.
 *
 * @param int $post_id the post id.
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
 * @param int $post_id the post id.
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
 * @param int $attachment_post_id the attachment post id.
 * @return string the language of the attachment
 */
function ai4seo_get_attachments_language( int $attachment_post_id ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Preserve the public attachment-language signature.
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 925515687, 'Prevented loop', true );
		return '';
	}

	return sanitize_text_field( ai4seo_get_setting( AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE ) );
}


/**
 * Normalize attachment-attribute identifiers to the canonical string domain.
 *
 * @param array $attachment_attribute_identifiers Raw attachment-attribute identifiers.
 * @return array Canonical string identifiers.
 */
function ai4seo_normalize_attachment_attribute_identifier_list( array $attachment_attribute_identifiers ): array {
	// Retain only exact identifier strings so later strict comparisons cannot coerce scalar lookalikes.
	$normalized_attachment_attribute_identifiers = array();

	foreach ( $attachment_attribute_identifiers as $attachment_attribute_identifier ) {
		if ( ! is_string( $attachment_attribute_identifier ) || '' === $attachment_attribute_identifier ) {
			continue;
		}

		$normalized_attachment_attribute_identifiers[] = $attachment_attribute_identifier;
	}

	return array_values( array_unique( $normalized_attachment_attribute_identifiers ) );
}


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

	return ai4seo_normalize_attachment_attribute_identifier_list( $active_attachment_attributes );
}


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


/**
 * Calculate the generation credit cost for one attachment post.
 *
 * @param array|null $only_this_attachment_attributes Optional attachment attribute identifiers to include.
 * @return int Credit cost per attachment post.
 */
function ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post( $only_this_attachment_attributes = null ): int {
	$attachment_attributes_price_table = ai4seo_get_attachment_attributes_price_table( $only_this_attachment_attributes );

	if ( empty( $attachment_attributes_price_table ) ) {
		return 1;
	}

	// calculate total costs.
	return array_sum( $attachment_attributes_price_table );
}


/**
 * Return credit prices for active attachment attributes.
 *
 * @param array|null $only_this_attachment_attributes Optional attachment attribute identifiers to include.
 * @return array Attachment attribute identifiers mapped to credit costs.
 */
function ai4seo_get_attachment_attributes_price_table( $only_this_attachment_attributes = null ): array {
	// Keep a non-empty caller filter restrictive even when all submitted identifiers normalize away.
	$active_attachment_attributes                = ai4seo_get_active_attachment_attributes();
	$restrict_to_requested_attachment_attributes = is_array( $only_this_attachment_attributes ) && ! empty( $only_this_attachment_attributes );

	if ( is_array( $only_this_attachment_attributes ) ) {
		$only_this_attachment_attributes = ai4seo_normalize_attachment_attribute_identifier_list( $only_this_attachment_attributes );
	}

	if ( empty( $active_attachment_attributes ) ) {
		return array();
	}

	$price_table = array();

	foreach ( $active_attachment_attributes as $this_active_attachment_attribute_identifier ) {
		if ( $restrict_to_requested_attachment_attributes && ! in_array( $this_active_attachment_attribute_identifier, $only_this_attachment_attributes, true ) ) {
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


/**
 * Return display names for active attachment attributes.
 *
 * @param array|null $active_attachment_attributes Optional active attribute identifiers.
 * @return array Active attachment attribute display names.
 */
function ai4seo_get_active_attachment_attributes_names( $active_attachment_attributes = null ): array {
	// Align optional caller input with the canonical setting domain used by the default accessor.
	if ( null === $active_attachment_attributes ) {
		$active_attachment_attributes = ai4seo_get_active_attachment_attributes();
	} elseif ( is_array( $active_attachment_attributes ) ) {
		$active_attachment_attributes = ai4seo_normalize_attachment_attribute_identifier_list( $active_attachment_attributes );
	} else {
		$active_attachment_attributes = array();
	}

	$active_attachment_attributes_names = array();

	foreach ( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details ) {
		if ( in_array( $ai4seo_this_attachment_attribute_identifier, $active_attachment_attributes, true ) && isset( $ai4seo_this_attachment_attribute_details['name'] ) ) {
			$active_attachment_attributes_names[] = $ai4seo_this_attachment_attribute_details['name'];
		}
	}

	return $active_attachment_attributes_names;
}


// endregion
// ___________________________________________________________________________________________.
