<?php
/**
 * Personal offers and separate, voluntary contact preferences.
 *
 * @package AI_For_SEO
 */

// Keep account offer helpers unavailable when WordPress has not loaded.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate the additive account payload before persisting or displaying it.
 *
 * @param mixed $state Untrusted account payload.
 */
function ai4seo_validate_activation_state( $state ): bool {
	if ( ! is_array( $state ) ) {
		return false;
	}
	if ( ! $state ) {
		return true;
	}
	if ( ! is_bool( $state['available'] ?? null ) || ! is_int( $state['received_at'] ?? null ) ) {
		return false;
	}
	if ( isset( $state['offer'] ) ) {
		$offer = $state['offer'];
		if ( ! is_array( $offer ) || ! is_string( $offer['id'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $offer['id'] )
			|| ! is_string( $offer['code'] ?? null ) || 1 !== preg_match( '/^[A-Z0-9]{6,64}$/D', $offer['code'] )
			|| ! in_array( $offer['percentage'] ?? null, array( 60, 70, 80, 90 ), true )
			|| ! in_array( $offer['currency'] ?? null, array( 'eur', 'usd' ), true )
			|| ! in_array( $offer['tax_behavior'] ?? null, array( 'exclusive', 'inclusive' ), true ) ) {
			return false;
		}
		foreach ( array( 'credits', 'base_minor', 'net_minor', 'expires_at' ) as $key ) {
			if ( ! is_int( $offer[ $key ] ?? null ) || $offer[ $key ] <= 0 ) {
				return false;
			}
		}
		if ( $offer['net_minor'] >= $offer['base_minor'] || $offer['credits'] > 16777215 ) {
			return false;
		}
	}
	if ( isset( $state['consent_version'] ) && ! is_string( $state['consent_version'] ) ) {
		return false;
	}
	if ( isset( $state['preferences'] ) ) {
		if ( ! is_array( $state['preferences'] ) ) {
			return false;
		}
		foreach ( $state['preferences'] as $key => $value ) {
			if ( in_array( $key, array( 'country', 'email' ), true ) ? ! is_string( $value ) : ! is_int( $value ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Preserve validated activation integers while sanitizing stored text.
 *
 * @param mixed $value Scalar activation value.
 * @return bool|int|string Sanitized value with boolean and integer types retained.
 */
function ai4seo_sanitize_activation_state_value( $value ) {
	return is_int( $value ) || is_bool( $value ) ? $value : sanitize_text_field( $value );
}

/**
 * Store only valid server state, clearing offers no longer exposed by the server.
 *
 * @param mixed $data Untrusted account payload.
 */
function ai4seo_store_activation_data( $data ): void {
	$data                = is_array( $data ) ? $data : array( 'available' => false );
	$data['received_at'] = time();
	$data['account']     = ai4seo_get_notification_preferences_account();
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVATION, ai4seo_validate_activation_state( $data ) ? $data : array() );
}

/**
 * Reject stale personal marketing; a failed sync never extends a previous permission snapshot.
 *
 * @param bool $preferences_only Allow older preference snapshots without returning their offers.
 * @return array Valid account-bound snapshot, or an empty array when unavailable.
 */
function ai4seo_get_activation_data( bool $preferences_only = false ): array {
	$data = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVATION );
	if ( ! ai4seo_validate_activation_state( $data ) || ( ! $preferences_only && time() - ( $data['received_at'] ?? 0 ) > 600 ) || ( $data['received_at'] ?? 0 ) > time()
		|| ( isset( $data['account'] ) && ai4seo_get_notification_preferences_account() !== $data['account'] ) ) {
		return array();
	}
	if ( $preferences_only ) {
		unset( $data['offer'] );
	}
	return $data;
}

/** Respect site-wide dismissal even when the remote payload still contains the same cycle. */
function ai4seo_get_activation_offer(): array {
	$sync = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_NOTIFICATION_PREFERENCES );
	if ( ! empty( $sync['pending'] ) || ( $sync && ( $sync['account'] ?? '' ) !== ai4seo_get_notification_preferences_account() ) ) {
		return array();
	}
	$data  = ai4seo_get_activation_data();
	$offer = $data['offer'] ?? array();
	if ( ! $offer || $offer['expires_at'] <= time() || ! empty( $data['preferences']['opted_out'] )
		|| ai4seo_is_notification_dismissed( 'activation-' . $offer['id'] ) ) {
		return array();
	}
	return $offer;
}

/**
 * Render a quote consistently on Account, Dashboard, and credit selection.
 *
 * @param array $offer Validated immutable offer.
 * @param bool  $global_notice Whether to render the compact global notice.
 */
function ai4seo_render_activation_offer( array $offer, bool $global_notice = false ): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		return;
	}
	if ( $global_notice ) {
		echo '<div class="notice notice-info is-dismissible ai4seo-activation-global" data-offer-surface="admin" data-offer-id="' . esc_attr( $offer['id'] ) . '"><p>';
		/* translators: 1: Plugin name, 2: Credits, 3: Price, 4: Currency. */
		echo esc_html( sprintf( __( 'Personal %1$s offer: %2$s credits for %3$s %4$s.', 'ai-for-seo' ), AI4SEO_PLUGIN_NAME, number_format_i18n( $offer['credits'] ), number_format_i18n( $offer['net_minor'] / 100, 2 ), strtoupper( $offer['currency'] ) ) );
		echo ' ' . esc_html( 'exclusive' === $offer['tax_behavior'] ? __( 'Plus applicable tax.', 'ai-for-seo' ) : __( 'Including applicable tax.', 'ai-for-seo' ) );
		echo '</p><div class="ai4seo-buttons-wrapper">';
		echo '<a class="ai4seo-button ai4seo-unicorn-button" href="' . esc_url( add_query_arg( 'activation_offer', $offer['id'], ai4seo_get_subpage_url( 'account' ) ) ) . '">' . esc_html__( 'View offer', 'ai-for-seo' ) . '</a>';
		ai4seo_echo_wp_kses( ai4seo_get_offer_preference_button( 'admin' ) );
		echo '</div>';
		// Supply the dismiss control so WordPress does not remove the notice before the server confirms it.
		echo '<button type="button" class="notice-dismiss ai4seo-activation-action" data-activation-action="dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'ai-for-seo' ) . '</span></button></div>';
		return;
	}
	echo '<div class="ai4seo-card ai4seo-activation-card" data-offer-surface="plugin" data-offer-id="' . esc_attr( $offer['id'] ) . '"><p><strong>';
	/* translators: 1: Number of credits, 2: Price, 3: Currency. */
	echo esc_html( sprintf( __( 'Your personal offer: %1$s credits for %2$s %3$s', 'ai-for-seo' ), number_format_i18n( $offer['credits'] ), number_format_i18n( $offer['net_minor'] / 100, 2 ), strtoupper( $offer['currency'] ) ) );
	echo '</strong></p><p>';
	/* translators: 1: Discount percentage, 2: Expiration date in the site timezone. */
	echo esc_html( sprintf( __( '%1$d%% off the proportional 5,000-credit package price. Includes your reported open credit requirement plus 5%%. Valid until %2$s.', 'ai-for-seo' ), $offer['percentage'], wp_date( 'Y-m-d H:i T', $offer['expires_at'] ) ) );
	echo ' ' . esc_html( 'exclusive' === $offer['tax_behavior'] ? __( 'Applicable tax is added in checkout.', 'ai-for-seo' ) : __( 'Includes applicable tax.', 'ai-for-seo' ) );
	echo '</p><p><code>' . esc_html( $offer['code'] ) . '</code> ' . esc_html__( 'Applied automatically in checkout.', 'ai-for-seo' ) . '</p><div class="ai4seo-buttons-wrapper ai4seo-activation-actions">';
	foreach ( array(
		'buy'     => __( 'Buy this offer', 'ai-for-seo' ),
		'dismiss' => __( 'Hide this offer', 'ai-for-seo' ),
	) as $action => $label ) {
		$button_class = 'buy' === $action ? 'ai4seo-button ai4seo-unicorn-button' : 'ai4seo-button ai4seo-secondary-button';
		echo '<button type="button" class="' . esc_attr( $button_class ) . ' ai4seo-activation-action" data-activation-action="' . esc_attr( $action ) . '" data-offer-id="' . esc_attr( $offer['id'] ) . '">' . esc_html( $label ) . '</button> ';
	}
	ai4seo_echo_wp_kses( ai4seo_get_offer_preference_button( 'plugin' ) );
	echo '</div></div>';
}

/**
 * Show current offers, including explicitly requested email offers on the Account page.
 *
 * @param bool $preferences Whether this is the Account page, including requested email offers.
 */
function ai4seo_render_activation_area( bool $preferences = false ): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		return;
	}
	$offer = ai4seo_get_activation_offer();
	if ( ! ai4seo_get_setting( AI4SEO_SETTING_SHOW_PLUGIN_OFFERS ) ) {
		$offer = array();
	}
	// An email link explicitly requests this one quote; it does not enable plugin marketing.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only quote link; owner authentication remains mandatory at the API.
	if ( $preferences && isset( $_GET['activation_offer'] ) && is_string( $_GET['activation_offer'] ) ) {
		$requested = sanitize_text_field( wp_unslash( $_GET['activation_offer'] ) );
		if ( 1 === preg_match( '/^[a-f0-9]{32}$/D', $requested ) ) {
			// An explicit quote must not fall back to a different cached offer after a failed lookup.
			$offer    = array();
			$response = ai4seo_robhub_api()->call(
				'client/activation-preferences',
				array(
					'preferences' => wp_json_encode(
						array(
							'action'   => 'view',
							'offer_id' => $requested,
						)
					),
				)
			);
			$view     = array(
				'available'   => false,
				'received_at' => time(),
				'offer'       => $response['data']['offer'] ?? null,
			);
			if ( ai4seo_robhub_api()->was_call_successful( $response ) && is_array( $view['offer'] ) && ai4seo_validate_activation_state( $view )
				&& $requested === $view['offer']['id'] && $view['offer']['expires_at'] > time() ) {
				$offer = $view['offer'];
			}
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	if ( $offer ) {
		ai4seo_render_activation_offer( $offer );
	}
}

/** Render voluntary channel preferences within the Account privacy section. */
function ai4seo_render_activation_preferences(): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		return;
	}
	$data       = ai4seo_get_activation_data( true );
	$permission = $data['preferences'] ?? array();
	$original   = array(
		'email'             => ! empty( $permission['email_consented_at'] ),
		'country'           => (string) ( $permission['country'] ?? '' ),
		'address'           => (string) ( $permission['email'] ?? '' ),
		'consent_version'   => (string) ( $data['consent_version'] ?? '' ),
		'expected_revision' => (int) ( $permission['preferences_revision'] ?? 0 ),
	);
	echo '<div id="ai4seo-activation-preferences" class="ai4seo-activation-preferences">';
	echo '<input type="hidden" name="ai4seo_activation_original" value="' . esc_attr( wp_json_encode( $original ) ) . '">';
	echo '<input type="hidden" name="ai4seo_activation_signature" value="' . esc_attr( ai4seo_get_activation_preferences_signature( $original ) ) . '">';
	echo '<input type="hidden" name="ai4seo_activation_consent_version" value="' . esc_attr( $original['consent_version'] ) . '">';
	echo '<hr class="ai4seo-form-item-divider"><div class="ai4seo-form-item">';
	echo '<span class="ai4seo-form-item-label">' . esc_html__( 'Optional email communication', 'ai-for-seo' ) . ':</span>';
	echo '<div class="ai4seo-form-item-input-wrapper">';
	/* translators: %s: Plugin name. */
	echo '<label for="ai4seo-activation-email">' . esc_html( sprintf( __( 'I agree to receive emails from %s about personalized offers, product updates and announcements. My email address must first be confirmed.', 'ai-for-seo' ), AI4SEO_PLUGIN_NAME ) ) . ' <input type="checkbox" id="ai4seo-activation-email" name="ai4seo_activation_email" class="ai4seo-single-checkbox ai4seo-lockable" ' . checked( $original['email'], true, false ) . '></label>';
	echo '<p class="ai4seo-form-item-description">' . esc_html__( 'Offers may be personalized using this website’s reported credit requirements. You can withdraw your email consent at any time without affecting plugin access.', 'ai-for-seo' );
	echo ' ' . esc_html__( 'More information:', 'ai-for-seo' ) . ' <a href="' . esc_url( AI4SEO_PRIVACY_POLICY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy policy', 'ai-for-seo' ) . '</a></p></div></div>';
	echo '<div class="ai4seo-form-item">';
	echo '<label for="ai4seo-activation-country">' . esc_html__( 'Your country', 'ai-for-seo' ) . ':</label>';
	echo '<div class="ai4seo-form-item-input-wrapper"><select id="ai4seo-activation-country" class="ai4seo-lockable" name="ai4seo_activation_country" autocomplete="country">';
	$countries = ai4seo_get_country_names();
	asort( $countries, SORT_NATURAL | SORT_FLAG_CASE );
	$selected_country = strtoupper( trim( (string) ( $permission['country'] ?? '' ) ) );
	echo '<option value=""' . selected( isset( $countries[ $selected_country ] ), false, false ) . '>' . esc_html__( 'Select your country', 'ai-for-seo' ) . '</option>';
	foreach ( $countries as $country_code => $country_name ) {
		echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $selected_country, $country_code, false ) . '>' . esc_html( $country_name ) . '</option>';
	}
	echo '</select></div></div>';
	echo '<div class="ai4seo-form-item">';
	echo '<label for="ai4seo-activation-address">' . esc_html__( 'Your email address', 'ai-for-seo' ) . ':</label>';
	echo '<div class="ai4seo-form-item-input-wrapper"><input type="email" id="ai4seo-activation-address" class="ai4seo-textfield ai4seo-lockable" name="ai4seo_activation_address" autocomplete="email" value="' . esc_attr( (string) ( $permission['email'] ?? '' ) ) . '">';
	if ( ! empty( $permission['email_consented_at'] ) && empty( $permission['email_confirmed_at'] ) ) {
		echo '<p class="ai4seo-form-item-description">' . esc_html__( 'Please confirm your email address using the link in the confirmation email.', 'ai-for-seo' ) . '</p>';
	}
	echo '</div></div>';
	if ( ! empty( $permission['opted_out'] ) ) {
		echo '<div class="ai4seo-form-item"><span class="ai4seo-form-item-label">' . esc_html__( 'Personal offers', 'ai-for-seo' ) . ':</span><div class="ai4seo-form-item-input-wrapper">';
		echo '<label><input type="checkbox" name="ai4seo_notification_reactivate" class="ai4seo-single-checkbox ai4seo-lockable"> ' . esc_html__( 'I want to resume personal offers and optional emails through my selected channels.', 'ai-for-seo' ) . '</label></div></div>';
	}
	echo '</div>';
}

