<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region POST META ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to update a post meta if it is empty
 *
 * @param int    $post_id the post id.
 * @param string $meta_key the meta key.
 * @param string $meta_value the meta value.
 * @return bool True if the post meta was updated, false if not
 */
function ai4seo_update_postmeta_if_empty( int $post_id, string $meta_key, string $meta_value ): bool {
	$post_id    = sanitize_key( $post_id );
	$meta_key   = sanitize_key( $meta_key );
	$meta_value = sanitize_textarea_field( $meta_value );

	$current_value = get_post_meta( $post_id, $meta_key, true );

	if ( $current_value ) {
		return false;
	} else {
		return ai4seo_update_post_meta( $post_id, $meta_key, $meta_value );
	}
}

// =========================================================================================== \\

/**
 * Returns weather a post got generated data
 *
 * @param int $post_id the post id.
 * @return bool
 */
function ai4seo_post_has_generated_data( int $post_id ): bool {
	$generated_data = ai4seo_read_generated_data_from_post_meta( $post_id );
	return ! empty( $generated_data );
}


// endregion
// ___________________________________________________________________________________________.
