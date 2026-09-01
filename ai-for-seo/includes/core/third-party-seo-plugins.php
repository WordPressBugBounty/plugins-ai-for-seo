<?php
/**
 * Provides integrations with third-party SEO plugins.
 *
 * @package AI_For_SEO
 */

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
			$metadata_identifier_by_array_key              = array_flip( $compound_sync_details['generation_field_array_keys'] );
			$postmeta_key                                  = $compound_sync_details['postmeta_key'];
			$sync_details_by_postmeta_key[ $postmeta_key ] = array(
				'plugin_identifier'                => sanitize_key( $plugin_identifier ),
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
			$postmeta_key        = sanitize_key( $postmeta_key );

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


/**
 * Manages request-local state for persisted third-party-to-SOOZ metadata synchronization.
 *
 * Supported operations are read, begin-outbound, end-outbound, mark-deleting,
 * mark-changed, mark-squirrly-synced, consume-changed, requeue-site-state,
 * and requeue-network-fallback.
 *
 * @param string $operation The state operation to perform.
 * @param int    $post_id The optional post id associated with the operation.
 * @param array  $origin_site_state Optional consumed origin-site state to requeue after finalization failure.
 * @return array The complete request-local synchronization state.
 */
function ai4seo_manage_third_party_seo_metadata_sync_request_state(
	string $operation,
	int $post_id = 0,
	array $origin_site_state = array()
): array {
	global $wpdb;

	// Keep nested outbound writes and per-post queues isolated to their originating site's storage.
	static $state_by_site_scope       = array();
	static $changed_site_scope_index  = array();
	static $requires_network_fallback = false;

	// Normalize internal callers so state keys always use the same post-id representation.
	$operation      = sanitize_key( $operation );
	$post_id        = absint( $post_id );
	$blog_id        = absint( get_current_blog_id() );
	$options_table  = isset( $wpdb->options ) ? (string) $wpdb->options : '';
	$postmeta_table = isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '';
	$site_scope_key = $options_table . '|' . $postmeta_table . '|' . $blog_id;

	if ( ! isset( $state_by_site_scope[ $site_scope_key ] ) ) {
		$state_by_site_scope[ $site_scope_key ] = array(
			'blog_id'                        => $blog_id,
			'options_table'                  => $options_table,
			'postmeta_table'                 => $postmeta_table,
			'outbound_sync_depth_by_post_id' => array(),
			'deleting_post_ids'              => array(),
			'changed_post_ids'               => array(),
			'squirrly_synced_post_ids'       => array(),
			'requires_full_rebuild'          => false,
		);
	}

	$state =& $state_by_site_scope[ $site_scope_key ];

	if ( 'requeue-network-fallback' === $operation ) {
		$requires_network_fallback = true;
		return $state;
	}

	if ( 'requeue-site-state' === $operation ) {
		$origin_blog_id        = absint( $origin_site_state['blog_id'] ?? 0 );
		$origin_options_table  = isset( $origin_site_state['options_table'] ) ? (string) $origin_site_state['options_table'] : '';
		$origin_postmeta_table = isset( $origin_site_state['postmeta_table'] ) ? (string) $origin_site_state['postmeta_table'] : '';
		$origin_scope_key      = $origin_options_table . '|' . $origin_postmeta_table . '|' . $origin_blog_id;
		$changed_post_ids      = isset( $origin_site_state['changed_post_ids'] ) && is_array( $origin_site_state['changed_post_ids'] )
			? array_keys( $origin_site_state['changed_post_ids'] )
			: array();
		$requires_full_rebuild = ! empty( $origin_site_state['requires_full_rebuild'] );

		if ( $origin_blog_id <= 0 || '' === $origin_options_table || '' === $origin_postmeta_table
			|| ( ! $changed_post_ids && ! $requires_full_rebuild ) ) {
			return $state;
		}

		if ( isset( $state_by_site_scope[ $origin_scope_key ] )
			&& ( absint( $state_by_site_scope[ $origin_scope_key ]['blog_id'] ?? 0 ) !== $origin_blog_id
				|| (string) ( $state_by_site_scope[ $origin_scope_key ]['options_table'] ?? '' ) !== $origin_options_table
				|| (string) ( $state_by_site_scope[ $origin_scope_key ]['postmeta_table'] ?? '' ) !== $origin_postmeta_table )
		) {
			return $state;
		}

		if ( ! isset( $changed_site_scope_index[ $origin_scope_key ] )
			&& count( $changed_site_scope_index ) >= ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit()
		) {
			$requires_network_fallback = true;
			return $state;
		}

		$changed_site_scope_index[ $origin_scope_key ] = true;

		if ( ! isset( $state_by_site_scope[ $origin_scope_key ] ) ) {
			$state_by_site_scope[ $origin_scope_key ] = array(
				'blog_id'                        => $origin_blog_id,
				'options_table'                  => $origin_options_table,
				'postmeta_table'                 => $origin_postmeta_table,
				'outbound_sync_depth_by_post_id' => array(),
				'deleting_post_ids'              => array(),
				'changed_post_ids'               => array(),
				'squirrly_synced_post_ids'       => array(),
				'requires_full_rebuild'          => false,
			);
		}

		$origin_state =& $state_by_site_scope[ $origin_scope_key ];

		if ( $requires_full_rebuild ) {
			$origin_state['changed_post_ids']      = array();
			$origin_state['requires_full_rebuild'] = true;
			return $origin_state;
		}

		$exact_post_id_limit = ai4seo_get_third_party_seo_metadata_finalization_retry_post_id_limit();

		foreach ( $changed_post_ids as $changed_post_id ) {
			$changed_post_id = absint( $changed_post_id );

			if (
				$changed_post_id <= 0
				|| $origin_state['requires_full_rebuild']
				|| isset( $origin_state['deleting_post_ids'][ $changed_post_id ] )
				|| isset( $origin_state['changed_post_ids'][ $changed_post_id ] )
			) {
				continue;
			}

			if ( count( $origin_state['changed_post_ids'] ) < $exact_post_id_limit ) {
				$origin_state['changed_post_ids'][ $changed_post_id ] = true;
			} else {
				$origin_state['changed_post_ids']      = array();
				$origin_state['requires_full_rebuild'] = true;
			}
		}

		return $origin_state;
	}

	if ( 'consume-changed' === $operation ) {
		// Consume only the bounded changed-origin index; unrelated guard state is never scanned or discarded.
		$consumed_state                              = $state;
		$consumed_state['site_states']               = array();
		$consumed_state['requires_network_fallback'] = $requires_network_fallback;

		foreach ( array_keys( $changed_site_scope_index ) as $this_site_scope_key ) {
			if ( ! isset( $state_by_site_scope[ $this_site_scope_key ] ) ) {
				continue;
			}

			$this_site_state =& $state_by_site_scope[ $this_site_scope_key ];

			if ( ! empty( $this_site_state['changed_post_ids'] ) || ! empty( $this_site_state['requires_full_rebuild'] ) ) {
				$consumed_state['site_states'][ $this_site_scope_key ] = $this_site_state;
			}

			$this_site_state['changed_post_ids']      = array();
			$this_site_state['requires_full_rebuild'] = false;

			if ( ! $this_site_state['outbound_sync_depth_by_post_id']
				&& ! $this_site_state['deleting_post_ids']
				&& ! $this_site_state['squirrly_synced_post_ids']
			) {
				unset( $this_site_state );
				unset( $state_by_site_scope[ $this_site_scope_key ] );
			}
		}

		unset( $this_site_state );
		$changed_site_scope_index  = array();
		$requires_network_fallback = false;

		return $consumed_state;
	}

	// Centralize all state transitions so outbound guards and shutdown finalization stay paired.
	switch ( $operation ) {
		case 'begin-outbound':
			// Scope nested suppression to the originating post so related-post integrations remain independent.
			if ( $post_id > 0 ) {
				$current_depth                                       = $state['outbound_sync_depth_by_post_id'][ $post_id ] ?? 0;
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

				if ( ! $state['changed_post_ids'] && ! $state['requires_full_rebuild'] ) {
					unset( $changed_site_scope_index[ $site_scope_key ] );
				}
			}
			break;
		case 'mark-changed':
			// Keying by post id collapses several third-party field writes into one shutdown refresh.
			// Once the exact-ID budget is exhausted, one full rebuild is the bounded superset repair.
			if ( $post_id > 0 && ! $state['requires_full_rebuild'] ) {
				$exact_post_id_limit = ai4seo_get_third_party_seo_metadata_finalization_retry_post_id_limit();

				if ( ! isset( $changed_site_scope_index[ $site_scope_key ] )
					&& count( $changed_site_scope_index ) >= ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit()
				) {
					$requires_network_fallback = true;

					if ( ! $state['outbound_sync_depth_by_post_id']
						&& ! $state['deleting_post_ids']
						&& ! $state['changed_post_ids']
						&& ! $state['squirrly_synced_post_ids']
					) {
						$overflow_state = $state;
						unset( $state );
						unset( $state_by_site_scope[ $site_scope_key ] );
						return $overflow_state;
					}

					break;
				}

				$changed_site_scope_index[ $site_scope_key ] = true;

				if ( isset( $state['changed_post_ids'][ $post_id ] ) ) {
					break;
				}

				if ( count( $state['changed_post_ids'] ) < $exact_post_id_limit ) {
					$state['changed_post_ids'][ $post_id ] = true;
				} else {
					$state['changed_post_ids']      = array();
					$state['requires_full_rebuild'] = true;
				}
			}
			break;
		case 'mark-squirrly-synced':
			// Prevent Squirrly's later save_post fallback from repeating a completed editor-save synchronization.
			if ( $post_id > 0 ) {
				$state['squirrly_synced_post_ids'][ $post_id ] = true;
			}
			break;
		case 'read':
			// Callers receive the current snapshot without mutating request state.
			break;
	}

	return $state;
}


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

	// Let the locked authoritative writer decide both the merge base and whether storage changed.
	$active_metadata_operation_details = array();

	$active_metadata_succeeded        = ai4seo_save_active_metadata_to_postmeta( $post_id, $prepared_metadata, false, $active_metadata_operation_details );
	$active_metadata_commit_state     = isset( $active_metadata_operation_details['commit_state'] )
		&& is_string( $active_metadata_operation_details['commit_state'] )
		&& in_array( $active_metadata_operation_details['commit_state'], array( 'not_committed', 'committed', 'possibly_committed' ), true )
		? $active_metadata_operation_details['commit_state']
		: 'possibly_committed';
	$active_metadata_may_have_changed = ! empty( $active_metadata_operation_details['active_metadata_changed'] )
		|| 'not_committed' !== $active_metadata_commit_state;

	if ( $active_metadata_may_have_changed ) {
		// A failed readback or release still needs durable derived-state reconciliation at shutdown.
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'mark-changed', $post_id );
	}

	if ( ! $active_metadata_succeeded ) {
		ai4seo_debug_message( 731948205, 'Could not synchronize persisted ' . $plugin_identifier . ' metadata to SOOZ for post id ' . $post_id . '.', true );
		return false;
	}

	if ( ! $active_metadata_may_have_changed ) {
		return true;
	}

	return true;
}


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

	// Preserve the deletion context established while mapping hook values before calling the shared synchronizer.
	$is_deleted_post_meta_hook = ( 'deleted_post_meta' === current_filter() );

	ai4seo_sync_third_party_metadata_values_to_active_metadata(
		$post_id,
		$third_party_seo_plugin_identifier,
		$raw_metadata_values,
		$is_deleted_post_meta_hook
	);
}


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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Squirrly's verified post-save hook owns nonce validation.
		if ( ! isset( $_REQUEST[ $post_id_request_key ] ) || ! is_scalar( $_REQUEST[ $post_id_request_key ] ) ) {
			continue;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Squirrly's verified post-save hook owns nonce validation.
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


/**
 * Returns the site-local option that retains unfinished third-party finalization work.
 *
 * @return string Durable retry-ledger option name.
 */
function ai4seo_get_third_party_seo_metadata_finalization_retry_option_name(): string {
	return 'ai4seo_third_party_seo_metadata_finalization_retries';
}


/**
 * Returns the maximum exact post-ID retry entries retained for one origin site.
 *
 * Larger batches collapse to one full-rebuild marker so a permanently unreachable
 * site cannot grow the dispatcher site's retry option without bound.
 *
 * @return int Maximum exact post-ID retry entries per origin.
 */
function ai4seo_get_third_party_seo_metadata_finalization_retry_post_id_limit(): int {
	return 32;
}


/**
 * Returns the maximum number of exact origin scopes retained in the dispatcher ledger.
 *
 * Additional origins collapse into one bounded all-sites rebuild generation instead of
 * growing one request's shutdown work or option storage without limit.
 *
 * @return int Maximum exact durable origin records.
 */
function ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit(): int {
	return 32;
}


/**
 * Returns the maximum number of exact durable origins processed by one finalizer invocation.
 *
 * @return int Maximum exact durable origins per invocation.
 */
function ai4seo_get_third_party_seo_metadata_finalization_scope_work_limit(): int {
	return 4;
}


/**
 * Returns the number of durable retry origins or network sites claimed before one switch.
 *
 * Advancing one cursor item at a time lets a later invocation move past an origin whose
 * site restoration remains ambiguous without collapsing bounded same-request preownership.
 *
 * @return int Maximum durable recovery items per claim.
 */
function ai4seo_get_third_party_seo_metadata_finalization_recovery_claim_limit(): int {
	return 1;
}


/**
 * Returns the stable key for one originating site's third-party finalization work.
 *
 * @param array $site_state Origin site identity.
 * @return string Scope key, or an empty string for incomplete identity.
 */
function ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( array $site_state ): string {
	$blog_id        = absint( $site_state['blog_id'] ?? 0 );
	$options_table  = isset( $site_state['options_table'] ) ? (string) $site_state['options_table'] : '';
	$postmeta_table = isset( $site_state['postmeta_table'] ) ? (string) $site_state['postmeta_table'] : '';

	if ( $blog_id <= 0 || '' === $options_table || '' === $postmeta_table ) {
		return '';
	}

	return hash( 'sha256', $options_table . "\0" . $postmeta_table . "\0" . $blog_id );
}


/**
 * Returns the empty versioned durable finalization ledger.
 *
 * @return array Empty normalized ledger state.
 */
function ai4seo_get_empty_third_party_seo_metadata_finalization_retry_ledger(): array {
	return array(
		'version'          => 1,
		'records'          => array(),
		'cursor'           => '',
		'network_fallback' => array(
			'active_token'  => '',
			'restart_token' => '',
			'last_blog_id'  => 0,
		),
	);
}


/**
 * Validates one bounded retry-generation token.
 *
 * @param mixed $retry_token Candidate durable token.
 * @param bool  $allow_empty Whether the idle empty token is valid.
 * @return bool Whether the token has the bounded plugin-owned shape.
 */
function ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $retry_token, bool $allow_empty = false ): bool {
	if ( ! is_string( $retry_token ) ) {
		return false;
	}

	if ( '' === $retry_token ) {
		return $allow_empty;
	}

	return strlen( $retry_token ) <= 64
		&& 1 === preg_match( '/\A[A-Za-z0-9_.-]+\z/', $retry_token );
}


