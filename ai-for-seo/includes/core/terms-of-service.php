<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region TERMS OF SERVICE ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Function to check if we're going to show a terms of service layer
 * ATTENTION: DO NOT USE ROBHUB API COMMUNICATOR FUNCTIONS IN THIS FUNCTION TO PREVENT LOOPS
 *
 * @param bool $check_group The check group value.
 * @return bool True if we need to show the terms of service layer, false if not.
 */
function ai4seo_does_user_need_to_accept_tos_toc_and_pp( $check_group = true ): bool {
	global $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;

	// currently deactivated.
	return false;

	if ( null !== $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp ) {
		return $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;
	}

	// get latest update to the terms of service, terms of conditions or privacy policy.
	$latest_tos_or_toc_or_pp_update_timestamp = ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp();

	// get the last time the user accepted the terms of service, terms of conditions or privacy policy.
	$tos_toc_and_pp_accepted_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME );

	// check if the user needs to accept the new terms.
	$ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp = ( $tos_toc_and_pp_accepted_time < $latest_tos_or_toc_or_pp_update_timestamp );

	return $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;
}

// =========================================================================================== \\

/**
 * Returns the latest timestamp of the terms of service, terms of conditions or privacy policy update, depending on
 * what is the latest
 *
 * @return int The latest timestamp
 */
function ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp(): int {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 613876128, 'Prevented loop', true );
		return 0;
	}

	// check the last known sooz.ai's terms update.
	$last_website_toc_and_pp_update_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME );

	// for satefty reasons, we will not accept a timestamp that is in the future -> limit it to the current time - 1.
	if ( $last_website_toc_and_pp_update_time > time() ) {
		$last_website_toc_and_pp_update_time = time() - 1;
	}

	if ( AI4SEO_TOS_VERSION_TIMESTAMP > $last_website_toc_and_pp_update_time ) {
		return AI4SEO_TOS_VERSION_TIMESTAMP;
	} else {
		return $last_website_toc_and_pp_update_time;
	}
}

// =========================================================================================== \\

/**
 * Function to get the latest version of the terms of service, terms of conditions or privacy policy
 *
 * @return string
 */
function ai4seo_get_latest_tos_and_toc_and_pp_version(): string {
	return 'v' . ( ai4seo_gmdate( 'Y-m-d', ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp() ) ?: '???' );
}

// =========================================================================================== \\

/**
 * Function to show the terms of service modal
 *
 * @return void
 */
function ai4seo_show_terms_of_service_modal() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// check if we are in the admin area of WordPress.
	if ( ! is_admin() ) {
		return;
	}

	$does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();

	if ( ! $does_user_need_to_accept_tos_toc_and_pp ) {
		return;
	}

	// update last open modal time to prevent re-opens in some cases.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME, time() );

	// --- JAVASCRIPT --------------------------------------------------------- \\
	?><script type="text/javascript">
	jQuery(function() {
		ai4seo_open_modal_from_schema("tos", {modal_css_class: "ai4seo-tos-modal", modal_size: "auto"})
	});
	</script>
	<?php
	// ------------------------------------------------------------------------ \\
}

// =========================================================================================== \\

/**
 * Returns the HTML code of the TOS content.
 *
 * @return string The HTML code of the TOS content
 */
