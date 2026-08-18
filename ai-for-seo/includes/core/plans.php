<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region PLANS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

function ai4seo_get_available_plans(): array {
	return array(
		'free' => array(
			'name'    => esc_html__( 'Free', 'ai-for-seo' ),
			'credits' => 100,
		),
		's'    => array(
			'name'    => esc_html__( 'Basic', 'ai-for-seo' ),
			'credits' => 500,
		),
		'm'    => array(
			'name'    => esc_html__( 'Pro', 'ai-for-seo' ),
			'credits' => 1500,
		),
		'l'    => array(
			'name'    => esc_html__( 'Premium', 'ai-for-seo' ),
			'credits' => 5000,
		),
	);
}

// =========================================================================================== \\

/**
 * Normalize plan identifiers and textual plan names to the stored plan keys.
 *
 * @param mixed $plan Plan identifier or plan name.
 * @return string Normalized plan key, or an empty string for unknown plans.
 */
function ai4seo_normalize_plan_identifier( $plan ): string {
	if ( ! is_scalar( $plan ) ) {
		return '';
	}

	// Keep all plan comparisons on the compact keys stored in the subscription environmental variable.
	$plan         = sanitize_key( (string) $plan );
	$plan_aliases = array(
		'free'    => 'free',
		's'       => 's',
		'basic'   => 's',
		'm'       => 'm',
		'pro'     => 'm',
		'l'       => 'l',
		'premium' => 'l',
	);

	return $plan_aliases[ $plan ] ?? '';
}

// =========================================================================================== \\

/**
 * Function to retrieve the given plans amount of credits
 *
 * @param mixed $plan The plan value.
 * @return int
 */
function ai4seo_get_plan_credits( $plan ): int {
	$available_plans = ai4seo_get_available_plans();
	$plan            = ai4seo_normalize_plan_identifier( $plan );

	return $available_plans[ $plan ]['credits'] ?? $available_plans['free']['credits'];
}

// =========================================================================================== \\

/**
 * Return the name of the given plan
 *
 * @param mixed $plan The plan value.
 * @return string
 */
function ai4seo_get_plan_name( $plan ): string {
	$available_plans = ai4seo_get_available_plans();
	$plan            = ai4seo_normalize_plan_identifier( $plan );

	return $available_plans[ $plan ]['name'] ?? $available_plans['free']['name'];
}

// =========================================================================================== \\

/**
 * Return the current custom instruction character limit.
 *
 * @return int Maximum number of custom instruction characters for the current account.
 */
function ai4seo_get_custom_instructions_length_limit(): int {
	// Custom instructions are capped by subscription status, not by one-off credit balance.
	if ( ai4seo_user_has_active_subscription() ) {
		return AI4SEO_CUSTOM_INSTRUCTIONS_SUBSCRIPTION_LENGTH_LIMIT;
	}

	return AI4SEO_CUSTOM_INSTRUCTIONS_FREE_LENGTH_LIMIT;
}

// =========================================================================================== \\

/**
 * Return a plan badge, optionally forced into the clickable upgrade state.
 *
 * The forced mode lets inline upgrade prompts reuse the existing badge styling and modal behavior.
 *
 * @param string $plan          Plan identifier.
 * @param bool   $force_upgrade Whether the badge should open the upgrade modal even if the plan is active.
 * @return string Badge HTML.
 */
