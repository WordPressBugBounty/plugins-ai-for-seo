<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region THIRD PARTY SEO PLUGINS ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Collects all the currently supported and active third party SEO plugins
 *
 * @return array The supported and currently active third party SEO plugins
 */
function ai4seo_get_active_third_party_seo_plugin_details(): array {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 500078657, 'Prevented loop', true );
		return array();
	}

	$active_supported_third_party_seo_plugin_details = array();

	$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

	foreach ( $third_party_seo_plugin_details as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( ai4seo_is_plugin_or_theme_active( $this_third_party_seo_plugin_identifier ) ) {
			$active_supported_third_party_seo_plugin_details[ $this_third_party_seo_plugin_identifier ] = $this_third_party_seo_plugin_details;
		}
	}

	return $active_supported_third_party_seo_plugin_details;
}

// =========================================================================================== \\

/**
 * Returns active third-party integrations whose JavaScript editors require DOM discovery.
 *
 * @return array Active integration identifiers in stable initialization order.
 */
function ai4seo_get_active_generation_editor_integration_identifiers(): array {
	// Plugin activation state is request-stable, so reuse the filtered list across localization calls.
	static $active_generation_editor_integration_identifiers = null;

	if ( is_array( $active_generation_editor_integration_identifiers ) ) {
		return $active_generation_editor_integration_identifiers;
	}

	// Keep this order aligned with the client definition map for deterministic adapter initialization.
	$generation_editor_integration_identifiers = array(
		AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO,
		AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO,
		AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS,
		AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO,
		AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO,
	);

	// Filter server-side so inactive plugins never reach JavaScript discovery or observer setup.
	$active_generation_editor_integration_identifiers = array();

	foreach ( $generation_editor_integration_identifiers as $generation_editor_integration_identifier ) {
		if ( ai4seo_is_plugin_or_theme_active( $generation_editor_integration_identifier ) ) {
			$active_generation_editor_integration_identifiers[] = $generation_editor_integration_identifier;
		}
	}

	return $active_generation_editor_integration_identifiers;
}

// =========================================================================================== \\

/**
 * Returns the configured generation-field postmeta mapping for one integration.
 *
 * The third-party registry is immutable during a request, so keeping one normalized copy per
 * plugin avoids repeating the same nested lookup in resolvers and editor localization.
 *
 * @param string $plugin_identifier The third-party SEO plugin identifier.
 * @return array The generation-field postmeta keys indexed by SOOZ metadata identifier.
 */
function ai4seo_get_third_party_seo_plugin_generation_postmeta_keys( string $plugin_identifier ): array {
	// Cache normalized mappings because frontend resolution can request several fields per post.
	static $generation_postmeta_keys_by_plugin = array();

	$plugin_identifier = sanitize_key( $plugin_identifier );

	// Reject empty identifiers before using them as registry or cache keys.
	if ( '' === $plugin_identifier ) {
		return array();
	}

	// Reuse empty mappings as well as populated ones so malformed entries are normalized only once.
	if ( isset( $generation_postmeta_keys_by_plugin[ $plugin_identifier ] ) ) {
		return $generation_postmeta_keys_by_plugin[ $plugin_identifier ];
	}

	// Normalize malformed or missing registry entries once so callers always receive a safe array.
	$third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();
	$generation_postmeta_keys       = $third_party_seo_plugin_details[ $plugin_identifier ]['generation-field-postmeta-keys'] ?? array();

	$generation_postmeta_keys_by_plugin[ $plugin_identifier ] = is_array( $generation_postmeta_keys )
		? $generation_postmeta_keys
		: array();

	return $generation_postmeta_keys_by_plugin[ $plugin_identifier ];
}

// =========================================================================================== \\

/**
 * Returns the SOOZ metadata identifiers supported by one third-party generation mapping.
 *
 * @param string $plugin_identifier The third-party SEO plugin identifier.
 * @return array The supported SOOZ metadata identifiers.
 */
function ai4seo_get_third_party_seo_plugin_generation_metadata_identifiers( string $plugin_identifier ): array {
	// Cache the derived keys separately because every resolver performs the same membership check.
	static $metadata_identifiers_by_plugin = array();

	$plugin_identifier = sanitize_key( $plugin_identifier );

	// Reject empty identifiers before using them as cache keys.
	if ( '' === $plugin_identifier ) {
		return array();
	}

	if ( ! isset( $metadata_identifiers_by_plugin[ $plugin_identifier ] ) ) {
		// Derive identifiers from the shared mapping so resolver field support cannot drift from synchronization.
		$metadata_identifiers_by_plugin[ $plugin_identifier ] = array_keys(
			ai4seo_get_third_party_seo_plugin_generation_postmeta_keys( $plugin_identifier )
		);
	}

	return $metadata_identifiers_by_plugin[ $plugin_identifier ];
}

// =========================================================================================== \\

/**
 * Reads the SEOPress fields needed by its JavaScript-rendered generation editor.
 *
 * The caller decides whether the plugin and external generation controls are active; this helper
 * owns only integration-specific storage normalization and keeps localization type-safe.
 *
 * @param int $post_id The post whose SEOPress generation fields should be read.
 * @return array The normalized metadata values indexed by SOOZ metadata identifier.
 */
function ai4seo_get_seopress_generation_metadata_for_editor( int $post_id ): array {
	// Localization never needs an invalid or unresolved post context.
	if ( $post_id <= 0 ) {
		return array();
	}

	// Read every field from the shared integration mapping so editor proxies and sync stay aligned.
	$seopress_generation_metadata      = array();
	$seopress_generation_postmeta_keys = ai4seo_get_third_party_seo_plugin_generation_postmeta_keys(
		AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS
	);

	foreach ( $seopress_generation_postmeta_keys as $metadata_identifier => $postmeta_key ) {
		$seopress_postmeta_value = get_post_meta( $post_id, $postmeta_key, true );

		// Treat malformed structured values as empty instead of emitting string-conversion warnings.
		if ( ! is_scalar( $seopress_postmeta_value ) ) {
			$seopress_postmeta_value = '';
		}

		$seopress_generation_metadata[ $metadata_identifier ] = html_entity_decode(
			(string) $seopress_postmeta_value,
			ENT_QUOTES | ENT_XML1,
			'UTF-8'
		);
	}

	return $seopress_generation_metadata;
}

// =========================================================================================== \\

