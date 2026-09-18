<?php
/**
 * Read-only native WordPress shortcuts to the existing SOOZ editors.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the appropriate editor only for a supported, editable native object.
 *
 * @param WP_Post $post Native list or destination object.
 * @return string Editor identifier, or an empty string when unavailable.
 */
function ai4seo_get_native_editor_for_post( WP_Post $post ): string {
	if ( ! ai4seo_can_edit_post( $post->ID ) || ai4seo_does_user_need_to_accept_tos_toc_and_pp()
		|| in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
		return '';
	}

	if ( 'attachment' === $post->post_type ) {
		return in_array( 'attachment', ai4seo_get_supported_attachment_post_types(), true )
			&& ai4seo_is_related_attachment_post_id_valid( $post->ID ) ? 'media' : '';
	}

	return in_array( $post->post_type, ai4seo_get_supported_post_types(), true ) ? 'metadata' : '';
}

/**
 * Append one native anchor without changing existing action keys or ordering.
 *
 * @param array   $actions Existing native row actions.
 * @param WP_Post $post    Native list object.
 * @return array Row actions including the available editor shortcut.
 */
function ai4seo_add_native_editor_row_action( array $actions, WP_Post $post ): array {
	$editor = ai4seo_get_native_editor_for_post( $post );

	if ( '' === $editor || isset( $actions['ai4seo_editor'] ) ) {
		return $actions;
	}

	$parameters = array( 'ai4seo_editor_post_id' => $post->ID );

	if ( 'media' === $editor ) {
		$url = ai4seo_get_subpage_url( 'media', $parameters );
		/* translators: %s: White-label plugin name. */
		$label = sprintf( __( '%s Media Attributes Editor', 'ai-for-seo' ), AI4SEO_SHORT_PLUGIN_NAME );
	} else {
		$parameters['ai4seo_post_type'] = $post->post_type;
		$url                            = ai4seo_get_subpage_url( 'post', $parameters );
		/* translators: %s: White-label plugin name. */
		$label = sprintf( __( '%s Metadata Editor', 'ai-for-seo' ), AI4SEO_SHORT_PLUGIN_NAME );
	}

	$actions['ai4seo_editor'] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';

	return $actions;
}

/**
 * Authorize a direct editor URL before exposing its target to plugin-page JavaScript.
 *
 * @return array Validated editor and decimal-string post ID, or no target.
 */
function ai4seo_get_requested_native_editor(): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only navigation; strict decimal validation below must reject malformed input rather than sanitize it into another ID. Editor AJAX verifies its nonce.
	$post_id = isset( $_GET['ai4seo_editor_post_id'] ) ? wp_unslash( $_GET['ai4seo_editor_post_id'] ) : '';

	// Do not coerce arrays, negatives, fractions, exponents, overflow or trailing text to another object.
	if ( ! is_string( $post_id ) || ! ctype_digit( $post_id ) || '0' === $post_id
		|| (string) (int) $post_id !== $post_id ) {
		return array();
	}

	$post = get_post( (int) $post_id );

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$editor = ai4seo_get_native_editor_for_post( $post );

	if ( 'media' === $editor && 'media' !== ai4seo_get_active_subpage() ) {
		return array();
	}

	if ( 'metadata' === $editor && ( 'post' !== ai4seo_get_active_subpage()
		|| ai4seo_get_active_post_type_subpage() !== $post->post_type ) ) {
		return array();
	}

	if ( '' === $editor ) {
		return array();
	}

	return array(
		'editor'  => $editor,
		'post_id' => $post_id,
	);
}
