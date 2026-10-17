<?php
/**
 * Archive context and generation admission for private taxonomy representatives.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read selected taxonomies, optionally from an authoritative settings snapshot.
 *
 * @param array|null $settings Persisted decoded settings, or null for normal settings.
 * @return array
 */
function ai4seo_get_enabled_bulk_generation_taxonomies( ?array $settings = null ): array {
	$value = null === $settings ? ai4seo_get_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES )
		: ( $settings[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ] ?? array() );
	return ai4seo_validate_setting_value( AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES, $value )
		? array_values( array_unique( $value ) ) : array();
}

/**
 * Resolve WPML's taxonomy identity using the term-taxonomy ID, never the proxy ID.
 *
 * @param array $source Validated term source.
 * @return string
 */
function ai4seo_get_term_language_code_by_multilanguage_plugins( array $source ): string {
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
		return '';
	}
	$language = apply_filters(
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public WPML taxonomy-language contract.
		'wpml_element_language_code',
		null,
		array(
			'element_id'   => $source['term_taxonomy_id'],
			'element_type' => 'tax_' . $source['taxonomy'],
		)
	);
	return is_string( $language ) ? sanitize_key( $language ) : '';
}

/**
 * Read a bounded selection of directly assigned public content without rendering it.
 *
 * @param array $context Checked archive context.
 * @return array|WP_Error Clean examples and language, or a failed read.
 */
function ai4seo_read_taxonomy_generation_content_sample( array $context ) {
	global $wpdb;
	$wpdb->last_error = '';
	$source           = $context['source'];
	$language         = ai4seo_get_term_language_code_by_multilanguage_plugins( $source );
	if ( $wpdb->last_error ) {
		return new WP_Error( 'taxonomy_language_read_failed' );
	}
	if ( '' === $language && ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) {
		return array(
			'examples' => array(),
			'language' => '',
		);
	}
	$types = array_values( array_filter( get_post_types( array( 'public' => true ) ), 'is_post_type_viewable' ) );
	$types = array_values( array_diff( $types, array( 'attachment', AI4SEO_TAXONOMY_PROXY_POST_TYPE ) ) );
	if ( ! $types ) {
		return array(
			'examples' => array(),
			'language' => $language,
		);
	}
	$examples = array();
	// Inspect at most 100 candidates; private/catalog-hidden or other-language rows never become evidence.
	$rows = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT p.ID, p.post_title, p.post_excerpt, p.post_content, p.post_type
		FROM {{posts}} p INNER JOIN {{relationships}} r ON r.object_id = p.ID
		WHERE r.term_taxonomy_id = {{term_taxonomy_id}} AND p.post_type IN ({{types}})
		AND p.post_status = {{status}} AND p.post_password = {{password}}
		ORDER BY p.post_date_gmt DESC, p.ID DESC LIMIT 100',
		array(
			'posts'            => ai4seo_database_identifier_binding( 'table.posts' ),
			'relationships'    => ai4seo_database_identifier_binding( 'table.term_relationships' ),
			'term_taxonomy_id' => ai4seo_database_scalar_binding( '%d', $source['term_taxonomy_id'] ),
			'types'            => ai4seo_database_list_binding( '%s', $types ),
			'status'           => ai4seo_database_scalar_binding( '%s', 'publish' ),
			'password'         => ai4seo_database_scalar_binding( '%s', '' ),
		)
	);
	if ( is_wp_error( $rows ) ) {
		return $rows;
	}
	foreach ( $rows as $row ) {
		$wpdb->last_error = '';
		if ( '' !== $language && ai4seo_get_post_language_code_by_multilanguage_plugins( (int) $row['ID'] ) !== $language ) {
			if ( $wpdb->last_error ) {
				return new WP_Error( 'taxonomy_sample_read_failed' );
			}
			continue;
		}
		if ( 'product' === $row['post_type'] && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( (int) $row['ID'] );
			if ( $wpdb->last_error || ! $product ) {
				return new WP_Error( 'taxonomy_sample_read_failed' );
			}
			if ( ! in_array( $product->get_catalog_visibility(), array( 'visible', 'catalog' ), true ) ) {
				continue;
			}
		}
		$text = '' !== trim( $row['post_excerpt'] ) ? $row['post_excerpt'] : $row['post_content'];
		$body = '';
		$ok   = true;
		ai4seo_condense_raw_post_content( $text, 160, 200, $body, $ok );
		if ( ! $ok || $wpdb->last_error ) {
			return new WP_Error( 'taxonomy_sample_read_failed' );
		}
		$examples[] = array(
			'title' => ai4seo_mb_substr( sanitize_text_field( $row['post_title'] ), 0, 100 ),
			'text'  => ai4seo_mb_substr( sanitize_text_field( $body ), 0, 200 ),
		);
		if ( 10 === count( $examples ) ) {
			break;
		}
	}
	return array(
		'examples' => $examples,
		'language' => $language,
	);
}