/**
 * Normalizes the exact origin records stored inside the durable ledger.
 *
 * @param mixed $raw_records Stored origin records.
 * @param bool  $enforce_scope_limit Whether the versioned-envelope scope cap must be enforced.
 * @return array|null Normalized records, or null for oversized/corrupt storage.
 */
function ai4seo_normalize_third_party_seo_metadata_finalization_retry_record_collection(
	$raw_records,
	bool $enforce_scope_limit = true
): ?array {
	if ( ! is_array( $raw_records )
		|| ( $enforce_scope_limit
			&& count( $raw_records ) > ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit() )
	) {
		return null;
	}

	$normalized_records  = array();
	$allowed_record_keys = array(
		'blog_id',
		'options_table',
		'postmeta_table',
		'post_id_tokens',
		'requires_full_rebuild',
	);

	foreach ( $raw_records as $scope_key => $raw_record ) {
		if ( ! is_string( $scope_key )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $scope_key )
			|| ! is_array( $raw_record )
			|| array_diff( array_keys( $raw_record ), $allowed_record_keys )
		) {
			return null;
		}

		$blog_id            = ai4seo_normalize_database_id( $raw_record['blog_id'] ?? null );
		$options_table      = $raw_record['options_table'] ?? null;
		$postmeta_table     = $raw_record['postmeta_table'] ?? null;
		$post_id_tokens     = $raw_record['post_id_tokens'] ?? null;
		$full_rebuild_token = $raw_record['requires_full_rebuild'] ?? null;

		if ( false === $blog_id
			|| ! is_string( $options_table )
			|| ! is_string( $postmeta_table )
			|| strlen( $options_table ) > 64
			|| strlen( $postmeta_table ) > 64
			|| ! ai4seo_is_valid_database_identifier( $options_table )
			|| ! ai4seo_is_valid_database_identifier( $postmeta_table )
			|| ! is_array( $post_id_tokens )
			|| ! ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $full_rebuild_token, true )
		) {
			return null;
		}

		$normalized_scope_key = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key(
			array(
				'blog_id'        => $blog_id,
				'options_table'  => $options_table,
				'postmeta_table' => $postmeta_table,
			)
		);

		if ( '' === $normalized_scope_key || ! hash_equals( $normalized_scope_key, $scope_key ) ) {
			return null;
		}

		$exact_post_id_limit = ai4seo_get_third_party_seo_metadata_finalization_retry_post_id_limit();

		if ( count( $post_id_tokens ) > $exact_post_id_limit ) {
			return null;
		}

		$normalized_post_id_tokens = array();

		foreach ( $post_id_tokens as $post_id => $retry_token ) {
			$post_id = ai4seo_normalize_database_id( $post_id );

			if ( false === $post_id
				|| ! ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $retry_token )
				|| isset( $normalized_post_id_tokens[ $post_id ] )
			) {
				return null;
			}

			$normalized_post_id_tokens[ $post_id ] = $retry_token;
		}

		if ( '' !== $full_rebuild_token ) {
			// A verified full analysis supersedes every exact per-post retry for this origin.
			$normalized_post_id_tokens = array();
		}

		if ( ! $normalized_post_id_tokens && '' === $full_rebuild_token ) {
			continue;
		}

		ksort( $normalized_post_id_tokens, SORT_NUMERIC );
		$normalized_records[ $scope_key ] = array(
			'blog_id'               => $blog_id,
			'options_table'         => $options_table,
			'postmeta_table'        => $postmeta_table,
			'post_id_tokens'        => $normalized_post_id_tokens,
			'requires_full_rebuild' => $full_rebuild_token,
		);
	}

	ksort( $normalized_records, SORT_STRING );

	return $normalized_records;
}


/**
 * Normalizes the bounded versioned durable third-party finalization ledger.
 *
 * A pre-envelope record map remains readable for the one-way deployment migration.
 *
 * @param mixed $raw_ledger Stored option value.
 * @return array|null Normalized ledger, or null when storage is ambiguous/corrupt.
 */
function ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger( $raw_ledger ): ?array {
	$empty_ledger = ai4seo_get_empty_third_party_seo_metadata_finalization_retry_ledger();

	if ( null === $raw_ledger || array() === $raw_ledger ) {
		return $empty_ledger;
	}

	if ( ! is_array( $raw_ledger ) ) {
		return null;
	}

	if ( ! array_key_exists( 'version', $raw_ledger ) ) {
		// The legacy record map had no global origin cap. Validate every decoded node before retaining a
		// deterministic exact subset, then cover every excess origin with one constant-size full sweep.
		$legacy_records = ai4seo_normalize_third_party_seo_metadata_finalization_retry_record_collection( $raw_ledger, false );

		if ( null === $legacy_records ) {
			return null;
		}

		$scope_limit = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit();

		if ( count( $legacy_records ) > $scope_limit ) {
			$canonical_legacy_records = wp_json_encode( $legacy_records );

			if ( ! is_string( $canonical_legacy_records ) || '' === $canonical_legacy_records ) {
				return null;
			}

			$empty_ledger['network_fallback']['active_token'] = hash( 'sha256', $canonical_legacy_records );
			$legacy_records                                   = array_slice( $legacy_records, 0, $scope_limit, true );
		}

		$empty_ledger['records'] = $legacy_records;
		return $empty_ledger;
	}

	$allowed_ledger_keys = array( 'version', 'records', 'cursor', 'network_fallback' );

	if ( 1 !== ( $raw_ledger['version'] ?? null )
		|| array_diff( array_keys( $raw_ledger ), $allowed_ledger_keys )
		|| ! array_key_exists( 'records', $raw_ledger )
		|| ! array_key_exists( 'cursor', $raw_ledger )
		|| ! array_key_exists( 'network_fallback', $raw_ledger )
	) {
		return null;
	}

	$records          = ai4seo_normalize_third_party_seo_metadata_finalization_retry_record_collection( $raw_ledger['records'] );
	$cursor           = $raw_ledger['cursor'];
	$network_fallback = $raw_ledger['network_fallback'];

	if ( null === $records
		|| ! is_string( $cursor )
		|| ( '' !== $cursor && 1 !== preg_match( '/\A[a-f0-9]{64}\z/', $cursor ) )
		|| ! is_array( $network_fallback )
		|| array_keys( $network_fallback ) !== array( 'active_token', 'restart_token', 'last_blog_id' )
		|| ! ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $network_fallback['active_token'] ?? null, true )
		|| ! ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $network_fallback['restart_token'] ?? null, true )
		|| ! is_int( $network_fallback['last_blog_id'] ?? null )
		|| $network_fallback['last_blog_id'] < 0
	) {
		return null;
	}

	if ( '' === $network_fallback['active_token']
		&& ( '' !== $network_fallback['restart_token'] || 0 !== $network_fallback['last_blog_id'] )
	) {
		return null;
	}

	return array(
		'version'          => 1,
		'records'          => $records,
		'cursor'           => $cursor,
		'network_fallback' => $network_fallback,
	);
}


