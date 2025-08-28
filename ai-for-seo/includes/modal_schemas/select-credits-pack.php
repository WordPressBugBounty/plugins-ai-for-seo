<?php
/**
 * Modal Schema: Represents the Select Credits Pack modal.
 *
 * @since 2.0
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!ai4seo_can_manage_this_plugin()) {
    return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

#$ai4seo_preferred_currency = ai4seo_deep_sanitize(ai4seo_get_setting(AI4SEO_SETTING_PREFERRED_CURRENCY));
$ai4seo_preferred_currency = "USD"; # todo: implement proper currency selection
$ai4seo_recommended_credits_pack_size = (int) ai4seo_get_recommended_credits_pack_size_by_num_missing_entries();
$ai4seo_credits_packs = ai4seo_get_credits_packs();


// === DISCOUNT ============================================================================= \\

$ai4seo_current_discount = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT);
$ai4seo_current_discount_percentage = $ai4seo_current_discount["percentage"] ?? 0;


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo esc_html__("Select Credits Pack", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";

    // === SELECT CURRENCY ====================================================================== \\

    # todo: implement this

    // === SELECT CREDITS PACK SIZE ============================================================= \\

    echo esc_html__("Select the amount of Credits for your needs.", "ai-for-seo") . " ";

    // current discount
    ai4seo_echo_current_discount();

    if (!empty($ai4seo_current_discount["voucher_code"])) {
        echo "<br>";
        echo esc_html__("Enter this voucher code during checkout to apply the discount: ", "ai-for-seo") . "<br>";
        echo ai4seo_wp_kses(ai4seo_get_voucher_code_output($ai4seo_current_discount["voucher_code"]));
    }

    echo "<div class='ai4seo-credits-pack-selection-container'>";

        $ai4seo_entry_counter = 0;
        $ai4seo_pre_selected_credits_pack_entry = array();

        foreach ($ai4seo_credits_packs AS $ai4seo_this_payg_stripe_price_id => $ai4seo_credits_pack_entry) {
            $ai4seo_entry_counter++;
            $ai4seo_this_credits_amount = (int) $ai4seo_credits_pack_entry["credits_amount"];
            $ai4seo_this_price_usd = $ai4seo_credits_pack_entry["price_usd"];
            $ai4seo_this_reference_price_usd = $ai4seo_credits_pack_entry["reference_price_usd"];
            $ai4seo_this_price_usd = $ai4seo_current_discount_percentage ? $ai4seo_this_price_usd * (1 - ($ai4seo_current_discount_percentage / 100)) : $ai4seo_this_price_usd;
            $ai4seo_this_discount_percentage = round((1 - ($ai4seo_this_price_usd / $ai4seo_this_reference_price_usd)) * 100);
            $ai4seo_this_entry_is_pre_selected = $ai4seo_this_credits_amount === $ai4seo_recommended_credits_pack_size;
            $ai4seo_this_entry_is_recommendation = $ai4seo_this_credits_amount === $ai4seo_recommended_credits_pack_size;

            // floor $ai4seo_this_price_usd at second decimal to fix rounding issues
            $ai4seo_this_price_usd = floor(round($ai4seo_this_price_usd * 100, 1)) / 100;

            $ai4seo_cost_per_page = $ai4seo_this_price_usd / ($ai4seo_this_credits_amount / AI4SEO_CREDITS_FLAT_COST);
            $ai4seo_cost_per_media_file = $ai4seo_this_price_usd / ($ai4seo_this_credits_amount / AI4SEO_CREDITS_FLAT_COST);

            if ($ai4seo_this_entry_is_pre_selected) {
                $ai4seo_pre_selected_credits_pack_entry = $ai4seo_credits_pack_entry;
                $ai4seo_pre_selected_credits_pack_entry["cost_per_page"] = $ai4seo_cost_per_page;
                $ai4seo_pre_selected_credits_pack_entry["cost_per_media_file"] = $ai4seo_cost_per_media_file;
                $ai4seo_pre_selected_credits_pack_entry["credits_amount"] = $ai4seo_this_credits_amount;
            }

            echo "<div class='ai4seo-credits-pack-selection-item" . ($ai4seo_this_entry_is_pre_selected ? " ai4seo-credits-pack-selection-item-selected ai4seo-credits-pack-selection-item-most-popular" : "") . "' onclick='ai4seo_handle_credits_pack_selection(this);' data-credits-amount='" . esc_attr($ai4seo_this_credits_amount) . "' data-price='" . esc_attr($ai4seo_this_price_usd) . "' data-currency='" . esc_attr($ai4seo_preferred_currency) . "' data-cost-per-page='" . esc_attr(number_format_i18n($ai4seo_cost_per_page, 2)) . "' data-cost-per-media-file='" . esc_attr(number_format_i18n($ai4seo_cost_per_media_file, 2)) . "'>";

                // most popular label
                if ($ai4seo_this_entry_is_recommendation) {
                    echo "<div class='ai4seo-credits-pack-selection-item-most-popular-label'>";
                        echo esc_html__("Most Popular – Best for Your Website Size", "ai-for-seo");
                    echo "</div>";
                }

                echo "<div class='ai4seo-credits-pack-selection-item-left-side'>";

                    echo "<div class='ai4seo-credits-pack-selection-item-radio-button'>";
                        echo "<input type='radio' name='ai4seo-credits-pack-selection[]' value='" . esc_attr($ai4seo_this_payg_stripe_price_id) . "' " . ($ai4seo_this_entry_is_pre_selected ? "checked" : "") . ">";
                    echo "</div>";

                    echo "<div class='ai4seo-credits-pack-selection-item-credits-amount'>";
                        echo esc_html(number_format_i18n($ai4seo_this_credits_amount, 0));
                        echo " " . esc_html__("Credits", "ai-for-seo");
                    echo "</div>";

                echo "</div>";

                echo "<div class='ai4seo-credits-pack-selection-item-right-side'>";

                    if ($ai4seo_this_discount_percentage > 0) {
                        echo "<div class='ai4seo-credits-pack-selection-item-discount-percentage'>";
                            echo sprintf(esc_html__("%s%% off", "ai-for-seo"), esc_html($ai4seo_this_discount_percentage));
                        echo "</div>";
                    }

                    if ($ai4seo_this_price_usd != $ai4seo_this_reference_price_usd) {
                        echo "<div class='ai4seo-credits-pack-selection-item-reference-price'>";
                            echo esc_html($ai4seo_preferred_currency) . " " . esc_html(number_format_i18n($ai4seo_this_reference_price_usd, 2));
                        echo "</div>";
                    }

                    echo "<div class='ai4seo-credits-pack-selection-item-price'>";
                        echo esc_html($ai4seo_preferred_currency) . " " . esc_html(number_format_i18n($ai4seo_this_price_usd, 2)) . "*";
                    echo "</div>";
                echo "</div>";
            echo "</div>";

            // show more options button
            if ($ai4seo_entry_counter === 3) {
                echo "<center>";
                    echo ai4seo_wp_kses(ai4seo_get_small_button_tag("#", "angle-down", __("Show more options", "ai-for-seo") . " " . ai4seo_get_svg_tag("angle-down"), "ai4seo-credits-pack-show-more-options-button", "jQuery(this).parent().hide();jQuery(this).parent().next().show();"));
                echo "</center>";
                echo "<div style='display: none;'>";
            }
        }

        echo "</div>"; // close display-none-container

        // Taxes may apply
        echo "<div style='float: right; margin-top: 10px; font-size: smaller'>";
            echo "* " . esc_html__("Taxes may apply", "ai-for-seo");
        echo "</div>";

    echo "</div>";


    // === COST PER ENTRY ================================================================================= \\

    echo "<div class='ai4seo-credits-pack-cost-per-entry-container'>";
        echo "<h4>" . esc_html__("Cost Breakdown", "ai-for-seo") . "</h4>";
        echo "<ol>";
            echo "<li>";
                echo sprintf(esc_html__("Each page/post will cost you %s on average to generate metadata for.", "ai-for-seo"), "<strong class='ai4seo-credits-pack-cost-per-page'>" . esc_html($ai4seo_preferred_currency) . " " . esc_html(number_format_i18n($ai4seo_pre_selected_credits_pack_entry["cost_per_page"] ?? 0, 2)) . "</strong>");
            echo "</li>";
            echo "<li>";
                echo sprintf(esc_html__("Each media file will cost you %s on average to generate media attributes for.", "ai-for-seo"), "<strong class='ai4seo-credits-pack-cost-per-media-file'>" . esc_html($ai4seo_preferred_currency) . " " . esc_html(number_format_i18n($ai4seo_pre_selected_credits_pack_entry["cost_per_media_file"] ?? 0, 2)) . "</strong>");
            echo "</li>";
        echo "</ol>";
    echo "</div>";

echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Close", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_close_modal_by_child(this)"));
    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Continue", "ai-for-seo"), "ai4seo-success-button", "ai4seo_handle_select_credits_pack(this);"));
echo "</div>";