<?php
/**
 * Modal Schema: Represents the Select Credits Pack modal.
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

// $ai4seo_preferred_currency = ai4seo_deep_sanitize(ai4seo_get_setting(AI4SEO_SETTING_PREFERRED_CURRENCY));
$ai4seo_preferred_currency            = 'USD'; // todo: implement proper currency selection.
$ai4seo_recommended_credits_pack_size = (int) ai4seo_get_recommended_credits_pack_size_by_num_missing_entries();
$ai4seo_credits_packs                 = ai4seo_get_credits_packs();


// === DISCOUNT ============================================================================= \\

$ai4seo_current_discount            = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT );
$ai4seo_current_discount_percentage = $ai4seo_current_discount['percentage'] ?? 0;


// === COSTS ================================================================================= \\

$ai4seo_metadata_credits_cost_per_post                         = ai4seo_calculate_metadata_credits_cost_per_post();
$ai4seo_attachment_attributes_credits_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
	echo esc_html__( 'Select Credits Pack', 'ai-for-seo' );
echo '</div>';


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";

	// === SELECT CURRENCY ====================================================================== \\

	// todo: implement this.

	// === SELECT CREDITS PACK SIZE ============================================================= \\

	echo esc_html__( 'Select the amount of Credits for your needs.', 'ai-for-seo' ) . ' ';

	// current discount.
	ai4seo_echo_current_discount();

if ( ! empty( $ai4seo_current_discount['voucher_code'] ) ) {
	echo '<br>';
	echo esc_html__( 'Enter this voucher code during checkout to apply the discount:', 'ai-for-seo' ) . '<br>';
	ai4seo_echo_wp_kses( ai4seo_get_voucher_code_output( $ai4seo_current_discount['voucher_code'] ) );
}

	// Group the visual cards as one native radio choice so their relationship is announced to keyboard users.
	echo "<div class='ai4seo-credits-pack-selection-container' role='radiogroup' aria-label='" . esc_attr__( 'Select Credits Pack', 'ai-for-seo' ) . "'>";

		$ai4seo_entry_counter                       = 0;
		$ai4seo_pre_selected_credits_pack_entry     = array();
		$ai4seo_hidden_credits_packs_container_open = false;

foreach ( $ai4seo_credits_packs as $ai4seo_this_payg_stripe_price_id => $ai4seo_credits_pack_entry ) {
	++$ai4seo_entry_counter;
	$ai4seo_this_credits_amount          = (int) $ai4seo_credits_pack_entry['credits_amount'];
	$ai4seo_this_price_usd               = $ai4seo_credits_pack_entry['price_usd'];
	$ai4seo_this_reference_price_usd     = $ai4seo_credits_pack_entry['reference_price_usd'];
	$ai4seo_this_price_usd               = $ai4seo_current_discount_percentage ? $ai4seo_this_price_usd * ( 1 - ( $ai4seo_current_discount_percentage / 100 ) ) : $ai4seo_this_price_usd;
	$ai4seo_this_discount_percentage     = round( ( 1 - ( $ai4seo_this_price_usd / $ai4seo_this_reference_price_usd ) ) * 100 );
	$ai4seo_this_entry_is_pre_selected   = $ai4seo_this_credits_amount === $ai4seo_recommended_credits_pack_size;
	$ai4seo_this_entry_is_recommendation = $ai4seo_this_credits_amount === $ai4seo_recommended_credits_pack_size;

	// Use the existing loop counter for a predictable label target without exposing the Stripe price ID in the DOM id.
	$ai4seo_this_radio_input_id          = 'ai4seo-credits-pack-selection-' . $ai4seo_entry_counter;

	// floor $ai4seo_this_price_usd at second decimal to fix rounding issues.
	$ai4seo_this_price_usd = floor( round( $ai4seo_this_price_usd * 100, 1 ) ) / 100;

	$ai4seo_cost_per_page       = $ai4seo_metadata_credits_cost_per_post > 0 ? $ai4seo_this_price_usd / ( $ai4seo_this_credits_amount / $ai4seo_metadata_credits_cost_per_post ) : 0;
	$ai4seo_cost_per_attachment = $ai4seo_attachment_attributes_credits_cost_per_attachment_post > 0 ? $ai4seo_this_price_usd / ( $ai4seo_this_credits_amount / $ai4seo_attachment_attributes_credits_cost_per_attachment_post ) : 0;

	if ( $ai4seo_this_entry_is_pre_selected ) {
		$ai4seo_pre_selected_credits_pack_entry                        = $ai4seo_credits_pack_entry;
		$ai4seo_pre_selected_credits_pack_entry['cost_per_page']       = $ai4seo_cost_per_page;
		$ai4seo_pre_selected_credits_pack_entry['cost_per_attachment'] = $ai4seo_cost_per_attachment;
		$ai4seo_pre_selected_credits_pack_entry['credits_amount']      = $ai4seo_this_credits_amount;
	}

	// most popular label.
	if ( $ai4seo_this_entry_is_recommendation ) {
		echo "<div class='ai4seo-credits-pack-selection-item-most-popular-label'>";
			echo esc_html__( 'Most Popular – Best for Your Website Size', 'ai-for-seo' );
		echo '</div>';
	}

	// Keep the data attributes locale-formatted because the modal preview displays these values directly.
	$ai4seo_cost_per_page_formatted       = ai4seo_format_number_i18n( $ai4seo_cost_per_page, 2 );
	$ai4seo_cost_per_attachment_formatted = ai4seo_format_number_i18n( $ai4seo_cost_per_attachment, 2 );

	echo "<label class='ai4seo-credits-pack-selection-item"
		. ( $ai4seo_this_entry_is_pre_selected ? ' ai4seo-credits-pack-selection-item-selected ai4seo-credits-pack-selection-item-most-popular' : '' )
		. "' for='" . esc_attr( $ai4seo_this_radio_input_id ) . "' data-credits-amount='" . esc_attr( $ai4seo_this_credits_amount )
		. "' data-price='" . esc_attr( $ai4seo_this_price_usd )
		. "' data-currency='" . esc_attr( $ai4seo_preferred_currency )
		. "' data-cost-per-page='" . esc_attr( $ai4seo_cost_per_page_formatted )
		. "' data-cost-per-attachment='" . esc_attr( $ai4seo_cost_per_attachment_formatted ) . "'>";

		echo "<div class='ai4seo-credits-pack-selection-item-left-side'>";

			echo "<div class='ai4seo-credits-pack-selection-item-radio-button'>";
				echo "<input type='radio' id='" . esc_attr( $ai4seo_this_radio_input_id ) . "' name='ai4seo-credits-pack-selection[]' value='" . esc_attr( $ai4seo_this_payg_stripe_price_id ) . "' " . ( $ai4seo_this_entry_is_pre_selected ? 'checked' : '' ) . '>';
			echo '</div>';

			echo "<div class='ai4seo-credits-pack-selection-item-credits-amount'>";
				echo esc_html( ai4seo_format_number_i18n( $ai4seo_this_credits_amount, 0 ) );
				echo ' ' . esc_html__( 'Credits', 'ai-for-seo' );
			echo '</div>';

		echo '</div>';

		echo "<div class='ai4seo-credits-pack-selection-item-right-side'>";

	if ( $ai4seo_this_discount_percentage > 0 ) {
		echo "<div class='ai4seo-credits-pack-selection-item-discount-percentage'>";
			/* translators: %s is the discount percentage */
			printf( esc_html__( '%s%% off', 'ai-for-seo' ), esc_html( ai4seo_format_number_i18n( $ai4seo_this_discount_percentage ) ) );
		echo '</div>';
	}

	if ( $ai4seo_this_price_usd != $ai4seo_this_reference_price_usd ) {
		echo "<div class='ai4seo-credits-pack-selection-item-reference-price'>";
			echo esc_html( $ai4seo_preferred_currency ) . ' ' . esc_html( ai4seo_format_number_i18n( $ai4seo_this_reference_price_usd, 2 ) );
		echo '</div>';
	}

			echo "<div class='ai4seo-credits-pack-selection-item-price'>";
				echo esc_html( $ai4seo_preferred_currency ) . ' ' . esc_html( ai4seo_format_number_i18n( $ai4seo_this_price_usd, 2 ) ) . '*';
			echo '</div>';
		echo '</div>';
	echo '</label>';

			// show more options button.
	if ( 3 === $ai4seo_entry_counter ) {
		if ( count( $ai4seo_credits_packs ) > 3 ) {
			echo '<center>';
			ai4seo_echo_wp_kses( ai4seo_get_small_icon_button_tag( 'angle-down', __( 'Show more options', 'ai-for-seo' ), 'ai4seo-credits-pack-show-more-options-button', 'jQuery(this).parent().hide();jQuery(this).parent().next().removeClass("ai4seo-display-none");' ) );
			echo '</center>';
			echo "<div class='ai4seo-display-none'>";
			$ai4seo_hidden_credits_packs_container_open = true;
		}
	}
}