/**
 * Preserves the public record-normalization contract while accepting the versioned ledger envelope.
 *
 * @param mixed $raw_ledger Stored option value.
 * @return array|null Normalized origin records, or null for invalid storage.
 */
function ai4seo_normalize_third_party_seo_metadata_finalization_retry_records( $raw_ledger ): ?array {
	$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger( $raw_ledger );

	return null === $ledger ? null : $ledger['records'];
}


/**
 * Reads the current verified site's complete durable third-party finalization ledger.
 *
 * @param bool|null $read_succeeded Receives whether authoritative storage was valid and readable.
 * @return array Normalized ledger, or the empty shape on failure.
 */
function ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( ?bool &$read_succeeded = null ): array {
	$read_succeeded = false;
	$option_name    = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$snapshot       = ai4seo_get_raw_option_snapshot( $option_name );
	$empty_ledger   = ai4seo_get_empty_third_party_seo_metadata_finalization_retry_ledger();

	if ( null === $snapshot ) {
		return $empty_ledger;
	}

	$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
		$snapshot['exists'] ? $snapshot['value'] : null
	);

	if ( null === $ledger ) {
		return $empty_ledger;
	}

	$read_succeeded = true;
	return $ledger;
}


/**
 * Reads the current verified site's durable third-party finalization origin records.
 *
 * @param bool|null $read_succeeded Receives whether authoritative storage was valid and readable.
 * @return array Normalized origin records keyed by scope.
 */
function ai4seo_read_third_party_seo_metadata_finalization_retry_records( ?bool &$read_succeeded = null ): array {
	$ledger = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );

	return $read_succeeded ? $ledger['records'] : array();
}


/**
 * Adds one constant-size all-sites fallback generation to a normalized ledger.
 *
 * New work arriving during an active sweep requests one later full sweep without rewinding
 * the active cursor, so permanently failing early sites cannot starve later blog IDs.
 *
 * @param array $ledger Normalized ledger, mutated in place.
 * @return string Active or pending token that durably represents the new work.
 */
function ai4seo_add_third_party_seo_metadata_network_fallback_generation( array &$ledger ): string {
	$retry_token      = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'ai4seo_', true );
	$network_fallback = $ledger['network_fallback'];

	if ( '' === $network_fallback['active_token'] ) {
		$network_fallback['active_token']  = $retry_token;
		$network_fallback['restart_token'] = '';
		$network_fallback['last_blog_id']  = 0;
	} else {
		// Replacing the single pending token lets a page claim clear only its own safety token;
		// every later arrival remains distinguishable while storage stays constant-size.
		$network_fallback['restart_token'] = $retry_token;
	}

	$ledger['network_fallback'] = $network_fallback;
	return $retry_token;
}


/**
 * Persist one bounded all-sites fallback generation.
 *
 * @param string|null $owned_token Receives the durable active or pending generation token.
 * @return bool Whether the fallback generation was written or already represented durably.
 */
function ai4seo_require_third_party_seo_metadata_network_fallback( ?string &$owned_token = null ): bool {
	$owned_token   = null;
	$option_name   = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return false;
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return false;
		}

		$original_ledger = $ledger;
		$owned_token     = ai4seo_add_third_party_seo_metadata_network_fallback_generation( $ledger );
		$is_envelope     = $snapshot['exists']
			&& is_array( $snapshot['value'] )
			&& 1 === ( $snapshot['value']['version'] ?? null );

		if ( $is_envelope && $ledger === $original_ledger ) {
			return true;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$verified_ledger = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );

		if ( ! $read_succeeded ) {
			return false;
		}

		return $owned_token === $verified_ledger['network_fallback']['active_token']
			|| $owned_token === $verified_ledger['network_fallback']['restart_token'];
	}

	return false;
}


/**
 * Adds bounded post retry ownership to the current verified site's durable ledger.
 *
 * @param array $site_state Origin site identity.
 * @param array $post_ids Post IDs requiring durable finalization retry.
 * @param array $owned_retry_record Receives exact post tokens or the full-rebuild token owned by this finalizer.
 * @param bool  $advance_existing_ownership Whether a semantic new arrival must replace matching older tokens.
 * @return bool Whether bounded durable ownership was written or observed and verified.
 */
function ai4seo_add_third_party_seo_metadata_finalization_retry_record(
	array $site_state,
	array $post_ids,
	array &$owned_retry_record,
	bool $advance_existing_ownership = false
): bool {
	$owned_retry_record    = array(
		'post_id_tokens'        => array(),
		'requires_full_rebuild' => '',
		'network_full_rebuild'  => '',
	);
	$scope_key             = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $site_state );
	$post_ids              = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$requires_full_rebuild = ! empty( $site_state['requires_full_rebuild'] );

	if ( '' === $scope_key || ( ! $post_ids && ! $requires_full_rebuild ) ) {
		return false;
	}

	$retry_token   = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'ai4seo_', true );
	$option_name   = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return false;
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return false;
		}

		$records     = $ledger['records'];
		$is_envelope = $snapshot['exists']
			&& is_array( $snapshot['value'] )
			&& 1 === ( $snapshot['value']['version'] ?? null );

		if ( ! isset( $records[ $scope_key ] )
			&& count( $records ) >= ai4seo_get_third_party_seo_metadata_finalization_retry_scope_limit()
		) {
			$network_token           = ai4seo_add_third_party_seo_metadata_network_fallback_generation( $ledger );
			$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
				$option_name,
				$snapshot,
				$ledger,
				false
			);

			if ( null === $compare_and_swap_result ) {
				return false;
			}

			if ( ! $compare_and_swap_result ) {
				continue;
			}

			$verified_ledger = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );

			if ( ! $read_succeeded
				|| ( $network_token !== $verified_ledger['network_fallback']['active_token']
					&& $network_token !== $verified_ledger['network_fallback']['restart_token'] )
			) {
				return false;
			}

			$owned_retry_record['network_full_rebuild'] = $network_token;
			return true;
		}

		$record             = $records[ $scope_key ] ?? array(
			'blog_id'               => absint( $site_state['blog_id'] ?? 0 ),
			'options_table'         => isset( $site_state['options_table'] ) ? (string) $site_state['options_table'] : '',
			'postmeta_table'        => isset( $site_state['postmeta_table'] ) ? (string) $site_state['postmeta_table'] : '',
			'post_id_tokens'        => array(),
			'requires_full_rebuild' => '',
		);
		$record_was_changed = false;

		if ( ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $record ) !== $scope_key ) {
			return false;
		}

		if ( '' !== $record['requires_full_rebuild'] && $advance_existing_ownership ) {
			// A new semantic arrival must outlive cleanup owned by a previously claimed full generation.
			$record['requires_full_rebuild'] = $retry_token;
			$record_was_changed              = true;
		} elseif ( '' !== $record['requires_full_rebuild'] ) {
			$retry_token                                 = $record['requires_full_rebuild'];
			$owned_retry_record['requires_full_rebuild'] = $record['requires_full_rebuild'];
		} elseif ( $requires_full_rebuild ) {
			$record['post_id_tokens']        = array();
			$record['requires_full_rebuild'] = $retry_token;
			$record_was_changed              = true;
		} else {
			foreach ( $post_ids as $post_id ) {
				if ( $advance_existing_ownership || ! isset( $record['post_id_tokens'][ $post_id ] ) ) {
					$record['post_id_tokens'][ $post_id ] = $retry_token;
					$record_was_changed                   = true;
				}
			}

			if ( count( $record['post_id_tokens'] ) > ai4seo_get_third_party_seo_metadata_finalization_retry_post_id_limit() ) {
				$record['post_id_tokens']        = array();
				$record['requires_full_rebuild'] = $retry_token;
				$record_was_changed              = true;
			}
		}

		if ( ! $record_was_changed && $is_envelope ) {
			if ( '' !== $record['requires_full_rebuild'] ) {
				return true;
			}

			foreach ( $post_ids as $post_id ) {
				$owned_retry_record['post_id_tokens'][ $post_id ] = $record['post_id_tokens'][ $post_id ];
			}

			return true;
		}

		ksort( $record['post_id_tokens'], SORT_NUMERIC );
		$records[ $scope_key ] = $record;
		ksort( $records, SORT_STRING );
		$ledger['records'] = $records;

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$verified_ledger  = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );
		$verified_records = $verified_ledger['records'];

		if ( ! $read_succeeded || ! isset( $verified_records[ $scope_key ] ) ) {
			return false;
		}

		if ( '' !== $record['requires_full_rebuild'] ) {
			if ( $verified_records[ $scope_key ]['requires_full_rebuild'] !== $retry_token
				|| $verified_records[ $scope_key ]['post_id_tokens'] ) {
				return false;
			}

			$owned_retry_record['requires_full_rebuild'] = $retry_token;
			return true;
		}

		foreach ( $post_ids as $post_id ) {
			$verified_retry_token = $verified_records[ $scope_key ]['post_id_tokens'][ $post_id ] ?? '';

			if ( '' === $verified_retry_token || $verified_retry_token !== $record['post_id_tokens'][ $post_id ] ) {
				return false;
			}

			$owned_retry_record['post_id_tokens'][ $post_id ] = $verified_retry_token;
		}

		return true;
	}

	return false;
}