/**
 * Build the existing metadata wire contract from current archive truth.
 *
 * @param int $post_id Proxy ID.
 * @return array|WP_Error
 */
function ai4seo_prepare_taxonomy_metadata_generation_content_data( int $post_id ) {
	global $wpdb;
	try {
		$context = ai4seo_get_taxonomy_proxy_editor_context( $post_id );
		if ( is_wp_error( $context ) ) {
			return $context;
		}
		if ( ! $context['available'] ) {
			return new WP_Error( 'taxonomy_generation_unavailable', __( 'This term archive is unavailable for generation.', 'ai-for-seo' ) );
		}
		$sample = ai4seo_read_taxonomy_generation_content_sample( $context );
		if ( is_wp_error( $sample ) ) {
			return $sample;
		}
		$parents = array();
		$parent  = (int) $context['source']['parent'];
		$seen    = array( $context['source']['term_id'] );
		while ( $parent ) {
			if ( in_array( $parent, $seen, true ) || count( $seen ) > 50 ) {
				return new WP_Error( 'taxonomy_hierarchy_invalid' );
			}
			$wpdb->last_error = '';
			$term             = get_term( $parent, $context['source']['taxonomy'] );
			if ( $wpdb->last_error || ! $term || is_wp_error( $term ) ) {
				return new WP_Error( 'taxonomy_hierarchy_unavailable' );
			}
			$seen[]    = $parent;
			$parents[] = sanitize_text_field( $term->name );
			$parent    = (int) $term->parent;
		}
		$description = $context['description'];
		$body        = '';
		$ok          = true;
		ai4seo_condense_raw_post_content( $description, 3750, 4000, $body, $ok );
		if ( ! $ok ) {
			return new WP_Error( 'taxonomy_description_unavailable' );
		}
		$body            = ai4seo_mb_substr( sanitize_text_field( $body ), 0, 4000 );
		$title           = sanitize_text_field( $context['name'] );
		$archive_context = 'Page kind: taxonomy archive. Describe the archive topic, not an individual item. Taxonomy: ' . sanitize_text_field( $context['label'] )
		. '. Archive title: ' . $title . '. Parent topics: ' . implode( ' > ', array_reverse( $parents ) ) . '. ';
		$archive_context = ai4seo_mb_substr( $archive_context, 0, min( 900, 4750 - ai4seo_mb_strlen( $body ) ) );
		$evidence        = $body;
		$sample_text     = '';
		foreach ( $sample['examples'] as $example ) {
			$sample_text .= $example['title'] . ': ' . $example['text'] . ' | ';
			$evidence    .= ' ' . $example['text'];
		}
		$archive_context .= 'Examples of directly assigned content, not a complete inventory. Item prices, stock and images are not archive properties: ' . ai4seo_mb_substr( $sample_text, 0, max( 0, 4750 - ai4seo_mb_strlen( $body . $archive_context ) ) );
		$classification   = ai4seo_classify_metadata_visible_content( $body );
		$language         = sanitize_text_field( ai4seo_get_setting( AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE ) );
		if ( 'auto' === $language && '' !== $sample['language'] ) {
			$language = ai4seo_get_language_long_version( $sample['language'], '' );
		}
		$result                       = array(
			'content_extraction_succeeded' => true,
			'content'                      => '' !== $body ? $body : ai4seo_mb_substr( $title, 0, 150 ),
			'post_context'                 => $archive_context,
			'content_url'                  => $context['archive_url'],
			'language'                     => $language,
			'taxonomy'                     => $context['source']['taxonomy'],
			'language_code'                => $sample['language'],
			'has_sufficient_content'       => ai4seo_mb_strlen( trim( $evidence ) ) >= AI4SEO_TOO_SHORT_CONTENT_LENGTH,
			'content_analysis'             => array(
				'schema_version'       => '1',
				'quality'              => $classification['quality'],
				'quality_reason'       => $classification['quality_reason'],
				'visible_word_count'   => $classification['visible_word_count'],
				'visible_letter_count' => $classification['visible_letter_count'],
				'body_text'            => $body,
				'excerpt_text'         => ai4seo_mb_substr( $body, 0, 512 ),
				'h1'                   => '',
				'post_title'           => ai4seo_mb_substr( $title, 0, 512 ),
				'site_language'        => ai4seo_get_language_long_version( ai4seo_get_wordpress_language_code(), '' ),
			),
		);
		$result['source_fingerprint'] = hash( 'sha256', wp_json_encode( array( $context['source']['term_taxonomy_id'], $result, $parents, $sample ) ) );
		return $result;
	} catch ( Throwable $exception ) {
		ai4seo_debug_message( 731014004, 'Taxonomy generation source could not be read: ' . $post_id, true );
		return new WP_Error( 'taxonomy_source_read_failed' );
	}
}

