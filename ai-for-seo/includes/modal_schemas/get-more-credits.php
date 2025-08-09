<?php
/**
 * Modal Schema: Represents the Get More Credits modal.
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

$ai4seo_robhub_subscription = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION);

$ai4seo_robhub_subscription_plan = $ai4seo_robhub_subscription["plan"] ?? "free";
$ai4seo_robhub_subscription_plan_name = ai4seo_get_plan_name($ai4seo_robhub_subscription_plan);

$ai4seo_robhub_subscription_next_credits_refresh_date_and_time = $ai4seo_robhub_subscription["next_credits_refresh"] ?? false;
$ai4seo_robhub_subscription_next_credits_refresh_timestamp = $ai4seo_robhub_subscription_next_credits_refresh_date_and_time
    ? strtotime($ai4seo_robhub_subscription_next_credits_refresh_date_and_time) : 0;
$ai4seo_robhub_subscription_next_credits_refresh_formatted_text = ai4seo_format_unix_timestamp($ai4seo_robhub_subscription_next_credits_refresh_timestamp);

$ai4seo_robhub_subscription_end_date_and_time = $ai4seo_robhub_subscription["subscription_end"] ?? false;
$ai4seo_robhub_subscription_end_timestamp = $ai4seo_robhub_subscription_end_date_and_time
    ? strtotime($ai4seo_robhub_subscription_end_date_and_time) : 0;
$ai4seo_current_subscription_end_formatted_text = ai4seo_format_unix_timestamp($ai4seo_robhub_subscription_end_timestamp);

$ai4seo_user_is_on_free_plan = ($ai4seo_robhub_subscription_plan == "free") || $ai4seo_robhub_subscription_end_timestamp < time();
$ai4seo_robhub_subscription_plan_css_class = ($ai4seo_user_is_on_free_plan ? "ai4seo-black-message" : "ai4seo-green-message");

// double check if subscription should be renewed
$ai4seo_robhub_subscription_do_renew = $ai4seo_robhub_subscription["do_renew"] ?? false;
$ai4seo_robhub_subscription_do_renew = !$ai4seo_user_is_on_free_plan
    && $ai4seo_robhub_subscription_end_timestamp
    && $ai4seo_robhub_subscription_do_renew == "1";

$ai4seo_robhub_subscription_renew_frequency = $ai4seo_robhub_subscription["renew_frequency"] ?? false;
$ai4seo_robhub_subscription_renew_frequency = $ai4seo_robhub_subscription_do_renew
    ? $ai4seo_robhub_subscription_renew_frequency : false;

$ai4seo_next_free_credits_timestamp = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP);
$ai4seo_robhub_credits_balance = ai4seo_robhub_api()->get_credits_balance();

$ai4seo_is_payg_enabled = (bool) ai4seo_get_setting(AI4SEO_SETTING_PAYG_ENABLED);
$ai4seo_has_purchased_something = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);
$ai4seo_payg_credits_threshold = (int) ai4seo_get_setting(AI4SEO_SETTING_PAYG_CREDITS_THRESHOLD);

$ai4seo_api_username = ai4seo_robhub_api()->get_api_username();


// ___________________________________________________________________________________________ \\
// === HEADLINE ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo esc_html__("How to get more Credits", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === CONTENT =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";
    echo esc_html__("Choose one of the following options to get more Credits (you may also combine):", "ai-for-seo");


    // === CREDITS PACK ================================================================================= \\

    echo "<div class='ai4seo-get-more-credits-section'>";
        echo "<div class='ai4seo-get-more-credits-section-left'>";
            echo "<div class='ai4seo-get-more-credits-section-big-number'>";
                echo "1";
            echo "</div>";
        echo "</div>";

        echo "<div class='ai4seo-get-more-credits-section-right'>";
            echo "<div class='ai4seo-get-more-credits-section-big-title'>";
                echo esc_html__("Credits Pack", "ai-for-seo");
            echo "</div>";

            echo esc_html__("Need more Credits for a one-time job? Choose a Credits Pack that fits your needs.", "ai-for-seo");

            // current discount
            ai4seo_echo_current_discount();

            echo "<br>";

            echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "list", esc_html__("See options", "ai-for-seo"), "ai4seo-success-button", "ai4seo_handle_open_select_credits_pack_modal();"));
        echo "</div>";
    echo "</div>";


    // === SUBSCRIPTION ================================================================================= \\

    echo "<div class='ai4seo-get-more-credits-section'>";
        echo "<div class='ai4seo-get-more-credits-section-left'>";
            echo "<div class='ai4seo-get-more-credits-section-big-number'>";
                echo "2";
            echo "</div>";
        echo "</div>";

        echo "<div class='ai4seo-get-more-credits-section-right'>";
            echo "<div class='ai4seo-get-more-credits-section-big-title'>";
                echo esc_html__("Subscription", "ai-for-seo");
            echo "</div>";

            // FREE PLAN
            if ($ai4seo_user_is_on_free_plan) {
                $ai4seo_purchase_plan_url = ai4seo_get_purchase_plan_url($ai4seo_api_username);

                echo esc_html__("Do you need Credits on a regular basis over a long period? With our annual subscriptions, you’ll receive a set amount of Credits each month at the best possible price.", "ai-for-seo");

                echo "<br><br>";

                echo sprintf(
                    esc_html__("Current status: %s", "ai-for-seo"),
                    "<strong><span class='ai4seo-red-message'>" . esc_html__("Not subscribed yet", "ai-for-seo") . "</span></strong>"
                );

                echo "<br>";

                // Upgrade button
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag($ai4seo_purchase_plan_url, "list", esc_html__("See options", "ai-for-seo"), "ai4seo-success-button", "", "_blank"));
            } else {
                // PAID PLAN
                echo "<div class='ai4seo-subscription-badge'>";
                    echo ai4seo_get_svg_tag("circle-check", "", "ai4seo-dark-green-icon") . " ";
                    echo sprintf(
                        esc_html__("Subscribed to the %s subscription.", "ai-for-seo"),
                        "<strong>" . esc_html($ai4seo_robhub_subscription_plan_name) . "</strong>",
                    );
                echo "</div>";

                echo "<ol>";

                    echo "<li>";
                        echo sprintf(
                            esc_html__("The %s subscription grants you %s Credits per month.", "ai-for-seo"),
                            "<strong>" . esc_html($ai4seo_robhub_subscription_plan_name) . "</strong>",
                            "<strong>" . esc_html(ai4seo_get_plan_credits($ai4seo_robhub_subscription_plan)) . "</strong>",
                        );
                    echo "</li>";

                    if ($ai4seo_robhub_subscription_next_credits_refresh_formatted_text && $ai4seo_robhub_subscription_next_credits_refresh_timestamp > time()) {
                        // subscription-end is more than one month in the future or we are going to renew the plan anyway (e.g. we are on a monthly renew frequency)
                        if ($ai4seo_robhub_subscription_end_timestamp > strtotime("+1 month") || $ai4seo_robhub_subscription_do_renew) {
                            echo "<li>";
                                echo ai4seo_wp_kses(sprintf(
                                    __("Next %s Credits on: %s.", "ai-for-seo"),
                                    "<strong>" . esc_html(ai4seo_get_plan_credits($ai4seo_robhub_subscription_plan)) . "</strong>",
                                    "<strong>" . esc_html($ai4seo_robhub_subscription_next_credits_refresh_formatted_text) . "</strong>",
                                ));
                            echo "</li>";
                        }
                    }

                    echo "<li>";
                        // infos about renewing the plan
                        if ($ai4seo_robhub_subscription_do_renew) {
                                echo ai4seo_wp_kses(sprintf(
                                    __("Your subscription renews on: %s (%s).", "ai-for-seo"),
                                    "<strong>" . esc_html($ai4seo_current_subscription_end_formatted_text) . "</strong>",
                                    "<strong>" . esc_html($ai4seo_robhub_subscription_renew_frequency) . "</strong>",
                                ));
                        } else if ($ai4seo_robhub_subscription_end_timestamp) {
                            // Check if subscription-end is in the past (should never be the case, as the user will fall back to the free plan)
                            if ($ai4seo_robhub_subscription_end_timestamp < time()) {
                                echo "<span class='ai4seo-red-message'>";
                                    echo sprintf(esc_html__("Your subscription was cancelled as of %s", "ai-for-seo"), esc_html($ai4seo_current_subscription_end_formatted_text));
                                echo "</span>";
                            } else {
                                // Check if subscription-end is in the future
                                echo "<span class='ai4seo-red-message'>";
                                    echo sprintf(esc_html__("Your subscription expires on %s", "ai-for-seo"), esc_html($ai4seo_current_subscription_end_formatted_text));
                                echo "</span>";
                            }
                        } else {
                            echo "<span class='ai4seo-red-message'>";
                                echo esc_html__("Current status: Subscription cancelled", "ai-for-seo");
                            echo "</span>";
                        }
                    echo "</li>";
                echo "</ol>";

                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag(AI4SEO_STRIPE_BILLING_URL, "stripe", esc_html__("Manage Subscription", "ai-for-seo"), "ai4seo-success-button", "", "_blank"));
            }
        echo "</div>";
    echo "</div>";


    // === PAY-AS-YOU-GO ================================================================================= \\

    echo "<div class='ai4seo-get-more-credits-section'>";
        echo "<div class='ai4seo-get-more-credits-section-left'>";
            echo "<div class='ai4seo-get-more-credits-section-big-number'>";
                echo "3";
            echo "</div>";
        echo "</div>";

        echo "<div class='ai4seo-get-more-credits-section-right'>";
            echo "<div class='ai4seo-get-more-credits-section-big-title'>";
                echo esc_html__("Pay-As-You-Go", "ai-for-seo");
            echo "</div>";

            echo ai4seo_wp_kses(sprintf(
                __("Automatically refill your Credits balance with a custom amount. Get <strong>%s%% discount</strong> on all refills.", "ai-for-seo"),
                AI4SEO_PAY_AS_YOU_GO_DISCOUNT,
            ));

            echo "<br><br>";

            echo sprintf(
                esc_html__("Current status: %s", "ai-for-seo"),
                ($ai4seo_is_payg_enabled
                    ? "<strong><span class='ai4seo-green-message'>" . esc_html__("Enabled", "ai-for-seo") . "</span></strong>"
                    : "<strong><span class='ai4seo-red-message'>" . esc_html__("Not enabled yet", "ai-for-seo") . "</span></strong>")
            );

            // info on $ai4seo_payg_credits_threshold
            if ($ai4seo_is_payg_enabled) {
                echo ". ";

                if ($ai4seo_robhub_credits_balance >= $ai4seo_payg_credits_threshold) {
                    echo sprintf(
                        esc_html__("Waiting for your Credits balance to drop below or are equal to %s Credits before refilling.", "ai-for-seo"),
                        "<strong>" . esc_html($ai4seo_payg_credits_threshold) . "</strong>"
                    );
                } else {
                    echo sprintf(
                        esc_html__("Your Credits balance is below %s Credits. We are currently processing your refill...", "ai-for-seo"),
                        "<strong>" . esc_html($ai4seo_payg_credits_threshold) . "</strong>"
                    );
                }
            }

            if (!$ai4seo_has_purchased_something) {
                echo ". <strong><span class='ai4seo-red-message'>" . esc_html__("Please purchase a Credits Pack or a subscription first.", "ai-for-seo") . "</span></strong>";
            }

            echo "<br>";

            if ($ai4seo_has_purchased_something) {
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "list", esc_html__("Customize", "ai-for-seo"), "ai4seo-success-button", "ai4seo_handle_open_customize_payg_modal();"));
            } else {
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "list", esc_html__("Customize", "ai-for-seo"), "ai4seo-inactive-button", "ai4seo_open_notification_modal('" . esc_js(esc_html__("Please purchase a Credits Pack or a subscription first.", "ai-for-seo")) . "');"));
            }


            if ($ai4seo_is_payg_enabled) {
                echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Disable", "ai-for-seo"), "ai4seo-abort-button ai4seo-gap-left", "ai4seo_disable_payg(this);"));
            }
        echo "</div>";
    echo "</div>";


    // === FREE CREDITS ================================================================================= \\

    if ($ai4seo_next_free_credits_timestamp) {
        echo "<div class='ai4seo-get-more-credits-section'>";
            echo "<div class='ai4seo-get-more-credits-section-left'>";
                echo "<div class='ai4seo-get-more-credits-section-big-number'>";
                    echo "4";
                echo "</div>";
            echo "</div>";

            echo "<div class='ai4seo-get-more-credits-section-right'>";
                echo "<div class='ai4seo-get-more-credits-section-big-title'>";
                    echo esc_html__("Free Credits", "ai-for-seo");
                echo "</div>";

                $ai4seo_free_plan_credits_amount = ai4seo_get_plan_credits("free");

                echo ai4seo_wp_kses(sprintf(
                    __("We provide you with <strong>%s free Credits each day</strong> if your balance falls below %s Credits. Simply keep using the plugin to receive them automatically.", "ai-for-seo"),
                    esc_html(AI4SEO_DAILY_FREE_CREDITS_AMOUNT),
                    esc_html($ai4seo_free_plan_credits_amount),
                ));

                echo "<br><br>";
                $ai4seo_next_free_credits_seconds_left = ai4seo_get_time_difference_in_seconds($ai4seo_next_free_credits_timestamp);
                echo ai4seo_wp_kses(sprintf(
                    __('Next <span class="ai4seo-green-bubble">+%1$s Credits</span> in <strong>%2$s</strong> if your balance falls below %3$s Credits.', 'ai-for-seo'),
                    esc_html(AI4SEO_DAILY_FREE_CREDITS_AMOUNT),
                    "<span class='ai4seo-countdown' data-time-left='" . esc_attr($ai4seo_next_free_credits_seconds_left) . "' data-trigger='ai4seo_reload_page'>" . esc_html(ai4seo_format_seconds_to_hhmmss_or_days_hhmmss($ai4seo_next_free_credits_seconds_left)) . "</span>",
                    esc_html($ai4seo_free_plan_credits_amount)
                ));
            echo "</div>";
        echo "</div>";
    }
echo "</div>";


// ___________________________________________________________________________________________ \\
// === FOOTER ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    echo ai4seo_wp_kses(ai4seo_get_button_text_link_tag("#", "", esc_html__("Close", "ai-for-seo"), "ai4seo-abort-button", "ai4seo_close_modal_by_child(this)"));
echo "</div>";