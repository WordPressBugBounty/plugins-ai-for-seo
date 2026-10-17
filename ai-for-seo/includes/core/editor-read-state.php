<?php
/**
 * Field-level editor reads and admission checks. No source repair is performed here.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describe a field without conflating unreadable storage with empty text.
 *
 * @param string $value Display value.
 * @param bool   $known Whether the display value is authoritative.
 * @param bool   $writable Whether its storage can be safely updated.
 * @return array Field contract.
 */
function ai4seo_editor_field_state( string $value, bool $known = true, bool $writable = true ): array {
	return array(
		'fingerprint' => hash( 'sha256', $value ),
		'value'       => $known ? $value : '',
		'known'       => $known,
		'writable'    => $known && $writable,
		'generatable' => $known && $writable,
	);
}

/**
 * Read the editor through the existing source readers, retaining field quality.
 *
 * @param int    $post_id Entry ID.
 * @param string $context Metadata or attachment_attributes.
 * @return array Values, quality and the existing metadata recovery view.
 */
function ai4seo_read_editor_state( int $post_id, string $context ): array {
	$state = array(
		'fields'         => array(),
		'values'         => array(),
		'read_succeeded' => false,
		'storage'        => array(),
		'file_known'     => true,
	);
	if ( 'metadata' === $context ) {
		$issues           = array();
		$views            = array();
		$values           = ai4seo_read_available_metadata_by_post_ids( array( $post_id ), true, $state['read_succeeded'], $views, true, $issues );
		$view             = $views[ $post_id ] ?? ai4seo_read_active_metadata_resolved_view( $post_id );
		$state['storage'] = $view;
		$unreadable       = 'unreadable' === $view['classification'];
		foreach ( array_keys( AI4SEO_METADATA_DETAILS ) as $field ) {
			$known = $state['read_succeeded'] && ! ai4seo_is_posts_table_analysis_field_unknown( $issues, $post_id, $field );
			$value = $values[ $post_id ][ $field ] ?? '';
			if ( $unreadable ) {
				$known = array_key_exists( $field, $view['display_metadata'] ?? array() );
				$value = $view['display_metadata'][ $field ] ?? '';
			}
			$state['fields'][ $field ] = ai4seo_editor_field_state( $value, $known, ! $unreadable );
		}
	} else {
		$quality                 = array();
		$exists                  = false;
		$snapshot                = ai4seo_read_attachment_attribute_source_snapshot( $post_id, $state['read_succeeded'], $exists, $quality );
		$state['read_succeeded'] = $state['read_succeeded'] && $exists;
		foreach ( array(
			'title'       => 'post_title',
			'caption'     => 'post_excerpt',
			'description' => 'post_content',
		) as $field => $column ) {
			$known                     = $state['read_succeeded'] && empty( $quality[ $column ] );
			$state['fields'][ $field ] = ai4seo_editor_field_state( $known ? $snapshot['post'][ $column ] : '', $known );
		}
		$alt_known                   = $state['read_succeeded'] && ! in_array( $quality['_wp_attachment_image_alt'] ?? '', array( 'unknown', 'conflict' ), true );
		$state['fields']['alt-text'] = ai4seo_editor_field_state( $alt_known ? $snapshot['alt_text']['value'] : '', $alt_known, empty( $quality['_wp_attachment_image_alt'] ) );
		$state['file_known']         = $state['read_succeeded'] && empty( $quality['_wp_attached_file'] ) && empty( $quality['guid'] ) && empty( $quality['post_mime_type'] );
		$state['mime_type']          = empty( $quality['post_mime_type'] ) ? ( $snapshot['post']['post_mime_type'] ?? '' ) : '';
		// Filename hints keep controls reachable; only the paid-request preflight grants image admission.
		$state['image_candidate'] = in_array( $state['mime_type'], ai4seo_get_allowed_attachment_mime_types(), true )
			|| ( ! in_array( $quality['_wp_attached_file'] ?? '', array( 'unknown', 'conflict' ), true )
				&& 1 === preg_match( '/\.(?:jpe?g|png|gif|webp|avif|svg)$/i', $snapshot['attached_file']['value'] ?? '' ) );
		// Field safety is separate from image-source checks performed before manual generation.
		if ( ! $state['read_succeeded'] || in_array( $quality['_wp_attached_file'] ?? '', array( 'unknown', 'conflict' ), true ) ) {
			foreach ( $state['fields'] as &$field_state ) {
				$field_state['generatable'] = false;
			}
			unset( $field_state );
		}
	}
	foreach ( $state['fields'] as $field => $field_state ) {
		$state['values'][ $field ] = $field_state['value'];
	}
	if ( ! ai4seo_is_plugin_update_context_ready( $context ) ) {
		foreach ( $state['fields'] as &$field_state ) {
			$field_state['generatable']    = false;
			$field_state['update_pending'] = true;
		}
		unset( $field_state );
	}
	$instruction_key       = 'metadata' === $context ? AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY : AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY;
	$instruction_state     = array();
	$instructions          = ai4seo_read_custom_instructions_postmeta( $post_id, $instruction_key, $instruction_state );
	$state['instructions'] = ai4seo_editor_field_state( $instructions, $instruction_state['known'], $instruction_state['writable'] );
	if ( 'metadata' === $context && ai4seo_is_taxonomy_proxy( $post_id ) ) {
		$editable = ai4seo_can_edit_taxonomy_proxy( $post_id );
		foreach ( $state['fields'] as &$field_state ) {
			$field_state['generatable']    = $editable && $field_state['generatable'];
			$field_state['taxonomy_proxy'] = true;
			$field_state['writable']       = $editable && $field_state['writable'];
		}
		unset( $field_state );
		$state['instructions']['writable']       = $editable && $state['instructions']['writable'];
		$state['instructions']['taxonomy_proxy'] = true;
	}
	return $state;
}