/**
 * Shared request context while retaining the established post preparation contract.
 *
 * @param int    $post_id Entry ID.
 * @param string $content Submitted post content; archives always use their real sources.
 * @return array|WP_Error
 */
function ai4seo_get_metadata_generation_entry_context( int $post_id, string $content = '' ) {
	if ( ai4seo_is_taxonomy_proxy( $post_id ) ) {
		return ai4seo_prepare_taxonomy_metadata_generation_content_data( $post_id );
	}
	return ai4seo_prepare_metadata_generation_content_data( $post_id, $content ) + array(
		'content_url' => get_permalink( $post_id ),
		'language'    => ai4seo_get_posts_language( $post_id ),
	);
}

/**
 * Check archive eligibility without using fake post authors or creation dates.
 *
 * @param int        $post_id Proxy ID.
 * @param string     $mode manual, inventory, or automatic.
 * @param array|null $settings Authoritative policy settings for automatic admission.
 * @return array|WP_Error
 */
function ai4seo_get_taxonomy_generation_eligibility( int $post_id, string $mode = 'inventory', ?array $settings = null ) {
	if ( 'manual' === $mode && ! ai4seo_can_edit_taxonomy_proxy( $post_id ) ) {
		return new WP_Error( 'taxonomy_generation_forbidden' );
	}
	$entry = ai4seo_prepare_taxonomy_metadata_generation_content_data( $post_id );
	if ( is_wp_error( $entry ) || 'manual' === $mode || 'inventory' === $mode ) {
		return $entry;
	}
	if ( null === $settings ) {
		$snapshot = ai4seo_get_raw_option_snapshot( AI4SEO_SETTINGS_OPTION_NAME );
		$settings = null === $snapshot ? null : ai4seo_decode_settings_snapshot( $snapshot );
	}
	if ( ! is_array( $settings ) || ! ai4seo_validate_setting_value( AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES, $settings[ AI4SEO_SETTING_ENABLED_BULK_GENERATION_TAXONOMIES ] ?? array() ) ) {
		return new WP_Error( 'taxonomy_policy_unavailable' );
	}
	if ( ! in_array( $entry['taxonomy'], ai4seo_get_enabled_bulk_generation_taxonomies( $settings ), true ) ) {
		return new WP_Error( 'taxonomy_generation_disabled' );
	}
	$disabled = $settings[ AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES ] ?? array();
	if ( ! ai4seo_validate_setting_value( AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES, $disabled ) ) {
		return new WP_Error( 'taxonomy_policy_unavailable' );
	}
	if ( in_array( $entry['language_code'], $disabled, true ) || ( $disabled && '' === $entry['language_code'] && ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_WPML ) ) ) {
		return new WP_Error( 'taxonomy_language_excluded' );
	}
	return $entry['has_sufficient_content'] ? $entry : new WP_Error( 'taxonomy_content_short', __( 'Too little content for automatic generation.', 'ai-for-seo' ) );
}

/**
 * Capture checked values before spending credits; the caller owns a generation claim.
 *
 * @param int   $post_id Proxy ID.
 * @param array $entry Prepared source context.
 * @param bool  $manual Whether user permissions apply.
 * @return array|WP_Error
 */
