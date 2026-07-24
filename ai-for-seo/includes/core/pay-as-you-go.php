<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region PAY AS YOU GO ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

function ai4seo_send_pay_as_you_go_settings(): bool {
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return false;
	}

	// call robhub api endpoint "payg-settings" with current payg settings.
	$robhub_endpoint = 'client/payg-settings';

	$payg_settings                                        = array();
	$payg_settings[ AI4SEO_SETTING_PAYG_ENABLED ]         = (bool) ai4seo_get_setting( AI4SEO_SETTING_PAYG_ENABLED );
	$payg_settings[ AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID ] = ai4seo_get_setting( AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID );
	$payg_settings[ AI4SEO_SETTING_PAYG_DAILY_BUDGET ]    = (int) ai4seo_get_setting( AI4SEO_SETTING_PAYG_DAILY_BUDGET );
	$payg_settings[ AI4SEO_SETTING_PAYG_MONTHLY_BUDGET ]  = (int) ai4seo_get_setting( AI4SEO_SETTING_PAYG_MONTHLY_BUDGET );
	$payg_settings                                        = ai4seo_deep_sanitize( $payg_settings );

	$response = ai4seo_robhub_api()->call( $robhub_endpoint, $payg_settings );

	// check response.
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_debug_message( 361217325, 'Invalid response from RobHub API.', true );
		return false;
	}

	// remove potential previous error notification.
	ai4seo_remove_notification( 'payg-status-error' );
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS, 'idle' );
	ai4seo_delete_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON );

	return true;
}