function ai4seo_get_plan_badge( $plan, bool $force_upgrade = false ): string {
	if ( ai4seo_prevent_loops( __FUNCTION__ ) ) {
		ai4seo_debug_message( 308030688, 'Prevented loop', true );
		return '';
	}

	// Resolve the display state once so regular feature badges and forced upgrade prompts share the same rendering path.
	$css_class                   = 'ai4seo-plan-badge';
	$user_has_at_least_this_plan = ai4seo_user_has_at_least_plan( $plan );
	$show_upgrade_state          = $force_upgrade || ! $user_has_at_least_this_plan;
	$onclick                     = '';

	// Keep the existing per-plan label and color mapping centralized in this helper.
	switch ( $plan ) {
		case 'free':
			$css_class  .= ' ai4seo-plan-badge-free';
			$badge_label = esc_html__( 'Free', 'ai-for-seo' );
			$alt_text    = esc_html__( 'Free plan', 'ai-for-seo' );
			break;
		case 's':
			$css_class  .= ' ai4seo-plan-badge-basic';
			$badge_label = esc_html__( 'Basic', 'ai-for-seo' );

			if ( $show_upgrade_state ) {
				$alt_text = esc_html__( 'You need the Basic Plan or higher to use this feature', 'ai-for-seo' );
			}

			break;
		case 'm':
			$css_class  .= ' ai4seo-plan-badge-pro';
			$badge_label = esc_html__( 'Pro', 'ai-for-seo' );

			if ( $show_upgrade_state ) {
				$alt_text = esc_html__( 'You need the Pro Plan or higher to use this feature', 'ai-for-seo' );
			}
			break;
		case 'l':
			$css_class .= ' ai4seo-plan-badge-premium';

			$badge_label = esc_html__( 'Premium', 'ai-for-seo' );

			if ( $show_upgrade_state ) {
				$alt_text = esc_html__( 'You need the Premium Plan to use this feature', 'ai-for-seo' );
			}
			break;
		default:
			return '';
	}

	// Forced or insufficient-plan badges are buttons because they must open the credits/subscription modal.
	if ( ! $show_upgrade_state ) {
		$alt_text = esc_html__( 'You can use this feature with your current plan', 'ai-for-seo' );
	} else {
		$badge_label .= ' - ' . esc_html__( 'Upgrade now', 'ai-for-seo' );
		$css_class   .= ' ai4seo-clickable';
		$onclick      = 'ai4seo_open_get_more_credits_modal();';
	}

	// Use the same content for span and button variants so existing CSS keeps both states aligned.
	if ( ! $show_upgrade_state ) {
		$output = "<span class='" . esc_attr( $css_class ) . "' title='" . esc_attr( $alt_text ) . "'>";
	} else {
		$output = "<button type='button' class='" . esc_attr( $css_class ) . "' title='" . esc_attr( $alt_text ) . "' onclick='" . esc_attr( $onclick ) . "'>";
	}

		$output .= ai4seo_get_svg_tag( 'crown', $alt_text, 'ai4seo-plan-badge-icon' );
		$output .= esc_html( $badge_label );

	// Close the element type selected above without duplicating the badge body.
	if ( ! $show_upgrade_state ) {
		$output .= '</span>';
	} else {
		$output .= '</button>';
	}

	return $output;
}

// =========================================================================================== \\

/**
 * Return an inline subscription upgrade prompt with a pricing button.
 *
 * @param string $plan              Minimum required subscription plan.
 * @param string $prompt_type       Prompt text variant.
 * @param string $wrapper_css_class Optional wrapper CSS class.
 * @return string Upgrade prompt HTML.
 */