/**
 * Removes only the exact durable retry tokens completed by this finalizer.
 *
 * @param array $site_state Origin site identity.
 * @param array $owned_retry_record Exact post tokens and optional full-rebuild token to remove.
 * @return bool Whether all exact owned tokens were absent after bounded CAS.
 */
function ai4seo_remove_third_party_seo_metadata_finalization_retry_record( array $site_state, array $owned_retry_record ): bool {
	$scope_key           = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $site_state );
	$owned_post_tokens   = isset( $owned_retry_record['post_id_tokens'] ) && is_array( $owned_retry_record['post_id_tokens'] )
		? $owned_retry_record['post_id_tokens']
		: array();
	$owned_rebuild_token = isset( $owned_retry_record['requires_full_rebuild'] ) && is_string( $owned_retry_record['requires_full_rebuild'] )
		? $owned_retry_record['requires_full_rebuild']
		: '';

	if ( '' === $scope_key || ( ! $owned_post_tokens && '' === $owned_rebuild_token ) ) {
		return false;
	}

	$option_name   = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return false;
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return false;
		}

		$records     = $ledger['records'];
		$is_envelope = $snapshot['exists']
			&& is_array( $snapshot['value'] )
			&& 1 === ( $snapshot['value']['version'] ?? null );

		$did_remove_owned_token = false;

		foreach ( $owned_post_tokens as $post_id => $retry_token ) {
			$post_id = absint( $post_id );

			if ( isset( $records[ $scope_key ]['post_id_tokens'][ $post_id ] )
				&& $records[ $scope_key ]['post_id_tokens'][ $post_id ] === $retry_token ) {
				unset( $records[ $scope_key ]['post_id_tokens'][ $post_id ] );
				$did_remove_owned_token = true;
			}
		}

		if ( '' !== $owned_rebuild_token
			&& isset( $records[ $scope_key ]['requires_full_rebuild'] )
			&& $records[ $scope_key ]['requires_full_rebuild'] === $owned_rebuild_token ) {
			$records[ $scope_key ]['requires_full_rebuild'] = '';
			$did_remove_owned_token                         = true;
		}

		if ( ! $did_remove_owned_token && $is_envelope ) {
			return true;
		}

		if ( isset( $records[ $scope_key ] )
			&& ! $records[ $scope_key ]['post_id_tokens']
			&& '' === $records[ $scope_key ]['requires_full_rebuild'] ) {
			unset( $records[ $scope_key ] );
		}

		ksort( $records, SORT_STRING );
		$ledger['records']       = $records;
		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$verified_ledger  = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );
		$verified_records = $verified_ledger['records'];

		if ( ! $read_succeeded ) {
			return false;
		}

		foreach ( $owned_post_tokens as $post_id => $retry_token ) {
			$post_id = absint( $post_id );

			if ( isset( $verified_records[ $scope_key ]['post_id_tokens'][ $post_id ] )
				&& $verified_records[ $scope_key ]['post_id_tokens'][ $post_id ] === $retry_token ) {
				return false;
			}
		}

		if ( '' !== $owned_rebuild_token
			&& isset( $verified_records[ $scope_key ]['requires_full_rebuild'] )
			&& $verified_records[ $scope_key ]['requires_full_rebuild'] === $owned_rebuild_token ) {
			return false;
		}

		return true;
	}

	return false;
}


/**
 * Selects the next deterministic non-wrapping exact-origin batch after a durable cursor.
 *
 * The following invocation wraps to the first key only after the current suffix is exhausted,
 * so retained early failures cannot consume work slots before every later key is visited.
 *
 * @param array  $records Normalized exact origin records.
 * @param string $cursor Last claimed scope key.
 * @param int    $limit Maximum selected records.
 * @return array Selected records keyed by scope.
 */
function ai4seo_select_third_party_seo_metadata_finalization_retry_record_batch(
	array $records,
	string $cursor,
	int $limit
): array {
	if ( ! $records || $limit <= 0 ) {
		return array();
	}

	ksort( $records, SORT_STRING );
	$scope_keys  = array_keys( $records );
	$start_index = 0;
	$did_find    = false;

	foreach ( $scope_keys as $index => $scope_key ) {
		if ( '' === $cursor || strcmp( $scope_key, $cursor ) > 0 ) {
			$start_index = $index;
			$did_find    = true;
			break;
		}
	}

	if ( ! $did_find ) {
		$start_index = 0;
	}

	$selected_scope_keys = array_slice( $scope_keys, $start_index, $limit );
	return array_intersect_key( $records, array_fill_keys( $selected_scope_keys, true ) );
}


/**
 * Claims one bounded exact-origin batch by advancing the durable cursor before work starts.
 *
 * Exact records remain in storage until token-conditional completion, so advancing past a failed
 * record is safe and lets a later invocation reach every other retained origin before wrapping.
 *
 * @param bool|null  $claim_succeeded Receives whether storage was valid and the cursor was claimed.
 * @param array|null $claimed_ledger Receives the normalized ledger observed after the claim.
 * @return array Claimed exact origin records keyed by scope.
 */
function ai4seo_claim_third_party_seo_metadata_finalization_retry_record_batch(
	?bool &$claim_succeeded = null,
	?array &$claimed_ledger = null
): array {
	$claim_succeeded = false;
	$claimed_ledger  = null;
	$option_name     = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit   = ai4seo_get_post_id_option_mutation_attempt_limit();
	$claim_limit     = ai4seo_get_third_party_seo_metadata_finalization_recovery_claim_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return array();
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return array();
		}

		$selected_records = ai4seo_select_third_party_seo_metadata_finalization_retry_record_batch(
			$ledger['records'],
			$ledger['cursor'],
			$claim_limit
		);
		$new_cursor       = $selected_records ? (string) array_key_last( $selected_records ) : '';
		$is_envelope      = $snapshot['exists']
			&& is_array( $snapshot['value'] )
			&& 1 === ( $snapshot['value']['version'] ?? null );

		if ( ! $snapshot['exists'] ) {
			// A verified missing row is the canonical empty state; do not recreate reset-owned storage on idle requests.
			$claim_succeeded = true;
			$claimed_ledger  = $ledger;
			return array();
		}

		if ( $is_envelope && $new_cursor === $ledger['cursor'] ) {
			$claim_succeeded = true;
			$claimed_ledger  = $ledger;
			return $selected_records;
		}

		$ledger['cursor']        = $new_cursor;
		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return array();
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$claim_succeeded = true;
		$claimed_ledger  = $ledger;
		return $selected_records;
	}

	return array();
}


/**
 * Reads one strict keyset page of active multisite blog IDs for the global fallback sweep.
 *
 * @param int       $last_blog_id Last claimed blog ID.
 * @param int       $limit Maximum returned blog IDs.
 * @param bool|null $read_succeeded Receives whether the authoritative page was valid.
 * @param bool|null $has_more Receives whether another page follows this one.
 * @return array<int,int> Strict ascending blog IDs.
 */
function ai4seo_read_third_party_seo_metadata_network_fallback_blog_id_page(
	int $last_blog_id,
	int $limit,
	?bool &$read_succeeded = null,
	?bool &$has_more = null
): array {
	global $wpdb;

	$read_succeeded = false;
	$has_more       = false;
	$last_blog_id   = max( 0, $last_blog_id );
	$limit          = max( 1, min( ai4seo_get_third_party_seo_metadata_finalization_recovery_claim_limit(), $limit ) );

	if ( ! is_multisite() ) {
		$current_blog_id = absint( get_current_blog_id() );

		if ( $current_blog_id <= 0 ) {
			return array();
		}

		$read_succeeded = true;
		return $current_blog_id > $last_blog_id ? array( $current_blog_id ) : array();
	}

	$page_limit = $limit + 1;
	$page_query = ai4seo_prepare_database_query(
		'SELECT blog_id FROM {{blogs_table}} WHERE blog_id > {{last_blog_id}} AND spam = 0 AND deleted = 0 AND archived = 0 ORDER BY blog_id ASC LIMIT {{page_limit}}',
		array(
			'blogs_table'  => ai4seo_database_identifier_binding( 'table.blogs' ),
			'last_blog_id' => ai4seo_database_scalar_binding( '%d', $last_blog_id ),
			'page_limit'   => ai4seo_database_scalar_binding( '%d', $page_limit ),
		)
	);

	if ( false === $page_query ) {
		return array();
	}

	$wpdb->last_error = '';

	// The typed compiler owns the network table and bounded keyset bindings; fallback progress cannot use caches.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$raw_blog_ids = $wpdb->get_col( $page_query );

	if ( $wpdb->last_error || ! is_array( $raw_blog_ids ) || count( $raw_blog_ids ) > $page_limit ) {
		return array();
	}

	$blog_ids         = array();
	$previous_blog_id = $last_blog_id;

	foreach ( $raw_blog_ids as $raw_blog_id ) {
		$blog_id = ai4seo_normalize_database_id( $raw_blog_id );

		if ( false === $blog_id || $blog_id <= $previous_blog_id ) {
			return array();
		}

		$blog_ids[]       = $blog_id;
		$previous_blog_id = $blog_id;
	}

	$has_more       = count( $blog_ids ) > $limit;
	$read_succeeded = true;
	return array_slice( $blog_ids, 0, $limit );
}


/**
 * Claims a network-fallback page before switching sites.
 *
 * The cursor advances with one owned restart token. A page failure leaves that token in place,
 * while a successful page may clear only the exact safety token it created.
 *
 * @param string $active_token Active global generation.
 * @param int    $expected_last_blog_id Cursor used to read the page.
 * @param int    $claimed_last_blog_id Last blog ID in the selected page.
 * @param string $owned_safety_token Receives the page-owned restart token, when one was created.
 * @return bool Whether the exact active cursor was claimed.
 */