/**
 * Bind a rendered preference snapshot to its license, including across account changes.
 *
 * @param array $preferences Original form values.
 * @return string Account-bound signature without exposing credentials.
 */
function ai4seo_get_activation_preferences_signature( array $preferences ): string {
	return wp_hash( wp_json_encode( array( $preferences, ai4seo_robhub_api()->read_auth_data() ) ) );
}

/** A global notice only for an authorized administrator with a current personal offer. */
function ai4seo_activation_admin_notice(): void {
	if ( ! ai4seo_can_administer_plugin() || ai4seo_is_plugin_page_active() ) {
		return;
	}
	$offer = ai4seo_get_activation_offer();
	if ( $offer ) {
		ai4seo_render_activation_offer( $offer, true );
	}
}

/** Add one non-forced notification per cycle; shared read state has no pricing effect. */
function ai4seo_check_activation_notification(): void {
	$offer = ai4seo_get_activation_offer();
	if ( ! $offer ) {
		return;
	}
	/* translators: %s: Website-specific offer code. */
	$message = sprintf( __( 'A personal credit offer is available: %s. Open your account to view the quantity and price.', 'ai-for-seo' ), $offer['code'] );
	ai4seo_push_notification(
		'activation-' . $offer['id'],
		$message,
		false,
		array(
			'activation_offer_id'     => $offer['id'],
			'type'                    => 'info',
			'get_more_credits_button' => true,
		)
	);
}