/**
 * Reports whether one registry entry participates in generic inbound postmeta synchronization.
 *
 * Keeping the capability interpretation here ensures hooks, outbound-loop suppression, and
 * settings copy continue to opt integrations in under the same rule.
 *
 * @param array $third_party_seo_plugin_details One third-party SEO plugin registry entry.
 * @return bool Whether generic inbound postmeta synchronization is enabled.
 */
function ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync( array $third_party_seo_plugin_details ): bool {
	// Registry entries opt in explicitly; compound stores provide their postmeta and nested-field mapping here.
	return ! empty( $third_party_seo_plugin_details['inbound-postmeta-sync'] );
}

// =========================================================================================== \\

/**
 * Reports whether one integration supports any persisted third-party-to-SOOZ synchronization path.
 *
 * @param array $third_party_seo_plugin_details One third-party SEO plugin registry entry.
 * @return bool Whether direct, compound, or integration-specific inbound synchronization is enabled.
 */
function ai4seo_does_third_party_seo_plugin_support_inbound_sync( array $third_party_seo_plugin_details ): bool {
	// Custom-table integrations opt in separately because postmeta hooks cannot observe their writes.
	return ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync( $third_party_seo_plugin_details )
		|| ! empty( $third_party_seo_plugin_details['inbound-custom-sync'] );
}

// =========================================================================================== \\

/**
 * Normalizes one compound postmeta synchronization declaration from the integration registry.
 *
 * Both inbound hooks and outbound shared-array writers use this representation so a plugin's
 * postmeta key and nested field mapping cannot drift between synchronization directions.
 *
 * @param array $third_party_seo_plugin_details One third-party SEO plugin registry entry.
 * @return array{postmeta_key: string, generation_field_array_keys: array}|array{}
 */
function ai4seo_get_third_party_seo_plugin_compound_postmeta_sync_details( array $third_party_seo_plugin_details ): array {
	// Only structured declarations describe a shared postmeta value with nested generation fields.
	$inbound_postmeta_sync = $third_party_seo_plugin_details['inbound-postmeta-sync'] ?? null;

	if ( ! is_array( $inbound_postmeta_sync ) ) {
		return array();
	}

	// Normalize registry identifiers before exposing them to postmeta or active-metadata lookups.
	$postmeta_key                = sanitize_key( $inbound_postmeta_sync['postmeta-key'] ?? '' );
	$generation_field_array_keys = $inbound_postmeta_sync['generation-field-array-keys'] ?? array();

	if ( ! $postmeta_key || ! is_array( $generation_field_array_keys ) ) {
		return array();
	}

	$normalized_generation_field_array_keys = array();

	// Preserve the registry's metadata-to-nested-key direction for outbound shared-array writes.
	foreach ( $generation_field_array_keys as $metadata_identifier => $array_key ) {
		$metadata_identifier = sanitize_key( $metadata_identifier );
		$array_key           = sanitize_key( $array_key );

		if ( $metadata_identifier && $array_key ) {
			$normalized_generation_field_array_keys[ $metadata_identifier ] = $array_key;
		}
	}

	if ( ! $normalized_generation_field_array_keys ) {
		return array();
	}

	return array(
		'postmeta_key'                => $postmeta_key,
		'generation_field_array_keys' => $normalized_generation_field_array_keys,
	);
}

// =========================================================================================== \\

/**
 * Indexes generic inbound synchronization details by third-party postmeta key.
 *
 * The global postmeta hooks use this request-local lookup to reject unrelated mutations before
 * post, setting, or metadata reads while retaining the registry as the integration source of truth.
 *
 * @return array Synchronization details indexed by postmeta key.
 */
function ai4seo_get_third_party_seo_plugin_inbound_sync_details_by_postmeta_key(): array {
	// Build the immutable registry-derived index once because every postmeta mutation enters the hooks.
	static $sync_details_by_postmeta_key = null;

	if ( null !== $sync_details_by_postmeta_key ) {
		return $sync_details_by_postmeta_key;
	}

	$sync_details_by_postmeta_key = array();
	$third_party_seo_plugins      = ai4seo_get_third_party_seo_plugin_details();

	// Only integrations opting into generic postmeta synchronization contribute hook keys.
	foreach ( $third_party_seo_plugins as $plugin_identifier => $plugin_details ) {
		if ( ! ai4seo_does_third_party_seo_plugin_support_inbound_postmeta_sync( $plugin_details ) ) {
			continue;
		}

		// Compound integrations identify one postmeta key and the nested key for each SOOZ field.
		if ( is_array( $plugin_details['inbound-postmeta-sync'] ) ) {
			$compound_sync_details = ai4seo_get_third_party_seo_plugin_compound_postmeta_sync_details( $plugin_details );

			if ( ! $compound_sync_details ) {
				continue;
			}

			// Inbound array reads need the inverse mapping because changed values arrive by nested key.
			$metadata_identifier_by_array_key = array_flip( $compound_sync_details['generation_field_array_keys'] );
			$postmeta_key                     = $compound_sync_details['postmeta_key'];
			$sync_details_by_postmeta_key[ $postmeta_key ] = array(
				'plugin_identifier'               => sanitize_key( $plugin_identifier ),
				'metadata_identifier_by_array_key' => $metadata_identifier_by_array_key,
			);

			continue;
		}

		// Direct integrations need one registry postmeta key for each independently stored SOOZ field.
		if ( empty( $plugin_details['generation-field-postmeta-keys'] )
			|| ! is_array( $plugin_details['generation-field-postmeta-keys'] ) ) {
			continue;
		}

		// Pair each valid postmeta key with the integration and SOOZ field required by later allowlist checks.
		foreach ( $plugin_details['generation-field-postmeta-keys'] as $metadata_identifier => $postmeta_key ) {
			$metadata_identifier = sanitize_key( $metadata_identifier );
			$postmeta_key         = sanitize_key( $postmeta_key );

			// Ignore malformed registry entries so they cannot create an empty global-hook lookup key.
			if ( ! $metadata_identifier || ! $postmeta_key ) {
				continue;
			}

			$sync_details_by_postmeta_key[ $postmeta_key ] = array(
				'plugin_identifier'   => sanitize_key( $plugin_identifier ),
				'metadata_identifier' => $metadata_identifier,
			);
		}
	}

	return $sync_details_by_postmeta_key;
}

// =========================================================================================== \\

/**
 * Returns metadata identifiers currently configured to sync with one active third-party SEO plugin.
 *
 * @param string $plugin_identifier The third-party SEO plugin identifier.
 * @return array The supported metadata identifiers that are enabled for synchronization.
 */
function ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers( string $plugin_identifier ): array {
	// Normalize caller input before using it as a plugin-registry key or setting value.
	$plugin_identifier = sanitize_key( $plugin_identifier );

	if ( ! $plugin_identifier ) {
		return array();
	}

	// Live editor synchronization applies only to plugins active in the current request.
	$active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! isset( $active_third_party_seo_plugin_details[ $plugin_identifier ]['generation-field-postmeta-keys'] )
		|| ! is_array( $active_third_party_seo_plugin_details[ $plugin_identifier ]['generation-field-postmeta-keys'] ) ) {
		return array();
	}

	// Respect the plugin-level synchronization setting before considering individual metadata fields.
	$sync_third_party_seo_plugin_identifiers = ai4seo_get_setting( AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS );

	if ( ! is_array( $sync_third_party_seo_plugin_identifiers )
		|| ! in_array( $plugin_identifier, $sync_third_party_seo_plugin_identifiers, true ) ) {
		return array();
	}

	// Field-level synchronization is the final allowlist shared by modal reads and save responses.
	$sync_metadata_identifiers = ai4seo_get_setting( AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA );

	if ( ! is_array( $sync_metadata_identifiers ) ) {
		return array();
	}

	$supported_metadata_identifiers = array_keys( $active_third_party_seo_plugin_details[ $plugin_identifier ]['generation-field-postmeta-keys'] );

	// Preserve setting order while removing fields unsupported by the requested plugin.
	return array_values( array_intersect( $sync_metadata_identifiers, $supported_metadata_identifiers ) );
}

// =========================================================================================== \\

/**
 * Returns stable display names for third-party SEO plugin identifiers.
 *
 * @param array $plugin_identifiers Plugin identifiers.
 * @return array
 */
function ai4seo_get_third_party_seo_plugin_names( array $plugin_identifiers ): array {
	$third_party_seo_plugins = ai4seo_get_third_party_seo_plugin_details();
	$plugin_names            = array();

	// Prefer registry labels while retaining an identifier fallback for future or removed integrations.
	foreach ( array_unique( array_map( 'sanitize_key', $plugin_identifiers ) ) as $plugin_identifier ) {
		$plugin_name = $third_party_seo_plugins[ $plugin_identifier ]['name'] ?? $plugin_identifier;
		$plugin_name = sanitize_text_field( (string) $plugin_name );

		if ( '' !== $plugin_name ) {
			$plugin_names[] = $plugin_name;
		}
	}

	// Stable sorting keeps manual errors and cron activity details deterministic across registry order changes.
	$plugin_names = array_values( array_unique( $plugin_names ) );
	sort( $plugin_names, SORT_NATURAL | SORT_FLAG_CASE );

	return $plugin_names;
}

// =========================================================================================== \\

/**
 * Returns the primary focus keyphrase represented by one third-party SEO plugin value.
 *
 * Rank Math stores secondary focus keywords after the primary value in one comma-separated
 * postmeta string, while SOOZ and the other supported integrations expose one primary phrase.
 *
 * @param string $plugin_identifier The third-party SEO plugin identifier.
 * @param string $focus_keyphrase The sanitized integration value.
 * @return string The primary focus keyphrase.
 */
function ai4seo_get_primary_third_party_seo_plugin_focus_keyphrase( string $plugin_identifier, string $focus_keyphrase ): string {
	$focus_keyphrase = trim( $focus_keyphrase );

	// Match Rank Math's own primary-keyword behavior without treating commas from other integrations as separators.
	if ( AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH === $plugin_identifier ) {
		$rank_math_focus_keyphrases = explode( ',', $focus_keyphrase, 2 );
		$focus_keyphrase            = trim( $rank_math_focus_keyphrases[0] ?? '' );
	}

	return $focus_keyphrase;
}

// =========================================================================================== \\

/**
 * Manages request-local state for persisted third-party-to-SOOZ metadata synchronization.
 *
 * Supported operations are read, begin-outbound, end-outbound, mark-deleting,
 * mark-changed, mark-squirrly-synced, and consume-changed.
 *
 * @param string $operation The state operation to perform.
 * @param int    $post_id The optional post id associated with the operation.
 * @return array The complete request-local synchronization state.
 */
function ai4seo_manage_third_party_seo_metadata_sync_request_state( string $operation, int $post_id = 0 ): array {
	// Keep nested outbound writes and per-post queues isolated to the current WordPress request.
	static $state = array(
		'outbound_sync_depth_by_post_id' => array(),
		'deleting_post_ids'              => array(),
		'changed_post_ids'               => array(),
		'squirrly_synced_post_ids'        => array(),
	);

	// Normalize internal callers so state keys always use the same post-id representation.
	$operation = sanitize_key( $operation );
	$post_id   = absint( $post_id );

	// Centralize all state transitions so outbound guards and shutdown finalization stay paired.
	switch ( $operation ) {
		case 'begin-outbound':
			// Scope nested suppression to the originating post so related-post integrations remain independent.
			if ( $post_id > 0 ) {
				$current_depth                                        = $state['outbound_sync_depth_by_post_id'][ $post_id ] ?? 0;
				$state['outbound_sync_depth_by_post_id'][ $post_id ] = $current_depth + 1;
			}
			break;
		case 'end-outbound':
			// Remove completed counters so the state reflects only posts with active outbound writes.
			if ( $post_id > 0 && isset( $state['outbound_sync_depth_by_post_id'][ $post_id ] ) ) {
				$current_depth = max( 0, $state['outbound_sync_depth_by_post_id'][ $post_id ] - 1 );

				if ( $current_depth > 0 ) {
					$state['outbound_sync_depth_by_post_id'][ $post_id ] = $current_depth;
				} else {
					unset( $state['outbound_sync_depth_by_post_id'][ $post_id ] );
				}
			}
			break;
		case 'mark-deleting':
			// A permanently deleted post must not be refreshed or recreated by later meta-delete hooks.
			if ( $post_id > 0 ) {
				$state['deleting_post_ids'][ $post_id ] = true;
				unset( $state['changed_post_ids'][ $post_id ] );
			}
			break;
		case 'mark-changed':
			// Keying by post id collapses several third-party field writes into one shutdown refresh.
			if ( $post_id > 0 ) {
				$state['changed_post_ids'][ $post_id ] = true;
			}
			break;
		case 'mark-squirrly-synced':
			// Prevent Squirrly's later save_post fallback from repeating a completed editor-save synchronization.
			if ( $post_id > 0 ) {
				$state['squirrly_synced_post_ids'][ $post_id ] = true;
			}
			break;
		case 'consume-changed':
			// Clear the queue before processing so repeated finalizer calls remain idempotent.
			$consumed_state            = $state;
			$state['changed_post_ids'] = array();
			return $consumed_state;
		case 'read':
			// Callers receive the current snapshot without mutating request state.
			break;
	}

	return $state;
}

