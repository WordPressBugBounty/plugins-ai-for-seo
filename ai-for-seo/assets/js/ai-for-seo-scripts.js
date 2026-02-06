// Prepare variables
let ai4seo_remaining_credits = 0;
const ai4seo_admin_plugin_page_url = ai4seo_get_full_domain() + '/wp-admin/admin.php?page=ai-for-seo';
const ai4seo_admin_installed_plugins_page_url = ai4seo_get_full_domain() + '/wp-admin/plugins.php';
const ai4seo_official_contact_url = 'https://aiforseo.ai/contact';
let ai4seo_mousedown_origin = null;
const AI4SEO_GLOBAL_NONCE_IDENTIFIER = 'ai4seo_ajax_nonce';
let ai4seo_output_console_debug = false; // or false to disable all console.debug output

const ai4seo_svg_icons = {
    'circle-check': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>',
    'rotate': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><defs><style>.fa-secondary{opacity:.4}</style></defs><path class="fa-primary" d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5z"/><path class="fa-secondary" d="M16 319.6l0-7.6c0-13.3 10.7-24 24-24h7.6c.2 0 .5 0 .7 0H168c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6c0-.2 0-.5 0-.7z"/></svg>',
    'square-xmark': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm79 143c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>',
}

// Global label maps for metadata and attachment editors.
const ai4seo_metadata_labels = {
    'focus-keyphrase': wp.i18n.__('Focus Keyphrase', 'ai-for-seo'),
    'meta-title': wp.i18n.__('Meta Title', 'ai-for-seo'),
    'meta-description': wp.i18n.__('Meta Description', 'ai-for-seo'),
    'keywords': wp.i18n.__('Keywords', 'ai-for-seo'),
    'facebook-title': wp.i18n.__('Facebook Title', 'ai-for-seo'),
    'facebook-description': wp.i18n.__('Facebook Description', 'ai-for-seo'),
    'twitter-title': wp.i18n.__('Twitter/X Title', 'ai-for-seo'),
    'twitter-description': wp.i18n.__('Twitter/X Description', 'ai-for-seo'),
};

const ai4seo_attachment_attribute_labels = {
    'title': wp.i18n.__('Title', 'ai-for-seo'),
    'alt-text': wp.i18n.__('Alt Text', 'ai-for-seo'),
    'caption': wp.i18n.__('Caption', 'ai-for-seo'),
    'description': wp.i18n.__('Description', 'ai-for-seo'),
};

const ai4seo_generate_data_for_inputs = {
    // "AI for SEO" Metadata Editor modal-elements
    '#ai4seo_metadata_focus-keyphrase': {'add_generate_button': true, 'metadata_identifier': 'focus-keyphrase', 'key_by_key': false, 'processing-context': 'metadata'},

    '#ai4seo_metadata_meta-title': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata'},
    '#ai4seo_metadata_meta-description': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': false, 'processing-context': 'metadata'},
    '#ai4seo_metadata_keywords': {'add_generate_button': true, 'metadata_identifier': 'keywords', 'key_by_key': false, 'processing-context': 'metadata'},

    '#ai4seo_metadata_facebook-title': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata'},
    '#ai4seo_metadata_facebook-description': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata'},

    '#ai4seo_metadata_twitter-title': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata'},
    '#ai4seo_metadata_twitter-description': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata'},

    // "AI for SEO" Attachment Attributes Editor modal-elements
    '#ai4seo_attachment_attribute_title': {'add_generate_button': true, 'attachment_attributes_identifier': 'title', 'key_by_key': false, 'processing-context': 'attachment-attributes'},
    '#ai4seo_attachment_attribute_alt-text': {'add_generate_button': true, 'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'processing-context': 'attachment-attributes'},
    '#ai4seo_attachment_attribute_caption': {'add_generate_button': true, 'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'processing-context': 'attachment-attributes'},
    '#ai4seo_attachment_attribute_description': {'add_generate_button': true, 'attachment_attributes_identifier': 'description', 'key_by_key': false, 'processing-context': 'attachment-attributes'},

    // Yoast elements
    '#focus-keyword-input-metabox': {'add_generate_button': true, 'metadata_identifier': 'focus-keyphrase', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_focuskw': {'add_generate_button': false, 'metadata_identifier': 'focus-keyphrase', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast-google-preview-title-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_title': {'add_generate_button': false, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast-google-preview-description-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_metadesc': {'add_generate_button': false, 'metadata_identifier': 'meta-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    '#facebook-title-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#facebook-description-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_opengraph-description': {'add_generate_button': false, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#social-title-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#social-description-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    '#twitter-title-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#twitter-description-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_twitter-description': {'add_generate_button': false, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#x-title-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#x-description-input-metabox > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    '#yoast-google-preview-title-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast-google-preview-description-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    '#facebook-title-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_opengraph-title': {'add_generate_button': false, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#facebook-description-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    '#twitter-title-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#yoast_wpseo_twitter-title': {'add_generate_button': false, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#twitter-description-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    // Rank Math elements
    '.rank-math-focus-keyword > div > input': {'add_generate_button': true, 'metadata_identifier': 'focus-keyphrase', 'key_by_key': false, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-editor-title': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-editor-description': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-facebook-title': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-facebook-description': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-twitter-title': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},
    '#rank-math-twitter-description': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': true, 'processing-context': 'metadata', 'use_exec_command_workaround': true},

    // Be-Builder elements
    '.preview-mfn-meta-seo-titleinput': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata'},
    '.preview-mfn-meta-seo-descriptioninput': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': false, 'processing-context': 'metadata'},
    'input[name=mfn-meta-seo-title]': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata'},
    'input[name=mfn-meta-seo-description]': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': false, 'processing-context': 'metadata'},

    '#social-title-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-title', 'key_by_key': false, 'processing-context': 'metadata'},
    '#social-description-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'facebook-description', 'key_by_key': false, 'processing-context': 'metadata'},

    '#x-title-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-title', 'key_by_key': false, 'processing-context': 'metadata'},
    '#x-description-input-modal > div > div > div': {'add_generate_button': true, 'metadata_identifier': 'twitter-description', 'key_by_key': false, 'processing-context': 'metadata'},

    // Attachments
    '.post-type-attachment #title[name=post_title]': {'add_generate_button': true, 'attachment_attributes_identifier': 'title', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.post-type-attachment #attachment_alt[name=_wp_attachment_image_alt]': {'add_generate_button': true, 'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.post-type-attachment #attachment_caption[name=excerpt]': {'add_generate_button': true, 'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.post-type-attachment #attachment_content[name=content]': {'add_generate_button': true, 'attachment_attributes_identifier': 'description', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},

    // media library
    '.attachment-info .setting #attachment-details-two-column-title': {'add_generate_button': true, 'attachment_attributes_identifier': 'title', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-info .setting #attachment-details-two-column-alt-text': {'add_generate_button': true, 'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-info .setting #attachment-details-two-column-caption': {'add_generate_button': true, 'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-info .setting #attachment-details-two-column-description': {'add_generate_button': true, 'attachment_attributes_identifier': 'description', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},

    // media upload side bar
    '.attachment-details .setting #attachment-details-title': {'add_generate_button': true, 'attachment_attributes_identifier': 'title', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-details .setting #attachment-details-alt-text': {'add_generate_button': true, 'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-details .setting #attachment-details-caption': {'add_generate_button': true, 'attachment_attributes_identifier': 'caption', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},
    '.attachment-details .setting #attachment-details-description': {'add_generate_button': true, 'attachment_attributes_identifier': 'description', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},

    // gutenberg side bar
    '.block-editor-block-inspector .components-tools-panel-item .components-base-control .components-textarea-control__input': {'add_generate_button': true, 'attachment_attributes_identifier': 'alt-text', 'key_by_key': false, 'css-class': 'ai4seo-attachment-generate-attributes-button', 'processing-context': 'attachment-attributes'},

    // SEO KEY Plugin
    '#tab-seokey-metas #meta-tags-inputs #metatitle': {'add_generate_button': true, 'metadata_identifier': 'meta-title', 'key_by_key': true, 'processing-context': 'metadata'},
    '#tab-seokey-metas #meta-tags-inputs #meta-tags-inputs-textarea': {'add_generate_button': true, 'metadata_identifier': 'meta-description', 'key_by_key': false, 'processing-context': 'metadata'},
};

const ai4seo_content_containers = [
    '.editor-post-title', '.wp-block-post-title', '.editor-post-excerpt__textarea textarea', '.wp-block-paragraph', '.wp-block-post-content', // Gutenberg
    '#titlediv > #titlewrap > input', '.wp-editor-area', '.woocommerce-Tabs-panel', // WooCommerce products
    'header h1.title', '.item-preview-content', '.elementor-widget-container', // Elementor
    '.mce-content-body', '.mcb-wrap-inner', '.the_content_wrapper', // Be-Builder
];

const ai4seo_generate_all_button_selectors = {
    'metadata': [
        '#ai4seo-generate-all-metadata-button-hook', // AI for SEO Metadata Editor
        '#wpseo-metabox-root', // Yoast SEO
        '#meta-tags-inputs', // BeBuilder
        //'.rank-math-tab-content-general', // Rank Math, bugged as we cannot detect/change all hidden fields here
    ],
    'attachment-attributes': [
        '#ai4seo-generate-all-attachment-attributes-button-hook', // AI for SEO Attachment Attributes Editor
        '.media-frame-content .attachment-info .details', // Media library modal
        '.post-type-attachment .wp_attachment_details.edit-form-section' // Attachment edit page
    ],
}

const ai4seo_error_codes_and_messages = {
    '12127323': wp.i18n.__('Could not initialize connection to AI for SEO server. Please contact the plugin developer.', 'ai-for-seo'),
    '13127323': wp.i18n.__('Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo'),
    '21127323': wp.i18n.__('Could not read post content.', 'ai-for-seo'),
    '22127323': wp.i18n.__('Posts content is empty.', 'ai-for-seo'),
    '351229323': wp.i18n.__('Posts content is empty.', 'ai-for-seo'),
    '491320823': wp.i18n.__('Posts content is too short.', 'ai-for-seo'),
    '28127323': wp.i18n.__('Could not execute API call. Please check your browser console for more details.', 'ai-for-seo'),
    '31127323': wp.i18n.__('AI for SEO server call did not return a success value. Please try again.', 'ai-for-seo'),
    '47127323': wp.i18n.__('AI for SEO server call returned an invalid success value. Please try again.', 'ai-for-seo'),
    '48127323': wp.i18n.__('AI for SEO server call did not return data. Please try again.', 'ai-for-seo'),
    '49127323': wp.i18n.__('AI for SEO server call returned an empty data array. Please try again.', 'ai-for-seo'),
    '50127323': wp.i18n.__('AI for SEO server call did not return consumed Credits. Please try again.', 'ai-for-seo'),
    '51127323': wp.i18n.__('AI for SEO server call did not return new Credits balance. Please try again.', 'ai-for-seo'),
    '52127323': wp.i18n.__('AI for SEO server call returned an invalid data array. Please try again.', 'ai-for-seo'),
    '291215624': wp.i18n.__('AI for SEO server call returned an invalid data array. Please try again.', 'ai-for-seo'),
    '301215624': wp.i18n.__('AI for SEO server call returned an invalid data array. Please try again.', 'ai-for-seo'),
    '311215624': wp.i18n.__('AI for SEO server call returned an invalid data array. Please try again.', 'ai-for-seo'),
    '1115424': wp.i18n.__('Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.', 'ai-for-seo'),
    '1215424': wp.i18n.__('Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.', 'ai-for-seo'),
    "3619101024": wp.i18n.__('This content violates our usage policies and cannot be processed. Please modify your content and try again.', 'ai-for-seo'),
};

const ai4seo_robhub_api_response_error_codes = [32127323, 18197323, 311823824];

const ai4seo_robhub_api_response_error_codes_and_messages = {
    'client secret is invalid. Api-Error-Code: 351816823': wp.i18n.__('Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo'),
    'client is not active. Api-Error-Code: 361816823': wp.i18n.__('Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo'),
    'could not create client. Api-Error-Code: 571931823': wp.i18n.__('Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo'),
    ': client not found. Api-Error-Code: 581931823': wp.i18n.__('Could not initialize AI for SEO server credentials. Please check your settings or contact the plugin developer.', 'ai-for-seo'),
    'client has insufficient credits': wp.i18n.__('Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.', 'ai-for-seo') + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__('Click here to add Credits', 'ai-for-seo') + '</a>',
    'No Credits left. Please get more credits.': wp.i18n.__('Your AI for SEO account does not contain sufficient Credits. Please add more Credits to your account.', 'ai-for-seo') + "<br /><br /><a href='" + ai4seo_admin_plugin_page_url + "' target='_blank'>" + wp.i18n.__('Click here to add Credits', 'ai-for-seo') + '</a>',
    'Too Many Requests. Api-Error-Code: 381816823': wp.i18n.__('Maximum number of requests reached. Please try again later.', 'ai-for-seo'),
    'Too Many Requests. Api-Error-Code: 591931823': wp.i18n.__('Maximum number of requests reached. Please try again later.', 'ai-for-seo'),
    'input parameter is too short': wp.i18n.__('The provided content length insufficient for optimal SEO performance.', 'ai-for-seo'),
    'We detected inappropriate content': wp.i18n.__('The provided post or media file contains inappropriate content. Please adjust your content and try again.', 'ai-for-seo'),
    'client blocked from using this service': wp.i18n.__('Your AI for SEO account has been blocked from using this service due to suspicious activity. Please contact the plugin developer if you believe this is an error.', 'ai-for-seo'),
};

const ai4seo_init_our_scripts_click_selectors = [
    // yoast
    '#yoast-google-preview-modal-open-button',
    '#yoast-facebook-preview-modal-open-button',
    '#yoast-twitter-preview-modal-open-button',
    '#wpseo-meta-tab-content',
    '#wpseo-meta-tab-social',
    '#yoast-search-appearance-modal-open-button',
    '#yoast-social-appearance-modal-open-button',
    '.sc-gKPRtg',

    // elementor
    '#page-options-tab',
    '#elementor-panel-header-menu-button',
    'button[value=document-settings]',
    'button.elementor-tab-control-settings',
    'button.elementor-tab-control-yoast-seo-tab',
    'button.MuiButtonBase-root',

    // rank math
    '.rank-math-toolbar-score',
    '.rank-math-edit-snippet',
    '.serp-preview-wrapper',
    '.rank-math-tabs button',
    '.rank-math-editor-social button',
    '.rank-math-editor-social .components-form-toggle',

    // media
    '.block-editor-media-replace-flow__media-upload-menu',
    '#editor',
    '.attachment-preview > .thumbnail',
    '.media-modal .edit-media-header button.left.dashicons',
    '.media-modal .edit-media-header button.right.dashicons',
];

const ai4seo_admin_scripts_version_number = ai4seo_get_admin_scripts_version_number();

const ai4seo_js_file_path = ai4seo_get_ai4seo_plugin_directory_url() + '/assets/js/ai-for-seo-scripts.js?ver=' + ai4seo_admin_scripts_version_number;
const ai4seo_js_file_id = 'ai-for-seo-scripts-js';

const ai4seo_css_file_path = ai4seo_get_ai4seo_plugin_directory_url() + '/assets/css/ai-for-seo-styles.css?ver=' + ai4seo_admin_scripts_version_number;
const ai4seo_css_file_id = 'ai-for-seo-styles-css';

const ai4seo_supported_mime_types = ['image/jpeg', 'JPEG', 'image/jpg', 'JPG', 'image/png', 'PNG', 'image/gif', 'GIF', 'image/webp', 'WEBP', 'image/avif', 'AVIF'];

const ai4seo_attachment_mime_type_selectors = ['.media-frame-content .attachment-info .details .file-type', '#minor-publishing #misc-publishing-actions .misc-pub-filetype'];

// allowed ajax function (also change in ai-for-seo.php file)
let ai4seo_allowed_ajax_actions = [
    'ai4seo_save_anything',
    'ai4seo_show_metadata_editor', 'ai4seo_show_attachment_attributes_editor',
    'ai4seo_generate_metadata', 'ai4seo_generate_attachment_attributes',
    'ai4seo_reject_tos', 'ai4seo_accept_tos', 'ai4seo_show_terms_of_service',
    'ai4seo_dismiss_notification', 'ai4seo_reset_plugin_data', 'ai4seo_stop_bulk_generation',
    'ai4seo_retry_all_failed_attachment_attributes', 'ai4seo_retry_all_failed_metadata',
    'ai4seo_disable_payg', 'ai4seo_init_purchase', 'ai4seo_track_subscription_pricing_visit',
    'ai4seo_import_nextgen_gallery_images',
    'ai4seo_export_settings', 'ai4seo_show_import_settings_preview', 'ai4seo_import_settings',
    'ai4seo_get_dashboard_html',
    'ai4seo_restore_default_settings',
    'ai4seo_request_lost_licence_data',
    'ai4seo_refresh_dashboard_statistics',
    'ai4seo_refresh_robhub_account'
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

        // try init our html elements for one second after page load to catch late loaded elements
        for (let i = 0; i <= 1000; i += 250) {
            setTimeout(function() {
                ai4seo_init_html_elements();
            }, i);
        }

        // Initialize dashboard auto-refresh if on dashboard page
        ai4seo_init_dashboard_refresh();

        // remove notification count (when dashboard was opened)
        ai4seo_remove_notification_count();

        // Init search field for the help page
        ai4seo_init_help_page_search_field();

        // Init html elements within the WordPress media-modal
        ai4seo_init_media_modal_html_elements();

        // Init elementor panel content wrapper listener
        ai4seo_init_elementor_panel_content_wrapper_listener();

        // Init our scripts load on click listeners for 3rd party editors in iframes
        for (let i = 0; i <= 5000; i += 1000) {
            setTimeout(function () {
                ai4seo_init_load_scripts_click_listeners();
            }, i);
        }

        // Init location hash links
        ai4seo_init_location_hash_links();
    });
} else {
    console.error('AI for SEO: jQuery is not defined \u2014 AI for SEO scripts could not be initialized.');
}

// =========================================================================================== \\

function ai4seo_init_help_page_search_field() {
    // Function to perform the search
    const $help_search_inputs = ai4seo_normalize_$('.ai4seo-help-search');

    if (!ai4seo_exists_$($help_search_inputs)) {
        ai4seo_console_debug('AI for SEO: No help search inputs found in ai4seo_init_help_page_search_field() — skipping initialization.');
        return;
    }

    const $faq_section_holder = ai4seo_normalize_$('.ai4seo-faq-section-holder');
    const $faq_entry_holder = ai4seo_normalize_$('.ai4seo-accordion-holder');
    const $no_results_notice_holder = ai4seo_normalize_$('.ai4seo-help-faq-search-notice');

    if (!ai4seo_exists_$($faq_section_holder) || !ai4seo_exists_$($faq_entry_holder) || !ai4seo_exists_$($no_results_notice_holder)) {
        console.warn('AI for SEO: Help search containers missing in ai4seo_help_search_keyup_handler() — cannot filter FAQ results.');
        return;
    }

    $help_search_inputs.off('keyup.ai4seo-help-search'); // Remove any previous keyup handlers to avoid duplicates
    $help_search_inputs.on('keyup.ai4seo-help-search', function(event) {
        const $this_search_input = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_search_input)) {
            console.warn('AI for SEO: Search input missing in ai4seo_help_search_keyup_handler() — skipping keyup event.');
            return;
        }

        const code_of_key_pressed = event.keyCode || event.which;
        const search_text = $this_search_input.val().toLowerCase();

        let has_results = false;

        if (search_text.length >= 3) {
            // Display all elements if char is deleted in input
            if (code_of_key_pressed === 8 || code_of_key_pressed === 46) {
                $faq_entry_holder.show();
                $faq_section_holder.show();
                $no_results_notice_holder.hide();
            }

            // Hide all faq-holders once the minimum of 3 characters have been added to the search field
            $faq_entry_holder.hide();

            // Loop through each faq-holder to check for a match
            $faq_entry_holder.each(function() {
                const $faq_entry = ai4seo_normalize_$(this);

                if (!$faq_entry) {
                    console.warn('AI for SEO: FAQ entry missing in ai4seo_help_search_keyup_handler() — skipping entry.');
                    return;
                }

                const $accordion_headline = $faq_entry.find('.ai4seo-accordion-headline');
                const $accordion_content = $faq_entry.find('.ai4seo-accordion-content');

                // Check if the faq-entry has a headline, if not skip this entry
                if (!ai4seo_exists_$($accordion_headline) || !ai4seo_exists_$($accordion_content)) {
                    console.warn('AI for SEO: FAQ accordion content missing in ai4seo_help_search_keyup_handler() — skipping entry.');
                    return;
                }

                const accordion_headline_text = $accordion_headline.text().toLowerCase();
                const accordion_content_text = $accordion_content.text().toLowerCase();

                // Check if the search_text is found in either the headline or the content
                if (accordion_headline_text.includes(search_text) || accordion_content_text.includes(search_text)) {
                    // Show this faq-entry if a match was found
                    $faq_entry.show();
                    has_results = true;
                }
            });

            // Loop through each faq-section-holder to check if there are still faq-entries in this section
            $faq_section_holder.each(function() {
                const $faq_section = ai4seo_normalize_$(this);

                if (!$faq_section) {
                    console.warn('AI for SEO: FAQ section missing in ai4seo_help_search_keyup_handler() — skipping section.');
                    return;
                }

                const $visible_accordion_headline_childs = $faq_section.find('.ai4seo-accordion-headline:visible');

                if (ai4seo_exists_$($visible_accordion_headline_childs)) {
                    $faq_section.show();
                } else {
                    $faq_section.hide();
                }
            });

            // Toggle the no results message based on whether matches have been found
            if (has_results) {
                $no_results_notice_holder.hide();
            } else {
                $no_results_notice_holder.show();
            }
        } else {
            // Show all accordion holders and hide the no results message if less than 3 characters are entered
            $faq_entry_holder.show();
            $faq_section_holder.show();
            $no_results_notice_holder.hide();
        }
    });
}

// =========================================================================================== \\

function ai4seo_init_location_hash_links() {
    // check for any anchor in the url and click the corresponding button
    const ai4seo_location_hash = window.location.hash;

    if (ai4seo_location_hash) {
        const $hash_link = ai4seo_normalize_$("a[href='" + ai4seo_location_hash + "']");

        if (ai4seo_exists_$($hash_link)) {
            $hash_link.children().click();
        }
    }
}

// =========================================================================================== \\

function ai4seo_init_media_modal_html_elements() {
    // Observe global media attachment additions -> add generate buttons
    if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined'
        && typeof wp.media.model !== 'undefined'
        && typeof wp.media.model.Attachments !== 'undefined'
        && typeof wp.media.model.Attachments.all !== 'undefined') {

        wp.media.model.Attachments.all.on('add', function(attachment) {
            if (attachment && attachment.get('type') === 'image') {
                // call ai4seo_init_html_elements for the next 10 seconds, 1 second each
                for (let i = 1; i <= 10; i++) {
                    setTimeout(function () {
                        // add generate buttons
                        ai4seo_init_generate_all_button();
                        ai4seo_init_generate_buttons();
                    }, 1000 * i);
                }
            }
        });
    }

    // Prepare variables
    const max_attempts = 10;
    let attempts = 0;
    const interval = 500;

    function ai4seo_check_visibility() {
        attempts++;

        // Check if the media-modal-element is visible
        if (ai4seo_exists_$('.media-modal.wp-core-ui')) {
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

function ai4seo_init_elementor_panel_content_wrapper_listener() {
    const $elementor_panel_content_wrapper = ai4seo_normalize_$('#elementor-panel-content-wrapper');

    if (ai4seo_exists_$($elementor_panel_content_wrapper)) {
        // Workaround to display buttons within elementor-navigation
        $elementor_panel_content_wrapper.click(function () {
            setTimeout(function () {
                ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation();
            }, 200);
        });
    }
}

// =========================================================================================== \\

/**
 * Helps to load our main scripts only when needed (on user interaction) on various third party editors, typically loaded in iframes.
 */
function ai4seo_init_load_scripts_click_listeners() {
    // Add click-functions to parent-window for ai4seo_click_function_containers-elements if they exist
    // Loop through all click-function-containers
    const $parent_document_body = ai4seo_normalize_$('body', window.parent.document);

    if (!ai4seo_exists_$($parent_document_body)) {
        console.warn('AI for SEO: element \"$parent_document_body\" missing in document_ready_handler() \u2014 cannot attach delegated bindings.');
        return;
    }

    for (let i = 0; i < ai4seo_init_our_scripts_click_selectors.length; i++) {
        // Check if click-function-container exists
        if (!ai4seo_exists_$(ai4seo_init_our_scripts_click_selectors[i])) {
            //ai4seo_console_debug('AI for SEO: selector ' + ai4seo_init_our_scripts_click_selectors[i] + ' no match in ai4seo_init_load_scripts_click_listeners() \u2014 skipping delegated binding.');
            continue;
        }

        ai4seo_console_debug('AI for SEO: Adding delegated binding for selector ' + ai4seo_init_our_scripts_click_selectors[i] + ' in ai4seo_init_load_scripts_click_listeners().');

        // Add click-function to parent-window
        $parent_document_body.off('click.ai4seo-init-scripts', ai4seo_init_our_scripts_click_selectors[i]);
        $parent_document_body.on('click.ai4seo-init-scripts', ai4seo_init_our_scripts_click_selectors[i], function() {
            setTimeout(function() {
                ai4seo_console_debug('AI for SEO: Detected click on selector ' + ai4seo_init_our_scripts_click_selectors[i] + ' \u2014 loading AI for SEO scripts.');

                // Call function to load js-file to main-window
                ai4seo_try_load_js_file(ai4seo_js_file_path, ai4seo_js_file_id);

                // Call function to load css-file to main-window
                ai4seo_try_load_css_file(ai4seo_css_file_path, ai4seo_css_file_id);

                // Call function to load ai4seo_localization-object to main-window
                ai4seo_try_set_localization_to_window_top();

                // init scripts click listeners again to catch late clicks
                // Init our scripts load on click listeners for 3rd party editors in iframes
                for (let i = 0; i <= 5000; i += 1000) {
                    setTimeout(function () {
                        ai4seo_init_load_scripts_click_listeners();
                    }, i);
                }

                // Init elements
                setTimeout(function() {
                    ai4seo_init_html_elements();
                }, 200);
            }, 100);
        });
    }
}

// =========================================================================================== \\

function ai4seo_try_load_js_file(url, script_id = '', callback = null) {
    // Stop script if no url is given
    if (!url) {
        return;
    }

    // Check if script is already loaded
    if (ai4seo_exists_$('#' + script_id)) {
        return;
    }

    // Define variable for the script-element
    const $script = ai4seo_normalize_$(window.top.document.createElement('script'), window.top.document);

    if (!ai4seo_exists_$($script)) {
        console.error('AI for SEO: Could not create script element in ai4seo_load_js_file() \u2014 aborting script load.');
        return;
    }

    // Set type-attribute for the script-element
    $script.attr('type', 'text/javascript');

    // Set src-attribute for the script-element
    $script.attr('src', url);

    // Set id-attribute for the script-element if an id is given
    if (script_id) {
        $script.attr('id', script_id);
    }

    // Add callback-function to the script-element if a callback is needed after the script is loaded
    if (callback) {
        $script.on('load', callback);
    }

    // Add script-element to the head-element of the parent window
    const $window_top_head = ai4seo_normalize_$(window.top.document.head, window.top.document);

    if (!ai4seo_exists_$($window_top_head)) {
        console.error('AI for SEO: Parent window head element missing in ai4seo_load_js_file() \u2014 aborting script load.');
        return;
    }

    $window_top_head.append($script);
}

// =========================================================================================== \\

function ai4seo_try_load_css_file(url, script_id = '', callback = null) {
    // Stop script if no url is given
    if (!url) {
        return;
    }

    // Check if script is already loaded
    if (ai4seo_exists_$('#' + script_id)) {
        return;
    }

    // Define variable for the link-element
    const $link = ai4seo_normalize_$(window.top.document.createElement('link'), window.top.document);

    if (!ai4seo_exists_$($link)) {
        console.error('AI for SEO: Could not create link element in ai4seo_load_css_file() \u2014 aborting stylesheet load.');
        return;
    }

    // Set type-attribute for the link-element
    $link.attr('type', 'text/css');

    // Set rel-attribute for the link-element
    $link.attr('rel', 'stylesheet');

    // Set href-attribute for the link-element
    $link.attr('href', url);

    // Set media-attribute for the link-element
    $link.attr('media', 'all');

    // Set id-attribute for the link-element if an id is given
    if (script_id) {
        $link.attr('id', script_id);
    }

    // Add callback-function to the link-element if a callback is needed after the link is loaded
    if (callback) {
        $link.on('load', callback);
    }

    // Add link-element to the head-element of the parent window
    const $window_top_head = ai4seo_normalize_$(window.top.document.head, window.top.document);

    if (!ai4seo_exists_$($window_top_head)) {
        console.error('AI for SEO: Parent window head element missing in ai4seo_load_css_file() \u2014 aborting stylesheet load.');
        return;
    }

    $window_top_head.append($link);
}

// =========================================================================================== \\

function ai4seo_try_set_localization_to_window_top() {
    // already defined in window.top -> skip
    if (typeof window.top.ai4seo_localization !== 'undefined') {
        return;
    }

    // check if ai4seo_localization exists -> should be defined through wp_localize_script
    if (typeof ai4seo_localization === 'undefined') {
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

    // init inactive countdown buttons
    ai4seo_init_inactive_countdown_buttons();

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

    // init buttons
    ai4seo_init_buttons();

    // init copy to clipboard functionality
    ai4seo_init_copy_to_clipboard();

    // Add open-layer-button to edit-page-header
    ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header();

    // Add open-layer-button to be-builder-navigation
    ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation();

    // Add open-layer-button to elementor-navigation
    ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation();

    // Init forms on license page
    ai4seo_init_license_form();

    // init advanced settings
    ai4seo_init_advanced_settings();

    // init render-level alt text settings visibility
    ai4seo_init_alt_text_injection_settings();

    // init generate buttons on gutenberg editor clicks
    setTimeout(function() {
        ai4seo_init_gutenberg_editor_generate_buttons();
    }, 1000);

    // notifications
    ai4seo_init_notifications();

    // init plugin version number
    init_plugin_version_number();

    // init sticky-buttons-bar
    ai4seo_init_sticky_buttons_bar();
    ai4seo_init_sticky_modal_footer();

    // init auto resize textareas
    ai4seo_init_auto_resize_textareas();
}

// =========================================================================================== \\

function ai4seo_init_buttons() {
    const $document = ai4seo_normalize_$(document);

    if (!ai4seo_exists_$($document)) {
        console.error('AI for SEO: element \"$document\" missing in ai4seo_init_notifications() \u2014 cannot initialize notification dismissal.');
        return;
    }

    // class "ai4seo-button" -> add data-currently-pressed while mouse is down and remove on mouse up
    $document.off('mousedown.ai4seo-button-press');
    $document.on('mousedown.ai4seo-button-press', '.ai4seo-button', function() {
        const $this_button = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_button)) {
            console.warn('AI for SEO: element \"$this_button\" missing in ai4seo_init_buttons() \u2014 cannot add currently-pressed state.');
            return;
        }

        $this_button.data('currently-pressed', 'true');
    });

    // remove data on mouse up
    $document.off('mouseup.ai4seo-button-press');
    $document.on('mouseup.ai4seo-button-press', '.ai4seo-button', function() {
        const $this_button = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_button)) {
            console.warn('AI for SEO: element \"$this_button\" missing in ai4seo_init_buttons() \u2014 cannot remove currently-pressed state.');
            return;
        }

        $this_button.removeData('currently-pressed');
    });
}

// =========================================================================================== \\

function ai4seo_init_sticky_buttons_bar() {
    const $sticky_buttons_bars = ai4seo_normalize_$('.ai4seo-sticky-buttons-bar');
    const buffer_tolerance = 20; // pixels

    if (!ai4seo_exists_$($sticky_buttons_bars)) {
        return;
    }

    const $window = ai4seo_normalize_$(window);

    if (!ai4seo_exists_$($window)) {
        console.error('AI for SEO: window object missing in ai4seo_init_sticky_buttons_bar() — cannot calculate box-shadow removal on scroll.');
        return;
    }

    const $document = ai4seo_normalize_$(document);

    if (!ai4seo_exists_$($document)) {
        console.error('AI for SEO: document object missing in ai4seo_init_sticky_buttons_bar() — cannot calculate box-shadow removal on scroll.');
        return;
    }

    // compute for all sticky bars
    function ai4seo_sticky_buttons_bar_compute() {
        const scroll_top = $window.scrollTop();
        const window_height = $window.height();
        const document_height = $document.height();
        const at_bottom = (scroll_top + window_height >= document_height - buffer_tolerance);

        $sticky_buttons_bars.each(function() {
            const $this_sticky_buttons_bar = ai4seo_normalize_$(this);
            if (!ai4seo_exists_$($this_sticky_buttons_bar)) { return; }

            const $this_possible_buttons_wrapper = $this_sticky_buttons_bar.find('.ai4seo-buttons-wrapper');
            const $this_target = ai4seo_exists_$($this_possible_buttons_wrapper) ? $this_possible_buttons_wrapper : $this_sticky_buttons_bar;

            if (at_bottom === true) {
                $this_target.removeClass('ai4seo_sticky_element_hides_something');
            } else {
                $this_target.addClass('ai4seo_sticky_element_hides_something');
            }
        });
    }

    // resize handler reuses the same compute, debounced
    const debounced_resize = ai4seo_debounce(function() {
        ai4seo_sticky_buttons_bar_compute();
    }, 100);

    // ensure only one global handler each
    $window.off('scroll.ai4seo-sticky-buttons-bar').on('scroll.ai4seo-sticky-buttons-bar', ai4seo_sticky_buttons_bar_compute);
    $window.off('resize.ai4seo-sticky-buttons-bar').on('resize.ai4seo-sticky-buttons-bar', debounced_resize);

    // initial compute
    ai4seo_sticky_buttons_bar_compute();
}

// =========================================================================================== \\

function ai4seo_init_sticky_modal_footer() {
    const $modal_footers = ai4seo_normalize_$('.ai4seo-modal-footer');
    const buffer_tolerance = 20; // pixels

    if (!ai4seo_exists_$($modal_footers)) {
        return;
    }

    const $window = ai4seo_normalize_$(window);

    if (!ai4seo_exists_$($window)) {
        console.error('AI for SEO: window object missing in ai4seo_init_sticky_modal_footer() \u2014 cannot calculate box-shadow removal on scroll.');
        return;
    }

    function ai4seo_sticky_modal_footer_compute() {
        const $visible_modals = ai4seo_normalize_$('.ai4seo-modal:visible');

        if (!ai4seo_exists_$($visible_modals)) {
            return;
        }

        // trigger scroll on every visible modal so the class updates
        $visible_modals.each(function() {
            const $this_visible_modal = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_visible_modal)) {
                return;
            }

            $this_visible_modal.triggerHandler('scroll');
        });
    }

    // one shared debounced resize handler
    const debounced_resize = ai4seo_debounce(function() {
        ai4seo_sticky_modal_footer_compute();
    }, 100);

    // ensure only one global handler
    $window.off('resize.ai4seo-modal-footer').on('resize.ai4seo-modal-footer', debounced_resize);

    $modal_footers.each(function() {
        const $this_footer = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_footer)) {
            console.warn('AI for SEO: element "$footer" missing in ai4seo_init_modal_footer_shadow_toggle() — skipping item.');
            return;
        }

        // find the modal scroll container
        const $this_modal = $this_footer.closest('.ai4seo-modal');

        if (!ai4seo_exists_$($this_modal)) {
            console.warn('AI for SEO: element ".ai4seo-modal" missing for footer — cannot calculate box-shadow removal on scroll.');
            return;
        }

        // prefer inner buttons wrapper if present
        const $this_buttons_wrapper = $this_footer.find('.ai4seo-buttons-wrapper');
        const $this_target = ai4seo_exists_$($this_buttons_wrapper) ? $this_buttons_wrapper : $this_footer;

        // ensure we do not stack handlers for the same modal
        $this_modal.off('scroll.ai4seo-modal-footer');
        $this_modal.on('scroll.ai4seo-modal-footer', function() {
            const $this_modal = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_modal)) {
                return;
            }

            const this_scroll_top = $this_modal.scrollTop();
            const this_visible_height = $this_modal.innerHeight();
            const this_content_height = this.scrollHeight;

            if (this_scroll_top + this_visible_height >= this_content_height - buffer_tolerance) {
                // scrolled to bottom inside modal
                $this_target.removeClass('ai4seo_sticky_element_hides_something');
            } else {
                // not at bottom
                $this_target.addClass('ai4seo_sticky_element_hides_something');
            }
        });

        // set initial state
        $this_modal.triggerHandler('scroll');
    });
}

// =========================================================================================== \\

function ai4seo_init_copy_to_clipboard() {
    // Loop through all elements with class ai4seo-copy-to-clipboard
    const $copy_to_clipboard_targets = ai4seo_normalize_$('.ai4seo-copy-to-clipboard');

    if (!ai4seo_exists_$($copy_to_clipboard_targets)) {
        //ai4seo_console_debug('AI for SEO: elements \".ai4seo-copy-to-clipboard\" missing in ai4seo_init_copy_to_clipboard() \u2014 skipping clipboard binding.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Initializing copy to clipboard for ' + $copy_to_clipboard_targets.length + ' elements in ai4seo_init_copy_to_clipboard().');

    $copy_to_clipboard_targets.each(function() {
        const $this = ai4seo_normalize_$(this);
        
        if (!ai4seo_exists_$($this)) {
            console.error('AI for SEO: element \"$this\" missing in ai4seo_init_copy_to_clipboard() \u2014 skipping item.');
            return;
        }
        
        // Get the text to copy from the data-clipboard-text attribute
        let text_to_copy = $this.data('clipboard-text');

        // If the text is not defined, skip this element
        if (typeof text_to_copy === 'undefined' || !text_to_copy) {
            console.warn('AI for SEO: Could not copy to clipboard');
            return;
        }

        // find closest .ai4seo-copied-to-clipboard to display it for 3 seconds
        let $copied_to_clipboard = ai4seo_get_nearest_element_$(this, '.ai4seo-copied-to-clipboard');

        // Add click event listener to the element
        $this.off('click.ai4seo-copy-to-clipboard');
        $this.on('click.ai4seo-copy-to-clipboard', function(event) {
            event.preventDefault();
            ai4seo_copy_to_clipboard(text_to_copy, $copied_to_clipboard);
        });
    });
}

// =========================================================================================== \\

function init_plugin_version_number() {
    let $sidebar = ai4seo_normalize_$('.ai4seo-sidebar');
    let $sidebar_version_number = ai4seo_normalize_$('.ai4seo-sidebar-version-number');
    
    if (!ai4seo_exists_$('.ai4seo-sidebar-version-number')) {
        // ai4seo_console_debug('AI for SEO: selector \".ai4seo-sidebar-version-number\" missing in init_plugin_version_number() \u2014 skipping version badge placement.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Positioning version badge in ai4seo_init_plugin_version_number().');

    if (!ai4seo_exists_$('.ai4seo-sidebar')) {
        //ai4seo_console_debug('AI for SEO: selector \".ai4seo-sidebar\" missing in init_plugin_version_number() \u2014 cannot position version badge.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Calculating sidebar spare height for version badge placement in ai4seo_init_plugin_version_number().');

    // set .ai4seo-sidebar-version-number to position absolute and bottom 1rem
    // in case .ai4seo-sidebar's height is below 100vh - 8rem;
    // calc height of all sub elements in sidebar
    let sidebar_spare_height = ai4seo_get_container_spare_height($sidebar);

    if (sidebar_spare_height > 0) {
        // set $sidebar_version_number to position absolute and bottom 1rem
        $sidebar_version_number.css({
            'position': 'absolute',
            'bottom': '1rem',
            'left': '0',
        });
    }
}

// =========================================================================================== \\

/**
 * Searches for an element with the given target selector that is a sibling, child, or closest ancestor of the provided element.
 * @param $reference
 * @param target_selector
 * @returns {*|null}
 */
function ai4seo_get_nearest_element_$($reference, target_selector) {
    $reference = ai4seo_normalize_$($reference);

    // Check if the element exists
    if (!ai4seo_exists_$($reference)) {
        console.warn('AI for SEO: element \"$reference\" missing in ai4seo_get_nearest_element() \u2014 unable to resolve related UI.');
        return null;
    }

    // check sibling elements first
    let $sibling = $reference.siblings(target_selector);

    if (ai4seo_exists_$($sibling)) {
        return $sibling.first();
    }

    // check children elements with find
    let $child = $reference.find(target_selector);

    if (ai4seo_exists_$($child)) {
        // If a child element is found, return the first one
        return $child.first();
    }

    // check closest element with the target selector
    let $closest = $reference.closest(target_selector);

    // If no closest element found, check for the next sibling element
    if (ai4seo_exists_$($closest)) {
        return $closest.first();
    }

    return null;
}


// =========================================================================================== \\

function ai4seo_get_container_spare_height($container) {
    $container = ai4seo_normalize_$($container);

    if (!ai4seo_exists_$($container)) {
        console.warn('AI for SEO: container \"$container\" missing in ai4seo_get_container_spare_height() \u2014 layout measurement unavailable.');
        return;
    }

    // go through all children and sum their heights
    let container_element_children_elements_height = 0;

    const $container_children = $container.children();

    if (ai4seo_exists_$($container_children)) {
        $container_children.each(function() {
            const $child = ai4seo_normalize_$(this);
            
            if (!ai4seo_exists_$($child)) {
                console.error('AI for SEO: element \"$child\" missing in ai4seo_get_container_spare_height() \u2014 DOM traversal skipped.');
                return;
            }
            
            container_element_children_elements_height += $child.outerHeight(true);
        });
    }

    let container_height = $container.outerHeight(true);

    return container_height - container_element_children_elements_height;
}

// =========================================================================================== \\

let ai4seo_init_gutenberg_editor_loop_timeout = null;

function ai4seo_init_gutenberg_editor_generate_buttons() {
    // find figure.wp-block-media-text__media in gutenberg editor iframe
    let $wp_block_media_text_media = ai4seo_get_gutenberg_editor_$();

    $wp_block_media_text_media = ai4seo_normalize_$($wp_block_media_text_media);

    if (!ai4seo_exists_$($wp_block_media_text_media)) {
        ai4seo_console_debug('AI for SEO: element \"$wp_block_media_text_media\" missing in ai4seo_init_gutenberg_editor_generate_buttons() \u2014 skipping Gutenberg media binding.');
        return;
    }

    // unbind previous click handlers to avoid multiple bindings
    $wp_block_media_text_media.off('click.ai4seo-gutenberg');

    // call ai4seo_init_generate_buttons(); on every click on the $wp_block_media_text_media
    $wp_block_media_text_media.on('click.ai4seo-gutenberg', function() {
        // Check if the element is visible
        if ($wp_block_media_text_media.is(':visible')) {
            // Call function to init generate buttons
            ai4seo_init_generate_buttons();
        }
    });

    // return if ai4seo_init_gutenberg_editor_loop_timeout is already set
    if (ai4seo_init_gutenberg_editor_loop_timeout) {
        // clear the timeout to prevent multiple executions
        clearTimeout(ai4seo_init_gutenberg_editor_loop_timeout);
    }

    // retry after a second, in case the user uploaded new media
    ai4seo_init_gutenberg_editor_loop_timeout = setTimeout(function() {
        ai4seo_init_gutenberg_editor_generate_buttons();
    }, 2500);
}

// =========================================================================================== \\

function ai4seo_get_gutenberg_editor_$() {
    // Get the Gutenberg editor iframe
    let gutenberg_editor_iframe_element = ai4seo_get_gutenberg_editor_iframe_element();

    // If the iframe does not exist, return null
    if (!gutenberg_editor_iframe_element) {
        return null;
    }

    // get the document element of the Gutenberg editor iframe
    let gutenberg_editor_iframe_document = null;

    if (typeof gutenberg_editor_iframe_element.contentDocument !== 'undefined' && gutenberg_editor_iframe_element.contentDocument) {
        gutenberg_editor_iframe_document = gutenberg_editor_iframe_element.contentDocument;
    } else if (typeof gutenberg_editor_iframe_element.contentWindow !== 'undefined' && gutenberg_editor_iframe_element.contentWindow.document) {
        gutenberg_editor_iframe_document = gutenberg_editor_iframe_element.contentWindow.document;
    } else {
        return null;
    }

    const $gutenberg_editor_iframe_document = ai4seo_normalize_$(gutenberg_editor_iframe_document);

    if (!ai4seo_exists_$($gutenberg_editor_iframe_document)) {
        console.error('AI for SEO: Gutenberg iframe document missing in ai4seo_init_gutenberg_editor_generate_buttons() \u2014 deferring block integrations.');
        return null;
    }

    // Use jQuery to find the element inside the iframe document
    return $gutenberg_editor_iframe_document.find('figure');
}

// =========================================================================================== \\

function ai4seo_get_gutenberg_editor_iframe_element() {
    // Check if the iframe with name 'editor-canvas' exists
    if (!ai4seo_exists_$('iframe[name="editor-canvas"]')) {
        ai4seo_console_debug('AI for SEO: selector iframe[name=\"editor-canvas\"] missing in ai4seo_get_gutenberg_editor_iframe_element() \u2014 skipping editor access.');
        return null;
    }

    // Get the iframe element
    let $gutenberg_editor_iframe = ai4seo_normalize_$('iframe[name="editor-canvas"]');

    // Return the iframe element
    return $gutenberg_editor_iframe.get(0);
}

// =========================================================================================== \\

function ai4seo_toggle_sidebar() {
    const $sidebar = ai4seo_normalize_$('.ai4seo-sidebar');

    if (!ai4seo_exists_$($sidebar)) {
        console.error('AI for SEO: $sidebar missing in ai4seo_toggle_sidebar() \u2014 cannot toggle sidebar visibility.');
        return;
    }

    if ($sidebar.hasClass('ai4seo-sidebar-open')) {
        $sidebar.removeClass('ai4seo-sidebar-open');

        // Remove the click event listener for outside clicks
        ai4seo_normalize_$(document).off('click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click);
    } else {
        $sidebar.addClass('ai4seo-sidebar-open');

        // Add a click event listener for outside clicks
        ai4seo_normalize_$(document).off('click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click);
        ai4seo_normalize_$(document).on('click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click);
    }
}

// =========================================================================================== \\

function ai4seo_toggle_visibility($target, $caret_down, $caret_up, duration = 0) {
    $target = ai4seo_normalize_$($target);

    if (!ai4seo_exists_$($target)) {
        console.warn('AI for SEO: element \"$target\" missing in ai4seo_toggle_visibility() \u2014 cannot toggle visibility.');
        return;
    }

    const is_visible = $target.is(':visible');

    const $normalized_caret_down = ai4seo_normalize_$($caret_down);
    const $normalized_caret_up = ai4seo_normalize_$($caret_up);

    if (is_visible) {
        $target.hide(duration);

        if (ai4seo_exists_$($normalized_caret_down)) {
            $normalized_caret_down.show();
        }

        if (ai4seo_exists_$($normalized_caret_up)) {
            $normalized_caret_up.hide();
        }
    } else {
        $target.show(duration);

        if (ai4seo_exists_$($normalized_caret_down)) {
            $normalized_caret_down.hide();
        }

        if (ai4seo_exists_$($normalized_caret_up)) {
            $normalized_caret_up.show();
        }
    }
}

// =========================================================================================== \\

function ai4seo_open_get_more_credits_modal() {
    ai4seo_open_modal_from_schema('get-more-credits', {modal_size: 'small'});

    let $all_items = ai4seo_normalize_$('#ai4seo-get-more-credits .ai4seo-get-more-credits-section');

    if (!ai4seo_exists_$($all_items)) {
        console.error('AI for SEO: elements \"$all_items\" missing in ai4seo_open_get_more_credits_modal() \u2014 credits carousel animation skipped.');
        return;
    }

    // remove transition and transform -100px to the left
    $all_items.css('transition', 'transform 0s');
    $all_items.css('transform', 'translateX(-100px)');
    $all_items.css('opacity', '0');

    // go through each item
    $all_items.each(function (index) {
        // Use a block-scoped variable to preserve the value of n
        const delay = index * 250;
        const $item = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($item)) {
            console.error('AI for SEO: element \"$item\" missing in ai4seo_open_get_more_credits_modal() \u2014 skipping iteration.');
            return;
        }

        setTimeout(function () {
            $item.css('transition', '0.5s ease-in-out');
            $item.css('transform', 'translateX(0)');
            $item.css('opacity', '1');
        }, delay);
    });
}

// =========================================================================================== \\

function ai4seo_handle_sidebar_outside_click(event) {
    const $sidebar = ai4seo_normalize_$('.ai4seo-sidebar');
    const $toggle_button = ai4seo_normalize_$('.ai4seo-mobile-top-bar-toggle-button');

    if (!ai4seo_exists_$('.ai4seo-sidebar')) {
        console.error('AI for SEO: selector \".ai4seo-sidebar\" missing in ai4seo_handle_sidebar_outside_click() \u2014 cannot evaluate outside clicks.');
        return;
    }
    
    if (!ai4seo_exists_$($toggle_button)) {
        console.error('AI for SEO: selector \".ai4seo-mobile-top-bar-toggle-button\" missing in ai4seo_handle_sidebar_outside_click() \u2014 cannot evaluate outside clicks.');
        return;
    }

    if (!$sidebar.hasClass('ai4seo-sidebar-open')) {
        return;
    }

    if(!$sidebar.is(event.target) && $sidebar.has(event.target).length === 0 && !$toggle_button.is(event.target) && $toggle_button.has(event.target).length === 0) {
        $sidebar.removeClass('ai4seo-sidebar-open');

        // Remove the click event listener for outside clicks
        ai4seo_normalize_$(document).off('click.ai4seo-sidebar-outside', ai4seo_handle_sidebar_outside_click);
    }
}

// =========================================================================================== \\

function ai4seo_init_inactive_countdown_buttons() {
    // Loop through all elements with class ai4seo-inactive-countdown-button
    const $inactive_countdown_buttons = ai4seo_normalize_$('.ai4seo-inactive-countdown-button');

    if (!ai4seo_exists_$($inactive_countdown_buttons)) {
        //ai4seo_console_debug('AI for SEO: elements \"$inactive_countdown_buttons\" missing in ai4seo_init_inactive_countdown_buttons() \u2014 timers remain disabled.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Initializing inactive countdown buttons in ai4seo_init_inactive_countdown_buttons().');

    $inactive_countdown_buttons.each(function() {
        const $button = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($button)) {
            console.error('AI for SEO: element \"$button\" missing in ai4seo_init_inactive_countdown_buttons() \u2014 handler binding skipped.');
            return;
        }

        // check if button has data-time-left attribute
        let total_seconds = $button.data('time-left');

        if (typeof total_seconds === 'undefined' || !total_seconds || isNaN(total_seconds) || total_seconds <= 0) {
            return;
        }

        // skip if data-countdown-active attribute is set to true
        let countdown_active = $button.data('countdown-active');

        if (typeof countdown_active !== 'undefined' && countdown_active) {
            return;
        }

        total_seconds = parseInt(total_seconds);

        // set button to disabled
        $button.prop('disabled', true);

        // add class ai4seo-ignore-during-dashboard-refresh if not already set
        if (!$button.hasClass('ai4seo-ignore-during-dashboard-refresh')) {
            $button.addClass('ai4seo-ignore-during-dashboard-refresh');
        }

        if (!$button.hasClass('ai4seo-inactive-button')) {
            $button.addClass('ai4seo-inactive-button');
        }

        // add "...{seconds}" to button text
        let original_button_text = $button.text();
        $button.text(original_button_text + ' (' + total_seconds + 's)');

        // add data-countdown-active attribute to button
        $button.data('countdown-active', true);

        // start countdown
        let countdown_interval = setInterval(function() {
            total_seconds--;

            if (total_seconds <= 0) {
                clearInterval(countdown_interval);
                $button.prop('disabled', false);
                $button.removeClass('ai4seo-inactive-button');
                $button.text(original_button_text);
                $button.removeData('time-left');
                $button.removeData('countdown-active');
                $button.removeClass('ai4seo-inactive-countdown-button');
                return;
            }

            $button.text(original_button_text + ' (' + total_seconds + 's)');
            $button.data('time-left', total_seconds);
        }, 1000);
    });
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

    const active_meta_tags = ai4seo_get_active_meta_tags();
    const active_attachment_attributes = ai4seo_get_active_attachment_attributes();

    // Loop through mapping and call function to add button-element
    jQuery.each(ai4seo_generate_data_for_inputs, function(this_generate_data_for_input_selector, this_generate_data_for_input_details) {
        let $this_generate_data_for_input = ai4seo_normalize_$(this_generate_data_for_input_selector);

        // keep selector intact
        // check if a jquery element exists for the selector
        if (!ai4seo_exists_$($this_generate_data_for_input)) {
            // ai4seo_console_debug('AI for SEO: No generate data for input match found for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons() \u2014 skipping generate button addition.');
            return;
        }

        // add listener for the input -> call ai4seo_init_generate_all_button() on input change
        let debounce_timer;
        $this_generate_data_for_input
            .off('input.ai4seo-generate-button-injection')
            .on('input.ai4seo-generate-button-injection', function() {
                clearTimeout(debounce_timer);
                debounce_timer = setTimeout(ai4seo_init_generate_all_button, 150);
            });

        // ai4seo_console_debug('AI for SEO: Found generate data for input match for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons().');

        // if metadata-identifier key is set -> check with ai4seo_get_active_meta_tags if this metadata is active
        // if attachment-attributes-identifier key is set -> check with ai4seo_get_active_attachment_attributes if this attribute is active
        if (typeof this_generate_data_for_input_details.metadata_identifier !== 'undefined' && this_generate_data_for_input_details.metadata_identifier) {
            if (!active_meta_tags.includes(this_generate_data_for_input_details.metadata_identifier)) {
                // ai4seo_console_debug('AI for SEO: Skipping generate button addition for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons() due to inactive meta tag "' + this_generate_data_for_input_details.metadata_identifier + '".');
                return;
            }
        }

        if (typeof this_generate_data_for_input_details.attachment_attributes_identifier !== 'undefined' && this_generate_data_for_input_details.attachment_attributes_identifier) {
            if (!active_attachment_attributes.includes(this_generate_data_for_input_details.attachment_attributes_identifier)) {
                // ai4seo_console_debug('AI for SEO: Skipping generate button addition for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons() due to inactive attachment attribute "' + this_generate_data_for_input_details.attachment_attributes_identifier + '".');
                return;
            }
        }

        // check for .add_generate_button = false, skip if set
        if (typeof this_generate_data_for_input_details.add_generate_button !== 'undefined' && this_generate_data_for_input_details.add_generate_button === false) {
            // ai4seo_console_debug('AI for SEO: Skipping generate button addition for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons() due to add_generate_button = false.');
            return;
        }

        // try to add generate button to input
        ai4seo_try_add_generate_button_to_input($this_generate_data_for_input, this_generate_data_for_input_selector);
    });
}

// =========================================================================================== \\

function ai4seo_is_attachment_post_type() {
    const $body = ai4seo_normalize_$('body');

    if (!ai4seo_exists_$($body)) {
        console.error('AI for SEO: element \"$body\" missing in ai4seo_is_attachment_post_type() \u2014 cannot detect attachment screen.');
        return false;
    }

    return $body.hasClass('post-type-attachment');
}

// =========================================================================================== \\

function ai4seo_is_attachment_mime_type_supported() {
    // Define boolean to determine whether supported mime-type has been found
    let has_supported_mime_type = false;

    // Loop through attachment-mime-type-selector-elements
    jQuery.each(ai4seo_attachment_mime_type_selectors, function(this_selector_key, this_selector) {
        // Make sure that mime-type-selector is jQuery-element
        const $this_mime_type_container = ai4seo_normalize_$(this_selector);

        if (!ai4seo_exists_$($this_mime_type_container)) {
            ai4seo_console_debug('AI for SEO: element \"$this_mime_type_container\" not found for selector \"' + this_selector + '\" in ai4seo_is_attachment_mime_type_supported() \u2014 skipping media support check for this selector.');
            return;
        }

        // Check if this selector-element exists on the current page
        // Get the content of the selector
        const mime_type_container_text = $this_mime_type_container.text();

        // Skip this entry if this selector doesn't have any content
        if (!mime_type_container_text) {
            return;
        }

        // Loop through ai4seo_supported_mime_types and check if mime-type exists in selector-content
        jQuery.each(ai4seo_supported_mime_types, function(this_mime_type_key, this_mime_type_value) {
            if (mime_type_container_text.indexOf(this_mime_type_value) > -1) {
                has_supported_mime_type = true;
            }
        });
    });

    return has_supported_mime_type;
}

// =========================================================================================== \\

function ai4seo_init_auto_resize_textareas() {
    const $textareas = ai4seo_normalize_$('.ai4seo-auto-resize-textarea');

    if (!ai4seo_exists_$($textareas)) {
        // ai4seo_console_debug('AI for SEO: elements \".ai4seo-auto-resize-textarea\" missing in ai4seo_init_auto_resize_textareas() \u2014 skipping auto-resize binding.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Initializing auto-resize for ' + $textareas.length + ' textareas in ai4seo_init_auto_resize_textareas().');

    $textareas.each(function() {
        const $this_textarea = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_textarea)) {
            console.error('AI for SEO: element \"$this_textarea\" missing in ai4seo_init_auto_resize_textareas() \u2014 skipping item.');
            return;
        }

        // bind input event to textarea
        $this_textarea.off('input.ai4seo-auto-resize');
        $this_textarea.on('input.ai4seo-auto-resize', function() {
            // reset height to auto to recalculate scrollHeight
            $this_textarea.css('height', 'auto');
            // set height to scrollHeight
            $this_textarea.css('height', ($this_textarea.prop('scrollHeight') + 2) + 'px');
        });

        // trigger input event to set initial height
        $this_textarea.triggerHandler('input.ai4seo-auto-resize');
    });
}

// =========================================================================================== \\

/**
 * Init all our tooltips on this page
 */
function ai4seo_init_tooltips() {
    if (typeof jQuery !== 'function') {
        return;
    }

    let $tooltip_holder = ai4seo_normalize_$('.ai4seo-tooltip-holder');
    let $tooltips = ai4seo_normalize_$('.ai4seo-tooltip');

    if (ai4seo_exists_$($tooltip_holder)) {
        // add tooltips functionality
        $tooltip_holder.on('mouseenter', function (event) {
            let $this_tooltip_child = jQuery(this).find('.ai4seo-tooltip');

            if (!ai4seo_exists_$($this_tooltip_child)) {
                console.warn('AI for SEO: element \"$this_tooltip_child\" missing in ai4seo_init_tooltips() — cannot prepare tooltip content.');
                return;
            }

            ai4seo_show_tooltip($this_tooltip_child, event);
        });

        $tooltip_holder.on('mouseleave', function () {
            let $this_tooltip = jQuery(this).find('.ai4seo-tooltip');

            if (!ai4seo_exists_$($this_tooltip)) {
                console.warn('AI for SEO: element \"$this_tooltip\" missing in ai4seo_init_tooltips() — cannot initialize tooltip content.');
                return;
            }

            $this_tooltip.fadeOut(200);
        });

        $tooltip_holder.on('click', function (event) {
            event.stopPropagation(); // Prevent the event from propagating to the document
            let $this_tooltip_child = jQuery(this).find('.ai4seo-tooltip');

            if (!ai4seo_exists_$($this_tooltip_child)) {
                console.warn('AI for SEO: element \"$this_tooltip_child\" missing in ai4seo_init_tooltips() \u2014 cannot prepare tooltip content.');
                return;
            }

            if (ai4seo_exists_$($tooltips)) {
                $tooltips.hide(); // Hide other tooltips
            }

            if ($this_tooltip_child.is(':visible')) {
                ai4seo_hide_tooltip($this_tooltip_child);
            } else {
                setTimeout(function () {
                    ai4seo_show_tooltip($this_tooltip_child, event);
                }, 1);
            }
        });
    }

    if (ai4seo_exists_$($tooltips)) {
        $tooltips.on('click', function (event) {
            // close tooltip upon click
            event.stopPropagation(); // Prevent the event from propagating to the document

            setTimeout(function () {
                $tooltips.hide(); // Hide all tooltips
            }, 2);
        });

        // Click event on the document to close all tooltips
        jQuery(document).on('click', function (event) {
            // close tooltip upon click
            event.stopPropagation(); // Prevent the event from propagating to the document

            setTimeout(function () {
                $tooltips.hide(); // Hide all tooltips
            }, 2);
        });
    }
}

// =========================================================================================== \\

/**
 * Init all our "ai4seo-countdown" elements
 */
function ai4seo_init_countdown_elements() {
    const $countdowns = ai4seo_normalize_$('.ai4seo-countdown');

    if (!ai4seo_exists_$($countdowns)) {
        //ai4seo_console_debug('AI for SEO: no \"$countdown\" elements found in ai4seo_init_countdown_elements() \u2014 no timers initialized.');
        return;
    }

    ai4seo_console_debug('AI for SEO: initializing ' + $countdowns.length + ' countdown timers in ai4seo_init_countdown_elements().');

    $countdowns.each(function() {
        ai4seo_init_countdown(this);
    });
}

// =========================================================================================== \\

/**
 * Apply a continuous countdown to the given element
 */
function ai4seo_init_countdown($countdown) {
    $countdown = ai4seo_normalize_$($countdown);

    if (!ai4seo_exists_$($countdown)) {
        console.warn('AI for SEO: element \"$countdown\" missing in ai4seo_init_countdown() \u2014 timer cannot start.');
        return;
    }

    // skip if element is already initialized
    if ($countdown.data('initialized')) {
        return;
    }

    // add class ai4seo-ignore-during-dashboard-refresh if not already set
    if (!$countdown.hasClass('ai4seo-ignore-during-dashboard-refresh')) {
        $countdown.addClass('ai4seo-ignore-during-dashboard-refresh');
    }

    // check if element has data-time-left attribute
    let total_seconds = $countdown.data('time-left');

    if (isNaN(total_seconds) || total_seconds <= 0) {
        return;
    }

    // get the time since page load in seconds and subtract it from total_seconds
    let time_since_page_load = Math.floor((Date.now() - window.ai4seo_page_load_time) / 1000);
    total_seconds -= time_since_page_load;

    let interval = setInterval(function () {
        total_seconds--;

        if (total_seconds <= 0) {
            clearInterval(interval);
            $countdown.text('00:00:00');
            time_since_page_load = Math.floor((Date.now() - window.ai4seo_page_load_time) / 1000);

            // only trigger the function if we are at least 10 seconds after page load
            if (time_since_page_load >= 10) {
                let trigger_function_name = $countdown.data('trigger');
                if (typeof window[trigger_function_name] === 'function') {
                    window[trigger_function_name]();
                }
            }
        } else if (total_seconds > 86400) { // More than 24 hours
            // format time as "X days hh:mm:ss"
            let time_str = ai4seo_format_time_with_days(total_seconds);
            $countdown.text(time_str);
        } else {
            // Format time as hh:mm:ss
            let time_str = ai4seo_format_time(total_seconds);
            $countdown.text(time_str);
        }
    }, 1000);

    // mark element as initialized
    $countdown.data('initialized', true);
}

// =========================================================================================== \\

/**
 * Format seconds into "X days and hh:mm:ss"
 */
function ai4seo_format_time_with_days(total_seconds) {
    let days = Math.floor(total_seconds / 86400); // 86400 = 24 * 60 * 60
    let remaining_seconds = total_seconds % 86400;

    let hours = Math.floor(remaining_seconds / 3600);
    let minutes = Math.floor((remaining_seconds % 3600) / 60);
    let seconds = remaining_seconds % 60;

    let time_str =
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');

    if (days > 0) {
        time_str = wp.i18n.sprintf(
            wp.i18n._n('%1$d day %2$s', '%1$d days %2$s', days, 'ai-for-seo'),
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
    const $select_all_checkboxes = ai4seo_normalize_$('.ai4seo-select-all-checkbox');

    if (!ai4seo_exists_$($select_all_checkboxes)) {
        //ai4seo_console_debug('AI for SEO: no select-all-checkbox elements found in ai4seo_init_select_all_checkboxes() \u2014 cannot manage bulk selection.');
        return;
    }

    ai4seo_console_debug('AI for SEO: initializing ' + $select_all_checkboxes.length + ' select-all-checkbox elements in ai4seo_init_select_all_checkboxes().');

    $select_all_checkboxes.each(function() {
        const $this_select_all_checkbox = ai4seo_normalize_$(this);

        const target_checkbox_name = $this_select_all_checkbox.data('target');

        // if no target-checkbox-name is set, then skip this element
        if (!target_checkbox_name) {
            console.warn('AI for SEO: No target-checkbox-name found for select-all-checkbox');
            return;
        }

        const $all_target_checkboxes = ai4seo_normalize_$("input[type='checkbox'][name='" + target_checkbox_name + "[]']:not(:disabled)");

        // if no target-checkbox-elements are found, then skip this element
        if (!ai4seo_exists_$($all_target_checkboxes)) {
            console.warn('AI for SEO: No target-checkbox-elements found for select-all-checkbox with target-checkbox-name: ' + target_checkbox_name);
            return;
        }

        // refresh the current state of the select all / unselect all checkbox
        ai4seo_refresh_select_all_checkbox_state($this_select_all_checkbox, $all_target_checkboxes);

        // add change event to all target-checkbox-elements
        $this_select_all_checkbox.off('change.ai4seo-checkboxes');
        $this_select_all_checkbox.on('change.ai4seo-checkboxes', function() {
            const $this_checkbox = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_checkbox)) {
                console.warn('AI for SEO: element \"$this_checkbox\" missing in ai4seo_init_select_all_checkboxes() \u2014 skipping item.');
                return;
            }

            // Get the checked status of the "Select All / Unselect All" checkbox
            const is_checked = $this_checkbox.prop('checked');

            // Get all checkboxes with the specified name and apply the checked status
            $all_target_checkboxes.prop('checked', is_checked).change();
        });

        // add change event to all target-checkbox-elements to refresh the state of the select all / unselect all checkbox
        $all_target_checkboxes.off('change.ai4seo-checkboxes');
        $all_target_checkboxes.on('change.ai4seo-checkboxes', function() {
            ai4seo_refresh_select_all_checkbox_state($this_select_all_checkbox, $all_target_checkboxes);
        });
    });
}

// =========================================================================================== \\

/**
 * Refresh the current state of the select all / unselect all checkbox
 */
function ai4seo_refresh_select_all_checkbox_state($select_all_checkbox, $all_target_checkboxes) {
    $select_all_checkbox = ai4seo_normalize_$($select_all_checkbox);
    $all_target_checkboxes = ai4seo_normalize_$($all_target_checkboxes);

    if (!ai4seo_exists_$($select_all_checkbox) || !ai4seo_exists_$($all_target_checkboxes)) {
        console.warn('AI for SEO: elements "$select_all_checkbox" or "$all_target_checkboxes" missing in ai4seo_refresh_select_all_checkbox_state() — cannot sync select-all state.');
        return;
    }

    // set the initial state of the select all checkbox
    const num_checked_target_checkboxes = parseInt($all_target_checkboxes.filter(':checked').length);
    const num_all_target_checkboxes = parseInt($all_target_checkboxes.length);

    // if there are more checked checkboxes, than unchecked checkboxes, then the "select all checkbox" is checked as well
    $select_all_checkbox.prop('checked', num_all_target_checkboxes === num_checked_target_checkboxes);
}

// =========================================================================================== \\

function ai4seo_init_checkbox_containers() {
    // class -> ai4seo-checkbox-container
    // add toggle effect for any checkboxes inside the container
    const $checkbox_containers = ai4seo_normalize_$('.ai4seo-checkbox-container');

    if (!ai4seo_exists_$($checkbox_containers)) {
        //ai4seo_console_debug('AI for SEO: no checkbox-containers found in ai4seo_init_checkbox_containers() \u2014 cannot initialize grouped toggles.');
        return;
    }

    ai4seo_console_debug('AI for SEO: initializing ' + $checkbox_containers.length + ' checkbox-containers in ai4seo_init_checkbox_containers().');

    $checkbox_containers.each(function() {
        const $this_container = ai4seo_normalize_$(this);
        const $this_container_checkboxes = $this_container.find('input[type="checkbox"]');

        if (!ai4seo_exists_$($this_container_checkboxes)) {
            console.warn('AI for SEO: elements \"$this_container_checkboxes\" missing in ai4seo_init_checkbox_containers() \u2014 cannot sync group state.');
            return;
        }

        // on click on the container, toggle it's checkboxes, but prevent the event from bubbling up to the parent container AND prevent a click on the checkbox to double toggle it
        $this_container.off('click.ai4seo-checkboxes');
        $this_container.on('click.ai4seo-checkboxes', function(event) {
            event.stopPropagation();
            $this_container_checkboxes.prop('checked', function(index, checked) {
                return !checked;
            });
        });

        // on click on the checkboxes, prevent the event from bubbling up to the parent container
        $this_container_checkboxes.off('click.ai4seo-checkboxes');
        $this_container_checkboxes.on('click.ai4seo-checkboxes', function(event) {
            event.stopPropagation();
        });
    });
}


// =========================================================================================== \\

function ai4seo_toggle_autopilot_remove_generated_data_section($clicked_button) {
    $clicked_button = ai4seo_normalize_$($clicked_button);

    if (!ai4seo_exists_$($clicked_button)) {
        console.warn('AI for SEO: element "$toggle_button" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — cannot toggle removal controls.');
        return;
    }

    const $generated_data_reminder_container = $clicked_button.closest('.ai4seo-generated-data-reminder-container');

    if (!ai4seo_exists_$($generated_data_reminder_container)) {
        console.warn('AI for SEO: element "$reminder" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — reminder container not found.');
        return;
    }

    const $autopilot_remove_generated_data_action_container = $generated_data_reminder_container.find('.ai4seo-remove-generated-data-action-container');

    if (!ai4seo_exists_$($autopilot_remove_generated_data_action_container)) {
        console.warn('AI for SEO: element "$action_container" missing in ai4seo_toggle_autopilot_remove_generated_data_section() — removal container not found.');
        return;
    }

    if ($autopilot_remove_generated_data_action_container.hasClass('ai4seo-display-none')) {
        $autopilot_remove_generated_data_action_container.removeClass('ai4seo-display-none');
        $clicked_button.attr('aria-expanded', 'true');

        const $remove_generated_data_button = $autopilot_remove_generated_data_action_container.find('.ai4seo-remove-generated-data-button');

        if (ai4seo_exists_$($remove_generated_data_button)) {
            $remove_generated_data_button.trigger('focus');
        }
    } else {
        $autopilot_remove_generated_data_action_container.addClass('ai4seo-display-none');
        $clicked_button.attr('aria-expanded', 'false');
    }
}

// =========================================================================================== \\

function ai4seo_confirm_autopilot_remove_generated_data() {
    const $autopilot_reset_generated_data_info_tooltip = ai4seo_normalize_$('#ai4seo-autopilot-reset-generated-data-info');
    let confirmation_message = '';

    if (ai4seo_exists_$($autopilot_reset_generated_data_info_tooltip)) {
        confirmation_message = $autopilot_reset_generated_data_info_tooltip.html() + '<br><br>';
    }

    confirmation_message += wp.i18n.__('Are you sure you want to remove all AI-generated data?', 'ai-for-seo');

    ai4seo_open_notification_modal(
        wp.i18n.__('Please confirm', 'ai-for-seo'),
        confirmation_message,
        "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('Abort', 'ai-for-seo') + "</button><button type='button' class='ai4seo-button ai4seo-secondary-button' onclick='ai4seo_remove_generated_data_via_autopilot(this);'>" + wp.i18n.__('Yes, remove AI-generated data', 'ai-for-seo') + '</button>',
        {close_on_backdrop_click: false}
    );
}

// =========================================================================================== \\

function ai4seo_remove_generated_data_via_autopilot() {
    ai4seo_close_notification_modal();

    const $autopilot_remove_generated_data_button = ai4seo_normalize_$('#ai4seo-autopilot-remove-generated-data-button');

    if (ai4seo_exists_$($autopilot_remove_generated_data_button)) {
        ai4seo_add_loading_html_to_element($autopilot_remove_generated_data_button);
    }

    ai4seo_lock_and_disable_lockable_input_fields();

    ai4seo_perform_ajax_call('ai4seo_reset_plugin_data', {ai4seo_reset_metadata: true})
        .then(response => { /* nothing */ })
        .catch(error => {
            ai4seo_show_generic_error_toast(512181225)
        })
        .finally(() => {
            ai4seo_safe_page_load();
        });
}

// =========================================================================================== \\


// ___________________________________________________________________________________________ \\
// === ELEMENTS ============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Function to show tooltip based on its position relative to the screen
function ai4seo_show_tooltip($tooltip, event) {
    $tooltip = ai4seo_normalize_$($tooltip);

    if (!ai4seo_exists_$($tooltip)) {
        console.warn('AI for SEO: element \"$tooltip\" missing in ai4seo_show_tooltip() \u2014 cannot display tooltip.');
        return;
    }

    const screen_width = jQuery(window).width();
    const screen_height = jQuery(window).height();
    const mouse_x = event.pageX;
    const mouse_y = event.pageY;
    const tooltip_width = $tooltip.outerWidth();
    const tooltip_height = $tooltip.outerHeight();
    const tooltip_half_width = tooltip_width / 2;
    const tooltip_top = mouse_y + 10; // 10px offset from mouse pointer
    const tooltip_bottom = screen_height - mouse_y + 10; // 10px offset from mouse pointer
    const vertical_buffer_zone = 30;
    const horizontal_buffer_zone = 30;
    const scroll_height = jQuery(window).scrollTop();
    const relative_mouse_y = mouse_y - scroll_height;
    const tooltip_buffer_zoned_half_width = tooltip_half_width + horizontal_buffer_zone;

    // Calculate left position ensuring tooltip doesn't go out of bounds
    let left_position = 0;

    // tooltip is overlapping with left screen border
    if (mouse_x - tooltip_half_width < 0) {
        left_position = tooltip_half_width - (mouse_x - horizontal_buffer_zone);

    // tooltip is overlapping with right screen border
    } else if (mouse_x + tooltip_half_width > screen_width) {
        left_position = -tooltip_half_width + (screen_width - mouse_x - horizontal_buffer_zone);
    }

    // check if ai4seo_tooltip is inside a modal (ai4seo-ajax-modal) -> apply workarounds
    const $closest_modal = $tooltip.closest('.ai4seo-modal');

    if (ai4seo_exists_$($closest_modal)) {
        // modal left position
        const modal_left_position = $closest_modal.offset().left;
        const modal_right_position = modal_left_position + $closest_modal.outerWidth();
        const modal_padding_left = parseInt($closest_modal.css('padding-left').replace('px', ''));
        const modal_padding_right = parseInt($closest_modal.css('padding-right').replace('px', ''));
        const mouse_distance_to_left_modal_border = mouse_x - modal_left_position;
        const mouse_distance_to_right_modal_border = modal_right_position - mouse_x;

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
        $tooltip.css({
            top: '100%',
            bottom: 'auto',
            left: left_position + 'px',
            marginTop: '10px',
            marginBottom: '0',
            transform: 'translateX(-50%)'
        });
        $tooltip.find('::after').css({
            top: '100%',
            bottom: 'auto',
            transform: 'translateX(-50%)'
        });
    } else {
        // tooltip is overlapping with bottom screen border or all other cases
        $tooltip.css({
            top: 'auto',
            bottom: '100%',
            left: left_position + 'px',
            marginBottom: '10px',
            marginTop: '0',
            transform: 'translateX(-50%)'
        });
        $tooltip.find('::after').css({
            top: 'auto',
            bottom: '100%',
            transform: 'translateX(-50%)'
        });
    }


    $tooltip.fadeIn(100);
}

// =========================================================================================== \\

function ai4seo_hide_tooltip($tooltip) {
    $tooltip = ai4seo_normalize_$($tooltip);

    if (!ai4seo_exists_$($tooltip)) {
        ai4seo_console_debug('AI for SEO: element \"$tooltip\" missing in ai4seo_hide_tooltip() \u2014 nothing to hide.');
        return;
    }

    $tooltip.fadeOut(100);
}


// ___________________________________________________________________________________________ \\
// === HELPER FUNCTIONS ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_input_value($input) {
    // Make sure that element can be found
    $input = ai4seo_normalize_$($input);

    if (!ai4seo_exists_$($input)) {
        console.warn('AI for SEO: element \"$input\" missing in ai4seo_get_input_value() \u2014 cannot read value.');
        return false;
    }

    // check if element is a single checkbox and class ai4seo-single-checkbox
    if ($input.is("input[type='checkbox']") && $input.length === 1 && $input.hasClass('ai4seo-single-checkbox')) {
        return $input.is(':checked');
    }

    // check if element is a group of checkboxes
    else if ($input.is("input[type='checkbox']")) {
        return $input.filter(':checked').map(function() {
            return jQuery(this).val();
        }).get();
    }

    // check if element is a group of radio buttons
    else if ($input.is("input[type='radio']")) {
        return $input.filter(':checked').val();
    }

    // Check if element is input-field (any other type than checkbox or radio)
    else if ($input.is('input')) {
        return $input.val();
    }

    // Check if element is textarea
    else if ($input.is('textarea')) {
        return $input.val();
    }

    // Check if element is select
    else if ($input.is('select')) {
        return $input.find('option').filter(':selected').val();
    }

    // check if element is a div or a span
    else if ($input.is('div') || $input.is('span')) {
        return $input.text();
    }

    // check if element is a paragraph
    else if ($input.is('p')) {
        return $input.text();
    }

    return $input.val();
}

// =========================================================================================== \\

function ai4seo_array_unique(array){
    return array.filter(function(el, index, arr) {
        return index === arr.indexOf(el);
    });
}

// =========================================================================================== \\

function ai4seo_normalize_$(mixed, context) {
    // check if it's already a jQuery object
    if (mixed instanceof jQuery) {
        return mixed;
    }

    if (!context) {
        context = window.parent.document;
    }

    return jQuery(mixed, context);
}

// =========================================================================================== \\

function ai4seo_exists_$(mixed, context) {
    const $element = ai4seo_normalize_$(mixed, context);

    // check if length is defined and if it's greater than 0
    return (typeof $element.length !== 'undefined' && $element.length > 0);
}

// =========================================================================================== \\

function ai4seo_get_post_id() {
    const $editor_context = ai4seo_get_editor_context_$();

    if (ai4seo_exists_$($editor_context)) {
        const $editor_modal_post_id_holder = ai4seo_normalize_$($editor_context.find('#ai4seo-editor-modal-post-id'));

        // first look for the post id in the ajax modal
        if (ai4seo_exists_$($editor_modal_post_id_holder)) {
            let post_id = $editor_modal_post_id_holder.val();

            if (post_id && !isNaN(post_id)) {
                return parseInt(post_id);
            }
        }
    }

    // Check if "media-modal"-element exists
    if (ai4seo_exists_$('.media-modal')) {
        // Read current url-parameters
        const current_url_parameters = new URLSearchParams(window.location.search);

        // Read item-parameter from current-url-parameters
        post_id = current_url_parameters.get('item');

        // Check if item-id could be found and is valid
        if (post_id && !isNaN(post_id)) {
            return parseInt(post_id);
        }

        // Get the first selected attachment
        const $selected_attachment_candidates = ai4seo_normalize_$('.attachments-wrapper .attachments .attachment.selected');

        if (ai4seo_exists_$($selected_attachment_candidates)) {
            const $selected_attachment = $selected_attachment_candidates.first();

            // Check if the selected attachment has a data-id attribute
            if ($selected_attachment.data('id')) {
                post_id = $selected_attachment.data('id');

                if (post_id && !isNaN(post_id)) {
                    return parseInt(post_id);
                }
            }
        }

        // If the post_id could not be read from the url of the page then try to access wp.media.frame
        else {
            // Access the wp.media frame
            const mediaFrame = wp.media.frame;

            // Check if the attachment-id exists within model.id
            if (mediaFrame.model && mediaFrame.model.id) {
                post_id = mediaFrame.model.id;
                if (post_id && !isNaN(post_id)) {
                    return parseInt(post_id);
                }
            }
        }
    }

    // Gutenberg: selected image in the editor
    // check if wp.data can be accessed
    do if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
        const {select} = wp.data;

        // check if we can call getSelectedBlock()
        if (typeof select('core/block-editor') === 'undefined' || typeof select('core/block-editor').getSelectedBlock !== 'function') {
            break;
        }

        // Get the currently selected block
        const selected_block = select('core/block-editor').getSelectedBlock();

        // check if we have a selected_block and have access to its attributes
        if (!selected_block || typeof selected_block.attributes === 'undefined') {
            break;
        }

        // check for mediaId
        if (typeof selected_block.attributes.mediaId !== 'undefined') {
            post_id = selected_block.attributes.mediaId;

            if (post_id && !isNaN(post_id)) {
                return parseInt(post_id);
            }
        }

        // check for id
        if (typeof selected_block.attributes.id !== 'undefined') {
            post_id = selected_block.attributes.id;

            if (post_id && !isNaN(post_id)) {
                return parseInt(post_id);
            }
        }
    } while (false);

    // then look for the post-id in the localized object -> check last as it can sometimes have invalid information
    post_id = ai4seo_get_localization_parameter('ai4seo_current_post_id');

    // Make sure that post_id could be found and is a number
    if (post_id && !isNaN(post_id) && parseInt(post_id) > 0) {
        return parseInt(post_id);
    }

    return false;
}

// =========================================================================================== \\

function ai4seo_get_plugin_version_number() {
    return ai4seo_get_localization_parameter('ai4seo_plugin_version_number');
}

// =========================================================================================== \\

function ai4seo_get_admin_ajax_url() {
    return (typeof window !== 'undefined' && window.ajaxurl) ||
        ai4seo_get_localization_parameter('ai4seo_admin_ajax_url') ||
        (ai4seo_get_localization_parameter('ai4seo_admin_url') + 'admin-ajax.php') ||
        '/wp-admin/admin-ajax.php';
}

// =========================================================================================== \\

function ai4seo_get_metadata_price_table() {
    return ai4seo_get_localization_parameter('ai4seo_metadata_price_table');
}

// =========================================================================================== \\

function ai4seo_get_attachment_attributes_price_table() {
    return ai4seo_get_localization_parameter('ai4seo_attachment_attributes_price_table');
}

// =========================================================================================== \\

function ai4seo_get_seconds_since_page_load() {
    // Check if ai4seo_page_load_time is defined
    if (typeof window.ai4seo_page_load_time === 'undefined') {
        return 0;
    }

    // Calculate the difference in seconds
    const current_time = Date.now();
    const time_difference = current_time - window.ai4seo_page_load_time;

    // Convert milliseconds to seconds
    return Math.floor(time_difference / 1000);
}

// =========================================================================================== \\

function ai4seo_compare_version(v1, v2, operator) {
    const normalize = (version) =>
        version
            .replace(/[^0-9a-z.+-]/gi, '')
            .split('.')
            .map((v) => (isNaN(v) ? v : parseInt(v)));

    const compareParts = (a, b) => {
        const len = Math.max(a.length, b.length);
        for (let i = 0; i < len; i++) {
            const partA = a[i] ?? 0;
            const partB = b[i] ?? 0;

            if (typeof partA === 'string' || typeof partB === 'string') {
                const sA = String(partA);
                const sB = String(partB);
                if (sA > sB) return 1;
                if (sA < sB) return -1;
            } else {
                if (partA > partB) return 1;
                if (partA < partB) return -1;
            }
        }
        return 0;
    };

    const result = compareParts(normalize(v1), normalize(v2));

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

function ai4seo_get_admin_scripts_version_number() {
    return ai4seo_get_localization_parameter('ai4seo_admin_scripts_version_number');
}

// =========================================================================================== \\

function ai4seo_get_asset_url(sub_path) {
    return ai4seo_get_localization_parameter('ai4seo_assets_directory_url') + '/' + sub_path;
}

// =========================================================================================== \\

function ai4seo_get_localization_parameter(parameter_name) {
    // Check if ai4seo_localization exists
    if (typeof ai4seo_localization === 'undefined') {
        console.error('AI for SEO: No localization object found!');
        return false;
    }

    // Check if parameter_name exists in ai4seo_localization
    if (typeof ai4seo_localization[parameter_name] === 'undefined') {
        console.warn('AI for SEO: No localization parameter found for: ' + parameter_name);
        return false;
    }

    return ai4seo_localization[parameter_name];
}

// =========================================================================================== \\

function ai4seo_normalize_length(length_value, default_value) {
    const parsed_value = parseInt(length_value, 10);

    if (!isNaN(parsed_value) && parsed_value > 0) {
        return parsed_value;
    }

    return default_value;
}

// =========================================================================================== \\

function ai4seo_resolve_length_limit(length_key, length_map, fallback_length) {
    const normalized_key = (typeof length_key === 'string') ? length_key.toLowerCase() : '';

    if (normalized_key && length_map && Object.prototype.hasOwnProperty.call(length_map, normalized_key)) {
        return ai4seo_normalize_length(length_map[normalized_key], fallback_length);
    }

    return fallback_length;
}

// =========================================================================================== \\

/**
 * Checks whether a value respects the configured character limit for the given identifier.
 */
function ai4seo_validate_editor_input_length(value, identifier, length_map, fallback_length, field_label, error_code) {
    const max_length = ai4seo_resolve_length_limit(identifier, length_map, fallback_length);

    if (value.length > max_length) {
        const safe_field_label = field_label || wp.i18n.__('This field', 'ai-for-seo');
        const error_message = wp.i18n.sprintf(
            wp.i18n.__('%1$s cannot exceed %2$d characters.', 'ai-for-seo'),
            safe_field_label,
            max_length
        );

        ai4seo_show_error_toast(error_code, error_message);
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_get_full_domain() {
    // try check ai4seo_site_url
    ai4seo_site_url = ai4seo_get_localization_parameter('ai4seo_site_url');

    // Check if ai4seo_localization.ai4seo_site_url exists
    if (ai4seo_site_url) {
        return ai4seo_site_url;
    }

    // fallback to window.location
    let protocol = window.location.protocol;
    let host = window.location.host;
    return protocol + '//' + host;
}

// =========================================================================================== \\

function ai4seo_get_ai4seo_plugin_directory_url() {
    return ai4seo_get_localization_parameter('ai4seo_plugin_directory_url');
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
    return navigator.userAgent.indexOf('Chrome') !== -1;
}

// =========================================================================================== \\

function ai4seo_build_custom_admin_url(subpage = '', additional_url_parameters = {}) {
    let admin_url = ai4seo_get_localization_parameter('ai4seo_admin_url');

    // not subpage given -> read from localization
    if (!subpage) {
        subpage = ai4seo_get_localization_parameter('ai4seo_active_subpage');
    }

    // fallback to dashboard
    if (!subpage) {
        subpage = 'dashboard';
    }

    if (!additional_url_parameters || typeof additional_url_parameters !== 'object') {
        additional_url_parameters = {};
    }

    const has_subpage_parameter = Object.prototype.hasOwnProperty.call(additional_url_parameters, 'ai4seo_subpage');

    if (!has_subpage_parameter || !additional_url_parameters.ai4seo_subpage) {
        additional_url_parameters.ai4seo_subpage = subpage;
    }

    additional_url_parameters.page = 'ai-for-seo';
    
    // go through all additional parameters and add them to the url
    for (const [key, value] of Object.entries(additional_url_parameters)) {
        admin_url = ai4seo_add_or_modify_url_parameter(admin_url, key, value);
    }
    
    return admin_url;
}

// =========================================================================================== \\

function ai4seo_add_or_modify_url_parameter(url, parmeter_name, parameter_value) {
    let url_object = new URL(url);
    let search_params = url_object.searchParams;

    // Set or update the parameter
    search_params.set(parmeter_name, parameter_value);

    // Return the modified URL as a string
    return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_remove_url_parameter(url, parameter_name) {
    let url_object = new URL(url);
    let search_params = url_object.searchParams;

    // Remove the parameter
    search_params.delete(parameter_name);

    // Return the modified URL as a string
    return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_clean_url_parameter(url, keep_page = true, keep_ai4seo_subpage = false, keep_ai4seo_post_type = false) {
    let url_object = new URL(url);
    let search_params = url_object.searchParams;

    // Remove all ai4seo_-parameters except the ones we want to keep
    search_params.forEach((value, key) => {
        if (key.startsWith('ai4seo_')) {
            if ((key === 'ai4seo_subpage' && keep_ai4seo_subpage) ||
                (key === 'ai4seo_post_type' && keep_ai4seo_post_type)) {
                return; // Skip removal for this parameter
            }
            search_params.delete(key);
        }

        // Remove page parameter if not requested to keep
        if (key === 'page' && !keep_page) {
            search_params.delete(key);
        }
    });

    // Return the modified URL as a string
    return url_object.toString();
}

// =========================================================================================== \\

function ai4seo_is_yoast_element($yoast_candidate) {
    // Define variable for selector
    $yoast_candidate = ai4seo_normalize_$($yoast_candidate);

    // Check if element is found
    if (!ai4seo_exists_$($yoast_candidate)) {
        ai4seo_console_debug('AI for SEO: element \"$yoast_candidate\" missing in ai4seo_is_yoast_element() \u2014 cannot resolve SEO field.');
        return false;
    }

    // Check if element is a yoast-element
    const $yoast_input_editor = ai4seo_normalize_$($yoast_candidate.closest('.yst-replacevar__editor'));

    if (!ai4seo_exists_$($yoast_input_editor)) {
        //ai4seo_console_debug('AI for SEO: element \"$yoast_candidate.closest(\".yst-replacevar__editor\")\" missing in ai4seo_is_yoast_element() \u2014 cannot resolve SEO field.');
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_console_debug(...args) {
    if (typeof ai4seo_output_console_debug !== 'undefined' && ai4seo_output_console_debug === true) {
        console.debug(...args);
    }
}

// ___________________________________________________________________________________________ \\
// === AI GENERATION ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Function to make an ajax call to generate-metadata.php to get the post details
function ai4seo_generate_with_ai($submit_button, ajax_action, generate_data_for_input_instructions = [], post_id = false, overwrite_data = true, try_read_page_content_via_js = false ) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        ai4seo_show_generic_error_toast(1112301025);
        console.error('AI for SEO: No valid submit_button defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.');
        return;
    }

    if (!ajax_action) {
        ai4seo_show_generic_error_toast(4310301025);
        console.error('AI for SEO: No ajax_action defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.');
        return;
    }

    // Read post-id from hidden container if not defined
    if (!post_id) {
        post_id = ai4seo_get_post_id();
    }

    if (!post_id || isNaN(post_id)) {
        ai4seo_show_generic_error_toast(132120824);
        console.error('AI for SEO: No valid post_id defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.');
        return;
    }

    if (!generate_data_for_input_instructions || typeof generate_data_for_input_instructions !== 'object') {
        ai4seo_show_generic_error_toast(4410301025);
        console.error('AI for SEO: No proper generate_data_for_selectors_by_generation_field_identifier defined in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.');
        return;
    }

    // collect data
    let ajax_data = {
        ai4seo_post_id: post_id,
    };

    // check for Divi Builder placeholder -> dont read from this page
    if (ai4seo_exists_$('.wp-block-divi-placeholder')) {
        try_read_page_content_via_js = false;
    }

    // check if we should try to read the page content via js
    if (try_read_page_content_via_js) {
        // Define variable for the content based on ai4seo_get_post_content()
        // add content as ai4seo_content to data
        ajax_data.ai4seo_content = ai4seo_get_post_content();
    }

    // generate_data_for_selectors_by_generation_field_identifier can be {'{{field_identifier}}': {}, ...}, if value is an empty object, try to populate with suitable selectors
    generate_data_for_input_instructions = ai4seo_get_normalized_generation_fields(generate_data_for_input_instructions, overwrite_data);

    if (!generate_data_for_input_instructions
        || typeof generate_data_for_input_instructions !== 'object'
        || Object.keys(generate_data_for_input_instructions).length === 0) {
        ai4seo_show_warning_toast(wp.i18n.__('Could not find any suitable fields to generate data for.', 'ai-for-seo'));
        console.warn('AI for SEO: No suitable generate_data_for_selectors_by_generation_field_identifier found in ai4seo_generate_with_ai() \u2014 cannot perform AI generation.');
        return;
    }

    ajax_data.ai4seo_generation_fields = Object.keys(generate_data_for_input_instructions);

    // collect affected generate buttons and old input values
    let affected_generate_buttons = ai4seo_collect_generate_data_for_inputs_generate_buttons(generate_data_for_input_instructions);
    let old_input_values = ai4seo_collect_generate_data_for_inputs_old_input_values();

    if (old_input_values) {
        ajax_data.ai4seo_old_input_values = old_input_values;
    }

    ai4seo_lock_and_disable_lockable_input_fields();

    // Replace button-label with loading-html
    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_add_loading_html_to_element(affected_generate_buttons);

    // debug ajax data
    ai4seo_console_debug(ajax_data);

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Generating content with AI now...', 'ai-for-seo'));

    // call desired ajax action
    ai4seo_perform_ajax_call(ajax_action, ajax_data)
        .then(response => {
            // debug response
            ai4seo_console_debug(response);

            // check for response.generated_data
            if (!response.generated_data || typeof response.generated_data !== 'object') {
                ai4seo_show_error_toast(4410301027, wp.i18n.__('No generated data received from the server.', 'ai-for-seo'));
                console.error('AI for SEO: No generated_data received in ai4seo_generate_with_ai() \u2014 cannot fill generated data into inputs.');
                return;
            }

            if (typeof response.new_credits_balance === 'number') {
                ai4seo_remaining_credits = response.new_credits_balance;
            }

            let credits_consumed = 0;

            if (typeof response.credits_consumed === 'number') {
                credits_consumed = response.credits_consumed;
            }

            // go through the selector mapping and fill the values
            ai4seo_fill_generated_data_into_inputs(response.generated_data || {}, generate_data_for_input_instructions);

            // build success toast
            const generated_field_identifiers = Object.keys(response.generated_data);
            const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names(generated_field_identifiers);
            const success_toast_message = wp.i18n.sprintf(
                wp.i18n.__('Successfully generated %1s. Consumed: %2s. Remaining: %3s.', 'ai-for-seo'),
                human_readable_generated_field_identifiers.join(', '),
                '<span class="ai4seo-credits-usage-badge">' + credits_consumed + '</span>',
                '<span class="ai4seo-credits-usage-badge">' + ai4seo_remaining_credits + '</span>'
            );

            ai4seo_show_success_toast(success_toast_message);
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(612181225);
        })
        .finally(() => {
            // Remove loading-html from button-label
            ai4seo_remove_loading_html_from_element($submit_button);
            ai4seo_remove_loading_html_from_element(affected_generate_buttons);
            ai4seo_unlock_and_enable_lockable_input_fields();
            ai4seo_init_generate_all_button();
        });
}

// =========================================================================================== \\

function ai4seo_collect_generate_data_for_inputs_generate_buttons(generate_data_for_input_instructions) {
    let affected_generate_buttons = [];

    // go through each ai4seo_generation_fields and collect affected generate buttons
    jQuery.each(generate_data_for_input_instructions, function(this_generation_field_identifier, this_generation_field_details) {
        if (typeof this_generation_field_details !== 'object' || typeof this_generation_field_details.value !== 'string') {
            console.warn('AI for SEO: no value defined for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping current value collection.');
        }

        // go through each selector and find the generate button for it
        jQuery.each(this_generation_field_details.selectors, function(_, this_generate_data_for_selector) {
            const this_generate_data_for_input_details = ai4seo_generate_data_for_inputs[this_generate_data_for_selector];

            // skip if no generate button is defined for this selector
            if (!this_generate_data_for_input_details) {
                console.error('AI for SEO: no generate data for input details found for selector: ' + this_generate_data_for_selector + ' \u2014 cannot find generate button for this selector.');
                return;
            }

            // no generate button for this selector expected
            if (!this_generate_data_for_input_details.add_generate_button) {
                return;
            }

            const $this_generate_data_for_input = ai4seo_normalize_$(this_generate_data_for_selector);

            if (!ai4seo_exists_$($this_generate_data_for_input)) {
                console.warn('AI for SEO: element \"$this_generate_data_for_input\" missing in ai4seo_generate_with_ai() \u2014 cannot read/generate data for this selector.' );
                return;
            }

            const $this_possible_generate_button = ai4seo_try_find_generate_button_by_input_$($this_generate_data_for_input);

            if (!ai4seo_exists_$($this_possible_generate_button)) {
                console.warn('AI for SEO: element \"$this_possible_generate_button\" missing in ai4seo_generate_with_ai() \u2014 cannot add loading state to generate button for selector ' + this_generate_data_for_selector + '.' );
                return;
            }

            affected_generate_buttons.push($this_possible_generate_button);
        });
    });

    return affected_generate_buttons;
}

// =========================================================================================== \\

function ai4seo_collect_generate_data_for_inputs_old_input_values() {
    let existing_field_values = {};

    jQuery.each(ai4seo_generate_data_for_inputs, function(this_generate_data_for_input_selector, this_generate_data_for_input_details) {
        // get identifier
        let this_identifier = '';

        if (typeof this_generate_data_for_input_details.metadata_identifier !== 'undefined' && this_generate_data_for_input_details.metadata_identifier) {
            this_identifier = this_generate_data_for_input_details.metadata_identifier;
        } else if (typeof this_generate_data_for_input_details.attachment_attributes_identifier !== 'undefined' && this_generate_data_for_input_details.attachment_attributes_identifier) {
            this_identifier = this_generate_data_for_input_details.attachment_attributes_identifier;
        } else {
            console.warn('AI for SEO: no identifier defined for generate data for input selector: ' + this_generate_data_for_input_selector + ' \u2014 cannot try to fetch existing field values.');
            return;
        }

        // check if we already have a value for this identifier
        if (typeof existing_field_values[this_identifier] !== 'undefined' && existing_field_values[this_identifier]) {
            return; // continue to next iteration
        }

        let $this_generate_data_for_input = ai4seo_normalize_$(this_generate_data_for_input_selector);

        // keep selector intact
        // check if a jquery element exists for the selector
        if (!ai4seo_exists_$($this_generate_data_for_input)) {
            // ai4seo_console_debug('AI for SEO: No generate data for input match found for selector "' + this_generate_data_for_input_selector + '" in ai4seo_init_generate_buttons() \u2014 skipping generate button addition.');
            return;
        }

        const this_value_candidate = ai4seo_get_input_value($this_generate_data_for_input);

        if (!this_value_candidate || typeof this_value_candidate !== 'string' || this_value_candidate.length === 0) {
            return;
        }

        // workaround for RankMath values being stored as JSON strings
        // check if value has format '[{"value":"Wasser auf Stein: Geduld und Kraft der Zeit"}]' -> unwrap it
        if (this_value_candidate.startsWith('[{"value":"') && this_value_candidate.endsWith('"}]')) {
            try {
                const parsed_value = JSON.parse(this_value_candidate);
                if (Array.isArray(parsed_value) && parsed_value.length > 0 && typeof parsed_value[0].value === 'string') {
                    existing_field_values[this_identifier] = parsed_value[0].value;
                    return;
                }
            } catch (e) {
                // do nothing, use original value
            }
        }

        existing_field_values[this_identifier] = this_value_candidate;
    });

    return existing_field_values;
}

// =========================================================================================== \\

function ai4seo_get_human_readable_generation_field_names(generated_field_identifiers) {
    let human_readable_generation_fields = [];

    // use ai4seo_metadata_labels and ai4seo_attachment_attributes_labels to get human readable field names
    jQuery.each(generated_field_identifiers, function(this_index, this_generated_field_identifier) {
        let human_readable_field_name = this_generated_field_identifier;

        // check in ai4seo_metadata_labels (identifier: label)
        if (typeof ai4seo_metadata_labels[this_generated_field_identifier] === 'string') {
            human_readable_field_name = ai4seo_metadata_labels[this_generated_field_identifier];
        } else if (typeof ai4seo_attachment_attribute_labels[this_generated_field_identifier] === 'string') {
            human_readable_field_name = ai4seo_attachment_attribute_labels[this_generated_field_identifier];
        }

        human_readable_generation_fields.push(human_readable_field_name);
    });

    return human_readable_generation_fields;
}

// =========================================================================================== \\

function ai4seo_get_normalized_generation_fields(generate_data_for_input_instructions, overwrite_data = true) {
    let normalized_generate_data_for_input_instructions = {};

    if (!generate_data_for_input_instructions || typeof generate_data_for_input_instructions !== 'object' || Object.keys(generate_data_for_input_instructions).length === 0) {
        return normalized_generate_data_for_input_instructions;
    }

    // 0. NORMALIZE INPUT
    // generation_field_selectors can be {'{{field_identifier}}': {}, ...}, if value is an empty object, try to populate with suitable selectors
    // find all keys with empty object as value, then go through ai4seo_generate_data_for_inputs and collect suitable selectors
    jQuery.each(generate_data_for_input_instructions, function(this_generation_field_identifier, this_generate_data_for_input_instruction) {
        let this_credits_cost = 1;

        // handle case where field_identifier is numeric (array instead of object), e.g. [ '{{field_identifier}}', ... ]
        // if field_identifier is numeric, assume "selectors" as the field_identifier and selectors as an empty object
        if (!isNaN(this_generation_field_identifier)) {
            this_generation_field_identifier = this_generate_data_for_input_instruction;
            this_credits_cost = ai4seo_get_generation_field_credits_cost(this_generation_field_identifier);
            this_generate_data_for_input_instruction = {'selectors': [], 'value': '', 'credits': this_credits_cost};
        }

        this_credits_cost = ai4seo_get_generation_field_credits_cost(this_generation_field_identifier);

        // check if this_generate_data_for_input_instruction is just an array of selectors (strings)
        if (typeof this_generate_data_for_input_instruction === 'object' && Array.isArray(this_generate_data_for_input_instruction)) {
            this_generate_data_for_input_instruction = {'selectors': this_generate_data_for_input_instruction, 'value': '', 'credits': this_credits_cost};
        }

        normalized_generate_data_for_input_instructions[this_generation_field_identifier] = this_generate_data_for_input_instruction;
    });

    // 1. POPULATE WITH SUITABLE SELECTORS
    // go through ai4seo_generate_data_for_inputs and collect suitable selectors
    jQuery.each(normalized_generate_data_for_input_instructions, function(this_generation_field_identifier, this_generate_data_for_input_instruction) {
        jQuery.each(ai4seo_generate_data_for_inputs, function (this_generate_data_for_input_selector, generate_data_for_input_details) {
            // if generation_field_details.metadata_identifier or generation_field_details.attachment_attributes_identifier matches field_identifier, add selector to generation_field_selectors
            if (generate_data_for_input_details.metadata_identifier === this_generation_field_identifier ||
                generate_data_for_input_details.attachment_attributes_identifier === this_generation_field_identifier) {

                // add selector to generation_field_selectors
                if (!normalized_generate_data_for_input_instructions[this_generation_field_identifier]) {
                    let this_credits_cost = ai4seo_get_generation_field_credits_cost(this_generation_field_identifier);

                    normalized_generate_data_for_input_instructions[this_generation_field_identifier] = {
                        'selectors': [],
                        'value': '',
                        'credits': this_credits_cost
                    };
                }

                normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].push(this_generate_data_for_input_selector);
            }
        });
    });

    // 2. DISCARD UNSUITABLE SELECTORS & COLLECT VALUES
    // go through normalized_generate_data_for_selectors_by_generation_field_identifier and remove selectors that could not be found on the page
    // if found and overwrite_data is false, check if the current value is empty, if not, remove the selector from the list
    jQuery.each(normalized_generate_data_for_input_instructions, function(this_generation_field_identifier, generation_field_details) {
        let this_generate_data_for_input_selectors = generation_field_details.selectors;

        // remove empty generation fields, if there are no suitable selectors found
        if (!Array.isArray(this_generate_data_for_input_selectors) || this_generate_data_for_input_selectors.length === 0) {
            delete normalized_generate_data_for_input_instructions[this_generation_field_identifier];
            return;
        }

        let this_credits_cost = ai4seo_get_generation_field_credits_cost(this_generation_field_identifier);

        jQuery.each([...this_generate_data_for_input_selectors], function(_, this_generate_data_for_input_selector) {
            const this_index = normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].indexOf(this_generate_data_for_input_selector);

            if (this_index <= -1) {
                console.warn('AI for SEO: element \"$this_generate_data_for_input\" missing in ai4seo_generate_with_ai() \u2014 cannot read/generate data for this selector.' );
                return;
            }

            // get value of the selector
            const $this_generate_data_for_input = ai4seo_normalize_$(this_generate_data_for_input_selector);

            if (!ai4seo_exists_$($this_generate_data_for_input)) {
                normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].splice(this_index, 1);
                return;
            }

            let this_current_value = ai4seo_get_input_value($this_generate_data_for_input);

            // workaround for yoast placeholders, remove 'Title', 'Page', 'Separator', 'Site title'
            // alle Platzhalter entfernen
            this_current_value = this_current_value
                .replace(/Site\stitle/gi, '')
                .replace(/Title/gi, '')
                .replace(/Page/gi, '')
                .replace(/Separator/gi, '')
                .trim();

            if (!overwrite_data && this_current_value && this_current_value.toString().trim() !== '') {
                normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'].splice(this_index, 1);
                return;
            }

            // set current value as a field in the object
            normalized_generate_data_for_input_instructions[this_generation_field_identifier]['value'] = this_current_value;

            normalized_generate_data_for_input_instructions[this_generation_field_identifier]['credits'] = this_credits_cost;
        });
    });

    // 3. REMOVE EMPTY GENERATION FIELDS
    jQuery.each({...normalized_generate_data_for_input_instructions}, function(this_generation_field_identifier, generation_field_details) {
        let this_generate_data_for_input_selectors = generation_field_details.selectors;

        // remove empty generation fields, if there are no suitable selectors found
        if (!Array.isArray(this_generate_data_for_input_selectors) || this_generate_data_for_input_selectors.length === 0) {
            delete normalized_generate_data_for_input_instructions[this_generation_field_identifier];
        }
    });

    // 4. UNIQUE VALUES FOR SELECTORS
    jQuery.each(normalized_generate_data_for_input_instructions, function(this_generation_field_identifier, generation_field_details) {
        let this_generate_data_for_input_selectors = generation_field_details.selectors;

        // make selectors unique
        normalized_generate_data_for_input_instructions[this_generation_field_identifier]['selectors'] = [...new Set(this_generate_data_for_input_selectors)];

    });

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

// Function to go through the selector mapping and fill the values
function ai4seo_fill_generated_data_into_inputs(generated_data = {}, generate_data_for_input_instructions) {
    // go through each generation_fields (field_identifier -> {selectors: [], value: 'xxx') and fill the values into the inputs
    jQuery.each(generate_data_for_input_instructions, function(this_generation_field_identifier, this_generate_data_for_input_instruction) {
        let this_generation_data_for_input_value = this_generate_data_for_input_instruction.value || '';
        let this_generation_data_for_input_selectors = this_generate_data_for_input_instruction.selectors || [];

        if (this_generation_data_for_input_selectors.length <= 0) {
            console.error('AI for SEO: No selectors defined for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping filling generated data into inputs.');
            return;
        }

        let this_generated_data = generated_data[this_generation_field_identifier] || '';

        if (!this_generated_data || this_generated_data.toString().trim() === '') {
            console.warn('AI for SEO: No generated data found for generation field identifier: ' + this_generation_field_identifier + ' \u2014 skipping filling generated data into inputs.');
            return;
        }

        // go through each selector and call ai4seo_fill_text( this_input_selector, this_new_value, this_applicable_input_details );
        jQuery.each(this_generation_data_for_input_selectors, function(_, this_generate_data_for_input_selector) {
            let this_generate_data_for_input_details = ai4seo_generate_data_for_inputs[this_generate_data_for_input_selector];

            if (!this_generate_data_for_input_details) {
                console.error('AI for SEO: No applicable input details found for selector: ' + this_generate_data_for_input_selector + ' \u2014 skipping filling generated data into input.');
                return;
            }

            ai4seo_fill_text( this_generate_data_for_input_selector, this_generated_data, this_generate_data_for_input_details );
        });
    });
}

// =========================================================================================== \\

// Function to fill the text with the element selected by the selector with the value
// the element can be a text field or a text area or a div
function ai4seo_fill_text( generate_data_for_input_selector, generated_data, generate_data_for_input_details = {}) {
    const $generate_data_for_input = ai4seo_normalize_$(generate_data_for_input_selector);

    if (!ai4seo_exists_$($generate_data_for_input)) {
        console.warn('AI for SEO: selector input_selector -> no match in ai4seo_fill_text() \u2014 cannot inject generated text.');
        return;
    }

    const is_yoast = ai4seo_is_yoast_element($generate_data_for_input);
    const use_exec_command_workaround = (typeof generate_data_for_input_details.use_exec_command_workaround !== 'undefined' && generate_data_for_input_details.use_exec_command_workaround === true);

    if ($generate_data_for_input.is('input')) {
        $generate_data_for_input.val(generated_data).keypress().keyup().change();
    } else if ($generate_data_for_input.is('textarea')) {
        $generate_data_for_input.val(generated_data).keypress().keyup().change();
    } else if (is_yoast && ($generate_data_for_input.is('div') || $generate_data_for_input.is('span') || $generate_data_for_input.is('p'))) {
        const text_length = $generate_data_for_input.text().length;

        if (generate_data_for_input_details.key_by_key && text_length > 0) {
            ai4seo_add_text_to_yoast_editor_key_by_key($generate_data_for_input, generated_data);
        } else {
            ai4seo_set_yoast_input_content($generate_data_for_input, generated_data);
        }
    }

    // workaround for some inputs to trigger change event properly
    if (use_exec_command_workaround) {
        $generate_data_for_input.focus();
        document.execCommand('insertText', false, '.');
        document.execCommand('delete');
    }

    // refreshes the yoast progress bar if this is a yoast element
    if (is_yoast) {
        ai4seo_try_refresh_yoast_progress_bar($generate_data_for_input);
    }

    ai4seo_console_debug('AI for SEO: Filled generated data "' + generated_data + '" into input selector "' + generate_data_for_input_selector + '".');

    // Rank Math -> update rankMath Parameter
    /*if (typeof generate_data_for_input_details.metadata_identifier !== 'undefined' && generate_data_for_input_details.metadata_identifier) {
        ai4seo_set_rank_math_serp_data(generate_data_for_input_details.metadata_identifier, generated_data);
    }*/
}

// =========================================================================================== \\

function ai4seo_set_rank_math_serp_data(field_identifier, value) {
    if(typeof rankMath === 'undefined' || typeof rankMath.assessor === 'undefined' || typeof rankMath.assessor.serpData === 'undefined') {
        return;
    }

    switch (field_identifier) {
        case 'meta-title':
            rankMath.assessor.serpData.title = value;
            break;

        case 'meta-description':
            rankMath.assessor.serpData.description = value;
            alert(rankMath.assessor.serpData.description);
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

function ai4seo_try_refresh_yoast_progress_bar( $input ) {
    $input = ai4seo_normalize_$($input);

    if (!ai4seo_exists_$($input)) {
        console.warn('AI for SEO: element \"$input\" missing in ai4seo_fill_text_for_yoast() \u2014 cannot inject generated text.');
        return;
    }

    // Call function to set progress bar to success
    // Define variable for the parent-element with class "yst-replacevar"
    const $yoast_input_container = $input.closest('.yst-replacevar');

    // check if this is actually an yoast-element
    if (!ai4seo_exists_$($yoast_input_container)) {
        ai4seo_console_debug('AI for SEO: input is not a Yoast element in ai4seo_fill_text_for_yoast() \u2014 skipping.');
        return;
    }

    // Define variable for the progress-bar-element
    const $yoast_progress_bar = $yoast_input_container.next('progress');

    // Make sure that progress-bar-element exists
    if (!ai4seo_exists_$($yoast_progress_bar)) {
        ai4seo_console_debug('AI for SEO: element \"$yoast_progress_bar\" missing in ai4seo_refresh_yoast_progress_bar() \u2014 cannot mark completion.');
        return;
    }

    // Read max-value of progress-bar-element
    const max_value = $yoast_progress_bar.attr('max');

    // Add success-class to progress-bar-element
    $yoast_progress_bar.addClass('ai4seo-progress-success');

    // Set progress-bar-value to max-value
    $yoast_progress_bar.attr('value', max_value);
}

// =========================================================================================== \\

function ai4seo_set_yoast_input_content( $input, value ) {
    $input = ai4seo_normalize_$($input);

    if (!ai4seo_exists_$($input)) {
        console.error('AI for SEO: element \"$input\" missing in ai4seo_set_yoast_input_content() \u2014 cannot update metadata.');
        return
    }

    const data_offset_key = $input.data('offset-key');
    const input_element = $input.get(0);
    const inner_span = React.createElement('span', { 'data-text': 'true' }, value);
    const span_container = React.createElement('span', { 'data-offset-key': data_offset_key }, inner_span);
    ReactDOM.unmountComponentAtNode(input_element);
    ReactDOM.render(span_container, input_element);

    // frozen input workaround: add empty character to editor
    const $editor_container = $input.parent().parent().parent();

    if (!ai4seo_exists_$($editor_container)) {
        console.warn('AI for SEO: container \"$editor_container\" missing in ai4seo_set_yoast_input_content() \u2014 cannot update Yoast field.');
        return;
    }

    const editor_element = $editor_container.get(0);

    if (editor_element) {
        editor_element.focus();
        document.execCommand('insertText', false, '​');
    }
}

// =========================================================================================== \\

function ai4seo_add_text_to_yoast_editor_key_by_key( $input, value ) {
    $input = ai4seo_normalize_$($input);

    if (!ai4seo_exists_$($input)) {
        console.error('AI for SEO: element \"$input\" missing in ai4seo_add_text_to_editor_key_by_key() \u2014 cannot update metadata.');
        return;
    }

    const $editor = $input.parent().parent().parent();

    if (!ai4seo_exists_$($editor)) {
        console.error('AI for SEO: element \"$editor\" missing in ai4seo_add_text_to_editor_key_by_key() \u2014 cannot manipulate editor content.');
        return;
    }

    // go through each character and add it to the editor
    if (ai4seo_is_chrome_browser()) {
        // delete all content in the editor
        ai4seo_delete_editor_content($editor);

        $editor.focus();
        ai4seo_set_cursor_at_the_end($editor);

        for (let i = 0; i < value.length; i++) {
            document.execCommand('insertText', false, value[i]);
        }
    } else {
        $editor.text(value);
    }
}

// =========================================================================================== \\

function ai4seo_delete_editor_content($editor) {
    $editor = ai4seo_normalize_$($editor);

    if (!ai4seo_exists_$($editor)) {
        console.error('AI for SEO: element \"$editor\" missing in ai4seo_delete_editor_content() \u2014 cannot manipulate editor content.');
        return;
    }

    $editor.focus();

    // place cursor at the beginning of the editor
    ai4seo_set_cursor_at_the_end($editor);

    // Remove the content one by one
    const text_length = $editor.text().length;

    if (ai4seo_is_chrome_browser()) {
        for (let i = 0; i < text_length; i++) {
            document.execCommand('delete', false, null);
        }
    } else {
        $editor.text('');
    }
}

// =========================================================================================== \\

function ai4seo_set_cursor_at_the_end($input) {
    $input = ai4seo_normalize_$($input);

    if (!ai4seo_exists_$($input)) {
        console.error('AI for SEO: element \"$editor\" missing in ai4seo_set_cursor_at_the_end() \u2014 cannot manipulate editor content.');
        return;
    }

    const range_element = document.createRange();
    const selection = window.getSelection();
    const input_element = $input.get(0);
    range_element.selectNodeContents(input_element);
    range_element.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range_element);
}

// =========================================================================================== \\

// Function to go through the content containers and grab with .text() and put everything into a big string
function ai4seo_get_post_content() {
    let post_content = '';
    let found_a_content_container = false;
    const $editor_context = ai4seo_get_editor_context_$();

    if (!ai4seo_exists_$($editor_context)) {
        console.warn('AI for SEO: element \"$editor_context\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.');
        return '';
    }

    for (let i = 0; i < ai4seo_content_containers.length; i++) {
        let this_content_containers_child_elements = $editor_context.find(ai4seo_content_containers[i]);

        // Make sure that child-elements could be found
        if (!ai4seo_exists_$(this_content_containers_child_elements)) {
            // ai4seo_console_debug('AI for SEO: element \"' + ai4seo_content_containers[i] + '\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.');
            continue;
        }

        // Loop through child-elements and add their text to the content
        this_content_containers_child_elements.each(function() {
            let this_additional_post_content = '';
            const $this_child = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_child)) {
                console.error('AI for SEO: element \"$child\" missing in ai4seo_get_post_content() \u2014 cannot extract post content.');
                return;
            }

            // add text of the element to the content
            // if it's an input or textarea, use val() instead of text()
            if ($this_child.is('input') || $this_child.is('textarea')) {
                this_additional_post_content = $this_child.val();
            } else {
                this_additional_post_content = $this_child.text();
            }

            if (!this_additional_post_content || this_additional_post_content.toString().trim() === '') {
                return;
            }

            found_a_content_container = true;

            this_additional_post_content = ai4seo_add_dot_to_string(this_additional_post_content);

            // add additional post content to the post content, adding a space in between, if post content is not empty
            if (post_content) {
                post_content += ' ';
            }

            post_content += this_additional_post_content;
        });
    }

    if (!found_a_content_container) {
        console.warn('AI for SEO: No content containers found in ai4seo_get_post_content() \u2014 post content will be empty.');
        return '';
    }

    // for debugging: look what we got
    ai4seo_console_debug('AI for SEO: extracted post content:', post_content);

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
    if (string[string.length - 1] === '.') {
        return string;
    }

    // Add a dot if none of the above conditions were met
    string += '.';

    return string;
}

// =========================================================================================== \\

// simple debounce utility function
function ai4seo_debounce(fn, wait) {
    var timeout_id;
    return function() {
        var ctx = this, args = arguments;
        clearTimeout(timeout_id);
        timeout_id = setTimeout(function(){ fn.apply(ctx, args); }, wait);
    };
}

// =========================================================================================== \\

// Function to check response
// --- helpers -----------------------------------------------------------------

// Detects HTML payloads (login redirects, maintenance pages, etc.)
function ai4seo_looks_like_html(s) {
    if (typeof s !== 'string') { return false; }
    const trimmed = s.trim();
    if (!trimmed || trimmed[0] !== '<') { return false; }
    return /<(html|body|!DOCTYPE)/i.test(trimmed);
}

// =========================================================================================== \\

// Detects the classic WordPress AJAX "0" failure
function ai4seo_is_zero_string(s) {
    return (typeof s === 'string') && s.trim() === '0';
}

// =========================================================================================== \\

// Attempts to parse JSON from a clean or noisy string
function ai4seo_try_parse_json_from_noise(s) {
    if (typeof s !== 'string') { return null; }
    const trimmed = s.trim();

    // fast path: direct JSON
    if (trimmed && (trimmed[0] === '{' || trimmed[0] === '[')) {
        try { return JSON.parse(trimmed); } catch (e) {}
    }

    // best-effort: extract first {...} or [...]
    const m = s.match(/(\{[\s\S]*\}|\[[\s\S]*\])/);
    if (m) {
        try { return JSON.parse(m[1]); } catch (e) {}
    }
    return null;
}

// =========================================================================================== \\

// Normalizes and validates the initial response object or returns false on hard error
function ai4seo_normalize_initial_response(response) {
    if (!response) {
        return response;
    }

    if (typeof response === 'string') {
        const parsed = ai4seo_try_parse_json_from_noise(response);

        if (parsed !== null) {
            response = parsed;
        }
    }

    if (ai4seo_is_json_string(response)) {
        try {
            response = JSON.parse(response);
        } catch (e) {}
    }

    return response;
}

// =========================================================================================== \\

// Parses and returns a safe integer error code
function ai4seo_sanitize_error_code(v, fallback) {
    const n = parseInt(String(v).replace(/[^0-9]/g, ''), 10);
    return Number.isFinite(n) ? n : fallback;
}

// =========================================================================================== \\

// Formats a template string that may contain "%s" without mutating the original
function ai4seo_format_template_message(template_string, substitution) {
    if (typeof template_string !== 'string') { return null; }
    return template_string.includes('%s') ? template_string.replace('%s', substitution) : template_string;
}

// --- main --------------------------------------------------------------------

// Function to check response
function ai4seo_check_response(response, additional_error_list = {}, show_generic_error = true, add_contact_us_link = true) {
    response = ai4seo_normalize_initial_response(response);

    if (response === false) {
        ai4seo_show_error_toast(1104232360,
            wp.i18n.__('Bad Request. You may be logged out or a security plugin blocked the request.', 'ai-for-seo')
        );

        console.error('AI for SEO: Empty AJAX response');
        return false;
    }

    // must have success flag
    if (typeof response !== 'object') {
        if (typeof response === 'string') {
            if (ai4seo_looks_like_html(response)) {
                ai4seo_show_error_toast(
                    1104232362,
                    wp.i18n.__('Bad Request. You may be logged out or a security plugin blocked the request.', 'ai-for-seo')
                );

                console.error('AI for SEO: AJAX response looks like HTML', response);
                return false;
            }

            if (ai4seo_is_zero_string(response)) {
                ai4seo_show_error_toast(
                    1104232363,
                    wp.i18n.__('Bad Request. Nonce, capability or security check failed. Please reload the page and try again.', 'ai-for-seo')
                );

                console.error('AI for SEO: AJAX response is zero string', response);
                return false;
            }
        }

        ai4seo_show_error_toast(
            5214241025,
            wp.i18n.__("Bad Request.", 'ai-for-seo')
        );

        console.error('AI for SEO: Bad AJAX response', response);

        return false;
    }

    // error field set but no success field -> set response.success
    if (typeof response.success === 'undefined' && typeof response.error !== 'undefined') {
        response.success = false;
    }

    // success path
    if (typeof response.success !== 'undefined' && response.success) {
        return true;
    }

    // not successful and response.message or response.error_message set -> normalize to .error
    if (typeof response.message !== 'undefined' && typeof response.error === 'undefined') {
        response.error = response.message;
    } else if (typeof response.error_message !== 'undefined' && typeof response.error === 'undefined') {
        response.error = response.error_message;
    }

    // not successful and response.error_code set -> normalize to .code
    if (typeof response.error_code !== 'undefined' && typeof response.code === 'undefined') {
        response.code = response.error_code;
    }

    // must have success or error field
    if (typeof response.success === 'undefined' && typeof response.error === 'undefined') {
        ai4seo_show_error_toast (
            1104232361,
            wp.i18n.__('Bad Request.', 'ai-for-seo')
        );

        console.error('AI for SEO: Bad AJAX response. No "success" or "error" field present', response);
        return false;
    }

    // error path
    if (typeof response.data !== 'undefined') {
        response = response.data;
    }

    if (typeof response !== 'object' || response === null) {
        response = {};
    }

    if (typeof response.code === 'undefined') {
        response.code = 5617101125;
    }

    response.code = ai4seo_sanitize_error_code(response.code, 5717101125);
    response.headline = response.headline || '';

    if (typeof response.add_contact_us_link !== 'undefined') {
        add_contact_us_link = response.add_contact_us_link;
    }

    if (typeof response.error === 'undefined') {
        response.error = wp.i18n.__('An unknown error occurred.', 'ai-for-seo');
    }

    let modal_settings = {};
    if (response.headline) {
        modal_settings.headline = response.headline;
    }

    // print the error
    console.error('AI for SEO - API Error #' + response.code + ': ' + response.error);

    // additional_error_list takes priority
    if (additional_error_list[response.code]) {
        const formated_template_message = ai4seo_format_template_message(additional_error_list[response.code], response.error);
        ai4seo_open_generic_error_notification_modal(response.code, formated_template_message || additional_error_list[response.code], '', modal_settings);
        return false;
    }

    // known RobHub API error codes
    if (Array.isArray(ai4seo_robhub_api_response_error_codes) &&
        ai4seo_robhub_api_response_error_codes.includes(response.code)) {
        ai4seo_handle_common_robhub_api_response_errors(response.error, response.code, modal_settings);
        return false;
    }

    // plugin's error-code map
    if (ai4seo_error_codes_and_messages &&
        Object.prototype.hasOwnProperty.call(ai4seo_error_codes_and_messages, response.code)) {
        const base = ai4seo_error_codes_and_messages[response.code];
        const msg2 = ai4seo_format_template_message(base, response.error);
        ai4seo_open_generic_error_notification_modal(response.code, msg2 || base, '', modal_settings);
        return false;
    }

    if (show_generic_error) {
        let error_message = (response.error ? response.error : '');
        if (add_contact_us_link) {
            if (error_message) {
                error_message += '<br><br>';
            }
            error_message += wp.i18n.sprintf(
                wp.i18n.__("Please check your settings or <a href='%s' target='_blank'>contact us</a>.", 'ai-for-seo'),
                ai4seo_official_contact_url
            );
        }
        ai4seo_open_generic_error_notification_modal(response.code, error_message, '', modal_settings);
    }

    return false;
}

// =========================================================================================== \\

function ai4seo_handle_common_robhub_api_response_errors(error_message, error_code, modal_settings = {}) {
    // Check if ai4seo_robhub_api_response_error_codes_and_messages-array contains key that contains the error-message
    for (const error_code in ai4seo_robhub_api_response_error_codes_and_messages) {
        if (error_message.includes(error_code)) {
            // Display error-message
            ai4seo_open_generic_error_notification_modal(error_code, ai4seo_robhub_api_response_error_codes_and_messages[error_code]);
            return;
        }
    }

    // Display generic error-message if no error-message was found
    ai4seo_open_generic_error_notification_modal(error_code, error_message, '', modal_settings);
}

// =========================================================================================== \\

function ai4seo_copy_to_clipboard(to_copy_text, $copied_to_clipboard) {
    // Method A: Using the Clipboard API if available
    if (typeof navigator !== 'undefined' && typeof navigator.clipboard !== 'undefined') {
        // Use the Clipboard API to copy the text
        navigator.clipboard.writeText(to_copy_text).then(function() {
            if ($copied_to_clipboard) {
                ai4seo_show_element_for_x_time($copied_to_clipboard)
            }
        }, function(err) {
            console.warn('AI for SEO: Could not copy to clipboard');
        });
    } else {
        // Method B: Fallback to using a textarea element
        const $temporary_text_area = ai4seo_normalize_$('<textarea></textarea>');
        const $body = ai4seo_normalize_$('body', document);

        if (!ai4seo_exists_$($temporary_text_area) || !ai4seo_exists_$($body)) {
            console.warn('AI for SEO: Could not prepare textarea fallback in ai4seo_copy_to_clipboard().');
            return;
        }

        $temporary_text_area.val(to_copy_text);
        $body.append($temporary_text_area);

        const temporary_text_area_element = $temporary_text_area.get(0);

        if (!temporary_text_area_element) {
            console.warn('AI for SEO: Temporary textarea element missing in ai4seo_copy_to_clipboard() fallback.');
            $temporary_text_area.remove();
            return;
        }

        temporary_text_area_element.select();

        try {
            document.execCommand('copy');
            ai4seo_show_element_for_x_time($copied_to_clipboard)
        } catch (err) {
            console.warn('AI for SEO: Could not copy to clipboard');
        }

        $temporary_text_area.remove();
    }
}

// =========================================================================================== \\

function ai4seo_show_element_for_x_time($target, milliseconds = 3000) {
    $target = ai4seo_normalize_$($target);

    if (!ai4seo_exists_$($target)) {
        console.error('AI for SEO: element \"$target\" missing in ai4seo_show_element_for_x_time() \u2014 nothing to display temporarily.');
        return;
    }

    // Show the element
    $target.css('display', 'block');

    // Hide the element after 3 seconds
    setTimeout(function() {
        $target.css('display', 'none');
    }, milliseconds);
}


// ___________________________________________________________________________________________ \\
// === PLUGIN'S PAGES ======================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_active_subpage() {
    return ai4seo_get_localization_parameter('ai4seo_active_subpage');
}

// =========================================================================================== \\

function ai4seo_get_active_post_type_subpage() {
    return ai4seo_get_localization_parameter('ai4seo_active_post_type_subpage');
}

// =========================================================================================== \\

function ai4seo_get_active_meta_tags() {
    return ai4seo_get_localization_parameter('ai4seo_active_meta_tags');
}

// =========================================================================================== \\

function ai4seo_get_active_attachment_attributes() {
    return ai4seo_get_localization_parameter('ai4seo_active_attachment_attributes');
}


// ___________________________________________________________________________________________ \\
// === DASHBOARD ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_remove_notification_count() {
    // only on dashboard page
    if (ai4seo_get_active_subpage() !== 'dashboard') {
        return;
    }

    const $update_plugins_badge = ai4seo_normalize_$('#toplevel_page_ai-for-seo .update-plugins');

    if (!ai4seo_exists_$($update_plugins_badge)) {
        ai4seo_console_debug('AI for SEO: element \"$update_plugins_badge\" missing in ai4seo_remove_notification_count() \u2014 cannot clear notification count.');
        return;
    }

    $update_plugins_badge.remove();
}

// =========================================================================================== \\

function ai4seo_refresh_dashboard_statistics($button) {
    $button = ai4seo_normalize_$($button);

    if (!ai4seo_exists_$($button)) {
        console.error('AI for SEO: element "$button" missing in ai4seo_refresh_dashboard_statistics() — cannot refresh statistics.');
        return;
    }

    ai4seo_add_loading_html_to_element($button);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Statistics are refreshing now...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_refresh_dashboard_statistics')
        .then(response => {
            ai4seo_show_success_toast(wp.i18n.__('Reloading page...', 'ai-for-seo'));
        })
        .catch((error) => {
            ai4seo_show_generic_error_toast(712181225);
            ai4seo_remove_loading_html_from_element($button);
            ai4seo_unlock_and_enable_lockable_input_fields();
            throw error;
        })
        .finally(() => {
            setTimeout(() => ai4seo_safe_page_load('dashboard'), 1000);
        });
}

// =========================================================================================== \\

function ai4seo_refresh_robhub_account($potential_button, options = {}) {
    $potential_button = ai4seo_normalize_$($potential_button);

    const settings = Object.assign({
        check_for_purchase: false,
        attempt: 1,
        max_attempts: 5,
        initial_delay_seconds: 5,
        reuse_loading: false,
    }, options || {});

    if (settings.attempt === 1 && !settings.reuse_loading) {
        if (ai4seo_exists_$($potential_button)) {
            ai4seo_add_loading_html_to_element($potential_button);
        }

        ai4seo_lock_and_disable_lockable_input_fields();
    }

    const payload = {};

    if (settings.check_for_purchase) {
        payload.check_for_purchase = 1;
    }

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Syncing your account now...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_refresh_robhub_account', payload)
        .then((data) => {
            const is_purchase_ready = settings.check_for_purchase ? Boolean(data && data.is_purchase_ready) : true;

            if (settings.check_for_purchase && !is_purchase_ready) {
                if (settings.attempt < settings.max_attempts) {
                    const next_delay_seconds = settings.initial_delay_seconds * settings.attempt;

                    ai4seo_show_info_toast(wp.i18n.__('Waiting for your purchase to complete. Checking again shortly...', 'ai-for-seo'), next_delay_seconds * 1000 + 1000);

                    setTimeout(() => {
                        ai4seo_refresh_robhub_account($potential_button, Object.assign({}, settings, {
                            attempt: settings.attempt + 1,
                            reuse_loading: true,
                        }));
                    }, next_delay_seconds * 1000);
                    return;
                }

                ai4seo_show_warning_toast(wp.i18n.__('Your purchase is still processing. Please try refreshing again later (Dashboard > Credits > Refresh) or contact support if the issue persists.', 'ai-for-seo'));

                if (ai4seo_exists_$($potential_button)) {
                    ai4seo_remove_loading_html_from_element($potential_button);
                }

                ai4seo_unlock_and_enable_lockable_input_fields();

                setTimeout(() => {
                    ai4seo_safe_page_load('dashboard');
                }, 4000);
                return;
            }

            ai4seo_show_success_toast(wp.i18n.__('Account synced successfully. Reloading page...', 'ai-for-seo'));
        })
        .catch((error) => {
            ai4seo_show_generic_error_toast(812181225);

            if (ai4seo_exists_$($potential_button)) {
                ai4seo_remove_loading_html_from_element($potential_button);
            }

            ai4seo_unlock_and_enable_lockable_input_fields();
            throw error;
        })
        .finally(() => {
            setTimeout(() => ai4seo_safe_page_load('dashboard'), 1000);
        });
}

// =========================================================================================== \\

function ai4seo_start_bulk_generation($button) {
    $button = ai4seo_normalize_$($button);

    if (!ai4seo_exists_$($button)) {
        console.error('AI for SEO: element \"$button\" missing in ai4seo_start_bulk_generation() \u2014 cannot start bulk generation.');
        return;
    }

    ai4seo_save_anything($button, ai4seo_validate_bulk_generation_inputs, function() { ai4seo_safe_page_load(); }, function() { ai4seo_safe_page_load(); });
}

// =========================================================================================== \\

// Handle datetime picker visibility and label updates for SEO Autopilot
function ai4seo_handle_bulk_generation_new_or_existing_filter_change() {
    const $document = ai4seo_normalize_$(document);

    if (!ai4seo_exists_$($document)) {
        console.error('AI for SEO: element \"$document\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot manage bulk generation filter.');
        return;
    }

    $document.ready(function() {
        if (!ai4seo_exists_$('#ai4seo_bulk_generation_new_or_existing_filter')) {
            console.warn('AI for SEO: selector \"#ai4seo_bulk_generation_new_or_existing_filter\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot configure bulk generation scope.');
            return;
        }

        if (!ai4seo_exists_$('.ai4seo-datetime-picker-container')) {
            console.warn('AI for SEO: selector \".ai4seo-datetime-picker-container\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot display filter options.');
            return;
        }

        if (!ai4seo_exists_$('.ai4seo-datetime-picker-label')) {
            console.warn('AI for SEO: selector \".ai4seo-datetime-picker-label\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot update filter label.');
            return;
        }

        if (!ai4seo_exists_$('#ai4seo_bulk_generation_new_or_existing_filter_reference_time')) {
            console.warn('AI for SEO: selector \"#ai4seo_bulk_generation_new_or_existing_filter_reference_time\" missing in ai4seo_handle_bulk_generation_new_or_existing_filter_change() \u2014 cannot capture schedule.');
            return;
        }

        // Get elements
        const $filter_select = ai4seo_normalize_$('#ai4seo_bulk_generation_new_or_existing_filter');
        const $datetime_picker_container = ai4seo_normalize_$('.ai4seo-datetime-picker-container');
        const $datetime_picker_label = ai4seo_normalize_$('.ai4seo-datetime-picker-label');
        const $datetime_picker_input = ai4seo_normalize_$('#ai4seo_bulk_generation_new_or_existing_filter_reference_time');

        // Function to update datetime picker visibility and label
        function ai4seo_on_bulk_generation_datetime_picker_update() {
            const selected_value = $filter_select.val();

            if (selected_value === 'new') {
                $datetime_picker_container.show();

                // 'New entries since:'
                $datetime_picker_label.text(wp.i18n.__('New entries since:', 'ai-for-seo'));

                // Populate with current timestamp if empty
                if (!$datetime_picker_input.val()) {
                    ai4seo_populate_datetime_picker_with_current_timestamp($datetime_picker_input);
                }
            } else if (selected_value === 'existing') {
                $datetime_picker_container.show();

                // 'Old entries before:'
                $datetime_picker_label.text(wp.i18n.__('Old entries before:', 'ai-for-seo'));

                // Populate with current timestamp if empty
                if (!$datetime_picker_input.val()) {
                    ai4seo_populate_datetime_picker_with_current_timestamp($datetime_picker_input);
                }
            } else {
                $datetime_picker_container.hide();
            }
        }

        // Initial update
        ai4seo_on_bulk_generation_datetime_picker_update();

        // Update on change
        $filter_select.off('change.ai4seo-datepicker', ai4seo_on_bulk_generation_datetime_picker_update);
        $filter_select.on('change.ai4seo-datepicker', ai4seo_on_bulk_generation_datetime_picker_update);
    });
}

// =========================================================================================== \\

// Populate datetime picker with current timestamp converted to datetime-local format
function ai4seo_populate_datetime_picker_with_current_timestamp($datetime_picker_input) {
    $datetime_picker_input = ai4seo_normalize_$($datetime_picker_input);

    if (!ai4seo_exists_$($datetime_picker_input)) {
        console.warn('AI for SEO: element \"$datetime_picker\" missing in ai4seo_populate_datetime_picker_with_current_timestamp() \u2014 reference time cannot preset.');
        return;
    }
    
    // Check if there's already a stored timestamp from the server
    let timestamp = $datetime_picker_input.data('stored-timestamp');

    // If no stored timestamp, use current time
    if (!timestamp) {
        timestamp = Math.floor(Date.now() / 1000);
    }

    // Convert timestamp to datetime-local format
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    const datetime_local = `${year}-${month}-${day}T${hours}:${minutes}`;

    // Set the datetime-local value
    $datetime_picker_input.val(datetime_local);
}

// =========================================================================================== \\

function ai4seo_validate_bulk_generation_inputs() {
    return true;
}

// =========================================================================================== \\

function ai4seo_stop_bulk_generation($submit) {
    $submit = ai4seo_normalize_$($submit);

    if (!ai4seo_exists_$($submit)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_stop_bulk_generation() \u2014 cannot stop bulk generation.');
        return;
    }

    ai4seo_add_loading_html_to_element($submit);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Stopping the SEO Autopilot now...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_stop_bulk_generation')
        .then(response => {
            ai4seo_show_success_toast(wp.i18n.__('SEO Autopilot stopped successfully. Reloading page...', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(912181225);
        })
        .finally(() => {
            setTimeout(() => ai4seo_safe_page_load(), 1000);
        });
}

// =========================================================================================== \\

function ai4seo_retry_all_failed_attachment_attributes($submit) {
    $submit = ai4seo_normalize_$($submit);

    if (!ai4seo_exists_$($submit)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_retry_all_failed_attachment_attributes() \u2014 cannot retry failed attachment attributes.');
        return;
    }

    ai4seo_add_loading_html_to_element($submit);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Retrying all failed attachment attributes now...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_retry_all_failed_attachment_attributes')
        .then(response => { /* nothing */ })
        .catch(error => {
            ai4seo_show_generic_error_toast(1012181225);
        })
        .finally(() => {
            ai4seo_safe_page_load();
        });
}

// =========================================================================================== \\

function ai4seo_retry_all_failed_metadata($submit, post_type) {
    $submit = ai4seo_normalize_$($submit);

    if (!ai4seo_exists_$($submit)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_retry_all_failed_metadata() \u2014 cannot retry failed metadata.');
        return;
    }

    ai4seo_add_loading_html_to_element($submit);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Retrying all failed metadata now...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_retry_all_failed_metadata', { post_type: post_type })
        .then(response => { /* nothing */ })
        .catch(error => {
            ai4seo_show_generic_error_toast(1112181225);
        })
        .finally(() => {
            ai4seo_safe_page_load();
        });
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
            // skip button if no active meta tags or attachment attributes are selected
            const num_active_meta_tags = ai4seo_get_active_meta_tags() ? Object.keys(ai4seo_get_active_meta_tags()).length : 0;
            const num_active_attachment_attributes = ai4seo_get_active_attachment_attributes() ? Object.keys(ai4seo_get_active_attachment_attributes()).length : 0;

            if (this_processing_context === 'metadata' && num_active_meta_tags === 0) {
                return;
            }

            if (this_processing_context === 'attachment-attributes' && num_active_attachment_attributes === 0) {
                return;
            }

            const $generate_all_button_container = ai4seo_normalize_$(this_generate_all_button_selector);

            if (!ai4seo_exists_$($generate_all_button_container)) {
                //ai4seo_console_debug('AI for SEO: no \"generate all button container\" match found for selector \"' + this_generate_all_button_selector + '\" in ai4seo_init_generate_all_button() \u2014 skipped.');
                return;
            }

            ai4seo_console_debug('AI for SEO: found \"generate all button container\" match for selector \"' + this_generate_all_button_selector + '\" in ai4seo_init_generate_all_button().');

            // Check if this container already has a generate all button (ai4seo-generate-all-button class)
            const $already_in_place_generate_all_buttons_wrapper = $generate_all_button_container.find('.ai4seo-generate-all-button-wrapper');

            if (ai4seo_exists_$($already_in_place_generate_all_buttons_wrapper)) {
                // find the generate all button inside
                const $possible_generate_button = $already_in_place_generate_all_buttons_wrapper.find('.ai4seo-generate-all-button');

                // if button is currently inactive -> keep it, don't change anything (probably generating right now)
                if (ai4seo_exists_$($possible_generate_button)) {
                    if ($possible_generate_button.hasClass('ai4seo-element-inactive')) {
                        return;
                    }

                    // if button got data-currently-pressed -> keep it, don't change anything (probably being pressed right now)
                    if ($possible_generate_button.data('currently-pressed')) {
                        return;
                    }
                }

                // remove the wrapper
                $already_in_place_generate_all_buttons_wrapper.remove();
            }

            ai4seo_add_generate_all_button(this_processing_context, $generate_all_button_container);
        });
    }
}

// =========================================================================================== \\

function ai4seo_add_generate_all_button(processing_context, $generate_all_buttons_container) {
    // Define variable for element
    $generate_all_buttons_container = ai4seo_normalize_$($generate_all_buttons_container);

    if (!ai4seo_exists_$($generate_all_buttons_container)) {
        ai4seo_console_debug('AI for SEO: $generate_all_button missing in ai4seo_add_generate_all_button() \u2014 skipping generate-all hook.');
        return;
    }

    const $generate_all_button_wrapper = $generate_all_buttons_container.find('.ai4seo-generate-all-button-wrapper');

    // check if this hook element already has a generate all button (ai4seo-generate-all-button-wrapper class)
    if (ai4seo_exists_$($generate_all_button_wrapper)) {
        return;
    }

    let button_html = '';
    let previous_num_normalized_generation_fields = -1;
    let try_read_page_content_via_js = 'true'; // assuming I'm inside a WordPress editor
    const $read_page_content_via_js = ai4seo_normalize_$('#ai4seo-read-page-content-via-js');

    if (ai4seo_exists_$($read_page_content_via_js)) {
        try_read_page_content_via_js = $read_page_content_via_js.val();
    }

    // find the generation fields
    let possible_generation_fields = [];

    if (processing_context === 'metadata') {
        possible_generation_fields = ai4seo_get_active_meta_tags();
    } else if (processing_context === 'attachment-attributes') {
        possible_generation_fields = ai4seo_get_active_attachment_attributes();
    }

    // go through each generate all button variant (overwrite existing content: true/false)
    jQuery.each([true, false], function(overwrite_existing_content) {
        // Define button variables
        let onclick = '';
        let button_title = '';
        let button_label = "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-64x64.png' class='ai4seo-logo'  alt='AI' />";

        if (processing_context === 'metadata') {
            const normalized_generation_fields = ai4seo_get_normalized_generation_fields(possible_generation_fields, overwrite_existing_content);
            const num_normalized_generation_fields = normalized_generation_fields ? Object.keys(normalized_generation_fields).length : 0;

            if (num_normalized_generation_fields === 0) {
                //ai4seo_console_debug('AI for SEO: no active meta tags found in ai4seo_add_generate_all_button() \u2014 skipping generate-all button.');
                return;
            }

            // if we previously and now have the same number of fields, skip adding the button again
            if (previous_num_normalized_generation_fields === num_normalized_generation_fields) {
                return;
            }

            previous_num_normalized_generation_fields = num_normalized_generation_fields;

            const generation_fields_identifiers = Object.keys(normalized_generation_fields);
            const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names(generation_fields_identifiers);
            const credits_usage = ai4seo_get_credits_usage_from_generation_fields(normalized_generation_fields);

            // build label
            if (overwrite_existing_content) {
                button_label += wp.i18n.sprintf(wp.i18n.__('Generate & <strong>Overwrite</strong><br>Data for %s %s', 'ai-for-seo'), num_normalized_generation_fields, wp.i18n._n('Field', 'Fields', num_normalized_generation_fields, 'ai-for-seo'));
                button_title += wp.i18n.sprintf(wp.i18n.__('Generate and overwrite existing field(s): %s', 'ai-for-seo'), human_readable_generated_field_identifiers.join(', '));
            } else {
                button_label += wp.i18n.sprintf(wp.i18n.__('Generate Data for<br>%s <strong>Empty</strong> %s', 'ai-for-seo'), num_normalized_generation_fields, wp.i18n._n('Field', 'Fields', num_normalized_generation_fields, 'ai-for-seo'));
                button_title += wp.i18n.sprintf(wp.i18n.__('Generate content for empty field(s) only: %s', 'ai-for-seo'), human_readable_generated_field_identifiers.join(', '));
            }

            button_label += '<div class=\"ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge\">' + credits_usage + ' ' + wp.i18n.__('Cr', 'ai-for-seo') + '</div>';

            // build onclick and title
            onclick += 'ai4seo_generate_with_ai(this, \"ai4seo_generate_metadata\", ai4seo_get_active_meta_tags(), false, ' + (overwrite_existing_content ? 'true' : 'false') + ', ' + (try_read_page_content_via_js) + ');';
        } else if (processing_context === 'attachment-attributes') {
            const normalized_generation_fields = ai4seo_get_normalized_generation_fields(possible_generation_fields, overwrite_existing_content);
            const num_normalized_generation_fields = normalized_generation_fields ? Object.keys(normalized_generation_fields).length : 0;

            if (num_normalized_generation_fields === 0) {
                ai4seo_console_debug('AI for SEO: no active attachment attributes found in ai4seo_add_generate_all_button() — skipping generate-all button.');
                return;
            }

            // if we previously and now have the same number of fields, skip adding the button again
            if (previous_num_normalized_generation_fields === num_normalized_generation_fields) {
                return;
            }

            previous_num_normalized_generation_fields = num_normalized_generation_fields;

            const generation_fields_identifiers = Object.keys(normalized_generation_fields);
            const human_readable_generated_field_identifiers = ai4seo_get_human_readable_generation_field_names(generation_fields_identifiers);
            const credits_usage = ai4seo_get_credits_usage_from_generation_fields(normalized_generation_fields);

            // build label
            if (overwrite_existing_content) {
                button_label += wp.i18n.sprintf(
                    wp.i18n.__('Generate & <strong>Overwrite</strong><br>Data for %s %s', 'ai-for-seo'),
                    num_normalized_generation_fields,
                    wp.i18n._n('Attribute', 'Attributes', num_normalized_generation_fields, 'ai-for-seo')
                );
                button_title += wp.i18n.sprintf(
                    wp.i18n.__('Generate and overwrite existing attribute(s): %s', 'ai-for-seo'),
                    human_readable_generated_field_identifiers.join(', ')
                );
            } else {
                button_label += wp.i18n.sprintf(
                    wp.i18n.__('Generate Data for<br>%s <strong>Empty</strong> %s', 'ai-for-seo'),
                    num_normalized_generation_fields,
                    wp.i18n._n('Attribute', 'Attributes', num_normalized_generation_fields, 'ai-for-seo')
                );
                button_title += wp.i18n.sprintf(
                    wp.i18n.__('Generate content for empty attribute(s) only: %s', 'ai-for-seo'),
                    human_readable_generated_field_identifiers.join(', ')
                );
            }

            button_label += '<div class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' + credits_usage + ' ' + wp.i18n.__('Cr', 'ai-for-seo') + '</div>';

            // build onclick and title
            onclick += 'ai4seo_generate_with_ai(this, "ai4seo_generate_attachment_attributes", ai4seo_get_active_attachment_attributes(), false, ' + (overwrite_existing_content ? 'true' : 'false') + ');';
        }

        // put everything together
        button_html += "<button type='button' onclick='" + onclick + "' title='" + button_title + "' class='ai4seo-button ai4seo-big-button ai4seo-generate-all-button ai4seo-lockable'>" + button_label + '</button>';
    });

    let wrapped_button_html = "<div class='ai4seo-generate-all-button-wrapper'>" + button_html + '</div>';

    // Add button-element after element
    $generate_all_buttons_container.prepend(wrapped_button_html);

    // init the potential new button
    ai4seo_init_buttons();
}


// =========================================================================================== \\

function ai4seo_get_credits_usage_from_generation_fields(generation_fields) {
    // go through each entry, find 'credits' and sum them up
    let total_credits = 0;

    for (const field_key in generation_fields) {
        if (generation_fields.hasOwnProperty(field_key)) {
            const field_data = generation_fields[field_key];
            if (field_data.hasOwnProperty('credits')) {
                const field_credits = parseInt(field_data['credits'], 10);
                if (!isNaN(field_credits)) {
                    total_credits += field_credits;
                }
            }
        }
    }

    return total_credits;
}

// =========================================================================================== \\

function ai4seo_try_add_generate_button_to_input($generate_data_for_input, generate_data_for_input_selector) {
    $generate_data_for_input = ai4seo_normalize_$($generate_data_for_input);

    if (!ai4seo_exists_$($generate_data_for_input)) {
        console.warn('AI for SEO: $generate_data_for_input missing in ai4seo_add_generate_button_to_input() \u2014 skipping button injection.');
        return;
    }

    const $possible_generate_button = ai4seo_try_find_generate_button_by_input_$($generate_data_for_input, false);

    // if we find a generate-button that is not inactive, we remove it to avoid duplicates
    if (ai4seo_exists_$($possible_generate_button)) {
        // if button is currently inactive -> keep it, don't change anything (probably generating right now)
        if ($possible_generate_button.hasClass('ai4seo-element-inactive')) {
            return;
        }

        // if button got data-currently-pressed -> keep it, don't change anything (probably being pressed right now)
        if ($possible_generate_button.data('currently-pressed')) {
            return;
        }

        $possible_generate_button.remove();
    }

    // Add button-element after input-element
    const generate_button_html = ai4seo_get_generate_button_output($generate_data_for_input, generate_data_for_input_selector);

    // Find a container we can attach the button to
    let $generate_button_reference = $generate_data_for_input;

    // If we're dealing with a Yoast-editor, we need to adjust the target-element
    const $yoast_generate_button_reference_candidate = $generate_data_for_input.closest('.yst-replacevar__editor');

    if (ai4seo_exists_$($yoast_generate_button_reference_candidate)) {
        $generate_button_reference = $yoast_generate_button_reference_candidate;
    }

    $generate_button_reference.after(generate_button_html);

    // check if we have a generate button now
    const $generate_button = ai4seo_try_find_generate_button_by_input_$($generate_data_for_input, false);

    if (!ai4seo_exists_$($generate_button)) {
        console.warn('AI for SEO: could not add generate button near $generate_button_reference in ai4seo_add_generate_button_to_input().');
        return;
    }

    // init the potential new button
    ai4seo_init_buttons();

    // workaround for yoast keyphrase (#id focus-keyword-input-metabox), make input 100% wide, make parent flex-direction: column
    // minor face lift when our button is next to the input
    if ($generate_button_reference.attr('id') === 'focus-keyword-input-metabox') {
        $generate_button_reference.css('width', '100%');
        $generate_button_reference.parent().css('flex-direction', 'column');
    }

    // workaround for rank math keyphrase (input is child of .rank-math-focus-keyword > div), make text align left, margin-top: -10px margin-bottom: 10px
    if ($generate_data_for_input.parent().parent().hasClass('rank-math-focus-keyword')) {
        $generate_button.css('text-align', 'left');
        $generate_button.css('transform', 'translateY(-15px)');
    }

    // workaround for gutenberg editor sidebar
    const $possible_side_bar_parent = ai4seo_normalize_$($generate_data_for_input.closest('.editor-sidebar'));

    if (ai4seo_exists_$($possible_side_bar_parent)) {
        $generate_button.css('text-align', 'left');
    }
}

// =========================================================================================== \\

function ai4seo_try_find_generate_button_by_input_$($generate_data_for_input) {
    $generate_data_for_input = ai4seo_normalize_$($generate_data_for_input);

    if (!ai4seo_exists_$($generate_data_for_input)) {
        console.warn('AI for SEO: $generate_data_for_input missing in ai4seo_get_generate_button_by_input_selector() \u2014 cannot find generate button.');
        return null;
    }

    // If we're dealing with a Yoast-editor, we need to adjust the target-element
    const $yoast_editor_candidate = $generate_data_for_input.closest('.yst-replacevar__editor');

    if (ai4seo_exists_$($yoast_editor_candidate)) {
        $generate_data_for_input = $yoast_editor_candidate;
    }

    let $possible_generate_button = $generate_data_for_input.next();

    if (!ai4seo_exists_$($possible_generate_button)) {
        // ai4seo_console_debug('AI for SEO: no next element found near $generate_data_for_input in ai4seo_get_generate_button_by_input_selector() \u2014 cannot find generate button.');
        return null;
    }

    //ai4seo_console_debug('AI for SEO: found possible_generate_button near $generate_data_for_input in ai4seo_get_generate_button_by_input_selector().', $possible_generate_button);

    // Check if element after $parent contains "ai4seo-generate-button"-class
    if (!$possible_generate_button.hasClass('ai4seo-generate-button')) {
        //ai4seo_console_debug('AI for SEO: possible_generate_button is not a generate button in ai4seo_get_generate_button_by_input_selector() \u2014 cannot find generate button.');
        return null;
    }

    // ai4seo_console_debug('AI for SEO: confirmed possible_generate_button is a generate button in ai4seo_get_generate_button_by_input_selector().', $generate_data_for_input, $possible_generate_button);

    return $possible_generate_button;
}

// =========================================================================================== \\

function ai4seo_get_generate_button_output($generate_data_for_input, generate_data_for_input_selector, button_label = 'auto', button_title = '') {
    // Make sure that onclick-variable is defined
    let button_onclick = '';
    let try_read_page_content_via_js = 'true'; // assuming I'm inside a WordPress editor
    const $read_page_content_via_js = ai4seo_normalize_$('#ai4seo-read-page-content-via-js');

    if (ai4seo_exists_$($read_page_content_via_js)) {
        try_read_page_content_via_js = $read_page_content_via_js.val();
    }

    if (button_label === 'auto') {
        // Generate with AI
        button_label = wp.i18n.__('Generate with AI', 'ai-for-seo');
    }

    // Check if processing-entry exists in mapping-array
    if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context']) {
        // Prepare onclick for attachment-attributes-processing
        if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context'] === 'attachment-attributes') {
            if (!ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['attachment_attributes_identifier']) {
                console.error('AI for SEO: No attachment_attributes_identifier defined for element-selector: ' + generate_data_for_input_selector);
                return;
            }

            const attachment_attributes_identifier = ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['attachment_attributes_identifier'];
            const raw_generation_fields = { [attachment_attributes_identifier]: [generate_data_for_input_selector]};
            const normalized_generation_fields = ai4seo_get_normalized_generation_fields(raw_generation_fields, true);
            const num_normalized_generation_fields = normalized_generation_fields ? Object.keys(normalized_generation_fields).length : 0;

            if (num_normalized_generation_fields === 0) {
                console.warn('AI for SEO: No active attachment attributes found for element-selector: ' + generate_data_for_input_selector);
                return;
            }

            const credits_usage = ai4seo_get_credits_usage_from_generation_fields(normalized_generation_fields);

            button_label += '<div class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' + credits_usage + ' ' + wp.i18n.__('Cr', 'ai-for-seo') + '</div>';
            button_onclick = 'ai4seo_generate_with_ai(this, \"ai4seo_generate_attachment_attributes\", ' + JSON.stringify(normalized_generation_fields) + ', false, true);';
        }

        // Prepare onclick for metadata-processing
        else if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context'] === 'metadata') {
            if (!ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['metadata_identifier']) {
                console.error('AI for SEO: No metadata_identifier defined for element-selector: ' + generate_data_for_input_selector);
                return;
            }

            const metadata_identifier = ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['metadata_identifier'];
            const raw_generation_fields = { [metadata_identifier]: [generate_data_for_input_selector] };
            const normalized_generation_fields = ai4seo_get_normalized_generation_fields(raw_generation_fields, true);
            const num_normalized_generation_fields = normalized_generation_fields ? Object.keys(normalized_generation_fields).length : 0;

            if (num_normalized_generation_fields === 0) {
                console.warn('AI for SEO: No active meta tags found for element-selector: ' + generate_data_for_input_selector);
                return;
            }

            const credits_usage = ai4seo_get_credits_usage_from_generation_fields(normalized_generation_fields);

            button_label += '<div class="ai4seo-generation-button-credits-usage ai4seo-credits-usage-badge">' +
                credits_usage + ' ' + wp.i18n.__('Cr', 'ai-for-seo') + '</div>';

            button_onclick = 'ai4seo_generate_with_ai(this, "ai4seo_generate_metadata", ' +
                JSON.stringify(normalized_generation_fields) + ', false, true, ' + (try_read_page_content_via_js) + ');';
        }


        // Prepare fallback onclick
        else {
            console.error('AI for SEO: Unknown processing-context: ' + ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['processing-context']);
        }
    } else {
        console.error('AI for SEO: No processing-context defined for element-selector: ' + generate_data_for_input_selector);
    }

    // Prepare additional css-class for button-output
    let additional_css_class = '';

    if (ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class']) {
        additional_css_class = ' ' + ai4seo_generate_data_for_inputs[generate_data_for_input_selector]['css-class'];
    }

    return "<button type='button' onclick='" + button_onclick + "' title='" + button_title + "' class='ai4seo-button ai4seo-generate-button ai4seo-generate-button-arrow ai4seo-lockable" + additional_css_class + "'><img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> " + button_label + '</button>';
}

// =========================================================================================== \\

function ai4seo_get_editor_context_$() {
    // Define variable for the elementor-preview-iframe-element
    const $elementor_preview_iframe = ai4seo_normalize_$('#elementor-preview-iframe');

    if (ai4seo_exists_$($elementor_preview_iframe)) {
        return ai4seo_normalize_$($elementor_preview_iframe.contents());
    }

    // Define variable for the be-builder-iframe
    const $mfn_iframe = ai4seo_normalize_$('#mfn-vb-ifr');

    if (ai4seo_exists_$($mfn_iframe)) {
        return ai4seo_normalize_$($mfn_iframe.contents());
    }

    // define variable for the gutenberg-editor-iframe (name="editor-canvas")
    const $gutenberg_editor_iframe = ai4seo_normalize_$('iframe[name="editor-canvas"]');

    if (ai4seo_exists_$($gutenberg_editor_iframe)) {
        return ai4seo_normalize_$($gutenberg_editor_iframe.contents());
    }

    // Return jQuery-document if no elementor-iframe exists
    return ai4seo_normalize_$(document);
}

// =========================================================================================== \\

/**
 * Check if the user is inside the Elementor editor.
 * @return bool True if inside the Elementor editor, false otherwise.
 */
function ai4seo_is_inside_elementor_editor() {
    const $body = ai4seo_normalize_$('body', document);

    if (!ai4seo_exists_$($body)) {
        console.error('AI for SEO: body element missing in ai4seo_is_inside_elementor_editor() \u2014 cannot determine if inside Elementor editor.');
        return false;
    }

    return typeof elementor !== 'undefined' &&
        typeof elementorFrontend !== 'undefined' &&
        ai4seo_exists_$($body) && $body.hasClass('elementor-editor-active');
}

// =========================================================================================== \\

function ai4seo_is_inside_gutenberg_editor() {
    const $body = ai4seo_normalize_$('body', document);

    if (!ai4seo_exists_$($body)) {
        console.error('AI for SEO: body element missing in ai4seo_is_inside_gutenberg_editor() \u2014 cannot determine if inside Gutenberg editor.');
        return false;
    }

    return ai4seo_exists_$($body) && $body.hasClass('block-editor-page');
}

// =========================================================================================== \\

function ai4seo_is_inside_muffin_builder_editor() {
    const $muffin_visual_builder = ai4seo_normalize_$('#mfn-visualbuilder', document);

    return ai4seo_exists_$($muffin_visual_builder);
}

// =========================================================================================== \\

function ai4seo_add_loading_html_to_element($target) {
    // Make sure that element is jquery-element
    $target = ai4seo_normalize_$($target);

    if (!ai4seo_exists_$($target)) {
        ai4seo_console_debug('AI for SEO: element \"$target\" missing in ai4seo_add_loading_html_to_element() \u2014 cannot display loading state.');
        return;
    }

    $target.each(function() {
        // Define variable for this element
        const $this = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this)) {
            console.warn('AI for SEO: element \"$this\" missing in ai4seo_add_loading_html_to_element() \u2014 cannot display loading state.');
            return;
        }

        // check if we already have a data-ai-for-seo-original-html-content
        if ($this.attr('data-ai-for-seo-original-html-content')) {
            // already in loading state
            return;
        }

        // check width and height, preserve it to avoid layout shifts
        const current_width = $this.outerWidth();
        const current_height = $this.outerHeight();

        if (current_width > 0) {
            $this.css('width', current_width + 'px');
        }

        if (current_height > 0) {
            $this.css('height', current_height + 'px');
        }

        // Define variable for the original html-content
        const original_html_content = $this.html();

        // Replace html-content with loading-elements
        $this.html("<div class='ai4seo-loading-animation-container'><div class='ai4seo-loading-animation'><div></div><div></div><div></div><div></div></div></div>");

        // Add data-attribute to element with original html-content
        $this.attr('data-ai-for-seo-original-html-content', original_html_content);

        // Add class to deactivate element to element
        $this.addClass('ai4seo-element-inactive');
    });
}

// =========================================================================================== \\

function ai4seo_remove_loading_html_from_element($target) {
    // Make sure that element is jquery-element
    $target = ai4seo_normalize_$($target);

    if (!ai4seo_exists_$($target)) {
        ai4seo_console_debug('AI for SEO: element \"$target\" missing in ai4seo_remove_loading_html_from_element() \u2014 cannot remove loading state.');
        return;
    }

    $target.each(function() {
        // Define variable for this element
        const $this = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this)) {
            console.warn('AI for SEO: element \"$this\" missing in ai4seo_remove_loading_html_from_element() \u2014 cannot remove loading state.');
            return;
        }

        // Define variable for the original html-content
        const original_html_content = $this.attr('data-ai-for-seo-original-html-content');

        // Remove data-attribute from element
        $this.removeAttr('data-ai-for-seo-original-html-content');

        // Replace html-content with original html-content
        $this.html(original_html_content);

        // Remove class to deactivate element from element
        $this.removeClass('ai4seo-element-inactive');
    });
}

// ___________________________________________________________________________________________ \\
// === SVG =================================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_get_svg_tag(icon_name, icon_css_class, alt_text) {
    // Make sure that the icon-name is allowed
    if (!ai4seo_svg_icons[icon_name]) {
        return '';
    }

    let svg_tag = ai4seo_svg_icons[icon_name];

    // add css class to svg tag
    if (icon_css_class) {
        icon_css_class = 'ai4seo-icon ' + icon_css_class;
    } else {
        icon_css_class = 'ai4seo-icon';
    }

    svg_tag = svg_tag.replace('<svg', "<svg class='" + icon_css_class + "'");

    // add alt text to svg tag
    if (alt_text) {
        svg_tag = svg_tag.replace('<svg', "<svg aria-label='" + alt_text + "'");
        svg_tag = svg_tag.replace('</svg>', '<title>' + alt_text + '</title></svg>');
    }

    return svg_tag;
}

// ___________________________________________________________________________________________ \\
// === MODALS ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_open_generic_error_notification_modal(error_code = 999, error_message = '', footer = '', modal_settings = {}) {
    if (!error_message) {
        error_message = wp.i18n.sprintf(wp.i18n.__("Please check your settings or <a href='%s' target='_blank'>contact us</a>.", 'ai-for-seo'), ai4seo_official_contact_url);
    }

    let default_headline = wp.i18n.__('An error occurred', 'ai-for-seo');
    let content = error_message + ' (' + wp.i18n.__('error code', 'ai-for-seo') + ': #' + error_code + ')';

    // default notification modal settings
    let default_settings = {
        close_on_outside_click: false,
        add_close_button: false,
        headline: (modal_settings.headline ? modal_settings.headline : default_headline),
        content: content,
    };

    // additional settings for low credits error
    if (error_code === 1115424 || error_code === 1215424) {
        modal_settings.headline = wp.i18n.__('Insufficient Credits', 'ai-for-seo');
        modal_settings.add_close_button = true;
        modal_settings.content = error_message;
        modal_settings.footer = "<a class='ai4seo-button ai4seo-success-button' href='#' onclick='ai4seo_close_all_modals();ai4seo_open_get_more_credits_modal();'>" + wp.i18n.__('Click here to add more Credits', 'ai-for-seo') + "</a>";
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_notification_modal(modal_settings.headline, modal_settings.content, footer, modal_settings);
}

// =========================================================================================== \\

function ai4seo_open_generic_success_notification_modal(content, footer = '', modal_settings = {}) {
    let default_headline = wp.i18n.__('Success!', 'ai-for-seo');

    // Display success message
    let check_icon = ai4seo_get_svg_tag('circle-check', 'ai4seo-big-icon ai4seo-fill-green', wp.i18n.__('Success!', 'ai-for-seo'));
    let default_content = check_icon + '<br>' + wp.i18n.__('The data have been saved successfully.', 'ai-for-seo');

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

function ai4seo_open_notification_modal(headline = '', content = '', footer = '', modal_settings = {}) {
    let modal_id = 'ai4seo-notification-modal';

    let default_footer = "<button type='button' onclick='ai4seo_close_modal(\"" + modal_id + "\")' class='ai4seo-button ai4seo-success-button'>" + wp.i18n.__('Close', 'ai-for-seo') + '</button>';

    // default notification modal settings
    let default_settings = {
        close_on_outside_click: false,
        add_close_button: false,
        modal_css_class: 'ai4seo-notification-modal',
        modal_wrapper_css_class: 'ai4seo-notification-modal-wrapper',
        modal_size: 'small',
        headline: headline,
        content: content,
        footer: (footer ? footer : default_footer),
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_modal_$(modal_id, modal_settings);
}

// =========================================================================================== \\

function ai4seo_close_notification_modal() {
    ai4seo_close_modal('ai4seo-notification-modal');
}

// =========================================================================================== \\

function ai4seo_open_ajax_modal(ajax_action, ajax_data = {}, modal_settings = {}) {
    let modal_id = 'ai4seo-ajax-modal';

    // ajax -> add loading icon to content
    let default_content = "<div class='ai4seo-ajax-modal-loading-icon'>" + ai4seo_get_svg_tag('rotate', 'ai4seo-spinning-icon', wp.i18n.__('Loading... Please wait.', 'ai-for-seo')) + '</div>';

    // default ajax modal settings
    let default_settings = {
        close_on_outside_click: true,
        add_close_button: true,
        modal_css_class: 'ai4seo-ajax-modal',
        modal_wrapper_css_class: 'ai4seo-ajax-modal-wrapper',
        content: default_content,
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    ai4seo_open_modal_$(modal_id, modal_settings);

    if (!ai4seo_get_modal_$(modal_id)) {
        console.error('AI for SEO: Could not open modal with id: ' + modal_id);
        return;
    }

    let $modal = ai4seo_get_modal_$(modal_id);

    // ajax -> perform ajax call
    ai4seo_perform_ajax_call(ajax_action, ajax_data, false)
        .then(response => {
            // check if modal is still open (maybe closed by the user by now)
            if (!ai4seo_get_modal_$(modal_id)) {
                console.error('AI for SEO: Could not find modal with id: ' + modal_id);
                return;
            }

            // on error, set response to error message
            if (typeof response !== 'string') {
                response = wp.i18n.__('An unknown error occurred while loading the modal content.', 'ai-for-seo');
                console.error('AI for SEO: Invalid response received for ajax modal with id: ' + modal_id, response);
            }

            // normalize original response
            let $response = ai4seo_normalize_$(response);

            if (!ai4seo_exists_$($response)) {
                response = '<div>' + response + '</div>';
                $response = ai4seo_normalize_$(response);
            }

            if (!ai4seo_exists_$($response)) {
                console.error('AI for SEO: Could not parse response for ajax modal with id: ' + modal_id, response);
                return;
            }

            // wrap everything into a shared root so removals affect the same DOM tree
            const $response_container = ai4seo_normalize_$('<div class="ai4seo-modal-response-root"></div>');
            $response_container.append($response);

            // find modal headline in response and set it separately
            let $possible_modal_headline = $response_container.find('.ai4seo-modal-headline').first();

            if (ai4seo_exists_$($possible_modal_headline)) {
                let headline_html = $possible_modal_headline.prop('outerHTML');
                $possible_modal_headline.remove();
                ai4seo_set_modal_headline(modal_id, headline_html);
            }

            // find modal sub headline in response and set it separately
            let $possible_modal_sub_headline = $response_container.find('.ai4seo-modal-sub-headline').first();

            if (ai4seo_exists_$($possible_modal_sub_headline)) {
                let sub_headline_html = $possible_modal_sub_headline.prop('outerHTML');
                $possible_modal_sub_headline.remove();
                ai4seo_set_modal_sub_headline(modal_id, sub_headline_html);
            }

            // find footer in response and set it separately
            let $possible_modal_footer = $response_container.find('.ai4seo-modal-footer').first();

            if (ai4seo_exists_$($possible_modal_footer)) {
                let footer_html = $possible_modal_footer.prop('outerHTML');
                $possible_modal_footer.remove();
                ai4seo_set_modal_footer(modal_id, footer_html);
            }

            // find modal content in response and set it separately
            let $possible_modal_content = $response_container.find('.ai4seo-modal-content').first();

            // fallback: if no .ai4seo-modal-content found, use remaining DOM after extractions
            if (!ai4seo_exists_$($possible_modal_content)) {
                const remaining_html = $response_container.html().trim();
                // use the rest as content
                const wrapped_remaining_html = '<div class="ai4seo-modal-content">' + remaining_html + '</div>';
                // clear to avoid re-use side effects
                $response_container.empty();
                ai4seo_set_modal_content(modal_id, wrapped_remaining_html);
            } else {
                let content_html = $possible_modal_content.prop('outerHTML');
                $possible_modal_content.remove();
                ai4seo_set_modal_content(modal_id, content_html);
            }

            // init modal
            ai4seo_init_modal(modal_id, modal_settings.close_on_outside_click);
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1112181225);
            ai4seo_close_modal(modal_id);
        })
        .finally(() => { /* do nothing */});
}

// =========================================================================================== \\

function ai4seo_set_modal_headline(modal_id, headline_html) {
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: Could not find modal with id: ' + modal_id);
        return;
    }

    // remove headline if exists
    let $modal_headline = $modal.find('.ai4seo-modal-headline');

    if (ai4seo_exists_$($modal_headline)) {
        $modal_headline.remove();
    }

    // if headline_html is the .ai4seo-modal-headline element, use it directly
    let $possible_modal_headline = ai4seo_normalize_$(headline_html);

    if (!ai4seo_exists_$($possible_modal_headline) || !$possible_modal_headline.hasClass('ai4seo-modal-headline')) {
        headline_html = '<div class=\"ai4seo-modal-headline\">' + headline_html + '</div>';
    }

    // try to find existing sub-headline, or content to insert headline before. fallback to append
    const $modal_sub_headline = $modal.find('.ai4seo-modal-sub-headline');
    const $modal_content = $modal.find('.ai4seo-modal-content');

    if (ai4seo_exists_$($modal_sub_headline)) {
        $modal_sub_headline.before(headline_html);
    } else if (ai4seo_exists_$($modal_content)) {
        $modal_content.before(headline_html);
    } else {
        $modal.append(headline_html);
    }
}

// =========================================================================================== \\

function ai4seo_set_modal_sub_headline(modal_id, sub_headline_html) {
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: Could not find modal with id: ' + modal_id);
        return;
    }

    // remove headline if exists
    let $modal_sub_headline = $modal.find('.ai4seo-modal-sub-headline');

    if (ai4seo_exists_$($modal_sub_headline)) {
        $modal_sub_headline.remove();
    }

    // if sub_headline_html is the .ai4seo-modal-sub-headline element, use it directly
    let $possible_modal_sub_headline = ai4seo_normalize_$(sub_headline_html);

    if (!ai4seo_exists_$($possible_modal_sub_headline) || !$possible_modal_sub_headline.hasClass('ai4seo-modal-sub-headline')) {
        sub_headline_html = '<div class=\"ai4seo-modal-sub-headline\">' + sub_headline_html + '</div>';
    }

    // try to find existing headline, or content to insert sub-headline after. fallback to append

    const $modal_headline = $modal.find('.ai4seo-modal-headline');
    const $modal_content = $modal.find('.ai4seo-modal-content');

    if (ai4seo_exists_$($modal_headline)) {
        $modal_headline.after(sub_headline_html);
    } else if (ai4seo_exists_$($modal_content)) {
        $modal_content.before(sub_headline_html);
    } else {
        $modal.append(sub_headline_html);
    }
}

// =========================================================================================== \\

function ai4seo_set_modal_content(modal_id, content_html) {
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: Could not find modal with id: ' + modal_id);
        return;
    }

    // remove content if exists
    let $modal_content = $modal.find('.ai4seo-modal-content');

    if (ai4seo_exists_$($modal_content)) {
        $modal_content.remove();
    }

    // if content_html is the .ai4seo-modal-content element, use it directly
    let $possible_modal_content = ai4seo_normalize_$(content_html);

    if (!ai4seo_exists_$($possible_modal_content) || !$possible_modal_content.hasClass('ai4seo-modal-content')) {
        content_html = '<div class=\"ai4seo-modal-content\">' + content_html + '</div>';
    }

    // try to find existing sub-headline, headline or footer to insert content between. fallback to append
    const $modal_sub_headline = $modal.find('.ai4seo-modal-sub-headline');
    const $modal_headline = $modal.find('.ai4seo-modal-headline');
    const $modal_footer = $modal.find('.ai4seo-modal-footer');

    if (ai4seo_exists_$($modal_sub_headline)) {
        $modal_sub_headline.after(content_html);
    } else if (ai4seo_exists_$($modal_headline)) {
        $modal_headline.after(content_html);
    } else if (ai4seo_exists_$($modal_footer)) {
        $modal_footer.before(content_html);
    } else {
        $modal.append(content_html);
    }
}

// =========================================================================================== \\

function ai4seo_set_modal_footer(modal_id, footer_html) {
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: Could not find modal with id: ' + modal_id);
        return;
    }

    // remove footer if exists
    let $modal_footer = $modal.find('.ai4seo-modal-footer');

    if (ai4seo_exists_$($modal_footer)) {
        $modal_footer.remove();
    }

    // if footer_html is the .ai4seo-modal-footer element, use it directly
    let $possible_modal_footer = ai4seo_normalize_$(footer_html);

    if (!ai4seo_exists_$($possible_modal_footer) || !$possible_modal_footer.hasClass('ai4seo-modal-footer')) {
        footer_html = '<div class=\"ai4seo-modal-footer\">' + footer_html + '</div>';
    }

    // append footer to modal
    $modal.append(footer_html);
}


// =========================================================================================== \\

function ai4seo_close_ajax_modal() {
    ai4seo_close_modal('ai4seo-ajax-modal');
}

// =========================================================================================== \\

function ai4seo_open_modal_from_schema(modal_schema_identifier, modal_settings = {}) {
    let $modal_schema = ai4seo_normalize_$('.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier);

    if (!ai4seo_exists_$($modal_schema)) {
        console.error('AI for SEO: Could not find modal schema with id: ' + modal_schema_identifier);
        return;
    }

    // find headline, content and footer
    let default_settings = {};

    // find and remove headline from schema
    let modal_schema_headline = $modal_schema.find('.ai4seo-modal-schema-headline');

    if (ai4seo_exists_$(modal_schema_headline)) {
        default_settings['headline'] = modal_schema_headline.html();
        modal_schema_headline.html('');
    }

    // find content and remove it from schema
    let modal_schema_content = $modal_schema.find('.ai4seo-modal-schema-content');

    if (ai4seo_exists_$(modal_schema_content)) {
        default_settings['content'] = modal_schema_content.html();
        modal_schema_content.html('');
    }

    // find footer and remove it from schema
    let modal_schema_footer = $modal_schema.find('.ai4seo-modal-schema-footer');

    if (ai4seo_exists_$(modal_schema_footer)) {
        default_settings['footer'] = modal_schema_footer.html();
        modal_schema_footer.html('');
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    // open modal
    let modal_id = 'ai4seo-' + modal_schema_identifier;
    let $modal = ai4seo_open_modal_$(modal_id, modal_settings);

    // add schema identifier to modal
    $modal.data('ai4seo-modal-schema-identifier', modal_schema_identifier);
    
    // Initialize datetime picker functionality for SEO Autopilot modal
    if (modal_schema_identifier === 'seo-autopilot') {
        ai4seo_handle_bulk_generation_new_or_existing_filter_change();
    }
}

// =========================================================================================== \\

function ai4seo_close_modal_from_schema(modal_schema_identifier) {
    ai4seo_close_modal('ai4seo-' + modal_schema_identifier);
}

// =========================================================================================== \\

function ai4seo_open_modal_$(modal_id, modal_settings = {}) {
    // === PREPARE PARAMETERS ================================================================ \\

    if (!modal_id) {
        modal_id = 'ai4seo-modal';
    }

    // default settings
    let default_settings = {
        close_on_outside_click: true,
        add_close_button: true,
        modal_css_class: '',
        modal_wrapper_css_class: '',
        headline_icon: 'default',
        headline: '',
        content: '',
        footer: '',
        modal_size: 'medium', // small, medium, large, auto
    }

    // merge settings
    modal_settings = Object.assign({}, default_settings, modal_settings);

    // define default headline icon
    if (modal_settings.headline_icon === 'default') {
        modal_settings.headline_icon = "<img src='" + ai4seo_get_asset_url('images/logos/ai-for-seo-logo-64x64.png') + "' class='ai4seo-logo' alt='AI for SEO' />";
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
    const $existing_modal_candidate = ai4seo_get_modal_$(modal_id);

    if (ai4seo_exists_$($existing_modal_candidate)) {
        ai4seo_close_modal(modal_id);
    }

    // create empty modal
    let $modal = ai4seo_create_empty_modal_$(modal_id, modal_settings.modal_css_class, modal_settings.modal_wrapper_css_class, modal_settings.modal_size);

    if (!$modal) {
        return;
    }

    // === ADD CONTENTS ================================================================================== \\

    // add close button
    if (modal_settings.add_close_button) {
        $modal.append("<div class='ai4seo-modal-close-icon' onclick='ai4seo_close_modal(\"" + modal_id + "\")'>" + ai4seo_get_svg_tag('square-xmark', '', wp.i18n.__('Close modal', 'ai-for-seo')) + '</div>');
    }

    // set headline
    if (modal_settings.headline) {
        // also check if there is not already a headline icon
        if (modal_settings.headline_icon && !modal_settings.headline.includes('ai4seo-modal-headline-icon')) {
            modal_settings.headline = "<div class='ai4seo-modal-headline-icon'>" + modal_settings.headline_icon + '</div>' + modal_settings.headline;
        }

        $modal.append("<div class='ai4seo-modal-headline'>" + modal_settings.headline + '</div>');
    }

    // set content
    if (modal_settings.content) {
        $modal.append("<div class='ai4seo-modal-content'>" + modal_settings.content + '</div>');
    }

    // set footer
    if (modal_settings.footer) {
        $modal.append("<div class='ai4seo-modal-footer ai4seo-buttons-wrapper'>" + modal_settings.footer + '</div>');
    }

    // add functions to modal
    ai4seo_init_modal(modal_id, modal_settings.close_on_outside_click);

    return $modal;
}

// =========================================================================================== \\

function ai4seo_init_modal(modal_id, close_on_outside_click) {
    if (!ai4seo_get_modal_$(modal_id)) {
        return;
    }

    let $modal = ai4seo_get_modal_$(modal_id);

    // close on outside click?
    if (close_on_outside_click) {
        // keep track of the mousedown origin, to only close the modal, if the mouseup event is on the wrapper too
        // to prevent closing the layer while dragging the mouse from inside the modal to outside while selecting
        // text for example
        let $modal_wrapper = ai4seo_get_modal_wrapper_$(modal_id);

        if ($modal_wrapper && $modal_wrapper.length) {
            $modal_wrapper
                .off('mousedown.ai4seo-modal')
                .on('mousedown.ai4seo-modal', function(event) {
                    ai4seo_mousedown_origin = event.target;
                });

            $modal_wrapper
                .off('mouseup.ai4seo-modal')
                .on('mouseup.ai4seo-modal', function(event) {
                    if (event.target === ai4seo_mousedown_origin) {
                        ai4seo_close_modal(modal_id);
                    }
                });
        }
    }

    for (let i = 0; i <= 1000; i += 250) {
        setTimeout(function() {
            // init html elements
            ai4seo_init_html_elements();

            // Vertically center modal on screen, if modal_elements height is smaller than 80% of screen height
            if ($modal.outerHeight() < jQuery(window).height() * 0.80) {
                $modal.css({
                    'top': '50%',
                    'margin-top': -$modal.outerHeight() / 2 - 50, // 50px buffer
                });
            } else {
                $modal.css({
                    'top': '3rem',
                    'margin-top': 0,
                });
            }
        }, i);
    }
}

// =========================================================================================== \\

function ai4seo_create_empty_modal_$(modal_id, modal_css_class, modal_wrapper_css_class, modal_size) {
    // get highest z-index of all modal wrappers
    let previous_highest_z_index = ai4seo_get_highest_modal_wrapper_z_index();

    // add modal css class
    if (modal_css_class) {
        modal_css_class = 'ai4seo-modal ' + modal_css_class;
    } else {
        modal_css_class = 'ai4seo-modal';
    }

    // add modal wrapper css class
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
    // AND disable scroll on body-element
    const $body = ai4seo_normalize_$('body');

    if (ai4seo_exists_$($body)) {
        $body
            .append("<div class='" + modal_wrapper_css_class + "'><div class='" + modal_css_class + "' id='" + modal_id + "'></div></div>")
            .addClass('ai4seo-has-open-modal');
    }

    // check for the modal tags
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!$modal) {
        console.error('AI for SEO: Could not create modal with id: ' + modal_id);
        return;
    }

    let $modal_wrapper = ai4seo_get_modal_wrapper_$(modal_id);

    if (!$modal_wrapper) {
        console.error('AI for SEO: Could not create modal wrapper for modal with id: ' + modal_id);
        return;
    }

    // Workaround: add stop propagation to modal to prevent closing when clicking inside the modal
    $modal
        .off('mouseup.ai4seo-modal')
        .on('mouseup.ai4seo-modal', function(event) {
            event.stopPropagation();
        });

    $modal.click(function(event) {
        event.stopPropagation();
    });

    // Workaround: if there was a highest z index, add 1 to it
    if (previous_highest_z_index) {
        previous_highest_z_index++;

        $modal_wrapper.css('z-index', previous_highest_z_index);
    }

    return $modal;
}

// =========================================================================================== \\

function ai4seo_get_highest_modal_wrapper_z_index() {
    let highest_z_index = 0;

    const $modal_wrappers = ai4seo_normalize_$('.ai4seo-modal-wrapper');

    if (!ai4seo_exists_$($modal_wrappers)) {
        ai4seo_console_debug('AI for SEO: elements \"$modal_wrappers\" missing in ai4seo_get_highest_modal_wrapper_z_index() \u2014 cannot resolve modal stacking context.');
        return highest_z_index;
    }

    $modal_wrappers.each(function() {
        const $this_modal_wrapper = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_modal_wrapper)) {
            console.warn('AI for SEO: element \"$this_modal_wrapper\" missing in ai4seo_get_highest_modal_wrapper_z_index() \u2014 cannot resolve modal stacking context.');
            return;
        }

        let z_index = $this_modal_wrapper.css('z-index');

        if (z_index > highest_z_index) {
            highest_z_index = z_index;
        }
    });

    return highest_z_index;
}

// =========================================================================================== \\

function ai4seo_get_modal_wrapper_$(modal_id) {
    const $modal = ai4seo_normalize_$('#' + modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: element \"$modal\" missing in ai4seo_get_modal_wrapper_$() \u2014 cannot resolve modal wrapper.');
        return null;
    }

    return $modal.parent('.ai4seo-modal-wrapper');
}

// =========================================================================================== \\

function ai4seo_get_modal_$(modal_id) {
    if (ai4seo_exists_$('#' + modal_id)) {
        return ai4seo_normalize_$('#' + modal_id);
    } else {
        // return empty jQuery object
        return null;
    }
}

// =========================================================================================== \\

function ai4seo_close_modal(modal_id) {
    let $modal = ai4seo_get_modal_$(modal_id);

    if (!ai4seo_exists_$($modal)) {
        console.error('AI for SEO: element \"$modal\" missing in ai4seo_close_modal() \u2014 modal lifecycle interrupted.');
        return;
    }

    // check for modal-schema-identifier data -> put data back to schema
    if ($modal.data('ai4seo-modal-schema-identifier')) {
        let modal_schema_identifier = $modal.data('ai4seo-modal-schema-identifier');

        // put back headline, content and footer to the schema
        if (ai4seo_exists_$('.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier)) {
            let $modal_schema = ai4seo_normalize_$('.ai4seo-modal-schemas-container > #ai4seo-modal-schema-' + modal_schema_identifier);

            // find headline
            if (ai4seo_exists_$($modal.find('.ai4seo-modal-headline')) && ai4seo_exists_$($modal_schema.find('.ai4seo-modal-schema-headline'))) {
                $modal_schema.find('.ai4seo-modal-schema-headline').html($modal.find('.ai4seo-modal-headline').html());
            }

            // find content
            if (ai4seo_exists_$($modal.find('.ai4seo-modal-content')) && ai4seo_exists_$($modal_schema.find('.ai4seo-modal-schema-content'))) {
                $modal_schema.find('.ai4seo-modal-schema-content').html($modal.find('.ai4seo-modal-content').html());
            }

            // find footer
            if (ai4seo_exists_$($modal.find('.ai4seo-modal-footer')) && ai4seo_exists_$($modal_schema.find('.ai4seo-modal-schema-footer'))) {
                $modal_schema.find('.ai4seo-modal-schema-footer').html($modal.find('.ai4seo-modal-footer').html());
            }
        }
    }

    if (ai4seo_get_modal_wrapper_$(modal_id)) {
        ai4seo_get_modal_wrapper_$(modal_id).remove();
    }

    // no more ai4seo-modal -> enable scroll on body-element
    const $modals = ai4seo_normalize_$('.ai4seo-modal');

    if (!ai4seo_exists_$($modals)) {
        ai4seo_console_debug('AI for SEO: No modals open in ai4seo_close_modal() — clearing body modal state.');
        const $body = ai4seo_normalize_$('body');

        if (ai4seo_exists_$($body)) {
            $body.removeClass('ai4seo-has-open-modal');
        }
    }
}

// =========================================================================================== \\

function ai4seo_close_modal_by_child($child) {
    $child = ai4seo_normalize_$($child);

    if (!ai4seo_exists_$($child)) {
        console.error('AI for SEO: element \"$child\" missing in ai4seo_close_modal_by_child() \u2014 cannot locate parent modal.');
        return;
    }

    // is modal_id a reference element like a button? find the modal_id
    const $closest_modal = $child.closest('.ai4seo-modal');

    if (!ai4seo_exists_$($closest_modal)) {
        console.error('AI for SEO: element \"$closest_modal\" missing in ai4seo_close_modal_by_child() \u2014 cannot locate parent modal.');
        return;
    }

    let modal_id = $closest_modal.attr('id');

    ai4seo_close_modal(modal_id);
}

// =========================================================================================== \\

function ai4seo_close_all_modals() {
    const $modals = ai4seo_normalize_$('.ai4seo-modal');

    if (!ai4seo_exists_$($modals)) {
        ai4seo_console_debug('AI for SEO: no modals are open or \"$modals\" missing in ai4seo_close_all_modals() \u2014 no modals to close.');
        return;
    }

    $modals.each(function() {
        const $this_modal = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_modal)) {
            console.error('AI for SEO: element \"$this_modal\" missing in ai4seo_close_all_modals() \u2014 modal lifecycle interrupted.');
            return;
        }

        let modal_id = $this_modal.attr('id');

        ai4seo_close_modal(modal_id);
    });
}

// =========================================================================================== \\

function ai4seo_open_metadata_editor_modal(post_id = false, read_page_content_via_js = false, all_post_ids = []) {
    // Read post-id from hidden container if not defined
    if (!post_id) {
        post_id = ai4seo_get_post_id();
    }

    if (!post_id) {
        ai4seo_show_generic_error_toast(26173424);
        return;
    }

    // CURRENT POST'S CONTENT
    let post_content = '';

    // Define variable for the content based on ai4seo_get_post_content()
    if (read_page_content_via_js) {
        post_content = ai4seo_get_post_content();
    }

    let parameters = {
        post_id: post_id,
        read_page_content_via_js: read_page_content_via_js,
        content: post_content,
        all_post_ids: all_post_ids,
    }

    ai4seo_open_ajax_modal('ai4seo_show_metadata_editor', parameters);
}

// =========================================================================================== \\

function ai4seo_open_attachment_attributes_editor_modal(attachment_post_id = false, all_attachment_post_ids = []) {
    // Read post-id from hidden container if not defined
    if (!attachment_post_id) {
        ai4seo_open_notification_modal(241920824);
        return;
    }

    // PARAMETERS
    let parameters = {
        attachment_post_id: attachment_post_id,
        all_attachment_post_ids: all_attachment_post_ids,
    }

    ai4seo_open_ajax_modal('ai4seo_show_attachment_attributes_editor', parameters);
}

// =========================================================================================== \\

function ai4seo_safe_page_load(subpage = '', additional_url_parameter = {}) {
    // if inside elementor or gutenberg editor, do not reload, close all modals instead
    if (ai4seo_is_inside_elementor_editor() || ai4seo_is_inside_gutenberg_editor() || ai4seo_is_inside_muffin_builder_editor()) {
        ai4seo_close_all_modals();
        return;
    }

    // check if subpage is a string an contains only of [a-z0-9_-]
    if (subpage && !/^[a-z0-9_-]+$/i.test(subpage)) {
        ai4seo_console_debug('AI for SEO: Invalid subpage identifier provided in ai4seo_safe_page_load() \u2014 aborting page load.', subpage);
        subpage = '';
    }

    // check if additional_url_parameter is an object
    if (additional_url_parameter && typeof additional_url_parameter !== 'object') {
        ai4seo_console_debug('AI for SEO: Invalid additional_url_parameter provided in ai4seo_safe_page_load() \u2014 aborting page load.', additional_url_parameter);
        additional_url_parameter = {};
    }

    // decide if we reload the page or go to a specific subpage
    if (subpage || !jQuery.isEmptyObject(additional_url_parameter)) {
        window.location.href = ai4seo_build_custom_admin_url(subpage, additional_url_parameter);
    } else {
        ai4seo_reload_page();
    }

    // show full page loading screen
    ai4seo_show_full_page_loading_screen();
}

// =========================================================================================== \\

function ai4seo_show_full_page_loading_screen() {
    // set opacity of .ai4seo-wrap to .7 and non clickable
    const $wrap = ai4seo_normalize_$('.ai4seo-wrap');

    if (ai4seo_exists_$($wrap)) {
        $wrap.css({
            'opacity': '0.7',
            'pointer-events': 'none',
        });
    }

    // set opacity of all ai4seo-modals to .7 and non-clickable
    const $modals = ai4seo_normalize_$('.ai4seo-modal');

    if (ai4seo_exists_$($modals)) {
        $modals.css({
            'opacity': '0.7',
            'pointer-events': 'none',
        });
    }

    const $body = ai4seo_normalize_$('body');

    if (ai4seo_exists_$($body)) {
        // add loading icon in the middle of the screen
        const loading_icon = "<div class='ai4seo-full-screen-loading-icon'>" + ai4seo_get_svg_tag('rotate', 'ai4seo-spinning-icon', wp.i18n.__('Loading... Please wait.', 'ai-for-seo')) + '</div>';
        $body.append(loading_icon);
        $body.css('overflow', 'hidden');
    }
}

// =========================================================================================== \\

function ai4seo_get_all_input_values_in_container($form_container) {
    // Define variable for the form-holder-element based on the form-holder-selector
    $form_container = ai4seo_normalize_$($form_container);

    // Stop script if form-holder-element could not be found
    if (!ai4seo_exists_$($form_container)) {
        console.error('AI for SEO: container \"$form_container\" missing in ai4seo_get_all_input_values_in_container() \u2014 cannot collect input values.');
        return false;
    }

    // Find form-elements within the form-holder-element
    let input_elements = $form_container.find('input, select, textarea');
    let input_values = {};
    let this_input_selector;
    let $this_input;
    let this_input_value;
    let $this_all_matching_inputs;
    let this_input_element_name = false;
    let already_processed_element_names = [];

    // Collect identifier (to prevent analysing the same checkbox or radio-name)
    for (let i = 0; i < input_elements.length; i++) {
        $this_input = input_elements[i];
        this_input_element_name = (typeof $this_input.name !== 'undefined') ? $this_input.name : false;

        if (!this_input_element_name) {
            continue;
        }

        if (already_processed_element_names.includes(this_input_element_name)) {
            continue;
        }

        already_processed_element_names.push(this_input_element_name);

        this_input_selector = "[name='" + this_input_element_name + "']";

        let $this_all_matching_inputs = ai4seo_normalize_$(this_input_selector);

        if (!ai4seo_exists_$($this_all_matching_inputs)) {
            console.warn ('AI for SEO: no matching inputs for selector \"' + this_input_selector + '\" found in ai4seo_get_all_input_values_in_container() \u2014 skipping input.');
            continue;
        }

        this_input_value = ai4seo_get_input_value($this_all_matching_inputs);

        if (typeof this_input_value === 'undefined') {
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
    // Define variable for the interface-pinned-items element within the edit-post-header-toolbar
    const $header_bar_buttons_container = ai4seo_normalize_$('.edit-post-header .interface-pinned-items');

    // Make sure the header_bar_buttons_container exists
    if (!ai4seo_exists_$($header_bar_buttons_container)) {
        ai4seo_console_debug('AI for SEO: no interface pinned items found in ai4seo_add_open_edit_metadata_modal_button_to_edit_page_header() \u2014 cannot add toolbar button.');
        return;
    }

    // remove old button
    const $existing_header_button = ai4seo_normalize_$('.ai4seo-header-builder-button');

    if (ai4seo_exists_$($existing_header_button)) {
        $existing_header_button.remove();
    }

    // Read post-id from hidden container if not defined
    const post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // Generate output
    let output = '';

    // Add button to output
    output += "<button type=\"button\" class=\"components-button has-icon ai4seo-header-builder-button\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-24x24-icon'>";
    output += '</button>';

    // Add button to header_bar_buttons_container
    $header_bar_buttons_container.append(output);
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() {
    // Define variable for the seo-title-element within the be-builder-navigation
    const $seo_title_container = ai4seo_normalize_$('.mfn-meta-seo-title');

    // Make sure the seo_title_container exists
    if (!ai4seo_exists_$($seo_title_container)) {
        //ai4seo_console_debug('AI for SEO: selector \".mfn-meta-seo-title\" no match in ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() \u2014 skipping toolbar injection.');
        return
    }

    ai4seo_console_debug('AI for SEO: selector \".mfn-meta-seo-title\" found in ai4seo_add_open_edit_metadata_modal_button_to_be_builder_navigation() \u2014 injecting toolbar button.');

    // Read post-id from hidden container if not defined
    const post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // check if we have a ai4seo-show-all-seo-settings-button already before seo_title_container
    if (ai4seo_exists_$('.ai4seo-show-all-seo-settings-button')) {
        return;
    }

    // Generate output
    let output = '';

    // Add button to output
    output += "<button type=\"button\" class=\"ai4seo-button ai4seo-generate-button ai4seo-show-all-seo-settings-button ai4seo-lockable\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> ";
        output += wp.i18n.__('Show all SEO settings', 'ai-for-seo');
    output += '</button>';

    // Add button to seo_title_container
    $seo_title_container.before(output);
}

// =========================================================================================== \\

function ai4seo_add_open_edit_metadata_modal_button_to_elementor_navigation() {
    // Read post-id from hidden container if not defined
    const post_id = ai4seo_get_post_id();

    // Make sure post_id is defined
    if (!post_id) {
        return;
    }

    // check if we have a ai4seo-show-all-seo-settings-button already before seo_title_container
    if (ai4seo_exists_$('.ai4seo-show-all-seo-settings-button')) {
        return;
    }

    // Generate output
    let output = '';

    // Add button to output
    output += "<button type=\"button\" class=\"ai4seo-button ai4seo-generate-button ai4seo-show-all-seo-settings-button ai4seo-lockable\" aria-label=\"AI for SEO\" title=\"AI for SEO\" onclick='ai4seo_open_metadata_editor_modal(" + post_id + ", true);'>";
        output += "<img src='" + ai4seo_get_ai4seo_plugin_directory_url() + "/assets/images/logos/ai-for-seo-logo-32x32.png' class='ai4seo-icon ai4seo-button-icon ai4seo-logo'> ";
        output += wp.i18n.__('Show all SEO settings', 'ai-for-seo');
    output += '</button>';

    // Make sure that at least one of the elementor-elements can be found
    if (ai4seo_exists_$('#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items')) {
        // Define variable for the first elementor-panel-menu-group-element within the elementor-navigation
        const $first_elementor_panel_menu_group_container = ai4seo_normalize_$('#elementor-panel-page-menu-content .elementor-panel-menu-group:first-child .elementor-panel-menu-items');

        // Add button to first_elementor_panel_menu_group_container
        $first_elementor_panel_menu_group_container.append(output);
    }

    if (ai4seo_exists_$('#elementor-panel-page-settings-controls')) {
        // Define variable for the container of the elementor panel page settings controls
        const $elementor_panel_page_settings_controls = ai4seo_normalize_$('#elementor-panel-page-settings-controls');

        // Add button to elementor panel page settings controls
        $elementor_panel_page_settings_controls.prepend(output);
    }
}

// =========================================================================================== \\

function ai4seo_validate_metadata_editor_inputs(input_values) {
    const raw_length_map = ai4seo_get_localization_parameter('ai4seo_max_editor_input_lengths') || {};
    const length_map = (typeof raw_length_map === 'object' && raw_length_map !== null) ? raw_length_map : {};
    const fallback_length = ai4seo_normalize_length(length_map.fallback, 512);

    const focus_keyphrase_value = input_values['ai4seo_metadata_focus-keyphrase'] || '';
    const meta_title_value = input_values['ai4seo_metadata_meta-title'] || '';
    const meta_description_value = input_values['ai4seo_metadata_meta-description'] || '';
    const keywords_value = input_values['ai4seo_metadata_keywords'] || '';
    const facebook_title_value = input_values['ai4seo_metadata_facebook-title'] || '';
    const facebook_description_value = input_values['ai4seo_metadata_facebook-description'] || '';
    const twitter_title_value = input_values['ai4seo_metadata_twitter-title'] || '';
    const twitter_description_value = input_values['ai4seo_metadata_twitter-description'] || '';

    if (!ai4seo_validate_editor_input_length(focus_keyphrase_value, 'focus-keyphrase', length_map, fallback_length, ai4seo_metadata_labels['focus-keyphrase'], 1916141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(meta_title_value, 'meta-title', length_map, fallback_length, ai4seo_metadata_labels['meta-title'], 2016141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(meta_description_value, 'meta-description', length_map, fallback_length, ai4seo_metadata_labels['meta-description'], 2116141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(keywords_value, 'keywords', length_map, fallback_length, ai4seo_metadata_labels['keywords'], 2216141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(facebook_title_value, 'facebook-title', length_map, fallback_length, ai4seo_metadata_labels['facebook-title'], 2316141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(facebook_description_value, 'facebook-description', length_map, fallback_length, ai4seo_metadata_labels['facebook-description'], 2416141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(twitter_title_value, 'twitter-title', length_map, fallback_length, ai4seo_metadata_labels['twitter-title'], 2516141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(twitter_description_value, 'twitter-description', length_map, fallback_length, ai4seo_metadata_labels['twitter-description'], 2616141025)) {
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_validate_attachment_attributes_editor_inputs(input_values) {
    const raw_length_map = ai4seo_get_localization_parameter('ai4seo_max_editor_input_lengths') || {};
    const length_map = (typeof raw_length_map === 'object' && raw_length_map !== null) ? raw_length_map : {};
    const fallback_length = ai4seo_normalize_length(length_map.fallback, 512);

    const title_value = input_values['ai4seo_attachment_attribute_title'] || '';
    const alt_text_value = input_values['ai4seo_attachment_attribute_alt-text'] || '';
    const caption_value = input_values['ai4seo_attachment_attribute_caption'] || '';
    const description_value = input_values['ai4seo_attachment_attribute_description'] || '';

    if (!ai4seo_validate_editor_input_length(title_value, 'title', length_map, fallback_length, ai4seo_attachment_attribute_labels['title'], 2716141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(alt_text_value, 'alt-text', length_map, fallback_length, ai4seo_attachment_attribute_labels['alt-text'], 2816141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(caption_value, 'caption', length_map, fallback_length, ai4seo_attachment_attribute_labels['caption'], 2916141025)) {
        return false;
    }

    if (!ai4seo_validate_editor_input_length(description_value, 'description', length_map, fallback_length, ai4seo_attachment_attribute_labels['description'], 3016141025)) {
        return false;
    }

    return true;
}


// ___________________________________________________________________________________________ \\
// === SAVE ANYTHING ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_save_anything($submit_button, validation_function, success_function, error_function) {
    $submit_button = ai4seo_normalize_$($submit_button);

    // check if $submit exists
    if (!ai4seo_exists_$($submit_button)) {
        console.error('AI for SEO: $submit_button does not exist.');
        return;
    }

    // find a form container nearby
    let $closest_form_container = ai4seo_find_closest_form_container_$($submit_button);

    $closest_form_container = ai4seo_normalize_$($closest_form_container);

    // if still not found, return error
    if (!ai4seo_exists_$($closest_form_container)) {
        console.error('AI for SEO: $closest_form_container does not exist.');
        return;
    }

    // get all input values from form_container
    let input_values = ai4seo_get_all_input_values_in_container($closest_form_container);

    if (validation_function) {
        if (!validation_function(input_values)) {
            return;
        }
    }

    // workaround for empty arrays: go through each ai4seo_ajax_data element and convert empty arrays to #ai4seo-empty-array#
    for (let key in input_values) {
        if (Array.isArray(input_values[key]) && input_values[key].length === 0) {
            input_values[key] = '#ai4seo-empty-array#';
        }
    }

    // add loading html to $submit
    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Saving your data now...', 'ai-for-seo'));

    // Perform ajax action
    ai4seo_perform_ajax_call('ai4seo_save_anything', input_values)
        .then(response => {
            // Display success message
            ai4seo_show_generic_saved_successfully_toast();

            // scroll to top of page
            window.scrollTo(0, 0);

            // perform success function
            if (success_function) {
                success_function(response);
            }
        })
        .catch(error => {
            // Hint: error modal will be shown dynamically, due to the auto error handler
            ai4seo_show_generic_error_toast(1212181225);

            // perform error function
            if (error_function) {
                error_function(error, response);
            }
        })
        .finally(() => {
            // Remove loading-html from submit-element
            ai4seo_remove_loading_html_from_element($submit_button);
            ai4seo_unlock_and_enable_lockable_input_fields();
        });
}

// =========================================================================================== \\

function ai4seo_find_closest_form_container_$($reference) {
    $reference = ai4seo_normalize_$($reference);

    // Check if $reference exists
    if (!ai4seo_exists_$($reference)) {
        console.error('AI for SEO: $reference does not exist.');
        return false;
    }

    //check if the reference element is actually a .ai4seo-form
    if ($reference.hasClass('ai4seo-form')) {
        return $reference;
    }

    // Array of selectors to check
    let check_elements = ['.ai4seo-form', '.ai4seo-modal', '.ai4seo-content-wrapper'];

    // Loop through selectors using for...of, which supports early exit
    for (let element of check_elements) {
        let $form_container = $reference.closest(element);

        if (ai4seo_exists_$($form_container)) {
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

    // make sure that both fields are empty or both filled
    if ((api_username.length === 0 && api_password.length > 0) || (api_username.length > 0 && api_password.length === 0)) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter both license owner and license key, or leave both fields empty.', 'ai-for-seo'));
        return false;
    }

    // check api username and password lengths
    if (api_username.length > 128) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid license owner (max. 128 characters).', 'ai-for-seo'));
        return false;
    }

    if (api_password.length > 0 && api_password.length !== 48) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid license key (48 characters).', 'ai-for-seo'));
        return false;
    }

    // Check the length of the plugin-name
    if (installed_plugins_plugin_name.length < 3 || installed_plugins_plugin_name.length > 100) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid plugin name (3-100 characters).', 'ai-for-seo'));
        return false;
    }

    // Check the length of the plugin-description
    if (installed_plugins_plugin_description.length < 3 || installed_plugins_plugin_description.length > 140) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid plugin description (3-140 characters).', 'ai-for-seo'));
        return false;
    }

    // Check the length of the source-code-notes-content-start
    if (meta_tags_block_starting_hint.length < 3 || meta_tags_block_starting_hint.length > 250) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid meta tag block starting hint (3-250 characters).', 'ai-for-seo'));
        return false;
    }

    // Check the length of the source-code-notes-content-end
    if (meta_tags_block_ending_hint.length < 3 || meta_tags_block_ending_hint.length > 250) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid meta tag block ending hint (3-250 characters).', 'ai-for-seo'));
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_init_license_form() {
    const $white_label_checkbox = ai4seo_normalize_$('#ai4seo-enable-white-label');
    const $white_label_container = ai4seo_normalize_$('.ai4seo-white-label-only-container');
    const $source_code_checkbox = ai4seo_normalize_$('#ai4seo-display-source-code-notes');
    const $source_code_container = ai4seo_normalize_$('.ai4seo-source-code-adjustments-only-container');

    if (!ai4seo_exists_$($white_label_checkbox) || !ai4seo_exists_$($white_label_container) || !ai4seo_exists_$($source_code_checkbox) || !ai4seo_exists_$($source_code_container)) {
        //ai4seo_console_debug('AI for SEO: White-label license controls missing in ai4seo_init_license_form() — cannot bind visibility toggles.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Initializing license form visibility toggles.');

    ai4seo_toggle_visibility_on_checkbox($white_label_checkbox, $white_label_container);
    ai4seo_toggle_visibility_on_checkbox($source_code_checkbox, $source_code_container);
}

// =========================================================================================== \\

function ai4seo_toggle_visibility_on_checkbox($checkbox, $target, visible_on_checked = true) {
    $checkbox = ai4seo_normalize_$($checkbox);
    $target = ai4seo_normalize_$($target);

    // Stop script if selector_checkbox or selector_target could not be found
    if (!ai4seo_exists_$($checkbox) || !ai4seo_exists_$($target)) {
        console.warn('AI for SEO: selector_checkbox or selector_target missing in ai4seo_toggle_visibility_on_checkbox() — cannot toggle visibility.');
        return;
    }

    // Check if the white-label-settings should be shown
    if ($checkbox.is(':checked')) {
        if (visible_on_checked) {
            $target.removeClass('ai4seo-display-none');
        } else {
            $target.addClass('ai4seo-display-none');
        }
    } else {
        if (visible_on_checked) {
            $target.addClass('ai4seo-display-none');
        } else {
            $target.removeClass('ai4seo-display-none');
        }
    }
}

// =========================================================================================== \\

// Function to display lost-key-modal
function ai4seo_open_lost_key_modal() {
    // Define variables for the modal
    let modal_headline = wp.i18n.__('Lost your license data?', 'ai-for-seo');
    let modal_content = "<div class='ai4seo-form-item'>";
    modal_content += wp.i18n.__('Please enter the same email address used during Stripe checkout. You can check your order confirmation email for the correct address.', 'ai-for-seo');
    modal_content += "<br><br>";
    modal_content += "<div class='ai4seo-form-item-input-wrapper'>";
    modal_content += "<input type='email' id='ai4seo-lost-licence-email' class='ai4seo-textfield' placeholder='" + wp.i18n.__('Enter your email address', 'ai-for-seo') + "' />";
    modal_content += '</div>';
    modal_content += '</div>';
    
    let modal_footer = "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('Cancel', 'ai-for-seo') + '</button> ';
    modal_footer += "<button type='button' id='ai4seo-lost-licence-submit' class='button ai4seo-button ai4seo-success-button' onclick='ai4seo_request_lost_licence_data(this);'>" + wp.i18n.__('Send License Data', 'ai-for-seo') + '</button>';
    
    let modal_settings = {
        close_on_outside_click: true,
        add_close_button: true,
    }

    // Open notification modal
    ai4seo_open_notification_modal(modal_headline, modal_content, modal_footer, modal_settings);
}

// =========================================================================================== \\

// Function to request lost licence data
function ai4seo_request_lost_licence_data($submit_button) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        console.warn('AI for SEO: element \"$submit\" missing in ai4seo_request_lost_licence_data() \u2014 cannot request licence recovery.');
        return;
    }

    // Get email value
    const $lost_licence_email = ai4seo_normalize_$('#ai4seo-lost-licence-email');

    if (!ai4seo_exists_$($lost_licence_email)) {
        console.warn('AI for SEO: element \"$lost_licence_email\" missing in ai4seo_request_lost_licence_data() \u2014 cannot request licence recovery.');
        return;
    }

    let email = $lost_licence_email.val();
    
    // Validate email
    if (!email || email.length < 3 || !email.includes('@')) {
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid email address.', 'ai-for-seo'));
        return;
    }
    
    // Add loading state to submit button
    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_lock_and_disable_lockable_input_fields();
    
    // Prepare AJAX data
    let ajax_data = {
        stripe_email: email
    };

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Requesting license data...', 'ai-for-seo'));

    // Perform AJAX call
    ai4seo_perform_ajax_call('ai4seo_request_lost_licence_data', ajax_data, true, {}, true)
        .then(response => {
            // Always show success confirmation regardless of API response
            let confirmation_message = wp.i18n.__('If this email address is linked to a Stripe order for AI for SEO, you will receive an email with your licence data within the next 60 seconds.', 'ai-for-seo');
            let confirmation_headline = wp.i18n.__('Request Sent', 'ai-for-seo');
            let confirmation_footer = "<button type='button' class='button ai4seo-button ai4seo-success-button' onclick='ai4seo_close_all_modals();ai4seo_safe_page_load(\"account\")'>" + wp.i18n.__('OK', 'ai-for-seo') + '</button>';
            
            ai4seo_open_notification_modal(confirmation_headline, confirmation_message, confirmation_footer, {close_on_outside_click: false, add_close_button: false});
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1312181225);
        })
        .finally(() => {
            ai4seo_remove_loading_html_from_element($submit_button);
            ai4seo_unlock_and_enable_lockable_input_fields();
        });
}


// ___________________________________________________________________________________________ \\
// === NOTIFICATIONS ========================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_init_notifications() {
    const $document = ai4seo_normalize_$(document);

    if (!ai4seo_exists_$($document)) {
        console.error('AI for SEO: element \"$document\" missing in ai4seo_init_notifications() \u2014 cannot initialize notification dismissal.');
        return;
    }

    // move all .ai4seo-notice to beginning of .ai4seo-dashboard, if not already done
    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (ai4seo_exists_$($dashboard)) {
        const $notices = ai4seo_normalize_$('.ai4seo-notice');

        if (!ai4seo_exists_$($notices)) {
            ai4seo_console_debug('AI for SEO: notices missing in ai4seo_init_notifications() — cannot reposition admin notices.');
            return;
        }

        // reverse the order of $notices, so that the oldest notice is at the top
        const $reverse_notices = ai4seo_normalize_$($notices.get().reverse());

        $reverse_notices.each(function() {
            const $this_notice = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_notice)) {
                console.warn('AI for SEO: element \"$this_notice\" missing in ai4seo_init_notifications() \u2014 cannot reposition admin notice.');
                return;
            }

            // potential dismiss button
            const $dismiss_button = $this_notice.find('.notice-dismiss');

            // re-init notice dismiss button (disappears on ajax calls)
            // or add .ai4seo-ignore-during-dashboard-refresh class to it
            if ($this_notice.hasClass('is-dismissible')) {
                if (ai4seo_exists_$($dismiss_button)) {
                    // add class ai4seo-ignore-during-dashboard-refresh if not already present
                    if (!$dismiss_button.hasClass('ai4seo-ignore-during-dashboard-refresh')) {
                        $dismiss_button.addClass('ai4seo-ignore-during-dashboard-refresh');
                    }
                } else {
                    $this_notice.append('<button type="button" class="notice-dismiss ai4seo-ignore-during-dashboard-refresh"></button>');
                }
            } else {
                // remove potential dismiss button
                if (ai4seo_exists_$($dismiss_button)) {
                    $dismiss_button.remove();
                }
            }

            const $this_notice_closest_dashboard = $this_notice.closest('.ai4seo-dashboard');

            // check if .ai4seo-notice is already inside .ai4seo-dashboard
            if (ai4seo_exists_$($this_notice_closest_dashboard)) {
                return; // already inside .ai4seo-dashboard, skip
            }

            $this_notice.prependTo($dashboard);
        });
    }

    // class "ai4seo-notification > notice-dismiss" (for notifications from notification system)
    $document.off('click.ai4seo-dismiss-notification', '.ai4seo-notification > .notice-dismiss');
    $document.on('click.ai4seo-dismiss-notification', '.ai4seo-notification > .notice-dismiss', function() {
        const $dismiss_button = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($dismiss_button)) {
            console.error('AI for SEO: element \"$dismiss_button\" missing in ai4seo_init_notifications() \u2014 notification cannot close.');
            return;
        }

        const $closest_notification = $dismiss_button.closest('.ai4seo-notification');

        if (!ai4seo_exists_$($closest_notification)) {
            console.error('AI for SEO: element \"$closest_notification\" missing in ai4seo_init_notifications() \u2014 cannot update notification state.');
            return;
        }

        if (!$closest_notification.data('notification-index')) {
            return;
        }

        let notification_index = $closest_notification.data('notification-index');

        // call desired ajax action
        ai4seo_perform_ajax_call('ai4seo_dismiss_notification', {ai4seo_notification_index: notification_index})
            .then(response => { /* nothing to do here */ })
            .catch(error => {
                ai4seo_show_generic_error_toast(1412181225);
            })
            .finally(() => { /* nothing to do here */ });
    });

    // class "ai4seo-notification-dismiss-button" (dismiss button in notification footer)
    $document.off('click.ai4seo-dismiss-notification', '.ai4seo-notification-dismiss-button');
    $document.on('click.ai4seo-dismiss-notification', '.ai4seo-notification-dismiss-button', function() {
        const $dismiss_button = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($dismiss_button)) {
            console.error('AI for SEO: element \"$dismiss_button\" missing in ai4seo_init_notifications() \u2014 notification cannot close.');
            return;
        }

        const $closest_notification = $dismiss_button.closest('.ai4seo-notification');

        if (!ai4seo_exists_$($closest_notification)) {
            console.error('AI for SEO: element \"$closest_notification\" missing in ai4seo_init_notifications() \u2014 cannot update notification state.');
            return;
        }

        if (!$closest_notification.data('notification-index')) {
            return;
        }

        let notification_index = $closest_notification.data('notification-index');

        // hide the notification with animation
        $closest_notification.slideUp(200, function() {
            $closest_notification.remove();
        });

        // call desired ajax action
        ai4seo_perform_ajax_call('ai4seo_dismiss_notification', {ai4seo_notification_index: notification_index})
            .then(response => { /* nothing to do here */ })
            .catch(error => {
                ai4seo_show_generic_error_toast(1013181225);
            })
            .finally(() => { /* nothing to do here */ });

    });
}


// ___________________________________________________________________________________________ \\
// === TERMS OF SERVICE ====================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Show confirmation notification modal to really reject tos
 */
function ai4seo_confirm_to_reject_tos() {
    let headline = wp.i18n.__('Please confirm', 'ai-for-seo');
    let content = wp.i18n.__('Are you sure you want to reject the terms of service and uninstall AI for SEO?', 'ai-for-seo');
    content += '<br><br>';
    content += wp.i18n.__("<strong>Attention:</strong><br>If you have already purchased a subscription, you can cancel it by clicking <a href='https://aiforseo.ai/cancel-plan' target='_blank'>HERE</a>.", 'ai-for-seo');

    let reject_button = "<button type='button' class='ai4seo-button ai4seo-abort-button' id='ai4seo-reject-tos-button' onclick='ai4seo_reject_tos();'>" + wp.i18n.__('Yes, please!', 'ai-for-seo') + '</button>';
    let back_button = "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('No, I changed my mind', 'ai-for-seo') + '</button>';

    ai4seo_open_notification_modal(headline, content, reject_button + back_button);
}

// =========================================================================================== \\

/**
 * Let the user reject tos, using ajax
 */
function ai4seo_reject_tos() {
    ai4seo_add_loading_html_to_element('.ai4seo-button');

    ai4seo_perform_ajax_call('ai4seo_reject_tos')
        .then(response => {
            window.location.href = ai4seo_admin_installed_plugins_page_url;
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1113181225);
        });
}

// =========================================================================================== \\

/**
 * Toggle the terms of service accept button based on the agreement checkbox state
 */
function ai4seo_refresh_tos_accept_button_state() {
    const $accept_tos_checkbox = ai4seo_normalize_$('.ai4seo-accept-tos-checkbox');

    if (!ai4seo_exists_$($accept_tos_checkbox)) {
        console.error('AI for SEO: element \"$accept_tos_checkbox\" missing in ai4seo_refresh_tos_accept_button_state() \u2014 cannot verify terms acceptance.');
        return;
    }

    const accepted_tos = $accept_tos_checkbox.prop('checked');
    const $accept_button = ai4seo_normalize_$('.ai4seo-accept-tos-button');

    if (!ai4seo_exists_$($accept_button)) {
        console.error('AI for SEO: element \"$accept_button\" missing in ai4seo_refresh_tos_accept_button_state() \u2014 cannot update terms acceptance state.');
        return;
    }

    if (accepted_tos) {
        // remove ai4seo-disabled-button class, add ai4seo-success-button class
        $accept_button.removeClass('ai4seo-disabled-button').addClass('ai4seo-success-button');
    } else {
        // add ai4seo-disabled-button class, remove ai4seo-success-button class
        $accept_button.addClass('ai4seo-disabled-button').removeClass('ai4seo-success-button');
    }
}

// =========================================================================================== \\

function ai4seo_check_if_user_accepted_tos() {
    const $accept_tos_checkbox = ai4seo_normalize_$('.ai4seo-accept-tos-checkbox');

    if (!ai4seo_exists_$($accept_tos_checkbox)) {
        console.error('AI for SEO: element \"$accept_tos_checkbox\" missing in ai4seo_check_if_user_accepted_tos() \u2014 cannot verify terms acceptance.');
        return false;
    }

    const accepted_tos = $accept_tos_checkbox.prop('checked');

    if (!accepted_tos) {
        ai4seo_show_accept_terms_notification_modal();
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_show_accept_terms_notification_modal() {
    ai4seo_open_notification_modal(wp.i18n.__('Attention!', 'ai-for-seo'), wp.i18n.__('Please accept the terms of service first.', 'ai-for-seo'));

    // add ai4seo-shake-animation to the checkbox and remove it after 3 seconds
    const $accept_tos_checkbox_wrapper = ai4seo_normalize_$('.ai4seo-accept-tos-checkbox-wrapper');

    if (ai4seo_exists_$($accept_tos_checkbox_wrapper)) {
        $accept_tos_checkbox_wrapper.addClass('ai4seo-shake-animation');
    }

    setTimeout(function() {
        const $checkbox_wrapper = ai4seo_normalize_$('.ai4seo-accept-tos-checkbox-wrapper');

        if (ai4seo_exists_$($checkbox_wrapper)) {
            $checkbox_wrapper.removeClass('ai4seo-shake-animation');
        }
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
    const $accept_enhanced_reporting_checkbox = ai4seo_normalize_$('.ai4seo-accept-enhanced-reporting-checkbox');

    if (!ai4seo_exists_$($accept_enhanced_reporting_checkbox)) {
        console.error('AI for SEO: element \"$accept_enhanced_reporting_checkbox\" missing in ai4seo_accept_tos() \u2014 enhanced reporting consent not updated.');
        return;
    }

    let accepted_enhanced_reporting = $accept_enhanced_reporting_checkbox.prop('checked');

    ai4seo_add_loading_html_to_element('.ai4seo-button');

    ai4seo_perform_ajax_call('ai4seo_accept_tos', {accepted_enhanced_reporting: accepted_enhanced_reporting})
        .then(response => {

        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1213181225);
        })
        .finally(() => {
            // reload page
            if (reload_page) {
                ai4seo_safe_page_load();
            } else {
                ai4seo_remove_loading_html_from_element('.ai4seo-button');
            }
        });
}

// =========================================================================================== \\

function ai4seo_does_user_need_to_accept_tos_toc_and_pp() {
    return ai4seo_get_localization_parameter('ai4seo_does_user_need_to_accepted_tos_toc_and_pp');
}


// ___________________________________________________________________________________________ \\
// === SETTINGS (PAGE) ======================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_toggle_sync_only_these_metadata_container() {
    let $sync_only_these_metadata_container = ai4seo_normalize_$('#ai4seo-sync-only-these-metadata-container');

    if (!ai4seo_exists_$($sync_only_these_metadata_container)) {
        console.error('AI for SEO: element \"$sync_only_these_metadata_container\" missing in ai4seo_toggle_sync_only_these_metadata_container() \u2014 cannot toggle metadata scope.');
        return;
    }

    // if any checkbox with class ai4seo_third_party_sync_checkbox is checked, display the container
    const $checked_sync_checkboxes = ai4seo_normalize_$('.ai4seo_third_party_sync_checkbox:checked');

    if (ai4seo_exists_$($checked_sync_checkboxes)) {
        $sync_only_these_metadata_container.show();
    } else {
        $sync_only_these_metadata_container.hide();
    }
}

// =========================================================================================== \\

function ai4seo_init_alt_text_injection_settings() {
    const $alt_text_injection_setting_toggle = ai4seo_normalize_$('.ai4seo-alt-text-injection-toggle');
    const $js_alt_text_injection_setting_container = ai4seo_normalize_$('#ai4seo-js-alt-text-injection-setting');

    if (!ai4seo_exists_$($alt_text_injection_setting_toggle) || !ai4seo_exists_$($js_alt_text_injection_setting_container)) {
        return;
    }

    const $advanced_settings_state = ai4seo_normalize_$('#ai4seo-advanced-setting-state');

    const ai4seo_toggle_js_alt_text_injection_visibility = function() {
        const are_advanced_settings_hidden = ai4seo_exists_$($advanced_settings_state) && $advanced_settings_state.val() !== 'show';

        if (are_advanced_settings_hidden) {
            $js_alt_text_injection_setting_container.addClass('ai4seo-js-alt-text-setting-hidden');
            return;
        }

        if ($alt_text_injection_setting_toggle.is(':checked')) {
            $js_alt_text_injection_setting_container.removeClass('ai4seo-js-alt-text-setting-hidden');

            // remove display attribute to fix visibility issues
            $js_alt_text_injection_setting_container.css('display', '');
        } else {
            $js_alt_text_injection_setting_container.addClass('ai4seo-js-alt-text-setting-hidden');
        }
    };

    $alt_text_injection_setting_toggle.off('change.ai4seo-alt-text-injection-toggle');
    $alt_text_injection_setting_toggle.on('change.ai4seo-alt-text-injection-toggle', ai4seo_toggle_js_alt_text_injection_visibility);

    const $show_advanced_button = ai4seo_normalize_$('#ai4seo-show-advanced-settings-container #ai4seo-toggle-advanced-button');
    const $hide_advanced_button = ai4seo_normalize_$('#ai4seo-hide-advanced-settings-container #ai4seo-toggle-advanced-button');

    const ai4seo_deferred_js_alt_text_injection_toggle = function() {
        setTimeout(ai4seo_toggle_js_alt_text_injection_visibility, 50);
    };

    if (ai4seo_exists_$($show_advanced_button)) {
        $show_advanced_button.off('click.ai4seo-alt-injection');
        $show_advanced_button.on('click.ai4seo-alt-injection', ai4seo_deferred_js_alt_text_injection_toggle);
    }

    if (ai4seo_exists_$($hide_advanced_button)) {
        $hide_advanced_button.off('click.ai4seo-alt-injection');
        $hide_advanced_button.on('click.ai4seo-alt-injection', ai4seo_deferred_js_alt_text_injection_toggle);
    }

    ai4seo_toggle_js_alt_text_injection_visibility();
}

// =========================================================================================== \\

function ai4seo_init_advanced_settings() {
    const $advanced_setting_state = ai4seo_normalize_$('#ai4seo-advanced-setting-state');

    if (!ai4seo_exists_$($advanced_setting_state)) {
        //ai4seo_console_debug('AI for SEO: element \"$advanced_setting_state\" missing in ai4seo_init_advanced_settings() \u2014 advanced preference state not saved.');
        return;
    }

    ai4seo_console_debug('AI for SEO: Initializing advanced settings view based on saved state.');

    let advanced_setting_state = $advanced_setting_state.val();

    if (advanced_setting_state === 'show') {
        // Show advanced settings
        ai4seo_show_advanced_settings();
    } else {
        // Hide advanced settings
        ai4seo_hide_advanced_settings();
    }
}

// =========================================================================================== \\

/**
 * Let the user show advanced settings
 */
function ai4seo_show_advanced_settings(show_fade_animation = false) {
    // Show advanced settings and swap buttons
    const $advanced_settings = ai4seo_normalize_$('.ai4seo-is-advanced-setting');
    const $show_advanced_settings_container = ai4seo_normalize_$('#ai4seo-show-advanced-settings-container');
    const $hide_advanced_settings_container = ai4seo_normalize_$('#ai4seo-hide-advanced-settings-container');
    const $advanced_setting_state = ai4seo_normalize_$('#ai4seo-advanced-setting-state');

    if (!ai4seo_exists_$($advanced_settings) || !ai4seo_exists_$($show_advanced_settings_container) || !ai4seo_exists_$($hide_advanced_settings_container) || !ai4seo_exists_$($advanced_setting_state)) {
        console.error('AI for SEO: Advanced settings containers missing in ai4seo_show_advanced_settings() — cannot reveal advanced options.');
        return;
    }

    $advanced_settings.show();
    $show_advanced_settings_container.hide();
    $hide_advanced_settings_container.show();
    $advanced_setting_state.val('show');

    if (show_fade_animation) {
        const $non_advanced_sections = ai4seo_normalize_$('.ai4seo-form-section:not(.ai4seo-is-advanced-setting)');

        if (!ai4seo_exists_$($non_advanced_sections)) {
            console.warn('AI for SEO: elements \"$non_advanced_sections\" missing in ai4seo_show_advanced_settings() \u2014 cannot toggle advanced view.');
            return;
        }

        $non_advanced_sections.fadeOut(0, function () {
            const $this_section = ai4seo_normalize_$(this);

            if (!ai4seo_exists_$($this_section)) {
                console.warn('AI for SEO: element \"$this_section\" missing in ai4seo_show_advanced_settings() \u2014 cannot toggle advanced view.');
            }

            $this_section.fadeIn(300);
        });
    }
}

// =========================================================================================== \\

/**
 * Let the user hide advanced settings
 */
function ai4seo_hide_advanced_settings(show_fade_animation = false) {
    // Hide advanced settings and swap buttons
    const $advanced_settings = ai4seo_normalize_$('.ai4seo-is-advanced-setting');
    const $show_advanced_settings_container = ai4seo_normalize_$('#ai4seo-show-advanced-settings-container');
    const $hide_advanced_settings_container = ai4seo_normalize_$('#ai4seo-hide-advanced-settings-container');
    const $advanced_setting_state = ai4seo_normalize_$('#ai4seo-advanced-setting-state');

    if (!ai4seo_exists_$($advanced_settings) || !ai4seo_exists_$($show_advanced_settings_container) || !ai4seo_exists_$($hide_advanced_settings_container) || !ai4seo_exists_$($advanced_setting_state)) {
        console.warn('AI for SEO: Advanced settings containers missing in ai4seo_hide_advanced_settings() — cannot conceal advanced options.');
        return;
    }

    $advanced_settings.hide();
    $show_advanced_settings_container.show();
    $hide_advanced_settings_container.hide();
    $advanced_setting_state.val('hide');

    if (show_fade_animation) {
        const $non_advanced_sections = ai4seo_normalize_$('.ai4seo-form-section:not(.ai4seo-is-advanced-setting)');

        if (!ai4seo_exists_$($non_advanced_sections)) {
            console.warn('AI for SEO: elements \"$non_advanced_sections\" missing in ai4seo_hide_advanced_settings() \u2014 cannot toggle advanced view.');
            return;
        }

        $non_advanced_sections.fadeOut(0, function () {
            const $this_section = ai4seo_normalize_$(this);

            if (ai4seo_exists_$($this_section)) {
                console.warn('AI for SEO: element \"$this_section\" missing in ai4seo_hide_advanced_settings() \u2014 cannot toggle advanced view.');
            }

            $this_section.fadeIn(300);
        });
    }
}

// =========================================================================================== \\

/**
 * Show confirmation dialog and restore default settings via Ajax
 */
function ai4seo_restore_default_settings($button) {
    if (ai4seo_exists_$($button)) {
        $button = ai4seo_normalize_$($button);
    }

    // Show confirmation dialog
    let headline = wp.i18n.__('Restore Default Settings', 'ai-for-seo');
    let content = wp.i18n.__('Are you sure you want to restore all settings to their default values?', 'ai-for-seo');
    content += '<br><br>';
    content += wp.i18n.__('<strong>Note:</strong> This action will reset all settings on this page to their default values. This cannot be undone.', 'ai-for-seo');

    let confirm_button = "<button type='button' class='ai4seo-button ai4seo-abort-button ai4seo-lockable' onclick='ai4seo_perform_restore_default_settings();'>" + wp.i18n.__('Yes, restore defaults', 'ai-for-seo') + '</button>';
    let cancel_button = "<button type='button' class='ai4seo-button ai4seo-success-button ai4seo-lockable' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('Cancel', 'ai-for-seo') + '</button>';

    ai4seo_open_notification_modal(headline, content, confirm_button + cancel_button);
}

// =========================================================================================== \\

/**
 * Perform the actual restore default settings Ajax call
 */
function ai4seo_perform_restore_default_settings() {
    // Show loading indicator
    ai4seo_lock_and_disable_lockable_input_fields();

    if (ai4seo_exists_$('.ai4seo-lockable')) {
        ai4seo_add_loading_html_to_element(ai4seo_normalize_$('.ai4seo-lockable'));
    }

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Restoring default settings...', 'ai-for-seo'));

    // Perform Ajax call
    ai4seo_perform_ajax_call('ai4seo_restore_default_settings')
        .then(response => {
            // Show success message
            ai4seo_show_success_toast(wp.i18n.__('Default settings restored successfully. Reloading page...', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1313181225);
        })
        .finally(() => {
            // Remove loading indicator
            ai4seo_unlock_and_enable_lockable_input_fields();
            ai4seo_remove_loading_html_from_element(ai4seo_normalize_$('.ai4seo-lockable'));
            setTimeout(() => ai4seo_safe_page_load(), 1000);
        });
}

// =========================================================================================== \\

function ai4seo_validate_settings_inputs(input_values) {
    // Check if prefix- and suffix-input-fields exist
    // Loop through all prefix- and suffix-input-fields and make sure that the content doesn't exceed the max-length
    const $prefix_suffix_inputs = ai4seo_normalize_$('input.ai4seo-prefix-suffix-setting-textfield');

    if (!ai4seo_exists_$($prefix_suffix_inputs)) {
        console.error('AI for SEO: elements \"$prefix_suffix_inputs\" missing in ai4seo_validate_settings_inputs() \u2014 prefix/suffix fields cannot be validated.');
        return false;
    }

    $prefix_suffix_inputs.each(function () {
        const $this_input = ai4seo_normalize_$(this);

        if (!ai4seo_exists_$($this_input)) {
            console.error('AI for SEO: element \"$input_field\" missing in ai4seo_validate_settings_inputs() \u2014 validation skipped.');
            return;
        }

        const this_input_value = $this_input.val();

        if (this_input_value.length > 0 && this_input_value.length > 48) {
            ai4seo_show_warning_toast(wp.i18n.__("Please don't exceed the maximum length-requirement for prefix- and suffix-input-fields (max. 48 characters).", 'ai-for-seo'));
            console.warn('AI for SEO: Validation failed for prefix/suffix input field with value \"' + this_input_value + '\" in ai4seo_validate_settings_inputs() \u2014 maximum length exceeded.');
            return false;
        }
    });

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
    const invalid = ai4seo_validate_ajax_action(action);

    if (invalid) {
        ai4seo_show_generic_error_toast(invalid.code);
        return Promise.reject(invalid);
    }

    // 2) Build payload
    const payload = ai4seo_build_ajax_payload(action, data);

    // 3) Execute request
    return ai4seo_execute_ajax_request(payload)
        .then((response) => {
            // 4) Unified success handling
            return ai4seo_handle_ajax_success({
                response,
                auto_check_response,
                additional_error_list,
                show_generic_error,
                add_contact_us_link,
            });
        })
        .catch((response) => {
            // 5) Try to recover JSON from non-JSON response
            const recovered = ai4seo_attempt_recover_json_from_ajax_error(response?.jqXHR);

            if (recovered) {
                return ai4seo_handle_ajax_success({
                    response: recovered,
                    auto_check_response,
                    additional_error_list,
                    show_generic_error,
                    add_contact_us_link,
                });
            }

            return Promise.reject(
                ai4seo_normalize_ajax_error(response)
            );
        });
}

// =========================================================================================== \\

/**
 * Validate the action against the allowlist.
 * @param {string} action
 * @returns {null|{error:string, code:number, message:string}}
 */
function ai4seo_validate_ajax_action(action) {
    if (!Array.isArray(ai4seo_allowed_ajax_actions) || !ai4seo_allowed_ajax_actions.includes(action)) {
        return {
            error: 'Invalid action',
            code: 4317101224,
            message: wp.i18n.__('AJAX action not allowed', 'ai-for-seo') + `: ${action}`,
        };
    }
    return null;
}

// =========================================================================================== \\

/**
 * Build the AJAX payload including nonce & action.
 * @param {string} action
 * @param {Object} data
 * @returns {Object}
 */
function ai4seo_build_ajax_payload(action, data) {
    const nonce = ai4seo_get_ajax_nonce();
    const bypass_incognito_mode = ai4seo_get_localization_parameter('ai4seo_bypass_incognito_mode');

    return {
        ...(data || {}),
        [AI4SEO_GLOBAL_NONCE_IDENTIFIER]: nonce,
        security: nonce,
        action: action,
        ai4seo_debug_bypass_incognito_mode: bypass_incognito_mode,
    };
}

// =========================================================================================== \\

/**
 * Execute the actual AJAX request (POST JSON to admin-ajax).
 * Isolated for testability and reuse.
 * @param {Object} payload
 * @returns {Promise<any>}
 */
function ai4seo_execute_ajax_request(payload) {
    let ai4seo_admin_ajax_url = ai4seo_get_admin_ajax_url();

    return new Promise((resolve, reject) => {
        jQuery
            .ajax({
                url: ai4seo_admin_ajax_url,
                method: 'POST',
                data: payload,
                dataType: 'json', // Force JSON; fail fast if it isn't
                cache: false,
            })
            .done((response) => resolve(response))
            .fail((jqXHR, textStatus, errorThrown) =>
                reject({ jqXHR, textStatus, errorThrown })
            );
    });
}

// =========================================================================================== \\

/**
 * Centralized success path (also used by recovered JSON).
 * Applies optional response checking and normalizes the resolved data.
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
                                   add_contact_us_link,
                               }) {

    const normalized = ai4seo_get_normalized_ajax_response_data(response);

    // If auto-checking is disabled, resolve raw (but normalized) data
    if (!auto_check_response) {
        return normalized;
    }

    // Use the existing checker; if it returns true, resolve; else reject
    if (ai4seo_check_response(response, additional_error_list, show_generic_error, add_contact_us_link)) {
        return normalized;
    }

    // Make sure to reject with something useful if check failed
    const error_object = {
        success: false,
        error: 'invalid_response',
        code: 4217101225,
        details: normalized,
    };

    return Promise.reject(error_object);
}

// =========================================================================================== \\

/**
 * Normalize how we resolve data (WP style `{ success, data }` vs raw).
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

/**
 * Attempt to recover a JSON object from a failed jqXHR responseText.
 * Trims noise before/after the first/last brace and tries to parse.
 * @param {jqXHR} jqXHR
 * @returns {null|Object}
 */
function ai4seo_attempt_recover_json_from_ajax_error(jqXHR) {
    try {
        const raw =
            jqXHR && typeof jqXHR.responseText === 'string'
                ? jqXHR.responseText
                : '';

        if (!raw) return null;

        const first_brace = raw.indexOf('{');
        const last_brace = raw.lastIndexOf('}');
        if (first_brace === -1 || last_brace === -1 || last_brace <= first_brace) {
            return null;
        }

        const sliced = raw.slice(first_brace, last_brace + 1);
        const parsed = JSON.parse(sliced);

        // Must be an object to be considered valid recovery
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
 * @param {jqXHR} jqXHR
 */
function ai4seo_log_special_zero_ajax_error(jqXHR) {
    const raw =
        jqXHR && typeof jqXHR.responseText === 'string'
            ? jqXHR.responseText.trim()
            : '';

    if (raw === '0') {
        console.warn('AI for SEO: Server responded with "0" (possible nonce/auth issue).');
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
    const { jqXHR = {}, textStatus, errorThrown } = response || {};
    let raw = '';
    let parsed = null;

    if (jqXHR && typeof jqXHR.responseText === 'string') {
        raw = jqXHR.responseText.trim();

        if (raw && (raw.startsWith('{') || raw.startsWith('['))) {
            try {
                parsed = JSON.parse(raw);
            } catch (e) {
                parsed = null;
            }
        }
    }

    const status = Number(jqXHR.status) || 0;
    const readyState = Number(jqXHR.readyState) || 0;

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

    // ---------------------------------------------------------------------
    // 4) Build details
    // ---------------------------------------------------------------------
    let details = null;

    if (errorThrown) {
        details = typeof errorThrown === 'string'
            ? errorThrown
            : JSON.stringify(errorThrown, null, 2);
    } else if (parsed) {
        details = parsed;
    } else if (raw) {
        details = raw.slice(0, 800);
    } else {
        details = 'No further details';
    }

    // ---------------------------------------------------------------------
    // 5) Logging (dev-friendly, compact)
    // ---------------------------------------------------------------------
    console.groupCollapsed(
        `AI for SEO: AJAX Error (${status || 'n/a'}) – click for details`
    );
    console.error('Error:', error);
    console.warn('Details:', details);
    if (readyState !== 4) console.info('XHR readyState:', readyState);
    if (parsed) console.info('Parsed JSON:', parsed);

    if (readyState === 0 && status === 0) {
        console.warn(
            'AI for SEO: Request not sent. Possible network, CORS, SSL, or mixed-content issue.'
        );
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
    // try to get the nonce from the DOM
    const $nonce_field = ai4seo_normalize_$('#ai4seo_ajax_nonce');

    if (ai4seo_exists_$($nonce_field)) {
        const dom_value = $nonce_field.val();

        if (dom_value) {
            return dom_value;
        }
    }

    // if not found in the DOM, try to get it from the localization parameters
    return ai4seo_get_localization_parameter('ai4seo_ajax_nonce') || '';
}

// =========================================================================================== \\

function ai4seo_lock_and_disable_lockable_input_fields() {
    // Define variable for all input-fields
    const $all_input_fields = ai4seo_normalize_$('.ai4seo-lockable');

    if (!ai4seo_exists_$($all_input_fields)) {
        ai4seo_console_debug('AI for SEO: no elements with \".ai4seo-lockable\" class found in ai4seo_lock_and_disable_lockable_input_fields() \u2014 no lockable inputs to update.');
        return;
    }

    // Add css-class to disable input-fields
    $all_input_fields.addClass('ai4seo-temporary-locked');

    // Add disabled attribute to all input-fields
    $all_input_fields.attr('disabled', 'disabled');
}

// =========================================================================================== \\

function ai4seo_unlock_and_enable_lockable_input_fields() {
    // Define variable for all input-fields
    const $all_input_fields = ai4seo_normalize_$('.ai4seo-temporary-locked');

    if (!ai4seo_exists_$($all_input_fields)) {
        ai4seo_console_debug('AI for SEO: no elements with \".ai4seo-temporary-locked\" class found in ai4seo_unlock_and_enable_lockable_input_fields() \u2014 no temporary locks to release.');
        return;
    }

    // Remove css-class to disable input-fields
    $all_input_fields.removeClass('ai4seo-temporary-locked');

    // Add disabled attribute to all input-fields
    $all_input_fields.prop('disabled', false);
}


// ___________________________________________________________________________________________ \\
// === HELP PAGE ============================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_validate_troubleshooting_settings(input_values) {
    if (typeof input_values !== 'object' || input_values === null) {
        return true;
    }

    const disable_heavy_db_operations_toggle_name = 'ai4seo_disable_heavy_db_operations';

    if (Object.prototype.hasOwnProperty.call(input_values, disable_heavy_db_operations_toggle_name) && typeof input_values[disable_heavy_db_operations_toggle_name] !== 'boolean') {
        ai4seo_show_warning_toast(wp.i18n.__('Please select a valid option for the debugging toggle.', 'ai-for-seo'));
        return false;
    }

    return true;
}

function ai4seo_confirm_reset_plugin_data() {
    const $reset_metadata_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-metadata');
    const ai4seo_reset_metadata = ai4seo_exists_$($reset_metadata_checkbox) && $reset_metadata_checkbox.is(':checked');

    let ai4seo_notification_modal_message = '';

    if (ai4seo_reset_metadata) {
        const $reset_generated_data_tooltip = ai4seo_normalize_$('#ai4seo-reset-generated-data-tooltip-text');

        if (ai4seo_exists_$($reset_generated_data_tooltip)) {
            ai4seo_notification_modal_message = $reset_generated_data_tooltip.html() + '<br><br>';
        }
    }

    ai4seo_notification_modal_message += wp.i18n.__('Are you sure you want to reset the selected plugin data?', 'ai-for-seo');

    ai4seo_open_notification_modal(
        wp.i18n.__('Please confirm', 'ai-for-seo'),
        ai4seo_notification_modal_message,
        "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('Abort', 'ai-for-seo') + "</button><button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_reset_plugin_data();'>" + wp.i18n.__('Reset Plugin Data', 'ai-for-seo') + '</button>'
    );
}

// =========================================================================================== \\

/**
 * Function to decode the HTML safely escaped by esc_js().
 * This replaces escaped characters (e.g., `&lt;`, `&gt;`) back to their HTML counterparts.
 */
function ai4seo_decode_escaped_html(escapedHtml) {
    const $textarea = ai4seo_normalize_$('<textarea></textarea>');

    if (!ai4seo_exists_$($textarea)) {
        console.error('AI for SEO: Could not create textarea element in ai4seo_decode_escaped_html() \u2014 returning original value.');
        return escapedHtml;
    }

    $textarea.html(escapedHtml); // Decodes HTML entities
    const value = $textarea.val(); // Returns unescaped HTML
    $textarea.remove();

    return value;
}

// =========================================================================================== \\

function ai4seo_reset_plugin_data() {
    ai4seo_close_notification_modal();

    const $reset_cache_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-cache');
    const $reset_notifications_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-notifications');
    const $reset_environmental_variables_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-env');
    const $reset_settings_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-settings');
    const $reset_metadata_checkbox = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-metadata');

    let reset_cache = ai4seo_exists_$($reset_cache_checkbox) && $reset_cache_checkbox.is(':checked');
    let reset_notifications = ai4seo_exists_$($reset_notifications_checkbox) && $reset_notifications_checkbox.is(':checked');
    let reset_environmental_variables = ai4seo_exists_$($reset_environmental_variables_checkbox) && $reset_environmental_variables_checkbox.is(':checked');
    let reset_settings = ai4seo_exists_$($reset_settings_checkbox) && $reset_settings_checkbox.is(':checked');
    let reset_metadata = ai4seo_exists_$($reset_metadata_checkbox) && $reset_metadata_checkbox.is(':checked');

    // Check if at least one option is selected
    if (!reset_cache && !reset_notifications && !reset_environmental_variables && !reset_settings && !reset_metadata) {
        ai4seo_open_notification_modal(
            wp.i18n.__('Oops...', 'ai-for-seo'),
            wp.i18n.__('Please select at least one option to reset.', 'ai-for-seo'),
            "<button type='button' class='ai4seo-button ai4seo-success-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('OK', 'ai-for-seo') + '</button>'
        );

        return;
    }

    ai4seo_lock_and_disable_lockable_input_fields();

    const $reset_button = ai4seo_normalize_$('#ai4seo-troubleshooting-reset-button');

    if (ai4seo_exists_$($reset_button)) {
        ai4seo_add_loading_html_to_element($reset_button);
    }

    let ajax_parameter = {
        ai4seo_reset_cache: reset_cache,
        ai4seo_reset_notifications: reset_notifications,
        ai4seo_reset_environmental_variables: reset_environmental_variables,
        ai4seo_reset_settings: reset_settings,
        ai4seo_reset_metadata: reset_metadata
    };

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Resetting plugin data...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_reset_plugin_data', ajax_parameter)
        .then(response => {
            ai4seo_show_success_toast(wp.i18n.__('The plugin data has been reset successfully.', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1413181225);
        })
        .finally(response => {
            ai4seo_unlock_and_enable_lockable_input_fields();

            if (ai4seo_exists_$($reset_button)) {
                ai4seo_remove_loading_html_from_element($reset_button);
            }
        });
}


// ___________________________________________________________________________________________ \\
// === SELECT CREDITS PACK MODAL ============================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_select_credits_pack_modal() {
    ai4seo_open_modal_from_schema('select-credits-pack', {modal_size: 'small'});

    const $most_popular_pack = ai4seo_normalize_$('.ai4seo-credits-pack-selection-item-most-popular');

    if (!ai4seo_exists_$($most_popular_pack)) {
        console.warn('AI for SEO: element \"$most_popular_pack\" missing in ai4seo_handle_open_select_credits_pack_modal() \u2014 default credits pack not highlighted.');
        return;
    }

    $most_popular_pack.click();
}

// =========================================================================================== \\

function ai4seo_track_subscription_pricing_visit() {
    ai4seo_perform_ajax_call('ai4seo_track_subscription_pricing_visit', {}, false, {}, false, false)
        .catch(() => {
            // We intentionally ignore errors to avoid interrupting the redirect to the pricing page.
        });
}

// =========================================================================================== \\

function ai4seo_handle_credits_pack_selection($credits_pack_selection_item) {
    $credits_pack_selection_item = ai4seo_normalize_$($credits_pack_selection_item);

    if (!ai4seo_exists_$($credits_pack_selection_item)) {
        console.error('AI for SEO: element \"$credits_pack_selection_item\" missing in ai4seo_handle_credits_pack_selection() \u2014 skipping iteration.');
        return;
    }

    let $all_credits_pack_items = ai4seo_normalize_$('div.ai4seo-credits-pack-selection-item');

    if (!ai4seo_exists_$($all_credits_pack_items)) {
        console.error('AI for SEO: elements \"$all_credits_pack_items\" missing in ai4seo_handle_credits_pack_selection() \u2014 no credits pack items to update.');
        return;
    }

    // remove .ai4seo-credits-pack-selection-item-selected class from all items
    $all_credits_pack_items.removeClass('ai4seo-credits-pack-selection-item-selected');

    // add .ai4seo-credits-pack-selection-item-selected class to selected item
    $credits_pack_selection_item.addClass('ai4seo-credits-pack-selection-item-selected');

    // set radio button in > ai4seo-credits-pack-selection-item-radio-button checked
    $credits_pack_selection_item.find('.ai4seo-credits-pack-selection-item-radio-button > input').prop('checked', true);

    // refresh cost breakdown
    let cost_per_page = $credits_pack_selection_item.data('cost-per-page');
    let cost_per_media_file = $credits_pack_selection_item.data('cost-per-media-file');
    let currency = $credits_pack_selection_item.data('currency');

    const $credits_pack_cost_per_page = ai4seo_normalize_$('.ai4seo-credits-pack-cost-per-page');
    const $credits_pack_cost_per_media_file = ai4seo_normalize_$('.ai4seo-credits-pack-cost-per-media-file');

    if (ai4seo_exists_$($credits_pack_cost_per_page)) {
        $credits_pack_cost_per_page.text(cost_per_page + ' ' + currency);
    }

    if (ai4seo_exists_$($credits_pack_cost_per_media_file)) {
        $credits_pack_cost_per_media_file.text(cost_per_media_file + ' ' + currency);
    }
}

// =========================================================================================== \\

function ai4seo_handle_select_credits_pack($submit_button) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_handle_select_credits_pack() \u2014 cannot save credits pack selection.');
        return;
    }

    const $selected_credits_pack = ai4seo_normalize_$("input[name='ai4seo-credits-pack-selection[]']");

    if (!ai4seo_exists_$($selected_credits_pack) || !ai4seo_get_input_value($selected_credits_pack)) {
        console.warn('AI for SEO: $credits_pack_selection missing or empty in ai4seo_handle_select_credits_pack() — cannot initiate purchase.');
        ai4seo_show_warning_toast(wp.i18n.__('Please select a Credits Pack first.', 'ai-for-seo'));
        return;
    }

    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_lock_and_disable_lockable_input_fields();

    let selected_stripe_price_id = ai4seo_get_input_value("input[name='ai4seo-credits-pack-selection[]']");

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Initiating purchase...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_init_purchase', {stripe_price_id: selected_stripe_price_id})
        .then(response => {
            if (typeof response.purchase_url === 'undefined' || !response.purchase_url) {
                ai4seo_show_error_toast(471818325, wp.i18n.__('An error occurred while trying to initiate the purchase.', 'ai-for-seo'));
                return false;
            }

            ai4seo_show_success_toast(wp.i18n.__('Redirecting to purchase page...', 'ai-for-seo'));

            // redirect to purchase url
            window.location.href = response.purchase_url;
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1513181225);
            ai4seo_remove_loading_html_from_element($submit_button);
            ai4seo_unlock_and_enable_lockable_input_fields();
        });
}


// ___________________________________________________________________________________________ \\
// === PAY-AS-YOU-GO MODAL =================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

function ai4seo_handle_open_customize_payg_modal() {
    ai4seo_open_modal_from_schema('customize-pay-as-you-go', {modal_size: 'small'});
    ai4seo_handle_payg_form_change();
}

// =========================================================================================== \\

function ai4seo_handle_payg_form_change() {
    const $payg_price_select = ai4seo_normalize_$('#ai4seo_payg_stripe_price_id');
    const $selected_option = ai4seo_normalize_$('#ai4seo_payg_stripe_price_id option:selected');
    const $payg_daily_budget_input = ai4seo_normalize_$('#ai4seo_payg_daily_budget');
    const $payg_monthly_budget_input = ai4seo_normalize_$('#ai4seo_payg_monthly_budget');

    if (!ai4seo_exists_$($payg_price_select) || !ai4seo_exists_$($selected_option) || !ai4seo_exists_$($payg_daily_budget_input) || !ai4seo_exists_$($payg_monthly_budget_input)) {
        console.warn('AI for SEO: PAYG form elements missing in ai4seo_handle_payg_form_change() — cannot update pricing summary.');
        return;
    }

    let payg_stripe_price_id = $payg_price_select.val();
    let payg_credits_amount = $selected_option.data('credits-amount');
    let payg_price = $selected_option.data('price');
    let payg_reference_price = $selected_option.data('reference-price');
    let payg_daily_budget = $payg_daily_budget_input.val();
    let payg_monthly_budget = $payg_monthly_budget_input.val();
    const price_buffer = 1.25; // 25% buffer to account for taxes

    // replace , with .
    if (typeof payg_price === 'string') {
        payg_price = payg_price.replace(',', '.');
    }

    // cast payg_price to float
    payg_price = parseFloat(payg_price);

    // add buffer to the price
    const buffered_payg_price = Math.ceil(payg_price * price_buffer);

    // cast payg_daily_budget to int
    payg_daily_budget = parseInt(payg_daily_budget);
    $payg_daily_budget_input.val(payg_daily_budget);

    // if daily budget is lower than the price, set it to the ceil(buffered_payg_price)
    if (payg_daily_budget < buffered_payg_price) {
        payg_daily_budget = buffered_payg_price;
        $payg_daily_budget_input.val(payg_daily_budget);
    }

    // cast payg_monthly_budget to int
    payg_monthly_budget = parseInt(payg_monthly_budget);

    // if monthly budget is lower than the price, set it to the ceil(price)
    if (payg_monthly_budget < buffered_payg_price) {
        payg_monthly_budget = buffered_payg_price;
        $payg_monthly_budget_input.val(payg_monthly_budget);
    }

    const $payg_summary_credits_amount = ai4seo_normalize_$('#ai4seo-payg-summary-credits-amount');
    const $payg_summary_price = ai4seo_normalize_$('#ai4seo-payg-summary-price');
    const $payg_summary_reference_price = ai4seo_normalize_$('#ai4seo-payg-summary-reference-price');
    const $payg_summary_daily_budget = ai4seo_normalize_$('#ai4seo-payg-summary-daily-budget');
    const $payg_summary_monthly_budget = ai4seo_normalize_$('#ai4seo-payg-summary-monthly-budget');

    if (ai4seo_exists_$($payg_summary_credits_amount)) {
        $payg_summary_credits_amount.text(payg_credits_amount);
    }

    if (ai4seo_exists_$($payg_summary_price)) {
        $payg_summary_price.text(payg_price);
    }

    if (ai4seo_exists_$($payg_summary_reference_price)) {
        $payg_summary_reference_price.text(payg_reference_price);
    }

    if (ai4seo_exists_$($payg_summary_daily_budget)) {
        $payg_summary_daily_budget.text(payg_daily_budget);
    }

    if (ai4seo_exists_$($payg_summary_monthly_budget)) {
        $payg_summary_monthly_budget.text(payg_monthly_budget);
    }
}

// =========================================================================================== \\

function ai4seo_handle_payg_submit($submit_button) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_handle_payg_submit() \u2014 cannot process PAYG checkout.');
        return;
    }

    ai4seo_save_anything($submit_button, ai4seo_validate_payg_inputs, function() { ai4seo_safe_page_load(); });
}

// =========================================================================================== \\

function ai4seo_validate_payg_inputs() {
    // #ai4seo_payg_enabled must be checked
    const $payg_enabled_checkbox = ai4seo_normalize_$('#ai4seo_payg_enabled');

    if (!ai4seo_exists_$($payg_enabled_checkbox)) {
        console.error('AI for SEO: element \"$payg_enabled_checkbox\" missing in ai4seo_validate_payg_inputs() \u2014 cannot confirm PAYG activation.');
        return false;
    }

    let payg_confirmation_checkbox = $payg_enabled_checkbox.is(':checked');

    if (!payg_confirmation_checkbox) {
        ai4seo_show_info_toast(wp.i18n.__('Please confirm that you have reviewed the settings above and you want to enable Pay-As-You-Go now.', 'ai-for-seo'));
        return false;
    }

    // check daily budget, must be at least as high as the price
    const $payg_daily_budget_input = ai4seo_normalize_$('#ai4seo_payg_daily_budget');

    if (!ai4seo_exists_$($payg_daily_budget_input)) {
        console.error('AI for SEO: element \"$payg_daily_budget_input\" missing in ai4seo_validate_payg_inputs() \u2014 daily budget validation failed.');
        return false;
    }

    let payg_daily_budget = $payg_daily_budget_input.val();
    let payg_price = null;
    const price_buffer = 1.25; // 25% buffer to account for taxes

    const $selected_option = ai4seo_normalize_$('#ai4seo_payg_stripe_price_id option:selected');

    if (!ai4seo_exists_$($selected_option)) {
        console.error('AI for SEO: element \"$selected_option\" missing in ai4seo_validate_payg_inputs() \u2014 PAYG option validation failed.');
        ai4seo_show_warning_toast(wp.i18n.__('Please select a valid credits pack.', 'ai-for-seo'));
        return false;
    }

    payg_price = parseFloat($selected_option.data('price'));

    // buffered price
    const buffered_payg_price = Math.ceil(payg_price * price_buffer);

    // cast payg_daily_budget to int
    payg_daily_budget = parseInt(payg_daily_budget);

    if (payg_daily_budget < buffered_payg_price) {
        ai4seo_show_warning_toast(wp.i18n.__('The daily budget must be at least as high as the selected price and a 25% buffer to account for taxes (' + buffered_payg_price + ').', 'ai-for-seo'));
        return false;
    }

    // max 99999
    if (payg_daily_budget > 99999) {
        ai4seo_show_warning_toast(wp.i18n.__('The daily budget must be at most 99999.', 'ai-for-seo'));
        return false;
    }

    // check monthly budget, must be at least as high as the price
    const $payg_monthly_budget_input = ai4seo_normalize_$('#ai4seo_payg_monthly_budget');

    if (!ai4seo_exists_$($payg_monthly_budget_input)) {
        console.error('AI for SEO: element \"$payg_monthly_budget_input\" missing in ai4seo_validate_payg_inputs() \u2014 monthly budget validation failed.');
        ai4seo_show_warning_toast(wp.i18n.__('Please enter a valid monthly budget.', 'ai-for-seo'));
        return false;
    }

    let payg_monthly_budget = $payg_monthly_budget_input.val();

    // cast payg_monthly_budget to int
    payg_monthly_budget = parseInt(payg_monthly_budget);

    if (payg_monthly_budget < buffered_payg_price) {
        ai4seo_show_warning_toast(wp.i18n.__('The monthly budget must be at least as high as the selected price and a 25% buffer to account for taxes (' + buffered_payg_price + ').', 'ai-for-seo'));
        return false;
    }

    // max 999999
    if (payg_monthly_budget > 999999) {
        ai4seo_show_warning_toast(wp.i18n.__('The monthly budget must be at most 999999.', 'ai-for-seo'));
        return false;
    }

    return true;
}

// =========================================================================================== \\

function ai4seo_disable_payg($submit_button) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        console.error('AI for SEO: element \"$submit\" missing in ai4seo_disable_payg() \u2014 cannot disable PAYG.');
        return;
    }

    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Disabling Pay-As-You-Go...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_disable_payg')
        .then(response => {
            ai4seo_show_success_toast(wp.i18n.__('Pay-As-You-Go has been disabled successfully. Reloading page...', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1613181225);
        })
        .finally(() => {
            setTimeout(() => ai4seo_safe_page_load(), 1000);
        });
}

// =========================================================================================== \\

function ai4seo_import_nextgen_gallery_images($submit_button) {
    $submit_button = ai4seo_normalize_$($submit_button);

    if (!ai4seo_exists_$($submit_button)) {
        console.warn('AI for SEO: element \"$submit\" missing in ai4seo_import_nextgen_gallery_images() \u2014 cannot import NextGEN gallery images.');
        return;
    }

    ai4seo_add_loading_html_to_element($submit_button);
    ai4seo_lock_and_disable_lockable_input_fields();

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Importing NextGEN gallery images...', 'ai-for-seo'));

    ai4seo_perform_ajax_call('ai4seo_import_nextgen_gallery_images')
        .then( response => {
            ai4seo_show_success_toast(wp.i18n.__('NextGEN gallery images imported successfully. Reloading page...', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1713181225);
        })
        .finally(() => {
            setTimeout(() => ai4seo_safe_page_load(), 1000);
        });
}


// ___________________________________________________________________________________________ \\
// === EXPORT/IMPORT SETTINGS ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

/**
 * Export all settings to a JSON file
 */
function ai4seo_init_export_settings() {
    let $export_button = ai4seo_normalize_$('#ai4seo-export-settings-button');

    if (!ai4seo_exists_$($export_button)) {
        console.error('AI for SEO: element \"$export_button\" missing in ai4seo_init_export_settings() \u2014 settings export aborted.');
        return;
    }
    
    // Add loading animation
    ai4seo_add_loading_html_to_element($export_button);

    // save any unsaved changes before exporting
    const $save_settings_button = ai4seo_normalize_$('#ai4seo-save-settings');

    if (!ai4seo_exists_$($save_settings_button)) {
        console.error('AI for SEO: element \"$save_settings_button\" missing in ai4seo_init_export_settings() \u2014 cannot trigger export.');
        return;
    }

    ai4seo_save_anything($save_settings_button, ai4seo_validate_settings_inputs, ai4seo_export_settings);
}

// =========================================================================================== \\

function ai4seo_export_settings() {
    let $export_button = ai4seo_normalize_$('#ai4seo-export-settings-button');

    if (!ai4seo_exists_$($export_button)) {
        console.error('AI for SEO: element \"$export_button\" missing in ai4seo_export_settings() \u2014 settings export aborted.');
        return;
    }

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Exporting settings...', 'ai-for-seo'));

    // Perform AJAX call to export settings
    ai4seo_perform_ajax_call('ai4seo_export_settings')
        .then(response => {
            if (response.settings_data && response.filename) {
                // Create downloadable file
                ai4seo_download_json_file(response.settings_data, response.filename);

                ai4seo_show_success_toast(wp.i18n.__('Settings exported successfully! The file can be imported using the same modal.', 'ai-for-seo'));
            } else {
                ai4seo_show_error_toast(
                    50176725,
                    wp.i18n.__('Failed to export settings. Please try again.', 'ai-for-seo')
                );
            }
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(1813181225);
        })
        .finally(() => {
            // Remove loading animation
            ai4seo_remove_loading_html_from_element($export_button);
            ai4seo_close_modal_from_schema('export-import-settings');
        });
}

// =========================================================================================== \\

/**
 * Import settings from uploaded JSON file
 */
function ai4seo_init_import_settings() {
    let $import_file_input = ai4seo_normalize_$('#ai4seo-import-file');

    if (!ai4seo_exists_$($import_file_input)) {
        console.error('AI for SEO: element \"$import_file_input\" missing in ai4seo_init_import_settings() \u2014 import cannot proceed.');
        return;
    }

    let $import_settings_button = ai4seo_normalize_$('#ai4seo-import-settings-button');

    if (!ai4seo_exists_$($import_settings_button)) {
        console.error('AI for SEO: element \"$import_settings_button\" missing in ai4seo_init_import_settings() \u2014 import workflow halted.');
        return;
    }

    let file_input_element = $import_file_input[0];

    // Validate file selection
    if (!file_input_element.files || file_input_element.files.length === 0) {
        ai4seo_show_warning_toast(wp.i18n.__('Please select a file to import.', 'ai-for-seo'));
        console.warn('AI for SEO: no file selected in ai4seo_init_import_settings() \u2014 import cannot proceed.');
        return;
    }

    let file = file_input_element.files[0];
    
    // Validate file type
    if (!file.name.toLowerCase().endsWith('.json')) {
        ai4seo_show_warning_toast(wp.i18n.__('Please select a valid JSON file.', 'ai-for-seo'));
        console.warn('AI for SEO: invalid file type in ai4seo_init_import_settings() \u2014 import cannot proceed.');
        return;
    }
    
    // Get selected categories
    let categories = [];

    const $import_settings_page_checkbox = ai4seo_normalize_$('#ai4seo-import-settings-page-checkbox');

    if (ai4seo_exists_$($import_settings_page_checkbox) && $import_settings_page_checkbox.is(':checked')) {
        categories.push('settings');
    }

    const $import_account_page_checkbox = ai4seo_normalize_$('#ai4seo-import-account-page-checkbox');

    if (ai4seo_exists_$($import_account_page_checkbox) && $import_account_page_checkbox.is(':checked')) {
        categories.push('account');
    }

    const $import_seo_autopilot_checkbox = ai4seo_normalize_$('#ai4seo-import-seo-autopilot-checkbox');

    if (ai4seo_exists_$($import_seo_autopilot_checkbox) && $import_seo_autopilot_checkbox.is(':checked')) {
        categories.push('seo_autopilot');
    }

    const $import_get_more_credits_checkbox = ai4seo_normalize_$('#ai4seo-import-get-more-credits-checkbox');

    if (ai4seo_exists_$($import_get_more_credits_checkbox) && $import_get_more_credits_checkbox.is(':checked')) {
        categories.push('get_more_credits');
    }

    // Validate category selection
    if (categories.length === 0) {
        ai4seo_show_warning_toast(wp.i18n.__('Please select at least one category to import.', 'ai-for-seo'));
        console.warn('AI for SEO: no categories selected in ai4seo_init_import_settings() \u2014 import cannot proceed.');
        return;
    }
    
    // Add loading animation
    ai4seo_add_loading_html_to_element($import_settings_button);
    
    // Read file content
    let reader = new FileReader();

    reader.onload = function(e) {
        try {
            let file_content = JSON.parse(e.target.result);

            // check for "ai4seo_plugin_version" property
            if (!file_content.hasOwnProperty('ai4seo_plugin_version')) {
                ai4seo_remove_loading_html_from_element($import_settings_button);
                ai4seo_show_error_toast(
                    44186725,
                    wp.i18n.__("Invalid JSON file format. The file must contain the 'ai4seo_plugin_version' property.", 'ai-for-seo')
                );
            }

            // check for settings property
            if (!file_content.hasOwnProperty('settings')) {
                ai4seo_remove_loading_html_from_element($import_settings_button);
                ai4seo_show_error_toast(
                    45186725,
                    wp.i18n.__("Invalid JSON file format. The file must contain the 'settings' property.", 'ai-for-seo')
                );
                return;
            }

            // check if version is lower than the current version
            let current_version = ai4seo_get_plugin_version_number();
            let imported_version = file_content.ai4seo_plugin_version;
            let new_settings = file_content.settings;

            if (imported_version !== current_version) {
                // show warning modal
                ai4seo_remove_loading_html_from_element($import_settings_button);

                ai4seo_open_notification_modal(
                    wp.i18n.__('Version Mismatch', 'ai-for-seo'),
                    wp.i18n.__('The imported settings are from an older or newer version of the plugin. Some settings may not be compatible with the current version.', 'ai-for-seo'),
                    "<button type='button' class='ai4seo-button ai4seo-abort-button' onclick='ai4seo_close_modal_by_child(this);'>" + wp.i18n.__('Abort Import', 'ai-for-seo') + '</button>' +
                    "<button type='button' class='ai4seo-button ai4seo-submit-button' onclick='ai4seo_close_modal_by_child(this);ai4seo_show_import_settings_preview(" + JSON.stringify(new_settings) + ', ' + JSON.stringify(categories) + ");'>" + wp.i18n.__('Proceed with Import', 'ai-for-seo') + '</button>'
                );
            } else {
                ai4seo_show_import_settings_preview(new_settings, categories);
            }
        } catch (error) {
            ai4seo_remove_loading_html_from_element($import_settings_button);
            ai4seo_show_error_toast(
                46186725,
                wp.i18n.__('Invalid JSON file format. Please check the file content.', 'ai-for-seo')
            );
        }
    }

    reader.readAsText(file);
}

// =========================================================================================== \\

/**
 * Download JSON data as file
 */
function ai4seo_download_json_file(data, filename) {
    // ensure a sane filename
    filename = (typeof filename === 'string' && filename.trim()) ? filename : 'download.json';

    // build JSON safely
    var json_str;
    try {
        json_str = JSON.stringify(data, null, 2);
    } catch (e) {
        console.error('AI for SEO: Could not stringify data in ai4seo_download_json_file().', e);
        return;
    }

    var blob  = new Blob([json_str], { type: 'application/json;charset=utf-8' });
    var URL_  = window.URL || window.webkitURL;
    var url   = URL_.createObjectURL(blob);

    // keep close to your jQuery approach
    var $download_link = ai4seo_normalize_$('<a></a>');
    var $body          = ai4seo_normalize_$('body', document);

    if (!ai4seo_exists_$($download_link) || !$download_link.length || !ai4seo_exists_$($body) || !$body.length) {
        console.error('AI for SEO: Unable to create or find elements in ai4seo_download_json_file().');
        try { URL_.revokeObjectURL(url); } catch (e) {}
        return;
    }

    $download_link
        .attr({ href: url, download: filename })
        .css('display', 'none');

    $body.append($download_link);

    // click the real DOM node for better browser compatibility
    var download_link_element = $download_link.get(0);
    try {
        if (download_link_element && typeof download_link_element.click === 'function') {
            download_link_element.click();
        } else if (download_link_element && download_link_element.dispatchEvent) {
            var evt = document.createEvent('MouseEvents');
            evt.initEvent('click', true, true);
            download_link_element.dispatchEvent(evt);
        } else {
            $download_link.trigger('click');
        }
    } finally {
        // defer cleanup so the download can start
        setTimeout(function () {
            try { $download_link.remove(); } catch (e) {}
            try { URL_.revokeObjectURL(url); } catch (e) {}
        }, 0);
    }
}


// =========================================================================================== \\

let ai4seo_import_new_settings = null;
let ai4seo_import_categories = null;

function ai4seo_show_import_settings_preview(new_settings, categories) {
    let $import_button = ai4seo_normalize_$('#ai4seo-import-settings-button');

    let import_settings_data = {
        ai4seo_new_settings: new_settings,
        ai4seo_import_categories: categories,
        ai4seo_import_mode: 'preview'
    }

    // keep the new settings and categories for later use
    ai4seo_import_new_settings = new_settings;
    ai4seo_import_categories = categories;

    ai4seo_open_ajax_modal('ai4seo_show_import_settings_preview', import_settings_data, {modal_size: 'small'});

    ai4seo_remove_loading_html_from_element($import_button);
}

// =========================================================================================== \\


/**
 * Execute the actual import after user confirmation
 */
function ai4seo_execute_import_settings($import_button, new_settings, categories) {
    $import_button = ai4seo_normalize_$($import_button);

    // check if ai4seo_import_new_settings and ai4seo_import_categories
    if (!ai4seo_import_new_settings || !ai4seo_import_categories) {
        ai4seo_show_error_toast(
            47186725,
            wp.i18n.__('No settings to import. Please select a valid JSON file first.', 'ai-for-seo')
        );
        return;
    }

    // Add loading animation
    ai4seo_add_loading_html_to_element($import_button);

    let import_settings_data = {
        ai4seo_new_settings: ai4seo_import_new_settings,
        ai4seo_import_categories: ai4seo_import_categories,
        ai4seo_import_mode: 'execute'
    }

    // show loading toast
    ai4seo_show_loading_toast(wp.i18n.__('Importing settings...', 'ai-for-seo'));

    // Execute import
    ai4seo_perform_ajax_call('ai4seo_import_settings', import_settings_data)
        .then(response => {
            ai4seo_close_all_modals();
            ai4seo_show_success_toast(wp.i18n.__('Settings imported successfully! The page will reload.', 'ai-for-seo'));
        })
        .catch(error => {
            ai4seo_show_generic_error_toast(19813181225);
            ai4seo_remove_loading_html_from_element($import_button);
        })
        .finally(() => {
            // Reload page after short delay
            setTimeout(() => ai4seo_safe_page_load(), 1000);
        });
}


// ___________________________________________________________________________________________ \\
// === DASHBOARD AUTO-REFRESH ================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Metrics counters (disabled by default, enable via debug flag)
const ai4seo_dashboard_debug_counter_enabled = false; // Toggle debug counter visibility
const ai4seo_dashboard_debug_metrics = false;
const ai4seo_dashboard_metrics = {
    refresh_attempts: 0,
    cancelled_responses: 0,
    no_change_streak_length: 0,
    hidden_mode_triggers: 0,
    full_reload_triggers: 0,
    user_interaction_locks: 0,
    last_ajax_response_duration_ms: 0
};

// Global variables for dashboard auto-refresh
let ai4seo_dashboard_refresh_timer = null;
let ai4seo_dashboard_refresh_lock = false;
const ai4seo_dashboard_refresh_interval = 10000; // 10 seconds # todo: change this to 10000
let ai4seo_dashboard_is_hidden = false;
let ai4seo_dashboard_refresh_failures = 0;
const ai4seo_dashboard_max_failures = 5;

// Enhanced refresh system variables
let ai4seo_dashboard_hidden_start_time = null;
let ai4seo_dashboard_hidden_refresh_timer = null;
let ai4seo_dashboard_hidden_reload_timer = null;
let ai4seo_dashboard_no_changes_streak = 0;
let ai4seo_dashboard_adaptive_interval = 10000; // Base interval for adaptive scaling
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
    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    // Only initialize if we're on the dashboard page
    if (!ai4seo_exists_$($dashboard)) {
        ai4seo_console_debug('AI for SEO: $dashboard missing in ai4seo_init_dashboard_refresh() \u2014 cannot initialize dashboard refresh cycle.');
        return;
    }

    ai4seo_init_dashboard_progress_bar();

    // Set up visibility change listener for pause/resume
    const $document = ai4seo_normalize_$(document);

    if (ai4seo_exists_$($document)) {
        $document.off('visibilitychange.ai4seo-dashboard', ai4seo_handle_dashboard_visibility_change);
        $document.on('visibilitychange.ai4seo-dashboard', ai4seo_handle_dashboard_visibility_change);
    }

    // Set up user interaction listeners
    ai4seo_init_dashboard_user_interaction_listeners();

    // Clear any existing timers
    ai4seo_clear_all_dashboard_timers();

    // Initialize user click tracking
    ai4seo_dashboard_last_user_click = Date.now();
    ai4seo_schedule_dashboard_idle_reload_check();

    // Start the refresh cycle
    ai4seo_schedule_dashboard_refresh();

    // Clean up on page unload
    const $window = ai4seo_normalize_$(window);

    if (ai4seo_exists_$($window)) {
        $window.off('beforeunload.ai4seo-dashboard', ai4seo_clear_all_dashboard_timers);
        $window.on('beforeunload.ai4seo-dashboard', ai4seo_clear_all_dashboard_timers);
    }
}

// =========================================================================================== \\

/**
 * Initialize user interaction listeners for dashboard refresh control
 */
function ai4seo_init_dashboard_user_interaction_listeners() {
    const $document = ai4seo_normalize_$(document);

    if (!ai4seo_exists_$($document)) {
        console.warn('AI for SEO: Document unavailable in ai4seo_init_dashboard_user_interaction_listeners() \u2014 skipping interaction bindings.');
        return;
    }

    // Click listener with 5-second refresh lock
    $document.off('mousedown.ai4seo-dashboard-interaction', ai4seo_handle_dashboard_click);
    $document.on('mousedown.ai4seo-dashboard-interaction', ai4seo_handle_dashboard_click);

    // Mouse move and scroll listeners with 1-second refresh lock (debounced)
    let ai4seo_move_timeout = null;

    const ai4seo_mousemove_handler = function() {
        if (ai4seo_move_timeout) {
            return; // Debounce high-frequency events
        }
        ai4seo_move_timeout = setTimeout(function() {
            ai4seo_handle_dashboard_mouse_interaction();
            ai4seo_move_timeout = null;
        }, 100); // 100ms debounce
    };

    const ai4seo_scroll_handler = function() {
        if (ai4seo_move_timeout) {
            return; // Debounce high-frequency events
        }
        ai4seo_move_timeout = setTimeout(function() {
            ai4seo_handle_dashboard_mouse_interaction();
            ai4seo_move_timeout = null;
        }, 100); // 100ms debounce
    };

    $document.off('mousemove.ai4seo-dashboard-interaction');
    $document.on('mousemove.ai4seo-dashboard-interaction', ai4seo_mousemove_handler);

    $document.off('scroll.ai4seo-dashboard-interaction');
    $document.on('scroll.ai4seo-dashboard-interaction', ai4seo_scroll_handler);
}

// =========================================================================================== \\

/**
 * Handle user clicks - apply 5-second refresh lock and reset intervals
 */
function ai4seo_handle_dashboard_click() {
    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (!ai4seo_exists_$($dashboard)) {
        ai4seo_console_debug('AI for SEO: $dashboard missing in ai4seo_handle_dashboard_click() \u2014 cannot process dashboard click interactions.');
        return;
    }

    // Update metrics
    if (ai4seo_dashboard_debug_metrics) {
        ai4seo_dashboard_metrics.user_interaction_locks++;
    }
    
    // Record click time for idle tracking
    ai4seo_dashboard_last_user_click = Date.now();
    
    // Reset adaptive interval to 10s
    ai4seo_dashboard_adaptive_interval = 10000;
    ai4seo_dashboard_no_changes_streak = 0;

    // Snap to 3 seconds remaining if near finish
    ai4seo_snap_dashboard_refresh_timer(3000);

    // Cancel any in-flight requests
    ai4seo_cancel_dashboard_in_flight_request();
}

// =========================================================================================== \\

/**
 * Handle mouse move and scroll - apply 1-second refresh lock
 */
function ai4seo_handle_dashboard_mouse_interaction() {
    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (!ai4seo_exists_$($dashboard)) {
        ai4seo_console_debug('AI for SEO: $dashboard missing in ai4seo_handle_dashboard_mouse_interaction() \u2014 cannot track dashboard mouse activity.');
        return;
    }
    
    // Record click time for idle tracking
    ai4seo_snap_dashboard_refresh_timer(1000); // Snap to 1 second remaining if near finish
}

// =========================================================================================== \\

/**
 * Snap the refresh timer back to a specific "seconds until refresh" if near finish
 * @param {number} snap_ms - ms to leave until refresh
 */
function ai4seo_snap_dashboard_refresh_timer(snap_ms) {
    if (!ai4seo_dashboard_refresh_end_time) {
        return; // No timer running
    }

    if (ai4seo_dashboard_current_ajax_request) {
        return;
    }

    const now = Date.now();
    const remaining_ms = ai4seo_dashboard_refresh_end_time - now;

    // Only snap if <= snap_seconds seconds left
    if (remaining_ms <= snap_ms) {
        ai4seo_dashboard_refresh_end_time = now + (snap_ms);

        // Clear and re-set the main refresh timeout
        if (ai4seo_dashboard_refresh_timer) {
            clearTimeout(ai4seo_dashboard_refresh_timer);
        }

        ai4seo_dashboard_refresh_timer = setTimeout(ai4seo_fetch_and_update_dashboard, snap_ms);

        // Restart progress bar with adjusted duration
        ai4seo_start_dashboard_progress(snap_ms);
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

    // Mark request as cancelled for idempotent discard
    ai4seo_dashboard_current_ajax_request.cancelled = true;

    // Update metrics
    if (ai4seo_dashboard_debug_metrics) {
        ai4seo_dashboard_metrics.cancelled_responses++;
    }

    // Note: We don't actually abort the request to avoid potential issues,
    // instead we mark it for discard when response arrives
    ai4seo_dashboard_current_ajax_request = null;

    return true;
}

// =========================================================================================== \\

/**
 * Handle browser tab visibility changes - enhanced with inactive behavior
 */
function ai4seo_handle_dashboard_visibility_change() {
    if (document.hidden) {
        // browser tab became hidden
        ai4seo_dashboard_is_hidden = true;
        ai4seo_dashboard_hidden_start_time = Date.now();
        
        // Update metrics
        if (ai4seo_dashboard_debug_metrics) {
            ai4seo_dashboard_metrics.hidden_mode_triggers++;
        }
        
        // Clear all active timers
        ai4seo_clear_all_dashboard_timers();
        
        // Start hidden mode: 3-minute refresh cadence
        ai4seo_schedule_dashboard_hidden_mode_refresh();
        
        // Schedule full reload after 15 minutes of inactivity
        ai4seo_dashboard_hidden_reload_timer = setTimeout(function() {
            if (ai4seo_dashboard_debug_metrics) {
                ai4seo_dashboard_metrics.full_reload_triggers++;
            }
            location.reload();
        }, 15 * 60 * 1000); // 15 minutes
        
    } else {
        // browser tab became visible
        const ai4seo_was_hidden = ai4seo_dashboard_is_hidden;
        ai4seo_dashboard_is_hidden = false;
        ai4seo_dashboard_hidden_start_time = null;
        
        // Clear hidden mode timers
        ai4seo_clear_dashboard_hidden_mode_timers();
        
        if (ai4seo_was_hidden && ai4seo_exists_$('.ai4seo-dashboard')) {
            // Reset adaptive interval to 10s base
            ai4seo_dashboard_adaptive_interval = 10000;
            ai4seo_dashboard_no_changes_streak = 0;
            
            // Trigger immediate refresh
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
    
    ai4seo_dashboard_hidden_refresh_timer = setTimeout(function() {
        if (ai4seo_dashboard_is_hidden && ai4seo_exists_$('.ai4seo-dashboard')) {
            ai4seo_fetch_and_update_dashboard();
            ai4seo_schedule_dashboard_hidden_mode_refresh(); // Schedule next
        }
    }, 3 * 60 * 1000); // 3 minutes
}

// =========================================================================================== \\

/**
 * Clear hidden mode timers
 */
function ai4seo_clear_dashboard_hidden_mode_timers() {
    if (ai4seo_dashboard_hidden_refresh_timer) {
        clearTimeout(ai4seo_dashboard_hidden_refresh_timer);
        ai4seo_dashboard_hidden_refresh_timer = null;
    }
    if (ai4seo_dashboard_hidden_reload_timer) {
        clearTimeout(ai4seo_dashboard_hidden_reload_timer);
        ai4seo_dashboard_hidden_reload_timer = null;
    }
}

// =========================================================================================== \\

/**
 * Schedule idle reload check (monitors for 1+ minute without clicks)
 */
function ai4seo_schedule_dashboard_idle_reload_check() {
    if (ai4seo_dashboard_idle_reload_timer) {
        clearTimeout(ai4seo_dashboard_idle_reload_timer);
    }
    
    ai4seo_dashboard_idle_reload_timer = setTimeout(function() {
        const ai4seo_time_since_click = Date.now() - ai4seo_dashboard_last_user_click;
        
        if (ai4seo_time_since_click >= 60 * 1000) { // 1 minute idle
            // User has been idle for 1+ minute, schedule full reload every 5 minutes
            if (ai4seo_dashboard_debug_metrics) {
                ai4seo_dashboard_metrics.full_reload_triggers++;
            }
            location.reload();
        } else {
            // Not idle yet, check again
            ai4seo_schedule_dashboard_idle_reload_check();
        }
    }, 5 * 60 * 1000); // Check every 5 minutes
}

// =========================================================================================== \\

/**
 * Clear all dashboard timers
 */
function ai4seo_clear_all_dashboard_timers() {
    ai4seo_clear_dashboard_refresh_timer();
    ai4seo_clear_dashboard_hidden_mode_timers();
    
    if (ai4seo_dashboard_user_interaction_timer) {
        clearTimeout(ai4seo_dashboard_user_interaction_timer);
        ai4seo_dashboard_user_interaction_timer = null;
    }
    
    if (ai4seo_dashboard_idle_reload_timer) {
        clearTimeout(ai4seo_dashboard_idle_reload_timer);
        ai4seo_dashboard_idle_reload_timer = null;
    }
}

// =========================================================================================== \\

/**
 * Schedule the next dashboard refresh with enhanced adaptive logic
 */
function ai4seo_schedule_dashboard_refresh() {
    // Precedence rule 1: User interaction locks take top priority
    if (ai4seo_dashboard_user_interaction_lock) {
        return;
    }
    
    // Precedence rule 2: Browser tab visibility state overrides cadence
    if (ai4seo_dashboard_is_hidden) {
        return; // Hidden mode handles its own scheduling
    }
    
    // Don't schedule if refresh is locked
    if (ai4seo_dashboard_refresh_lock) {
        return;
    }

    ai4seo_clear_dashboard_refresh_timer();
    
    let start_dashboard_refresh_delay;
    
    // Precedence rule 3: Failure backoff applies when request fails
    if (ai4seo_dashboard_refresh_failures > 0) {
        // Exponential backoff: 10s -> 20s -> 40s -> 80s -> 120s (max)
        start_dashboard_refresh_delay = Math.min(ai4seo_dashboard_refresh_interval * Math.pow(2, ai4seo_dashboard_refresh_failures), 120000);
    } else {
        // Precedence rule 4: No-change adaptive cadence for successful refreshes
        start_dashboard_refresh_delay = ai4seo_dashboard_adaptive_interval;
    }

    // if element ai4seo-no-dashboard-refresh-delay exists, override start_dashboard_refresh_delay with 1 second
    if (ai4seo_exists_$('#ai4seo-no-dashboard-refresh-delay')) {
        start_dashboard_refresh_delay = 1000;
    }

    ai4seo_start_dashboard_progress(start_dashboard_refresh_delay);

    ai4seo_dashboard_refresh_timer = setTimeout(ai4seo_fetch_and_update_dashboard, start_dashboard_refresh_delay);
}

// =========================================================================================== \\

/**
 * Clear the dashboard refresh timer
 */
function ai4seo_clear_dashboard_refresh_timer() {
    if (ai4seo_dashboard_refresh_timer) {
        clearTimeout(ai4seo_dashboard_refresh_timer);
        ai4seo_dashboard_refresh_timer = null;
    }
}

// =========================================================================================== \\

/**
 * Fetch fresh dashboard HTML and update the DOM
 */
function ai4seo_fetch_and_update_dashboard() {
    // Skip if already refreshing
    if (ai4seo_dashboard_refresh_lock) {
        return;
    }
    
    // Skip if user interaction is locked
    if (ai4seo_dashboard_user_interaction_lock) {
        return;
    }
    
    // Skip if dashboard container no longer exists
    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (!ai4seo_exists_$($dashboard)) {
        //ai4seo_console_debug('AI for SEO: $dashboard missing in ai4seo_fetch_and_update_dashboard() \u2014 cannot refresh dashboard metrics.');
        return;
    }

    // Update metrics
    if (ai4seo_dashboard_debug_metrics) {
        ai4seo_dashboard_metrics.refresh_attempts++;
    }

    // Set lock to prevent concurrent refreshes (single-flight semantics)
    ai4seo_dashboard_refresh_lock = true;

    // Store request reference for cancellation tracking
    const this_request = { cancelled: false };
    ai4seo_dashboard_current_ajax_request = this_request;

    if (ai4seo_dashboard_debug_counter_enabled && ai4seo_exists_$('#ai4seo-dashboard-debug-counter')) {
        setTimeout(function() {
            ai4seo_add_loading_html_to_element(ai4seo_normalize_$('#ai4seo-dashboard-debug-counter'));
        }, 1000);
    }

    let ajax_response_start_time = 0;

    if (ai4seo_dashboard_debug_metrics) {
        console.info('AI for SEO: Dashboard refresh attempt #' + ai4seo_dashboard_metrics.refresh_attempts);
        ajax_response_start_time = performance.now();
    }

    ai4seo_perform_ajax_call('ai4seo_get_dashboard_html', {}, false) // auto_check_response = false
        .then(response => {
            if (ai4seo_dashboard_debug_metrics) {
                let ajax_response_duration = performance.now() - ajax_response_start_time;
                ai4seo_dashboard_metrics.last_ajax_response_duration_ms = ajax_response_duration;
                console.info('AI for SEO: Dashboard AJAX response time: ' + ajax_response_duration.toFixed(2) + 'ms');
            }

            // Check if this request was cancelled (idempotent discard)
            if (this_request.cancelled) {
                return; // Discard response
            }
            
            if (response && typeof response === 'string') {
                const ai4seo_changes_made = ai4seo_update_dashboard_content(response);
                
                // Adaptive interval logic based on changes
                if (ai4seo_changes_made) {
                    // Reset to base interval on changes (rule 5: reset on changes)
                    ai4seo_dashboard_adaptive_interval = 10000;
                    ai4seo_dashboard_no_changes_streak = 0;
                } else {
                    // Increase interval for no changes (rule 7: adaptive cadence)
                    ai4seo_dashboard_no_changes_streak++;
                    ai4seo_dashboard_adaptive_interval = Math.min(
                        10000 + (ai4seo_dashboard_no_changes_streak * 10000), // 20s, 30s, 40s, 50s, 60s
                        60000 // Cap at 60s
                    );
                    
                    if (ai4seo_dashboard_debug_metrics) {
                        ai4seo_dashboard_metrics.no_change_streak_length = ai4seo_dashboard_no_changes_streak;
                    }
                }
                
                // Reset failure count on success
                ai4seo_dashboard_refresh_failures = 0;
            }
        })
        .catch(error => {
            // Check if this request was cancelled
            if (this_request.cancelled) {
                return; // Discard error
            }
            
            // Increment failure count for exponential backoff
            ai4seo_dashboard_refresh_failures = Math.min(ai4seo_dashboard_refresh_failures + 1, ai4seo_dashboard_max_failures);

            // Silently log errors, don't show user notifications for auto-refresh failures
            console.warn('AI for SEO: Dashboard auto-refresh failed (attempt ' + ai4seo_dashboard_refresh_failures + '):', error);
        })
        .finally(() => {
            // Clear request reference
            if (ai4seo_dashboard_current_ajax_request === this_request) {
                ai4seo_dashboard_current_ajax_request = null;
            }
            
            // Release lock and schedule next refresh
            ai4seo_dashboard_refresh_lock = false;
            
            // Schedule next refresh based on current state
            if (ai4seo_dashboard_is_hidden) {
                // Hidden mode handles its own scheduling
                return;
            } else {
                ai4seo_schedule_dashboard_refresh();
            }
        });
}

// =========================================================================================== \\

/**
 * Update dashboard content with new HTML using atomic DOM diffing
 * @param {string} new_html - Fresh HTML content for the dashboard
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_update_dashboard_content(new_html) {
    const start_time = performance.now();

    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (!ai4seo_exists_$($dashboard)) {
        console.warn('AI for SEO: .ai4seo-dashboard container missing in ai4seo_update_dashboard_content() \u2014 cannot update dashboard.');
        return false;
    }

    const current_dashboard_element = $dashboard.get(0);

    if (!current_dashboard_element) {
        return false;
    }

    try {
        // Clear previous changed nodes array
        ai4seo_dashboard_changed_nodes = [];
        
        // Parse new HTML into a DOM tree
        const dom_parser = new DOMParser();
        const new_parsed_dom_html = dom_parser.parseFromString(new_html, 'text/html');
        const $new_dashboard = ai4seo_normalize_$('.ai4seo-dashboard', new_parsed_dom_html);

        if (!ai4seo_exists_$($new_dashboard)) {
            console.warn('AI for SEO: New dashboard content missing .ai4seo-dashboard container');
            return false;
        }

        const new_dashboard_element = $new_dashboard.get(0);

        if (!new_dashboard_element) {
            console.warn('AI for SEO: Unable to normalize new dashboard content.');
            return false;
        }

        // Perform DOM diffing and patching
        const changes_made = ai4seo_diff_and_patch_dashboard(current_dashboard_element, new_dashboard_element);

        // Performance guardrail - if diffing took too long, replace everything next time
        const elapsed_time = performance.now() - start_time;

        if (elapsed_time > 100) {
            console.warn('AI for SEO: Dashboard diff took too long (' + elapsed_time.toFixed(2) + 'ms), consider full replacement');
        }

        // Apply highlighting to changed nodes (requirement 1)
        if (changes_made && ai4seo_dashboard_changed_nodes.length > 0) {
            ai4seo_apply_highlight_animation();
        }

        // If changes were made, reinitialize HTML elements
        if (changes_made) {
            ai4seo_init_html_elements();
        }
        
        return changes_made;

    } catch (error) {
        console.warn('AI for SEO: Dashboard update failed:', error);

        // Fall back to full replacement
        current_dashboard_element.outerHTML = new_html;

        ai4seo_init_html_elements();

        return true; // Assume changes were made in fallback
    }
}

// =========================================================================================== \\

/**
 * Apply highlight animation to changed nodes
 */
function ai4seo_apply_highlight_animation() {
    // Batch DOM writes to avoid layout thrashing
    ai4seo_dashboard_changed_nodes.forEach(function(node) {
        if (node && node.nodeType === Node.ELEMENT_NODE) {
            const $node = ai4seo_normalize_$(node);

            if (ai4seo_exists_$($node)) {
                $node.addClass('ai4seo-transparent-animation');
            }
        }
    });

    // Remove highlighting after 3 seconds
    setTimeout(function() {
        ai4seo_dashboard_changed_nodes.forEach(function(node) {
            if (node && node.nodeType === Node.ELEMENT_NODE) {
                const $node = ai4seo_normalize_$(node);

                if (ai4seo_exists_$($node)) {
                    $node.removeClass('ai4seo-transparent-animation');
                    ai4seo_remove_empty_class_attr(node); // Clean up empty class attributes
                }
            }
        });
        ai4seo_dashboard_changed_nodes = [];
    }, 3000);
}

// =========================================================================================== \\

// Utility: remove empty class attribute
function ai4seo_remove_empty_class_attr(el) {
    if (!el || el.nodeType !== Node.ELEMENT_NODE) {
        return;
    }
    const $element = ai4seo_normalize_$(el);

    if (!ai4seo_exists_$($element)) {
        return;
    }

    const class_attribute = ($element.attr('class') || '').trim();

    if (class_attribute === '') {
        $element.removeAttr('class');
    }
}

// =========================================================================================== \\

/**
 * Perform atomic DOM diffing and patching between old and new dashboard nodes
 * @param {Element} old_dashboard_element - Current dashboard DOM node
 * @param {Element} new_dashboard_element - New dashboard DOM node
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_diff_and_patch_dashboard(old_dashboard_element, new_dashboard_element) {
    let changes_made = false;
    let new_cloned_element = null;

    // Compare node types
    if (old_dashboard_element.nodeType !== new_dashboard_element.nodeType) {
        //console.debug('AI4SEO: Node type changed, replaced entire node: ' + old_node.nodeName + ' to ' + JSON.stringify(new_node));
        new_cloned_element = new_dashboard_element.cloneNode(true);

        old_dashboard_element.parentNode.replaceChild(new_cloned_element, old_dashboard_element);

        // Track replaced node for highlighting
        if (new_cloned_element.nodeType === Node.ELEMENT_NODE) {
            ai4seo_dashboard_changed_nodes.push(new_cloned_element);
        }

        return true;
    }

    // Handle text nodes
    if (old_dashboard_element.nodeType === Node.TEXT_NODE) {
        if (old_dashboard_element.textContent !== new_dashboard_element.textContent) {
            // console.debug('AI4SEO: Text content changed for node: ' + old_node.parentNode.nodeName + ' from ' + old_node.textContent + ' to ' + new_node.textContent);
            old_dashboard_element.textContent = new_dashboard_element.textContent;

            changes_made = true;

            // Track parent element for highlighting (can't highlight text nodes directly)
            if (old_dashboard_element.parentNode && old_dashboard_element.parentNode.nodeType === Node.ELEMENT_NODE) {
                ai4seo_dashboard_changed_nodes.push(old_dashboard_element.parentNode);
            }
        }
        return changes_made;
    }

    // Handle element nodes
    if (old_dashboard_element.nodeType === Node.ELEMENT_NODE) {
        if (ai4seo_is_dashboard_diff_excluded(old_dashboard_element)) {
            return false;
        }

        // Compare tag names
        if (old_dashboard_element.tagName !== new_dashboard_element.tagName) {
            // console.debug('AI4SEO: Tag name changed, replaced entire node: ' + old_node.tagName + ' to ' + new_node.tagName + ' (' + old_node.outerHTML + ' to ' + new_node.outerHTML + ')');
            new_cloned_element = new_dashboard_element.cloneNode(true);

            old_dashboard_element.parentNode.replaceChild(new_cloned_element, old_dashboard_element);

            // Track replaced node for highlighting
            ai4seo_dashboard_changed_nodes.push(new_cloned_element);

            return true;
        }

        // Compare and update attributes
        if (ai4seo_sync_node_attributes(old_dashboard_element, new_dashboard_element)) {
            changes_made = true;

            // Track element for highlighting when attributes change
            ai4seo_dashboard_changed_nodes.push(old_dashboard_element);
        }

        // Compare and update child nodes
        changes_made = ai4seo_sync_child_nodes(old_dashboard_element, new_dashboard_element) || changes_made;
    }

    return changes_made;
}

// =========================================================================================== \\

/**
 * Synchronize attributes between old and new nodes
 * @param {Element} old_element
 * @param {Element} new_element
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_sync_node_attributes(old_element, new_element) {
    let changes_made = false;
    const old_attributes = old_element.attributes;
    const new_attributes = new_element.attributes;

    // Update/add attributes from new node
    for (let i = 0; i < new_attributes.length; i++) {
        const this_new_attributes = new_attributes[i];
        const this_old_attributes_value = old_element.getAttribute(this_new_attributes.name);
        
        if (this_old_attributes_value !== this_new_attributes.value) {
            old_element.setAttribute(this_new_attributes.name, this_new_attributes.value);
            changes_made = true;
        }
    }

    // Remove attributes not in new node
    for (let j = old_attributes.length - 1; j >= 0; j--) {
        const this_old_attributes = old_attributes[j];
        if (!new_element.hasAttribute(this_old_attributes.name)) {
            old_element.removeAttribute(this_old_attributes.name);
            changes_made = true;
        }
    }

    return changes_made;
}

// =========================================================================================== \\

/**
 * Synchronize child nodes between old and new nodes
 * @param {Element} old_container_element
 * @param {Element} new_container_element
 * @returns {boolean} - Whether any changes were made
 */
function ai4seo_sync_child_nodes(old_container_element, new_container_element) {
    let changes_made = false;

    // If the container itself is excluded, skip all children work.
    if (ai4seo_is_dashboard_diff_excluded(old_container_element)) {
        return false;
    }

    let old_container_index = 0;
    let new_container_index = 0;

    function getChildrenPairs() {
        const old_is_dashboard_root =
            old_container_element
            && old_container_element.nodeType === Node.ELEMENT_NODE
            && old_container_element.classList.contains('ai4seo-dashboard');

        // For the dashboard root: element-only prevents index drift from whitespace/newlines.
        const force_elements_only = old_is_dashboard_root === true;

        return {
            old_children: ai4seo_collect_children(old_container_element, force_elements_only),
            new_children: ai4seo_collect_children(new_container_element, force_elements_only)
        };
    }

    function is_ignorable_whitespace_text(node) {
        return node
            && node.nodeType === Node.TEXT_NODE
            && typeof node.textContent === 'string'
            && node.textContent.trim() === '';
    }

    function is_notice_element(node) {
        if (!node || node.nodeType !== Node.ELEMENT_NODE) {
            return false;
        }

        const el = /** @type {Element} */ (node);
        return el.classList.contains('notice') || el.classList.contains('ai4seo-notice') || el.hasAttribute('data-notification-index');
    }

    function get_notice_index(node) {
        if (!node || node.nodeType !== Node.ELEMENT_NODE) {
            return '';
        }

        const el = /** @type {Element} */ (node);
        return el.getAttribute('data-notification-index') || '';
    }

    function is_card_element(node) {
        if (!node || node.nodeType !== Node.ELEMENT_NODE) {
            return false;
        }

        const el = /** @type {Element} */ (node);
        return el.classList.contains('card') || el.classList.contains('ai4seo-card');
    }

    const is_root_dashboard =
        old_container_element
        && old_container_element.nodeType === Node.ELEMENT_NODE
        && /** @type {Element} */ (old_container_element).classList.contains('ai4seo-dashboard');

    let children_pairs = getChildrenPairs();
    let old_children = children_pairs.old_children;
    let new_children = children_pairs.new_children;

    while (old_container_index < old_children.length || new_container_index < new_children.length) {
        const this_old_child = old_children[old_container_index] || null;
        const this_new_child = new_children[new_container_index] || null;

        // Ignore whitespace-only text nodes to avoid alignment drift.
        if (is_ignorable_whitespace_text(this_old_child)) {
            old_container_index++;
            continue;
        }

        if (is_ignorable_whitespace_text(this_new_child)) {
            new_container_index++;
            continue;
        }

        // Treat excluded nodes as "transparent": advance only the side that is excluded.
        if (this_old_child && this_old_child.nodeType === Node.ELEMENT_NODE && ai4seo_is_dashboard_diff_excluded(this_old_child)) {
            old_container_index++;
            continue;
        }

        if (this_new_child && this_new_child.nodeType === Node.ELEMENT_NODE && ai4seo_is_dashboard_diff_excluded(this_new_child)) {
            new_container_index++;
            continue;
        }

        // Case A: old exists, new missing -> removal candidate
        if (this_old_child && !this_new_child) {
            old_container_element.removeChild(this_old_child);
            changes_made = true;

            children_pairs = getChildrenPairs();
            old_children = children_pairs.old_children;
            new_children = children_pairs.new_children;
            continue;
        }

        // Case B: new exists, old missing -> addition candidate
        if (!this_old_child && this_new_child) {
            const this_cloned = this_new_child.cloneNode(true);
            old_container_element.appendChild(this_cloned);
            changes_made = true;

            if (this_cloned.nodeType === Node.ELEMENT_NODE) {
                ai4seo_dashboard_changed_nodes.push(this_cloned);
            }

            children_pairs = getChildrenPairs();
            old_children = children_pairs.old_children;
            new_children = children_pairs.new_children;
            old_container_index++;
            new_container_index++;
            continue;
        }

        // Case C: both exist
        if (this_old_child && this_new_child) {
            // Dashboard top-level heuristic:
            // If a notice disappears, do not "morph" it into the next card.
            if (is_root_dashboard) {
                const old_is_notice = is_notice_element(this_old_child);
                const new_is_notice = is_notice_element(this_new_child);

                if (old_is_notice && new_is_notice) {
                    const old_notice_index = get_notice_index(this_old_child);
                    const new_notice_index = get_notice_index(this_new_child);

                    // If indices differ, most likely the old notice was removed.
                    if (old_notice_index && new_notice_index && old_notice_index !== new_notice_index) {
                        old_container_element.removeChild(this_old_child);
                        changes_made = true;

                        children_pairs = getChildrenPairs();
                        old_children = children_pairs.old_children;
                        new_children = children_pairs.new_children;
                        continue;
                    }
                }

                // Notice -> Card mismatch: remove the old notice (it likely vanished in new markup).
                if (old_is_notice && !new_is_notice && is_card_element(this_new_child)) {
                    old_container_element.removeChild(this_old_child);
                    changes_made = true;

                    children_pairs = getChildrenPairs();
                    old_children = children_pairs.old_children;
                    new_children = children_pairs.new_children;
                    continue;
                }
            }

            changes_made = ai4seo_diff_and_patch_dashboard(this_old_child, this_new_child) || changes_made;
            old_container_index++;
            new_container_index++;
            continue;
        }
    }

    return changes_made;
}


// === DASHBOARD REFRESH PROGRESS BAR ========================================== \\

let ai4seo_dashboard_progress_interval = null;
let ai4seo_dashboard_refresh_end_time = null;

/**
 * Initialize progress bar UI
 */
function ai4seo_init_dashboard_progress_bar() {
    const $wrap_container = ai4seo_normalize_$('.ai4seo-wrap');

    if (!ai4seo_exists_$($wrap_container)) {
        ai4seo_console_debug('AI for SEO: $wrap missing in ai4seo_init_dashboard_progress_bar() \u2014 cannot attach progress UI.');
        return;
    }

    const $dashboard = ai4seo_normalize_$('.ai4seo-dashboard');

    if (!ai4seo_exists_$($dashboard)) {
        ai4seo_console_debug('AI for SEO: $dashboard missing in ai4seo_init_dashboard_progress_bar() \u2014 cannot render progress bar.');
        return;
    }

    // Remove existing if re-init
    const $existing_progress_wrapper = ai4seo_normalize_$('#ai4seo-dashboard-progress-wrapper');

    if (ai4seo_exists_$($existing_progress_wrapper)) {
        $existing_progress_wrapper.remove();
    }

    const $existing_debug_counter = ai4seo_normalize_$('#ai4seo-dashboard-debug-counter');

    if (ai4seo_exists_$($existing_debug_counter)) {
        $existing_debug_counter.remove();
    }

    // Create wrapper
    const $new_dashboard_progress_wrapper = ai4seo_normalize_$('<div>', {
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
    });

    // Create progress bar
    const $new_dashboard_progress_bar = ai4seo_normalize_$('<div>', {
        id: 'ai4seo-dashboard-progress-bar',
        css: {
            height: '100%',
            width: '0%',
            background: 'rgba(84, 163, 203, 0.8)', // light blue, 50% transparent
            transition: 'width 0.1s linear',
            display: ai4seo_dashboard_debug_counter_enabled ? 'block' : 'none'
        }
    });

    $new_dashboard_progress_wrapper.append($new_dashboard_progress_bar);

    $wrap_container.prepend($new_dashboard_progress_wrapper);

    // Create debug counter
    const $new_dashboard_debug_counter = ai4seo_normalize_$('<div>', {
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
            display: ai4seo_dashboard_debug_counter_enabled ? 'block' : 'none'
        }
    });

    $wrap_container.append($new_dashboard_debug_counter);
}

// =========================================================================================== \\

/**
 * Start the progress countdown
 * @param {number} duration_ms - total duration in milliseconds
 */
function ai4seo_start_dashboard_progress(duration_ms) {
    let $dashboard_progress_bar = ai4seo_normalize_$('#ai4seo-dashboard-progress-bar');

    if (!ai4seo_exists_$($dashboard_progress_bar)) {
        ai4seo_console_debug('AI for SEO: $dashboard_progress_bar missing in ai4seo_start_dashboard_progress() — initializing progress UI.');
        ai4seo_init_dashboard_progress_bar();
    }

    $dashboard_progress_bar = ai4seo_normalize_$('#ai4seo-dashboard-progress-bar');

    if (!ai4seo_exists_$($dashboard_progress_bar)) {
        ai4seo_console_debug('AI for SEO: $dashboard_progress_bar missing in ai4seo_start_dashboard_progress() \u2014 cannot start progress bar.');
        return;
    }

    ai4seo_dashboard_refresh_end_time = Date.now() + duration_ms;

    if (ai4seo_dashboard_progress_interval) {
        clearInterval(ai4seo_dashboard_progress_interval);
    }

    ai4seo_dashboard_progress_interval = setInterval(function() {
        const now = Date.now();
        let remaining = ai4seo_dashboard_refresh_end_time - now;
        if (remaining < 0) {
            remaining = 0;
        }

        const percent = 100 - ((remaining / duration_ms) * 100);
        const $progress_bar = ai4seo_normalize_$('#ai4seo-dashboard-progress-bar');

        if (ai4seo_exists_$($progress_bar)) {
            $progress_bar.css('width', percent + '%');
        }

        if (ai4seo_dashboard_debug_counter_enabled) {
            const $debug_counter = ai4seo_normalize_$('#ai4seo-dashboard-debug-counter');

            if (ai4seo_exists_$($debug_counter)) {
                $debug_counter.text(Math.ceil(remaining / 1000) + 's');
            }
        }

        if (remaining <= 0) {
            clearInterval(ai4seo_dashboard_progress_interval);
        }
    }, 100);
}

// =========================================================================================== \\

/**
 * Reset the progress bar immediately
 */
function ai4seo_reset_dashboard_progress() {
    const $progress_bar = ai4seo_normalize_$('#ai4seo-dashboard-progress-bar');

    if (ai4seo_exists_$($progress_bar)) {
        $progress_bar.css('width', '0%');
    }

    if (ai4seo_dashboard_progress_interval) {
        clearInterval(ai4seo_dashboard_progress_interval);
        ai4seo_dashboard_progress_interval = null;
    }

    if (ai4seo_dashboard_debug_counter_enabled) {
        const $debug_counter = ai4seo_normalize_$('#ai4seo-dashboard-debug-counter');

        if (ai4seo_exists_$($debug_counter)) {
            $debug_counter.text('');
        }
    }
}

// === DASHBOARD DIFF EXCLUSIONS ============================================================ \\

// 1) Configure which containers should be frozen during diffing.
//    You can add classes, ids, or attributes. Two generic hooks are included:
//    [data-ai4seo-ignore-during-dashboard-refresh="1"] and .ai4seo-ignore-during-dashboard-refresh
const ai4seo_dashboard_diff_exclude_selectors = [
    '[data-ai4seo-ignore-during-dashboard-refresh="1"]',
    '.ai4seo-ignore-during-dashboard-refresh',
    // Examples for cards you keep open/collapsed:
    '.ai4seo-card.ai4seo-is-open',
    '.ai4seo-card.ai4seo-is-collapsed',
    '.ai4seo-card[data-ai4seo-keep-state="1"]'
];

// =========================================================================================== \\

/**
 * Public API: add more exclusion selectors at runtime.
 * @param {string[]} selectors
 * @return {void}
 */
function ai4seo_register_dashboard_diff_exclusions(selectors) {
    if (!Array.isArray(selectors)) {
        return;
    }
    selectors.forEach(function(sel) {
        if (typeof sel === 'string' && sel.trim() && ai4seo_dashboard_diff_exclude_selectors.indexOf(sel) === -1) {
            ai4seo_dashboard_diff_exclude_selectors.push(sel);
        }
    });
}

// =========================================================================================== \\

/**
 * True if node is inside an excluded container.
 * Matches the node itself or any ancestor with a configured selector.
 * @param {Node} node
 * @return {boolean}
 */
function ai4seo_is_dashboard_diff_excluded(node) {
    if (!node || node.nodeType !== Node.ELEMENT_NODE) {
        return false;
    }
    const el = /** @type {Element} */ (node);

    // Fast path: generic hooks
    if (el.closest('[data-ai4seo-ignore-during-dashboard-refresh="1"], .ai4seo-ignore-during-dashboard-refresh')) {
        return true;
    }

    // Custom selectors
    for (let i = 0; i < ai4seo_dashboard_diff_exclude_selectors.length; i++) {
        const sel = ai4seo_dashboard_diff_exclude_selectors[i];
        try {
            if (el.closest(sel)) {
                return true;
            }
        } catch (e) {
            // Invalid selector should not break diffing
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
 * @returns {jQuery}
 */
function ai4seo_get_toast_container_$() {
    var $container = ai4seo_normalize_$('#ai4seo-toasts');

    if (!ai4seo_exists_$($container)) {
        $container = jQuery('<div id="ai4seo-toasts" class="ai4seo-toast-container" aria-live="polite" aria-atomic="true"></div>');
        jQuery('body').append($container);
    }

    return $container;
}

// =========================================================================================== \\

/**
 * Map toast type to Dashicons classes. Uses built-in WP icons, no extra deps.
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
            // Closest built-in loading-style icon in Dashicons
            dashicon = 'dashicons-hourglass';
            break;

        default:
            dashicon = 'dashicons-yes-alt';
            break;
    }

    return '<span class="ai4seo-toast-icon dashicons ' + dashicon + '" aria-hidden="true"></span>';
}

// =========================================================================================== \\

/**
 * Show a toast. Non-blocking. Auto-hide unless duration <= 0.
 * @param {Object} opts
 *  - type: 'success'|'error'|'warning'|'info' (default: 'success')
 *  - title: string (optional)
 *  - message: string (required)
 *  - duration: number ms (default: 5000; set 0 for sticky)
 *  - id: string (optional, replaces an existing toast with same id)
 *  - actions: [{label, href, onClick}] (optional)
 * @returns {HTMLElement|null}
 */
function ai4seo_show_toast(opts) {
    try {
        if (!opts || !opts.message) {
            ai4seo_console_debug('AI for SEO: ai4seo_show_toast() without message — skipped.');
            return null;
        }

        var type = opts.type || 'info';
        var duration = (typeof opts.duration === 'number') ? opts.duration : 5000;

        var $holder = ai4seo_get_toast_container_$();
        if (!ai4seo_exists_$($holder)) {
            if (window.wp && wp.a11y && wp.a11y.speak) {
                wp.a11y.speak(opts.message);
            }
            return null;
        }

        // Replace same-id toast
        if (opts.id) {
            $holder.find('.ai4seo-toast[data-toast-id="' + opts.id + '"]').remove();
        }

        // remove toasts with css class ai4seo-close-on-new-toast
        $holder.find('.ai4seo-toast.ai4seo-close-on-new-toast').remove();

        var $toast = jQuery('<div class="ai4seo-toast ai4seo-toast-' + type + '" role="status" aria-live="polite"></div>');

        // add id
        if (opts.id) {
            $toast.attr('data-toast-id', opts.id);
        }

        // add ai4seo-close-on-new-toast class when auto_close_on_new_toast is set
        if (opts.auto_close_on_new_toast) {
            $toast.addClass('ai4seo-close-on-new-toast');
        }

        // add content
        var $content = jQuery('<div class="ai4seo-toast-content"></div>');

        $content.append(ai4seo_get_toast_icon_html(type));

        var $message_wrap = jQuery('<div class="ai4seo-toast-message"></div>');

        if (opts.title) {
            $message_wrap.append('<div class="ai4seo-text ai4seo-text-1">' + opts.title + '</div>');
        } else {
            $message_wrap.append('<div class="ai4seo-text ai4seo-text-1">' + ai4seo_get_type_based_fallback_toast_title(type) + '</div>');
        }

        $message_wrap.append('<div class="ai4seo-text ai4seo-text-2">' + opts.message + '</div>');

        // Optional actions
        if (opts.actions && opts.actions.length) {
            var $actions = jQuery('<div class="ai4seo-toast-actions"></div>');
            jQuery.each(opts.actions, function(i, act) {
                if (!act || !act.label) { return; }
                var $action_links = jQuery('<a href="' + (act.href || '#') + '" class="ai4seo-toast-action-link"></a>');
                $action_links.text(act.label);
                if (typeof act.onClick === 'function') {
                    $action_links.on('click', function(e) {
                        e.preventDefault();
                        try { act.onClick(e); } catch (err) { console.error('AI for SEO: toast action error', err); }
                    });
                }
                $actions.append($action_links);
            });
            $message_wrap.append($actions);
        }

        $content.append($message_wrap);

        var $close = jQuery(
            '<button type="button" class="ai4seo-toast-close" aria-label="' + (wp && wp.i18n ? wp.i18n.__('Close', 'ai-for-seo') : 'Close') + '">×</button>'
        );

        // Progress bar
        var $progress = jQuery('<div class="ai4seo-toast-progress"><span></span></div>');

        if (duration > 0) {
            $progress.addClass('active');
            // Set animation duration dynamically to match JS timeout
            $progress.find('span').css('animation-duration', duration + 'ms');
        }

        $toast.append($content).append($close).append($progress);
        $holder.append($toast);

        // SR announce
        if (window.wp && wp.a11y && wp.a11y.speak) {
            var announce = (opts.title ? (opts.title + '. ') : '') + opts.message;
            wp.a11y.speak(announce, 'polite');
        }

        // Slide-in after paint
        setTimeout(function() { $toast.addClass('active'); }, 1);

        // Auto close timers
        var timer1 = null, timer2 = null;
        if (duration > 0) {
            timer1 = setTimeout(function() {
                $toast.removeClass('active');
            }, duration);

            timer2 = setTimeout(function() {
                $progress.removeClass('active');
                setTimeout(function() { $toast.remove(); }, 400);
            }, duration + 300);
        }

        // Manual close
        $close.on('click', function() {
            $toast.removeClass('active');
            if (timer1) { clearTimeout(timer1); }
            if (timer2) { clearTimeout(timer2); }
            setTimeout(function() { $toast.remove(); }, 400);
        });

        return $toast.get(0);
    } catch (e) {
        console.error('AI for SEO: ai4seo_show_toast() failed', e);
        return null;
    }
}

// =========================================================================================== \\

/**
 * Clear all toasts.
 * @return {void}
 */
function ai4seo_clear_all_toasts() {
    var $holder = ai4seo_get_toast_container_$();
    if (!ai4seo_exists_$($holder)) { return; }
    $holder.find('.ai4seo-toast').remove();
}

// =========================================================================================== \\

/**
 * Capitalize fallback title from type.
 * @param {string} type
 * @return {string}
 */
function ai4seo_get_type_based_fallback_toast_title(type) {
    try {
        if (wp && wp.i18n) {
            switch (type) {
                case 'success':
                    return wp.i18n.__('Success', 'ai-for-seo');

                case 'error':
                    return wp.i18n.__('Error', 'ai-for-seo');

                case 'warning':
                    return wp.i18n.__('Warning', 'ai-for-seo');

                case 'info':
                    return wp.i18n.__('Info', 'ai-for-seo');

                case 'loading':
                    return wp.i18n.__('Please wait', 'ai-for-seo');

                default:
                    return '';
            }
        }
    } catch (e) {}
    return type ? (type.charAt(0).toUpperCase() + type.slice(1)) : 'Info';
}

// =========================================================================================== \\

function ai4seo_calculate_toast_duration_by_message_length(message, factor = 1) {
    const base_duration = 3000; // 3 seconds
    const extra_per_char = 50; // 50 ms per character
    const max_duration = 10000; // 10 seconds

    let calculated_duration = Math.round((base_duration + (message.length * extra_per_char)) * factor);

    return Math.min(calculated_duration, max_duration);
}


// =========================================================================================== \\

function ai4seo_show_success_toast(message, duration) {
    if (!duration) {
        duration = ai4seo_calculate_toast_duration_by_message_length(message);
    }

    return ai4seo_show_toast({
        type: 'success',
        message: message,
        duration: duration
    });
}

// =========================================================================================== \\

function ai4seo_show_error_toast(error_code, message, duration) {
    if (!message) {
        message = wp.i18n.__('An error occurred. Please try again or contact support.', 'ai-for-seo');
    }

    message = message + (error_code ? ' (Error #' + error_code + ')' : '');

    if (!duration) {
        duration = ai4seo_calculate_toast_duration_by_message_length(message, 1.7);
    }

    return ai4seo_show_toast({
        type: 'error',
        message: message,
        duration: duration
    });
}

// =========================================================================================== \\

function ai4seo_show_info_toast(message, duration) {
    if (!duration) {
        duration = ai4seo_calculate_toast_duration_by_message_length(message, 1.3);
    }

    return ai4seo_show_toast({
        type: 'info',
        message: message,
        duration: duration
    });
}

// =========================================================================================== \\

function ai4seo_show_loading_toast(message, duration) {
    if (!duration) {
        duration = 10000;
    }

    if (!message) {
        message = wp.i18n.__('Loading...', 'ai-for-seo');
    }

    return ai4seo_show_toast({
        type: 'loading',
        message: message,
        duration: duration,
        auto_close_on_new_toast: true
    });
}

// =========================================================================================== \\

function ai4seo_show_warning_toast(message, duration) {
    if (!duration) {
        duration = ai4seo_calculate_toast_duration_by_message_length(message, 1.5);
    }

    return ai4seo_show_toast({
        type: 'warning',
        message: message,
        duration: duration
    });
}

// =========================================================================================== \\

function ai4seo_show_generic_saved_successfully_toast() {
    return ai4seo_show_success_toast(wp.i18n.__('Saved successfully.', 'ai-for-seo'));
}

// =========================================================================================== \\

function ai4seo_show_generic_error_toast(error_code, message) {
    if (!error_code) {
        error_code = 912912;
    }

    return ai4seo_show_error_toast(error_code, message);
}