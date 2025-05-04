// Prepare variables
var ai4seo_post_outputs = {};
var ai4seo_remaining_credits = 0;
var ai4seo_admin_ajax_url = ai4seo_get_full_domain() + "/wp-admin/admin-ajax.php";
var ai4seo_admin_url = ai4seo_get_full_domain() + "/wp-admin/admin.php";
var ai4seo_admin_plugin_page_url = ai4seo_get_full_domain() + "/wp-admin/admin.php?page=ai-for-seo";
var ai4seo_admin_installed_plugins_page_url = ai4seo_get_full_domain() + "/wp-admin/plugins.php";
var ai4seo_official_contact_url = "https://aiforseo.ai/contact";
var ai4seo_mousedown_origin = null;

var ai4seo_svg_icons = {
    'circle-check': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
    'rotate': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-primary" d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5z"/><path class="fa-secondary" d="M16 319.6l0-7.6c0-13.3 10.7-24 24-24h7.6c.2 0 .5 0 .7 0H168c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6c0-.2 0-.5 0-.7z"/></svg>',
    'square-xmark': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
}

var ai4seo_seo_inputs = {
    // Yoast elements
    '#yoast-google-preview-title-metabox > div > div > div': {'metadata_identifier': 'meta-title','additional_selectors': ["#yoast_wpseo_title"], 'key_by_key': true, "processing-context": "metadata"},
    '#yoast-google-preview-description-metabox > div > div > div': {'metadata_identifier': 'meta-description', 'additional_selectors': ["#yoast_wpseo_metadesc"], 'key_by_key': false, "processing-context": "metadata"},

    '#facebook-title-input-metabox > div > div > div': {'metadata_identifier': 'facebook-title', 'key_by_key': false, "processing-context": "metadata"},
    '#facebook-description-input-metabox > div > div > div': {'metadata_identifier': 'facebook-description', 'additional_selectors': ["#yoast_wpseo_opengraph-description"], 'key_by_key': false, "processing-context": "metadata"},
    '#social-title-input-metabox > div > div > div': {'metadata_identifier': 'facebook-title', 'key_by_key': false, "processing-context": "metadata"},
    '#social-description-input-metabox > div > div > div': {'metadata_identifier': 'facebook-description', 'additional_selectors': ["#yoast_wpseo_opengraph-description"], 'key_by_key': false, "processing-context": "metadata"},

    '#twitter-title-input-metabox > div > div > div': {'metadata_identifier': 'twitter-title', 'key_by_key': false, "processing-context": "metadata"},
    '#twitter-description-input-metabox > div > div > div': {'metadata_identifier': 'twitter-description', 'additional_selectors': ["#yoast_wpseo_twitter-description"], 'key_by_key': false, "processing-context": "metadata"},
    '#x-title-input-metabox > div > div > div': {'metadata_identifier': 'twitter-title', 'key_by_key': false, "processing-context": "metadata"},
    '#x-description-input-metabox > div > div > div': {'metadata_identifier': 'twitter-description', 'additional_selectors': ["#yoast_wpseo_twitter-description"], 'key_by_key': false, "processing-context": "metadata"},

    '#yoast-google-preview-title-modal > div > div > div': {'metadata_identifier': 'twitter-title', 'additional_selectors': ["#yoast_wpseo_title"], 'key_by_key': true, "processing-context": "metadata"},
    '#yoast-google-preview-description-modal > div > div > div': {'metadata_identifier': 'twitter-description', 'additional_selectors': ["#yoast_wpseo_metadesc"], 'key_by_key': false, "processing-context": "metadata"},

    '#facebook-title-input-modal > div > div > div': {'metadata_identifier': 'facebook-title', 'additional_selectors': ["#yoast_wpseo_opengraph-title"], 'key_by_key': false, "processing-context": "metadata"},
    '#facebook-description-input-modal > div > div > div': {'metadata_identifier': 'facebook-description', 'additional_selectors': ["#yoast_wpseo_opengraph-description"], 'key_by_key': false, "processing-context": "metadata"},

    '#twitter-title-input-modal > div > div > div': {'metadata_identifier': 'twitter-title', 'additional_selectors': ["#yoast_wpseo_twitter-title"], 'key_by_key': false, "processing-context": "metadata"},
    '#twitter-description-input-modal > div > div > div': {'metadata_identifier': 'twitter-description', 'additional_selectors': ["#yoast_wpseo_twitter-description"], 'key_by_key': false, "processing-context": "metadata"},

    // "AI for SEO" Metadata Editor modal-elements
    '#ai4seo_metadata_meta-title': {'metadata_identifier': 'meta-title', 'key_by_key': true, "processing-context": "metadata"},
    '#ai4seo_metadata_meta-description': {'metadata_identifier': 'meta-description', 'key_by_key': false, "processing-context": "metadata"},

    '#ai4seo_metadata_facebook-title': {'metadata_identifier': 'facebook-title', 'key_by_key': false, "processing-context": "metadata"},
    '#ai4seo_metadata_facebook-description': {'metadata_identifier': 'facebook-description', 'key_by_key': false, "processing-context": "metadata"},

    '#ai4seo_metadata_twitter-title': {'metadata_identifier': 'twitter-title', 'key_by_key': false, "processing-context": "metadata"},
    '#ai4seo_metadata_twitter-description': {'metadata_identifier': 'twitter-description', 'key_by_key': false, "processing-context": "metadata"},

    // "AI for SEO" Attachment Attributes Editor modal-elements
    '#ai4seo_attachment_attribute_title': {'attachment_attributes_identifier': 'title', 'key_by_key': false, "processing-context": "attachment-attributes"},
    '#ai4seo_attachment_attribute_alt-text': {'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, "processing-context": "attachment-attributes"},
    '#ai4seo_attachment_attribute_caption': {'attachment_attributes_identifier': 'caption', 'key_by_key': false, "processing-context": "attachment-attributes"},
    '#ai4seo_attachment_attribute_description': {'attachment_attributes_identifier': 'description', 'key_by_key': false, "processing-context": "attachment-attributes"},

    // Be-Builder elements
    '.preview-mfn-meta-seo-titleinput': {'metadata_identifier': 'meta-title', 'key_by_key': true, "processing-context": "metadata"},
    '.preview-mfn-meta-seo-descriptioninput': {'metadata_identifier': 'meta-description', 'key_by_key': false, "processing-context": "metadata"},
    'input[name=mfn-meta-seo-title]': {'metadata_identifier': 'meta-title', 'key_by_key': true, "processing-context": "metadata"},
    'input[name=mfn-meta-seo-description]': {'metadata_identifier': 'meta-description', 'key_by_key': false, "processing-context": "metadata"},

    '#social-title-input-modal > div > div > div': {'metadata_identifier': 'facebook-title', 'key_by_key': false, "processing-context": "metadata"},
    '#social-description-input-modal > div > div > div': {'metadata_identifier': 'facebook-description', 'additional_selectors': ["#yoast_wpseo_twitter-description"], 'key_by_key': false, "processing-context": "metadata"},

    '#x-title-input-modal > div > div > div': {'metadata_identifier': 'twitter-title', 'key_by_key': false, "processing-context": "metadata"},
    '#x-description-input-modal > div > div > div': {'metadata_identifier': 'twitter-description', 'additional_selectors': ["#yoast_wpseo_twitter-description"], 'key_by_key': false, "processing-context": "metadata"},

    // Attachments
    '.post-type-attachment #title[name=post_title]': {'attachment_attributes_identifier': 'title', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.post-type-attachment #attachment_alt[name=_wp_attachment_image_alt]': {'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.post-type-attachment #attachment_caption[name=excerpt]': {'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.post-type-attachment #attachment_content[name=content]': {'attachment_attributes_identifier': 'description', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.attachment-info .setting #attachment-details-two-column-title': {'attachment_attributes_identifier': 'title', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.attachment-info .setting #attachment-details-two-column-alt-text': {'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.attachment-info .setting #attachment-details-two-column-caption': {'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
    '.attachment-info .setting #attachment-details-two-column-description': {'attachment_attributes_identifier': 'description', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', "processing-context": "attachment-attributes"},
};

var ai4seo_content_containers = [
    ".wp-block-post-title", ".editor-post-excerpt__textarea textarea", ".wp-block-paragraph", ".wp-block-post-content", // Gutenberg
    "header h1.title", ".elementor-widget-container", ".item-preview-content", // Elementor
    ".mce-content-body", ".mcb-wrap-inner", ".the_content_wrapper", // Be-Builder
    "#titlediv > #titlewrap > input", ".wp-editor-area", ".woocommerce-Tabs-panel", // WooCommerce products
];

let ai4seo_generate_all_button_selectors = {
    "metadata": ["#wpseo-metabox-root", "#ai4seo-generate-all-metadata-button-hook"],
    "attachment-attributes": [".edit-attachment-frame .media-frame-content .attachment-info .details", ".post-type-attachment .wp_attachment_details.edit-form-section", "#ai4seo-generate-all-attachment-attributes-button-hook"],
}

var ai4seo_error_codes_and_messages = {
    "12127323": wp.i18n.__("Could not initialize connection to AI for SEO server. Please contact the plugin developer.", "ai-for-seo"),
    "13127323": wp.i18n.__("Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.", "ai-for-seo"),
    "21127323": wp.i18n.__("Could not read post content.", "ai-for-seo"),
    "22127323": wp.i18n.__("Posts content is empty.", "ai-for-seo"),
    "351229323": wp.i18n.__("Posts content is empty.", "ai-for-seo"),
    "491320823": wp.i18n.__("Posts content is too short.", "ai-for-seo"),
    "28127323": wp.i18n.__("Could not execute API call.", "ai-for-seo"),
    "31127323": wp.i18n.__("AI for SEO server call did not return a success value. Please try again.", "ai-for-seo"),
    "47127323": wp.i18n.__("AI for SEO server call returned an invalid success value. Please try again.", "ai-for-seo"),
    "48127323": wp.i18n.__("AI for SEO server call did not return data. Please try again.", "ai-for-seo"),
    "49127323": wp.i18n.__("AI for SEO server call returned an empty data array. Please try again.", "ai-for-seo"),
    "50127323": wp.i18n.__("AI for SEO server call did not return consumed Credits. Please try again.", "ai-for-seo"),
    "51127323": wp.i18n.__("AI for SEO server call did not return new Credits balance. Please try again.", "ai-for-seo"),
    "52127323": wp.i18n.__("AI for SEO server call returned an invalid data array. Please try again.", "ai-for-seo"),
    "291215624": wp.i18n.__("AI for SEO server call returned an invalid data array. Please try again.", "ai-for-seo"),
    "301215624": wp.i18n.__("AI for SEO server call returned an invalid data array. Please try again.", "ai-for-seo"),
    "311215624": wp.i18n.__("AI for SEO server call returned an invalid data array. Please try again.", "ai-for-seo"),
    "1115424": wp.i18n.__("Your AI for SEO account does not contain sufficient Credits. Please more add Credits to your account.", "ai-for-seo") + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__("Click here to add Credits", "ai-for-seo") + "</a>",
};

var ai4seo_robhub_api_response_error_codes = [32127323, 18197323, 311823824];

var ai4seo_robhub_api_response_error_codes_and_messages = {
    "client secret is invalid. Api-Error-Code: 351816823": wp.i18n.__("Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.", "ai-for-seo"),
    "client is not active. Api-Error-Code: 361816823": wp.i18n.__("Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.", "ai-for-seo"),
    "could not create client. Api-Error-Code: 571931823": wp.i18n.__("Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.", "ai-for-seo"),
    ": client not found. Api-Error-Code: 581931823": wp.i18n.__("Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.", "ai-for-seo"),
    "client has insufficient credits": wp.i18n.__("Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.", "ai-for-seo") + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__("Click here to add Credits", "ai-for-seo") + "</a>",
    "No credits left. Please buy more credits.": wp.i18n.__("Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.", "ai-for-seo") + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__("Click here to add Credits", "ai-for-seo") + "</a>",
    "Too Many Requests. Api-Error-Code: 381816823": wp.i18n.__("Maximum number of requests reached. Please try again later.", "ai-for-seo"),
    "Too Many Requests. Api-Error-Code: 591931823": wp.i18n.__("Maximum number of requests reached. Please try again later.", "ai-for-seo"),
    "input parameter is too short": wp.i18n.__("The provided content length insufficient for optimal SEO performance.", "ai-for-seo"),
    "We detected inappropriate content": wp.i18n.__("The provided post or media file contains inappropriate content. Please adjust your content and try again.", "ai-for-seo"),
    "client blocked from using this service": wp.i18n.__("Your AI for SEO account has been blocked from using this service due to suspicious activity. Please contact the plugin developer if you believe this is an error.", "ai-for-seo"),
};

var ai4seo_context = ai4seo_get_context();

var ai4seo_click_function_containers = [
    "#yoast-google-preview-modal-open-button",
    "#yoast-facebook-preview-modal-open-button",
    "#yoast-twitter-preview-modal-open-button",
    "#wpseo-meta-tab-content",
    "#wpseo-meta-tab-social",

    "#yoast-search-appearance-modal-open-button",
    "#yoast-social-appearance-modal-open-button",
    ".sc-gKPRtg",
    ".attachment-preview > .thumbnail",
    ".media-modal .edit-media-header button.left.dashicons",
    ".media-modal .edit-media-header button.right.dashicons",

    "#page-options-tab",
    "#elementor-panel-header-menu-button",
    "button[value=document-settings]",
    "button.elementor-tab-control-settings",
    "button.elementor-tab-control-yoast-seo-tab",
];

var ai4seo_admin_scripts_version_number = ai4seo_get_admin_scripts_version_number();

var ai4seo_js_file_path = ai4seo_get_ai4seo_plugin_directory_url() + "/assets/js/ai-for-seo-scripts.js?ver=" + ai4seo_admin_scripts_version_number;
var ai4seo_js_file_id = "ai-for-seo-scripts-js";

var ai4seo_css_file_path = ai4seo_get_ai4seo_plugin_directory_url() + "/assets/css/ai-for-seo-styles.css?ver=" + ai4seo_admin_scripts_version_number;
var ai4seo_css_file_id = "ai-for-seo-styles-css";

var ai4seo_just_clicked_modal_wrapper = false;
var ai4seo_min_content_length = 75;

var ai4seo_supported_mime_types = ["image/jpeg", "JPEG", "image/jpg", "JPG", "image/png", "PNG", "image/gif", "GIF", "image/webp", "WEBP"];

var ai4seo_attachment_mime_type_selectors = [".media-frame-content .attachment-info .details .file-type", "#minor-publishing #misc-publishing-actions .misc-pub-filetype"];

// allowed ajax function (also change in ai-for-seo.php file)
let ai4seo_allowed_ajax_actions = [
    "ai4seo_save_anything",
    "ai4seo_display_license_information", "ai4seo_dismiss_performance_notice",
    "ai4seo_show_metadata_editor", "ai4seo_show_attachment_attributes_editor",
    "ai4seo_generate_metadata", "ai4seo_generate_attachment_attributes",
    "ai4seo_reject_tos", "ai4seo_accept_tos", "ai4seo_show_terms_of_service", "ai4seo_dismiss_one_time_notice",
    "ai4seo_reset_plugin_data", "ai4seo_stop_bulk_generation",
    "ai4seo_disable_payg", "ai4seo_init_purchase",
];


// ___________________________________________________________________________________________ \\
// === INIT ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

if (typeof jQuery === 'function') {
    // Call above function for each editor element
    jQuery(document).ready(function(){
        /**
         * Initialize page load time
         */
        if (typeof window.ai4seo_page_load_time === 'undefined') {
            window.ai4seo_page_load_time = Date.now();
        }

        setTimeout(function() {
            // init html element
            ai4seo_init_html_elements();
        }, 100);

        // Init html elements within the media-modal
        ai4seo_init_html_elements_for_media_modal()

        // Add click-functions to parent-window for ai4seo_click_function_containers-elements if they exist
        if (ai4seo_exists(ai4seo_click_function_containers)) {
            // Loop through all click-function-containers
            for (var i = 0; i < ai4seo_click_function_containers.length; i++) {
                // Add click-function to parent-window
                jQuery("body", window.parent.document).on("click", ai4seo_click_function_containers[i], function() {
                    setTimeout(function() {
                        // Call function to load js-file to main-window
                        ai4seo_load_js_file(ai4seo_js_file_path, ai4seo_js_file_id);

                        // Call function to load css-file to main-window
                        ai4seo_load_css_file(ai4seo_css_file_path, ai4seo_css_file_id);

                        // Call function to load ai4seo_localization-object to main-window
                        ai4seo_set_localization_to_window_top();

                        // Init buttons
                        setTimeout(function() {
                            ai4seo_init_html_elements();
                        }, 200);
                    }, 100);
                });
            }
        }
    });
}

// =========================================================================================== \\

function ai4seo_load_js_file(url, script_id = false, callback = false) {
    // Stop script if no url is given
    if (!url) {
        return;
    }

    // Check if script is already loaded
    if (ai4seo_exists("#" + script_id)) {
        return;
    }

    // Define variable for the script-element
    var script = window.top.document.createElement("script");

    // Set type-attribute for the script-element
    script.type = "text/javascript";

    // Set src-attribute for the script-element
    script.src = url;

    // Set id-attribute for the script-element if an id is given
    if (script_id) {
        script.id = script_id;
    }

    // Add callback-function to the script-element if a callback is needed after the script is loaded
    if (callback) {
        script.onload = callback;
    }

    // Add script-element to the head-element of the parent window
    window.top.document.head.appendChild(script);
}

// =========================================================================================== \\

function ai4seo_load_css_file(url, script_id = false, callback = false) {
    // Stop script if no url is given
    if (!url) {
        return;
    }

    // Check if script is already loaded
    if (ai4seo_exists("#" + script_id)) {
        return;
    }

    // Define variable for the link-element
    var link = window.top.document.createElement("link");

    // Set type-attribute for the link-element
    link.type = "text/css";

    // Set rel-attribute for the link-element
    link.rel = "stylesheet";

    // Set href-attribute for the link-element
    link.href = url;

    // Set media-attribute for the link-element
    link.media = "all";

    // Set id-attribute for the link-element if an id is given
    if (script_id) {
        link.id = script_id;
    }

    // Add callback-function to the link-element if a callback is needed after the link is loaded
    if (callback) {
        link.onload = callback;
    }

    // Add link-element to the head-element of the parent window
    window.top.document.head.appendChild(link);
}

// =========================================================================================== \\

function ai4seo_set_localization_to_window_top() {
    // check if ai4seo_localization exists -> should be defined through wp_localize_script
    if (typeof ai4seo_localization === "undefined") {
        return;
    }

    window.top.ai4seo_localization = ai4seo_localization;
}

// =========================================================================================== \\

function ai4seo_init_html_elements() {
    // Add tooltip functionality
    ai4seo_init_tooltips();

    // Add countdown functionality
    ai4seo_init_countdown_elements();

    // Add select all / unselect all checkbox functionality
    ai4seo_init_select_all_checkboxes();

    // init checkbox containers
    ai4seo_init_checkbox_containers();

    if (ai4seo_does_user_need_to_accept_tos_toc_and_pp()) {
        // workaround: if the checkbox is already checked when the page is loaded, the button is not enabled
        setTimeout(function() {
            ai4seo_refresh_tos_accept_button_state();
        }, 250);

        // stop script if user needs to accept TOS, TOC and PP
        return;
    }

    // Init 'Generate with AI' buttons
    ai4seo_init_generate_buttons();

    // Add 'Generate all with AI' buttons
    ai4seo_init_generate_all_button();

    // Add open-layer-button to edit-page-header
    ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header();

    // Add open-layer-button to be-builder-navigation
    ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation();

    // Add open-layer-button to elementor-navigation
    ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation();

    // Init forms on license page
    ai4seo_init_license_form();
}

// =========================================================================================== \\

function ai4seo_toggle_sidebar() {
    if (!ai4seo_exists(".ai4seo-sidebar")) {
        return;
    }

    var sidebar = ai4seo_jQuery(".ai4seo-sidebar");

    if (sidebar.hasClass("ai4seo-sidebar-open")) {
        sidebar.removeClass("ai4seo-sidebar-open");

        // Remove the click event listener for outside clicks
        document.removeEventListener("click", ai4seo_handle_sidebar_outside_click);
    } else {
        sidebar.addClass("ai4seo-sidebar-open");

        // Add a click event listener for outside clicks
        document.addEventListener("click", ai4seo_handle_sidebar_outside_click);
    }
}

// =========================================================================================== \\

function ai4seo_toggle_visibility(element, caret_down_element, caret_up_element, duration = 0) {
    if (!ai4seo_exists(element)) {
        return;
    }

    element = ai4seo_jQuery(element);

    const is_visible = element.is(":visible");

    if (is_visible) {
        element.hide(duration);

        if (ai4seo_exists(caret_down_element)) {
            ai4seo_jQuery(caret_down_element).show();
        }

        if (ai4seo_exists(caret_up_element)) {
            ai4seo_jQuery(caret_up_element).hide();
        }
    } else {
        element.show(duration);

        if (ai4seo_exists(caret_down_element)) {
            ai4seo_jQuery(caret_down_element).hide();
        }

        if (ai4seo_exists(caret_up_element)) {
            ai4seo_jQuery(caret_up_element).show();
        }
    }
}

// =========================================================================================== \\

function ai4seo_open_get_more_credits_modal() {
    ai4seo_open_modal_from_schema("get-more-credits", {modal_size: "small"});

    if (!ai4seo_exists("#ai4seo-get-more-credits .ai4seo-get-more-credits-section")) {
        return;
    }

    let all_items = ai4seo_jQuery("#ai4seo-get-more-credits .ai4seo-get-more-credits-section");

    // remove transition and transform -100px to the left
    all_items.css("transition", "transform 0s");
    all_items.css("transform", "translateX(-100px)");
    all_items.css("opacity", "0");

    // go through each item
    all_items.each(function (index) {
        // Use a block-scoped variable to preserve the value of n
        const delay = index * 250;
        const item = ai4seo_jQuery(this);
        setTimeout(function () {
            item.css("transition", "0.5s ease-in-out");
            item.css("transform", "translateX(0)");
            item.css("opacity", "1");
        }, delay);
    });
}

// =========================================================================================== \\

function ai4seo_handle_sidebar_outside_click(event) {
    if (!ai4seo_exists(".ai4seo-sidebar")) {
        return;
    }

    var sidebar = ai4seo_jQuery(".ai4seo-sidebar");
    var toggle_button = ai4seo_jQuery(".ai4seo-mobile-top-bar-toggle-button");

    if (!sidebar.hasClass("ai4seo-sidebar-open")) {
        return;
    }

    if(!sidebar.is(event.target) && sidebar.has(event.target).length === 0 && !toggle_button.is(event.target) && toggle_button.has(event.target).length === 0) {
        sidebar.removeClass("ai4seo-sidebar-open");

        // Remove the click event listener for outside clicks
        document.removeEventListener("click", ai4seo_handle_sidebar_outside_click);
    }
}

// =========================================================================================== \\

function ai4seo_init_html_elements_for_media_modal() {
    // Prepare variables
    var max_attempts = 10;
    var attempts = 0;
    var interval = 500;

    function ai4seo_check_visibility() {
        attempts++;

        // Check if the media-modal-element is visible
        if (ai4seo_exists(".media-modal.wp-core-ui")) {
            // Call function to init html elements
            ai4seo_init_html_elements();
            return;
        }

        // Stop function if the maximum number of attempts has been reached
        if (attempts >= max_attempts) {
            return;
        }

        // Continue checking after the specified interval
        setTimeout(ai4seo_check_visibility, interval);
    }

    // Start the checking process
    ai4seo_check_visibility();
}

// =========================================================================================== \\

function ai4seo_init_generate_buttons() {
    // Check if current page is attachment-page
    if (ai4seo_is_attachment_post_type()) {
        // Stop script if the current attachment doesn't contain supported mime type
        if (!ai4seo_is_attachment_mime_type_supported()) {
            return;
        }
    }

    if (ai4seo_exists(".ai4seo-generate-button")) {
        ai4seo_jQuery(".ai4seo-generate-button").remove();
    }

    // Loop through mapping and call function to add button-element
    jQuery.each(ai4seo_seo_inputs, function(selector, value) {
        // check if a jquery element exists for the selector
        if (!ai4seo_exists(selector)) {
            return;
        }

        // YOAST SEO INTEGRATION
        // Call function to add button to yoast-seo-input-label if element is yoast-element
        if (ai4seo_is_yoast_element(selector)) {
            ai4seo_add_link_element_to_yoast_seo_input_label(selector);
        }

        // Call function to add button to input-element if element is other element
        else {
            ai4seo_add_generate_button_to_input(selector);
        }
    });
}

// =========================================================================================== \\

function ai4seo_is_attachment_post_type() {
    return jQuery("body").hasClass("post-type-attachment");
}

// =========================================================================================== \\

function ai4seo_is_attachment_mime_type_supported() {
    // Define boolean to determine whether supported mime-type has been found
    var has_supported_mime_type = false;

    // Loop through attachment-mime-type-selector-elements
    jQuery.each(ai4seo_attachment_mime_type_selectors, function(key, selector) {
        if (!ai4seo_exists(selector)) {
            return;
        }

        // Make sure that mime-type-selector is jQuery-element
        selector = ai4seo_jQuery(selector);

        // Check if this selector-element exists on the current page
        // Get the content of the selector
        var selector_content = selector.text();

        // Skip this entry if this selector doesn't have any content
        if (!selector_content) {
            return;
        }

        // Loop through ai4seo_supported_mime_types and check if mime-type exists in selector-content
        jQuery.each(ai4seo_supported_mime_types, function(mimeTypeKey, mimeTypeValue) {
            if (selector_content.indexOf(mimeTypeValue) > -1) {
                has_supported_mime_type = true;
            }
        });
    });

    return has_supported_mime_type;
}

// =========================================================================================== \\

/**
 * Init all our tooltips on this page
 */
function ai4seo_init_tooltips() {
    if (typeof jQuery !== 'function') {
        return;
    }

    if (ai4seo_exists('.ai4seo-tooltip-holder')) {
        let ai4seo_tooltip_holder = ai4seo_jQuery('.ai4seo-tooltip-holder');

        // add tooltips functionality
        ai4seo_tooltip_holder.hover(
            function (event) {
                let this_ai4seo_tooltip = jQuery(this).find('.ai4seo-tooltip');
                ai4seo_show_tooltip(this_ai4seo_tooltip, event);
            },
            function () {
                jQuery(this).find('.ai4seo-tooltip').fadeOut(200);
            }
        );

        ai4seo_tooltip_holder.click(function (event) {
            event.stopPropagation(); // Prevent the event from propagating to the document
            let this_ai4seo_tooltip = jQuery(this).find('.ai4seo-tooltip');
            jQuery('.ai4seo-tooltip').hide(); // Hide other tooltips


            if (this_ai4seo_tooltip.is(':visible')) {
                ai4seo_hide_tooltip(this_ai4seo_tooltip);
            } else {
                setTimeout(function () {
                    ai4seo_show_tooltip(this_ai4seo_tooltip, event);
                }, 1);
            }
        });
    }

    if (ai4seo_exists('.ai4seo-tooltip')) {
        let ai4seo_tooltip = ai4seo_jQuery('.ai4seo-tooltip');

        ai4seo_tooltip.click(function (event) {
            // close tooltip upon click
            event.stopPropagation(); // Prevent the event from propagating to the document
            setTimeout(function () {
                ai4seo_tooltip.hide(); // Hide all tooltips
            }, 2);
        });

        // Click event on the document to close all tooltips
        jQuery(document).click(function (event) {
            // close tooltip upon click
            event.stopPropagation(); // Prevent the event from propagating to the document
            setTimeout(function () {
                ai4seo_tooltip.hide(); // Hide all tooltips
            }, 2);
        });
    }
}

// =========================================================================================== \\

/**
 * Init all our "ai4seo-countdown" elements
 */
function ai4seo_init_countdown_elements() {
    jQuery(".ai4seo-countdown").each(function() {
        ai4seo_init_countdown(jQuery(this));
    });
}

// =========================================================================================== \\

/**
 * Apply a continuous countdown to the given element
 */
function ai4seo_init_countdown(element) {
    if (typeof jQuery !== 'function') {
        return;
    }

    let time_text = element.text(); // Get time as string hh:mm:ss
    let total_seconds = ai4seo_parse_time(time_text);

    if (isNaN(total_seconds) || total_seconds <= 0) {
        return;
    }

    let interval = setInterval(function () {
        total_seconds--;

        if (total_seconds <= 0) {
            clearInterval(interval);
            element.text('00:00:00');

            // Check if page has been open for at least 10 seconds
            let time_since_load = Date.now() - window.ai4seo_page_load_time;
            if (time_since_load >= 10000) { // 10000 milliseconds = 10 seconds
                let trigger_function_name = element.data('trigger');
                if (typeof window[trigger_function_name] === 'function') {
                    window[trigger_function_name]();
                }
            }
        } else {
            let time_str = ai4seo_format_time(total_seconds);
            element.text(time_str);
        }
    }, 1000);
}

// =========================================================================================== \\

/**
 * Parse a time string in hh:mm:ss format into total seconds
 */
function ai4seo_parse_time(time_text) {
    let parts = time_text.split(':');
    if (parts.length !== 3) {
        return NaN;
    }
    let hours = parseInt(parts[0], 10);
    let minutes = parseInt(parts[1], 10);
    let seconds = parseInt(parts[2], 10);

    if (isNaN(hours) || isNaN(minutes) || isNaN(seconds)) {
        return NaN;
    }

    return hours * 3600 + minutes * 60 + seconds;
}

// =========================================================================================== \\

/**
 * Format total seconds into a time string hh:mm:ss
 */
function ai4seo_format_time(total_seconds) {
    let hours = Math.floor(total_seconds / 3600);
    let minutes = Math.floor((total_seconds % 3600) / 60);
    let seconds = total_seconds % 60;

    return (
        String(hours).padStart(2, '0') +
        ':' +
        String(minutes).padStart(2, '0') +
        ':' +
        String(seconds).padStart(2, '0')
    );
}

// =========================================================================================== \\

function ai4seo_reload_page() {
    window.location.reload();
}

// =========================================================================================== \\

/**
 * Init all our select all / unselect all checkboxes
 */
function ai4seo_init_select_all_checkboxes() {
    // pre-check any select all checkbox, depending on the state of the checkboxes it controls (only if all child  checkboxes are checked, then the select all checkbox is checked)
    jQuery('.ai4seo-select-all-checkbox').each(function() {
        const this_select_all_checkbox_element = jQuery(this);

        const target_checkbox_name = this_select_all_checkbox_element.data('target');

        // if no target-checkbox-name is set, then skip this element
        if (!target_checkbox_name) {
            console.log('AI4SEO: No target-checkbox-name found for select-all-checkbox');
            return;
        }

        const all_target_checkbox_elements = jQuery("input[type='checkbox'][name='" + target_checkbox_name + "[]']:not(:disabled)");

        // if no target-checkbox-elements are found, then skip this element
        if (!ai4seo_exists(all_target_checkbox_elements)) {
            console.log('AI4SEO: No target-checkbox-elements found for select-all-checkbox with target-checkbox-name: ' + target_checkbox_name);
            return;
        }

        // refresh the current state of the select all / unselect all checkbox
        ai4seo_refresh_select_all_checkbox_state(this_select_all_checkbox_element, all_target_checkbox_elements);

        // add change event to all target-checkbox-elements
        this_select_all_checkbox_element.on('change', function() {
            // Get the checked status of the "Select All / Unselect All" checkbox
            const is_checked = jQuery(this).prop('checked');

            // Get all checkboxes with the specified name and apply the checked status
            all_target_checkbox_elements.prop('checked', is_checked).change();
        });

        // add change event to all target-checkbox-elements to refresh the state of the select all / unselect all checkbox
        all_target_checkbox_elements.on('change', function() {
            ai4seo_refresh_select_all_checkbox_state(this_select_all_checkbox_element, all_target_checkbox_elements);
        });
    });
}

// =========================================================================================== \\

/**
 * Refresh the current state of the select all / unselect all checkbox
 */
function ai4seo_refresh_select_all_checkbox_state(select_all_checkbox_element, all_target_checkbox_elements) {
    // set the initial state of the select all checkbox
    const num_checked_target_checkbox_elements = all_target_checkbox_elements.filter(':checked').length;
    const num_all_target_checkbox_elements = all_target_checkbox_elements.length;

    // if there are more checked checkboxes, than unchecked checkboxes, then the "select all checkbox" is checked as well
    select_all_checkbox_element.prop('checked', num_all_target_checkbox_elements - (num_checked_target_checkbox_elements * 2) < 0);
}

// =========================================================================================== \\

function ai4seo_init_checkbox_containers() {
    // class -> ai4seo-checkbox-container
    // add toggle effect for any checkboxes inside the container
    jQuery('.ai4seo-checkbox-container').each(function() {
        const container = jQuery(this);
        const checkboxes = container.find('input[type="checkbox"]');

        // on click on the container, toggle it's checkboxes, but prevent the event from bubbling up to the parent container AND prevent a click on the checkbox to double toggle it
        container.on('click', function(event) {
            event.stopPropagation();
            checkboxes.prop('checked', function(index, checked) {
                return !checked;
            });
        });

        // on click on the checkboxes, prevent the event from bubbling up to the parent container
        checkboxes.on('click', function(event) {
            event.stopPropagation();
        });
    });
}


// ___________________________________________________________________________________________ \\
// === ELEMENTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Function to show tooltip based on its position relative to the screen
function ai4seo_show_tooltip(ai4seo_tooltip, event) {
    var screen_width = jQuery(window).width();
    var screen_height = jQuery(window).height();
    var mouse_x = event.pageX;
    var mouse_y = event.pageY;
    var tooltip_width = ai4seo_tooltip.outerWidth();
    var tooltip_height = ai4seo_tooltip.outerHeight();
    var tooltip_half_width = tooltip_width / 2;
    var tooltip_top = mouse_y + 10; // 10px offset from mouse pointer
    var tooltip_bottom = screen_height - mouse_y + 10; // 10px offset from mouse pointer
    var vertical_buffer_zone = 30;
    var horizontal_buffer_zone = 30;
    var scroll_height = jQuery(window).scrollTop();
    var relative_mouse_y = mouse_y - scroll_height;
    var tooltip_buffer_zoned_half_width = tooltip_half_width + horizontal_buffer_zone;

    // Calculate left position ensuring tooltip doesn't go out of bounds
    var left_position = 0;

    // tooltip is overlapping with left screen border
    if (mouse_x - tooltip_half_width < 0) {
        left_position = tooltip_half_width - (mouse_x - horizontal_buffer_zone);

    // tooltip is overlapping with right screen border
    } else if (mouse_x + tooltip_half_width > screen_width) {
        left_position = -tooltip_half_width + (screen_width - mouse_x - horizontal_buffer_zone);
    }

    // check if ai4seo_tooltip is inside a modal (ai4seo-ajax-modal) -> apply workarounds
    var ai4seo_modal = ai4seo_tooltip.closest(".ai4seo-modal");

    if (ai4seo_exists(ai4seo_modal)) {
        // modal left position
        var modal_left_position = ai4seo_modal.offset().left;
        var modal_right_position = modal_left_position + ai4seo_modal.outerWidth();
        var modal_padding_left = parseInt(ai4seo_modal.css('padding-left').replace('px', ''));
        var modal_padding_right = parseInt(ai4seo_modal.css('padding-right').replace('px', ''));
        var mouse_distance_to_left_modal_border = mouse_x - modal_left_position;
        var mouse_distance_to_right_modal_border = modal_right_position - mouse_x;

        // if mouse position is too close to modal left border, move tooltip on the right
        if (mouse_distance_to_left_modal_border < tooltip_buffer_zoned_half_width) {
            left_position += (tooltip_buffer_zoned_half_width - mouse_distance_to_left_modal_border);
        }

        // if mouse position is too close to modal right border, move tooltip on the left
        if (mouse_distance_to_right_modal_border < tooltip_buffer_zoned_half_width) {
            left_position -= (tooltip_buffer_zoned_half_width - mouse_distance_to_right_modal_border);
        }
    }

    // tooltip is overlapping with top screen border
    if (relative_mouse_y <= vertical_buffer_zone + tooltip_height) {
        // Enough space below, show tooltip below
        ai4seo_tooltip.css({
            top: '100%',
            bottom: 'auto',
            left: left_position + 'px',
            marginTop: '10px',
            marginBottom: '0',
            transform: 'translateX(-50%)'
        });
        ai4seo_tooltip.find('::after').css({
            top: '100%',
            bottom: 'auto',
            transform: 'translateX(-50%)'
        });
    } else {
        // tooltip is overlapping with bottom screen border or all other cases
        ai4seo_tooltip.css({
            top: 'auto',
            bottom: '100%',
            left: left_position + 'px',
            marginBottom: '10px',
            marginTop: '0',
            transform: 'translateX(-50%)'
        });
        ai4seo_tooltip.find('::after').css({
            top: 'auto',
            bottom: '100%',
            transform: 'translateX(-50%)'
        });
    }


    ai4seo_tooltip.fadeIn(100);
}

function ai4seo_hide_tooltip(ai4seo_tooltip) {
    ai4seo_tooltip.fadeOut(100);
}


// ___________________________________________________________________________________________ \\
// === HELPER FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_input_val(element) {
    // Make sure that element can be found
    if (!ai4seo_exists(element)) {
        return false;
    }

    element = ai4seo_jQuery(element);

    // check if element is a single checkbox and class ai4seo-single-checkbox
    if (element.is("input[type='checkbox']") && element.length === 1 && element.hasClass("ai4seo-single-checkbox")) {
        return element.is(":checked");
    }

    // check if element is a group of checkboxes
    else if (element.is("input[type='checkbox']")) {
        return element.filter(':checked').map(function() {
            return jQuery(this).val();
        }).get();
    }

    // check if element is a group of radio buttons
    else if (element.is("input[type='radio']")) {
        return element.filter(':checked').val();
    }

    // Check if element is input-field (any other type than checkbox or radio)
    else if (element.is("input")) {
        return element.val();
    }

    // Check if element is textarea
    else if (element.is("textarea")) {
        return element.val();
    }

    // Check if element is select
    else if (element.is("select")) {
        return element.find('option').filter(':selected').val();
    }

    // check if element is a div or a span
    else if (element.is("div") || element.is("span")) {
        return element.text();
    }
}

// =========================================================================================== \\

function ai4seo_array_unique(array){
    return array.filter(function(el, index, arr) {
        return index === arr.indexOf(el);
    });
}

// =========================================================================================== \\

function ai4seo_jQuery(selector, context) {
    if (!selector) {
        return null;
    }

    if (!context) {
        context = window.parent.document;
    }

    let jquery_object = jQuery(selector, context);

    // console.log if no jquery_object could be found
    if (jquery_object.length === 0) {
        console.log("No jquery object found for selector: " + selector);
    }

    return jquery_object;
}

// =========================================================================================== \\

function ai4seo_exists(selector, context) {
    if (!context) {
        context = window.parent.document;
    }

    return jQuery(selector, context).length > 0;
}

// =========================================================================================== \\

function ai4seo_get_post_id() {
    // first look for the post id in the ajax modal
    let post_id = ai4seo_context.find("#ai4seo-editor-modal-post-id").val();

    if (post_id && !isNaN(post_id)) {
        return parseInt(post_id);
    }

    // Check if "media-modal"-element exists
    if (ai4seo_exists(".media-modal")) {
        // Read current url-parameters
        var current_url_parameters = new URLSearchParams(window.location.search);

        // Read item-parameter from current-url-parameters
        post_id = current_url_parameters.get("item");

        // Check if item-id could be found and is valid
        if (post_id && !isNaN(post_id)) {
            return parseInt(post_id);
        }

        // If the post_id could not be read from the url of the page then try to access wp.media.frame
        else {
            // Access the wp.media frame
            var mediaFrame = wp.media.frame;

            // Check if the attachment-id exists within model.id
            if (mediaFrame.model && mediaFrame.model.id) {
                post_id = mediaFrame.model.id;

                if (post_id && !isNaN(post_id)) {
                    return parseInt(post_id);
                }
            }
        }
    }

    // then look for the post-id in the localized object -> check last as it can sometimes have invalid information
    post_id = ai4seo_get_localization_parameter("ai4seo_current_post_id");

    // Make sure that post_id could be found and is a number
    if (post_id && !isNaN(post_id) && parseInt(post_id) > 0) {
        return parseInt(post_id);
    }

    return false;
}

// =========================================================================================== \\

function ai4seo_get_plugin_version_number() {
    return ai4seo_get_localization_parameter("ai4seo_plugin_version_number");
}


// =========================================================================================== \\

function ai4seo_get_admin_scripts_version_number() {
    return ai4seo_get_localization_parameter("ai4seo_admin_scripts_version_number");
}

// =========================================================================================== \\

function ai4seo_get_asset_url(sub_path) {
    return ai4seo_get_localization_parameter("ai4seo_assets_directory_url") + "/" + sub_path;
}

// =========================================================================================== \\

function ai4seo_get_localization_parameter(parameter_name) {
    // Check if ai4seo_localization exists
    if (typeof ai4seo_localization === "undefined") {
        console.log("AI for SEO: No localization object found!");
        return false;
    }

    // Check if parameter_name exists in ai4seo_localization
    if (typeof ai4seo_localization[parameter_name] === "undefined") {
        console.log("AI for SEO: No localization parameter found for: " + parameter_name);
        return false;
    }

    return ai4seo_localization[parameter_name];
}

// =========================================================================================== \\

function ai4seo_get_full_domain() {
    // try check ai4seo_site_url
    ai4seo_site_url = ai4seo_get_localization_parameter("ai4seo_site_url");

    // Check if ai4seo_localization.ai4seo_site_url exists
    if (ai4seo_site_url) {
        return ai4seo_site_url;
    }

    // fallback to window.location
    let protocol = window.location.protocol;
    let host = window.location.host;
    return protocol + "//" + host;
}

// =========================================================================================== \\

function ai4seo_get_ai4seo_plugin_directory_url() {
    return ai4seo_get_localization_parameter("ai4seo_plugin_directory_url");
}

// =========================================================================================== \\

function ai4seo_is_json_string( string ) {
    try {
        JSON.parse(string);
    } catch (e) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_is_object( object ) {
    return object === Object(object);
}

// =========================================================================================== \\

function ai4seo_is_chrome_browser() {
    return navigator.userAgent.indexOf("Chrome") !== -1;
}

// =========================================================================================== \\

function ai4seo_reload_page_with_parameter(parameter_name, parameter_value) {
    // Get current URL parameters
    var search_params = new URLSearchParams(window.location.search);

    // Set or update the parameter
    search_params.set(parameter_name, parameter_value);

    // Create the new URL with updated parameters
    // Reload the page with the new URL
    window.location.href = window.location.pathname + '?' + search_params.toString() + window.location.hash;
}

// =========================================================================================== \\

function ai4seo_is_yoast_element(element_selector) {
    // Check if element is found
    if (!ai4seo_exists(element_selector)) {
        return false;
    }

    // Define variable for element
    var element = ai4seo_jQuery(element_selector);

    // Check if element is a yoast-element
    if (!ai4seo_exists(element.closest(".yst-replacevar__editor"))) {
        return false;
    }

    return true;
}


// ___________________________________________________________________________________________ \\
// === AI GENERATION ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Function to make an ajax call to generate-metadata.php to get the post details
function ai4seo_generate_with_ai(ajax_action, post_id = false, only_this_selector = false, try_read_page_content_via_js = false ) {
    // Read post-id from hidden container if not defined
    if (!post_id) {
        post_id = ai4seo_get_post_id();
    }

    if (!post_id || isNaN(post_id)) {
        ai4seo_open_generic_error_notification_modal(132120824, wp.i18n.__("Could not read post ID. Please check your settings or contact the plugin developer.", "ai-for-seo"));
        return;
    }

    // collect data
    let ajax_data = {
        ai4seo_post_id: post_id,
    };

    // check if we should try to read the page content via js
    if (try_read_page_content_via_js) {
        // Define variable for the content based on ai4seo_get_post_content()
        // add content as ai4seo_content to data
        ajax_data.ai4seo_content = ai4seo_get_post_content();
    }

    // try to determine all current input values
    let current_generation_input_values = ai4seo_fetch_generation_input_values(only_this_selector);

    if (current_generation_input_values) {
        ajax_data.ai4seo_generation_input_values = current_generation_input_values;
    }

    // Replace button-label with loading-html
    ai4seo_add_loading_html_to_element(".ai4seo-generate-button");
    ai4seo_add_loading_html_to_element(".ai4seo-generate-all-button");

    // call desired ajax action
    ai4seo_perform_ajax_call(ajax_action, ajax_data)
        .then(response => {
            if (typeof response.new_credits_balance === 'number') {
                ai4seo_remaining_credits = response.new_credits_balance;
            }

            // go through the selector mapping and fill the values
            ai4seo_fill_post_outputs_from_mapping(response.generated_data || {}, only_this_selector);
        })
        .catch(error => { /* auto error handler enabled */ })
        .finally(() => {
            // Remove loading-html from button-label
            ai4seo_remove_loading_html_from_element(".ai4seo-generate-button");
            ai4seo_remove_loading_html_from_element(".ai4seo-generate-all-button");
        });
}

// =========================================================================================== \\

// Function to go through the content containers and grab with .text() and put everything into a big string
function ai4seo_get_post_content() {
    let post_content = "";

    for (let i = 0; i < ai4seo_content_containers.length; i++) {
        let this_content_container = ai4seo_content_containers[i];

        let this_content_containers_child_elements = ai4seo_context.find(this_content_container);

        // Make sure that child-elements could be found
        if (!this_content_containers_child_elements) {
            continue;
        }

        // Loop through child-elements and add their text to the content
        this_content_containers_child_elements.each(function() {
            let additional_post_content = "";

            // add text of the element to the content
            // if it's an input or textarea, use val() instead of text()
            if (ai4seo_jQuery(this).is('input') || ai4seo_jQuery(this).is('textarea')) {
                additional_post_content = ai4seo_jQuery(this).val();
            } else {
                additional_post_content = ai4seo_jQuery(this).text();
            }

            additional_post_content = ai4seo_add_dot_to_string(additional_post_content);

            // add additional post content to the post content, adding a space in between, if post content is not empty
            if (post_content) {
                post_content += " ";
            }

            post_content += additional_post_content;
        });
    }

    // for debugging: look what we got
    //console.log(post_content);

    return post_content;
}

// =========================================================================================== \\

/**
 * Function to add a dot at the end of the string if not already there
 * @param {string} string
 * @returns {string}
 */
function ai4seo_add_dot_to_string(string) {
    // trim string
    string = string.trim();

    // Return if the string is not longer than 1 character
    if (string.length <= 1) {
        return string;
    }

    // Return if the last character is already a dot
    if (string[string.length - 1] === ".") {
        return string;
    }

    // Add a dot if none of the above conditions were met
    string += ".";

    return string;
}

// =========================================================================================== \\

// Function to check response
function ai4seo_check_response( response, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true ) {
    if (!response) {
        ai4seo_open_generic_error_notification_modal(1104232360);
        return false;
    }

    // if response is a json-string, parse it so we can work with it
    if (ai4seo_is_json_string(response)) {
        response = JSON.parse( response );
    }

    // check if we have a success key in the response
    if (typeof response.success === 'undefined') {
        ai4seo_open_generic_error_notification_modal(1104232361);
        return false;
    }

    // if success is true -> return true
    if (response.success) {
        return true;
    }

    // otherwise we have an error
    console.log(response);

    // prepare response parameter
    if (typeof response.data !== 'undefined') {
        response = response.data;
    }

    if (typeof response !== 'object') {
        response = {};
    }

    if (typeof response.code === 'undefined') {
        response.code = 888;
    }

    // sanitize error code
    response.code = parseInt(response.code.toString().replace(/[^0-9]/g, ''));
    response.headline = response.headline || "";

    if (typeof response.add_contact_us_link !== 'undefined') {
        add_contact_us_link = response.add_contact_us_link
    }

    // check error message
    if (typeof response.error === 'undefined') {
        response.error = wp.i18n.__("An unknown error occurred.", "ai-for-seo");
    }

    let modal_settings = {};

    if (response.headline) {
        modal_settings.headline = response.headline;
    }

    // how to handle the error
    if (additional_error_list[response.code]) {
        // try to replace %s with the error message
        additional_error_list[response.code] = additional_error_list[response.code].replace('%s', response.error);
        ai4seo_open_generic_error_notification_modal(response.code, additional_error_list[response.code], "", modal_settings);
    }

    // Check if response.code (cast to int) is in ai4seo_robhub_api_response_error_codes
    else if (ai4seo_robhub_api_response_error_codes.includes(response.code)) {
        // Handle error-code 32127323 or 18197323 (common RobHub API error-codes)
        ai4seo_handle_common_robhub_api_response_errors(response.error, response.code, modal_settings);
    }

    // Check if error-code exists as key in ai4seo_error_codes_and_messages
    else if (ai4seo_error_codes_and_messages[response.code]) {
        // try to replace %s with the error message
        ai4seo_error_codes_and_messages[response.code] = ai4seo_error_codes_and_messages[response.code].replace('%s', response.error);
        ai4seo_open_generic_error_notification_modal(response.code, ai4seo_error_codes_and_messages[response.code], "", modal_settings);
    }

    else if (show_generic_error) {
        let error_message = (response.error ? response.error : "");

        if (add_contact_us_link) {
            // add extra lines
            if (error_message) {
                error_message += "<br><br>";
            }

            error_message += wp.i18n.sprintf(wp.i18n.__("Please check your settings or <a href='%s' target='_blank'>contact us</a>.", "ai-for-seo"), ai4seo_official_contact_url);
        }

        ai4seo_open_generic_error_notification_modal(response.code, error_message, "", modal_settings);
    }

    return false;
}

// =========================================================================================== \\

function ai4seo_handle_common_robhub_api_response_errors(error_message, error_code, modal_settings = {}) {
    // Check if ai4seo_robhub_api_response_error_codes_and_messages-array contains key that contains the error-message
    for (var key in ai4seo_robhub_api_response_error_codes_and_messages) {
        if (error_message.includes(key)) {
            // Display error-message
            ai4seo_open_generic_error_notification_modal(key, ai4seo_robhub_api_response_error_codes_and_messages[key]);
            return;
        }
    }

    // Display generic error-message if no error-message was found
    ai4seo_open_generic_error_notification_modal(error_code, error_message, "", modal_settings);
}

// =========================================================================================== \\

// Function to go through the selector mapping and fill the values
function ai4seo_fill_post_outputs_from_mapping(ai4seo_new_values = {}, only_this_selector = false) {
    // Define variable for all selectors
    var applicable_seo_inputs = ai4seo_seo_inputs;

    if (only_this_selector) {
        if (!ai4seo_seo_inputs[only_this_selector]) {
            console.log("AI for SEO: Unknown selector: " + only_this_selector);
            return;
        }

        applicable_seo_inputs = {};
        applicable_seo_inputs[only_this_selector] = ai4seo_seo_inputs[only_this_selector];
    }

    // Go through the selector mapping and fill the values
    for (var this_applicable_seo_input_index in applicable_seo_inputs) {
        var this_applicable_seo_input = applicable_seo_inputs[this_applicable_seo_input_index];

        let this_data_identifier = "";

        if (typeof this_applicable_seo_input.metadata_identifier !== "undefined") {
            this_data_identifier = this_applicable_seo_input.metadata_identifier;
        } else if (typeof this_applicable_seo_input.attachment_attributes_identifier !== "undefined") {
            this_data_identifier = this_applicable_seo_input.attachment_attributes_identifier;
        } else {
            console.log("AI for SEO: No metadata_identifier or attachment_attributes_identifier found for selector: " + this_applicable_seo_input_index);
            continue;
        }

        // check if we already have a value for this_value_index
        if (typeof ai4seo_new_values[this_data_identifier] === "undefined") {
            continue;
        }

        // Define variable for the value of the selector
        var this_new_value = ai4seo_new_values[this_data_identifier];

        // Set selectors by this_applicable_selector.additional_selectors if given, otherwise set to {}
        var this_seo_input_selectors = this_applicable_seo_input.additional_selectors ? this_applicable_seo_input.additional_selectors : [];

        // Add the this_applicable_selector_index to the selectors
        this_seo_input_selectors.push(this_applicable_seo_input_index);

        // Go through the selectors and fill the value
        for (var i = 0; i < this_seo_input_selectors.length; i++) {
            var this_seo_input_selector = this_seo_input_selectors[i];

            if (!ai4seo_exists(this_seo_input_selector)) {
                continue;
            }

            ai4seo_fill_text( this_seo_input_selector, this_new_value, this_applicable_seo_input );
        }
    }
}

// =========================================================================================== \\

// Function to fill the text with the element selected by the selector with the value
// the element can be a text field or a text area or a div
function ai4seo_fill_text( selector, value, options = {}) {
    if (!ai4seo_exists(selector)) {
        return;
    }

    var element = ai4seo_jQuery(selector);

    if (element.is('input')) {
        element.val(value).keypress().change();
    } else if (element.is('textarea')) {
        element.val(value).keypress().change();
    } else {
        var text_length = ai4seo_jQuery(selector).text().length;

        if (options.key_by_key && text_length > 0 && ai4seo_is_chrome_browser()) {
            ai4seo_add_text_to_editor_key_by_key(selector, value);
        } else {
            ai4seo_set_yoast_input_content(selector, value);

            //if (options.key_by_key && text_length > 0 && !ai4seo_is_chrome_browser()) {
            if (!ai4seo_is_chrome_browser()) {
                // disable input for the selector's element
                ai4seo_jQuery(selector).parent().parent().parent().attr("contenteditable", false);
                ai4seo_jQuery(selector).parent().parent().parent().attr("readonly", true);
                ai4seo_jQuery(selector).parent().parent().parent().css("pointer-events", "none");
                ai4seo_jQuery(selector).parent().parent().parent().parent().parent().parent().css("background-color", "rgba(155,155,155,.5)");
                ai4seo_jQuery(selector).parent().parent().parent().parent().parent().parent().attr("onclick", "alert(\"" + wp.i18n.__("Please save your changes before editing generated SEO content.", "ai-for-seo") + "\");");
            }
        }
    }

    // Call function to set progress bar to success
    ai4seo_set_progress_bar_success(selector);
}

// =========================================================================================== \\

function ai4seo_set_progress_bar_success(selector) {
    if (!ai4seo_exists(selector)) {
        return;
    }

    // Define variable for selector-element
    var selector_element = ai4seo_jQuery(selector);

    // Define variable for the parent-element with class "yst-replacevar"
    var parent_element = selector_element.closest(".yst-replacevar");

    // Make sure that parent-element exists
    if (!ai4seo_exists(parent_element)) {
        return;
    }

    // Define variable for the progress-bar-element
    var progress_bar = parent_element.next("progress");

    // Make sure that progress-bar-element exists
    if (!ai4seo_exists(progress_bar)) {
        return;
    }

    // Read max-value of progress-bar-element
    var max_value = progress_bar.attr("max");

    // Add success-class to progress-bar-element
    progress_bar.addClass("ai4seo-progress-success");

    // Set porgress-bar-value to max-value
    progress_bar.attr("value", max_value);
}

// =========================================================================================== \\

function ai4seo_set_yoast_input_content( selector, value ) {
    const jquery_container = ai4seo_jQuery(selector);
    const data_offset_key = jquery_container.data("offset-key");
    const container = jquery_container.get(0);
    const inner_span = React.createElement('span', { 'data-text': 'true' }, value);
    const span_container = React.createElement('span', { 'data-offset-key': data_offset_key }, inner_span);
    ReactDOM.unmountComponentAtNode(container);
    ReactDOM.render(span_container, container);

    if (ai4seo_is_chrome_browser()) {
        // frozen input workaround: add empty character to editor
        var editor = ai4seo_jQuery(selector).parent().parent().parent().get(0);

        editor.focus();
        document.execCommand('insertText', false, "​");
    }
}

// =========================================================================================== \\

function ai4seo_add_text_to_editor_key_by_key( selector, value ) {
    var editor = ai4seo_jQuery(selector).parent().parent().parent().get(0);

    // delete all content in the editor
    ai4seo_delete_editor_content(editor);

    editor.focus();
    ai4seo_set_cursor_at_the_end(editor);

    // go through each character and add it to the editor
    for (var i = 0; i < value.length; i++) {
        document.execCommand('insertText', false, value[i]);
    }
}

// =========================================================================================== \\

function ai4seo_delete_editor_content(editor) {
    editor.focus();

    // place cursor at the beginning of the editor
    ai4seo_set_cursor_at_the_end(editor);

    // Remove the content one by one
    var text_length = ai4seo_jQuery(editor).text().length;

    for (var i = 0; i < text_length; i++) {
        document.execCommand('delete', false, null);
    }
}

// =========================================================================================== \\

function ai4seo_set_cursor_at_the_end(element) {
    const range = document.createRange();
    const selection = window.getSelection();
    range.selectNodeContents(element);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
}


// ___________________________________________________________________________________________ \\
// === DASHBOARD ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function add_refresh_credits_balance_parameter_and_reload_page() {
    ai4seo_reload_page_with_parameter("ai4seo_refresh_credits_balance", "true");
}

// =========================================================================================== \\

function ai4seo_start_bulk_generation(button_element) {
    ai4seo_save_anything(button_element, ai4seo_validate_bulk_generation_inputs, ai4seo_reload_page, ai4seo_reload_page);
}

// =========================================================================================== \\

function ai4seo_validate_bulk_generation_inputs() {
    return true;
}

// =========================================================================================== \\

function ai4seo_stop_bulk_generation(submit_element) {
    ai4seo_add_loading_html_to_element(submit_element);
    ai4seo_lock_and_disable_lockable_input_fields();

    ai4seo_perform_ajax_call("ai4seo_stop_bulk_generation")
        .finally(response => { ai4seo_reload_page(); });
}


// ___________________________________________________________________________________________ \\
// === GENERATE THROUGH AI - BUTTONS ========================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_init_generate_all_button() {
    // Check if current page is attachment-page
    // workaround: we need to check if the attachment mime type is supported
    if (ai4seo_is_attachment_post_type()) {
        // Stop script if the current attachment doesn't contain supported mime type
        if (!ai4seo_is_attachment_mime_type_supported()) {
            return;
        }
    }

    // Loop through selectors and add button to each selector
    for (let this_processing_context in ai4seo_generate_all_button_selectors) {
        ai4seo_generate_all_button_selectors[this_processing_context].forEach(function(this_generate_all_button_selector) {
            if (!ai4seo_exists(this_generate_all_button_selector)) {
                return;
            }

            if (ai4seo_exists(ai4seo_jQuery(this_generate_all_button_selector).find(".ai4seo-generate-all-button"))) {
                return;
            }

            ai4seo_add_generate_all_button(this_processing_context, this_generate_all_button_selector);
        });
    }
}

// =========================================================================================== \\

function ai4seo_add_generate_all_button(processing_context, element_selector) {
    if (!ai4seo_exists(element_selector)) {
        return;
    }

    // Define variable for element
    var hook_element = ai4seo_jQuery(element_selector);

    // check if this hook element already has a generate all button (ai4seo-generate-all-button-wrapper class)
    if (ai4seo_exists(hook_element.find(".ai4seo-generate-all-button-wrapper"))) {
        return;
    }

    // Define button variables
    let onclick = "";
    let button_title = "";
    let button_label = "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-64x64.png' class='ai4seo-logo'  alt='AI'/>" + wp.i18n.__("Generate all SEO", "ai-for-seo");

    if (processing_context === "metadata") {
        onclick += "ai4seo_generate_with_ai(\"ai4seo_generate_metadata\", false, false, true);";
        button_title += wp.i18n.__("Generate metadata for all meta tags via AI.", "ai-for-seo");
    } else if (processing_context === "attachment-attributes") {
        onclick += "ai4seo_generate_with_ai(\"ai4seo_generate_attachment_attributes\",false, false);";
        button_title += wp.i18n.__("Generate content for all available media attributes via AI.", "ai-for-seo");
    }

    // put everything together
    let button_html = "<button type='button' onclick='" + onclick + "' title='" + button_title + "' class='ai4seo-generate-all-button'>" + button_label + "</button>";
    let wrapped_button_html = "<div class='ai4seo-generate-all-button-wrapper'>" + button_html + "</div>";

    // Add button-element after element
    hook_element.prepend(wrapped_button_html);
}

// =========================================================================================== \\

/**
 * Fetch the values of all input fields that are mapped to a metadata or attachment attributes identifier
 * @returns {{}} - object with metadata_identifier or media_attributes_identifier as key and input value as value
 */
function ai4seo_fetch_generation_input_values(only_this_selector = false) {
    let fetched_values = {};
    let seo_input_selectors = [];

    if (only_this_selector) {
        seo_input_selectors.push(only_this_selector);
    } else {
        // get objects field names
        seo_input_selectors = Object.keys(ai4seo_seo_inputs);
    }

    for (let i = 0; i < seo_input_selectors.length; i++) {
        let this_seo_input_selector = seo_input_selectors[i];

        // check if the selector exists and the element is visible
        if (!ai4seo_exists(this_seo_input_selector)) {
            continue;
        }

        // check if the element is visible
        if (!ai4seo_jQuery(this_seo_input_selector).is(":visible")) {
            continue;
        }

        if (typeof ai4seo_seo_inputs[this_seo_input_selector] === "undefined") {
            console.log("AI for SEO: Unknown selector: " + this_seo_input_selector);
            continue;
        }

        let this_seo_input = ai4seo_seo_inputs[this_seo_input_selector];

        // metadata or attachment_attributes identifier
        let this_data_identifier = "";

        if (typeof this_seo_input.metadata_identifier !== "undefined") {
            this_data_identifier = this_seo_input.metadata_identifier;
        } else if (typeof this_seo_input.attachment_attributes_identifier !== "undefined") {
            this_data_identifier = this_seo_input.attachment_attributes_identifier;
        } else {
            console.log("AI for SEO: No metadata_identifier or attachment_attributes_identifier defined for selector: " + this_seo_input_selector);
            continue;
        }

        // check if we already have a value for this_data_identifier
        if (typeof fetched_values[this_data_identifier] !== "undefined") {
            continue;
        }

        fetched_values[this_data_identifier] = ai4seo_get_input_val(this_seo_input_selector);
    }

    return fetched_values;
}

// =========================================================================================== \\

function ai4seo_add_link_element_to_yoast_seo_input_label(editor_element_selector) {
    if (!ai4seo_exists(editor_element_selector)) {
        return;
    }

    // Overwrite editor-element with jquery-element
    var editor_element = ai4seo_jQuery(editor_element_selector);

    // Define variable for the parent-element
    var parent_element = editor_element.closest(".yst-replacevar__editor");

    // Stop script if parent-element is not found
    if (!ai4seo_exists(parent_element)) {
        return;
    }

    // Check if element after parent_element contains "ai4seo-generate-button"-class
    if (parent_element.next().hasClass("ai4seo-generate-button")) {
        // Remove button-element
        parent_element.next().remove();
    }

    // Add link element after parent-element
    parent_element.after(ai4seo_get_generate_button_output(editor_element_selector));
}

// =========================================================================================== \\

function ai4seo_get_generate_button_output(element_selector, button_label = "auto", button_title = "") {
    // Make sure that onclick-variable is defined
    let button_onclick = "";
    let try_read_page_content_via_js = "true"; // assuming I'm inside a WordPress editor

    if (ai4seo_exists("#ai4seo-read-page-content-via-js")) {
        try_read_page_content_via_js = ai4seo_jQuery("#ai4seo-read-page-content-via-js").val();
    }

    if (button_label === "auto") {
        // Generate with AI
        button_label = wp.i18n.__("Generate with AI", "ai-for-seo");
    }

    // Check if processing-entry exists in mapping-array
    if (ai4seo_seo_inputs[element_selector]['processing-context']) {
        // Prepare onclick for attachment-attributes-processing
        if (ai4seo_seo_inputs[element_selector]['processing-context'] === "attachment-attributes") {
            button_onclick = "ai4seo_generate_with_ai(\"ai4seo_generate_attachment_attributes\", false, \"" + element_selector + "\");";
        }

        // Prepare onclick for  -processing
        else if (ai4seo_seo_inputs[element_selector]['processing-context'] === "metadata") {
            button_onclick = "ai4seo_generate_with_ai(\"ai4seo_generate_metadata\", false, \"" + element_selector + "\", " + try_read_page_content_via_js + ");";
        }

        // Prepare fallback onclick
        else {
            console.log("AI for SEO: Unknown processing-context: " + ai4seo_seo_inputs[element_selector]['processing-context']);
        }
    } else {
        console.log("AI for SEO: No processing-context defined for element-selector: " + element_selector);
    }

    // Prepare additional css-class for button-output
    let additional_css_class = "";

    if (ai4seo_seo_inputs[element_selector]['css-class']) {
        additional_css_class = " " + ai4seo_seo_inputs[element_selector]['css-class'];
    }

    return "<button type='button' onclick='" + button_onclick + "' title='" + button_title + "' class='ai4seo-generate-button ai4seo-generate-button-arrow" + additional_css_class + "'><img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> " + button_label + "</button>";
}

// =========================================================================================== \\

function ai4seo_add_generate_button_to_input(input_element_selector) {
    if (!ai4seo_exists(input_element_selector)) {
        return;
    }

    // Define variable for input-element
    var input_element = ai4seo_jQuery(input_element_selector);

    // Check if element after input_element contains "ai4seo-generate-button"-class
    if (input_element.next().hasClass("ai4seo-generate-button")) {
        // Remove button-element
        input_element.next().remove();
    }

    // Add button-element after input-element
    input_element.after(ai4seo_get_generate_button_output(input_element_selector));
}

// =========================================================================================== \\

function ai4seo_get_context() {
    // Define variable for the elementor-preview-iframe-element
    if (ai4seo_exists("#elementor-preview-iframe")) {
        return ai4seo_jQuery("#elementor-preview-iframe").contents();
    }

    // Define variable for the be-builder-iframe
    if (ai4seo_exists("#mfn-vb-ifr")) {
        return ai4seo_jQuery("#mfn-vb-ifr").contents();
    }

    // Return jQuery-document if no elementor-iframe exists
    return jQuery(document);
}

// =========================================================================================== \\

/**
 * Check if the user is inside the Elementor editor.
 * @return bool True if inside the Elementor editor, false otherwise.
 */
function ai4seo_is_inside_elementor_editor() {
    return typeof elementor !== 'undefined' &&
        typeof elementorFrontend !== 'undefined' &&
        document.body.classList.contains('elementor-editor-active');
}

// =========================================================================================== \\

function ai4seo_add_loading_html_to_element(element) {
    if (!ai4seo_exists(element)) {
        return;
    }

    // Make sure that element is jquery-element
    element = ai4seo_jQuery(element);

    element.each(function() {
        // Define variable for this element
        var this_element = ai4seo_jQuery(this);

        // Define variable for the original html-content
        var original_html_content = this_element.html();

        // Replace html-content with loading-elements
        this_element.html("<div class='ai4seo-loading-animation'><div></div><div></div><div></div><div></div></div>");

        // Add data-attribute to element with original html-content
        this_element.attr("data-ai-for-seo-original-html-content", original_html_content);

        // Add class to deactivate element to element
        this_element.addClass("ai4seo-element-inactive");
    });
}

// =========================================================================================== \\

function ai4seo_remove_loading_html_from_element(element) {
    if (!ai4seo_exists(element)) {
        return;
    }

    // Make sure that element is jquery-element
    element = ai4seo_jQuery(element);

    element.each(function() {
        // Define variable for this element
        var this_element = ai4seo_jQuery(this);

        // Define variable for the original html-content
        var original_html_content = this_element.attr("data-ai-for-seo-original-html-content");

        // Remove data-attribute from element
        this_element.removeAttr("data-ai-for-seo-original-html-content");

        // Replace html-content with original html-content
        this_element.html(original_html_content);

        // Remove class to deactivate element from element
        this_element.removeClass("ai4seo-element-inactive");
    });
}

// ___________________________________________________________________________________________ \\
// === SVG =================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_svg_tag(icon_name, icon_css_class, alt_text) {
    // Make sure that the icon-name is allowed
    if (!ai4seo_svg_icons[icon_name]) {
        return "";
    }

    let svg_tag = ai4seo_svg_icons[icon_name];

    // add css class to svg tag
    if (icon_css_class) {
        icon_css_class = "ai4seo-icon " + icon_css_class;
    } else {
        icon_css_class = "ai4seo-icon";
    }

    svg_tag = svg_tag.replace("<svg", "<svg class='" + icon_css_class + "'");

    // add alt text to svg tag
    if (alt_text) {
        svg_tag = svg_tag.replace("<svg", "<svg aria-label='" + alt_text + "'");
        svg_tag = svg_tag.replace("</svg>", "<title>" + alt_text + "</title></svg>");
    }

    return svg_tag;
}