function ai4seo_claim_third_party_seo_metadata_network_fallback_page(
	string $active_token,
	int $expected_last_blog_id,
	int $claimed_last_blog_id,
	string &$owned_safety_token
): bool {
	$owned_safety_token = '';

	if ( ! ai4seo_is_valid_third_party_seo_metadata_finalization_retry_token( $active_token )
		|| $expected_last_blog_id < 0
		|| $claimed_last_blog_id <= $expected_last_blog_id
	) {
		return false;
	}

	$option_name   = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return false;
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return false;
		}

		$network_fallback = $ledger['network_fallback'];

		if ( $active_token !== $network_fallback['active_token']
			|| $expected_last_blog_id !== $network_fallback['last_blog_id']
		) {
			return false;
		}

		$page_safety_token = '';

		if ( '' === $network_fallback['restart_token'] ) {
			$page_safety_token                 = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'ai4seo_', true );
			$network_fallback['restart_token'] = $page_safety_token;
		}

		$network_fallback['last_blog_id'] = $claimed_last_blog_id;
		$ledger['network_fallback']       = $network_fallback;
		$compare_and_swap_result          = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( ! $compare_and_swap_result ) {
			continue;
		}

		$owned_safety_token = $page_safety_token;
		return true;
	}

	return false;
}


/**
 * Commits one claimed network page without clearing any concurrent fallback generation.
 *
 * @param string $active_token Generation whose page was processed.
 * @param int    $claimed_last_blog_id Claimed cursor.
 * @param string $owned_safety_token Exact page-owned restart token, or empty when pre-existing.
 * @param bool   $page_succeeded Whether every site switch, identity, schedule, and restore succeeded.
 * @param bool   $is_complete_page Whether no later blog ID existed in the keyset snapshot.
 * @return bool Whether progress was checked and committed safely.
 */
function ai4seo_complete_third_party_seo_metadata_network_fallback_page(
	string $active_token,
	int $claimed_last_blog_id,
	string $owned_safety_token,
	bool $page_succeeded,
	bool $is_complete_page
): bool {
	$option_name   = ai4seo_get_third_party_seo_metadata_finalization_retry_option_name();
	$attempt_limit = ai4seo_get_post_id_option_mutation_attempt_limit();

	for ( $attempt = 0; $attempt < $attempt_limit; ++$attempt ) {
		$snapshot = ai4seo_get_raw_option_snapshot( $option_name );

		if ( null === $snapshot ) {
			return false;
		}

		$ledger = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger(
			$snapshot['exists'] ? $snapshot['value'] : null
		);

		if ( null === $ledger ) {
			return false;
		}

		$network_fallback          = $ledger['network_fallback'];
		$original_network_fallback = $network_fallback;

		if ( $active_token !== $network_fallback['active_token'] ) {
			// Another checked finalizer already completed or promoted this generation.
			return true;
		}

		if ( $claimed_last_blog_id !== $network_fallback['last_blog_id'] ) {
			// Another page advanced the same generation; its later cursor owns completion.
			return true;
		}

		if ( $page_succeeded
			&& '' !== $owned_safety_token
			&& $owned_safety_token === $network_fallback['restart_token']
		) {
			$network_fallback['restart_token'] = '';
		}

		if ( ! $page_succeeded && '' === $network_fallback['restart_token'] ) {
			$network_fallback['restart_token'] = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'ai4seo_', true );
		}

		if ( $is_complete_page ) {
			if ( '' !== $network_fallback['restart_token'] ) {
				$network_fallback['active_token']  = $network_fallback['restart_token'];
				$network_fallback['restart_token'] = '';
				$network_fallback['last_blog_id']  = 0;
			} else {
				$network_fallback = array(
					'active_token'  => '',
					'restart_token' => '',
					'last_blog_id'  => 0,
				);
			}
		}

		$ledger['network_fallback'] = $network_fallback;

		if ( $network_fallback === $original_network_fallback ) {
			// The page claim already durably retained this failure/restart state.
			return true;
		}

		$compare_and_swap_result = ai4seo_compare_and_swap_option_snapshot(
			$option_name,
			$snapshot,
			$ledger,
			false
		);

		if ( null === $compare_and_swap_result ) {
			return false;
		}

		if ( $compare_and_swap_result ) {
			return true;
		}
	}

	return false;
}


/**
 * Processes one bounded keyset page of the all-sites full-rebuild fallback.
 *
 * @param array|null $observed_ledger Optional normalized ledger observed by the exact-record claim.
 * @throws RuntimeException When one target site cannot be entered or verified.
 * @return bool Whether the page was absent or its durable progress was committed safely.
 */
function ai4seo_process_third_party_seo_metadata_network_fallback_page( ?array $observed_ledger = null ): bool {
	$dispatcher_context = ai4seo_get_third_party_seo_metadata_finalization_site_context();

	if ( null === $observed_ledger ) {
		$ledger = ai4seo_read_third_party_seo_metadata_finalization_retry_ledger( $read_succeeded );
	} else {
		$ledger         = ai4seo_normalize_third_party_seo_metadata_finalization_retry_ledger( $observed_ledger );
		$read_succeeded = null !== $ledger;
	}

	if ( ! $read_succeeded ) {
		return false;
	}

	$network_fallback = $ledger['network_fallback'];
	$active_token     = $network_fallback['active_token'];

	if ( '' === $active_token ) {
		return true;
	}

	$last_blog_id = $network_fallback['last_blog_id'];
	$blog_ids     = ai4seo_read_third_party_seo_metadata_network_fallback_blog_id_page(
		$last_blog_id,
		ai4seo_get_third_party_seo_metadata_finalization_recovery_claim_limit(),
		$page_read_succeeded,
		$has_more
	);

	if ( ! $page_read_succeeded ) {
		// A fresh pending generation distinguishes this failed pass from a later failure-free sweep.
		if ( ! ai4seo_require_third_party_seo_metadata_network_fallback( $failure_token ) ) {
			ai4seo_debug_message( 418620960, 'Could not retain the all-sites third-party finalization fallback after its keyset read failed.', true );
		}

		return false;
	}

	if ( ! $blog_ids ) {
		return ai4seo_complete_third_party_seo_metadata_network_fallback_page(
			$active_token,
			$last_blog_id,
			'',
			true,
			true
		);
	}

	$claimed_last_blog_id = (int) end( $blog_ids );
	$owned_safety_token   = '';

	if ( ! ai4seo_claim_third_party_seo_metadata_network_fallback_page(
		$active_token,
		$last_blog_id,
		$claimed_last_blog_id,
		$owned_safety_token
	) ) {
		return false;
	}

	$page_succeeded = true;

	foreach ( $blog_ids as $blog_id ) {
		$switched_site  = false;
		$site_succeeded = false;

		try {
			if ( absint( get_current_blog_id() ) !== $blog_id ) {
				if ( ! is_multisite() || ! function_exists( 'switch_to_blog' ) || ! function_exists( 'restore_current_blog' ) ) {
					throw new RuntimeException( 'The target site cannot be entered from this request.' );
				}

				$switched_site = true === switch_to_blog( $blog_id );

				if ( ! $switched_site ) {
					throw new RuntimeException( 'The target site switch did not complete.' );
				}
			}

			$origin_context = ai4seo_get_third_party_seo_metadata_finalization_site_context();

			if ( $blog_id !== $origin_context['blog_id']
				|| '' === $origin_context['options_table']
				|| '' === $origin_context['postmeta_table']
			) {
				throw new RuntimeException( 'The target site identity could not be verified.' );
			}

			$site_succeeded = ai4seo_schedule_generation_status_summary_rebuild();
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620961, 'Could not schedule an all-sites third-party finalization fallback: ' . $throwable->getMessage(), true );
		} finally {
			if ( $switched_site ) {
				try {
					restore_current_blog();
				} catch ( Throwable $throwable ) {
					ai4seo_debug_message( 418620962, 'Could not restore the dispatcher after an all-sites third-party finalization fallback: ' . $throwable->getMessage(), true );
				}
			}

			$restore_succeeded = ai4seo_restore_third_party_seo_metadata_finalization_site_context( $dispatcher_context );
		}

		if ( ! $restore_succeeded ) {
			// The page claim already advanced with a retained safety token before the ambiguous switch.
			ai4seo_debug_message( 418620963, 'The all-sites third-party finalization fallback stopped after an ambiguous site restoration.', true );
			return false;
		}

		if ( ! $site_succeeded ) {
			$page_succeeded = false;
		}
	}

	return ai4seo_complete_third_party_seo_metadata_network_fallback_page(
		$active_token,
		$claimed_last_blog_id,
		$owned_safety_token,
		$page_succeeded,
		! $has_more
	);
}


/**
 * Requeues failed third-party synchronization finalization work for its originating site.
 *
 * @param array $site_state The consumed origin-site state.
 * @param array $post_ids Post IDs whose derived state still needs to be finalized.
 * @param bool  $requires_full_rebuild Whether an origin-wide authoritative rebuild still needs scheduling.
 * @return void
 */
function ai4seo_requeue_third_party_seo_metadata_sync_site_state(
	array $site_state,
	array $post_ids,
	bool $requires_full_rebuild = false
): void {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	if ( ! $post_ids && ! $requires_full_rebuild ) {
		return;
	}

	// Requeue only unfinished IDs; completed IDs must stay consumed when one sibling fails.
	$site_state['changed_post_ids']      = array_fill_keys( $post_ids, true );
	$site_state['requires_full_rebuild'] = $requires_full_rebuild;
	ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $site_state );
}


/**
 * Durably owns one bounded finalization batch before any selected origin can change site context.
 *
 * Advancing ownership distinguishes a semantic new arrival from a previously claimed generation,
 * so cleanup by an older concurrent finalizer cannot consume the newer work through token reuse.
 *
 * @param array $site_states Selected origin states keyed by scope.
 * @param array $retry_storage_context Verified dispatcher storage context.
 * @param array $owned_retry_records_by_scope Durable ownership accumulated by this finalizer.
 * @param bool  $advance_existing_ownership Whether every selected ID/full marker is a new generation.
 * @return bool Whether every selected unit of work has exact or global durable ownership.
 */
