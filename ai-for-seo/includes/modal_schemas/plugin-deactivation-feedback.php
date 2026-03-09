<?php
/**
 * Modal Schema: Deactivation feedback.
 *
 * @since 2.0
 */

if (!defined("ABSPATH")) {
    exit;
}

// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (!ai4seo_can_manage_this_plugin()) {
    return;
}

$ai4seo_claimed_feedback_offer = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER);


// ___________________________________________________________________________________________ \\
// === OUTPUT: MODAL HEADLINE ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-headline'>";
    echo esc_html__("Before you deactivate...", "ai-for-seo");
echo "</div>";


// ___________________________________________________________________________________________ \\
// === OUTPUT: MODAL CONTENT ================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-content'>";
    echo "<div class='ai4seo-plugin-deactivation-feedback-modal'>";

        echo "<div class='ai4seo-plugin-deactivation-feedback-intro'>";
            echo esc_html__("We'd love to know why you're deactivating AI for SEO. Your feedback helps us improve the plugin!", "ai-for-seo");
        echo "</div>";

        echo "<div class='ai4seo-medium-gap'></div>";

        echo "<div class='ai4seo-plugin-deactivation-feedback-reason'>";
            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='just_testing_or_temporary' checked>";
                echo esc_html__("Just testing / temporary", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='not_satisfied_with_ai_text_quality'>";
                echo esc_html__("Not satisfied with AI text quality", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='too_expensive'>";
                echo esc_html__("Too expensive", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='missing_feature'>";
                echo esc_html__("Missing feature", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='hard_to_use'>";
                echo esc_html__("Hard to use", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='bug_or_error'>";
                echo esc_html__("Bug / error", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='performance_issues'>";
                echo esc_html__("Performance issues", "ai-for-seo");
            echo "</label>";

            echo '<br>';

            echo "<label>";
                echo "<input type='radio' name='ai4seo_plugin_deactivation_feedback_reason' value='other'>";
                echo esc_html__("Other (please specify)", "ai-for-seo");
            echo "</label>";
        echo "</div>";

        echo "<div class='ai4seo-medium-gap'></div>";

        echo "<div class='ai4seo-plugin-deactivation-feedback-conditional' style='display: none;'>";
            echo "<textarea class='ai4seo-textarea ai4seo-plugin-deactivation-feedback-message ai4seo-auto-resize-textarea' id='ai4seo-plugin-deactivation-feedback-message' name='ai4seo_plugin_deactivation_feedback_message' maxlength='2000' placeholder='" . esc_attr__("What price would feel reasonable for your usage?", "ai-for-seo") . "'></textarea>";
        echo "</div>";
    echo "</div>";

    if (!$ai4seo_claimed_feedback_offer) {
        echo "<div class='ai4seo-medium-gap'></div>";

        echo "<div class='ai4seo-plugin-deactivation-feedback-conditional' style='display: none;'>";
        echo sprintf(
        /* translators: 1: free credits, 2: discount percentage */
            esc_html__('If you’re open to it, we can add %1$s free credits and %2$s%% off your next purchase.', "ai-for-seo"),
            "<strong>" . esc_html(AI4SEO_GIVING_FEEDBACK_CREDITS) . "</strong>",
            "<strong>" . esc_html(AI4SEO_GIVING_FEEDBACK_DISCOUNT) . "</strong>"
        );
        echo ' ';
        ai4seo_echo_wp_kses(
        /* translators: 1: claim offer button, 2: contact us button */
            sprintf('%1$s or %2$s',
                ai4seo_get_small_icon_button_tag('gift', esc_html__("Claim offer", "ai-for-seo"), "", "ai4seo_submit_feedback(this, 'claim_offer');"),
                ai4seo_get_contact_us_button('', 'ai4seo-small-button')
                )
            );
        echo "</div>";
    }

    echo "<div class='ai4seo-medium-gap'></div>";

    // a warning message that mentions that after deactivation, AI for SEO is no longer able to output generated metadata on your website anymore
    echo "<div>";
        ai4seo_echo_wp_kses(ai4seo_get_svg_tag('triangle-exclamation', esc_html__("Warning", "ai-for-seo")));
        echo ' ';
        echo esc_html__("Depending on your settings, meta tags may no longer be output after deactivation.", "ai-for-seo");
    echo "</div>";
echo "</div>";


// ___________________________________________________________________________________________ \\
// === OUTPUT: MODAL FOOTER ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-modal-schema-footer'>";
    ai4seo_echo_wp_kses(ai4seo_get_modal_close_button_tag(esc_html__("Cancel", "ai-for-seo"), "ai4seo-secondary-button"));
    ai4seo_echo_wp_kses(ai4seo_get_submit_button_tag(esc_html__("Deactivate", "ai-for-seo"), "", "ai4seo_submit_feedback(this, 'deactivate');"));
echo "</div>";