// =========================================================================================== \\

/**
 * Marks a post as being permanently deleted so removed third-party fields are not recreated in SOOZ.
 *
 * @param int $post_id The post id being deleted.
 * @return void
 */
function ai4seo_track_deleting_post_for_third_party_seo_metadata_sync( int $post_id ): void {
	// Mark before WordPress deletes postmeta, because those delete hooks run while the post is being removed.
	ai4seo_manage_third_party_seo_metadata_sync_request_state( 'mark-deleting', $post_id );
}

// =========================================================================================== \\

/**
 * Mirrors persisted third-party metadata values into SOOZ active metadata.
 *
 * @param int    $post_id The post whose metadata changed.
 * @param string $plugin_identifier The originating third-party SEO plugin.
 * @param array  $raw_metadata_values Raw values indexed by SOOZ metadata identifier.
 * @param bool   $values_were_deleted Whether every supplied field represents an explicit deletion.
 * @return bool Whether the requested SOOZ state was reached or no eligible change was required.
 */
function ai4seo_sync_third_party_metadata_values_to_active_metadata(
	int $post_id,
	string $plugin_identifier,
	array $raw_metadata_values,
	bool $values_were_deleted = false
): bool {
	// Normalize hook and integration inputs before any post, setting, or active-metadata work.
	$post_id           = absint( $post_id );
	$plugin_identifier = sanitize_key( $plugin_identifier );

	if ( $post_id <= 0 || ! $plugin_identifier || wp_is_post_revision( $post_id ) ) {
		return false;
	}

	// Ignore writes originating from SOOZ and metadata changes associated with permanent post deletion.
	$request_state = ai4seo_manage_third_party_seo_metadata_sync_request_state( 'read' );

	if ( ! empty( $request_state['outbound_sync_depth_by_post_id'][ $post_id ] )
		|| isset( $request_state['deleting_post_ids'][ $post_id ] ) ) {
		return true;
	}

	// Active metadata is supported only for the post types already exposed by SOOZ.
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, ai4seo_get_supported_post_types(), true ) ) {
		return false;
	}

	// Both synchronization directions share the selected plugin and field allowlists.
	$sync_metadata_identifiers = ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers( $plugin_identifier );
	$metadata_values           = array();

	foreach ( $raw_metadata_values as $metadata_identifier => $raw_metadata_value ) {
		$metadata_identifier = sanitize_key( $metadata_identifier );

		if ( ! in_array( $metadata_identifier, $sync_metadata_identifiers, true ) ) {
			continue;
		}

		// Persist explicit deletions and null defaults while rejecting unexpected structured values.
		if ( $values_were_deleted || null === $raw_metadata_value ) {
			$metadata_value = '';
		} elseif ( ! is_scalar( $raw_metadata_value ) ) {
			continue;
		} else {
			$metadata_value = ai4seo_sanitize_editor_field_value( $raw_metadata_value );
		}

		if ( 'focus-keyphrase' === $metadata_identifier ) {
			$metadata_value = ai4seo_get_primary_third_party_seo_plugin_focus_keyphrase(
				$plugin_identifier,
				$metadata_value
			);
		}

		$metadata_values[ $metadata_identifier ] = $metadata_value;
	}

	if ( ! $metadata_values ) {
		return true;
	}

	// Reuse active-metadata normalization so inbound values follow the existing schema and length limits.
	$prepared_metadata = ai4seo_prepare_active_metadata_values( $metadata_values, false );

	if ( ! $prepared_metadata ) {
		return false;
	}

	// Avoid redundant JSON writes and their downstream cache or coverage work.
	$current_active_metadata = ai4seo_read_active_metadata_from_post_meta( $post_id, false );
	$changed_metadata        = array();

	foreach ( $prepared_metadata as $metadata_identifier => $metadata_value ) {
		if ( ! array_key_exists( $metadata_identifier, $current_active_metadata )
			|| $metadata_value !== $current_active_metadata[ $metadata_identifier ] ) {
			$changed_metadata[ $metadata_identifier ] = $metadata_value;
		}
	}

	if ( ! $changed_metadata ) {
		return true;
	}

	// Merge only changed fields so unrelated SOOZ metadata remains authoritative and intact.
	if ( ! ai4seo_save_active_metadata_to_postmeta( $post_id, $changed_metadata ) ) {
		ai4seo_debug_message( 731948205, 'Could not synchronize persisted ' . $plugin_identifier . ' metadata to SOOZ for post id ' . $post_id . '.', true );
		return false;
	}

	// Defer derived-state work because editors commonly persist several fields in one request.
	ai4seo_manage_third_party_seo_metadata_sync_request_state( 'mark-changed', $post_id );

	return true;
}

// =========================================================================================== \\

/**
 * Mirrors one supported persisted third-party postmeta change into SOOZ active metadata.
 *
 * @param mixed  $meta_id_or_ids The affected postmeta id or ids supplied by WordPress.
 * @param int    $post_id The post id whose metadata changed.
 * @param string $meta_key The changed postmeta key.
 * @param mixed  $meta_value The persisted postmeta value.
 * @return void
 */