function ai4seo_get_tos_content(): string {
	$html = '';

	$html .= '<h2>' . __( 'I - General Definitions', 'ai-for-seo' ) . '</h2>';
	$html .= '<ol>';
		/* translators: 1: Company name. 2: Company abbreviation. */
		$html .= '<li>' . sprintf( __( 'This plugin was created and is maintained, including all updates and support, by <em>%1$s</em>, a German SEO agency (hereinafter referred to as \'<em>%2$s</em>\').', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_NAME, AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';

		/* translators: %s: plugin name */
		$html .= '<li>' . sprintf( __( 'These Terms of Service outline your rights and responsibilities when using the <em>%s</em> plugin. Please read them carefully.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</li>';

		$html .= '<li>' . __( 'These Terms of Service are governed by the laws of Germany, and any disputes shall be resolved under German jurisdiction.', 'ai-for-seo' ) . '</li>';
	$html     .= '</ol>';

	$html .= '<h2>' . __( 'II - General Acknowledgements', 'ai-for-seo' ) . '</h2>';
	$html .= '<ol>';
		/* translators: 1: Terms and Conditions URL. 2: Privacy Policy URL. 3: Company abbreviation. */
		$html .= '<li>' . sprintf( __( 'I have read and accept the <a href=\'%1$s\' target=\'_blank\'>Terms and Conditions</a> and <a href=\'%2$s\' target=\'_blank\'>Privacy Policy</a> of <em>%3$s</em>.', 'ai-for-seo' ), AI4SEO_TERMS_AND_CONDITIONS_URL, AI4SEO_PRIVACY_POLICY_URL, AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( '<em>%s</em> will not be liable for any direct, indirect, incidental, or consequential damages arising from the use of the plugin or generated content.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
	$html     .= '</ol>';

	$html     .= '<h2>' . __( 'III - User Responsibilities', 'ai-for-seo' ) . '</h2>';
	$html     .= '<ol>';
		$html .= '<li>' . __( 'I confirm that my content will be free from references to illegal drugs, violence, explicit material or otherwise illegal material.', 'ai-for-seo' ) . '</li>';
		$html .= '<li>' . __( 'I understand that AI may make errors, and I am responsible for reviewing all generated results to ensure accuracy and compliance with applicable laws.', 'ai-for-seo' ) . '</li>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( 'I acknowledge that I am solely responsible for how the generated data is used on my website, and <em>%s</em> is not liable for any misuse or improper application of the data.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		$html .= '<li>' . __( 'I will ensure that my use of the plugin complies with all applicable laws and regulations in my jurisdiction.', 'ai-for-seo' ) . '</li>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( 'I understand that using certain features within the plugin will consume Credits based on the specific feature. If higher-than-expected credit consumption occurs due to user actions, whether intentional or unintentional, Credits cannot be refunded or reversed unless %s determines the user was not responsible. However, the right to a 100%% refund within the 14-day money-back guarantee period still applies under these circumstances.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		$html .= '<li>' . __( 'I may request a full refund within 14 days of the first purchase if not satisfied with the plugin’s performance, as outlined in our money-back guarantee policy. This refund policy applies only to the first purchase of either a subscription or Credits Pack. A refund for any purchases beyond the first one is excluded.', 'ai-for-seo' ) . '</li>';
	$html     .= '</ol>';

	$html .= '<h2>' . __( 'IV - Data Ownership, Handling and Lifetime', 'ai-for-seo' ) . '</h2>';
	$html .= '<ol>';
		/* translators: %1$s plugin name, %2$s Company abbreviation. */
		$html .= '<li>' . sprintf( __( 'All data generated through the <em>%1$s</em> plugin remains the intellectual property of the user, provided it does not violate the terms of service. <em>%2$s</em> holds no claims to ownership of user-generated content.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( '<em>%s</em> complies with applicable data protection regulations, including the GDPR and DSGVO.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		/* translators: 1: Company abbreviation. 2: Terms and Conditions URL. 3: Privacy Policy URL. */
		$html .= '<li>' . sprintf( __( 'I agree that, in order to execute certain functions, data will be sent to <em>%1$s</em>\'s servers. This content will only be used and stored for purposes stated in the <a href=\'%2$s\' target=\'_blank\'>Terms and Conditions</a> and <a href=\'%3$s\' target=\'_blank\'>Privacy Policy</a>.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_TERMS_AND_CONDITIONS_URL, AI4SEO_PRIVACY_POLICY_URL ) . '</li>';
		/* translators: 1: Company abbreviation. 2: Privacy Policy URL. */
		$html .= '<li>' . sprintf( __( 'When accepting these Terms of Service, <em>%1$s</em> may collect and store certain information, including the user’s website URL, website name, email address, IP address, the version of the Terms accepted, and the timestamp of acceptance. This data is collected solely for compliance purposes and will be retained securely for the period necessary to fulfill legal obligations or until account deletion, as outlined in our <a href=\'%2$s\' target=\'_blank\'>Privacy Policy</a>.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_PRIVACY_POLICY_URL ) . '</li>';
		/* translators: %s: Privacy Policy URL. */
		$html .= '<li>' . sprintf( __( "Data will be stored only for as long as necessary to fulfill the stated purpose and will be deleted in accordance with the data retention policy outlined in the <a href='%s' target='_blank'>Privacy Policy</a>.", 'ai-for-seo' ), AI4SEO_PRIVACY_POLICY_URL ) . '</li>';
		/* translators: 1: Company abbreviation. 2: Support email address. 3: Support email address. */
		$html .= '<li>' . sprintf( __( 'I may request the deletion of my data at any time, and <em>%1$s</em> will comply unless the data is required for fulfilling contractual or legal obligations. Requests can be sent to <a href=\'mailto:%2$s\'>%3$s</a>.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_SUPPORT_EMAIL, AI4SEO_SUPPORT_EMAIL ) . '</li>';
	$html     .= '</ol>';

	$html .= '<h2>' . __( 'V - Third-parties and partners', 'ai-for-seo' ) . '</h2>';
	$html .= '<ol>';
		/* translators: %s: OpenAI URL. */
		$html .= '<li>' . sprintf( __( "I agree that, in order to execute certain functions, data from my website and its content may be sent to third-party services, including <em><a href='%s' target='_blank'>OpenAI</a></em>.", 'ai-for-seo' ), AI4SEO_OPENAI_URL ) . '</li>';
		/* translators: %s: OpenAI Terms of Use URL. */
		$html .= '<li>' . sprintf( __( "I will adhere to <em>OpenAI</em>'s <a href='%s' target='_blank'>Terms of Use</a> at all times.", 'ai-for-seo' ), AI4SEO_OPENAI_TERMS_OF_USE_URL ) . '</li>';
		$html .= '<li>' . __( 'I specifically confirm that my content will be free from references to illegal drugs, extreme violence, or explicit material.', 'ai-for-seo' ) . '</li>';
	$html     .= '</ol>';

	$html .= '<h2>' . __( 'VI - Rights and Modifications', 'ai-for-seo' ) . '</h2>';
	$html .= '<ol>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( '<em>%s</em> reserves the right to terminate access to the plugin or revoke usage rights at any time if the terms are violated. In the event of termination, I will no longer have access to the plugin and any associated services.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
		/* translators: %s: Company abbreviation. */
		$html .= '<li>' . sprintf( __( '<em>%s</em> reserves the right to modify these terms at any time. Users will be notified of any significant changes.', 'ai-for-seo' ), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION ) . '</li>';
	$html     .= '</ol>';

	return $html;
}

// =========================================================================================== \\

/**
 * Called via AJAX - On reject of the terms of service -> deactivate plugin
 *
 * @return void
 */
function ai4seo_reject_tos() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109840 );
		return;
	}

	// check if we are in the admin area of WordPress.
	if ( ! is_admin() ) {
		return;
	}

	// perform the reject terms call.
	ai4seo_robhub_api()->perform_reject_terms_call( AI4SEO_TOS_VERSION_TIMESTAMP );

	// uninstall the plugin.
	ai4seo_deactivate_plugin();

	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - On accept of the terms of service -> save the timestamp
 *
 * @return void
 */
function ai4seo_accept_tos() {
	// Make sure that this function is only called once.
	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// Recheck the global AJAX nonce before handling this protected admin request.
	if ( wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
		ai4seo_send_ajax_error( esc_html__( 'Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo' ), 12109841 );
		return;
	}

	// check if we are in the admin area of WordPress.
	if ( ! is_admin() ) {
		return;
	}

	// check if we accepted tos, toc and pp before.
	$tos_toc_and_pp_accepted_time = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME );

	// check if we accepted enhanced reporting before.
	$enhanced_reporting_accepted = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED );

	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME, time() );

	// handle enhanced reporting -> only save changes if we see the tos for the first time or the user has not accepted it before
	// not handling the save here only because the user did not see the checkbox in the modal.
	if ( ! $tos_toc_and_pp_accepted_time || ! $enhanced_reporting_accepted ) {
		// check for $_POST["accepted_enhanced_reporting"].
		$enhanced_reporting_accepted = isset( $_POST['accepted_enhanced_reporting'] ) && 'true' === $_POST['accepted_enhanced_reporting'];

		if ( $enhanced_reporting_accepted ) {
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED, true );
			ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME, time() );
		}
	}

	// set tos accept details to database to share it with the maker of the plugin.
	ai4seo_set_tos_accept_details( $enhanced_reporting_accepted, 'accepted tos, toc and pp' );

	ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Set the ToS Acceptance details to the database
 *
 * @param bool   $accepted_enhanced_reporting Whether the user agreed to the extended data collection.
 * @param string $action The action that was performed.
 * @return void
 */
function ai4seo_set_tos_accept_details( bool $accepted_enhanced_reporting, string $action = 'unknown' ) {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 287798693, 'Prevented loop', true );
		return;
	}

	// collect additional data and put it into the wp_option "AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS".
	$additional_tos_accept_details = array(
		'action'                            => sanitize_text_field( $action ),
		'website_url'                       => sanitize_text_field( get_site_url() ),
		'website_name'                      => sanitize_text_field( get_bloginfo( 'name' ) ),
		'email_address'                     => sanitize_email( ai4seo_get_option( 'admin_email' ) ),
		'client_ip_address'                 => ai4seo_get_client_ip(),
		'server_ip_address'                 => ai4seo_get_server_ip(),
		'user_agent'                        => ai4seo_get_client_user_agent(),
		'tos_version'                       => AI4SEO_TOS_VERSION_TIMESTAMP,
		'timestamp'                         => time(),
		'accepted_extended_data_collection' => $accepted_enhanced_reporting ? '1' : '0',
	);

	$additional_tos_accept_details = ai4seo_deep_sanitize( $additional_tos_accept_details );

	ai4seo_update_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME, $additional_tos_accept_details );
}

// =========================================================================================== \\

/**
 * Function to send the additional tos accept details, if available in the database
 *
 * @return void
 */
function ai4seo_send_additional_tos_accept_details() {
	if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
		return;
	}

	// Make sure that the user is allowed to use this plugin.
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
		return;
	}

	// check in wp_options if we have additional tos accept details.
	$additional_tos_accept_details = ai4seo_get_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME );
	$additional_tos_accept_details = ai4seo_deep_sanitize( $additional_tos_accept_details );

	if ( ! $additional_tos_accept_details ) {
		return;
	}

	$new_tos_details_checksum  = ai4seo_get_array_checksum( $additional_tos_accept_details );
	$last_tos_details_checksum = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM );

	// prevent re-sending the same data.
	if ( $new_tos_details_checksum === $last_tos_details_checksum ) {
		// delete the additional tos accept details from the database.
		ai4seo_delete_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME );
		return;
	}

	// prevent re-sending the same data using a timestamp of the last try
	// only allow to send this data once every 1 hour.
	$tried_to_send_this_data_before_timestamp = (int) ai4seo_get_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME );

	if ( $tried_to_send_this_data_before_timestamp && $tried_to_send_this_data_before_timestamp > ( time() - 3600 ) ) {
		return;
	}

	ai4seo_update_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME, time() );

	// call robhub api endpoint "accept-terms" with the additional tos accept details.
	$response = ai4seo_robhub_api()->call( 'client/accept-terms', $additional_tos_accept_details );

	// check response.
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_debug_message( 1712121224, 'Invalid response from RobHub API.', true );
		return;
	}

	// on success...
	// remove the additional tos accept details from the database.
	ai4seo_delete_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME );
	ai4seo_delete_option( AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME );

	// save the checksum of the new tos details.
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM, $new_tos_details_checksum );
}


// endregion
// ___________________________________________________________________________________________.
