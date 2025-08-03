<?php
/**
 * AJAX handler for showing the import settings preview
 *
 * @since 2.1.0
 */

if (!defined("ABSPATH")) {
    exit;
}

// Check user permissions
if (!ai4seo_can_manage_this_plugin()) {
    return;
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// requires the import settings process file in preview mode
$_REQUEST['ai4seo_import_mode'] = 'preview';
require_once(ai4seo_get_includes_ajax_process_path("import-settings.php"));