function ai4seo_sync_third_party_postmeta_change_to_active_metadata( $meta_id_or_ids, int $post_id, string $meta_key, $meta_value ): void {
	// WordPress supplies row ids for these hooks, but synchronization is keyed by post and metadata field.
	unset( $meta_id_or_ids );

	// Resolve the changed postmeta key before post, setting, or supported-type reads enter this global hook path.
	$sync_details_by_postmeta_key        = ai4seo_get_third_party_seo_plugin_inbound_sync_details_by_postmeta_key();
	$meta_key                            = sanitize_key( $meta_key );
	$sync_details                        = $sync_details_by_postmeta_key[ $meta_key ] ?? array();
	$third_party_seo_plugin_identifier   = $sync_details['plugin_identifier'] ?? '';
	$metadata_identifier                 = $sync_details['metadata_identifier'] ?? '';
	$metadata_identifier_by_array_key    = $sync_details['metadata_identifier_by_array_key'] ?? array();
	$has_compound_metadata_field_mapping = is_array( $metadata_identifier_by_array_key )
		&& ! empty( $metadata_identifier_by_array_key );

	if ( ! $third_party_seo_plugin_identifier
		|| ( ! $metadata_identifier && ! $has_compound_metadata_field_mapping ) ) {
		return;
	}

	$raw_metadata_values = array();

	if ( $has_compound_metadata_field_mapping ) {
		// Deleting the shared row clears every mapped field; ordinary writes read each nested value independently.
		if ( 'deleted_post_meta' === current_filter() || null === $meta_value ) {
			foreach ( $metadata_identifier_by_array_key as $this_metadata_identifier ) {
				$raw_metadata_values[ $this_metadata_identifier ] = '';
			}
		} elseif ( is_array( $meta_value ) ) {
			foreach ( $metadata_identifier_by_array_key as $array_key => $this_metadata_identifier ) {
				$raw_metadata_values[ $this_metadata_identifier ] = $meta_value[ $array_key ] ?? '';
			}
		} else {
			return;
		}
	} else {
		$raw_metadata_values[ $metadata_identifier ] = $meta_value;
	}

	ai4seo_sync_third_party_metadata_values_to_active_metadata(
		$post_id,
		$third_party_seo_plugin_identifier,
		$raw_metadata_values,
		'deleted_post_meta' === current_filter()
	);
}

// =========================================================================================== \\

/**
 * Mirrors Squirrly's persisted custom-table metadata into SOOZ.
 *
 * @param int $post_id The post whose Squirrly metadata should be synchronized.
 * @return bool Whether the saved state was synchronized or already matched SOOZ.
 */
function ai4seo_sync_squirrly_seo_metadata_to_active_metadata( int $post_id ): bool {
	// Normalize hook and request values before any plugin, setting, or custom-table work.
	$post_id = absint( $post_id );

	if ( $post_id <= 0 || wp_is_post_revision( $post_id ) ) {
		return false;
	}

	// Reject unsupported post types before entering Squirrly's unindexed serialized-row lookup.
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, ai4seo_get_supported_post_types(), true ) ) {
		return false;
	}

	// Avoid querying Squirrly's custom table when the plugin is inactive or no shared fields are enabled.
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO ) ) {
		return false;
	}

	if ( ! ai4seo_get_third_party_seo_plugin_sync_metadata_identifiers( AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO ) ) {
		return true;
	}

	// Read Squirrly only after activation and synchronization settings make the row relevant.
	$squirrly_metadata_by_post_id = ai4seo_read_squirrly_seo_metadata_by_post_ids( array( $post_id ) );

	if ( ! array_key_exists( $post_id, $squirrly_metadata_by_post_id )
		|| ! is_array( $squirrly_metadata_by_post_id[ $post_id ] ) ) {
		return false;
	}

	return ai4seo_sync_third_party_metadata_values_to_active_metadata(
		$post_id,
		AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO,
		$squirrly_metadata_by_post_id[ $post_id ]
	);
}

// =========================================================================================== \\

/**
 * Synchronizes Squirrly after its own purple Save button successfully persists the snippet.
 *
 * Squirrly's sq_save_seo_after action does not pass the post id, so use the same request field
 * that its successful AJAX save handler already validated and persisted.
 *
 * @return void
 */
function ai4seo_sync_squirrly_seo_editor_save_to_active_metadata(): void {
	// Squirrly has used several post-id request keys across its editor save paths.
	$post_id = 0;

	foreach ( array( 'post_id', 'post_ID', 'ID' ) as $post_id_request_key ) {
		if ( ! isset( $_REQUEST[ $post_id_request_key ] ) || ! is_scalar( $_REQUEST[ $post_id_request_key ] ) ) {
			continue;
		}

		$post_id = absint( wp_unslash( $_REQUEST[ $post_id_request_key ] ) );

		if ( $post_id > 0 ) {
			break;
		}
	}

	if ( $post_id > 0 && ai4seo_sync_squirrly_seo_metadata_to_active_metadata( $post_id ) ) {
		// Squirrly can fire this action from its save_post callback before SOOZ's priority-30 fallback.
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'mark-squirrly-synced', $post_id );
	}
}

// =========================================================================================== \\

/**
 * Synchronizes Squirrly after the normal WordPress page or post Save flow completes its writes.
 *
 * @param int     $post_id The saved post id.
 * @param WP_Post $post The saved post object.
 * @return void
 */
function ai4seo_sync_squirrly_seo_post_save_to_active_metadata( int $post_id, WP_Post $post ): void {
	// Autosaves, revisions, and non-public lifecycle placeholders must not replace persisted SOOZ metadata.
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id )
		|| 'auto-draft' === $post->post_status || 'inherit' === $post->post_status ) {
		return;
	}

	// Squirrly's successful editor-save action already exposed its final row earlier in this request.
	$request_state = ai4seo_manage_third_party_seo_metadata_sync_request_state( 'read' );

	if ( isset( $request_state['squirrly_synced_post_ids'][ $post_id ] ) ) {
		return;
	}

	// Reuse the editor-save path so both save mechanisms normalize and merge the same persisted row.
	ai4seo_sync_squirrly_seo_metadata_to_active_metadata( $post_id );
}

// =========================================================================================== \\

/**
 * Refreshes derived state once for every post changed by persisted third-party synchronization.
 *
 * @return void
 */
function ai4seo_finalize_third_party_seo_metadata_sync(): void {
	// Consume the queue first so a second finalizer invocation cannot repeat completed work.
	$request_state    = ai4seo_manage_third_party_seo_metadata_sync_request_state( 'consume-changed' );
	$changed_post_ids = array_keys( $request_state['changed_post_ids'] );

	// Refresh each affected post once, regardless of how many third-party fields changed during the request.
	foreach ( $changed_post_ids as $this_post_id ) {
		$this_post_id = absint( $this_post_id );

		if ( $this_post_id <= 0 || isset( $request_state['deleting_post_ids'][ $this_post_id ] ) ) {
			continue;
		}

		// Coverage reads the newly persisted SOOZ values before the frontend cache is invalidated.
		ai4seo_refresh_one_posts_metadata_coverage_status( $this_post_id );

		try {
			ai4seo_purge_frontend_cache_for_post( $this_post_id );
		} catch ( Throwable $throwable ) {
			// Cache integrations are best-effort and must not break request shutdown.
			ai4seo_debug_message( 418620937, 'Could not purge the frontend cache after third-party metadata synchronization: ' . $throwable->getMessage(), true );
		}
	}
}

// =========================================================================================== \\

