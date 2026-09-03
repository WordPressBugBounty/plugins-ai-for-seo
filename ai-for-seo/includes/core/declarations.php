<?php
/**
 * Defines plugin constants, defaults, and static registries.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region CONSTANTS AND VARIABLES ============================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

// Centralize plugin identity and asset-cache declarations consumed throughout bootstrap.
const AI4SEO_PLUGIN_VERSION_NUMBER              = '2.5.2';
const AI4SEO_PLUGIN_NAME                        = 'SOOZ - AI for SEO';
const AI4SEO_SHORT_PLUGIN_NAME                  = 'SOOZ';
const AI4SEO_PLUGIN_DESCRIPTION                 = 'One-Click SEO solution. *SOOZ - AI for SEO* helps your website to rank higher in Web Search results.';
const AI4SEO_PLUGIN_IDENTIFIER                  = 'ai-for-seo';
const AI4SEO_ASSET_REFRESH_QUERY_PARAMETER      = 'ai4seo_asset_refresh';
const AI4SEO_PLUGIN_AUTHOR_COMPANY_NAME         = 'Andre Erbis, Space Codes';
const AI4SEO_PLUGIN_AUTHOR_COMPANY_ABBREVIATION = 'AESC';
const AI4SEO_DEFAULT_FALLBACK_LANGUAGE          = 'english';
const AI4SEO_POST_PARAMETER_PREFIX              = 'ai4seo_';
const AI4SEO_TOS_VERSION_TIMESTAMP              = 1767426000;
const AI4SEO_TOO_SHORT_CONTENT_LENGTH           = 100;
const AI4SEO_MAX_TOTAL_CONTENT_SIZE             = 5000;
// Keep manual and automated soft selection aligned to the same maximum delivery dimensions.
const AI4SEO_ATTACHMENT_GENERATION_MAX_IMAGE_DIMENSION = 2048;
// Keep conversion logic and coverage tied to the same bounded model-input policy.
const AI4SEO_ATTACHMENT_GENERATION_TARGET_IMAGE_SIZE_BYTES = 100000;
const AI4SEO_ATTACHMENT_GENERATION_DERIVATIVE_QUALITY      = 75;
const AI4SEO_ATTACHMENT_GENERATION_MAX_ENCODING_ATTEMPTS   = 4;
// Keep base64 media input aligned with RobHub's URL-fetch size limit so fallback cannot bypass server safeguards.
const AI4SEO_MAX_BASE64_ATTACHMENT_SOURCE_SIZE_BYTES      = 26214400; // 25 MB
const AI4SEO_SUPPORT_EMAIL                                = 'support@sooz.ai';
const AI4SEO_OFFICIAL_WEBSITE                             = 'https://sooz.ai';
const AI4SEO_OFFICIAL_PRICING_URL                         = 'https://sooz.ai/pricing';
const AI4SEO_OFFICIAL_CONTACT_URL                         = 'https://sooz.ai/contact';
const AI4SEO_TERMS_AND_CONDITIONS_URL                     = 'https://sooz.ai/terms-and-conditions#plugin';
const AI4SEO_PRIVACY_POLICY_URL                           = 'https://sooz.ai/privacy-policy#plugin';
const AI4SEO_PLUGINS_OFFICIAL_WORDPRESS_ORG_PAGE          = 'https://wordpress.org/plugins/ai-for-seo/';
const AI4SEO_OFFICIAL_RATE_US_URL                         = 'https://sooz.ai/rate-us';
const AI4SEO_OPENAI_URL                                   = 'https://openai.com';
const AI4SEO_OPENAI_TERMS_OF_USE_URL                      = 'https://openai.com/terms';
const AI4SEO_ROBHUB_ENVIRONMENTAL_VARIABLES_OPTION_NAME   = '_ai4seo_robhub_environmental_variables';
const AI4SEO_ENVIRONMENTAL_VARIABLES_OPTION_NAME          = '_ai4seo_environmental_variables';
const AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME        = '_ai4seo_generation_status_summary';
const AI4SEO_GENERATION_STATUS_SUMMARY_TOTALS_OPTION_NAME = '_ai4seo_generation_status_summary_totals';
const AI4SEO_DISABLED_QUEUE_INSPECTION_STATE_OPTION_NAME  = 'ai4seo_disabled_queue_inspection_state';
const AI4SEO_AUTO_RETRY_FAILED_REQUIRED_OPTION_NAME       = '_ai4seo_auto_retry_failed_required';
const AI4SEO_CONTENT_TYPE_LIST_CACHE_VERSION_OPTION_NAME  = '_ai4seo_content_type_list_cache_version';
const AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME             = '_ai4seo_posts_to_be_analyzed';
const AI4SEO_NOTIFICATIONS_OPTION_NAME                    = '_ai4seo_notifications';

// Authenticated provenance prevents API metadata from selecting the richer trusted-local HTML policy.
const AI4SEO_NOTIFICATION_MESSAGE_SOURCE_FIELD           = 'message_source';
const AI4SEO_NOTIFICATION_MESSAGE_SOURCE_SIGNATURE_FIELD = 'message_source_signature';
const AI4SEO_NOTIFICATION_MESSAGE_SOURCE_LOCAL           = 'local';
const AI4SEO_NOTIFICATION_MESSAGE_SOURCE_REMOTE          = 'remote';

// Bound repair and mutation retries so persistent concurrency cannot monopolize a request.
const AI4SEO_NOTIFICATION_CAS_MAX_ATTEMPTS = 3;

// Keep general option and post-meta identifiers separate from notification provenance policy.
const AI4SEO_DEBUG_MESSAGES_OPTION_NAME              = 'ai4seo_debug_messages';
const AI4SEO_SETTINGS_OPTION_NAME                    = 'ai4seo_settings';
const AI4SEO_POST_META_GENERATED_DATA_META_KEY       = 'ai4seo_generated_data';
const AI4SEO_POST_META_ACTIVE_METADATA_META_KEY      = 'ai4seo_active_metadata';
const AI4SEO_POST_META_POST_CONTENT_SUMMARY_META_KEY = 'ai4seo_content_summary';
const AI4SEO_POST_META_RELATED_POST_ID_META_KEY      = 'ai4seo_related_post_id';

// Entry-level custom instructions are stored separately from generated data and active metadata.
const AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY              = 'ai4seo_metadata_custom_instructions';
const AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY = 'ai4seo_attachment_attributes_custom_instructions';

const AI4SEO_STYLES_HANDLE               = 'ai-for-seo-styles';
const AI4SEO_SCRIPTS_HANDLE              = 'ai-for-seo-scripts';
const AI4SEO_INJECTION_SCRIPTS_HANDLE    = 'ai-for-seo-injection-scripts';
const AI4SEO_STYLES_FILE_NAME            = 'ai-for-seo-styles-' . AI4SEO_PLUGIN_VERSION_NUMBER . '.css';
const AI4SEO_SCRIPTS_FILE_NAME           = 'ai-for-seo-scripts-' . AI4SEO_PLUGIN_VERSION_NUMBER . '.js';
const AI4SEO_INJECTION_SCRIPTS_FILE_NAME = 'ai-for-seo-alt-text-injection-' . AI4SEO_PLUGIN_VERSION_NUMBER . '.js';
// Keep PHP context validation and the localized JavaScript dispatcher on one stable vocabulary.
const AI4SEO_PRIMARY_ASSET_CONTEXTS                     = array(
	'plugin-ui',
	'frontend-metadata-editor',
	'content-list',
	'post-editor',
	'external-media',
	'plugin-deactivation',
	'tos-gate',
);
const AI4SEO_VERY_LOW_CREDITS_THRESHOLD                 = 10;
const AI4SEO_LOW_CREDITS_THRESHOLD                      = 40;
const AI4SEO_CUSTOM_PLAN_DISCOUNT                       = 30; // in percent.
const AI4SEO_DAILY_FREE_CREDITS_AMOUNT                  = 5;
const AI4SEO_MONEY_BACK_GUARANTEE_DAYS                  = 14;
const AI4SEO_MAX_LATEST_ACTIVITY_LOGS                   = 10;
const AI4SEO_BLUE_GET_MORE_CREDITS_BUTTON_THRESHOLD     = 100;
const AI4SEO_GIVING_FEEDBACK_CREDITS                    = 100;
const AI4SEO_GIVING_FEEDBACK_DISCOUNT                   = 10; // in percent.
const AI4SEO_NEXTGEN_GALLERY_POST_TYPE                  = 'ai4seo_ngg';
const AI4SEO_MAX_DISPLAYABLE_ALREADY_READ_NOTIFICATIONS = 2;
const AI4SEO_ANALYZE_PERFORMANCE_INTERVAL               = 7200; // 2h
const AI4SEO_GLOBAL_NONCE_IDENTIFIER                    = 'ai4seo_ajax_nonce';
// These values form the single-use checkout-return handshake shared by URL generation and validation.
const AI4SEO_PURCHASE_RETURN_QUERY_PARAMETER               = 'ai4seo-just-purchased';
const AI4SEO_PURCHASE_RETURN_TOKEN_QUERY_PARAMETER         = 'ai4seo_purchase_return_token';
const AI4SEO_PURCHASE_RETURN_TOKEN_OPTION_PREFIX           = 'ai4seo_purchase_return_token_';
const AI4SEO_PURCHASE_RETURN_TOKEN_EXPIRY_CRON_HOOK        = 'ai4seo_expire_purchase_return_token';
const AI4SEO_PURCHASE_RETURN_TOKEN_TTL_SECONDS             = 604800; // 7 days.
const AI4SEO_PAYG_CREDITS_THRESHOLD                        = 100;
const AI4SEO_ALLOWED_PAYG_STATUS                           = array( 'idle', 'budget-limit-reached', 'processing', 'payment-pending', 'payment-received', 'payment-failed', 'payment-method-failed', 'error' );
const AI4SEO_SEMAPHORE_MAX_WAIT_SECONDS                    = 5; // 5 seconds
const AI4SEO_SEMAPHORE_POLL_INTERVAL_SECONDS               = .1; // .1 seconds
const AI4SEO_SEMAPHORE_TTL_SECONDS                         = 30; // 30 seconds
const AI4SEO_POST_TABLE_ANALYSIS_BATCH_SIZE                = 10000; // number of posts to analyze per batch.
const AI4SEO_POST_TABLE_ANALYSIS_MAX_EXECUTION_TIME        = 2; // maximum execution time in seconds per batch.
const AI4SEO_POST_TABLE_ANALYSIS_SLEEP_BETWEEN_RUNS        = 100000; // microseconds to sleep between runs.
const AI4SEO_POST_TABLE_ANALYSIS_PROCESSING_TIMEOUT        = 90; // seconds.
const AI4SEO_LARGE_SITE_POSTS_THRESHOLD                    = 50000;
const AI4SEO_LARGE_SITE_AUTOMATIC_ANALYSIS_INTERVAL        = 86400; // 24h
const AI4SEO_DEEP_CONTEXT_SEARCH_POSTMETA_THRESHOLD        = 1000000;
const AI4SEO_DEEP_CONTEXT_SEARCH_MAX_TIMEOUTS_PER_CRON_RUN = 3;
// Keep neutral SQL date bindings inside MySQL's supported DATETIME range under strict modes.
const AI4SEO_MYSQL_DATETIME_MINIMUM = '1000-01-01 00:00:00';

// Custom instruction limits are tied to subscription status rather than one-off credit balances.
const AI4SEO_CUSTOM_INSTRUCTIONS_FREE_LENGTH_LIMIT         = 200;
const AI4SEO_CUSTOM_INSTRUCTIONS_SUBSCRIPTION_LENGTH_LIMIT = 1000;

const AI4SEO_CRON_JOBS_ENABLED = true; // set to true to enable cron jobs, false to disable them.

$GLOBALS['ai4seo_held_semaphores'] = isset( $GLOBALS['ai4seo_held_semaphores'] ) && is_array( $GLOBALS['ai4seo_held_semaphores'] )
	? $GLOBALS['ai4seo_held_semaphores']
	: array();

$GLOBALS['ai4seo_ajax_nonce'] = '';

const AI4SEO_MAX_EDITOR_INPUT_LENGTHS = array(
	'focus-keyphrase'      => 128,
	'meta-title'           => 205,
	'meta-description'     => 505,
	'keywords'             => 512,
	'facebook-title'       => 205,
	'facebook-description' => 375,
	'twitter-title'        => 205,
	'twitter-description'  => 375,
	'title'                => 256,
	'alt-text'             => 390,
	'caption'              => 256,
	'description'          => 512,
	// Reserve filename capacity here so API-generated names use the same immutable cap registry as editor fields.
	'file-name'            => 255,
	'fallback'             => 512,
);

// Mirror API prompt targets for save-time diagnostics; misses stay usable while the hard caps above remain authoritative.
const AI4SEO_GENERATED_OUTPUT_QUALITY_WINDOWS = array(
	'metadata'              => array(
		'meta-title'           => array(
			'min-length' => 45,
			'max-length' => 65,
		),
		'meta-description'     => array(
			'min-length' => 130,
			'max-length' => 160,
		),
		'keywords'             => array(
			'max-items' => 10,
		),
		'facebook-title'       => array(
			'min-length' => 45,
			'max-length' => 65,
		),
		'facebook-description' => array(
			'min-length' => 90,
			'max-length' => 120,
		),
		'twitter-title'        => array(
			'min-length' => 45,
			'max-length' => 65,
		),
		'twitter-description'  => array(
			'min-length' => 90,
			'max-length' => 125,
		),
	),
	'attachment_attributes' => array(
		'title'       => array(
			'min-length' => 50,
			'max-length' => 80,
		),
		'alt-text'    => array(
			'min-length' => 95,
			'max-length' => 125,
		),
		'caption'     => array(
			'min-length' => 130,
			'max-length' => 160,
		),
		'description' => array(
			'min-length' => 180,
			'max-length' => 240,
		),
		'file-name'   => array(
			'min-length' => 40,
			'max-length' => 80,
		),
	),
);

const AI4SEO_FOCUS_KEYPHRASE_RECOMMENDED_MAX_LENGTH  = 30;
const AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MIN_ITEMS = 5;
const AI4SEO_METADATA_KEYWORDS_RECOMMENDED_MAX_ITEMS = 10;


/**
 * Return the plugin change log.
 *
 * @return array[] the change log of the plugin
 */
