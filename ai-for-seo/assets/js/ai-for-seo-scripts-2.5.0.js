/**
 * Prepare AI for SEO admin script globals.
 *
 * @package AI_For_SEO
 */

let ai4seo_remaining_credits = 0;
const ai4seo_admin_plugin_page_url = ai4seo_get_full_domain() + '/wp-admin/admin.php?page=ai-for-seo';
const ai4seo_admin_installed_plugins_page_url = ai4seo_get_full_domain() + '/wp-admin/plugins.php';
const ai4seo_official_contact_url = 'https://sooz.ai/contact';
let ai4seo_mousedown_origin = null;
const AI4SEO_GLOBAL_NONCE_IDENTIFIER = 'ai4seo_ajax_nonce';
let ai4seo_unsaved_changes_navigation_initialized = false;
const ai4seo_unsaved_changes_data_attribute = 'ai4seo-unsaved-changes';
const ai4seo_dashboard_debug_counter_enabled = false; // Toggle debug counter visibility.
const ai4seo_dashboard_debug_metrics = false;
const ai4seo_dashboard_refresh_interval = 10000; // 10 seconds
const ai4seo_dashboard_max_failures = 5;
let ai4seo_output_console_debug = false; // or false to disable all console.debug output.

// Persist the counter across repeated AJAX initialization so generated tooltip IDs remain unique.
let ai4seo_tooltip_id_counter = 0;
let ai4seo_tooltip_document_close_handler_initialized = false;

// Keep shared global handlers stable while element-level initialization stays root-scoped.
let ai4seo_sticky_modal_footer_resize_handler = null;
let ai4seo_pending_third_party_sync_warning_was_consumed = false;

// Keep the chosen editor mode for this page session while allowing a reload to restore the saved default.
const ai4seo_editor_runtime_view_modes = {};

// Keep initialization and live refresh aligned on the same supported metadata and media editor controls.
const ai4seo_editor_length_feedback_input_selector = 'input[id^="ai4seo_metadata_"][type="text"], textarea[id^="ai4seo_metadata_"], input[id^="ai4seo_attachment_attribute_"][type="text"], textarea[id^="ai4seo_attachment_attribute_"]';

// Tie pending work and observers to dynamic modal elements without retaining workspaces after removal.
const ai4seo_editor_preview_refresh_workspaces = new WeakSet();
const ai4seo_editor_preview_resize_observers = new WeakMap();
let ai4seo_editor_preview_window_resize_initialized = false;

// Keep WordPress media lifecycle hooks and root-scoped initialization stable across repeated entrypoints.
let ai4seo_wordpress_media_attachment_add_listener_initialized = false;
let ai4seo_wordpress_media_lifecycle_events_initialized = false;
const ai4seo_wordpress_media_initialized_frames = new WeakSet();
const ai4seo_wordpress_media_pending_init_roots = new Set();
let ai4seo_wordpress_media_init_requires_root_discovery = false;
let ai4seo_wordpress_media_init_request_id = null;
let ai4seo_wordpress_media_fallback_timer = null;
let ai4seo_wordpress_media_fallback_render_observer = null;
let ai4seo_wordpress_media_fallback_render_observer_timeout = null;
let ai4seo_wordpress_media_standard_signal_generation = 0;
let ai4seo_wordpress_media_last_standard_signal_timestamp = 0;

// Gutenberg attachment controls follow editor lifecycle signals instead of recurring DOM scans.
const ai4seo_gutenberg_block_inspector_selector = '.block-editor-block-inspector';
const ai4seo_gutenberg_attachment_input_selector = '.components-textarea-control__input';
const ai4seo_gutenberg_editor_iframe_selector = 'iframe[name="editor-canvas"]';
const ai4seo_gutenberg_media_observer_target_selectors = [
	'.interface-interface-skeleton',
	'.edit-post-layout',
	'.edit-site-layout'
];
let ai4seo_gutenberg_media_store_unsubscribe = null;
let ai4seo_gutenberg_media_store_subscription_initialized = false;
let ai4seo_gutenberg_media_observer = null;
let ai4seo_gutenberg_media_observer_target = null;
let ai4seo_gutenberg_media_init_request_id = null;
let ai4seo_gutenberg_media_selected_block_signature = null;
let ai4seo_gutenberg_media_lifecycle_events_initialized = false;
let ai4seo_gutenberg_media_dom_ready_callback_registered = false;
const ai4seo_gutenberg_media_bound_iframes = new Set();

// Coalesce dynamic HTML initialization requests without losing their narrowest shared DOM root.
let ai4seo_scheduled_html_elements_init_scope = null;
let ai4seo_scheduled_html_elements_init_request_id = null;

// Keep click-driven parent-frame integrations isolated from the normal primary-bundle bootstrap.
const ai4seo_external_metadata_integration_group = 'metadata';
const ai4seo_external_media_integration_group = 'media';
const ai4seo_top_frame_integration_bootstrap_attribute = 'data-ai4seo-top-frame-integration-bootstrap';
const ai4seo_pending_top_frame_integration_groups_key = 'ai4seo_pending_top_frame_integration_groups';
const ai4seo_top_frame_integration_bootstrap_state_key = 'ai4seo_top_frame_integration_bootstrap_state';
const ai4seo_primary_bundle_script_element = document.currentScript;
const ai4seo_is_top_frame_integration_bootstrap = ai4seo_primary_bundle_script_element !== null
	&& ai4seo_primary_bundle_script_element.getAttribute( ai4seo_top_frame_integration_bootstrap_attribute ) === 'true';
const ai4seo_scheduled_external_integration_groups = new Set();
let ai4seo_scheduled_external_integration_groups_request_id = null;
let ai4seo_elementor_app_bar_metadata_editor_action_is_registered = false;

// Keep the Gutenberg header shortcut attached while React mounts or replaces its settings controls.
const ai4seo_gutenberg_header_metadata_editor_mutation_selector = '.editor-header__settings, .interface-pinned-items, .editor-post-publish-button, .ai4seo-header-builder-button';
let ai4seo_gutenberg_header_metadata_editor_observer = null;
let ai4seo_gutenberg_header_metadata_editor_observer_target = null;

// Runtime helpers may run before DOM ready when a click-loaded bundle is scheduled from its load event.
if (typeof window.ai4seo_page_load_time === 'undefined') {
	window.ai4seo_page_load_time = Date.now();
}

// Keep delegated button press handlers and their active state stable across repeated initialization.
let ai4seo_button_press_handlers_initialized = false;
let ai4seo_last_pressed_button = null;

// Keep pending AIOSEO values available while its Facebook and X editors are lazily mounted.
const ai4seo_aioseo_pending_metadata = {};
let ai4seo_is_applying_metadata_to_live_aioseo_editor = false;
let ai4seo_aioseo_pending_flush_timer = null;
let ai4seo_aioseo_pending_flush_attempts = 0;
const ai4seo_aioseo_pending_flush_retry_interval_ms = 50;
const ai4seo_aioseo_pending_flush_max_attempts = 20;

// Keep generated SEOPress values available while its React tabs are lazily mounted.
const ai4seo_seopress_pending_metadata = {};
let ai4seo_is_applying_metadata_to_live_seopress_editor = false;
const ai4seo_seopress_pending_generate_all_metadata_by_root = new WeakMap();
const ai4seo_seopress_save_bridge_requests_by_root = new WeakMap();
const ai4seo_seopress_editor_root_selector = '[id^="seopress-js-module-seo-metabox"]';

const ai4seo_slim_seo_editor_root_selector = '#slim-seo #ss-single';

// One registry keeps Slim SEO's generation selectors and lazy-field observer aligned.
const ai4seo_slim_seo_generation_field_details = {
	'meta-title': {
		'editor_selector': 'input[name="slim_seo[title]"]',
		'key_by_key': true,
	},
	'meta-description': {
		'editor_selector': 'textarea[name="slim_seo[description]"]',
		'key_by_key': false,
	},
};

// Squirrly loads and replaces its complete snippet editor through AJAX.
const ai4seo_squirrly_editor_root_selector = '#sq_blocksnippet';

// Yoast mounts its Gutenberg metabox fields asynchronously inside this stable React root.
const ai4seo_yoast_editor_root_selector = '#wpseo-metabox-root';

// One registry keeps Squirrly's search, Open Graph, Twitter, and keyword fields aligned.
const ai4seo_squirrly_generation_field_details = {
	'meta-title': {
		'editor_selector': 'textarea[name="sq_title"]',
		'key_by_key': true,
	},
	'meta-description': {
		'editor_selector': 'textarea[name="sq_description"]',
		'key_by_key': false,
	},
	'keywords': {
		'editor_selector': 'input[name="sq_keywords"]',
		'key_by_key': false,
	},
	'facebook-title': {
		'editor_selector': 'textarea[name="sq_og_title"]',
		'key_by_key': false,
	},
	'facebook-description': {
		'editor_selector': 'textarea[name="sq_og_description"]',
		'key_by_key': false,
	},
	'twitter-title': {
		'editor_selector': 'textarea[name="sq_tw_title"]',
		'key_by_key': false,
	},
	'twitter-description': {
		'editor_selector': 'textarea[name="sq_tw_description"]',
		'key_by_key': false,
	},
};

// Define every JavaScript-rendered SEO editor once, then activate only server-approved entries.
const ai4seo_generation_editor_integration_definitions = new Map( [
	[
		'yoast-seo',
		{
			'root_selector': ai4seo_yoast_editor_root_selector,
			'init_generation_ui': ai4seo_init_yoast_generation_ui,
			'rescan_generation_ui': ai4seo_init_yoast_generation_ui,
		},
	],
	[
		'all-in-one-seo-pack',
		{
			'root_selector': '.aioseo-post-settings',
			'init_generation_ui': ai4seo_init_aioseo_generation_ui,
			'rescan_generation_ui': ai4seo_rescan_aioseo_generation_ui,
		},
	],
	[
		'seopress',
		{
			'root_selector': ai4seo_seopress_editor_root_selector,
			'init_generation_ui': ai4seo_init_seopress_generation_ui,
			'rescan_generation_ui': ai4seo_rescan_seopress_generation_ui,
		},
	],
	[
		'slim-seo',
		{
			'root_selector': ai4seo_slim_seo_editor_root_selector,
			'init_generation_ui': ai4seo_init_slim_seo_generation_ui,
			'rescan_generation_ui': ai4seo_rescan_slim_seo_generation_ui,
		},
	],
	[
		'squirrly-seo',
		{
			'root_selector': ai4seo_squirrly_editor_root_selector,
			'init_generation_ui': ai4seo_init_squirrly_generation_ui,
			'rescan_generation_ui': ai4seo_rescan_squirrly_generation_ui,
		},
	],
	] );

// One discovery observer finds active integration roots; narrow field observers stay root-local.
let ai4seo_generation_editor_discovery_observer = null;
let ai4seo_generation_editor_discovery_observer_target = null;
let ai4seo_generation_editor_target_lifecycle_observer = null;
let ai4seo_generation_editor_target_lifecycle_observer_target = null;
let ai4seo_generation_editor_target_resync_timer = null;
let ai4seo_active_generation_editor_integrations = new Map();
const ai4seo_generation_editor_field_observers = new Map();
const ai4seo_pending_generation_editor_rescans = new Map();
let ai4seo_generation_editor_rescan_timer = null;
let ai4seo_generation_editor_lifecycle_initialized = false;

// The SEO Framework renders all supported fields within one stable post editor metabox.
const ai4seo_the_seo_framework_editor_root_selector = '#tsf-inpost-box';

// One registry keeps The SEO Framework's search, Open Graph, and Twitter selectors aligned.
const ai4seo_the_seo_framework_generation_field_details = {
	'meta-title': {
		'editor_selector': '#autodescription_title',
		'key_by_key': true,
	},
	'meta-description': {
		'editor_selector': '#autodescription_description',
		'key_by_key': false,
	},
	'facebook-title': {
		'editor_selector': '#autodescription_og_title',
		'key_by_key': false,
	},
	'facebook-description': {
		'editor_selector': '#autodescription_og_description',
		'key_by_key': false,
	},
	'twitter-title': {
		'editor_selector': '#autodescription_twitter_title',
		'key_by_key': false,
	},
	'twitter-description': {
		'editor_selector': '#autodescription_twitter_description',
		'key_by_key': false,
	},
};

// Carry third-party synchronization warnings across the full-page reload that follows a save.
const ai4seo_pending_third_party_sync_warning_storage_key = 'ai4seo-pending-third-party-sync-warning';
const ai4seo_pending_third_party_sync_warning_lifetime_ms = 60000;

// One field registry keeps AIOSEO's hidden payload, visual editors, and generation controls aligned.
const ai4seo_aioseo_generation_field_details = {
	'meta-title': {
		'post_settings_key': 'title',
		'store_update_method': 'updateTitle',
		'visual_editor_selector': '#aioseo-post-settings-post-title-row .ql-editor',
		'key_by_key': true,
	},
	'meta-description': {
		'post_settings_key': 'description',
		'store_update_method': 'updateDescription',
		'visual_editor_selector': '#aioseo-post-settings-meta-description-row .ql-editor',
		'key_by_key': false,
	},
	'facebook-title': {
		'post_settings_key': 'og_title',
		'visual_editor_selector': '.facebook-title-settings .ql-editor',
		'key_by_key': false,
	},
	'facebook-description': {
		'post_settings_key': 'og_description',
		'visual_editor_selector': '.facebook-description-settings .ql-editor',
		'key_by_key': false,
	},
	'twitter-title': {
		'post_settings_key': 'twitter_title',
		'visual_editor_selector': '.twitter-title-settings .ql-editor',
		'key_by_key': false,
	},
	'twitter-description': {
		'post_settings_key': 'twitter_description',
		'visual_editor_selector': '.twitter-description-settings .ql-editor',
		'key_by_key': false,
	},
};

// One registry covers SEOPress's universal React editor, classic metabox, and Generate All proxies.
const ai4seo_seopress_generation_field_details = {
	'meta-title': {
		'universal_editor_field_selector': 'input[name="title"]',
		'classic_editor_selector': '#seopress_titles_title_meta',
		'save_group': 'title-description',
		'save_parameter': 'title',
		'key_by_key': true,
	},
	'meta-description': {
		'universal_editor_field_selector': 'textarea[name="description"]',
		'classic_editor_selector': '#seopress_titles_desc_meta',
		'save_group': 'title-description',
		'save_parameter': 'description',
		'key_by_key': false,
	},
	'facebook-title': {
		'universal_editor_field_selector': 'input[name="_seopress_social_fb_title"]',
		'classic_editor_selector': '#seopress_social_fb_title_meta',
		'save_group': 'social',
		'save_parameter': '_seopress_social_fb_title',
		'key_by_key': false,
	},
	'facebook-description': {
		'universal_editor_field_selector': 'textarea[name="_seopress_social_fb_desc"]',
		'classic_editor_selector': '#seopress_social_fb_desc_meta',
		'save_group': 'social',
		'save_parameter': '_seopress_social_fb_desc',
		'key_by_key': false,
	},
	'twitter-title': {
		'universal_editor_field_selector': 'input[name="_seopress_social_twitter_title"]',
		'classic_editor_selector': '#seopress_social_twitter_title_meta',
		'save_group': 'social',
		'save_parameter': '_seopress_social_twitter_title',
		'key_by_key': false,
	},
	'twitter-description': {
		'universal_editor_field_selector': 'textarea[name="_seopress_social_twitter_desc"]',
		'classic_editor_selector': '#seopress_social_twitter_desc_meta',
		'save_group': 'social',
		'save_parameter': '_seopress_social_twitter_desc',
		'key_by_key': false,
	},
};

const ai4seo_svg_icons = {
	'circle-check': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
	'circle-xmark': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
	'rotate': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-primary" d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5z"/><path class="fa-secondary" d="M16 319.6l0-7.6c0-13.3 10.7-24 24-24h7.6c.2 0 .5 0 .7 0H168c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6c0-.2 0-.5 0-.7z"/></svg>',
	'sooz-with-ai-for-seo': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 731.25 333.75" width="131.46" height="60" style="max-height:80px; width:auto;" xml:space="preserve"><path fill="#0066aa" d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path fill="#0066aa" d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path fill="#0066aa" d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/><g><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M127.495,252.48c0.96,0,1.8,0.234,2.52,0.701c0.72,0.47,1.2,1.099,1.44,1.891L158.095,327h-10.92l-21.48-61.128c-0.321-1.008-0.66-2.033-1.02-3.078c-0.36-1.043-0.701-2.033-1.02-2.97h-3.36c-0.321,0.937-0.642,1.927-0.96,2.97c-0.321,1.045-0.681,2.07-1.08,3.078L96.775,327h-10.92l26.64-71.928c0.24-0.792,0.72-1.421,1.44-1.891c0.72-0.467,1.56-0.701,2.52-0.701H127.495z M142.855,295.464v8.208h-42v-8.208H142.855z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M183.895,252.48V327h-10.56v-74.52H183.895z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M269.214,274.404v7.235h-39.48v-6.695l11.16-0.54H269.214z M257.334,249.564c1.279,0,2.919,0.019,4.92,0.054c1.999,0.037,4.039,0.107,6.12,0.216c2.08,0.108,3.879,0.27,5.4,0.486l-0.84,6.804h-12c-3.84,0-6.54,0.686-8.1,2.052c-1.56,1.369-2.34,3.637-2.34,6.805V327h-10.2v-61.992c0-3.311,0.559-6.102,1.68-8.37c1.119-2.268,2.919-4.013,5.4-5.237C249.853,250.177,253.173,249.564,257.334,249.564z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M308.693,273.108c6.72,0,12.099,0.9,16.14,2.699c4.039,1.801,6.939,4.735,8.7,8.803c1.759,4.068,2.64,9.449,2.64,16.146s-0.881,12.079-2.64,16.146c-1.761,4.068-4.661,7.003-8.7,8.802c-4.041,1.799-9.42,2.7-16.14,2.7c-6.641,0-11.981-0.901-16.02-2.7c-4.041-1.799-6.96-4.733-8.76-8.802c-1.8-4.067-2.7-9.45-2.7-16.146s0.9-12.077,2.7-16.146c1.8-4.067,4.72-7.002,8.76-8.803C296.712,274.009,302.052,273.108,308.693,273.108z M308.693,280.884c-4.241,0-7.581,0.594-10.02,1.782c-2.441,1.188-4.181,3.223-5.22,6.102c-1.041,2.881-1.56,6.877-1.56,11.988c0,5.113,0.52,9.109,1.56,11.988c1.039,2.881,2.779,4.914,5.22,6.102c2.439,1.188,5.779,1.782,10.02,1.782c4.239,0,7.599-0.594,10.08-1.782c2.479-1.188,4.239-3.221,5.28-6.102c1.039-2.879,1.56-6.875,1.56-11.988c0-5.111-0.521-9.107-1.56-11.988c-1.041-2.879-2.801-4.914-5.28-6.102C316.292,281.478,312.933,280.884,308.693,280.884z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M363.652,274.404l1.32,10.044l0.96,1.62V327h-10.2v-52.596H363.652z M392.332,273.108l-1.2,8.64h-3.36c-3.439,0-6.881,0.631-10.319,1.89c-3.44,1.261-7.641,3.043-12.6,5.347l-0.84-5.725c4.32-3.167,8.659-5.651,13.02-7.452c4.359-1.799,8.58-2.699,12.659-2.699H392.332z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M459.41,251.184c3.918,0.073,7.959,0.254,12.119,0.54c4.159,0.289,8.319,0.829,12.48,1.62l-0.72,6.912c-3.441-0.144-7.241-0.27-11.4-0.378c-4.16-0.108-8.16-0.162-12-0.162c-2.961,0-5.501,0.091-7.62,0.271c-2.12,0.181-3.86,0.612-5.22,1.296c-1.361,0.685-2.34,1.801-2.94,3.348c-0.6,1.549-0.899,3.69-0.899,6.426c0,4.104,0.819,6.967,2.46,8.586c1.639,1.62,4.299,2.827,7.979,3.618l16.8,3.78c6.399,1.368,10.78,3.763,13.141,7.182c2.358,3.421,3.54,8.011,3.54,13.771c0,4.319-0.54,7.813-1.62,10.476c-1.08,2.665-2.741,4.698-4.98,6.103c-2.24,1.403-5.12,2.376-8.64,2.916c-3.521,0.54-7.68,0.81-12.479,0.81c-2.721,0-6.261-0.107-10.62-0.324c-4.361-0.216-9.381-0.793-15.061-1.728l0.72-7.021c4.72,0.146,8.521,0.271,11.4,0.378c2.88,0.108,5.358,0.162,7.44,0.162c2.079,0,4.239,0,6.479,0c4.239,0,7.579-0.286,10.021-0.863c2.439-0.576,4.158-1.729,5.159-3.456c1-1.729,1.5-4.283,1.5-7.668c0-2.879-0.359-5.111-1.079-6.696c-0.721-1.583-1.86-2.789-3.421-3.618c-1.56-0.827-3.539-1.493-5.939-1.998l-17.16-3.888c-6-1.367-10.221-3.743-12.66-7.128c-2.441-3.384-3.66-7.92-3.66-13.608c0-4.32,0.54-7.793,1.62-10.422c1.08-2.627,2.719-4.606,4.92-5.94c2.2-1.331,4.98-2.214,8.341-2.646C450.77,251.4,454.77,251.184,459.41,251.184z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M522.528,252.372c4,0,8.02,0,12.061,0c4.038,0,8.04,0.054,12,0.162c3.96,0.107,7.779,0.27,11.46,0.485l-0.48,7.452h-33.239c-2.481,0-4.302,0.559-5.461,1.675c-1.16,1.116-1.739,2.934-1.739,5.453v44.28c0,2.521,0.579,4.357,1.739,5.508c1.159,1.153,2.979,1.729,5.461,1.729h33.239l0.48,7.344c-3.681,0.216-7.5,0.361-11.46,0.432c-3.96,0.073-7.962,0.125-12,0.162c-4.041,0.036-8.061,0.055-12.061,0.055c-4.88,0-8.741-1.17-11.58-3.511c-2.84-2.339-4.301-5.489-4.38-9.449v-48.816c0.079-4.031,1.54-7.199,4.38-9.504C513.787,253.524,517.648,252.372,522.528,252.372z M508.729,283.8h44.16v7.668h-44.16V283.8z"/><path fill="#4E6C7A" stroke="#4E6C7A" stroke-width="3" stroke-miterlimit="10" d="M607.009,251.076c6,0,11.1,0.647,15.3,1.943c4.2,1.297,7.561,3.439,10.08,6.427c2.521,2.988,4.359,6.966,5.521,11.934c1.158,4.968,1.739,11.089,1.739,18.36c0,7.272-0.581,13.392-1.739,18.359c-1.161,4.969-3,8.947-5.521,11.935c-2.52,2.988-5.88,5.13-10.08,6.426s-9.3,1.944-15.3,1.944s-11.1-0.648-15.3-1.944s-7.581-3.438-10.141-6.426c-2.561-2.987-4.421-6.966-5.58-11.935c-1.16-4.968-1.739-11.087-1.739-18.359c0-7.271,0.579-13.393,1.739-18.36c1.159-4.968,3.02-8.945,5.58-11.934c2.56-2.987,5.94-5.13,10.141-6.427C595.909,251.724,601.009,251.076,607.009,251.076z M607.009,259.608c-5.441,0-9.74,0.937-12.9,2.808c-3.161,1.873-5.399,4.986-6.72,9.342c-1.32,4.357-1.979,10.352-1.979,17.982c0,7.56,0.659,13.537,1.979,17.928c1.32,4.393,3.559,7.524,6.72,9.396c3.16,1.873,7.459,2.808,12.9,2.808c5.439,0,9.738-0.935,12.9-2.808c3.159-1.872,5.399-5.004,6.72-9.396c1.319-4.391,1.979-10.368,1.979-17.928c0-7.631-0.66-13.625-1.979-17.982c-1.32-4.355-3.561-7.469-6.72-9.342C616.747,260.545,612.448,259.608,607.009,259.608z"/></g></svg>',
	'sooz': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="145.28" height="40" viewBox="0 0 731.25 201.25" enable-background="new 0 0 731.25 201.25" xml:space="preserve" fill="#0066aa" style="max-height:40px; width:auto;"><path d="M680.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H721.61v33.215H596.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H558.526V5.501H680.595z"/><path d="M172.98,5.501v33.206H53.139c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825H9.895v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642L35.513,91.627c-9.945-4.309-17.375-10.075-22.329-17.299C8.227,67.104,5.75,58.033,5.75,47.154c0-13.328,3.818-23.594,11.436-30.816C24.799,9.111,36.044,5.501,50.935,5.501H172.98z"/><path d="M521.13,29.148C506.678,13.38,485.924,5.501,458.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489h-25.341c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18h-25.341c-27.207,0-48.001,7.879-62.414,23.646c-14.419,15.76-21.606,39.689-21.606,71.777c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C542.772,68.836,535.582,44.906,521.13,29.148z"/></svg>',
	'sooz-oo': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="72.30" height="40" viewBox="0 0 363.75 201.25" enable-background="new 0 0 363.75 201.25" xml:space="preserve" style="max-height:40px; width:auto;"><path fill="#0066aa" d="M496.595,5.501c14.86,0,26.106,3.61,33.744,10.837c7.631,7.224,11.411,17.488,11.411,30.816c0,10.879-2.508,19.949-7.465,27.174c-4.962,7.225-12.35,12.992-22.256,17.299l-101.367,44.188c-2.998,1.341-5.275,3.212-6.896,5.642c-1.705,2.439-2.519,5.248-2.519,8.437c0,3.749,1.172,6.763,3.448,9.021c2.277,2.241,5.727,3.368,10.356,3.368H537.61v33.215H412.858c-14.862,0-26.074-3.622-33.619-10.825c-7.556-7.225-11.296-17.512-11.296-30.85c0-10.857,2.443-19.928,7.397-27.152s12.509-12.992,22.655-17.299l100.793-44.2c6.416-2.628,9.667-7.328,9.667-14.067c0-3.76-1.181-6.758-3.458-9.019c-2.353-2.249-5.884-3.378-10.597-3.378H374.526V5.501H496.595z"/><path fill="#0066aa" d="M-11.02,5.501v33.206h-119.841c-4.783,0-8.319,1.129-10.598,3.378c-2.31,2.261-3.448,5.259-3.448,9.019c0,6.74,3.207,11.439,9.624,14.067l100.833,44.2c10.111,4.309,17.625,10.075,22.579,17.299c4.957,7.225,7.43,16.295,7.43,27.152c0,13.338-3.733,23.625-11.287,30.85c-7.513,7.203-18.721,10.825-33.586,10.825h-124.792v-33.215h122.56c4.625,0,8.044-1.127,10.355-3.368c2.277-2.259,3.449-5.271,3.449-9.021c0-3.188-0.852-5.997-2.477-8.437c-1.664-2.43-3.973-4.301-6.903-5.642l-101.365-44.188c-9.945-4.309-17.375-10.075-22.329-17.299c-4.958-7.225-7.435-16.295-7.435-27.174c0-13.328,3.818-23.594,11.436-30.816c7.613-7.227,18.859-10.837,33.749-10.837H-11.02z"/><path fill="#0066aa" d="M337.13,29.148C322.678,13.38,301.924,5.501,274.719,5.501h-25.341c-23.143,0-41.59,5.757-55.478,17.18c-2.41,2.002-4.764,4.103-6.902,6.468c-1.857,2.041-3.607,4.24-5.24,6.568c-7.854,11.215-12.879,25.689-15.072,43.319c-0.018,0.103-0.059,0.169-0.074,0.301c-0.042,0.379-0.108,0.956-0.212,1.664c-0.018,0.173-0.025,0.354-0.042,0.515c-0.723,7.373-2.169,27.873-2.868,37.846c-0.303,4.354-0.959,8.685-2.25,12.846c-1.837,5.691-4.224,10.465-7.252,14.359c-0.304,0.388-0.59,0.848-0.91,1.229c-8.167,9.662-21.161,14.489-38.948,14.489H88.788c-18.193,0-31.35-4.786-39.389-14.351c-8.122-9.572-12.142-25.244-12.142-47.02c0-21.947,4.098-37.801,12.264-47.566c8.161-9.75,21.239-14.639,39.266-14.639h25.341c17.991,0,31.027,4.889,39.109,14.639c0.319,0.379,0.598,0.834,0.865,1.23c3.275-11.043,7.721-20.838,13.593-29.162l1.89-2.734c-13.868-11.422-32.355-17.18-55.457-17.18H88.788c-27.207,0-48.001,7.879-62.414,23.646C11.956,44.908,4.768,68.838,4.768,100.926c0,31.901,7.188,55.635,21.606,71.213c14.413,15.578,35.207,23.362,62.414,23.362h25.341c23.076,0,41.541-5.691,55.407-16.929c2.448-2.002,4.82-4.086,6.973-6.433c1.866-2.034,3.599-4.21,5.249-6.499c10.266-14.483,15.609-34.501,16.232-59.908c-0.05-1.585-0.109-3.133-0.109-4.805c0-1.665,0.061-3.227,0.109-4.82c0.565-18.717,4.319-32.613,11.328-41.684c0.308-0.333,0.541-0.74,0.805-1.074c8.188-9.75,21.295-14.639,39.256-14.639h25.341c18.027,0,31.026,4.889,39.139,14.639c8.045,9.766,12.108,25.524,12.108,47.281c0,21.777-4.105,37.502-12.226,47.166c-8.204,9.662-21.161,14.489-39.023,14.489h-25.341c-18.193,0-31.291-4.786-39.395-14.351c-0.2-0.279-0.391-0.6-0.624-0.864c-3.225,10.99-7.763,20.7-13.581,28.94l-1.828,2.561c13.88,11.237,32.318,16.929,55.428,16.929h25.341c27.204,0,47.957-7.784,62.411-23.362c14.452-15.578,21.643-39.312,21.643-71.213C358.772,68.836,351.582,44.906,337.13,29.148z"/></svg>',
	'square-xmark': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
}

// Global label maps for metadata and attachment editors.
const ai4seo_metadata_labels = {
	'focus-keyphrase': wp.i18n.__( 'Focus Keyphrase', 'ai-for-seo' ),
	'meta-title': wp.i18n.__( 'Meta Title', 'ai-for-seo' ),
	'meta-description': wp.i18n.__( 'Meta Description', 'ai-for-seo' ),
	'keywords': wp.i18n.__( 'Keywords', 'ai-for-seo' ),
	'facebook-title': wp.i18n.__( 'Facebook Title', 'ai-for-seo' ),
	'facebook-description': wp.i18n.__( 'Facebook Description', 'ai-for-seo' ),
	'twitter-title': wp.i18n.__( 'Twitter/X Title', 'ai-for-seo' ),
	'twitter-description': wp.i18n.__( 'Twitter/X Description', 'ai-for-seo' ),
};

const ai4seo_attachment_attribute_labels = {
	'title': wp.i18n.__( 'Title', 'ai-for-seo' ),
	'alt-text': wp.i18n.__( 'Alt Text', 'ai-for-seo' ),
	'caption': wp.i18n.__( 'Caption', 'ai-for-seo' ),
	'description': wp.i18n.__( 'Description', 'ai-for-seo' ),
};

const ai4seo_generate_data_for_inputs = {
	// Our Metadata Editor modal-elements.
	'#ai4seo_metadata_focus-keyphrase': {
		'add_generate_button': true,
		'metadata_identifier': 'focus-keyphrase',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	'#ai4seo_metadata_meta-title': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata'
	},
	'#ai4seo_metadata_meta-description': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#ai4seo_metadata_keywords': {
		'add_generate_button': true,
		'metadata_identifier': 'keywords',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	'#ai4seo_metadata_facebook-title': {
		'add_generate_button': true,
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#ai4seo_metadata_facebook-description': {
		'add_generate_button': true,
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	'#ai4seo_metadata_twitter-title': {
		'add_generate_button': true,
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#ai4seo_metadata_twitter-description': {
		'add_generate_button': true,
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	// Our Attachment Attributes Editor modal-elements.
	'#ai4seo_attachment_attribute_title': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'title',
		'key_by_key': false,
		'processing-context': 'attachment-attributes'
	},
	'#ai4seo_attachment_attribute_alt-text': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'processing-context': 'attachment-attributes'
	},
	'#ai4seo_attachment_attribute_caption': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'caption',
		'key_by_key': false,
		'processing-context': 'attachment-attributes'
	},
	'#ai4seo_attachment_attribute_description': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'description',
		'key_by_key': false,
		'processing-context': 'attachment-attributes'
	},

	// Yoast elements.
	'#focus-keyword-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'focus-keyphrase',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_focuskw': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'focus-keyphrase',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast-google-preview-title-metabox > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_title': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast-google-preview-description-metabox > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-description',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_metadesc': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	'#facebook-title-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#facebook-description-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_opengraph-description': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#social-title-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#social-description-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	'#twitter-title-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#twitter-description-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_twitter-description': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#x-title-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#x-description-input-metabox': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	'#yoast-google-preview-title-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast-google-preview-description-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'meta-description',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	'#facebook-title-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_opengraph-title': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#facebook-description-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	'#twitter-title-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#yoast_wpseo_twitter-title': {
		'add_generate_button': false,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#twitter-description-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	// Rank Math elements.
	'.rank-math-focus-keyword > div > input': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'focus-keyphrase',
		'key_by_key': false,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-editor-title': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-editor-description': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'meta-description',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-facebook-title': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'facebook-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-facebook-description': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'facebook-description',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-twitter-title': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'twitter-title',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},
	'#rank-math-twitter-description': {
		'add_generate_button': true,
		'metadata_source': 'rank-math',
		'metadata_identifier': 'twitter-description',
		'key_by_key': true,
		'processing-context': 'metadata',
		'use_exec_command_workaround': true
	},

	// All AIOSEO entries derive from one field registry so visual and proxy behavior cannot drift.
	...ai4seo_get_aioseo_generation_input_details(),

	// SEOPress entries derive from one registry so its lazy React tabs and classic metabox stay aligned.
	...ai4seo_get_seopress_generation_input_details(),

	// Slim SEO entries derive from one registry so generation and lazy remount handling cannot drift.
	...ai4seo_get_scoped_third_party_generation_input_details(
		ai4seo_slim_seo_editor_root_selector,
		'slim-seo',
		ai4seo_slim_seo_generation_field_details
	),

	// Squirrly entries derive from one registry so every AJAX-refreshed tab stays aligned.
	...ai4seo_get_scoped_third_party_generation_input_details(
		ai4seo_squirrly_editor_root_selector,
		'squirrly',
		ai4seo_squirrly_generation_field_details
	),

	// The SEO Framework entries use the same scoped-field structure as the other simple editors.
	...ai4seo_get_scoped_third_party_generation_input_details(
		ai4seo_the_seo_framework_editor_root_selector,
		'the-seo-framework',
		ai4seo_the_seo_framework_generation_field_details
	),

	// SEO SIMPLE PACK elements.
	'#ssp_metabox #ssp_meta_title': {
		'add_generate_button': true,
		'metadata_source': 'seo-simple-pack',
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata'
	},
	'#ssp_metabox textarea[name="ssp_meta_description"]': {
		'add_generate_button': true,
		'metadata_source': 'seo-simple-pack',
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#ssp_metabox #ssp_meta_keyword': {
		'add_generate_button': true,
		'metadata_source': 'seo-simple-pack',
		'metadata_identifier': 'keywords',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	// Be-Builder elements.
	'.preview-mfn-meta-seo-titleinput': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata'
	},
	'.preview-mfn-meta-seo-descriptioninput': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'input[name=mfn-meta-seo-title]': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata'
	},
	'input[name=mfn-meta-seo-description]': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	'#social-title-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-title',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#social-description-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'facebook-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	'#x-title-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-title',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
	'#x-description-input-modal > div > div > div': {
		'add_generate_button': true,
		'metadata_source': 'yoast',
		'metadata_identifier': 'twitter-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},

	// Attachments.
	'.post-type-attachment #title[name=post_title]': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'title',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.post-type-attachment #attachment_alt[name=_wp_attachment_image_alt]': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.post-type-attachment #attachment_caption[name=excerpt]': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'caption',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.post-type-attachment #attachment_content[name=content]': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'description',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},

	// media library.
	'.attachment-info .setting #attachment-details-two-column-title': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'title',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-info .setting #attachment-details-two-column-alt-text': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-info .setting #attachment-details-two-column-caption': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'caption',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-info .setting #attachment-details-two-column-description': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'description',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},

	// media upload side bar.
	'.attachment-details .setting #attachment-details-title': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'title',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-details .setting #attachment-details-alt-text': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},

	// Elementor image details modal.
	'#image-details-alt-text': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-details .setting #attachment-details-caption': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'caption',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},
	'.attachment-details .setting #attachment-details-description': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'description',
		'key_by_key': false,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},

	// gutenberg side bar.
	'.block-editor-block-inspector .components-tools-panel-item .components-base-control .components-textarea-control__input': {
		'add_generate_button': true,
		'attachment_attributes_identifier': 'alt-text',
		'key_by_key': false,
		'use_exec_command_workaround': true,
		'css-class': 'ai4seo-attachment-generate-attributes-button',
		'processing-context': 'attachment-attributes'
	},

	// SEO KEY Plugin.
	'#tab-seokey-metas #meta-tags-inputs #metatitle': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-title',
		'key_by_key': true,
		'processing-context': 'metadata'
	},
	'#tab-seokey-metas #meta-tags-inputs #meta-tags-inputs-textarea': {
		'add_generate_button': true,
		'metadata_identifier': 'meta-description',
		'key_by_key': false,
		'processing-context': 'metadata'
	},
};

const ai4seo_content_containers = [
	'.editor-post-title', '.wp-block-post-title', '.editor-post-excerpt__textarea textarea', '.wp-block-paragraph', '.wp-block-post-content', // Gutenberg.
	'#titlediv > #titlewrap > input', '.wp-editor-area', '.woocommerce-Tabs-panel', // WooCommerce products.
	'header h1.title', '.item-preview-content', '.elementor-widget', // Elementor.
	'.mce-content-body', '.mcb-wrap-inner', '.the_content_wrapper', // Be-Builder.
];

const ai4seo_generate_all_button_selectors = {
	'metadata': [
		'#ai4seo-generate-all-metadata-button-hook', // Our Metadata Editor.
		'.ai4seo-aioseo-generate-all-button-hook', // All in One SEO.
		'.ai4seo-seopress-generate-all-button-hook', // SEOPress.
		'#seopress_cpt #seopress-tabs', // SEOPress classic fallback.
		'#slim-seo .inside', // Slim SEO.
		'#ssp_metabox .ssp_metabox.-post', // SEO SIMPLE PACK.
		'.ai4seo-squirrly-generate-all-button-hook', // Squirrly SEO.
		ai4seo_the_seo_framework_editor_root_selector + ' .inside', // The SEO Framework.
		'#wpseo-metabox-root', // Yoast SEO.
		'#meta-tags-inputs', // BeBuilder
		// '.rank-math-tab-content-general', // Rank Math, bugged as we cannot detect/change all hidden fields here.
	],
	'attachment-attributes': [
		'#ai4seo-generate-all-attachment-attributes-button-hook', // Our Attachment Attributes Editor.
		'.media-frame-content .attachment-info .details', // Media library modal.
		'.post-type-attachment .wp_attachment_details.edit-form-section' // Attachment edit page.
	],
}

// Keep plugin-branded API notices synchronized with the PHP-localized plugin name.
const ai4seo_server_connection_error_message = wp.i18n.sprintf(
	wp.i18n.__( 'Could not initialize connection to %s server. Please contact the plugin developer.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_credentials_error_message = wp.i18n.sprintf(
	wp.i18n.__( 'Could not initialize %s server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_missing_success_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call did not return a success value. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_invalid_success_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call returned an invalid success value. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_missing_data_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call did not return data. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_empty_data_array_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call returned an empty data array. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_missing_consumed_credits_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call did not return consumed Credits. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_missing_new_credits_balance_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call did not return new Credits balance. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_server_call_invalid_data_array_error_message = wp.i18n.sprintf(
	wp.i18n.__( '%s server call returned an invalid data array. Please try again.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_insufficient_credits_error_message = wp.i18n.sprintf(
	wp.i18n.__( 'Your %s account does not contain sufficient Credits. Please add more Credits to your account.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);
const ai4seo_insufficient_credits_with_link_error_message = ai4seo_insufficient_credits_error_message + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__( 'Click here to add Credits', 'ai-for-seo' ) + '</a>';
const ai4seo_client_blocked_error_message = wp.i18n.sprintf(
	wp.i18n.__( 'Your %s account has been blocked from using this service due to suspicious activity. Please contact the plugin developer if you believe this is an error.', 'ai-for-seo' ),
	ai4seo_get_plugin_name()
);

const ai4seo_error_codes_and_messages = {
	'12127323': ai4seo_server_connection_error_message,
	'13127323': ai4seo_server_credentials_error_message,
	'21127323': wp.i18n.__( 'Could not read post content.', 'ai-for-seo' ),
	'22127323': wp.i18n.__( 'Posts content is empty.', 'ai-for-seo' ),
	'351229323': wp.i18n.__( 'Posts content is empty.', 'ai-for-seo' ),
	'491320823': wp.i18n.__( 'Posts content is too short.', 'ai-for-seo' ),
	'28127323': wp.i18n.__( 'Could not execute API call. Please check your browser console for more details.', 'ai-for-seo' ),
	'31127323': ai4seo_server_call_missing_success_error_message,
	'47127323': ai4seo_server_call_invalid_success_error_message,
	'48127323': ai4seo_server_call_missing_data_error_message,
	'49127323': ai4seo_server_call_empty_data_array_error_message,
	'50127323': ai4seo_server_call_missing_consumed_credits_error_message,
	'51127323': ai4seo_server_call_missing_new_credits_balance_error_message,
	'52127323': ai4seo_server_call_invalid_data_array_error_message,
	'291215624': ai4seo_server_call_invalid_data_array_error_message,
	'301215624': ai4seo_server_call_invalid_data_array_error_message,
	'311215624': ai4seo_server_call_invalid_data_array_error_message,
	'2113111223': ai4seo_server_credentials_error_message,
	'251118426': ai4seo_server_credentials_error_message,
	'581715426': ai4seo_server_credentials_error_message,
	'1115424': ai4seo_insufficient_credits_error_message,
	'1215424': ai4seo_insufficient_credits_error_message,
	'3619101024': wp.i18n.__( 'This content violates our usage policies and cannot be processed. Please modify your content and try again.', 'ai-for-seo' ),
};

const ai4seo_robhub_api_response_error_codes = [32127323, 18197323, 311823824];

const ai4seo_robhub_api_response_error_codes_and_messages = {
	'client not found / access denied': ai4seo_server_credentials_error_message,
	'invalid credentials: invalid api username': ai4seo_server_credentials_error_message,
	'invalid credentials: invalid api password': ai4seo_server_credentials_error_message,
	'invalid credentials: access denied': ai4seo_server_credentials_error_message,
	'client secret is invalid. Api-Error-Code: 351816823': ai4seo_server_credentials_error_message,
	'client is not active. Api-Error-Code: 361816823': ai4seo_server_credentials_error_message,
	'could not create client. Api-Error-Code: 571931823': ai4seo_server_credentials_error_message,
	': client not found. Api-Error-Code: 581931823': ai4seo_server_credentials_error_message,
	'client has insufficient credits': ai4seo_insufficient_credits_with_link_error_message,
	'No Credits left. Please get more credits.': ai4seo_insufficient_credits_with_link_error_message,
	'Too Many Requests. Api-Error-Code: 381816823': wp.i18n.__( 'Maximum number of requests reached. Please try again later.', 'ai-for-seo' ),
	'Too Many Requests. Api-Error-Code: 591931823': wp.i18n.__( 'Maximum number of requests reached. Please try again later.', 'ai-for-seo' ),
	'input parameter is too short': wp.i18n.__( 'The provided content length insufficient for optimal SEO performance.', 'ai-for-seo' ),
	'We detected inappropriate content': wp.i18n.__( 'The provided post or media file contains inappropriate content. Please adjust your content and try again.', 'ai-for-seo' ),
	'client blocked from using this service': ai4seo_client_blocked_error_message,
};

// Metadata integrations use these parent-frame triggers to discover late-loaded SEO fields.
const ai4seo_external_metadata_integration_click_selectors = [
	// yoast.
	'#yoast-google-preview-modal-open-button',
	'#yoast-facebook-preview-modal-open-button',
	'#yoast-twitter-preview-modal-open-button',
	'#wpseo-meta-tab-content',
	'#wpseo-meta-tab-social',
	'#yoast-search-appearance-modal-open-button',
	'#yoast-social-appearance-modal-open-button',

	// elementor.
	'#elementor-panel #page-options-tab',
	'#elementor-panel #elementor-panel-header-menu-button',
	'#elementor-panel button[value=document-settings]',
	'#elementor-panel button.elementor-tab-control-settings',
	'#elementor-panel button.elementor-tab-control-yoast-seo-tab',
	'#elementor-panel .elementor-component-tab[data-tab="yoast-seo-tab"]',
	'#elementor-panel button.MuiButtonBase-root',

	// rank math.
	'.rank-math-toolbar-score',
	'.rank-math-edit-snippet',
	'.rank-math-editor .serp-preview-wrapper',
	'.rank-math-tabs button',
	'.rank-math-editor-social button',
	'.rank-math-editor-social .components-form-toggle',

	// all in one seo.
	'.aioseo-post-settings .var-tab',
];

// Media integrations use these parent-frame triggers to discover late-loaded attachment fields.
const ai4seo_external_media_integration_click_selectors = [
	// woocommerce.
	'#postimagediv',
	'#set-post-thumbnail',
	'p.add_product_images > a',

	// media.
	'.block-editor-media-replace-flow__media-upload-menu',
	'.attachment-preview > .thumbnail',
	'.media-modal .edit-media-header button.left.dashicons',
	'.media-modal .edit-media-header button.right.dashicons',

	// Elementor / TinyMCE image details modal.
	'.mce-inline-toolbar-grp .mce-btn:has(.mce-i-dashicon.dashicons-edit)',
];

const ai4seo_js_file_id = 'ai-for-seo-scripts-js';

const ai4seo_css_file_id = 'ai-for-seo-styles-css';

const ai4seo_supported_mime_types = ['image/jpeg', 'JPEG', 'image/jpg', 'JPG', 'image/png', 'PNG', 'image/gif', 'GIF', 'image/webp', 'WEBP', 'image/avif', 'AVIF'];

const ai4seo_attachment_mime_type_selectors = ['.media-frame-content .attachment-info .details .file-type', '#minor-publishing #misc-publishing-actions .misc-pub-filetype'];

// ___________________________________________________________________________________________ \\
// === INIT ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (typeof jQuery === 'function') {
	// Initialize the permitted request contexts once WordPress and its editor markup are ready.
	jQuery( document ).ready(
		function () {
			// A click-loaded top-frame bundle initializes only its explicitly requested integration groups.
			if (ai4seo_is_top_frame_integration_bootstrap) {
				ai4seo_init_load_scripts_click_listeners();
				ai4seo_schedule_pending_top_frame_integration_groups();
				return;
			}

			// Only internal plugin pages can consume the request-scoped asset refresh parameter.
			if (ai4seo_has_asset_context( 'plugin-ui' )) {
				ai4seo_consume_asset_refresh_query_parameter();
			}

			// Consume reload-persistent editor warnings once, before dynamic element initialization begins.
			ai4seo_consume_pending_third_party_sync_warning();

			// Lifecycle-specific schedulers handle every later DOM insertion explicitly.
			ai4seo_init_html_elements( document );

			// Dashboard, notification, and Help bootstrapping belong exclusively to the internal plugin UI.
			if (ai4seo_has_asset_context( 'plugin-ui' )) {
				// Initialize dashboard auto-refresh if on dashboard page.
				ai4seo_init_dashboard_refresh();

				// Remove notification count when the dashboard was opened.
				ai4seo_remove_notification_count();

				// Initialize the Help search and navigation before resolving its location hash.
				ai4seo_init_help_page_search_field();
				ai4seo_init_help_page_navigation();
				ai4seo_activate_help_section_from_location_hash();
			}

			// WordPress editors and frontend page builders share the external metadata integration hooks.
			const is_metadata_editor_integration_context = ai4seo_has_asset_context( 'post-editor' ) || ai4seo_has_asset_context( 'frontend-metadata-editor' );

			// Elementor panel hooks only add the external metadata editor shortcut.
			if (is_metadata_editor_integration_context && ai4seo_are_external_metadata_generate_buttons_enabled()) {
				ai4seo_init_elementor_panel_content_wrapper_listener();
			}

			// Delegated parent-frame handlers do not depend on their dynamic target elements being mounted yet.
			if (ai4seo_is_external_integration_allowed( ai4seo_external_metadata_integration_group )
				|| ai4seo_is_external_integration_allowed( ai4seo_external_media_integration_group )) {
				ai4seo_init_load_scripts_click_listeners();
			}
		}
	);
} else {
	console.error( ai4seo_get_plugin_name() + ': jQuery is not defined \u2014 our scripts could not be initialized.' );
}

// =========================================================================================== \\


function ai4seo_consume_asset_refresh_query_parameter() {
	// Limit history mutations to update-refresh requests whose parameter name came from the PHP declaration.
	const asset_refresh_query_parameter = ai4seo_get_localization_parameter( 'ai4seo_asset_refresh_query_parameter' );

	if (!asset_refresh_query_parameter || !window.history || typeof window.history.replaceState !== 'function') {
		return;
	}

	// Preserve all unrelated query parameters and the location hash while removing the consumed cache variant.
	const current_url = new URL( window.location.href );

	if (!current_url.searchParams.has( asset_refresh_query_parameter )) {
		return;
	}

	current_url.searchParams.delete( asset_refresh_query_parameter );

	try {
		window.history.replaceState( window.history.state, '', current_url.toString() );
	} catch (error) {
		// URL cleanup is non-critical, so browser history restrictions must not interrupt admin initialization.
	}
}

// =========================================================================================== \\

function ai4seo_init_help_page_navigation() {
	const $help_navigation_links = ai4seo_normalize_$( '.ai4seo-help-navigation-link' );

	if (!ai4seo_exists_$( $help_navigation_links )) {
		return;
	}

	// Track anchors separately because the visible tile is the child element but aria-current belongs on the link.
	const $help_navigation_link_anchors = ai4seo_normalize_$( $help_navigation_links.closest( 'a' ) );

	// Replace any previous Help navigation binding so repeated init calls do not stack click handlers.
	$help_navigation_links.off( 'click.ai4seo-help-navigation' );
	$help_navigation_links.on(
        'click.ai4seo-help-navigation',
        function (event) {
		const $navigation_link = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $navigation_link )) {
			console.warn( ai4seo_get_plugin_name() + ': Help navigation link missing in ai4seo_init_help_page_navigation() — skipping click event.' );
			return;
		}

		const target_id = $navigation_link.attr( 'data-ai4seo-help-target' );

		if (!target_id) {
			console.warn( ai4seo_get_plugin_name() + ': Help navigation target missing in ai4seo_init_help_page_navigation() — skipping click event.' );
			return;
		}

		const $target = ai4seo_normalize_$( '#' + target_id );

		if (!ai4seo_exists_$( $target )) {
			console.warn( ai4seo_get_plugin_name() + ': Help navigation target element not found in ai4seo_init_help_page_navigation() — skipping click event.' );
			return;
		}

		// Keep Help tile clicks instant while still writing a server-readable URL for refreshes.
		if (event && window.history && window.history.replaceState) {
			event.preventDefault();
		}

		// Read the parent link once because it carries both the canonical refresh URL and ARIA state.
		const $navigation_link_anchor = ai4seo_normalize_$( $navigation_link.closest( 'a' ) );

		ai4seo_clear_faq_search_fields();

		const $help_content = ai4seo_normalize_$( '.ai4seo-help-content' );

		if (!ai4seo_exists_$( $help_content )) {
			console.warn( ai4seo_get_plugin_name() + ': Help content container missing in ai4seo_init_help_page_navigation() — cannot hide other content sections.' );
			return;
		}

		$help_content.addClass( 'ai4seo-display-none' );

		// Keep visual and assistive-technology current states aligned with the visible Help section.
		$help_navigation_links.removeClass( 'ai4seo-active-help-preview-selection' );
		$navigation_link.addClass( 'ai4seo-active-help-preview-selection' );

		if (ai4seo_exists_$( $help_navigation_link_anchors ) && ai4seo_exists_$( $navigation_link_anchor )) {
			$help_navigation_link_anchors.removeAttr( 'aria-current' );
			$navigation_link_anchor.attr( 'aria-current', 'page' );

			// Persist the selected Help section in the URL so PHP can render it directly on refresh.
			const navigation_href = $navigation_link_anchor.attr( 'href' );

			if (navigation_href && window.history && window.history.replaceState) {
				try {
					window.history.replaceState( null, '', navigation_href );
				} catch (error) {
					console.warn( ai4seo_get_plugin_name() + ': Help navigation URL could not be updated in ai4seo_init_help_page_navigation() — continuing with the visible section switch.', error );
				}
			}
		}

		$target.removeClass( 'ai4seo-display-none' );
        }
    );
}

// =========================================================================================== \\

/**
 * Bind the shared Help and FAQ accordion triggers without duplicating handlers on reinitialization.
 */
function ai4seo_init_accordion_elements() {
	// Accordion panels render together on plugin Help pages and share one global open state.
	const $accordion_triggers = ai4seo_normalize_$( '.ai4seo-accordion-trigger' );

	// Pages without accordion markup do not need an event binding.
	if (!ai4seo_exists_$( $accordion_triggers )) {
		return;
	}

	// Replace previous bindings so repeated plugin initialization keeps one action per trigger.
	$accordion_triggers
		.off( 'click.ai4seo-accordion' )
		.on( 'click.ai4seo-accordion', ai4seo_handle_accordion_trigger_click );
}

// =========================================================================================== \\

/**
 * Open the panel controlled by one accordion trigger and close every other accordion panel.
 */
function ai4seo_handle_accordion_trigger_click() {
	// Normalize the bound trigger so the handler follows the shared jQuery input contract.
	const $accordion_trigger = ai4seo_normalize_$( this );

	// A detached or invalid trigger cannot provide a reliable controlled-panel relationship.
	if (!ai4seo_exists_$( $accordion_trigger )) {
		console.warn( ai4seo_get_plugin_name() + ': Accordion trigger missing in ai4seo_init_accordion_elements() — skipping click event.' );
		return;
	}

	// Resolve the server-rendered relationship instead of relying on sibling order.
	const accordion_panel_id = String( $accordion_trigger.attr( 'aria-controls' ) || '' );

	if (accordion_panel_id === '') {
		console.warn( ai4seo_get_plugin_name() + ': Accordion panel reference missing in ai4seo_init_accordion_elements() — skipping click event.' );
		return;
	}

	// Use the shared normalization boundary so top-frame Help output remains discoverable when applicable.
	const $accordion_panel = ai4seo_normalize_$( '#' + accordion_panel_id );

	// Invalid markup must leave the current accordion state unchanged.
	if (!ai4seo_exists_$( $accordion_panel )) {
		console.warn( ai4seo_get_plugin_name() + ': Accordion panel missing in ai4seo_init_accordion_elements() — skipping click event.' );
		return;
	}

	// Live collections retain the historical page-wide single-open-panel behavior.
	const $all_accordion_triggers = ai4seo_normalize_$( '.ai4seo-accordion-trigger' );
	const $all_accordion_panels = ai4seo_normalize_$( '.ai4seo-accordion-content' );

	// Collapse the page-wide group before synchronizing the selected visual and accessible state.
	$all_accordion_triggers.attr( 'aria-expanded', 'false' );
	$all_accordion_panels.prop( 'hidden', true ).hide();
	$accordion_trigger.attr( 'aria-expanded', 'true' );
	$accordion_panel.prop( 'hidden', false ).show();
}

// =========================================================================================== \\

function ai4seo_init_help_page_search_field() {
	// Function to perform the search.
	const $help_search_inputs = ai4seo_normalize_$( '.ai4seo-help-search' );

	if (!ai4seo_exists_$( $help_search_inputs )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': No help search inputs found in ai4seo_init_help_page_search_field() — skipping initialization.');.
		return;
	}

	$help_search_inputs.off( 'keyup.ai4seo-help-search' ); // Remove any previous keyup handlers to avoid duplicates.
	$help_search_inputs.on(
        'keyup.ai4seo-help-search',
        function (event) {
		const $this_search_input = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_search_input )) {
			console.warn( ai4seo_get_plugin_name() + ': Search input missing in ai4seo_help_search_keyup_handler() — skipping keyup event.' );
			return;
		}

		// Restrict every search instance to its own help container so duplicate desktop/mobile layouts do not affect each other.
		const $help_content = ai4seo_normalize_$( $this_search_input.closest( '.ai4seo-help-content' ) );

		if (!ai4seo_exists_$( $help_content )) {
			console.warn( ai4seo_get_plugin_name() + ': Help content missing in ai4seo_help_search_keyup_handler() — cannot filter FAQ results.' );
			return;
		}

		// Resolve all dependent elements from the same container before changing any FAQ visibility.
		const $faq_section_holders = ai4seo_normalize_$( $help_content.find( '.ai4seo-faq-section-holder' ) );
		const $faq_entry_holders = ai4seo_normalize_$( $help_content.find( '.ai4seo-accordion-holder' ) );
		const $no_results_notice_holder = ai4seo_normalize_$( $help_content.find( '.ai4seo-help-faq-search-notice' ) );

		if (!ai4seo_exists_$( $faq_section_holders ) || !ai4seo_exists_$( $faq_entry_holders ) || !ai4seo_exists_$( $no_results_notice_holder )) {
			console.warn( ai4seo_get_plugin_name() + ': Help search containers missing in ai4seo_help_search_keyup_handler() — cannot filter FAQ results.' );
			return;
		}

		const code_of_key_pressed = event.keyCode || event.which;
		const search_text = $this_search_input.val().toLowerCase();

		let has_results = false;

		if (search_text.length >= 3) {
			// Display all elements if char is deleted in input.
			if (code_of_key_pressed === 8 || code_of_key_pressed === 46) {
				$faq_entry_holders.show();
				$faq_section_holders.show();
				$no_results_notice_holder.addClass( 'ai4seo-display-none' );
			}

			// Hide all faq-holders once the minimum of 3 characters have been added to the search field.
			$faq_entry_holders.hide();

			// Loop through each faq-holder to check for a match.
			$faq_entry_holders.each(
                function () {
				const $faq_entry = ai4seo_normalize_$( this );

				if (!$faq_entry) {
					console.warn( ai4seo_get_plugin_name() + ': FAQ entry missing in ai4seo_help_search_keyup_handler() — skipping entry.' );
					return;
				}

				const $accordion_headline = $faq_entry.find( '.ai4seo-accordion-headline' );
				const $accordion_content = $faq_entry.find( '.ai4seo-accordion-content' );

				// Check if the faq-entry has a headline, if not skip this entry.
				if (!ai4seo_exists_$( $accordion_headline ) || !ai4seo_exists_$( $accordion_content )) {
					console.warn( ai4seo_get_plugin_name() + ': FAQ accordion content missing in ai4seo_help_search_keyup_handler() — skipping entry.' );
					return;
				}

				const accordion_headline_text = $accordion_headline.text().toLowerCase();
				const accordion_content_text = $accordion_content.text().toLowerCase();

				// Check if the search_text is found in either the headline or the content.
				if (accordion_headline_text.includes( search_text ) || accordion_content_text.includes( search_text )) {
					// Show this faq-entry if a match was found.
					$faq_entry.show();
					has_results = true;
				}
                }
            );

			// Loop through each faq-section-holder to check if there are still faq-entries in this section.
			$faq_section_holders.each(
                function () {
				const $faq_section = ai4seo_normalize_$( this );

				if (!$faq_section) {
					console.warn( ai4seo_get_plugin_name() + ': FAQ section missing in ai4seo_help_search_keyup_handler() — skipping section.' );
					return;
				}

				const $visible_accordion_headline_childs = $faq_section.find( '.ai4seo-accordion-headline:visible' );

				if (ai4seo_exists_$( $visible_accordion_headline_childs )) {
					$faq_section.show();
				} else {
					$faq_section.hide();
				}
                }
            );

			// Toggle the no results message based on whether matches have been found.
			if (has_results) {
				$no_results_notice_holder.addClass( 'ai4seo-display-none' );
			} else {
				$no_results_notice_holder.removeClass( 'ai4seo-display-none' );
			}
		} else {
			// Show all accordion holders and hide the no results message if less than 3 characters are entered.
			$faq_entry_holders.show();
			$faq_section_holders.show();
			$no_results_notice_holder.addClass( 'ai4seo-display-none' );
		}
        }
    );
}

// =========================================================================================== \\

function ai4seo_clear_faq_search_fields() {
	const $help_search_inputs = ai4seo_normalize_$( '.ai4seo-help-search' );

	if (!ai4seo_exists_$( $help_search_inputs )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': No help search inputs found in ai4seo_clear_faq_search_fields() — cannot clear search fields.');.
	}

	$help_search_inputs.val( '' ).trigger( 'keyup' );
}

// =========================================================================================== \\

function ai4seo_activate_help_section_from_location_hash() {
	const location_hash = window.location.hash;

	if (!location_hash) {
		return;
	}

	const $help_navigation_links = ai4seo_normalize_$( '.ai4seo-help-navigation-link' );

	if (!ai4seo_exists_$( $help_navigation_links )) {
		return;
	}

	// Match both legacy hash-only links and refresh-safe Help links that include query parameters.
	const $matching_help_navigation_link = $help_navigation_links.filter(
		function () {
			const link_href = ai4seo_normalize_$( this ).closest( 'a' ).attr( 'href' );

			if (!link_href) {
				return false;
			}

			try {
				return new URL( link_href, window.location.href ).hash === location_hash;
			} catch (error) {
				return false;
			}
		}
	).first();

	if (ai4seo_exists_$( $matching_help_navigation_link )) {
		$matching_help_navigation_link.trigger( 'click' );
	}
}

// =========================================================================================== \\

function ai4seo_should_initialize_wordpress_media_lifecycle() {
	// Media hooks must honor both the feature toggle and PHP's request-scoped screen classification.
	return ai4seo_are_external_media_generate_buttons_enabled() && ai4seo_has_asset_context( 'external-media' );
}

// =========================================================================================== \\

function ai4seo_init_wordpress_media_lifecycle() {
	// Keep MediaFrame hooks out of frontend builders and admin screens that did not request external-media assets.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// Install every idempotent event source before scanning markup that may already be visible.
	ai4seo_init_wordpress_media_modal_open_hook();
	ai4seo_init_wordpress_media_attachment_add_event();
	ai4seo_init_wordpress_media_lifecycle_events();
	ai4seo_bind_existing_wordpress_media_frame_events();

	// Cover a modal that was already opened before this bundle or a click-loaded top-frame copy initialized.
	ai4seo_schedule_wordpress_media_elements_init();
}

// =========================================================================================== \\

function ai4seo_bind_existing_wordpress_media_frame_events() {
	// Existing frames are optional because some external-media screens load only attachment markup.
	if (typeof window.wp === 'undefined' || typeof window.wp.media === 'undefined') {
		return;
	}

	// Dedupe the global frame and named frame registry before binding their lifecycle events.
	const existing_media_frames = new Set();

	if (window.wp.media.frame) {
		existing_media_frames.add( window.wp.media.frame );
	}

	// Include plugin-created frames exposed through WordPress' shared frame collection.
	if (window.wp.media.frames && typeof window.wp.media.frames === 'object') {
		Object.keys( window.wp.media.frames ).forEach(
			function (frame_identifier) {
				const media_frame = window.wp.media.frames[frame_identifier];

				if (media_frame) {
					existing_media_frames.add( media_frame );
				}
			}
		);
	}

	// Each frame tracks its own idempotent binding state in the shared WeakSet.
	existing_media_frames.forEach(
		function (media_frame) {
			ai4seo_bind_wordpress_media_frame_events( media_frame );
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_wordpress_media_modal_open_hook() {
	// WordPress media is not guaranteed to be present on every external-media screen.
	if (
		typeof window.wp === 'undefined' ||
		typeof window.wp.media === 'undefined' ||
		typeof window.wp.media.view === 'undefined' ||
		typeof window.wp.media.view.Modal === 'undefined' ||
		typeof window.wp.media.view.Modal.prototype.open !== 'function'
	) {
		return;
	}

	const modal_prototype = window.wp.media.view.Modal.prototype;

	// Store the marker on the patched prototype so repeated integration entrypoints remain no-ops.
	if (modal_prototype.ai4seo_wordpress_media_open_overridden === true) {
		return;
	}

	// Preserve the original open implementation and its return value for all WordPress callers.
	const native_open = modal_prototype.open;

	modal_prototype.open = function () {
		// Let WordPress attach, display, and populate the modal before its root is scheduled.
		const native_open_result = native_open.apply( this, arguments );

		ai4seo_handle_wordpress_media_modal_open( this );

		return native_open_result;
	};

	modal_prototype.ai4seo_wordpress_media_open_overridden = true;
}

// =========================================================================================== \\

function ai4seo_handle_wordpress_media_modal_open(media_modal) {
	// A retained prototype wrapper must become a no-op if this request is not allowed to run the integration.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// Bind the owning frame before treating the modal opening as a standard lifecycle signal.
	const media_frame = media_modal && media_modal.controller ? media_modal.controller : null;

	ai4seo_bind_wordpress_media_frame_events( media_frame );
	ai4seo_record_wordpress_media_standard_signal();
	ai4seo_schedule_wordpress_media_elements_init( media_modal );
}

// =========================================================================================== \\

function ai4seo_bind_wordpress_media_frame_events(media_frame) {
	// Invalid, eventless, and previously initialized frames require no lifecycle work.
	if (
		!media_frame ||
		(typeof media_frame !== 'object' && typeof media_frame !== 'function') ||
		typeof media_frame.on !== 'function' ||
		ai4seo_wordpress_media_initialized_frames.has( media_frame )
	) {
		return;
	}

	const handle_media_frame_change = function () {
		ai4seo_record_wordpress_media_standard_signal();
		ai4seo_schedule_wordpress_media_elements_init( media_frame );
	};

	const handle_media_frame_close = function () {
		ai4seo_remove_wordpress_media_pending_init_root( media_frame );
	};

	// WordPress fires these after opening, content replacement, selection changes, and Grid navigation.
	media_frame.on( 'open content:render content:activate selection:toggle refresh', handle_media_frame_change );
	media_frame.on( 'close', handle_media_frame_close );

	// Mark the frame only after both handler sets have been registered successfully.
	ai4seo_wordpress_media_initialized_frames.add( media_frame );
}

// =========================================================================================== \\

function ai4seo_init_wordpress_media_attachment_add_event() {
	// Attachment collection events follow the same request-level permission as modal events.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// WordPress media is not loaded on every external-media screen, so this hook must stay optional.
	if (
		typeof window.wp === 'undefined' ||
		typeof window.wp.media === 'undefined' ||
		typeof window.wp.media.model === 'undefined' ||
		typeof window.wp.media.model.Attachments === 'undefined' ||
		typeof window.wp.media.model.Attachments.all === 'undefined'
	) {
		return;
	}

	// The global attachment collection needs only one add listener per window.
	if (ai4seo_wordpress_media_attachment_add_listener_initialized) {
		return;
	}

	// Rebind by callback identity to avoid retaining a stale listener after repeated initialization.
	const media_attachments = window.wp.media.model.Attachments.all;

	media_attachments.off( 'add', ai4seo_handle_wordpress_media_attachment_add );
	media_attachments.on( 'add', ai4seo_handle_wordpress_media_attachment_add );

	// Set the state only after the collection accepted the listener.
	ai4seo_wordpress_media_attachment_add_listener_initialized = true;
}

// =========================================================================================== \\

function ai4seo_handle_wordpress_media_attachment_add(attachment) {
	// Ignore stale collection callbacks if localization no longer permits external media behavior.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// Only image attachments can receive AI for SEO generation controls in WordPress media views.
	if (!attachment || typeof attachment.get !== 'function' || attachment.get( 'type' ) !== 'image') {
		return;
	}

	// Coalesce upload-driven markup changes with any media-frame events fired in the same frame.
	ai4seo_record_wordpress_media_standard_signal();
	ai4seo_schedule_wordpress_media_elements_init();
}

// =========================================================================================== \\

function ai4seo_init_wordpress_media_lifecycle_events() {
	// Page lifecycle cleanup is registered once for the lifetime of this window.
	if (ai4seo_wordpress_media_lifecycle_events_initialized) {
		return;
	}

	// Normalize the window through the shared jQuery helper before namespaced event binding.
	const $window = ai4seo_normalize_$( window );

	if (!ai4seo_exists_$( $window )) {
		return;
	}

	// Clear asynchronous work when leaving and rescan restored bfcache markup once on return.
	$window
		.off( 'pagehide.ai4seo-wordpress-media pageshow.ai4seo-wordpress-media' )
		.on(
			'pagehide.ai4seo-wordpress-media',
			function () {
				// Prevent delayed scans from acting on a document that is being hidden or discarded.
				ai4seo_clear_wordpress_media_fallback_scan();
				ai4seo_clear_scheduled_wordpress_media_elements_init();
			}
		)
		.on(
			'pageshow.ai4seo-wordpress-media',
			function (event) {
				const original_event = event && event.originalEvent ? event.originalEvent : event;

				// Persisted pages retain handlers but need one fresh scan of their restored media views.
				if (original_event && original_event.persisted === true) {
					ai4seo_schedule_wordpress_media_elements_init();
				}
			}
		);

	// Record registration only after both namespaced lifecycle handlers are installed.
	ai4seo_wordpress_media_lifecycle_events_initialized = true;
}

// =========================================================================================== \\

function ai4seo_resolve_wordpress_media_root(source, require_visible = true) {
	// Normalize Backbone modal/frame objects and raw DOM or jQuery sources to one traversal root.
	let $source = null;

	if (source && source.modal && source.modal.$el) {
		$source = ai4seo_normalize_$( source.modal.$el );
	} else if (source && source.$el) {
		$source = ai4seo_normalize_$( source.$el );
	} else if (source) {
		$source = ai4seo_normalize_$( source );
	}

	// A source-free call intentionally delegates root discovery to the scheduler.
	if (!ai4seo_exists_$( $source )) {
		return null;
	}

	// Prefer the complete modal so all current attachment fields share one scheduled pass.
	let $media_modal = $source
		.filter( '.media-modal' )
		.add( $source.closest( '.media-modal' ) )
		.add( $source.find( '.media-modal' ) )
		.last();

	// Lifecycle scheduling processes only rendered roots; close cleanup may opt out of this filter.
	if (require_visible) {
		$media_modal = $media_modal.filter( ':visible' );
	}

	if (ai4seo_exists_$( $media_modal )) {
		return $media_modal.get( 0 );
	}

	// Fall back to the active frame content when a third-party view omits the standard modal shell.
	let $media_content = $source
		.filter( '.media-frame-content' )
		.add( $source.closest( '.media-frame-content' ) )
		.add( $source.find( '.media-frame-content' ) )
		.first();

	if (require_visible) {
		$media_content = $media_content.filter( ':visible' );
	}

	if (ai4seo_exists_$( $media_content )) {
		return $media_content.get( 0 );
	}

	// Standalone attachment and upload screens use their stable admin content container as root.
	let $media_page = $source
		.filter( '#poststuff, #wpbody-content' )
		.add( $source.closest( '#poststuff, #wpbody-content' ) )
		.first();

	if (require_visible) {
		$media_page = $media_page.filter( ':visible' );
	}

	return ai4seo_exists_$( $media_page ) ? $media_page.get( 0 ) : null;
}

// =========================================================================================== \\

function ai4seo_get_active_wordpress_media_roots() {
	// Collect all visible standard modal and standalone attachment roots without duplicates.
	const media_roots = new Set();
	const $root_candidates = ai4seo_normalize_$([
		'.media-modal:visible',
		'.media-frame-content:visible',
		'body.post-type-attachment #poststuff:visible',
		'body.media-new-php #wpbody-content:visible'
	].join( ', ' ));

	// Resolve each candidate through the same priority rules used for event-provided sources.
	$root_candidates.each(
		function () {
			const media_root = ai4seo_resolve_wordpress_media_root( this );

			if (media_root) {
				media_roots.add( media_root );
			}
		}
	);

	return [...media_roots];
}

// =========================================================================================== \\

function ai4seo_schedule_wordpress_media_elements_init(source = null) {
	// Retained event handlers must remain inert whenever this request no longer permits media controls.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// Queue a concrete root where possible and defer source-free discovery until WordPress finishes rendering.
	const media_root = ai4seo_resolve_wordpress_media_root( source );

	if (media_root) {
		ai4seo_wordpress_media_pending_init_roots.add( media_root );
	} else {
		// Resolve active roots in the next frame so Backbone can finish inserting its rendered view first.
		ai4seo_wordpress_media_init_requires_root_discovery = true;
	}

	// Coalesce all roots collected during the current frame into one scheduled flush.
	if (ai4seo_wordpress_media_init_request_id !== null) {
		return;
	}

	ai4seo_wordpress_media_init_request_id = ai4seo_schedule_next_animation_frame(
		ai4seo_flush_scheduled_wordpress_media_elements_init
	);
}

// =========================================================================================== \\

function ai4seo_flush_scheduled_wordpress_media_elements_init() {
	// Work from a snapshot so new scheduling requests created during initialization are retained.
	const media_roots = new Set( ai4seo_wordpress_media_pending_init_roots );

	// Source-free signals discover every currently visible media root only once per flush.
	if (ai4seo_wordpress_media_init_requires_root_discovery) {
		ai4seo_get_active_wordpress_media_roots().forEach(
			function (media_root) {
				media_roots.add( media_root );
			}
		);
	}

	// Reset scheduler state before initialization so a generated view can request a later pass safely.
	ai4seo_wordpress_media_pending_init_roots.clear();
	ai4seo_wordpress_media_init_requires_root_discovery = false;
	ai4seo_wordpress_media_init_request_id = null;

	// Initialize independent roots separately and skip any view detached or hidden before the frame ran.
	media_roots.forEach(
		function (media_root) {
			const $media_root = ai4seo_normalize_$( media_root );

			if (!media_root || media_root.isConnected === false || !ai4seo_exists_$( $media_root ) || !$media_root.is( ':visible' )) {
				return;
			}

			// Media lifecycle events target attachment controls only, never the full HTML dispatcher.
			ai4seo_init_generate_buttons( media_root, 'attachment-attributes' );
			ai4seo_init_generate_all_buttons( media_root, 'attachment-attributes' );
			ai4seo_init_buttons( media_root );
		}
	);
}

// =========================================================================================== \\

function ai4seo_remove_wordpress_media_pending_init_root(source) {
	// Resolve hidden modal markup during close so a previously queued visible root can be removed.
	const media_root = ai4seo_resolve_wordpress_media_root( source, false );

	if (media_root) {
		ai4seo_wordpress_media_pending_init_roots.delete( media_root );
	}

	// Cancel an otherwise empty frame request after its only media root closed.
	if (
		ai4seo_wordpress_media_pending_init_roots.size === 0 &&
		!ai4seo_wordpress_media_init_requires_root_discovery
	) {
		ai4seo_cancel_scheduled_wordpress_media_elements_init();
	}
}

// =========================================================================================== \\

function ai4seo_cancel_scheduled_wordpress_media_elements_init() {
	// Avoid touching browser scheduling APIs when no media flush is pending.
	if (ai4seo_wordpress_media_init_request_id === null) {
		return;
	}

	// Cancel with the API paired to the shared requestAnimationFrame-or-timeout scheduler.
	if (ai4seo_can_use_animation_frame_scheduler()) {
		window.cancelAnimationFrame( ai4seo_wordpress_media_init_request_id );
	} else {
		window.clearTimeout( ai4seo_wordpress_media_init_request_id );
	}

	// Release the request marker only after the browser callback has been cancelled.
	ai4seo_wordpress_media_init_request_id = null;
}

// =========================================================================================== \\

function ai4seo_clear_scheduled_wordpress_media_elements_init() {
	// Reset both pending work and deferred-discovery state during page lifecycle cleanup.
	ai4seo_cancel_scheduled_wordpress_media_elements_init();
	ai4seo_wordpress_media_pending_init_roots.clear();
	ai4seo_wordpress_media_init_requires_root_discovery = false;
}

// =========================================================================================== \\

function ai4seo_record_wordpress_media_standard_signal() {
	// Timestamp immediate interactions and invalidate any fallback associated with an older signal generation.
	ai4seo_wordpress_media_last_standard_signal_timestamp = Date.now();
	ai4seo_wordpress_media_standard_signal_generation++;
	ai4seo_clear_wordpress_media_fallback_scan();
}

// =========================================================================================== \\

function ai4seo_schedule_active_wordpress_media_roots() {
	// Reuse one discovery path for immediate, delayed, and mutation-driven third-party render signals.
	const active_media_roots = ai4seo_get_active_wordpress_media_roots();

	active_media_roots.forEach(
		function (media_root) {
			ai4seo_schedule_wordpress_media_elements_init( media_root );
		}
	);

	return active_media_roots.length > 0;
}

// =========================================================================================== \\

function ai4seo_does_mutation_batch_contain_media_root(mutations) {
	// Ignore unrelated page-builder churn while the short-lived fallback observer is active.
	return mutations.some(
		function (mutation) {
			const changed_nodes = mutation.type === 'attributes'
				? [mutation.target]
				: [...mutation.addedNodes];

			return changed_nodes.some(
				function (changed_node) {
					if (!changed_node || changed_node.nodeType !== 1) {
						return false;
					}

					return changed_node.matches( '.media-modal, .media-frame-content' )
						|| changed_node.querySelector( '.media-modal, .media-frame-content' ) !== null;
				}
			);
		}
	);
}

// =========================================================================================== \\

function ai4seo_disconnect_wordpress_media_fallback_render_observer() {
	// Disconnect both observer and deadline so later media clicks start with a clean bounded lifecycle.
	if (ai4seo_wordpress_media_fallback_render_observer) {
		ai4seo_wordpress_media_fallback_render_observer.disconnect();
		ai4seo_wordpress_media_fallback_render_observer = null;
	}

	if (ai4seo_wordpress_media_fallback_render_observer_timeout !== null) {
		window.clearTimeout( ai4seo_wordpress_media_fallback_render_observer_timeout );
		ai4seo_wordpress_media_fallback_render_observer_timeout = null;
	}
}

// =========================================================================================== \\

function ai4seo_init_wordpress_media_fallback_render_observer(signal_generation) {
	// The observer is reserved for click-triggered frames that do not expose WordPress MediaFrame events.
	if (typeof window.MutationObserver !== 'function' || !document.body) {
		return;
	}

	ai4seo_disconnect_wordpress_media_fallback_render_observer();

	ai4seo_wordpress_media_fallback_render_observer = new window.MutationObserver(
		function (mutations) {
			// Standard MediaFrame activity supersedes this compatibility path immediately.
			if (signal_generation !== ai4seo_wordpress_media_standard_signal_generation) {
				ai4seo_clear_wordpress_media_fallback_scan();
				return;
			}

			if (!ai4seo_does_mutation_batch_contain_media_root( mutations )) {
				return;
			}

			// A visible media root completes the one-shot compatibility lifecycle without further observation.
			if (ai4seo_schedule_active_wordpress_media_roots()) {
				ai4seo_clear_wordpress_media_fallback_scan();
			}
		}
	);

	// Observe only during the bounded post-click window and only react to media-root insertions or visibility changes.
	ai4seo_wordpress_media_fallback_render_observer.observe(
		document.body,
		{
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['class', 'style', 'aria-hidden'],
		}
	);

	ai4seo_wordpress_media_fallback_render_observer_timeout = window.setTimeout(
		function () {
			ai4seo_wordpress_media_fallback_render_observer_timeout = null;

			// Cover a frame that became visible without a relevant observable attribute mutation.
			if (signal_generation === ai4seo_wordpress_media_standard_signal_generation) {
				ai4seo_schedule_active_wordpress_media_roots();
			}

			ai4seo_disconnect_wordpress_media_fallback_render_observer();
		},
		2000
	);
}

// =========================================================================================== \\

function ai4seo_schedule_wordpress_media_fallback_scan() {
	// The third-party fallback follows the same strict request permission as standard WordPress events.
	if (!ai4seo_should_initialize_wordpress_media_lifecycle()) {
		return;
	}

	// A new media interaction always replaces compatibility work retained from an earlier click.
	ai4seo_clear_wordpress_media_fallback_scan();

	// Initialize already visible roots immediately instead of creating an unnecessary fallback timer.
	if (ai4seo_schedule_active_wordpress_media_roots()) {
		return;
	}

	// A standard synchronous click/open signal already owns initialization for this interaction.
	if (Date.now() - ai4seo_wordpress_media_last_standard_signal_timestamp <= 250) {
		return;
	}

	// Remember which standard-signal generation this compatibility lifecycle belongs to.
	const signal_generation = ai4seo_wordpress_media_standard_signal_generation;

	// Known Core frames will cancel this observer through their events; non-standard frames can signal through DOM rendering.
	ai4seo_init_wordpress_media_fallback_render_observer( signal_generation );

	ai4seo_wordpress_media_fallback_timer = window.setTimeout(
		function () {
			// Release the timer marker before processing so a later interaction may schedule another fallback.
			ai4seo_wordpress_media_fallback_timer = null;

			// A newer standard signal owns initialization and makes this delayed scan obsolete.
			if (signal_generation !== ai4seo_wordpress_media_standard_signal_generation) {
				return;
			}

			// Preserve the original 500ms compatibility scan while the bounded render observer covers later frames.
			if (ai4seo_schedule_active_wordpress_media_roots()) {
				ai4seo_clear_wordpress_media_fallback_scan();
			}
		},
		500
	);
}

// =========================================================================================== \\

function ai4seo_clear_wordpress_media_fallback_scan() {
	// Clear the original delayed scan independently because the render observer may outlive its 500ms deadline.
	if (ai4seo_wordpress_media_fallback_timer !== null) {
		window.clearTimeout( ai4seo_wordpress_media_fallback_timer );
		ai4seo_wordpress_media_fallback_timer = null;
	}

	// Standard signals, successful discovery, and pagehide all terminate the bounded compatibility observer.
	ai4seo_disconnect_wordpress_media_fallback_render_observer();
}

// =========================================================================================== \\

function ai4seo_init_elementor_panel_content_wrapper_listener() {
	// Elementor panel clicks only need rebinding when the external metadata shortcut can be injected.
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	const $elementor_panel_content_wrapper = ai4seo_normalize_$( '#elementor-panel-content-wrapper' );

	if (ai4seo_exists_$( $elementor_panel_content_wrapper )) {
		// Workaround to display buttons within elementor-navigation.
		$elementor_panel_content_wrapper.click(
            function () {
			setTimeout(
                function () {
				ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation();
                },
                200
            );
            }
        );
	}
}

// =========================================================================================== \\

/**
 * Helps to load our main scripts only when needed (on user interaction) on various third party editors, typically loaded in iframes.
 */
function ai4seo_init_load_scripts_click_listeners() {
	const parent_document = ai4seo_get_same_origin_frame_document( window.parent, 'parent' );

	if (!parent_document) {
		return;
	}

	const $parent_document = ai4seo_normalize_$( parent_document );

	if (!ai4seo_exists_$( $parent_document )) {
		return;
	}

	ai4seo_init_external_integration_click_listener(
		$parent_document,
		ai4seo_external_metadata_integration_group,
		ai4seo_external_metadata_integration_click_selectors,
		'click.ai4seo-init-external-metadata-scripts'
	);

	ai4seo_init_external_integration_click_listener(
		$parent_document,
		ai4seo_external_media_integration_group,
		ai4seo_external_media_integration_click_selectors,
		'click.ai4seo-init-external-media-scripts'
	);
}

// =========================================================================================== \\

function ai4seo_init_external_integration_click_listener($parent_document, integration_group, click_selectors, event_name) {
	// A stable namespace makes repeated initialization idempotent without waiting for target elements.
	$parent_document.off( event_name );

	if (!ai4seo_is_external_integration_allowed( integration_group ) || !Array.isArray( click_selectors ) || click_selectors.length <= 0) {
		return;
	}

	$parent_document.on(
		event_name,
		click_selectors.join( ', ' ),
		function () {
			ai4seo_bootstrap_top_frame_integration( integration_group );
		}
	);
}

// =========================================================================================== \\

function ai4seo_get_same_origin_frame_document(target_window, frame_name = 'target') {
	// Accessing document is also the same-origin capability check required before frame coordination.
	if (!target_window) {
		return null;
	}

	try {
		const target_document = target_window.document;

		if (!target_document || !target_document.documentElement) {
			return null;
		}

		return target_document;
	} catch (error) {
		console.warn( ai4seo_get_plugin_name() + ': Cannot access the ' + frame_name + ' frame document \u2014 external integration bootstrap skipped.', error );
		return null;
	}
}

// =========================================================================================== \\

/**
 * Return the parent Document only when it is accessible and differs from the current Document.
 *
 * This narrower helper intentionally stays silent because parent lookup is an optional selector fallback.
 *
 * @return {Document|null}
 */
function ai4seo_get_distinct_parent_document() {
	// Cross-origin frames can expose window.parent while denying access to its Document.
	try {
		return window.parent && window.parent.document !== document
			? window.parent.document
			: null;
	} catch (error) {
		return null;
	}
}

// =========================================================================================== \\

function ai4seo_is_external_integration_allowed(integration_group, localization = null) {
	// Resolve the current frame's localization unless a candidate top-frame configuration was supplied.
	if (localization === null) {
		localization = ai4seo_get_localization_object();
	}

	if (!localization || typeof localization !== 'object' || Array.isArray( localization )) {
		return false;
	}

	const asset_contexts = localization.ai4seo_asset_contexts;

	if (!Array.isArray( asset_contexts )) {
		return false;
	}

	if (integration_group === ai4seo_external_metadata_integration_group) {
		return ai4seo_is_truthy_value( localization.ai4seo_enable_external_metadata_generate_buttons )
			&& (asset_contexts.includes( 'post-editor' ) || asset_contexts.includes( 'frontend-metadata-editor' ));
	}

	if (integration_group === ai4seo_external_media_integration_group) {
		return ai4seo_is_truthy_value( localization.ai4seo_enable_external_media_generate_buttons )
			&& (
				asset_contexts.includes( 'external-media' )
				|| asset_contexts.includes( 'post-editor' )
				|| asset_contexts.includes( 'frontend-metadata-editor' )
			);
	}

	return false;
}

// =========================================================================================== \\

function ai4seo_get_localized_primary_asset_url(localization, url_parameter_name) {
	// Require both URL and version so dynamically inserted assets match WordPress' registered variant.
	if (!localization || typeof localization !== 'object') {
		return '';
	}

	const asset_url = localization[url_parameter_name];
	const asset_version = localization.ai4seo_admin_scripts_version_number;

	if (typeof asset_url !== 'string' || asset_url.trim() === ''
		|| typeof asset_version === 'undefined' || asset_version === null || asset_version === '') {
		return '';
	}

	const version_separator = asset_url.includes( '?' ) ? '&' : '?';

	return asset_url + version_separator + 'ver=' + encodeURIComponent( asset_version );
}

// =========================================================================================== \\

function ai4seo_copy_localization_to_top_frame(top_window, source_localization) {
	// A top-frame-owned localization object takes precedence over configuration copied from an iframe.
	if (!top_window || !source_localization || typeof source_localization !== 'object') {
		return null;
	}

	if (typeof top_window.ai4seo_localization !== 'undefined') {
		return top_window.ai4seo_localization;
	}

	let copied_localization;

	try {
		// Create the object in the target realm and avoid retaining mutable references to the source frame.
		copied_localization = top_window.JSON.parse( JSON.stringify( source_localization ) );
	} catch (error) {
		console.error( ai4seo_get_plugin_name() + ': Could not copy localization into the top frame.', error );
		return null;
	}

	top_window.ai4seo_localization = copied_localization;

	return top_window.ai4seo_localization;
}

// =========================================================================================== \\

function ai4seo_set_top_frame_integration_bootstrap_state(top_window, script_element, stylesheet_element, localization, owns_localization) {
	if (!top_window || !script_element) {
		return false;
	}

	top_window[ai4seo_top_frame_integration_bootstrap_state_key] = {
		script_element: script_element,
		stylesheet_element: stylesheet_element || null,
		localization: localization || null,
		owns_localization: owns_localization === true,
	};

	return true;
}

// =========================================================================================== \\

function ai4seo_finalize_top_frame_integration_bootstrap(top_window, script_element) {
	if (!top_window) {
		return;
	}

	const bootstrap_state = top_window[ai4seo_top_frame_integration_bootstrap_state_key];

	if (!bootstrap_state || bootstrap_state.script_element !== script_element) {
		return;
	}

	delete top_window[ai4seo_top_frame_integration_bootstrap_state_key];
}

// =========================================================================================== \\

function ai4seo_rollback_top_frame_integration_bootstrap(top_window, script_element) {
	if (!top_window) {
		return;
	}

	const bootstrap_state = top_window[ai4seo_top_frame_integration_bootstrap_state_key];

	// A stale error callback may only remove its own failed script, never a newer bootstrap attempt.
	if (!bootstrap_state || bootstrap_state.script_element !== script_element) {
		const $stale_script = ai4seo_normalize_$( script_element );

		if (ai4seo_exists_$( $stale_script )
			&& $stale_script.attr( ai4seo_top_frame_integration_bootstrap_attribute ) === 'true') {
			$stale_script.remove();
		}

		return;
	}

	ai4seo_clear_pending_top_frame_integration_groups( top_window );

	const $script = ai4seo_normalize_$( bootstrap_state.script_element );
	const $stylesheet = ai4seo_normalize_$( bootstrap_state.stylesheet_element );

	if (ai4seo_exists_$( $script )) {
		$script.remove();
	}

	if (ai4seo_exists_$( $stylesheet )) {
		$stylesheet.remove();
	}

	if (bootstrap_state.owns_localization
		&& top_window.ai4seo_localization === bootstrap_state.localization) {
		delete top_window.ai4seo_localization;
	}

	delete top_window[ai4seo_top_frame_integration_bootstrap_state_key];
}

// =========================================================================================== \\

function ai4seo_queue_top_frame_integration_group(top_window, integration_group) {
	// Store requests on the top window so they survive until the dynamically inserted bundle executes.
	let pending_integration_groups = top_window[ai4seo_pending_top_frame_integration_groups_key];

	if (!Array.isArray( pending_integration_groups )) {
		pending_integration_groups = [];
		top_window[ai4seo_pending_top_frame_integration_groups_key] = pending_integration_groups;
	}

	if (!pending_integration_groups.includes( integration_group )) {
		pending_integration_groups.push( integration_group );
	}
}

// =========================================================================================== \\

function ai4seo_clear_pending_top_frame_integration_groups(top_window) {
	// Replace rather than mutate the shared array so stale script callbacks cannot retain queued entries.
	if (top_window) {
		top_window[ai4seo_pending_top_frame_integration_groups_key] = [];
	}
}

// =========================================================================================== \\

function ai4seo_try_schedule_top_frame_integration_group(top_window, integration_group) {
	// An exported scheduler proves the top-frame bundle has executed and can handle the request directly.
	if (top_window && typeof top_window.ai4seo_schedule_external_integration_elements_init === 'function') {
		top_window.ai4seo_schedule_external_integration_elements_init( integration_group );
		return true;
	}

	return false;
}

// =========================================================================================== \\

function ai4seo_handle_top_frame_bundle_script(top_window, script, integration_group) {
	const $script = ai4seo_normalize_$( script );

	if (!ai4seo_exists_$( $script )) {
		return;
	}

	const is_click_bootstrap_script = $script.attr( ai4seo_top_frame_integration_bootstrap_attribute ) === 'true';
	const event_namespace = '.ai4seo-top-frame-' + integration_group;

	if (is_click_bootstrap_script) {
		ai4seo_queue_top_frame_integration_group( top_window, integration_group );
	}

	$script.off( 'load' + event_namespace + ' error' + event_namespace );
	$script.one(
		'load' + event_namespace,
		function () {
			if (is_click_bootstrap_script) {
				if (typeof top_window.ai4seo_schedule_pending_top_frame_integration_groups === 'function') {
					ai4seo_finalize_top_frame_integration_bootstrap( top_window, script );
					top_window.ai4seo_schedule_pending_top_frame_integration_groups();
					return;
				}

				if (ai4seo_try_schedule_top_frame_integration_group( top_window, integration_group )) {
					ai4seo_finalize_top_frame_integration_bootstrap( top_window, script );
					return;
				}

				// A downloaded script that did not expose its bootstrap entrypoints is not a successful load.
				ai4seo_rollback_top_frame_integration_bootstrap( top_window, script );
				console.error( ai4seo_get_plugin_name() + ': The primary bundle did not initialize in the top frame.' );
				return;
			}

			ai4seo_try_schedule_top_frame_integration_group( top_window, integration_group );
		}
	);
	$script.one(
		'error' + event_namespace,
		function () {
			if (is_click_bootstrap_script) {
				ai4seo_rollback_top_frame_integration_bootstrap( top_window, script );
			}

			console.error( ai4seo_get_plugin_name() + ': Could not load the primary bundle in the top frame.' );
		}
	);

	// Close the small race between finding an existing script and registering its load handler.
	if (ai4seo_try_schedule_top_frame_integration_group( top_window, integration_group )) {
		$script.off( 'load' + event_namespace + ' error' + event_namespace );

		if (is_click_bootstrap_script && typeof top_window.ai4seo_schedule_pending_top_frame_integration_groups === 'function') {
			ai4seo_finalize_top_frame_integration_bootstrap( top_window, script );
			top_window.ai4seo_schedule_pending_top_frame_integration_groups();
		}
	}
}

// =========================================================================================== \\

function ai4seo_get_or_append_stylesheet_to_document(target_document, url, style_id = '') {
	// Reuse an existing stylesheet by ID and otherwise append one link to the requested document head.
	if (!target_document || !url) {
		return null;
	}

	const existing_stylesheet = style_id ? target_document.getElementById( style_id ) : null;

	if (existing_stylesheet) {
		return {
			element: existing_stylesheet,
			was_appended: false,
		};
	}

	const link = target_document.createElement( 'link' );
	const $link = ai4seo_normalize_$( link );
	const $target_head = ai4seo_normalize_$( target_document.head, target_document );

	if (!ai4seo_exists_$( $link ) || !ai4seo_exists_$( $target_head )) {
		return null;
	}

	$link.attr( 'type', 'text/css' );
	$link.attr( 'rel', 'stylesheet' );
	$link.attr( 'href', url );
	$link.attr( 'media', 'all' );

	if (style_id) {
		$link.attr( 'id', style_id );
	}

	try {
		$target_head.append( $link );
	} catch (error) {
		return null;
	}

	return {
		element: link,
		was_appended: true,
	};
}

// =========================================================================================== \\

function ai4seo_bootstrap_top_frame_integration(integration_group) {
	// Reject the click before touching frame state unless the source request explicitly permits the group.
	const source_localization = ai4seo_get_localization_object();

	if (!ai4seo_is_external_integration_allowed( integration_group, source_localization )) {
		return;
	}

	const parent_document = ai4seo_get_same_origin_frame_document( window.parent, 'parent' );
	const top_document = ai4seo_get_same_origin_frame_document( window.top, 'top' );

	if (!parent_document || !top_document) {
		return;
	}

	const top_window = window.top;

	// A top-level document already has all dependencies and only needs the requested integration pass.
	if (top_window === window) {
		ai4seo_schedule_external_integration_elements_init( integration_group );
		return;
	}

	if (typeof top_window.jQuery !== 'function'
		|| typeof top_window.wp !== 'object'
		|| typeof top_window.wp.i18n !== 'object'
		|| typeof top_window.wp.i18n.__ !== 'function') {
		return;
	}

	const top_frame_had_localization = typeof top_window.ai4seo_localization !== 'undefined';
	let top_localization = top_frame_had_localization
		? top_window.ai4seo_localization
		: source_localization;

	// Existing top-frame localization remains authoritative and must independently allow this group.
	if (!ai4seo_is_external_integration_allowed( integration_group, top_localization )) {
		return;
	}

	// An already executed bundle still needs target-realm localization before its scheduler can validate the group.
	if (ai4seo_try_schedule_top_frame_integration_group( top_window, integration_group )) {
		return;
	}

	const existing_script = top_document.getElementById( ai4seo_js_file_id );

	if (existing_script) {
		if (!top_frame_had_localization) {
			top_localization = ai4seo_copy_localization_to_top_frame( top_window, source_localization );

			if (!ai4seo_is_external_integration_allowed( integration_group, top_localization )) {
				return;
			}
		}

		ai4seo_handle_top_frame_bundle_script( top_window, existing_script, integration_group );
		return;
	}

	const script_url = ai4seo_get_localized_primary_asset_url( top_localization, 'ai4seo_admin_script_url' );
	const style_url = ai4seo_get_localized_primary_asset_url( top_localization, 'ai4seo_admin_style_url' );

	if (!script_url || !style_url) {
		return;
	}

	const script = top_document.createElement( 'script' );
	const $script = ai4seo_normalize_$( script );
	const $top_head = ai4seo_normalize_$( top_document.head, top_document );

	if (!ai4seo_exists_$( $script ) || !ai4seo_exists_$( $top_head )) {
		return;
	}

	const stylesheet_result = ai4seo_get_or_append_stylesheet_to_document( top_document, style_url, ai4seo_css_file_id );

	if (!stylesheet_result) {
		return;
	}

	if (!top_frame_had_localization) {
		top_localization = ai4seo_copy_localization_to_top_frame( top_window, source_localization );
	}

	if (!ai4seo_is_external_integration_allowed( integration_group, top_localization )) {
		if (stylesheet_result.was_appended) {
			ai4seo_normalize_$( stylesheet_result.element ).remove();
		}

		if (!top_frame_had_localization && top_window.ai4seo_localization === top_localization) {
			delete top_window.ai4seo_localization;
		}

		return;
	}

	$script.attr( 'type', 'text/javascript' );
	$script.attr( 'src', script_url );
	$script.attr( 'id', ai4seo_js_file_id );
	$script.attr( ai4seo_top_frame_integration_bootstrap_attribute, 'true' );

	const bootstrap_state_created = ai4seo_set_top_frame_integration_bootstrap_state(
		top_window,
		script,
		stylesheet_result.was_appended ? stylesheet_result.element : null,
		top_localization,
		!top_frame_had_localization
	);

	if (!bootstrap_state_created) {
		if (stylesheet_result.was_appended) {
			ai4seo_normalize_$( stylesheet_result.element ).remove();
		}

		if (!top_frame_had_localization && top_window.ai4seo_localization === top_localization) {
			delete top_window.ai4seo_localization;
		}

		return;
	}

	ai4seo_handle_top_frame_bundle_script( top_window, script, integration_group );

	try {
		$top_head.append( $script );
	} catch (error) {
		ai4seo_rollback_top_frame_integration_bootstrap( top_window, script );
		console.error( ai4seo_get_plugin_name() + ': Could not append the primary bundle to the top frame.', error );
	}
}

// =========================================================================================== \\

function ai4seo_schedule_pending_top_frame_integration_groups() {
	const pending_integration_groups = window[ai4seo_pending_top_frame_integration_groups_key];

	// Clear the shared queue before scheduling so another click cannot be lost during initialization.
	ai4seo_clear_pending_top_frame_integration_groups( window );

	if (!Array.isArray( pending_integration_groups )) {
		return;
	}

	pending_integration_groups.forEach(
		function (integration_group) {
			ai4seo_schedule_external_integration_elements_init( integration_group );
		}
	);
}

// =========================================================================================== \\

function ai4seo_schedule_external_integration_elements_init(integration_group) {
	// Permission checks also restrict the scheduler to the two known integration groups.
	if (!ai4seo_is_external_integration_allowed( integration_group )) {
		return;
	}

	ai4seo_scheduled_external_integration_groups.add( integration_group );

	if (ai4seo_scheduled_external_integration_groups_request_id !== null) {
		return;
	}

	ai4seo_scheduled_external_integration_groups_request_id = ai4seo_schedule_next_animation_frame(
		ai4seo_flush_scheduled_external_integration_elements_init
	);
}

// =========================================================================================== \\

function ai4seo_flush_scheduled_external_integration_elements_init() {
	const scheduled_integration_groups = [...ai4seo_scheduled_external_integration_groups];

	// Reset before dispatch so an initializer can safely request a later animation frame.
	ai4seo_scheduled_external_integration_groups.clear();
	ai4seo_scheduled_external_integration_groups_request_id = null;

	scheduled_integration_groups.forEach(
		function (integration_group) {
			if (integration_group === ai4seo_external_metadata_integration_group) {
				ai4seo_init_external_metadata_interaction_elements();
			}

			if (integration_group === ai4seo_external_media_integration_group) {
				ai4seo_init_external_media_interaction_elements();
			}
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_external_metadata_interaction_elements() {
	if (!ai4seo_is_external_integration_allowed( ai4seo_external_metadata_integration_group )) {
		return;
	}

	// Initialize only metadata adapters and the controls they own in the current top-frame document.
	ai4seo_init_metadata_editor_integration_elements();
	ai4seo_init_generate_buttons( document, 'metadata' );
	ai4seo_init_metadata_editor_length_feedback();
	ai4seo_init_generate_all_buttons( document, 'metadata' );
	ai4seo_init_buttons();
}

// =========================================================================================== \\

function ai4seo_init_external_media_interaction_elements() {
	if (!ai4seo_is_external_integration_allowed( ai4seo_external_media_integration_group )) {
		return;
	}

	// Initialize the request-scoped media lifecycle before processing the interaction's affected controls.
	ai4seo_init_external_media_elements();

	if (ai4seo_has_asset_context( 'external-media' )) {
		// Standard MediaFrame events resolve their own roots; this one-shot fallback serves non-standard frames.
		ai4seo_schedule_wordpress_media_elements_init();
		ai4seo_schedule_wordpress_media_fallback_scan();
	} else {
		// Frontend page builders retain their click-scoped compatibility without installing WordPress MediaFrame hooks.
		ai4seo_init_generate_buttons( document, 'attachment-attributes' );
		ai4seo_init_generate_all_buttons( document, 'attachment-attributes' );
		ai4seo_init_buttons();
	}
}

// =========================================================================================== \\

/**
 * Convert Window roots to the Document shape consumed by DOM-scoped initializers and lookups.
 *
 * @param {*} scope_item Candidate DOM scope.
 * @return {*} Document for Window inputs; otherwise the original candidate.
 */
function ai4seo_get_dom_scope_item(scope_item) {
	// Window itself is not a selector root, while its Document represents the same browsing context.
	return scope_item && scope_item.window === scope_item && scope_item.document
		? scope_item.document
		: scope_item;
}

// =========================================================================================== \\

/**
 * Determine whether a DOM candidate is the requested scope or one of its descendants.
 *
 * Window candidates are compared through their Document so every scoped lookup uses the same
 * browsing-context boundary.
 *
 * @param {*} candidate_item Candidate DOM item.
 * @param {Node} scope_item DOM boundary.
 * @return {boolean}
 */
function ai4seo_is_dom_item_within_scope(candidate_item, scope_item) {
	// Normalize Window inputs before applying the common identity and containment checks.
	const normalized_candidate_item = ai4seo_get_dom_scope_item( candidate_item );

	return normalized_candidate_item === scope_item || Boolean(
		normalized_candidate_item
		&& normalized_candidate_item.nodeType
		&& jQuery.contains( scope_item, normalized_candidate_item )
	);
}

// =========================================================================================== \\

/**
 * Resolve a DOM root for a scheduled HTML initialization request.
 *
 * Multiple elements within the same scope are reduced to their common safe document root when
 * they are not nested. Invalid or unrestricted scopes intentionally fall back to the document.
 *
 * @param {jQuery|Node|Window|null} scope
 * @return {Node}
 */
function ai4seo_resolve_html_elements_init_scope(scope = null) {
	// An unrestricted request must cover every initializer that still scans the complete document.
	if (scope === null) {
		return document;
	}

	let $scope;

	// Normalize directly so Window and Document roots remain associated with their browsing context.
	try {
		$scope = jQuery( scope );
	} catch (error) {
		return document;
	}

	// Invalid roots fall back to the document so a requested reinitialization is never dropped.
	if (!ai4seo_exists_$( $scope )) {
		return document;
	}

	let resolved_scope = null;

	// Collapse collections into the outermost safe root before the request reaches the scheduler.
	$scope.each(
		function () {
			// Window roots dispatch against their Document because element initializers consume DOM scopes.
			const scope_item = ai4seo_get_dom_scope_item( this );

			// Non-DOM collection entries cannot provide a safe selector boundary.
			if (!scope_item || !scope_item.nodeType) {
				resolved_scope = document;
				return false;
			}

			// Keep nested requests narrow while unrelated roots safely expand to their owner document.
			resolved_scope = ai4seo_merge_html_elements_init_scopes( resolved_scope, scope_item );

			// The document is already the widest useful root, so remaining collection entries cannot change it.
			if (resolved_scope === document) {
				return false;
			}
		}
	);

	return resolved_scope || document;
}

// =========================================================================================== \\

/**
 * Merge two initialization roots, preferring the outer root and escalating unrelated roots.
 *
 * @param {Node|null} current_scope
 * @param {Node|null} requested_scope
 * @return {Node}
 */
function ai4seo_merge_html_elements_init_scopes(current_scope, requested_scope) {
	// Seed the pending request with its first usable root.
	if (!current_scope) {
		return requested_scope || document;
	}

	// Missing and duplicate follow-up roots cannot widen the pending request.
	if (!requested_scope || current_scope === requested_scope) {
		return current_scope;
	}

	// Nested requests share their existing outer root.
	if (typeof current_scope.contains === 'function' && current_scope.contains( requested_scope )) {
		return current_scope;
	}

	// A newly requested ancestor replaces the narrower pending root.
	if (typeof requested_scope.contains === 'function' && requested_scope.contains( current_scope )) {
		return requested_scope;
	}

	const current_document = current_scope.nodeType === 9 ? current_scope : current_scope.ownerDocument;
	const requested_document = requested_scope.nodeType === 9 ? requested_scope : requested_scope.ownerDocument;

	// Sibling roots require one document-wide pass, while cross-document roots fall back to this bundle's document.
	if (current_document && current_document === requested_document) {
		return current_document;
	}

	return document;
}

// =========================================================================================== \\

/**
 * Find the requested elements within one initialization root, including the root itself.
 *
 * @param {string} selector
 * @param {jQuery|Node|Window|null} scope
 * @return {jQuery}
 */
function ai4seo_get_elements_in_scope_$(selector, scope = null) {
	// Keep one authoritative root-inclusive lookup implementation for helpers and initializers.
	return ai4seo_normalize_$( selector, scope );
}

// =========================================================================================== \\

/**
 * Schedule a callback for the browser's next rendering opportunity.
 *
 * @param {Function} callback
 * @return {number}
 */
function ai4seo_schedule_next_animation_frame(callback) {
	// Use animation frames only when the browser exposes the matching cancellation primitive as well.
	if (ai4seo_can_use_animation_frame_scheduler()) {
		return window.requestAnimationFrame( callback );
	}

	return window.setTimeout( callback, 0 );
}

// =========================================================================================== \\

function ai4seo_can_use_animation_frame_scheduler() {
	// Treat partial requestAnimationFrame polyfills as timeout-only so every scheduled callback stays cancellable.
	return typeof window.requestAnimationFrame === 'function'
		&& typeof window.cancelAnimationFrame === 'function';
}

// =========================================================================================== \\

/**
 * Schedule one coalesced HTML initialization for the next animation frame.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_schedule_html_elements_init(scope = null) {
	const requested_scope = ai4seo_resolve_html_elements_init_scope( scope );

	// Merge every same-frame request before deciding whether another callback is necessary.
	ai4seo_scheduled_html_elements_init_scope = ai4seo_merge_html_elements_init_scopes(
		ai4seo_scheduled_html_elements_init_scope,
		requested_scope
	);

	// One pending callback is sufficient because later requests only widen its shared root.
	if (ai4seo_scheduled_html_elements_init_request_id !== null) {
		return;
	}

	// Retain the request ID as the scheduler's pending-state marker until the callback begins.
	ai4seo_scheduled_html_elements_init_request_id = ai4seo_schedule_next_animation_frame( ai4seo_flush_scheduled_html_elements_init );
}

// =========================================================================================== \\

/**
 * Run the pending HTML initialization after clearing scheduler state for safe reentry.
 */
function ai4seo_flush_scheduled_html_elements_init() {
	const scheduled_scope = ai4seo_scheduled_html_elements_init_scope || document;

	// Clear state before dispatch so initializers can safely request a later follow-up frame.
	ai4seo_scheduled_html_elements_init_scope = null;
	ai4seo_scheduled_html_elements_init_request_id = null;

	ai4seo_init_html_elements( scheduled_scope );
}

// =========================================================================================== \\

/**
 * Dispatch context-specific HTML initializers.
 *
 * @param {jQuery|Node|Window|null} scope Optional root for scope-aware initializers.
 */
function ai4seo_init_html_elements(scope = null) {
	// Every initializer in one dispatch must receive the same stable DOM boundary.
	const resolved_scope = ai4seo_resolve_html_elements_init_scope( scope );

	// Legal-gate controls must initialize before mandatory acceptance stops all normal features.
	if (ai4seo_has_asset_context( 'tos-gate' )) {
		ai4seo_init_tos_gate_elements( resolved_scope );
	}

	// Preserve the plugin-wide TOS gate for every caller that reinitializes dynamic markup.
	if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
		return;
	}

	// Every non-TOS context uses the common controls found in AJAX and schema modals.
	const shared_primary_asset_contexts = [
		'plugin-ui',
		'frontend-metadata-editor',
		'content-list',
		'post-editor',
		'external-media',
		'plugin-deactivation'
	];
	// Backend editors and frontend page builders share the same metadata integration group.
	const is_metadata_editor_integration_context = ai4seo_has_asset_context( 'post-editor' ) || ai4seo_has_asset_context( 'frontend-metadata-editor' );

	// Shared controls are the common foundation for every non-TOS initializer below.
	if (shared_primary_asset_contexts.some( (asset_context) => ai4seo_has_asset_context( asset_context ) )) {
		ai4seo_init_shared_primary_asset_elements( resolved_scope );
	}

	// Fragment passes own only their subtree; lifecycle controllers remain bound to the full current document pass.
	if (resolved_scope !== document) {
		return;
	}

	// Dispatch internal page behavior only when PHP selected the complete plugin UI.
	if (ai4seo_has_asset_context( 'plugin-ui' )) {
		ai4seo_init_plugin_ui_elements();
	}

	// Native WordPress list screens use only their bulk confirmation behavior.
	if (ai4seo_has_asset_context( 'content-list' )) {
		ai4seo_init_content_list_elements();
	}

	// Initialize the shared metadata integration group after its combined context decision.
	if (is_metadata_editor_integration_context) {
		ai4seo_init_metadata_editor_integration_elements();
	}

	// Core media screens initialize their Gutenberg and MediaFrame-related element handlers separately.
	if (ai4seo_has_asset_context( 'external-media' )) {
		ai4seo_init_external_media_elements();
	}

	// Installed Plugins is the only context allowed to intercept SOOZ deactivation.
	if (ai4seo_has_asset_context( 'plugin-deactivation' )) {
		ai4seo_init_plugin_deactivation_elements();
	}
}

// =========================================================================================== \\

/**
 * Initialize controls shared by every non-TOS primary-asset context.
 *
 * @param {jQuery|Node|Window|null} scope Optional root for generation controls inserted dynamically.
 */
function ai4seo_init_shared_primary_asset_elements(scope = null) {
	// Reinitialize context-independent controls after initial load and every dynamic markup replacement.
	ai4seo_init_tooltips( scope );
	ai4seo_init_editor_custom_instruction_tooltips( scope );

	// Add countdown functionality.
	ai4seo_init_countdown_elements( scope );

	// Add select all / unselect all checkbox functionality.
	ai4seo_init_select_all_checkboxes( scope );

	// Init staged slider inputs.
	ai4seo_init_slider_inputs( scope );

	// Init related media modal filter and bulk action controls.
	ai4seo_init_related_attachments_modal_controls( scope );
	ai4seo_init_bulk_generation_queue_action_descriptions( scope );

	// init inactive countdown buttons.
	ai4seo_init_inactive_countdown_buttons( scope );

	// init unsaved changes detection.
	ai4seo_init_unsaved_changes_warnings( scope );

	// Init SEO Autopilot modal controls after modal markup has been injected.
	if (ai4seo_exists_$( ai4seo_get_elements_in_scope_$( '#ai4seo_bulk_generation_auto_queue_entries', scope ) )) {
		ai4seo_handle_bulk_generation_auto_queue_entries_change( scope );
	}

	if (ai4seo_exists_$( ai4seo_get_elements_in_scope_$( '#ai4seo_bulk_generation_new_or_existing_filter', scope ) )) {
		ai4seo_handle_bulk_generation_new_or_existing_filter_change( scope );
	}

	// Init 'Generate with SOOZ' buttons.
	ai4seo_init_generate_buttons( scope );

	// Keep metadata-editor character counts aligned with each field's active generation target.
	ai4seo_init_metadata_editor_length_feedback( scope );

	// Initialize preview-first workspaces after generation controls have been inserted into their source fields.
	ai4seo_init_editor_view_modes( scope );
	ai4seo_init_editor_previews( scope );

	// Add 'Generate all with AI' buttons.
	ai4seo_init_generate_all_buttons( scope );

	// init buttons.
	ai4seo_init_buttons( scope );

	// init copy to clipboard functionality.
	ai4seo_init_copy_to_clipboard( scope );

	// Keep modal actions visible in every context that can open a SOOZ schema or AJAX modal.
	ai4seo_init_sticky_modal_footer( scope );

	// init attachment usage context status checks.
	ai4seo_init_attachment_usage_context_statuses( scope );

	// Let attachment image context thumbnails open a larger, accessible preview.
	ai4seo_init_attachment_context_image_previews( scope );

	// init custom instruction character counters.
	ai4seo_init_custom_instruction_counters( scope );

	// init auto resize textareas.
	ai4seo_init_auto_resize_textareas( scope );
}

// =========================================================================================== \\

function ai4seo_init_plugin_ui_elements() {
	// Internal plugin pages initialize shared Help and FAQ accordion behavior before page-specific controls.
	ai4seo_init_accordion_elements();

	// Init content list search form keyboard controls.
	ai4seo_init_content_list_search_forms();

	// Hydrate exact status filter counts after large content lists have rendered usable rows.
	ai4seo_init_content_type_status_filter_hydration();

	// Init custom SOOZ list bulk queue action controls.
	ai4seo_init_bulk_generation_queue_action_forms();

	// Init Help > Troubleshooting debug operation controls.
	ai4seo_init_debug_operation_form();

	// Init Help > Troubleshooting debug operation completion controls.
	ai4seo_init_debug_operation_completion_page();

	ai4seo_init_generated_data_reset_full_reset_note(
		'.ai4seo-troubleshooting-reset-generated-data-post-type-checkbox',
		'.ai4seo-troubleshooting-reset-generated-data-full-reset-note'
	);

	// Keep account-page TOS forms interactive when they are opened voluntarily.
	ai4seo_init_tos_accept_button_state();

	// init help page debug log actions.
	ai4seo_init_help_page_debug_log_actions();

	// Init license key reveal toggle.
	ai4seo_init_license_key_toggle();

	// Init forms on license page.
	ai4seo_init_license_form();

	// init advanced settings.
	ai4seo_init_advanced_settings();

	// Init Settings section navigation.
	ai4seo_init_settings_section_navigation();

	// init render-level alt text settings visibility.
	ai4seo_init_alt_text_injection_settings();

	// notifications.
	ai4seo_init_notifications();

	// init sticky-buttons-bar.
	ai4seo_init_sticky_buttons_bar();
}

// =========================================================================================== \\

function ai4seo_init_content_list_elements() {
	// Native lists require only the opt-in bulk queue confirmation handlers beyond shared controls.
	ai4seo_init_native_bulk_generation_queue_action_forms();
}

// =========================================================================================== \\

function ai4seo_init_metadata_editor_integration_elements() {
	// Post editors and page builders expose metadata shortcuts plus third-party SEO field adapters.
	// Reactive SEO adapters are limited to active integrations on WordPress post editors.
	ai4seo_init_generation_editor_integrations();

	// Add open-layer-button to edit-page-header.
	ai4seo_init_gutenberg_header_metadata_editor_button();
	ai4seo_add_open_edit_metadata_modal_button_to_elementor_app_bar();

	// BeBuilder navigation buttons are controlled by the external metadata button setting.
	if (ai4seo_are_external_metadata_generate_buttons_enabled()) {
		ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation();
	}

	// Elementor navigation buttons are controlled by the external metadata button setting.
	if (ai4seo_are_external_metadata_generate_buttons_enabled()) {
		ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation();
	}
}

// =========================================================================================== \\

function ai4seo_init_external_media_elements() {
	// External media contexts install event-driven WordPress and Gutenberg media lifecycles.
	if (ai4seo_are_external_media_generate_buttons_enabled()) {
		ai4seo_init_wordpress_media_lifecycle();
		ai4seo_init_gutenberg_media_lifecycle();
	}
}

// =========================================================================================== \\

function ai4seo_init_plugin_deactivation_elements() {
	// Scope feedback interception to Installed Plugins so foreign links retain their native behavior.
	ai4seo_init_plugin_deactivation_feedback();
}

// =========================================================================================== \\

/**
 * Initialize only the legal-gate controls present in the requested root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_tos_gate_elements(scope = null) {
	// The legal gate intentionally initializes only the controls needed before normal plugin execution resumes.
	// Add tooltip functionality inside the terms gate.
	ai4seo_init_tooltips( scope );

	// Bind TOS checkbox state so the modal stays interactive on repeat init.
	ai4seo_init_tos_accept_button_state();
}

// =========================================================================================== \\

/**
 * Get the closest unsaved changes container for a given element or return the element itself if it is a container.
 *
 * @param {*} element
 * @returns {jQuery|null}
 */
function ai4seo_get_unsaved_changes_container(element) {
	let $element = ai4seo_normalize_$( element );

	if (!ai4seo_exists_$( $element )) {
		console.warn( ai4seo_get_plugin_name() + ': element missing in ai4seo_get_unsaved_changes_container() — cannot find unsaved changes container.' );
		return null;
	}

	if ($element.hasClass( 'ai4seo-unsaved-changes-warnings' )) {
		return $element;
	}

	const $container = $element.closest( '.ai4seo-unsaved-changes-warnings' );

	if (ai4seo_exists_$( $container )) {
		return $container;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': No unsaved changes container found in ai4seo_get_unsaved_changes_container() for element.', $element );

	return null;
}

// =========================================================================================== \\

/**
 * Enable or disable save buttons inside a given unsaved changes container (or a child element inside it).
 *
 * @param {*} $container
 * @param {boolean} is_active
 * @param {boolean} [is_initial=false]
 */
function ai4seo_update_container_submit_buttons_state($container, is_active, is_initial = false) {
	$container = ai4seo_get_unsaved_changes_container( $container );

	if (!ai4seo_exists_$( $container )) {
		console.warn( ai4seo_get_plugin_name() + ': container missing in ai4seo_update_container_submit_buttons_state() — cannot update save button states.' );
		return;
	}

	// Save & edit next uses .ai4seo-save-button so it follows the same dirty-state gate as the modal save button.
	const $buttons = $container.find( '.ai4seo-submit-button, .ai4seo-save-button' );

	if (!ai4seo_exists_$( $buttons )) {
		console.warn( ai4seo_get_plugin_name() + ': No save button found in ai4seo_update_container_submit_buttons_state() for container.', $container );
		return;
	}

	$buttons.each(
        function () {
		const $button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $button )) {
			console.warn( ai4seo_get_plugin_name() + ': save button missing in ai4seo_update_container_submit_buttons_state() — skipping.', this );
			return;
		}

		// if initial and ai4seo-start-inactive is missing -> do not disable.
		if (is_initial && !$button.hasClass( 'ai4seo-start-inactive' )) {
			return;
		}

		$button.prop( 'disabled', !is_active );

		if (!is_active) {
			$button.addClass( 'ai4seo-inactive-button' );
		} else {
			$button.removeClass( 'ai4seo-inactive-button' );
			$button.removeClass( 'ai4seo-start-inactive' );
		}
        }
    );
}

// =========================================================================================== \\

/**
 * Set the unsaved changes state for a given container or child element.
 *
 * @param {*} $container
 * @param {boolean} has_unsaved_changes
 * @param {boolean} [is_initial=false]
 */
function ai4seo_set_unsaved_changes_state($container, has_unsaved_changes, is_initial = false) {
	$container = ai4seo_get_unsaved_changes_container( $container );

	if (!ai4seo_exists_$( $container )) {
		console.warn( ai4seo_get_plugin_name() + ': container missing in ai4seo_set_unsaved_changes_state() — cannot set state.' );
		return;
	}

	const data_attribute_name = 'data-' + ai4seo_unsaved_changes_data_attribute;
	$container.attr( data_attribute_name, has_unsaved_changes ? 'true' : 'false' );
	$container.data( ai4seo_unsaved_changes_data_attribute, !!has_unsaved_changes );

	ai4seo_update_container_submit_buttons_state( $container, !!has_unsaved_changes, is_initial );
}

// =========================================================================================== \\

/**
 * Remove the unsaved changes data attribute for the given container or child element.
 *
 * @param {*} element
 */
function ai4seo_remove_unsaved_changes_attribute(element) {
	const $container = ai4seo_get_unsaved_changes_container( element );

	if (!ai4seo_exists_$( $container )) {
		console.warn( ai4seo_get_plugin_name() + ': container missing in ai4seo_remove_unsaved_changes_attribute() — cannot remove attribute.' );
		return;
	}

	const data_attribute_name = 'data-' + ai4seo_unsaved_changes_data_attribute;

	$container.removeAttr( data_attribute_name );
	$container.removeData( ai4seo_unsaved_changes_data_attribute );

	ai4seo_update_container_submit_buttons_state( $container, false );
}

// =========================================================================================== \\

/**
 * Determine whether any tracked container currently has unsaved changes.
 *
 * @returns {boolean}
 */
function ai4seo_has_unsaved_changes() {
	const $containers = ai4seo_normalize_$( '.ai4seo-unsaved-changes-warnings' );

	if (!ai4seo_exists_$( $containers )) {
		return false;
	}

	let has_unsaved = false;

	$containers.each(
        function () {
		if (ai4seo_container_has_unsaved_changes( this )) {
			has_unsaved = true;
			return false; // break loop.
		}
        }
    );

	return has_unsaved;
}

// =========================================================================================== \\

/**
 * Check if a given unsaved changes container currently has pending changes.
 *
 * @param {*} container
 * @returns {boolean}
 */
function ai4seo_container_has_unsaved_changes(container) {
	const $container = ai4seo_get_unsaved_changes_container( container );

	if (!ai4seo_exists_$( $container )) {
		console.warn( ai4seo_get_plugin_name() + ': container missing in ai4seo_container_has_unsaved_changes() — assuming no unsaved changes.' );
		return false;
	}

	const unsaved_changes_data = $container.data( ai4seo_unsaved_changes_data_attribute );
	const unsaved_changes_attr = $container.attr( 'data-' + ai4seo_unsaved_changes_data_attribute );

	return (unsaved_changes_data === true || unsaved_changes_attr === 'true');
}

// =========================================================================================== \\

/**
 * Initialize navigation guards that warn about unsaved changes when leaving the page.
 */
function ai4seo_init_unsaved_changes_navigation_guard() {
	if (ai4seo_unsaved_changes_navigation_initialized) {
		return;
	}

	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.warn( ai4seo_get_plugin_name() + ': document missing in ai4seo_init_unsaved_changes_navigation_guard() — cannot initialize navigation guard.' );
		return;
	}

	let ai4seo_allow_unload_without_prompt = false;

	window.addEventListener(
        'beforeunload',
        function (event) {
		if (ai4seo_allow_unload_without_prompt === true) {
			return;
		}

		if (!ai4seo_has_unsaved_changes()) {
			return;
		}

		// hide a potential full page loading screen.
		ai4seo_hide_full_page_loading_screen();
		event.preventDefault();
		event.returnValue = '';
        }
    );

	// Scope delegated navigation warnings to plugin UI links so unrelated admin links keep native behavior.
	const guarded_link_selector = '.ai4seo-wrap a, .ai4seo-mobile-top-bar a, .ai4seo-modal-wrapper a';

	$document.off( 'click.ai4seo-unsaved-changes-navigation' );
	$document.on(
        'click.ai4seo-unsaved-changes-navigation',
        guarded_link_selector,
        function (event) {
		const $link = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $link )) {
			return;
		}

		if (typeof event.isDefaultPrevented === 'function' && event.isDefaultPrevented()) {
			return;
		}

		if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		// Non-page navigations should not prompt because they do not abandon unsaved plugin settings.
		const href = ($link.attr( 'href' ) || '').trim();
		const normalized_href = href.toLowerCase();

		if (!href || href === '#' || href.charAt( 0 ) === '#') {
			return;
		}

		if (normalized_href.indexOf( 'javascript:' ) === 0 || normalized_href.indexOf( 'mailto:' ) === 0 || normalized_href.indexOf( 'tel:' ) === 0) {
			return;
		}

		if ($link.is( '[download]' ) || ($link.attr( 'target' ) || '').toLowerCase() === '_blank') {
			return;
		}

		if (!ai4seo_has_unsaved_changes()) {
			return;
		}

		const confirm_navigation = window.confirm(
			wp.i18n.__( 'You have unsaved changes. Are you sure you want to leave this page?', 'ai-for-seo' )
		);

		if (confirm_navigation === false) {
			event.preventDefault();
			event.stopPropagation();

			// hide a potential full page loading screen.
			ai4seo_hide_full_page_loading_screen();
			return;
		}

		// User confirmed: disable beforeunload for this navigation.
		ai4seo_allow_unload_without_prompt = true;

		// re-enable shortly after, in case navigation is blocked/cancelled.
		setTimeout(
            function () {
			ai4seo_allow_unload_without_prompt = false;
            },
            1000
        );
        }
    );

	ai4seo_unsaved_changes_navigation_initialized = true;
}

// =========================================================================================== \\

/**
 * Initialize unsaved changes tracking for containers and their inputs.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_unsaved_changes_warnings(scope = null) {
	const $unsaved_changes_containers = ai4seo_get_elements_in_scope_$( '.ai4seo-unsaved-changes-warnings', scope );

	if (!ai4seo_exists_$( $unsaved_changes_containers )) {
		return;
	}

	ai4seo_init_unsaved_changes_navigation_guard();

	$unsaved_changes_containers.each(
        function () {
		const $container = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $container )) {
			console.warn( ai4seo_get_plugin_name() + ': container missing in ai4seo_init_unsaved_changes_warnings() — skipping.' );
			return;
		}

		// Ignore visual preview controls that should not trigger settings save warnings.
		const $inputs = ai4seo_filter_persistent_inputs( $container.find( 'input, textarea, select' ) );

		if (!ai4seo_exists_$( $inputs )) {
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': No inputs found in ai4seo_init_unsaved_changes_warnings() for container.', $container );
			return;
		}

		// Preserve any dirty state from earlier init calls, while still rebinding current DOM inputs below.
		const has_unsaved_changes_attribute = typeof $container.attr( 'data-' + ai4seo_unsaved_changes_data_attribute ) !== 'undefined';

		if (!has_unsaved_changes_attribute) {
			ai4seo_set_unsaved_changes_state( $container, false, true );
		}

		// Rebind persistent inputs on every init so AJAX-inserted settings join the same warning flow.
		$inputs.off( 'change.ai4seo-unsaved keyup.ai4seo-unsaved' );
		$inputs.on(
            'change.ai4seo-unsaved keyup.ai4seo-unsaved',
            function () {
			const $target_container = ai4seo_get_unsaved_changes_container( this );

			if (!ai4seo_exists_$( $target_container )) {
				return;
			}

			ai4seo_set_unsaved_changes_state( $target_container, true );
            }
        );
        }
    );
}

// =========================================================================================== \\

/**
 * Register the delegated pressed-state handlers once they have at least one applicable button.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_buttons(scope = null) {
	// Repeated HTML initialization must not stack document- or window-level handlers.
	if (ai4seo_button_press_handlers_initialized) {
		return;
	}

	// Delay global handler registration until the first static or dynamically inserted button exists.
	if (!ai4seo_exists_$( ai4seo_get_elements_in_scope_$( '.ai4seo-button', scope ) )) {
		return;
	}

	// Delegation belongs to the current document so later AJAX buttons inherit the same behavior.
	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$document\" missing in ai4seo_init_notifications() \u2014 cannot initialize notification dismissal.' );
		return;
	}

	// Window-level release events clear pressed state even when the pointer leaves the originating button.
	const $window = ai4seo_normalize_$( window );

	if (!ai4seo_exists_$( $window )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$window\" missing in ai4seo_init_buttons() \u2014 cannot initialize button press states.' );
		return;
	}

	// Delegation covers buttons inserted after this one-time registration.
	$document.on(
        'mousedown.ai4seo-button-press',
        '.ai4seo-button',
        function () {
		const $this_button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_button )) {
			console.warn( ai4seo_get_plugin_name() + ': element "$this_button" missing in ai4seo_init_buttons() — cannot add currently-pressed state.' );
			return;
		}

		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Button pressed:', $this_button );

		ai4seo_last_pressed_button = $this_button;
		$this_button.data( 'currently-pressed', 'true' );
        }
    );

	// Global release prevents pressed state from sticking when pointer release occurs outside the button.
	$window.on(
		'mouseup.ai4seo-button-press ' +
		'pointerup.ai4seo-button-press ' +
		'blur.ai4seo-button-press',
		function (event) {
			if (!ai4seo_exists_$( ai4seo_last_pressed_button )) {
				ai4seo_last_pressed_button = null;
				return;
			}

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Button released (' + event.type + '):', ai4seo_last_pressed_button );

			ai4seo_last_pressed_button.removeData( 'currently-pressed' );
			ai4seo_last_pressed_button = null;
		}
	);

	// Mark registration only after both persistent handlers have been attached successfully.
	ai4seo_button_press_handlers_initialized = true;
}

// =========================================================================================== \\

function ai4seo_init_sticky_buttons_bar() {
	const $sticky_buttons_bars = ai4seo_normalize_$( '.ai4seo-sticky-buttons-bar' );
	const buffer_tolerance = 20; // pixels.

	if (!ai4seo_exists_$( $sticky_buttons_bars )) {
		return;
	}

	const $window = ai4seo_normalize_$( window );

	if (!ai4seo_exists_$( $window )) {
		console.error( ai4seo_get_plugin_name() + ': window object missing in ai4seo_init_sticky_buttons_bar() — cannot calculate box-shadow removal on scroll.' );
		return;
	}

	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.error( ai4seo_get_plugin_name() + ': document object missing in ai4seo_init_sticky_buttons_bar() — cannot calculate box-shadow removal on scroll.' );
		return;
	}

	// compute for all sticky bars.
	function ai4seo_sticky_buttons_bar_compute() {
		const scroll_top = $window.scrollTop();
		const window_height = $window.height();
		const document_height = $document.height();
		const at_bottom = (scroll_top + window_height >= document_height - buffer_tolerance);

		$sticky_buttons_bars.each(
            function () {
			const $this_sticky_buttons_bar = ai4seo_normalize_$( this );
			if (!ai4seo_exists_$( $this_sticky_buttons_bar )) {
				return;
			}

			const $this_possible_buttons_wrapper = $this_sticky_buttons_bar.find( '.ai4seo-buttons-wrapper' );
			const $this_target = ai4seo_exists_$( $this_possible_buttons_wrapper ) ? $this_possible_buttons_wrapper : $this_sticky_buttons_bar;

			if (at_bottom === true) {
				$this_target.removeClass( 'ai4seo_sticky_element_hides_something' );
			} else {
				$this_target.addClass( 'ai4seo_sticky_element_hides_something' );
			}
            }
        );
	}

	// resize handler reuses the same compute, debounced.
	const debounced_resize = ai4seo_debounce(
        function () {
		ai4seo_sticky_buttons_bar_compute();
        },
        100
    );

	// ensure only one global handler each.
	$window.off( 'scroll.ai4seo-sticky-buttons-bar' ).on( 'scroll.ai4seo-sticky-buttons-bar', ai4seo_sticky_buttons_bar_compute );
	$window.off( 'resize.ai4seo-sticky-buttons-bar' ).on( 'resize.ai4seo-sticky-buttons-bar', debounced_resize );

	// initial compute.
	ai4seo_sticky_buttons_bar_compute();
}

// =========================================================================================== \\

/**
 * Recompute every visible modal footer from the one shared window resize handler.
 */
function ai4seo_refresh_sticky_modal_footers() {
	const $visible_modals = ai4seo_normalize_$( '.ai4seo-modal:visible' );

	$visible_modals.each(
		function () {
			ai4seo_normalize_$( this ).triggerHandler( 'scroll.ai4seo-modal-footer' );
		}
	);
}

// =========================================================================================== \\

/**
 * Bind newly inserted modal footers without disturbing handlers owned by other modal roots.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_sticky_modal_footer(scope = null) {
	const $modal_footers = ai4seo_get_elements_in_scope_$( '.ai4seo-modal-footer', scope );
	const buffer_tolerance = 20; // pixels.

	if (!ai4seo_exists_$( $modal_footers )) {
		return;
	}

	const $window = ai4seo_normalize_$( window );

	if (!ai4seo_exists_$( $window )) {
		console.error( ai4seo_get_plugin_name() + ': window object missing in ai4seo_init_sticky_modal_footer() \u2014 cannot calculate box-shadow removal on scroll.' );
		return;
	}

	// Register one persistent window handler only after the first modal footer exists.
	if (ai4seo_sticky_modal_footer_resize_handler === null) {
		ai4seo_sticky_modal_footer_resize_handler = ai4seo_debounce(
			ai4seo_refresh_sticky_modal_footers,
			100
		);

		$window
			.off( 'resize.ai4seo-modal-footer' )
			.on( 'resize.ai4seo-modal-footer', ai4seo_sticky_modal_footer_resize_handler );
	}

	$modal_footers.each(
        function () {
		const $this_footer = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_footer )) {
			console.warn( ai4seo_get_plugin_name() + ': element "$footer" missing in ai4seo_init_modal_footer_shadow_toggle() — skipping item.' );
			return;
		}

		// find the modal scroll container.
		const $this_modal = $this_footer.closest( '.ai4seo-modal' );

		if (!ai4seo_exists_$( $this_modal )) {
			console.warn( ai4seo_get_plugin_name() + ': element ".ai4seo-modal" missing for footer — cannot calculate box-shadow removal on scroll.' );
			return;
		}

		// prefer inner buttons wrapper if present.
		const $this_buttons_wrapper = $this_footer.find( '.ai4seo-buttons-wrapper' );
		const $this_target = ai4seo_exists_$( $this_buttons_wrapper ) ? $this_buttons_wrapper : $this_footer;

		// ensure we do not stack handlers for the same modal.
		$this_modal.off( 'scroll.ai4seo-modal-footer' );
		$this_modal.on(
            'scroll.ai4seo-modal-footer',
            function () {
			const $this_modal = ai4seo_normalize_$( this );

			if (!ai4seo_exists_$( $this_modal )) {
				return;
			}

			const this_scroll_top = $this_modal.scrollTop();
			const this_visible_height = $this_modal.innerHeight();
			const this_content_height = this.scrollHeight;

			if (this_scroll_top + this_visible_height >= this_content_height - buffer_tolerance) {
				// scrolled to bottom inside modal.
				$this_target.removeClass( 'ai4seo_sticky_element_hides_something' );
			} else {
				// not at bottom.
				$this_target.addClass( 'ai4seo_sticky_element_hides_something' );
			}
            }
        );

		// set initial state.
		$this_modal.triggerHandler( 'scroll' );
        }
    );
}

// =========================================================================================== \\

/**
 * Bind copy controls within the requested initialization root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_copy_to_clipboard(scope = null) {
	// Loop through all elements with class ai4seo-copy-to-clipboard.
	const $copy_to_clipboard_targets = ai4seo_get_elements_in_scope_$( '.ai4seo-copy-to-clipboard', scope );

	if (!ai4seo_exists_$( $copy_to_clipboard_targets )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': elements \".ai4seo-copy-to-clipboard\" missing in ai4seo_init_copy_to_clipboard() \u2014 skipping clipboard binding.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': Initializing copy to clipboard for ' + $copy_to_clipboard_targets.length + ' elements in ai4seo_init_copy_to_clipboard().' );

	$copy_to_clipboard_targets.each(
        function () {
		const $this = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$this\" missing in ai4seo_init_copy_to_clipboard() \u2014 skipping item.' );
			return;
		}

		// Get the text to copy from the data-clipboard-text attribute.
		let text_to_copy = $this.data( 'clipboard-text' );

		// If the text is not defined, skip this element.
		if (typeof text_to_copy === 'undefined' || !text_to_copy) {
			console.warn( ai4seo_get_plugin_name() + ': Could not copy to clipboard' );
			return;
		}

		// find closest .ai4seo-copied-to-clipboard to display it for 3 seconds.
		let $copied_to_clipboard = ai4seo_get_nearest_element_$( this, '.ai4seo-copied-to-clipboard' );

		// Add click event listener to the element.
		$this.off( 'click.ai4seo-copy-to-clipboard' );
		$this.on(
            'click.ai4seo-copy-to-clipboard',
            function (event) {
			event.preventDefault();
			ai4seo_copy_to_clipboard( text_to_copy, $copied_to_clipboard );
            }
        );
        }
    );
}

// =========================================================================================== \\

/**
 * Searches for an element with the given target selector that is a sibling, child, or closest ancestor of the provided element.
 *
 * @param $reference
 * @param target_selector
 * @returns {*|null}
 */
function ai4seo_get_nearest_element_$($reference, target_selector) {
	$reference = ai4seo_normalize_$( $reference );

	// Check if the element exists.
	if (!ai4seo_exists_$( $reference )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$reference\" missing in ai4seo_get_nearest_element() \u2014 unable to resolve related UI.' );
		return null;
	}

	// check sibling elements first.
	let $sibling = $reference.siblings( target_selector );

	if (ai4seo_exists_$( $sibling )) {
		return $sibling.first();
	}

	// check children elements with find.
	let $child = $reference.find( target_selector );

	if (ai4seo_exists_$( $child )) {
		// If a child element is found, return the first one.
		return $child.first();
	}

	// check closest element with the target selector.
	let $closest = $reference.closest( target_selector );

	// If no closest element found, check for the next sibling element.
	if (ai4seo_exists_$( $closest )) {
		return $closest.first();
	}

	return null;
}


// =========================================================================================== \\

function ai4seo_get_container_spare_height($container) {
	$container = ai4seo_normalize_$( $container );

	if (!ai4seo_exists_$( $container )) {
		console.warn( ai4seo_get_plugin_name() + ': container \"$container\" missing in ai4seo_get_container_spare_height() \u2014 layout measurement unavailable.' );
		return;
	}

	// go through all children and sum their heights.
	let container_element_children_elements_height = 0;

	const $container_children = $container.children();

	if (ai4seo_exists_$( $container_children )) {
		$container_children.each(
            function () {
			const $child = ai4seo_normalize_$( this );

			if (!ai4seo_exists_$( $child )) {
				console.error( ai4seo_get_plugin_name() + ': element \"$child\" missing in ai4seo_get_container_spare_height() \u2014 DOM traversal skipped.' );
				return;
			}

			container_element_children_elements_height += $child.outerHeight( true );
            }
        );
	}

	let container_height = $container.outerHeight( true );

	return container_height - container_element_children_elements_height;
}

// =========================================================================================== \\

/**
 * Return Gutenberg's optional block-editor store without coupling non-editor screens to wp.data.
 *
 * @return {Object|null}
 */
function ai4seo_get_gutenberg_block_editor_store() {
	if (
		typeof window.wp === 'undefined'
		|| !window.wp.data
		|| typeof window.wp.data.select !== 'function'
	) {
		return null;
	}

	try {
		const block_editor_store = window.wp.data.select( 'core/block-editor' );

		return block_editor_store && typeof block_editor_store.getSelectedBlock === 'function'
			? block_editor_store
			: null;
	} catch (error) {
		return null;
	}
}

// =========================================================================================== \\

/**
 * Check whether the current request may track Gutenberg's editor lifecycle.
 *
 * Store registration can finish after DOM ready, so discovery must not depend on the block-editor
 * selectors being available yet.
 *
 * @return {boolean}
 */
function ai4seo_should_track_gutenberg_media_lifecycle() {
	return ai4seo_are_external_media_generate_buttons_enabled()
		&& ai4seo_has_asset_context( 'external-media' )
		&& typeof window.wp !== 'undefined'
		&& window.wp.data
		&& typeof window.wp.data.subscribe === 'function';
}

// =========================================================================================== \\

/**
 * Check whether Gutenberg's store is ready for attachment-control initialization.
 *
 * @return {boolean}
 */
function ai4seo_should_initialize_gutenberg_media_lifecycle() {
	return ai4seo_should_track_gutenberg_media_lifecycle()
		&& ai4seo_get_gutenberg_block_editor_store() !== null;
}

// =========================================================================================== \\

/**
 * Build the minimal state token needed to ignore unrelated wp.data notifications.
 *
 * @return {string}
 */
function ai4seo_get_gutenberg_selected_block_signature() {
	const block_editor_store = ai4seo_get_gutenberg_block_editor_store();

	if (!block_editor_store) {
		return '';
	}

	let selected_block = null;

	try {
		selected_block = block_editor_store.getSelectedBlock();
	} catch (error) {
		return '';
	}

	if (!selected_block) {
		return '';
	}

	const selected_block_attributes = selected_block.attributes && typeof selected_block.attributes === 'object'
		? selected_block.attributes
		: {};
	const attachment_id = selected_block_attributes.mediaId || selected_block_attributes.id || '';

	return [selected_block.clientId || '', selected_block.name || '', attachment_id].join( ':' );
}

// =========================================================================================== \\

/**
 * Resolve the narrowest stable editor shell that can contain the Inspector or editor canvas.
 *
 * @return {HTMLElement|null}
 */
function ai4seo_get_gutenberg_media_observer_target() {
	for (const observer_target_selector of ai4seo_gutenberg_media_observer_target_selectors) {
		const observer_target = document.querySelector( observer_target_selector );

		if (observer_target) {
			return observer_target;
		}
	}

	return null;
}

// =========================================================================================== \\

/**
 * Limit observer work to batches that mount an Inspector input or replace the editor canvas.
 *
 * @param {MutationRecord[]} mutations
 * @return {boolean}
 */
function ai4seo_does_mutation_batch_contain_gutenberg_media_elements(mutations) {
	return mutations.some(
		function (mutation) {
			return Array.from( mutation.addedNodes || [] ).some(
				function (added_node) {
					if (!added_node || added_node.nodeType !== 1) {
						return false;
					}

					if (added_node.matches( ai4seo_gutenberg_block_inspector_selector + ', ' + ai4seo_gutenberg_editor_iframe_selector )
						|| added_node.querySelector( ai4seo_gutenberg_block_inspector_selector + ', ' + ai4seo_gutenberg_editor_iframe_selector )) {
						return true;
					}

					if (added_node.matches( ai4seo_gutenberg_media_observer_target_selectors.join( ', ' ) )) {
						return true;
					}

					if (added_node.matches( ai4seo_gutenberg_attachment_input_selector )) {
						return added_node.closest( ai4seo_gutenberg_block_inspector_selector ) !== null;
					}

					return Array.from( added_node.querySelectorAll( ai4seo_gutenberg_attachment_input_selector ) ).some(
						(attachment_input) => attachment_input.closest( ai4seo_gutenberg_block_inspector_selector ) !== null
					);
				}
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Bind each current editor canvas once and release references to canvases removed by navigation.
 *
 * @return {boolean} Whether a previously unseen iframe was bound.
 */
function ai4seo_bind_gutenberg_media_iframe_events() {
	let iframe_was_bound = false;

	// Drop retained references to editor canvases removed during navigation or template switching.
	for (const bound_iframe of ai4seo_gutenberg_media_bound_iframes) {
		if (bound_iframe.isConnected !== false) {
			continue;
		}

		jQuery( bound_iframe ).off( 'load.ai4seo-gutenberg-media' );
		ai4seo_gutenberg_media_bound_iframes.delete( bound_iframe );
	}

	document.querySelectorAll( ai4seo_gutenberg_editor_iframe_selector ).forEach(
		function (editor_iframe) {
			if (ai4seo_gutenberg_media_bound_iframes.has( editor_iframe )) {
				return;
			}

			jQuery( editor_iframe )
				.off( 'load.ai4seo-gutenberg-media' )
				.on(
					'load.ai4seo-gutenberg-media',
					function () {
						ai4seo_schedule_gutenberg_attachment_controls_init();
					}
				);

			ai4seo_gutenberg_media_bound_iframes.add( editor_iframe );
			iframe_was_bound = true;
		}
	);

	return iframe_was_bound;
}

// =========================================================================================== \\

/**
 * Keep one filtered observer attached to the current Gutenberg editor shell.
 *
 * @return {boolean} Whether the observed target changed.
 */
function ai4seo_sync_gutenberg_media_observer() {
	const observer_target = ai4seo_get_gutenberg_media_observer_target();

	if (
		ai4seo_gutenberg_media_observer
		&& ai4seo_gutenberg_media_observer_target === observer_target
		&& observer_target
		&& observer_target.isConnected !== false
	) {
		return false;
	}

	if (ai4seo_gutenberg_media_observer) {
		ai4seo_gutenberg_media_observer.disconnect();
		ai4seo_gutenberg_media_observer = null;
	}

	ai4seo_gutenberg_media_observer_target = observer_target;

	if (!observer_target || typeof window.MutationObserver !== 'function') {
		return false;
	}

	ai4seo_gutenberg_media_observer = new window.MutationObserver(
		function (mutations) {
			if (!ai4seo_does_mutation_batch_contain_gutenberg_media_elements( mutations )) {
				return;
			}

			ai4seo_sync_gutenberg_media_observer();
			ai4seo_bind_gutenberg_media_iframe_events();
			ai4seo_schedule_gutenberg_attachment_controls_init();
		}
	);

	ai4seo_gutenberg_media_observer.observe(
		observer_target,
		{
			childList: true,
			subtree: true,
		}
	);

	return true;
}

// =========================================================================================== \\

/**
 * Check whether discovery already owns a connected Gutenberg editor shell.
 *
 * @return {boolean}
 */
function ai4seo_is_gutenberg_media_observer_current() {
	return Boolean(
		ai4seo_gutenberg_media_observer
		&& ai4seo_gutenberg_media_observer_target
		&& ai4seo_gutenberg_media_observer_target.isConnected !== false
	);
}

// =========================================================================================== \\

/**
 * Reinitialize only when Gutenberg selection or editor-shell ownership actually changes.
 */
function ai4seo_handle_gutenberg_media_store_change() {
	if (!ai4seo_should_track_gutenberg_media_lifecycle()) {
		ai4seo_cleanup_gutenberg_media_lifecycle();
		return;
	}

	const block_editor_store_is_ready = ai4seo_get_gutenberg_block_editor_store() !== null;
	const selected_block_signature = block_editor_store_is_ready
		? ai4seo_get_gutenberg_selected_block_signature()
		: null;
	const selected_block_changed = selected_block_signature !== ai4seo_gutenberg_media_selected_block_signature;

	// Gutenberg notifies subscribers for every data store. Avoid repeated document queries while
	// neither the selected block nor the editor-shell ownership has changed.
	if (!selected_block_changed && ai4seo_is_gutenberg_media_observer_current()) {
		return;
	}

	const observer_target_changed = ai4seo_sync_gutenberg_media_observer();
	const iframe_was_bound = ai4seo_bind_gutenberg_media_iframe_events();

	ai4seo_gutenberg_media_selected_block_signature = selected_block_signature;

	if (block_editor_store_is_ready && (selected_block_changed || observer_target_changed || iframe_was_bound)) {
		ai4seo_schedule_gutenberg_attachment_controls_init();
	}
}

// =========================================================================================== \\

/**
 * Coalesce Gutenberg attachment-control work into one render-frame callback.
 */
function ai4seo_schedule_gutenberg_attachment_controls_init() {
	if (!ai4seo_should_initialize_gutenberg_media_lifecycle() || ai4seo_gutenberg_media_init_request_id !== null) {
		return;
	}

	ai4seo_gutenberg_media_init_request_id = ai4seo_schedule_next_animation_frame(
		ai4seo_flush_gutenberg_attachment_controls_init
	);
}

// =========================================================================================== \\

/**
 * Initialize attachment controls only within the active block Inspector.
 */
function ai4seo_flush_gutenberg_attachment_controls_init() {
	ai4seo_gutenberg_media_init_request_id = null;

	if (!ai4seo_should_initialize_gutenberg_media_lifecycle()) {
		return;
	}

	const $block_inspector = ai4seo_get_elements_in_scope_$( ai4seo_gutenberg_block_inspector_selector, document )
		.filter( ':visible' )
		.first();

	if (!ai4seo_exists_$( $block_inspector )) {
		return;
	}

	ai4seo_init_generate_buttons( $block_inspector, 'attachment-attributes' );
	ai4seo_init_buttons( $block_inspector );
}

// =========================================================================================== \\

/**
 * Cancel the pending Inspector initialization with its matching scheduling primitive.
 */
function ai4seo_cancel_scheduled_gutenberg_attachment_controls_init() {
	if (ai4seo_gutenberg_media_init_request_id === null) {
		return;
	}

	if (ai4seo_can_use_animation_frame_scheduler()) {
		window.cancelAnimationFrame( ai4seo_gutenberg_media_init_request_id );
	} else {
		window.clearTimeout( ai4seo_gutenberg_media_init_request_id );
	}

	ai4seo_gutenberg_media_init_request_id = null;
}

// =========================================================================================== \\

/**
 * Release every Gutenberg media resource before navigation or a context shutdown.
 */
function ai4seo_cleanup_gutenberg_media_lifecycle() {
	if (typeof ai4seo_gutenberg_media_store_unsubscribe === 'function') {
		ai4seo_gutenberg_media_store_unsubscribe();
	}

	ai4seo_gutenberg_media_store_unsubscribe = null;
	ai4seo_gutenberg_media_store_subscription_initialized = false;

	if (ai4seo_gutenberg_media_observer) {
		ai4seo_gutenberg_media_observer.disconnect();
	}

	ai4seo_gutenberg_media_observer = null;
	ai4seo_gutenberg_media_observer_target = null;

	for (const bound_iframe of ai4seo_gutenberg_media_bound_iframes) {
		jQuery( bound_iframe ).off( 'load.ai4seo-gutenberg-media' );
	}

	ai4seo_gutenberg_media_bound_iframes.clear();
	ai4seo_cancel_scheduled_gutenberg_attachment_controls_init();
	ai4seo_gutenberg_media_selected_block_signature = null;
}

// =========================================================================================== \\

/**
 * Register page lifecycle cleanup once while allowing bfcache documents to reconnect.
 */
function ai4seo_init_gutenberg_media_lifecycle_events() {
	if (ai4seo_gutenberg_media_lifecycle_events_initialized) {
		return;
	}

	const $window = ai4seo_normalize_$( window );

	if (!ai4seo_exists_$( $window )) {
		return;
	}

	$window
		.off( 'pagehide.ai4seo-gutenberg-media pageshow.ai4seo-gutenberg-media' )
		.on( 'pagehide.ai4seo-gutenberg-media', ai4seo_cleanup_gutenberg_media_lifecycle )
		.on(
			'pageshow.ai4seo-gutenberg-media',
			function (event) {
				const original_event = event && event.originalEvent ? event.originalEvent : event;

				if (original_event && original_event.persisted === true) {
					ai4seo_init_gutenberg_media_lifecycle();
				}
			}
		);

	ai4seo_gutenberg_media_lifecycle_events_initialized = true;
}

// =========================================================================================== \\

/**
 * Install the event-driven Gutenberg media lifecycle idempotently.
 */
function ai4seo_init_gutenberg_media_lifecycle() {
	if (!ai4seo_gutenberg_media_dom_ready_callback_registered
		&& typeof window.wp !== 'undefined'
		&& typeof window.wp.domReady === 'function') {
		ai4seo_gutenberg_media_dom_ready_callback_registered = true;
		window.wp.domReady( ai4seo_init_gutenberg_media_lifecycle );
	}

	if (!ai4seo_should_track_gutenberg_media_lifecycle()) {
		ai4seo_cleanup_gutenberg_media_lifecycle();
		return;
	}

	ai4seo_init_gutenberg_media_lifecycle_events();

	if (!ai4seo_gutenberg_media_store_subscription_initialized) {
		ai4seo_gutenberg_media_store_unsubscribe = window.wp.data.subscribe( ai4seo_handle_gutenberg_media_store_change );
		ai4seo_gutenberg_media_store_subscription_initialized = true;
	}

	const observer_target_changed = ai4seo_sync_gutenberg_media_observer();
	const iframe_was_bound = ai4seo_bind_gutenberg_media_iframe_events();
	const block_editor_store_is_ready = ai4seo_get_gutenberg_block_editor_store() !== null;

	if (!block_editor_store_is_ready) {
		// The early data subscription and DOM observer will re-enter as soon as the editor store mounts.
		ai4seo_gutenberg_media_selected_block_signature = null;
		return;
	}

	const selected_block_signature = ai4seo_get_gutenberg_selected_block_signature();
	const selected_block_changed = selected_block_signature !== ai4seo_gutenberg_media_selected_block_signature;

	ai4seo_gutenberg_media_selected_block_signature = selected_block_signature;

	if (selected_block_changed || observer_target_changed || iframe_was_bound) {
		ai4seo_schedule_gutenberg_attachment_controls_init();
	}
}

// =========================================================================================== \\

function ai4seo_toggle_sidebar() {
	const $sidebar = ai4seo_normalize_$( '.ai4seo-sidebar' );
	const $toggle_button = ai4seo_normalize_$( '.ai4seo-mobile-top-bar-toggle-button' );

	if (!ai4seo_exists_$( $sidebar )) {
		console.error( ai4seo_get_plugin_name() + ': $sidebar missing in ai4seo_toggle_sidebar() \u2014 cannot toggle sidebar visibility.' );
		return;
	}

	if (!ai4seo_exists_$( $toggle_button )) {
		console.error( ai4seo_get_plugin_name() + ': $toggle_button missing in ai4seo_toggle_sidebar() \u2014 cannot synchronize sidebar state.' );
		return;
	}

	ai4seo_set_sidebar_open_state( $sidebar, $toggle_button, !$sidebar.hasClass( 'ai4seo-sidebar-open' ) );
}

// =========================================================================================== \\

/**
 * Synchronize mobile sidebar visibility, toggle state, and outside-click handling.
 *
 * @param {jQuery} $sidebar Sidebar element.
 * @param {jQuery} $toggle_button Sidebar toggle button.
 * @param {boolean} is_open Whether the sidebar should be open.
 */
function ai4seo_set_sidebar_open_state($sidebar, $toggle_button, is_open) {
	// Apply the visual and accessible states together so every close path leaves the toggle synchronized.
	$sidebar.toggleClass( 'ai4seo-sidebar-open', is_open );
	$toggle_button.attr( 'aria-expanded', is_open ? 'true' : 'false' );

	// Replace the namespaced document handler so repeated toggles never accumulate outside-click listeners.
	const $document = ai4seo_normalize_$( document );

	$document.off( 'click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click );

	if (is_open) {
		$document.on( 'click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click );
	}
}

// =========================================================================================== \\

function ai4seo_toggle_visibility($target, $caret_down, $caret_up, duration = 0) {
	$target = ai4seo_normalize_$( $target );

	if (!ai4seo_exists_$( $target )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$target\" missing in ai4seo_toggle_visibility() \u2014 cannot toggle visibility.' );
		return;
	}

	const is_visible = $target.is( ':visible' );

	const $normalized_caret_down = ai4seo_normalize_$( $caret_down );
	const $normalized_caret_up = ai4seo_normalize_$( $caret_up );

	if (is_visible) {
		// Persist the collapsed state as a class after optional jQuery animation cleanup.
		if (duration > 0) {
			$target.hide(
				duration,
				function () {
					ai4seo_normalize_$( this ).addClass( 'ai4seo-display-none' ).css( 'display', '' );
				}
			);
		} else {
			$target.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
		}

		// Keep both caret states class-driven so server markup and client toggles agree.
		if (ai4seo_exists_$( $normalized_caret_down )) {
			$normalized_caret_down.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
		}

		if (ai4seo_exists_$( $normalized_caret_up )) {
			$normalized_caret_up.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
		}
	} else {
		// Remove the class before animation so CSS-hidden content can become visible.
		$target.removeClass( 'ai4seo-display-none' );

		if (duration > 0) {
			$target.hide().show(
				duration,
				function () {
					ai4seo_normalize_$( this ).css( 'display', '' );
				}
			);
		} else {
			$target.show().css( 'display', '' );
		}

		if (ai4seo_exists_$( $normalized_caret_down )) {
			$normalized_caret_down.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
		}

		if (ai4seo_exists_$( $normalized_caret_up )) {
			$normalized_caret_up.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
		}
	}
}

// =========================================================================================== \\

function ai4seo_toggle_collapsible_section(trigger_element, duration = 0) {
	const $ai4seo_trigger = ai4seo_normalize_$( trigger_element );

	if (!ai4seo_exists_$( $ai4seo_trigger )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"trigger_element\" missing in ai4seo_toggle_collapsible_section() \u2014 cannot toggle collapsible section.' );
		return;
	}

	const ai4seo_target_id = $ai4seo_trigger.attr( 'aria-controls' );
	const ai4seo_target_element = ai4seo_target_id ? document.getElementById( ai4seo_target_id ) : null;
	const $ai4seo_target = ai4seo_target_element ? ai4seo_normalize_$( ai4seo_target_element ) : $ai4seo_trigger.next();
	const ai4seo_will_expand = !$ai4seo_target.is( ':visible' );

	ai4seo_toggle_visibility(
		$ai4seo_target,
		$ai4seo_trigger.find( '.ai4seo-caret-down' ),
		$ai4seo_trigger.find( '.ai4seo-caret-up' ),
		duration
	);

	$ai4seo_trigger.attr( 'aria-expanded', ai4seo_will_expand ? 'true' : 'false' );
}

// =========================================================================================== \\

function ai4seo_handle_collapsible_section_keydown(event, trigger_element, duration = 0) {
	if (!event) {
		return;
	}

	if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar' && event.keyCode !== 13 && event.keyCode !== 32) {
		return;
	}

	event.preventDefault();
	ai4seo_toggle_collapsible_section( trigger_element, duration );
}

// =========================================================================================== \\

function ai4seo_open_get_more_credits_modal() {
	const $modal_schema = ai4seo_normalize_$( '.ai4seo-modal-schemas-container > #ai4seo-modal-schema-get-more-credits' );

	if (ai4seo_exists_$( $modal_schema )) {
		const $modal = ai4seo_open_modal_from_schema( 'get-more-credits', {modal_size: 'small'} );

		if (ai4seo_exists_$( $modal )) {
			ai4seo_init_get_more_credits_modal_animation( $modal );
		}

		return;
	}

	// Click-loaded top-frame bundles do not have pre-rendered schemas, so request only this modal on demand.
	ai4seo_open_ajax_modal(
		'ai4seo_show_get_more_credits_modal',
		{},
		{
			modal_id: 'ai4seo-get-more-credits',
			modal_size: 'small',
			content_loaded_callback: ai4seo_init_get_more_credits_modal_animation,
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_get_more_credits_modal_animation(modal) {
	const $modal = ai4seo_normalize_$( modal );

	if (!ai4seo_exists_$( $modal )) {
		return;
	}

	let $all_items = $modal.find( '.ai4seo-get-more-credits-section' );

	if (!ai4seo_exists_$( $all_items )) {
		console.error( ai4seo_get_plugin_name() + ': elements \"$all_items\" missing in ai4seo_init_get_more_credits_modal_animation() \u2014 credits carousel animation skipped.' );
		return;
	}

	// remove transition and transform -100px to the left.
	$all_items.css( 'transition', 'transform 0s' );
	$all_items.css( 'transform', 'translateX(-100px)' );
	$all_items.css( 'opacity', '0' );

	// go through each item.
	$all_items.each(
        function (index) {
		// Use a block-scoped variable to preserve the value of n.
		const delay = index * 250;
		const $item = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $item )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$item\" missing in ai4seo_init_get_more_credits_modal_animation() \u2014 skipping iteration.' );
			return;
		}

		setTimeout(
            function () {
			$item.css( 'transition', '0.5s ease-in-out' );
			$item.css( 'transform', 'translateX(0)' );
			$item.css( 'opacity', '1' );
            },
            delay
        );
        }
    );
}

// =========================================================================================== \\

function ai4seo_handle_sidebar_outside_click(event) {
	const $sidebar = ai4seo_normalize_$( '.ai4seo-sidebar' );
	const $toggle_button = ai4seo_normalize_$( '.ai4seo-mobile-top-bar-toggle-button' );

	if (!ai4seo_exists_$( '.ai4seo-sidebar' )) {
		console.error( ai4seo_get_plugin_name() + ': selector \".ai4seo-sidebar\" missing in ai4seo_handle_sidebar_outside_click() \u2014 cannot evaluate outside clicks.' );
		return;
	}

	if (!ai4seo_exists_$( $toggle_button )) {
		console.error( ai4seo_get_plugin_name() + ': selector \".ai4seo-mobile-top-bar-toggle-button\" missing in ai4seo_handle_sidebar_outside_click() \u2014 cannot evaluate outside clicks.' );
		return;
	}

	if (!$sidebar.hasClass( 'ai4seo-sidebar-open' )) {
		return;
	}

	if (!$sidebar.is( event.target ) && $sidebar.has( event.target ).length === 0 && !$toggle_button.is( event.target ) && $toggle_button.has( event.target ).length === 0) {
		ai4seo_set_sidebar_open_state( $sidebar, $toggle_button, false );
	}
}

// =========================================================================================== \\

/**
 * Start inactive-button countdowns only for controls in the requested root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_inactive_countdown_buttons(scope = null) {
	// Loop through all elements with class ai4seo-inactive-countdown-button.
	const $inactive_countdown_buttons = ai4seo_get_elements_in_scope_$( '.ai4seo-inactive-countdown-button', scope );

	if (!ai4seo_exists_$( $inactive_countdown_buttons )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': elements \"$inactive_countdown_buttons\" missing in ai4seo_init_inactive_countdown_buttons() \u2014 timers remain disabled.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': Initializing inactive countdown buttons in ai4seo_init_inactive_countdown_buttons().' );

	$inactive_countdown_buttons.each(
        function () {
		const $button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $button )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$button\" missing in ai4seo_init_inactive_countdown_buttons() \u2014 handler binding skipped.' );
			return;
		}

		// check if button has data-time-left attribute.
		let total_seconds = $button.data( 'time-left' );

		if (typeof total_seconds === 'undefined' || !total_seconds || isNaN( total_seconds ) || total_seconds <= 0) {
			return;
		}

		// skip if data-countdown-active attribute is set to true.
		let countdown_active = $button.data( 'countdown-active' );

		if (typeof countdown_active !== 'undefined' && countdown_active) {
			return;
		}

		total_seconds = parseInt( total_seconds );

		// set button to disabled.
		$button.prop( 'disabled', true );

		// add class ai4seo-ignore-during-dashboard-refresh if not already set.
		if (!$button.hasClass( 'ai4seo-ignore-during-dashboard-refresh' )) {
			$button.addClass( 'ai4seo-ignore-during-dashboard-refresh' );
		}

		if (!$button.hasClass( 'ai4seo-inactive-button' )) {
			$button.addClass( 'ai4seo-inactive-button' );
		}

		// add "...{seconds}" to button text.
		let original_button_text = $button.text();
		$button.text( original_button_text + ' (' + total_seconds + 's)' );

		// add data-countdown-active attribute to button.
		$button.data( 'countdown-active', true );

		// start countdown.
		let countdown_interval = setInterval(
            function () {
			total_seconds--;

			if (total_seconds <= 0) {
				clearInterval( countdown_interval );
				$button.prop( 'disabled', false );
				$button.removeClass( 'ai4seo-inactive-button' );
				$button.text( original_button_text );
				$button.removeData( 'time-left' );
				$button.removeData( 'countdown-active' );
				$button.removeClass( 'ai4seo-inactive-countdown-button' );
				return;
			}

			$button.text( original_button_text + ' (' + total_seconds + 's)' );
			$button.data( 'time-left', total_seconds );
            },
            1000
        );
        }
    );
}

// =========================================================================================== \\

/**
 * Returns the stable selector used by an always-mounted AIOSEO generation proxy.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @return {string} Proxy selector.
 */
function ai4seo_get_aioseo_generation_proxy_selector(metadata_identifier) {
	return '.ai4seo-aioseo-generation-proxy[data-ai4seo-aioseo-metadata-identifier="' + metadata_identifier + '"]';
}

// =========================================================================================== \\

/**
 * Returns AIOSEO's canonical hidden post-settings field shared by every visual editor instance.
 *
 * @return {jQuery} First canonical AIOSEO settings field.
 */
function ai4seo_get_aioseo_post_settings_hidden_field_$() {
	return ai4seo_normalize_$( '#aioseo-post-settings' ).first();
}

// =========================================================================================== \\

/**
 * Resolves AIOSEO's Pinia store from one mounted Vue application context.
 *
 * @param {Object|null} app_context Vue application context.
 * @return {Object|null} Validated AIOSEO PostEditorStore or null.
 */
function ai4seo_get_aioseo_post_editor_store_from_app_context(app_context) {
	const provides = app_context && app_context.provides ? app_context.provides : {};

	for (const provider_key of Reflect.ownKeys( provides )) {
		const pinia_provider = provides[provider_key];

		if (!pinia_provider || !pinia_provider._s || typeof pinia_provider._s.get !== 'function') {
			continue;
		}

		const store = pinia_provider._s.get( 'PostEditorStore' );

		if (store && store.$id === 'PostEditorStore'
			&& store.currentPost && typeof store.currentPost === 'object') {
			return store;
		}
	}

	return null;
}

// =========================================================================================== \\

/**
 * Returns AIOSEO's shared reactive post-editor store for a mounted editor instance.
 *
 * AIOSEO does not expose this store publicly, so the adapter discovers its named Pinia store
 * through Vue's mounted application context and validates the expected public store shape.
 *
 * @param {jQuery|HTMLElement|null} scope Optional AIOSEO editor instance.
 * @return {Object|null} AIOSEO PostEditorStore or null when it is not mounted yet.
 */
function ai4seo_get_aioseo_post_editor_store(scope = null) {
	const $scope = ai4seo_normalize_$( scope );
	const $aioseo_roots = ai4seo_exists_$( $scope )
		? $scope.filter( '.aioseo-post-settings' ).add( $scope.find( '.aioseo-post-settings' ) )
		: ai4seo_normalize_$( '.aioseo-post-settings' );

	for (const aioseo_root of $aioseo_roots.toArray()) {
		const app_contexts = new Set();
		let candidate_element = aioseo_root;

		// The Vue app marker is stored on its mount container, while descendants expose a component marker.
		while (candidate_element && candidate_element !== document) {
			if (candidate_element.__vue_app__ && candidate_element.__vue_app__._context) {
				app_contexts.add( candidate_element.__vue_app__._context );
			}

			if (candidate_element.__vueParentComponent && candidate_element.__vueParentComponent.appContext) {
				app_contexts.add( candidate_element.__vueParentComponent.appContext );
			}

			candidate_element = candidate_element.parentElement;
		}

		for (const app_context of app_contexts) {
			const store = ai4seo_get_aioseo_post_editor_store_from_app_context( app_context );

			if (store) {
				return store;
			}
		}
	}

	return null;
}

// =========================================================================================== \\

/**
 * Returns the post ID owned by AIOSEO's mounted reactive editor.
 *
 * @param {jQuery|HTMLElement|null} scope Optional AIOSEO editor instance.
 * @return {number} Post ID or zero when the store is unavailable.
 */
function ai4seo_get_aioseo_post_editor_store_post_id(scope = null) {
	const aioseo_post_editor_store = ai4seo_get_aioseo_post_editor_store( scope );

	return aioseo_post_editor_store ? parseInt( aioseo_post_editor_store.currentPost.id, 10 ) || 0 : 0;
}

// =========================================================================================== \\

/**
 * Updates one field in AIOSEO's reactive editor state, including page-builder modal instances.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @param {string} value Normalized metadata value.
 * @param {jQuery|HTMLElement|null} scope Optional AIOSEO editor instance.
 * @param {boolean} notify_page_builder_change Whether AIOSEO's page-builder bridge should mark the entry dirty.
 * @return {boolean} Whether the reactive store reached the requested value.
 */
function ai4seo_update_aioseo_post_editor_store(metadata_identifier, value, scope = null, notify_page_builder_change = true) {
	const field_details = ai4seo_aioseo_generation_field_details[metadata_identifier] || {};
	const post_settings_key = field_details.post_settings_key || '';
	const store_update_method = field_details.store_update_method || '';
	const aioseo_post_editor_store = ai4seo_get_aioseo_post_editor_store( scope );

	if (!post_settings_key || !aioseo_post_editor_store) {
		return false;
	}

	const value_was_changed = String( aioseo_post_editor_store.currentPost[post_settings_key] ?? '' ) !== value;

	try {
		// Prefer AIOSEO's field action when registered, with direct reactive assignment as the shared fallback.
		if (store_update_method && typeof aioseo_post_editor_store[store_update_method] === 'function') {
			aioseo_post_editor_store[store_update_method]( value );
		} else {
			aioseo_post_editor_store.currentPost[post_settings_key] = value;
		}

		// Populate the native hidden payload immediately; the deep watcher remains the normal follow-up path.
		if (typeof aioseo_post_editor_store.savePostState === 'function') {
			aioseo_post_editor_store.savePostState();
		}

		// Page builders save AIOSEO only after this bridge event marks their document as modified.
		if (value_was_changed && notify_page_builder_change && window.aioseoBus && typeof window.aioseoBus.$emit === 'function') {
			aioseo_post_editor_store.isDirty = true;
			window.aioseoBus.$emit( 'postSettingsUpdated' );
		}
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not update AIOSEO reactive metadata state.', error );
		return false;
	}

	return String( aioseo_post_editor_store.currentPost[post_settings_key] ?? '' ) === value;
}

// =========================================================================================== \\

/**
 * Builds AIOSEO's visual and proxy entries for the shared generation-input registry.
 *
 * Visual entries remain first to preserve the registry order used by existing value collection.
 *
 * @return {Object} Generation input details indexed by selector.
 */
function ai4seo_get_aioseo_generation_input_details() {
	const generation_input_details = {};
	const aioseo_generation_fields = Object.entries( ai4seo_aioseo_generation_field_details );

	// Visual editor entries receive individual Generate with SOOZ buttons.
	for (const [metadata_identifier, field_details] of aioseo_generation_fields) {
		generation_input_details[field_details.visual_editor_selector] = {
			'add_generate_button': true,
			'metadata_source': 'aioseo',
			'metadata_identifier': metadata_identifier,
			'key_by_key': field_details.key_by_key,
			'processing-context': 'metadata',
		};
	}

	// Proxy entries expose unmounted social fields to Generate All without rendering duplicate buttons.
	for (const [metadata_identifier, field_details] of aioseo_generation_fields) {
		generation_input_details[ai4seo_get_aioseo_generation_proxy_selector( metadata_identifier )] = {
			'add_generate_button': false,
			'metadata_source': 'aioseo',
			'metadata_identifier': metadata_identifier,
			'key_by_key': field_details.key_by_key,
			'processing-context': 'metadata',
		};
	}

	return generation_input_details;
}

// =========================================================================================== \\

/**
 * Returns every Yoast metabox field selector that receives an individual SOOZ generation button.
 *
 * @return {string} Selector list used by the root-local field observer.
 */
function ai4seo_get_yoast_generation_mounted_field_selector() {
	return Object.entries( ai4seo_generate_data_for_inputs )
		.filter(
			([selector, field_details]) => field_details.metadata_source === 'yoast'
				&& field_details.add_generate_button !== false
				&& selector.includes( '-metabox' )
		)
		.map( ([selector]) => selector )
		.join( ', ' );
}

// =========================================================================================== \\

/**
 * Initializes already mounted Yoast metabox roots and their delayed React field lifecycle.
 *
 * @param {jQuery|HTMLElement|null} yoast_root Optional discovered Yoast metabox root.
 */
function ai4seo_init_yoast_generation_ui(yoast_root = null) {
	const $provided_root = ai4seo_normalize_$( yoast_root );
	const $yoast_roots = ai4seo_exists_$( $provided_root )
		? $provided_root.filter( ai4seo_yoast_editor_root_selector ).add( $provided_root.find( ai4seo_yoast_editor_root_selector ) )
		: ai4seo_normalize_$( ai4seo_yoast_editor_root_selector );
	const mounted_field_selector = ai4seo_get_yoast_generation_mounted_field_selector();

	$yoast_roots.each(
		function () {
			const yoast_root_element = this;

			ai4seo_observe_generation_editor_fields(
				yoast_root_element,
				mounted_field_selector,
				() => ai4seo_rescan_yoast_generation_ui( yoast_root_element )
			);
			ai4seo_rescan_yoast_generation_ui( yoast_root_element );
		}
	);
}

// =========================================================================================== \\

/**
 * Rebinds SOOZ controls after Yoast mounts or replaces its metabox fields.
 *
 * @param {jQuery|HTMLElement|null} yoast_root Current Yoast metabox root.
 */
function ai4seo_rescan_yoast_generation_ui(yoast_root = null) {
	ai4seo_init_generate_buttons( yoast_root, 'metadata' );
	ai4seo_init_generate_all_buttons( yoast_root, 'metadata' );
}

// =========================================================================================== \\

/**
 * Rebinds generation controls after AIOSEO replaces a lazily mounted editor.
 */
function ai4seo_rescan_aioseo_generation_ui(aioseo_root = null) {
	ai4seo_init_aioseo_generation_ui( aioseo_root );
	ai4seo_init_generate_buttons( aioseo_root );
	ai4seo_init_generate_all_buttons( aioseo_root );
}

// =========================================================================================== \\

/**
 * Returns the server-approved generation editors supported by this bundle.
 *
 * @return {Map<string, Object>} Active integration details in stable server order.
 */
function ai4seo_get_active_generation_editor_integrations() {
	// Treat server localization as the allowlist and discard entries unknown to this bundle version.
	const active_generation_editor_integrations = new Map();
	const localized_generation_editor_integrations = ai4seo_get_localization_parameter( 'ai4seo_active_generation_editor_integrations' );
	const localized_integration_identifiers = Array.isArray( localized_generation_editor_integrations )
		? localized_generation_editor_integrations
		: [];

	// Preserve the stable server order while resolving each identifier to its client-side adapter definition.
	for (const integration_identifier of localized_integration_identifiers) {
		if (ai4seo_generation_editor_integration_definitions.has( integration_identifier )) {
			active_generation_editor_integrations.set(
				integration_identifier,
				ai4seo_generation_editor_integration_definitions.get( integration_identifier )
			);
		}
	}

	return active_generation_editor_integrations;
}

// =========================================================================================== \\

/**
 * Initializes only active reactive SEO editors on a WordPress post-editor screen.
 */
function ai4seo_init_generation_editor_integrations() {
	// Frontend builders share metadata controls but must never start WordPress SEO-editor observers.
	if (!ai4seo_are_external_metadata_generate_buttons_enabled() || !ai4seo_has_asset_context( 'post-editor' )) {
		ai4seo_disconnect_generation_editor_observers();
		return;
	}

	// Resolve the localized allowlist on every dispatcher entry so context changes remove stale state.
	const active_generation_editor_integrations = ai4seo_get_active_generation_editor_integrations();

	// Avoid retaining stale observers when the server reports no supported active integration.
	if (active_generation_editor_integrations.size === 0) {
		ai4seo_disconnect_generation_editor_observers();
		return;
	}

	// Synchronize discovery before adapters inspect already-mounted roots so both mount paths share one registry.
	ai4seo_init_generation_editor_observer_lifecycle();
	ai4seo_sync_generation_editor_discovery_observer( active_generation_editor_integrations );

	// Initialize only the adapters admitted by the server/client registry intersection above.
	for (const integration_details of active_generation_editor_integrations.values()) {
		integration_details.init_generation_ui();
	}
}

// =========================================================================================== \\

/**
 * Checks whether two active-integration registries contain the same identifiers.
 *
 * @param {Map<string, Object>} first_integrations First integration registry.
 * @param {Map<string, Object>} second_integrations Second integration registry.
 * @return {boolean} Whether both registries contain the same identifiers.
 */
function ai4seo_generation_editor_integration_registries_match(first_integrations, second_integrations) {
	// The registry values come from one immutable definition map, so comparing identifiers is sufficient.
	if (first_integrations.size !== second_integrations.size) {
		return false;
	}

	// Key membership is order-independent because adapter initialization uses the newly resolved registry.
	for (const integration_identifier of first_integrations.keys()) {
		if (!second_integrations.has( integration_identifier )) {
			return false;
		}
	}

	return true;
}

// =========================================================================================== \\

/**
 * Checks whether the current document still represents a WordPress post editor.
 *
 * @return {boolean} Whether a post-editor document was recognized.
 */
function ai4seo_is_generation_editor_post_document() {
	// Require both the localized request context and concrete WordPress editor DOM markers.
	if (!ai4seo_has_asset_context( 'post-editor' ) || !document.body) {
		return false;
	}

	return document.body.classList.contains( 'post-php' )
		|| document.body.classList.contains( 'post-new-php' )
		|| document.body.classList.contains( 'block-editor-page' )
		|| document.querySelector( '.block-editor, #poststuff' ) !== null;
}

// =========================================================================================== \\

/**
 * Selects the narrowest stable container that can receive supported editor roots.
 *
 * @return {HTMLElement|null} Discovery-observer target, or null outside a recognized editor.
 */
function ai4seo_get_generation_editor_discovery_observer_target() {
	// Never fall back to a broad container unless the request still identifies as a post editor.
	if (!ai4seo_is_generation_editor_post_document()) {
		return null;
	}

	// Prefer editor-owned roots; wpbody and body exist only for integrations mounted unusually late.
	return document.querySelector( '.block-editor' )
		|| document.querySelector( '#poststuff' )
		|| document.querySelector( '#wpbody-content' )
		|| document.body;
}

// =========================================================================================== \\

/**
 * Selects a stable parent that can observe replacement of the active discovery target.
 *
 * @param {HTMLElement} discovery_target Current discovery-observer target.
 * @return {HTMLElement|null} Stable lifecycle-observer target.
 */
function ai4seo_get_generation_editor_target_lifecycle_observer_target(discovery_target) {
	if (!discovery_target) {
		return null;
	}

	// The direct parent is sufficient to observe replacement of this exact editor shell.
	return discovery_target.parentElement || document.documentElement;
}

// =========================================================================================== \\

/**
 * Synchronizes the narrow lifecycle observer that detects replacement of the discovery target.
 *
 * @param {HTMLElement} discovery_target Current discovery-observer target.
 */
function ai4seo_sync_generation_editor_target_lifecycle_observer(discovery_target) {
	const lifecycle_observer_target = ai4seo_get_generation_editor_target_lifecycle_observer_target( discovery_target );

	if (typeof MutationObserver === 'undefined' || lifecycle_observer_target === null) {
		return;
	}

	const lifecycle_observer_is_current = ai4seo_generation_editor_target_lifecycle_observer !== null
		&& ai4seo_generation_editor_target_lifecycle_observer_target === lifecycle_observer_target
		&& lifecycle_observer_target.isConnected;

	if (lifecycle_observer_is_current) {
		return;
	}

	if (ai4seo_generation_editor_target_lifecycle_observer !== null) {
		ai4seo_generation_editor_target_lifecycle_observer.disconnect();
	}

	ai4seo_generation_editor_target_lifecycle_observer_target = lifecycle_observer_target;
	ai4seo_generation_editor_target_lifecycle_observer = new MutationObserver(
		ai4seo_handle_generation_editor_target_lifecycle_mutations
	);
	// Only direct child-list changes matter; editor-internal subtree activity remains discovery-owned.
	ai4seo_generation_editor_target_lifecycle_observer.observe(
		lifecycle_observer_target,
		{childList: true}
	);
}

// =========================================================================================== \\

/**
 * Detects a removed discovery target or a newly mounted, narrower WordPress editor shell.
 *
 * @param {MutationRecord[]} mutations Parent lifecycle-observer mutation records.
 */
function ai4seo_handle_generation_editor_target_lifecycle_mutations(mutations) {
	const discovery_target = ai4seo_generation_editor_discovery_observer_target;

	if (!discovery_target) {
		return;
	}

	if (!discovery_target.isConnected) {
		ai4seo_schedule_generation_editor_target_resync();
		return;
	}

	// A block editor is already the narrowest target; only its disconnection requires a resync.
	if (discovery_target.matches( '.block-editor' )) {
		return;
	}

	const preferred_target_selector = discovery_target.matches( '#poststuff' )
		? '.block-editor'
		: '.block-editor, #poststuff';

	for (const mutation of mutations) {
		for (const added_node of Array.from( mutation.addedNodes || [] )) {
			if (added_node.nodeType !== 1) {
				continue;
			}

			if (added_node.matches( preferred_target_selector )
				|| added_node.querySelector( preferred_target_selector )) {
				ai4seo_schedule_generation_editor_target_resync();
				return;
			}
		}
	}
}

// =========================================================================================== \\

/**
 * Releases stale observer state and coalesces editor-target rediscovery into one task.
 */
function ai4seo_schedule_generation_editor_target_resync() {
	if (ai4seo_generation_editor_target_resync_timer !== null) {
		return;
	}

	// Stop every producer immediately so detached editor trees cannot enqueue additional rescans.
	ai4seo_disconnect_generation_editor_observers();
	ai4seo_generation_editor_target_resync_timer = window.setTimeout(
		function () {
			ai4seo_generation_editor_target_resync_timer = null;
			ai4seo_init_generation_editor_integrations();
		},
		0
	);
}

// =========================================================================================== \\

/**
 * Registers observer lifecycle handlers once per Window.
 */
function ai4seo_init_generation_editor_observer_lifecycle() {
	// Permanent Window handlers survive observer replacement, so bind them once for the bundle lifetime.
	if (ai4seo_generation_editor_lifecycle_initialized) {
		return;
	}

	// Page exit releases observers, while only a persisted history restore recreates them explicitly.
	window.addEventListener( 'pagehide', ai4seo_disconnect_generation_editor_observers );
	window.addEventListener( 'pageshow', ai4seo_handle_generation_editor_pageshow );
	ai4seo_generation_editor_lifecycle_initialized = true;
}

// =========================================================================================== \\

/**
 * Restores generation-editor observers after a persisted page returns from the back/forward cache.
 *
 * @param {PageTransitionEvent} event Page lifecycle event.
 */
function ai4seo_handle_generation_editor_pageshow(event) {
	// Normal page loads already run the dispatcher; only BFCache restores need explicit reinitialization.
	if (event.persisted) {
		ai4seo_init_generation_editor_integrations();
	}
}

// =========================================================================================== \\

/**
 * Synchronizes the single discovery observer with the active editor registry and DOM target.
 *
 * @param {Map<string, Object>} active_generation_editor_integrations Active editor definitions.
 */
function ai4seo_sync_generation_editor_discovery_observer(active_generation_editor_integrations) {
	// Target selection is repeated on every dispatcher entry because editor shells can be replaced in place.
	const observer_target = ai4seo_get_generation_editor_discovery_observer_target();

	// A missing API or stable editor target invalidates all previously retained observer state.
	if (typeof MutationObserver === 'undefined' || observer_target === null) {
		ai4seo_disconnect_generation_editor_observers();
		return;
	}

	// Reuse the observer only while both its DOM target and server-approved integration set remain current.
	const observer_is_current = ai4seo_generation_editor_discovery_observer !== null
		&& ai4seo_generation_editor_discovery_observer_target === observer_target
		&& observer_target.isConnected
		&& ai4seo_generation_editor_integration_registries_match(
			ai4seo_active_generation_editor_integrations,
			active_generation_editor_integrations
		);

	// Refresh the registry reference even when its identifiers did not require observer replacement.
	if (observer_is_current) {
		ai4seo_active_generation_editor_integrations = active_generation_editor_integrations;
		ai4seo_sync_generation_editor_target_lifecycle_observer( observer_target );
		return;
	}

	// Target or registry changes require a clean root-observer rebuild before discovery resumes.
	ai4seo_disconnect_generation_editor_observers();
	ai4seo_active_generation_editor_integrations = active_generation_editor_integrations;
	ai4seo_generation_editor_discovery_observer_target = observer_target;
	ai4seo_generation_editor_discovery_observer = new MutationObserver( ai4seo_handle_generation_editor_discovery_mutations );
	ai4seo_generation_editor_discovery_observer.observe( observer_target, {childList: true, subtree: true} );
	ai4seo_sync_generation_editor_target_lifecycle_observer( observer_target );
}

// =========================================================================================== \\

/**
 * Collects removed roots and newly mounted roots from one shared mutation batch.
 *
 * @param {MutationRecord[]} mutations Discovery-observer mutation records.
 */
function ai4seo_handle_generation_editor_discovery_mutations(mutations) {
	// Disconnect removed root observers before a reinserted root is considered for this batch.
	for (const mutation of mutations) {
		for (const removed_node of Array.from( mutation.removedNodes || [] )) {
			ai4seo_disconnect_generation_editor_field_observers_in_subtree( removed_node );
		}
	}

	// Scan additions only after all removals are cleaned up so reinserted roots receive fresh observers.
	for (const mutation of mutations) {
		for (const added_node of Array.from( mutation.addedNodes || [] )) {
			ai4seo_collect_generation_editor_roots_from_added_node( added_node );
		}
	}
}

// =========================================================================================== \\

/**
 * Adds active integration roots from one mounted subtree to the shared pending rescan map.
 *
 * @param {Node} added_node Added DOM node.
 */
function ai4seo_collect_generation_editor_roots_from_added_node(added_node) {
	// Text and comment mutations cannot contain integration roots and need no selector work.
	if (!added_node || added_node.nodeType !== 1) {
		return;
	}

	// Match both a directly mounted root and roots nested inside a framework wrapper.
	for (const [integration_identifier, integration_details] of ai4seo_active_generation_editor_integrations) {
		if (added_node.matches( integration_details.root_selector )) {
			ai4seo_schedule_generation_editor_rescan( added_node, integration_identifier );
		}

		for (const generation_editor_root of added_node.querySelectorAll( integration_details.root_selector )) {
			ai4seo_schedule_generation_editor_rescan( generation_editor_root, integration_identifier );
		}
	}
}

// =========================================================================================== \\

/**
 * Queues one editor root for the next shared mutation flush.
 *
 * @param {HTMLElement} generation_editor_root Mounted integration root.
 * @param {string} integration_identifier Active integration identifier.
 */
function ai4seo_schedule_generation_editor_rescan(generation_editor_root, integration_identifier) {
	// Keying by root deduplicates repeated mutation records while the identifier resolves through the current registry.
	ai4seo_pending_generation_editor_rescans.set( generation_editor_root, integration_identifier );

	// One pending timer already covers every root subsequently added to the shared Map.
	if (ai4seo_generation_editor_rescan_timer !== null) {
		return;
	}

	// Let the owning framework complete the current mount before generation controls inspect it.
	ai4seo_generation_editor_rescan_timer = window.setTimeout( ai4seo_flush_generation_editor_rescans, 0 );
}

// =========================================================================================== \\

/**
 * Processes all roots discovered across pending mutation callbacks exactly once.
 */
function ai4seo_flush_generation_editor_rescans() {
	// Reset shared pending state before callbacks run so adapter work can safely schedule another flush.
	const pending_generation_editor_rescans = new Map( ai4seo_pending_generation_editor_rescans );

	ai4seo_pending_generation_editor_rescans.clear();
	ai4seo_generation_editor_rescan_timer = null;

	// Revalidate roots against the live target and registry because navigation can occur before the timer fires.
	for (const [generation_editor_root, integration_identifier] of pending_generation_editor_rescans) {
		const integration_details = ai4seo_active_generation_editor_integrations.get( integration_identifier );

		if (!generation_editor_root.isConnected
			|| !integration_details
			|| !ai4seo_generation_editor_discovery_observer_target
			|| !ai4seo_generation_editor_discovery_observer_target.isConnected
			|| !ai4seo_generation_editor_discovery_observer_target.contains( generation_editor_root )) {
			continue;
		}

		integration_details.rescan_generation_ui( generation_editor_root );
	}
}

// =========================================================================================== \\

/**
 * Disconnects root-local observers and pending rescans owned by a removed subtree.
 *
 * @param {Node} removed_node Removed DOM subtree.
 */
function ai4seo_disconnect_generation_editor_field_observers_in_subtree(removed_node) {
	// Only element subtrees can own an observed integration root.
	if (!removed_node || removed_node.nodeType !== 1) {
		return;
	}

	// Merge observed and pending roots so one containment check cleans every lifecycle registry.
	const generation_editor_roots = new Set( [
		...ai4seo_generation_editor_field_observers.keys(),
		...ai4seo_pending_generation_editor_rescans.keys(),
	] );

	for (const generation_editor_root of generation_editor_roots) {
		if (removed_node === generation_editor_root || removed_node.contains( generation_editor_root )) {
			ai4seo_disconnect_generation_editor_field_observer( generation_editor_root );
			ai4seo_pending_generation_editor_rescans.delete( generation_editor_root );
		}
	}
}

// =========================================================================================== \\

/**
 * Disconnects one root-local observer and cancels its pending debounced rescan.
 *
 * @param {HTMLElement} generation_editor_root Observed editor root.
 */
function ai4seo_disconnect_generation_editor_field_observer(generation_editor_root) {
	// The iterable registry owns both the observer and its cancellable debounce timer.
	const observer_details = ai4seo_generation_editor_field_observers.get( generation_editor_root );

	if (!observer_details) {
		return;
	}

	// Disconnect before clearing the timer so no further mutation batches can enqueue work.
	observer_details.observer.disconnect();

	if (observer_details.rescan_timer !== null) {
		window.clearTimeout( observer_details.rescan_timer );
	}

	ai4seo_generation_editor_field_observers.delete( generation_editor_root );
}

// =========================================================================================== \\

/**
 * Disconnects all generation-editor observers and clears their pending work.
 */
function ai4seo_disconnect_generation_editor_observers() {
	// Discovery must stop before root state is cleared because it is the only producer of new rescans.
	if (ai4seo_generation_editor_discovery_observer !== null) {
		ai4seo_generation_editor_discovery_observer.disconnect();
	}

	if (ai4seo_generation_editor_target_lifecycle_observer !== null) {
		ai4seo_generation_editor_target_lifecycle_observer.disconnect();
	}

	// Reset discovery state so the next dispatcher or persisted pageshow performs a complete synchronization.
	ai4seo_generation_editor_discovery_observer = null;
	ai4seo_generation_editor_discovery_observer_target = null;
	ai4seo_generation_editor_target_lifecycle_observer = null;
	ai4seo_generation_editor_target_lifecycle_observer_target = null;
	ai4seo_active_generation_editor_integrations = new Map();

	// Root-local observers own independent debounce timers and therefore require individual cleanup.
	for (const generation_editor_root of Array.from( ai4seo_generation_editor_field_observers.keys() )) {
		ai4seo_disconnect_generation_editor_field_observer( generation_editor_root );
	}

	// Cancel the shared discovery flush last, after no observer remains able to add pending roots.
	if (ai4seo_generation_editor_rescan_timer !== null) {
		window.clearTimeout( ai4seo_generation_editor_rescan_timer );
	}

	if (ai4seo_generation_editor_target_resync_timer !== null) {
		window.clearTimeout( ai4seo_generation_editor_target_resync_timer );
	}

	ai4seo_generation_editor_rescan_timer = null;
	ai4seo_generation_editor_target_resync_timer = null;
	ai4seo_pending_generation_editor_rescans.clear();
}

// =========================================================================================== \\

/**
 * Observes lazy field mounts inside one external generation editor.
 *
 * @param {HTMLElement} editor_root External editor root element.
 * @param {string} mounted_field_selector Selector identifying a newly mounted supported field.
 * @param {Function} rescan_generation_ui Callback that rebinds controls for this editor.
 */
function ai4seo_observe_generation_editor_fields(editor_root, mounted_field_selector, rescan_generation_ui) {
	// One observer per connected editor root is sufficient because each watches the complete local subtree.
	if (typeof MutationObserver === 'undefined'
		|| !editor_root
		|| ai4seo_generation_editor_field_observers.has( editor_root )
		|| !mounted_field_selector
		|| typeof rescan_generation_ui !== 'function') {
		return;
	}

	// Keep the timer beside its observer so root removal can cancel both through one iterable registry.
	const observer_details = {
		'observer': null,
		'rescan_timer': null,
	};
	observer_details.observer = new MutationObserver(
		function (mutations) {
			// Ignore framework mutations that do not mount any supported generation field.
			const editor_field_was_mounted = mutations.some(
				mutation => Array.from( mutation.addedNodes || [] ).some(
					node => node.nodeType === 1 && (
						node.matches( mounted_field_selector ) || node.querySelector( mounted_field_selector )
					)
				)
			);

			if (!editor_field_was_mounted) {
				return;
			}

			// Restart the root-local debounce so one framework render burst produces one rescan.
			if (observer_details.rescan_timer !== null) {
				window.clearTimeout( observer_details.rescan_timer );
			}

			// Collapse framework subtree batches while preventing detached roots from rescanning later.
			observer_details.rescan_timer = window.setTimeout(
				function () {
					observer_details.rescan_timer = null;

					if (editor_root.isConnected
						&& ai4seo_generation_editor_field_observers.get( editor_root ) === observer_details) {
						rescan_generation_ui();
					}
				},
				50
			);
		}
	);

	// Register only after observation starts; MutationObserver callbacks still run after this stack completes.
	observer_details.observer.observe( editor_root, {childList: true, subtree: true} );
	ai4seo_generation_editor_field_observers.set( editor_root, observer_details );
}

// =========================================================================================== \\

/**
 * Builds document-scoped field entries for a simple third-party generation editor.
 *
 * @param {string} editor_root_selector Editor root selector.
 * @param {string} metadata_source Generation metadata source.
 * @param {Object} generation_field_details Field details indexed by metadata identifier.
 * @return {Object} Generation input details indexed by document-scoped selector.
 */
function ai4seo_get_scoped_third_party_generation_input_details(editor_root_selector, metadata_source, generation_field_details) {
	const generation_input_details = {};

	// Prefix relative selectors so fields with common names cannot match outside their owning editor.
	for (const [metadata_identifier, field_details] of Object.entries( generation_field_details )) {
		const editor_selector = field_details.editor_selector || '';

		if (!editor_selector) {
			continue;
		}

		generation_input_details[editor_root_selector + ' ' + editor_selector] = {
			'add_generate_button': true,
			'metadata_source': metadata_source,
			'metadata_identifier': metadata_identifier,
			'key_by_key': field_details.key_by_key,
			'processing-context': 'metadata',
		};
	}

	return generation_input_details;
}

// =========================================================================================== \\

/**
 * Rebinds generation controls after Slim SEO mounts or refreshes its React fields.
 *
 * @param {HTMLElement} slim_seo_root Slim SEO React root.
 */
function ai4seo_rescan_slim_seo_generation_ui(slim_seo_root) {
	const $slim_seo_metabox = ai4seo_normalize_$( slim_seo_root ).closest( '#slim-seo' );
	const slim_seo_root_element = ai4seo_normalize_$( slim_seo_root )
		.filter( ai4seo_slim_seo_editor_root_selector )
		.get( 0 );

	if (!slim_seo_root_element || !ai4seo_exists_$( $slim_seo_metabox )) {
		return;
	}

	// Derive lazy-field detection from the same registry used by the generation controls.
	const mounted_field_selector = Object.values( ai4seo_slim_seo_generation_field_details )
		.map( field_details => field_details.editor_selector || '' )
		.filter( Boolean )
		.join( ', ' );
	ai4seo_observe_generation_editor_fields(
		slim_seo_root_element,
		mounted_field_selector,
		() => ai4seo_rescan_slim_seo_generation_ui( slim_seo_root_element )
	);

	ai4seo_init_generate_buttons( $slim_seo_metabox );
	ai4seo_init_generate_all_buttons( $slim_seo_metabox );
}

// =========================================================================================== \\

/**
 * Prepares generation controls for Slim SEO's React-rendered post editor.
 *
 * @param {HTMLElement|null} slim_seo_root Optional Slim SEO React root.
 */
function ai4seo_init_slim_seo_generation_ui(slim_seo_root = null) {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	const $slim_seo_roots = slim_seo_root === null
		? ai4seo_normalize_$( ai4seo_slim_seo_editor_root_selector )
		: ai4seo_normalize_$( slim_seo_root ).filter( ai4seo_slim_seo_editor_root_selector );

	// Rescan existing roots; dynamically mounted roots enter through the shared discovery observer.
	$slim_seo_roots.each(
		function () {
			ai4seo_rescan_slim_seo_generation_ui( this );
		}
	);
}

// =========================================================================================== \\

/**
 * Updates Squirrly's tag-style keyword control and its hidden comma-separated input.
 *
 * @param {jQuery|HTMLElement} keyword_input Squirrly keyword input.
 * @param {*} value Generated keyword value.
 * @return {boolean} Whether the keyword value was applied.
 */
function ai4seo_set_squirrly_keywords_content(keyword_input, value) {
	// Normalize API output to the comma-separated representation used by Squirrly's hidden input.
	const $keyword_inputs = ai4seo_normalize_$( keyword_input );
	const normalized_keywords = String( value || '' )
		.split( ',' )
		.map( keyword => keyword.trim() )
		.filter( Boolean )
		.join( ',' );
	let value_was_applied = false;

	if (!ai4seo_exists_$( $keyword_inputs )) {
		return false;
	}

	$keyword_inputs.each(
		function () {
			const $keyword_input = jQuery( this );

			// Squirrly hides this input behind sqtagsinput; use its public API to update both representations.
			if (typeof $keyword_input.sqtagsinput === 'function' && $keyword_input.data( 'tagsinput' )) {
				$keyword_input.sqtagsinput( 'removeAll' );

				if (normalized_keywords) {
					$keyword_input.sqtagsinput( 'add', normalized_keywords );
				}

				value_was_applied = String( $keyword_input.val() || '' ) === normalized_keywords || value_was_applied;
				return;
			}

			value_was_applied = ai4seo_fill_input_without_exec_command( $keyword_input, normalized_keywords ) || value_was_applied;
		}
	);

	return value_was_applied;
}

// =========================================================================================== \\

/**
 * Adds Squirrly's Generate All host above the tab-specific forms.
 *
 * @param {jQuery|HTMLElement} squirrly_root Squirrly snippet root.
 * @return {jQuery} Generate All hook or an empty jQuery object.
 */
function ai4seo_ensure_squirrly_generate_all_hook_$(squirrly_root) {
	// Scope host discovery to the current AJAX-refreshed snippet instance.
	const $squirrly_root = ai4seo_normalize_$( squirrly_root ).first();

	if (!ai4seo_exists_$( $squirrly_root )) {
		return jQuery();
	}

	// Reuse the non-persistent hook when a rescan follows Squirrly's load or save event.
	let $generate_all_hook = $squirrly_root.find( '.ai4seo-squirrly-generate-all-button-hook' ).first();

	if (ai4seo_exists_$( $generate_all_hook )) {
		return $generate_all_hook;
	}

	const $form_container = $squirrly_root
		.find( '.sq_snippet_wrap > .sq-card-body > .sq-d-flex > .sq-tab-content' )
		.first();

	if (!ai4seo_exists_$( $form_container )) {
		return jQuery();
	}

	$generate_all_hook = jQuery(
		'<div class="ai4seo-squirrly-generate-all-button-hook" data-ai4seo-non-persistent="1"></div>'
	);
	$form_container.prepend( $generate_all_hook );

	return $generate_all_hook;
}

// =========================================================================================== \\

/**
 * Rebinds generation controls after Squirrly loads or replaces its snippet editor.
 *
 * @param {HTMLElement|jQuery|null} squirrly_root Squirrly snippet root.
 */
function ai4seo_rescan_squirrly_generation_ui(squirrly_root = null) {
	// Normalize document-observer and delegated-event inputs to the same root representation.
	const $squirrly_root = ai4seo_normalize_$( squirrly_root ).first();

	if (!ai4seo_exists_$( $squirrly_root )) {
		return;
	}

	// Reattach both control types because Squirrly can replace every tab in one refresh.
	ai4seo_ensure_squirrly_generate_all_hook_$( $squirrly_root );
	ai4seo_init_generate_buttons( $squirrly_root );
	ai4seo_init_generate_all_buttons( $squirrly_root );
}

// =========================================================================================== \\

/**
 * Prepares generation controls for Squirrly's AJAX-rendered snippet editor.
 *
 * @param {HTMLElement|null} squirrly_root Optional Squirrly snippet root.
 */
function ai4seo_init_squirrly_generation_ui(squirrly_root = null) {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	// Squirrly keeps the root but replaces its contents after load, refresh, and save.
	jQuery( document )
		.off( 'sq_snippet_loaded.ai4seo-squirrly', ai4seo_squirrly_editor_root_selector )
		.on(
			'sq_snippet_loaded.ai4seo-squirrly',
			ai4seo_squirrly_editor_root_selector,
			function () {
				const this_squirrly_root = this;

				window.setTimeout( () => ai4seo_rescan_squirrly_generation_ui( this_squirrly_root ), 0 );
			}
		);

	// Cover already-mounted roots as well as a specific root delivered by the document observer.
	const $squirrly_roots = squirrly_root === null
		? ai4seo_normalize_$( ai4seo_squirrly_editor_root_selector )
		: ai4seo_normalize_$( squirrly_root ).filter( ai4seo_squirrly_editor_root_selector );

	$squirrly_roots.each(
		function () {
			ai4seo_rescan_squirrly_generation_ui( this );
		}
	);
}

// =========================================================================================== \\

/**
 * Prepares generation controls for AIOSEO's Vue/Quill post editor.
 *
 * AIOSEO unmounts the inactive Facebook or X tab, so hidden proxies represent all six fields
 * for Generate All while pending values are replayed when the corresponding tab is opened.
 */
function ai4seo_init_aioseo_generation_ui(aioseo_root = null) {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	const $aioseo_roots = aioseo_root === null
		? ai4seo_normalize_$( '.aioseo-post-settings' )
		: ai4seo_normalize_$( aioseo_root ).filter( '.aioseo-post-settings' );
	const $aioseo_hidden_field = ai4seo_get_aioseo_post_settings_hidden_field_$();

	if (!ai4seo_exists_$( $aioseo_roots )) {
		return;
	}

	// AIOSEO replaces the social editor DOM when tabs change, so rescan the newly mounted fields.
	ai4seo_normalize_$( document )
		.off( 'click.ai4seo-aioseo-tabs', '.aioseo-post-settings .var-tab' )
		.on(
			'click.ai4seo-aioseo-tabs',
			'.aioseo-post-settings .var-tab',
			function () {
				const this_aioseo_root = jQuery( this ).closest( '.aioseo-post-settings' ).get( 0 );
				setTimeout( () => ai4seo_rescan_aioseo_generation_ui( this_aioseo_root ), 100 );
			}
		);

	$aioseo_roots.each(
		function () {
			ai4seo_init_one_aioseo_generation_root( jQuery( this ) );
		}
	);

	// Merge pending values at the last browser-owned persistence boundary before form serialization.
	const $post_form = ai4seo_exists_$( $aioseo_hidden_field )
		? $aioseo_hidden_field.closest( 'form' )
		: jQuery();

	if (ai4seo_exists_$( $post_form )) {
		$post_form
			.off( 'submit.ai4seo-aioseo-pending' )
			.on( 'submit.ai4seo-aioseo-pending', ai4seo_handle_aioseo_post_form_submit );
	}

	if (ai4seo_has_pending_aioseo_metadata() && !ai4seo_flush_pending_aioseo_metadata_to_hidden_field()) {
		ai4seo_schedule_pending_aioseo_metadata_flush();
	}
}

// =========================================================================================== \\

/**
 * Prepares one AIOSEO editor instance without leaking controls or values into another instance.
 *
 * @param {jQuery|HTMLElement} aioseo_root AIOSEO editor root.
 */
function ai4seo_init_one_aioseo_generation_root(aioseo_root) {
	const $aioseo_root = ai4seo_normalize_$( aioseo_root ).first();
	const aioseo_root_element = $aioseo_root.get( 0 );

	if (!aioseo_root_element) {
		return;
	}

	// Observe lazy editor mounts independently in the metabox and Gutenberg sidebar.
	ai4seo_observe_generation_editor_fields(
		aioseo_root_element,
		'.ql-editor',
		() => ai4seo_rescan_aioseo_generation_ui( aioseo_root_element )
	);

	const $aioseo_active_tab = $aioseo_root.children( '.aioseo-tab' ).first();

	if (!ai4seo_exists_$( $aioseo_active_tab )) {
		return;
	}

	// Each editor instance owns its Generate All host and proxy fields; classes avoid duplicate document IDs.
	let $generate_all_hook = $aioseo_root.find( '.ai4seo-aioseo-generate-all-button-hook' ).first();

	if (!ai4seo_exists_$( $generate_all_hook )) {
		$generate_all_hook = jQuery(
			'<div class="ai4seo-aioseo-generate-all-button-hook" data-ai4seo-non-persistent="1">' +
			'<div class="ai4seo-aioseo-generation-proxies" hidden></div>' +
			'</div>'
		);
	}

	if (!$generate_all_hook.closest( '.aioseo-tab' ).is( $aioseo_active_tab )) {
		$aioseo_active_tab.prepend( $generate_all_hook );
	}

	const $proxy_container = $generate_all_hook.find( '.ai4seo-aioseo-generation-proxies' ).first();
	const aioseo_metadata = ai4seo_read_aioseo_generation_metadata( $aioseo_root );

	// Keep one proxy per configured field synchronized with AIOSEO's hidden payload and mounted visual editor.
	jQuery.each(
		ai4seo_aioseo_generation_field_details,
		function (metadata_identifier, field_details) {
			const proxy_selector = ai4seo_get_aioseo_generation_proxy_selector( metadata_identifier );
			let $proxy = $proxy_container.find( proxy_selector ).first();

			if (!ai4seo_exists_$( $proxy )) {
				$proxy = jQuery( '<input type="hidden" class="ai4seo-aioseo-generation-proxy" data-ai4seo-non-persistent="1" />' )
					.attr( 'data-ai4seo-aioseo-metadata-identifier', metadata_identifier );
				$proxy_container.append( $proxy );
			}

			$proxy.val( aioseo_metadata[metadata_identifier] || '' );

			const visual_selector = field_details.visual_editor_selector || '';
			const $visual_editor = visual_selector
				? ai4seo_normalize_$( visual_selector, $aioseo_root ).first()
				: jQuery();

			if (!ai4seo_exists_$( $visual_editor )) {
				return;
			}

			// Native AIOSEO edits must supersede values generated while this field was unmounted.
			$visual_editor
				.off( 'input.ai4seo-aioseo-pending' )
				.on(
					'input.ai4seo-aioseo-pending',
					function () {
						if (!ai4seo_is_applying_metadata_to_live_aioseo_editor) {
							delete ai4seo_aioseo_pending_metadata[metadata_identifier];
						}
					}
				);
		}
	);

	// Replay values generated while this root's social tab was unmounted.
	jQuery.each(
		{...ai4seo_aioseo_pending_metadata},
		function (metadata_identifier, metadata_value) {
			ai4seo_set_aioseo_metadata_content( metadata_identifier, metadata_value, $aioseo_root );
		}
	);
}

// =========================================================================================== \\

/**
 * Returns the stable selector used by an always-mounted SEOPress generation proxy.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @return {string} Proxy selector.
 */
function ai4seo_get_seopress_generation_proxy_selector(metadata_identifier) {
	return '.ai4seo-seopress-generation-proxy[data-ai4seo-seopress-metadata-identifier="' + metadata_identifier + '"]';
}

// =========================================================================================== \\

/**
 * Expands one SEOPress field selector into its document-scoped universal-editor selector.
 *
 * Runtime reads use the relative selector inside one root, while the generation registry needs
 * a full selector that cannot match similarly named WordPress fields outside SEOPress.
 *
 * @param {Object} field_details SEOPress generation field details.
 * @return {string} Document-scoped selector or an empty string.
 */
function ai4seo_get_seopress_universal_editor_selector(field_details) {
	const field_selector = field_details.universal_editor_field_selector || '';

	return field_selector ? ai4seo_seopress_editor_root_selector + ' ' + field_selector : '';
}

// =========================================================================================== \\

/**
 * Builds SEOPress's universal, classic, and proxy entries for the generation-input registry.
 *
 * @return {Object} Generation input details indexed by selector.
 */
function ai4seo_get_seopress_generation_input_details() {
	const generation_input_details = {};
	const seopress_generation_fields = Object.entries( ai4seo_seopress_generation_field_details );

	// Universal and classic editors receive their own buttons while retaining distinct state adapters.
	for (const [metadata_identifier, field_details] of seopress_generation_fields) {
		const field_generation_details = {
			'add_generate_button': true,
			'metadata_identifier': metadata_identifier,
			'key_by_key': field_details.key_by_key,
			'processing-context': 'metadata',
		};

		generation_input_details[ai4seo_get_seopress_universal_editor_selector( field_details )] = {
			...field_generation_details,
			'metadata_source': 'seopress',
		};
		generation_input_details[field_details.classic_editor_selector] = {
			...field_generation_details,
			'metadata_source': 'seopress-classic',
		};
	}

	// Proxies expose fields from tabs React has not mounted without receiving duplicate buttons.
	for (const [metadata_identifier, field_details] of seopress_generation_fields) {
		generation_input_details[ai4seo_get_seopress_generation_proxy_selector( metadata_identifier )] = {
			'add_generate_button': false,
			'metadata_source': 'seopress',
			'metadata_identifier': metadata_identifier,
			'key_by_key': field_details.key_by_key,
			'processing-context': 'metadata',
		};
	}

	return generation_input_details;
}

// =========================================================================================== \\

/**
 * Reads SEOPress metadata from localized postmeta, proxies, and currently mounted React fields.
 *
 * @param {jQuery|HTMLElement|null} scope Optional SEOPress editor root.
 * @return {Object} Metadata values indexed by SOOZ identifier.
 */
function ai4seo_read_seopress_generation_metadata(scope = null) {
	const localized_metadata = ai4seo_get_localization_parameter( 'ai4seo_seopress_generation_metadata' );
	const seopress_metadata = localized_metadata && typeof localized_metadata === 'object'
		? {...localized_metadata}
		: {};
	const $scope = ai4seo_normalize_$( scope );

	// Pending generations win over mounted editors, which in turn win over stale localized postmeta.
	jQuery.each(
		ai4seo_seopress_generation_field_details,
		function (metadata_identifier, field_details) {
			const $proxy = ai4seo_normalize_$( ai4seo_get_seopress_generation_proxy_selector( metadata_identifier ), $scope ).first();
			const $visual_editor = ai4seo_normalize_$( field_details.universal_editor_field_selector, $scope ).first();

			if (Object.prototype.hasOwnProperty.call( ai4seo_seopress_pending_metadata, metadata_identifier )) {
				seopress_metadata[metadata_identifier] = ai4seo_seopress_pending_metadata[metadata_identifier];
			} else if (ai4seo_exists_$( $visual_editor )) {
				seopress_metadata[metadata_identifier] = ai4seo_get_input_value( $visual_editor );
			} else if (ai4seo_exists_$( $proxy )) {
				seopress_metadata[metadata_identifier] = ai4seo_get_input_value( $proxy );
			}

			seopress_metadata[metadata_identifier] = String( seopress_metadata[metadata_identifier] || '' );
		}
	);

	return seopress_metadata;
}

// =========================================================================================== \\

/**
 * Updates a React-controlled input through its native value setter and bubbling events.
 *
 * @param {jQuery|HTMLElement} input React-controlled input or textarea.
 * @param {string} value New value.
 * @return {boolean} Whether an input was updated.
 */
function ai4seo_set_react_controlled_input_value(input, value) {
	const $input = ai4seo_normalize_$( input ).first();
	const input_element = $input.get( 0 );

	if (!input_element) {
		return false;
	}

	// React tracks the native value setter, so direct jQuery assignment alone can leave Formik stale.
	const input_prototype = Object.getPrototypeOf( input_element );
	const value_descriptor = input_prototype
		? Object.getOwnPropertyDescriptor( input_prototype, 'value' )
		: null;

	if (value_descriptor && typeof value_descriptor.set === 'function') {
		value_descriptor.set.call( input_element, value );
	} else {
		input_element.value = value;
	}

	// Bubble the same event set used by the general input filler so counters and dirty state also refresh.
	['input', 'change', 'keyup'].forEach(
		function (event_name) {
			input_element.dispatchEvent( new Event( event_name, {bubbles: true} ) );
		}
	);

	return String( input_element.value || '' ) === value;
}

// =========================================================================================== \\

/**
 * Updates one SEOPress field, retaining the value for a lazily mounted tab when necessary.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @param {*} value Generated metadata value.
 * @param {jQuery|HTMLElement|null} scope Optional SEOPress editor root.
 * @return {boolean} Whether the value reached a proxy or mounted field.
 */
function ai4seo_set_seopress_metadata_content(metadata_identifier, value, scope = null) {
	const field_details = ai4seo_seopress_generation_field_details[metadata_identifier] || {};
	const universal_editor_field_selector = field_details.universal_editor_field_selector || '';
	const normalized_value = String( value || '' );
	const $scope = ai4seo_normalize_$( scope );

	if (!universal_editor_field_selector) {
		return false;
	}

	// Retain the generated value until every lazily mounted representation has received it.
	ai4seo_seopress_pending_metadata[metadata_identifier] = normalized_value;

	const $proxy = ai4seo_normalize_$( ai4seo_get_seopress_generation_proxy_selector( metadata_identifier ), $scope ).first();
	const $visual_editors = ai4seo_normalize_$( universal_editor_field_selector, $scope );
	let value_was_applied = false;

	// The hidden proxy keeps Generate All state available while the owning social tab is absent.
	if (ai4seo_exists_$( $proxy )) {
		$proxy.val( normalized_value );
		value_was_applied = true;
	}

	// Suppress the manual-edit listener while bubbling React's controlled-input events.
	ai4seo_is_applying_metadata_to_live_seopress_editor = true;

	try {
		$visual_editors.each(
			function () {
				value_was_applied = ai4seo_set_react_controlled_input_value( this, normalized_value ) || value_was_applied;
			}
		);
	} finally {
		ai4seo_is_applying_metadata_to_live_seopress_editor = false;
	}

	return value_was_applied;
}

// =========================================================================================== \\

/**
 * Returns the universal SEOPress root associated with one generation or save control.
 *
 * @param {jQuery|HTMLElement|null} scope A control or editor root.
 * @return {HTMLElement|null} Universal SEOPress root element.
 */
function ai4seo_get_seopress_generation_root_element(scope = null) {
	const $scope = ai4seo_normalize_$( scope ).first();

	if (!ai4seo_exists_$( $scope )) {
		return null;
	}

	const $seopress_root = $scope.is( ai4seo_seopress_editor_root_selector )
		? $scope
		: $scope.closest( ai4seo_seopress_editor_root_selector );

	return $seopress_root.get( 0 ) || null;
}

// =========================================================================================== \\

/**
 * Records fields changed by a successful SEOPress Generate All action.
 *
 * Individual generation buttons stay owned by the currently visible SEOPress section. Only a
 * cross-tab Generate All action needs the save bridge because the other section is not submitted.
 *
 * @param {jQuery|HTMLElement} generate_button Successful generation control.
 * @param {jQuery|HTMLElement|null} scope Closest generation editor container.
 * @param {Object} generated_data Successful generated fields.
 */
function ai4seo_track_seopress_generate_all_metadata(generate_button, scope, generated_data = {}) {
	const $generate_button = ai4seo_normalize_$( generate_button ).first();
	const seopress_root_element = ai4seo_get_seopress_generation_root_element( scope );

	if (!seopress_root_element
		|| !$generate_button.hasClass( 'ai4seo-generate-all-button' )
		|| !$generate_button.closest( '.ai4seo-seopress-generate-all-button-hook' ).length
		|| !generated_data
		|| typeof generated_data !== 'object') {
		return;
	}

	const pending_metadata_identifiers = ai4seo_seopress_pending_generate_all_metadata_by_root.get( seopress_root_element ) || new Set();

	Object.keys( generated_data ).forEach(
		function (metadata_identifier) {
			if (Object.prototype.hasOwnProperty.call( ai4seo_seopress_generation_field_details, metadata_identifier )) {
				pending_metadata_identifiers.add( metadata_identifier );
			}
		}
	);

	if (pending_metadata_identifiers.size) {
		ai4seo_seopress_pending_generate_all_metadata_by_root.set( seopress_root_element, pending_metadata_identifiers );
	}
}

// =========================================================================================== \\

/**
 * Removes one manually superseded field from the current SEOPress Generate All transaction.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @param {HTMLElement|null} seopress_root_element Universal SEOPress root element.
 */
function ai4seo_remove_seopress_pending_generate_all_metadata(metadata_identifier, seopress_root_element) {
	const pending_metadata_identifiers = seopress_root_element
		? ai4seo_seopress_pending_generate_all_metadata_by_root.get( seopress_root_element )
		: null;

	if (!pending_metadata_identifiers) {
		return;
	}

	pending_metadata_identifiers.delete( metadata_identifier );

	if (!pending_metadata_identifiers.size) {
		ai4seo_seopress_pending_generate_all_metadata_by_root.delete( seopress_root_element );
	}
}

// =========================================================================================== \\

/**
 * Determines which SEOPress REST form owns one native Save button.
 *
 * @param {jQuery|HTMLElement} save_button Native SEOPress Save button.
 * @return {string} Save group identifier or an empty string.
 */
function ai4seo_get_seopress_save_group(save_button) {
	const $save_panel = ai4seo_normalize_$( save_button ).first().closest( '[tabindex="0"]' );

	if (!ai4seo_exists_$( $save_panel )) {
		return '';
	}

	for (const field_details of Object.values( ai4seo_seopress_generation_field_details )) {
		if (field_details.save_group
			&& ai4seo_exists_$( ai4seo_normalize_$( field_details.universal_editor_field_selector, $save_panel ) )) {
			return field_details.save_group;
		}
	}

	return '';
}

// =========================================================================================== \\

/**
 * Persists pending Generate All fields from the non-visible SEOPress section.
 *
 * The native button still submits its own visible Formik form. This bridge uses SEOPress's
 * official REST endpoint only for generated fields whose owning section is not part of that form.
 *
 * @param {jQuery|HTMLElement} save_button Native SEOPress Save button.
 * @return {Promise<void>|null} Active bridge request or null when no bridge is required.
 */
function ai4seo_bridge_pending_seopress_generate_all_metadata(save_button) {
	const $save_button = ai4seo_normalize_$( save_button ).first();
	const seopress_root_element = ai4seo_get_seopress_generation_root_element( $save_button );
	const current_save_group = ai4seo_get_seopress_save_group( $save_button );
	const pending_metadata_identifiers = seopress_root_element
		? ai4seo_seopress_pending_generate_all_metadata_by_root.get( seopress_root_element )
		: null;

	if (!seopress_root_element || !current_save_group || !pending_metadata_identifiers) {
		return null;
	}

	// The clicked form owns its visible fields; SEOPress itself reports a failure for that request.
	for (const metadata_identifier of Array.from( pending_metadata_identifiers )) {
		const field_details = ai4seo_seopress_generation_field_details[metadata_identifier] || {};

		if (field_details.save_group === current_save_group) {
			ai4seo_remove_seopress_pending_generate_all_metadata( metadata_identifier, seopress_root_element );
		}
	}

	// Only fields owned by the other form require an additional request from this explicit save action.
	const target_save_group = current_save_group === 'title-description' ? 'social' : 'title-description';
	const target_metadata_identifiers = Array.from( pending_metadata_identifiers ).filter(
		metadata_identifier => (ai4seo_seopress_generation_field_details[metadata_identifier] || {}).save_group === target_save_group
	);

	if (!target_metadata_identifiers.length) {
		return null;
	}

	// Reuse the active promise so repeated clicks cannot create duplicate cross-section writes.
	const active_bridge_request = ai4seo_seopress_save_bridge_requests_by_root.get( seopress_root_element );

	if (active_bridge_request) {
		return active_bridge_request;
	}

	// Build the request entirely from SEOPress's localized REST contract and the exact pending snapshot.
	const seopress_data = typeof SEOPRESS_DATA === 'object' && SEOPRESS_DATA ? SEOPRESS_DATA : {};
	const rest_url = typeof seopress_data.REST_URL === 'string' ? seopress_data.REST_URL : '';
	const nonce = typeof seopress_data.NONCE === 'string' ? seopress_data.NONCE : '';
	const post_id = Number.parseInt( seopress_data.POST_ID, 10 );
	const endpoint_suffix = target_save_group === 'social' ? 'social-settings' : 'title-description-metas';
	const metadata_snapshot = {};
	const request_payload = {};

	target_metadata_identifiers.forEach(
		function (metadata_identifier) {
			const field_details = ai4seo_seopress_generation_field_details[metadata_identifier] || {};
			const metadata_value = String( ai4seo_seopress_pending_metadata[metadata_identifier] || '' );

			metadata_snapshot[metadata_identifier] = metadata_value;
			request_payload[field_details.save_parameter] = metadata_value;
		}
	);

	// Keep request ownership in the root-specific WeakMap until success or failure has been handled.
	const bridge_request = (async function () {
		// Yield once so synchronous validation cannot clean up before this promise is registered below.
		await Promise.resolve();

		try {
			if (!rest_url || !nonce || !Number.isInteger( post_id ) || post_id <= 0 || typeof window.fetch !== 'function') {
				throw new Error( 'SEOPress REST configuration is unavailable.' );
			}

			const bridge_response = await window.fetch(
				rest_url + 'seopress/v1/posts/' + encodeURIComponent( post_id ) + '/' + endpoint_suffix,
				{
					method: 'PUT',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify( request_payload ),
				}
			);

			if (!bridge_response.ok) {
				throw new Error( 'SEOPress save bridge failed (' + bridge_response.status + ').' );
			}

			// Clear only the exact values confirmed by this request; a newer generation stays pending.
			target_metadata_identifiers.forEach(
				function (metadata_identifier) {
					if (ai4seo_seopress_pending_metadata[metadata_identifier] === metadata_snapshot[metadata_identifier]) {
						delete ai4seo_seopress_pending_metadata[metadata_identifier];
						ai4seo_remove_seopress_pending_generate_all_metadata( metadata_identifier, seopress_root_element );
					}
				}
			);
		} catch (error) {
			const target_section_name = target_save_group === 'social'
				? wp.i18n.__( 'Social', 'ai-for-seo' )
				: wp.i18n.__( 'Titles & Metas', 'ai-for-seo' );

			ai4seo_console_debug( error );
			ai4seo_show_warning_toast(
				wp.i18n.sprintf(
					/* translators: %s: SEOPress section name. */
					wp.i18n.__( 'SOOZ could not save the generated %s fields with this SEOPress save. The values remain in the editor; open that section and save again.', 'ai-for-seo' ),
					target_section_name
				)
			);
		} finally {
			ai4seo_seopress_save_bridge_requests_by_root.delete( seopress_root_element );
		}
	})();

	ai4seo_seopress_save_bridge_requests_by_root.set( seopress_root_element, bridge_request );

	return bridge_request;
}

// =========================================================================================== \\

/**
 * Rebinds controls after SEOPress mounts or restores one cached React tab.
 *
 * @param {jQuery|HTMLElement|null} seopress_root Optional SEOPress editor root.
 */
function ai4seo_rescan_seopress_generation_ui(seopress_root = null) {
	ai4seo_init_seopress_generation_ui( seopress_root );
	ai4seo_init_generate_buttons( seopress_root );
	ai4seo_init_generate_all_buttons( seopress_root );
}

// =========================================================================================== \\

/**
 * Prepares generation controls for SEOPress's lazy universal metabox tabs.
 *
 * @param {jQuery|HTMLElement|null} seopress_root Optional SEOPress editor root.
 */
function ai4seo_init_seopress_generation_ui(seopress_root = null) {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	const $seopress_roots = seopress_root === null
		? ai4seo_normalize_$( ai4seo_seopress_editor_root_selector )
		: ai4seo_normalize_$( seopress_root ).filter( ai4seo_seopress_editor_root_selector );

	$seopress_roots.each(
		function () {
			ai4seo_init_one_seopress_generation_root( this );
		}
	);
}

// =========================================================================================== \\

/**
 * Prepares one SEOPress editor instance and its six always-mounted Generate All proxies.
 *
 * @param {jQuery|HTMLElement} seopress_root SEOPress universal metabox root.
 */
function ai4seo_init_one_seopress_generation_root(seopress_root) {
	const $seopress_root = ai4seo_normalize_$( seopress_root ).first();
	const seopress_root_element = $seopress_root.get( 0 );

	if (!seopress_root_element) {
		return;
	}

	// A delegated handler survives SEOPress's lazy tab mounts and only reacts to its native save rows.
	$seopress_root
		.off( 'click.ai4seo-seopress-save-bridge', '.sp-fixed button' )
		.on(
			'click.ai4seo-seopress-save-bridge',
			'.sp-fixed button',
			function () {
				ai4seo_bridge_pending_seopress_generate_all_metadata( this );
			}
		);

	// Derive mount detection from the field registry so future fields cannot miss lazy initialization.
	const mounted_field_selector = Object.values( ai4seo_seopress_generation_field_details )
		.map( field_details => field_details.universal_editor_field_selector )
		.filter( Boolean )
		.join( ', ' );
	ai4seo_observe_generation_editor_fields(
		seopress_root_element,
		mounted_field_selector,
		() => ai4seo_rescan_seopress_generation_ui( seopress_root_element )
	);

	const $seopress_main = $seopress_root.find( '.sp-main' ).first();

	if (!ai4seo_exists_$( $seopress_main )) {
		return;
	}

	// The hook sits outside SEOPress's cached tab panels so one Generate All action stays visible.
	let $generate_all_hook = $seopress_root.find( '.ai4seo-seopress-generate-all-button-hook' ).first();

	if (!ai4seo_exists_$( $generate_all_hook )) {
		$generate_all_hook = jQuery(
			'<div class="ai4seo-seopress-generate-all-button-hook" data-ai4seo-non-persistent="1">' +
			'<div class="ai4seo-seopress-generation-proxies" hidden></div>' +
			'</div>'
		);
	}

	if (!$generate_all_hook.parent().is( $seopress_main )) {
		$seopress_main.prepend( $generate_all_hook );
	}

	const $proxy_container = $generate_all_hook.find( '.ai4seo-seopress-generation-proxies' ).first();
	const seopress_metadata = ai4seo_read_seopress_generation_metadata( $seopress_root );

	// Synchronize each proxy with mounted fields and let subsequent manual edits supersede pending generations.
	jQuery.each(
		ai4seo_seopress_generation_field_details,
		function (metadata_identifier, field_details) {
			const proxy_selector = ai4seo_get_seopress_generation_proxy_selector( metadata_identifier );
			let $proxy = $proxy_container.find( proxy_selector ).first();

			if (!ai4seo_exists_$( $proxy )) {
				$proxy = jQuery( '<input type="hidden" class="ai4seo-seopress-generation-proxy" data-ai4seo-non-persistent="1" />' )
					.attr( 'data-ai4seo-seopress-metadata-identifier', metadata_identifier );
				$proxy_container.append( $proxy );
			}

			$proxy.val( seopress_metadata[metadata_identifier] || '' );

			const $visual_editor = ai4seo_normalize_$( field_details.universal_editor_field_selector, $seopress_root ).first();

			if (!ai4seo_exists_$( $visual_editor )) {
				return;
			}

			$visual_editor
				.off( 'input.ai4seo-seopress-pending' )
				.on(
					'input.ai4seo-seopress-pending',
					function () {
						if (ai4seo_is_applying_metadata_to_live_seopress_editor) {
							return;
						}

						delete ai4seo_seopress_pending_metadata[metadata_identifier];
						ai4seo_remove_seopress_pending_generate_all_metadata( metadata_identifier, seopress_root_element );
						$proxy.val( ai4seo_get_input_value( jQuery( this ) ) );
					}
				);
		}
	);

	// Replay generated values when a previously unvisited tab becomes available.
	jQuery.each(
		{...ai4seo_seopress_pending_metadata},
		function (metadata_identifier, metadata_value) {
			ai4seo_set_seopress_metadata_content( metadata_identifier, metadata_value, $seopress_root );
		}
	);
}

// =========================================================================================== \\

/**
 * Initializes individual generation buttons inside the requested editor scope.
 *
 * @param {jQuery|HTMLElement|null} scope Optional editor instance.
 * @param {string|null} processing_context Optional metadata or attachment-attributes filter.
 */
function ai4seo_init_generate_buttons(scope = null, processing_context = null) {
	// Check if current page is attachment-page.
	if (ai4seo_is_attachment_post_type()) {
		// Stop script if the current attachment doesn't contain supported mime type.
		if (!ai4seo_is_attachment_mime_type_supported()) {
			return;
		}
	}

	const active_meta_tags = ai4seo_get_active_meta_tags();
	const active_attachment_attributes = ai4seo_get_active_attachment_attributes();

	// Loop through mapping and call function to add button-element.
	jQuery.each(
		ai4seo_generate_data_for_inputs,
		function (this_generate_data_for_input_selector, this_generate_data_for_input_details) {
			// Click-driven integration passes limit generation controls to the affected field family.
			if (processing_context !== null && this_generate_data_for_input_details['processing-context'] !== processing_context) {
				return;
			}

			if (!ai4seo_should_render_generate_button_for_selector( this_generate_data_for_input_selector, this_generate_data_for_input_details )) {
				return;
			}

			const $this_generate_data_for_inputs = ai4seo_get_elements_in_scope_$( this_generate_data_for_input_selector, scope );

			// Keep the registry selector intact while resolving every matching editor instance.
			if (!ai4seo_exists_$( $this_generate_data_for_inputs )) {
				return;
			}

			// Skip registered metadata and attachment fields that are inactive in SOOZ.
			if (typeof this_generate_data_for_input_details.metadata_identifier !== 'undefined' && this_generate_data_for_input_details.metadata_identifier) {
				if (!active_meta_tags.includes( this_generate_data_for_input_details.metadata_identifier )) {
					return;
				}
			}

			if (typeof this_generate_data_for_input_details.attachment_attributes_identifier !== 'undefined' && this_generate_data_for_input_details.attachment_attributes_identifier) {
				if (!active_attachment_attributes.includes( this_generate_data_for_input_details.attachment_attributes_identifier )) {
					return;
				}
			}

			// Respect proxy entries that participate in Generate All without receiving an individual button.
			if (typeof this_generate_data_for_input_details.add_generate_button !== 'undefined' && this_generate_data_for_input_details.add_generate_button === false) {
				return;
			}

			// Bind and inject each match independently so duplicate editor instances retain their own scope.
			$this_generate_data_for_inputs.each(
				function () {
					const $this_generate_data_for_input = jQuery( this );
					const debounced_generate_all_button_init = ai4seo_debounce(
						function () {
							ai4seo_init_generate_all_buttons( scope, processing_context );
						},
						150
					);

					$this_generate_data_for_input
						.off( 'input.ai4seo-generate-button-injection' )
						.on(
							'input.ai4seo-generate-button-injection',
							debounced_generate_all_button_init
						);

					ai4seo_try_add_generate_button_to_input( $this_generate_data_for_input, this_generate_data_for_input_selector );
				}
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Bind live character feedback to every metadata field that has an active quality window.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_metadata_editor_length_feedback(scope = null) {
	// AJAX modal refreshes can replace the editor markup, so bind the current fields on every initialization.
	const $length_tracked_inputs = ai4seo_get_elements_in_scope_$( '.ai4seo-editor-length-tracked', scope )
		.add( ai4seo_get_elements_in_scope_$( ai4seo_editor_length_feedback_input_selector, scope ) );

	if (!ai4seo_exists_$( $length_tracked_inputs )) {
		return;
	}

	// Namespace and replace listeners because generic modal initialization may run repeatedly.
	$length_tracked_inputs
		.off( 'input.ai4seo-editor-length-feedback change.ai4seo-editor-length-feedback' )
		.on(
			'input.ai4seo-editor-length-feedback change.ai4seo-editor-length-feedback',
			function () {
				ai4seo_refresh_metadata_editor_length_feedback( this );
			}
		);

	// Synchronize server-rendered values before the user interacts with the editor.
	ai4seo_refresh_metadata_editor_length_feedback( $length_tracked_inputs );
}

// =========================================================================================== \\

/**
 * Resolve a metadata/attachment field identifier from a rendered editor input element.
 *
 * @param {HTMLElement} input_element
 * @return {string}
 */
function ai4seo_get_editor_field_identifier_from_input(input_element) {
	const input_id = String( input_element.id || '' );

	if (input_id.startsWith( 'ai4seo_metadata_' )) {
		return input_id.substring( 'ai4seo_metadata_'.length );
	}

	if (input_id.startsWith( 'ai4seo_attachment_attribute_' )) {
		return input_id.substring( 'ai4seo_attachment_attribute_'.length );
	}

	return '';
}

/**
 * Resolve the matching editor field label for messages and fit guidance.
 *
 * @param {HTMLElement} input_element
 * @return {string}
 */
function ai4seo_get_editor_field_label_from_input(input_element) {
	const form_item = input_element.closest( '.ai4seo-form-item' );
	const label = form_item ? form_item.querySelector( '.ai4seo-form-item-label label' ) : null;

	if (label) {
		return label.textContent.trim();
	}

	return '';
}

/**
 * Refresh character counts and target-range states for shared editor form items.
 *
 * @param {jQuery|HTMLElement|string} scope Length-tracked input or containing editor element.
 */
function ai4seo_refresh_metadata_editor_length_feedback(scope) {
	const $scope = ai4seo_normalize_$( scope );

	if (!ai4seo_exists_$( $scope )) {
		return;
	}

	let $editor_inputs = $scope.is( '.ai4seo-editor-length-tracked' )
		? $scope
		: $scope.find( '.ai4seo-editor-length-tracked' );

	// Include every supported editor field because the server context may provide a target even when
	// legacy PHP markup did not add the explicit tracker class to that individual input.
	const $supported_inputs = $scope.is( ai4seo_editor_length_feedback_input_selector )
		? $scope
		: $scope.find( ai4seo_editor_length_feedback_input_selector );

	$editor_inputs = $editor_inputs.add( $supported_inputs );

	if (!ai4seo_exists_$( $editor_inputs )) {
		return;
	}

	$editor_inputs.each(
		function () {
			// Resolve each field's identifier and context-specific target before updating its live output block.
			const $input = ai4seo_normalize_$( this );
			const input_element = $input.get( 0 );
			const field_identifier = ai4seo_get_editor_field_identifier_from_input( input_element );

			if (!field_identifier) {
				return;
			}

			const $workspace = $input.closest( '.ai4seo-editor-workspace' ).first();
			if (!ai4seo_exists_$( $workspace )) {
				return;
			}

			const context = ai4seo_get_editor_preview_context( $workspace.get( 0 ) );
			const input_value = String( ai4seo_get_input_value( $input ) || '' );
			const field_label = ai4seo_get_editor_field_label_from_input( input_element ) || field_identifier;
			const evaluation_details = ai4seo_get_editor_field_evaluation_details(
				context,
				field_identifier,
				field_label,
				input_value
			);

			ai4seo_update_editor_preview_evaluation(
				$workspace.get( 0 ),
				field_identifier,
				evaluation_details.count_text,
				evaluation_details.target_text,
				[],
				evaluation_details.fit_feedback
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Initialize Preview and Editor mode switches inside dynamically loaded entry editors.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_editor_view_modes(scope = null) {
	const $workspaces = ai4seo_get_elements_in_scope_$( '.ai4seo-editor-workspace', scope );

	if (!ai4seo_exists_$( $workspaces )) {
		return;
	}

	// Each AJAX-loaded workspace resolves its initial mode from page-session state before the saved default.
	$workspaces.each(
		function () {
			const $workspace = ai4seo_normalize_$( this );
			const editor_context = String( $workspace.attr( 'data-ai4seo-editor-context' ) || '' );
			const configured_mode = String( $workspace.attr( 'data-ai4seo-default-view-mode' ) || 'preview' );
			const initial_mode = ai4seo_editor_runtime_view_modes[ editor_context ] || configured_mode;

			// Namespace handlers because modal initialization can run repeatedly after generation or navigation.
			$workspace.find( '.ai4seo-editor-mode-switch-button' )
				.off( 'click.ai4seo-editor-view-mode' )
				.on(
					'click.ai4seo-editor-view-mode',
					function () {
						ai4seo_set_editor_view_mode( $workspace, String( this.getAttribute( 'data-ai4seo-editor-view-mode' ) || '' ) );
					}
				);

			// Route initial activation through the public setter so ARIA and panel state share one path.
			ai4seo_set_editor_view_mode( $workspace, initial_mode );
		}
	);
}

// =========================================================================================== \\

/**
 * Activate one editor workspace mode without disabling or removing any form controls.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @param {string} view_mode
 */
function ai4seo_set_editor_view_mode(workspace, view_mode) {
	const $workspace = ai4seo_normalize_$( workspace ).first();

	if (!ai4seo_exists_$( $workspace )) {
		return;
	}

	// Invalid client input falls back to Preview, matching the declared fresh-install default.
	if (view_mode !== 'preview' && view_mode !== 'editor') {
		view_mode = 'preview';
	}

	const editor_context = String( $workspace.attr( 'data-ai4seo-editor-context' ) || '' );

	// Store a separate page-session choice for each editor type without changing the persisted setting.
	if (editor_context) {
		ai4seo_editor_runtime_view_modes[ editor_context ] = view_mode;
	}

	// Mode classes let CSS adapt shared context cards without changing the underlying form controls.
	$workspace
		.attr( 'data-ai4seo-active-view-mode', view_mode )
		.toggleClass( 'ai4seo-editor-workspace-preview-mode', view_mode === 'preview' )
		.toggleClass( 'ai4seo-editor-workspace-editor-mode', view_mode === 'editor' );

	// Keep the segmented control's programmatic state synchronized with the visible panel.
	$workspace.find( '.ai4seo-editor-mode-switch-button' ).each(
		function () {
			const is_active = this.getAttribute( 'data-ai4seo-editor-view-mode' ) === view_mode;
			this.setAttribute( 'aria-pressed', is_active ? 'true' : 'false' );
		}
	);

	// Native hidden state removes inactive panels from navigation while retaining every source input in the form.
	$workspace.find( '[data-ai4seo-editor-mode-panel]' ).each(
		function () {
			this.hidden = this.getAttribute( 'data-ai4seo-editor-mode-panel' ) !== view_mode;
		}
	);

	// Preview-only context supplements shared controls and therefore follows the same active mode.
	$workspace.find( '.ai4seo-editor-preview-only' ).each(
		function () {
			this.hidden = view_mode !== 'preview';
		}
	);

	// Editor-only context controls remain outside the main field panel while following the same active mode.
	$workspace.find( '.ai4seo-editor-editor-only' ).each(
		function () {
			this.hidden = view_mode !== 'editor';
		}
	);

	// Shared form-item feedback remains visible in both modes and is always synced with live input state.
	ai4seo_refresh_metadata_editor_length_feedback( $workspace );

	// Measurements are meaningful only after the preview panel becomes visible.
	if (view_mode === 'preview') {
		ai4seo_refresh_editor_previews( $workspace );
	}

	// Textarea scroll height is reliable only after the active editor panel has been painted.
	if (view_mode === 'editor') {
		const refresh_textareas = function () {
			if ($workspace.attr( 'data-ai4seo-active-view-mode' ) === 'editor') {
				ai4seo_refresh_auto_resize_textareas( $workspace );
			}
		};

		if (typeof window.requestAnimationFrame === 'function') {
			window.requestAnimationFrame( refresh_textareas );
		} else {
			window.setTimeout( refresh_textareas, 0 );
		}
	}
}

// =========================================================================================== \\

/**
 * Parse and cache one workspace's server-provided preview context.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @return {Object}
 */
function ai4seo_get_editor_preview_context(workspace) {
	const $workspace = ai4seo_normalize_$( workspace ).first();

	if (!ai4seo_exists_$( $workspace )) {
		return {};
	}

	// Reuse jQuery's element-scoped data cache so detached modal state is released with its workspace.
	const cached_context = $workspace.data( 'ai4seo-editor-preview-context' );

	if (cached_context && typeof cached_context === 'object') {
		return cached_context;
	}

	let preview_context = {};

	// Keep malformed server context isolated to this workspace rather than interrupting modal initialization.
	try {
		preview_context = JSON.parse( String( cached_context || $workspace.attr( 'data-ai4seo-preview-context' ) || '{}' ) );
	} catch (error) {
		console.error( ai4seo_get_plugin_name() + ': Could not parse editor preview context.', error );
	}

	$workspace.data( 'ai4seo-editor-preview-context', preview_context );

	return preview_context;
}

// =========================================================================================== \\

/**
 * Resolve current metadata input, fallback, prefix, and suffix values for one preview field.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @param {string} metadata_identifier
 * @return {string}
 */
function ai4seo_resolve_metadata_preview_value(workspace, metadata_identifier) {
	const $workspace = ai4seo_normalize_$( workspace ).first();
	const context = ai4seo_get_editor_preview_context( $workspace );
	const active_fields = Array.isArray( context.activeFields ) ? context.activeFields : [];

	// Resolve chained metadata fallbacks recursively while rejecting cycles from invalid imported settings.
	function resolve_raw_value(identifier, visited_identifiers = []) {
		// A repeated identifier means the fallback graph cannot produce a stable value.
		if (visited_identifiers.includes( identifier )) {
			return '';
		}

		// Live form values take precedence so unsaved edits immediately drive every related preview.
		const input = $workspace.find( '#ai4seo_metadata_' + identifier ).get( 0 );
		let value = input ? String( ai4seo_get_input_value( ai4seo_normalize_$( input ) ) || '' ) : '';

		// Inactive fields have no control, so retain the server value for fallback-driven appearances.
		if (!input && context.values && Object.prototype.hasOwnProperty.call( context.values, identifier )) {
			value = String( context.values[ identifier ] || '' );
		}

		// A direct non-empty value ends the fallback chain before presentation affixes are applied.
		if (value.trim() !== '') {
			return value.trim();
		}

		// Resolve the stored fallback choice only after the direct value is empty.
		const fallback_identifier = context.fallbackPreferences
			? String( context.fallbackPreferences[ identifier ] || '' )
			: '';

		if (!fallback_identifier || fallback_identifier === 'no-fallback') {
			return '';
		}

		// Post title, excerpt, and content fallbacks are immutable server-provided sources rather than editor fields.
		if (context.fallbackSources && Object.prototype.hasOwnProperty.call( context.fallbackSources, fallback_identifier )) {
			return String( context.fallbackSources[ fallback_identifier ] || '' ).trim();
		}

		// Field-to-field fallbacks may only follow values that the plugin currently manages.
		if (!active_fields.includes( fallback_identifier )) {
			return '';
		}

		return resolve_raw_value( fallback_identifier, visited_identifiers.concat( identifier ) );
	}

	const raw_value = resolve_raw_value( metadata_identifier );

	// Empty effective fields remain empty so Important states are not obscured by affixes alone.
	if (!raw_value) {
		return '';
	}

	// Prefixes and suffixes mirror frontend rendering without modifying the stored editor value.
	const prefix = context.prefixes ? String( context.prefixes[ metadata_identifier ] || '' ) : '';
	const suffix = context.suffixes ? String( context.suffixes[ metadata_identifier ] || '' ) : '';

	// Frontend output inserts one separator space around each non-empty affix after trimming it.
	return [prefix.trim(), raw_value, suffix.trim()].filter( Boolean ).join( ' ' );
}

// =========================================================================================== \\

/**
 * Evaluate actual line-clamp and box overflow for one visible preview element.
 *
 * @param {HTMLElement|null} preview_element
 * @return {{fits: boolean, measurable: boolean}}
 */
function ai4seo_evaluate_preview_text_fit(preview_element) {
	// Hidden panels cannot provide reliable layout measurements and remain advisory-neutral.
	if (!preview_element || preview_element.hidden || preview_element.offsetParent === null) {
		return {fits: true, measurable: false};
	}

	// Placeholder labels describe missing content but must not be classified as the user's rendered text.
	if (preview_element.getAttribute( 'data-ai4seo-preview-empty' ) === 'true') {
		return {fits: true, measurable: false};
	}

	// A one-pixel tolerance avoids fractional layout rounding being reported as truncation.
	const has_overflow = preview_element.scrollWidth > preview_element.clientWidth + 1
		|| preview_element.scrollHeight > preview_element.clientHeight + 1;

	return {fits: !has_overflow, measurable: true};
}

// =========================================================================================== \\

/**
 * Switch to Editor mode and place keyboard focus in a preview's source form field.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @param {string} field_identifier
 */
function ai4seo_focus_editor_preview_source_field(workspace, field_identifier) {
	const $workspace = ai4seo_normalize_$( workspace ).first();

	if (!ai4seo_exists_$( $workspace )) {
		return;
	}

	const editor_context = String( $workspace.attr( 'data-ai4seo-editor-context' ) || '' );
	const input_id = editor_context === 'metadata'
		? '#ai4seo_metadata_' + field_identifier
		: '#ai4seo_attachment_attribute_' + field_identifier;
	const input = $workspace.find( input_id ).get( 0 );

	// Stale or inactive card targets must not switch modes when no editable control exists.
	if (!input) {
		return;
	}

	// Reveal the existing source control before scheduling focus and scroll positioning.
	ai4seo_set_editor_view_mode( $workspace, 'editor' );

	// Defer focus until the browser has applied the panel visibility change.
	ai4seo_schedule_next_animation_frame(
		function () {
			input.focus( {preventScroll: true} );
			input.scrollIntoView( {behavior: 'smooth', block: 'center'} );
		}
	);
}

// =========================================================================================== \\

/**
 * Initialize live preview listeners, edit handoffs, image refreshes, and resize measurement.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_editor_previews(scope = null) {
	const $workspaces = ai4seo_get_elements_in_scope_$( '.ai4seo-editor-workspace', scope );

	if (!ai4seo_exists_$( $workspaces )) {
		return;
	}

	$workspaces.each(
		function () {
			const $workspace = ai4seo_normalize_$( this );
			const workspace_element = $workspace.get( 0 );

			// One delegated refresh path keeps manual edits and generated values synchronized identically.
			$workspace.find( 'input, textarea, select' )
				.off( 'input.ai4seo-editor-preview change.ai4seo-editor-preview' )
				.on(
					'input.ai4seo-editor-preview change.ai4seo-editor-preview',
					function () {
						ai4seo_refresh_editor_previews( $workspace );
					}
				);

			// Preview edit actions share the same field handoff regardless of the card that initiated it.
			$workspace.find( '.ai4seo-editor-preview-edit-button' )
				.off( 'click.ai4seo-editor-preview-edit' )
				.on(
					'click.ai4seo-editor-preview-edit',
					function () {
						ai4seo_focus_editor_preview_source_field( $workspace, String( this.getAttribute( 'data-ai4seo-preview-edit-target' ) || '' ) );
					}
				);

			// Image completion can change card height and therefore requires a new rendered-fit measurement.
			$workspace.find( 'img' )
				.off( 'load.ai4seo-editor-preview' )
				.on(
					'load.ai4seo-editor-preview',
					function () {
						ai4seo_refresh_editor_previews( $workspace );
					}
				);

			// Observe each dynamic workspace once; repeated modal initialization must not stack observers.
			if (typeof ResizeObserver === 'function' && workspace_element && !ai4seo_editor_preview_resize_observers.has( workspace_element )) {
				const resize_observer = new ResizeObserver(
					function () {
						ai4seo_refresh_editor_previews( $workspace );
					}
				);
				resize_observer.observe( workspace_element );
				ai4seo_editor_preview_resize_observers.set( workspace_element, resize_observer );
			}

			// Populate text and metrics immediately after all listeners are ready.
			ai4seo_refresh_editor_previews( $workspace );
		}
	);

	// The single window listener is the fallback for browsers without ResizeObserver and catches viewport changes.
	if (!ai4seo_editor_preview_window_resize_initialized) {
		ai4seo_editor_preview_window_resize_initialized = true;
		ai4seo_normalize_$( window ).on(
			'resize.ai4seo-editor-previews',
			function () {
				ai4seo_refresh_editor_previews( document );
			}
		);
	}
}

// =========================================================================================== \\

/**
 * Release preview resources owned by workspaces that are about to leave the document.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_destroy_editor_previews(scope = null) {
	const $workspaces = ai4seo_get_elements_in_scope_$( '.ai4seo-editor-workspace', scope );

	$workspaces.each(
		function () {
			const resize_observer = ai4seo_editor_preview_resize_observers.get( this );

			if (resize_observer) {
				resize_observer.disconnect();
				ai4seo_editor_preview_resize_observers.delete( this );
			}

			ai4seo_editor_preview_refresh_workspaces.delete( this );
		}
	);
}

// =========================================================================================== \\

/**
 * Batch preview updates into an animation frame so typing and resize signals stay inexpensive.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_refresh_editor_previews(scope = null) {
	// The shared scoped lookup already includes a matching root, including a directly supplied workspace.
	const $workspaces = ai4seo_get_elements_in_scope_$( '.ai4seo-editor-workspace', scope );

	$workspaces.each(
		function () {
			const workspace_element = this;

			// One pending frame per workspace absorbs rapid input, image, and resize signals.
			if (ai4seo_editor_preview_refresh_workspaces.has( workspace_element )) {
				return;
			}

			ai4seo_editor_preview_refresh_workspaces.add( workspace_element );
			ai4seo_schedule_next_animation_frame(
				function () {
					ai4seo_editor_preview_refresh_workspaces.delete( workspace_element );

					// Disconnect observers only after modal removal so active workspaces remain resize-aware.
					if (!document.documentElement.contains( workspace_element )) {
						const resize_observer = ai4seo_editor_preview_resize_observers.get( workspace_element );
						if (resize_observer) {
							resize_observer.disconnect();
							ai4seo_editor_preview_resize_observers.delete( workspace_element );
						}
						return;
					}

					ai4seo_refresh_editor_preview_workspace( workspace_element );
				}
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Replace one preview text node without interpreting field content as HTML.
 *
 * @param {HTMLElement|null} element
 * @param {string} value
 * @param {string} empty_label
 */
function ai4seo_set_editor_preview_text(element, value, empty_label) {
	if (!element) {
		return;
	}

	// textContent preserves literal metadata and marks placeholders so fit checks ignore synthetic labels.
	value = String( value || '' );
	element.textContent = value || empty_label;
	element.setAttribute( 'data-ai4seo-preview-empty', value ? 'false' : 'true' );
}

// =========================================================================================== \\

/**
 * Resolve an editor workspace from either the workspace itself or its surrounding modal scope.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @return {jQuery}
 */
function ai4seo_get_editor_workspace_$(workspace) {
	const $candidate = ai4seo_normalize_$( workspace ).first();

	if (!ai4seo_exists_$( $candidate )) {
		return jQuery();
	}

	if ($candidate.is( '.ai4seo-editor-workspace' )) {
		return $candidate;
	}

	const $descendant_workspace = $candidate.find( '.ai4seo-editor-workspace' ).first();
	if (ai4seo_exists_$( $descendant_workspace )) {
		return $descendant_workspace;
	}

	return $candidate.closest( '.ai4seo-editor-workspace' ).first();
}

/**
 * Return preview value elements that should transition after a successful generation response.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @return {Array<HTMLElement>}
 */
function ai4seo_get_editor_preview_generation_transition_elements(workspace) {
	const $workspace = ai4seo_get_editor_workspace_$( workspace );

	if (!ai4seo_exists_$( $workspace ) || $workspace.attr( 'data-ai4seo-active-view-mode' ) !== 'preview') {
		return [];
	}

	return Array.from(
		$workspace.get( 0 ).querySelectorAll(
			'.ai4seo-editor-preview-panel [data-ai4seo-preview-field], '
			+ '.ai4seo-editor-preview-panel .ai4seo-keywords-code-value'
		)
	);
}

/**
 * Fade the current Preview values before generated input values replace them.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @return {Promise<Array<HTMLElement>>}
 */
function ai4seo_hide_editor_preview_values_for_generation(workspace) {
	const elements = ai4seo_get_editor_preview_generation_transition_elements( workspace );
	const window_context = elements.length ? elements[0].ownerDocument.defaultView : null;
	const prefers_reduced_motion = window_context
		&& typeof window_context.matchMedia === 'function'
		&& window_context.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if (!elements.length || prefers_reduced_motion) {
		return Promise.resolve( [] );
	}

	elements.forEach(
		function (element) {
			element.classList.add( 'ai4seo-editor-preview-value-transition', 'ai4seo-editor-preview-value-leaving' );
		}
	);

	// Keep the delay slightly longer than the CSS transition so slow frames cannot expose a hard text swap.
	return new Promise(
		function (resolve) {
			window_context.setTimeout( () => resolve( elements ), 220 );
		}
	);
}

/**
 * Reveal newly refreshed Preview values after generation.
 *
 * @param {Array<HTMLElement>} elements
 */
function ai4seo_reveal_editor_preview_values_after_generation(elements) {
	elements = Array.isArray( elements ) ? elements.filter( element => element && element.isConnected ) : [];

	if (!elements.length) {
		return;
	}

	elements.forEach(
		function (element) {
			element.classList.remove( 'ai4seo-editor-preview-value-leaving' );
			element.classList.add( 'ai4seo-editor-preview-value-entering' );
		}
	);

	// Two frames establish the enter state before transitioning back to the resting appearance.
	ai4seo_schedule_next_animation_frame(
		function () {
			ai4seo_schedule_next_animation_frame(
				function () {
					elements.forEach(
						function (element) {
							element.classList.remove( 'ai4seo-editor-preview-value-entering' );
						}
					);
				}
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Build consistently labelled advisory feedback for the independent preview evaluations.
 *
 * @param {string} type
 * @param {string} text
 * @return {{type: string, label: string, icon: string, text: string}}
 */
function ai4seo_get_editor_preview_message(type, text) {
	// Central labels keep visual severity, text prefixes, and translation wording consistent across cards.
	const labels = {
		error: wp.i18n.__( 'Error', 'ai-for-seo' ),
		hint: wp.i18n.__( 'Hint', 'ai-for-seo' ),
		warning: wp.i18n.__( 'Warning', 'ai-for-seo' ),
		important: wp.i18n.__( 'Important', 'ai-for-seo' ),
	};
	const icons = {
		error: '\u00d7',
		warning: '!',
		hint: 'i',
		important: '!',
	};

	// Unknown message types retain their machine label so callers never lose visible context.
	return {
		type: type,
		label: labels[ type ] || type,
		icon: icons[ type ] || '\u2022',
		text: text,
	};
}

// =========================================================================================== \\

/**
 * Append one compact, keyboard-accessible tooltip for important preview guidance.
 *
 * @param {HTMLElement} evaluation
 * @param {{label: string, text: string}} message
 */
function ai4seo_append_editor_preview_important_tooltip(evaluation, message) {
	const document_context = evaluation.ownerDocument || document;
	const fit = evaluation.querySelector( '.ai4seo-editor-preview-fit' );
	const tooltip_parent = fit && fit.parentElement ? fit.parentElement : evaluation;
	const holder = document_context.createElement( 'span' );
	const trigger = document_context.createElement( 'button' );
	const icon = document_context.createElement( 'span' );
	const tooltip = document_context.createElement( 'span' );
	const message_text = message.label + ': ' + message.text;

	// Match canonical tooltip markup so the shared initializer supplies IDs, ARIA links, and interactions.
	holder.className = 'ai4seo-tooltip-holder ai4seo-icon-with-tooltip ai4seo-editor-preview-important-tooltip';
	trigger.className = 'ai4seo-tooltip-trigger ai4seo-icon-tooltip-trigger';
	trigger.type = 'button';
	trigger.setAttribute( 'aria-expanded', 'false' );
	trigger.setAttribute( 'aria-label', message_text );
	icon.className = 'ai4seo-editor-preview-important-tooltip-icon';
	icon.setAttribute( 'aria-hidden', 'true' );
	icon.textContent = '?';
	tooltip.className = 'ai4seo-tooltip ai4seo-ignore-during-dashboard-refresh';
	tooltip.setAttribute( 'role', 'tooltip' );
	tooltip.setAttribute( 'aria-hidden', 'true' );
	tooltip.textContent = message_text;
	trigger.append( icon );
	holder.append( trigger, tooltip );
	tooltip_parent.append( holder );
}

// =========================================================================================== \\

/**
 * Replace evaluation messages with icon- and text-labelled feedback.
 *
 * Important guidance remains available in a compact tooltip so the red action result stays readable.
 *
 * @param {HTMLElement|null} evaluation
 * @param {Array<{type: string, label: string, icon: string, text: string}>} messages
 */
function ai4seo_set_editor_preview_messages(evaluation, messages) {
	if (!evaluation) {
		return;
	}

	const holder = evaluation.querySelector( '.ai4seo-editor-preview-messages' );

	if (!holder) {
		return;
	}

	// Rebuild only this card's advisory area so typing does not disturb permanent description or metrics.
	holder.replaceChildren();
	evaluation.querySelectorAll( '.ai4seo-editor-preview-important-tooltip' ).forEach(
		function (tooltip) {
			tooltip.remove();
		}
	);
	let has_important_tooltip = false;

	// Important messages use compact help triggers while the remaining advisory types retain their inline rows.
	messages.forEach(
		function (message) {
			if (message.type === 'important') {
				ai4seo_append_editor_preview_important_tooltip( evaluation, message );
				has_important_tooltip = true;
				return;
			}

			// Use text nodes for field-adjacent feedback because metadata can contain arbitrary markup-like text.
			const row = evaluation.ownerDocument.createElement( 'p' );
			const icon = evaluation.ownerDocument.createElement( 'span' );
			const content = evaluation.ownerDocument.createElement( 'span' );
			const label = evaluation.ownerDocument.createElement( 'strong' );
			row.className = 'ai4seo-editor-preview-message ai4seo-editor-preview-message-' + message.type;
			icon.className = 'ai4seo-editor-preview-message-icon';
			icon.setAttribute( 'aria-hidden', 'true' );
			icon.textContent = message.icon;
			label.textContent = message.label + ': ';
			content.append( label, evaluation.ownerDocument.createTextNode( message.text ) );
			row.append( icon, content );
			holder.append( row );
		}
	);

	// Newly generated help triggers need the same scoped bindings as the static heading tooltips.
	if (has_important_tooltip) {
		ai4seo_init_tooltips( evaluation );
	}
}

// =========================================================================================== \\

/**
 * Return all preview evaluation blocks matching one field or legacy card identifier.
 *
 * @param {HTMLElement} workspace
 * @param {string} card_identifier
 * @param {string} [panel=''] Optional view mode panel selector: preview|editor.
 * @return {HTMLElement[]}
 */
function ai4seo_get_editor_preview_evaluation_elements(workspace, card_identifier, panel = '') {
	const panel_mode = String( panel || '' );
	const panel_scope = panel_mode
		? workspace.querySelector( '.ai4seo-editor-mode-panel[data-ai4seo-editor-mode-panel="' + panel_mode + '"]' )
		: workspace;

	// Support both current evaluation-markup and legacy card-markup that stores
	// the identifier directly on the card container. Unknown panels retain the legacy workspace fallback.
	const query_scope = panel_scope || workspace;

	if (!query_scope) {
		return [];
	}

	const direct_matches = Array.from( query_scope.querySelectorAll( '.ai4seo-editor-preview-evaluation[data-ai4seo-preview-card="' + card_identifier + '"]' ) );
	if (direct_matches.length) {
		return direct_matches;
	}

	const nested_matches = Array.from( query_scope.querySelectorAll( '[data-ai4seo-preview-card="' + card_identifier + '"] .ai4seo-editor-preview-evaluation' ) );
	if (nested_matches.length) {
		return nested_matches;
	}

	return Array.from( query_scope.querySelectorAll( '[data-ai4seo-preview-card="' + card_identifier + '"]' ) );
}

/**
 * Return the first matching preview evaluation for callers that only need source metadata.
 *
 * @param {HTMLElement} workspace
 * @param {string} card_identifier
 * @param {string} [panel=''] Optional view mode panel selector: preview|editor.
 * @return {HTMLElement|null}
 */
function ai4seo_get_editor_preview_evaluation_element(workspace, card_identifier, panel = '') {
	return ai4seo_get_editor_preview_evaluation_elements( workspace, card_identifier, panel )[0] || null;
}

// =========================================================================================== \\

/**
 * Update one preview card's count, generation target, rendered-fit result, and messages.
 *
 * @param {HTMLElement} workspace
 * @param {string} card_identifier
 * @param {string} count_text
 * @param {string} target_text
 * @param {Array} messages
 * @param {Object?} fit_feedback
 * @param {string} [panel=''] Optional view mode panel selector: preview|editor.
 */
function ai4seo_update_editor_preview_evaluation(workspace, card_identifier, count_text, target_text, messages, fit_feedback = null, panel = '') {
	const evaluations = ai4seo_get_editor_preview_evaluation_elements( workspace, card_identifier, panel );

	if (!evaluations.length) {
		return;
	}

	evaluations.forEach(
		function (evaluation) {
			// Measure every clamped/code region within the card because one overflow is enough to flag truncation.
			const card = evaluation.closest( '.ai4seo-editor-preview-card' );
			const measured_elements = card ? Array.from( card.querySelectorAll( '.ai4seo-preview-measured-text, pre' ) ) : [];
			const fit_results = measured_elements.map( ai4seo_evaluate_preview_text_fit ).filter( result => result.measurable );
			const fits = fit_results.every( result => result.fits );
			const count = evaluation.querySelector( '.ai4seo-editor-preview-count' );
			const target = evaluation.querySelector( '.ai4seo-editor-preview-target' );
			const fit = evaluation.querySelector( '.ai4seo-editor-preview-fit' );
			const source = evaluation.querySelector( '.ai4seo-editor-preview-source-message' );
			const advisory_messages = messages ? messages.slice() : [];
			const has_fit_feedback = fit_feedback && typeof fit_feedback === 'object';
			const feedback_messages = has_fit_feedback && Array.isArray( fit_feedback.messages ) ? fit_feedback.messages : [];
			const fit_level = has_fit_feedback && typeof fit_feedback.fit_level === 'string' ? fit_feedback.fit_level : '';

			// Counts remain stored-value metrics and therefore update independently from rendered fit.
			if (count) {
				const count_label = String( evaluation.getAttribute( 'data-ai4seo-editor-evaluation-label' ) || '' ).trim();
				count.textContent = count_label && count_text ? count_label + ': ' + count_text : count_text;
			}

			// Empty dynamic targets preserve any static target description emitted by PHP.
			if (target && target_text) {
				target.textContent = target_text;
			}

			// Fit wording distinguishes unavailable measurement from successful and overflowing rendered states.
			if (fit) {
				fit.textContent = has_fit_feedback && fit_feedback.fit_text
					? fit_feedback.fit_text
					: fit_results.length === 0
						? ''
						: fits
							? wp.i18n.__( 'Fits this preview', 'ai-for-seo' )
							: wp.i18n.__( 'Likely to truncate', 'ai-for-seo' );
				fit.classList.toggle( 'ai4seo-editor-preview-fit-warning', fit_results.length > 0 && !fits && !has_fit_feedback );
				fit.classList.toggle( 'ai4seo-editor-preview-fit-caution', fit_level === 'warning' );
				fit.classList.toggle( 'ai4seo-editor-preview-fit-important', fit_level === 'important' );
				fit.classList.toggle( 'ai4seo-editor-preview-fit-good', fit_level === 'good' || !fit_level );
			}

			// Keep source-hint lineups synchronized with editable input metadata.
			if (source) {
				const source_message = ai4seo_resolve_editor_preview_source_message( workspace, card_identifier, panel );

				ai4seo_update_editor_preview_source_message_element(
					source,
					source_message,
					[
						'ai4seo-editor-field-source-message',
						'ai4seo-sub-info',
						'ai4seo-display-none',
						'ai4seo-gray-message',
						'ai4seo-red-message',
						'ai4seo-green-message',
						'ai4seo-yellow-message',
					]
				);
			}

			// Append one advisory hint per overflowing card without turning pixel fit into a save blocker.
			if (!has_fit_feedback && fit_results.length > 0 && !fits) {
				advisory_messages.push(
					ai4seo_get_editor_preview_message(
						'hint',
						wp.i18n.__( 'This text exceeds the currently rendered card or line limit and is likely to be truncated.', 'ai-for-seo' )
					)
				);
			}

			ai4seo_set_editor_preview_messages( evaluation, advisory_messages.concat( feedback_messages ) );
		}
	);
}

// =========================================================================================== \\

/**
 * Refresh one metadata or attachment workspace immediately inside an animation frame.
 *
 * @param {HTMLElement} workspace
 */
function ai4seo_refresh_editor_preview_workspace(workspace) {
	const context = ai4seo_get_editor_preview_context( workspace );

	// The server context selects the owning renderer while both editors share scheduling and metrics.
	if (context.context === 'metadata') {
		ai4seo_refresh_metadata_editor_previews( workspace, context );
	} else if (context.context === 'attachment') {
		ai4seo_refresh_attachment_editor_previews( workspace, context );
	}

	// Shared field-level feedback should update whenever either preview or editor mode is visible.
	ai4seo_refresh_metadata_editor_length_feedback( workspace );
}

// =========================================================================================== \\

/**
 * Return a translated generation-target summary from the server's current quality window.
 *
 * @param {Object} context
 * @param {string} field_identifier
 * @return {string}
 */
function ai4seo_get_preview_generation_target_text(context, field_identifier) {
	const quality_window = ai4seo_get_preview_generation_target_window( context, field_identifier );
	const minimum_length = quality_window.minimum_length;
	const maximum_length = quality_window.maximum_length;

	// Missing or incomplete windows mean the field has no existing generation target to display.
	if (!minimum_length || !maximum_length) {
		return '';
	}

	return wp.i18n.sprintf(
		/* translators: 1: Minimum character target. 2: Maximum character target. */
		wp.i18n.__( 'Target: %1$d–%2$d characters', 'ai-for-seo' ),
		minimum_length,
		maximum_length
	);
}

/**
 * Return a normalized quality window for a given preview field.
 *
 * @param {Object} context
 * @param {string} field_identifier
 * @return {{minimum_length: number, maximum_length: number}}
 */
function ai4seo_get_preview_generation_target_window(context, field_identifier) {
	const quality_window = context.qualityWindows ? context.qualityWindows[ field_identifier ] : null;

	return {
		minimum_length: quality_window ? Number.parseInt( quality_window.min, 10 ) : 0,
		maximum_length: quality_window ? Number.parseInt( quality_window.max, 10 ) : 0,
	};
}

/**
 * Build a unified length-feedback summary for editor field previews.
 *
 * @param {Object} context
 * @param {string} field_identifier
 * @param {string} field_label
 * @param {number} value_length
 * @param {Object|null} override_length_window Optional window {minimum_length: number, maximum_length: number}.
 * @return {{fit_text: string, fit_level: string, messages: Array}|null}
 */
function ai4seo_get_editor_preview_length_feedback(context, field_identifier, field_label, value_length, override_length_window = null) {
	const quality_window = override_length_window
		? {
			minimum_length: parseInt( override_length_window.minimum_length || 0, 10 ),
			maximum_length: parseInt( override_length_window.maximum_length || 0, 10 ),
		}
		: ai4seo_get_preview_generation_target_window( context, field_identifier );
	const minimum_length = quality_window.minimum_length;
	const maximum_length = quality_window.maximum_length;

	if (!minimum_length || !maximum_length) {
		return null;
	}

	// Empty output is always the most actionable invalid-state message in the editor previews.
	if (!value_length) {
		return {
			fit_text: wp.i18n.sprintf( wp.i18n.__( 'Missing %s', 'ai-for-seo' ), field_label ),
			fit_level: 'important',
			messages: [
				ai4seo_get_editor_preview_message(
					'important',
					wp.i18n.__( 'This value is empty. Add it before saving.', 'ai-for-seo' )
				),
			],
		};
	}

	const percent_over_max = ((value_length - maximum_length) / maximum_length) * 100;
	const percent_under_min = ((minimum_length - value_length) / minimum_length) * 100;
	if (percent_under_min > 50) {
		return {
			fit_text: wp.i18n.__( 'Add more text', 'ai-for-seo' ),
			fit_level: 'important',
			messages: [
				ai4seo_get_editor_preview_message(
					'important',
					wp.i18n.__( 'This value is much shorter than the target. Add more text.', 'ai-for-seo' )
				),
			],
		};
	}
	if (percent_under_min > 20) {
		return {
			fit_text: wp.i18n.__( 'Add slightly more text', 'ai-for-seo' ),
			fit_level: 'warning',
			messages: [],
		};
	}

	if (percent_over_max > 50) {
		return {
			fit_text: wp.i18n.__( 'Remove some text', 'ai-for-seo' ),
			fit_level: 'important',
			messages: [
				ai4seo_get_editor_preview_message(
					'important',
					wp.i18n.__( 'This value is much longer than the target. Remove some text to keep it concise.', 'ai-for-seo' )
				),
			],
		};
	}
	if (percent_over_max > 20) {
		return {
			fit_text: wp.i18n.__( 'Remove some text', 'ai-for-seo' ),
			fit_level: 'warning',
			messages: [],
		};
	}

	if (value_length >= minimum_length && value_length <= maximum_length) {
		return {
			fit_text: wp.i18n.__( 'Great length', 'ai-for-seo' ),
			fit_level: 'good',
			messages: [],
		};
	}

	return {
		fit_text: wp.i18n.__( 'Okay', 'ai-for-seo' ),
		fit_level: 'good',
		messages: [],
	};
}

/**
 * Normalize manually entered metadata keywords for item-count feedback.
 *
 * @param {string} field_value
 * @return {Array<string>}
 */
function ai4seo_get_normalized_editor_keyword_items(field_value) {
	const keyword_keys = new Set();

	return String( field_value || '' )
		.split( /[,;\r\n]+/u )
		.map( keyword => keyword.trim() )
		.filter(
			function (keyword) {
				if (!keyword) {
					return false;
				}

				const keyword_key = keyword.toLowerCase();

				if (keyword_keys.has( keyword_key )) {
					return false;
				}

				keyword_keys.add( keyword_key );

				return true;
			}
		);
}

/**
 * Build one field-level evaluation summary for Metadata and Media Attributes editors.
 *
 * @param {Object} context
 * @param {string} field_identifier
 * @param {string} field_label
 * @param {string} field_value
 * @return {{count_text: string, target_text: string, fit_feedback: Object|null}}
 */
function ai4seo_get_editor_field_evaluation_details(context, field_identifier, field_label, field_value) {
	const value = String( field_value || '' ).trim();
	const value_length = ai4seo_get_text_length( value );

	// Focus keyphrases use one maximum-only recommendation instead of a generation target window.
	if (context.context === 'metadata' && field_identifier === 'focus-keyphrase') {
		const maximum_length = Number.parseInt( context.focusKeyphraseMaxLength || 30, 10 );
		let fit_feedback = {
			fit_text: wp.i18n.__( 'Good length', 'ai-for-seo' ),
			fit_level: 'good',
			messages: [],
		};

		if (!value_length) {
			fit_feedback = {
				fit_text: wp.i18n.__( 'Missing keyphrase', 'ai-for-seo' ),
				fit_level: 'important',
				messages: [ ai4seo_get_editor_preview_message( 'important', wp.i18n.__( 'Add a concise focus keyphrase before generating related metadata.', 'ai-for-seo' ) ) ],
			};
		} else if (value_length > maximum_length) {
			fit_feedback = {
				fit_text: wp.i18n.__( 'Shorten keyphrase', 'ai-for-seo' ),
				fit_level: 'important',
				messages: [
					ai4seo_get_editor_preview_message(
						'important',
						wp.i18n.sprintf(
							wp.i18n.__( 'Use no more than %d characters to keep the keyphrase focused.', 'ai-for-seo' ),
							maximum_length
						)
					),
				],
			};
		}

		return {
			count_text: wp.i18n.sprintf(
				wp.i18n._n( '%d character', '%d characters', value_length, 'ai-for-seo' ),
				value_length
			),
			target_text: '',
			fit_feedback: fit_feedback,
		};
	}

	// Keywords are evaluated as normalized distinct items because character fit is not meaningful for this field.
	if (context.context === 'metadata' && field_identifier === 'keywords') {
		const minimum_items = Number.parseInt( context.keywordsMinimumItems || 5, 10 );
		const maximum_items = Number.parseInt( context.keywordsMaximumItems || 10, 10 );
		const keyword_items = ai4seo_get_normalized_editor_keyword_items( value );
		let fit_feedback = {
			fit_text: wp.i18n.__( 'Good keyword count', 'ai-for-seo' ),
			fit_level: 'good',
			messages: [],
		};

		if (!keyword_items.length) {
			fit_feedback = {
				fit_text: wp.i18n.__( 'Missing keywords', 'ai-for-seo' ),
				fit_level: 'important',
				messages: [
					ai4seo_get_editor_preview_message(
						'important',
						wp.i18n.sprintf(
							wp.i18n.__( 'Add %1$d to %2$d distinct keywords.', 'ai-for-seo' ),
							minimum_items,
							maximum_items
						)
					),
				],
			};
		} else if (keyword_items.length < minimum_items) {
			fit_feedback = {
				fit_text: wp.i18n.__( 'Add more keywords', 'ai-for-seo' ),
				fit_level: 'warning',
				messages: [],
			};
		} else if (keyword_items.length > maximum_items) {
			fit_feedback = {
				fit_text: wp.i18n.__( 'Remove some keywords', 'ai-for-seo' ),
				fit_level: 'important',
				messages: [
					ai4seo_get_editor_preview_message(
						'important',
						wp.i18n.sprintf(
							wp.i18n.__( 'Keep the list focused at no more than %d keywords.', 'ai-for-seo' ),
							maximum_items
						)
					),
				],
			};
		}

		return {
			count_text: wp.i18n.sprintf(
				wp.i18n._n( '%d keyword', '%d keywords', keyword_items.length, 'ai-for-seo' ),
				keyword_items.length
			),
			target_text: wp.i18n.sprintf(
				wp.i18n.__( 'Recommended: %1$d–%2$d keywords', 'ai-for-seo' ),
				minimum_items,
				maximum_items
			),
			fit_feedback: fit_feedback,
		};
	}

	// All remaining fields reuse the shared quality-window evaluation used by the Media Attributes editor.
	return {
		count_text: wp.i18n.sprintf(
			wp.i18n._n( '%d character', '%d characters', value_length, 'ai-for-seo' ),
			value_length
		),
		target_text: ai4seo_get_preview_generation_target_text( context, field_identifier ),
		fit_feedback: ai4seo_get_editor_preview_length_feedback( context, field_identifier, field_label, value_length ),
	};
}

/**
 * Backward-compatible alias for attachment-specific callers and tests still referencing the old name.
 *
 * @param {Object} context
 * @param {string} field_identifier
 * @param {string} field_label
 * @param {number} value_length
 * @return {{fit_text: string, fit_level: string, messages: Array}|null}
 */
function ai4seo_get_attachment_preview_length_feedback(context, field_identifier, field_label, value_length) {
	return ai4seo_get_editor_preview_length_feedback( context, field_identifier, field_label, value_length );
}

/**
 * Read the current source hint for one editable field from its editor form label.
 *
 * @param {HTMLElement} workspace
 * @param {string} field_identifier
 * @param {string|null} current_value
 * @return {{text: string, class_name: string}|null}
 */
function ai4seo_get_editor_field_source_message(workspace, field_identifier, current_value = null) {
	const editor_context = String( workspace.getAttribute( 'data-ai4seo-editor-context' ) || '' );
	const input_selector = editor_context === 'attachment'
		? '#ai4seo_attachment_attribute_' + field_identifier
		: '#ai4seo_metadata_' + field_identifier;
	const input = workspace.querySelector( input_selector );

	if (!input) {
		return null;
	}

	const form_item = input.closest( '.ai4seo-form-item' );
	if (!form_item) {
		return null;
	}

	const source_message_element = form_item.querySelector( '.ai4seo-editor-field-source-message' );
	if (!source_message_element) {
		return null;
	}

	const field_value = current_value !== null ? String( current_value ) : String( ai4seo_get_input_value( input ) || '' );
	const source_message_base = source_message_element.getAttribute( 'data-ai4seo-editor-source-message-base' )
		|| source_message_element.textContent.trim();
	const is_generated_source = source_message_element.getAttribute( 'data-ai4seo-editor-source-message-generated' ) === 'true';
	const generated_value = source_message_element.getAttribute( 'data-ai4seo-editor-source-message-base-value' ) || '';

	const class_name = Array.from( source_message_element.classList ).find(
		function (candidate_class) {
			return candidate_class.indexOf( 'ai4seo-' ) === 0
				&& candidate_class !== 'ai4seo-editor-field-source-message'
				&& candidate_class !== 'ai4seo-sub-info';
		}
	);

	if (!source_message_base) {
		return null;
	}

	// This is a manual-edit notice appended only for values generated in the current session.
	if (is_generated_source && field_value !== generated_value) {
		return {
			text: source_message_base + ' ' + wp.i18n.__( 'This value appears to have been changed by a user after it was generated.', 'ai-for-seo' ),
			class_name: class_name || '',
		};
	}

	return {
		text: source_message_base,
		class_name: class_name || '',
	};
}

/**
 * Resolve one preview block's best source hint from its linked editable fields.
 *
 * @param {HTMLElement} workspace
 * @param {string} card_identifier
 * @param {string} [panel=''] Optional view mode panel selector: preview|editor.
 * @return {{text: string, class_name: string}|null}
 */
function ai4seo_resolve_editor_preview_source_message(workspace, card_identifier, panel = '') {
	const evaluation = ai4seo_get_editor_preview_evaluation_element( workspace, card_identifier, panel );

	if (!evaluation) {
		return null;
	}

	const source_identifiers = String( evaluation.getAttribute( 'data-ai4seo-editor-field-source-identifier' ) || '' )
		.split( ',' )
		.map( function (value) {
			return String( value || '' ).trim();
		} )
		.filter( Boolean );

	for (const source_identifier of source_identifiers) {
		const source = ai4seo_get_editor_field_source_message( workspace, source_identifier );
		if (source && source.text) {
			return source;
		}
	}

	// Fall back to the embedded static block text only when no editable source message is available.
	const static_source = evaluation.querySelector( '.ai4seo-editor-preview-source-message' );
	if (static_source && static_source.textContent.trim()) {
		return {
			text: static_source.textContent.trim(),
			class_name: '',
		};
	}

	return null;
}

// =========================================================================================== \\

/**
 * Apply a resolved source hint to an evaluation element through one shared DOM update path.
 *
 * @param {HTMLElement} source
 * @param {{text: string, class_name: string}|null} source_message
 * @param {Array<string>} removable_classes
 */
function ai4seo_update_editor_preview_source_message_element(source, source_message, removable_classes) {
	if (!source) {
		return;
	}

	// Callers retain control of context-specific stale classes while sharing content and visibility behavior.
	if (Array.isArray( removable_classes ) && removable_classes.length) {
		source.classList.remove( ...removable_classes );
	}

	source.classList.add( 'ai4seo-editor-field-source-message', 'ai4seo-sub-info' );

	if (source_message) {
		source.textContent = source_message.text;
		if (source_message.class_name) {
			source.classList.add( source_message.class_name );
		}
		return;
	}

	source.textContent = '';
	source.classList.add( 'ai4seo-display-none' );
}

// =========================================================================================== \\

/**
 * Replace featured-image preview shells with an image or a neutral placeholder.
 *
 * @param {HTMLElement} workspace
 * @param {string} selector
 * @param {string} image_url
 * @param {string} image_alt_text
 */
function ai4seo_update_editor_preview_images(workspace, selector, image_url, image_alt_text) {
	// Social shells share the same WordPress featured image while retaining platform-specific surrounding UI.
	workspace.querySelectorAll( selector ).forEach(
		function (shell) {
			const current_image = shell.querySelector( 'img' );

			// Preserve already-correct nodes to avoid image-load refresh loops and unnecessary network work.
			if (image_url && current_image && current_image.getAttribute( 'src' ) === image_url) {
				return;
			}
			if (!image_url && !current_image && shell.getAttribute( 'data-ai4seo-preview-placeholder' ) === 'true') {
				return;
			}

			// A source change replaces only the image surface, never the explanatory ownership label.
			shell.replaceChildren();

			// Missing featured images use a neutral textual placeholder instead of implying SOOZ image control.
			if (!image_url) {
				shell.textContent = wp.i18n.__( 'No featured image', 'ai-for-seo' );
				shell.classList.add( 'ai4seo-social-preview-image-placeholder' );
				shell.setAttribute( 'data-ai4seo-preview-placeholder', 'true' );
				return;
			}

			// Re-measure after the browser knows the replacement image's intrinsic dimensions.
			const image = workspace.ownerDocument.createElement( 'img' );
			image.src = image_url;
			image.alt = image_alt_text || '';
			image.addEventListener( 'load', () => ai4seo_refresh_editor_previews( workspace ), {once: true} );
			shell.classList.remove( 'ai4seo-social-preview-image-placeholder' );
			shell.removeAttribute( 'data-ai4seo-preview-placeholder' );
			shell.append( image );
		}
	);
}

// =========================================================================================== \\

/**
 * Refresh all metadata platform previews and their independent advisory evaluations.
 *
 * @param {HTMLElement} workspace
 * @param {Object} context
 */
function ai4seo_refresh_metadata_editor_previews(workspace, context) {
	const values = {};

	// Resolve every field once because multiple platform cards reuse the same effective values.
	const preview_fields = [
		'focus-keyphrase',
		'meta-title',
		'meta-description',
		'keywords',
		'facebook-title',
		'facebook-description',
		'twitter-title',
		'twitter-description'
	];

	preview_fields.forEach(
		identifier => {
			values[ identifier ] = ai4seo_resolve_metadata_preview_value( workspace, identifier );
		}
	);

	// Data attributes connect repeated platform surfaces to the resolved value without duplicating selectors.
	workspace.querySelectorAll( '[data-ai4seo-preview-field]' ).forEach(
		function (element) {
			const identifier = String( element.getAttribute( 'data-ai4seo-preview-field' ) || '' );
			ai4seo_set_editor_preview_text( element, values[ identifier ], wp.i18n.__( 'No value provided', 'ai-for-seo' ) );
		}
	);

	// Site identity and URL data remain constant while editor values change around them.
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-google-preview-site-name' ), context.siteName, wp.i18n.__( 'Website', 'ai-for-seo' ) );
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-google-preview-url' ), context.postUrl, context.siteUrl || '' );
	workspace.querySelectorAll( '.ai4seo-social-preview-domain, .ai4seo-whatsapp-preview-domain' ).forEach( element => {
		ai4seo_set_editor_preview_text( element, context.siteDomain, wp.i18n.__( 'Website', 'ai-for-seo' ) );
	} );

	// Use the WordPress site icon when available and a stable site-name initial otherwise.
	const site_icon = workspace.querySelector( '.ai4seo-google-preview-site-icon' );
	if (site_icon) {
		site_icon.replaceChildren();
		if (context.siteIcon) {
			const image = workspace.ownerDocument.createElement( 'img' );
			image.src = context.siteIcon;
			image.alt = '';
			site_icon.append( image );
		} else {
			site_icon.textContent = String( context.siteName || 'W' ).charAt( 0 ).toUpperCase();
		}
	}

	// Social previews intentionally share WordPress's featured image rather than adding plugin-managed image fields.
	ai4seo_update_editor_preview_images( workspace, '.ai4seo-social-preview-image, .ai4seo-whatsapp-preview-image', String( context.featuredImage || '' ), String( context.featuredImageAltText || '' ) );

	// Mirror keyphrase value and source into its prominent preview context card.
	const focus_keyphrase = values['focus-keyphrase'];
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-editor-focus-keyphrase-value' ), focus_keyphrase, wp.i18n.__( 'No focus keyphrase', 'ai-for-seo' ) );

	// Keywords remain escaped text inside the code representation; shared field feedback handles item counts.
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-keywords-code-value' ), values.keywords, '' );
}

// =========================================================================================== \\

/**
 * Read the current value of one Media Attributes editor source field.
 *
 * @param {HTMLElement} workspace
 * @param {Object} context
 * @param {string} field_identifier
 * @return {string}
 */
function ai4seo_get_attachment_preview_value(workspace, context, field_identifier) {
	// Prefer the live source control and use server context only when an inactive field has no input.
	const input = workspace.querySelector( '#ai4seo_attachment_attribute_' + field_identifier );
	if (input) {
		return String( ai4seo_get_input_value( ai4seo_normalize_$( input ) ) || '' ).trim();
	}
	return context.values ? String( context.values[ field_identifier ] || '' ).trim() : '';
}

// =========================================================================================== \\

/**
 * Refresh all Media Attributes previews and accessibility guidance.
 *
 * @param {HTMLElement} workspace
 * @param {Object} context
 */
function ai4seo_refresh_attachment_editor_previews(workspace, context) {
	const active_fields = Array.isArray( context.activeFields ) ? context.activeFields : [];
	const values = {};

	// Resolve the four registered attributes once before updating their different visual representations.
	['title', 'alt-text', 'caption', 'description'].forEach(
		identifier => {
			values[ identifier ] = ai4seo_get_attachment_preview_value( workspace, context, identifier );
		}
	);

	// Populate plain-text media, caption, description, and code surfaces from the same values.
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-attachment-title-preview-value' ), values.title, wp.i18n.__( 'Untitled media item', 'ai-for-seo' ) );
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-attachment-preview-filename' ), context.fileName, wp.i18n.__( 'File name unavailable', 'ai-for-seo' ) );
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-caption-preview-value' ), values.caption, wp.i18n.__( 'No caption stored', 'ai-for-seo' ) );
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-attachment-description-preview-value' ), values.description, wp.i18n.__( 'No description stored', 'ai-for-seo' ) );
	ai4seo_set_editor_preview_text( workspace.querySelector( '.ai4seo-alt-code-value' ), values['alt-text'], '' );

	// Non-image attachments replace image-specific simulations without producing a false Important state.
	const alt_code = workspace.querySelector( '.ai4seo-alt-code-preview' );
	const not_applicable = workspace.querySelector( '.ai4seo-attachment-not-applicable' );
	if (alt_code) {
		alt_code.classList.toggle( 'ai4seo-display-none', !context.isImage );
	}
	if (not_applicable) {
		not_applicable.classList.toggle( 'ai4seo-display-none', Boolean( context.isImage ) );
	}

	// Title evaluation keeps inactive-field guidance independent from the rendered truncation result.
	const title_feedback = active_fields.includes( 'title' ) ? ai4seo_get_attachment_preview_length_feedback(
		context,
		'title',
		wp.i18n.__( 'Title', 'ai-for-seo' ),
		ai4seo_get_text_length( values.title )
	) : null;
	const title_messages = [];
	if (!active_fields.includes( 'title' )) {
		title_messages.push( ai4seo_get_editor_preview_message( 'hint', wp.i18n.__( 'Title is inactive, so SOOZ does not manage it for this media item.', 'ai-for-seo' ) ) );
	}
	ai4seo_update_editor_preview_evaluation(
		workspace,
		'attachment-title',
		wp.i18n.sprintf( wp.i18n.__( '%d characters', 'ai-for-seo' ), ai4seo_get_text_length( values.title ) ),
		ai4seo_get_preview_generation_target_text( context, 'title' ),
		title_messages,
		title_feedback,
		'preview'
	);

	// Alt guidance prioritizes applicability, then field activity, then the missing-value accessibility warning.
	const alt_feedback = context.isImage && active_fields.includes( 'alt-text' ) ? ai4seo_get_attachment_preview_length_feedback(
		context,
		'alt-text',
		wp.i18n.__( 'Alt text', 'ai-for-seo' ),
		ai4seo_get_text_length( values['alt-text'] )
	) : null;
	const alt_messages = [];
	if (!context.isImage) {
		alt_messages.push( ai4seo_get_editor_preview_message( 'hint', wp.i18n.__( 'Alt text is not applicable to this non-image attachment.', 'ai-for-seo' ) ) );
	} else if (!active_fields.includes( 'alt-text' )) {
		alt_messages.push( ai4seo_get_editor_preview_message( 'hint', wp.i18n.__( 'Alt Text is inactive, so SOOZ does not manage it for this image.', 'ai-for-seo' ) ) );
	}
	ai4seo_update_editor_preview_evaluation(
		workspace,
		'attachment-alt',
		wp.i18n.sprintf( wp.i18n.__( '%d characters', 'ai-for-seo' ), ai4seo_get_text_length( values['alt-text'] ) ),
		ai4seo_get_preview_generation_target_text( context, 'alt-text' ),
		alt_messages,
		alt_feedback,
		'preview'
	);

	// Caption and description share inactive-field evaluation while retaining their static PHP target copy.
	[
		{card: 'attachment-caption', field: 'caption'},
		{card: 'attachment-description', field: 'description'},
	].forEach(
		function (details) {
			const field_length = ai4seo_get_text_length( values[ details.field ] );
			const field_feedback = active_fields.includes( details.field ) ? ai4seo_get_attachment_preview_length_feedback(
				context,
				details.field,
				details.field === 'description' ? wp.i18n.__( 'Description', 'ai-for-seo' ) : wp.i18n.__( 'Caption', 'ai-for-seo' ),
				field_length
			) : null;
			const messages = [];
			if (!active_fields.includes( details.field )) {
				messages.push( ai4seo_get_editor_preview_message( 'hint', wp.i18n.__( 'This attribute is inactive, so SOOZ does not manage it for this media item.', 'ai-for-seo' ) ) );
			}
			ai4seo_update_editor_preview_evaluation(
				workspace,
				details.card,
				wp.i18n.sprintf( wp.i18n.__( '%d characters', 'ai-for-seo' ), field_length ),
				ai4seo_get_preview_generation_target_text( context, details.field ),
				messages,
				field_feedback,
				'preview'
			);
		}
	);
}

// =========================================================================================== \\

function ai4seo_is_attachment_post_type() {
	const $body = ai4seo_normalize_$( 'body' );

	if (!ai4seo_exists_$( $body )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$body\" missing in ai4seo_is_attachment_post_type() \u2014 cannot detect attachment screen.' );
		return false;
	}

	return $body.hasClass( 'post-type-attachment' );
}

// =========================================================================================== \\

function ai4seo_is_attachment_mime_type_supported() {
	// Define boolean to determine whether supported mime-type has been found.
	let has_supported_mime_type = false;

	// Loop through attachment-mime-type-selector-elements.
	jQuery.each(
        ai4seo_attachment_mime_type_selectors,
        function (this_selector_key, this_selector) {
		// Make sure that mime-type-selector is jQuery-element.
		const $this_mime_type_container = ai4seo_normalize_$( this_selector );

		if (!ai4seo_exists_$( $this_mime_type_container )) {
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$this_mime_type_container\" not found for selector \"' + this_selector + '\" in ai4seo_is_attachment_mime_type_supported() \u2014 skipping media support check for this selector.' );
			return;
		}

		// Check if this selector-element exists on the current page
		// Get the content of the selector.
		const mime_type_container_text = $this_mime_type_container.text();

		// Skip this entry if this selector doesn't have any content.
		if (!mime_type_container_text) {
			return;
		}

		// Loop through ai4seo_supported_mime_types and check if mime-type exists in selector-content.
		jQuery.each(
            ai4seo_supported_mime_types,
            function (this_mime_type_key, this_mime_type_value) {
			if (mime_type_container_text.indexOf( this_mime_type_value ) > -1) {
				has_supported_mime_type = true;
			}
            }
        );
        }
    );

	return has_supported_mime_type;
}

// =========================================================================================== \\

/**
 * Resize one plugin textarea to its complete rendered content height.
 *
 * @param {jQuery|Node|Window|null} textarea
 */
function ai4seo_resize_textarea(textarea) {
	const $textarea = ai4seo_normalize_$( textarea ).first();

	if (!ai4seo_exists_$( $textarea )) {
		return;
	}

	const is_custom_instructions_textarea = $textarea.hasClass( 'ai4seo-custom-instructions-input' );
	const textarea_style = window.getComputedStyle( $textarea[ 0 ] );
	const border_bottom = parseFloat( textarea_style.borderBottomWidth || 0 );
	const border_top = parseFloat( textarea_style.borderTopWidth || 0 );
	const padding_bottom = parseFloat( textarea_style.paddingBottom || 0 );
	const padding_top = parseFloat( textarea_style.paddingTop || 0 );
	const line_height = parseFloat( textarea_style.lineHeight || 0 );
	const textarea_border_height = ( isNaN( border_top ) ? 0 : border_top ) + ( isNaN( border_bottom ) ? 0 : border_bottom );
	const textarea_border_padding = textarea_border_height
		+ ( isNaN( padding_bottom ) ? 0 : padding_bottom )
		+ ( isNaN( padding_top ) ? 0 : padding_top );
	const textarea_line_height = line_height > 0 ? line_height : 22;

	// Clear a previous inline height before measuring the current wrapped content.
	$textarea.css( 'height', 'auto' );
	const scroll_height = Number( $textarea.prop( 'scrollHeight' ) || 0 );

	// Hidden panels report zero. Their next visible activation schedules a fresh measurement.
	if (scroll_height <= 0) {
		return;
	}

	let next_height = scroll_height + textarea_border_height;

	if (is_custom_instructions_textarea) {
		const min_height = textarea_line_height + textarea_border_padding;
		const max_height = 4 * textarea_line_height + textarea_border_padding;

		next_height = Math.max( min_height, Math.min( next_height, max_height ) );
		$textarea.css( 'overflow-y', next_height >= max_height ? 'auto' : 'hidden' );
	} else {
		// Normal plugin textareas expand fully; the containing page or modal handles longer content scrolling.
		$textarea.css( 'overflow-y', 'hidden' );
	}

	$textarea.css( 'height', next_height + 'px' );
}

// =========================================================================================== \\

/**
 * Re-measure all plugin-owned textareas within the requested root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_refresh_auto_resize_textareas(scope = null) {
	const $textareas = ai4seo_get_elements_in_scope_$( 'textarea.ai4seo-textarea, textarea.ai4seo-editor-textarea', scope );

	if (!ai4seo_exists_$( $textareas )) {
		return;
	}

	$textareas.each(
		function () {
			ai4seo_resize_textarea( this );
		}
	);
}

// =========================================================================================== \\ 

/**
 * Bind global auto-resize behavior to plugin-owned textareas inside the requested root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_auto_resize_textareas(scope = null) {
	const $textareas = ai4seo_get_elements_in_scope_$( 'textarea.ai4seo-textarea, textarea.ai4seo-editor-textarea', scope );

	if (!ai4seo_exists_$( $textareas )) {
		return;
	}

	$textareas.each(
		function () {
			const $this_textarea = ai4seo_normalize_$( this );

			if (!ai4seo_exists_$( $this_textarea )) {
				return;
			}

			$this_textarea.off( 'input.ai4seo-auto-resize' );
			$this_textarea.on(
				'input.ai4seo-auto-resize',
				function () {
					ai4seo_resize_textarea( this );
				}
			);

			ai4seo_resize_textarea( this );
		}
	);
}

// =========================================================================================== \\

/**
 * Resolve the active custom-instruction length limit from the textarea first, then from localization.
 */
function ai4seo_get_custom_instruction_input_limit($input) {
	$input = ai4seo_normalize_$( $input );

	if (ai4seo_exists_$( $input )) {
		const input_limit = parseInt( $input.attr( 'data-ai4seo-custom-instructions-limit' ) || $input.attr( 'maxlength' ), 10 );

		if (!isNaN( input_limit ) && input_limit > 0) {
			return input_limit;
		}
	}

	return ai4seo_normalize_length( ai4seo_get_localization_parameter( 'ai4seo_custom_instructions_length_limit' ), 200 );
}

// =========================================================================================== \\

/**
 * Count Unicode code points so browser-side length checks match PHP mb_strlen() closely.
 */
function ai4seo_get_text_length(input_value) {
	return Array.from( String( input_value || '' ) ).length;
}

// =========================================================================================== \\

/**
 * Count custom-instruction text through the shared Unicode length mechanism.
 */
function ai4seo_get_custom_instruction_text_length(input_value) {
	// Preserve the feature-specific helper for existing callers while centralizing the counting behavior.
	return ai4seo_get_text_length( input_value );
}

// =========================================================================================== \\

/**
 * Trim by Unicode code points to mirror the server-side mb_substr() cap.
 */
function ai4seo_trim_custom_instruction_text(input_value, max_length) {
	return Array.from( String( input_value || '' ) ).slice( 0, max_length ).join( '' );
}

// =========================================================================================== \\

/**
 * Find the counter paired with a custom-instruction textarea by its generated input id.
 */
function ai4seo_get_custom_instruction_counter_for_input($input) {
	$input = ai4seo_normalize_$( $input );

	if (!ai4seo_exists_$( $input )) {
		return jQuery();
	}

	const input_id = $input.attr( 'id' ) || '';

	if (!input_id) {
		return jQuery();
	}

	// Search from the owning form outward so repeated modal IDs cannot bind a sibling instance.
	const $form = $input.closest( 'form' ).first();
	const $modal = $input.closest( '.ai4seo-modal' ).first();
	const counter_scopes = [$form, $modal, $input.get( 0 ).ownerDocument];

	for (const counter_scope of counter_scopes) {
		if (!counter_scope || (counter_scope.jquery && !ai4seo_exists_$( counter_scope ))) {
			continue;
		}

		const $matching_counters = ai4seo_get_elements_in_scope_$( '.ai4seo-custom-instructions-counter', counter_scope ).filter(
			function () {
				return jQuery( this ).attr( 'data-input-id' ) === input_id;
			}
		);

		if (ai4seo_exists_$( $matching_counters )) {
			return $matching_counters;
		}
	}

	return jQuery();
}

// =========================================================================================== \\

/**
 * Keep textarea contents capped and update the paired usage counter.
 */
function ai4seo_update_custom_instruction_counter($input) {
	$input = ai4seo_normalize_$( $input );

	if (!ai4seo_exists_$( $input )) {
		return;
	}

	const max_length = ai4seo_get_custom_instruction_input_limit( $input );
	let input_value = String( $input.val() || '' );
	let input_length = ai4seo_get_custom_instruction_text_length( input_value );

	if (input_length > max_length) {
		input_value = ai4seo_trim_custom_instruction_text( input_value, max_length );
		$input.val( input_value );
		input_length = ai4seo_get_custom_instruction_text_length( input_value );
	}

	const used_characters = input_length;
	const $counter = ai4seo_get_custom_instruction_counter_for_input( $input );

	if (!ai4seo_exists_$( $counter )) {
		return;
	}

	const $characters_left = $counter.find( '.ai4seo-custom-instructions-characters-left' );

	if (ai4seo_exists_$( $characters_left )) {
		$characters_left.text(
            wp.i18n.sprintf(
			/* translators: %1$s: Used characters. %2$s: Character limit. */
                wp.i18n.__( '%1$s / %2$s characters', 'ai-for-seo' ),
                ai4seo_format_number_i18n( used_characters ),
                ai4seo_format_number_i18n( max_length )
            )
        );
	}
}

// =========================================================================================== \\

/**
 * Bind custom-instruction counters after AJAX-rendered settings and editor forms are inserted.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_custom_instruction_counters(scope = null) {
	const $custom_instruction_inputs = ai4seo_get_elements_in_scope_$( '.ai4seo-custom-instructions-input', scope );

	if (!ai4seo_exists_$( $custom_instruction_inputs )) {
		return;
	}

	$custom_instruction_inputs.each(
        function () {
		const $this_input = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_input )) {
			return;
		}

		// Reset AJAX-rendered forms before binding so repeated modal opens do not stack handlers.
		$this_input.off( 'input.ai4seo-custom-instructions' );
		$this_input.on(
            'input.ai4seo-custom-instructions',
            function () {
			ai4seo_update_custom_instruction_counter( $this_input );
            }
        );

		ai4seo_update_custom_instruction_counter( $this_input );
        }
    );
}

// =========================================================================================== \\

/**
 * Hide every tooltip in this document while synchronizing the shared ARIA state.
 *
 * @param {number} fade_duration
 */
function ai4seo_hide_all_tooltips(fade_duration = 0) {
	const $tooltips = ai4seo_normalize_$( '.ai4seo-tooltip' );

	if (ai4seo_exists_$( $tooltips )) {
		ai4seo_hide_tooltip( $tooltips, fade_duration );
	}
}

// =========================================================================================== \\

/**
 * Resolve a holder's tooltip even while a modal tooltip is temporarily rendered under body.
 *
 * @param {jQuery|Node|null} tooltip_holder
 * @return {jQuery}
 */
function ai4seo_get_tooltip_for_holder_$(tooltip_holder) {
	const $tooltip_holder = ai4seo_normalize_$( tooltip_holder );

	if (!ai4seo_exists_$( $tooltip_holder )) {
		return ai4seo_normalize_$( [] );
	}

	const tooltip_holder_element = $tooltip_holder.get( 0 );
	const $tooltip = $tooltip_holder.children( '.ai4seo-tooltip' ).first();

	if (ai4seo_exists_$( $tooltip )) {
		return $tooltip;
	}

	return ai4seo_normalize_$( tooltip_holder_element.ai4seo_portaled_tooltip || [] );
}

// =========================================================================================== \\

/**
 * Resolve the original holder after a modal tooltip has moved outside clipping ancestors.
 *
 * @param {jQuery|Node|null} tooltip
 * @return {jQuery}
 */
function ai4seo_get_tooltip_holder_$(tooltip) {
	const $tooltip = ai4seo_normalize_$( tooltip );

	if (!ai4seo_exists_$( $tooltip )) {
		return ai4seo_normalize_$( [] );
	}

	const tooltip_element = $tooltip.get( 0 );

	return tooltip_element.ai4seo_tooltip_holder
		? ai4seo_normalize_$( tooltip_element.ai4seo_tooltip_holder )
		: $tooltip.closest( '.ai4seo-tooltip-holder' );
}

// =========================================================================================== \\

/**
 * Cancel a pending pointer-leave close so users can reach and scroll a portaled tooltip.
 *
 * @param {jQuery|Node|null} tooltip_holder
 */
function ai4seo_cancel_tooltip_hide(tooltip_holder) {
	const $tooltip_holder = ai4seo_normalize_$( tooltip_holder );

	if (!ai4seo_exists_$( $tooltip_holder )) {
		return;
	}

	const tooltip_holder_element = $tooltip_holder.get( 0 );

	if (tooltip_holder_element.ai4seo_tooltip_hide_timeout) {
		window.clearTimeout( tooltip_holder_element.ai4seo_tooltip_hide_timeout );
		tooltip_holder_element.ai4seo_tooltip_hide_timeout = null;
	}
}

// =========================================================================================== \\

/**
 * Close a tooltip after a short bridge delay when it is rendered outside its holder.
 *
 * @param {jQuery|Node|null} tooltip_holder
 * @param {jQuery|Node|null} tooltip
 */
function ai4seo_schedule_tooltip_hide(tooltip_holder, tooltip) {
	const $tooltip_holder = ai4seo_normalize_$( tooltip_holder );
	const $tooltip = ai4seo_normalize_$( tooltip );

	if (!ai4seo_exists_$( $tooltip_holder ) || !ai4seo_exists_$( $tooltip )) {
		return;
	}

	if (!$tooltip.hasClass( 'ai4seo-tooltip-portal' )) {
		ai4seo_hide_tooltip( $tooltip, 200 );
		return;
	}

	ai4seo_cancel_tooltip_hide( $tooltip_holder );
	const tooltip_holder_element = $tooltip_holder.get( 0 );

	tooltip_holder_element.ai4seo_tooltip_hide_timeout = window.setTimeout(
		function () {
			tooltip_holder_element.ai4seo_tooltip_hide_timeout = null;

			if (!tooltip_holder_element.ai4seo_tooltip_is_pinned && !$tooltip.is( ':hover' )) {
				ai4seo_hide_tooltip( $tooltip, 200 );
			}
		},
		180
	);
}

// =========================================================================================== \\

/**
 * Initialize tooltip markup only within the requested static or AJAX-rendered root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_tooltips(scope = null) {
	if (typeof jQuery !== 'function') {
		return;
	}

	// Use one namespace for all tooltip handlers so repeat init replaces bindings without stacking them.
	const tooltip_event_namespace = '.ai4seo-tooltips';
	const tooltip_holder_events = 'mouseenter' + tooltip_event_namespace + ' mouseleave' + tooltip_event_namespace + ' click' + tooltip_event_namespace;
	const $tooltip_holders = ai4seo_get_elements_in_scope_$( '.ai4seo-tooltip-holder', scope );
	const $tooltip_triggers = ai4seo_get_elements_in_scope_$( '.ai4seo-tooltip-trigger', scope );
	const $tooltips = ai4seo_get_elements_in_scope_$( '.ai4seo-tooltip', scope );

	if (ai4seo_exists_$( $tooltip_holders )) {
		// Connect each canonical trigger to its tooltip, assigning stable collision-free IDs to new AJAX markup.
		$tooltip_holders.each(
			function () {
				const $this_tooltip_holder = ai4seo_normalize_$( this );
				const $this_tooltip_trigger = $this_tooltip_holder.children( '.ai4seo-tooltip-trigger' ).first();
				const $this_tooltip = $this_tooltip_holder.children( '.ai4seo-tooltip' ).first();

				if (!ai4seo_exists_$( $this_tooltip_trigger ) || !ai4seo_exists_$( $this_tooltip )) {
					console.warn( ai4seo_get_plugin_name() + ': canonical tooltip trigger or content missing in ai4seo_init_tooltips() — cannot prepare accessibility state.' );
					return;
				}

				let tooltip_id = $this_tooltip.attr( 'id' );

				if (!tooltip_id) {
					const tooltip_document = $this_tooltip.get( 0 ).ownerDocument || document;

					do {
						ai4seo_tooltip_id_counter++;
						tooltip_id = 'ai4seo-tooltip-' + ai4seo_tooltip_id_counter;
					} while (tooltip_document.getElementById( tooltip_id ));

					$this_tooltip.attr( 'id', tooltip_id );
				}

				const tooltip_is_visible = $this_tooltip.is( ':visible' );

				$this_tooltip_trigger.attr(
					{
						'aria-controls': tooltip_id,
						'aria-describedby': tooltip_id,
						'aria-expanded': tooltip_is_visible ? 'true' : 'false'
					}
				);
				$this_tooltip.attr(
					{
						'role': 'tooltip',
						'aria-hidden': tooltip_is_visible ? 'false' : 'true'
					}
				);
			}
		);

		// Rebind current tooltip holders because modal and settings markup can be inserted after page load.
		$tooltip_holders.off( tooltip_holder_events );

		$tooltip_holders.on(
            'mouseenter' + tooltip_event_namespace,
            function (event) {
			const $this_tooltip_holder = jQuery( this );
			const $this_tooltip_child = ai4seo_get_tooltip_for_holder_$( $this_tooltip_holder );

			if (!ai4seo_exists_$( $this_tooltip_child )) {
				console.warn( ai4seo_get_plugin_name() + ': element \"$this_tooltip_child\" missing in ai4seo_init_tooltips() — cannot prepare tooltip content.' );
				return;
			}

			ai4seo_cancel_tooltip_hide( $this_tooltip_holder );
			this.ai4seo_tooltip_is_pinned = false;
			ai4seo_show_tooltip( $this_tooltip_child, event );
            }
        );

		$tooltip_holders.on(
            'mouseleave' + tooltip_event_namespace,
            function () {
			const $this_tooltip_holder = jQuery( this );
			const $this_tooltip = ai4seo_get_tooltip_for_holder_$( $this_tooltip_holder );

			if (!ai4seo_exists_$( $this_tooltip )) {
				console.warn( ai4seo_get_plugin_name() + ': element \"$this_tooltip\" missing in ai4seo_init_tooltips() — cannot initialize tooltip content.' );
				return;
			}

			ai4seo_schedule_tooltip_hide( $this_tooltip_holder, $this_tooltip );
            }
        );

		$tooltip_holders.on(
            'click' + tooltip_event_namespace,
            function (event) {
			event.stopPropagation(); // Prevent the event from propagating to the document.
			const $this_tooltip_holder = jQuery( this );
			const $this_tooltip_child = ai4seo_get_tooltip_for_holder_$( $this_tooltip_holder );

			if (!ai4seo_exists_$( $this_tooltip_child )) {
				console.warn( ai4seo_get_plugin_name() + ': element \"$this_tooltip_child\" missing in ai4seo_init_tooltips() \u2014 cannot prepare tooltip content.' );
				return;
			}

			const should_show_tooltip = !$this_tooltip_child.is( ':visible' ) || event.detail > 0;

			// Resolve tooltips at click time so newly inserted tooltips are included in the close behavior.
			ai4seo_hide_all_tooltips();

			if (should_show_tooltip) {
				this.ai4seo_tooltip_is_pinned = true;
				setTimeout(
                    function () {
					ai4seo_show_tooltip( $this_tooltip_child, event );
                    },
                    1
                );
			}
            }
		);
	}

	if (ai4seo_exists_$( $tooltip_triggers )) {
		$tooltip_triggers.off( 'keydown' + tooltip_event_namespace + ' focusout' + tooltip_event_namespace );

		$tooltip_triggers.on(
			'keydown' + tooltip_event_namespace,
			function (event) {
				if ('Enter' === event.key || ' ' === event.key) {
					event.preventDefault();
					jQuery( this ).trigger( 'click' );
					return;
				}

				if ('Escape' !== event.key) {
					return;
				}

				event.stopPropagation();
				ai4seo_hide_tooltip( ai4seo_get_tooltip_for_holder_$( jQuery( this ).closest( '.ai4seo-tooltip-holder' ) ) );
			}
		);

		$tooltip_triggers.on(
			'focusout' + tooltip_event_namespace,
			function () {
				ai4seo_hide_tooltip( ai4seo_get_tooltip_for_holder_$( jQuery( this ).closest( '.ai4seo-tooltip-holder' ) ), 200 );
			}
		);
	}

	if (ai4seo_exists_$( $tooltips )) {
		$tooltips.off( 'click' + tooltip_event_namespace );

		$tooltips.on(
            'click' + tooltip_event_namespace,
            function (event) {
			event.stopPropagation(); // Prevent the event from propagating to the document.

			// Portaled modal help remains open while users select text or operate its scrollbar.
			if (jQuery( this ).hasClass( 'ai4seo-tooltip-portal' )) {
				return;
			}

			setTimeout(
                function () {
				// Resolve tooltips at close time so repeat init does not keep stale jQuery collections.
				ai4seo_hide_all_tooltips();
                },
                2
            );
            }
        );

		// Register the document close behavior once, and only after tooltip markup actually exists.
		if (!ai4seo_tooltip_document_close_handler_initialized) {
			jQuery( document )
				.off( 'click' + tooltip_event_namespace )
				.on(
					'click' + tooltip_event_namespace,
					function () {
						setTimeout(
							function () {
								// Resolve current markup instead of retaining a collection across AJAX replacements.
								ai4seo_hide_all_tooltips();
							},
							2
						);
					}
				);

			ai4seo_tooltip_document_close_handler_initialized = true;
		}
	}
}

// =========================================================================================== \\

/**
 * Init all our "ai4seo-countdown" elements
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_countdown_elements(scope = null) {
	const $countdowns = ai4seo_get_elements_in_scope_$( '.ai4seo-countdown', scope );

	if (!ai4seo_exists_$( $countdowns )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': no \"$countdown\" elements found in ai4seo_init_countdown_elements() \u2014 no timers initialized.');.
		return;
	}

	// ai4seo_console_debug(ai4seo_get_plugin_name() + ': initializing ' + $countdowns.length + ' countdown timers in ai4seo_init_countdown_elements().');.

	$countdowns.each(
        function () {
		ai4seo_init_countdown( this );
        }
    );
}

// =========================================================================================== \\

/**
 * Apply a continuous countdown to the given element
 */
function ai4seo_init_countdown($countdown) {
	$countdown = ai4seo_normalize_$( $countdown );

	if (!ai4seo_exists_$( $countdown )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$countdown\" missing in ai4seo_init_countdown() \u2014 timer cannot start.' );
		return;
	}

	// skip if element is already initialized.
	if ($countdown.data( 'initialized' )) {
		return;
	}

	// add class ai4seo-ignore-during-dashboard-refresh if not already set.
	if (!$countdown.hasClass( 'ai4seo-ignore-during-dashboard-refresh' )) {
		$countdown.addClass( 'ai4seo-ignore-during-dashboard-refresh' );
	}

	// check if element has data-time-left attribute.
	let total_seconds = $countdown.data( 'time-left' );

	if (isNaN( total_seconds ) || total_seconds <= 0) {
		return;
	}

	// get the time since page load in seconds and subtract it from total_seconds.
	let time_since_page_load = Math.floor( (Date.now() - window.ai4seo_page_load_time) / 1000 );
	total_seconds -= time_since_page_load;

	let interval = setInterval(
        function () {
		total_seconds--;

		if (total_seconds <= 0) {
			clearInterval( interval );
			$countdown.text( '00:00:00' );
			time_since_page_load = Math.floor( (Date.now() - window.ai4seo_page_load_time) / 1000 );

			// only trigger the function if we are at least 10 seconds after page load.
			if (time_since_page_load >= 10) {
				let trigger_function_name = $countdown.data( 'trigger' );
				if (typeof window[trigger_function_name] === 'function') {
					window[trigger_function_name]();
				}
			}
		} else if (total_seconds > 86400) { // More than 24 hours
			// format time as "X days hh:mm:ss".
			let time_str = ai4seo_format_time_with_days( total_seconds );
			$countdown.text( time_str );
		} else {
			// Format time as hh:mm:ss.
			let time_str = ai4seo_format_time( total_seconds );
			$countdown.text( time_str );
		}
        },
        1000
    );

	// mark element as initialized.
	$countdown.data( 'initialized', true );
}

// =========================================================================================== \\

/**
 * Format seconds into "X days and hh:mm:ss"
 */
function ai4seo_format_time_with_days(total_seconds) {
	let days = Math.floor( total_seconds / 86400 ); // 86400 = 24 * 60 * 60
	let remaining_seconds = total_seconds % 86400;

	let hours = Math.floor( remaining_seconds / 3600 );
	let minutes = Math.floor( (remaining_seconds % 3600) / 60 );
	let seconds = remaining_seconds % 60;

	let time_str =
		String( hours ).padStart( 2, '0' ) + ':' +
		String( minutes ).padStart( 2, '0' ) + ':' +
		String( seconds ).padStart( 2, '0' );

	if (days > 0) {
		time_str = wp.i18n.sprintf(
			wp.i18n._n( '%1$d day %2$s', '%1$d days %2$s', days, 'ai-for-seo' ),
			days,
			time_str
		);
	}

	return time_str;
}

// =========================================================================================== \\

/**
 * Parse a time string in hh:mm:ss format into total seconds
 */
function ai4seo_parse_time(time_text) {
	let parts = time_text.split( ':' );
	if (parts.length !== 3) {
		return NaN;
	}
	let hours = parseInt( parts[0], 10 );
	let minutes = parseInt( parts[1], 10 );
	let seconds = parseInt( parts[2], 10 );

	if (isNaN( hours ) || isNaN( minutes ) || isNaN( seconds )) {
		return NaN;
	}

	return hours * 3600 + minutes * 60 + seconds;
}

// =========================================================================================== \\

/**
 * Format total seconds into a time string hh:mm:ss
 */
function ai4seo_format_time(total_seconds) {
	let hours = Math.floor( total_seconds / 3600 );
	let minutes = Math.floor( (total_seconds % 3600) / 60 );
	let seconds = total_seconds % 60;

	return (
		String( hours ).padStart( 2, '0' ) +
		':' +
		String( minutes ).padStart( 2, '0' ) +
		':' +
		String( seconds ).padStart( 2, '0' )
	);
}

// =========================================================================================== \\

function ai4seo_reload_page() {
	window.location.reload();
}

// =========================================================================================== \\

/**
 * Init all our select all / unselect all checkboxes
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_select_all_checkboxes(scope = null) {
	// pre-check any select all checkbox, depending on the state of the checkboxes it controls (only if all child  checkboxes are checked, then the select all checkbox is checked).
	const $select_all_checkboxes = ai4seo_get_elements_in_scope_$( '.ai4seo-select-all-checkbox', scope );

	if (!ai4seo_exists_$( $select_all_checkboxes )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': no select-all-checkbox elements found in ai4seo_init_select_all_checkboxes() \u2014 cannot manage bulk selection.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': initializing ' + $select_all_checkboxes.length + ' select-all-checkbox elements in ai4seo_init_select_all_checkboxes().' );

	$select_all_checkboxes.each(
        function () {
		const $this_select_all_checkbox = ai4seo_normalize_$( this );

		const target_checkbox_name = $this_select_all_checkbox.data( 'target' );

		// if no target-checkbox-name is set, then skip this element.
		if (!target_checkbox_name) {
			console.warn( ai4seo_get_plugin_name() + ': No target-checkbox-name found for select-all-checkbox' );
			return;
		}

		// Build the selector once because enabled and disabled target states are handled separately below.
		const target_checkbox_selector = "input[type='checkbox'][name='" + target_checkbox_name + "[]']";

		// Prefer the owning form and modal before falling back to the dispatcher root.
		const $owning_form = $this_select_all_checkbox.closest( 'form' ).first();
		const $owning_modal = $this_select_all_checkbox.closest( '.ai4seo-modal' ).first();
		const target_scopes = [$owning_form, $owning_modal, scope || document];
		let $all_target_checkboxes = jQuery();
		let $all_target_checkboxes_including_disabled = jQuery();

		for (const target_scope of target_scopes) {
			if (!target_scope || (target_scope.jquery && !ai4seo_exists_$( target_scope ))) {
				continue;
			}

			const $this_scope_target_checkboxes = ai4seo_get_elements_in_scope_$( target_checkbox_selector, target_scope );

			if (!ai4seo_exists_$( $this_scope_target_checkboxes )) {
				continue;
			}

			$all_target_checkboxes_including_disabled = $this_scope_target_checkboxes;
			$all_target_checkboxes = $this_scope_target_checkboxes.filter( ':not(:disabled)' );
			break;
		}

		// if no target-checkbox-elements are found, then skip this element.
		if (!ai4seo_exists_$( $all_target_checkboxes )) {
			// Disabled-only target lists are valid during locked states, so remove stale handlers and keep the select-all control inert.
			if (ai4seo_exists_$( $all_target_checkboxes_including_disabled )) {
				$this_select_all_checkbox.off( 'change.ai4seo-checkboxes' );
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': No enabled target-checkbox-elements found for select-all-checkbox with target-checkbox-name: ' + target_checkbox_name );
				return;
			}

			// Remove stale select-all handlers when repeated initialization no longer finds matching targets.
			$this_select_all_checkbox.off( 'change.ai4seo-checkboxes' );
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': No target-checkbox-elements found for select-all-checkbox with target-checkbox-name: ' + target_checkbox_name );
			return;
		}

		// refresh the current state of the select all / unselect all checkbox.
		ai4seo_refresh_select_all_checkbox_state( $this_select_all_checkbox, $all_target_checkboxes );

		// add change event to all target-checkbox-elements.
		$this_select_all_checkbox.off( 'change.ai4seo-checkboxes' );
		$this_select_all_checkbox.on(
            'change.ai4seo-checkboxes',
            function () {
			const $this_checkbox = ai4seo_normalize_$( this );

			if (!ai4seo_exists_$( $this_checkbox )) {
				console.warn( ai4seo_get_plugin_name() + ': element \"$this_checkbox\" missing in ai4seo_init_select_all_checkboxes() \u2014 skipping item.' );
				return;
			}

			// Get the checked status of the "Select All / Unselect All" checkbox.
			const is_checked = $this_checkbox.prop( 'checked' );

			// Get all checkboxes with the specified name and apply the checked status.
			$all_target_checkboxes.prop( 'checked', is_checked ).change();
            }
        );

		// add change event to all target-checkbox-elements to refresh the state of the select all / unselect all checkbox.
		$all_target_checkboxes.off( 'change.ai4seo-checkboxes' );
		$all_target_checkboxes.on(
            'change.ai4seo-checkboxes',
            function () {
			ai4seo_refresh_select_all_checkbox_state( $this_select_all_checkbox, $all_target_checkboxes );
            }
        );
        }
    );
}

// =========================================================================================== \\

/**
 * Refresh the current state of the select all / unselect all checkbox
 */
function ai4seo_refresh_select_all_checkbox_state($select_all_checkbox, $all_target_checkboxes) {
	$select_all_checkbox = ai4seo_normalize_$( $select_all_checkbox );
	$all_target_checkboxes = ai4seo_normalize_$( $all_target_checkboxes );

	if (!ai4seo_exists_$( $select_all_checkbox ) || !ai4seo_exists_$( $all_target_checkboxes )) {
		console.warn( ai4seo_get_plugin_name() + ': elements "$select_all_checkbox" or "$all_target_checkboxes" missing in ai4seo_refresh_select_all_checkbox_state() — cannot sync select-all state.' );
		return;
	}

	// set the initial state of the select all checkbox.
	const num_checked_target_checkboxes = parseInt( $all_target_checkboxes.filter( ':checked' ).length );
	const num_all_target_checkboxes = parseInt( $all_target_checkboxes.length );

	// if there are more checked checkboxes, than unchecked checkboxes, then the "select all checkbox" is checked as well.
	$select_all_checkbox.prop( 'checked', num_all_target_checkboxes === num_checked_target_checkboxes );
}

// =========================================================================================== \\

let ai4seo_slider_input_event_namespace_counter = 0;

/**
 * Return the stable event namespace for a slider input.
 */
function ai4seo_get_slider_input_event_namespace($slider_input) {
	$slider_input = ai4seo_normalize_$( $slider_input );

	if (!ai4seo_exists_$( $slider_input )) {
		return '.ai4seo-slider-input';
	}

	const namespace_data_key = 'ai4seo-slider-input-event-namespace';
	const existing_namespace = $slider_input.data( namespace_data_key );

	if (existing_namespace) {
		return existing_namespace;
	}

	let namespace_suffix = String( $slider_input.attr( 'id' ) || '' ).replace( /[^a-zA-Z0-9_-]/g, '' );

	if (!namespace_suffix) {
		ai4seo_slider_input_event_namespace_counter++;
		namespace_suffix = 'instance-' + ai4seo_slider_input_event_namespace_counter;
	}

	// Store generated namespaces on the element so ID-less AJAX markup stays repeat-init safe.
	const slider_event_namespace = '.ai4seo-slider-input-' + namespace_suffix;
	$slider_input.data( namespace_data_key, slider_event_namespace );

	return slider_event_namespace;
}

// =========================================================================================== \\

/**
 * Init staged slider inputs that are rendered as radio groups.
 *
 * The radio implementation keeps future settings compatible with the existing input collection
 * helpers while the JS only synchronizes selected classes and the shared description preview.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_slider_inputs(scope = null) {
	// Collect all slider wrappers before binding per-instance namespaced events.
	const $slider_inputs = ai4seo_get_elements_in_scope_$( '.ai4seo-slider-input', scope );

	if (!ai4seo_exists_$( $slider_inputs )) {
		return;
	}

	$slider_inputs.each(
        function () {
		// Cache the related pieces once so hover, focus, and change handlers update the same UI.
		const $slider_input = ai4seo_normalize_$( this );
		const $slider_control = $slider_input.find( '.ai4seo-slider-input-control' ).first();
		const $slider_track = $slider_input.find( '.ai4seo-slider-input-track' ).first();
		const $stage_inputs = $slider_input.find( '.ai4seo-slider-input-radio' );
		const $stage_labels = $slider_input.find( '.ai4seo-slider-input-stage' );
		const $description = $slider_input.find( '.ai4seo-slider-input-selected-description' );
		const $description_text = $description.find( '.ai4seo-slider-input-selected-description-text' ).first();
		const $description_note = $description.find( '.ai4seo-slider-input-selected-note' ).first();
		const $description_note_text = $description.find( '.ai4seo-slider-input-selected-note-text' ).first();
		const slider_event_namespace = ai4seo_get_slider_input_event_namespace( $slider_input );
		let ai4seo_is_slider_pointer_active = false;

		// Broken slider markup should not break the rest of the settings page initialization.
		const is_slider_markup_complete = ai4seo_exists_$( $slider_control )
			&& ai4seo_exists_$( $slider_track )
			&& ai4seo_exists_$( $stage_inputs )
			&& ai4seo_exists_$( $description )
			&& ai4seo_exists_$( $description_text );

		if (!is_slider_markup_complete) {
			return;
		}

		// Show the hovered/focused/selected stage description in the persistent description area.
		function ai4seo_update_slider_description($input) {
			$input = ai4seo_normalize_$( $input );

			if (!ai4seo_exists_$( $input )) {
				return;
			}

			// Keep the visible guidance synchronized with the stage currently previewed or selected.
			$description_text.text( String( $input.attr( 'data-ai4seo-slider-description' ) || '' ) );

			// Stage notes are optional, so older slider markup can still use this initializer safely.
			if (ai4seo_exists_$( $description_note ) && ai4seo_exists_$( $description_note_text )) {
				const note_text = String( $input.attr( 'data-ai4seo-slider-note' ) || '' );

				$description_note_text.text( note_text );

				if (note_text) {
					$description_note.removeAttr( 'hidden' );
				} else {
					$description_note.attr( 'hidden', 'hidden' );
				}
			}
		}

		// Return whether a stage is visible for comparison but unavailable to the current subscription.
		function ai4seo_is_slider_stage_locked($input) {
			$input = ai4seo_normalize_$( $input );

			return ai4seo_exists_$( $input ) && $input.prop( 'disabled' );
		}

		// Keep paid stages discoverable while routing attempted selection into the existing upgrade flow.
		function ai4seo_open_slider_locked_stage_upgrade($input, event = null) {
			if (!ai4seo_is_slider_stage_locked( $input )) {
				return false;
			}

			if (event) {
				event.preventDefault();
				event.stopPropagation();
			}

			// Reset transient preview state before focus enters and later returns from the upgrade modal.
			ai4seo_is_slider_pointer_active = false;
			ai4seo_restore_selected_slider_description();
			ai4seo_open_get_more_credits_modal();
			return true;
		}

		// Remove the visual track preview without changing the currently selected radio.
		function ai4seo_clear_slider_stage_preview() {
			$stage_labels.removeClass( 'ai4seo-slider-input-stage-previewed' );
		}

		// Highlight the stage currently represented by a track hover or press.
		function ai4seo_mark_slider_stage_previewed($input) {
			$input = ai4seo_normalize_$( $input );

			if (!ai4seo_exists_$( $input )) {
				return;
			}

			ai4seo_clear_slider_stage_preview();
			$input.closest( '.ai4seo-slider-input-stage' ).addClass( 'ai4seo-slider-input-stage-previewed' );
		}

		// Mirror the checked radio state with a class for the custom marker styling.
		function ai4seo_mark_slider_stage_selected($input) {
			$input = ai4seo_normalize_$( $input );

			if (!ai4seo_exists_$( $input )) {
				return;
			}

			ai4seo_clear_slider_stage_preview();
			$stage_labels.removeClass( 'ai4seo-slider-input-stage-selected' );
			$input.closest( '.ai4seo-slider-input-stage' ).addClass( 'ai4seo-slider-input-stage-selected' );
			ai4seo_update_slider_description( $input );
		}

		// Return the description preview to the checked stage after hover/focus interaction ends.
		function ai4seo_restore_selected_slider_description() {
			if (ai4seo_is_slider_pointer_active) {
				return;
			}

			ai4seo_clear_slider_stage_preview();

			const $checked_input = $stage_inputs.filter( ':checked' ).first();

			if (ai4seo_exists_$( $checked_input )) {
				ai4seo_update_slider_description( $checked_input );
			}
		}

		// Extract mouse or touch coordinates from the current pointer event.
		function ai4seo_get_slider_pointer_coordinates(event) {
			const original_event = event.originalEvent || event;

			if (original_event.touches && original_event.touches.length > 0) {
				return {
					x: original_event.touches[0].clientX,
					y: original_event.touches[0].clientY,
				};
			}

			if (original_event.changedTouches && original_event.changedTouches.length > 0) {
				return {
					x: original_event.changedTouches[0].clientX,
					y: original_event.changedTouches[0].clientY,
				};
			}

			if (typeof event.clientX === 'number' && typeof event.clientY === 'number') {
				return {
					x: event.clientX,
					y: event.clientY,
				};
			}

			return null;
		}

		// Ignore native label interactions because those already select and preview their own radio.
		function ai4seo_is_slider_stage_event_target(event) {
			const $event_target = ai4seo_normalize_$( event.target );

			return ai4seo_exists_$( $event_target.closest( '.ai4seo-slider-input-stage' ) );
		}

		// Return the radio whose marker center is closest to the current track pointer position.
		function ai4seo_get_nearest_slider_stage_input(event) {
			const pointer_coordinates = ai4seo_get_slider_pointer_coordinates( event );
			const track_element = $slider_track.get( 0 );
			const slider_element = $slider_input.get( 0 );

			if (!pointer_coordinates || !track_element || !slider_element) {
				return null;
			}

			const track_rectangle = track_element.getBoundingClientRect();
			const slider_style = window.getComputedStyle( slider_element );
			const marker_size = parseFloat( slider_style.getPropertyValue( '--ai4seo-slider-marker-size' ) ) || 18;
			const track_hit_tolerance = Math.max( marker_size / 2, 8 );
			const is_vertical_slider = $slider_input.hasClass( 'ai4seo-slider-input-vertical' );

			// Keep track interactions local to the visible line/bar rather than the whole control area.
			if (is_vertical_slider) {
				if (pointer_coordinates.x < track_rectangle.left - track_hit_tolerance || pointer_coordinates.x > track_rectangle.right + track_hit_tolerance) {
					return null;
				}
			} else if (pointer_coordinates.y < track_rectangle.top - track_hit_tolerance || pointer_coordinates.y > track_rectangle.bottom + track_hit_tolerance) {
				return null;
			}

			let $nearest_input = null;
			let nearest_distance = Number.MAX_VALUE;

			// Nearest marker center naturally creates the requested half-and-half split between stages.
			$stage_inputs.each(
                function () {
				const $this_input = ai4seo_normalize_$( this );
				const marker_element = $this_input.siblings( '.ai4seo-slider-input-marker' ).first().get( 0 );

				if (!marker_element) {
					return;
				}

				const marker_rectangle = marker_element.getBoundingClientRect();
				const marker_center = is_vertical_slider
					? marker_rectangle.top + marker_rectangle.height / 2
					: marker_rectangle.left + marker_rectangle.width / 2;
				const pointer_position = is_vertical_slider ? pointer_coordinates.y : pointer_coordinates.x;
				const marker_distance = Math.abs( pointer_position - marker_center );

				if (marker_distance < nearest_distance) {
					nearest_distance = marker_distance;
					$nearest_input = $this_input;
				}
                }
            );

			return $nearest_input;
		}

		// Preview the nearest stage while hovering or pressing on the slider track.
		function ai4seo_preview_slider_track_stage(event) {
			if (ai4seo_is_slider_stage_event_target( event )) {
				return;
			}

			const $nearest_input = ai4seo_get_nearest_slider_stage_input( event );

			if (!ai4seo_exists_$( $nearest_input )) {
				ai4seo_restore_selected_slider_description();
				return;
			}

			ai4seo_mark_slider_stage_previewed( $nearest_input );
			ai4seo_update_slider_description( $nearest_input );
		}

		// Select the nearest stage when users click the track between explicit radio markers.
		function ai4seo_select_slider_track_stage(event) {
			if (ai4seo_is_slider_stage_event_target( event )) {
				return;
			}

			const $nearest_input = ai4seo_get_nearest_slider_stage_input( event );

			if (!ai4seo_exists_$( $nearest_input )) {
				return;
			}

			if (ai4seo_open_slider_locked_stage_upgrade( $nearest_input, event )) {
				return;
			}

			event.preventDefault();
			$nearest_input.prop( 'checked', true ).trigger( 'change' );
		}

		// Radio changes are the source of truth for selected state and future persisted values.
		$stage_inputs.off( 'change.ai4seo-slider-input' );
		$stage_inputs.on(
            'change.ai4seo-slider-input',
            function () {
			ai4seo_is_slider_pointer_active = false;

			// An explicit selection replaces any previously preserved unavailable paid stage.
			$slider_input.removeAttr( 'data-ai4seo-slider-preserved-value' );
			ai4seo_mark_slider_stage_selected( this );
            }
        );

		// Preview on pointer/focus start so mousedown does not briefly show the old description.
		$stage_labels.off( 'mouseenter.ai4seo-slider-input focusin.ai4seo-slider-input mousedown.ai4seo-slider-input touchstart.ai4seo-slider-input' );
		$stage_labels.on(
            'mouseenter.ai4seo-slider-input focusin.ai4seo-slider-input mousedown.ai4seo-slider-input touchstart.ai4seo-slider-input',
            function (event) {
			if (event.type === 'mousedown' || event.type === 'touchstart') {
				ai4seo_is_slider_pointer_active = true;
			}

			const $this_input = ai4seo_normalize_$( this ).find( '.ai4seo-slider-input-radio' ).first();

			// Locked stages expose their range through the button label without replacing the checked option's guidance.
			if (event.type === 'focusin' && ai4seo_is_slider_stage_locked( $this_input )) {
				ai4seo_restore_selected_slider_description();
				return;
			}

			ai4seo_update_slider_description( $this_input );
            }
        );

		// Locked stages remain keyboard-focusable buttons so pointer and keyboard users can reach the upgrade action.
		$stage_labels.off( 'click.ai4seo-slider-input-locked keydown.ai4seo-slider-input-locked' );
		$stage_labels.on(
            'click.ai4seo-slider-input-locked keydown.ai4seo-slider-input-locked',
            function (event) {
			const is_keyboard_activation = event.type === 'keydown'
				&& (event.key === 'Enter' || event.key === ' ' || event.keyCode === 13 || event.keyCode === 32);

			if (event.type === 'keydown' && !is_keyboard_activation) {
				return;
			}

			const $this_input = ai4seo_normalize_$( this ).find( '.ai4seo-slider-input-radio' ).first();
			ai4seo_open_slider_locked_stage_upgrade( $this_input, event );
            }
        );

		// Track pointer movement previews the nearest stage without changing the saved value.
		$slider_control.off( 'mouseenter' + slider_event_namespace + ' mousemove' + slider_event_namespace );
		$slider_control.on(
            'mouseenter' + slider_event_namespace + ' mousemove' + slider_event_namespace,
            function (event) {
			ai4seo_preview_slider_track_stage( event );
            }
        );

		// Track presses immediately preview the eventual click target for smoother feedback.
		$slider_control.off( 'mousedown' + slider_event_namespace + ' touchstart' + slider_event_namespace );
		$slider_control.on(
            'mousedown' + slider_event_namespace + ' touchstart' + slider_event_namespace,
            function (event) {
			if (ai4seo_is_slider_stage_event_target( event )) {
				return;
			}

			const $nearest_input = ai4seo_get_nearest_slider_stage_input( event );

			if (!ai4seo_exists_$( $nearest_input )) {
				return;
			}

			ai4seo_is_slider_pointer_active = true;
			ai4seo_mark_slider_stage_previewed( $nearest_input );
			ai4seo_update_slider_description( $nearest_input );
            }
        );

		// Track clicks map to the nearest radio while preserving normal radio change events.
		$slider_control.off( 'click' + slider_event_namespace );
		$slider_control.on(
            'click' + slider_event_namespace,
            function (event) {
			ai4seo_select_slider_track_stage( event );
            }
        );

		// Leaving the track area removes hover preview and restores the selected stage text.
		$slider_control.off( 'mouseleave' + slider_event_namespace );
		$slider_control.on(
            'mouseleave' + slider_event_namespace,
            function () {
			ai4seo_restore_selected_slider_description();
            }
        );

		// Mouseup/touchend may fire before the radio change, so restoration waits one tick.
		ai4seo_normalize_$( document ).off( 'mouseup' + slider_event_namespace + ' touchend' + slider_event_namespace + ' touchcancel' + slider_event_namespace );
		ai4seo_normalize_$( document ).on(
            'mouseup' + slider_event_namespace + ' touchend' + slider_event_namespace + ' touchcancel' + slider_event_namespace,
            function () {
			setTimeout(
                function () {
				ai4seo_is_slider_pointer_active = false;
				ai4seo_restore_selected_slider_description();
                },
                0
            );
            }
        );

		// When preview interaction ends normally, show the currently selected stage again.
		$stage_labels.off( 'mouseleave.ai4seo-slider-input focusout.ai4seo-slider-input' );
		$stage_labels.on(
            'mouseleave.ai4seo-slider-input focusout.ai4seo-slider-input',
            function () {
			ai4seo_restore_selected_slider_description();
            }
        );

		// Initial sync handles browser-restored radio state as well as server-rendered defaults.
		ai4seo_mark_slider_stage_selected( $stage_inputs.filter( ':checked' ).first() );
        }
    );
}

// =========================================================================================== \\

/**
 * Init shared content list search form keyboard controls.
 */
function ai4seo_init_content_list_search_forms() {
	const $content_list_search_forms = ai4seo_normalize_$( '.ai4seo-content-list-search-form' );

	if (!ai4seo_exists_$( $content_list_search_forms )) {
		return;
	}

	// Let Enter-triggered form submits share the same loading behavior as the visible Search button.
	$content_list_search_forms.off( 'submit.ai4seo-content-list-search' );
	$content_list_search_forms.on(
        'submit.ai4seo-content-list-search',
        function (event) {
		const $form = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $form )) {
			return true;
		}

		if (ai4seo_exists_$( $form.closest( '.ai4seo-related-attachments-modal-content' ) )) {
			return true;
		}

		const $submit_button = $form.find( '.ai4seo-content-list-search-submit' ).first();

		if (ai4seo_exists_$( $submit_button )) {
			ai4seo_add_loading_html_to_element( $submit_button );
		}

		ai4seo_show_full_page_loading_screen();

		return true;
        }
    );

	// Bind only the text input so Esc resets search text without interfering with selects or bulk actions.
	const $filter_text_inputs = $content_list_search_forms.find( "input[name='ai4seo_filter_text']" );

	if (!ai4seo_exists_$( $filter_text_inputs )) {
		return;
	}

	$filter_text_inputs.off( 'keydown.ai4seo-content-list-search' );
	$filter_text_inputs.on(
        'keydown.ai4seo-content-list-search',
        function (event) {
		const key = String( event.key || '' );
		const $input = ai4seo_normalize_$( this );
		const $form = $input.closest( '.ai4seo-content-list-search-form' );

		if (!ai4seo_exists_$( $form )) {
			return true;
		}

		if (key === 'Enter') {
			event.preventDefault();
			event.stopPropagation();
			ai4seo_submit_content_list_search_form( $form );
			return false;
		}

		if (key === 'Escape' || key === 'Esc') {
			const $reset_button = $form.find( '.ai4seo-content-list-search-reset-button' ).first();

			if (!ai4seo_exists_$( $reset_button )) {
				return true;
			}

			event.preventDefault();
			event.stopPropagation();
			ai4seo_click_content_list_search_reset_button( $reset_button );
			return false;
		}

		return true;
        }
    );
}

// =========================================================================================== \\

/**
 * Toggle the retry-all-failed action that belongs to a deferred content-list status filter.
 *
 * @param {HTMLElement|jQuery} status_filter_list
 * @param {boolean} should_show_retry_all_failed_button
 */
function ai4seo_set_content_type_retry_all_failed_button_visibility(status_filter_list, should_show_retry_all_failed_button) {
	const $status_filter_list = ai4seo_normalize_$( status_filter_list );

	if (!ai4seo_exists_$( $status_filter_list )) {
		return;
	}

	const retry_all_failed_button_target = String( $status_filter_list.attr( 'data-ai4seo-retry-all-failed-button-target' ) || '' );

	if (retry_all_failed_button_target === '') {
		return;
	}

	// Use the server-provided target id instead of inferring retry visibility from hydrated HTML.
	const retry_all_failed_button = document.getElementById( retry_all_failed_button_target );

	if (!retry_all_failed_button) {
		return;
	}

	const $retry_all_failed_button = ai4seo_normalize_$( retry_all_failed_button );

	if (!ai4seo_exists_$( $retry_all_failed_button )) {
		return;
	}

	// Persist hidden state as a class while clearing jQuery's temporary inline display value.
	if (should_show_retry_all_failed_button) {
		$retry_all_failed_button.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	} else {
		$retry_all_failed_button.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	}
}

// =========================================================================================== \\

/**
 * Read sanitized routing fields that PHP allowed the deferred status-filter hydration to preserve.
 *
 * @param {HTMLElement|jQuery} status_filter_list
 * @returns {Object}
 */
function ai4seo_get_content_type_status_filter_hydration_hidden_fields(status_filter_list) {
	const $status_filter_list = ai4seo_normalize_$( status_filter_list );

	if (!ai4seo_exists_$( $status_filter_list )) {
		return {};
	}

	const hidden_fields_json = String( $status_filter_list.attr( 'data-ai4seo-hydration-hidden-fields' ) || '' );

	if (hidden_fields_json === '') {
		return {};
	}

	try {
		const hidden_fields = JSON.parse( hidden_fields_json );

		if (hidden_fields && typeof hidden_fields === 'object' && !Array.isArray( hidden_fields )) {
			return hidden_fields;
		}
	} catch (error) {
		return {};
	}

	return {};
}

// =========================================================================================== \\

/**
 * Replace the deferred status-filter loading hint with a non-blocking inline error message.
 *
 * @param {HTMLElement|jQuery} status_filter_list
 * @param {Object} error
 */
function ai4seo_show_content_type_status_filter_hydration_error(status_filter_list, error = {}) {
	const $status_filter_list = ai4seo_normalize_$( status_filter_list );

	if (!ai4seo_exists_$( $status_filter_list )) {
		return;
	}

	const $loading_item = $status_filter_list.find( '.ai4seo-content-list-status-filter-loading' ).first();
	const has_existing_status_items = $status_filter_list.find( 'li' ).not( $loading_item ).length > 0;
	const $error_item = jQuery(
        '<li/>',
        {
		class: 'ai4seo-content-list-status-filter-error',
		'aria-live': 'polite',
        }
    );

	// Keep the row usable and make hydration failures visible without blocking row actions.
	if (has_existing_status_items) {
		$error_item.append( document.createTextNode( ' | ' ) );
	}

	$error_item.append(
		jQuery(
            '<span/>',
            {
			text: wp.i18n.__( 'Status filters could not be loaded.', 'ai-for-seo' ),
            }
        )
	);

	if (ai4seo_exists_$( $loading_item )) {
		$loading_item.replaceWith( $error_item );
	} else {
		$status_filter_list.append( $error_item );
	}

	console.warn( ai4seo_get_plugin_name() + ': Content type status filter hydration failed.', error );
}

// =========================================================================================== \\

/**
 * Hydrate deferred content-list status filters with exact counts.
 */
function ai4seo_init_content_type_status_filter_hydration() {
	const $status_filter_lists = ai4seo_normalize_$( '.ai4seo-content-list-status-filters[data-ai4seo-defer-status-filters="1"]' );

	if (!ai4seo_exists_$( $status_filter_lists )) {
		return;
	}

	$status_filter_lists.each(
        function () {
		const $status_filter_list = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $status_filter_list ) || $status_filter_list.attr( 'data-ai4seo-status-filters-loading' ) === '1') {
			return;
		}

		$status_filter_list.attr( 'data-ai4seo-status-filters-loading', '1' );

		const hydration_hidden_fields = ai4seo_get_content_type_status_filter_hydration_hidden_fields( $status_filter_list );

		// Send only the active UI state; PHP derives post types, MIME types, and disabled authors from settings.
		ai4seo_perform_ajax_call(
			'ai4seo_get_content_type_status_filters',
			{
				content_context: String( $status_filter_list.attr( 'data-ai4seo-content-context' ) || '' ),
				post_type: String( $status_filter_list.attr( 'data-ai4seo-post-type' ) || '' ),
				filter_text: String( $status_filter_list.attr( 'data-ai4seo-filter-text' ) || '' ),
				filter_status: String( $status_filter_list.attr( 'data-ai4seo-filter-status' ) || 'all' ),
				orderby: String( $status_filter_list.attr( 'data-ai4seo-orderby' ) || 'id' ),
				order: String( $status_filter_list.attr( 'data-ai4seo-order' ) || 'desc' ),
				hidden_fields: hydration_hidden_fields,
			},
			true,
			{},
			false,
			false
		)
			.then(
                function (response) {
				const status_filter_html = String( response?.status_filter_html || '' );
				const should_show_retry_all_failed_button = response?.show_retry_all_failed_button === true;

				if (status_filter_html !== '') {
					$status_filter_list.html( status_filter_html );
				}

				ai4seo_set_content_type_retry_all_failed_button_visibility( $status_filter_list, should_show_retry_all_failed_button );

				$status_filter_list.removeAttr( 'data-ai4seo-defer-status-filters' );
                }
            )
			.catch(
                function (error) {
				// Keep the cheap initial All count visible and leave row actions usable when hydration fails.
				ai4seo_show_content_type_status_filter_hydration_error( $status_filter_list, error );
				$status_filter_list.removeAttr( 'data-ai4seo-defer-status-filters' );
                }
            )
			.finally(
                function () {
				$status_filter_list.removeAttr( 'data-ai4seo-status-filters-loading' );
                }
            );
        }
    );
}

// =========================================================================================== \\

/**
 * Submit a shared content list search form through the browser form submit event.
 *
 * @param {HTMLElement|jQuery} search_form
 */
function ai4seo_submit_content_list_search_form(search_form) {
	const $form = ai4seo_normalize_$( search_form );

	if (!ai4seo_exists_$( $form ) || !$form.get( 0 )) {
		return;
	}

	// Prefer requestSubmit because it preserves native submit events and validation hooks.
	const form = $form.get( 0 );

	if (typeof form.requestSubmit === 'function') {
		form.requestSubmit();
		return;
	}

	$form.trigger( 'submit' );
}

// =========================================================================================== \\

/**
 * Trigger the text search reset control.
 *
 * @param {HTMLElement|jQuery} reset_button
 */
function ai4seo_click_content_list_search_reset_button(reset_button) {
	const $reset_button = ai4seo_normalize_$( reset_button );

	if (!ai4seo_exists_$( $reset_button )) {
		return;
	}

	// Trigger the actual reset control so existing onclick/loading behavior remains the source of truth.
	const reset_button_element = $reset_button.get( 0 );

	if (reset_button_element && typeof reset_button_element.click === 'function') {
		reset_button_element.click();
		return;
	}

	$reset_button.trigger( 'click' );
}

// =========================================================================================== \\

/**
 * Show the selected custom bulk action description without opening the full action reference.
 *
 * @param {HTMLElement|jQuery} form
 */
function ai4seo_update_bulk_generation_queue_action_description(form) {
	const $form = ai4seo_normalize_$( form );

	if (!ai4seo_exists_$( $form )) {
		return;
	}

	const $select = $form.find( '.ai4seo-bulk-generation-queue-action-select' ).first();
	const $description = $form.find( '.ai4seo-bulk-generation-queue-action-selected-description' ).first();
	const $description_text = $description.find( '.ai4seo-bulk-generation-queue-action-selected-description-text' ).first();

	if (!ai4seo_exists_$( $select ) || !ai4seo_exists_$( $description ) || !ai4seo_exists_$( $description_text )) {
		return;
	}

	const $selected_option = $select.find( 'option:selected' ).first();
	const selected_description = String( $selected_option.attr( 'data-ai4seo-description' ) || '' ).trim();

	$description_text.text( selected_description );
	$description.prop( 'hidden', selected_description === '' );
}

// =========================================================================================== \\

/**
 * Init contextual descriptions for custom SOOZ list bulk actions.
 *
 * @param {jQuery|Node|Window|null} scope Optional root for dynamically inserted list controls.
 */
function ai4seo_init_bulk_generation_queue_action_descriptions(scope = null) {
	const $bulk_generation_queue_action_forms = ai4seo_get_elements_in_scope_$( '.ai4seo-bulk-generation-queue-action-form', scope );

	if (!ai4seo_exists_$( $bulk_generation_queue_action_forms )) {
		return;
	}

	$bulk_generation_queue_action_forms.each(
        function () {
		const $form = ai4seo_normalize_$( this );
		const $select = $form.find( '.ai4seo-bulk-generation-queue-action-select' ).first();

		if (!ai4seo_exists_$( $form ) || !ai4seo_exists_$( $select )) {
			return;
		}

		$select.off( 'change.ai4seo-bulk-generation-queue-action-description' );
		$select.on(
            'change.ai4seo-bulk-generation-queue-action-description',
            function () {
			ai4seo_update_bulk_generation_queue_action_description( $form );
            }
        );

		// Initial sync supports browser-restored selections as well as freshly inserted modal controls.
		ai4seo_update_bulk_generation_queue_action_description( $form );
        }
    );
}

// =========================================================================================== \\

/**
 * Init custom SOOZ list bulk queue action forms.
 */
function ai4seo_init_bulk_generation_queue_action_forms() {
	const $bulk_generation_queue_action_forms = ai4seo_normalize_$( '.ai4seo-bulk-generation-queue-action-form' );

	if (!ai4seo_exists_$( $bulk_generation_queue_action_forms )) {
		return;
	}

	// Bind form submit once per init pass; modal/page refreshes call this function repeatedly.
	$bulk_generation_queue_action_forms.off( 'submit.ai4seo-bulk-generation-queue' );
	$bulk_generation_queue_action_forms.on(
        'submit.ai4seo-bulk-generation-queue',
        function (event) {
		event.preventDefault();
		ai4seo_submit_bulk_generation_queue_action_form( this );
        }
    );

	// The shared button helper renders type="button", so handle the Apply click explicitly.
	$bulk_generation_queue_action_forms.find( '.ai4seo-bulk-generation-queue-action-submit' ).off( 'click.ai4seo-bulk-generation-queue' );
	$bulk_generation_queue_action_forms.find( '.ai4seo-bulk-generation-queue-action-submit' ).on(
        'click.ai4seo-bulk-generation-queue',
        function (event) {
		event.preventDefault();
		ai4seo_submit_bulk_generation_queue_action_form( ai4seo_normalize_$( this ).closest( '.ai4seo-bulk-generation-queue-action-form' ) );
        }
    );
}

// =========================================================================================== \\

/**
 * Init native WordPress list confirmations for destructive SOOZ bulk actions.
 */
function ai4seo_init_native_bulk_generation_queue_action_forms() {
	const $forms = ai4seo_normalize_$( 'form' );

	if (!ai4seo_exists_$( $forms )) {
		return;
	}

	const $native_bulk_action_forms = $forms.filter(
        function () {
		const $this_form = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_form )) {
			return false;
		}

		return $this_form.find( "#bulk-action-selector-top, #bulk-action-selector-bottom, .bulkactions select[name='action'], .bulkactions select[name='action2']" ).length > 0;
        }
    );

	if (!ai4seo_exists_$( $native_bulk_action_forms )) {
		return;
	}

	$native_bulk_action_forms.off( 'submit.ai4seo-native-bulk-generation-queue' );
	$native_bulk_action_forms.on(
        'submit.ai4seo-native-bulk-generation-queue',
        function (event) {
		const bulk_generation_queue_action = ai4seo_get_selected_native_bulk_generation_queue_action( this );
		const context = ai4seo_get_native_bulk_generation_queue_context();

		// Modal-only actions need to collect extra input before native WordPress can submit the bulk form.
		if (ai4seo_is_bulk_custom_instructions_action( bulk_generation_queue_action )) {
			event.preventDefault();
			event.stopImmediatePropagation();

			const post_ids = ai4seo_get_selected_native_bulk_generation_queue_post_ids( this, context );
			ai4seo_open_bulk_custom_instructions_modal( bulk_generation_queue_action, context, post_ids );
			return false;
		}

		if (!ai4seo_confirm_bulk_generation_queue_action_if_needed( bulk_generation_queue_action, context )) {
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		}

		return true;
        }
    );
}

// =========================================================================================== \\

/**
 * Read the selected native WordPress bulk action.
 *
 * @param {HTMLElement|jQuery} native_bulk_action_form
 * @returns {string}
 */
function ai4seo_get_selected_native_bulk_generation_queue_action(native_bulk_action_form) {
	const $native_bulk_action_form = ai4seo_normalize_$( native_bulk_action_form );

	if (!ai4seo_exists_$( $native_bulk_action_form )) {
		return '';
	}

	const top_action = String( $native_bulk_action_form.find( "select[name='action']" ).val() || '' );

	if (top_action && top_action !== '-1') {
		return top_action;
	}

	const bottom_action = String( $native_bulk_action_form.find( "select[name='action2']" ).val() || '' );

	if (bottom_action && bottom_action !== '-1') {
		return bottom_action;
	}

	return '';
}

// =========================================================================================== \\

/**
 * Checks whether a bulk action must open the custom-instructions modal first.
 *
 * @param {string} bulk_generation_queue_action
 * @returns {boolean}
 */
function ai4seo_is_bulk_custom_instructions_action(bulk_generation_queue_action) {
	return String( bulk_generation_queue_action || '' ) === 'ai4seo_bulk_generation_set_custom_instructions';
}

// =========================================================================================== \\

/**
 * Read checked row IDs for a native WordPress bulk action form.
 *
 * @param {HTMLElement|jQuery} native_bulk_action_form
 * @param {string} context
 * @returns {number[]}
 */
function ai4seo_get_selected_native_bulk_generation_queue_post_ids(native_bulk_action_form, context = '') {
	const post_ids = [];
	const $native_bulk_action_form = ai4seo_normalize_$( native_bulk_action_form );

	if (!ai4seo_exists_$( $native_bulk_action_form )) {
		return post_ids;
	}

	// Native edit screens use post[], while the Media Library table uses media[].
	const checkbox_names = (context === 'attachment_attributes')
		? ['media[]', 'post[]']
		: ['post[]'];

	checkbox_names.forEach(
        function (checkbox_name) {
		const checkbox_selector = "input[type='checkbox'][name='" + checkbox_name + "']:checked:not(:disabled)";
		const $selected_checkboxes = $native_bulk_action_form.find( checkbox_selector );

		if (!ai4seo_exists_$( $selected_checkboxes )) {
			return;
		}

		// Convert native row checkbox values to integer IDs and dedupe across top/bottom bulk controls.
		$selected_checkboxes.each(
            function () {
			const post_id = parseInt( ai4seo_normalize_$( this ).val(), 10 );

			if (post_id > 0 && !post_ids.includes( post_id )) {
				post_ids.push( post_id );
			}
            }
        );
        }
    );

	return post_ids;
}

// =========================================================================================== \\

/**
 * Open the bulk custom-instructions modal for selected post IDs.
 *
 * @param {string} bulk_generation_queue_action
 * @param {string} context
 * @param {number[]} post_ids
 * @returns {boolean}
 */
function ai4seo_open_bulk_custom_instructions_modal(bulk_generation_queue_action, context, post_ids) {
	// Normalize caller-provided selections because this helper is shared by native and SOOZ list forms.
	post_ids = Array.isArray( post_ids ) ? post_ids : [];

	if (!post_ids.length) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select at least one entry.', 'ai-for-seo' ) );
		return false;
	}

	// The modal gathers the text before the server mutates entry-level postmeta.
	ai4seo_open_ajax_modal(
        'ai4seo_show_bulk_custom_instructions_modal',
        {
		bulk_generation_queue_action: bulk_generation_queue_action,
		context: context,
		post_ids: post_ids,
        },
        {
		modal_id: 'ai4seo-bulk-custom-instructions-modal',
		modal_size: 'small',
		unsaved_changes_warnings: true,
        }
    );

	return true;
}

// =========================================================================================== \\

/**
 * Return the current native WordPress bulk action context.
 *
 * @returns {string}
 */
function ai4seo_get_native_bulk_generation_queue_context() {
	const $body = ai4seo_normalize_$( 'body' );
	const current_path = String( window.location.pathname || '' );
	const is_media_library_screen = (
		current_path.indexOf( 'upload.php' ) !== -1
		|| (ai4seo_exists_$( $body ) && ($body.hasClass( 'upload-php' ) || $body.hasClass( 'post-type-attachment' )))
	);

	return is_media_library_screen ? 'attachment_attributes' : 'metadata';
}

// =========================================================================================== \\

/**
 * Confirm destructive SOOZ bulk actions before submission.
 *
 * @param {string} bulk_generation_queue_action
 * @param {string} context
 * @returns {boolean}
 */
function ai4seo_confirm_bulk_generation_queue_action_if_needed(bulk_generation_queue_action, context = '') {
	const confirmation_message = ai4seo_get_bulk_generation_queue_action_confirmation_message( bulk_generation_queue_action, context );

	if (!confirmation_message) {
		return true;
	}

	return window.confirm( confirmation_message );
}

// =========================================================================================== \\

/**
 * Return the confirmation message for a destructive SOOZ bulk action.
 *
 * @param {string} bulk_generation_queue_action
 * @param {string} context
 * @returns {string}
 */
function ai4seo_get_bulk_generation_queue_action_confirmation_message(bulk_generation_queue_action, context = '') {
	switch (bulk_generation_queue_action) {
		case 'ai4seo_bulk_generation_remove_generated_data':
			if (context === 'attachment_attributes') {
				return wp.i18n.__( 'Saved data may still be stored in the media library.\n\nThis action marks the selected media files as not generated, so SEO Autopilot can auto queue them again if applicable.\n\nContinue?', 'ai-for-seo' );
			}

			return wp.i18n.__( 'Saved data may still be visible as meta tags, especially if it has been synced to third-party SEO plugins.\n\nThis action marks the selected entries as not generated, so SEO Autopilot can auto queue them again if applicable.\n\nContinue?', 'ai-for-seo' );

		case 'ai4seo_bulk_generation_remove_saved_data':
			return wp.i18n.__( 'Third-party SEO plugins may still contain previously synced saved data.\n\nContinue?', 'ai-for-seo' );
	}

	return '';
}

// =========================================================================================== \\

/**
 * Submit a custom SOOZ list bulk queue action form.
 *
 * @param {HTMLElement|jQuery} bulk_generation_queue_action_form
 * @returns {boolean}
 */
function ai4seo_submit_bulk_generation_queue_action_form(bulk_generation_queue_action_form) {
	const $bulk_generation_queue_action_form = ai4seo_normalize_$( bulk_generation_queue_action_form );

	if (!ai4seo_exists_$( $bulk_generation_queue_action_form )) {
		console.error( ai4seo_get_plugin_name() + ': element "$bulk_generation_queue_action_form" missing in ai4seo_submit_bulk_generation_queue_action_form() — cannot submit bulk queue action.' );
		return false;
	}

	// Read the PHP-rendered custom list context so AJAX validates the same action set as the visible dropdown.
	const context = String( $bulk_generation_queue_action_form.find( '.ai4seo-bulk-generation-queue-context' ).val() || '' );
	const checkbox_name = String( $bulk_generation_queue_action_form.find( '.ai4seo-bulk-generation-queue-checkbox-name' ).val() || '' );
	const active_status_filter = String( $bulk_generation_queue_action_form.find( '.ai4seo-bulk-generation-queue-active-status-filter' ).val() || 'all' );
	const bulk_generation_queue_action = String( $bulk_generation_queue_action_form.find( '.ai4seo-bulk-generation-queue-action-select' ).val() || '' );
	const $submit_button = $bulk_generation_queue_action_form.find( '.ai4seo-bulk-generation-queue-action-submit' );

	if (!bulk_generation_queue_action) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a bulk action.', 'ai-for-seo' ) );
		return false;
	}

	if (!context || !checkbox_name) {
		ai4seo_show_error_toast( 5106062601, wp.i18n.__( 'Bulk action controls are incomplete. Please refresh the page and try again.', 'ai-for-seo' ) );
		return false;
	}

	const $checkbox_scope = $bulk_generation_queue_action_form.closest( '.ai4seo-related-attachments-modal-content' );
	const post_ids = ai4seo_get_selected_bulk_generation_queue_post_ids( checkbox_name, $checkbox_scope );

	if (!post_ids.length) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select at least one entry.', 'ai-for-seo' ) );
		return false;
	}

	// Custom instructions cannot be submitted through the queue endpoint because the textarea value is required first.
	if (ai4seo_is_bulk_custom_instructions_action( bulk_generation_queue_action )) {
		return ai4seo_open_bulk_custom_instructions_modal( bulk_generation_queue_action, context, post_ids );
	}

	if (!ai4seo_confirm_bulk_generation_queue_action_if_needed( bulk_generation_queue_action, context )) {
		return false;
	}

	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	ai4seo_perform_ajax_call(
        'ai4seo_apply_bulk_generation_queue_action',
        {
		bulk_generation_queue_action: bulk_generation_queue_action,
		context: context,
		active_status_filter: active_status_filter,
		post_ids: post_ids,
        }
    )
		.then(
            response => {
			ai4seo_handle_bulk_generation_queue_action_success( response, $bulk_generation_queue_action_form );
            }
        )
		.catch(
            () => {
			// The shared AJAX handler already shows the detailed error toast.
            }
        )
		.finally(
            () => {
			ai4seo_remove_loading_html_from_element( $submit_button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );

	return true;
}

// =========================================================================================== \\

/**
 * Read checked row IDs for a custom SOOZ list bulk queue action.
 *
 * @param {string} checkbox_name
 * @param {HTMLElement|jQuery|null} scope
 * @returns {number[]}
 */
function ai4seo_get_selected_bulk_generation_queue_post_ids(checkbox_name, scope = null) {
	const post_ids = [];
	const checkbox_selector = "input[type='checkbox'][name='" + checkbox_name + "[]']:checked:not(:disabled)";
	const $scope = ai4seo_normalize_$( scope );
	const $selected_checkboxes = ai4seo_exists_$( $scope )
		? $scope.find( checkbox_selector )
		: ai4seo_normalize_$( checkbox_selector );

	if (!ai4seo_exists_$( $selected_checkboxes )) {
		return post_ids;
	}

	// Convert checkbox values to integer post IDs and drop malformed values before AJAX.
	$selected_checkboxes.each(
        function () {
		const post_id = parseInt( ai4seo_normalize_$( this ).val(), 10 );

		if (post_id > 0 && !post_ids.includes( post_id )) {
			post_ids.push( post_id );
		}
        }
    );

	return post_ids;
}

// =========================================================================================== \\

// The confirmation modal is separate from the AJAX form modal, so keep its pending save payload explicit.
let ai4seo_pending_bulk_custom_instructions_payload = null;

// =========================================================================================== \\

/**
 * Submit the bulk custom-instructions modal after user confirmation.
 *
 * @param {HTMLElement|jQuery} submit_element
 * @returns {boolean}
 */
function ai4seo_apply_bulk_custom_instructions_action(submit_element) {
	const payload = ai4seo_get_bulk_custom_instructions_action_payload( submit_element );

	if (!payload) {
		return false;
	}

	// Require explicit confirmation because the bulk action overwrites or clears entry-level postmeta.
	ai4seo_open_bulk_custom_instructions_confirmation_modal( payload );

	return true;
}

// =========================================================================================== \\

/**
 * Collect and validate the bulk custom-instructions modal payload.
 *
 * @param {HTMLElement|jQuery} submit_element
 * @returns {Object|null}
 */
function ai4seo_get_bulk_custom_instructions_action_payload(submit_element) {
	const $submit_button = ai4seo_normalize_$( submit_element );

	if (!ai4seo_exists_$( $submit_button )) {
		console.error( ai4seo_get_plugin_name() + ': element "$submit_button" missing in ai4seo_get_bulk_custom_instructions_action_payload() — cannot submit modal.' );
		return null;
	}

	// The submit button lives in the AJAX modal footer, so resolve the form from the active modal wrapper.
	const $modal = $submit_button.closest( '.ai4seo-modal' );

	if (!ai4seo_exists_$( $modal )) {
		ai4seo_show_error_toast( 1507062614, wp.i18n.__( 'Bulk custom-instructions modal is incomplete. Please refresh the page and try again.', 'ai-for-seo' ) );
		return null;
	}

	const $form = $modal.find( '.ai4seo-bulk-custom-instructions-form' ).first();

	if (!ai4seo_exists_$( $form )) {
		ai4seo_show_error_toast( 1507062604, wp.i18n.__( 'Bulk custom-instructions modal is incomplete. Please refresh the page and try again.', 'ai-for-seo' ) );
		return null;
	}

	// Keep the modal payload explicit instead of relying on the original list form after AJAX insertion.
	const bulk_generation_queue_action = String( $form.find( '.ai4seo-bulk-custom-instructions-action' ).val() || '' );
	const context = String( $form.find( '.ai4seo-bulk-custom-instructions-context' ).val() || '' );
	const $custom_instructions_input = $form.find( '.ai4seo-bulk-custom-instructions-input' ).first();

	if (!ai4seo_exists_$( $custom_instructions_input )) {
		ai4seo_show_error_toast( 1507062605, wp.i18n.__( 'Bulk custom-instructions modal data is incomplete. Please refresh the page and try again.', 'ai-for-seo' ) );
		return null;
	}

	const custom_instructions_input_name = String( $custom_instructions_input.attr( 'name' ) || 'ai4seo_bulk_custom_instructions' );
	const raw_custom_instructions = String( $custom_instructions_input.val() || '' );
	const post_ids = [];

	// Hidden IDs are generated by the modal display file so selections cannot change while the modal is open.
	$form.find( '.ai4seo-bulk-custom-instructions-post-id' ).each(
        function () {
		const post_id = parseInt( ai4seo_normalize_$( this ).val(), 10 );

		if (post_id > 0 && !post_ids.includes( post_id )) {
			post_ids.push( post_id );
		}
        }
    );

	if (!bulk_generation_queue_action || !context || !post_ids.length) {
		ai4seo_show_error_toast( 1507062605, wp.i18n.__( 'Bulk custom-instructions modal data is incomplete. Please refresh the page and try again.', 'ai-for-seo' ) );
		return null;
	}

	// Validate with the existing custom-instruction length helper before sending the final save request.
	if (!ai4seo_validate_custom_instruction_input_values(
        {
		[custom_instructions_input_name]: raw_custom_instructions,
        },
        1507062606
    )) {
		return null;
	}

	return {
		bulk_generation_queue_action: bulk_generation_queue_action,
		context: context,
		post_ids: post_ids,
		custom_instructions: raw_custom_instructions.trim(),
	};
}

// =========================================================================================== \\

/**
 * Show the final confirmation before bulk custom-instructions postmeta is changed.
 *
 * @param {Object} payload
 */
function ai4seo_open_bulk_custom_instructions_confirmation_modal(payload) {
	// Recount the captured selection because the original list checkboxes may change while the modal is open.
	const selected_entries_count = Array.isArray( payload.post_ids ) ? payload.post_ids.length : 0;

	if (selected_entries_count <= 0) {
		ai4seo_pending_bulk_custom_instructions_payload = null;
		ai4seo_show_error_toast( 1507062608, wp.i18n.__( 'Bulk custom-instructions confirmation data is incomplete. Please close the modal and try again.', 'ai-for-seo' ) );
		return;
	}

	ai4seo_pending_bulk_custom_instructions_payload = payload;

	// Build the stacked confirmation modal from the already-validated payload so the submit path stays linear.
	const confirmation_details = ai4seo_get_bulk_custom_instructions_confirmation_details( payload, selected_entries_count );
	const modal_footer = ai4seo_get_bulk_custom_instructions_confirmation_footer( confirmation_details.confirm_button_label );

	ai4seo_open_notification_modal(
		wp.i18n.__( 'Please confirm', 'ai-for-seo' ),
		confirmation_details.content,
		modal_footer,
		{
			close_on_outside_click: false,
			add_close_button: false,
		}
	);
}

// =========================================================================================== \\

/**
 * Build the confirmation copy and primary button label for the pending custom-instructions write.
 *
 * @param {Object} payload
 * @param {number} selected_entries_count
 * @returns {{content: string, confirm_button_label: string}}
 */
function ai4seo_get_bulk_custom_instructions_confirmation_details(payload, selected_entries_count) {
	const is_clearing_custom_instructions = String( payload.custom_instructions || '' ) === '';

	// Empty normalized instructions mean the bulk action will clear entry-level postmeta for every valid selection.
	if (is_clearing_custom_instructions) {
		const clear_content = wp.i18n.sprintf(
			wp.i18n._n(
				'Are you sure you want to empty all custom instructions for %d selected entry?',
				'Are you sure you want to empty all custom instructions for %d selected entries?',
				selected_entries_count,
				'ai-for-seo'
			),
			selected_entries_count
		);

		return {
			content: clear_content,
			confirm_button_label: wp.i18n.__( 'Yes, empty instructions', 'ai-for-seo' ),
		};
	}

	// Non-empty instructions are escaped before insertion because notification modal content accepts HTML.
	const save_content = wp.i18n.sprintf(
		wp.i18n._n(
			'Are you sure you want to set custom instructions for %d selected entry to:',
			'Are you sure you want to set custom instructions for %d selected entries to:',
			selected_entries_count,
			'ai-for-seo'
		),
		selected_entries_count
	) + "<div class='ai4seo-bulk-custom-instructions-confirmation-preview'>" + ai4seo_escape_html( payload.custom_instructions ) + '</div>';

	return {
		content: save_content,
		confirm_button_label: wp.i18n.__( 'Yes, set instructions', 'ai-for-seo' ),
	};
}

// =========================================================================================== \\

/**
 * Build the stacked confirmation footer that triggers or cancels the pending custom-instructions write.
 *
 * @param {string} confirm_button_label
 * @returns {string}
 */
function ai4seo_get_bulk_custom_instructions_confirmation_footer(confirm_button_label) {
	// Notification modals use inline handlers throughout the plugin because their footer is rendered from an HTML string.
	return "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_cancel_bulk_custom_instructions_confirmation(this);'>" + wp.i18n.__( 'Cancel', 'ai-for-seo' ) + '</button>'
		+ "<button type='button' class='ai4seo-button ai4seo-primary-button ai4seo-lockable' onclick='ai4seo_confirm_bulk_custom_instructions_action(this);'>" + confirm_button_label + '</button>';
}

// =========================================================================================== \\

/**
 * Cancel the pending bulk custom-instructions confirmation.
 *
 * @param {HTMLElement|jQuery} cancel_element
 * @returns {boolean}
 */
function ai4seo_cancel_bulk_custom_instructions_confirmation(cancel_element) {
	ai4seo_pending_bulk_custom_instructions_payload = null;
	ai4seo_close_modal_by_child( cancel_element );

	return true;
}

// =========================================================================================== \\

/**
 * Apply the pending bulk custom-instructions action after confirmation.
 *
 * @param {HTMLElement|jQuery} confirm_element
 * @returns {boolean}
 */
function ai4seo_confirm_bulk_custom_instructions_action(confirm_element) {
	const $confirm_button = ai4seo_normalize_$( confirm_element );
	const payload = ai4seo_pending_bulk_custom_instructions_payload;

	// Guard the second-step confirmation path so stale notification buttons cannot submit incomplete data.
	if (!ai4seo_exists_$( $confirm_button ) || !payload || !payload.bulk_generation_queue_action || !payload.context || !Array.isArray( payload.post_ids ) || !payload.post_ids.length) {
		ai4seo_show_error_toast( 1507062607, wp.i18n.__( 'Bulk custom-instructions confirmation data is incomplete. Please close the modal and try again.', 'ai-for-seo' ) );
		return false;
	}

	// Lock the UI only after the explicit confirmation so users can still edit or cancel from the first modal.
	ai4seo_add_loading_html_to_element( $confirm_button );
	ai4seo_lock_and_disable_lockable_input_fields();
	ai4seo_show_loading_toast( wp.i18n.__( 'Saving custom instructions...', 'ai-for-seo' ) );

	// Submit through the dedicated postmeta endpoint; the generic queue endpoint rejects modal-only actions.
	ai4seo_perform_ajax_call(
        'ai4seo_apply_bulk_custom_instructions_action',
        {
		bulk_generation_queue_action: payload.bulk_generation_queue_action,
		context: payload.context,
		post_ids: payload.post_ids,
		custom_instructions: payload.custom_instructions,
        }
    )
		.then(
            response => {
			ai4seo_pending_bulk_custom_instructions_payload = null;
			ai4seo_close_notification_modal();
			ai4seo_handle_bulk_custom_instructions_action_success( response );
            }
        )
		.catch(
            () => {
			// The shared AJAX handler already shows the detailed error toast.
            }
        )
		.finally(
            () => {
			ai4seo_remove_loading_html_from_element( $confirm_button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );

	return true;
}

// =========================================================================================== \\

/**
 * Show the bulk custom-instructions result and refresh changed lists.
 *
 * @param {Object} response
 */
function ai4seo_handle_bulk_custom_instructions_action_success(response) {
	// The processor reports selected-entry outcomes, not queue mutations, so build a custom toast.
	const changed_entries = parseInt( response?.changed || 0, 10 );
	const skipped_entries = parseInt( response?.skipped || 0, 10 );
	const custom_instructions_cleared = response?.custom_instructions_cleared === true || response?.custom_instructions_cleared === '1';
	const changed_entries_label = wp.i18n._n( 'entry', 'entries', changed_entries, 'ai-for-seo' );
	const skipped_entries_label = wp.i18n._n( 'entry', 'entries', skipped_entries, 'ai-for-seo' );

	// Mirror the PHP result semantics: empty textarea clears, non-empty textarea saves for each valid selected entry.
	const message_parts = [
		custom_instructions_cleared
			? wp.i18n.sprintf(
				wp.i18n.__( 'Custom instructions cleared for %1$d %2$s.', 'ai-for-seo' ),
				changed_entries,
				changed_entries_label
			)
			: wp.i18n.sprintf(
				wp.i18n.__( 'Custom instructions saved for %1$d %2$s.', 'ai-for-seo' ),
				changed_entries,
				changed_entries_label
			)
	];

	if (skipped_entries > 0) {
		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s skipped.', 'ai-for-seo' ),
                skipped_entries,
                skipped_entries_label
            )
        );
	}

	const message = message_parts.join( ' ' );

	// Successful AJAX completion owns the modal lifecycle, even when all selected rows were skipped as invalid.
	ai4seo_set_unsaved_changes_state( '#ai4seo-bulk-custom-instructions-modal', false );
	ai4seo_close_ajax_modal( 'ai4seo-bulk-custom-instructions-modal' );

	if (changed_entries > 0) {
		ai4seo_show_success_toast( message );

		// Reload after successful postmeta changes so editor buttons and row state are rendered from fresh PHP state.
		setTimeout(
            function () {
			ai4seo_reload_page();
            },
            800
        );

		return;
	}

	ai4seo_show_warning_toast( message );
}

// =========================================================================================== \\

/**
 * Show the bulk queue action result and refresh changed lists.
 *
 * @param {Object} response
 * @param {HTMLElement|jQuery|null} bulk_generation_queue_action_form
 */
function ai4seo_handle_bulk_generation_queue_action_success(response, bulk_generation_queue_action_form = null) {
	const action = String( response?.action || '' );
	const changed_entries = parseInt( response?.changed || 0, 10 );
	const not_applicable_entries = parseInt( response?.not_applicable || 0, 10 );
	const skipped_entries = parseInt( response?.skipped || 0, 10 );
	const generated_data_deleted_entries = parseInt( response?.generated_data_deleted || 0, 10 );
	const active_metadata_deleted_entries = parseInt( response?.active_metadata_deleted || 0, 10 );
	const is_remove_generated_data_action = action === 'ai4seo_bulk_generation_remove_generated_data';
	const is_remove_saved_data_action = action === 'ai4seo_bulk_generation_remove_saved_data';

	// Related-image actions use a separate toast because selected source entries and affected media differ.
	const is_related_attachment_remove_queue_action = action === 'ai4seo_bulk_generation_remove_related_attachments_from_queue';
	const is_related_attachment_queue_action = action === 'ai4seo_bulk_generation_add_related_attachments_to_queue'
		|| action === 'ai4seo_bulk_generation_add_related_attachments_to_queue_force_overwrite'
		|| is_related_attachment_remove_queue_action;
	const is_data_removal_action = is_remove_generated_data_action || is_remove_saved_data_action;

	if (is_data_removal_action) {
		ai4seo_handle_bulk_generation_data_removal_action_success(
			generated_data_deleted_entries,
			active_metadata_deleted_entries,
			skipped_entries,
			is_remove_generated_data_action,
			is_remove_saved_data_action,
			bulk_generation_queue_action_form
		);
		return;
	}

	if (is_related_attachment_queue_action) {
		ai4seo_handle_bulk_generation_related_attachments_action_success( response, bulk_generation_queue_action_form, is_related_attachment_remove_queue_action );
		return;
	}

	const changed_entries_label = wp.i18n._n( 'entry', 'entries', changed_entries, 'ai-for-seo' );
	const not_applicable_entries_label = wp.i18n._n( 'entry', 'entries', not_applicable_entries, 'ai-for-seo' );
	const skipped_entries_label = wp.i18n._n( 'entry', 'entries', skipped_entries, 'ai-for-seo' );
	const message_parts = [
		wp.i18n.sprintf(
			wp.i18n.__( '%1$d %2$s changed.', 'ai-for-seo' ),
			changed_entries,
			changed_entries_label
		)
	];

	if (not_applicable_entries > 0) {
		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s not applicable.', 'ai-for-seo' ),
                not_applicable_entries,
                not_applicable_entries_label
            )
        );
	}

	message_parts.push(
        wp.i18n.sprintf(
            wp.i18n.__( '%1$d %2$s skipped.', 'ai-for-seo' ),
            skipped_entries,
            skipped_entries_label
        )
    );

	const message = message_parts.join( ' ' );

	if (changed_entries > 0) {
		ai4seo_show_success_toast( message );

		if (ai4seo_try_refresh_related_attachments_modal_after_bulk_action( bulk_generation_queue_action_form )) {
			return;
		}

		// Reload after the toast is queued so status labels and queue membership are rendered from fresh PHP state.
		setTimeout(
            function () {
			ai4seo_reload_page();
            },
            800
        );

		return;
	}

	ai4seo_show_warning_toast( message );
}

// =========================================================================================== \\

/**
 * Show the related-image bulk action result and refresh changed lists.
 *
 * @param {Object} response
 * @param {HTMLElement|jQuery|null} bulk_generation_queue_action_form
 * @param {boolean} is_related_attachment_remove_queue_action
 */
function ai4seo_handle_bulk_generation_related_attachments_action_success(response, bulk_generation_queue_action_form = null, is_related_attachment_remove_queue_action = false) {
	// Related-image responses include source-scan counts plus discovered attachment queue counts.
	const selected_source_entries = parseInt( response?.selected || 0, 10 );
	const related_source_entries_scanned = parseInt( response?.related_source_entries_scanned || 0, 10 );
	const related_images_found = parseInt( response?.related_images_found || 0, 10 );
	const related_images_changed = parseInt( response?.related_images_changed || response?.changed || 0, 10 );
	const related_images_skipped = parseInt( response?.related_images_skipped || response?.skipped || 0, 10 );
	const related_sources_without_images = parseInt( response?.related_sources_without_images || 0, 10 );
	const related_partial_scans = parseInt( response?.related_partial_scans || 0, 10 );
	const selected_source_entries_label = wp.i18n._n( 'source entry', 'source entries', selected_source_entries, 'ai-for-seo' );
	const scanned_source_entries_label = wp.i18n._n( 'source entry', 'source entries', related_source_entries_scanned, 'ai-for-seo' );
	const related_images_found_label = wp.i18n._n( 'related image', 'related images', related_images_found, 'ai-for-seo' );
	const related_images_changed_label = wp.i18n._n( 'related image', 'related images', related_images_changed, 'ai-for-seo' );
	const related_images_skipped_label = wp.i18n._n( 'related image', 'related images', related_images_skipped, 'ai-for-seo' );
	const message_parts = [
		wp.i18n.sprintf(
			wp.i18n.__( '%1$d %2$s selected.', 'ai-for-seo' ),
			selected_source_entries,
			selected_source_entries_label
		),
		wp.i18n.sprintf(
			wp.i18n.__( '%1$d %2$s scanned.', 'ai-for-seo' ),
			related_source_entries_scanned,
			scanned_source_entries_label
		),
		wp.i18n.sprintf(
			wp.i18n.__( '%1$d %2$s found.', 'ai-for-seo' ),
			related_images_found,
			related_images_found_label
		),
		wp.i18n.sprintf(
			is_related_attachment_remove_queue_action
				? wp.i18n.__( '%1$d %2$s removed from queue.', 'ai-for-seo' )
				: wp.i18n.__( '%1$d %2$s queued or promoted.', 'ai-for-seo' ),
			related_images_changed,
			related_images_changed_label
		),
		wp.i18n.sprintf(
			wp.i18n.__( '%1$d %2$s skipped.', 'ai-for-seo' ),
			related_images_skipped,
			related_images_skipped_label
		)
	];

	// Scanner edge-case details stay out of the toast unless they affected this action.
	if (related_sources_without_images > 0) {
		const related_sources_without_images_label = wp.i18n._n( 'source entry', 'source entries', related_sources_without_images, 'ai-for-seo' );

		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s had no related images.', 'ai-for-seo' ),
                related_sources_without_images,
                related_sources_without_images_label
            )
        );
	}

	if (related_partial_scans > 0) {
		const related_partial_scans_label = wp.i18n._n( 'source entry', 'source entries', related_partial_scans, 'ai-for-seo' );

		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s had partial related-media scans.', 'ai-for-seo' ),
                related_partial_scans,
                related_partial_scans_label
            )
        );
	}

	const message = message_parts.join( ' ' );

	if (related_images_changed > 0) {
		ai4seo_show_success_toast( message );

		// Reload because media queue state changes are not fully represented in the current post table DOM.
		setTimeout(
            function () {
			ai4seo_reload_page();
            },
            800
        );

		return;
	}

	ai4seo_show_warning_toast( message );
}

// =========================================================================================== \\

/**
 * Show the data-removal bulk action result and refresh changed lists.
 *
 * @param {number} generated_data_deleted_entries
 * @param {number} active_metadata_deleted_entries
 * @param {number} skipped_entries
 * @param {boolean} is_remove_generated_data_action
 * @param {boolean} is_remove_saved_data_action
 * @param {HTMLElement|jQuery|null} bulk_generation_queue_action_form
 */
function ai4seo_handle_bulk_generation_data_removal_action_success(generated_data_deleted_entries, active_metadata_deleted_entries, skipped_entries, is_remove_generated_data_action, is_remove_saved_data_action, bulk_generation_queue_action_form = null) {
	const generated_data_deleted_entries_label = wp.i18n._n( 'generated data entry', 'generated data entries', generated_data_deleted_entries, 'ai-for-seo' );
	const active_metadata_deleted_entries_label = wp.i18n._n( 'active meta entry', 'active meta entries', active_metadata_deleted_entries, 'ai-for-seo' );
	const skipped_entries_label = wp.i18n._n( 'entry', 'entries', skipped_entries, 'ai-for-seo' );
	const deleted_entries = generated_data_deleted_entries + active_metadata_deleted_entries;
	const message_parts = [];

	if (is_remove_generated_data_action) {
		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s deleted from wp_postmeta.', 'ai-for-seo' ),
                generated_data_deleted_entries,
                generated_data_deleted_entries_label
            )
        );
	}

	if (is_remove_saved_data_action) {
		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s deleted from wp_postmeta.', 'ai-for-seo' ),
                active_metadata_deleted_entries,
                active_metadata_deleted_entries_label
            )
        );
	}

	if (skipped_entries > 0) {
		message_parts.push(
            wp.i18n.sprintf(
                wp.i18n.__( '%1$d %2$s skipped.', 'ai-for-seo' ),
                skipped_entries,
                skipped_entries_label
            )
        );
	}

	const message = message_parts.join( ' ' );

	if (deleted_entries > 0) {
		ai4seo_show_success_toast( message );

		if (ai4seo_try_refresh_related_attachments_modal_after_bulk_action( bulk_generation_queue_action_form )) {
			return;
		}

		setTimeout(
            function () {
			ai4seo_reload_page();
            },
            800
        );

		return;
	}

	ai4seo_show_warning_toast( message );
}

// =========================================================================================== \\

/**
 * Refresh the related media modal after a bulk action instead of reloading the whole admin page.
 *
 * @param {HTMLElement|jQuery|null} bulk_generation_queue_action_form
 * @returns {boolean}
 */
function ai4seo_try_refresh_related_attachments_modal_after_bulk_action(bulk_generation_queue_action_form = null) {
	const $bulk_generation_queue_action_form = ai4seo_normalize_$( bulk_generation_queue_action_form );

	if (!ai4seo_exists_$( $bulk_generation_queue_action_form )) {
		return false;
	}

	// Only modal-embedded media lists should refresh in place; full-page lists keep their page reload behavior.
	const $related_attachments_modal_content = ai4seo_get_related_attachments_modal_content_$( $bulk_generation_queue_action_form );

	if (!ai4seo_exists_$( $related_attachments_modal_content )) {
		return false;
	}

	// Delay the reload to match the existing toast timing used by full-page bulk actions.
	setTimeout(
        function () {
		if (!ai4seo_exists_$( $related_attachments_modal_content.closest( '.ai4seo-modal' ) )) {
			return;
		}

		ai4seo_reload_related_attachments_modal(
			$related_attachments_modal_content,
			ai4seo_get_related_attachments_modal_current_filter_parameters( $related_attachments_modal_content )
		);
        },
        800
    );

	return true;
}

// =========================================================================================== \\

function ai4seo_init_checkbox_containers() {
	// class -> ai4seo-checkbox-container
	// add toggle effect for any checkboxes inside the container.
	const $checkbox_containers = ai4seo_normalize_$( '.ai4seo-checkbox-container' );

	if (!ai4seo_exists_$( $checkbox_containers )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': no checkbox-containers found in ai4seo_init_checkbox_containers() \u2014 cannot initialize grouped toggles.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': initializing ' + $checkbox_containers.length + ' checkbox-containers in ai4seo_init_checkbox_containers().' );

	$checkbox_containers.each(
        function () {
		const $this_container = ai4seo_normalize_$( this );
		const $this_container_checkboxes = $this_container.find( 'input[type="checkbox"]' );

		if (!ai4seo_exists_$( $this_container_checkboxes )) {
			console.warn( ai4seo_get_plugin_name() + ': elements \"$this_container_checkboxes\" missing in ai4seo_init_checkbox_containers() \u2014 cannot sync group state.' );
			return;
		}

		// on click on the container, toggle it's checkboxes, but prevent the event from bubbling up to the parent container AND prevent a click on the checkbox to double toggle it.
		$this_container.off( 'click.ai4seo-checkboxes' );
		$this_container.on(
            'click.ai4seo-checkboxes',
            function (event) {
			event.stopPropagation();
			$this_container_checkboxes.prop(
                'checked',
                function (index, checked) {
				return !checked;
                }
            );
            }
        );

		// on click on the checkboxes, prevent the event from bubbling up to the parent container.
		$this_container_checkboxes.off( 'click.ai4seo-checkboxes' );
		$this_container_checkboxes.on(
            'click.ai4seo-checkboxes',
            function (event) {
			event.stopPropagation();
            }
        );
        }
    );
}


// =========================================================================================== \\

function ai4seo_toggle_autopilot_remove_generated_data_section($clicked_button) {
	$clicked_button = ai4seo_normalize_$( $clicked_button );

	if (!ai4seo_exists_$( $clicked_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$toggle_button" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — cannot toggle removal controls.' );
		return;
	}

	const $generated_data_reminder_container = $clicked_button.closest( '.ai4seo-generated-data-reminder-container' );

	if (!ai4seo_exists_$( $generated_data_reminder_container )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$reminder" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — reminder container not found.' );
		return;
	}

	const $autopilot_remove_generated_data_action_container = $generated_data_reminder_container.find( '.ai4seo-remove-generated-data-action-container' );

	if (!ai4seo_exists_$( $autopilot_remove_generated_data_action_container )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$action_container" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — removal container not found.' );
		return;
	}

	if ($autopilot_remove_generated_data_action_container.hasClass( 'ai4seo-display-none' )) {
		$autopilot_remove_generated_data_action_container.removeClass( 'ai4seo-display-none' );
		$clicked_button.attr( 'aria-expanded', 'true' );

		const $remove_generated_data_button = $autopilot_remove_generated_data_action_container.find( '.ai4seo-remove-generated-data-button' );

		if (ai4seo_exists_$( $remove_generated_data_button )) {
			$remove_generated_data_button.trigger( 'focus' );
		}
	} else {
		$autopilot_remove_generated_data_action_container.addClass( 'ai4seo-display-none' );
		$clicked_button.attr( 'aria-expanded', 'false' );
	}
}

// =========================================================================================== \\

function ai4seo_escape_html(value) {
	const $element = ai4seo_normalize_$( '<div></div>' );

	if (!ai4seo_exists_$( $element )) {
		return String( value ?? '' );
	}

	return $element.text( String( value ?? '' ) ).html();
}

// =========================================================================================== \\

function ai4seo_get_generated_data_reset_post_type_options($options_container) {
	$options_container = ai4seo_normalize_$( $options_container );

	if (!ai4seo_exists_$( $options_container )) {
		return [];
	}

	const options = [];
	const $post_type_options = $options_container.find( '.ai4seo-generated-data-reset-post-type-option' );

	if (!ai4seo_exists_$( $post_type_options )) {
		return options;
	}

	$post_type_options.each(
        function () {
		const $this_option = ai4seo_normalize_$( this );
		const post_type = $this_option.data( 'post-type' );
		const label = $this_option.data( 'label' );

		if (!post_type || !label) {
			return;
		}

		options.push(
            {
			post_type: String( post_type ),
			label: String( label )
            }
        );
        }
    );

	return options;
}

// =========================================================================================== \\

function ai4seo_get_generated_data_reset_post_type_checkboxes_html(options, checkbox_name, checkbox_class, id_prefix, checked = false) {
	if (!Array.isArray( options ) || options.length === 0) {
		return '';
	}

	let checkboxes_html = "<div class='ai4seo-generated-data-reset-post-type-selection'>";
	const select_all_id = 'ai4seo-select-all-' + id_prefix;
	const checked_attribute = checked ? ' checked' : '';

	checkboxes_html += "<label class='ai4seo-select-all-checkbox-label ai4seo-form-multiple-inputs' for='" + ai4seo_escape_html( select_all_id ) + "'>";
	checkboxes_html += "<input type='checkbox' class='ai4seo-select-all-checkbox' data-target='" + ai4seo_escape_html( checkbox_name ) + "' id='" + ai4seo_escape_html( select_all_id ) + "'" + checked_attribute + '>';
	checkboxes_html += ai4seo_escape_html( wp.i18n.__( 'Select All / Unselect All', 'ai-for-seo' ) );
	checkboxes_html += '</label>';
	checkboxes_html += "<div class='ai4seo-medium-gap'></div>";

	options.forEach(
        function (option) {
		const post_type = String( option.post_type || '' );
		const label = String( option.label || '' );

		if (!post_type || !label) {
			return;
		}

		const input_id = id_prefix + '-' + post_type.replace( /[^a-zA-Z0-9_-]/g, '-' );

		checkboxes_html += "<div class='ai4seo-form-multiple-inputs'>";
		checkboxes_html += "<input type='checkbox' id='" + ai4seo_escape_html( input_id ) + "' name='" + ai4seo_escape_html( checkbox_name ) + "[]' value='" + ai4seo_escape_html( post_type ) + "' class='ai4seo-generated-data-reset-post-type-checkbox " + ai4seo_escape_html( checkbox_class ) + "'" + checked_attribute + '>';
		checkboxes_html += "<label for='" + ai4seo_escape_html( input_id ) + "'>" + ai4seo_escape_html( label ) + '</label>';
		checkboxes_html += '</div>';
        }
    );

	const note_context_class = id_prefix + '-full-reset-note';
	const note_css_class = checked
		? 'ai4seo-generated-data-reset-full-reset-note'
		: 'ai4seo-generated-data-reset-full-reset-note ai4seo-display-none';

	checkboxes_html += "<div class='" + ai4seo_escape_html( note_css_class + ' ' + note_context_class ) + "' role='status'>";
	checkboxes_html += ai4seo_escape_html( wp.i18n.__( 'All generated data will be removed because every entry type is selected.', 'ai-for-seo' ) );
	checkboxes_html += '</div>';

	checkboxes_html += '</div>';

	return checkboxes_html;
}

// =========================================================================================== \\

/**
 * Read the unique checked post types within one generated-data reset surface.
 *
 * @param {string} checkbox_selector Reset checkbox selector.
 * @param {jQuery|Node|Window|null} scope Optional form or modal boundary.
 * @return {Array<string>}
 */
function ai4seo_get_selected_generated_data_reset_post_types(checkbox_selector, scope = null) {
	// A reset modal must not consume checked values from another stacked or background surface.
	const $selected_post_type_checkboxes = ai4seo_normalize_$( checkbox_selector + ':checked', scope );

	if (!ai4seo_exists_$( $selected_post_type_checkboxes )) {
		return [];
	}

	const selected_post_types = [];

	$selected_post_type_checkboxes.each(
        function () {
		const post_type = String( jQuery( this ).val() || '' );

		if (!post_type || selected_post_types.includes( post_type )) {
			return;
		}

		selected_post_types.push( post_type );
        }
    );

	return selected_post_types;
}

// =========================================================================================== \\

/**
 * Read the unique post types offered within one generated-data reset surface.
 *
 * @param {string} checkbox_selector Reset checkbox selector.
 * @param {jQuery|Node|Window|null} scope Optional form or modal boundary.
 * @return {Array<string>}
 */
function ai4seo_get_presented_generated_data_reset_post_types(checkbox_selector, scope = null) {
	// Presented entries use the same boundary as selected entries so full-reset detection remains local.
	const $post_type_checkboxes = ai4seo_normalize_$( checkbox_selector, scope );

	if (!ai4seo_exists_$( $post_type_checkboxes )) {
		return [];
	}

	const presented_post_types = [];

	$post_type_checkboxes.each(
        function () {
		const post_type = String( jQuery( this ).val() || '' );

		if (!post_type || presented_post_types.includes( post_type )) {
			return;
		}

		presented_post_types.push( post_type );
        }
    );

	return presented_post_types;
}

// =========================================================================================== \\

/**
 * Determine whether every presented post type is selected within one reset surface.
 *
 * @param {string} checkbox_selector Reset checkbox selector.
 * @param {jQuery|Node|Window|null} scope Optional form or modal boundary.
 * @return {boolean}
 */
function ai4seo_is_full_generated_data_reset_selection(checkbox_selector, scope = null) {
	// Compare scope-local unique values so duplicate inputs cannot produce a false full-reset result.
	const presented_post_types = ai4seo_get_presented_generated_data_reset_post_types( checkbox_selector, scope );
	const selected_post_types = ai4seo_get_selected_generated_data_reset_post_types( checkbox_selector, scope );

	if (presented_post_types.length === 0 || selected_post_types.length !== presented_post_types.length) {
		return false;
	}

	return presented_post_types.every(
        function (post_type) {
		return selected_post_types.includes( post_type );
        }
    );
}

// =========================================================================================== \\

/**
 * Synchronize the full-reset notice belonging to one reset surface.
 *
 * @param {string} checkbox_selector Reset checkbox selector.
 * @param {string} note_selector Full-reset notice selector.
 * @param {jQuery|Node|Window|null} scope Optional form or modal boundary.
 */
function ai4seo_update_generated_data_reset_full_reset_note(checkbox_selector, note_selector, scope = null) {
	// Resolve the notice inside the same surface used to calculate selection completeness.
	const $full_reset_note = ai4seo_normalize_$( note_selector, scope );

	if (!ai4seo_exists_$( $full_reset_note )) {
		return;
	}

	const is_full_reset = ai4seo_is_full_generated_data_reset_selection( checkbox_selector, scope );

	$full_reset_note.toggleClass( 'ai4seo-display-none', !is_full_reset );
}

// =========================================================================================== \\

/**
 * Bind full-reset notice updates within one reset surface.
 *
 * @param {string} checkbox_selector Reset checkbox selector.
 * @param {string} note_selector Full-reset notice selector.
 * @param {jQuery|Node|Window|null} scope Optional form or modal boundary.
 */
function ai4seo_init_generated_data_reset_full_reset_note(checkbox_selector, note_selector, scope = null) {
	// Rebinding is limited to the supplied surface so stacked modals retain their own handlers.
	const $post_type_checkboxes = ai4seo_normalize_$( checkbox_selector, scope );
	const $full_reset_note = ai4seo_normalize_$( note_selector, scope );

	if (!ai4seo_exists_$( $post_type_checkboxes ) || !ai4seo_exists_$( $full_reset_note )) {
		return;
	}

	$post_type_checkboxes.off( 'change.ai4seo-generated-data-reset-full-reset-note' );
	$post_type_checkboxes.on(
        'change.ai4seo-generated-data-reset-full-reset-note',
        function () {
		ai4seo_update_generated_data_reset_full_reset_note( checkbox_selector, note_selector, scope );
        }
    );

	ai4seo_update_generated_data_reset_full_reset_note( checkbox_selector, note_selector, scope );
}

// =========================================================================================== \\

/**
 * Open the generated-data confirmation modal and bind controls inside the returned modal root.
 */
function ai4seo_confirm_autopilot_remove_generated_data() {
	const $autopilot_reset_generated_data_info_tooltip = ai4seo_normalize_$( '#ai4seo-autopilot-reset-generated-data-info' );
	const post_type_options = ai4seo_get_generated_data_reset_post_type_options( '#ai4seo-autopilot-reset-generated-data-post-type-options' );
	let confirmation_message = '';

	if (ai4seo_exists_$( $autopilot_reset_generated_data_info_tooltip )) {
		confirmation_message = $autopilot_reset_generated_data_info_tooltip.html() + '<br><br>';
	}

	if (post_type_options.length === 0) {
		ai4seo_show_warning_toast( wp.i18n.__( 'There is no AI-generated data to remove.', 'ai-for-seo' ) );
		return;
	}

	confirmation_message += wp.i18n.__( 'Choose which entry types should be cleared. Only selected entry types will be affected.', 'ai-for-seo' );
	confirmation_message += '<br><br>';
	confirmation_message += ai4seo_get_generated_data_reset_post_type_checkboxes_html(
		post_type_options,
		'ai4seo-autopilot-reset-generated-data-post-type',
		'ai4seo-autopilot-reset-generated-data-post-type-checkbox',
		'ai4seo-autopilot-reset-generated-data-post-type',
		true
	);

	// Keep the created modal reference as the authoritative boundary for its reset controls.
	const $notification_modal = ai4seo_open_notification_modal(
		wp.i18n.__( 'Please confirm', 'ai-for-seo' ),
		confirmation_message,
		"<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Abort', 'ai-for-seo' ) + "</button><button type='button' class='ai4seo-button ai4seo-secondary-button' onclick='ai4seo_remove_generated_data_via_autopilot(this);'>" + wp.i18n.__( 'Remove selected data', 'ai-for-seo' ) + '</button>',
		{close_on_backdrop_click: false}
	);

	if (!ai4seo_exists_$( $notification_modal )) {
		return;
	}

	ai4seo_init_select_all_checkboxes( $notification_modal );
	ai4seo_init_generated_data_reset_full_reset_note(
		'.ai4seo-autopilot-reset-generated-data-post-type-checkbox',
		'.ai4seo-autopilot-reset-generated-data-post-type-full-reset-note',
		$notification_modal
	);
}

// =========================================================================================== \\

/**
 * Submit the generated-data reset selected in the initiating notification modal.
 *
 * @param {jQuery|HTMLElement|null} button Initiating confirmation button.
 */
function ai4seo_remove_generated_data_via_autopilot(button = null) {
	const reset_metadata_checkbox_selector = '.ai4seo-autopilot-reset-generated-data-post-type-checkbox';

	// Prefer the initiating button's modal so a stacked notification cannot redirect the reset selection.
	const $button = ai4seo_normalize_$( button );
	let $notification_modal = ai4seo_exists_$( $button )
		? $button.closest( '.ai4seo-modal' )
		: ai4seo_get_modal_$( 'ai4seo-notification-modal' );

	// Direct callers without a button retain the established notification-modal fallback.
	if (!ai4seo_exists_$( $notification_modal )) {
		$notification_modal = ai4seo_get_modal_$( 'ai4seo-notification-modal' );
	}

	// Never widen a destructive reset to the document when its owning confirmation modal is missing.
	if (!ai4seo_exists_$( $notification_modal )) {
		return;
	}

	// Both payload values must be derived from the identical reset surface.
	const reset_metadata_post_types = ai4seo_get_selected_generated_data_reset_post_types( reset_metadata_checkbox_selector, $notification_modal );
	const reset_metadata_is_full_reset = ai4seo_is_full_generated_data_reset_selection( reset_metadata_checkbox_selector, $notification_modal );

	if (reset_metadata_post_types.length === 0) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select at least one entry type.', 'ai-for-seo' ) );
		return;
	}

	const $autopilot_remove_generated_data_button = ai4seo_normalize_$( '.ai4seo-remove-generated-data-button' );

	if (ai4seo_exists_$( $autopilot_remove_generated_data_button )) {
		ai4seo_add_loading_html_to_element( $autopilot_remove_generated_data_button );
	}

	ai4seo_close_notification_modal();
	ai4seo_lock_and_disable_lockable_input_fields();

	ai4seo_perform_ajax_call(
		'ai4seo_reset_plugin_data',
		{
			ai4seo_reset_metadata: true,
			ai4seo_reset_metadata_is_full_reset: reset_metadata_is_full_reset,
			ai4seo_reset_metadata_post_types: reset_metadata_post_types
		}
	)
		.then(
            response => { /* nothing */
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 512181225, error );
            }
        )
		.finally(
            () => {
			ai4seo_safe_page_load();
            }
        );
}

// =========================================================================================== \\


// ___________________________________________________________________________________________ \\
// === ELEMENTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Move a workspace tooltip under body so modal cards and scroll regions cannot crop it.
 *
 * @param {jQuery} $tooltip
 * @param {jQuery} $tooltip_holder
 * @return {boolean}
 */
function ai4seo_portal_editor_tooltip($tooltip, $tooltip_holder) {
	if (!ai4seo_exists_$( $tooltip ) || !ai4seo_exists_$( $tooltip_holder ) || !ai4seo_exists_$( $tooltip_holder.closest( '.ai4seo-editor-workspace-modal' ) )) {
		return false;
	}

	const tooltip_element = $tooltip.get( 0 );
	const tooltip_holder_element = $tooltip_holder.get( 0 );
	const tooltip_document = tooltip_element.ownerDocument || document;

	if (!tooltip_element.ai4seo_tooltip_original_parent) {
		tooltip_element.ai4seo_tooltip_original_parent = tooltip_element.parentNode;
		tooltip_element.ai4seo_tooltip_original_next_sibling = tooltip_element.nextSibling;
	}

	tooltip_element.ai4seo_tooltip_holder = tooltip_holder_element;
	tooltip_holder_element.ai4seo_portaled_tooltip = tooltip_element;
	tooltip_document.body.appendChild( tooltip_element );
	$tooltip
		.addClass( 'ai4seo-tooltip-portal' )
		.toggleClass( 'ai4seo-tooltip-portal-custom-instructions', $tooltip_holder.hasClass( 'ai4seo-custom-instructions-examples' ) )
		.off( '.ai4seo-tooltip-portal' )
		.on(
			'mouseenter.ai4seo-tooltip-portal',
			function () {
				ai4seo_cancel_tooltip_hide( $tooltip_holder );
			}
		)
		.on(
			'mouseleave.ai4seo-tooltip-portal',
			function () {
				ai4seo_schedule_tooltip_hide( $tooltip_holder, $tooltip );
			}
		);

	return true;
}

// =========================================================================================== \\

/**
 * Return a portaled tooltip to its holder after it has fully closed.
 *
 * @param {jQuery} $tooltip
 */
function ai4seo_restore_portaled_tooltip($tooltip) {
	if (!ai4seo_exists_$( $tooltip ) || !$tooltip.hasClass( 'ai4seo-tooltip-portal' )) {
		return;
	}

	const tooltip_element = $tooltip.get( 0 );
	const tooltip_holder_element = tooltip_element.ai4seo_tooltip_holder;
	const original_parent = tooltip_element.ai4seo_tooltip_original_parent;
	const original_next_sibling = tooltip_element.ai4seo_tooltip_original_next_sibling;

	if (original_parent && original_parent.isConnected) {
		if (original_next_sibling && original_next_sibling.parentNode === original_parent) {
			original_parent.insertBefore( tooltip_element, original_next_sibling );
		} else {
			original_parent.appendChild( tooltip_element );
		}
	} else {
		// AJAX preview refreshes can remove a dynamic holder while its tooltip is open.
		tooltip_element.remove();
	}

	if (tooltip_holder_element) {
		ai4seo_cancel_tooltip_hide( tooltip_holder_element );
		tooltip_holder_element.ai4seo_portaled_tooltip = null;
		tooltip_holder_element.ai4seo_tooltip_is_pinned = false;
	}

	delete tooltip_element.ai4seo_tooltip_holder;
	delete tooltip_element.ai4seo_tooltip_original_parent;
	delete tooltip_element.ai4seo_tooltip_original_next_sibling;
	$tooltip
		.removeClass( 'ai4seo-tooltip-portal ai4seo-tooltip-portal-custom-instructions show-above show-below' )
		.off( '.ai4seo-tooltip-portal' )
		.css(
			{
				bottom: '',
				left: '',
				marginBottom: '',
				marginLeft: '',
				marginRight: '',
				marginTop: '',
				maxHeight: '',
				overflowY: '',
				position: '',
				top: '',
				transform: '',
				visibility: '',
			}
		);
}

// =========================================================================================== \\

// Function to show tooltip based on its position relative to the screen.
function ai4seo_show_tooltip($tooltip, event) {
	$tooltip = ai4seo_normalize_$( $tooltip );

	if (!ai4seo_exists_$( $tooltip )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$tooltip\" missing in ai4seo_show_tooltip() \u2014 cannot display tooltip.' );
		return;
	}

	const $tooltip_holder = ai4seo_get_tooltip_holder_$( $tooltip );
	const $tooltip_trigger = $tooltip_holder.children( '.ai4seo-tooltip-trigger' ).first();

	// Finish an earlier close before portaling again. Otherwise its delayed completion can restore a newly reopened
	// tooltip into the modal, changing inherited typography and exposing it to scroll-region clipping.
	ai4seo_cancel_tooltip_hide( $tooltip_holder );
	$tooltip.stop( true, true );
	const tooltip_is_portaled = ai4seo_portal_editor_tooltip( $tooltip, $tooltip_holder );
	const screen_width = window.innerWidth || jQuery( window ).width();

	if (tooltip_is_portaled) {
		const tooltip_element = $tooltip.get( 0 );
		const tooltip_anchor = ai4seo_exists_$( $tooltip_trigger ) ? $tooltip_trigger.get( 0 ) : $tooltip_holder.get( 0 );
		const tooltip_anchor_rectangle = tooltip_anchor.getBoundingClientRect();
		const viewport_height = window.innerHeight || jQuery( window ).height();
		const viewport_padding = 16;
		const tooltip_gap = 10;
		const tooltip_was_visible = $tooltip.is( ':visible' );
		const previous_display = tooltip_element.style.display;
		const previous_visibility = tooltip_element.style.visibility;

		$tooltip.css(
			{
				bottom: 'auto',
				display: 'block',
				left: '0',
				margin: '0',
				maxHeight: 'none',
				overflowY: 'auto',
				position: 'fixed',
				top: '0',
				transform: 'none',
				visibility: 'hidden',
			}
		);

		const natural_tooltip_height = $tooltip.outerHeight();
		const available_space_above = Math.max( 0, tooltip_anchor_rectangle.top - tooltip_gap - viewport_padding );
		const available_space_below = Math.max( 0, viewport_height - tooltip_anchor_rectangle.bottom - tooltip_gap - viewport_padding );
		const show_below = available_space_below >= natural_tooltip_height || available_space_below > available_space_above;
		// Allow the overlay to cross the trigger line when needed; scroll only if it exceeds the viewport itself.
		const available_vertical_space = Math.max( 96, viewport_height - (viewport_padding * 2) );

		$tooltip
			.css( 'max-height', available_vertical_space + 'px' )
			.toggleClass( 'show-below', show_below )
			.toggleClass( 'show-above', !show_below );

		const tooltip_width = Math.min( $tooltip.outerWidth(), Math.max( 0, screen_width - (viewport_padding * 2) ) );
		const tooltip_height = Math.min( $tooltip.outerHeight(), available_vertical_space );
		const anchor_center = tooltip_anchor_rectangle.left + (tooltip_anchor_rectangle.width / 2);
		const maximum_left = Math.max( viewport_padding, screen_width - viewport_padding - tooltip_width );
		const tooltip_left = Math.min( maximum_left, Math.max( viewport_padding, anchor_center - (tooltip_width / 2) ) );
		const unclamped_top = show_below
			? tooltip_anchor_rectangle.bottom + tooltip_gap
			: tooltip_anchor_rectangle.top - tooltip_gap - tooltip_height;
		const maximum_top = Math.max( viewport_padding, viewport_height - viewport_padding - tooltip_height );
		const tooltip_top = Math.min( maximum_top, Math.max( viewport_padding, unclamped_top ) );

		$tooltip.css( {left: tooltip_left + 'px', top: tooltip_top + 'px'} );

		if (!tooltip_was_visible) {
			$tooltip.css( {display: previous_display || 'none', visibility: previous_visibility || 'visible'} );
		} else {
			$tooltip.css( 'visibility', previous_visibility || 'visible' );
		}

		$tooltip.attr( 'aria-hidden', 'false' );
		$tooltip_trigger.attr( 'aria-expanded', 'true' );
		$tooltip.fadeIn( 100 );
		return;
	}

	const has_pointer_coordinates = event
		&& Number.isFinite( event.pageX )
		&& Number.isFinite( event.pageY )
		&& ('mouseenter' === event.type || 0 !== event.pageX || 0 !== event.pageY);
	let interaction_x = has_pointer_coordinates ? event.pageX : 0;
	let interaction_y = has_pointer_coordinates ? event.pageY : 0;

	// Keyboard activation has no pointer coordinates, so anchor positioning to the canonical trigger instead.
	if (!has_pointer_coordinates) {
		const $tooltip_anchor = ai4seo_exists_$( $tooltip_trigger ) ? $tooltip_trigger : $tooltip_holder;

		if (ai4seo_exists_$( $tooltip_anchor )) {
			const tooltip_anchor_offset = $tooltip_anchor.offset();
			interaction_x = tooltip_anchor_offset.left + ($tooltip_anchor.outerWidth() / 2);
			interaction_y = tooltip_anchor_offset.top + ($tooltip_anchor.outerHeight() / 2);
		}
	}

	// Hidden tooltips otherwise report a zero size, which can place long editor help outside the viewport.
	const tooltip_element = $tooltip.get( 0 );
	const tooltip_was_visible = $tooltip.is( ':visible' );
	const previous_display = tooltip_element.style.display;
	const previous_visibility = tooltip_element.style.visibility;

	if (!tooltip_was_visible) {
		$tooltip.css( {display: 'block', visibility: 'hidden'} );
	}

	const tooltip_width = $tooltip.outerWidth();
	const tooltip_height = $tooltip.outerHeight();

	if (!tooltip_was_visible) {
		$tooltip.css( {display: previous_display, visibility: previous_visibility} );
	}

	const tooltip_half_width = tooltip_width / 2;
	const vertical_buffer_zone = 30;
	const horizontal_buffer_zone = 30;
	const scroll_height = jQuery( window ).scrollTop();
	const relative_interaction_y = interaction_y - scroll_height;
	const viewport_height = window.innerHeight || jQuery( window ).height();
	const available_space_above = Math.max( 0, relative_interaction_y - vertical_buffer_zone );
	const available_space_below = Math.max( 0, viewport_height - relative_interaction_y - vertical_buffer_zone );
	const show_below = available_space_below >= tooltip_height || available_space_below > available_space_above;
	const tooltip_maximum_height = Math.max( 6 * 16, show_below ? available_space_below : available_space_above );
	const tooltip_half_width_with_buffer = tooltip_half_width + horizontal_buffer_zone;

	// Calculate left position ensuring tooltip doesn't go out of bounds.
	let left_position = 0;

	// tooltip is overlapping with left screen border.
	if (interaction_x - tooltip_half_width < 0) {
		left_position = tooltip_half_width - (interaction_x - horizontal_buffer_zone);

		// tooltip is overlapping with right screen border.
	} else if (interaction_x + tooltip_half_width > screen_width) {
		left_position = -tooltip_half_width + (screen_width - interaction_x - horizontal_buffer_zone);
	}

	// check if ai4seo_tooltip is inside a modal (ai4seo-ajax-modal) -> apply workarounds.
	const $closest_modal = $tooltip.closest( '.ai4seo-modal' );

	if (ai4seo_exists_$( $closest_modal )) {
		// modal left position.
		const modal_left_position = $closest_modal.offset().left;
		const modal_right_position = modal_left_position + $closest_modal.outerWidth();
		const interaction_distance_to_left_modal_border = interaction_x - modal_left_position;
		const interaction_distance_to_right_modal_border = modal_right_position - interaction_x;

		// Move away from the modal's left edge when the active pointer or keyboard anchor is too close.
		if (interaction_distance_to_left_modal_border < tooltip_half_width_with_buffer) {
			left_position += (tooltip_half_width_with_buffer - interaction_distance_to_left_modal_border);
		}

		// Move away from the modal's right edge using the same buffered width calculation.
		if (interaction_distance_to_right_modal_border < tooltip_half_width_with_buffer) {
			left_position -= (tooltip_half_width_with_buffer - interaction_distance_to_right_modal_border);
		}
	}

	// Keep the longest tooltip on the roomier side of its trigger and make excess content scroll.
	$tooltip
		.css( {'max-height': tooltip_maximum_height + 'px', 'overflow-y': 'auto'} )
		.toggleClass( 'show-below', show_below )
		.toggleClass( 'show-above', !show_below );

	if (show_below) {
		$tooltip.css(
            {
			top: '100%',
			bottom: 'auto',
			left: left_position + 'px',
			marginTop: '10px',
			marginBottom: '0',
			transform: 'translateX(-50%)'
            }
        );
	} else {
		$tooltip.css(
            {
			top: 'auto',
			bottom: '100%',
			left: left_position + 'px',
			marginBottom: '10px',
			marginTop: '0',
			transform: 'translateX(-50%)'
            }
        );
	}

	// Synchronize accessibility state before the fade begins so assistive technology receives the update immediately.
	$tooltip.attr( 'aria-hidden', 'false' );
	$tooltip_trigger.attr( 'aria-expanded', 'true' );
	$tooltip.fadeIn( 100 );
}

// =========================================================================================== \\

function ai4seo_hide_tooltip($tooltip, fade_duration = 100) {
	$tooltip = ai4seo_normalize_$( $tooltip );

	if (!ai4seo_exists_$( $tooltip )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$tooltip\" missing in ai4seo_hide_tooltip() \u2014 nothing to hide.' );
		return;
	}

	$tooltip.each(
		function () {
			const $this_tooltip = ai4seo_normalize_$( this );
			const $this_tooltip_holder = ai4seo_get_tooltip_holder_$( $this_tooltip );

			// Keep the trigger state aligned with the animation for both single tooltips and collections.
			$this_tooltip.attr( 'aria-hidden', 'true' );
			$this_tooltip_holder.children( '.ai4seo-tooltip-trigger' ).first().attr( 'aria-expanded', 'false' );
			$this_tooltip.stop( true, true );

			// A zero duration is used by close-all paths that must finish before another tooltip opens.
			if (fade_duration > 0) {
				$this_tooltip.fadeOut(
					fade_duration,
					function () {
						ai4seo_restore_portaled_tooltip( $this_tooltip );
					}
				);
			} else {
				$this_tooltip.hide();
				ai4seo_restore_portaled_tooltip( $this_tooltip );
			}
		}
	);
}

// ___________________________________________________________________________________________ \\
// === HELPER FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Filter out preview-only controls from generic save and unsaved-change input scans.
 *
 * Demo or visual controls can share the same form container as real settings. This helper keeps
 * those controls out of persistence paths without special casing each caller.
 */
function ai4seo_filter_persistent_inputs($inputs) {
	// Normalize the input collection so callers can pass selectors, elements, or jQuery objects.
	$inputs = ai4seo_normalize_$( $inputs );

	if (!ai4seo_exists_$( $inputs )) {
		return $inputs;
	}

	// Exclude controls that are explicitly marked as non-persistent or live inside such a wrapper.
	return $inputs.filter(
        function () {
		const $this_input = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_input )) {
			return false;
		}

		return !$this_input.is( '[data-ai4seo-non-persistent="1"]' )
			&& $this_input.closest( '[data-ai4seo-non-persistent="1"]' ).length === 0;
        }
    );
}

// =========================================================================================== \\

function ai4seo_get_input_value($input) {
	// Make sure that element can be found.
	$input = ai4seo_normalize_$( $input );

	if (!ai4seo_exists_$( $input )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$input\" missing in ai4seo_get_input_value() \u2014 cannot read value.' );
		return false;
	}

	// check if element is a single checkbox and class ai4seo-single-checkbox.
	if ($input.is( "input[type='checkbox']" ) && $input.length === 1 && $input.hasClass( 'ai4seo-single-checkbox' )) {
		return $input.is( ':checked' );
	} else if ($input.is( "input[type='checkbox']" )) {
		// check if element is a group of checkboxes.
		return $input.filter( ':checked' ).map(
            function () {
			return jQuery( this ).val();
            }
        ).get();
	} else if ($input.is( "input[type='radio']" )) {
		// check if element is a group of radio buttons.
		// Preserve a locked paid stage across unrelated saves until the user chooses an available replacement.
		const preserved_slider_value = $input.first().closest( '.ai4seo-slider-input' ).attr( 'data-ai4seo-slider-preserved-value' );

		if (typeof preserved_slider_value !== 'undefined' && preserved_slider_value !== '') {
			return preserved_slider_value;
		}

		return $input.filter( ':checked' ).val();
	} else if ($input.is( 'input' )) {
		// Check if element is input-field (any other type than checkbox or radio).
		return $input.val();
	} else if ($input.is( 'textarea' )) {
		// Check if element is textarea.
		return $input.val();
	} else if ($input.is( 'select' )) {
		// Check if element is select.
		return $input.find( 'option' ).filter( ':selected' ).val();
	} else if ($input.is( 'div' ) || $input.is( 'span' )) {
		// check if element is a div or a span.
		return $input.text();
	} else if ($input.is( 'p' )) {
		// check if element is a paragraph.
		return $input.text();
	}

	return $input.val();
}

// =========================================================================================== \\

function ai4seo_array_unique(array) {
	return array.filter(
        function (el, index, arr) {
		return index === arr.indexOf( el );
        }
    );
}

// =========================================================================================== \\

/**
 * Normalize jQuery-compatible input, optionally constrained to one or more DOM roots.
 *
 * Unscoped selector lookups retain the parent-document fallback used by editor integrations.
 * Explicit scopes are root-inclusive and never widen when the supplied boundary is invalid.
 *
 * @param {*} mixed jQuery-compatible selector, element, collection, or object.
 * @param {jQuery|Node|Window|string|Object|null} scope Optional lookup root or single-tag property map.
 * @return {jQuery}
 */
function ai4seo_normalize_$(mixed = null, scope = null) {
	// Every failure path returns the same empty jQuery shape expected by ai4seo_exists_$().
	let $mixed = jQuery();

	// Preserve jQuery's single-tag property-map signature for external callers while scoped lookups stay strict.
	const is_single_tag_html = typeof mixed === 'string'
		&& /^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i.test( mixed.trim() );

	if (is_single_tag_html && jQuery.isPlainObject( scope )) {
		try {
			return jQuery( mixed, scope );
		} catch (error) {
			return jQuery();
		}
	}

	// Without an explicit scope, retain the current-document lookup and distinct parent-document fallback.
	if (scope === null) {
		try {
			$mixed = jQuery( mixed );
		} catch (error) {
			console.error( ai4seo_get_plugin_name() + ': Error normalizing jQuery element in ai4seo_normalize_$() before applying context: ' + error );
			return jQuery(); // return empty jQuery object on error.
		}

		// if we found a jQuery object with the selector or created a valid element, then return it.
		if (typeof $mixed.length !== 'undefined' && $mixed.length > 0) {
			return $mixed;
		}

		// Resolve the optional parent context once so cross-origin access remains a silent fallback miss.
		const parent_document = ai4seo_get_distinct_parent_document();

		if (parent_document) {
			try {
				$mixed = jQuery( mixed, parent_document );
			} catch (error) {
				return jQuery();
			}
		}

		return $mixed.length > 0 ? $mixed : jQuery();
	}

	let $scope = jQuery();

	// Resolve selector scopes in the current document first, with the same safe parent fallback as unscoped lookups.
	try {
		$scope = jQuery( scope );
	} catch (error) {
		return jQuery();
	}

	if ($scope.length === 0 && typeof scope === 'string') {
		// Selector scopes use the same silent, same-origin parent fallback as unscoped selectors.
		const parent_document = ai4seo_get_distinct_parent_document();

		if (parent_document) {
			try {
				$scope = jQuery( scope, parent_document );
			} catch (error) {
				return jQuery();
			}
		}
	}

	if ($scope.length === 0) {
		return jQuery();
	}

	let $scoped_mixed = jQuery();

	$scope.each(
		function () {
			// Window and Document inputs share the same DOM selector boundary.
			const scope_item = ai4seo_get_dom_scope_item( this );

			// Only DOM containers are valid selector boundaries; plain objects must never widen to document.
			if (!scope_item || ![1, 9, 11].includes( scope_item.nodeType )) {
				return;
			}

			try {
				if (typeof mixed === 'string') {
					const $scope_item = jQuery( scope_item );
					let $matches = scope_item.nodeType === 9
						? jQuery( mixed, scope_item )
						: $scope_item.filter( mixed ).add( $scope_item.find( mixed ) );

					const may_contain_selector_list = mixed.includes( ',' );

					if (
						scope_item.nodeType !== 9
						&& ($matches.length === 0 || may_contain_selector_list)
						&& scope_item.ownerDocument
					) {
						// Document-scoped third-party selectors can repeat the editor root, which
						// jQuery.find() cannot match from inside that same root. Selector lists also
						// need this merge when another branch already produced a local match.
						const $document_matches = jQuery( mixed, scope_item.ownerDocument ).filter(
							function () {
								return ai4seo_is_dom_item_within_scope( this, scope_item );
							}
						);

						$matches = $matches.add( $document_matches );
					}

					$scoped_mixed = $scoped_mixed.add( $matches );
					return;
				}

				jQuery( mixed ).each(
					function () {
						// Apply the shared boundary check while returning the original candidate shape.
						if (ai4seo_is_dom_item_within_scope( this, scope_item )) {
							$scoped_mixed = $scoped_mixed.add( this );
						}
					}
				);
			} catch (error) {
				return;
			}
		}
	);

	return $scoped_mixed;
}

// =========================================================================================== \\

function ai4seo_exists_$(mixed, scope = null) {
	const $mixed = ai4seo_normalize_$( mixed, scope );

	// check if length is defined and if it's greater than 0.
	return (typeof $mixed.length !== 'undefined' && $mixed.length > 0);
}

// =========================================================================================== \\

function ai4seo_get_post_id(processing_context, scope = null) {
	const $scoped_context = ai4seo_normalize_$( scope );

	if (ai4seo_exists_$( $scoped_context )) {
		const $scoped_editor_modal_post_id_holder = $scoped_context.find( '#ai4seo-editor-modal-post-id' ).first();

		// Stacked editor modals still share this legacy hidden id, so scoped generation must prefer its own modal.
		if (ai4seo_exists_$( $scoped_editor_modal_post_id_holder )) {
			let post_id = $scoped_editor_modal_post_id_holder.val();

			if (post_id && !isNaN( post_id )) {
				return parseInt( post_id );
			}
		}
	}

	const $editor_context = ai4seo_get_editor_context_$();

	if (ai4seo_exists_$( $editor_context )) {
		const $editor_modal_post_id_holder = ai4seo_normalize_$( $editor_context.find( '#ai4seo-editor-modal-post-id' ) );

		// Keep the historical global lookup for non-stacked editor contexts and existing external integrations.
		if (ai4seo_exists_$( $editor_modal_post_id_holder )) {
			let post_id = $editor_modal_post_id_holder.val();

			if (post_id && !isNaN( post_id )) {
				return parseInt( post_id );
			}
		}
	}

	// Check if "media-modal"-element exists.
	if ((!processing_context || processing_context === 'attachment-attributes') && ai4seo_exists_$( '.media-modal' )) {
		// Read current url-parameters.
		const current_url_parameters = new URLSearchParams( window.location.search );

		// Read item-parameter from current-url-parameters.
		post_id = current_url_parameters.get( 'item' );

		// Check if item-id could be found and is valid.
		if (post_id && !isNaN( post_id )) {
			return parseInt( post_id );
		}

		// Resolve the active media frame defensively because some editor integrations render the modal in another window.
		const media_frame = typeof wp !== 'undefined' && wp.media && wp.media.frame
			? wp.media.frame
			: null;

		// Image Details frames expose the authoritative attachment through their active PostImage state.
		if (media_frame && typeof media_frame.state === 'function') {
			let media_frame_state = null;

			try {
				media_frame_state = media_frame.state();
			} catch (error) {
				// Third-party MediaFrame implementations may not expose an active state until their modal finishes opening.
				media_frame_state = null;
			}

			const image_details_attachment = media_frame_state && media_frame_state.image && media_frame_state.image.attachment;

			if (image_details_attachment) {
				// Start with the plain-object ID so an empty or failing Backbone accessor cannot block compatible integrations.
				post_id = image_details_attachment.id;

				if (typeof image_details_attachment.get === 'function') {
					try {
						// Prefer the model attribute because Image Details frames keep their live attachment ID there.
						post_id = image_details_attachment.get( 'id' ) || post_id;
					} catch (error) {
						// Keep the plain-object fallback and allow the DOM lookup paths below when third-party accessors fail.
					}
				}

				// Return through the same numeric-ID guard used by the other media-frame lookup paths below.
				if (post_id && !isNaN( post_id )) {
					return parseInt( post_id );
				}
			}
		}

		// Get the first selected attachment.
		const $selected_attachment_candidates = ai4seo_normalize_$( '.attachments-wrapper .attachments .attachment.selected' );

		if (ai4seo_exists_$( $selected_attachment_candidates )) {
			const $selected_attachment = $selected_attachment_candidates.first();

			// Check if the selected attachment has a data-id attribute.
			if ($selected_attachment.data( 'id' )) {
				post_id = $selected_attachment.data( 'id' );

				if (post_id && !isNaN( post_id )) {
					return parseInt( post_id );
				}
			}
		} else if (media_frame) {
			// Generic media frames retain the attachment directly on their Backbone model.
			// Check if the attachment-id exists within model.id.
			if (media_frame.model && media_frame.model.id) {
				post_id = media_frame.model.id;
				if (post_id && !isNaN( post_id )) {
					return parseInt( post_id );
				}
			}
		}
	}

	if (!processing_context || processing_context === 'attachment-attributes') {
		// Gutenberg: selected image in the editor
		// check if wp.data can be accessed.
		do {
			if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
				const {select} = wp.data;

				// check if we can call getSelectedBlock().
				if (typeof select( 'core/block-editor' ) === 'undefined' || typeof select( 'core/block-editor' ).getSelectedBlock !== 'function') {
					break;
				}

				// Get the currently selected block.
				const selected_block = select( 'core/block-editor' ).getSelectedBlock();

				// check if we have a selected_block and have access to its attributes.
				if (!selected_block || typeof selected_block.attributes === 'undefined') {
					break;
				}

				// check for mediaId.
				if (typeof selected_block.attributes.mediaId !== 'undefined') {
					post_id = selected_block.attributes.mediaId;

					if (post_id && !isNaN( post_id )) {
						return parseInt( post_id );
					}
				}

				// check for id.
				if (typeof selected_block.attributes.id !== 'undefined') {
					post_id = selected_block.attributes.id;

					if (post_id && !isNaN( post_id )) {
						return parseInt( post_id );
					}
				}
			}
		} while (false);
	}

	// check for .post-type-attachment -> then we are inside the edit attachment form.
	const $post_type_attachment = ai4seo_normalize_$( '.post-type-attachment' );

	if (ai4seo_exists_$( $post_type_attachment ) || !processing_context || processing_context === 'metadata') {
		// then look for the post-id in the localized object -> check last as it can sometimes have invalid information.
		post_id = ai4seo_get_localization_parameter( 'ai4seo_current_post_id' );

		// Make sure that post_id could be found and is a number.
		if (post_id && !isNaN( post_id ) && parseInt( post_id ) > 0) {
			return parseInt( post_id );
		}
	}

	return false;
}

// =========================================================================================== \\

function ai4seo_get_plugin_version_number() {
	return ai4seo_get_localization_parameter( 'ai4seo_plugin_version_number' );
}

// =========================================================================================== \\

function ai4seo_get_admin_ajax_url() {
	return (typeof window !== 'undefined' && window.ajaxurl) ||
		ai4seo_get_localization_parameter( 'ai4seo_admin_ajax_url' ) ||
		(ai4seo_get_localization_parameter( 'ai4seo_admin_url' ) + 'admin-ajax.php') ||
		'/wp-admin/admin-ajax.php';
}

// =========================================================================================== \\

function ai4seo_get_metadata_price_table() {
	return ai4seo_get_localization_parameter( 'ai4seo_metadata_price_table' );
}

// =========================================================================================== \\

function ai4seo_get_attachment_attributes_price_table() {
	return ai4seo_get_localization_parameter( 'ai4seo_attachment_attributes_price_table' );
}

// =========================================================================================== \\

function ai4seo_get_seconds_since_page_load() {
	// Check if ai4seo_page_load_time is defined.
	if (typeof window.ai4seo_page_load_time === 'undefined') {
		return 0;
	}

	// Calculate the difference in seconds.
	const current_time = Date.now();
	const time_difference = current_time - window.ai4seo_page_load_time;

	// Convert milliseconds to seconds.
	return Math.floor( time_difference / 1000 );
}

// =========================================================================================== \\

function ai4seo_compare_version(v1, v2, operator) {
	const normalize = (version) =>
		version
			.replace( /[^0-9a-z.+-]/gi, '' )
			.split( '.' )
			.map( (v) => (isNaN( v ) ? v : parseInt( v )) );

	const compareParts = (a, b) => {
		const len = Math.max( a.length, b.length );
		for (let i = 0; i < len; i++) {
			const partA = a[i] ?? 0;
			const partB = b[i] ?? 0;

			if (typeof partA === 'string' || typeof partB === 'string') {
				const sA = String( partA );
				const sB = String( partB );
				if (sA > sB) {
					return 1;
				}
				if (sA < sB) {
					return -1;
				}
			} else {
				if (partA > partB) {
					return 1;
				}
				if (partA < partB) {
					return -1;
				}
			}
		}
		return 0;
	};

	const result = compareParts( normalize( v1 ), normalize( v2 ) );

	switch (operator) {
		case '==':
		case '=':
		case 'eq':
			return result === 0;
		case '!=':
		case '<>':
		case 'ne':
			return result !== 0;
		case '>':
		case 'gt':
			return result > 0;
		case '>=':
		case 'ge':
			return result >= 0;
		case '<':
		case 'lt':
			return result < 0;
		case '<=':
		case 'le':
			return result <= 0;
		default:
			return result;
	}
}

// =========================================================================================== \\

function ai4seo_get_localization_parameter(parameter_name) {
	const localization = ai4seo_get_localization_object();

	// Check if ai4seo_localization exists.
	if (!localization) {
		ai4seo_console_debug( 'ai4seo_get_localization_parameter(): localization object could not be found when trying to read parameter: ' + parameter_name ); // attention: no ai4seo_plugin_name here() to prevent circular dependency.
		return false;
	}

	// Check if parameter_name exists in ai4seo_localization.
	if (typeof localization[parameter_name] === 'undefined') {
		console.warn( 'No localization parameter found for: ' + parameter_name ); // attention: no ai4seo_plugin_name here() to prevent circular dependency.
		return false;
	}

	// ai4seo_console_debug('ai4seo_get_localization_parameter(): reading parameter: ' + parameter_name + ' with value: ' + localization[parameter_name]); // attention: no ai4seo_plugin_name here() to prevent circular dependency.

	return localization[parameter_name];
}

// =========================================================================================== \\

function ai4seo_has_asset_context(asset_context) {
	// Read the server-approved context list so JavaScript never infers WordPress screens independently.
	const asset_contexts = ai4seo_get_localization_parameter( 'ai4seo_asset_contexts' );

	return Array.isArray( asset_contexts ) && asset_contexts.includes( asset_context );
}

// =========================================================================================== \\

function ai4seo_get_localization_object() {
	if (typeof ai4seo_localization !== 'undefined') {
		return ai4seo_localization;
	}

	if (typeof window.top !== 'undefined' && typeof window.top.ai4seo_localization !== 'undefined') {
		return window.top.ai4seo_localization;
	}

	return null;
}

// =========================================================================================== \\

function ai4seo_format_number_i18n(number, decimals = 0) {
	number = parseFloat( number );
	decimals = parseInt( decimals, 10 );

	// Avoid writing "NaN" into summaries while a numeric field is temporarily invalid.
	if (isNaN( number )) {
		return '';
	}

	// Keep decimal precision predictable for summary values calculated in JavaScript.
	if (isNaN( decimals ) || decimals < 0) {
		decimals = 0;
	}

	const localization = ai4seo_get_localization_object() || {};
	const decimal_point = typeof localization.ai4seo_number_format_decimal_point === 'string' ? localization.ai4seo_number_format_decimal_point : '.';
	const thousands_sep = typeof localization.ai4seo_number_format_thousands_sep === 'string' ? localization.ai4seo_number_format_thousands_sep : ',';
	const formatted_number_parts = number.toFixed( decimals ).split( '.' );

	// WordPress can intentionally use an empty thousands separator, so use the localized value as-is.
	formatted_number_parts[0] = formatted_number_parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, thousands_sep );

	if (decimals > 0) {
		return formatted_number_parts[0] + decimal_point + formatted_number_parts[1];
	}

	return formatted_number_parts[0];
}


// =========================================================================================== \\

function ai4seo_get_admin_scripts_version_number() {
	return ai4seo_get_localization_parameter( 'ai4seo_admin_scripts_version_number' );
}

// =========================================================================================== \\

function ai4seo_get_assets_directory_url(sub_path) {
	return ai4seo_get_localization_parameter( 'ai4seo_assets_directory_url' ) + '/' + sub_path;
}


// =========================================================================================== \\

function ai4seo_get_plugin_name() {
	return ai4seo_get_localization_parameter( 'ai4seo_plugin_name' );
}

// =========================================================================================== \\

function ai4seo_get_short_plugin_name() {
	return ai4seo_get_localization_parameter( 'ai4seo_short_plugin_name' );
}

// =========================================================================================== \\

function ai4seo_get_plugin_identifier() {
	return ai4seo_get_localization_parameter( 'ai4seo_plugin_identifier' );
}

// =========================================================================================== \\

function ai4seo_normalize_length(length_value, default_value) {
	const parsed_value = parseInt( length_value, 10 );

	if (!isNaN( parsed_value ) && parsed_value > 0) {
		return parsed_value;
	}

	return default_value;
}

// =========================================================================================== \\

function ai4seo_resolve_length_limit(length_key, length_map, fallback_length) {
	const normalized_key = (typeof length_key === 'string') ? length_key.toLowerCase() : '';

	if (normalized_key && length_map && Object.prototype.hasOwnProperty.call( length_map, normalized_key )) {
		return ai4seo_normalize_length( length_map[normalized_key], fallback_length );
	}

	return fallback_length;
}

// =========================================================================================== \\

/**
 * Checks whether a value respects the configured character limit for the given identifier.
 */
function ai4seo_validate_editor_input_length(value, identifier, length_map, fallback_length, field_label, error_code) {
	const max_length = ai4seo_resolve_length_limit( identifier, length_map, fallback_length );

	if (value.length > max_length) {
		const safe_field_label = field_label || wp.i18n.__( 'This field', 'ai-for-seo' );
		const error_message = wp.i18n.sprintf(
			wp.i18n.__( '%1$s cannot exceed %2$d characters.', 'ai-for-seo' ),
			safe_field_label,
			max_length
		);

		ai4seo_show_error_toast( error_code, error_message );
		return false;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Locate a rendered custom-instruction textarea by submitted field name for save/generate validation.
 */
function ai4seo_get_custom_instruction_input_by_name(input_name) {
	return ai4seo_normalize_$( '.ai4seo-custom-instructions-input' ).filter(
        function () {
		return jQuery( this ).attr( 'name' ) === input_name;
        }
    ).first();
}

// =========================================================================================== \\

/**
 * Validate submitted custom-instruction values before AJAX requests can spend credits or save data.
 */
function ai4seo_validate_custom_instruction_input_values(input_values, error_code) {
	if (!input_values || typeof input_values !== 'object') {
		return true;
	}

	for (const input_name in input_values) {
		if (!Object.prototype.hasOwnProperty.call( input_values, input_name )) {
			continue;
		}

		if (input_name.indexOf( 'custom_instructions' ) === -1) {
			continue;
		}

		const input_value = String( input_values[input_name] || '' );
		const $input = ai4seo_get_custom_instruction_input_by_name( input_name );
		const max_length = ai4seo_get_custom_instruction_input_limit( $input );

		if (ai4seo_get_custom_instruction_text_length( input_value ) <= max_length) {
			continue;
		}

		const field_label = ai4seo_exists_$( $input )
			? ($input.attr( 'data-ai4seo-custom-instructions-label' ) || wp.i18n.__( 'Custom instructions', 'ai-for-seo' ))
			: wp.i18n.__( 'Custom instructions', 'ai-for-seo' );

		ai4seo_show_error_toast(
			error_code,
			wp.i18n.sprintf(
				/* translators: 1: Field label, 2: Maximum number of characters. */
				wp.i18n.__( '%1$s cannot exceed %2$d chars.', 'ai-for-seo' ),
				field_label,
				max_length
			)
		);

		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_get_full_domain() {
	// try check ai4seo_site_url.
	ai4seo_site_url = ai4seo_get_localization_parameter( 'ai4seo_site_url' );

	// Check if ai4seo_localization.ai4seo_site_url exists.
	if (ai4seo_site_url) {
		return ai4seo_site_url;
	}

	// fallback to window.location.
	let protocol = window.location.protocol;
	let host = window.location.host;
	return protocol + '//' + host;
}

// =========================================================================================== \\

function ai4seo_get_ai4seo_plugin_directory_url() {
	return ai4seo_get_localization_parameter( 'ai4seo_plugin_directory_url' );
}

// =========================================================================================== \\

function ai4seo_is_json_string(string) {
	try {
		JSON.parse( string );
	} catch (e) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_is_object(object) {
	return object === Object( object );
}

// =========================================================================================== \\

function ai4seo_is_chrome_browser() {
	return navigator.userAgent.indexOf( 'Chrome' ) !== -1;
}

// =========================================================================================== \\

function ai4seo_build_custom_admin_url(subpage = '', additional_url_parameters = {}) {
	let admin_url = ai4seo_get_localization_parameter( 'ai4seo_admin_url' );

	// not subpage given -> read from localization.
	if (!subpage) {
		subpage = ai4seo_get_localization_parameter( 'ai4seo_active_subpage' );
	}

	// fallback to dashboard.
	if (!subpage) {
		subpage = 'dashboard';
	}

	if (!additional_url_parameters || typeof additional_url_parameters !== 'object') {
		additional_url_parameters = {};
	}

	const has_subpage_parameter = Object.prototype.hasOwnProperty.call( additional_url_parameters, 'ai4seo_subpage' );

	if (!has_subpage_parameter || !additional_url_parameters.ai4seo_subpage) {
		additional_url_parameters.ai4seo_subpage = subpage;
	}

	additional_url_parameters.page = 'ai-for-seo';

	// go through all additional parameters and add them to the url.
	for (const [key, value] of Object.entries( additional_url_parameters )) {
		admin_url = ai4seo_add_or_modify_url_parameter( admin_url, key, value );
	}

	return admin_url;
}

// =========================================================================================== \\

function ai4seo_add_or_modify_url_parameter(url, parmeter_name, parameter_value) {
	let url_object = new URL( url );
	let search_params = url_object.searchParams;

	// Set or update the parameter.
	search_params.set( parmeter_name, parameter_value );

	// Return the modified URL as a string.
	return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_remove_url_parameter(url, parameter_name) {
	let url_object = new URL( url );
	let search_params = url_object.searchParams;

	// Remove the parameter.
	search_params.delete( parameter_name );

	// Return the modified URL as a string.
	return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_clean_url_parameter(url, keep_page = true, keep_ai4seo_subpage = false, keep_ai4seo_post_type = false) {
	let url_object = new URL( url );
	let search_params = url_object.searchParams;

	// Remove all ai4seo_-parameters except the ones we want to keep.
	search_params.forEach(
        (value, key) => {
		if (key.startsWith( 'ai4seo_' )) {
			if ((key === 'ai4seo_subpage' && keep_ai4seo_subpage) ||
				(key === 'ai4seo_post_type' && keep_ai4seo_post_type)) {
				return; // Skip removal for this parameter.
			}
			search_params.delete( key );
		}
		// Remove page parameter if not requested to keep.
		if (key === 'page' && !keep_page) {
			search_params.delete( key );
		}
        }
    );

	// Return the modified URL as a string.
	return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_is_yoast_element($yoast_candidate) {
	// Define variable for selector.
	$yoast_candidate = ai4seo_normalize_$( $yoast_candidate );

	// Check if element is found.
	if (!ai4seo_exists_$( $yoast_candidate )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$yoast_candidate\" missing in ai4seo_is_yoast_element() \u2014 cannot resolve SEO field.' );
		return false;
	}

	// Check if element is a yoast-element.
	const $yoast_input_editor = ai4seo_normalize_$( $yoast_candidate.closest( '.yst-replacevar__editor' ) );

	if (!ai4seo_exists_$( $yoast_input_editor )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': element \"$yoast_candidate.closest(\".yst-replacevar__editor\")\" missing in ai4seo_is_yoast_element() \u2014 cannot resolve SEO field.');.
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_is_yoast_selector(selector) {
	if (!selector || typeof selector !== 'string') {
		return false;
	}

	// Prefer the generation registry's explicit source declaration for selectors SOOZ already manages.
	const generate_data_for_input_details = ai4seo_generate_data_for_inputs[selector] || {};

	if (generate_data_for_input_details.metadata_source === 'yoast') {
		return true;
	}

	// Retain heuristic detection for Yoast elements that are not registered generation inputs.
	const yoast_selector_indicators = [
		'yoast',
		'wpseo',
		'focus-keyword-input-metabox',
		'facebook-title-input-metabox',
		'facebook-description-input-metabox',
		'social-title-input-metabox',
		'social-description-input-metabox',
		'twitter-title-input-metabox',
		'twitter-description-input-metabox',
		'x-title-input-metabox',
		'x-description-input-metabox',
		'facebook-title-input-modal',
		'facebook-description-input-modal',
		'twitter-title-input-modal',
		'twitter-description-input-modal',
		'x-title-input-modal',
		'x-description-input-modal',
	];

	return yoast_selector_indicators.some(
        function (indicator) {
		return selector.indexOf( indicator ) !== -1;
        }
    );
}

// =========================================================================================== \\

function ai4seo_console_debug(...args) {
	try {
		if (ai4seo_output_console_debug === true) {
			console.debug( ...args );
		}
	} catch (error) {
		// silently ignore unless uncommented: debug system not initialized yet
		// console.debug(...args);.
	}
}

// ___________________________________________________________________________________________ \\
// === AI GENERATION ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Function to make an ajax call to generate-metadata.php to get the post details.
function ai4seo_generate_with_ai($generate_button, ajax_action, generate_data_for_input_instructions = [], post_id = false, overwrite_data = true, try_read_page_content_via_js = false) {
	$generate_button = ai4seo_normalize_$( $generate_button );

	if (!ai4seo_exists_$( $generate_button )) {
		ai4seo_show_generic_error_toast( 1112301025 );
		console.error( ai4seo_get_plugin_name() + ': No valid submit_button defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.' );
		return;
	}

	if (!ajax_action) {
		ai4seo_show_generic_error_toast( 4310301025 );
		console.error( ai4seo_get_plugin_name() + ': No ajax_action defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.' );
		return;
	}

	// Resolve the generating modal/container before post-id fallback so stacked parent modals cannot leak their id.
	const $closest_container = ai4seo_get_closest_container_$( $generate_button );
	let processing_context = '';

	if (ajax_action === 'ai4seo_generate_attachment_attributes') {
		processing_context = 'attachment-attributes';
	} else if (ajax_action === 'ai4seo_generate_metadata') {
		processing_context = 'metadata';
	}

	// Read post-id from the current generation scope if it was not provided by a pre-built button.
	if (!post_id) {
		post_id = ai4seo_get_post_id( processing_context, $closest_container );
	}

	if (!post_id || isNaN( post_id )) {
		ai4seo_show_generic_error_toast( 132120824 );
		console.error( ai4seo_get_plugin_name() + ': No valid post_id defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.' );
		return;
	}

	if (!generate_data_for_input_instructions || typeof generate_data_for_input_instructions !== 'object') {
		ai4seo_show_generic_error_toast( 4410301025 );
		console.error( ai4seo_get_plugin_name() + ': No proper generate_data_for_selectors_by_generation_field_identifier defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.' );
		return;
	}

	// collect data.
	let ajax_data = {
		ai4seo_post_id: post_id,
	};

	// check for Divi Builder placeholder -> dont read from this page.
	if (ai4seo_exists_$( '.wp-block-divi-placeholder' )) {
		try_read_page_content_via_js = false;
	}

	// check if we should try to read the page content via js.
	if (try_read_page_content_via_js) {
		// Define variable for the content based on ai4seo_get_post_content()
		// add content as ai4seo_content to data.
		ajax_data.ai4seo_content = ai4seo_get_post_content();
	}

	// generate_data_for_selectors_by_generation_field_identifier can be {'{{field_identifier}}': {}, ...}, if value is an empty object, try to populate with suitable selectors.
	generate_data_for_input_instructions = ai4seo_get_normalized_generation_fields( generate_data_for_input_instructions, overwrite_data, $closest_container, post_id );

	if (!generate_data_for_input_instructions
		|| typeof generate_data_for_input_instructions !== 'object'
		|| Object.keys( generate_data_for_input_instructions ).length === 0) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Could not find any suitable fields to generate data for.', 'ai-for-seo' ) );
		console.warn( ai4seo_get_plugin_name() + ': No suitable generate_data_for_selectors_by_generation_field_identifier found in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.' );
		return;
	}

	ajax_data.ai4seo_generation_fields = Object.keys( generate_data_for_input_instructions );

	// collect affected generate buttons and old input values.
	const affected_generate_buttons = ai4seo_collect_generate_data_for_inputs_generate_buttons( generate_data_for_input_instructions, $closest_container );
	const old_input_values = ai4seo_collect_generate_data_for_inputs_old_input_values(
		post_id,
		$closest_container,
		generate_data_for_input_instructions
	);

	if (old_input_values) {
		ajax_data.ai4seo_old_input_values = old_input_values;
	}

	// Include the entry-specific instruction in manual generation so unsaved editor text is honored immediately.
	const $entry_custom_instructions_input = $closest_container.find( '.ai4seo-entry-custom-instructions-input' ).first();

	if (ai4seo_exists_$( $entry_custom_instructions_input )) {
		const entry_custom_instructions_input_name = $entry_custom_instructions_input.attr( 'name' ) || '';
		const entry_custom_instructions_value = String( $entry_custom_instructions_input.val() || '' );
		// Validate the editor-specific instruction with the same field-name map used by save requests.
		const entry_custom_instruction_input_values = {
			[entry_custom_instructions_input_name]: entry_custom_instructions_value,
		};

		if (!ai4seo_validate_custom_instruction_input_values( entry_custom_instruction_input_values, 3416141025 )) {
			return;
		}

		ajax_data.ai4seo_entry_custom_instructions = entry_custom_instructions_value;
	}

	ai4seo_lock_and_disable_lockable_input_fields();

	// Replace button-label with loading-html.
	ai4seo_add_loading_html_to_element( $generate_button );
	ai4seo_add_loading_html_to_element( affected_generate_buttons );

	// debug ajax data.
	ai4seo_console_debug( ajax_data );

	// show loading toast.
	const generation_process_time = 10;
	ai4seo_show_loading_toast(
		wp.i18n.sprintf(
			/* translators: 1: plugin name, 2: estimated generation time in seconds */
			wp.i18n.__( '%1$s is generating content now. This process can take up to %2$d seconds.', 'ai-for-seo' ),
			ai4seo_get_short_plugin_name(),
			generation_process_time
		)
	);

	// call desired ajax action.
	ai4seo_perform_ajax_call( ajax_action, ajax_data )
		.then(
            async response => {
			// debug response.
			ai4seo_console_debug( response );
			response.generated_data = response.generated_data || {};
			// check for response.generated_data.
			if (!response.generated_data || typeof response.generated_data !== 'object') {
				ai4seo_show_error_toast( 4410301027, wp.i18n.__( 'No generated data received from the server.', 'ai-for-seo' ) );
				console.error( ai4seo_get_plugin_name() + ': No generated_data received in ai4seo_generate_with_ai() \u2014 cannot fill generated data into inputs.' );
				return;
			}

			if (typeof response.new_credits_balance === 'number') {
				ai4seo_remaining_credits = response.new_credits_balance;
			}

			let credits_consumed = 0;
			if (typeof response.credits_consumed === 'number') {
				credits_consumed = response.credits_consumed;
			}

			// Derive unresolved fields from the original request so partial responses need no separate public payload field.
			const requested_field_identifiers = Object.keys( generate_data_for_input_instructions );
			const unresolved_field_identifiers = requested_field_identifiers.filter(
				this_field_identifier => !Object.prototype.hasOwnProperty.call( response.generated_data, this_field_identifier )
			);
			const preview_transition_elements = $generate_button.hasClass( 'ai4seo-generate-all-button' )
				? await ai4seo_hide_editor_preview_values_for_generation( $closest_container )
				: [];

			// Fill generated values first, then refresh only SOOZ modal source labels from the same server timestamp.
			try {
				ai4seo_fill_generated_data_into_inputs(
					response.generated_data || {},
					generate_data_for_input_instructions,
					response.generated_at_output || '',
					$closest_container
				);
				ai4seo_track_seopress_generate_all_metadata( $generate_button, $closest_container, response.generated_data );

				// Generated content bypasses native input events, so explicitly synchronize all shared live feedback.
				if (processing_context === 'metadata' || processing_context === 'attachment-attributes') {
					ai4seo_refresh_editor_previews( $closest_container );
					ai4seo_refresh_metadata_editor_length_feedback( $closest_container );
				}
			} finally {
				ai4seo_reveal_editor_preview_values_after_generation( preview_transition_elements );
			}

			// Build plain and node-based messages from the same translated template.
			const generated_field_identifiers = Object.keys( response.generated_data );
			const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names( generated_field_identifiers );
			const human_readable_generated_fields = human_readable_generated_field_identifiers.join( ', ' );
			const success_toast_message_format = wp.i18n.__( 'Generated %1s. Consumed: %2s. Remaining: %3s.', 'ai-for-seo' );
			const credits_consumed_token = 'AI4SEO_CREDITS_CONSUMED_TOAST_TOKEN';
			const remaining_credits_token = 'AI4SEO_REMAINING_CREDITS_TOAST_TOKEN';
			const credit_badge_replacements = {};
			const success_toast_message = wp.i18n.sprintf(
				success_toast_message_format,
				human_readable_generated_fields,
				credits_consumed,
				ai4seo_remaining_credits
			);
			const success_toast_message_template = wp.i18n.sprintf(
				success_toast_message_format,
				human_readable_generated_fields,
				credits_consumed_token,
				remaining_credits_token
			);
			// Register badge replacements separately so translated placeholder order stays intact.
			credit_badge_replacements[credits_consumed_token] = credits_consumed;
			credit_badge_replacements[remaining_credits_token] = ai4seo_remaining_credits;
			const $success_toast_message = ai4seo_get_toast_message_with_credit_badges_$(
				success_toast_message_template,
				credit_badge_replacements
			);

			// Partial success remains non-blocking, but users must know which existing values were preserved.
			if (unresolved_field_identifiers.length > 0) {
				const human_readable_unresolved_fields = ai4seo_get_human_readable_generation_field_names( unresolved_field_identifiers ).join( ', ' );
				const partial_success_toast_message = wp.i18n.sprintf(
					/* translators: 1: Generated field names. 2: Unresolved field names. 3: Credits consumed. 4: Credits remaining. */
					wp.i18n.__( 'Generated %1$s. Unresolved fields were left unchanged: %2$s. Consumed: %3$s. Remaining: %4$s.', 'ai-for-seo' ),
					human_readable_generated_fields,
					human_readable_unresolved_fields,
					credits_consumed,
					ai4seo_remaining_credits
				);

				ai4seo_show_warning_toast( partial_success_toast_message );
				return;
			}

			// Complete responses retain the established success toast with visual credit badges.
			ai4seo_show_success_toast( success_toast_message, null, $success_toast_message );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 612181225, error );
            }
        )
		.finally(
            () => {
			// Remove loading-html from button-label.
			ai4seo_remove_loading_html_from_element( $generate_button );
			ai4seo_remove_loading_html_from_element( affected_generate_buttons );
			ai4seo_unlock_and_enable_lockable_input_fields();
			ai4seo_init_generate_all_buttons( $closest_container, processing_context || null );
            }
        );
}

// =========================================================================================== \\

/**
 * Collects the individual generation buttons affected by one normalized request.
 *
 * @param {Object} generate_data_for_input_instructions Normalized generation instructions.
 * @param {jQuery|HTMLElement|null} scope Optional initiating editor instance.
 * @return {Array<jQuery>} Matching generation buttons.
 */
function ai4seo_collect_generate_data_for_inputs_generate_buttons(generate_data_for_input_instructions, scope = null) {
	const affected_generate_buttons = [];

	// go through each ai4seo_generation_fields and collect affected generate buttons.
	jQuery.each(
		generate_data_for_input_instructions,
		function (this_generation_field_identifier, this_generation_field_details) {
			if (typeof this_generation_field_details !== 'object' || typeof this_generation_field_details.value !== 'string') {
				console.warn( ai4seo_get_plugin_name() + ': no value defined for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping current value collection.' );
			}

			// Find the individual generation button associated with each eligible selector.
			jQuery.each(
				this_generation_field_details.selectors,
				function (_, this_generate_data_for_selector) {
					const this_generate_data_for_input_details = ai4seo_generate_data_for_inputs[this_generate_data_for_selector];

					if (!this_generate_data_for_input_details) {
						console.error( ai4seo_get_plugin_name() + ': no generate data for input details found for selector: ' + this_generate_data_for_selector + ' \u2014 cannot find generate button for this selector.' );
						return;
					}

					// Proxy inputs intentionally participate without an individual button.
					if (!this_generate_data_for_input_details.add_generate_button) {
						return;
					}

					const $this_generate_data_for_input = ai4seo_normalize_$( this_generate_data_for_selector, scope );

					if (!ai4seo_exists_$( $this_generate_data_for_input )) {
						console.warn( ai4seo_get_plugin_name() + ': element \"$this_generate_data_for_input\" missing in ai4seo_generate_with_ai() \u2014 cannot read/generate data for this selector.' );
						return;
					}

					const $this_possible_generate_button = ai4seo_try_find_generate_button_by_input_$( $this_generate_data_for_input );

					if (!ai4seo_exists_$( $this_possible_generate_button )) {
						// Generate All can include a mounted field before its optional individual control is attached.
						return;
					}

					affected_generate_buttons.push( $this_possible_generate_button );
				}
			);
		}
	);

	return affected_generate_buttons;
}

// =========================================================================================== \\

/**
 * Reads the six AIOSEO metadata fields from its reactive store or native hidden payload.
 *
 * @param {jQuery|HTMLElement|null} scope Optional AIOSEO editor instance.
 * @return {Object} Metadata values indexed by SOOZ field identifier.
 */
function ai4seo_read_aioseo_generation_metadata(scope = null) {
	const aioseo_metadata = {};
	const aioseo_post_editor_store = ai4seo_get_aioseo_post_editor_store( scope );
	const $aioseo_hidden_field = ai4seo_get_aioseo_post_settings_hidden_field_$();
	const aioseo_post_settings = aioseo_post_editor_store
		? aioseo_post_editor_store.currentPost
		: ai4seo_parse_aioseo_post_settings( $aioseo_hidden_field ) || {};

	jQuery.each(
		ai4seo_aioseo_generation_field_details,
		function (metadata_identifier, field_details) {
			const current_value = aioseo_post_settings[field_details.post_settings_key];

			aioseo_metadata[metadata_identifier] = typeof current_value === 'string'
				? current_value
				: '';

			if (Object.prototype.hasOwnProperty.call( ai4seo_aioseo_pending_metadata, metadata_identifier )) {
				aioseo_metadata[metadata_identifier] = String( ai4seo_aioseo_pending_metadata[metadata_identifier] ?? '' );
			}
		}
	);

	return aioseo_metadata;
}

// =========================================================================================== \\

/**
 * Parses AIOSEO's canonical hidden settings value and accepts only a plain JSON object shape.
 *
 * @param {jQuery|HTMLElement|string} aioseo_hidden_field AIOSEO hidden settings field.
 * @return {Object|null} Parsed settings or null when the payload cannot be updated safely.
 */
function ai4seo_parse_aioseo_post_settings(aioseo_hidden_field) {
	const $aioseo_hidden_field = ai4seo_normalize_$( aioseo_hidden_field ).first();

	if (!ai4seo_exists_$( $aioseo_hidden_field )) {
		return null;
	}

	try {
		const raw_post_settings = String( $aioseo_hidden_field.val() ?? '' ).trim();

		// AIOSEO renders this input empty before its store hydrates; an empty object is not save-ready state.
		if (!raw_post_settings) {
			return null;
		}

		const parsed_post_settings = JSON.parse( raw_post_settings );

		if (!parsed_post_settings || typeof parsed_post_settings !== 'object' || Array.isArray( parsed_post_settings )) {
			return null;
		}

		// A complete post payload always identifies its owner; reject partial placeholder objects.
		if (!(parseInt( parsed_post_settings.id, 10 ) > 0)) {
			return null;
		}

		return parsed_post_settings;
	} catch (error) {
		return null;
	}
}

// =========================================================================================== \\

/**
 * Checks whether generated AIOSEO values still need a persistence path.
 *
 * @return {boolean} Whether at least one value remains pending.
 */
function ai4seo_has_pending_aioseo_metadata() {
	return Object.keys( ai4seo_aioseo_pending_metadata ).length > 0;
}

// =========================================================================================== \\

/**
 * Merges generated values into AIOSEO's canonical hidden payload before WordPress serializes it.
 *
 * @return {boolean} Whether the hidden payload was updated.
 */
function ai4seo_flush_pending_aioseo_metadata_to_hidden_field() {
	const $aioseo_hidden_field = ai4seo_get_aioseo_post_settings_hidden_field_$();

	if (!ai4seo_exists_$( $aioseo_hidden_field )) {
		return false;
	}

	const aioseo_post_settings = ai4seo_parse_aioseo_post_settings( $aioseo_hidden_field );

	if (aioseo_post_settings === null) {
		return false;
	}

	jQuery.each(
		ai4seo_aioseo_pending_metadata,
		function (metadata_identifier, metadata_value) {
			const field_details = ai4seo_aioseo_generation_field_details[metadata_identifier] || {};
			const post_settings_key = field_details.post_settings_key || '';

			if (post_settings_key) {
				aioseo_post_settings[post_settings_key] = String( metadata_value ?? '' );
			}
		}
	);

	const serialized_post_settings = JSON.stringify( aioseo_post_settings );
	$aioseo_hidden_field.val( serialized_post_settings );

	const hidden_field_element = $aioseo_hidden_field.get( 0 );

	if (hidden_field_element) {
		hidden_field_element.value = serialized_post_settings;
	}

	return true;
}

// =========================================================================================== \\

/**
 * Retries a pending hidden-payload merge while AIOSEO hydrates its native editor state.
 *
 * @param {boolean} restart Whether a newly generated value should restart the bounded retry window.
 */
function ai4seo_schedule_pending_aioseo_metadata_flush(restart = false) {
	if (restart) {
		ai4seo_aioseo_pending_flush_attempts = 0;
	}

	if (ai4seo_aioseo_pending_flush_timer !== null
		|| !ai4seo_has_pending_aioseo_metadata()
		|| !ai4seo_exists_$( ai4seo_get_aioseo_post_settings_hidden_field_$() )
		|| ai4seo_aioseo_pending_flush_attempts >= ai4seo_aioseo_pending_flush_max_attempts) {
		return;
	}

	ai4seo_aioseo_pending_flush_timer = window.setTimeout(
		function () {
			ai4seo_aioseo_pending_flush_timer = null;
			ai4seo_aioseo_pending_flush_attempts++;

			if (ai4seo_flush_pending_aioseo_metadata_to_hidden_field()) {
				ai4seo_aioseo_pending_flush_attempts = 0;
				return;
			}

			ai4seo_schedule_pending_aioseo_metadata_flush();
		},
		ai4seo_aioseo_pending_flush_retry_interval_ms
	);
}

// =========================================================================================== \\

/**
 * Prevents native WordPress submission while AIOSEO's hidden payload is still unsafe to merge.
 *
 * @param {Event} event Native form-submit event.
 */
function ai4seo_handle_aioseo_post_form_submit(event) {
	if (!ai4seo_has_pending_aioseo_metadata() || ai4seo_flush_pending_aioseo_metadata_to_hidden_field()) {
		return;
	}

	if (event && typeof event.preventDefault === 'function') {
		event.preventDefault();
		event.stopImmediatePropagation();
	}

	ai4seo_show_warning_toast(
		wp.i18n.__(
			'AIOSEO is still preparing its editor data. Please wait a moment, then save the entry again.',
			'ai-for-seo'
		)
	);
	ai4seo_schedule_pending_aioseo_metadata_flush( true );
}

// =========================================================================================== \\

/**
 * Replaces a Quill contenteditable value through the browser editing path AIOSEO observes.
 *
 * @param {jQuery|string|Element} $editor AIOSEO editor or selector.
 * @param {*} value Generated field value.
 * @return {boolean} Whether a supported editor was updated.
 */
function ai4seo_fill_contenteditable_with_exec_command($editor, value) {
	$editor = ai4seo_normalize_$( $editor ).first();

	if (!ai4seo_exists_$( $editor )) {
		return false;
	}

	const editor_element = $editor.get( 0 );

	if (!editor_element || editor_element.getAttribute( 'contenteditable' ) !== 'true') {
		return false;
	}

	editor_element.focus();

	const selection = window.getSelection ? window.getSelection() : null;
	let inserted_with_exec_command = false;

	if (selection && document.createRange) {
		const range = document.createRange();
		range.selectNodeContents( editor_element );
		selection.removeAllRanges();
		selection.addRange( range );

		try {
			inserted_with_exec_command = document.execCommand( 'insertText', false, String( value || '' ) );
		} catch (error) {
			inserted_with_exec_command = false;
		}
	}

	if (!inserted_with_exec_command) {
		editor_element.textContent = String( value || '' );
		editor_element.dispatchEvent( new Event( 'input', {bubbles: true} ) );
	}

	editor_element.dispatchEvent( new Event( 'change', {bubbles: true} ) );
	return true;
}

// =========================================================================================== \\

/**
 * Applies one generated field to AIOSEO's reactive store, mounted editor, proxy, and hidden payload.
 *
 * @param {string} metadata_identifier SOOZ metadata identifier.
 * @param {*} value Generated field value.
 * @param {jQuery|HTMLElement|null} scope AIOSEO editor instance that initiated the generation.
 * @param {boolean} notify_page_builder_change Whether AIOSEO's page-builder bridge should mark the entry dirty.
 * @return {boolean} Whether an AIOSEO persistence path reached the requested value.
 */
function ai4seo_set_aioseo_metadata_content(metadata_identifier, value, scope = null, notify_page_builder_change = true) {
	const field_details = ai4seo_aioseo_generation_field_details[metadata_identifier] || {};
	const post_settings_key = field_details.post_settings_key || '';
	const visual_editor_selector = field_details.visual_editor_selector || '';

	if (!post_settings_key || !visual_editor_selector) {
		return false;
	}

	value = String( value ?? '' );
	ai4seo_aioseo_pending_metadata[metadata_identifier] = value;
	const store_was_updated = ai4seo_update_aioseo_post_editor_store( metadata_identifier, value, scope, notify_page_builder_change );
	const was_already_applying_metadata = ai4seo_is_applying_metadata_to_live_aioseo_editor;
	ai4seo_is_applying_metadata_to_live_aioseo_editor = true;
	let visual_editor_was_updated = false;

	try {
		const $visual_editor = ai4seo_normalize_$( visual_editor_selector, scope ).first();

		if (ai4seo_exists_$( $visual_editor )) {
			visual_editor_was_updated = ai4seo_fill_contenteditable_with_exec_command( $visual_editor, value );
		}
	} finally {
		ai4seo_is_applying_metadata_to_live_aioseo_editor = was_already_applying_metadata;
	}

	const proxy_selector = ai4seo_get_aioseo_generation_proxy_selector( metadata_identifier );
	const $proxy = ai4seo_normalize_$( proxy_selector, scope ).first();

	if (ai4seo_exists_$( $proxy )) {
		$proxy.val( value );
	}

	const hidden_payload_was_updated = ai4seo_flush_pending_aioseo_metadata_to_hidden_field();

	// Store updates cover mounted and unmounted fields; the fallback needs both visual and form state.
	if (store_was_updated || (visual_editor_was_updated && hidden_payload_was_updated)) {
		delete ai4seo_aioseo_pending_metadata[metadata_identifier];
	}

	if (Object.prototype.hasOwnProperty.call( ai4seo_aioseo_pending_metadata, metadata_identifier )) {
		ai4seo_schedule_pending_aioseo_metadata_flush( true );
	}

	return store_was_updated || hidden_payload_was_updated || visual_editor_was_updated;
}

// =========================================================================================== \\

/**
 * Collects the external metadata sources represented by normalized generation instructions.
 *
 * @param {Object} generation_input_instructions Normalized generation instructions.
 * @return {Set<string>} Requested source identifiers.
 */
function ai4seo_get_requested_generation_metadata_sources(generation_input_instructions) {
	const requested_metadata_sources = new Set();

	for (const generation_input_details of Object.values( generation_input_instructions || {} )) {
		if (!generation_input_details || typeof generation_input_details !== 'object') {
			continue;
		}

		for (const generation_input_selector of generation_input_details.selectors || []) {
			const registered_input_details = ai4seo_generate_data_for_inputs[generation_input_selector] || {};

			if (registered_input_details.metadata_source) {
				requested_metadata_sources.add( registered_input_details.metadata_source );
			}
		}
	}

	return requested_metadata_sources;
}

// =========================================================================================== \\

/**
 * Collects existing generation-field values from the initiating editor and metadata source.
 *
 * @param {number} post_id Post ID.
 * @param {jQuery|HTMLElement|null} scope Optional initiating editor instance.
 * @param {Object} generation_input_instructions Normalized generation instructions.
 * @return {Object} Existing values indexed by generation-field identifier.
 */
function ai4seo_collect_generate_data_for_inputs_old_input_values(post_id, scope = null, generation_input_instructions = {}) {
	const existing_field_values = {};
	const requested_metadata_sources = ai4seo_get_requested_generation_metadata_sources( generation_input_instructions );

	// Read each structured editor payload at most once while the registry enumerates duplicate field selectors.
	let yoast_generation_metadata = null;
	let aioseo_generation_metadata = null;

	jQuery.each(
		ai4seo_generate_data_for_inputs,
		function (this_generate_data_for_input_selector, this_generate_data_for_input_details) {
			if (requested_metadata_sources.size > 0
				&& !requested_metadata_sources.has( this_generate_data_for_input_details.metadata_source || '' )) {
				return;
			}

			// Resolve the common generation-field identifier from the registry entry.
			let this_identifier = '';

			if (typeof this_generate_data_for_input_details.metadata_identifier !== 'undefined' && this_generate_data_for_input_details.metadata_identifier) {
				this_identifier = this_generate_data_for_input_details.metadata_identifier;
			} else if (typeof this_generate_data_for_input_details.attachment_attributes_identifier !== 'undefined' && this_generate_data_for_input_details.attachment_attributes_identifier) {
				this_identifier = this_generate_data_for_input_details.attachment_attributes_identifier;
			} else {
				console.warn( ai4seo_get_plugin_name() + ': no identifier defined for generate data for input selector: ' + this_generate_data_for_input_selector + ' \u2014 cannot try to fetch existing field values.' );
				return;
			}

			// Preserve the first non-empty value found for an identifier with multiple selectors.
			if (typeof existing_field_values[this_identifier] !== 'undefined' && existing_field_values[this_identifier]) {
				return;
			}

			const $this_generate_data_for_input = ai4seo_normalize_$( this_generate_data_for_input_selector, scope );

			// Keep the registry selector intact while resolving it inside the initiating editor.
			if (!ai4seo_exists_$( $this_generate_data_for_input )) {
				return;
			}

			let this_value_candidate = '';

			// Structured editors expose authoritative state outside their rendered text content.
			if (this_generate_data_for_input_details.metadata_source === 'yoast') {
				if (yoast_generation_metadata === null) {
					yoast_generation_metadata = ai4seo_read_yoast_generation_metadata( post_id );
				}

				if (!Object.prototype.hasOwnProperty.call( yoast_generation_metadata, this_identifier )) {
					return;
				}

				this_value_candidate = yoast_generation_metadata[this_identifier];
			} else if (this_generate_data_for_input_details.metadata_source === 'aioseo') {
				if (aioseo_generation_metadata === null) {
					aioseo_generation_metadata = ai4seo_read_aioseo_generation_metadata( scope );
				}

				if (!Object.prototype.hasOwnProperty.call( aioseo_generation_metadata, this_identifier )) {
					return;
				}

				this_value_candidate = aioseo_generation_metadata[this_identifier];
			} else {
				this_value_candidate = ai4seo_get_input_value( $this_generate_data_for_input );
			}

			if (!this_value_candidate || typeof this_value_candidate !== 'string' || this_value_candidate.length === 0) {
				return;
			}

			// Rank Math can wrap an existing value in a one-item JSON collection.
			if (this_value_candidate.startsWith( '[{"value":"' ) && this_value_candidate.endsWith( '"}]' )) {
				try {
					const parsed_value = JSON.parse( this_value_candidate );

					if (Array.isArray( parsed_value ) && parsed_value.length > 0 && typeof parsed_value[0].value === 'string') {
						existing_field_values[this_identifier] = parsed_value[0].value;
						return;
					}
				} catch (error) {
					// Retain the original value when Rank Math's candidate is not valid JSON.
				}
			}

			existing_field_values[this_identifier] = this_value_candidate;
		}
	);

	return existing_field_values;
}

// =========================================================================================== \\

function ai4seo_get_human_readable_generation_field_names(generated_field_identifiers) {
	let human_readable_generation_fields = [];

	// use ai4seo_metadata_labels and ai4seo_attachment_attributes_labels to get human readable field names.
	jQuery.each(
        generated_field_identifiers,
        function (this_index, this_generated_field_identifier) {
		let human_readable_field_name = this_generated_field_identifier;

		// check in ai4seo_metadata_labels (identifier: label).
		if (typeof ai4seo_metadata_labels[this_generated_field_identifier] === 'string') {
			human_readable_field_name = ai4seo_metadata_labels[this_generated_field_identifier];
		} else if (typeof ai4seo_attachment_attribute_labels[this_generated_field_identifier] === 'string') {
			human_readable_field_name = ai4seo_attachment_attribute_labels[this_generated_field_identifier];
		}

		human_readable_generation_fields.push( human_readable_field_name );
        }
    );

	return human_readable_generation_fields;
}

// =========================================================================================== \\

/**
 * Normalizes requested generation fields against fields present in the initiating editor.
 *
 * @param {Object|Array} generate_data_for_input_instructions Requested fields or instructions.
 * @param {boolean} overwrite_data Whether populated fields remain eligible.
 * @param {jQuery|HTMLElement|null} scope Optional initiating editor instance.
 * @param {number|boolean} post_id Post ID when structured editor state requires it.
 * @return {Object} Normalized generation instructions.
 */
function ai4seo_get_normalized_generation_fields(generate_data_for_input_instructions, overwrite_data = true, scope = null, post_id = false) {
	let normalized_generate_data_for_input_instructions = {};

	// Cache structured editor state so duplicate selectors share the same emptiness decision.
	let live_yoast_metadata = null;
	let live_aioseo_metadata = null;

	if (!generate_data_for_input_instructions || typeof generate_data_for_input_instructions !== 'object' || Object.keys( generate_data_for_input_instructions ).length === 0) {
		return normalized_generate_data_for_input_instructions;
	}

	// 0. NORMALIZE INPUT
	// generation_field_selectors can be {'{{field_identifier}}': {}, ...}, if value is an empty object, try to populate with suitable selectors
	// find all keys with empty object as value, then go through ai4seo_generate_data_for_inputs and collect suitable selectors
	jQuery.each(
        generate_data_for_input_instructions,
        function (this_generation_field_identifier, this_generate_data_for_input_instruction) {
		let this_credits_cost = 1;

		// handle case where field_identifier is numeric (array instead of object), e.g. [ '{{field_identifier}}', ... ]
		// if field_identifier is numeric, assume "selectors" as the field_identifier and selectors as an empty object.
		if (!isNaN( this_generation_field_identifier )) {
			this_generation_field_identifier = this_generate_data_for_input_instruction;
			this_credits_cost = ai4seo_get_generation_field_credits_cost( this_generation_field_identifier );
			this_generate_data_for_input_instruction = {'selectors': [], 'value': '', 'credits': this_credits_cost};
		}

		this_credits_cost = ai4seo_get_generation_field_credits_cost( this_generation_field_identifier );

		// check if this_generate_data_for_input_instruction is just an array of selectors (strings).
		if (typeof this_generate_data_for_input_instruction === 'object' && Array.isArray( this_generate_data_for_input_instruction )) {
			this_generate_data_for_input_instruction = {
				'selectors': this_generate_data_for_input_instruction,
				'value': '',
				'credits': this_credits_cost
			};
		}

		normalized_generate_data_for_input_instructions[this_generation_field_identifier] = this_generate_data_for_input_instruction;
        }
    );

	// 1. POPULATE WITH SUITABLE SELECTORS
	// go through ai4seo_generate_data_for_inputs and collect suitable selectors
	jQuery.each(
        normalized_generate_data_for_input_instructions,
        function (this_generation_field_identifier, this_generate_data_for_input_instruction) {
		jQuery.each(
            ai4seo_generate_data_for_inputs,
            function (this_generate_data_for_input_selector, generate_data_for_input_details) {
			// if generation_field_details.metadata_identifier or generation_field_details.attachment_attributes_identifier matches field_identifier, add selector to generation_field_selectors.
			if (generate_data_for_input_details.metadata_identifier === this_generation_field_identifier ||
				generate_data_for_input_details.attachment_attributes_identifier === this_generation_field_identifier) {

				// add selector to generation_field_selectors.
				if (!normalized_generate_data_for_input_instructions[this_generation_field_identifier]) {
					let this_credits_cost = ai4seo_get_generation_field_credits_cost( this_generation_field_identifier );

					normalized_generate_data_for_input_instructions[this_generation_field_identifier] = {
						'selectors': [],
						'value': '',
						'credits': this_credits_cost
					};
				}

				normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].push( this_generate_data_for_input_selector );
			}
            }
        );
        }
    );

	// 2. DISCARD UNSUITABLE SELECTORS & COLLECT VALUES
	// go through normalized_generate_data_for_selectors_by_generation_field_identifier and remove selectors that could not be found on the page
	// if found and overwrite_data is false, check if the current value is empty, if not, remove the selector from the list
	jQuery.each(
        normalized_generate_data_for_input_instructions,
        function (this_generation_field_identifier, generation_field_details) {
		let this_generate_data_for_input_selectors = generation_field_details.selectors;

		// remove empty generation fields, if there are no suitable selectors found.
		if (!Array.isArray( this_generate_data_for_input_selectors ) || this_generate_data_for_input_selectors.length === 0) {
			delete normalized_generate_data_for_input_instructions[this_generation_field_identifier];
			return;
		}

		let this_credits_cost = ai4seo_get_generation_field_credits_cost( this_generation_field_identifier );

		jQuery.each(
            [...this_generate_data_for_input_selectors],
            function (_, this_generate_data_for_input_selector) {
			const this_index = normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].indexOf( this_generate_data_for_input_selector );

			if (this_index <= -1) {
				console.warn( ai4seo_get_plugin_name() + ': element \"$this_generate_data_for_input\" missing in ai4seo_generate_with_ai() \u2014 cannot read/generate data for this selector.' );
				return;
			}

			// get value of the selector.
			const $this_generate_data_for_input = ai4seo_normalize_$( this_generate_data_for_input_selector, scope );

			if (!ai4seo_exists_$( $this_generate_data_for_input )) {
				normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].splice( this_index, 1 );
				return;
			}

			const this_generate_data_for_input_details = ai4seo_generate_data_for_inputs[this_generate_data_for_input_selector] || {};
			let this_current_value = '';

			// Explicit Yoast state determines emptiness without exposing rendered replacement-variable badges.
			if (this_generate_data_for_input_details.metadata_source === 'yoast') {
				if (live_yoast_metadata === null) {
					live_yoast_metadata = ai4seo_read_live_yoast_metadata( post_id );
				}

				this_current_value = Object.prototype.hasOwnProperty.call( live_yoast_metadata, this_generation_field_identifier )
					? live_yoast_metadata[this_generation_field_identifier]
					: '';
			} else if (this_generate_data_for_input_details.metadata_source === 'aioseo') {
				if (live_aioseo_metadata === null) {
					live_aioseo_metadata = ai4seo_read_aioseo_generation_metadata( scope );
				}

				this_current_value = Object.prototype.hasOwnProperty.call( live_aioseo_metadata, this_generation_field_identifier )
					? live_aioseo_metadata[this_generation_field_identifier]
					: '';
			} else {
				this_current_value = ai4seo_get_input_value( $this_generate_data_for_input );
			}

			if (!overwrite_data && this_current_value && this_current_value.toString().trim() !== '') {
				normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].splice( this_index, 1 );
				return;
			}

			// set current value as a field in the object.
			normalized_generate_data_for_input_instructions[this_generation_field_identifier]['value'] = this_current_value;

			normalized_generate_data_for_input_instructions[this_generation_field_identifier]['credits'] = this_credits_cost;
            }
        );
        }
    );

	// 3. REMOVE EMPTY GENERATION FIELDS
	jQuery.each(
        {...normalized_generate_data_for_input_instructions},
        function (this_generation_field_identifier, generation_field_details) {
		let this_generate_data_for_input_selectors = generation_field_details.selectors;

		// remove empty generation fields, if there are no suitable selectors found.
		if (!Array.isArray( this_generate_data_for_input_selectors ) || this_generate_data_for_input_selectors.length === 0) {
			delete normalized_generate_data_for_input_instructions[this_generation_field_identifier];
		}
        }
    );

	// 4. UNIQUE VALUES FOR SELECTORS
	jQuery.each(
        normalized_generate_data_for_input_instructions,
        function (this_generation_field_identifier, generation_field_details) {
		let this_generate_data_for_input_selectors = generation_field_details.selectors;

		// make selectors unique.
		normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'] = [...new Set( this_generate_data_for_input_selectors )];
        }
    );

	return normalized_generate_data_for_input_instructions;
}

// =========================================================================================== \\

function ai4seo_get_generation_field_credits_cost(generation_field_identifier) {
	let metadata_price_table = ai4seo_get_metadata_price_table();
	let attachment_attributes_price_table = ai4seo_get_attachment_attributes_price_table();

	let credits_cost = 1;

	if (metadata_price_table && typeof metadata_price_table === 'object' && typeof metadata_price_table[generation_field_identifier] === 'number') {
		credits_cost = metadata_price_table[generation_field_identifier];
	} else if (attachment_attributes_price_table && typeof attachment_attributes_price_table === 'object' && typeof attachment_attributes_price_table[generation_field_identifier] === 'number') {
		credits_cost = attachment_attributes_price_table[generation_field_identifier];
	}

	return credits_cost;
}


// =========================================================================================== \\

// Function to go through the selector mapping and fill the values.
function ai4seo_fill_generated_data_into_inputs(generated_data = {}, generate_data_for_input_instructions, generated_at_output = '', scope = null) {
	// go through each generation_fields (field_identifier -> {selectors: [], value: 'xxx') and fill the values into the inputs.
	jQuery.each(
		generate_data_for_input_instructions,
		function (this_generation_field_identifier, this_generate_data_for_input_instruction) {
			const this_generation_data_for_input_selectors = this_generate_data_for_input_instruction.selectors || [];

			if (this_generation_data_for_input_selectors.length <= 0) {
				console.error( ai4seo_get_plugin_name() + ': No selectors defined for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping filling generated data into inputs.' );
				return;
			}

			const this_generated_data = generated_data[this_generation_field_identifier] || '';

			if (!this_generated_data || this_generated_data.toString().trim() === '') {
				// The server removes unresolved provenance, so clear matching live labels while preserving input values.
				jQuery.each(
					this_generation_data_for_input_selectors,
					function (_, this_generate_data_for_input_selector) {
						ai4seo_update_editor_field_generated_source_message( this_generate_data_for_input_selector, '', true, scope, false );
					}
				);

				console.warn( ai4seo_get_plugin_name() + ': No generated data found for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping filling generated data into inputs.' );
				return;
			}

			// Structured editors share one state write between their visual and proxy selectors.
			const processed_structured_metadata_sources = new Set();

			jQuery.each(
				this_generation_data_for_input_selectors,
				function (_, this_generate_data_for_input_selector) {
					const this_generate_data_for_input_details = ai4seo_generate_data_for_inputs[this_generate_data_for_input_selector];

					if (!this_generate_data_for_input_details) {
						console.error( ai4seo_get_plugin_name() + ': No applicable input details found for selector: ' + this_generate_data_for_input_selector + ' \u2014 skipping filling generated data into input.' );
						return;
					}

					// One source update writes its proxy and any currently mounted visual editor.
					if (['aioseo', 'seopress'].includes( this_generate_data_for_input_details.metadata_source )) {
						if (processed_structured_metadata_sources.has( this_generate_data_for_input_details.metadata_source )) {
							return;
						}

						processed_structured_metadata_sources.add( this_generate_data_for_input_details.metadata_source );
					}

					// Source labels are updated after the input value so exact-match state remains visible without reload.
					ai4seo_fill_text( this_generate_data_for_input_selector, this_generated_data, this_generate_data_for_input_details, scope );
					ai4seo_update_editor_field_generated_source_message(
						this_generate_data_for_input_selector,
						generated_at_output,
						false,
						scope,
						false
					);
				}
			);
		}
	);

	// Synchronize Preview and Editor provenance once after every generated field has its final value.
	ai4seo_refresh_editor_preview_source_messages( scope );
}

// =========================================================================================== \\

// Keep live source hints aligned to the shared field evaluation blocks in Preview and Editor modes.
function ai4seo_update_editor_field_generated_source_message(generate_data_for_input_selector, generated_at_output = '', remove_source_message = false, scope = null, refresh_workspace = true) {
	if (typeof generate_data_for_input_selector !== 'string') {
		return;
	}

	if (!ai4seo_is_internal_metadata_generate_button_selector( generate_data_for_input_selector )
		&& !ai4seo_is_internal_media_generate_button_selector( generate_data_for_input_selector )) {
		return;
	}

	const $generate_data_for_input = ai4seo_normalize_$( generate_data_for_input_selector, scope );

	if (!ai4seo_exists_$( $generate_data_for_input )) {
		return;
	}

	const $form_item = $generate_data_for_input.closest( '.ai4seo-form-item' );

	if (!ai4seo_exists_$( $form_item )) {
		return;
	}

	const input_element = $generate_data_for_input.get( 0 );
	const field_identifier = ai4seo_get_editor_field_identifier_from_input( input_element );
	if (!field_identifier) {
		return;
	}

	const source_message_selector = field_identifier ? '[data-ai4seo-preview-card="' + field_identifier + '"] .ai4seo-editor-field-source-message' : '.ai4seo-editor-field-source-message';
	const $source_message = $form_item.find( source_message_selector ).first();

	if (!ai4seo_exists_$( $source_message )) {
		return;
	}

	// Partial responses clear only a previously rendered source hint because the existing field value must survive.
	if (remove_source_message) {
		const default_source_message = $source_message.attr( 'data-ai4seo-editor-source-message-default' ) || '';
		$source_message
			.text( default_source_message )
			.removeAttr( 'data-ai4seo-editor-source-message-generated data-ai4seo-editor-source-message-base data-ai4seo-editor-source-message-base-value' )
			.removeClass( 'ai4seo-red-message ai4seo-gray-message ai4seo-sub-info ai4seo-orange-message ai4seo-yellow-message' )
			.addClass( 'ai4seo-sub-info' );

		if (!default_source_message) {
			$source_message.addClass( 'ai4seo-display-none' );
		} else {
			$source_message.removeClass( 'ai4seo-display-none' );
		}

		if (refresh_workspace) {
			ai4seo_refresh_editor_preview_source_messages( scope );
		}
		return;
	}

	generated_at_output = String( generated_at_output || '' ).trim();

	// Use the localized plugin name so the live label matches the server-rendered PHP label.
	const plugin_name = ai4seo_get_plugin_name();
	let source_message = wp.i18n.sprintf(
		/* translators: %s: Plugin name. */
		wp.i18n.__( 'This field value was generated by %s.', 'ai-for-seo' ),
		plugin_name
	);

	if (generated_at_output) {
		source_message = wp.i18n.sprintf(
			/* translators: 1: Plugin name. 2: Generated-at date and time. */
			wp.i18n.__( 'This field value was generated by %1$s on %2$s.', 'ai-for-seo' ),
			plugin_name,
			generated_at_output
		);
	}

	$source_message
		.removeClass( 'ai4seo-red-message ai4seo-gray-message ai4seo-sub-info ai4seo-orange-message ai4seo-yellow-message ai4seo-display-none' )
		.addClass( 'ai4seo-sub-info' )
		.addClass( 'ai4seo-gray-message' )
		.attr(
			{
				'data-ai4seo-editor-source-message-generated': 'true',
				'data-ai4seo-editor-source-message-base': source_message,
				'data-ai4seo-editor-source-message-base-value': String( ai4seo_get_input_value( $generate_data_for_input ) || '' ),
			}
		)
		.text( source_message );

	const $workspace = $generate_data_for_input.closest( '.ai4seo-editor-workspace' ).first();

	if (refresh_workspace && ai4seo_exists_$( $workspace )) {
		ai4seo_refresh_editor_preview_source_messages( $workspace );
	}
}

// =========================================================================================== \\

/**
 * Refresh only source-hint copy for all editable preview blocks in one or both editor panels.
 *
 * @param {jQuery|HTMLElement|string} workspace
 * @param {string} [panel=''] Optional view mode panel selector: preview|editor.
 */
function ai4seo_refresh_editor_preview_source_messages(workspace, panel = '') {
	const $workspace = ai4seo_get_editor_workspace_$( workspace );

	if (!ai4seo_exists_$( $workspace )) {
		return;
	}

	const workspace_element = $workspace.get( 0 );
	const panel_scope = panel
		? workspace_element.querySelector( '.ai4seo-editor-mode-panel[data-ai4seo-editor-mode-panel=\"' + panel + '\"]' ) || workspace_element
		: workspace_element;

	panel_scope.querySelectorAll( '.ai4seo-editor-preview-evaluation[data-ai4seo-editor-field-source-identifier]' ).forEach(
		function (evaluation) {
			const card_identifier = String( evaluation.getAttribute( 'data-ai4seo-preview-card' ) || '' );
			if (!card_identifier) {
				return;
			}

			const source = evaluation.querySelector( '.ai4seo-editor-preview-source-message' );
			if (!source) {
				return;
			}

			const source_message = ai4seo_resolve_editor_preview_source_message( workspace_element, card_identifier, panel );

			ai4seo_update_editor_preview_source_message_element(
				source,
				source_message && source_message.text ? source_message : null,
				[
					'ai4seo-editor-field-source-message',
					'ai4seo-sub-info',
					'ai4seo-display-none',
					'ai4seo-gray-message',
					'ai4seo-red-message',
					'ai4seo-green-message',
					'ai4seo-yellow-message',
					'ai4seo-orange-message',
				]
			);
		}
	);

}

// =========================================================================================== \\

// Function to fill the text with the element selected by the selector with the value
// the element can be a text field or a text area or a div.
function ai4seo_fill_text(generate_data_for_input_selector, generated_data, generate_data_for_input_details = {}, scope = null) {
	const $generate_data_for_input = ai4seo_normalize_$( generate_data_for_input_selector, scope );

	if (!ai4seo_exists_$( $generate_data_for_input )) {
		console.warn( ai4seo_get_plugin_name() + ': selector input_selector -> no match in ai4seo_fill_text() \u2014 cannot inject generated text.' );
		return;
	}

	const is_yoast = ai4seo_is_yoast_element( $generate_data_for_input );
	const is_yoast_related = is_yoast || ai4seo_is_yoast_selector( generate_data_for_input_selector );
	const is_aioseo = generate_data_for_input_details.metadata_source === 'aioseo';
	const is_seopress = generate_data_for_input_details.metadata_source === 'seopress';
	const is_slim_seo = generate_data_for_input_details.metadata_source === 'slim-seo';
	const is_squirrly = generate_data_for_input_details.metadata_source === 'squirrly';
	const use_exec_command_workaround = (typeof generate_data_for_input_details.use_exec_command_workaround !== 'undefined' && generate_data_for_input_details.use_exec_command_workaround === true);

	if (is_aioseo) {
		ai4seo_set_aioseo_metadata_content( generate_data_for_input_details.metadata_identifier || '', generated_data, scope );
	} else if (is_seopress) {
		ai4seo_set_seopress_metadata_content( generate_data_for_input_details.metadata_identifier || '', generated_data, scope );
	} else if (is_slim_seo) {
		// Use React's native value path so Slim SEO's controlled state and later post save receive the generation.
		$generate_data_for_input.each(
			function () {
				ai4seo_set_react_controlled_input_value( this, String( generated_data || '' ) );
			}
		);
	} else if (is_squirrly && generate_data_for_input_details.metadata_identifier === 'keywords') {
		ai4seo_set_squirrly_keywords_content( $generate_data_for_input, generated_data );
	} else if (is_yoast_related) {
		ai4seo_set_yoast_input_content( generate_data_for_input_selector, generated_data, generate_data_for_input_details );
	} else if ($generate_data_for_input.is( 'input' )) {
		ai4seo_fill_input_without_exec_command( $generate_data_for_input, generated_data );
	} else if ($generate_data_for_input.is( 'textarea' )) {
		ai4seo_fill_input_without_exec_command( $generate_data_for_input, generated_data );
	}

	// workaround for some inputs to trigger change event properly.
	if (use_exec_command_workaround && !is_yoast_related && !is_aioseo && !is_seopress && !is_slim_seo && !is_squirrly) {
		$generate_data_for_input.focus();
		document.execCommand( 'insertText', false, '.' );
		document.execCommand( 'delete' );
	}

	// refreshes the yoast progress bar if this is a yoast element.
	if (is_yoast) {
		ai4seo_try_refresh_yoast_progress_bar( $generate_data_for_input );
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': Filled generated data "' + generated_data + '" into input selector "' + generate_data_for_input_selector + '".' );

}

// =========================================================================================== \\

function ai4seo_fill_input_without_exec_command($input, value) {
	$input = ai4seo_normalize_$( $input );

	if (!ai4seo_exists_$( $input )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$input\" missing in ai4seo_fill_input_without_exec_command() \u2014 cannot update input.' );
		return false;
	}

	$input.val( value );

	const input_element = $input.get( 0 );

	if (input_element) {
		input_element.value = value;

		['input', 'change', 'keyup'].forEach(
            function (event_name) {
			input_element.dispatchEvent( new Event( event_name, {bubbles: true} ) );
            }
        );
	}

	$input.triggerHandler( 'input' );
	$input.triggerHandler( 'keyup' );
	$input.triggerHandler( 'change' );

	return true;
}

// =========================================================================================== \\

function ai4seo_set_rank_math_serp_data(field_identifier, value) {
	if (typeof rankMath === 'undefined' || typeof rankMath.assessor === 'undefined' || typeof rankMath.assessor.serpData === 'undefined') {
		return;
	}

	switch (field_identifier) {
		case 'meta-title':
			rankMath.assessor.serpData.title = value;
			break;

		case 'meta-description':
			rankMath.assessor.serpData.description = value;
			alert( rankMath.assessor.serpData.description );
			break;

		case 'focus-keyword':
			rankMath.assessor.serpData.focusKeywords = value;
			break;

		case 'facebook-title':
			rankMath.assessor.serpData.facebookTitle = value;
			break;

		case 'facebook-description':
			rankMath.assessor.serpData.facebookDescription = value;
			break;

		case 'twitter-title':
			rankMath.assessor.serpData.twitterTitle = value;
			break;

		case 'twitter-description':
			rankMath.assessor.serpData.twitterDescription = value;
			break;
	}
}

// =========================================================================================== \\

function ai4seo_try_refresh_yoast_progress_bar($input) {
	$input = ai4seo_normalize_$( $input );

	if (!ai4seo_exists_$( $input )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$input\" missing in ai4seo_fill_text_for_yoast() \u2014 cannot inject generated text.' );
		return;
	}

	// Call function to set progress bar to success
	// Define variable for the parent-element with class "yst-replacevar".
	const $yoast_input_container = $input.closest( '.yst-replacevar' );

	// check if this is actually an yoast-element.
	if (!ai4seo_exists_$( $yoast_input_container )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': input is not a Yoast element in ai4seo_fill_text_for_yoast() \u2014 skipping.' );
		return;
	}

	// Define variable for the progress-bar-element.
	const $yoast_progress_bar = $yoast_input_container.next( 'progress' );

	// Make sure that progress-bar-element exists.
	if (!ai4seo_exists_$( $yoast_progress_bar )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$yoast_progress_bar\" missing in ai4seo_refresh_yoast_progress_bar() \u2014 cannot mark completion.' );
		return;
	}

	// Read max-value of progress-bar-element.
	const max_value = $yoast_progress_bar.attr( 'max' );

	// Add success-class to progress-bar-element.
	$yoast_progress_bar.addClass( 'ai4seo-progress-success' );

	// Set progress-bar-value to max-value.
	$yoast_progress_bar.attr( 'value', max_value );
}

// =========================================================================================== \\

function ai4seo_get_yoast_metadata_field_details(metadata_identifier = '') {
	// Keep each field's Redux and backing-input contracts together so both sync directions use the same allowlist.
	const yoast_metadata_field_details = {
		'focus-keyphrase': {
			backing_input_selector: '#yoast_wpseo_focuskw',
			generation_context_read_selector: 'getFocusKeyphrase',
			read_selector: 'getFocusKeyphrase',
			update_action: 'setFocusKeyword',
		},
		'meta-title': {
			backing_input_selector: '#yoast_wpseo_title',
			generation_context_read_selector: 'getSnippetEditorTitleWithTemplate',
			read_selector: 'getSnippetEditorTitle',
			update_action: 'updateData',
			update_data_key: 'title',
		},
		'meta-description': {
			backing_input_selector: '#yoast_wpseo_metadesc',
			generation_context_read_selector: 'getSnippetEditorDescriptionWithTemplate',
			read_selector: 'getSnippetEditorDescription',
			update_action: 'updateData',
			update_data_key: 'description',
		},
		'facebook-title': {
			backing_input_selector: '#yoast_wpseo_opengraph-title',
			generation_context_read_selector: 'getFacebookTitleOrFallback',
			read_selector: 'getFacebookTitle',
			update_action: 'setFacebookPreviewTitle',
			prefer_backing_input_for_live_reads: true,
		},
		'facebook-description': {
			backing_input_selector: '#yoast_wpseo_opengraph-description',
			generation_context_read_selector: 'getFacebookDescriptionOrFallback',
			read_selector: 'getFacebookDescription',
			update_action: 'setFacebookPreviewDescription',
			prefer_backing_input_for_live_reads: true,
		},
		'twitter-title': {
			backing_input_selector: '#yoast_wpseo_twitter-title',
			generation_context_read_selector: 'getTwitterTitleOrFallback',
			read_selector: 'getTwitterTitle',
			update_action: 'setTwitterPreviewTitle',
			prefer_backing_input_for_live_reads: true,
		},
		'twitter-description': {
			backing_input_selector: '#yoast_wpseo_twitter-description',
			generation_context_read_selector: 'getTwitterDescriptionOrFallback',
			read_selector: 'getTwitterDescription',
			update_action: 'setTwitterPreviewDescription',
			prefer_backing_input_for_live_reads: true,
		},
	};

	// An empty identifier supports callers that need to enumerate every synchronized Yoast field.
	return metadata_identifier ? yoast_metadata_field_details[metadata_identifier] || {} : yoast_metadata_field_details;
}

// =========================================================================================== \\

function ai4seo_get_yoast_backing_input_selector(metadata_identifier) {
	// Preserve the existing selector helper while sourcing its value from the shared field registry.
	const yoast_metadata_field_details = ai4seo_get_yoast_metadata_field_details( metadata_identifier );

	return yoast_metadata_field_details.backing_input_selector || '';
}

// =========================================================================================== \\

function ai4seo_get_yoast_editor_store(unavailable_message = '') {
	// Yoast's Redux store is optional, so callers can continue through their backing-input fallbacks.
	if (typeof wp === 'undefined' || typeof wp.data === 'undefined' || typeof wp.data.select !== 'function') {
		return false;
	}

	try {
		return wp.data.select( 'yoast-seo/editor' ) || false;
	} catch (error) {
		// Keep each caller's diagnostic context while centralizing the optional store lookup.
		if (unavailable_message) {
			ai4seo_console_debug( unavailable_message, error );
		}

		return false;
	}
}

// =========================================================================================== \\

function ai4seo_is_yoast_editor_store_loading(yoast_select = false) {
	// A failed readiness probe makes Redux unsafe to read, while backing inputs remain available to callers.
	if (!yoast_select || typeof yoast_select.getSnippetEditorIsLoading !== 'function') {
		return false;
	}

	try {
		return Boolean( yoast_select.getSnippetEditorIsLoading() );
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not read the Yoast editor store loading state.', error );
		return true;
	}
}

// =========================================================================================== \\

function ai4seo_read_yoast_backing_input_value(metadata_field_details = {}) {
	// Backing inputs preserve Yoast's raw explicit values, including an intentional empty social override.
	const yoast_backing_input_selector = metadata_field_details.backing_input_selector || '';

	if (!yoast_backing_input_selector) {
		return {has_value: false, value: undefined};
	}

	const $yoast_backing_input = ai4seo_normalize_$( yoast_backing_input_selector );

	if (!ai4seo_exists_$( $yoast_backing_input )) {
		return {has_value: false, value: undefined};
	}

	const value = ai4seo_get_input_value( $yoast_backing_input.first() );
	const has_value = typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean';

	return {has_value: has_value, value: value};
}

// =========================================================================================== \\

function ai4seo_get_yoast_editor_store_post_id(yoast_select = false) {
	// Resolve the store lazily so callers with an existing selector object avoid another registry lookup.
	if (!yoast_select) {
		yoast_select = ai4seo_get_yoast_editor_store( ai4seo_get_plugin_name() + ': Could not read the Yoast editor store post ID.' );
	}

	if (!yoast_select || typeof yoast_select.getPostId !== 'function') {
		return 0;
	}

	// Treat an unavailable or invalid store identity as unknown while rejecting confirmed mismatches at the caller.
	try {
		const yoast_post_id = parseInt( yoast_select.getPostId(), 10 );

		return yoast_post_id > 0 ? yoast_post_id : 0;
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not read the Yoast editor store post ID.', error );
		return 0;
	}
}

// =========================================================================================== \\

function ai4seo_get_current_page_editor_post_id() {
	// Prefer the localized page-level ID because modal IDs can refer to another entry in list-navigation flows.
	let current_post_id = parseInt( ai4seo_get_localization_parameter( 'ai4seo_current_post_id' ), 10 );

	if (current_post_id) {
		return current_post_id;
	}

	// Classic editor screens expose the same page-level identity through WordPress's hidden post input.
	const $post_id_input = ai4seo_normalize_$( '#post_ID' );

	if (ai4seo_exists_$( $post_id_input )) {
		current_post_id = parseInt( ai4seo_get_input_value( $post_id_input.first() ), 10 );
	}

	return current_post_id || 0;
}

// =========================================================================================== \\

function ai4seo_read_live_yoast_metadata(post_id) {
	// Restrict live reads to the page's editor because list modals can target unrelated post IDs.
	const target_post_id = parseInt( post_id, 10 );
	const current_post_id = ai4seo_get_current_page_editor_post_id();

	// A Yoast editor store belongs only to the entry currently loaded in the page editor.
	if (!target_post_id || !current_post_id || target_post_id !== current_post_id) {
		return {};
	}

	// Use Yoast's Redux state when registered, while retaining its backing inputs as a compatibility fallback.
	const yoast_select = ai4seo_get_yoast_editor_store(
		ai4seo_get_plugin_name() + ': Yoast editor store is not available in ai4seo_read_live_yoast_metadata(). Falling back to backing inputs.'
	);

	// Confirm the store itself is bound to the requested post when Yoast exposes that selector.
	const yoast_post_id = yoast_select ? ai4seo_get_yoast_editor_store_post_id( yoast_select ) : 0;

	if (yoast_post_id && yoast_post_id !== target_post_id) {
		return {};
	}

	// Enumerate the shared registry so reading and writing support the same Yoast fields.
	const yoast_metadata_field_details = ai4seo_get_yoast_metadata_field_details();
	const live_yoast_metadata = {};
	const yoast_store_is_loading = ai4seo_is_yoast_editor_store_loading( yoast_select );

	// Build one identifier-to-value map while preserving raw social overrides ahead of derived fallbacks.
	for (const [metadata_identifier, metadata_field_details] of Object.entries( yoast_metadata_field_details )) {
		const read_selector = metadata_field_details.read_selector || '';
		const prefer_backing_input = metadata_field_details.prefer_backing_input_for_live_reads === true;
		let value;
		let has_value = false;

		// Social Redux selectors can contain derived template fallbacks, so read their raw backing inputs first.
		if (prefer_backing_input) {
			const backing_input_value_details = ai4seo_read_yoast_backing_input_value( metadata_field_details );

			value = backing_input_value_details.value;
			has_value = backing_input_value_details.has_value;
		}

		// Avoid treating Yoast's empty initial Redux state as an intentional unsaved clear while it hydrates.
		if (!has_value && yoast_select && !yoast_store_is_loading && read_selector && typeof yoast_select[read_selector] === 'function') {
			try {
				value = yoast_select[read_selector]();
				has_value = typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean';
			} catch (error) {
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not read live Yoast field "' + metadata_identifier + '".', error );
			}
		}

		// Older Yoast versions and hydrating snippet stores retain their raw value in a backing input.
		if (!has_value && !prefer_backing_input) {
			const backing_input_value_details = ai4seo_read_yoast_backing_input_value( metadata_field_details );

			value = backing_input_value_details.value;
			has_value = backing_input_value_details.has_value;
		}

		if (has_value) {
			live_yoast_metadata[metadata_identifier] = String( value );
		}
	}

	return live_yoast_metadata;
}

// =========================================================================================== \\

function ai4seo_resolve_yoast_generation_metadata_value(metadata_value) {
	// Normalize literals and templates consistently before deciding whether Yoast resolution is required.
	let resolved_value = ai4seo_decode_html_entities( String( metadata_value ?? '' ) ).trim();

	if (!resolved_value || resolved_value.indexOf( '%%' ) === -1) {
		return resolved_value;
	}

	// Resolve raw templates through Yoast's current editor data without allowing integration failures to escape.
	try {
		const replace_vars_plugin = typeof YoastSEO !== 'undefined'
			&& YoastSEO.wp
			&& YoastSEO.wp.replaceVarsPlugin
			? YoastSEO.wp.replaceVarsPlugin
			: false;

		if (!replace_vars_plugin || typeof replace_vars_plugin.replaceVariables !== 'function') {
			return '';
		}

		resolved_value = replace_vars_plugin.replaceVariables( resolved_value );
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not resolve Yoast metadata for generation context.', error );
		return '';
	}

	if (typeof resolved_value !== 'string' && typeof resolved_value !== 'number') {
		return '';
	}

	resolved_value = ai4seo_decode_html_entities( String( resolved_value ) ).trim();

	// An unresolved token is less useful than omitting this optional context field entirely.
	if (/%%[^%\s]+%%/.test( resolved_value )) {
		return '';
	}

	return resolved_value;
}

// =========================================================================================== \\

function ai4seo_read_yoast_generation_metadata(post_id) {
	// Generation context may only come from the editor that owns the requested post.
	const target_post_id = parseInt( post_id, 10 );
	const current_post_id = ai4seo_get_current_page_editor_post_id();

	// Never read a page-level Yoast editor while generation targets a modal for another post.
	if (!target_post_id || !current_post_id || target_post_id !== current_post_id) {
		return {};
	}

	// Start with raw explicit values, then consult Yoast's template-aware selectors when its store is ready.
	const live_yoast_metadata = ai4seo_read_live_yoast_metadata( target_post_id );
	const yoast_select = ai4seo_get_yoast_editor_store(
		ai4seo_get_plugin_name() + ': Yoast editor store is not available for generation context.'
	);

	const yoast_post_id = yoast_select ? ai4seo_get_yoast_editor_store_post_id( yoast_select ) : 0;

	if (yoast_post_id && yoast_post_id !== target_post_id) {
		return {};
	}

	// The shared field registry keeps generation reads aligned with the existing Yoast sync allowlist.
	const yoast_metadata_field_details = ai4seo_get_yoast_metadata_field_details();
	const generation_metadata = {};
	const yoast_store_is_loading = ai4seo_is_yoast_editor_store_loading( yoast_select );

	// Resolve each supported field from explicit state or its generation-specific template/fallback selector.
	for (const [metadata_identifier, metadata_field_details] of Object.entries( yoast_metadata_field_details )) {
		const live_value = live_yoast_metadata[metadata_identifier] ?? '';
		const has_explicit_value = live_value.trim() !== '';
		const generation_context_read_selector = metadata_field_details.generation_context_read_selector || '';
		const prefer_explicit_value = metadata_field_details.prefer_backing_input_for_live_reads === true && has_explicit_value;
		let context_value = live_value;

		// Template and social fallback selectors describe the value Yoast currently intends to render.
		if (!prefer_explicit_value && yoast_select && !yoast_store_is_loading
			&& generation_context_read_selector && typeof yoast_select[generation_context_read_selector] === 'function') {
			try {
				const selector_value = yoast_select[generation_context_read_selector]();

				if (typeof selector_value === 'string' || typeof selector_value === 'number') {
					context_value = selector_value;
				}
			} catch (error) {
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not read Yoast generation context for "' + metadata_identifier + '".', error );
			}
		}

		// Only resolved, human-readable context is eligible for the outgoing generation payload.
		const resolved_value = ai4seo_resolve_yoast_generation_metadata_value( context_value );

		if (resolved_value) {
			generation_metadata[metadata_identifier] = resolved_value;
		}
	}

	return generation_metadata;
}

// =========================================================================================== \\

function ai4seo_update_yoast_store(metadata_identifier, value) {
	// Redux is optional in older Yoast integrations, where the backing-input update remains the fallback.
	if (typeof wp === 'undefined' || typeof wp.data === 'undefined' || typeof wp.data.dispatch !== 'function') {
		return false;
	}

	let yoast_dispatch = false;

	try {
		yoast_dispatch = wp.data.dispatch( 'yoast-seo/editor' );
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Yoast editor store is not available in ai4seo_update_yoast_store().', error );
		return false;
	}

	if (!yoast_dispatch) {
		return false;
	}

	// Resolve the correct Yoast action from the same registry used by live-state reads.
	const yoast_metadata_field_details = ai4seo_get_yoast_metadata_field_details( metadata_identifier );
	const update_action = yoast_metadata_field_details.update_action || '';

	if (!update_action || typeof yoast_dispatch[update_action] !== 'function') {
		return false;
	}

	// Contain integration failures so the caller can continue with the backing-input compatibility path.
	try {
		// Snippet fields share updateData(), while keyphrase and social fields expose direct actions.
		if (yoast_metadata_field_details.update_data_key) {
			yoast_dispatch[update_action]( {[yoast_metadata_field_details.update_data_key]: value} );
		} else {
			yoast_dispatch[update_action]( value );
		}
	} catch (error) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Could not update live Yoast field "' + metadata_identifier + '".', error );
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_set_yoast_input_content(generate_data_for_input_selector, value, generate_data_for_input_details = {}) {
	const metadata_identifier = generate_data_for_input_details.metadata_identifier || '';

	if (!metadata_identifier) {
		console.warn( ai4seo_get_plugin_name() + ': metadata_identifier missing in ai4seo_set_yoast_input_content() \u2014 cannot update Yoast field.' );
		return false;
	}

	let was_updated = ai4seo_update_yoast_store( metadata_identifier, value );
	let yoast_backing_input_selector = '';

	if (generate_data_for_input_selector && generate_data_for_input_selector.indexOf( '#yoast_wpseo_' ) === 0) {
		yoast_backing_input_selector = generate_data_for_input_selector;
	} else {
		yoast_backing_input_selector = ai4seo_get_yoast_backing_input_selector( metadata_identifier );
	}

	if (yoast_backing_input_selector) {
		const $yoast_backing_input = ai4seo_normalize_$( yoast_backing_input_selector );

		if (ai4seo_exists_$( $yoast_backing_input )) {
			was_updated = ai4seo_fill_input_without_exec_command( $yoast_backing_input, value ) || was_updated;
		}
	}

	return was_updated;
}

// =========================================================================================== \\

let ai4seo_is_applying_metadata_to_live_yoast_editor = false;

function ai4seo_apply_metadata_to_live_yoast_editor(post_id, yoast_metadata) {
	// Only the current page editor owns mutable Yoast state; other modal targets need no client refresh.
	const target_post_id = parseInt( post_id, 10 );
	const current_post_id = ai4seo_get_current_page_editor_post_id();

	// There is no open Yoast state to refresh when the saved entry is not the current editor entry.
	if (!target_post_id || !current_post_id || target_post_id !== current_post_id) {
		return true;
	}

	// A missing response map means no Yoast fields were configured for synchronization.
	if (!yoast_metadata || typeof yoast_metadata !== 'object' || Array.isArray( yoast_metadata )) {
		return true;
	}

	// The shared field registry also acts as the response allowlist before any editor state is changed.
	const yoast_metadata_field_details = ai4seo_get_yoast_metadata_field_details();
	const metadata_entries = Object.entries( yoast_metadata ).filter(
		([metadata_identifier]) => Object.prototype.hasOwnProperty.call( yoast_metadata_field_details, metadata_identifier )
	);

	if (metadata_entries.length === 0) {
		return true;
	}

	// Reject a confirmed store mismatch so saved metadata cannot be applied to another open Yoast editor.
	const yoast_editor_store_post_id = ai4seo_get_yoast_editor_store_post_id();

	if (yoast_editor_store_post_id && yoast_editor_store_post_id !== target_post_id) {
		return false;
	}

	// Prevent nested input events from starting another application pass before this one completes.
	if (ai4seo_is_applying_metadata_to_live_yoast_editor) {
		return false;
	}

	ai4seo_is_applying_metadata_to_live_yoast_editor = true;
	let all_fields_were_updated = true;

	// Update Redux and backing inputs through the existing single-field Yoast integration.
	try {
		for (const [metadata_identifier, value] of metadata_entries) {
			const normalized_value = String( value ?? '' );
			const field_was_updated = ai4seo_set_yoast_input_content(
				'',
				normalized_value,
				{metadata_identifier: metadata_identifier}
			);

			all_fields_were_updated = field_was_updated && all_fields_were_updated;
		}
	} finally {
		ai4seo_is_applying_metadata_to_live_yoast_editor = false;
	}

	return all_fields_were_updated;
}

// =========================================================================================== \\

/**
 * Applies server-confirmed AIOSEO metadata to the retained page editor state.
 *
 * @param {number} post_id Saved post ID.
 * @param {Object} aioseo_metadata Canonical AIOSEO values indexed by SOOZ identifier.
 * @return {boolean} Whether no refresh was needed or every returned field was refreshed.
 */
function ai4seo_apply_metadata_to_live_aioseo_editor(post_id, aioseo_metadata) {
	const target_post_id = parseInt( post_id, 10 );

	if (!target_post_id || !aioseo_metadata || typeof aioseo_metadata !== 'object' || Array.isArray( aioseo_metadata )) {
		return true;
	}

	const metadata_entries = Object.entries( aioseo_metadata ).filter(
		([metadata_identifier]) => Object.prototype.hasOwnProperty.call( ai4seo_aioseo_generation_field_details, metadata_identifier )
	);

	if (metadata_entries.length === 0) {
		return true;
	}

	const $aioseo_roots = ai4seo_normalize_$( '.aioseo-post-settings' );

	// A closed or absent AIOSEO editor has no client state that can overwrite the confirmed server values.
	if (!ai4seo_exists_$( $aioseo_roots )) {
		return true;
	}

	const $aioseo_root = $aioseo_roots.first();
	const aioseo_post_editor_store = ai4seo_get_aioseo_post_editor_store( $aioseo_root );

	if (!aioseo_post_editor_store) {
		return false;
	}

	const aioseo_editor_post_id = ai4seo_get_aioseo_post_editor_store_post_id( $aioseo_root );

	// A different open editor is unrelated to the saved modal target and must remain untouched.
	if (aioseo_editor_post_id && aioseo_editor_post_id !== target_post_id) {
		return true;
	}

	if (!aioseo_editor_post_id || ai4seo_is_applying_metadata_to_live_aioseo_editor) {
		return false;
	}

	ai4seo_is_applying_metadata_to_live_aioseo_editor = true;
	let all_fields_were_updated = true;

	try {
		for (const [metadata_identifier, value] of metadata_entries) {
			const normalized_value = String( value ?? '' );
			const field_details = ai4seo_aioseo_generation_field_details[metadata_identifier] || {};
			const post_settings_key = field_details.post_settings_key || '';
			const field_was_updated = ai4seo_set_aioseo_metadata_content(
				metadata_identifier,
				normalized_value,
				$aioseo_root,
				false
			);
			const store_has_requested_value = post_settings_key
				&& String( aioseo_post_editor_store.currentPost[post_settings_key] ?? '' ) === normalized_value;

			all_fields_were_updated = field_was_updated && store_has_requested_value && all_fields_were_updated;
		}
	} finally {
		ai4seo_is_applying_metadata_to_live_aioseo_editor = false;
	}

	return all_fields_were_updated;
}

// =========================================================================================== \\

/**
 * Stores a synchronization warning long enough to survive the save-triggered page reload.
 *
 * @param {string} message Warning text.
 * @return {boolean} Whether the warning was stored.
 */
function ai4seo_store_pending_third_party_sync_warning(message) {
	message = String( message || '' ).trim();

	if (!message) {
		return false;
	}

	try {
		window.sessionStorage.setItem(
			ai4seo_pending_third_party_sync_warning_storage_key,
			JSON.stringify( {message: message, expires_at: Date.now() + ai4seo_pending_third_party_sync_warning_lifetime_ms} )
		);
		return true;
	} catch (error) {
		return false;
	}
}

// =========================================================================================== \\

/**
 * Displays and removes a synchronization warning left by the previous page lifecycle.
 */
function ai4seo_consume_pending_third_party_sync_warning() {
	// Preserve pending warnings until a regular, post-TOS page can display or discard them authoritatively.
	if (ai4seo_pending_third_party_sync_warning_was_consumed
		|| ai4seo_is_top_frame_integration_bootstrap
		|| ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
		return;
	}

	// Restrict storage consumption to contexts reachable after a metadata-editor save reload.
	const synchronization_warning_contexts = [
		'plugin-ui',
		'content-list',
		'post-editor',
		'frontend-metadata-editor'
	];

	if (!synchronization_warning_contexts.some( (asset_context) => ai4seo_has_asset_context( asset_context ) )) {
		return;
	}

	// The first eligible bootstrap owns both displaying and discarding this reload-scoped value.
	ai4seo_pending_third_party_sync_warning_was_consumed = true;

	let stored_warning = '';

	try {
		stored_warning = window.sessionStorage.getItem( ai4seo_pending_third_party_sync_warning_storage_key ) || '';
		window.sessionStorage.removeItem( ai4seo_pending_third_party_sync_warning_storage_key );
	} catch (error) {
		return;
	}

	if (!stored_warning) {
		return;
	}

	try {
		const parsed_warning = JSON.parse( stored_warning );
		const warning_message = parsed_warning && typeof parsed_warning.message === 'string'
			? parsed_warning.message.trim()
			: '';
		const warning_expires_at = parsed_warning ? Number( parsed_warning.expires_at ) : 0;

		if (warning_message && warning_expires_at > Date.now()) {
			ai4seo_show_warning_toast( warning_message );
		}
	} catch (error) {
		// Invalid or obsolete session data is intentionally discarded above.
	}
}

// =========================================================================================== \\

function ai4seo_handle_metadata_editor_save_success(response, should_finish_editor = true) {
	// Normalize the optional metadata response because instruction-only saves intentionally omit it.
	const metadata_editor_response = response && typeof response === 'object' && response.metadata_editor
		? response.metadata_editor
		: {};
	const post_id = parseInt( metadata_editor_response.post_id, 10 );
	const third_party_sync_warning = typeof metadata_editor_response.third_party_sync_warning === 'string'
		? metadata_editor_response.third_party_sync_warning.trim()
		: '';

	// Builders and Save & edit next retain the page, so a failed live refresh remains relevant to the user.
	const page_will_remain_loaded = !should_finish_editor
		|| ai4seo_is_inside_elementor_editor()
		|| ai4seo_is_inside_gutenberg_editor()
		|| ai4seo_is_inside_muffin_builder_editor();

	// Refresh retained third-party stores only; full reloads will hydrate canonical state themselves.
	const yoast_editor_was_refreshed = ai4seo_apply_metadata_to_live_yoast_editor( post_id, metadata_editor_response.yoast_metadata );
	const aioseo_editor_was_refreshed = !page_will_remain_loaded
		|| ai4seo_apply_metadata_to_live_aioseo_editor( post_id, metadata_editor_response.aioseo_metadata );

	// Carry warnings through full reloads so the editor cannot accept changes during a delayed navigation.
	if (should_finish_editor) {
		if (third_party_sync_warning && !page_will_remain_loaded) {
			if (ai4seo_store_pending_third_party_sync_warning( third_party_sync_warning )) {
				ai4seo_safe_page_load();
			} else {
				const warning_duration = Math.min(
					ai4seo_calculate_toast_duration_by_message_length( third_party_sync_warning, 1.5 ),
					5000
				);

				ai4seo_show_warning_toast( third_party_sync_warning, warning_duration );
				ai4seo_show_full_page_loading_screen();
				window.setTimeout( ai4seo_safe_page_load, warning_duration );
			}
		} else {
			ai4seo_safe_page_load();
		}
	}

	// Save & edit next and embedded editors retain the page, so their warning can be shown immediately.
	if (third_party_sync_warning && (!should_finish_editor || page_will_remain_loaded)) {
		ai4seo_show_warning_toast( third_party_sync_warning );
	}

	// Warn only when stale Yoast state can survive long enough to overwrite the server-confirmed metadata.
	if (!yoast_editor_was_refreshed && page_will_remain_loaded) {
		ai4seo_show_warning_toast(
			wp.i18n.__(
				'The metadata was saved, but the open Yoast editor could not be refreshed. Reload this entry before updating it to avoid restoring stale Yoast values.',
				'ai-for-seo'
			)
		);
	}

	// AIOSEO's page-builder and Gutenberg stores also remain able to overwrite canonical metadata.
	if (!aioseo_editor_was_refreshed && page_will_remain_loaded) {
		ai4seo_show_warning_toast(
			wp.i18n.__(
				'The metadata was saved, but the open AIOSEO editor could not be refreshed. Reload this entry before updating it to avoid restoring stale AIOSEO values.',
				'ai-for-seo'
			)
		);
	}
}

// =========================================================================================== \\

// Function to go through the content containers and grab with .text() and put everything into a big string.
function ai4seo_get_post_content() {
	let post_content = '';
	let found_a_content_container = false;

	// Prefer Gutenberg's data store because iframe-based canvases may not expose rendered block nodes yet.
	if (window.wp && wp.data && typeof wp.data.select === 'function') {
		const core_editor_store = wp.data.select( 'core/editor' );

		if (core_editor_store && typeof core_editor_store.getEditedPostContent === 'function') {
			const edited_post_content = core_editor_store.getEditedPostContent();

			if (typeof edited_post_content === 'string' && edited_post_content.trim() !== '') {
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': extracted post content from the Gutenberg editor store:', edited_post_content );
				return edited_post_content;
			}
		}
	}

	const $editor_context = ai4seo_get_editor_context_$();

	if (!ai4seo_exists_$( $editor_context )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$editor_context\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.' );
		return '';
	}

	for (let i = 0; i < ai4seo_content_containers.length; i++) {
		let this_content_containers_child_elements = $editor_context.find( ai4seo_content_containers[i] );

		// Make sure that child-elements could be found.
		if (!ai4seo_exists_$( this_content_containers_child_elements )) {
			// ai4seo_console_debug(ai4seo_get_plugin_name() + ': element \"' + ai4seo_content_containers[i] + '\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.');.
			continue;
		}

		// Loop through child-elements and add their text to the content.
		this_content_containers_child_elements.each(
            function () {
			let this_additional_post_content = '';
			const $this_child = ai4seo_normalize_$( this );

			if (!ai4seo_exists_$( $this_child )) {
				console.error( ai4seo_get_plugin_name() + ': element \"$child\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.' );
				return;
			}

			// add text of the element to the content
			// if it's an input or textarea, use val() instead of text().
			if ($this_child.is( 'input' ) || $this_child.is( 'textarea' )) {
				this_additional_post_content = $this_child.val();
			} else {
				this_additional_post_content = $this_child.text();
			}

			if (!this_additional_post_content || this_additional_post_content.toString().trim() === '') {
				return;
			}

			found_a_content_container = true;

			this_additional_post_content = ai4seo_add_dot_to_string( this_additional_post_content );

			// add additional post content to the post content, adding a space in between, if post content is not empty.
			if (post_content) {
				post_content += ' ';
			}

			post_content += this_additional_post_content;
            }
        );
	}

	if (!found_a_content_container) {
		console.warn( ai4seo_get_plugin_name() + ': No content containers found in ai4seo_get_post_content() \u2014 post content will be empty.' );
		return '';
	}

	// for debugging: look what we got.
	ai4seo_console_debug( ai4seo_get_plugin_name() + ': extracted post content:', post_content );

	return post_content;
}

// =========================================================================================== \\

/**
 * Function to add a dot at the end of the string if not already there
 *
 * @param {string} string
 * @returns {string}
 */
function ai4seo_add_dot_to_string(string) {
	// trim string.
	string = string.trim();

	// Return if the string is not longer than 1 character.
	if (string.length <= 1) {
		return string;
	}

	// Return if the last character is already a dot.
	if (string[string.length - 1] === '.') {
		return string;
	}

	// Add a dot if none of the above conditions were met.
	string += '.';

	return string;
}

// =========================================================================================== \\

// simple debounce utility function.
function ai4seo_debounce(fn, wait) {
	var timeout_id;
	return function () {
		var ctx = this, args = arguments;
		clearTimeout( timeout_id );
		timeout_id = setTimeout(
            function () {
			fn.apply( ctx, args );
            },
            wait
        );
	};
}

// =========================================================================================== \\

// Function to check response
// --- helpers -----------------------------------------------------------------.

// Detects HTML payloads (login redirects, maintenance pages, etc.).
function ai4seo_looks_like_html(s) {
	if (typeof s !== 'string') {
		return false;
	}
	const trimmed = s.trim();
	if (!trimmed || trimmed[0] !== '<') {
		return false;
	}
	return /<(html|body|!DOCTYPE)/i.test( trimmed );
}

// =========================================================================================== \\

// Detects the classic WordPress AJAX "0" failure.
function ai4seo_is_zero_string(s) {
	return (typeof s === 'string') && s.trim() === '0';
}

// =========================================================================================== \\

// Attempts to parse JSON from a clean or noisy string.
function ai4seo_try_parse_json_from_noise(s) {
	if (typeof s !== 'string') {
		return null;
	}
	const trimmed = s.trim();

	// fast path: direct JSON.
	if (trimmed && (trimmed[0] === '{' || trimmed[0] === '[')) {
		try {
			return JSON.parse( trimmed );
		} catch (e) {
		}
	}

	// best-effort: extract first {...} or [...].
	const m = s.match( /(\{[\s\S]*\}|\[[\s\S]*\])/ );
	if (m) {
		try {
			return JSON.parse( m[1] );
		} catch (e) {
		}
	}
	return null;
}

// =========================================================================================== \\

// Normalizes and validates the initial response object or returns false on hard error.
function ai4seo_normalize_initial_response(response) {
	if (!response) {
		return response;
	}

	if (typeof response === 'string') {
		const parsed = ai4seo_try_parse_json_from_noise( response );

		if (parsed !== null) {
			response = parsed;
		}
	}

	if (ai4seo_is_json_string( response )) {
		try {
			response = JSON.parse( response );
		} catch (e) {
		}
	}

	return response;
}

// =========================================================================================== \\

// Parses and returns a safe integer error code.
function ai4seo_sanitize_error_code(v, fallback) {
	const n = parseInt( String( v ).replace( /[^0-9]/g, '' ), 10 );
	return Number.isFinite( n ) ? n : fallback;
}

// =========================================================================================== \\

// Formats a template string that may contain "%s" without mutating the original.
function ai4seo_format_template_message(template_string, substitution) {
	if (typeof template_string !== 'string') {
		return null;
	}
	return template_string.includes( '%s' ) ? template_string.replace( '%s', substitution ) : template_string;
}

// --- main --------------------------------------------------------------------

// Function to check response.
function ai4seo_check_response(response, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true) {
	response = ai4seo_normalize_initial_response( response );

	if (response === false) {
		ai4seo_show_error_toast(
            1104232360,
			wp.i18n.__( 'Bad Request. You may be logged out or a security plugin blocked the request.', 'ai-for-seo' )
		);

		console.error( ai4seo_get_plugin_name() + ': Empty AJAX response' );
		return false;
	}

	// must have success flag.
	if (typeof response !== 'object') {
		if (typeof response === 'string') {
			if (ai4seo_looks_like_html( response )) {
				ai4seo_show_error_toast(
					1104232362,
					wp.i18n.__( 'Bad Request. You may be logged out or a security plugin blocked the request.', 'ai-for-seo' )
				);

				console.error( ai4seo_get_plugin_name() + ': AJAX response looks like HTML', response );
				return false;
			}

			if (ai4seo_is_zero_string( response )) {
				ai4seo_show_error_toast(
					1104232363,
					wp.i18n.__( 'Bad Request. Nonce, capability or security check failed. Please reload the page and try again.', 'ai-for-seo' )
				);

				console.error( ai4seo_get_plugin_name() + ': AJAX response is zero string', response );
				return false;
			}
		}

		ai4seo_show_error_toast(
			5214241025,
			wp.i18n.__( 'Bad Request.', 'ai-for-seo' )
		);

		console.error( ai4seo_get_plugin_name() + ': Bad AJAX response', response );

		return false;
	}

	// error field set but no success field -> set response.success.
	if (typeof response.success === 'undefined' && typeof response.error !== 'undefined') {
		response.success = false;
	}

	// success path.
	if (typeof response.success !== 'undefined' && response.success) {
		return true;
	}

	// not successful and response.message or response.error_message set -> normalize to .error.
	if (typeof response.message !== 'undefined' && typeof response.error === 'undefined') {
		response.error = response.message;
	} else if (typeof response.error_message !== 'undefined' && typeof response.error === 'undefined') {
		response.error = response.error_message;
	}

	// not successful and response.error_code set -> normalize to .code.
	if (typeof response.error_code !== 'undefined' && typeof response.code === 'undefined') {
		response.code = response.error_code;
	}

	// must have success or error field.
	if (typeof response.success === 'undefined' && typeof response.error === 'undefined') {
		ai4seo_show_error_toast(
			1104232361,
			wp.i18n.__( 'Bad Request.', 'ai-for-seo' )
		);

		console.error( ai4seo_get_plugin_name() + ': Bad AJAX response. No "success" or "error" field present', response );
		return false;
	}

	// error path.
	if (typeof response.data !== 'undefined') {
		response = response.data;
	}

	if (typeof response !== 'object' || response === null) {
		response = {};
	}

	if (typeof response.code === 'undefined') {
		response.code = 5617101125;
	}

	response.code = ai4seo_sanitize_error_code( response.code, 5717101125 );
	response.headline = response.headline || '';

	if (typeof response.add_contact_us_link !== 'undefined') {
		add_contact_us_link = response.add_contact_us_link;
	}

	if (typeof response.error === 'undefined') {
		response.error = wp.i18n.__( 'An unknown error occurred.', 'ai-for-seo' );
	}

	let modal_settings = {};
	if (response.headline) {
		modal_settings.headline = response.headline;
	}

	// print the error.
	console.error( ai4seo_get_plugin_name() + ': API Error #' + response.code + ': ' + response.error );

	// additional_error_list takes priority.
	if (additional_error_list[response.code]) {
		const formated_template_message = ai4seo_format_template_message( additional_error_list[response.code], response.error );
		ai4seo_open_generic_error_notification_modal( response.code, formated_template_message || additional_error_list[response.code], '', modal_settings );
		return false;
	}

	// known RobHub API error codes.
	if (Array.isArray( ai4seo_robhub_api_response_error_codes ) &&
		ai4seo_robhub_api_response_error_codes.includes( response.code )) {
		ai4seo_handle_common_robhub_api_response_errors( response.error, response.code, modal_settings );
		return false;
	}

	// plugin's error-code map.
	if (ai4seo_error_codes_and_messages &&
		Object.prototype.hasOwnProperty.call( ai4seo_error_codes_and_messages, response.code )) {
		const base = ai4seo_error_codes_and_messages[response.code];
		const msg2 = ai4seo_format_template_message( base, response.error );
		ai4seo_open_generic_error_notification_modal( response.code, msg2 || base, '', modal_settings );
		return false;
	}

	if (show_generic_error) {
		let error_message = (response.error ? response.error : '');
		if (add_contact_us_link) {
			if (error_message) {
				error_message += '<br><br>';
			}
			error_message += wp.i18n.sprintf(
				wp.i18n.__( "Please check your settings or <a href='%s' target='_blank'>contact us</a>.", 'ai-for-seo' ),
				ai4seo_official_contact_url
			);
		}
		ai4seo_open_generic_error_notification_modal( response.code, error_message, '', modal_settings );
	}

	return false;
}

// =========================================================================================== \\

function ai4seo_handle_common_robhub_api_response_errors(error_message, error_code, modal_settings = {}) {
	// Check if ai4seo_robhub_api_response_error_codes_and_messages-array contains key that contains the error-message.
	for (const error_code in ai4seo_robhub_api_response_error_codes_and_messages) {
		if (error_message.includes( error_code )) {
			// Display error-message.
			ai4seo_open_generic_error_notification_modal( error_code, ai4seo_robhub_api_response_error_codes_and_messages[error_code] );
			return;
		}
	}

	// Display generic error-message if no error-message was found.
	ai4seo_open_generic_error_notification_modal( error_code, error_message, '', modal_settings );
}

// =========================================================================================== \\

function ai4seo_copy_to_clipboard(to_copy_text, $copied_to_clipboard) {
	// Method A: Using the Clipboard API if available.
	if (typeof navigator !== 'undefined' && typeof navigator.clipboard !== 'undefined') {
		// Use the Clipboard API to copy the text.
		navigator.clipboard.writeText( to_copy_text ).then(
            function () {
			if ($copied_to_clipboard) {
				ai4seo_show_element_for_x_time( $copied_to_clipboard )
			}
            },
            function (err) {
			console.warn( ai4seo_get_plugin_name() + ': Could not copy to clipboard' );
            }
        );
	} else {
		// Method B: Fallback to using a textarea element.
		const $temporary_text_area = ai4seo_normalize_$( '<textarea></textarea>' );
		const $body = ai4seo_normalize_$( 'body', document );

		if (!ai4seo_exists_$( $temporary_text_area ) || !ai4seo_exists_$( $body )) {
			console.warn( ai4seo_get_plugin_name() + ': Could not prepare textarea fallback in ai4seo_copy_to_clipboard().' );
			return;
		}

		$temporary_text_area.val( to_copy_text );
		$body.append( $temporary_text_area );

		const temporary_text_area_element = $temporary_text_area.get( 0 );

		if (!temporary_text_area_element) {
			console.warn( ai4seo_get_plugin_name() + ': Temporary textarea element missing in ai4seo_copy_to_clipboard() fallback.' );
			$temporary_text_area.remove();
			return;
		}

		temporary_text_area_element.select();

		try {
			document.execCommand( 'copy' );
			ai4seo_show_element_for_x_time( $copied_to_clipboard )
		} catch (err) {
			console.warn( ai4seo_get_plugin_name() + ': Could not copy to clipboard' );
		}

		$temporary_text_area.remove();
	}
}

// =========================================================================================== \\

function ai4seo_show_element_for_x_time($target, milliseconds = 3000) {
	$target = ai4seo_normalize_$( $target );

	if (!ai4seo_exists_$( $target )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$target\" missing in ai4seo_show_element_for_x_time() \u2014 nothing to display temporarily.' );
		return;
	}

	const was_hidden_by_class = $target.hasClass( 'ai4seo-display-none' );

	// Use the temporary class so copy feedback overrides hidden CSS without keeping inline display styles.
	$target.removeClass( 'ai4seo-display-none' ).addClass( 'ai4seo-temporary-visible' );

	// Return the tooltip to its previous visibility source after the feedback window.
	setTimeout(
		function () {
			$target.removeClass( 'ai4seo-temporary-visible' );

			// Restore the original class-hidden state for tooltips that should not stay visible after feedback.
			if (was_hidden_by_class) {
				$target.addClass( 'ai4seo-display-none' );
			}
		},
		milliseconds
	);
}


// ___________________________________________________________________________________________ \\
// === PLUGIN'S PAGES ======================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_active_subpage() {
	return ai4seo_get_localization_parameter( 'ai4seo_active_subpage' );
}

// =========================================================================================== \\

function ai4seo_get_active_post_type_subpage() {
	return ai4seo_get_localization_parameter( 'ai4seo_active_post_type_subpage' );
}

// =========================================================================================== \\

function ai4seo_get_active_meta_tags() {
	return ai4seo_get_localization_parameter( 'ai4seo_active_meta_tags' );
}

// =========================================================================================== \\

function ai4seo_get_active_attachment_attributes() {
	return ai4seo_get_localization_parameter( 'ai4seo_active_attachment_attributes' );
}

// =========================================================================================== \\

function ai4seo_is_truthy_value(value) {
	// PHP localization can represent enabled flags as booleans, integers, or their string equivalents.
	return value === true
		|| value === 1
		|| value === '1'
		|| value === 'true';
}

// =========================================================================================== \\

function ai4seo_is_truthy_localization_parameter(parameter_name) {
	return ai4seo_is_truthy_value( ai4seo_get_localization_parameter( parameter_name ) );
}

// =========================================================================================== \\

function ai4seo_are_external_metadata_generate_buttons_enabled() {
	return ai4seo_is_truthy_localization_parameter( 'ai4seo_enable_external_metadata_generate_buttons' );
}

// =========================================================================================== \\

function ai4seo_are_external_media_generate_buttons_enabled() {
	return ai4seo_is_truthy_localization_parameter( 'ai4seo_enable_external_media_generate_buttons' );
}

// =========================================================================================== \\

function ai4seo_is_internal_metadata_generate_button_selector(selector) {
	return typeof selector === 'string'
		&& selector.indexOf( '#ai4seo_metadata_' ) === 0;
}

// =========================================================================================== \\

function ai4seo_is_internal_media_generate_button_selector(selector) {
	return typeof selector === 'string'
		&& selector.indexOf( '#ai4seo_attachment_attribute_' ) === 0;
}

// =========================================================================================== \\

function ai4seo_should_render_generate_button_for_selector(selector, generate_button_details = {}) {
	if (ai4seo_is_internal_metadata_generate_button_selector( selector )
		|| ai4seo_is_internal_media_generate_button_selector( selector )) {
		return true;
	}

	const processing_context = generate_button_details['processing-context'] || '';

	if (processing_context === 'metadata') {
		return ai4seo_are_external_metadata_generate_buttons_enabled();
	}

	if (processing_context === 'attachment-attributes') {
		return ai4seo_are_external_media_generate_buttons_enabled();
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_should_render_generate_all_button_for_selector(processing_context, selector) {
	if (selector === '#ai4seo-generate-all-metadata-button-hook'
		|| selector === '#ai4seo-generate-all-attachment-attributes-button-hook') {
		return true;
	}

	if (processing_context === 'metadata') {
		return ai4seo_are_external_metadata_generate_buttons_enabled();
	}

	if (processing_context === 'attachment-attributes') {
		return ai4seo_are_external_media_generate_buttons_enabled();
	}

	return true;
}


// ___________________________________________________________________________________________ \\
// === DASHBOARD ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_remove_notification_count() {
	// only on dashboard page.
	if (ai4seo_get_active_subpage() !== 'dashboard') {
		return;
	}

	const $update_plugins_badge = ai4seo_normalize_$( '#toplevel_page_ai-for-seo .update-plugins' );

	if (!ai4seo_exists_$( $update_plugins_badge )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': element \"$update_plugins_badge\" missing in ai4seo_remove_notification_count() \u2014 cannot clear notification count.');.
		return;
	}

	$update_plugins_badge.remove();
}

// =========================================================================================== \\

function ai4seo_refresh_dashboard_statistics($button) {
	$button = ai4seo_normalize_$( $button );

	if (!ai4seo_exists_$( $button )) {
		console.error( ai4seo_get_plugin_name() + ': element "$button" missing in ai4seo_refresh_dashboard_statistics() — cannot refresh statistics.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $button );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Statistics are refreshing now...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_refresh_dashboard_statistics' )
		.then(
            response => {
			ai4seo_show_success_toast( wp.i18n.__( 'Reloading page...', 'ai-for-seo' ) );
            }
        )
		.catch(
            (error) => {
			ai4seo_show_generic_error_toast( 712181225 );
			ai4seo_remove_loading_html_from_element( $button );
			ai4seo_unlock_and_enable_lockable_input_fields();
			throw error;
            }
        )
		.finally(
            () => {
			setTimeout( () => ai4seo_safe_page_load( 'dashboard' ), 1000 );
            }
        );
}

// =========================================================================================== \\

function ai4seo_refresh_robhub_account($potential_button, options = {}) {
	$potential_button = ai4seo_normalize_$( $potential_button );

	const settings = Object.assign(
        {
		check_for_purchase: false,
		attempt: 1,
		max_attempts: 5,
		initial_delay_seconds: 5,
		reuse_loading: false,
        },
        options || {}
    );

	if (settings.attempt === 1 && !settings.reuse_loading) {
		if (ai4seo_exists_$( $potential_button )) {
			ai4seo_add_loading_html_to_element( $potential_button );
		}

		ai4seo_lock_and_disable_lockable_input_fields();
	}

	const payload = {};

	if (settings.check_for_purchase) {
		payload.check_for_purchase = 1;
	}

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Syncing your account now...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_refresh_robhub_account', payload )
		.then(
            (data) => {
			const is_purchase_ready = settings.check_for_purchase ? Boolean( data && data.is_purchase_ready ) : true;
			if (settings.check_for_purchase && !is_purchase_ready) {
				if (settings.attempt < settings.max_attempts) {
					const next_delay_seconds = settings.initial_delay_seconds * settings.attempt;

					ai4seo_show_info_toast( wp.i18n.__( 'Waiting for your purchase to complete. Checking again shortly...', 'ai-for-seo' ), next_delay_seconds * 1000 + 1000 );

					setTimeout(
                        () => {
						ai4seo_refresh_robhub_account(
                            $potential_button,
                            Object.assign(
                                {},
                                settings,
                                {
                                attempt: settings.attempt + 1,
                                reuse_loading: true,
                                }
                            )
                        );
                        },
                        next_delay_seconds * 1000
                    );
					return;
				}

				ai4seo_show_warning_toast( wp.i18n.__( 'Your purchase is still processing. Please try refreshing again later (Dashboard > Credits > Refresh) or contact support if the issue persists.', 'ai-for-seo' ) );

				if (ai4seo_exists_$( $potential_button )) {
					ai4seo_remove_loading_html_from_element( $potential_button );
				}

				ai4seo_unlock_and_enable_lockable_input_fields();

				setTimeout(
                    () => {
					ai4seo_safe_page_load( 'dashboard' );
                    },
                    4000
                );
				return;
			}
			ai4seo_show_success_toast( wp.i18n.__( 'Account synced successfully. Reloading page...', 'ai-for-seo' ) );
			setTimeout( () => ai4seo_safe_page_load( 'dashboard' ), 1000 );
            }
        )
		.catch(
            (error) => {
			ai4seo_show_generic_error_toast( 812181225 );
			if (ai4seo_exists_$( $potential_button )) {
				ai4seo_remove_loading_html_from_element( $potential_button );
			}
			ai4seo_unlock_and_enable_lockable_input_fields();
			throw error;
            }
        );
}

// =========================================================================================== \\

function ai4seo_start_bulk_generation($button) {
	$button = ai4seo_normalize_$( $button );

	if (!ai4seo_exists_$( $button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$button\" missing in ai4seo_start_bulk_generation() \u2014 cannot start bulk generation.' );
		return;
	}

	ai4seo_save_anything(
        $button,
        ai4seo_validate_bulk_generation_inputs,
        function () {
		ai4seo_safe_page_load();
        },
        function () {
		ai4seo_safe_page_load();
        }
    );
}

// =========================================================================================== \\

// ___________________________________________________________________________________________ \\
// === SEO AUTOPILOT MODAL CONTROLS ========================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Handle datetime picker visibility and label updates inside one SEO Autopilot root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_handle_bulk_generation_new_or_existing_filter_change(scope = null) {
	// Resolve every related field within the same modal instance before binding its update closure.
	const $filter_select = ai4seo_get_elements_in_scope_$( '#ai4seo_bulk_generation_new_or_existing_filter', scope ).first();
	const $datetime_picker_container = ai4seo_get_elements_in_scope_$( '.ai4seo-datetime-picker-container', scope ).first();
	const $datetime_picker_label = ai4seo_get_elements_in_scope_$( '.ai4seo-datetime-picker-label', scope ).first();
	const $datetime_picker_input = ai4seo_get_elements_in_scope_$(
		'#ai4seo_bulk_generation_new_or_existing_filter_reference_time',
		scope
	).first();

	if (!ai4seo_exists_$( $filter_select )) {
		console.warn( ai4seo_get_plugin_name() + ': selector \"#ai4seo_bulk_generation_new_or_existing_filter\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot configure bulk generation scope.' );
		return;
	}

	if (!ai4seo_exists_$( $datetime_picker_container )) {
		console.warn( ai4seo_get_plugin_name() + ': selector \".ai4seo-datetime-picker-container\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot display filter options.' );
		return;
	}

	if (!ai4seo_exists_$( $datetime_picker_label )) {
		console.warn( ai4seo_get_plugin_name() + ': selector \".ai4seo-datetime-picker-label\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot update filter label.' );
		return;
	}

	if (!ai4seo_exists_$( $datetime_picker_input )) {
		console.warn( ai4seo_get_plugin_name() + ': selector \"#ai4seo_bulk_generation_new_or_existing_filter_reference_time\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot capture schedule.' );
		return;
	}

	// Function to update datetime picker visibility and label.
	function ai4seo_on_bulk_generation_datetime_picker_update() {
		const selected_value = $filter_select.val();

		if (selected_value === 'new') {
			$datetime_picker_container.removeClass( 'ai4seo-display-none' );

			// 'New entries since:'
			$datetime_picker_label.text( wp.i18n.__( 'New entries since:', 'ai-for-seo' ) );

			// Populate with current timestamp if empty.
			if (!$datetime_picker_input.val()) {
				ai4seo_populate_datetime_picker_with_current_timestamp( $datetime_picker_input );
			}
		} else if (selected_value === 'existing') {
			$datetime_picker_container.removeClass( 'ai4seo-display-none' );

			// 'Old entries before:'
			$datetime_picker_label.text( wp.i18n.__( 'Old entries before:', 'ai-for-seo' ) );

			// Populate with current timestamp if empty.
			if (!$datetime_picker_input.val()) {
				ai4seo_populate_datetime_picker_with_current_timestamp( $datetime_picker_input );
			}
		} else {
			$datetime_picker_container.addClass( 'ai4seo-display-none' );
		}
	}

	// Initial update.
	ai4seo_on_bulk_generation_datetime_picker_update();

	// Update on change.
	$filter_select.off( 'change.ai4seo-datepicker' );
	$filter_select.on( 'change.ai4seo-datepicker', ai4seo_on_bulk_generation_datetime_picker_update );
}

// =========================================================================================== \\

/**
 * Toggle auto-queue-dependent settings inside one SEO Autopilot root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_handle_bulk_generation_auto_queue_entries_change(scope = null) {
	// Keep the controller and dependent groups inside the same modal instance.
	const $auto_queue_entries_select = ai4seo_get_elements_in_scope_$( '#ai4seo_bulk_generation_auto_queue_entries', scope ).first();
	const $dependent_settings = ai4seo_get_elements_in_scope_$( '.ai4seo-bulk-generation-auto-queue-dependent-settings', scope );
	const $manual_queue_note = ai4seo_get_elements_in_scope_$( '.ai4seo-bulk-generation-manual-queue-note', scope );

	if (!ai4seo_exists_$( $auto_queue_entries_select )) {
		console.warn( ai4seo_get_plugin_name() + ': selector \"#ai4seo_bulk_generation_auto_queue_entries\" missing in ai4seo_handle_bulk_generation_auto_queue_entries_change() \u2014 cannot configure auto queue entries.' );
		return;
	}

	function ai4seo_on_bulk_generation_auto_queue_entries_update() {
		const is_auto_queue_enabled = $auto_queue_entries_select.val() === 'true';

		// Show the discovery filter only when cron excavation can use it.
		if (ai4seo_exists_$( $dependent_settings )) {
			$dependent_settings.toggleClass( 'ai4seo-display-none', !is_auto_queue_enabled );
		}

		// Show the manual queue note only when users must queue entries themselves.
		if (ai4seo_exists_$( $manual_queue_note )) {
			$manual_queue_note.toggleClass( 'ai4seo-display-none', is_auto_queue_enabled );
		}
	}

	// Sync the initial modal state, then keep it updated while the modal is open.
	ai4seo_on_bulk_generation_auto_queue_entries_update();

	$auto_queue_entries_select.off( 'change.ai4seo-auto-queue-entries' );
	$auto_queue_entries_select.on( 'change.ai4seo-auto-queue-entries', ai4seo_on_bulk_generation_auto_queue_entries_update );
}

// =========================================================================================== \\

// Populate datetime picker with current timestamp converted to datetime-local format.
function ai4seo_populate_datetime_picker_with_current_timestamp($datetime_picker_input) {
	$datetime_picker_input = ai4seo_normalize_$( $datetime_picker_input );

	if (!ai4seo_exists_$( $datetime_picker_input )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$datetime_picker\" missing in ai4seo_populate_datetime_picker_with_current_timestamp() \u2014 reference time cannot preset.' );
		return;
	}

	// Check if there's already a stored timestamp from the server.
	let timestamp = $datetime_picker_input.data( 'stored-timestamp' );

	// If no stored timestamp, use current time.
	if (!timestamp) {
		timestamp = Math.floor( Date.now() / 1000 );
	}

	// Convert timestamp to datetime-local format.
	const date = new Date( timestamp * 1000 );
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	const hours = String( date.getHours() ).padStart( 2, '0' );
	const minutes = String( date.getMinutes() ).padStart( 2, '0' );

	const datetime_local = `${year}-${month}-${day}T${hours}:${minutes}`;

	// Set the datetime-local value.
	$datetime_picker_input.val( datetime_local );
}

// =========================================================================================== \\

function ai4seo_validate_bulk_generation_inputs() {
	return true;
}

// =========================================================================================== \\

// Refresh the queue counter and clear-button state without reloading the modal.
function ai4seo_update_bulk_generation_queue_status(queue_count) {
	queue_count = parseInt( queue_count, 10 );

	if (!Number.isFinite( queue_count ) || queue_count < 0) {
		queue_count = 0;
	}

	const $queue_count = ai4seo_normalize_$( '.ai4seo-bulk-generation-queue-count' );
	const $queue_count_label = ai4seo_normalize_$( '.ai4seo-bulk-generation-queue-count-label' );
	const $clear_queue_button_container = ai4seo_normalize_$( '.ai4seo-clear-bulk-generation-queue-button-container' );
	const $clear_queue_button = ai4seo_normalize_$( '.ai4seo-clear-bulk-generation-queue-button' );

	if (ai4seo_exists_$( $queue_count )) {
		$queue_count.text( queue_count.toLocaleString() );
	}

	if (ai4seo_exists_$( $queue_count_label )) {
		const queueCountLabel = wp.i18n._n(
			'entry',
			'entries',
			queue_count,
			'ai-for-seo'
		);

		$queue_count_label.text( queueCountLabel );
	}

	// The button is only useful while there are Pending entries to remove.
	if (ai4seo_exists_$( $clear_queue_button_container )) {
		if (queue_count > 0) {
			$clear_queue_button_container.removeClass( 'ai4seo-display-none' );
		} else {
			$clear_queue_button_container.addClass( 'ai4seo-display-none' );
		}
	}

	if (ai4seo_exists_$( $clear_queue_button )) {
		if (queue_count > 0) {
			$clear_queue_button.show();
		} else {
			$clear_queue_button.hide();
		}
	}
}

// =========================================================================================== \\

// Clear all Pending SEO Autopilot entries through AJAX and update the visible counter.
function ai4seo_clear_bulk_generation_queue($button) {
	$button = ai4seo_normalize_$( $button );

	if (!ai4seo_exists_$( $button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$button\" missing in ai4seo_clear_bulk_generation_queue() \u2014 cannot clear queue.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $button );
	ai4seo_lock_and_disable_lockable_input_fields();

	ai4seo_show_loading_toast( wp.i18n.__( 'Clearing queue...', 'ai-for-seo' ) );

	// The PHP endpoint only clears Pending metadata/media-attribute queues.
	ai4seo_perform_ajax_call( 'ai4seo_clear_bulk_generation_queue' )
		.then(
            response => {
			const queue_count = response && typeof response.queue_count !== 'undefined' ? response.queue_count : 0;
			ai4seo_update_bulk_generation_queue_status( queue_count );
			ai4seo_show_success_toast( wp.i18n.__( 'Queue cleared.', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1010062602, error );
            }
        )
		.finally(
            () => {
			ai4seo_remove_loading_html_from_element( $button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );
}

// =========================================================================================== \\

function ai4seo_stop_bulk_generation($submit) {
	$submit = ai4seo_normalize_$( $submit );

	if (!ai4seo_exists_$( $submit )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_stop_bulk_generation() \u2014 cannot stop bulk generation.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Stopping the SEO Autopilot now...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_stop_bulk_generation' )
		.then(
            response => {
			ai4seo_show_success_toast( wp.i18n.__( 'SEO Autopilot stopped successfully. Reloading page...', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 912181225, error );
            }
        )
		.finally(
            () => {
			setTimeout( () => ai4seo_safe_page_load(), 1000 );
            }
        );
}

// =========================================================================================== \\

function ai4seo_retry_all_failed_attachment_attributes($submit) {
	$submit = ai4seo_normalize_$( $submit );

	if (!ai4seo_exists_$( $submit )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_retry_all_failed_attachment_attributes() \u2014 cannot retry failed attachment attributes.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Retrying all failed attachment attributes now...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_retry_all_failed_attachment_attributes' )
		.then(
            response => { /* nothing */
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1012181225, error );
            }
        )
		.finally(
            () => {
			ai4seo_safe_page_load();
            }
        );
}

// =========================================================================================== \\

function ai4seo_retry_all_failed_metadata($submit, post_type) {
	$submit = ai4seo_normalize_$( $submit );

	if (!ai4seo_exists_$( $submit )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_retry_all_failed_metadata() \u2014 cannot retry failed metadata.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Retrying all failed metadata now...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_retry_all_failed_metadata', {post_type: post_type} )
		.then(
            response => { /* nothing */
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1112181225, error );
            }
        )
		.finally(
            () => {
			ai4seo_safe_page_load();
            }
        );
}


// ___________________________________________________________________________________________ \\
// === GENERATE THROUGH AI - BUTTONS ========================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Checks whether an existing generation button must remain untouched during an active request.
 *
 * @param {*} $generate_button Possible generation button.
 * @return {boolean} Whether the button is locked or currently pressed.
 */
function ai4seo_is_generate_button_busy($generate_button) {
	$generate_button = ai4seo_normalize_$( $generate_button );

	return ai4seo_exists_$( $generate_button )
		&& ($generate_button.hasClass( 'ai4seo-temporary-locked' ) || $generate_button.data( 'currently-pressed' ));
}

// =========================================================================================== \\

/**
 * Rebuilds the Generate All control for one editor while preserving any in-flight request.
 *
 * @param {string} processing_context Generation context.
 * @param {*} generate_all_button_container One editor's Generate All host.
 */
function ai4seo_init_generate_all_button_container(processing_context, generate_all_button_container) {
	const $generate_all_button_container = ai4seo_normalize_$( generate_all_button_container );

	if (!ai4seo_exists_$( $generate_all_button_container )) {
		return;
	}

	const $already_in_place_generate_all_buttons_wrapper = $generate_all_button_container.find( '.ai4seo-generate-all-button-wrapper' );

	if (ai4seo_exists_$( $already_in_place_generate_all_buttons_wrapper )) {
		const $possible_generate_button = $already_in_place_generate_all_buttons_wrapper.find( '.ai4seo-generate-all-button' );

		if (ai4seo_is_generate_button_busy( $possible_generate_button )) {
			return;
		}

		$already_in_place_generate_all_buttons_wrapper.remove();
	}

	ai4seo_add_generate_all_buttons( processing_context, $generate_all_button_container );
}

// =========================================================================================== \\

/**
 * Initializes Generate All controls inside the requested editor scope.
 *
 * @param {jQuery|HTMLElement|null} scope Optional editor instance.
 * @param {string|null} processing_context Optional metadata or attachment-attributes filter.
 */
function ai4seo_init_generate_all_buttons(scope = null, processing_context = null) {
	// Check if current page is attachment-page
	// workaround: we need to check if the attachment mime type is supported.
	if (ai4seo_is_attachment_post_type()) {
		// Stop script if the current attachment doesn't contain supported mime type.
		if (!ai4seo_is_attachment_mime_type_supported()) {
			return;
		}
	}

	// Resolve active fields once for every generate-all host initialized in this pass.
	const active_meta_tags = ai4seo_get_active_meta_tags();
	const active_attachment_attributes = ai4seo_get_active_attachment_attributes();
	const num_active_meta_tags = active_meta_tags ? Object.keys( active_meta_tags ).length : 0;
	const num_active_attachment_attributes = active_attachment_attributes
		? Object.keys( active_attachment_attributes ).length
		: 0;

	// Loop through selectors and add button to each selector.
	for (const this_processing_context in ai4seo_generate_all_button_selectors) {
		// Click-driven integration passes limit Generate All controls to the affected field family.
		if (processing_context !== null && this_processing_context !== processing_context) {
			continue;
		}

		ai4seo_generate_all_button_selectors[this_processing_context].forEach(
			function (this_generate_all_button_selector) {
				if (!ai4seo_should_render_generate_all_button_for_selector( this_processing_context, this_generate_all_button_selector )) {
					return;
				}

				// Skip contexts with no active fields.
				if (this_processing_context === 'metadata' && num_active_meta_tags === 0) {
					ai4seo_console_debug( ai4seo_get_plugin_name() + ': no active meta tags found in ai4seo_init_generate_all_button() \u2014 skipped.' );
					return;
				}

				if (this_processing_context === 'attachment-attributes' && num_active_attachment_attributes === 0) {
					ai4seo_console_debug( ai4seo_get_plugin_name() + ': no active attachment attributes found in ai4seo_init_generate_all_button() \u2014 skipped.' );
					return;
				}

				const $generate_all_button_containers = ai4seo_get_elements_in_scope_$( this_generate_all_button_selector, scope );

				if (!ai4seo_exists_$( $generate_all_button_containers )) {
					return;
				}

				// Treat every matching editor as an independent host instead of cloning one shared button state.
				$generate_all_button_containers.each(
					function () {
						ai4seo_init_generate_all_button_container( this_processing_context, this );
					}
				);
			}
		);
	}
}

// =========================================================================================== \\

function ai4seo_add_generate_all_buttons(processing_context, $generate_all_buttons_container) {
	// Define variable for element.
	$generate_all_buttons_container = ai4seo_normalize_$( $generate_all_buttons_container );

	if (!ai4seo_exists_$( $generate_all_buttons_container )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $generate_all_button missing in ai4seo_add_generate_all_button() \u2014 skipping generate-all hook.' );
		return;
	}

	const $generate_all_button_wrapper = $generate_all_buttons_container.find( '.ai4seo-generate-all-button-wrapper' );

	// check if this hook element already has a generate all button (ai4seo-generate-all-button-wrapper class).
	if (ai4seo_exists_$( $generate_all_button_wrapper )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': generate all button wrapper already exists in ai4seo_add_generate_all_button() \u2014 skipping generate-all button.' );
		return;
	}

	// get the closest container that holds the generation fields and hidden editor ids for this button.
	const $closest_container = ai4seo_get_closest_container_$( $generate_all_buttons_container );

	// make sure we have a post_id from the same modal/container as the generate-all hook.
	const post_id = ai4seo_get_post_id( processing_context, $closest_container );

	if (!post_id || isNaN( post_id )) {
		return;
	}

	let button_html = '';
	let previous_num_normalized_generation_fields = -1;
	let try_read_page_content_via_js = 'true'; // assuming I'm inside a WordPress editor.
	const $read_page_content_via_js = ai4seo_normalize_$( '#ai4seo-read-page-content-via-js' );

	if (ai4seo_exists_$( $read_page_content_via_js )) {
		try_read_page_content_via_js = $read_page_content_via_js.val();
	}

	// find the generation fields.
	let possible_generation_fields = [];

	if (processing_context === 'metadata') {
		possible_generation_fields = ai4seo_get_active_meta_tags();
	} else if (processing_context === 'attachment-attributes') {
		possible_generation_fields = ai4seo_get_active_attachment_attributes();
	}

	// Every editor shares the standard overwrite-all and generate-missing variants.
	const generate_all_overwrite_variants = [true, false];

	// go through each generate all button variant (overwrite existing content: true/false).
	jQuery.each(
        generate_all_overwrite_variants,
		function (_, overwrite_existing_content) {
		// Define button variables.
		let onclick = '';
		let button_title = '';
		let button_label = '';
		let credits_badge_html = '';
		const button_icon_html = ai4seo_get_sooz_logo_svg_tag( 'sooz-oo' );

		if (processing_context === 'metadata') {
			const normalized_generation_fields = ai4seo_get_normalized_generation_fields( possible_generation_fields, overwrite_existing_content, $closest_container, post_id );
			const num_normalized_generation_fields = normalized_generation_fields ? Object.keys( normalized_generation_fields ).length : 0;

			if (num_normalized_generation_fields === 0) {
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': no active meta tags found in ai4seo_add_generate_all_button() \u2014 skipping generate-all button.' );
				return;
			}

			// if we previously and now have the same number of fields, skip adding the button again.
			if (previous_num_normalized_generation_fields === num_normalized_generation_fields) {
				return;
			}

			previous_num_normalized_generation_fields = num_normalized_generation_fields;

			const generation_fields_identifiers = Object.keys( normalized_generation_fields );
			const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names( generation_fields_identifiers );
			const credits_usage = ai4seo_get_credits_usage_from_generation_fields( normalized_generation_fields );

			// build label.
			if (overwrite_existing_content) {
				button_label += wp.i18n.sprintf( wp.i18n.__( 'Generate & <strong>Overwrite</strong><br>Data for %s %s', 'ai-for-seo' ), num_normalized_generation_fields, wp.i18n._n( 'Field', 'Fields', num_normalized_generation_fields, 'ai-for-seo' ) );
				button_title += wp.i18n.sprintf( wp.i18n.__( 'Generate and overwrite existing field(s): %s', 'ai-for-seo' ), human_readable_generated_field_identifiers.join( ', ' ) );
			} else {
				button_label += wp.i18n.sprintf( wp.i18n.__( 'Generate Data for<br>%s <strong>Empty</strong> %s', 'ai-for-seo' ), num_normalized_generation_fields, wp.i18n._n( 'Field', 'Fields', num_normalized_generation_fields, 'ai-for-seo' ) );
				button_title += wp.i18n.sprintf( wp.i18n.__( 'Generate content for empty field(s) only: %s', 'ai-for-seo' ), human_readable_generated_field_identifiers.join( ', ' ) );
			}

			credits_badge_html = '<span class=\"ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge\">' + credits_usage + ' ' + wp.i18n.__( 'Cr', 'ai-for-seo' ) + '</span>';

			// build onclick and title.
			onclick += 'ai4seo_generate_with_ai(this, \"ai4seo_generate_metadata\", ai4seo_get_active_meta_tags(), false, ' + (overwrite_existing_content ? 'true' : 'false') + ', ' + (try_read_page_content_via_js) + ');';
		} else if (processing_context === 'attachment-attributes') {
			const normalized_generation_fields = ai4seo_get_normalized_generation_fields( possible_generation_fields, overwrite_existing_content, $closest_container, post_id );
			const num_normalized_generation_fields = normalized_generation_fields ? Object.keys( normalized_generation_fields ).length : 0;

			if (num_normalized_generation_fields === 0) {
				ai4seo_console_debug( ai4seo_get_plugin_name() + ': no active attachment attributes found in ai4seo_add_generate_all_button() — skipping generate-all button.' );
				return;
			}

			// if we previously and now have the same number of fields, skip adding the button again.
			if (previous_num_normalized_generation_fields === num_normalized_generation_fields) {
				return;
			}

			previous_num_normalized_generation_fields = num_normalized_generation_fields;

			const generation_fields_identifiers = Object.keys( normalized_generation_fields );
			const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names( generation_fields_identifiers );
			const credits_usage = ai4seo_get_credits_usage_from_generation_fields( normalized_generation_fields );

			// build label.
			if (overwrite_existing_content) {
				button_label += wp.i18n.sprintf(
					wp.i18n.__( 'Generate & <strong>Overwrite</strong><br>Data for %s %s', 'ai-for-seo' ),
					num_normalized_generation_fields,
					wp.i18n._n( 'Attribute', 'Attributes', num_normalized_generation_fields, 'ai-for-seo' )
				);
				button_title += wp.i18n.sprintf(
					wp.i18n.__( 'Generate and overwrite existing attribute(s): %s', 'ai-for-seo' ),
					human_readable_generated_field_identifiers.join( ', ' )
				);
			} else {
				button_label += wp.i18n.sprintf(
					wp.i18n.__( 'Generate Data for<br>%s <strong>Empty</strong> %s', 'ai-for-seo' ),
					num_normalized_generation_fields,
					wp.i18n._n( 'Attribute', 'Attributes', num_normalized_generation_fields, 'ai-for-seo' )
				);
				button_title += wp.i18n.sprintf(
					wp.i18n.__( 'Generate content for empty attribute(s) only: %s', 'ai-for-seo' ),
					human_readable_generated_field_identifiers.join( ', ' )
				);
			}

			credits_badge_html = '<span class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' + credits_usage + ' ' + wp.i18n.__( 'Cr', 'ai-for-seo' ) + '</span>';

			// build onclick and title.
			onclick += 'ai4seo_generate_with_ai(this, "ai4seo_generate_attachment_attributes", ai4seo_get_active_attachment_attributes(), false, ' + (overwrite_existing_content ? 'true' : 'false') + ');';
		}

		// put everything together.
		button_html += "<button type='button' onclick='" + onclick + "' title='" + button_title + "' class='ai4seo-button ai4seo-big-button ai4seo-generate-all-button ai4seo-lockable'><span class='ai4seo-generation-button-icon'>" + button_icon_html + "</span><span class='ai4seo-generation-button-label'>" + button_label + '</span>' + credits_badge_html + '</button>';
        }
    );

	const wrapped_button_html = "<div class='ai4seo-generate-all-button-wrapper'>" + button_html + '</div>';

	// Add button-element after element.
	$generate_all_buttons_container.prepend( wrapped_button_html );

	// init the potential new button.
	ai4seo_init_buttons( $generate_all_buttons_container );
}


// =========================================================================================== \\

function ai4seo_get_credits_usage_from_generation_fields(generation_fields) {
	// go through each entry, find 'credits' and sum them up.
	let total_credits = 0;

	for (const field_key in generation_fields) {
		if (generation_fields.hasOwnProperty( field_key )) {
			const field_data = generation_fields[field_key];
			if (field_data.hasOwnProperty( 'credits' )) {
				const field_credits = parseInt( field_data['credits'], 10 );
				if (!isNaN( field_credits )) {
					total_credits += field_credits;
				}
			}
		}
	}

	return total_credits;
}

// =========================================================================================== \\

/**
 * Builds a field-specific accessible name for a single-field generation button.
 *
 * @param {string} field_identifier Generation field identifier.
 * @param {*} credits_usage Credit cost for generating the field.
 * @return {string} Accessible button name.
 */
function ai4seo_get_generate_button_accessible_label(field_identifier, credits_usage) {
	const field_labels = ai4seo_get_human_readable_generation_field_names( [field_identifier] );
	const field_label = field_labels.length > 0 ? field_labels[0] : '';
	const plugin_name = ai4seo_get_plugin_name();
	const normalized_credits_usage = parseInt( credits_usage, 10 );

	if (!field_label || !plugin_name) {
		return '';
	}

	if (isNaN( normalized_credits_usage )) {
		return wp.i18n.sprintf(
			wp.i18n.__( 'Generate %1$s with %2$s', 'ai-for-seo' ),
			field_label,
			plugin_name
		);
	}

	return wp.i18n.sprintf(
		wp.i18n.__( 'Generate %1$s with %2$s — %3$d %4$s', 'ai-for-seo' ),
		field_label,
		plugin_name,
		normalized_credits_usage,
		wp.i18n._n( 'Credit', 'Credits', normalized_credits_usage, 'ai-for-seo' )
	);
}

// =========================================================================================== \\

/**
 * Resolves the editor container beside which a field's generation button is rendered.
 *
 * Yoast and AIOSEO wrap their editable elements, so insertion and lookup must share one reference.
 *
 * @param {*} $generate_data_for_input Generation field or selector.
 * @return {jQuery} Field or external editor container.
 */
function ai4seo_get_generate_button_reference_$($generate_data_for_input) {
	$generate_data_for_input = ai4seo_normalize_$( $generate_data_for_input );

	if (!ai4seo_exists_$( $generate_data_for_input )) {
		return $generate_data_for_input;
	}

	let $generate_button_reference = $generate_data_for_input;
	const external_editor_container_selectors = [
		'.yst-replacevar__editor',
		'.aioseo-html-tags-editor',
	];

	// Later matching containers retain the established precedence used by button insertion.
	for (const editor_container_selector of external_editor_container_selectors) {
		const $editor_container = $generate_data_for_input.closest( editor_container_selector );

		if (ai4seo_exists_$( $editor_container )) {
			$generate_button_reference = $editor_container;
		}
	}

	return $generate_button_reference;
}

// =========================================================================================== \\

function ai4seo_try_add_generate_button_to_input($generate_data_for_input, generate_data_for_input_selector) {
	$generate_data_for_input = ai4seo_normalize_$( $generate_data_for_input );

	if (!ai4seo_exists_$( $generate_data_for_input )) {
		console.warn( ai4seo_get_plugin_name() + ': $generate_data_for_input missing in ai4seo_add_generate_button_to_input() \u2014 skipping button injection.' );
		return;
	}

	const $possible_generate_button = ai4seo_try_find_generate_button_by_input_$( $generate_data_for_input, false );

	// if we find a generate-button that is not inactive, we remove it to avoid duplicates.
	if (ai4seo_exists_$( $possible_generate_button )) {
		// Keep in-flight buttons intact while reinitialization removes only idle duplicates.
		if (ai4seo_is_generate_button_busy( $possible_generate_button )) {
			return;
		}

		$possible_generate_button.remove();
	}

	// Add button-element after input-element.
	let $generate_button = ai4seo_build_generate_button( $generate_data_for_input, generate_data_for_input_selector );

	// Share the external-editor container resolution with the lookup path that prevents duplicate buttons.
	const $generate_button_reference = ai4seo_get_generate_button_reference_$( $generate_data_for_input );

	$generate_button_reference.after( $generate_button );

	// check if we have a generate button now.
	$generate_button = ai4seo_try_find_generate_button_by_input_$( $generate_data_for_input, false );

	if (!ai4seo_exists_$( $generate_button )) {
		// this can be true, if we don't add a generate button to this input because the context is not supported (no post id etc.).
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': failed to add generate button near $generate_data_for_input in ai4seo_add_generate_button_to_input() \u2014 cannot find generate button after adding it.' );
		return;
	}

	// init the potential new button.
	ai4seo_init_buttons( $generate_button );

	// workaround for yoast keyphrase (#id focus-keyword-input-metabox), make input 100% wide, make parent flex-direction: column
	// minor face lift when our button is next to the input.
	if ($generate_button_reference.attr( 'id' ) === 'focus-keyword-input-metabox') {
		$generate_button_reference.css( 'width', '100%' );
		$generate_button_reference.parent().css( 'flex-direction', 'column' );
	}

	// workaround for rank math keyphrase (input is child of .rank-math-focus-keyword > div), make text align left, margin-top: -10px margin-bottom: 10px.
	if ($generate_data_for_input.parent().parent().hasClass( 'rank-math-focus-keyword' )) {
		$generate_button.css( 'text-align', 'left' );
		$generate_button.css( 'transform', 'translateY(-15px)' );
	}

	// workaround for gutenberg editor sidebar.
	const $possible_side_bar_parent = ai4seo_normalize_$( $generate_data_for_input.closest( '.editor-sidebar' ) );

	if (ai4seo_exists_$( $possible_side_bar_parent )) {
		$generate_button.css( 'text-align', 'left' );
	}
}

// =========================================================================================== \\

function ai4seo_try_find_generate_button_by_input_$($generate_data_for_input) {
	$generate_data_for_input = ai4seo_normalize_$( $generate_data_for_input );

	if (!ai4seo_exists_$( $generate_data_for_input )) {
		console.warn( ai4seo_get_plugin_name() + ': $generate_data_for_input missing in ai4seo_get_generate_button_by_input_selector() \u2014 cannot find generate button.' );
		return null;
	}

	// Resolve the same external-editor container used by the insertion path above.
	$generate_data_for_input = ai4seo_get_generate_button_reference_$( $generate_data_for_input );

	const $possible_generate_button = $generate_data_for_input.next();

	if (!ai4seo_exists_$( $possible_generate_button )) {
		return null;
	}

	// Check if element after $parent contains "ai4seo-generate-button"-class.
	if (!$possible_generate_button.hasClass( 'ai4seo-generate-button' )) {
		return null;
	}

	return $possible_generate_button;
}

// =========================================================================================== \\

function ai4seo_build_generate_button($generate_data_for_input, generate_data_for_input_selector, button_label = 'auto', button_title = '') {
	// Make sure that onclick-variable is defined.
	let try_read_page_content_via_js = 'true'; // assuming I'm inside a WordPress editor.
	const $read_page_content_via_js = ai4seo_normalize_$( '#ai4seo-read-page-content-via-js' );

	if (ai4seo_exists_$( $read_page_content_via_js )) {
		try_read_page_content_via_js = $read_page_content_via_js.val();
	}

	const try_read_page_content_via_js_bool = (try_read_page_content_via_js === 'true');
	const try_read_page_content_via_js_string = try_read_page_content_via_js_bool ? 'true' : 'false';

	// get the closest container that holds the generation fields to limit the scope of content reading via js.
	const $closest_container = ai4seo_get_closest_container_$( $generate_data_for_input );

	if (button_label === 'auto') {
		// Generate with SOOZ.
		button_label = wp.i18n.sprintf( wp.i18n.__( 'Generate with %s', 'ai-for-seo' ), ai4seo_get_sooz_logo_svg_tag() );
	}

	// Check if processing-entry exists in mapping-array.
	if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context']) {
		// Prepare onclick for attachment-attributes-processing.
		if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context'] === 'attachment-attributes') {
			// Resolve the attachment id from the same modal/container as the source field.
			let post_id = ai4seo_get_post_id( 'attachment-attributes', $closest_container );

			if (!post_id || isNaN( post_id )) {
				return null;
			}

			if (!ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['attachment_attributes_identifier']) {
				console.error( ai4seo_get_plugin_name() + ': No attachment_attributes_identifier defined for element-selector: ' + generate_data_for_input_selector );
				return;
			}

			const attachment_attributes_identifier = ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['attachment_attributes_identifier'];
			const raw_generation_fields = {[attachment_attributes_identifier]: [generate_data_for_input_selector]};
			const normalized_generation_fields = ai4seo_get_normalized_generation_fields( raw_generation_fields, true, $closest_container, post_id );
			const num_normalized_generation_fields = normalized_generation_fields ? Object.keys( normalized_generation_fields ).length : 0;

			if (num_normalized_generation_fields === 0) {
				console.warn( ai4seo_get_plugin_name() + ': No active attachment attributes found for element-selector: ' + generate_data_for_input_selector );
				return;
			}

			const credits_usage = ai4seo_get_credits_usage_from_generation_fields( normalized_generation_fields );
			const button_accessible_label = ai4seo_get_generate_button_accessible_label( attachment_attributes_identifier, credits_usage );

			button_label += '<div class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' + credits_usage + ' ' + wp.i18n.__( 'Cr', 'ai-for-seo' ) + '</div>';

			// Build button via jQuery (no inline onclick)
			// Prepare additional css-class for button-output.
			let additional_css_class = '';

			if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class']) {
				additional_css_class = ' ' + ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class'];
			}

			const $button = jQuery( '<button type="button" class=""></button>' );

			if (button_title) {
				$button.attr( 'title', button_title );
			}
			if (button_accessible_label) {
				$button.attr( 'aria-label', button_accessible_label );
			}

			$button.addClass( 'ai4seo-button ai4seo-generate-button ai4seo-generate-button-arrow ai4seo-lockable' + additional_css_class );
			$button.html( button_label );

			$button.off( 'click.ai4seo-generate' );
			$button.on(
                'click.ai4seo-generate',
                function () {
				ai4seo_generate_with_ai( this, 'ai4seo_generate_attachment_attributes', normalized_generation_fields, post_id, true );
                }
            );

			return $button;
		} else if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context'] === 'metadata') {
			// Prepare onclick for metadata-processing.
			// Resolve the metadata post id from the same modal/container as the source field.
			let post_id = ai4seo_get_post_id( 'metadata', $closest_container );

			if (!post_id || isNaN( post_id )) {
				return null;
			}

			if (!ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['metadata_identifier']) {
				console.error( ai4seo_get_plugin_name() + ': No metadata_identifier defined for element-selector: ' + generate_data_for_input_selector );
				return;
			}

			const metadata_identifier = ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['metadata_identifier'];
			const raw_generation_fields = {[metadata_identifier]: [generate_data_for_input_selector]};
			const normalized_generation_fields = ai4seo_get_normalized_generation_fields( raw_generation_fields, true, $closest_container, post_id );
			const num_normalized_generation_fields = normalized_generation_fields ? Object.keys( normalized_generation_fields ).length : 0;

			if (num_normalized_generation_fields === 0) {
				console.warn( ai4seo_get_plugin_name() + ': No active meta tags found for element-selector: ' + generate_data_for_input_selector );
				return;
			}

			const credits_usage = ai4seo_get_credits_usage_from_generation_fields( normalized_generation_fields );
			const button_accessible_label = ai4seo_get_generate_button_accessible_label( metadata_identifier, credits_usage );

			button_label += '<div class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' +
				credits_usage + ' ' + wp.i18n.__( 'Cr', 'ai-for-seo' ) + '</div>';

			// Build button via jQuery (no inline onclick)
			// Prepare additional css-class for button-output.
			let additional_css_class = '';

			if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class']) {
				additional_css_class = ' ' + ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class'];
			}

			const $button = jQuery( '<button type="button" class=""></button>' );
			if (button_title) {
				$button.attr( 'title', button_title );
			}
			if (button_accessible_label) {
				$button.attr( 'aria-label', button_accessible_label );
			}
			$button.addClass( 'ai4seo-button ai4seo-generate-button ai4seo-generate-button-arrow ai4seo-lockable' + additional_css_class );
			$button.html( button_label );

			$button.off( 'click.ai4seo-generate' );
			$button.on(
                'click.ai4seo-generate',
                function () {
				ai4seo_generate_with_ai( this, 'ai4seo_generate_metadata', normalized_generation_fields, post_id, true, try_read_page_content_via_js_bool );
                }
            );

			return $button;
		} else {
			console.error( ai4seo_get_plugin_name() + ': Unknown processing-context: ' + ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context'] );
		}
	} else {
		console.error( ai4seo_get_plugin_name() + ': No processing-context defined for element-selector: ' + generate_data_for_input_selector );
	}

	return null;
}

// =========================================================================================== \\

function ai4seo_get_editor_context_$() {
	// Define variable for the elementor-preview-iframe-element.
	const $elementor_preview_iframe = ai4seo_normalize_$( '#elementor-preview-iframe' );

	if (ai4seo_exists_$( $elementor_preview_iframe )) {
		return ai4seo_normalize_$( $elementor_preview_iframe.contents() );
	}

	// Define variable for the be-builder-iframe.
	const $mfn_iframe = ai4seo_normalize_$( '#mfn-vb-ifr' );

	if (ai4seo_exists_$( $mfn_iframe )) {
		return ai4seo_normalize_$( $mfn_iframe.contents() );
	}

	// define variable for the gutenberg-editor-iframe (name="editor-canvas").
	const $gutenberg_editor_iframe = ai4seo_normalize_$( 'iframe[name="editor-canvas"]' );

	if (ai4seo_exists_$( $gutenberg_editor_iframe )) {
		return ai4seo_normalize_$( $gutenberg_editor_iframe.contents() );
	}

	// Return jQuery-document if no elementor-iframe exists.
	return ai4seo_normalize_$( document );
}

// =========================================================================================== \\

/**
 * Check if the user is inside the Elementor editor.
 *
 * @return bool True if inside the Elementor editor, false otherwise.
 */
function ai4seo_is_inside_elementor_editor() {
	const $body = ai4seo_normalize_$( 'body', document );

	if (!ai4seo_exists_$( $body )) {
		console.error( ai4seo_get_plugin_name() + ': body element missing in ai4seo_is_inside_elementor_editor() \u2014 cannot determine if inside Elementor editor.' );
		return false;
	}

	return typeof elementor !== 'undefined' &&
		typeof elementorFrontend !== 'undefined' &&
		ai4seo_exists_$( $body ) && $body.hasClass( 'elementor-editor-active' );
}

// =========================================================================================== \\

function ai4seo_is_inside_gutenberg_editor() {
	const $body = ai4seo_normalize_$( 'body', document );

	if (!ai4seo_exists_$( $body )) {
		console.error( ai4seo_get_plugin_name() + ': body element missing in ai4seo_is_inside_gutenberg_editor() \u2014 cannot determine if inside Gutenberg editor.' );
		return false;
	}

	return ai4seo_exists_$( $body ) && $body.hasClass( 'block-editor-page' );
}

// =========================================================================================== \\

function ai4seo_is_inside_muffin_builder_editor() {
	const $muffin_visual_builder = ai4seo_normalize_$( '#mfn-visualbuilder', document );

	return ai4seo_exists_$( $muffin_visual_builder );
}

// =========================================================================================== \\

function ai4seo_add_loading_html_to_element($target) {
	// Make sure that element is jquery-element.
	$target = ai4seo_normalize_$( $target );

	if (!ai4seo_exists_$( $target )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$target\" missing in ai4seo_add_loading_html_to_element() \u2014 cannot display loading state.' );
		return;
	}

	$target.each(
        function () {
		// Define variable for this element.
		const $this = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this )) {
			console.warn( ai4seo_get_plugin_name() + ': element \"$this\" missing in ai4seo_add_loading_html_to_element() \u2014 cannot display loading state.' );
			return;
		}

		// check if we already have a data-ai-for-seo-original-html-content.
		if ($this.attr( 'data-ai-for-seo-original-html-content' )) {
			// already in loading state.
			return;
		}

		// check width and height, preserve it to avoid layout shifts.
		const current_width = $this.outerWidth();
		const current_height = $this.outerHeight();

		if (current_width > 0) {
			$this.css( 'width', current_width + 'px' );
		}

		if (current_height > 0) {
			$this.css( 'height', current_height + 'px' );
		}

		// Define variable for the original html-content.
		const original_html_content = $this.html();

		// Replace html-content with loading-elements.
		$this.html( "<div class='ai4seo-loading-animation-container'><div class='ai4seo-loading-animation'><div></div><div></div><div></div><div></div></div></div>" );

		// Add data-attribute to element with original html-content.
		$this.attr( 'data-ai-for-seo-original-html-content', original_html_content );

		// Add class to deactivate element to element.
		$this.addClass( 'ai4seo-inactive-element' );
        }
    );
}

// =========================================================================================== \\

function ai4seo_remove_loading_html_from_element($target) {
	// Make sure that element is jquery-element.
	$target = ai4seo_normalize_$( $target );

	if (!ai4seo_exists_$( $target )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': element \"$target\" missing in ai4seo_remove_loading_html_from_element() \u2014 cannot remove loading state.' );
		return;
	}

	$target.each(
        function () {
		// Define variable for this element.
		const $this = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this )) {
			console.warn( ai4seo_get_plugin_name() + ': element \"$this\" missing in ai4seo_remove_loading_html_from_element() \u2014 cannot remove loading state.' );
			return;
		}

		// Define variable for the original html-content.
		const original_html_content = $this.attr( 'data-ai-for-seo-original-html-content' );

		// Remove data-attribute from element.
		$this.removeAttr( 'data-ai-for-seo-original-html-content' );

		// Replace html-content with original html-content.
		$this.html( original_html_content );

		// Remove class to deactivate element from element.
		$this.removeClass( 'ai4seo-inactive-element' );
        }
    );
}

// =========================================================================================== \\

function ai4seo_get_closest_container_$($reference_element) {
	// Check editor-specific roots before falling back to the page body.
	let $closest_container = $reference_element.closest( '.ai4seo-modal' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// AIOSEO can render simultaneous metabox and Gutenberg-sidebar instances.
	$closest_container = $reference_element.closest( '.aioseo-post-settings' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope SEOPress generation to its universal React metabox instance.
	$closest_container = $reference_element.closest( ai4seo_seopress_editor_root_selector );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope classic SEOPress generation to its legacy metabox.
	$closest_container = $reference_element.closest( '#seopress_cpt' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope SEO SIMPLE PACK generation to its post editor metabox.
	$closest_container = $reference_element.closest( '#ssp_metabox' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope Slim SEO generation to its post editor metabox.
	$closest_container = $reference_element.closest( '#slim-seo' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope Squirrly generation to the AJAX-refreshed snippet editor instance.
	$closest_container = $reference_element.closest( ai4seo_squirrly_editor_root_selector );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// Scope The SEO Framework generation to its post editor metabox.
	$closest_container = $reference_element.closest( ai4seo_the_seo_framework_editor_root_selector );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// media frame content.
	$closest_container = $reference_element.closest( '.wp-core-ui .media-frame-content' );

	if (ai4seo_exists_$( $closest_container )) {
		return $closest_container;
	}

	// fallback to body.
	return ai4seo_normalize_$( 'body' );
}

// ___________________________________________________________________________________________ \\
// === SVG =================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_svg_tag(icon_name, icon_css_class, alt_text) {
	// Make sure that the icon-name is allowed.
	if (!ai4seo_svg_icons[icon_name]) {
		return '';
	}

	let svg_tag = ai4seo_svg_icons[icon_name];

	// add css class to svg tag.
	if (icon_css_class) {
		icon_css_class = 'ai4seo-icon ' + icon_css_class;
	} else {
		icon_css_class = 'ai4seo-icon';
	}

	svg_tag = svg_tag.replace( '<svg', "<svg class='" + icon_css_class + "'" );

	// add alt text to svg tag.
	if (alt_text) {
		svg_tag = svg_tag.replace( '<svg', "<svg aria-label='" + alt_text + "'" );
		svg_tag = svg_tag.replace( '</svg>', '<title>' + alt_text + '</title></svg>' );
	}

	return svg_tag;
}

// =========================================================================================== \\

function ai4seo_get_sooz_logo_svg_tag(icon_name = 'sooz', icon_css_class = '', alt_text = '') {
	if (!icon_name) {
		icon_name = 'sooz';
	}

	if (!icon_css_class) {
		icon_css_class = 'ai4seo-sooz-logo';
	} else {
		icon_css_class += ' ai4seo-sooz-logo';
	}

	if (!alt_text) {
		alt_text = ai4seo_get_plugin_name();
	}

	return ai4seo_get_svg_tag( icon_name, icon_css_class, alt_text );
}


// ___________________________________________________________________________________________ \\
// === MODALS ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_open_generic_error_notification_modal(error_code = 999, error_message = '', footer = '', modal_settings = {}) {
	if (!error_message) {
		error_message = wp.i18n.sprintf( wp.i18n.__( "Please check your settings or <a href='%s' target='_blank'>contact us</a>.", 'ai-for-seo' ), ai4seo_official_contact_url );
	}

	let default_headline = wp.i18n.__( 'An error occurred', 'ai-for-seo' );
	let content = error_message + ' (' + wp.i18n.__( 'error code', 'ai-for-seo' ) + ': #' + error_code + ')';

	// default notification modal settings.
	let default_settings = {
		close_on_outside_click: false,
		add_close_button: false,
		headline: (modal_settings.headline ? modal_settings.headline : default_headline),
		content: content,
	};

	// additional settings for low credits error.
	if (error_code === 1115424 || error_code === 1215424) {
		modal_settings.headline = wp.i18n.__( 'Insufficient Credits', 'ai-for-seo' );
		modal_settings.add_close_button = true;
		modal_settings.content = error_message;
		modal_settings.footer = "<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_close_all_modals();ai4seo_open_get_more_credits_modal();'>" + wp.i18n.__( 'Click here to add more Credits', 'ai-for-seo' ) + '</button>';
	}

	// merge settings.
	modal_settings = Object.assign( {}, default_settings, modal_settings );

	ai4seo_open_notification_modal( modal_settings.headline, modal_settings.content, footer, modal_settings );
}

// =========================================================================================== \\

function ai4seo_open_generic_success_notification_modal(content, footer = '', modal_settings = {}) {
	let default_headline = wp.i18n.__( 'Success!', 'ai-for-seo' );

	// Display success message.
	let check_icon = ai4seo_get_svg_tag( 'circle-check', 'ai4seo-big-icon ai4seo-fill-green', wp.i18n.__( 'Success!', 'ai-for-seo' ) );
	let default_content = check_icon + '<br>' + wp.i18n.__( 'The data have been saved successfully.', 'ai-for-seo' );

	// default notification modal settings.
	let default_settings = {
		close_on_outside_click: true,
		add_close_button: true,
		headline: (modal_settings.headline ? modal_settings.headline : default_headline),
		content: (content ? content : default_content),
	};

	// merge settings.
	modal_settings = Object.assign( {}, default_settings, modal_settings );

	ai4seo_open_notification_modal( modal_settings.headline, modal_settings.content, footer, modal_settings );
}

// =========================================================================================== \\

/**
 * Open the reusable notification modal and return its scoped jQuery element.
 *
 * @param {string} headline Modal headline.
 * @param {string|jQuery} content Modal content.
 * @param {string|jQuery} footer Modal footer.
 * @param {Object} modal_settings Modal behavior and presentation settings.
 * @return {jQuery|null|undefined} Created modal, or no element when modal creation was cancelled.
 */
function ai4seo_open_notification_modal(headline = '', content = '', footer = '', modal_settings = {}) {
	// All notification callers share one replaceable modal identity and fallback close action.
	const modal_id = 'ai4seo-notification-modal';
	const default_footer = "<button type='button' onclick='ai4seo_close_modal(\"" + modal_id + "\");' class='ai4seo-button ai4seo-primary-button'>" + wp.i18n.__( 'Close', 'ai-for-seo' ) + '</button>';

	// Defaults retain established notification behavior while allowing specialized callers to override it.
	const default_settings = {
		close_on_outside_click: false,
		add_close_button: false,
		modal_css_class: 'ai4seo-notification-modal',
		modal_wrapper_css_class: 'ai4seo-notification-modal-wrapper',
		modal_size: 'small',
		headline: headline,
		content: content,
		footer: (footer ? footer : default_footer),
	};

	// Caller settings take precedence without mutating the shared defaults object.
	modal_settings = Object.assign( {}, default_settings, modal_settings );

	return ai4seo_open_modal_$( modal_id, modal_settings );
}

// =========================================================================================== \\

function ai4seo_close_notification_modal() {
	ai4seo_close_modal( 'ai4seo-notification-modal' );
}

// =========================================================================================== \\

/**
 * Moves schemas bundled with an AJAX modal response into the document-level schema container.
 *
 * @param {jQuery} $response_container Detached AJAX response root.
 */
function ai4seo_install_modal_schemas_from_ajax_response($response_container) {
	$response_container = ai4seo_normalize_$( $response_container );

	if (!ai4seo_exists_$( $response_container )) {
		return;
	}

	const $incoming_schema_containers = $response_container.find( '.ai4seo-modal-schemas-container' );

	if (!ai4seo_exists_$( $incoming_schema_containers )) {
		return;
	}

	let $document_schema_container = ai4seo_normalize_$( '.ai4seo-modal-schemas-container' ).first();
	const $body = ai4seo_normalize_$( document.body );

	$incoming_schema_containers.each(
		function () {
			const $incoming_schema_container = ai4seo_normalize_$( this );

			$incoming_schema_container.children( '.ai4seo-modal-schema[id]' ).each(
				function () {
					const schema_element = this;

					// Preserve an already loaded or currently active schema instead of replacing its state.
					if (!schema_element.id || document.getElementById( schema_element.id )) {
						return;
					}

					if (!ai4seo_exists_$( $document_schema_container )) {
						if (!ai4seo_exists_$( $body )) {
							return;
						}

						$document_schema_container = ai4seo_normalize_$( '<div class="ai4seo-modal-schemas-container"></div>' );
						$body.append( $document_schema_container );
					}

					$document_schema_container.append( ai4seo_normalize_$( schema_element ).detach() );
				}
			);

			// Response-local containers must not leak into the visible AJAX modal content.
			$incoming_schema_container.remove();
		}
	);
}

// =========================================================================================== \\

function ai4seo_open_ajax_modal(ajax_action, ajax_data = {}, modal_settings = {}) {
	// ajax -> add loading icon to content.
	let default_content = "<div class='ai4seo-ajax-modal-loading-icon'>" + ai4seo_get_svg_tag( 'rotate', 'ai4seo-spinning-icon', wp.i18n.__( 'Loading... Please wait.', 'ai-for-seo' ) ) + '</div>';
	const content_loaded_callback = typeof modal_settings.content_loaded_callback === 'function'
		? modal_settings.content_loaded_callback
		: null;

	// Keep internal lifecycle callbacks out of generic modal rendering settings.
	modal_settings = Object.assign( {}, modal_settings );
	delete modal_settings.content_loaded_callback;

	// default ajax modal settings.
	let default_settings = {
		modal_id: 'ai4seo-ajax-modal',
		close_on_outside_click: true,
		add_close_button: true,
		modal_css_class: 'ai4seo-ajax-modal',
		modal_wrapper_css_class: 'ai4seo-ajax-modal-wrapper',
		content: default_content,
	}

	// Resolve the AJAX-specific modal id before handing generic settings to the modal renderer.
	modal_settings = Object.assign( {}, default_settings, modal_settings );
	const modal_id = modal_settings.modal_id || default_settings.modal_id;
	delete modal_settings.modal_id;

	// Stop the AJAX flow when an existing same-id modal refuses to close because of unsaved changes.
	const $modal = ai4seo_open_modal_$( modal_id, modal_settings );

	// Avoid sending the AJAX request when the modal was not created or an existing modal stayed open.
	if (!ai4seo_exists_$( $modal )) {
		return;
	}

	// ajax -> perform ajax call.
	ai4seo_perform_ajax_call( ajax_action, ajax_data, false )
		.then(
            response => {
			// check if modal is still open (maybe closed by the user by now).
			if (!ai4seo_get_modal_$( modal_id )) {
				console.error( ai4seo_get_plugin_name() + ': Could not find modal with id: ' + modal_id );
				return;
			}

			// on error, set response to error message.
			if (typeof response !== 'string') {
				const original_response = response;
				response = wp.i18n.__( 'An unknown error occurred while loading the modal content.', 'ai-for-seo' );
				console.error( ai4seo_get_plugin_name() + ': Invalid response received for ajax modal with id: ' + modal_id, response, original_response );
			}

			// normalize original response.
			let $response = ai4seo_normalize_$( response );
			if (!ai4seo_exists_$( $response )) {
				response = '<div>' + response + '</div>';
				$response = ai4seo_normalize_$( response );
			}
			if (!ai4seo_exists_$( $response )) {
				console.error( ai4seo_get_plugin_name() + ': Could not parse response for ajax modal with id: ' + modal_id, response );
				return;
			}
			// wrap everything into a shared root so removals affect the same DOM tree.
			const $response_container = ai4seo_normalize_$( '<div class="ai4seo-modal-response-root"></div>' );
			$response_container.append( $response );
			// Install response-bundled dependency schemas before the parser extracts and discards siblings.
			ai4seo_install_modal_schemas_from_ajax_response( $response_container );
			// find modal headline in response and set it separately.
			let $possible_modal_headline = $response_container.find( '.ai4seo-modal-headline' ).first();
			if (ai4seo_exists_$( $possible_modal_headline )) {
				let headline_html = $possible_modal_headline.prop( 'outerHTML' );
				$possible_modal_headline.remove();
				ai4seo_set_modal_headline( modal_id, headline_html );
			}
			// find modal sub headline in response and set it separately.
			let $possible_modal_sub_headline = $response_container.find( '.ai4seo-modal-sub-headline' ).first();
			if (ai4seo_exists_$( $possible_modal_sub_headline )) {
				let sub_headline_html = $possible_modal_sub_headline.prop( 'outerHTML' );
				$possible_modal_sub_headline.remove();
				ai4seo_set_modal_sub_headline( modal_id, sub_headline_html );
			}
			// find footer in response and set it separately.
			let $possible_modal_footer = $response_container.find( '.ai4seo-modal-footer' ).first();
			if (ai4seo_exists_$( $possible_modal_footer )) {
				let footer_html = $possible_modal_footer.prop( 'outerHTML' );
				$possible_modal_footer.remove();
				ai4seo_set_modal_footer( modal_id, footer_html );
			}
			// find modal content in response and set it separately.
			let $possible_modal_content = $response_container.find( '.ai4seo-modal-content' ).first();
			// fallback: if no .ai4seo-modal-content found, use remaining DOM after extractions.
			if (!ai4seo_exists_$( $possible_modal_content )) {
				const $wrapped_remaining = ai4seo_normalize_$( '<div class="ai4seo-modal-content"></div>' );
				$wrapped_remaining.append( $response_container.contents() );
				$response_container.empty();
				ai4seo_set_modal_content( modal_id, $wrapped_remaining );
			} else {
				$possible_modal_content.remove();
				ai4seo_set_modal_content( modal_id, $possible_modal_content );
			}

			// init modal.
			ai4seo_init_modal( modal_id, modal_settings.close_on_outside_click );

			if (content_loaded_callback) {
				try {
					content_loaded_callback( ai4seo_get_modal_$( modal_id ) );
				} catch (error) {
					console.error( ai4seo_get_plugin_name() + ': AJAX modal content callback failed for modal id: ' + modal_id, error );
				}
			}
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 2212181225, error );
			ai4seo_close_modal( modal_id );
            }
        )
		.finally(
            () => { /* do nothing */
            }
        );
}

// =========================================================================================== \\

function ai4seo_set_modal_headline(modal_id, headline_html) {
	let $modal = ai4seo_get_modal_$( modal_id );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': Could not find modal with id: ' + modal_id );
		return;
	}

	// remove headline if exists.
	let $modal_headline = $modal.find( '.ai4seo-modal-headline' );

	if (ai4seo_exists_$( $modal_headline )) {
		$modal_headline.remove();
	}

	// if headline_html is the .ai4seo-modal-headline element, use it directly.
	let $possible_modal_headline = ai4seo_normalize_$( headline_html );

	if (!ai4seo_exists_$( $possible_modal_headline ) || !$possible_modal_headline.hasClass( 'ai4seo-modal-headline' )) {
		headline_html = '<div class=\"ai4seo-modal-headline\">' + headline_html + '</div>';
	}

	// try to find existing sub-headline, or content to insert headline before. fallback to append.
	const $modal_sub_headline = $modal.find( '.ai4seo-modal-sub-headline' );
	const $modal_content = $modal.find( '.ai4seo-modal-content' );

	if (ai4seo_exists_$( $modal_sub_headline )) {
		$modal_sub_headline.before( headline_html );
	} else if (ai4seo_exists_$( $modal_content )) {
		$modal_content.before( headline_html );
	} else {
		$modal.append( headline_html );
	}

	// Re-evaluate the accessible name when callers replace a modal headline after creation.
	ai4seo_sync_modal_accessibility( $modal );
}

// =========================================================================================== \\

function ai4seo_set_modal_sub_headline(modal_id, sub_headline_html) {
	let $modal = ai4seo_get_modal_$( modal_id );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': Could not find modal with id: ' + modal_id );
		return;
	}

	// remove headline if exists.
	let $modal_sub_headline = $modal.find( '.ai4seo-modal-sub-headline' );

	if (ai4seo_exists_$( $modal_sub_headline )) {
		$modal_sub_headline.remove();
	}

	// if sub_headline_html is the .ai4seo-modal-sub-headline element, use it directly.
	let $possible_modal_sub_headline = ai4seo_normalize_$( sub_headline_html );

	if (!ai4seo_exists_$( $possible_modal_sub_headline ) || !$possible_modal_sub_headline.hasClass( 'ai4seo-modal-sub-headline' )) {
		sub_headline_html = '<div class=\"ai4seo-modal-sub-headline\">' + sub_headline_html + '</div>';
	}

	// try to find existing headline, or content to insert sub-headline after. fallback to append.

	const $modal_headline = $modal.find( '.ai4seo-modal-headline' );
	const $modal_content = $modal.find( '.ai4seo-modal-content' );

	if (ai4seo_exists_$( $modal_headline )) {
		$modal_headline.after( sub_headline_html );
	} else if (ai4seo_exists_$( $modal_content )) {
		$modal_content.before( sub_headline_html );
	} else {
		$modal.append( sub_headline_html );
	}
}

// =========================================================================================== \\

function ai4seo_set_modal_content(modal_id, content_html) {
	let $modal = ai4seo_get_modal_$( modal_id );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': Could not find modal with id: ' + modal_id );
		return;
	}

	// remove content if exists.
	let $modal_content = $modal.find( '.ai4seo-modal-content' );

	if (ai4seo_exists_$( $modal_content )) {
		$modal_content.remove();
	}

	let $content = content_html;

	// normalize to jQuery element.
	if (typeof content_html === 'string') {
		$content = ai4seo_normalize_$( content_html );
	} else {
		$content = ai4seo_normalize_$( content_html );
	}

	// ensure we insert a .ai4seo-modal-content wrapper as a node (no HTML string concatenation).
	if (!ai4seo_exists_$( $content )) {
		$content = ai4seo_normalize_$( '<div class="ai4seo-modal-content"></div>' );
	} else if (!$content.hasClass( 'ai4seo-modal-content' )) {
		const $wrapped = ai4seo_normalize_$( '<div class="ai4seo-modal-content"></div>' );
		$wrapped.append( $content );
		$content = $wrapped;
	}

	// try to find existing sub-headline, headline or footer to insert content between. fallback to append.
	const $modal_sub_headline = $modal.find( '.ai4seo-modal-sub-headline' );
	const $modal_headline = $modal.find( '.ai4seo-modal-headline' );
	const $modal_footer = $modal.find( '.ai4seo-modal-footer' );

	if (ai4seo_exists_$( $modal_sub_headline )) {
		$modal_sub_headline.after( $content );
	} else if (ai4seo_exists_$( $modal_headline )) {
		$modal_headline.after( $content );
	} else if (ai4seo_exists_$( $modal_footer )) {
		$modal_footer.before( $content );
	} else {
		$modal.append( $content );
	}
}

// =========================================================================================== \\

function ai4seo_set_modal_footer(modal_id, footer_html) {
	let $modal = ai4seo_get_modal_$( modal_id );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': Could not find modal with id: ' + modal_id );
		return;
	}

	// remove footer if exists.
	let $modal_footer = $modal.find( '.ai4seo-modal-footer' );

	if (ai4seo_exists_$( $modal_footer )) {
		$modal_footer.remove();
	}

	// if footer_html is the .ai4seo-modal-footer element, use it directly.
	let $possible_modal_footer = ai4seo_normalize_$( footer_html );

	if (!ai4seo_exists_$( $possible_modal_footer ) || !$possible_modal_footer.hasClass( 'ai4seo-modal-footer' )) {
		footer_html = '<div class=\"ai4seo-modal-footer\">' + footer_html + '</div>';
	}

	// append footer to modal.
	$modal.append( footer_html );
}


// =========================================================================================== \\

/**
 * Close an AJAX modal, defaulting to the legacy single-modal id for older callers.
 *
 * @param {string} modal_id
 */
function ai4seo_close_ajax_modal(modal_id = 'ai4seo-ajax-modal') {
	ai4seo_close_modal( modal_id );
}

// =========================================================================================== \\

function ai4seo_open_modal_from_schema(modal_schema_identifier, modal_settings = {}) {
	let $modal_schema = ai4seo_normalize_$( '.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier );

	if (!ai4seo_exists_$( $modal_schema )) {
		console.error( ai4seo_get_plugin_name() + ': Could not find modal schema with id: ' + modal_schema_identifier );
		return null;
	}

	// Close an existing same-schema modal before moving content out of the hidden schema container.
	let modal_id = 'ai4seo-' + modal_schema_identifier;
	const $existing_modal_candidate = ai4seo_get_modal_$( modal_id );

	if (ai4seo_exists_$( $existing_modal_candidate ) && !ai4seo_close_modal( modal_id )) {
		return null;
	}

	// find headline, content and footer.
	let default_settings = {};

	// find and remove headline from schema.
	let modal_schema_headline = $modal_schema.find( '.ai4seo-modal-schema-headline' );

	if (ai4seo_exists_$( modal_schema_headline )) {
		default_settings['headline'] = modal_schema_headline.html();
		modal_schema_headline.html( '' );
	}

	// find content and remove it from schema.
	let modal_schema_content = $modal_schema.find( '.ai4seo-modal-schema-content' );

	if (ai4seo_exists_$( modal_schema_content )) {
		default_settings['content'] = modal_schema_content.html();
		modal_schema_content.html( '' );
	}

	// find footer and remove it from schema.
	let modal_schema_footer = $modal_schema.find( '.ai4seo-modal-schema-footer' );

	if (ai4seo_exists_$( modal_schema_footer )) {
		default_settings['footer'] = modal_schema_footer.html();
		modal_schema_footer.html( '' );
	}

	// merge settings.
	modal_settings = Object.assign( {}, default_settings, modal_settings );

	// open modal.
	let $modal = ai4seo_open_modal_$( modal_id, modal_settings );

	// Restore schema content if modal creation failed after the schema content was moved.
	if (!ai4seo_exists_$( $modal )) {
		if (ai4seo_exists_$( modal_schema_headline ) && Object.prototype.hasOwnProperty.call( default_settings, 'headline' )) {
			modal_schema_headline.html( default_settings['headline'] );
		}

		if (ai4seo_exists_$( modal_schema_content ) && Object.prototype.hasOwnProperty.call( default_settings, 'content' )) {
			modal_schema_content.html( default_settings['content'] );
		}

		if (ai4seo_exists_$( modal_schema_footer ) && Object.prototype.hasOwnProperty.call( default_settings, 'footer' )) {
			modal_schema_footer.html( default_settings['footer'] );
		}

		return null;
	}

	// add schema identifier to modal.
	$modal.data( 'ai4seo-modal-schema-identifier', modal_schema_identifier );

	return $modal;
}

// =========================================================================================== \\

function ai4seo_close_modal_from_schema(modal_schema_identifier) {
	ai4seo_close_modal( 'ai4seo-' + modal_schema_identifier );
}

// =========================================================================================== \\

function ai4seo_open_modal_$(modal_id, modal_settings = {}) {
	// === PREPARE PARAMETERS ================================================================ \\

	if (!modal_id) {
		modal_id = 'ai4seo-modal';
	}

	// default settings.
	let default_settings = {
		close_on_outside_click: true,
		add_close_button: true,
		modal_css_class: '',
		modal_wrapper_css_class: '',
		headline_icon: 'default',
		headline: '',
		content: '',
		footer: '',
		modal_size: 'medium', // small, medium, large, auto.
	}

	// merge settings.
	modal_settings = Object.assign( {}, default_settings, modal_settings );

	// define default headline icon.
	if (modal_settings.headline_icon === 'default') {
		modal_settings.headline_icon = ai4seo_get_sooz_logo_svg_tag( 'sooz-oo', 'ai4seo-sooz-logo' );
	}

	// check if message is a jQuery element -> use it's html instead.
	if (modal_settings.content instanceof jQuery) {
		modal_settings.content = modal_settings.content.html();
	}

	if (modal_settings.headline instanceof jQuery) {
		modal_settings.headline = modal_settings.headline.html();
	}

	if (modal_settings.footer instanceof jQuery) {
		modal_settings.footer = modal_settings.footer.html();
	}

	// === PREPARE MODAL ================================================================================== \\

	// Remove existing same-id modals first; a cancelled unsaved-changes warning must abort opening.
	const $existing_modal_candidate = ai4seo_get_modal_$( modal_id );

	if (ai4seo_exists_$( $existing_modal_candidate )) {
		const existing_modal_closed = ai4seo_close_modal( modal_id );

		if (!existing_modal_closed) {
			return null;
		}
	}

	// check for setting unsaved_changes_warnings, if true, add css class ai4seo-unsaved-changes-warnings to modal_css_class.
	if (modal_settings.unsaved_changes_warnings) {
		if (modal_settings.modal_css_class) {
			modal_settings.modal_css_class += ' ';
		}

		modal_settings.modal_css_class += 'ai4seo-unsaved-changes-warnings';
	}

	// create empty modal.
	let $modal = ai4seo_create_empty_modal_$( modal_id, modal_settings.modal_css_class, modal_settings.modal_wrapper_css_class, modal_settings.modal_size );

	if (!$modal) {
		return;
	}

	// === ADD CONTENTS ================================================================================== \\

	// add close button.
	if (modal_settings.add_close_button) {
		const ai4seo_close_modal_label = wp.i18n.__( 'Close modal', 'ai-for-seo' );
		const $ai4seo_close_button = jQuery( '<button type="button" class="ai4seo-modal-close-icon"></button>' );

		$ai4seo_close_button.attr( 'aria-label', ai4seo_close_modal_label );
		$ai4seo_close_button.attr( 'title', ai4seo_close_modal_label );
		$ai4seo_close_button.html( ai4seo_get_svg_tag( 'square-xmark', '', '' ) );
		$ai4seo_close_button.on(
            'click',
            function () {
			ai4seo_close_modal( modal_id );
            }
        );

		$modal.append( $ai4seo_close_button );
	}

	// set headline.
	if (modal_settings.headline) {
		// also check if there is not already a headline icon.
		if (modal_settings.headline_icon && !modal_settings.headline.includes( 'ai4seo-modal-headline-icon' )) {
			modal_settings.headline = "<div class='ai4seo-modal-headline-icon'>" + modal_settings.headline_icon + '</div>' + modal_settings.headline;
		}

		$modal.append( "<div class='ai4seo-modal-headline'>" + modal_settings.headline + '</div>' );
	}

	// set content.
	if (modal_settings.content) {
		$modal.append( "<div class='ai4seo-modal-content'>" + modal_settings.content + '</div>' );
	}

	// set footer.
	if (modal_settings.footer) {
		$modal.append( "<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>" + modal_settings.footer + '</div>' );
	}

	// Modal content may have added or replaced the headline after the empty shell was created.
	ai4seo_sync_modal_accessibility( $modal );

	// add functions to modal.
	ai4seo_init_modal( modal_id, modal_settings.close_on_outside_click );

	return $modal;
}

// =========================================================================================== \\

function ai4seo_init_modal(modal_id, close_on_outside_click) {
	if (!ai4seo_get_modal_$( modal_id )) {
		return;
	}

	let $modal = ai4seo_get_modal_$( modal_id );

	// close on outside click?
	if (close_on_outside_click) {
		// keep track of the mousedown origin, to only close the modal, if the mouseup event is on the wrapper too
		// to prevent closing the layer while dragging the mouse from inside the modal to outside while selecting
		// text for example.
		let $modal_wrapper = ai4seo_get_modal_wrapper_$( modal_id );

		if ($modal_wrapper && $modal_wrapper.length) {
			$modal_wrapper
				.off( 'mousedown.ai4seo-modal' )
				.on(
                    'mousedown.ai4seo-modal',
                    function (event) {
					ai4seo_mousedown_origin = event.target;
                    }
                );

			$modal_wrapper
				.off( 'mouseup.ai4seo-modal' )
				.on(
                    'mouseup.ai4seo-modal',
                    function (event) {
					if (event.target === ai4seo_mousedown_origin) {
						ai4seo_close_modal( modal_id );
					}
                    }
                );
		}
	}

	// Move keyboard focus only after modal setup has exposed its final controls.
	ai4seo_focus_modal( $modal );

	// Initialize only the inserted modal subtree, coalesced with other DOM changes in this frame.
	ai4seo_schedule_html_elements_init( $modal );

	// Reposition once when an uncached modal image resolves after the initial render measurement.
	ai4seo_init_modal_image_position_updates( $modal );

	// Position after the browser has measured the static shell or final AJAX response content.
	ai4seo_schedule_modal_position( $modal );
}

// =========================================================================================== \\

/**
 * Schedule one modal position update for the next rendering opportunity.
 *
 * @param {jQuery|HTMLElement} modal
 */
function ai4seo_schedule_modal_position(modal) {
	if (!ai4seo_exists_$( modal )) {
		return;
	}

	const $modal = ai4seo_normalize_$( modal );
	const modal_position_scheduled_data_key = 'ai4seo-modal-position-scheduled';

	// Multiple image events in the same frame require only the final measured modal height.
	if ($modal.data( modal_position_scheduled_data_key )) {
		return;
	}

	$modal.data( modal_position_scheduled_data_key, true );

	ai4seo_schedule_next_animation_frame(
		function () {
			$modal.removeData( modal_position_scheduled_data_key );

			const modal_element = $modal.get( 0 );
			const modal_document = modal_element ? modal_element.ownerDocument : null;

			// Ignore image events that finish after their modal has already closed.
			if (!modal_element || !modal_document || !jQuery.contains( modal_document, modal_element )) {
				return;
			}

			ai4seo_position_modal( $modal );
		}
	);
}

// =========================================================================================== \\

/**
 * Position a modal after its current content has been rendered.
 *
 * @param {jQuery|HTMLElement} modal
 */
function ai4seo_position_modal(modal) {
	if (!ai4seo_exists_$( modal )) {
		return;
	}

	const $modal = ai4seo_normalize_$( modal );

	// Vertically center short modals while keeping tall content accessible from the top.
	if ($modal.outerHeight() < jQuery( window ).height() * 0.80) {
		$modal.css(
			{
				'top': '50%',
				'margin-top': -$modal.outerHeight() / 2 - 50, // 50px buffer
			}
		);
	} else {
		$modal.css(
			{
				'top': '3rem',
				'margin-top': 0,
			}
		);
	}
}

// =========================================================================================== \\

/**
 * Reposition a modal after any image that was pending during initialization finishes loading.
 *
 * @param {jQuery|HTMLElement} modal
 */
function ai4seo_init_modal_image_position_updates(modal) {
	if (!ai4seo_exists_$( modal )) {
		return;
	}

	const $modal = ai4seo_normalize_$( modal );

	$modal.find( 'img' ).each(
		function () {
			const $image = ai4seo_normalize_$( this );
			const image_element = $image.get( 0 );

			if (!image_element) {
				return;
			}

			$image.off( '.ai4seo-modal-image-position' );

			// The scheduled initial position already includes dimensions of cached or failed images.
			if (image_element.complete) {
				return;
			}

			$image.one(
				'load.ai4seo-modal-image-position error.ai4seo-modal-image-position',
				function () {
					$image.off( '.ai4seo-modal-image-position' );
					ai4seo_schedule_modal_position( $modal );
				}
			);
		}
	);
}

// =========================================================================================== \\

/**
 * Synchronize a modal's dialog semantics and accessible name.
 *
 * @param {jQuery} $modal
 */
function ai4seo_sync_modal_accessibility($modal) {
	$modal = ai4seo_normalize_$( $modal );

	if (!ai4seo_exists_$( $modal )) {
		return;
	}

	// The reusable modal shell must itself be focusable when it contains no interactive controls.
	$modal.attr( 'role', 'dialog' );
	$modal.attr( 'tabindex', '-1' );

	// Prefer a visible modal headline as the stable accessible-name source.
	const modal_id = $modal.attr( 'id' );
	const $modal_headline = $modal.children( '.ai4seo-modal-headline' ).first();

	if (modal_id && ai4seo_exists_$( $modal_headline )) {
		const modal_headline_id = modal_id + '-headline';

		// The branded logo is decorative here; hiding it keeps the dialog name limited to the visible heading text.
		$modal_headline.find( '.ai4seo-sooz-logo' )
			.attr( 'aria-hidden', 'true' )
			.removeAttr( 'aria-label' );

		$modal_headline.attr( 'id', modal_headline_id );
		$modal.attr( 'aria-labelledby', modal_headline_id );
		$modal.removeAttr( 'aria-label' );
	} else {
		// Headline-free dialogs inherit the opener label before falling back to the plugin name.
		const modal_opener = $modal.data( 'ai4seo-modal-opener' ) || null;
		const $modal_opener = ai4seo_normalize_$( modal_opener );
		let modal_accessible_label = ai4seo_get_plugin_name();

		if (ai4seo_exists_$( $modal_opener )) {
			modal_accessible_label = $modal_opener.attr( 'aria-label' )
				|| $modal_opener.text().trim()
				|| $modal_opener.attr( 'title' )
				|| modal_accessible_label;
		}

		$modal.removeAttr( 'aria-labelledby' );
		$modal.attr( 'aria-label', modal_accessible_label );
	}
}

// =========================================================================================== \\

/**
 * Expose only the top-most modal as active to assistive technology.
 */
function ai4seo_sync_modal_stack_accessibility() {
	// Compare every open dialog with the same top-most modal used by keyboard handling.
	const $modals = ai4seo_normalize_$( '.ai4seo-modal' );
	const $active_modal = ai4seo_get_active_modal_$();

	if (!ai4seo_exists_$( $modals ) || !ai4seo_exists_$( $active_modal )) {
		return;
	}

	const active_modal_element = $active_modal.get( 0 );

	$modals.each(
        function () {
		const $this_modal = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_modal )) {
			return;
		}

		if ($this_modal.get( 0 ) === active_modal_element) {
			$this_modal.attr( 'aria-modal', 'true' );
			$this_modal.removeAttr( 'aria-hidden' );
		} else {
			$this_modal.removeAttr( 'aria-modal' );
			$this_modal.attr( 'aria-hidden', 'true' );
		}
        }
    );
}

// =========================================================================================== \\

/**
 * Return the visible, enabled controls that can receive focus inside a modal.
 *
 * @param {jQuery} $modal
 * @returns {jQuery}
 */
function ai4seo_get_modal_focusable_elements_$($modal) {
	$modal = ai4seo_normalize_$( $modal );

	if (!ai4seo_exists_$( $modal )) {
		return jQuery();
	}

	// Keep selector coverage aligned with native controls plus explicit custom focus targets.
	const focusable_selector = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled]):not([type="hidden"])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[contenteditable="true"]',
		'[tabindex]:not([tabindex="-1"])',
	].join( ', ' );

	return $modal.find( focusable_selector ).filter(
        function () {
		const $element = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $element )) {
			return false;
		}

		return $element.is( ':visible' )
			&& !$element.prop( 'disabled' )
			&& $element.attr( 'aria-disabled' ) !== 'true'
			&& !ai4seo_exists_$( $element.closest( '[inert]' ) );
        }
    );
}

// =========================================================================================== \\

/**
 * Move focus into a modal unless it already contains the active element.
 *
 * @param {jQuery} $modal
 */
function ai4seo_focus_modal($modal) {
	$modal = ai4seo_normalize_$( $modal );

	if (!ai4seo_exists_$( $modal )) {
		return;
	}

	const modal_element = $modal.get( 0 );
	const active_element = document.activeElement;

	if (modal_element && active_element
		&& (active_element === modal_element || jQuery.contains( modal_element, active_element ))) {
		return;
	}

	// Respect explicit autofocus, then the first available control, then the dialog shell.
	const $focusable_elements = ai4seo_get_modal_focusable_elements_$( $modal );
	const $autofocus_element = $focusable_elements.filter( '[autofocus]' ).first();

	if (ai4seo_exists_$( $autofocus_element )) {
		$autofocus_element.trigger( 'focus' );
	} else if (ai4seo_exists_$( $focusable_elements )) {
		$focusable_elements.first().trigger( 'focus' );
	} else {
		$modal.trigger( 'focus' );
	}
}

// =========================================================================================== \\

/**
 * Keep Tab and Shift+Tab navigation inside the active modal.
 *
 * @param {KeyboardEvent|jQuery.Event} event
 * @param {jQuery} $active_modal
 */
function ai4seo_keep_focus_in_modal(event, $active_modal) {
	$active_modal = ai4seo_normalize_$( $active_modal );

	if (!event || !ai4seo_exists_$( $active_modal )) {
		return;
	}

	const $focusable_elements = ai4seo_get_modal_focusable_elements_$( $active_modal );

	// A control-free modal traps focus on its dialog shell.
	if (!ai4seo_exists_$( $focusable_elements )) {
		event.preventDefault();
		event.stopPropagation();
		$active_modal.trigger( 'focus' );
		return;
	}

	const first_focusable_element = $focusable_elements.first().get( 0 );
	const last_focusable_element = $focusable_elements.last().get( 0 );
	const target_element = event.target;
	const target_index = $focusable_elements.index( target_element );

	// Focus that entered from outside restarts at the first valid modal control.
	if (target_index < 0) {
		event.preventDefault();
		event.stopPropagation();
		$focusable_elements.first().trigger( 'focus' );
		return;
	}

	if (event.shiftKey && target_element === first_focusable_element) {
		event.preventDefault();
		event.stopPropagation();
		$focusable_elements.last().trigger( 'focus' );
	} else if (!event.shiftKey && target_element === last_focusable_element) {
		event.preventDefault();
		event.stopPropagation();
		$focusable_elements.first().trigger( 'focus' );
	}
}

// =========================================================================================== \\

/**
 * Restore focus after a modal closes, respecting any remaining stacked modal.
 *
 * @param {Element|null} modal_opener
 */
function ai4seo_restore_modal_focus(modal_opener) {
	const $active_modal = ai4seo_get_active_modal_$();
	const $modal_opener = ai4seo_normalize_$( modal_opener );
	const opener_element = ai4seo_exists_$( $modal_opener ) ? $modal_opener.get( 0 ) : null;

	// Detached, hidden, body, and disabled openers cannot receive meaningful restored focus.
	const opener_is_available = opener_element
		&& opener_element !== document.body
		&& jQuery.contains( document, opener_element )
		&& $modal_opener.is( ':visible' )
		&& !$modal_opener.prop( 'disabled' );

	if (ai4seo_exists_$( $active_modal )) {
		// A remaining stacked dialog takes precedence over an opener outside that dialog.
		const active_modal_element = $active_modal.get( 0 );
		const active_element = document.activeElement;

		if (opener_is_available && active_modal_element
			&& (opener_element === active_modal_element || jQuery.contains( active_modal_element, opener_element ))) {
			$modal_opener.trigger( 'focus' );
			return;
		}

		if (active_modal_element && active_element
			&& (active_element === active_modal_element || jQuery.contains( active_modal_element, active_element ))) {
			return;
		}

		ai4seo_focus_modal( $active_modal );
		return;
	}

	if (opener_is_available) {
		$modal_opener.trigger( 'focus' );
	}
}

// =========================================================================================== \\

/**
 * Bind keyboard shortcuts while a SOOZ modal is open.
 * Trap focus in the top-most modal, close it with Escape, and trigger its primary action with Enter.
 */
function ai4seo_init_modal_keyboard_shortcuts() {
	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.error( ai4seo_get_plugin_name() + ': document object missing in ai4seo_init_modal_keyboard_shortcuts() - cannot bind keyboard shortcuts.' );
		return;
	}

	if (!ai4seo_exists_$( ai4seo_get_active_modal_$() )) {
		ai4seo_destroy_modal_keyboard_shortcuts();
		return;
	}

	$document
		.off( 'keydown.ai4seo-modal-shortcuts' )
		.on(
            'keydown.ai4seo-modal-shortcuts',
            function (event) {
			if (ai4seo_is_keyboard_event_default_prevented( event )) {
				return;
			}

			const $active_modal = ai4seo_get_active_modal_$();

			if (!ai4seo_exists_$( $active_modal )) {
				ai4seo_destroy_modal_keyboard_shortcuts();
				return;
			}

			// Tab navigation stays inside the same top-most modal used by Escape and Enter.
			if (event.key === 'Tab' || event.keyCode === 9) {
				ai4seo_keep_focus_in_modal( event, $active_modal );
				return;
			}

			// Escape should never bubble into other handlers once we decide to close a modal.
			if (event.key === 'Escape' || event.key === 'Esc' || event.keyCode === 27) {
				event.preventDefault();
				event.stopPropagation();
				ai4seo_close_active_modal();
				return;
			}

			if (event.key === 'Enter' || event.keyCode === 13) {
				// Preserve native Enter behavior for editing, controls, and non-modal contexts.
				if (ai4seo_should_ignore_modal_enter_shortcut( event, $active_modal )) {
					return;
				}

				// Consume the original Enter event so it cannot also activate the underlying trigger.
				event.preventDefault();
				event.stopPropagation();
				ai4seo_press_active_primary_button( $active_modal );
				return;
			}
            }
        );
}

// =========================================================================================== \\

/**
 * Remove modal keyboard shortcuts when no SOOZ modal is open.
 */
function ai4seo_destroy_modal_keyboard_shortcuts() {
	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		return;
	}

	$document.off( 'keydown.ai4seo-modal-shortcuts' );
}

// =========================================================================================== \\

/**
 * Check whether a keyboard event has already been handled elsewhere.
 *
 * @param {KeyboardEvent|jQuery.Event} event
 * @returns {boolean}
 */
function ai4seo_is_keyboard_event_default_prevented(event) {
	if (!event) {
		return false;
	}

	if (typeof event.isDefaultPrevented === 'function' && event.isDefaultPrevented()) {
		return true;
	}

	if (event.defaultPrevented) {
		return true;
	}

	return !!(event.originalEvent && event.originalEvent.defaultPrevented);
}

// =========================================================================================== \\

/**
 * Ignore the Enter shortcut while the user is actively editing multiline or rich-text fields.
 *
 * @param {KeyboardEvent} event
 * @param {jQuery|null} $active_modal
 * @returns {boolean}
 */
function ai4seo_should_ignore_modal_enter_shortcut(event, $active_modal = null) {
	if (!event || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
		return true;
	}

	const $target = ai4seo_normalize_$( event.target );

	if (!ai4seo_exists_$( $target )) {
		return true;
	}

	$active_modal = ai4seo_normalize_$( $active_modal );

	if (!ai4seo_exists_$( $active_modal )) {
		return true;
	}

	const target_element = $target.get( 0 );
	const active_modal_element = $active_modal.get( 0 );
	const is_document_or_body_target = (target_element === document || target_element === document.body);

	if (!is_document_or_body_target
		&& (!active_modal_element || target_element !== active_modal_element && !jQuery.contains( active_modal_element, target_element ))) {
		return true;
	}

	const ignored_enter_targets = [
		'input',
		'textarea',
		'select',
		'button',
		'a[href]',
		'[role="button"]',
		'[role="link"]',
		'[role="textbox"]',
		'[contenteditable]',
		'.block-editor-rich-text__editable',
		'.rich-text',
		'.components-text-control__input',
		'.components-textarea-control__input',
		'.editor-post-title',
		'.wp-block-post-title',
		'.wp-block-paragraph',
	].join( ', ' );

	if ($target.is( ignored_enter_targets )) {
		return true;
	}

	if (ai4seo_exists_$( $target.closest( ignored_enter_targets ) )) {
		return true;
	}

	// Rich text editors often render nested editable elements instead of plain inputs.
	return !!$target.prop( 'isContentEditable' );
}

// =========================================================================================== \\

function ai4seo_create_empty_modal_$(modal_id, modal_css_class, modal_wrapper_css_class, modal_size) {
	// get highest z-index of all modal wrappers.
	let previous_highest_z_index = ai4seo_get_highest_modal_wrapper_z_index();

	// Store the focused opener before creating stacked modal markup so close can restore it reliably.
	const modal_opener = document.activeElement;

	// add modal css class.
	if (modal_css_class) {
		modal_css_class = 'ai4seo-modal ' + modal_css_class;
	} else {
		modal_css_class = 'ai4seo-modal';
	}

	// add modal wrapper css class.
	if (modal_wrapper_css_class) {
		modal_wrapper_css_class = 'ai4seo-modal-wrapper ' + modal_wrapper_css_class;
	} else {
		modal_wrapper_css_class = 'ai4seo-modal-wrapper';
	}

	if (modal_size === 'small') {
		modal_css_class += ' ai4seo-modal-small-size';
	} else if (modal_size === 'medium') {
		modal_css_class += ' ai4seo-modal-medium-size';
	} else if (modal_size === 'large') {
		modal_css_class += ' ai4seo-modal-large-size';
	} else {
		modal_css_class += ' ai4seo-modal-auto-size';
	}

	// add empty modal wrapper and modal to the footer of the body
	// AND disable scroll on body-element.
	const $body = ai4seo_normalize_$( 'body' );

	if (ai4seo_exists_$( $body )) {
		$body
			.append( "<div class='" + modal_wrapper_css_class + "'><div class='" + modal_css_class + "' id='" + modal_id + "'></div></div>" )
			.addClass( 'ai4seo-has-open-modal' );
	}

	// check for the modal tags.
	let $modal = ai4seo_get_modal_$( modal_id );

	if (!$modal) {
		console.error( ai4seo_get_plugin_name() + ': Could not create modal with id: ' + modal_id );
		return;
	}

	let $modal_wrapper = ai4seo_get_modal_wrapper_$( modal_id );

	if (!$modal_wrapper) {
		console.error( ai4seo_get_plugin_name() + ': Could not create modal wrapper for modal with id: ' + modal_id );
		return;
	}

	// Attach opener and dialog semantics to the reusable shell before caller-specific content is added.
	$modal.data( 'ai4seo-modal-opener', modal_opener );
	ai4seo_sync_modal_accessibility( $modal );

	// Workaround: add stop propagation to modal to prevent closing when clicking inside the modal.
	$modal
		.off( 'mouseup.ai4seo-modal' )
		.on(
            'mouseup.ai4seo-modal',
            function (event) {
			event.stopPropagation();
            }
        );

	$modal.click(
        function (event) {
		event.stopPropagation();
        }
    );

	// Workaround: if there was a highest z index, add 1 to it.
	if (previous_highest_z_index) {
		previous_highest_z_index++;

		$modal_wrapper.css( 'z-index', previous_highest_z_index );
	}

	// Only the newly top-most modal should remain exposed to assistive technology.
	ai4seo_sync_modal_stack_accessibility();
	ai4seo_init_modal_keyboard_shortcuts();

	return $modal;
}

// =========================================================================================== \\

function ai4seo_get_highest_modal_wrapper_z_index() {
	let highest_z_index = 0;

	const $modal_wrappers = ai4seo_normalize_$( '.ai4seo-modal-wrapper' );

	if (!ai4seo_exists_$( $modal_wrappers )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': elements \"$modal_wrappers\" missing in ai4seo_get_highest_modal_wrapper_z_index() \u2014 cannot resolve modal stacking context.' );
		return highest_z_index;
	}

	$modal_wrappers.each(
        function () {
		const $this_modal_wrapper = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_modal_wrapper )) {
			console.warn( ai4seo_get_plugin_name() + ': element \"$this_modal_wrapper\" missing in ai4seo_get_highest_modal_wrapper_z_index() \u2014 cannot resolve modal stacking context.' );
			return;
		}

		let z_index = $this_modal_wrapper.css( 'z-index' );

		if (z_index > highest_z_index) {
			highest_z_index = z_index;
		}
        }
    );

	return highest_z_index;
}

// =========================================================================================== \\

function ai4seo_get_modal_wrapper_$(modal_id) {
	const $modal = ai4seo_normalize_$( '#' + modal_id );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$modal\" missing in ai4seo_get_modal_wrapper_$() \u2014 cannot resolve modal wrapper.' );
		return null;
	}

	return $modal.parent( '.ai4seo-modal-wrapper' );
}

// =========================================================================================== \\

function ai4seo_get_modal_$(modal_id) {
	if (ai4seo_exists_$( '#' + modal_id )) {
		return ai4seo_normalize_$( '#' + modal_id );
	} else {
		// return empty jQuery object.
		return null;
	}
}

// =========================================================================================== \\

/**
 * Resolve the currently active modal by wrapper stacking order.
 *
 * @returns {jQuery|null}
 */
function ai4seo_get_active_modal_$() {
	const $modal_wrappers = ai4seo_normalize_$( '.ai4seo-modal-wrapper' );
	let $active_modal = null;
	let highest_z_index = -1;

	if (!ai4seo_exists_$( $modal_wrappers )) {
		return null;
	}

	$modal_wrappers.each(
        function () {
		const $this_modal_wrapper = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_modal_wrapper )) {
			return;
		}

		const $this_modal = $this_modal_wrapper.children( '.ai4seo-modal' ).last();

		if (!ai4seo_exists_$( $this_modal )) {
			return;
		}

		let this_z_index = parseInt( $this_modal_wrapper.css( 'z-index' ), 10 );

		// Wrappers without an explicit z-index should still be considered, but below stacked modals.
		if (isNaN( this_z_index )) {
			this_z_index = 0;
		}

		// Use the top-most wrapper so nested confirmation modals win over their parent modal.
		if (this_z_index >= highest_z_index) {
			highest_z_index = this_z_index;
			$active_modal = $this_modal;
		}
        }
    );

	return $active_modal;
}

// =========================================================================================== \\

/**
 * Close the current top-most modal through the standard close lifecycle.
 */
function ai4seo_close_active_modal() {
	const $active_modal = ai4seo_get_active_modal_$();

	if (!ai4seo_exists_$( $active_modal )) {
		return;
	}

	const modal_id = $active_modal.attr( 'id' );

	// Reuse the regular modal close flow so unsaved-changes checks still apply.
	if (!modal_id) {
		console.warn( ai4seo_get_plugin_name() + ': active modal without id found in ai4seo_close_active_modal() - cannot close modal.' );
		return;
	}

	ai4seo_close_modal( modal_id );
}

// =========================================================================================== \\

/**
 * Trigger the first enabled primary action in the active modal.
 *
 * @param {jQuery|null} $active_modal
 */
function ai4seo_press_active_primary_button($active_modal = null) {
	$active_modal = ai4seo_normalize_$( $active_modal );

	if (!ai4seo_exists_$( $active_modal )) {
		$active_modal = ai4seo_get_active_modal_$();
	}

	const $primary_button = ai4seo_get_primary_button_in_container( $active_modal );

	if (!ai4seo_exists_$( $primary_button )) {
		return;
	}

	if ($primary_button.prop( 'disabled' )) {
		return;
	}

	// Trigger click so existing button-specific handlers run unchanged.
	$primary_button.trigger( 'click' );
}

// =========================================================================================== \\

/**
 * Find the first visible enabled primary action button within a container.
 *
 * @param {*} $container
 * @returns {jQuery|null}
 */
function ai4seo_get_primary_button_in_container($container) {
	$container = ai4seo_normalize_$( $container );

	if (!ai4seo_exists_$( $container )) {
		return null;
	}

	const $visible_buttons = $container.find( '.ai4seo-primary-button:visible, .ai4seo-submit-button:visible' );

	if (!ai4seo_exists_$( $visible_buttons )) {
		return null;
	}

	const $enabled_buttons = $visible_buttons.filter(
        function () {
		const $button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $button )) {
			return false;
		}

		// Skip visually inactive buttons that are still present in the DOM.
		return !$button.prop( 'disabled' ) && !$button.hasClass( 'ai4seo-inactive-button' );
        }
    );

	if (!ai4seo_exists_$( $enabled_buttons )) {
		return null;
	}

	return $enabled_buttons.first();
}

// =========================================================================================== \\

/**
 * Check if a modal contains unsaved changes and optionally warn the user before closing.
 *
 * @param {jQuery} $modal
 * @returns {boolean} true if closing should proceed, false if it should be aborted
 */
function ai4seo_should_close_modal($modal) {
	$modal = ai4seo_normalize_$( $modal );

	if (!ai4seo_exists_$( $modal )) {
		console.warn( ai4seo_get_plugin_name() + ': modal missing in ai4seo_should_close_modal() — allowing close to proceed.' );
		return true;
	}

	const $container = ai4seo_get_unsaved_changes_container( $modal );

	if (!ai4seo_exists_$( $container )) {
		return true;
	}

	let has_unsaved_changes = false;

	if (ai4seo_container_has_unsaved_changes( $container )) {
		has_unsaved_changes = true;
	}

	if (!has_unsaved_changes) {
		return true;
	}

	return window.confirm( wp.i18n.__( 'You have unsaved changes. Are you sure you want to leave this page?', 'ai-for-seo' ) );
}

// =========================================================================================== \\

function ai4seo_close_modal(modal_id) {
	const $modal = ai4seo_get_modal_$( modal_id );

	// Treat missing modal elements as a failed close so callers do not continue modal replacement flows.
	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$modal\" missing in ai4seo_close_modal() \u2014 modal lifecycle interrupted.' );
		return false;
	}

	// Respect unsaved-change guards before removing DOM or restoring schema-backed modal content.
	if (!ai4seo_should_close_modal( $modal )) {
		return false;
	}

	// Capture the opener before removing the modal and its stored jQuery data.
	const modal_opener = $modal.data( 'ai4seo-modal-opener' ) || null;

	// check for modal-schema-identifier data -> put data back to schema.
	if ($modal.data( 'ai4seo-modal-schema-identifier' )) {
		let modal_schema_identifier = $modal.data( 'ai4seo-modal-schema-identifier' );

		// put back headline, content and footer to the schema.
		if (ai4seo_exists_$( '.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier )) {
			let $modal_schema = ai4seo_normalize_$( '.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier );

			// find headline.
			if (ai4seo_exists_$( $modal.find( '.ai4seo-modal-headline' ) ) && ai4seo_exists_$( $modal_schema.find( '.ai4seo-modal-schema-headline' ) )) {
				$modal_schema.find( '.ai4seo-modal-schema-headline' ).html( $modal.find( '.ai4seo-modal-headline' ).html() );
			}

			// find content.
			if (ai4seo_exists_$( $modal.find( '.ai4seo-modal-content' ) ) && ai4seo_exists_$( $modal_schema.find( '.ai4seo-modal-schema-content' ) )) {
				$modal_schema.find( '.ai4seo-modal-schema-content' ).html( $modal.find( '.ai4seo-modal-content' ).html() );
			}

			// find footer.
			if (ai4seo_exists_$( $modal.find( '.ai4seo-modal-footer' ) ) && ai4seo_exists_$( $modal_schema.find( '.ai4seo-modal-schema-footer' ) )) {
				$modal_schema.find( '.ai4seo-modal-schema-footer' ).html( $modal.find( '.ai4seo-modal-footer' ).html() );
			}
		}
	}

	// Remove the wrapper once close is allowed so callers can tell whether the modal really went away.
	const $modal_wrapper = ai4seo_get_modal_wrapper_$( modal_id );

	if (ai4seo_exists_$( $modal_wrapper )) {
		// Disconnect modal-owned observers before jQuery removes their workspace elements and data.
		ai4seo_destroy_editor_previews( $modal_wrapper );
		$modal_wrapper.remove();
	} else {
		return false;
	}

	// no more ai4seo-modal -> enable scroll on body-element.
	const $modals = ai4seo_normalize_$( '.ai4seo-modal' );

	if (!ai4seo_exists_$( $modals )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': No modals open in ai4seo_close_modal() — clearing body modal state.' );
		const $body = ai4seo_normalize_$( 'body' );

		if (ai4seo_exists_$( $body )) {
			$body.removeClass( 'ai4seo-has-open-modal' );
		}

		ai4seo_destroy_modal_keyboard_shortcuts();
	} else {
		// Closing a stacked modal promotes the remaining top-most dialog for assistive technology.
		ai4seo_sync_modal_stack_accessibility();
	}

	// Return focus to the opener when possible, or into the remaining stacked modal.
	ai4seo_restore_modal_focus( modal_opener );

	return true;
}

// =========================================================================================== \\

function ai4seo_close_modal_by_child($child) {
	$child = ai4seo_normalize_$( $child );

	if (!ai4seo_exists_$( $child )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$child\" missing in ai4seo_close_modal_by_child() \u2014 cannot locate parent modal.' );
		return;
	}

	// is modal_id a reference element like a button? find the modal_id.
	const $closest_modal = $child.closest( '.ai4seo-modal' );

	if (!ai4seo_exists_$( $closest_modal )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$closest_modal\" missing in ai4seo_close_modal_by_child() \u2014 cannot locate parent modal.' );
		return;
	}

	let modal_id = $closest_modal.attr( 'id' );

	ai4seo_close_modal( modal_id );
}

// =========================================================================================== \\

function ai4seo_close_all_modals() {
	const $modals = ai4seo_normalize_$( '.ai4seo-modal' );

	if (!ai4seo_exists_$( $modals )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': no modals are open or \"$modals\" missing in ai4seo_close_all_modals() \u2014 no modals to close.' );
		return;
	}

	$modals.each(
        function () {
		const $this_modal = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_modal )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$this_modal\" missing in ai4seo_close_all_modals() \u2014 modal lifecycle interrupted.' );
			return;
		}

		let modal_id = $this_modal.attr( 'id' );

		ai4seo_close_modal( modal_id );
        }
    );
}

// =========================================================================================== \\

function ai4seo_open_metadata_editor_modal(post_id = false, read_page_content_via_js = false, all_post_ids = []) {
	// Read post-id from hidden container if not defined.
	if (!post_id) {
		post_id = ai4seo_get_post_id( 'metadata' );
	}

	if (!post_id) {
		ai4seo_show_generic_error_toast( 26173424 );
		return;
	}

	// CURRENT POST'S CONTENT.
	let post_content = '';

	// Define variable for the content based on ai4seo_get_post_content().
	if (read_page_content_via_js) {
		post_content = ai4seo_get_post_content();
	}

	const parameters = {
		post_id: post_id,
		read_page_content_via_js: read_page_content_via_js,
		content: post_content,
		all_post_ids: all_post_ids,
	};

	// Pass the current Yoast editor snapshot so the modal can overlay unsaved synchronized fields.
	const live_yoast_metadata = ai4seo_read_live_yoast_metadata( post_id );

	if (Object.keys( live_yoast_metadata ).length > 0) {
		parameters.live_yoast_metadata = live_yoast_metadata;
	}

	ai4seo_open_ajax_modal(
        'ai4seo_show_metadata_editor',
        parameters,
        {
		modal_id: 'ai4seo-metadata-editor-modal',
		modal_size: 'large',
		modal_css_class: 'ai4seo-ajax-modal ai4seo-editor-workspace-modal',
		unsaved_changes_warnings: true,
        }
    );
}

// =========================================================================================== \\

function ai4seo_open_attachment_attributes_editor_modal(attachment_post_id = false, all_attachment_post_ids = []) {
	// Read post-id from hidden container if not defined.
	if (!attachment_post_id) {
		ai4seo_open_notification_modal( 241920824 );
		return;
	}

	// PARAMETERS.
	let parameters = {
		attachment_post_id: attachment_post_id,
		all_attachment_post_ids: all_attachment_post_ids,
	}

	ai4seo_open_ajax_modal(
        'ai4seo_show_attachment_attributes_editor',
        parameters,
        {
		modal_id: 'ai4seo-attachment-attributes-editor-modal',
		modal_size: 'large',
		modal_css_class: 'ai4seo-ajax-modal ai4seo-editor-workspace-modal',
		unsaved_changes_warnings: true,
        }
    );
}

// =========================================================================================== \\

/**
 * Open the AJAX modal that renders attachment.php scoped to media related to one post.
 *
 * @param {number|boolean} post_id
 * @param {Object} filter_data
 */
function ai4seo_open_related_attachments_modal(post_id = false, filter_data = {}) {
	// Metadata-editor buttons can omit the ID because the editor already exposes the active post context.
	if (!post_id) {
		post_id = ai4seo_get_post_id( 'metadata' );
	}

	if (!post_id) {
		ai4seo_show_generic_error_toast( 12032601 );
		return;
	}

	// Keep filters explicit so reloading the modal never leaks unrelated query-string state.
	let parameters = ai4seo_get_related_attachments_modal_filter_parameters( filter_data );
	parameters.post_id = post_id;

	ai4seo_open_ajax_modal(
        'ai4seo_show_related_attachments',
        parameters,
        {
		modal_id: 'ai4seo-related-attachments-modal',
		modal_size: 'large',
        }
    );
}

// =========================================================================================== \\

/**
 * Find Related Media modal content from an embedded control or from the currently open stacked modal.
 *
 * @param {HTMLElement|jQuery|null} scope
 * @returns {jQuery}
 */
function ai4seo_get_related_attachments_modal_content_$(scope = null) {
	const $scope = ai4seo_normalize_$( scope );

	if (ai4seo_exists_$( $scope )) {
		// Prefer the nearest wrapper so embedded table actions refresh the modal they belong to.
		return $scope.closest( '.ai4seo-related-attachments-modal-content' );
	}

	// Deeper stacked modals do not contain the Related Media wrapper, so use the open modal content.
	return ai4seo_normalize_$( '.ai4seo-related-attachments-modal-content' ).first();
}

// =========================================================================================== \\

/**
 * Bind filter, sorting, pagination, and reset controls inside related-media modals.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_related_attachments_modal_controls(scope = null) {
	const $related_attachments_modal_contents = ai4seo_get_elements_in_scope_$( '.ai4seo-related-attachments-modal-content', scope );

	if (!ai4seo_exists_$( $related_attachments_modal_contents )) {
		return;
	}

	$related_attachments_modal_contents.each(
        function () {
		const $related_attachments_modal_content = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $related_attachments_modal_content )) {
			return;
		}

		// attachment.php emits full-page onclick handlers; the modal replaces them with AJAX reloads.
		$related_attachments_modal_content
			.find( '.ai4seo-content-list-search-form [onclick], .ai4seo-content-list-controls a[onclick], .ai4seo-pagination a[onclick], .ai4seo-abort-button[href][onclick]' )
			.removeAttr( 'onclick' );

		// Search submissions should refresh the modal body instead of navigating the admin page.
		$related_attachments_modal_content.off( 'submit.ai4seo-related-attachments-modal', '.ai4seo-content-list-search-form' );
		$related_attachments_modal_content.on(
            'submit.ai4seo-related-attachments-modal',
            '.ai4seo-content-list-search-form',
            function (event) {
			event.preventDefault();
			ai4seo_reload_related_attachments_modal_from_form( $related_attachments_modal_content, this );
			return false;
            }
        );

		// Reset buttons can be nested inside reset links, so use the link URL before generic form-button handling.
		$related_attachments_modal_content.off( 'click.ai4seo-related-attachments-modal', '.ai4seo-content-list-search-reset-button' );
		$related_attachments_modal_content.on(
            'click.ai4seo-related-attachments-modal',
            '.ai4seo-content-list-search-reset-button',
            function (event) {
			const href = ai4seo_get_related_attachments_modal_filter_url_from_element( this );

			event.preventDefault();
			event.stopImmediatePropagation();
			ai4seo_reload_related_attachments_modal_from_url( $related_attachments_modal_content, href );
			return false;
            }
        );

		// Search button clicks are handled separately so mouse interaction uses the same AJAX path as Enter.
		$related_attachments_modal_content.off( 'click.ai4seo-related-attachments-modal', '.ai4seo-content-list-search-form button' );
		$related_attachments_modal_content.on(
            'click.ai4seo-related-attachments-modal',
            '.ai4seo-content-list-search-form button',
            function (event) {
			const $button = ai4seo_normalize_$( this );

			if ($button.hasClass( 'ai4seo-content-list-search-reset-button' ) || ai4seo_exists_$( $button.closest( '.ai4seo-content-list-search-reset-button' ) )) {
				return true;
			}

			event.preventDefault();
			ai4seo_reload_related_attachments_modal_from_form( $related_attachments_modal_content, $button.closest( 'form' ) );
			return false;
            }
        );

		// Status filters, sortable headers, pagination, and reset links all map back to modal AJAX parameters.
		$related_attachments_modal_content.off( 'click.ai4seo-related-attachments-modal', '.ai4seo-content-list-controls a[href], .ai4seo-content-list-sortable-column a[href], .ai4seo-pagination a[href], .ai4seo-abort-button[href]' );
		$related_attachments_modal_content.on(
            'click.ai4seo-related-attachments-modal',
            '.ai4seo-content-list-controls a[href], .ai4seo-content-list-sortable-column a[href], .ai4seo-pagination a[href], .ai4seo-abort-button[href]',
            function (event) {
			const href = String( ai4seo_normalize_$( this ).attr( 'href' ) || '' );

			if (!ai4seo_is_related_attachments_modal_filter_url( href )) {
				return true;
			}

			event.preventDefault();
			ai4seo_reload_related_attachments_modal_from_url( $related_attachments_modal_content, href );
			return false;
            }
        );
        }
    );
}

// =========================================================================================== \\

/**
 * Return the shared media-table query parameters that are safe to keep during modal reloads.
 *
 * @returns {string[]}
 */
function ai4seo_get_related_attachments_modal_filter_parameter_names() {
	return [
		'ai4seo_filter_text',
		'ai4seo_filter_status',
		'ai4seo_filter_language',
		'ai4seo_content_type_filter_nonce',
		'orderby',
		'order',
		'ai4seo_page',
		'lang',
	];
}

// =========================================================================================== \\

/**
 * Extract allowed related-media modal parameters from arbitrary filter data.
 *
 * @param {Object} filter_data
 * @returns {Object}
 */
function ai4seo_get_related_attachments_modal_filter_parameters(filter_data = {}) {
	const filter_parameters = {};
	const allowed_filter_parameter_names = ai4seo_get_related_attachments_modal_filter_parameter_names();

	if (!filter_data || typeof filter_data !== 'object') {
		return filter_parameters;
	}

	// Copy only known table filter keys so callers cannot inject unrelated AJAX parameters.
	for (const filter_parameter_name of allowed_filter_parameter_names) {
		if (!Object.prototype.hasOwnProperty.call( filter_data, filter_parameter_name )) {
			continue;
		}

		const filter_parameter_value = filter_data[filter_parameter_name];

		if (filter_parameter_value === null || typeof filter_parameter_value === 'undefined') {
			continue;
		}

		filter_parameters[filter_parameter_name] = String( filter_parameter_value );
	}

	return filter_parameters;
}

// =========================================================================================== \\

/**
 * Read a media-table filter URL from an element or its wrapping link.
 *
 * @param {HTMLElement|jQuery} element
 * @returns {string}
 */
function ai4seo_get_related_attachments_modal_filter_url_from_element(element) {
	const $element = ai4seo_normalize_$( element );

	if (!ai4seo_exists_$( $element )) {
		return '';
	}

	// Some icon buttons are rendered inside links, so check the current element before its parents.
	const own_href = String( $element.attr( 'href' ) || '' );

	if (own_href !== '') {
		return own_href;
	}

	// Reuse the wrapping link URL so nested reset buttons keep the same filter target as full-page links.
	const $parent_link = $element.closest( 'a[href]' );

	if (!ai4seo_exists_$( $parent_link )) {
		return '';
	}

	return String( $parent_link.attr( 'href' ) || '' );
}

// =========================================================================================== \\

/**
 * Extract allowed related-media modal filter parameters from a table link URL.
 *
 * @param {string} url
 * @returns {Object}
 */
function ai4seo_get_related_attachments_modal_filter_parameters_from_url(url) {
	const filter_parameters = {};

	try {
		const parsed_url = new URL( url, window.location.href );
		const allowed_filter_parameter_names = ai4seo_get_related_attachments_modal_filter_parameter_names();

		// Preserve empty parameter values because existing full-page filters also include them.
		for (const filter_parameter_name of allowed_filter_parameter_names) {
			if (parsed_url.searchParams.has( filter_parameter_name )) {
				filter_parameters[filter_parameter_name] = parsed_url.searchParams.get( filter_parameter_name );
			}
		}
	} catch (error) {
		return filter_parameters;
	}

	return filter_parameters;
}

// =========================================================================================== \\

/**
 * Extract allowed related-media modal filter parameters from the embedded media search form.
 *
 * @param {HTMLElement|jQuery} form
 * @returns {Object}
 */
function ai4seo_get_related_attachments_modal_filter_parameters_from_form(form) {
	const filter_parameters = {};
	const $form = ai4seo_normalize_$( form );

	if (!ai4seo_exists_$( $form ) || !$form.get( 0 )) {
		return filter_parameters;
	}

	const allowed_filter_parameter_names = ai4seo_get_related_attachments_modal_filter_parameter_names();
	const form_data = new FormData( $form.get( 0 ) );

	// FormData keeps hidden fields from attachment.php, including the source post ID and current filters.
	form_data.forEach(
        function (value, key) {
		if (!allowed_filter_parameter_names.includes( key )) {
			return;
		}

		filter_parameters[key] = String( value );
        }
    );

	return filter_parameters;
}

// =========================================================================================== \\

/**
 * Resolve the currently visible filter state before refreshing the related-media modal after bulk actions.
 *
 * @param {HTMLElement|jQuery} related_attachments_modal_content
 * @returns {Object}
 */
function ai4seo_get_related_attachments_modal_current_filter_parameters(related_attachments_modal_content) {
	const $related_attachments_modal_content = ai4seo_normalize_$( related_attachments_modal_content );

	if (!ai4seo_exists_$( $related_attachments_modal_content )) {
		return {};
	}

	const $search_form = $related_attachments_modal_content.find( '.ai4seo-content-list-search-form' ).first();

	// Prefer the form because it carries search, sort, status, language, and hidden modal context together.
	if (ai4seo_exists_$( $search_form )) {
		return ai4seo_get_related_attachments_modal_filter_parameters_from_form( $search_form );
	}

	// Fall back to the active status link for older markup that might not include the shared search form.
	const $current_status_filter = $related_attachments_modal_content.find( '.ai4seo-content-list-status-filters a.current' ).first();

	if (ai4seo_exists_$( $current_status_filter )) {
		return ai4seo_get_related_attachments_modal_filter_parameters_from_url( String( $current_status_filter.attr( 'href' ) || '' ) );
	}

	return {};
}

// =========================================================================================== \\

/**
 * Resolve the source post ID from the related-media modal container.
 *
 * @param {HTMLElement|jQuery} related_attachments_modal_content
 * @returns {number}
 */
function ai4seo_get_related_attachments_modal_post_id(related_attachments_modal_content) {
	const $related_attachments_modal_content = ai4seo_normalize_$( related_attachments_modal_content );

	if (!ai4seo_exists_$( $related_attachments_modal_content )) {
		return 0;
	}

	// The modal wrapper owns the source post ID; hidden form fallback keeps filter submissions resilient.
	let post_id = parseInt( $related_attachments_modal_content.attr( 'data-post-id' ), 10 );

	if (post_id > 0) {
		return post_id;
	}

	post_id = parseInt( $related_attachments_modal_content.find( "input[name='post_id']" ).first().val(), 10 );

	return post_id > 0 ? post_id : 0;
}

// =========================================================================================== \\

/**
 * Check whether a media-table link should be handled as an AJAX modal filter reload.
 *
 * @param {string} url
 * @returns {boolean}
 */
function ai4seo_is_related_attachments_modal_filter_url(url) {
	if (!url || url === '#') {
		return false;
	}

	// Reuse the URL extractor so link detection and parameter preservation stay in sync.
	return Object.keys( ai4seo_get_related_attachments_modal_filter_parameters_from_url( url ) ).length > 0;
}

// =========================================================================================== \\

/**
 * Reload the related-media modal using filter parameters parsed from a media-table link.
 *
 * @param {HTMLElement|jQuery} related_attachments_modal_content
 * @param {string} url
 */
function ai4seo_reload_related_attachments_modal_from_url(related_attachments_modal_content, url) {
	const filter_parameters = ai4seo_get_related_attachments_modal_filter_parameters_from_url( url );
	ai4seo_reload_related_attachments_modal( related_attachments_modal_content, filter_parameters );
}

// =========================================================================================== \\

/**
 * Reload the related-media modal using filter parameters from the embedded search form.
 *
 * @param {HTMLElement|jQuery} related_attachments_modal_content
 * @param {HTMLElement|jQuery} form
 */
function ai4seo_reload_related_attachments_modal_from_form(related_attachments_modal_content, form) {
	const filter_parameters = ai4seo_get_related_attachments_modal_filter_parameters_from_form( form );
	ai4seo_reload_related_attachments_modal( related_attachments_modal_content, filter_parameters );
}

// =========================================================================================== \\

/**
 * Reload the related-media modal by reopening the existing AJAX modal with preserved filter data.
 *
 * @param {HTMLElement|jQuery} related_attachments_modal_content
 * @param {Object} filter_parameters
 */
function ai4seo_reload_related_attachments_modal(related_attachments_modal_content, filter_parameters = {}) {
	const post_id = ai4seo_get_related_attachments_modal_post_id( related_attachments_modal_content );

	if (!post_id) {
		ai4seo_show_generic_error_toast( 14032601 );
		return;
	}

	// Hide any full-page loader inherited from attachment.php before the modal AJAX request starts.
	ai4seo_hide_full_page_loading_screen();
	ai4seo_open_related_attachments_modal( post_id, filter_parameters );
}

// =========================================================================================== \\

/**
 * Complete attachment-editor saves without a page reload when the editor is nested in Related Media.
 */
function ai4seo_handle_attachment_attributes_editor_save_success() {
	if (ai4seo_try_refresh_related_attachments_modal_after_attachment_attributes_save()) {
		return;
	}

	// Preserve the previous standalone attachment-editor behavior when no Related Media modal is open.
	ai4seo_safe_page_load();
}

// =========================================================================================== \\

/**
 * Refresh the related-media modal after saving a nested attachment editor.
 *
 * @returns {boolean}
 */
function ai4seo_try_refresh_related_attachments_modal_after_attachment_attributes_save() {
	const $related_attachments_modal_content = ai4seo_get_related_attachments_modal_content_$();

	if (!ai4seo_exists_$( $related_attachments_modal_content )) {
		return false;
	}

	// Only stacked modal saves should refresh in place; full-page attachment editors still reload the page.
	if (!ai4seo_exists_$( $related_attachments_modal_content.closest( '.ai4seo-modal' ) )) {
		return false;
	}

	const filter_parameters = ai4seo_get_related_attachments_modal_current_filter_parameters( $related_attachments_modal_content );

	// Close the deeper attachment editor so the refreshed related-media modal becomes active again.
	if (!ai4seo_close_modal( 'ai4seo-attachment-attributes-editor-modal' )) {
		return false;
	}

	ai4seo_reload_related_attachments_modal( $related_attachments_modal_content, filter_parameters );

	return true;
}

// =========================================================================================== \\

/**
 * Resolve attachment usage statuses only for placeholders inserted in the requested root.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_attachment_usage_context_statuses(scope = null) {
	const $usage_context_statuses = ai4seo_get_elements_in_scope_$( '.ai4seo-attachment-usage-context-status', scope );

	if (!ai4seo_exists_$( $usage_context_statuses )) {
		return;
	}

	$usage_context_statuses.each(
        function () {
		const $usage_context_status = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $usage_context_status )) {
			return;
		}

		if ($usage_context_status.data( 'ai4seo-usage-context-initialized' )) {
			return;
		}

		$usage_context_status.data( 'ai4seo-usage-context-initialized', true );

		const attachment_post_id = parseInt( $usage_context_status.attr( 'data-attachment-post-id' ), 10 );

		if (!attachment_post_id || attachment_post_id <= 0) {
			ai4seo_render_attachment_usage_context_status(
                $usage_context_status,
                {
				usage_context_available: false,
				deep_context_search_enabled: true,
				deep_context_search_supported: false,
                }
            );
			return;
		}

		ai4seo_perform_ajax_call(
			'ai4seo_check_attachment_usage_context',
			{attachment_post_id: attachment_post_id},
			true,
			{},
			false,
			false
		)
			.then(
                response => {
				ai4seo_render_attachment_usage_context_status( $usage_context_status, response );
                }
            )
			.catch(
                error => {
				console.warn( ai4seo_get_plugin_name() + ': Could not check attachment usage context.', error );
				ai4seo_render_attachment_usage_context_status(
                    $usage_context_status,
                    {
					usage_context_available: false,
					deep_context_search_enabled: true,
					deep_context_search_supported: false,
                    }
                );
                }
            );
        }
    );
}

// =========================================================================================== \\

function ai4seo_render_attachment_usage_context_status($usage_context_status, response) {
	$usage_context_status = ai4seo_normalize_$( $usage_context_status );

	if (!ai4seo_exists_$( $usage_context_status )) {
		return;
	}

	if (!response || typeof response !== 'object') {
		response = {};
	}

	const post_id = parseInt( response.post_id || 0, 10 );
	const is_usage_context_available = !!response.usage_context_available && post_id > 0;
	const $usage_context_result = $usage_context_status.find( '.ai4seo-attachment-usage-context-result' );

	if (!ai4seo_exists_$( $usage_context_result )) {
		return;
	}

	const status_class = is_usage_context_available ? 'ai4seo-attachment-usage-context-status-success' : 'ai4seo-attachment-usage-context-status-error';
	const result_class = is_usage_context_available ? 'ai4seo-attachment-usage-context-result-success' : 'ai4seo-attachment-usage-context-result-error';
	const icon_name = is_usage_context_available ? 'circle-check' : 'circle-xmark';
	const icon_css_class = is_usage_context_available
		? 'ai4seo-light-green-icon ai4seo-16x16-icon'
		: 'ai4seo-gray-icon ai4seo-16x16-icon';
	const icon_alt_text = is_usage_context_available ? wp.i18n.__( 'Usage context available', 'ai-for-seo' ) : wp.i18n.__( 'Usage context unavailable', 'ai-for-seo' );
	const $message = ai4seo_get_attachment_usage_context_status_message_element( response, is_usage_context_available, post_id );

	const $result_inner = jQuery( '<div></div>' ).addClass( 'ai4seo-attachment-usage-context-result-inner' );
	const $icon = jQuery( '<span></span>' )
		.addClass( 'ai4seo-attachment-usage-context-result-icon' )
		.html( ai4seo_get_svg_tag( icon_name, icon_css_class, icon_alt_text ) );
	const $text = jQuery( '<span></span>' ).addClass( 'ai4seo-attachment-usage-context-result-text' );

	$text.append( $message );

	$result_inner.append( $icon );
	$result_inner.append( $text );

	$usage_context_result
		.empty()
		.removeClass( 'ai4seo-attachment-usage-context-result-success ai4seo-attachment-usage-context-result-error' )
		.addClass( result_class )
		.append( $result_inner );

	$usage_context_status
		.removeClass( 'ai4seo-attachment-usage-context-status-success ai4seo-attachment-usage-context-status-error' )
		.addClass( status_class );

	ai4seo_reveal_attachment_usage_context_result( $usage_context_status );
	ai4seo_init_tooltips( $usage_context_status );
	ai4seo_init_attachment_usage_context_fix_tooltips( $usage_context_status );
}

// =========================================================================================== \\

/**
 * Bind a dynamic tooltip subtree to the shared tooltip display helpers.
 *
 * @param {jQuery|Node|Window|null} scope
 * @param {string} tooltip_holder_selector
 * @param {string} tooltip_event_namespace
 */
function ai4seo_init_dynamic_tooltip_holders(scope = null, tooltip_holder_selector = '.ai4seo-tooltip-holder', tooltip_event_namespace = '.ai4seo-dynamic-tooltip') {
	const $tooltip_holders = ai4seo_get_elements_in_scope_$( tooltip_holder_selector, scope );

	if (!ai4seo_exists_$( $tooltip_holders )) {
		return;
	}

	const holder_events = 'mouseenter' + tooltip_event_namespace + ' mouseleave' + tooltip_event_namespace + ' click' + tooltip_event_namespace;

	$tooltip_holders.each(
		function () {
			const $holder = ai4seo_normalize_$( this );
			const $trigger = $holder.children( '.ai4seo-tooltip-trigger' ).first();
			const $tooltip = $holder.children( '.ai4seo-tooltip' ).first();

			if (!ai4seo_exists_$( $trigger ) || !ai4seo_exists_$( $tooltip )) {
				return;
			}

			let tooltip_id = $tooltip.attr( 'id' );

			if (!tooltip_id) {
				ai4seo_tooltip_id_counter++;
				tooltip_id = 'ai4seo-tooltip-' + ai4seo_tooltip_id_counter;
				$tooltip.attr( 'id', tooltip_id );
			}

			$trigger.attr(
				{
					'aria-controls': tooltip_id,
					'aria-describedby': tooltip_id,
					'aria-expanded': 'false'
				}
			);
			$tooltip.attr( {'aria-hidden': 'true', role: 'tooltip'} );
		}
	);

	$tooltip_holders
		// These small AJAX subtrees own their tooltip interaction after the generic initializer has prepared them.
		.off( 'mouseenter.ai4seo-tooltips mouseleave.ai4seo-tooltips click.ai4seo-tooltips' )
		.off( holder_events )
		.on(
			'mouseenter' + tooltip_event_namespace,
			function (event) {
				const $holder = jQuery( this );

				ai4seo_cancel_tooltip_hide( $holder );
				this.ai4seo_tooltip_is_pinned = false;
				ai4seo_show_tooltip( ai4seo_get_tooltip_for_holder_$( $holder ), event );
			}
		)
		.on(
			'mouseleave' + tooltip_event_namespace,
			function () {
				const $holder = jQuery( this );

				ai4seo_schedule_tooltip_hide( $holder, ai4seo_get_tooltip_for_holder_$( $holder ) );
			}
		)
		.on(
			'click' + tooltip_event_namespace,
			function (event) {
				event.preventDefault();
				event.stopPropagation();

				const $tooltip = ai4seo_get_tooltip_for_holder_$( jQuery( this ) );
				// A pointer click naturally follows mouseenter, which may already have revealed the tooltip.
				// Keep that click open instead of treating it as an accidental second toggle.
				const should_show_tooltip = !$tooltip.is( ':visible' ) || event.detail > 0;

				ai4seo_hide_all_tooltips();

				if (should_show_tooltip) {
					this.ai4seo_tooltip_is_pinned = true;
					ai4seo_show_tooltip( $tooltip, event );
				}
			}
		);
}

// =========================================================================================== \\

/**
 * Bind the usage-context recovery help that is rendered after its AJAX request completes.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_attachment_usage_context_fix_tooltips(scope = null) {
	ai4seo_init_dynamic_tooltip_holders( scope, '.ai4seo-tooltip-holder', '.ai4seo-attachment-usage-context-tooltip' );
}

// =========================================================================================== \\

/**
 * Keep custom-instructions help usable after either editor is inserted through AJAX.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_editor_custom_instruction_tooltips(scope = null) {
	ai4seo_init_dynamic_tooltip_holders( scope, '.ai4seo-custom-instructions-examples', '.ai4seo-editor-custom-instructions-tooltip' );
}

// =========================================================================================== \\

/**
 * Bind larger image previews to attachment-context thumbnails rendered in AJAX editors.
 *
 * @param {jQuery|Node|Window|null} scope
 */
function ai4seo_init_attachment_context_image_previews(scope = null) {
	const $image_triggers = ai4seo_get_elements_in_scope_$( '.ai4seo-attachment-context-image-trigger', scope );

	if (!ai4seo_exists_$( $image_triggers )) {
		return;
	}

	$image_triggers
		.off( 'click.ai4seo-attachment-context-image-preview' )
		.on(
			'click.ai4seo-attachment-context-image-preview',
			function () {
				ai4seo_open_attachment_context_image_overlay( this );
			}
		);
}

// =========================================================================================== \\

/**
 * Open a modal-sized, dismissible image preview for an attachment context thumbnail.
 *
 * @param {HTMLElement|jQuery} image_trigger
 */
function ai4seo_open_attachment_context_image_overlay(image_trigger) {
	const $image_trigger = ai4seo_normalize_$( image_trigger );

	if (!ai4seo_exists_$( $image_trigger )) {
		return;
	}

	const image_url = String( $image_trigger.attr( 'data-ai4seo-attachment-context-image-url' ) || '' ).trim();

	if (!image_url) {
		return;
	}

	const trigger_element = $image_trigger.get( 0 );
	const overlay_document = trigger_element.ownerDocument || document;

	// One preview at a time avoids stacking focus traps when a modal is reopened.
	jQuery( overlay_document ).find( '.ai4seo-attachment-context-image-overlay' ).remove();

	const $overlay = jQuery( '<div></div>' )
		.addClass( 'ai4seo-attachment-context-image-overlay' )
		.attr(
			{
				'aria-label': wp.i18n.__( 'Larger attachment preview', 'ai-for-seo' ),
				'aria-modal': 'true',
				role: 'dialog'
			}
		);
	const $dialog = jQuery( '<div></div>' ).addClass( 'ai4seo-attachment-context-image-overlay-dialog' );
	const $close_button = jQuery( '<button></button>' )
		.addClass( 'ai4seo-attachment-context-image-overlay-close' )
		.attr(
			{
				'aria-label': wp.i18n.__( 'Close image preview', 'ai-for-seo' ),
				type: 'button'
			}
		)
		.text( '\u00d7' );
	const $image = jQuery( '<img />' )
		.attr(
			{
				alt: '',
				src: image_url
			}
		);

	const close_overlay = function () {
		jQuery( overlay_document ).off( 'keydown.ai4seo-attachment-context-image-preview' );
		$overlay.remove();
		$image_trigger.trigger( 'focus' );
	};

	$close_button.on( 'click.ai4seo-attachment-context-image-preview', close_overlay );
	$overlay.on(
		'click.ai4seo-attachment-context-image-preview',
		function (event) {
			if (event.target === $overlay.get( 0 )) {
				close_overlay();
			}
		}
	);
	jQuery( overlay_document ).on(
		'keydown.ai4seo-attachment-context-image-preview',
		function (event) {
			if ('Escape' === event.key) {
				close_overlay();
			}
		}
	);

	$dialog.append( $close_button, $image );
	$overlay.append( $dialog );
	jQuery( overlay_document.body ).append( $overlay );

	window.requestAnimationFrame(
		function () {
			$close_button.trigger( 'focus' );
		}
	);
}

// =========================================================================================== \\

function ai4seo_get_attachment_usage_context_status_message_element(response, is_usage_context_available, post_id) {
	const $message = jQuery( '<span></span>' );

	if (is_usage_context_available) {
		let post_title = ai4seo_decode_html_entities( String( response.post_title || '' ).trim() ).trim();

		if (!post_title) {
			post_title = wp.i18n.__( 'Untitled', 'ai-for-seo' );
		}

		const post_reference = wp.i18n.sprintf(
			/* translators: 1: Post ID, 2: Post title. */
			wp.i18n.__( 'Post #%1$s: %2$s', 'ai-for-seo' ),
			String( post_id ),
			post_title
		);

		// Link the post reference only when the AJAX payload can provide a valid frontend permalink.
		const post_url = String( response.post_url || '' ).trim();
		const $post_reference = jQuery( '<strong></strong>' ).text( post_reference );

		if (post_url) {
			$post_reference
				.wrapInner(
                    jQuery( '<a></a>' )
					.attr( 'href', post_url )
					.attr( 'target', '_blank' )
                    .attr( 'rel', 'noopener noreferrer' )
                );
		}

		$message
			.append( jQuery( '<span></span>' ).text( wp.i18n.__( 'Usage context available.', 'ai-for-seo' ) ) )
			.append( ' ' )
			.append( $post_reference )
			.append( '. ' )
			.append( jQuery( '<span></span>' ).text( wp.i18n.__( 'This context will be used during generation.', 'ai-for-seo' ) ) );

		return $message;
	}

	if (ai4seo_should_show_attachment_usage_context_settings_link( response, is_usage_context_available )) {
		const $bold_notice = jQuery( '<strong></strong>' )
			.text( wp.i18n.__( 'No usage context could be found.', 'ai-for-seo' ) );
		const $lead_text = jQuery( '<span></span>' )
			.text( ' ' + wp.i18n.__( 'If you think this is a mistake and you actually used this image on a specific page:', 'ai-for-seo' ) + ' ' );

		$message
			.append( $bold_notice )
			.append( $lead_text )
			.append( ai4seo_get_attachment_usage_context_fix_tooltip() );
		return $message;
	}

	$message.text( wp.i18n.__( 'We could not determine where this image is used, so usage context will not be available during generation. Please review the posts where you expect this image to appear.', 'ai-for-seo' ) );
	return $message;
}

/**
 * Build a text tooltip about enabling deep image usage search.
 *
 * @return {jQuery}
 */
function ai4seo_get_attachment_usage_context_fix_tooltip() {
	const holder = document.createElement( 'span' );
	const trigger = document.createElement( 'button' );
	const tooltip = document.createElement( 'span' );

	holder.className = 'ai4seo-tooltip-holder';
	trigger.className = 'ai4seo-tooltip-trigger ai4seo-attachment-usage-context-fix-trigger';
	trigger.type = 'button';
	trigger.setAttribute( 'aria-expanded', 'false' );
	trigger.textContent = wp.i18n.__( 'Learn how to fix it', 'ai-for-seo' );

	const tooltip_text = wp.i18n.__( 'Open WordPress > Settings > SOOZ, show Advanced Settings, and enable Deep Search for Image Usage. SOOZ will perform broader database searches across posts, templates, and supported page-builder data to find where this image is used. When a match is found, the surrounding page context can help generate more relevant media attributes. Tradeoff: these additional searches can increase database load and make processing slower, especially on large sites. The setting itself does not consume extra SOOZ credits; the normal credit cost for generation still applies.', 'ai-for-seo' );
	trigger.setAttribute( 'aria-label', trigger.textContent );
	tooltip.className = 'ai4seo-tooltip ai4seo-ignore-during-dashboard-refresh';
	tooltip.setAttribute( 'role', 'tooltip' );
	tooltip.setAttribute( 'aria-hidden', 'true' );
	tooltip.textContent = tooltip_text;

	holder.appendChild( trigger );
	holder.appendChild( tooltip );

	return jQuery( holder );
}

// =========================================================================================== \\

function ai4seo_decode_html_entities(value) {
	let decoded_value = String( value ?? '' );

	for (let i = 0; i < 3; i++) {
		const next_decoded_value = ai4seo_decode_escaped_html( decoded_value );

		if (next_decoded_value === decoded_value) {
			break;
		}

		decoded_value = next_decoded_value;
	}

	return decoded_value;
}

// =========================================================================================== \\

function ai4seo_should_show_attachment_usage_context_settings_link(response, is_usage_context_available) {
	if (is_usage_context_available) {
		return false;
	}

	return !!response.deep_context_search_supported && !response.deep_context_search_enabled;
}

// =========================================================================================== \\

function ai4seo_reveal_attachment_usage_context_result($usage_context_status) {
	const $loading = $usage_context_status.find( '.ai4seo-attachment-usage-context-loading' );
	const $result = $usage_context_status.find( '.ai4seo-attachment-usage-context-result' );

	if (!ai4seo_exists_$( $result )) {
		return;
	}

	const reveal_result = function () {
		$usage_context_status.removeClass( 'ai4seo-attachment-usage-context-status-loading' );
		$result.fadeIn( 200 );
	};

	if (ai4seo_exists_$( $loading ) && $loading.is( ':visible' )) {
		$loading.fadeOut( 200, reveal_result );
		return;
	}

	reveal_result();
}

// =========================================================================================== \\

function ai4seo_safe_page_load(subpage = '', additional_url_parameter = {}) {
	// if inside elementor or gutenberg editor, do not reload, close all modals instead.
	if (ai4seo_is_inside_elementor_editor() || ai4seo_is_inside_gutenberg_editor() || ai4seo_is_inside_muffin_builder_editor()) {
		ai4seo_close_all_modals();
		return;
	}

	// check if subpage is a string an contains only of [a-z0-9_-].
	if (subpage && !/^[a-z0-9_-]+$/i.test( subpage )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Invalid subpage identifier provided in ai4seo_safe_page_load() \u2014 aborting page load.', subpage );
		subpage = '';
	}

	// check if additional_url_parameter is an object.
	if (additional_url_parameter && typeof additional_url_parameter !== 'object') {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Invalid additional_url_parameter provided in ai4seo_safe_page_load() \u2014 aborting page load.', additional_url_parameter );
		additional_url_parameter = {};
	}

	// decide if we reload the page or go to a specific subpage.
	if (subpage || !jQuery.isEmptyObject( additional_url_parameter )) {
		window.location.href = ai4seo_build_custom_admin_url( subpage, additional_url_parameter );
	} else {
		ai4seo_reload_page();
	}

	// show full page loading screen.
	ai4seo_show_full_page_loading_screen();
}

// =========================================================================================== \\

function ai4seo_show_full_page_loading_screen() {
	// set opacity of .ai4seo-wrap to .7 and non clickable.
	const $wrap = ai4seo_normalize_$( '.ai4seo-wrap' );

	if (ai4seo_exists_$( $wrap )) {
		$wrap.css(
            {
			'opacity': '0.7',
			'pointer-events': 'none',
            }
        );
	}

	// set opacity of all ai4seo-modals to .7 and non-clickable.
	const $modals = ai4seo_normalize_$( '.ai4seo-modal' );

	if (ai4seo_exists_$( $modals )) {
		$modals.css(
            {
			'opacity': '0.7',
			'pointer-events': 'none',
            }
        );
	}

	const $body = ai4seo_normalize_$( 'body' );

	if (ai4seo_exists_$( $body )) {
		// add loading icon in the middle of the screen.
		const loading_screen = "<div class='ai4seo-full-screen-loading-screen'>" + ai4seo_get_svg_tag( 'rotate', 'ai4seo-spinning-icon', wp.i18n.__( 'Loading... Please wait.', 'ai-for-seo' ) ) + '</div>';
		$body.append( loading_screen );
		$body.css( 'overflow', 'hidden' );

		// revert everything after 15 seconds if anything gone wrong.
		setTimeout(
            function () {
			ai4seo_hide_full_page_loading_screen();
            },
            15000
        );
	}
}

// =========================================================================================== \\

function ai4seo_hide_full_page_loading_screen() {
	const $wrap = ai4seo_normalize_$( '.ai4seo-wrap' );

	if (ai4seo_exists_$( $wrap )) {
		$wrap.css(
            {
			'opacity': '',
			'pointer-events': '',
            }
        );
	}

	const $modals = ai4seo_normalize_$( '.ai4seo-modal' );

	if (ai4seo_exists_$( $modals )) {
		$modals.css(
            {
			'opacity': '',
            }
        )
	}

	const $body = ai4seo_normalize_$( 'body' );

	if (ai4seo_exists_$( $body )) {
		$body.css( 'overflow', '' );
		$body.find( '.ai4seo-full-screen-loading-screen' ).remove();
	}
}

// =========================================================================================== \\

function ai4seo_get_all_input_values_in_container($form_container) {
	// Define variable for the form-holder-element based on the form-holder-selector.
	$form_container = ai4seo_normalize_$( $form_container );

	// Stop script if form-holder-element could not be found.
	if (!ai4seo_exists_$( $form_container )) {
		console.error( ai4seo_get_plugin_name() + ': container \"$form_container\" missing in ai4seo_get_all_input_values_in_container() \u2014 cannot collect input values.' );
		return false;
	}

	// Find persistent form-elements within the form-holder-element.
	let input_elements = ai4seo_filter_persistent_inputs( $form_container.find( 'input, select, textarea' ) );
	let input_values = {};
	let this_input_selector;
	let $this_input;
	let this_input_value;
	let this_input_element_name = false;
	let already_processed_element_names = [];

	// Collect identifier (to prevent analysing the same checkbox or radio-name).
	for (let i = 0; i < input_elements.length; i++) {
		$this_input = input_elements[i];
		this_input_element_name = (typeof $this_input.name !== 'undefined') ? $this_input.name : false;

		if (!this_input_element_name) {
			continue;
		}

		if (already_processed_element_names.includes( this_input_element_name )) {
			continue;
		}

		already_processed_element_names.push( this_input_element_name );

		this_input_selector = "[name='" + this_input_element_name + "']";

		// Scope grouped inputs to the current form so preview controls outside it cannot be saved.
		let $this_all_matching_inputs = ai4seo_filter_persistent_inputs( $form_container.find( this_input_selector ) );

		if (!ai4seo_exists_$( $this_all_matching_inputs )) {
			console.warn( ai4seo_get_plugin_name() + ': no matching inputs for selector \"' + this_input_selector + '\" found in ai4seo_get_all_input_values_in_container() \u2014 skipping input.' );
			continue;
		}

		this_input_value = ai4seo_get_input_value( $this_all_matching_inputs );

		if (typeof this_input_value === 'undefined') {
			continue;
		}

		input_values[this_input_element_name] = this_input_value;
	}

	// Make sure that input_vals is not empty.
	if (Object.keys( input_values ).length === 0) {
		ai4seo_open_notification_modal( 1207230231 );
		return false;
	}

	return input_values;
}

// =========================================================================================== \\

function ai4seo_init_gutenberg_header_metadata_editor_button() {
	if (!ai4seo_is_inside_gutenberg_editor()) {
		return false;
	}

	ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header();

	const observer_target = document.querySelector( '.interface-interface-skeleton' ) || document.body;

	if (!observer_target || typeof window.MutationObserver !== 'function') {
		return false;
	}

	const observer_is_current = ai4seo_gutenberg_header_metadata_editor_observer !== null
		&& ai4seo_gutenberg_header_metadata_editor_observer_target === observer_target
		&& observer_target.isConnected;

	if (observer_is_current) {
		return true;
	}

	if (ai4seo_gutenberg_header_metadata_editor_observer !== null) {
		ai4seo_gutenberg_header_metadata_editor_observer.disconnect();
	}

	ai4seo_gutenberg_header_metadata_editor_observer_target = observer_target;
	ai4seo_gutenberg_header_metadata_editor_observer = new window.MutationObserver(
		function (mutations) {
			const header_controls_changed = mutations.some(
				mutation => Array.from( mutation.addedNodes || [] )
					.concat( Array.from( mutation.removedNodes || [] ) )
					.some(
						node => node.nodeType === 1 && (
							node.matches( ai4seo_gutenberg_header_metadata_editor_mutation_selector )
							|| node.querySelector( ai4seo_gutenberg_header_metadata_editor_mutation_selector )
						)
					)
			);

			if (header_controls_changed) {
				ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header();
			}
		}
	);
	ai4seo_gutenberg_header_metadata_editor_observer.observe( observer_target, {childList: true, subtree: true} );

	return true;
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header() {
	const $header_settings = ai4seo_normalize_$( '.edit-post-header .editor-header__settings' );

	// Wait for Gutenberg to mount its current header settings container.
	if (!ai4seo_exists_$( $header_settings )) {
		return false;
	}

	// Prefer WordPress' plugin shortcut group, but remain visible before another plugin creates that SlotFill container.
	const $pinned_items = $header_settings.find( '.interface-pinned-items' ).first();
	const $header_bar_buttons_container = ai4seo_exists_$( $pinned_items ) ? $pinned_items : $header_settings;
	const $existing_header_buttons = ai4seo_normalize_$( '.ai4seo-header-builder-button' );
	const $current_header_button = $header_bar_buttons_container.children( '.ai4seo-header-builder-button' ).first();

	if (ai4seo_exists_$( $current_header_button )) {
		$existing_header_buttons.not( $current_header_button ).remove();
		return true;
	}

	$existing_header_buttons.remove();

	// Read post-id from hidden container if not defined.
	const post_id = ai4seo_get_post_id( 'metadata' );

	// Make sure post_id is defined.
	if (!post_id) {
		return false;
	}

	const button_label = wp.i18n.sprintf(
		wp.i18n.__( 'Open %s Metadata Editor', 'ai-for-seo' ),
		ai4seo_get_plugin_name()
	);
	const $header_button = ai4seo_normalize_$(
		'<button>',
		{
			type: 'button',
			class: 'components-button has-icon ai4seo-header-builder-button',
			'aria-label': button_label,
			title: button_label,
		}
	);

	$header_button
		.html( ai4seo_get_sooz_logo_svg_tag( 'sooz-oo' ) )
		.on(
			'click',
			function () {
				ai4seo_open_metadata_editor_modal( post_id, true );
			}
		);

	if ($header_bar_buttons_container.is( $header_settings )) {
		const $publish_button = $header_settings.children( '.editor-post-publish-button, .editor-post-publish-panel__toggle' ).first();

		if (ai4seo_exists_$( $publish_button )) {
			$header_button.insertBefore( $publish_button );
		} else {
			$header_settings.append( $header_button );
		}
	} else {
		$header_bar_buttons_container.append( $header_button );
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_get_elementor_app_bar_metadata_editor_icon() {
	if (!window.React || typeof window.React.createElement !== 'function') {
		return null;
	}

	return window.React.createElement(
		'span',
		{
			className: 'ai4seo-elementor-app-bar-icon',
			dangerouslySetInnerHTML: {
				__html: ai4seo_get_sooz_logo_svg_tag( 'sooz-oo' ),
			},
		}
	);
}

// =========================================================================================== \\

function ai4seo_get_elementor_app_bar_metadata_editor_action_props() {
	const post_id = ai4seo_get_post_id( 'metadata' );
	const button_label = wp.i18n.sprintf(
		wp.i18n.__( 'Open %s Metadata Editor', 'ai-for-seo' ),
		ai4seo_get_plugin_name()
	);

	return {
		icon: ai4seo_get_elementor_app_bar_metadata_editor_icon,
		title: button_label,
		visible: Boolean( post_id ),
		onClick: function () {
			ai4seo_open_metadata_editor_modal( post_id, true );
		},
	};
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_elementor_app_bar() {
	if (ai4seo_elementor_app_bar_metadata_editor_action_is_registered) {
		return true;
	}

	const editor_app_bar = window.elementorV2 && window.elementorV2.editorAppBar;
	const utilities_menu = editor_app_bar && editor_app_bar.utilitiesMenu;

	if (!utilities_menu
		|| typeof utilities_menu.registerAction !== 'function'
		|| !window.React
		|| typeof window.React.createElement !== 'function') {
		return false;
	}

	utilities_menu.registerAction(
		{
			id: 'ai4seo-metadata-editor',
			priority: 20,
			useProps: ai4seo_get_elementor_app_bar_metadata_editor_action_props,
		}
	);

	ai4seo_elementor_app_bar_metadata_editor_action_is_registered = true;

	return true;
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	const be_builder_button_initialized_data_key = 'ai4seo-be-builder-show-all-seo-settings-button-initialized';

	// Define variable for the seo-title-element within the be-builder-navigation.
	const $seo_title_container = ai4seo_normalize_$( '.mfn-meta-seo-title' );

	// Make sure the seo_title_container exists.
	if (!ai4seo_exists_$( $seo_title_container )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': selector \".mfn-meta-seo-title\" no match in ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() \u2014 skipping toolbar injection.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': selector \".mfn-meta-seo-title\" found in ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() \u2014 injecting toolbar button.' );

	// Read post-id from hidden container if not defined.
	const post_id = ai4seo_get_post_id( 'metadata' );

	// Make sure post_id is defined.
	if (!post_id) {
		return;
	}

	// Build one shared button string; each BE Builder target below decides independently whether it needs it.
	let output = '';

	// Keep the shared styling class and add a BE Builder marker for scoped duplicate checks.
	const plugin_name = ai4seo_get_plugin_name();

	output += '<button type="button" class="ai4seo-button ai4seo-generate-button ai4seo-show-all-seo-settings-button ai4seo-be-builder-show-all-seo-settings-button ai4seo-lockable" aria-label="' + plugin_name + '" title="' + plugin_name + "\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
	output += wp.i18n.sprintf( wp.i18n.__( 'Open %s Metadata Editor', 'ai-for-seo' ), ai4seo_get_sooz_logo_svg_tag( 'sooz' ) );
	output += '</button>';

	$seo_title_container.each(
        function () {
		const $this_seo_title_container = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_seo_title_container )) {
			return;
		}

		const initialized_button = $this_seo_title_container.data( be_builder_button_initialized_data_key );

		// Only this BE Builder slot is considered initialized; later slots can still receive a button.
		if (
			(initialized_button && jQuery.contains( document, initialized_button )) ||
			ai4seo_exists_$( $this_seo_title_container.prev( '.ai4seo-be-builder-show-all-seo-settings-button' ) )
		) {
			return;
		}

		// Insert directly before the SEO title field so it remains grouped with Builder metadata controls.
		$this_seo_title_container.before( output );

		// Remember the inserted element so this target remains idempotent even if BE Builder adds siblings later.
		const $inserted_button = $this_seo_title_container.prev( '.ai4seo-be-builder-show-all-seo-settings-button' );
		$this_seo_title_container.data( be_builder_button_initialized_data_key, $inserted_button.get( 0 ) );
        }
    );
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation() {
	if (!ai4seo_are_external_metadata_generate_buttons_enabled()) {
		return;
	}

	// Read post-id from hidden container if not defined.
	const post_id = ai4seo_get_post_id( 'metadata' );

	// Make sure post_id is defined.
	if (!post_id) {
		return;
	}

	// Build one shared button string; each Elementor panel target below owns its duplicate check.
	let output = '';

	const plugin_name = ai4seo_get_plugin_name();

	// Keep the shared styling class and add an Elementor marker for scoped duplicate checks.
	output += '<button type="button" class="ai4seo-button ai4seo-generate-button ai4seo-show-all-seo-settings-button ai4seo-elementor-show-all-seo-settings-button ai4seo-lockable" aria-label="' + plugin_name + '" title="' + plugin_name + "\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
	output += wp.i18n.sprintf( wp.i18n.__( 'Open %s Metadata Editor', 'ai-for-seo' ), ai4seo_get_sooz_logo_svg_tag( 'sooz' ) );
	output += '</button>';

	// Make sure that at least one of the elementor-elements can be found.
	if (ai4seo_exists_$( '#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items' )) {
		// Define variable for the first elementor-panel-menu-group-element within the elementor-navigation.
		const $first_elementor_panel_menu_group_container = ai4seo_normalize_$( '#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items' );

		// Elementor menu panels can appear at different times, so initialize this target independently.
		if (!ai4seo_exists_$( $first_elementor_panel_menu_group_container.children( '.ai4seo-elementor-show-all-seo-settings-button' ) )) {
			$first_elementor_panel_menu_group_container.append( output );
		}
	}

	if (ai4seo_exists_$( '#elementor-panel-page-settings-controls' )) {
		// Define variable for the container of the elementor panel page settings controls.
		const $elementor_panel_page_settings_controls = ai4seo_normalize_$( '#elementor-panel-page-settings-controls' );

		// Page settings controls are a separate insertion point from the menu panel above.
		if (!ai4seo_exists_$( $elementor_panel_page_settings_controls.children( '.ai4seo-elementor-show-all-seo-settings-button' ) )) {
			$elementor_panel_page_settings_controls.prepend( output );
		}
	}
}

// =========================================================================================== \\

function ai4seo_validate_metadata_editor_inputs(input_values) {
	const raw_length_map = ai4seo_get_localization_parameter( 'ai4seo_max_editor_input_lengths' ) || {};
	const length_map = (typeof raw_length_map === 'object' && raw_length_map !== null) ? raw_length_map : {};
	const fallback_length = ai4seo_normalize_length( length_map.fallback, 512 );

	const focus_keyphrase_value = input_values['ai4seo_metadata_focus-keyphrase'] || '';
	const meta_title_value = input_values['ai4seo_metadata_meta-title'] || '';
	const meta_description_value = input_values['ai4seo_metadata_meta-description'] || '';
	const keywords_value = input_values['ai4seo_metadata_keywords'] || '';
	const facebook_title_value = input_values['ai4seo_metadata_facebook-title'] || '';
	const facebook_description_value = input_values['ai4seo_metadata_facebook-description'] || '';
	const twitter_title_value = input_values['ai4seo_metadata_twitter-title'] || '';
	const twitter_description_value = input_values['ai4seo_metadata_twitter-description'] || '';

	if (!ai4seo_validate_editor_input_length( focus_keyphrase_value, 'focus-keyphrase', length_map, fallback_length, ai4seo_metadata_labels['focus-keyphrase'], 1916141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( meta_title_value, 'meta-title', length_map, fallback_length, ai4seo_metadata_labels['meta-title'], 2016141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( meta_description_value, 'meta-description', length_map, fallback_length, ai4seo_metadata_labels['meta-description'], 2116141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( keywords_value, 'keywords', length_map, fallback_length, ai4seo_metadata_labels['keywords'], 2216141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( facebook_title_value, 'facebook-title', length_map, fallback_length, ai4seo_metadata_labels['facebook-title'], 2316141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( facebook_description_value, 'facebook-description', length_map, fallback_length, ai4seo_metadata_labels['facebook-description'], 2416141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( twitter_title_value, 'twitter-title', length_map, fallback_length, ai4seo_metadata_labels['twitter-title'], 2516141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( twitter_description_value, 'twitter-description', length_map, fallback_length, ai4seo_metadata_labels['twitter-description'], 2616141025 )) {
		return false;
	}

	if (!ai4seo_validate_custom_instruction_input_values( input_values, 3116141025 )) {
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_validate_attachment_attributes_editor_inputs(input_values) {
	const raw_length_map = ai4seo_get_localization_parameter( 'ai4seo_max_editor_input_lengths' ) || {};
	const length_map = (typeof raw_length_map === 'object' && raw_length_map !== null) ? raw_length_map : {};
	const fallback_length = ai4seo_normalize_length( length_map.fallback, 512 );

	const title_value = input_values['ai4seo_attachment_attribute_title'] || '';
	const alt_text_value = input_values['ai4seo_attachment_attribute_alt-text'] || '';
	const caption_value = input_values['ai4seo_attachment_attribute_caption'] || '';
	const description_value = input_values['ai4seo_attachment_attribute_description'] || '';

	if (!ai4seo_validate_editor_input_length( title_value, 'title', length_map, fallback_length, ai4seo_attachment_attribute_labels['title'], 2716141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( alt_text_value, 'alt-text', length_map, fallback_length, ai4seo_attachment_attribute_labels['alt-text'], 2816141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( caption_value, 'caption', length_map, fallback_length, ai4seo_attachment_attribute_labels['caption'], 2916141025 )) {
		return false;
	}

	if (!ai4seo_validate_editor_input_length( description_value, 'description', length_map, fallback_length, ai4seo_attachment_attribute_labels['description'], 3016141025 )) {
		return false;
	}

	if (!ai4seo_validate_custom_instruction_input_values( input_values, 3216141025 )) {
		return false;
	}

	return true;
}


// ___________________________________________________________________________________________ \\
// === SAVE ANYTHING ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_save_anything($submit_button, validation_function, success_function, error_function) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	// check if $submit exists.
	if (!ai4seo_exists_$( $submit_button )) {
		console.error( ai4seo_get_plugin_name() + ': $submit_button does not exist.' );
		return;
	}

	// find a form container nearby.
	let $closest_form_container = ai4seo_find_closest_form_container_$( $submit_button );

	$closest_form_container = ai4seo_normalize_$( $closest_form_container );

	// if still not found, return error.
	if (!ai4seo_exists_$( $closest_form_container )) {
		console.error( ai4seo_get_plugin_name() + ': $closest_form_container does not exist.' );
		return;
	}

	// get all input values from form_container.
	let input_values = ai4seo_get_all_input_values_in_container( $closest_form_container );

	if (validation_function) {
		if (!validation_function( input_values )) {
			return;
		}
	}

	// workaround for empty arrays: go through each ai4seo_ajax_data element and convert empty arrays to #ai4seo-empty-array#.
	for (let key in input_values) {
		if (Array.isArray( input_values[key] ) && input_values[key].length === 0) {
			input_values[key] = '#ai4seo-empty-array#';
		}
	}

	// add loading html to $submit.
	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Saving your data now...', 'ai-for-seo' ) );

	const ajax_data = {
		ai4seo_save_anything_payload: ai4seo_encode_save_anything_payload( input_values ),
		ai4seo_save_anything_payload_encoding: 'base64_json',
	};

	// Perform ajax action.
	ai4seo_perform_ajax_call( 'ai4seo_save_anything', ajax_data )
		.then(
            response => {
			// Display success message.
			ai4seo_show_generic_saved_successfully_toast();
			// scroll to top of page.
			window.scrollTo( 0, 0 );
			// set unsave changes status to false.
			ai4seo_set_unsaved_changes_state( $submit_button, false );
			// perform success function.
			if (success_function) {
				success_function( response );
			}
            }
        )
		.catch(
            error => {
			// Hint: error modal will be shown dynamically, due to the auto error handler.
			ai4seo_show_error_toast( 1212181225, error.message );
			// Forward the failure to the optional caller hook after showing the shared error toast.
			if (error_function) {
				// Failed save requests do not expose a response object in this catch scope.
				error_function( error, null );
			}
            }
        )
		.finally(
            () => {
			// Remove loading-html from submit-element.
			ai4seo_remove_loading_html_from_element( $submit_button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );
}

// =========================================================================================== \\

function ai4seo_encode_save_anything_payload(input_values) {
	// Convert all collected form values into one JSON string before transport encoding.
	const json_payload = JSON.stringify( input_values );

	// Prefer TextEncoder so Unicode text is converted to bytes without deprecated APIs.
	try {
		// Use a byte-safe path when the browser supports TextEncoder.
		if (typeof TextEncoder !== 'undefined') {
			const bytes = new TextEncoder().encode( json_payload );
			let binary_payload = '';

			// Build the binary string expected by btoa() from the UTF-8 bytes.
			for (let i = 0; i < bytes.length; i++) {
				binary_payload += String.fromCharCode( bytes[i] );
			}

			// Base64-encode the binary payload so quotes and backslashes are safe in POST data.
			return btoa( binary_payload );
		}
	} catch (error) {
		console.warn( ai4seo_get_plugin_name() + ': TextEncoder payload encoding failed. Falling back to URI encoding.', error );
	}

	// Use the legacy URI-encoding fallback for older browsers without TextEncoder support.
	return btoa( unescape( encodeURIComponent( json_payload ) ) );
}

// =========================================================================================== \\

function ai4seo_find_closest_form_container_$($reference) {
	$reference = ai4seo_normalize_$( $reference );

	// Check if $reference exists.
	if (!ai4seo_exists_$( $reference )) {
		console.error( ai4seo_get_plugin_name() + ': $reference does not exist.' );
		return false;
	}

	// check if the reference element is actually a .ai4seo-form.
	if ($reference.hasClass( 'ai4seo-form' )) {
		return $reference;
	}

	// Array of selectors to check.
	let check_elements = ['.ai4seo-form', '.ai4seo-modal', '.ai4seo-content-wrapper'];

	// Loop through selectors using for...of, which supports early exit.
	for (let element of check_elements) {
		let $form_container = $reference.closest( element );

		if (ai4seo_exists_$( $form_container )) {
			return $form_container;
		}
	}

	return false;
}


// ___________________________________________________________________________________________ \\
// === ACCOUNT PAGE ========================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_validate_account_inputs(input_values) {
	let api_password = input_values['ai4seo_api_password'] || '';
	let api_username = input_values['ai4seo_api_username'] || '';
	let installed_plugins_plugin_name = input_values['ai4seo_installed_plugins_plugin_name'] || '';
	let installed_plugins_plugin_description = input_values['ai4seo_installed_plugins_plugin_description'] || '';
	let meta_tags_block_starting_hint = input_values['ai4seo_meta_tags_block_starting_hint'] || '';
	let meta_tags_block_ending_hint = input_values['ai4seo_meta_tags_block_ending_hint'] || '';

	// make sure that both fields are empty or both filled.
	if ((api_username.length === 0 && api_password.length > 0) || (api_username.length > 0 && api_password.length === 0)) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter both license owner and license key, or leave both fields empty.', 'ai-for-seo' ) );
		return false;
	}

	// check api username and password lengths.
	if (api_username.length > 128) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid license owner (max. 128 characters).', 'ai-for-seo' ) );
		return false;
	}

	if (api_password.length > 0 && api_password.length !== 48) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid license key (48 characters).', 'ai-for-seo' ) );
		return false;
	}

	// Check the length of the plugin-name.
	if (installed_plugins_plugin_name.length < 3 || installed_plugins_plugin_name.length > 100) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid plugin name (3-100 characters).', 'ai-for-seo' ) );
		return false;
	}

	// Check the length of the plugin-description.
	if (installed_plugins_plugin_description.length < 3 || installed_plugins_plugin_description.length > 140) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid plugin description (3-140 characters).', 'ai-for-seo' ) );
		return false;
	}

	// Check the length of the source-code-notes-content-start.
	if (meta_tags_block_starting_hint.length < 3 || meta_tags_block_starting_hint.length > 250) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid meta tag block starting hint (3-250 characters).', 'ai-for-seo' ) );
		return false;
	}

	// Check the length of the source-code-notes-content-end.
	if (meta_tags_block_ending_hint.length < 3 || meta_tags_block_ending_hint.length > 250) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid meta tag block ending hint (3-250 characters).', 'ai-for-seo' ) );
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_validate_license_inputs(input_values) {
	let api_password = input_values['ai4seo_api_password'] || '';
	let api_username = input_values['ai4seo_api_username'] || '';

	// make sure that both fields are empty or both filled.
	if ((api_username.length === 0 && api_password.length > 0) || (api_username.length > 0 && api_password.length === 0)) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter both license owner and license key, or leave both fields empty.', 'ai-for-seo' ) );
		return false;
	}

	// check api username and password lengths.
	if (api_username.length > 128) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid license owner (max. 128 characters).', 'ai-for-seo' ) );
		return false;
	}

	if (api_password.length > 0 && api_password.length !== 48) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid license key (48 characters).', 'ai-for-seo' ) );
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_set_license_key_visibility(show_actual_license_key) {
	// Synchronize the masked field, actual field, and toggle state from one place for click and remove-license flows.
	const $visual_license_key_holder = ai4seo_normalize_$( '#ai4seo-visual-license-key-holder' );
	const $actual_license_key_holder = ai4seo_normalize_$( '#ai4seo-actual-license-key-holder' );
	const $license_key_toggle = ai4seo_normalize_$( '.ai4seo-license-key-toggle' );

	if (!ai4seo_exists_$( $visual_license_key_holder ) || !ai4seo_exists_$( $actual_license_key_holder )) {
		return;
	}

	// Swap the two field holders without changing the saved license key input value.
	if (show_actual_license_key) {
		$visual_license_key_holder.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
		$actual_license_key_holder.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	} else {
		$visual_license_key_holder.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
		$actual_license_key_holder.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	}

	if (!ai4seo_exists_$( $license_key_toggle )) {
		return;
	}

	// Keep accessibility metadata aligned with the currently visible license key state.
	$license_key_toggle.attr( 'aria-expanded', show_actual_license_key ? 'true' : 'false' );

	const license_key_toggle_label = String(
		$license_key_toggle.attr( show_actual_license_key ? 'data-ai4seo-hide-label' : 'data-ai4seo-show-label' ) || ''
	);

	if (license_key_toggle_label !== '') {
		$license_key_toggle.attr( 'aria-label', license_key_toggle_label );
	}

	const $show_state = ai4seo_normalize_$( $license_key_toggle.find( '.ai4seo-license-key-toggle-show-state' ) );
	const $hide_state = ai4seo_normalize_$( $license_key_toggle.find( '.ai4seo-license-key-toggle-hide-state' ) );

	if (!ai4seo_exists_$( $show_state ) || !ai4seo_exists_$( $hide_state )) {
		return;
	}

	// Toggle the button labels after the field state changes so the next available action is visible.
	if (show_actual_license_key) {
		$show_state.addClass( 'ai4seo-display-none' );
		$hide_state.removeClass( 'ai4seo-display-none' );
	} else {
		$show_state.removeClass( 'ai4seo-display-none' );
		$hide_state.addClass( 'ai4seo-display-none' );
	}
}

// =========================================================================================== \\

function ai4seo_toggle_license_key_visibility(toggle_button) {
	// Read the current state from the clicked button so keyboard and pointer activation use the same path.
	const $toggle_button = ai4seo_normalize_$( toggle_button );

	if (!ai4seo_exists_$( $toggle_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "toggle_button" missing in ai4seo_toggle_license_key_visibility() — cannot toggle license key visibility.' );
		return;
	}

	ai4seo_set_license_key_visibility( $toggle_button.attr( 'aria-expanded' ) !== 'true' );
}

// =========================================================================================== \\

function ai4seo_init_license_key_toggle() {
	// Bind the account page license toggle when saved credentials render the masked license key field.
	const $license_key_toggle = ai4seo_normalize_$( '.ai4seo-license-key-toggle' );

	if (!ai4seo_exists_$( $license_key_toggle )) {
		return;
	}

	$license_key_toggle.off( 'click.ai4seo-license-key-toggle' );
	$license_key_toggle.on(
		'click.ai4seo-license-key-toggle',
		function () {
			ai4seo_toggle_license_key_visibility( this );
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_license_form() {
	const $white_label_checkbox = ai4seo_normalize_$( '#ai4seo-enable-white-label' );
	const $white_label_container = ai4seo_normalize_$( '.ai4seo-white-label-only-container' );
	const $source_code_checkbox = ai4seo_normalize_$( '#ai4seo-display-source-code-notes' );
	const $source_code_container = ai4seo_normalize_$( '.ai4seo-source-code-adjustments-only-container' );

	if (!ai4seo_exists_$( $white_label_checkbox ) || !ai4seo_exists_$( $white_label_container ) || !ai4seo_exists_$( $source_code_checkbox ) || !ai4seo_exists_$( $source_code_container )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': White-label license controls missing in ai4seo_init_license_form() — cannot bind visibility toggles.');.
		return;
	}

	// ai4seo_console_debug(ai4seo_get_plugin_name() + ': Initializing license form visibility toggles.');.

	ai4seo_toggle_visibility_on_checkbox( $white_label_checkbox, $white_label_container );
	ai4seo_toggle_visibility_on_checkbox( $source_code_checkbox, $source_code_container );
}

// =========================================================================================== \\

function ai4seo_toggle_visibility_on_checkbox($checkbox, $target, visible_on_checked = true) {
	$checkbox = ai4seo_normalize_$( $checkbox );
	$target = ai4seo_normalize_$( $target );

	// Stop script if selector_checkbox or selector_target could not be found.
	if (!ai4seo_exists_$( $checkbox ) || !ai4seo_exists_$( $target )) {
		console.warn( ai4seo_get_plugin_name() + ': selector_checkbox or selector_target missing in ai4seo_toggle_visibility_on_checkbox() — cannot toggle visibility.' );
		return;
	}

	// Check if the white-label-settings should be shown.
	if ($checkbox.is( ':checked' )) {
		if (visible_on_checked) {
			$target.removeClass( 'ai4seo-display-none' );
		} else {
			$target.addClass( 'ai4seo-display-none' );
		}
	} else {
		if (visible_on_checked) {
			$target.addClass( 'ai4seo-display-none' );
		} else {
			$target.removeClass( 'ai4seo-display-none' );
		}
	}
}

// =========================================================================================== \\

// Function to display lost-key-modal.
function ai4seo_open_lost_key_modal() {
	// Define variables for the modal.
	let modal_headline = wp.i18n.__( 'Lost your license data?', 'ai-for-seo' );
	let modal_content = "<div class='ai4seo-form-item'>";
	modal_content += wp.i18n.__( 'Please enter the same email address used during Stripe checkout. You can check your order confirmation email for the correct address.', 'ai-for-seo' );
	modal_content += '<br><br>';
	modal_content += "<div class='ai4seo-form-item-input-wrapper'>";
	modal_content += "<input type='email' id='ai4seo-lost-licence-email' class='ai4seo-textfield' placeholder='" + wp.i18n.__( 'Enter your email address', 'ai-for-seo' ) + "' />";
	modal_content += '</div>';
	modal_content += '</div>';

	let modal_footer = "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Cancel', 'ai-for-seo' ) + '</button> ';
	modal_footer += "<button type='button' id='ai4seo-lost-licence-submit' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_request_lost_licence_data(this);'>" + wp.i18n.__( 'Send License Data', 'ai-for-seo' ) + '</button>';

	let modal_settings = {
		close_on_outside_click: true,
		add_close_button: true,
	}

	// Open notification modal.
	ai4seo_open_notification_modal( modal_headline, modal_content, modal_footer, modal_settings );
}

// =========================================================================================== \\

function ai4seo_remove_license($button) {
	$button = ai4seo_normalize_$( $button );

	if (!ai4seo_exists_$( $button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$button" missing in ai4seo_remove_license() � cannot open confirmation modal.' );
		return;
	}

	const remove_button_id = 'ai4seo-remove-license-button';
	$button.attr( 'id', remove_button_id );

	let modal_headline = wp.i18n.__( 'Please confirm', 'ai-for-seo' );
	let modal_content = wp.i18n.__( 'Are you sure you want to remove your license data?', 'ai-for-seo' );
	modal_content += '<br><br>' + wp.i18n.__( 'If you continue, you must re-enter the license owner and license key to reconnect this website.', 'ai-for-seo' );
	modal_content += '<br><br>' + wp.i18n.__( 'You can find your license data in the email we sent you, or request it again with the "Lost your license data?" button.', 'ai-for-seo' );

	let modal_footer = "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Cancel', 'ai-for-seo' ) + '</button> ';
	modal_footer += "<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_confirm_remove_license(this, \"" + remove_button_id + "\");'>" + wp.i18n.__( 'Yes, remove license', 'ai-for-seo' ) + '</button>';

	ai4seo_open_notification_modal(
        modal_headline,
        modal_content,
        modal_footer,
        {
		close_on_outside_click: true,
		add_close_button: true,
        }
    );
}

// =========================================================================================== \\

function ai4seo_confirm_remove_license($modal_confirm_button, remove_button_id = '') {
	$modal_confirm_button = ai4seo_normalize_$( $modal_confirm_button );

	if (!ai4seo_exists_$( $modal_confirm_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$modal_confirm_button" missing in ai4seo_confirm_remove_license() � cannot remove licence.' );
		return;
	}

	const $remove_button = remove_button_id ? ai4seo_normalize_$( '#' + remove_button_id ) : ai4seo_normalize_$();

	if (!ai4seo_exists_$( $remove_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$remove_button" missing in ai4seo_confirm_remove_license() � cannot remove licence.' );
		return;
	}

	ai4seo_close_modal_by_child( $modal_confirm_button );
	ai4seo_perform_remove_license( $remove_button );
}

// =========================================================================================== \\

function ai4seo_perform_remove_license($button) {
	$button = ai4seo_normalize_$( $button );

	if (!ai4seo_exists_$( $button )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$button" missing in ai4seo_perform_remove_license() — cannot remove licence.' );
		return;
	}

	const $closest_form_container = ai4seo_normalize_$( ai4seo_find_closest_form_container_$( $button ) );

	if (!ai4seo_exists_$( $closest_form_container )) {
		console.warn( ai4seo_get_plugin_name() + ': element "$closest_form_container" missing in ai4seo_perform_remove_license() — cannot remove licence.' );
		return;
	}

	const $license_owner_input = ai4seo_normalize_$( "input[name='ai4seo_api_username']" );
	const $license_key_input = ai4seo_normalize_$( "input[name='ai4seo_api_password']" );
	const $visual_license_key_holder = ai4seo_normalize_$( '#ai4seo-visual-license-key-holder' );
	const $actual_license_key_holder = ai4seo_normalize_$( '#ai4seo-actual-license-key-holder' );
	const $license_key_input_wrapper = ai4seo_normalize_$( $license_key_input.closest( '.ai4seo-form-item-input-wrapper' ) );
	const $license_key_toggle_button = ai4seo_exists_$( $license_key_input_wrapper )
		? ai4seo_normalize_$( $license_key_input_wrapper.find( '.ai4seo-license-key-toggle' ) )
		: ai4seo_normalize_$();

	if (!ai4seo_exists_$( $license_owner_input ) || !ai4seo_exists_$( $license_key_input )) {
		console.warn( ai4seo_get_plugin_name() + ': License input fields missing in ai4seo_perform_remove_license() — cannot remove licence.' );
		return;
	}

	$license_owner_input.val( '' );
	$license_key_input.val( '' );

	// Reveal the actual input after clearing so the empty field remains editable for reconnecting the license.
	if (ai4seo_exists_$( $visual_license_key_holder ) && ai4seo_exists_$( $actual_license_key_holder )) {
		ai4seo_set_license_key_visibility( true );
	}

	// Hide the toggle once no masked saved license key remains.
	if (ai4seo_exists_$( $license_key_toggle_button )) {
		$license_key_toggle_button.hide();
	}

	ai4seo_set_unsaved_changes_state( $closest_form_container, true );

	ai4seo_save_anything(
        $button,
        ai4seo_validate_license_inputs,
        function () {
		$button.remove();
		ai4seo_safe_page_load();
        }
    );
}

// =========================================================================================== \\

// Function to request lost licence data.
function ai4seo_request_lost_licence_data($submit_button) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	if (!ai4seo_exists_$( $submit_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_request_lost_licence_data() \u2014 cannot request licence recovery.' );
		return;
	}

	// Get email value.
	const $lost_licence_email = ai4seo_normalize_$( '#ai4seo-lost-licence-email' );

	if (!ai4seo_exists_$( $lost_licence_email )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$lost_licence_email\" missing in ai4seo_request_lost_licence_data() \u2014 cannot request licence recovery.' );
		return;
	}

	let email = $lost_licence_email.val();

	// Validate email.
	if (!email || email.length < 3 || !email.includes( '@' )) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid email address.', 'ai-for-seo' ) );
		return;
	}

	// Add loading state to submit button.
	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	// Prepare AJAX data.
	let ajax_data = {
		stripe_email: email
	};

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Requesting license data...', 'ai-for-seo' ) );

	// Perform AJAX call.
	ai4seo_perform_ajax_call( 'ai4seo_request_lost_licence_data', ajax_data, true, {}, true )
		.then(
            response => {
			// Always show success confirmation regardless of API response.
			const plugin_name = ai4seo_get_plugin_name();
			/* translators: %s: plugin name */
			let confirmation_message = wp.i18n.sprintf( wp.i18n.__( 'If this email address is linked to a Stripe order for %s, you will receive an email with your licence data within the next 60 seconds. Otherwise you will not receive any email. Please check your inbox and spam folder.', 'ai-for-seo' ), plugin_name );
			let confirmation_headline = wp.i18n.__( 'Request Sent', 'ai-for-seo' );
			let confirmation_footer = "<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_close_all_modals();'>" + wp.i18n.__( 'OK', 'ai-for-seo' ) + '</button>';
			ai4seo_open_notification_modal(
                confirmation_headline,
                confirmation_message,
                confirmation_footer,
                {
				close_on_outside_click: false,
				add_close_button: false
                }
            );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1312181225, error );
            }
        )
		.finally(
            () => {
			ai4seo_remove_loading_html_from_element( $submit_button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );
}


// ___________________________________________________________________________________________ \\
// === NOTIFICATIONS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_init_notifications() {
	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$document\" missing in ai4seo_init_notifications() \u2014 cannot initialize notification dismissal.' );
		return;
	}

	// Server-rendered notice buttons only need delegated click binding; placement is handled by PHP page rendering.
	$document.off( 'click.ai4seo-dismiss-notification', '.ai4seo-notification > .notice-dismiss' );
	$document.on(
        'click.ai4seo-dismiss-notification',
        '.ai4seo-notification > .notice-dismiss',
        function () {
		const $dismiss_button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $dismiss_button )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$dismiss_button\" missing in ai4seo_init_notifications() \u2014 notification cannot close.' );
			return;
		}

		const $closest_notification = $dismiss_button.closest( '.ai4seo-notification' );

		if (!ai4seo_exists_$( $closest_notification )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$closest_notification\" missing in ai4seo_init_notifications() \u2014 cannot update notification state.' );
			return;
		}

		if (!$closest_notification.data( 'notification-index' )) {
			return;
		}

		// hide the notification with animation.
		$closest_notification.slideUp(
            200,
            function () {
			$closest_notification.remove();
            }
        );

		let notification_index = $closest_notification.data( 'notification-index' );

		// call desired ajax action.
		ai4seo_perform_ajax_call( 'ai4seo_dismiss_notification', {ai4seo_notification_index: notification_index} )
			.then(
                response => { /* nothing to do here */
                }
            )
			.catch(
                error => {
				ai4seo_show_generic_error_toast( 1412181225 );
                }
            )
			.finally(
                () => { /* nothing to do here */
                }
            );
        }
    );

	// class "ai4seo-notification-dismiss-button" (dismiss button in notification footer).
	$document.off( 'click.ai4seo-dismiss-notification', '.ai4seo-notification-dismiss-button' );
	$document.on(
        'click.ai4seo-dismiss-notification',
        '.ai4seo-notification-dismiss-button',
        function () {
		const $dismiss_button = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $dismiss_button )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$dismiss_button\" missing in ai4seo_init_notifications() \u2014 notification cannot close.' );
			return;
		}

		const $closest_notification = $dismiss_button.closest( '.ai4seo-notification' );

		if (!ai4seo_exists_$( $closest_notification )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$closest_notification\" missing in ai4seo_init_notifications() \u2014 cannot update notification state.' );
			return;
		}

		if (!$closest_notification.data( 'notification-index' )) {
			return;
		}

		let notification_index = $closest_notification.data( 'notification-index' );

		// hide the notification with animation.
		$closest_notification.slideUp(
            200,
            function () {
			$closest_notification.remove();
            }
        );

		// call desired ajax action.
		ai4seo_perform_ajax_call( 'ai4seo_dismiss_notification', {ai4seo_notification_index: notification_index} )
			.then(
                response => { /* nothing to do here */
                }
            )
			.catch(
                error => {
				ai4seo_show_generic_error_toast( 1013181225 );
                }
            )
			.finally(
                () => { /* nothing to do here */
                }
            );

        }
    );
}


// ___________________________________________________________________________________________ \\
// === PLUGIN DEACTIVATION FEEDBACK ========================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_init_plugin_deactivation_feedback() {
	const $deactivate_link = ai4seo_normalize_$( 'tr[data-plugin="ai-for-seo/ai-for-seo.php"] .deactivate a' );

	if (!ai4seo_exists_$( $deactivate_link )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': Plugin deactivate link not found in ai4seo_init_plugin_deactivation_feedback() — skipping interception.');.
		return;
	}

	$deactivate_link.off( 'click.ai4seo-plugin-deactivation-feedback' );
	$deactivate_link.on(
        'click.ai4seo-plugin-deactivation-feedback',
        function (event) {
		event.preventDefault();
		event.stopPropagation();

		const $this_link = ai4seo_normalize_$( this );
		const deactivation_url = $this_link.attr( 'href' ) || ''; // kept for debug fallback only.

		const $modal = ai4seo_open_modal_from_schema(
            'plugin-deactivation-feedback',
            {
			modal_size: 'small',
			close_on_outside_click: true,
            }
        );

		if (!ai4seo_exists_$( $modal )) {
			console.error( ai4seo_get_plugin_name() + ': Failed to open deactivation feedback modal in ai4seo_init_plugin_deactivation_feedback(). Redirecting to deactivation URL as fallback.' );
			ai4seo_force_plugin_deactivation_on_error();
			return;
		}

		ai4seo_init_plugin_deactivation_feedback_modal_behavior( $modal );
		$modal.data( 'ai4seo-deactivation-url', deactivation_url );
        }
    );
}

// =========================================================================================== \\

function ai4seo_force_plugin_deactivation_on_error() {
	// if anything goes wrong during the feedback submission or modal display, we want to make sure that the plugin can still be deactivated by redirecting to the deactivation URL as fallback.
	const $deactivate_link = ai4seo_normalize_$( 'tr[data-plugin="ai-for-seo/ai-for-seo.php"] .deactivate a' );

	if (!ai4seo_exists_$( $deactivate_link )) {
		console.warn( ai4seo_get_plugin_name() + ': Plugin deactivate link not found in ai4seo_force_plugin_deactivation_on_error() — cannot redirect to deactivation URL as fallback.' );
		return;
	}

	// remove event listener to avoid potential interference if modal fails to open in the future.
	$deactivate_link.off( 'click.ai4seo-plugin-deactivation-feedback' );

	// redirect to deactivation URL as fallback.
	window.location.href = $deactivate_link.attr( 'href' ) || ''; // kept for debug fallback only.

	// close any open modals just in case.
	ai4seo_close_all_modals();
}

// =========================================================================================== \\

function ai4seo_init_plugin_deactivation_feedback_modal_behavior($modal) {
	if (!ai4seo_exists_$( $modal )) {
		return;
	}

	const textarea_placeholders = {
		not_satisfied_with_ai_text_quality: wp.i18n.__( 'What felt generic or inaccurate? One example helps.', 'ai-for-seo' ),
		missing_feature: wp.i18n.__( 'We have a very agile dev team. What feature are you missing? We sure implement it in one of our next updates.', 'ai-for-seo' ),
		bug_or_error: wp.i18n.__( 'We treat bugs seriously. What happened? Did you see an error message?', 'ai-for-seo' ),
		too_expensive: wp.i18n.__(
			'What price would feel reasonable for your usage? We’re happy to discuss it, and if you found a cheaper option elsewhere, we’re happy to match it.',
			'ai-for-seo'
		),
		hard_to_use: wp.i18n.__( 'Which part felt confusing or hard to use?', 'ai-for-seo' ),
		performance_issues: wp.i18n.__( 'What performance issues did you notice?', 'ai-for-seo' ),
		other: wp.i18n.__( 'Please tell us your reason in a few words.', 'ai-for-seo' ),
	};

	const $reason_inputs = $modal.find( 'input[name="ai4seo_plugin_deactivation_feedback_reason"]' );
	const $message_input = $modal.find( '.ai4seo-plugin-deactivation-feedback-message' );
	const $conditional_sections = $modal.find( '.ai4seo-plugin-deactivation-feedback-conditional' );

	if (!ai4seo_exists_$( $reason_inputs ) || !ai4seo_exists_$( $message_input ) || !ai4seo_exists_$( $conditional_sections )) {
		return;
	}

	function update_conditional_feedback_fields() {
		const reason = $reason_inputs.filter( ':checked' ).val() || 'just_testing_or_temporary';
		const is_just_testing_or_temporary = (reason === 'just_testing_or_temporary');
		const placeholder = textarea_placeholders[reason] || '';

		$message_input.attr( 'placeholder', placeholder );

		if (is_just_testing_or_temporary) {
			// Store hidden state in the class after the slide animation finishes.
			$conditional_sections.stop( true, true ).slideUp(
				150,
				function () {
					ai4seo_normalize_$( this ).addClass( 'ai4seo-display-none' ).css( 'display', '' );
				}
			);
		} else {
			// Clear the class before sliding down so initially hidden sections can animate.
			$conditional_sections.removeClass( 'ai4seo-display-none' ).hide().stop( true, true ).slideDown(
				150,
				function () {
					ai4seo_normalize_$( this ).css( 'display', '' );
				}
			);
		}
	}

	$reason_inputs.off( 'change.ai4seo-plugin-deactivation-feedback-modal-behavior' );
	$reason_inputs.on( 'change.ai4seo-plugin-deactivation-feedback-modal-behavior', update_conditional_feedback_fields );

	update_conditional_feedback_fields();
}

// =========================================================================================== \\

function ai4seo_submit_feedback($button, flow = 'deactivate') {
	const $modal = ai4seo_get_modal_$( 'ai4seo-plugin-deactivation-feedback' );

	if (!ai4seo_exists_$( $modal )) {
		console.warn( ai4seo_get_plugin_name() + ': Modal missing in ai4seo_submit_feedback().' );
		ai4seo_force_plugin_deactivation_on_error();
		return;
	}

	// COLLECT PARAMETERS
	// Flow.
	if (!['deactivate', 'claim_offer'].includes( flow )) {
		console.warn( ai4seo_get_plugin_name() + ': Invalid flow in ai4seo_submit_feedback():', flow );
		ai4seo_force_plugin_deactivation_on_error();
		return;
	}

	// Message.
	const $message_input = $modal.find( '.ai4seo-plugin-deactivation-feedback-message' );

	if (!ai4seo_exists_$( $message_input )) {
		console.warn( ai4seo_get_plugin_name() + ': Message input missing in ai4seo_submit_feedback().' );
		ai4seo_force_plugin_deactivation_on_error();
		return;
	}

	// trim message.
	let message = ($message_input.val() || '').trim();

	// max length 2000: cut off.
	if (message.length > 2000) {
		message = message.substring( 0, 2000 );
	}

	// reason (radiobutton name = ai4seo_plugin_deactivation_feedback_reason).
	const $reason_inputs = $modal.find( 'input[name="ai4seo_plugin_deactivation_feedback_reason"]' );

	if (!ai4seo_exists_$( $reason_inputs )) {
		console.warn( ai4seo_get_plugin_name() + ': Reason inputs missing in ai4seo_submit_feedback().' );
		ai4seo_force_plugin_deactivation_on_error();
		return;
	}

	let reason = $reason_inputs.filter( ':checked' ).val() || '';

	// reason must be one of the following: 'just_testing_or_temporary', 'not_satisfied_with_ai_text_quality', 'too_expensive', 'missing_feature', 'hard_to_use', 'bug_or_error', 'performance_issues', 'other'.
	if (!['just_testing_or_temporary', 'not_satisfied_with_ai_text_quality', 'too_expensive', 'missing_feature', 'hard_to_use', 'bug_or_error', 'performance_issues', 'other'].includes( reason )) {
		console.warn( ai4seo_get_plugin_name() + ': Invalid reason in ai4seo_submit_feedback():', reason );
		// fallback to 'just_testing_or_temporary' if reason is invalid or missing.
		reason = 'just_testing_or_temporary';
	}

	// if reason is 'just_testing_or_temporary' we just close the modal deactivate the plugin without sending feedback.
	if (reason === 'just_testing_or_temporary') {
		ai4seo_force_plugin_deactivation_on_error();
		return;
	}

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Submitting your feedback...', 'ai-for-seo' ) );

	// LOCK UI.
	ai4seo_lock_and_disable_lockable_input_fields();
	ai4seo_add_loading_html_to_element( $button );

	// PERFORM AJAX CALL.
	ai4seo_perform_ajax_call(
        'ai4seo_submit_feedback',
        {
		feedback_reason: reason,
		feedback_message: message,
		feedback_flow: flow,
        }
    )
		.then(
            (response) => {
			// on claim offer.
			if (flow === 'claim_offer') {
				ai4seo_show_success_toast( wp.i18n.__( 'Thank you for your feedback! You will be redirected to the dashboard to claim your offer.', 'ai-for-seo' ) );
				ai4seo_safe_page_load( 'dashboard' );
			} else if (flow === 'deactivate') {
				// on deactivate.
				ai4seo_show_success_toast( wp.i18n.__( 'Thank you for your feedback! We hope to see you again in the future. ❤️', 'ai-for-seo' ) );

				// close modal.
				ai4seo_close_all_modals();

				const was_deactivated = !!response.was_deactivated;

				if (was_deactivated) {
					ai4seo_reload_page();
				} else {
					console.warn( ai4seo_get_plugin_name() + ': Feedback submitted but plugin deactivation did not complete.' );
					ai4seo_force_plugin_deactivation_on_error();
				}
			} else {
				console.warn( ai4seo_get_plugin_name() + ': Invalid flow in AJAX response of ai4seo_submit_feedback():', flow );
				ai4seo_force_plugin_deactivation_on_error();
			}
            }
        )
		.catch(
            error => {
			console.error( ai4seo_get_plugin_name() + ': Failed in ai4seo_submit_feedback():', error );
			ai4seo_show_error_toast( 51520626, error );
			ai4seo_force_plugin_deactivation_on_error();
            }
        )
		.finally(
            () => {
			ai4seo_unlock_and_enable_lockable_input_fields();
			ai4seo_remove_loading_html_from_element( $button );
            }
        );
}

// =========================================================================================== \\

function ai4seo_handle_plugin_deactivation_feedback_abort(button) {
	const $modal = ai4seo_get_modal_$( 'ai4seo-plugin-deactivation-feedback' );

	if (!ai4seo_exists_$( $modal )) {
		console.error( ai4seo_get_plugin_name() + ': Modal missing in ai4seo_handle_plugin_deactivation_feedback_abort().' );
		return;
	}

	const $message_input = $modal.find( '.ai4seo-plugin-deactivation-feedback-message' );
	const message_length = ai4seo_exists_$( $message_input ) ? (($message_input.val() || '').trim().length) : 0;

	if (message_length >= 20) {
		ai4seo_submit_feedback( button, 'count_me_in' );
	} else {
		ai4seo_close_modal_from_schema( 'plugin-deactivation-feedback' );
	}
}


// ___________________________________________________________________________________________ \\
// === TERMS OF SERVICE ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Show confirmation notification modal to really reject tos
 */
function ai4seo_confirm_to_reject_tos() {
	let headline = wp.i18n.__( 'Please confirm', 'ai-for-seo' );
	const plugin_name = ai4seo_get_plugin_name();
	let content = wp.i18n.sprintf( wp.i18n.__( 'Are you sure you want to reject the terms of service and uninstall %s?', 'ai-for-seo' ), plugin_name );
	content += '<br><br>';
	content += wp.i18n.__( "<strong>Attention:</strong><br>If you have already purchased a subscription, you can cancel it by clicking <a href='https://sooz.ai/manage-plan' target='_blank'>HERE</a>.", 'ai-for-seo' );

	let reject_button = "<button type='button' class='ai4seo-button ai4seo-abort-button' id='ai4seo-reject-tos-button' onclick='ai4seo_reject_tos();'>" + wp.i18n.__( 'Yes, please!', 'ai-for-seo' ) + '</button>';
	let back_button = "<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'No, I changed my mind', 'ai-for-seo' ) + '</button>';

	ai4seo_open_notification_modal( headline, content, reject_button + back_button );
}

// =========================================================================================== \\

/**
 * Let the user reject tos, using ajax
 */
function ai4seo_reject_tos() {
	ai4seo_add_loading_html_to_element( '.ai4seo-button' );

	ai4seo_perform_ajax_call( 'ai4seo_reject_tos' )
		.then(
            response => {
			window.location.href = ai4seo_admin_installed_plugins_page_url;
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1113181225, error );
            }
        );
}

// =========================================================================================== \\

/**
 * Initialize the terms of service accept button state and checkbox handler.
 */
function ai4seo_init_tos_accept_button_state() {
	const $accept_tos_checkbox = ai4seo_normalize_$( '.ai4seo-accept-tos-checkbox' );
	const $accept_button = ai4seo_normalize_$( '.ai4seo-accept-tos-button' );

	if (!ai4seo_exists_$( $accept_tos_checkbox ) || !ai4seo_exists_$( $accept_button )) {
		return;
	}

	// Move the TOS checkbox behavior out of inline markup so repeated init can replace one handler.
	$accept_tos_checkbox.off( 'change.ai4seo-tos-accept-button-state' );
	$accept_tos_checkbox.on(
        'change.ai4seo-tos-accept-button-state',
        function () {
		ai4seo_refresh_tos_accept_button_state();
        }
    );

	// Refresh immediately for restored checkbox states and markup injected before this initializer runs.
	ai4seo_refresh_tos_accept_button_state();
}

// =========================================================================================== \\

/**
 * Toggle the terms of service accept button based on the agreement checkbox state
 */
function ai4seo_refresh_tos_accept_button_state() {
	const $accept_tos_checkbox = ai4seo_normalize_$( '.ai4seo-accept-tos-checkbox' );

	if (!ai4seo_exists_$( $accept_tos_checkbox )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$accept_tos_checkbox\" missing in ai4seo_refresh_tos_accept_button_state() \u2014 cannot verify terms acceptance.' );
		return;
	}

	const accepted_tos = $accept_tos_checkbox.prop( 'checked' );
	const $accept_button = ai4seo_normalize_$( '.ai4seo-accept-tos-button' );

	if (!ai4seo_exists_$( $accept_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$accept_button\" missing in ai4seo_refresh_tos_accept_button_state() \u2014 cannot update terms acceptance state.' );
		return;
	}

	if (accepted_tos) {
		// remove ai4seo-inactive-button class, add ai4seo-primary-button class.
		$accept_button.removeClass( 'ai4seo-inactive-button' ).addClass( 'ai4seo-primary-button' );
	} else {
		// add ai4seo-inactive-button class, remove ai4seo-primary-button class.
		$accept_button.addClass( 'ai4seo-inactive-button' ).removeClass( 'ai4seo-primary-button' );
	}
}

// =========================================================================================== \\

function ai4seo_check_if_user_accepted_tos() {
	const $accept_tos_checkbox = ai4seo_normalize_$( '.ai4seo-accept-tos-checkbox' );

	if (!ai4seo_exists_$( $accept_tos_checkbox )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$accept_tos_checkbox\" missing in ai4seo_check_if_user_accepted_tos() \u2014 cannot verify terms acceptance.' );
		return false;
	}

	const accepted_tos = $accept_tos_checkbox.prop( 'checked' );

	if (!accepted_tos) {
		ai4seo_show_accept_terms_notification_modal();
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_show_accept_terms_notification_modal() {
	ai4seo_open_notification_modal( wp.i18n.__( 'Attention!', 'ai-for-seo' ), wp.i18n.__( 'Please accept the terms of service first.', 'ai-for-seo' ) );

	// add ai4seo-shake-animation to the checkbox and remove it after 3 seconds.
	const $accept_tos_checkbox_wrapper = ai4seo_normalize_$( '.ai4seo-accept-tos-checkbox-wrapper' );

	if (ai4seo_exists_$( $accept_tos_checkbox_wrapper )) {
		$accept_tos_checkbox_wrapper.addClass( 'ai4seo-shake-animation' );
	}

	setTimeout(
        function () {
		const $checkbox_wrapper = ai4seo_normalize_$( '.ai4seo-accept-tos-checkbox-wrapper' );

		if (ai4seo_exists_$( $checkbox_wrapper )) {
			$checkbox_wrapper.removeClass( 'ai4seo-shake-animation' );
		}
        },
        3000
    );
}

// =========================================================================================== \\

/**
 * Let the user accept tos, using ajax
 */
function ai4seo_accept_tos(reload_page = true) {
	if (!ai4seo_check_if_user_accepted_tos()) {
		return;
	}

	// check state of checkbox "ai4seo-accept-enhanced-reporting-checkbox".
	const $accept_enhanced_reporting_checkbox = ai4seo_normalize_$( '.ai4seo-accept-enhanced-reporting-checkbox' );

	if (!ai4seo_exists_$( $accept_enhanced_reporting_checkbox )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$accept_enhanced_reporting_checkbox\" missing in ai4seo_accept_tos() \u2014 enhanced reporting consent not updated.' );
		return;
	}

	let accepted_enhanced_reporting = $accept_enhanced_reporting_checkbox.prop( 'checked' );

	ai4seo_add_loading_html_to_element( '.ai4seo-button' );

	ai4seo_perform_ajax_call( 'ai4seo_accept_tos', {accepted_enhanced_reporting: accepted_enhanced_reporting} )
		.then(
            response => {
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1213181225, error );
            }
        )
		.finally(
            () => {
			// reload page.
			if (reload_page) {
				ai4seo_safe_page_load();
			} else {
				ai4seo_remove_loading_html_from_element( '.ai4seo-button' );
			}
            }
        );
}

// =========================================================================================== \\

function ai4seo_does_user_need_to_accept_tos_toc_and_pp() {
	return ai4seo_get_localization_parameter( 'ai4seo_does_user_need_to_accepted_tos_toc_and_pp' );
}


// ___________________________________________________________________________________________ \\
// === SETTINGS (PAGE) ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_toggle_sync_only_these_metadata_container() {
	let $sync_only_these_metadata_container = ai4seo_normalize_$( '#ai4seo-sync-only-these-metadata-container' );

	if (!ai4seo_exists_$( $sync_only_these_metadata_container )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$sync_only_these_metadata_container\" missing in ai4seo_toggle_sync_only_these_metadata_container() \u2014 cannot toggle metadata scope.' );
		return;
	}

	// if any checkbox with class ai4seo_third_party_sync_checkbox is checked, display the container.
	const $checked_sync_checkboxes = ai4seo_normalize_$( '.ai4seo_third_party_sync_checkbox:checked' );

	if (ai4seo_exists_$( $checked_sync_checkboxes )) {
		$sync_only_these_metadata_container.removeClass( 'ai4seo-display-none' );
	} else {
		$sync_only_these_metadata_container.addClass( 'ai4seo-display-none' );
	}
}

// =========================================================================================== \\

function ai4seo_init_alt_text_injection_settings() {
	const $alt_text_injection_setting_toggle = ai4seo_normalize_$( '.ai4seo-alt-text-injection-toggle' );
	const $js_alt_text_injection_setting_container = ai4seo_normalize_$( '#ai4seo-js-alt-text-injection-setting' );

	if (!ai4seo_exists_$( $alt_text_injection_setting_toggle ) || !ai4seo_exists_$( $js_alt_text_injection_setting_container )) {
		return;
	}

	const $advanced_settings_state = ai4seo_normalize_$( '#ai4seo-advanced-setting-state' );

	const ai4seo_toggle_js_alt_text_injection_visibility = function () {
		const are_advanced_settings_hidden = ai4seo_exists_$( $advanced_settings_state ) && $advanced_settings_state.val() !== 'show';

		if (are_advanced_settings_hidden) {
			$js_alt_text_injection_setting_container.addClass( 'ai4seo-js-alt-text-setting-hidden' );
			return;
		}

		if ($alt_text_injection_setting_toggle.is( ':checked' )) {
			$js_alt_text_injection_setting_container.removeClass( 'ai4seo-js-alt-text-setting-hidden' );

			// remove display attribute to fix visibility issues.
			$js_alt_text_injection_setting_container.css( 'display', '' );
		} else {
			$js_alt_text_injection_setting_container.addClass( 'ai4seo-js-alt-text-setting-hidden' );
		}
	};

	$alt_text_injection_setting_toggle.off( 'change.ai4seo-alt-text-injection-toggle' );
	$alt_text_injection_setting_toggle.on( 'change.ai4seo-alt-text-injection-toggle', ai4seo_toggle_js_alt_text_injection_visibility );

	const $show_advanced_button = ai4seo_normalize_$( '#ai4seo-show-advanced-settings-container #ai4seo-toggle-advanced-button' );
	const $hide_advanced_button = ai4seo_normalize_$( '#ai4seo-hide-advanced-settings-container #ai4seo-toggle-advanced-button' );

	const ai4seo_deferred_js_alt_text_injection_toggle = function () {
		setTimeout( ai4seo_toggle_js_alt_text_injection_visibility, 50 );
	};

	if (ai4seo_exists_$( $show_advanced_button )) {
		$show_advanced_button.off( 'click.ai4seo-alt-injection' );
		$show_advanced_button.on( 'click.ai4seo-alt-injection', ai4seo_deferred_js_alt_text_injection_toggle );
	}

	if (ai4seo_exists_$( $hide_advanced_button )) {
		$hide_advanced_button.off( 'click.ai4seo-alt-injection' );
		$hide_advanced_button.on( 'click.ai4seo-alt-injection', ai4seo_deferred_js_alt_text_injection_toggle );
	}

	ai4seo_toggle_js_alt_text_injection_visibility();
}

// =========================================================================================== \\

function ai4seo_init_advanced_settings() {
	const $advanced_setting_state = ai4seo_normalize_$( '#ai4seo-advanced-setting-state' );

	if (!ai4seo_exists_$( $advanced_setting_state )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': element \"$advanced_setting_state\" missing in ai4seo_init_advanced_settings() \u2014 advanced preference state not saved.');.
		return;
	}

	ai4seo_console_debug( ai4seo_get_plugin_name() + ': Initializing advanced settings view based on saved state.' );

	let advanced_setting_state = $advanced_setting_state.val();

	if (advanced_setting_state === 'show') {
		// Show advanced settings.
		ai4seo_show_advanced_settings();
	} else {
		// Hide advanced settings.
		ai4seo_hide_advanced_settings();
	}
}

// =========================================================================================== \\

/**
 * Show a recovery prompt when the current hash names a Settings section that Advanced Settings hides.
 *
 * @param {jQuery} $navigation The Settings section navigation landmark.
 * @param {jQuery} $links The navigation links that share the Settings heading targets.
 */
function ai4seo_update_settings_section_navigation_hidden_target_notice( $navigation, $links ) {
	// Keep the optional recovery prompt scoped to the Settings landmark so other admin pages stay unaffected.
	const $notice = $navigation.find( '#ai4seo-settings-section-navigation-hidden-target-notice' );

	// Exit when the current page has no recovery prompt markup.
	if (!ai4seo_exists_$( $notice )) {
		return;
	}

	// A hash can name a visible section, an unrelated element, or an Advanced Settings target that is currently hidden.
	const target_hash = window.location.hash;
	const $hash_link = $links.filter(
		function () {
			return target_hash === ai4seo_normalize_$( this ).attr( 'href' );
		}
	);
	const target = (typeof target_hash === 'string' && target_hash.charAt( 0 ) === '#') ? document.getElementById( target_hash.substring( 1 ) ) : null;
	const $target = ai4seo_normalize_$( target );
	const should_show_notice = ai4seo_exists_$( $hash_link ) && ( !ai4seo_exists_$( $target ) || !$target.is( ':visible' ) );

	// Avoid repeated style writes during scroll refreshes when the recovery state has not changed.
	if (should_show_notice && $notice.hasClass( 'ai4seo-display-none' )) {
		$notice.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	} else if (!should_show_notice && !$notice.hasClass( 'ai4seo-display-none' )) {
		$notice.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	}
}

// =========================================================================================== \\

/**
 * Focus a Settings navigation target and retain a neutral visual marker for its destination context.
 *
 * @param {HTMLElement} target The visible Settings section heading to focus.
 */
function ai4seo_focus_settings_section_navigation_target( target ) {
	// Normalize the target so a missing or hidden destination cannot receive focus or a stale marker.
	const $target = ai4seo_normalize_$( target );

	if (!ai4seo_exists_$( $target ) || !$target.is( ':visible' )) {
		return;
	}

	// Keep one destination marker aligned with the single current navigator link.
	ai4seo_normalize_$( '.ai4seo-settings-section-heading' ).removeClass( 'ai4seo-settings-section-heading-focused' );
	$target.addClass( 'ai4seo-settings-section-heading-focused' );
	target.focus( { preventScroll: true } );
}

// =========================================================================================== \\

/**
 * Keep the Settings section navigation aligned with the visible form section.
 */
function ai4seo_refresh_settings_section_navigation() {
	// Limit state updates to the Settings navigator so shared initialization can safely run on every admin page.
	const $navigation = ai4seo_normalize_$( '.ai4seo-settings-section-navigation' );

	// Exit on other plugin pages that do not render Settings navigation markup.
	if (!ai4seo_exists_$( $navigation )) {
		return;
	}

	// Advanced Settings hides links with the same class as their sections, so only visible links can become current.
	const $links = $navigation.find( '.ai4seo-settings-section-navigation-link' );
	const $visible_links = $links.filter( ':visible' );

	// Offer an explicit reveal action instead of silently changing the Advanced Settings preference for a hidden hash target.
	ai4seo_update_settings_section_navigation_hidden_target_notice( $navigation, $links );

	if (!ai4seo_exists_$( $visible_links )) {
		// Leave no stale landmark state behind when every tracked section is hidden.
		$links.removeAttr( 'aria-current' );
		return;
	}

	// Use the sticky navigator edge as the shared reference point for every section heading.
	const navigation_bottom = $navigation[0].getBoundingClientRect().bottom;
	const section_activation_tolerance = 64;
	let $active_link = null;

	// Select the last visible section reached above the navigator, which remains stable while its fields fill the viewport.
	$visible_links.each(
		function () {
			const $link = ai4seo_normalize_$( this );
			const target_hash = $link.attr( 'href' );

			// Native links must resolve to an in-page heading before they can participate in viewport tracking.
			if (typeof target_hash !== 'string' || target_hash.charAt( 0 ) !== '#') {
				return;
			}

			const target = document.getElementById( target_hash.substring( 1 ) );
			const $target = ai4seo_normalize_$( target );

			// Ignore missing or hidden targets so advanced visibility never produces an invalid current link.
			if (!ai4seo_exists_$( $target ) || !$target.is( ':visible' )) {
				return;
			}

			if (target.getBoundingClientRect().top <= navigation_bottom + section_activation_tolerance) {
				$active_link = $link;
			}
		}
	);

	// Fall back to the first visible link when the scroll position precedes every tracked heading.
	if (!ai4seo_exists_$( $active_link )) {
		$active_link = $visible_links.eq( 0 );
	}

	// Maintain one landmark location value for assistive technology and the selected link style.
	$links.removeAttr( 'aria-current' );
	$active_link.attr( 'aria-current', 'location' );
}

// =========================================================================================== \\

/**
 * Initialize sticky Settings section navigation behavior.
 */
function ai4seo_init_settings_section_navigation() {
	// Bind only when the Settings page has rendered the navigation landmark.
	const $navigation = ai4seo_normalize_$( '.ai4seo-settings-section-navigation' );

	// Exit on other plugin pages that do not render Settings navigation markup.
	if (!ai4seo_exists_$( $navigation )) {
		return;
	}

	// Use the same link collection for native-anchor focus handling and current-section tracking.
	const $links = $navigation.find( '.ai4seo-settings-section-navigation-link' );
	const $show_advanced_settings_button = $navigation.find( '#ai4seo-settings-section-navigation-show-advanced-settings' );
	const $window = ai4seo_normalize_$( window );

	// Skip binding when the rendered page cannot provide the complete link or viewport contract.
	if (!ai4seo_exists_$( $links ) || !ai4seo_exists_$( $window )) {
		return;
	}

	// Coalesce scroll and layout changes while preserving immediate updates after deliberate navigation.
	const debounced_refresh = ai4seo_debounce( ai4seo_refresh_settings_section_navigation, 50 );

	// Preserve native hash/history behavior, then move focus after the browser completes anchor scrolling.
	$links.off( 'click.ai4seo-settings-section-navigation' ).on(
		'click.ai4seo-settings-section-navigation',
		function () {
			const $link = ai4seo_normalize_$( this );
			const target_hash = $link.attr( 'href' );

			// Leave malformed links to their native browser behavior instead of manufacturing a focus target.
			if (typeof target_hash !== 'string' || target_hash.charAt( 0 ) !== '#') {
				return;
			}

			const target = document.getElementById( target_hash.substring( 1 ) );
			const $target = ai4seo_normalize_$( target );

			// Hidden advanced sections cannot receive focus while their matching navigator links are unavailable.
			if (!ai4seo_exists_$( $target ) || !$target.is( ':visible' )) {
				return;
			}

			// Focus the matching heading without a second scroll so keyboard users receive the destination context.
			setTimeout(
				function () {
					ai4seo_focus_settings_section_navigation_target( target );
					ai4seo_refresh_settings_section_navigation();
				},
				0
			);
		}
	);

	// Let users reveal the hidden hash target through the existing preference flow, then restore its native anchor destination.
	$show_advanced_settings_button.off( 'click.ai4seo-settings-section-navigation' ).on(
		'click.ai4seo-settings-section-navigation',
		function () {
			const target_hash = window.location.hash;
			const target = (typeof target_hash === 'string' && target_hash.charAt( 0 ) === '#') ? document.getElementById( target_hash.substring( 1 ) ) : null;
			const $target = ai4seo_normalize_$( target );

			// Ignore a stale prompt if its hash no longer resolves to a Settings heading.
			if (!ai4seo_exists_$( $target )) {
				return;
			}

			ai4seo_show_advanced_settings( true );

			// Match the existing Settings fade duration before scrolling and focusing the newly visible target.
			setTimeout(
				function () {
					if (!$target.is( ':visible' )) {
						return;
					}

					target.scrollIntoView( { block: 'start' } );
					ai4seo_focus_settings_section_navigation_target( target );
					ai4seo_refresh_settings_section_navigation();
				},
				350
			);
		}
	);

	// Reuse the debounced refresh for viewport, hash, and passive-scroll changes without writing browser history.
	$window
		.off( 'scroll.ai4seo-settings-section-navigation' )
		.on( 'scroll.ai4seo-settings-section-navigation', debounced_refresh );
	$window
		.off( 'resize.ai4seo-settings-section-navigation' )
		.on( 'resize.ai4seo-settings-section-navigation', debounced_refresh );
	$window
		.off( 'hashchange.ai4seo-settings-section-navigation' )
		.on( 'hashchange.ai4seo-settings-section-navigation', debounced_refresh );

	// Cover the initial DOM position and the completed layout after the page's other Settings initializers finish.
	ai4seo_refresh_settings_section_navigation();
	setTimeout( ai4seo_refresh_settings_section_navigation, 100 );
}

// =========================================================================================== \\

/**
 * Let the user show advanced settings
 */
function ai4seo_show_advanced_settings(show_fade_animation = false) {
	// Show advanced settings and swap buttons.
	const $advanced_settings = ai4seo_normalize_$( '.ai4seo-is-advanced-setting' );
	const $show_advanced_settings_container = ai4seo_normalize_$( '#ai4seo-show-advanced-settings-container' );
	const $hide_advanced_settings_container = ai4seo_normalize_$( '#ai4seo-hide-advanced-settings-container' );
	const $advanced_setting_state = ai4seo_normalize_$( '#ai4seo-advanced-setting-state' );

	if (!ai4seo_exists_$( $advanced_settings ) || !ai4seo_exists_$( $show_advanced_settings_container ) || !ai4seo_exists_$( $hide_advanced_settings_container ) || !ai4seo_exists_$( $advanced_setting_state )) {
		console.error( ai4seo_get_plugin_name() + ': Advanced settings containers missing in ai4seo_show_advanced_settings() — cannot reveal advanced options.' );
		return;
	}

	$advanced_settings.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	$show_advanced_settings_container.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	$hide_advanced_settings_container.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	$advanced_setting_state.val( 'show' );

	// Update visible navigation entries as soon as advanced sections return to the layout.
	ai4seo_refresh_settings_section_navigation();

		if (show_fade_animation) {
			ai4seo_set_unsaved_changes_state( $advanced_setting_state, true );
			ai4seo_show_success_toast( wp.i18n.__( 'Advanced settings are now shown. Save changes to keep this preference.', 'ai-for-seo' ) );

			const $non_advanced_sections = ai4seo_normalize_$( '.ai4seo-form-section:not(.ai4seo-is-advanced-setting)' );

			if (!ai4seo_exists_$( $non_advanced_sections )) {
			console.warn( ai4seo_get_plugin_name() + ': elements \"$non_advanced_sections\" missing in ai4seo_show_advanced_settings() \u2014 cannot toggle advanced view.' );
			return;
		}

			$non_advanced_sections.fadeOut(
				0,
				function () {
					const $this_section = ai4seo_normalize_$( this );

					if (!ai4seo_exists_$( $this_section )) {
						console.warn( ai4seo_get_plugin_name() + ': element \"$this_section\" missing in ai4seo_show_advanced_settings() \u2014 cannot toggle advanced view.' );
					}

					$this_section.fadeIn( 300 );
				}
			);

			// Refresh after the shared section fade completes so the viewport measurement uses final positions.
			setTimeout( ai4seo_refresh_settings_section_navigation, 350 );
		}
	}

// =========================================================================================== \\

/**
 * Let the user hide advanced settings
 */
function ai4seo_hide_advanced_settings(show_fade_animation = false) {
	// Hide advanced settings and swap buttons.
	const $advanced_settings = ai4seo_normalize_$( '.ai4seo-is-advanced-setting' );
	const $show_advanced_settings_container = ai4seo_normalize_$( '#ai4seo-show-advanced-settings-container' );
	const $hide_advanced_settings_container = ai4seo_normalize_$( '#ai4seo-hide-advanced-settings-container' );
	const $advanced_setting_state = ai4seo_normalize_$( '#ai4seo-advanced-setting-state' );

	if (!ai4seo_exists_$( $advanced_settings ) || !ai4seo_exists_$( $show_advanced_settings_container ) || !ai4seo_exists_$( $hide_advanced_settings_container ) || !ai4seo_exists_$( $advanced_setting_state )) {
		console.warn( ai4seo_get_plugin_name() + ': Advanced settings containers missing in ai4seo_hide_advanced_settings() — cannot conceal advanced options.' );
		return;
	}

	$advanced_settings.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	$show_advanced_settings_container.removeClass( 'ai4seo-display-none' ).show().css( 'display', '' );
	$hide_advanced_settings_container.hide().addClass( 'ai4seo-display-none' ).css( 'display', '' );
	$advanced_setting_state.val( 'hide' );

	// Remove advanced navigation entries from current-section tracking as soon as the preference hides them.
	ai4seo_refresh_settings_section_navigation();

	if (show_fade_animation) {
		ai4seo_set_unsaved_changes_state( $advanced_setting_state, true );
		ai4seo_show_success_toast( wp.i18n.__( 'Advanced settings are now hidden. Save changes to keep this preference.', 'ai-for-seo' ) );

		const $non_advanced_sections = ai4seo_normalize_$( '.ai4seo-form-section:not(.ai4seo-is-advanced-setting)' );

		if (!ai4seo_exists_$( $non_advanced_sections )) {
			console.warn( ai4seo_get_plugin_name() + ': elements \"$non_advanced_sections\" missing in ai4seo_hide_advanced_settings() \u2014 cannot toggle advanced view.' );
			return;
		}

			$non_advanced_sections.fadeOut(
				0,
				function () {
					const $this_section = ai4seo_normalize_$( this );

					if (!ai4seo_exists_$( $this_section )) {
						console.warn( ai4seo_get_plugin_name() + ': element \"$this_section\" missing in ai4seo_hide_advanced_settings() \u2014 cannot toggle advanced view.' );
					}

					$this_section.fadeIn( 300 );
				}
			);

			// Refresh after the shared section fade completes so the viewport measurement uses final positions.
			setTimeout( ai4seo_refresh_settings_section_navigation, 350 );
		}
	}

// =========================================================================================== \\

/**
 * Show confirmation dialog and restore default settings via Ajax
 */
function ai4seo_restore_default_settings($button) {
	if (ai4seo_exists_$( $button )) {
		$button = ai4seo_normalize_$( $button );
	}

	// Show confirmation dialog.
	let headline = wp.i18n.__( 'Restore Default Settings', 'ai-for-seo' );
	let content = wp.i18n.__( 'Are you sure you want to restore all settings to their default values?', 'ai-for-seo' );
	content += '<br><br>';
	content += wp.i18n.__( '<strong>Note:</strong> This action will reset all settings on this page to their default values. This cannot be undone.', 'ai-for-seo' );

	let confirm_button = "<button type='button' class='ai4seo-button ai4seo-abort-button ai4seo-lockable' onclick='ai4seo_perform_restore_default_settings();'>" + wp.i18n.__( 'Yes, restore defaults', 'ai-for-seo' ) + '</button>';
	let cancel_button = "<button type='button' class='ai4seo-button ai4seo-primary-button ai4seo-lockable' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Cancel', 'ai-for-seo' ) + '</button>';

	ai4seo_open_notification_modal( headline, content, confirm_button + cancel_button );
}

// =========================================================================================== \\

/**
 * Perform the actual restore default settings Ajax call
 */
function ai4seo_perform_restore_default_settings() {
	// check if we have unsaved changes -> ignore warnings.
	if (ai4seo_has_unsaved_changes()) {
		ai4seo_set_unsaved_changes_state( jQuery( '.ai4seo-unsaved-changes-warnings' ), false );
	}

	// Show loading indicator.
	ai4seo_lock_and_disable_lockable_input_fields();

	if (ai4seo_exists_$( '.ai4seo-lockable' )) {
		ai4seo_add_loading_html_to_element( ai4seo_normalize_$( '.ai4seo-lockable' ) );
	}

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Restoring default settings...', 'ai-for-seo' ) );

	// Perform Ajax call.
	ai4seo_perform_ajax_call( 'ai4seo_restore_default_settings' )
		.then(
            response => {
			// Show success message.
			ai4seo_show_success_toast( wp.i18n.__( 'Default settings restored successfully. Reloading page...', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1313181225, error );
            }
        )
		.finally(
            () => {
			// Remove loading indicator.
			ai4seo_unlock_and_enable_lockable_input_fields();
			ai4seo_remove_loading_html_from_element( ai4seo_normalize_$( '.ai4seo-lockable' ) );
			setTimeout( () => ai4seo_safe_page_load(), 1000 );
            }
        );
}

// =========================================================================================== \\

function ai4seo_validate_settings_inputs(input_values) {
	// Check if prefix- and suffix-input-fields exist
	// Loop through all prefix- and suffix-input-fields and make sure that the content doesn't exceed the max-length.
	const $prefix_suffix_inputs = ai4seo_normalize_$( 'input.ai4seo-prefix-suffix-setting-textfield' );

	if (!ai4seo_exists_$( $prefix_suffix_inputs )) {
		console.error( ai4seo_get_plugin_name() + ': elements \"$prefix_suffix_inputs\" missing in ai4seo_validate_settings_inputs() \u2014 prefix/suffix fields cannot be validated.' );
		return false;
	}

	let has_invalid_input = false;

	$prefix_suffix_inputs.each(
        function () {
		const $this_input = ai4seo_normalize_$( this );

		if (!ai4seo_exists_$( $this_input )) {
			console.error( ai4seo_get_plugin_name() + ': element \"$input_field\" missing in ai4seo_validate_settings_inputs() \u2014 validation skipped.' );
			return;
		}

		const this_input_value = $this_input.val();
		const max_length = parseInt( $this_input.attr( 'maxlength' ), 10 ) || 48;

		if (this_input_value.length > 0 && this_input_value.length > max_length) {
			ai4seo_show_warning_toast( wp.i18n.__( "Please don't exceed the maximum length-requirement for prefix- and suffix-input-fields (max. 48 characters).", 'ai-for-seo' ) );
			console.warn( ai4seo_get_plugin_name() + ': Validation failed for prefix/suffix input field with value \"' + this_input_value + '\" in ai4seo_validate_settings_inputs() \u2014 maximum length exceeded.' );
			has_invalid_input = true;
			return false;
		}
        }
    );

	if (has_invalid_input) {
		return false;
	}

	if (!ai4seo_validate_custom_instruction_input_values( input_values, 3316141025 )) {
		return false;
	}

	return true;
}


// ___________________________________________________________________________________________ \\
// === AJAX ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Perform a WP-admin AJAX call with robust JSON handling and recoverability.
 *
 * @param {string}  action
 * @param {Object}  data
 * @param {boolean} auto_check_response
 * @param {Object}  additional_error_list
 * @param {boolean} show_generic_error
 * @param {boolean} add_contact_us_link
 * @returns {Promise<any>}
 */
function ai4seo_perform_ajax_call(action, data = {}, auto_check_response = true, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true) {
	// 1) Validate the action early
	const invalid = ai4seo_validate_ajax_action( action );

	if (invalid) {
		ai4seo_show_generic_error_toast( invalid.code );
		return Promise.reject( invalid );
	}

	// 2) Build payload
	const payload = ai4seo_build_ajax_payload( action, data );

	// 3) Execute request
	return ai4seo_execute_ajax_request( payload )
		.then(
            (response) => {
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': AJAX Response for action "' + action + '":', response );
			// 4) Unified success handling
			return ai4seo_handle_ajax_success(
                {
				response,
				auto_check_response,
				additional_error_list,
				show_generic_error,
				add_contact_us_link,
                }
            );
            }
        )
		.catch(
            (response) => {
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': AJAX Error Response for action "' + action + '":', response );
			// 5) Try to recover JSON from non-JSON response
			const recovered = ai4seo_attempt_recover_json_from_ajax_error( response?.jqXHR );
			if (recovered) {
				return ai4seo_handle_ajax_success(
                    {
					response: recovered,
					auto_check_response,
					additional_error_list,
					show_generic_error,
					add_contact_us_link,
                    }
                );
			}
			return Promise.reject(
				ai4seo_normalize_ajax_error( response )
			);
            }
        );
}

// =========================================================================================== \\

/**
 * Validate the action against the allowlist.
 *
 * @param {string} action
 * @returns {null|{error:string, code:number, message:string}}
 */
function ai4seo_validate_ajax_action(action) {
	const allowed_ajax_actions = ai4seo_get_localization_parameter( 'ai4seo_allowed_ajax_actions' );

	if (!Array.isArray( allowed_ajax_actions ) || !allowed_ajax_actions.includes( action )) {
		return {
			error: 'Invalid action',
			code: 4317101224,
			message: wp.i18n.__( 'AJAX action not allowed', 'ai-for-seo' ) + `: ${action}`,
		};
	}
	return null;
}

// =========================================================================================== \\

/**
 * Build the AJAX payload including nonce & action.
 *
 * @param {string} action
 * @param {Object} data
 * @returns {Object}
 */
function ai4seo_build_ajax_payload(action, data) {
	const nonce = ai4seo_get_ajax_nonce();
	const reserved_keys = [
		'action',
		AI4SEO_GLOBAL_NONCE_IDENTIFIER,
		'security',
		'ai4seo_ajax_payload_complete',
	];

	const payload = {
		action: action,
		[AI4SEO_GLOBAL_NONCE_IDENTIFIER]: nonce,
		security: nonce,
	};

	for (const [key, value] of Object.entries( data || {} )) {
		if (reserved_keys.includes( key )) {
			console.warn( ai4seo_get_plugin_name() + ': AJAX payload skipped reserved key: ' + key );
			continue;
		}

		payload[key] = value;
	}

	payload.ai4seo_ajax_payload_complete = '1';

	return payload;
}

// =========================================================================================== \\

/**
 * Execute the actual AJAX request (POST JSON to admin-ajax).
 * Isolated for testability and reuse.
 *
 * @param {Object} payload
 * @returns {Promise<any>}
 */
function ai4seo_execute_ajax_request(payload) {
	let ai4seo_admin_ajax_url = ai4seo_get_admin_ajax_url();

	return new Promise(
        (resolve, reject) => {
		jQuery
			.ajax(
                {
				url: ai4seo_admin_ajax_url,
				method: 'POST',
				data: payload,
				cache: false,
                }
            )
			.done( (response) => resolve( response ) )
			.fail(
                (jqXHR, textStatus, errorThrown) =>
				reject(
                    {
					jqXHR,
					textStatus,
					errorThrown,
					action: payload?.action || '',
                    }
                )
			);
        }
    );
}

// =========================================================================================== \\

/**
 * Centralized success path (also used by recovered JSON).
 * Applies optional response checking and normalizes the resolved data.
 *
 * @param {Object} opts
 * @param {any}    opts.response
 * @param {boolean}opts.auto_check_response
 * @param {Object} opts.additional_error_list
 * @param {boolean}opts.show_generic_error
 * @param {boolean}opts.add_contact_us_link
 * @returns {any|Promise<any>}
 */
function ai4seo_handle_ajax_success({
	                                    response,
	                                    auto_check_response,
	                                    additional_error_list,
	                                    show_generic_error,
	                                    add_contact_us_link
                                    }) {
	const normalized_response = ai4seo_get_normalized_ajax_response_data( response );

	// If auto-checking is disabled, resolve raw (but normalized) data.
	if (!auto_check_response) {
		return normalized_response;
	}

	// Use the existing checker; if it returns true, resolve; else reject.
	if (ai4seo_check_response( response, additional_error_list, show_generic_error, add_contact_us_link )) {
		return normalized_response;
	}

	// Make sure to reject with something useful if check failed.
	const error_object = {
		success: false,
		error: 'invalid_response',
		code: 4217101225,
		details: normalized_response,
	};

	return Promise.reject( error_object );
}

// =========================================================================================== \\

/**
 * Normalize how we resolve data (WP style `{ success, data }` vs raw).
 *
 * @param {any} response
 * @returns {any}
 */
function ai4seo_get_normalized_ajax_response_data(response) {
	if (response && typeof response === 'object' && 'data' in response) {
		return response.data;
	}

	return response;
}

// =========================================================================================== \\

function ai4seo_unslash_string(str) {
	return str.replace( /\\'/g, "'" ).replace( /\\"/g, '"' ).replace( /\\\\/g, '\\' );
}

// =========================================================================================== \\

function ai4seo_unslash_object(obj) {
	if (typeof obj !== 'object' || obj === null) {
		return obj;
	}

	const unslashedObj = Array.isArray( obj ) ? [] : {};

	for (const key in obj) {
		if (Object.prototype.hasOwnProperty.call( obj, key )) {
			const value = obj[key];
			if (typeof value === 'string') {
				unslashedObj[key] = ai4seo_unslash_string( value );
			} else if (typeof value === 'object' && value !== null) {
				unslashedObj[key] = ai4seo_unslash_object( value );
			} else {
				unslashedObj[key] = value;
			}
		}
	}

	return unslashedObj;
}

// =========================================================================================== \\

/**
 * Attempt to recover a JSON object from a failed jqXHR responseText.
 * Trims noise before/after the first/last brace and tries to parse.
 *
 * @param {jqXHR} jqXHR
 * @returns {null|Object}
 */
function ai4seo_attempt_recover_json_from_ajax_error(jqXHR) {
	try {
		const raw =
			jqXHR && typeof jqXHR.responseText === 'string'
				? jqXHR.responseText
				: '';

		if (!raw) {
			return null;
		}

		const first_brace = raw.indexOf( '{' );
		const last_brace = raw.lastIndexOf( '}' );
		if (first_brace === -1 || last_brace === -1 || last_brace <= first_brace) {
			return null;
		}

		const sliced = raw.slice( first_brace, last_brace + 1 );
		const parsed = JSON.parse( sliced );

		// Must be an object to be considered valid recovery.
		if (parsed && typeof parsed === 'object') {
			return parsed;
		}

		return null;
	} catch (_) {
		return null;
	}
}

// =========================================================================================== \\

/**
 * Log special WP "0" case (nonce/auth problem) for easier debugging.
 *
 * @param {jqXHR} jqXHR
 */
function ai4seo_log_special_zero_ajax_error(jqXHR) {
	const raw =
		jqXHR && typeof jqXHR.responseText === 'string'
			? jqXHR.responseText.trim()
			: '';

	if (raw === '0') {
		console.warn( ai4seo_get_plugin_name() + ': Server responded with "0" (possible POST truncation, missing action handler, missing action parameter, or nonce/auth issue).' );
	}
}

// =========================================================================================== \\

/**
 * Build a consistent, compact error object for callers.
 * Supports:
 *  - jQuery AJAX failCtx
 *  - AI4SEO internal error objects
 *  - Defensive fallbacks
 *
 * @param {any} response
 * @returns {{success:false, error:string, code:number, details:any}}
 */
function ai4seo_normalize_ajax_error(response) {
	// ---------------------------------------------------------------------
	// 1) Already-normalized AI4SEO error → pass through safely
	// ---------------------------------------------------------------------
	if (
		response &&
		typeof response === 'object' &&
		response.success === false &&
		typeof response.error === 'string'
	) {
		return {
			success: false,
			error: response.error,
			code: ai4seo_sanitize_error_code(
				response.code || 4217101225,
				4217101226
			),
			details: response.details ?? null,
		};
	}

	// ---------------------------------------------------------------------
	// 2) Extract typical jQuery AJAX failCtx
	// ---------------------------------------------------------------------
	const {jqXHR = {}, textStatus, errorThrown, action = ''} = response || {};
	let raw = '';
	let parsed = null;

	if (jqXHR && typeof jqXHR.responseText === 'string') {
		raw = jqXHR.responseText.trim();

		if (raw && (raw.startsWith( '{' ) || raw.startsWith( '[' ))) {
			try {
				parsed = JSON.parse( raw );
			} catch (e) {
				parsed = null;
			}
		}
	}

	const status = Number( jqXHR.status ) || 0;
	const readyState = Number( jqXHR.readyState ) || 0;

	// ---------------------------------------------------------------------
	// 3) Determine error message
	// ---------------------------------------------------------------------
	let error = 'Unknown error';

	if (typeof textStatus === 'string' && textStatus) {
		error = textStatus;
	} else if (parsed && typeof parsed.error === 'string') {
		error = parsed.error;
	} else if (typeof errorThrown === 'string' && errorThrown) {
		error = errorThrown;
	}

	if (status === 400 && raw === '0') {
		error = 'WordPress could not route the AJAX request';
	}

	// ---------------------------------------------------------------------
	// 4) Build details
	// ---------------------------------------------------------------------
	let details = null;

	if (errorThrown) {
		details = typeof errorThrown === 'string'
			? errorThrown
			: JSON.stringify( errorThrown, null, 2 );
	} else if (parsed) {
		details = parsed;
	} else if (raw) {
		details = raw.slice( 0, 800 );
	} else {
		details = 'No further details';
	}

	if (status === 400 && raw === '0') {
		details = 'Server returned "0". This usually means WordPress did not receive a usable action parameter. On large settings pages, PHP max_input_vars truncation is a common cause.';
	}

	// ---------------------------------------------------------------------
	// 5) Logging (dev-friendly, compact)
	// ---------------------------------------------------------------------
	console.groupCollapsed( ai4seo_get_plugin_name() + ': AJAX Error (' + (status || 'n/a') + ') - click for details' );
	if (action) {
		console.info( 'Action:', action );
	}
	console.info( 'HTTP status:', status || 'n/a' );
	console.error( 'Error:', error );
	console.warn( 'Details:', details );
	if (readyState !== 4) {
		console.info( 'XHR readyState:', readyState );
	}
	if (parsed) {
		console.info( 'Parsed JSON:', parsed );
	}
	ai4seo_log_special_zero_ajax_error( jqXHR );

	if (readyState === 0 && status === 0) {
		console.warn( ai4seo_get_plugin_name() + ': Request not sent. Possible network, CORS, SSL, or mixed-content issue.' );
	}

	console.groupEnd();

	// ---------------------------------------------------------------------
	// 6) Final normalized error
	// ---------------------------------------------------------------------
	return {
		success: false,
		error,
		code: ai4seo_sanitize_error_code(
			status || parsed?.code || 4217101224,
			4217101227
		),
		details,
	};
}


// =========================================================================================== \\

function ai4seo_get_ajax_nonce() {
	// try to get the nonce from the DOM.
	const $nonce_field = ai4seo_normalize_$( '#ai4seo_ajax_nonce' );

	if (ai4seo_exists_$( $nonce_field )) {
		const dom_value = $nonce_field.val();

		if (dom_value) {
			return dom_value;
		}
	}

	// if not found in the DOM, try to get it from the localization parameters.
	return ai4seo_get_localization_parameter( 'ai4seo_ajax_nonce' ) || '';
}

// =========================================================================================== \\

function ai4seo_lock_and_disable_lockable_input_fields() {
	// Define variable for all input-fields.
	const $all_input_fields = ai4seo_normalize_$( '.ai4seo-lockable' );
	const $lockable_links = $all_input_fields.filter( 'a' );

	if (!ai4seo_exists_$( $all_input_fields )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': no elements with \".ai4seo-lockable\" class found in ai4seo_lock_and_disable_lockable_input_fields() \u2014 no lockable inputs to update.' );
		return;
	}

	// Add css-class to disable input-fields.
	$all_input_fields.addClass( 'ai4seo-temporary-locked' );

	// Add disabled attribute to all input-fields.
	$all_input_fields.attr( 'disabled', 'disabled' );

	// Anchors do not support disabled, so make locked links non-interactive and expose their state.
	$lockable_links.attr( 'inert', 'inert' ).attr( 'aria-disabled', 'true' );
}

// =========================================================================================== \\

function ai4seo_unlock_and_enable_lockable_input_fields() {
	// Define variable for all input-fields.
	const $all_input_fields = ai4seo_normalize_$( '.ai4seo-temporary-locked' );
	const $lockable_links = $all_input_fields.filter( 'a' );

	if (!ai4seo_exists_$( $all_input_fields )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': no elements with \".ai4seo-temporary-locked\" class found in ai4seo_unlock_and_enable_lockable_input_fields() \u2014 no temporary locks to release.' );
		return;
	}

	// Remove css-class to disable input-fields.
	$all_input_fields.removeClass( 'ai4seo-temporary-locked' );

	// Remove the disabled state from all input-fields.
	$all_input_fields.prop( 'disabled', false );

	// Restore link focus and activation when the temporary lock is released.
	$lockable_links.removeAttr( 'inert' ).removeAttr( 'aria-disabled' );
}


// ___________________________________________________________________________________________ \\
// === HELP PAGE ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_init_debug_operation_form() {
	// Bind Help > Troubleshooting debug forms without assuming the page section is currently visible.
	const $debug_operation_forms = ai4seo_normalize_$( '.ai4seo-debug-operation-form' );

	if (!ai4seo_exists_$( $debug_operation_forms )) {
		return;
	}

	$debug_operation_forms.each(
		function () {
			// Scope lookups to the current form so future repeated debug forms cannot cross-toggle fields.
			const $form = ai4seo_normalize_$( this );
			const $operation_select = ai4seo_normalize_$( '.ai4seo-debug-operation-select', $form );
			const $submit_button = ai4seo_normalize_$( '.ai4seo-debug-operation-submit-button', $form );
			const prohibit_operation = $form.attr( 'data-ai4seo-prohibit-operation' ) || 'prohibit-ai-for-seo';

			if (!ai4seo_exists_$( $operation_select )) {
				return;
			}

			function update_debug_operation_form_target(selected_operation) {
				// Keep the separate-window behavior centralized because both change and submit events must agree.
				if (selected_operation === prohibit_operation) {
					$form.attr( 'target', '_blank' );
					$form.attr( 'rel', 'noopener' );
					return;
				}

				$form.removeAttr( 'target' );
				$form.removeAttr( 'rel' );
			}

			function update_debug_operation_fields() {
				const selected_operation = $operation_select.val() || '';
				const has_selected_operation = selected_operation !== '';
				const $field_groups = ai4seo_normalize_$( '.ai4seo-debug-operation-field', $form );

				// Keep submissions unavailable until a concrete operation is selected from the server-owned registry.
				if (ai4seo_exists_$( $submit_button )) {
					$submit_button.prop( 'disabled', !has_selected_operation );
					$submit_button.toggleClass( 'ai4seo-inactive-button', !has_selected_operation );
				}

				// Open only the prohibit operation in a separate window so normal operations can show the local completion view.
				update_debug_operation_form_target( selected_operation );

				if (!ai4seo_exists_$( $field_groups )) {
					return;
				}

				$field_groups.each(
					function () {
						// The server owns final validation; the data attributes only drive form ergonomics.
						const $field_group = ai4seo_normalize_$( this );
						const available_operations = String( $field_group.attr( 'data-ai4seo-operations' ) || '' ).split( ' ' );
						const is_visible = available_operations.includes( selected_operation );
						const is_required = String( $field_group.attr( 'data-ai4seo-required' ) || '' ) === '1';

						// The PHP markup starts hidden to avoid page-load flicker; JS only reveals the fields for the active operation.
						$field_group.toggleClass( 'ai4seo-display-none', !is_visible );
						$field_group.find( 'input, select, textarea' ).prop( 'required', is_visible && is_required );
					}
				);
			}

			function prepare_debug_operation_submit_target() {
				// Recheck the target at submit time so only the prohibit operation can leave the current Troubleshooting page.
				const selected_operation = $operation_select.val() || '';
				update_debug_operation_form_target( selected_operation );
			}

			// Rebind namespaced events to avoid duplicate handlers when admin markup is initialized again.
			$operation_select.off( 'change.ai4seo-debug-operation' );
			$operation_select.on( 'change.ai4seo-debug-operation', update_debug_operation_fields );
			$form.off( 'submit.ai4seo-debug-operation-target-window' );
			$form.on( 'submit.ai4seo-debug-operation-target-window', prepare_debug_operation_submit_target );
			update_debug_operation_fields();
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_debug_operation_completion_page() {
	// Bind the completion controls that swap back to the already-rendered Troubleshooting page.
	const $completion_pages = ai4seo_normalize_$( '.ai4seo-debug-operation-completion-page' );
	const $go_back_buttons = ai4seo_normalize_$( '.ai4seo-debug-operation-go-back-button' );

	if (ai4seo_exists_$( $completion_pages )) {
		$completion_pages.each(
			function () {
				// Completion pages can still leave the intermediate Help response when PHP headers cannot redirect.
				const $completion_page = ai4seo_normalize_$( this );
				const auto_redirect_url = $completion_page.attr( 'data-ai4seo-auto-redirect-url' ) || '';

				if (auto_redirect_url) {
					window.location.replace( auto_redirect_url );
				}
			}
		);
	}

	if (!ai4seo_exists_$( $go_back_buttons )) {
		return;
	}

	$go_back_buttons.off( 'click.ai4seo-debug-operation-completion' );
	$go_back_buttons.on(
		'click.ai4seo-debug-operation-completion',
		function (event) {
			event.preventDefault();

			// Resolve the paired completion view and hidden Help page from server-rendered data attributes.
			const $button = ai4seo_normalize_$( this );
			const target_id = $button.attr( 'data-ai4seo-target' ) || 'ai4seo-debug-operation-full-help-page';
			const completion_id = $button.attr( 'data-ai4seo-completion' ) || 'ai4seo-debug-operation-completion-page';
			const target_url = $button.attr( 'data-ai4seo-url' ) || '';
			const $target = ai4seo_normalize_$( '#' + target_id );
			const $completion = ai4seo_normalize_$( '#' + completion_id );

			if (!ai4seo_exists_$( $target )) {
				console.warn( ai4seo_get_plugin_name() + ': debug operation return target missing in ai4seo_init_debug_operation_completion_page() - cannot show Help page.' );
				return;
			}

			// Hide the completion view and reveal the already-loaded Help page without submitting or navigating again.
			if (ai4seo_exists_$( $completion )) {
				$completion.addClass( 'ai4seo-display-none' );
			}

			$target.removeClass( 'ai4seo-display-none' );

			// Restore the address bar to the canonical Troubleshooting URL after the in-place view swap.
			if (target_url && window.history && window.history.replaceState) {
				try {
					window.history.replaceState( null, '', target_url );
				} catch (error) {
					console.warn( ai4seo_get_plugin_name() + ': debug operation return URL could not be updated in ai4seo_init_debug_operation_completion_page().', error );
				}
			}

			// Keep the admin near the relevant Troubleshooting section after the hidden Help page is revealed.
			const troubleshooting_heading = document.getElementById( 'ai4seo-troubleshooting-section' );

			if (troubleshooting_heading && typeof troubleshooting_heading.scrollIntoView === 'function') {
				troubleshooting_heading.scrollIntoView();
			}
		}
	);
}

// =========================================================================================== \\

function ai4seo_init_help_page_debug_log_actions() {
	const $clear_debug_log_buttons = ai4seo_normalize_$( '.ai4seo-clear-debug-log-button' );

	if (!ai4seo_exists_$( $clear_debug_log_buttons )) {
		return;
	}

	$clear_debug_log_buttons.off( 'click.ai4seo-clear-debug-log' );
	$clear_debug_log_buttons.on(
        'click.ai4seo-clear-debug-log',
        function (event) {
		event.preventDefault();
		ai4seo_confirm_clear_debug_message_log();
        }
    );
}

// =========================================================================================== \\

function ai4seo_confirm_clear_debug_message_log() {
	const modal_message = wp.i18n.__( 'This will permanently remove all debug messages stored in the database. This action cannot be undone.', 'ai-for-seo' );
	const modal_footer = "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Abort', 'ai-for-seo' ) + '</button>'
		+ "<button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_clear_debug_message_log(this);'>" + wp.i18n.__( 'Clear log', 'ai-for-seo' ) + '</button>';

	ai4seo_open_notification_modal(
		wp.i18n.__( 'Please confirm', 'ai-for-seo' ),
		modal_message,
		modal_footer,
		{close_on_outside_click: false}
	);
}

// =========================================================================================== \\

function ai4seo_clear_debug_message_log($submit) {
	$submit = ai4seo_normalize_$( $submit );

	if (!ai4seo_exists_$( $submit )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_clear_debug_message_log() - cannot clear debug log.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit );
	ai4seo_lock_and_disable_lockable_input_fields();
	ai4seo_show_loading_toast( wp.i18n.__( 'Clearing debug log...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_clear_debug_message_log' )
		.then(
            response => {
			const $log_container = ai4seo_normalize_$( '#ai4seo-debug-message-log-entries' );
			if (ai4seo_exists_$( $log_container )) {
				// Reuse the server-rendered empty-state class so cleared logs keep the same spacing as initial page loads.
				$log_container.html( "<p class='ai4seo-debug-message-log-empty-message'>" + wp.i18n.__( 'No debug messages recorded yet. Entries stored with \"Store in the database\" will appear here.', 'ai-for-seo' ) + '</p>' );
			}
			const $copy_button = ai4seo_normalize_$( '.ai4seo-debug-log-copy-button' );
			if (ai4seo_exists_$( $copy_button )) {
				$copy_button.attr( 'data-clipboard-text', '' );
			}
			ai4seo_init_copy_to_clipboard( $copy_button );
			ai4seo_show_success_toast( wp.i18n.__( 'Debug log cleared.', 'ai-for-seo' ) );
			ai4seo_close_notification_modal();
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 512821225, error );
            }
        )
		.finally(
            () => {
			ai4seo_remove_loading_html_from_element( $submit );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );
}

// =========================================================================================== \\

function ai4seo_validate_troubleshooting_settings(input_values) {
	if (typeof input_values !== 'object' || input_values === null) {
		return true;
	}

	const debug_output_mode_name = 'ai4seo_debug_output_mode';
	const allowed_debug_output_modes = ['none', 'error_log', 'file', 'database', 'notice', 'print_r'];

	if (Object.prototype.hasOwnProperty.call( input_values, debug_output_mode_name )) {
		const selected_debug_output_mode = input_values[debug_output_mode_name];

		if (typeof selected_debug_output_mode !== 'string' || !allowed_debug_output_modes.includes( selected_debug_output_mode )) {
			ai4seo_show_warning_toast( wp.i18n.__( 'Please select a valid debug output mode.', 'ai-for-seo' ) );
			return false;
		}
	}

	const disable_heavy_db_operations_toggle_name = 'ai4seo_disable_heavy_db_operations';

	if (Object.prototype.hasOwnProperty.call( input_values, disable_heavy_db_operations_toggle_name ) && typeof input_values[disable_heavy_db_operations_toggle_name] !== 'boolean') {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a valid option for the debugging toggle.', 'ai-for-seo' ) );
		return false;
	}

	return true;
}

function ai4seo_confirm_reset_plugin_data() {
	const ai4seo_reset_metadata_post_types = ai4seo_get_selected_generated_data_reset_post_types( '.ai4seo-troubleshooting-reset-generated-data-post-type-checkbox' );
	const ai4seo_reset_metadata = ai4seo_reset_metadata_post_types.length > 0;

	let ai4seo_notification_modal_message = '';

	if (ai4seo_reset_metadata) {
		const $reset_generated_data_tooltip = ai4seo_normalize_$( '#ai4seo-reset-generated-data-tooltip-text' );

		if (ai4seo_exists_$( $reset_generated_data_tooltip )) {
			ai4seo_notification_modal_message = $reset_generated_data_tooltip.html() + '<br><br>';
		}
	}

	ai4seo_notification_modal_message += wp.i18n.__( 'Are you sure you want to reset the selected plugin data?', 'ai-for-seo' );

	ai4seo_open_notification_modal(
		wp.i18n.__( 'Please confirm', 'ai-for-seo' ),
		ai4seo_notification_modal_message,
		"<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'Abort', 'ai-for-seo' ) + "</button><button type='button' class='ai4seo-button ai4seo-primary-button' onclick='ai4seo_reset_plugin_data();'>" + wp.i18n.__( 'Reset Plugin Data', 'ai-for-seo' ) + '</button>'
	);
}

// =========================================================================================== \\

/**
 * Function to decode the HTML safely escaped by esc_js().
 * This replaces escaped characters (e.g., `&lt;`, `&gt;`) back to their HTML counterparts.
 */
function ai4seo_decode_escaped_html(escapedHtml) {
	const $textarea = ai4seo_normalize_$( '<textarea></textarea>' );

	if (!ai4seo_exists_$( $textarea )) {
		console.error( ai4seo_get_plugin_name() + ': Could not create textarea element in ai4seo_decode_escaped_html() \u2014 returning original value.' );
		return escapedHtml;
	}

	$textarea.html( escapedHtml ); // Decodes HTML entities.
	const value = $textarea.val(); // Returns unescaped HTML.
	$textarea.remove();

	return value;
}

// =========================================================================================== \\

function ai4seo_reset_plugin_data() {
	ai4seo_close_notification_modal();

	const $reset_cache_checkbox = ai4seo_normalize_$( '#ai4seo-troubleshooting-reset-cache' );
	const $reset_notifications_checkbox = ai4seo_normalize_$( '#ai4seo-troubleshooting-reset-notifications' );
	const $reset_environmental_variables_checkbox = ai4seo_normalize_$( '#ai4seo-troubleshooting-reset-env' );
	const $reset_settings_checkbox = ai4seo_normalize_$( '#ai4seo-troubleshooting-reset-settings' );
	const reset_metadata_checkbox_selector = '.ai4seo-troubleshooting-reset-generated-data-post-type-checkbox';
	const reset_metadata_post_types = ai4seo_get_selected_generated_data_reset_post_types( reset_metadata_checkbox_selector );
	const reset_metadata_is_full_reset = ai4seo_is_full_generated_data_reset_selection( reset_metadata_checkbox_selector );

	let reset_cache = ai4seo_exists_$( $reset_cache_checkbox ) && $reset_cache_checkbox.is( ':checked' );
	let reset_notifications = ai4seo_exists_$( $reset_notifications_checkbox ) && $reset_notifications_checkbox.is( ':checked' );
	let reset_environmental_variables = ai4seo_exists_$( $reset_environmental_variables_checkbox ) && $reset_environmental_variables_checkbox.is( ':checked' );
	let reset_settings = ai4seo_exists_$( $reset_settings_checkbox ) && $reset_settings_checkbox.is( ':checked' );
	let reset_metadata = reset_metadata_post_types.length > 0;

	// Check if at least one option is selected.
	if (!reset_cache && !reset_notifications && !reset_environmental_variables && !reset_settings && !reset_metadata) {
		ai4seo_open_notification_modal(
			wp.i18n.__( 'Oops...', 'ai-for-seo' ),
			wp.i18n.__( 'Please select at least one option to reset.', 'ai-for-seo' ),
			"<button type='button' class='ai4seo-button ai4seo-submit-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__( 'OK', 'ai-for-seo' ) + '</button>'
		);

		return;
	}

	ai4seo_lock_and_disable_lockable_input_fields();

	const $reset_button = ai4seo_normalize_$( '.ai4seo-troubleshooting-reset-button' );

	if (ai4seo_exists_$( $reset_button )) {
		ai4seo_add_loading_html_to_element( $reset_button );
	}

	let ajax_parameter = {
		ai4seo_reset_cache: reset_cache,
		ai4seo_reset_notifications: reset_notifications,
		ai4seo_reset_environmental_variables: reset_environmental_variables,
		ai4seo_reset_settings: reset_settings,
		ai4seo_reset_metadata: reset_metadata,
		ai4seo_reset_metadata_is_full_reset: reset_metadata_is_full_reset,
		ai4seo_reset_metadata_post_types: reset_metadata_post_types
	};

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Resetting plugin data...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_reset_plugin_data', ajax_parameter )
		.then(
            response => {
			ai4seo_show_success_toast( wp.i18n.__( 'The plugin data has been reset successfully.', 'ai-for-seo' ) );
			ai4seo_set_unsaved_changes_state( $reset_button, false );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1413181225, error );
            }
        )
		.finally(
            response => {
			ai4seo_unlock_and_enable_lockable_input_fields();
			if (ai4seo_exists_$( $reset_button )) {
				ai4seo_remove_loading_html_from_element( $reset_button );
			}
            }
        );
}


// ___________________________________________________________________________________________ \\
// === SELECT CREDITS PACK MODAL ============================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_select_credits_pack_modal() {
	ai4seo_open_modal_from_schema( 'select-credits-pack', {modal_size: 'small', unsaved_changes_warnings: true} );

	// Bind native radio changes so mouse and keyboard input reuse the existing selection synchronizer.
	const credits_pack_selection_event_namespace = '.ai4seo-credits-pack-selection';
	const $credits_pack_inputs = ai4seo_normalize_$( "input[name='ai4seo-credits-pack-selection[]']" );

	if (ai4seo_exists_$( $credits_pack_inputs )) {
		$credits_pack_inputs.off( 'change' + credits_pack_selection_event_namespace );
		$credits_pack_inputs.on(
			'change' + credits_pack_selection_event_namespace,
			function () {
				ai4seo_handle_credits_pack_selection( jQuery( this ).closest( '.ai4seo-credits-pack-selection-item' ) );
			}
		);
	}

	// Reuse the same state synchronizer for the initial recommendation without dispatching a synthetic change.
	const $most_popular_pack = ai4seo_normalize_$( '.ai4seo-credits-pack-selection-item-most-popular' );

	if (!ai4seo_exists_$( $most_popular_pack )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$most_popular_pack\" missing in ai4seo_handle_open_select_credits_pack_modal() \u2014 default credits pack not highlighted.' );
		return;
	}

	ai4seo_handle_credits_pack_selection( $most_popular_pack );
}

// =========================================================================================== \\

function ai4seo_track_subscription_pricing_visit() {
	ai4seo_perform_ajax_call( 'ai4seo_track_subscription_pricing_visit', {}, false, {}, false, false )
		.catch(
            () => {
			// We intentionally ignore errors to avoid interrupting the redirect to the pricing page.
            }
        );
}

// =========================================================================================== \\

function ai4seo_handle_credits_pack_selection($credits_pack_selection_item) {
	$credits_pack_selection_item = ai4seo_normalize_$( $credits_pack_selection_item );

	if (!ai4seo_exists_$( $credits_pack_selection_item )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$credits_pack_selection_item\" missing in ai4seo_handle_credits_pack_selection() \u2014 skipping iteration.' );
		return;
	}

	const $all_credits_pack_items = ai4seo_normalize_$( '.ai4seo-credits-pack-selection-item' );

	if (!ai4seo_exists_$( $all_credits_pack_items )) {
		console.error( ai4seo_get_plugin_name() + ': elements \"$all_credits_pack_items\" missing in ai4seo_handle_credits_pack_selection() \u2014 no credits pack items to update.' );
		return;
	}

	// Keep the visual card state and native radio state aligned for every selection path.
	$all_credits_pack_items.removeClass( 'ai4seo-credits-pack-selection-item-selected' );
	$credits_pack_selection_item.addClass( 'ai4seo-credits-pack-selection-item-selected' );
	$credits_pack_selection_item.find( '.ai4seo-credits-pack-selection-item-radio-button > input' ).prop( 'checked', true );

	// Refresh both estimates from the selected card so the summary follows keyboard changes as well as clicks.
	const cost_per_page = $credits_pack_selection_item.data( 'cost-per-page' );
	const cost_per_attachment = $credits_pack_selection_item.data( 'cost-per-attachment' );
	const currency = $credits_pack_selection_item.data( 'currency' );

	const $credits_pack_cost_per_page = ai4seo_normalize_$( '.ai4seo-credits-pack-cost-per-page' );
	const $credits_pack_cost_per_attachment = ai4seo_normalize_$( '.ai4seo-credits-pack-cost-per-attachment' );

	if (ai4seo_exists_$( $credits_pack_cost_per_page )) {
		$credits_pack_cost_per_page.text( cost_per_page + ' ' + currency );
	}

	if (ai4seo_exists_$( $credits_pack_cost_per_attachment )) {
		$credits_pack_cost_per_attachment.text( cost_per_attachment + ' ' + currency );
	}
}

// =========================================================================================== \\

function ai4seo_handle_select_credits_pack($submit_button) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	if (!ai4seo_exists_$( $submit_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_handle_select_credits_pack() \u2014 cannot save credits pack selection.' );
		return;
	}

	const $selected_credits_pack = ai4seo_normalize_$( "input[name='ai4seo-credits-pack-selection[]']" );

	if (!ai4seo_exists_$( $selected_credits_pack ) || !ai4seo_get_input_value( $selected_credits_pack )) {
		console.warn( ai4seo_get_plugin_name() + ': $credits_pack_selection missing or empty in ai4seo_handle_select_credits_pack() — cannot initiate purchase.' );
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a Credits Pack first.', 'ai-for-seo' ) );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	let selected_stripe_price_id = ai4seo_get_input_value( "input[name='ai4seo-credits-pack-selection[]']" );

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Initiating purchase...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_init_purchase', {stripe_price_id: selected_stripe_price_id} )
		.then(
            response => {
			if (typeof response.purchase_url === 'undefined' || !response.purchase_url) {
				ai4seo_show_error_toast( 471818325, wp.i18n.__( 'An error occurred while trying to initiate the purchase.', 'ai-for-seo' ) );
				return false;
			}
			ai4seo_show_success_toast( wp.i18n.__( 'Redirecting to purchase page...', 'ai-for-seo' ) );
			ai4seo_set_unsaved_changes_state( $submit_button, false );
			// redirect to purchase url.
			window.location.href = response.purchase_url;
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1513181225, error );
			ai4seo_remove_loading_html_from_element( $submit_button );
			ai4seo_unlock_and_enable_lockable_input_fields();
            }
        );
}


// ___________________________________________________________________________________________ \\
// === PAY-AS-YOU-GO MODAL =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_customize_payg_modal() {
	ai4seo_open_modal_from_schema( 'customize-pay-as-you-go', {modal_size: 'small', unsaved_changes_warnings: true} );
	ai4seo_handle_payg_form_change();
}

// =========================================================================================== \\

function ai4seo_handle_payg_form_change() {
	const $payg_price_select = ai4seo_normalize_$( '#ai4seo_payg_stripe_price_id' );
	const $selected_option = ai4seo_normalize_$( '#ai4seo_payg_stripe_price_id option:selected' );
	const $payg_daily_budget_input = ai4seo_normalize_$( '#ai4seo_payg_daily_budget' );
	const $payg_monthly_budget_input = ai4seo_normalize_$( '#ai4seo_payg_monthly_budget' );

	if (!ai4seo_exists_$( $payg_price_select ) || !ai4seo_exists_$( $selected_option ) || !ai4seo_exists_$( $payg_daily_budget_input ) || !ai4seo_exists_$( $payg_monthly_budget_input )) {
		console.warn( ai4seo_get_plugin_name() + ': PAYG form elements missing in ai4seo_handle_payg_form_change() — cannot update pricing summary.' );
		return;
	}

	let payg_stripe_price_id = $payg_price_select.val();
	let payg_credits_amount_formatted = $selected_option.data( 'credits-amount-formatted' );
	let payg_price = $selected_option.data( 'price' );
	let payg_price_formatted = $selected_option.data( 'price-formatted' );
	let payg_reference_price_formatted = $selected_option.data( 'reference-price-formatted' );
	let payg_daily_budget = $payg_daily_budget_input.val();
	let payg_monthly_budget = $payg_monthly_budget_input.val();
	const price_buffer = 1.25; // 25% buffer to account for taxes

	// Normalize legacy formatted price data before using it for minimum-budget calculations.
	if (typeof payg_price === 'string') {
		payg_price = payg_price.replace( ',', '.' );
	}

	// Keep budget calculations numeric even though the visible summary uses locale-formatted strings.
	payg_price = parseFloat( payg_price );

	// add buffer to the price.
	const buffered_payg_price = Math.ceil( payg_price * price_buffer );

	// cast payg_daily_budget to int.
	payg_daily_budget = parseInt( payg_daily_budget );
	$payg_daily_budget_input.val( payg_daily_budget );

	// if daily budget is lower than the price, set it to the ceil(buffered_payg_price).
	if (payg_daily_budget < buffered_payg_price) {
		payg_daily_budget = buffered_payg_price;
		$payg_daily_budget_input.val( payg_daily_budget );
	}

	// cast payg_monthly_budget to int.
	payg_monthly_budget = parseInt( payg_monthly_budget );

	// if monthly budget is lower than the price, set it to the ceil(price).
	if (payg_monthly_budget < buffered_payg_price) {
		payg_monthly_budget = buffered_payg_price;
		$payg_monthly_budget_input.val( payg_monthly_budget );
	}

	// Use formatted option values for visible summary text, with fallbacks for older cached modal markup.
	if (typeof payg_credits_amount_formatted === 'undefined') {
		payg_credits_amount_formatted = ai4seo_format_number_i18n( $selected_option.data( 'credits-amount' ) );
	}

	if (typeof payg_price_formatted === 'undefined') {
		payg_price_formatted = ai4seo_format_number_i18n( payg_price, 2 );
	}

	if (typeof payg_reference_price_formatted === 'undefined') {
		payg_reference_price_formatted = ai4seo_format_number_i18n( $selected_option.data( 'reference-price' ), 2 );
	}

	// Keep recalculated budget summaries visually aligned with the PHP-rendered initial state.
	const payg_daily_budget_formatted = ai4seo_format_number_i18n( payg_daily_budget, 2 );
	const payg_monthly_budget_formatted = ai4seo_format_number_i18n( payg_monthly_budget, 2 );

	const $payg_summary_credits_amount = ai4seo_normalize_$( '#ai4seo-payg-summary-credits-amount' );
	const $payg_summary_price = ai4seo_normalize_$( '#ai4seo-payg-summary-price' );
	const $payg_summary_reference_price = ai4seo_normalize_$( '#ai4seo-payg-summary-reference-price' );
	const $payg_summary_daily_budget = ai4seo_normalize_$( '#ai4seo-payg-summary-daily-budget' );
	const $payg_summary_monthly_budget = ai4seo_normalize_$( '#ai4seo-payg-summary-monthly-budget' );

	if (ai4seo_exists_$( $payg_summary_credits_amount )) {
		$payg_summary_credits_amount.text( payg_credits_amount_formatted );
	}

	if (ai4seo_exists_$( $payg_summary_price )) {
		$payg_summary_price.text( payg_price_formatted );
	}

	if (ai4seo_exists_$( $payg_summary_reference_price )) {
		$payg_summary_reference_price.text( payg_reference_price_formatted );
	}

	if (ai4seo_exists_$( $payg_summary_daily_budget )) {
		$payg_summary_daily_budget.text( payg_daily_budget_formatted );
	}

	if (ai4seo_exists_$( $payg_summary_monthly_budget )) {
		$payg_summary_monthly_budget.text( payg_monthly_budget_formatted );
	}
}

// =========================================================================================== \\

function ai4seo_handle_payg_submit($submit_button) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	if (!ai4seo_exists_$( $submit_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_handle_payg_submit() \u2014 cannot process PAYG checkout.' );
		return;
	}

	ai4seo_save_anything(
        $submit_button,
        ai4seo_validate_payg_inputs,
        function () {
		ai4seo_safe_page_load();
        }
    );
}

// =========================================================================================== \\

function ai4seo_validate_payg_inputs() {
	// #ai4seo_payg_enabled must be checked
	const $payg_enabled_checkbox = ai4seo_normalize_$( '#ai4seo_payg_enabled' );

	if (!ai4seo_exists_$( $payg_enabled_checkbox )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$payg_enabled_checkbox\" missing in ai4seo_validate_payg_inputs() \u2014 cannot confirm PAYG activation.' );
		return false;
	}

	let payg_confirmation_checkbox = $payg_enabled_checkbox.is( ':checked' );

	if (!payg_confirmation_checkbox) {
		ai4seo_show_info_toast( wp.i18n.__( 'Please confirm that you have reviewed the settings above and you want to enable Pay-As-You-Go now.', 'ai-for-seo' ) );
		return false;
	}

	// check daily budget, must be at least as high as the price.
	const $payg_daily_budget_input = ai4seo_normalize_$( '#ai4seo_payg_daily_budget' );

	if (!ai4seo_exists_$( $payg_daily_budget_input )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$payg_daily_budget_input\" missing in ai4seo_validate_payg_inputs() \u2014 daily budget validation failed.' );
		return false;
	}

	let payg_daily_budget = $payg_daily_budget_input.val();
	let payg_price = null;
	const price_buffer = 1.25; // 25% buffer to account for taxes

	const $selected_option = ai4seo_normalize_$( '#ai4seo_payg_stripe_price_id option:selected' );

	if (!ai4seo_exists_$( $selected_option )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$selected_option\" missing in ai4seo_validate_payg_inputs() \u2014 PAYG option validation failed.' );
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a valid credits pack.', 'ai-for-seo' ) );
		return false;
	}

	payg_price = parseFloat( $selected_option.data( 'price' ) );

	// buffered price.
	const buffered_payg_price = Math.ceil( payg_price * price_buffer );

	// cast payg_daily_budget to int.
	payg_daily_budget = parseInt( payg_daily_budget );

	if (payg_daily_budget < buffered_payg_price) {
		ai4seo_show_warning_toast( wp.i18n.__( 'The daily budget must be at least as high as the selected price and a 25% buffer to account for taxes (' + buffered_payg_price + ').', 'ai-for-seo' ) );
		return false;
	}

	// max 99999.
	if (payg_daily_budget > 99999) {
		ai4seo_show_warning_toast( wp.i18n.__( 'The daily budget must be at most 99999.', 'ai-for-seo' ) );
		return false;
	}

	// check monthly budget, must be at least as high as the price.
	const $payg_monthly_budget_input = ai4seo_normalize_$( '#ai4seo_payg_monthly_budget' );

	if (!ai4seo_exists_$( $payg_monthly_budget_input )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$payg_monthly_budget_input\" missing in ai4seo_validate_payg_inputs() \u2014 monthly budget validation failed.' );
		ai4seo_show_warning_toast( wp.i18n.__( 'Please enter a valid monthly budget.', 'ai-for-seo' ) );
		return false;
	}

	let payg_monthly_budget = $payg_monthly_budget_input.val();

	// cast payg_monthly_budget to int.
	payg_monthly_budget = parseInt( payg_monthly_budget );

	if (payg_monthly_budget < buffered_payg_price) {
		ai4seo_show_warning_toast( wp.i18n.__( 'The monthly budget must be at least as high as the selected price and a 25% buffer to account for taxes (' + buffered_payg_price + ').', 'ai-for-seo' ) );
		return false;
	}

	// max 999999.
	if (payg_monthly_budget > 999999) {
		ai4seo_show_warning_toast( wp.i18n.__( 'The monthly budget must be at most 999999.', 'ai-for-seo' ) );
		return false;
	}

	return true;
}

// =========================================================================================== \\

function ai4seo_disable_payg($submit_button) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	if (!ai4seo_exists_$( $submit_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_disable_payg() \u2014 cannot disable PAYG.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Disabling Pay-As-You-Go...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_disable_payg' )
		.then(
            response => {
			ai4seo_show_success_toast( wp.i18n.__( 'Pay-As-You-Go has been disabled successfully. Reloading page...', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1613181225, error );
            }
        )
		.finally(
            () => {
			setTimeout( () => ai4seo_safe_page_load(), 1000 );
            }
        );
}

// =========================================================================================== \\

function ai4seo_import_nextgen_gallery_images($submit_button) {
	$submit_button = ai4seo_normalize_$( $submit_button );

	if (!ai4seo_exists_$( $submit_button )) {
		console.warn( ai4seo_get_plugin_name() + ': element \"$submit\" missing in ai4seo_import_nextgen_gallery_images() \u2014 cannot import NextGEN gallery images.' );
		return;
	}

	ai4seo_add_loading_html_to_element( $submit_button );
	ai4seo_lock_and_disable_lockable_input_fields();

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Importing NextGEN gallery images...', 'ai-for-seo' ) );

	ai4seo_perform_ajax_call( 'ai4seo_import_nextgen_gallery_images' )
		.then(
            response => {
			ai4seo_show_success_toast( wp.i18n.__( 'NextGEN gallery images imported successfully. Reloading page...', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1713181225, error );
            }
        )
		.finally(
            () => {
			setTimeout( () => ai4seo_safe_page_load(), 1000 );
            }
        );
}


// ___________________________________________________________________________________________ \\
// === EXPORT/IMPORT SETTINGS ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Export all settings to a JSON file
 */
function ai4seo_init_export_settings() {
	let $export_button = ai4seo_normalize_$( '.ai4seo-export-settings-button' );

	if (!ai4seo_exists_$( $export_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$export_button\" missing in ai4seo_init_export_settings() \u2014 settings export aborted.' );
		return;
	}

	// save any unsaved changes before exporting.
	const $save_settings_button = ai4seo_normalize_$( '.ai4seo-save-settings-button' );

	if (!ai4seo_exists_$( $save_settings_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$save_settings_button\" missing in ai4seo_init_export_settings() \u2014 cannot trigger export.' );
		return;
	}

	ai4seo_save_anything( $save_settings_button, ai4seo_validate_settings_inputs, ai4seo_export_settings );
}

// =========================================================================================== \\

function ai4seo_export_settings() {
	let $export_button = ai4seo_normalize_$( '.ai4seo-export-settings-button' );

	if (!ai4seo_exists_$( $export_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$export_button\" missing in ai4seo_export_settings() \u2014 settings export aborted.' );
		return;
	}

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Exporting settings...', 'ai-for-seo' ) );

	// Perform AJAX call to export settings.
	ai4seo_perform_ajax_call( 'ai4seo_export_settings' )
		.then(
            response => {
			response.settings_data = response.settings_data || null;
			response.filename = response.filename || null;
			if (response.settings_data && response.filename) {
				// Create downloadable file.
				ai4seo_download_json_file( response.settings_data, response.filename );
				ai4seo_show_success_toast( wp.i18n.__( 'Settings exported successfully! The file can be imported using the same modal.', 'ai-for-seo' ) );
			} else {
            ai4seo_show_error_toast(
                50176725,
                wp.i18n.__( 'Failed to export settings. Please try again.', 'ai-for-seo' )
            );
			}
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 1813181225, error );
            }
        )
		.finally(
            () => {
			// Remove loading animation.
			ai4seo_remove_loading_html_from_element( $export_button );
			ai4seo_close_modal_from_schema( 'export-import-settings' );
            }
        );
}

// =========================================================================================== \\

/**
 * Import settings from uploaded JSON file
 */
function ai4seo_init_import_settings() {
	let $import_file_input = ai4seo_normalize_$( '#ai4seo-import-file' );

	if (!ai4seo_exists_$( $import_file_input )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$import_file_input\" missing in ai4seo_init_import_settings() \u2014 import cannot proceed.' );
		return;
	}

	let $import_settings_button = ai4seo_normalize_$( '.ai4seo-import-settings-button' );

	if (!ai4seo_exists_$( $import_settings_button )) {
		console.error( ai4seo_get_plugin_name() + ': element \"$import_settings_button\" missing in ai4seo_init_import_settings() \u2014 import workflow halted.' );
		return;
	}

	let file_input_element = $import_file_input[0];

	// Validate file selection.
	if (!file_input_element.files || file_input_element.files.length === 0) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a file to import.', 'ai-for-seo' ) );
		console.warn( ai4seo_get_plugin_name() + ': no file selected in ai4seo_init_import_settings() \u2014 import cannot proceed.' );
		return;
	}

	let file = file_input_element.files[0];

	// Validate file type.
	if (!file.name.toLowerCase().endsWith( '.json' )) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select a valid JSON file.', 'ai-for-seo' ) );
		console.warn( ai4seo_get_plugin_name() + ': invalid file type in ai4seo_init_import_settings() \u2014 import cannot proceed.' );
		return;
	}

	// Get selected categories.
	let categories = [];

	const $import_settings_page_checkbox = ai4seo_normalize_$( '#ai4seo-import-settings-page-checkbox' );

	if (ai4seo_exists_$( $import_settings_page_checkbox ) && $import_settings_page_checkbox.is( ':checked' )) {
		categories.push( 'settings' );
	}

	const $import_account_page_checkbox = ai4seo_normalize_$( '#ai4seo-import-account-page-checkbox' );

	if (ai4seo_exists_$( $import_account_page_checkbox ) && $import_account_page_checkbox.is( ':checked' )) {
		categories.push( 'account' );
	}

	const $import_seo_autopilot_checkbox = ai4seo_normalize_$( '#ai4seo-import-seo-autopilot-checkbox' );

	if (ai4seo_exists_$( $import_seo_autopilot_checkbox ) && $import_seo_autopilot_checkbox.is( ':checked' )) {
		categories.push( 'seo_autopilot' );
	}

	const $import_get_more_credits_checkbox = ai4seo_normalize_$( '#ai4seo-import-get-more-credits-checkbox' );

	if (ai4seo_exists_$( $import_get_more_credits_checkbox ) && $import_get_more_credits_checkbox.is( ':checked' )) {
		categories.push( 'get_more_credits' );
	}

	// Validate category selection.
	if (categories.length === 0) {
		ai4seo_show_warning_toast( wp.i18n.__( 'Please select at least one category to import.', 'ai-for-seo' ) );
		console.warn( ai4seo_get_plugin_name() + ': no categories selected in ai4seo_init_import_settings() \u2014 import cannot proceed.' );
		return;
	}

	// Add loading animation.
	ai4seo_add_loading_html_to_element( $import_settings_button );

	// Read file content.
	let reader = new FileReader();

	reader.onload = function (e) {
		try {
			let file_content = JSON.parse( e.target.result );

			// check for "ai4seo_plugin_version" property.
			if (!file_content.hasOwnProperty( 'ai4seo_plugin_version' )) {
				ai4seo_remove_loading_html_from_element( $import_settings_button );
				ai4seo_show_error_toast(
					44186725,
					wp.i18n.__( "Invalid JSON file format. The file must contain the 'ai4seo_plugin_version' property.", 'ai-for-seo' )
				);
				return;
			}

			// check for settings property.
			if (!file_content.hasOwnProperty( 'settings' )) {
				ai4seo_remove_loading_html_from_element( $import_settings_button );
				ai4seo_show_error_toast(
					45186725,
					wp.i18n.__( "Invalid JSON file format. The file must contain the 'settings' property.", 'ai-for-seo' )
				);
				return;
			}

			// check if version is lower than the current version.
			let current_version = ai4seo_get_plugin_version_number();
			let imported_version = file_content.ai4seo_plugin_version;
			let new_settings = file_content.settings;

			if (imported_version !== current_version) {
				// Pause the import flow when settings come from a different plugin version.
				ai4seo_remove_loading_html_from_element( $import_settings_button );

				// Keep imported settings data out of inline handlers; the click bindings below close over the parsed JSON safely.
				const version_mismatch_footer =
					"<button type='button' class='ai4seo-button ai4seo-abort-button ai4seo-import-version-mismatch-abort'>" + ai4seo_escape_html( wp.i18n.__( 'Abort Import', 'ai-for-seo' ) ) + '</button>' +
					"<button type='button' class='ai4seo-button ai4seo-submit-button ai4seo-import-version-mismatch-proceed'>" + ai4seo_escape_html( wp.i18n.__( 'Proceed with Import', 'ai-for-seo' ) ) + '</button>';

				// Show the existing notification modal so the import preview only opens after explicit confirmation.
				ai4seo_open_notification_modal(
					wp.i18n.__( 'Version Mismatch', 'ai-for-seo' ),
					wp.i18n.__( 'The imported settings are from an older or newer version of the plugin. Some settings may not be compatible with the current version.', 'ai-for-seo' ),
					version_mismatch_footer
				);

				// Bind against the opened modal instead of writing imported JSON into button attributes.
				const $version_mismatch_modal = ai4seo_get_modal_$( 'ai4seo-notification-modal' );
				if (ai4seo_exists_$( $version_mismatch_modal )) {
					$version_mismatch_modal
						.off( 'click.ai4seo-import-version-mismatch' )
						.on(
                            'click.ai4seo-import-version-mismatch',
                            '.ai4seo-import-version-mismatch-abort',
                            function () {
							ai4seo_close_notification_modal();
                            }
                        )
						.on(
                            'click.ai4seo-import-version-mismatch',
                            '.ai4seo-import-version-mismatch-proceed',
                            function () {
							ai4seo_close_notification_modal();
							ai4seo_show_import_settings_preview( new_settings, categories, imported_version );
                            }
                        );
				}
			} else {
				ai4seo_show_import_settings_preview( new_settings, categories, imported_version );
			}
		} catch (error) {
			ai4seo_remove_loading_html_from_element( $import_settings_button );
			console.error( ai4seo_get_plugin_name() + ': error parsing JSON file in ai4seo_init_import_settings() \u2014 import cannot proceed.', error );
			ai4seo_show_error_toast(
				46186725,
				wp.i18n.__( 'Invalid JSON file format. Please check the file content.', 'ai-for-seo' )
			);
		}
	}

	reader.readAsText( file );
}

// =========================================================================================== \\

/**
 * Download JSON data as file
 */
function ai4seo_download_json_file(data, filename) {
	// ensure a sane filename.
	filename = (typeof filename === 'string' && filename.trim()) ? filename : 'download.json';

	// build JSON safely.
	var json_str;
	try {
		json_str = JSON.stringify( data, null, 2 );
	} catch (e) {
		console.error( ai4seo_get_plugin_name() + ': Could not stringify data in ai4seo_download_json_file().', e );
		return;
	}

	var blob = new Blob( [json_str], {type: 'application/json;charset=utf-8'} );
	var URL_ = window.URL || window.webkitURL;
	var url = URL_.createObjectURL( blob );

	// keep close to your jQuery approach.
	var $download_link = ai4seo_normalize_$( '<a></a>' );
	var $body = ai4seo_normalize_$( 'body', document );

	if (!ai4seo_exists_$( $download_link ) || !$download_link.length || !ai4seo_exists_$( $body ) || !$body.length) {
		console.error( ai4seo_get_plugin_name() + ': Unable to create or find elements in ai4seo_download_json_file().' );
		try {
			URL_.revokeObjectURL( url );
		} catch (e) {
		}
		return;
	}

	$download_link
		.attr( {href: url, download: filename} )
		.addClass( 'ai4seo-display-none' );

	$body.append( $download_link );

	// click the real DOM node for better browser compatibility.
	var download_link_element = $download_link.get( 0 );
	try {
		if (download_link_element && typeof download_link_element.click === 'function') {
			download_link_element.click();
		} else if (download_link_element && download_link_element.dispatchEvent) {
			var evt = document.createEvent( 'MouseEvents' );
			evt.initEvent( 'click', true, true );
			download_link_element.dispatchEvent( evt );
		} else {
			$download_link.trigger( 'click' );
		}
	} finally {
		// defer cleanup so the download can start.
		setTimeout(
            function () {
			try {
				$download_link.remove();
			} catch (e) {
			}
			try {
				URL_.revokeObjectURL( url );
			} catch (e) {
			}
            },
            0
        );
	}
}


// =========================================================================================== \\

// Preserve import preview state because the confirmation modal triggers the execute request later.
let ai4seo_import_new_settings = null;
let ai4seo_import_categories = null;
let ai4seo_import_plugin_version = null;

/**
 * Build import request data from the confirmed preview state.
 */
function ai4seo_get_import_settings_ajax_data(import_mode) {
	// The preview and execute requests must use the same source version for compatibility migrations.
	const import_settings_payload = {
		ai4seo_new_settings: ai4seo_import_new_settings,
		ai4seo_import_categories: ai4seo_import_categories,
		ai4seo_import_plugin_version: ai4seo_import_plugin_version,
		ai4seo_import_mode: import_mode
	};

	// Send the import data as one JSON envelope so empty arrays/objects survive jQuery.param().
	return {
		ai4seo_import_settings_payload: ai4seo_encode_save_anything_payload( import_settings_payload ),
		ai4seo_import_settings_payload_encoding: 'base64_json'
	};
}

// =========================================================================================== \\

function ai4seo_show_import_settings_preview(new_settings, categories, imported_plugin_version = '') {
	let $import_button = ai4seo_normalize_$( '.ai4seo-import-settings-button' );

	// Keep the new settings, categories, and source version for the confirmed execute request.
	ai4seo_import_new_settings = new_settings;
	ai4seo_import_categories = categories;
	ai4seo_import_plugin_version = imported_plugin_version;

	// Preview and execute share the same payload shape so migration parameters cannot drift.
	let import_settings_data = ai4seo_get_import_settings_ajax_data( 'preview' );

	ai4seo_open_ajax_modal( 'ai4seo_show_import_settings_preview', import_settings_data, {modal_size: 'small'} );

	ai4seo_remove_loading_html_from_element( $import_button );
}

// =========================================================================================== \\


/**
 * Execute the actual import after user confirmation
 */
function ai4seo_execute_import_settings($import_button, new_settings, categories) {
	$import_button = ai4seo_normalize_$( $import_button );

	// check if ai4seo_import_new_settings and ai4seo_import_categories.
	if (!ai4seo_import_new_settings || !ai4seo_import_categories) {
		ai4seo_show_error_toast(
			47186725,
			wp.i18n.__( 'No settings to import. Please select a valid JSON file first.', 'ai-for-seo' )
		);
		return;
	}

	// Add loading animation.
	ai4seo_add_loading_html_to_element( $import_button );

	// Execute imports with the same payload shape that powered the preview modal.
	let import_settings_data = ai4seo_get_import_settings_ajax_data( 'execute' );

	// show loading toast.
	ai4seo_show_loading_toast( wp.i18n.__( 'Importing settings...', 'ai-for-seo' ) );

	// check if we have unsaved changes -> ignore warnings.
	if (ai4seo_has_unsaved_changes()) {
		ai4seo_set_unsaved_changes_state( jQuery( '.ai4seo-unsaved-changes-warnings' ), false );
	}

	// Execute import.
	ai4seo_perform_ajax_call( 'ai4seo_import_settings', import_settings_data )
		.then(
            response => {
			ai4seo_close_all_modals();
			ai4seo_show_success_toast( wp.i18n.__( 'Settings imported successfully! The page will reload.', 'ai-for-seo' ) );
            }
        )
		.catch(
            error => {
			ai4seo_show_error_toast( 19813181225, error );
			ai4seo_remove_loading_html_from_element( $import_button );
            }
        )
		.finally(
            () => {
			// Reload page after short delay.
			setTimeout( () => ai4seo_safe_page_load(), 1000 );
            }
        );
}


// ___________________________________________________________________________________________ \\
// === DASHBOARD AUTO-REFRESH ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Metrics counters (disabled by default, enable via debug flag).
const ai4seo_dashboard_metrics = {
	refresh_attempts: 0,
	cancelled_responses: 0,
	no_change_streak_length: 0,
	hidden_mode_triggers: 0,
	full_reload_triggers: 0,
	user_interaction_locks: 0,
	last_ajax_response_duration_ms: 0
};

// Global variables for dashboard auto-refresh.
let ai4seo_dashboard_refresh_timer = null;
let ai4seo_dashboard_refresh_lock = false;
let ai4seo_dashboard_is_hidden = false;
let ai4seo_dashboard_refresh_failures = 0;

// Enhanced refresh system variables.
let ai4seo_dashboard_hidden_start_time = null;
let ai4seo_dashboard_hidden_refresh_timer = null;
let ai4seo_dashboard_hidden_reload_timer = null;
let ai4seo_dashboard_no_changes_streak = 0;
let ai4seo_dashboard_adaptive_interval = 10000; // Base interval for adaptive scaling.
let ai4seo_dashboard_last_user_click = Date.now();
let ai4seo_dashboard_idle_reload_timer = null;
let ai4seo_dashboard_user_interaction_lock = false;
let ai4seo_dashboard_user_interaction_timer = null;
let ai4seo_dashboard_current_ajax_request = null;
let ai4seo_dashboard_changed_nodes = [];

/**
 * Initialize dashboard auto-refresh functionality
 * Only runs on dashboard page where .ai4seo-dashboard container exists
 */
function ai4seo_init_dashboard_refresh() {
	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	// Only initialize if we're on the dashboard page.
	if (!ai4seo_exists_$( $dashboard )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': $dashboard missing in ai4seo_init_dashboard_refresh() \u2014 cannot initialize dashboard refresh cycle.');.
		return;
	}

	ai4seo_init_dashboard_progress_bar();

	// Set up visibility change listener for pause/resume.
	const $document = ai4seo_normalize_$( document );

	if (ai4seo_exists_$( $document )) {
		$document.off( 'visibilitychange.ai4seo-dashboard', ai4seo_handle_dashboard_visibility_change );
		$document.on( 'visibilitychange.ai4seo-dashboard', ai4seo_handle_dashboard_visibility_change );
	}

	// Set up user interaction listeners.
	ai4seo_init_dashboard_user_interaction_listeners();

	// Clear any existing timers.
	ai4seo_clear_all_dashboard_timers();

	// Initialize user click tracking.
	ai4seo_dashboard_last_user_click = Date.now();
	ai4seo_schedule_dashboard_idle_reload_check();

	// Start the refresh cycle.
	ai4seo_schedule_dashboard_refresh();

	// Clean up on page unload.
	const $window = ai4seo_normalize_$( window );

	if (ai4seo_exists_$( $window )) {
		$window.off( 'beforeunload.ai4seo-dashboard', ai4seo_clear_all_dashboard_timers );
		$window.on( 'beforeunload.ai4seo-dashboard', ai4seo_clear_all_dashboard_timers );
	}
}

// =========================================================================================== \\

/**
 * Initialize user interaction listeners for dashboard refresh control
 */
function ai4seo_init_dashboard_user_interaction_listeners() {
	const $document = ai4seo_normalize_$( document );

	if (!ai4seo_exists_$( $document )) {
		console.warn( ai4seo_get_plugin_name() + ': Document unavailable in ai4seo_init_dashboard_user_interaction_listeners() \u2014 skipping interaction bindings.' );
		return;
	}

	// Click listener with 5-second refresh lock.
	$document.off( 'mousedown.ai4seo-dashboard-interaction', ai4seo_handle_dashboard_click );
	$document.on( 'mousedown.ai4seo-dashboard-interaction', ai4seo_handle_dashboard_click );

	// Mouse move and scroll listeners with 1-second refresh lock (debounced).
	let ai4seo_move_timeout = null;

	const ai4seo_mousemove_handler = function () {
		if (ai4seo_move_timeout) {
			return; // Debounce high-frequency events.
		}
		ai4seo_move_timeout = setTimeout(
            function () {
			ai4seo_handle_dashboard_mouse_interaction();
			ai4seo_move_timeout = null;
            },
            100
        ); // 100ms debounce
	};

	const ai4seo_scroll_handler = function () {
		if (ai4seo_move_timeout) {
			return; // Debounce high-frequency events.
		}
		ai4seo_move_timeout = setTimeout(
            function () {
			ai4seo_handle_dashboard_mouse_interaction();
			ai4seo_move_timeout = null;
            },
            100
        ); // 100ms debounce
	};

	$document.off( 'mousemove.ai4seo-dashboard-interaction' );
	$document.on( 'mousemove.ai4seo-dashboard-interaction', ai4seo_mousemove_handler );

	$document.off( 'scroll.ai4seo-dashboard-interaction' );
	$document.on( 'scroll.ai4seo-dashboard-interaction', ai4seo_scroll_handler );
}

// =========================================================================================== \\

/**
 * Handle user clicks - apply 5-second refresh lock and reset intervals
 */
function ai4seo_handle_dashboard_click() {
	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	if (!ai4seo_exists_$( $dashboard )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $dashboard missing in ai4seo_handle_dashboard_click() \u2014 cannot process dashboard click interactions.' );
		return;
	}

	// Update metrics.
	if (ai4seo_dashboard_debug_metrics) {
		ai4seo_dashboard_metrics.user_interaction_locks++;
	}

	// Record click time for idle tracking.
	ai4seo_dashboard_last_user_click = Date.now();

	// Reset adaptive interval to 10s.
	ai4seo_dashboard_adaptive_interval = 10000;
	ai4seo_dashboard_no_changes_streak = 0;

	// Snap to 3 seconds remaining if near finish.
	ai4seo_snap_dashboard_refresh_timer( 3000 );

	// Cancel any in-flight requests.
	ai4seo_cancel_dashboard_in_flight_request();
}

// =========================================================================================== \\

/**
 * Handle mouse move and scroll - apply 1-second refresh lock
 */
function ai4seo_handle_dashboard_mouse_interaction() {
	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	if (!ai4seo_exists_$( $dashboard )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $dashboard missing in ai4seo_handle_dashboard_mouse_interaction() \u2014 cannot track dashboard mouse activity.' );
		return;
	}

	// Record click time for idle tracking.
	ai4seo_snap_dashboard_refresh_timer( 1000 ); // Snap to 1 second remaining if near finish.
}

// =========================================================================================== \\

/**
 * Snap the refresh timer back to a specific "seconds until refresh" if near finish
 *
 * @param {number} snap_ms - ms to leave until refresh
 */
function ai4seo_snap_dashboard_refresh_timer(snap_ms) {
	if (!ai4seo_dashboard_refresh_end_time) {
		return; // No timer running.
	}

	if (ai4seo_dashboard_current_ajax_request) {
		return;
	}

	const now = Date.now();
	const remaining_ms = ai4seo_dashboard_refresh_end_time - now;

	// Only snap if <= snap_seconds seconds left.
	if (remaining_ms <= snap_ms) {
		ai4seo_dashboard_refresh_end_time = now + (snap_ms);

		// Clear and re-set the main refresh timeout.
		if (ai4seo_dashboard_refresh_timer) {
			clearTimeout( ai4seo_dashboard_refresh_timer );
		}

		ai4seo_dashboard_refresh_timer = setTimeout( ai4seo_fetch_and_update_dashboard, snap_ms );

		// Restart progress bar with adjusted duration.
		ai4seo_start_dashboard_progress( snap_ms );
	}
}

// =========================================================================================== \\

/**
 * Cancel any in-flight AJAX request and mark response for discard
 */
function ai4seo_cancel_dashboard_in_flight_request() {
	if (!ai4seo_dashboard_current_ajax_request) {
		return false;
	}

	// Mark request as cancelled for idempotent discard.
	ai4seo_dashboard_current_ajax_request.cancelled = true;

	// Update metrics.
	if (ai4seo_dashboard_debug_metrics) {
		ai4seo_dashboard_metrics.cancelled_responses++;
	}

	// Note: We don't actually abort the request to avoid potential issues,
	// instead we mark it for discard when response arrives.
	ai4seo_dashboard_current_ajax_request = null;

	return true;
}

// =========================================================================================== \\

/**
 * Handle browser tab visibility changes - enhanced with inactive behavior
 */
function ai4seo_handle_dashboard_visibility_change() {
	if (document.hidden) {
		// browser tab became hidden.
		ai4seo_dashboard_is_hidden = true;
		ai4seo_dashboard_hidden_start_time = Date.now();

		// Update metrics.
		if (ai4seo_dashboard_debug_metrics) {
			ai4seo_dashboard_metrics.hidden_mode_triggers++;
		}

		// Clear all active timers.
		ai4seo_clear_all_dashboard_timers();

		// Start hidden mode: 3-minute refresh cadence.
		ai4seo_schedule_dashboard_hidden_mode_refresh();

		// Schedule full reload after 15 minutes of inactivity.
		ai4seo_dashboard_hidden_reload_timer = setTimeout(
            function () {
			if (ai4seo_dashboard_debug_metrics) {
				ai4seo_dashboard_metrics.full_reload_triggers++;
			}
			location.reload();
            },
            15 * 60 * 1000
        ); // 15 minutes

	} else {
		// browser tab became visible.
		const ai4seo_was_hidden = ai4seo_dashboard_is_hidden;
		ai4seo_dashboard_is_hidden = false;
		ai4seo_dashboard_hidden_start_time = null;

		// Clear hidden mode timers.
		ai4seo_clear_dashboard_hidden_mode_timers();

		if (ai4seo_was_hidden && ai4seo_exists_$( '.ai4seo-dashboard' )) {
			// Reset adaptive interval to 10s base.
			ai4seo_dashboard_adaptive_interval = 10000;
			ai4seo_dashboard_no_changes_streak = 0;

			// Trigger immediate refresh.
			ai4seo_fetch_and_update_dashboard();
		}
	}
}

// =========================================================================================== \\

/**
 * Schedule refresh in hidden mode (3-minute intervals)
 */
function ai4seo_schedule_dashboard_hidden_mode_refresh() {
	if (!ai4seo_dashboard_is_hidden) {
		return;
	}

	ai4seo_dashboard_hidden_refresh_timer = setTimeout(
        function () {
		if (ai4seo_dashboard_is_hidden && ai4seo_exists_$( '.ai4seo-dashboard' )) {
			ai4seo_fetch_and_update_dashboard();
			ai4seo_schedule_dashboard_hidden_mode_refresh(); // Schedule next.
		}
        },
        3 * 60 * 1000
    ); // 3 minutes
}

// =========================================================================================== \\

/**
 * Clear hidden mode timers
 */
function ai4seo_clear_dashboard_hidden_mode_timers() {
	if (ai4seo_dashboard_hidden_refresh_timer) {
		clearTimeout( ai4seo_dashboard_hidden_refresh_timer );
		ai4seo_dashboard_hidden_refresh_timer = null;
	}
	if (ai4seo_dashboard_hidden_reload_timer) {
		clearTimeout( ai4seo_dashboard_hidden_reload_timer );
		ai4seo_dashboard_hidden_reload_timer = null;
	}
}

// =========================================================================================== \\

/**
 * Schedule idle reload check (monitors for 1+ minute without clicks)
 */
function ai4seo_schedule_dashboard_idle_reload_check() {
	if (ai4seo_dashboard_idle_reload_timer) {
		clearTimeout( ai4seo_dashboard_idle_reload_timer );
	}

	ai4seo_dashboard_idle_reload_timer = setTimeout(
        function () {
		const ai4seo_time_since_click = Date.now() - ai4seo_dashboard_last_user_click;

		if (ai4seo_time_since_click >= 60 * 1000) { // 1 minute idle
			// User has been idle for 1+ minute, schedule full reload every 5 minutes
			if (ai4seo_dashboard_debug_metrics) {
				ai4seo_dashboard_metrics.full_reload_triggers++;
			}
			location.reload();
		} else {
			// Not idle yet, check again.
			ai4seo_schedule_dashboard_idle_reload_check();
		}
        },
        5 * 60 * 1000
    ); // Check every 5 minutes.
}

// =========================================================================================== \\

/**
 * Clear all dashboard timers
 */
function ai4seo_clear_all_dashboard_timers() {
	ai4seo_clear_dashboard_refresh_timer();
	ai4seo_clear_dashboard_hidden_mode_timers();

	if (ai4seo_dashboard_user_interaction_timer) {
		clearTimeout( ai4seo_dashboard_user_interaction_timer );
		ai4seo_dashboard_user_interaction_timer = null;
	}

	if (ai4seo_dashboard_idle_reload_timer) {
		clearTimeout( ai4seo_dashboard_idle_reload_timer );
		ai4seo_dashboard_idle_reload_timer = null;
	}
}

// =========================================================================================== \\

/**
 * Schedule the next dashboard refresh with enhanced adaptive logic
 */
function ai4seo_schedule_dashboard_refresh() {
	// Precedence rule 1: User interaction locks take top priority.
	if (ai4seo_dashboard_user_interaction_lock) {
		return;
	}

	// Precedence rule 2: Browser tab visibility state overrides cadence.
	if (ai4seo_dashboard_is_hidden) {
		return; // Hidden mode handles its own scheduling.
	}

	// Don't schedule if refresh is locked.
	if (ai4seo_dashboard_refresh_lock) {
		return;
	}

	ai4seo_clear_dashboard_refresh_timer();

	let start_dashboard_refresh_delay;

	// Precedence rule 3: Failure backoff applies when request fails.
	if (ai4seo_dashboard_refresh_failures > 0) {
		// Exponential backoff: 10s -> 20s -> 40s -> 80s -> 120s (max).
		start_dashboard_refresh_delay = Math.min( ai4seo_dashboard_refresh_interval * Math.pow( 2, ai4seo_dashboard_refresh_failures ), 120000 );
	} else {
		// Precedence rule 4: No-change adaptive cadence for successful refreshes.
		start_dashboard_refresh_delay = ai4seo_dashboard_adaptive_interval;
	}

	// if element ai4seo-no-dashboard-refresh-delay exists, override start_dashboard_refresh_delay with 5 seconds.
	if (ai4seo_exists_$( '#ai4seo-no-dashboard-refresh-delay' )) {
		start_dashboard_refresh_delay = 5000;
	}

	ai4seo_start_dashboard_progress( start_dashboard_refresh_delay );

	ai4seo_dashboard_refresh_timer = setTimeout( ai4seo_fetch_and_update_dashboard, start_dashboard_refresh_delay );
}

// =========================================================================================== \\

/**
 * Clear the dashboard refresh timer
 */
function ai4seo_clear_dashboard_refresh_timer() {
	if (ai4seo_dashboard_refresh_timer) {
		clearTimeout( ai4seo_dashboard_refresh_timer );
		ai4seo_dashboard_refresh_timer = null;
	}
}

// =========================================================================================== \\

/**
 * Fetch fresh dashboard HTML and update the DOM
 */
function ai4seo_fetch_and_update_dashboard() {
	// Skip if already refreshing.
	if (ai4seo_dashboard_refresh_lock) {
		return;
	}

	// Skip if user interaction is locked.
	if (ai4seo_dashboard_user_interaction_lock) {
		return;
	}

	// Skip if dashboard container no longer exists.
	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	if (!ai4seo_exists_$( $dashboard )) {
		// ai4seo_console_debug(ai4seo_get_plugin_name() + ': $dashboard missing in ai4seo_fetch_and_update_dashboard() \u2014 cannot refresh dashboard metrics.');.
		return;
	}

	// Update metrics.
	if (ai4seo_dashboard_debug_metrics) {
		ai4seo_dashboard_metrics.refresh_attempts++;
	}

	// Set lock to prevent concurrent refreshes (single-flight semantics).
	ai4seo_dashboard_refresh_lock = true;

	// Store request reference for cancellation tracking.
	const this_request = {cancelled: false};
	ai4seo_dashboard_current_ajax_request = this_request;

	if (ai4seo_dashboard_debug_counter_enabled && ai4seo_exists_$( '#ai4seo-dashboard-debug-counter' )) {
		setTimeout(
            function () {
			ai4seo_add_loading_html_to_element( ai4seo_normalize_$( '#ai4seo-dashboard-debug-counter' ) );
            },
            1000
        );
	}

	let ajax_response_start_time = 0;

	if (ai4seo_dashboard_debug_metrics) {
		console.info( ai4seo_get_plugin_name() + ': Dashboard refresh attempt #' + ai4seo_dashboard_metrics.refresh_attempts );
		ajax_response_start_time = performance.now();
	}

	ai4seo_perform_ajax_call( 'ai4seo_get_dashboard_html', {}, false ) // auto_check_response = false.
		.then(
            response => {
			if (ai4seo_dashboard_debug_metrics) {
				let ajax_response_duration = performance.now() - ajax_response_start_time;
				ai4seo_dashboard_metrics.last_ajax_response_duration_ms = ajax_response_duration;
				console.info( ai4seo_get_plugin_name() + ': Dashboard AJAX response time: ' + ajax_response_duration.toFixed( 2 ) + 'ms' );
			}
			// Check if this request was cancelled (idempotent discard).
			if (this_request.cancelled) {
				return; // Discard response.
			}

			if (response && typeof response === 'string') {
				const ai4seo_changes_made = ai4seo_update_dashboard_content( response );

				// Adaptive interval logic based on changes.
				if (ai4seo_changes_made) {
					// Reset to base interval on changes (rule 5: reset on changes).
					ai4seo_dashboard_adaptive_interval = 10000;
					ai4seo_dashboard_no_changes_streak = 0;
				} else {
					// Increase interval for no changes (rule 7: adaptive cadence).
					ai4seo_dashboard_no_changes_streak++;
					ai4seo_dashboard_adaptive_interval = Math.min(
						10000 + (ai4seo_dashboard_no_changes_streak * 10000), // 20s, 30s, 40s, 50s, 60s
						60000 // Cap at 60s.
					);

					if (ai4seo_dashboard_debug_metrics) {
						ai4seo_dashboard_metrics.no_change_streak_length = ai4seo_dashboard_no_changes_streak;
					}
				}

				// Reset failure count on success.
				ai4seo_dashboard_refresh_failures = 0;
			}
            }
        )
		.catch(
            error => {
			// Check if this request was cancelled.
			if (this_request.cancelled) {
				return; // Discard error.
			}

			// Increment failure count for exponential backoff.
			ai4seo_dashboard_refresh_failures = Math.min( ai4seo_dashboard_refresh_failures + 1, ai4seo_dashboard_max_failures );
			// Silently log errors, don't show user notifications for auto-refresh failures.
			console.warn( ai4seo_get_plugin_name() + ': Dashboard auto-refresh failed (attempt ' + ai4seo_dashboard_refresh_failures + '):', error );
            }
        )
		.finally(
            () => {
			// Clear request reference.
			if (ai4seo_dashboard_current_ajax_request === this_request) {
				ai4seo_dashboard_current_ajax_request = null;
			}

			// Release lock and schedule next refresh.
			ai4seo_dashboard_refresh_lock = false;
			// Schedule next refresh based on current state.
			if (ai4seo_dashboard_is_hidden) {
				// Hidden mode handles its own scheduling.
				return;
			} else {
				ai4seo_schedule_dashboard_refresh();
			}
            }
        );
}

// =========================================================================================== \\

/**
 * Update dashboard content with new HTML using atomic DOM diffing
 *
 * @param {string} new_html - Fresh HTML content for the dashboard
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_update_dashboard_content(new_html) {
	const start_time = performance.now();

	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	if (!ai4seo_exists_$( $dashboard )) {
		console.warn( ai4seo_get_plugin_name() + ': .ai4seo-dashboard container missing in ai4seo_update_dashboard_content() \u2014 cannot update dashboard.' );
		return false;
	}

	const current_dashboard_element = $dashboard.get( 0 );
	// Preserve the current response boundary so older markup can be upgraded atomically when encountered.
	const current_refresh_root_element = current_dashboard_element ? current_dashboard_element.closest( '.ai4seo-dashboard-refresh-root' ) : null;
	// Retain the parsed root for the catch fallback without using unparsed response markup.
	let new_refresh_root_element = null;

	if (!current_dashboard_element) {
		return false;
	}

	try {
		// Clear previous changed nodes array.
		ai4seo_dashboard_changed_nodes = [];

		// Parse the response root first because notifications are synchronized separately from dashboard cards.
		const dom_parser = new DOMParser();
		const new_parsed_dom_html = dom_parser.parseFromString( new_html, 'text/html' );
		const $new_refresh_root = ai4seo_normalize_$( '.ai4seo-dashboard-refresh-root', new_parsed_dom_html );

		if (!ai4seo_exists_$( $new_refresh_root )) {
			console.warn( ai4seo_get_plugin_name() + ': New dashboard content missing .ai4seo-dashboard-refresh-root container' );
			return false;
		}

		new_refresh_root_element = $new_refresh_root.get( 0 );

		if (!new_refresh_root_element) {
			console.warn( ai4seo_get_plugin_name() + ': Unable to normalize new dashboard refresh root.' );
			return false;
		}

		// Require both managed regions before changing the live dashboard.
		const new_dashboard_element = new_refresh_root_element.querySelector( '.ai4seo-dashboard' );
		const new_notifications_element = new_refresh_root_element.querySelector( '.ai4seo-dashboard-notifications' );

		if (!new_dashboard_element || !new_notifications_element) {
			console.warn( ai4seo_get_plugin_name() + ': New dashboard refresh root is missing dashboard content.' );
			return false;
		}

		// Upgrade an open dashboard from the previous refresh structure in one atomic replacement.
		if (!current_refresh_root_element) {
			if (!current_dashboard_element.parentNode) {
				return false;
			}

			const replacement_refresh_root_element = new_refresh_root_element.cloneNode( true );
			current_dashboard_element.parentNode.replaceChild( replacement_refresh_root_element, current_dashboard_element );
			ai4seo_remove_dashboard_notification_duplicates( replacement_refresh_root_element );
			ai4seo_schedule_html_elements_init( replacement_refresh_root_element );

			return true;
		}

		// An incomplete root cannot be diffed safely, so restore both managed regions together.
		const current_notifications_element = current_refresh_root_element.querySelector( '.ai4seo-dashboard-notifications' );

		if (!current_notifications_element) {
			const replacement_refresh_root_element = new_refresh_root_element.cloneNode( true );
			current_refresh_root_element.parentNode.replaceChild( replacement_refresh_root_element, current_refresh_root_element );
			ai4seo_remove_dashboard_notification_duplicates( replacement_refresh_root_element );
			ai4seo_schedule_html_elements_init( replacement_refresh_root_element );

			return true;
		}

		// Reconcile server-managed notifications independently from the dashboard-card diff.
		const notifications_changed = ai4seo_sync_dashboard_notifications( current_notifications_element, new_notifications_element );
		const dashboard_changed = ai4seo_diff_and_patch_dashboard( current_dashboard_element, new_dashboard_element );
		const duplicates_removed = ai4seo_remove_dashboard_notification_duplicates( current_refresh_root_element );
		const changes_made = notifications_changed || dashboard_changed || duplicates_removed;

		// Performance guardrail - if diffing took too long, replace everything next time.
		const elapsed_time = performance.now() - start_time;

		if (elapsed_time > 100) {
			console.warn( ai4seo_get_plugin_name() + ': Dashboard diff took too long (' + elapsed_time.toFixed( 2 ) + 'ms), consider full replacement' );
		}

		// Apply highlighting to changed nodes (requirement 1).
		if (changes_made && ai4seo_dashboard_changed_nodes.length > 0) {
			ai4seo_apply_highlight_animation();
		}

		// If changes were made, reinitialize HTML elements.
		if (changes_made) {
			ai4seo_schedule_html_elements_init( current_refresh_root_element );
		}

		return changes_made;

	} catch (error) {
		console.warn( ai4seo_get_plugin_name() + ': Dashboard update failed:', error );

		// Fall back to the complete parsed root so the notification host and card grid remain aligned.
		const replacement_target_element = current_refresh_root_element || current_dashboard_element;

		if (!new_refresh_root_element || !replacement_target_element || !replacement_target_element.parentNode) {
			return false;
		}

		const replacement_refresh_root_element = new_refresh_root_element.cloneNode( true );
		replacement_target_element.parentNode.replaceChild( replacement_refresh_root_element, replacement_target_element );
		ai4seo_remove_dashboard_notification_duplicates( replacement_refresh_root_element );

		ai4seo_schedule_html_elements_init( replacement_refresh_root_element );

		return true; // Assume changes were made in fallback.
	}
}

// =========================================================================================== \\

/**
 * Reconcile the notification host from an authoritative dashboard refresh response.
 *
 * @param {Element} current_notifications_element
 * @param {Element} new_notifications_element
 * @returns {boolean} Whether notification markup changed
 */
function ai4seo_sync_dashboard_notifications(current_notifications_element, new_notifications_element) {
	if (!current_notifications_element || !new_notifications_element) {
		return false;
	}

	// Use notification indices as the server-managed identity so malformed or duplicate live notices cannot persist.
	let changes_made = false;
	const current_notifications_by_index = new Map();
	const new_notifications_by_index = new Map();
	const current_notification_elements = Array.from( current_notifications_element.children ).filter(
		function (notification_element) {
			return notification_element.classList.contains( 'ai4seo-notification' );
		}
	);
	const new_notification_elements = Array.from( new_notifications_element.children ).filter(
		function (notification_element) {
			return notification_element.classList.contains( 'ai4seo-notification' );
		}
	);

	current_notification_elements.forEach(
		function (notification_element) {
			const notification_index = notification_element.getAttribute( 'data-notification-index' ) || '';

			if (!notification_index || current_notifications_by_index.has( notification_index )) {
				notification_element.remove();
				changes_made = true;
				return;
			}

			current_notifications_by_index.set( notification_index, notification_element );
		}
	);

	new_notification_elements.forEach(
		function (notification_element) {
			const notification_index = notification_element.getAttribute( 'data-notification-index' ) || '';

			if (notification_index && !new_notifications_by_index.has( notification_index )) {
				new_notifications_by_index.set( notification_index, notification_element );
			}
		}
	);

	// Remove notifications that the server no longer renders before applying the response order.
	current_notifications_by_index.forEach(
		function (notification_element, notification_index) {
			if (!new_notifications_by_index.has( notification_index )) {
				notification_element.remove();
				current_notifications_by_index.delete( notification_index );
				changes_made = true;
			}
		}
	);

	// Replace excluded notice nodes directly, then retain the server-defined notification order in the dedicated host.
	let previous_notification_element = null;

	new_notifications_by_index.forEach(
		function (new_notification_element, notification_index) {
			let current_notification_element = current_notifications_by_index.get( notification_index );

			if (current_notification_element && current_notification_element.outerHTML !== new_notification_element.outerHTML) {
				const replacement_notification_element = new_notification_element.cloneNode( true );
				current_notifications_element.replaceChild( replacement_notification_element, current_notification_element );
				current_notification_element = replacement_notification_element;
				current_notifications_by_index.set( notification_index, current_notification_element );
				changes_made = true;
			}

			if (!current_notification_element) {
				current_notification_element = new_notification_element.cloneNode( true );
				current_notifications_element.appendChild( current_notification_element );
				current_notifications_by_index.set( notification_index, current_notification_element );
				changes_made = true;
			}

			if (previous_notification_element) {
				if (current_notification_element.previousElementSibling !== previous_notification_element) {
					current_notifications_element.insertBefore( current_notification_element, previous_notification_element.nextSibling );
					changes_made = true;
				}
			} else if (current_notifications_element.firstElementChild !== current_notification_element) {
				current_notifications_element.insertBefore( current_notification_element, current_notifications_element.firstChild );
				changes_made = true;
			}

			previous_notification_element = current_notification_element;
		}
	);

	return changes_made;
}

// =========================================================================================== \\

/**
 * Remove plugin notification copies outside a dashboard root synchronized from an AJAX response.
 * Initial WordPress notice setup may relocate the only rendered copy into the content wrapper,
 * so callers must run this cleanup only after the response has populated the canonical host.
 *
 * @param {Element} dashboard_refresh_root_element
 * @returns {boolean} Whether duplicate notifications were removed
 */
function ai4seo_remove_dashboard_notification_duplicates(dashboard_refresh_root_element) {
	if (!dashboard_refresh_root_element || dashboard_refresh_root_element.nodeType !== Node.ELEMENT_NODE) {
		return false;
	}

	// The synchronized host confirms that direct wrapper notices are stale copies rather than the initial live notice.
	const notifications_element = dashboard_refresh_root_element.querySelector( '.ai4seo-dashboard-notifications' );
	const content_wrapper_element = dashboard_refresh_root_element.closest( '.ai4seo-content-wrapper' );

	if (!notifications_element || !content_wrapper_element) {
		return false;
	}

	// Only direct wrapper children can be copies left outside the managed refresh boundary.
	const duplicate_notification_elements = Array.from( content_wrapper_element.children ).filter(
		function (child_element) {
			return child_element !== dashboard_refresh_root_element && child_element.classList.contains( 'ai4seo-notification' );
		}
	);

	duplicate_notification_elements.forEach(
		function (duplicate_notification_element) {
			duplicate_notification_element.remove();
		}
	);

	return duplicate_notification_elements.length > 0;
}

// =========================================================================================== \\

/**
 * Apply highlight animation to changed nodes
 */
function ai4seo_apply_highlight_animation() {
	// Batch DOM writes to avoid layout thrashing.
	ai4seo_dashboard_changed_nodes.forEach(
        function (node) {
		if (node && node.nodeType === Node.ELEMENT_NODE) {
			const $node = ai4seo_normalize_$( node );

			if (ai4seo_exists_$( $node )) {
				$node.addClass( 'ai4seo-transparent-animation' );
			}
		}
        }
    );

	// Remove highlighting after 3 seconds.
	setTimeout(
        function () {
		ai4seo_dashboard_changed_nodes.forEach(
            function (node) {
			if (node && node.nodeType === Node.ELEMENT_NODE) {
				const $node = ai4seo_normalize_$( node );

				if (ai4seo_exists_$( $node )) {
					$node.removeClass( 'ai4seo-transparent-animation' );
					ai4seo_remove_empty_class_attr( node ); // Clean up empty class attributes.
				}
			}
            }
        );
		ai4seo_dashboard_changed_nodes = [];
        },
        3000
    );
}

// =========================================================================================== \\

// Utility: remove empty class attribute.
function ai4seo_remove_empty_class_attr(el) {
	if (!el || el.nodeType !== Node.ELEMENT_NODE) {
		return;
	}
	const $element = ai4seo_normalize_$( el );

	if (!ai4seo_exists_$( $element )) {
		return;
	}

	const class_attribute = ($element.attr( 'class' ) || '').trim();

	if (class_attribute === '') {
		$element.removeAttr( 'class' );
	}
}

// =========================================================================================== \\

/**
 * Perform atomic DOM diffing and patching between old and new dashboard nodes
 *
 * @param {Element} old_dashboard_element - Current dashboard DOM node
 * @param {Element} new_dashboard_element - New dashboard DOM node
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_diff_and_patch_dashboard(old_dashboard_element, new_dashboard_element) {
	let changes_made = false;
	let new_cloned_element = null;

	// Compare node types.
	if (old_dashboard_element.nodeType !== new_dashboard_element.nodeType) {
		new_cloned_element = new_dashboard_element.cloneNode( true );

		old_dashboard_element.parentNode.replaceChild( new_cloned_element, old_dashboard_element );

		// Track replaced node for highlighting.
		if (new_cloned_element.nodeType === Node.ELEMENT_NODE) {
			ai4seo_dashboard_changed_nodes.push( new_cloned_element );
		}

		ai4seo_console_debug( ai4seo_get_plugin_name() + ': Node type changed, replaced entire node: ' + old_dashboard_element.tagName + ' to ' + new_dashboard_element.tagName, old_dashboard_element, new_dashboard_element );

		return true;
	}

	// Handle text nodes.
	if (old_dashboard_element.nodeType === Node.TEXT_NODE) {
		if (old_dashboard_element.textContent !== new_dashboard_element.textContent) {
			// console.debug('AI4SEO: Text content changed for node: ' + old_node.parentNode.nodeName + ' from ' + old_node.textContent + ' to ' + new_node.textContent);.
			old_dashboard_element.textContent = new_dashboard_element.textContent;

			changes_made = true;

			// Track parent element for highlighting (can't highlight text nodes directly).
			if (old_dashboard_element.parentNode && old_dashboard_element.parentNode.nodeType === Node.ELEMENT_NODE) {
				ai4seo_dashboard_changed_nodes.push( old_dashboard_element.parentNode );
			}

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Text content changed for node: ' + old_dashboard_element.parentNode.nodeName + ' from "' + old_dashboard_element.textContent + '" to "' + new_dashboard_element.textContent + '"', old_dashboard_element, new_dashboard_element );
		}

		return changes_made;
	}

	// Handle element nodes.
	if (old_dashboard_element.nodeType === Node.ELEMENT_NODE) {
		if (ai4seo_is_dashboard_diff_excluded( old_dashboard_element )) {
			return false;
		}

		// Compare tag names.
		if (old_dashboard_element.tagName !== new_dashboard_element.tagName) {
			// console.debug('AI4SEO: Tag name changed, replaced entire node: ' + old_node.tagName + ' to ' + new_node.tagName + ' (' + old_node.outerHTML + ' to ' + new_node.outerHTML + ')');.
			new_cloned_element = new_dashboard_element.cloneNode( true );

			old_dashboard_element.parentNode.replaceChild( new_cloned_element, old_dashboard_element );

			// Track replaced node for highlighting.
			ai4seo_dashboard_changed_nodes.push( new_cloned_element );

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Tag name changed, replaced entire node: ' + old_dashboard_element.tagName + ' to ' + new_dashboard_element.tagName, old_dashboard_element, new_dashboard_element );

			return true;
		}

		// Compare and update attributes.
		if (ai4seo_sync_node_attributes( old_dashboard_element, new_dashboard_element )) {
			changes_made = true;

			// Track element for highlighting when attributes change.
			ai4seo_dashboard_changed_nodes.push( old_dashboard_element );

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Attributes changed for node: ' + old_dashboard_element.tagName, old_dashboard_element, new_dashboard_element );
		}

		// Compare and update child nodes.
		if (ai4seo_sync_child_nodes( old_dashboard_element, new_dashboard_element )) {
			changes_made = true;

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Child nodes changed for node: ' + old_dashboard_element.tagName, old_dashboard_element, new_dashboard_element );
		}
	}

	return changes_made;
}

// =========================================================================================== \\

/**
 * Synchronize attributes between old and new nodes
 *
 * @param {Element} old_element
 * @param {Element} new_element
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_sync_node_attributes(old_element, new_element) {
	let changes_made = false;
	const old_attributes = old_element.attributes;
	const new_attributes = new_element.attributes;

	// Update/add attributes from new node.
	for (let i = 0; i < new_attributes.length; i++) {
		const this_new_attributes = new_attributes[i];
		const this_old_attributes_value = old_element.getAttribute( this_new_attributes.name );

		if (this_old_attributes_value !== this_new_attributes.value) {
			old_element.setAttribute( this_new_attributes.name, this_new_attributes.value );
			changes_made = true;
		}
	}

	// Remove attributes not in new node.
	for (let j = old_attributes.length - 1; j >= 0; j--) {
		const this_old_attributes = old_attributes[j];
		if (!new_element.hasAttribute( this_old_attributes.name )) {
			old_element.removeAttribute( this_old_attributes.name );
			changes_made = true;
		}
	}

	return changes_made;
}

// =========================================================================================== \\

/**
 * Synchronize child nodes between old and new nodes
 *
 * @param {Element} old_container_element
 * @param {Element} new_container_element
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_sync_child_nodes(old_container_element, new_container_element) {
	let changes_made = false;

	// If the container itself is excluded, skip all children work.
	if (ai4seo_is_dashboard_diff_excluded( old_container_element )) {
		return false;
	}

	let old_container_index = 0;
	let new_container_index = 0;

	function ai4seo_get_children_pairs() {
		// Preserve the current diff behavior by pairing the raw childNodes from both containers.
		return {
			old_children: Array.from( old_container_element.childNodes ),
			new_children: Array.from( new_container_element.childNodes )
		};
	}

	function ai4seo_is_ignorable_whitespace_text(node) {
		return node
			&& node.nodeType === Node.TEXT_NODE
			&& typeof node.textContent === 'string'
			&& node.textContent.trim() === '';
	}

	function ai4seo_is_notice_element(node) {
		if (!node || node.nodeType !== Node.ELEMENT_NODE) {
			return false;
		}

		/**
		 * Notice DOM element.
		 *
		 * @type {Element}
		 */
		const el = node;
		return el.classList.contains( 'notice' ) || el.classList.contains( 'ai4seo-notice' ) || el.hasAttribute( 'data-notification-index' );
	}

	function ai4seo_get_notice_index(node) {
		if (!node || node.nodeType !== Node.ELEMENT_NODE) {
			return '';
		}

		/**
		 * Notice DOM element.
		 *
		 * @type {Element}
		 */
		const el = node;
		return el.getAttribute( 'data-notification-index' ) || '';
	}

	function ai4seo_is_card_element(node) {
		if (!node || node.nodeType !== Node.ELEMENT_NODE) {
			return false;
		}

		/**
		 * Card DOM element.
		 *
		 * @type {Element}
		 */
		const el = node;
		return el.classList.contains( 'card' ) || el.classList.contains( 'ai4seo-card' );
	}

	/**
	 * Root dashboard DOM element.
	 *
	 * @type {Element}
	 */
	const old_container_root_element = old_container_element;
	const is_root_dashboard =
		old_container_element
		&& old_container_element.nodeType === Node.ELEMENT_NODE
		&& old_container_root_element.classList.contains( 'ai4seo-dashboard' );

	let children_pairs = ai4seo_get_children_pairs();
	let old_children = children_pairs.old_children;
	let new_children = children_pairs.new_children;

	while (old_container_index < old_children.length || new_container_index < new_children.length) {
		const this_old_child = old_children[old_container_index] || null;
		const this_new_child = new_children[new_container_index] || null;

		// if a child is excluded, skip it.
		if (ai4seo_is_dashboard_diff_excluded( this_old_child )) {
			old_container_index++;
			continue;
		}

		if (ai4seo_is_dashboard_diff_excluded( this_new_child )) {
			new_container_index++;
			continue;
		}

		// Ignore whitespace-only text nodes to avoid alignment drift.
		if (ai4seo_is_ignorable_whitespace_text( this_old_child )) {
			old_container_index++;
			continue;
		}

		if (ai4seo_is_ignorable_whitespace_text( this_new_child )) {
			new_container_index++;
			continue;
		}

		// Treat excluded nodes as "transparent": advance only the side that is excluded.
		if (this_old_child && this_old_child.nodeType === Node.ELEMENT_NODE && ai4seo_is_dashboard_diff_excluded( this_old_child )) {
			old_container_index++;
			continue;
		}

		if (this_new_child && this_new_child.nodeType === Node.ELEMENT_NODE && ai4seo_is_dashboard_diff_excluded( this_new_child )) {
			new_container_index++;
			continue;
		}

		// Case A: old exists, new missing -> removal candidate.
		if (this_old_child && !this_new_child) {
			old_container_element.removeChild( this_old_child );
			changes_made = true;

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Removed child node: ' + this_old_child.outerHTML );

			children_pairs = ai4seo_get_children_pairs();
			old_children = children_pairs.old_children;
			new_children = children_pairs.new_children;
			continue;
		}

		// Case B: new exists, old missing -> addition candidate.
		if (!this_old_child && this_new_child) {
			const this_cloned = this_new_child.cloneNode( true );
			old_container_element.appendChild( this_cloned );
			changes_made = true;

			ai4seo_console_debug( ai4seo_get_plugin_name() + ': Added child node: ' + this_cloned.outerHTML );

			if (this_cloned.nodeType === Node.ELEMENT_NODE) {
				ai4seo_dashboard_changed_nodes.push( this_cloned );
			}

			children_pairs = ai4seo_get_children_pairs();
			old_children = children_pairs.old_children;
			new_children = children_pairs.new_children;
			old_container_index++;
			new_container_index++;
			continue;
		}

		// Case C: both exist.
		if (this_old_child && this_new_child) {
			// Dashboard top-level heuristic:
			// If a notice disappears, do not "morph" it into the next card.
			if (is_root_dashboard) {
				const old_is_notice = ai4seo_is_notice_element( this_old_child );
				const new_is_notice = ai4seo_is_notice_element( this_new_child );

				if (old_is_notice && new_is_notice) {
					const old_notice_index = ai4seo_get_notice_index( this_old_child );
					const new_notice_index = ai4seo_get_notice_index( this_new_child );

					// If indices differ, most likely the old notice was removed.
					if (old_notice_index && new_notice_index && old_notice_index !== new_notice_index) {
						old_container_element.removeChild( this_old_child );
						changes_made = true;

						ai4seo_console_debug( ai4seo_get_plugin_name() + ': Removed notice node due to index mismatch: ' + this_old_child.outerHTML );

						children_pairs = ai4seo_get_children_pairs();
						old_children = children_pairs.old_children;
						new_children = children_pairs.new_children;
						continue;
					}
				}

				// Card -> Notice mismatch: insert the new notice before the current card.
				if (new_is_notice && !old_is_notice && ai4seo_is_card_element( this_old_child )) {
					const cloned_notice_element = this_new_child.cloneNode( true );
					old_container_element.insertBefore( cloned_notice_element, this_old_child );
					changes_made = true;

					ai4seo_console_debug( ai4seo_get_plugin_name() + ': Added notice node before card: ' + cloned_notice_element.outerHTML );

					if (cloned_notice_element.nodeType === Node.ELEMENT_NODE) {
						ai4seo_dashboard_changed_nodes.push( cloned_notice_element );
					}

					children_pairs = ai4seo_get_children_pairs();
					old_children = children_pairs.old_children;
					new_children = children_pairs.new_children;
					continue;
				}

				// Notice -> Card mismatch: remove the old notice (it likely vanished in new markup).
				if (old_is_notice && !new_is_notice && ai4seo_is_card_element( this_new_child )) {
					old_container_element.removeChild( this_old_child );
					changes_made = true;

					ai4seo_console_debug( ai4seo_get_plugin_name() + ': Removed notice node due to type mismatch with new card: ' + this_old_child.outerHTML );

					children_pairs = ai4seo_get_children_pairs();
					old_children = children_pairs.old_children;
					new_children = children_pairs.new_children;
					continue;
				}
			}

			changes_made = ai4seo_diff_and_patch_dashboard( this_old_child, this_new_child ) || changes_made;
			old_container_index++;
			new_container_index++;
			continue;
		}
	}

	return changes_made;
}


// === DASHBOARD REFRESH PROGRESS BAR ========================================== \\

const ai4seo_dashboard_progress_refresh_interval_ms = 200;
let ai4seo_dashboard_progress_interval = null;
let ai4seo_dashboard_refresh_end_time = null;
let ai4seo_dashboard_progress_start_time = null;
let ai4seo_dashboard_progress_duration_ms = 0;
let ai4seo_dashboard_progress_last_render_time = 0;

/**
 * Initialize progress bar UI
 */
function ai4seo_init_dashboard_progress_bar() {
	const $wrap_container = ai4seo_normalize_$( '.ai4seo-wrap' );

	if (!ai4seo_exists_$( $wrap_container )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $wrap missing in ai4seo_init_dashboard_progress_bar() \u2014 cannot attach progress UI.' );
		return;
	}

	const $dashboard = ai4seo_normalize_$( '.ai4seo-dashboard' );

	if (!ai4seo_exists_$( $dashboard )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $dashboard missing in ai4seo_init_dashboard_progress_bar() \u2014 cannot render progress bar.' );
		return;
	}

	// Remove existing if re-init.
	const $existing_progress_wrapper = ai4seo_normalize_$( '#ai4seo-dashboard-progress-wrapper' );

	if (ai4seo_exists_$( $existing_progress_wrapper )) {
		$existing_progress_wrapper.remove();
	}

	const $existing_debug_counter = ai4seo_normalize_$( '#ai4seo-dashboard-debug-counter' );

	if (ai4seo_exists_$( $existing_debug_counter )) {
		$existing_debug_counter.remove();
	}

	// Create wrapper.
	const $new_dashboard_progress_wrapper = jQuery(
        '<div>',
        {
		id: 'ai4seo-dashboard-progress-wrapper',
		css: {
			position: 'absolute',
			top: 0,
			left: 0,
			width: '100%',
			height: '3px',
			background: 'transparent',
			zIndex: 9999,
			display: ai4seo_dashboard_debug_counter_enabled ? 'block' : 'none'
		}
        }
    );

	// Create progress bar.
	const $new_dashboard_progress_bar = jQuery(
        '<div>',
        {
		id: 'ai4seo-dashboard-progress-bar',
		css: {
			height: '100%',
			width: '0%',
			background: 'rgba(84, 163, 203, 0.8)', // light blue, 50% transparent.
			transition: 'width 0.1s linear',
			display: ai4seo_dashboard_debug_counter_enabled ? 'block' : 'none'
		}
        }
    );

	$new_dashboard_progress_wrapper.append( $new_dashboard_progress_bar );

	$wrap_container.prepend( $new_dashboard_progress_wrapper );

	// Create debug counter.
	const $new_dashboard_debug_counter = jQuery(
        '<div>',
        {
		id: 'ai4seo-dashboard-debug-counter',
		text: '',
		css: {
			position: 'absolute',
			top: '4px',
			right: '8px',
			fontSize: '11px',
			background: 'rgba(0, 0, 0, 0.5)',
			color: '#fff',
			padding: '2px 5px',
			borderRadius: '3px',
			zIndex: 10000,
			display: ai4seo_dashboard_debug_counter_enabled ? 'block' : 'none',
			overflow: 'hidden'
		}
        }
    );

	$wrap_container.append( $new_dashboard_debug_counter );
}

// =========================================================================================== \\

/**
 * Start the progress countdown
 *
 * @param {number} duration_ms - total duration in milliseconds
 */
function ai4seo_start_dashboard_progress(duration_ms) {
	let $dashboard_progress_bar = ai4seo_normalize_$( '#ai4seo-dashboard-progress-bar' );

	if (!ai4seo_exists_$( $dashboard_progress_bar )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $dashboard_progress_bar missing in ai4seo_start_dashboard_progress() — initializing progress UI.' );
		ai4seo_init_dashboard_progress_bar();
	}

	$dashboard_progress_bar = ai4seo_normalize_$( '#ai4seo-dashboard-progress-bar' );

	if (!ai4seo_exists_$( $dashboard_progress_bar )) {
		ai4seo_console_debug( ai4seo_get_plugin_name() + ': $dashboard_progress_bar missing in ai4seo_start_dashboard_progress() \u2014 cannot start progress bar.' );
		return;
	}

	const now = Date.now();
	ai4seo_dashboard_progress_start_time = now;
	ai4seo_dashboard_progress_duration_ms = Math.max( 1, parseInt( duration_ms, 10 ) || 1 );
	ai4seo_dashboard_refresh_end_time = now + ai4seo_dashboard_progress_duration_ms;

	if (!ai4seo_dashboard_progress_interval) {
		ai4seo_update_dashboard_progress_ui( true );

		ai4seo_dashboard_progress_interval = setInterval(
			ai4seo_update_dashboard_progress_ui,
			ai4seo_dashboard_progress_refresh_interval_ms
		);
	}
}

// =========================================================================================== \\

/**
 * Update dashboard refresh progress UI at most every 200ms.
 *
 * @param {boolean} force_update
 */
function ai4seo_update_dashboard_progress_ui(force_update = false) {
	const now = Date.now();

	if (!force_update && ai4seo_dashboard_progress_last_render_time && now - ai4seo_dashboard_progress_last_render_time < ai4seo_dashboard_progress_refresh_interval_ms) {
		return;
	}

	ai4seo_dashboard_progress_last_render_time = now;

	let remaining = ai4seo_dashboard_refresh_end_time - now;

	if (remaining < 0) {
		remaining = 0;
	}

	const elapsed = ai4seo_dashboard_progress_start_time ? now - ai4seo_dashboard_progress_start_time : 0;
	const percent = Math.min( 100, Math.max( 0, (elapsed / ai4seo_dashboard_progress_duration_ms) * 100 ) );
	const $progress_bar = ai4seo_normalize_$( '#ai4seo-dashboard-progress-bar' );

	if (ai4seo_exists_$( $progress_bar )) {
		$progress_bar.css( 'width', percent + '%' );
	}

	if (ai4seo_dashboard_debug_counter_enabled) {
		const $debug_counter = ai4seo_normalize_$( '#ai4seo-dashboard-debug-counter' );

		if (ai4seo_exists_$( $debug_counter )) {
			$debug_counter.text( Math.ceil( remaining / 1000 ) + 's' );
		}
	}

	if (remaining <= 0 && ai4seo_dashboard_progress_interval) {
		clearInterval( ai4seo_dashboard_progress_interval );
		ai4seo_dashboard_progress_interval = null;
	}
}

// =========================================================================================== \\

/**
 * Reset the progress bar immediately
 */
function ai4seo_reset_dashboard_progress() {
	const $progress_bar = ai4seo_normalize_$( '#ai4seo-dashboard-progress-bar' );

	if (ai4seo_exists_$( $progress_bar )) {
		$progress_bar.css( 'width', '0%' );
	}

	if (ai4seo_dashboard_progress_interval) {
		clearInterval( ai4seo_dashboard_progress_interval );
		ai4seo_dashboard_progress_interval = null;
	}

	ai4seo_dashboard_progress_start_time = null;
	ai4seo_dashboard_progress_duration_ms = 0;
	ai4seo_dashboard_progress_last_render_time = 0;

	if (ai4seo_dashboard_debug_counter_enabled) {
		const $debug_counter = ai4seo_normalize_$( '#ai4seo-dashboard-debug-counter' );

		if (ai4seo_exists_$( $debug_counter )) {
			$debug_counter.text( '' );
		}
	}
}

// === DASHBOARD DIFF EXCLUSIONS ============================================================ \\

// 1) Configure which containers should be frozen during diffing.
// You can add classes, ids, or attributes. Two generic hooks are included:
// [data-ai4seo-ignore-during-dashboard-refresh="1"] and .ai4seo-ignore-during-dashboard-refresh
const ai4seo_dashboard_diff_exclude_selectors = [
	'[data-ai4seo-ignore-during-dashboard-refresh="1"]',
	'.ai4seo-ignore-during-dashboard-refresh',
	// Examples for cards you keep open/collapsed:.
	'.ai4seo-card.ai4seo-is-open',
	'.ai4seo-card.ai4seo-is-collapsed',
	'.ai4seo-card[data-ai4seo-keep-state="1"]'
];

// =========================================================================================== \\

/**
 * Public API: add more exclusion selectors at runtime.
 *
 * @param {string[]} selectors
 * @return {void}
 */
function ai4seo_register_dashboard_diff_exclusions(selectors) {
	if (!Array.isArray( selectors )) {
		return;
	}
	selectors.forEach(
        function (sel) {
		if (typeof sel === 'string' && sel.trim() && ai4seo_dashboard_diff_exclude_selectors.indexOf( sel ) === -1) {
			ai4seo_dashboard_diff_exclude_selectors.push( sel );
		}
        }
    );
}

// =========================================================================================== \\

/**
 * True if node is inside an excluded container.
 * Matches the node itself or any ancestor with a configured selector.
 *
 * @param {Node} node
 * @return {boolean}
 */
function ai4seo_is_dashboard_diff_excluded(node) {
	if (!node || node.nodeType !== Node.ELEMENT_NODE) {
		return false;
	}
	/**
	 * Dashboard diff DOM element.
	 *
	 * @type {Element}
	 */
	const el = node;

	// Fast path: generic hooks.
	if (el.closest( '[data-ai4seo-ignore-during-dashboard-refresh="1"], .ai4seo-ignore-during-dashboard-refresh' )) {
		return true;
	}

	// Custom selectors.
	for (let i = 0; i < ai4seo_dashboard_diff_exclude_selectors.length; i++) {
		const sel = ai4seo_dashboard_diff_exclude_selectors[i];
		try {
			if (el.closest( sel )) {
				return true;
			}
		} catch (e) {
			// Invalid selector should not break diffing.
			continue;
		}
	}
	return false;
}

// ___________________________________________________________________________________________ \\
// === TOASTS ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// =========================================================================================== \\
// Usage examples:
// ai4seo_show_toast({ type: 'success', message: wp.i18n.__('Your changes have been saved.', 'ai-for-seo') });
// ai4seo_show_toast({ type: 'error', title: wp.i18n.__('Save failed', 'ai-for-seo'), message: wp.i18n.__('Please try again.', 'ai-for-seo'), duration: 7000 });
// ai4seo_show_toast({ type: 'warning', message: wp.i18n.__('Check your settings.', 'ai-for-seo'), actions:[{label: wp.i18n.__('Open settings', 'ai-for-seo'), onClick: function(){ window.location = ai4seo_get_ai4seo_admin_url() + 'admin.php?page=ai-for-seo&ai4seo_subpage=settings'; }}] });
// =========================================================================================== \\


/**
 * Return the toast container as jQuery object. Create on demand.
 *
 * @returns {jQuery}
 */
function ai4seo_get_toast_container_$() {
	var $container = ai4seo_normalize_$( '#ai4seo-toasts' );

	if (!ai4seo_exists_$( $container )) {
		$container = jQuery( '<div id="ai4seo-toasts" class="ai4seo-toast-container" aria-live="polite" aria-atomic="true"></div>' );
		jQuery( 'body' ).append( $container );
	}

	return $container;
}

// =========================================================================================== \\

/**
 * Map toast type to Dashicons classes. Uses built-in WP icons, no extra deps.
 *
 * @param {string} type
 * @returns {string} HTML for the icon span
 */
function ai4seo_get_toast_icon_html(type) {
	var dashicon = 'dashicons-info';

	switch (type) {
		case 'error':
			dashicon = 'dashicons-dismiss';
			break;

		case 'warning':
			dashicon = 'dashicons-warning';
			break;

		case 'info':
			dashicon = 'dashicons-info';
			break;

		case 'loading':
			// Closest built-in loading-style icon in Dashicons.
			dashicon = 'dashicons-hourglass';
			break;

		default:
			dashicon = 'dashicons-yes-alt';
			break;
	}

	return '<span class="ai4seo-toast-icon dashicons ' + dashicon + '" aria-hidden="true"></span>';
}

// =========================================================================================== \\

// Normalize toast action URLs before they are assigned to href attributes.
function ai4seo_get_safe_toast_action_href(action_href) {
	if (typeof action_href !== 'string') {
		return '#';
	}

	// Keep empty actions inert and preserve in-page anchors for internal toast controls.
	const trimmed_action_href = action_href.trim();
	if (!trimmed_action_href) {
		return '#';
	}

	if (trimmed_action_href.charAt( 0 ) === '#') {
		return trimmed_action_href;
	}

	try {
		// Resolve relative URLs before checking the protocol so unsafe schemes cannot reach href.
		const action_url = new URL( trimmed_action_href, window.location.href );
		if (action_url.protocol === 'http:' || action_url.protocol === 'https:') {
			return trimmed_action_href;
		}
	} catch (error) {
		return '#';
	}

	return '#';
}

// =========================================================================================== \\

// Build a trusted toast message node by converting internal credit placeholders into badge spans.
function ai4seo_get_toast_message_with_credit_badges_$(message_template, credit_badge_replacements) {
	const $toast_message = jQuery( '<span></span>' );
	const replacement_tokens = Object.keys( credit_badge_replacements || {} );

	// Keep the fallback text-only when no badge placeholders are registered.
	if (!replacement_tokens.length) {
		$toast_message.text( message_template );
		return $toast_message;
	}

	// Escape internal placeholder tokens before building the split matcher.
	const escaped_replacement_tokens = replacement_tokens.map(
        function (replacement_token) {
		return replacement_token.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
        }
    );

	// Replace only known internal tokens so translated surrounding text remains plain text.
	message_template.split( new RegExp( '(' + escaped_replacement_tokens.join( '|' ) + ')' ) ).forEach(
        function (message_part) {
		if (Object.prototype.hasOwnProperty.call( credit_badge_replacements, message_part )) {
			$toast_message.append( jQuery( '<span class="ai4seo-credits-usage-badge"></span>' ).text( credit_badge_replacements[message_part] ) );
			return;
		}

		if (message_part) {
			$toast_message.append( document.createTextNode( message_part ) );
		}
        }
    );

	return $toast_message;
}

// =========================================================================================== \\

/**
 * Show a toast. Non-blocking. Auto-hide unless duration <= 0.
 *
 * @param {Object} opts
 *  - type: 'success'|'error'|'warning'|'info' (default: 'success')
 *  - title: string (optional)
 *  - message: string (required)
 *  - message_content: jQuery object with trusted internal nodes (optional)
 *  - duration: number ms (default: 5000; set 0 for sticky)
 *  - id: string (optional, replaces an existing toast with same id)
 *  - actions: [{label, href, onClick}] (optional)
 * @returns {HTMLElement|null}
 */
function ai4seo_show_toast(opts) {
	try {
		if (!opts || !opts.message) {
			ai4seo_console_debug( ai4seo_get_plugin_name() + ': ai4seo_show_toast() without message — skipped.' );
			return null;
		}

		var type = opts.type || 'info';
		var duration = (typeof opts.duration === 'number') ? opts.duration : 5000;

		var $holder = ai4seo_get_toast_container_$();
		if (!ai4seo_exists_$( $holder )) {
			if (window.wp && wp.a11y && wp.a11y.speak) {
				wp.a11y.speak( opts.message );
			}
			return null;
		}

		// Replace same-id toast.
		if (opts.id) {
			$holder.find( '.ai4seo-toast[data-toast-id="' + opts.id + '"]' ).remove();
		}

		// remove toasts with css class ai4seo-close-on-new-toast.
		$holder.find( '.ai4seo-toast.ai4seo-close-on-new-toast' ).remove();

		var $toast = jQuery( '<div class="ai4seo-toast ai4seo-toast-' + type + '" role="status" aria-live="polite"></div>' );

		// add id.
		if (opts.id) {
			$toast.attr( 'data-toast-id', opts.id );
		}

		// add ai4seo-close-on-new-toast class when auto_close_on_new_toast is set.
		if (opts.auto_close_on_new_toast) {
			$toast.addClass( 'ai4seo-close-on-new-toast' );
		}

		// Assemble the toast body after container state and positioning are finalized.
		var $content = jQuery( '<div class="ai4seo-toast-content"></div>' );

		$content.append( ai4seo_get_toast_icon_html( type ) );

		var $message_wrap = jQuery( '<div class="ai4seo-toast-message"></div>' );

		// Build toast title as text so caller-provided titles cannot inject markup.
		var $toast_title = jQuery( '<div class="ai4seo-text ai4seo-text-1"></div>' );
		if (opts.title) {
			$toast_title.text( opts.title );
		} else {
			$toast_title.text( ai4seo_get_type_based_fallback_toast_title( type ) );
		}
		$message_wrap.append( $toast_title );

		// Build toast messages as text unless a trusted internal caller passes prepared nodes.
		var $toast_message = jQuery( '<div class="ai4seo-text ai4seo-text-2"></div>' );
		if (opts.message_content && opts.message_content.jquery && opts.message_content.length) {
			$toast_message.append( opts.message_content );
		} else {
			$toast_message.text( opts.message );
		}
		$message_wrap.append( $toast_message );

		// Optional actions.
		if (opts.actions && opts.actions.length) {
			var $actions = jQuery( '<div class="ai4seo-toast-actions"></div>' );
			jQuery.each(
                opts.actions,
                function (i, act) {
				if (!act || !act.label) {
					return;
				}
				// Set action URLs through jQuery so the element template stays static.
				var $action_links = jQuery( '<a class="ai4seo-toast-action-link"></a>' );
				$action_links.attr( 'href', ai4seo_get_safe_toast_action_href( act.href ) );
				$action_links.text( act.label );
				if (typeof act.onClick === 'function') {
					$action_links.on(
                        'click',
                        function (e) {
						e.preventDefault();
						try {
							act.onClick( e );
						} catch (err) {
							console.error( ai4seo_get_plugin_name() + ': toast action error', err );
						}
                        }
                    );
				}
				$actions.append( $action_links );
                }
            );
			$message_wrap.append( $actions );
		}

		$content.append( $message_wrap );

		var $close_button = jQuery( '<button type="button" class="ai4seo-toast-close" aria-label="' + (wp && wp.i18n ? wp.i18n.__( 'Close', 'ai-for-seo' ) : 'Close') + '">×</button>' );

		// Progress bar.
		var $progress = jQuery( '<div class="ai4seo-toast-progress"><span></span></div>' );

		if (duration > 0) {
			$progress.addClass( 'active' );

			// Set animation duration dynamically to match JS timeout.
			$progress.find( 'span' ).css( 'animation-duration', duration + 'ms' );
		}

		$toast.append( $content ).append( $close_button ).append( $progress );
		$holder.append( $toast );

		// SR announce.
		if (window.wp && wp.a11y && wp.a11y.speak) {
			var announce = (opts.title ? (opts.title + '. ') : '') + opts.message;
			wp.a11y.speak( announce, 'polite' );
		}

		// Slide-in after paint.
		setTimeout(
            function () {
			$toast.addClass( 'active' );
            },
            1
        );

		// Auto close timers.
		var timer1 = null, timer2 = null;

		if (duration > 0) {
			timer1 = setTimeout(
                function () {
				$toast.removeClass( 'active' );
                },
                duration
            );

			timer2 = setTimeout(
                function () {
				$progress.removeClass( 'active' );
				setTimeout(
                    function () {
					$toast.remove();
                    },
                    400
                );
                },
                duration + 300
            );
		}

		// Manual close.
		$close_button.on(
            'click',
            function () {
			$toast.removeClass( 'active' );
			if (timer1) {
				clearTimeout( timer1 );
			}
			if (timer2) {
				clearTimeout( timer2 );
			}
			setTimeout(
                function () {
				$toast.remove();
                },
                400
            );
            }
        );

		return $toast.get( 0 );
	} catch (e) {
		console.error( ai4seo_get_plugin_name() + ': ai4seo_show_toast() failed', e );
		return null;
	}
}

// =========================================================================================== \\

/**
 * Clear all toasts.
 *
 * @return {void}
 */
function ai4seo_clear_all_toasts() {
	var $holder = ai4seo_get_toast_container_$();
	if (!ai4seo_exists_$( $holder )) {
		return;
	}
	$holder.find( '.ai4seo-toast' ).remove();
}

// =========================================================================================== \\

/**
 * Capitalize fallback title from type.
 *
 * @param {string} type
 * @return {string}
 */
function ai4seo_get_type_based_fallback_toast_title(type) {
	try {
		if (wp && wp.i18n) {
			switch (type) {
				case 'success':
					return wp.i18n.__( 'Success', 'ai-for-seo' );

				case 'error':
					return wp.i18n.__( 'Error', 'ai-for-seo' );

				case 'warning':
					return wp.i18n.__( 'Warning', 'ai-for-seo' );

				case 'info':
					return wp.i18n.__( 'Info', 'ai-for-seo' );

				case 'loading':
					return wp.i18n.__( 'Please wait', 'ai-for-seo' );

				default:
					return '';
			}
		}
	} catch (e) {
	}
	return type ? (type.charAt( 0 ).toUpperCase() + type.slice( 1 )) : 'Info';
}

// =========================================================================================== \\

function ai4seo_calculate_toast_duration_by_message_length(message, factor = 1) {
	const base_duration = 3000; // 3 seconds
	const extra_per_char = 50; // 50 ms per character
	const max_duration = 10000; // 10 seconds

	let calculated_duration = Math.round( (base_duration + (message.length * extra_per_char)) * factor );

	return Math.min( calculated_duration, max_duration );
}


// =========================================================================================== \\

function ai4seo_show_success_toast(message, duration, message_content) {
	if (!duration) {
		duration = ai4seo_calculate_toast_duration_by_message_length( message );
	}

	return ai4seo_show_toast(
        {
		type: 'success',
		message: message,
		message_content: message_content,
		duration: duration
        }
    );
}

// =========================================================================================== \\

function ai4seo_show_error_toast(error_code, message, duration) {
	if (typeof message === 'object' && message !== null) {
		message = (typeof message.message !== 'undefined' && message.message) ? message.message : wp.i18n.__( 'An error occurred. Please try again or contact support.', 'ai-for-seo' );
	}

	if (!message) {
		message = wp.i18n.__( 'An error occurred. Please try again or contact support.', 'ai-for-seo' );
	}

	message = message + (error_code ? ' (Error #' + error_code + ')' : '');

	if (!duration) {
		duration = ai4seo_calculate_toast_duration_by_message_length( message, 1.7 );
	}

	return ai4seo_show_toast(
        {
		type: 'error',
		message: message,
		duration: duration
        }
    );
}

// =========================================================================================== \\

function ai4seo_show_info_toast(message, duration) {
	if (!duration) {
		duration = ai4seo_calculate_toast_duration_by_message_length( message, 1.3 );
	}

	return ai4seo_show_toast(
        {
		type: 'info',
		message: message,
		duration: duration
        }
    );
}

// =========================================================================================== \\

function ai4seo_show_loading_toast(message, duration) {
	if (!duration) {
		duration = 10000;
	}

	if (!message) {
		message = wp.i18n.__( 'Loading...', 'ai-for-seo' );
	}

	return ai4seo_show_toast(
        {
		type: 'loading',
		message: message,
		duration: duration,
		auto_close_on_new_toast: true
        }
    );
}

// =========================================================================================== \\

function ai4seo_show_warning_toast(message, duration) {
	if (!duration) {
		duration = ai4seo_calculate_toast_duration_by_message_length( message, 1.5 );
	}

	return ai4seo_show_toast(
        {
		type: 'warning',
		message: message,
		duration: duration
        }
    );
}

// =========================================================================================== \\

function ai4seo_show_generic_saved_successfully_toast() {
	return ai4seo_show_success_toast( wp.i18n.__( 'Saved.', 'ai-for-seo' ) );
}

// =========================================================================================== \\

function ai4seo_show_generic_error_toast(error_code, message) {
	if (!error_code) {
		error_code = 912912;
	}

	return ai4seo_show_error_toast( error_code, message );
}