/**
 * Resolves Yoast template variables in metadata that SOOZ is about to render on the frontend.
 *
 * @param string $metadata_value The raw metadata value.
 * @param int    $post_id The post id used as Yoast replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved metadata value, or the original value when resolution is unavailable.
 */
function ai4seo_resolve_yoast_metadata_variables( string $metadata_value, int $post_id, string $metadata_identifier ): string {
	// Most metadata is literal, so avoid loading Yoast context unless a template marker is present.
	if ( '' === $metadata_value || false === strpos( $metadata_value, '%%' ) ) {
		return $metadata_value;
	}

	// Share registry-derived field support with synchronization and the other integration resolvers.
	$yoast_metadata_identifiers = ai4seo_get_third_party_seo_plugin_generation_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO
	);

	// Resolve only Yoast-supported fields when its replacement service is available in this request.
	if ( ! in_array( $metadata_identifier, $yoast_metadata_identifiers, true )
		|| ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO )
		|| ! class_exists( 'WPSEO_Replace_Vars' ) ) {
		return $metadata_value;
	}

	// Supply the current post on every request so title, separator, and site-name variables remain dynamic.
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return $metadata_value;
	}

	// Preserve the raw template if Yoast cannot resolve it, matching the integration's fail-safe contract.
	try {
		$yoast_replace_vars = new WPSEO_Replace_Vars();
		$resolved_value     = $yoast_replace_vars->replace( $metadata_value, $post );
	} catch ( Throwable $throwable ) {
		return $metadata_value;
	}

	if ( ! is_string( $resolved_value ) ) {
		return $metadata_value;
	}

	// Apply the active metadata field cap after dynamic variables expand in the current request.
	$resolved_value = ai4seo_normalize_editor_input_value( $resolved_value );
	$max_length     = ai4seo_get_max_editor_input_length( $metadata_identifier );

	return ai4seo_trim_string_to_length( $resolved_value, $max_length );
}

// =========================================================================================== \\

/**
 * Resolves SEOPress template variables in metadata used outside its own renderer.
 *
 * @param string $metadata_value The raw metadata value.
 * @param int    $post_id The post id used as SEOPress replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved metadata value, or the original value when resolution is unavailable.
 */
function ai4seo_resolve_seopress_metadata_variables( string $metadata_value, int $post_id, string $metadata_identifier ): string {
	// Literal values bypass SEOPress services on the common frontend path.
	if ( '' === $metadata_value || false === strpos( $metadata_value, '%%' ) ) {
		return $metadata_value;
	}

	// Share registry-derived field support with synchronization and the other integration resolvers.
	$seopress_metadata_identifiers = ai4seo_get_third_party_seo_plugin_generation_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS
	);

	if ( ! in_array( $metadata_identifier, $seopress_metadata_identifiers, true )
		|| ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS )
		|| ! function_exists( 'seopress_get_service' )
		|| ! class_exists( '\\SEOPress\\Services\\Context\\ContextPage' ) ) {
		return $metadata_value;
	}

	// Let another integration handle mixed or foreign templates instead of stripping unknown tags.
	if ( ! preg_match_all( '/%%(.*?)%%/u', $metadata_value, $matches ) || empty( $matches[1] ) ) {
		return $metadata_value;
	}

	try {
		$tags_to_string = seopress_get_service( 'TagsToString' );

		if ( ! is_object( $tags_to_string )
			|| ! is_callable( array( $tags_to_string, 'getTagClass' ) )
			|| ! is_callable( array( $tags_to_string, 'replace' ) ) ) {
			return $metadata_value;
		}

		foreach ( array_unique( $matches[1] ) as $seopress_tag ) {
			if ( null === $tags_to_string->getTagClass( $seopress_tag ) ) {
				return $metadata_value;
			}
		}

		$context_page   = new \SEOPress\Services\Context\ContextPage();
		$context        = $context_page->buildContextWithCurrentId( $post_id )->getContext();
		$resolved_value = $tags_to_string->replace( $metadata_value, $context );
	} catch ( Throwable $throwable ) {
		return $metadata_value;
	}

	if ( ! is_string( $resolved_value ) ) {
		return $metadata_value;
	}

	// Apply the active metadata field cap after dynamic variables expand in the current request.
	$resolved_value = ai4seo_normalize_editor_input_value( $resolved_value );
	$max_length     = ai4seo_get_max_editor_input_length( $metadata_identifier );

	return ai4seo_trim_string_to_length( $resolved_value, $max_length );
}

// =========================================================================================== \\

/**
 * Classifies single-percent metadata variables against Rank Math's live registry.
 *
 * @param string $metadata_value The metadata value to inspect.
 * @return array Registered and unregistered full variable tokens.
 */
function ai4seo_get_rank_math_metadata_variable_matches( string $metadata_value ): array {
	$variable_matches = array(
		'registered'   => array(),
		'unregistered' => array(),
	);

	// Avoid initializing Rank Math on the common literal-value path or when its registry is unavailable.
	if ( '' === $metadata_value || false === strpos( $metadata_value, '%' ) || ! function_exists( 'rank_math' ) ) {
		return $variable_matches;
	}

	try {
		$rank_math = rank_math();

		if ( ! isset( $rank_math->variables ) ) {
			return $variable_matches;
		}

		// AJAX and CLI requests do not fire Rank Math's normal admin/frontend setup hooks.
		if ( is_callable( array( $rank_math->variables, 'setup' ) ) ) {
			$rank_math->variables->setup();
		}

		if ( ! is_callable( array( $rank_math->variables, 'get_replacements' ) ) ) {
			return $variable_matches;
		}

		$registered_replacements = $rank_math->variables->get_replacements();
	} catch ( Throwable $throwable ) {
		return $variable_matches;
	}

	if ( ! is_array( $registered_replacements )
		|| ! preg_match_all( '/(?<!%)%(([a-z0-9_-]+)\(([^)]*)\)|[^\s%]+)%(?!%)/iu', $metadata_value, $matches, PREG_SET_ORDER ) ) {
		return $variable_matches;
	}

	foreach ( $matches as $match ) {
		$full_token    = $match[0];
		$variable_id   = $match[1];
		$has_arguments = ! empty( $match[2] ) && ! empty( $match[3] );

		// Rank Math accepts either the base id or its optional argument-specific registry entry.
		$is_registered = isset( $registered_replacements[ $variable_id ] );

		if ( $has_arguments ) {
			$variable_id   = $match[2];
			$is_registered = isset( $registered_replacements[ $variable_id ] )
				|| isset( $registered_replacements[ $variable_id . '_args' ] );
		}

		$classification                       = $is_registered ? 'registered' : 'unregistered';
		$variable_matches[ $classification ][] = $full_token;
	}

	$variable_matches['registered']   = array_values( array_unique( $variable_matches['registered'] ) );
	$variable_matches['unregistered'] = array_values( array_unique( $variable_matches['unregistered'] ) );

	return $variable_matches;
}