if ( $ai4seo_hidden_credits_packs_container_open ) {
	echo '</div>'; // close display-none-container.
}

		// Taxes may apply.
		echo "<div class='ai4seo-credits-pack-taxes-note'>";
			echo '* ' . esc_html__( 'Taxes may apply', 'ai-for-seo' );
		echo '</div>';

		// contact us for larger packs.
if ( count( $ai4seo_credits_packs ) <= 3 ) {
	echo "<div class='ai4seo-tiny-gap'></div>";

	// you're interested in larger Credits Packs? Contact us!
	echo "<div class='ai4seo-enterprise-plan-notice'>";
		printf(
			/* translators: %1$s is the discount info, %2$s is the opening anchor tag, %3$s is the closing anchor tag */
			esc_html__( 'Managing 2,000+ posts, products or images? Get a tailored enterprise plan with up to %1$s. %2$sRequest a custom plan%3$s', 'ai-for-seo' ),
			'<strong>30% savings</strong>',
			"<br>-> <a href='" . esc_url( AI4SEO_OFFICIAL_CONTACT_URL ) . "' target='_blank' rel='noopener'><strong>",
			'</strong></a> <-'
		);
	echo '</div>';
}

	echo '</div>';


	// === COST PER ENTRY ================================================================================= \\

	echo "<div class='ai4seo-credits-pack-cost-per-entry-container'>";
		echo '<h4>' . esc_html__( 'Cost Breakdown', 'ai-for-seo' ) . '</h4>';
		echo '<ol>';
			echo '<li>';
				printf(
					/* translators: %s is the cost per page */
					esc_html__( 'Based on your current settings, generating metadata for each page or post will cost approximately %s.', 'ai-for-seo' ),
					"<strong class='ai4seo-credits-pack-cost-per-page'>" . esc_html( $ai4seo_preferred_currency ) . ' '
					. esc_html( ai4seo_format_number_i18n( $ai4seo_pre_selected_credits_pack_entry['cost_per_page'] ?? 0, 2 ) ) . '</strong>'
				);
				echo '</li>';
				echo '<li>';
				printf(
					/* translators: %s is the cost per media file */
					esc_html__( 'Based on your current settings, generating media attributes for each image will cost approximately %s.', 'ai-for-seo' ),
					"<strong class='ai4seo-credits-pack-cost-per-attachment'>" . esc_html( $ai4seo_preferred_currency ) . ' '
					. esc_html( ai4seo_format_number_i18n( $ai4seo_pre_selected_credits_pack_entry['cost_per_attachment'] ?? 0, 2 ) ) . '</strong>'
				);
				echo '</li>';
				echo '</ol>';
				echo '</div>';

				echo '</div>';


				// ___________________________________________________________________________________________ \\
				// === FOOTER ================================================================================ \\
				// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

				echo "<div class='ai4seo-modal-schema-footer'>";
				ai4seo_echo_wp_kses( ai4seo_get_modal_close_button_tag() );
				ai4seo_echo_wp_kses( ai4seo_get_submit_button_tag( esc_html__( 'Continue', 'ai-for-seo' ), '', 'ai4seo_handle_select_credits_pack(this);' ) );
				echo '</div>';
