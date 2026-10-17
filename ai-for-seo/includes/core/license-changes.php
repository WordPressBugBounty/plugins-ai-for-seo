<?php
/**
 * Administrative AJAX adapters for email-confirmed license changes.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Prepare a manual change without altering credentials. */
function ai4seo_request_license_change() {
	ai4seo_process_license_change_action( 'request' );
}

/** Complete the change authorized by the checkout inbox. */
function ai4seo_confirm_license_change() {
	ai4seo_process_license_change_action( 'confirm' );
}

/** Reconcile a durable operation without repeating its remote mutation. */
function ai4seo_get_license_change_status() {
	ai4seo_process_license_change_action( 'status' );
}

/**
 * Shared rights checks and closed, translated errors for administrative actions.
 *
 * @param string $action Requested workflow step.
 */
function ai4seo_process_license_change_action( string $action ): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) || ! ai4seo_require_ajax_administration() ) {
		return;
	}
	$api = ai4seo_robhub_api();
	if ( 'request' === $action ) {
		// The central dispatcher verified the nonce; retain exact bytes for strict selection validation.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Central nonce check; strict kind validation.
		$kind = isset( $_POST['kind'] ) && is_string( $_POST['kind'] ) ? wp_unslash( $_POST['kind'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Central nonce check; strict username validation rejects rather than rewrites input.
		$name   = isset( $_POST['new_username'] ) && is_string( $_POST['new_username'] ) ? wp_unslash( $_POST['new_username'] ) : '';
		$result = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING )
			? $api->request_license_change( $kind, $name ) : array(
				'success' => false,
				'message' => 'credential_change_unavailable',
			);
	} elseif ( 'confirm' === $action ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Central nonce check; exact eight-digit code validation.
		$code   = isset( $_POST['confirmation_code'] ) && is_string( $_POST['confirmation_code'] ) ? wp_unslash( $_POST['confirmation_code'] ) : '';
		$result = $api->confirm_license_change( $code );
	} else {
		$result = $api->get_license_change_status();
	}
	if ( $api->was_call_successful( $result ) ) {
		if ( 'unchanged' === ( $result['data']['state'] ?? '' ) ) {
			ai4seo_send_ajax_error( __( 'Choose a different license owner before requesting a code.', 'ai-for-seo' ), 52101201 );
			return;
		}
		ai4seo_send_ajax_success( $result['data'] ?? array() );
		return;
	}
	$messages = array(
		'credential_change_name_unavailable'            => __( 'This license owner is already in use or reserved. Choose another name.', 'ai-for-seo' ),
		'credential_change_invalid_username_or_request' => __( 'Use 5–48 lowercase letters, numbers, single hyphens or underscores. Start and end with a letter or number, include a letter, and avoid reserved system names.', 'ai-for-seo' ),
		'credential_change_invalid_code'                => __( 'The confirmation code is incorrect. Check the eight-digit code in your checkout email.', 'ai-for-seo' ),
		'credential_change_expired_or_used'             => __( 'This confirmation has expired or is no longer available. Request a new code, or use license recovery if the change was already completed.', 'ai-for-seo' ),
		'credential_change_checkout_unavailable'        => __( 'A verified paid Stripe checkout email could not be established for this license. Please contact support.', 'ai-for-seo' ),
		'credential_change_checkout_changed'            => __( 'The checkout details changed. Request a new confirmation code.', 'ai-for-seo' ),
		'credential_change_rate_limited'                => __( 'Please wait at least 60 seconds before requesting another code. A maximum of three codes per hour is allowed for this license.', 'ai-for-seo' ),
		'credential_change_unavailable'                 => __( 'Connect and refresh your license first. Complete any pending license recovery before requesting a change.', 'ai-for-seo' ),
	);
	ai4seo_send_ajax_error( $messages[ $result['message'] ?? '' ] ?? __( 'The license change could not be confirmed. Its status will be checked automatically; your recovery information has been preserved.', 'ai-for-seo' ), 52101201 );
}