function ai4seo_get_taxonomy_generation_snapshot( int $post_id, array $entry, bool $manual ) {
	$lock          = ai4seo_get_taxonomy_proxy_sync_lock_name();
	$already_owned = ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock );
	if ( ! $already_owned && ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return new WP_Error( 'taxonomy_generation_busy' );
	}
	try {
		$current      = ai4seo_get_taxonomy_generation_eligibility( $post_id, $manual ? 'manual' : 'automatic' );
		$storage      = ai4seo_read_active_metadata_resolved_view( $post_id );
		$state        = array();
		$instructions = ai4seo_read_custom_instructions_postmeta( $post_id, AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY, $state );
		if ( is_wp_error( $current ) || ! hash_equals( $entry['source_fingerprint'], $current['source_fingerprint'] )
		|| 'unreadable' === $storage['classification'] || ( ! $manual && ( ! in_array( $storage['classification'], array( 'single', 'missing' ), true ) || $storage['conflicts'] ) ) || empty( $state['known'] )
		|| ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
			return new WP_Error( 'taxonomy_generation_changed', __( 'The archive or stored values changed. Reopen the entry before generating.', 'ai-for-seo' ) );
		}
		return array(
			'entry'        => $current,
			'manual'       => $manual,
			'values'       => $storage['active_metadata'],
			'fingerprint'  => $storage['fingerprint'],
			'instructions' => $instructions,
		);
	} finally {
		if ( ! $already_owned ) {
			ai4seo_release_database_advisory_lock( $lock );
		}
	}
}

/**
 * Acquire the inventory lock and reject stale paid results before any metadata write.
 *
 * @param int   $post_id Proxy ID.
 * @param array $snapshot Checked pre-request snapshot.
 * @return bool Caller must release the inventory lock on success.
 */
function ai4seo_lock_taxonomy_generation_result( int $post_id, array $snapshot ): bool {
	$lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) || ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return false;
	}
	$current = ai4seo_get_taxonomy_generation_snapshot( $post_id, $snapshot['entry'], $snapshot['manual'] );
	if ( is_wp_error( $current ) || $snapshot['values'] !== $current['values'] || $snapshot['fingerprint'] !== $current['fingerprint'] || $snapshot['instructions'] !== $current['instructions'] ) {
		ai4seo_release_database_advisory_lock( $lock );
		return false;
	}
	return true;
}

/**
 * Reserve a manual archive request without holding the inventory lock during HTTP.
 *
 * @param int   $post_id Proxy ID.
 * @param array $entry Prepared archive.
 * @return array|WP_Error
 */
function ai4seo_begin_taxonomy_manual_generation( int $post_id, array $entry ) {
	$lock = ai4seo_get_taxonomy_proxy_sync_lock_name();
	if ( ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) || ! ai4seo_acquire_database_advisory_lock( $lock ) ) {
		return new WP_Error( 'taxonomy_generation_busy' );
	}
	try {
		$snapshot = ai4seo_get_taxonomy_generation_snapshot( $post_id, $entry, true );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		$claimed     = false;
		$force       = false;
		$token       = '';
		$pending     = false;
		$prior_force = false;
		$ok          = ai4seo_claim_post_id_for_direct_processing( AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $post_id, $claimed, AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME, $force, $token, $pending, $prior_force );
		$registered  = $claimed && '' !== $token && ai4seo_register_bulk_generation_processing_claim( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $post_id, $token );
		if ( ! $ok || ! $registered || ! ai4seo_is_database_advisory_lock_owned_by_current_connection( $lock ) ) {
			if ( $claimed && '' !== $token ) {
				ai4seo_release_bulk_generation_processing_claim( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $post_id, $token, $pending, $prior_force );
			}
			return new WP_Error( 'taxonomy_generation_busy' );
		}
		$snapshot['token']   = $token;
		$snapshot['pending'] = $pending;
		$snapshot['force']   = $prior_force;
		return $snapshot;
	} finally {
		ai4seo_release_database_advisory_lock( $lock );
	}
}

/**
 * Validate a generation write under the inventory lock using a server-owned claim.
 *
 * @param int   $post_id Proxy ID.
 * @param array $context Writer context.
 * @return bool
 */