function ai4seo_preflight_third_party_seo_metadata_finalization_retry_ownership(
	array &$site_states,
	array $retry_storage_context,
	array &$owned_retry_records_by_scope,
	bool $advance_existing_ownership
): bool {
	if ( count( $site_states ) > ai4seo_get_third_party_seo_metadata_finalization_scope_work_limit() ) {
		return false;
	}

	foreach ( $site_states as &$site_state ) {
		if ( ! is_array( $site_state )
			|| ! ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context )
		) {
			return false;
		}

		$scope_key = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $site_state );

		if ( '' === $scope_key ) {
			return false;
		}

		$changed_post_ids   = isset( $site_state['changed_post_ids'] ) && is_array( $site_state['changed_post_ids'] )
			? array_keys( $site_state['changed_post_ids'] )
			: array();
		$retryable_post_ids = array();

		foreach ( $changed_post_ids as $changed_post_id ) {
			$changed_post_id = absint( $changed_post_id );

			if ( $changed_post_id > 0 && ! isset( $site_state['deleting_post_ids'][ $changed_post_id ] ) ) {
				$retryable_post_ids[] = $changed_post_id;
			}
		}

		$retryable_post_ids    = array_values( array_unique( $retryable_post_ids ) );
		$requires_full_rebuild = ! empty( $site_state['requires_full_rebuild'] );

		if ( ! $retryable_post_ids && ! $requires_full_rebuild ) {
			continue;
		}

		$owned_retry_record       = $owned_retry_records_by_scope[ $scope_key ] ?? array(
			'post_id_tokens'        => array(),
			'requires_full_rebuild' => '',
			'network_full_rebuild'  => '',
		);
		$owned_post_tokens        = isset( $owned_retry_record['post_id_tokens'] ) && is_array( $owned_retry_record['post_id_tokens'] )
			? $owned_retry_record['post_id_tokens']
			: array();
		$owned_full_rebuild_token = isset( $owned_retry_record['requires_full_rebuild'] )
			&& is_string( $owned_retry_record['requires_full_rebuild'] )
			? $owned_retry_record['requires_full_rebuild']
			: '';

		if ( ! $advance_existing_ownership && '' !== $owned_full_rebuild_token ) {
			$site_state['requires_full_rebuild'] = true;
			continue;
		}

		$post_ids_requiring_ownership      = $advance_existing_ownership
			? $retryable_post_ids
			: array_values( array_diff( $retryable_post_ids, array_map( 'absint', array_keys( $owned_post_tokens ) ) ) );
		$requires_owned_full_rebuild_token = $requires_full_rebuild
			&& ( $advance_existing_ownership || '' === $owned_full_rebuild_token );

		if ( ! $post_ids_requiring_ownership && ! $requires_owned_full_rebuild_token ) {
			continue;
		}

		$new_owned_retry_record = array();

		if ( ! ai4seo_add_third_party_seo_metadata_finalization_retry_record(
			$site_state,
			$post_ids_requiring_ownership,
			$new_owned_retry_record,
			$advance_existing_ownership
		) || ! ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context ) ) {
			return false;
		}

		if ( '' !== $new_owned_retry_record['requires_full_rebuild'] ) {
			$owned_retry_record                  = $new_owned_retry_record;
			$site_state['requires_full_rebuild'] = true;
		} elseif ( '' !== $new_owned_retry_record['network_full_rebuild'] ) {
			$owned_retry_record['network_full_rebuild'] = $new_owned_retry_record['network_full_rebuild'];
		} else {
			$owned_retry_record['post_id_tokens'] = array_replace(
				$owned_post_tokens,
				$new_owned_retry_record['post_id_tokens']
			);
		}

		$owned_retry_records_by_scope[ $scope_key ] = $owned_retry_record;
	}

	unset( $site_state );

	return true;
}


/**
 * Captures the exact WordPress site-storage and multisite-switch context.
 *
 * @return array{blog_id: int, options_table: string, postmeta_table: string, switch_stack: array|null}
 */
function ai4seo_get_third_party_seo_metadata_finalization_site_context(): array {
	global $wpdb;

	$switch_stack = isset( $GLOBALS['_wp_switched_stack'] ) ? $GLOBALS['_wp_switched_stack'] : array();

	return array(
		'blog_id'        => absint( get_current_blog_id() ),
		'options_table'  => isset( $wpdb->options ) ? (string) $wpdb->options : '',
		'postmeta_table' => isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '',
		'switch_stack'   => is_array( $switch_stack ) ? $switch_stack : null,
	);
}


/**
 * Verifies the current WordPress site-storage and multisite-switch context.
 *
 * @param array $expected_context Previously captured context.
 * @return bool Whether the exact identity and switch stack are current.
 */
function ai4seo_is_third_party_seo_metadata_finalization_site_context_current( array $expected_context ): bool {
	if ( empty( $expected_context['blog_id'] )
		|| empty( $expected_context['options_table'] )
		|| empty( $expected_context['postmeta_table'] )
		|| ! isset( $expected_context['switch_stack'] )
		|| ! is_array( $expected_context['switch_stack'] )
	) {
		return false;
	}

	return ai4seo_get_third_party_seo_metadata_finalization_site_context() === $expected_context;
}


/**
 * Unwinds only switch frames added after a verified WordPress site context.
 *
 * The exact stack-prefix check prevents an ambiguous callback from popping a caller-owned frame.
 * Four attempts bound shutdown work while covering the expected single switch and limited nested
 * switches initiated by third-party callbacks.
 *
 * @param array $expected_context Previously captured context to restore.
 * @return bool Whether the exact identity and switch stack were restored.
 */
function ai4seo_restore_third_party_seo_metadata_finalization_site_context( array $expected_context ): bool {
	if ( ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $expected_context ) ) {
		return true;
	}

	$expected_switch_stack = $expected_context['switch_stack'] ?? null;

	if ( ! is_array( $expected_switch_stack ) || ! function_exists( 'restore_current_blog' ) ) {
		return false;
	}

	$expected_stack_depth  = count( $expected_switch_stack );
	$restore_attempt_limit = 4;

	for ( $restore_attempt = 0; $restore_attempt < $restore_attempt_limit; ++$restore_attempt ) {
		$current_context = ai4seo_get_third_party_seo_metadata_finalization_site_context();

		if ( ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $expected_context ) ) {
			return true;
		}

		$current_switch_stack = $current_context['switch_stack'];

		if ( ! is_array( $current_switch_stack )
			|| count( $current_switch_stack ) <= $expected_stack_depth
			|| array_slice( $current_switch_stack, 0, $expected_stack_depth ) !== $expected_switch_stack
		) {
			return false;
		}

		try {
			restore_current_blog();
		} catch ( Throwable $throwable ) {
			ai4seo_debug_message( 418620953, 'Could not unwind a partial third-party metadata finalization site switch: ' . $throwable->getMessage(), true );
		}
	}

	return ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $expected_context );
}


/**
 * Refreshes derived state once for every post changed by persisted third-party synchronization.
 *
 * @return void
 */
