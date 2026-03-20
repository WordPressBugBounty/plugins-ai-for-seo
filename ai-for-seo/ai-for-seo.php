<?php
/*
Plugin Name: SOOZ - AI for SEO
Plugin URI: https://sooz.ai
Description: One-Click SEO solution. *SOOZ - AI for SEO* helps your website to rank higher in Web Search results.
Version: 2.3.1
Author: spacecodes
Author URI: https://spa.ce.codes
Text Domain: ai-for-seo
Copyright 2026 spacecodes
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4
*/
if (!defined("ABSPATH")) {
    exit;
}

// workarounds for deactivation and prohibition via URL parameters
if (isset($_GET['deactivate-ai-for-seo'])) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    return;
}

if (isset($_GET['prohibit-ai-for-seo'])) {
    return;
}


// endregion _________________________________________________________________________________ \\
// region CONSTANTS AND VARIABLES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

const AI4SEO_PLUGIN_VERSION_NUMBER = "2.3.1";
const AI4SEO_PLUGIN_NAME = "SOOZ - AI for SEO";
const AI4SEO_SHORT_PLUGIN_NAME = "SOOZ";
const AI4SEO_PLUGIN_DESCRIPTION = 'One-Click SEO solution. *SOOZ - AI for SEO* helps your website to rank higher in Web Search results.';
const AI4SEO_PLUGIN_IDENTIFIER = "ai-for-seo";
const AI4SEO_PLUGIN_AUTHOR_COMPANY_NAME = "Andre Erbis, Space Codes";
const AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION = "AESC";
const AI4SEO_DEFAULT_FALLBACK_LANGUAGE = 'english';
const AI4SEO_POST_PARAMETER_PREFIX = "ai4seo_";
const AI4SEO_TOS_VERSION_TIMESTAMP = 1767426000;
const AI4SEO_TOO_SHORT_CONTENT_LENGTH = 100;
const AI4SEO_MAX_TOTAL_CONTENT_SIZE = 5000;
const AI4SEO_SUPPORT_EMAIL = "support@sooz.ai";
const AI4SEO_OFFICIAL_WEBSITE = "https://sooz.ai";
const AI4SEO_OFFICIAL_PRICING_URL = "https://sooz.ai/pricing";
const AI4SEO_OFFICIAL_CONTACT_URL = "https://sooz.ai/contact";
const AI4SEO_TERMS_AND_CONDITIONS_URL = "https://sooz.ai/terms-and-conditions#plugin";
const AI4SEO_PRIVACY_POLICY_URL = "https://sooz.ai/privacy-policy#plugin";
const AI4SEO_PLUGINS_OFFICIAL_WORDPRESS_ORG_PAGE = "https://wordpress.org/plugins/ai-for-seo/";
const AI4SEO_OFFICIAL_RATE_US_URL = "https://sooz.ai/rate-us";
const AI4SEO_OPENAI_URL = "https://openai.com";
const AI4SEO_OPENAI_TERMS_OF_USE_URL = "https://openai.com/terms";
const AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME = "_ai4seo_robhub_environmental_variables";
const AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME = "_ai4seo_environmental_variables";
const AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME = '_ai4seo_generation_status_summary';
const AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME = '_ai4seo_posts_to_be_analyzed';
const AI4SEO_NOTIFICATIONS_OPTION_NAME = '_ai4seo_notifications';
const AI4SEO_DEBUG_MESSAGES_OPTION_NAME = 'ai4seo_debug_messages';
const AI4SEO_SETTINGS_OPTION_NAME = "ai4seo_settings";
const AI4SEO_POST_META_GENERATED_DATA_META_KEY = "ai4seo_generated_data";
const AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY = "ai4seo_content_summary";
const AI4SEO_STYLES_HANDLE = "ai-for-seo-styles";
const AI4SEO_SCRIPTS_HANDLE = "ai-for-seo-scripts";
const AI4SEO_INJECTION_SCRIPTS_HANDLE = "ai-for-seo-injection-scripts";
const AI4SEO_VERY_LOW_CREDITS_THRESHOLD = 10;
const AI4SEO_LOW_CREDITS_THRESHOLD = 40;
const AI4SEO_CUSTOM_PLAN_DISCOUNT = 30; # in percent
const AI4SEO_DAILY_FREE_CREDITS_AMOUNT = 5;
const AI4SEO_MONEY_BACK_GUARANTEE_DAYS = 14;
const AI4SEO_MAX_LATEST_ACTIVITY_LOGS = 10;
const AI4SEO_BLUE_GET_MORE_CREDITS_BUTTON_THRESHOLD = 100;
const AI4SEO_GIVING_FEEDBACK_CREDITS = 100;
const AI4SEO_GIVING_FEEDBACK_DISCOUNT = 10; # in percent
const AI4SEO_NEXTGEN_GALLERY_POST_TYPE = "ai4seo_ngg";
const AI4SEO_MAX_DISPLAYABLE_ALREADY_READ_NOTIFICATIONS = 2;
const AI4SEO_ANALYZE_PERFORMANCE_INTERVAL = 7200; // 2h
const AI4SEO_GLOBAL_NONCE_IDENTIFIER = "ai4seo_ajax_nonce";
const AI4SEO_PAYG_CREDITS_THRESHOLD = 100;
const AI4SEO_ALLOWED_PAYG_STATUS = array('idle', 'budget-limit-reached', 'processing', 'payment-pending', 'payment-received', 'payment-failed', 'error');
const AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS = 5; // 5 seconds
const AI4SEO_SEMAPHORE_POLL_INTERVAL_SECONDS = .1; // .1 seconds
const AI4SEO_SEMAPHORE_TTL_SECONDS = 30; // 30 seconds
const AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE = 10000; // number of posts to analyze per batch
const AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME = 2; // maximum execution time in seconds per batch
const AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS = 100000; // microseconds to sleep between runs
const AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT = 90; // seconds

const AI4SEO_CRON_JOBS_ENABLED = true; # set to true to enable cron jobs, false to disable them

$GLOBALS['ai4seo_held_semaphores'] = isset( $GLOBALS['ai4seo_held_semaphores'] ) && is_array( $GLOBALS['ai4seo_held_semaphores'] )
    ? $GLOBALS['ai4seo_held_semaphores']
    : array();

$GLOBALS["ai4seo_ajax_nonce"] = "";

const AI4SEO_MAX_EDITOR_INPUT_LENGTHS = array(
    'focus-keyphrase' => 128,
    'meta-title' => 128,
    'meta-description' => 256,
    'keywords' => 512,
    'facebook-title' => 128,
    'facebook-description' => 256,
    'twitter-title' => 128,
    'twitter-description' => 256,
    'title' => 256,
    'alt-text' => 256,
    'caption' => 256,
    'description' => 512,
    'fallback' => 512,
);

// =========================================================================================== \\

/**
 * function to return the change log of the plugin
 * @return array[] the change log of the plugin
 */
function ai4seo_get_change_log(): array {
    return [
        [
            'date' => 'March 19th, 2026',
            'version' => '2.3.1',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 6 minor bugs and resolved 3 security issues.',
            ],
        ],
        [
            'date' => 'March 9th, 2026',
            'version' => '2.3.0',
            'important' => true,
            'updates' => [
                'Changed branding from "AI for SEO" to "SOOZ - AI for SEO". Visit sooz.ai for more information.',
                'Enhanced the quality of alt text and image attribute generation by incorporating a broader context from the surrounding content, resulting in more accurate descriptions.',
                'Performance improvements across multiple areas. The plugin should feel more responsive on large sites.',
                'Posts and Media lists now show all languages. Entries are labeled and can be filtered by WPML language.',
                'New advanced setting: Toggle frontend cache purging after metadata updates.',
                'Added a warning when leaving a page or modal with unsaved changes, on various editors and settings pages.',
                'Added a feedback modal on plugin deactivation, so users can share why they are leaving.',
                'Added a new "Remove License" button on the account page.',
                'Added a new setting "Query IDs Chunk Size": Advanced troubleshooting option to adjust chunk size when processing large amounts of entries. Lower values may reduce database load and fix MySQL-related issues. Higher values can improve performance if your database allows it.',
                'Bug Fixes & Maintenance: Fixed 21 minor bugs, implemented 9 usability improvements, implemented 14 performance optimizations, and resolved 13 security issues.',
            ],
        ],
        [
            'date' => 'December 26th, 2025',
            'version' => '2.2.5',
            'important' => false,
            'updates' => [
                'Added an advanced setting to adjust the Focus Keyphrase behavior during SEO Autopilot when existing metadata is present.',
                'Bug Fixes & Maintenance: Fixed 4 minor bugs and implemented 2 usability improvements, and resolved 2 security issues.',
            ],
        ],
        [
            'date' => 'December 10th, 2025',
            'version' => '2.2.4',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 5 minor bugs and implemented 3 usability improvements.',
            ],
        ],
        [
            'date' => 'December 3rd, 2025',
            'version' => '2.2.3',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 7 minor bugs and implemented 2 usability improvements.',
            ],
        ],
        [
            'date' => 'November 20th, 2025',
            'version' => '2.2.2',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 9 minor bugs, implemented 3 usability improvements, and resolved 2 security issues.'
            ],
        ],
        [
            'date' => 'November 15th, 2025',
            'version' => '2.2.1',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 5 minor bugs',
            ],
        ],
        [
            'date' => 'November 14th, 2025',
            'version' => '2.2.0',
            'important' => true,
            'updates' => [
                'Changed how Credits are consumed. Credits are now charged per generated field instead of per entry. Use the “Active Meta Tags” and “Active Media Attributes” settings to control which fields are generated and how many Credits each action requires.',
                'Added Focus Keyphrase generation, editing, and syncing for Yoast SEO and RankMath.',
                'Added Meta Keywords generation, editing, and front-end output.',
                'Added WooCommerce price inclusion modes for AI-generated metadata (Never, Fixed Price, Dynamic Price).',
                'Added additional “Generate with SOOZ” buttons inside the Gutenberg editor when RankMath is active.',
                'Added “Generate with SOOZ” buttons for Focus Keyphrase (Yoast SEO) and Focus Keyword (RankMath) inside their editors.',
                'Added a filter bar to the Posts and Media views including SEO-status filters and a text search.',
                'Added placeholder support for prefixes and suffixes across all syntaxes ({TITLE}, [TITLE], %%TITLE%% and their placeholder variants).',
                'Added a setting to include existing values when generating metadata and media attributes (Basic Plan+).',
                'Added a setting to enable enhanced entity recognition for media attributes (Pro Plan+).',
                'Added a setting to enable advanced celebrity face recognition for media attributes (Premium Plan+).',
                'Added configurable meta tag fallback rules to reuse existing values when no generated data is available.',
                'Added a toggle to control JavaScript-based alt text injection separately from render-level injection.',
                'Added a troubleshooting toggle to pause database refresh operations during debugging.',
                'Added the “Active Meta Tags” setting to control which meta tags the plugin should generate.',
                'Added the “Active Post Types” setting to define which post types the plugin should process.',
                'Added a reminder and one-click removal option for previously generated SEO-relevant data within the SEO Autopilot modal.',
                'Added a “Generate Data for X Empty Fields” button in the Metadata and Media Attribute Editors.',
                'Added a “Save & edit next” button in the Metadata and Media Attribute Editors for faster sequential editing.',
                'Added a “Retry all failed” quick action to the Dashboard when failed entries are detected.',
                'Added Credits badges across the plugin UI to indicate the cost of generation actions.',
                'Added an “SEO-Expert Concierge” card to the Dashboard with direct contact options.',
                'Submit and Abort buttons are now sticky for easier access during editing.',
                'Bug Fixes & Maintenance: Fixed 21 minor bugs, implemented 29 usability improvements, implemented 35 stability improvements, and resolved 8 security issues.'
            ],
        ],
        [
            'date' => 'September 23th, 2025',
            'version' => '2.1.5',
            'important' => false,
            'updates' => [
                'Added a new feature to easily retrieve lost license data. Go to your Account page, click "Lost your license data?", and follow the instructions.',
                'Bug Fixes & Maintenance: Fixed 2 minor bugs and implemented 4 usability improvements.'
            ],
        ],
        [
            'date' => 'August 28th, 2025',
            'version' => '2.1.3',
            'important' => true,
            'updates' => [
                'Dashboard now refreshes automatically; manual page reload is no longer required.',
                'Added compatibility with the SEOKey plugin.',
                'Alt Text Injection is now disabled by default. To re-enable, go to Settings > Show Advanced Settings (top right) > Troubleshooting & Experimental > Alt Text Injection.',
                'Added a submenu for direct plugin access via the WordPress admin menu.',
                'Added a "Refresh" button to dashboard statistics. Recommended for large sites (>10,000 entries) to update statistics on demand.',
                'Added a new FAQ area under Help > Troubleshooting, covering common problems and solutions.',
                'Bug Fixes & Maintenance: Fixed 11 minor bugs, implemented 13 performance and usability improvements, and resolved 2 security issues.',
            ]
        ],
        [
            'date' => 'August 3rd, 2025',
            'version' => '2.1.0',
            'important' => true,
            'updates' => [
                'Added "Generate with SOOZ" buttons in the media section of the Gutenberg editor, allowing users to generate media attributes directly from the editor.',
                'Improved context awareness for pages, posts, and products, especially for content with short text. Ensures AI-generated metadata is more relevant and tailored.',
                'Added a setting for render-level alt text injection. This checkbox setting (enabled by default) ensures images always have the correct alt text, even if themes or other mechanisms fail to display it.',
                'Added a setting for render-level image title injection. Includes a select input to choose what should be injected as the title attribute: Disabled, Inject image title, Inject alt text (default), Inject caption, or Inject image description.',
                'Added a setting to the "SEO Autopilot" modal that allows users to customize the reference time used by the "Generate Metadata for" option. This gives more precise control over how new and old entries are distinguished.',
                'Added an "Export/Import" button to the plugin settings, enabling users to export their configuration and import it on another website. Useful for SEO and web agencies managing multiple sites.',
                'Added a "Restore Default" button to the plugin settings.',
                'Added a "Show/Hide Advanced Settings" toggle in the plugin settings. Some advanced settings are now hidden by default to simplify the interface for most users.',
                'Made setting descriptions more concise and user-friendly. Rearranged several settings for improved clarity and usability.',
                'Private or pending posts, pages, and attachments are now ignored by the plugin, preventing them from being processed.',
                #'The plugin now indicates posts, pages, and attachments correctly when ignored by the SEO Autopilot, fully respecting the user\'s selection and settings.',
                'Improved UX: The SOOZ - AI for SEO sidebar is now sticky on desktop, keeping it visible during page scrolling for easier navigation.',
                'Changed how plugin notifications are handled. All notifications are now indicated by a red bubble in the admin menu. Detailed notices can be viewed in the SOOZ - AI for SEO dashboard.',
                'Added new notifications: 
                <ul>
                    <li>Insufficient credits balance.</li>
                    <li>Overview of missing entries to generate.</li>
                    <li>WPML plugin detected.</li>
                    <li>SEO Autopilot needing attention.</li>
                    <li>New major plugin updates.</li>
                    <li>Ongoing promos and discounts.</li>
                    <li>And others.</li>
                </ul>',
                'Bug Fixes & Maintenance: Fixed 17 minor bugs, added 6 quality-of-life improvements, implemented 3 performance optimizations, and 2 security updates.'
            ]
        ],
        [
            'date' => 'July 1st, 2025',
            'version' => '2.0.7',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 7 minor bugs, added 3 quality of life improvements, and implemented security updates.'
            ]
        ],
        [
            'date' => 'June 23nd, 2025',
            'version' => '2.0.6',
            'important' => false,
            'updates' => [
                'Bug Fixes & Maintenance: Fixed 3 minor bug'
            ]
        ],
        [
            'date' => 'June 22nd, 2025',
            'version' => '2.0.5',
            'important' => true,
            'updates' => [
                'Added a setting to force the image upload to use either the image url only or convert the contents to base64. This can be useful for users who experience issues with the generation of media attributes.',
                'Added support for AVIF image files',
                'Bug Fixes & Maintenance: Fixed 11 minor bugs and implemented security updates.'
            ]
        ],
        [
            'date' => 'May 9th, 2025',
            'version' => '2.0.4',
            'important' => false,
            'updates' => [
                'Added support for NextGen Gallery: The plugin now recognizes and processes media attributes for images created with the NextGen Gallery plugin. Use the new "Import" button in the media page to import all images from the NextGen Gallery into the *SOOZ - AI for SEO* plugin.',
                'Bug Fixes & Maintenance: Fixed 2 minor bugs'
            ]
        ],
        [
            'date' => 'May 4th, 2025',
            'version' => '2.0.3',
            'important' => false,
            'updates' => [
                'SEO Autopilot now more accurately reflects its current status and includes an option to immediately schedule the next run.',
                'Bug Fixes & Maintenance: Fixed 15 minor bugs, corrected typos, and implemented security updates.'
            ]
        ],
        [
            'date' => 'April 08th, 2025',
            'version' => '2.0.2',
            'important' => false,
            'updates' => [
                'Improved Prefix & Suffix Support: Prefixes and suffixes are now correctly applied when using the "Generate with SOOZ" button in both the Metadata Editor and the Attachment Attributes Editor.',
                'Enhanced Mobile UX: Better responsiveness and usability on the Pages / Posts and Media Files views for mobile devices.',
                'Account Page Improvements: Added direct buttons for managing your active subscription and customizing Pay-As-You-Go settings.',
                'Updated Help Section: Improved help content and clearer "First Steps" guidance for new users.',
                'Bug Fixes & Maintenance: Fixed 11 minor bugs, corrected typos, and implemented security updates.'
            ]
        ],
        [
            'date' => 'March 20th, 2025',
            'version' => '2.0.0',
            'important' => true,
            'updates' => [
                'Complete UI/UX Overhaul: The look, feel, design, layout, and navigation of the plugin have been completely redesigned.',
                'Enhanced Mobile Experience: Improved usability and user experience for mobile users.',
                'New "Account" Page: Users can now manage their license key directly from this page.',
                'Incognito Mode: SEO and web agencies can hide the plugin from other users/admins (available in the new "Account" page).',
                'White-Label Feature: SEO and web agencies can rebrand the plugin with their own name or further hide it from other users/admins (available in the new "Account" page).',
                'Customizable Generator Hints: Added a setting to modify or disable generator hints in the source code for additional privacy (available in the "Account" page).',
                'Privacy & Data Policy Update: Moved to the new "Account" page.',
                'New Metadata Customization Options: Added settings to apply prefixes and suffixes to metadata and media attributes.',
                'Advanced Media Attribute Control: New setting allows users to specify which media attributes the plugin should use.',
                '"SEO Autopilot" Feature: Replaces bulk generation checkboxes with a more intuitive and easy-to-use interface, directly accessible from the dashboard.',
                '"Recent Activity" Dashboard Section: Track all manual and automatic metadata and media attribute generations in one place.',
                'Implemented new ways to get credits:
                        <ol>
                            <li>* Introduced Credit Packs, allowing users to purchase additional credits as needed.</li>
                            <li>* Added a Pay-As-You-Go option for automatic credit refills when running low.</li>
                            <li>* All credit purchasing options are now combined in a "Get more Credits" modal, accessible from the dashboard.</li>
                        </ol>',
                '"Guarantee" Section: Review our Guarantees and Refund Policy directly on the dashboard.',
                '"Recent Plugin Updates" Section: Stay informed about the latest updates from the dashboard.',
                'New "Support & Feedback" Section: Easily access support and provide feedback directly from the dashboard.',
                'Tons more minor improvements, bug fixes, and performance enhancements.'
            ]
        ],
    ];
}

/**
 * Function to return the credits packs available for purchase
 * @return array[]
 */
function ai4seo_get_credits_packs(): array {
    $credits_packs = array(
        "price_1S6ThfHNyvfVK0r9KimFGz1E" => array(
            "credits_amount" => 500,
            "price_usd" => 9,
            "reference_price_usd" => 9,
            "price_eur" => 8,
            "reference_price_eur" => 8,
            "stripe_product_id" => "prod_RD8C2kl2gPqozh",
            "stripe_payment_link" => "https://buy.stripe.com/5kA00X7yF5Rc3BK8ww",
        ),
        "price_1S6TjQHNyvfVK0r9WttAsfP9" => array(
            "credits_amount" => 1500,
            "price_usd" => 19,
            "reference_price_usd" => 19,
            "price_eur" => 16,
            "reference_price_eur" => 16,
            "stripe_product_id" => "prod_RD8JI7ELrXPSWg",
            "stripe_payment_link" => "",
        ),
        "price_1S6TlCHNyvfVK0r9s0CZ3z1Z" => array(
            "credits_amount" => 5000,
            "price_usd" => 49,
            "reference_price_usd" => 49,
            "price_eur" => 45,
            "reference_price_eur" => 45,
            "stripe_product_id" => "prod_RD8KgysYBIyi2Z",
            "stripe_payment_link" => "",
        ),
    );

    # Large Credit Packs for users in group B-E
    if ((!ai4seo_robhub_api()->is_group('a') && !ai4seo_robhub_api()->is_group('f')) || isset($_GET["ai4seo_show_all_credits_packs"])) {
        $credits_packs += array(
            "price_1SjAj1HNyvfVK0r9TkVWQsJE" => array(
                "credits_amount" => 15000,
                "price_usd" => 129,
                "reference_price_usd" => 129,
                "price_eur" => 109,
                "reference_price_eur" => 109,
                "stripe_product_id" => "prod_RD8LcGkIHN7O0K",
                "stripe_payment_link" => "",
            ),
            "price_1SjAk2HNyvfVK0r9NJDtHuRJ" => array(
                "credits_amount" => 50000,
                "price_usd" => 349,
                "reference_price_usd" => 349,
                "price_eur" => 299,
                "reference_price_eur" => 299,
                "stripe_product_id" => "prod_RD8LWAmW1fQ32n",
                "stripe_payment_link" => "",
            ),
        );
    }

    return $credits_packs;
}

/**
 * function to get the SVG icons used in the plugin
 * @return string[] associative array with icon names as keys and SVG strings as values
 */
function ai4seo_get_svg_tags(): array {
    return array(
        "ai-for-seo-main-menu-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 500.000000 500.000000"><g transform="translate(0.000000,500.000000) scale(0.100000,-0.100000)" fill="#a7aaad" stroke="none"><path d="M2145 4755 l7 -245 -843 -2 -844 -3 -3 -827 -2 -828 -230 0 -230 0 0 -345 0 -345 230 0 230 0 2 -22 c1 -13 2 -387 3 -833 l0 -810 838 -3 837 -2 0 -245 0 -245 350 0 350 0 0 245 0 245 840 0 840 0 2 308 c1 169 1 539 0 822 -1 283 1 522 3 530 4 13 38 15 240 12 l235 -3 0 345 0 346 -237 2 -238 3 -3 828 -2 827 -840 0 -840 0 0 245 0 245 -351 0 -351 0 7 -245z m344 -1143 c5 -10 21 -63 35 -118 36 -142 104 -375 110 -382 10 -10 18 6 31 60 8 29 32 125 55 213 24 88 45 174 47 190 3 17 10 36 15 43 8 9 124 12 519 12 401 0 509 -3 509 -12 -1 -7 1 -168 4 -358 4 -251 8 -1595 6 -1902 0 -17 -43 -18 -754 -18 l-754 0 -97 145 c-54 80 -101 145 -104 145 -4 0 -25 -24 -46 -52 -47 -63 -150 -196 -170 -220 -14 -17 -48 -18 -463 -18 -247 0 -451 3 -455 6 -3 3 2 29 13 58 10 28 34 103 55 166 124 393 661 2012 677 2043 8 16 37 17 383 17 351 0 375 -1 384 -18z"/><path d="M1907 3088 c-102 -299 -189 -553 -247 -718 -63 -179 -195 -563 -210 -613 l-9 -28 141 3 141 3 41 125 41 125 296 3 296 2 39 -130 38 -130 143 0 c79 0 143 2 143 4 0 6 -80 240 -115 336 -14 41 -51 143 -80 225 -29 83 -70 195 -90 250 -37 102 -235 667 -235 672 0 2 -65 3 -144 3 l-143 0 -46 -132z m203 -241 c0 -8 43 -143 95 -302 52 -158 95 -294 95 -301 0 -11 -38 -14 -205 -14 -136 0 -205 4 -205 10 0 25 202 620 211 620 5 0 9 -6 9 -13z"/><path d="M3126 2484 c-3 -404 -4 -740 -1 -745 4 -5 67 -9 141 -9 l134 0 0 745 0 745 -133 0 -134 0 -7 -736z"/></g></svg> ',
        "all-in-one-seo" => '<svg viewBox="0 0 20 20" width="16" height="16" fill="#a7aaad" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.98542 19.9708C15.5002 19.9708 19.9708 15.5002 19.9708 9.98542C19.9708 4.47063 15.5002 0 9.98542 0C4.47063 0 0 4.47063 0 9.98542C0 15.5002 4.47063 19.9708 9.98542 19.9708ZM8.39541 3.65464C8.26016 3.4485 8.0096 3.35211 7.77985 3.43327C7.51816 3.52572 7.26218 3.63445 7.01349 3.7588C6.79519 3.86796 6.68566 4.11731 6.73372 4.36049L6.90493 5.22694C6.949 5.44996 6.858 5.6763 6.68522 5.82009C6.41216 6.04734 6.16007 6.30426 5.93421 6.58864C5.79383 6.76539 5.57233 6.85907 5.35361 6.81489L4.50424 6.6433C4.26564 6.5951 4.02157 6.70788 3.91544 6.93121C3.85549 7.05738 3.79889 7.1862 3.74583 7.31758C3.69276 7.44896 3.64397 7.58105 3.59938 7.71369C3.52048 7.94847 3.61579 8.20398 3.81839 8.34133L4.53958 8.83027C4.72529 8.95617 4.81778 9.1819 4.79534 9.40826C4.75925 9.77244 4.76072 10.136 4.79756 10.4936C4.82087 10.7198 4.72915 10.9459 4.54388 11.0724L3.82408 11.5642C3.62205 11.7022 3.52759 11.9579 3.60713 12.1923C3.69774 12.4593 3.8043 12.7205 3.92615 12.9743C4.03313 13.1971 4.27749 13.3088 4.51581 13.2598L5.36495 13.0851C5.5835 13.0401 5.80533 13.133 5.94623 13.3093C6.16893 13.5879 6.42071 13.8451 6.6994 14.0756C6.87261 14.2188 6.96442 14.4448 6.92112 14.668L6.75296 15.5348C6.70572 15.7782 6.81625 16.0273 7.03511 16.1356C7.15876 16.1967 7.285 16.2545 7.41375 16.3086C7.54251 16.3628 7.67196 16.4126 7.80195 16.4581C8.18224 16.5912 8.71449 16.1147 9.108 15.7625C9.30205 15.5888 9.42174 15.343 9.42301 15.0798C9.42301 15.0784 9.42302 15.077 9.42302 15.0756L9.42301 13.6263C9.42301 13.6109 9.4236 13.5957 9.42476 13.5806C8.26248 13.2971 7.39838 12.2301 7.39838 10.9572V9.41823C7.39838 9.30125 7.49131 9.20642 7.60596 9.20642H8.32584V7.6922C8.32584 7.48312 8.49193 7.31364 8.69683 7.31364C8.90171 7.31364 9.06781 7.48312 9.06781 7.6922V9.20642H11.0155V7.6922C11.0155 7.48312 11.1816 7.31364 11.3865 7.31364C11.5914 7.31364 11.7575 7.48312 11.7575 7.6922V9.20642H12.4773C12.592 9.20642 12.6849 9.30125 12.6849 9.41823V10.9572C12.6849 12.2704 11.7653 13.3643 10.5474 13.6051C10.5477 13.6121 10.5478 13.6192 10.5478 13.6263L10.5478 15.0694C10.5478 15.3377 10.6711 15.5879 10.871 15.7622C11.2715 16.1115 11.8129 16.5837 12.191 16.4502C12.4527 16.3577 12.7086 16.249 12.9573 16.1246C13.1756 16.0155 13.2852 15.7661 13.2371 15.5229L13.0659 14.6565C13.0218 14.4334 13.1128 14.2071 13.2856 14.0633C13.5587 13.8361 13.8107 13.5792 14.0366 13.2948C14.177 13.118 14.3985 13.0244 14.6172 13.0685L15.4666 13.2401C15.7052 13.2883 15.9493 13.1756 16.0554 12.9522C16.1153 12.8261 16.1719 12.6972 16.225 12.5659C16.2781 12.4345 16.3269 12.3024 16.3714 12.1698C16.4503 11.935 16.355 11.6795 16.1524 11.5421L15.4312 11.0532C15.2455 10.9273 15.153 10.7015 15.1755 10.4752C15.2116 10.111 15.2101 9.74744 15.1733 9.38986C15.1499 9.16361 15.2417 8.93757 15.4269 8.811L16.1467 8.31927C16.3488 8.18126 16.4432 7.92558 16.3637 7.69115C16.2731 7.42411 16.1665 7.16292 16.0447 6.90915C15.9377 6.68638 15.6933 6.57462 15.455 6.62366L14.6059 6.79837C14.3873 6.84334 14.1655 6.75048 14.0246 6.57418C13.8019 6.29554 13.5501 6.03832 13.2714 5.80784C13.0982 5.6646 13.0064 5.43858 13.0497 5.2154L13.2179 4.34868C13.2651 4.10521 13.1546 3.85616 12.9357 3.74787C12.8121 3.68669 12.6858 3.62895 12.5571 3.5748C12.4283 3.52065 12.2989 3.47086 12.1689 3.42537C11.9388 3.34485 11.6884 3.44211 11.5538 3.64884L11.0746 4.38475C10.9513 4.57425 10.73 4.66862 10.5082 4.64573C10.1513 4.6089 9.79502 4.61039 9.44459 4.64799C9.22286 4.67177 9.00134 4.57818 8.87731 4.38913L8.39541 3.65464Z" fill="#a7aaad" /></svg>',
        "angle-down" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"/></svg>',
        "arrow-right" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"/></svg>',
        "arrow-up-right-from-square" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32h82.7L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3V192c0 17.7 14.3 32 32 32s32-14.3 32-32V32c0-17.7-14.3-32-32-32H320zM80 32C35.8 32 0 67.8 0 112V432c0 44.2 35.8 80 80 80H400c44.2 0 80-35.8 80-80V320c0-17.7-14.3-32-32-32s-32 14.3-32 32V432c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16H192c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/></svg>',
        "bars-sort" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l253.44 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM0 416c0 17.7 14.3 32 32 32l126.72 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 384c-17.7 0-32 14.3-32 32z"/></svg>',
        "betheme" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50"><text x="5" y="40" font-size="60" font-family="Arial Black" font-weight="bold">Be</text></svg>',
        "bolt" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288H175.5L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7H272.5L349.4 44.6z"/></svg>',
        "caret-down" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M160 352L0 192h320z"/></svg>',
        "caret-up" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M160 160L0 320h320z"/></svg>',
        "check" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>',
        'circle' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/></svg>',
        'circle-check' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
        "circle-plus" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>',
        "circle-question" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',
        'crown' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 48"><path d="M8 34L4 12l14 10L32 4l14 18 14-10-4 22z" fill="currentColor"/><rect x="12" y="34" width="40" height="10" rx="2" fill="currentColor"/></svg>',
        "circle-up" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM135.1 217.4l107.1-99.9c3.8-3.5 8.7-5.5 13.8-5.5s10.1 2 13.8 5.5l107.1 99.9c4.5 4.2 7.1 10.1 7.1 16.3c0 12.3-10 22.3-22.3 22.3H304v96c0 17.7-14.3 32-32 32H240c-17.7 0-32-14.3-32-32V256H150.3C138 256 128 246 128 233.7c0-6.2 2.6-12.1 7.1-16.3z"/></svg>',
        "circle-xmark" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
        "code" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M392.8 1.2c-17-4.9-34.7 5-39.6 22l-128 448c-4.9 17 5 34.7 22 39.6s34.7-5 39.6-22l128-448c4.9-17-5-34.7-22-39.6zm80.6 120.1c-12.5 12.5-12.5 32.8 0 45.3L562.7 256l-89.4 89.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l112-112c12.5-12.5 12.5-32.8 0-45.3l-112-112c-12.5-12.5-32.8-12.5-45.3 0zm-306.7 0c-12.5-12.5-32.8-12.5-45.3 0l-112 112c-12.5 12.5-12.5 32.8 0 45.3l112 112c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256l89.4-89.4c12.5-12.5 12.5-32.8 0-45.3z"/></svg>',
        'comment' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M115.9 448.9C83.3 408.6 64 358.4 64 304C64 171.5 178.6 64 320 64C461.4 64 576 171.5 576 304C576 436.5 461.4 544 320 544C283.5 544 248.8 536.8 217.4 524L101 573.9C97.3 575.5 93.5 576 89.5 576C75.4 576 64 564.6 64 550.5C64 546.2 65.1 542 67.1 538.3L115.9 448.9zM153.2 418.7C165.4 433.8 167.3 454.8 158 471.9L140 505L198.5 479.9C210.3 474.8 223.7 474.7 235.6 479.6C261.3 490.1 289.8 496 319.9 496C437.7 496 527.9 407.2 527.9 304C527.9 200.8 437.8 112 320 112C202.2 112 112 200.8 112 304C112 346.8 127.1 386.4 153.2 418.7z"/></svg>',
        'comments' => '<svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 512 450.27"><path d="M217.91 393.59c53.26 49.01 127.33 63.27 201.39 31.71l63.49 24.97-9.94-59.73c59.07-51.65 45.36-123.42-1.79-173.93-3.69 19.53-10.48 38.07-19.94 55.27-14.17 25.77-34.46 48.67-59.31 67.52-24.07 18.27-52.17 32.61-82.8 41.87-28.16 8.51-58.91 12.91-91.1 12.32zm-85.88-167.22c-7.7 0-13.95-6.25-13.95-13.95 0-7.7 6.25-13.95 13.95-13.95h124.12c7.7 0 13.94 6.25 13.94 13.95 0 7.7-6.24 13.95-13.94 13.95H132.03zm0-71.41c-7.7 0-13.95-6.25-13.95-13.95 0-7.71 6.25-13.95 13.95-13.95h177.35c7.7 0 13.94 6.24 13.94 13.95 0 7.7-6.24 13.95-13.94 13.95H132.03zM226.13.12l.21.01c60.33 1.82 114.45 23.27 153.19 56.49 39.57 33.92 63.3 80.1 61.82 130.51l-.01.23c-1.56 50.44-28.05 95.17-69.62 126.71-40.74 30.92-96.12 49.16-156.44 47.39-15.45-.46-30.47-2.04-44.79-4.82-12.45-2.42-24.5-5.75-36-10.05L28.17 379.06l31.85-75.75c-18.2-15.99-32.94-34.6-43.24-55.01C5.29 225.51-.72 200.48.07 174.33c1.52-50.49 28.02-95.26 69.61-126.82C110.44 16.59 165.81-1.65 226.13.12zm-.55 27.7-.21-.01C171.49 26.23 122.33 42.3 86.41 69.55c-35.07 26.61-57.39 63.9-58.65 105.54-.65 21.39 4.31 41.94 13.78 60.72 10.01 19.82 25.02 37.7 43.79 52.58l8.26 6.54-16.99 40.39 59.12-18.06 4.5 1.81c11.15 4.48 23.04 7.9 35.48 10.31 13.07 2.55 26.59 3.98 40.34 4.39 53.88 1.58 103.04-14.49 138.96-41.74 35.07-26.61 57.39-63.9 58.65-105.54v-.22c1.19-41.57-18.82-80.01-52.15-108.59-34.18-29.3-82.19-48.24-135.92-49.86z"/></svg>',
        "copy" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 64C252.7 64 224 92.7 224 128L224 384C224 419.3 252.7 448 288 448L480 448C515.3 448 544 419.3 544 384L544 183.4C544 166 536.9 149.3 524.3 137.2L466.6 81.8C454.7 70.4 438.8 64 422.3 64L288 64zM160 192C124.7 192 96 220.7 96 256L96 512C96 547.3 124.7 576 160 576L352 576C387.3 576 416 547.3 416 512L416 496L352 496L352 512L160 512L160 256L176 256L176 192L160 192z"/></svg>',
        "download" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 242.7-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7 288 32zM64 352c-35.3 0-64 28.7-64 64l0 32c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-32c0-35.3-28.7-64-64-64l-101.5 0-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352 64 352zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>',
        "envelope" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>',
        "eye" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>',
        "eye-slash" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c8.4-19.3 10.6-41.4 4.8-63.3c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zM373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5L373 389.9z"/></svg>',
        "file-arrow-down" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM216 232l0 102.1 31-31c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-72 72c-9.4 9.4-24.6 9.4-33.9 0l-72-72c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l31 31L168 232c0-13.3 10.7-24 24-24s24 10.7 24 24z"/></svg>',
        "file-arrow-up" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM216 408c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-102.1-31 31c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l72-72c9.4-9.4 24.6-9.4 33.9 0l72 72c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-31-31L216 408z"/></svg>',
        "file-export" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 128-168 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l168 0 0 112c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64zM384 336l0-48 110.1 0-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39L384 336zm0-208l-128 0L256 0 384 128z"/></svg>',
        'flag' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 96C160 78.3 145.7 64 128 64C110.3 64 96 78.3 96 96L96 544C96 561.7 110.3 576 128 576C145.7 576 160 561.7 160 544L160 422.4L222.7 403.6C264.6 391 309.8 394.9 348.9 414.5C391.6 435.9 441.4 438.5 486.1 421.7L523.2 407.8C535.7 403.1 544 391.2 544 377.8L544 130.1C544 107.1 519.8 92.1 499.2 102.4L487.4 108.3C442.5 130.8 389.6 130.8 344.6 108.3C308.2 90.1 266.3 86.5 227.4 98.2L160 118.4L160 96z"/></svg>',
        "gear" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>',
        "gift" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M385.5 132.8C393.1 119.9 406.9 112 421.8 112L424 112C446.1 112 464 129.9 464 152C464 174.1 446.1 192 424 192L350.7 192L385.5 132.8zM254.5 132.8L289.3 192L216 192C193.9 192 176 174.1 176 152C176 129.9 193.9 112 216 112L218.2 112C233.1 112 247 119.9 254.5 132.8zM344.1 108.5L320 149.5L295.9 108.5C279.7 80.9 250.1 64 218.2 64L216 64C167.4 64 128 103.4 128 152C128 166.4 131.5 180 137.6 192L96 192C78.3 192 64 206.3 64 224L64 256C64 273.7 78.3 288 96 288L544 288C561.7 288 576 273.7 576 256L576 224C576 206.3 561.7 192 544 192L502.4 192C508.5 180 512 166.4 512 152C512 103.4 472.6 64 424 64L421.8 64C389.9 64 360.3 80.9 344.1 108.4zM544 336L344 336L344 544L480 544C515.3 544 544 515.3 544 480L544 336zM296 336L96 336L96 480C96 515.3 124.7 544 160 544L296 544L296 336z"/></svg>',
        "globe" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M352 256c0 22.2-1.2 43.6-3.3 64l-185.3 0c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64l185.3 0c2.2 20.4 3.3 41.8 3.3 64zm28.8-64l123.1 0c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64l-123.1 0c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32l-116.7 0c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0l-176.6 0c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 20.9 58.2 27 94.7zm-209 0L18.6 160C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192l123.1 0c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64L8.1 320C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6l176.6 0c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352l116.7 0zm358.1 0c-30 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6l116.7 0z"/></svg>',
        "handshake" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M323.4 85.2l-96.8 78.4c-16.1 13-19.2 36.4-7 53.1c12.9 17.8 38 21.3 55.3 7.8l99.3-77.2c7-5.4 17-4.2 22.5 2.8s4.2 17-2.8 22.5l-20.9 16.2L512 316.8 512 128l-.7 0-3.9-2.5L434.8 79c-15.3-9.8-33.2-15-51.4-15c-21.8 0-43 7.5-60 21.2zm22.8 124.4l-51.7 40.2C263 274.4 217.3 268 193.7 235.6c-22.2-30.5-16.6-73.1 12.7-96.8l83.2-67.3c-11.6-4.9-24.1-7.4-36.8-7.4C234 64 215.7 69.6 200 80l-72 48 0 224 28.2 0 91.4 83.4c19.6 17.9 49.9 16.5 67.8-3.1c5.5-6.1 9.2-13.2 11.1-20.6l17 15.6c19.5 17.9 49.9 16.6 67.8-2.9c4.5-4.9 7.8-10.6 9.9-16.5c19.4 13 45.8 10.3 62.1-7.5c17.9-19.5 16.6-49.9-2.9-67.8l-134.2-123zM16 128c-8.8 0-16 7.2-16 16L0 352c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-224-80 0zM48 320a16 16 0 1 1 0 32 16 16 0 1 1 0-32zM544 128l0 224c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-208c0-8.8-7.2-16-16-16l-80 0zm32 208a16 16 0 1 1 32 0 16 16 0 1 1 -32 0z"/></svg>',
        'hashtag' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M278.7 64.7C296 68.4 307 85.4 303.3 102.7L284.2 192L410.7 192L432.7 89.3C436.4 72 453.4 61 470.7 64.7C488 68.4 499 85.4 495.3 102.7L476.2 192L544 192C561.7 192 576 206.3 576 224C576 241.7 561.7 256 544 256L462.4 256L435 384L502.8 384C520.5 384 534.8 398.3 534.8 416C534.8 433.7 520.5 448 502.8 448L421.2 448L399.2 550.7C395.5 568 378.5 579 361.2 575.3C343.9 571.6 332.9 554.6 336.6 537.3L355.7 448L229.2 448L207.2 550.7C203.5 568 186.5 579 169.2 575.3C151.9 571.6 140.9 554.6 144.6 537.3L163.8 448L96 448C78.3 448 64 433.7 64 416C64 398.3 78.3 384 96 384L177.6 384L205 256L137.2 256C119.5 256 105.2 241.7 105.2 224C105.2 206.3 119.5 192 137.2 192L218.8 192L240.8 89.3C244.4 72 261.4 61 278.7 64.7zM270.4 256L243 384L369.5 384L396.9 256L270.4 256z"/></svg>',
        "headline" => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="20" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="20" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
        'hourglass-start' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 64C142.3 64 128 78.3 128 96C128 113.7 142.3 128 160 128L160 139C160 181.4 176.9 222.1 206.9 252.1L274.8 320L206.9 387.9C176.9 417.9 160 458.6 160 501L160 512C142.3 512 128 526.3 128 544C128 561.7 142.3 576 160 576L480 576C497.7 576 512 561.7 512 544C512 526.3 497.7 512 480 512L480 501C480 458.6 463.1 417.9 433.1 387.9L365.2 320L433.1 252.1C463.1 222.1 480 181.4 480 139L480 128C497.7 128 512 113.7 512 96C512 78.3 497.7 64 480 64L160 64zM416 501L416 512L224 512L224 501C224 475.5 234.1 451.1 252.1 433.1L320 365.2L387.9 433.1C405.9 451.1 416 475.5 416 501z"/></svg>',
        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l96 0 32 0 208 0c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>',
        'image-slash' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l96 0 32 0 208 0c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/><line x1="0" y1="0" x2="512" y2="512" stroke="black" stroke-width="32" /></svg>',
        'key' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M336 352c97.2 0 176-78.8 176-176S433.2 0 336 0S160 78.8 160 176c0 18.7 2.9 36.8 8.3 53.7L7 391c-4.5 4.5-7 10.6-7 17l0 80c0 13.3 10.7 24 24 24l80 0c13.3 0 24-10.7 24-24l0-40 40 0c13.3 0 24-10.7 24-24l0-40 40 0c6.4 0 12.5-2.5 17-7l33.3-33.3c16.9 5.4 35 8.3 53.7 8.3zM376 96a40 40 0 1 1 0 80 40 40 0 1 1 0-80z"/></svg>',
        'key-slash' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com --><path d="M400 416C497.2 416 576 337.2 576 240C576 142.8 497.2 64 400 64C302.8 64 224 142.8 224 240C224 258.7 226.9 276.8 232.3 293.7L71 455C66.5 459.5 64 465.6 64 472L64 552C64 565.3 74.7 576 88 576L168 576C181.3 576 192 565.3 192 552L192 512L232 512C245.3 512 256 501.3 256 488L256 448L296 448C302.4 448 308.5 445.5 313 441L346.3 407.7C363.2 413.1 381.3 416 400 416zM440 160C462.1 160 480 177.9 480 200C480 222.1 462.1 240 440 240C417.9 240 400 222.1 400 200C400 177.9 417.9 160 440 160z"/><line x1="50" y1="50" x2="552" y2="552" stroke="black" stroke-width="50" /></svg>',
        'list' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zM16 232l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0z"/></svg>',
        "magnifying-glass" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg> ',
        'paper-plane' => '<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.56 122.88"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><title>send</title><path class="cls-1" d="M2.33,44.58,117.33.37a3.63,3.63,0,0,1,5,4.56l-44,115.61h0a3.63,3.63,0,0,1-6.67.28L53.93,84.14,89.12,33.77,38.85,68.86,2.06,51.24a3.63,3.63,0,0,1,.27-6.66Z"/></svg>',
        "rank-math" => '<svg viewBox="0 0 462.03 462.03" xmlns="http://www.w3.org/2000/svg" width="20"><g fill="#a7aaad"><path d="m462 234.84-76.17 3.43 13.43 21-127 81.18-126-52.93-146.26 60.97 10.14 24.34 136.1-56.71 128.57 54 138.69-88.61 13.43 21z"/><path d="m54.1 312.78 92.18-38.41 4.49 1.89v-54.58h-96.67zm210.9-223.57v235.05l7.26 3 89.43-57.05v-181zm-105.44 190.79 96.67 40.62v-165.19h-96.67z"/></g></svg>',
        "robot" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z"/></svg>',
        "rotate" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M142.9 142.9c-17.5 17.5-30.1 38-37.8 59.8c-5.9 16.7-24.2 25.4-40.8 19.5s-25.4-24.2-19.5-40.8C55.6 150.7 73.2 122 97.6 97.6c87.2-87.2 228.3-87.5 315.8-1L455 55c6.9-6.9 17.2-8.9 26.2-5.2s14.8 12.5 14.8 22.2l0 128c0 13.3-10.7 24-24 24l-8.4 0c0 0 0 0 0 0L344 224c-9.7 0-18.5-5.8-22.2-14.8s-1.7-19.3 5.2-26.2l41.1-41.1c-62.6-61.5-163.1-61.2-225.3 1zM16 312c0-13.3 10.7-24 24-24l7.6 0 .7 0L168 288c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6 0-.7 0-7.6z"/></svg>',
        "pen-to-square" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-primary" d="M392.4 21.7L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0zM339.7 74.3L172.4 241.7c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3z"/><path class="fa-secondary" d="M0 160c0-53 43-96 96-96h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H96c-17.7 0-32 14.3-32 32V416c0 17.7 14.3 32 32 32H352c17.7 0 32-14.3 32-32V320c0-17.7 14.3-32 32-32s32 14.3 32 32v96c0 53-43 96-96 96H96c-53 0-96-43-96-96V160z"/></svg>',
        "rocket" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2l0 82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9l0-107.2c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>',
        "rocket-chat" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M284 224.8a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 284 224.8zm-110.5 0a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 173.6 224.8zm220.9 0a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 394.5 224.8zm153.8-55.3c-15.5-24.2-37.3-45.6-64.7-63.6-52.9-34.8-122.4-54-195.7-54a406 406 0 0 0 -72 6.4 238.5 238.5 0 0 0 -49.5-36.6C99.7-11.7 40.9 .7 11.1 11.4A14.3 14.3 0 0 0 5.6 34.8C26.5 56.5 61.2 99.3 52.7 138.3c-33.1 33.9-51.1 74.8-51.1 117.3 0 43.4 18 84.2 51.1 118.1 8.5 39-26.2 81.8-47.1 103.5a14.3 14.3 0 0 0 5.6 23.3c29.7 10.7 88.5 23.1 155.3-10.2a238.7 238.7 0 0 0 49.5-36.6A406 406 0 0 0 288 460.1c73.3 0 142.8-19.2 195.7-54 27.4-18 49.1-39.4 64.7-63.6 17.3-26.9 26.1-55.9 26.1-86.1C574.4 225.4 565.6 196.4 548.3 169.5zM285 409.9a345.7 345.7 0 0 1 -89.4-11.5l-20.1 19.4a184.4 184.4 0 0 1 -37.1 27.6 145.8 145.8 0 0 1 -52.5 14.9c1-1.8 1.9-3.6 2.8-5.4q30.3-55.7 16.3-100.1c-33-26-52.8-59.2-52.8-95.4 0-83.1 104.3-150.5 232.8-150.5s232.9 67.4 232.9 150.5C517.9 342.5 413.6 409.9 285 409.9z"/></svg>',
        "seopress" => '<svg id="uuid-4f6a8a41-18e3-4f77-b5a9-4b1b38aa2dc9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 899.655 494.3094"><path id="uuid-a155c1ca-d868-4653-8477-8dd87240a765" d="M327.3849,435.128l-299.9999-.2497c-16.2735,1.1937-28.4981,15.3538-27.3044,31.6273,1.0719,14.6128,12.6916,26.2325,27.3044,27.3044l299.9999,.2497c16.2735-1.1937,28.4981-15.3538,27.3044-31.6273-1.0718-14.6128-12.6916-26.2325-27.3044-27.3044Z" style="fill:#fff"/><path id="uuid-e30ba4c6-4769-466b-a03a-e644c5198e56" d="M27.3849,58.9317l299.9999,.2497c16.2735-1.1937,28.4981-15.3537,27.3044-31.6273-1.0718-14.6128-12.6916-26.2325-27.3044-27.3044L27.3849,0C11.1114,1.1937-1.1132,15.3537,.0805,31.6273c1.0719,14.6128,12.6916,26.2325,27.3044,27.3044Z" style="fill:#fff"/><path id="uuid-2bbd52d6-aec1-4689-9d4c-23c35d4f22b8" d="M652.485,.2849c-124.9388,.064-230.1554,93.4132-245.1001,217.455H27.3849c-16.2735,1.1937-28.4981,15.3537-27.3044,31.6272,1.0719,14.6128,12.6916,26.2325,27.3044,27.3044H407.3849c16.2298,135.4454,139.187,232.0888,274.6323,215.8589,135.4455-16.2298,232.0888-139.1869,215.8589-274.6324C882.9921,93.6834,777.5884,.2112,652.485,.2849Zm0,433.4217c-102.9754,0-186.4533-83.478-186.4533-186.4533,0-102.9753,83.4781-186.4533,186.4533-186.4533,102.9754,0,186.4533,83.478,186.4533,186.4533,.0524,102.9753-83.383,186.4959-186.3583,186.5483-.0316,0-.0634,0-.0951,0v-.095Z" style="fill:#fff"/></svg>',
        "shopping-cart" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>',
        "sliders" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 128C78.3 128 64 142.3 64 160C64 177.7 78.3 192 96 192L182.7 192C195 220.3 223.2 240 256 240C288.8 240 317 220.3 329.3 192L544 192C561.7 192 576 177.7 576 160C576 142.3 561.7 128 544 128L329.3 128C317 99.7 288.8 80 256 80C223.2 80 195 99.7 182.7 128L96 128zM96 288C78.3 288 64 302.3 64 320C64 337.7 78.3 352 96 352L342.7 352C355 380.3 383.2 400 416 400C448.8 400 477 380.3 489.3 352L544 352C561.7 352 576 337.7 576 320C576 302.3 561.7 288 544 288L489.3 288C477 259.7 448.8 240 416 240C383.2 240 355 259.7 342.7 288L96 288zM96 448C78.3 448 64 462.3 64 480C64 497.7 78.3 512 96 512L150.7 512C163 540.3 191.2 560 224 560C256.8 560 285 540.3 297.3 512L544 512C561.7 512 576 497.7 576 480C576 462.3 561.7 448 544 448L297.3 448C285 419.7 256.8 400 224 400C191.2 400 163 419.7 150.7 448L96 448z"/></svg>',
        "sooz-with-ai-for-seo" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 731.25 333.75" width="131.46" height="60" style="max-height:80px; width:auto;" xml:space="preserve"><path fill="#0066aa" d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path fill="#0066aa" d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path fill="#0066aa" d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/><g><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M127.495,252.48c0.96,0,1.8,0.234,2.52,0.701c0.72,0.47,1.2,1.099,1.44,1.891L158.095,327h-10.92l-21.48-61.128c-0.321-1.008-0.66-2.033-1.02-3.078c-0.36-1.043-0.701-2.033-1.02-2.97h-3.36c-0.321,0.937-0.642,1.927-0.96,2.97c-0.321,1.045-0.681,2.07-1.08,3.078L96.775,327h-10.92l26.64-71.928c0.24-0.792,0.72-1.421,1.44-1.891c0.72-0.467,1.56-0.701,2.52-0.701H127.495z M142.855,295.464v8.208h-42v-8.208H142.855z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M183.895,252.48V327h-10.56v-74.52H183.895z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M269.214,274.404v7.235h-39.48v-6.695l11.16-0.54H269.214z M257.334,249.564c1.279,0,2.919,0.019,4.92,0.054c1.999,0.037,4.039,0.107,6.12,0.216c2.08,0.108,3.879,0.27,5.4,0.486l-0.84,6.804h-12c-3.84,0-6.54,0.686-8.1,2.052c-1.56,1.369-2.34,3.637-2.34,6.805V327h-10.2v-61.992c0-3.311,0.559-6.102,1.68-8.37c1.119-2.268,2.919-4.013,5.4-5.237C249.853,250.177,253.173,249.564,257.334,249.564z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M308.693,273.108c6.72,0,12.099,0.9,16.14,2.699c4.039,1.801,6.939,4.735,8.7,8.803c1.759,4.068,2.64,9.449,2.64,16.146s-0.881,12.079-2.64,16.146c-1.761,4.068-4.661,7.003-8.7,8.802c-4.041,1.799-9.42,2.7-16.14,2.7c-6.641,0-11.981-0.901-16.02-2.7c-4.041-1.799-6.96-4.733-8.76-8.802c-1.8-4.067-2.7-9.45-2.7-16.146s0.9-12.077,2.7-16.146c1.8-4.067,4.72-7.002,8.76-8.803C296.712,274.009,302.052,273.108,308.693,273.108z M308.693,280.884c-4.241,0-7.581,0.594-10.02,1.782c-2.441,1.188-4.181,3.223-5.22,6.102c-1.041,2.881-1.56,6.877-1.56,11.988c0,5.113,0.52,9.109,1.56,11.988c1.039,2.881,2.779,4.914,5.22,6.102c2.439,1.188,5.779,1.782,10.02,1.782c4.239,0,7.599-0.594,10.08-1.782c2.479-1.188,4.239-3.221,5.28-6.102c1.039-2.879,1.56-6.875,1.56-11.988c0-5.111-0.521-9.107-1.56-11.988c-1.041-2.879-2.801-4.914-5.28-6.102C316.292,281.478,312.933,280.884,308.693,280.884z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M363.652,274.404l1.32,10.044l0.96,1.62V327h-10.2v-52.596H363.652z M392.332,273.108l-1.2,8.64h-3.36c-3.439,0-6.881,0.631-10.319,1.89c-3.44,1.261-7.641,3.043-12.6,5.347l-0.84-5.725c4.32-3.167,8.659-5.651,13.02-7.452c4.359-1.799,8.58-2.699,12.659-2.699H392.332z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M459.41,251.184c3.918,0.073,7.959,0.254,12.119,0.54c4.159,0.289,8.319,0.829,12.48,1.62l-0.72,6.912c-3.441-0.144-7.241-0.27-11.4-0.378c-4.16-0.108-8.16-0.162-12-0.162c-2.961,0-5.501,0.091-7.62,0.271c-2.12,0.181-3.86,0.612-5.22,1.296c-1.361,0.685-2.34,1.801-2.94,3.348c-0.6,1.549-0.899,3.69-0.899,6.426c0,4.104,0.819,6.967,2.46,8.586c1.639,1.62,4.299,2.827,7.979,3.618l16.8,3.78c6.399,1.368,10.78,3.763,13.141,7.182c2.358,3.421,3.54,8.011,3.54,13.771c0,4.319-0.54,7.813-1.62,10.476c-1.08,2.665-2.741,4.698-4.98,6.103c-2.24,1.403-5.12,2.376-8.64,2.916c-3.521,0.54-7.68,0.81-12.479,0.81c-2.721,0-6.261-0.107-10.62-0.324c-4.361-0.216-9.381-0.793-15.061-1.728l0.72-7.021c4.72,0.146,8.521,0.271,11.4,0.378c2.88,0.108,5.358,0.162,7.44,0.162c2.079,0,4.239,0,6.479,0c4.239,0,7.579-0.286,10.021-0.863c2.439-0.576,4.158-1.729,5.159-3.456c1-1.729,1.5-4.283,1.5-7.668c0-2.879-0.359-5.111-1.079-6.696c-0.721-1.583-1.86-2.789-3.421-3.618c-1.56-0.827-3.539-1.493-5.939-1.998l-17.16-3.888c-6-1.367-10.221-3.743-12.66-7.128c-2.441-3.384-3.66-7.92-3.66-13.608c0-4.32,0.54-7.793,1.62-10.422c1.08-2.627,2.719-4.606,4.92-5.94c2.2-1.331,4.98-2.214,8.341-2.646C450.77,251.4,454.77,251.184,459.41,251.184z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M522.528,252.372c4,0,8.02,0,12.061,0c4.038,0,8.04,0.054,12,0.162c3.96,0.107,7.779,0.27,11.46,0.485l-0.48,7.452h-33.239c-2.481,0-4.302,0.559-5.461,1.675c-1.16,1.116-1.739,2.934-1.739,5.453v44.28c0,2.521,0.579,4.357,1.739,5.508c1.159,1.153,2.979,1.729,5.461,1.729h33.239l0.48,7.344c-3.681,0.216-7.5,0.361-11.46,0.432c-3.96,0.073-7.962,0.125-12,0.162c-4.041,0.036-8.061,0.055-12.061,0.055c-4.88,0-8.741-1.17-11.58-3.511c-2.84-2.339-4.301-5.489-4.38-9.449v-48.816c0.079-4.031,1.54-7.199,4.38-9.504C513.787,253.524,517.648,252.372,522.528,252.372z M508.729,283.8h44.16v7.668h-44.16V283.8z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M607.009,251.076c6,0,11.1,0.647,15.3,1.943c4.2,1.297,7.561,3.439,10.08,6.427c2.521,2.988,4.359,6.966,5.521,11.934c1.158,4.968,1.739,11.089,1.739,18.36c0,7.272-0.581,13.392-1.739,18.359c-1.161,4.969-3,8.947-5.521,11.935c-2.52,2.988-5.88,5.13-10.08,6.426s-9.3,1.944-15.3,1.944s-11.1-0.648-15.3-1.944s-7.581-3.438-10.141-6.426c-2.561-2.987-4.421-6.966-5.58-11.935c-1.16-4.968-1.739-11.087-1.739-18.359c0-7.271,0.579-13.393,1.739-18.36c1.159-4.968,3.02-8.945,5.58-11.934c2.56-2.987,5.94-5.13,10.141-6.427C595.909,251.724,601.009,251.076,607.009,251.076z M607.009,259.608c-5.441,0-9.74,0.937-12.9,2.808c-3.161,1.873-5.399,4.986-6.72,9.342c-1.32,4.357-1.979,10.352-1.979,17.982c0,7.56,0.659,13.537,1.979,17.928c1.32,4.393,3.559,7.524,6.72,9.396c3.16,1.873,7.459,2.808,12.9,2.808c5.439,0,9.738-0.935,12.9-2.808c3.159-1.872,5.399-5.004,6.72-9.396c1.319-4.391,1.979-10.368,1.979-17.928c0-7.631-0.66-13.625-1.979-17.982c-1.32-4.355-3.561-7.469-6.72-9.342C616.747,260.545,612.448,259.608,607.009,259.608z"/></g></svg>',
        'sooz' => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="145.28" height="40" viewBox="0 0 731.25 201.25" enable-background="new 0 0 731.25 201.25" xml:space="preserve" fill="#0066aa" style="max-height:40px; width:auto;"><path d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/></svg>',
        'sooz-oo' => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="72.30" height="40" viewBox="0 0 363.75 201.25" enable-background="new 0 0 363.75 201.25" xml:space="preserve" style="max-height:40px; width:auto;"><path fill="#0066aa" d="M496.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H537.61v33.215H412.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H374.526V5.501H496.595z"/><path fill="#0066aa" d="M-11.02,5.501v33.206h-119.841c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825h-124.792v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642l-101.365-44.188c-9.945-4.309-17.375-10.075-22.329-17.299c-4.958-7.225-7.435-16.295-7.435-27.174c0-13.328,3.818-23.594,11.436-30.816c7.613-7.227,18.859-10.837,33.749-10.837H-11.02z"/><path fill="#0066aa" d="M337.13,29.148C322.678,13.38,301.924,5.501,274.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489H88.788c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18H88.788c-27.207,0-48.001,7.879-62.414,23.646C11.956,44.908,4.768,68.838,4.768,100.926c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C358.772,68.836,351.582,44.906,337.13,29.148z"/></svg>',
        "square-check" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
        "square-facebook" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64h98.2V334.2H109.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H255V480H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64z"/></svg>',
        "square-twitter-x" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm297.1 84L257.3 234.6 379.4 396H283.8L209 298.1 123.3 396H75.8l111-126.9L69.7 116h98l67.7 89.5L313.6 116h47.5zM323.3 367.6L153.4 142.9H125.1L296.9 367.6h26.3z"/></svg>',
        "square-xmark" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
        "star" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>',
        "stripe" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M492.4 220.8c-8.9 0-18.7 6.7-18.7 22.7h36.7c0-16-9.3-22.7-18-22.7zM375 223.4c-8.2 0-13.3 2.9-17 7l.2 52.8c3.5 3.7 8.5 6.7 16.8 6.7 13.1 0 21.9-14.3 21.9-33.4 0-18.6-9-33.2-21.9-33.1zM528 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM122.2 281.1c0 25.6-20.3 40.1-49.9 40.3-12.2 0-25.6-2.4-38.8-8.1v-33.9c12 6.4 27.1 11.3 38.9 11.3 7.9 0 13.6-2.1 13.6-8.7 0-17-54-10.6-54-49.9 0-25.2 19.2-40.2 48-40.2 11.8 0 23.5 1.8 35.3 6.5v33.4c-10.8-5.8-24.5-9.1-35.3-9.1-7.5 0-12.1 2.2-12.1 7.7 0 16 54.3 8.4 54.3 50.7zm68.8-56.6h-27V275c0 20.9 22.5 14.4 27 12.6v28.9c-4.7 2.6-13.3 4.7-24.9 4.7-21.1 0-36.9-15.5-36.9-36.5l.2-113.9 34.7-7.4v30.8H191zm74 2.4c-4.5-1.5-18.7-3.6-27.1 7.4v84.4h-35.5V194.2h30.7l2.2 10.5c8.3-15.3 24.9-12.2 29.6-10.5h.1zm44.1 91.8h-35.7V194.2h35.7zm0-142.9l-35.7 7.6v-28.9l35.7-7.6zm74.1 145.5c-12.4 0-20-5.3-25.1-9l-.1 40.2-35.5 7.5V194.2h31.3l1.8 8.8c4.9-4.5 13.9-11.1 27.8-11.1 24.9 0 48.4 22.5 48.4 63.8 0 45.1-23.2 65.5-48.6 65.6zm160.4-51.5h-69.5c1.6 16.6 13.8 21.5 27.6 21.5 14.1 0 25.2-3 34.9-7.9V312c-9.7 5.3-22.4 9.2-39.4 9.2-34.6 0-58.8-21.7-58.8-64.5 0-36.2 20.5-64.9 54.3-64.9 33.7 0 51.3 28.7 51.3 65.1 0 3.5-.3 10.9-.4 12.9z"/></svg>',
        "subtitle" => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="70" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="70" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
        "subtitles" => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="50" width="30" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="55" y="50" width="30" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="15" y="70" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="70" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
        'trash' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z"/></svg>',
        "triangle-exclamation" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>',
        "xmark" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-secondary" d="M297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6z"/></svg>',
        "yoast" => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M91.3 76h186l-7 18.9h-179c-39.7 0-71.9 31.6-71.9 70.3v205.4c0 35.4 24.9 70.3 84 70.3V460H91.3C41.2 460 0 419.8 0 370.5V165.2C0 115.9 40.7 76 91.3 76zm229.1-56h66.5C243.1 398.1 241.2 418.9 202.2 459.3c-20.8 21.6-49.3 31.7-78.3 32.7v-51.1c49.2-7.7 64.6-49.9 64.6-75.3 0-20.1 .6-12.6-82.1-223.2h61.4L218.2 299 320.4 20zM448 161.5V460H234c6.6-9.6 10.7-16.3 12.1-19.4h182.5V161.5c0-32.5-17.1-51.9-48.2-62.9l6.7-17.6c41.7 13.6 60.9 43.1 60.9 80.5z"/></svg>',
        "woocommerce" => '<svg preserveAspectRatio="xMidYMid" version="1.1" viewBox="0 0 256 153" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"><title>WooCommerce Logo</title><metadata><rdf:RDF><cc:Work rdf:about=""><dc:format>image/svg+xml</dc:format><dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"/><dc:title/></cc:Work></rdf:RDF></metadata><path d="m23.759 0h208.38c13.187 0 23.863 10.675 23.863 23.863v79.542c0 13.187-10.675 23.863-23.863 23.863h-74.727l10.257 25.118-45.109-25.118h-98.695c-13.187 0-23.863-10.675-23.863-23.863v-79.542c-0.10466-13.083 10.571-23.863 23.758-23.863z" fill="#7f54b3"/><path d="m14.578 21.75c1.4569-1.9772 3.6423-3.0179 6.5561-3.226 5.3073-0.41626 8.3252 2.0813 9.0537 7.4927 3.226 21.75 6.7642 40.169 10.511 55.259l22.79-43.395c2.0813-3.9545 4.6829-6.0358 7.8049-6.2439 4.5789-0.3122 7.3886 2.6016 8.5333 8.7415 2.6016 13.841 5.9317 25.6 9.8862 35.59 2.7057-26.433 7.2846-45.476 13.737-57.236 1.561-2.9138 3.8504-4.3707 6.8683-4.5789 2.3935-0.20813 4.5789 0.52033 6.5561 2.0813 1.9772 1.561 3.0179 3.5382 3.226 5.9317 0.10406 1.8732-0.20813 3.4341-1.0407 4.9951-4.0585 7.4927-7.3886 20.085-10.094 37.567-2.6016 16.963-3.5382 30.179-2.9138 39.649 0.20813 2.6016-0.20813 4.8911-1.2488 6.8683-1.2488 2.2894-3.122 3.5382-5.5154 3.7463-2.7057 0.20813-5.5154-1.0406-8.2211-3.8504-9.678-9.8862-17.379-24.663-22.998-44.332-6.7642 13.32-11.759 23.311-14.985 29.971-6.1398 11.759-11.343 17.795-15.714 18.107-2.8098 0.20813-5.2033-2.1854-7.2846-7.1805-5.3073-13.633-11.031-39.961-17.171-78.985-0.41626-2.7057 0.20813-5.0992 1.665-6.9724zm223.64 16.338c-3.7463-6.5561-9.2618-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0.10406-8.1171-1.665-14.881-4.9951-20.501zm-13.112 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93659-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0 2.7057-0.20813 5.3073-0.72846 7.7008zm-52.033-28.826c-3.7463-6.5561-9.3659-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0-8.1171-1.665-14.881-4.9951-20.501zm-13.216 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93658-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0.10406 2.7057-0.20813 5.3073-0.72846 7.7008z" fill="#fff"/></svg>'
    );
}

const AI4SEO_STRIPE_BILLING_URL = "https://sooz.ai/manage-plan";
const AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME = "post";

// Constants for the wp_options entries
const AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME = "ai4seo_fully_covered_metadata_post_ids";
const AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME = "ai4seo_missing_metadata_post_ids";
const AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME = "ai4seo_pending_metadata_post_ids";
const AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME = "ai4seo_processing_metadata_post_ids";
const AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME = "ai4seo_generated_metadata_post_ids";
const AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME = "ai4seo_failed_metadata_post_ids";
const AI4SEO_LATEST_ACTIVITY_OPTION_NAME = "_ai4seo_latest_activity"; # todo: replace with database table

const AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_fully_covered_attachment_attributes_post_ids";
const AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_missing_attachment_attributes_post_ids";
const AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_pending_attachment_attributes_post_ids";
const AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_processing_attachment_attributes_post_ids";
const AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_generated_attachment_attributes_post_ids";
const AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = "ai4seo_failed_attachment_attributes_post_ids";

const AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME = "_ai4seo_additional_tos_accept_details";
const AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME = "_ai4seo_additional_tos_accept_details_last_try_timestamp";

// all wp_options that contain post ids
const AI4SEO_ALL_POST_ID_OPTIONS = array(
    AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,

    AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// all wp_options that define the seo coverage
// a post id cannot be in MISSING and one of the other options at the same time
const AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS = array(
    AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,

    AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// all wp-Options that define the generation status of a given post
// a post id cannot be in PENDING and PROCESSING at the same time
const AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS = array(
    AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
    AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,

    AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
    AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// region CRON JOBS ========================================================================================== \\

const AI4SEO_BULK_GENERATION_CRON_JOB_NAME = "ai4seo_automated_generation_cron_job";
const AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME = "ai4seo_analyze_plugin_performance";


// endregion
// region THIRD PARTY PLUGINS ============================================================================== \\

// Constants for third party plugin identifiers
// editors
const AI4SEO_THIRD_PARTY_PLUGIN_ELEMENTOR = "elementor";

// shops
const AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE = "woocommerce";

// traditional seo plugins
const AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO = "yoast-seo";
const AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO = "all-in-one-seo-pack";
const AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK = "the-seo-framework";
const AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH = "rank-math";
const AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS = "seopress";
const AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK = "seo-simple-pack";
const AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO = "slim-seo";
const AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO = "squirrly-seo";
const AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY = "seo-key";

// editors + seo plugins
const AI4SEO_THIRD_PARTY_PLUGIN_BETHEME = "betheme";

// social media plugins
const AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL = "blog2social";

// multi-language plugins
const AI4SEO_THIRD_PARTY_PLUGIN_WPML = "wpml";

// attachments / images plugins
const AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY = "nextgen-gallery";

// details for the third party seo plugins
function ai4seo_get_third_party_seo_plugin_details(): array {
    return array(
        AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO => array(
            'name' => 'Yoast SEO',
            'icon' => 'yoast',
            'icon-css-class' => 'ai4seo-purple-icon',
            'generation-field-postmeta-keys' => array(
                'focus-keyphrase' => '_yoast_wpseo_focuskw',
                'meta-title' => '_yoast_wpseo_title',
                'meta-description' => '_yoast_wpseo_metadesc',
                'facebook-title' => '_yoast_wpseo_opengraph-title',
                'facebook-description' => '_yoast_wpseo_opengraph-description',
                'twitter-title' => '_yoast_wpseo_twitter-title',
                'twitter-description' => '_yoast_wpseo_twitter-description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_BETHEME => array(
            'name' => 'BeTheme',
            'icon' => 'betheme',
            'icon-css-class' => 'ai4seo-blue-icon',
            'generation-field-postmeta-keys' => array(
                'meta-title' => 'mfn-meta-seo-title',
                'meta-description' => 'mfn-meta-seo-description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO => array(
            'name' => 'All in One SEO',
            'icon' => 'all-in-one-seo',
            'generation-field-postmeta-keys' => array( # workaround: in addition, this plugin saves its data into wp_ai4seo_posts
                'meta-title' => '_aioseo_title',
                'meta-description' => '_aioseo_description',
                'facebook-title' => '_aioseo_og_title',
                'facebook-description' => '_aioseo_og_description',
                'twitter-title' => '_aioseo_twitter_title',
                'twitter-description' => '_aioseo_twitter_description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH => array(
            'name' => 'Rank Math',
            'icon' => 'rank-math',
            'icon-css-class' => 'ai4seo-purple-icon',
            'seo-score-postmeta-key' => 'rank_math_seo_score', # todo: make this dynamic
            'generation-field-postmeta-keys' => array(
                'focus-keyphrase' => 'rank_math_focus_keyword',
                'meta-title' => 'rank_math_title',
                'meta-description' => 'rank_math_description',
                'facebook-title' => 'rank_math_facebook_title',
                'facebook-description' => 'rank_math_facebook_description',
                'twitter-title' => 'rank_math_twitter_title',
                'twitter-description' => 'rank_math_twitter_description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK => array(
            'name' => 'SEO Simple Pack',
            'generation-field-postmeta-keys' => array(
                'meta-title' => 'ssp_meta_title',
                'meta-description' => 'ssp_meta_description',
                'facebook-title' => 'ssp_meta_title',
                'facebook-description' => 'ssp_meta_description',
                'twitter-title' => 'ssp_meta_title',
                'twitter-description' => 'ssp_meta_description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS => array(
            'name' => 'SEOPress',
            'icon' => 'seopress',
            'generation-field-postmeta-keys' => array(
                'meta-title' => '_seopress_titles_title',
                'meta-description' => '_seopress_titles_desc',
                'facebook-title' => '_seopress_social_fb_title',
                'facebook-description' => '_seopress_social_fb_desc',
                'twitter-title' => '_seopress_social_twitter_title',
                'twitter-description' => '_seopress_social_twitter_desc',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO => array(
            'name' => 'Slim SEO',
            'generation-field-postmeta-keys' => array(
                'meta-title' => '_ai4seo_workaround',
                'meta-description' => '_ai4seo_workaround',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO => array(
            'name' => 'Squirrly SEO',
            'generation-field-postmeta-keys' => array(
                'meta-title' => '_ai4seo_workaround',
                'meta-description' => '_ai4seo_workaround',
                'facebook-title' => '_ai4seo_workaround',
                'facebook-description' => '_ai4seo_workaround',
                'twitter-title' => '_ai4seo_workaround',
                'twitter-description' => '_ai4seo_workaround',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK => array(
            'name' => 'The SEO Framework',
            'generation-field-postmeta-keys' => array(
                'meta-title' => '_genesis_title',
                'meta-description' => '_genesis_description',
                'facebook-title' => '_open_graph_title',
                'facebook-description' => '_open_graph_description',
                'twitter-title' => '_twitter_title',
                'twitter-description' => '_twitter_description',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL => array(
            'name' => 'Blog2Social',
            'generation-field-postmeta-keys' => array(
                'facebook-title' => '_ai4seo_workaround',
                'facebook-description' => '_ai4seo_workaround',
                'twitter-title' => '_ai4seo_workaround',
                'twitter-description' => '_ai4seo_workaround',
            ),
        ),
        AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY => array(
            'name' => 'SEOKEY',
            'generation-field-postmeta-keys' => array(
                'meta-title' => 'seokey-metatitle',
                'meta-description' => 'seokey-metadesc',
            ),
        ),
    );
}

function ai4seo_get_allowed_currencies(): array {
    return array(
        "AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN",
        "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL",
        "BSD", "BTC", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLF",
        "CLP", "CNH", "CNY", "COP", "CRC", "CUC", "CUP", "CVE", "CZK", "DJF",
        "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "GBP",
        "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL",
        "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK",
        "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KMF", "KPW", "KRW",
        "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD",
        "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRU", "MUR", "MVR", "MWK",
        "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR",
        "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD",
        "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLL",
        "SOS", "SRD", "SSP", "STD", "STN", "SVC", "SYP", "SZL", "THB", "TJS",
        "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "USD",
        "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XAG", "XAU", "XCD",
        "XDR", "XOF", "XPD", "XPF", "XPT", "YER", "ZAR", "ZMW", "ZWL"
    );
}


// endregion _________________________________________________________________________________ \\
// region PLUGIN'S SETTINGS ============================================================================== \\

/** Check .agent/rules/settings.md for a guide on how to use Plugin's settings */

// SETTINGS FROM THE SETTINGS PAGE
const AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS = 'show_advanced_settings';
const AI4SEO_SETTING_VISIBLE_META_TAGS = 'visible_meta_tags'; # deprecated < 2.2.0
const AI4SEO_SETTING_ACTIVE_META_TAGS = 'active_meta_tags'; # added in 2.2.0
const AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE = 'metadata_fallback_meta_title';
const AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION = 'metadata_fallback_meta_description';
const AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE = 'metadata_fallback_facebook_title';
const AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION = 'metadata_fallback_facebook_description';
const AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE = 'metadata_fallback_twitter_title';
const AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION = 'metadata_fallback_twitter_description';
const AI4SEO_SETTING_META_TAG_OUTPUT_MODE = 'meta_tags_output_method';
const AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS = 'apply_changes_to_this_party_seo_plugins';
const AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA = 'sync_only_these_metadata';
const AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE = 'metadata_generation_language';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE = 'attachment_attributes_generation_language';
const AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES = 'active_attachment_attributes';
const AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA = 'overwrite_existing_metadata';
const AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES = 'overwrite_existing_attachment_attributes';
const AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES = 'generate_metadata_for_fully_covered_entries';
const AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES = 'generate_attachment_attributes_for_fully_covered_entries';
const AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION = 'enable_render_level_alt_text_injection';
const AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION = 'enable_js_alt_text_injection';
const AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE = 'image_title_injection_mode';
const AI4SEO_SETTING_METADATA_PREFIXES = 'metadata_prefix';
const AI4SEO_SETTING_METADATA_SUFFIXES = 'metadata_suffix';
const AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA = 'include_product_price_in_metadata';
const AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA = 'focus_keyphrase_behavior_on_existing_metadata';
const AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE = 'use_existing_metadata_as_reference';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES = 'attachment_attributes_prefix';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES = 'attachment_attributes_suffix';
const AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE = 'use_existing_attachment_attributes_as_reference';
const AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION = 'enable_enhanced_entity_recognition';
const AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION = 'enable_enhanced_celebrity_recognition';
const AI4SEO_SETTING_IMAGE_UPLOAD_METHOD = 'image_upload_method';
const AI4SEO_SETTING_ALLOWED_USER_ROLES = 'allowed_user_roles';
const AI4SEO_SETTING_DISABLED_POST_TYPES = 'disabled_post_types';
const AI4SEO_SETTING_BULK_GENERATION_DURATION = 'bulk_generation_duration';
const AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS = 'disable_heavy_db_operations';
const AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE = 'enable_frontend_cache_purge';
const AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES = 'deep_context_search_for_images';
const AI4SEO_SETTING_DEBUG_OUTPUT_MODE = 'debug_output_mode';
const AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE = 'query_ids_chunk_size';

// settings option values
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_GENERATE_KEYPHRASE = 'generate_keyphrase';
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP = 'skip';
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE = 'regenerate';

const AI4SEO_ALL_SETTING_PAGE_SETTINGS = array(
    AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS,
    AI4SEO_SETTING_ACTIVE_META_TAGS,
    AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE,
    AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION,
    AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE,
    AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION,
    AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE,
    AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION,
    AI4SEO_SETTING_META_TAG_OUTPUT_MODE,
    AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS,
    AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA,
    AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE,
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE,
    AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES,
    AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA,
    AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES,
    AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES,
    AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES,
    AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION,
    AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION,
    AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE,
    AI4SEO_SETTING_METADATA_PREFIXES,
    AI4SEO_SETTING_METADATA_SUFFIXES,
    AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA,
    AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA,
    AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE,
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES,
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES,
    AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE,
    AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION,
    AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION,
    AI4SEO_SETTING_IMAGE_UPLOAD_METHOD,
    AI4SEO_SETTING_ALLOWED_USER_ROLES,
    AI4SEO_SETTING_DISABLED_POST_TYPES,
    AI4SEO_SETTING_BULK_GENERATION_DURATION,
    AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS,
    AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE,
    AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES,
    AI4SEO_SETTING_DEBUG_OUTPUT_MODE,
    AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE,
);

// SETTINGS FROM THE SEO AUTOPILOT MODAL
const AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES = 'enabled_bulk_generation_post_types';
const AI4SEO_SETTING_BULK_GENERATION_ORDER = 'bulk_generation_order';
const AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER = 'bulk_generation_new_or_existing_filter';

const AI4SEO_ALL_SEO_AUTOPILOT_SETTINGS = array(
    AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES,
    AI4SEO_SETTING_BULK_GENERATION_ORDER,
    AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER,
);

// SETTINGS FROM THE ACCOUNT PAGE
const AI4SEO_SETTING_ENABLE_INCOGNITO_MODE = 'enable_incognito_mode';
const AI4SEO_SETTING_INCOGNITO_MODE_USER_ID = 'incognito_mode_user_id';
const AI4SEO_SETTING_ENABLE_WHITE_LABEL = 'enable_white_label';
const AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME = 'installed_plugins_plugin_name';
const AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION = 'installed_plugins_plugin_description';
const AI4SEO_SETTING_ADD_GENERATOR_HINTS = 'add_generator_hints';
const AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT = 'meta_tags_block_starting_hint';
const AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT = 'meta_tags_block_ending_hint';

const AI4SEO_ALL_ACCOUNT_PAGE_SETTINGS = array(
    AI4SEO_SETTING_ENABLE_INCOGNITO_MODE,
    AI4SEO_SETTING_INCOGNITO_MODE_USER_ID,
    AI4SEO_SETTING_ENABLE_WHITE_LABEL,
    AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME,
    AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION,
    AI4SEO_SETTING_ADD_GENERATOR_HINTS,
    AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT,
    AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT,
);

// SETTINGS FROM THE GET MORE CREDITS MODAL
const AI4SEO_SETTING_PREFERRED_CURRENCY = 'preferred_currency';
const AI4SEO_SETTING_PAYG_ENABLED = 'payg_enabled';
const AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID = 'payg_stripe_price_id';
const AI4SEO_SETTING_PAYG_DAILY_BUDGET = 'payg_daily_budget';
const AI4SEO_SETTING_PAYG_MONTHLY_BUDGET = 'payg_monthly_budget';

const AI4SEO_ALL_GET_MORE_CREDITS_MODAL_SETTINGS = array(
    AI4SEO_SETTING_PREFERRED_CURRENCY,
    AI4SEO_SETTING_PAYG_ENABLED,
    AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID,
    AI4SEO_SETTING_PAYG_DAILY_BUDGET,
    AI4SEO_SETTING_PAYG_MONTHLY_BUDGET,
);

// NOT IMPORTABLE SETTINGS
const AI4SEO_NOT_IMPORTABLE_SETTINGS = array(
    AI4SEO_SETTING_INCOGNITO_MODE_USER_ID
);

// DEFAULT SETTINGS
const AI4SEO_DEFAULT_SETTINGS = array(
    AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS => "hide",
    AI4SEO_SETTING_BULK_GENERATION_DURATION => 60,
    AI4SEO_SETTING_META_TAG_OUTPUT_MODE => "replace",
    AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS => array(AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO, AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH, AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS, AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK, AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK, AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY),
    AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA => array("focus-keyphrase", "meta-title", "meta-description", "facebook-title", "facebook-description", "twitter-title", "twitter-description"),
    AI4SEO_SETTING_ALLOWED_USER_ROLES => array("administrator"),
    AI4SEO_SETTING_DISABLED_POST_TYPES => array(),
    AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES => array(),
    AI4SEO_SETTING_BULK_GENERATION_ORDER => "newest",
    AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER => "both",
    AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE => "auto",
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE => "auto",
    AI4SEO_SETTING_ACTIVE_META_TAGS => array("focus-keyphrase", "meta-title", "meta-description", "keywords", "facebook-title", "facebook-description", "twitter-title", "twitter-description"),
    AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE => 'no-fallback',
    AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION => 'no-fallback',
    AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE => 'no-fallback',
    AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION => 'no-fallback',
    AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE => 'no-fallback',
    AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION => 'no-fallback',
    AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES => array("title", "alt-text", "caption", "description"),
    AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA => array(),
    AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES => array(),
    AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES => false,
    AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA => 'never',
    AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA => AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP,
    AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE => false,
    AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES => false,
    AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION => false,
    AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION => false,
    AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS => false,
    AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE => false,
    AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES => false,
    AI4SEO_SETTING_DEBUG_OUTPUT_MODE => "none",
    AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE => 1000,
    AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE => false,
    AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION => true,
    AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION => false,
    AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE => "disabled",
    AI4SEO_SETTING_METADATA_PREFIXES => array(
        "meta-title" => "",
        "meta-description" => "",
        "facebook-title" => "",
        "facebook-description" => "",
        "twitter-title" => "",
        "twitter-description" => "",
    ),
    AI4SEO_SETTING_METADATA_SUFFIXES => array(
        "meta-title" => "",
        "meta-description" => "",
        "facebook-title" => "",
        "facebook-description" => "",
        "twitter-title" => "",
        "twitter-description" => "",
    ),
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES => array(
        "title" => "",
        "alt-text" => "",
        "caption" => "",
        "description" => "",
    ),
    AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES => array(
        "title" => "",
        "alt-text" => "",
        "caption" => "",
        "description" => "",
    ),
    AI4SEO_SETTING_IMAGE_UPLOAD_METHOD => "auto",
    AI4SEO_SETTING_ENABLE_INCOGNITO_MODE => false,
    AI4SEO_SETTING_INCOGNITO_MODE_USER_ID => "0",
    AI4SEO_SETTING_ENABLE_WHITE_LABEL => false,
    AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME => AI4SEO_PLUGIN_NAME,
    AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION => AI4SEO_PLUGIN_DESCRIPTION,
    AI4SEO_SETTING_ADD_GENERATOR_HINTS => true,
    AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT => "[{NAME}] This site is optimized with the {NAME} plugin v{VERSION} - {WEBSITE}",
    AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT => "[{NAME}] End",
    AI4SEO_SETTING_PREFERRED_CURRENCY => "usd",
    AI4SEO_SETTING_PAYG_ENABLED => false,
    AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID => "", # defaults to previously purchased pack
    AI4SEO_SETTING_PAYG_DAILY_BUDGET => 0, # defaults to cost credits pack
    AI4SEO_SETTING_PAYG_MONTHLY_BUDGET => 0, # defaults to recommended credits pack entry,
);

const AI4SEO_METADATA_FALLBACK_MAPPING = array(
    'meta-title' => AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE,
    'meta-description' => AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION,
    'facebook-title' => AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE,
    'facebook-description' => AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION,
    'twitter-title' => AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE,
    'twitter-description' => AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION,
);

$ai4seo_settings = AI4SEO_DEFAULT_SETTINGS;
$ai4seo_are_settings_initialized = false;

$ai4seo_fallback_allowed_user_roles = array("administrator" => "Administrator");
$ai4seo_forbidden_allowed_user_roles = array("subscriber", "customer");
$ai4seo_can_manage_this_plugin = null; # cache variable

const AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS = array("random", "oldest", "newest");
const AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS = array("both", "new", "existing");
const AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS = array(250, 500, 1000, 2500, 5000, 10000, 20000);

// init parameters with translation
add_action("init", function() {
    define('AI4SEO_BULK_GENERATION_ORDER_TRANSLATED_OPTIONS', array(
            "random" => __("Random", "ai-for-seo"),
            "oldest" => __("Oldest to newest", "ai-for-seo"),
            "newest" => __("Newest to oldest", "ai-for-seo")
        )
    );

    define('AI4SEO_BULK_GENERATION_NEW_OR_EXISTING_FILTER_TRANSLATED_OPTIONS', array(
            "both" => __("New entries and existing entries", "ai-for-seo"),
            "new" => __("New entries only", "ai-for-seo"),
            "existing" => __("Existing entries only", "ai-for-seo")
        )
    );
}, 9);

const AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS = 7;
const AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX = "__ttl_time";


// endregion _________________________________________________________________________________ \\
// region ENVIRONMENTAL VARIABLES ============================================================================== \\

const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION = "last_known_plugin_version";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL = "last_cronjob_call";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS = "last_specific_cronjob_call";
const AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST = "cron_job_status_list";
const AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES = "cron_job_status_last_update_times";
const AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME = "tos_toc_and_pp_accepted_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM = "last_tos_details_checksum";
const AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME = "tos_last_modal_open_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED = "enhanced_reporting_accepted";
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME = "enhanced_reporting_accepted_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME = "enhanced_reporting_revoke_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME = "last_website_toc_and_pp_update_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME = "bulk_generation_new_or_existing_filter_reference_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING = "has_purchased_something";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME = "last_seo_autopilot_set_up_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT = "unread_notifications_count";
const AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES = "num_last_known_posts_table_entries";
const AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES = "num_current_posts_table_entries";
const AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT = "current_discount";
const AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME = "plugin_activation_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME = "last_performance_analysis_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS = "payg_status";
const AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME = "just_purchased_something_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME = "payg_low_credits_first_occurrence_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME = "payg_low_credits_last_sync_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID = "posts_table_analysis_last_post_id";
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE = "posts_table_analysis_state";
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME = "posts_table_analysis_start_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME = "posts_table_analysis_last_core_run_time";
const AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE = "supported_post_types_cache";
const AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE = "attachment_id_lookup_cache";
const AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE = "nextgen_picture_pids_cache";
const AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE = "nextgen_imported_images_count_cache";
const AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE = "max_post_id_cache";
const AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER = "claimed_feedback_offer";

const AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES = array(
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION => "0.0.0",
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED => false,
    AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING => false,
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS => 'idle',
    AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE => 'idle',
    AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE => array(),
    AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE => 0,
    AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER => false,
);

$ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;

$ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp = null; # cache variable
$ai4seo_scripts_version_number = isset($_GET["ai4seo_debug_uncached_assets"]) && $_GET["ai4seo_debug_uncached_assets"] ? time() : AI4SEO_PLUGIN_VERSION_NUMBER;
$ai4seo_user_has_at_least_plan = array(); # cache variable to store if user has at least a specific plan
$ai4seo_did_run_post_table_analysis = false;

// used to store various details about all supported metadata fields to use it on many places throughout the plugin
add_action('init', function() {
    define('AI4SEO_METADATA_DETAILS', array(
        "focus-keyphrase" => array(
            "name" => esc_html__("Focus Keyphrase", "ai-for-seo"),
            "icon" => "flag",
            "input" => "textfield",
            "hint" => esc_html__("<strong>Best Practice:</strong> A primary SEO keyword or keyphrase that best represents the main topic of this entry. It should be specific, relevant, and reflect the content accurately to help improve search engine rankings.<br><br>The focus keyphrase is added to the meta title and meta description for best SEO results. Make sure to first generate the keyphrase before generating the meta title and meta description or just generate all at once.", "ai-for-seo"),
            "api-identifier" => "focus_keyphrase",
            "flat-credits-cost" => 2,
        ),
        "meta-title" => array(
            "name" => esc_html__("Meta Title", "ai-for-seo"),
            "icon" => "globe",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A unique and concise title for this entry, which will be displayed on search engine results pages (SERPs) and in the browser tab. This helps users understand your content and enhances visibility.<br><br>The AI aims to generate a meta title with an optimal length of <strong>50 to 60</strong> characters.<br><br>The meta title is added to the <strong>title tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_title",
            "output-tag-type" => "title",
            "output-tag-identifier" => "",
            "meta-tag-regex" => '/<title>(.*?)<\/title>/is',
            "meta-tag-regex-match-index" => 1,
            "flat-credits-cost" => 1,
        ),
        "meta-description" => array(
            "name" => esc_html__("Meta Description", "ai-for-seo"),
            "icon" => "globe",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A compelling and relevant meta description for your page or post, which will appear on search engine results pages (SERPs) beneath the meta title. This description provides a summary of your content, helping to attract clicks and improve visibility.<br><br>The AI aims to generate a meta description with an optimal length of <strong>135 to 150</strong> characters.<br><br>The meta description is added to the <strong>meta description tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_description",
            "output-tag-type" => "meta name",
            "output-tag-identifier" => "description",
            "meta-tag-regex" => '/<meta\s+[^>]*name=(["\'])description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 2,
        ),
        "keywords" => array(
            "name" => esc_html__("Keywords", "ai-for-seo"),
            "icon" => "hashtag",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> Provide a concise, comma-separated list of the most relevant SEO keywords for this entry. Focus on specific phrases that best describe the content and avoid duplicates or keyword stuffing.<br><br>The meta keywords are added to the <strong>meta keywords tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_keywords",
            "output-tag-type" => "meta name",
            "output-tag-identifier" => "keywords",
            "meta-tag-regex" => '/<meta\s+[^>]*name=(["\'])keywords\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 1,
        ),
        "facebook-title" => array(
            "name" => esc_html__("Facebook Title", "ai-for-seo"),
            "icon" => "square-facebook",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> This title will be displayed as the headline in the preview when your content is shared on Facebook, helping to capture attention and increase engagement.<br><br>The AI aims to generate a Facebook title with an optimal length of <strong>50 to 60</strong> characters.<br><br>The Facebook title is added to the <strong>og:title tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_facebook_title",
            "output-tag-type" => "meta property",
            "output-tag-identifier" => "og:title",
            "meta-tag-regex" => '/<meta\s+[^>]*property=(["\'])og:title\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 1,
        ),
        "facebook-description" => array(
            "name" => esc_html__("Facebook Description", "ai-for-seo"),
            "icon" => "square-facebook",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> This description will appear in the preview when your content is shared, providing a summary that encourages users to engage with your content.<br><br>The AI aims to generate a Facebook description with an optimal length of <strong>55 to 65</strong> characters.<br><br>The Facebook description is added to the <strong>og:description tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_facebook_description",
            "output-tag-type" => "meta property",
            "output-tag-identifier" => "og:description",
            "meta-tag-regex" => '/<meta\s+[^>]*property=(["\'])og:description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 1,
        ),
        "twitter-title" => array(
            "name" => esc_html__("Twitter/X Title", "ai-for-seo"),
            "icon" => "square-twitter-x",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> An attention-grabbing title for your page or post, optimized for sharing on Twitter/X. This title will be displayed as the headline in the preview when your content is tweeted, helping to increase visibility and encourage clicks.<br><br>The AI aims to generate a Twitter/X title with an optimal length of <strong>50 to 60</strong> characters.<br><br>The Twitter/X title is added to the <strong>twitter:title tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_twitter_title",
            "output-tag-type" => "meta name",
            "output-tag-identifier" => "twitter:title",
            "meta-tag-regex" => '/<meta\s+[^>]*name=(["\'])twitter:title\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 1,
        ),
        "twitter-description" => array(
            "name" => esc_html__("Twitter/X Description", "ai-for-seo"),
            "icon" => "square-twitter-x",
            "input" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A concise and engaging description for your page or post, optimized for sharing on Twitter/X. This description will appear in the preview when your content is tweeted, providing a brief summary that encourages users to click and interact.<br><br>The AI aims to generate a Twitter/X description with an optimal length of <strong>55 to 65</strong> characters.<br><br>The Twitter/X description is added to the <strong>twitter:description tag</strong> of your website.", "ai-for-seo"),
            "api-identifier" => "meta_twitter_description",
            "output-tag-type" => "meta name",
            "output-tag-identifier" => "twitter:description",
            "meta-tag-regex" => '/<meta\s+[^>]*name=(["\'])twitter:description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
            "meta-tag-regex-match-index" => 3,
            "flat-credits-cost" => 1,
        ),
    ));

    define ('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS', array(
        "title" => array(
            "name" => esc_html__("Title", "ai-for-seo"),
            "icon" => "headline",
            "mime-type-restrictions" => array(),
            "input-type" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A descriptive and unique title for your image that helps users and search engines understand the content of the image. This title is displayed when the image is loaded in the browser and may be used as the default filename if someone downloads the image.<br><br>The AI aims to generate an image title with an optimal length of <strong>20 to 50</strong> characters.<br><br>The image title is not directly visible on your website but is stored in the <strong>image metadata</strong>. A well-crafted title can aid in organizing your media library and improve searchability within WordPress.", "ai-for-seo"),
            "api-identifier" => "image_title",
            "flat-credits-cost" => 1,
        ),
        "alt-text" => array(
            "name" => esc_html__("Alt Text", "ai-for-seo"),
            "icon" => "code",
            "mime-type-restrictions" => array(
                "image/jpeg",
                "image/gif",
                "image/png",
                "image/bmp",
                "image/tiff",
                "image/webp",
                "image/avif",
                "image/x-icon",
                "image/heic",
            ),
            "input-type" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> An informative and clear alt text for your image that describes its content and function. This text is used by screen readers to assist visually impaired users and is displayed in place of the image if it cannot be loaded. It also contributes to SEO by providing context to search engines.<br><br>The AI aims to generate alt text with an optimal length of <strong>145 to 155</strong> characters.<br><br>Alt text is added to the <strong>alt attribute</strong> of the image HTML tag.", "ai-for-seo"),
            "api-identifier" => "image_alt_text",
            "flat-credits-cost" => 2,
        ),
        "caption" => array(
            "name" => esc_html__("Caption", "ai-for-seo"),
            "icon" => "subtitle",
            "mime-type-restrictions" => array(),
            "input-type" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A brief and engaging caption for your image that provides additional context or credit information. Captions are typically displayed below the image on your website and can help enhance user engagement and provide useful information.<br><br>The AI aims to generate a caption with an optimal length of <strong>50 to 125</strong> characters.<br><br>The caption is added to the <strong>caption field</strong> in the WordPress Media Library and is displayed directly on the page where the image appears.", "ai-for-seo"),
            "api-identifier" => "image_caption",
            "flat-credits-cost" => 1,
        ),
        "description" => array(
            "name" => esc_html__("Description", "ai-for-seo"),
            "icon" => "subtitles",
            "mime-type-restrictions" => array(),
            "input-type" => "textarea",
            "hint" => __("<strong>Best Practice:</strong> A detailed and informative description of your image, which helps users understand the image's content and context. This description is particularly useful for internal reference and can aid in organizing and managing your media library.<br><br>The AI aims to generate a description with an optimal length of <strong>155 to 165</strong> characters.<br><br>The description is stored in the <strong>image metadata</strong> and is not directly visible to users on your website. A well-crafted description can aid in organizing your media library and improve searchability within WordPress.", "ai-for-seo"),
            "api-identifier" => "image_description",
            "flat-credits-cost" => 1,
        ),
        #"file-name" => array(
        #    "name" => esc_html__("File Name", "ai-for-seo"),
        #    "mime-type-restrictions" => array(),
        #    "input-type" => "textfield",
        #    "hint" => __("The AI will generate a file name for your image based on its content. A descriptive file name can improve SEO and help search engines understand the image. Review the file name to ensure it accurately reflects the image.", "ai-for-seo"),
        #    "api-identifier" => "image_file_name",
        #    "flat-credits-cost" => 2,
        #),
    ));

    define('AI4SEO_TRANSLATED_LANGUAGE_NAMES', array(
        "en" => __("English", "ai-for-seo"),
        "de" => __("German", "ai-for-seo"),
        "fr" => __("French", "ai-for-seo"),
        "es" => __("Spanish", "ai-for-seo"),
        "it" => __("Italian", "ai-for-seo"),
        "nl" => __("Dutch", "ai-for-seo"),
        "pt" => __("Portuguese", "ai-for-seo"),
        "ru" => __("Russian", "ai-for-seo"),
        "zh" => __("Chinese", "ai-for-seo"),
        "ja" => __("Japanese", "ai-for-seo"),
        "ko" => __("Korean", "ai-for-seo"),
    ));
}, 7);

function ai4seo_get_allowed_html_tags_and_attributes(): array {
    static $ai4seo_allowed_html_tags_and_attributes = array(
        "div" => array(
            "id" => array(),
            "class" => array(),
            "onclick" => array(),
            "style" => array(),
            "title" => array(),
        ),
        "img" => array(
            "class" => array(),
            "src" => array(),
            "alt" => array(),
            "onclick" => array(),
            "style" => array(),
        ),
        'meta' => array(
            'name' => array(),
            'content' => array(),
            'property' => array(),
        ),
        'title' => array(),
        'svg' => array(
            'xmlns'               => true,
            'xmlns:xlink'         => true,
            'version'             => true,
            'width'               => true,
            'height'              => true,
            'viewbox'             => true,
            'preserveaspectratio' => true,
            'role'                => true,
            'aria-hidden'         => true,
            'aria-label'          => true,
            'class'               => true,
            'id'                  => true,
            'focusable'           => true,
        ),
        'defs' => array(),
        'style' => array(
            'type' => array(),
            'media' => true,
        ),
        'desc'   => array(),
        'path' => array(
            'class' => array(),
            'd' => array(),
            'fill-rule' => array(),
            'fill' => array(),
            'clip-rule' => array(),
            'id'                => true,
            'transform'         => true,
            'fill-opacity'      => true,
            'stroke'            => true,
            'stroke-width'      => true,
            'stroke-linecap'    => true,
            'stroke-linejoin'   => true,
            'stroke-miterlimit' => true,
            'stroke-opacity'    => true,
            'opacity'           => true,
        ),
        'g' => array(
            'class'     => true,
            'id'        => true,
            'transform' => true,
        ),
        'circle' => array(
            'cx' => array(),
            'cy' => array(),
            'r' => array(),
            'fill' => array(),
            'class'        => true,
            'id'           => true,
            'stroke'       => true,
            'stroke-width' => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'rect' => array(
            'width' => array(),
            'height' => array(),
            'rx' => array(),
            'ry' => array(),
            'x' => array(),
            'y' => array(),
            'fill' => array(),
            'class'        => true,
            'id'           => true,
            'stroke'       => true,
            'stroke-width' => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'line' => array(
            'x1' => array(),
            'y1' => array(),
            'x2' => array(),
            'y2' => array(),
            'stroke' => array(),
            'stroke-width' => array(),
            'class'        => true,
            'id'           => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'polygon' => array(
            'points' => array(),
            'fill' => array(),
            'class'        => true,
            'id'           => true,
            'stroke'       => true,
            'stroke-width' => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'polyline' => array(
            'class'        => true,
            'id'           => true,
            'points'       => true,
            'fill'         => true,
            'stroke'       => true,
            'stroke-width' => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'ellipse' => array(
            'class'        => true,
            'id'           => true,
            'cx'           => true,
            'cy'           => true,
            'rx'           => true,
            'ry'           => true,
            'fill'         => true,
            'stroke'       => true,
            'stroke-width' => true,
            'opacity'      => true,
            'transform'    => true,
        ),
        'use'    => array(
            'xlink:href' => true,
            'href'       => true,
            'x'          => true,
            'y'          => true,
            'width'      => true,
            'height'     => true,
        ),
        'text' => array(
            'x' => array(),
            'y' => array(),
            'font-size' => array(),
            'font-family' => array(),
            'font-weight' => array(),
            'fill' => array(),
        ),
        "button" => array(
            "type" => array(),
            "onclick" => array(),
            "class" => array(),
            "id" => array(),
            "disabled" => array(),
            "style" => array(),
            "title" => array(),
            "data-clipboard-text" => array(),
            "data-time-left" => array(),
            "data-ai4seo-generation-action" => array(),
            "data-ai4seo-generation-fields" => array(),
            "aria-controls" => array(),
            "aria-expanded" => array(),
        ),
        "span" => array(
            "id" => array(),
            "class" => array(),
            "style" => array(),
            "data-trigger" => array(),
            "data-time-left" => array(),
            "onclick" => array(),
        ),
        "h1" => array(
            "class" => array(),
            "style" => array(),
        ),
        "h2" => array(
            "class" => array(),
            "style" => array(),
        ),
        "p" => array(
            "class" => array(),
            "style" => array(),
        ),
        "b" => array(),
        "u" => array(),
        "a" => array(
            "href" => array(),
            "target" => array(),
            "rel" => array(),
            "title" => array(),
            "class" => array(),
            "onclick" => array(),
            "data-time-left" => array(),
        ),
        "i" => array(
            "onclick" => array(),
            "class" => array(),
            "id" => array(),
            "style" => array(),
        ),
        "select" => array(
            "id" => array(),
            "name" => array(),
            "class" => array(),
            "style" => array(),
            "onchange" => array(),
        ),
        "option" => array(
            "value" => array(),
            "selected" => array(),
        ),
        "br" => array(),
        "strong" => array(
            "class" => array(),
        ),
        "input" => array(
            "type" => array(),
            "id" => array(),
            "class" => array(),
            "style" => array(),
            "value" => array(),
            "name" => array(),
            "placeholder" => array(),
            "onchange" => array(),
            "onclick" => array(),
            "disabled" => array(),
            "data-target" => array(),
        ),
        "textarea" => array(
            "id" => array(),
            "name" => array(),
            "class" => array(),
            "style" => array(),
            "onchange" => array(),
            "onclick" => array(),
            "disabled" => array(),
        ),
        "label" => array(
            "for" => array(),
            "class" => array(),
            "style" => array(),
        ),
        "center" => array(),
        "ol" => array(
            "class" => array(),
            "style" => array(),
        ),
        "ul" => array(
            "class" => array(),
            "style" => array(),
        ),
        "li" => array(
            "class" => array(),
            "style" => array(),
        ),
        "em" => array(),
        "form" => array(
            "id" => array(),
            "class" => array(),
            "style" => array(),
            "method" => array(),
            "action" => array(),
        ),
        "pre" => array(
            "class" => array(),
            "style" => array(),
        ),
        "code" => array(
            "class" => array(),
            "style" => array(),
        ),
        "small" => array(
            "class" => array(),
            "style" => array(),
        ),
    );

    return $ai4seo_allowed_html_tags_and_attributes;
}


$ai4seo_cached_active_plugins_and_themes = array();
$ai4seo_cached_supported_post_types = array();
$ai4seo_checked_supported_post_types = array();
$ai4seo_allowed_attachment_mime_types = array("image/jpeg", "image/png", "image/gif", "image/webp", "image/avif"); # IMPORTANT! Also apply changes to the api-service AND to ai4seo_supported_mime_types-variable in JS-file
$ai4seo_allowed_image_mime_types = array("image/jpeg", "image/png", "image/gif", "image/webp", "image/avif");
$ai4seo_allowed_image_file_type_names = array("jpg", "jpeg", "png", "gif", "webp", "avif");

// Define the constants for full and base language code mappings
const AI4SEO_FULL_LANGUAGE_CODE_MAPPING = array(
    'zh_cn' => 'simplified chinese',
    'zh_tw' => 'traditional chinese',
    'pt_br' => 'brazilian portuguese',
    'pt_pt' => 'european portuguese',
    'fr_ca' => 'canadian french',
    'en_us' => 'american english',
    'en_gb' => 'british english',
);

const AI4SEO_BASE_LANGUAGE_CODE_MAPPING = array(
    'sq' => 'albanian',
    'ar' => 'arabic',
    'bg' => 'bulgarian',
    'zh' => 'chinese',  // General Chinese fallback
    'hr' => 'croatian',
    'cs' => 'czech',
    'da' => 'danish',
    'nl' => 'dutch',
    'en' => 'english',  // General English fallback
    'et' => 'estonian',
    'fi' => 'finnish',
    'fr' => 'french',   // General French fallback
    'de' => 'german',
    'el' => 'greek',
    'he' => 'hebrew',
    'hi' => 'hindi',
    'hu' => 'hungarian',
    'is' => 'icelandic',
    'id' => 'indonesian',
    'it' => 'italian',
    'ja' => 'japanese',
    'ko' => 'korean',
    'lv' => 'latvian',
    'lt' => 'lithuanian',
    'mk' => 'macedonian',
    'mt' => 'maltese',
    'no' => 'norwegian',
    'pl' => 'polish',
    'pt' => 'portuguese',  // General Portuguese fallback
    'ro' => 'romanian',
    'ru' => 'russian',
    'sr' => 'serbian',
    'sk' => 'slovak',
    'sl' => 'slovenian',
    'es' => 'spanish',  // General Spanish fallback
    'sv' => 'swedish',
    'th' => 'thai',
    'tr' => 'turkish',
    'uk' => 'ukrainian',
    'vi' => 'vietnamese',
);

// allowed ajax function (also change in javascript file)
const AI4SEO_ALLOWED_AJAX_FUNCTIONS = array(
    "ai4seo_save_anything",
    "ai4seo_show_metadata_editor",
    "ai4seo_show_attachment_attributes_editor",
    "ai4seo_generate_metadata",
    "ai4seo_generate_attachment_attributes",
    "ai4seo_reject_tos",
    "ai4seo_accept_tos",
    "ai4seo_show_terms_of_service",
    "ai4seo_dismiss_notification",
    "ai4seo_get_dashboard_html",
    "ai4seo_reset_plugin_data",
    "ai4seo_clear_debug_message_log",
    "ai4seo_stop_bulk_generation",
    "ai4seo_retry_all_failed_attachment_attributes",
    "ai4seo_retry_all_failed_metadata",
    "ai4seo_disable_payg",
    "ai4seo_init_purchase",
    "ai4seo_track_subscription_pricing_visit",
    "ai4seo_import_nextgen_gallery_images",
    "ai4seo_export_settings",
    "ai4seo_show_import_settings_preview",
    "ai4seo_import_settings",
    "ai4seo_restore_default_settings",
    "ai4seo_request_lost_licence_data",
    "ai4seo_refresh_dashboard_statistics",
    "ai4seo_refresh_robhub_account",
    "ai4seo_submit_feedback",
);

// the robhub api communicator is used to communicate with the robhub api which handles all the AI operations
$ai4seo_robhub_api = null;


// endregion _________________________________________________________________________________ \\
// endregion _________________________________________________________________________________ \\
// region INITIALIZATION ======================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// init settings
add_action("init", "ai4seo_init_settings", 8);

// CRON CALL ONLY
if (wp_doing_cron()) {
    // init cron jobs
    add_action("init", "ai4seo_init_cron_jobs", 10);

    // exit here
    return;
}

// FOR FRONTEND, LOGGED-OUT USERS. ALSO FOR: LOGGED-IN USERS, ADMIN AREA
// init plugin injections for all users for the frontend
add_action("init", "ai4seo_enqueue_frontend_scripts");
add_action("init", "ai4seo_init_frontend_injections");

// FOR LOGGED-IN USERS. ALSO FOR: ADMIN AREA
// init (logged-in) user essentials after all plugins have been loaded, used for admin area and frontend
add_action("init", "ai4seo_init_user_essentials");

// perform ajax nonce check
add_action( 'admin_init', 'ai4seo_on_ajax_action', 9999 );

// not admin area -> exit here
if (!ai4seo_is_function_usable("is_admin") || !is_admin()) {
    return;
}

// init admin essentials for the backend after all plugins have been loaded
add_action("init", "ai4seo_init_admin_area_essentials", 12);

// on plugin deactivation
register_deactivation_hook(__FILE__, "ai4seo_on_deactivation");

if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
    return;
}

// init cron jobs
add_action("init", "ai4seo_init_cron_jobs");

// do some checks after all plugins have been loaded
add_action('init', 'ai4seo_check_and_handle_plugin_update');

// check for unfinished post table analysis
add_action('init', 'ai4seo_try_start_posts_table_analysis', 9);

// check for new notifications
add_action('init', 'ai4seo_check_for_new_notifications', 13);

// init admin essentials for the backend after all plugins have been loaded
add_action("init", "ai4seo_send_additional_tos_accept_details");

// on saving a post, check if the all ceo meta tags are filled
add_action("save_post", "ai4seo_mark_post_to_be_analyzed", 20, 3);

// add unified cache invalidation hooks for environmental-variable based caches
ai4seo_add_invalidate_caches_hooks();

// analyze the post after it has been saved, call ai4seo_handle_posts_to_be_analyzed() at the end of the request
add_action("shutdown", "ai4seo_handle_posts_to_be_analyzed");

// on plugin activation
register_activation_hook(__FILE__, "ai4seo_on_activation");


// === INIT FUNCTIONS ======================================================================== \\

/**
 * Function to init plugin injections for all users for the frontend
 * @return void
 */
function ai4seo_init_frontend_injections() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // make sure we are on the frontend and not in the admin area, also exclude feeds, REST requests and AJAX requests
    if (
        is_admin()
        || is_feed()
        || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
        || ( defined( 'DOING_AJAX' ) && DOING_AJAX )
    ) {
        return;
    }

    // workaround for Squirrly SEO, as they use their own buffer
    $is_squirrly_seo_active = ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO);

    if ($is_squirrly_seo_active) {
        // Squirrly SEO workaround, as they use their own buffer
        add_action("sq_buffer", "ai4seo_inject_our_meta_tags_into_the_html_head", 20);
        add_action("sq_buffer", "ai4seo_inject_image_attributes_into_html", 20);
        return;
    }

    // inject meta tags and image attributes into html
    add_action( 'template_redirect', function(){
        ob_start( function( $html ) {
            $html = ai4seo_inject_our_meta_tags_into_the_html_head( $html );
            return ai4seo_inject_image_attributes_into_html( $html );
        } );
    }, PHP_INT_MAX );

    add_action( 'shutdown', function(){
        if ( ob_get_length() ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo ob_get_clean();
        }
    } );
    /*
    // add alt text injection to images (for logged-in users and guests)
    add_filter("the_content", "ai4seo_inject_image_attributes", 99999);

    // also filter block output for gutenberg blocks to inject alt text
    add_filter("render_block", "ai4seo_inject_image_attributes_for_gutenberg", 99999, 2);

    // Also cover images output via wp_get_attachment_image() and similar.
    add_filter('wp_get_attachment_image_attributes', 'ai4seo_filter_wp_image_attrs', 10, 3);

    add_filter( 'post_thumbnail_html', 'ai4seo_inject_image_attributes', 99999, 5 );

    add_filter( 'wp_get_attachment_image_attributes', 'ai4seo_inject_image_attributes', 99999, 3 );

    add_filter( 'get_image_tag', 'ai4seo_inject_image_attributes', 99999, 6 );

    add_filter( 'get_avatar', 'ai4seo_inject_image_attributes', 99999, 6 );

    add_filter( 'widget_text', 'ai4seo_inject_image_attributes', 99999 );

    add_filter( 'the_excerpt', 'ai4seo_inject_image_attributes', 99999 );
    add_filter( 'get_the_excerpt', 'ai4seo_inject_image_attributes', 99999 );
    */
}

// =========================================================================================== \\

/**
 * Function to init plugin essentials for admins in the front and backend
 * @return void
 */
function ai4seo_init_user_essentials() {
    // Make sure that the user is allowed to use this plugin
    if (!ai4seo_can_manage_this_plugin()) {
        return;
    }

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'ai4seo_enqueue_admin_scripts');
    add_action('admin_enqueue_scripts', 'ai4seo_enqueue_admin_scripts');

    // init ajax functions
    foreach (AI4SEO_ALLOWED_AJAX_FUNCTIONS AS $this_ajax_function) {
        add_action("wp_ajax_{$this_ajax_function}", $this_ajax_function);
    }

    // add modal schemas to the footer
    add_action("wp_footer", "ai4seo_include_modal_schemas_file");
    add_action("get_footer", "ai4seo_include_modal_schemas_file");
    add_action("admin_footer", "ai4seo_include_modal_schemas_file");

    // register compatibility with other plugins
    if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY)) {
        register_post_type( AI4SEO_NEXTGEN_GALLERY_POST_TYPE, array(
            'label'                 => AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
            'public'                => false,
            'show_ui'               => false,
            'show_in_menu'          => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'query_var'             => false,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'supports'              => array( 'title', 'editor' ),
            'can_export'            => false,
            'show_in_rest'          => false,
        ) );
    }

    // user needs to accept tos? stop here, to prevent further plugin actions
    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        return;
    }

    // admin bar menu item
    add_action("admin_bar_menu", "ai4seo_add_admin_menu_item", 999);
}

// =========================================================================================== \\

/**
 * Function to init plugin essentials for admins in the backend
 * @return void
 */
function ai4seo_init_admin_area_essentials() {
    // ADMIN AREA ONLY (REST OF INIT CODE FROM HERE) ->
    // make sure the robhub api communicator is initialized before anything else
    try {
        if (!ai4seo_robhub_api(true) || !ai4seo_robhub_api()->is_initialized) {
            // if the robhub api communicator could not be initialized, we cannot continue
            // show notice
            if (ai4seo_can_manage_this_plugin()) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>';
                        sprintf(
                            /* translators: %s: plugin name */
                            esc_html__("The %s plugin could not be initialized. Class 'Ai4Seo_RobHubApiCommunicator' could not be initialized. Please check your server configuration and try again.", "ai-for-seo"),
                            esc_html(AI4SEO_PLUGIN_NAME)
                        );
                    echo '</p></div>';
                });
            }

            // exit here
            return;
        }
    } catch (Throwable $e) {
        // could not initialize the robhub api communicator -> abort here, echoing a notice
        if (ai4seo_can_manage_this_plugin()) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                    sprintf(
                        /* translators: %s: plugin name */
                        esc_html__("The %s plugin could not be initialized. Class 'Ai4Seo_RobHubApiCommunicator' could not be initialized. Please check your server configuration and try again.", "ai-for-seo"),
                        esc_html(AI4SEO_PLUGIN_NAME)
                    );
                echo '</p></div>';
            });
        }

        return;
    }

    // Overwrite the plugin-details for white-label-settings
    add_filter("all_plugins", "ai4seo_modify_plugin_details_for_white_label", 10, 1);

    // Make sure that the user is allowed to use this plugin
    if (!ai4seo_can_manage_this_plugin()) {
        return;
    }

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $is_user_inside_our_plugin_admin_pages = ai4seo_is_user_inside_our_plugin_admin_pages();

    // Add menu-item to main menu, sub menu-items and page titles
    add_filter('admin_title', 'ai4seo_filter_admin_title', 10, 2 );
    add_action('admin_menu', "ai4seo_add_menu_entries");
    add_filter('parent_file', 'ai4seo_mark_parent_menu_active');
    add_filter('submenu_file', 'ai4seo_mark_submenu_active');

    // plugin action link use filter "plugin_action_links_ + plugin_basename"
    $this_plugin_basename = sanitize_text_field(ai4seo_get_plugin_basename());
    add_filter("plugin_action_links_{$this_plugin_basename}", 'ai4seo_add_links_to_the_plugin_directory', 999);

    // show terms of service if not accepted yet
    $last_tos_modal_open_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME);

    // outside the plugin admin pages, show the modal only once a week
    if ($is_user_inside_our_plugin_admin_pages || $last_tos_modal_open_time < time() - WEEK_IN_SECONDS) {
        if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
            add_action("wp_footer", "ai4seo_show_terms_of_service_modal");
            add_action("get_footer", "ai4seo_show_terms_of_service_modal");
            add_action("admin_footer", "ai4seo_show_terms_of_service_modal");
        }
    }

    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        // stop here, to prevent further plugin actions
        return;
    }

    // put our code into the post and page table
    add_filter("manage_post_posts_columns", "ai4seo_add_metadata_editor_column_to_posts_table");
    add_filter("manage_page_posts_columns", "ai4seo_add_metadata_editor_column_to_posts_table");
    #add_filter("manage_edit-product_columns", "ai4seo_add_metadata_editor_column_to_posts_table");
    add_action("manage_post_posts_custom_column", "ai4seo_add_metadata_editor_button_to_posts_table", 10, 2);
    add_action("manage_page_posts_custom_column", "ai4seo_add_metadata_editor_button_to_posts_table", 10, 2);
    #add_action("manage_product_posts_custom_column", "ai4seo_add_metadata_editor_button_to_posts_table", 10, 2);

    // add ajax nonce field to the footer
    add_action( 'admin_print_footer_scripts', 'ai4seo_print_ajax_nonce_field' );

    // if user is inside our plugin admin pages, check for account sync
    if ($is_user_inside_our_plugin_admin_pages) {
        ai4seo_check_for_robhub_account_sync();
    }
}

// =========================================================================================== \\

/**
 * Function to init the plugin-settings
 * @return void
 */
function ai4seo_init_settings() {
    global $ai4seo_settings;
    global $ai4seo_are_settings_initialized;

    if (ai4seo_prevent_loops(__FUNCTION__, 1, 10)) {
        ai4seo_debug_message(223772145, 'Prevented loop', true);
        return;
    }

    // Read settings from database
    $from_database_settings = ai4seo_read_settings();

    // Loop through settings and add the new values to $ai4seo_settings
    foreach ($from_database_settings as $setting_name => $setting_value) {
        // Make sure that this setting is valid
        if (!ai4seo_validate_setting_value($setting_name, $setting_value)) {
            continue;
        }

        // Save the new values to $ai4seo_settings
        $ai4seo_settings[$setting_name] = $setting_value;
    }

    $ai4seo_are_settings_initialized = true;
}

// =========================================================================================== \\

function ai4seo_read_settings() : array {
    // prevent infinite loops (1 depth, max 10 calls)
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 10)) {
        ai4seo_debug_message(134874085, 'Prevented loop', true);
        return array();
    }

    // Read settings from database
    $settings = ai4seo_get_option(AI4SEO_SETTINGS_OPTION_NAME);

    // Make sure that settings could be read from database
    if (!$settings) {
        $settings = array();
    }

    $settings = maybe_unserialize($settings);

    // Make sure that $settings is array
    if (!is_array($settings)) {
        if (is_string($settings) && ai4seo_is_json($settings)) {
            $settings = json_decode($settings, true);
        }
    }

    if (!is_array($settings)) {
        $settings = array();
    }

    $settings = ai4seo_deep_sanitize($settings);

    return $settings;
}

// =========================================================================================== \\

/**
 * Things to do on plugin activation
 * @return void
 */
function ai4seo_on_activation() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // set AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME
    if (!ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME)) {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME, time());
    }

    // init cron jobs
    ai4seo_init_cron_jobs();
}

// =========================================================================================== \\

/**
 * Things to do on plugin deactivation
 * @return void
 */
function ai4seo_on_deactivation() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // un schedule all cron jobs
    ai4seo_un_schedule_cron_jobs();

    ai4seo_robhub_api()->perform_product_deactivated_call();

    // Check for function get_current_user_id()
    if (!function_exists("get_current_user_id")) {
        return;
    }

    // Define variables for the incognito-setting
    $ai4seo_setting_enable_incognito_mode = ai4seo_is_incognito_mode_enabled();
    $ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID);
    $current_user_id = get_current_user_id();

    // Delete plugin if it was deactivated by non-incognito mode user
    if ($ai4seo_setting_enable_incognito_mode && $ai4seo_setting_incognito_mode_user_id != AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_INCOGNITO_MODE_USER_ID] && $ai4seo_setting_incognito_mode_user_id != $current_user_id) {
        // Make sure we can call delete_plugins()
        if (!function_exists("delete_plugins")) {
            include_once(ABSPATH . "wp-admin/includes/plugin.php");
        }

        // Attempt to delete this plugin's files
        if (function_exists("delete_plugins")) {
            delete_plugins([plugin_basename(__FILE__)]);
        }
    }
}

// =========================================================================================== \\

function ai4seo_is_incognito_mode_enabled(): bool {
    // prevent infinite loops (0 depth, max 10 calls)
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(176167926, 'Prevented loop', true);
        return false;
    }

    if (isset($_REQUEST["ai4seo_debug_bypass_incognito_mode"]) && $_REQUEST["ai4seo_debug_bypass_incognito_mode"]) {
        // If the debug bypass parameter is set, we can bypass the incognito mode
        return false;
    }

    // Check if the incognito mode is enabled
    $ai4seo_setting_enable_incognito_mode = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_INCOGNITO_MODE);

    // If the incognito mode is enabled, return true
    if ($ai4seo_setting_enable_incognito_mode) {
        return true;
    }

    // Otherwise, return false
    return false;
}

// =========================================================================================== \\

/**
 * Function to check if we have updated recently and do some actions accordingly
 * @return void
 */
function ai4seo_check_and_handle_plugin_update() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $last_known_plugin_version = strval(ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION));

    // same plugin version as last known version? -> skip
    if ($last_known_plugin_version == AI4SEO_PLUGIN_VERSION_NUMBER) {
        return;
    }

    // save new version to database
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION, AI4SEO_PLUGIN_VERSION_NUMBER);

    // workaround for version 0.0.0 -> remove $last_known_plugin_version
    if ($last_known_plugin_version == AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION]) {
        $last_known_plugin_version = "";
    }

    // tidy up some old version parameters, tables and options
    ai4seo_tidy_up($last_known_plugin_version);

    // call "product-updated" endpoint to RobHub if we are not on a fresh install
    if ($last_known_plugin_version) {
        // call robhub api endpoint "client/product-updated" with the old and new plugin version
        $robhub_api_parameters = array(
            "old_version" => $last_known_plugin_version,
            "new_version" => AI4SEO_PLUGIN_VERSION_NUMBER,
        );

        ai4seo_robhub_api()->call("client/product-updated", $robhub_api_parameters);

        // maybe push a new plugin update notification
        ai4seo_check_for_plugin_update_notification($last_known_plugin_version, true);
    }
}

// =========================================================================================== \\

/**
 * Function to clean up old version's options, variables etc. "Cleanup". "Clean_up", "Tidy-up"
 * @param $last_known_plugin_version string The last known plugin version, used to determine which cleanup actions to perform
 * @return void
 */
function ai4seo_tidy_up(string $last_known_plugin_version = AI4SEO_PLUGIN_VERSION_NUMBER) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // reestablish cron jobs
    ai4seo_un_schedule_cron_jobs();
    ai4seo_init_cron_jobs();

    // start cron jobs in 10 seconds
    ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, 10);
    ai4seo_inject_additional_cronjob_call(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, 10);

    // unset temporary environmental variables
    ai4seo_robhub_api()->reset_last_account_sync();
    ai4seo_robhub_api()->tidy_up_api_locks();
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL, time() - 300);
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS, array());

    // we need the raw settings to check for old variations of the settings
    $raw_settings = ai4seo_read_settings();

    // we need the raw environmental variables to check for old variations
    $raw_environmental_variables = ai4seo_get_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME);

    if($raw_environmental_variables) {
        $raw_environmental_variables = maybe_unserialize($raw_environmental_variables);
        $raw_environmental_variables = ai4seo_deep_sanitize($raw_environmental_variables);
    }


    // region V1.1.X ============================================================================== \\

    // remove old options (from older versions)
    // required after V1.1.1
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.1.1', '<')) {
        ai4seo_delete_option("_ai4seo_current_credits_balance");
    }

    // if old option ai4seo_missing_seo_data_post_ids is set, rename it to ai4seo_processing_metadata_post_ids
    // required after V1.1.2
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.1.2', '<')) {
        if (ai4seo_get_option("ai4seo_missing_seo_data_post_ids")) {
            $missing_seo_data_post_ids = ai4seo_get_option("ai4seo_missing_seo_data_post_ids");
            ai4seo_update_option("ai4seo_processing_metadata_post_ids", $missing_seo_data_post_ids);
            ai4seo_delete_option("ai4seo_missing_seo_data_post_ids");
        }

        // if old option _ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type is set, rename it to _ai4seo_num_processing_metadata_post_ids_by_post_type
        // required after V1.1.2
        if (ai4seo_get_option("_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type")) {
            $num_existing_going_to_fill_this_post_ids_by_post_type = ai4seo_get_option("_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type");
            ai4seo_update_option("_ai4seo_num_processing_metadata_post_ids_by_post_type", $num_existing_going_to_fill_this_post_ids_by_post_type);
            ai4seo_delete_option("_ai4seo_num_existing_going_to_fill_this_post_ids_by_post_type");
        }

        // clear schedule of old cronjobs, as of V1.1.2 we use new cronjobs
        wp_clear_scheduled_hook("ai4seo_search_missing_seo_data_posts");
        wp_clear_scheduled_hook("ai4seo_search_missing_metadata_posts");
        wp_clear_scheduled_hook("ai4seo_automated_seo_data_generation");
    }

    // V1.1.8: clear schedule of old cronjob "ai4seo_automated_metadata_generation", it's now called "ai4seo_automated_generation_cron_job"
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.1.8', '<')) {
        wp_clear_scheduled_hook("ai4seo_automated_metadata_generation");

        ai4seo_delete_option("ai4seo_is_automation_activated_for_posts");
        ai4seo_delete_option("ai4seo_is_automation_activated_for_pages");
        ai4seo_delete_option("ai4seo_is_automation_activated_for_products");
    }


    // region V1.2.X ============================================================================== \\

    // if old option ai4seo_already_filled_post_ids is set, rename it to ai4seo_already_filled_metadata_post_ids
    // required after V1.2
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.2', '<')) {
        if (ai4seo_get_option("ai4seo_already_filled_post_ids")) {
            $already_filled_metadata_post_ids = ai4seo_get_option("ai4seo_already_filled_post_ids");
            ai4seo_update_option("ai4seo_already_filled_metadata_post_ids", $already_filled_metadata_post_ids);
            ai4seo_delete_option("ai4seo_already_filled_post_ids");
        }

        // if old option ai4seo_failed_to_fill_post_ids is set, rename it to ai4seo_failed_to_fill_metadata_post_ids
        // required after V1.2
        if (ai4seo_get_option("ai4seo_failed_to_fill_post_ids")) {
            $failed_to_fill_metadata_post_ids = ai4seo_get_option("ai4seo_failed_to_fill_post_ids");
            ai4seo_update_option("ai4seo_failed_to_fill_metadata_post_ids", $failed_to_fill_metadata_post_ids);
            ai4seo_delete_option("ai4seo_failed_to_fill_post_ids");
        }

        // V1.2: check for table "wp_ai4seo_cache" (id, post_id, data), if available, save all it's "data" to the post_meta of the corresponding post_id, using ai4seo_save_generated_data()
        ai4seo_tidy_up_old_ai4seo_cache_table();
    }

    // V1.2.1: Delete old summary options
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.2.1', '<')) {
        if (ai4seo_get_option("_ai4seo_num_processing_metadata_post_ids_by_post_type")) {
            ai4seo_delete_option("_ai4seo_num_processing_metadata_post_ids_by_post_type");
        }

        if (ai4seo_get_option("_ai4seo_num_failed_to_fill_post_ids_by_post_type")) {
            ai4seo_delete_option("_ai4seo_num_failed_to_fill_post_ids_by_post_type");
        }

        if (ai4seo_get_option("_ai4seo_num_already_filled_post_ids_by_post_type")) {
            ai4seo_delete_option("_ai4seo_num_already_filled_post_ids_by_post_type");
        }

        if (ai4seo_get_option("_ai4seo_num_posts_not_filled_by_post_type")) {
            ai4seo_delete_option("_ai4seo_num_posts_not_filled_by_post_type");
        }

        if (ai4seo_get_option("ai4seo_already_filled_metadata_post_ids")) {
            ai4seo_delete_option("ai4seo_already_filled_metadata_post_ids");
        }

        if (ai4seo_get_option("ai4seo_already_filled_attributes_attachment_post_ids")) {
            ai4seo_delete_option("ai4seo_already_filled_attributes_attachment_post_ids");
        }

        // V1.2.1: Rename some post ids options
        // (ai4seo_failed_to_fill_metadata_post_ids -> ai4seo_failed_metadata_post_ids)
        // (ai4seo_failed_to_fill_attributes_attachment_post_ids -> ai4seo_failed_attributes_attachment_post_ids)
        if (ai4seo_get_option("ai4seo_failed_to_fill_metadata_post_ids")) {
            $failed_metadata_post_ids = ai4seo_get_option("ai4seo_failed_to_fill_metadata_post_ids");
            ai4seo_update_option("ai4seo_failed_metadata_post_ids", $failed_metadata_post_ids);
            ai4seo_delete_option("ai4seo_failed_to_fill_metadata_post_ids");
        }

        if (ai4seo_get_option("ai4seo_failed_to_fill_attributes_attachment_post_ids")) {
            $failed_attributes_attachment_post_ids = ai4seo_get_option("ai4seo_failed_to_fill_attributes_attachment_post_ids");
            ai4seo_update_option("ai4seo_failed_attributes_attachment_post_ids", $failed_attributes_attachment_post_ids);
            ai4seo_delete_option("ai4seo_failed_to_fill_attributes_attachment_post_ids");
        }
    }

    // V1.2.6: Save various options into the new environmental variables option
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '1.2.6', '<')) {
        if (ai4seo_get_option("_ai4seo_robhub_last_credit_balance_check") !== false) {
            ai4seo_delete_option("_ai4seo_robhub_last_credit_balance_check");
        }

        if (ai4seo_get_option("ai4seo_robhub_auth_data") !== false) {
            $old_robhub_auth_data = ai4seo_get_option("ai4seo_robhub_auth_data");
            $old_api_username = sanitize_text_field($old_robhub_auth_data[0] ?? "");
            $old_api_password = sanitize_text_field($old_robhub_auth_data[1] ?? "");
            ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME, $old_api_username);
            ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $old_api_password);
            ai4seo_delete_option("ai4seo_robhub_auth_data");
        }

        if (ai4seo_get_option("_ai4seo_robhub_credits_balance") !== false) {
            ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE, (int) ai4seo_get_option("_ai4seo_robhub_credits_balance"));
            ai4seo_delete_option("_ai4seo_robhub_credits_balance");
        }

        if (ai4seo_get_option("_ai4seo_version") !== false) {
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION, ai4seo_get_option("_ai4seo_version"));
            ai4seo_delete_option("_ai4seo_version");
        }

        if (ai4seo_get_option("_ai4seo_licence_key_shown") !== false) {
            #ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LICENSE_KEY_SHOWN, (bool) ai4seo_get_option("_ai4seo_licence_key_shown"));
            ai4seo_delete_option("_ai4seo_licence_key_shown");
        }

        if (ai4seo_get_option("_ai4seo_last_cronjob_call") !== false) {
            # we defined the new variable earlier in this function, therefore we can safely delete the old one
            ai4seo_delete_option("_ai4seo_last_cronjob_call");
        }

        if (ai4seo_get_option("_ai4seo_last_cronjob_call_for_ai4seo_automated_generation_cron_job") !== false) {
            # we defined the new variable earlier in this function, therefore we can safely delete the old one
            ai4seo_delete_option("_ai4seo_last_cronjob_call_for_ai4seo_automated_generation_cron_job");
        }

        if (ai4seo_get_option("_ai4seo_last_cronjob_call_for_ai4seo_automated_metadata_generation") !== false) {
            # we defined the new variable earlier in this function, therefore we can safely delete the old one
            ai4seo_delete_option("_ai4seo_last_cronjob_call_for_ai4seo_automated_metadata_generation");
        }

        if (ai4seo_get_option("_ai4seo_performance_notice_dismissed_timestamp") !== false) {
            # we defined the new variable earlier in this function, therefore we can safely delete the old one
            ai4seo_delete_option("_ai4seo_performance_notice_dismissed_timestamp");
        }
    }


    // region V2.0.X ============================================================================== \\

    // V2.0.0:
    // robhub auth data changed from environmental variable "auth_data" to "api_username" and "api_password"
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '2.0.0', '<')) {
        ai4seo_robhub_api()->tidy_up_deprecated_auth_data();

        // V2.0.0: Settings migration
        // enabled_automated_generations -> enabled_bulk_generation_post_types
        if (isset($raw_settings['enabled_automated_generations']) && is_array($raw_settings['enabled_automated_generations'])) {
            $new_enabled_bulk_generation_post_types = array();

            foreach ($raw_settings['enabled_automated_generations'] AS $this_post_type => $this_is_enabled) {
                if ($this_is_enabled) {
                    $new_enabled_bulk_generation_post_types[] = $this_post_type;
                }
            }

            if ($new_enabled_bulk_generation_post_types) {
                ai4seo_update_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, $new_enabled_bulk_generation_post_types);
            }
        }

        // automated_generation_order (array) -> bulk_generation_order (string)
        if (isset($raw_settings['automated_generation_order']) && is_array($raw_settings['automated_generation_order'])) {
            // get first element of the array, set it as the new value
            if (count($raw_settings['automated_generation_order']) >= 1) {
                $new_bulk_generation_order = reset($raw_settings['automated_generation_order']);

                if ($new_bulk_generation_order) {
                    ai4seo_update_setting(AI4SEO_SETTING_BULK_GENERATION_ORDER, $new_bulk_generation_order);
                }
            }
        }

        // automated_generation_new_or_existing_filter (array) -> bulk_generation_new_or_existing_filter (string)
        if (isset($raw_settings['automated_generation_new_or_existing_filter']) && is_array($raw_settings['automated_generation_new_or_existing_filter'])) {
            // get first element of the array, set it as the new value
            if (count($raw_settings['automated_generation_new_or_existing_filter']) >= 1) {
                $new_bulk_generation_new_or_existing_filter = reset($raw_settings['automated_generation_new_or_existing_filter']);

                if ($new_bulk_generation_new_or_existing_filter) {
                    ai4seo_update_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER, $new_bulk_generation_new_or_existing_filter);
                }
            }
        }

        // automated_generation_new_or_existing_filter_reference_times (array) -> bulk_generation_new_or_existing_filter_reference_time (string)
        // environmental variable
        if (isset($raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times']) && is_array($raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times'])) {
            // get first element of the array, set it as the new value
            if (count($raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times']) >= 1) {
                $new_bulk_generation_new_or_existing_filter_reference_time = reset($raw_environmental_variables['automated_generation_new_or_existing_filter_reference_times']);

                if ($new_bulk_generation_new_or_existing_filter_reference_time) {
                    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME, $new_bulk_generation_new_or_existing_filter_reference_time);
                }
            }
        }
    }


    // region 2.1.X ============================================================================== \\

    // V2.1.0:
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '2.1.0', '<')) {
        // Remove old environmental variable "performance_notice_dismissed_time" and dismissed_one_time_notices, feature was removed in V2.1.0
        ai4seo_delete_environmental_variable("performance_notice_dismissed_time");
        ai4seo_delete_environmental_variable("dismissed_one_time_notices");
        ai4seo_delete_environmental_variable("is_first_purchase_discount_available");
        ai4seo_delete_environmental_variable("early_bird_discount_time_left");

        // delete option "_ai4seo_plugin_activation_time" -> deprecated in V2.1.0
        if (ai4seo_get_option("_ai4seo_plugin_activation_time") !== false) {
            ai4seo_delete_option("_ai4seo_plugin_activation_time");
        }
    }

    // V2.1.1:
    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '2.1.1', '<')) {
        // delete old robhub environmental variable last_credit_balance_check
        ai4seo_robhub_api()->delete_environmental_variable("last_credit_balance_check");
    }


    // region 2.2.X ============================================================================== \\

    if ($last_known_plugin_version && version_compare($last_known_plugin_version, '2.2.0', '<')) {
        // handle default visible meta tags setting change to new active meta tags setting
        if (!isset($raw_settings[AI4SEO_SETTING_VISIBLE_META_TAGS]) || !$raw_settings[AI4SEO_SETTING_VISIBLE_META_TAGS]) {
            // old default value for visible meta tags
            ai4seo_update_setting(AI4SEO_SETTING_ACTIVE_META_TAGS, array("meta-title", "meta-description", "facebook-title", "facebook-description"));
        }

        // if visible meta tags were set, apply the same to active meta tags
        if (isset($raw_settings[AI4SEO_SETTING_VISIBLE_META_TAGS]) && $raw_settings[AI4SEO_SETTING_VISIBLE_META_TAGS]) {
            ai4seo_update_setting(AI4SEO_SETTING_ACTIVE_META_TAGS, $raw_settings[AI4SEO_SETTING_VISIBLE_META_TAGS]);
        }

        // set AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE to 'facebook-title' and set AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION to 'facebook-description'
        ai4seo_update_setting(AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE, 'facebook-title');
        ai4seo_update_setting(AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION, 'facebook-description');
    }

    // to finish the tidy up, we re-analyze the plugin performance and by adding notifications
    ai4seo_analyze_plugin_performance();

    // force push various notifications, if applicable
    ai4seo_check_for_missing_entries_notification(true);
    ai4seo_check_for_low_credits_balance_notification(true);

    // refresh unread notifications count
    ai4seo_refresh_unread_notifications_count();
}

// =========================================================================================== \\

/**
 * Function to tidy up old ai4seo_cache table
 * @return void
 */
function ai4seo_tidy_up_old_ai4seo_cache_table() {
    global $wpdb;

    // Check if the table exists.
    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            "{$wpdb->prefix}ai4seo_cache"
        )
    );

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321667, 'Database error: ' . $wpdb->last_error, true);
        return;
    }

    if (!$table_exists) {
        return;
    }

    // Table identifiers can't be prepared; table name is internal.
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $results = $wpdb->get_results("SELECT post_id, data FROM `{$wpdb->prefix}ai4seo_cache`", ARRAY_A);

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321668, 'Database error: ' . $wpdb->last_error, true);
        return;
    }

    foreach ((array) $results as $result) {
        $post_id = absint($result['post_id']);
        if (!$post_id) {
            continue;
        }

        $decoded = json_decode((string) $result['data'], true);
        $data = ai4seo_deep_sanitize(is_array($decoded) ? $decoded : array());

        ai4seo_save_generated_data_to_postmeta($post_id, $data);
    }

    // Table identifiers can't be prepared; table name is internal.
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}ai4seo_cache`");

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321669, 'Database error: ' . $wpdb->last_error, true);
        return;
    }
}

// =========================================================================================== \\

/**
 * Function to init cron jobs
 * @return void
 */
function ai4seo_init_cron_jobs() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    try {
        if (!ai4seo_robhub_api(true) || !ai4seo_robhub_api()->is_initialized) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }

    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        return;
    }

    // Add custom cron schedule
    add_filter("cron_schedules", "ai4seo_add_cron_job_intervals");

    // add cron jobs to automate content generation
    add_action(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, AI4SEO_BULK_GENERATION_CRON_JOB_NAME);

    // add cron jobs to analyze current state of the plugins performance
    add_action(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME);

    // schedule cron jobs if not already scheduled
    ai4seo_schedule_cron_jobs();
}

// =========================================================================================== \\

/**
 * Adjusts the browser title for all AI for SEO plugin admin pages.
 *
 * Uses ai4seo_is_user_inside_our_plugin_admin_pages(), ai4seo_get_active_subpage(),
 * ai4seo_get_active_post_type(), and ai4seo_get_plugins_menu_registry()
 * to determine the current page label.
 *
 * @param string $admin_title Default admin title.
 * @param string $title       Original page title.
 * @return string Filtered admin title.
 */
function ai4seo_filter_admin_title(string $admin_title, string $title ): string {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return false;
    }

    if ( ! ai4seo_is_user_inside_our_plugin_admin_pages() ) {
        return $admin_title;
    }

    $registry       = ai4seo_get_plugins_menu_registry();
    $active_subpage = ai4seo_get_active_subpage();
    $active_post_type_subpage    = ai4seo_get_active_post_type_subpage();

    // default label
    $active_page_label = __( 'Dashboard', 'ai-for-seo' );

    if ( ! empty( $active_subpage ) && isset( $registry[ $active_subpage ] ) ) {
        // Static subpages: settings, media, account, help
        $active_page_label = $registry[ $active_subpage ]['label'];
    } elseif ( $active_subpage === 'post' && ! empty( $active_post_type_subpage ) && isset( $registry['post_types'][ $active_post_type_subpage ] ) ) {
        // Dynamic post-type subpages
        $active_page_label = $registry['post_types'][ $active_post_type_subpage ]['label'];
    }

    $website_name = get_bloginfo( 'name' );

    // build everything together and sanitize
    $browser_title = $active_page_label . ' ‹ ' . AI4SEO_PLUGIN_NAME . ' ‹ '  . $website_name;
    $browser_title = wp_strip_all_tags( $browser_title );
    $browser_title = str_replace( array( '&amp;', '&#038;' ), '&', $browser_title );
    $browser_title = str_replace( array( '&lt;', '&#060;' ), '<', $browser_title );
    $browser_title = str_replace( array( '&gt;', '&#062;' ), '>', $browser_title );
    $browser_title = str_replace( array( '&quot;', '&#034;' ), '"', $browser_title );
    $browser_title = str_replace( array( '&#039;', '&#039;' ), "'", $browser_title );

    return $browser_title;
}

// =========================================================================================== \\

/**
 * Build a registry of AI4SEO menu entries: labels and slugs.
 *
 * @return array{
 *   dashboard: array{label:string, slug:string},
 *   media: array{label:string, slug:string},
 *   account: array{label:string, slug:string},
 *   settings: array{label:string, slug:string},
 *   help: array{label:string, slug:string},
 *   post_types: array<string, array{label:string, slug:string}>
 * }
 */
function ai4seo_get_plugins_menu_registry(): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(925974828, 'Prevented loop', true);
        return array();
    }

    // Static pages.
    $dashboard_slug = str_replace( '?page=', '', ai4seo_get_subpage_url( 'dashboard', array(), false ) ); // typically 'ai-for-seo'
    $settings_slug  = str_replace( '?page=', '', ai4seo_get_subpage_url( 'settings', array(), false ) );  // e.g. 'ai-for-seo&ai4seo_subpage=settings'
    $media_slug     = str_replace( '?page=', '', ai4seo_get_subpage_url( 'media', array(), false ) );     // e.g. 'ai-for-seo&ai4seo_subpage=media'
    $account_slug   = str_replace( '?page=', '', ai4seo_get_subpage_url( 'account', array(), false ) );   // e.g. 'ai-for-seo&ai4seo_subpage=account'
    $help_slug      = str_replace( '?page=', '', ai4seo_get_subpage_url( 'help', array(), false ) );      // e.g. 'ai-for-seo&ai4seo_subpage=help'

    // Dynamic post-type pages: use a stable slug (no pagination or other volatile args).
    $post_types    = ai4seo_get_supported_post_types();
    $post_type_map = array();

    foreach ( $post_types as $this_post_type ) {
        $this_post_type = sanitize_key( $this_post_type );
        $label = ai4seo_get_nice_label( ai4seo_get_post_type_translation( $this_post_type, true ) );
        $slug  = AI4SEO_PLUGIN_IDENTIFIER . '&ai4seo_subpage=post&ai4seo_post_type=' . $this_post_type;

        $post_type_map[ $this_post_type ] = array(
            'label' => $label,
            'slug'  => $slug,
        );
    }

    return array(
        'dashboard'  => array(
            'label' => __( 'Dashboard', 'ai-for-seo' ),
            'slug'  => $dashboard_slug,
        ),
        'media'      => array(
            'label' => _n( 'Media', 'Media', 2, 'ai-for-seo' ),
            'slug'  => $media_slug,
        ),
        'account'    => array(
            'label' => __( 'Account', 'ai-for-seo' ),
            'slug'  => $account_slug,
        ),
        'settings'   => array(
            'label' => __( 'Settings', 'ai-for-seo' ),
            'slug'  => $settings_slug,
        ),
        'help'       => array(
            'label' => __( 'Help', 'ai-for-seo' ),
            'slug'  => $help_slug,
        ),
        'post_types' => $post_type_map,
    );
}

// =========================================================================================== \\

/**
 * Register AI4SEO menu and submenus.
 *
 * @return void
 */
function ai4seo_add_menu_entries() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $svg_tags = ai4seo_get_svg_tags();

    if ( ! isset( $svg_tags['ai-for-seo-main-menu-icon'] ) ) {
        return;
    }

    $encoded_svg = 'data:image/svg+xml;base64,' . base64_encode( $svg_tags['ai-for-seo-main-menu-icon'] );

    // Top-level title with notification bubble.
    $menu_title        = AI4SEO_PLUGIN_NAME;
    $notification_count = ai4seo_get_num_unread_notification();

    if ( $notification_count > 0 ) {
        # todo: remove absolute position, after AI4SEO_PLUGIN_NAME is short enough to not need it
        $menu_title .= " <span style='position: absolute; right: 12px;' class='update-plugins count-{$notification_count}'><span class='plugin-count'>{$notification_count}</span></span>";
    }

    // Central registry for labels and slugs.
    $plugins_menu_registries = ai4seo_get_plugins_menu_registry();

    // Top-level.
    add_menu_page(
        AI4SEO_PLUGIN_NAME,
        $menu_title,                 // Contains markup for bubble. Keep as-is.
        'edit_posts',
        AI4SEO_PLUGIN_IDENTIFIER,
        'ai4seo_include_menu_frame_file',
        $encoded_svg,
        99
    );

    // Dashboard (main page uses parent slug as submenu slug).
    add_submenu_page(
        AI4SEO_PLUGIN_IDENTIFIER,
        $plugins_menu_registries['dashboard']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
        $plugins_menu_registries['dashboard']['label'],
        'edit_posts',
        AI4SEO_PLUGIN_IDENTIFIER,
        'ai4seo_include_menu_frame_file'
    );

    // Dynamic post-type submenus.
    foreach ( $plugins_menu_registries['post_types'] as $this_post_type ) {
        add_submenu_page(
            AI4SEO_PLUGIN_IDENTIFIER,
            $this_post_type['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
            $this_post_type['label'],
            'edit_posts',
            $this_post_type['slug'],
            'ai4seo_include_menu_frame_file'
        );
    }

    // Media.
    add_submenu_page(
        AI4SEO_PLUGIN_IDENTIFIER,
        $plugins_menu_registries['media']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
        $plugins_menu_registries['media']['label'],
        'edit_posts',
        $plugins_menu_registries['media']['slug'],
        'ai4seo_include_menu_frame_file'
    );

    // Account.
    add_submenu_page(
        AI4SEO_PLUGIN_IDENTIFIER,
        $plugins_menu_registries['account']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
        $plugins_menu_registries['account']['label'],
        'edit_posts',
        $plugins_menu_registries['account']['slug'],
        'ai4seo_include_menu_frame_file'
    );

    // Settings.
    add_submenu_page(
        AI4SEO_PLUGIN_IDENTIFIER,
        $plugins_menu_registries['settings']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
        $plugins_menu_registries['settings']['label'],
        'edit_posts',
        $plugins_menu_registries['settings']['slug'],
        'ai4seo_include_menu_frame_file'
    );

    // Help.
    add_submenu_page(
        AI4SEO_PLUGIN_IDENTIFIER,
        $plugins_menu_registries['help']['label'] . ' - ' . AI4SEO_PLUGIN_NAME,
        $plugins_menu_registries['help']['label'],
        'edit_posts',
        $plugins_menu_registries['help']['slug'],
        'ai4seo_include_menu_frame_file'
    );
}

// =========================================================================================== \\

/**
 * Mark our top-level menu as current when any AI for SEO page is open.
 *
 * @param string|null $parent_file
 * @return string|null The slug of the current top-level menu (if any).
 */
function ai4seo_mark_parent_menu_active(?string $parent_file ): ?string {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return "";
    }

    if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
        $parent_file = AI4SEO_PLUGIN_IDENTIFIER;
    }

    return $parent_file;
}

// =========================================================================================== \\

/**
 * Mark the correct submenu entry as current.
 *
 * @param string|null $submenu_file
 * @return string|null The slug of the current submenu entry (if any).
 */
function ai4seo_mark_submenu_active(?string $submenu_file): ?string {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return '';
    }

    if ( ai4seo_is_user_inside_our_plugin_admin_pages() ) {
        // Central registry for labels and slugs.
        $plugins_menu_registries = ai4seo_get_plugins_menu_registry();

        if ($active_post_type = ai4seo_get_active_post_type_subpage()) {
            $submenu_file = $plugins_menu_registries['post_types'][$active_post_type]['slug'] ?? $submenu_file;
        } elseif ( $active_subpage = ai4seo_get_active_subpage() ) {
            $submenu_file = $plugins_menu_registries[$active_subpage]['slug'] ?? $submenu_file;
        } else {
            $submenu_file = AI4SEO_PLUGIN_IDENTIFIER;
        }
    }

    return $submenu_file;
}

// =========================================================================================== \\

/**
 * Function to display the menu frame
 * @return void
*/
function ai4seo_include_menu_frame_file() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    include_once(ai4seo_get_plugin_dir_path("includes/menu-frame.php"));
}

// =========================================================================================== \\

/**
 * Function to add modal schemas to the footer
 * @return void
 */
function ai4seo_include_modal_schemas_file() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    include_once(ai4seo_get_includes_modal_schemas_path("autoload-modal-schemas.php"));
}

// =========================================================================================== \\

function ai4seo_enqueue_frontend_scripts() {
    global $ai4seo_scripts_version_number;

    // prevent multiple calls of this function
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check if we are outside the admin area
    if (is_admin()) {
        return;
    }

    // Enqueue ai-for-seo-alt-text-injection
    $is_render_level_alt_text_enabled = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION);
    $is_js_alt_text_enabled = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION);

    if ($is_render_level_alt_text_enabled && $is_js_alt_text_enabled) {
        wp_enqueue_script(AI4SEO_INJECTION_SCRIPTS_HANDLE, ai4seo_get_assets_js_path("ai-for-seo-alt-text-injection.js"), array("jquery"), $ai4seo_scripts_version_number, true);
    }
}

// =========================================================================================== \\

/**
 * Function to enqueue javascript- and css-files
 * @return void
*/
function ai4seo_enqueue_admin_scripts() {
    global $ai4seo_scripts_version_number;

    // prevent multiple calls of this function
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    wp_enqueue_script("wp-i18n");

    // Register and enqueue stylesheet
    wp_register_style(AI4SEO_STYLES_HANDLE, ai4seo_get_assets_css_path("ai-for-seo-styles.css"), "", $ai4seo_scripts_version_number);
    wp_enqueue_style(AI4SEO_STYLES_HANDLE);

    // Enqueue javascript-file
    wp_enqueue_script(AI4SEO_SCRIPTS_HANDLE, ai4seo_get_assets_js_path("ai-for-seo-scripts.js"), array("jquery", "wp-i18n"), $ai4seo_scripts_version_number, true);

    // Set localization parameters
    ai4seo_set_localization_parameters();

    /**
     * Prevent optimizers from combining/caching the main script which contains a localized nonce.
     */
    add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
        if ( $handle === AI4SEO_SCRIPTS_HANDLE ) {
            // LiteSpeed/Autoptimize respect data-no-optimize.
            $tag = str_replace( '<script ', '<script data-no-optimize="1" ', $tag );
        }

        return $tag;
    }, 10, 3 );
}

// =========================================================================================== \\

/**
 * Function to set localization parameters
 *
 * @return void
 */
function ai4seo_set_localization_parameters() {
    global $ai4seo_scripts_version_number;

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // region INITIALISATIONS ====================================================== \\

    $current_post_id                            = ai4seo_get_current_post_id();
    $ajax_nonce                                 = wp_create_nonce( AI4SEO_GLOBAL_NONCE_IDENTIFIER );
    $site_url                                   = site_url();
    $admin_url                                  = admin_url();
    $admin_ajax_url                             = admin_url( 'admin-ajax.php' );
    $includes_url                               = includes_url();
    $content_url                                = content_url();
    $plugin_url                                 = plugins_url();
    $plugin_directory_url                       = plugins_url( '', __FILE__ );
    $uploads_directory_url                      = wp_upload_dir();
    $assets_directory_url                       = ai4seo_get_plugins_url( 'assets' );
    $does_user_need_to_accept_tos_toc_and_pp    = ai4seo_does_user_need_to_accept_tos_toc_and_pp();
    $active_subpage                             = ai4seo_get_active_subpage();
    $active_post_type_subpage                   = ai4seo_get_active_post_type_subpage();
    $active_meta_tags                           = ai4seo_get_active_meta_tags();
    $active_attachment_attributes               = ai4seo_get_active_attachment_attributes();
    $bypass_incognito_mode                      = ( isset( $_REQUEST['ai4seo_debug_bypass_incognito_mode'] ) && $_REQUEST['ai4seo_debug_bypass_incognito_mode'] );
    $metadata_price_table                       = ai4seo_get_metadata_price_table();
    $attachment_attributes_price_table          = ai4seo_get_attachment_attributes_price_table();

    // region LOCALIZATION PARAMETERS ============================================== \\

    $localization_parameters = array(
        'ai4seo_plugin_identifier'                     => AI4SEO_PLUGIN_IDENTIFIER,
        'ai4seo_plugin_name'                           => AI4SEO_PLUGIN_NAME,
        'ai4seo_short_plugin_name'                     => AI4SEO_SHORT_PLUGIN_NAME,
        'ai4seo_site_url'                              => $site_url,
        'ai4seo_admin_url'                             => $admin_url,
        'ai4seo_admin_ajax_url'                        => $admin_ajax_url,
        'ai4seo_includes_url'                          => $includes_url,
        'ai4seo_content_url'                           => $content_url,
        'ai4seo_plugin_url'                            => $plugin_url,
        'ai4seo_plugin_directory_url'                  => $plugin_directory_url,
        'ai4seo_uploads_directory_url'                 => $uploads_directory_url,
        'ai4seo_assets_directory_url'                  => $assets_directory_url,
        'ai4seo_does_user_need_to_accepted_tos_toc_and_pp' => $does_user_need_to_accept_tos_toc_and_pp,
        'ai4seo_plugin_version_number'                 => AI4SEO_PLUGIN_VERSION_NUMBER,
        'ai4seo_admin_scripts_version_number'          => $ai4seo_scripts_version_number,
        'ai4seo_current_post_id'                       => $current_post_id,
        AI4SEO_GLOBAL_NONCE_IDENTIFIER                 => $ajax_nonce,
        'ai4seo_bypass_incognito_mode'                 => $bypass_incognito_mode,
        'ai4seo_active_subpage'                        => $active_subpage,
        'ai4seo_active_post_type_subpage'              => $active_post_type_subpage,
        'ai4seo_active_meta_tags'                      => $active_meta_tags,
        'ai4seo_active_attachment_attributes'          => $active_attachment_attributes,
        'ai4seo_max_editor_input_lengths'              => AI4SEO_MAX_EDITOR_INPUT_LENGTHS,
        'ai4seo_metadata_price_table'                  => $metadata_price_table,
        'ai4seo_attachment_attributes_price_table'     => $attachment_attributes_price_table,
    );

    // region REGISTER SCRIPT LOCALIZATION ========================================= \\

    wp_localize_script( AI4SEO_SCRIPTS_HANDLE, 'ai4seo_localization', $localization_parameters );
    wp_set_script_translations( AI4SEO_SCRIPTS_HANDLE, 'ai-for-seo' );
}


// =========================================================================================== \\

/**
 * Function to add new column to page- and post-table
 * @param array $columns
 * @return array
*/
function ai4seo_add_metadata_editor_column_to_posts_table(array $columns): array {
    // Make sure that this function is only called once
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(929531028, 'Prevented loop', true);
        return array();
    }

    $sooz_oo_logo = ai4seo_get_sooz_logo_image_tag('sooz-oo');

    return array_merge($columns, [AI4SEO_PLUGIN_IDENTIFIER => $sooz_oo_logo]);
}

// =========================================================================================== \\

/**
 * Function to add content to new page- and post-table column
 * @param string $column_name
 * @param int $post_id
 * @return void
*/
function ai4seo_add_metadata_editor_button_to_posts_table(string $column_name, int $post_id) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(907296736, 'Prevented loop', true);
        return;
    }

    if ($column_name == AI4SEO_PLUGIN_IDENTIFIER) {
        ai4seo_echo_wp_kses(ai4seo_get_edit_metadata_button($post_id));
    }
}

// =========================================================================================== \\

/**
 * Function to add plugin links (in the plugin directory)
 * @return array $links - array with links that will be displayed in the plugin directory near the plugin name
 */
function ai4seo_add_links_to_the_plugin_directory($links): array {
    // Check for function get_current_user_id()
    if (!function_exists("get_current_user_id")) {
        return array();
    }

    // check if we loaded plugins already
    if (!did_action("load-plugins.php")) {
        return $links; // avoid running in unexpected contexts
    }

    // double check if we are in the plugin directory
    $this_plugin_basename = sanitize_text_field(ai4seo_get_plugin_basename());

    if (current_filter() !== "plugin_action_links_{$this_plugin_basename}") {
        return $links;
    }

    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return $links;
    }

    // remove everything from $links, except the deactivate link
    $links = array_filter($links, function($link) {
        return strpos($link, 'deactivate') !== false;
    });

    // Define variables for the incognito-setting
    $ai4seo_setting_enable_incognito_mode = ai4seo_is_incognito_mode_enabled();
    $ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID);
    $current_user_id = get_current_user_id();

    // Check incognito-setting and incognito user-id
    if ($ai4seo_setting_enable_incognito_mode && $ai4seo_setting_incognito_mode_user_id != $current_user_id) {
        return array();
    }

    $dashboard_link_url = ai4seo_get_subpage_url("dashboard");

    // only show help and upgrade links if the user has not accepted the TOS yet
    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        // add accept terms of service link (by going to the plugin's dashboard)
        $tos_link_tag = "<a href='" . esc_url($dashboard_link_url) . "'>> " . esc_html__("Accept Terms of Service", "ai-for-seo") . " <</a>";
        array_unshift($links, $tos_link_tag);
    } else {
        // Add Settings Link
        $settings_link_url = ai4seo_get_subpage_url("settings");

        if ($settings_link_url) {
            $settings_link_tag = "<a href='" . esc_url($settings_link_url) . "'>" . esc_html__("Settings", "ai-for-seo") . "</a>";
            array_unshift($links, $settings_link_tag);
        }

        // add Help link
        $help_link_url = ai4seo_get_subpage_url("help");

        if ($help_link_url) {
            $help_link_tag = "<a href='" . esc_url($help_link_url) . "'>" . esc_html__("Help", "ai-for-seo") . "</a>";
            array_unshift($links, $help_link_tag);
        }

        # todo: add get more credits link with ajax modal

        // add dashboard link at the front of the links
        $dashboard_link_tag = "<a href='" . esc_url($dashboard_link_url) . "'>" . esc_html__("Dashboard", "ai-for-seo") . "</a>";
        array_unshift($links, $dashboard_link_tag);
    }

    return $links;
}

// =========================================================================================== \\

/**
 * Function to add menu-item to admin-bar
 * @return void
 */
function ai4seo_add_admin_menu_item($wp_admin_bar) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // Stop function if called outside of page or post etc.
    if (!is_singular()) {
        return;
    }

    // Prepare arguments for admin-bar menu-item
    $args = array(
        "id" => "ai4seo-edit",
        "title" => "<div class='ai4seo-main-menu-icon'></div> " . esc_html__("Metadata Editor", "ai-for-seo"),
        "meta" => array(
            "onclick" => "ai4seo_open_metadata_editor_modal();return false;",
        ),
    );

    // Add node
    $wp_admin_bar->add_node($args);

    // Add node for mobile version
    $wp_admin_bar->add_menu( array(
        "parent" => "appearance",
        "id" => "ai4seo-edit-mobile",
        "title" => sprintf(
            /* translators: %s: plugin name */
            esc_html__("%s - Metadata Editor", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        ),
        "meta" => array(
            "onclick" => "ai4seo_open_metadata_editor_modal();return false;",
        ),
    ));
}

// =========================================================================================== \\

/*function ai4seo_init_meta_tags_output() {
    // read setting AI4SEO_SETTING_META_TAG_OUTPUT_MODE
    $meta_tag_output_mode = ai4seo_get_setting(AI4SEO_SETTING_META_TAG_OUTPUT_MODE);

    // Stop function if meta tag output is disabled
    if ($meta_tag_output_mode == "disable") {
        return;
    }

    if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO)) {
        // Squirrly SEO workaround, as they use their own buffer
        add_action("sq_buffer", "ai4seo_inject_our_meta_tags_into_the_html_head", 20);
    } else {
        ob_start('ai4seo_inject_our_meta_tags_into_the_html_head');
    }
}*/

// =========================================================================================== \\

/**
 * Function modify and add meta tags to the html header
 * @param string $full_html_buffer - the full html buffer
 * @return string $full_html_buffer - the modified html buffer
 */
function ai4seo_inject_our_meta_tags_into_the_html_head(string $full_html_buffer): string {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return $full_html_buffer;
    }

    // check if we are on a singular
    if (!is_singular()) {
        return $full_html_buffer; // stop function if we are not on a singular page or post
    }

    // Define variable for the page- or post-id
    $post_id = ai4seo_get_current_post_id();

    // Stop function if no page- or post-id is defined
    if (!$post_id) {
        return $full_html_buffer;
    }

    if (!defined('AI4SEO_METADATA_DETAILS')) {
        return $full_html_buffer;
    }

    // read setting AI4SEO_SETTING_META_TAG_OUTPUT_MODE
    $meta_tag_output_mode = ai4seo_get_setting(AI4SEO_SETTING_META_TAG_OUTPUT_MODE);

    if ($meta_tag_output_mode == "disable") {
        return $full_html_buffer; // stop function if meta tag output is disabled
    }

    // read settings AI4SEO_SETTING_VISIBLE_META_TAGS
    $active_meta_tags = ai4seo_get_active_meta_tags();

    if (!$active_meta_tags) {
        return $full_html_buffer;
    }

    // Extract the content between <head> and </head>
    $head_start_position = strpos($full_html_buffer, '<head>');

    if ($head_start_position === false) {
        return $full_html_buffer;
    }

    // start position right after <head>
    $head_start_position += 6;

    $head_end_position = strpos($full_html_buffer, '</head>');

    if ($head_end_position === false) {
        $head_end_position = strlen($full_html_buffer); // if no closing head tag is found, set end position to the end of the buffer
    }

    $head_html = substr($full_html_buffer, $head_start_position, $head_end_position - $head_start_position);

    // analyze head html
    $found_third_party_meta_tags = ai4seo_get_meta_tags_from_html($head_html);

    // read OUR metadata values for this post
    $our_metadata = ai4seo_read_available_metadata_by_post_ids(array($post_id), false);

    if ($our_metadata) {
        $our_metadata = $our_metadata[$post_id] ?? array();
    }

    // check post type
    $supported_post_types = ai4seo_get_supported_post_types();
    $current_post_type = get_post_type($post_id);

    if (!in_array($current_post_type, $supported_post_types, true)) {
        return $full_html_buffer;
    }

    $current_product_price_for_placeholders = '';
    $current_product_name_for_placeholders = '';
    $current_post_title_for_placeholders = '';

    $current_post_title_raw = get_the_title($post_id);

    if (is_string($current_post_title_raw)) {
        $current_post_title_for_placeholders = trim(wp_strip_all_tags($current_post_title_raw));
    }

    if ($current_post_type === 'product'
        && ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE)
        && function_exists('wc_get_product') && ai4seo_is_function_usable('wc_get_product')
        && function_exists('wc_price') && ai4seo_is_function_usable('wc_price')
        && class_exists('WC_Product')
    ) {
        $current_wc_product = wc_get_product($post_id);

        if ($current_wc_product instanceof WC_Product) {
            $current_product_name_for_placeholders = wp_strip_all_tags($current_wc_product->get_name());
            $current_wc_product_price_raw = $current_wc_product->get_price();

            if ($current_wc_product_price_raw !== '' && $current_wc_product_price_raw !== null) {
                $current_wc_product_price = wc_price($current_wc_product_price_raw);
                $current_wc_product_price = wp_strip_all_tags($current_wc_product_price);
                $current_wc_product_price = html_entity_decode($current_wc_product_price, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $current_wc_product_price = str_replace("\xC2\xA0", ' ', $current_wc_product_price);
                $current_product_price_for_placeholders = trim($current_wc_product_price);
            }
        }
    }

    // go through each meta tag and decide what to do with it
    $add_this_metadata = array();
    $remove_this_third_party_meta_tags = array();

    $metadata_placeholder_replacements = ai4seo_get_metadata_placeholder_replacements(
        $post_id,
        $current_product_price_for_placeholders,
        $current_product_name_for_placeholders
    );

    if ($current_post_title_for_placeholders !== '') {
        $metadata_placeholder_replacements['TITLE'] = $current_post_title_for_placeholders;
    }

    foreach (AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_field_details) {
        $this_found_third_party_meta_tags = $found_third_party_meta_tags[$this_metadata_identifier] ?? array();
        $this_our_metadata = $our_metadata[$this_metadata_identifier] ?? "";

        // exclude this meta tag if not active
        if (!in_array($this_metadata_identifier, $active_meta_tags)) {
            $this_our_metadata = "";
            $our_metadata[$this_metadata_identifier] = "";
        }

        // find a fallback if neither we nor a third party have a value for this meta tag
        if (empty($this_our_metadata) && empty($this_found_third_party_meta_tags)) {
            ai4seo_apply_possible_fallbacks($post_id, $this_metadata_identifier, $our_metadata);
            $this_our_metadata = $our_metadata[$this_metadata_identifier] ?? "";
        }

        // leave this meta tag alone if we do not have a value for it or we exclude this meta tag
        if (!$this_our_metadata) {
            continue;
        }

        switch ($meta_tag_output_mode) {
            case "force":
                $add_this_metadata[$this_metadata_identifier] = $this_our_metadata;
                break;
            case "replace":
                $add_this_metadata[$this_metadata_identifier] = $this_our_metadata;

                // remove found third party meta tags
                if ($this_found_third_party_meta_tags) {
                    foreach ($this_found_third_party_meta_tags AS $this_found_third_party_meta_tag) {
                        if ($this_found_third_party_meta_tag) {
                            $remove_this_third_party_meta_tags[] = $this_found_third_party_meta_tag["raw-html"];
                        }
                    }
                }
                break;
            case "complement":
                if (!$this_found_third_party_meta_tags) {
                    $add_this_metadata[$this_metadata_identifier] = $this_our_metadata;
                } else {
                    // workaround: if all the found meta tags are empty -> add ours anyway and remove their empty ones
                    $this_found_third_party_meta_tag_got_content = false;
                    $this_found_third_party_meta_tag_no_content_raw_html = array();
                    foreach ($this_found_third_party_meta_tags AS $this_found_third_party_meta_tag) {
                        if ($this_found_third_party_meta_tag["content"]) {
                            $this_found_third_party_meta_tag_got_content = true;
                            break;
                        } else {
                            $this_found_third_party_meta_tag_no_content_raw_html[] = $this_found_third_party_meta_tag["raw-html"];
                        }
                    }

                    if (!$this_found_third_party_meta_tag_got_content) {
                        $add_this_metadata[$this_metadata_identifier] = $this_our_metadata;
                        $remove_this_third_party_meta_tags = array_merge($remove_this_third_party_meta_tags, $this_found_third_party_meta_tag_no_content_raw_html);
                    }
                }
                break;
        }
    }

    // Remove any third-party meta tags and surrounding non-visible characters
    if ($remove_this_third_party_meta_tags) {
        foreach ($remove_this_third_party_meta_tags AS $this_remove_this_meta_tag) {
            // Use preg_replace to match the tag and any surrounding whitespace or line breaks
            $full_html_buffer = preg_replace(
                '/' . preg_quote($this_remove_this_meta_tag, '/') . '\s*/s',
                '',
                $full_html_buffer
            );
        }
    }

    // add our tags to the head, finding position first
    if ($add_this_metadata) {
        $add_this_meta_tags = array();

        // Read prefix- and suffix-settings
        $ai4seo_metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
        $ai4seo_metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);

        // prepare our meta tags
        foreach ($add_this_metadata as $this_metadata_identifier => $this_metadata_content) {
            $this_metadata_field_details = AI4SEO_METADATA_DETAILS[$this_metadata_identifier] ?? array();
            $this_metadata_prefix_raw = $ai4seo_metadata_prefixes[$this_metadata_identifier] ?? "";
            $this_metadata_suffix_raw = $ai4seo_metadata_suffixes[$this_metadata_identifier] ?? "";

            $this_metadata_prefix = trim(sanitize_text_field($this_metadata_prefix_raw));
            $this_metadata_suffix = trim(sanitize_text_field($this_metadata_suffix_raw));

            if ($current_post_type !== 'product'
                && ai4seo_text_contains_product_placeholder($this_metadata_prefix_raw)
            ) {
                $this_metadata_prefix = '';
            } else {
                $this_metadata_prefix = ai4seo_replace_text_placeholders(
                    $this_metadata_prefix,
                    $metadata_placeholder_replacements
                );
            }

            if ($current_post_type !== 'product'
                && ai4seo_text_contains_product_placeholder($this_metadata_suffix_raw)
            ) {
                $this_metadata_suffix = '';
            } else {
                $this_metadata_suffix = ai4seo_replace_text_placeholders(
                    $this_metadata_suffix,
                    $metadata_placeholder_replacements
                );
            }

            $this_metadata_prefix = ai4seo_replace_metadata_title_placeholder(
                $this_metadata_prefix,
                $current_post_title_for_placeholders
            );

            $this_metadata_suffix = ai4seo_replace_metadata_title_placeholder(
                $this_metadata_suffix,
                $current_post_title_for_placeholders
            );

            if (!$this_metadata_field_details) {
                continue;
            }

            if (false !== strpos($this_metadata_content, '{WC_PRICE=')) {
                $this_metadata_content = preg_replace_callback(
                    '/\{WC_PRICE=([^}]+)\}/',
                    function ($matches) use ($current_product_price_for_placeholders) {
                        $fallback_price = html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $fallback_price = str_replace("\xC2\xA0", ' ', $fallback_price);
                        $fallback_price = trim($fallback_price);

                        if ($current_product_price_for_placeholders !== '') {
                            return $current_product_price_for_placeholders;
                        }

                        return $fallback_price;
                    },
                    $this_metadata_content
                );
            }

            // Add prefix and suffix
            $this_metadata_content = trim($this_metadata_prefix . " " . $this_metadata_content . " " . $this_metadata_suffix);

            // Prepare variables
            $this_output_tag_type = $this_metadata_field_details["output-tag-type"] ?? "";
            $this_output_tag_identifier = $this_metadata_field_details["output-tag-identifier"] ?? "";

            // Handle output for output-tag-type "title"
            if ($this_output_tag_type == "title") {
                $add_this_meta_tags[] = "<title>" . esc_attr($this_metadata_content) . "</title>";
            }

            // Handle output for output-tag-type "meta name"
            elseif ($this_output_tag_type == "meta name") {
                $add_this_meta_tags[] = "<meta name=\"" . esc_attr($this_output_tag_identifier) . "\" content=\"" . esc_attr($this_metadata_content) . "\" />";
            }

            // Handle output for output-tag-type "meta property"
            elseif ($this_output_tag_type == "meta property") {
                $add_this_meta_tags[] = "<meta property=\"" . esc_attr($this_output_tag_identifier) . "\" content=\"" . esc_attr($this_metadata_content) . "\" />";
            }
        }

        // output our meta tags
        if ($add_this_meta_tags) {
            // find a suitable position for our meta tags
            $our_meta_tags_position = $head_start_position;

            // consider the charset meta tag position, if it's near the head start
            if (isset($found_third_party_meta_tags["charset"])) {
                $charset_meta_tags_position = strpos($full_html_buffer, $found_third_party_meta_tags["charset"]["raw-html"]) + strlen($found_third_party_meta_tags["charset"]["raw-html"]);

                // set $charset_meta_tags_position as our meta tags position if it's not further away than 100 characters
                if ($charset_meta_tags_position - $head_start_position < 100) {
                    $our_meta_tags_position = $charset_meta_tags_position;
                }
            }

            // consider the viewport meta tag position, if it's near the head start
            if (isset($found_third_party_meta_tags["viewport"])) {
                $viewport_meta_tags_position = strpos($full_html_buffer, $found_third_party_meta_tags["viewport"]["raw-html"]) + strlen($found_third_party_meta_tags["viewport"]["raw-html"]);

                // set $viewport_meta_tags_position as our meta tags position if it's not further away than 200 characters
                if ($viewport_meta_tags_position - $head_start_position < 200) {
                    $our_meta_tags_position = $viewport_meta_tags_position;
                }
            }

            // Read start- and end-settings for generator hints
            $add_generator_hints = ai4seo_get_setting(AI4SEO_SETTING_ADD_GENERATOR_HINTS);

            if ($add_generator_hints) {
                $source_code_notes_start = ai4seo_get_setting(AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT);
                $source_code_notes_end = ai4seo_get_setting(AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT);

                // Replace placeholders in source-code-notes
                $source_code_notes_start = ai4seo_replace_white_label_placeholders($source_code_notes_start);
                $source_code_notes_end = ai4seo_replace_white_label_placeholders($source_code_notes_end);

                // Make sure that $source_code_notes_start and $source_code_notes_end don't exceed the max. length
                // decode \&quot; to "
                $source_code_notes_start = str_replace('\&quot;', '"', $source_code_notes_start);
                $source_code_notes_end = str_replace('\&quot;', '"', $source_code_notes_end);
                $source_code_notes_start = ai4seo_mb_substr($source_code_notes_start, 0, 250);
                $source_code_notes_end = ai4seo_mb_substr($source_code_notes_end, 0, 250);

                // add plugin information to the meta tags block
                array_unshift($add_this_meta_tags, "<!-- " . esc_html($source_code_notes_start) . " -->");
                $add_this_meta_tags[] = "<!-- " . esc_html($source_code_notes_end) . " -->";
            }

            $add_this_meta_tags = ai4seo_deep_sanitize($add_this_meta_tags, 'ai4seo_wp_kses');

            // add our meta tags to the head
            $full_html_buffer = substr_replace($full_html_buffer, "\n\n\t" . implode("\n\t", $add_this_meta_tags) . "\n", $our_meta_tags_position, 0);
        }
    }

    return $full_html_buffer;
}

// =========================================================================================== \\

function ai4seo_inject_image_attributes_for_gutenberg( $content, $block ) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return $content;
    }

    // Skip admin, feeds, REST API and AJAX requests
    if (
        is_admin()
        || is_feed()
        || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
        || ( defined( 'DOING_AJAX' ) && DOING_AJAX )
    ) {
        return $content;
    }

    return ai4seo_inject_image_attributes_into_html( $content );
}

// =========================================================================================== \\

/**
 * Function to inject image attributes (alt text and title) into images
 * Sets up output buffering to inject attributes at render level when enabled
 */
function ai4seo_inject_image_attributes_into_html($content) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return $content;
    }

    $alt_enabled   = ai4seo_get_setting( AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION );
    $title_injection_mode = ai4seo_get_setting( AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE );

    if ( ! $alt_enabled && $title_injection_mode === 'disabled' ) {
        return $content;
    }

    static $cache = [];

    return preg_replace_callback(
        '/<img\b([^>]*?)>/i',
        function( $matches ) use ( &$cache, $alt_enabled, $title_injection_mode ) {
            $this_full_tag = $matches[0];
            $this_attr_str = $matches[1];

            // find src → attachment ID
            if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/', $this_attr_str, $this_src_matches ) ) {
                return $this_full_tag;
            }

            $this_post_id = ai4seo_get_attachment_id_from_src( $this_src_matches[1] );

            if ( ! $this_post_id ) {
                return $this_full_tag;
            }

            // make sure our plugin generated alt text for this entry
            // check if we have a wp_postmeta entry AI4SEO_POST_META_GENERATED_DATA_META_KEY for this post id -> skip if not
            if (!get_post_meta($this_post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, true)) {
                return $this_full_tag;
            }

            $this_needs_mod = false;
            $this_to_add    = [];

            // ——— ALT logic ———
            if ( $alt_enabled ) {
                // grab existing (might be empty)
                preg_match( '/\balt\s*=\s*["\']([^"\']*)["\']/', $this_attr_str, $this_alt_matches );
                $this_existing_alt = $this_alt_matches[1] ?? null;

                // get DB value (once)
                if ( ! isset( $cache[ $this_post_id ]['alt'] ) ) {
                    $cache[ $this_post_id ]['alt'] = get_post_meta( $this_post_id, '_wp_attachment_image_alt', true );
                }
                $this_db_alt = $cache[ $this_post_id ]['alt'];

                if ( $this_db_alt && $this_existing_alt !== $this_db_alt ) {
                    $this_to_add['alt'] = $this_db_alt;
                }
            }

            // ——— TITLE logic ———
            if ( $title_injection_mode !== 'disabled' ) {
                preg_match( '/\btitle\s*=\s*["\']([^"\']*)["\']/', $this_attr_str, $this_title_matches );
                $this_existing_title = $this_title_matches[1] ?? null;

                if ( ! isset( $cache[ $this_post_id ][ $title_injection_mode ] ) ) {
                    $cache[ $this_post_id ][ $title_injection_mode ] = ai4seo_get_title_attribute_value( $this_post_id, $title_injection_mode, $cache );
                }
                $this_db_title = $cache[ $this_post_id ][ $title_injection_mode ];

                if ( $this_db_title && $this_existing_title !== $this_db_title ) {
                    $this_to_add['title'] = $this_db_title;
                }
            }

            if ( ! $this_to_add ) {
                return $this_full_tag;
            }

            // strip out any old alt="" or title=""
            if (isset($this_to_add['alt'])) {
                $this_attr_str = preg_replace( '/\s*(?:alt)\s*=\s*["\'][^"\']*["\']/', '', $this_attr_str );
            }

            if (isset($this_to_add['title'])) {
                $this_attr_str = preg_replace( '/\s*(?:title)\s*=\s*["\'][^"\']*["\']/', '', $this_attr_str );
            }

            // remove trailing slash
            $this_attr_str = preg_replace( '/\s*\/$/', '', rtrim( $this_attr_str ) );

            // rebuild
            $this_self_closed = substr( rtrim( $this_full_tag ), -2 ) === '/>';
            $this_tag_ending      = $this_self_closed ? ' />' : '>';
            $this_new_tag     = '<img' . $this_attr_str;

            foreach ( $this_to_add as $name => $val ) {
                $this_new_tag .= ' ' . $name . '="' . esc_attr( $val ) . '"';
            }

            $this_full_tag = $this_new_tag . $this_tag_ending;

            return $this_full_tag;
        },
        $content
    );
}

// =========================================================================================== \\

/**
 * Helper function to get the appropriate title attribute value based on setting
 * @param int $attachment_id The attachment ID
 * @param string $setting_value The title injection setting value
 * @param array &$cache Reference to the cache array
 * @return string|false The title attribute value or false if none found
 */
function ai4seo_get_title_attribute_value(int $attachment_id, string $setting_value, array &$cache ) {
    $cache_key = $setting_value;

    if ( isset( $cache[ $attachment_id ][ $cache_key ] ) ) {
        return $cache[ $attachment_id ][ $cache_key ];
    }

    $value = false;

    switch ( $setting_value ) {
        case 'inject_title':
            $value = get_the_title( $attachment_id );
            break;
        case 'inject_alt_text':
            $value = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            break;
        case 'inject_caption':
            $attachment = get_post( $attachment_id );

            if ( $attachment ) {
                $value = $attachment->post_excerpt;
            }
            break;
        case 'inject_description':
            $attachment = get_post( $attachment_id );

            if ( $attachment ) {
                $value = $attachment->post_content;
            }
            break;
    }

    $cache[ $attachment_id ][ $cache_key ] = $value;
    return $value;
}

// =========================================================================================== \\

/**
 * Function to get attachment ID from image src URL
 * @param string $img_src - the image src URL
 * @return int|false - attachment ID or false if not found
 */
function ai4seo_get_attachment_id_from_src(string $img_src) {
    global $wpdb;

    // Remove query parameters and fragments from URL
    $img_src = strtok($img_src, '?');
    $img_src = strtok($img_src, '#');

    // First try WordPress built-in function
    $attachment_id = attachment_url_to_postid($img_src);

    if ($attachment_id) {
        return $attachment_id;
    }

    // If that fails, try to match by filename in case of different sizes
    $filename = basename($img_src);

    // Remove size suffixes like -150x150, -300x200, etc.
    $filename_without_size = preg_replace('/-\d+x\d+(?=\.[^.]*$)/', '', $filename);

    if ($filename_without_size !== $filename) {
        // Try to find by the original filename
        $original_url = str_replace($filename, $filename_without_size, $img_src);
        $attachment_id = attachment_url_to_postid($original_url);

        if ($attachment_id) {
            return $attachment_id;
        }
    }

    // As last resort, search in postmeta for the URL
    $cached_attachment_id = ai4seo_get_cached_attachment_id_from_filename($filename);

    if ($cached_attachment_id !== false) {
        return $cached_attachment_id ?: false;
    }

    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
        esc_sql($filename)
    ));

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321670, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }

    $attachment_id = $attachment_id ? (int) $attachment_id : 0;
    ai4seo_set_cached_attachment_id_from_filename($filename, $attachment_id);

    return $attachment_id ?: false;
}

// =========================================================================================== \\

function ai4seo_filter_wp_image_attrs( $attr, $attachment, $size ) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return $attr;
    }

    if ( empty( $attr['alt'] ) ) {
        $alt = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
        if ( $alt ) {
            $attr['alt'] = sanitize_text_field( $alt );
        }
    }
    return $attr;
}

// =========================================================================================== \\

/**
 * Populate missing metadata values by applying configured fallbacks.
 *
 * @param int    $post_id                      The current post ID.
 * @param string $metadata_identifier          Metadata identifier to resolve.
 * @param array  $our_metadata                 Reference to metadata collection for this post.
 * @param array  $visited_metadata_identifiers Optional stack to avoid circular fallbacks.
 *
 * @return void
 */
function ai4seo_apply_possible_fallbacks(int $post_id, string $metadata_identifier, array &$our_metadata, array $visited_metadata_identifiers = array()): void {
    if (ai4seo_prevent_loops(__FUNCTION__, 5, 100)) {
        ai4seo_debug_message(176107421, 'Prevented loop', true);
        return;
    }

    if (isset($our_metadata[$metadata_identifier]) && $our_metadata[$metadata_identifier]) {
        return;
    }

    if (in_array($metadata_identifier, $visited_metadata_identifiers, true)) {
        return;
    }

    $active_meta_tags = ai4seo_get_active_meta_tags();

    $visited_metadata_identifiers[] = $metadata_identifier;

    $fallback_setting_name = ai4seo_get_metadata_fallback_setting_name($metadata_identifier);

    if (!$fallback_setting_name) {
        return;
    }

    $fallback_preference = ai4seo_get_setting($fallback_setting_name);

    $allowed_fallback_values = ai4seo_get_metadata_fallback_allowed_values($metadata_identifier);

    if (!is_string($fallback_preference) || $fallback_preference === '' || $fallback_preference === 'no-fallback' || !array_key_exists($fallback_preference, $allowed_fallback_values)) {
        return;
    }

    $fallback_value = '';

    switch ($fallback_preference) {
        case 'post-title':
            $fallback_value = ai4seo_get_metadata_fallback_post_title($post_id);
            break;

        case 'post-excerpt':
            $fallback_value = ai4seo_get_metadata_fallback_post_excerpt($post_id);
            break;

        case 'content':
            $fallback_value = ai4seo_get_metadata_fallback_post_content($post_id);
            break;

        default:
            $fallback_metadata_identifier = $fallback_preference;

            // if the fallback metadata identifier is not active, stop here
            if (!in_array($fallback_metadata_identifier, $active_meta_tags, true)) {
                return;
            }

            if (in_array($fallback_metadata_identifier, $visited_metadata_identifiers, true)) {
                return;
            }

            if (!isset($our_metadata[$fallback_metadata_identifier]) || !$our_metadata[$fallback_metadata_identifier]) {
                ai4seo_apply_possible_fallbacks($post_id, $fallback_metadata_identifier, $our_metadata, $visited_metadata_identifiers);
            }

            $fallback_value = $our_metadata[$fallback_metadata_identifier] ?? '';
            break;
    }

    if (!is_string($fallback_value)) {
        if (is_scalar($fallback_value)) {
            $fallback_value = (string) $fallback_value;
        } else {
            return;
        }
    }

    $fallback_value = trim($fallback_value);

    if ($fallback_value === '') {
        return;
    }

    $our_metadata[$metadata_identifier] = $fallback_value;
}

// =========================================================================================== \\

/**
 * Retrieve the setting name that stores the fallback preference for a metadata identifier.
 *
 * @param string $metadata_identifier Metadata identifier.
 *
 * @return string|null The related setting constant or null when unsupported.
 */
function ai4seo_get_metadata_fallback_setting_name(string $metadata_identifier): ?string {
    switch ($metadata_identifier) {
        case 'meta-title':
            return AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE;
        case 'meta-description':
            return AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION;
        case 'facebook-title':
            return AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE;
        case 'facebook-description':
            return AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION;
        case 'twitter-title':
            return AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE;
        case 'twitter-description':
            return AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION;
    }

    return null;
}

// =========================================================================================== \\

/**
 * Retrieve the metadata identifier mapped to a fallback setting name.
 *
 * @param string $setting_name Setting identifier.
 *
 * @return string|null Metadata identifier or null if not supported.
 */
function ai4seo_get_fallback_metadata_identifier_by_setting_name(string $setting_name): ?string {
    switch ($setting_name) {
        case AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE:
            return 'meta-title';
        case AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION:
            return 'meta-description';
        case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE:
            return 'facebook-title';
        case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION:
            return 'facebook-description';
        case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE:
            return 'twitter-title';
        case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION:
            return 'twitter-description';
    }

    return null;
}

// =========================================================================================== \\

/**
 * Return the available fallback options for a metadata identifier.
 *
 * @param string $metadata_identifier Metadata identifier.
 *
 * @return array<string,string> Allowed fallback options.
 */
function ai4seo_get_metadata_fallback_allowed_values(string $metadata_identifier): array {
    switch ($metadata_identifier) {
        case 'meta-title':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-title' => esc_html__('Use post title', 'ai-for-seo'),
                'facebook-title' => esc_html__('Use Facebook title', 'ai-for-seo'),
                'twitter-title' => esc_html__('Use Twitter title', 'ai-for-seo'),
            );

        case 'meta-description':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-excerpt' => esc_html__('Use post excerpt', 'ai-for-seo'),
                'content' => esc_html__('Use post content', 'ai-for-seo'),
                'facebook-description' => esc_html__('Use Facebook description', 'ai-for-seo'),
                'twitter-description' => esc_html__('Use Twitter description', 'ai-for-seo'),
            );

        case 'facebook-title':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-title' => esc_html__('Use post title', 'ai-for-seo'),
                'meta-title' => esc_html__('Use meta title', 'ai-for-seo'),
                'twitter-title' => esc_html__('Use Twitter title', 'ai-for-seo'),
            );

        case 'facebook-description':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-excerpt' => esc_html__('Use post excerpt', 'ai-for-seo'),
                'content' => esc_html__('Use post content', 'ai-for-seo'),
                'meta-description' => esc_html__('Use meta description', 'ai-for-seo'),
                'twitter-description' => esc_html__('Use Twitter description', 'ai-for-seo'),
            );

        case 'twitter-title':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-title' => esc_html__('Use post title', 'ai-for-seo'),
                'meta-title' => esc_html__('Use meta title', 'ai-for-seo'),
                'facebook-title' => esc_html__('Use Facebook title', 'ai-for-seo'),
            );

        case 'twitter-description':
            return array(
                'no-fallback' => esc_html__('No fallback', 'ai-for-seo'),
                'post-excerpt' => esc_html__('Use post excerpt', 'ai-for-seo'),
                'content' => esc_html__('Use post content', 'ai-for-seo'),
                'meta-description' => esc_html__('Use meta description', 'ai-for-seo'),
                'facebook-description' => esc_html__('Use Facebook description', 'ai-for-seo'),
            );
    }

    return array();
}

// =========================================================================================== \\

/**
 * Get a shortened fallback text from the post title.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_title(int $post_id): string {
    $post_title = get_the_title($post_id);
    $post_title = ai4seo_prepare_metadata_fallback_text($post_title);

    if ($post_title === '') {
        return '';
    }

    return ai4seo_limit_metadata_fallback_text($post_title, 100);
}

// =========================================================================================== \\

/**
 * Get a shortened fallback text from the post excerpt.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_excerpt(int $post_id): string {
    $post_excerpt = get_the_excerpt($post_id);
    $post_excerpt = ai4seo_prepare_metadata_fallback_text($post_excerpt);

    if ($post_excerpt === '') {
        return '';
    }

    return ai4seo_limit_metadata_fallback_text($post_excerpt, 150);
}

// =========================================================================================== \\

/**
 * Get a shortened fallback text from the post content.
 *
 * @param int $post_id Current post ID.
 *
 * @return string Prepared fallback text.
 */
function ai4seo_get_metadata_fallback_post_content(int $post_id): string {
    $post_content = get_post_field('post_content', $post_id, 'raw');
    $post_content = ai4seo_prepare_metadata_fallback_text($post_content);

    if ($post_content === '') {
        return '';
    }

    return ai4seo_limit_metadata_fallback_text($post_content, 150);
}

// =========================================================================================== \\

/**
 * Prepare a string for fallback usage by removing markup and normalizing whitespace.
 *
 * @param mixed $text Input text.
 *
 * @return string Cleaned text.
 */
function ai4seo_prepare_metadata_fallback_text($text): string {
    if (!is_string($text)) {
        return '';
    }

    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    if (!is_string($text)) {
        $text = '';
    }

    return trim($text);
}

// =========================================================================================== \\

/**
 * Limit fallback text length while keeping current words intact where possible.
 *
 * @param string $text        Prepared text.
 * @param int    $base_length Hard character limit before soft extension.
 *
 * @return string Shortened text.
 */
function ai4seo_limit_metadata_fallback_text(string $text, int $base_length): string {
    if ($text === '') {
        return '';
    }

    if (ai4seo_mb_strlen($text) <= $base_length) {
        return $text;
    }

    $base_snippet = ai4seo_mb_substr($text, 0, $base_length);
    $remaining_segment = ai4seo_mb_substr($text, $base_length, 20);

    $append = '';

    if ($remaining_segment !== '') {
        $remaining_segment_trimmed = ltrim($remaining_segment);
        $leading_whitespace_removed = ai4seo_mb_strlen($remaining_segment_trimmed) !== ai4seo_mb_strlen($remaining_segment);

        if ($remaining_segment_trimmed !== '' && preg_match('/^\S{0,20}/u', $remaining_segment_trimmed, $match) && isset($match[0])) {
            $append_segment = $match[0];

            if ($leading_whitespace_removed && $append_segment !== '' && !preg_match('/\s$/u', $base_snippet)) {
                $append = ' ' . $append_segment;
            } else {
                $append = $append_segment;
            }
        }
    }

    $result = $base_snippet . $append;

    return trim($result);
}

// =========================================================================================== \\

/**
 * Function to replace white-label-placeholders
 * @param string $text string with text containing placeholders
 * @return string $text with placeholders replaced
 */
function ai4seo_replace_white_label_placeholders(string $text): string {
    $text = str_replace(
        ["{NAME}", "{VERSION}", "{WEBSITE}"],
        [AI4SEO_PLUGIN_NAME, AI4SEO_PLUGIN_VERSION_NUMBER, AI4SEO_OFFICIAL_WEBSITE],
        $text);

    return $text;
}

// =========================================================================================== \\

/**
 * Returns common placeholder replacements shared across metadata and attachments.
 *
 * @return array
 */
function ai4seo_get_common_placeholder_replacements(): array {
    $website_url = untrailingslashit(home_url());
    $website_url = $website_url ? trim(esc_url_raw($website_url)) : '';

    $website_name = get_bloginfo('name');
    $website_name = is_string($website_name) ? trim(wp_strip_all_tags($website_name)) : '';

    return array(
        'WEBSITE_URL' => $website_url,
        'WEBSITE_NAME' => $website_name,
    );
}

// =========================================================================================== \\

/**
 * Returns placeholder replacements for metadata prefixes and suffixes.
 *
 * @param int    $post_id              The current post ID.
 * @param string $product_price        The WooCommerce product price if available.
 * @param string $product_name         The WooCommerce product name if available.
 *
 * @return array
 */
function ai4seo_get_metadata_placeholder_replacements(int $post_id, string $product_price = '', string $product_name = ''): array {
    $replacements = ai4seo_get_common_placeholder_replacements();

    $replacements['POST_ID'] = (string) absint($post_id);

    $post_type = get_post_type($post_id);

    $product_name_value = '';
    $product_price_value = '';

    if ($post_type === 'product') {
        if ($product_name === '') {
            $product_name = get_the_title($post_id);
        }

        $product_name_value = is_string($product_name) ? trim(wp_strip_all_tags($product_name)) : '';

        if ($product_price !== '') {
            $product_price_value = trim(wp_strip_all_tags($product_price));
        }
    }

    $replacements['PRODUCT_NAME'] = $product_name_value;
    $replacements['PRODUCT_PRICE'] = $product_price_value;

    return $replacements;
}

// =========================================================================================== \\

/**
 * Returns placeholder replacements for attachment prefixes and suffixes.
 *
 * @param int $attachment_post_id The attachment post ID.
 *
 * @return array
 */
function ai4seo_get_attachment_placeholder_replacements(int $attachment_post_id): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(278475537, 'Prevented loop', true);
        return array();
    }

    $replacements = ai4seo_get_common_placeholder_replacements();

    $replacements['POST_ID'] = (string) absint($attachment_post_id);
    $replacements['FILE_NAME'] = '';
    $replacements['FILE_TYPE'] = '';
    $replacements['FILE_SIZE'] = '';
    $replacements['IMAGE_DIMENSIONS'] = '';

    $attached_file_path = get_attached_file($attachment_post_id);
    $pathinfo = array();

    if ($attached_file_path) {
        $pathinfo = pathinfo($attached_file_path);
    } else {
        $attachment_url = wp_get_attachment_url($attachment_post_id);

        if ($attachment_url) {
            $url_path = wp_parse_url($attachment_url, PHP_URL_PATH);

            if ($url_path) {
                $pathinfo = pathinfo($url_path);
            }
        }
    }

    if (!empty($pathinfo['filename'])) {
        $replacements['FILE_NAME'] = trim(sanitize_text_field($pathinfo['filename']));
    }

    if (!empty($pathinfo['extension'])) {
        $replacements['FILE_TYPE'] = strtolower(trim(sanitize_text_field($pathinfo['extension'])));
    }

    if ($attached_file_path && file_exists($attached_file_path)) {
        $file_size_bytes = @filesize($attached_file_path);

        if (is_int($file_size_bytes) || is_float($file_size_bytes)) {
            $file_size_kb = $file_size_bytes / 1024;

            if ($file_size_kb > 0) {
                if ($file_size_kb < 10) {
                    $formatted_file_size = number_format_i18n(round($file_size_kb, 2), 2);
                } else {
                    $formatted_file_size = number_format_i18n(round($file_size_kb));
                }
            } else {
                $formatted_file_size = '0';
            }

            $replacements['FILE_SIZE'] = trim($formatted_file_size . ' KB');
        }
    }

    $attachment_metadata = wp_get_attachment_metadata($attachment_post_id);

    if (is_array($attachment_metadata)
        && !empty($attachment_metadata['width'])
        && !empty($attachment_metadata['height'])
    ) {
        $width = (int) $attachment_metadata['width'];
        $height = (int) $attachment_metadata['height'];

        if ($width > 0 && $height > 0) {
            $replacements['IMAGE_DIMENSIONS'] = $width . 'x' . $height;
        }
    } elseif ($attached_file_path && file_exists($attached_file_path)) {
        $image_size = @getimagesize($attached_file_path);

        if (is_array($image_size) && isset($image_size[0], $image_size[1])) {
            $width = (int) $image_size[0];
            $height = (int) $image_size[1];

            if ($width > 0 && $height > 0) {
                $replacements['IMAGE_DIMENSIONS'] = $width . 'x' . $height;
            }
        }
    }

    return $replacements;
}

// =========================================================================================== \\

/**
 * Replaces supported placeholders in the provided text.
 *
 * @param string $text          The text that may contain placeholders.
 * @param array  $replacements  Map of placeholder => replacement value.
 *
 * @return string
 */
function ai4seo_replace_text_placeholders(string $text, array $replacements): string {
    if ($text === ''
        || (strpos($text, '{') === false
            && strpos($text, '[') === false
            && strpos($text, '%%') === false)
    ) {
        return $text;
    }

    return (string) preg_replace_callback(
        '/\{([A-Z0-9_]+)\}|\[([A-Z0-9_]+)\]|%%([A-Z0-9_]+)%%/i',
        static function ($matches) use ($replacements) {
            $placeholder = '';

            if (!empty($matches[1])) {
                $placeholder = $matches[1];
            } elseif (!empty($matches[2])) {
                $placeholder = $matches[2];
            } elseif (!empty($matches[3])) {
                $placeholder = $matches[3];
            }

            if ($placeholder !== '') {
                $key = strtoupper($placeholder);

                if (array_key_exists($key, $replacements)) {
                    return (string) $replacements[$key];
                }
            }

            return $matches[0];
        },
        $text
    );
}

// =========================================================================================== \\

/**
 * Replaces the [TITLE] placeholder in metadata prefixes or suffixes.
 *
 * @param string $text       Text that may contain the [TITLE] placeholder.
 * @param string $post_title The current post title used as replacement.
 *
 * @return string
 */
function ai4seo_replace_metadata_title_placeholder(string $text, string $post_title): string {
    if ($text === '' || $post_title === '') {
        return $text;
    }

    $contains_title_placeholder = (
        stripos($text, '{title}') !== false
        || stripos($text, '[title]') !== false
        || stripos($text, '%%title%%') !== false
    );

    if (!$contains_title_placeholder) {
        return $text;
    }

    return str_ireplace(
        array('{TITLE}', '[TITLE]', '%%TITLE%%'),
        $post_title,
        $text
    );
}

// =========================================================================================== \\

/**
 * Checks whether the provided text contains WooCommerce product placeholders.
 *
 * @param string $text The text to inspect.
 *
 * @return bool
 */
function ai4seo_text_contains_product_placeholder(string $text): bool {
    if ($text === '') {
        return false;
    }

    return (bool) preg_match(
        '/\{PRODUCT_(?:NAME|PRICE)\}|\[PRODUCT_(?:NAME|PRICE)\]|%%PRODUCT_(?:NAME|PRICE)%%/i',
        $text
    );
}

// =========================================================================================== \\

/**
 * Remove TranslatePress tags and wrappers from a string.
 *
 * Example input:
 * "#!trpst#trp-gettext#Metadata Editor#!trpen#Manage metadata for Stuffed peppers (#35432)#!trpst#"
 * Output:
 * "Metadata Editor Manage metadata for Stuffed peppers (#35432)"
 *
 * @param string $input
 * @return string
 */
function ai4seo_remove_translatepress_tags(string $input ): string {
    // Replace TranslatePress wrapped text with its inner content
    $clean = preg_replace_callback(
        '/#!trpst#trp-gettext#(.*?)#!trpen#/us',
        function ( $m ) {
            return ' ' . $m[1] . ' ';
        },
        $input
    );

    // Handle inline variant like #trp-gettext data-trpgettextoriginal=157#!trpen#
    $clean = preg_replace( '/#trp-gettext[^#]*#!trpen#/us', ' ', $clean );

    // Remove any remaining TranslatePress markers
    $clean = preg_replace( '/#!?trp[a-zA-Z0-9_\-\s="]+#?/', ' ', $clean );

    // Normalize spaces and decode entities
    //$clean = html_entity_decode( $clean, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $clean = trim( preg_replace( '/\s+/', ' ', $clean ) );

    return $clean;
}


// =========================================================================================== \\

/**
 * Function to modify plugin-details for white-label-settings
 * @param array $all_plugins array with all plugins
 * @return array $all_plugins edited array with all plugins
 */
function ai4seo_modify_plugin_details_for_white_label(array $all_plugins): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(651264325, 'Prevented loop', true);
        return array();
    }

    // Define variable for the plugin-file
    $plugin_file = ai4seo_get_plugin_basename();

    if (!isset($all_plugins[$plugin_file])) {
        // If the plugin-file is not found in $all_plugins, return the original array
        return $all_plugins;
    }

    // APPLYING WHITE-LABEL SETTINGS
    $setting_enable_white_label = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_WHITE_LABEL);

    if ($setting_enable_white_label) {
        // Define variables for plugin-name and plugin-description based on settings
        $new_plugin_name = ai4seo_get_setting(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME);
        $new_plugin_description = ai4seo_get_setting(AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION);

        // Make sure that plugin-name and plugin-description could be found and have content
        if ($new_plugin_name && $new_plugin_description) {
            // Replace plugin-name and plugin-description based on settings
            $all_plugins[$plugin_file]["Name"] = wp_unslash(ai4seo_mb_substr($new_plugin_name, 0, 100));
            $all_plugins[$plugin_file]["Description"] = stripslashes(ai4seo_mb_substr($new_plugin_description, 0, 140));
        }
    }

    // APPLYING INCOGNITO SETTINGS
    // check for function get_current_user_id()
    if (!function_exists("get_current_user_id")) {
        return $all_plugins;
    }

    $current_user_id = get_current_user_id();

    // Define variables for the incognito-setting
    $setting_enable_incognito_mode = ai4seo_is_incognito_mode_enabled();
    $setting_incognito_mode_user_id = ai4seo_get_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID);
    $visible_to_anyone = !$setting_enable_incognito_mode || ($setting_incognito_mode_user_id == AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_INCOGNITO_MODE_USER_ID]);
    $only_visible_to_current_user = $setting_enable_incognito_mode && !$visible_to_anyone && $setting_incognito_mode_user_id == $current_user_id;

    // Check incognito-setting and incognito user-id
    if ($only_visible_to_current_user) {
        // Add a note about the incognito mode plugin meta
        add_filter("plugin_row_meta", "ai4seo_add_incognito_note_to_plugin_meta", 10, 4);
    } else if (!$visible_to_anyone || $setting_enable_white_label) {
        // Remove plugin-meta from plugin details
        add_filter("plugin_row_meta", "ai4seo_remove_plugin_meta", 10, 4);
    }

    // Return array with all plugins
    return $all_plugins;
}

// =========================================================================================== \\

/**
 * Function to remove plugin meta from plugins-list
 * @param array $plugin_meta An array of the plugin’s metadata, including the version, author, author URI, and plugin URI.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array $plugin_data An array of plugin data.
 * @param string $status Status filter currently applied to the plugin list.
 * @return array $plugin_meta - an array with the found meta tags
 */
function ai4seo_remove_plugin_meta(array $plugin_meta, string $plugin_file, array $plugin_data, string $status): array {
    // Check if slug could be found and if it matches the plugin
    if (isset($plugin_data["slug"]) && $plugin_data["slug"] == AI4SEO_PLUGIN_IDENTIFIER) {
        $plugin_meta[] = esc_html__("Version", "ai-for-seo") . ": " . AI4SEO_PLUGIN_VERSION_NUMBER;
    }

    return $plugin_meta;
}

// =========================================================================================== \\

/**
 * Function to add a note about the incognito mode plugin meta
 * @param array $plugin_meta An array of the plugin’s metadata, including the version, author, author URI, and plugin URI.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array $plugin_data An array of plugin data.
 * @param string $status Status filter currently applied to the plugin list.
 * @return array $plugin_meta - an array with the found meta tags
 */
function ai4seo_add_incognito_note_to_plugin_meta(array $plugin_meta, string $plugin_file, array $plugin_data, string $status): array {
    // Check if slug could be found and if it matches the plugin
    if (isset($plugin_data["slug"]) && $plugin_data["slug"] == AI4SEO_PLUGIN_IDENTIFIER) {
        $plugin_meta[] = esc_html__("(Incognito Mode: This info is only visible to you)", "ai-for-seo");
    }

    return $plugin_meta;
}

// =========================================================================================== \\

/**
 * Function to retrieve specific meta tags from html
 * @param string $head_html the html content of the head
 * @return array $found_meta_tags - an array with the found meta tags
 */
function ai4seo_get_meta_tags_from_html(string $head_html): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(764731280, 'Prevented loop', true);
        return array();
    }

    if (!defined('AI4SEO_METADATA_DETAILS')) {
        return array();
    }

    // Remove <script>, <style>, and <link> tags and their content
    $head_html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $head_html);
    $head_html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $head_html);
    $head_html = preg_replace('/<link\b[^>]*>/i', '', $head_html);

    // Remove <![CDATA[ sections
    $head_html = preg_replace('/<!\[CDATA\[.*?\]\]>/s', '', $head_html);

    // Remove HTML comments
    $head_html = preg_replace('/<!--.*?-->/s', '', $head_html);

    // Trim
    $head_html = trim($head_html);

    // Workaround: Replace line breaks with placeholders
    $head_html = preg_replace('/\r\n/', '#AI4SEO#LBRN#', $head_html);
    $head_html = preg_replace('/\n/', '#AI4SEO#LBN#', $head_html);

    // add line breaks after each closing tag like </title>
    $head_html = preg_replace('/<\/[^>]+>/', "$0\n", $head_html);

    // add line breaks after each cosing single tag like <meta ... />
    $head_html = preg_replace('/<[^>]+\/>/', "$0\n", $head_html);

    // add line breaks between two tags
    $head_html = preg_replace('/>\s*</', ">\n<", $head_html);
    $head_html = preg_replace('/>(#AI4SEO#LBRN#|#AI4SEO#LBN#|\s)+</', ">\n<", $head_html);

    // generate array splitting by line breaks
    $head_tags = explode("\n", $head_html);

    // go through each and analyze it's content
    $found_meta_tags = array();

    foreach ($head_tags as $head_tag) {
        if (!$head_tag) {
            continue;
        }

        // trim
        $head_tag = trim($head_tag);

        // check for charset meta tag
        if (preg_match('/<meta\s+[^>]*charset\s*=\s*["\'][^"\']+["\'][^>]*>/i', $head_tag)) {
            $found_meta_tags["charset"] = array (
                "raw-html" => trim(ai4seo_remove_header_line_break_placeholders($head_tag)),
                "content" => "charset",
            );
        }

        // check for viewport meta tag
        if (preg_match('/<meta\s+[^>]*name\s*=\s*["\']viewport["\'][^>]*>/i', $head_tag)) {
            $found_meta_tags["viewport"] = array (
                "raw-html" => trim(ai4seo_remove_header_line_break_placeholders($head_tag)),
                "content" => "viewport",
            );
        }

        // go through each metadata field and check if the meta-tag-regex matches
        foreach (AI4SEO_METADATA_DETAILS AS $this_metadata_identifier => $this_metadata_field_details) {
            $this_meta_tag_regex = $this_metadata_field_details["meta-tag-regex"] ?? "";
            $this_meta_tag_regex_match_index = $this_metadata_field_details["meta-tag-regex-match-index"] ?? 0;

            if (!$this_meta_tag_regex || !$this_meta_tag_regex_match_index) {
                continue;
            }

            if (!preg_match($this_meta_tag_regex, $head_tag, $this_meta_tag_regex_matches)) {
                continue;
            }

            if (!isset($this_meta_tag_regex_matches[$this_meta_tag_regex_match_index])) {
                continue;
            }

            // Workaround: replace line break placeholders back
            $this_meta_tag_regex_matches[0] = trim(ai4seo_remove_header_line_break_placeholders($this_meta_tag_regex_matches[0]));
            $this_meta_tag_regex_matches[$this_meta_tag_regex_match_index] = trim(ai4seo_remove_header_line_break_placeholders($this_meta_tag_regex_matches[$this_meta_tag_regex_match_index]));

            $found_meta_tags[$this_metadata_identifier][] = array (
                "raw-html" => $this_meta_tag_regex_matches[0],
                "content" => $this_meta_tag_regex_matches[$this_meta_tag_regex_match_index],
            );
        }
    }

    return $found_meta_tags;
}

// =========================================================================================== \\

/**
 * Removes line break placeholders from the given string
 * @param $string - the string to remove the line break placeholders from
 * @return string - the string without line break placeholders
 */
function ai4seo_remove_header_line_break_placeholders(string $string): string {
    return str_replace(array('#AI4SEO#LBRN#', '#AI4SEO#LBN#'), array("\r\n", "\n"), $string);
}

// =========================================================================================== \\

function ai4seo_handle_posts_to_be_analyzed() {
    // Make sure that the user is allowed to use this plugin
    if (!ai4seo_can_manage_this_plugin()) {
        return;
    }

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // get all posts that need to be analyzed
    $posts_to_be_analyzed = ai4seo_get_post_ids_from_option(AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME);

    // if there are no posts to be analyzed, return
    if (!$posts_to_be_analyzed) {
        return;
    }

    // get the first post to be analyzed
    $post_id = array_shift($posts_to_be_analyzed);

    // check if the post id is numeric
    if (is_numeric($post_id)) {
        // analyze the post
        ai4seo_analyze_post($post_id);
    }

    // update the option
    ai4seo_remove_post_ids_from_option(AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME, $post_id);
}

// =========================================================================================== \\

/**
 * Gatekeeper for AI4SEO AJAX requests.
 *
 * @return void
 */
function ai4seo_on_ajax_action() {
    if ( wp_doing_ajax() === false ) {
        return;
    }

    $action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

    if ( $action === '' || strpos( $action, 'ai4seo_' ) !== 0 ) {
        return;
    }

    // we have an AJAX request for our plugin, let's run the security gate
    ai4seo_ajax_security_gate();
}

// =========================================================================================== \\

/**
 * Validates nonce and permissions for AI4SEO AJAX requests.
 *
 * Sends an AJAX error and exits on failure.
 *
 * @return void
 */
function ai4seo_ajax_security_gate() {
    $ajax_nonce = '';

    if ( isset( $_REQUEST[ AI4SEO_GLOBAL_NONCE_IDENTIFIER ] ) ) {
        $ajax_nonce = sanitize_text_field( wp_unslash( $_REQUEST[ AI4SEO_GLOBAL_NONCE_IDENTIFIER ] ) );
    } elseif ( isset( $_REQUEST['security'] ) ) {
        $ajax_nonce = sanitize_text_field( wp_unslash( $_REQUEST['security'] ) );
    }

    if ( $ajax_nonce === '' ) {
        ai4seo_send_ajax_error(
            esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
            401271224
        );
    }

    if ( wp_verify_nonce( $ajax_nonce, AI4SEO_GLOBAL_NONCE_IDENTIFIER ) === false ) {
        ai4seo_send_ajax_error(
            esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
            411271224
        );
    }

    if ( ai4seo_can_manage_this_plugin() === false ) {
        ai4seo_send_ajax_error(
            esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' ),
            11420725
        );
    }

    $GLOBALS["ai4seo_ajax_nonce"] = $ajax_nonce;
}

// =========================================================================================== \\

/**
 * Checks if the plugin performance analysis should be run
 */
function ai4seo_check_for_performance_analysis() {
    global $wpdb;

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // compare cached and real count of posts
    $last_known_num_posts_table_entries = (int) ai4seo_read_environmental_variable(
        AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES
    );

    if (ai4seo_is_environmental_variable_cache_available(AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES)) {
        $current_num_posts_table_entries = (int) ai4seo_read_environmental_variable(
            AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES
        );
    } else {
        $current_num_posts_table_entries = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts}");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321671, 'Database error: ' . $wpdb->last_error, true);
            return;
        }

        ai4seo_update_environmental_variable(
            AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES,
            $current_num_posts_table_entries,
            true,
            HOUR_IN_SECONDS
        );
    }

    if ($last_known_num_posts_table_entries !== $current_num_posts_table_entries) {
        ai4seo_analyze_plugin_performance();
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES, $current_num_posts_table_entries);
        return;
    }

    $num_batches_needed = ceil($current_num_posts_table_entries / AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE);

    if ($num_batches_needed <= 1) {
        ai4seo_analyze_plugin_performance();
        return;
    }

    $last_performance_analysis_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME);
    $analyze_performance_interval = AI4SEO_ANALYZE_PERFORMANCE_INTERVAL + ($num_batches_needed * 60); # add extra time based on number of batches needed

    // mainly useful if cron job didn't run for a while or on first plugin activation
    if ($last_performance_analysis_time <= time() - $analyze_performance_interval) {
        ai4seo_analyze_plugin_performance();
    }
}

// =========================================================================================== \\

/**
 * Function to init the RobHub Account by syncing it eventually
 */
function ai4seo_check_for_robhub_account_sync(): void {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED
    $is_account_synced = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED);

    if (!$is_account_synced) {
        ai4seo_sync_robhub_account('not_yet_synced');
        return;
    }

    // check last sync timestamp
    $last_account_sync = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC);

    if ($last_account_sync < time() - ai4seo_robhub_api()::ACCOUNT_SYNC_INTERVAL) {
        ai4seo_sync_robhub_account('regular_interval');
        return;
    }

    // if next free credits timestamp is set and in the past, we need to sync the account again
    $next_free_credits_timestamp = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP);

    if ($next_free_credits_timestamp && $next_free_credits_timestamp < time()) {
        ai4seo_sync_robhub_account('next_free_credits_passed');
        return;
    }

    // if the credits balance is below 100 AND AI4SEO_SETTING_PAYG_ENABLED is true, we need to check for client's payment
    // dashboard only
    $is_payg_enabled = (bool) ai4seo_get_setting(AI4SEO_SETTING_PAYG_ENABLED);
    $credits_balance = ai4seo_robhub_api()->get_credits_balance();

    if ($is_payg_enabled && $credits_balance < 100) {
        $now = time();
        $did_trigger_payg_waiting_for_payment_sync = false;

        // Track the first timestamp when this low-credits + PAYG state started.
        $payg_low_credits_first_occurrence_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME);

        if (!$payg_low_credits_first_occurrence_time) {
            $payg_low_credits_first_occurrence_time = $now;
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME, $payg_low_credits_first_occurrence_time);
        }

        // Hard-stop this sync reason after 60 minutes from first occurrence.
        if ($now - $payg_low_credits_first_occurrence_time <= HOUR_IN_SECONDS) {
            $payg_low_credits_last_sync_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME);

            // Rate-limit to at most once every 5 minutes while inside the 60-minute window.
            if (!$payg_low_credits_last_sync_time || $payg_low_credits_last_sync_time <= $now - (5 * MINUTE_IN_SECONDS)) {
                ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME, $now);
                ai4seo_sync_robhub_account('payg_waiting_for_payment');
                $did_trigger_payg_waiting_for_payment_sync = true;
            }
        }

        // Return only when this branch actually triggered the PAYG waiting-for-payment sync.
        if ($did_trigger_payg_waiting_for_payment_sync) {
            return;
        }
    } else {
        // Reset tracking once the low-credits + PAYG condition is no longer active.
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME, 0);
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME, 0);
    }

    // if the environmental variable AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME is set and in the last 120 minutes, we need to sync the account again
    $just_purchased_something_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME);

    if ($just_purchased_something_time && $just_purchased_something_time > time() - 7200 && $credits_balance < 100) {
        ai4seo_sync_robhub_account('waiting_for_payment');
        return;
    }
}

// =========================================================================================== \\
/**
 * Function to sync with client's RobHub Account
 * @param bool $allow_notification_force - if true, we will force a notification to be sent in case of an error
 * @param string $sync_reason - reason for the sync (for logging purposes)
 * @return bool - true if the RobHub Account was synced, false on error
 */
function ai4seo_sync_robhub_account(string $sync_reason = "unknown", bool $allow_notification_force = false): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 10)) {
        ai4seo_debug_message(461426226, 'Prevented loop', true);
        return false;
    }

    $api_response = ai4seo_robhub_api()->sync_account($sync_reason);

    // in case we have an error, we try to push a notification
    ai4seo_check_for_robhub_account_error_notification($api_response, true);

    // Interpret response
    if (!ai4seo_robhub_api()->was_call_successful($api_response) || !isset($api_response["data"]) || !is_array($api_response["data"]) || !$api_response["data"]) {
        ai4seo_debug_message(451426226, 'Account sync failed or returned invalid data', true);
        return false;
    }

    $synced_account_data = $api_response["data"];

    $last_website_toc_and_pp_update_time = (int) ($synced_account_data["last_terms_update_time"] ?? false);

    // update the last website's ToC and PP update time if it is not set
    if ($last_website_toc_and_pp_update_time && $last_website_toc_and_pp_update_time != ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME)) {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME, $last_website_toc_and_pp_update_time);
    }

    // compare settings and environmental variables
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING, (bool) ($synced_account_data["has_purchased_something"] ?? false));

    // Sync Pay-As-You-Go settings
    if (isset($synced_account_data["is_payg_enabled"])) {
        ai4seo_update_setting(AI4SEO_SETTING_PAYG_ENABLED, (bool) $synced_account_data["is_payg_enabled"]);
    }

    if (isset($synced_account_data["stripe_price_id"]) && $synced_account_data["stripe_price_id"]) {
        ai4seo_update_setting(AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID, sanitize_text_field($synced_account_data["stripe_price_id"]));
    }

    if (isset($synced_account_data["payg_daily_budget"]) && is_numeric($synced_account_data["payg_daily_budget"])) {
        ai4seo_update_setting(AI4SEO_SETTING_PAYG_DAILY_BUDGET, (int) $synced_account_data["payg_daily_budget"]);
    }

    if (isset($synced_account_data["payg_monthly_budget"]) && is_numeric($synced_account_data["payg_monthly_budget"])) {
        ai4seo_update_setting(AI4SEO_SETTING_PAYG_MONTHLY_BUDGET, (int) $synced_account_data["payg_monthly_budget"]);
    }

    if (isset($synced_account_data["payg_status"]) && in_array($synced_account_data["payg_status"], AI4SEO_ALLOWED_PAYG_STATUS)) {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS, sanitize_key($synced_account_data["payg_status"]));

        ai4seo_check_for_payg_status_errors($synced_account_data["payg_status"]);
    } else {
        ai4seo_delete_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS);
    }

    // claimed_feedback_offer
    if (isset($synced_account_data["claimed_feedback_offer"]) && $synced_account_data["claimed_feedback_offer"]) {
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER, true);
    } else {
        ai4seo_delete_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER);
    }

    // preferred_currency
    if (isset($synced_account_data["preferred_currency"]) && $synced_account_data["preferred_currency"]) {
        ai4seo_update_setting(AI4SEO_SETTING_PREFERRED_CURRENCY, $synced_account_data["preferred_currency"]);
    }

    // in case there is a new plugin version available, we need to check for it
    ai4seo_check_for_plugin_update_available($synced_account_data["latest_product_version"] ?? "", true);

    // discount
    if (isset($synced_account_data["discount"]) && is_array($synced_account_data["discount"])) {
        $discount = $synced_account_data["discount"];

        if (isset($discount["name"]) && $discount["name"] && isset($discount["percentage"]) && is_numeric($discount["percentage"])) {
            // sanitize integers
            $discount["percentage"] = (int) $discount["percentage"];

            if (isset($discount["expire_in"])) {
                $discount["expire_in"] = (int) $discount["expire_in"];
            }

            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT, $discount);
            ai4seo_check_discount_notification($discount, $allow_notification_force);
        }
    } else {
        ai4seo_delete_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT);
        ai4seo_remove_notification('discount');
    }

    // notifications
    if (isset($synced_account_data["notifications"]) && is_array($synced_account_data["notifications"])) {
        $notifications = $synced_account_data["notifications"];

        foreach ($notifications AS $notification_index => $notification) {
            if (!isset($notification["message"]) || !$notification["message"]) {
                continue;
            }

            // set $message and unset it from the notification array
            $message = $notification["message"];
            unset($notification["message"]);

            // set $force and unset it from the notification array
            if ($allow_notification_force) {
                $force = isset($notification["force"]) && (bool) $notification["force"];
            } else {
                $force = false;
            }

            unset($notification["force"]);

            ai4seo_push_notification($notification_index, $message, $force, $notification);
        }
    }

    // auto_retry_failed
    if (isset($synced_account_data['auto_retry_failed']) && $synced_account_data['auto_retry_failed']) {
        // reset AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME and AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME
        ai4seo_delete_option(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);
        ai4seo_delete_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

        // Refresh the generation status summary
        ai4seo_try_start_posts_table_analysis(true);
    }

    return true;
}


// endregion
// ___________________________________________________________________________________________ \\
// region RIGHTS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Retrieve an array of all user-roles that are currently available
 * @return array An array of all user-roles
 */
function ai4seo_get_all_possible_user_roles(): array {
    global $ai4seo_fallback_allowed_user_roles;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(874528039, 'Prevented loop', true);
        return $ai4seo_fallback_allowed_user_roles;
    }

    if (!function_exists('wp_roles')) {
        ai4seo_debug_message(49176824, 'wp_roles() does not exist', true);
        return $ai4seo_fallback_allowed_user_roles;
    }

    // Attempt to get WordPress roles
    $wp_roles = wp_roles();

    // Check if wp_roles() returned a valid object
    if (!is_object($wp_roles) || !method_exists($wp_roles, 'get_names')) {
        ai4seo_debug_message(50176824, 'wp_roles() did not return a valid object', true);
        return $ai4seo_fallback_allowed_user_roles;
    }

    // Get the array of role names
    $not_sanitized_user_roles = $wp_roles->get_names();

    // Check if roles array is not empty
    if (empty($not_sanitized_user_roles)) {
        ai4seo_debug_message(51176824, 'wp_roles() did not return any roles', true);
        return $ai4seo_fallback_allowed_user_roles;
    }

    // sanitize and filter based on 'edit_post' capability
    $sanitized_user_roles = array();

    foreach ($not_sanitized_user_roles as $user_role_identifier => $user_role) {
        // Sanitize identifiers
        $user_role_identifier = sanitize_key($user_role_identifier);
        $user_role = sanitize_text_field($user_role);

        // Check if the role has the 'edit_post' capability
        $role_object = get_role($user_role_identifier);

        if ($role_object && $role_object->has_cap('edit_posts')) {
            $sanitized_user_roles[$user_role_identifier] = $user_role;
        }
    }

    ai4seo_remove_forbidden_allowed_user_roles($sanitized_user_roles);

    // add administrator role if it's not already in the array
    if (!isset($sanitized_user_roles["administrator"])) {
        $sanitized_user_roles["administrator"] = "Administrator";
    }

    return $sanitized_user_roles;
}

// =========================================================================================== \\

/**
 * Removes forbidden user roles from the given user roles array
 * @param $user_roles
 * @return void
 */
function ai4seo_remove_forbidden_allowed_user_roles(&$user_roles) {
    global $ai4seo_forbidden_allowed_user_roles;

    if (!is_array($user_roles)) {
        return;
    }

    foreach ($ai4seo_forbidden_allowed_user_roles as $user_role) {
        unset($user_roles[$user_role]);
    }
}


// endregion
// ___________________________________________________________________________________________ \\
// region PLANS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_available_plans(): array {
    return array(
        "free" => array(
            'name' => esc_html__('Free', 'ai-for-seo'),
            'credits' => 100,
        ),
        "s" => array(
            'name' => esc_html__('Basic', 'ai-for-seo'),
            'credits' => 500,
        ),
        "m" => array(
            'name' => esc_html__('Pro', 'ai-for-seo'),
            'credits' => 1500,
        ),
        "l" => array(
            'name' => esc_html__('Premium', 'ai-for-seo'),
            'credits' => 5000,
        ),
    );
}

/**
 * Function to retrieve the given plans amount of credits
 * @param $plan
 * @return int
 */
function ai4seo_get_plan_credits($plan): int {
    $available_plans = ai4seo_get_available_plans();

    return $available_plans[$plan]["credits"] ?? $available_plans["free"]["credits"];
}

// =========================================================================================== \\

/**
 * Return the name of the given plan
 * @param $plan
 * @return string
 */
function ai4seo_get_plan_name($plan): string {
    $available_plans = ai4seo_get_available_plans();

    return $available_plans[$plan]["name"] ?? $available_plans["free"]["name"];
}

// =========================================================================================== \\

/**
 * Determine whether the current account has at least the required plan level.
 *
 * Accepts plan identifiers (free, s, m, l) or their textual equivalents (basic, pro, premium).
 *
 * @since 2.3.0
 *
 * @param string $required_plan Plan identifier or name to compare against.
 *
 * @return bool True when the user's subscription meets or exceeds the requirement.
 */
function ai4seo_user_has_at_least_plan(string $required_plan): bool {
    global $ai4seo_user_has_at_least_plan;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(988736227, 'Prevented loop', true);
        return false;
    }

    if (isset($ai4seo_user_has_at_least_plan[$required_plan])) {
        return $ai4seo_user_has_at_least_plan[$required_plan];
    }

    $current_subscription = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION);
    $current_plan = $current_subscription["plan"] ?? "free";
    $available_plans = ai4seo_get_available_plans();

    // fetch the plan indexes and compare them
    $current_plan_index = (int) array_search($current_plan, array_keys($available_plans), true);

    // build $ai4seo_user_has_at_least_plan
    foreach ($available_plans AS $this_plan_identifier => $this_plan_details) {
        $this_plan_index = (int) array_search($this_plan_identifier, array_keys($available_plans), true);
        $ai4seo_user_has_at_least_plan[$this_plan_identifier] = $current_plan_index >= $this_plan_index;
    }

    return $ai4seo_user_has_at_least_plan[$required_plan];
}

// =========================================================================================== \\

function ai4seo_get_plan_badge($plan): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(308030688, 'Prevented loop', true);
        return '';
    }

    $css_class = 'ai4seo-plan-badge';
    $user_has_at_least_this_plan = ai4seo_user_has_at_least_plan($plan);
    $onclick = '';

    switch ($plan) {
        case 'free':
            $css_class .= ' ai4seo-plan-badge-free';
            $badge_label = esc_html__("Free", "ai-for-seo");
            $alt_text = esc_html__("Free plan", "ai-for-seo");
            break;
        case 's':
            $css_class .= ' ai4seo-plan-badge-basic';
            $badge_label = esc_html__("Basic", "ai-for-seo");

            if (!$user_has_at_least_this_plan) {
                $alt_text = esc_html__("You need the Basic Plan or higher to use this feature", "ai-for-seo");
            }

            break;
        case 'm':
            $css_class .= ' ai4seo-plan-badge-pro';
            $badge_label = esc_html__("Pro", "ai-for-seo");

            if (!$user_has_at_least_this_plan) {
                $alt_text = esc_html__("You need the Pro Plan or higher to use this feature", "ai-for-seo");
            }
            break;
        case 'l':
            $css_class .= ' ai4seo-plan-badge-premium';

            $badge_label = esc_html__("Premium", "ai-for-seo");

            if (!$user_has_at_least_this_plan) {
                $alt_text = esc_html__("You need the Premium Plan to use this feature", "ai-for-seo");
            }
            break;
        default:
            return '';
    }

    if ($user_has_at_least_this_plan) {
        $alt_text = esc_html__("You can use this feature with your current plan", "ai-for-seo");
    } else {
        $badge_label .= ' - ' . esc_html__("Upgrade now", "ai-for-seo");
        $css_class .= ' ai4seo-clickable';
        $onclick = 'ai4seo_open_get_more_credits_modal();';
    }

    $output = "<span class='" . esc_attr($css_class) . "' onclick='" . esc_attr($onclick) . "'>";
        $output .= ai4seo_get_svg_tag('crown', $alt_text, 'ai4seo-plan-badge-icon');
        $output .= esc_html($badge_label);
    $output .= "</span>";

    return $output;
}


// endregion
// ___________________________________________________________________________________________ \\
// region UTILITY / HELPER FUNCTIONS ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to return the robhub api communicator
 * @return Ai4Seo_RobHubApiCommunicator|null The robhub api communicator
 */
function ai4seo_robhub_api($init_only = false): ?Ai4Seo_RobHubApiCommunicator {
    global $ai4seo_robhub_api;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(232093142, 'Prevented loop', true);
        return null;
    }

    // init the robhub api communicator if not already done
    if (!$ai4seo_robhub_api instanceof Ai4Seo_RobHubApiCommunicator) {
        $ai4seo_robhub_api_path = ai4seo_get_includes_api_path('class-robhub-api-communicator.php');

        if (!file_exists($ai4seo_robhub_api_path)) {
            if ($init_only) {
                return null;
            }

            ai4seo_debug_message(174300405, 'RobHub API communicator file missing at ' . $ai4seo_robhub_api_path, true);
            throw new RuntimeException('RobHub API communicator file missing.');
        }

        require_once $ai4seo_robhub_api_path;

        if (!class_exists('Ai4Seo_RobHubApiCommunicator')) {
            if ($init_only) {
                return null;
            }

            ai4seo_debug_message(214019245, 'Failed to load Ai4Seo_RobHubApiCommunicator from ' . $ai4seo_robhub_api_path, true);
            throw new RuntimeException('Ai4Seo_RobHubApiCommunicator class not found after include.');
        }

        $ai4seo_robhub_api = new Ai4Seo_RobHubApiCommunicator();
        $ai4seo_robhub_api->set_environmental_variables_option_name(AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME);
        $product_activation_time = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME);
        $ai4seo_robhub_api->set_product_parameters('ai4seo', AI4SEO_PLUGIN_VERSION_NUMBER, $product_activation_time);
        $does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();
        $ai4seo_robhub_api->set_does_user_need_to_accept_tos_toc_and_pp($does_user_need_to_accept_tos_toc_and_pp);
        $ai4seo_robhub_api->is_initialized = true;
    }

    return $ai4seo_robhub_api;
}

// =========================================================================================== \\

/**
 * Return a fully sanitized array, using custom sanitize functions for both keys and values.
 *
 * @param array|string $data The array or value to be sanitized.
 * @param string $sanitize_value_function_name The custom sanitize function for the values (default: sanitize_text_field).
 * @param string $sanitize_key_function_name The custom sanitize function for the keys (default: sanitize_key).
 * @return array|string The sanitized array or value.
 */
function ai4seo_deep_sanitize($data, string $sanitize_value_function_name = 'sanitize_text_field', string $sanitize_key_function_name = 'sanitize_key') {
    if (ai4seo_prevent_loops(__FUNCTION__, 100, 99999)) {
        ai4seo_debug_message(549978452, 'Prevented loop', true);
        return is_array($data) ? array() : '';
    }

    if (!ai4seo_is_function_usable($sanitize_value_function_name)) {
        ai4seo_debug_message(54103126, 'Value sanitize function ' . $sanitize_value_function_name . ' is not usable', true);
        return is_array($data) ? array() : '';
    }

    if (is_array($data)) {
        $sanitized_data = array();
        foreach ($data as $key => $value) {
            // Sanitize the key using the key sanitize function
            $sanitized_key = $sanitize_key_function_name($key);

            // Recursively sanitize the value if it's an array, or sanitize the value using the value sanitize function
            if (is_array($value)) {
                $sanitized_data[$sanitized_key] = ai4seo_deep_sanitize($value, $sanitize_value_function_name, $sanitize_key_function_name);
            } else {
                if (is_bool($value)) {
                    $sanitized_data[$sanitized_key] = $value;
                } else {
                    $sanitized_data[$sanitized_key] = $sanitize_value_function_name($value);
                }
            }
        }
        return $sanitized_data;
    } else {
        if (is_bool($data)) {
            return $data;
        }

        // If it's not an array, sanitize the value directly
        return $sanitize_value_function_name($data);
    }
}

// =========================================================================================== \\

/**
 * Function to check whether the current user is allowed to use this plugin
 * @return bool
 */
function ai4seo_can_manage_this_plugin(): bool {
    global $ai4seo_can_manage_this_plugin;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(472226712, 'Prevented loop', true);
        return false;
    }

    // use cache if available
    if ($ai4seo_can_manage_this_plugin !== null) {
        return $ai4seo_can_manage_this_plugin;
    }

    // check if is_user_logged_in() is defined
    if (!function_exists('is_user_logged_in')) {
        return false;
    }

    if (!function_exists('get_current_user_id')) {
        return false;
    }

    if (!function_exists('wp_get_current_user')) {
        return false;
    }

    // Check if the current user is logged in
    if (!is_user_logged_in()) {
        return false;
    }

    // Define variables for the incognito-setting
    $ai4seo_setting_enable_incognito_mode = ai4seo_is_incognito_mode_enabled();
    $ai4seo_setting_incognito_mode_user_id = ai4seo_get_setting(AI4SEO_SETTING_INCOGNITO_MODE_USER_ID);
    $current_user_id = get_current_user_id();

    // Check incognito-setting and incognito user-id
    if ($ai4seo_setting_enable_incognito_mode && $ai4seo_setting_incognito_mode_user_id != AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_INCOGNITO_MODE_USER_ID]
        && $ai4seo_setting_incognito_mode_user_id != $current_user_id) {
        return false;
    }

    // if we are here, we can assume the outcome of this function can be cached
    // (before this point, WordPress might not be fully loaded)
    $ai4seo_can_manage_this_plugin = false;

    // Define variable for the allowed user-roles based on plugin-settings
    $allowed_user_roles = ai4seo_get_setting(AI4SEO_SETTING_ALLOWED_USER_ROLES);

    if (!$allowed_user_roles || !is_array($allowed_user_roles)) {
        return false;
    }

    // Get the details of the current user
    $user = wp_get_current_user();

    // Stop script if the current user or the roles of the current user could not be read
    if (!$user || !isset($user->roles) || !is_array($user->roles)) {
        return false;
    }

    // Loop through allowed roles and check if roles apply to current user
    foreach ($allowed_user_roles as $allowed_user_role) {
        // Check if the user has this allowed role
        if (in_array($allowed_user_role, (array) $user->roles)) {
            $ai4seo_can_manage_this_plugin = true;
            return true;
        }
    }

    return false;
}

// =========================================================================================== \\

/**
 * Runs a callback while ignore_user_abort() is forced to true (unless WP-Cron already handles it).
 *
 * @param callable $callback             Callback to execute.
 * @param array    $callback_arguments   Arguments passed to the callback.
 * @param bool     $skip_for_cron_calls  Skip toggling ignore_user_abort() when running in WP-Cron.
 *
 * @return mixed The callback result.
 */
function ai4seo_run_with_ignore_user_abort(callable $callback, array $callback_arguments = array(), bool $skip_for_cron_calls = true) {
    $previous_ignore_user_abort_state = null;
    $should_restore_ignore_user_abort = false;

    if (!$skip_for_cron_calls || !wp_doing_cron()) {
        $previous_ignore_user_abort_state = ignore_user_abort(true);
        $should_restore_ignore_user_abort = true;
    }

    try {
        return call_user_func_array($callback, $callback_arguments);
    } finally {
        if ($should_restore_ignore_user_abort) {
            ignore_user_abort($previous_ignore_user_abort_state);
        }
    }
}

// =========================================================================================== \\

/**
 * Function to prevent recursive loops
 * @param string $function_name The name of the function to check
 * @param int $max_depth The maximum depth of recursion allowed (default 1, min 1)
 * @param int $max_calls The maximum number of calls allowed globally (default 99999, min 1)
 * @return bool True if the loop should be prevented, false otherwise
 */
function ai4seo_prevent_loops(string $function_name, int $max_depth = 1, int $max_calls = 22222): bool {
    static $call_counts = [];

    if ($max_depth < 1) {
        $max_depth = 1;
    }

    if ($max_calls < 1) {
        $max_calls = 1;
    }

    // Initialize call count if not exists
    if (!isset($call_counts[$function_name])) {
        $call_counts[$function_name] = 0;
    }

    // Increment global call count
    $call_counts[$function_name]++;

    // Check max calls
    if ($call_counts[$function_name] > $max_calls) {
        return true;
    }

    // if $call_counts[$function_name] is less than $max_depth, we cannot have reached max depth yet
    if ($call_counts[$function_name] <= $max_depth) {
        return false;
    }

    // Check recursion depth
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $depth = 0;

    // Iterate through backtrace to count occurrences of the function
    foreach ($backtrace as $trace) {
        if (isset($trace['function']) && $trace['function'] === $function_name) {
            $depth++;
        }
    }

    // The current call is included in the backtrace, so depth is at least 1.
    // If max_depth is 1, we want to prevent ANY recursion (i.e., if depth > 1).
    // If max_depth is 2, we allow 2 recursive call (depth 2).
    // So we return true if depth > $max_depth

    if ($depth > $max_depth) {
        return true;
    }

    return false;
}

// =========================================================================================== \\

/**
 * Function to simulate a singleton (only one call per function per id)
 * @param $id
 * @return bool
 */
function ai4seo_singleton($id): bool {
    return !ai4seo_prevent_loops($id, 1, 1);
}

// =========================================================================================== \\

/**
 * Given any text phrase that may not be suitable as a button or page label, this function will return a nice label
 * @param $text string The text to be converted
 * @return string The nice label
 */
function ai4seo_get_nice_label(string $text, $separator = " "): string {
    // convert every _ to $separator
    $text = str_replace("_", $separator, $text);

    // explode by the separator
    $text_array = explode($separator, $text);

    // make every word start with a capital letter
    $text_array = array_map("ucfirst", $text_array);

    // put the words back together
    $text = implode($separator, $text_array);

    // make some manual adjustments
    $text = str_replace(array("Rss"), array("RSS"), $text);

    return $text;
}

// =========================================================================================== \\

/**
 * Return weather the given string is a valid json
 * @param $string
 * @return bool
 */
function ai4seo_is_json($string): bool {
    if (!is_string($string)) {
        return false;
    }

    // check if string starts with { or [
    if ($string[0] !== "{" && $string[0] !== "[") {
        return false;
    }

    json_decode($string);

    return (json_last_error() == JSON_ERROR_NONE);
}

// =========================================================================================== \\

/**
 * Returns the SVG tag for the given (fontawesome) icon name
 * @param string $icon_name The name of the icon. Check function for allowed icon names.
 * @param string $alt_text (optional)
 * @param string $icon_css_class (optional)
 * @return string The icon SVG tag
 */
function ai4seo_get_svg_tag(string $icon_name, string $alt_text = "", string $icon_css_class = ""): string {
    $svg_tags = ai4seo_get_svg_tags();

    // Make sure that the icon-name is allowed
    if (!isset($svg_tags[$icon_name])) {
        return "";
    }

    $svg_tag = $svg_tags[$icon_name];

    // add css class to svg tag
    if ($icon_css_class) {
        $icon_css_class = "ai4seo-icon " . $icon_css_class;
    } else {
        $icon_css_class = "ai4seo-icon";
    }

    $svg_tag = str_replace("<svg", "<svg class='" . esc_attr($icon_css_class) . "'", $svg_tag);

    // add alt text to svg tag
    if ($alt_text) {
        $svg_tag = str_replace("<svg", "<svg aria-label='" . esc_attr($alt_text) . "'", $svg_tag);
        $svg_tag = str_replace("</svg>", "<title>" . esc_html($alt_text) . "</title></svg>", $svg_tag);
    }

    // Remove "<![CDATA[" and "]]>" anywhere (commonly used inside <style>).
    $svg_tag = str_replace( array( '<![CDATA[', ']]>' ), '', $svg_tag );

    return $svg_tag;
}

// =========================================================================================== \\

/**
 * Returns a question mark icon with tooltip
 * @param string $tooltip_text The tooltip text to be displayed
 * @param string $icon_css_class (optional) The css class for the icon
 * @param string $icon_name (optional) The name of the icon. Check function for allowed icon names.
 * @return string The icon SVG tag
 */
function ai4seo_get_icon_with_tooltip_tag(string $tooltip_text, string $icon_css_class = "", string $icon_name = "circle-question"): string {
    $icon = ai4seo_get_svg_tag($icon_name, "", $icon_css_class);
    $output = "<span class='ai4seo-icon-with-tooltip ai4seo-tooltip-holder'>";
    $output .= $icon;
    $output .= "<div class='ai4seo-tooltip ai4seo-ignore-during-dashboard-refresh'>{$tooltip_text}</div>";
    $output .= "</span>";
    return $output;
}

// =========================================================================================== \\

/**
 * Removes double sentences from the given string
 * @param $input_string
 * @return string
 */
function ai4seo_remove_double_sentences($input_string): string {
    // Split the input string into sentences using a regular expression
    $sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $input_string);

    // Create an empty array to store unique sentences
    $unique_sentences = array();

    // Loop through the sentences array and add unique sentences to the uniqueSentences array
    foreach ($sentences as $sentence) {
        $trimmed_sentence = trim($sentence);

        if (!in_array($trimmed_sentence, $unique_sentences)) {
            $unique_sentences[] = $trimmed_sentence;
        }
    }

    // Join the unique sentences back into a single string
    return implode(' ', $unique_sentences);
}

// =========================================================================================== \\

/**
 * Truncate a string after a specified soft cap length, considering the first end of sentence
 * as the end of the input, with a hard cap on the length.
 *
 * @param string $input   The input string to be truncated.
 * @param int $soft_cap The soft cap length after which to look for the end of a sentence.
 * @param int $hard_cap The hard cap length to truncate the string if no sentence end is found.
 * @return string         The truncated string.
 */
function ai4seo_truncate_sentence(string $input, int $soft_cap, int $hard_cap = 0 ): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(574839326, 'Prevented loop', true);
        return $input;
    }

    // Ensure the input length is within the limits.
    if ( ai4seo_mb_strlen( $input ) <= $soft_cap ) {
        return $input;
    }

    // if hard cap is less than soft cap, set hard cap to soft cap
    if ($hard_cap < $soft_cap) {
        $hard_cap = $soft_cap;
    }

    // Start truncation from soft cap onwards.
    $truncated_at_hard_cap    = ai4seo_mb_substr( $input, 0, $hard_cap );
    $truncated_after_soft_cap = ai4seo_mb_substr( $truncated_at_hard_cap, $soft_cap );

    // Define sentence-ending punctuation marks.
    $punctuation_marks = array( '.', '!', '?', '…', '؟', '·', '。', '！', '？' );

    // Find the first sentence-ending punctuation after the soft cap.
    $first_sentence_after_soft_cap_end = PHP_INT_MAX;

    foreach ( $punctuation_marks as $mark ) {
        $position = ai4seo_mb_strpos( $truncated_after_soft_cap, $mark );

        if ( $position !== false ) {
            $first_sentence_after_soft_cap_end = min( $first_sentence_after_soft_cap_end, $position );
        }
    }

    // If an end of sentence is found, adjust the truncation to include it.
    if ( $first_sentence_after_soft_cap_end !== PHP_INT_MAX ) {
        $truncated_sentence = ai4seo_mb_substr( $truncated_at_hard_cap, 0, $soft_cap + $first_sentence_after_soft_cap_end + 1 );
    } else {
        // If no sentence end is found, ensure the truncation is at hard cap.
        $truncated_sentence = $truncated_at_hard_cap;
    }

    return $truncated_sentence;
}

// =========================================================================================== \\

/**
 * Returns the plugin basename
 * @return string The plugin basename
 */
function ai4seo_get_plugin_basename(): string {
    return sanitize_text_field(plugin_basename(__FILE__));
}

// =========================================================================================== \\

/**
 * Returns a url leading to a point within the plugin
 * @param string $sub_page The page to navigate to
 * @param array $additional_parameter Additional parameters to add to the url
 * @param bool $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo)
 * @return string The plugins admin sub page url
 */
function ai4seo_get_subpage_url(string $sub_page = "", array $additional_parameter = array(), bool $return_full_path = true): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(688861817, 'Prevented loop', true);
        return '';
    }

    $sub_page = sanitize_key($sub_page);

    if ($return_full_path) {
        $page_url = admin_url("admin.php");
    } else {
        $page_url = "";
    }

    $page_url = ai4seo_add_query_arg('page', AI4SEO_PLUGIN_IDENTIFIER, $page_url);

    // workaround: if page is dashboard, remove it from the url
    if ($sub_page == "dashboard") {
        $sub_page = "";
    }

    // add subpage if set
    if ($sub_page) {
        $page_url = ai4seo_add_query_arg('ai4seo_subpage', $sub_page, $page_url);
    }

    // add additional parameters if set
    if ($additional_parameter) {
        foreach ($additional_parameter as $this_key => $this_value) {
            $page_url = ai4seo_add_query_arg($this_key, $this_value, $page_url);
        }
    }

    // sanitize the url if we want the full path
    if ($return_full_path) {
        $page_url = esc_url_raw($page_url);
    }

    $page_url = str_replace('&#038;', '&', $page_url);
    $page_url = str_replace('#038;', '&', $page_url);
    $page_url = html_entity_decode($page_url, ENT_QUOTES);

    return $page_url;
}

// =========================================================================================== \\

function ai4seo_add_query_arg($key, $value, $url): string {
    $key = sanitize_key($key);
    $value = sanitize_text_field($value);

    // preserve %#% placeholder during add_query_arg
    if ( strpos( $url, '%#%' ) !== false ) {
        $url = str_replace( '%#%', 'AI4SEO_PAGE_PLACEHOLDER', $url );
    }

    $url = add_query_arg($key, $value, $url);

    // restore %#%
    $url = str_replace( 'AI4SEO_PAGE_PLACEHOLDER', '%#%', $url );

    return $url;
}

// =========================================================================================== \\

/**
 * Returns the url to a specific post type within the AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME array
 * @param string $post_type The post type to navigate to
 * @param int $current_page The current page to navigate to
 * @param array $additional_parameter Additional parameters to add to the url
 * @param bool $return_full_path Whether to add the full path (http://example.com/wp-admin/admin.php?page=ai-for-seo&ai4seo_subpage=post&ai4seo_post_type=post)
 * @return string The url to the post type
 */
function ai4seo_get_post_type_page_url(string $post_type, int $current_page = 1, array $additional_parameter = array(), bool $return_full_path = true): string {
    $additional_parameter["ai4seo_page"] = $current_page ?: "%#%"; # %#% = pagination workaround

    return ai4seo_get_subpage_url(
        AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME,
        array("ai4seo_post_type" => $post_type) + $additional_parameter,
        $return_full_path
    );
}

// =========================================================================================== \\

function ai4seo_normalize_pagination_links($pagination_links) {
    // Normalize broken ampersand entities in query strings.
    // This:
    //   href="...page=ai-for-seo#038;ai4seo_subpage=media&#038;ai4seo_page=2"
    // becomes:
    //   href="...page=ai-for-seo&ai4seo_subpage=media&ai4seo_page=2"
    $pagination_links = preg_replace(
        '/&(?:amp;)?#038;|#038;/',
        '&',
        $pagination_links
    );

    return $pagination_links;
}

// =========================================================================================== \\

/**
 * Returns whether the user is inside our plugin's admin pages
 * @return bool Whether the user is inside our plugin's admin pages
 */
function ai4seo_is_user_inside_our_plugin_admin_pages(): bool {
    // check if the "page" parameter is set and if it is our plugin
    return is_admin() && isset($_GET["page"]) && sanitize_key($_GET["page"]) == AI4SEO_PLUGIN_IDENTIFIER;
}

// =========================================================================================== \\

/**
 * Checks if the active page is the given page
 * @param string $plugin_page The page to check
 * @return bool Whether the active page is the given page
 */
function ai4seo_is_plugin_page_active(string $plugin_page = ""): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(585904324, 'Prevented loop', true);
        return false;
    }

    $plugin_page = sanitize_key($plugin_page);
    $active_plugin_page = ai4seo_get_active_subpage();

    // check if we are inside the plugins admin pages (page should be ai-for-seo)
    if (!ai4seo_is_user_inside_our_plugin_admin_pages()) {
        return false;
    }

    // Dashboard: both "dashboard" and empty are considered dashboard
    if (!$plugin_page) {
        $plugin_page = "dashboard";
    }

    if (!$active_plugin_page) {
        $active_plugin_page = "dashboard";
    }

    return $active_plugin_page == $plugin_page;
}

// =========================================================================================== \\

/**
 * Checks, if the current post type is the given post type
 * @param string $post_type The post type to check
 * @return bool Whether the current post type is the given post type
 */
function ai4seo_is_post_type_open(string $post_type): bool {
    $current_post_type = ai4seo_get_active_post_type_subpage();
    return $current_post_type == $post_type;
}

// =========================================================================================== \\

/**
 * Returns the active page (admin url page)
 * @return string The active page
 */
function ai4seo_get_active_subpage(): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(332066427, 'Prevented loop', true);
        return '';
    }

    if (!ai4seo_is_user_inside_our_plugin_admin_pages()) {
        return '';
    }

    # workaround: amp; is added to the url when the user is redirected from stripe
    $potential_subpage = sanitize_key($_GET["ai4seo_subpage"] ?? $_GET["amp;ai4seo_subpage"] ?? $_GET["ai4seo-tab"] ?? $_GET["amp;ai4seo-tab"] ?? '');

    if (!$potential_subpage) {
        $potential_subpage = ai4seo_get_default_subpage();
    }

    return $potential_subpage;
}

// =========================================================================================== \\

/**
 * Returns the active post type page
 * @return string The active post type page
 */
function ai4seo_get_active_post_type_subpage(): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(605553019, 'Prevented loop', true);
        return '';
    }

    if (!ai4seo_is_user_inside_our_plugin_admin_pages()) {
        return "";
    }

    if (ai4seo_get_active_subpage() != "post") {
        return "";
    }

    return sanitize_key($_GET["ai4seo_post_type"] ?? ai4seo_get_default_post_type());
}

// =========================================================================================== \\

/**
 * Returns the default page (dashboard)
 * @return string The default page
 */
function ai4seo_get_default_subpage(): string {
    return "dashboard";
}

// =========================================================================================== \\

/**
 * Returns the default post type
 * @return string The default post type
 */
function ai4seo_get_default_post_type(): string {
    return "page";
}

// =========================================================================================== \\

/**
 * Returns the plugin directory path
 * @param string $sub_path The sub path to append to the plugin directory path (optional)
 * @return string The plugin directory path
 */
function ai4seo_get_plugin_dir_path(string $sub_path = ""): string {
    return plugin_dir_path(__FILE__) . $sub_path;
}

// =========================================================================================== \\

/**
 * Returns the plugins base urls
 * @param string $sub_path The sub path to append to the plugins base url (optional)
 * @return string The url to the file
 */
function ai4seo_get_plugins_url(string $sub_path = ""): string {
    return plugins_url($sub_path, __FILE__);
}

// =========================================================================================== \\

/**
 * Returns the path to includes/modals
 * @param string $sub_path The sub path to append to the includes/modals path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_modal_schemas_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/modal_schemas/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/pages
 * @param string $sub_path The sub path to append to the includes/pages path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/pages/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/pages/content_types
 * @param string $sub_path The sub path to append to the includes/pages/content_types path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_pages_content_types_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/pages/content_types/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/ajax/display
 * @param string $sub_path The sub path to append to the includes/ajax/display path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_display_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/ajax/display/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/ajax/process
 * @param string $sub_path The sub path to append to the includes/ajax/process path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_ajax_process_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/ajax/process/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/elements
 * @param string $sub_path The sub path to append to the includes/elements path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_elements_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/elements/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the path to includes/api
 * @param string $sub_path The sub path to append to the includes/api path (optional)
 * @return string The path to the file
 */
function ai4seo_get_includes_api_path(string $sub_path = ""): string {
    return ai4seo_get_plugin_dir_path("includes/api/{$sub_path}");
}

// =========================================================================================== \\

/**
 * Returns the url to assets/images
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_images_url($file_name = ""): string {
    return ai4seo_get_plugins_url("assets/images/{$file_name}");
}

// =========================================================================================== \\

/**
 * Returns the url to assets/css
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_css_path(string $file_name = ""): string {
    return ai4seo_get_plugins_url("assets/css/{$file_name}");
}

// =========================================================================================== \\

/**
 * Returns the url to assets/js
 * @param string $file_name The name of the file to get the path for
 * @return string The url to the file
 */
function ai4seo_get_assets_js_path(string $file_name): string {
    return ai4seo_get_plugins_url("assets/js/{$file_name}");
}

// =========================================================================================== \\

/**
 * Returns the url to the SOOZ logo
 * @param string $variant The variant of the logo to get the url for
 * @return string The url to the file
 */
function ai4seo_get_sooz_logo_url(string $variant = "32x32"): string {
    switch ($variant) {
        case "sooz":
            return ai4seo_get_assets_images_url("logos/sooz.svg");
        case "sooz-oo":
            return ai4seo_get_assets_images_url("logos/sooz-oo.svg");
        case "sooz-with-ai-for-seo":
            return ai4seo_get_assets_images_url("logos/sooz-with-ai-for-seo.svg");
        case "svg":
            return ai4seo_get_assets_images_url("logos/ai-for-seo.svg");
        case "full":
            return ai4seo_get_assets_images_url("logos/ai-for-seo-full-logo.png");
        case "64x64":
            return ai4seo_get_assets_images_url("logos/ai-for-seo-logo-64x64.png");
        case "256x256":
            return ai4seo_get_assets_images_url("logos/ai-for-seo-logo-256x256.png");
        case "512x512-animated":
            return ai4seo_get_assets_images_url("logos/ai-for-seo-logo-animated-512x512.gif");
        case "sooz-32x32":
            return ai4seo_get_assets_images_url("logos/sooz-ai-for-seo-logo-32x32.jpg");
        case "sooz-64x64":
            return ai4seo_get_assets_images_url("logos/sooz-ai-for-seo-logo-64x64.jpg");
        case "sooz-256x256":
            return ai4seo_get_assets_images_url("logos/sooz-ai-for-seo-logo-256x256.jpg");
        case "32x32":
        default:
            return ai4seo_get_assets_images_url("logos/ai-for-seo-logo-32x32.png");
    }
}

// =========================================================================================== \\

function ai4seo_get_sooz_logo_image_tag(string $variant = "sooz"): string {
    return "<img src='" . esc_url(ai4seo_get_sooz_logo_url($variant)) . "' alt='" . esc_attr(AI4SEO_PLUGIN_NAME) . "' title='" . esc_attr(AI4SEO_PLUGIN_NAME) . "' class='ai4seo-sooz-logo'>";
}

// =========================================================================================== \\

/**
 * Returns the purchase plan url
 * @param string $ai4seo_client_id
 * @return string The purchase plan url
 */
function ai4seo_get_purchase_plan_url(string $ai4seo_client_id): string {
    return AI4SEO_OFFICIAL_PRICING_URL . "/?client-id={$ai4seo_client_id}";
}

// =========================================================================================== \\

/**
 * This function uses wp_kses with our collection of allowed html tags and attributes
 * @param $content string The content to sanitize
 * @return string The sanitized content
 */
function ai4seo_wp_kses(string $content): string {
    $allowed_html_tags_and_attributes = ai4seo_get_allowed_html_tags_and_attributes();

    return wp_kses($content, $allowed_html_tags_and_attributes);
}

// =========================================================================================== \\

/**
 * Echoes the sanitized content using ai4seo_wp_kses.
 *
 * @param string $content The content to sanitize and echo.
 * @return void
 */
function ai4seo_echo_wp_kses(string $content): void {
    $allowed_html_tags_and_attributes = ai4seo_get_allowed_html_tags_and_attributes();

    echo wp_kses($content, $allowed_html_tags_and_attributes);
}

// =========================================================================================== \\

function ai4seo_get_publicly_accessible_post_types(): array {
    $excluded_post_types = array(
        'attachment',
        'ai4seo_ngg', # nextgen gallery
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'template',
        'wp_block',
    );

    $args = array(
        'public'   => true,
    );

    $post_types = get_post_types($args, 'objects');
    $publicly_accessible_post_types = array();

    foreach ($post_types as $post_type) {
        if (!$post_type->_builtin && !$post_type->publicly_queryable) {
            continue;
        }

        if (!$post_type->_builtin && !$post_type->rewrite) {
            continue;
        }

        if (in_array($post_type->name, $excluded_post_types)) {
            continue;
        }

        if ($post_type->has_archive || $post_type->capability_type === 'post' || !$post_type->exclude_from_search) {
            $publicly_accessible_post_types[$post_type->name] = $post_type->label;
        }
    }

    return $publicly_accessible_post_types;
}

// =========================================================================================== \\

/**
 * This function retrieves the language code of the WordPress installation as defined in the settings
 * @return string The language code of the WordPress installation
 */
function ai4seo_get_wordpress_language_code(): string {
    return get_bloginfo("language");
}

// =========================================================================================== \\

/**
 * This function retrieves the language of the WordPress installation as defined in the settings
 * @return string The language of the WordPress installation
 */
function ai4seo_get_wordpress_language(): string {
    $wordpress_language_code = ai4seo_get_wordpress_language_code();
    return ai4seo_get_language_long_version($wordpress_language_code);
}

// =========================================================================================== \\

/**
 * This functions returns the long version of a given language short version (de_DE -> german)
 * @param string $language_short_version The short version of the language
 * @param string $value_on_undefined The value to return if the language is not found
 * @return string The long version of the language
 */
function ai4seo_get_language_long_version(string $language_short_version, string $value_on_undefined = AI4SEO_DEFAULT_FALLBACK_LANGUAGE): string {
    // Normalize the short code by converting it to lowercase
    $language_short_version = strtolower($language_short_version);

    // Check for a full language code match first
    if (isset(AI4SEO_FULL_LANGUAGE_CODE_MAPPING[$language_short_version])) {
        return AI4SEO_FULL_LANGUAGE_CODE_MAPPING[$language_short_version];
    }

    // Fall back to checking the base language code (first two letters)
    $language_base = substr($language_short_version, 0, 2);
    return AI4SEO_BASE_LANGUAGE_CODE_MAPPING[$language_base] ?? $value_on_undefined;
}

// =========================================================================================== \\

/**
 * Check if a PHP function is usable (defined and not disabled).
 *
 * @param string $function_name The name of the function to check.
 * @return bool Returns true if the function is usable, false otherwise.
 */
function ai4seo_is_function_usable(string $function_name): bool {
    if (!function_exists($function_name)) {
        return false;
    }

    $disabled_functions = ini_get("disable_functions");

    if (!$disabled_functions) {
        return true;
    }

    return !in_array($function_name, explode(",", $disabled_functions));
}

// =========================================================================================== \\

/**
 * Convert seconds into HH:MM:SS format.
 *
 * @param int $seconds The total number of seconds to convert.
 * @return string The formatted time in HH:MM:SS or "D days and HH:MM:SS" format.
 */
function ai4seo_format_seconds_to_hhmmss_or_days_hhmmss(int $seconds): string {
    // Ensure the seconds are non-negative
    $seconds = max(0, $seconds);

    // Calculate hours, minutes, and seconds
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remaining_seconds = $seconds % 60;

    if ($hours >= 24) {
        $formatted_duration = sprintf(
            /* translators: 1: Number of days. 2: Hours. 3: Minutes. 4: Seconds. */
            esc_html__('%1$d days %2$02d:%3$02d:%4$02d', 'ai-for-seo'),
            floor($hours / 24),
            $hours % 24,
            $minutes,
            $remaining_seconds);
    } else {
        // Format the result as HH:MM:SS
        $formatted_duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining_seconds);
    }

    return $formatted_duration;
}

// =========================================================================================== \\

/**
 * Calculate the difference in seconds between the current user timestamp and a given UTC timestamp.
 *
 * @param int $utc_timestamp The UTC timestamp to compare.
 * @return int The difference in seconds. Positive if the UTC timestamp is in the future, negative if in the past.
 */
function ai4seo_get_time_difference_in_seconds(int $utc_timestamp): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(206072924, 'Prevented loop', true);
        return 0;
    }

    // Get the current timestamp in WordPress timezone
    $timezone = get_option('timezone_string');
    $current_time = current_time('timestamp'); // Current time in WordPress timezone

    // If a valid timezone is set, convert UTC timestamp to WordPress timezone
    if ($timezone) {
        $datetime_utc = new DateTime("@$utc_timestamp");
        try {
            $datetime_utc->setTimezone(new DateTimeZone($timezone)); // Convert to WordPress timezone
        } catch (Exception $e) {
            return $utc_timestamp - $current_time; // return the difference in seconds if timezone is invalid
        }
        $utc_timestamp_local = strtotime($datetime_utc->format('Y-m-d H:i:s')); // Convert to timestamp
    } else {
        $utc_timestamp_local = $utc_timestamp; // Default to UTC if no timezone is set
    }

    // Calculate and return the difference in seconds
    return $utc_timestamp_local - $current_time;
}

// =========================================================================================== \\

/**
 * Function returns the users formatted time, based on a unix timestamp
 *
 * @param int    $unix_timestamp The unix timestamp to format.
 * @param string $date_format    The date format to use (auto, default: date_format).
 * @param string $time_format    The time format to use (auto, default: time_format).
 * @param string $separator      The separator to use (default: ' ').
 * @param string $timezone       The timezone to use (auto, default: timezone_string).
 *
 * @return string
 */
function ai4seo_format_unix_timestamp( int $unix_timestamp, string $date_format = 'auto', string $time_format = 'auto', string $separator = ' ', string $timezone = 'auto' ) : string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(942137684, 'Prevented loop', true);
        return strval($unix_timestamp);
    }

    $final_format = '';
    $date_auto_miss = false;

    if ($date_format === 'auto-miss') {
        $date_auto_miss = true;
        $date_format = 'auto';
    }

    // add date format
    if ( $date_format ) {
        if ( $date_format === 'auto' ) {
            // use plugin option with fallback
            $date_format = get_option( 'date_format', 'Y-m-d' );

            if (!$date_format) {
                $date_format = 'Y-m-d';
            }
        }

        $final_format .= sanitize_text_field( $date_format );
    }

    // add time format
    if ( $time_format ) {
        if ( $time_format === 'auto' ) {
            // use plugin option with fallback
            $time_format = get_option( 'time_format', 'H:i' );

            if (!$time_format) {
                $time_format = 'H:i';
            }
        }

        // separator if we already have a date format
        if ( $date_format ) {
            $final_format .= $separator;
        }

        $final_format .= sanitize_text_field( $time_format );
    }

    if (!$final_format) {
        // nothing to format, return empty string
        return '';
    }

    // Get the WordPress timezone
    if ( $timezone === 'auto' ) {
        // use plugin option with fallback to UTC
        $timezone = get_option( 'timezone_string', 'UTC' );
    }

    // If no valid timezone is set, default to UTC
    if ( ! $timezone ) {
        // Use safe UTC format as fallback
        return ai4seo_gmdate( $final_format, $unix_timestamp );
    }

    try {
        // auto-miss: omit date if timestamp is today (use timezone-aware comparison)
        if ( $date_auto_miss ) {
            try {
                $now_datetime_object = new DateTime( 'now', new DateTimeZone( $timezone ) );
                $this_datetime_object = new DateTime( '@' . $unix_timestamp );
                $this_datetime_object->setTimezone( new DateTimeZone( $timezone ) );

                if ( $now_datetime_object->format( 'Y-m-d' ) === $this_datetime_object->format( 'Y-m-d' ) ) {
                    $final_format = sanitize_text_field( $time_format );
                }
            } catch ( Exception $e ) {
                // silently ignore and fall back to normal formatting
            }
        }

        // Create a DateTime object with the UTC timestamp
        $datetime_object = new DateTime( '@' . $unix_timestamp ); // The @ symbol treats the timestamp as UNIX time
        $datetime_object->setTimezone( new DateTimeZone( $timezone ) ); // Set to WordPress timezone
    } catch ( Exception $e ) {
        // Use safe UTC format as fallback
        return ai4seo_gmdate( $final_format, $unix_timestamp );
    }

    // Format and return the time in the desired format
    return $datetime_object->format( $final_format );
}

// =========================================================================================== \\

/**
 * Safely wrap gmdate() and provide fallbacks if gmdate is unavailable.
 *
 * @param string $format         The date/time format.
 * @param int    $unix_timestamp The UNIX timestamp.
 *
 * @return string
 */
function ai4seo_gmdate(string $format, int $unix_timestamp = 0 ) : string {
    $unix_timestamp = (int) $unix_timestamp;

    if ( $unix_timestamp <= 0 ) {
        $unix_timestamp = time();
    }

    if ( $format === '' ) {
        $format = 'Y-m-d H:i:s';
    }

    if ( function_exists( 'gmdate' ) ) {
        return gmdate( $format, $unix_timestamp );
    }

    try {
        $datetime_object = new DateTimeImmutable( '@' . $unix_timestamp );
        $datetime_object = $datetime_object->setTimezone( new DateTimeZone( 'UTC' ) );

        return $datetime_object->format( $format );
    } catch ( Exception $e ) {
        // Fallback to date() in UTC if anything goes wrong.
        return date( $format, $unix_timestamp );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
    }
}

// =========================================================================================== \\

/**
 * Resolve a DateTimeZone based on plugin/WordPress settings or a given timezone string.
 *
 * Note: Currently not used by ai4seo_format_unix_timestamp(), but kept as a helper.
 *
 * @param string $timezone Timezone identifier or 'auto'.
 *
 * @return DateTimeZone
 */
function ai4seo_get_timezone(string $timezone = 'auto' ) : DateTimeZone {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(381267591, 'Prevented loop', true);
        return new DateTimeZone( 'UTC' );
    }

    $timezone_string = '';

    if ( $timezone === 'auto' || $timezone === '' ) {
        // 1) Try plugin option.
        $timezone_string = get_option( 'timezone_string', '' );

        // 2) Fallback: wp_timezone_string() if available (WP 5.3+).
        if ( ! is_string( $timezone_string ) || $timezone_string === '' ) {
            if ( function_exists( 'wp_timezone_string' ) ) {
                $timezone_string = wp_timezone_string();
            }

        }

        // 4) Fallback: build from gmt_offset if still empty.
        if ( ! is_string( $timezone_string ) || $timezone_string === '' ) {
            $gmt_offset = ai4seo_get_option( 'gmt_offset' );

            if ( is_numeric( $gmt_offset ) && (float) $gmt_offset !== 0.0 ) {
                $timezone_string = timezone_name_from_abbr( '', (float) $gmt_offset * HOUR_IN_SECONDS, 0 );

                if ( $timezone_string === false ) {
                    // Last-resort mapping for fixed offsets (Etc/GMT has reversed sign).
                    $timezone_string = sprintf( 'Etc/GMT%+d', (int) - $gmt_offset );
                }
            }
        }
    } else {
        $timezone_string = sanitize_text_field( $timezone );
    }

    if ( ! is_string( $timezone_string ) || $timezone_string === '' ) {
        $timezone_string = 'UTC';
    }

    try {
        return new DateTimeZone( $timezone_string );
    } catch ( Exception $e ) {
        return new DateTimeZone( 'UTC' );
    }
}

// =========================================================================================== \\

/**
 * Function to convert datetime-local format to unix timestamp
 * @param string $datetime_local The datetime-local string (YYYY-MM-DDTHH:MM)
 * @param string $timezone The timezone to use (auto, default: timezone_string)
 * @return int The unix timestamp
 */
function ai4seo_convert_datetime_local_to_timestamp(string $datetime_local, string $timezone = 'auto'): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(858347572, 'Prevented loop', true);
        return 0;
    }

    // Get the WordPress timezone
    if ($timezone == 'auto') {
        $timezone = get_option('timezone_string');
    }

    // If no valid timezone is set, default to UTC
    if (!$timezone) {
        return strtotime($datetime_local . ' UTC'); // Treat as UTC if no timezone
    }

    try {
        // Create DateTime object from the local datetime string in the specified timezone
        $datetime_object = new DateTime($datetime_local, new DateTimeZone($timezone));
        return $datetime_object->getTimestamp();
    } catch (Exception $e) {
        // Fallback: treat as UTC
        return strtotime($datetime_local . ' UTC');
    }
}

// =========================================================================================== \\

/**
 * Function to deactivate AI for SEO
 * @return bool Whether the plugin was deactivated
 */
function ai4seo_deactivate_plugin(): bool {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return false;
    }

    // Check if the user has the required permissions
    if (!current_user_can('activate_plugins')) {
        return false;
    }

    // Deactivate the plugin
    try {
        deactivate_plugins(ai4seo_get_plugin_basename());
    } catch (Exception $e) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Function to return the clients ip
 * @return string The clients ip
 */
function ai4seo_get_client_ip(): string {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $client_ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);

        if (ai4seo_is_valid_ip($client_ip)) {
            return $client_ip;
        }
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $client_ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);

        if (ai4seo_is_valid_ip($client_ip)) {
            return $client_ip;
        }
    }

    if (isset($_SERVER['REMOTE_ADDR'])) {
        $client_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

        if (ai4seo_is_valid_ip($client_ip)) {
            return $client_ip;
        }
    }

    return "";
}

// =========================================================================================== \\

/**
 * Function to return the clients user agent
 * @return string The clients user agent
 */
function ai4seo_get_client_user_agent(): string {
    return sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? "");
}

// =========================================================================================== \\

/**
 * Function to return the webservers ip
 * @return string The webservers ip
 */
function ai4seo_get_server_ip(): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(863637103, 'Prevented loop', true);
        return '';
    }

    try {
        $server_ip_response = ai4seo_file_get_contents('https://api.ipify.org');

        if ($server_ip_response !== false) {
            $server_ip = sanitize_text_field($server_ip_response);

            if (ai4seo_is_valid_ip($server_ip)) {
                return $server_ip;
            }
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            $server_ip = sanitize_text_field($_SERVER['SERVER_ADDR']);

            if (ai4seo_is_valid_ip($server_ip)) {
                return $server_ip;
            }
        }
    } catch (Exception $e) {
        return "";
    }

    return "";
}

// =========================================================================================== \\

/**
 * Function to check if the given string is a valid ip address
 * @param string $ip The ip to check
 * @return bool Whether the given string is a valid ip address
 */
function ai4seo_is_valid_ip(string $ip): bool {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

// =========================================================================================== \\

/**
 * Function to get the checksum of an array
 * @return int The crc32 checksum of the array
 */
function ai4seo_get_array_checksum($array): int {
    return crc32(serialize($array));
}

// =========================================================================================== \\

/**
 * Returns whether the user is inside the 'installed plugins' (plugins.php) admin page
 * @return bool Whether the user is inside the 'installed plugins' admin page
 */
function ai4seo_is_user_inside_installed_plugins_page(): bool {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    if ($request_uri === '') {
        return false;
    }

    return strpos($request_uri, 'plugins.php') !== false;
}

// =========================================================================================== \\

function ai4seo_is_wordpress_cron_disabled(): bool {
    return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
}

// =========================================================================================== \\

function ai4seo_get_prefixed_input_name($input_id): string {
    return AI4SEO_POST_PARAMETER_PREFIX . $input_id;
}

// =========================================================================================== \\

function ai4seo_get_unprefixed_input_name($input_id): string {
    return str_replace(AI4SEO_POST_PARAMETER_PREFIX, "", $input_id);
}

// =========================================================================================== \\

/**
 * Determine if a URL references a locally hosted file.
 *
 * @param string $url URL to inspect.
 * @return bool True when the URL refers to a file on the current site.
 */
function ai4seo_is_local_file( string $url ): bool {
    if ( empty( $url ) ) {
        return false;
    }

    $uploads_directory = wp_get_upload_dir();

    if ( ! empty( $uploads_directory['baseurl'] ) && strpos( $url, $uploads_directory['baseurl'] ) === 0 ) {
        return true;
    }

    $parsed_url = wp_parse_url( $url );

    if ( empty( $parsed_url ) ) {
        return false;
    }

    if ( empty( $parsed_url['host'] ) ) {
        return true;
    }

    $site_url  = wp_parse_url( home_url() );
    $site_host = isset( $site_url['host'] ) ? strtolower( $site_url['host'] ) : '';
    $url_host  = strtolower( $parsed_url['host'] );

    if ( $site_host === $url_host ) {
        return true;
    }

    $normalized_site_host = preg_replace( '/^www\./', '', $site_host );
    $normalized_url_host  = preg_replace( '/^www\./', '', $url_host );

    return ! empty( $normalized_site_host ) && $normalized_site_host === $normalized_url_host;
}

// =========================================================================================== \\

/**
 * Convert a local URL into an absolute filesystem path when possible.
 *
 * @param string $url Local URL to convert.
 * @return string|null Absolute path or null when it cannot be resolved.
 */
function ai4seo_get_local_path_from_url( string $url ): ?string {
    $decoded_url = rawurldecode( $url );

    $uploads_directory = wp_get_upload_dir();

    if ( ! empty( $uploads_directory['baseurl'] ) && strpos( $decoded_url, $uploads_directory['baseurl'] ) === 0 ) {
        $relative_path = ltrim( substr( $decoded_url, strlen( $uploads_directory['baseurl'] ) ), '/' );
        $local_path    = trailingslashit( $uploads_directory['basedir'] ) . $relative_path;

        if ( file_exists( $local_path ) ) {
            return wp_normalize_path( $local_path );
        }
    }

    $parsed_url = wp_parse_url( $decoded_url );
    $path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

    if ( empty( $path ) ) {
        return null;
    }

    $absolute_path = trailingslashit( ABSPATH ) . ltrim( $path, '/' );

    if ( file_exists( $absolute_path ) ) {
        return wp_normalize_path( $absolute_path );
    }

    return null;
}

// =========================================================================================== \\

/**
 * Retrieve the MIME type of a locally stored file.
 *
 * @param string $path Absolute path to the local file.
 * @return string|null MIME type string when detected, otherwise null.
 */
function ai4seo_get_local_mime_type( string $path ): ?string {
    if ( empty( $path ) || ! file_exists( $path ) || ! is_readable( $path ) ) {
        return null;
    }

    $normalized_path = wp_normalize_path( $path );

    if ( ai4seo_is_function_usable( 'mime_content_type' ) ) {
        $mime_type = @mime_content_type( $normalized_path );

        if ( ! empty( $mime_type ) ) {
            return ai4seo_normalize_mime_type_string( $mime_type );
        }
    }

    if ( ai4seo_is_function_usable( 'finfo_open' ) && ai4seo_is_function_usable( 'finfo_file' ) ) {
        $file_info = finfo_open( FILEINFO_MIME_TYPE );

        if ( $file_info ) {
            $mime_type = finfo_file( $file_info, $normalized_path );
            if ( ai4seo_is_function_usable( 'finfo_close' ) ) {
                finfo_close( $file_info );
            }

            if ( ! empty( $mime_type ) ) {
                return ai4seo_normalize_mime_type_string( $mime_type );
            }
        }
    }

    if ( function_exists( 'wp_check_filetype' ) ) {
        $file_type = wp_check_filetype( $normalized_path );

        if ( ! empty( $file_type['type'] ) ) {
            return ai4seo_normalize_mime_type_string( $file_type['type'] );
        }
    }

    return null;
}

// =========================================================================================== \\

/**
 * Attempt to retrieve a MIME type from remote headers using WordPress HTTP helpers.
 *
 * @param string $url Remote URL to probe.
 * @return string|null MIME type string or null if unavailable.
 */
function ai4seo_get_remote_mime_type( string $url ): ?string {
    if ( empty( $url ) ) {
        return null;
    }

    $request_arguments = array(
        'timeout'     => 5,
        'redirection' => 3,
        'user-agent'  => 'AI4SEO/' . AI4SEO_PLUGIN_VERSION_NUMBER,
        'sslverify'   => false,
    );

    if ( function_exists( 'wp_remote_head' ) ) {
        $response = wp_remote_head( $url, $request_arguments );

        if ( ! is_wp_error( $response ) ) {
            $mime_type = wp_remote_retrieve_header( $response, 'content-type' );

            $mime_type = ai4seo_normalize_mime_type_string( $mime_type );

            if ( ! empty( $mime_type ) ) {
                return $mime_type;
            }
        }
    }

    if ( function_exists( 'wp_remote_get' ) ) {
        $request_arguments['method'] = 'GET';
        $request_arguments['headers'] = array( 'Range' => 'bytes=0-1023' );

        $response = wp_remote_get( $url, $request_arguments );

        if ( ! is_wp_error( $response ) ) {
            $mime_type = wp_remote_retrieve_header( $response, 'content-type' );

            $mime_type = ai4seo_normalize_mime_type_string( $mime_type );

            if ( ! empty( $mime_type ) ) {
                return $mime_type;
            }
        }
    }

    return null;
}

// =========================================================================================== \\

/**
 * Clean and normalize a MIME type string extracted from headers or file metadata.
 *
 * @param string|null $mime_type Raw MIME type string.
 * @return string|null Normalized MIME type or null when empty.
 */
function ai4seo_normalize_mime_type_string( ?string $mime_type ): ?string {
    if ( empty( $mime_type ) ) {
        return null;
    }

    if ( strpos( $mime_type, ';' ) !== false ) {
        $mime_type = explode( ';', $mime_type )[0];
    }

    $mime_type = strtolower( trim( $mime_type ) );

    return $mime_type !== '' ? $mime_type : null;
}

// =========================================================================================== \\

/**
 * Get the MIME type of file from a given URL.
 *
 * @param string $url The URL of the file.
 * @return string|null The MIME type (e.g., "image/jpeg") or null if not found.
 */
function ai4seo_get_mime_type_from_url(string $url ): ?string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(739526157, 'Prevented loop', true);
        return null;
    }

    if ( empty( $url ) ) {
        return null;
    }

    if ( ai4seo_is_local_file( $url ) ) {
        $local_path = ai4seo_get_local_path_from_url( $url );

        if ( ! empty( $local_path ) ) {
            $mime_type = ai4seo_get_local_mime_type( $local_path );

            if ( ! empty( $mime_type ) ) {
                return $mime_type;
            }
        }
    }

    return ai4seo_get_remote_mime_type( $url );
}

// =========================================================================================== \\

function ai4seo_get_attachment_post_mime_type($attachment_post_id): ?string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(373146404, 'Prevented loop', true);
        return null;
    }

    $attachment_post = get_post($attachment_post_id);

    if ( ! $attachment_post || empty($attachment_post->post_type) ) {
        return null;
    }

    // we found it already in the post_mime_type field
    if (!empty($attachment_post->post_mime_type)) {
        return ai4seo_normalize_mime_type_string($attachment_post->post_mime_type);
    }

    // fallback: try to get it from the url
    $attachment_url = ai4seo_get_attachment_url($attachment_post_id);

    if (!$attachment_url) {
        return "";
    }

    return ai4seo_get_mime_type_from_url($attachment_url);
}

// =========================================================================================== \\

function ai4seo_get_attachment_url($attachment_post_id): ?string {
    $attachment_post = get_post($attachment_post_id);

    if ( ! $attachment_post || empty($attachment_post->post_type) ) {
        return null;
    }

    // check if it's an attachment
    if ($attachment_post->post_type === "attachment") {
        // check url of the attachment
        $ai4seo_attachment_url = wp_get_attachment_url($attachment_post_id);
    } else {
        $ai4seo_attachment_url = get_the_guid($attachment_post);
    }

    return $ai4seo_attachment_url;
}

// =========================================================================================== \\

/**
 * Retrieves a formatted backtrace debug message.
 *
 * @param string $separator The separator for each backtrace entry.
 * @return string The formatted backtrace message.
 */
function ai4seo_get_backtrace_debug_message(string $separator = '<br />' ): string {
    $backtrace_array = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
    $formatted_backtrace = array();

    foreach ( $backtrace_array as $index => $item ) {
        // Ensure necessary keys exist
        if ( ! isset( $item['function'], $item['line'], $item['file'] ) ) {
            continue;
        }

        // Ignore specific functions
        $ignored_functions = array( 'ai4seo_get_backtrace_debug_message' );

        if ( in_array( $item['function'], $ignored_functions, true ) ) {
            continue;
        }

        $formatted_backtrace[] = sprintf(
            '%s @ Line %d: <b>%s()</b>',
            basename( $item['file'] ),
            intval( $item['line'] ),
            esc_html( $item['function'] )
        );
    }

    if ( empty( $formatted_backtrace ) ) {
        return '';
    }

    $formatted_backtrace = array_reverse( $formatted_backtrace );

    // Add index numbers
    foreach ( $formatted_backtrace as $i => &$entry ) {
        $entry = ( $i + 1 ) . '. ' . sanitize_text_field($entry);
    }

    unset( $entry );

    return implode( $separator, $formatted_backtrace );
}

// =========================================================================================== \\

/**
 * Checks if debug output is visible to the current request context.
 *
 * @return bool
 */
function ai4seo_can_display_debug_output_messages(): bool {
    if (function_exists('wp_doing_cron') && wp_doing_cron()) {
        return false;
    }

    if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        return false;
    }

    if (!is_admin()) {
        return false;
    }

    return ai4seo_can_manage_this_plugin();
}

// =========================================================================================== \\

/**
 * Writes a debug message to the uploads log file.
 *
 * @param string $message The final log message.
 * @return bool
 */
function ai4seo_append_debug_message_to_file(string $message): bool {
    if (!ai4seo_is_function_usable('wp_upload_dir')
        || !ai4seo_is_function_usable('trailingslashit')
        || !ai4seo_is_function_usable('wp_mkdir_p')
        || !ai4seo_is_function_usable('wp_is_writable')
        || !ai4seo_is_function_usable('is_wp_error')
    ) {
        return false;
    }

    $message = ai4seo_deep_sanitize($message);
    $message = ai4seo_deep_sanitize($message, 'wp_strip_all_tags');

    $upload_dir = wp_upload_dir();

    if (empty($upload_dir) || is_wp_error($upload_dir) || empty($upload_dir['basedir'])) {
        return false;
    }

    $log_directory = trailingslashit($upload_dir['basedir']);

    if (!wp_mkdir_p($log_directory)) {
        return false;
    }

    if (!wp_is_writable($log_directory)) {
        return false;
    }

    $log_file_path = $log_directory . 'ai-for-seo-debug.log';
    $timestamp = ai4seo_gmdate('Y-m-d H:i:s');
    $log_entry = '[' . $timestamp . '] ' . $message . PHP_EOL;

    if (!ai4seo_is_function_usable('file_put_contents')) {
        return false;
    }

    $result = file_put_contents($log_file_path, $log_entry, FILE_APPEND | LOCK_EX);

    if ($result === false) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Stores debug messages in the WordPress options table.
 *
 * @param int    $code      The error code.
 * @param string $message   The error message.
 * @param string $backtrace The backtrace content.
 * @return bool
 */
function ai4seo_store_debug_message_in_database(int $code, string $message, string $backtrace): bool {
    $existing_log_entries = get_option(AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array());
    $sanitized_message = ai4seo_deep_sanitize($message);
    $sanitized_message = ai4seo_deep_sanitize($sanitized_message, 'wp_strip_all_tags');
    $sanitized_backtrace = ai4seo_deep_sanitize($backtrace, 'wp_unslash');
    $sanitized_backtrace = ai4seo_deep_sanitize($sanitized_backtrace, 'wp_kses_post');

    if (!is_array($existing_log_entries)) {
        $existing_log_entries = array();
    }

    $existing_log_entries[] = array(
        'time' => time(),
        'code' => $code,
        'message' => $sanitized_message,
        'backtrace' => $sanitized_backtrace,
    );

    if (count($existing_log_entries) > 1000) {
        $existing_log_entries = array_slice($existing_log_entries, -1000);
    }

    return ai4seo_update_option(AI4SEO_DEBUG_MESSAGES_OPTION_NAME, $existing_log_entries, false, false);
}

// =========================================================================================== \\

/**
 * Collects debug messages for admin notice output.
 *
 * @param string $message The message to display.
 * @return bool
 */
function ai4seo_collect_debug_notice_message(string $message): bool {
    if (!ai4seo_can_display_debug_output_messages()) {
        return false;
    }

    global $ai4seo_debug_notice_messages;

    if (!is_array($ai4seo_debug_notice_messages)) {
        $ai4seo_debug_notice_messages = array();
    }

    $ai4seo_debug_notice_messages[] = $message;

    if (!has_action('admin_footer', 'ai4seo_render_debug_notice_messages')) {
        add_action('admin_footer', 'ai4seo_render_debug_notice_messages');
    }

    return true;
}

// =========================================================================================== \\

/**
 * Renders collected debug notice messages once in the admin footer.
 *
 * @return void
 */
function ai4seo_render_debug_notice_messages(): void {
    global $ai4seo_debug_notice_messages;

    if (!ai4seo_can_display_debug_output_messages()) {
        return;
    }

    if (empty($ai4seo_debug_notice_messages) || !is_array($ai4seo_debug_notice_messages)) {
        return;
    }

    $combined_message = implode('<br /><br />', $ai4seo_debug_notice_messages);

    echo "<div class='notice notice-error'>";
        echo "<p>";
            ai4seo_echo_wp_kses($combined_message);
        echo "</p>";
    echo "</div>";

    $ai4seo_debug_notice_messages = array();
}

// =========================================================================================== \\

/**
 * Outputs a formatted debug message using the selected debug output mode.
 *
 * @param int    $code          The error code identifier.
 * @param string $message       The error message.
 * @param bool   $add_traceback Whether to append a backtrace.
 * @return bool
 */
function ai4seo_debug_message(int $code, string $message, bool $add_traceback = false): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 99999)) {
        return false;
    }

    // sanitize message
    $message = wp_specialchars_decode( $message, ENT_QUOTES );
    $sanitized_message = sanitize_text_field($message);
    $sanitized_message = wp_strip_all_tags($sanitized_message);
    $sanitized_message = trim($sanitized_message);

    $debug_output_mode = ai4seo_get_setting(AI4SEO_SETTING_DEBUG_OUTPUT_MODE);
    $debug_output_mode_options = ai4seo_get_debug_output_mode_options();

    if (!array_key_exists($debug_output_mode, $debug_output_mode_options)) {
        $debug_output_mode = 'none';
    }

    if ($debug_output_mode === 'none') {
        return false;
    }

    // check if we can display notices or print_r outputs (not in cron/ajax)
    if (!ai4seo_can_display_debug_output_messages() && in_array($debug_output_mode, array('notice', 'print_r'), true)) {
        return false;
    }

    // build final message
    $sanitized_db_message = $sanitized_message;
    $combined_message = AI4SEO_PLUGIN_NAME . ': ' . $sanitized_message . ' (Code #' . $code;

    // add backtrace if requested
    $backtrace = '';

    if ($add_traceback) {
        $backtrace_separator = (in_array($debug_output_mode, array('print_r', 'notice', 'database'), true)) ? '<br />' : ' > ';

        if ($backtrace = ai4seo_get_backtrace_debug_message($backtrace_separator)) {
            $combined_message .= ', Backtrace:' . $backtrace_separator . $backtrace;
        }
    }

    $combined_message .= ')';

    switch ($debug_output_mode) {
        case 'error_log':
            error_log($combined_message);
            return true;
        case 'file':
            return ai4seo_append_debug_message_to_file($combined_message);
        case 'database':
            return ai4seo_store_debug_message_in_database($code, $sanitized_db_message, $backtrace);
        case 'notice':
            return ai4seo_collect_debug_notice_message($combined_message);
        case 'print_r':
            ai4seo_echo_wp_kses('<pre>' . $combined_message . '</pre>');
            return true;
        default:
            return false;
    }
}

// =========================================================================================== \\

/**
 * Convert any variable into a readable single-line string.
 * Similar to print_r( $var, true ), but compact and consistent.
 *
 * @param mixed $value Variable to stringify.
 *
 * @return string
 */
function ai4seo_stringify($value): string {
    if (is_array($value)) {
        $output = 'Array( ';

        $entries = array();

        foreach ($value as $key => $this_value) {
            if (is_array($this_value) || is_object($this_value)) {
                $entries[] = '[' . $key . '] => ' . ai4seo_stringify($this_value);
            } else {
                $entries[] = '[' . $key . '] => ' . var_export($this_value, true);
            }
        }

        $output .= implode(', ', $entries) . ' )';

        return $output;
    }

    if (is_object($value)) {
        $output = get_class($value) . ' Object( ';

        $entries = array();

        foreach (get_object_vars($value) as $property_name => $this_value) {
            if (is_array($this_value) || is_object($this_value)) {
                $entries[] = '[' . $property_name . '] => ' . ai4seo_stringify($this_value);
            } else {
                $entries[] = '[' . $property_name . '] => ' . var_export($this_value, true);
            }
        }

        $output .= implode(', ', $entries) . ' )';

        return $output;
    }

    return var_export($value, true);
}

// =========================================================================================== \\

function ai4seo_get_recommended_credits_pack_size_by_num_missing_entries(): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(978225511, 'Prevented loop', true);
        return 0;
    }

    $approximate_credits_needed = ai4seo_get_approximate_credits_needed();
    $credits_packs = ai4seo_get_credits_packs();

    // find the smallest credit pack size that is larger than the approximate credits needed
    // only consider first three entries
    $n = 0;
    foreach ($credits_packs AS $this_credits_pack) {
        $this_credits_amount = (int) $this_credits_pack["credits_amount"];
        $n++;

        if ($this_credits_amount >= $approximate_credits_needed) {
            return $this_credits_amount;
        }

        // we reached the third entry, return the current entry
        if ($n >= 3) {
            return $this_credits_amount;
        }
    }

    // fallback: return the smallest pack size
    $first_credits_pack = reset($credits_packs);
    return (int) ($first_credits_pack["credits_amount"] ?? 0);
}

// =========================================================================================== \\

function ai4seo_get_approximate_credits_needed() : int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(736466930, 'Prevented loop', true);
        return 0;
    }

    $approximate_credits_needed = 0;

    $num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

    if (!$num_missing_posts_by_post_type) {
        return 0;
    }

    $metadata_credits_cost_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
    $attachment_attributes_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

    foreach ($num_missing_posts_by_post_type AS $post_type => $num_missing_posts) {
        if ($post_type === 'attachment') {
            $approximate_credits_needed += $num_missing_posts * $attachment_attributes_cost_per_attachment_post;
        } else {
            $approximate_credits_needed += $num_missing_posts * $metadata_credits_cost_per_post;
        }
    }

    return $approximate_credits_needed;
}

// =========================================================================================== \\

function ai4seo_get_base64_from_image_file($image_url): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(697474987, 'Prevented loop', true);
        return array(
            "success" => false,
            "message" => "Infinite loop detected",
            "code" => 91234725
        );
    }

    // Use wp_safe_remote_get instead of file_get_contents for fetching remote files
    try {
        foreach (array('local_only', 'remote_only', 'download_only', 'safe_remote_only') AS $fetch_mode) {
            $image_body = ai4seo_get_remote_body($image_url, $fetch_mode);

            if (is_wp_error($image_body)) {
                continue;
            }

            if (!$image_body) {
                continue;
            }

            // Verify that the content is a valid image
            $is_probably_image = ai4seo_is_probably_image_content($image_body);

            if ($is_probably_image) {
                break;
            }
        }
    } catch (Exception $e) {
        return array(
            "success" => false,
            "message" => "Media URL not accessible: " . $e->getMessage(),
            "code" => 91324725
        );
    }

    if (is_wp_error($image_body)) {
        $remote_get_response_error = $image_body->get_error_message();

        return array(
            "success" => false,
            "message" => "Media URL not accessible: " . $remote_get_response_error,
            "code" => 101324725
        );
    }

    if (!$image_body) {
        return array(
            "success" => false,
            "message" => "Media content not accessible",
            "code" => 111324725
        );
    }

    if (!isset($is_probably_image['is_probably_image']) || !$is_probably_image['is_probably_image']) {
        return array(
            "success" => false,
            "message" => "The fetched content is not a valid image",
            "code" => 581927126
        );
    }

    // encode the attachment body to base64
    try {
        $attachment_base64 = ai4seo_smart_image_base64_encode($image_body);
    } catch (Exception $e) {
        return array(
            "success" => false,
            "message" => "Media content could not be base64 encoded: " . $e->getMessage(),
            "code" => 131324725
        );
    }

    if (!$attachment_base64) {
        return array(
            "success" => false,
            "message" => "Media content could not be base64 encoded",
            "code" => 141324725
        );
    }

    return array(
        "success" => true,
        "data" => $attachment_base64,
    );
}

// =========================================================================================== \\

/**
 * Determine whether given binary content is probably an image.
 *
 * This is a "best effort" check. It does not require GD/Imagick support for the format.
 *
 * @param string $binary_content Raw response body (binary).
 * @param string $content_type Optional HTTP Content-Type header value.
 * @return array {
 * @type bool $is_probably_image True if it looks like an image.
 * @type string $reason Short explanation.
 * @type string|null $detected_format Detected format (jpeg/png/gif/webp/avif/bmp/tiff/ico/svg) or null.
 * }
 */
function ai4seo_is_probably_image_content(string $binary_content, string $content_type = ''): array {
    if ($binary_content === '') {
        return array(
            'is_probably_image' => false,
            'reason' => 'empty_body',
            'detected_format' => null,
        );
    }

    // Quick header hint (if you have it).
    if ($content_type !== '') {
        $content_type_normalized = strtolower(trim(explode(';', $content_type)[0]));

        if (strpos($content_type_normalized, 'image/') === 0) {
            // Still validate signature below. Some CDNs return image/* for error placeholders.
        }
    }

    // 1) Verify with getimagesizefromstring (if available).
    if (function_exists('getimagesizefromstring')) {
        $image_size = @getimagesizefromstring($binary_content);

        if ($image_size) {
            $mime_type = $image_size['mime'] ?? '';
            return array('is_probably_image' => true, 'reason' => 'getimagesizefromstring', 'detected_format' => $mime_type );
        }
    }

    // 2) Magic bytes signature checks (most stable).
    $head = substr($binary_content, 0, 64);

    // JPEG: FF D8 FF
    if (substr($head, 0, 3) === "\xFF\xD8\xFF") {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'jpeg');
    }

    // PNG: 89 50 4E 47 0D 0A 1A 0A
    if (substr($head, 0, 8) === "\x89PNG\r\n\x1A\n") {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'png');
    }

    // GIF: GIF87a / GIF89a
    if (substr($head, 0, 6) === 'GIF87a' || substr($head, 0, 6) === 'GIF89a') {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'gif');
    }

    // WebP: RIFF....WEBP
    if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'webp');
    }

    // BMP: BM
    if (substr($head, 0, 2) === 'BM') {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'bmp');
    }

    // TIFF: II*\x00 or MM\x00*
    if (substr($head, 0, 4) === "II*\x00" || substr($head, 0, 4) === "MM\x00*") {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'tiff');
    }

    // ICO: 00 00 01 00
    if (substr($head, 0, 4) === "\x00\x00\x01\x00") {
        return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => 'ico');
    }

    // AVIF/HEIF family: look for 'ftyp' box + known brands.
    // Note: ISO BMFF has 'ftyp' typically within first bytes, but not always at offset 4 exactly.
    if (strpos($head, 'ftyp') !== false) {
        $brands = array('avif', 'avis', 'heic', 'heix', 'mif1', 'msf1');
        foreach ($brands as $brand) {
            if (strpos($head, $brand) !== false) {
                $detected = ($brand === 'avif' || $brand === 'avis') ? 'avif' : 'heif';
                return array('is_probably_image' => true, 'reason' => 'magic_bytes', 'detected_format' => $detected);
            }
        }
    }

    // 3) Optional: finfo MIME sniff (fallback).
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $mime = finfo_buffer($finfo, $binary_content);
            finfo_close($finfo);

            if (is_string($mime) && strpos(strtolower($mime), 'image/') === 0) {
                return array(
                    'is_probably_image' => true,
                    'reason' => 'finfo_mime',
                    'detected_format' => strtolower(substr($mime, 6)),
                );
            }
        }
    }

    // 4) As a last fallback: trust header if it says image/*.
    if ($content_type !== '') {
        $content_type_normalized = strtolower(trim(explode(';', $content_type)[0]));
        if (strpos($content_type_normalized, 'image/') === 0) {
            return array(
                'is_probably_image' => true,
                'reason' => 'content_type_only',
                'detected_format' => strtolower(substr($content_type_normalized, 6)),
            );
        }
    }

    return array(
        'is_probably_image' => false,
        'reason' => 'no_match',
        'detected_format' => null,
    );
}


// =========================================================================================== \\

/**
 * Fetch remote file contents with fallback strategies:
 * 1. wp_safe_remote_get (default)
 * 2. wp_safe_remote_get with 'sslverify' => false
 * 3. Local file access (if URL is local)
 * 4. download_url() fallback
 *
 * @param string $url The full URL of the media to fetch
 * @param string $attempt_type (Optional) Type of attempts to make ('all' by default)
 * @return string|WP_Error The file contents on success, or WP_Error on failure
 */
function ai4seo_get_remote_body(string $url, string $attempt_type = 'all') {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(416990059, 'Prevented loop', true);
        return '';
    }

    // Attempt 1: Standard remote fetch
    if ($attempt_type === 'remote_only' || $attempt_type === 'all') {
        $response = wp_safe_remote_get($url, array(
            'timeout' => 15,
            'redirection' => 5,
            'decompress' => true,
        ));

        if (!is_wp_error($response)) {
            return wp_remote_retrieve_body($response);
        }
    }

    // Attempt 2: Retry with sslverify disabled (less secure)
    if ($attempt_type === 'safe_remote_only' || $attempt_type === 'all') {
        $response = wp_safe_remote_get($url, array(
            'timeout' => 15,
            'redirection' => 5,
            'decompress' => true,
            'sslverify' => false,
        ));

        if (!is_wp_error($response)) {
            return wp_remote_retrieve_body($response);
        }
    }

    // Attempt 3: Try to resolve as a local file if URL is local
    if ($attempt_type === 'local_only' || $attempt_type === 'all') {
        $parsed_url = wp_parse_url($url);
        $site_url = wp_parse_url(site_url());

        if (isset($parsed_url['host'], $site_url['host']) && $parsed_url['host'] === $site_url['host']) {
            $relative_path = $parsed_url['path'] ?? '';
            $relative_path = str_replace($site_url['path'] ?? '', '', $relative_path); // strip subdirectory if any
            $local_path = ABSPATH . ltrim($relative_path, '/');

            if (file_exists($local_path)) {
                $contents = ai4seo_file_get_contents($local_path);

                if ($contents !== false) {
                    return $contents;
                }
            }
        }
    }

    // Attempt 4: Use download_url
    if ($attempt_type === 'download_only' || $attempt_type === 'all') {
        $temp_file = download_url($url);

        if (!is_wp_error($temp_file)) {
            $contents = ai4seo_file_get_contents($temp_file);

            wp_delete_file($temp_file); // Always clean up temp file

            if ($contents !== false) {
                return $contents;
            }
        }
    }

    // All attempts failed
    return new WP_Error('ai4seo_fetch_failed', 'Could not fetch media contents.');
}

// =========================================================================================== \\

/**
 * Safely measure the length of a string regardless of mbstring availability.
 *
 * @param string $string   String to measure.
 * @param string $encoding Optional encoding, defaults to UTF-8.
 * @return int             Length of the string.
 */
function ai4seo_mb_strlen(string $string, string $encoding = 'UTF-8'): int {
    if (function_exists('mb_strlen')) {
        try {
            return $encoding ? mb_strlen($string, $encoding) : mb_strlen($string);
        } catch (Throwable $e) {
            // fall back when mbstring throws (e.g. invalid encoding).
        }
    }

    if (function_exists('iconv_strlen')) {
        try {
            return $encoding ? iconv_strlen($string, $encoding) : iconv_strlen($string);
        } catch (Throwable $e) {
            // continue to basic strlen fallback.
        }
    }

    return strlen($string);
}

// =========================================================================================== \\

/**
 * Safely extract a substring regardless of mbstring availability.
 *
 * @param string      $string   Input string.
 * @param int         $start    Start position.
 * @param int|null    $length   Optional length.
 * @param string|null $encoding Optional encoding.
 * @return string               Extracted substring.
 */
function ai4seo_mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = 'UTF-8'): string {
    if (function_exists('mb_substr')) {
        try {
            return $encoding ? mb_substr($string, $start, $length, $encoding) : mb_substr($string, $start, $length);
        } catch (Throwable $e) {
            // fall back when mbstring throws (e.g. invalid encoding).
        }
    }

    if (function_exists('iconv_substr')) {
        try {
            return $encoding ? iconv_substr($string, $start, $length, $encoding) : iconv_substr($string, $start, $length);
        } catch (Throwable $e) {
            // continue to basic substr fallback.
        }
    }

    if ($length === null) {
        return substr($string, $start);
    }

    return substr($string, $start, $length);
}

// =========================================================================================== \\

/**
 * Safely locate substring position without requiring mbstring.
 *
 * @param string      $haystack Haystack string.
 * @param string      $needle   Needle to find.
 * @param int         $offset   Optional offset.
 * @param string|null $encoding Optional encoding.
 * @return int|false            Position or false when not found.
 */
function ai4seo_mb_strpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = 'UTF-8') {
    if (function_exists('mb_strpos')) {
        try {
            return $encoding ? mb_strpos($haystack, $needle, $offset, $encoding) : mb_strpos($haystack, $needle, $offset);
        } catch (Throwable $e) {
            // fall back when mbstring throws (e.g. invalid encoding).
        }
    }

    return strpos($haystack, $needle, $offset);
}

// =========================================================================================== \\

/**
 * Wrapper for file_get_contents() that gracefully falls back to the WP HTTP API or stream access.
 *
 * @param string   $path    Remote URL or local path.
 * @param resource $context Optional stream context (only used when native function available).
 * @return string|false     File contents on success, false on failure.
 */
function ai4seo_file_get_contents(string $path, $context = null) {
    $parsed_url = wp_parse_url($path);
    $scheme     = $parsed_url['scheme'] ?? '';

    // Remote URLs: use WP HTTP API.
    if (in_array($scheme, array('http', 'https'), true)) {
        $args = array(
            'timeout'     => 15,
            'redirection' => 5,
        );

        // Optional: if you used $context to set headers/options, you could map them to $args here.
        $response = wp_safe_remote_get( $path, $args );

        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    // Reject unknown schemes except file://
    if ($scheme && 'file' !== $scheme) {
        return false;
    }

    // Local path (file:// or plain path)
    $local_path = ('file' === $scheme) ? ($parsed_url['path'] ?? '') : $path;

    if (!$local_path) {
        return false;
    }

    // Use WP_Filesystem for local files.
    global $wp_filesystem;

    if (!$wp_filesystem) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if ($wp_filesystem) {
        $contents = $wp_filesystem->get_contents( $local_path );
        if (false !== $contents) {
            return $contents;
        }
    }

    // Fallback: direct file_get_contents (only if you want to keep it).
    if (ai4seo_is_function_usable('file_get_contents')) {
        try {
            // Fallback for environments where WP_Filesystem is unavailable or fails.
            // WP_Filesystem is used as the primary method above.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
            $contents = $context
                ? @file_get_contents($local_path, false, $context)
                : @file_get_contents($local_path);

            if (false !== $contents) {
                return $contents;
            }
        } catch (Throwable $e) {
            // continue
        }
    }

    return false;
}

// =========================================================================================== \\

/**
 * Safely call set_time_limit() when available.
 *
 * @param int $seconds Requested timeout.
 * @return bool True when the limit was adjusted, false otherwise.
 */
function ai4seo_safe_set_time_limit(int $seconds): bool {
    if (!ai4seo_is_function_usable('set_time_limit')) {
        return false;
    }

    try {
        set_time_limit($seconds);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// =========================================================================================== \\

/**
 * Lightweight alternative to get_option() using direct $wpdb access.
 *
 * This function:
 * - Reads the option directly from the options table.
 * - Returns the provided default if the option does not exist or a DB error occurs.
 * - Unserializes the stored value using maybe_unserialize().
 *
 * @param string $option_name Name of the option to retrieve.
 * @param mixed  $default     Optional. Default value to return if the option does not exist.
 *                            Default false.
 * @param bool   $use_direct_database_call Optional. Whether to bypass get_option() and query the database directly.
 *
 * @return mixed The option value if found, otherwise the default.
 */
function ai4seo_get_option(string $option_name, $default = false, bool $use_direct_database_call = true ) {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(663145060, 'Prevented loop', true);
        return '';
    }

    // If the caller explicitly wants to use get_option() instead of direct DB access, delegate to it.
    if (!$use_direct_database_call) {
        return get_option($option_name, $default);
    }

    if ( ! isset( $wpdb ) || ! $wpdb ) {
        return $default;
    }

    $option_name = trim( $option_name );

    if ( $option_name === '' ) {
        return $default;
    }

    try {
        // Directly query the options table for this specific option.
        $option_value_serialized = $wpdb->get_var(
                $wpdb->prepare(
                        "SELECT option_value 
             FROM {$wpdb->options} 
             WHERE option_name = %s 
             LIMIT 1",
                        $option_name
                )
        );

        if ($wpdb->last_error) {
            ai4seo_debug_message(984321672, 'Database error: ' . $wpdb->last_error, true);
            return $default;
        }
    } catch ( Exception $exception ) {
        // In case of DB error, fall back to the default.
        return $default;
    }

    // If no row was found, return default.
    if ( $option_value_serialized === null ) {
        return $default;
    }

    // Unserialize if needed and return.
    return maybe_unserialize( $option_value_serialized );
}

// =========================================================================================== \\

/**
 * Update or insert an option using direct $wpdb access.
 *
 * This function behaves similar to update_option(), but bypasses the core
 * update_option() internals and writes directly to the options table.
 *
 * - Inserts the option if it does not exist.
 * - Updates the option if it exists and the value has changed.
 * - Returns false if the value is unchanged or on failure.
 * - Clears the options cache so get_option() sees the new value.
 *
 * @param string $option_name   Name of the option to update.
 * @param mixed      $option_value  Value to store. Will be maybe_serialize()'d.
 * @param string|bool|null $autoload Optional. Whether to load the option when WordPress starts up.
 *                                   Accepts 'yes', 'no', true, false, or null.
 *                                   Null keeps existing autoload or defaults to 'yes' on insert.
 * @param bool $use_direct_database_call Optional. Whether to bypass update_option() and query the database directly.
 *
 * @return bool True if the option value was changed or added, false otherwise.
 */
function ai4seo_update_option(string $option_name, $option_value, $autoload = false, bool $use_direct_database_call = true): bool {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(635985897, 'Prevented loop', true);
        return false;
    }

    // If the caller explicitly wants to use update_option() instead of direct DB access, delegate to it.
    if (!$use_direct_database_call) {
        return update_option($option_name, $option_value, $autoload);
    }

    if ( ! isset( $wpdb ) || ! $wpdb ) {
        return false;
    }

    $option_name = trim( $option_name );

    if ( $option_name === '' ) {
        return false;
    }

    // Use ai4seo_get_option() with a distinct default so we can detect non-existent options.
    $old_value = ai4seo_get_option( $option_name, null, $use_direct_database_call );

    // Normalize new vs old for comparison using serialization, matching core semantics.
    $serialized_new = maybe_serialize( $option_value );
    $serialized_old = ( $old_value === null ) ? null : maybe_serialize( $old_value );

    // If option exists and the value is identical, do nothing (same as update_option()).
    if ( $old_value !== null && $serialized_new === $serialized_old ) {
        return true;
    }

    // Read the current row so we can preserve or inspect autoload and existence.
    try {
        #ai4seo_debug_message(984321671, "Existing value: " . ai4seo_stringify($old_value) . ", New value: " . ai4seo_stringify($option_value), true);
        $existing_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT option_id, option_value, autoload 
                 FROM {$wpdb->options} 
                 WHERE option_name = %s 
                 LIMIT 1",
                $option_name
            ),
            ARRAY_A
        );

        if ($wpdb->last_error) {
            ai4seo_debug_message(984321673, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }
    } catch (Throwable $e) {
        return false;
    }

    $is_insert = ( $existing_row === null );

    // Resolve autoload value.
    if ( $autoload === null ) {
        if ( $is_insert === false && isset( $existing_row['autoload'] ) && $existing_row['autoload'] !== '' ) {
            $autoload = $existing_row['autoload'];
        } else {
            // Default autoload behavior in WordPress is 'yes' for new options.
            $autoload = 'yes';
        }
    } else {
        // Normalize autoload to 'yes' / 'no'.
        if ( $autoload === 'no' || $autoload === false || ( is_string( $autoload ) && strtolower( $autoload ) === 'no' ) ) {
            $autoload = 'no';
        } else {
            $autoload = 'yes';
        }
    }

    // Perform insert or update via $wpdb.
    try {
        if ($is_insert === true) {
            $result = $wpdb->insert(
                $wpdb->options,
                array(
                    'option_name' => $option_name,
                    'option_value' => $serialized_new,
                    'autoload' => $autoload,
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                )
            );

            if ($result === false) {
                return false;
            }

            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321674, 'Database error: ' . $wpdb->last_error, true);
                return false;
            }
        } else {
            $result = $wpdb->update(
                $wpdb->options,
                array(
                    'option_value' => $serialized_new,
                    'autoload' => $autoload,
                ),
                array(
                    'option_name' => $option_name,
                ),
                array(
                    '%s',
                    '%s',
                ),
                array(
                    '%s',
                )
            );

            // $result can be 0 if nothing changed on DB-level, but we already filtered that above.
            if ($result === false) {
                return true;
            }

            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321675, 'Database error: ' . $wpdb->last_error, true);
                return false;
            }
        }

        // Clear caches so core get_option() stays consistent.
        // wp_cache_delete( $option_name, 'options' );
        // wp_cache_delete( 'alloptions', 'options' );
        // wp_cache_delete( 'notoptions', 'options' );
    } catch (Throwable $e) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Delete an option using direct $wpdb access.
 *
 * This function:
 * - Deletes the option row directly from the options table.
 * - Returns true when at least one row was removed, false otherwise.
 * - Wraps all $wpdb operations in a try/catch block.
 * - Clears the options cache so get_option() and friends stay in sync.
 *
 * No hooks or actions are triggered.
 *
 * @param string $option_name Name of the option to delete.
 * @param bool $use_direct_database_call Optional. Whether to bypass delete_option() and query the database directly.
 *
 * @return bool True if the option was deleted, false on failure or if it did not exist.
 */
function ai4seo_delete_option(string $option_name, bool $use_direct_database_call = true): bool {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(980160314, 'Prevented loop', true);
        return false;
    }

    if (!$use_direct_database_call) {
        return delete_option($option_name);
    }

    if ( ! isset( $wpdb ) || ! $wpdb ) {
        return false;
    }

    $option_name = trim( $option_name );

    if ( $option_name === '' ) {
        return false;
    }

    try {
        // Delete the option row directly from the options table.
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s",
                $option_name
            )
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321676, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }
    } catch ( Exception $exception ) {
        // On DB error, indicate failure.
        return false;
    }

    // If query failed or no rows were affected, return true.
    if ( $result === false || (int) $result === 0 ) {
        return true;
    }

    // Clear caches so core get_option() stays consistent.
    // wp_cache_delete( $option_name, 'options' );
    // wp_cache_delete( 'alloptions', 'options' );
    // wp_cache_delete( 'notoptions', 'options' );

    return true;
}


// endregion
// ___________________________________________________________________________________________ \\
// region SEMAPHORES ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Acquire a semaphore for a critical section name.
 * Polls every 0.1s up to 5s. Auto-releases on shutdown.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return bool True on success, false on timeout.
 */
function ai4seo_acquire_semaphore(string $critical_section_name ): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(503298098, 'Prevented loop', true);
        return false;
    }

    ai4seo_register_semaphore_shutdown_handler();

    $option_key = ai4seo_get_semaphore_option_key( $critical_section_name );
    $token      = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : ( uniqid( 'ai4seo_', true ) );

    $deadline   = microtime( true ) + (float) AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS;
    $interval   = (int) max( 1, floor( (float) AI4SEO_SEMAPHORE_POLL_INTERVAL_SECONDS * 1_000_000 ) ); // microseconds

    while ( microtime( true ) < $deadline ) {
        // Fast path: create lock if not set.
        if ( ai4seo_try_create_lock( $option_key, $token ) === true ) {
            // Remember to release on shutdown.
            $GLOBALS['ai4seo_held_semaphores'][ $option_key ] = $token;
            return true;
        }

        // If set, check staleness and reclaim if stale.
        $existing = ai4seo_get_option( $option_key );

        if ( false !== $existing && ai4seo_is_lock_stale( $existing ) === true ) {
            // Remove stale lock and try again immediately.
            ai4seo_delete_option( $option_key );
            // Next loop iteration will try to acquire again.
        }

        // Wait before next attempt.
        usleep( $interval );
    }

    return false;
}

// =========================================================================================== \\

/**
 * Release a previously acquired semaphore.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return bool True if released (or not present), false if lock belongs to someone else or was not held.
 */
function ai4seo_release_semaphore(string $critical_section_name ): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(641172961, 'Prevented loop', true);
        return false;
    }

    $option_key = ai4seo_get_semaphore_option_key( $critical_section_name );

    $token = isset( $GLOBALS['ai4seo_held_semaphores'][ $option_key ] )
        ? (string) $GLOBALS['ai4seo_held_semaphores'][ $option_key ]
        : '';

    if ( '' === $token ) {
        // We do not believe we hold this lock; do a safe attempt anyway.
        $existing = ai4seo_get_option( $option_key );

        if ( false === $existing ) {
            return true;
        }

        return false; // Someone else holds it.
    }

    $released = ai4seo_release_semaphore_by_key_and_token( $option_key, $token );

    // Clean local map regardless.
    unset( $GLOBALS['ai4seo_held_semaphores'][ $option_key ] );

    return $released;
}

// =========================================================================================== \\

/**
 * Build the option key for a semaphore name.
 *
 * @param string $critical_section_name Critical section identifier.
 * @return string Option key.
 */
function ai4seo_get_semaphore_option_key(string $critical_section_name ): string {
    $normalized = sanitize_key( (string) $critical_section_name );

    if ( '' === $normalized ) {
        // Keep key length safe and deterministic even if empty after sanitize_key().
        $normalized = 'empty';
    }

    // Option name length safety with original hash.
    $hash = md5( $normalized );
    return 'ai4seo_sem_' . $normalized . '_' . $hash;
}

// =========================================================================================== \\

/**
 * Ensure we always release held semaphores on shutdown.
 *
 * @return void
 */
function ai4seo_register_semaphore_shutdown_handler() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    register_shutdown_function(
        static function () {
            if ( empty( $GLOBALS['ai4seo_held_semaphores'] ) || ! is_array( $GLOBALS['ai4seo_held_semaphores'] ) ) {
                return;
            }

            foreach ( $GLOBALS['ai4seo_held_semaphores'] as $option_key => $token ) {
                // Best-effort release. Ignore result.
                ai4seo_release_semaphore_by_key_and_token( $option_key, $token );
            }

            // Reset map to avoid double work if shutdown functions chain.
            $GLOBALS['ai4seo_held_semaphores'] = array();
        }
    );
}

// =========================================================================================== \\

/**
 * Try to create the lock atomically.
 *
 * @param string $option_key Option key.
 * @param string $token      Unique token for the holder.
 * @return bool True if created, false otherwise.
 */
function ai4seo_try_create_lock(string $option_key, string $token ): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(511053771, 'Prevented loop', true);
        return false;
    }

    $payload = array(
        'token'      => (string) $token,
        'started_at' => time(),
    );

    // Atomic when the option does not yet exist.
    // Do not autoload.
    return aa_option( $option_key, $payload, '', 'no' );
}

// =========================================================================================== \\

/**
 * Return true if the existing lock is stale by TTL.
 *
 * @param array $payload Stored payload.
 * @return bool
 */
function ai4seo_is_lock_stale(array $payload ): bool {
    $started_at = isset( $payload['started_at'] ) ? (int) $payload['started_at'] : 0;

    if ( $started_at <= 0 ) {
        return true;
    }

    return ( time() - $started_at ) > (int) AI4SEO_SEMAPHORE_TTL_SECONDS;
}

// =========================================================================================== \\

/**
 * Release by option key and token. Internal helper.
 *
 * @param string $option_key Option key.
 * @param string $token      Token we expect to hold.
 * @return bool True if released or not present, false if held by someone else.
 */
function ai4seo_release_semaphore_by_key_and_token(string $option_key, string $token ): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(255831310, 'Prevented loop', true);
        return false;
    }

    $existing = ai4seo_get_option( $option_key );

    if ( false === $existing ) {
        return true; // Already gone.
    }

    $existing_token = is_array( $existing ) && isset( $existing['token'] ) ? (string) $existing['token'] : '';

    if ( $existing_token !== (string) $token ) {
        // Do not release another holder’s lock.
        return false;
    }

    ai4seo_delete_option( $option_key );
    return true;
}


// endregion
// ___________________________________________________________________________________________ \\
// region POSTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Return a robust Post/Page ID for the current request.
 *
 * Default: prefer the main queried object (stable even with secondary loops).
 * Falls back to the loop's global $post when requested.
 *
 * Usage: replace get_the_ID() with ai4seo_get_post_id().
 *
 * @since 2.1.4
 *
 * @param array $args {
 *     Optional behavior flags.
 *
 *     @type string $prefer   'primary' or 'loop'. Default 'primary'.
 *                            - 'primary': use main query / queried object.
 *                            - 'loop'   : use global $post first, then primary.
 *     @type string $fallback 'loop' or '0'. Default 'loop'.
 *                            What to return if no primary ID is found.
 * }
 * @return int Post ID or 0.
 */
function ai4seo_get_current_post_id(array $args = array() ): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(528983587, 'Prevented loop', true);
        return 0;
    }

    $args = wp_parse_args(
        $args,
        array(
            'prefer'   => 'primary',
            'fallback' => 'loop',
        )
    );

    // Per-request manual override stack. Use push/pop helpers below.
    static $ai4seo_post_context_stack = array();

    // If an override is active, honor it.
    if ( ! empty( $ai4seo_post_context_stack ) ) {
        $override_id = (int) end( $ai4seo_post_context_stack );

        if ( $override_id > 0 ) {
            /**
             * Filter: allow last-chance override of the manually pushed ID.
             *
             * @param int   $override_id
             * @param array $args
             */
            return (int) apply_filters( 'ai4seo_post_id_overridden', $override_id, $args );
        }
    }

    // Cache the computed "primary" ID to avoid repeated work.
    static $ai4seo_cached_primary_id = null;

    // Helper to compute the primary (main-queried) ID once.
    $compute_primary = static function () {
        // Not for wp-admin screens (except AJAX). Keep predictable.
        if ( is_admin() && ! wp_doing_ajax() ) {
            return 0;
        }

        $post_id = 0;

        $queried = get_queried_object();
        if ( $queried instanceof WP_Post ) {
            $post_id = (int) $queried->ID;
        } else {
            // Static "Posts page" when set and we are on the blog index.
            if ( is_home() && ! is_front_page() ) {
                $page_for_posts = (int) ai4seo_get_option( 'page_for_posts' );
                if ( $page_for_posts > 0 ) {
                    $post_id = $page_for_posts;
                }
            }

            // Static "Front page" when set.
            if ( 0 === $post_id && is_front_page() ) {
                $page_on_front = (int) ai4seo_get_option( 'page_on_front' );
                if ( $page_on_front > 0 ) {
                    $post_id = $page_on_front;
                }
            }

            // WooCommerce shop archive maps to a Page ID.
            if ( 0 === $post_id && function_exists( 'is_shop' ) && is_shop() ) {
                $shop_id = (int) ai4seo_get_option( 'woocommerce_shop_page_id' );
                if ( $shop_id > 0 ) {
                    $post_id = $shop_id;
                }
            }
        }

        // Resolve previews that point to a revision.
        if ( $post_id > 0 ) {
            $maybe_parent = wp_is_post_revision( $post_id );
            if ( $maybe_parent ) {
                $post_id = (int) $maybe_parent;
            }
        }

        /**
         * Filter the detected primary post ID for the current request.
         *
         * @param int $post_id
         */
        return (int) apply_filters( 'ai4seo_primary_post_id', $post_id );
    };

    // Compute or read the cached primary ID.
    if ( null === $ai4seo_cached_primary_id ) {
        $ai4seo_cached_primary_id = $compute_primary();
    }

    // Optionally use the loop's current post first.
    if ( 'loop' === $args['prefer'] ) {
        $loop_id = 0;
        /** @var WP_Post|null $post */
        global $post;
        if ( $post instanceof WP_Post ) {
            $loop_id = (int) $post->ID;
        }
        if ( $loop_id > 0 ) {
            return (int) apply_filters( 'ai4seo_post_id_loop_preferred', $loop_id, $args );
        }
        // Fall through to primary if loop ID not available.
    }

    // Prefer primary.
    if ( $ai4seo_cached_primary_id > 0 ) {
        return (int) $ai4seo_cached_primary_id;
    }

    // Fallback strategy.
    if ( 'loop' === $args['fallback'] ) {
        $loop_id = 0;
        /** @var WP_Post|null $post */
        global $post;
        if ( $post instanceof WP_Post ) {
            $loop_id = (int) $post->ID;
        }
        if ( $loop_id > 0 ) {
            return (int) $loop_id;
        }
    }

    return 0;
}

// =========================================================================================== \\

/**
 * Push a temporary post context ID.
 * Call before entering a custom loop; pair with ai4seo_pop_post_context().
 *
 * @param int $post_id
 * @return void
 *@since 2.1.4
 */
function ai4seo_push_post_context( int $post_id ) {
    static $ai4seo_post_context_stack = array(); // same static as above by function scope.
    $ai4seo_post_context_stack[]       = (int) $post_id;
}

// =========================================================================================== \\

/**
 * Pop the last pushed post context ID.
 *
 * @since 2.1.4
 * @return void
 */
function ai4seo_pop_post_context() {
    static $ai4seo_post_context_stack = array(); // same static as above by function scope.
    if ( ! empty( $ai4seo_post_context_stack ) ) {
        array_pop( $ai4seo_post_context_stack );
    }
}

// =========================================================================================== \\

/**
 * Returns all supported post types for this wordpress setup
 * @param bool $apply_user_setting Whether to filter out user-disabled post types.
 * @return array The supported post types
 */
function ai4seo_get_supported_post_types( bool $apply_user_setting = true ): array {
    global $ai4seo_cached_supported_post_types;
    global $ai4seo_checked_supported_post_types;
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(327975848, 'Prevented loop', true);
        return array();
    }

    $publicly_accessible_post_types = ai4seo_get_publicly_accessible_post_types();

    $check_this_post_types = array_keys( $publicly_accessible_post_types );
    $check_this_post_types = ai4seo_deep_sanitize( $check_this_post_types, 'sanitize_key' );

    // go through supported_post_types and remove those we already found in $ai4seo_checked_supported_post_types
    if ( is_array( $ai4seo_checked_supported_post_types ) && ! empty( $ai4seo_checked_supported_post_types ) ) {
        $check_this_post_types = array_diff( $check_this_post_types, $ai4seo_checked_supported_post_types );
    }

    if ( ! $check_this_post_types ) {
        $supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
    } else {
        // add entries to checked supported post types
        $ai4seo_checked_supported_post_types = array_merge( (array) $ai4seo_checked_supported_post_types, $check_this_post_types );

        // Keep existing behavior (require at least one post). If you want empty CPTs too, replace the DB query with $check_this_post_types.
        // Hard cap post types to 256 entries to avoid oversized IN(...) clauses.
        $check_this_post_types = array_slice( $check_this_post_types, 0, 256 );

        if (ai4seo_is_environmental_variable_cache_available(AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE)) {
            $supported_post_types_from_database = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE);
        } else {
            $placeholders = implode( ', ', array_fill( 0, count( $check_this_post_types ), '%s' ) );

            $sql = $wpdb->prepare(
                "SELECT DISTINCT post_type 
                 FROM {$wpdb->posts} 
                 WHERE post_type IN ($placeholders) 
                 AND post_status IN ('publish', 'future') 
                 LIMIT 100",
                ...$check_this_post_types
            );

            $supported_post_types_from_database = $wpdb->get_col( $sql );

            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321677, 'Database error: ' . $wpdb->last_error, true);
                $supported_post_types_from_database = array();
            } else {
                ai4seo_update_environmental_variable(
                    AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE,
                    $supported_post_types_from_database,
                    true,
                    HOUR_IN_SECONDS
                );
            }
        }

        if ( ! $supported_post_types_from_database ) {
            $supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
        } else {
            // sanitize the supported post types from database
            $supported_post_types_from_database = ai4seo_deep_sanitize( $supported_post_types_from_database, 'sanitize_key' );

            // add $ai4seo_cached_supported_post_types to supported post types
            $ai4seo_cached_supported_post_types = array_merge( (array) $ai4seo_cached_supported_post_types, $supported_post_types_from_database );
            $ai4seo_cached_supported_post_types = array_values( array_unique( $ai4seo_cached_supported_post_types ) );

            // order the post types
            sort( $ai4seo_cached_supported_post_types );

            $supported_post_types = $ai4seo_cached_supported_post_types;
        }
    }

    if ( ! isset( $supported_post_types ) ) {
        $supported_post_types = is_array( $ai4seo_cached_supported_post_types ) ? $ai4seo_cached_supported_post_types : array();
    }

    if ( ! $apply_user_setting ) {
        return $supported_post_types;
    }

    // check active meta tags
    $ai4seo_active_meta_tags = ai4seo_get_active_meta_tags();

    if (!$ai4seo_active_meta_tags) {
        return array();
    }

    // check disabled post types
    $disabled_post_types = ai4seo_get_setting( AI4SEO_SETTING_DISABLED_POST_TYPES );

    if ( ! is_array( $disabled_post_types ) ) {
        $disabled_post_types = array();
    } else {
        $disabled_post_types = ai4seo_deep_sanitize( $disabled_post_types, 'sanitize_key' );
    }

    if ( empty( $disabled_post_types ) ) {
        return $supported_post_types;
    }

    $supported_post_types = array_values( array_diff( $supported_post_types, $disabled_post_types ) );

    return $supported_post_types;
}

// =========================================================================================== \\

/**
 * @param $post_id int The ID of the post to get the pure text content for.
 * @param $debug bool Whether to enable debug mode (default: false).
 * @return string The pure text content of the post.
 */
function ai4seo_get_condensed_post_content_from_database(int $post_id, bool $debug = false): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(561711889, 'Prevented loop', true);
        return '';
    }

    // Retrieve the post object
    $post = get_post($post_id);

    if (!$post) {
        return ''; // Return empty if post is not found
    }

    // Get the post content
    $post_content = ai4seo_get_combined_post_content($post_id, "", $debug);

    // condense the post content
    ai4seo_condense_raw_post_content($post_content);

    if ($debug) {
        ai4seo_debug_message(614595339, "FINAL POST CONTENT (condensed) >" . ai4seo_stringify(htmlspecialchars($post_content)));
    }

    return $post_content;
}

// =========================================================================================== \\

/**
 * Returns the post content to a given post_id by also reading the content of the most common page builders and
 * combining them into one content
 * @param int $post_id The post or page id to read the content from
 * @param string $editor_identifier The identifier of the editor to read the content from
 * @param bool $debug Whether to enable debug mode (default: false)
 * @return false|string The post or page content or false if the post_id is empty
 */
function ai4seo_get_combined_post_content(int $post_id = 0, string $editor_identifier = "", bool $debug = false) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(482499702, 'Prevented loop', true);
        return false;
    }

    // Define variables for the current theme and the parent theme
    $current_theme = wp_get_theme();
    $parent_theme = $current_theme->parent();

    // Read post-id if it is not numeric
    if (empty($post_id)) {
        // Get post- or page-id
        $post_id = ai4seo_get_current_post_id();
    }

    if (empty($post_id)) {
        return false;
    }

    // Get post-object
    $post = get_post($post_id);

    // Define variable for the combined post- or page-content
    $combined_content = array();

    // Get post-content
    $post_content = $post->post_content;

    // apply short codes
    $post_content = do_shortcode($post_content);

    // Return post-content if not empty and not the same as the post-title or post-excerpt
    if (!empty($post_content)) {
        if ($debug) {
            ai4seo_debug_message(369296244, "POST CONTENT >" . ai4seo_stringify(htmlspecialchars($post_content)));
        }

        $combined_content[] = trim($post_content);
    }

    // check if is_plugin_active() is available
    $plugins_are_loaded = function_exists('is_plugin_active');

    // Elementor: only if the post_content got less than 100 characters, as the post_content should contain even a clearer version of the content
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "elementor") && is_plugin_active("elementor/elementor.php")) {
        // Get elementor-content
        $elementor_content = get_post_meta($post_id, "_elementor_data", true);

        // Return elementor-content if not empty
        if (!empty($elementor_content)) {
            if ($debug) {
                ai4seo_debug_message(264856340, "ELEMENTOR CONTENT>" . ai4seo_stringify(htmlspecialchars($elementor_content)));
            }

            $combined_content[] = trim($elementor_content);
        }
    }

    // Check if muffin-builder-plugin is active. If yes, only consider it's content as it's the content that is shown on the page
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "mfn-builder") && ($current_theme->get("Name") === "Betheme"
            || ($parent_theme && $parent_theme->get("Name") === "Betheme"))) {
        // Get muffin-builder-content
        $muffin_builder_content = get_post_meta($post_id, "mfn-page-items-seo", true);

        // Return muffin-builder-content if not empty
        if (!empty($muffin_builder_content)) {
            if ($debug) {
                ai4seo_debug_message(288886929, "MUFFIN BUILDER CONTENT>" . ai4seo_stringify(htmlspecialchars($muffin_builder_content)));
            }

            $combined_content[] = trim($muffin_builder_content);
        }
    }

    // Check if beaver-builder-plugin is active
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "fl-builder") && is_plugin_active("beaver-builder-lite-version/fl-builder.php")) {
        // Get beaver-builder-content
        $beaver_builder_content = get_post_meta($post_id, "_fl_builder_data", true);

        // Return beaver-builder-content if not empty
        if (!empty($beaver_builder_content)) {
            if ($debug) {
                ai4seo_debug_message(899351813, "BEAVER BUILDER CONTENT>" . ai4seo_stringify(htmlspecialchars($beaver_builder_content)));
            }

            $combined_content[] = trim($beaver_builder_content);
        }
    }

    // Check if divi-builder-plugin is active
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "divi-builder") && is_plugin_active("divi-builder/divi-builder.php")) {
        // Get divi-builder-content
        $divi_builder_content = get_post_meta($post_id, "_et_pb_use_builder", true);

        // Return divi-builder-content if not empty
        if (!empty($divi_builder_content)) {
            if ($debug) {
                ai4seo_debug_message(412179553, "DIVI BUILDER CONTENT>" . ai4seo_stringify(htmlspecialchars($divi_builder_content)));
            }

            $combined_content[] = trim($divi_builder_content);
        }
    }

    // Check if oxygen-plugin is active
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "oxygen") && is_plugin_active("oxygen/functions.php")) {
        // Get oxygen-content
        $oxygen_content = get_post_meta($post_id, "ct_builder_shortcodes", true);

        // Return oxygen-content if not empty
        if (!empty($oxygen_content)) {
            if ($debug) {
                ai4seo_debug_message(528624552, "OXYGEN CONTENT>" . ai4seo_stringify(htmlspecialchars($oxygen_content)));
            }

            $combined_content[] = trim($oxygen_content);
        }
    }

    // Check if brizy-plugin is active
    if ($plugins_are_loaded && (!$editor_identifier || $editor_identifier == "brizy") && is_plugin_active("brizy/brizy.php")) {
        // Get brizy-content
        $brizy_content = get_post_meta($post_id, "brizy_post_uid", true);

        // Return brizy-content if not empty
        if (!empty($brizy_content)) {
            if ($debug) {
                ai4seo_debug_message(344263270, "BRIZY CONTENT>" . ai4seo_stringify(htmlspecialchars($brizy_content)));
            }

            $combined_content[] = trim($brizy_content);
        }
    }

    // Fallback -> wp_remote_get the post content
    if (empty($combined_content) || strlen(implode("", $combined_content)) < AI4SEO_TOO_SHORT_CONTENT_LENGTH) {
        // Get the post content from the remote URL
        $post_permalink = get_permalink($post_id);

        try {
            $remote_content = ai4seo_get_remote_body($post_permalink);
        } catch (Exception $e) {
            $remote_content = new WP_Error('remote_content_error', 'Error fetching remote content: ' . $e->getMessage());
        }

        // If remote content is not an error, add it to the combined content
        if (!is_wp_error($remote_content) && !empty($remote_content)) {
            $remote_content = "(ATTENTION: RAW HTML BODY — MAY NOT ACCURATELY REPRESENT THE FULL OR ACTUAL PAGE CONTENT. PLEASE INTERPRET WITH CAUTION AND RELY HEAVILY ON CONTEXTUAL CLUES): " . $remote_content;

            if ($debug) {
                ai4seo_debug_message(823132530, "REMOTE CONTENT>" . ai4seo_stringify(htmlspecialchars($remote_content)));
            }

            $combined_content = array($remote_content);
        }
    }

    $combined_content = implode(" ", $combined_content);

    // Apply the 'the_content' filter to the post content
    $filtered_combined_content = apply_filters('the_content', $combined_content);

    if ($filtered_combined_content && strlen($filtered_combined_content) > $combined_content) {
        if ($debug) {
            ai4seo_debug_message(859196742, "FILTERED COMBINED CONTENT>" . ai4seo_stringify(htmlspecialchars($filtered_combined_content)));
        }

        $combined_content = $filtered_combined_content;
    }

    return $combined_content;
}

// =========================================================================================== \\

/**
 * Condenses the raw content to a more readable and useful format for the api
 * @param $content string The raw content to condense
 * @param $soft_cap int Consider at least this many characters before truncating
 * @param $hard_cap int Truncate the content to this length if no sentence end is found
 */
function ai4seo_condense_raw_post_content(string &$content, int $soft_cap = 2000, int $hard_cap = 2250) {
    global $shortcode_tags;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(528878491, 'Prevented loop', true);
        return;
    }

    // workaround for ACF blocks, as content for ACF blocks are defined inside <!-- wp:acf/... --> tags
    if (ai4seo_is_acf_content($content)) {
        $content .= ai4seo_extract_acf_content($content);
    }

    // Remove <style> and <script> tags and their content
    $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
    $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);

    // Remove HTML comments
    $content = preg_replace('/<!--(.*?)-->/', '', $content);

    // Remove CSS/JS comments
    $content = preg_replace('/\/\*(.*?)\*\//', '', $content);

    // replace \/ with /
    $content = str_replace('\/', '/', $content);
    $content = str_replace('\'', "'", $content);

    // remove icons ("icon-lamp")
    $content = preg_replace('/icon-[a-z0-9-]+/', '', $content);

    // remove shortcodes like [vc_row1]
    $content = preg_replace('/\[[a-zA-Z0-9_]+(\]|$)/', '', $content);

    // Remove opening vc_ shortcodes
    $content = preg_replace('/\[vc_[^\]]+(\]|$)/', '', $content);

    // Remove closing vc_ shortcodes
    $content = preg_replace('/\[\/vc_[^\]]+(\]|$)/', '', $content);

    // handle $shortcode_tags
    $shortcodes = array_keys($shortcode_tags);

    if ($shortcodes) {
        foreach ($shortcodes as $shortcode) {
            $content = preg_replace('/\[' . $shortcode . '[^\]]*\]/', '', $content);
            $content = preg_replace('/\[\/' . $shortcode . '[^\]]*\]/', '', $content);
        }
    }

    // Remove all HTML tags
    $content = wp_strip_all_tags($content);

    // remove all URLs
    $content = ai4seo_remove_urls_from_string($content);

    // Replace multiple spaces with a single space and trim whitespace
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);

    // remove be-builder progress bar infos (50 10 #72a5d8)
    $content = preg_replace('/[0-9]+ [0-9]+ #[a-f0-9]+/', '', $content);
    $content = preg_replace('/[0-9]+ [0-9]+ (grey|gray|red|green|blue|yellow|orange|purple|pink|black|white)/', '', $content);

    // Decode HTML entities and handle common entities separately
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Handle common entities that might not be converted
    $content = str_replace(['&nbsp;', '&amp;', '&quot;', '&#39;', '&lt;', '&gt;', '&;', '\u2019', 'â€™', 'â€', 'â€³', '€™t', '\u201d', '\u003cli>', '\u2013'], [' ', '&', '"', "'", '<', '>', '\'', '\'', '\'', '"', '"', '\'', '"', '- ', '–'], $content);

    // Replace multiple spaces with a single space and trim whitespace
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);

    // remove remaining short tags with all kinds of [ - ] combinations,
    // but only apply the changes if we have at least AI4SEO_TOO_SHORT_CONTENT_LENGTH chars left
    $temp_content = preg_replace('/\[.*?\]/', '', $content);

    if ($content != $temp_content && ai4seo_mb_strlen($temp_content) >= AI4SEO_TOO_SHORT_CONTENT_LENGTH) {
        $content = $temp_content;

        // Replace multiple spaces with a single space and trim whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
    }

    // remove double sentences
    $content = ai4seo_remove_double_sentences($content);

    // truncate sentence
    $content = ai4seo_truncate_sentence($content, $soft_cap, $hard_cap);
}

// =========================================================================================== \\

function ai4seo_add_post_context($post_id, &$content) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(275721265, 'Prevented loop', true);
        return;
    }

    $context = "";

    // ADD WEBSITE CONTEXT
    $about_this_website = ai4seo_get_website_context();
    $context .= "WEBSITE: {$about_this_website} | ";

    // ADD POST ENTRY CONTEXT
    $context .= "SUB PAGE: ";

    // url
    $post_url = get_permalink($post_id);

    if ($post_url) {
        $context .= "URL: '" . $post_url . "'. ";
    }

    // post type
    $post_type = get_post_type($post_id);

    if ($post_type) {
        $context .= "WordPress Post Type: '" . $post_type . "'. ";
    }

    // categories
    $post_categories = get_the_category($post_id);

    if ($post_categories) {
        $category_names = array_map(function($category) {
            return $category->name;
        }, $post_categories);
        $context .= "Category: '" . implode(", ", $category_names) . "'. ";
    }

    // woocommerce context
    if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE) && function_exists('wc_get_page_id') && ai4seo_is_function_usable('wc_get_page_id')) {
        // genric pages
        if ($post_id === wc_get_page_id( 'shop' )) {
            $context .= "WooCommerce: This page displays all products and serves as the main store landing page. Please keep metadata generic. ";
        } else if ($post_id === wc_get_page_id( 'cart' )) {
            $context .= "WooCommerce: This page is the shopping cart where customers can view and manage their selected products. Please keep metadata generic. ";
        } else if ($post_id === wc_get_page_id( 'checkout' )) {
            $context .= "WooCommerce: This page is the checkout page where customers complete their purchases. Please keep metadata generic. ";
        } else if ($post_id === wc_get_page_id( 'myaccount' )) {
            $context .= "WooCommerce: This page is the customer account page where users can manage their account details. Please keep metadata generic. ";
        } else if ($post_id === ai4seo_get_option( 'woocommerce_terms_page_id' )) {
            $context .= "WooCommerce: This page is the Terms and Conditions page for this WooCommerce store. Please keep metadata generic. ";
        }

        // product pages -> product details
        if ($post_type === 'product'
            && function_exists('wc_get_product') && ai4seo_is_function_usable('wc_get_product')
            && function_exists('wc_price') && ai4seo_is_function_usable('wc_price')
            && class_exists( 'WC_Product' )) {
            $product = wc_get_product($post_id);

            if ($product instanceof WC_Product) {
                $context .= "WooCommerce: This is a product page for the product '" . ai4seo_deep_sanitize($product->get_name()) . "'. ";

                $include_product_price_mode = ai4seo_get_setting(AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA);

                if (!is_string($include_product_price_mode)
                    || !array_key_exists($include_product_price_mode, ai4seo_get_setting_include_product_price_in_metadata_allowed_values())) {
                    $include_product_price_mode = 'never';
                }

                $product_price = '';
                $product_price_raw = $product->get_price();

                if ($product_price_raw !== '' && $product_price_raw !== null) {
                    $product_price = ai4seo_deep_sanitize(wc_price($product_price_raw));
                    $product_price = wp_strip_all_tags($product_price);
                    $product_price = html_entity_decode($product_price, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $product_price = str_replace("\xC2\xA0", ' ', $product_price);
                    $product_price = trim($product_price);
                }

                $product_price_instruction_added = false;

                if ($include_product_price_mode === 'fixed' && $product_price !== '') {
                    $context .= "The product has a price of '" . $product_price . "'. Include this price directly in the metadata (meta-title, meta-description and social-media-description). ";
                    $product_price_instruction_added = true;
                } elseif ($include_product_price_mode === 'dynamic' && $product_price !== '') {
                    $product_price_placeholder = "{WC_PRICE=" . $product_price . "}";
                    $context .= "The product has a price of '" . $product_price . "'. Include the placeholder " . $product_price_placeholder . " in the metadata (meta-title, meta-description and social-media-description); it will be replaced with the live price during rendering. ";
                    $product_price_instruction_added = true;
                }

                if (!$product_price_instruction_added) {
                    $context .= "Important: Don't add the product price in the metadata. ";
                }

                $category_ids = ai4seo_deep_sanitize($product->get_category_ids());

                $terms = array_map(function($term_id) {
                    $term = get_term( $term_id, 'product_cat' );
                    return $term ? $term->name : null;
                }, $category_ids);

                $terms = ai4seo_deep_sanitize(array_filter( $terms ));

                if ( ! empty( $terms ) ) {
                    $context .= "The product is in the category '" . implode(", ", $terms) . "'. ";
                }
            }
        }
    }

    // privacy policy context
    if ($post_id === ai4seo_get_option('wp_page_for_privacy_policy')) {
        $context .= "Attention: This page is the Privacy Policy page for this website. Please keep metadata generic. ";
    }

    // post title
    $post_title = get_the_title($post_id);

    if ($post_title) {
        $context .= "Sub Page Title: '" . $post_title . "'. ";
    }

    // excerpt
    $post_excerpt = get_the_excerpt($post_id);

    if ($post_excerpt) {
        ai4seo_condense_raw_post_content($post_excerpt, 150, 250); // Condense the excerpt
        $context .= "Excerpt: '" . $post_excerpt . "'. ";
    }

    // first section
    if ($content) {
        $context .= "First section: '" . $content . "'. ";
    }

    $context = trim($context);

    // enrich the content with the context
    $content = $context;
}

// =========================================================================================== \\

function ai4seo_get_website_context(): string {
    $website_context = "";

    // Get the WordPress site name, tagline, and URL
    $wp_name = get_bloginfo('name');

    if ($wp_name) {
        $website_context .= "Name: '" . $wp_name . "'. ";
    }

    $wp_tagline = get_bloginfo('description');

    if ($wp_tagline) {
        $website_context .= "Tagline: '" . $wp_tagline . "'. ";
    }

    $wp_url = get_bloginfo('url');

    if ($wp_url) {
        $website_context .= "URL: '" . $wp_url . "'";
    }

    return $website_context;
}

// =========================================================================================== \\

function ai4seo_is_acf_content($post_content): bool {
    return strpos($post_content, "<!-- wp:acf/") !== false;
}

// =========================================================================================== \\

function ai4seo_extract_acf_content($post_content): string {
    // Initialize an array to hold the extracted content
    $extracted_content = [];

    // Match all ACF blocks in the post_content
    preg_match_all('/<!-- wp:acf\/(.*?) (.*?)\/-->/s', $post_content, $matches, PREG_SET_ORDER);

    // Loop through each ACF block match
    foreach ($matches as $match) {
        // Decode the JSON data for the ACF block
        $acf_data = json_decode($match[2], true);

        if (isset($acf_data['data'])) {
            // Loop through the 'data' array and extract field content
            foreach ($acf_data['data'] as $key => $value) {
                // Skip metadata fields (fields starting with an underscore)
                if (strpos($key, '_') === 0) {
                    continue;
                }

                // Add the content to the extracted content array
                if (!empty($value)) {
                    $extracted_content[] = $value;
                }
            }
        }
    }

    // Return the extracted content as a plain text string
    return implode(" ", $extracted_content);
}

// =========================================================================================== \\

function ai4seo_calculate_metadata_credits_cost_per_post($only_this_meta_tags = null) : int {
    // check all active meta tags
    $metadata_price_table = ai4seo_get_metadata_price_table($only_this_meta_tags);

    if (empty($metadata_price_table)) {
        return 1;
    }

    // calculate total costs
    return array_sum($metadata_price_table);
}

// =========================================================================================== \\

function ai4seo_get_metadata_price_table($only_this_meta_tags = null): array {
    $active_meta_tags = ai4seo_get_active_meta_tags();

    if (empty($active_meta_tags)) {
        return array();
    }

    $price_table = array();

    foreach ($active_meta_tags AS $this_active_meta_tag) {
        if ($only_this_meta_tags && is_array($only_this_meta_tags) && !in_array($this_active_meta_tag, $only_this_meta_tags)) {
            continue;
        }

        if (!defined('AI4SEO_METADATA_DETAILS') || !is_array(AI4SEO_METADATA_DETAILS)) {
            $price_table[$this_active_meta_tag] = 1; // fallback to 1 credit per meta tag
            continue;
        }

        $price_table[$this_active_meta_tag] = AI4SEO_METADATA_DETAILS[$this_active_meta_tag]['flat-credits-cost'] ?? 1;
    }

    return $price_table;
}

// =========================================================================================== \\

/**
 * Removes all URLs from a given string.
 *
 * @param string $content The input string from which URLs will be removed.
 * @return string The string with all URLs removed.
 */
function ai4seo_remove_urls_from_string(string $content): string {
    // Define the regex pattern to match URLs
    $pattern = '/\b(?:https?|ftp):\/\/\S+/i';

    // Use preg_replace to remove URLs
    $cleaned_content = preg_replace($pattern, '', $content);

    // Return the cleaned content
    return $cleaned_content;
}

// =========================================================================================== \\

/**
 * Check if current admin screen is post edit (classic or Gutenberg).
 *
 * @return bool
 */
function ai4seo_is_post_edit_screen(): bool {
    if (!is_admin()) {
        return false;
    }

    if (!function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();

    if (!$screen) {
        return false;
    }

    return in_array($screen->base, array('post', 'post-new'), true);
}


// =========================================================================================== \\


/**
 * Is called when a post is updated or created, using the action hook "save_post". The function will add the post
 * id to the option "AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME" to be analyzed by the plugin.
 * @param $post_id int the post id
 * @param $post WP_Post|null the post object
 * @param $update bool if the post is updated
 * @return void
 */
function ai4seo_mark_post_to_be_analyzed(int $post_id, WP_Post $post = null, bool $update = false) {
    // check if the post is a revision
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Check if this is an autosave routine. If it is, the edit form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // check if we are currently inside an edit form
    if (!ai4seo_is_post_edit_screen()) {
        return;
    }

    // Make sure that the user is allowed to use this plugin
    if (!ai4seo_can_manage_this_plugin()) {
        return;
    }

    // Verify this came from our screen and with proper authorization.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // Insert post id into option to be analyzed AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME
    ai4seo_add_post_ids_to_option(AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME, $post_id);
}

// =========================================================================================== \\

/**
 * Analyzes the post, currently updating the metadata coverage
 * @param $post_id int the post id
 * @return void
 */
function ai4seo_analyze_post(int $post_id) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(516343145, 'Prevented loop', true);
        return;
    }

    if (!is_numeric($post_id)) {
        return;
    }

    // read post
    $post = get_post($post_id);

    // check if the post could be read
    if (!$post || is_wp_error($post) || !isset($post->post_type)) {
        return;
    }

    // ignore attachments
    $supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

    if (in_array($post->post_type, $supported_attachment_post_types)) {
        return;
    }

    ai4seo_refresh_one_posts_metadata_coverage_status($post_id, $post);
}

// =========================================================================================== \\

/**
 * Best-effort purge for a single post/page URL across common caching layers.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ai4seo_purge_frontend_cache_for_post( int $post_id ) : void {
    $is_frontend_cache_purge_enabled = ai4seo_get_setting(AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE);

    if (!$is_frontend_cache_purge_enabled) {
        return;
    }

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(371792553, 'Prevented loop', true);
        return;
    }

    $post_id = absint( $post_id );

    if ( $post_id <= 0 ) {
        return;
    }

    clean_post_cache( $post_id );

    $permalink = get_permalink( $post_id );

    if ( empty( $permalink ) ) {
        return;
    }

    ai4seo_purge_frontend_cache_for_url( $permalink );
}

// =========================================================================================== \\

/**
 * Best-effort purge for a single URL across common caching plugins.
 *
 * Note: This cannot purge CDN/browser caches unless your setup integrates them.
 *
 * @param string $url Absolute URL.
 * @return void
 */
function ai4seo_purge_frontend_cache_for_url( string $url ) : void {
    $url = esc_url_raw( $url );

    if ( empty( $url ) ) {
        return;
    }

    // LiteSpeed Cache.
    if ( function_exists( 'do_action' ) ) {
        do_action( 'litespeed_purge_url', $url );
    }

    // WP Rocket.
    if ( function_exists( 'rocket_clean_files' ) ) {
        rocket_clean_files( array( $url ) );
    }

    // W3 Total Cache.
    if ( function_exists( 'w3tc_flush_url' ) ) {
        w3tc_flush_url( $url );
    }

    // WP Super Cache.
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        // Clears whole cache; Super Cache has limited per-URL purge in many setups.
        wp_cache_clear_cache();
    }

    // SiteGround Optimizer.
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
        sg_cachepress_purge_cache();
    }

    // Cache Enabler.
    if ( function_exists( 'cache_enabler_clear_page_cache_by_url' ) ) {
        cache_enabler_clear_page_cache_by_url( $url );
    }

    // WP-Optimize
    if ( function_exists( 'wp_optimize_cache_purge_url' ) ) {
        wp_optimize_cache_purge_url( $url );
    }

    // WP fastest cache
    if ( function_exists( 'wpfc_clear_url_cache' ) ) {
        wpfc_clear_url_cache($url);
    }
}


// endregion
// ___________________________________________________________________________________________ \\
// region TAXONOMIES ============================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Get all taxonomies that expose public term archive URLs.
 *
 * Includes core, custom, WooCommerce product taxonomies, and Woo attributes (pa_*)
 * but excludes Woo attributes that have archives disabled.
 *
 * @return array[] List of taxonomy info:
 *                 array(
 *                     'taxonomy'      => 'category',
 *                     'label'         => 'Categories',
 *                     'is_woocommerce'=> true|false,
 *                     'is_attribute'  => true|false,
 *                     'archives_on'   => true|false,
 *                     'term_count'    => 123,
 *                     'sample_url'    => 'https://example.com/category/foo' | null,
 *                 )
 */
function ai4seo_get_url_exposed_taxonomies(): array {
    $cache_key   = 'ai4seo_url_exposed_taxonomies_v1';
    $cached      = get_transient( $cache_key );

    if ( is_array( $cached ) ) {
        return $cached;
    }

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(543892749, 'Prevented loop', true);
        return array();
    }

    // Map Woo attribute archive settings if WooCommerce is present.
    $woo_attr_archive_on = array(); // taxonomy => bool
    if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
        $attrs = wc_get_attribute_taxonomies();
        if ( is_array( $attrs ) ) {
            foreach ( $attrs as $attr ) {
                if ( ! empty( $attr->attribute_name ) ) {
                    $tax_name                         = 'pa_' . sanitize_key( $attr->attribute_name );
                    $woo_attr_archive_on[ $tax_name ] = ! empty( $attr->attribute_public );
                }
            }
        }
    }

    // Get public taxonomies.
    $tax_objects = get_taxonomies(
        array(
            'public' => true,
        ),
        'objects'
    );

    $results = array();

    foreach ( $tax_objects as $tax_name => $tax_obj ) {
        // Must be queryable and have rewrite rules to expose pretty URLs.
        $has_rewrite   = ! empty( $tax_obj->rewrite );
        $is_queryable  = ! empty( $tax_obj->publicly_queryable );
        if ( ! $has_rewrite || ! $is_queryable ) {
            continue;
        }

        // Woo and attributes flags.
        $is_woo        = in_array( $tax_name, array( 'product_cat', 'product_tag' ), true ) || 0 === strpos( $tax_name, 'pa_' );
        $is_attribute  = 0 === strpos( $tax_name, 'pa_' );

        // For Woo attributes: respect "Enable archives".
        if ( $is_attribute ) {
            $archives_on = isset( $woo_attr_archive_on[ $tax_name ] ) ? (bool) $woo_attr_archive_on[ $tax_name ] : true;
            if ( ! $archives_on ) {
                continue; // Skip attributes without archives.
            }
        }

        // Count terms cheaply.
        $term_count = (int) wp_count_terms(
            array(
                'taxonomy'   => $tax_name,
                'hide_empty' => false,
            )
        );

        // Sample URL: try to fetch a single term and link to it.
        $sample_url = null;
        if ( $term_count > 0 ) {
            $terms = get_terms(
                array(
                    'taxonomy'   => $tax_name,
                    'hide_empty' => false,
                    'number'     => 1,
                    'fields'     => 'all',
                )
            );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $url = get_term_link( $terms[0] );
                if ( ! is_wp_error( $url ) ) {
                    $sample_url = esc_url( $url );
                }
            }
        }

        $results[] = array(
            'taxonomy'       => $tax_name,
            'label'          => isset( $tax_obj->labels->name ) ? (string) $tax_obj->labels->name : $tax_name,
            'is_woocommerce' => $is_woo,
            'is_attribute'   => $is_attribute,
            'archives_on'    => true, // reached only if queryable + rewrite (+ attr archives on)
            'term_count'     => $term_count,
            'sample_url'     => $sample_url,
        );
    }

    // Sort: Woo first, then by name.
    usort(
        $results,
        static function ( $a, $b ) {
            if ( $a['is_woocommerce'] !== $b['is_woocommerce'] ) {
                return $a['is_woocommerce'] ? -1 : 1;
            }
            return strcasecmp( $a['taxonomy'], $b['taxonomy'] );
        }
    );

    set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );
    return $results;
}


// endregion
// ___________________________________________________________________________________________ \\
// region OUTPUT FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Returns the HTML for the edit metadata button
 * @param $post_id int The post id to get the button for
 * @param $all_post_ids array all post ids in this current list
 * @return string The HTML for the button
 */
function ai4seo_get_edit_metadata_button(int $post_id, array $all_post_ids = array()): string {
    $all_post_ids = ai4seo_deep_sanitize($all_post_ids, 'absint');

    return ai4seo_get_icon_button_tag("pen-to-square", "", "", "ai4seo_open_metadata_editor_modal(" . esc_js($post_id) . ", false" . ($all_post_ids ? ", " . json_encode($all_post_ids) : "") . ");", AI4SEO_PLUGIN_NAME . ": " . esc_html__("Metadata Editor", "ai-for-seo"));
}

// =========================================================================================== \\

/**
 * Returns the HTML for the edit attachment attributes button
 * @param $attachment_post_id int The post id to get the button for
 * @param $all_attachment_post_ids array all post ids in this current list
 * @return string The HTML for the button
 */
function ai4seo_get_edit_attachment_attributes_button(int $attachment_post_id, array $all_attachment_post_ids = array()): string {
    $all_attachment_post_ids = ai4seo_deep_sanitize($all_attachment_post_ids, 'absint');

    return ai4seo_get_icon_button_tag("pen-to-square", "", "", "ai4seo_open_attachment_attributes_editor_modal(" . esc_js($attachment_post_id) . ($all_attachment_post_ids ? ", " . json_encode($all_attachment_post_ids) : "") . ");", AI4SEO_PLUGIN_NAME . ": " . esc_html__("Media Attributes Editor", "ai-for-seo"));
}

// =========================================================================================== \\

/*function ai4seo_get_current_language() {
    // Read current language with weglot-plugin if it is installed and active
    if (function_exists("weglot_get_current_language")) {
        return weglot_get_current_language();
    }

    // Read current language with WPML-plugin if it is installed and active
    elseif (has_filter("wpml_current_language")) {
        return apply_filters("wpml_current_language", null);
    }

    // Read regular WordPress-language
    else {
        // Get language
        $language = get_locale();

        // Set default language if no language has been found
        if (empty($language)) {
            $language = "en_US";
        }

        // Convert language into simple language-code and return it
        return substr($language, 0, 2);
    }
}*/

// =========================================================================================== \\

/**
 * Generates the content for one accordion-element
 * @param string $headline
 * @param string $content
 * @return string
 */
function ai4seo_get_accordion_element(string $headline, string $content): string {
    // Generate output
    $output = "<div class='ai4seo-accordion-holder'>";
        // Add headline to output
        $output .= "<div class='card ai4seo-card ai4seo-accordion-headline' onclick='jQuery(\".ai4seo-accordion-content\").hide();jQuery(this).next().show();'>";
            $output .= $headline;
        $output .= "</div>";

        // Add content to output
        $output .= "<div class='card ai4seo-card ai4seo-accordion-content'>";
            $output .= $content;
        $output .= "</div>";
    $output .= "</div>";

    return $output;
}

// =========================================================================================== \\

function ai4seo_echo_half_donut_chart_with_headline_and_percentage($headline, $chart_values, $num_done, $num_total, $posts_table_analysis_state, $post_type) {
    $ai4seo_percentage_done = round($num_done / $num_total * 100);

    // set $ai4seo_percentage_color
    if ($ai4seo_percentage_done < 99) {
        $ai4seo_percentage_color = "black";
    } else {
        $ai4seo_percentage_color = "#005500";
    }

    echo "<div class='ai4seo-chart-container'>";
        echo "<h4>";
            ai4seo_echo_wp_kses($headline);
        echo "</h4>";

        echo "<div class='ai4seo-half-donut-chart-container'>";
            ai4seo_echo_half_donut_chart($chart_values);

            echo "<div class='ai4seo-half-donut-chart-percentage' style='color: " . esc_attr($ai4seo_percentage_color) . ";'>";
                echo esc_html($ai4seo_percentage_done) . "%";
            echo "</div>";

            echo "<div class='ai4seo-half-donut-chart-done' style='color: " . esc_attr($ai4seo_percentage_color) . ";'>";
                ai4seo_echo_wp_kses(sprintf(
                    /* translators: 1: Number of completed items. 2: Total number of items. */
                    esc_html__('%1$s/%2$s done', "ai-for-seo"),
                    esc_html($num_done),
                    $posts_table_analysis_state !== 'completed' ? ai4seo_get_svg_tag("gear", '', "ai4seo-spinning-icon ai4seo-gray-icon") : esc_html($num_total)
                ));
            echo "</div>";

            if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML) && in_array($post_type, array('attachment', 'media'))) {
                echo "<div class='ai4seo-half-donut-chart-sub-info ai4seo-tooltip-holder'>";
                    ai4seo_echo_wp_kses(sprintf(
                        /* translators: %s: Total number of media items. */
                        esc_html__('Why %1$s?', "ai-for-seo"),
                        esc_html($num_total),
                    ));

                    echo "<span class='ai4seo-tooltip'>";
                        ai4seo_echo_wp_kses(esc_html__("Your images appear on different language versions of your website. Therefore, each image needs to be analyzed for each language separately to ensure optimal SEO performance across all languages.", "ai-for-seo"));
                    echo "</span>";
                echo "</div>";
            }
        echo "</div>";
    echo "</div>";
}

// =========================================================================================== \\

/**
 * Function to output a half donut chart
 * @param $values array Example: [ "type1" => ["value" => 10, "color" => "#ff0000"], "type2" => ["value" => 20, "color" => "#00ff00"] ]
 * @return void
 */

function ai4seo_echo_half_donut_chart(array $values) {
    $total = array_sum(array_column($values, 'value'));

    echo '<svg width="250" height="120" xmlns="http://www.w3.org/2000/svg">';
    $startOffset = -235; // Adjust start position so that it begins to the left
    foreach ($values as $type => $info) {
        $percentage = ($info['value'] / $total) * 235;
        // Offset calculation needs to be adjusted
        echo "<circle class='ai4seo-circle' r='75' cx='125' cy='100' fill='transparent' stroke='" . esc_attr($info['color']) . "' ";
        echo "stroke-width='20' stroke-dasharray='" . esc_attr($percentage) . " 99999' stroke-dashoffset='" . esc_attr($startOffset) . "' />";
        $startOffset -= $percentage;
    }
    echo '</svg>';
}

// =========================================================================================== \\

/**
 * Function to output the legend for the half donut chart
 * @param $values array Example: [ "type1" => ["value" => 10, "color" => "#ff0000"], "type2" => ["value" => 20, "color" => "#00ff00"] ]
 * @return void
 */
function ai4seo_echo_chart_legend(array $values) {
    echo '<div class="ai4seo-chart-legend">';

    foreach ($values as $type => $info) {
        echo '<div class="ai4seo-chart-legend-item">';
            echo '<div class="ai4seo-chart-legend-color" style="background-color: ' . esc_attr($info['color']) . '"></div>';
            echo '<div class="ai4seo-chart-legend-text">' . esc_html(ai4seo_get_chart_legend_translation($type)) . '</div>';
        echo '</div>';
    }

    echo '</div>';
}

// =========================================================================================== \\

/**
 * Function to output a money-back-guarantee notice
 * @return void
 */
function ai4seo_output_money_back_guarantee_notice() {
    echo "<div class='ai4seo-money-back-guarantee-notice'>";

        // Portrait
        /*echo "<div class='ai4seo-andre-erbis-portrait'>";
            echo "<img src='" . esc_url(ai4seo_get_assets_images_url("andre-erbis-at-space-codes.webp")) . "' alt='André Erbis @ Space Codes - " . esc_attr__("SEO Expert and Full Stack Developer", "ai-for-seo") . "' />";
        echo "</div>";*/

            // Headline
        echo "<div class='ai4seo-money-back-guarantee-headline'>";
            echo esc_html__("Found a better price elsewhere? We'll match it!", "ai-for-seo");
        echo "</div>";

        echo "<div class='ai4seo-money-back-guarantee-quote'>";
            ai4seo_echo_wp_kses(sprintf(
                /* translators: %1$s plugin name, %2$s is a clickable email address */
                __('We’re excited for you to experience *%1$s*. If you find a better price elsewhere, simply <a href="%2$s" target="_blank">reach out</a>! We’ll match it.', 'ai-for-seo'),
                esc_attr(AI4SEO_OFFICIAL_CONTACT_URL),
                esc_html(AI4SEO_PLUGIN_NAME)
            ));
        echo "</div>";

        echo "<br>";

        // Headline
        echo "<div class='ai4seo-money-back-guarantee-headline'>";
            echo esc_html__("We provide a 100% Risk-Free Money-Back Guarantee!", "ai-for-seo");
        echo "</div>";

        echo "<div class='ai4seo-money-back-guarantee-quote'>";
            ai4seo_echo_wp_kses(sprintf(
                /* translators: 1: Number of money-back guarantee days. 2: Support contact link. 3: Percentage to refund. 4: Plugin name */
                __('During the first %1$u days after purchasing a subscription (Basic, Pro or Premium) or your first Credits Pack, if *%4$s* isn’t the best fit, simply <a href="%2$s" target="blank">reach out</a>! We’ll happily refund %3$s of your money. No questions asked.', 'ai-for-seo'),
                AI4SEO_MONEY_BACK_GUARANTEE_DAYS,
                esc_attr(AI4SEO_OFFICIAL_CONTACT_URL),
                '100%',
                esc_html(AI4SEO_PLUGIN_NAME),
            ));
        echo "</div>";

        echo "<div class='ai4seo-money-back-guarantee-signature'>";
            echo "<img src='" . esc_url(ai4seo_get_assets_images_url("andre-erbis-signature.png")) . "' alt='André Erbis @ Space Codes - " . esc_attr__("SEO Expert and Full Stack Developer", "ai-for-seo") . "' /><br>";
            echo "André Erbis @ Space Codes - " . esc_html__("SEO Expert and Full Stack Developer", "ai-for-seo");
        echo "</div>";

    echo "</div>";
}

// =========================================================================================== \\

/**
 * Function to output the loading-icon including holder-element
 * @return void
 */
function ai4seo_echo_loading_icon_output() {
    echo "<span class='ai4seo-hidden-loading-icon-holder'>";
        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("rotate", __("Loading", "ai-for-seo"), "ai4seo-spinning-icon"));
    echo "</span>";
}

// =========================================================================================== \\

/**
 * Function to output a button text link tag, wrapped in an a-tag
 * @param $a_href string The URL
 * @param $a_css_class string The CSS class for the a-tag
 * @param $a_target string The target attribute
 * @param $button_icon string The Font Awesome icon name
 * @param $button_text string The text to display
 * @param $button_css_class string The CSS class for the button
 * @param $button_onclick string The onclick event for the button
 * @return string HTML
 */
function ai4seo_get_a_tag_icon_button_tag(string $a_href, string $a_css_class = "", string $a_target = "_self", string $button_icon = "", string $button_text = "", string $button_css_class = "", string $button_onclick = ""): string {
    if (!$a_href) {
        // If no href is given, we set the href to "#"
        $a_href = "#";
    }

    $output = "<a href='" . esc_url($a_href) . "' class='" . esc_attr($a_css_class) . "' target='" . esc_attr($a_target) . "'>";
        $output .= ai4seo_get_icon_button_tag($button_icon, $button_text, $button_css_class, $button_onclick);
    $output .= "</a>";

    return $output;
}

// =========================================================================================== \\

function ai4seo_get_contact_us_button(string $a_css_class = "", $button_css_class = ""): string {
    return ai4seo_get_a_tag_icon_button_tag(AI4SEO_OFFICIAL_CONTACT_URL, $a_css_class, "_blank", "comments", __("Contact us", "ai-for-seo"), $button_css_class);
}

// =========================================================================================== \\

function ai4seo_get_button_tag(string $text, string $css_class = "", string $onclick = ""): string {
    return ai4seo_get_icon_button_tag("", $text, $css_class, $onclick);
}

// =========================================================================================== \\

/**
 * Function to output a button tag with icon and text.
 *
 * @param string $icon      The icon name.
 * @param string $text      The text to display.
 * @param string $css_class Additional CSS classes.
 * @param string $onclick   The onclick event.
 * @param string $title     The title attribute.
 *
 * @return string HTML.
 */
function ai4seo_get_icon_button_tag(string $icon, string $text, string $css_class = '', string $onclick = '', string $title = ''): string {
    // Base classes
    $css_class = 'ai4seo-button ai4seo-lockable' . ($css_class !== '' ? ' ' . $css_class : '');

    // Icon-only handling
    if ($text === '') {
        $css_class .= ' ai4seo-icon-only-button';
    }

    $css_class = trim($css_class);

    // Auto title fallback
    if ($title === '' && $text !== '') {
        $title = $text;
    }

    // Build attributes dynamically
    $attributes = array(
        'type'  => 'button',
        'class' => $css_class,
    );

    if ($onclick !== '') {
        $attributes['onclick'] = $onclick;
    }

    if ($title !== '') {
        $attributes['title'] = $title;
    }

    // Convert attributes to string
    $attribute_string = '';
    foreach ($attributes as $key => $value) {
        $attribute_string .= ' ' . $key . '="' . esc_attr($value) . '"';
    }

    // Build output
    $output = '<button' . $attribute_string . '>';

    if ($icon !== '') {
        $output .= ai4seo_get_svg_tag($icon, '', 'ai4seo-button-icon-left');
    }

    if ($text !== '') {
        $output .= '<span>' . esc_html($text) . '</span>';
    }

    $output .= '</button>';

    return $output;
}

// =========================================================================================== \\

function ai4seo_get_small_icon_button_tag(string $icon = "", string $text = "", string $css_class = "", string $onclick = ""): string {
    // default values
    if (empty($css_class)) {
        $css_class = "ai4seo-small-button";
    } else {
        $css_class .= " ai4seo-small-button";
    }

    return ai4seo_get_icon_button_tag($icon, $text, $css_class, $onclick);
}

// =========================================================================================== \\

function ai4seo_get_abort_button_tag(string $icon = "", string $text = "", string $css_class = "", string $onclick = ""): string {
    // default values
    if (empty($text)) {
        $text = __("Abort", "ai-for-seo");
    }

    if (empty($css_class)) {
        $css_class = "ai4seo-abort-button";
    } else {
        $css_class .= " ai4seo-abort-button";
    }

    return ai4seo_get_icon_button_tag($icon, $text, $css_class, $onclick);
}

// =========================================================================================== \\

function ai4seo_get_modal_close_button_tag(string $text = "", string $css_class = "", string $onclick = ""): string {
    // default values
    if (empty($text)) {
        $text = __("Close", "ai-for-seo");
    }

    if (empty($css_class)) {
        $css_class = "ai4seo-abort-button";
    } else {
        $css_class .= " ai4seo-abort-button";
    }

    if (empty($onclick)) {
        $onclick = "ai4seo_close_modal_by_child(this);";
    } else {
        $onclick .= " ai4seo_close_modal_by_child(this);";
    }

    return ai4seo_get_button_tag($text, $css_class, $onclick);
}

// =========================================================================================== \\

function ai4seo_get_submit_button_tag(string $text = "", string $css_class = "", string $onclick = ""): string {
    // default values
    if (empty($text)) {
        $text = __("Submit", "ai-for-seo");
    }

    if (empty($css_class)) {
        $css_class = "ai4seo-submit-button";
    } else {
        $css_class .= " ai4seo-submit-button";
    }

    return ai4seo_get_button_tag($text, $css_class, $onclick);
}


// =========================================================================================== \\

/**
 * Function to output a small button text link tag, wrapped in an a-tag
 * @return string HTML
 */
function ai4seo_get_small_a_tag_icon_button_tag(string $a_href, string $a_css_class = "", string $a_target = "_self", string $button_icon = "", string $button_text = "", string $button_css_class = "", string $button_onclick = ""): string {
    // default values
    if (empty($button_css_class)) {
        $button_css_class = "ai4seo-small-button";
    } else {
        $button_css_class .= " ai4seo-small-button";
    }

    return ai4seo_get_a_tag_icon_button_tag($a_href, $a_css_class, $a_target, $button_icon, $button_text, $button_css_class, $button_onclick);
}

// =========================================================================================== \\

/**
 * Retrieve the translation for the different content types
 * @return string The translation
 */
function ai4seo_get_post_type_translation($post_type, $count_or_plural = false): string {
    $post_type_original = $post_type;
    $post_type = strtolower($post_type);
    $translation = $post_type_original;

    switch ($post_type) {
        case "post":
        case "posts":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("posts", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("post", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of posts. */
                $translation = sprintf(
                    /* translators: %s: Number of posts. */
                    _nx('%1$s post', '%1$s posts', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "page":
        case "pages":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("pages", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("page", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of pages. */
                $translation = sprintf(
                    /* translators: %s: Number of pages. */
                    _nx('%1$s page', '%1$s pages', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "product":
        case "products":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("products", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("product", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of products. */
                $translation = sprintf(
                    /* translators: %s: Number of products. */
                    _nx('%1$s product', '%1$s products', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "portfolio":
        case "portfolios":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("portfolios", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("portfolio", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of portfolios. */
                $translation = sprintf(
                    /* translators: %s: Number of portfolios. */
                    _nx('%1$s portfolio', '%1$s portfolios', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "attachment":
        case "attachments":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("attachments", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("attachment", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of attachments. */
                $translation = sprintf(
                    /* translators: %s: Number of attachments. */
                    _nx('%1$s attachment', '%1$s attachments', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "media": # not a post type, but useful to have in some situations, as we describe attachments as media for the user
        case "medias":
            // Plural
            if ($count_or_plural === true) {
                $translation = _n('medium', 'media', 2, 'ai-for-seo');
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = _n('medium', 'media', 1, 'ai-for-seo');
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of media items. */
                $translation = sprintf(
                    /* translators: %s: Number of media items. */
                    _nx('%1$s medium', '%1$s media', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        case "media file": # not a post type, but useful to have in some situations, as we describe attachments as media for the user
        case "media files":
            // Plural
            if ($count_or_plural === true) {
                $translation = __("media files", "ai-for-seo");
            }
            // Singular
            else if ($count_or_plural === false) {
                $translation = __("media file", "ai-for-seo");
            }
            // Singular or plural with count
            else {
                /* translators: %s: Number of media files. */
                $translation = sprintf(
                    /* translators: %s: Number of media files. */
                    _nx('%1$s media file', '%1$s media files', $count_or_plural, 'noun', 'ai-for-seo'),
                    $count_or_plural
                );
            }
            break;
        default:
            // plural
            if ($count_or_plural === true) {
                // we do not add an "s" to the end of the translation, as it does not work with every language reliably
                // $translation .= "s";

            // singular
            } else if ($count_or_plural === false) {
                // nothing to do

            // singular / plural with a counter
            } else if (is_numeric($count_or_plural)) {
                $translation = $count_or_plural . " " . $post_type_original;

                if ($count_or_plural !== 1) {
                    // we do not add an "s" to the end of the translation, as it does not work with every language reliably
                    // $translation .= "s";
                }
            }
    }

    return $translation;
}

// =========================================================================================== \\

/**
 * Function that outputs the options for a language selection select field
 * @return string The html of the options for the select field
 */
function ai4seo_get_generation_language_select_options_html($selected = "auto"): string {
    $languages = ai4seo_get_translated_generation_language_options();
    $languages = array("auto" => "- " . __("Automatic", "ai-for-seo") . " -") + $languages;
    $options_html = "";

    foreach ($languages as $value => $text) {
        $selected_attribute = ($selected == $value) ? " selected" : "";
        $options_html .= "<option value='" . esc_attr($value) . "'" . esc_attr($selected_attribute) . ">" . esc_html($text) . "</option>";
    }

    return $options_html;
}

// =========================================================================================== \\

/**
 * Get all available language options for AI generation
 * @return array An array of all available language options this plugin supports for AI generation
 */
function ai4seo_get_translated_generation_language_options(): array {
    // Array of language codes and their corresponding names
    $languages = array(
        'albanian' => esc_html__('Albanian', 'ai-for-seo'),
        'arabic' => esc_html__('Arabic', 'ai-for-seo'),
        'bulgarian' => esc_html__('Bulgarian', 'ai-for-seo'),
        'chinese' => esc_html__('Chinese (General)', 'ai-for-seo'),
        'simplified chinese' => esc_html__('Chinese (Simplified)', 'ai-for-seo'),
        'traditional chinese' => esc_html__('Chinese (Traditional)', 'ai-for-seo'),
        'croatian' => esc_html__('Croatian', 'ai-for-seo'),
        'czech' => esc_html__('Czech', 'ai-for-seo'),
        'danish' => esc_html__('Danish', 'ai-for-seo'),
        'dutch' => esc_html__('Dutch', 'ai-for-seo'),
        'american english' => esc_html__('English (America)', 'ai-for-seo'),
        'british english' => esc_html__('English (Britain)', 'ai-for-seo'),
        'estonian' => esc_html__('Estonian', 'ai-for-seo'),
        'finnish' => esc_html__('Finnish', 'ai-for-seo'),
        'european french' => esc_html__('French (Europe)', 'ai-for-seo'),
        'canadian french' => esc_html__('French (Canada)', 'ai-for-seo'),
        'german' => esc_html__('German', 'ai-for-seo'),
        'greek' => esc_html__('Greek', 'ai-for-seo'),
        'hebrew' => esc_html__('Hebrew', 'ai-for-seo'),
        'hindi' => esc_html__('Hindi', 'ai-for-seo'),
        'hungarian' => esc_html__('Hungarian', 'ai-for-seo'),
        'icelandic' => esc_html__('Icelandic', 'ai-for-seo'),
        'indonesian' => esc_html__('Indonesian', 'ai-for-seo'),
        'italian' => esc_html__('Italian', 'ai-for-seo'),
        'japanese' => esc_html__('Japanese', 'ai-for-seo'),
        'korean' => esc_html__('Korean', 'ai-for-seo'),
        'latvian' => esc_html__('Latvian', 'ai-for-seo'),
        'lithuanian' => esc_html__('Lithuanian', 'ai-for-seo'),
        'macedonian' => esc_html__('Macedonian', 'ai-for-seo'),
        'maltese' => esc_html__('Maltese', 'ai-for-seo'),
        'norwegian' => esc_html__('Norwegian', 'ai-for-seo'),
        'polish' => esc_html__('Polish', 'ai-for-seo'),
        'european portuguese' => esc_html__('Portuguese (Europe)', 'ai-for-seo'),
        'brazilian portuguese' => esc_html__('Portuguese (Brazil)', 'ai-for-seo'),
        'romanian' => esc_html__('Romanian', 'ai-for-seo'),
        'russian' => esc_html__('Russian', 'ai-for-seo'),
        'serbian' => esc_html__('Serbian', 'ai-for-seo'),
        'slovak' => esc_html__('Slovak', 'ai-for-seo'),
        'slovenian' => esc_html__('Slovenian', 'ai-for-seo'),
        'spanish' => esc_html__('Spanish', 'ai-for-seo'),
        'swedish' => esc_html__('Swedish', 'ai-for-seo'),
        'thai' => esc_html__('Thai', 'ai-for-seo'),
        'turkish' => esc_html__('Turkish', 'ai-for-seo'),
        'ukrainian' => esc_html__('Ukrainian', 'ai-for-seo'),
        'vietnamese' => esc_html__('Vietnamese', 'ai-for-seo'),
    );

    return $languages;
}

// =========================================================================================== \\

/**
 * Retrieve the translation for the different chart-legend-types
 * @param string $legend_identifier
 * @return string
 */
function ai4seo_get_chart_legend_translation(string $legend_identifier): string {
    $legend_identifier_original = $legend_identifier;
    $legend_identifier = strtolower($legend_identifier);

    switch ($legend_identifier) {
        case "done":
            return esc_html__("Done", "ai-for-seo");
        case "processing":
            return esc_html__("Processing", "ai-for-seo");
        case "missing":
            return esc_html__("Missing SEO / Pending", "ai-for-seo");
        case "failed":
            return esc_html__("Failed (please check details)", "ai-for-seo");
        default:
            return $legend_identifier_original;
    }
}

// =========================================================================================== \\

function ai4seo_get_select_all_checkbox($target_checkbox_name, $label = "auto"): string {
    if ($label === "auto") {
        $label = esc_html__("Select All / Unselect All", "ai-for-seo");
    }

    $select_all_checkbox_id = "ai4seo-select-all-{$target_checkbox_name}";

    $output = "";

    if (!empty($label)) {
        $output .= "<label class='ai4seo-select-all-checkbox-label ai4seo-form-multiple-inputs' for='" . esc_attr($select_all_checkbox_id) . "'>";
    }

    $output .= "<input type='checkbox' class='ai4seo-select-all-checkbox' data-target='" . esc_attr($target_checkbox_name) . "' id='" . esc_attr($select_all_checkbox_id) . "'>";

    if (!empty($label)) {
        $output .= esc_html($label);
        $output .= "</label>";
    }

    return $output;
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of the terms of service in a readable format
 * @return string A readable format of the accepted timestamp
 */
function ai4seo_get_tos_toc_and_pp_accepted_time_output(): string {
    return ai4seo_get_environmental_variable_accepted_time_output(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME);
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of the enhanced reporting agreement
 * @return string A readable format of the accepted timestamp
 */
function ai4seo_get_enhanced_reporting_accepted_time_output(): string {
    return ai4seo_get_environmental_variable_accepted_time_output(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME);
}

// =========================================================================================== \\

/**
 * Function to output the current accepted timestamp of a specific environmental variable in a readable format
 * @return string A readable format of the accepted timestamp of the terms of service
 */
function ai4seo_get_environmental_variable_accepted_time_output($environmental_variable_name): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(212334759, 'Prevented loop', true);
        return '';
    }

    $accepted_time = ai4seo_read_environmental_variable($environmental_variable_name);

    $content = "";

    if ($accepted_time) {
        $readable_accepted_time = ai4seo_format_unix_timestamp($accepted_time);
        $content .= ai4seo_get_svg_tag("square-check", "", "ai4seo-16x16-icon ai4seo-dark-green-icon") . " ";
        /* translators: %s: Accepted time in a human readable format. */
        $content .= sprintf(esc_html__('Accepted on %s.', 'ai-for-seo'), $readable_accepted_time);
    } else {
        //$content .= ai4seo_get_svg_tag("square-xmark", "", "ai4seo-16x16-icon ai4seo-red-icon") . " ";
        //$content .= esc_html__("Not accepted yet.", "ai-for-seo");
    }
    return $content;
}

// =========================================================================================== \\

/**
 * Function to check if the SEO Autopilot is running at least X amount of seconds
 * @param int $duration The duration in seconds
 * @return bool True if the SEO Autopilot is running at least X amount of seconds
 */
function ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago(int $duration = 300): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(530953976, 'Prevented loop', true);
        return false;
    }

    $seo_autopilot_start_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME);

    if (!$seo_autopilot_start_time) {
        return false;
    }

    return (time() - $seo_autopilot_start_time) >= $duration;
}

// =========================================================================================== \\

function ai4seo_echo_cost_breakdown_section($credits_percentage) {
    $active_meta_tags_names = ai4seo_get_active_meta_tags_names();
    $active_attachment_attribute_names = ai4seo_get_active_attachment_attributes_names();
    $metadata_credits_cost_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
    $attachment_attributes_credits_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

    echo "<div style='text-align: center'><h4 style='margin-top: 0;'>";
        echo esc_html__("Cost Breakdown", "ai-for-seo");
    echo "</h4>";

    echo "<ul>";
            echo "<li>";
                if ($metadata_credits_cost_per_post) {
                    ai4seo_echo_wp_kses(sprintf(
                        /* translators: %s: Number of credits per piece of metadata. */
                        __('Metadata per page/post/etc.: %s', 'ai-for-seo'),
                        "<span class='ai4seo-credits-usage-badge'><strong>" . $metadata_credits_cost_per_post . "</strong> "
                        . esc_html(_n('Credit', 'Credits', $metadata_credits_cost_per_post, 'ai-for-seo')) . '</span>'
                        )
                    );

                    /* translators: %s: List of active metadata tags. */
                    ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag(sprintf(__('Your current generation setup: %s', 'ai-for-seo'), esc_html(implode(', ', $active_meta_tags_names)))));
                } else {
                    ai4seo_echo_wp_kses(sprintf(
                        __("No meta tags are currently active.", "ai-for-seo")
                ));
            }
        echo "</li>";
            echo "<li>";
                if ($attachment_attributes_credits_cost_per_attachment_post) {
                    ai4seo_echo_wp_kses(sprintf(
                        /* translators: %s: Number of credits per image attribute set. */
                        __('Media attributes per image: %s', 'ai-for-seo'),
                        "<span class='ai4seo-credits-usage-badge'><strong>" . $attachment_attributes_credits_cost_per_attachment_post . "</strong> "
                        . esc_html(_n('Credit', 'Credits', $attachment_attributes_credits_cost_per_attachment_post, 'ai-for-seo')) . '</span>'
                        )
                    );

                    /* translators: %s: List of active media attributes. */
                    ai4seo_echo_wp_kses(ai4seo_get_icon_with_tooltip_tag(sprintf(__('Your current generation setup: %s', 'ai-for-seo'), esc_html(implode(', ', $active_attachment_attribute_names)))));
                } else {
                    ai4seo_echo_wp_kses(sprintf(
                        __("No media attributes are currently active.", "ai-for-seo")
                ));
            }
        echo "</li>";

        if ($credits_percentage <= 0) {
            echo "<li class='ai4seo-red-message'>";
                ai4seo_echo_wp_kses(sprintf(
                    __("<strong>Note:</strong> Your Credits balance is insufficient to cover any additional AI generations.", "ai-for-seo")
                ));
            echo "</li>";
        } else if ($credits_percentage < 100) {
            echo "<li class='ai4seo-red-message'>";
                ai4seo_echo_wp_kses(sprintf(
                    /* translators: %s: Percentage of remaining coverage. */
                    __('<strong>Note:</strong> Your Credits balance only covers approximately <strong>%1$s%%</strong> of the remaining pages / media files.', 'ai-for-seo'),
                    esc_html($credits_percentage)
                ));
            echo "</li>";
        }
    echo "</ul></div>";
}

// =========================================================================================== \\

function ai4seo_echo_current_discount() {
    $ai4seo_current_discount = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT);

    if (!$ai4seo_current_discount) {
        return "";
    }

    // create green bubble with gift icon and discount percentage
    echo "<div class='ai4seo-green-bubble ai4seo-discount-available-message'>";

        ai4seo_echo_wp_kses(ai4seo_get_svg_tag("gift", esc_attr__("Discount available!", "ai-for-seo")));
        echo " ";

        // with countdown
        if (isset($ai4seo_current_discount["expire_in"]) && $ai4seo_current_discount["expire_in"] > 0) {
            echo sprintf(
                /* translators: 1: Discount percentage. 2: Remaining time. */
                esc_html__('%1$s%% discount available (time left: %2$s)', 'ai-for-seo'),
                (int) $ai4seo_current_discount["percentage"],
                "<span class='ai4seo-countdown ai4seo-ignore-during-dashboard-refresh' data-time-left='" . esc_attr($ai4seo_current_discount["expire_in"]) . "' data-trigger='ai4seo_refresh_robhub_account'>" . esc_html(ai4seo_format_seconds_to_hhmmss_or_days_hhmmss($ai4seo_current_discount["expire_in"])) . "</span>"
            );
            // without countdown
        } else {
            echo sprintf(
                /* translators: %s: Discount percentage. */
                esc_html__('%1$s%% discount available', 'ai-for-seo'),
                (int) $ai4seo_current_discount["percentage"]
            );
        }
    echo "</div>";
}

// =========================================================================================== \\

function ai4seo_get_voucher_code_output($voucher_code): string {
    $voucher_code_output = "<div class='ai4seo-voucher-code-wrapper'>";
        $voucher_code_output .= "<div class='ai4seo-voucher-code'>" . esc_html($voucher_code);
                $voucher_code_output .= "<button class='ai4seo-button ai4seo-secondary-button ai4seo-icon-only-button ai4seo-copy-voucher-code-button ai4seo-copy-to-clipboard' data-clipboard-text='" . esc_attr($voucher_code) . "' title='" . esc_attr__("Copy voucher code", "ai-for-seo") . "'>";
                $voucher_code_output .= ai4seo_get_svg_tag("copy");
            $voucher_code_output .= "</button>";
            $voucher_code_output .= "<div class='ai4seo-copy-voucher-code-tooltip ai4seo-copied-to-clipboard' style='display: none'>👍 " . esc_html__("Copied!", "ai-for-seo") . "</div>";
        $voucher_code_output .= "</div>";
    $voucher_code_output .= "</div>";

    return $voucher_code_output;
}

// =========================================================================================== \\

/**
 * Function to return the HTML for a dashicon tag
 * @param $icon_name string The name of the dashicon
 * @param $css_class string The CSS class to add to the icon (optional)
 * @return string The HTML for the dashicon tag
 */
function ai4seo_get_dashicon_tag(string $icon_name, string $css_class = ""): string {
    return '<i class="dashicons dashicons-' . esc_attr($icon_name) . ' ' . esc_attr($css_class) . '"></i>';
}

// =========================================================================================== \\

/**
 * Function to return the HTML for a dashicon tag for the menu items
 * @param $plugin_page string The name of the plugin page (e.g., "page", "post", "category", etc.)
 * @return string The HTML for the dashicon tag for the menu items
 */
function ai4seo_get_dashicon_tag_for_navigation($plugin_page): string {
    $icon_name_mapping = array(
        "default" => 'text-page',
        "page" => 'admin-page',
        "post" => 'admin-post',
        "category" => 'admin-category',
        "product" => 'products',
        "product-category" => 'products',
        "portfolio" => 'portfolio',
        "attachment" => 'admin-media',
        "media files" => 'admin-media',
        "media" => 'admin-media',
        "rss" => 'rss',
        "rss-feed" => 'rss',
        "rss_feed" => 'rss',
    );

    $icon_name = $icon_name_mapping[$plugin_page] ?? $icon_name_mapping['default'];

    return ai4seo_get_dashicon_tag($icon_name, "ai4seo-menu-item-icon");
}


// endregion
// ___________________________________________________________________________________________ \\
// region THIRD PARTY SEO PLUGINS ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Collects all the currently supported and active third party SEO plugins
 * @return array The supported and currently active third party SEO plugins
 */
function ai4seo_get_active_third_party_seo_plugin_details(): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(500078657, 'Prevented loop', true);
        return array();
    }

    $active_supported_third_party_seo_plugin_details = array();

    $third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

    foreach ($third_party_seo_plugin_details AS $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details) {
        if (ai4seo_is_plugin_or_theme_active($this_third_party_seo_plugin_identifier)) {
            $active_supported_third_party_seo_plugin_details[$this_third_party_seo_plugin_identifier] = $this_third_party_seo_plugin_details;
        }
    }

    return $active_supported_third_party_seo_plugin_details;
}

// =========================================================================================== \\

/**
 * Returns the keyphrase of the currently active third party SEO plugin, if it exists
 * @param $post_id int The post id
 * @return string The keyphrase or an empty string
 */
function ai4seo_get_any_third_party_seo_plugin_keyphrase(int $post_id): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(608212638, 'Prevented loop', true);
        return '';
    }

    $active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

    foreach ($active_supported_third_party_seo_plugins AS $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details) {
        if (empty($this_third_party_seo_plugin_details['generation-field-postmeta-keys'])
            || empty($this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'])) {
            continue;
        }

        $keyphrase_postmeta_key = sanitize_text_field($this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase']);

        $this_keyphrase = get_post_meta($post_id, $keyphrase_postmeta_key, true);

        if (!empty($this_keyphrase) && is_string($this_keyphrase)) {
            return $this_keyphrase;
        }
    }

    return "";
}

// =========================================================================================== \\

/**
 * Returns the key phrases for the given post ids (based on the currently active third party seo plugin)
 * @param $post_ids array post ids
 * @return array key phrases by post id or null on error
 */
function ai4seo_read_third_party_seo_plugin_key_phrases(array $post_ids): ?array {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(807307700, 'Prevented loop', true);
        return array();
    }

    if (!$post_ids) {
        return array();
    }

    $sanitized_post_ids = array_map('intval', $post_ids);
    $sanitized_post_ids = array_filter($sanitized_post_ids);

    if (empty($sanitized_post_ids)) {
        return array();
    }

    // Chunk post IDs to avoid oversized IN(...) clauses.
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $sanitized_post_ids_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

    // only consider the currently active third party seo plugins
    $active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

    if (!$active_supported_third_party_seo_plugins) {
        return array();
    }

    // go through all active third party seo plugins and get the key phrases
    $key_phrases = array();

    foreach ($active_supported_third_party_seo_plugins AS $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details) {
        if (empty($this_third_party_seo_plugin_details['generation-field-postmeta-keys'])
            || empty($this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase'])) {
            continue;
        }

        // if we found all key phrases, we can stop the loop
        if (count($key_phrases) == count($post_ids)) {
            break;
        }

        $this_keyphrase_postmeta_key = sanitize_text_field($this_third_party_seo_plugin_details['generation-field-postmeta-keys']['focus-keyphrase']);

        foreach ( $sanitized_post_ids_chunks as $this_post_ids_chunk ) {
            if ( empty( $this_post_ids_chunk ) ) {
                continue;
            }

            $this_post_id_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

            $query_args = array_merge( array( $this_keyphrase_postmeta_key ), $this_post_ids_chunk );

            $this_postmeta_entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_value 
                    FROM {$wpdb->postmeta} 
                    WHERE meta_key = %s 
                    AND post_id IN ({$this_post_id_placeholders})",
                    ...$query_args
                ),
                ARRAY_A
            );

            // on error
            if ($wpdb->last_error) {
                ai4seo_debug_message(984321654, 'Database error: ' . $wpdb->last_error, true);
                return array();
            }

            if (!$this_postmeta_entries) {
                continue;
            }

            // loop through all key phrases and add them to the $ai4seo_this_page_post_ids array
            foreach ($this_postmeta_entries as $this_postmeta_entry) {
                $this_post_id = isset($this_postmeta_entry['post_id']) ? intval( $this_postmeta_entry['post_id']) : 0;
                $this_key_phrase_value = isset($this_postmeta_entry['meta_value']) ? sanitize_text_field($this_postmeta_entry['meta_value']) : '';

                // Make sure that post id is numeric
                if (!$this_post_id) {
                    continue;
                }

                // skip if we already have a key phrase for this post id
                if (isset($key_phrases[$this_post_id])) {
                    continue;
                }

                // Add key phrase to the $ai4seo_this_page_post_ids array
                $key_phrases[$this_post_id] = $this_key_phrase_value;
            }
        }
    }

    return $key_phrases;
}

// =========================================================================================== \\

/**
 * Returns the yoast seo scores for the given post ids
 * @param $post_ids array post ids
 * @return array yoast seo scores by post id or null on error
 */
function ai4seo_read_yoast_seo_scores(array $post_ids): ?array {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(647946549, 'Prevented loop', true);
        return array();
    }

    # todo: make this whole function dynamic

    // Make sure that yoast seo plugin is active
    if (!ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO)) {
        return array();
    }

    if (!$post_ids) {
        return array();
    }

    $postmeta_table = $wpdb->postmeta;
    $postmeta_table = sanitize_text_field($postmeta_table);

    // Sanitize and escape each post ID
    $sanitized_post_ids = array_map(function($id) use ($wpdb) {
        return intval($id);
    }, $post_ids);

    // Chunk post IDs to avoid oversized IN(...) clauses.
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $sanitized_post_id_chunks = array_chunk( $sanitized_post_ids, $database_chunk_size );

    $yoast_seo_scores = array();

    foreach ( $sanitized_post_id_chunks as $this_post_id_chunk ) {
        $this_post_ids_string = implode( ',', $this_post_id_chunk );

        // Construct the SQL query
        $this_chunk_yoast_seo_scores = $wpdb->get_results("SELECT post_id, meta_value FROM " . esc_sql($postmeta_table) . " WHERE meta_key = '_yoast_wpseo_linkdex' AND post_id IN ($this_post_ids_string)");

        // on error
        if ($wpdb->last_error) {
            ai4seo_debug_message(984321655, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if (!$this_chunk_yoast_seo_scores) {
            continue;
        }

        $yoast_seo_scores = array_merge( $yoast_seo_scores, $this_chunk_yoast_seo_scores );
    }

    if (!$yoast_seo_scores) {
        return array();
    }

    // loop through all yoast seo scores and add them to the $ai4seo_this_page_post_ids array
    $seo_scores = array();

    foreach ($yoast_seo_scores as $yoast_seo_score) {
        $post_id = $yoast_seo_score->post_id;
        $seo_score = $yoast_seo_score->meta_value;

        // Make sure that post id is numeric
        if (!is_numeric($post_id) || !$post_id) {
            continue;
        }

        // Add seo score to the $ai4seo_this_page_post_ids array
        $seo_scores[$post_id] = $seo_score;
    }

    return $seo_scores;
}


// endregion
// ___________________________________________________________________________________________ \\
// region EXTERNAL PLUGINS ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Returns weather a plugin or theme is active
 * @param $identifier
 * @return bool
 */
function ai4seo_is_plugin_or_theme_active($identifier): bool {
    global $ai4seo_cached_active_plugins_and_themes;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(406909321, 'Prevented loop', true);
        return false;
    }

    // try use cache first
    if (isset($ai4seo_cached_active_plugins_and_themes[$identifier])) {
        return $ai4seo_cached_active_plugins_and_themes[$identifier];
    }

    // Make sure that plugin-file has been loaded
    if (!function_exists("is_plugin_active")) {
        include_once(ABSPATH . "wp-admin/includes/plugin.php");
    }

    if (!function_exists("is_plugin_active")) {
        return false;
    }

    $is_active = false;
    $check_this_theme_name = "";
    $check_this_file_path = "";
    $check_this_class_name = "";

    switch ($identifier) {
        // editors
        case AI4SEO_THIRD_PARTY_PLUGIN_BETHEME:
            $check_this_theme_name = "Betheme";
            break;
        case AI4SEO_THIRD_PARTY_PLUGIN_ELEMENTOR:
            $check_this_file_path = "elementor/elementor.php";
            $check_this_class_name = "Elementor\Plugin";
            break;

        // shops
        case AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE:
            $check_this_file_path = "woocommerce/woocommerce.php";
            $check_this_class_name = "WooCommerce";
            break;

        // multi-language
        case AI4SEO_THIRD_PARTY_PLUGIN_WPML:
            $check_this_file_path = "sitepress-multilingual-cms/sitepress.php";
            $check_this_class_name = "SitePress";
            break;

        // seo plugins
        case AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO:
            $check_this_file_path = "wordpress-seo/wp-seo.php";
            $check_this_class_name = "WPSEO_Meta";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO:
            $check_this_file_path = "all-in-one-seo-pack/all_in_one_seo_pack.php";
            $check_this_class_name = "AIOSEO\Plugin\AIOSEO";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH:
            $check_this_file_path = "seo-by-rank-math/rank-math.php";
            $check_this_class_name = "RankMath";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK:
            $check_this_file_path = "seo-simple-pack/seo-simple-pack.php";
            $check_this_class_name = "SEO_SIMPLE_PACK";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS:
            $check_this_file_path = "wp-seopress/seopress.php";
            $check_this_class_name = "SEOPress\Core\Kernel";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO:
            $check_this_file_path = "slim-seo/slim-seo.php";
            $check_this_class_name = "SlimSEO\\Core";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO:
            $check_this_file_path = "squirrly-seo/squirrly.php";
            $check_this_class_name = "SQ_Classes_ObjController";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK:
            $check_this_file_path = "autodescription/autodescription.php";
            # do not check for class, as it is not unique, as the plugin uses a load system
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL:
            $check_this_file_path = "blog2social/blog2social.php";
            $check_this_class_name = "B2S_System";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY:
            $check_this_file_path = "nextgen-gallery/nggallery.php";
            $check_this_class_name = "C_NextGEN_Bootstrap";
            break;

        case AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY:
            $check_this_file_path = "seo-key/seo-key.php";
            $check_this_class_name = "SEOKEY_Free";
            break;
    }

    do {
        // check for a specific theme
        if ($check_this_theme_name) {
            $current_theme = wp_get_theme();
            $parent_theme = $current_theme->parent();

            // Check if betheme is active
            $is_active = $current_theme->get("Name") === $check_this_theme_name || ($parent_theme && $parent_theme->get("Name") === $check_this_theme_name);

            if (!$is_active) {
                break;
            }
        }

        //check for a specific plugin -> check path
        if ($check_this_file_path) {
            try {
                $is_active = is_plugin_active($check_this_file_path);
            } catch (Exception $e) {
                $is_active = false;
            }

            if (!$is_active) {
                break;
            }
        }

        //check for a specific plugin -> check class
        if ($check_this_class_name) {
            try {
                $is_active = class_exists($check_this_class_name);
            } catch (Exception $e) {
                $is_active = false;
            }

            if (!$is_active) {
                break;
            }
        }
    } while (false);

    // update cache
    $ai4seo_cached_active_plugins_and_themes[$identifier] = $is_active;

    return $is_active;
}


// endregion
// ___________________________________________________________________________________________ \\
// region MULTI-LANGUAGE THIRD-PARTY PLUGINS ==================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function that tries to determine the language of a post by checking various multi-language plugins
 * @param $post_id int The post id
 * @return string The language of the post
 */
function ai4seo_try_get_post_language_by_checking_multilanguage_plugins(int $post_id): string {
    $post_language_code = ai4seo_get_post_language_code_by_multilanguage_plugins($post_id);

    if ($post_language_code !== "") {
        return ai4seo_get_language_long_version($post_language_code, "");
    }

    return "";
}

// =========================================================================================== \\

/**
 * Returns the language code for a post if a supported multi-language plugin is active.
 *
 * @param int $post_id The post id.
 * @return string The language code (e.g. "en", "de") or an empty string if not available.
 */
function ai4seo_get_post_language_code_by_multilanguage_plugins(int $post_id): string {
    // WPML
    if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
        $attachment_language = apply_filters("wpml_post_language_details", null, $post_id);
        $attachment_language = ai4seo_deep_sanitize($attachment_language);

        if ($attachment_language && isset($attachment_language["language_code"])) {
            return sanitize_key($attachment_language["language_code"]);
        }
    }

    return "";
}

// =========================================================================================== \\

/**
 * Executes a callback while temporarily forcing WPML to return all languages.
 *
 * @param callable $callback The callback to execute.
 * @return mixed The callback result.
 */
function ai4seo_with_wpml_all_languages(callable $callback) {
    if (!ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
        return $callback();
    }

    $filter_function = 'ai4seo_wpml_return_all_languages';
    $previous_language = null;

    if (isset($_GET['lang'])) {
        $previous_lang_get = sanitize_key(wp_unslash($_GET['lang']));
    } else {
        $previous_lang_get = null;
    }

    $sitepress = $GLOBALS['sitepress'] ?? null;

    if ($sitepress && is_object($sitepress) && method_exists($sitepress, 'get_current_language') && method_exists($sitepress, 'switch_lang')) {
        $previous_language = $sitepress->get_current_language();
        $sitepress->switch_lang('all', true);
    }

    $_GET['lang'] = 'all';
    add_filter('wpml_current_language', $filter_function, 99);

    try {
        return $callback();
    } finally {
        remove_filter('wpml_current_language', $filter_function, 99);

        if ($sitepress && is_object($sitepress) && method_exists($sitepress, 'switch_lang') && $previous_language !== null) {
            $sitepress->switch_lang($previous_language, true);
        }

        if ($previous_lang_get === null) {
            unset($_GET['lang']);
        } else {
            $_GET['lang'] = $previous_lang_get;
        }
    }
}

// =========================================================================================== \\

/**
 * Helper to return the WPML value for "all languages".
 *
 * @return string
 */
function ai4seo_wpml_return_all_languages(): string {
    return 'all';
}


// endregion
// ___________________________________________________________________________________________ \\
// region CRON JOBS (CRONJOBS) ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to schedule cron jobs
 * @return void
 */
function ai4seo_schedule_cron_jobs() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // add custom cron schedule for automated metadata generation
    if (!wp_next_scheduled(AI4SEO_BULK_GENERATION_CRON_JOB_NAME)) {
        wp_schedule_event(time(), "five_minutes", AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
    }

    // add custom cron schedule for analyzing the plugins performance
    if (!wp_next_scheduled(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME)) {
        wp_schedule_event(time(), "one_hour", AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME);
    }
}

// =========================================================================================== \\

/**
 * Function to un-schedule cron jobs
 * @return void
 */
function ai4seo_un_schedule_cron_jobs() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    wp_clear_scheduled_hook(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
    wp_clear_scheduled_hook(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME);
}

// =========================================================================================== \\

/**
 * Function to inject an additional cronjob call of a specific cronjob name, but only if there isn't already one scheduled within the next minute
 * @param $cronjob_name String the name of the cronjob
 * @return void
 */
function ai4seo_inject_additional_cronjob_call(string $cronjob_name, int $delay = 1) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(675990511, 'Prevented loop', true);
        return;
    }

    // is the cron job enabled?
    if (!AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron()) {
        return;
    }

    // Current time
    $now = time();

    // Define a constant for the minimum interval in seconds
    $bulk_generation_duration = (int) ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_DURATION);
    $min_delay_for_looping_cron_jobs = $bulk_generation_duration + 10;
    $cron_job_status = ai4seo_get_cron_job_status($cronjob_name);

    // do not allow an injection if the cron job status is still processing or initiating
    if (in_array($cron_job_status, array("processing", "initiating"))) {
        return;
    }

    // Get the next scheduled time for the event
    $next_scheduled = wp_next_scheduled($cronjob_name);

    // Schedule the event for ASAP only if there isn't already one scheduled within the $delay + 1 seconds
    if (!$next_scheduled || $next_scheduled > ($now + $delay + 1)) {
        // Clear the scheduled hook
        wp_unschedule_event($next_scheduled, $cronjob_name);

        // Schedule it to run ASAP (in $delay seconds)
        wp_schedule_single_event($now + $delay, $cronjob_name);

        // set the status to scheduled
        ai4seo_set_cron_job_status($cronjob_name, "scheduled");
    }
}

// =========================================================================================== \\

/**
 * Function to add custom cron schedule
 * @param $schedules
 * @return mixed
 */
function ai4seo_add_cron_job_intervals($schedules) {
    $schedules["five_minutes"] = array(
        "interval" => 60 * 5, // Number of seconds, 5 minutes in seconds.
        "display"  => __("Every Five Minutes", "ai-for-seo"),
    );

    $schedules["one_hour"] = array(
        "interval" => 60 * 60, // Number of seconds, 60 minutes in seconds.
        "display"  => __("Every Hour", "ai-for-seo"),
    );

    return $schedules;
}

// =========================================================================================== \\

/**
 * Function to set the last execution time of a cronjob
 * @param $cron_job_name string the name of the cronjob
 * @param int $time the time of the last execution
 * @return bool true on success, false on failure
 */
function ai4seo_set_last_cron_job_call_time(string $cron_job_name, int $time = 0): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(422248054, 'Prevented loop', true);
        return false;
    }

    if (!wp_doing_cron()) {
        return false;
    }

    if (!is_numeric($time)) {
        return false;
    }

    $cron_job_name = sanitize_key($cron_job_name);
    $cron_job_name = preg_replace("/[^a-zA-Z0-9_]/", "", $cron_job_name);

    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL, $time);
    $last_specific_cronjob_calls = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS);
    $last_specific_cronjob_calls[$cron_job_name] = $time;
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS, $last_specific_cronjob_calls);

    return true;
}

// =========================================================================================== \\

/**
 * Function to get the last execution time of a cronjob
 * @param $cron_job_name string the name of the cronjob
 * @return int the last execution time of a cronjob
 */
function ai4seo_get_last_cron_job_call_time(string $cron_job_name = ""): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(877770607, 'Prevented loop', true);
        return 0;
    }

    if ($cron_job_name) {
        $cron_job_name = sanitize_key($cron_job_name);
        $cron_job_name = preg_replace("/[^a-zA-Z0-9_]/", "", $cron_job_name);

        $last_specific_cronjob_calls = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS);
        return (int) ($last_specific_cronjob_calls[$cron_job_name] ?? 0);
    } else {
        return (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL);
    }
}

// =========================================================================================== \\

/**
 * Function to get the current status of a specific cron job
 * @param $cron_job_name string the name of the cron job
 * @return string the status of the cron job
 */
function ai4seo_get_cron_job_status(string $cron_job_name): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(876565543, 'Prevented loop', true);
        return '';
    }

    $all_cronjob_job_status = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST);

    return $all_cronjob_job_status[$cron_job_name] ?? "unknown";
}

// =========================================================================================== \\

/**
 * Function to set the current status of a specific cron job
 * @param $cron_job_name string the name of the cron job
 * @param $status string the status of the cron job
 * @return bool true on success, false on failure
 */
function ai4seo_set_cron_job_status(string $cron_job_name, string $status): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(164630102, 'Prevented loop', true);
        return false;
    }

    if (!wp_doing_cron()) {
        return false;
    }

    $status = sanitize_key($status);

    // first refresh the last status update time
    ai4seo_refresh_cron_job_status_update_time($cron_job_name);

    $all_cronjob_job_status = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST);
    $all_cronjob_job_status[$cron_job_name] = $status;
    return ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST, $all_cronjob_job_status);
}

// =========================================================================================== \\

/**
 * Function to refresh the last status update time of a specific cron job
 * @param $cron_job_name string the name of the cron job
 * @return bool true on success, false on failure
 */
function ai4seo_refresh_cron_job_status_update_time(string $cron_job_name): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(495640314, 'Prevented loop', true);
        return false;
    }

    $all_cronjob_job_status_time = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES);
    $all_cronjob_job_status_time[$cron_job_name] = time();

    return ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES, $all_cronjob_job_status_time);
}

// =========================================================================================== \\

/**
 * Function to get the last status update time of a specific cron job
 * @param $cron_job_name string the name of the cron job
 * @return int the last status update time of the cron job
 */
function ai4seo_get_cron_job_status_update_time(string $cron_job_name): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(190451061, 'Prevented loop', true);
        return 0;
    }

    $all_cronjob_job_status_time = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES);

    return $all_cronjob_job_status_time[$cron_job_name] ?? 0;
}

// CRONJOB: ai4seo_automated_generation_cron_job() ============================================================== \\

/**
 * Function to automatically generate data for different kind of contexts
 * @return bool true on success, false on failure
 */
function ai4seo_automated_generation_cron_job($debug = false): bool {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return true;
    }

    // is the cron job enabled?
    if (!AI4SEO_CRON_JOBS_ENABLED && wp_doing_cron()) {
        return true;
    }

    $bulk_generation_duration = (int) ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_DURATION);

    if (!$bulk_generation_duration) {
        $bulk_generation_duration = AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_BULK_GENERATION_DURATION];
    }

    $max_execution_time = $debug ? 20 : ($bulk_generation_duration - 5);
    $approximate_single_run_duration = 10;
    $max_tolerated_execution_time = $debug ? 25 : ($bulk_generation_duration + 10);
    $max_runs = $debug ? 3 : round($bulk_generation_duration / 3);
    $metadata_credits_cost_per_post = ai4seo_calculate_metadata_credits_cost_per_post();
    $attachment_attributes_credits_costs_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();
    $min_credits_cost_per_entry = min($metadata_credits_cost_per_post, $attachment_attributes_credits_costs_per_attachment_post);

    // set the maximum execution time according to these functions needs
    ai4seo_safe_set_time_limit($max_tolerated_execution_time + 30);

    // define the start time of this cron job function call
    $start_time = time();
    $cron_job_status = ai4seo_get_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
    $cron_job_status_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
    $last_cron_job_status_update_was_recently = $start_time - $cron_job_status_update_time < $max_tolerated_execution_time;
    $last_cron_job_is_still_processing = in_array($cron_job_status, array("processing", "initiating"));

    // if the last cron job call was too recent, we should skip this call.
    // Maybe there was an server error, and we should give the server some time to recover
    if ($last_cron_job_is_still_processing && $last_cron_job_status_update_was_recently) {
        if ($debug) {
            ai4seo_debug_message(267805633, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because we're too close to another unfinished cron job call")));
        }

        return true;
    }

    // update the last execution time of this cron job
    ai4seo_set_last_cron_job_call_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, $start_time);
    ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "initiating");


    // CHECK USERS ROBHUB ACCOUNT
    $is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

    if (!$is_robhub_account_synced) {
        if ($debug) {
            ai4seo_debug_message(284451160, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Robhub account not synced -> skip")));
        }

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "finished-with-error");
        return false;
    }

    // check if credentials are set
    if (!ai4seo_robhub_api()->init_credentials(false)) {
        if ($debug) {
            ai4seo_debug_message(755826516, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("auth failed -> skip")));
        }

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "finished-with-error");
        return false;
    }

    // check the current credits balance, compare it to $min_credits_cost_per_entry and if it's lower, return true
    if (ai4seo_robhub_api()->get_credits_balance() < $min_credits_cost_per_entry) {
        if ($debug) {
            ai4seo_debug_message(458928065, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because of low Credits balance")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "low-credits-balance");
        return true;
    }

    // if no bulk generation is enabled, we can savely return here
    if (!ai4seo_is_any_bulk_generation_enabled()) {
        if ($debug) {
            ai4seo_debug_message(277251009, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because every automated generation is disabled")));
        }

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "bulk-generation-disabled");
        return true;
    }

    // check if we have a posts table analysis completed
    $posts_table_analysis_state = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false);

    // if we have a posts table analysis not completed, we should first help to finish it
    if ($posts_table_analysis_state !== 'completed') {
        if ($debug) {
            ai4seo_debug_message(240531747, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("posts table analysis not completed -> try to continue it first")));
        }

        ai4seo_try_start_posts_table_analysis();

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "waiting-for-posts-table-analysis");
        return true;
    }

    $run_counter = 1;

    do {
        $made_some_progress = false;

        if ($debug) {
            ai4seo_debug_message(635240362, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("start new run: #{$run_counter}")));
        }

        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "processing");

        // metadata & keyphrase
        $success = ai4seo_automated_metadata_generation($debug);

        if ($success) {
            $made_some_progress = true;
        }

        // attachments
        $success = ai4seo_automated_attachment_attributes_generation($debug);

        if ($success) {
            $made_some_progress = true;
        }

        if ($made_some_progress) {
            sleep(3);
            $run_counter++;
        } else {
            break;
        }
    } while (
        $made_some_progress &&
        time() - $start_time < $max_execution_time - $approximate_single_run_duration &&
        $run_counter <= $max_runs
    );

    // workaround: empty all leftover processing ids (only relevant if the generation was aborted for an unknown reason)
    ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, array());
    ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, array());

    // reschedule this cronjob asap, so that the next posts can be filled shortly
    if ($made_some_progress) {
        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "finished");
        ai4seo_inject_additional_cronjob_call(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);
    } else {
        ai4seo_set_cron_job_status(AI4SEO_BULK_GENERATION_CRON_JOB_NAME, "idle");
    }

    return true;
}

// =========================================================================================== \\

/**
 * Function to automatically generate metadata for posts
 * @return bool true on success, false on failure
 */
function ai4seo_automated_metadata_generation($debug = false, $only_this_post_id = 0): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(374991526, 'Prevented loop', true);
        return false;
    }

    $active_meta_tags = ai4seo_get_active_meta_tags();

    if (!$active_meta_tags) {
        if ($debug) {
            ai4seo_debug_message(979105658, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no active meta tags found -> skip")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");

        return false;
    }

    $metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

    // check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true
    if (ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post) {
        if ($debug) {
            ai4seo_debug_message(275318901, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because of low Credits balance")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");
        return false;
    }

    // handle one single post id, if given, otherwise excavate new posts with missing metadata
    if ($only_this_post_id) {
        $post_id = $only_this_post_id;
    } else {
        // try to search for posts with missing metadata
        $got_new_pending_posts = ai4seo_excavate_post_entries_with_missing_metadata($debug);

        if (!$got_new_pending_posts) {
            if ($debug) {
                ai4seo_debug_message(513505109, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No new pending posts found")));
            }

            // remove all processing and pending ids
            ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
            ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");
            return false;
        }

        $pending_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);

        if (!$pending_post_ids) {
            // skip here because we don't have any posts or pages
            if ($debug) {
                ai4seo_debug_message(872810398, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No pending posts found")));
            }

            // remove all processing and pending ids
            ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
            ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");
            return false;
        }

        if ($debug) {
            ai4seo_debug_message(809859626, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Found pending post(s): " . esc_html(implode(", ", $pending_post_ids)))));
        }

        // only take one post id
        $post_id = reset($pending_post_ids);
    }

    // make sure every entry is numeric
    if (!is_numeric($post_id) || !$post_id) {
        if ($debug) {
            ai4seo_debug_message(940723212, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("post-id is not numeric or not set")));
        }

        return false;
    }

    if ($debug) {
        ai4seo_debug_message(439931261, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("trying to generate metadata for #" . esc_html($post_id))));
    }

    // let's find fields to generate for this post id
    $generate_this_fields = $active_meta_tags;
    $old_generated_metadata = ai4seo_read_generated_data_from_post_meta($post_id);
    $old_available_metadata = ai4seo_read_available_metadata($post_id);
    $overwrite_existing_metadata = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA);
    $focus_keyphrase_behavior = ai4seo_get_setting(AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA);

    if (!is_array($overwrite_existing_metadata)) {
        $overwrite_existing_metadata = array();
    }

    // handle focus keyphrase behavior when existing meta title/description are present (SEO Autopilot)
    // consider both meta title and meta description as not generated, so that we can regenerate them
    // however, if we don't generate the meta title or description for some reason, the focus keyphrase generation will be skipped later
    if (in_array("focus-keyphrase", $generate_this_fields) && $focus_keyphrase_behavior === AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE) {
        unset($old_generated_metadata["meta-title"]);
        unset($old_generated_metadata["meta-description"]);
    }

    // remove all already generated metadata from the $generate_this_meta_tags array
    foreach ($old_generated_metadata as $this_metadata_identifier => $this_metadata_value) {
        // not available -> skip, despite we generated it before
        if (!isset($old_available_metadata[$this_metadata_identifier]) || !$old_available_metadata[$this_metadata_identifier]) {
            continue;
        }

        // already generated -> skip
        if (in_array($this_metadata_identifier, $generate_this_fields) && $this_metadata_value) {
            $this_index = array_search($this_metadata_identifier, $generate_this_fields);
            unset($generate_this_fields[$this_index]);
        }
    }

    // nothing left to generate -> skip
    if (!$generate_this_fields) {
        if ($debug) {
            ai4seo_debug_message(594533434, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no missing metadata found for post-id")));
        }

        // all metadata is already generated
        ai4seo_remove_post_ids_from_all_generation_status_options($post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id);
        return true;
    }

    // check for available metadata (from 3rd party seo plugins)
    // and remove meta tags from the missing metadata array that are already available, if we don't want to overwrite them
    $is_post_fully_covered = true;

    foreach ($generate_this_fields AS $this_entry_index => $this_metadata_identifier) {
        if (isset($old_available_metadata[$this_metadata_identifier])
            && $old_available_metadata[$this_metadata_identifier]
            && !in_array($this_metadata_identifier, $overwrite_existing_metadata)) {
            unset($generate_this_fields[$this_entry_index]);
            continue;
        }

        if (!isset($old_available_metadata[$this_metadata_identifier]) || !$old_available_metadata[$this_metadata_identifier]) {
            $is_post_fully_covered = false;
        }
    }

    // if we skip or regenerate the focus keyphrase, but neither meta title nor meta description is in the generation list, we should also skip the focus keyphrase generation
    if (($focus_keyphrase_behavior === AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP || $focus_keyphrase_behavior === AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE)
        && in_array("focus-keyphrase", $generate_this_fields)
        && !in_array("meta-title", $generate_this_fields)
        && !in_array("meta-description", $generate_this_fields)) {
        unset($generate_this_fields[array_search("focus-keyphrase", $generate_this_fields)]);
    }

    // nothing left to generate -> skip
    if (!$generate_this_fields) {
        if ($debug) {
            ai4seo_debug_message(777726188, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no missing metadata found for post-id")));
        }

        // all metadata is already generated
        ai4seo_remove_post_ids_from_all_generation_status_options($post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id);
        return true;
    }

    // make sure to abort, if we have full coverage and don't want to generate metadata for fully covered entries
    $generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();

    if ($is_post_fully_covered && !$generate_metadata_for_fully_covered_entries) {
        if ($debug) {
            ai4seo_debug_message(245485518, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("full metadata coverage found and generation for fully covered entries is disabled -> skip")));
        }

        ai4seo_remove_post_ids_from_all_generation_status_options($post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id);
        return true;
    }

    // mark post as being processed
    ai4seo_add_post_ids_to_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $post_id);

    // first, let's get a summary of the content
    $post_content = ai4seo_get_condensed_post_content_from_database($post_id);
    ai4seo_add_post_context($post_id, $post_content);
    $post_content = sanitize_text_field($post_content);

    // if we have original content -> go ahead
    $content_length = ai4seo_mb_strlen($post_content);

    // check if content is at least AI4SEO_TOO_SHORT_CONTENT_LENGTH characters long
    if ($content_length < AI4SEO_TOO_SHORT_CONTENT_LENGTH) {
        ai4seo_handle_failed_metadata_generation($post_id, __FUNCTION__, "Post content is too short for post ID: " . $post_id, $debug);
        ai4seo_add_latest_activity_entry($post_id, "error", "metadata-bulk-generated", 0, "Post content is too short");
        return true;
    }

    // check if content is not larger than AI4SEO_MAX_TOTAL_CONTENT_SIZE characters
    if ($content_length > AI4SEO_MAX_TOTAL_CONTENT_SIZE) {
        ai4seo_handle_failed_metadata_generation($post_id, __FUNCTION__, "Post content is too long for post ID: " . $post_id, $debug);
        ai4seo_add_latest_activity_entry($post_id, "error", "metadata-bulk-generated", 0, "Post content is too long");
        return true;
    }

    // here we put our new generated data
    $metadata_generation_language = ai4seo_get_posts_language($post_id);
    $metadata_generation_language = sanitize_text_field($metadata_generation_language);

    $robhub_api_call_parameters = array(
        "content" => $post_content,
        "language" => $metadata_generation_language,
    );

    // check for a key phrase
    $third_party_keyphrase = sanitize_text_field(ai4seo_get_any_third_party_seo_plugin_keyphrase($post_id));

    if ($third_party_keyphrase) {
        $robhub_api_call_parameters["keyphrase"] = $third_party_keyphrase;
    }

    $robhub_api_call_parameters["trigger"] = "automated";
    $robhub_api_call_parameters["context"] = ai4seo_get_website_context();

    // url
    $post_permalink = get_permalink($post_id);

    if ($post_permalink) {
        $robhub_api_call_parameters["content_url"] = $post_permalink;
    }

    // collect and build field instructions
    $field_instructions = array();
    $metadata_prefixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_PREFIXES);
    $metadata_suffixes = ai4seo_get_setting(AI4SEO_SETTING_METADATA_SUFFIXES);
    $placeholder_replacements = ai4seo_get_metadata_placeholder_replacements($post_id);

    foreach (AI4SEO_METADATA_DETAILS AS $this_metadata_identifier => $this_metadata_details) {
        $this_to_generate = in_array($this_metadata_identifier, $generate_this_fields);
        $this_old_value = $old_available_metadata[$this_metadata_identifier] ?? "";
        $this_prefix = $metadata_prefixes[$this_metadata_identifier] ?? "";
        $this_suffix = $metadata_suffixes[$this_metadata_identifier] ?? "";

        if (!$this_to_generate && !$this_old_value) {
            continue;
        }

        $this_prefix = ai4seo_replace_text_placeholders($this_prefix, $placeholder_replacements);
        $this_suffix = ai4seo_replace_text_placeholders($this_suffix, $placeholder_replacements);

        $field_instructions[$this_metadata_identifier] = array(
            "generate" => $this_to_generate,
            "old_value" => $this_old_value,
            "prefix" => $this_prefix,
            "suffix" => $this_suffix,
        );
    }

    $robhub_api_call_parameters["approximate_cost"] = ai4seo_calculate_metadata_credits_cost_per_post($generate_this_fields);
    $robhub_api_call_parameters["field_instructions"] = $field_instructions;

    $results = ai4seo_robhub_api()->call("ai4seo/generate-all-metadata", $robhub_api_call_parameters);


    // CHECK RESULTS

    if (!ai4seo_robhub_api()->was_call_successful($results)) {
        $error_message = $results["message"] ?? "Generation with API endpoint failed for post ID: " . $post_id;

        if (isset($results["code"])) {
            $error_message .= " (Error #" . sanitize_text_field($results["code"]) . ")";
        }

        ai4seo_handle_failed_metadata_generation($post_id, __FUNCTION__, $error_message . ($debug ? ": " . ai4seo_stringify($results) : ""), $debug);
        ai4seo_add_latest_activity_entry($post_id, "error", "metadata-bulk-generated", 0, $error_message);

        ai4seo_debug_message(4133326, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Generation with API endpoint failed for post ID: " . $post_id . ": " . ai4seo_stringify($results))), true);
        return false;
    }


    // ALL GOOD -> PROCEED TO SAVE THE RESULTS

    $raw_new_generated_metadata = $results["data"] ?? array();

    if (empty($raw_new_generated_metadata) || !is_array($raw_new_generated_metadata)) {
        ai4seo_handle_failed_metadata_generation($post_id, __FUNCTION__, "Generation with API endpoint failed for post ID: " . $post_id . ($debug ? ": " . ai4seo_stringify($results) : ""), $debug);
        ai4seo_add_latest_activity_entry($post_id, "error", "metadata-bulk-generated", 0, "No data returned from API endpoint");
        return false;
    }


    $new_generated_metadata = array();

    // convert from api_identifier to our internal metadata identifiers, remove unrequested fields
    foreach (AI4SEO_METADATA_DETAILS AS $this_metadata_identifier => $this_metadata_details) {
        $this_api_identifier = $this_metadata_details['api-identifier'] ?? "";

        if (!isset($raw_new_generated_metadata[$this_api_identifier])) {
            continue;
        }

        if (!in_array($this_metadata_identifier, $generate_this_fields)) {
            unset($raw_new_generated_metadata[$this_api_identifier]);
            continue;
        }

        $new_generated_metadata[$this_metadata_identifier] = sanitize_text_field($raw_new_generated_metadata[$this_api_identifier]);
    }


    // UPDATE

    // update metadata to be the new active metadata
    $this_success = ai4seo_update_active_metadata($post_id, $new_generated_metadata, true);

    if (!$this_success) {
        ai4seo_handle_failed_metadata_generation($post_id, __FUNCTION__, "Could not save generated metadata for post ID: " . $post_id, $debug);
        ai4seo_add_latest_activity_entry($post_id, "error", "metadata-bulk-generated", 0, "Could not save generated metadata");
        return false;
    }

    // save generated data to post meta table
    ai4seo_save_generated_data_to_postmeta($post_id, $new_generated_metadata);
    ai4seo_save_post_content_summary_to_postmeta($post_id, $post_content);

    // set posts as fully covered and generated
    ai4seo_remove_post_ids_from_all_generation_status_options($post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_id);

    // add success entry to the latest activity log
    ai4seo_add_latest_activity_entry($post_id, "success", "metadata-bulk-generated", (int) ($results["credits-consumed"] ?? 0));

    if ($debug) {
        ai4seo_debug_message(523529302, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("metadata generated for post ID: " . $post_id . ": " . esc_html(ai4seo_stringify($new_generated_metadata)))));
    }

    return true;
}

// =========================================================================================== \\

/**
 * Helps handle failed metadata generation by removing the post id from all generation status options and adding it to the failed ones
 * @param $post_id int the attachment post id
 * @param $function_name string the name of the function that failed
 * @param $error_message string the error message
 * @param $debug bool if true, debug information will be printed
 * @return void
 */
function ai4seo_handle_failed_metadata_generation(int $post_id, string $function_name = "", string $error_message = "", bool $debug = false) {
    if ($function_name) {
        ai4seo_debug_message(585453895, esc_html($function_name) . " >" . esc_html(ai4seo_stringify($error_message)), true);
    } else {
        ai4seo_debug_message(791605560, $error_message, true);
    }

    ai4seo_remove_post_ids_from_all_generation_status_options($post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME, $post_id);
}

// =========================================================================================== \\

/**
 * Determines whether to use base64 encoding or URL for image upload based on user setting and automatic logic
 * @param string $attachment_url The attachment URL to check
 * @return bool true if base64 should be used, false if URL should be used
 */
function ai4seo_should_use_base64_image(string $attachment_url): bool {
    global $ai4seo_allowed_image_file_type_names;

    // Get the user's preference for image upload method
    $image_upload_method = ai4seo_get_setting(AI4SEO_SETTING_IMAGE_UPLOAD_METHOD);

    switch ($image_upload_method) {
        case 'base64':
            // User explicitly chose base64 - always encode and send image data directly
            return true;

        case 'url':
            // User explicitly chose URL - always send the image URL
            return false;

        case 'auto':
        default:
            // Auto mode: use intelligent logic to decide the best method
            // Default to URL method for better performance (smaller payload)
            $ai4seo_use_base64_image = false;

            // First check: Validate URL format
            // If URL format is invalid, we must use base64 as fallback
            if (!filter_var($attachment_url, FILTER_VALIDATE_URL)) {
                $ai4seo_use_base64_image = true;
            }

            // Second check: Detect localhost/development environments
            // Our API cannot access localhost URLs, so base64 is required
            if (!$ai4seo_use_base64_image && ai4seo_robhub_api()->are_we_on_a_localhost_system()) {
                $ai4seo_use_base64_image = true;
            }

            // third check: Validate file type at the end of the URL
            if (!$ai4seo_use_base64_image) {
                // Get the file extension from the URL
                $file_extension = pathinfo($attachment_url, PATHINFO_EXTENSION);

                // If the file extension is not in our allowed list, we must use base64
                if (!in_array(strtolower($file_extension), $ai4seo_allowed_image_file_type_names)) {
                    $ai4seo_use_base64_image = true;
                }
            }

            // Third check: Test URL accessibility (only if we haven't already decided on base64)
            if (!$ai4seo_use_base64_image) {
                // Attempt to get HTTP headers to verify the URL is accessible
                $attachment_url_headers = get_headers($attachment_url);

                // If we can't get headers or they're malformed, the URL is not accessible
                if (!$attachment_url_headers || !is_array($attachment_url_headers) || !isset($attachment_url_headers[0])) {
                    $ai4seo_use_base64_image = true;
                }

                // Check for successful HTTP response (200 OK)
                // If the response is not successful, our Server won't be able to access the URL
                if (strpos($attachment_url_headers[0], "200") === false) {
                    $ai4seo_use_base64_image = true;
                }
            }

            return $ai4seo_use_base64_image;
    }
}

// =========================================================================================== \\

/**
 * Function to automatically generate attributes for attachments
 * @param bool $debug debug mode yes or no
 * @param int $only_this_attachment_post_id care only this attachment post id
 * @return bool true on success, false on failure
 */
function ai4seo_automated_attachment_attributes_generation(bool $debug = false, int $only_this_attachment_post_id = 0): bool {
    global $ai4seo_allowed_attachment_mime_types;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(583323686, 'Prevented loop', true);
        return false;
    }

    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();
    $supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

    if (!$active_attachment_attributes) {
        if ($debug) {
            ai4seo_debug_message(156074497, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no active meta tags found -> skip")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");

        return false;
    }

    $approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

    // check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return true
    if (ai4seo_robhub_api()->get_credits_balance() < $approximate_cost_per_attachment_post) {
        if ($debug) {
            ai4seo_debug_message(616491274, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because of low Credits balance")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");

        return false;
    }

    if ($only_this_attachment_post_id) {
        $attachment_post_id = $only_this_attachment_post_id;
    } else {
        // try to search for attachment posts with missing attributes
        $got_new_pending_attachment_post_ids = ai4seo_excavate_attachments_with_missing_attributes($debug);

        if (!$got_new_pending_attachment_post_ids) {
            // skip here because we don't have any attachment posts
            if ($debug) {
                ai4seo_debug_message(466380276, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No pending media posts found")));
            }

            // remove all processing and pending ids
            ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");
            ai4seo_update_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");

            return false;
        }

        $pending_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

        if (!$pending_attachment_post_ids) {
            // skip here because we don't have any attachment posts
            if ($debug) {
                ai4seo_debug_message(170584235, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No pending media posts found")));
            }
            return false;
        }

        if ($debug) {
            ai4seo_debug_message(968541383, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Found pending media post(s): " . esc_html(implode(", ", $pending_attachment_post_ids)))));
        }

        // only take one post id
        $attachment_post_id = reset($pending_attachment_post_ids);
    }

    // make sure every entry is numeric
    if (!is_numeric($attachment_post_id)) {
        if ($debug) {
            ai4seo_debug_message(993108805, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("media post-id is not numeric")));
        }
        return false;
    }

    if ($debug) {
        ai4seo_debug_message(537974883, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("going to generate media attributes for #" . esc_html($attachment_post_id))));
    }

    $generate_this_fields = $active_attachment_attributes;
    $old_generated_attachment_attributes = ai4seo_read_generated_data_from_post_meta($attachment_post_id);
    $old_available_attachment_attributes = ai4seo_read_available_attachment_attributes($attachment_post_id);

    // remove all already generated metadata from the $generate_this_meta_tags array
    foreach ($old_generated_attachment_attributes as $this_attachment_attribute_identifier => $this_attachment_attribute_value) {
        // not available -> skip, despite we generated it before
        if (!isset($old_available_attachment_attributes[$this_attachment_attribute_identifier]) || !$old_available_attachment_attributes[$this_attachment_attribute_identifier]) {
            continue;
        }

        if (in_array($this_attachment_attribute_identifier, $generate_this_fields) && $this_attachment_attribute_value) {
            $this_index = array_search($this_attachment_attribute_identifier, $generate_this_fields);
            unset($generate_this_fields[$this_index]);
        }
    }

    // nothing left to generate -> skip
    if (!$generate_this_fields) {
        if ($debug) {
            ai4seo_debug_message(375893908, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no missing attachment attributes found for post-id")));
        }

        // all metadata is already generated
        ai4seo_remove_post_ids_from_all_generation_status_options($attachment_post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
        return true;
    }

    // check for available attachment attributes
    // and remove meta tags from the missing metadata array that are already available, if we don't want to overwrite them
    $overwrite_existing_attachment_attributes = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES);
    $is_attachment_post_fully_covered = true;

    foreach ($generate_this_fields AS $this_index => $this_attachment_attribute_identifier) {
        if (isset($old_available_attachment_attributes[$this_attachment_attribute_identifier])
            && $old_available_attachment_attributes[$this_attachment_attribute_identifier]
            && !in_array($this_attachment_attribute_identifier, $overwrite_existing_attachment_attributes)) {
            unset($generate_this_fields[$this_index]);
            continue;
        }

        if (!isset($old_available_attachment_attributes[$this_attachment_attribute_identifier]) || !$old_available_attachment_attributes[$this_attachment_attribute_identifier]) {
            $is_attachment_post_fully_covered = false;
        }
    }

    // nothing left to generate -> skip
    if (!$generate_this_fields) {
        if ($debug) {
            ai4seo_debug_message(556643354, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("no missing attachment found found for attachment post-id")));
        }

        // all metadata is already generated
        ai4seo_remove_post_ids_from_all_generation_status_options($attachment_post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
        return true;
    }

    // make sure to abort, if we have full coverage and don't want to generate attachment attribute for fully covered entries
    $generate_attachment_attributes_for_fully_covered_entries = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES);

    if ($is_attachment_post_fully_covered && !$generate_attachment_attributes_for_fully_covered_entries) {
        if ($debug) {
            ai4seo_debug_message(334900672, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("full attachment atributes coverage found and generation for fully covered entries is disabled -> skip")));
        }

        ai4seo_remove_post_ids_from_all_generation_status_options($attachment_post_id);
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
        return true;
    }

    // mark post as being processed
    ai4seo_add_post_ids_to_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);

    // there are missing attachment attributes -> generate it
    // first, let's get the wp_post entry for more checks
    $attachment_post = get_post($attachment_post_id);
    $attachment_post_type = $attachment_post->post_type;
    $attachment_post_mime_type = ai4seo_get_attachment_post_mime_type($attachment_post_id);

    // check if it's an attachment
    if (!$attachment_post || !in_array($attachment_post_type, $supported_attachment_post_types)) {
        ai4seo_handle_failed_attachment_generation($attachment_post_id, __FUNCTION__, "Post is not a media for media post ID: " . $attachment_post_id, $debug);
        ai4seo_add_latest_activity_entry($attachment_post_id, "error", "attachment-attributes-bulk-generated", 0, "Post is not a media");
        return true;
    }

    // check if it's one of the allowed mime types
    if (!in_array($attachment_post_mime_type, $ai4seo_allowed_attachment_mime_types)) {
        ai4seo_handle_failed_attachment_generation($attachment_post_id, __FUNCTION__, "Mime type not supported for media post ID: " . $attachment_post_id, $debug);
        ai4seo_add_latest_activity_entry($attachment_post_id, "error", "attachment-attributes-bulk-generated", 0, "Mime type not supported");
        return true;
    }

    // check url of the attachment
    $attachment_url = ai4seo_get_attachment_url($attachment_post_id);

    if (!$attachment_url) {
        ai4seo_handle_failed_attachment_generation($attachment_post_id, __FUNCTION__, "Media URL not found for media post ID: " . $attachment_post_id, $debug);
        ai4seo_add_latest_activity_entry($attachment_post_id, "error", "attachment-attributes-bulk-generated", 0, "Media URL not found");
        return true;
    }

    $use_base64_image = ai4seo_should_use_base64_image($attachment_url);

    // PREPARE ROBHUB API CALL
    $attachment_attributes_generation_language = ai4seo_get_attachments_language($attachment_post_id);

    $robhub_api_call_parameters = array(
        "language" => $attachment_attributes_generation_language,
    );

    $robhub_api_call_parameters["trigger"] = "automated";
    $robhub_api_call_parameters["context"] = ai4seo_get_website_context();

    $attachment_usage_context = ai4seo_get_attachment_post_related_context($attachment_post_id);

    if (!empty($attachment_usage_context)) {
        $robhub_api_call_parameters["attachment_usage_context"] = $attachment_usage_context;
    }

    // collect and build field instructions
    $field_instructions = array();
    $attachment_attributes_prefixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES);
    $attachment_attributes_suffixes = ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES);
    $attachment_placeholder_replacements = ai4seo_get_attachment_placeholder_replacements($attachment_post_id);

    foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS AS $this_attachment_attribute_identifier => $this_attachment_attribute_details) {
        $this_to_generate = in_array($this_attachment_attribute_identifier, $generate_this_fields);
        $this_old_value = $old_available_attachment_attributes[$this_attachment_attribute_identifier] ?? "";
        $this_prefix = $attachment_attributes_prefixes[$this_attachment_attribute_identifier] ?? "";
        $this_suffix = $attachment_attributes_suffixes[$this_attachment_attribute_identifier] ?? "";

        if (!$this_to_generate && !$this_old_value) {
            continue;
        }

        $this_prefix = ai4seo_replace_text_placeholders($this_prefix, $attachment_placeholder_replacements);
        $this_suffix = ai4seo_replace_text_placeholders($this_suffix, $attachment_placeholder_replacements);

        $field_instructions[$this_attachment_attribute_identifier] = array(
            "generate" => $this_to_generate,
            "old_value" => $this_old_value,
            "prefix" => $this_prefix,
            "suffix" => $this_suffix,
        );
    }

    $robhub_api_call_parameters["approximate_cost"] = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post($generate_this_fields);
    $robhub_api_call_parameters["field_instructions"] = $field_instructions;

    // API CALL WITH ATTACHMENT URL
    if (!$use_base64_image) {
        $robhub_api_call_parameters["attachment_url"] = $attachment_url;

        $results = ai4seo_robhub_api()->call("ai4seo/generate-all-attachment-attributes", $robhub_api_call_parameters);

        if (!ai4seo_robhub_api()->was_call_successful($results) && ai4seo_robhub_api()->can_retry_generation_with_base64($results)) {
            unset($robhub_api_call_parameters["attachment_url"]);
            $use_base64_image = true;
        }
    }

    // API CALL WITH BASE64 ENCODED IMAGE
    if ($use_base64_image) {
        $results = ai4seo_generate_attachment_attributes_using_base64($attachment_url, $attachment_post_mime_type, $robhub_api_call_parameters);
    }


    // CHECK RESULTS

    if (!ai4seo_robhub_api()->was_call_successful($results ?? false)) {
        $error_message = $results["message"] ?? "Generation with API endpoint failed for attachment post ID: " . $attachment_post_id;

        if (isset($results["code"])) {
            $error_message .= " (Error #" . sanitize_text_field($results["code"]) . ")";
        }

        ai4seo_handle_failed_attachment_generation($attachment_post_id, __FUNCTION__, $error_message . ($debug ? ": " . ai4seo_stringify($results) : ""), $debug);
        ai4seo_add_latest_activity_entry($attachment_post_id, "error", "attachment-attributes-bulk-generated", 0, $error_message);

        ai4seo_debug_message(4133326, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("API call failed for media post ID: " . $attachment_post_id . ": " . ai4seo_stringify($results))), true);
        return false;
    }


    // ALL GOOD -> PROCEED TO SAVE the RESULTS

    $raw_new_attachment_attributes = $results["data"] ?? array();

    if (empty($raw_new_attachment_attributes) || !is_array($raw_new_attachment_attributes)) {
        ai4seo_handle_failed_attachment_generation($attachment_post_id, __FUNCTION__, "Could not interpret data for media post ID: " . $attachment_post_id . ($debug ? ": " . ai4seo_stringify($results) : ""), $debug);
        ai4seo_add_latest_activity_entry($attachment_post_id, "error", "attachment-attributes-bulk-generated", 0, "Could not interpret data");
        return false;
    }

    $new_attachment_attributes = array();

    // convert from api_identifier to our internal attachment attribute identifiers, remove unrequested fields
    foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS AS $this_attachment_attribute_identifier => $this_attachment_attribute_details) {
        $this_api_identifier = $this_attachment_attribute_details['api-identifier'] ?? "";

        if (!isset($raw_new_attachment_attributes[$this_api_identifier])) {
            continue;
        }

        if (!in_array($this_attachment_attribute_identifier, $generate_this_fields)) {
            unset($raw_new_attachment_attributes[$this_api_identifier]);
            continue;
        }

        $new_attachment_attributes[$this_attachment_attribute_identifier] = sanitize_text_field($raw_new_attachment_attributes[$this_api_identifier]);
    }


    // ADD PREFIX- AND SUFFIX-DATA

    foreach ($new_attachment_attributes as $attachment_attribute_identifier => $attachment_attribute_value) {
        // not a field we wanted to generate -> skip
        if (!isset($field_instructions[$attachment_attribute_identifier])) {
            continue;
        }

        $this_attachment_attribute_prefix = trim(sanitize_text_field($attachment_attributes_prefixes[$attachment_attribute_identifier] ?? ""));
        $this_attachment_attribute_suffix = trim(sanitize_text_field($attachment_attributes_suffixes[$attachment_attribute_identifier] ?? ""));

        if (!$this_attachment_attribute_prefix && !$this_attachment_attribute_suffix) {
            continue;
        }

        $this_attachment_attribute_prefix = ai4seo_replace_text_placeholders($this_attachment_attribute_prefix, $attachment_placeholder_replacements);
        $this_attachment_attribute_suffix = ai4seo_replace_text_placeholders($this_attachment_attribute_suffix, $attachment_placeholder_replacements);

        // Add prefix and suffix
        $attachment_attribute_value = trim($this_attachment_attribute_prefix . " " . $attachment_attribute_value . " " . $this_attachment_attribute_suffix);

        // Overwrite generated data entry
        $new_attachment_attributes[$attachment_attribute_identifier] = html_entity_decode($attachment_attribute_value);
    }

    ai4seo_update_attachment_attributes($attachment_post_id, $new_attachment_attributes, true);

    // save generated data to post meta table
    ai4seo_save_generated_data_to_postmeta($attachment_post_id, $new_attachment_attributes);

    // add the attachment post id to the already filled ones
    ai4seo_remove_post_ids_from_all_generation_status_options($attachment_post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);

    // add success entry to the latest activity log
    ai4seo_add_latest_activity_entry($attachment_post_id, "success", "attachment-attributes-bulk-generated", (int) ($results["credits-consumed"] ?? 0));

    if ($debug) {
        ai4seo_debug_message(422896712, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("updated media attributes for #" . esc_html($attachment_post_id) . ":" . esc_html(ai4seo_stringify($new_attachment_attributes)))));
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_generate_attachment_attributes_using_base64($attachment_url, $mime_type, $robhub_api_call_parameters) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(868051563, 'Prevented loop', true);
        return array(
            "success" => false,
            "message" => "Prevented infinite loop",
            "code" => 361324724,
        );
    }

    $base64_from_image_file_response = ai4seo_get_base64_from_image_file($attachment_url);

    if (isset($base64_from_image_file_response["success"]) && $base64_from_image_file_response["success"]
        && isset($base64_from_image_file_response["data"]) && $base64_from_image_file_response["data"]) {
        $attachment_base64 = $base64_from_image_file_response["data"];

        $attachment_base64_uri = "data:{$mime_type};base64,{$attachment_base64}";
        $robhub_api_call_parameters["reference_attachment_url"] = $attachment_url;
        $robhub_api_call_parameters["content"] = $attachment_base64_uri;

        $results = ai4seo_robhub_api()->call("ai4seo/generate-all-attachment-attributes", $robhub_api_call_parameters);
    } else {
        $results = array(
            "success" => false,
            "message" => $base64_from_image_file_response["message"] ?? "Unknown error",
            "code" => $base64_from_image_file_response["code"] ?? 361324725,
        );
        $results["message"] = "Error #" . $results["code"] . ": " . $results["message"];
    }

    return $results;
}

// =========================================================================================== \\

/**
 * Helps handle failed attachment generation by removing the post id from all generation status options and adding it to the failed ones
 * @param $attachment_post_id int the attachment post id
 * @param $function_name string the name of the function that failed
 * @param $error_message string the error message
 * @param $debug bool if true, debug information will be printed
 * @return void
 */
function ai4seo_handle_failed_attachment_generation(int $attachment_post_id, string $function_name = "", string $error_message = "", bool $debug = false) {
    if ($error_message) {
        if ($function_name) {
            ai4seo_debug_message(689393850, esc_html($function_name) . " >" . esc_html(ai4seo_stringify($error_message)), true);
        } else {
            ai4seo_debug_message(250362054, $error_message, true);
        }
    }

    ai4seo_remove_post_ids_from_all_generation_status_options($attachment_post_id);
    ai4seo_add_post_ids_to_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
}

// =========================================================================================== \\

/**
 * Function to excavate posts, pages, products etc. with missing metadata.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job" to find posts and pages that are missing metadata
 * @param bool $debug if true, debug information will be printed
 * @return bool
 */
function ai4seo_excavate_post_entries_with_missing_metadata(bool $debug = false): bool {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(730356485, 'Prevented loop', true);
        return false;
    }

    $metadata_credits_costs_per_post = ai4seo_calculate_metadata_credits_cost_per_post();

    // check the current credits balance, compare it to $metadata_credits_costs_per_post and if it's lower, return true
    if (ai4seo_robhub_api()->get_credits_balance() < $metadata_credits_costs_per_post) {
        if ($debug) {
            ai4seo_debug_message(690700036, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because of low Credits balance")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, "");

        return false;
    }

    $supported_post_types = ai4seo_get_supported_post_types();

    // find out if the automation is enabled
    $enabled_bulk_generation_post_types = array();

    foreach ($supported_post_types as $this_post_type) {
        if (ai4seo_is_bulk_generation_enabled($this_post_type)) {
            $enabled_bulk_generation_post_types[] = $this_post_type;
        }
    }

    // if automation is completely disabled -> return
    if (!$enabled_bulk_generation_post_types) {
        if ($debug) {
            ai4seo_debug_message(894341588, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No automation enabled")));
        }

        return false;
    }

    // check the number of already pending posts
    $pending_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);

    if ($pending_metadata_post_ids && count($pending_metadata_post_ids) >= 2) {
        // skip here because we already have two posts pending, that are going to be processed
        // better keep the amount of post ids low if the user suddenly stops the automation
        if ($debug) {
            ai4seo_debug_message(693756939, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Already >= 2 posts pending -> skip")));
        }

        return true;
    }

    // only these posts we have to look for
    $missing_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME);

    if (!$missing_metadata_post_ids) {
        // skip here because we don't have any posts or pages
        if ($debug) {
            ai4seo_debug_message(411642134, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No posts found")));
        }

        return false;
    }

    $missing_metadata_post_ids = array_unique($missing_metadata_post_ids);

    // additionally, these posts we have to ignore
    $failed_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);
    $processing_metadata_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME);

    // exclude this post_ids (merge $processing_post_ids and $already_filled_post_ids and $failed_to_fill_post_ids)
    $exclude_this_post_ids = array_merge($pending_metadata_post_ids, $processing_metadata_post_ids, $failed_metadata_post_ids);

    // check if all values are numeric
    foreach ($exclude_this_post_ids as &$this_excluded_post_id) {
        $this_excluded_post_id = absint($this_excluded_post_id);
    }

    // make sure that $exclude_this_post_ids is an array and not empty (otherwise the query will fail)
    if (!$exclude_this_post_ids) {
        $exclude_this_post_ids = array(0);
    }

    $exclude_this_post_ids = array_unique($exclude_this_post_ids);

    $candidate_post_ids = array_values(array_diff($missing_metadata_post_ids, $exclude_this_post_ids));

    if (!$candidate_post_ids) {
        if ($debug) {
            ai4seo_debug_message(345541132, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No candidates after exclusions")));
        }

        return false;
    }

    $new_pending_post_ids = $pending_metadata_post_ids;

    // check bulk generation order
    $bulk_generation_order = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_ORDER);

    switch ($bulk_generation_order) {
        case "oldest":
            $order_by_term_string = "ID ASC";
            break;
        case "newest":
            $order_by_term_string = "ID DESC";
            break;
        case "random":
        default:
            $order_by_term_string = "";
            break;
    }

    // Apply order in PHP (avoid ORDER BY RAND(), keep SQL simple)
    switch ($bulk_generation_order) {
        case "oldest":
            sort($candidate_post_ids, SORT_NUMERIC);
            break;
        case "newest":
            rsort($candidate_post_ids, SORT_NUMERIC);
            break;
        case "random":
        default:
            shuffle($candidate_post_ids);
            break;
    }

    // check if we should only generate metadata for new or existing posts
    $bulk_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
    $bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);

    $database_chunk_size = ai4seo_get_database_chunk_size();

    // go through each enabled automated generation post types and read at least two post ids
    foreach ($enabled_bulk_generation_post_types AS $this_post_type)  {
        if (count($new_pending_post_ids) >= 2) {
            break;
        }

        if ($bulk_generation_new_or_existing_filter != "both" && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric($bulk_generation_new_or_existing_filter_reference_timestamp)) {
            // determine $post_date_term_string, if $automated_generation_new_or_existing_filter is "new", the post has to be newer than $bulk_generation_new_or_existing_filter_reference_timestamp
            // and if $automated_generation_new_or_existing_filter is "existing", the post has to be older than (or exactly) $bulk_generation_new_or_existing_filter_reference_timestamp
            if ($bulk_generation_new_or_existing_filter == "new") {
                $post_date_term_string = "post_date_gmt > '" . ai4seo_gmdate("Y-m-d H:i:s", (int) $bulk_generation_new_or_existing_filter_reference_timestamp) . "'";
            } else {
                $post_date_term_string = "post_date_gmt <= '" . ai4seo_gmdate("Y-m-d H:i:s", (int) $bulk_generation_new_or_existing_filter_reference_timestamp) . "'";
            }
        } else {
            $post_date_term_string = "";
        }

        $post_date_sql = '';
        if ($post_date_term_string) {
            $post_date_sql = ' AND ' . $post_date_term_string;
        }

        $order_by_sql = '';
        if ($order_by_term_string) {
            $order_by_sql = ' ORDER BY ' . $order_by_term_string;
        }

        // chunk candidates and query only current post type, stop once we have 2 IDs total
        $candidate_post_ids_chunks = array_chunk($candidate_post_ids, $database_chunk_size);
        foreach ($candidate_post_ids_chunks as $this_candidate_post_ids_chunk) {
            if (count($new_pending_post_ids) >= 2) {
                break;
            }

            $post_ids_placeholders = implode(', ', array_fill(0, count($this_candidate_post_ids_chunk), '%d'));

            $this_new_pending_post_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID 
                    FROM {$wpdb->posts} 
                    WHERE post_type = %s AND ID IN ( {$post_ids_placeholders} ) AND post_status IN ( %s, %s ) {$post_date_sql} {$order_by_sql} 
                    LIMIT %d",
                    array_merge(
                        array($this_post_type),
                        $this_candidate_post_ids_chunk,
                        array('publish', 'future'),
                        array(2)
                    )
                )
            );

            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321678, 'Database error: ' . $wpdb->last_error, true);
                return false;
            }

            if ($this_new_pending_post_ids) {
                $new_pending_post_ids = array_merge($new_pending_post_ids, $this_new_pending_post_ids);

                if (count($new_pending_post_ids) >= 2) {
                    $new_pending_post_ids = array_slice($new_pending_post_ids, 0, 2);
                    break;
                }
            }
        }
    }

    if (!$new_pending_post_ids && !$pending_metadata_post_ids) {
        // skip here because we don't have any posts or pages
        if ($debug) {
            ai4seo_debug_message(345541131, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No posts found")));
        }

        return false;
    }

    // add the new post ids to the option "ai4seo_processing_metadata_post_ids"
    if ($new_pending_post_ids) {
        ai4seo_add_post_ids_to_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, $new_pending_post_ids);

        if ($debug) {
            ai4seo_debug_message(588880985, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("New pending post(s): " . esc_textarea(implode(", ", $new_pending_post_ids)))));
        }
    }

    return true;
}

// =========================================================================================== \\

/**
 * Function to excavate attachments with missing attributes.
 * Is used by the cronjob "ai4seo_automated_generation_cron_job"
 * @param bool $debug if true, debug information will be printed
 * @return bool
 */
function ai4seo_excavate_attachments_with_missing_attributes(bool $debug = false): bool {
    global $wpdb;
    global $ai4seo_allowed_attachment_mime_types;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(548885475, 'Prevented loop', true);
        return false;
    }

    $supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

    $approximate_cost_per_attachment_post = ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post();

    // check the current credits balance, compare it to $approximate_cost_per_attachment_post and if it's lower, return false
    if (ai4seo_robhub_api()->get_credits_balance() < $approximate_cost_per_attachment_post) {
        if ($debug) {
            ai4seo_debug_message(791110592, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("skipped, because of low Credits balance")));
        }

        // remove all processing and pending ids
        ai4seo_update_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");
        ai4seo_update_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, "");

        return false;
    }

    // is automation disabled, skip
    if (!ai4seo_is_bulk_generation_enabled("attachment")) {
        if ($debug) {
            ai4seo_debug_message(457348107, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No automation enabled")));
        }

        return false;
    }

    // check the number of already planned posts
    $pending_attributes_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    if ($pending_attributes_attachment_post_ids && count($pending_attributes_attachment_post_ids) >= 2) {
        // skip here because we already have two attachment posts that are going to be processed
        // better keep the amount of post ids low if the user suddenly stops the automation
        if ($debug) {
            ai4seo_debug_message(977145156, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Already >= 2 media posts to generate -> skip")));
        }

        return true;
    }

    // only consider this attachment posts with missing post ids
    $missing_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    if (!$missing_attachment_attributes_post_ids) {
        // skip here because we don't have any attachment posts with missing attributes
        if ($debug) {
            ai4seo_debug_message(325744145, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No media posts found")));
        }

        return false;
    }

    $missing_attachment_attributes_post_ids = array_unique($missing_attachment_attributes_post_ids);

    // additionally, exclude these attachment posts
    $processing_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
    $failed_attachment_attributes_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    // exclude this post_ids (merge $processing_post_ids and $already_filled_post_ids and $failed_to_fill_post_ids)
    $exclude_this_attachment_post_ids = array_merge($pending_attributes_attachment_post_ids, $processing_attachment_attributes_post_ids, $failed_attachment_attributes_post_ids);

    // check if all values are numeric
    foreach ($exclude_this_attachment_post_ids as &$this_excluded_attachment_post_id) {
        $this_excluded_attachment_post_id = absint($this_excluded_attachment_post_id);
    }

    // make sure that $exclude_this_post_ids is an array and not empty (otherwise the query will fail)
    if (!$exclude_this_attachment_post_ids) {
        $exclude_this_attachment_post_ids = array(0);
    }

    $exclude_this_attachment_post_ids = array_unique($exclude_this_attachment_post_ids);

    // perform esc_sql on every entry of $ai4seo_supported_attachment_mime_types
    $only_this_mime_types_sql_terms = array();

    foreach ($ai4seo_allowed_attachment_mime_types AS $this_mime_type) {
        $only_this_mime_types_sql_terms[] = esc_sql($this_mime_type);
    }

    // check bulk generation order
    $bulk_generation_order = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_ORDER);

    switch ($bulk_generation_order) {
        case "oldest":
            $order_by_term_string = "ID ASC";
            break;
        case "newest":
            $order_by_term_string = "ID DESC";
            break;
        case "random":
        default:
            $order_by_term_string = "";
            break;
    }

    // check if we should only generate media attributes for new or existing media files
    $bulk_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
    $bulk_generation_new_or_existing_filter_reference_timestamp = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);

    if ($bulk_generation_new_or_existing_filter != "both" && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric($bulk_generation_new_or_existing_filter_reference_timestamp)) {
        // determine $post_date_term_string, if $automated_generation_new_or_existing_filter is "new", the post has to be newer than $bulk_generation_new_or_existing_filter_reference_timestamp
        // and if $automated_generation_new_or_existing_filter is "existing", the post has to be older than (or exactly) $bulk_generation_new_or_existing_filter_reference_timestamp
        if ($bulk_generation_new_or_existing_filter == "new") {
            $post_date_term_string = "post_date_gmt > '" . ai4seo_gmdate("Y-m-d H:i:s", (int) $bulk_generation_new_or_existing_filter_reference_timestamp) . "'";
        } else {
            $post_date_term_string = "post_date_gmt <= '" . ai4seo_gmdate("Y-m-d H:i:s", (int) $bulk_generation_new_or_existing_filter_reference_timestamp) . "'";
        }
    } else {
        $post_date_term_string = "";
    }

    if (count($supported_attachment_post_types) > 1) {
        $escaped_supported_attachment_post_types = array();

        // escape each element
        foreach ($supported_attachment_post_types AS $this_supported_attachment_post_type) {
            $escaped_supported_attachment_post_types[] = esc_sql($this_supported_attachment_post_type);
        }

        // Hard cap post types to 256 entries to avoid oversized IN(...) clauses.
        $escaped_supported_attachment_post_types = array_slice( $escaped_supported_attachment_post_types, 0, 256 );
        $post_type_term = "post_type IN ('" . implode("', '", $escaped_supported_attachment_post_types) . "')";
    } else {
        $post_type_term = "post_type = '" . esc_sql(reset($supported_attachment_post_types)) . "'";
    }

    // remove excluded IDs in PHP first, then chunk only candidates
    $candidate_attachment_post_ids = array_values(array_diff($missing_attachment_attributes_post_ids, $exclude_this_attachment_post_ids));

    if (!$candidate_attachment_post_ids) {
        if ($debug) {
            ai4seo_debug_message(742528302, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No candidates after exclusions")));
        }

        return false;
    }

    // Apply order in PHP (avoid ORDER BY RAND(), keep SQL simple)
    switch ($bulk_generation_order) {
        case "oldest":
            sort($candidate_attachment_post_ids, SORT_NUMERIC);
            break;
        case "newest":
            rsort($candidate_attachment_post_ids, SORT_NUMERIC);
            break;
        case "random":
        default:
            shuffle($candidate_attachment_post_ids);
            break;
    }

    $new_pending_attachment_post_ids = array();

    $database_chunk_size = ai4seo_get_database_chunk_size();

    $candidate_attachment_post_ids_chunks = array_chunk($candidate_attachment_post_ids, $database_chunk_size);

    foreach ($candidate_attachment_post_ids_chunks as $this_candidate_post_ids_chunk) {
        $post_ids_placeholders   = implode(', ', array_fill(0, count($this_candidate_post_ids_chunk), '%d'));
        $mime_types_placeholders = implode(', ', array_fill(0, count($only_this_mime_types_sql_terms), '%s'));

        $post_date_sql = '';

        if ($post_date_term_string) {
            $post_date_sql = ' AND ' . $post_date_term_string;
        }

        $order_by_sql = '';

        if ($order_by_term_string) {
            $order_by_sql = ' ORDER BY ' . $order_by_term_string;
        }

        $chunk_result = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID 
                FROM {$wpdb->posts} 
                WHERE {$post_type_term} AND ID IN ( {$post_ids_placeholders} ) AND post_status IN ( %s, %s, %s ) AND post_mime_type IN ( {$mime_types_placeholders} ) {$post_date_sql} {$order_by_sql} 
                LIMIT %d ",
                array_merge(
                    $this_candidate_post_ids_chunk,
                    array('publish', 'future', 'inherit'),
                    $only_this_mime_types_sql_terms,
                    array(2)
                )
            )
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321679, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }

        if ($chunk_result) {
            $new_pending_attachment_post_ids = array_merge($new_pending_attachment_post_ids, $chunk_result);

            if (count($new_pending_attachment_post_ids) >= 2) {
                $new_pending_attachment_post_ids = array_slice($new_pending_attachment_post_ids, 0, 2);
                break;
            }
        }
    }

    if (!$new_pending_attachment_post_ids && !$pending_attributes_attachment_post_ids) {
        // skip here because we don't have any posts or pages
        if ($debug) {
            ai4seo_debug_message(742528301, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("No new media found")));
        }

        return false;
    }

    // add the new attachment post ids to be processed
    if ($new_pending_attachment_post_ids) {
        ai4seo_add_post_ids_to_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $new_pending_attachment_post_ids);

        if ($debug) {
            ai4seo_debug_message(209429876, esc_html(__FUNCTION__) . " >" . esc_html(ai4seo_stringify("Added pending media: " . (implode(", ", $new_pending_attachment_post_ids)))));
        }
    }

    return true;
}


// =========================================================================================== \\

/**
 * Function to analyze the performance of the plugin like getting the amount of content "AI for SEO" could
 * generate metadata for
 * @param bool $debug if true, debug information will be printed
 * @return bool true on success, false on failure
 */
function ai4seo_analyze_plugin_performance(bool $debug = false): bool {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return false;
    }

    // check if disable heavy db operations parameter is set
    ai4seo_check_for_disable_heavy_db_operations_parameter();
    ai4seo_set_cron_job_status(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, "processing");

    // sync robhub account eventually
    $last_account_sync = ai4seo_robhub_api()->read_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC);

    if ($last_account_sync < time() - ai4seo_robhub_api()::BACKGROUND_ACCOUNT_SYNC_INTERVAL) {
        ai4seo_sync_robhub_account('plugin_analyse', true);
    }

    // perform a full seo posts coverage
    ai4seo_try_start_posts_table_analysis(true, $debug);

    // update the last performance analysis time
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME, time());

    ai4seo_set_cron_job_status(AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME, "finished");
    return true;
}

// =========================================================================================== \\

function ai4seo_check_for_disable_heavy_db_operations_parameter(): void {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(989420626, 'Prevented loop', true);
        return;
    }

    if ( !isset( $_GET['ai4seo_disable_heavy_db_operations'] ) ) {
        return;
    }

    $ai4seo_disable_heavy_db_operations_parameter = sanitize_text_field( wp_unslash( $_GET['ai4seo_disable_heavy_db_operations'] ) );
    $should_disable_heavy_db_operations = ($ai4seo_disable_heavy_db_operations_parameter === ''
        || in_array( strtolower( $ai4seo_disable_heavy_db_operations_parameter ), array( '1', 'true', 'yes', 'on' ), true ));

    if ( !$should_disable_heavy_db_operations ) {
        return;
    }

    ai4seo_update_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS, true );
}

// =========================================================================================== \\

/**
 * Tries to start the posts table analysis
 * @param $restart_if_completed bool if true, the analysis will be restarted even if it was already completed
 * @param $debug bool if true, debug information will be printed
 * @return void
 */
function ai4seo_try_start_posts_table_analysis(bool $restart_if_completed = false, bool $debug = false) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(197197425, 'Prevented loop', true);
        return;
    }

    ai4seo_run_with_ignore_user_abort(
        'ai4seo_run_posts_table_analysis_task',
        array($restart_if_completed, $debug)
    );
}

// =========================================================================================== \\

function ai4seo_run_posts_table_analysis_task(bool $restart_if_completed = false, bool $debug = false) {
    global $ai4seo_did_run_post_table_analysis;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(423645868, 'Prevented loop', true);
        return;
    }

    // allow to override the heavy db operations disable check with a debug parameter
    $debug_posts_table_analysis = sanitize_text_field( wp_unslash( $_GET['ai4seo_debug_posts_table_analysis'] ?? '' ) );
    $debug_posts_table_analysis = ( $debug_posts_table_analysis === 'true' || $debug_posts_table_analysis === '1');

    // if the setting to disable heavy db operations is enabled, and we don't have the debug parameter for posts table analysis enabled, or debug mode generally disabled
    // -> skip heavy db operations
    if (ai4seo_get_setting(AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS) && (!$debug_posts_table_analysis || !$debug)) {
        if ($debug) {
            ai4seo_debug_message(101605330, esc_html(__FUNCTION__) . ' > Heavy database operations disabled.');
        }

        // set state to completed to avoid further attempts
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, "completed", true);
        return;
    }

    /*if (!ai4seo_acquire_semaphore(__FUNCTION__)) {
        // could not acquire semaphore -> another process is in the critical section -> return
        return;
    }*/

    // check the state and decide if we can start or if we have to wait for the other process to finish
    try {
        $posts_table_analysis_state = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false);
        $posts_table_analysis_start_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME, false);
        $do_restart = false;
        $processing_timeout = AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT; // XX seconds
        $usleep_between_runs = AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS; // 0.X seconds
        $total_max_run_time = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME; // X seconds

        // double it when running in ajax
        if (wp_doing_ajax()) {
            $total_max_run_time *= 4;
        }

        // for cron runs -> longer run time and sleep time
        if (wp_doing_cron()) {
            $total_max_run_time *= 5;
            $usleep_between_runs *= 5;
        }

        $max_runs_per_task = $total_max_run_time / ($usleep_between_runs / 1000000); // calculate max runs per task based on sleep time

        // first, check if the state is "in-progress" and if the last start time was longer ago than $timeout -> restart
        if ($posts_table_analysis_state === "processing") {
            if (!$posts_table_analysis_start_time || (time() - $posts_table_analysis_start_time) > $processing_timeout) {
                $do_restart = true;

                if ($debug) {
                    ai4seo_debug_message(604817040, esc_html(__FUNCTION__) . " > Posts table analysis timed out -> restarting");
                }
            } else {
                if ($debug) {
                    ai4seo_debug_message(364907215, esc_html(__FUNCTION__) . " > Posts table analysis already in progress since " . esc_html(ai4seo_gmdate("Y-m-d H:i:s", (int) $posts_table_analysis_start_time)) . " -> stop");
                }

                // still in progress -> return
                return;
            }
        }

        // check if we are competed and $restart_if_completed is true -> restart
        if ($posts_table_analysis_state === "completed") {
            if ($restart_if_completed) {
                $do_restart = true;

                if ($debug) {
                    ai4seo_debug_message(978731049, esc_html(__FUNCTION__) . " > Posts table analysis already completed -> restarting");
                }
            } else {
                if ($debug) {
                    ai4seo_debug_message(405037545, esc_html(__FUNCTION__) . " > Posts table analysis already completed -> stop");
                }

                // already completed, and we don't want to restart -> return
                return;
            }
        }

        // to avoid multiple runs in a short time, we check when the last run was and if it's less than XX seconds ago, we skip
        $run_interval_in_seconds = AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME * 2;
        $last_core_run_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, false);

        if ($last_core_run_time && (time() - $last_core_run_time) < $run_interval_in_seconds) {
            if ($debug) {
                ai4seo_debug_message(811764522, esc_html(__FUNCTION__) . " > Last run happened less than " . esc_html($run_interval_in_seconds) . " seconds ago -> stop");
            }

            return;
        }

        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME, time(), false);

        // if we decided to restart -> do it
        if ($do_restart) {
            ai4seo_reset_posts_table_analysis();
        }

        // set start
        $start_time = time();
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, "processing", false);
        ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME, $start_time, false);
    } finally {
        //ai4seo_release_semaphore(__FUNCTION__);
    }

    // call ai4seo_perform_posts_table_analysis() for max $total_max_run_time seconds
    $previous_posts_table_analysis_last_post_id = -1;
    $run_counter = 0;
    $is_finished = false;

    try {
        while (time() - $start_time < $total_max_run_time && $run_counter < $max_runs_per_task) {
            $run_counter++;

            $posts_table_analysis_last_post_id = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, false);

            if ($debug) {
                ai4seo_debug_message(231305633, esc_html(__FUNCTION__) . " > Run #" . esc_html($run_counter) . " - Last analyzed post ID: " . esc_html($posts_table_analysis_last_post_id));
            }

            // prevent infinite loop when the offset is not updated
            if ($posts_table_analysis_last_post_id === $previous_posts_table_analysis_last_post_id) {
                if ($debug) {
                    ai4seo_debug_message(107971319, esc_html(__FUNCTION__) . " > Posts table analysis last post id not updated -> stopping to prevent infinite loop");
                }
                break;
            }

            $is_finished = ai4seo_perform_posts_table_analysis($posts_table_analysis_last_post_id, $debug);

            if ($is_finished) {
                break;
            }

            $previous_posts_table_analysis_last_post_id = $posts_table_analysis_last_post_id;

            usleep($usleep_between_runs);
        }
    } catch (Throwable $e) {
        ai4seo_debug_message(842653579, $e->getMessage(), true);
    } finally {
        // update state
        if ($is_finished) {
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, "completed", false);

            if ($debug) {
                ai4seo_debug_message(174773382, esc_html(__FUNCTION__) . " > Posts table analysis completed");
            }
        } else {
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, "idle", false);

            if ($debug) {
                ai4seo_debug_message(679211510, esc_html(__FUNCTION__) . " > Posts table analysis paused, not yet completed");
            }
        }

        $ai4seo_did_run_post_table_analysis = true;
    }

    if ($debug) {
        ai4seo_debug_message(472361408, esc_html(__FUNCTION__) . " > Current state: " . esc_html(ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, false)));
    }
}

// =========================================================================================== \\

/**
 * Performs posts table analysis for a certain number of rows
 * @param $posts_table_analysis_last_post_id int the last post id that was analyzed
 * @param $debug bool if true, debug information will be printed
 * @return bool true if the analysis is completed, false otherwise
 */
function ai4seo_perform_posts_table_analysis(int $posts_table_analysis_last_post_id, bool $debug): bool {
    global $wpdb;
    global $ai4seo_allowed_attachment_mime_types;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(381607754, 'Prevented loop', true);
        return true;
    }

    $total_rows_per_run = AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE;

    // if ajax -> double it
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $total_rows_per_run *= 2;
    }

    // Cursor-based pagination query
    $raw_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_type, post_status, post_mime_type, post_date_gmt, post_date, post_modified_gmt, post_modified
        FROM {$wpdb->posts}
        WHERE ID > %d
        ORDER BY ID ASC
        LIMIT %d",
        $posts_table_analysis_last_post_id,
        $total_rows_per_run
    ), ARRAY_A);

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321680, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }

    if (!$raw_posts || count($raw_posts) === 0) {
        if ($debug) {
            ai4seo_debug_message(681121926, esc_html(__FUNCTION__) . " > No more posts to analyze");
        }

        // no more posts to analyze -> finished
        return true;
    }

    $num_raw_posts = count($raw_posts);
    $is_last_chunk = $num_raw_posts < $total_rows_per_run;

    // check if we should only generate data for new or existing posts
    $bulk_generation_new_or_existing_filter = ai4seo_get_setting(AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER);
    $bulk_generation_new_or_existing_filter_reference_timestamp = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME);


    // PRE-FILTER POSTS & SEPARATE ATTACHMENTS

    $supported_post_types = ai4seo_get_supported_post_types();
    $supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();
    $posts = array();
    $attachment_posts = array();

    if ($supported_post_types || $supported_attachment_post_types) {
        foreach ($raw_posts as $this_raw_post) {

            if ($bulk_generation_new_or_existing_filter != "both" && $bulk_generation_new_or_existing_filter_reference_timestamp && is_numeric($bulk_generation_new_or_existing_filter_reference_timestamp)) {
                $posted_date = $this_raw_post['post_date_gmt'] ?: $this_raw_post['post_date'] ?: $this_raw_post['post_modified_gmt'] ?: $this_raw_post['post_modified'];
                $posted_date_timestamp = @strtotime($posted_date);

                if ($posted_date && $posted_date_timestamp) {
                    if ($bulk_generation_new_or_existing_filter == "new") {
                        if ($posted_date_timestamp < $bulk_generation_new_or_existing_filter_reference_timestamp) {
                            continue; // skip existing posts
                        }
                    } else {
                        if ($posted_date_timestamp >= $bulk_generation_new_or_existing_filter_reference_timestamp) {
                            continue; // skip new posts
                        }
                    }
                }
            }

            if ($supported_post_types && in_array($this_raw_post['post_type'], $supported_post_types, true)) {
                // skip if not status publish or future
                if (!in_array($this_raw_post['post_status'], array('publish', 'future'), true)) {
                    continue;
                }

                $this_post_id = (int) $this_raw_post['ID'];

                $posts[$this_post_id] = $this_raw_post;
            } else if ($supported_attachment_post_types && in_array($this_raw_post['post_type'], $supported_attachment_post_types, true)) {
                // skip if not status publish, future or inherit
                if (!in_array($this_raw_post['post_status'], array('publish', 'future', 'inherit'), true)) {
                    continue;
                }

                // check mime type
                if (!in_array($this_raw_post['post_mime_type'], $ai4seo_allowed_attachment_mime_types, true)) {
                    continue;
                }

                $this_attachment_post_id = (int) $this_raw_post['ID'];

                // check availability of the file
                $attachment_path = get_attached_file($this_attachment_post_id);

                if (!$attachment_path) {
                    continue;
                }

                // check if file exists
                if (!file_exists( $attachment_path )) {
                    continue;
                }

                $attachment_posts[$this_attachment_post_id] = $this_raw_post;
            } else {
                // unsupported post type -> skip
                continue;
            }
        }
    }


    // PREPARE

    // get last $raw_posts entry
    $last_raw_post = end($raw_posts);
    $last_processed_post_id = (int) $last_raw_post['ID'];

    unset($raw_posts); // free memory

    $post_ids = array_keys($posts);
    $attachment_posts_ids = array_keys($attachment_posts);

    // prepare the coverage based post ids array
    $new_post_ids_by_option = array();

    foreach (AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS AS $this_option_name) {
        $new_post_ids_by_option[$this_option_name] = array();
    }

    // read generated data post ids
    $generated_data_post_ids = ai4seo_read_generated_data_post_ids_by_post_ids(array_merge($post_ids, $attachment_posts_ids));

    // read AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME (include post IDs for validation)
    $current_generation_status_summary = ai4seo_read_generation_status_summary(false, true);

    // collect post ids per option and post type to reduce summary writes
    $generation_status_post_ids_to_add = array();


    // ANALYSE POSTS

    if ($post_ids) {
        $generate_metadata_for_fully_covered_entries = ai4seo_do_generate_metadata_for_fully_covered_entries();
        $processing_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME);
        $pending_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);
        $failed_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);

        // read the percentage of active metadata by post ids
        $percentage_of_available_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids($post_ids);

        foreach ($percentage_of_available_metadata_by_post_ids as $this_post_id => $this_percentage) {
            $this_post_id = (int) $this_post_id;
            $this_post_type = $posts[$this_post_id]['post_type'] ?? '';
            $this_post_was_generated = in_array($this_post_id, $generated_data_post_ids);

            // check if fully covered
            if ($this_percentage == 100) {
                // remove from fully covered those entries that has not been generated yet
                if ($generate_metadata_for_fully_covered_entries && !$this_post_was_generated) {
                    $this_percentage = 0; // set to 0 to mark as missing
                } else {
                    $new_post_ids_by_option[AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME][] = $this_post_id;
                    $generation_status_post_ids_to_add[AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
                }
            }

            if ($this_percentage < 100) {
                $new_post_ids_by_option[AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME][] = $this_post_id;
                $generation_status_post_ids_to_add[AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
            }

            // check if this post was generated
            if ($this_post_was_generated) {
                $new_post_ids_by_option[AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME][] = $this_post_id;
                $generation_status_post_ids_to_add[AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
            }

            // check if this post is in processing post ids
            if (in_array($this_post_id, $processing_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
            }

            // check if this post is in pending post ids
            if (in_array($this_post_id, $pending_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
            }

            // check if this post is in failed post ids
            if (in_array($this_post_id, $failed_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME][$this_post_type][] = $this_post_id;
            }
        }
    }


    // ANALYZE ATTACHMENT POSTS

    if ($attachment_posts_ids) {
        $generate_attachment_attributes_for_fully_covered_entries = ai4seo_do_generate_attachment_attributes_for_fully_covered_entries();

        // BUILD ATTACHMENT ATTRIBUTES COVERAGE ARRAY
        $attachment_attributes_coverage = ai4seo_read_and_analyse_attachment_attributes_coverage($attachment_posts_ids);
        $num_total_attachment_attributes_fields = ai4seo_get_active_num_attachment_attributes();
        $attachment_attributes_coverage_summary = ai4seo_get_attachment_attributes_coverage_summary($attachment_attributes_coverage);
        $processing_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
        $pending_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
        $failed_attachment_post_ids = ai4seo_get_post_ids_from_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);
        unset($attachment_attributes_coverage);

        // ADD ENTRIES TO THE GENERATION STATUS POST IDS
        foreach ($attachment_attributes_coverage_summary AS $this_post_id => $num_fields_covered) {
            $this_post_was_generated = in_array($this_post_id, $generated_data_post_ids);
            $this_attachment_post_type = 'attachment';
            $is_fully_covered = ($num_fields_covered >= $num_total_attachment_attributes_fields);

            // check if fully covered
            if ($is_fully_covered) {
                // remove from fully covered those entries that has not been generated yet
                if ($generate_attachment_attributes_for_fully_covered_entries && !$this_post_was_generated) {
                    $is_fully_covered = false; // set to 'false' to mark as missing
                } else {
                    $new_post_ids_by_option[AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][] = (int) $this_post_id;

                    $generation_status_post_ids_to_add[AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
                }
            }

            if (!$is_fully_covered) {
                $new_post_ids_by_option[AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][] = (int) $this_post_id;

                $generation_status_post_ids_to_add[AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
            }

            // check if this post was generated
            if ($this_post_was_generated) {
                $new_post_ids_by_option[AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][] = (int) $this_post_id;

                $generation_status_post_ids_to_add[AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
            }

            // check if this post is in processing attachment post ids
            if (in_array($this_post_id, $processing_attachment_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
            }

            // check if this post is in pending attachment post ids
            if (in_array($this_post_id, $pending_attachment_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
            }

            // check if this post is in failed attachment post ids
            if (in_array($this_post_id, $failed_attachment_post_ids)) {
                $generation_status_post_ids_to_add[AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME][$this_attachment_post_type][] = (int) $this_post_id;
            }
        }
    }

    if ($generation_status_post_ids_to_add) {
        foreach ($generation_status_post_ids_to_add as $option_name => $post_type_entries) {
            if (!$post_type_entries || !is_array($post_type_entries)) {
                continue;
            }

            foreach ($post_type_entries as $post_type => $post_ids) {
                ai4seo_add_post_ids_to_generation_status_summary(
                    $current_generation_status_summary,
                    $option_name,
                    $post_type,
                    $post_ids
                );
            }
        }
    }


    // SAVE NEW POST IDS TO OPTIONS
    
    foreach ($new_post_ids_by_option AS $this_option_name => $this_post_ids) {
        if (!$this_post_ids) {
            continue;
        }

        if ($debug) {
            ai4seo_debug_message(732600128, esc_html(__FUNCTION__) . " > Adding to option " . $this_option_name . ": " . count($this_post_ids) . " post ids");
        }

        ai4seo_add_post_ids_to_option($this_option_name, $this_post_ids);
    }

    if ($debug) {
        ai4seo_debug_message(417529305, esc_html(__FUNCTION__) . " > Current generation status summary: " . esc_html(ai4seo_stringify($current_generation_status_summary)));
    }

    ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $current_generation_status_summary );


    // KEEP TRACK OF LAST POST ID

    if ($debug) {
        ai4seo_debug_message(408476980, esc_html(__FUNCTION__) . " > Last processed post ID: " . $last_processed_post_id);
    }

    // update the last processed post id
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, $last_processed_post_id, false);

    // was last chunk? -> finished = true, otherwise false
    return $is_last_chunk;
}

// =========================================================================================== \\

/**
 * Read the generation status summary option.
 *
 * @param bool $totals_only When true, return legacy totals-only format.
 * @param bool $use_direct_database_call When true, read directly from the database, bypassing any caching layers.
 * @return array Generation status summary.
 */
function ai4seo_read_generation_status_summary(bool $totals_only = true, bool $use_direct_database_call = true): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(451439298, 'Prevented loop', true);
        return array();
    }

    // read AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME
    $generation_status_summary = ai4seo_get_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, '{}', $use_direct_database_call );

    if (!is_array($generation_status_summary)) {
        $generation_status_summary = maybe_unserialize($generation_status_summary);
    }

    if (!is_array($generation_status_summary) && ai4seo_is_json($generation_status_summary)) {
        $generation_status_summary = json_decode($generation_status_summary, true);
    }

    if (!is_array($generation_status_summary)) {
        $generation_status_summary = array();
    }

    ai4seo_deep_sanitize($generation_status_summary, "absint");

    if (!$totals_only) {
        return ai4seo_normalize_generation_status_summary_storage($generation_status_summary);
    }

    return ai4seo_get_generation_status_summary_totals($generation_status_summary);
}

// =========================================================================================== \\

/**
 * Normalize stored generation status summary to include total and post_ids entries.
 *
 * @param array $generation_status_summary Raw summary from storage.
 * @return array Normalized summary with totals and post IDs.
 */
function ai4seo_normalize_generation_status_summary_storage(array $generation_status_summary): array {
    $normalized_summary = array();

    foreach ($generation_status_summary as $option_name => $post_type_entries) {
        if (!is_array($post_type_entries)) {
            continue;
        }

        foreach ($post_type_entries as $post_type => $summary_entry) {
            $post_ids = array();

            if (is_array($summary_entry) && isset($summary_entry['post_ids']) && is_array($summary_entry['post_ids'])) {
                $post_ids = array_map('absint', $summary_entry['post_ids']);
            }

            $post_ids = array_values(array_unique(array_filter($post_ids)));

            $normalized_summary[$option_name][$post_type] = array(
                'total' => count($post_ids),
                'post_ids' => $post_ids,
            );
        }
    }

    return $normalized_summary;
}

// =========================================================================================== \\

/**
 * Return totals-only summary for backward compatibility.
 *
 * @param array $generation_status_summary Raw or normalized summary data.
 * @return array Totals by option and post type.
 */
function ai4seo_get_generation_status_summary_totals(array $generation_status_summary): array {
    $totals_summary = array();

    foreach ($generation_status_summary as $option_name => $post_type_entries) {
        if (!is_array($post_type_entries)) {
            continue;
        }

        foreach ($post_type_entries as $post_type => $summary_entry) {
            if (is_array($summary_entry)) {
                if (array_key_exists('total', $summary_entry)) {
                    $totals_summary[$option_name][$post_type] = (int) $summary_entry['total'];
                    continue;
                }

                if (isset($summary_entry['post_ids']) && is_array($summary_entry['post_ids'])) {
                    $totals_summary[$option_name][$post_type] = count(array_unique(array_map('absint', $summary_entry['post_ids'])));
                    continue;
                }
            }

            $totals_summary[$option_name][$post_type] = (int) $summary_entry;
        }
    }

    return $totals_summary;
}

// =========================================================================================== \\

/**
 * Append post IDs to the generation status summary and keep totals in sync.
 *
 * @param array $generation_status_summary Summary array passed by reference.
 * @param string $option_name Option name to update.
 * @param string $post_type Post type key.
 * @param array $post_ids Post IDs to append.
 * @return void
 */
function ai4seo_add_post_ids_to_generation_status_summary(array &$generation_status_summary, string $option_name, string $post_type, array $post_ids): void {
    if (!isset($generation_status_summary[$option_name]) || !is_array($generation_status_summary[$option_name])) {
        $generation_status_summary[$option_name] = array();
    }

    if (!isset($generation_status_summary[$option_name][$post_type]) || !is_array($generation_status_summary[$option_name][$post_type])) {
        $generation_status_summary[$option_name][$post_type] = array(
            'total' => 0,
            'post_ids' => array(),
        );
    }

    if (!isset($generation_status_summary[$option_name][$post_type]['post_ids']) || !is_array($generation_status_summary[$option_name][$post_type]['post_ids'])) {
        $generation_status_summary[$option_name][$post_type]['post_ids'] = array();
    }

    $post_ids = array_filter(array_map('absint', $post_ids));
    $generation_status_summary[$option_name][$post_type]['post_ids'] = array_merge(
        $generation_status_summary[$option_name][$post_type]['post_ids'],
        $post_ids
    );
    $generation_status_summary[$option_name][$post_type]['post_ids'] = array_values(
        array_unique(array_map('absint', $generation_status_summary[$option_name][$post_type]['post_ids']))
    );
    $generation_status_summary[$option_name][$post_type]['total'] = count(
        $generation_status_summary[$option_name][$post_type]['post_ids']
    );
}

// =========================================================================================== \\

/**
 * Retrieve post IDs that have generated data stored in postmeta.
 *
 * @param array $post_ids List of post IDs to check.
 * @return array Sanitized list of post IDs with generated data.
 */
function ai4seo_read_generated_data_post_ids_by_post_ids(array $post_ids ): array {
    global $wpdb;

    if (empty( $post_ids )) {
        return array();
    }

    // Sanitize and filter invalid entries.
    $post_ids = array_filter( array_map( 'absint', $post_ids ) );

    if ( empty( $post_ids ) ) {
        return array();
    }

    $generated_data_post_ids = array();
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        // Build dynamic placeholders.
        $placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        // Prepare query safely.
        $this_generated_data_post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s AND post_id IN ($placeholders)",
                array_merge(
                    array( AI4SEO_POST_META_GENERATED_DATA_META_KEY ),
                    $this_post_ids_chunk
                )
            )
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321681, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if ( ! $this_generated_data_post_ids ) {
            continue;
        }

        $generated_data_post_ids = array_merge( $generated_data_post_ids, $this_generated_data_post_ids );
    }

    // Sanitize result set.
    return array_map( 'absint', (array) $generated_data_post_ids );
}

// =========================================================================================== \\

function ai4seo_reset_posts_table_analysis() {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(402005224, 'Prevented loop', true);
        return;
    }

    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID, 0, false);
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE, "idle", false);

    // reset seo coverage base post ids options
    foreach (AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS AS $this_option_name) {
        ai4seo_update_option($this_option_name, array());
    }

    // reset generation status summary
    $generation_status_summary = array();

    foreach (AI4SEO_ALL_POST_ID_OPTIONS AS $this_option_name) {
        $generation_status_summary[$this_option_name] = array();
    }

    ai4seo_update_option( AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME, $generation_status_summary );
}


// endregion
// ___________________________________________________________________________________________ \\
// region META DATA ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to get the summary (amount of posts) of a specific options (generation status)
 * @param $option_name string the name of the option (generation status)
 * @return array the generation status summary entry or false if not found
 */
function ai4seo_get_generation_status_summary_entry(string $option_name): array {
    $generation_status_summary = ai4seo_read_generation_status_summary(true, false);

    if (!isset($generation_status_summary[$option_name])) {
        return array();
    }

    return $generation_status_summary[$option_name];
}

// =========================================================================================== \\

/**
 * Function to get all missing posts by post type by using the generation status summary-cache
 * @return array the missing posts by post type
 */
function ai4seo_get_num_missing_posts_by_post_type(): array {
    $num_missing_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME);
    $num_missing_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_missing_metadata_by_post_type, $num_missing_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get all fully covered posts by post type by using the generation status summary-cache
 * @return array the fully covered posts by post type
 */
function ai4seo_get_num_fully_covered_posts_by_post_type(): array {
    $num_fully_covered_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME);
    $num_fully_covered_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_fully_covered_metadata_by_post_type, $num_fully_covered_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get all generated posts by post type by using the generation status summary-cache
 * @return array the generated posts by post type
 */
function ai4seo_get_num_generated_posts_by_post_type(): array {
    $num_generated_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME);
    $num_generated_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_generated_metadata_by_post_type, $num_generated_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get all fully covered OR generated posts by post type by using the generation status summary-cache, depending on, if we care fully covered entries
 * @return array the fully covered or generated posts by post type
 */
function ai4seo_get_num_finished_posts_by_post_type(): array {
    $num_finished_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME);
    $num_finished_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_finished_metadata_by_post_type, $num_finished_attachment_attributes);
}


// =========================================================================================== \\

/**
 * Function to get all failed posts by post type by using the generation status summary-cache
 * @return array the failed posts by post type
 */
function ai4seo_get_num_failed_posts_by_post_type(): array {
    $num_failed_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);
    $num_failed_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_failed_metadata_by_post_type, $num_failed_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get all pending posts by post type by using the generation status summary-cache
 * @return array the pending posts by post type
 */
function ai4seo_get_num_pending_posts_by_post_type(): array {
    $num_pending_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME);
    $num_pending_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_pending_metadata_by_post_type, $num_pending_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get all processing posts by post type by using the generation status summary-cache
 * @return array the processing posts by post type
 */
function ai4seo_get_num_processing_posts_by_post_type(): array {
    $num_processing_metadata_by_post_type = ai4seo_get_generation_status_summary_entry(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME);
    $num_processing_attachment_attributes = ai4seo_get_generation_status_summary_entry(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    return array_merge($num_processing_metadata_by_post_type, $num_processing_attachment_attributes);
}

// =========================================================================================== \\

/**
 * Function to get the summary (amount of posts) of a specific options and post type
 * @param $option_name string the name of the option (generation status)
 * @param $post_type string the post type
 * @return int the amount of posts for this specific generation status and post type
 */
function ai4seo_get_num_generation_status_and_post_types_posts(string $option_name, string $post_type): int {
    $generation_status_summary = ai4seo_read_generation_status_summary(true, false);

    if (!$generation_status_summary) {
        return 0;
    }

    if (!isset($generation_status_summary[$option_name])) {
        return 0;
    }

    if (!isset($generation_status_summary[$option_name][$post_type])) {
        return 0;
    }

    return (int) $generation_status_summary[$option_name][$post_type];
}

// =========================================================================================== \\

/**
 * Function to return a post meta key by the given post id and the name of the metadata field from the
 * $ai4seo_metadata_details array
 * @param $post_id int the post id
 * @param $metadata_identifier string the metadata identifier
 * @return string the post meta key
 */
function ai4seo_generate_postmeta_key_by_metadata_identifier($post_id, $metadata_identifier): string {
    return "_ai4seo_" . $post_id . "_" . $metadata_identifier;
}

// =========================================================================================== \\

/**
 * Function to get the metadata identifier out of a (our plugin's) postmeta key
 * @param $metadata_postmeta_key string the postmeta key
 * @return string the metadata identifier or an empty string if not found
 */
function ai4seo_get_metadata_identifier_by_postmeta_key(string $metadata_postmeta_key) {
    $matches = array();
    preg_match("/^_ai4seo_([0-9]+)_(.*)$/", $metadata_postmeta_key, $matches);

    if (empty($matches[2])) {
        return false;
    }

    return $matches[2];
}

// =========================================================================================== \\

/**
 * Function to read the post meta from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array
 */
function ai4seo_read_our_plugins_metadata_by_post_ids( array $post_ids ): array {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(610785731, 'Prevented loop', true);
        return array();
    }

    $active_meta_tags = ai4seo_get_active_meta_tags();

    if (!$active_meta_tags) {
        return array();
    }

    // make sure all entries are numeric
    foreach ( $post_ids as $post_id ) {
        if ( ! is_numeric( $post_id ) ) {
            return array();
        }
    }

    // bail early on empty
    if ( empty( $post_ids ) ) {
        return array();
    }

    // sanitize IDs
    $post_ids = array_map( 'absint', $post_ids );

    // table and pattern
    $regexp         = '^_ai4seo_[0-9]+_.*$';

    $reordered_results = array();

    // split into chunks
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        $this_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE meta_key REGEXP %s AND post_id IN ({$this_post_ids_placeholders})",
                array_merge( array( $regexp ), $this_post_ids_chunk )
            ),
            ARRAY_A
        );

        // on error
        if ($wpdb->last_error) {
            ai4seo_debug_message(984321656, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if ( ! $this_rows ) {
            continue;
        }

        foreach ( $this_rows as $this_row ) {
            $this_post_id            = absint( $this_row['post_id'] );
            $this_metadata_identifier = ai4seo_get_metadata_identifier_by_postmeta_key( $this_row['meta_key'] );

            if ( ! $this_metadata_identifier ) {
                continue;
            }

            if (!in_array( $this_metadata_identifier, $active_meta_tags, true )) {
                continue;
            }

            $reordered_results[ $this_post_id ][ $this_metadata_identifier ] = strval( $this_row['meta_value'] );
        }
    }

    return $reordered_results;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for a specific third party plugin from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_third_party_seo_plugin_metadata_by_post_ids($third_party_plugin_name, array $post_ids): array {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(915529372, 'Prevented loop', true);
        return array();
    }

    // cast all post ids to int with absint and filter out non-numeric entries
    $post_ids = array_filter(array_map('absint', $post_ids));

    // Make sure that all parameters are not empty
    if (empty($post_ids)) {
        return array();
    }

    // workaround for Slim SEO
    if ($third_party_plugin_name == AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO) {
        return ai4seo_read_slim_seo_metadata_by_post_ids($post_ids);
    }

    // workaround for Blog2Social
    if ($third_party_plugin_name == AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL) {
        return ai4seo_read_blog2social_metadata_by_post_ids($post_ids);
    }

    // workaround for Squirrly SEO
    if ($third_party_plugin_name == AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO) {
        return ai4seo_read_squirrly_seo_metadata_by_post_ids($post_ids);
    }

    // workaround for All in One SEO
    if ($third_party_plugin_name == AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO) {
        return ai4seo_read_all_in_one_seo_metadata_by_post_ids($post_ids);
    }

    $third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

    // Make sure that all parameters are of the correct type
    $metadata_postmeta_keys = $third_party_seo_plugin_details[$third_party_plugin_name]["generation-field-postmeta-keys"] ?? array();

    if (!$metadata_postmeta_keys) {
        return array();
    }

    $metadata_postmeta_keys = ai4seo_deep_sanitize($metadata_postmeta_keys);
    $metadata_postmeta_keys_placeholders = implode( ',', array_fill( 0, count( $metadata_postmeta_keys ), '%s' ) );

    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );


    // reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
    // also skip entries with empty meta_value
    $third_party_seo_plugins_metadata = array();

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        // read directly from database by searching for entries in the postmeta table
        $query_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE meta_key IN ({$metadata_postmeta_keys_placeholders}) AND post_id IN ({$this_post_ids_placeholders})",
                array_merge( $metadata_postmeta_keys, $this_post_ids_chunk )
            ),
            ARRAY_A
        );

        // on error
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321657, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if (!$query_results) {
            continue;
        }

        foreach ($query_results as $query_result) {
            $this_post_id = $query_result["post_id"];

            // find metadata identifier
            $this_metadata_identifier = array_search($query_result["meta_key"], $metadata_postmeta_keys);

            if (!$this_metadata_identifier) {
                continue;
            }

            $third_party_seo_plugins_metadata[$this_post_id][$this_metadata_identifier] = strval($query_result["meta_value"]);
        }
    }

    return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the Slim SEO plugin from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_slim_seo_metadata_by_post_ids(array $post_ids): array {
    // check postmeta "slim_seo". It's serialized with keys "title" and "description", nothing else
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
    );

    // read postmeta entries
    global $wpdb;

    // make sure all post ids are absolute integers
    $post_ids = array_map('absint', $post_ids);

    // reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
    // also skip entries with empty meta_value
    $third_party_plugins_metadata = array();

    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        $this_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders})",
                array_merge( array( 'slim_seo' ), $this_post_ids_chunk )
            ),
            ARRAY_A
        );

        // on error
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321658, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if (!$this_rows) {
            continue;
        }

        foreach ($this_rows as $this_row) {
            $this_post_id = (int) $this_row["post_id"];
            $this_metadata = maybe_unserialize($this_row["meta_value"]);

            if (!$this_metadata) {
                continue;
            }

            foreach ($metadata_identifier_mapping as $this_metadata_identifier => $this_third_party_plugin_key) {
                $third_party_plugins_metadata[$this_post_id][$this_metadata_identifier] = $this_metadata[$this_third_party_plugin_key] ?? "";
            }
        }
    }

    return $third_party_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the Blog2Social plugin from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_blog2social_metadata_by_post_ids(array $post_ids): array {
    // check postmeta "_b2s_post_meta". It's serialized with keys "og_title", "og_desc", "card_title" and "card_desc"
    $metadata_identifier_mapping = array(
        "facebook-title" => "og_title",
        "facebook-description" => "og_desc",
        "twitter-title" => "card_title",
        "twitter-description" => "card_desc",
    );

    // read postmeta entries
    global $wpdb;

    // reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
    // also skip entries with empty meta_value
    $third_party_plugins_metadata = array();

    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        $query_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders})",
                array_merge( array( '_b2s_post_meta' ), $this_post_ids_chunk )
            ),
            ARRAY_A
        );

        // on error
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321659, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if (!$query_results) {
            continue;
        }

        foreach ($query_results as $query_result) {
            $this_post_id = (int) $query_result["post_id"];
            $this_metadata = maybe_unserialize($query_result["meta_value"]);

            if (!$this_metadata) {
                continue;
            }

            foreach ($metadata_identifier_mapping as $this_metadata_identifier => $this_third_party_plugin_key) {
                $third_party_plugins_metadata[$this_post_id][$this_metadata_identifier] = $this_metadata[$this_third_party_plugin_key] ?? "";
            }
        }
    }

    return $third_party_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the Squirrly SEO plugin from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_squirrly_seo_metadata_by_post_ids(array $post_ids): array {
    // check table "wp_qss" -> column "seo". It's serialized with keys "title", "description", "og_title", "og_description", "tw_title", "tw_description"
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
        "facebook-title" => "og_title",
        "facebook-description" => "og_description",
        "twitter-title" => "tw_title",
        "twitter-description" => "tw_description",
    );

    // read column "seo" in table "wp_qss"
    global $wpdb;

    // Initialize the values array
    $all_squirrly_values = array();

    // Ensure post IDs are properly escaped and form the pattern for LIKE queries
    $patterns = array_map(function($post_id) {
        $post_id = intval($post_id);
        return '%s:2:"ID";i:' . esc_sql($post_id) . ';%';
    }, $post_ids);

    // Chunk pattern values to avoid oversized OR ... LIKE clauses.
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $pattern_chunks = array_chunk( $patterns, $database_chunk_size );

    foreach ( $pattern_chunks as $this_pattern_chunk ) {
        // Implode all patterns to use them in one SQL query chunk with multiple LIKE clauses.
        $like_clauses = implode(" OR post LIKE ", array_fill(0, count($this_pattern_chunk), '%s'));

        // Prepare the query to get SEO data for this chunk.
        $query = "
            SELECT post, seo
            FROM {$wpdb->prefix}qss
            WHERE post LIKE " . $like_clauses;

        // Execute the query
        $results = $wpdb->get_results($wpdb->prepare($query, ...$this_pattern_chunk), OBJECT);

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321682, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if ( ! $results ) {
            continue;
        }

        // Loop through the results and map them to the post IDs
        foreach ($results as $result) {
            $post_id = false;

            // Check if the post data contains a serialized "ID" field
            if (preg_match('/s:2:"ID";i:(\d+);/', $result->post, $matches)) {
                $post_id = intval($matches[1]);
            }

            if ($post_id) {
                // Deserialize the SEO value
                $this_posts_current_squirrly_values = maybe_unserialize($result->seo);
                if (is_string($this_posts_current_squirrly_values)) {
                    $this_posts_current_squirrly_values = unserialize($this_posts_current_squirrly_values);
                }

                // Store the result for the post ID
                if (is_array($this_posts_current_squirrly_values) && !empty($this_posts_current_squirrly_values)) {
                    $all_squirrly_values[$post_id] = $this_posts_current_squirrly_values;
                } else {
                    $all_squirrly_values[$post_id] = array();
                }
            }
        }
    }

    // reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
    // also skip entries with empty meta_value
    $third_party_seo_plugins_metadata = array();

    foreach ($all_squirrly_values as $post_id => $this_metadata) {
        foreach ($metadata_identifier_mapping as $this_metadata_identifier => $this_squirrly_seo_key) {
            $third_party_seo_plugins_metadata[$post_id][$this_metadata_identifier] = $this_metadata[$this_squirrly_seo_key] ?? "";
        }
    }

    return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Function to read the post's metadata for the All in One SEO plugin from specific posts by the given post ids
 * @param $post_ids array of post ids (all int)
 * @return array the metadata by post-ids, using metadata-identifier keys
 */
function ai4seo_read_all_in_one_seo_metadata_by_post_ids(array $post_ids): array {
    // check table "wp_aioseo_posts" for the post id. Columns are "title", "description", "og_title", "og_description", "twitter_title", "twitter_description"
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
        "facebook-title" => "og_title",
        "facebook-description" => "og_description",
        "twitter-title" => "twitter_title",
        "twitter-description" => "twitter_description",
    );

    $post_ids = ai4seo_deep_sanitize($post_ids, "absint");

    // read entries
    global $wpdb;

    // reorder results, to make post_id the 2d key, then the meta_keys the 1d key and meta_value the value
    // also skip entries with empty meta_value
    $third_party_seo_plugins_metadata = array();

    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        $this_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aioseo_posts WHERE post_id IN ({$this_post_ids_placeholders})",
                $this_post_ids_chunk
            ),
            ARRAY_A
        );

        // on error
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321660, 'Database error: ' . $wpdb->last_error, true);
            return array();
        }

        if ( empty( $this_rows ) ) {
            continue;
        }

        foreach ($this_rows as $this_row) {
            $this_post_id = (int) $this_row["post_id"];

            foreach ($metadata_identifier_mapping as $this_metadata_identifier => $this_aioseo_key) {
                $third_party_seo_plugins_metadata[$this_post_id][$this_metadata_identifier] = $this_row[$this_aioseo_key] ?? "";
            }
        }
    }

    return $third_party_seo_plugins_metadata;
}

// =========================================================================================== \\

/**
 * Returns the number of metadata fields
 * @return int the number of metadata fields
 */
function ai4seo_get_num_metadata_fields(): int {
    return defined('AI4SEO_METADATA_DETAILS') ? count(AI4SEO_METADATA_DETAILS) : 0;
}

// =========================================================================================== \\

function ai4seo_read_available_metadata(int $post_id, bool $consider_third_party_seo_plugin_metadata = true): array {
    $available_metadata_by_post_ids = ai4seo_read_available_metadata_by_post_ids(array($post_id), $consider_third_party_seo_plugin_metadata);

    if (!isset($available_metadata_by_post_ids[$post_id])) {
        return array();
    }

    return $available_metadata_by_post_ids[$post_id];
}

// =========================================================================================== \\

/**
 * Function to read all the available metadata, regardless of the source, for a specific post by the given post id
 * @param $post_ids array of post ids
 * @param $consider_third_party_seo_plugin_metadata bool if true, the own plugin's metadata will be preferred
 * @return array the post meta coverage by post ids
 */
function ai4seo_read_available_metadata_by_post_ids(array $post_ids, bool $consider_third_party_seo_plugin_metadata = true): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(615102829, 'Prevented loop', true);
        return array();
    }

    // make sure post_ids is not empty
    if (empty($post_ids)) {
        return array();
    }

    if (!defined('AI4SEO_METADATA_DETAILS')) {
        return array();
    }

    // make sure all entries of post_ids are numeric
    foreach ($post_ids as $key => $post_id) {
        if (!is_numeric($post_id)) {
            ai4seo_debug_message(964175850, 'post_id is not numeric: ' . ai4seo_stringify($post_id), true);
            return array();
        }
    }

    $available_metadata = array();

    // 1. read our own plugin's metadata
    $our_plugins_metadata_by_post_ids = ai4seo_read_our_plugins_metadata_by_post_ids($post_ids);

    foreach ($post_ids AS $this_key => $this_post_id) {
        $this_posts_got_missing_metadata = false;

        foreach (AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details) {
            $available_metadata[$this_post_id][$this_metadata_identifier] = $our_plugins_metadata_by_post_ids[$this_post_id][$this_metadata_identifier] ?? "";

            // still empty -> mark as missing
            if (empty($available_metadata[$this_post_id][$this_metadata_identifier])) {
                $this_posts_got_missing_metadata = true;
            }
        }

        // if we have every metadata field filled, remove the post id from the array
        if (!$this_posts_got_missing_metadata) {
            unset($post_ids[$this_key]);
        }
    }

    // should we consider third party seo plugins?
    if (!$consider_third_party_seo_plugin_metadata) {
        return $available_metadata;
    }

    // all posts are filled with our own metadata? return the metadata here
    if (count($post_ids) == 0) {
        return $available_metadata;
    }

    // if not, we...

    // 2. check third party seo plugins
    $active_third_party_seo_plugin_details = ai4seo_get_active_third_party_seo_plugin_details();

    foreach ($active_third_party_seo_plugin_details AS $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details) {
        $this_third_plugins_plugins_metadata_by_post_ids = ai4seo_read_third_party_seo_plugin_metadata_by_post_ids($this_third_party_seo_plugin_identifier, $post_ids);

        if (!$this_third_plugins_plugins_metadata_by_post_ids) {
            continue;
        }

        foreach ($post_ids AS $this_key => $this_post_id) {
            $this_posts_got_missing_metadata = false;

            foreach (AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details) {
                // skip if we already have the meta value from our own plugin (or any other third party plugin)
                if ($available_metadata[$this_post_id][$this_metadata_identifier]) {
                    continue;
                }

                $available_metadata[$this_post_id][$this_metadata_identifier] = $this_third_plugins_plugins_metadata_by_post_ids[$this_post_id][$this_metadata_identifier] ?? "";

                // still empty -> mark as missing
                if (empty($available_metadata[$this_post_id][$this_metadata_identifier])) {
                    $this_posts_got_missing_metadata = true;
                }
            }

            // if we have every metadata field filled, remove the post id from the array
            if (!$this_posts_got_missing_metadata) {
                unset($post_ids[$this_key]);
            }
        }

        // all posts are filled with our own metadata? return the metadata here
        if (count($post_ids) == 0) {
            return $available_metadata;
        }
    }

    return $available_metadata;
}

// =========================================================================================== \\

/**
 * Function to return the amount of active metadata per post id
 * @param $post_ids array of post ids
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_num_available_metadata_by_post_ids(array $post_ids): array {
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 99999)) {
        ai4seo_debug_message(561144878, 'Prevented loop', true);
        return array();
    }

    if (!defined('AI4SEO_METADATA_DETAILS')) {
        return array();
    }

    $active_meta_tags = ai4seo_get_active_meta_tags();
    $focus_keyphrase_behavior = ai4seo_get_setting(AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA);
    $overwrite_metadata = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA);

    $available_metadata = ai4seo_read_available_metadata_by_post_ids($post_ids);

    if (!$available_metadata) {
        return array();
    }

    // generate a summary of the post meta coverage array
    $num_available_metadata_by_post_ids = array();

    foreach ($available_metadata as $post_id => $this_metadata_entry) {
        $num_available_metadata_by_post_ids[$post_id] = 0;

        foreach (AI4SEO_METADATA_DETAILS AS $this_metadata_identifier => $this_metadata_details) {
            if (!in_array($this_metadata_identifier, $active_meta_tags, true)) {
                continue;
            }

            if (isset($this_metadata_entry[$this_metadata_identifier]) && $this_metadata_entry[$this_metadata_identifier]) {
                $num_available_metadata_by_post_ids[$post_id]++;
            }
        }

        // workaround -> if we skip the focus keyphrase, but meta title and meta description are set, count it as available metadata
        if ((!isset($this_metadata_entry['focus-keyphrase']) || !$this_metadata_entry['focus-keyphrase'])
            && in_array('focus-keyphrase', $active_meta_tags, true)
            && isset($this_metadata_entry['meta-title']) && $this_metadata_entry['meta-title']
            && isset($this_metadata_entry['meta-description']) && $this_metadata_entry['meta-description']
        ) {
            if ($focus_keyphrase_behavior == AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP) {
                $num_available_metadata_by_post_ids[$post_id]++;
            }

            if ($focus_keyphrase_behavior == AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE
                && !in_array('meta-title', $overwrite_metadata, true)
                && !in_array('meta-description', $overwrite_metadata, true)) {
                $num_available_metadata_by_post_ids[$post_id]++;
            }
        }
    }

    return $num_available_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Function to return the percentage of active metadata per post id
 * @param $post_ids array of post ids
 * @param $round_precision int the precision to round the percentage to
 * @return array the amount of active metadata by post ids
 */
function ai4seo_read_percentage_of_available_metadata_by_post_ids(array $post_ids, int $round_precision = 0): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(343030419, 'Prevented loop', true);
        return array();
    }

    $active_meta_tags = ai4seo_get_active_meta_tags();

    // no active meta tags -> return 100% for all posts if no active meta tags are defined
    if (!$active_meta_tags || count($active_meta_tags) === 0) {
        $percentage_of_active_metadata_by_post_ids = array();

        foreach ($post_ids as $this_post_id) {
            $percentage_of_active_metadata_by_post_ids[$this_post_id] = 100;
        }

        return $percentage_of_active_metadata_by_post_ids;
    }

    // first read how many metadata values are available per post id,
    // then compare it with the total amount of active meta tags
    $num_available_metadata_by_post_ids = ai4seo_read_num_available_metadata_by_post_ids($post_ids);

    $num_active_meta_tags = count($active_meta_tags);

    $percentage_of_active_metadata_by_post_ids = array();

    foreach ($num_available_metadata_by_post_ids as $this_post_id => $this_num_active_metadata) {
        $percentage_of_active_metadata_by_post_ids[$this_post_id] = round(($this_num_active_metadata / $num_active_meta_tags) * 100, $round_precision);
        $percentage_of_active_metadata_by_post_ids[$this_post_id] = min(100, max(0, $percentage_of_active_metadata_by_post_ids[$this_post_id]));
    }

    return $percentage_of_active_metadata_by_post_ids;
}

// =========================================================================================== \\

/**
 * Refreshes the metadata coverage for the given post by putting the post id into the corresponding option
 * @param $post_id int The post id to refresh the metadata coverage for
 * @param null $post WP_Post|null The post object to refresh the metadata coverage for
 * @return void
 */
function ai4seo_refresh_one_posts_metadata_coverage_status(int $post_id, $post = null) {
    if (!is_numeric($post_id)) {
        return;
    }

    // remove post id if it's not a valid post
    if (!ai4seo_is_post_a_valid_content_post($post_id, $post)) {
        ai4seo_remove_post_ids_from_all_options($post_id);
        return;
    }

    // consider which option to put the post id into
    if (ai4seo_read_is_posts_metadata_fully_covered($post_id)) {
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_id);

        // check if the post has generated data
        if (ai4seo_post_has_generated_data($post_id)) {
            ai4seo_add_post_ids_to_option(AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_id);
        }
    } else {
        ai4seo_add_post_ids_to_option(AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME, $post_id);
    }
}

// =========================================================================================== \\

/**
 * Function to check if this post is a valid content post to be considered by our plugin
 * @param int $post_id The post id to check
 * @param $post WP_Post|null
 * @return bool Whether the post is a valid content post
 */
function ai4seo_is_post_a_valid_content_post(int $post_id, WP_Post $post = null): bool {
    if (!is_numeric($post_id)) {
        return false;
    }

    // read post
    if ($post === null) {
        $post = get_post($post_id);
    }

    // check if the post could be read
    if (!$post || is_wp_error($post) || !isset($post->post_type)) {
        return false;
    }

    // supported post types
    $supported_post_types = ai4seo_get_supported_post_types();

    // check if the post is supported
    if (!in_array($post->post_type, $supported_post_types)) {
        return false;
    }

    // check post status
    if (!in_array($post->post_status, array("publish", "future", "private", "pending"))) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Checks if the metadata for a given post is fully covered
 * @param $post_id int The post id to check the metadata coverage for
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_read_is_posts_metadata_fully_covered(int $post_id): bool {
    $percentage_of_active_metadata_by_post_ids = ai4seo_read_percentage_of_available_metadata_by_post_ids(array($post_id));

    return (($percentage_of_active_metadata_by_post_ids[$post_id] ?? 0) == 100);
}

// =========================================================================================== \\

/**
 * Removes all post ids for all or a specific post type and generation status. It's recommended to run
 * @param string $post_type The post type to remove the post ids for
 * @param string $generation_status_option_name The generation status option name to remove the post ids for
 * @return void
 */
function ai4seo_remove_all_post_ids_by_post_type_and_generation_status(string $post_type, string $generation_status_option_name) {
    global $wpdb;

    $post_type = sanitize_text_field($post_type);

    // read all ids from $generation_status_option_name and check which of them are of the given post_type
    $post_ids = ai4seo_get_post_ids_from_option($generation_status_option_name);

    // no failed posts? skip here
    if (!$post_ids) {
        return;
    }

    // make sure all post ids are absolute integers
    $post_ids = array_map('absint', $post_ids);

    $possible_post_ids_of_post_type = array();
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $post_ids_chunks = array_chunk( $post_ids, $database_chunk_size );

    foreach ( $post_ids_chunks as $this_post_ids_chunk ) {
        if ( empty( $this_post_ids_chunk ) ) {
            continue;
        }

        $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

        // nail down the post_type
        $this_possible_post_ids_of_post_type = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ({$this_post_ids_placeholders})",
                array_merge( array( $post_type ), $this_post_ids_chunk )
            )
        );

        // on error
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321661, 'Database error: ' . $wpdb->last_error, true);
            return;
        }

        if ( empty( $this_possible_post_ids_of_post_type ) ) {
            continue;
        }

        $possible_post_ids_of_post_type = array_merge( $possible_post_ids_of_post_type, $this_possible_post_ids_of_post_type );
    }

    if (!$possible_post_ids_of_post_type) {
        return;
    }

    // remove all post_ids of the given post_type from $generation_status_option_name
    ai4seo_remove_post_ids_from_option($generation_status_option_name, $possible_post_ids_of_post_type);
}

// =========================================================================================== \\

/**
 * Reads the generated data for a given post, if it exists
 * @param $post_id int the post id
 * @return array
 */
function ai4seo_read_generated_data_from_post_meta(int $post_id): array {
    // reading in post meta, looking for the meta_key AI4SEO_POST_META_GENERATED_DATA_META_KEY
    $generate_data_json_string = get_post_meta($post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, true);

    if (!$generate_data_json_string) {
        return array();
    }

    $generate_data = json_decode($generate_data_json_string, true);

    if (!$generate_data) {
        return array();
    }

    // sanitize all fields and then return
    return ai4seo_deep_sanitize($generate_data);
}

// =========================================================================================== \\

/**
 * Function to save the generated data for a given post
 * @param $post_id int the post id
 * @param $generated_data array the generated data
 * @return bool
 */
function ai4seo_save_generated_data_to_postmeta(int $post_id, array $generated_data): bool {
    // read old data for a basis
    $old_generated_data = ai4seo_read_generated_data_from_post_meta($post_id);

    if (!$old_generated_data) {
        $old_generated_data = array();
    }

    foreach ($generated_data as $this_generated_data_identifier => $this_generated_data_value) {
        if (!is_string($this_generated_data_value) && !is_scalar($this_generated_data_value)) {
            continue;
        }

        // make sure the max length is respected
        $this_generated_data_value = ai4seo_normalize_editor_input_value($this_generated_data_value);
        $this_max_length = ai4seo_get_max_editor_input_length($this_generated_data_identifier);
        $this_generated_data_value = ai4seo_trim_string_to_length($this_generated_data_value, $this_max_length);

        $old_generated_data[$this_generated_data_identifier] = $this_generated_data_value;
    }

    // encode the data
    $generated_data_json_string = wp_json_encode($old_generated_data, JSON_UNESCAPED_UNICODE);

    $success = ai4seo_update_post_meta($post_id, AI4SEO_POST_META_GENERATED_DATA_META_KEY, $generated_data_json_string);;

    // save the data
    return $success;
}

// =========================================================================================== \\

/**
 * Safer wrapper for update_post_meta(). Same parameters and order.
 * Returns true on success, false on failure or when nothing changed and WP reports failure.
 *
 * @param int $post_id     Post ID.
 * @param string $meta_key    Metadata key.
 * @param mixed       $meta_value  Metadata value. Can be any serializable type.
 * @param mixed       $prev_value  Optional. Previous value to check before updating.
 *
 * @return bool True if meta updated/inserted successfully and no DB error occurred. False otherwise.
 */
function ai4seo_update_post_meta(int $post_id, string $meta_key, $meta_value, $prev_value = '' ): bool {
    // Basic validation to avoid useless DB calls.
    $post_id  = absint( $post_id );

    if ( $post_id <= 0 || $meta_key === '' ) {
        return false;
    }

    // Ensure the post exists. get_post() is cached by WP, cheap enough.
    if ( ! get_post( $post_id ) ) {
        return false;
    }

    global $wpdb;

    // Capture and suppress low-level DB errors for a clean boolean outcome.
    $previous_suppress = $wpdb->suppress_errors( true );
    $wpdb->last_error  = ''; // reset before operation

    // slash the value for update_post_meta
    $meta_value = wp_slash($meta_value);

    // Perform to write.
    $result = update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
    
    // Read-only or failed write surfaces here.
    $had_error = ! empty( $wpdb->last_error );

    // Restore previous error handling.
    $wpdb->suppress_errors( $previous_suppress );

    if ( $had_error ) {
        ai4seo_debug_message(984321662, 'Database error during update_post_meta: ' . $wpdb->last_error, true);
    }

    // Return true only if no DB error occurred.
    return ( ! $had_error );
}

// =========================================================================================== \\

/**
 * Function to read the post content summary for a given post
 * @param $post_id int the post id
 * @return string
 */
function ai4seo_read_post_content_summary_from_post_meta(int $post_id): string {
    // reading in post meta, looking for the meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY
    $post_content_summary = get_post_meta($post_id, AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY, true);

    if (!$post_content_summary) {
        return "";
    }

    return sanitize_text_field($post_content_summary);
}

// =========================================================================================== \\

/**
 * Function to save the post content summary for a given post
 * @param $post_id int the post id
 * @param $post_content_summary string the content summary
 * @return bool
 */
function ai4seo_save_post_content_summary_to_postmeta(int $post_id, string $post_content_summary): bool {
    // sanitize the post content
    $post_content_summary = sanitize_text_field($post_content_summary);

    // save the data
    return ai4seo_update_post_meta($post_id, AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY, $post_content_summary);
}

// =========================================================================================== \\

/**
 * Returns the configured maximum length for the given editor field identifier.
 *
 * @param string $identifier The metadata or attachment identifier to evaluate.
 * @return int
 */
function ai4seo_get_max_editor_input_length(string $identifier): int {
    if (!defined('AI4SEO_MAX_EDITOR_INPUT_LENGTHS')) {
        return 512;
    }

    $identifier   = strtolower($identifier);
    $max_lengths  = AI4SEO_MAX_EDITOR_INPUT_LENGTHS;
    $fallback_max = (int) ($max_lengths['fallback'] ?? 512);

    if (isset($max_lengths[$identifier])) {
        return (int) $max_lengths[$identifier];
    }

    return $fallback_max;
}

// =========================================================================================== \\

/**
 * Normalizes editor input values so they can be validated and trimmed consistently.
 *
 * @param mixed $value The value to normalize.
 * @return string
 */
function ai4seo_normalize_editor_input_value($value, bool $should_unslash = false): string {
    if (is_scalar($value)) {
        $value = (string) $value;
    }

    if (!is_string($value)) {
        return '';
    }

    // normalize metadata raw value
    $value = trim($value);

    if ($should_unslash) {
        $value = wp_unslash($value);
    }

    return $value;
}

// =========================================================================================== \\

/**
 * Sanitizes editor input values while preserving literal backslashes.
 *
 * @param mixed $value The value to sanitize.
 * @return string
 */
function ai4seo_sanitize_editor_field_value($value): string {
    if (is_scalar($value)) {
        $value = (string) $value;
    }

    if (!is_string($value)) {
        return '';
    }

    $value = wp_check_invalid_utf8($value);
    $value = wp_strip_all_tags($value);
    $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

    if ($value === null) {
        return '';
    }

    return trim($value);
}

// =========================================================================================== \\

/**
 * Trims a string to the provided maximum length.
 *
 * @param string $value      The string to trim.
 * @param int    $max_length The maximum length.
 * @return string
 */
function ai4seo_trim_string_to_length(string $value, int $max_length): string {
    if ($max_length <= 0) {
        return $value;
    }

    return ai4seo_mb_substr($value, 0, $max_length);
}

// =========================================================================================== \\

/**
 * Updates the currently active metadata for a post. Also applies the changes to the third party seo plugins postmeta (table) meta keys
 * @param $post_id int the post id
 * @param $metadata_updates array the updates
 * @param $overwrite_existing_data bool if true, existing data will be overwritten, if false, we check the settings to identify the metadata fields that should be overwritten
 * @return boolean true on success, false on failure
 */
function ai4seo_update_active_metadata(int $post_id, array $metadata_updates, bool $overwrite_existing_data = false): bool {
    if (!defined('AI4SEO_METADATA_DETAILS')) {
        return false;
    }

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(239030919, 'Prevented loop', true);
        return false;
    }

    // sanitize everything
    $metadata_updates = ai4seo_deep_sanitize($metadata_updates, 'ai4seo_sanitize_editor_field_value');

    // handle specific overwrite existing data instruction
    $overwrite_existing_data_metadata_names = array();

    if (!$overwrite_existing_data) {
        $overwrite_existing_data_metadata_names = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA);
    }

    $overall_success = true;

    // go through $ai4seo_metadata_fields_details, find corresponding api-identifier and add the data to the post meta
    foreach (AI4SEO_METADATA_DETAILS as $this_metadata_identifier => $this_metadata_details) {
        $this_api_identifier = $this_metadata_details["api-identifier"];
        $this_postmeta_key = ai4seo_generate_postmeta_key_by_metadata_identifier($post_id, $this_metadata_identifier);

        if (isset($metadata_updates[$this_metadata_identifier])) {
            $this_new_metadata_content = $metadata_updates[$this_metadata_identifier];
        } else if (isset($metadata_updates[$this_api_identifier])) {
            # workaround: also check the api identifier for this metadata, as the api sends facebook-title as social-media-title etc.
            # Should be fixed since 2.0.2, we keep this in case the user got old generated data prior to 2.0.2
            $this_new_metadata_content = $metadata_updates[$this_api_identifier];
        } else {
            continue;
        }

        // make sure to respect max length
        $this_new_metadata_content = ai4seo_normalize_editor_input_value($this_new_metadata_content);
        $this_max_length = ai4seo_get_max_editor_input_length($this_metadata_identifier);
        $this_new_metadata_content = ai4seo_trim_string_to_length($this_new_metadata_content, $this_max_length);

        // do we overwrite this particular metadata field?
        if ($overwrite_existing_data === true) {
            $overwrite_this_metadata_field = true;
        } else {
            $overwrite_this_metadata_field = in_array($this_metadata_identifier, $overwrite_existing_data_metadata_names);
        }

        // update third party seo plugins metadata and get a hint if we should skip to update our own metadata, when we
        // do not overwrite third-party seo plugins data AND there is existing data already
        $we_should_not_save_our_own_metadata = ai4seo_update_third_party_seo_plugins_metadata($post_id, $this_metadata_identifier, $this_new_metadata_content, $overwrite_this_metadata_field);

        if ($we_should_not_save_our_own_metadata) {
            continue;
        }

        // add value to our own postmeta (table) meta key entry, but only if not set yet (fill only empty fields)
        if ($overwrite_this_metadata_field) {
            $this_success = ai4seo_update_post_meta($post_id, $this_postmeta_key, $this_new_metadata_content);
        } else {
            $this_success = ai4seo_update_postmeta_if_empty($post_id, $this_postmeta_key, $this_new_metadata_content);
        }

        if (!$this_success) {
            $overall_success = false;
        }
    }

    // purge cache if we had success
    if ($overall_success) {
        // try purge cache
        try {
            ai4seo_purge_frontend_cache_for_post($post_id);
        } catch (Exception $e) {
            // do nothing
        }

    }

    return $overall_success;
}

// =========================================================================================== \\

/**
 * Updates the metadata for a post in the third party seo plugins postmeta (table) meta keys and if we do not overwrite
 * AND there is existing data already in one of the third party seo plugins.
 * We return true to indicate that we should not save our own metadata for this field.
 * @param $post_id int the post id
 * @param $metadata_identifier string the metadata identifier
 * @param $metadata_value string the metadata value
 * @param $overwrite_existing_data bool if true, existing data will be overwritten
 * @return bool if we do not overwrite AND there is existing data already
 **/
function ai4seo_update_third_party_seo_plugins_metadata(int $post_id, string $metadata_identifier, string $metadata_value, bool $overwrite_existing_data): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(378117210, 'Prevented loop', true);
        return false;
    }

    $we_should_not_save_our_own_metadata = false;

    // check if we sync this metadata
    $apply_changes_only_to_this_metadata = ai4seo_get_setting(AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA);

    if (!$apply_changes_only_to_this_metadata || !is_array($apply_changes_only_to_this_metadata) || !in_array($metadata_identifier, $apply_changes_only_to_this_metadata)) {
        return false;
    }

    // get the active third party seo plugins
    $active_supported_third_party_seo_plugins = ai4seo_get_active_third_party_seo_plugin_details();

    if (!$active_supported_third_party_seo_plugins) {
        return false;
    }

    $apply_changes_only_to_this_third_party_seo_plugin = ai4seo_get_setting(AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS);

    if (!$apply_changes_only_to_this_third_party_seo_plugin || !is_array($apply_changes_only_to_this_third_party_seo_plugin)) {
        return false;
    }

    foreach ($active_supported_third_party_seo_plugins as $this_third_party_seo_plugin_identifier => $this_third_party_seo_plugin_details) {
        // check if we should only apply changes to a specific third party seo plugin
        if (!in_array($this_third_party_seo_plugin_identifier, $apply_changes_only_to_this_third_party_seo_plugin)) {
            continue;
        }

        // check if we got any meta keys for this third party seo plugin
        if (!isset($this_third_party_seo_plugin_details['generation-field-postmeta-keys'][$metadata_identifier])) {
            continue;
        }

        // workaround: handle SLIM SEO (stores everything in a single serialized postmeta)
        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO) && $this_third_party_seo_plugin_identifier === AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO) {
            $this_was_updated = ai4seo_update_active_metadata_for_slim_seo($post_id, $metadata_identifier, $metadata_value, !$overwrite_existing_data);

            if (!$this_was_updated && !$overwrite_existing_data) {
                $we_should_not_save_our_own_metadata = true;
            }

            continue;
        }

        // workaround: handle Blog2Social (stores everything in a single serialized postmeta)
        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL) && $this_third_party_seo_plugin_identifier === AI4SEO_THIRD_PARTY_PLUGIN_BLOG2SOCIAL) {
            $this_was_updated = ai4seo_update_active_metadata_for_blog2social($post_id, $metadata_identifier, $metadata_value, !$overwrite_existing_data);

            if (!$this_was_updated && !$overwrite_existing_data) {
                $we_should_not_save_our_own_metadata = true;
            }

            continue;
        }

        // workaround: handle Squirrly SEO (stores everything in a single serialized column in own table)
        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO) && $this_third_party_seo_plugin_identifier === AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO) {
            $this_was_updated = ai4seo_update_active_metadata_for_squirrly_seo($post_id, $metadata_identifier, $metadata_value, !$overwrite_existing_data);

            if (!$this_was_updated && !$overwrite_existing_data) {
                $we_should_not_save_our_own_metadata = true;
            }

            continue;
        }

        // normal postmeta key update
        $this_third_party_seo_plugin_postmeta_key = sanitize_text_field($this_third_party_seo_plugin_details['generation-field-postmeta-keys'][$metadata_identifier]);

        if ($overwrite_existing_data) {
            $this_was_updated = ai4seo_update_post_meta($post_id, $this_third_party_seo_plugin_postmeta_key, $metadata_value);
        } else {
            $this_was_updated = ai4seo_update_postmeta_if_empty($post_id, $this_third_party_seo_plugin_postmeta_key, $metadata_value);

            if (!$this_was_updated) {
                $we_should_not_save_our_own_metadata = true;
            }
        }

        // handle specific third party seo plugin 'ALL IN ONE SEO'
        if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO) && $this_third_party_seo_plugin_identifier === AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO) {
            ai4seo_update_active_metadata_for_all_in_one_seo($post_id, $metadata_identifier, $metadata_value, !$overwrite_existing_data);
            # we can ignore $this_was_updated as ALL in one SEO saves the values both in postmeta and in its own table
        }
    }

    return $we_should_not_save_our_own_metadata;
}

// =========================================================================================== \\

/**
 * Updates the metadata for a post for the Squirrly SEO plugin
 * @param int $post_id the post id
 * @param string $metadata_identifier the metadata identifier
 * @param string $metadata_value the metadata value
 * @param bool $only_if_empty if true, the metadata will only be updated if it is empty
 * @return bool true if we updated something, false if not
 */
function ai4seo_update_active_metadata_for_squirrly_seo(int $post_id, string $metadata_identifier, string $metadata_value, bool $only_if_empty = false): bool {
    // check table "wp_qss" -> column "seo". It's serialized with keys "title", "description", "og_title", "og_description", "tw_title", "tw_description"
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
        "facebook-title" => "og_title",
        "facebook-description" => "og_description",
        "twitter-title" => "tw_title",
        "twitter-description" => "tw_description",
    );

    $this_slim_seo_json_key = $metadata_identifier_mapping[$metadata_identifier] ?? "";

    if (!$this_slim_seo_json_key) {
        return false;
    }

    // read entry
    global $wpdb;

    // Serialized key pattern for "ID"
    $pattern = '%s:2:"ID";i:' . esc_sql($post_id) . ';%';

    // Updated SQL query using LIKE to find the serialized post ID
    $current_squirrly_values = $wpdb->get_var($wpdb->prepare("SELECT seo FROM {$wpdb->prefix}qss WHERE post LIKE %s", $pattern));

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321683, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }
    $current_squirrly_values = maybe_unserialize($current_squirrly_values);

    if ($current_squirrly_values && is_string($current_squirrly_values)) {
        $current_squirrly_values = unserialize($current_squirrly_values);
    } else if ($current_squirrly_values && is_array($current_squirrly_values)) {
        // do nothing
    } else {
        $current_squirrly_values = array();
    }

    // something is wrong -> return false
    if (!is_array($current_squirrly_values) || empty($current_squirrly_values)) {
        return false;
    }

    // check the current value
    if ($only_if_empty) {
        if (isset($current_squirrly_values[$this_slim_seo_json_key]) && $current_squirrly_values[$this_slim_seo_json_key]) {
            return false;
        }
    }

    // update the value
    $current_squirrly_values[$this_slim_seo_json_key] = sanitize_text_field($metadata_value);

    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}qss SET seo = %s WHERE post LIKE %s", maybe_serialize($current_squirrly_values), $pattern));

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321684, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Updates the metadata for a post for the Slim SEO plugin
 * @param int $post_id the post id
 * @param string $metadata_identifier the metadata identifier
 * @param string $metadata_value the metadata value
 * @param bool $only_if_empty if true, the metadata will only be updated if it is empty
 * @return bool true if we updated something, false if not
 */
function ai4seo_update_active_metadata_for_slim_seo(int $post_id, string $metadata_identifier, string $metadata_value, bool $only_if_empty = false): bool {
    // check postmeta "slim_seo". It's serialized with keys "title" and "description", nothing else
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
    );

    $this_slim_seo_key = $metadata_identifier_mapping[$metadata_identifier] ?? "";

    if (!$this_slim_seo_key) {
        return false;
    }

    // read postmeta entry
    $current_slim_seo_values = get_post_meta($post_id, "slim_seo", true);
    $current_slim_seo_values = maybe_unserialize($current_slim_seo_values);

    // something is wrong -> return false
    if (!is_array($current_slim_seo_values) || !$current_slim_seo_values) {
        $current_slim_seo_values = array();
    }

    // check the current value
    if ($only_if_empty) {
        if (isset($current_slim_seo_values[$this_slim_seo_key]) && $current_slim_seo_values[$this_slim_seo_key]) {
            return false;
        }
    }

    // update the value
    $current_slim_seo_values[$this_slim_seo_key] = sanitize_text_field($metadata_value);

    return ai4seo_update_post_meta($post_id, "slim_seo", $current_slim_seo_values);
}

// =========================================================================================== \\

/**
 * Updates the metadata for a post for the Blog2Social plugin
 * @param int $post_id the post id
 * @param string $metadata_identifier the metadata identifier
 * @param string $metadata_value the metadata value
 * @param bool $only_if_empty if true, the metadata will only be updated if it is empty
 * @return bool true if we updated something, false if not
 */
function ai4seo_update_active_metadata_for_blog2social(int $post_id, string $metadata_identifier, string $metadata_value, bool $only_if_empty = false): bool {
    // check postmeta "_b2s_post_meta". It's serialized with keys "og_title", "og_desc", "card_title" and "card_desc"
    $metadata_identifier_mapping = array(
        "facebook-title" => "og_title",
        "facebook-description" => "og_desc",
        "twitter-title" => "card_title",
        "twitter-description" => "card_desc",
    );

    $this_mapped_key = $metadata_identifier_mapping[$metadata_identifier] ?? "";

    if (!$this_mapped_key) {
        return false;
    }

    // read postmeta entry
    $current_values = get_post_meta($post_id, "_b2s_post_meta", true);
    $current_values = maybe_unserialize($current_values);

    // something is wrong -> return false
    if (!is_array($current_values) || !$current_values) {
        $current_values = array();
    }

    // check the current value
    if ($only_if_empty) {
        if (isset($current_values[$this_mapped_key]) && $current_values[$this_mapped_key]) {
            return false;
        }
    }

    // update the value
    $current_values[$this_mapped_key] = sanitize_text_field($metadata_value);

    return ai4seo_update_post_meta($post_id, "_b2s_post_meta", $current_values);
}

// =========================================================================================== \\

/**
 * Updates the metadata for a post for the All in One SEO plugin
 * @param int $post_id the post id
 * @param string $metadata_identifier the metadata identifier
 * @param string $metadata_value the metadata value
 * @param bool $only_if_empty if true, the metadata will only be updated if it is empty
 * @return bool true if we updated something, false if not
 */
function ai4seo_update_active_metadata_for_all_in_one_seo(int $post_id, string $metadata_identifier, string $metadata_value, bool $only_if_empty = false): bool {
    // check table "wp_aioseo_posts" for the post id. Columns are "title", "description", "og_title", "og_description", "twitter_title", "twitter_description"
    $metadata_identifier_mapping = array(
        "meta-title" => "title",
        "meta-description" => "description",
        "facebook-title" => "og_title",
        "facebook-description" => "og_description",
        "twitter-title" => "twitter_title",
        "twitter-description" => "twitter_description",
    );

    $this_aioseo_column = $metadata_identifier_mapping[$metadata_identifier] ?? "";

    if (!$this_aioseo_column) {
        return false;
    }

    global $wpdb;

    $post_id_exists = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}aioseo_posts WHERE post_id = %d", $post_id));

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321685, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }

    if (!$post_id_exists) {
        return false;
    }

    // check the current value
    if ($only_if_empty) {
        $current_value = $wpdb->get_var($wpdb->prepare(
            "SELECT " . esc_sql($this_aioseo_column) . " 
            FROM {$wpdb->prefix}aioseo_posts 
            WHERE post_id = %d",
            $post_id)
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321686, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }

        if ($current_value) {
            return false;
        }
    }

    // update the value
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}aioseo_posts SET " . esc_sql($this_aioseo_column) . " = %s WHERE post_id = %d", $metadata_value, $post_id));

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321687, 'Database error: ' . $wpdb->last_error, true);
        return false;
    }

    return true;
}


// =========================================================================================== \\

/**
 * Returns the language of a post / page / product
 * @param int $post_id the post id
 * @return string the language of the post
 */
function ai4seo_get_posts_language(int $post_id): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(482665630, 'Prevented loop', true);
        return '';
    }

    $metadata_generation_language = sanitize_text_field(ai4seo_get_setting(AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE));

    do if ($metadata_generation_language == "auto") {
        // try to get post language using multilanguage plugin
        $multilanguage_plugin_language = sanitize_text_field(ai4seo_try_get_post_language_by_checking_multilanguage_plugins($post_id));

        if ($multilanguage_plugin_language) {
            $metadata_generation_language = $multilanguage_plugin_language;
            break;
        }

        // we stay at "auto" if we could not find a language -> let the AI detect the language
    } while (false);

    return $metadata_generation_language;
}

// =========================================================================================== \\

/**
 * Retrieves the active meta tags
 *
 * @return array The active meta tags
 */
function ai4seo_get_active_meta_tags(): array {
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 99999)) {
        ai4seo_debug_message(798608158, 'Prevented loop', true);
        return array();
    }

    $active_meta_tags = ai4seo_get_setting(AI4SEO_SETTING_ACTIVE_META_TAGS);

    if (!is_array($active_meta_tags)) {
        return array();
    }

    return $active_meta_tags;
}

// =========================================================================================== \\

function ai4seo_get_active_meta_tags_names($active_meta_tags = null): array {
    if ($active_meta_tags === null) {
        $active_meta_tags = ai4seo_get_active_meta_tags();
    }

    $active_meta_tags_names = array();

    foreach (AI4SEO_METADATA_DETAILS AS $ai4seo_this_metadata_identifier => $ai4seo_this_metadata_details) {
        if (in_array($ai4seo_this_metadata_identifier, $active_meta_tags) && isset($ai4seo_this_metadata_details["name"])) {
            $active_meta_tags_names[] = $ai4seo_this_metadata_details["name"];
        }
    }

    return $active_meta_tags_names;
}


// endregion
// ___________________________________________________________________________________________ \\
// region ATTACHMENTS / MEDIA =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to read and analyze the attachment attributes coverage of the given attachment ids (post ids)
 * @param int|array $attachment_post_ids The post ids of the attachments we want to analyze
 * @return array
 */
function ai4seo_read_and_analyse_attachment_attributes_coverage( $attachment_post_ids ): array {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(898193802, 'Prevented loop', true);
        return array();
    }

    // allow single ID
    if ( ! is_array( $attachment_post_ids ) ) {
        $attachment_post_ids = array( $attachment_post_ids );
    }

    // initial coverage structure
    $attachment_attributes_coverage = ai4seo_create_empty_attachment_attributes_coverage_array( $attachment_post_ids );

    // bail on empty or invalid IDs
    if ( empty( $attachment_post_ids ) ) {
        return $attachment_attributes_coverage;
    }

    foreach ( $attachment_post_ids as $attachment_post_id ) {
        if ( ! is_numeric( $attachment_post_id ) ) {
            return $attachment_attributes_coverage;
        }
    }

    // normalize IDs
    $attachment_post_ids = array_map( 'absint', $attachment_post_ids );

    // active attributes
    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if ( ! $active_attachment_attributes ) {
        return $attachment_attributes_coverage;
    }

    // chunk IDs to avoid huge IN-lists
    $database_chunk_size = ai4seo_get_database_chunk_size();
    $attachment_post_ids_chunks = array_chunk( $attachment_post_ids, $database_chunk_size );


    // --- TITLE / CAPTION / DESCRIPTION / GUID ----------------------------------------- \\

    if ( array_intersect( array( 'title', 'caption', 'description' ), $active_attachment_attributes ) ) {
        foreach ( $attachment_post_ids_chunks as $this_attachment_post_ids_chunk ) {
            if ( empty( $this_attachment_post_ids_chunk ) ) {
                continue;
            }

            $this_attachment_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_attachment_post_ids_chunk ), '%d' ) );

            $attachment_posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_title, post_excerpt, post_content, guid 
                     FROM {$wpdb->posts} 
                     WHERE ID IN ({$this_attachment_post_ids_placeholders})",
                    $this_attachment_post_ids_chunk
                ),
                ARRAY_A
            );

            // on error
            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321663, 'Database error: ' . $wpdb->last_error, true);
                return array();
            }

            if ( ! $attachment_posts ) {
                continue;
            }

            foreach ( $attachment_posts as $this_attachment_post ) {
                $this_attachment_post_id = absint( $this_attachment_post['ID'] );

                if ( in_array( 'title', $active_attachment_attributes, true ) ) {
                    $attachment_attributes_coverage[ $this_attachment_post_id ]['title'] = $this_attachment_post['post_title'];
                }

                if ( in_array( 'caption', $active_attachment_attributes, true ) ) {
                    $attachment_attributes_coverage[ $this_attachment_post_id ]['caption'] = $this_attachment_post['post_excerpt'];
                }

                if ( in_array( 'description', $active_attachment_attributes, true ) ) {
                    $attachment_attributes_coverage[ $this_attachment_post_id ]['description'] = $this_attachment_post['post_content'];
                }

                // file-name if needed in the future:
                // $file_name = substr( $attachment_post['guid'], strrpos( $attachment_post['guid'], '/' ) + 1 );
                // $attachment_attributes_coverage[ $this_id ]['file-name'] = $file_name;
            }
        }
    }


    // --- ALT TEXT --------------------------------------------------------------------- \\

    if ( in_array( 'alt-text', $active_attachment_attributes, true ) ) {
        foreach ( $attachment_post_ids_chunks as $this_post_ids_chunk ) {
            if ( empty( $this_post_ids_chunk ) ) {
                continue;
            }

            $this_post_ids_placeholders = implode( ',', array_fill( 0, count( $this_post_ids_chunk ), '%d' ) );

            $this_attachment_postmetas = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ({$this_post_ids_placeholders})",
                    array_merge( array( '_wp_attachment_image_alt' ), $this_post_ids_chunk )
                ),
                ARRAY_A
            );

            // on error
            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321664, 'Database error: ' . $wpdb->last_error, true);
                return array();
            }

            if ( empty( $this_attachment_postmetas ) ) {
                continue;
            }

            foreach ( $this_attachment_postmetas as $this_attachment_postmeta ) {
                $this_attachment_post_id = absint( $this_attachment_postmeta['post_id'] );
                $attachment_attributes_coverage[ $this_attachment_post_id ]['alt-text'] = strval( $this_attachment_postmeta['meta_value'] );
            }
        }
    }

    return $attachment_attributes_coverage;
}

// =========================================================================================== \\

/**
 * Function to return the summary of the attachment attributes coverage array
 * @param $attachment_attributes_coverage array The attachment attributes coverage array generated by ai4seo_read_and_analyze_attachment_attributes_coverage()
 * @return array The summary of the attachment attributes coverage array, basically the amount of filled attachment attributes per attachment
 */
function ai4seo_get_attachment_attributes_coverage_summary(array $attachment_attributes_coverage): array {
    // generate a summary of the attachment attributes coverage array
    $attachment_attributes_coverage_summary = array();

    if (!$attachment_attributes_coverage) {
        return $attachment_attributes_coverage_summary;
    }

    foreach ($attachment_attributes_coverage as $attachment_post_id => $attachment_attributes) {
        $attachment_attributes_coverage_summary[$attachment_post_id] = 0;

        foreach ($attachment_attributes as $this_attachment_attribute) {
            if ($this_attachment_attribute) {
                $attachment_attributes_coverage_summary[$attachment_post_id]++;
            }
        }
    }

    return $attachment_attributes_coverage_summary;
}

// =========================================================================================== \\

/**
 * Function to create an empty attachment attributes coverage array
 * @param $attachment_post_ids array The post ids of the attachments we want to analyze
 * @return array The empty attachment attributes coverage array
 */
function ai4seo_create_empty_attachment_attributes_coverage_array(array $attachment_post_ids): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(155352411, 'Prevented loop', true);
        return array();
    }

    // make sure all entries of post_ids are numeric
    foreach ($attachment_post_ids as $attachment_post_id) {
        if (!is_numeric($attachment_post_id)) {
            return array();
        }
    }

    // Make sure that all parameters are not empty
    if (empty($attachment_post_ids)) {
        return array();
    }

    if (!defined('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS')) {
        return array();
    }

    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if (!$active_attachment_attributes) {
        return array();
    }

    // build an array that holds track of which attachment_attributes are covered by the given posts
    $attachment_attributes_coverage = array();

    foreach ($attachment_post_ids as $post_id) {
        $attachment_attributes_coverage[$post_id] = array();

        foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details) {
            if (!in_array($this_attachment_attribute_identifier, $active_attachment_attributes)) {
                continue;
            }

            $attachment_attributes_coverage[$post_id][$this_attachment_attribute_identifier] = "";
        }
    }

    return $attachment_attributes_coverage;
}

// =========================================================================================== \\

/**
 * Checks if the metadata for a given post is fully covered
 * @param $attachment_post_id int The post id to check the metadata coverage for
 * @return bool Whether the metadata for a given post is fully covered
 */
function ai4seo_are_attachment_attributes_fully_covered(int $attachment_post_id): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(346802262, 'Prevented loop', true);
        return true;
    }

    // get the total amount of attachment attributes
    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if (!$active_attachment_attributes) {
        return true;
    }

    $num_active_and_covered_attachment_attributes = 0;

    // get existing attributes coverage
    $attachment_attributes_coverage = ai4seo_read_and_analyse_attachment_attributes_coverage($attachment_post_id);
    $this_attachment_attributes_coverage = $attachment_attributes_coverage[$attachment_post_id] ?? array();

    foreach ($active_attachment_attributes as $this_attachment_attribute) {
        if ($this_attachment_attributes_coverage[$this_attachment_attribute]) {
            $num_active_and_covered_attachment_attributes++;
        }
    }

    $attachment_attributes_coverage_percentage = ($num_active_and_covered_attachment_attributes / count($active_attachment_attributes)) * 100;

    return ($attachment_attributes_coverage_percentage == 100);
}

// =========================================================================================== \\

/**
 * Returns the number of active attachment attributes
 * @return int the number of active attachment attributes
 */
function ai4seo_get_active_num_attachment_attributes(): int {
    return count(ai4seo_get_active_attachment_attributes());
}

// =========================================================================================== \\

/**
 * Returns the attachment attributes for a specific attachment post id
 * @param $attachment_post_id int The post id of the attachment
 * @return array The attachment attributes
 */
function ai4seo_read_available_attachment_attributes(int $attachment_post_id): array {
    // Read attachment title, caption, description, alt-text and file-path
    $ai4seo_this_attachment_post = get_post($attachment_post_id);
    $ai4seo_this_post_attachment_attributes_values["title"] = $ai4seo_this_attachment_post->post_title ?? "";
    $ai4seo_this_post_attachment_attributes_values["caption"] = $ai4seo_this_attachment_post->post_excerpt ?? "";
    $ai4seo_this_post_attachment_attributes_values["description"] = $ai4seo_this_attachment_post->post_content ?? "";
    $ai4seo_this_post_attachment_attributes_values["alt-text"] = get_post_meta($attachment_post_id, "_wp_attachment_image_alt", true) ?? "";
    //$ai4seo_this_attachment_post_details["file-name"] = basename(get_attached_file($attachment_post_id)) ?? "";

    return $ai4seo_this_post_attachment_attributes_values;
}

// =========================================================================================== \\

/**
 * Refreshes the attachment attributes coverage for the given post by putting the post id into the corresponding option
 * @param $attachment_post_id int The post id to refresh the attachment attributes coverage for
 * @param null $post WP_Post|null The post object to refresh the attachment attributes coverage for
 * @return void
 */
function ai4seo_refresh_one_posts_attachment_attributes_coverage(int $attachment_post_id, $post = null) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(393113591, 'Prevented loop', true);
        return;
    }

    if (!is_numeric($attachment_post_id)) {
        return;
    }

    if (!ai4seo_is_post_a_valid_attachment($attachment_post_id, $post)) {
        ai4seo_remove_post_ids_from_all_options($attachment_post_id);
        return;
    }

    // consider which option to put the post id into
    if (ai4seo_are_attachment_attributes_fully_covered($attachment_post_id)) {
        ai4seo_add_post_ids_to_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);

        // check if the post has generated data
        if (ai4seo_post_has_generated_data($attachment_post_id)) {
            ai4seo_add_post_ids_to_option(AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
        }
    } else {
        ai4seo_add_post_ids_to_option(AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $attachment_post_id);
    }
}

// =========================================================================================== \\

/**
 * This function checks if an attachment is valid for our plugin to be considered
 * @param $attachment_post_id int The post id to check
 * @param $attachment_post WP_Post|null The post object to check
 * @return bool Whether the attachment is valid
 */
function ai4seo_is_post_a_valid_attachment(int $attachment_post_id, WP_Post $attachment_post = null): bool {
    global $ai4seo_allowed_attachment_mime_types;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(304333735, 'Prevented loop', true);
        return false;
    }

    if (!is_numeric($attachment_post_id)) {
        return false;
    }

    $supported_attachment_post_types = ai4seo_get_supported_attachment_post_types();

    // read post
    if ($attachment_post === null) {
        $attachment_post = get_post($attachment_post_id);
    }

    // check if the post could be read
    if (!$attachment_post || is_wp_error($attachment_post) || !isset($attachment_post->post_type)) {
        return false;
    }

    // check if the post type is an attachment
    if (!in_array($attachment_post->post_type, $supported_attachment_post_types)) {
        return false;
    }

    $attachment_post_mime_type = ai4seo_get_attachment_post_mime_type($attachment_post_id);

    // check mime type
    if (!in_array($attachment_post_mime_type, $ai4seo_allowed_attachment_mime_types)) {
        return false;
    }

    // check post status
    if (!in_array($attachment_post->post_status, array("publish", "future", "private", "pending", "inherit"))) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

/**
 * Creates a base64-encoded string of an image, downsizing it if necessary to fit within 3 MB.
 *
 * @param string $image_data The image data to encode.
 * @return string The base64-encoded image data, or false if there was an error.
 */
function ai4seo_smart_image_base64_encode( string $image_data ): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(241293986, 'Prevented loop', true);
        return '';
    }

    // Set the file size limit to 1 MB.
    $max_file_size = 100000; // 1 MB in bytes.

    try {
        // Get the size of the decoded image data in bytes.
        $image_size = strlen( $image_data );

        // If the image size is less than or equal to the limit, return the original image as base64.
        if ( $image_size <= $max_file_size ) {
            return base64_encode( $image_data );
        }

        // check if we can use the image functions
        if ( !function_exists( 'imagecreatefromstring' )
            || !function_exists( 'imagejpeg')
            || !function_exists( 'imagecopyresampled' )
            || !function_exists( 'imagecreatetruecolor' )
            || !function_exists( 'imagedestroy' )
        ) {
            throw new Exception( 'Required image functions are not available.' );
        }

        // Try to create an image from the string.
        $image = @imagecreatefromstring( $image_data );

        if ( $image === false ) {
            throw new Exception( 'Failed to create image from string.' );
        }

        // Get the original image dimensions.
        $width  = imagesx( $image );
        $height = imagesy( $image );

        // Calculate the scaling factor to downsize the image to fit within 1 MB.
        $scale      = sqrt( $max_file_size / $image_size );
        $new_width  = intval( $width * $scale );
        $new_height = intval( $height * $scale );

        // Create a new image with the new dimensions.
        $new_image = imagecreatetruecolor( $new_width, $new_height );

        if ( !imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height ) ) {
            throw new Exception( 'Failed to resample the image.' );
        }

        // Start output buffering to capture the downsized image data.
        ob_start();

        if ( !imagejpeg( $new_image, null, 75 ) ) { // 75 is the quality for the JPEG.
            ob_end_clean();
            throw new Exception( 'Failed to output the resized image.' );
        }

        $downsized_image_data = ob_get_contents();
        ob_end_clean();

        // Free memory.
        imagedestroy( $image );
        imagedestroy( $new_image );

        // Return the new base64-encoded image.
        return base64_encode( $downsized_image_data );

    } catch ( Exception $e ) {
        // Log the error message for debugging (WordPress style).
        ai4seo_debug_message(578877568, $e->getMessage(), true);

        if (function_exists( 'imagedestroy' ) && function_exists('is_resource')) {
            // Free any allocated resources in case of an error.
            if (isset($image) && is_resource($image)) {
                imagedestroy($image);
            }

            if (isset($new_image) && is_resource($new_image)) {
                imagedestroy($new_image);
            }
        }

        // Return "" to indicate failure.
        return "";
    }
}

// =========================================================================================== \\

/**
 * Updates the currently active attachment attributes for an attachment
 * @param int $attachment_post_id the attachment post id
 * @param array $attachment_attribute_updates the updates to apply with the keys title, caption, description, alt-text
 * @param bool $force_overwrite_all_existing_data if true, existing data will be overwritten, if false, we check the settings to identify the attachment attributes that should be overwritten
 * @return bool true on success, false on failure
 */
function ai4seo_update_attachment_attributes(int $attachment_post_id, array $attachment_attribute_updates = array(), bool $force_overwrite_all_existing_data = false): bool {
    global $wpdb;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(540510370, 'Prevented loop', true);
        return false;
    }

    // sanitize
    $attachment_attribute_updates = ai4seo_deep_sanitize($attachment_attribute_updates, 'ai4seo_sanitize_editor_field_value');

    // handle specific overwrite existing data instruction
    $overwrite_existing_data_attachment_attributes_names = array();

    if (!$force_overwrite_all_existing_data) {
        $overwrite_existing_data_attachment_attributes_names = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES);
    }

    $ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    // read the attachment post
    $attachment_post = get_post($attachment_post_id);

    if (!$attachment_post) {
        return false;
    }

    // keep track if we made changes to the post
    $we_made_changes_to_the_post = false;

    // collect post column updates for wp_posts (title/caption/description)
    $post_updates = array();
    $post_update_formats = array();

    // third party plugins
    $is_nextgen_gallery_active = ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY);

    if ($is_nextgen_gallery_active) {
        $nextgen_gallery_updates = array();
    }

    foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS as $this_attachment_attribute_identifier => $this_attachment_attribute_details) {
        if (!in_array($this_attachment_attribute_identifier, $ai4seo_active_attachment_attributes)) {
            continue;
        }

        $this_api_identifier = $this_attachment_attribute_details['api-identifier'] ?? "";

        if (isset($attachment_attribute_updates[$this_attachment_attribute_identifier])) {
            $this_attachment_attribute_value = $attachment_attribute_updates[$this_attachment_attribute_identifier];
        } else if ($this_api_identifier && isset($attachment_attribute_updates[$this_api_identifier])) {
            $this_attachment_attribute_value = $attachment_attribute_updates[$this_api_identifier];
        } else {
            continue;
        }

        // do we overwrite this particular attachment attribute?
        if ($force_overwrite_all_existing_data === true) {
            $overwrite_this_attachment_attribute = true;
        } else {
            $overwrite_this_attachment_attribute = in_array($this_attachment_attribute_identifier, $overwrite_existing_data_attachment_attributes_names);
        }

        // make sure the max length is respected
        $this_attachment_attribute_value = ai4seo_normalize_editor_input_value($this_attachment_attribute_value);
        $this_max_length = ai4seo_get_max_editor_input_length($this_attachment_attribute_identifier);
        $this_attachment_attribute_value = ai4seo_trim_string_to_length($this_attachment_attribute_value, $this_max_length);

        // which table do we need to update? (title, caption, description => wp_posts, alt-text => wp_postmeta)
        if (in_array($this_attachment_attribute_identifier, array("title", "caption", "description"))) {
            // which column do we need to update? (title => post_title, caption => post_excerpt, description => post_content)
            switch ($this_attachment_attribute_identifier) {
                case "title":
                    $this_post_column = "post_title";
                    break;
                case "caption":
                    $this_post_column = "post_excerpt";
                    break;
                case "description":
                    $this_post_column = "post_content";
                    break;
                default:
                    continue 2;
            }

            // skip, if $overwrite_existing_data is false AND the previous value is not empty
            if (!$overwrite_this_attachment_attribute && !empty($attachment_post->$this_post_column)) {
                continue;
            }

            // collect updates for direct DB update (avoid wp_update_post slashing behavior)
            $post_updates[$this_post_column] = $this_attachment_attribute_value;
            $post_update_formats[] = '%s';

            // handle nextgen gallery description
            if ($is_nextgen_gallery_active && $this_attachment_attribute_identifier == "description") {
                $nextgen_gallery_updates["description"] = $this_attachment_attribute_value;
            }

            $we_made_changes_to_the_post = true;
        } else if ($this_attachment_attribute_identifier == "alt-text") {
            // update the postmeta table (mata_key = _wp_attachment_image_alt)
            if (!$overwrite_this_attachment_attribute) {
                // if not empty -> skip
                $existing_attachment_attribute_value = get_post_meta($attachment_post_id, "_wp_attachment_image_alt", true);

                if (!empty($existing_attachment_attribute_value)) {
                    continue;
                }
            }

            ai4seo_update_post_meta($attachment_post_id, "_wp_attachment_image_alt", $this_attachment_attribute_value);

            // handle nextgen gallery description
            if ($is_nextgen_gallery_active) {
                $nextgen_gallery_updates["alttext"] = $this_attachment_attribute_value;
            }
        }
    }

    // only update the post if we made changes
    if ($we_made_changes_to_the_post && !empty($post_updates)) {
        $wpdb->update(
            $wpdb->posts,
            $post_updates,
            array(
                'ID' => $attachment_post_id,
            ),
            $post_update_formats,
            array(
                '%d',
            )
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321688, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }

        clean_post_cache($attachment_post_id);
    }

    // handle nextgen gallery update
    if ($is_nextgen_gallery_active && isset($nextgen_gallery_updates) && $nextgen_gallery_updates) {
        $nextgen_gallery_pid = (int) $attachment_post->post_parent;
        $nextgen_gallery_updates = ai4seo_deep_sanitize($nextgen_gallery_updates);
        $wpdb->update(esc_sql($wpdb->prefix) . "ngg_pictures", $nextgen_gallery_updates, array("pid" => $nextgen_gallery_pid));

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321689, 'Database error: ' . $wpdb->last_error, true);
            return false;
        }
    }

    return true;
}

// =========================================================================================== \

/**
 * Returns post-related context for an attachment.
 *
 * @param int $attachment_post_id the attachment post id
 * @return string the post-related context for the attachment, including condensed content and surrounding content
 * of the first occurrence of the attachment in the content, separated by " | ".
 * Returns an empty string if no related post or content is found.
 */
function ai4seo_get_attachment_post_related_context(int $attachment_post_id): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(630085028, 'Prevented loop', true);
        return "";
    }

    $attachment_post_id = absint($attachment_post_id);

    if ($attachment_post_id <= 0) {
        ai4seo_debug_message(630085031, 'Invalid attachment post ID', true);
        return "";
    }

    $attachment_url = ai4seo_get_attachment_url($attachment_post_id);

    // try to find a post where the attachment is used (e.g. as featured image or in the content)
    $post_id = ai4seo_get_first_attachment_using_post_id($attachment_post_id);

    if ($post_id <= 0) {
        #ai4seo_debug_message(630085030, 'No post found using the attachment', true);
        return "";
    }

    $post = get_post($post_id);

    if (!$post) {
        ai4seo_debug_message(630085029, 'Could not read post for attachment context');
        return "";
    }

    // POST CONTENT
    $post_related_context = ai4seo_get_condensed_post_content_from_database($post_id);

    // ADD WEBSITE AND POST CONTEXT
    ai4seo_add_post_context($post_id, $post_related_context);

    // FIND SURROUNDING CONTENT
    $content_markers = array(
            'wp-image-' . $attachment_post_id,
            '"id":' . $attachment_post_id,
            'attachment_' . $attachment_post_id,
            'ids="' . $attachment_post_id,
            "ids='" . $attachment_post_id,
    );

    if ($attachment_url) {
        $content_markers[] = $attachment_url;
    }

    $post_content_raw = ($post->post_content ?? '');
    $first_occurrence_position = false;

    foreach ($content_markers as $this_marker) {
        $this_position = ai4seo_mb_strpos($post_content_raw, $this_marker);

        if ($this_position === false) {
            continue;
        }

        if ($first_occurrence_position === false || $this_position < $first_occurrence_position) {
            $first_occurrence_position = $this_position;
        }
    }

    $post_content_around_first_image_occurrence = '';

    if ($first_occurrence_position !== false) {
        $length = 450;
        $start = max(0, ((int) $first_occurrence_position) - 450);
        $pre_image_content = ai4seo_mb_substr($post_content_raw, $start, $length);
        ai4seo_condense_raw_post_content($pre_image_content, 350, 450);

        $start = ((int) $first_occurrence_position - 16);
        $post_image_content = ai4seo_mb_substr($post_content_raw, $start, 750);
        ai4seo_condense_raw_post_content($post_image_content, 650, 750);

        $post_content_around_first_image_occurrence = $pre_image_content . " #IMAGE IS USED HERE# " . $post_image_content;
    }

    if ($post_content_around_first_image_occurrence) {
        $post_related_context .= " Image surrounding content: '[...] " . $post_content_around_first_image_occurrence . " [...]'";
    }

    return $post_related_context;
}

// =========================================================================================== \

/**
 * Returns the first matching post ID where an attachment is used.
 *
 * Preferred lookup order:
 * 1) parent_id relation
 * 2) Featured image relation (_thumbnail_id)
 * 3) Deep search (optional): post_content + postmeta references
 *
 * @param int $attachment_post_id the attachment post id
 * @return int first matching post id or 0 if not found
 */
function ai4seo_get_first_attachment_using_post_id(int $attachment_post_id): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(605028311, 'Prevented loop', true);
        return 0;
    }

    $attachment_post_id = absint($attachment_post_id);

    if ($attachment_post_id <= 0) {
        return 0;
    }

    $attachment_post = get_post($attachment_post_id);

    if (!$attachment_post) {
        return 0;
    }

    if ( $attachment_post->post_type !== 'attachment') {
        return 0;
    }

    // try parent post relation first
    $parent_post_id = absint($attachment_post->post_parent ?? 0);

    if (ai4seo_is_attachment_context_post_eligible($parent_post_id)) {
        return $parent_post_id;
    }

    // look for thumbnail relations next
    global $wpdb;

    $thumbnail_post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id
         FROM {$wpdb->postmeta}
         WHERE meta_key = '_thumbnail_id'
           AND meta_value = %d
         ORDER BY post_id ASC
         LIMIT 25",
        $attachment_post_id
    ));

    if (!empty($thumbnail_post_ids) && is_array($thumbnail_post_ids)) {
        $thumbnail_post_id = ai4seo_get_first_eligible_attachment_context_post_id($thumbnail_post_ids);

        if ($thumbnail_post_id > 0) {
            return $thumbnail_post_id;
        }
    }

    // from here we only continue if deep context search for images is enabled, otherwise we return 0 at this point
    if (!ai4seo_get_setting(AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES)) {
        return 0;
    }

    // deep search: look for the attachment post id or url in the content of posts and postmeta
    // (e.g. for page builders that store the content in postmeta or for attachments
    $attachment_post_id_string = (string) $attachment_post_id;

    $attachment_like_parts = array(
        '%' . $wpdb->esc_like( 'wp-image-' . $attachment_post_id ) . '%',
        '%' . $wpdb->esc_like( '"id":' . $attachment_post_id ) . '%',
        '%' . $wpdb->esc_like( 'attachment_' . $attachment_post_id ) . '%',

        // ids="123, 234, 345" (and variants): match as a distinct list item, not inside another number.
        '%' . $wpdb->esc_like( 'ids="' . $attachment_post_id_string . ',' ) . '%',   // start: ids="234,
        '%' . $wpdb->esc_like( 'ids="' . $attachment_post_id_string . ' ,' ) . '%',  // start (space): ids="234 ,

        '%' . $wpdb->esc_like( 'ids="%,' . $attachment_post_id_string . ',%' ) . '%',    // middle: ids="...,234,..."
        '%' . $wpdb->esc_like( 'ids="%, ' . $attachment_post_id_string . ',%' ) . '%',   // middle with space after comma

        '%' . $wpdb->esc_like( 'ids="%,' . $attachment_post_id_string . '"' ) . '%',     // end: ids="...,234"
        '%' . $wpdb->esc_like( 'ids="%, ' . $attachment_post_id_string . '"' ) . '%',    // end with space

        // Same for single quotes: ids='123, 234, 345'
        '%' . $wpdb->esc_like( "ids='" . $attachment_post_id_string . ',' ) . '%',
        '%' . $wpdb->esc_like( "ids='" . $attachment_post_id_string . ' ,' ) . '%',

        '%' . $wpdb->esc_like( "ids='%," . $attachment_post_id_string . ',%' ) . '%',
        '%' . $wpdb->esc_like( "ids='%, " . $attachment_post_id_string . ',%' ) . '%',

        '%' . $wpdb->esc_like( "ids='%," . $attachment_post_id_string . "'" ) . '%',
        '%' . $wpdb->esc_like( "ids='%, " . $attachment_post_id_string . "'" ) . '%',

        // Common builder attributes.
        '%' . $wpdb->esc_like( 'data-id="' . $attachment_post_id_string . '"' ) . '%',
        '%' . $wpdb->esc_like( "data-id='" . $attachment_post_id_string . "'" ) . '%',

        '%' . $wpdb->esc_like( 'data-ids="' . $attachment_post_id_string . '"' ) . '%',
        '%' . $wpdb->esc_like( "data-ids='" . $attachment_post_id_string . "'" ) . '%',
    );

    $attachment_url = ai4seo_get_attachment_url($attachment_post_id);

    if ( $attachment_url ) {
        $attachment_like_parts[] = '%' . $wpdb->esc_like( $attachment_url ) . '%';

        // Try to match common WordPress variants like "-300x300" and "-scaled".
        $path_parts = wp_parse_url( $attachment_url );

        $attachment_path = $path_parts['path'] ?? '';
        $attachment_base = wp_basename( $attachment_path );

        $dot_position = strrpos( $attachment_base, '.' );

        if ( $dot_position !== false ) {
            $filename = substr( $attachment_base, 0, $dot_position );
            $extension = substr( $attachment_base, $dot_position + 1 );

            // Matches: my-image-300x300.jpg
            $attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-' ) . '%x%' . $wpdb->esc_like( '.' . $extension ) . '%';

            // Matches: my-image-scaled.jpg
            $attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-scaled.' . $extension ) . '%';

            // Matches: my-image-rotated.jpg (sometimes created by WP when editing)
            $attachment_like_parts[] = '%' . $wpdb->esc_like( $filename . '-rotated.' . $extension ) . '%';
        }
    }

    $content_like_clause_parts = array_fill(0, count($attachment_like_parts), 'post_content LIKE %s');

    $content_post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID
         FROM {$wpdb->posts}
         WHERE (" . implode(' OR ', $content_like_clause_parts) . ")
            AND post_type != 'revision'
            AND post_status NOT IN ('auto-draft')
            AND ID != %d
         ORDER BY ID DESC
         LIMIT 20",
        array_merge($attachment_like_parts, array($attachment_post_id))
    ));

    if (!empty($content_post_ids) && is_array($content_post_ids)) {
        $content_post_id = ai4seo_get_first_eligible_attachment_context_post_id($content_post_ids);

        if ($content_post_id > 0) {
            return $content_post_id;
        }
    }

    // if not found in content, look in postmeta
    $postmeta_like_clause_parts = array_fill(0, count($attachment_like_parts), 'meta_value LIKE %s');

    $postmeta_post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id
         FROM {$wpdb->postmeta}
         WHERE (" . implode(' OR ', $postmeta_like_clause_parts) . ")
            AND post_id != %d
         ORDER BY post_id DESC
         LIMIT 100",
        array_merge($attachment_like_parts, array($attachment_post_id))
    ));

    if (!empty($postmeta_post_ids) && is_array($postmeta_post_ids)) {
        $postmeta_post_id = ai4seo_get_first_eligible_attachment_context_post_id($postmeta_post_ids);

        if ($postmeta_post_id > 0) {
            return $postmeta_post_id;
        }
    }

    return 0;
}

// =========================================================================================== \\

/**
 * Returns the first eligible post ID from a list of candidates.
 *
 * @param array $candidate_post_ids list of post ids
 * @return int first eligible post id or 0
 */
function ai4seo_get_first_eligible_attachment_context_post_id(array $candidate_post_ids): int {
    foreach ($candidate_post_ids as $candidate_post_id) {
        $candidate_post_id = absint($candidate_post_id);

        if (ai4seo_is_attachment_context_post_eligible($candidate_post_id)) {
            return $candidate_post_id;
        }
    }

    return 0;
}

// =========================================================================================== \

/**
 * Checks whether a post can be used for attachment context.
 *
 * @param int $post_id the post id
 * @return bool true if eligible
 */
function ai4seo_is_attachment_context_post_eligible(int $post_id): bool {
    $post_id = absint($post_id);

    if ($post_id <= 0) {
        return false;
    }

    $post = get_post($post_id);

    if (!$post) {
        return false;
    }

    if (in_array($post->post_status, array('trash', 'auto-draft'), true)) {
        return false;
    }

    if (in_array($post->post_type, array('attachment', 'revision', 'nav_menu_item'), true)) {
        return false;
    }

    return true;
}

// =========================================================================================== \

/**
 * Returns the language of the attachment
 * @param int $attachment_post_id the attachment post id
 * @return string the language of the attachment
 */
function ai4seo_get_attachments_language(int $attachment_post_id): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(925515687, 'Prevented loop', true);
        return '';
    }

    $attachment_attributes_generation_language = sanitize_text_field(ai4seo_get_setting(AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE));

    do if ($attachment_attributes_generation_language == "auto") {
        // todo: determine language by context (attachment surroundings / usings)

        // try to get post language using multilanguage plugin
        $multilanguage_plugin_language = sanitize_text_field(ai4seo_try_get_post_language_by_checking_multilanguage_plugins($attachment_post_id));

        if ($multilanguage_plugin_language) {
            $attachment_attributes_generation_language = $multilanguage_plugin_language;
            break;
        }

        // fallback: WordPress language
        $attachment_attributes_generation_language = sanitize_text_field(ai4seo_get_wordpress_language());
    } while (false);

    return $attachment_attributes_generation_language;
}

// =========================================================================================== \\

/**
 * Retrieves the active attachment attributes.
 *
 * @return array The active attachment attributes.
 */
function ai4seo_get_active_attachment_attributes(): array {
    $active_attachment_attributes = ai4seo_get_setting(AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES);

    if (!is_array($active_attachment_attributes)) {
        return array();
    }

    return $active_attachment_attributes;
}

// =========================================================================================== \\

/**
 * Retrieves the supported attachment post types
 *
 * @return array the supported attachment post types
 */
function ai4seo_get_supported_attachment_post_types(): array {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(867135627, 'Prevented loop', true);
        return array();
    }

    $ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if (!$ai4seo_active_attachment_attributes) {
        return array();
    }

    $supported_attachment_post_types = array('attachment');

    if (ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY)) {
        $supported_attachment_post_types[] = AI4SEO_NEXTGEN_GALLERY_POST_TYPE;
    }

    return $supported_attachment_post_types;
}

// =========================================================================================== \\

function ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post($only_this_attachment_attributes = null) : int {
    $attachment_attributes_price_table = ai4seo_get_attachment_attributes_price_table($only_this_attachment_attributes);

    if (empty($attachment_attributes_price_table)) {
        return 1;
    }

    // calculate total costs
    return array_sum($attachment_attributes_price_table);
}

// =========================================================================================== \\

function ai4seo_get_attachment_attributes_price_table($only_this_attachment_attributes = null): array {
    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    if (empty($active_attachment_attributes)) {
        return array();
    }

    $price_table = array();

    foreach ($active_attachment_attributes AS $this_active_attachment_attribute_identifier) {
        if ($only_this_attachment_attributes && is_array($only_this_attachment_attributes) && !in_array($this_active_attachment_attribute_identifier, $only_this_attachment_attributes)) {
            continue;
        }

        if (!defined('AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS') || !is_array(AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS)) {
            $price_table[$this_active_attachment_attribute_identifier] = 1; // fallback to 1 credit per attribute
            continue;
        }

        $price_table[$this_active_attachment_attribute_identifier] = AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS[$this_active_attachment_attribute_identifier]['flat-credits-cost'] ?? 1;
    }

    return $price_table;
}

// =========================================================================================== \\

function ai4seo_get_active_attachment_attributes_names($active_attachment_attributes = null): array {
    if ($active_attachment_attributes === null) {
        $active_attachment_attributes = ai4seo_get_active_attachment_attributes();
    }

    $active_attachment_attributes_names = array();

    foreach (AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS AS $ai4seo_this_attachment_attribute_identifier => $ai4seo_this_attachment_attribute_details) {
        if (in_array($ai4seo_this_attachment_attribute_identifier, $active_attachment_attributes) && isset($ai4seo_this_attachment_attribute_details["name"])) {
            $active_attachment_attributes_names[] = $ai4seo_this_attachment_attribute_details["name"];
        }
    }

    return $active_attachment_attributes_names;
}


// endregion
// ___________________________________________________________________________________________ \\
// region POST META ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to update a post meta if it is empty
 * @param $post_id int the post id
 * @param $meta_key string the meta key
 * @param $meta_value string the meta value
 * @return bool True if the post meta was updated, false if not
 */
function ai4seo_update_postmeta_if_empty(int $post_id, string $meta_key, string $meta_value): bool {
    $post_id = sanitize_key($post_id);
    $meta_key = sanitize_key($meta_key);
    $meta_value = sanitize_textarea_field($meta_value);

    $current_value = get_post_meta($post_id, $meta_key, true);

    if ($current_value) {
        return false;
    } else {
        return ai4seo_update_post_meta($post_id, $meta_key, $meta_value);
    }
}

// =========================================================================================== \\

/**
 * Returns weather a post got generated data
 * @param $post_id int the post id
 * @return bool
 */
function ai4seo_post_has_generated_data(int $post_id): bool {
    $generated_data = ai4seo_read_generated_data_from_post_meta($post_id);
    return !empty($generated_data);
}


// endregion
// ___________________________________________________________________________________________ \\
// region WORDPRESS OPTIONS ===================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to get all post ids based on an option that is saved as json
 * @param string $option
 * @return array
 */
function ai4seo_get_post_ids_from_option(string $option): array {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(769714633, 'Prevented loop', true);
        return array();
    }

    $option = sanitize_key($option);

    // get post ids
    $post_ids = ai4seo_get_option($option);

    $post_ids = maybe_unserialize($post_ids);

    // create empty option if it does not exist
    if (!$post_ids) {
        add_option($option, array());
        return array();
    }

    if (ai4seo_is_json($post_ids)) {
        $post_ids = json_decode($post_ids);
    }

    // on error -> return empty array
    if (!$post_ids || !is_array($post_ids)) {
        $post_ids = array();
    }

    // deep intval sanitize
    $post_ids = ai4seo_deep_sanitize($post_ids, 'intval');

    // return unique post ids, remove 0
    $post_ids = array_unique($post_ids);

    $post_ids = array_filter($post_ids, function($value) {
        return $value !== 0;
    });

    return $post_ids;
}

// =========================================================================================== \\

/**
 * Function to add post ids to an option that is saved as json
 * @param $option
 * @param $post_ids
 * @return bool
 */
function ai4seo_add_post_ids_to_option($option, $post_ids): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(942539402, 'Prevented loop', true);
        return false;
    }

    $option = sanitize_key($option);

    if (!is_array($post_ids)) {
        $post_ids = array($post_ids);
    }

    // intval sanitize
    $post_ids = ai4seo_deep_sanitize($post_ids, 'intval');

    // logic based removals
    ai4seo_remove_contradictory_post_ids($option, $post_ids);

    // get old post ids
    $old_post_ids = ai4seo_get_post_ids_from_option($option);

    // add the new post ids to the old ones
    $new_post_ids = array_merge($old_post_ids, $post_ids);
    $new_post_ids = ai4seo_deep_sanitize($new_post_ids, 'intval');
    $new_post_ids = array_unique($new_post_ids);
    $new_post_ids = array_values($new_post_ids);

    // remove 0 entries
    $new_post_ids = array_filter($new_post_ids, function($value) {
        return $value !== 0;
    });

    return ai4seo_update_option($option, $new_post_ids);
}

// =========================================================================================== \\

/**
 * Function to remove post ids from options that are contrary to the option that got added to
 * @param $add_to_this_option string The option that got added to
 * @param $post_ids array The post ids that got added (and need to get removed)
 * @return void
 */
function ai4seo_remove_contradictory_post_ids(string $add_to_this_option, array $post_ids) {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(173818084, 'Prevented loop', true);
        return;
    }

    switch ($add_to_this_option) {
        // now missing -> remove from fully covered and generated
        case AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME, $post_ids);
            ai4seo_remove_post_ids_from_option(AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME, $post_ids);
            break;
        case AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids);
            ai4seo_remove_post_ids_from_option(AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids);
            break;

        // now fully covered -> remove from missing
        case AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME, $post_ids);
            break;
        case AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids);
            break;

        // now processing -> remove from pending
        case AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME, $post_ids);
            break;
        case AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids);
            break;

        // now pending -> remove from processing
        case AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME, $post_ids);
            break;
        case AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME:
            ai4seo_remove_post_ids_from_option(AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME, $post_ids);
            break;
    }
}

// =========================================================================================== \\

/**
 * Remove post ids from an option that is saved as json
 * @param string $remove_from_this_option
 * @param int|array $post_ids
 * @return bool
 */
function ai4seo_remove_post_ids_from_option(string $remove_from_this_option, $post_ids): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 2)) {
        ai4seo_debug_message(534042781, 'Prevented loop', true);
        return false;
    }

    $remove_from_this_option = sanitize_key($remove_from_this_option);

    if (!is_array($post_ids)) {
        $post_ids = array($post_ids);
    }

    // make sure every entry is numeric
    foreach ($post_ids as $key => $post_id) {
        if (!is_numeric($post_id)) {
            unset($post_ids[$key]);
        }
    }

    // get old post ids
    $old_post_ids = ai4seo_get_post_ids_from_option($remove_from_this_option);

    // remove the new post ids from the old ones
    $new_post_ids = array_diff($old_post_ids, $post_ids);

    // rearrange the array keys to start at 0
    $new_post_ids = array_values($new_post_ids);
    $new_post_ids = array_unique($new_post_ids);

    // intval sanitize
    $new_post_ids = ai4seo_deep_sanitize($new_post_ids, 'intval');

    // remove 0 entries
    $new_post_ids = array_filter($new_post_ids, function($value) {
        return $value !== 0;
    });

    // check if old and new post ids are the same
    if ($old_post_ids === $new_post_ids) {
        return false;
    }

    // update the option
    return ai4seo_update_option($remove_from_this_option, $new_post_ids);
}

// =========================================================================================== \\

/**
 * Function to remove post ids from EVERY WP_OPTION
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_options($post_ids) {
    foreach (AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option) {
        ai4seo_remove_post_ids_from_option($ai4seo_option, $post_ids);
    }
}

// =========================================================================================== \\

/**
 * Function ro remove post ids from EVERY WP_OPTION that handles the SEO COVERAGE
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_seo_coverage_options($post_ids) {
    foreach (AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS as $ai4seo_option) {
        ai4seo_remove_post_ids_from_option($ai4seo_option, $post_ids);
    }
}

// =========================================================================================== \\

/**
 * Function to remove post ids from EVERY WP_OPTION that handles the GENERATION STATUS
 * @param int|array $post_ids
 */
function ai4seo_remove_post_ids_from_all_generation_status_options($post_ids) {
    foreach (AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS as $ai4seo_option) {
        ai4seo_remove_post_ids_from_option($ai4seo_option, $post_ids);
    }
}

// =========================================================================================== \\

/**
 * Returns the active overwrite existing metadata settings
 * @return array The active overwrite existing metadata settings
 */
function ai4seo_get_active_overwrite_existing_metadata(): array {
    $active_meta_tags = ai4seo_get_active_meta_tags();
    $overwrite_existing_metadata = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA);

    // remove from $overwrite_existing_metadata any meta tag that is not in $active_meta_tags
    $active_overwrite_existing_metadata = array();

    foreach ($overwrite_existing_metadata AS $this_overwrite_existing_metadata) {
        if (in_array($this_overwrite_existing_metadata, $active_meta_tags)) {
            $active_overwrite_existing_metadata[] = $this_overwrite_existing_metadata;
        }
    }

    return $active_overwrite_existing_metadata;
}

// =========================================================================================== \\

/**
 * Returns the setting if we should generate metadata for fully covered entries.
 * But only if we have active overwrite existing metadata settings.
 * @return bool Whether to generate metadata for fully covered entries
 */
function ai4seo_do_generate_metadata_for_fully_covered_entries(): bool {
    $generate_metadata_for_fully_covered_entries = ai4seo_get_setting(AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES);

    if (!$generate_metadata_for_fully_covered_entries) {
        return false;
    }

    $active_overwrite_existing_metadata = ai4seo_get_active_overwrite_existing_metadata();

    return !empty($active_overwrite_existing_metadata);
}

// =========================================================================================== \\

/**
 * Returns the active overwrite existing media attributes settings
 *
 * @return array The active overwrite existing media attributes settings
 */
function ai4seo_get_active_overwrite_existing_attachment_attributes(): array {
    $active_attachment_attributes = ai4seo_get_active_attachment_attributes();
    $overwrite_existing_attachment_attributes = ai4seo_get_setting(AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES);

    // remove from $overwrite_existing_attachment_attributes any attachment attribute that is not in $active_attachment_attributes
    $active_overwrite_existing_attachment_attributes = array();

    foreach ($overwrite_existing_attachment_attributes AS $this_overwrite_existing_attachment_attribute) {
        if (in_array($this_overwrite_existing_attachment_attribute, $active_attachment_attributes)) {
            $active_overwrite_existing_attachment_attributes[] = $this_overwrite_existing_attachment_attribute;
        }
    }

    return $active_overwrite_existing_attachment_attributes;
}

// =========================================================================================== \\

/**
 * Returns the setting if we should generate attachment attributes for fully covered entries.
 * But only if we have active overwrite existing attachment attributes settings.
 * @return bool Whether to generate attachment attributes for fully covered entries
 */
function ai4seo_do_generate_attachment_attributes_for_fully_covered_entries(): bool {
    $generate_attachment_attributes_for_fully_covered_entries = ai4seo_get_setting(AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES);

    if (!$generate_attachment_attributes_for_fully_covered_entries) {
        return false;
    }

    $active_overwrite_existing_attachment_attributes = ai4seo_get_active_overwrite_existing_attachment_attributes();

    return !empty($active_overwrite_existing_attachment_attributes);
}


// endregion
// ___________________________________________________________________________________________ \\
// region AJAX ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/*
 * Instructions for adding a new AJAX action: see .agent/rules/ajax.md
 */

// =========================================================================================== \\

/**
 * Helper: send clean JSON and log any noise safely.
 */
function ai4seo_send_ajax_success($response = [], $status_code = null, $send_raw_html_content = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $noise = '';

    while (ob_get_level()) {
        $noise .= @ob_get_clean();
    }

    if ($noise !== '') {
        // Log the first part so we can find the culprit later
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            ai4seo_debug_message(526144335, 'AJAX noise stripped: ' . substr($noise, 0, 500), true);
        }
    }

    // clean data
    ai4seo_normalize_ajax_response_data($response);

    if ($send_raw_html_content) {
        ai4seo_echo_wp_kses($response);
    } else {
        // JSON header + exit
        wp_send_json_success($response, $status_code);
    }
}

// =========================================================================================== \\

/**
 * Returns an error as JSON and quit the php execution.
 * @param string $error_message The error message to return
 * @param int $error_code The error code to return
 * @return void
 */
function ai4seo_send_ajax_error(string $error_message = "Unknown Error", int $error_code = 999, $error_headline = "", $add_contact_us_link = true) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $clear_buffer = apply_filters('ai4seo_clear_buffer_on_error', true);

    // Clean output buffer if active
    if ($clear_buffer && ob_get_level()) {
        // error_log(ob_get_contents()); # for debugging
        ob_end_clean();
    }

    wp_send_json_error(array(
        'success' => false,
        'error' => ai4seo_wp_kses($error_message),
        'code' => $error_code,
        'headline' => ai4seo_wp_kses($error_headline),
        'add_contact_us_link' => $add_contact_us_link
    ));
}

// =========================================================================================== \\

function ai4seo_normalize_ajax_response_data(&$data) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (is_array($data)) {
        array_walk_recursive($data, function (&$item) {
            ai4seo_normalize_ajax_response_item($item);
        });
    } else if (is_string($data)) {
        ai4seo_normalize_ajax_response_item($data);
    }

    // check if we already have a success and data structure
    if (is_array($data) && isset($data['success']) && isset($data['data'])) {
        $data = $data['data'];
    }
}

// =========================================================================================== \\

function ai4seo_normalize_ajax_response_item(&$item) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(967428361, 'Prevented loop', true);
        return;
    }

    if (!is_string($item)) {
        return;
    }

    $item = ai4seo_remove_translatepress_tags($item);
}

// =========================================================================================== \\

/**
 * Print a hidden nonce field into admin pages we render.
 */
function ai4seo_print_ajax_nonce_field(): void {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // Only when our menu/page is active.
    if ( !ai4seo_is_user_inside_our_plugin_admin_pages() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    // Hidden input. Not part of a form, just a DOM source for JS. No referrer field.
    printf(
        '<input type="hidden" id="' . esc_attr(AI4SEO_GLOBAL_NONCE_IDENTIFIER) . '" value="%s" />',
        esc_attr( wp_create_nonce( AI4SEO_GLOBAL_NONCE_IDENTIFIER ) )
    );
}

// =========================================================================================== \\

/**
 * Called via AJAX - saves various kind of data
 * @param mixed $additional_upcoming_updates Additional updates to consider (in addition to $_POST)
 * @return void
 */
function ai4seo_save_anything($additional_upcoming_updates = array()) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // add $_POST to the updates
    if (!is_array($additional_upcoming_updates)) {
        $additional_upcoming_updates = array();
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109824);
        return;
    }

    if (is_array($_POST) === false) {
        $raw_all_save_anything_updates = $additional_upcoming_updates;
    } else {
        $post_data = wp_unslash($_POST);
        $post_data = ai4seo_deep_sanitize($post_data);
        $raw_all_save_anything_updates = $additional_upcoming_updates + $post_data;
    }

    // check for and sanitize every $raw_all_save_anything_updates variable with AI4SEO_POST_PARAMETER_PREFIX prefix
    $upcoming_save_anything_updates = array();

    foreach ($raw_all_save_anything_updates as $ai4seo_this_prefixed_input_id => $ai4seo_this_post_value) {
        // only consider prefixed input ids from our plugin
        if (strpos($ai4seo_this_prefixed_input_id, AI4SEO_POST_PARAMETER_PREFIX) === 0) {
            // remove prefix and sanitize
            $ai4seo_this_input_id = ai4seo_get_unprefixed_input_name($ai4seo_this_prefixed_input_id);

            // handle checkboxes
            # todo: use better indicator like "checkbox-true"
            if ($ai4seo_this_post_value === "true") {
                $ai4seo_this_post_value = true;
            } else if ($ai4seo_this_post_value === "false") {
                $ai4seo_this_post_value = false;
            }

            // handle empty arrays (#ai4seo-empty-array# as string)
            if ($ai4seo_this_post_value === "#ai4seo-empty-array#"
                || (is_array($ai4seo_this_post_value) && count($ai4seo_this_post_value) === 1 && reset($ai4seo_this_post_value) === "#ai4seo-empty-array#")) {
                $ai4seo_this_post_value = array();
            }

            $upcoming_save_anything_updates[$ai4seo_this_input_id] = ai4seo_deep_sanitize($ai4seo_this_post_value);
        }
    }
    
    // save various kinds of data, grouped by category
    require_once( ai4seo_get_includes_ajax_process_path('save-anything-categories/save-settings.php') );
    require_once( ai4seo_get_includes_ajax_process_path('save-anything-categories/save-environmental-variables.php') );
    require_once( ai4seo_get_includes_ajax_process_path('save-anything-categories/save-robhub-environmental-variables.php') );
    require_once( ai4seo_get_includes_ajax_process_path('save-anything-categories/save-metadata-editor-values.php') );
    require_once( ai4seo_get_includes_ajax_process_path('save-anything-categories/save-attachment-attributes-editor-values.php') );

    // we can send success if none of the above code sent an error
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - stop bulk generation
 * @return void
 */
function ai4seo_stop_bulk_generation() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // stop bulk generation
    ai4seo_update_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES, AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES]);

    // send success
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - retry all failed attachment attributes
 * @return void
 */
function ai4seo_retry_all_failed_attachment_attributes() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // Reset all failed attachment attributes by clearing the option
    ai4seo_delete_option(AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME);

    // Refresh the generation status summary
    ai4seo_try_start_posts_table_analysis(true);

    // send success
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - retry all failed metadata for a specific post type
 * @return void
 */
function ai4seo_retry_all_failed_metadata() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109825);
        return;
    }

    // Get the post type from the request
    $post_type = sanitize_text_field($_POST['post_type'] ?? '');

    if (empty($post_type)) {
        ai4seo_send_ajax_error(esc_html__('Post type is required', 'ai-for-seo'), 12109825);
        return;
    }

    // Remove all failed post IDs for this post type
    ai4seo_remove_all_post_ids_by_post_type_and_generation_status($post_type, AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME);

    // Refresh the generation status summary
    ai4seo_try_start_posts_table_analysis(true);

    // send success
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - refresh dashboard statistics by running the performance analysis
 * @return void
 */
function ai4seo_refresh_dashboard_statistics() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (ai4seo_get_setting(AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS)) {
        ai4seo_send_ajax_error(
            esc_html__('Heavy database operations are currently disabled. Enable them in Settings to refresh statistics.', 'ai-for-seo'),
            44129001
        );
        return;
    }

    ai4seo_analyze_plugin_performance();

    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - refresh the RobHub account data manually
 * @return void
 */
function ai4seo_refresh_robhub_account() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109826);
        return;
    }

    $check_for_purchase = (bool) ($_POST['check_for_purchase'] ?? false);

    $sync_success = ai4seo_sync_robhub_account('manual_refresh');

    if (!$sync_success) {
        ai4seo_send_ajax_error(
            esc_html__('We could not refresh your account right now. Please try again in a moment.', 'ai-for-seo'),
            44129002
        );
        return;
    }

    $response = array();

    if ($check_for_purchase) {
        $has_purchased_something = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);
        $credits_balance = ai4seo_robhub_api()->get_credits_balance();

        $response['is_purchase_ready'] = ($has_purchased_something && $credits_balance > 400);
    }

    ai4seo_send_ajax_success($response);
}


// =========================================================================================== \\

/**
 * Called via AJAX - disable payg
 * @return void
 */
function ai4seo_disable_payg() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // disable payg setting
    ai4seo_update_setting(AI4SEO_SETTING_PAYG_ENABLED, false);

    // send new settings to robhub
    $sent_pay_as_you_go_settings_response = ai4seo_send_pay_as_you_go_settings();

    if ($sent_pay_as_you_go_settings_response === false) {
        ai4seo_send_ajax_error(esc_html__("Could not send pay-as-you-go settings to RobHub", "ai-for-seo"), 421217325);
        wp_die();
    } else if (is_string($sent_pay_as_you_go_settings_response)) {
        ai4seo_send_ajax_error($sent_pay_as_you_go_settings_response, 431217325);
        wp_die();
    }

    // send success
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

function ai4seo_init_purchase() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109827);
        return;
    }

    // check stripe_price_id
    if (!isset($_POST["stripe_price_id"]) || !is_string($_POST["stripe_price_id"])) {
        ai4seo_send_ajax_error(esc_html__("Invalid stripe_price_id", "ai-for-seo"), 551818325);
        wp_die();
    }

    $stripe_price_id = ai4seo_deep_sanitize($_POST["stripe_price_id"]);

    // build redirect url
    $redirect_url = ai4seo_get_subpage_url("dashboard") . "&ai4seo-just-purchased=true";

    // call robhub api endpoint "payg-settings" with current payg settings
    $robhub_endpoint = "client/init-purchase";

    $endpoint_parameter = array();
    $endpoint_parameter["stripe_price_id"] = $stripe_price_id;
    $endpoint_parameter["redirect_url"] = $redirect_url;

    $response = ai4seo_robhub_api()->call($robhub_endpoint, $endpoint_parameter);

    // check response
    if (!ai4seo_robhub_api()->was_call_successful($response)) {
        ai4seo_debug_message(561818325, 'Invalid response from RobHub API.', true);
        ai4seo_send_ajax_error(esc_html__("Invalid response from RobHub API", "ai-for-seo"), 561818325);
    }

    if (!isset($response["data"]["purchase_url"]) || !$response["data"]["purchase_url"]) {
        ai4seo_debug_message(581818325, 'Invalid response from RobHub API.', true);
        ai4seo_send_ajax_error(esc_html__("Invalid response from RobHub API", "ai-for-seo"), 581818325);
    }

    // url decode
    $purchase_url = urldecode($response["data"]["purchase_url"]);

    // html_entity_decode
    $purchase_url = html_entity_decode($purchase_url);

    // validate
    if (!filter_var($purchase_url, FILTER_VALIDATE_URL)) {
        ai4seo_debug_message(591818325, 'Invalid response from RobHub API.', true);
        ai4seo_send_ajax_error(esc_html__("Invalid response from RobHub API", "ai-for-seo"), 591818325);
    }

    // we assume the purchase process is started now, so we better start syncing the account in case a payment is made
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time());

    ai4seo_send_ajax_success(array("purchase_url" => $purchase_url));
}

// =========================================================================================== \\

/**
 * Called via AJAX - track subscription pricing CTA clicks
 *
 * Updates the JUST_PURCHASED environmental variable so the account sync can run immediately
 * while the user reviews pricing options.
 *
 * @return void
 */
function ai4seo_track_subscription_pricing_visit() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time());

    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - submit plugin deactivation feedback.
 *
 * @return void
 */
function ai4seo_submit_feedback() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // CHECK PARAMETER
    // message
    $feedback_message = '';

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109828);
        return;
    }

    if (isset($_POST['feedback_message']) && is_string($_POST['feedback_message'])) {
        $feedback_message = sanitize_textarea_field(wp_unslash($_POST['feedback_message']));
    }

    // cap at 2000 characters
    if (strlen($feedback_message) > 2000) {
        $feedback_message = ai4seo_mb_substr($feedback_message, 0, 2000);
    }

    // flow
    $feedback_flow = '';

    if (isset($_POST['feedback_flow']) && is_string($_POST['feedback_flow'])) {
        $feedback_flow = sanitize_key(wp_unslash($_POST['feedback_flow']));
    }

    if (!in_array($feedback_flow, array('deactivate', 'claim_offer'), true)) {
        $feedback_flow = 'deactivate';
    }

    // reason
    $feedback_reason = '';

    if (isset($_POST['feedback_reason']) && is_string($_POST['feedback_reason'])) {
        $feedback_reason = sanitize_key(wp_unslash($_POST['feedback_reason']));
    }

    // if flow is deactivate, perform deactivate pre robhub call
    $was_plugin_deactivated = false;

    if ($feedback_flow === 'deactivate') {
        $was_plugin_deactivated = ai4seo_deactivate_plugin();
    }

    // SEND FEEDBACK TO ROBHUB
    $response = ai4seo_robhub_api()->perform_client_feedback_call($feedback_reason, $feedback_message, $feedback_flow);

    if (!ai4seo_robhub_api()->was_call_successful($response)) {
        ai4seo_debug_message(421426226, 'Failed to submit feedback to RobHub API.', true);
        ai4seo_send_ajax_error(esc_html__('Could not submit feedback. Please try again.', 'ai-for-seo'), 55120626);
    }

    // HANDLE RARE CASE WHERE PLUGIN DEACTIVATION FAILED
    if ($feedback_flow === 'deactivate' && !$was_plugin_deactivated) {
        ai4seo_debug_message(171426226, 'Plugin deactivation failed after feedback submission.', true);
        ai4seo_send_ajax_error(esc_html__('Could not deactivate the plugin. Please refresh the page and try again.', 'ai-for-seo'), 56120626);
    }

    // ON CLAIM OFFER -> sync account
    if ($feedback_flow === 'claim_offer') {
        ai4seo_sync_robhub_account('claimed_feedback_offer');
    }

    ai4seo_send_ajax_success(array('was_deactivated' => $was_plugin_deactivated));
}

// =========================================================================================== \\

/**
 * Called via AJAX - requests lost licence data to be sent via email
 * @return void
 */
function ai4seo_request_lost_licence_data() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109829);
        return;
    }

    // check stripe_email
    if (!isset($_POST["stripe_email"]) || !is_string($_POST["stripe_email"])) {
        ai4seo_send_ajax_error(esc_html__("Invalid email address", "ai-for-seo"), 551819325);
        wp_die();
    }

    $stripe_email = sanitize_email($_POST["stripe_email"]);

    // Validate email format
    if (!filter_var($stripe_email, FILTER_VALIDATE_EMAIL)) {
        ai4seo_send_ajax_error(esc_html__("Invalid email address", "ai-for-seo"), 561819325);
        wp_die();
    }

    $response = ai4seo_robhub_api()->perform_lost_licence_call($stripe_email);

    // endpoint lock it (61 seconds -> return error)
    if (!ai4seo_robhub_api()->was_call_successful($response) && isset($response["code"]) && $response["code"] === 521561224) {
        ai4seo_send_ajax_error(esc_html__("You can only request your licence data once every 60 seconds. Please wait a moment and try again.", "ai-for-seo"), 521561224);
        wp_die();
    }

    // Always treat as success regardless of API response (as per requirements)
    // Even if the API responds with an error (e.g. email not found), treat it as a success
    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - resets selected plugin data
 * @return void
 */
function ai4seo_reset_plugin_data() {
    global $wpdb;
    global $ai4seo_settings;
    global $ai4seo_environmental_variables;

    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109830);
        return;
    }

    // remove caches
    if (isset($_POST["ai4seo_reset_cache"]) && $_POST["ai4seo_reset_cache"] === "true") {
        // remove wp_options named robhub_api_lock_*
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_robhub_api_lock_%'");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321690, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ), 784322901);
            wp_die();
        }

        // remove transient ai4seo_last_contact_form_submit_timestamp
        delete_transient("ai4seo_last_contact_form_submit_timestamp");

        // remove very wp_options named inside AI4SEO_ALL_POST_ID_OPTIONS-Array
        foreach (AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option) {
            ai4seo_delete_option($ai4seo_option);
        }

        // delete wp_option AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME
        ai4seo_delete_option(AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME);

        // delete wp_option AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME
        ai4seo_delete_option(AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME);

        // invalidate all environmental variable caches
        ai4seo_invalidate_all_environmental_variable_caches();

        // remove all postmeta entries with meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
                AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY,
            )
        );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321691, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(esc_html__( 'Database error while resetting cache data.', 'ai-for-seo' ), 784322902);
            wp_die();
        }
    }

    // ai4seo_reset_notifications
    if (isset($_POST["ai4seo_reset_notifications"]) && $_POST["ai4seo_reset_notifications"] === "true") {
        // remove all notifications
        ai4seo_remove_all_notifications();
    }

    // remove environmental variables
    if (isset($_POST["ai4seo_reset_environmental_variables"]) && $_POST["ai4seo_reset_environmental_variables"] === "true") {
        $ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
        ai4seo_delete_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME);

        ai4seo_robhub_api()->delete_all_environmental_variables();
    }

    // remove/reset settings
    if (isset($_POST["ai4seo_reset_settings"]) && $_POST["ai4seo_reset_settings"] === "true") {
        $ai4seo_settings = AI4SEO_DEFAULT_SETTINGS;
        ai4seo_delete_option(AI4SEO_SETTINGS_OPTION_NAME);
    }

    // remove existing generated metadata
    if (isset($_POST["ai4seo_reset_metadata"]) && $_POST["ai4seo_reset_metadata"] === "true") {
        // remove all postmeta entries with meta_key _ai4seo_[0-9]+_.*
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key REGEXP '^_ai4seo_[0-9]+_.*$'");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321692, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322903);
            wp_die();
        }

        // remove all postmeta entries with meta_key AI4SEO_POST_META_GENERATED_DATA_META_KEY
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '" . esc_sql(AI4SEO_POST_META_GENERATED_DATA_META_KEY) . "'");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321693, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322904);
            wp_die();
        }

        // remove all postmeta entries with meta_key AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '" . esc_sql(AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY) . "'");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321694, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(esc_html__( 'Database error while resetting metadata.', 'ai-for-seo' ), 784322905);
            wp_die();
        }

        // remove very wp_options named inside AI4SEO_ALL_POST_ID_OPTIONS-Array
        foreach (AI4SEO_ALL_POST_ID_OPTIONS as $ai4seo_option) {
            ai4seo_delete_option($ai4seo_option);
        }

        // delete wp_option AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME
        ai4seo_delete_option(AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME);

        // delete wp_option AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME
        ai4seo_delete_option(AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME);
    }

    // tidy up
    ai4seo_tidy_up();

    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - clears the debug message log
 * @return void
 */
function ai4seo_clear_debug_message_log() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $existing_log_entries = get_option(AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array());

    if (!is_array($existing_log_entries)) {
        $existing_log_entries = array();
    }

    $update_result = ai4seo_update_option(AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array(), false, false);

    if ($update_result === false && !empty($existing_log_entries)) {
        ai4seo_send_ajax_error(esc_html__("Could not clear the debug log. Please try again.", "ai-for-seo"), 712981225);
        return;
    }

    ai4seo_send_ajax_success();
}

/**
 * AJAX handler for exporting settings
 */
function ai4seo_export_settings() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    require_once(ai4seo_get_includes_ajax_process_path("export-settings.php"));
}

// =========================================================================================== \\

/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_show_import_settings_preview() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    ob_start();
    require_once(ai4seo_get_includes_ajax_display_path("import-settings-preview.php"));
    $content = ob_get_clean();

    ai4seo_send_ajax_success($content);
}

// =========================================================================================== \\

/**
 * AJAX handler for uploading and validating import settings file
 */
function ai4seo_import_settings() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // file used for display and processing
    require_once(ai4seo_get_includes_ajax_process_path("import-settings.php"));
}

// =========================================================================================== \\

/**
 * Called via AJAX - Restores default settings for settings page
 * @return void
 */
function ai4seo_restore_default_settings() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    try {
        // Prepare array of settings to restore
        $ai4seo_settings_to_restore = array();

        // Get default values for only the settings page settings
        foreach (AI4SEO_ALL_SETTING_PAGE_SETTINGS as $ai4seo_setting_name) {
            if (isset(AI4SEO_DEFAULT_SETTINGS[$ai4seo_setting_name])) {
                $ai4seo_settings_to_restore[$ai4seo_setting_name] = AI4SEO_DEFAULT_SETTINGS[$ai4seo_setting_name];
            }
        }

        // Update settings using the bulk update function
        if (!ai4seo_bulk_update_settings($ai4seo_settings_to_restore)) {
            ai4seo_send_ajax_error(esc_html__("Failed to restore default settings.", "ai-for-seo"), 14109825);
            return;
        }

        // Success response
        ai4seo_send_ajax_success(array(
            'message' => __("Default settings restored successfully.", "ai-for-seo"),
            'restored_count' => count($ai4seo_settings_to_restore)
        ));

    } catch (Exception $e) {
        ai4seo_debug_message(581818325, 'Error restoring default settings: ' . $e->getMessage(), true);
        ai4seo_send_ajax_error(esc_html__("An error occurred while restoring default settings. Please check your PHP error log for more details.", "ai-for-seo"), 15109825);
    }
}

// =========================================================================================== \\

/**
 * Called via AJAX - Requires the metadata editor to be displayed
 * @return void
 */
function ai4seo_show_metadata_editor() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    ob_start();
    require_once(ai4seo_get_includes_ajax_display_path("metadata-editor.php"));
    $content = ob_get_clean(); // only your output
    ai4seo_send_ajax_success($content);
}


// =========================================================================================== \\

/**
 * Called via AJAX - Requires the attachment attributes editor to be displayed
 * @return void
 */
function ai4seo_show_attachment_attributes_editor() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    ob_start();
    require_once(ai4seo_get_includes_ajax_display_path("attachment-attributes-editor.php"));
    $content = ob_get_clean(); // only your output
    ai4seo_send_ajax_success($content);
}


// =========================================================================================== \\

/**
 * Called via AJAX - Returns the dashboard HTML for auto-refresh functionality
 * @return void
 */
function ai4seo_get_dashboard_html() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // we might want to see fresh data
    ai4seo_check_for_performance_analysis();

    ob_start();
    require_once(ai4seo_get_includes_pages_path("dashboard.php"));
    $dashboard_content = ob_get_clean();

    // Extract only the dashboard container content
    // Look for the opening div and find the matching closing div
    $start_pattern = '<div class=\'ai4seo-cards-container ai4seo-dashboard\'>';
    $start_pos = strpos($dashboard_content, $start_pattern);

    if ($start_pos !== false) {
        // Find the content starting from after the opening tag
        $content_start = $start_pos + strlen($start_pattern);
        $inner_content = substr($dashboard_content, $content_start);

        // Find the last closing div tag (which should be the matching one)
        $last_div_pos = strrpos($inner_content, '</div>');

        if ($last_div_pos !== false) {
            $inner_content = substr($inner_content, 0, $last_div_pos);
            $dashboard_html = '<div class="ai4seo-cards-container ai4seo-dashboard">' . $inner_content . '</div>';
            ai4seo_send_ajax_success($dashboard_html);
        } else {
            ai4seo_send_ajax_error( esc_html__('Dashboard closing tag not found', 'ai-for-seo'), 71628825 );
        }
    } else {
        ai4seo_send_ajax_error( esc_html__('Dashboard container not found', 'ai-for-seo'), 81628825 );
    }
}


// =========================================================================================== \\

/**
 * Called via AJAX - Generates metadata after clicking on a generate metadata button
 * @return void
 */
function ai4seo_generate_metadata() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    require_once(ai4seo_get_includes_ajax_process_path("generate-metadata.php"));
    wp_die();
}


// =========================================================================================== \\

/**
 * Called via AJAX - Generates attachment-attributes after clicking on a generate attachment-attributes button
 * @return void
 */
function ai4seo_generate_attachment_attributes() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    require_once(ai4seo_get_includes_ajax_process_path("generate-attachment-attributes.php"));
    wp_die();
}

// =========================================================================================== \\

/**
 * Called via AJAX - Dismisses a notification by index
 * @return void
 */
function ai4seo_dismiss_notification() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    if (wp_verify_nonce($GLOBALS["ai4seo_ajax_nonce"] ?? "", AI4SEO_GLOBAL_NONCE_IDENTIFIER) === false) {
        ai4seo_send_ajax_error(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'ai-for-seo'), 12109831);
        return;
    }

    // get the notification index
    $notification_index = sanitize_key($_POST["ai4seo_notification_index"] ?? '');

    if (empty($notification_index)) {
        ai4seo_send_ajax_error(esc_html__("Invalid notification index.", "ai-for-seo"), 16109825);
        return;
    }

    // mark the notification as dismissed
    $result = ai4seo_mark_notification_as_dismissed($notification_index);

    if ($result) {
        ai4seo_send_ajax_success();
    } else {
        ai4seo_send_ajax_error(esc_html__("Failed to dismiss notification.", "ai-for-seo"), 17109825);
    }
}

// =========================================================================================== \\

/**
 * Called via AJAX - Shows the terms of service
 * @return void
 */
function ai4seo_show_terms_of_service() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    ob_start();
    $latest_tos_and_toc_and_pp_version = ai4seo_get_latest_tos_and_toc_and_pp_version();

    // headline
    echo "<center>";
            ai4seo_echo_wp_kses(ai4seo_get_sooz_logo_image_tag());
            echo "<h1>" . esc_html(__("Terms of Service", "ai-for-seo")) . "</h1>";
        ai4seo_echo_wp_kses(ai4seo_get_tos_toc_and_pp_accepted_time_output());
        echo " ";
    echo "</center><br>";

    echo "<div class='ai4seo-tos-version-number'>" . esc_html($latest_tos_and_toc_and_pp_version) . "</div>";
    ai4seo_echo_wp_kses(get_tos_content());
    $content = ob_get_clean();

    ai4seo_send_ajax_success($content, null, true);
}

// =========================================================================================== \\

/**
 * Called via AJAX - Imports possible nextgen gallery images to the posts table using our own post_type
 * @return void
 */
/**
 * Import NextGen Gallery images into AI4SEO custom post type attachments.
 *
 * @return void
 */
function ai4seo_import_nextgen_gallery_images() {
    global $wpdb;

    // Make sure that this function is only called once.
    if ( ! ai4seo_singleton( __FUNCTION__ ) ) {
        return;
    }

    // Read all pid's of wp_ngg_pictures.
    $nextgen_gallery_images = $wpdb->get_results(
        "SELECT `pid`, `image_slug`, `galleryid`, `filename`, `description`, `alttext`, `imagedate`, `updated_at`
        FROM " . esc_sql( $wpdb->prefix ) . "ngg_pictures
        WHERE `pid` > 0",
        ARRAY_A
    );

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321695, 'Database error: ' . $wpdb->last_error, true);
        ai4seo_send_ajax_error( esc_html__( 'Database error while reading NextGen Gallery images.', 'ai-for-seo' ), 784322906 );
    }

    if ( ! $nextgen_gallery_images ) {
        ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery Images found', 'ai-for-seo' ), 18147525 );
    }

    // Find all distinct galleryid's.
    $nextgen_gallery_image_gallery_ids = array_column( $nextgen_gallery_images, 'galleryid' );

    if ( ! $nextgen_gallery_image_gallery_ids ) {
        ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery galleries found', 'ai-for-seo' ), 19147525 );
    }

    // Normalize gallery IDs to integers, unique, no zeros.
    $nextgen_gallery_image_gallery_ids = array_map( 'absint', $nextgen_gallery_image_gallery_ids );
    $nextgen_gallery_image_gallery_ids = array_filter( array_unique( $nextgen_gallery_image_gallery_ids ) );

    $nextgen_gallery_gallery_paths = array();

    $gallery_ids_chunk_size = ai4seo_get_database_chunk_size();
    $gallery_ids_chunks     = array_chunk( $nextgen_gallery_image_gallery_ids, $gallery_ids_chunk_size );

    foreach ( $gallery_ids_chunks as $this_gallery_ids_chunk ) {
        if ( ! $this_gallery_ids_chunk ) {
            continue;
        }

        $placeholders = implode( ',', array_fill( 0, count( $this_gallery_ids_chunk ), '%d' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $placeholders is built from %d only.
        $nextgen_gallery_galleries_temp = $wpdb->get_results( $wpdb->prepare("SELECT `gid`, `path` FROM " . esc_sql( $wpdb->prefix ) . "ngg_gallery WHERE `gid` IN ($placeholders)", $this_gallery_ids_chunk), ARRAY_A );

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321696, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error( esc_html__( 'Database error while reading NextGen Gallery galleries.', 'ai-for-seo' ), 784322907 );
        }

        if ( $nextgen_gallery_galleries_temp ) {
            foreach ( $nextgen_gallery_galleries_temp as $this_nextgen_gallery_image_gallery_paths_temp_entry ) {
                $this_nextgen_gallery_gallery_id   = absint( $this_nextgen_gallery_image_gallery_paths_temp_entry['gid'] );
                $this_nextgen_gallery_gallery_path = sanitize_text_field( $this_nextgen_gallery_image_gallery_paths_temp_entry['path'] );

                if ( $this_nextgen_gallery_gallery_id > 0 && $this_nextgen_gallery_gallery_path !== '' ) {
                    $nextgen_gallery_gallery_paths[ $this_nextgen_gallery_gallery_id ] = $this_nextgen_gallery_gallery_path;
                }
            }
        }
    }

    if ( ! $nextgen_gallery_gallery_paths ) {
        ai4seo_send_ajax_error( esc_html__( 'No NextGen Gallery gallery paths found', 'ai-for-seo' ), 20147525 );
    }

    // Reformat to array(pid => array(entry), ...).
    $nextgen_gallery_images = array_column( $nextgen_gallery_images, null, 'pid' );

    // Get the already imported pids from wp_posts where type is AI4SEO_NEXTGEN_GALLERY_POST_TYPE.
    $already_imported_nextgen_gallery_image_pids = $wpdb->get_results(
        "SELECT post_parent
        FROM " . esc_sql( $wpdb->posts ) . "
        WHERE `post_type` = '" . esc_sql( AI4SEO_NEXTGEN_GALLERY_POST_TYPE ) . "'",
        ARRAY_A
    );

    if ( $wpdb->last_error ) {
        ai4seo_debug_message(984321697, 'Database error: ' . $wpdb->last_error, true);
        ai4seo_send_ajax_error( esc_html__( 'Database error while reading imported NextGen Gallery images.', 'ai-for-seo' ), 784322908 );
    }

    if ( $already_imported_nextgen_gallery_image_pids ) {
        $already_imported_nextgen_gallery_image_pids = array_map(
            'absint',
            array_column( $already_imported_nextgen_gallery_image_pids, 'post_parent' )
        );
    } else {
        $already_imported_nextgen_gallery_image_pids = array();
    }

    // Go through $nextgen_gallery_images, build guid and insert into wp_posts.
    foreach ( $nextgen_gallery_images as $this_nextgen_gallery_image ) {
        $this_nextgen_gallery_image_pid        = absint( $this_nextgen_gallery_image['pid'] );
        $this_nextgen_gallery_image_gallery_id = absint( $this_nextgen_gallery_image['galleryid'] );

        // Check if pid is already imported.
        if ( in_array( $this_nextgen_gallery_image_pid, $already_imported_nextgen_gallery_image_pids, true ) ) {
            continue;
        }

        // Check if gallery id is valid.
        if ( ! isset( $nextgen_gallery_gallery_paths[ $this_nextgen_gallery_image_gallery_id ] ) ) {
            continue;
        }

        $this_nextgen_gallery_gallery_path = $nextgen_gallery_gallery_paths[ $this_nextgen_gallery_image_gallery_id ];

        // Build guid.
        $this_website_url             = get_site_url();
        $this_nextgen_gallery_image_guid = untrailingslashit( $this_website_url ) . trailingslashit( $this_nextgen_gallery_gallery_path ) . $this_nextgen_gallery_image['filename'];
        $this_image_mime_type         = ai4seo_get_mime_type_from_url( $this_nextgen_gallery_image_guid );

        // Fallback to jpeg, as this information is not technically required.
        if ( ! $this_image_mime_type ) {
            $this_image_mime_type = 'image/jpeg';
        }

        // Insert into wp_posts.
        $wpdb->insert(
            $wpdb->posts,
            array(
                'post_title'        => sanitize_text_field( $this_nextgen_gallery_image['image_slug'] ),
                'post_name'         => sanitize_text_field( $this_nextgen_gallery_image['image_slug'] ),
                'post_content'      => sanitize_text_field( $this_nextgen_gallery_image['description'] ),
                'post_excerpt'      => sanitize_text_field( $this_nextgen_gallery_image['alttext'] ),
                'post_type'         => AI4SEO_NEXTGEN_GALLERY_POST_TYPE,
                'post_status'       => 'publish',
                'post_mime_type'    => sanitize_text_field( $this_image_mime_type ),
                'post_parent'       => $this_nextgen_gallery_image_pid,
                'guid'              => esc_url( $this_nextgen_gallery_image_guid ),
                'post_date'         => date( 'Y-m-d H:i:s', strtotime( $this_nextgen_gallery_image['imagedate'] ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'post_date_gmt'     => ai4seo_gmdate( 'Y-m-d H:i:s', strtotime( $this_nextgen_gallery_image['imagedate'] ) ),
                'post_modified'     => date( 'Y-m-d H:i:s', absint( $this_nextgen_gallery_image['updated_at'] ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'post_modified_gmt' => ai4seo_gmdate( 'Y-m-d H:i:s', absint( $this_nextgen_gallery_image['updated_at'] ) ),
            )
        );

        // Check for errors.
        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321665, 'Database error: ' . $wpdb->last_error, true);
            ai4seo_send_ajax_error(
                sprintf(
                    /* translators: NextGen Gallery picture ID */
                    esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
                    $this_nextgen_gallery_image_pid
                ),
                21147525
            );
        }

        // Get added post id.
        $this_new_post_id = absint( $wpdb->insert_id );

        // Add _wp_attachment_image_alt post meta for the alt text too.
        if ( ! empty( $this_nextgen_gallery_image['alttext'] ) ) {
            $wpdb->insert(
                $wpdb->postmeta,
                array(
                    'post_id'    => $this_new_post_id,
                    'meta_key'   => '_wp_attachment_image_alt',
                    'meta_value' => sanitize_text_field( $this_nextgen_gallery_image['alttext'] ),
                )
            );

            // Check for errors.
            if ( $wpdb->last_error ) {
                ai4seo_debug_message(984321666, 'Database error: ' . $wpdb->last_error, true);
                ai4seo_send_ajax_error(
                    sprintf(
                        /* translators: NextGen Gallery picture ID */
                        esc_html__( 'Could not import NextGen Gallery image with pid %s', 'ai-for-seo' ),
                        $this_nextgen_gallery_image_pid
                    ),
                    22147525
                );
            }
        }
    }

    ai4seo_send_ajax_success();
}


// ___________________________________________________________________________________________ \\
// ==== SETTINGS ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Retrieve all settings
 * @return array
 */
function ai4seo_get_all_settings(): array {
    global $ai4seo_settings;
    return $ai4seo_settings;
}

// =========================================================================================== \\

/**
 * Retrieve value of a setting
 * @param string $setting_name The name of the setting
 */
function ai4seo_get_setting(string $setting_name) {
    global $ai4seo_settings;
    global $ai4seo_are_settings_initialized;

    if (ai4seo_prevent_loops(__FUNCTION__, 5, 99999)) {
        ai4seo_debug_message(739593453, 'Prevented loop', true);
        return '';
    }

    if (!$ai4seo_are_settings_initialized) {
        ai4seo_init_settings();
    }

    if (!$ai4seo_are_settings_initialized) {
        ai4seo_debug_message(7122824, 'Settings are not initialized.', true);
        return "";
    }

    // Make sure that $setting_name-parameter has content
    if (!$setting_name) {
        ai4seo_debug_message(8122824, 'Setting name is empty.', true);
        return "";
    }

    // Check if the $setting_name-parameter exists in settings-array
    if (!isset($ai4seo_settings[$setting_name])) {
        return AI4SEO_DEFAULT_SETTINGS[$setting_name] ?? "";
    }

    return $ai4seo_settings[$setting_name];
}

// =========================================================================================== \\

/**
 * Update value a setting
 * @return bool True if the setting was updated successfully, false if not
 */
function ai4seo_update_setting(string $setting_name, $new_setting_value): bool {
    global $ai4seo_settings;
    global $ai4seo_are_settings_initialized;

    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(341531855, 'Prevented loop', true);
        return false;
    }

    if (!$ai4seo_are_settings_initialized) {
        ai4seo_init_settings();
    }

    if (!$ai4seo_are_settings_initialized) {
        ai4seo_debug_message(5122824, 'Settings are not initialized.', true);
        return false;
    }

    // Make sure that the new value of the setting is valid
    if (!ai4seo_validate_setting_value($setting_name, $new_setting_value)) {
        ai4seo_debug_message(9122824, 'Invalid setting value for setting "' . $setting_name . '"', true);
        return false;
    }

    // no change at all?
    if ($ai4seo_settings[$setting_name] == $new_setting_value) {
        return true;
    }

    // Overwrite entry in $ai4seo_settings-array
    $ai4seo_settings[$setting_name] = $new_setting_value;

    return ai4seo_push_local_setting_changes_to_database();
}

// =========================================================================================== \\

/**
 * Update values of given settings
 * @param $setting_changes array An array of settings to update
 * @return bool True if the setting was updated successfully, false if not
 */
function ai4seo_bulk_update_settings(array $setting_changes): bool {
    global $ai4seo_settings;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(796348328, 'Prevented loop', true);
        return false;
    }

    $ai4seo_new_settings = $ai4seo_settings;

    foreach ($setting_changes AS $this_setting_name => $this_setting_value) {
        // Make sure that the new value of the setting is valid
        if (!ai4seo_validate_setting_value($this_setting_name, $this_setting_value)) {
            ai4seo_debug_message(40146824, 'Invalid setting value for setting "' . $this_setting_name . '"', true);
            return false;
        }

        // Overwrite entry in $ai4seo_settings-array
        $ai4seo_new_settings[$this_setting_name] = $this_setting_value;
    }

    $ai4seo_settings = $ai4seo_new_settings;

    return ai4seo_push_local_setting_changes_to_database();
}

// =========================================================================================== \\

/**
 * Function to update the wp_options table with the current settings, by removing default values
 * @return bool
 */
function ai4seo_push_local_setting_changes_to_database(): bool {
    global $ai4seo_settings;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(648276361, 'Prevented loop', true);
        return false;
    }

    $ai4seo_settings_copy = $ai4seo_settings;

    foreach ($ai4seo_settings_copy AS $ai4seo_setting_name => $ai4seo_setting_value) {
        // if the setting is equal to the default setting, set it to null to prevent overhead
        if (isset(AI4SEO_DEFAULT_SETTINGS[$ai4seo_setting_name]) && $ai4seo_setting_value == AI4SEO_DEFAULT_SETTINGS[$ai4seo_setting_name]) {
            unset($ai4seo_settings_copy[$ai4seo_setting_name]);
        }
    }

    // Save updated settings to database
    return ai4seo_update_option(AI4SEO_SETTINGS_OPTION_NAME, $ai4seo_settings_copy, true);
}

// =========================================================================================== \\

/**
 * Validate value of a setting
 * @return bool True if the value is valid, false if not
 */
function ai4seo_validate_setting_value(string $setting_name, $setting_value): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(596454676, 'Prevented loop', true);
        return false;
    }

    switch ($setting_name) {
        case AI4SEO_SETTING_BULK_GENERATION_DURATION:
            // cast to int
            $setting_value = (int) $setting_value;

            // integer between 10 and 300
            return $setting_value >= 10 && $setting_value <= 300;

        case AI4SEO_SETTING_META_TAG_OUTPUT_MODE:
            $ai4seo_setting_meta_tag_output_mode_allowed_values = ai4seo_get_setting_meta_tag_output_mode_allowed_values();
            return in_array($setting_value, array_keys($ai4seo_setting_meta_tag_output_mode_allowed_values));

        case AI4SEO_SETTING_DEBUG_OUTPUT_MODE:
            $ai4seo_debug_output_mode_options = ai4seo_get_debug_output_mode_options();

            return is_string($setting_value)
                && array_key_exists($setting_value, $ai4seo_debug_output_mode_options);

        case AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE:
        case AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION:
        case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE:
        case AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION:
        case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE:
        case AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION:
            $fallback_to_this_metadata_identifier = ai4seo_get_fallback_metadata_identifier_by_setting_name($setting_name);

            if (!$fallback_to_this_metadata_identifier) {
                return false;
            }

            $allowed_fallback_values = ai4seo_get_metadata_fallback_allowed_values($fallback_to_this_metadata_identifier);

            return is_string($setting_value) && array_key_exists($setting_value, $allowed_fallback_values);

        case AI4SEO_SETTING_ALLOWED_USER_ROLES:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(45146824, 'Invalid setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            $allowed_user_roles = ai4seo_get_all_possible_user_roles();
            $allowed_user_role_identifiers = array_keys($allowed_user_roles);

            // check if all values are proper user roles
            foreach ($setting_value as $user_role_identifier) {
                if (!in_array($user_role_identifier, $allowed_user_role_identifiers)) {
                    ai4seo_debug_message(44146824, 'Invalid user role in the allowed user roles.', true);
                    return false;
                }
            }

            // Make sure that the administrator-role exists in the array
            if (!in_array("administrator", $setting_value)) {
                ai4seo_debug_message(43146824, 'Administrator role is missing in the allowed user roles', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_DISABLED_POST_TYPES:
            if (!is_array($setting_value)) {
                ai4seo_debug_message(311815240, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            foreach ($setting_value as $post_type) {
                if (!is_string($post_type) || !preg_match("/^[a-zA-Z0-9_-]+$/", $post_type)) {
                    ai4seo_debug_message(321815240, 'Invalid post type in the disabled post types setting.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(1188824, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            // Make sure the keys consist of alphanumeric strings, with - and _ allowed and the values should be "1" or "0" only
            foreach ($setting_value as $value) {
                if (!preg_match("/^[a-zA-Z0-9_-]+$/", $value)) {
                    ai4seo_debug_message(2188824, 'Invalid value in the enabled bulk generations post types setting.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_BULK_GENERATION_ORDER:
            if (!defined('AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS') || !in_array($setting_value, AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS)) {
                ai4seo_debug_message(2911171224, 'Invalid value in the bulk generations order setting "' . $setting_value . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER:
            if (!defined('AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS') || !in_array($setting_value, AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS)) {
                ai4seo_debug_message(3211171224, 'Invalid value in the automated generations new or existing filter setting.', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS:
            if (!is_array($setting_value)) {
                ai4seo_debug_message(161523924, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            $third_party_seo_plugin_details = ai4seo_get_third_party_seo_plugin_details();

            $allowed_third_party_seo_plugin_identifier = array_keys($third_party_seo_plugin_details);

            foreach ($setting_value as $key => $value) {
                if (!is_string($value) || !preg_match("/^[a-zA-Z0-9_-]+$/", $value)) {
                    ai4seo_debug_message(171523924, 'Invalid value in the apply changes to third party seo plugin setting.', true);
                    return false;
                }

                if (!in_array($value, $allowed_third_party_seo_plugin_identifier)) {
                    ai4seo_debug_message(181523924, 'Invalid third party seo plugin name in the apply changes to third party seo plugin setting.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE:
        case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE:
            // Make sure that the new setting-value is a string
            if (!is_string($setting_value)) {
                ai4seo_debug_message(261016824, 'Setting value for setting "' . $setting_name . '" is not a string.', true);
                return false;
            }

            $generation_language_options = array_keys(ai4seo_get_translated_generation_language_options());

            // Make sure that the new setting-value is a valid language
            if ($setting_value !== "auto" && !in_array($setting_value, $generation_language_options)) {
                ai4seo_debug_message(271016824, 'Invalid language in the generation language setting: "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_ACTIVE_META_TAGS:
        case AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA:
        case AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(421728824, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            if (!defined('AI4SEO_METADATA_DETAILS')) {
                return false;
            }

            $all_meta_tags = array_keys(AI4SEO_METADATA_DETAILS);

            // Make sure that the new setting-value is a valid meta tag
            foreach ($setting_value as $meta_tag) {
                if (!in_array($meta_tag, $all_meta_tags)) {
                    ai4seo_debug_message(431728824, 'Invalid meta tag in the visible meta tags setting: "' . $setting_name . '"', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES:
        case AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(101424924, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            if (!defined("AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS")) {
                return false;
            }

            $all_attachment_attributes = array_keys(AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS);

            // Make sure that the new setting-value is a valid attachment attribute
            foreach ($setting_value as $attachment_attribute) {
                if (!in_array($attachment_attribute, $all_attachment_attributes)) {
                    ai4seo_debug_message(111424924, 'Invalid attachment attribute in the overwrite existing attachment attributes setting: "' . $setting_name . '" is not an array.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS:
            return in_array($setting_value, array("show", "hide"));

        case AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES:
        case AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES:
        case AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION:
        case AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION:
        case AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS:
        case AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE:
        case AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES:
        case AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE:
        case AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE:
        case AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION:
        case AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION:
            // check for boolean
            return is_bool($setting_value);

        case AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA:
            $include_product_price_in_metadata_allowed_values = ai4seo_get_setting_include_product_price_in_metadata_allowed_values();

            return is_string($setting_value)
                && array_key_exists($setting_value, $include_product_price_in_metadata_allowed_values);

        case AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA:
            $focus_keyphrase_behavior_options = ai4seo_get_focus_keyphrase_behavior_options();

            return is_string($setting_value)
                && array_key_exists($setting_value, $focus_keyphrase_behavior_options);

        case AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE:
            $render_level_title_injection_allowed_values = ai4seo_get_setting_render_level_title_injection_allowed_values();

            // check for valid allowed value
            return is_string($setting_value) && array_key_exists($setting_value, $render_level_title_injection_allowed_values);


        case AI4SEO_SETTING_METADATA_PREFIXES:
        case AI4SEO_SETTING_METADATA_SUFFIXES:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(421728825, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            foreach ($setting_value as $key => $value) {
                if (!is_string($key) || !preg_match("/^[a-zA-Z0-9_-]+$/", $key)) {
                    ai4seo_debug_message(274714041, 'Invalid key in the metadata prefix / suffix setting.', true);
                    return false;
                }

                if (!is_string($value)) {
                    ai4seo_debug_message(274714042, 'Invalid value in the metadata prefix / suffix setting.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES:
        case AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES:
            // Make sure that the new setting-value is an array
            if (!is_array($setting_value)) {
                ai4seo_debug_message(421728826, 'Setting value for setting "' . $setting_name . '" is not an array.', true);
                return false;
            }

            foreach ($setting_value as $key => $value) {
                if (!is_string($key) || !preg_match("/^[a-zA-Z0-9_-]+$/", $key)) {
                    ai4seo_debug_message(274714043, 'Invalid key in the attachment-attribute prefix / suffix setting.', true);
                    return false;
                }

                if (!is_string($value)) {
                    ai4seo_debug_message(274714044, 'Invalid value in the attachment-attribute prefix / suffix setting.', true);
                    return false;
                }
            }

            return true;

        case AI4SEO_SETTING_ENABLE_INCOGNITO_MODE:
        case AI4SEO_SETTING_ENABLE_WHITE_LABEL:
            // Make sure that setting-value is 0 or 1
            if ($setting_value != "0" && $setting_value != "1") {
                ai4seo_debug_message(385825154, 'Invalid value for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_INCOGNITO_MODE_USER_ID:
            // Make sure that setting-value is 0 or numeric
            if ($setting_value != "0" && !is_numeric($setting_value)) {
                ai4seo_debug_message(385825155, 'Invalid value for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;


        case AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME:
            // default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_PLUGIN_NAME] if not set
            if (!$setting_value) {
                $setting_value = AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME];
            }

            if (!is_string($setting_value) || ai4seo_mb_strlen($setting_value) < 3 || ai4seo_mb_strlen($setting_value) > 100) {
                ai4seo_debug_message(385825156, 'Invalid value in the plugin-name for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION:
            // default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_PLUGIN_DESCRIPTION] if not set
            if (!$setting_value) {
                $setting_value = AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION];
            }

            if (!is_string($setting_value) || ai4seo_mb_strlen($setting_value) < 3 || ai4seo_mb_strlen($setting_value) > 140) {
                ai4seo_debug_message(385825157, 'Invalid value in the plugin-description for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_ADD_GENERATOR_HINTS:
            // Make sure that setting-value is 0 or 1
            if ($setting_value != "0" && $setting_value != "1") {
                ai4seo_debug_message(385825158, 'Invalid value for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT:
            // default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_SOURCE_CODE_NOTES_CONTENT_START] if not set
            if (!$setting_value) {
                $setting_value = AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT];
            }

            if (!is_string($setting_value) || ai4seo_mb_strlen($setting_value) < 3 || ai4seo_mb_strlen($setting_value) > 250) {
                ai4seo_debug_message(385825159, 'Invalid value in the source-code-notes-content-start for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT:
            // default to AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_SOURCE_CODE_NOTES_CONTENT_END] if not set
            if (!$setting_value) {
                $setting_value = AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT];
            }

            if (!is_string($setting_value) || ai4seo_mb_strlen($setting_value) < 3 || ai4seo_mb_strlen($setting_value) > 250) {
                ai4seo_debug_message(385825160, 'Invalid value in the source-code-notes-content-end for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_PREFERRED_CURRENCY:
            $allowed_currencies = ai4seo_get_allowed_currencies();

            if (!in_array(strtoupper($setting_value), $allowed_currencies)) {
                ai4seo_debug_message(341016325, 'Invalid currency for setting "' . $setting_name . '"', true);
                return false;
            }

            return true;

        case AI4SEO_SETTING_PAYG_ENABLED:
            return is_bool($setting_value);

        case AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID:
            // allow empty value for the setting, which means that the user is not using a credits pack
            if ($setting_value === '') {
                return true;
            }

            // any string starting with "price_" is allowed here, but we double-check with the available credits packs
            return preg_match("/^price_[a-zA-Z0-9]+$/", $setting_value);

        case AI4SEO_SETTING_PAYG_DAILY_BUDGET:
        case AI4SEO_SETTING_PAYG_MONTHLY_BUDGET:
            return is_numeric($setting_value) && $setting_value >= 0;

        case AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE:
            $setting_value = (int) $setting_value;

            return in_array($setting_value, AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS, true);

        case AI4SEO_SETTING_IMAGE_UPLOAD_METHOD:
            $allowed_values = array("auto", "url", "base64");
            return in_array($setting_value, $allowed_values);

        default:
            return false;
    }
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the debug output mode setting.
 *
 * @return array
 */
function ai4seo_get_debug_output_mode_options(): array {
    return array(
        'none' => esc_html__("Disable debug output", "ai-for-seo"),
        'error_log' => esc_html__("Send to PHP/WP debug log (respects WP_DEBUG_LOG)", "ai-for-seo"),
        'file' => esc_html__("Write to uploads/ai-for-seo-debug.log", "ai-for-seo"),
        'database' => esc_html__("Store in the database (see 'Debug message log' below)", "ai-for-seo"),
        'notice' => esc_html__("Show as an admin notice", "ai-for-seo"),
        'print_r' => esc_html__("Display inline", "ai-for-seo"),
    );
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the setting for the meta tag output modes
 * @return array return the allowed values for the setting for the meta tag output modes
 */
function ai4seo_get_setting_meta_tag_output_mode_allowed_values(): array {
    return array(
        "disable" => sprintf(
            /* translators: %s: plugin name */
            esc_html__("Disable '%s' Meta Tags", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        ),
        "force" => sprintf(
            /* translators: %s: plugin name */
            esc_html__("Force '%s' Meta Tags", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        ),
        "replace" => esc_html__("Replace Existing Meta Tags", "ai-for-seo"),
        "complement" => esc_html__("Complement Existing Meta Tags", "ai-for-seo"),
    );
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the render level image title injection setting
 * @return array return the allowed values for the render level image title injection setting
 */
function ai4seo_get_setting_render_level_title_injection_allowed_values(): array {
    return array(
        "disabled" => esc_html__("Disabled", "ai-for-seo"),
        "inject_title" => esc_html__("Inject image title", "ai-for-seo"),
        "inject_alt_text" => esc_html__("Inject alt text", "ai-for-seo"),
        "inject_caption" => esc_html__("Inject caption", "ai-for-seo"),
        "inject_description" => esc_html__("Inject image description", "ai-for-seo"),
    );
}

// =========================================================================================== \\

/**
 * Returns the allowed values for the WooCommerce price inclusion setting.
 *
 * @return array
 */
function ai4seo_get_setting_include_product_price_in_metadata_allowed_values(): array {
    return array(
        'never' => esc_html__("Never include WooCommerce price", "ai-for-seo"),
        'fixed' => esc_html__("Fixed price (store current amount)", "ai-for-seo"),
        'dynamic' => esc_html__("Dynamic placeholder (updates at render time)", "ai-for-seo"),
    );
}

// =========================================================================================== \\

/**
 * Returns the options for the Focus Keyphrase behavior when metadata already exists.
 *
 * @return array
 */
function ai4seo_get_focus_keyphrase_behavior_options(): array {
    return array(
        AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP => esc_html__("Skip focus keyphrase generation", "ai-for-seo"),
        AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_GENERATE_KEYPHRASE => esc_html__("Generate focus keyphrase only", "ai-for-seo"),
        AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE => esc_html__("Regenerate metadata (recommended)", "ai-for-seo"),
    );
}


// =========================================================================================== \

/**
 * Returns the options for query ID chunk sizes used for batched database lookups.
 *
 * @return array
 */
function ai4seo_get_query_ids_chunk_size_options(): array {
    $options = array();

    foreach (AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS as $chunk_size) {
        $chunk_size = (int) $chunk_size;

        if ($chunk_size <= 0) {
            continue;
        }

        $options[$chunk_size] = number_format_i18n($chunk_size);
    }

    return $options;
}

// =========================================================================================== \

/**
 * Returns the validated query ID chunk size setting.
 *
 * @return int
 */
function ai4seo_get_database_chunk_size(): int {
    $chunk_size = (int) ai4seo_get_setting(AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE);

    if (!in_array($chunk_size, AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS, true)) {
        $chunk_size = (int) AI4SEO_DEFAULT_SETTINGS[AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE];
    }

    return $chunk_size;
}


// endregion
// ___________________________________________________________________________________________ \\
// region BULK GENERATION / SEO AUTOPILOT ======================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Check if the auto generation is enabled for a specific context
 * @param $post_type string The context of the auto generation (post, page, product, attachment, keyphrase etc.)
 * @return bool True if the auto generation is enabled, false if not
 */
function ai4seo_is_bulk_generation_enabled(string $post_type): bool {
    $enabled_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES) ?: array();

    return is_array($enabled_bulk_generation_post_types) && in_array($post_type, $enabled_bulk_generation_post_types);
}

// =========================================================================================== \\

/**
 * Check if any auto generation is enabled
 * @return bool True if any auto generation is enabled, false if not
 */
function ai4seo_is_any_bulk_generation_enabled(): bool {
    $enabled_bulk_generations_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES) ?: array();

    return count($enabled_bulk_generations_post_types) > 0;
}


// endregion
// ___________________________________________________________________________________________ \\
// region ENVIRONMENTAL VARIABLES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to retrieve all environmental variables from database
 * @param bool $use_cache Should we use the cache
 * @return array All environmental variables
 */
function ai4seo_read_all_environmental_variables(bool $use_cache = true): array {
    global $ai4seo_environmental_variables;

    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(690812093, 'Prevented loop', true);
        return array();
    }

    if (!isset($ai4seo_environmental_variables) || !$ai4seo_environmental_variables) {
        $ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
    }

    // get cached version
    if ($use_cache && $ai4seo_environmental_variables !== AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES) {
        return $ai4seo_environmental_variables;
    }

    $current_environmental_variables = ai4seo_get_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, false, !$use_cache);

    // nothing in our database? fallback to known/default environmental variables
    if (!is_array($current_environmental_variables) || !$current_environmental_variables) {
        return $ai4seo_environmental_variables;
    }

    // go through each environmental variable and check if it is valid
    foreach ($ai4seo_environmental_variables as $environmental_variable_name => $environmental_variable_value) {
        // set default if not set
        if (!isset($current_environmental_variables[$environmental_variable_name])) {
            $current_environmental_variables[$environmental_variable_name] = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
        }

        // validate
        if (!ai4seo_validate_environmental_variable_value($environmental_variable_name, $current_environmental_variables[$environmental_variable_name])) {
            ai4seo_debug_message(2317181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true);
            $current_environmental_variables[$environmental_variable_name] = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
        }

        // set $ai4seo_environmental_variables
        $ai4seo_environmental_variables[$environmental_variable_name] = $current_environmental_variables[$environmental_variable_name];
    }

    // include ttl companion entries as runtime-accepted environmental variables
    foreach ($current_environmental_variables as $environmental_variable_name => $environmental_variable_value) {
        if (!ai4seo_is_environmental_variable_ttl_name((string) $environmental_variable_name)) {
            continue;
        }

        if (!ai4seo_validate_environmental_variable_value($environmental_variable_name, $environmental_variable_value)) {
            ai4seo_debug_message(2317181025, 'Invalid TTL value for environmental variable "' . $environmental_variable_name . '"', true);
            continue;
        }

        $ai4seo_environmental_variables[$environmental_variable_name] = (int) $environmental_variable_value;
    }

    return $ai4seo_environmental_variables;
}

// =========================================================================================== \\

/**
 * Function to retrieve a specific environmental variable
 * @param string $environmental_variable_name The name of the environmental variable
 * @param bool $use_cache Should we use the cache
 * @return mixed The value of the environmental variable
 */
function ai4seo_read_environmental_variable(string $environmental_variable_name, bool $use_cache = true) {
    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(232735921, 'Prevented loop', true);
        return null;
    }

    // Make sure that $environmental_variable_name-parameter has content
    if (!$environmental_variable_name) {
        ai4seo_debug_message(515181024, 'Environmental variable name is empty.', true);
        return null;
    }

    // check for the default value
    if (!isset(AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
        ai4seo_debug_message(56187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true);
        return null;
    }

    $current_environmental_variables = ai4seo_read_all_environmental_variables($use_cache);

    // Check if the $environmental_variable_name-parameter exists in environmental variables-array
    if (isset($current_environmental_variables[$environmental_variable_name])) {
        return $current_environmental_variables[$environmental_variable_name];
    } else {
        return AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
    }
}

// =========================================================================================== \\

/**
 * Function to update a specific environmental variable
 * @param string $environmental_variable_name The name of the environmental variable
 * @param mixed $new_environmental_variable_value The new value of the environmental variable
 * @param bool $use_cache Should we use the cache
 * @param int $cache_ttl Cache TTL in seconds. 0 disables TTL companion updates.
 * @return bool True if the environmental variable was updated successfully, false if not
 */
function ai4seo_update_environmental_variable(string $environmental_variable_name, $new_environmental_variable_value, bool $use_cache = true, int $cache_ttl = 0): bool {
    global $ai4seo_environmental_variables;

    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(726736127, 'Prevented loop', true);
        return false;
    }

    if (ai4seo_is_environmental_variable_ttl_name($environmental_variable_name)) {
        ai4seo_debug_message(141224226, 'Attempted to update TTL companion variable via ai4seo_update_environmental_variable(): "' . $environmental_variable_name . '"', true);
        return false;
    }

    if (!isset(AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
        ai4seo_debug_message(51187825, 'Unknown environmental variable name: "' . $environmental_variable_name . '"', true);
        return false;
    }

    // Make sure that the new value of the environmental variable is valid
    if (!ai4seo_validate_environmental_variable_value($environmental_variable_name, $new_environmental_variable_value)) {
        ai4seo_debug_message(535181024, 'Invalid value for environmental variable "' . $environmental_variable_name . '"', true);
        return false;
    }

    // sanitize
    $new_environmental_variable_value = ai4seo_deep_sanitize($new_environmental_variable_value);

    // use semaphore to make sure this critical section is thread-safe
    if (!$use_cache) {
        /*if (!ai4seo_acquire_semaphore(__FUNCTION__)) {
            // could not acquire semaphore -> another process is in the critical section -> return
            return false;
        }*/
    }

    // overwrite entry in $current_environmental_variables-array
    $current_environmental_variables = ai4seo_read_all_environmental_variables($use_cache);

    // is same as default value? delete it
    if ($new_environmental_variable_value == AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name]) {
        unset($current_environmental_variables[$environmental_variable_name]);
    } else {
        // no change at all?
        if (isset($current_environmental_variables[$environmental_variable_name])
            && $current_environmental_variables[$environmental_variable_name] == $new_environmental_variable_value) {
            return true;
        }

        $current_environmental_variables[$environmental_variable_name] = $new_environmental_variable_value;
    }

    // if we have a cache TTL, we also update the TTL companion variable to current time + TTL, so that the cache can be properly invalidated after the TTL has expired
    if ($cache_ttl > 0) {
        $ttl_environmental_variable_name = ai4seo_get_environmental_variable_ttl_name($environmental_variable_name);
        $current_environmental_variables[$ttl_environmental_variable_name] = time() + $cache_ttl;
    }

    // no changes made
    if ($ai4seo_environmental_variables == $current_environmental_variables) {
        return true;
    }

    // update the global parameter as well
    $ai4seo_environmental_variables = $current_environmental_variables;

    // Save updated environmental variables to database
    $success = ai4seo_update_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables, false, !$use_cache);

    if (!$use_cache) {
        //ai4seo_release_semaphore(__FUNCTION__);
    }

    return $success;
}

// =========================================================================================== \\

/**
 * Function to delete an environmental variable
 * @param string $environmental_variable_name The name of the environmental variable
 * @return bool True if the environmental variable was deleted successfully, false if not
 */
function ai4seo_delete_environmental_variable(string $environmental_variable_name): bool {
    global $ai4seo_environmental_variables;

    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(912986381, 'Prevented loop', true);
        return false;
    }

    // Make sure that $environmental_variable_name-parameter has content
    if (!$environmental_variable_name) {
        ai4seo_debug_message(491226225, 'Environmental variable name is empty.', true);
        return false;
    }

    // overwrite entry in $current_environmental_variables-array
    $current_environmental_variables = ai4seo_read_all_environmental_variables();

    if (!isset($current_environmental_variables[$environmental_variable_name])) {
        return true;
    }

    // delete the entry
    unset($current_environmental_variables[$environmental_variable_name]);

    // update the class parameter as well
    $ai4seo_environmental_variables = $current_environmental_variables;

    // Save updated environmental variables to database
    return ai4seo_update_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables);
}

// =========================================================================================== \\

/**
 * Deletes all environmental variables
 * @return bool
 */
function ai4seo_delete_all_environmental_variables(): bool {
    global $ai4seo_environmental_variables;

    $ai4seo_environmental_variables = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;

    return ai4seo_delete_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME);
}

// =========================================================================================== \\

/**
 * Bulk update environmental variables.
 *
 * Accepts an associative array of updates like array( 'variable_name' => 'new_value', ... ).
 * Each entry is validated against AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES and
 * ai4seo_validate_environmental_variable_value() before being applied.
 * Values equal to their defaults are removed from the stored overrides.
 *
 * @param array $environmental_variable_updates Associative array: name => value.
 * @return array {
 *     @type bool  $success        True if persisted successfully (or nothing to persist), false on DB write failure.
 *     @type int   $updated_count  Number of variables that changed (added/updated/removed).
 *     @type array $invalid_names  List of names skipped because they are unknown.
 *     @type array $invalid_values List of names skipped because the value was invalid.
 * }
 */
function ai4seo_bulk_update_environmental_variables( array $environmental_variable_updates ): array {
    global $ai4seo_environmental_variables;

    $result = array(
        'success'        => true,
        'updated_count'  => 0,
        'invalid_names'  => array(),
        'invalid_values' => array(),
    );

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(210791927, 'Prevented loop', true);
        return $result;
    }

    // Nothing to do.
    if ( empty( $environmental_variable_updates ) ) {
        return $result;
    }

    // Read current overrides once.
    $current_environmental_variables = ai4seo_read_all_environmental_variables();

    // Iterate all requested updates.
    foreach ( $environmental_variable_updates as $this_name => $this_value ) {
        // Validate variable name against whitelist.
        if ( ! isset( AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) ) {
            // Unknown name. Skip and record.
            $result['invalid_names'][] = $this_name;
            ai4seo_debug_message(2017171025, 'Unknown environmental variable name in bulk update: "' . $this_name . '"', true);
            continue;
        }

        // Validate value using existing validator.
        if ( ! ai4seo_validate_environmental_variable_value( $this_name, $this_value ) ) {
            // Invalid value. Skip and record.
            $result['invalid_values'][] = $this_name;
            ai4seo_debug_message(2117171025, 'Invalid value for environmental variable "' . $this_name . '" in bulk update.', true);
            continue;
        }

        // Sanitize value deeply.
        $this_value = ai4seo_deep_sanitize( $this_value );

        // If equals default, ensure override is removed.
        if ( $this_value == AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) {
            if ( isset( $current_environmental_variables[ $this_name ] ) ) {
                unset( $current_environmental_variables[ $this_name ] );
                $result['updated_count']++;
            }
            continue;
        }

        // If no change vs current override, skip.
        if ( isset( $current_environmental_variables[ $this_name ] )
            && $current_environmental_variables[ $this_name ] == $this_value ) {
            continue;
        }

        // Apply/overwrite override.
        $current_environmental_variables[ $this_name ] = $this_value;
        $result['updated_count']++;
    }

    // If nothing changed, keep success=true and return.
    if ( $ai4seo_environmental_variables == $current_environmental_variables ) {
        return $result;
    }

    // Update the global cache.
    $ai4seo_environmental_variables = $current_environmental_variables;

    // Persist once.
    $did_update = ai4seo_update_option( AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $current_environmental_variables );

    if ( ! $did_update ) {
        // DB write failed. Keep in-memory state but surface failure.
        $result['success'] = false;
        ai4seo_debug_message(2217171025, 'Failed to persist environmental variables in bulk update.', true);
    }

    return $result;
}

// =========================================================================================== \\

/**
 * Validate value of an environmental variable
 * @param string $environmental_variable_name The name of the environmental variable
 * @param mixed $environmental_variable_value The value of the environmental variable
 */
function ai4seo_validate_environmental_variable_value(string $environmental_variable_name, $environmental_variable_value): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 5)) {
        ai4seo_debug_message(705807482, 'Prevented loop', true);
        return false;
    }

    if (ai4seo_is_environmental_variable_ttl_name($environmental_variable_name)) {
        return is_numeric($environmental_variable_value) && (int) $environmental_variable_value >= 0;
    }

    switch ($environmental_variable_name) {
        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION:
            // contains only of numbers and dots
            return is_string($environmental_variable_value) && preg_match("/^[0-9.]+$/", $environmental_variable_value);

        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME:
            // contains only of numbers
            return is_numeric($environmental_variable_value) && $environmental_variable_value >= 0;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE:
            // string with specific allowed values
            return in_array($environmental_variable_value, array("idle", "processing", "completed"));

        case AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT:
            // empty or array with at least name and percentage
            if (empty($environmental_variable_value)) {
                return true;
            }

            if (!is_array($environmental_variable_value) || !isset($environmental_variable_value['name']) || !isset($environmental_variable_value['percentage'])) {
                ai4seo_debug_message(531729725, 'Invalid current discount environmental variable.', true);
                return false;
            }

            // name contains only small letters and "-"
            if (!is_string($environmental_variable_value['name']) || !preg_match("/^[a-z0-9-]+$/", $environmental_variable_value['name'])) {
                ai4seo_debug_message(541729725, 'Invalid discount name in the current discount environmental variable.', true);
                return false;
            }

            // percentage must be int
            if (!is_numeric($environmental_variable_value['percentage']) || $environmental_variable_value['percentage'] < 0 || $environmental_variable_value['percentage'] > 100) {
                ai4seo_debug_message(551729725, 'Invalid percentage in the current discount environmental variable.', true);
                return false;
            }

            // if expire_in is provided, check its integer and between 0 and 99.999.999
            if (isset($environmental_variable_value['expire_in']) && (!is_numeric($environmental_variable_value['expire_in']) || $environmental_variable_value['expire_in'] < 0 || $environmental_variable_value['expire_in'] > 99999999)) {
                ai4seo_debug_message(561729725, 'Invalid expire_in in the current discount environmental variable.', true);
                return false;
            }

            return true;


        case AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED:
            // boolean
            return is_bool($environmental_variable_value);

        case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST:
            // array of strings (containing a-z and -)
            if (!is_array($environmental_variable_value)) {
                return false;
            }

            foreach ($environmental_variable_value as $key => $value) {
                if (!is_string($value) || !preg_match("/^[a-z0-9-]+$/", $value)) {
                    return false;
                }
            }

            return true;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME:
            if (!is_numeric($environmental_variable_value) || $environmental_variable_value < 0) {
                ai4seo_debug_message(5713171224, 'Invalid value in the automated generations new or existing filter reference times setting.', true);
                return false;
            }

            return true;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER:
            return is_bool($environmental_variable_value);

        case AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS:
        case AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES:
            // array of integers >= 0
            if (!is_array($environmental_variable_value)) {
                return false;
            }

            foreach ($environmental_variable_value as $key => $value) {
                if (!is_numeric($value) || $value < 0) {
                    return false;
                }
            }

            return true;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS:
            return in_array($environmental_variable_value, AI4SEO_ALLOWED_PAYG_STATUS);

        case AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE:
            if (!is_array($environmental_variable_value)) {
                return false;
            }

            foreach ($environmental_variable_value as $this_value) {
                if (!is_string($this_value) || sanitize_key($this_value) !== $this_value) {
                    return false;
                }
            }

            return true;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE:
            if (!is_array($environmental_variable_value)) {
                return false;
            }

            foreach ($environmental_variable_value as $this_value) {
                if (!is_numeric($this_value) || (int) $this_value < 0) {
                    return false;
                }
            }

            return true;

        case AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE:
            if (!is_array($environmental_variable_value)) {
                return false;
            }

            foreach ($environmental_variable_value as $this_key => $this_value) {
                if (!is_string($this_key) || $this_key === '') {
                    return false;
                }

                if (!is_numeric($this_value) || (int) $this_value < 0) {
                    return false;
                }
            }

            return true;

        default:
            return false;
    }
}

// =========================================================================================== \\

/**
 * Check if an environmental variable name is a TTL companion variable.
 *
 * @param string $environmental_variable_name
 * @return bool True if the name ends with the TTL suffix, false otherwise.
 */
function ai4seo_is_environmental_variable_ttl_name(string $environmental_variable_name): bool {
    return substr($environmental_variable_name, -strlen(AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX)) === AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}

// =========================================================================================== \\

/**
 * Build ttl companion environmental variable name.
 *
 * @param string $environmental_variable_name
 * @return string
 */
function ai4seo_get_environmental_variable_ttl_name(string $environmental_variable_name): string {
    return $environmental_variable_name . AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX;
}

// =========================================================================================== \
/**
 * Returns true if an environmental variable cache TTL exists and is not expired.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return bool
 */
function ai4seo_is_environmental_variable_cache_available(string $environmental_variable_name): bool {
    if (!isset(AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
        return false;
    }

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(301224226, 'Prevented loop', true);
        return false;
    }

    $ttl_name = ai4seo_get_environmental_variable_ttl_name($environmental_variable_name);
    $all_environmental_variables = ai4seo_read_all_environmental_variables();

    if (!isset($all_environmental_variables[$ttl_name]) || !is_numeric($all_environmental_variables[$ttl_name])) {
        return false;
    }

    return (int) $all_environmental_variables[$ttl_name] > time();
}

// =========================================================================================== \\

/**
 * Invalidate one environmental variable cache by removing its ttl companion value.
 *
 * @param string $environmental_variable_name The base environmental variable name.
 * @return void
 */
function ai4seo_invalidate_environmental_variable_cache(string $environmental_variable_name): void {
    if (!isset(AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
        return;
    }

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(311224226, 'Prevented loop', true);
        return;
    }

    ai4seo_delete_environmental_variable(ai4seo_get_environmental_variable_ttl_name($environmental_variable_name));
}

// =========================================================================================== \\

/**
 * Invalidate all environmental variable caches by removing every __ttl_time entry.
 *
 * @return void
 */
function ai4seo_invalidate_all_environmental_variable_caches(): void {
    global $ai4seo_environmental_variables;

    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(321224226, 'Prevented loop', true);
        return;
    }

    $all_environmental_variables = ai4seo_read_all_environmental_variables(false);
    $did_change = false;

    foreach ($all_environmental_variables as $this_name => $unused_value) {
        if (!ai4seo_is_environmental_variable_ttl_name((string) $this_name)) {
            continue;
        }

        unset($all_environmental_variables[$this_name]);
        $did_change = true;
    }

    if (!$did_change) {
        return;
    }

    $ai4seo_environmental_variables = $all_environmental_variables;
    ai4seo_update_option(AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME, $all_environmental_variables);
}

// =========================================================================================== \\

/**
 * Returns environmental variable => action map for cache invalidation.
 *
 * @return array<string, array<int, string>>
 */
function ai4seo_get_environmental_variable_to_action_cache_invalidation_map(): array {
    return array(
        AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES => array(
            'save_post',
            'delete_post',
            'deleted_post',
            'trashed_post',
            'untrashed_post',
            'transition_post_status',
        ),
        AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE => array(
            'save_post',
            'delete_post',
            'deleted_post',
            'registered_post_type',
            'unregistered_post_type',
            'switch_theme',
        ),
        AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE => array(
            'save_post',
            'delete_post',
            'deleted_post',
        ),
        AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE => array(
            'add_attachment',
            'edit_attachment',
            'delete_attachment',
            'updated_post_meta',
        ),
        AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE => array(
            'add_attachment',
            'delete_attachment',
        ),
        AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE => array(
            'add_attachment',
            'delete_attachment',
            'save_post',
            'deleted_post',
        ),
    );
}

// =========================================================================================== \\
/**
 * Register all cache invalidation hooks based on environmental variable map.
 *
 * @return void
 */
function ai4seo_add_invalidate_caches_hooks(): void {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $environmental_variable_to_action_map = ai4seo_get_environmental_variable_to_action_cache_invalidation_map();

    foreach ($environmental_variable_to_action_map as $this_environmental_variable_name => $this_actions) {
        foreach ($this_actions as $this_action) {
            add_action(
                    $this_action,
                    function() use ($this_environmental_variable_name) {
                        ai4seo_invalidate_environmental_variable_cache($this_environmental_variable_name);
                    },
                    5,
                    20
            );
        }
    }
}


// =========================================================================================== \\

/**
 * Read attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename
 * @return int|false
 */
function ai4seo_get_cached_attachment_id_from_filename(string $normalized_filename) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(331224226, 'Prevented loop', true);
        return false;
    }

    if (!ai4seo_is_environmental_variable_cache_available(AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE)) {
        return false;
    }

    $lookup_cache = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE);

    if (!is_array($lookup_cache) || !isset($lookup_cache[$normalized_filename])) {
        return false;
    }

    return (int) $lookup_cache[$normalized_filename];
}

// =========================================================================================== \\

/**
 * Store attachment ID lookup cache entry by normalized filename.
 *
 * @param string $normalized_filename
 * @param int $attachment_id
 * @return void
 */
function ai4seo_set_cached_attachment_id_from_filename(string $normalized_filename, int $attachment_id): void {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(341224226, 'Prevented loop', true);
        return;
    }

    $lookup_cache = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE);

    if (!is_array($lookup_cache)) {
        $lookup_cache = array();
    }

    $lookup_cache[$normalized_filename] = (int) $attachment_id;

    if (count($lookup_cache) > 200) {
        $lookup_cache = array_slice($lookup_cache, -200, null, true);
    }

    ai4seo_update_environmental_variable(
            AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE,
            $lookup_cache,
            true,
            HOUR_IN_SECONDS
    );
}


// endregion
// ___________________________________________________________________________________________ \\
// region NOTIFICATIONS / NOTICES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to push a new unread notification
 * @param string $notification_index The notification identifier
 * @param string $message The notification message
 * @param bool $force Whether to force replace existing notification
 * @param array $additional_fields inject additional fields into the notification (notice_type, is_permanent, etc.)
 * @return bool True if notification was added, false otherwise
 */
function ai4seo_push_notification(string $notification_index, string $message, bool $force = false, array $additional_fields = array()): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 1, 10)) {
        ai4seo_debug_message(982407188, 'Prevented loop', true);
        return false;
    }

    if (empty($notification_index)) {
        return false;
    }

    // empty message -> remove notification
    if (empty($message)) {
        ai4seo_remove_notification($notification_index);
        return false;
    }

    // prepare parameters
    $notification_index = sanitize_key($notification_index);
    // sanitize the message, but keep html tags
    $message = ai4seo_wp_kses($message);
    $message = trim($message);
    $current_time = time();

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (!is_array($notifications)) {
        $notifications = array();
    }

    // do we push and create a new notification?
    $push_new_notification = $force || !isset($notifications[$notification_index]) || !$notifications[$notification_index] || !is_array($notifications[$notification_index]);

    // create empty array
    if ($push_new_notification) {
        $notifications[$notification_index] = array();
    }

    // keep track if we made changes to this notification
    $made_changes = false;

    // go through additional fields and apply them, set made_changes to true if any field is set and different
    if (is_array($additional_fields) && !empty($additional_fields)) {
        foreach ($additional_fields as $field_name => $field_value) {
            // sanitize the field name
            $field_name = sanitize_key($field_name);
            // sanitize the field value, but keep html tags
            $field_value = ai4seo_wp_kses($field_value);

            // to make expire_at time zone aware, we convert the time left to a specific timestamp
            // convert expire_in (seconds from now) -> expire_at (time in the future)
            if ($field_name === 'expire_in' && is_numeric($field_value) && $field_value > 0) {
                $field_name = 'expire_at';
                $field_value = $current_time + (int) $field_value; // make sure it's an integer
            }

            // if the field is not set or different, update it
            if (!isset($notifications[$notification_index][$field_name]) || $notifications[$notification_index][$field_name] !== $field_value) {
                $made_changes = true;
                $notifications[$notification_index][$field_name] = $field_value;
            }
        }
    }

    // always make sure to apply a new message
    if (!isset($notifications[$notification_index]['message']) || $notifications[$notification_index]['message'] != $message) {
        $made_changes = true;
        $notifications[$notification_index]['message'] = $message;
    }

    // if we push a new notification, we reset the time_created, read, dismissed and time_dismissed fields
    if ($push_new_notification) {
        $made_changes = true;
        $notifications[$notification_index]['time_created'] = $current_time;
        $notifications[$notification_index]['read'] = false; // unread by default

        if (!isset($notifications[$notification_index]["is_permanent"]) || !$notifications[$notification_index]["is_permanent"]) {
            $notifications[$notification_index]['dismissed'] = false;
            $notifications[$notification_index]['time_dismissed'] = 0;
            $notifications[$notification_index]['time_auto_dismiss'] = 0;
        } else {
            unset($notifications[$notification_index]['dismissed'],
                $notifications[$notification_index]['time_dismissed'],
                $notifications[$notification_index]['time_auto_dismiss']);
        }
    }

    // no changes detected? return true
    if (!$made_changes) {
        return true;
    }

    ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $notifications, false, false);
    ai4seo_refresh_unread_notifications_count();

    return true;
}

// =========================================================================================== \\

/**
 * Function to auto-dismiss expired notifications and get displayable notifications
 * @param bool $skip_num_displayable_notification_condition Whether to skip the condition that checks the number of displayable notifications to prevent loops
 * @param bool $refresh_unread_count Whether to refresh the unread notifications counter after auto-dismissing or deleting notifications
 * @return array Array of notifications that should be displayed (not dismissed and not expired)
 */
function ai4seo_get_displayable_notifications(bool $skip_num_displayable_notification_condition = false, bool $refresh_unread_count = true): array {
    if (ai4seo_prevent_loops(__FUNCTION__, 3)) {
        ai4seo_debug_message(377627317, 'Prevented loop', true);
        return array();
    }

    $notifications = ai4seo_get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array(), false);

    if (!is_array($notifications)) {
        return array();
    }

    $current_time = time();
    $read_displayable_notifications = array();
    $unread_displayable_notifications = array();
    $made_changes = false;
    $max_displayable_already_red_notifications = AI4SEO_MAX_DISPLAYABLE_ALREADY_READ_NOTIFICATIONS;

    foreach ($notifications as $this_notification_index => $this_notification) {
        if (!is_array($this_notification)) {
            continue;
        }

        // Skip already dismissed notifications
        if (isset($this_notification['dismissed']) && $this_notification['dismissed']) {
            continue;
        }

        // Check if notification should be auto-dismissed
        if (isset($this_notification['time_auto_dismiss'])
            && $this_notification['time_auto_dismiss'] > 0
            && $current_time >= $this_notification['time_auto_dismiss']
            && (!isset($this_notification['is_permanent']) || !$this_notification['is_permanent'])) {

            // Auto-dismiss this notification
            $notifications[$this_notification_index]['dismissed'] = true;
            $notifications[$this_notification_index]['time_dismissed'] = $current_time;
            $made_changes = true;
            continue;
        }

        // Check if notification should be removed due to expiration
        if (isset($this_notification['expire_at']) && is_numeric($this_notification['expire_at']) && $this_notification['expire_at'] > 0 && $current_time > $this_notification['expire_at']) {
            // Remove this notification
            unset($notifications[$this_notification_index]);
            $made_changes = true;
            continue;
        }

        // skip if we don't pass conditions
        if (!ai4seo_check_notification_conditions($this_notification_index, $this_notification, $skip_num_displayable_notification_condition)) {
            continue;
        }

        // This notification should be displayed
        if ($this_notification['read']) {
            // If the notification is read, we limit the number of already read notifications
            if (count($read_displayable_notifications) < $max_displayable_already_red_notifications) {
                $read_displayable_notifications[$this_notification_index] = $this_notification;
            }
        } else {
            // If the notification is unread, we add it to the unread notifications
            $unread_displayable_notifications[$this_notification_index] = $this_notification;
        }
    }

    // Update notifications if any were auto-dismissed or deleted
    if ($made_changes) {
        ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $notifications, false, false);

        if ($refresh_unread_count) {
            ai4seo_refresh_unread_notifications_count();
        }
    }

    return array_merge($unread_displayable_notifications, $read_displayable_notifications);
}

// =========================================================================================== \\

/**
 * Echo a notice from the notification system
 * @param string $notification_index The notification index
 * @param array $notification The notification data
 * @return void
 */
function ai4seo_echo_notice_from_notification(string $notification_index, array $notification) {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(601523355, 'Prevented loop', true);
        return;
    }

    if (empty($notification_index) || empty($notification) || empty($notification['message'])) {
        return;
    }

    $is_dismissable = !(isset($notification['is_permanent']) && $notification['is_permanent']);
    $show_contact_us_info = (bool) ($notification['contact_us'] ?? false);
    $notice_class = $notification['notice_type'] ?? 'notice-info';
    $message = $notification['message'];
    $is_unread = !isset($notification['read']) || !$notification['read'];
    $ignore_during_dashboard_refresh = (bool) ($notification['ignore_during_dashboard_refresh'] ?? true);

    if ($show_contact_us_info) {
        $message .= "<br /><br />" . __("If you have any questions, just click the button below to <strong>contact us</strong>. We’re happy to help. In any language you prefer.", "ai-for-seo");
    }

    // Add CSS classes for unread notifications (blinking)
    $additional_classes = '';

    // unread?
    $additional_classes .= $is_unread ? ' ai4seo-unread-notice' : '';

    // ignore during dashboard refresh?
    if ($ignore_during_dashboard_refresh) {
        $additional_classes .= ' ai4seo-ignore-during-dashboard-refresh';
    }

    echo '<div class="notice ai4seo-notice ai4seo-notification' . ($is_dismissable ? " is-dismissible" : "") . ' ' . esc_attr($notice_class) . esc_attr($additional_classes) . '" data-notification-index="' . esc_attr($notification_index) . '">';
        ai4seo_echo_wp_kses(ai4seo_get_sooz_logo_image_tag('sooz-oo'));

        // the message
        ai4seo_echo_wp_kses(ai4seo_filter_notification_message($message, $notification_index, $notification));

        // Add footer
        $notification_buttons = ai4seo_get_notification_buttons($notification_index, $notification);

        if ($notification_buttons) {
            echo '<div class="ai4seo-buttons-wrapper">';
                ai4seo_echo_wp_kses($notification_buttons);
            echo '</div>';
        }

        # add two span as a workaround for wordpress notice dismiss button bugs
        echo "<span></span><span></span>";

    echo '</div>';
}

// =========================================================================================== \\

function ai4seo_filter_notification_message($message, string $notification_index, array $notification): string {
    // replace placeholders in the message
    if (strstr($message, "{{EXPIRE_COUNTDOWN}}") && isset($notification["expire_at"]) && is_numeric($notification["expire_at"]) && $notification["expire_at"] > time()) {
        $message_expires_in = $notification["expire_at"] - time();
        $expire_in_countdown = "<span class='ai4seo-countdown' data-time-left='" . esc_attr($message_expires_in) . "' data-trigger='ai4seo_refresh_robhub_account'>" . esc_html(ai4seo_format_seconds_to_hhmmss_or_days_hhmmss($message_expires_in)) . "</span>";
        $message = str_replace("{{EXPIRE_COUNTDOWN}}", $expire_in_countdown, $message);
    }

    // add avatar and greetings if the notification has 'show_avatar' set to true
    if (isset($notification["show_avatar"])) {
        $avatar = "<div class='ai4seo-developer-avatar-wrapper'><img src='" . esc_attr(ai4seo_get_assets_images_url("andre-erbis-at-space-codes.webp")) . "'></div>";
        $users_first_name = ai4seo_is_function_usable('get_current_user_id') && get_current_user_id() ? get_user_meta(get_current_user_id(), 'first_name', true) : '';

        if ($users_first_name) {
            /* translators: %s: User first name. */
            $greetings = "<strong>" . sprintf(esc_html__('Hi %s', 'ai-for-seo'), esc_html($users_first_name)) . ",</strong>";
        } else {
            $greetings = "<strong>" . esc_html__("Hi", "ai-for-seo") . ",</strong>";
        }

        $greetings .= "<br><br>";
        $greetings .= sprintf(
            /* translators: %s: plugin name */
            esc_html__("This is Andre from the %s team. Thanks for joining our SEO community of 2,400+ happy users – we appreciate having you on board!", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        );
        $greetings .= "<br><br>";

        $message = $avatar . $greetings . $message;
    }

    // add voucher_code if the notification has 'voucher_code' set
    if (!empty($notification["voucher_code"])) {
        $message .= "<br><br>";
        $message .= esc_html__("Enter this voucher code during checkout to apply the discount: ", "ai-for-seo") . "<br>";
        $message .= ai4seo_get_voucher_code_output($notification["voucher_code"]);
        $message .= "";
    }

    // Filter the message through the 'ai4seo_notification_message' filter
    return apply_filters('ai4seo_notification_message', $message, $notification_index, $notification);
}

// =========================================================================================== \\

/**
 * Filter and customize the footer for notifications
 * @param string $notification_index The notification index
 * @param array $notification The notification data
 * @return string The filtered footer HTML
 */
function ai4seo_get_notification_buttons(string $notification_index, array $notification): string {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(470991053, 'Prevented loop', true);
        return '';
    }

    $show_dismiss_button = !(isset($notification['is_permanent']) && $notification['is_permanent']);
    $show_not_now_button = (bool) ($notification['not_now_button'] ?? false); # replaces dismiss button if set
    $show_contact_us_button = (bool) ($notification['contact_us'] ?? false);
    $show_set_up_seo_autopilot_button = (bool) ($notification['set_up_seo_autopilot_button'] ?? false);
    $show_get_a_get_more_credits_button = (bool) ($notification['get_more_credits_button'] ?? false);
    $show_customize_payg_button = (bool) ($notification['customize_payg_button'] ?? false);
    $show_increase_payg_budget_button = (bool) ($notification['increase_payg_budget_button'] ?? false); // same as customize payg
    $show_sync_account_button = (bool) ($notification['sync_account_button'] ?? false);
    $show_get_a_custom_quote_button = (bool) ($notification['get_a_custom_quote_button'] ?? false);
    $show_grab_deal_button = (bool) ($notification['grab_deal_button'] ?? false);
    $show_claim_bonus_button = (bool) ($notification['claim_bonus_button'] ?? false);
    $show_rate_us_button = (bool) ($notification['rate_us_button'] ?? false);
    $show_go_to_account_settings_button = (bool) ($notification['go_to_account_settings_button'] ?? false);
    $show_lost_licence_key_button = (bool) ($notification['lost_licence_key_button'] ?? false);
    $show_update_plugin_button = (bool) ($notification['update_plugin_button'] ?? false);
    $show_go_to_settings_button = (bool) ($notification['go_to_settings_button'] ?? false);
    $show_go_to_help_button = (bool) ($notification['go_to_help_button'] ?? false);
    $show_see_whats_new_button = (bool) ($notification['see_whats_new_button'] ?? false);
    $account_url = ai4seo_get_subpage_url("account");
    $settings_url = ai4seo_get_subpage_url("settings");
    $help_url = ai4seo_get_subpage_url("help");
    $wp_admin_plugins_list_url = admin_url("plugins.php");

    $notification_buttons = "";


    // SPECIFIC NOTIFICATION BUTTONS ============================================================================ \\

    // plugin-update notification -> add "See what's new" button
    if ($show_see_whats_new_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag("#ai4seo_recent_plugin_updates", "", "", "arrow-up-right-from-square", __("See what's new", "ai-for-seo"), "ai4seo-notification-dismiss-button", "jQuery(\".ai4seo-recent-plugin-updates-content\").show()");
    }


    // ADDITIONAL GENERIC BUTTONS =============================================================================== \\

    // Show a "Rate us" Button
    if ($show_rate_us_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag(AI4SEO_OFFICIAL_RATE_US_URL, "", "", "star", __("Rate us", "ai-for-seo"), "ai4seo-unicorn-button", "", "_blank");

        // already rated button
        //$notification_buttons .= ai4seo_get_button_text_link_tag("#", "heart", __("Already rated?", "ai-for-seo"), "", "ai4seo_open_already_rated_modal()");
    }

    // Show a "Grab Deal" Button
    if ($show_grab_deal_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("gift", __("Grab Deal", "ai-for-seo"), "ai4seo-unicorn-button", "ai4seo_open_get_more_credits_modal();");
    }

    // Show a "Claim Bonus" Button
    if ($show_claim_bonus_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("arrow-up-right-from-square", __("Claim Bonus", "ai-for-seo"), "ai4seo-unicorn-button", "ai4seo_open_get_more_credits_modal();");
    }

    // Show a "Get more Credits" Button
    if ($show_get_a_get_more_credits_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("circle-plus", __("Get more Credits", "ai-for-seo"), "ai4seo-primary-button", "ai4seo_open_get_more_credits_modal();");
    }

    // Show a "Customize PAYG" Button
    if ($show_customize_payg_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("sliders", esc_html__("Customize Pay-As-You-Go", "ai-for-seo"), "", "ai4seo_handle_open_customize_payg_modal();");
    }

    // Show a "Increase Budget" Button (same as customize payg)
    if ($show_increase_payg_budget_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("sliders", esc_html__("Increase Budget", "ai-for-seo"), "", "ai4seo_handle_open_customize_payg_modal();");
    }

    // Show a "Get an exclusive quote" Button
    if ($show_get_a_custom_quote_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag(AI4SEO_OFFICIAL_CONTACT_URL, "", "_blank", "handshake", __("Get an exclusive quote", "ai-for-seo"));
    }

    // show a Set up SEO Autopilot button
    if ($show_set_up_seo_autopilot_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("paper-plane", esc_html__("Set up SEO Autopilot", "ai-for-seo"), "", "ai4seo_open_modal_from_schema(\"seo-autopilot\", {modal_size: \"small\", unsaved_changes_warnings: true});");
    }

    // Show a "Go to Settings" button
    if ($show_go_to_settings_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag($settings_url, "", "", "gear", __("Go to Settings", "ai-for-seo"), "ai4seo-primary-button");
    }

    // Show an "Account Settings" button
    if ($show_go_to_account_settings_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag($account_url, "", "", "key", __("Account Settings", "ai-for-seo"), "ai4seo-primary-button");
    }

    // Show an "Update Plugin" button
    if ($show_update_plugin_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag($wp_admin_plugins_list_url, "", "", "circle-up", __("Update Plugin", "ai-for-seo"), "ai4seo-primary-button");
    }

    // Show a lost license key button
    if ($show_lost_licence_key_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("key-slash", esc_html__("Lost your license data?", "ai-for-seo"), "", "ai4seo_open_lost_key_modal();");
    }

    // Show a "Go to Help section" button
    if ($show_go_to_help_button) {
        $notification_buttons .= ai4seo_get_a_tag_icon_button_tag($help_url, "", "", "circle-question", __("Go to Help", "ai-for-seo"), "ai4seo-primary-button");
    }

    // sync account button
    if ($show_sync_account_button) {
        $notification_buttons .= ai4seo_get_icon_button_tag("rotate", __("Refresh", "ai-for-seo"), "", "ai4seo_refresh_robhub_account(this); return false;");
    }

    // contact us button
    if ($show_contact_us_button) {
        $notification_buttons .= ai4seo_get_contact_us_button();
    }

    // dismiss / not now button
    if ($show_dismiss_button || $show_not_now_button) {
        // dismiss button
        $notification_buttons .= '<button type="button" class="ai4seo-button ai4seo-abort-button ai4seo-notification-dismiss-button" data-notification-index="' . esc_attr($notification_index) . '" title="' . esc_attr__("Dismiss this notification", "ai-for-seo") . '">';
            if ($show_not_now_button) {
                $notification_buttons .= esc_html__("Not now", "ai-for-seo");
            } else {
                $notification_buttons .= esc_html__("Dismiss", "ai-for-seo");
            }
        $notification_buttons .= '</button>';
    }

    return $notification_buttons;
}

// =========================================================================================== \\

function ai4seo_check_notification_conditions(string $notification_index, array $additional_fields = array(), bool $skip_num_displayable_notification_condition = false): bool {
    if (ai4seo_prevent_loops(__FUNCTION__, 3)) {
        ai4seo_debug_message(283333336, 'Prevented loop', true);
        return false;
    }

    $conditions = array();
    $debug = false; # set to true to enable debug logging

    // go through each $additional_fields and check if one is suffixed with "_condition", filter them out
    foreach ($additional_fields as $field_name => $field_value) {
        if (substr($field_name, -10) === '_condition') {
            $conditions[$field_name] = $field_value;
        }
    }

    if (!$conditions) {
        if ($debug) {
            ai4seo_debug_message(472507849, $notification_index . ">" . ai4seo_stringify("No conditions to check, passing by default."));
        }

        return true;
    }

    if ($debug) {
        ai4seo_debug_message(978849734, $notification_index . ">" . ai4seo_stringify("Checking conditions: " . json_encode($conditions)));
    }

    // go through each condition and check if it is met
    foreach ($conditions AS $condition_name => $condition_value) {
        if ($condition_value === "true") {
            $condition_value = true; // convert "true" string to boolean true
        } elseif ($condition_value === "false") {
            $condition_value = false; // convert "false" string to boolean false
        } else {
            $condition_value = ai4seo_deep_sanitize($condition_value); // sanitize string values
        }

        switch ($condition_name) {
            case "min_num_missing_entries_condition":
                // check if the number of missing entries is less than the condition value
                $min_num_missing_entries_condition = (int) $condition_value;
                $num_missing_posts = 0;
                $num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

                if ($num_missing_posts_by_post_type) {
                    $num_missing_posts = array_sum($num_missing_posts_by_post_type);
                }

                if ($num_missing_posts < $condition_value) {
                    if ($debug) {
                        ai4seo_debug_message(225223351, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $num_missing_posts . " < " . $condition_value));
                    }

                    return false; // condition not met
                }
                break;

            case "max_credits_balance_condition":
                // check if the credits balance is less than the condition value
                $max_credits_balance_condition = (int) $condition_value;
                $credits_balance = ai4seo_robhub_api()->get_credits_balance();

                if ($credits_balance > $max_credits_balance_condition) {
                    if ($debug) {
                        ai4seo_debug_message(104350543, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $credits_balance . " > " . $max_credits_balance_condition));
                    }

                    return false; // condition not met
                }
                break;

            case "min_credits_balance_condition":
                // check if the credits balance is greater than the condition value
                $min_credits_balance_condition = (int) $condition_value;
                $credits_balance = ai4seo_robhub_api()->get_credits_balance();

                if ($credits_balance < $min_credits_balance_condition) {
                    if ($debug) {
                        ai4seo_debug_message(574266499, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $credits_balance . " < " . $min_credits_balance_condition));
                    }

                    return false; // condition not met
                }
                break;

            case "do_credits_cover_all_missing_entries_condition":
                // check if the credits cover all missing entries
                $do_credits_cover_all_missing_entries_condition = (bool) $condition_value;
                $credits_balance = ai4seo_robhub_api()->get_credits_balance();

                $needed_amount_of_credits_to_cover_all_missing_entries = ai4seo_get_approximate_credits_needed();
                $do_credits_cover_all_missing_entries = $credits_balance >= $needed_amount_of_credits_to_cover_all_missing_entries;

                if ($do_credits_cover_all_missing_entries_condition != $do_credits_cover_all_missing_entries) {
                    if ($debug) {
                        ai4seo_debug_message(665345926, $notification_index . " >" . ai4seo_stringify($condition_name . ": credits_balance = " . $credits_balance . ", needed = " . $needed_amount_of_credits_to_cover_all_missing_entries . ", condition = " . ($do_credits_cover_all_missing_entries_condition ? "true" : "false") . ", actual = " . ($do_credits_cover_all_missing_entries ? "true" : "false")));
                    }

                    return false; // condition not met
                }
                break;

            case "has_purchased_something_condition":
                // check if the user has purchased something
                $has_purchased_something_condition = (bool) $condition_value;
                $has_purchased_something = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);

                if ($has_purchased_something_condition != $has_purchased_something) {
                    if ($debug) {
                        ai4seo_debug_message(530710183, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . ($has_purchased_something ? "true" : "false") . " != " . ($has_purchased_something_condition ? "true" : "false")));
                    }

                    return false; // condition not met
                }
                break;

            case "max_num_unread_notifications_condition":
                // check if the number of unread notifications is less than the condition value
                $max_num_unread_notifications_condition = (int) $condition_value;
                $num_unread_notifications = ai4seo_get_num_unread_notification();

                if ($num_unread_notifications > $max_num_unread_notifications_condition) {
                    if ($debug) {
                        ai4seo_debug_message(386181290, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $num_unread_notifications . " > " . $max_num_unread_notifications_condition));
                    }

                    return false; // condition not met
                }
                break;

            case "max_num_visible_notifications_condition":
                if ($skip_num_displayable_notification_condition) {
                    // skip this condition if we are not checking the number of displayable notifications to prevent a loop
                    break;
                }

                // check if the number of undismissed notifications is less than the condition value
                $max_num_visible_notifications_condition = (int) $condition_value;
                $num_visible_notifications = count(ai4seo_get_displayable_notifications(true)) - 1; # account for this notification itself

                if ($num_visible_notifications > $max_num_visible_notifications_condition) {
                    if ($debug) {
                        ai4seo_debug_message(637430938, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $num_visible_notifications . " > " . $max_num_visible_notifications_condition));
                    }

                    return false; // condition not met
                }
                break;

            case "is_robhub_account_synced_condition":
                // check if the RobHub account is synced
                $is_robhub_account_synced_condition = (bool) $condition_value;
                $is_robhub_account_synced = ai4seo_robhub_api()->is_account_synced();

                if ($is_robhub_account_synced_condition != $is_robhub_account_synced) {
                    if ($debug) {
                        ai4seo_debug_message(689873061, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . ($is_robhub_account_synced ? "true" : "false") . " != " . ($is_robhub_account_synced_condition ? "true" : "false")));
                    }

                    return false; // condition not met
                }

                break;

            case "min_product_version_condition":
                // check if the product version is at least the condition value
                $min_product_version_condition = ai4seo_deep_sanitize($condition_value);
                $current_product_version = AI4SEO_PLUGIN_VERSION_NUMBER;

                if (version_compare($current_product_version, $min_product_version_condition, '<')) {
                    if ($debug) {
                        ai4seo_debug_message(200593735, $notification_index . " >" . ai4seo_stringify($condition_name . ": " . $current_product_version . " < " . $min_product_version_condition));
                    }

                    return false; // condition not met
                }
                break;

            # unknown condition -> always opt out
            default:
                if ($debug) {
                    ai4seo_debug_message(498983924, $notification_index . " >" . ai4seo_stringify("Unknown condition: " . $condition_name . ", opting out."));
                }
                return false;
        }
    }

    if ($debug) {
        ai4seo_debug_message(136156803, $notification_index . " > All conditions met");
    }

    return true;
}

// =========================================================================================== \\

/**
 * Function to refresh the unread notification counter from AI4SEO_NOTIFICATIONS_OPTION_NAME
 * @return void
 */
function ai4seo_refresh_unread_notifications_count() {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(847616570, 'Prevented loop', true);
        return;
    }

    $displayable_notifications = ai4seo_get_displayable_notifications(false, false);

    $ai4seo_unread_count = 0;

    foreach ($displayable_notifications as $notification_index => $notification) {
        // skip if read
        if (isset($notification['read']) && $notification['read']) {
            continue;
        }

        // all other notifications are considered unread and not-dismissed
        $ai4seo_unread_count++;
    }

    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT, $ai4seo_unread_count);
}

// =========================================================================================== \\

/**
 * Function to check if an notification is defined in the $notifications array
 * @param string $notification_index The notification identifier
 * @return bool True if the notification is defined, false otherwise
 */
function ai4seo_is_notification_defined(string $notification_index): bool {
    if (empty($notification_index)) {
        return false;
    }

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    return $notifications && is_array($notifications) && isset($notifications[$notification_index]) && is_array($notifications[$notification_index]);
}

// =========================================================================================== \\

/**
 * Function to get the amount of unread notifications
 * @return int The number of unread notifications
 */
function ai4seo_get_num_unread_notification(): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(769273087, 'Prevented loop', true);
        return 0;
    }

    return (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT);
}

// =========================================================================================== \\

/**
 * Function to mark all notifications as read
 * @return bool True if all notifications were marked as read, false otherwise
 */
function ai4seo_mark_all_displayable_notifications_as_read(): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(527064110, 'Prevented loop', true);
        return false;
    }

    $displayable_notifications = ai4seo_get_displayable_notifications();
    $all_notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (empty($displayable_notifications) || !is_array($all_notifications) || empty($all_notifications)) {
        return false; // no notifications to mark as read
    }

    $current_time = time();
    $auto_dismiss_time = $current_time + (AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS * DAY_IN_SECONDS);
    $made_changes = false;

    foreach ($displayable_notifications as $this_notification_index => $this_notification) {
        if (!is_array($this_notification)) {
            continue;
        }

        if (isset($this_notification['read']) && $this_notification['read']) {
            // already read, skip
            continue;
        }

        if (!isset($all_notifications[$this_notification_index])) {
            continue;
        }

        $all_notifications[$this_notification_index]['read'] = true;
        $all_notifications[$this_notification_index]['time_auto_dismiss'] = $auto_dismiss_time;
        $made_changes = true;
    }

    if ($made_changes) {
        ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $all_notifications, false, false);
        ai4seo_refresh_unread_notifications_count();
    }

    return true;
}

// =========================================================================================== \\

/**
 * Function to mark a notification as read by index
 * @param string $notification_index The notification identifier
 * @return bool True if notification was marked as read, false otherwise
 */
function ai4seo_mark_notification_as_read(string $notification_index): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(715015911, 'Prevented loop', true);
        return false;
    }

    if (empty($notification_index)) {
        return false;
    }

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (!is_array($notifications) || !isset($notifications[$notification_index])) {
        return false;
    }

    // skip if already read
    if (isset($notifications[$notification_index]['read']) && $notifications[$notification_index]['read']) {
        return true;
    }

    $notifications[$notification_index]['read'] = true;

    if (!isset($notifications[$notification_index]['is_permanent']) || !$notifications[$notification_index]['is_permanent']) {
        $current_time = time();
        $auto_dismiss_time = $current_time + (AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS * DAY_IN_SECONDS);
        $notifications[$notification_index]['time_auto_dismiss'] = $auto_dismiss_time;
    }

    ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $notifications, false, false);
    ai4seo_refresh_unread_notifications_count();

    return true;
}

// =========================================================================================== \\

/**
 * Function to mark a notification as dismissed by index
 * @param string $index The notification identifier
 * @return bool True if notification was marked as dismissed, false otherwise
 */
function ai4seo_mark_notification_as_dismissed(string $index): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(398944775, 'Prevented loop', true);
        return false;
    }

    if (empty($index)) {
        return false;
    }

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (!is_array($notifications) || !isset($notifications[$index])) {
        return false;
    }

    $notifications[$index]['dismissed'] = true;
    $notifications[$index]['time_dismissed'] = time();
    $notifications[$index]['message'] = ''; // clear message to clean up the database

    ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $notifications, false, false);
    ai4seo_refresh_unread_notifications_count();

    return true;
}

// =========================================================================================== \\

function ai4seo_is_notification_dismissed(string $notification_index): bool {
    // Make sure that $notification_index-parameter has content
    if (!$notification_index) {
        ai4seo_debug_message(511301024, 'Notification index is empty.', true);
        return false;
    }

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (!is_array($notifications) || !isset($notifications[$notification_index])) {
        return false;
    }

    return isset($notifications[$notification_index]['dismissed']) && $notifications[$notification_index]['dismissed'];
}

// =========================================================================================== \\

/**
 * Function to remove a notification entry by index
 * @param string $notification_index The notification identifier
 * @return bool True if notification was removed, false otherwise
 */
function ai4seo_remove_notification(string $notification_index): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(831312893, 'Prevented loop', true);
        return false;
    }

    if (empty($notification_index)) {
        return false;
    }

    $notifications = get_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, array());

    if (!is_array($notifications) || !isset($notifications[$notification_index])) {
        return false;
    }

    unset($notifications[$notification_index]);

    ai4seo_update_option(AI4SEO_NOTIFICATIONS_OPTION_NAME, $notifications, false, false);
    ai4seo_refresh_unread_notifications_count();

    return true;
}

// =========================================================================================== \\

/**
 * Function to remove all notifications
 */
function ai4seo_remove_all_notifications() {
    delete_option(AI4SEO_NOTIFICATIONS_OPTION_NAME);
}


// NOTIFICATION CHECKS =================================================================== \\

function ai4seo_check_for_new_notifications() {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $is_user_on_our_dashboard = ai4seo_is_plugin_page_active("dashboard");

    // push these notifications, only on our dashboard to save resources

    if ($is_user_on_our_dashboard) {
        // present a fresh missing entries notification on the dashboard page
        ai4seo_check_for_missing_entries_notification();

        // check for wpml plugin heads up notification
        ai4seo_check_for_wpml_heads_up_notification();

        // check for rate us notification
        ai4seo_check_for_rate_us_notification();
    }

    // push these notifications, even when the user is not inside our plugin admin pages ->
    ai4seo_check_for_low_credits_balance_notification();
    ai4seo_check_for_inefficient_cron_jobs_notification();
    ai4seo_check_for_finished_seo_autopilot_notification();
    ai4seo_check_for_unfinished_posts_table_analysis_notification(true);
    ai4seo_check_for_heavy_db_operations_disabled_notification();
}

// =========================================================================================== \\

function ai4seo_check_for_unfinished_posts_table_analysis_notification($force = false) {
    global $wpdb;

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "unfinished-posts-table-analysis";

    $posts_table_analysis_state = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE);

    if ($posts_table_analysis_state === 'completed') {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // if we have dismissed this notification before, we don't show it again
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    $posts_table_analysis_last_post_id = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID);

    // read last post id in posts table
    if (ai4seo_is_environmental_variable_cache_available(AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE)) {
        $max_post_id_in_wp_posts_table = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE);
    } else {
        $max_post_id_in_wp_posts_table = (int) $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts}");

        if ( $wpdb->last_error ) {
            ai4seo_debug_message(984321698, 'Database error: ' . $wpdb->last_error, true);
            return;
        }

        ai4seo_update_environmental_variable(
            AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE,
            $max_post_id_in_wp_posts_table,
            true,
            HOUR_IN_SECONDS
        );
    }

    // calculate percentage done
    $percentage_done = 0;

    if ($max_post_id_in_wp_posts_table > 0) {
        $percentage_done = round(($posts_table_analysis_last_post_id / $max_post_id_in_wp_posts_table) * 100);
    }

    $message = sprintf(
        /* translators: %s: plugin name */
        esc_html__("Your pages and media files are being analyzed to improve SEO coverage statistics. This process helps %s identify which content needs AI optimization. Please wait until the analysis is complete.", "ai-for-seo"),
        esc_html(AI4SEO_PLUGIN_NAME)
    );

    $message .= "<br><br>";

    $message .= "<div class='ai4seo-seo-coverage-progress-bar ai4seo-green-animated-progress-bar ai4seo-progress-bar-not-finished'>";
        $message .= "<div class='ai4seo-seo-coverage-inner-progress-bar' style='width: " . esc_attr($percentage_done) . "%'></div>";
    $message .= "</div>";

    /* translators: %s: Percentage of posts table analysis completed. */
    $message .= sprintf(esc_html__('Progress: %s%% completed', 'ai-for-seo'), esc_html($percentage_done));

    // in smaller font the number of posts analyzed so far and max entries,
    // also the estimated time remaining considering AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE, AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME and AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS
    $num_posts_analyzed_so_far = $posts_table_analysis_last_post_id;
    $num_posts_remaining = $max_post_id_in_wp_posts_table - $num_posts_analyzed_so_far;

    $num_batches_remaining = ceil($num_posts_remaining / AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE);
    $num_batches_per_seconds = round((AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME / (AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS / 100000))); # how many batches can be processed in 10 seconds (considering auto dashboard reloads triggering a batch-stack)
    $estimated_time_remaining_seconds = ($num_batches_remaining / max($num_batches_per_seconds, 1)) * 10; // in seconds

    $message .= " <span class='ai4seo-sub-info'>";
        $message .= sprintf(
            /* translators: 1: Number of processed entries. 2: Total number of entries. 3: Estimated time remaining. */
            esc_html__('(%1$s / %2$s entries. Estimated time remaining: %3$s. This page refreshes automatically until the analysis is complete.)', 'ai-for-seo'),
            esc_html(number_format_i18n($num_posts_analyzed_so_far)),
            esc_html(number_format_i18n($max_post_id_in_wp_posts_table)),
            sprintf(
                /* translators: %s: Estimated time remaining in seconds. */
                _n('%s second', '%s seconds', $estimated_time_remaining_seconds, 'ai-for-seo'),
                esc_html(number_format_i18n($estimated_time_remaining_seconds))
            ),
        );
    $message .= "</span>";

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-info",
            "is_permanent" => true,
            "ignore_during_dashboard_refresh" => false,
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_plugin_update_notification($last_known_plugin_version, $force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "plugin-update";

    // go through change log and collect useful data
    $full_change_log = ai4seo_get_change_log();
    $total_num_changes = 0;
    $change_log_examples = array();
    $missed_plugin_versions = array();

    foreach ($full_change_log as $change_log_entry) {
        if (!isset($change_log_entry['important']) || !$change_log_entry['important']) {
            continue;
        }

        // we can break here since there are no versions lower than the last known plugin version left
        if (!isset($change_log_entry['version']) || version_compare($change_log_entry['version'], $last_known_plugin_version, '<=')) {
            break;
        }

        if (!isset($change_log_entry['updates']) || !$change_log_entry['updates']) {
            continue;
        }

        $missed_plugin_versions[] = $change_log_entry['version'];
        $total_num_changes += count($change_log_entry['updates']);

        foreach ($change_log_entry['updates'] AS $this_update) {
            if (count($change_log_examples) < 3) {
                $change_log_examples[] = $this_update;
            }
        }
    }

    // if we only have one entry and this contains "maintenance" updates, we skip showing this notification
    if (count($change_log_examples) === 1) {
        $first_change_log_example = reset($change_log_examples);

        if (stripos($first_change_log_example, 'Maintenance') !== false) {
            ai4seo_remove_notification($notification_index);
            return;
        }
    }

    if (!$missed_plugin_versions) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // if we have dismissed this notification before, we don't show it again
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    $remaining_changes = ($total_num_changes - count($change_log_examples));

    // build message
    $message = sprintf(
    /* translators: 1: Plugin name, 2: Plugin version, 3: Plugin versions */
        esc_html__('Heads up! %1$s has been updated from version %2$s to version %3$s, and it includes %4$s important improvements:', 'ai-for-seo'),
        '<strong>' . AI4SEO_PLUGIN_NAME . '</strong>',
        $last_known_plugin_version,
        '<strong>' . AI4SEO_PLUGIN_VERSION_NUMBER . '</strong>',
        '<strong>' . $total_num_changes . '</strong>'
    );

    $message .= "<ul>";
        foreach ($change_log_examples as $this_example) {
            $message .= "<li>" . esc_html($this_example) . "</li>";
        }

        if ($remaining_changes > 0) {
            $message .= "<li>";
            $message .= sprintf(
                /* translators: %s: Number of additional changelog improvements. */
                esc_html__('And %1$s more improvements!', 'ai-for-seo'),
                "<strong>{$remaining_changes}</strong>",
            );
            $message .= "</li>";
        }
    $message .= "</ul>";

    if ($remaining_changes > 0) {
        $message .= esc_html__('👉 Check out the full changelog by clicking the button below.', 'ai-for-seo');
    }

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-info",
            "max_num_visible_notifications_condition" => 1, # prevent spam
            "see_whats_new_button" => $remaining_changes > 0,
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_heavy_db_operations_disabled_notification( bool $force = false ) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = 'heavy-db-operations-disabled';

    if ( ! ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS ) ) {
        ai4seo_remove_notification( $notification_index );
        return;
    }

    $help_troubleshooting_url = ai4seo_get_subpage_url( 'help' ) . '#ai4seo-troubleshooting-section';

    $message = sprintf(
        /* translators: %s: Help & Troubleshooting URL */
        __('Heavy database refresh operations are currently <strong>disabled for debugging</strong>. Coverage statistics and generation summaries may be outdated until you re-enable this option under <a href="%s" target="_blank" rel="noopener noreferrer">Help &gt; Troubleshooting</a>.', 'ai-for-seo'),
        esc_url( $help_troubleshooting_url )
    );

    ai4seo_push_notification(
        $notification_index,
        $message,
        true,
        array(
            'notice_type' => 'notice-warning',
            "is_permanent" => true,
            'go_to_help_button' => true,
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_wpml_heads_up_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "wpml-heads-up";

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // check if WPML plugin is active
    if (!ai4seo_is_plugin_or_theme_active(AI4SEO_THIRD_PARTY_PLUGIN_WPML)) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    $message = sprintf(
        /* translators: 1: Plugin name “WPML”, 2: Your plugin name */
        esc_html__('Just a heads-up — this isn’t a warning. %1$s is currently active on your website, and %2$s is fully compatible with it. Here are a few useful tips tailored to your setup:', 'ai-for-seo'),
        '<strong>WPML</strong>',
        '<span class="ai4seo-plugin-name">' . AI4SEO_PLUGIN_NAME . '</span>'
    );
    $message .= "<ul>";
        $message .= "<li>1. " . esc_html__('Metadata and media attributes should be generated for each entry in every language. For this reason, the total number displayed on the dashboard appears higher, as each entry is processed separately for each language.', 'ai-for-seo') . "</li>";
        $message .= "<li>2. " . esc_html__("For best results, we recommend keeping the language settings at \"automatic\", as this ensures the metadata is generated correctly for each language using WPML's language detection.", "ai-for-seo") . "</li>";
    $message .= "</ul>";

    $message .= esc_html__("You may safely dismiss this notification, once you are aware of the above.", "ai-for-seo");

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force);
}

// =========================================================================================== \\

function ai4seo_check_for_rate_us_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "rate-us";

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    $ai4seo_has_purchased_something = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING);

    // user has not purchased anything, no need to show the rate us notification yet
    if (!$ai4seo_has_purchased_something) {
        return;
    }

    // todo: user already rated us, no need to show the rate us notification again

    $message = esc_html__("We hope you're enjoying the plugin and we'd love to hear your feedback and thoughts on your experience. Leaving a comment and rating our plugin using the button below truly helps us with further development and allows us to maintain high support standards. Your input is greatly appreciated!", "ai-for-seo");
    $message .= "<br><br>" . sprintf(
            /* translators: %s: plugin name */
            esc_html__("On behalf of the entire %s team, thank you for your support!", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        );
    $message .= " ❤️";

    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-success",
            "show_avatar" => true,
            "rate_us_button" => true,
            "max_num_visible_notifications_condition" => 0, # prevent spam, catch a focused moment
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
            "has_purchased_something_condition" => true, // only show this notification if the user has purchased something
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_low_credits_balance_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "low-credits-balance";

    // check credits balance
    $current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

    // everything is fine, no need to show a notice
    if ($current_credits_balance >= AI4SEO_LOW_CREDITS_THRESHOLD) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // very low credits balance, show notice-error
    if ($current_credits_balance < AI4SEO_VERY_LOW_CREDITS_THRESHOLD) {
        $notice_type = "notice-error";
        $message = sprintf(
            /* translators: %u: Remaining credits balance. */
            __('<span style=\'color: red; font-weight: bold;\'>Remaining Credits: %u</span>. Your Credits are running very low.', 'ai-for-seo'),
            $current_credits_balance
        );
    } else {
        $notice_type = "notice-warning";
        $message = sprintf(
            /* translators: %u: Remaining credits balance. */
            __('<strong>Remaining Credits: %u</strong>. Your Credits are running low.', 'ai-for-seo'),
            $current_credits_balance
        );
    }

    $message .= "<br><br>" . __("To continue improving your remaining content, please consider purchasing more Credits using the <strong>Get more Credits</strong> button below. You can also activate <strong>Pay-As-You-Go</strong> to ensure you never run out of Credits.", "ai-for-seo");
    $message .= "<br><br>" . __("Have questions or need a custom quote? Just click the <strong>Get an exclusive quote</strong> button to contact us. We're happy to find a solution that fits your needs.", "ai-for-seo");

    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => $notice_type,
            "is_permanent" => true,
            "get_more_credits_button" => true,
            "get_a_custom_quote_button" => true,
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
            "ignore_during_dashboard_refresh" => false, // refersh this notification even during dashboard refreshes
        ));
}

// =========================================================================================== \\

/**
 * Function to add the performance notice. ATTENTION: Make sure to add the admin notices if the user got the rights to see them
 * @return void
 */
function ai4seo_check_for_missing_entries_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "missing-entries";

    $posts_table_analysis_state = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE);

    // don't show missing entries notification while analysis is still ongoing
    if ( $posts_table_analysis_state !== 'completed' ) {
        ai4seo_remove_notification( $notification_index );
        return;
    }

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    $messages = array();

    // check current credits balance
    $current_credits_balance = ai4seo_robhub_api()->get_credits_balance();

    // MISSING POSTS
    // do we even have missing posts? If not, we can skip the notice
    $num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();
    $num_failed_posts_by_post_type = ai4seo_get_num_failed_posts_by_post_type();

    // remove all empty post types
    foreach ($num_missing_posts_by_post_type as $post_type => $num_posts) {
        // check for failed entries (to subtract them)
        if (isset($num_failed_posts_by_post_type[$post_type]) && $num_failed_posts_by_post_type[$post_type]) {
            $num_posts -= $num_failed_posts_by_post_type[$post_type];
        }

        if ($num_posts <= 0) {
            unset($num_missing_posts_by_post_type[$post_type]);
        }

        // also remove if this post type is auto generated, and we have enough credits left, as it will be fully generated soon
        if (ai4seo_is_bulk_generation_enabled($post_type) && $current_credits_balance >= AI4SEO_VERY_LOW_CREDITS_THRESHOLD) {
            unset($num_missing_posts_by_post_type[$post_type]);
        }
    }

    // if there are no missing posts, return
    if (empty($num_missing_posts_by_post_type)) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // GENERATED POSTS
    // check ai4seo_get_generation_status_summary_entry for generated posts
    $num_generated_by_post_type = ai4seo_get_num_generated_posts_by_post_type();

    // remove empty post types
    foreach ($num_generated_by_post_type as $post_type => $num_posts) {
        if ($num_posts == 0) {
            unset($num_generated_by_post_type[$post_type]);
        }
    }

    // YOU'RE DOING GREAT SO FAR! NOTICE
    if ($num_generated_by_post_type) {
        $generated_post_types_strings_parts = array();

        foreach ($num_generated_by_post_type AS $post_type => $num_posts) {
            # attachment -> media workaround
            if ($post_type == "attachment") {
                $post_type = "media file";
            }

            $generated_post_types_strings_parts[] = ai4seo_get_post_type_translation($post_type, $num_posts);
        }

        // build $post_types_to_mention_string by separating with commas and the last one with "and"
        if (count($generated_post_types_strings_parts) > 1) {
            $generated_post_types_complete_string = implode(", ", array_slice($generated_post_types_strings_parts, 0, -1)) . " " . __("and", "ai-for-seo") . " " . end($generated_post_types_strings_parts);
        } else {
            $generated_post_types_complete_string = $generated_post_types_strings_parts[0];
        }

        $messages[] = sprintf(
            /* Translators: %1$s is replaced with bold text. */
            __('<strong>You\'re doing great so far!</strong> You already generated SEO-relevant data for %1$s.', 'ai-for-seo'),
            '<strong>' . esc_html($generated_post_types_complete_string) . '</strong>'
        );
    }

    // ROOM FOR IMPROVEMENT! NOTICE
    $missing_post_types_strings_parts = array();

    foreach ($num_missing_posts_by_post_type AS $post_type => $num_posts) {
        # attachment -> media workaround
        if ($post_type == "attachment") {
            $post_type = "media file";
        }

        $missing_post_types_strings_parts[] = ai4seo_get_post_type_translation($post_type, $num_posts);
    }

    // build $post_types_to_mention_string by separating with commas and the last one with "and"
    // only, when we already have generated posts
    if ($missing_post_types_strings_parts && $num_generated_by_post_type) {
        if (count($missing_post_types_strings_parts) > 1) {
            $missing_post_types_complete_string = implode(", ", array_slice($missing_post_types_strings_parts, 0, -1)) . " " . __("and", "ai-for-seo") . " " . end($missing_post_types_strings_parts);
        } else {
            $missing_post_types_complete_string = $missing_post_types_strings_parts[0];
        }

        $messages[] = sprintf(
            /* Translators: %1$s plugin name, %2$s is replaced with bold text of missing post types. */
            __('However, there is still room for improvement. <strong>%1$s</strong> has found missing or problematic data in %2$s. Please check the statistics below and consider generating the missing data to enhance your SEO performance.', 'ai-for-seo'),
            esc_html(AI4SEO_PLUGIN_NAME),
            '<strong>' . esc_html($missing_post_types_complete_string) . '</strong>',
        );
    }

    // NO NOTICES COLLECTED SO FAR? RETURN
    if (!$messages) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // PUSH NOTIFICATION
    ai4seo_push_notification($notification_index, implode("<br>", $messages), $force,
        array(
            "set_up_seo_autopilot_button" => true,
            "max_num_visible_notifications_condition" => 1, # prevent spam
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_robhub_account_error_notification($api_response, $force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "robhub-account-error";

    // if we have a successful response, potentially remove the notification
    if (isset($api_response["success"]) && $api_response["success"]) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // no subscription data -> echo error
    $message = __("<strong>Failed to verify your license data.</strong> Please check your account settings.", "ai-for-seo");

    if (isset($api_response["message"]) && $api_response["message"]) {
        $message .= " <strong>ERROR: "  . esc_html($api_response["message"]) . "</strong>";
    }

    if (isset($api_response["code"]) && $api_response["code"]) {
        $message .= " " . esc_html("(#" . $api_response["code"] . ").");
    }

    $message .= "<br><br>";
    $message .= "<strong>" . esc_html__("Lost your license data?", "ai-for-seo") . "</strong> ";
    $message .= sprintf(
        /* translators: %s: Link label to recover license data. */
        esc_html__('Please click on %s and follow the instructions.', 'ai-for-seo'),
        "<strong>" . esc_html__("Lost your license data?", "ai-for-seo") . "</strong>"
    );

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-error",
            "is_permanent" => true,
            "lost_licence_key_button" => true,
            "contact_us" => true
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_plugin_update_available($latest_plugin_version, $force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "plugin-update-available";

    if (!$latest_plugin_version) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // if we have the latest version, potentially remove the notification
    if (version_compare(AI4SEO_PLUGIN_VERSION_NUMBER, $latest_plugin_version, '>=')) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // build message
    $message = sprintf(
        /* translators: 1: Plugin name, 2: Plugin version */
        esc_html__('A new version of %1$s is available: %2$s. Your current version is %3$s. Please update to the latest version to enjoy new features and improvements.', 'ai-for-seo'),
        '<strong>' . esc_html(AI4SEO_PLUGIN_NAME) . '</strong>',
        '<strong>' . esc_html($latest_plugin_version) . '</strong>',
        esc_html(AI4SEO_PLUGIN_VERSION_NUMBER)
    );

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-info",
            "update_plugin_button" => true,
            "max_num_visible_notifications_condition" => 1 # prevent spam
        )
    );
}

// =========================================================================================== \\

function ai4seo_check_for_payg_status_errors($payg_status, $force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "payg-status-error";

    // if we have a successful response, potentially remove the notification
    if (!in_array($payg_status, array('budget-limit-reached', 'payment-pending', 'payment-failed', 'error'))) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    $show_increase_payg_budget_button = false;
    $show_contact_us_button = false;
    $show_sync_account_button = false;

    switch ($payg_status) {
        case 'budget-limit-reached':
            $notice_type = 'notice-warning';
            $message = __("<strong>Budget limit reached.</strong> New usage is paused. Increase your limit to resume immediately, or wait for the next cycle.", "ai-for-seo");
            $show_increase_payg_budget_button = true;
            break;
        case 'payment-pending':
            $notice_type = 'notice-warning';
            $message = sprintf(
                /* translators: %s: Refresh link label. */
                __('<strong>Your Pay-As-You-Go payment is still pending.</strong> New usage is paused until the payment is completed. Click \'%s\' to check if the payment has arrived. If it takes longer than expected, please contact us for assistance.', 'ai-for-seo'),
                '<strong>' . esc_html__("Refresh", "ai-for-seo") . '</strong>'
            );
            $show_contact_us_button = true;
            $show_sync_account_button = true;
            $force = true;
            break;
        case 'payment-failed':
            $notice_type = 'notice-error';
            $message = __("<strong>Your Pay-As-You-Go payment has failed.</strong> New usage is paused until the payment issue is resolved. Please check your payment information.", "ai-for-seo");
            $show_contact_us_button = true;
            $show_sync_account_button = true;
            $force = true;
            break;
        case 'error':
            $notice_type = 'notice-error';
            $message = __("<strong>There was an error with your Pay-As-You-Go refill.</strong> New usage is paused until the issue is resolved.", "ai-for-seo");
            $show_contact_us_button = true;
            $show_sync_account_button = true;
            $force = true;
            break;
        default:
            ai4seo_remove_notification($notification_index);
            return;
    }

    // check if this notification is already dismissed
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => $notice_type,
            "is_permanent" => true,
            "contact_us" => $show_contact_us_button,
            "increase_payg_budget_button" => $show_increase_payg_budget_button,
            "sync_account_button" => $show_sync_account_button,
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
        )
    );
}

// =========================================================================================== \\

/**
 * Function to eventually output a notice about inefficient cron jobs
 * @return void
 */
function ai4seo_check_for_inefficient_cron_jobs_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "inefficient-cron-jobs";

    // no need to check cron job efficiency if seo autopilot is not enabled
    $active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);

    if (!$active_bulk_generation_post_types) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // check if the SEO Autopilot was set up at least X seconds ago
    if (!ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago()) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // notification dismissed and no force -> return
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    // check if the last bulk generation cron job status update time is older than XX minutes
    $bulk_generation_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time(AI4SEO_BULK_GENERATION_CRON_JOB_NAME);

    if (!$bulk_generation_cron_job_status_update_time) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    if ($bulk_generation_cron_job_status_update_time >= (time() - MINUTE_IN_SECONDS * 10)) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // no need to check cron job efficiency if we don't have any missing posts
    $we_got_any_missing_posts = false;
    $num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

    foreach ($active_bulk_generation_post_types as $this_post_type) {
        // ignore post types for which the bulk generation is not enabled, as we don't generate these via cron jobs
        if (!in_array($this_post_type, $active_bulk_generation_post_types)) {
            continue;
        }

        // check if we have any missing posts for the current post type
        if (!empty($num_missing_posts_by_post_type[$this_post_type])
            && is_numeric($num_missing_posts_by_post_type[$this_post_type])
            && $num_missing_posts_by_post_type[$this_post_type] > 0) {
            $we_got_any_missing_posts = true;
            break;
        }
    }

    if (!$we_got_any_missing_posts) {
        // no missing posts for the active post types, remove the notification
        ai4seo_remove_notification($notification_index);
        return;
    }

    // cron job to slow notification
    if (ai4seo_is_wordpress_cron_disabled()) {
        $message = sprintf(
            /* translators: %s: plugin name */
            esc_html__("Your server cron jobs do not appear to be functioning properly, limiting %s automation. Please ensure that server cron jobs run at least every 5 minutes (1 minute for best results) or (not recommended) enable WordPress' internal cron system.", "ai-for-seo"),
            esc_html(AI4SEO_PLUGIN_NAME)
        );
    } else {
        $message = esc_html__("Heads up: You’re currently using WordPress’ built-in cron system. While it works reliably, automation may run more slowly, especially on websites with low traffic. If you’re satisfied with the current automation speed, you can safely dismiss this notice. If you’d like to speed things up, search for “cron” in Help → F.A.Q. for guidance.", "ai-for-seo");
    }

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => "notice-warning",
            "go_to_help_button" => true,
            "max_num_visible_notifications_condition" => 2, # prevent spam
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
            "ignore_during_dashboard_refresh" => false,
        )
    );
}


// =========================================================================================== \\

/**
 * Function to eventually push a notification about SEO Autopilot being finished
 * @return void
 */
function ai4seo_check_for_finished_seo_autopilot_notification($force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "seo-autopilot-finished";

    // check if the SEO Autopilot was set up at least X seconds ago
    if (!ai4seo_was_seo_autopilot_set_up_at_least_x_seconds_ago(10)) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // no need to check if seo autopilot is not enabled
    $active_bulk_generation_post_types = ai4seo_get_setting(AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES);

    if (!$active_bulk_generation_post_types) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // notification dismissed and no force -> return
    if (!$force && ai4seo_is_notification_dismissed($notification_index)) {
        return;
    }

    $finished_seo_autopilot_post_types_message = "";
    $translated_post_types = array();

    foreach ($active_bulk_generation_post_types AS $this_post_type) {
        # attachment -> media workaround
        if ($this_post_type == "attachment") {
            $this_post_type = "media file";
        }

        $translated_post_types[] = ai4seo_get_post_type_translation($this_post_type, true);
    }

    // build $post_types_to_mention_string by separating with commas and the last one with "and"
    // only, when we already have generated posts
    if ($translated_post_types) {
        if (count($translated_post_types) > 1) {
            $finished_seo_autopilot_post_types_message = implode(", ", array_slice($translated_post_types, 0, -1)) . " " . __("and", "ai-for-seo") . " " . end($translated_post_types);
        } else {
            $finished_seo_autopilot_post_types_message = $translated_post_types[0];
        }
    }

    // no need to check if we still have any missing posts left
    $we_got_any_missing_posts = false;
    $num_missing_posts_by_post_type = ai4seo_get_num_missing_posts_by_post_type();

    foreach ($active_bulk_generation_post_types as $this_post_type) {
        // check if we have any missing posts for the current post type
        if (!empty($num_missing_posts_by_post_type[$this_post_type])
            && is_numeric($num_missing_posts_by_post_type[$this_post_type])
            && $num_missing_posts_by_post_type[$this_post_type] > 0) {
            $we_got_any_missing_posts = true;
            break;
        }
    }

    if ($we_got_any_missing_posts) {
        // we have missing posts for the active post types, remove the notification
        ai4seo_remove_notification($notification_index);
        return;
    }

    // check for failed generation in active post types
    $num_failed_posts = 0;

    $num_failed_posts_by_post_type = ai4seo_get_num_failed_posts_by_post_type();

    foreach ($active_bulk_generation_post_types as $this_post_type) {
        // check if we have any failed posts for the current post type
        if (!empty($num_failed_posts_by_post_type[$this_post_type])
            && is_numeric($num_failed_posts_by_post_type[$this_post_type])
            && $num_failed_posts_by_post_type[$this_post_type] > 0) {
            $num_failed_posts += $num_failed_posts_by_post_type[$this_post_type];
            break;
        }
    }

    // check AI4SEO_LATEST_ACTIVITY_OPTION_NAME for an entry not older than the seo autopilot setup and action containing "bulk-generated"
    $ai4seo_seo_autopilot_start_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME);
    $latest_activity = ai4seo_get_option(AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array());

    $found_a_recent_bulk_generated_entry = false;

    foreach ($latest_activity AS $ai4seo_this_latest_activity_entry) {
        if (isset($ai4seo_this_latest_activity_entry['action'])
            && strstr($ai4seo_this_latest_activity_entry['action'], "bulk-generated")
            && isset($ai4seo_this_latest_activity_entry['timestamp'])
            && $ai4seo_this_latest_activity_entry['timestamp'] >= $ai4seo_seo_autopilot_start_time) {
            $found_a_recent_bulk_generated_entry = true;
            break;
        }
    }

    // no recent activity found, remove the notification
    if (!$found_a_recent_bulk_generated_entry) {
        ai4seo_remove_notification($notification_index);
        return;
    }

    // finished with failed generations
    if ($num_failed_posts) {
        $notice_type = "notice-warning";
        // build message
        $message = sprintf(
            /* translators: 1: Finished post types, 2: Num failed entries */
            esc_html__('The SEO Autopilot has finished processing all %1$s. However, generation failed for %2$s entries. Check the “Recent Activity” section or the relevant content pages (e.g. Posts, Media) for details.', 'ai-for-seo'),
            '<strong>' . esc_html($finished_seo_autopilot_post_types_message) . '</strong>',
            '<strong>' . esc_html($num_failed_posts) . '</strong>'
        );
    } else {
        $notice_type = "notice-success";
        // build message
        $message = sprintf(
            /* translators: 1: Finished post types */
            esc_html__('Congratulations! The SEO Autopilot has successfully finished processing all %1$s.', 'ai-for-seo'),
            '<strong>' . esc_html($finished_seo_autopilot_post_types_message) . '</strong>'
        );
    }

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force,
        array(
            "notice_type" => $notice_type,
            "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
            "ignore_during_dashboard_refresh" => false,
        )
    );
}


// =========================================================================================== \\

function ai4seo_check_discount_notification($discount, $allow_notification_force = false) {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    $notification_index = "discount";
    $discount_name = $discount['name'] ?? '';
    $discount_description = $discount['description'] ?? '';
    $discount_percentage = $discount['percentage'] ?? 0;
    $discount_expire_in = $discount['expire_in'] ?? 0;
    $expire_within_next_24h = $discount_expire_in > 0 && $discount_expire_in <= 24 * HOUR_IN_SECONDS;
    $expire_at = $discount_expire_in > 0 ? time() + $discount_expire_in : 0;
    $discount_first_purchase_only = $discount['first_purchase_only'] ?? false;
    $discount_voucher_code = $discount['voucher_code'] ?? '';
    $force = false;

    // if we enter the last 24h, force push this notification again
    if ($allow_notification_force && $expire_within_next_24h) {
        $force = true;
    }

    // pre-defined discount descriptions
    if ($discount_name == "early-bird") {
        $discount_description = sprintf(
            /* translators: 1: Discount percentage. 2: Remaining discount time. */
            esc_html__('We hope you\'re enjoying your first steps with our plugin! We understand that getting started can be challenging, so to support you, we\'re offering a special Early Bird discount of %1$s%% off all your upcoming purchases within the next %2$s.', 'ai-for-seo'),
            "<strong>" . esc_html($discount_percentage) . "</strong>",
            "<strong>{{EXPIRE_COUNTDOWN}}</strong>"
        );
    }

    // exclusive offer description
    if ($discount_name == "exclusive-offer") {
        $discount_description = sprintf(
            /* translators: 1: Discount percentage. 2: Remaining discount time. */
            esc_html__('As a token of our appreciation, we\'re excited to offer you an exclusive discount of %1$s%% on all your Credits Pack purchases for the next %2$s.', 'ai-for-seo'),
            "<strong>" . esc_html($discount_percentage) . "</strong>",
            "<strong>{{EXPIRE_COUNTDOWN}}</strong>"
        );
    }

    // build generic description
    if (!$discount_description) {
        if ($discount_first_purchase_only) {
            $discount_description = sprintf(
                /* translators: %s: Discount percentage. */
                esc_html__('As a welcome gift, we\'re offering you a %s discount on your first purchase.', 'ai-for-seo'),
                "<strong>" . esc_html($discount_percentage) . "%</strong>"
            );
            $discount_description .= "<br>👉 ";

            $discount_description .= sprintf(
                /* translators: %s: Discount countdown placeholder. */
                esc_html__('This offer is only valid for the next %1$s, so make sure to claim it before it expires.', 'ai-for-seo'),
                "<strong>{{EXPIRE_COUNTDOWN}}</strong>"
            );
        } else {
            $discount_description = sprintf(
                /* translators: %s: Discount percentage. */
                esc_html__('We\'re happily offering you a %1$s discount.', 'ai-for-seo'),
                "<strong>" . esc_html($discount_percentage) . "%</strong>",
            );
            $discount_description .= "<br>👉 ";
            $discount_description .= " " . sprintf(
                /* translators: %s: Discount countdown placeholder. */
                esc_html__('You can use this discount for ALL your purchases within the next %1$s.', 'ai-for-seo'),
                "<strong>{{EXPIRE_COUNTDOWN}}</strong>"
            );
        }
    }

    // save hours and boost rankings
    #$discount_description .= "<br>🚀 ";
    #$discount_description .= esc_html__("Save hours of manual work and boost your rankings – effortlessly.", "ai-for-seo");
    $discount_description .= "<br><br>";

    // take your chance
    $discount_description .= " " . sprintf(
        /* translators: %s: Call-to-action button label. */
        esc_html__('Click %1$s below to apply the discount now.', 'ai-for-seo'),
        "<strong>\"" . esc_html__("Grab Deal", "ai-for-seo") . "\"</strong>"
    );

    // build notification
    $message = $discount_description;

    $additional_fields = array(
        "notice_type" => "notice-success",
        "show_avatar" => true,
        "grab_deal_button" => true,
        "not_now_button" => true,
        "expire_at" => $expire_at,
        "voucher_code" => $discount_voucher_code,
        "is_robhub_account_synced_condition" => true, // only show this notification if the RobHub account is synced
        # "min_num_missing_entries_condition" => 50, # todo: use this, if we can distinguish between agency and non-agency users
        # "do_credits_cover_all_missing_entries_condition" => false, # todo: use this, if we can distinguish between agency and non-agency users
    );

    // add has_purchased_something_condition dynamically based on if this discount is for first purchase only
    if ($discount_first_purchase_only) {
        $additional_fields["has_purchased_something_condition"] = false;
    }

    // push the notification
    ai4seo_push_notification($notification_index, $message, $force, $additional_fields);
}


// endregion
// ___________________________________________________________________________________________ \\
// region TERMS OF SERVICE ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to check if we're going to show a terms of service layer
 * ATTENTION: DO NOT USE ROBHUB API COMMUNICATOR FUNCTIONS IN THIS FUNCTION TO PREVENT LOOPS
 * @return bool True if we need to show the terms of service layer, false if not.
 */
function ai4seo_does_user_need_to_accept_tos_toc_and_pp($check_group = true): bool {
    global $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;

    // currently deactivated
    return false;

    if ($ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp !== null) {
        return $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;
    }

    // get latest update to the terms of service, terms of conditions or privacy policy
    $latest_tos_or_toc_or_pp_update_timestamp = ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp();

    // get the last time the user accepted the terms of service, terms of conditions or privacy policy
    $tos_toc_and_pp_accepted_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME);

    // check if the user needs to accept the new terms
    $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp = ($tos_toc_and_pp_accepted_time < $latest_tos_or_toc_or_pp_update_timestamp);

    return $ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp;
}

// =========================================================================================== \\

/**
 * Returns the latest timestamp of the terms of service, terms of conditions or privacy policy update, depending on
 * what is the latest
 * @return int The latest timestamp
 */
function ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp(): int {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(613876128, 'Prevented loop', true);
        return 0;
    }

    // check the last known sooz.ai's terms update
    $last_website_toc_and_pp_update_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME);

    // for satefty reasons, we will not accept a timestamp that is in the future -> limit it to the current time - 1
    if ($last_website_toc_and_pp_update_time > time()) {
        $last_website_toc_and_pp_update_time = time() - 1;
    }

    if (AI4SEO_TOS_VERSION_TIMESTAMP > $last_website_toc_and_pp_update_time) {
        return AI4SEO_TOS_VERSION_TIMESTAMP;
    } else {
        return $last_website_toc_and_pp_update_time;
    }
}

// =========================================================================================== \\

/**
 * Function to get the latest version of the terms of service, terms of conditions or privacy policy
 * @return string
 */
function ai4seo_get_latest_tos_and_toc_and_pp_version(): string {
    return "v" . (ai4seo_gmdate("Y-m-d", ai4seo_get_latest_tos_or_toc_or_pp_update_timestamp()) ?: "???");

}

// =========================================================================================== \\

/**
 * Function to show the terms of service modal
 * @return void
 */
function ai4seo_show_terms_of_service_modal() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check if we are in the admin area of WordPress
    if (!is_admin()) {
        return;
    }

    $does_user_need_to_accept_tos_toc_and_pp = ai4seo_does_user_need_to_accept_tos_toc_and_pp();

    if (!$does_user_need_to_accept_tos_toc_and_pp) {
        return;
    }

    // update last open modal time to prevent re-opens in some cases
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME, time());

    // --- JAVASCRIPT --------------------------------------------------------- \\
    ?><script type="text/javascript">
    jQuery(function() {
        ai4seo_open_modal_from_schema("tos", {modal_css_class: "ai4seo-tos-modal", modal_size: "auto"})
    });
    </script><?php
    // ------------------------------------------------------------------------ \\
}

// =========================================================================================== \\

/**
 * Returns the HTML code of the TOS content.
 * @return string The HTML code of the TOS content
 */
function get_tos_content(): string {
    $html = "";

    $html .= "<h2>" . __("I - General Definitions", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        /* translators: 1: Company name. 2: Company abbreviation. */
        $html .= "<li>" . sprintf(__('This plugin was created and is maintained, including all updates and support, by <em>%1$s</em>, a German SEO agency (hereinafter referred to as \'<em>%2$s</em>\').', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_NAME, AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";

        /* translators: %s: plugin name */
        $html .= "<li>" . sprintf(__('These Terms of Service outline your rights and responsibilities when using the <em>%s</em> plugin. Please read them carefully.', 'ai-for-seo'), esc_html(AI4SEO_PLUGIN_NAME)) . "</li>";

        $html .= "<li>" . __("These Terms of Service are governed by the laws of Germany, and any disputes shall be resolved under German jurisdiction.", "ai-for-seo") . "</li>";
    $html .= "</ol>";

    $html .= "<h2>" . __("II - General Acknowledgements", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        /* translators: 1: Terms and Conditions URL. 2: Privacy Policy URL. 3: Company abbreviation. */
        $html .= "<li>" . sprintf(__('I have read and accept the <a href=\'%1$s\' target=\'_blank\'>Terms and Conditions</a> and <a href=\'%2$s\' target=\'_blank\'>Privacy Policy</a> of <em>%3$s</em>.', 'ai-for-seo'), AI4SEO_TERMS_AND_CONDITIONS_URL, AI4SEO_PRIVACY_POLICY_URL, AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('<em>%s</em> will not be liable for any direct, indirect, incidental, or consequential damages arising from the use of the plugin or generated content.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
    $html .= "</ol>";

    $html .= "<h2>" . __("III - User Responsibilities", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        $html .= "<li>" . __("I confirm that my content will be free from references to illegal drugs, violence, explicit material or otherwise illegal material.", "ai-for-seo") . "</li>";
        $html .= "<li>" . __("I understand that AI may make errors, and I am responsible for reviewing all generated results to ensure accuracy and compliance with applicable laws.", "ai-for-seo") . "</li>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('I acknowledge that I am solely responsible for how the generated data is used on my website, and <em>%s</em> is not liable for any misuse or improper application of the data.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        $html .= "<li>" . __("I will ensure that my use of the plugin complies with all applicable laws and regulations in my jurisdiction.", "ai-for-seo") . "</li>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('I understand that using certain features within the plugin will consume Credits based on the specific feature. If higher-than-expected credit consumption occurs due to user actions, whether intentional or unintentional, Credits cannot be refunded or reversed unless %s determines the user was not responsible. However, the right to a 100%% refund within the 14-day money-back guarantee period still applies under these circumstances.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        $html .= "<li>" . __("I may request a full refund within 14 days of the first purchase if not satisfied with the plugin’s performance, as outlined in our money-back guarantee policy. This refund policy applies only to the first purchase of either a subscription or Credits Pack. A refund for any purchases beyond the first one is excluded.", "ai-for-seo") . "</li>";
    $html .= "</ol>";

    $html .= "<h2>" . __("IV - Data Ownership, Handling and Lifetime", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        /* translators: %1$s plugin name, %2$s Company abbreviation. */
        $html .= "<li>" . sprintf(__('All data generated through the <em>%1$s</em> plugin remains the intellectual property of the user, provided it does not violate the terms of service. <em>%2$s</em> holds no claims to ownership of user-generated content.', 'ai-for-seo'), esc_html(AI4SEO_PLUGIN_NAME), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('<em>%s</em> complies with applicable data protection regulations, including the GDPR and DSGVO.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        /* translators: 1: Company abbreviation. 2: Terms and Conditions URL. 3: Privacy Policy URL. */
        $html .= "<li>" . sprintf(__('I agree that, in order to execute certain functions, data will be sent to <em>%1$s</em>\'s servers. This content will only be used and stored for purposes stated in the <a href=\'%2$s\' target=\'_blank\'>Terms and Conditions</a> and <a href=\'%3$s\' target=\'_blank\'>Privacy Policy</a>.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_TERMS_AND_CONDITIONS_URL, AI4SEO_PRIVACY_POLICY_URL) . "</li>";
        /* translators: 1: Company abbreviation. 2: Privacy Policy URL. */
        $html .= "<li>" . sprintf(__('When accepting these Terms of Service, <em>%1$s</em> may collect and store certain information, including the user’s website URL, website name, email address, IP address, the version of the Terms accepted, and the timestamp of acceptance. This data is collected solely for compliance purposes and will be retained securely for the period necessary to fulfill legal obligations or until account deletion, as outlined in our <a href=\'%2$s\' target=\'_blank\'>Privacy Policy</a>.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_PRIVACY_POLICY_URL) . "</li>";
        /* translators: %s: Privacy Policy URL. */
        $html .= "<li>" . sprintf(__('Data will be stored only for as long as necessary to fulfill the stated purpose and will be deleted in accordance with the data retention policy outlined in the <a href=\'%s\' target=\'_blank\'>Privacy Policy</a>.', 'ai-for-seo'), AI4SEO_PRIVACY_POLICY_URL) . "</li>";
        /* translators: 1: Company abbreviation. 2: Support email address. 3: Support email address. */
        $html .= "<li>" . sprintf(__('I may request the deletion of my data at any time, and <em>%1$s</em> will comply unless the data is required for fulfilling contractual or legal obligations. Requests can be sent to <a href=\'mailto:%2$s\'>%3$s</a>.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION, AI4SEO_SUPPORT_EMAIL, AI4SEO_SUPPORT_EMAIL) . "</li>";
    $html .= "</ol>";

    $html .= "<h2>" . __("V - Third-parties and partners", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        /* translators: %s: OpenAI URL. */
        $html .= "<li>" . sprintf(__('I agree that, in order to execute certain functions, data from my website and its content may be sent to third-party services, including <em><a href=\'%s\' target=\'_blank\'>OpenAI</a></em>.', 'ai-for-seo'), AI4SEO_OPENAI_URL) . "</li>";
        /* translators: %s: OpenAI Terms of Use URL. */
        $html .= "<li>" . sprintf(__('I will adhere to <em>OpenAI</em>\'s <a href=\'%s\' target=\'_blank\'>Terms of Use</a> at all times.', 'ai-for-seo'), AI4SEO_OPENAI_TERMS_OF_USE_URL) . "</li>";
        $html .= "<li>" . __("I specifically confirm that my content will be free from references to illegal drugs, extreme violence, or explicit material.", "ai-for-seo") . "</li>";
    $html .= "</ol>";

    $html .= "<h2>" . __("VI - Rights and Modifications", "ai-for-seo") . "</h2>";
    $html .= "<ol>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('<em>%s</em> reserves the right to terminate access to the plugin or revoke usage rights at any time if the terms are violated. In the event of termination, I will no longer have access to the plugin and any associated services.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
        /* translators: %s: Company abbreviation. */
        $html .= "<li>" . sprintf(__('<em>%s</em> reserves the right to modify these terms at any time. Users will be notified of any significant changes.', 'ai-for-seo'), AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION) . "</li>";
    $html .= "</ol>";

    return $html;
}

// =========================================================================================== \\

/**
 * Called via AJAX - On reject of the terms of service -> deactivate plugin
 * @return void
 */
function ai4seo_reject_tos() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check if we are in the admin area of WordPress
    if (!is_admin()) {
        return;
    }

    // perform the reject terms call
    ai4seo_robhub_api()->perform_reject_terms_call(AI4SEO_TOS_VERSION_TIMESTAMP);

    // uninstall the plugin
    ai4seo_deactivate_plugin();

    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Called via AJAX - On accept of the terms of service -> save the timestamp
 * @return void
 */
function ai4seo_accept_tos() {
    // Make sure that this function is only called once
    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check if we are in the admin area of WordPress
    if (!is_admin()) {
        return;
    }

    // check if we accepted tos, toc and pp before
    $tos_toc_and_pp_accepted_time = (int) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME);

    // check if we accepted enhanced reporting before
    $enhanced_reporting_accepted = (bool) ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED);

    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME, time());

    // handle enhanced reporting -> only save changes if we see the tos for the first time or the user has not accepted it before
    // not handling the save here only because the user did not see the checkbox in the modal
    if (!$tos_toc_and_pp_accepted_time || !$enhanced_reporting_accepted) {
        // check for $_POST["accepted_enhanced_reporting"]
        $enhanced_reporting_accepted = isset($_POST["accepted_enhanced_reporting"]) && $_POST["accepted_enhanced_reporting"] == "true";

        if ($enhanced_reporting_accepted) {
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED, true);
            ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME, time());
        }
    }

    // set tos accept details to database to share it with the maker of the plugin
    ai4seo_set_tos_accept_details($enhanced_reporting_accepted, "accepted tos, toc and pp");

    ai4seo_send_ajax_success();
}

// =========================================================================================== \\

/**
 * Set the ToS Acceptance details to the database
 * @param $accepted_enhanced_reporting bool Whether the user agreed to the extended data collection
 * @param $action string The action that was performed
 * @return void
 */
function ai4seo_set_tos_accept_details(bool $accepted_enhanced_reporting, string $action = "unknown") {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(287798693, 'Prevented loop', true);
        return;
    }

    // collect additional data and put it into the wp_option "AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS"
    $additional_tos_accept_details = array(
        "action" => sanitize_text_field($action),
        "website_url" => sanitize_text_field(get_site_url()),
        "website_name" => sanitize_text_field(get_bloginfo("name")),
        "email_address" => sanitize_email(ai4seo_get_option("admin_email")),
        "client_ip_address" => ai4seo_get_client_ip(),
        "server_ip_address" => ai4seo_get_server_ip(),
        "user_agent" => ai4seo_get_client_user_agent(),
        "tos_version" => AI4SEO_TOS_VERSION_TIMESTAMP,
        "timestamp" => time(),
        "accepted_extended_data_collection" => $accepted_enhanced_reporting ? "1" : "0"
    );

    $additional_tos_accept_details = ai4seo_deep_sanitize($additional_tos_accept_details);

    ai4seo_update_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME, $additional_tos_accept_details);
}

// =========================================================================================== \\

/**
 * Function to send the additional tos accept details, if available in the database
 * @return void
 */
function ai4seo_send_additional_tos_accept_details() {
    if (!ai4seo_is_user_inside_our_plugin_admin_pages()) {
        return;
    }

    // Make sure that the user is allowed to use this plugin
    if (!ai4seo_can_manage_this_plugin()) {
        return;
    }

    if (!ai4seo_singleton(__FUNCTION__)) {
        return;
    }

    // check in wp_options if we have additional tos accept details
    $additional_tos_accept_details = ai4seo_get_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME);
    $additional_tos_accept_details = ai4seo_deep_sanitize($additional_tos_accept_details);

    if (!$additional_tos_accept_details) {
        return;
    }

    $new_tos_details_checksum = ai4seo_get_array_checksum($additional_tos_accept_details);
    $last_tos_details_checksum = ai4seo_read_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM);

    // prevent re-sending the same data
    if ($new_tos_details_checksum === $last_tos_details_checksum) {
        // delete the additional tos accept details from the database
        ai4seo_delete_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME);
        return;
    }

    // prevent re-sending the same data using a timestamp of the last try
    // only allow to send this data once every 1 hour
    $tried_to_send_this_data_before_timestamp = (int) ai4seo_get_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME);

    if ($tried_to_send_this_data_before_timestamp && $tried_to_send_this_data_before_timestamp > (time() - 3600)) {
        return;
    }

    ai4seo_update_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME, time());

    // call robhub api endpoint "accept-terms" with the additional tos accept details
    $response = ai4seo_robhub_api()->call("client/accept-terms", $additional_tos_accept_details);

    //check response
    if (!ai4seo_robhub_api()->was_call_successful($response)) {
        ai4seo_debug_message(1712121224, 'Invalid response from RobHub API.', true);
        return;
    }

    // on success...
    // remove the additional tos accept details from the database
    ai4seo_delete_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME);
    ai4seo_delete_option(AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME);

    // save the checksum of the new tos details
    ai4seo_update_environmental_variable(AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM, $new_tos_details_checksum);
}


// endregion
// ___________________________________________________________________________________________ \\
// region LATEST ACTIVITY ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Function to add an entry to the latest activity log
 * @param int $post_id The post id this entry refers to
 * @param string $status The status of the action (success, error)
 * @param string $action The action that was performed ("metadata-manually-generated", "metadata-bulk-generated", "attachment-attributes-manually-generated", "attachment-attributes-bulk-generated")
 * @param int $cost The cost of the action in credits
 * @param string $details The details of the action
 * @return bool
 */
function ai4seo_add_latest_activity_entry(int $post_id, string $status, string $action, int $cost = 0, string $details = ""): bool {
    if (ai4seo_prevent_loops(__FUNCTION__)) {
        ai4seo_debug_message(581297488, 'Prevented loop', true);
        return false;
    }

    $new_entry["timestamp"] = time();

    // check if the post_id is a valid post id
    if ($post_id < 0) {
        ai4seo_debug_message(231410125, 'Invalid post id in latest activity log.', true);
        return false;
    }

    // check if the status is one of the allowed statuses (success, error)
    $status = sanitize_text_field($status);

    if (!in_array($status, array("success", "error"))) {
        return false;
    }

    $action = sanitize_text_field($action);

    if (!in_array($action, array("metadata-manually-generated", "metadata-bulk-generated", "attachment-attributes-manually-generated", "attachment-attributes-bulk-generated"))) {
        ai4seo_debug_message(241410125, 'Invalid action in latest activity log.', true);
        return false;
    }

    // check if the cost is valid
    if ($cost < 0) {
        ai4seo_debug_message(251410125, 'Invalid cost in latest activity log.', true);
        return false;
    }

    // read additional post data
    $post_type = get_post_type($post_id);

    // check post type (alphanumeric, - and _ allowed)
    $post_type = sanitize_text_field($post_type);

    if (!preg_match("/^[a-zA-Z0-9_-]+$/", $post_type)) {
        return false;
    }

    // check title
    $title = sanitize_text_field(get_the_title($post_id));
    $title = ai4seo_mb_substr($title, 0 , 50);
    $title = sanitize_text_field($title);

    if ($post_type == "attachment") {
        $url = get_edit_post_link($post_id);
    } else {
        $url = get_permalink($post_id);
    }

    if (!$url) {
        $url = "";
    }

    // check url
    $url = sanitize_url($url);

    if ($url) {
        $url = esc_url($url);
    }

    // check details
    $details = sanitize_text_field($details);

    // build the new entry
    $new_entry = array(
        "timestamp" => time(),
        "post_id" => $post_id,
        "post_type" => $post_type,
        "status" => $status,
        "action" => $action,
        "cost" => $cost,
        "title" => $title,
        "url" => $url,
        "details" => $details
    );

    // read the latest activity logs
    $latest_activity = ai4seo_get_option(AI4SEO_LATEST_ACTIVITY_OPTION_NAME, array());

    // add the new entry
    array_unshift($latest_activity, $new_entry);

    // remove the oldest entry if necessary
    if (count($latest_activity) >= AI4SEO_MAX_LATEST_ACTIVITY_LOGS) {
        array_pop($latest_activity);
    }

    // save the new latest activity logs
    ai4seo_update_option(AI4SEO_LATEST_ACTIVITY_OPTION_NAME, $latest_activity);

    return true;
}


// endregion
// ___________________________________________________________________________________________ \\
// region PAY AS YOU GO ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_send_pay_as_you_go_settings(): bool {
    if (!ai4seo_singleton(__FUNCTION__)) {
        return false;
    }

    // call robhub api endpoint "payg-settings" with current payg settings
    $robhub_endpoint = "client/payg-settings";

    $payg_settings = array();
    $payg_settings[AI4SEO_SETTING_PAYG_ENABLED] = (bool) ai4seo_get_setting(AI4SEO_SETTING_PAYG_ENABLED);
    $payg_settings[AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID] = ai4seo_get_setting(AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID);
    $payg_settings[AI4SEO_SETTING_PAYG_DAILY_BUDGET] = (int) ai4seo_get_setting(AI4SEO_SETTING_PAYG_DAILY_BUDGET);
    $payg_settings[AI4SEO_SETTING_PAYG_MONTHLY_BUDGET] = (int) ai4seo_get_setting(AI4SEO_SETTING_PAYG_MONTHLY_BUDGET);
    $payg_settings = ai4seo_deep_sanitize($payg_settings);

    $response = ai4seo_robhub_api()->call($robhub_endpoint, $payg_settings);

    // check response
    if (!ai4seo_robhub_api()->was_call_successful($response)) {
        ai4seo_debug_message(361217325, 'Invalid response from RobHub API.', true);
        return false;
    }

    // remove potential previous error notification
    ai4seo_remove_notification("payg-status-error");

    return true;
}