function ai4seo_get_change_log(): array {
	return array(
		array(
			'date'      => 'September 3rd, 2026',
			'version'   => '2.5.2',
			'important' => false,
			'updates'   => array(
				'Improved account recovery and purchase setup so license credentials are refreshed safely and subscription or credit checkout starts more reliably.',
				'Added clearer Latest Activity details that show which metadata or media fields were generated, with direct WordPress edit links from content lists.',
				'Improved the Metadata Editor so drafts can save manual changes and field removals reliably before publication.',
				'Improved Settings and bulk-action controls so actions activate only after meaningful changes and valid selections, with a simpler accessible Advanced Settings toggle.',
				'Improved automatic repair of legacy generation queues and saved data so older site state no longer interrupts analysis.',
				'Bug Fixes & Maintenance: Fixed 4 minor bugs and addressed 2 security issues.',
			),
		),
		array(
			'date'      => 'August 26th, 2026',
			'version'   => '2.5.1',
			'important' => false,
			'updates'   => array(
				'Added direct SOOZ metadata-editor controls for Elementor and the WordPress Block Editor, with more reliable initialization for dynamically loaded editor fields.',
				'Added AVIF support for media attribute generation, including automatic preparation of compatible image data.',
				'Improved media attribute saving and coverage tracking so valid existing image text stays intact and complete entries are recognized reliably.',
				'Improved role-based access so permitted editors can use SOOZ only for content they are allowed to edit.',
				'Bug Fixes & Maintenance: Fixed 5 minor bugs, implemented 1 performance improvement, and addressed 2 security issues.',
			),
		),
		array(
			'date'      => 'August 17th, 2026',
			'version'   => '2.5.0',
			'important' => true,
			'updates'   => array(
				'Added preview-first Metadata and Media Attributes editors with responsive Google, social, keyword, accessibility, and media appearance previews, field-specific edit shortcuts, and live quality guidance.',
				'Added a setting to choose whether Preview or Editor mode opens by default while preserving Editor mode for existing users.',
				'Improved "Generate with SOOZ" controls across the WordPress editor, Media Library, frontend page builders, and supported third-party SEO editors so dynamically loaded fields and frames initialize more reliably.',
				'Added AVIF support for media attribute generation by preparing compatible image data automatically.',
				'Improved SEO Autopilot with consistent new-versus-existing date filters, clearer paused-state feedback, and contextual descriptions for bulk actions.',
				'Changed third-party SEO synchronization on fresh installations to opt-in while preserving existing sites\' current synchronization choices.',
				'Improved the Get More Credits flow with reliable on-demand access to subscriptions, credit packs, Pay-As-You-Go, and free-credit options.',
				'Improved Help and account experiences with accessible FAQ accordions, direct Help-section links, and WordPress-language-aware account synchronization.',
				'Bug Fixes & Maintenance: Fixed 5 minor bugs and implemented 2 performance improvements.',
			),
		),
		array(
			'date'      => 'August 10th, 2026',
			'version'   => '2.4.3',
			'important' => false,
			'updates'   => array(
				'Improved third-party SEO workflows with embedded generation controls, two-way metadata synchronization where supported, template-aware values, and live editor refreshes across Yoast SEO, Rank Math, All in One SEO, SEOPress, Slim SEO, SEO Simple Pack, Squirrly SEO, The SEO Framework, and SEOKEY.',
				'Improved metadata generation accuracy for Gutenberg and builder-driven pages by analyzing visible local content and avoiding unrelated fallback text on sparse or structural pages.',
				'Strengthened role-based access so permitted editors can generate and manage metadata or media attributes only for content they are allowed to edit.',
				'Improved metadata editor usability with live length guidance, missing-keyphrase warnings, clearer accessible actions and navigation, and more reliable saved-state feedback.',
				'Removed the legacy Blog2Social integration from the supported compatibility list.',
				'Bug Fixes & Maintenance: Fixed 6 minor bugs and addressed 2 security issues.',
			),
		),
		array(
			'date'      => 'July 29th, 2026',
			'version'   => '2.4.2',
			'important' => false,
			'updates'   => array(
				'Improved media attribute generation reliability by automatically recovering with direct image data when a remote image URL cannot be accessed.',
				'Improved dashboard and SEO Autopilot status accuracy and performance with incremental background updates and clearer, accessible progress details.',
				'Added sticky section navigation to Settings so longer configuration pages are easier to move through.',
				'Bug Fixes & Maintenance: Fixed 8 minor bugs, implemented 2 performance improvements, and addressed 1 security issue.',
			),
		),
		array(
			'date'      => 'July 24th, 2026',
			'version'   => '2.4.1',
			'important' => false,
			'updates'   => array(
				'Added experimental generation-length sliders for meta titles and descriptions, social metadata, and image alt text, available under Advanced Settings.',
				'Added bundled translations for Arabic, Brazilian Portuguese, Dutch, French, Italian, Japanese, Polish, Russian, Spanish, and Swedish.',
				'Improved the quality and reliability of generated metadata and media attributes with field-by-field validation and targeted retries for incomplete or unsuitable results.',
				'Improved Custom Instructions so compatible preferences are applied without overriding configured languages, field formats, prefixes, suffixes, quality limits, factual accuracy, or safety requirements.',
				'Improved usability and accessibility across dashboards, editors, settings, tooltips, notifications, and account controls.',
				'Bug Fixes & Maintenance: Improved settings saving and error feedback, dashboard refreshes, frontend alt-text handling, selection controls, notifications, and security checks.',
			),
		),
		array(
			'date'      => 'July 3rd, 2026',
			'version'   => '2.4.0',
			'important' => true,
			'updates'   => array(
				'Improved media context detection for better image attributes by finding featured images, uploaded child media, WooCommerce variation images, galleries, local image URLs, and media stored by page builders or custom fields.',
				'Added Metadata prompt sliders so users can tune tone, keyword intensity, focus keyphrase influence, CTA style, social variation, brand context, and existing-value reference strength.',
				'Added Attachment Attributes prompt sliders so users can tune visual tone, surrounding context, file name influence, keyword intensity, recognizable entity handling, brand context, and existing-value reference strength.',
				'Added custom instruction fields for global generation behavior, metadata, post types, media attributes, and individual editor entries so users can guide future AI generations more precisely.',
				'Added WPML language controls so site owners can exclude specific languages from SOOZ dashboards and SEO Autopilot for metadata or media attributes.',
				'Improved image attribute generation reliability by retrying with direct image data when an image URL cannot be accessed.',
				'Added an "Auto Queue Entries" setting so SEO Autopilot can either automatically queue applicable entries or process only manually queued entries.',
				'Added bulk actions to add selected entries to the queue safely, force regeneration, and remove selected entries from the queue.',
				'Added bulk actions to add all related images to the queue, force regeneration for related images, or remove related images from the queue.',
				'Improved SOOZ post and media lists with status counts, hidden and waiting queue states, WPML language filters, text search, sortable columns, and bulk queue controls.',
				'Improved dashboard and list feedback for SEO Autopilot, including clearer queue, processing, insufficient-credit, and manual-queue status messages.',
				'Added a "Related Media" workflow that shows media connected to a post, page, or product.',
				'Added a General setting to show SOOZ bulk actions in native WordPress post, page, product, and Media Library tables when needed.',
				'Added bulk actions to hide selected entries from SOOZ lists or show hidden entries again.',
				'Added bulk actions to exclude selected entries from the Auto Queue feature or allow them again.',
				'Added bulk actions to mark selected entries as not generated or delete saved SOOZ metadata.',
				'Added a bulk action to set or clear custom instructions for selected posts, pages, products, and media files.',
				'Added source hints in the Metadata and Media Attributes editors so users can see whether field values were generated by SOOZ, imported from SEO plugins, or edited afterward.',
				'Added editor notices for inactive meta tags and media attributes, including a direct link to the settings that can show them again.',
				'Added a Metadata Editor warning when an entry has title or description data but no focus keyphrase, helping users generate related metadata together.',
				'Improved settings import/export with JSON files, category selection, preview before import, and clearer feedback for skipped or invalid settings.',
				'Improved settings imports from older plugin versions so exported settings are migrated more safely into 2.4.0 prompt controls.',
				'Improved the Credits Pack selection flow with recommended packs, cost-per-page and cost-per-image estimates, and a custom-plan prompt for large websites.',
				'Improved Pay-As-You-Go setup with clearer budget guidance and an explicit confirmation before enabling automatic refills.',
				'Improved billing guidance before plugin deactivation, including warnings that deactivation does not cancel subscriptions or Pay-As-You-Go refills.',
				'Bug Fixes & Maintenance: Improved API error messages, strengthened AJAX validation and permission checks, optimized large-site background analysis, and fixed minor UI issues.',
			),
		),
		array(
			'date'      => 'March 9th, 2026',
			'version'   => '2.3.0',
			'important' => true,
			'updates'   => array(
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
			),
		),
		array(
			'date'      => 'November 14th, 2025',
			'version'   => '2.2.0',
			'important' => true,
			'updates'   => array(
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
				'Bug Fixes & Maintenance: Fixed 21 minor bugs, implemented 29 usability improvements, implemented 35 stability improvements, and resolved 8 security issues.',
			),
		),
		array(
			'date'      => 'August 3rd, 2025',
			'version'   => '2.1.0',
			'important' => true,
			'updates'   => array(
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
				// 'The plugin now indicates posts, pages, and attachments correctly when ignored by the SEO Autopilot, fully respecting the user\'s selection and settings.',
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
				'Bug Fixes & Maintenance: Fixed 17 minor bugs, added 6 quality-of-life improvements, implemented 3 performance optimizations, and 2 security updates.',
			),
		),
		array(
			'date'      => 'March 20th, 2025',
			'version'   => '2.0.0',
			'important' => true,
			'updates'   => array(
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
				'Tons more minor improvements, bug fixes, and performance enhancements.',
			),
		),
	);
}

/**
 * Function to return the credits packs available for purchase
 *
 * @return array[]
 */
