<?php
/**
 * Modal Schema: Represents the Customize Pay-As-You-Go modal.
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

# USD currency workaround
$ai4seo_preferred_currency = "USD";

$ai4seo_is_payg_enabled = (bool) ai4seo_get_setting(AI4SEO_SETTING_PAYG_ENABLED);
$ai4seo_payg_stripe_price_id = ai4seo_deep_sanitize(ai4seo_get_setting(AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID));
$ai4seo_payg_credits_threshold = (int) ai4seo_get_setting(AI4SEO_SETTING_PAYG_CREDITS_THRESHOLD);
$ai4seo_payg_daily_budget = (float) ai4seo_get_setting(AI4SEO_SETTING_PAYG_DAILY_BUDGET);
$ai4seo_payg_monthly_budget = (float) ai4seo_get_setting(AI4SEO_SETTING_PAYG_MONTHLY_BUDGET);

// default to first entry if not found
if (!isset(AI4SEO_CREDITS_PACKS[$ai4seo_payg_stripe_price_id])) {
    $ai4seo_payg_stripe_price_id = array_keys(AI4SEO_CREDITS_PACKS)[0];
}

$ai4seo_selected_credits_pack_entry = AI4SEO_CREDITS_PACKS[$ai4seo_payg_stripe_price_id];

$ai4seo_credits_pack_discounted_price = $ai4seo_selected_credits_pack_entry["price_usd"] * (1 - AI4SEO_PAY_AS_YOU_GO_DISCOUNT / 100);

// default values for daily and monthly budget
if (!$ai4seo_payg_daily_budget) {
    $ai4seo_payg_daily_budget = ceil($ai4seo_selected_credits_pack_entry["price_usd"] * 3);
}

if (!$ai4seo_payg_monthly_budget) {
    $ai4seo_payg_monthly_budget = ceil($ai4seo_selected_credits_pack_entry["price_usd"] * 10);
}


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo esc_html__("Customize Pay-As-You-Go", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";

    // form description
    if ($ai4seo_is_payg_enabled) {
        echo esc_html__("You have Pay-As-You-Go enabled. Use the following form to customize the settings.", "ai-for-seo");
    } else {
        echo esc_html__("Please use the form below to customize your Pay-As-You-Go settings, then click 'Enable P-A-Y-G' to activate them.", "ai-for-seo");
    }

    echo "<br><br>";

    echo "<div class='ai4seo-form ai4seo-small-form ai4seo-no-borders'>";

        echo "<div class='ai4seo-form-section'>";

            // === STRIPE PRICE ID =========================================================================== \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID);

            $ai4seo_this_setting_description = esc_html__("The amount of Credits that will be automatically purchased whenever your Credits balance falls below the threshold.", "ai-for-seo");

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Amount Per Refill", "ai-for-seo") . ":</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<select name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' id='" . esc_attr($ai4seo_this_prefixed_input_id) . "' onchange='ai4seo_handle_payg_form_change();'/>";

                    foreach (AI4SEO_CREDITS_PACKS AS $ai4seo_this_payg_stripe_price_id => $ai4seo_this_credits_pack_entry) {
                        $ai4seo_this_price_usd = $ai4seo_this_credits_pack_entry["price_usd"];
                        $ai4seo_this_discount_price_usd = $ai4seo_this_price_usd * (1 - AI4SEO_PAY_AS_YOU_GO_DISCOUNT / 100);
                        $ai4seo_this_entry_is_pre_selected = $ai4seo_payg_stripe_price_id === $ai4seo_this_payg_stripe_price_id;
                        $ai4seo_this_credits_amount = $ai4seo_this_credits_pack_entry["credits_amount"];

                        echo "<option value='" . esc_attr($ai4seo_this_payg_stripe_price_id) . "' " . ($ai4seo_this_entry_is_pre_selected ? "selected" : "") . " data-price='" . esc_attr(number_format_i18n($ai4seo_this_discount_price_usd, 2)) . "' data-reference-price='" . esc_attr(number_format_i18n($ai4seo_this_price_usd, 2)) . "' data-credits-amount='" . esc_attr($ai4seo_this_credits_amount) . "'>";
                            echo esc_html(number_format_i18n($ai4seo_this_credits_amount, 0)) . " Credits (" . esc_html($ai4seo_preferred_currency) . " " . esc_html(number_format_i18n($ai4seo_this_discount_price_usd, 2)) . ")";
                        echo "</option>";
                    }

                    echo "</select>";

                    // Description
                    /*echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";*/
                echo "</div>";
            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === THRESHOLD ================================================================================= \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_PAYG_CREDITS_THRESHOLD);

            $ai4seo_this_setting_description = esc_html__("The threshold (Credits) at which the Credits will be automatically purchased.", "ai-for-seo");

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Threshold (Credits)", "ai-for-seo") . ":</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<input type='number' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' id='" . esc_attr($ai4seo_this_prefixed_input_id) . "' value='" . esc_attr($ai4seo_payg_credits_threshold) . "' min='5' max='99999' step='5' minlength='1' maxlength='5' required onkeyup='ai4seo_handle_payg_form_change();'>";

                    // Description
                    /*echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";*/
                echo "</div>";
            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === DAILY BUDGET ============================================================================= \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_PAYG_DAILY_BUDGET);

            $ai4seo_this_setting_description = esc_html__("The maximum amount of Credits that can be spent within a 24-hour period.", "ai-for-seo");

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Daily Budget", "ai-for-seo") . " (" . esc_html($ai4seo_preferred_currency) . "):</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<input type='number' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' id='" . esc_attr($ai4seo_this_prefixed_input_id) . "' value='" . esc_attr($ai4seo_payg_daily_budget) . "' min='1' max='99999' step='1' minlength='1' maxlength='5' required onkeyup='ai4seo_handle_payg_form_change();'>";

                    // Description
                    /*echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";*/
                echo "</div>";
            echo "</div>";

            echo "<hr class='ai4seo-form-item-divider'>";


            // === MONTHLY BUDGET ============================================================================= \\

            $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_PAYG_MONTHLY_BUDGET);

            $ai4seo_this_setting_description = esc_html__("The maximum amount of Credits that can be spent per calendar month.", "ai-for-seo");

            echo "<div class='ai4seo-form-item'>";
                echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>" . esc_html__("Monthly Budget", "ai-for-seo") . " (" . esc_html($ai4seo_preferred_currency) . "):</label>";
                echo "<div class='ai4seo-form-item-input-wrapper'>";
                    echo "<input type='number' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' id='" . esc_attr($ai4seo_this_prefixed_input_id) . "' value='" . esc_attr($ai4seo_payg_monthly_budget) . "' min='1' max='999999' step='1' minlength='1' maxlength='6' required onkeyup='ai4seo_handle_payg_form_change();'>";

                    // Description
                    /*echo "<p class='ai4seo-form-item-description'>";
                        echo ai4seo_wp_kses($ai4seo_this_setting_description);
                    echo "</p>";*/
                echo "</div>";
            echo "</div>";

        echo "</div>";


        // === SUMMARY ================================================================================= \\

        echo "<div class='ai4seo-pay-as-you-go-summary-container'>";
            echo "<h3>" . esc_html__("Summary", "ai-for-seo") . "</h3>";
            echo "<ol>";
                echo "<li>";
                    // PAYG comes always with AI4SEO_PAY_AS_YOU_GO_DISCOUNT% discount
                    echo ai4seo_wp_kses(sprintf(
                        __("All Pay-As-You-Go purchases come with <strong>%s%% off</strong>.", "ai-for-seo"),
                        esc_html(AI4SEO_PAY_AS_YOU_GO_DISCOUNT)
                    ));
                echo "</li>";
                echo "<li>";
                    echo sprintf(
                        esc_html__("I will automatically purchase %s Credits for %s whenever my Credits balance falls below %s Credits.", "ai-for-seo"),
                        "<strong><span id='ai4seo-payg-summary-credits-amount'>" . esc_html(AI4SEO_CREDITS_PACKS[$ai4seo_payg_stripe_price_id]["credits_amount"] ?? 0) . "</span></strong>",
                        "<span style='text-decoration: line-through; color: #555;'>" . esc_html($ai4seo_preferred_currency) . " <span id='ai4seo-payg-summary-reference-price'>" . esc_html(number_format_i18n($ai4seo_selected_credits_pack_entry["price_usd"], 2)) . "</span></span> " .
                        "<strong>" . esc_html($ai4seo_preferred_currency) . " <span id='ai4seo-payg-summary-price'>" . esc_html(number_format_i18n($ai4seo_credits_pack_discounted_price, 2)) . "</span></strong>",
                        "<strong><span id='ai4seo-payg-summary-threshold'>" . esc_html($ai4seo_payg_credits_threshold) . "</span></strong>",
                    );
                echo "</li>";
                echo "<li>";
                    echo ai4seo_wp_kses(sprintf(
                        __("I will never spend more than %s within a <strong>24-hour period</strong>.", "ai-for-seo"),
                        "<strong>" . esc_html($ai4seo_preferred_currency) . " <span id='ai4seo-payg-summary-daily-budget'>" . esc_html(number_format_i18n($ai4seo_payg_daily_budget, 2)) . "</span></strong>",
                    ));
                    echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag(esc_html__("Including manually purchased Credits packs.", "ai-for-seo")));
                echo "</li>";
                echo "<li>";
                    echo ai4seo_wp_kses(sprintf(
                        __("I will never spend more than %s per <strong>calendar month</strong>.", "ai-for-seo"),
                        "<strong>" . esc_html($ai4seo_preferred_currency) . " <span id='ai4seo-payg-summary-monthly-budget'>" . esc_html(number_format_i18n($ai4seo_payg_monthly_budget, 2)) . "</span></strong>",
                    ));
                    echo ai4seo_wp_kses(ai4seo_get_icon_with_tooltip_tag(esc_html__("Including manually purchased Credits packs. Resets on the 1st of each month, CET – Central European Time.", "ai-for-seo")));
                echo "</li>";
            echo "</ol>";
        echo "</div>";


        // === CONFIRMATION CHECKBOX ================================================================================= \\

        $ai4seo_this_prefixed_input_id = ai4seo_get_prefixed_input_name(AI4SEO_SETTING_PAYG_ENABLED);

        echo "<div style='" . ($ai4seo_is_payg_enabled ? "display: none;" : "margin-top: 2.5rem;") . "'>";

            echo "<div class='ai4seo-confirmation-checkbox-container'>";
                echo "<div class='ai4seo-confirmation-checkbox-container-left'>";
                    echo "<input type='checkbox' id='" . esc_attr($ai4seo_this_prefixed_input_id) . "' name='" . esc_attr($ai4seo_this_prefixed_input_id) . "' class='ai4seo-payg-confirmation-checkbox ai4seo-single-checkbox' value='1' required " . ($ai4seo_is_payg_enabled ? "checked" : "") . ">";
                echo "</div>";
                echo "<div class='ai4seo-confirmation-checkbox-container-right'>";
                    echo "<label for='" . esc_attr($ai4seo_this_prefixed_input_id) . "'>";
                        echo esc_html__("I have reviewed and confirmed the settings above and I want to enable Pay-As-You-Go now.", "ai-for-seo");
                    echo "</label>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</div>";

echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Close", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_close_modal_by_child(this)"));

    if ($ai4seo_is_payg_enabled) {
        echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Submit", "ai-for-seo"), "ai4seo-success-button", "ai4seo_handle_payg_submit(this);"));
    } else {
        echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Enable P-A-Y-G", "ai-for-seo"), "ai4seo-success-button", "ai4seo_handle_payg_submit(this);"));
    }

echo "</div>";