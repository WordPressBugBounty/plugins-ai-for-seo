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
 * @return int|string Sanitized value with its required integer type retained.
 */
function ai4seo_sanitize_activation_state_value( $value ) {
	return is_int( $value ) ? $value : sanitize_text_field( $value );
}

/**
 * Store only valid server state, clearing offers no longer exposed by the server.
 *
 * @param mixed $data Untrusted account payload.
 */
function ai4seo_store_activation_data( $data ): void {
	$data                = is_array( $data ) ? $data : array( 'available' => false );
	$data['received_at'] = time();
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVATION, ai4seo_validate_activation_state( $data ) ? $data : array() );
}

/** Reject stale personal marketing; a failed sync never extends a previous permission snapshot. */
function ai4seo_get_activation_data(): array {
	$data = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVATION );
	if ( ! ai4seo_validate_activation_state( $data ) || time() - ( $data['received_at'] ?? 0 ) > 600 || ( $data['received_at'] ?? 0 ) > time() ) {
		return array();
	}
	return $data;
}

/** Respect site-wide dismissal even when the remote payload still contains the same cycle. */
function ai4seo_get_activation_offer(): array {
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
		echo '<div class="notice notice-info is-dismissible ai4seo-activation-global" data-offer-id="' . esc_attr( $offer['id'] ) . '"><p>';
		/* translators: 1: Credits, 2: Price, 3: Currency. */
		echo esc_html( sprintf( __( 'Personal AI for SEO offer: %1$s credits for %2$s %3$s.', 'ai-for-seo' ), number_format_i18n( $offer['credits'] ), number_format_i18n( $offer['net_minor'] / 100, 2 ), strtoupper( $offer['currency'] ) ) );
		echo ' ' . esc_html( 'exclusive' === $offer['tax_behavior'] ? __( 'Plus applicable tax.', 'ai-for-seo' ) : __( 'Including applicable tax.', 'ai-for-seo' ) );
		echo ' <a href="' . esc_url( ai4seo_get_subpage_url( 'account' ) ) . '">' . esc_html__( 'View offer', 'ai-for-seo' ) . '</a> · <button type="button" class="button-link ai4seo-activation-action" data-activation-action="optout">' . esc_html__( 'No personal offers', 'ai-for-seo' ) . '</button></p>';
		// Supply the dismiss control so WordPress does not remove the notice before the server confirms it.
		echo '<button type="button" class="notice-dismiss ai4seo-activation-action" data-activation-action="dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'ai-for-seo' ) . '</span></button></div>';
		return;
	}
	echo '<div class="ai4seo-card ai4seo-activation-card" data-offer-id="' . esc_attr( $offer['id'] ) . '"><p><strong>';
	/* translators: 1: Number of credits, 2: Price, 3: Currency. */
	echo esc_html( sprintf( __( 'Your personal offer: %1$s credits for %2$s %3$s', 'ai-for-seo' ), number_format_i18n( $offer['credits'] ), number_format_i18n( $offer['net_minor'] / 100, 2 ), strtoupper( $offer['currency'] ) ) );
	echo '</strong></p><p>';
	/* translators: 1: Discount percentage, 2: Expiration date in the site timezone. */
	echo esc_html( sprintf( __( '%1$d%% off the proportional 5,000-credit package price. Includes your reported open credit requirement plus 5%%. Valid until %2$s.', 'ai-for-seo' ), $offer['percentage'], wp_date( 'Y-m-d H:i T', $offer['expires_at'] ) ) );
	echo ' ' . esc_html( 'exclusive' === $offer['tax_behavior'] ? __( 'Applicable tax is added in checkout.', 'ai-for-seo' ) : __( 'Includes applicable tax.', 'ai-for-seo' ) );
	echo '</p><p><code>' . esc_html( $offer['code'] ) . '</code> ' . esc_html__( 'Applied automatically in checkout.', 'ai-for-seo' ) . '</p><p class="ai4seo-activation-actions">';
	foreach ( array(
		'buy'     => __( 'Buy this offer', 'ai-for-seo' ),
		'dismiss' => __( 'Hide this offer', 'ai-for-seo' ),
		'optout'  => __( 'No personal offers', 'ai-for-seo' ),
	) as $action => $label ) {
		$button_class = 'buy' === $action ? 'button button-primary' : 'button';
		echo '<button type="button" class="' . esc_attr( $button_class ) . ' ai4seo-activation-action" data-activation-action="' . esc_attr( $action ) . '" data-offer-id="' . esc_attr( $offer['id'] ) . '">' . esc_html( $label ) . '</button> ';
	}
	echo '</p></div>';
}