function ai4seo_validate_taxonomy_generation_write( int $post_id, array $context ): bool {
	if ( ! ai4seo_is_database_advisory_lock_owned_by_current_connection( ai4seo_get_taxonomy_proxy_sync_lock_name() ) ) {
		return false;
	}
	$claim = ai4seo_get_registered_bulk_generation_processing_claim( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA, $post_id );
	$entry = ai4seo_get_taxonomy_proxy_editor_context( $post_id );
	if ( ! $claim || is_wp_error( $entry ) || ! $entry['available'] ) {
		return false;
	}
	if ( isset( $context['taxonomy_generation_source'] ) ) {
		$current = ai4seo_prepare_taxonomy_metadata_generation_content_data( $post_id );
		if ( is_wp_error( $current ) || ! hash_equals( $context['taxonomy_generation_source'], $current['source_fingerprint'] ) ) {
			return false;
		}
	}
	// This guard also runs inside the metadata lock: inspect ownership without reversing lock order.
	$snapshot = ai4seo_get_raw_option_snapshot( ai4seo_get_processing_claim_lease_option_name( AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME ) );
	$leases   = array();
	return null !== $snapshot && $snapshot['exists'] && ai4seo_validate_processing_claim_leases( $snapshot['value'], $leases )
		&& isset( $leases[ $post_id ] ) && hash_equals( $claim['claim_token'], $leases[ $post_id ]['token'] )
		&& ai4seo_is_processing_claim_lease_active( $leases[ $post_id ] );
}

/**
 * Request one replayable status rebuild when taxonomy generation is first introduced.
 *
 * @return void
 */
function ai4seo_maybe_prepare_taxonomy_generation_index(): void {
	if ( ! AI4SEO_CRON_JOBS_ENABLED || ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return;
	}
	if ( '1' !== (string) get_option( AI4SEO_TAXONOMY_GENERATION_INDEX_OPTION, '' ) && ai4seo_schedule_generation_status_summary_rebuild() ) {
		update_option( AI4SEO_TAXONOMY_GENERATION_INDEX_OPTION, '1', false );
	}
}

/**
 * Project trusted shared status IDs onto a taxonomy, with no independent status cache.
 *
 * @param string     $taxonomy Taxonomy name.
 * @param bool       $active_only Restrict dashboard coverage to active inventory entries.
 * @param array|null $option_inspections Optional caller-owned snapshot shared within one render. Reset after writes or before a new render; omitted calls read fresh.
 * @return array|WP_Error
 */
function ai4seo_read_taxonomy_generation_status_summary( string $taxonomy, bool $active_only = false, ?array &$option_inspections = null ) {
	$summary = array();
	$ids     = ai4seo_query_taxonomy_proxy_inventory(
		'SELECT p.ID FROM {{posts}} p WHERE p.post_type = {{type}} AND EXISTS
		(SELECT 1 FROM {{meta}} m WHERE m.post_id = p.ID AND m.meta_key = {{key}} AND m.meta_value = {{taxonomy}})
		AND ({{active_only}} = 0 OR EXISTS (SELECT 1 FROM {{availability_meta}} a WHERE a.post_id = p.ID AND a.meta_key = {{availability_key}} AND a.meta_value = {{active}}))',
		array(
			'posts'             => ai4seo_database_identifier_binding( 'table.posts' ),
			'type'              => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_POST_TYPE ),
			'meta'              => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'key'               => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_NAME_META_KEY ),
			'taxonomy'          => ai4seo_database_scalar_binding( '%s', $taxonomy ),
			'active_only'       => ai4seo_database_scalar_binding( '%d', $active_only ? 1 : 0 ),
			'availability_meta' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			'availability_key'  => ai4seo_database_scalar_binding( '%s', AI4SEO_TAXONOMY_PROXY_AVAILABILITY_META_KEY ),
			'active'            => ai4seo_database_scalar_binding( '%s', 'available' ),
		)
	);
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}
	$ids     = array_map( 'intval', array_column( $ids, 'ID' ) );
	$options = ai4seo_get_bulk_generation_queue_options_by_context( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA );
	if ( $active_only ) {
		$options['complete'] = AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME;
	}
	foreach ( $options as $key => $option ) {
		if ( ! isset( $option_inspections[ $option ] ) ) {
			$option_inspections[ $option ] = ai4seo_inspect_analysis_post_id_option( $option );
		}
		$inspection = $option_inspections[ $option ];
		if ( ! $inspection['complete'] || ! $inspection['read_succeeded'] ) {
			return new WP_Error( 'taxonomy_status_unavailable' );
		}
		$summary[ $key ] = array_values( array_intersect( $ids, $inspection['ids'] ) );
	}
	return $summary;
}
