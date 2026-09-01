<?php
/**
 * Displays the Get More Credits modal. Called via AJAX.
 *
 * @package AI_For_SEO
 * @since 2.4.3
 */

// Reject direct execution before any account or billing helper can run.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Billing details and purchase controls belong exclusively to the administrative boundary.
if ( ! ai4seo_can_administer_plugin() ) {
	return;
}

// Recheck the global AJAX nonce before rendering account and billing information.
if ( false === wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) ) {
	ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 1108261200 );
	return;
}

$ai4seo_get_more_credits_schema_path = ai4seo_get_includes_modal_schemas_path( 'get-more-credits.php' );

if ( ! is_readable( $ai4seo_get_more_credits_schema_path ) ) {
	ai4seo_send_ajax_error( esc_html__( 'The Credits modal is currently unavailable. Please refresh the page and try again.', 'ai-for-seo' ), 1108261201 );
	return;
}

// Render the canonical schema directly with the wrapper classes consumed by the AJAX modal parser.
ob_start();
$ai4seo_modal_render_context = 'ajax';
require $ai4seo_get_more_credits_schema_path;
unset( $ai4seo_modal_render_context );
$ai4seo_get_more_credits_modal_content = ob_get_clean();

// Return no partial response when the canonical schema produced no usable HTML.
if ( ! is_string( $ai4seo_get_more_credits_modal_content ) || '' === trim( $ai4seo_get_more_credits_modal_content ) ) {
	return;
}

ai4seo_echo_wp_kses( $ai4seo_get_more_credits_modal_content );

// Supply every schema opened from the Credits overview before the AJAX parser discards response siblings.
ob_start();
echo "<div class='ai4seo-modal-schemas-container'>";

foreach ( array( 'select-credits-pack', 'customize-pay-as-you-go' ) as $ai4seo_credits_modal_schema_identifier ) {
	echo "<div class='ai4seo-modal-schema' id='ai4seo-modal-schema-" . esc_attr( $ai4seo_credits_modal_schema_identifier ) . "'>";
		include ai4seo_get_includes_modal_schemas_path( $ai4seo_credits_modal_schema_identifier . '.php' );
	echo '</div>';
}

echo '</div>';
$ai4seo_credits_modal_dependency_schemas = ob_get_clean();

if ( is_string( $ai4seo_credits_modal_dependency_schemas ) && '' !== trim( $ai4seo_credits_modal_dependency_schemas ) ) {
	ai4seo_echo_wp_kses( $ai4seo_credits_modal_dependency_schemas );
}