// =========================================================================================== \\

/**
 * Checks whether a metadata value contains a registered Rank Math template variable.
 *
 * @param string $metadata_value The metadata value to inspect.
 * @return bool Whether the value contains a registered single-percent Rank Math variable.
 */
function ai4seo_metadata_contains_rank_math_variable( string $metadata_value ): bool {
	$variable_matches = ai4seo_get_rank_math_metadata_variable_matches( $metadata_value );

	return ! empty( $variable_matches['registered'] );
}

// =========================================================================================== \\

/**
 * Resolves Rank Math template variables in metadata used outside Rank Math's own renderer.
 *
 * @param string $metadata_value The raw metadata value.
 * @param int    $post_id The post id used as Rank Math replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved metadata value, or the original value when resolution is unavailable.
 */
function ai4seo_resolve_rank_math_metadata_variables( string $metadata_value, int $post_id, string $metadata_identifier ): string {
	// Literal values bypass Rank Math services on the common frontend path.
	if ( '' === $metadata_value || false === strpos( $metadata_value, '%' ) ) {
		return $metadata_value;
	}

	// Share registry-derived field support with synchronization and the other integration resolvers.
	$rank_math_metadata_identifiers = ai4seo_get_third_party_seo_plugin_generation_metadata_identifiers(
		AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH
	);

	// Resolve only Rank Math-supported fields while its public replacement service is active and available.
	if ( ! in_array( $metadata_identifier, $rank_math_metadata_identifiers, true )
		|| ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH )
		|| ! class_exists( 'RankMath\Helper' ) ) {
		return $metadata_value;
	}

	// Supply the current post so dynamic title, separator, site, taxonomy, and custom-field variables retain their semantics.
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return $metadata_value;
	}

	$original_metadata_value = $metadata_value;

	// Treat integration exceptions as an unavailable resolution service instead of breaking frontend rendering.
	try {
		$variable_matches = ai4seo_get_rank_math_metadata_variable_matches( $metadata_value );

		if ( empty( $variable_matches['registered'] ) ) {
			return $metadata_value;
		}

		// Shield literal percent-delimited text because Rank Math removes every unknown replacement by default.
		$preserved_literals = array();
		$placeholder_base   = 'AI4SEO_PERCENT_LITERAL_' . md5( $metadata_value ) . '_';

		foreach ( $variable_matches['unregistered'] as $index => $unregistered_token ) {
			$placeholder = '{' . $placeholder_base . $index . '}';

			while ( false !== strpos( $metadata_value, $placeholder ) ) {
				$placeholder .= '_';
			}

			$metadata_value                    = str_replace( $unregistered_token, $placeholder, $metadata_value );
			$preserved_literals[ $placeholder ] = $unregistered_token;
		}

		$resolved_value = RankMath\Helper::replace_vars( $metadata_value, $post );

		if ( $preserved_literals ) {
			$resolved_value = strtr( $resolved_value, $preserved_literals );
		}
	} catch ( Throwable $throwable ) {
		return $original_metadata_value;
	}

	if ( ! is_string( $resolved_value ) ) {
		return $original_metadata_value;
	}

	// Apply the active metadata field cap after dynamic variables expand in the current request.
	$resolved_value = ai4seo_normalize_editor_input_value( $resolved_value );
	$max_length     = ai4seo_get_max_editor_input_length( $metadata_identifier );

	return ai4seo_trim_string_to_length( $resolved_value, $max_length );
}

// =========================================================================================== \\

/**
 * Resolves supported third-party templates and rejects values that remain unresolved.
 *
 * Returning an empty string lets frontend replace mode retain the integration's own correctly
 * rendered tag instead of publishing raw template syntax through SOOZ.
 *
 * @param string $metadata_value The raw metadata value.
 * @param int    $post_id The post id used as replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved metadata value, or an empty string when a template remains unresolved.
 */
function ai4seo_resolve_third_party_seo_metadata_variables( string $metadata_value, int $post_id, string $metadata_identifier ): string {
	// Apply each integration's syntax-specific resolver without affecting ordinary literal percentages.
	$metadata_value = ai4seo_resolve_seopress_metadata_variables( $metadata_value, $post_id, $metadata_identifier );
	$metadata_value = ai4seo_resolve_yoast_metadata_variables( $metadata_value, $post_id, $metadata_identifier );
	$metadata_value = ai4seo_resolve_rank_math_metadata_variables( $metadata_value, $post_id, $metadata_identifier );

	// Never publish or forward a recognized token when its owning integration could not resolve it.
	if ( preg_match( '/%%[^%\s]+%%/u', $metadata_value )
		|| ai4seo_metadata_contains_rank_math_variable( $metadata_value ) ) {
		return '';
	}

	return $metadata_value;
}

// =========================================================================================== \\

/**
 * Prepares optional third-party metadata context before it is sent for generation.
 *
 * @param string $metadata_value The submitted metadata value.
 * @param int    $post_id The post id used as integration replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved value, or an empty string when a supported token remains unresolved.
 */
function ai4seo_prepare_third_party_seo_metadata_value_for_generation_context(
	string $metadata_value,
	int $post_id,
	string $metadata_identifier
): string {
	// Literal context needs neither integration resolution nor a temporary WordPress filter.
	if ( '' === $metadata_value
		|| ( false === strpos( $metadata_value, '%%' )
			&& ! ai4seo_metadata_contains_rank_math_variable( $metadata_value ) ) ) {
		return $metadata_value;
	}

	// Keep unresolved Yoast placeholders visible until the shared guard can reject the complete value.
	$preserve_unresolved_variables = static function (): bool {
		return false;
	};

	$has_yoast_variable = false !== strpos( $metadata_value, '%%' );

	if ( $has_yoast_variable ) {
		add_filter( 'wpseo_replacements_final', $preserve_unresolved_variables, PHP_INT_MAX );
	}

	try {
		// Reuse frontend resolution while limiting Yoast placeholder preservation to this one call.
		$metadata_value = ai4seo_resolve_third_party_seo_metadata_variables( $metadata_value, $post_id, $metadata_identifier );
	} finally {
		if ( $has_yoast_variable ) {
			remove_filter( 'wpseo_replacements_final', $preserve_unresolved_variables, PHP_INT_MAX );
		}
	}

	return $metadata_value;
}