function ai4seo_get_subscription_upgrade_prompt_tag( string $plan, string $prompt_type = 'use_this_feature', string $wrapper_css_class = '' ): string {
	// Normalize aliases such as "basic" before selecting copy or comparing tier order.
	$ai4seo_normalized_plan = ai4seo_normalize_plan_identifier( $plan );

	// Free or unknown plan identifiers should not produce a paid upgrade prompt.
	if ( '' === $ai4seo_normalized_plan || 'free' === $ai4seo_normalized_plan ) {
		return '';
	}

	// Keep the plan name as the only sentence placeholder so translators control each full prompt variant.
	$ai4seo_plan_name            = ai4seo_get_plan_name( $ai4seo_normalized_plan );
	$ai4seo_plan_name_html       = '<strong>' . esc_html( $ai4seo_plan_name ) . '</strong>';
	$ai4seo_plan_has_higher_tier = in_array( $ai4seo_normalized_plan, array( 's', 'm' ), true );

	// Use complete translation strings for the "or higher" variants instead of assembling sentence fragments.
	switch ( $prompt_type ) {
		case 'generation_length_options':
			// Free accounts need both paid thresholds explained, while Basic accounts only need the Pro threshold.
			$ai4seo_pro_plan_name_html = '<strong>' . esc_html( ai4seo_get_plan_name( 'm' ) ) . '</strong>';

			if ( 's' === $ai4seo_normalized_plan ) {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %1$s: Basic plan name wrapped in bold markup. %2$s: Pro plan name wrapped in bold markup. */
					esc_html__( 'Option 4 requires a %1$s plan or higher. Option 5 requires a %2$s plan or higher.', 'ai-for-seo' ),
					$ai4seo_plan_name_html,
					$ai4seo_pro_plan_name_html
				);
			} else {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %s: Pro plan name wrapped in bold markup. */
					esc_html__( 'Option 5 requires a %s plan or higher.', 'ai-for-seo' ),
					$ai4seo_pro_plan_name_html
				);
			}
			break;
		case 'custom_instructions_1000_chars':
			// Keep the limit placeholder shared by both prompt variants so the copy stays consistent.
			$ai4seo_custom_instructions_subscription_length_limit_formatted = esc_html(
				ai4seo_format_number_i18n( AI4SEO_CUSTOM_INSTRUCTIONS_SUBSCRIPTION_LENGTH_LIMIT )
			);

			if ( $ai4seo_plan_has_higher_tier ) {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %1$s: Subscription plan name wrapped in bold markup. %2$s: Character limit. */
					esc_html__( 'Upgrade to %1$s or higher to enter up to %2$s chars.', 'ai-for-seo' ),
					$ai4seo_plan_name_html,
					$ai4seo_custom_instructions_subscription_length_limit_formatted
				);
			} else {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %1$s: Subscription plan name wrapped in bold markup. %2$s: Character limit. */
					esc_html__( 'Upgrade to %1$s to enter up to %2$s chars.', 'ai-for-seo' ),
					$ai4seo_plan_name_html,
					$ai4seo_custom_instructions_subscription_length_limit_formatted
				);
			}
			break;
		case 'use_this_feature':
		default:
			if ( $ai4seo_plan_has_higher_tier ) {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %s: Subscription plan name wrapped in bold markup. */
					esc_html__( 'Upgrade to %s or higher to use this feature.', 'ai-for-seo' ),
					$ai4seo_plan_name_html
				);
			} else {
				$ai4seo_upgrade_text = sprintf(
					/* translators: %s: Subscription plan name wrapped in bold markup. */
					esc_html__( 'Upgrade to %s to use this feature.', 'ai-for-seo' ),
					$ai4seo_plan_name_html
				);
			}
			break;
	}

	// Reuse the same direct pricing URL and click tracking used by subscription CTAs elsewhere.
	$ai4seo_api_username        = ai4seo_robhub_api()->get_api_username();
	$ai4seo_purchase_plan_url   = ai4seo_get_purchase_plan_url( $ai4seo_api_username );
	$ai4seo_wrapper_css_class   = trim( $wrapper_css_class );
	$ai4seo_upgrade_button_html = ai4seo_get_a_tag_icon_button_tag(
		$ai4seo_purchase_plan_url,
		'',
		'_blank',
		'crown',
		esc_html_x( 'Upgrade now', 'subscription upgrade prompt button', 'ai-for-seo' ),
		'ai4seo-primary-button ai4seo-small-button',
		'ai4seo_track_subscription_pricing_visit();'
	);

	$ai4seo_upgrade_prompt_html = $ai4seo_upgrade_text . ' ' . $ai4seo_upgrade_button_html;

	// Let callers opt into local layout without duplicating the prompt and button generation.
	if ( '' !== $ai4seo_wrapper_css_class ) {
		$ai4seo_upgrade_prompt_html = "<span class='" . esc_attr( $ai4seo_wrapper_css_class ) . "'>" . $ai4seo_upgrade_prompt_html . '</span>';
	}

	return $ai4seo_upgrade_prompt_html;
}


// endregion
// ___________________________________________________________________________________________.