/**
 * Recheck only submitted fields before an editor write or paid generation.
 * Existing conflict resolutions retain their separate fingerprint-checked writer.
 *
 * @param int    $post_id Entry ID.
 * @param string $context Editor context.
 * @param array  $fields Submitted field identifiers.
 * @param bool   $instructions Whether instructions will be written.
 * @param array  $resolutions Existing metadata conflict resolutions.
 * @param bool   $generation Whether to require generation readiness.
 * @return bool Whether the requested operation is safe.
 */
function ai4seo_validate_editor_read_admission( int $post_id, string $context, array $fields, bool $instructions = false, array $resolutions = array(), bool $generation = false ): bool {
	$state = ai4seo_read_editor_state( $post_id, $context );
	if ( $instructions && ! $state['instructions']['writable'] ) {
		return false;
	}
	foreach ( $fields as $field ) {
		if ( ! $generation && isset( $resolutions[ $field ], $state['storage']['conflicts'][ $field ] ) ) {
			continue;
		}
		if ( empty( $state['fields'][ $field ][ $generation ? 'generatable' : 'writable' ] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Shared explanation for restricted controls in both editor modes.
 *
 * @param array $field_state Field contract.
 * @return string Translated explanation, or empty for an unrestricted field.
 */
function ai4seo_get_editor_field_read_notice( array $field_state ): string {
	// Expected taxonomy restrictions are explained once by the workspace, not as damaged data.
	if ( ! empty( $field_state['taxonomy_proxy'] ) && $field_state['known'] ) {
		return '';
	}
	if ( ! empty( $field_state['update_pending'] ) ) {
		return __( 'You can edit readable values. Generation is unavailable until the required update settings have been verified. See Help for update status.', 'ai-for-seo' );
	}
	if ( ! $field_state['known'] ) {
		return __( 'Stored value unavailable or conflicting. This field is excluded from saving and generation.', 'ai-for-seo' );
	}
	if ( ! $field_state['writable'] ) {
		return __( 'This value is readable. Editing is unavailable to preserve the original stored data.', 'ai-for-seo' );
	}
	if ( ! $field_state['generatable'] ) {
		return __( 'You can edit this value. Generation is unavailable until the media file data has been corrected.', 'ai-for-seo' );
	}
	return '';
}

/**
 * Fixed, escaped input attributes; restrictions exist before JavaScript runs.
 *
 * @param array $state Field contract.
 * @return string HTML attributes.
 */
function ai4seo_get_editor_read_attributes( array $state ): string {
	$attributes = $state['writable'] ? '' : ' readonly aria-readonly="true" data-ai4seo-non-persistent="1"';
	if ( ! $state['generatable'] ) {
		$attributes .= ' data-ai4seo-generation-unavailable="1"';
	}
	if ( ! $state['known'] ) {
		$attributes .= ' placeholder="' . esc_attr__( 'Stored value unavailable', 'ai-for-seo' ) . '"';
	}
	return $attributes;
}

/**
 * Explain restrictions in both view modes without exposing raw storage rows.
 *
 * @param array  $state Editor state.
 * @param string $context Editor context.
 * @param array  $active_fields Visible field identifiers.
 * @return string HTML and the client quality contract.
 */
function ai4seo_get_editor_read_state_tag( array $state, string $context, array $active_fields ): string {
	$details = 'metadata' === $context ? AI4SEO_METADATA_DETAILS : AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS;
	$client  = array(
		'fields'       => array(),
		'instructions' => array_diff_key( $state['instructions'], array( 'value' => true ) ),
	);
	$notices = '';
	foreach ( $state['fields'] as $field => $field_state ) {
		$client['fields'][ $field ] = array_diff_key( $field_state, array( 'value' => true ) );
		$message                    = ai4seo_get_editor_field_read_notice( $field_state );
		if ( $message && in_array( $field, $active_fields, true ) ) {
			$notices .= '<li><strong>' . esc_html( $details[ $field ]['name'] ?? $field ) . ':</strong> ' . esc_html( $message ) . '</li>';
		}
	}
	if ( ! $state['instructions']['writable'] && empty( $state['instructions']['taxonomy_proxy'] ) ) {
		$notices .= '<li><strong>' . esc_html__( 'Custom Instructions', 'ai-for-seo' ) . ':</strong> ' . esc_html__( 'The stored instructions cannot be edited safely. They will be preserved when other fields are saved.', 'ai-for-seo' ) . '</li>';
	}
	$marker = 'metadata' === $context ? 'metadata_editor_read_version' : 'attachment_attributes_editor_read_version';
	$html   = '<input type="hidden" name="' . esc_attr( ai4seo_get_prefixed_input_name( $marker ) ) . '" value="2" />';
	$html  .= '<div class="ai4seo-editor-read-state" data-ai4seo-read-state="' . esc_attr( wp_json_encode( $client ) ) . '">';
	if ( $notices ) {
		$html .= '<div class="notice notice-warning" role="status"><p><strong>' . esc_html__( 'Some stored data needs attention', 'ai-for-seo' ) . '</strong></p><ul>' . $notices . '</ul><p>' . esc_html__( 'An administrator can use Help → Inspect post for diagnosis. After the data has been corrected, close and reopen this entry to check it again. Keep any unsaved changes before reopening.', 'ai-for-seo' ) . '</p></div>';
	}
	return $html . '</div>';
}

/**
 * Classify submitted fields against the editor's original values without changing storage.
 *
 * @param array $state Current authoritative editor state.
 * @param array $updates Requested text fields, including custom_instructions when present.
 * @param array $baseline Original field fingerprints.
 * @param array $recovery Existing explicit metadata resolutions.
 * @return array Per-field admission and current readable values.
 */
function ai4seo_classify_editor_save_fields( array $state, array $updates, array $baseline, array $recovery = array() ): array {
	$result = array();
	foreach ( $updates as $field => $value ) {
		$current = 'custom_instructions' === $field ? $state['instructions'] : ( $state['fields'][ $field ] ?? array() );
		$status  = 'unavailable';
		if ( ! empty( $current['writable'] ) ) {
			$status = isset( $baseline[ $field ] ) && hash_equals( $current['fingerprint'], $baseline[ $field ] ) ? 'ready' : 'conflict';
		}
		if ( isset( $recovery['resolutions'][ $field ], $state['storage']['conflicts'][ $field ] ) ) {
			$safe   = ai4seo_filter_metadata_recovery_updates( array( $field => $value ), $state['storage'], $recovery );
			$status = $safe ? 'ready' : 'conflict';
		}
		$result[ $field ] = array(
			'status'      => $status,
			'known'       => ! empty( $current['known'] ),
			'current'     => ! empty( $current['known'] ) ? $current['value'] : null,
			'fingerprint' => $current['fingerprint'] ?? '',
		);
	}
	return $result;
}

/**
 * Explain a failed reservation without treating damaged ownership as an empty queue.
 * This read is diagnostic only; the shared fence remains the sole admission authority.
 *
 * @param string $context Queue context.
 * @return string Translated diagnostic.
 */
function ai4seo_get_editor_reservation_notice( string $context ): string {
	$options = ai4seo_get_bulk_generation_queue_options_by_context( $context );
	foreach ( array( 'pending', 'processing', 'force_overwrite' ) as $key ) {
		if ( isset( $options[ $key ] ) && ! ai4seo_inspect_analysis_post_id_option( $options[ $key ] )['complete'] ) {
			return __( 'Texts could not be reserved because queue data is unreadable. Your drafts are retained. Use Help → Inspect post for diagnosis; waiting alone may not resolve this.', 'ai-for-seo' );
		}
	}
	$snapshot = ai4seo_get_raw_option_snapshot( ai4seo_get_processing_claim_lease_option_name( $options['processing'] ) );
	$leases   = array();
	if ( null === $snapshot || ( $snapshot['exists'] && ( ! ai4seo_validate_processing_claim_leases( $snapshot['value'], $leases ) || ! ai4seo_validate_processing_claim_lease_context( $options['processing'], $leases ) ) ) ) {
		return __( 'Generation ownership data could not be verified. Texts were not overwritten and your drafts are retained. Use Help → Inspect post for diagnosis.', 'ai-for-seo' );
	}
	return __( 'Texts could not be reserved safely. Generation may still own this entry, or its previous operation needs recovery. Your drafts are retained.', 'ai-for-seo' );
}

/**
 * Save a validated editor request through existing writers and their generation fence.
 * Legacy clients continue using their existing handlers without this additive protocol.
 *
 * @param int    $post_id Authorized entry.
 * @param string $context Editor context.
 * @param array  $updates Validated text updates.
 * @param array  $request Sanitized editor request.
 * @param array  $recovery Existing metadata conflict resolutions.
 * @return array|WP_Error Field results or invalid-request error.
 */
function ai4seo_save_editor_field_changes( int $post_id, string $context, array $updates, array $request, array $recovery = array() ) {
	$taxonomy_proxy = ai4seo_is_taxonomy_proxy( $post_id );
	if ( $taxonomy_proxy && ( 'metadata' !== $context || ! ai4seo_can_edit_taxonomy_proxy( $post_id )
		|| ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) ) {
		return new WP_Error( 'taxonomy_editor_unavailable', __( 'This taxonomy entry could not be reserved safely. Your drafts have been retained.', 'ai-for-seo' ) );
	}
	$prefix   = $context . '_editor_';
	$baseline = $request[ $prefix . 'baseline' ] ?? null;
	if ( ! is_array( $baseline ) ) {
		return new WP_Error( 728451921, __( 'The editor baseline is missing. Keep your drafts and reopen this entry before saving.', 'ai-for-seo' ) );
	}
	if ( array_key_exists( $prefix . 'custom_instructions', $request ) ) {
		if ( ! is_string( $request[ $prefix . 'custom_instructions' ] ) ) {
			return new WP_Error( 728451921, __( 'Custom instructions must be text.', 'ai-for-seo' ) );
		}
		$updates['custom_instructions'] = ai4seo_normalize_custom_instructions_value( $request[ $prefix . 'custom_instructions' ] );
	}
	foreach ( $updates as $field => &$value ) {
		if ( ! is_string( $baseline[ $field ] ?? null ) || 1 !== preg_match( '/\A[0-9a-f]{64}\z/D', $baseline[ $field ] ) ) {
			return new WP_Error( 728451921, __( 'The editor baseline is incomplete. Keep your drafts and reopen this entry before saving.', 'ai-for-seo' ) );
		}
		$value = 'custom_instructions' === $field ? $value : ai4seo_normalize_editor_input_value( (string) $value );
	}
	unset( $value );
	$state         = ai4seo_read_editor_state( $post_id, $context );
	$output_before = $taxonomy_proxy ? ai4seo_read_metadata_output_state( $post_id ) : array();
	$activity      = 'metadata' === $context ? 'metadata-bulk-generated' : 'attachment-attributes-bulk-generated';
	$error_token   = ai4seo_get_recent_activity_error_token( $post_id, $activity );
	$fields        = ai4seo_classify_editor_save_fields( $state, $updates, $baseline, $recovery );
	$attempted     = array();
	$details       = array( 'commit_state' => 'not_committed' );
	$fence         = array();
	$warnings      = array();
	$response      = array();
	$texts         = array_diff_key( $updates, array( 'custom_instructions' => true ) );
	$ready_text    = array_filter(
		$texts,
		static function ( $field ) use ( $fields ): bool {
			return 'ready' === $fields[ $field ]['status'];
		},
		ARRAY_FILTER_USE_KEY
	);
	if ( $ready_text ) {
		$persist = static function () use ( $post_id, $context, $taxonomy_proxy, $texts, $baseline, $recovery, &$fields, &$attempted, &$details ): bool {
			$current = ai4seo_read_editor_state( $post_id, $context );
			$fields  = array_replace( $fields, ai4seo_classify_editor_save_fields( $current, $texts, $baseline, $recovery ) );
			foreach ( $texts as $field => $value ) {
				if ( 'ready' === $fields[ $field ]['status'] ) {
					$attempted[ $field ] = $value;
				}
			}
			if ( ! $attempted ) {
				return false;
			}
			if ( 'metadata' === $context ) {
				$recovery['expected_metadata'] = $current['storage']['active_metadata'];
				if ( $taxonomy_proxy ) {
					$recovery['taxonomy_proxy'] = true;
					return ai4seo_save_active_metadata_to_postmeta( $post_id, $attempted, false, $details, array(), array(), $recovery );
				}
				ai4seo_update_active_metadata( $post_id, $attempted, true, $details, $recovery );
				return ! empty( $details['active_metadata_succeeded'] ) || ! empty( $details['confirmed_fields'] );
			}
			return ai4seo_update_attachment_attributes( $post_id, $attempted, true, $details, $current['values'] );
		};
		ai4seo_save_manual_editor_values_with_generation_fence( $post_id, $context, $persist, $fence, $details );
		if ( empty( $fence['reservation_succeeded'] ) ) {
			$warnings[] = ai4seo_get_editor_reservation_notice( $context );
		}
	}
	// Instructions have their own conditional writer and can succeed independently of text admission.
	if ( isset( $fields['custom_instructions'] ) && 'ready' === $fields['custom_instructions']['status']
		&& ( ! $taxonomy_proxy || ( ai4seo_can_edit_taxonomy_proxy( $post_id )
			&& ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) ) ) {
		$key = ai4seo_get_custom_instructions_postmeta_key_by_bulk_generation_queue_context( $context );
		if ( $taxonomy_proxy ) {
			$saved = ai4seo_save_manual_editor_values_with_generation_fence(
				$post_id,
				$context,
				static function () use ( $post_id, $key, $updates, $state ): bool {
					return ai4seo_save_custom_instructions_postmeta( $post_id, $key, $updates['custom_instructions'], $state['instructions']['value'] );
				}
			);
		} else {
			$saved = ai4seo_save_custom_instructions_postmeta( $post_id, $key, $updates['custom_instructions'], $state['instructions']['value'] );
		}
		$fields['custom_instructions']['status'] = $saved ? 'saved' : 'unconfirmed';
	}
	$after = ai4seo_read_editor_state( $post_id, $context );
	if ( $taxonomy_proxy ) {
		if ( $attempted ) {
			ai4seo_refresh_taxonomy_metadata_output_cache( $post_id, $output_before, $details );
			$warnings = array_merge( $warnings, $details['warnings'] ?? array() );
		}
		$confirmed                   = ai4seo_get_confirmed_active_metadata_updates( $attempted, $after['storage'] );
		$response['metadata_editor'] = array(
			'post_id'                 => $post_id,
			'metadata'                => $confirmed,
			'saved_fields'            => array_keys( $confirmed ),
			'storage'                 => ai4seo_metadata_recovery_editor_state( $after['storage'] ),
			'conflicts'               => $after['storage']['conflicts'],
			'unresolved_fields'       => array_keys( $after['storage']['conflicts'] ),
			'status'                  => count( $confirmed ) === count( $attempted ) ? 'complete' : 'partial',
			'submitted_changes_saved' => count( $confirmed ) === count( $attempted ),
			'generatable'             => ai4seo_can_edit_taxonomy_proxy( $post_id ) && ai4seo_is_plugin_update_context_ready( 'metadata' ),
		);
	} elseif ( 'metadata' === $context && $attempted ) {
		$response = ai4seo_build_metadata_editor_save_response( $post_id, $attempted, $details, $fence );
		$warnings = array_merge( $warnings, $response['metadata_editor']['warnings'] );
	}
	foreach ( $fields as $field => &$result ) {
		$current = 'custom_instructions' === $field ? $after['instructions'] : $after['fields'][ $field ];
		if ( 'ready' === $result['status'] ) {
			$confirmed        = 'metadata' === $context
				? isset( $response['metadata_editor']['metadata'][ $field ] ) && $updates[ $field ] === $response['metadata_editor']['metadata'][ $field ]
				: ! empty( $fence['persistence_succeeded'] ) && $current['known'] && $updates[ $field ] === $current['value'];
			$result['status'] = $confirmed ? 'saved' : ( ! isset( $attempted[ $field ] ) || 'not_committed' === ( $details['commit_state'] ?? '' ) ? 'not_saved' : 'unconfirmed' );
		}
		if ( 'saved' === $result['status'] && ( ! $current['known'] || $current['value'] !== $updates[ $field ] ) ) {
			$result['status'] = 'unconfirmed';
		}
		$result['known']       = $current['known'];
		$result['current']     = $current['known'] ? $current['value'] : null;
		$result['fingerprint'] = $current['fingerprint'];
	}
	unset( $result );
	$statistics_pending = ! empty( $fence['persistence_succeeded'] ) && empty( $fence['coverage_succeeded'] );
	$ownership_pending  = ! empty( $fence['reservation_succeeded'] )
		&& empty( $fence['release_succeeded'] ) && empty( $fence['rollback_succeeded'] );
	if ( $statistics_pending ) {
		$warnings[] = __( 'Statistics notice: completeness statistics may still be incomplete or outdated. This does not affect confirmed saved texts.', 'ai-for-seo' );
	}
	if ( $ownership_pending ) {
		$warnings[] = __( 'Generation ownership could not be released safely. Keep any remaining drafts and reopen this entry before continuing.', 'ai-for-seo' );
	}
	$response['editor_save'] = array(
		'post_id'         => $post_id,
		'context'         => $context,
		'fields'          => $fields,
		'warnings'        => array_values( array_unique( $warnings ) ),
		'reload_required' => $ownership_pending || ! empty( $response['metadata_editor']['reload_required'] ),
	);
	$pending                 = array_filter(
		$fields,
		static function ( array $field ): bool {
			return 'saved' !== $field['status'];
		}
	);
	if ( $texts && ! $pending && ! $warnings && ! empty( $fence['coverage_succeeded'] ) && ! empty( $fence['release_succeeded'] )
		&& '' !== $error_token && ai4seo_resolve_recent_activity_error( $post_id, $activity, $error_token ) ) {
		$response['resolved_activity_tokens'][ $activity ] = $error_token;
	}
	return $response;
}