/** Administrative AJAX: separate consent, dismissal, and global opt-out actions. */
function ai4seo_update_activation_preferences(): void {
	if ( ! ai4seo_singleton( __FUNCTION__ ) || ! ai4seo_require_ajax_administration() ) {
		return;
	}
	if ( false === wp_verify_nonce( $GLOBALS['ai4seo_ajax_nonce'] ?? '', AI4SEO_GLOBAL_NONCE_IDENTIFIER ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Please refresh the page and try again.', 'ai-for-seo' ), 5092704 );
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON booleans retain their types; the authenticated API validates every allowed field.
	$input = isset( $_POST['preferences'] ) && is_string( $_POST['preferences'] ) ? json_decode( wp_unslash( $_POST['preferences'] ), true ) : null;
	if ( ! is_array( $input ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Invalid offer preferences.', 'ai-for-seo' ), 5092702 );
		return;
	}
	$result = ai4seo_save_activation_preferences( $input );
	if ( is_wp_error( $result ) ) {
		ai4seo_send_ajax_error( $result->get_error_message(), (int) $result->get_error_code() );
		return;
	}
	ai4seo_send_ajax_success();
}

/**
 * Share the authenticated API update between Account saves and offer actions.
 *
 * @param array $input Channel choices or an explicit offer action.
 * @return WP_Error|null Error on failure, null after confirmed persistence.
 */
function ai4seo_save_activation_preferences( array $input ) {
	if ( ! ai4seo_can_administer_plugin() ) {
		return new WP_Error( 11420725, esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ) );
	}
	$input['actor'] = (string) get_current_user_id();
	$response       = ai4seo_robhub_api()->call( 'client/activation-preferences', array( 'preferences' => wp_json_encode( $input ) ) );
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		return new WP_Error( 5092703, esc_html__( 'Could not save offer preferences. Please try again later.', 'ai-for-seo' ) );
	}
	if ( 'dismiss' === ( $input['action'] ?? '' ) && is_string( $input['offer_id'] ?? null ) ) {
		ai4seo_mark_notification_as_dismissed( 'activation-' . $input['offer_id'] );
	}
	ai4seo_store_activation_data( $response['data'] ?? array() );
	return null;
}
