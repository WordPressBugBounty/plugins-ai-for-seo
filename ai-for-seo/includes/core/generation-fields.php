<?php
/**
 * Shared read access to the existing generation-field registries.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return unchanged field descriptors for an exact generation context.
 *
 * Registries are translated during init. Read them on each call so an early
 * empty result cannot hide the declarations after initialization.
 *
 * @param string $context Metadata or attachment_attributes generation context.
 * @return array<string, array<string, mixed>> Field descriptors in declaration order.
 */
function ai4seo_get_generation_field_descriptors( string $context ): array {
	if ( 'metadata' === $context && defined( 'AI4SEO_METADATA_DETAILS' ) && is_array( AI4SEO_METADATA_DETAILS ) ) {
		return AI4SEO_METADATA_DETAILS;
	}

	if ( 'attachment_attributes' === $context && defined( 'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS' ) && is_array( AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS ) ) {
		return AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS;
	}

	return array();
}

/**
 * Returns the configured maximum length for the given editor field identifier.
 *
 * @param string $identifier The metadata or attachment identifier to evaluate.
 * @return int
 */
function ai4seo_get_max_editor_input_length( string $identifier ): int {
	if ( ! defined( 'AI4SEO_MAX_EDITOR_INPUT_LENGTHS' ) ) {
		return 512;
	}

	$identifier   = strtolower( $identifier );
	$max_lengths  = AI4SEO_MAX_EDITOR_INPUT_LENGTHS;
	$fallback_max = (int) ( $max_lengths['fallback'] ?? 512 );

	return (int) ( $max_lengths[ $identifier ] ?? $fallback_max );
}
