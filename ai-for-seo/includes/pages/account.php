<?php
/**
 * Renders the content of the submenu page for the AI for SEO license page.
 *
 * @since 2.0.0
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Recovery renders before any credential read so invalid ownership cannot expose the normal Account page.
if ( ai4seo_can_recover_incognito_mode() ) {
	$ai4seo_incognito_setting_input_name = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE );

	echo "<div class='card ai4seo-form-section'>";
		echo '<h1>' . esc_html__( 'Incognito Mode recovery', 'ai-for-seo' ) . '</h1>';
		echo '<p>' . esc_html__( 'The saved Incognito Mode owner is missing or is no longer a site administrator. Content access remains blocked until an administrator claims ownership or disables Incognito Mode.', 'ai-for-seo' ) . '</p>';

		echo "<div class='ai4seo-buttons-wrapper'>";
			echo "<div class='ai4seo-form'>";
				echo "<input type='hidden' name='" . esc_attr( $ai4seo_incognito_setting_input_name ) . "' value='true' />";
				ai4seo_echo_wp_kses(
					ai4seo_get_submit_button_tag(
						esc_html__( 'Claim Incognito ownership', 'ai-for-seo' ),
						'ai4seo-primary-button ai4seo-lockable',
						'ai4seo_save_anything(jQuery(this), null, function() { ai4seo_safe_page_load(); });'
					)
				);
			echo '</div>';

			echo "<div class='ai4seo-form'>";
				echo "<input type='hidden' name='" . esc_attr( $ai4seo_incognito_setting_input_name ) . "' value='false' />";
				ai4seo_echo_wp_kses(
					ai4seo_get_submit_button_tag(
						esc_html__( 'Disable Incognito Mode', 'ai-for-seo' ),
						'ai4seo-lockable',
						'ai4seo_save_anything(jQuery(this), null, function() { ai4seo_safe_page_load(); });'
					)
				);
			echo '</div>';
		echo '</div>';
	echo '</div>';

	return;
}

// Defense in depth keeps direct includes behind the same route-level administrative boundary.
if ( ! ai4seo_can_administer_plugin() ) {
	wp_die(
		esc_html__( 'You are not allowed to access this page.', 'ai-for-seo' ),
		esc_html__( 'Access denied', 'ai-for-seo' ),
		array( 'response' => 403 )
	);
}

$ai4seo_robhub_subscription      = ai4seo_robhub_api()->read_environmental_variable( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );
$ai4seo_is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();
$ai4seo_is_auth_locked           = ai4seo_robhub_api()->is_auth_data_locked();
$ai4seo_has_rotation_recovery    = ai4seo_robhub_api()->has_api_password_rotation_recovery_intent();
$ai4seo_rotation_recovery_notice = ai4seo_robhub_api()->get_api_password_rotation_recovery_notice_state();

// Define boolean to determine whether to read license-data.
$ai4seo_show_license_details = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );

// Allow the read-only support flag to reveal saved license details without changing account data.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This read-only support flag only changes local account-page display.
if ( isset( $_GET['ai4seo_force_show_licence_details'] ) && sanitize_text_field( wp_unslash( $_GET['ai4seo_force_show_licence_details'] ) ) ) {
	$ai4seo_show_license_details = true;
}

// Define license variables.
$ai4seo_auth_data        = ai4seo_robhub_api()->read_auth_data();
$ai4seo_license_username = $ai4seo_auth_data[0] ?? '';
$ai4seo_license_key      = $ai4seo_auth_data[1] ?? '';

if ( ! $ai4seo_license_username || ! $ai4seo_license_key ) {
	$ai4seo_show_license_details = false;
}

if ( ! $ai4seo_show_license_details ) {
	$ai4seo_license_username = '';
	$ai4seo_license_key      = '';
}

// Prepare enhanced reporting.
$ai4seo_did_user_accept_enhanced_reporting  = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED );
$ai4seo_enhanced_reporting_revoke_timestamp = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME );

// Define variables for the current username and email.
$ai4seo_current_user          = wp_get_current_user();
$ai4seo_current_user_username = ( $ai4seo_current_user->user_login ?? 'unknown' );
$ai4seo_current_user_email    = ( $ai4seo_current_user->user_email ?? 'unknown' );

// Define variables for the settings.
$ai4seo_setting_enable_incognito_mode           = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE );
$ai4seo_setting_enable_white_label              = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_WHITE_LABEL );
$ai4seo_setting_plugin_name                     = ai4seo_get_setting( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME );
$ai4seo_setting_plugin_description              = ai4seo_get_setting( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION );
$ai4seo_setting_display_source_code_notes       = ai4seo_get_setting( AI4SEO_SETTING_ADD_GENERATOR_HINTS );
$ai4seo_setting_source_code_notes_content_start = ai4seo_get_setting( AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT );
$ai4seo_setting_source_code_notes_content_end   = ai4seo_get_setting( AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT );

$ai4seo_current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

$ai4seo_robhub_subscription_plan              = $ai4seo_robhub_subscription['plan'] ?? 'free';
$ai4seo_robhub_subscription_end_date_and_time = $ai4seo_robhub_subscription['subscription_end'] ?? false;
$ai4seo_robhub_subscription_end_timestamp     = $ai4seo_robhub_subscription_end_date_and_time
	? strtotime( $ai4seo_robhub_subscription_end_date_and_time ) : 0;
$ai4seo_user_is_on_free_plan                  = ( 'free' === $ai4seo_robhub_subscription_plan ) || $ai4seo_robhub_subscription_end_timestamp < time();
$ai4seo_has_purchased_something               = (bool) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-form ai4seo-unsaved-changes-warnings'>";

// A server-generated replacement is intentionally never returned to this site.
// Keep the administrator on secure recovery guidance until the emailed pair verifies.
if ( $ai4seo_has_rotation_recovery ) {
	$ai4seo_rotation_recovery_status        = $ai4seo_rotation_recovery_notice['status'] ?? 'none';
	$ai4seo_rotation_credential_email_state = $ai4seo_rotation_recovery_notice['credential_email_status'] ?? 'not-applicable';

	echo "<div class='notice notice-warning inline ai4seo-api-password-rotation-recovery-notice' role='status'>";
	echo '<p><strong>' . esc_html__( 'License reconnection required', 'ai-for-seo' ) . '</strong></p>';
	echo '<p>';

	if ( 'confirmed' === $ai4seo_rotation_recovery_status && 'sent' === $ai4seo_rotation_credential_email_state ) {
		echo esc_html__( 'A replacement license key was sent to the verified Stripe checkout email. Check your inbox and spam folder, then enter the emailed license owner and key below and save your changes.', 'ai-for-seo' );
	} elseif ( 'confirmed' === $ai4seo_rotation_recovery_status && 'failed' === $ai4seo_rotation_credential_email_state ) {
		echo esc_html__( 'A replacement license key was generated, but email delivery could not be confirmed. Delivery retries automatically; use the license recovery help below if the verified Stripe checkout inbox still has no message.', 'ai-for-seo' );
	} elseif ( 'confirmed' === $ai4seo_rotation_recovery_status ) {
		echo esc_html__( 'A replacement license key is being delivered to the verified Stripe checkout email. Check your inbox and spam folder, then enter the emailed license owner and key below and save your changes.', 'ai-for-seo' );
	} elseif ( 'pending' === $ai4seo_rotation_recovery_status ) {
		echo esc_html__( 'Secure license recovery is awaiting confirmation. Refresh the account status, then check the verified Stripe checkout email before reconnecting below.', 'ai-for-seo' );
	} else {
		// Malformed or unreadable local recovery bytes remain quarantined and are never rendered.
		echo esc_html__( 'Secure license recovery needs attention. Check the verified Stripe checkout inbox or use the recovery help below, then reconnect with the verified credentials.', 'ai-for-seo' );
	}

	echo '</p>';
	echo "<div class='ai4seo-buttons-wrapper'>";
	ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'key-slash', esc_html__( 'License recovery help', 'ai-for-seo' ), '', 'ai4seo_open_lost_key_modal();' ) );
	echo '</div>';
	echo '</div>';
}

	// ___________________________________________________________________________________________ \\
	// === LICENSE =============================================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	echo "<div class='card ai4seo-form-section'>";

		// === HEADLINE ============================================================================== \\

		echo '<h2>';
			ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'id-alt', 'ai4seo-menu-item-icon', true ) );
			echo esc_html__( 'License', 'ai-for-seo' );
		echo '</h2>';


		// === DESCRIPTION =========================================================================== \\

			echo "<div class='ai4seo-account-license-description'>";
				// Show description in case of existing license.
if ( $ai4seo_show_license_details ) {
	echo '<ul>';
	echo '<li>' . esc_html__( 'Please make sure to save the license owner and license key somewhere safe in case your need to reconnect your website to your existing account.', 'ai-for-seo' ) . '</li>';
	echo '<li><strong>' . esc_html__( 'You can use these credentials on as many websites as you like which is especially convenient for SEO- and web agencies.', 'ai-for-seo' ) . '</strong></li>';
	echo '</ul>';
} else {
			// Show the onboarding copy when no license credentials are available yet.
	echo '<ul>';
	echo '<li>' . esc_html__( 'Here you can connect your website to an existing account in order to use the Credits from your main account.', 'ai-for-seo' ) . '</li>';
	echo '<li>' . esc_html__( 'Your credentials will be generated automatically when you purchase a plan or Credits and you will be able to find them here.', 'ai-for-seo' ) . '</li>';
	echo '</ul>';
}
			echo '</div>';


		// === API USERNAME / LICENSE OWNER ========================================================== \\

		$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME );

		echo "<div class='ai4seo-form-item'>";
			echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'License owner', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_license_username ) . "' />";
			echo '</div>';
		echo '</div>';

		echo "<hr class='ai4seo-form-item-divider'>";


		// === API PASSWORD / LICENSE KEY =========================================================================== \\

		$ai4seo_this_prefixed_input_id          = ai4seo_get_prefixed_input_name( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD );
		$ai4seo_license_key_input_wrapper_class = 'ai4seo-form-item-input-wrapper ai4seo-license-key-input-wrapper ai4seo-license-key-input-wrapper-has-toggle' . ( $ai4seo_license_key ? '' : ' ai4seo-license-key-entry-mode' );
		$ai4seo_license_key_toggle_controls     = $ai4seo_license_key ? 'ai4seo-visual-license-key-holder ai4seo-actual-license-key-holder' : $ai4seo_this_prefixed_input_id;

		echo "<div class='ai4seo-form-item'>";
			echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'License key', 'ai-for-seo' ) . ':</label>';
			echo "<div class='" . esc_attr( $ai4seo_license_key_input_wrapper_class ) . "'>";
				// Mask a new license key while retaining an explicit reveal control for verification.
if ( ! $ai4seo_license_key ) {
	echo "<input type='password' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='new-password' value='' />";
} else {
			// Render both saved-key states so JavaScript can swap visibility without rebuilding the field.
	echo "<div id='ai4seo-actual-license-key-holder' class='ai4seo-display-none'>";
	echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_license_key ) . "' />";
	echo '</div>';

			// Keep the masked state in the same wrapper so the floating toggle stays anchored.
	echo "<div id='ai4seo-visual-license-key-holder'>";
	echo "<input type='text' class='ai4seo-textfield ai4seo-inactive-element ai4seo-license-key-mask' autocomplete='off' value='**********************************' readonly='readonly' />";
	echo '</div>';
}

				// Use one control and label pair for both saved-key and editable-key visibility.
	echo "<button type='button' class='ai4seo-form-floating-textfield-icon-holder ai4seo-license-key-toggle' aria-controls='" . esc_attr( $ai4seo_license_key_toggle_controls ) . "' aria-expanded='false' aria-label='" . esc_attr__( 'Show license key', 'ai-for-seo' ) . "' data-ai4seo-show-label='" . esc_attr__( 'Show license key', 'ai-for-seo' ) . "' data-ai4seo-hide-label='" . esc_attr__( 'Hide license key', 'ai-for-seo' ) . "'>";
	echo "<span class='ai4seo-license-key-toggle-show-state'>";
		echo esc_html__( 'Show', 'ai-for-seo' ) . ' ';
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'eye', __( 'Reveal License Key', 'ai-for-seo' ) ) );
	echo '</span>';
	echo "<span class='ai4seo-license-key-toggle-hide-state ai4seo-display-none'>";
		echo esc_html__( 'Hide', 'ai-for-seo' ) . ' ';
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'eye-slash', __( 'Hide License Key', 'ai-for-seo' ) ) );
	echo '</span>';
	echo '</button>';
			echo '</div>';
		echo '</div>';

		echo "<hr class='ai4seo-form-item-divider'>";


		// === BUTTONS =========================================================================== \\

		echo "<div class='ai4seo-form-item ai4seo-form-item-no-top-padding'>";
			echo "<div class='ai4seo-buttons-wrapper ai4seo-account-license-buttons-wrapper'>";
				// Button to show lost-license-instructions.
if ( ! $ai4seo_is_robhub_account_synced || ! $ai4seo_show_license_details ) {
	ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'key-slash', esc_html__( 'Lost your license data?', 'ai-for-seo' ), '', 'ai4seo_open_lost_key_modal();' ) );
}

				// Button to manage subscription if user has an active subscription.
if ( ! $ai4seo_user_is_on_free_plan ) {
	ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( AI4SEO_STRIPE_BILLING_URL, '', '_blank', 'stripe', esc_html__( 'Manage Subscription / Invoices', 'ai-for-seo' ) ) );
}

				// Button to manage credits.
if ( $ai4seo_is_robhub_account_synced ) {
	ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'arrow-up-right-from-square', esc_html__( 'Get more Credits', 'ai-for-seo' ), '', 'ai4seo_open_get_more_credits_modal();' ) );
}

				// Customize pay-as-you-go.
if ( $ai4seo_has_purchased_something && $ai4seo_is_robhub_account_synced ) {
	ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'sliders', esc_html__( 'Customize Pay-As-You-Go', 'ai-for-seo' ), '', 'ai4seo_handle_open_customize_payg_modal();' ) );
}

if ( $ai4seo_license_username && $ai4seo_license_key && $ai4seo_show_license_details ) {
	ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'trash', esc_html__( 'Remove license', 'ai-for-seo' ), 'ai4seo-secondary-button', 'ai4seo_remove_license(this);' ) );
}
			echo '</div>';
		echo '</div>';
	echo '</div>';


	// ___________________________________________________________________________________________ \\
	// === AGENCY MODE =========================================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	echo "<div class='card ai4seo-form-section'>";

		// === HEADLINE ============================================================================== \\

		echo '<h2>';
			ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'megaphone', 'ai4seo-menu-item-icon', true ) );
			echo esc_html__( 'For SEO and Web Agencies', 'ai-for-seo' );
		echo '</h2>';


		// === ENABLE INCOGNITO MODE ================================================================= \\

		$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_ENABLE_INCOGNITO_MODE );

		$ai4seo_this_setting_description = sprintf(
			/* translators: 1: Username, 2: Email Address */
			__( 'By enabling this checkbox you can hide the plugin from all other users. This means that <strong><u>only you</u> (%1$s, %2$s)</strong> will be able to generate data, access and edit plugin settings and see the menu item in the header and the main menu of your website. Please note that the plugin will still be visible in the plugin list to other users. However, you may white-label the appearance using the settings below.', 'ai-for-seo' ),
			$ai4seo_current_user_username,
			$ai4seo_current_user_email
		);

		echo "<div class='ai4seo-form-item'>";
			echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'Incognito Mode', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				// Keep the native checkbox inside the label so the shared switch CSS can place it after the copy.
				echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>";
					echo esc_html__( 'Enable Incognito Mode', 'ai-for-seo' );
					echo "<input type='checkbox' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' class='ai4seo-single-checkbox' " . ( $ai4seo_setting_enable_incognito_mode ? " checked='checked'" : '' ) . ' /> ';
				echo '</label>';

				// Description.
				echo "<p class='ai4seo-form-item-description'>";
					ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
				echo '</p>';
			echo '</div>';
		echo '</div>';

		echo "<hr class='ai4seo-form-item-divider'>";


		// === ENABLE WHITE-LABEL ==================================================================== \\

		$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_ENABLE_WHITE_LABEL );

		$ai4seo_this_setting_description = __( 'Enabling this option will give you access to change the display of certain plugin related information (i.e. the plugin name).', 'ai-for-seo' );

		echo "<div class='ai4seo-form-item'>";
			echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'White-Label Mode', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				// Keep the onchange handler on the native input while the label row handles the switch alignment.
				echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>";
					echo esc_html__( 'Enable White-Label Mode', 'ai-for-seo' );
					echo "<input type='checkbox' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' class='ai4seo-single-checkbox' onchange='ai4seo_toggle_visibility_on_checkbox(jQuery(this), jQuery(\".ai4seo-white-label-only-container\"));'" . ( $ai4seo_setting_enable_white_label ? " checked='checked'" : '' ) . ' /> ';
				echo '</label>';

				// Description.
				echo "<p class='ai4seo-form-item-description'>";
					ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
				echo '</p>';
			echo '</div>';
		echo '</div>';


		// ___________________________________________________________________________________________ \\
		// === HIDDEN WHITE LABEL ELEMENTS =========================================================== \\
		// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

		echo "<div class='ai4seo-white-label-only-container" . ( $ai4seo_setting_enable_white_label ? '' : ' ai4seo-display-none' ) . "'>";

			echo "<hr class='ai4seo-form-item-divider'>";


			// === PLUGIN NAME =========================================================================== \\

			$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME );

			$ai4seo_this_setting_description = sprintf(
				/* translators: %s: URL to the installed plugins page */
				__( "Here you can define the plugin name that will be shown on the <a href='%s'>installed plugins page</a> of your website.", 'ai-for-seo' ),
				esc_url( admin_url( 'plugins.php' ) )
			);

			echo "<div class='ai4seo-form-item'>";
				echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( "Overwrite 'Installed Plugins' Page Plugin Name", 'ai-for-seo' ) . ':</label>';
				echo "<div class='ai4seo-form-item-input-wrapper'>";
					// Input.
					echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_setting_plugin_name ) . "' minlength='3' maxlength='100' />";

					// Description.
					echo "<p class='ai4seo-form-item-description'>";
						ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
					echo '</p>';
				echo '</div>';
			echo '</div>';

			echo "<hr class='ai4seo-form-item-divider'>";


			// === PLUGIN DESCRIPTION ==================================================================== \\

			$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION );

			$ai4seo_this_setting_description = sprintf(
				/* translators: %s: URL to the installed plugins page */
				__( "Here you can define the plugin description that will be shown on the <a href='%s'>installed plugins page</a> of your website.", 'ai-for-seo' ),
				esc_url( admin_url( 'plugins.php' ) )
			);

			echo "<div class='ai4seo-form-item'>";
				echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( "Overwrite 'Installed Plugins' Page Plugin Description", 'ai-for-seo' ) . ':</label>';
				echo "<div class='ai4seo-form-item-input-wrapper'>";
					// Input.
					echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_setting_plugin_description ) . "' minlength='3' maxlength='140' />";

					// Description.
					echo "<p class='ai4seo-form-item-description'>";
						ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
					echo '</p>';
				echo '</div>';
			echo '</div>';

			echo "<hr class='ai4seo-form-item-divider'>";


			// === ADD GENERATOR HINTS ============================================================= \\

			$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_ADD_GENERATOR_HINTS );

			$ai4seo_this_setting_description = __( 'With this setting you can decide whether to display a comment block before and after the meta tags block generated by the plugin in the <u>source code</u> of your website.', 'ai-for-seo' );

			echo "<div class='ai4seo-form-item'>";
				echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'Generator Hints (Source Code)', 'ai-for-seo' ) . ':</label>';
				echo "<div class='ai4seo-form-item-input-wrapper'>";
					// Keep the onchange handler on the native input while the label row handles the switch alignment.
					echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>";
						echo esc_html__( 'Add Generator Hints', 'ai-for-seo' );
						echo "<input type='checkbox' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' class='ai4seo-single-checkbox' onchange='ai4seo_toggle_visibility_on_checkbox(jQuery(this), jQuery(\".ai4seo-source-code-adjustments-only-container\"));' " . ( $ai4seo_setting_display_source_code_notes ? " checked='checked'" : '' ) . ' /> ';
					echo '</label>';

					// Description.
					echo "<p class='ai4seo-form-item-description'>";
						ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
					echo '</p>';
				echo '</div>';
			echo '</div>';


			// ___________________________________________________________________________________________ \\
			// === HIDDEN SOURCE CODE ADJUSTMENTS ======================================================== \\
			// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

			echo "<div class='ai4seo-source-code-adjustments-only-container" . ( $ai4seo_setting_display_source_code_notes ? '' : ' ai4seo-display-none' ) . "'>";

				echo "<hr class='ai4seo-form-item-divider'>";

				// === Meta Tags Block Starting Hint ======================================================= \\

				$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT );

				$ai4seo_this_setting_description  = __( 'Here you can define the content of the comment block that will be displayed before the meta tags generated by the plugin in the source code of your website.', 'ai-for-seo' ) . '<br /><br />';
				$ai4seo_this_setting_description .= esc_html__( 'Possible placeholders:', 'ai-for-seo' ) . ' {NAME} = ' . esc_html( AI4SEO_PLUGIN_NAME ) . ', {VERSION} = ' . esc_html( AI4SEO_PLUGIN_VERSION_NUMBER ) . ', {WEBSITE} = ' . esc_html( AI4SEO_OFFICIAL_WEBSITE );

				echo "<div class='ai4seo-form-item'>";
					echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'Meta Tags Block Starting Hint', 'ai-for-seo' ) . ':</label>';
					echo "<div class='ai4seo-form-item-input-wrapper'>";
						// Input.
						echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_setting_source_code_notes_content_start ) . "' minlength='3' maxlength='250' />";

						// Description.
						echo "<p class='ai4seo-form-item-description'>";
							ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
						echo '</p>';
					echo '</div>';
				echo '</div>';

				echo "<hr class='ai4seo-form-item-divider'>";


				// === Meta Tags Block Ending Hint ========================================================= \\

				$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT );

				$ai4seo_this_setting_description  = __( 'Here you can define the content of the comment block that will be displayed after the meta tags generated by the plugin in the source code of your website.', 'ai-for-seo' ) . '<br /><br>';
				$ai4seo_this_setting_description .= esc_html__( 'Possible placeholders:', 'ai-for-seo' ) . ' {NAME} = ' . esc_html( AI4SEO_PLUGIN_NAME );

				echo "<div class='ai4seo-form-item'>";
					echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'Meta Tags Block Ending Hint', 'ai-for-seo' ) . ':</label>';
					echo "<div class='ai4seo-form-item-input-wrapper'>";
						// Input.
						echo "<input type='text' class='ai4seo-textfield' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' autocomplete='off' value='" . esc_attr( $ai4seo_setting_source_code_notes_content_end ) . "' minlength='3' maxlength='250' />";

						// Description.
						echo "<p class='ai4seo-form-item-description'>";
							ai4seo_echo_wp_kses( $ai4seo_this_setting_description );
						echo '</p>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';


			// ___________________________________________________________________________________________ \\
			// === PRIVACY AND AGREEMENTS ================================================================ \\
			// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

			echo "<div class='card ai4seo-form-section'>";
			// Headline.
			echo '<h2>';
			ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'shield', 'ai4seo-menu-item-icon', true ) );
			echo esc_html__( 'Privacy & Agreements', 'ai-for-seo' );
			echo '</h2>';

			// TERMS OF SERVICE BUTTON.
			echo "<div class='ai4seo-form-item'>";
			echo "<span class='ai4seo-form-item-label'>";
				echo esc_html__( 'Terms of Service', 'ai-for-seo' ) . ':';
			echo '</span>';

			echo "<div class='ai4seo-form-item-input-wrapper'>";
			if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp( false ) ) {
				ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'arrow-up-right-from-square', esc_html__( 'Show Terms of Service', 'ai-for-seo' ), '', 'ai4seo_open_modal_from_schema("tos", {modal_css_class: "ai4seo-tos-modal", modal_size: "auto"});' ) );
			} else {
				ai4seo_echo_wp_kses( ai4seo_get_icon_button_tag( 'arrow-up-right-from-square', esc_html__( 'Show Terms of Service', 'ai-for-seo' ), 'ai4seo-lockable', 'ai4seo_open_ajax_modal("ai4seo_show_terms_of_service", {}, {modal_size: "small"});' ) );
			}

				echo "<p class='ai4seo-form-item-description'>";
					$ai4seo_latest_tos_and_toc_and_pp_version = ai4seo_get_latest_tos_and_toc_and_pp_version();
					/* translators: %s: Latest version number */
					echo esc_html( sprintf( __( 'Current version: %s', 'ai-for-seo' ), $ai4seo_latest_tos_and_toc_and_pp_version ) ) . '.<br><br>';
					ai4seo_echo_wp_kses( ai4seo_get_tos_toc_and_pp_accepted_time_output() );
				echo '</p>';
			echo '</div>';
			echo '</div>';

			echo "<hr class='ai4seo-form-item-divider'>";

			// ENHANCED REPORTING.
			echo "<div class='ai4seo-form-item'>";
			echo "<span class='ai4seo-form-item-label'>";
				echo esc_html__( 'Enhanced Reporting:', 'ai-for-seo' );
			echo '</span>';

			echo "<div class='ai4seo-form-item-input-wrapper'>";

				$ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name( AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED );

				// Enhanced reporting opt-in keeps the existing option text while the native checkbox renders as a switch.
				$ai4seo_extended_data_collection_tooltip_text = __( 'This data includes feature usage, performance metrics, and error logs. It will be stored for up to 30 days to assist with improving the plugin. You can opt out of data collection at any time through the plugin settings.', 'ai-for-seo' );

				echo "<div class='ai4seo-account-extended-data-consent'>";
					// Keep the tooltip trigger outside the label so both controls retain their native keyboard behavior.
					echo "<span class='ai4seo-label-with-tooltip'>";
						echo "<label for='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "'>" . esc_html__( 'I agree to share extended data to support the ongoing development of the plugin. I may opt out at any time.', 'ai-for-seo' ) . '</label>';
						ai4seo_echo_wp_kses(
							ai4seo_get_icon_with_tooltip_tag(
								$ai4seo_extended_data_collection_tooltip_text,
								'',
								'circle-question',
								__( 'Enhanced Reporting help', 'ai-for-seo' )
							)
						);
						echo '</span>';
						echo "<input type='checkbox' id='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' name='" . esc_attr( $ai4seo_this_prefixed_input_id ) . "' class='ai4seo-single-checkbox' " . ( $ai4seo_did_user_accept_enhanced_reporting ? " checked='checked'" : '' ) . '>';
						echo '</div>';

						echo "<p class='ai4seo-form-item-description'>";
						// revoked?
						if ( ! $ai4seo_did_user_accept_enhanced_reporting && $ai4seo_enhanced_reporting_revoke_timestamp ) {
							$ai4seo_readable_revoked_time = ai4seo_format_unix_timestamp( $ai4seo_enhanced_reporting_revoke_timestamp );
							ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'square-xmark', '', 'ai4seo-16x16-icon ai4seo-red-icon' ) );
							echo ' ';
							/* translators: %s: Revoked time */
							printf( esc_html__( 'Revoked on %s.', 'ai-for-seo' ), esc_html( $ai4seo_readable_revoked_time ) );
						} else {
							ai4seo_echo_wp_kses( ai4seo_get_enhanced_reporting_accepted_time_output() );
						}
						echo '</p>';
						echo '</div>';
						echo '</div>';
						echo '</div>';

						// Submit button.
						echo "<div class='ai4seo-sticky-buttons-bar'>";
						echo "<div class='ai4seo-buttons-wrapper'>";
						ai4seo_echo_wp_kses( ai4seo_get_submit_button_tag( esc_html__( 'Save changes', 'ai-for-seo' ), 'ai4seo-start-inactive ai4seo-big-button', 'ai4seo_save_anything(jQuery(this), ai4seo_validate_account_inputs, function() { ai4seo_safe_page_load(); });' ) );
						echo '</div>';
						echo '</div>';
						echo '</div>';