function ai4seo_finalize_third_party_seo_metadata_sync(): void {
	global $wpdb;

	$retry_storage_context = ai4seo_get_third_party_seo_metadata_finalization_site_context();
	$durable_retry_records = array();
	$claimed_retry_ledger  = null;

	// Claim before consuming request-local work, with one bounded retry for transient CAS contention.
	for ( $claim_pass = 1; $claim_pass <= 2; ++$claim_pass ) {
		$durable_retry_records = ai4seo_claim_third_party_seo_metadata_finalization_retry_record_batch(
			$retry_records_claim_succeeded,
			$claimed_retry_ledger
		);

		if ( $retry_records_claim_succeeded ) {
			break;
		}
	}

	if ( ! $retry_records_claim_succeeded ) {
		ai4seo_debug_message( 418620949, 'Could not claim the bounded durable third-party metadata finalization retry batch.', true );
		return;
	}

	if ( ! ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context ) ) {
		// Unexpected switch-stack or table identity must fail before request-local work is consumed.
		ai4seo_debug_message( 418620967, 'Third-party metadata finalization could not verify its retry-storage context before consuming request work.', true );
		return;
	}

	$incoming_request_state             = ai4seo_manage_third_party_seo_metadata_sync_request_state( 'consume-changed' );
	$incoming_site_states               = isset( $incoming_request_state['site_states'] ) && is_array( $incoming_request_state['site_states'] )
		? $incoming_request_state['site_states']
		: array();
	$incoming_requires_network_fallback = ! empty( $incoming_request_state['requires_network_fallback'] );

	$owned_retry_records_by_scope  = array();
	$selected_scope_keys           = array();
	$selected_incoming_site_states = array();
	$work_limit                    = ai4seo_get_third_party_seo_metadata_finalization_scope_work_limit();

	foreach ( $durable_retry_records as $scope_key => $durable_retry_record ) {
		$durable_retry_site_state = array(
			'blog_id'               => $durable_retry_record['blog_id'],
			'options_table'         => $durable_retry_record['options_table'],
			'postmeta_table'        => $durable_retry_record['postmeta_table'],
			'changed_post_ids'      => array_fill_keys( array_keys( $durable_retry_record['post_id_tokens'] ), true ),
			'requires_full_rebuild' => '' !== $durable_retry_record['requires_full_rebuild'],
		);

		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $durable_retry_site_state );
		$selected_scope_keys[ $scope_key ]          = true;
		$owned_retry_records_by_scope[ $scope_key ] = array(
			'post_id_tokens'        => $durable_retry_record['post_id_tokens'],
			'requires_full_rebuild' => $durable_retry_record['requires_full_rebuild'],
		);
	}

	$normalized_incoming_site_states = array();
	$has_unselected_incoming_scope   = $incoming_requires_network_fallback;

	foreach ( $incoming_site_states as $incoming_site_state ) {
		if ( ! is_array( $incoming_site_state ) ) {
			$has_unselected_incoming_scope = true;
			continue;
		}

		$scope_key = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $incoming_site_state );

		if ( '' === $scope_key ) {
			$has_unselected_incoming_scope = true;
			continue;
		}

		$normalized_incoming_site_states[ $scope_key ] = $incoming_site_state;
	}

	ksort( $normalized_incoming_site_states, SORT_STRING );

	foreach ( $normalized_incoming_site_states as $scope_key => $incoming_site_state ) {
		if ( isset( $selected_scope_keys[ $scope_key ] ) ) {
			$selected_incoming_site_states[ $scope_key ] = $incoming_site_state;
			continue;
		}

		if ( count( $selected_scope_keys ) < $work_limit ) {
			$selected_scope_keys[ $scope_key ]           = true;
			$selected_incoming_site_states[ $scope_key ] = $incoming_site_state;
			continue;
		}

		$has_unselected_incoming_scope = true;
	}

	foreach ( $selected_incoming_site_states as $incoming_site_state ) {
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $incoming_site_state );
	}

	if ( $has_unselected_incoming_scope
		&& ! ai4seo_require_third_party_seo_metadata_network_fallback( $overflow_fallback_token )
	) {
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-network-fallback' );
		ai4seo_debug_message( 418620964, 'Could not persist the bounded all-sites fallback for excess third-party finalization origins.', true );
		return;
	}

	if ( ! ai4seo_preflight_third_party_seo_metadata_finalization_retry_ownership(
		$selected_incoming_site_states,
		$retry_storage_context,
		$owned_retry_records_by_scope,
		true
	) ) {
		foreach ( $selected_incoming_site_states as $selected_incoming_site_state ) {
			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $selected_incoming_site_state );
		}

		$fallback_was_persisted = ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context )
			&& ai4seo_require_third_party_seo_metadata_network_fallback( $preflight_fallback_token )
			&& ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context );

		if ( ! $fallback_was_persisted ) {
			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-network-fallback' );
		}

		ai4seo_debug_message( 418620968, 'Could not preflight durable ownership for every selected third-party metadata finalization origin.', true );
		return;
	}

	// Requeue the preflight-normalized states so an exact overflow promoted to full is processed as its durable superset.
	foreach ( $selected_incoming_site_states as $selected_incoming_site_state ) {
		ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $selected_incoming_site_state );
	}

	if ( ! ai4seo_process_third_party_seo_metadata_network_fallback_page( $claimed_retry_ledger ) ) {
		ai4seo_debug_message( 418620965, 'The bounded all-sites third-party metadata finalization fallback remains pending.', true );
	}

	// One immediate retry absorbs transient table, switch, and coverage failures before shutdown returns.
	for ( $finalization_pass = 1; $finalization_pass <= 2; ++$finalization_pass ) {
		if ( ! ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context ) ) {
			ai4seo_debug_message( 418620954, 'Third-party metadata finalization stopped outside its verified retry-storage site context.', true );
			return;
		}

		$request_state = ai4seo_manage_third_party_seo_metadata_sync_request_state( 'consume-changed' );
		$site_states   = isset( $request_state['site_states'] ) && is_array( $request_state['site_states'] )
			? $request_state['site_states']
			: array();

		$selected_pass_site_states = array();

		$pass_requires_network_fallback = ! empty( $request_state['requires_network_fallback'] );

		foreach ( $site_states as $site_state ) {
			if ( ! is_array( $site_state ) ) {
				$pass_requires_network_fallback = true;
				continue;
			}

			$scope_key = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $site_state );

			if ( '' === $scope_key || ! isset( $selected_scope_keys[ $scope_key ] ) ) {
				$pass_requires_network_fallback = true;
				continue;
			}

			$selected_pass_site_states[ $scope_key ] = $site_state;
		}

		if ( $pass_requires_network_fallback
			&& ! ai4seo_require_third_party_seo_metadata_network_fallback( $pass_fallback_token )
		) {
			foreach ( $selected_pass_site_states as $selected_pass_site_state ) {
				ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $selected_pass_site_state );
			}

			ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-network-fallback' );
			ai4seo_debug_message( 418620966, 'Could not retain the bounded all-sites fallback for third-party finalization work arriving during a finalization pass.', true );
			return;
		}

		$site_states = $selected_pass_site_states;

		if ( ! $site_states ) {
			break;
		}

		if ( ! ai4seo_preflight_third_party_seo_metadata_finalization_retry_ownership(
			$site_states,
			$retry_storage_context,
			$owned_retry_records_by_scope,
			false
		) ) {
			foreach ( $site_states as $selected_pass_site_state ) {
				ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-site-state', 0, $selected_pass_site_state );
			}

			$fallback_was_persisted = ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context )
				&& ai4seo_require_third_party_seo_metadata_network_fallback( $pass_preflight_fallback_token )
				&& ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context );

			if ( ! $fallback_was_persisted ) {
				ai4seo_manage_third_party_seo_metadata_sync_request_state( 'requeue-network-fallback' );
			}

			ai4seo_debug_message( 418620969, 'Could not verify preflight ownership for the selected third-party metadata finalization pass.', true );
			return;
		}

		// Every queued ID must be interpreted against the exact tables that owned the originating hook.
		foreach ( $site_states as $site_state ) {
			if ( ! is_array( $site_state ) ) {
				continue;
			}

			$origin_blog_id        = absint( $site_state['blog_id'] ?? 0 );
			$origin_options_table  = isset( $site_state['options_table'] ) ? (string) $site_state['options_table'] : '';
			$origin_postmeta_table = isset( $site_state['postmeta_table'] ) ? (string) $site_state['postmeta_table'] : '';
			$changed_post_ids      = isset( $site_state['changed_post_ids'] ) && is_array( $site_state['changed_post_ids'] )
				? array_keys( $site_state['changed_post_ids'] )
				: array();
			$requires_full_rebuild = ! empty( $site_state['requires_full_rebuild'] );

			if ( $origin_blog_id <= 0 || '' === $origin_options_table || '' === $origin_postmeta_table
				|| ( ! $changed_post_ids && ! $requires_full_rebuild ) ) {
				continue;
			}

			$retryable_post_ids = array();

			foreach ( $changed_post_ids as $changed_post_id ) {
				$changed_post_id = absint( $changed_post_id );

				if ( $changed_post_id > 0 && ! isset( $site_state['deleting_post_ids'][ $changed_post_id ] ) ) {
					$retryable_post_ids[] = $changed_post_id;
				}
			}

			$retryable_post_ids = array_values( array_unique( $retryable_post_ids ) );

			if ( ! $retryable_post_ids && ! $requires_full_rebuild ) {
				continue;
			}

			$scope_key          = ai4seo_get_third_party_seo_metadata_finalization_retry_scope_key( $site_state );
			$owned_retry_record = $owned_retry_records_by_scope[ $scope_key ] ?? array(
				'post_id_tokens'        => array(),
				'requires_full_rebuild' => '',
			);

			$previous_site_context = ai4seo_get_third_party_seo_metadata_finalization_site_context();
			$is_origin_site        = $origin_blog_id === $previous_site_context['blog_id']
				&& $origin_options_table === $previous_site_context['options_table']
				&& $origin_postmeta_table === $previous_site_context['postmeta_table'];
			$switched_site         = false;

			if ( ! $is_origin_site ) {
				if ( ! is_multisite() || ! function_exists( 'switch_to_blog' ) || ! function_exists( 'restore_current_blog' ) ) {
					ai4seo_debug_message( 418620938, 'Could not enter the originating site for third-party metadata synchronization finalization.', true );
					ai4seo_requeue_third_party_seo_metadata_sync_site_state( $site_state, $retryable_post_ids, $requires_full_rebuild );

					if ( 2 === $finalization_pass ) {
						ai4seo_debug_message( 418620947, 'Third-party metadata synchronization remained outside its originating site after the bounded shutdown retry.', true );
					}

					continue;
				}

				try {
					$switched_site = true === switch_to_blog( $origin_blog_id );
				} catch ( Throwable $throwable ) {
					ai4seo_debug_message( 418620943, 'Could not switch to the originating site for third-party metadata synchronization finalization: ' . $throwable->getMessage(), true );
					ai4seo_requeue_third_party_seo_metadata_sync_site_state( $site_state, $retryable_post_ids, $requires_full_rebuild );

					if ( ! ai4seo_restore_third_party_seo_metadata_finalization_site_context( $previous_site_context ) ) {
						ai4seo_debug_message( 418620955, 'Third-party metadata finalization stopped after an ambiguous originating-site switch failure.', true );
						return;
					}

					continue;
				}

				if ( ! $switched_site ) {
					ai4seo_debug_message( 418620939, 'Could not switch to the originating site for third-party metadata synchronization finalization.', true );
					ai4seo_requeue_third_party_seo_metadata_sync_site_state( $site_state, $retryable_post_ids, $requires_full_rebuild );

					if ( ! ai4seo_restore_third_party_seo_metadata_finalization_site_context( $previous_site_context ) ) {
						ai4seo_debug_message( 418620955, 'Third-party metadata finalization stopped after an ambiguous originating-site switch result.', true );
						return;
					}

					continue;
				}
			}

			$failed_post_ids            = array();
			$coverage_failed            = false;
			$full_rebuild_was_scheduled = ! $requires_full_rebuild;
			$restore_was_verified       = true;

			try {
				$is_verified_origin_site = absint( get_current_blog_id() ) === $origin_blog_id
					&& ( isset( $wpdb->options ) ? (string) $wpdb->options : '' ) === $origin_options_table
					&& ( isset( $wpdb->postmeta ) ? (string) $wpdb->postmeta : '' ) === $origin_postmeta_table;

				if ( ! $is_verified_origin_site ) {
					ai4seo_debug_message( 418620940, 'The originating site storage could not be verified before third-party metadata synchronization finalization.', true );
					$failed_post_ids = $retryable_post_ids;
				} else {
					foreach ( $retryable_post_ids as $this_post_id ) {
						$coverage_was_refreshed = false;

						try {
							$coverage_was_refreshed = ai4seo_refresh_one_posts_metadata_coverage_status( $this_post_id );
						} catch ( Throwable $throwable ) {
							ai4seo_debug_message( 418620944, 'Could not refresh metadata coverage after third-party metadata synchronization: ' . $throwable->getMessage(), true );
						}

						if ( ! $coverage_was_refreshed ) {
							$failed_post_ids[] = $this_post_id;
							$coverage_failed   = true;
							ai4seo_debug_message( 418620941, 'Could not refresh metadata coverage after third-party metadata synchronization.', true );
						}

						try {
							ai4seo_purge_frontend_cache_for_post( $this_post_id );
						} catch ( Throwable $throwable ) {
							// Cache integrations are best-effort and must not break request shutdown.
							ai4seo_debug_message( 418620937, 'Could not purge the frontend cache after third-party metadata synchronization: ' . $throwable->getMessage(), true );
						}
					}

					$must_schedule_full_rebuild = $requires_full_rebuild || ( 2 === $finalization_pass && $coverage_failed );

					if ( $must_schedule_full_rebuild ) {
						$rebuild_was_scheduled = false;

						try {
							$rebuild_was_scheduled = ai4seo_schedule_generation_status_summary_rebuild();
						} catch ( Throwable $throwable ) {
							ai4seo_debug_message( 418620952, 'Could not schedule the authoritative third-party metadata finalization rebuild: ' . $throwable->getMessage(), true );
						}

						if ( ! $rebuild_was_scheduled ) {
							ai4seo_debug_message( 418620948, 'Could not verify durable generation-summary rebuild scheduling after third-party metadata finalization failure.', true );
						}

						if ( $requires_full_rebuild ) {
							$full_rebuild_was_scheduled = $rebuild_was_scheduled;
						}
					}
				}
			} finally {
				if ( $switched_site ) {
					try {
						restore_current_blog();
					} catch ( Throwable $throwable ) {
						ai4seo_debug_message( 418620953, 'Could not restore the prior site after third-party metadata synchronization finalization: ' . $throwable->getMessage(), true );
					}
				}

				$restore_was_verified = ai4seo_restore_third_party_seo_metadata_finalization_site_context( $previous_site_context );

				if ( ! $restore_was_verified ) {
					ai4seo_debug_message( 418620942, 'Could not verify restoration of the prior site after third-party metadata synchronization finalization.', true );
				}
			}

			// Failed restoration makes every operation's site ownership ambiguous, so retry the full origin batch.
			if ( ! $restore_was_verified ) {
				$failed_post_ids = $retryable_post_ids;
			}

			$full_rebuild_retry_required = $requires_full_rebuild
				&& ( ! $full_rebuild_was_scheduled || ! $restore_was_verified );

			if ( $requires_full_rebuild && $full_rebuild_was_scheduled && $restore_was_verified ) {
				// The verified full analysis is the durable superset repair for any direct per-post refresh failure.
				$failed_post_ids = array();
			}

			ai4seo_requeue_third_party_seo_metadata_sync_site_state(
				$site_state,
				$failed_post_ids,
				$full_rebuild_retry_required
			);

			if ( ! $restore_was_verified ) {
				// Every selected origin was preowned from the dispatcher; stop before using an ambiguous switch stack.
				return;
			}

			$completed_post_ids     = array_values( array_diff( $retryable_post_ids, $failed_post_ids ) );
			$completed_token_lookup = array_fill_keys( $completed_post_ids, true );
			$completed_retry_record = array(
				'post_id_tokens'        => array_intersect_key( $owned_retry_record['post_id_tokens'], $completed_token_lookup ),
				'requires_full_rebuild' => $full_rebuild_retry_required
					? ''
					: $owned_retry_record['requires_full_rebuild'],
			);
			$is_retry_storage_site  = ai4seo_is_third_party_seo_metadata_finalization_site_context_current( $retry_storage_context );

			if ( $completed_retry_record['post_id_tokens'] || '' !== $completed_retry_record['requires_full_rebuild'] ) {
				if ( ! $is_retry_storage_site
					|| ! ai4seo_remove_third_party_seo_metadata_finalization_retry_record( $site_state, $completed_retry_record ) ) {
					// Leaving exact retry tokens is safer than acknowledging cleanup that could not be verified.
					ai4seo_debug_message( 418620951, 'Could not clear completed third-party metadata finalization retry ownership.', true );
				} else {
					foreach ( array_keys( $completed_retry_record['post_id_tokens'] ) as $completed_post_id ) {
						unset( $owned_retry_records_by_scope[ $scope_key ]['post_id_tokens'][ $completed_post_id ] );
					}

					if ( '' !== $completed_retry_record['requires_full_rebuild'] ) {
						$owned_retry_records_by_scope[ $scope_key ]['requires_full_rebuild'] = '';
					}
				}
			}
		}
	}
}


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