/**
 * Show contextual opt-in without preselected consent.
 *
 * @param bool $preferences Whether to include contact preferences.
 */
function ai4seo_render_activation_area( bool $preferences = false ): void {
	if ( ! ai4seo_can_administer_plugin() ) {
		return;
	}
	$data  = ai4seo_get_activation_data();
	$offer = ai4seo_get_activation_offer();
	// An email link explicitly requests this one quote; it does not enable plugin marketing.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only quote link; owner authentication remains mandatory at the API.
	if ( $preferences && isset( $_GET['activation_offer'] ) && is_string( $_GET['activation_offer'] ) ) {
		$requested = sanitize_text_field( wp_unslash( $_GET['activation_offer'] ) );
		if ( 1 === preg_match( '/^[a-f0-9]{32}$/D', $requested ) ) {
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
			if ( ai4seo_robhub_api()->was_call_successful( $response ) && ai4seo_validate_activation_state( $view ) ) {
				$offer = $view['offer'];
			}
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	if ( $offer ) {
		ai4seo_render_activation_offer( $offer );
	}
	if ( empty( $data['available'] ) ) {
		return;
	}
	if ( ! $preferences ) {
		if ( ! $offer ) {
			echo '<p><a href="' . esc_url( ai4seo_get_subpage_url( 'account' ) ) . '">' . esc_html__( 'Optional personal credit offers: manage your preferences', 'ai-for-seo' ) . '</a></p>';
		}
		return;
	}
	$permission = $data['preferences'] ?? array();
	echo '<div class="ai4seo-card ai4seo-activation-preferences" data-consent-version="' . esc_attr( (string) ( $data['consent_version'] ?? '' ) ) . '">';
	echo '<h2>' . esc_html__( 'Optional personal offers', 'ai-for-seo' ) . '</h2><p>' . esc_html__( 'Offers use this website’s reported credit requirements. Each channel is optional. You can withdraw consent at any time without affecting plugin access.', 'ai-for-seo' ) . '</p>';
	echo '<p><label>' . esc_html__( 'Your country (two-letter country code)', 'ai-for-seo' ) . ' <input name="activation_country" maxlength="2" pattern="[A-Za-z]{2}" value="' . esc_attr( (string) ( $permission['country'] ?? '' ) ) . '"></label></p>';
	echo '<p><label><input type="checkbox" name="activation_plugin" ' . checked( ! empty( $permission['plugin_consented_at'] ), true, false ) . '> ' . esc_html__( 'I agree to receive personalized AI for SEO offers in this plugin.', 'ai-for-seo' ) . '</label></p>';
	echo '<p><label><input type="checkbox" name="activation_email" ' . checked( ! empty( $permission['email_consented_at'] ), true, false ) . '> ' . esc_html__( 'I agree to receive personalized AI for SEO offers by email. My address must first be confirmed.', 'ai-for-seo' ) . '</label></p>';
	echo '<p><label>' . esc_html__( 'Email address for offers', 'ai-for-seo' ) . ' <input type="email" name="activation_address" value="' . esc_attr( (string) ( $permission['email'] ?? '' ) ) . '"></label></p>';
	if ( ! empty( $permission['email_consented_at'] ) && empty( $permission['email_confirmed_at'] ) ) {
		echo '<p>' . esc_html__( 'Please confirm your email address using the link in the confirmation email.', 'ai-for-seo' ) . '</p>';
	}
	echo '<p><a href="' . esc_url( AI4SEO_PRIVACY_POLICY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy policy', 'ai-for-seo' ) . '</a></p>';
	echo '<p class="ai4seo-activation-actions"><button type="button" class="button button-primary ai4seo-activation-action" data-activation-action="save">' . esc_html__( 'Save offer preferences', 'ai-for-seo' ) . '</button> <button type="button" class="button ai4seo-activation-action" data-activation-action="optout">' . esc_html__( 'No personal offers', 'ai-for-seo' ) . '</button></p></div>';
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
	$input['actor'] = (string) get_current_user_id();
	$response       = ai4seo_robhub_api()->call( 'client/activation-preferences', array( 'preferences' => wp_json_encode( $input ) ) );
	if ( ! ai4seo_robhub_api()->was_call_successful( $response ) ) {
		ai4seo_send_ajax_error( esc_html__( 'Could not save offer preferences. Please try again later.', 'ai-for-seo' ), 5092703 );
		return;
	}
	if ( 'dismiss' === ( $input['action'] ?? '' ) && is_string( $input['offer_id'] ?? null ) ) {
		ai4seo_mark_notification_as_dismissed( 'activation-' . $input['offer_id'] );
	}
	ai4seo_store_activation_data( $response['data'] ?? array() );
	ai4seo_send_ajax_success();
}