// ___________________________________________________________________________________________ \\
// === MODALS ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_open_generic_error_notification_modal(error_code = 999, error_message = "", footer = "", modal_settings = {}) {
    if (!error_message) {
        error_message = wp.i18n.sprintf(wp.i18n.__("Please check your settings or <a href='%s' target='_blank'>contact us</a>.", "ai-for-seo"), ai4seo_official_contact_url);
    }

    let default_headline = wp.i18n.__("An error occurred!", "ai-for-seo");
    let content = error_message + " (" + wp.i18n.__("error code", "ai-for-seo") + ": #" + error_code + ")";

    // default notification modal settings
    let default_settings = {
        close_on_outside_click: false,
        add_close_button: false,
        headline: (modal_settings.headline ? modal_settings.headline : default_headline),
        content: content,
    };

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_notification_modal(modal_settings.headline, modal_settings.content, footer, modal_settings);
}

// =========================================================================================== \\

function ai4seo_open_generic_success_notification_modal(content, footer = "", modal_settings = {}) {
    let default_headline = wp.i18n.__("Success!", "ai-for-seo");

    // Display success message
    let check_icon = ai4seo_get_svg_tag('circle-check', 'ai4seo-big-icon ai4seo-fill-green', wp.i18n.__("Success!", "ai-for-seo"));
    let default_content = check_icon + "<br>" + wp.i18n.__("The data have been saved successfully.", "ai-for-seo");

    // default notification modal settings
    let default_settings = {
        close_on_outside_click: true,
        add_close_button: true,
        headline: (modal_settings.headline ? modal_settings.headline : default_headline),
        content: (content ? content : default_content),
    };

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_notification_modal(modal_settings.headline, modal_settings.content, footer, modal_settings);
}