function ai4seo_get_credits_packs(): array {
	$credits_packs = array(
		'price_1S6ThfHNyvfVK0r9KimFGz1E' => array(
			'credits_amount'      => 500,
			'price_usd'           => 9,
			'reference_price_usd' => 9,
			'price_eur'           => 8,
			'reference_price_eur' => 8,
			'stripe_product_id'   => 'prod_RD8C2kl2gPqozh',
			'stripe_payment_link' => 'https://buy.stripe.com/5kA00X7yF5Rc3BK8ww',
		),
		'price_1S6TjQHNyvfVK0r9WttAsfP9' => array(
			'credits_amount'      => 1500,
			'price_usd'           => 19,
			'reference_price_usd' => 19,
			'price_eur'           => 16,
			'reference_price_eur' => 16,
			'stripe_product_id'   => 'prod_RD8JI7ELrXPSWg',
			'stripe_payment_link' => '',
		),
		'price_1S6TlCHNyvfVK0r9s0CZ3z1Z' => array(
			'credits_amount'      => 5000,
			'price_usd'           => 49,
			'reference_price_usd' => 49,
			'price_eur'           => 45,
			'reference_price_eur' => 45,
			'stripe_product_id'   => 'prod_RD8KgysYBIyi2Z',
			'stripe_payment_link' => '',
		),
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only feature-preview flag; no state is changed.
	if ( isset( $_GET['ai4seo_show_all_credits_packs'] ) ) {
		$credits_packs += array(
			'price_1SjAj1HNyvfVK0r9TkVWQsJE' => array(
				'credits_amount'      => 15000,
				'price_usd'           => 129,
				'reference_price_usd' => 129,
				'price_eur'           => 109,
				'reference_price_eur' => 109,
				'stripe_product_id'   => 'prod_RD8LcGkIHN7O0K',
				'stripe_payment_link' => '',
			),
			'price_1SjAk2HNyvfVK0r9NJDtHuRJ' => array(
				'credits_amount'      => 50000,
				'price_usd'           => 349,
				'reference_price_usd' => 349,
				'price_eur'           => 299,
				'reference_price_eur' => 299,
				'stripe_product_id'   => 'prod_RD8LWAmW1fQ32n',
				'stripe_payment_link' => '',
			),
		);
	}

	return $credits_packs;
}

/**
 * Return the SVG icons used in the plugin.
 *
 * @return string[] associative array with icon names as keys and SVG strings as values
 */
function ai4seo_get_svg_tags(): array {
	return array(
		'ai-for-seo-main-menu-icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 500.000000 500.000000"><g transform="translate(0.000000,500.000000) scale(0.100000,-0.100000)" fill="#a7aaad" stroke="none"><path d="M2145 4755 l7 -245 -843 -2 -844 -3 -3 -827 -2 -828 -230 0 -230 0 0 -345 0 -345 230 0 230 0 2 -22 c1 -13 2 -387 3 -833 l0 -810 838 -3 837 -2 0 -245 0 -245 350 0 350 0 0 245 0 245 840 0 840 0 2 308 c1 169 1 539 0 822 -1 283 1 522 3 530 4 13 38 15 240 12 l235 -3 0 345 0 346 -237 2 -238 3 -3 828 -2 827 -840 0 -840 0 0 245 0 245 -351 0 -351 0 7 -245z m344 -1143 c5 -10 21 -63 35 -118 36 -142 104 -375 110 -382 10 -10 18 6 31 60 8 29 32 125 55 213 24 88 45 174 47 190 3 17 10 36 15 43 8 9 124 12 519 12 401 0 509 -3 509 -12 -1 -7 1 -168 4 -358 4 -251 8 -1595 6 -1902 0 -17 -43 -18 -754 -18 l-754 0 -97 145 c-54 80 -101 145 -104 145 -4 0 -25 -24 -46 -52 -47 -63 -150 -196 -170 -220 -14 -17 -48 -18 -463 -18 -247 0 -451 3 -455 6 -3 3 2 29 13 58 10 28 34 103 55 166 124 393 661 2012 677 2043 8 16 37 17 383 17 351 0 375 -1 384 -18z"/><path d="M1907 3088 c-102 -299 -189 -553 -247 -718 -63 -179 -195 -563 -210 -613 l-9 -28 141 3 141 3 41 125 41 125 296 3 296 2 39 -130 38 -130 143 0 c79 0 143 2 143 4 0 6 -80 240 -115 336 -14 41 -51 143 -80 225 -29 83 -70 195 -90 250 -37 102 -235 667 -235 672 0 2 -65 3 -144 3 l-143 0 -46 -132z m203 -241 c0 -8 43 -143 95 -302 52 -158 95 -294 95 -301 0 -11 -38 -14 -205 -14 -136 0 -205 4 -205 10 0 25 202 620 211 620 5 0 9 -6 9 -13z"/><path d="M3126 2484 c-3 -404 -4 -740 -1 -745 4 -5 67 -9 141 -9 l134 0 0 745 0 745 -133 0 -134 0 -7 -736z"/></g></svg> ',
		'all-in-one-seo'             => '<svg viewBox="0 0 20 20" width="16" height="16" fill="#a7aaad" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.98542 19.9708C15.5002 19.9708 19.9708 15.5002 19.9708 9.98542C19.9708 4.47063 15.5002 0 9.98542 0C4.47063 0 0 4.47063 0 9.98542C0 15.5002 4.47063 19.9708 9.98542 19.9708ZM8.39541 3.65464C8.26016 3.4485 8.0096 3.35211 7.77985 3.43327C7.51816 3.52572 7.26218 3.63445 7.01349 3.7588C6.79519 3.86796 6.68566 4.11731 6.73372 4.36049L6.90493 5.22694C6.949 5.44996 6.858 5.6763 6.68522 5.82009C6.41216 6.04734 6.16007 6.30426 5.93421 6.58864C5.79383 6.76539 5.57233 6.85907 5.35361 6.81489L4.50424 6.6433C4.26564 6.5951 4.02157 6.70788 3.91544 6.93121C3.85549 7.05738 3.79889 7.1862 3.74583 7.31758C3.69276 7.44896 3.64397 7.58105 3.59938 7.71369C3.52048 7.94847 3.61579 8.20398 3.81839 8.34133L4.53958 8.83027C4.72529 8.95617 4.81778 9.1819 4.79534 9.40826C4.75925 9.77244 4.76072 10.136 4.79756 10.4936C4.82087 10.7198 4.72915 10.9459 4.54388 11.0724L3.82408 11.5642C3.62205 11.7022 3.52759 11.9579 3.60713 12.1923C3.69774 12.4593 3.8043 12.7205 3.92615 12.9743C4.03313 13.1971 4.27749 13.3088 4.51581 13.2598L5.36495 13.0851C5.5835 13.0401 5.80533 13.133 5.94623 13.3093C6.16893 13.5879 6.42071 13.8451 6.6994 14.0756C6.87261 14.2188 6.96442 14.4448 6.92112 14.668L6.75296 15.5348C6.70572 15.7782 6.81625 16.0273 7.03511 16.1356C7.15876 16.1967 7.285 16.2545 7.41375 16.3086C7.54251 16.3628 7.67196 16.4126 7.80195 16.4581C8.18224 16.5912 8.71449 16.1147 9.108 15.7625C9.30205 15.5888 9.42174 15.343 9.42301 15.0798C9.42301 15.0784 9.42302 15.077 9.42302 15.0756L9.42301 13.6263C9.42301 13.6109 9.4236 13.5957 9.42476 13.5806C8.26248 13.2971 7.39838 12.2301 7.39838 10.9572V9.41823C7.39838 9.30125 7.49131 9.20642 7.60596 9.20642H8.32584V7.6922C8.32584 7.48312 8.49193 7.31364 8.69683 7.31364C8.90171 7.31364 9.06781 7.48312 9.06781 7.6922V9.20642H11.0155V7.6922C11.0155 7.48312 11.1816 7.31364 11.3865 7.31364C11.5914 7.31364 11.7575 7.48312 11.7575 7.6922V9.20642H12.4773C12.592 9.20642 12.6849 9.30125 12.6849 9.41823V10.9572C12.6849 12.2704 11.7653 13.3643 10.5474 13.6051C10.5477 13.6121 10.5478 13.6192 10.5478 13.6263L10.5478 15.0694C10.5478 15.3377 10.6711 15.5879 10.871 15.7622C11.2715 16.1115 11.8129 16.5837 12.191 16.4502C12.4527 16.3577 12.7086 16.249 12.9573 16.1246C13.1756 16.0155 13.2852 15.7661 13.2371 15.5229L13.0659 14.6565C13.0218 14.4334 13.1128 14.2071 13.2856 14.0633C13.5587 13.8361 13.8107 13.5792 14.0366 13.2948C14.177 13.118 14.3985 13.0244 14.6172 13.0685L15.4666 13.2401C15.7052 13.2883 15.9493 13.1756 16.0554 12.9522C16.1153 12.8261 16.1719 12.6972 16.225 12.5659C16.2781 12.4345 16.3269 12.3024 16.3714 12.1698C16.4503 11.935 16.355 11.6795 16.1524 11.5421L15.4312 11.0532C15.2455 10.9273 15.153 10.7015 15.1755 10.4752C15.2116 10.111 15.2101 9.74744 15.1733 9.38986C15.1499 9.16361 15.2417 8.93757 15.4269 8.811L16.1467 8.31927C16.3488 8.18126 16.4432 7.92558 16.3637 7.69115C16.2731 7.42411 16.1665 7.16292 16.0447 6.90915C15.9377 6.68638 15.6933 6.57462 15.455 6.62366L14.6059 6.79837C14.3873 6.84334 14.1655 6.75048 14.0246 6.57418C13.8019 6.29554 13.5501 6.03832 13.2714 5.80784C13.0982 5.6646 13.0064 5.43858 13.0497 5.2154L13.2179 4.34868C13.2651 4.10521 13.1546 3.85616 12.9357 3.74787C12.8121 3.68669 12.6858 3.62895 12.5571 3.5748C12.4283 3.52065 12.2989 3.47086 12.1689 3.42537C11.9388 3.34485 11.6884 3.44211 11.5538 3.64884L11.0746 4.38475C10.9513 4.57425 10.73 4.66862 10.5082 4.64573C10.1513 4.6089 9.79502 4.61039 9.44459 4.64799C9.22286 4.67177 9.00134 4.57818 8.87731 4.38913L8.39541 3.65464Z" fill="#a7aaad" /></svg>',
		'angle-down'                 => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M201.4 374.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 306.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"/></svg>',
		'arrow-right'                => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"/></svg>',
		'arrow-up-right-from-square' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32h82.7L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3V192c0 17.7 14.3 32 32 32s32-14.3 32-32V32c0-17.7-14.3-32-32-32H320zM80 32C35.8 32 0 67.8 0 112V432c0 44.2 35.8 80 80 80H400c44.2 0 80-35.8 80-80V320c0-17.7-14.3-32-32-32s-32 14.3-32 32V432c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16H192c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/></svg>',
		'bars-sort'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!-- Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. --><path d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l253.44 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM0 416c0 17.7 14.3 32 32 32l126.72 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 384c-17.7 0-32 14.3-32 32z"/></svg>',
		'betheme'                    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50"><text x="5" y="40" font-size="60" font-family="Arial Black" font-weight="bold">Be</text></svg>',
		'bolt'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288H175.5L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7H272.5L349.4 44.6z"/></svg>',
		'caret-down'                 => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M160 352L0 192h320z"/></svg>',
		'caret-up'                   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M160 160L0 320h320z"/></svg>',
		'check'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>',
		'circle'                     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/></svg>',
		'circle-check'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
		'circle-plus'                => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>',
		'circle-question'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>',
		'crown'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 48"><path d="M8 34L4 12l14 10L32 4l14 18 14-10-4 22z" fill="currentColor"/><rect x="12" y="34" width="40" height="10" rx="2" fill="currentColor"/></svg>',
		'circle-up'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM135.1 217.4l107.1-99.9c3.8-3.5 8.7-5.5 13.8-5.5s10.1 2 13.8 5.5l107.1 99.9c4.5 4.2 7.1 10.1 7.1 16.3c0 12.3-10 22.3-22.3 22.3H304v96c0 17.7-14.3 32-32 32H240c-17.7 0-32-14.3-32-32V256H150.3C138 256 128 246 128 233.7c0-6.2 2.6-12.1 7.1-16.3z"/></svg>',
		'circle-xmark'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
		'code'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M392.8 1.2c-17-4.9-34.7 5-39.6 22l-128 448c-4.9 17 5 34.7 22 39.6s34.7-5 39.6-22l128-448c4.9-17-5-34.7-22-39.6zm80.6 120.1c-12.5 12.5-12.5 32.8 0 45.3L562.7 256l-89.4 89.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l112-112c12.5-12.5 12.5-32.8 0-45.3l-112-112c-12.5-12.5-32.8-12.5-45.3 0zm-306.7 0c-12.5-12.5-32.8-12.5-45.3 0l-112 112c-12.5 12.5-12.5 32.8 0 45.3l112 112c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256l89.4-89.4c12.5-12.5 12.5-32.8 0-45.3z"/></svg>',
		'comment'                    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M115.9 448.9C83.3 408.6 64 358.4 64 304C64 171.5 178.6 64 320 64C461.4 64 576 171.5 576 304C576 436.5 461.4 544 320 544C283.5 544 248.8 536.8 217.4 524L101 573.9C97.3 575.5 93.5 576 89.5 576C75.4 576 64 564.6 64 550.5C64 546.2 65.1 542 67.1 538.3L115.9 448.9zM153.2 418.7C165.4 433.8 167.3 454.8 158 471.9L140 505L198.5 479.9C210.3 474.8 223.7 474.7 235.6 479.6C261.3 490.1 289.8 496 319.9 496C437.7 496 527.9 407.2 527.9 304C527.9 200.8 437.8 112 320 112C202.2 112 112 200.8 112 304C112 346.8 127.1 386.4 153.2 418.7z"/></svg>',
		'comments'                   => '<svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 512 450.27"><path d="M217.91 393.59c53.26 49.01 127.33 63.27 201.39 31.71l63.49 24.97-9.94-59.73c59.07-51.65 45.36-123.42-1.79-173.93-3.69 19.53-10.48 38.07-19.94 55.27-14.17 25.77-34.46 48.67-59.31 67.52-24.07 18.27-52.17 32.61-82.8 41.87-28.16 8.51-58.91 12.91-91.1 12.32zm-85.88-167.22c-7.7 0-13.95-6.25-13.95-13.95 0-7.7 6.25-13.95 13.95-13.95h124.12c7.7 0 13.94 6.25 13.94 13.95 0 7.7-6.24 13.95-13.94 13.95H132.03zm0-71.41c-7.7 0-13.95-6.25-13.95-13.95 0-7.71 6.25-13.95 13.95-13.95h177.35c7.7 0 13.94 6.24 13.94 13.95 0 7.7-6.24 13.95-13.94 13.95H132.03zM226.13.12l.21.01c60.33 1.82 114.45 23.27 153.19 56.49 39.57 33.92 63.3 80.1 61.82 130.51l-.01.23c-1.56 50.44-28.05 95.17-69.62 126.71-40.74 30.92-96.12 49.16-156.44 47.39-15.45-.46-30.47-2.04-44.79-4.82-12.45-2.42-24.5-5.75-36-10.05L28.17 379.06l31.85-75.75c-18.2-15.99-32.94-34.6-43.24-55.01C5.29 225.51-.72 200.48.07 174.33c1.52-50.49 28.02-95.26 69.61-126.82C110.44 16.59 165.81-1.65 226.13.12zm-.55 27.7-.21-.01C171.49 26.23 122.33 42.3 86.41 69.55c-35.07 26.61-57.39 63.9-58.65 105.54-.65 21.39 4.31 41.94 13.78 60.72 10.01 19.82 25.02 37.7 43.79 52.58l8.26 6.54-16.99 40.39 59.12-18.06 4.5 1.81c11.15 4.48 23.04 7.9 35.48 10.31 13.07 2.55 26.59 3.98 40.34 4.39 53.88 1.58 103.04-14.49 138.96-41.74 35.07-26.61 57.39-63.9 58.65-105.54v-.22c1.19-41.57-18.82-80.01-52.15-108.59-34.18-29.3-82.19-48.24-135.92-49.86z"/></svg>',
		'copy'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 64C252.7 64 224 92.7 224 128L224 384C224 419.3 252.7 448 288 448L480 448C515.3 448 544 419.3 544 384L544 183.4C544 166 536.9 149.3 524.3 137.2L466.6 81.8C454.7 70.4 438.8 64 422.3 64L288 64zM160 192C124.7 192 96 220.7 96 256L96 512C96 547.3 124.7 576 160 576L352 576C387.3 576 416 547.3 416 512L416 496L352 496L352 512L160 512L160 256L176 256L176 192L160 192z"/></svg>',
		'download'                   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 242.7-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7 288 32zM64 352c-35.3 0-64 28.7-64 64l0 32c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-32c0-35.3-28.7-64-64-64l-101.5 0-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352 64 352zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>',
		'envelope'                   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>',
		'eye'                        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>',
		'eye-slash'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c8.4-19.3 10.6-41.4 4.8-63.3c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zM373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5L373 389.9z"/></svg>',
		'file-arrow-down'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM216 232l0 102.1 31-31c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-72 72c-9.4 9.4-24.6 9.4-33.9 0l-72-72c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l31 31L168 232c0-13.3 10.7-24 24-24s24 10.7 24 24z"/></svg>',
		'file-arrow-up'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM216 408c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-102.1-31 31c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l72-72c9.4-9.4 24.6-9.4 33.9 0l72 72c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-31-31L216 408z"/></svg>',
		'file-export'                => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 128-168 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l168 0 0 112c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64zM384 336l0-48 110.1 0-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39L384 336zm0-208l-128 0L256 0 384 128z"/></svg>',
		'flag'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 96C160 78.3 145.7 64 128 64C110.3 64 96 78.3 96 96L96 544C96 561.7 110.3 576 128 576C145.7 576 160 561.7 160 544L160 422.4L222.7 403.6C264.6 391 309.8 394.9 348.9 414.5C391.6 435.9 441.4 438.5 486.1 421.7L523.2 407.8C535.7 403.1 544 391.2 544 377.8L544 130.1C544 107.1 519.8 92.1 499.2 102.4L487.4 108.3C442.5 130.8 389.6 130.8 344.6 108.3C308.2 90.1 266.3 86.5 227.4 98.2L160 118.4L160 96z"/></svg>',
		'gear'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>',
		'gift'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M385.5 132.8C393.1 119.9 406.9 112 421.8 112L424 112C446.1 112 464 129.9 464 152C464 174.1 446.1 192 424 192L350.7 192L385.5 132.8zM254.5 132.8L289.3 192L216 192C193.9 192 176 174.1 176 152C176 129.9 193.9 112 216 112L218.2 112C233.1 112 247 119.9 254.5 132.8zM344.1 108.5L320 149.5L295.9 108.5C279.7 80.9 250.1 64 218.2 64L216 64C167.4 64 128 103.4 128 152C128 166.4 131.5 180 137.6 192L96 192C78.3 192 64 206.3 64 224L64 256C64 273.7 78.3 288 96 288L544 288C561.7 288 576 273.7 576 256L576 224C576 206.3 561.7 192 544 192L502.4 192C508.5 180 512 166.4 512 152C512 103.4 472.6 64 424 64L421.8 64C389.9 64 360.3 80.9 344.1 108.4zM544 336L344 336L344 544L480 544C515.3 544 544 515.3 544 480L544 336zM296 336L96 336L96 480C96 515.3 124.7 544 160 544L296 544L296 336z"/></svg>',
		'globe'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M352 256c0 22.2-1.2 43.6-3.3 64l-185.3 0c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64l185.3 0c2.2 20.4 3.3 41.8 3.3 64zm28.8-64l123.1 0c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64l-123.1 0c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32l-116.7 0c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0l-176.6 0c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 20.9 58.2 27 94.7zm-209 0L18.6 160C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192l123.1 0c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64L8.1 320C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6l176.6 0c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352l116.7 0zm358.1 0c-30 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6l116.7 0z"/></svg>',
		'handshake'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M323.4 85.2l-96.8 78.4c-16.1 13-19.2 36.4-7 53.1c12.9 17.8 38 21.3 55.3 7.8l99.3-77.2c7-5.4 17-4.2 22.5 2.8s4.2 17-2.8 22.5l-20.9 16.2L512 316.8 512 128l-.7 0-3.9-2.5L434.8 79c-15.3-9.8-33.2-15-51.4-15c-21.8 0-43 7.5-60 21.2zm22.8 124.4l-51.7 40.2C263 274.4 217.3 268 193.7 235.6c-22.2-30.5-16.6-73.1 12.7-96.8l83.2-67.3c-11.6-4.9-24.1-7.4-36.8-7.4C234 64 215.7 69.6 200 80l-72 48 0 224 28.2 0 91.4 83.4c19.6 17.9 49.9 16.5 67.8-3.1c5.5-6.1 9.2-13.2 11.1-20.6l17 15.6c19.5 17.9 49.9 16.6 67.8-2.9c4.5-4.9 7.8-10.6 9.9-16.5c19.4 13 45.8 10.3 62.1-7.5c17.9-19.5 16.6-49.9-2.9-67.8l-134.2-123zM16 128c-8.8 0-16 7.2-16 16L0 352c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-224-80 0zM48 320a16 16 0 1 1 0 32 16 16 0 1 1 0-32zM544 128l0 224c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-208c0-8.8-7.2-16-16-16l-80 0zm32 208a16 16 0 1 1 32 0 16 16 0 1 1 -32 0z"/></svg>',
		'hashtag'                    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M278.7 64.7C296 68.4 307 85.4 303.3 102.7L284.2 192L410.7 192L432.7 89.3C436.4 72 453.4 61 470.7 64.7C488 68.4 499 85.4 495.3 102.7L476.2 192L544 192C561.7 192 576 206.3 576 224C576 241.7 561.7 256 544 256L462.4 256L435 384L502.8 384C520.5 384 534.8 398.3 534.8 416C534.8 433.7 520.5 448 502.8 448L421.2 448L399.2 550.7C395.5 568 378.5 579 361.2 575.3C343.9 571.6 332.9 554.6 336.6 537.3L355.7 448L229.2 448L207.2 550.7C203.5 568 186.5 579 169.2 575.3C151.9 571.6 140.9 554.6 144.6 537.3L163.8 448L96 448C78.3 448 64 433.7 64 416C64 398.3 78.3 384 96 384L177.6 384L205 256L137.2 256C119.5 256 105.2 241.7 105.2 224C105.2 206.3 119.5 192 137.2 192L218.8 192L240.8 89.3C244.4 72 261.4 61 278.7 64.7zM270.4 256L243 384L369.5 384L396.9 256L270.4 256z"/></svg>',
		'headline'                   => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="20" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="20" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
		'hourglass-start'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 64C142.3 64 128 78.3 128 96C128 113.7 142.3 128 160 128L160 139C160 181.4 176.9 222.1 206.9 252.1L274.8 320L206.9 387.9C176.9 417.9 160 458.6 160 501L160 512C142.3 512 128 526.3 128 544C128 561.7 142.3 576 160 576L480 576C497.7 576 512 561.7 512 544C512 526.3 497.7 512 480 512L480 501C480 458.6 463.1 417.9 433.1 387.9L365.2 320L433.1 252.1C463.1 222.1 480 181.4 480 139L480 128C497.7 128 512 113.7 512 96C512 78.3 497.7 64 480 64L160 64zM416 501L416 512L224 512L224 501C224 475.5 234.1 451.1 252.1 433.1L320 365.2L387.9 433.1C405.9 451.1 416 475.5 416 501z"/></svg>',
		'image'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l96 0 32 0 208 0c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>',
		'image-slash'                => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l96 0 32 0 208 0c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/><line x1="0" y1="0" x2="512" y2="512" stroke="black" stroke-width="32" /></svg>',
		'key'                        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M336 352c97.2 0 176-78.8 176-176S433.2 0 336 0S160 78.8 160 176c0 18.7 2.9 36.8 8.3 53.7L7 391c-4.5 4.5-7 10.6-7 17l0 80c0 13.3 10.7 24 24 24l80 0c13.3 0 24-10.7 24-24l0-40 40 0c13.3 0 24-10.7 24-24l0-40 40 0c6.4 0 12.5-2.5 17-7l33.3-33.3c16.9 5.4 35 8.3 53.7 8.3zM376 96a40 40 0 1 1 0 80 40 40 0 1 1 0-80z"/></svg>',
		'key-slash'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com --><path d="M400 416C497.2 416 576 337.2 576 240C576 142.8 497.2 64 400 64C302.8 64 224 142.8 224 240C224 258.7 226.9 276.8 232.3 293.7L71 455C66.5 459.5 64 465.6 64 472L64 552C64 565.3 74.7 576 88 576L168 576C181.3 576 192 565.3 192 552L192 512L232 512C245.3 512 256 501.3 256 488L256 448L296 448C302.4 448 308.5 445.5 313 441L346.3 407.7C363.2 413.1 381.3 416 400 416zM440 160C462.1 160 480 177.9 480 200C480 222.1 462.1 240 440 240C417.9 240 400 222.1 400 200C400 177.9 417.9 160 440 160z"/><line x1="50" y1="50" x2="552" y2="552" stroke="black" stroke-width="50" /></svg>',
		'list'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zM16 232l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0z"/></svg>',
		'magnifying-glass'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg> ',
		'paper-plane'                => '<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.56 122.88"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><path class="cls-1" d="M2.33,44.58,117.33.37a3.63,3.63,0,0,1,5,4.56l-44,115.61h0a3.63,3.63,0,0,1-6.67.28L53.93,84.14,89.12,33.77,38.85,68.86,2.06,51.24a3.63,3.63,0,0,1,.27-6.66Z"/></svg>',
		'rank-math'                  => '<svg viewBox="0 0 462.03 462.03" xmlns="http://www.w3.org/2000/svg" width="20"><g fill="#a7aaad"><path d="m462 234.84-76.17 3.43 13.43 21-127 81.18-126-52.93-146.26 60.97 10.14 24.34 136.1-56.71 128.57 54 138.69-88.61 13.43 21z"/><path d="m54.1 312.78 92.18-38.41 4.49 1.89v-54.58h-96.67zm210.9-223.57v235.05l7.26 3 89.43-57.05v-181zm-105.44 190.79 96.67 40.62v-165.19h-96.67z"/></g></svg>',
		'robot'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z"/></svg>',
		'rotate'                     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M142.9 142.9c-17.5 17.5-30.1 38-37.8 59.8c-5.9 16.7-24.2 25.4-40.8 19.5s-25.4-24.2-19.5-40.8C55.6 150.7 73.2 122 97.6 97.6c87.2-87.2 228.3-87.5 315.8-1L455 55c6.9-6.9 17.2-8.9 26.2-5.2s14.8 12.5 14.8 22.2l0 128c0 13.3-10.7 24-24 24l-8.4 0c0 0 0 0 0 0L344 224c-9.7 0-18.5-5.8-22.2-14.8s-1.7-19.3 5.2-26.2l41.1-41.1c-62.6-61.5-163.1-61.2-225.3 1zM16 312c0-13.3 10.7-24 24-24l7.6 0 .7 0L168 288c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6 0-.7 0-7.6z"/></svg>',
		'pen-to-square'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-primary" d="M392.4 21.7L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0zM339.7 74.3L172.4 241.7c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3z"/><path class="fa-secondary" d="M0 160c0-53 43-96 96-96h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H96c-17.7 0-32 14.3-32 32V416c0 17.7 14.3 32 32 32H352c17.7 0 32-14.3 32-32V320c0-17.7 14.3-32 32-32s32 14.3 32 32v96c0 53-43 96-96 96H96c-53 0-96-43-96-96V160z"/></svg>',
		'rocket'                     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2l0 82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9l0-107.2c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>',
		'rocket-chat'                => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M284 224.8a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 284 224.8zm-110.5 0a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 173.6 224.8zm220.9 0a34.1 34.1 0 1 0 34.3 34.1A34.2 34.2 0 0 0 394.5 224.8zm153.8-55.3c-15.5-24.2-37.3-45.6-64.7-63.6-52.9-34.8-122.4-54-195.7-54a406 406 0 0 0 -72 6.4 238.5 238.5 0 0 0 -49.5-36.6C99.7-11.7 40.9 .7 11.1 11.4A14.3 14.3 0 0 0 5.6 34.8C26.5 56.5 61.2 99.3 52.7 138.3c-33.1 33.9-51.1 74.8-51.1 117.3 0 43.4 18 84.2 51.1 118.1 8.5 39-26.2 81.8-47.1 103.5a14.3 14.3 0 0 0 5.6 23.3c29.7 10.7 88.5 23.1 155.3-10.2a238.7 238.7 0 0 0 49.5-36.6A406 406 0 0 0 288 460.1c73.3 0 142.8-19.2 195.7-54 27.4-18 49.1-39.4 64.7-63.6 17.3-26.9 26.1-55.9 26.1-86.1C574.4 225.4 565.6 196.4 548.3 169.5zM285 409.9a345.7 345.7 0 0 1 -89.4-11.5l-20.1 19.4a184.4 184.4 0 0 1 -37.1 27.6 145.8 145.8 0 0 1 -52.5 14.9c1-1.8 1.9-3.6 2.8-5.4q30.3-55.7 16.3-100.1c-33-26-52.8-59.2-52.8-95.4 0-83.1 104.3-150.5 232.8-150.5s232.9 67.4 232.9 150.5C517.9 342.5 413.6 409.9 285 409.9z"/></svg>',
		'seopress'                   => '<svg id="uuid-4f6a8a41-18e3-4f77-b5a9-4b1b38aa2dc9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 899.655 494.3094"><path id="uuid-a155c1ca-d868-4653-8477-8dd87240a765" d="M327.3849,435.128l-299.9999-.2497c-16.2735,1.1937-28.4981,15.3538-27.3044,31.6273,1.0719,14.6128,12.6916,26.2325,27.3044,27.3044l299.9999,.2497c16.2735-1.1937,28.4981-15.3538,27.3044-31.6273-1.0718-14.6128-12.6916-26.2325-27.3044-27.3044Z" style="fill:#fff"/><path id="uuid-e30ba4c6-4769-466b-a03a-e644c5198e56" d="M27.3849,58.9317l299.9999,.2497c16.2735-1.1937,28.4981-15.3537,27.3044-31.6273-1.0718-14.6128-12.6916-26.2325-27.3044-27.3044L27.3849,0C11.1114,1.1937-1.1132,15.3537,.0805,31.6273c1.0719,14.6128,12.6916,26.2325,27.3044,27.3044Z" style="fill:#fff"/><path id="uuid-2bbd52d6-aec1-4689-9d4c-23c35d4f22b8" d="M652.485,.2849c-124.9388,.064-230.1554,93.4132-245.1001,217.455H27.3849c-16.2735,1.1937-28.4981,15.3537-27.3044,31.6272,1.0719,14.6128,12.6916,26.2325,27.3044,27.3044H407.3849c16.2298,135.4454,139.187,232.0888,274.6323,215.8589,135.4455-16.2298,232.0888-139.1869,215.8589-274.6324C882.9921,93.6834,777.5884,.2112,652.485,.2849Zm0,433.4217c-102.9754,0-186.4533-83.478-186.4533-186.4533,0-102.9753,83.4781-186.4533,186.4533-186.4533,102.9754,0,186.4533,83.478,186.4533,186.4533,.0524,102.9753-83.383,186.4959-186.3583,186.5483-.0316,0-.0634,0-.0951,0v-.095Z" style="fill:#fff"/></svg>',
		'shopping-cart'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>',
		'sliders'                    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 128C78.3 128 64 142.3 64 160C64 177.7 78.3 192 96 192L182.7 192C195 220.3 223.2 240 256 240C288.8 240 317 220.3 329.3 192L544 192C561.7 192 576 177.7 576 160C576 142.3 561.7 128 544 128L329.3 128C317 99.7 288.8 80 256 80C223.2 80 195 99.7 182.7 128L96 128zM96 288C78.3 288 64 302.3 64 320C64 337.7 78.3 352 96 352L342.7 352C355 380.3 383.2 400 416 400C448.8 400 477 380.3 489.3 352L544 352C561.7 352 576 337.7 576 320C576 302.3 561.7 288 544 288L489.3 288C477 259.7 448.8 240 416 240C383.2 240 355 259.7 342.7 288L96 288zM96 448C78.3 448 64 462.3 64 480C64 497.7 78.3 512 96 512L150.7 512C163 540.3 191.2 560 224 560C256.8 560 285 540.3 297.3 512L544 512C561.7 512 576 497.7 576 480C576 462.3 561.7 448 544 448L297.3 448C285 419.7 256.8 400 224 400C191.2 400 163 419.7 150.7 448L96 448z"/></svg>',
		'sooz-with-ai-for-seo'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 731.25 333.75" width="131.46" height="60" style="max-height:80px; width:auto;" xml:space="preserve"><path fill="#0066aa" d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path fill="#0066aa" d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path fill="#0066aa" d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/><g><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M127.495,252.48c0.96,0,1.8,0.234,2.52,0.701c0.72,0.47,1.2,1.099,1.44,1.891L158.095,327h-10.92l-21.48-61.128c-0.321-1.008-0.66-2.033-1.02-3.078c-0.36-1.043-0.701-2.033-1.02-2.97h-3.36c-0.321,0.937-0.642,1.927-0.96,2.97c-0.321,1.045-0.681,2.07-1.08,3.078L96.775,327h-10.92l26.64-71.928c0.24-0.792,0.72-1.421,1.44-1.891c0.72-0.467,1.56-0.701,2.52-0.701H127.495z M142.855,295.464v8.208h-42v-8.208H142.855z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M183.895,252.48V327h-10.56v-74.52H183.895z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M269.214,274.404v7.235h-39.48v-6.695l11.16-0.54H269.214z M257.334,249.564c1.279,0,2.919,0.019,4.92,0.054c1.999,0.037,4.039,0.107,6.12,0.216c2.08,0.108,3.879,0.27,5.4,0.486l-0.84,6.804h-12c-3.84,0-6.54,0.686-8.1,2.052c-1.56,1.369-2.34,3.637-2.34,6.805V327h-10.2v-61.992c0-3.311,0.559-6.102,1.68-8.37c1.119-2.268,2.919-4.013,5.4-5.237C249.853,250.177,253.173,249.564,257.334,249.564z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M308.693,273.108c6.72,0,12.099,0.9,16.14,2.699c4.039,1.801,6.939,4.735,8.7,8.803c1.759,4.068,2.64,9.449,2.64,16.146s-0.881,12.079-2.64,16.146c-1.761,4.068-4.661,7.003-8.7,8.802c-4.041,1.799-9.42,2.7-16.14,2.7c-6.641,0-11.981-0.901-16.02-2.7c-4.041-1.799-6.96-4.733-8.76-8.802c-1.8-4.067-2.7-9.45-2.7-16.146s0.9-12.077,2.7-16.146c1.8-4.067,4.72-7.002,8.76-8.803C296.712,274.009,302.052,273.108,308.693,273.108z M308.693,280.884c-4.241,0-7.581,0.594-10.02,1.782c-2.441,1.188-4.181,3.223-5.22,6.102c-1.041,2.881-1.56,6.877-1.56,11.988c0,5.113,0.52,9.109,1.56,11.988c1.039,2.881,2.779,4.914,5.22,6.102c2.439,1.188,5.779,1.782,10.02,1.782c4.239,0,7.599-0.594,10.08-1.782c2.479-1.188,4.239-3.221,5.28-6.102c1.039-2.879,1.56-6.875,1.56-11.988c0-5.111-0.521-9.107-1.56-11.988c-1.041-2.879-2.801-4.914-5.28-6.102C316.292,281.478,312.933,280.884,308.693,280.884z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M363.652,274.404l1.32,10.044l0.96,1.62V327h-10.2v-52.596H363.652z M392.332,273.108l-1.2,8.64h-3.36c-3.439,0-6.881,0.631-10.319,1.89c-3.44,1.261-7.641,3.043-12.6,5.347l-0.84-5.725c4.32-3.167,8.659-5.651,13.02-7.452c4.359-1.799,8.58-2.699,12.659-2.699H392.332z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M459.41,251.184c3.918,0.073,7.959,0.254,12.119,0.54c4.159,0.289,8.319,0.829,12.48,1.62l-0.72,6.912c-3.441-0.144-7.241-0.27-11.4-0.378c-4.16-0.108-8.16-0.162-12-0.162c-2.961,0-5.501,0.091-7.62,0.271c-2.12,0.181-3.86,0.612-5.22,1.296c-1.361,0.685-2.34,1.801-2.94,3.348c-0.6,1.549-0.899,3.69-0.899,6.426c0,4.104,0.819,6.967,2.46,8.586c1.639,1.62,4.299,2.827,7.979,3.618l16.8,3.78c6.399,1.368,10.78,3.763,13.141,7.182c2.358,3.421,3.54,8.011,3.54,13.771c0,4.319-0.54,7.813-1.62,10.476c-1.08,2.665-2.741,4.698-4.98,6.103c-2.24,1.403-5.12,2.376-8.64,2.916c-3.521,0.54-7.68,0.81-12.479,0.81c-2.721,0-6.261-0.107-10.62-0.324c-4.361-0.216-9.381-0.793-15.061-1.728l0.72-7.021c4.72,0.146,8.521,0.271,11.4,0.378c2.88,0.108,5.358,0.162,7.44,0.162c2.079,0,4.239,0,6.479,0c4.239,0,7.579-0.286,10.021-0.863c2.439-0.576,4.158-1.729,5.159-3.456c1-1.729,1.5-4.283,1.5-7.668c0-2.879-0.359-5.111-1.079-6.696c-0.721-1.583-1.86-2.789-3.421-3.618c-1.56-0.827-3.539-1.493-5.939-1.998l-17.16-3.888c-6-1.367-10.221-3.743-12.66-7.128c-2.441-3.384-3.66-7.92-3.66-13.608c0-4.32,0.54-7.793,1.62-10.422c1.08-2.627,2.719-4.606,4.92-5.94c2.2-1.331,4.98-2.214,8.341-2.646C450.77,251.4,454.77,251.184,459.41,251.184z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M522.528,252.372c4,0,8.02,0,12.061,0c4.038,0,8.04,0.054,12,0.162c3.96,0.107,7.779,0.27,11.46,0.485l-0.48,7.452h-33.239c-2.481,0-4.302,0.559-5.461,1.675c-1.16,1.116-1.739,2.934-1.739,5.453v44.28c0,2.521,0.579,4.357,1.739,5.508c1.159,1.153,2.979,1.729,5.461,1.729h33.239l0.48,7.344c-3.681,0.216-7.5,0.361-11.46,0.432c-3.96,0.073-7.962,0.125-12,0.162c-4.041,0.036-8.061,0.055-12.061,0.055c-4.88,0-8.741-1.17-11.58-3.511c-2.84-2.339-4.301-5.489-4.38-9.449v-48.816c0.079-4.031,1.54-7.199,4.38-9.504C513.787,253.524,517.648,252.372,522.528,252.372z M508.729,283.8h44.16v7.668h-44.16V283.8z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M607.009,251.076c6,0,11.1,0.647,15.3,1.943c4.2,1.297,7.561,3.439,10.08,6.427c2.521,2.988,4.359,6.966,5.521,11.934c1.158,4.968,1.739,11.089,1.739,18.36c0,7.272-0.581,13.392-1.739,18.359c-1.161,4.969-3,8.947-5.521,11.935c-2.52,2.988-5.88,5.13-10.08,6.426s-9.3,1.944-15.3,1.944s-11.1-0.648-15.3-1.944s-7.581-3.438-10.141-6.426c-2.561-2.987-4.421-6.966-5.58-11.935c-1.16-4.968-1.739-11.087-1.739-18.359c0-7.271,0.579-13.393,1.739-18.36c1.159-4.968,3.02-8.945,5.58-11.934c2.56-2.987,5.94-5.13,10.141-6.427C595.909,251.724,601.009,251.076,607.009,251.076z M607.009,259.608c-5.441,0-9.74,0.937-12.9,2.808c-3.161,1.873-5.399,4.986-6.72,9.342c-1.32,4.357-1.979,10.352-1.979,17.982c0,7.56,0.659,13.537,1.979,17.928c1.32,4.393,3.559,7.524,6.72,9.396c3.16,1.873,7.459,2.808,12.9,2.808c5.439,0,9.738-0.935,12.9-2.808c3.159-1.872,5.399-5.004,6.72-9.396c1.319-4.391,1.979-10.368,1.979-17.928c0-7.631-0.66-13.625-1.979-17.982c-1.32-4.355-3.561-7.469-6.72-9.342C616.747,260.545,612.448,259.608,607.009,259.608z"/></g></svg>',
		'sooz'                       => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="145.28" height="40" viewBox="0 0 731.25 201.25" enable-background="new 0 0 731.25 201.25" xml:space="preserve" fill="#0066aa" style="max-height:40px; width:auto;"><path d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/></svg>',
		'sooz-oo'                    => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="72.30" height="40" viewBox="0 0 363.75 201.25" enable-background="new 0 0 363.75 201.25" xml:space="preserve" style="max-height:40px; width:auto;"><path fill="#0066aa" d="M496.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H537.61v33.215H412.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H374.526V5.501H496.595z"/><path fill="#0066aa" d="M-11.02,5.501v33.206h-119.841c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825h-124.792v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642l-101.365-44.188c-9.945-4.309-17.375-10.075-22.329-17.299c-4.958-7.225-7.435-16.295-7.435-27.174c0-13.328,3.818-23.594,11.436-30.816c7.613-7.227,18.859-10.837,33.749-10.837H-11.02z"/><path fill="#0066aa" d="M337.13,29.148C322.678,13.38,301.924,5.501,274.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489H88.788c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18H88.788c-27.207,0-48.001,7.879-62.414,23.646C11.956,44.908,4.768,68.838,4.768,100.926c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C358.772,68.836,351.582,44.906,337.13,29.148z"/></svg>',
		'square-check'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
		'square-facebook'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64h98.2V334.2H109.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H255V480H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64z"/></svg>',
		'square-twitter-x'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm297.1 84L257.3 234.6 379.4 396H283.8L209 298.1 123.3 396H75.8l111-126.9L69.7 116h98l67.7 89.5L313.6 116h47.5zM323.3 367.6L153.4 142.9H125.1L296.9 367.6h26.3z"/></svg>',
		'square-xmark'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
		'star'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>',
		'stripe'                     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M492.4 220.8c-8.9 0-18.7 6.7-18.7 22.7h36.7c0-16-9.3-22.7-18-22.7zM375 223.4c-8.2 0-13.3 2.9-17 7l.2 52.8c3.5 3.7 8.5 6.7 16.8 6.7 13.1 0 21.9-14.3 21.9-33.4 0-18.6-9-33.2-21.9-33.1zM528 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM122.2 281.1c0 25.6-20.3 40.1-49.9 40.3-12.2 0-25.6-2.4-38.8-8.1v-33.9c12 6.4 27.1 11.3 38.9 11.3 7.9 0 13.6-2.1 13.6-8.7 0-17-54-10.6-54-49.9 0-25.2 19.2-40.2 48-40.2 11.8 0 23.5 1.8 35.3 6.5v33.4c-10.8-5.8-24.5-9.1-35.3-9.1-7.5 0-12.1 2.2-12.1 7.7 0 16 54.3 8.4 54.3 50.7zm68.8-56.6h-27V275c0 20.9 22.5 14.4 27 12.6v28.9c-4.7 2.6-13.3 4.7-24.9 4.7-21.1 0-36.9-15.5-36.9-36.5l.2-113.9 34.7-7.4v30.8H191zm74 2.4c-4.5-1.5-18.7-3.6-27.1 7.4v84.4h-35.5V194.2h30.7l2.2 10.5c8.3-15.3 24.9-12.2 29.6-10.5h.1zm44.1 91.8h-35.7V194.2h35.7zm0-142.9l-35.7 7.6v-28.9l35.7-7.6zm74.1 145.5c-12.4 0-20-5.3-25.1-9l-.1 40.2-35.5 7.5V194.2h31.3l1.8 8.8c4.9-4.5 13.9-11.1 27.8-11.1 24.9 0 48.4 22.5 48.4 63.8 0 45.1-23.2 65.5-48.6 65.6zm160.4-51.5h-69.5c1.6 16.6 13.8 21.5 27.6 21.5 14.1 0 25.2-3 34.9-7.9V312c-9.7 5.3-22.4 9.2-39.4 9.2-34.6 0-58.8-21.7-58.8-64.5 0-36.2 20.5-64.9 54.3-64.9 33.7 0 51.3 28.7 51.3 65.1 0 3.5-.3 10.9-.4 12.9z"/></svg>',
		'subtitle'                   => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="70" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="70" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
		'subtitles'                  => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" rx="15" ry="15"/><rect x="15" y="50" width="30" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="55" y="50" width="30" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="15" y="70" width="40" height="10" rx="5" ry="5" fill="#ffffff"/><rect x="60" y="70" width="20" height="10" rx="5" ry="5" fill="#ffffff"/></svg>',
		'trash'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z"/></svg>',
		'triangle-exclamation'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>',
		'xmark'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-secondary" d="M297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6z"/></svg>',
		'yoast'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M91.3 76h186l-7 18.9h-179c-39.7 0-71.9 31.6-71.9 70.3v205.4c0 35.4 24.9 70.3 84 70.3V460H91.3C41.2 460 0 419.8 0 370.5V165.2C0 115.9 40.7 76 91.3 76zm229.1-56h66.5C243.1 398.1 241.2 418.9 202.2 459.3c-20.8 21.6-49.3 31.7-78.3 32.7v-51.1c49.2-7.7 64.6-49.9 64.6-75.3 0-20.1 .6-12.6-82.1-223.2h61.4L218.2 299 320.4 20zM448 161.5V460H234c6.6-9.6 10.7-16.3 12.1-19.4h182.5V161.5c0-32.5-17.1-51.9-48.2-62.9l6.7-17.6c41.7 13.6 60.9 43.1 60.9 80.5z"/></svg>',
		'woocommerce'                => '<svg preserveAspectRatio="xMidYMid" version="1.1" viewBox="0 0 256 153" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"><title>WooCommerce Logo</title><metadata><rdf:RDF><cc:Work rdf:about=""><dc:format>image/svg+xml</dc:format><dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"/><dc:title/></cc:Work></rdf:RDF></metadata><path d="m23.759 0h208.38c13.187 0 23.863 10.675 23.863 23.863v79.542c0 13.187-10.675 23.863-23.863 23.863h-74.727l10.257 25.118-45.109-25.118h-98.695c-13.187 0-23.863-10.675-23.863-23.863v-79.542c-0.10466-13.083 10.571-23.863 23.758-23.863z" fill="#7f54b3"/><path d="m14.578 21.75c1.4569-1.9772 3.6423-3.0179 6.5561-3.226 5.3073-0.41626 8.3252 2.0813 9.0537 7.4927 3.226 21.75 6.7642 40.169 10.511 55.259l22.79-43.395c2.0813-3.9545 4.6829-6.0358 7.8049-6.2439 4.5789-0.3122 7.3886 2.6016 8.5333 8.7415 2.6016 13.841 5.9317 25.6 9.8862 35.59 2.7057-26.433 7.2846-45.476 13.737-57.236 1.561-2.9138 3.8504-4.3707 6.8683-4.5789 2.3935-0.20813 4.5789 0.52033 6.5561 2.0813 1.9772 1.561 3.0179 3.5382 3.226 5.9317 0.10406 1.8732-0.20813 3.4341-1.0407 4.9951-4.0585 7.4927-7.3886 20.085-10.094 37.567-2.6016 16.963-3.5382 30.179-2.9138 39.649 0.20813 2.6016-0.20813 4.8911-1.2488 6.8683-1.2488 2.2894-3.122 3.5382-5.5154 3.7463-2.7057 0.20813-5.5154-1.0406-8.2211-3.8504-9.678-9.8862-17.379-24.663-22.998-44.332-6.7642 13.32-11.759 23.311-14.985 29.971-6.1398 11.759-11.343 17.795-15.714 18.107-2.8098 0.20813-5.2033-2.1854-7.2846-7.1805-5.3073-13.633-11.031-39.961-17.171-78.985-0.41626-2.7057 0.20813-5.0992 1.665-6.9724zm223.64 16.338c-3.7463-6.5561-9.2618-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0.10406-8.1171-1.665-14.881-4.9951-20.501zm-13.112 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93659-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0 2.7057-0.20813 5.3073-0.72846 7.7008zm-52.033-28.826c-3.7463-6.5561-9.3659-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0-8.1171-1.665-14.881-4.9951-20.501zm-13.216 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93658-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0.10406 2.7057-0.20813 5.3073-0.72846 7.7008z" fill="#fff"/></svg>',
		'wpml'                       => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="#000000"><defs><linearGradient id="wpml-icon-gradient" x1="21.531" y1="22.766" x2="25.042" y2="28.463" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#33879e"></stop><stop offset="0.047" stop-color="#537983"></stop><stop offset="0.105" stop-color="#726c68"></stop><stop offset="0.17" stop-color="#8e6051"></stop><stop offset="0.244" stop-color="#a4573f"></stop><stop offset="0.33" stop-color="#b55030"></stop><stop offset="0.436" stop-color="#c04b26"></stop><stop offset="0.585" stop-color="#c74821"></stop><stop offset="1" stop-color="#c9471f"></stop></linearGradient></defs><path d="M15.09,2C10.15,2.013,4.178,7.74,4.245,13.788c-.011,5.97,4.739,11.236,11.868,11.552,4.047.177,5.532-1.865,7.23-1.414s1.874,1.945,1.788,3.085a2.353,2.353,0,0,1-2.5,2.338c-.988-.5-.391-3.2-1.906-3.36-.946.1-1.368.93-1.159,1.946S21.082,29.923,22.949,30a2.888,2.888,0,0,0,2.987-3.006,3.284,3.284,0,0,0-2.809-3.516c-1.913-.178-4.337,2.139-7.957,1.473A10.506,10.506,0,0,1,6.446,14.261c.03-6.9,6.429-10.254,10.452-10.217s6.144,1.4,6.759,3.693-2.031,4.616-3.223,5.973-1.822,2.266-1.572,3.851c.251,1.663,2.162,4.1,4.166,4.087s4.919-2.255,4.716-7.7a11.993,11.993,0,0,0-4.558-9.353C21.752,3.456,20.032,1.989,15.09,2Z" style="fill:url(#wpml-icon-gradient)"></path></svg>',
	);
}

const AI4SEO_STRIPE_BILLING_URL                        = 'https://sooz.ai/manage-plan';
const AI4SEO_POST_TYPES_PLUGIN_PAGE_NAME               = 'post';
const AI4SEO_DEEP_CONTEXT_SEARCH_QUERY_TIMEOUT_SECONDS = 10;

// Constants for the wp_options entries.
const AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME         = 'ai4seo_fully_covered_metadata_post_ids';
const AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME               = 'ai4seo_missing_metadata_post_ids';
const AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME               = 'ai4seo_pending_metadata_post_ids';
const AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME            = 'ai4seo_processing_metadata_post_ids';
const AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME             = 'ai4seo_generated_metadata_post_ids';
const AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME                = 'ai4seo_failed_metadata_post_ids';
const AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME       = 'ai4seo_force_overwrite_metadata_post_ids';
const AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME                = 'ai4seo_hidden_metadata_post_ids';
const AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME = 'ai4seo_auto_queue_disallowed_metadata_post_ids';
const AI4SEO_LATEST_ACTIVITY_OPTION_NAME                         = '_ai4seo_latest_activity'; // todo: replace with database table.

const AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME         = 'ai4seo_fully_covered_attachment_attributes_post_ids';
const AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME               = 'ai4seo_missing_attachment_attributes_post_ids';
const AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME               = 'ai4seo_pending_attachment_attributes_post_ids';
const AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME            = 'ai4seo_processing_attachment_attributes_post_ids';
const AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME             = 'ai4seo_generated_attachment_attributes_post_ids';
const AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME                = 'ai4seo_failed_attachment_attributes_post_ids';
const AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME       = 'ai4seo_force_overwrite_attachment_attributes_post_ids';
const AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME                = 'ai4seo_hidden_attachment_attributes_post_ids';
const AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME = 'ai4seo_auto_queue_disallowed_attachment_attributes_post_ids';

const AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_OPTION_NAME                    = '_ai4seo_additional_tos_accept_details';
const AI4SEO_ADDITIONAL_TOS_ACCEPT_DETAILS_LAST_TRY_TIMESTAMP_OPTION_NAME = '_ai4seo_additional_tos_accept_details_last_try_timestamp';

// all wp_options that contain post ids.
const AI4SEO_ALL_POST_ID_OPTIONS = array(
	AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,

	AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// all wp_options that define the seo coverage
// a post id cannot be in MISSING and one of the other options at the same time.
const AI4SEO_SEO_COVERAGE_POST_ID_OPTIONS = array(
	AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FULLY_COVERED_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME,

	AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_FULLY_COVERED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// all wp-Options that define the generation status of a given post
// a post id cannot be in PENDING and PROCESSING at the same time.
const AI4SEO_GENERATION_STATUS_POST_ID_OPTIONS = array(
	AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,

	AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
	AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// all wp_options that mark queued entries as force-overwrite runs.
const AI4SEO_FORCE_OVERWRITE_BULK_GENERATION_POST_ID_OPTIONS = array(
	AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
	AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
);

// Bulk action IDs are shared by PHP handlers, native table redirects, and AJAX response routing.
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE                                     = 'ai4seo_bulk_generation_add_to_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE                     = 'ai4seo_bulk_generation_add_to_queue_force_overwrite';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE                 = 'ai4seo_bulk_generation_add_related_attachments_to_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE = 'ai4seo_bulk_generation_add_related_attachments_to_queue_force_overwrite';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE            = 'ai4seo_bulk_generation_remove_related_attachments_from_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS                          = 'ai4seo_bulk_generation_set_custom_instructions';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE                                = 'ai4seo_bulk_generation_remove_from_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY                                       = 'ai4seo_bulk_generation_hide_entry';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY                                       = 'ai4seo_bulk_generation_show_entry';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE                              = 'ai4seo_bulk_generation_disallow_auto_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE                                 = 'ai4seo_bulk_generation_allow_auto_queue';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA                            = 'ai4seo_bulk_generation_remove_generated_data';
const AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA                                = 'ai4seo_bulk_generation_remove_saved_data';
// Queue contexts separate post/page/product metadata from media-library attachment attributes.
const AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA              = 'metadata';
const AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES = 'attachment_attributes';

// region CRON JOBS ==========================================================================================.

const AI4SEO_BULK_GENERATION_CRON_JOB_NAME                   = 'ai4seo_automated_generation_cron_job';
const AI4SEO_ANALYSE_PLUGIN_PERFORMANCE_CRON_JOB_NAME        = 'ai4seo_analyze_plugin_performance';
const AI4SEO_GENERATION_STATUS_SUMMARY_REBUILD_CRON_JOB_NAME = 'ai4seo_rebuild_generation_status_summary';
const AI4SEO_ACTIVE_METADATA_MIGRATION_V235_CRON_JOB_NAME    = 'ai4seo_active_metadata_migration_v235_cron_job';
const AI4SEO_ACTIVE_METADATA_MIGRATION_V235_BATCH_SIZE       = 500;


// endregion
// region THIRD PARTY PLUGINS ==============================================================================.

// Constants for third party plugin identifiers
// editors.
const AI4SEO_THIRD_PARTY_PLUGIN_ELEMENTOR = 'elementor';

// shops.
const AI4SEO_THIRD_PARTY_PLUGIN_WOOCOMMERCE = 'woocommerce';

// traditional seo plugins.
const AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO         = 'yoast-seo';
const AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO    = 'all-in-one-seo-pack';
const AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK = 'the-seo-framework';
const AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH         = 'rank-math';
const AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS          = 'seopress';
const AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK   = 'seo-simple-pack';
const AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO          = 'slim-seo';
const AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO      = 'squirrly-seo';
const AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY           = 'seo-key';

// Preserve the former default selection only for sites upgrading from versions before 2.4.4.
const AI4SEO_LEGACY_DEFAULT_THIRD_PARTY_SEO_PLUGIN_SYNC_IDENTIFIERS = array(
	AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO,
	AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH,
	AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS,
	AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK,
	AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK,
	AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY,
);

// editors + seo plugins.
const AI4SEO_THIRD_PARTY_PLUGIN_BETHEME = 'betheme';

// multi-language plugins.
const AI4SEO_THIRD_PARTY_PLUGIN_WPML = 'wpml';

// attachments / images plugins.
const AI4SEO_THIRD_PARTY_PLUGIN_NEXTGEN_GALLERY = 'nextgen-gallery';

/**
 * Return integration details for supported third-party SEO plugins.
 *
 * @return array Third-party SEO plugin details keyed by plugin identifier.
 */
function ai4seo_get_third_party_seo_plugin_details(): array {
	return array(
		AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO         => array(
			'name'                           => 'Yoast SEO',
			'icon'                           => 'yoast',
			'icon-css-class'                 => 'ai4seo-purple-icon',
			// Direct Yoast postmeta writes can be mirrored without integration-specific storage handling.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'focus-keyphrase'      => '_yoast_wpseo_focuskw',
				'meta-title'           => '_yoast_wpseo_title',
				'meta-description'     => '_yoast_wpseo_metadesc',
				'facebook-title'       => '_yoast_wpseo_opengraph-title',
				'facebook-description' => '_yoast_wpseo_opengraph-description',
				'twitter-title'        => '_yoast_wpseo_twitter-title',
				'twitter-description'  => '_yoast_wpseo_twitter-description',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_BETHEME           => array(
			'name'                           => 'BeTheme',
			'icon'                           => 'betheme',
			'icon-css-class'                 => 'ai4seo-blue-icon',
			'generation-field-postmeta-keys' => array(
				'meta-title'       => 'mfn-meta-seo-title',
				'meta-description' => 'mfn-meta-seo-description',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_ALL_IN_ONE_SEO    => array(
			'name'                           => 'All in One SEO',
			'icon'                           => 'all-in-one-seo',
			// AIOSEO updates these postmeta mirrors before it saves its canonical aioseo_posts row.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'meta-title'           => '_aioseo_title',
				'meta-description'     => '_aioseo_description',
				'facebook-title'       => '_aioseo_og_title',
				'facebook-description' => '_aioseo_og_description',
				'twitter-title'        => '_aioseo_twitter_title',
				'twitter-description'  => '_aioseo_twitter_description',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_RANK_MATH         => array(
			'name'                           => 'Rank Math',
			'icon'                           => 'rank-math',
			'icon-css-class'                 => 'ai4seo-purple-icon',
			'seo-score-postmeta-key'         => 'rank_math_seo_score', // todo: make this dynamic.
			// Direct Rank Math postmeta writes can be mirrored without integration-specific storage handling.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'focus-keyphrase'      => 'rank_math_focus_keyword',
				'meta-title'           => 'rank_math_title',
				'meta-description'     => 'rank_math_description',
				'facebook-title'       => 'rank_math_facebook_title',
				'facebook-description' => 'rank_math_facebook_description',
				'twitter-title'        => 'rank_math_twitter_title',
				'twitter-description'  => 'rank_math_twitter_description',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_SEO_SIMPLE_PACK   => array(
			'name'                           => 'SEO Simple Pack',
			// SEO SIMPLE PACK persists these independent editor fields as direct postmeta values.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'meta-title'       => 'ssp_meta_title',
				'meta-description' => 'ssp_meta_description',
				'keywords'         => 'ssp_meta_keyword',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_SEOPRESS          => array(
			'name'                           => 'SEOPress',
			'icon'                           => 'seopress',
			// SEOPress persists every supported editor field as direct postmeta.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'meta-title'           => '_seopress_titles_title',
				'meta-description'     => '_seopress_titles_desc',
				'facebook-title'       => '_seopress_social_fb_title',
				'facebook-description' => '_seopress_social_fb_desc',
				'twitter-title'        => '_seopress_social_twitter_title',
				'twitter-description'  => '_seopress_social_twitter_desc',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_SLIM_SEO          => array(
			'name'                           => 'Slim SEO',
			// Slim SEO persists both fields inside one shared postmeta array.
			'inbound-postmeta-sync'          => array(
				'postmeta-key'                => 'slim_seo',
				'generation-field-array-keys' => array(
					'meta-title'       => 'title',
					'meta-description' => 'description',
				),
			),
			'generation-field-postmeta-keys' => array(
				'meta-title'       => '_ai4seo_workaround',
				'meta-description' => '_ai4seo_workaround',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_SQUIRRLY_SEO      => array(
			'name'                           => 'Squirrly SEO',
			// Squirrly persists its editor fields in the custom qss table instead of WordPress postmeta.
			'inbound-custom-sync'            => true,
			'generation-field-postmeta-keys' => array(
				'meta-title'           => '_ai4seo_workaround',
				'meta-description'     => '_ai4seo_workaround',
				'keywords'             => '_ai4seo_workaround',
				'facebook-title'       => '_ai4seo_workaround',
				'facebook-description' => '_ai4seo_workaround',
				'twitter-title'        => '_ai4seo_workaround',
				'twitter-description'  => '_ai4seo_workaround',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_THE_SEO_FRAMEWORK => array(
			'name'                           => 'The SEO Framework',
			// The SEO Framework persists all six supported fields as direct postmeta values.
			'inbound-postmeta-sync'          => true,
			'generation-field-postmeta-keys' => array(
				'meta-title'           => '_genesis_title',
				'meta-description'     => '_genesis_description',
				'facebook-title'       => '_open_graph_title',
				'facebook-description' => '_open_graph_description',
				'twitter-title'        => '_twitter_title',
				'twitter-description'  => '_twitter_description',
			),
		),
		AI4SEO_THIRD_PARTY_PLUGIN_SEO_KEY           => array(
			'name'                           => 'SEOKEY',
			'generation-field-postmeta-keys' => array(
				'meta-title'       => 'seokey-metatitle',
				'meta-description' => 'seokey-metadesc',
			),
		),
	);
}

/**
 * Return the currency codes supported by plugin billing controls.
 *
 * @return array Supported ISO currency codes.
 */
function ai4seo_get_allowed_currencies(): array {
	return array(
		'AED',
		'AFN',
		'ALL',
		'AMD',
		'ANG',
		'AOA',
		'ARS',
		'AUD',
		'AWG',
		'AZN',
		'BAM',
		'BBD',
		'BDT',
		'BGN',
		'BHD',
		'BIF',
		'BMD',
		'BND',
		'BOB',
		'BRL',
		'BSD',
		'BTC',
		'BTN',
		'BWP',
		'BYN',
		'BZD',
		'CAD',
		'CDF',
		'CHF',
		'CLF',
		'CLP',
		'CNH',
		'CNY',
		'COP',
		'CRC',
		'CUC',
		'CUP',
		'CVE',
		'CZK',
		'DJF',
		'DKK',
		'DOP',
		'DZD',
		'EGP',
		'ERN',
		'ETB',
		'EUR',
		'FJD',
		'FKP',
		'GBP',
		'GEL',
		'GGP',
		'GHS',
		'GIP',
		'GMD',
		'GNF',
		'GTQ',
		'GYD',
		'HKD',
		'HNL',
		'HRK',
		'HTG',
		'HUF',
		'IDR',
		'ILS',
		'IMP',
		'INR',
		'IQD',
		'IRR',
		'ISK',
		'JEP',
		'JMD',
		'JOD',
		'JPY',
		'KES',
		'KGS',
		'KHR',
		'KMF',
		'KPW',
		'KRW',
		'KWD',
		'KYD',
		'KZT',
		'LAK',
		'LBP',
		'LKR',
		'LRD',
		'LSL',
		'LYD',
		'MAD',
		'MDL',
		'MGA',
		'MKD',
		'MMK',
		'MNT',
		'MOP',
		'MRU',
		'MUR',
		'MVR',
		'MWK',
		'MXN',
		'MYR',
		'MZN',
		'NAD',
		'NGN',
		'NIO',
		'NOK',
		'NPR',
		'NZD',
		'OMR',
		'PAB',
		'PEN',
		'PGK',
		'PHP',
		'PKR',
		'PLN',
		'PYG',
		'QAR',
		'RON',
		'RSD',
		'RUB',
		'RWF',
		'SAR',
		'SBD',
		'SCR',
		'SDG',
		'SEK',
		'SGD',
		'SHP',
		'SLL',
		'SOS',
		'SRD',
		'SSP',
		'STD',
		'STN',
		'SVC',
		'SYP',
		'SZL',
		'THB',
		'TJS',
		'TMT',
		'TND',
		'TOP',
		'TRY',
		'TTD',
		'TWD',
		'TZS',
		'UAH',
		'UGX',
		'USD',
		'UYU',
		'UZS',
		'VES',
		'VND',
		'VUV',
		'WST',
		'XAF',
		'XAG',
		'XAU',
		'XCD',
		'XDR',
		'XOF',
		'XPD',
		'XPF',
		'XPT',
		'YER',
		'ZAR',
		'ZMW',
		'ZWL',
	);
}


// endregion _________________________________________________________________________________ \\
// region PLUGIN'S SETTINGS ==============================================================================.

/** Check .agent/rules/settings.md for a guide on how to use Plugin's settings */

// SETTINGS FROM THE SETTINGS PAGE.
const AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS                      = 'show_advanced_settings';
const AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS                  = 'global_custom_instructions';
const AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE                    = 'default_editor_view_mode';
const AI4SEO_SETTING_ENABLE_NATIVE_BULK_ACTIONS                  = 'enable_native_bulk_actions';
const AI4SEO_SETTING_VISIBLE_META_TAGS                           = 'visible_meta_tags'; // deprecated < 2.2.0.
const AI4SEO_SETTING_ACTIVE_META_TAGS                            = 'active_meta_tags'; // added in 2.2.0.
const AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS                = 'metadata_custom_instructions';
const AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS      = 'metadata_post_type_custom_instructions';
const AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE                = 'metadata_fallback_meta_title';
const AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION          = 'metadata_fallback_meta_description';
const AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE            = 'metadata_fallback_facebook_title';
const AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION      = 'metadata_fallback_facebook_description';
const AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE             = 'metadata_fallback_twitter_title';
const AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION       = 'metadata_fallback_twitter_description';
const AI4SEO_SETTING_META_TAG_OUTPUT_MODE                        = 'meta_tags_output_method';
const AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS    = 'apply_changes_to_this_party_seo_plugins';
const AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS   = 'enable_external_metadata_generate_buttons';
const AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA                    = 'sync_only_these_metadata';
const AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE                = 'metadata_generation_language';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE   = 'attachment_attributes_generation_language';
const AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES                = 'active_attachment_attributes';
const AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA                 = 'overwrite_existing_metadata';
const AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES    = 'overwrite_existing_attachment_attributes';
const AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES = 'generate_metadata_for_fully_covered_entries';
const AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES = 'generate_attachment_attributes_for_fully_covered_entries';
const AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION                   = 'enable_render_level_alt_text_injection';
const AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION                             = 'enable_js_alt_text_injection';
const AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE                               = 'image_title_injection_mode';
const AI4SEO_SETTING_METADATA_PREFIXES                                        = 'metadata_prefix';
const AI4SEO_SETTING_METADATA_SUFFIXES                                        = 'metadata_suffix';
const AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA                        = 'include_product_price_in_metadata';
const AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA            = 'focus_keyphrase_behavior_on_existing_metadata';
const AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE                       = 'use_existing_metadata_as_reference';

// Shared view modes for the Metadata and Media Attributes editors.
const AI4SEO_EDITOR_VIEW_MODE_PREVIEW = 'preview';
const AI4SEO_EDITOR_VIEW_MODE_EDITOR  = 'editor';
const AI4SEO_EDITOR_VIEW_MODES        = array(
	AI4SEO_EDITOR_VIEW_MODE_PREVIEW,
	AI4SEO_EDITOR_VIEW_MODE_EDITOR,
);

// Prompt sliders are saved settings that the RobHub API reads as staged generation guidance.
const AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH = 'metadata_existing_values_reference_strength';
const AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE          = 'metadata_focus_keyphrase_influence';
const AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY              = 'metadata_seo_keyword_intensity';
const AI4SEO_SETTING_METADATA_COMMERCIAL_TONE                    = 'metadata_commercial_tone';
const AI4SEO_SETTING_METADATA_SOCIAL_VARIATION                   = 'metadata_social_variation';
const AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE    = 'metadata_website_brand_context_influence';
const AI4SEO_SETTING_METADATA_TONE_VARIANT                       = 'metadata_tone_variant';

// Generation-length controls are separate saved sliders so each output field can resolve its own request contract.
const AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH           = 'metadata_meta_title_generation_length';
const AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH     = 'metadata_meta_description_generation_length';
const AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH       = 'metadata_facebook_title_generation_length';
const AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH = 'metadata_facebook_description_generation_length';
const AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH        = 'metadata_twitter_title_generation_length';
const AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH  = 'metadata_twitter_description_generation_length';

const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS       = 'attachment_attributes_custom_instructions';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES                  = 'attachment_attributes_prefix';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES                  = 'attachment_attributes_suffix';
const AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS          = 'enable_external_media_generate_buttons';
const AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE = 'use_existing_attachment_attributes_as_reference';

// Media prompt sliders share the same staged-radio renderer as metadata sliders but target media prompts.
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE      = 'attachment_attributes_surrounding_context_influence';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE                = 'attachment_attributes_file_name_influence';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH = 'attachment_attributes_existing_values_reference_strength';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY              = 'attachment_attributes_seo_keyword_intensity';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION      = 'attachment_attributes_recognizable_entity_inclusion';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE    = 'attachment_attributes_website_brand_context_influence';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT                       = 'attachment_attributes_tone_variant';
const AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH         = 'attachment_attributes_alt_text_generation_length';
const AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION                       = 'enable_enhanced_entity_recognition';
const AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION                    = 'enable_enhanced_celebrity_recognition';
const AI4SEO_SETTING_IMAGE_UPLOAD_METHOD                                      = 'image_upload_method';
const AI4SEO_SETTING_ALLOWED_USER_ROLES                                       = 'allowed_user_roles';
const AI4SEO_SETTING_DISABLED_POST_TYPES                                      = 'disabled_post_types';
const AI4SEO_SETTING_DISABLED_POST_AUTHORS                                    = 'disabled_post_authors';
const AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS                         = 'disabled_attachment_post_authors';
const AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES                         = 'disabled_metadata_wpml_languages';
const AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES            = 'disabled_attachment_attributes_wpml_languages';
const AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS                                  = 'disabled_taxonomy_terms';
const AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM              = 'exclude_posts_if_any_disabled_taxonomy_term';
const AI4SEO_SETTING_BULK_GENERATION_DURATION                                 = 'bulk_generation_duration';
const AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS                              = 'disable_heavy_db_operations';
const AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE                              = 'enable_frontend_cache_purge';
const AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES                           = 'deep_context_search_for_images';
const AI4SEO_SETTING_DEBUG_OUTPUT_MODE                                        = 'debug_output_mode';
const AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE                                     = 'query_ids_chunk_size';

// settings option values.
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_GENERATE_KEYPHRASE = 'generate_keyphrase';
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP               = 'skip';
const AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE         = 'regenerate';

const AI4SEO_AVAILABLE_METADATA_IDENTIFIERS = array(
	'focus-keyphrase',
	'meta-title',
	'meta-description',
	'keywords',
	'facebook-title',
	'facebook-description',
	'twitter-title',
	'twitter-description',
);

const AI4SEO_AVAILABLE_ATTACHMENT_ATTRIBUTE_IDENTIFIERS = array(
	'title',
	'alt-text',
	'caption',
	'description',
);

const AI4SEO_AVAILABLE_DEBUG_OUTPUT_MODE_OPTIONS = array(
	'none',
	'error_log',
	'file',
	'database',
	'notice',
	'print_r',
);

const AI4SEO_AVAILABLE_META_TAG_OUTPUT_MODE_OPTIONS = array(
	'disable',
	'force',
	'replace',
	'complement',
);

const AI4SEO_AVAILABLE_IMAGE_TITLE_INJECTION_MODE_OPTIONS = array(
	'disabled',
	'inject_title',
	'inject_alt_text',
	'inject_caption',
	'inject_description',
);

const AI4SEO_AVAILABLE_INCLUDE_PRODUCT_PRICE_IN_METADATA_OPTIONS = array(
	'never',
	'fixed',
	'dynamic',
);

const AI4SEO_AVAILABLE_FOCUS_KEYPHRASE_BEHAVIOR_OPTIONS = array(
	AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP,
	AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_GENERATE_KEYPHRASE,
	AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_REGENERATE,
);

// Stage counts keep import/save validation aligned with each slider's supported prompt-control range.
const AI4SEO_PROMPT_SLIDER_SETTING_STAGE_COUNTS = array(
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE => 5,
	AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH => 5,
	AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE    => 5,
	AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY        => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY => 5,
	AI4SEO_SETTING_METADATA_COMMERCIAL_TONE              => 5,
	AI4SEO_SETTING_METADATA_SOCIAL_VARIATION             => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION => 5,
	AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE => 5,
	AI4SEO_SETTING_METADATA_TONE_VARIANT                 => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT    => 5,
	AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH => 5,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH => 5,
);

// Paid-stage requirements are shared by rendering, request serialization, and effective-stage resolution.
const AI4SEO_GENERATION_LENGTH_STAGE_MINIMUM_PLANS = array(
	'4' => 's',
	'5' => 'm',
);

// Reuse title stages for metadata and both social networks so their contracts cannot drift.
const AI4SEO_GENERATION_LENGTH_TITLE_STAGES = array(
	'1' => array(
		'min-length' => 30,
		'max-length' => 45,
	),
	'2' => array(
		'min-length' => 45,
		'max-length' => 65,
	),
	'3' => array(
		'min-length' => 65,
		'max-length' => 85,
	),
	'4' => array(
		'min-length' => 85,
		'max-length' => 110,
	),
	'5' => array(
		'min-length' => 110,
		'max-length' => 135,
	),
);

// Meta descriptions use a wider search-result window than title or social-description fields.
const AI4SEO_GENERATION_LENGTH_META_DESCRIPTION_STAGES = array(
	'1' => array(
		'min-length' => 105,
		'max-length' => 130,
	),
	'2' => array(
		'min-length' => 130,
		'max-length' => 160,
	),
	'3' => array(
		'min-length' => 160,
		'max-length' => 210,
	),
	'4' => array(
		'min-length' => 210,
		'max-length' => 270,
	),
	'5' => array(
		'min-length' => 270,
		'max-length' => 335,
	),
);

// Facebook descriptions retain the established social-preview progression.
const AI4SEO_GENERATION_LENGTH_FACEBOOK_DESCRIPTION_STAGES = array(
	'1' => array(
		'min-length' => 65,
		'max-length' => 90,
	),
	'2' => array(
		'min-length' => 90,
		'max-length' => 120,
	),
	'3' => array(
		'min-length' => 120,
		'max-length' => 155,
	),
	'4' => array(
		'min-length' => 155,
		'max-length' => 195,
	),
	'5' => array(
		'min-length' => 195,
		'max-length' => 250,
	),
);

// Twitter descriptions use a slightly wider recommended window while keeping adjacent stages contiguous.
const AI4SEO_GENERATION_LENGTH_TWITTER_DESCRIPTION_STAGES = array(
	'1' => array(
		'min-length' => 65,
		'max-length' => 90,
	),
	'2' => array(
		'min-length' => 90,
		'max-length' => 125,
	),
	'3' => array(
		'min-length' => 125,
		'max-length' => 155,
	),
	'4' => array(
		'min-length' => 155,
		'max-length' => 195,
	),
	'5' => array(
		'min-length' => 195,
		'max-length' => 250,
	),
);

// Alt text keeps its own accessibility-oriented progression and larger later-stage windows.
const AI4SEO_GENERATION_LENGTH_ALT_TEXT_STAGES = array(
	'1' => array(
		'min-length' => 70,
		'max-length' => 95,
	),
	'2' => array(
		'min-length' => 95,
		'max-length' => 125,
	),
	'3' => array(
		'min-length' => 125,
		'max-length' => 160,
	),
	'4' => array(
		'min-length' => 160,
		'max-length' => 205,
	),
	'5' => array(
		'min-length' => 205,
		'max-length' => 260,
	),
);

// This registry is the plugin-side source of truth for slider labels and entitlement-aware quality diagnostics.
const AI4SEO_GENERATION_LENGTH_SETTING_DETAILS = array(
	AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'meta-title',
		'stages'           => AI4SEO_GENERATION_LENGTH_TITLE_STAGES,
	),
	AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'meta-description',
		'stages'           => AI4SEO_GENERATION_LENGTH_META_DESCRIPTION_STAGES,
	),
	AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'facebook-title',
		'stages'           => AI4SEO_GENERATION_LENGTH_TITLE_STAGES,
	),
	AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'facebook-description',
		'stages'           => AI4SEO_GENERATION_LENGTH_FACEBOOK_DESCRIPTION_STAGES,
	),
	AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'twitter-title',
		'stages'           => AI4SEO_GENERATION_LENGTH_TITLE_STAGES,
	),
	AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH => array(
		'context'          => 'metadata',
		'field-identifier' => 'twitter-description',
		'stages'           => AI4SEO_GENERATION_LENGTH_TWITTER_DESCRIPTION_STAGES,
	),
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH => array(
		'context'          => 'attachment_attributes',
		'field-identifier' => 'alt-text',
		'stages'           => AI4SEO_GENERATION_LENGTH_ALT_TEXT_STAGES,
	),
);

const AI4SEO_AVAILABLE_GENERATION_LANGUAGE_OPTIONS = array(
	'albanian',
	'arabic',
	'bulgarian',
	'chinese',
	'simplified chinese',
	'traditional chinese',
	'croatian',
	'czech',
	'danish',
	'dutch',
	'american english',
	'british english',
	'estonian',
	'finnish',
	'european french',
	'canadian french',
	'german',
	'greek',
	'hebrew',
	'hindi',
	'hungarian',
	'icelandic',
	'indonesian',
	'italian',
	'japanese',
	'korean',
	'latvian',
	'lithuanian',
	'macedonian',
	'maltese',
	'norwegian',
	'polish',
	'european portuguese',
	'brazilian portuguese',
	'romanian',
	'russian',
	'serbian',
	'slovak',
	'slovenian',
	'spanish',
	'swedish',
	'thai',
	'turkish',
	'ukrainian',
	'vietnamese',
);

const AI4SEO_EXPORTABLE_SETTING_PAGE_SETTINGS = array(
	AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS,
	AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS,
	AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE,
	AI4SEO_SETTING_ENABLE_NATIVE_BULK_ACTIONS,
	AI4SEO_SETTING_ACTIVE_META_TAGS,
	AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS,
	AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS,
	AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE,
	AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION,
	AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE,
	AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION,
	AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE,
	AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION,
	AI4SEO_SETTING_META_TAG_OUTPUT_MODE,
	AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS,
	AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS,
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

	// Prompt-control sliders are exportable because they are normal saved settings.
	AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH,
	AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE,
	AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY,
	AI4SEO_SETTING_METADATA_COMMERCIAL_TONE,
	AI4SEO_SETTING_METADATA_SOCIAL_VARIATION,
	AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE,
	AI4SEO_SETTING_METADATA_TONE_VARIANT,
	AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH,
	AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH,
	AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH,
	AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH,
	AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH,
	AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES,
	AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS,
	AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE,

	// Media prompt controls are exported beside legacy compatibility settings used during migration.
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH,
	AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION,
	AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION,
	AI4SEO_SETTING_IMAGE_UPLOAD_METHOD,
	AI4SEO_SETTING_ALLOWED_USER_ROLES,
	AI4SEO_SETTING_DISABLED_POST_TYPES,
	AI4SEO_SETTING_DISABLED_POST_AUTHORS,
	AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS,
	AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES,
	AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES,
	AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS,
	AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM,
	AI4SEO_SETTING_BULK_GENERATION_DURATION,
	AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS,
	AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE,
	AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES,
	AI4SEO_SETTING_DEBUG_OUTPUT_MODE,
	AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE,
);

const AI4SEO_ALL_SETTING_PAGE_SETTINGS = AI4SEO_EXPORTABLE_SETTING_PAGE_SETTINGS + array();

// SETTINGS FROM THE SEO AUTOPILOT MODAL.
const AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES     = 'enabled_bulk_generation_post_types';
const AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES     = 'bulk_generation_auto_queue_entries';
const AI4SEO_SETTING_BULK_GENERATION_ORDER                  = 'bulk_generation_order';
const AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER = 'bulk_generation_new_or_existing_filter';

const AI4SEO_EXPORTABLE_SEO_AUTOPILOT_SETTINGS = array(
	AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES,
	AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES,
	AI4SEO_SETTING_BULK_GENERATION_ORDER,
	AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER,
);

// SETTINGS FROM THE ACCOUNT PAGE.
const AI4SEO_SETTING_ENABLE_INCOGNITO_MODE                = 'enable_incognito_mode';
const AI4SEO_SETTING_INCOGNITO_MODE_USER_ID               = 'incognito_mode_user_id';
const AI4SEO_SETTING_ENABLE_WHITE_LABEL                   = 'enable_white_label';
const AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME        = 'installed_plugins_plugin_name';
const AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION = 'installed_plugins_plugin_description';
const AI4SEO_SETTING_ADD_GENERATOR_HINTS                  = 'add_generator_hints';
const AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT        = 'meta_tags_block_starting_hint';
const AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT          = 'meta_tags_block_ending_hint';

const AI4SEO_EXPORTABLE_ACCOUNT_PAGE_SETTINGS = array(
	AI4SEO_SETTING_ENABLE_WHITE_LABEL,
	AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME,
	AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION,
	AI4SEO_SETTING_ADD_GENERATOR_HINTS,
	AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT,
	AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT,
);

// SETTINGS FROM THE GET MORE CREDITS MODAL.
const AI4SEO_SETTING_PREFERRED_CURRENCY   = 'preferred_currency';
const AI4SEO_SETTING_PAYG_ENABLED         = 'payg_enabled';
const AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID = 'payg_stripe_price_id';
const AI4SEO_SETTING_PAYG_DAILY_BUDGET    = 'payg_daily_budget';
const AI4SEO_SETTING_PAYG_MONTHLY_BUDGET  = 'payg_monthly_budget';

const AI4SEO_EXPORTABLE_GET_MORE_CREDITS_MODAL_SETTINGS = array(
	AI4SEO_SETTING_PREFERRED_CURRENCY,
	AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID,
	AI4SEO_SETTING_PAYG_DAILY_BUDGET,
	AI4SEO_SETTING_PAYG_MONTHLY_BUDGET,
);

// NOT IMPORTABLE SETTINGS.
const AI4SEO_NOT_IMPORTABLE_SETTINGS = array(
	AI4SEO_SETTING_INCOGNITO_MODE_USER_ID,
);

// DEFAULT SETTINGS.
const AI4SEO_DEFAULT_SETTINGS = array(
	AI4SEO_SETTING_SHOW_ADVANCED_SETTINGS                 => 'hide',
	AI4SEO_SETTING_GLOBAL_CUSTOM_INSTRUCTIONS             => '',
	AI4SEO_SETTING_DEFAULT_EDITOR_VIEW_MODE               => AI4SEO_EDITOR_VIEW_MODE_PREVIEW,
	AI4SEO_SETTING_ENABLE_NATIVE_BULK_ACTIONS             => false,
	AI4SEO_SETTING_BULK_GENERATION_DURATION               => 60,
	AI4SEO_SETTING_META_TAG_OUTPUT_MODE                   => 'replace',
	AI4SEO_SETTING_APPLY_CHANGES_TO_THIRD_PARTY_SEO_PLUGINS => array(),
	AI4SEO_SETTING_ENABLE_EXTERNAL_METADATA_GENERATE_BUTTONS => false,
	AI4SEO_SETTING_SYNC_ONLY_THESE_METADATA               => array( 'focus-keyphrase', 'meta-title', 'meta-description', 'keywords', 'facebook-title', 'facebook-description', 'twitter-title', 'twitter-description' ),
	AI4SEO_SETTING_ALLOWED_USER_ROLES                     => array( 'administrator' ),
	AI4SEO_SETTING_DISABLED_POST_TYPES                    => array(),
	AI4SEO_SETTING_DISABLED_POST_AUTHORS                  => array(),
	AI4SEO_SETTING_DISABLED_ATTACHMENT_POST_AUTHORS       => array(),
	AI4SEO_SETTING_DISABLED_METADATA_WPML_LANGUAGES       => array(),
	AI4SEO_SETTING_DISABLED_ATTACHMENT_ATTRIBUTES_WPML_LANGUAGES => array(),
	AI4SEO_SETTING_DISABLED_TAXONOMY_TERMS                => array(),
	AI4SEO_SETTING_EXCLUDE_POSTS_IF_ANY_DISABLED_TAXONOMY_TERM => false,
	AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES     => array(),
	AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES     => true,
	AI4SEO_SETTING_BULK_GENERATION_ORDER                  => 'newest',
	AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER => 'both',
	AI4SEO_SETTING_METADATA_GENERATION_LANGUAGE           => 'auto',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_GENERATION_LANGUAGE => 'auto',
	AI4SEO_SETTING_ACTIVE_META_TAGS                       => array( 'focus-keyphrase', 'meta-title', 'meta-description', 'keywords', 'facebook-title', 'facebook-description', 'twitter-title', 'twitter-description' ),
	AI4SEO_SETTING_METADATA_CUSTOM_INSTRUCTIONS           => '',
	AI4SEO_SETTING_METADATA_POST_TYPE_CUSTOM_INSTRUCTIONS => array(),
	AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE           => 'no-fallback',
	AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION     => 'no-fallback',
	AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE       => 'no-fallback',
	AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION => 'no-fallback',
	AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE        => 'no-fallback',
	AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION  => 'no-fallback',
	AI4SEO_SETTING_ACTIVE_ATTACHMENT_ATTRIBUTES           => array( 'title', 'alt-text', 'caption', 'description' ),
	AI4SEO_SETTING_OVERWRITE_EXISTING_METADATA            => array(),
	AI4SEO_SETTING_OVERWRITE_EXISTING_ATTACHMENT_ATTRIBUTES => array(),
	AI4SEO_SETTING_GENERATE_METADATA_FOR_FULLY_COVERED_ENTRIES => false,
	AI4SEO_SETTING_INCLUDE_PRODUCT_PRICE_IN_METADATA      => 'never',
	AI4SEO_SETTING_FOCUS_KEYPHRASE_BEHAVIOR_ON_EXISTING_METADATA => AI4SEO_FOCUS_KEYPHRASE_BEHAVIOR_SKIP,
	AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE     => false,

	// New installs use balanced prompt-slider defaults until users choose weaker or stronger generation guidance.
	AI4SEO_SETTING_METADATA_EXISTING_VALUES_REFERENCE_STRENGTH => '3',
	AI4SEO_SETTING_METADATA_FOCUS_KEYPHRASE_INFLUENCE     => '3',
	AI4SEO_SETTING_METADATA_SEO_KEYWORD_INTENSITY         => '3',
	AI4SEO_SETTING_METADATA_COMMERCIAL_TONE               => '3',
	AI4SEO_SETTING_METADATA_SOCIAL_VARIATION              => '3',
	AI4SEO_SETTING_METADATA_WEBSITE_BRAND_CONTEXT_INFLUENCE => '3',
	AI4SEO_SETTING_METADATA_TONE_VARIANT                  => '3',

	// Option 2 preserves the generation windows used before per-field length controls were introduced.
	AI4SEO_SETTING_METADATA_META_TITLE_GENERATION_LENGTH  => '2',
	AI4SEO_SETTING_METADATA_META_DESCRIPTION_GENERATION_LENGTH => '2',
	AI4SEO_SETTING_METADATA_FACEBOOK_TITLE_GENERATION_LENGTH => '2',
	AI4SEO_SETTING_METADATA_FACEBOOK_DESCRIPTION_GENERATION_LENGTH => '2',
	AI4SEO_SETTING_METADATA_TWITTER_TITLE_GENERATION_LENGTH => '2',
	AI4SEO_SETTING_METADATA_TWITTER_DESCRIPTION_GENERATION_LENGTH => '2',

	AI4SEO_SETTING_GENERATE_ATTACHMENT_ATTRIBUTES_FOR_FULLY_COVERED_ENTRIES => false,
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS => '',
	AI4SEO_SETTING_ENABLE_RENDER_LEVEL_ALT_TEXT_INJECTION => false,
	AI4SEO_SETTING_ENABLE_JS_ALT_TEXT_INJECTION           => false,
	AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS            => false,
	AI4SEO_SETTING_ENABLE_FRONTEND_CACHE_PURGE            => false,
	AI4SEO_SETTING_DEEP_CONTEXT_SEARCH_FOR_IMAGES         => false,
	AI4SEO_SETTING_DEBUG_OUTPUT_MODE                      => 'none',
	AI4SEO_SETTING_QUERY_IDS_CHUNK_SIZE                   => 1000,
	AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE => false,

	// Media prompt-slider defaults mirror the same balanced baseline as metadata.
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SURROUNDING_CONTEXT_INFLUENCE => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_FILE_NAME_INFLUENCE => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_EXISTING_VALUES_REFERENCE_STRENGTH => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SEO_KEYWORD_INTENSITY => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_RECOGNIZABLE_ENTITY_INCLUSION => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_WEBSITE_BRAND_CONTEXT_INFLUENCE => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_TONE_VARIANT     => '3',
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_ALT_TEXT_GENERATION_LENGTH => '2',
	AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION     => true,
	AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION  => false,
	AI4SEO_SETTING_IMAGE_TITLE_INJECTION_MODE             => 'disabled',
	AI4SEO_SETTING_METADATA_PREFIXES                      => array(
		'meta-title'           => '',
		'meta-description'     => '',
		'facebook-title'       => '',
		'facebook-description' => '',
		'twitter-title'        => '',
		'twitter-description'  => '',
	),
	AI4SEO_SETTING_METADATA_SUFFIXES                      => array(
		'meta-title'           => '',
		'meta-description'     => '',
		'facebook-title'       => '',
		'facebook-description' => '',
		'twitter-title'        => '',
		'twitter-description'  => '',
	),
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_PREFIXES         => array(
		'title'       => '',
		'alt-text'    => '',
		'caption'     => '',
		'description' => '',
	),
	AI4SEO_SETTING_ATTACHMENT_ATTRIBUTES_SUFFIXES         => array(
		'title'       => '',
		'alt-text'    => '',
		'caption'     => '',
		'description' => '',
	),
	AI4SEO_SETTING_ENABLE_EXTERNAL_MEDIA_GENERATE_BUTTONS => false,
	AI4SEO_SETTING_IMAGE_UPLOAD_METHOD                    => 'auto',
	AI4SEO_SETTING_ENABLE_INCOGNITO_MODE                  => false,
	AI4SEO_SETTING_INCOGNITO_MODE_USER_ID                 => '0',
	AI4SEO_SETTING_ENABLE_WHITE_LABEL                     => false,
	AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_NAME          => AI4SEO_PLUGIN_NAME,
	AI4SEO_SETTING_INSTALLED_PLUGINS_PLUGIN_DESCRIPTION   => AI4SEO_PLUGIN_DESCRIPTION,
	AI4SEO_SETTING_ADD_GENERATOR_HINTS                    => true,
	AI4SEO_SETTING_META_TAGS_BLOCK_STARTING_HINT          => '[{NAME}] This site is optimized with the {NAME} plugin v{VERSION} - {WEBSITE}',
	AI4SEO_SETTING_META_TAGS_BLOCK_ENDING_HINT            => '[{NAME}] End',
	AI4SEO_SETTING_PREFERRED_CURRENCY                     => 'usd',
	AI4SEO_SETTING_PAYG_ENABLED                           => false,
	AI4SEO_SETTING_PAYG_STRIPE_PRICE_ID                   => '', // defaults to previously purchased pack.
	AI4SEO_SETTING_PAYG_DAILY_BUDGET                      => 0, // defaults to cost credits pack.
	AI4SEO_SETTING_PAYG_MONTHLY_BUDGET                    => 0, // defaults to recommended credits pack entry,.
);

const AI4SEO_METADATA_FALLBACK_MAPPING = array(
	'meta-title'           => AI4SEO_SETTING_METADATA_FALLBACK_META_TITLE,
	'meta-description'     => AI4SEO_SETTING_METADATA_FALLBACK_META_DESCRIPTION,
	'facebook-title'       => AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_TITLE,
	'facebook-description' => AI4SEO_SETTING_METADATA_FALLBACK_FACEBOOK_DESCRIPTION,
	'twitter-title'        => AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_TITLE,
	'twitter-description'  => AI4SEO_SETTING_METADATA_FALLBACK_TWITTER_DESCRIPTION,
);

$ai4seo_settings                          = AI4SEO_DEFAULT_SETTINGS;
$ai4seo_are_settings_initialized          = false;
$ai4seo_settings_request_cache_scope      = '';
$ai4seo_settings_request_cache_by_site    = array();
$ai4seo_settings_scopes_being_initialized = array();

$ai4seo_fallback_allowed_user_roles  = array( 'administrator' => 'Administrator' );
$ai4seo_forbidden_allowed_user_roles = array( 'subscriber', 'customer' );
$ai4seo_can_manage_this_plugin       = null; // cache variable.

const AI4SEO_AVAILABLE_BULK_GENERATION_ORDER_OPTIONS                  = array( 'random', 'oldest', 'newest' );
const AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS = array( 'both', 'new', 'existing' );
const AI4SEO_AVAILABLE_QUERY_IDS_CHUNK_SIZE_OPTIONS                   = array( 250, 500, 1000, 2500, 5000, 10000, 20000 );

// init parameters with translation.
add_action(
	'init',
	function () {
		define(
			'AI4SEO_BULK_GENERATION_ORDER_TRANSLATED_OPTIONS',
			array(
				'random' => __( 'Random', 'ai-for-seo' ),
				'oldest' => __( 'Oldest to newest', 'ai-for-seo' ),
				'newest' => __( 'Newest to oldest', 'ai-for-seo' ),
			)
		);

		define(
			'AI4SEO_BULK_GENERATION_NEW_OR_EXISTING_FILTER_TRANSLATED_OPTIONS',
			array(
				'both'     => __( 'New entries and existing entries', 'ai-for-seo' ),
				'new'      => __( 'New entries only', 'ai-for-seo' ),
				'existing' => __( 'Existing entries only', 'ai-for-seo' ),
			)
		);
	},
	9
);

const AI4SEO_NOTIFICATION_AUTO_DISMISS_DAYS          = 7;
const AI4SEO_ENVIRONMENTAL_VARIABLE_CACHE_TTL_SUFFIX = '__ttl_time';


// endregion _________________________________________________________________________________ \\
// region ENVIRONMENTAL VARIABLES ==============================================================================.

const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION                             = 'last_known_plugin_version';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL                                    = 'last_cronjob_call';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SPECIFIC_CRON_JOB_CALLS                          = 'last_specific_cronjob_call';
const AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST                                  = 'cron_job_status_list';
const AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LAST_UPDATE_TIMES                     = 'cron_job_status_last_update_times';
const AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_TOC_AND_PP_ACCEPTED_TIME                          = 'tos_toc_and_pp_accepted_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_TOS_DETAILS_CHECKSUM                             = 'last_tos_details_checksum';
const AI4SEO_ENVIRONMENTAL_VARIABLE_TOS_LAST_MODAL_OPEN_TIME                              = 'tos_last_modal_open_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED                           = 'enhanced_reporting_accepted';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_ACCEPTED_TIME                      = 'enhanced_reporting_accepted_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ENHANCED_REPORTING_REVOKED_TIME                       = 'enhanced_reporting_revoke_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_WEBSITE_TOC_AND_PP_UPDATE_TIME                   = 'last_website_toc_and_pp_update_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME = 'bulk_generation_new_or_existing_filter_reference_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING                               = 'has_purchased_something';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME                        = 'last_seo_autopilot_set_up_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT                            = 'unread_notifications_count';
const AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES                    = 'num_last_known_posts_table_entries';
const AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES                       = 'num_current_posts_table_entries';
const AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES                    = 'num_current_postmeta_table_entries';
const AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT                                      = 'current_discount';
const AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME                                = 'plugin_activation_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME                        = 'last_performance_analysis_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS                                      = 'payg_status';
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON                              = 'payg_failure_reason';
const AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME                    = 'just_purchased_something_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME           = 'payg_low_credits_first_occurrence_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME                  = 'payg_low_credits_last_sync_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID                = 'posts_table_analysis_last_post_id';
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE                       = 'posts_table_analysis_state';
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME                  = 'posts_table_analysis_start_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME          = 'posts_table_analysis_last_core_run_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE          = 'generation_status_summary_rebuild_state';
const AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED                       = 'auto_retry_failed_required';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE             = 'active_metadata_migration_v235_state';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME      = 'active_metadata_migration_v235_started_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME     = 'active_metadata_migration_v235_last_run_time';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES = 'active_metadata_migration_v235_processed_entries';
const AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE                       = 'supported_post_types_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE                     = 'available_post_authors_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE                   = 'supported_taxonomy_terms_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE                       = 'attachment_id_lookup_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE                       = 'nextgen_picture_pids_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE              = 'nextgen_imported_images_count_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE                                = 'max_post_id_cache';
const AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER                           = 'claimed_feedback_offer';

const AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES = array(
	AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_KNOWN_PLUGIN_VERSION => '0.0.0',
	AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_CRON_JOB_CALL       => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_CRON_JOB_STATUS_LIST     => array(),
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
	AI4SEO_ENVIRONMENTAL_VARIABLE_HAS_PURCHASED_SOMETHING  => false,
	AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_SEO_AUTOPILOT_SET_UP_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_UNREAD_NOTIFICATIONS_COUNT => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_LAST_KNOWN_POSTS_TABLE_ENTRIES => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTS_TABLE_ENTRIES => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_NUM_CURRENT_POSTMETA_TABLE_ENTRIES => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_CURRENT_DISCOUNT         => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_PLUGIN_ACTIVATION_TIME   => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_LAST_PERFORMANCE_ANALYSIS_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_STATUS              => 'idle',
	AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_FAILURE_REASON      => '',
	AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_FIRST_OCCURRENCE_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_PAYG_LOW_CREDITS_LAST_SYNC_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_POST_ID => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_STATE => 'idle',
	AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_START_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_POSTS_TABLE_ANALYSIS_LAST_CORE_RUN_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_GENERATION_STATUS_SUMMARY_REBUILD_STATE => 'idle',
	AI4SEO_ENVIRONMENTAL_VARIABLE_AUTO_RETRY_FAILED_REQUIRED => false,
	AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE => 'completed',
	AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_POST_TYPES_CACHE => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_AVAILABLE_POST_AUTHORS_CACHE => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_SUPPORTED_TAXONOMY_TERMS_CACHE => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_ATTACHMENT_ID_LOOKUP_CACHE => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_PICTURE_PIDS_CACHE => array(),
	AI4SEO_ENVIRONMENTAL_VARIABLE_NEXTGEN_IMPORTED_IMAGES_COUNT_CACHE => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_MAX_POST_ID_CACHE        => 0,
	AI4SEO_ENVIRONMENTAL_VARIABLE_CLAIMED_FEEDBACK_OFFER   => false,
);

$ai4seo_environmental_variables            = AI4SEO_DEFAULT_ENVIRONMENTAL_VARIABLES;
$ai4seo_environmental_variables_are_loaded = false; // cache variable.

$ai4seo_persistent_does_user_need_to_accept_tos_toc_and_pp = null; // cache variable
// Debug cache-busting remains request-wide; update refreshes are scoped later to authorized plugin admin pages.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cache-busting flag; no state is changed.
$ai4seo_scripts_version_number = isset( $_GET['ai4seo_debug_uncached_assets'] ) && sanitize_text_field( wp_unslash( $_GET['ai4seo_debug_uncached_assets'] ) )
	? time()
	: AI4SEO_PLUGIN_VERSION_NUMBER;

$ai4seo_user_has_at_least_plan                         = array(); // cache variable to store if user has at least a specific plan.
$ai4seo_user_has_active_generation_length_subscription = array(); // Request cache for expiry-aware length-stage entitlement.

// used to store various details about all supported metadata fields to use it on many places throughout the plugin.
add_action(
	'init',
	function () {
		define(
			'AI4SEO_METADATA_DETAILS',
			array(
				'focus-keyphrase'      => array(
					'name'              => esc_html__( 'Focus Keyphrase', 'ai-for-seo' ),
					'icon'              => 'flag',
					'input'             => 'textfield',
					'hint'              => esc_html__( '<strong>Best Practice:</strong> A primary SEO keyword or keyphrase that best represents the main topic of this entry. It should be specific, relevant, and reflect the content accurately to help improve search engine rankings.<br><br>The focus keyphrase is added to the meta title and meta description for best SEO results. Make sure to first generate the keyphrase before generating the meta title and meta description or just generate all at once.', 'ai-for-seo' ),
					'api-identifier'    => 'focus_keyphrase',
					'flat-credits-cost' => 2,
				),
				'meta-title'           => array(
					'name'                       => esc_html__( 'Meta Title', 'ai-for-seo' ),
					'icon'                       => 'globe',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> A unique and concise title for this entry, which will be displayed on search engine results pages (SERPs) and in the browser tab. This helps users understand your content and enhances visibility.<br><br>The recommended natural quality window is <strong>45 to 65</strong> characters. Your active Generation Length setting may use a different target.<br><br>The meta title is added to the <strong>title tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_title',
					'output-tag-type'            => 'title',
					'output-tag-identifier'      => '',
					'meta-tag-regex'             => '/<title>(.*?)<\/title>/is',
					'meta-tag-regex-match-index' => 1,
					'flat-credits-cost'          => 1,
				),
				'meta-description'     => array(
					'name'                       => esc_html__( 'Meta Description', 'ai-for-seo' ),
					'icon'                       => 'globe',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> A compelling and relevant meta description for your page or post, which will appear on search engine results pages (SERPs) beneath the meta title. This description provides a summary of your content, helping to attract clicks and improve visibility.<br><br>The recommended natural quality window is <strong>130 to 160</strong> characters. Your active Generation Length setting may use a different target.<br><br>The meta description is added to the <strong>meta description tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_description',
					'output-tag-type'            => 'meta name',
					'output-tag-identifier'      => 'description',
					'meta-tag-regex'             => '/<meta\s+[^>]*name=(["\'])description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 2,
				),
				'keywords'             => array(
					'name'                       => esc_html__( 'Keywords', 'ai-for-seo' ),
					'icon'                       => 'hashtag',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> Provide a concise, comma-separated list of up to <strong>10</strong> relevant keywords for compatibility with tools and content consumers that still read this field. Focus on specific phrases that describe the content and avoid duplicates or keyword stuffing.<br><br>Google Search does not use the <strong>meta keywords tag</strong> for web ranking.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_keywords',
					'output-tag-type'            => 'meta name',
					'output-tag-identifier'      => 'keywords',
					'meta-tag-regex'             => '/<meta\s+[^>]*name=(["\'])keywords\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 1,
				),
				'facebook-title'       => array(
					'name'                       => esc_html__( 'Facebook Title', 'ai-for-seo' ),
					'icon'                       => 'square-facebook',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> This title will be displayed as the headline in the preview when your content is shared on Facebook, helping to capture attention and increase engagement.<br><br>The recommended natural quality window is <strong>45 to 65</strong> characters. Your active Generation Length setting may use a different target.<br><br>The Facebook title is added to the <strong>og:title tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_facebook_title',
					'output-tag-type'            => 'meta property',
					'output-tag-identifier'      => 'og:title',
					'meta-tag-regex'             => '/<meta\s+[^>]*property=(["\'])og:title\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 1,
				),
				'facebook-description' => array(
					'name'                       => esc_html__( 'Facebook Description', 'ai-for-seo' ),
					'icon'                       => 'square-facebook',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> This description will appear in the preview when your content is shared, providing a summary that encourages users to engage with your content.<br><br>The recommended natural quality window is <strong>90 to 120</strong> characters. Your active Generation Length setting may use a different target.<br><br>The Facebook description is added to the <strong>og:description tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_facebook_description',
					'output-tag-type'            => 'meta property',
					'output-tag-identifier'      => 'og:description',
					'meta-tag-regex'             => '/<meta\s+[^>]*property=(["\'])og:description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 1,
				),
				'twitter-title'        => array(
					'name'                       => esc_html__( 'Twitter/X Title', 'ai-for-seo' ),
					'icon'                       => 'square-twitter-x',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> An attention-grabbing title for your page or post, optimized for sharing on Twitter/X. This title will be displayed as the headline in the preview when your content is tweeted, helping to increase visibility and encourage clicks.<br><br>The recommended natural quality window is <strong>45 to 65</strong> characters. Your active Generation Length setting may use a different target.<br><br>The Twitter/X title is added to the <strong>twitter:title tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_twitter_title',
					'output-tag-type'            => 'meta name',
					'output-tag-identifier'      => 'twitter:title',
					'meta-tag-regex'             => '/<meta\s+[^>]*name=(["\'])twitter:title\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 1,
				),
				'twitter-description'  => array(
					'name'                       => esc_html__( 'Twitter/X Description', 'ai-for-seo' ),
					'icon'                       => 'square-twitter-x',
					'input'                      => 'textarea',
					'hint'                       => __( '<strong>Best Practice:</strong> A concise and engaging description for your page or post, optimized for sharing on Twitter/X. This description will appear in the preview when your content is tweeted, providing a brief summary that encourages users to click and interact.<br><br>The recommended natural quality window is <strong>90 to 125</strong> characters. Your active Generation Length setting may use a different target.<br><br>The Twitter/X description is added to the <strong>twitter:description tag</strong> of your website.', 'ai-for-seo' ),
					'api-identifier'             => 'meta_twitter_description',
					'output-tag-type'            => 'meta name',
					'output-tag-identifier'      => 'twitter:description',
					'meta-tag-regex'             => '/<meta\s+[^>]*name=(["\'])twitter:description\1[^>]*content=(["\'])(.*?)\2[^>]*>/i',
					'meta-tag-regex-match-index' => 3,
					'flat-credits-cost'          => 1,
				),
			)
		);

		define(
			'AI4SEO_ATTACHMENT_ATTRIBUTES_DETAILS',
			array(
				'title'       => array(
					'name'                   => esc_html__( 'Title', 'ai-for-seo' ),
					'icon'                   => 'headline',
					'mime-type-restrictions' => array(),
					'input-type'             => 'textarea',
					'hint'                   => __( '<strong>Best Practice:</strong> A descriptive and unique title for your image that helps users and search engines understand the content of the image. This title is displayed when the image is loaded in the browser and may be used as the default filename if someone downloads the image.<br><br>The AI aims to generate an image title within a natural quality window of <strong>50 to 80</strong> characters.<br><br>The image title is not directly visible on your website but is stored in the <strong>image metadata</strong>. A well-crafted title can aid in organizing your media library and improve searchability within WordPress.', 'ai-for-seo' ),
					'api-identifier'         => 'image_title',
					'flat-credits-cost'      => 1,
				),
				'alt-text'    => array(
					'name'                   => esc_html__( 'Alt Text', 'ai-for-seo' ),
					'icon'                   => 'code',
					'mime-type-restrictions' => array(
						'image/jpeg',
						'image/gif',
						'image/png',
						'image/bmp',
						'image/tiff',
						'image/webp',
						'image/avif',
						'image/x-icon',
						'image/heic',
					),
					'input-type'             => 'textarea',
					// phpcs:ignore -- Keep this translated registry entry aligned with the neighboring field declarations.
					'hint'                   => __( '<strong>Best Practice:</strong> An informative and clear alt text for your image that describes its content and function. This text is used by screen readers to assist visually impaired users and is displayed in place of the image if it cannot be loaded. It also contributes to SEO by providing context to search engines.<br><br>Alt text is added to the <strong>alt attribute</strong> of the image HTML tag.', 'ai-for-seo' ),
					'api-identifier'         => 'image_alt_text',
					'flat-credits-cost'      => 2,
				),
				'caption'     => array(
					'name'                   => esc_html__( 'Caption', 'ai-for-seo' ),
					'icon'                   => 'subtitle',
					'mime-type-restrictions' => array(),
					'input-type'             => 'textarea',
					'hint'                   => __( '<strong>Best Practice:</strong> A brief and engaging caption for your image that provides additional context or credit information. Captions are typically displayed below the image on your website and can help enhance user engagement and provide useful information.<br><br>The AI aims to generate a caption within a natural quality window of <strong>130 to 160</strong> characters.<br><br>The caption is added to the <strong>caption field</strong> in the WordPress Media Library and is displayed directly on the page where the image appears.', 'ai-for-seo' ),
					'api-identifier'         => 'image_caption',
					'flat-credits-cost'      => 1,
				),
				'description' => array(
					'name'                   => esc_html__( 'Description', 'ai-for-seo' ),
					'icon'                   => 'subtitles',
					'mime-type-restrictions' => array(),
					'input-type'             => 'textarea',
					'hint'                   => __( "<strong>Best Practice:</strong> A detailed and informative description of your image, which helps users understand the image's content and context. This description is particularly useful for internal reference and can aid in organizing and managing your media library.<br><br>The AI aims to generate a description within a natural quality window of <strong>180 to 240</strong> characters.<br><br>The description is stored in the <strong>image metadata</strong> and is not directly visible to users on your website. A well-crafted description can aid in organizing your media library and improve searchability within WordPress.", 'ai-for-seo' ),
					'api-identifier'         => 'image_description',
					'flat-credits-cost'      => 1,
				),
			)
		);

		define(
			'AI4SEO_TRANSLATED_LANGUAGE_NAMES',
			array(
				'en' => __( 'English', 'ai-for-seo' ),
				'de' => __( 'German', 'ai-for-seo' ),
				'fr' => __( 'French', 'ai-for-seo' ),
				'es' => __( 'Spanish', 'ai-for-seo' ),
				'it' => __( 'Italian', 'ai-for-seo' ),
				'nl' => __( 'Dutch', 'ai-for-seo' ),
				'pt' => __( 'Portuguese', 'ai-for-seo' ),
				'ru' => __( 'Russian', 'ai-for-seo' ),
				'zh' => __( 'Chinese', 'ai-for-seo' ),
				'ja' => __( 'Japanese', 'ai-for-seo' ),
				'ko' => __( 'Korean', 'ai-for-seo' ),
			)
		);
	},
	7
);

/**
 * Return the narrow HTML policy for API-provided notification messages.
 *
 * @return array Allowed remote-message tags mapped to their permitted attributes.
 */
function ai4seo_get_remote_notification_allowed_html_tags_and_attributes(): array {
	// Keep API-controlled markup isolated so future changes to the rich local allowlist cannot widen this policy.
	static $ai4seo_remote_notification_allowed_html_tags_and_attributes = array(
		'a'      => array(
			'href' => array(),
		),
		'p'      => array(),
		'br'     => array(),
		'strong' => array(),
		'em'     => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
	);

	return $ai4seo_remote_notification_allowed_html_tags_and_attributes;
}


/**
 * Return the HTML tags and attributes allowed in plugin output.
 *
 * @return array Allowed tags mapped to their permitted attributes.
 */
function ai4seo_get_allowed_html_tags_and_attributes(): array {
	static $ai4seo_allowed_html_tags_and_attributes = array(
		'div'      => array(
			'id'                                         => array(),
			'class'                                      => array(),
			'hidden'                                     => array(),
			'onclick'                                    => array(),
			'style'                                      => array(),
			'title'                                      => array(),
			'role'                                       => array(),
			'aria-describedby'                           => array(),
			'aria-labelledby'                            => array(),
			'aria-hidden'                                => array(),
			'aria-live'                                  => array(),
			'aria-label'                                 => array(),
			'data-ai4seo-non-persistent'                 => array(),
			'data-ai4seo-slider-input'                   => array(),
			'data-ai4seo-preview-card'                   => array(),
			'data-ai4seo-preview-field'                  => array(),
			'data-ai4seo-editor-field-source-identifier' => array(),
			// Keep evaluation labels available after modal output passes through the plugin KSES allowlist.
			'data-ai4seo-editor-evaluation-label'        => array(),
		),
		'details'  => array(
			'class' => array(),
			'open'  => array(),
		),
		'summary'  => array(
			'class' => array(),
		),
		'img'      => array(
			'class'   => array(),
			'src'     => array(),
			'alt'     => array(),
			'onclick' => array(),
			'style'   => array(),
		),
		'meta'     => array(
			'name'     => array(),
			'content'  => array(),
			'property' => array(),
		),
		'title'    => array(),
		'svg'      => array(
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
		'defs'     => array(),
		'style'    => array(
			'type'  => array(),
			'media' => true,
		),
		'desc'     => array(),
		'path'     => array(
			'class'             => array(),
			'd'                 => array(),
			'fill-rule'         => array(),
			'fill'              => array(),
			'clip-rule'         => array(),
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
		'g'        => array(
			'class'     => true,
			'id'        => true,
			'transform' => true,
		),
		'circle'   => array(
			'cx'           => array(),
			'cy'           => array(),
			'r'            => array(),
			'fill'         => array(),
			'class'        => true,
			'id'           => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
			'transform'    => true,
		),
		'rect'     => array(
			'width'        => array(),
			'height'       => array(),
			'rx'           => array(),
			'ry'           => array(),
			'x'            => array(),
			'y'            => array(),
			'fill'         => array(),
			'class'        => true,
			'id'           => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
			'transform'    => true,
		),
		'line'     => array(
			'x1'           => array(),
			'y1'           => array(),
			'x2'           => array(),
			'y2'           => array(),
			'stroke'       => array(),
			'stroke-width' => array(),
			'class'        => true,
			'id'           => true,
			'opacity'      => true,
			'transform'    => true,
		),
		'polygon'  => array(
			'points'       => array(),
			'fill'         => array(),
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
		'ellipse'  => array(
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
		'use'      => array(
			'xlink:href' => true,
			'href'       => true,
			'x'          => true,
			'y'          => true,
			'width'      => true,
			'height'     => true,
		),
		'text'     => array(
			'x'           => array(),
			'y'           => array(),
			'font-size'   => array(),
			'font-family' => array(),
			'font-weight' => array(),
			'fill'        => array(),
		),
		'button'   => array(
			'type'                            => array(),
			'onclick'                         => array(),
			'class'                           => array(),
			'id'                              => array(),
			'disabled'                        => array(),
			'style'                           => array(),
			'title'                           => array(),
			'data-clipboard-text'             => array(),
			'data-time-left'                  => array(),
			'data-ai4seo-generation-action'   => array(),
			'data-ai4seo-generation-fields'   => array(),
			'data-ai4seo-editor-view-mode'    => array(),
			'data-ai4seo-preview-edit-target' => array(),
			'aria-controls'                   => array(),
			'aria-expanded'                   => array(),
			'aria-label'                      => array(),
			'aria-pressed'                    => array(),
		),
		'span'     => array(
			'id'                                        => array(),
			'class'                                     => array(),
			'style'                                     => array(),
			'data-trigger'                              => array(),
			'data-time-left'                            => array(),
			'data-post-type'                            => array(),
			'data-label'                                => array(),
			'data-ai4seo-editor-source-message-default' => array(),
			'aria-hidden'                               => array(),
			'hidden'                                    => array(),
			'onclick'                                   => array(),
		),
		'h1'       => array(
			'class' => array(),
			'style' => array(),
		),
		'h2'       => array(
			'class' => array(),
			'style' => array(),
		),
		// Preview-card headings use the next semantic level below the modal workspace title.
		'h3'       => array(
			'class' => array(),
			'style' => array(),
		),
		'p'        => array(
			'id'              => array(),
			'class'           => array(),
			'style'           => array(),
			'data-input-id'   => array(),
			'data-max-length' => array(),
			'hidden'          => array(),
			'aria-live'       => array(),
			'aria-atomic'     => array(),
		),
		'b'        => array(),
		'u'        => array(),
		'a'        => array(
			'href'           => array(),
			'target'         => array(),
			'rel'            => array(),
			'title'          => array(),
			'class'          => array(),
			'onclick'        => array(),
			'data-time-left' => array(),
			'aria-label'     => array(),
		),
		'i'        => array(
			'onclick'     => array(),
			'class'       => array(),
			'id'          => array(),
			'style'       => array(),
			'aria-hidden' => array(),
		),
		'select'   => array(
			'id'               => array(),
			'name'             => array(),
			'class'            => array(),
			'style'            => array(),
			'onchange'         => array(),
			'aria-describedby' => array(),
		),
		'option'   => array(
			'value'                   => array(),
			'selected'                => array(),
			'data-ai4seo-description' => array(),
		),
		'br'       => array(),
		'hr'       => array(
			'class' => array(),
		),
		'strong'   => array(
			'class' => array(),
		),
		'input'    => array(
			'type'                           => array(),
			'id'                             => array(),
			'class'                          => array(),
			'style'                          => array(),
			'value'                          => array(),
			'name'                           => array(),
			'placeholder'                    => array(),
			'checked'                        => array(),
			'onchange'                       => array(),
			'onclick'                        => array(),
			'disabled'                       => array(),
			'data-target'                    => array(),
			'data-ai4seo-non-persistent'     => array(),
			'data-ai4seo-slider-description' => array(),
			'data-ai4seo-slider-note'        => array(),
			'aria-describedby'               => array(),
			'data-ai4seo-min-length'         => array(),
			'data-ai4seo-max-length'         => array(),
		),
		'textarea' => array(
			'id'                                    => array(),
			'name'                                  => array(),
			'class'                                 => array(),
			'placeholder'                           => array(),
			'style'                                 => array(),
			'onchange'                              => array(),
			'onclick'                               => array(),
			'disabled'                              => array(),
			'aria-describedby'                      => array(),
			'data-ai4seo-min-length'                => array(),
			'data-ai4seo-max-length'                => array(),
			'data-ai4seo-custom-instructions-limit' => array(),
			'data-ai4seo-custom-instructions-label' => array(),
		),
		'label'    => array(
			'for'   => array(),
			'class' => array(),
			'style' => array(),
			'title' => array(),
		),
		'center'   => array(),
		'ol'       => array(
			'class' => array(),
			'style' => array(),
		),
		'ul'       => array(
			'class'                                      => array(),
			'style'                                      => array(),
			'data-ai4seo-defer-status-filters'           => array(),
			'data-ai4seo-content-context'                => array(),
			'data-ai4seo-post-type'                      => array(),
			'data-ai4seo-filter-text'                    => array(),
			'data-ai4seo-filter-status'                  => array(),
			'data-ai4seo-orderby'                        => array(),
			'data-ai4seo-order'                          => array(),
			'data-ai4seo-retry-all-failed-button-target' => array(),
			'data-ai4seo-hydration-hidden-fields'        => array(),
		),
		'li'       => array(
			'class'     => array(),
			'style'     => array(),
			'aria-live' => array(),
		),
		'em'       => array(),
		'form'     => array(
			'id'     => array(),
			'class'  => array(),
			'style'  => array(),
			'method' => array(),
			'action' => array(),
		),
		'pre'      => array(
			'class' => array(),
			'style' => array(),
		),
		'code'     => array(
			'class' => array(),
			'style' => array(),
		),
		'small'    => array(
			'class' => array(),
			'style' => array(),
		),
	);

	return $ai4seo_allowed_html_tags_and_attributes;
}


$ai4seo_cached_active_plugins_and_themes                = array();
$ai4seo_active_plugins_and_themes_request_cache_scope   = '';
$ai4seo_active_plugins_and_themes_request_cache_by_site = array();
$ai4seo_cached_supported_post_types                     = array();
$ai4seo_checked_supported_post_types                    = array();
$ai4seo_allowed_image_mime_types                        = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif' );
$ai4seo_allowed_image_file_type_names                   = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' );

/**
 * Returns allowed attachment mime types.
 *
 * @return array
 */
function ai4seo_get_allowed_attachment_mime_types(): array {
	return array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif' );
}


// Define the constants for full and base language code mappings.
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
	'zh' => 'chinese',  // General Chinese fallback.
	'hr' => 'croatian',
	'cs' => 'czech',
	'da' => 'danish',
	'nl' => 'dutch',
	'en' => 'english',  // General English fallback.
	'et' => 'estonian',
	'fi' => 'finnish',
	'fr' => 'french',   // General French fallback.
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
	'pt' => 'portuguese',  // General Portuguese fallback.
	'ro' => 'romanian',
	'ru' => 'russian',
	'sr' => 'serbian',
	'sk' => 'slovak',
	'sl' => 'slovenian',
	'es' => 'spanish',  // General Spanish fallback.
	'sv' => 'swedish',
	'th' => 'thai',
	'tr' => 'turkish',
	'uk' => 'ukrainian',
	'vi' => 'vietnamese',
);

// Allowed logged-in AJAX function names. Localized to JavaScript from this canonical list.
const AI4SEO_ALLOWED_AJAX_FUNCTIONS = array(
	'ai4seo_save_anything',
	'ai4seo_show_metadata_editor',
	'ai4seo_show_get_more_credits_modal',
	'ai4seo_show_attachment_attributes_editor',
	'ai4seo_show_related_attachments',
	'ai4seo_check_attachment_usage_context',
	'ai4seo_generate_metadata',
	'ai4seo_generate_attachment_attributes',
	'ai4seo_reject_tos',
	'ai4seo_accept_tos',
	'ai4seo_show_terms_of_service',
	'ai4seo_dismiss_notification',
	'ai4seo_get_dashboard_html',
	'ai4seo_reset_plugin_data',
	'ai4seo_clear_debug_message_log',
	'ai4seo_stop_bulk_generation',
	'ai4seo_clear_bulk_generation_queue',
	'ai4seo_apply_bulk_generation_queue_action',
	'ai4seo_show_bulk_custom_instructions_modal',
	'ai4seo_apply_bulk_custom_instructions_action',
	'ai4seo_get_content_type_status_filters',
	'ai4seo_retry_all_failed_attachment_attributes',
	'ai4seo_retry_all_failed_metadata',
	'ai4seo_disable_payg',
	'ai4seo_init_purchase',
	'ai4seo_init_subscription_pricing',
	'ai4seo_import_nextgen_gallery_images',
	'ai4seo_export_settings',
	'ai4seo_show_import_settings_preview',
	'ai4seo_import_settings',
	'ai4seo_restore_default_settings',
	'ai4seo_refresh_dashboard_statistics',
	'ai4seo_refresh_robhub_account',
	'ai4seo_submit_feedback',
);

// AJAX actions that change or expose site-wide account, configuration, or operational state.
const AI4SEO_ADMINISTRATIVE_AJAX_FUNCTIONS = array(
	'ai4seo_show_get_more_credits_modal',
	'ai4seo_reject_tos',
	'ai4seo_accept_tos',
	'ai4seo_show_terms_of_service',
	'ai4seo_dismiss_notification',
	'ai4seo_reset_plugin_data',
	'ai4seo_clear_debug_message_log',
	'ai4seo_stop_bulk_generation',
	'ai4seo_clear_bulk_generation_queue',
	'ai4seo_retry_all_failed_attachment_attributes',
	'ai4seo_retry_all_failed_metadata',
	'ai4seo_disable_payg',
	'ai4seo_init_purchase',
	'ai4seo_init_subscription_pricing',
	'ai4seo_import_nextgen_gallery_images',
	'ai4seo_export_settings',
	'ai4seo_show_import_settings_preview',
	'ai4seo_import_settings',
	'ai4seo_restore_default_settings',
	'ai4seo_refresh_dashboard_statistics',
	'ai4seo_refresh_robhub_account',
	'ai4seo_submit_feedback',
);

// the robhub api communicator is used to communicate with the robhub api which handles all the AI operations.
$ai4seo_robhub_api = null;


// endregion _________________________________________________________________________________ \\
// endregion _________________________________________________________________________________.