// =========================================================================================== \\

/**
 * Returns the keyphrase of the currently active third party SEO plugin, if it exists
 *
 * @param int $post_id The post id.
 * @return string The keyphrase or an empty string
 */
function ai4seo_get_any_third_party_seo_plugin_keyphrase( int $post_id ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 608212638, 'Prevented loop', true );
		return '';
	}

	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'] )
			|| empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] ) ) {
			continue;
		}

		$keyphrase_postmeta_key = sanitize_text_field( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] );

		$this_keyphrase = get_post_meta( $post_id, $keyphrase_postmeta_key, true );

		if ( is_string( $this_keyphrase ) ) {
			$this_keyphrase = sanitize_text_field( $this_keyphrase );

			if ( '' !== $this_keyphrase ) {
				return ai4seo_get_primary_third_party_seo_plugin_focus_keyphrase(
					$this_third_party_seo_plugin_identifier,
					$this_keyphrase
				);
			}
		}
	}

	return '';
}

// =========================================================================================== \\

/**
 * Returns the key phrases for the given post ids (based on the currently active third party seo plugin)
 *
 * @param array $post_ids post ids.
 * @return array key phrases by post id or null on error
 */
function ai4seo_read_third_party_seo_plugin_key_phrases( array $post_ids ): ?array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 807307700, 'Prevented loop', true );
		return array();
	}

	if ( ! $post_ids ) {
		return array();
	}

	$sanitized_post_ids = array_map( 'intval', $post_ids );
	$sanitized_post_ids = array_filter( $sanitized_post_ids );

	if ( empty( $sanitized_post_ids ) ) {
		return array();
	}

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size       = ai4seo_get_database_chunk_size();
	$sanitized_post_ids_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	// only consider the currently active third party seo plugins.
	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! $active_supported_third_party_seo_plugins ) {
		return array();
	}

	// go through all active third party seo plugins and get the key phrases.
	$key_phrases = array();

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'] )
			|| empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] ) ) {
			continue;
		}

		// if we found all key phrases, we can stop the loop.
		if ( count( $key_phrases ) === count( $post_ids ) ) {
			break;
		}

		$this_keyphrase_postmeta_key = sanitize_text_field( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] );

		foreach ( $sanitized_post_ids_chunks as $this_post_ids_chunk ) {
			if ( empty( $this_post_ids_chunk ) ) {
				continue;
			}

			$this_post_id_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

			$query_args = array_merge( array( $this_keyphrase_postmeta_key ), $this_post_ids_chunk );

			$this_postmeta_entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = %s
                    AND post_id IN ({$this_post_id_placeholders})",
					...$query_args
				),
				ARRAY_A
			);

			// on error.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321654, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! $this_postmeta_entries ) {
				continue;
			}

			// loop through all key phrases and add them to the $ai4seo_this_page_post_ids array.
			foreach ( $this_postmeta_entries as $this_postmeta_entry ) {
				$this_post_id          = isset( $this_postmeta_entry['post_id'] ) ? intval( $this_postmeta_entry['post_id'] ) : 0;
				$this_key_phrase_value = isset( $this_postmeta_entry['meta_value'] ) ? sanitize_text_field( $this_postmeta_entry['meta_value'] ) : '';
				$this_key_phrase_value = ai4seo_get_primary_third_party_seo_plugin_focus_keyphrase(
					$this_third_party_seo_plugin_identifier,
					$this_key_phrase_value
				);

				// Make sure that post id is numeric.
				if ( ! $this_post_id ) {
					continue;
				}

				// skip if we already have a key phrase for this post id.
				if ( isset( $key_phrases[ $this_post_id ] ) ) {
					continue;
				}

				// Add key phrase to the $ai4seo_this_page_post_ids array.
				$key_phrases[ $this_post_id ] = $this_key_phrase_value;
			}
		}
	}

	return $key_phrases;
}

// =========================================================================================== \\

/**
 * Returns the yoast seo scores for the given post ids
 *
 * @param array $post_ids post ids.
 * @return array yoast seo scores by post id or null on error
 */
function ai4seo_read_yoast_seo_scores( array $post_ids ): ?array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 647946549, 'Prevented loop', true );
		return array();
	}

	// todo: make this whole function dynamic.

	// Make sure that yoast seo plugin is active.
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ) ) {
		return array();
	}

	if ( ! $post_ids ) {
		return array();
	}

	// Normalize post IDs before binding them to the generated placeholders.
	$sanitized_post_ids = array_map(
		function ( $id ) {
			return intval( $id );
		},
		$post_ids
	);

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size      = ai4seo_get_database_chunk_size();
	$sanitized_post_id_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	$yoast_seo_scores = array();

	foreach ( $sanitized_post_id_chunks as $this_post_id_chunk ) {
		$this_post_ids_placeholders = implode( ', ', array_fill( 0, count( $this_post_id_chunk ), '%d' ) );

		// Read Yoast scores for the current chunk with post IDs bound as placeholders.
		$this_chunk_yoast_seo_scores = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND post_id IN ( {$this_post_ids_placeholders} )",
				...array_merge(
					array( '_yoast_wpseo_linkdex' ),
					$this_post_id_chunk
				)
			)
		);

		// on error.
		if ( $wpdb->last_error ) {
			ai4seo_debug_message( 984321655, 'Database error: ' . $wpdb->last_error, true );
			return array();
		}

		if ( ! $this_chunk_yoast_seo_scores ) {
			continue;
		}

		$yoast_seo_scores = array_merge( $yoast_seo_scores, $this_chunk_yoast_seo_scores );
	}

	if ( ! $yoast_seo_scores ) {
		return array();
	}

	// loop through all yoast seo scores and add them to the $ai4seo_this_page_post_ids array.
	$seo_scores = array();

	foreach ( $yoast_seo_scores as $yoast_seo_score ) {
		$post_id   = $yoast_seo_score->post_id;
		$seo_score = $yoast_seo_score->meta_value;

		// Make sure that post id is numeric.
		if ( ! is_numeric( $post_id ) || ! $post_id ) {
			continue;
		}

		// Add seo score to the $ai4seo_this_page_post_ids array.
		$seo_scores[ $post_id ] = $seo_score;
	}

	return $seo_scores;
}


// endregion
// ___________________________________________________________________________________________.