// =========================================================================================== \\

function ai4seo_open_notification_modal(headline = "", content = "", footer = "", modal_settings = {}) {
    let modal_id = "ai4seo-notification-modal";

    let default_footer = "<button type='button' onclick='ai4seo_close_modal(\"" + modal_id + "\")' class='ai4seo-button ai4seo-success-button'>" + wp.i18n.__("Close", "ai-for-seo") + "</button>";

    // default notification modal settings
    let default_settings = {
        close_on_outside_click: false,
        add_close_button: false,
        modal_css_class: "ai4seo-notification-modal",
        modal_wrapper_css_class: "ai4seo-notification-modal-wrapper",
        modal_size: "small",
        headline: headline,
        content: content,
        footer: (footer ? footer : default_footer),
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_modal(modal_id, modal_settings);
}

// =========================================================================================== \\

function ai4seo_close_notification_modal() {
    ai4seo_close_modal("ai4seo-notification-modal");
}

// =========================================================================================== \\

function ai4seo_open_ajax_modal(ajax_action, ajax_data = {}, modal_settings = {}) {
    let modal_id = "ai4seo-ajax-modal";

    // ajax -> add loading icon to content
    let default_content = "<div class='ai4seo-ajax-modal-loading-icon'>" + ai4seo_get_svg_tag("rotate", "ai4seo-spinning-icon", wp.i18n.__("Loading... Please wait.", "ai-for-seo")) + "</div>";

    // default ajax modal settings
    let default_settings = {
        close_on_outside_click: true,
        add_close_button: true,
        modal_css_class: "ai4seo-ajax-modal",
        modal_wrapper_css_class: "ai4seo-ajax-modal-wrapper",
        content: default_content,
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_modal(modal_id, modal_settings);

    if (!ai4seo_get_modal(modal_id)) {
        console.log("AI for SEO: Could not open modal with id: " + modal_id);
        return;
    }

    let modal_element = ai4seo_get_modal(modal_id);

    // ajax -> perform ajax call
    ai4seo_perform_ajax_call(ajax_action, ajax_data, false)
        .then(response => {
            // check if modal is still open (maybe closed by the user by now)
            if (!ai4seo_get_modal(modal_id)) {
                return;
            }

            if (!response || ai4seo_is_json_string(response) || typeof response !== "string") {
                ai4seo_close_modal(modal_id);
                ai4seo_check_response(response);
                return;
            }

            // insert response into content element
            modal_element.find(".ai4seo-modal-content").html(response);

            // init modal to reflect new changes and functions inside the modal
            ai4seo_init_modal_functions(modal_id, modal_settings.close_on_outside_click);
        })
        .catch(error => { ai4seo_close_modal(modal_id); })
        .finally(() => { /* do nothing */});
}

// =========================================================================================== \\

function ai4seo_close_ajax_modal() {
    ai4seo_close_modal("ai4seo-ajax-modal");
}

// =========================================================================================== \\

function ai4seo_open_modal_from_schema(modal_schema_identifier, modal_settings = {}) {
    if (!ai4seo_exists(".ai4seo-modal-schemas-container > #ai4seo-modal-schema-" + modal_schema_identifier)) {
        console.log("AI for SEO: Could not find modal schema with id: " + modal_schema_identifier);
        return;
    }

    let modal_schema = ai4seo_jQuery(".ai4seo-modal-schemas-container > #ai4seo-modal-schema-" + modal_schema_identifier);

    // find headline, content and footer
    let default_settings = {};

    // find and remove headline from schema
    let modal_schema_headline = modal_schema.find(".ai4seo-modal-schema-headline");

    if (ai4seo_exists(modal_schema_headline)) {
        default_settings["headline"] = modal_schema_headline.html();
        modal_schema_headline.html("");
    }

    // find content and remove it from schema
    let modal_schema_content = modal_schema.find(".ai4seo-modal-schema-content");

    if (ai4seo_exists(modal_schema_content)) {
        default_settings["content"] = modal_schema_content.html();
        modal_schema_content.html("");
    }

    // find footer and remove it from schema
    let modal_schema_footer = modal_schema.find(".ai4seo-modal-schema-footer");

    if (ai4seo_exists(modal_schema_footer)) {
        default_settings["footer"] = modal_schema_footer.html();
        modal_schema_footer.html("");
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    // open modal
    let modal_id = "ai4seo-" + modal_schema_identifier;
    let modal_element = ai4seo_open_modal(modal_id, modal_settings);

    // add schema identifier to modal
    modal_element.data("ai4seo-modal-schema-identifier", modal_schema_identifier);
}

// =========================================================================================== \\

function ai4seo_close_modal_from_schema(modal_schema_identifier) {
    ai4seo_close_modal("ai4seo-" + modal_schema_identifier);
}

// =========================================================================================== \\

function ai4seo_open_modal(modal_id, modal_settings = {}) {
    // === PREPARE PARAMETERS ================================================================ \\

    if (!modal_id) {
        modal_id = "ai4seo-modal";
    }

    // default settings
    let default_settings = {
        close_on_outside_click: true,
        add_close_button: true,
        modal_css_class: "",
        modal_wrapper_css_class: "",
        headline_icon: "default",
        headline: "",
        content: "",
        footer: "",
        modal_size: "medium", // small, medium, large, auto
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    // define default headline icon
    if (modal_settings.headline_icon === "default") {
        modal_settings.headline_icon = "<img src='" + ai4seo_get_asset_url("images/logos/ai-for-seo-logo-64x64.png") + "' class='ai4seo-logo' alt='AI for SEO' />";
    }

    // check if message is a jQuery element -> use it's html instead
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

    // remove existing modals with same id first
    ai4seo_close_modal(modal_id);

    // create empty modal
    let modal_element = ai4seo_create_empty_modal(modal_id, modal_settings.modal_css_class, modal_settings.modal_wrapper_css_class, modal_settings.modal_size);

    if (!modal_element) {
        return;
    }

    // === ADD CONTENTS ================================================================================== \\

    // add close button
    if (modal_settings.add_close_button) {
        modal_element.append("<div class='ai4seo-modal-close-icon' onclick='ai4seo_close_modal(\"" + modal_id + "\")'>" + ai4seo_get_svg_tag('square-xmark', '', wp.i18n.__("Close modal", "ai-for-seo")) + "</div>");
    }

    // set headline
    if (modal_settings.headline) {
        // also check if there is not already a headline icon
        if (modal_settings.headline_icon && !modal_settings.headline.includes("ai4seo-modal-headline-icon")) {
            modal_settings.headline = "<div class='ai4seo-modal-headline-icon'>" + modal_settings.headline_icon + "</div>" + modal_settings.headline;
        }

        modal_element.append("<div class='ai4seo-modal-headline'>" + modal_settings.headline + "</div>");
    }

    // set content
    if (modal_settings.content) {
        modal_element.append("<div class='ai4seo-modal-content'>" + modal_settings.content + "</div>");
    }

    // set footer
    if (modal_settings.footer) {
        modal_element.append("<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>" + modal_settings.footer + "</div>");
    }

    // add functions to modal
    ai4seo_init_modal_functions(modal_id, modal_settings.close_on_outside_click);

    return modal_element;
}

// =========================================================================================== \\

function ai4seo_init_modal_functions(modal_id, close_on_outside_click) {
    if (!ai4seo_get_modal(modal_id)) {
        return;
    }

    let modal_element = ai4seo_get_modal(modal_id);

    // close on outside click?
    if (close_on_outside_click) {
        // keep track of the mousedown origin, to only close the modal, if the mouseup event is on the wrapper too
        // to prevent closing the layer while dragging the mouse from inside the modal to outside while selecting
        // text for example
        ai4seo_get_modal_wrapper(modal_id).mousedown(function(event) {
            ai4seo_mousedown_origin = event.target;
        });

        ai4seo_get_modal_wrapper(modal_id).mouseup(function(event) {
            if (event.target === ai4seo_mousedown_origin) {
                ai4seo_close_modal(modal_id);
            }
        });
    }

    // init html elements
    ai4seo_init_html_elements();

    // Vertically center modal on screen, if modal_elements height is smaller than 80% of screen height
    if (modal_element.outerHeight() < jQuery(window).height() * 0.80) {
        modal_element.css({
            "top": "50%",
            "margin-top": -modal_element.outerHeight() / 2 - 50, // 50px buffer
        });
    } else {
        modal_element.css({
            "top": "3rem",
            "margin-top": 0,
        });
    }
}

// =========================================================================================== \\

function ai4seo_create_empty_modal(modal_id, modal_css_class, modal_wrapper_css_class, modal_size) {
    // get highest z-index of all modal wrappers
    let previous_highest_z_index = ai4seo_get_highest_modal_wrapper_z_index();

    // add modal css class
    if (modal_css_class) {
        modal_css_class = "ai4seo-modal " + modal_css_class;
    } else {
        modal_css_class = "ai4seo-modal";
    }

    // add modal wrapper css class
    if (modal_wrapper_css_class) {
        modal_wrapper_css_class = "ai4seo-modal-wrapper " + modal_wrapper_css_class;
    } else {
        modal_wrapper_css_class = "ai4seo-modal-wrapper";
    }

    if (modal_size === "small") {
        modal_css_class += " ai4seo-modal-small-size";
    } else if (modal_size === "medium") {
        modal_css_class += " ai4seo-modal-medium-size";
    } else if (modal_size === "large") {
        modal_css_class += " ai4seo-modal-large-size";
    } else {
        modal_css_class += " ai4seo-modal-auto-size";
    }

    // add empty modal wrapper and modal to the footer of the body
    // AND disable scroll on body-element
    jQuery("body")
        .append("<div class='" + modal_wrapper_css_class + "'><div class='" + modal_css_class + "' id='" + modal_id + "'></div></div>")
        .addClass("ai4seo-has-open-modal");

    // check for the modal tags
    let modal_element = ai4seo_get_modal(modal_id);

    if (!modal_element) {
        console.log("AI for SEO: Could not create modal with id: " + modal_id);
        return;
    }

    let modal_wrapper_element = ai4seo_get_modal_wrapper(modal_id);

    if (!modal_wrapper_element) {
        console.log("AI for SEO: Could not create modal wrapper for modal with id: " + modal_id);
    }

    // Workaround: add stop propagation to modal to prevent closing when clicking inside the modal
    modal_element.mouseup(function(event) {
        event.stopPropagation();
    });

    modal_element.click(function(event) {
        event.stopPropagation();
    });

    // Workaround: if there was a highest z index, add 1 to it
    if (previous_highest_z_index) {
        previous_highest_z_index++;

        modal_wrapper_element.css("z-index", previous_highest_z_index);
    }

    return modal_element;
}

// =========================================================================================== \\

function ai4seo_get_highest_modal_wrapper_z_index() {
    let highest_z_index = 0;

    jQuery(".ai4seo-modal-wrapper").each(function() {
        let z_index = jQuery(this).css("z-index");

        if (z_index > highest_z_index) {
            highest_z_index = z_index;
        }
    });

    return highest_z_index;
}

// =========================================================================================== \\

function ai4seo_get_modal_wrapper(modal_id) {
    if (ai4seo_exists("#" + modal_id)) {
        return ai4seo_jQuery("#" + modal_id).parent(".ai4seo-modal-wrapper");
    } else {
        // return empty jQuery object
        return null;
    }
}

// =========================================================================================== \\

function ai4seo_get_modal(modal_id) {
    if (ai4seo_exists("#" + modal_id)) {
        return ai4seo_jQuery("#" + modal_id);
    } else {
        // return empty jQuery object
        return null;
    }
}

// =========================================================================================== \\

function ai4seo_close_modal(modal_id) {
    let modal_element = ai4seo_get_modal(modal_id);

    if (!ai4seo_exists(modal_element)) {
        return;
    }

    // check for modal-schema-identifier data -> put data back to schema
    if (modal_element.data("ai4seo-modal-schema-identifier")) {
        let modal_schema_identifier = modal_element.data("ai4seo-modal-schema-identifier");

        // put back headline, content and footer to the schema
        if (ai4seo_exists(".ai4seo-modal-schemas-container > #ai4seo-modal-schema-" + modal_schema_identifier)) {
            let modal_schema = ai4seo_jQuery(".ai4seo-modal-schemas-container > #ai4seo-modal-schema-" + modal_schema_identifier);

            // find headline
            if (ai4seo_exists(modal_element.find(".ai4seo-modal-headline")) && ai4seo_exists(modal_schema.find(".ai4seo-modal-schema-headline"))) {
                modal_schema.find(".ai4seo-modal-schema-headline").html(modal_element.find(".ai4seo-modal-headline").html());
            }

            // find content
            if (ai4seo_exists(modal_element.find(".ai4seo-modal-content")) && ai4seo_exists(modal_schema.find(".ai4seo-modal-schema-content"))) {
                modal_schema.find(".ai4seo-modal-schema-content").html(modal_element.find(".ai4seo-modal-content").html());
            }

            // find footer
            if (ai4seo_exists(modal_element.find(".ai4seo-modal-footer")) && ai4seo_exists(modal_schema.find(".ai4seo-modal-schema-footer"))) {
                modal_schema.find(".ai4seo-modal-schema-footer").html(modal_element.find(".ai4seo-modal-footer").html());
            }
        }
    }

    if (ai4seo_get_modal_wrapper(modal_id)) {
        ai4seo_get_modal_wrapper(modal_id).remove();
    }

    // no more ai4seo-modal -> enable scroll on body-element
    if (!ai4seo_exists(".ai4seo-modal")) {
        jQuery("body").removeClass("ai4seo-has-open-modal");
    }
}

// =========================================================================================== \\

function ai4seo_close_modal_by_child(child_element) {
    if (!ai4seo_exists(child_element)) {
        return;
    }

    child_element = ai4seo_jQuery(child_element);

    // is modal_id a reference element like a button? find the modal_id
    if (ai4seo_exists(child_element.closest(".ai4seo-modal"))) {
        let modal_id = child_element.closest(".ai4seo-modal").attr("id");

        ai4seo_close_modal(modal_id);
    }
}

// =========================================================================================== \\

function ai4seo_close_all_modals() {
    if (!ai4seo_exists(".ai4seo-modal")) {
        return;
    }

    ai4seo_jQuery(".ai4seo-modal").each(function() {
        ai4seo_close_modal(ai4seo_jQuery(this).attr("id"));
    });
}

// =========================================================================================== \\

function ai4seo_open_metadata_editor_modal(post_id = false, read_page_content_via_js = false) {
    // Read post-id from hidden container if not defined
    if (!post_id) {
        post_id = ai4seo_get_post_id();
    }

    if (!post_id) {
        ai4seo_open_generic_error_notification_modal(26173424);
        return;
    }

    // CURRENT POST'S CONTENT
    let post_content = "";

    // Define variable for the content based on ai4seo_get_post_content()
    if (read_page_content_via_js) {
        post_content = ai4seo_get_post_content();
    }

    let parameters = {
        post_id: post_id,
        read_page_content_via_js: read_page_content_via_js,
        content: post_content,
    }

    ai4seo_open_ajax_modal("ai4seo_show_metadata_editor", parameters);
}

// =========================================================================================== \\

function ai4seo_open_attachment_attributes_editor_modal(post_id = false) {
    // Read post-id from hidden container if not defined
    if (!post_id) {
        ai4seo_open_notification_modal(241920824);
        return;
    }

    // PARAMETERS
    let parameters = {
        post_id: post_id,
    }

    ai4seo_open_ajax_modal("ai4seo_show_attachment_attributes_editor", parameters);
}

// =========================================================================================== \\

function ai4seo_safe_reload() {
    if (ai4seo_is_inside_elementor_editor()) {
        return;
    }

    ai4seo_reload_page();
}

// =========================================================================================== \\

function ai4seo_get_all_input_values_in_container(form_container) {
    // Define variable for the form-holder-element based on the form-holder-selector
    var container_element = ai4seo_jQuery(form_container);

    // Stop script if form-holder-element could not be found
    if (!ai4seo_exists(container_element)) {
        ai4seo_open_notification_modal(501622824);
        return false;
    }

    // Find form-elements within the form-holder-element
    let input_elements = container_element.find("input, select, textarea");
    let input_values = {};
    let this_input_selector;
    let this_input_element;
    let this_input_value;
    let this_input_element_name = false;
    let already_processed_element_names = [];

    // Collect identifier (to prevent analysing the same checkbox or radio-name)
    for(var i = 0; i < input_elements.length; i++) {
        this_input_element = input_elements[i];
        this_input_element_name = (typeof this_input_element.name !== "undefined") ? this_input_element.name : false;

        if (!this_input_element_name) {
            continue;
        }

        if (already_processed_element_names.includes(this_input_element_name)) {
            continue;
        }

        already_processed_element_names.push(this_input_element_name);

        this_input_selector = "[name='" + this_input_element_name + "']";

        if (!ai4seo_exists(this_input_selector)) {
            continue;
        }

        this_input_value = ai4seo_get_input_val(ai4seo_jQuery(this_input_selector));

        if (typeof this_input_value === "undefined") {
            continue;
        }

        input_values[this_input_element_name] = this_input_value;
    }

    // Make sure that input_vals is not empty
    if (Object.keys(input_values).length === 0) {
        ai4seo_open_notification_modal(1207230231);
        return false;
    }

    return input_values;
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header() {
    // Make sure the header_bar_buttons_container exists
    if (!ai4seo_exists(".edit-post-header .interface-pinned-items")) {
        return;
    }

    // remove old button
    if (ai4seo_exists(".ai4seo-header-builder-button")) {
        ai4seo_jQuery(".ai4seo-header-builder-button").remove();
    }

    // Define variable for the interface-pinned-items element within the edit-post-header-toolbar
    var header_bar_buttons_container = ai4seo_jQuery(".edit-post-header .interface-pinned-items");

    // Read post-id from hidden container if not defined
    var post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // Generate output
    var output = "";

    // Add button to output
    output += "<button type=\"button\" class=\"components-button has-icon ai4seo-header-builder-button\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-24x24-icon'>";
    output += "</button>";

    // Add button to header_bar_buttons_container
    header_bar_buttons_container.append(output);
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() {
    // Make sure the seo_title_element_container exists
    if (!ai4seo_exists(".mfn-meta-seo-title")) {
        return
    }

    // Define variable for the seo-title-element within the be-builder-navigation
    var seo_title_element_container = ai4seo_jQuery(".mfn-meta-seo-title");

    // Read post-id from hidden container if not defined
    var post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // Generate output
    var output = "";

    // Add button to output
    output += "<button type=\"button\" class=\"ai4seo-generate-button\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> ";
        output += wp.i18n.__("Show all SEO settings", "ai-for-seo");
    output += "</button>";

    // Add button to seo_title_element_container
    seo_title_element_container.before(output);
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation() {
    // Make sure that at least one of the elementor-elements can be found
    if (!ai4seo_exists("#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items") && !ai4seo_exists("#elementor-panel-page-settings-controls")) {
        return
    }

    // Define variable for the first elementor-panel-menu-group-element within the elementor-navigation
    var first_elementor_panel_menu_group_container = ai4seo_jQuery("#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items");

    // Define variable for the container of the elementor panel page settings controls
    var elementor_panel_page_settings_controls = ai4seo_jQuery("#elementor-panel-page-settings-controls");

    // Read post-id from hidden container if not defined
    var post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // Generate output
    var output = "";

    // Add button to output
    output += "<button type=\"button\" class=\"ai4seo-generate-button\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> ";
        output += wp.i18n.__("Show all SEO settings", "ai-for-seo");
    output += "</button>";

    // Add button to first_elementor_panel_menu_group_container
    first_elementor_panel_menu_group_container.append(output);

    // Add button to elementor panel page settings controls
    elementor_panel_page_settings_controls.prepend(output);
}

// =========================================================================================== \\

function ai4seo_validate_metadata_editor_inputs(input_values) {
    return true;
}

// =========================================================================================== \\

function ai4seo_validate_attachment_attributes_editor_inputs(input_values) {
    return true;
}


// ___________________________________________________________________________________________ \\
// === SAVE ANYTHING ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_save_anything(submit_element, validation_function, success_function, error_function) {
    // check if submit_element exists
    if (!ai4seo_exists(submit_element)) {
        console.log("AI for SEO: submit_element does not exist.");
        return;
    }

    // find a form container nearby
    let form_container = ai4seo_find_closest_form_container(submit_element);

    // if still not found, return error
    if (!ai4seo_exists(form_container)) {
        console.log("AI for SEO: form_container does not exist.");
        return;
    }

    // get all input values from form_container
    let ai4seo_ajax_data = ai4seo_get_all_input_values_in_container(form_container);

    if (validation_function) {
        if (!validation_function(ai4seo_ajax_data)) {
            return;
        }
    }

    // add loading html to submit_element
    ai4seo_add_loading_html_to_element(submit_element);

    // workaround for empty arrays: go through each ai4seo_ajax_data element and convert empty arrays to #ai4seo-empty-array#
    for (let key in ai4seo_ajax_data) {
        if (Array.isArray(ai4seo_ajax_data[key]) && ai4seo_ajax_data[key].length === 0) {
            ai4seo_ajax_data[key] = "#ai4seo-empty-array#";
        }
    }

    // Perform ajax action
    ai4seo_perform_ajax_call('ai4seo_save_anything', ai4seo_ajax_data)
        .then(response => {
            // Display success message
            ai4seo_open_generic_success_notification_modal();

            // scroll to top of page
            window.scrollTo(0, 0);

            // perform success function
            if (success_function) {
                success_function(response);
            }
        })
        .catch(error => {
            // Hint: error modal will be shown dynamically, due to the auto error handler

            // perform error function
            if (error_function) {
                error_function(error, response);
            }
        })
        .finally(() => {
            // Remove loading-html from submit-element
            if (ai4seo_exists(submit_element)) {
                ai4seo_remove_loading_html_from_element(submit_element);
            }
        });
}

// =========================================================================================== \\

function ai4seo_find_closest_form_container(reference_element) {
    // Check if reference_element exists
    if (!ai4seo_exists(reference_element)) {
        console.log("AI for SEO: reference_element does not exist.");
        return false;
    }

    //check if the reference element is actually a .ai4seo-form
    if (ai4seo_jQuery(reference_element).hasClass("ai4seo-form")) {
        return reference_element;
    }

    // Array of selectors to check
    let check_elements = [".ai4seo-form", ".ai4seo-modal", ".ai4seo-tab-content"];

    // Loop through selectors using for...of, which supports early exit
    for (let element of check_elements) {
        let form_container = ai4seo_jQuery(reference_element).closest(element);

        if (ai4seo_exists(form_container)) {
            return form_container;
        }
    }

    return false;
}


// ___________________________________________________________________________________________ \\
// === ACCOUNT PAGE ========================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_validate_account_inputs(input_values) {
    let api_password = input_values["ai4seo_api_password"] || "";
    let api_username = input_values["ai4seo_api_username"] || "";
    let installed_plugins_plugin_name = input_values["ai4seo_installed_plugins_plugin_name"] || "";
    let installed_plugins_plugin_description = input_values["ai4seo_installed_plugins_plugin_description"] || "";
    let meta_tags_block_starting_hint = input_values["ai4seo_meta_tags_block_starting_hint"] || "";
    let meta_tags_block_ending_hint = input_values["ai4seo_meta_tags_block_ending_hint"] || "";

    if (api_username.length > 128) {
        ai4seo_open_generic_error_notification_modal(4510271224, wp.i18n.__("Please enter a valid username.", "ai-for-seo"));
        return false;
    }

    if (api_password.length > 0 && api_password.length !== 48) {
        ai4seo_open_generic_error_notification_modal(361222324, wp.i18n.__("Please enter a valid license key.", "ai-for-seo"));
        return false;
    }

    // Check the length of the plugin-name
    if (installed_plugins_plugin_name.length < 3 || installed_plugins_plugin_name.length > 100) {
        ai4seo_open_generic_error_notification_modal(4510271235, wp.i18n.__("Please enter a valid plugin name (3-100 characters).", "ai-for-seo"));
        return false;
    }

    // Check the length of the plugin-description
    if (installed_plugins_plugin_description.length < 3 || installed_plugins_plugin_description.length > 140) {
        ai4seo_open_generic_error_notification_modal(4510271246, wp.i18n.__("Please enter a valid plugin description (3-140 characters).", "ai-for-seo"));
        return false;
    }

    // Check the length of the source-code-notes-content-start
    if (meta_tags_block_starting_hint.length < 3 || meta_tags_block_starting_hint.length > 250) {
        ai4seo_open_generic_error_notification_modal(4510271247, wp.i18n.__("Please enter a valid meta tag block starting hint (3-250 characters).", "ai-for-seo"));
        return false;
    }

    // Check the length of the source-code-notes-content-end
    if (meta_tags_block_ending_hint.length < 3 || meta_tags_block_ending_hint.length > 250) {
        ai4seo_open_generic_error_notification_modal(4510271248, wp.i18n.__("Please enter a valid meta tag block ending hint (3-250 characters).", "ai-for-seo"));
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_display_license_information() {
    // perform ajax action
    ai4seo_perform_ajax_call('ai4seo_display_license_information')
        .then(response => {
            // reload page
            ai4seo_reload_page();
        })
        .catch(error => { /* auto error handler enabled */ });
}

// =========================================================================================== \\

function ai4seo_init_license_form() {
    ai4seo_toggle_visibility_on_checkbox(jQuery("#ai4seo-enable-white-label"), jQuery(".ai4seo-white-label-only-container"));
    ai4seo_toggle_visibility_on_checkbox(jQuery("#ai4seo-display-source-code-notes"), jQuery(".ai4seo-source-code-adjustments-only-container"));
}

// =========================================================================================== \\

function ai4seo_toggle_visibility_on_checkbox(selector_checkbox, selector_target, visible_on_checked = true) {
    // Stop script if selector_checkbox or selector_target could not be found
    if (!ai4seo_exists(selector_checkbox) || !ai4seo_exists(selector_target)) {
        return;
    }

    // Make sure that selector_checkbox is jQuery element
    if (ai4seo_exists(selector_checkbox)) {
        selector_checkbox = ai4seo_jQuery(selector_checkbox);
    }

    // Make sure that selector_target is jQuery element
    if (ai4seo_exists(selector_target)) {
        selector_target = ai4seo_jQuery(selector_target);
    }

    // Check if the white-label-settings should be shown
    if (selector_checkbox.is(":checked")) {
        if (visible_on_checked) {
            selector_target.removeClass("ai4seo-display-none");
        } else {
            selector_target.addClass("ai4seo-display-none");
        }
    } else {
        if (visible_on_checked) {
            selector_target.addClass("ai4seo-display-none");
        } else {
            selector_target.removeClass("ai4seo-display-none");
        }
    }
}


// ___________________________________________________________________________________________ \\
// === NOTICES =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// add dismiss click action to notice elements
// class "ai4seo-notice > notice-dismiss"
jQuery(document).on("click", ".ai4seo-performance-notice > .notice-dismiss", function() {
    // call desired ajax action
    ai4seo_perform_ajax_call('ai4seo_dismiss_performance_notice').catch(error => { /* auto error handler enabled */ });
});

// class "ai4seo-one-time-notice > notice-dismiss"
jQuery(document).on("click", ".ai4seo-one-time-notice > .notice-dismiss", function() {
    let ai4seo_notice_identifier = ai4seo_jQuery(this).closest(".ai4seo-one-time-notice").data("notice-identifier");

    if (!ai4seo_notice_identifier) {
        console.log("AI for SEO: Notice identifier not found.");
        return;
    }

    // call desired ajax action
    ai4seo_perform_ajax_call('ai4seo_dismiss_one_time_notice', {ai4seo_notice_identifier: ai4seo_notice_identifier}).catch(error => { /* auto error handler enabled */ });
});

// move all .ai4seo-notice to .ai4seo-notices-area
jQuery(document).ready(function() {
    // check if .ai4seo-notices-area exists
    if (ai4seo_exists(".ai4seo-notices-area")) {
        // move all .ai4seo-notice to .ai4seo-notices-area
        jQuery(".ai4seo-notice").each(function() {
            jQuery(this).appendTo(".ai4seo-notices-area");
        });
    }
});


// ___________________________________________________________________________________________ \\
// === TERMS OF SERVICE ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Show confirmation notification modal to really reject tos
 */
function ai4seo_confirm_to_reject_tos() {
    let headline = wp.i18n.__("Please confirm", "ai-for-seo");
    let content = wp.i18n.__("Are you sure you want to reject the terms of service and uninstall AI for SEO?", "ai-for-seo");
    content += "<br><br>";
    content += wp.i18n.__("<strong>Attention:</strong><br>If you have already purchased a subscription, you can cancel it by clicking <a href='https://aiforseo.ai/cancel-plan' target='_blank'>HERE</a>.", "ai-for-seo");

    let reject_button = "<button type='button' class='ai4seo-button ai4seo-abort-button' id='ai4seo-reject-tos-button' onclick='ai4seo_reject_tos();'>" + wp.i18n.__("Yes, please!", "ai-for-seo") + "</button>";
    let back_button = "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__("No, I changed my mind", "ai-for-seo") + "</button>";

    ai4seo_open_notification_modal(headline, content, reject_button + back_button);
}

// =========================================================================================== \\

/**
 * Let the user reject tos, using ajax
 */
function ai4seo_reject_tos() {
    ai4seo_add_loading_html_to_element(".ai4seo-button");

    ai4seo_perform_ajax_call('ai4seo_reject_tos')
        .then(response => {
            window.location.href = ai4seo_admin_installed_plugins_page_url;
        })
        .catch(error => { /* auto error handler enabled */ });
}

// =========================================================================================== \\

/**
 * Toggle the terms of service accept button based on the agreement checkbox state
 */
function ai4seo_refresh_tos_accept_button_state() {
    let accepted_tos = ai4seo_jQuery(".ai4seo-accept-tos-checkbox").prop("checked");
    let accept_button = ai4seo_jQuery(".ai4seo-accept-tos-button");

    if (accepted_tos) {
        // remove ai4seo-disabled-button class, add ai4seo-success-button class
        accept_button.removeClass("ai4seo-disabled-button").addClass("ai4seo-success-button");
    } else {
        // add ai4seo-disabled-button class, remove ai4seo-success-button class
        accept_button.addClass("ai4seo-disabled-button").removeClass("ai4seo-success-button");
    }
}

// =========================================================================================== \\

function ai4seo_check_if_user_accepted_tos() {
    let accepted_tos = ai4seo_jQuery(".ai4seo-accept-tos-checkbox").prop("checked");

    if (!accepted_tos) {
        ai4seo_show_accept_terms_notification_modal();
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_show_accept_terms_notification_modal() {
    ai4seo_open_notification_modal(wp.i18n.__("Attention!", "ai-for-seo"), wp.i18n.__("Please accept the terms of service first.", "ai-for-seo"));

    // add ai4seo-shake-animation to the checkbox and remove it after 3 seconds
    ai4seo_jQuery(".ai4seo-accept-tos-checkbox-wrapper").addClass("ai4seo-shake-animation");

    setTimeout(function() {
        ai4seo_jQuery(".ai4seo-accept-tos-checkbox-wrapper").removeClass("ai4seo-shake-animation");
    }, 3000);
}

// =========================================================================================== \\

/**
 * Let the user accept tos, using ajax
 */
function ai4seo_accept_tos(reload_page = true) {
    if (!ai4seo_check_if_user_accepted_tos()) {
        return;
    }

    // check state of checkbox "ai4seo-accept-enhanced-reporting-checkbox"
    let accepted_enhanced_reporting = ai4seo_jQuery(".ai4seo-accept-enhanced-reporting-checkbox").prop("checked");

    ai4seo_add_loading_html_to_element(".ai4seo-button");

    ai4seo_perform_ajax_call('ai4seo_accept_tos', {accepted_enhanced_reporting: accepted_enhanced_reporting})
        .then(response => {
            // reload page
            if (reload_page) {
                ai4seo_reload_page();
            }
        })
        .catch(error => { /* auto error handler enabled */ });
}

// =========================================================================================== \\

function ai4seo_does_user_need_to_accept_tos_toc_and_pp() {
    return ai4seo_get_localization_parameter("ai4seo_does_user_need_to_accepted_tos_toc_and_pp");
}

// ___________________________________________________________________________________________ \\
// === SETTINGS (PAGE) ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_toggle_sync_only_these_metadata_container() {
    let sync_only_these_metadata_container = ai4seo_jQuery("#ai4seo-sync-only-these-metadata-container");

    if (!ai4seo_exists(".ai4seo_third_party_sync_checkbox")) {
        return;
    }

    // if any checkbox with class ai4seo_third_party_sync_checkbox is checked, display the container
    if (ai4seo_jQuery(".ai4seo_third_party_sync_checkbox:checked").length > 0) {
        sync_only_these_metadata_container.show();
    } else {
        sync_only_these_metadata_container.hide();
    }
}

// =========================================================================================== \\

function ai4seo_validate_settings_inputs(input_values) {
    // Check if prefix- and suffix-input-fields exist
    if (ai4seo_exists("input.ai4seo-prefix-suffix-setting-textfield")) {
        // Define boolean to determine whether an error has occurred
        var no_errors = true;

        // Loop through all prefix- and suffix-input-fields and make sure that the content doesn't exceed the max-length
        ai4seo_jQuery("input.ai4seo-prefix-suffix-setting-textfield").each(function (index) {
            // Define variable for the value of this input-field
            var this_input_value = ai4seo_jQuery(this).val();

            if (this_input_value.length > 0 && this_input_value.length > 48) {
                ai4seo_open_generic_error_notification_modal(4510271249, wp.i18n.__("Please don't exceed the maximum length-requirement for prefix- and suffix-input-fields (max. 48 characters).", "ai-for-seo"));
                return no_errors = false;
            }
        });

        return no_errors;
    }

    return true;
}


// ___________________________________________________________________________________________ \\
// === AJAX ================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_perform_ajax_call(action, data = {}, auto_check_response = true, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true) {
    // Check action
    if (!ai4seo_allowed_ajax_actions.includes(action)) {
        ai4seo_open_generic_error_notification_modal(4317101224);
        return Promise.reject({
            error: "Invalid action",
            code: 4317101224,
            message: wp.i18n.__("Action not allowed", "ai-for-seo"),
        });
    }

    // Ensure data is an object and merge additional fields
    data = {
        ...(data || {}),
        ai4seo_ajax_nonce: ai4seo_get_ajax_nonce(),
        action: action,
    };

    // Return a Promise for better async handling
    return new Promise((resolve, reject) => {
        jQuery.post(ai4seo_admin_ajax_url, data)
            .done(function(response) {
                if (auto_check_response) {
                    if (ai4seo_check_response(response, additional_error_list, show_generic_error, add_contact_us_link)) {
                        resolve(response.data || response);
                    } else {
                        reject(response.data || response);
                    }
                } else {
                    resolve(response.data || response);
                }
            })
            .fail(function(jq_xhr, text_status, error_thrown) {
                console.error("AJAX Error:", text_status, error_thrown);
                reject({ error: text_status, code: 4217101224, details: error_thrown });
            });
    });
}

// =========================================================================================== \\

function ai4seo_get_ajax_nonce() {
    return ai4seo_get_localization_parameter("ai4seo_ajax_nonce");
}

// =========================================================================================== \\

function ai4seo_hide_loading_icons(element) {
    // Default-definition of icon-element
    if (!ai4seo_exists(element)) {
        element = ai4seo_jQuery(".ai4seo-hidden-loading-icon-holder");
    }

    // Make sure that icon_element is jquery-element
    element = ai4seo_jQuery(element);

    // Hide loading icon/s
    element.hide();

    // Enable locked input-fields
    ai4seo_unlock_and_enable_lockable_input_fields();
}

// =========================================================================================== \\

function ai4seo_lock_and_disable_lockable_input_fields() {
    // Define variable for all input-fields
    var all_input_fields = ai4seo_jQuery(".ai4seo-lockable");

    // Add css-class to disable input-fields
    all_input_fields.addClass("ai4seo-temporary-locked");

    // Add disabled attribute to all input-fields
    all_input_fields.attr("disabled", "disabled");
}

// =========================================================================================== \\

function ai4seo_unlock_and_enable_lockable_input_fields() {
    // Define variable for all input-fields
    var all_input_fields = ai4seo_jQuery(".ai4seo-temporary-locked");

    // Remove css-class to disable input-fields
    all_input_fields.removeClass("ai4seo-temporary-locked");

    // Add disabled attribute to all input-fields
    all_input_fields.prop("disabled", false);
}

// =========================================================================================== \\

function ai4seo_set_input_type_attribute(input_element, type_attribute) {
    // Make sure that input_element is jquery-element
    input_element = ai4seo_jQuery(input_element);

    // Make sure that input_element exists
    if (!ai4seo_exists(input_element)) {
        return;
    }

    // Set new type-attribute to selected attribute
    input_element.attr("type", type_attribute);
}

// ___________________________________________________________________________________________ \\
// === HELP PAGE ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

jQuery(document).ready(function() {
    // Function to perform the search
    jQuery("#ai4seo-help-search").on("keyup", function(event) {
        var code_of_key_pressed = event.keyCode || event.which;
        var search_text = jQuery(this).val().toLowerCase();
        var faq_section_holder_element = jQuery(".ai4seo-faq-section-holder");
        var faq_entry_holder_element = jQuery(".ai4seo-accordion-holder");
        var no_results_notice_holder = jQuery("#ai4seo-help-faq-search-notice");
        var has_results = false;

        if (search_text.length >= 3) {
            // Display all elements of char is deleted in input
            if (code_of_key_pressed === 8 || code_of_key_pressed === 46) {
                faq_entry_holder_element.show();
                faq_section_holder_element.show();
                no_results_notice_holder.hide();
            }

            // Hide all faq-holders once the minimum of 3 characters have been added to the search field
            faq_entry_holder_element.hide();

            // Loop through each faq-holder to check for a match
            faq_entry_holder_element.each(function() {
                var headline = jQuery(this).find(".ai4seo-accordion-headline").text().toLowerCase();
                var content = jQuery(this).find(".ai4seo-accordion-content").text().toLowerCase();

                // Check if the search_text is found in either the headline or the content
                if (headline.includes(search_text) || content.includes(search_text)) {
                    // Show this faq-entry if a match was found
                    jQuery(this).show();
                    has_results = true;
                }
            });

            // Loop through each faq-section-holder to check if there are still faq-entries in this section
            faq_section_holder_element.each(function() {
                if (jQuery(this).find(".ai4seo-accordion-headline:visible").length !== 0) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            });

            // Toggle the no results message based on whether matches have been found
            if (has_results) {
                no_results_notice_holder.hide();
            } else {
                no_results_notice_holder.show();
            }
        } else {
            // Show all accordion holders and hide the no results message if less than 3 characters are entered
            faq_entry_holder_element.show();
            faq_section_holder_element.show();
            no_results_notice_holder.hide();
        }
    });

    // check for any anchor in the url and click the corresponding button
    var ai4seo_location_hash = window.location.hash;

    if (ai4seo_location_hash) {
        jQuery("a[href='" + ai4seo_location_hash + "']").children().click();
    }
});

// =========================================================================================== \\

function ai4seo_confirm_reset_plugin_data() {
    let ai4seo_reset_metadata = jQuery("#ai4seo-troubleshooting-reset-metadata").is(":checked");

    let ai4seo_notification_modal_message = "";

    if (ai4seo_reset_metadata) {
        ai4seo_notification_modal_message = jQuery("#ai4seo-reset-generated-data-tooltip-text").html() + "<br><br>";
    }

    ai4seo_notification_modal_message += wp.i18n.__("Are you sure you want to reset the selected plugin data?", "ai-for-seo");

    ai4seo_open_notification_modal(
        wp.i18n.__("Please confirm", "ai-for-seo"),
        ai4seo_notification_modal_message,
    "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__("Abort", "ai-for-seo") + "</button><button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_reset_plugin_data();'>" + wp.i18n.__("Reset Plugin Data", "ai-for-seo") + "</button>"
);
}

// =========================================================================================== \\

/**
 * Function to decode the HTML safely escaped by esc_js().
 * This replaces escaped characters (e.g., `&lt;`, `&gt;`) back to their HTML counterparts.
 */
function ai4seo_decode_escaped_html(escapedHtml) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = escapedHtml; // Decodes HTML entities
    const value = textarea.value; // Returns unescaped HTML
    // remove textarea from the DOM
    textarea.remove();
    return value;
}

// =========================================================================================== \\

function ai4seo_reset_plugin_data() {
    ai4seo_close_notification_modal();

    let ai4seo_reset_cache = jQuery("#ai4seo-troubleshooting-reset-cache").is(":checked");
    let ai4seo_reset_environmental_variables = jQuery("#ai4seo-troubleshooting-reset-env").is(":checked");
    let ai4seo_reset_settings = jQuery("#ai4seo-troubleshooting-reset-settings").is(":checked");
    let ai4seo_reset_metadata = jQuery("#ai4seo-troubleshooting-reset-metadata").is(":checked");

    // Check if at least one option is selected
    if (!ai4seo_reset_cache && !ai4seo_reset_environmental_variables && !ai4seo_reset_settings && !ai4seo_reset_metadata) {
        ai4seo_open_notification_modal(
            wp.i18n.__("Oops...", "ai-for-seo"),
            wp.i18n.__("Please select at least one option to reset.", "ai-for-seo"),
            "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__("OK", "ai-for-seo") + "</button>"
        );

        return;
    }

    ai4seo_lock_and_disable_lockable_input_fields();
    ai4seo_add_loading_html_to_element(jQuery("#ai4seo-troubleshooting-reset-button"));

    ai4seo_perform_ajax_call("ai4seo_reset_plugin_data", {ai4seo_reset_cache: ai4seo_reset_cache, ai4seo_reset_environmental_variables: ai4seo_reset_environmental_variables, ai4seo_reset_settings: ai4seo_reset_settings, ai4seo_reset_metadata: ai4seo_reset_metadata})
        .then(response => {
            ai4seo_open_generic_success_notification_modal(
                wp.i18n.__("The plugin data has been reset successfully.", "ai-for-seo"),
            "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__("OK", "ai-for-seo") + "</button>"
        );
        })
        .finally(response => {
            ai4seo_unlock_and_enable_lockable_input_fields();
            ai4seo_remove_loading_html_from_element(jQuery("#ai4seo-troubleshooting-reset-button"));
        })
        .catch(error => { /* auto error handler */ });
}

// ___________________________________________________________________________________________ \\
// === SELECT CREDITS PACK MODAL ============================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_select_credits_pack_modal() {
    ai4seo_open_modal_from_schema("select-credits-pack", {modal_size: "small"});
    jQuery(".ai4seo-credits-pack-selection-item-most-popular").click();
}

// =========================================================================================== \\

function ai4seo_handle_credits_pack_selection(item_element) {
    item_element = ai4seo_jQuery(item_element);
    let all_credits_pack_items = ai4seo_jQuery("div.ai4seo-credits-pack-selection-item");

    // remove .ai4seo-credits-pack-selection-item-selected class from all items
    all_credits_pack_items.removeClass("ai4seo-credits-pack-selection-item-selected");

    // add .ai4seo-credits-pack-selection-item-selected class to selected item
    item_element.addClass("ai4seo-credits-pack-selection-item-selected");

    // set radio button in > ai4seo-credits-pack-selection-item-radio-button checked
    item_element.find(".ai4seo-credits-pack-selection-item-radio-button > input").prop("checked", true);

    // refresh cost breakdown
    let cost_per_page = item_element.data("cost-per-page");
    let cost_per_media_file = item_element.data("cost-per-media-file");
    let currency = item_element.data("currency");
    ai4seo_jQuery(".ai4seo-credits-pack-cost-per-page").text(cost_per_page + " " + currency);
    ai4seo_jQuery(".ai4seo-credits-pack-cost-per-media-file").text(cost_per_media_file + " " + currency);
}

// =========================================================================================== \\

function ai4seo_handle_select_credits_pack(submit_element) {
    if (!ai4seo_exists("input[name='ai4seo-credits-pack-selection[]']") || !ai4seo_get_input_val("input[name='ai4seo-credits-pack-selection[]']")) {
        ai4seo_open_generic_error_notification_modal(461818325, wp.i18n.__("Please select a Credits Pack first.", "ai-for-seo"));
        return;
    }

    ai4seo_add_loading_html_to_element(submit_element);
    ai4seo_lock_and_disable_lockable_input_fields();

    let selected_stripe_price_id = ai4seo_get_input_val("input[name='ai4seo-credits-pack-selection[]']");

    ai4seo_perform_ajax_call("ai4seo_init_purchase", {stripe_price_id: selected_stripe_price_id})
        .then(response => {
            if (typeof response.purchase_url === 'undefined' || !response.purchase_url) {
                ai4seo_open_generic_error_notification_modal(471818325, wp.i18n.__("An error occurred while trying to initiate the purchase.", "ai-for-seo"));
                return false;
            }


            // redirect to purchase url
            window.location.href = response.purchase_url;
        })
        .catch(error => {
            ai4seo_remove_loading_html_from_element(submit_element);
            ai4seo_unlock_and_enable_lockable_input_fields();
        });
}


// ___________________________________________________________________________________________ \\
// === PAY-AS-YOU-GO MODAL =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_customize_payg_modal() {
    ai4seo_open_modal_from_schema("customize-pay-as-you-go", {modal_size: "small"});
    ai4seo_handle_payg_form_change();
}

// =========================================================================================== \\

function ai4seo_handle_payg_form_change() {
    let payg_stripe_price_id = ai4seo_jQuery("#ai4seo_payg_stripe_price_id").val();
    let payg_credits_amount = ai4seo_jQuery("#ai4seo_payg_stripe_price_id option:selected").data("credits-amount");
    let payg_price = ai4seo_jQuery("#ai4seo_payg_stripe_price_id option:selected").data("price");
    let payg_reference_price = ai4seo_jQuery("#ai4seo_payg_stripe_price_id option:selected").data("reference-price");
    let payg_credits_threshold = ai4seo_jQuery("#ai4seo_payg_credits_threshold").val();
    let payg_daily_budget = ai4seo_jQuery("#ai4seo_payg_daily_budget").val();
    let payg_monthly_budget = ai4seo_jQuery("#ai4seo_payg_monthly_budget").val();

    // replace , with .
    payg_price = payg_price.replace(",", ".");

    // cast payg_price to float
    payg_price = parseFloat(payg_price);

    // cast payg_credits_threshold to int
    payg_credits_threshold = parseInt(payg_credits_threshold);
    ai4seo_jQuery("#ai4seo_payg_credits_threshold").val(payg_credits_threshold);

    // cast payg_daily_budget to int
    payg_daily_budget = parseInt(payg_daily_budget);
    ai4seo_jQuery("#ai4seo_payg_daily_budget").val(payg_daily_budget);

    // if daily budget is lower than the price, set it to the ceil(price) * 3
    if (payg_daily_budget < payg_price) {
        payg_daily_budget = Math.ceil(payg_price) * 3;
        ai4seo_jQuery("#ai4seo_payg_daily_budget").val(payg_daily_budget);
    }

    // cast payg_monthly_budget to int
    payg_monthly_budget = parseInt(payg_monthly_budget);

    // if monthly budget is lower than the price, set it to the ceil(price) * 10
    if (payg_monthly_budget < payg_price) {
        payg_monthly_budget = Math.ceil(payg_price) * 10;
        ai4seo_jQuery("#ai4seo_payg_monthly_budget").val(payg_monthly_budget);
    }

    ai4seo_jQuery("#ai4seo-payg-summary-credits-amount").text(payg_credits_amount);
    ai4seo_jQuery("#ai4seo-payg-summary-price").text(payg_price);
    ai4seo_jQuery("#ai4seo-payg-summary-reference-price").text(payg_reference_price);
    ai4seo_jQuery("#ai4seo-payg-summary-threshold").text(payg_credits_threshold);
    ai4seo_jQuery("#ai4seo-payg-summary-daily-budget").text(payg_daily_budget);
    ai4seo_jQuery("#ai4seo-payg-summary-monthly-budget").text(payg_monthly_budget);
}

// =========================================================================================== \\

function ai4seo_handle_payg_submit(submit_element) {
    ai4seo_save_anything(jQuery(submit_element), ai4seo_validate_payg_inputs, ai4seo_safe_reload);
}

// =========================================================================================== \\

function ai4seo_validate_payg_inputs() {
    // #ai4seo_payg_enabled must be checked
    let payg_confirmation_checkbox = ai4seo_jQuery("#ai4seo_payg_enabled").is(":checked");

    if (!payg_confirmation_checkbox) {
        ai4seo_open_generic_error_notification_modal(101117324, wp.i18n.__("Please confirm that you have reviewed the settings above and you want to enable Pay-As-You-Go now.", "ai-for-seo"));
        return false;
    }

    // check threshold (must be between 0 and 99999)
    let payg_credits_threshold = ai4seo_jQuery("#ai4seo_payg_credits_threshold").val();

    // cast payg_credits_threshold to int
    payg_credits_threshold = parseInt(payg_credits_threshold);

    if (payg_credits_threshold < 0 || payg_credits_threshold > 99999) {
        ai4seo_open_generic_error_notification_modal(101117325, wp.i18n.__("Please enter a valid threshold (0-99999).", "ai-for-seo"));
        return false;
    }

    // check daily budget, must be at least as high as the price
    let payg_daily_budget = ai4seo_jQuery("#ai4seo_payg_daily_budget").val();
    let payg_price = ai4seo_jQuery("#ai4seo_payg_credits_amount option:selected").data("price");

    // cast payg_daily_budget to int
    payg_daily_budget = parseInt(payg_daily_budget);

    // cast payg_price to float
    payg_price = parseFloat(payg_price);

    if (payg_daily_budget < payg_price) {
        ai4seo_open_generic_error_notification_modal(111117325, wp.i18n.__("The daily budget must be at least as high as the selected price.", "ai-for-seo"));
        return false;
    }

    // max 99999
    if (payg_daily_budget > 99999) {
        ai4seo_open_generic_error_notification_modal(121117325, wp.i18n.__("The daily budget must be at most 99999.", "ai-for-seo"));
        return false;
    }

    // check monthly budget, must be at least as high as the price
    let payg_monthly_budget = ai4seo_jQuery("#ai4seo_payg_monthly_budget").val();

    // cast payg_monthly_budget to int
    payg_monthly_budget = parseInt(payg_monthly_budget);

    if (payg_monthly_budget < payg_price) {
        ai4seo_open_generic_error_notification_modal(131117325, wp.i18n.__("The monthly budget must be at least as high as the selected price.", "ai-for-seo"));
        return false;
    }

    // max 999999
    if (payg_monthly_budget > 999999) {
        ai4seo_open_generic_error_notification_modal(141117325, wp.i18n.__("The monthly budget must be at most 999999.", "ai-for-seo"));
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_disable_payg(submit_element) {
    ai4seo_add_loading_html_to_element(submit_element);
    ai4seo_lock_and_disable_lockable_input_fields();

    ai4seo_perform_ajax_call("ai4seo_disable_payg")
        .finally(response => { ai4seo_reload_page(); });
}