<?php
/**
 * Includes / autoload the modal schemas.
 *
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_manage_this_plugin() ) {
	return;
}

// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_active_plugin_page     = ai4seo_get_active_subpage();
$ai4seo_modal_schemas          = array();
$ai4seo_primary_asset_contexts = ai4seo_get_primary_asset_contexts();


// === FIND SUITABLE MODAL SCHEMAS =========================================================== \\

$is_user_inside_plugin_admin_pages     = ai4seo_is_user_inside_our_plugin_admin_pages();
$is_user_inside_installed_plugins_page = ai4seo_is_user_inside_installed_plugins_page();
$ai4seo_is_tos_gate_context            = in_array( 'tos-gate', $ai4seo_primary_asset_contexts, true );

// Foreign admin pages receive only the TOS schema selected by their dedicated asset context.
if ( $ai4seo_is_tos_gate_context && ai4seo_does_user_need_to_accept_tos_toc_and_pp( true ) ) {
	$ai4seo_modal_schemas[] = 'tos';
}

// Internal plugin pages retain their existing subpage-specific schema selection.
if ( $is_user_inside_plugin_admin_pages && in_array( 'plugin-ui', $ai4seo_primary_asset_contexts, true ) ) {
	// TOS.
	if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp( true ) ) {
		$ai4seo_modal_schemas[] = 'tos'; // group a -> every page.
	} else {
		if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp( false ) && 'account' === $ai4seo_active_plugin_page ) {
			$ai4seo_modal_schemas[] = 'tos'; // group b -> via account page.
		}

		if ( 'dashboard' === $ai4seo_active_plugin_page ) {
			$ai4seo_modal_schemas[] = 'seo-autopilot';
		}

		if ( 'settings' === $ai4seo_active_plugin_page ) {
			$ai4seo_modal_schemas[] = 'export-import-settings';
		}

		// The Credits overview opens both purchase dialogs, so keep its complete schema pack available.
		$ai4seo_modal_schemas[] = 'get-more-credits';
		$ai4seo_modal_schemas[] = 'select-credits-pack';
		$ai4seo_modal_schemas[] = 'customize-pay-as-you-go';
	}
}

// Deactivation and PAYG schemas accompany only the Installed Plugins integration context.
if ( $is_user_inside_installed_plugins_page && in_array( 'plugin-deactivation', $ai4seo_primary_asset_contexts, true ) ) {
	$ai4seo_modal_schemas[] = 'plugin-deactivation-feedback';
	$ai4seo_modal_schemas[] = 'customize-pay-as-you-go';
}

// Avoid an empty schema container when no page-specific modal is required.
if ( ! $ai4seo_modal_schemas ) {
	return;
}

// TOS may be selected by both plugin UI and gate context; emit every schema only once.
$ai4seo_modal_schemas = array_values( array_unique( $ai4seo_modal_schemas ) );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schemas-container'>";

foreach ( $ai4seo_modal_schemas as $ai4seo_this_modal_identifier ) {
	echo "<div class='ai4seo-modal-schema' id='ai4seo-modal-schema-" . esc_attr( $ai4seo_this_modal_identifier ) . "'>";
		include ai4seo_get_includes_modal_schemas_path( $ai4seo_this_modal_identifier . '.php' );
	echo '</div>';
}

echo '</div>';