/**
 * Resolves SEOPress template variables in metadata used outside its own renderer.
 *
 * @param string $metadata_value The raw metadata value.
 * @param int    $post_id The post id used as SEOPress replacement context.
 * @param string $metadata_identifier The SOOZ metadata identifier.
 * @return string The resolved metadata value, or the original value when resolution is unavailable.
 * @noinspection PhpUndefinedNamespaceInspection
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

        /** @noinspection PhpUndefinedClassInspection */
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

		$classification                        = $is_registered ? 'registered' : 'unregistered';
		$variable_matches[ $classification ][] = $full_token;
	}

	$variable_matches['registered']   = array_values( array_unique( $variable_matches['registered'] ) );
	$variable_matches['unregistered'] = array_values( array_unique( $variable_matches['unregistered'] ) );

	return $variable_matches;
}


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

			$metadata_value                     = str_replace( $unregistered_token, $placeholder, $metadata_value );
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


/**
 * Prepares the shared exact-key postmeta value query used by provider readers.
 *
 * @param array  $post_ids Normalized post IDs for one bounded query chunk.
 * @param string $meta_key Provider-owned postmeta key.
 * @return string|false Prepared query, or false when the typed specification is invalid.
 */
function ai4seo_prepare_third_party_postmeta_value_rows_query( array $post_ids, string $meta_key ) {
	return ai4seo_prepare_database_query(
		'SELECT post_id, meta_value FROM {{postmeta_table}} WHERE meta_key = {{meta_key}} AND post_id IN ({{post_ids}})',
		array(
			'postmeta_table' => ai4seo_database_identifier_binding( 'table.postmeta' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Each exact provider key is paired with one bounded post-ID chunk.
			'meta_key'       => ai4seo_database_scalar_binding( '%s', $meta_key ),
			'post_ids'       => ai4seo_database_list_binding( '%d', array_values( $post_ids ) ),
		)
	);
}


/**
 * Returns keyphrases for post IDs using active third-party SEO provider precedence.
 *
 * @param array $post_ids Post IDs.
 * @return array|null Keyphrases by post ID, or null on error.
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

	$sanitized_post_ids = array_values( array_filter( array_map( 'intval', $post_ids ) ) );

	if ( empty( $sanitized_post_ids ) ) {
		return array();
	}

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size       = ai4seo_get_database_chunk_size();
	$sanitized_post_ids_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	// Provider order defines fallback precedence, so retain the active integration registry order.
	$active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

	if ( ! $active_supported_third_party_seo_plugins ) {
		return array();
	}

	// Fill each post once so the first active provider with a non-empty keyphrase wins.
	$key_phrases = array();

	foreach ( $active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details ) {
		if ( empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys'] )
			|| empty( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] ) ) {
			continue;
		}

		// Stop consulting lower-priority providers after every submitted post has a result.
		if ( count( $key_phrases ) === count( $post_ids ) ) {
			break;
		}

		$this_keyphrase_postmeta_key = sanitize_text_field( $this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'] );

		foreach ( $sanitized_post_ids_chunks as $this_post_ids_chunk ) {
			if ( empty( $this_post_ids_chunk ) ) {
				continue;
			}

			// Reuse the provider postmeta query contract so keyphrase and score readers bind chunks identically.
			$this_query = ai4seo_prepare_third_party_postmeta_value_rows_query( $this_post_ids_chunk, $this_keyphrase_postmeta_key );

			if ( false === $this_query ) {
				return array();
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the closed postmeta/key/ID spec; external SEO metadata needs a current batch read because provider writes have no reliable shared invalidation hook.
			$this_postmeta_entries = $wpdb->get_results( $this_query, ARRAY_A );

			// Abort the complete provider fallback result rather than returning a partially filled map.
			if ( $wpdb->last_error ) {
				ai4seo_debug_message( 984321654, 'Database error: ' . $wpdb->last_error, true );
				return array();
			}

			if ( ! $this_postmeta_entries ) {
				continue;
			}

			// Resolve provider-specific compound formats before testing fallback precedence.
			foreach ( $this_postmeta_entries as $this_postmeta_entry ) {
				$this_post_id          = isset( $this_postmeta_entry['post_id'] ) ? intval( $this_postmeta_entry['post_id'] ) : 0;
				$this_key_phrase_value = isset( $this_postmeta_entry['meta_value'] ) ? sanitize_text_field( $this_postmeta_entry['meta_value'] ) : '';
				$this_key_phrase_value = ai4seo_get_primary_third_party_seo_plugin_focus_keyphrase(
					$this_third_party_seo_plugin_identifier,
					$this_key_phrase_value
				);

				// Ignore malformed rows that cannot be associated with a submitted WordPress post.
				if ( ! $this_post_id ) {
					continue;
				}

				// Empty provider rows do not claim precedence; allow a later active integration to supply fallback.
				if ( '' === $this_key_phrase_value ) {
					continue;
				}

				// Preserve the first non-empty value supplied by the configured provider order.
				if ( isset( $key_phrases[ $this_post_id ] ) ) {
					continue;
				}

				$key_phrases[ $this_post_id ] = $this_key_phrase_value;
			}
		}
	}

	return $key_phrases;
}


/**
 * Returns Yoast SEO scores for the requested post IDs.
 *
 * @param array $post_ids Post IDs.
 * @return array|null Yoast SEO scores by post ID, or null on error.
 */
function ai4seo_read_yoast_seo_scores( array $post_ids ): ?array {
	global $wpdb;

	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 647946549, 'Prevented loop', true );
		return array();
	}

	// Avoid querying provider-owned metadata when Yoast is unavailable.
	if ( ! ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ) ) {
		return array();
	}

	if ( ! $post_ids ) {
		return array();
	}

	// Normalize post IDs before binding them to the generated placeholders.
	$sanitized_post_ids = array_values( array_map( 'intval', $post_ids ) );

	// Chunk post IDs to avoid oversized IN(...) clauses.
	$database_chunk_size      = ai4seo_get_database_chunk_size();
	$sanitized_post_id_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

	$yoast_seo_scores = array();

	foreach ( $sanitized_post_id_chunks as $this_post_id_chunk ) {
		// Reuse the same exact-key query shape as other third-party postmeta readers.
		$this_query = ai4seo_prepare_third_party_postmeta_value_rows_query( $this_post_id_chunk, '_yoast_wpseo_linkdex' );

		if ( false === $this_query ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The typed query compiler prepared the closed postmeta/key/ID spec; Yoast scores need a current batch read because provider writes have no reliable shared invalidation hook.
		$this_chunk_yoast_seo_scores = $wpdb->get_results( $this_query );

		// Abort the whole score map rather than exposing a partial set after a failed chunk.
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

	// Convert raw provider rows to the function's established post-ID score map.
	$seo_scores = array();

	foreach ( $yoast_seo_scores as $yoast_seo_score ) {
		$post_id   = $yoast_seo_score->post_id;
		$seo_score = $yoast_seo_score->meta_value;

		// Ignore malformed provider rows that cannot be associated with a WordPress post.
		if ( ! is_numeric( $post_id ) || ! $post_id ) {
			continue;
		}

		// Preserve Yoast's raw score representation under its owning post ID.
		$seo_scores[ $post_id ] = $seo_score;
	}

	return $seo_scores;
}


// endregion
// ___________________________________________________________________________________________.
